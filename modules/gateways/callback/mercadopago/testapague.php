<?
require($_SERVER['DOCUMENT_ROOT']."/thx/init.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/thx/tarifasTHX.php');
$whmcs->load_function('gateway');
$whmcs->load_function('client');
setlocale( LC_ALL, "pt_BR" );
date_default_timezone_set('America/Sao_Paulo');
$erro = '';
enviaLogTHXJdl('Mercado Pago Envio de Status');
