<?php
header('Location: /thx/viewinvoice.php');





require_once($_SERVER['DOCUMENT_ROOT'].'/thx/viewinvoice.php');
die();



require_once $_SERVER['DOCUMENT_ROOT'].'/init.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/tarifasTHX.php';
use WHMCS\Database\Capsule;

if(!isset($_GET['i']) || empty($_GET['i']))
{
    die("fatura não localizada. ErrorCode: 001");
}


$idFaturaParaEnvio = decriptX($_GET['i']);

if(!$idFaturaParaEnvio)
{
    $idFaturaParaEnvio = base64_decode($_GET['i']);
}

if(!is_numeric($idFaturaParaEnvio))
{
    die("fatura não localizada. ErrorCode: 003");
}

$idFaturaParaEnvio = intval($idFaturaParaEnvio);

if($idFaturaParaEnvio <= 0)
{
    die("fatura não localizada. ErrorCode: 004");
}

$dadosCliente = Capsule::select("SELECT * FROM tblinvoices
                                                INNER JOIN tblclients ON tblinvoices.userid = tblclients.id
                                                        WHERE tblinvoices.id =  {$idFaturaParaEnvio}");

if(!$dadosCliente)
{
    die("fatura não localizada. ErrorCode: 002");
}


function viewPaymentData($idFaturaParaEnvio, $invoice)
{
    $grupothx_merge_fields = [];
    // $invoice			= localAPI( 'GetInvoice', array('invoiceid' => $idFaturaParaEnvio), false);
    //echo 'Cliente<br /><pre>'.print_r($invoice,1).'</pre>';
    if($invoice['status'] == 'error')
    {
        return '<h1 class="paid">Fatura não localizada <strong>:(</strong></h1>';
    }

    if($invoice['status'] == 'Paid')
    {
        return '<h1 class="paid">Fatura Paga <strong>:)</strong></h1>';
    }

    if($invoice['status'] == 'Cancelled')
    {
        return '<h1 class="paid">Fatura Cancelada <strong>:)</strong></h1>';
    }


    $grupothx_merge_fields['grupothx_pix_copia_e_cola']		= file_get_contents('https://pix.grupothx.com/?valor='.$invoice['total'].'&id=A'.$idFaturaParaEnvio.'&cc=true');



         $boleto = '';

         $pix = '
         <tr style="height:131px">
            <td style="padding: 20px;text-align:center;vertical-align:middle">
               <img class="mobilehidden" src="https://grupothx.com/imagens/logopix.png">
            </td>
            <td style="padding: 20px;text-align: center; vertical-align: middle; width: 50%;">
               <img class="qrcode-íx" src="https://pix.grupothx.com/?id=A'.$idFaturaParaEnvio.'&amp;valor='.$invoice['total'].'"><br><strong style="font-size: 15px;line-height: 40px;"><i class="ri-pix-line"></i> PIX QR Code e Copia e Cola (R$ '.$invoice['total'].')</strong><br><code id="pixCode" class="copia-cola">'.$grupothx_merge_fields['grupothx_pix_copia_e_cola'].'</code>

               <button
                    class="btn-copy"
                    onclick="copiarTexto(\'pixCode\', this, \'Código PIX copiado!\')">
                    Copiar PIX
                </button>
            </td>
         </tr>
         <tr>
            <td></td>
            <td><span style="font-size: 12px; text-align:center; font-weight:bold; border: solid #CCC 1px; display: block; padding: 5px;background: red;color: white;">Após o pagamento via PIX não esqueça de responder com o comprovante.</span></td>
         </tr>';

         ########################################################################GORFAS BOLETO
         $boleto_gerencianet = array();
			foreach( Capsule::table('gofasgerencianetboleto') -> where('invoice_id', '=', $idFaturaParaEnvio) -> get(
				array( 'invoice_id', 'charge_id', 'link', 'pdf', 'expire_at', 'total', 'barcode', 'charge_id', 'status', 'api_mode', 'qrcode', 'qrcode_image' ) ) as $key => $value ) {
				$boletos_gerencianet[$key] = json_decode(json_encode($value), true);
			}




         if($boletos_gerencianet) //isset($boletos_gerencianet['0']))
         {
               $boleto_gerencianet = $boletos_gerencianet['0'];



               // Merge Fields
               $grupothx_merge_fields['grupothx_link']			= $boleto_gerencianet['link'];
               $grupothx_merge_fields['grupothx_pdf']			= $boleto_gerencianet['pdf'];
               $grupothx_merge_fields['grupothx_barcode']		= $boleto_gerencianet['barcode'];
               $grupothx_merge_fields['grupothx_expire_at']	= date('d/m/Y', strtotime($boleto_gerencianet['expire_at']));
               $grupothx_merge_fields['grupothx_total']		= number_format($boleto_gerencianet['total']/100,  2, ',', '.' );
               $grupothx_merge_fields['grupothx_charge_id']	= $boleto_gerencianet['charge_id'];
               $grupothx_merge_fields['grupothx_api_mode']		= $boleto_gerencianet['api_mode'];
               $grupothx_merge_fields['grupothx_qrcode']		= $boleto_gerencianet['qrcode'];

               if(true) //$params['linkbilletonemail'])
               {
                  $grupothx_merge_fields['invoice_link']		= $boleto_gerencianet['link'];
               }

               $boleto = '
               <tr>
                  <td style="text-align:center;vertical-align:middle;width: 20%;"><img class="mobilehidden" src="https://grupothx.com/imagens/logoboleto.jpg"></td>
                  <td>
                     <br>
                     <p>
                        
                        <strong><i class="bx bx-barcode-reader"></i> Código de Barras:</strong>
                        <br />'.getBarCode($grupothx_merge_fields['grupothx_barcode']).'
                        <br><span id="codigoBarras">'.$grupothx_merge_fields['grupothx_barcode'].'</span>


                     </p>
                     <!--<p><strong>Valor:&nbsp;</strong>R$ '.$grupothx_merge_fields['grupothx_total'].'</p>-->
                     <button
                        class="btn-copy"
                        onclick="copiarTexto(\'codigoBarras\', this, \'Código de barras copiado!\')">
                        <i class="bx bx-copy"></i> Copiar código de barras
                    </button>
                     <a class="btn-copy btn-copy2 fright"  href="'.$grupothx_merge_fields['grupothx_pdf'].'" target="_blank"><i class="ion-printer"></i> Imprimir Boleto</a>

                  </td>
               </tr>
               <tr style="height:10px">
                  <td colspan="2" height="10"><hr style="border:solid red 1px"></td>
               </tr>';

               $pix = '
               <tr style="height:131px">
                  <td style="padding: 20px;text-align:center;vertical-align:middle;width: 20%;">
                     <img class="mobilehidden" src="https://grupothx.com/imagens/logopix.png">
                  </td>
                  <td style="padding: 20px;text-align: center; vertical-align: middle;">
                     <img class="qrcode-pix" src="https://pix.grupothx.com/copiaecolatoqrcode.php?codigo='.$grupothx_merge_fields['grupothx_qrcode'].'"><br><strong style="font-size: 15px;line-height: 40px;"><i class="ri-pix-line"></i> PIX QR Code e Copia e Cola <!--(R$ '.$grupothx_merge_fields['grupothx_total'].')--></strong><br><code id="pixCode" class="copia-cola">'.$grupothx_merge_fields['grupothx_qrcode'].'</code>
                     <button
                        class="btn-copy"
                        onclick="copiarTexto(\'pixCode\', this, \'Código PIX copiado!\')">
                        <i class="bx bx-copy"></i> Copiar PIX
                    </button>
                  </td>
               </tr>
               ';


         }
         ########################################################################GORFAS BOLETO FIM



        


         $vencimento = strftime('%d/%m/%Y', strtotime($invoice['duedate']));


         $grupothx_merge_fields['grupothx_html_formas_pagamento'] = '
         <table width="100%">
            <tbody>
               <tr style="background-color:red;color:#ffffff;padding:7px 15px;font-weight:bold;height:17px">
                  <td style="padding: 5px 10px;" colspan="2"><i class="fa fa-calendar"></i> Vencimento: '.$vencimento.'</td>
               </tr>
               '.$boleto.'
               '.$pix.'
            </tbody>
         </table>';

         return  $grupothx_merge_fields['grupothx_html_formas_pagamento'];
}




            $invoice= localAPI( 'GetInvoice', array('invoiceid' => $idFaturaParaEnvio), false);

            if($invoice['status'] == 'error')
            {
                die(header('Location: https://'.$_SERVER['HTTP_HOST']));
            }

            $invoiceItens = Capsule::select("SELECT * FROM tblinvoiceitems WHERE invoiceid =  {$idFaturaParaEnvio}");


            $itensdafatura = '
            <table style="margin:0 auto;color: #666; font-size: 14px; width: 100%;" border="0" cellspacing="0" cellpadding="5" align="center">
                <tbody>
                    <tr class="tabela">
                        <td style="background-color: #1a3c6a; color: #fff; padding-left: 15px;" height="23"><strong><i class="fa fa-list-ol"></i> Itens da Fatura</strong></td>
                    </tr>
                </tbody>
            </table>';

            foreach($invoiceItens as $item)
            {
                $itensdafatura.= '
                <table style="width: 100%; margin: 0;">
                    <tbody>
                    <tr>
                    <td style="text-align: left; padding: 3px; border: solid #CCC 1px;"><strong>'.$item->description.'</strong></td>
                    <td style="text-align: right; padding: 3px; border: solid #CCC 1px; width: 100px;"><strong>R$ '.$item->amount.'</strong></td>
                    </tr>
                    </tbody>
                </table>';
            }

            $itensdafatura.='
            <table style="width: 100%; margin: 0;" class="table-total">
                <tbody>
                    <tr>
                        <td style="text-align: right; padding: 3px; border: solid #CCC 1px;" colspan="2"><strong> Total:  R$ '.$dadosCliente[0]->total.'</strong></td>
                    </tr>
                </tbody>
            </table>';

            $btNota = getNotaFiscal($idFaturaParaEnvio);


            $dadoscabecalho = Capsule::select("SELECT value FROM tblconfiguration WHERE setting IN('EmailGlobalHeader', 'EmailGlobalFooter') ORDER BY setting");
            $html = html_entity_decode($dadoscabecalho[1]->value);
            $html.='
            <table style="font-size: 14px; color: #666; width: 100%;" border="0" cellspacing="0" cellpadding="0">
                <tbody>
                    <tr>
                    <td width="100%">
                    <h3 id="grupothx_emails_titulo" class="grupothx_emails_titulo">Olá, '.$dadosCliente[0]->firstname.' <i class="fa fa-smile-o"></i></h3>
                    <p>Esses são os serviços referentes à fatura <strong>#'.$idFaturaParaEnvio.'</strong>.</p>
                    '.$itensdafatura.'
                    </td>
                    </tr>
                    <tr>
                        <td>
                        '.$btNota.'
                        '.viewPaymentData($idFaturaParaEnvio, $invoice).'
                    </td>
                    </tr>
                </tbody>
            </table>';





?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pague aqui sua fatura</title>

    <meta name="description" content="Pague sua fatura de forma rápida simples e segura">

    <!-- Favicons-->
    <link rel="shortcut icon" href="img/favicon.ico" type="image/x-icon"/>
    <link rel="apple-touch-icon" type="image/x-icon" href="img/apple-touch-icon-57x57-precomposed.png">
    <link rel="apple-touch-icon" type="image/x-icon" sizes="72x72" href="img/apple-touch-icon-72x72-precomposed.png">
    <link rel="apple-touch-icon" type="image/x-icon" sizes="114x114" href="img/apple-touch-icon-114x114-precomposed.png">
    <link rel="apple-touch-icon" type="image/x-icon" sizes="144x144" href="img/apple-touch-icon-144x144-precomposed.png">

    <meta property="og:type" content="website">
    <meta property="og:title" content="Pague aqui sua fatura">
    <meta property="og:description" content="Pague sua fatura de forma rápida simples e segura">
    <meta property="og:image" content="https://tihospedagem.com.br/thx-host-assets/ogimage-whmcs.webp">
    <meta property="og:image:alt" content="https://tihospedagem.com.br/thx-host-assets/ogimage-whmcs.webp">
    <meta property="og:url" content="<?='https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']?>">

    
<!--Icons by X-Painel-->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/Grupo-THX/xpainel-assets/icons.css" />
<!--End Icons by X-Painel-->


    <style>
        body{
    margin:0;
    padding:15px;
    font-family:Arial,Helvetica,sans-serif;
    background:#f5f5f5;
}

table{
    width:100% !important;
    max-width:900px;
    margin:auto;
    border-collapse:collapse;
}

img{
    max-width:100%;
    height:auto;
}

code{
    display:block;
    white-space:pre-wrap;
    word-break:break-all;
    overflow-wrap:anywhere;
    font-size:12px;
}

.barcode {
    width: 100%;
    margin: 10px 0px 0px 0;
}

#codigoBarras {
    text-align: center;
    display: block;
    font-size: 14pt;
    color: black;
}

.fright{
    float: right;
}

.paid{
    text-align:center;
    color:#1a3c6a;
    margin:40px auto;
}

.copia-cola{
    width: 100%;font-size: 12px;border:solid #ccc 1px;display:block;padding:5px;text-decoration:none;white-space: normal;background: none;color: #333333;
}

.qrcode-pix{
    width: 80%;max-width: 250px;border: solid #29bbac 4px
}

.table-total strong {
    color: #333333;
    font-size: 19px;
    margin: 0;
    padding: 0;
    line-height: 47px;
}

#grupothx_emails_titulo {
    color: #1a3c6a;
    font-size: 21pt;
}

#grupothx_emails_titulo i{
    color: red;
    font-size: 21pt;
}
@media (max-width:768px){


    body{
        padding:10px;
    }

    td{
        display:block;
        width:100% !important;
        box-sizing:border-box;
    }

    tr{
        /* display:block; */
    }

    img{
        margin:20px auto;
        display:block;
    }

    .mobilehidden {
        display: none;
    }

    h3{
        font-size:20px;
    }

    .btn-copy {

    width: 100% !important;
    max-width: stretch !important;
}
}
        .grupothx_emails_topo{
            margin: 0 auto !important;
        }
        code{
            inline-size: 400px;
	        overflow-wrap: break-word;
        }
        .paid{
            text-align: center;
	        color: #1a3c6a;
            margin: 40px auto;
        }

        .qrcode-pix{

                width: 100%;
	            max-width: 100%;
            }
.btn-copy {
	display: inline-block;
	width: auto;
	max-width: 260px;
	padding: 5px 10px;
	border: 0;
	border-radius: 6px;
	background: #1a3c6a;
	color: #fff;
	font-size: 16px;
	font-weight: bold;
	cursor: pointer;
	transition: .2s;
	text-decoration: none;
	text-align: center;
	float: none;
	margin: 10px;
}

.btn-copy:hover{
    background:red;
}

.btn-nota {
    width: 170px;
    margin: 15px auto;
    display: block;
    float: right;
}
    </style>
</head>
<body>
    <?=$html?>
    <script>
    function removeUrlQueryString()
    {
        window.history.pushState('Object', '', '/');
    }
    //removeUrlQueryString();
    </script>

    <script>
function copiarTexto(id, botao, textoSucesso)
{
    var elemento = document.getElementById(id);

    if (!elemento) {
        return;
    }

    var texto = (elemento.innerText || elemento.value || '').trim();

    function sucesso() {
        var original = botao.innerHTML;

        botao.innerHTML = '<i class="fa fa-check"></i> ' + textoSucesso;
        botao.disabled = true;

        setTimeout(function () {
            botao.innerHTML = original;
            botao.disabled = false;
        }, 2000);
    }

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(texto)
            .then(sucesso)
            .catch(fallback);
    } else {
        fallback();
    }

    function fallback()
    {
        var input = document.createElement('textarea');
        input.value = texto;
        input.style.position = 'fixed';
        input.style.left = '-9999px';

        document.body.appendChild(input);
        input.select();

        try {
            document.execCommand('copy');
            sucesso();
        } finally {
            document.body.removeChild(input);
        }
    }
}
</script>
</body>
</html>


