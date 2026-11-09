<?php

use WHMCS\Database\Capsule;
require_once __DIR__ . '/../../modules/gateways/gofasgerencianetboleto/sdk/vendor/autoload.php';
use Gerencianet\Exception\GerencianetException;
use Gerencianet\Gerencianet;

require_once __DIR__ . '/../../tarifasTHX.php';

add_hook('InvoiceCancelled', 1, function($vars) {
	logModuleCall('includes/hooks/cancela_boleto_efi.php','start',$vars, '' );
    $envioEmailLog = '<br />Invoice <strong>'.$vars['invoiceid'].'</strong> foi cancelada.';

    $api_mode				= 'live';

    $options	= array("client_id"=>GERENCIANET_CLIENTID,"client_secret"=>GERENCIANET_CLIENTESECRET,"sandbox"=>GERENCIANET_SANDBOX,"debug"=>GERENCIANET_DEBUG);
    $invoice_id	= (int)$vars['invoiceid'];
	$invoice	= localAPI('GetInvoice',array( 'invoiceid' => $invoice_id, ), ADMIN_THX);

	logModuleCall('includes/hooks/cancela_boleto_efi.php', 'geninvoices', $invoice, '');

	// Localiza a última cobrança válida, mesmo que existam notificações posteriores.
	$trans_idendA = $invoice['transactions'] ?? [];
	$trans_idend = $trans_idendA['transaction'] ?? [];
	$trans_id = false;
	$trans_idp = [];

	foreach (array_reverse($trans_idend) as $transacao) {
		if (preg_match('/^ggnb_' . preg_quote($api_mode, '/') . '_(?:waiting|unpaid)-(\\d+)$/', $transacao['transid'] ?? '', $matches)) {
			$trans_id = (int)$matches[1];
			$trans_idp = $transacao;
			break;
		}
	}
	logModuleCall('includes/hooks/cancela_boleto_efi.php', 'trans_idendA', $trans_idendA, $trans_idp);
	$UpdateTransaction = null;
	$cancelCharge = null;

	if ($trans_id) {
		try {
			$api = new Gerencianet($options);
            $envioEmailLog.='<br />Transação  Efí encontrada: <strong>'.$trans_id.'</strong>';
			$id = array('id' => (int)$trans_id);
			$cancelCharge = $api->cancelCharge( $id, array() );
            $envioEmailLog.='<br /><br />Retorno Efí do cancelamento<hr /> Sucesso:<br /><pre>'.print_r($cancelCharge,1).'</pre>';
			logModuleCall('includes/hooks/cancela_boleto_efi.php','cancel_transaction',array('Sucesso:'=>$cancelCharge), '' );
			$UpdateTransaction = localAPI('UpdateTransaction', array('transactionid' => $trans_idp['id'], 'transid' => 'ggnb_'.$api_mode.'_canceled-'.$trans_id), ADMIN_THX);
		}
		catch (GerencianetException $e) {
			$error	= 'Erro ao cancelar Boleto: ' . $e->errorDescription;
            $envioEmailLog.='<br />'.$error;
			logModuleCall('includes/hooks/cancela_boleto_efi.php','cancel_transaction',array('Error:'=>$error), '' );
		}
		catch (Exception $e) {
			$error	= 'Erro ao cancelar Boleto: ' . $e->getMessage();
            $envioEmailLog.='<br />'.$error;
			logModuleCall('includes/hooks/cancela_boleto_efi.php','cancel_transaction',array('Error:'=>$error), '' );
		}

	}
	logModuleCall('includes/hooks/cancela_boleto_efi.php', 'cancel_billet', $UpdateTransaction, $cancelCharge, '');
    enviaLogTHXJdl($envioEmailLog);
} );