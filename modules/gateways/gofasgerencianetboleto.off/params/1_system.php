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
// Parâmetros do sistema
$companyName				= $params['companyname'];
foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'ggnbwhmcsurl') -> get( array( 'value','created_at' ) ) as $ggnbwhmcsurl_ ) {
	$ggnbwhmcsurl					= $ggnbwhmcsurl_->value;
}
$system_url					= $ggnbwhmcsurl;
$returnUrl					= $system_url.'/modules/gateways/gofasgerencianetboleto/includes/callback.php';
$langPayNow					= $params['langpaynow'];
$moduleDisplayName			= $params['name'];
$moduleName					= $params['paymentmethod'];