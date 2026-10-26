<?php die('No Thanks');
$whmcsurl = "https://grupothx.com/thx/dologin.php";
$autoauthkey = 'GrUpO@THXcurrentKeY_-tRErt543ER';
$secret_key =  'RE345trERt-_YeKtnerrucXHT@OpUrG';
$hash2=md5(date('dmYGsi'));

require_once($_SERVER['DOCUMENT_ROOT'].'/tarifasTHX.php');
if (md5($_GET['email'].$secret_key) != $_GET['hash'])
{
	die('No Thanks');
}

$redirect = isset($_GET['thxgo']) ? $_GET['thxgo'] : false;

$timestamp = time();
$email = $_GET['email'];
switch($redirect)
{
	case 'mercadopago':
		$goto = "modules/gateways/callback/mercadopago/?nowlogin={$hash2}&hashjdl={$hash2}&invoiceid=".$_GET['invoice'].'&userid='.$_GET['userid'];
	break;
	case 'cielo':
		$goto = "modules/gateways/callback/mercadopago/?nowlogin={$hash2}&hashjdl={$hash2}&invoiceid=".$_GET['invoice'].'&userid='.$_GET['userid'];
	break;
	case 'cielorecorrente':
		$goto = "modules/gateways/callback/mercadopago/?nowlogin={$hash2}&hashjdl={$hash2}&invoiceid=".$_GET['invoice'].'&userid='.$_GET['userid'];
	break;
	case 'deposito_ou_transferencia':
		$goto = "modules/gateways/callback/mercadopago/?nowlogin={$hash2}&hashjdl={$hash2}&invoiceid=".$_GET['invoice'].'&userid='.$_GET['userid'];
	break;
	case 'deposito':
		$goto = "modules/gateways/callback/mercadopago/?nowlogin={$hash2}&hashjdl={$hash2}&invoiceid=".$_GET['invoice'].'&userid='.$_GET['userid'];
	break;
	case 'itaushopline':
		$goto = "modules/gateways/callback/mercadopago/?nowlogin={$hash2}&hashjdl={$hash2}&invoiceid=".$_GET['invoice'].'&userid='.$_GET['userid'];
	break;
	case 'all':
		$goto = "modules/gateways/callback/mercadopago/?nowlogin={$hash2}&hashjdl={$hash2}&invoiceid=".$_GET['invoice'].'&userid='.$_GET['userid'];
	break;


	default:
		logoffX();
		die("<script>location='https://grupothx.com/thx/clientarea.php';</script>");
	break;
}


$hash = sha1($email.$timestamp.$autoauthkey); # Gera o Hash

# Gera a URL do AutoAuth & Redireciona
$url = $whmcsurl."?email=$email&timestamp=$timestamp&hash=$hash&goto=".urlencode($goto);
header("Location: $url");
exit;

?>