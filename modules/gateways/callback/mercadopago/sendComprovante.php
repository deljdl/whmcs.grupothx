<?
if(! $_POST)
{
	die('No Thanks');
}
$anexo = false;

if($_FILES)
{
	$path = $_SERVER['DOCUMENT_ROOT'].'/thx/modules/gateways/callback/mercadopago/comprovantescodifi556tr/';
	if(move_uploaded_file($_FILES['comprovante']['tmp_name'], $path.$_FILES['comprovante']['name']))
	{
		$anexo = true;
	}

}

require($_SERVER['DOCUMENT_ROOT']."/thx/init.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/thx/tarifasTHX.php');
$whmcs->load_function('gateway');
$whmcs->load_function('client');
setlocale( LC_ALL, "pt_BR" );
    date_default_timezone_set('America/Sao_Paulo');
$erro = '';

$command = 'OpenTicket';
$postData = array(
    'deptid' => '6',
    'subject' => $_POST['subject'],
    'message' => $_POST['message'],
    'clientid' => $_POST['userid'],
    'priority' => 'Medium',
    'markdown' => true,
);





$adminUsername = 'deljdl'; // Optional for WHMCS 7.2 and later

$results = localAPI($command, $postData, $adminUsername);
header('Location: https://grupothx.com/thx/pagamento/retorno');