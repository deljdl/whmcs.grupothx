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

/*
 *
 * Ítens para gerar Transação
 *
*/

$metadata = array(
	'custom_id' => (string)$invoice_id,
	'notification_url' => $returnUrl
);
$body = array(
	'items' => $items,
	'metadata' => $metadata
);

// $body2
if ( !$configurations and $discount ) {
	$body2 = array(
		'payment' => array(
			'banking_billet' => array(
				'expire_at' => $billet_duedate,
				'customer' => $customer,
				'discount' => $discount,
				'message' => $message,
				'instructions' => $instructions
			)
		)
	);
	$body3 = array(
		'payment' => array(
			'banking_billet' => array(
				'expire_at' => $billet_duedate,
				'customer' => $customer,
				'discount' => $discount,
				'message' => $message,
				'instructions' => $instructions
			)
		)
	);
		
} elseif ( !$configurations and !$discount ) {
	$body2 = array(
		'payment' => array(
			'banking_billet' => array(
				'expire_at' => $billet_duedate,
				'customer' => $customer,
				//'discount' => $discount,
				'message' => $message,
				'instructions' => $instructions
			)
		)
	);
	$body3 = array(
		'payment' => array(
			'banking_billet' => array(
				'expire_at' => $billet_duedate,
				'customer' => $customer,
				//'discount' => $discount,
				'message' => $message,
				'instructions' => $instructions
			)
		)
	);
					
} elseif ( $configurations and $discount ) {
	$body2 = array(
		'payment' => array(
			'banking_billet' => array(
				'expire_at' => $billet_duedate,
				'customer' => $customer,
				'discount' => $discount,
				'message' => $message,
				'configurations' => $configurations
			)
		)
	);
	$body3 = array(
		'payment' => array(
			'banking_billet' => array(
				'expire_at' => $billet_duedate,
				'customer' => $customer,
				'discount' => $discount,
				'message' => $message,
				'configurations' => $configurations
			)
		)
	);
			
} elseif ( $configurations and !$discount ) {
	$body2 = array(
		'payment' => array(
			'banking_billet' => array(
				'expire_at' => $billet_duedate,
				'customer' => $customer,
				//'discount' => $discount,
				'message' => $message,
				'configurations' => $configurations
			)
		)
	);
	$body3 = array(
		'payment' => array(
			'banking_billet' => array(
				'expire_at' => $billet_duedate,
				'customer' => $customer,
				//'discount' => $discount,
				'message' => $message,
				'configurations' => $configurations
			)
		)
	);
}