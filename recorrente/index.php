<?php 
require("../init.php");
$whmcs->load_function('gateway');
$whmcs->load_function('client');
setlocale( LC_ALL, "pt_BR" );
date_default_timezone_set('America/Sao_Paulo');
require_once($_SERVER['DOCUMENT_ROOT'].'/tarifasTHX.php');
if(!isset($_SESSION['uid']))
{
    header('Location: http://tihospedagem.com.br/clientarea.php?action=invoices');
}

function moeda($valor,$separador=','){
    return @number_format($valor, 2, $separador, '');

}

$result = select_query("tblinvoices","",array("id"=>$invoiceid, "userid" => $_SESSION["uid"]));


$data = mysql_fetch_array($result);


$result2 = select_query("tblinvoiceitems","",array("invoiceid"=>(int)$invoiceid));




$itens='
<div class="panel panel-default"> 
    <div class="panel-heading" data-toggle="collapse" data-target="#demo" style="background-color: #bf0003;color: #FFF;font-size: 25px; cursor:pointer"><i class="fa fa-hand-pointer-o" aria-hidden="true"></i> Clique para Ver Produtos/Serviços da Fatura</div>
        <div id="demo" class="collapse">
            <div class="panel-body" > 
                <p>
                    Abaixo estão os Produtos, Serviços e Taxas que compoem sua Fatura.
                    <br /> Caso tenha alguma dúvida não hesite em solicitar suporte.
                </p> 
            </div> 
            <table class="table">
                <thead> 
                    <tr>
                        <th>Descrição</th>
                        <th style="text-align:right">Valor</th>
                    </tr> 
                </thead> 
                <tbody> ';
$totalPedidoValor = 0;
while($pedidoIt = mysql_fetch_array($result2))
{   
    $itens.='
    <tr>
        <th>'.$pedidoIt['description'].'</th> 
        <td style="text-align:right">R$ '.$pedidoIt['amount'].'</td> 
    </tr>';

    $totalPedidoValor+=$pedidoIt['amount'];
}

$itens.=' 
        </tbody>
    </table>
    <div class="panel-footer" style="text-align:right;background-color: #bf0003;color: #FFF;font-size: 25px;">Total R$ '.moeda($totalPedidoValor,'.').'</div>
</div>
</div>
</div>';


/*
Array ( [0] => 2107 [id] => 2107 [1] => 210 [userid] => 210 [2] => [invoicenum] => [3] => 2016-06-21 [date] => 2016-06-21 [4] => 2016-06-21 [duedate] => 2016-06-21 [5] => 0000-00-00 00:00:00 [datepaid] => 0000-00-00 00:00:00 [6] => 1.00 [subtotal] => 1.00 [7] => 0.00 [credit] => 0.00 [8] => 0.00 [tax] => 0.00 [9] => 0.00 [tax2] => 0.00 [10] => 1.00 [total] => 1.00 [11] => 0.00 [taxrate] => 0.00 [12] => 0.00 [taxrate2] => 0.00 [13] => Unpaid [status] => Unpaid [14] => boleto_itau_sem_registro [paymentmethod] => boleto_itau_sem_registro [15] => [notes] => )
*/

$status = $data['status'];
if ( $data["id"] && $data["userid"] && ( isset($_SESSION['adminid']) || $_SESSION["uid"]==$data["userid"] ) ) {} else {
    die(header('Location: http://tihospedagem.com.br/clientarea.php'));
}

$clientsdetails = getClientsDetails($data['userid']);

$formaspag[] = array('titulo' => 'Cartões Cielo', 'img' => 'cielo.png', 'link' => 'cielo', 'explica' => 'Pague com seu cartão de crédito ou de débito. <br />Parcele seu pedido.');
$formaspag[] = array('titulo' => 'Cobrança Recorrente Cielo', 'img' => 'cielorecorrente.png', 'link' => 'cielorecorrente', 'explica' => 'Cobrança automática. <br /> As próximas cobranças ocorrerão automaticamente em seu cartão.');
$formaspag[] = array('titulo' => 'Depósito Bancário', 'img' => 'deposito.png', 'link' => 'deposito_ou_transferencia', 'explica' => 'Pague fazendo um depósito. <br />Não esqueça de enviar o comprovante.');
$formaspag[] = array('titulo' => 'Transferência Bancária', 'img' => 'transferencia.png', 'link' => 'deposito_ou_transferencia', 'explica' => 'Pague com Transferência Bancária. <br />Não esqueça de enviar o comprovante.');
$formaspag[] = array('titulo' => 'Itaú ShopLine', 'img' => 'itaushopline.jpg', 'link' => 'itaushopline', 'explica' => 'Pague via Boleto ou Transferência. <br />Não precisa ser cliente Itaú para Boleto');
$formaspag[] = array('titulo' => 'Boleto', 'img' => 'boleto.png', 'link' => 'itaushopline', 'explica' => 'Pagamento via Boleto.<br />Não precisa ser cliente Itaú');

$autoauthkey = 'GrUpO@THXcurrentKeY_-tRErt543ER';  
$secret_key =  'RE345trERt-_YeKtnerrucXHT@OpUrG';           
$timestamp = time();
$hashnew = md5($clientsdetails['email'].$secret_key);  

$html = '';

if($data['status'] == 'Paid')
{
    $html .= '
    <div class="row" style="text-align:center">
        <h3>Olá, '.$clientsdetails['firstname'].' ('.$clientsdetails['companyname'].')</h3>
        <h2>Obrigado</h2>
        <h1 style="font-size:95px; color:#BF0003"><i class="fa fa-thumbs-up" aria-hidden="true"></i></h1>
        <h2>A cobrança #'.$invoiceid.' já foi paga.</h2>
    </div>'.$itens;

}
else
{
    $html .= '
    <div class="row" style="text-align:center">
        <h3>Olá, '.$clientsdetails['firstname'].' ('.$clientsdetails['companyname'].')</h3>
        '.$itens.'
        <h2>Escolha uma forma de pagamento abaixo</h2>
        <hr />
    </div>';
    foreach($formaspag as $key => $forma) 
    {
        $html .= '
                <div onclick="paymentBegin('.$key.', \''.$forma['link'].'\')" class="col-md-2 explica" style="margin-bottom: 30px;" id="gateway'.$key.'" onmouseout="explica(\' \')" onmouseover="explica(\''.$forma['explica'].'\')">
                    <div  class="col-md-12 pagti">
                        <h4 style="min-height: 40px;">'.$forma['titulo'].'</h4>
                        <img class="img-responsive" src="'.$forma['img'].'" />
                        <a href="javascript:paymentBegin('.$key.', \''.$forma['link'].'\')" class="btn btn-primary">Pagar Agora</a>
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

          .pagti:hover > h4{
            color: #BF0003;
          }
    </style>
    <script>
    function paymentBegin(key, gtw)
    {
        if(gtw == 'cielorecorrente')
        {
            if(! confirm('Confirma o pagamento recorrente de R$ <?=moeda($totalPedidoValor,'.')?> em seu cartão. '))
            {
                return false;
            }
        }
        destFinal = 'http://tihospedagem.com.br/autologin.php?thxgo='+gtw+'&email=<?=$clientsdetails['email']?>&invoice=<?=$invoiceid?>&hash=<?=$hashnew?>';
        $('#gateway'+key).append('<div style="width: 100%;height: 100%;left: 0;top: 0;background-color: rgba(255, 255, 255, 0.9);position: absolute;text-align: center;"><h1 style="font-size:95px; color:#BF0003"><i class="fa fa-spinner fa-spin fa fa-fw"></i></h1><h1 style="font-size:25px; color:#BF0003;">Enviando...</h1></div>');
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
                        <li><a href="javascript:void(0)"><i class="fa fa-shopping-cart" aria-hidden="true"></i> Pagamento da Cobrança de número #<?=$invoiceid?></a></li>
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
<? logoffSecurity(); ?>

