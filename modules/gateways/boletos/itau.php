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
{	$itens.='<br />'.$pedidoIt['description'].' R$ '.$pedidoIt['amount']; //print_r($pedidoIt);
}

$data = mysql_fetch_array($result);
/*
Array ( [0] => 2107 [id] => 2107 [1] => 210 [userid] => 210 [2] => [invoicenum] => [3] => 2016-06-21 [date] => 2016-06-21 [4] => 2016-06-21 [duedate] => 2016-06-21 [5] => 0000-00-00 00:00:00 [datepaid] => 0000-00-00 00:00:00 [6] => 1.00 [subtotal] => 1.00 [7] => 0.00 [credit] => 0.00 [8] => 0.00 [tax] => 0.00 [9] => 0.00 [tax2] => 0.00 [10] => 1.00 [total] => 1.00 [11] => 0.00 [taxrate] => 0.00 [12] => 0.00 [taxrate2] => 0.00 [13] => Unpaid [status] => Unpaid [14] => boleto_itau_sem_registro [paymentmethod] => boleto_itau_sem_registro [15] => [notes] => )
*/

$status = $data['status'];
if ( $data["id"] && $data["userid"] && ( isset($_SESSION['adminid']) || $_SESSION["uid"]==$data["userid"] ) ) {} else {
	die(header('Location: '.HTTP_WHMCS.'clientarea.php'));
}

$clientsdetails = getClientsDetails($userid);


$dados_cliente='';
$dados_cliente.='<br /><br /><strong> Dados do Cliente:</strong>';
$dados_cliente.='<br /><strong>Nome: </strong>'.$clientsdetails['firstname'].' '.$clientsdetails['lastname'];
$dados_cliente.='<br /><strong>E-Mail: </strong>'.$clientsdetails['email'];
$dados_cliente.='<br /><strong>Telefone: </strong>('.$clientsdetails['phonecc'].') '.$clientsdetails['phonenumber'];

//____________________________________________________________parametros

$dias_de_prazo_para_pagamento = 0;

$taxa_boleto = 0;
$taxa_boleto_descrito = '';
$data_venc = date('d/m/Y',strtotime($data['duedate']));  // Prazo de X dias OU informe data: "13/04/2006"; 
$valor_cobrado = $data["total"]; // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal

$diferenca = strtotime(date('Y-m-d')) - strtotime($data['duedate']);    
$dias_atrasados = (int)floor( $diferenca / (60 * 60 * 24));

if($dias_atrasados > 0)
{
	if($dias_atrasados > LIMITE_ATRAZO)
	{
	    $multa = ($data["total"] * MULTA_ATRAZO_THX) - $data["total"];
		$taxa_boleto += $multa;
		$taxa_boleto_descrito .= "<br />Multa por atraso - R$ ".moeda($multa);
	    $juros  = (($data["total"] + $multa) * JUROS_POR_DIA) * $dias_atrasados;
	    $taxa_boleto += $juros;
	    $taxa_boleto_descrito .= '<br />Juros por '.$dias_atrasados.' dias de atraso - R$ '.moeda($juros);
	}
	$data_venc = date('d/m/Y');
}



if($valor_cobrado <= MINIMO_ISENSAO_TAXA)
{
	$taxa_boleto += TAXA_BOLETO_THX;
	$taxa_boleto_descrito .= "<br />Taxa de serviço - R$ ".moeda(TAXA_BOLETO_THX);
}

$valor_boleto=moeda($valor_cobrado+$taxa_boleto);


	//$data["userid"]
$dadosboleto["nosso_numero"] = $invoiceid;  // Nosso numero - REGRA: M&aacute;ximo de 8 caracteres!
$dadosboleto["numero_documento"] = $data['userid'].'/'.$invoiceid;	// Num do pedido ou nosso numero
$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emisso do Boleto
$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
$dadosboleto["valor_boleto"] = $valor_boleto; 	// Valor do Boleto - REGRA: Com v&iacute;rgula e sempre com duas casas depois da virgula

// DADOS DO SEU CLIENTE
$dadosboleto["sacado"] = $clientsdetails['firstname'].' '.$clientsdetails['lastname'].' ('.$clientsdetails['companyname'].') CPF/CNPJ:  '.$clientsdetails['customfields3'];
$dadosboleto["endereco1"] = $clientsdetails['address1'];
$dadosboleto["endereco2"] = $clientsdetails['city'].' - '.$clientsdetails['fullstate'].' -  '.$clientsdetails['postcode'];

// INFORMACOES PARA O CLIENTE
$dadosboleto["demonstrativo1"] = $itens;
$dadosboleto["demonstrativo2"] = $taxa_boleto_descrito;
$dadosboleto["demonstrativo3"] = '';
$dadosboleto["instrucoes1"] = '';
$dadosboleto["instrucoes2"] = "";
$dadosboleto["instrucoes3"] = "- Em caso de d&uacute;vidas entre em contato conosco: (11) 4655-0330<br />- Não receber após o vencimento.";
$dadosboleto["instrucoes4"] = "";

// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
$dadosboleto["quantidade"] = "";
$dadosboleto["valor_unitario"] = "";
$dadosboleto["aceite"] = "";		
$dadosboleto["especie"] = "R$";
$dadosboleto["especie_doc"] = "";


// ---------------------- DADOS FIXOS DE CONFIGURA&ccedil;O DO SEU BOLETO --------------- //


// DADOS DA SUA CONTA - ITA&uacute;
$dadosboleto["agencia"] = "4299"; // Num da agencia, sem digito
$dadosboleto["conta"] = "28973";	// Num da conta, sem digito
$dadosboleto["conta_dv"] = "8"; 	// Digito do Num da conta

// DADOS PERSONALIZADOS - ITA&uacute;
$dadosboleto["carteira"] = "109";  // C&oacute;digo da Carteira: pode ser 175, 174, 104, 109, 178, ou 157

// SEUS DADOS
$dadosboleto["identificacao"] = "Boleto - Grupo THX - ".$clientsdetails['companyname'].' - Vencimento: '.$data_venc;
$dadosboleto["cpf_cnpj"] = "19.496.171/0001-81";
$dadosboleto["endereco"] = "Rua Milton Ferreira Franco, 80 - Aruj&aacute;";
$dadosboleto["cidade_uf"] = "SP";
$dadosboleto["cedente"] = "Grupo THX - grupothx.com.br";




//BOLETO BLOQUEADOOOOOOOOOOOOOOOOOOOOOOO
$autoauthkey = 'GrUpO@THXcurrentKeY_-tRErt543ER';  
$secret_key =  'RE345trERt-_YeKtnerrucXHT@OpUrG';           
$timestamp = time();
$hashnew = md5($clientsdetails['email'].$secret_key);
$blocbolurl = HTTP_WHMCS.'autologin.php?thxgo=all&email='.$clientsdetails['email'].'&invoice='.$invoiceid.'&hash='.$hashnew;
header('Location : '.$blocbolurl);
die("<script>location='{$blocbolurl}';</script>");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Suporte - Pagamento com Boleto Bancário</title>
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
    <section id="header" class="noPrint">
        <div class="container">
            <a href="/index.php"><img src="/templates/six-THX/img/logo.png" alt="GRUPO THX" /></a>
        </div>
    </section>
    <section id="main-menu" class="noPrint">
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
                        <li><a href="javascript:openLigue('chat')"><i class="fa fa-question-circle" aria-hidden="true"></i> Ajuda</a></li>
                    </ul>
                </div><!-- /.navbar-collapse -->
            </div>
        </nav>
    </section>
    <section id="main-body" class="container">
        <div class="row">
            <div class="col-xs-12 main-content">
            	<div class="row noPrint" style="text-align:center">
            		<h2>Pagamento com Boleto Bancário</h2>
            		<hr />
            	</div>
            	<? if($status != 'Paid') { ?>
				<div class="row">
					<?
						include("include/funcoes_itau.php");
						include("include/layout_itau.php");
					?>
                </div>
                <div class="row noPrint">
                	<br />
                	<div class="col-md-6 col-md-offset-3">
						<button type="button" onclick="print()" class="btn btn-primary btn-lg btn-block" style="font-size: 28px;"><i class="fa fa-print" aria-hidden="true"></i> Imprimir Boleto</button>
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
    <section id="footer" class="noPrint">
        <p>Copyright &copy; 2019 Grupo THX</p>
    </section>
    <script src="/assets/js/bootstrap.min.js"></script>
    <script src="/assets/js/jquery-ui.min.js"></script>
    <?=file_get_contents('http://grupothx.com.br/scriptAtendimentoOnlineTodosOsSites.php')?>
</body>
</html><?
if(isset($_GET['nowlogin']))
{
	unset($_SESSION);
	session_destroy();
}