<?php require("../../../init.php");
$whmcs->load_function('gateway');
$whmcs->load_function('client');

$cookie_name = 'invoice-'.$_GET['id'];
if(isset ($_COOKIE[$cookie_name]))
{
	die(json_encode('ok'));
}
setcookie($cookie_name, $_GET['id'], time() + 31556926);




$command = "updateinvoice";
$adminuser = 'deljdl';
$values["invoiceid"] = $_GET['id'];
$values["paymentmethod"] = 'deposito_ou_transferencia';
$results = localAPI($command,$values,$adminuser);



$command = 'SendEmail';
$values=$vars;
$values = array('messagename' => 'pagamento-iniciado', 'id' => $_GET['id']); 
$adminuser = 'deljdl';
$results = localAPI($command,$values,$adminuser);
if(isset($results['result']) && $results['result'] == 'success')
{
	die(json_encode('ok'));
}
die(json_encode('Erro ao Informar seu pagamento.'));