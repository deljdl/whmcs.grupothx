<?php
require("../../../init.php");
$whmcs->load_function('gateway');
$whmcs->load_function('client');
setlocale( LC_ALL, "pt_BR" );
date_default_timezone_set('America/Sao_Paulo');
require_once($_SERVER['DOCUMENT_ROOT'].'/thx/tarifasTHX.php');

if(!isset($_SESSION['uid']))
{
	header('Location: '.HTTP_WHMCS.'clientarea.php?action=invoices');
}
function moeda($valor,$separador=','){
	return @number_format($valor, 2, $separador, '');

}
$dados_pedido='<strong>Itens do pedido: </strong>';

$result = select_query("tblinvoices","",array("id"=>(int)$invoiceid));

$result2 = select_query("tblinvoiceitems","",array("invoiceid"=>(int)$invoiceid));




$itens='';
while($pedidoIt = mysql_fetch_array($result2))
{
    $itens.='<i class="fa fa-check" aria-hidden="true" style="color: #16c33d;"></i>'.$pedidoIt['description'].' R$ '.$pedidoIt['amount'].'<br /><br />';
}



$data = mysql_fetch_array($result);

$status = $data['status'];

$valor = $data["total"];


	$diferenca = strtotime(date('Y-m-d')) - strtotime($data['duedate']);
	$dias_atrasados = (int)floor( $diferenca / (60 * 60 * 24));




if($dias_atrasados >= LIMITE_ATRAZO)
{

  	$multa = ($data["total"] * MULTA_ATRAZO_THX) - $data["total"];

	$valor += $multa;
	$itens.='<i class="fa fa-check" aria-hidden="true" style="color: #16c33d;"></i>Multa por atraso + R$ '.moeda($multa).'<br /><br />';

	$juros  = (($data["total"] + $multa) * JUROS_POR_DIA) * $dias_atrasados;
	$valor += $juros;
	$itens.='<i class="fa fa-check" aria-hidden="true" style="color: #16c33d;"></i>Juros por '.$dias_atrasados.' dias de atraso + R$ '.moeda($juros).'<br /><br />';
}

if ( $data["id"] && $data["userid"] && ( isset($_SESSION['adminid']) || $_SESSION["uid"]==$data["userid"] ) ) {} else {
	die(header('Location: '.HTTP_WHMCS.'clientarea.php?action=invoices'));
}

$clientsdetails = getClientsDetails($userid);

$data_venc = $data['duedate'];


$valor = moeda($valor);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GRUPO THX - Pagamento com Depósito ou Transferência Bancária</title>
    <!-- Bootstrap -->
    <link href="<?=HTTP_WHMCS?>assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?=HTTP_WHMCS?>assets/css/font-awesome.min.css" rel="stylesheet">
    <!-- Styling -->
    <link href="<?=HTTP_WHMCS?>/templates/six-THX/css/overrides.css" rel="stylesheet">
    <link href="<?=HTTP_WHMCS?>/templates/six-THX/css/styles.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="<?=HTTP_WHMCS?>assets/js/jquery.min.js"></script>
    <!-- Custom Styling -->
    <link rel="stylesheet" href="<?=HTTP_WHMCS?>/templates/six-THX/css/custom.css">

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
            <a href="/index.php"><img src="<?=HTTP_WHMCS?>/templates/six-THX/img/logo.png" alt="GRUPO THX" /></a>
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
                        <li><a href="<?=HTTP_WHMCS?>clientarea.php?action=invoices"><i class="fa fa-undo" aria-hidden="true"></i> Voltar</a></li>
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
            		<h2>Pagamento com Depósito ou Transferência Bancária</h2>
            		<hr />
            	</div>
                <? if($status != 'Paid') {


                    ?>

                     <button type="button" id="iniciaDeposito" onclick="iniciaDeposito()" class="btn btn-success btn-lg btn-block noPrint" style="font-size: 28px;">Carregando dados Bancários ...<br /><i class="fa fa-spinner fa-spin fa fa-fw"></i></button>
                    <div id="bancos" style="display:none">
                        <div class="row" >
        					<div class="col-md-4" style="text-align:center;">
        						<h4>Itaú</h4>
            					<img src="http://tihospedagem.com.br/imagens/itau.png">
            					<p style="font-weight: bolder;">
        							Agência: 4299<br />
        							Conta: 28973-8<br />
        							Beneficiário: Grupo THX<br />
        							CNPJ: 19.496.171/0001-81
            					</p>
        					</div>
        					<div class="col-md-4" style="text-align:center;">
        						<h4>Bradesco</h4>
            					<img src="http://tihospedagem.com.br/imagens/bradesco.png">
            					<p style="font-weight: bolder;">
        							Agência: 2501-1<br />
        							Conta: 49486-0<br />
        							Beneficiário: Jardel Jacomini<br />
        							CPF: 294.665.118-58<br />
            					</p>
        					</div>
                            <div class="col-md-4" style="text-align:center;">
                                <h4>Nu Bank</h4>
                                <img src="http://tihospedagem.com.br/imagens/nubank.jpg">
                                <p style="font-weight: bolder;">
                                    Agência: 0001<br />
                                    Conta: 9061981-6<br />
                                    Banco: Nu Pagamentos S.A. (260)<br />
                                    CPF: 294.665.118-58<br />
                                </p>
                            </div>
        				</div>
        				<!--<div class="row">
        					<div id="accordion" role="tablist" aria-multiselectable="true">
        					  <div class="panel panel-default">
        					    <div class="panel-heading" role="tab" id="headingThree">
        					      <h4 class="panel-title">
        					        <a class="collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
        					          Ver Produtos/Serviços da Fatura
        					        </a>
        					      </h4>
        					    </div>
        					    <div id="collapseThree" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingThree">
        					      <?=$itens?>
        					    </div>
        					  </div>
        					</div>
        				</div>
                    </div> -->
    				<div class="row">
    					<div class="col-md-12" style="text-align:center">
    						<h2>Valor: <strong style="color:#F00">R$ <?=$valor?></strong></h2>
    						<h5 class="noPrint">Caso já tenha feito o pagamento por favor clique no botão abaixo:</h5>
    						<div id="btpagar"></div>
    					</div>
                    </div>
                 <? } else { ?>
                    <div class="row" style="text-align:center">
                        <h2>Obrigado</h2>
                        <h1 style="font-size:95px; color:#BF0003"><i class="fa fa-thumbs-up" aria-hidden="true"></i></h1>
                        <h2>Esta cobrança já foi paga.</h2>
                    </div>
                <? } ?>
            </div>
                </div>
        <div class="clearfix"></div>
    </section>
    <section id="footer">
        <p>Copyright &copy; 2016 TiHospedagem - Uma empresa do <a href="http://grupothx.com.br" target="_blank">Grupo THX</a></p>
    </section>
    <script src="<?=HTTP_WHMCS?>assets/js/bootstrap.min.js"></script>
    <script src="<?=HTTP_WHMCS?>assets/js/jquery-ui.min.js"></script>
    <script>
        function iniciaDeposito()
        {
            $.getJSON( "inicio-deposito.php?id=<?=$invoiceid?>", function(data) {
                $('#iniciaDeposito').fadeOut('slow', function()
                {
                    $('#bancos').fadeIn('slow');
                });
            });
        }
        function informadeposito()
        {
           var botao = $('#btpagar');
           botao.html('<h3>Confirma que o pagamento da cobrança já foi feito ?</h3> <br /><button type="button" onclick="confirmadeposito()" class="btn btn-success noPrint" style="font-size: 28px;"><i class="fa fa-thumbs-up" aria-hidden="true"></i> Sim </button>  <button type="button" onclick="defaltdeposito()" class="btn btn-danger noPrint" style="font-size: 28px;"><i class="fa fa-thumbs-down" aria-hidden="true"></i> Não </button>');
            $('html,body').animate({scrollTop: $('#btpagar').offset().top},'slow');
        }
		function confirmadeposito()
		{
			var botao = $('#btpagar');
			{
				botao.html('Enviando ... <br /><i class="fa fa-spinner fa-spin fa-fw" aria-hidden="true"></i>');
				$.getJSON( "aviso-deposito.php?id=<?=$invoiceid?>", function(data) {
					if(data == 'ok')
					{

				 	 	botao.html('<button type="button" class="btn btn-success noPrint" style="font-size: 28px;"><i class="fa fa-thumbs-up" aria-hidden="true"></i> Seu Pagamento<br /> foi Informado </button>');
                        location = '<?=URL_PAGO?>';
					}
					else
					{

						botao.html('<button type="button" onClick="defaltdeposito()"  class="btn btn-danger noPrint" style="font-size: 28px;"><i class="fa fa-thumbs-down" aria-hidden="true"></i> Erro ao informar<br />seu pagamento ('+data+') </button>');
					}
				});
			}
		}
        function defaltdeposito()
        {
             var botao = $('#btpagar');
            botao.html('<button type="button" onclick="informadeposito()" class="btn btn-primary btn-lg btn-block noPrint" style="font-size: 28px;">Ja pagou ? <br> <i class="fa fa-hand-pointer-o" aria-hidden="true"></i> Clique Aqui.</button>');
        }
        defaltdeposito();
        iniciaDeposito();
	</script>
    <script src="//grupothx.com.br/atendimento-online/apiTHX/scriptAtendimentoOnlineTodosOsSitesJs.php"></script>
    <? logoffSecurity(); ?>
</body>
</html>