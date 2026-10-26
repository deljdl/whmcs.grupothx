<?php 
require("../init.php");
include("../includes/functions.php");
include("../includes/gatewayfunctions.php");
include("../includes/invoicefunctions.php");


if(!isset($_GET['i']) || !isset($_GET['gtw']))
{
    die('No Thanks');
}

$gateways_instalados = array('deposito_ou_transferencia', 'boleto_itau_sem_registro', 'itaushopline', 'cielo', 'mercadopago');


if(! in_array($_GET['gtw'],$gateways_instalados))
{
    die('No Gateway');
}


$command = "updateinvoice";
$adminuser = 'deljdl';
$values["invoiceid"] = $_GET['i'];
$values["paymentmethod"] = $_GET['gtw'];
$results = localAPI($command,$values,$adminuser);
die('Atualizando Invoice '.$_GET['i'].' para '.$_GET['gtw'].'<br />'.$results["result"].':'.$results["message"]);