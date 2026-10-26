<?php
require_once($_SERVER['DOCUMENT_ROOT']."/thx/init.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/thx/tarifasTHX.php');
$whmcs->load_function('gateway');
$whmcs->load_function('client');
setlocale( LC_ALL, "pt_BR" );
date_default_timezone_set('America/Sao_Paulo');
$html = '';

if(!isset($_SESSION['uid']))
{
    header('Location: '.HTTP_WHMCS.'clientarea.php?action=invoices');
}

function moeda($valor,$separador=',')
{
    return str_replace(array(',','.'),'',$valor);
}
function limitaCaracteres($str,$max,$sufixo='')
{
    $str = tiracento($str);
    if(strlen($str) > $max)
        $str=substr($str, 0, $max).$sufixo;
    return $str;
}
function logCiello($titulo,$dados, $idlog)
{
    $quebra = chr(13).chr(10);
    $dados = $titulo.$quebra.'<pre>'.print_r($dados,1).'</pre>'.$quebra;

    $abre = fopen('log-cielo/'.$idlog, 'a');
    $escreve = fwrite($abre, $quebra.$dados);
    fclose($abre);
}

function geraCobrancaRecorrenteCielo($clientsdetails, $idRecorrente, $name, $description, $valor, $recorrencia)
{
	$order = new stdClass();
	$order->OrderNumber = $idRecorrente;
	$order->SoftDescriptor = 'GrupoTHX';
	$order->Cart = new stdClass();
	$order->Cart->Discount = new stdClass();
	$order->Cart->Discount->Type = 'Percent';
	$order->Cart->Discount->Value = 0;
	$order->Cart->Items = array();


	$it=0;
	$order->Cart->Items[$it] = new stdClass();
	$order->Cart->Items[$it]->Name = limitaCaracteres($name, 125, '...');
	$order->Cart->Items[$it]->Description = limitaCaracteres($description, 250, '...');
	$order->Cart->Items[$it]->UnitPrice = moeda($valor);
	$order->Cart->Items[$it]->Quantity = 1;
	$order->Cart->Items[$it]->Type = 'Service';



	$order->Shipping = new stdClass();
	$order->Shipping->Type = 'WithoutShipping';



	$traduzReverso = array(
	'Monthly' => 'Monthly',
	'Quarterly' => 'Quarterly',
	'Semi-Annually' => 'SemiAnnual',
	'Annually' => 'Annual');


	$order->Payment->RecurrentPayment = new stdClass();
	$order->Payment->RecurrentPayment->Interval = $traduzReverso[$recorrencia];
	$order->Payment->RecurrentPayment->EndDate = '';





	$order->Customer = new stdClass();
	$order->Customer->Identity = str_pad((int)$produto['id'], 14, 0, STR_PAD_LEFT);
	$order->Customer->FullName = $clientsdetails['firstname'].' '.$clientsdetails['lastname'];
	$order->Customer->Email = $clientsdetails['email'];

	if(isset($clientsdetails['customfields3'][10]))
	{
		$order->Customer->Identity = preg_replace("/[^0-9]/", "", $clientsdetails['customfields3']);
	}
	//$order->Customer->Phone = $clientsdetails['phonecc'].$clientsdetails['phonenumber'];
	$order->Options = new stdClass();
	$order->Options->AntifraudEnabled = false;

	//die('<pre>'.print_r($order,1));


	logCiello('Order Recorrente',$order, $idRecorrente);

	$curl = curl_init();

	curl_setopt($curl, CURLOPT_URL, 'https://cieloecommerce.cielo.com.br/api/public/v1/orders');
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($order));
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
	'MerchantId: 67d6a642-5bba-49d7-a1cf-46c149087e27',
	'Content-Type: application/json'
	));

	$response = curl_exec($curl);

	curl_close($curl);

	$json = json_decode($response);

	logCiello('Response Json',$json, $idRecorrente);


	if(isset($json->settings->checkoutUrl))
	{

		$results = localAPI($command,$values,$adminuser);


		$urlCielo= $json->settings->checkoutUrl;
		return '
		<div class="row" style="text-align:center">
        <h2>Pagamento Recorrente com cartão de crédito</h2>
		<h1 style="font-size:95px; color:#BF0003"><i class="fa fa-spinner fa-spin fa fa-fw"></i></h1>
		<h2>Redirecionando para o ambiente Seguro</h2>
		<meta http-equiv="refresh" content=3;url="'.$urlCielo.'"><br />
		<img src="cielo.png" class="img-responsive" style="margin:5px auto;" />
		</div>';
	}



	return  '
	<div class="row" style="text-align:center">
	<h2>Desculpe</h2>
	<h1 style="font-size:95px; color:#BF0003"><i class="fa fa-thumbs-down" aria-hidden="true"></i></h1>
	<h2>Houve um erro ao gerar seu pagamento.</h2><br /><pre style="color:#FFF">'.print_r($order,1).print_r($json,1).'</pre>
	</div>';
}




#################################### BEGIN

$command = 'GetClientsDetails';
$postData = array(
'clientid' => $_GET['userid'],
);

$adminUsername = 'deljdl';
$clientsdetails = localAPI($command, $postData, $adminUsername);



$autoauthkey = 'GrUpO@THXcurrentKeY_-tRErt543ER';
$secret_key =  'RE345trERt-_YeKtnerrucXHT@OpUrG';
$timestamp = time();
$hashnew = md5($clientsdetails['email'].$secret_key);



if(isset($_GET['service_id'][0]))
{

 	$idRecorrente = $_GET['service_id'];


	if(strstr($_GET['service_id'], 's'))
	{

		$service_id = str_replace('s','',$_GET['service_id']);

	    $command = 'GetClientsProducts';
	    $postData = array(
	    'clientid' => $_GET['userid'],
	    'serviceid' => $service_id,
	    );
	    $adminUsername = 'deljdl';
	    $produto = localAPI($command, $postData, $adminUsername);
	    $produto = $produto['products']['product'][0];
	    /*die(print_r($produto));

	    Array ( [id] => 476 [clientid] => 255 [orderid] => 419 [pid] => 3 [regdate] => 2017-02-07 [name] => [translated_name] => TIH - Master [groupname] => Hospedagem de Sites [translated_groupname] => Hospedagem de Sites [domain] => yespromo.com.br [dedicatedip] => [serverid] => 8 [servername] => TiHospedagem.com [serverip] => 198.1.103.252 [serverhostname] => tihospedagem.com [firstpaymentamount] => 89.91 [recurringamount] => 89.91 [paymentmethod] => deposito_ou_transferencia [paymentmethodname] => Depósito ou Transferência [billingcycle] => Quarterly [nextduedate] => 2017-02-07 [status] => Pending [username] => [password] => [subscriptionid] => [promoid] => 0 [overideautosuspend] => 0 [overidesuspenduntil] => 0000-00-00 [ns1] => [ns2] => [assignedips] => [notes] => [diskusage] => 0 [disklimit] => 0 [bwusage] => 0 [bwlimit] => 0 [lastupdate] => 0000-00-00 00:00:00 [customfields] => Array ( [customfield] => Array ( ) ) [configoptions] => Array ( [configoption] => Array ( ) ) ) 1

	    */
	    //$name, $description, $valor, $recorrencia
	   $name = $produto['groupname'];
	   $description = $produto['translated_name'];
	   $valor = $produto['recurringamount'];
	   $recorrencia = $produto['billingcycle'];

	    logCiello('Dados Serviço',$produto, $idRecorrente);
	}
	elseif(strstr($_GET['service_id'], 'd'))
	{
		$service_id = str_replace('d','',$_GET['service_id']);

	    $command = 'GetClientsDomains';
	    $postData = array(
	    'clientid' => $_GET['userid'],
	    'domainid' => $service_id,
	    );
	    $adminUsername = 'deljdl';
	    $dominio = localAPI($command, $postData, $adminUsername);

	    $dominio = $dominio['domains']['domain'][0];


		/*

	    Array ( [id] => 476 [clientid] => 255 [orderid] => 419 [pid] => 3 [regdate] => 2017-02-07 [name] => [translated_name] => TIH - Master [groupname] => Hospedagem de Sites [translated_groupname] => Hospedagem de Sites [domain] => yespromo.com.br [dedicatedip] => [serverid] => 8 [servername] => TiHospedagem.com [serverip] => 198.1.103.252 [serverhostname] => tihospedagem.com [firstpaymentamount] => 89.91 [recurringamount] => 89.91 [paymentmethod] => deposito_ou_transferencia [paymentmethodname] => Depósito ou Transferência [billingcycle] => Quarterly [nextduedate] => 2017-02-07 [status] => Pending [username] => [password] => [subscriptionid] => [promoid] => 0 [overideautosuspend] => 0 [overidesuspenduntil] => 0000-00-00 [ns1] => [ns2] => [assignedips] => [notes] => [diskusage] => 0 [disklimit] => 0 [bwusage] => 0 [bwlimit] => 0 [lastupdate] => 0000-00-00 00:00:00 [customfields] => Array ( [customfield] => Array ( ) ) [configoptions] => Array ( [configoption] => Array ( ) ) ) 1

	    */

		$name = 'Manutenção de Domínio - '.$dominio['domainname'];
		$description = 'Manutenção de Domínio - '.$dominio['domainname'];
		$valor = $dominio['recurringamount'];
		$recorrencia = 'Annually';

		logCiello('Dados Dominio',$dominio, $idRecorrente);
	}



	logCiello('Dados Cliente',$clientsdetails, $idRecorrente);

    ####################################################################################################################



	$html = geraCobrancaRecorrenteCielo($clientsdetails, $idRecorrente, $name, $description, $valor, $recorrencia);



####################################################################################################################
}
else
{

	$command = 'GetClientsProducts';
    $postData = array(
    'clientid' => $_GET['userid'],
    'stats' => 'false',
    );
    $adminUsername = 'deljdl';
    $produtos = localAPI($command, $postData, $adminUsername);

    $html .= '
    <div class="row" style="text-align:center">
        <h3>Olá, '.$clientsdetails['firstname'].' ('.$clientsdetails['companyname'].')</h3>
        '.$itens.'
        <h2>Seus serviços que podem ter cobrança recorrente e automática estão listados abaixo.</h2>
        <p>
            O valor será debitado de seu cartão de crédito automáticamente.
            Nesse processo de agendamento automático de transações de crédito a transação se repetirá automaticamente de acordo periodicidade de cada serviço.
            <br /><br />
            Verifique abaixo os serviços que podem ser pagos usando essa modalidade.
        </p>
        <hr />
    </div>';

    $traduz = array(
    'Monthly' => 'Mensal',
    'Quarterly' => 'Trimestral',
    'Semi-Annually' => 'Semestral',
    'Annually' => 'Anual');

    $i = 0;
    foreach($produtos['products']['product'] as $prod)
    {

       if($prod['recurringamount'] > 0 && $prod['billingcycle'] != 'One Time' ) //&& (strtotime($prod['nextduedate']) >= strtotime(date('Y-m-d')))
       {
            //echo '<pre>'.print_r($prod,1);
            $html .= '
            <div onclick="paymentBegin(\'s'.$prod['id'].'\')" class="col-md-6 explica" style="margin-bottom: 30px;" id="s'.$prod['id'].'">
                <div  class="col-md-12 pagti" style="text-align:left">
                    <h2>'.$prod['groupname'].'</h2>
                    <table class="table">
                        <tr>
                            <th style="min-width: 135px;">
                                <i class="fa fa-wrench" aria-hidden="true"></i> Serviço
                            </th>
                            <td>
                                '.$prod['translated_name'].'
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <i class="fa fa-globe" aria-hidden="true"></i> Domínio
                            </th>
                            <td>
                                '.$prod['domain'].'
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <i class="fa fa-usd" aria-hidden="true"></i> Valor
                            </th>
                            <td>
                                R$ '.$prod['recurringamount'].'
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <i class="fa fa-refresh" aria-hidden="true"></i> Recorrência
                            </th>
                            <td>
                                '.$traduz[$prod['billingcycle']].'
                            </td>
                        </tr>
                    </table>
                    <a href="javascript:paymentBegin(\'s'.$prod['id'].'\')" class="btn btn-primary">Assinar Cobrança '.$traduz[$prod['billingcycle']].'</a>
                </div>
            </div>';
            $i++;
            if($i%2 == 0)
            {
                $html.='<div class="col-md-12"><hr /></div>';
            }
        }
    }


    $command = 'GetClientsDomains';
	$postData = array(
	    'clientid' => $_GET['userid'],
	    'stats' => 'false',
	);
	$adminUsername = 'deljdl';

	$dominios = localAPI($command, $postData, $adminUsername);



	//echo '<pre>'.print_r($dominios,1);
    foreach($dominios['domains']['domain'] as $dominio)
    {
        //echo '<pre>'.print_r($dominio,1);
         if($dominio['recurringamount'] > 0 ) // && (strtotime($dominio['nextduedate']) >= strtotime(date('Y-m-d')))
         {
            $html .= '
            <div onclick="paymentBegin(\'d'.$dominio['id'].'\')" class="col-md-6 explica" style="margin-bottom: 30px;" id="d'.$dominio['id'].'">
                <div  class="col-md-12 pagti" style="text-align:left">
                    <h2>Manutenção de Domínio</h2>
                    <table class="table">
                        <tr>
                            <th style="min-width: 135px;">
                                <i class="fa fa-hourglass-half" aria-hidden="true"></i> Expiração
                            </th>
                            <td>
                                '.strftime('%d de %B de %Y', strtotime($dominio['expirydate'])).'
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <i class="fa fa-globe" aria-hidden="true"></i> Domínio
                            </th>
                            <td>
                                '.$dominio['domainname'].'
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <i class="fa fa-usd" aria-hidden="true"></i> Valor
                            </th>
                            <td>
                                R$ '.$dominio['recurringamount'].'
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <i class="fa fa-refresh" aria-hidden="true"></i> Recorrência
                            </th>
                            <td>
                                Anual
                            </td>
                        </tr>
                    </table>
                    <a href="javascript:paymentBegin(\'d'.$dominio['id'].'\')" class="btn btn-primary">Assinar Cobrança Anual</a>
                </div>
            </div>';
            $i++;
            if($i%2 == 0)
            {
                $html.='<div class="col-md-12"><hr /></div>';
            }
        }

    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GRUPO THX - Pagamento Recorrente</title>
    <!-- Bootstrap -->
    <link href="<?=HTTP_WHMCS?>assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?=HTTP_WHMCS?>assets/css/font-awesome.min.css" rel="stylesheet">
    <!-- Styling -->
    <link href="<?=HTTP_WHMCS?>templates/six-THX/css/overrides.css" rel="stylesheet">
    <link href="<?=HTTP_WHMCS?>templates/six-THX/css/styles.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="<?=HTTP_WHMCS?>assets/js/jquery.min.js"></script>
    <!-- Custom Styling -->
    <link rel="stylesheet" href="<?=HTTP_WHMCS?>templates/six-THX/css/custom.css">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    <style>
        @media print {.noPrint {
            display:none;
          }}
          .pagti{
            background: #fff;
            border-radius: 2px;
            padding: 5px;
            width: 100%;
            box-shadow: 0 19px 38px rgba(0,0,0,0.30), 0 15px 12px rgba(0,0,0,0.22);
            transition: all 0.2s ease-in-out;
            cursor: pointer;
            text-align: center;
            position: relative;


          }
          .pagti a{
            width: 100%;
            transition: all 0.2s ease-in-out;
          }
          .pagti img{
            margin: auto;
          }
          .pagti:hover{
              box-shadow: 0 10px 20px rgba(0,0,0,0.19), 0 6px 6px rgba(0,0,0,0.23);
              transform: scale(1.1);

          }
          .pagti:hover > a{
            background-color: #BF0003;
            border-color: #BF0003;
            font-size: 15px;
            font-weight: bold;
          }

          .pagti h2{
            text-align: center;
            color: #bf0003;
            line-height: 50px;
          }
    </style>
    <script>
    function paymentBegin(id)
    {
        destFinal = HTTP_WHMCS.'autologin.php?thxgo=cielorecorrente&email=<?=$clientsdetails['email']?>&userid=<?=$clientsdetails['userid']?>&hash=<?=$hashnew?>&service_id='+id;
        $('#'+id).append('<div style="width: 100%;height: 100%;left: 0;top: 0;background-color: rgba(255, 255, 255, 0.9);position: absolute;text-align: center;"><h1 style="font-size:95px; color:#BF0003"><i class="fa fa-spinner fa-spin fa fa-fw"></i></h1><h1 style="font-size:25px; color:#BF0003;">Enviando...</h1></div>');

            location = destFinal;
    }

    </script>
</head>
<body>
    <section id="header">
        <div class="container">
            <a href="/index.php"><img src="<?=HTTP_WHMCS?>templates/six-THX/img/logo.png" alt="GRUPO THX" /></a>
        </div>
    </section>
    <section id="main-menu">
        <nav id="nav" class="navbar navbar-default navbar-main" role="navigation">
            <div class="container">
                <!-- Brand and toggle get grouped for better mobile display -->
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                        <span class="sr-only">Menu</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                </div>
                <!-- Collect the nav links, forms, and other content for toggling -->
                <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                    <ul class="nav navbar-nav navbar-right">
                        <li><a href="javascript:void(0)"><i class="fa fa-shopping-cart" aria-hidden="true"></i> Assinaturas</a></li>
                        <li><a href="javascript:openLigue('chat')"><i class="fa fa-question-circle" aria-hidden="true"></i> Ajuda</a></li>
                    </ul>
                </div><!-- /.navbar-collapse -->
            </div>
        </nav>
    </section>
    <section id="main-body" class="container">
        <div class="row">
            <div class="col-xs-12 main-content" style="margin-bottom: 0">
                <div class="row">
                <?=$html?>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-a2" style="margin-bottom: 30px; text-align:center" >
                        <h4 id="explica"></h4>
                    </div>
                </div>
        </div>
        <div class="clearfix"></div>
    </section>
    <section id="footer">
        <p>Copyright &copy; 2019 Grupo THX</p>
    </section>
    <script src="<?=HTTP_WHMCS?>assets/js/bootstrap.min.js"></script>
    <script src="<?=HTTP_WHMCS?>assets/js/jquery-ui.min.js"></script>
    <script src="//grupothx.com.br/atendimento-online/apiTHX/scriptAtendimentoOnlineTodosOsSitesJs.php"></script>
</body>
</html>
<? logoffSecurity(); ?>