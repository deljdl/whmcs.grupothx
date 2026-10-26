<?
use WHMCS\ClientArea;
use WHMCS\Database\Capsule;
require("init.php");
//echo '<br />Debug na linha <strong>'.__LINE__.'</strong><br /><pre>'.print_r($_REQUEST, 1).'</pre>';
$whmcs->load_function('gateway');
$whmcs->load_function('client');
//$whmcs->load_function('invoice');

if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'getDadosBoletoHtmlTHX')
{
	foreach (Capsule::table('gofasgerencianetboleto')->where("invoice_id", '=', $_REQUEST['invoiceid'])->get() as $gorfas)
	{
		die(json_encode(['result' => 'success', 'boleto' => $gorfas] ));
	}
	die(json_encode(['result' => 'error', 'message' => 'Não há boletos para a invoice '.$_REQUEST['invoiceid']] ));
}
die('No Thankssssss');