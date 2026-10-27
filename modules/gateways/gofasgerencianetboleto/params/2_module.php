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

// Parâmetros do Módulo
$module_version	= '2.2.1';
$sandbox		= $params['sandbox'];

if ( $sandbox ) {
	$clientid				= $params['clientidsandbox'];
	$clientsecret			= $params['clientsecretsandbox'];
	$api_mode				= 'sandbox';
	
} elseif (!$sandbox) {
	$clientid				= $params['clientid'];
	$clientsecret			= $params['clientsecret'];
	$api_mode				= 'live';
}

if ( stripos($_SERVER['REQUEST_URI'], 'viewinvoice.php' ) ) { // Verifica se a página é uma fatura
	$isInvoice	= true;
	$debug		= $params['debug'];
	
} else {
	$isInvoice	= false;
	$debug		= false;
}

if ($isInvoice and $params['debug']){
	$debug	= true;
} else {
	$debug	= false;
}

if (!$debug and !$_REQUEST['redirectToBillet']) {
	$redirectToBillet	= $params['redirecttobillet'];
}
if (!$debug and $_REQUEST['redirectToBillet'] === "true") {
	$redirectToBillet	= true;
}
if ( $debug and $_REQUEST['redirectToBillet'] === "true") {
	$redirectToBillet	= true;
	$debug = false;
}
if ($_REQUEST['redirectToBillet'] === "false") {
	$redirectToBillet	= false;
}
$log = $params['log'];

if($debug || $log) {
	$log_result = array();
	$debug_or_log = true;
}
if ($debug) {
			echo '<pre style="height:300px;max-width: 850px;margin: 20px auto;padding: 5px 15px 5px 15px;" class="debug" onfocus="select_all_and_copy(this)" onclick="select_all_and_copy(this)">';
			echo '<h4 style="text-align:center;line-height: 1.4;border-bottom: 1px solid black;padding: 0px 0px 12px 0px;margin: 11px 0px 20px 0px;">Você está vendo essas informações na tela por quê a opção "debug" do módulo<br><b>Gofas Gerencianet Boleto v'.$module_version.'</b> está ativa.</h4>';
			echo '<p>Operações bem sucedidas possuem títulos <span class="ok">verdes</span> e erros são destacados em <span class="erro">vermelho</span></p>';
			echo '<p>Saiba mais sobre como diagnosticar erros e coletar informações para suporte <a target="_blank" href="https://gofas.net/?p=7899&rf=ggnbfatura">neste link</a></p>';
			echo '<h4>Suporte:</h4>';
			echo '<p>Veja várias soluções para dificuldades comuns no <a href="https://gofas.net/forums/forum/whmcs/modulo-gerencianet-boleto-para-whmcs/?rf=ggnbfatura" target="_blank">fórum de suporte do módulo</a>.</p>';
			echo'<p  onfocus="select_all_and_copy(debugDiv)" onclick="select_all_and_copy(debugDiv)"">1) <span style="cursor:copy;text-decoration: underline; ">Clique aqui para copiar as informações de diagnóstico (debug)</span>.</p>';
			echo'<p>2) <a target="_blank" tyle="cursor:alias;" href="https://gofas.net/forums/forum/whmcs/modulo-gerencianet-boleto-para-whmcs/?rf=ggnbfatura">Clique aqui para publicar no fórum do módulo as informações de diagnósico</a>.</p>';
}
$emailonError				= $params['emailonerror'];
$showDueDate				= $params['showduedate'];
$showBarCode				= $params['showbarcode'];
$requireCNPJandCPF			= $params['requirecnpjandcpf'];
$cancelBillet				= $params['cancelbillet'];
$customfCPF					= $params['customfieldcpf'];
$customfCNPJ				= $params['customfieldcnpj'];
$fine						= 0;
$interest					= 0;
$fee						= $params['fee'];

// Dias adicionais à Data de vencimento
if ( $params['diasparavencimento'] ) {
	$diasParaVencimento		= '+'.$params['diasparavencimento'].' days';

} elseif ( $params['diasparavencimento'] == '0' ) {
	$diasParaVencimento		= 'zero';
}

elseif ( !$params['diasparavencimento'] ) {
	$diasParaVencimento		= '+1 day';
}
else {
	$diasParaVencimento		= false;
}

if ($params['message']) { $message				= $params['message'];
}
elseif (!$params['message'] || empty($params['message'])) {
	$message				= '    ';
}

if ( $params['minimunamount'] ) {
	$minimunAmount			= $params['minimunamount'];
}
elseif ( !$params['minimunamount'] || $params['minimunamount'] < 5 ) {
	$minimunAmount			= '5.00' ;
}

if ($params['paybutton']){
	$payButton				= '<img alt="Visualizar Boleto" src="'.$params['paybutton'].'">';
}elseif(!$params['paybutton']){
	$payButton				= 'Visualizar Boleto';
}
if($params['admin']) {
	$whmcsAdmin				= $params['admin'];
}elseif(!$params['admin']){
	$whmcsAdmin				= false;
}

// Instruções
if($params['instruction1']){
	$instruction1			= $params['instruction1'];
}elseif(!$instruction1){
	$instruction1			= 'Sr. Caixa, após vencimento aceitar somente no banco emissor.';
}
if($params['instruction2']){
	$instruction2			= $params['instruction2'];
}elseif(!$instruction2){
	$instruction2			= 'Sr. Caixa, não cobrar juros após o vencimento.';
}
if($params['instruction3']){
	$instruction3			= $params['instruction3'];
}elseif(!$instruction3){
	$instruction3			= 'Sr. Caixa, não cobrar multa após o vencimento.';
}
if($params['instruction4']){
	$instruction4			= $params['instruction4'];
}elseif(!$instruction4){
	$instruction4			= 'Sr. Caixa, aceitar apenas pagamento em dinheiro.';
}

// Instruções ao caixa
$instructions = [
	(string)$instruction1,
	(string)$instruction2,
	(string)$instruction3,
	(string)$instruction4
];

if ( $fine and $interest) {
	$configurations = [
			'fine' => $fine,
			'interest' => $interest,
		];
} elseif ( $fine and !$interest ) {
	$configurations = [
			'fine' => $fine,
			//'interest' => $interest,
		];
} elseif ( !$fine and $interest ) {
	$configurations = [
			//'fine' => $fine,
			'interest' => $interest,
		];
} elseif ( !$fine and !$interest ) {
	$configurations = false;
}
$i1a = 'client_id';$i1b = $clientid; $i2a = 'client_secret';$i2b = $clientsecret;$i3a = 'sandbox';$hsh = 'baaf5b95d55433890bd835cf006772b9462bde8f';$i3b = $sandbox;$i4a = 'debug';$i4b = $debug;$i5a = 'partner_token';$i5b = $hsh;$cnct = [$i1a => $i1b,$i2a => $i2b,$i3a => $i3b,$i4a => $i4b,$i5a => $i5b]; if ($i5b == $hsh) $opt = $cnct;