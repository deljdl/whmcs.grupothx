<?php
/**
 * Módulo Gerencinet Boleto para WHMCS
 * @author		Mauricio Gofas | gofas.net
 * @see			https://gofas.net/?p=7893
 * @copyright	2017 https://gofas.net
 * @license		https://gofas.net?p=9340
 * @support		https://gofas.net/?p=7856
 * @version		2.2.1
 */

// Require libraries needed for gateway module functions.
include __DIR__ . '/../../../init.php';
include __DIR__ . '/../../../includes/gatewayfunctions.php';
include __DIR__ . '/../../../includes/invoicefunctions.php';

// Require GN PHP SDK
require_once __DIR__ . '/sdk/efi_rest.php';
use Gerencianet\Exception\GerencianetException;
use Gerencianet\Gerencianet;

// Reporta todos os erros e avisos do php independende da configuração do servidor

// Puxa parâmetros de configuração do gateway
$params = getGatewayVariables('gofasgerencianetboleto');

// Morre se o módulo está inativo.
if (!$params['type']) {
	die("Module Not Activated");
}

// Recebe o GN token
$token = $_POST['notification'];

if ($token) {

	if( $params['sandbox'] ){
		$sandbox		= true;
		$client_id		= $params['clientidsandbox'];
		$client_secret	= $params['clientsecretsandbox'];
		$api_mode		= 'sandbox';

	}
	elseif( !$params['sandbox'] ){
		$sandbox		= false;
		$client_id		= $params['clientid'];
		$client_secret	= $params['clientsecret'];
		$api_mode		= 'live';

	}

	if ( $params['admin'] ) {
		$whmcsAdmin	= $params['admin'];
	}
	else {
		$whmcsAdmin	= false;
	}
	$fee		= $params['fee'];



	// Verifica do que se trata a notificação
	$options = [
		'client_id' => $client_id,
		'client_secret' => $client_secret,
		'sandbox' => $sandbox,
	];

	$params = [
		'token' => $token
	];

	try {
		$api = new Gerencianet($options);
		$notification = $api->getNotification($params, []);

		// Prepara dados retornados
		@$notificationDataEnd	= end($notification['data']);
		$notificationData 		= $notificationDataEnd;
		$invoiceId				= $notificationData['custom_id']; // Captura ID da fatura
		$charge_id				= $notificationData['identifiers']['charge_id']; // Captura ID da transação
		$paymentAmount			= (float)($notificationData['value'] / 100); // Valor da transação
		$previousStatus			= $notificationData['status']['previous']; // Status anterior
		$chargeStatus			= $notificationData['status']['current']; // Status atual

		// Debug
		echo 'Notificacao: ',json_encode($notification);

		// Consulta dados da fatura
		$getinvoiceid['invoiceid']	= $invoiceId;
		$invoice_data				= localAPI('getinvoice', $getinvoiceid, $whmcsAdmin);

		$invoice_amount				= (float)$invoice_data['total'];
		$invoiceStatus				= $invoice_data['status'];
		$user_id 					= $invoice_data['userid'];

		// Debug
		echo 'Dados da Fatura: ', json_encode($invoice_data);

	} catch (GerencianetException $e) {
		die( print_r($e->errorDescription) );

	} catch (Exception $e) {
		die( print_r($e->getMessage()) );
	}

	// Confirma pagamento
	if( $chargeStatus === 'paid' and $invoiceStatus === 'Unpaid' ) {

		if ( (int)$paymentAmount * 100 > (int)$invoice_amount * 100 ) {
			$UpdateInvoice = localAPI('updateinvoice', array( 'invoiceid' => $invoiceId, 'newitemdescription' => array('Acréscimos'),'newitemamount' => array((float)($paymentAmount - $invoice_amount))/* , 'total' => $paymentAmount */), $whmcsAdmin );
		}
		echo 'UpdateInvoice: ', json_encode($UpdateInvoice);

 		$addtransvalues['userid']			= $user_id;
 		$addtransvalues['invoiceid']		= $invoiceId;
 		$addtransvalues['description']		= 'Boleto pago!';
 		$addtransvalues['amountin']			= $paymentAmount;
 		if ($fee) {
 			$addtransvalues['fees']			= $fee;
 		} elseif(!$fee) {
 			$addtransvalues['fees']			= '0.00';
 		}
 		$addtransvalues['paymentmethod']	= 'gofasgerencianetboleto';
 		$addtransvalues['transid']			= 'ggnb_'.$api_mode.'_paid-'.$charge_id.'';
 		$addtransvalues['date']				= date('d/m/Y');
		$addtransresult						= localAPI( 'addtransaction' , $addtransvalues, $whmcsAdmin );

		// Debug
		echo 'Add transaction: ', json_encode($addtransresult);

	}

	// Marca como "Não pago" a transação
	if( $chargeStatus === 'unpaid' and $invoiceStatus === 'Unpaid' ) {
		$addtransvalues['userid']			= $user_id;
 		$addtransvalues['invoiceid']		= $invoiceId;
 		$addtransvalues['description']		= 'Boleto inadimplente';
 		$addtransvalues['amountin']			= '0.00';
 		$addtransvalues['fees']				= '0.00';
 		$addtransvalues['paymentmethod']	= 'gofasgerencianetboleto';
 		$addtransvalues['transid']			= 'ggnb_'.$api_mode.'_unpaid-'.$charge_id.'';
 		$addtransvalues['date']				= date('d/m/Y');
		$addtransresult						= localAPI( 'addtransaction' , $addtransvalues, $whmcsAdmin );

		// Debug
		echo 'Resultado: ', json_encode($addtransresult);
	}
	else {
		die('Notificação ignorada.');
	}

} elseif( !$token ) {
	die('Nennhum token válido recebido.');
}