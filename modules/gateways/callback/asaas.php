<?php
/**
 * Callback Asaas / WHMCS Grupo THX.
 * Salva cada entrega autenticada e processa pagamentos APENAS do Sandbox.
 */
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
require_once __DIR__ . '/../asaas/api.php';

use WHMCS\Database\Capsule;

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$gateway = getGatewayVariables('asaas');
if (empty($gateway['type'])) {
    http_response_code(503);
    exit('Gateway inativo');
}

$receivedToken = (string) ($_SERVER['HTTP_ASAAS_ACCESS_TOKEN'] ?? '');
$sandboxToken = (string) ($gateway['sandboxWebhookToken'] ?? '');
$productionToken = (string) ($gateway['productionWebhookToken'] ?? '');

$environment = '';
if ($receivedToken !== '' && $sandboxToken !== '' && hash_equals($sandboxToken, $receivedToken)) {
    $environment = 'sandbox';
} elseif ($receivedToken !== '' && $productionToken !== '' && hash_equals($productionToken, $receivedToken)) {
    $environment = 'production';
}
if ($environment === '') {
    http_response_code(401);
    exit('Unauthorized');
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
$eventId = is_array($payload) ? (string) ($payload['id'] ?? '') : '';
$eventType = is_array($payload) ? (string) ($payload['event'] ?? '') : '';
$paymentId = is_array($payload) ? (string) ($payload['payment']['id'] ?? '') : '';

try {
    if (!Capsule::schema()->hasTable('asaas_notificacoes')) {
        Capsule::schema()->create('asaas_notificacoes', function ($table) {
            $table->increments('id');
            $table->string('event_id', 100)->nullable()->index();
            $table->string('event_type', 80)->nullable()->index();
            $table->string('payment_id', 100)->nullable()->index();
            $table->string('environment', 16);
            $table->unsignedInteger('invoice_id')->nullable();
            $table->string('status', 40);
            $table->text('result')->nullable();
            $table->longText('payload');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    $logId = Capsule::table('asaas_notificacoes')->insertGetId([
        'event_id' => $eventId ?: null,
        'event_type' => $eventType ?: null,
        'payment_id' => $paymentId ?: null,
        'environment' => $environment,
        'status' => 'received',
        'payload' => (string) $raw,
    ]);
} catch (\Throwable $e) {
    http_response_code(503);
    exit('Não foi possível armazenar a notificação');
}

$finish = function ($status, $result, $invoiceId = null) use ($logId) {
    Capsule::table('asaas_notificacoes')->where('id', $logId)->update([
        'status' => $status,
        'result' => $result,
        'invoice_id' => $invoiceId,
    ]);
};

try {
    if (!is_array($payload) || $eventId === '' || $eventType === '') {
        $finish('ignored', 'Payload inválido');
        http_response_code(400);
        exit('Invalid payload');
    }

    // Produção fica completamente impedida de realizar baixas.
    if ($environment !== 'sandbox') {
        $finish('ignored', 'Produção bloqueada durante o desenvolvimento');
        exit('OK');
    }

    if (!in_array($eventType, ['PAYMENT_CONFIRMED', 'PAYMENT_RECEIVED'], true)) {
        $finish('ignored', 'Evento registrado sem ação financeira');
        exit('OK');
    }
    if ($paymentId === '') {
        throw new RuntimeException('Evento de pagamento sem ID da cobrança');
    }

    if (!Capsule::schema()->hasTable('asaas_cobrancas')) {
        throw new RuntimeException('Tabela de cobranças não instalada');
    }

    $charge = Capsule::table('asaas_cobrancas')
        ->where('charge_id', $paymentId)
        ->where('environment', 'sandbox')
        ->first();

    if (!$charge) {
        // Guarda o evento, mas requer conciliação da cobrança ainda desconhecida.
        throw new RuntimeException('Cobrança não localizada no WHMCS: ' . $paymentId);
    }

    $invoiceId = (int) $charge->invoice_id;
    $key = trim((string) ($gateway['sandboxApiKey'] ?? ''));
    if ($key === '') {
        throw new RuntimeException('Token API Sandbox não configurado');
    }

    $payment = asaas_api($key, 'GET', 'payments/' . rawurlencode($paymentId));
    if ((string) ($payment['id'] ?? '') !== $paymentId
        || (string) ($payment['externalReference'] ?? '') !== 'whmcs-invoice-' . $invoiceId
        || !in_array((string) ($payment['status'] ?? ''), ['CONFIRMED', 'RECEIVED'], true)) {
        throw new RuntimeException('Cobrança Asaas não confirmada ou não corresponde à fatura');
    }

    $invoice = Capsule::table('tblinvoices')->where('id', $invoiceId)->first();
    if (!$invoice || $invoice->paymentmethod !== 'asaas') {
        throw new RuntimeException('Fatura WHMCS inexistente ou com outro gateway');
    }

    $amount = (float) ($payment['value'] ?? 0);
    if ($amount <= 0 || abs($amount - (float) $charge->amount) > 0.009) {
        throw new RuntimeException('Valor da cobrança divergente da emissão original');
    }

    $transactionId = 'asaas_sandbox_' . $paymentId;
    $lockName = 'asaas_paid_' . $invoiceId;
    $locked = Capsule::connection()->selectOne('SELECT GET_LOCK(?, 15) AS acquired', [$lockName]);
    if ((int) ($locked->acquired ?? 0) !== 1) {
        throw new RuntimeException('Não foi possível obter trava da fatura');
    }

    try {
        $existing = Capsule::table('tblaccounts')->where('transid', $transactionId)->first();
        if ($existing) {
            if ((int) $existing->invoiceid !== $invoiceId) {
                throw new RuntimeException('Transação já associada a outra fatura');
            }
            $finish('duplicate', 'Pagamento já registrado', $invoiceId);
            exit('OK');
        }

        $invoice = Capsule::table('tblinvoices')->where('id', $invoiceId)->first();
        if (!$invoice || $invoice->status !== 'Unpaid' || $invoice->paymentmethod !== 'asaas') {
            $finish('ignored', 'Fatura não está em aberto com gateway Asaas', $invoiceId);
            exit('OK');
        }

        $netValue = isset($payment['netValue']) ? (float) $payment['netValue'] : null;
        $fee = $netValue !== null ? max(0, round($amount - $netValue, 2))
            : (float) str_replace(',', '.', (string) ($gateway['asaasFee'] ?? 0));

        addInvoicePayment($invoiceId, $transactionId, $amount, $fee, 'asaas');
        Capsule::table('asaas_cobrancas')->where('invoice_id', $invoiceId)
            ->where('charge_id', $paymentId)->update([
                'status' => (string) $payment['status'],
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        $finish('paid', 'Pagamento registrado no WHMCS', $invoiceId);
        logTransaction('asaas', [
            'event' => $eventType,
            'event_id' => $eventId,
            'payment_id' => $paymentId,
            'invoice_id' => $invoiceId,
        ], 'Pagamento confirmado');
    } finally {
        Capsule::connection()->selectOne('SELECT RELEASE_LOCK(?) AS released', [$lockName]);
    }

    exit('OK');
} catch (\Throwable $e) {
    $message = substr($e->getMessage(), 0, 500);
    try {
        $finish('error', $message, $invoiceId ?? null);
    } catch (\Throwable $ignored) {
    }
    logModuleCall('asaas', 'webhook_error', [
        'event_id' => $eventId,
        'event_type' => $eventType,
        'payment_id' => $paymentId,
    ], ['error' => $message]);
    http_response_code(503);
    exit('Processing error');
}
