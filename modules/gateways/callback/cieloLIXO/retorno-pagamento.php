<?php 
require("../../../../init.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/tarifasTHX.php');
$whmcs->load_function('gateway');
$whmcs->load_function('client');
setlocale( LC_ALL, "pt_BR" );
    date_default_timezone_set('America/Sao_Paulo');

    $strretorno = '
    <div class="row" style="text-align:center">
        <h2>Obrigado</h2>
        <h1 style="font-size:95px; color:#BF0003"><i class="fa fa-thumbs-up" aria-hidden="true"></i></h1>
        <h2>O processo de pagamento foi finalizado. <br /><br /> Por favor verifique seu email.</h2>
    </div>';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GRUPO THX - Pagamento com cartão de crédito ou débito</title>
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
    </style>
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
                        <li><a href="http://tihospedagem.com.br/clientarea.php?action=invoices"><i class="fa fa-undo" aria-hidden="true"></i> Voltar</a></li>
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
    <script src="/assets/js/bootstrap.min.js"></script>
    <script src="/assets/js/jquery-ui.min.js"></script>
    <?=file_get_contents('http://grupothx.com.br/scriptAtendimentoOnlineTodosOsSites.php')?>
</body>
</html>



