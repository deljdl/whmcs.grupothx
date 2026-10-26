<?php
/**
 * Módulo Gerencinet Dashboard para WHMCS
 * @author		Mauricio Gofas | gofas.net
 * @see			https://gofas.net/?p=7893
 * @copyright	2017 https://gofas.net
 * @license		https://gofas.net?p=9340
 * @support		https://gofas.net/?p=7856
 * @version		2.2.0
 */
use WHMCS\Database\Capsule;
include __DIR__ . '/../../gateways/gofasgerencianetboleto/sdk/vendor/autoload.php';
use Gerencianet\Exception\GerencianetException;
use Gerencianet\Gerencianet;

// Cancel Billet on cancel invoice
add_hook( 'InvoiceCancelled', 1, function($vars) {
	//logModuleCall('gofasgerencianet','start',$vars );
	// Get Module Params
	foreach( Capsule::table('tblpaymentgateways') -> where('gateway', '=', 'gofasgerencianetboleto') -> get( array( 'setting', 'value') ) as $settings ) {
		$setting_key				= $settings->setting;
		$params["$setting_key"]		= $settings->value;
	}
	if ($params['cancelbilletoncancelinvoice']) {
			// Parâmetros do Módulo
			$sandbox	= $params['sandbox'];
			$debug		= $params['debug'];
			if ( $sandbox ) {
				$sandbox				= true;
				$clientid				= $params['clientidsandbox'];
				$clientsecret			= $params['clientsecretsandbox'];
				$api_mode				= 'sandbox';
			}
			 elseif (!$sandbox) {
				$sandbox				= false;
				$clientid				= $params['clientid'];
				$clientsecret			= $params['clientsecret'];
				$api_mode				= 'live';
			}
			$options	= array("client_id"=>$clientid,"client_secret"=>$clientsecret,"sandbox"=>$sandbox,"debug"=>$debug);
			$api		= new Gerencianet($options);
	}
	$whmcs_admin	= $params['admin'];
	//logModuleCall('gofasgerencianet', 'params', $params);

    $invoice_id	= (int)$vars['invoiceid'];
	$invoice	= localAPI('GetInvoice',array( 'invoiceid' => $invoice_id, ), $whmcs_admin);

	//logModuleCall('gofasgerencianet', 'geninvoices', $invoice);

	// Parâmetros das transações associadas à Fatura
	$trans_idendA				= $invoice['transactions'];
	if($trans_idendA) {
		$trans_idend			= $trans_idendA['transaction'];
	}
	if ($trans_idend) {
		$trans_idp				= end( $trans_idend );
		$trans_id_				= $trans_idp['transid'];

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
	//logModuleCall('gofasgerencianet', 'trans_idendA', $trans_idendA, $trans_idp);

	if ($trans_id) {

		try {
			$id = array('id' => (int)$trans_id);
			$cancelCharge = $api->cancelCharge( $id, array() );
			logModuleCall('gofasgerencianet','cancel_transaction',array('Sucesso:'=>$cancelCharge), '' );
		}
		catch (GerencianetException $e) {
			$error	= 'Erro ao cancelar Boleto: ' . $e->errorDescription;
			logModuleCall('gofasgerencianet','cancel_transaction',array('Error:'=>$error), '' );
		}
		catch (Exception $e) {
			$error	= 'Erro ao cancelar Boleto: ' . $e->getMessage();
			logModuleCall('gofasgerencianet','cancel_transaction',array('Error:'=>$error), '' );
		}

		$UpdateTransaction = localAPI('UpdateTransaction', array('transactionid' => $trans_idp['id'], 'transid' => 'ggnb_'.$api_mode.'_canceled-'.$trans_id, ), $whmcs_admin);
	}
	logModuleCall('gofasgerencianet', 'cancel_billet', $UpdateTransaction, $cancelCharge, '');
} // End function($vars)
);


add_hook('EmailPreSend', 1, function($vars) {
	logModuleCall('gofasgerencianet', 'EmailPreSend', 'start', $vars, '');
    // Get Module Options
	foreach( Capsule::table('tblpaymentgateways') -> where('gateway', '=', 'gofasgerencianetboleto') -> get( array( 'setting', 'value') ) as $settings ) {
		$setting_key				= $settings->setting;
		$params["$setting_key"]		= $settings->value;
	}
	//$billetonemail					= $params['billetonemail'];

	// Invoice Created | Invoice Payment Reminder | First Invoice Overdue Notice |  Second Invoice Overdue Notice |  Third Invoice Overdue Notice
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
		$vars['messagename'] === 'Negativacao 3'
	) {
		$ggnb_merge_fields	= array();
		$invoice			= localAPI( 'GetInvoice', array('invoiceid' => $vars['relid']), false);

		logModuleCall('gofasgerencianet', 'EmailPreSend', 'invoice', $invoice, '');




		if( (float)$invoice['total'] > (float)'0.00' and $invoice['paymentmethod'] === 'gofasgerencianetboleto' ) {
			// Saved Billets
			$billet_saved = array();
			foreach( Capsule::table('gofasgerencianetboleto') -> where('invoice_id', '=', $vars['relid']) -> get(
				array( 'invoice_id', 'charge_id', 'link', 'pdf', 'expire_at', 'total', 'barcode', 'charge_id', 'status', 'api_mode', 'qrcode', 'qrcode_image' ) ) as $key => $value ) {
				$billets_for_invoice[$key]					= json_decode(json_encode($value), true);
			}
			$billet_saved = $billets_for_invoice['0']; // Array

			logModuleCall('gofasgerencianet', 'EmailPreSend', 'billet_saved', $billet_saved, '');

			// Merge Fields
			$ggnb_merge_fields['ggnb_link']			= $billet_saved['link'];
			$ggnb_merge_fields['ggnb_pdf']			= $billet_saved['pdf'];
			$ggnb_merge_fields['ggnb_barcode']		= $billet_saved['barcode'];
			$ggnb_merge_fields['ggnb_expire_at']	= date('d/m/Y', strtotime($billet_saved['expire_at']));
			$ggnb_merge_fields['ggnb_total']		= number_format($billet_saved['total']/100,  2, ',', '.' );
			$ggnb_merge_fields['ggnb_charge_id']	= $billet_saved['charge_id'];
			$ggnb_merge_fields['ggnb_api_mode']		= $billet_saved['api_mode'];
			$ggnb_merge_fields['ggnb_qrcode']		= $billet_saved['qrcode'];

			
			

			if($params['linkbilletonemail']) {
				$ggnb_merge_fields['invoice_link']		= $billet_saved['link'];
			}

			$ggnb_merge_fields['ggnb_billet_info']	.= '<br>------------------------------------------------------';
			$ggnb_merge_fields['ggnb_billet_info']	.= '<br>Linha digitável do Boleto:<br><b>'.$billet_saved['barcode'];
			$ggnb_merge_fields['ggnb_billet_info']	.= '</b><br>Vencimento do Boleto: '.date('d/m/Y', strtotime($billet_saved['expire_at'])) ;
			$ggnb_merge_fields['ggnb_billet_info']	.= '<br>Total do Boleto:  R$ '.number_format(($billet_saved['total']/100),  2, ',', '.' ) ;
			$ggnb_merge_fields['ggnb_billet_info']	.= '<br>Código do Boleto: '.$billet_saved['charge_id'];
			$ggnb_merge_fields['ggnb_billet_info']	.= '<br><b><a href="'.$billet_saved['link'].'">Visualizar Boleto em HTML</a></b>';
			$ggnb_merge_fields['ggnb_billet_info']	.= '<br><b><a href="'.$billet_saved['pdf'].'">Visualizar Boleto em PDF</a></b>';
			$ggnb_merge_fields['ggnb_billet_info']	.= '<br>------------------------------------------------------';
			$ggnb_merge_fields['ggnb_debug'] .= "Debug:\n".(string)json_encode($vars).json_encode($invoice);

			logModuleCall('gofasgerencianet', 'EmailPreSend', 'ggnb_merge_fields', $ggnb_merge_fields, '');

			return $ggnb_merge_fields;
		}
		else {
			return;
		}

    }
	else { // Not
		return;
	}
});

// https://developers.whmcs.com/hooks-reference/everything-else/#emailpresend
//Output additional merge fields in the list when editing an email template
add_hook('EmailTplMergeFields', 1, function($vars) {
    $ggnb_merge_fields = array();
	$ggnb_merge_fields['ggnb_billet_info']	= 'GGNB: Informações do boleto';
    $ggnb_merge_fields['ggnb_link']			= 'GGNB: Link para o boleto';
	$ggnb_merge_fields['ggnb_pdf']			= 'GGNB: Link para o boleto em PDF';
	$ggnb_merge_fields['ggnb_barcode']		= 'GGNB: Linha digitável do boleto';
	$ggnb_merge_fields['ggnb_expire_at']	= 'GGNB: Vencimento do boleto';
	$ggnb_merge_fields['ggnb_total']		= 'GGNB: Total do boleto';
	$ggnb_merge_fields['ggnb_charge_id']	= 'GGNB: ID da transação';
	$ggnb_merge_fields['ggnb_api_mode']		= 'GGNB: API mode (sandbox ou live)';
	$ggnb_merge_fields['ggnb_debug']		= 'GGNB: Debug nos emails';
	$ggnb_merge_fields['ggnb_qrcode']		= 'GGNB: Pix Copia e Cola';
    return $ggnb_merge_fields;
});