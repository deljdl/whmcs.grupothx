<?php

use WHMCS\Database\Capsule;

add_hook('InvoiceCancelled', 1, function ($vars) {
    $invoiceId = (int) ($vars['invoiceid'] ?? 0);
    if ($invoiceId < 1) {
        return;
    }

    $invoice = Capsule::table('tblinvoices')->where('id', $invoiceId)->first();
    if (!$invoice || $invoice->paymentmethod !== 'asaas') {
        return;
    }

    try {
        require_once __DIR__ . '/../../includes/gatewayfunctions.php';
        require_once __DIR__ . '/../../modules/gateways/asaas/api.php';

        $gateway = getGatewayVariables('asaas');
        if (empty($gateway['type']) || ($gateway['environment'] ?? '') !== 'sandbox') {
            return; // Produção permanece bloqueada.
        }

        $charge = Capsule::table('asaas_cobrancas')
            ->where('invoice_id', $invoiceId)
            ->where('environment', 'sandbox')
            ->first();
        if (!$charge || $charge->status === 'DELETED') {
            return;
        }

        $key = trim((string) ($gateway['sandboxApiKey'] ?? ''));
        if ($key === '') {
            throw new RuntimeException('Token Sandbox não configurado.');
        }

        $paymentId = (string) $charge->charge_id;
        $payment = asaas_api($key, 'GET', 'payments/' . rawurlencode($paymentId));

        if ((string) ($payment['id'] ?? '') !== $paymentId
            || (string) ($payment['externalReference'] ?? '') !== 'whmcs-invoice-' . $invoiceId) {
            throw new RuntimeException('Cobrança não corresponde à fatura.');
        }

        $status = (string) ($payment['status'] ?? '');
        if (!in_array($status, ['PENDING', 'OVERDUE'], true)) {
            logModuleCall('asaas', 'cancel_skipped', ['invoice_id' => $invoiceId, 'status' => $status], 'Cobrança não cancelável automaticamente.');
            return;
        }

        $response = asaas_api($key, 'DELETE', 'payments/' . rawurlencode($paymentId));
        if (empty($response['deleted'])) {
            throw new RuntimeException('Asaas não confirmou a exclusão.');
        }

        Capsule::table('asaas_cobrancas')
            ->where('invoice_id', $invoiceId)
            ->where('charge_id', $paymentId)
            ->update(['status' => 'DELETED', 'updated_at' => date('Y-m-d H:i:s')]);

        logModuleCall('asaas', 'cancel_success', ['invoice_id' => $invoiceId, 'payment_id' => $paymentId], 'Cobrança excluída no Sandbox.');
    } catch (\Throwable $e) {
        logModuleCall('asaas', 'cancel_error', ['invoice_id' => $invoiceId], ['error' => $e->getMessage()]);
    }
});
