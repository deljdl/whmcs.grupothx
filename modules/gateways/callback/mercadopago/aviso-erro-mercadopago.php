<?php require("../../../init.php");
$whmcs->load_function('gateway');
$whmcs->load_function('client');
$command = 'SendEmail';
$values=$vars;
$values = array('messagename' => 'pagamento-nao-aprovado', 'id' => $_GET['id']); 
$adminuser = 'deljdl';
$results = localAPI($command,$values,$adminuser);
if(isset($results['result']) && $results['result'] == 'success')
{
	die(json_encode('<status>OK</status>'));
}
die(json_encode('<status>ERROR</status>'));