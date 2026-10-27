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
require_once __DIR__ . '/../../../../init.php';
require_once __DIR__ . '/../../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../../includes/invoicefunctions.php';
require_once __DIR__ . '/../../../../tarifasTHX.php';

// Require GN PHP SDK
require_once __DIR__ . '/../sdk/efi_rest.php';
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
$token = $_REQUEST['notification'];



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
		$tituloStatus = $chargeStatus;


		enviaLogTHXJdl('Novo post de notificação gerencianet '.date('d/m/Y G:i').'<br /> Token: '.$token.'<br /> Status: '.$chargeStatus.'<br /> Valor Pago: '.$paymentAmount.'<br /> Dados Brutos da ultima notificação: <br /><pre>'.print_r($notificationData, 1).'</pre>');

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
		enviaLogTHXJdl($token.'<br />'.print_r($e->errorDescription, 1));
		die( print_r($e->errorDescription) );

	} catch (Exception $e) {
		enviaLogTHXJdl($token.'<br />'.print_r($e->getMessage(), 1));
		die( print_r($e->getMessage()) );
	}


	$addtransvalues['userid']			= $user_id;
	$addtransvalues['invoiceid']		= $invoiceId;
	$addtransvalues['description']		= 'Notificação EFI';
	$addtransvalues['amountin']			= '0.00';
	$addtransvalues['fees']			= '0.00';
	$addtransvalues['paymentmethod']	= 'gofasgerencianetboleto';
	$addtransvalues['transid']			= 'ggnb_'.$api_mode.'_'.'_notification-'.$charge_id.'['.$chargeStatus.']';
	$addtransvalues['date']				= date('d/m/Y');
	$notificationExists = WHMCS\Database\Capsule::table('tblaccounts')
		->where('transid', $addtransvalues['transid'])
		->exists();
	if (!$notificationExists) {
		$addtransresult = localAPI('addtransaction', $addtransvalues, $whmcsAdmin);
	}



	// Confirma pagamento
	if( ($chargeStatus === 'paid' || $chargeStatus === 'identified')   and $invoiceStatus === 'Unpaid' )
	{
		$valorPagoOuIdentificado = 'ValorPago';
		if($chargeStatus === 'identified')
		{
			$paymentAmount = $invoice_amount;
			$valorPagoOuIdentificado = 'ValorAPagar';
		}

		// if ( (int)$paymentAmount * 100 > (int)$invoice_amount * 100 ) {
		// 	$UpdateInvoice = localAPI('updateinvoice', array( 'invoiceid' => $invoiceId, 'newitemdescription' => array('Acréscimos calculados no momento da emissão do boleto'),'newitemamount' => array((float)($paymentAmount - $invoice_amount))/* , 'total' => $paymentAmount */), $whmcsAdmin );
		// }

		// // - Billet amount is less than the invoice amount
		// if ( (int)$paymentAmount * 100 < (int)$invoice_amount * 100 ) {
		// 	$UpdateInvoice = localAPI('updateinvoice', array( 'invoiceid' => $invoiceId, 'newitemdescription' => array('Descontos calculados no momento da emissão do boleto'),'newitemamount' => array((float)($paymentAmount - $invoice_amount))/* , 'total' => $paymentAmount */), $whmcsAdmin );
		// }
		// echo 'UpdateInvoice: ', json_encode($UpdateInvoice);

		$mensagem = "<br />Iniciada uma baixa em ".date('d/m/Y G:i');

		if($paymentAmount != $invoice_amount)
		{
			$mensagem.= "
			<br /><br />
			Cálculo errado na geração de fatura afetando baixa automática de fatura.
			<br />
			O valor pago é de  <strong>R$ {$paymentAmount}</strong> mas o valor da fatura é de <strong>R$ {$invoice_amount}</strong><br /><br />";

			$diferenca = $paymentAmount - $invoice_amount;
			//if($diferenca < 1.00)
			{
				$mensagem.="<br />A diferença é de {$diferenca}! <br />Verificar!!!";
				//$paymentAmount = $invoice_amount;
			}

		}

		

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
 		$addtransvalues['transid']			= 'ggnb_'.$api_mode.'_'.'_'.$tituloStatus.'-'.$charge_id.'['.$valorPagoOuIdentificado.':'.$paymentAmount.']';
 		$addtransvalues['date']				= date('d/m/Y');
		$paymentTransactionExists = \WHMCS\Database\Capsule::table('tblaccounts')->where('transid', $addtransvalues['transid'])->exists();
		if (!$paymentTransactionExists) {
			$addtransresult = localAPI('addtransaction', $addtransvalues, $whmcsAdmin);
		} else {
			$addtransresult = array('result' => 'success', 'message' => 'Notificação repetida; transação já registrada.');
		}

		// Debug
		echo 'Add transaction: ', json_encode($addtransresult);

		$mensagem.='<br /><br />Dados para a baixa: <br /><hr /><pre>'.print_r($addtransvalues, 1).'</pre><br /><br />';

		$mensagem.='Resultado da baixa: <br /><hr /><pre>'.print_r($addtransresult, 1).'</pre><br /><br />';

		enviaLogTHXJdl($token.'<br />'.$mensagem);

	}
	elseif( $chargeStatus === 'unpaid' and $invoiceStatus === 'Unpaid' )
	{
		$addtransvalues['userid']			= $user_id;
 		$addtransvalues['invoiceid']		= $invoiceId;
 		$addtransvalues['description']		= 'Boleto inadimplente';
 		$addtransvalues['amountin']			= '0.00';
 		$addtransvalues['fees']				= '0.00';
 		$addtransvalues['paymentmethod']	= 'gofasgerencianetboleto';
 		$addtransvalues['transid']			= 'ggnb_'.$api_mode.'_unpaid-'.$charge_id.'';
 		$addtransvalues['date']				= date('d/m/Y');
		$unpaidTransactionExists = \WHMCS\Database\Capsule::table('tblaccounts')->where('transid', $addtransvalues['transid'])->exists();
		if (!$unpaidTransactionExists) {
			$addtransresult = localAPI('addtransaction', $addtransvalues, $whmcsAdmin);
		} else {
			$addtransresult = array('result' => 'success', 'message' => 'Notificação repetida; transação já registrada.');
		}

		// Debug
		echo 'Resultado: ', json_encode($addtransresult);
	}
	else
	{
		//enviaLogTHXJdl($token.'<br />'.'Notificação ignorada.');
		die('');
	}

} elseif( !$token ) {
	enviaLogTHXJdl($token.'<br />'.'Nenhum token válido recebido.');
	die('Nenhum token válido recebido.');
}