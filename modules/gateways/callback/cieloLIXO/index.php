<?php
require($_SERVER['DOCUMENT_ROOT']."/thx/init.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/thx/tarifasTHX.php');
$whmcs->load_function('gateway');
$whmcs->load_function('client');
setlocale( LC_ALL, "pt_BR" );
    date_default_timezone_set('America/Sao_Paulo');
$erro = '';

if(!isset($_SESSION['uid']))
{
    header('Location: '.HTTP_WHMCS.'clientarea.php?action=invoices');
}







function moeda($valor,$separador=','){
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

$result = select_query("tblinvoices","",array("id"=>(int)$invoiceid));

$data = mysql_fetch_array($result);
logCiello('Dados Invoice',$data, $invoiceid);
$clientsdetails = getClientsDetails($userid);
logCiello('Dados Cliente',$clientsdetails, $invoiceid);

$status = $data['status'];

 $strretorno = '';

if($status == 'Paid')
{
    $strretorno = '
    <div class="row" style="text-align:center">
        <h2>Obrigado</h2>
        <h1 style="font-size:95px; color:#BF0003"><i class="fa fa-thumbs-up" aria-hidden="true"></i></h1>
        <h2>Esta cobrança já foi paga.</h2>
    </div>';
}
else
{


    $order = new stdClass();
    $order->OrderNumber = $invoiceid;
    $order->SoftDescriptor = 'GrupoTHX';
    $order->Cart = new stdClass();
    $order->Cart->Discount = new stdClass();
    $order->Cart->Discount->Type = 'Percent';
    $order->Cart->Discount->Value = 0;
    $order->Cart->Items = array();


    //PRODUTOS ADICIONANDO
    $result2 = select_query("tblinvoiceitems","",array("invoiceid"=>(int)$invoiceid));
    $it = 0;
    $desconto = 0;
    while($pedidoIt = mysql_fetch_array($result2))
    {
        if($pedidoIt['amount'] > 0)
        {
            $order->Cart->Items[$it] = new stdClass();
            $order->Cart->Items[$it]->Name = limitaCaracteres($pedidoIt['description'], 125, '...');
            $order->Cart->Items[$it]->Description = limitaCaracteres($pedidoIt['description'], 250, '...');
            $order->Cart->Items[$it]->UnitPrice = moeda($pedidoIt['amount']);
            $order->Cart->Items[$it]->Quantity = 1;
            $order->Cart->Items[$it]->Type = 'Service';
            $it++;
        }
        else
        {
            $desconto+=(moeda($pedidoIt['amount'])*-1);
        }
    }

    $diferenca = strtotime(date('Y-m-d')) - strtotime($data['duedate']);
    $dias_atrasados = (int)floor( $diferenca / (60 * 60 * 24));


    if($dias_atrasados >= LIMITE_ATRAZO)
    {

        $multa = ($data["total"] * MULTA_ATRAZO_THX) - $data["total"];
        $multa = round($multa, 2);

        $order->Cart->Items[$it]->Name = 'Multa por atraso no pagamento';
        $order->Cart->Items[$it]->Description = 'Multa por atraso no pagamento';
        $order->Cart->Items[$it]->UnitPrice = moeda($multa*100);
        $order->Cart->Items[$it]->Quantity = 1;
        $order->Cart->Items[$it]->Type = 'Service';

        $it++;

        $juros  = (($data["total"] + $multa) * JUROS_POR_DIA) * $dias_atrasados;
        $juros = round($juros, 2);

        $order->Cart->Items[$it]->Name = 'Juros por '.$dias_atrasados.' dias de atraso';
        $order->Cart->Items[$it]->Description = 'Juros por '.$dias_atrasados.' dias de atraso';
        $order->Cart->Items[$it]->UnitPrice = moeda($juros*100);
        $order->Cart->Items[$it]->Quantity = 1;
        $order->Cart->Items[$it]->Type = 'Service';
    }


    $order->Shipping = new stdClass();
    $order->Shipping->Type = 'WithoutShipping';




    if($desconto > 0)
    {

        $order->Cart->Discount = new stdClass();
        $order->Cart->Discount->Type = 'Amount';
        $order->Cart->Discount->Value = moeda($desconto);
    }
    if(isset($_GET['recorrente']))
    {
    	$order->Payment->RecurrentPayment = new stdClass();
		$order->Payment->RecurrentPayment->Interval = 'Monthly';
		$order->Payment->RecurrentPayment->EndDate = '';


		$recinvoice = select_query("tblinvoices","",array("id"=>(int)$invoiceid));
		while($rec = mysql_fetch_array($recinvoice))
		{
			print_r($rec);
		}
		die('<pre>'.print_r(mysql_fetch_array($result),1));
	}



    $order->Customer = new stdClass();

    $order->Customer->FullName = $clientsdetails['firstname'].' '.$clientsdetails['lastname'];
    $order->Customer->Email = $clientsdetails['email'];
    if(isset($clientsdetails['customfields3'][10]))
    {
    	 $order->Customer->Identity = preg_replace("/[^0-9]/", "", $clientsdetails['customfields3']);
	}
    else
    {
        $erro = 'Seu CPF ou CNPJ não foram salvos corretamente';
    }

    //$order->Customer->Phone = $clientsdetails['phonecc'].$clientsdetails['phonenumber'];
    $order->Options = new stdClass();
    $order->Options->AntifraudEnabled = false;


    logCiello('Order',$order, $invoiceid);

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

    logCiello('Response Json',$json, $invoiceid);




    if(isset($json->settings->checkoutUrl))
    {

        $results = localAPI($command,$values,$adminuser);


        $urlCielo= $json->settings->checkoutUrl;
        $strretorno ='
        <div class="row" style="text-align:center">
            <h1 style="font-size:95px; color:#BF0003"><i class="fa fa-spinner fa-spin fa fa-fw"></i></h1>
            <h2>Redirecionando para o ambiente Seguro</h2>
            <meta http-equiv="refresh" content=3;url="'.$urlCielo.'"><br />
            <img src="cielo.png" class="img-responsive" style="margin:5px auto;" />
        </div>';
    }
    else
    {
        $strretorno = '
        <div class="row" style="text-align:center">
            <h2>Desculpe</h2>
            <h1 style="font-size:95px; color:#BF0003"><i class="fa fa-thumbs-down" aria-hidden="true"></i></h1>
            <h2>Houve um erro ao gerar seu pagamento.</h2><br />
            <h3>'.$erro.'</h3>
            <br /><pre style="display:none">'.print_r($order,1).print_r($json,1).'</pre>
        </div>';
    }
}



?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GRUPO THX - Pagamento com cartão de crédito ou débito</title>
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
    </style>
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
                        <li><a href="https://grupothx.com/thx/clientarea.php?action=invoices"><i class="fa fa-undo" aria-hidden="true"></i> Voltar</a></li>
                        <li><a href="javascript:print();"><i class="fa fa-print" aria-hidden="true"></i> Imprimir</a></li>
                        <li><a href="javascript:informadeposito()"><i class="fa fa-shopping-cart" aria-hidden="true"></i> Informar Pagamento</a></li>
                        <li><a href="javascript:openLigue('chat')"><i class="fa fa-question-circle" aria-hidden="true"></i> Ajuda</a></li>
                    </ul>
                </div><!-- /.navbar-collapse -->
            </div>
        </nav>
    </section>
    <section id="main-body" class="container">
        <div class="row">
            <div class="col-xs-12 main-content">
                <div class="row" style="text-align:center">
                    <h2>Pagamento com cartão de crédito ou débito</h2>
                    <hr />
                </div>
                <?=$strretorno?>
            </div>
                </div>
        <div class="clearfix"></div>
    </section>
    <section id="footer">
        <p>Copyright &copy; 2016 TiHospedagem - Uma empresa do <a href="http://grupothx.com.br" target="_blank">Grupo THX</a></p>
    </section>
    <script src="<?=HTTP_WHMCS?>assets/js/bootstrap.min.js"></script>
    <script src="<?=HTTP_WHMCS?>assets/js/jquery-ui.min.js"></script>
    <script src="//grupothx.com.br/atendimento-online/apiTHX/scriptAtendimentoOnlineTodosOsSitesJs.php"></script>
</body>
</html>
<? logoffSecurity(); ?>



