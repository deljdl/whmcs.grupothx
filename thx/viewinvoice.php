<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/init.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/tarifasTHX.php';
use WHMCS\Database\Capsule;

// Redireciona somente links padrão do WHMCS com ID numérico.
if (isset($_GET['id']) && ctype_digit((string) $_GET['id']) && (int) $_GET['id'] > 0) {
    $id = (int) $_GET['id'];
    $usuario = new \WHMCS\Authentication\CurrentUser();
    $cliente = $usuario->client();

    if (!$cliente && !$usuario->isAuthenticatedAdmin()) {
        header('Location: /clientarea.php', true, 302);
        exit;
    }

    $dono = Capsule::table('tblinvoices')->where('id', $id)->value('userid');
    if (!$dono || (!$usuario->isAuthenticatedAdmin() && (int) $cliente->id !== (int) $dono)) {
        http_response_code(403);
        exit('Fatura não localizada ou acesso negado.');
    }

    header('Cache-Control: no-store');
    header('Location: /viewinvoice-thx/' . rawurlencode(criptX((string) $id)), true, 302);
    exit;
}

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
        return '';
    }

    if($invoice['status'] == 'Cancelled')
    {
        return '';
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


         $abasPagamento = '
         <div class="thx-pagamentos">
             <input type="radio" name="thx-pagamento" id="thx-tab-pix" checked>
             <label for="thx-tab-pix" class="thx-aba thx-aba-pix"><i class="ri-pix-line"></i> PIX</label>';

         if ($boleto !== '') {
             $abasPagamento .= '
             <input type="radio" name="thx-pagamento" id="thx-tab-boleto">
             <label for="thx-tab-boleto" class="thx-aba thx-aba-boleto"><i class="bx bx-barcode-reader"></i> Boleto</label>';
         }

         $abasPagamento .= '
             <div class="thx-painel thx-painel-pix">
                 <table><tbody>'.$pix.'</tbody></table>
             </div>';

         if ($boleto !== '') {
             $abasPagamento .= '
             <div class="thx-painel thx-painel-boleto">
                 <table><tbody>'.$boleto.'</tbody></table>
             </div>';
         }

         $abasPagamento .= '</div>';

         $grupothx_merge_fields['grupothx_html_formas_pagamento'] = '
         <div class="thx-vencimento"><i class="fa fa-calendar"></i> Vencimento: '.$vencimento.'</div>
         <h3 class="thx-titulo-pagamento">Pague sua fatura</h3>
         '.$abasPagamento;

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


            $statusFatura = [
                'Paid' => ['Paga', 'paga'],
                'Unpaid' => ['Aguardando pagamento', 'pendente'],
                'Payment Pending' => ['Pagamento em processamento', 'pendente'],
                'Cancelled' => ['Cancelada', 'cancelada'],
                'Refunded' => ['Reembolsada', 'cancelada'],
                'Draft' => ['Em elaboração', 'pendente'],
            ];
            [$textoStatus, $classeStatus] = $statusFatura[$invoice['status']] ?? [$invoice['status'], 'pendente'];

            $dadoscabecalho = Capsule::select("SELECT value FROM tblconfiguration WHERE setting IN('EmailGlobalHeader', 'EmailGlobalFooter') ORDER BY setting");
            $html = html_entity_decode($dadoscabecalho[1]->value);
            $html.='
            <table style="font-size: 14px; color: #666; width: 100%;" border="0" cellspacing="0" cellpadding="0">
                <tbody>
                    <tr>
                    <td width="100%">
                    <div class="thx-cabecalho">
                        <h3 id="grupothx_emails_titulo" class="grupothx_emails_titulo">Olá, '.$dadosCliente[0]->firstname.'</h3>
                        <div class="thx-status thx-status-'.$classeStatus.'"><i class="fa fa-circle"></i> '.htmlspecialchars($textoStatus, ENT_QUOTES, 'UTF-8').'</div>
                    </div>
                    <div class="thx-resumo">
                        <span>Fatura <strong>#'.$idFaturaParaEnvio.'</strong></span>
                        <strong>R$ '.number_format((float)$dadosCliente[0]->total, 2, ',', '.').'</strong>
                    </div>
                    <details class="thx-detalhes">
                        <summary><i class="fa fa-list-ol"></i> Ver itens da fatura</summary>
                        <div class="thx-itens">'.$itensdafatura.'</div>
                    </details>
                    </td>
                    </tr>
                    <tr>
                        <td>
                        <div class="thx-area-pagamento">
                            '.$btNota.'
                            '.viewPaymentData($idFaturaParaEnvio, $invoice).'
                        </div>
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
    float: none;
    display: block;
    width: fit-content !important;
    max-width: 100% !important;
    box-sizing: border-box;
    margin: 8px 0 14px auto;
    padding: 8px 12px;
    font-size: 13px;
    background: #fff;
    color: #1a3c6a;
    border: 1px solid #1a3c6a;
}
.btn-nota:hover {
    background: #1a3c6a;
    color: #fff;
}

/* Cabeçalho da fatura */
.thx-cabecalho { display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; margin:0 0 12px; }
.thx-cabecalho #grupothx_emails_titulo { margin:0; font-size:20px; min-width:0; }
.thx-cabecalho .thx-status { margin:0; }
.thx-area-pagamento { display:flex; flex-direction:column; }
.thx-area-pagamento .thx-vencimento { order:1; }
.thx-area-pagamento .btn-nota { order:2; }
.thx-area-pagamento .thx-titulo-pagamento { order:3; }
.thx-area-pagamento .thx-pagamentos { order:4; }
/* Página de pagamento compacta */
.thx-status { display:inline-block; margin:0 0 5px; padding:7px 12px; border-radius:6px; font-size:13px; font-weight:bold; }
.thx-status i { font-size:9px; vertical-align:middle; margin-right:4px; }
.thx-status-paga { background:#e5f5e9; color:#176532; }
.thx-status-pendente { background:#fff4d8; color:#855700; }
.thx-status-cancelada { background:#fce7e7; color:#9c2525; }
body { padding:12px; }
body > table, .grupothx_emails_topo { max-width:680px !important; }
.thx-resumo { display:flex; justify-content:space-between; align-items:center; gap:12px; margin:14px 0; padding:16px; background:#f4f7fb; border:1px solid #dce5ef; border-radius:8px; color:#1a3c6a; }
.thx-resumo > strong { font-size:23px; white-space:nowrap; }
.thx-detalhes { margin:12px 0 18px; border:1px solid #dce5ef; border-radius:8px; overflow:hidden; }
.thx-detalhes summary { display:flex; align-items:center; gap:8px; cursor:pointer; padding:13px 15px; background:#1a3c6a; color:#fff; font-weight:bold; list-style:none; }
.thx-detalhes summary::-webkit-details-marker { display:none; }
.thx-itens { padding:0; overflow-wrap:anywhere; }
.thx-itens table { font-size:12px !important; }
.thx-itens > table:first-child { display:none; }
.thx-vencimento { padding:11px 14px; border-radius:6px; background:#d92222; color:white; font-weight:bold; margin:14px 0; }
.thx-titulo-pagamento { color:#1a3c6a; font-size:19px; margin:20px 0 12px; text-align:center; }
.thx-pagamentos { display:flex; flex-wrap:wrap; gap:8px; }
.thx-pagamentos > input { position:absolute; width:1px; height:1px; opacity:0; }
.thx-aba { box-sizing:border-box; flex:1; min-width:125px; text-align:center; padding:13px 8px; border:2px solid #dce5ef; border-radius:8px; color:#1a3c6a; cursor:pointer; font-weight:bold; font-size:15px; background:#f8fafd; }
.thx-aba i { margin-right:5px; }
#thx-tab-pix:checked ~ .thx-aba-pix, #thx-tab-boleto:checked ~ .thx-aba-boleto { border-color:#1a3c6a; background:#1a3c6a; color:white; }
.thx-pagamentos > input:focus-visible + .thx-aba { outline:3px solid #f05030; outline-offset:2px; }
.thx-painel { display:none; width:100%; box-sizing:border-box; padding:0; border:1px solid #dce5ef; border-radius:8px; text-align:center; }
#thx-tab-pix:checked ~ .thx-painel-pix, #thx-tab-boleto:checked ~ .thx-painel-boleto { display:block; }
.thx-painel table { max-width:100% !important; }
.thx-painel td { padding:8px !important; }
.thx-painel .qrcode-pix { width:100%; max-width:230px; height:auto; margin:10px auto; }
.thx-painel .copia-cola { box-sizing:border-box; width:100%; max-width:100%; inline-size:auto; }
.thx-painel #codigoBarras { font-size:12px; overflow-wrap:anywhere; }
.thx-painel .btn-copy { float:none; }
@media (max-width:768px) {
  .thx-resumo { padding:12px; }
  .thx-resumo > strong { font-size:20px; }
  .thx-painel { padding:0; }
  .thx-painel td { display:block; width:100% !important; }
  .thx-painel img { margin:10px auto; }
  .thx-painel .btn-copy { box-sizing:border-box; width:100% !important; max-width:100% !important; margin:7px 0; }
  .thx-itens table { table-layout:fixed; }
  .thx-itens td { display:table-cell; width:auto !important; overflow-wrap:break-word; }
  .thx-itens td:nth-child(2) { width:82px !important; white-space:nowrap; vertical-align:top; }
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


