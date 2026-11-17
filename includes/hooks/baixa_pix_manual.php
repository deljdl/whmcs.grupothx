<?php
/**
 * Fatura quitada manualmente por Pix: cancela a cobrança pendente no gateway original.
 * Erros não podem interromper ou desfazer a baixa do WHMCS.
 */
use WHMCS\Database\Capsule;

add_hook('InvoicePaid', 1, function ($vars) {
    $invoiceId = (int)($vars['invoiceid'] ?? 0);
    if ($invoiceId < 1) {
        return;
    }
    try {
        $invoice = Capsule::table('tblinvoices')->where('id', $invoiceId)->first();
        if (!$invoice || $invoice->status !== 'Paid'
            || !in_array($invoice->paymentmethod, ['asaas', 'gofasgerencianetboleto'], true)) {
            return;
        }
        $payment = Capsule::table('tblaccounts')->where('invoiceid', $invoiceId)
            ->where('amountin', '>', 0)->orderBy('id', 'desc')->first();
        if (!$payment || $payment->gateway !== 'deposito_ou_transferencia') {
            return;
        }
        require_once __DIR__ . '/../../includes/gatewayfunctions.php';
        if ($invoice->paymentmethod === 'asaas') {
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
            throw new \RuntimeException('Falha ao atualizar forma de pagamento: ' . ($result['message'] ?? 'erro desconhecido'));
        }
        logActivity('Pix manual: fatura #' . $invoiceId . ' quitada; cobrança '
            . $invoice->paymentmethod . ' cancelada e forma de pagamento atualizada.');
    } catch (\Throwable $e) {
        try {
            logModuleCall('pix_manual', 'erro_baixa', ['invoice_id' => $invoiceId], ['error' => $e->getMessage()]);
            logActivity('Pix manual: fatura #' . $invoiceId . ' paga; cancelamento/atualização não concluído: ' . $e->getMessage());
        } catch (\Throwable $ignored) {
        }
    }
});
