<?php


//Laravel DataBase
use WHMCS\Database\Capsule;

//Bloqueia o acesso direto ao arquivo
if (!defined("WHMCS")){
	die("Acesso restrito!");
}


add_hook('EmailPreSend', 1, function($vars) {
	logModuleCall('grupothx_hooks', 'EmailPreSend', 'start', $vars, '');
    
    if (
		$vars['messagename'] === 'Invoice Created' ||
		$vars['messagename'] === 'Invoice Payment Reminder' ||
		$vars['messagename'] === 'First Invoice Overdue Notice' ||
		$vars['messagename'] === 'Second Invoice Overdue Notice' ||
		$vars['messagename'] === 'Third Invoice Overdue Notice' ||
		$vars['messagename'] === 'Invoice Modified' ||
		$vars['messagename'] === 'Negativacao' ||
		$vars['messagename'] === 'Negativacao 1' ||
		$vars['messagename'] === 'Negativacao 2' ||
		$vars['messagename'] === 'Negativacao 3' ||
		$vars['messagename'] === 'Negativacao 3' ||
      $vars['messagename'] === 'fatura-atualizada'
	) {
        $invoice = localAPI('GetInvoice', ['invoiceid' => $vars['relid']], false);
        if (($invoice['result'] ?? '') !== 'success') {
            return;
        }
        require_once __DIR__ . '/../thx_payment.php';
        $dadosPagamento = thx_payment_data((int) $vars['relid'], $invoice);
        $grupothx_merge_fields = [
            'grupothx_pix_copia_e_cola' => $dadosPagamento['pix'],
            'grupothx_html_formas_pagamento' => thx_payment_html((int) $vars['relid'], $invoice, true, $dadosPagamento),
        ];

        // Preserva os merge fields existentes dos templates Efí.
        if (($invoice['paymentmethod'] ?? '') === 'gofasgerencianetboleto') {
            $efi = Capsule::table('gofasgerencianetboleto')->where('invoice_id', (int) $vars['relid'])->first();
            if ($efi) {
                $grupothx_merge_fields += [
                    'grupothx_link' => (string) $efi->link,
                    'grupothx_pdf' => (string) $efi->pdf,
                    'grupothx_barcode' => (string) $efi->barcode,
                    'grupothx_expire_at' => date('d/m/Y', strtotime($efi->expire_at)),
                    'grupothx_total' => number_format($efi->total / 100, 2, ',', '.'),
                    'grupothx_charge_id' => (string) $efi->charge_id,
                    'grupothx_api_mode' => (string) $efi->api_mode,
                    'grupothx_qrcode' => (string) $efi->qrcode,
                    'invoice_link' => (string) $efi->link,
                ];
            }
        }

			return $grupothx_merge_fields;
		

    }
	else { // Not
		return;
	}
});



add_hook('EmailTplMergeFields', 1, function($vars) {
   $grupothx = array();
   $grupothx['grupothx_pix_copia_e_cola'] = 'GRUPO THX - PIX Copia e Cola';
   $grupothx['grupothx_html_formas_pagamento'] = 'GRUPO THX - HTML com todas as formas de pagamento disponíveis.';
    return $grupothx;
});