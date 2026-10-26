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
    return str_replace(array('.','.'),'',$valor);
}
function limitaCaracteres($str,$max,$sufixo='')
{
    $str = tiracento($str);
    if(strlen($str) > $max)
        $str=substr($str, 0, $max).$sufixo;
    return $str;
}
function logMercadoPago($titulo,$dados, $idlog)
{
    $quebra = chr(13).chr(10);
    $dados = $titulo.$quebra.'<pre>'.print_r($dados,1).'</pre>'.$quebra;

    $abre = fopen('log-mercadopago/'.$idlog, 'a');
    $escreve = fwrite($abre, $quebra.$dados);
    fclose($abre);
}

$result = select_query("tblinvoices","",array("id"=>(int)$invoiceid));

$data = mysql_fetch_array($result);
logMercadoPago('Dados Invoice',$data, $invoiceid);
$clientsdetails = getClientsDetails($userid);
logMercadoPago('Dados Cliente',$clientsdetails, $invoiceid);


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
	// SDK de Mercado Pago
	require __DIR__ .  '/vendor/autoload.php';
	// Configura credenciais
	MercadoPago\SDK::setAccessToken('APP_USR-393294712257526-100422-41f857a1f6c8fb661b2ebe66b2066df4-165408372');





	 //PRODUTOS ADICIONANDO
    $result2 = select_query("tblinvoiceitems","",array("invoiceid"=>(int)$invoiceid));
    $it = 0;
    $desconto = 0;
     $totalpedido = 0;

    // Cria um objeto de preferência
    $preference = new MercadoPago\Preference();



    $itens='
	<div class="panel panel-default">
	    <div class="panel-heading" data-toggle="collapse" data-target="#demo" style="background-color: #bf0003;color: #FFF;font-size: 25px; cursor:pointer">Detalhes da Fatura</div>
	        <div id="demo" class="">
	            <div class="panel-body" >
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
        if($pedidoIt['amount'] > 0)
        {
            $totalpedido += $pedidoIt['amount'];

            $itens.='
		    <tr>
		        <th>'.$pedidoIt['description'].'</th>
		        <td style="text-align:right">R$ '.$pedidoIt['amount'].'</td>
		    </tr>';

		    $totalPedidoValor+=$pedidoIt['amount'];


        }
        else
        {
            $desconto+=($pedidoIt['amount']*-1);
        }
    }


    $vdesc = setDescontoAvista($totalpedido);

    $jajuda = "javascript:sendMsgChat('Cliente: {$clientsdetails['firstname']}.\\nMensagem: Preciso de ajuda para o pagamento da fatura {$invoiceid}')";

    $totalpedido = checkJuros($totalpedido, $data['duedate']);



        $itens.='
            <tr style="background-color: #bf0003;color: #FFF;">
                <th colspan="2" style="text-align: center;font-size: 19px;">Vencimento: '.formataData($data['duedate']).'</th>
            </tr>';



    	if($totalpedido['dias_atrasados'] > 0)
    	{
    		$itens.='
		    <tr>
		        <th>Multa por atraso</th>
		        <td style="text-align:right">R$ '.moeda2($totalpedido['multa']).'</td>
		    </tr>
		    <tr>
		        <th>Juros por '.$totalpedido['dias_atrasados'].' dias de atraso</th>
		        <td style="text-align:right">R$ '.moeda2($totalpedido['juros']).'</td>
		    </tr>';
    	}





    $itens.='
	        </tbody>
	    </table>
	    <div class="panel-footer" style="text-align:right;background-color: #bf0003;color: #FFF;font-size: 25px;">Total R$ '.moeda2($totalpedido['valor']).'</div>
	</div>
	</div>
	</div>';


		$preference->back_urls = array(
	        "success" => HTTP_WHMCS.'modules/gateways/callback/mercadopago/retornoMercadoPagoX-Cript.php',
	        "failure" => HTTP_WHMCS.'modules/gateways/callback/mercadopago/retornoMercadoPagoX-Cript.php',
	        "pending" => HTTP_WHMCS.'modules/gateways/callback/mercadopago/retornoMercadoPagoX-Cript.php'
	    );


    	$preference->auto_return = "all";

###################################################################DADOS COMPRADOR


        $isCnpj = false;
        if(isset($clientsdetails['customfields3'][12]))
        {
            $isCnpj = true;
        }


        $payer = new MercadoPago\Payer();
        $payer->name = $isCnpj ? $clientsdetails['companyname'] : $clientsdetails['firstname'].' '.$clientsdetails['lastname'];
        $payer->email = $clientsdetails['email'];
        $payer->date_created = $clientsdetails['datecreated'];
        $telC = soNumeros($clientsdetails['phonenumber']);
        $payer->phone = array(
        "area_code" => substr($telC, 0, 2),
        "number" => substr($telC, 2)
        );
        $payer->identification = array(
        "type" => $isCnpj ? "CNPJ" : "CPF",
        "number" => soNumeros($clientsdetails['customfields3'])
        );
        $payer->address = array(
        "street_name" => $clientsdetails['address1'],
        "street_number" => soNumeros($clientsdetails['address1']),
        "zip_code" => soNumeros($clientsdetails['postcode'])
            );


###################################################################DADOS COMPRADOR

    	// Cria um item na preferência
	    $item = new MercadoPago\Item();
	    $item->title = 'Grupo THX - Pedido '.$invoiceid;
	    $item->description = 'Grupo THX - Pedido '.$invoiceid;
	    $item->quantity = 1;
	    $item->unit_price = moeda2($totalpedido['valor']);



		$preference->items = array($item);

        $preference->notificaction_url = HTTP_WHMCS.'modules/gateways/callback/mercadopago/webhookmercadopagox-cript.php';



    	$preference->external_reference=$invoiceid;





	    $preference->save();


    if($totalpedido > 0)
    {

        $results = localAPI($command,$values,$adminuser);

        $strretorno ='
        <div class="row" style="text-align:center">
        	<h1>Olá, '.$clientsdetails['firstname'].' :)</h1>
        	<br />
            <p>
                        Veja abaixo sua fatura detalhada e seu link de pagamento.
                        <br /> Caso tenha alguma dúvida não hesite em solicitar suporte.
                    </p>

            <!--<img src="mercadopago.png" class="img-responsive" style="margin:5px auto;" />-->.
            <form action="'.HTTP_WHMCS.'/modules/gateways/callback/mercadopago/retornoMercadoPagoX-Cript.php" method="POST">
			  <script
			   src="https://www.mercadopago.com.br/integrations/v1/web-payment-checkout.js"
			   data-preference-id="'.$preference->id.'" data-header-color="#1a3c6a" data-elements-color="#1a3c6a" data-elements-color="#1a3c6a" data-button-label="Pagar agora"  >
			  </script>
			</form>
            <br /><br />
            '.$itens.'
        </div>';

        if(true)
        {

            $strretorno ='
            <div class="row" style="text-align:center">
                <h1>Olá, '.$clientsdetails['firstname'].' :)</h1>
                <br />
                <p style="padding: 0 20px;">
                            Veja abaixo sua fatura detalhada e seus links de pagamento.
                            <br /> Caso tenha alguma dúvida não hesite em solicitar suporte <a href="'.$jajuda.'" style="color: red;font-weight: bold;">clicando aqui</a>.
                        </p>

            </div>



            <div class="col-md-5 pull-left">
                <form onmouseout="explica(\' \')" onmouseover="explica(\'Pague com Boleto<br />ou<br />Cartão em até 12x.\')" action="'.HTTP_WHMCS.'/modules/gateways/callback/mercadopago/retornoMercadoPagoX-Cript.php" method="POST">
                  <script
                   src="https://www.mercadopago.com.br/integrations/v1/web-payment-checkout.js"
                   data-preference-id="'.$preference->id.'" data-header-color="#1a3c6a" data-elements-color="#1a3c6a" data-elements-color="#1a3c6a" data-button-label="Pagar com boleto ou cartão          R$ '.moeda2($totalpedido['valor']).'"  >
                  </script>
                </form>
            </div>
            <div class="col-md-5 pull-right">
                <div onmouseout="explica(\' \')" onmouseover="explica(\'Pague com Transferência e Ganhe um Super Desconto<br />Pague apenas R$ '.$vdesc.'\')">
                        <button type="button" class="mercadopago-button"" data-toggle="modal" data-target="#myModal" style="background: #17c139 !important;">Pagar com desconto à vista R$ '.$vdesc.'</button>
                </div>
            </div>
            <br />
            <div class="clearfix"></div>
            <h4 id="explica"></h4>
            <div class="clearfix"></div>
            </div>
            <br />
            <div class="col-xs-12">
            '.$itens.'
            </div>

            <!-- Modal -->
            <div id="myModal" class="modal fade" role="dialog">
              <div class="modal-dialog">

                <!-- Modal content-->
                <div class="modal-content">
                  <div class="modal-body">
                  <img src="'.HTTP_WHMCS.'/imagens/nubank.jpg" class="mimg" /><br />
                    <table id="tablenubank">
                                        <tbody><tr>
                                            <td>Nome</td>
                                            <td>Jardel Jacomini</td>
                                        </tr>
                                        <tr>
                                            <td>CPF</td>
                                            <td>294.665.118-58</td>
                                        </tr>
                                        <tr>
                                            <td>Banco</td>
                                            <td>260 - Nu Pagamentos S.A</td>
                                        </tr>
                                        <tr>
                                            <td>Agência</td>
                                            <td>0001</td>
                                        </tr>
                                        <tr>
                                            <td>Conta</td>
                                            <td>9061981-6</td>
                                        </tr>
                                        <tr>
                                            <td>Valor com Desconto</td>
                                            <td>R$ '.$vdesc.'</td>
                                        </tr>
                                    </tbody>
                    </table>
                    <form method="POST" id="sendcomprovante" onSubmit="return enviador()" enctype="multipart/form-data" action="'.HTTP_WHMCS.'/modules/gateways/callback/mercadopago/sendComprovante.php">
                        <span class="mdesc">Para pagar sua fatura com o desconto no valor de <strong>R$ '.$vdesc.'</strong>, clique no botão abaixo. Enviaremos um email para solicitar seu comprovante de <strong>depósito</strong> ou <strong>transferência para o Banco informado acima.</strong> </span>
                            <button type="submit" id="enviador" class="btn btn-default btmod">Pagar com Desconto</button>
                        <input type="hidden" name="userid" value="'.$clientsdetails['userid'].'" >
                        <input type="hidden" name="invoiceid" value="'.$invoiceid.'" >
                        <textarea style="display: none" name="message">Olá **'.$clientsdetails['firstname'].'**, '."\n\n".'Seu pagamento para a cobrança '.$invoiceid.' foi informado com sucesso.'."\n\n".'Por favor responda esse email ou esse chamado com seu **comprovante** no valor promocional de **R$ '.$vdesc.'.'."**\n\n".'Informaremos a baixa por aqui mesmo :).'."\n\n".'Envie seu comprovante o quanto antes e garanta seu valor promocional de **R$ '.$vdesc.'**</textarea>

                        <input type="hidden" name="subject" value="Pagamento para fatura '.$invoiceid.' no valor promocional de R$ '.moeda2($totalpedido['valor']).' por R$ '.$vdesc.'" >


                    </form>
                  </div>

                </div>

              </div>
            </div>'.'<input type="hidden" id="botCall" value="{bom}'.$clientsdetails['firstname'].',{nl1}{nl1}O que acha de pagar apenas R$ '.$vdesc.' por essa fatura {nl1} ao invés de R$ '.moeda2($totalpedido['valor']).'?{end}" >';
        }
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
    <title>GRUPO THX - Pagamento</title>
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

          .main-content {
                margin-bottom: 0;
                min-height: auto;
            }
          .mercadopago-button{
          	    background: #1a3c6a !important;
			    font-size: 20px !important;
			    padding: 0px 26px !important;
			    float: none !important;
			    display: block !important;
			    width: 100% !important;
			    margin: 27px 0 !important;
                text-decoration: none;
                line-height: 40px !important;
          }
          .mercadopago-button:hover{
            text-decoration: none;
            color:  #FFF;
          }
          .modal-content {
            background-color: #8904be;
            color: #FFF;
            text-align: center;
            }

        #tablenubank{
            color:  #FFF;
            margin: 40px auto;
            margin: 40px auto;
            border: solid #FFF 1px;
            }

        #tablenubank td{
            padding: 12px 28px;
            font-size: 19px;
            font-weight: bold;
        }

         #tablenubanktr:nth-child(2n+2) {
                background: #ccc;
            }

        .btmod{
                background: #FFF;
                padding: 12px 37px;
                font-size: 25px;
                font-weight: bold;
                webkit-box-shadow: 0 5px 15px rgba(0,0,0,.5);
                box-shadow: 0 5px 15px rgba(0,0,0,.5);
        }
        .mimg   {
            max-height: 69px;
            margin: 7px auto 0 auto;
        }
        .mdesc{
            margin: 10px auto 18px auto;
            display: block;
            border: solid #FFF 1px;
            padding: 5px 51px;
        }
        .ldesc{
            cursor: pointer;
        }
        #explica{
            text-align: center;
            font-family: "Open Sans", Verdana, Tahoma, serif;
            min-height: 60px;
        }
        #comprovante{
                display: none;
        }
    </style>
</head>
<body>
    <section id="header">
        <div class="container">
            <a href="<?=HTTP_WHMCS?>"><img src="<?=HTTP_WHMCS?>templates/six-THX/img/logo.png" alt="GRUPO THX" /></a>
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


                        <li><a href="<?=$jajuda?>"><i class="fa fa-question-circle" aria-hidden="true"></i> Ajuda</a></li>
                    </ul>
                </div><!-- /.navbar-collapse -->
            </div>
        </nav>
    </section>
    <section id="main-body" class="container">
        <div class="row">
            <div class="col-xs-12 main-content">
                <?=$strretorno?>
            </div>



                </div>

        <div class="clearfix"></div>
    </section>
    <section id="footer">
        <p>Copyright &copy; 2019 <a href="http://grupothx.com.br" target="_blank">Grupo THX</a></p>
    </section>
    <script src="<?=HTTP_WHMCS?>assets/js/bootstrap.min.js"></script>
    <script src="<?=HTTP_WHMCS?>assets/js/jquery-ui.min.js"></script>
    <script src="//grupothx.com/atendimento-online/apiTHX/scriptAtendimentoOnlineTodosOsSitesJs.php"></script>


    <script>
    function paymentBegin(key, gtw)
    {
        destFinal = 'https://grupothx.com/thx/autologin.php?thxgo='+gtw+'&email=<?=$clientsdetails['email']?>&userid=<?=$clientsdetails['userid']?>&invoice=<?=$invoiceid?>&hash=<?=$hashnew?>';
        $('#gateway'+key).append('<div style="width: 100%;height: 100%;left: 0;top: 0;background-color: rgba(255, 255, 255, 0.9);position: absolute;text-align: center;"><h1 style="font-size:95px; color:#BF0003"><i class="fa fa-spinner fa-spin fa fa-fw"></i></h1><h1 style="font-size:25px; color:#BF0003;">Enviando...</h1></div>');
        $.get( "methodpaymentUpdate.php?i=<?=$invoiceid?>&gtw="+gtw, function( data ) {
            location = destFinal;
        });

    }
    function explica($str)
    {
        $('#explica').html($str);
    }

    function sendComprovante(comprovante)
    {
        if(confirm('Deseja enviar o comprovante de pagamento agora ?'))
        {
            document.getElementById('sendcomprovante').submit();
        }
        return false;
    }
    function enviador()
    {
        document.getElementById('enviador').innerHTML='Enviando ...';
        document.getElementById('enviador').setAttribute("disabled", "disabled");
        return true;
    }
    </script>







</body>
</html>
<? logoffSecurity(); ?>



