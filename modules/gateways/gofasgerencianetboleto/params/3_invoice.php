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

// Parâmetros da fatura
$invoice_id					= $params['invoiceid'];
$getinvoiceid['invoiceid']	= $invoice_id;
$GetInvoiceResults			= localAPI('getinvoice',$getinvoiceid,$whmcsAdmin);

$invoice_duedate				= $GetInvoiceResults['duedate']; // Data de vencimento da fatura

if ( $invoice_duedate >= date('Y-m-d') ) {
	$billet_duedate			= $invoice_duedate;
	
} elseif( $invoice_duedate < date('Y-m-d') and !$diasParaVencimento ) {
	$billet_duedate			= date('Y-m-d', strtotime('+1 day')); // Se fatura já venceu, data de vencimento do boleto = Hoje + 1 dia
	
} elseif( $invoice_duedate < date('Y-m-d') and $diasParaVencimento and $diasParaVencimento !== 'zero' ) {
	$billet_duedate			= date('Y-m-d', strtotime( $diasParaVencimento )); // Se fatura já venceu, data de vencimento do boleto = Hoje + X dia(s)

} elseif( $invoice_duedate < date('Y-m-d') and $diasParaVencimento and $diasParaVencimento === 'zero' ) {
	$billet_duedate			= date('Y-m-d'); // Se fatura já venceu, data de vencimento do boleto = Hoje
}

$invoiceTotal	=	$GetInvoiceResults['total'];
$invoiceCredit	=	(int)($GetInvoiceResults['credit'] * 100);

// Procura a cobrança existente entre todas as transações, não apenas a última.
$trans_id = false;
$trans_idendA = $GetInvoiceResults['transactions'] ?? array();
$trans_idend = $trans_idendA['transaction'] ?? array();
foreach (array_reverse($trans_idend) as $transacao) {
    $trans_id_ = $transacao['transid'] ?? '';
    if (preg_match('/^ggnb_'.preg_quote($api_mode, '/').'_(?:waiting|unpaid)-([0-9]+)$/', $trans_id_, $matches)) {
        $trans_id = (int)$matches[1];
        break;
    }
}
// Serviços/produtos relacionados à fatura
$invoiceItemsItem	= $GetInvoiceResults['items']['item'];