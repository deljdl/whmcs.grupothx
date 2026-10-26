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

if(!defined('WHMCS')) { die('Esse arquivo não pode ser acessado diretamente'); }
use WHMCS\Database\Capsule;
/*
 *
 * Desconto / Tarifa
 *
*/
$item = array();

if (!function_exists('parseNumberToInt')) {
	function parseNumberToInt($n)
	{
		$n = $n * 100;
		$n = (int)str_replace('.','', $n);
		return $n;
	}

}


// Define desconto personalizado 
foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $params['custom_discount_type'] ) -> where( 'relid', '=', $user_id ) -> get( array( 'value' ) ) as $customfieldvalue ) {
	$custom_discount_type = $customfieldvalue->value;
		
}
foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $params['custom_discount_value'] ) -> where( 'relid', '=', $user_id ) -> get( array( 'value' ) ) as $customfieldvalue ) {
	$custom_discount_value = $customfieldvalue->value;	
}

if($debug_or_log){
	$log_result['custom_discount_type']		= $params['custom_discount_type'] . ' - ' . $custom_discount_type;
	$log_result['custom_discount_value']	=  $params['custom_discount_value']. ' - ' . $custom_discount_value;
}

// Define desconto personalizado 
if ( $custom_discount_value and $custom_discount_type ) {
	$discount_tax			= 1;
	$discount_tax_value		= $custom_discount_value;
	
	if ( strpos( $custom_discount_type, '%' ) !== false ) {
		$discount_tax_type		= 1;
	}
	
	if ( strpos( $custom_discount_type, '$' ) !== false ) {
		$discount_tax_type		= 2;
	}
}
else {
	$discount_tax				= (int)$params['descontooutaxa']; // Define se é desconto ou taxa: 1 = desconto | 2 = taxa
	$discount_tax_type			= (int)$params['tipodescontooutaxa']; // 1 = % | 2 = $  
	$discount_tax_value 		= $params['valordescontooutaxa'];
}

// Validade do desconto
$days_for_discount			= (string)$params['diasantesvencadddesconto']; // $invoice_duedate - $days_for_discount

if ( (int)$days_for_discount >= 1 ) {
	
	$limit_date = new DateTime( $invoice_duedate );
	$limit_date->sub(new DateInterval("P".$days_for_discount."D"));
	$discount_valid_until = $limit_date->format('Y-m-d');

}
if ( (string)$days_for_discount === "0") {
	$discount_valid_until = $invoice_duedate;
}
if ( (string)$days_for_discount !== "0" and !$days_for_discount) {
	$discount_valid_until =  date('Y-m-d');
}

// Define data de validade do desconto
if ( $discount_tax === 1 and $discount_valid_until and $discount_valid_until < date( 'Y-m-d' ) ) {
	$discount_tax_value = 0;
}
if ( $discount_tax === 1 and $discount_tax_value > 0 and $discount_valid_until and $discount_valid_until >= date( 'Y-m-d' ) ) {
		unset($configurations);
		$billet_duedate = $discount_valid_until;
		
		// Instruções ao caixa
		$instructions = [
			(string)'Sr. Caixa, por favor: Não aceitar pagamento após o vencimento.',
			(string)' ',
			(string)' ',
			(string)' ',
		];
}

// Exibir info sobre desconto na fatura
$discount_tax_visible		= $params['exibedescontooutaxa'];

// Desconto do WHMCS / Itens com valor negativo
$disc_item = array();
foreach( $invoiceItemsItem as $Key => $Value){	
		if ($Value['amount'] < 0 ) {
			$n_item = (int)$Value['amount'] * 100;
			$ngtv_item = preg_replace('/[^0-9]/', '', $n_item);
			$negative_item = (int)$ngtv_item;
			$disc_item[] = $negative_item; // Array com itens negativos
			
			$discount_item = (int)array_sum( $disc_item );
		}
}
if ( $invoiceCredit > 0 and $discount_item > 0 ) {
	$whmcs_discount = $invoiceCredit + $discount_item;
}
elseif ( $invoiceCredit > 0 and !$discount_item ) {
	$whmcs_discount = $invoiceCredit;
}
elseif ( !$invoiceCredit and $discount_item > 0 ) {
	$whmcs_discount = $discount_item;
}


// Cálculo de multa e juros
if (!function_exists('ggnb_calculate_fine_interest')) {
function ggnb_calculate_fine_interest( $VALUE, $fine, $interest, $invoice_duedate, $debug ) {

	$today = date('Y-m-d');
	$due_date = date('Y-m-d', strtotime($invoice_duedate));
	$startTimeStamp = strtotime($due_date);
	$endTimeStamp = strtotime($today);
	$timeDiff = abs($endTimeStamp - $startTimeStamp);
	$due_days = $timeDiff/86400;  // 86400 seconds in one day

	// and you might want to convert to integer
	$due_days = intval($due_days);

	if ( $fine and $invoice_duedate >= date('Y-m-d') ) {
		$fine_value = false;
	}
	elseif ( $fine and $invoice_duedate < date('Y-m-d')) {
		$fine_value = ( ( $fine / 100 ) * $VALUE );
		
	}
	if ( $interest and $invoice_duedate >= date('Y-m-d') ) {
		$interest_value = false;
	}
	elseif ( $interest and $invoice_duedate < date('Y-m-d') ) {
		$interest_value = ( ($due_days * $interest) / 1000 ) * $VALUE;
	}
	
	if ( $fine and $interest ) {
		return array(
			'fine_value'=>$fine_value,
			'interest_value'=>$interest_value,
			'due_days' => $due_days,
		);
	}
	elseif ( $fine and !$interest) {
		return array(
			'fine_value'=>$fine_value,
			'due_days' => $due_days,
		);
	}
	elseif ( !$fine and $interest) {
		return array(
			'interest_value'=>$interest_value,
			'due_days' => $due_days,
		);
	}
	elseif ( !$fine and !$interest) {
		//$new_value = $VALUE * 100;
		return false;
	}
}
}

// Desconto em porcentagem %
if ( $discount_tax === 1 and $discount_tax_type === 1 and $discount_tax_value ) {
	$discount_tax_valueRS			= (int)((((float)$invoiceTotal / (float)100 )*(float)$discount_tax_value)*100);
	$invoice_amount__ 				= (int)($invoiceTotal*100);
	$invoice_amount_				= $invoice_amount__ - $discount_tax_valueRS;
	
	$discount_tax_visible_message	.= '<p>Desconto de '.$discount_tax_value.'% (R$'.number_format($discount_tax_valueRS/100,  2, ',', '.' ).') para Boleto';
	
	foreach( $invoiceItemsItem as $itemKey => $itemValue){
		if ($itemValue['amount'] >= 0 ) {
			$item[] = array('name' => substr($itemValue['description'], 0, 255 ), 'amount' => 1, 
			'value' => (int)($itemValue['amount']*100),);
		}
	}

	$discount_value	= (int)($discount_tax_value*100);
	
	if ($whmcs_discount > 0 ) {
		$discount = [
			'type' => 'currency',
			'value' => ($discount_tax_valueRS + $whmcs_discount),
		];
	}
	else {
		$discount = [
			'type' => 'percentage',
			'value' => $discount_value,
		];
	}
}
// Desconto Fixo R$
elseif ( $discount_tax === 1 and $discount_tax_type === 2 and $discount_tax_value ) {
	$discount_tax_valueRS			= (int)($discount_tax_value*100);
	//$invoice_amount 				= (int)($invoiceTotal - $discount_tax_value) * 100;
	$invoice_amount__ 				= (int)($invoiceTotal*100);
	$invoice_amount_				= $invoice_amount__ - $discount_tax_valueRS;
	
	$discount_tax_visible_message	.= '<p>Desconto de R$'.number_format($discount_tax_value,  2, ',', '.' ).' para Boleto </p>';
	
	foreach( $invoiceItemsItem as $itemKey => $itemValue){
		if ($itemValue['amount'] >= 0 ) {
			$item[] = array('name' => substr($itemValue['description'], 0, 255 ), 'amount' => 1, 
			'value' => (int)($itemValue['amount']*100),);
		}
	}
	
	$discount_value	= (int)($discount_tax_value * 100);
	
	if ($whmcs_discount > 0 ) {
		$discount = [
			'type' => 'currency',
			'value' => $discount_value + $whmcs_discount,
		
		];
		
	}
	else {
		$discount = [
			'type' => 'currency',
			'value' => $discount_value,
		
		];
	}
}
// Tarifa em porcentagem %
elseif ( $discount_tax === 2 and $discount_tax_type === 1 and $discount_tax_value ) {
	$discount_tax_valueRS			= (int)((((float)$invoiceTotal / (float)100 )*(float)$discount_tax_value)*100);
	$invoice_amount__ 				= (int)($invoiceTotal*100);
	$invoice_amount_				= $invoice_amount__ + $discount_tax_valueRS;
	$discount_tax_visible_message	.= '<p>Tarifa de '.$discount_tax_value.'% (R$'.number_format($discount_tax_valueRS/100,  2, ',', '.' ) . ') para Boleto</p>';	
	
	foreach( $invoiceItemsItem as $itemKey => $itemValue){
		if ($itemValue['amount'] >= 0 ) {
			$item[] = array('name' => substr($itemValue['description'], 0, 255 ), 'amount' => 1, 
			'value' => (int)($itemValue['amount']*100),);
		}
	}
	$item[] = array('name' => 'Tarifa do boleto', 'amount' => 1, 'value' => $discount_tax_valueRS,);

	if ($whmcs_discount > 0 ) {
		$discount = [
			'type' => 'currency',
			'value' => (int)$whmcs_discount,
		];
	}
}

// Tarifa Fixa R$
elseif ( $discount_tax === 2 and $discount_tax_type === 2 and $discount_tax_value ) {
	
	$discount_tax_valueRS			= (int)($discount_tax_value*100);
	$invoice_amount__ 				= (int)($invoiceTotal*100);
	$invoice_amount_				= $invoice_amount__ + $discount_tax_valueRS;
	
	$discount_tax_visible_message	.= '<p>Tarifa de R$'.number_format($discount_tax_value,  2, ',', '.' ).' para Boleto</p>';	
	
	foreach( $invoiceItemsItem as $itemKey => $itemValue){
		if ($itemValue['amount'] >= 0 ) {
			$item[] = array('name' => substr($itemValue['description'], 0, 255 ), 'amount' => 1, 
			'value' => (int)($itemValue['amount']*100),);
		}
	}
	$item[] = array('name' => 'Tarifa do Boleto', 'amount' => 1, 'value' => $discount_tax_valueRS,);
	if ($whmcs_discount > 0 ) {
		$discount = [
		
			'type' => 'currency',
			'value' => (int)$whmcs_discount,
		
		];
		
	}
	
}
// Valor sem acréscimos ou descontos
elseif ( !$discount_tax_value ) {
	foreach( $invoiceItemsItem as $itemKey => $itemValue){
		if ($itemValue['amount'] >= 0 ) {
			$item[] = array('name' => substr($itemValue['description'], 0, 255 ), 'amount' => 1, 
			'value' => parseNumberToInt($itemValue['amount']),);
		}
	}
	$invoice_amount_ = (int)($invoiceTotal * 100);
	if ($whmcs_discount > 0 ) {
		$discount = [
			'type' => 'currency',
			'value' => (int)$whmcs_discount,
		
		];
	}
}

/// Determine Fine and Interest Line Itens
$fine_interest_values	= array();
$fine_values_arr		= array();
$interest_values_arr		= array();
foreach( $invoiceItemsItem as $itemKey => $itemValue){
	if ($itemValue['amount'] >= 0 ) {
		$fine_interest_values = ggnb_calculate_fine_interest( $itemValue['amount'], $fine, $interest, $invoice_duedate, $debug);
	}
	if($fine_interest_values['fine_value']) {
		$fine_values_arr[] = $fine_interest_values['fine_value'];
	}
	if($fine_interest_values['interest_value'] ) {
		$interest_values_arr[] = $fine_interest_values['interest_value'];
	}
}
if($fine_interest_values['fine_value']) {
$item[] = array('name' => 'Multa por atraso', 'amount' => 1, 
		'value' => (int)array_sum($fine_values_arr), );
}
if($fine_interest_values['interest_value'] ) {
	$item[] = array('name' => 'Juros por atraso', 'amount' => 1, 
		'value' => (int)array_sum($interest_values_arr), );
}

if( $fine_interest_values['fine_value'] and !$fine_interest_values['interest_value'] ) {
	$invoice_amount = $invoice_amount_ + (int)array_sum($fine_values_arr)/100;
	$discount_tax_visible_message	.= '<p>Multa por atraso: R$'.number_format((int)array_sum($fine_values_arr)/100,  2, ',', '.'). '</p>';
	$billet_duedate			= date('Y-m-d');
}
elseif( $fine_interest_values['fine_value'] and $fine_interest_values['interest_value']   ) {
	$invoice_amount = $invoice_amount_ + (int)((int)array_sum($fine_values_arr) + (int)array_sum($interest_values_arr));
	$discount_tax_visible_message	.= '<p>Multa de '.$params['multa'].'% por atraso: R$'.number_format((int)array_sum($fine_values_arr)/100,  2, ',', '.'). '</p>';
	$discount_tax_visible_message	.= '<p>Juros ('.$params['juros'].'% /dia X '.$fine_interest_values['due_days'].' dias): R$'.number_format((int)array_sum($interest_values_arr)/100,  2, ',', '.'). '</p>';
	$billet_duedate			= date('Y-m-d');
}
elseif( !$fine_interest_values['fine_value'] and $fine_interest_values['interest_value']   ) {
	$invoice_amount = $invoice_amount_ + (int)array_sum($interest_values_arr)/100;
	$discount_tax_visible_message	.= '<p>Juros de '.$fine_interest_values['due_days'].' dias de atraso: R$'.number_format((int)array_sum($interest_values_arr)/100,  2, ',', '.'). '</p>';
	$billet_duedate			= date('Y-m-d');
}
else {
	$invoice_amount = $invoice_amount_;
}

$discount_tax_visible_message	.= '<p>Total do Boleto: R$'.number_format((int)($invoice_amount)/100,  2, ',', '.'). '</p>';
// Array to Gateway
$items = $item;