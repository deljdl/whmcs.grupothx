<?php 
if($_SERVER['REMOTE_ADDR'] != '177.97.133.3')
{
	die('MODULO EM DESENVOLVIMENTO<br /> Por favor escolha outra forma de pagamento.<br />'.$_SERVER['REMOTE_ADDR']);
}
require($_SERVER['DOCUMENT_ROOT']."/init.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/tarifasTHX.php');
$whmcs->load_function('gateway');
$whmcs->load_function('client');
setlocale( LC_ALL, "pt_BR" );
date_default_timezone_set('America/Sao_Paulo');
$html = '';



if(!isset($_SESSION['uid']))
{
    header('Location: http://tihospedagem.com.br/clientarea.php?action=invoices');
}

if(!isset($_GET['']))
{

}

if(! isset($_GET['aceiteRecorrente']))
{


    $command = 'GetClientsDetails';
    $postData = array(
        'clientid' => $_GET['userid'],
    );
    $adminUsername = 'deljdl';

    $clientsdetails = localAPI($command, $postData, $adminUsername);
    //print_r($results);

    $autoauthkey = 'GrUpO@THXcurrentKeY_-tRErt543ER';  
	$secret_key =  'RE345trERt-_YeKtnerrucXHT@OpUrG';           
	$timestamp = time();
	$hashnew = md5($clientsdetails['email'].$secret_key);

    $command = 'GetClientsProducts';
    $postData = array(
        'clientid' => $_GET['userid'],
    );
    $adminUsername = 'deljdl';

    $results = localAPI($command, $postData, $adminUsername);
    //print_r($results);





    $command = 'GetClientsProducts';
    $postData = array(
        'clientid' => $_GET['userid'],
        'stats' => 'true',
    );
    $adminUsername = 'deljdl';
    $produtos = localAPI($command, $postData, $adminUsername);

    
    $html .= '
    <div class="row" style="text-align:center">
        <h3>Olá, '.$clientsdetails['firstname'].' ('.$clientsdetails['companyname'].')</h3>
        '.$itens.'
        <h2>Seus serviços passíveis de assinatura automática estão listados abaixo.</h2>
        <hr />
    </div>';
    
    $traduz = array(
    'Monthly' => 'Mensal',
    'Bimonthly' => 'Bimestral',
    'Quarterly' => 'Trimestral',
    'SemiAnnual' => 'Semestral',
    'Annual' => 'Anual');

    foreach($produtos['products']['product'] as $prod)
    {
    	echo '<pre>'.print_r($prod,1);
        $html .= '
        <div onclick="paymentBegin('.$prod['id'].')" class="col-md-5 explica" style="margin-bottom: 30px;" id="servico'.$prod['id'].'">
            <div  class="col-md-12 pagti" style="text-align:left">
            	<h1>'.$prod['groupname'].'</h1>
            	<table class="table">
            		<tr>
            			<th>
            				<i class="fa fa-wrench" aria-hidden="true"></i> Plano
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
            				<i class="fa fa-wrench" aria-hidden="true"></i> Recorrência
            			</th>
            			<td>
            				'.$traduz[$prod['billingcycle']].'
            			</td>
            		</tr>
     			</table>
                <a href="javascript:paymentBegin('.$key.', \''.$forma['link'].'\')" class="btn btn-primary">Assinar Cobrança '.$traduz[$prod['billingcycle']].'</a>
            </div>
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
    <title>GRUPO THX - Pagamento</title>
    <!-- Bootstrap -->
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/font-awesome.min.css" rel="stylesheet">
    <!-- Styling -->
    <link href="/templates/six-THX/css/overrides.css" rel="stylesheet">
    <link href="/templates/six-THX/css/styles.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="/assets/js/jquery.min.js"></script>
    <!-- Custom Styling -->
    <link rel="stylesheet" href="/templates/six-THX/css/custom.css">

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

          .pagti h1{
          	text-align: center;
		    color: #bf0003;
		    line-height: 50px;
          }
    </style>
    <script>
    function paymentBegin(id, key)
    {
        destFinal = 'http://tihospedagem.com.br/autologin.php?thxgo=cielorecorrente&email=<?=$clientsdetails['email']?>&userid=<?=$clientsdetails['userid']?>&hash=<?=$hashnew?>';
        $('#servico'+key).append('<div style="width: 100%;height: 100%;left: 0;top: 0;background-color: rgba(255, 255, 255, 0.9);position: absolute;text-align: center;"><h1 style="font-size:95px; color:#BF0003"><i class="fa fa-spinner fa-spin fa fa-fw"></i></h1><h1 style="font-size:25px; color:#BF0003;">Enviando...</h1></div>');
        $.get( "methodpaymentUpdate.php?i=<?=$invoiceid?>&gtw="+gtw, function( data ) {
            location = destFinal;
        });
        
        

    }
    function explica($str)
    {
        $('#explica').html($str);
    }
    </script>
</head>
<body>
    <section id="header">
        <div class="container">
            <a href="/index.php"><img src="/templates/six-THX/img/logo.png" alt="GRUPO THX" /></a>
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
        <p>Copyright &copy; 2016 TiHospedagem - Uma empresa do <a href="http://grupothx.com.br" target="_blank">Grupo THX</a></p>
    </section>
    <script src="/assets/js/bootstrap.min.js"></script>
    <script src="/assets/js/jquery-ui.min.js"></script>
    <?=file_get_contents('http://grupothx.com.br/scriptAtendimentoOnlineTodosOsSites.php')?>
</body>
</html>