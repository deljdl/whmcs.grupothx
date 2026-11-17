<?php
/**
 * Baixa manual por Pix: cancela a cobrança pendente no gateway original
 * e identifica a fatura como paga por depósito/transferência.
 */
use WHMCS\Database\Capsule;

add_hook('InvoicePaid', 1, function ($vars) {
    $invoiceId = (int) ($vars['invoiceid'] ?? 0);
    if ($invoiceId < 1) {
        return;
    }

    $invoice = Capsule::table('tblinvoices')->where('id', $invoiceId)->first();
    if (!$invoice || $invoice->status !== 'Paid') {
        return;
    }

    $gatewayOriginal = (string) $invoice->paymentmethod;
    if (!in_array($gatewayOriginal, ['asaas', 'gofasgerencianetboleto'], true)) {
        return;
    }

    $pagamento = Capsule::table('tblaccounts')
        ->where('invoiceid', $invoiceId)
        ->where('amountin', '>', 0)
        ->orderBy('id', 'desc')
        ->first();

    if (!$pagamento || $pagamento->paymentmethod !== 'deposito_ou_transferencia') {
        return;
    }

    try {
        require_once __DIR__ . '/../../includes/gatewayfunctions.php';

        if ($gatewayOriginal === 'asaas') {
            require_once __DIR__ . '/../../modules/gateways/asaas/api.php';
            asaas_cancelar_por_pix_manual($invoiceId);
        } else {
            require_once __DIR__ . '/../../modules/gateways/gofasgerencianetboleto/includes/functions.php';
            ggnb_cancelar_por_pix_manual($invoiceId);
        }

        $result = localAPI('UpdateInvoice', [
            'invoiceid' => $invoiceId,
            'paymentmethod' => 'deposito_ou_transferencia',
        ]);
        if (($result['result'] ?? '') !== 'success') {
            throw new \RuntimeException('Não foi possível atualizar o gateway da fatura: ' . ($result['message'] ?? 'erro desconhecido'));
        }

        logModuleCall('pix_manual', 'baixa_confirmada', [
            'invoice_id' => $invoiceId, 'gateway_original' => $gatewayOriginal,
            'transacao_id' => $pagamento->id,
        ], 'Cobrança original cancelada e fatura marcada como Pix.');
    } catch (\Throwable $e) {
        logModuleCall('pix_manual', 'cancelamento_falhou', [
            'invoice_id' => $invoiceId, 'gateway_original' => $gatewayOriginal,
        ], ['error' => $e->getMessage()]);
        logActivity('Pix manual: fatura #' . $invoiceId . ' paga, mas cancelamento da cobrança ' . $gatewayOriginal . ' não foi concluído: ' . $e->getMessage());
    }
});
