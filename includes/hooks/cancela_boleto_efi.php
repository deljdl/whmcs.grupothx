<?php

use WHMCS\Database\Capsule;
include __DIR__ . '/../../gateways/gofasgerencianetboleto/sdk/vendor/autoload.php';
use Gerencianet\Exception\GerencianetException;
use Gerencianet\Gerencianet;

#REQUIRE TARIFAS OBRIGATÓRIO PARA NÃO DAR ERRO NO CRON
if(empty($_SERVER['DOCUMENT_ROOT']))
{
    $_SERVER['DOCUMENT_ROOT'] = '/home/thxcom/public_html';
}
require_once($_SERVER['DOCUMENT_ROOT'].'/tarifasTHX.php');

add_hook('InvoiceCancelled', 1, function($vars) {
	logModuleCall('includes/hooks/cancela_boleto_efi.php','start',$vars );
    $envioEmailLog = '<br />Invoice <strong>'.$vars['invoiceid'].'</strong> foi cancelada.';

    $api_mode				= 'live';

    $options	= array("client_id"=>GERENCIANET_CLIENTID,"client_secret"=>GERENCIANET_CLIENTESECRET,"sandbox"=>GERENCIANET_SANDBOX,"debug"=>GERENCIANET_DEBUG);
    $api		= new Gerencianet($options);

    $invoice_id	= (int)$vars['invoiceid'];
	$invoice	= localAPI('GetInvoice',array( 'invoiceid' => $invoice_id, ), ADMIN_THX);

	logModuleCall('includes/hooks/cancela_boleto_efi.php', 'geninvoices', $invoice);

	// Parâmetros das transações associadas à Fatura
	$trans_idendA				= $invoice['transactions'];
	if($trans_idendA) {
		$trans_idend			= $trans_idendA['transaction'];
	}


	if ($trans_idend) {
		$trans_idp				= end( $trans_idend );
		$trans_id_				= $trans_idp['transid'];
        //echo '<br />trans_id_ -> '.$trans_id_;
		// Verifica se a transação pertence ao módulo
		if ( (strpos( $trans_id_, 'ggnb') !== false) and (strpos( $trans_id_, 'unpaid') !== false or strpos( $trans_id_, 'waiting') !== false ) and strpos( $trans_id_, $api_mode) ) {
			$trans_id					= (int)preg_replace('/[^0-9]/', '', $trans_id_ ); // ggnb_waiting_213630
		}
		else {
			$trans_id				= false;
		}
	}
	else {
		$trans_id				= false;
	}
	logModuleCall('includes/hooks/cancela_boleto_efi.php', 'trans_idendA', $trans_idendA, $trans_idp);



	if ($trans_id) {
		try {
            $envioEmailLog.='<br />Transação  Efí encontrada: <strong>'.$trans_id.'</strong>';
			$id = array('id' => (int)$trans_id);
			$cancelCharge = $api->cancelCharge( $id, array() );
            $envioEmailLog.='<br /><br />Retorno Efí do cancelamento<hr /> Sucesso:<br /><pre>'.print_r($cancelCharge,1).'</pre>';
			logModuleCall('includes/hooks/cancela_boleto_efi.php','cancel_transaction',array('Sucesso:'=>$cancelCharge), '' );
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

		$UpdateTransaction = localAPI('UpdateTransaction', array('transactionid' => $trans_idp['id'], 'transid' => 'ggnb_'.$api_mode.'_canceled-'.$trans_id, ), ADMIN_THX);
	}
	logModuleCall('includes/hooks/cancela_boleto_efi.php', 'cancel_billet', $UpdateTransaction, $cancelCharge, '');
    enviaLogTHXJdl($envioEmailLog);
} );