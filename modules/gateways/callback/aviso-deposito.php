<?php require("../../../init.php");
$whmcs->load_function('gateway');
$whmcs->load_function('client');
$command = 'SendEmail';
$values=$vars;
$values = array('messagename' => 'pagamento-informado', 'id' => $_GET['id']); 
$adminuser = 'deljdl';
$results = localAPI($command,$values,$adminuser);
if(isset($results['result']) && $results['result'] == 'success')
{
	die(json_encode('ok'));
}
die(json_encode('Erro ao Informar seu pagamento.'));