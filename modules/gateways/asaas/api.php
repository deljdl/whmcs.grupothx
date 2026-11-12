<?php
// Funções internas do gateway Asaas. Emissão permitida SOMENTE no Sandbox.
if (!defined('WHMCS')) {
    exit;
}

use WHMCS\Database\Capsule;

function asaas_api($key, $method, $path, $body = null)
{
    $url = 'https://api-sandbox.asaas.com/v3/' . ltrim($path, '/');
    $headers = [
        'accept: application/json',
        'access_token: ' . $key,
        'User-Agent: GrupoTHX-WHMCS-Asaas/1.0',
    ];
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
    }
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 25,
    ]);
    if ($body !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $result = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($result === false || $status < 200 || $status >= 300) {
        // Não logar corpo das respostas: pode conter dados pessoais.
        throw new RuntimeException('Asaas Sandbox: falha de comunicação (HTTP ' . $status . ').');
    }
    $data = json_decode($result, true);
    if (!is_array($data)) {
        throw new RuntimeException('Asaas Sandbox: resposta JSON inválida.');
    }
    return $data;
}

function asaas_install_charge_table()
{
    if (Capsule::schema()->hasTable('asaas_cobrancas')) {
        return;
    }
    Capsule::schema()->create('asaas_cobrancas', function ($table) {
        $table->increments('id');
        $table->unsignedInteger('invoice_id')->unique();
        $table->string('charge_id', 80)->unique();
        $table->string('customer_id', 80);
        $table->string('environment', 16);
        $table->string('status', 40);
        $table->string('invoice_url', 512)->nullable();
        $table->string('bank_slip_url', 512)->nullable();
        $table->decimal('amount', 12, 2);
        $table->date('due_date');
        $table->timestamps();
    });
}

function asaas_cpf_cnpj($clientId, $company)
{
    $rows = Capsule::table('tblcustomfieldsvalues as v')
        ->join('tblcustomfields as f', 'f.id', '=', 'v.fieldid')
        ->where('f.type', 'client')
        ->where('v.relid', $clientId)
        ->where(function ($query) {
            $query->where('f.fieldname', 'like', '%cpf%')
                ->orWhere('f.fieldname', 'like', '%cnpj%');
        })
        ->get(['f.fieldname', 'v.value']);

    $cpf = '';
    $cnpj = '';
    foreach ($rows as $row) {
        $number = preg_replace('/\D/', '', (string) $row->value);
        if (strlen($number) === 11 && stripos($row->fieldname, 'cpf') !== false) {
            $cpf = $number;
        }
        if (strlen($number) === 14 && stripos($row->fieldname, 'cnpj') !== false) {
            $cnpj = $number;
        }
    }
    return $company && $cnpj ? $cnpj : ($cpf ?: $cnpj);
}

/**
 * Registra histórico de cobrança/eventos sem alterar o saldo da fatura.
 * A referência é estável por cobrança e tipo de evento.
 */
function asaas_record_history($invoiceId, $paymentId, $reference, $description)
{
    $invoiceId = (int) $invoiceId;
    if ($invoiceId < 1 || $paymentId === '') {
        throw new RuntimeException('Fatura ou cobrança inválida para registrar transação.');
    }

    $lockName = 'asaas_history_' . md5($reference);
    $acquired = Capsule::connection()->selectOne('SELECT GET_LOCK(?, 15) AS acquired', [$lockName]);
    if ((int) ($acquired->acquired ?? 0) !== 1) {
        throw new RuntimeException('Não foi possível bloquear registro simultâneo da transação.');
    }

    try {
        $existing = Capsule::table('tblaccounts')->where('transid', $reference)->first();
        if ($existing) {
            if ((int) $existing->invoiceid !== $invoiceId) {
                throw new RuntimeException('Referência já utilizada em outra fatura.');
            }
            return;
        }

        $invoice = Capsule::table('tblinvoices')->where('id', $invoiceId)->first();
        if (!$invoice || $invoice->paymentmethod !== 'asaas') {
            throw new RuntimeException('Fatura não localizada com gateway Asaas.');
        }

        $result = localAPI('AddTransaction', [
            'userid' => (int) $invoice->userid,
            'invoiceid' => $invoiceId,
            'paymentmethod' => 'asaas',
            'transid' => $reference,
            'description' => $description,
            'amountin' => '0.00',
            'fees' => '0.00',
        ]);

        if (($result['result'] ?? '') !== 'success') {
            $existing = Capsule::table('tblaccounts')->where('transid', $reference)->first();
            if (!$existing || (int) $existing->invoiceid !== $invoiceId) {
                throw new RuntimeException('WHMCS não registrou a transação: ' . ($result['message'] ?? 'erro desconhecido'));
            }
        }
    } finally {
        Capsule::connection()->selectOne('SELECT RELEASE_LOCK(?) AS released', [$lockName]);
    }
}

function asaas_record_issuance($invoiceId, $paymentId)
{
    asaas_record_history($invoiceId, $paymentId, 'asaas_sandbox_waiting-' . $paymentId,
        'Boleto gerado aguardando pagamento.');
}

function asaas_record_notification($invoiceId, $paymentId, $eventType)
{
    asaas_record_history($invoiceId, $paymentId,
        'asaas_sandbox__notification-' . $paymentId . '[' . $eventType . ']',
        'Notificação Asaas: ' . $eventType);
}

function asaas_sync_prior_notifications($invoiceId, $paymentId)
{
    if (!Capsule::schema()->hasTable('asaas_notificacoes')) {
        return;
    }

    $events = Capsule::table('asaas_notificacoes')
        ->where('payment_id', $paymentId)
        ->where('environment', 'sandbox')
        ->whereNotNull('event_type')
        ->distinct()
        ->pluck('event_type');
    foreach ($events as $eventType) {
        if ($eventType !== '') {
            asaas_record_notification($invoiceId, $paymentId, (string) $eventType);
        }
    }
}

function asaas_get_customer($key, $client)
{
    $clientId = (int) ($client['id'] ?? 0);
    $reference = 'whmcs-client-' . $clientId;
    $existing = asaas_api($key, 'GET', 'customers?externalReference=' . rawurlencode($reference) . '&limit=10');
    $items = $existing['data'] ?? [];
    if (count($items) > 1) {
        throw new RuntimeException('Existem clientes Asaas duplicados para este cadastro. Verifique antes de continuar.');
    }
    if ($items) {
        $id = (string) ($items[0]['id'] ?? '');
        if ($id === '') {
            throw new RuntimeException('Identificador do cliente Asaas inválido.');
        }
        // Asaas não deve enviar notificações aos clientes.
        asaas_api($key, 'PUT', 'customers/' . rawurlencode($id), ['notificationDisabled' => true]);
        return $id;
    }

    $name = trim((string) ($client['firstname'] ?? '') . ' ' . (string) ($client['lastname'] ?? ''));
    $document = asaas_cpf_cnpj($clientId, !empty($client['companyname']));
    if ($clientId < 1 || $name === '' || $document === '') {
        throw new RuntimeException('Preencha o nome e CPF/CNPJ do cliente WHMCS antes de emitir.');
    }
    $created = asaas_api($key, 'POST', 'customers', [
        'name' => $name,
        'cpfCnpj' => $document,
        'email' => (string) ($client['email'] ?? ''),
        'externalReference' => $reference,
        'notificationDisabled' => true,
    ]);
    if (empty($created['id'])) {
        throw new RuntimeException('Asaas não retornou o ID do cliente.');
    }
    return (string) $created['id'];
}

function asaas_save_charge($invoiceId, $charge)
{
    if (empty($charge['id']) || empty($charge['customer'])) {
        throw new RuntimeException('Cobrança sem identificação válida.');
    }
    $data = [
        'charge_id' => (string) $charge['id'],
        'customer_id' => (string) $charge['customer'],
        'environment' => 'sandbox',
        'status' => (string) ($charge['status'] ?? 'PENDING'),
        'invoice_url' => (string) ($charge['invoiceUrl'] ?? ''),
        'bank_slip_url' => (string) ($charge['bankSlipUrl'] ?? ''),
        'amount' => (float) ($charge['value'] ?? 0),
        'due_date' => (string) ($charge['dueDate'] ?? date('Y-m-d')),
        'updated_at' => date('Y-m-d H:i:s'),
    ];
    $found = Capsule::table('asaas_cobrancas')->where('invoice_id', $invoiceId)->first();
    if ($found) {
        if ($found->charge_id !== $data['charge_id']) {
            throw new RuntimeException('Já existe outra cobrança vinculada a esta fatura.');
        }
        Capsule::table('asaas_cobrancas')->where('invoice_id', $invoiceId)->update($data);
    } else {
        $data['invoice_id'] = $invoiceId;
        $data['created_at'] = date('Y-m-d H:i:s');
        Capsule::table('asaas_cobrancas')->insert($data);
    }
}

function asaas_get_or_create_charge($params)
{
    $invoiceId = (int) ($params['invoiceid'] ?? 0);
    $key = trim((string) ($params['sandboxApiKey'] ?? ''));
    if ($invoiceId < 1 || $key === '') {
        throw new RuntimeException('Fatura ou token Sandbox não configurado.');
    }

    // O lock MySQL cobre dois acessos simultâneos à mesma fatura.
    $lockName = 'asaas_invoice_' . $invoiceId;
    $lock = Capsule::connection()->selectOne('SELECT GET_LOCK(?, 15) AS acquired', [$lockName]);
    if ((int) ($lock->acquired ?? 0) !== 1) {
        throw new RuntimeException('Emissão em andamento. Atualize a fatura em alguns instantes.');
    }

    try {
        asaas_install_charge_table();
        $local = Capsule::table('asaas_cobrancas')->where('invoice_id', $invoiceId)->first();
        if ($local) {
            if ($local->environment !== 'sandbox') {
                throw new RuntimeException('Cobrança já vinculada em outro ambiente.');
            }
            asaas_record_issuance($invoiceId, (string) $local->charge_id);
            asaas_sync_prior_notifications($invoiceId, (string) $local->charge_id);
            return (array) $local;
        }

        $reference = 'whmcs-invoice-' . $invoiceId;
        $remote = asaas_api($key, 'GET', 'payments?externalReference=' . rawurlencode($reference) . '&limit=10');
        $items = $remote['data'] ?? [];
        if (count($items) > 1) {
            throw new RuntimeException('Encontradas cobranças duplicadas no Asaas. Emissão bloqueada.');
        }
        if ($items) {
            asaas_save_charge($invoiceId, $items[0]);
            asaas_record_issuance($invoiceId, (string) $items[0]['id']);
            asaas_sync_prior_notifications($invoiceId, (string) $items[0]['id']);
            return (array) Capsule::table('asaas_cobrancas')->where('invoice_id', $invoiceId)->first();
        }

        $invoice = localAPI('GetInvoice', ['invoiceid' => $invoiceId]);
        if (($invoice['result'] ?? '') !== 'success' || ($invoice['status'] ?? '') !== 'Unpaid') {
            throw new RuntimeException('Publique a fatura e deixe-a em aberto antes de emitir o boleto.');
        }
        if (($invoice['paymentmethod'] ?? '') !== 'asaas') {
            throw new RuntimeException('Selecione o gateway Asaas nesta fatura e salve.');
        }
        $amount = (float) ($invoice['balance'] ?? $invoice['total'] ?? 0);
        $minimum = (float) str_replace(',', '.', (string) ($params['minimumAmount'] ?? '5'));
        if ($amount <= 0 || $amount < $minimum) {
            throw new RuntimeException('Valor da fatura abaixo do mínimo permitido para boleto.');
        }
        $dueDate = (string) ($invoice['duedate'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate) || $dueDate < date('Y-m-d')) {
            throw new RuntimeException('A fatura precisa ter uma data de vencimento válida e futura.');
        }

        $client = $params['clientdetails'] ?? [];
        $customerId = asaas_get_customer($key, $client);
        $charge = asaas_api($key, 'POST', 'payments', [
            'customer' => $customerId,
            'billingType' => 'BOLETO',
            'value' => $amount,
            'dueDate' => $dueDate,
            'description' => 'Fatura WHMCS #' . $invoiceId,
            'externalReference' => $reference,
            'postalService' => false,
            'interest' => ['value' => 0],
            'fine' => ['value' => 0],
        ]);
        asaas_save_charge($invoiceId, $charge);
        asaas_record_issuance($invoiceId, (string) $charge['id']);
        return (array) Capsule::table('asaas_cobrancas')->where('invoice_id', $invoiceId)->first();
    } finally {
        Capsule::connection()->selectOne('SELECT RELEASE_LOCK(?) AS released', [$lockName]);
    }
}
