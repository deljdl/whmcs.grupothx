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

foreach( glob( __DIR__.'/../params/*.php') as $file1 ) {
	include $file1;
}
foreach( glob( __DIR__.'/../params/customparams/*.php') as $file2 ) {
	if ( file_exists($file2) ) {
		include $file2;
	}
}