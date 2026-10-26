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
use WHMCS\Database\Capsule;
if(!defined("WHMCS")) { die("Esse arquivo não pode ser acessado diretamente"); }

// Debug
if ($debug || $log) {

	$log_result["params_"]	= "<p class='ok'>Todas as configurações do módulo</p>";
	$log_result["params"]	= $params;

	// Debug fatura
	$log_result["GetInvoiceResults_"]	= "<p class='ok'>Resultado da consulta por informações da fatura (API interna - WHMCS).</p>";
	$log_result["GetInvoiceResults"]	= $GetInvoiceResults;


	$log_result["trans_id_"]	= "<p class='ok'>Transações registradas por esta fatura - API WHMCS.</p>";
	if ($trans_id) {
		$log_result["trans_id"]	= "Transação existente: ".$trans_id;
	}
	else {
		$log_result["trans_id"]	= "Nenhuma transação registrada.";
	}


	// Debug de juros e multa
	if ( $fine || $interest ) {

		$log_result["configurations___"]	= "<p class='ok'>Configurações de juros e multa</p>";
		$log_result["configurations__"]	=$configurations;

		$log_result["configurations_"]	= "Multa de " .$params["multa"]."% equivale a fine = ". $fine . "";
		$log_result["configurations"]	= "Juros de " .$params["juros"]."% equivale a interest = ". $interest . "";

	}

	// Debug Desconto Personalizado
	if ( $custom_discount ) {
		$log_result["custom_discount__"]	= "<p class='ok'>Desconto personalizado específico do cliente associado à essa Fatura</p>";
		if ($custom_discount_type ) {
			$log_result["custom_discount_"]	= "Tipo de desconto: ". $custom_discount_type. "";
		}
		if ( $custom_discount ) {
			$log_result["custom_discount"]	= "Valor do desconto: ". $custom_discount. "";
		}

	}


	$log_result["disc_item__"]	= "<p class='ok'>Produtos/serviços da fatura - API WHMCS.</p>";
	$log_result["disc_item_"]	= "<p class='ok'>Itens com valor negativo:</p>";
	$log_result["disc_item"]	= $disc_item;

	$log_result["item_"]	= "<p class='ok'>Itens da Fatura:</p>";
	$log_result["item"]	= $item ;


	$log_result["discount_item_"]	= "<p class='ok'>Soma dos itens com valor negativo:</p>";
	$log_result["discount_item"]	= "$discount_item: ". $discount_item. "";
	$log_result["whmcs_discount"]	= "$whmcs_discount: ". $whmcs_discount. "";

	$log_result["discount_valid_until_"]	= "<p class='ok'>Desconto válido até:</p>";
	$log_result["discount_valid_until"]	= $discount_valid_until. " | ". $days_for_discount. " dias antes do vencimento";

	$log_result["calculate_______"]	= "<p class='ok'>Cálculos.</p>";
	$log_result["calculate______"]	= "Hoje: ". date("d/m/Y"). "";
	$log_result["calculate_____"]	= "Vencimento da fatura: ". date("d/m/Y", strtotime($invoice_duedate)). "";
	$log_result["calculate____"]	= "Diferença entre datas: ". $fine_interest_values['due_days']. " dia(s)";
	$log_result["calculate___"]	= "Multa: " . $params['multa']. "% do total";
	$log_result["calculate__"]	= "Juros: " . $params['juros']. "% ao dia";
	$log_result["calculate_"]	= "Valor original ".$invoiceTotal;
	$log_result["calculate"]	= "Total: " .number_format((int)($invoice_amount)/100,  2, ',', '.'). "";


	$log_result["customer__"]	= "<p class='ok'>Dados do cliente enviados à GN API</p>";
	$log_result["customer_cpf"]	= "cpf: " . $cpf ;
	$log_result["customer_cnpj"]	= "cnpj: " . $cnpj ;
	$log_result["customer_"]	= "customer: ";
	$log_result["customer"]		= $customer;
	$log_result["juridical_data"]	= $juridical_data;

	// Debug Functions
	//$log_result["pre"]	= "</pre>";
}
if ($debug) {
	echo '<div id="debugDiv"  onfocus="select_all_and_copy(this)" onclick="select_all_and_copy(this)">',print_r($log_result), "</div></pre>";
}
if ($log) {
	logModuleCall("gofasgerencianetboleto","genarate_billet",array('invoice_id'=>$invoice_id,'user_id'=>$user_id),"", $log_result);
}