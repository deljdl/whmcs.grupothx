<?
require($_SERVER['DOCUMENT_ROOT']."/thx/init.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/thx/tarifasTHX.php');
$whmcs->load_function('gateway');
$whmcs->load_function('client');
setlocale( LC_ALL, "pt_BR" );
date_default_timezone_set('America/Sao_Paulo');
$erro = '';
$GLOBALS['logatualthxMercadoPago'] = '<h1>Log Iniciada '.date('d/m/Y G:s').': </h1>'.chr(13).chr(10);
$GLOBALS['logatualthxMercadoPagoId'] = 'logs-sem-numero-de-ordem';


function logMercadoPagoWebhook($dados)
{
    $quebra = chr(13).chr(10);
    $dados=$quebra.'LOG:'.$dados.$quebra;
    $variaveis= $quebra.'GET:'.$quebra.'<pre>'.print_r($_GET,true).'</pre>'.$quebra;
    $cabecalho =$quebra.date('d/m/Y G:s:i').$quebra.'INICIO_______________________________________________________________'.$quebra;
    $fim = $quebra.'_______________________________________________________________END'.$quebra;
    $dados = $cabecalho.$dados.$variaveis.$fim;
    $abre = fopen('log-mercadopago-posts-notificacoes/'.$GLOBALS['logatualthxMercadoPagoId'], 'a');
    $escreve = fwrite($abre, $quebra.$dados);
    fclose($abre);
}

function mercadoPagoOk()
{
    enviaLogTHXJdl($GLOBALS['logatualthxMercadoPago']);
    logMercadoPagoWebhook($GLOBALS['logatualthxMercadoPago']);
    die('ok');
}

mercadoPagoOk();
function padronizaFloatMercado($v)
{
    return str_replace('.',',', $v);
}
function padronizaFloatMercadoToSave($v)
{
    return str_replace('.',',', $v);
}
function mercadopagoSetPagamentoM($dados)
{

	if($dados->status != 'approved')
	{
        $GLOBALS['logatualthxMercadoPago'].= '<br /><h2>Notificação não é de approved, e sim de '.$dados->status.'.<br />Saindo ... </h2>';
		mercadoPagoOk();
	}

    ##PEGANDO DADOS DA INVOICE
    $command = "getinvoice";
    $adminuser = 'deljdl';
    $values1["invoiceid"] = $GLOBALS['logatualthxMercadoPagoId'];
    $xml = localAPI($command,$values1,$adminuser);

    $xml['total'] = padronizaFloatMercado($xml['total']);

    $GLOBALS['logatualthxMercadoPago'].= '<br /><h2>Dados da fatura</h2><pre>'.print_r($xml,1).'</pre>';

    $valorPago = padronizaFloatMercado($dados->transaction_details->total_paid_amount);

    $valorPago = $valorPago > $xml['total'] ? $xml['total'] : $valorPago;


    if($xml['status'] == 'Paid')
    {
        $GLOBALS['logatualthxMercadoPago'].= '<br /><h2>Invoice já estava paga !!!. Saindo ...</h2>';
        mercadoPagoOk();
    }

    ##ATUALIZANDO INVOICE PARA O MERCADO LIVRE

    // $command = "updateinvoice";
    // $adminuser = 'deljdl';
    // $values2["invoiceid"] = $GLOBALS['logatualthxMercadoPagoId'];
    // $values2["paymentmethod"] = 'mercadopago';
    // $results = localAPI($command,$values2,$adminuser);
    // $GLOBALS['logatualthxMercadoPago'].= '<br /><h2>Atualizando a forma de pagamento para mercadopago </h2><pre>'.print_r($results,1).'</pre>';

    // $taxacobradaMercadoLivre = ($dados->transaction_details->total_paid_amount - $dados->transaction_details->net_received_amount);
    ##ADICIONANDO O PAGAMENTO
    // $command = "addinvoicepayment";
    // $adminuser = 'deljdl';
    // $values3["invoiceid"] = $GLOBALS['logatualthxMercadoPagoId'];
    // $values3["transid"] = $_GET["data_id"];
    // $values3["amount"] = padronizaFloatMercadoToSave($valorPago);
    // $values3["fees"] = padronizaFloatMercadoToSave($taxacobradaMercadoLivre);
    // $values3["date"] = date('Y-m-d G:i:s');//PADRÂO YYYY-MM-DD HH:mm:ss
    // $values3["noemail"] = false;
    // $results = localAPI($command,$values3,$adminuser);



    // $GLOBALS['logatualthxMercadoPago'].= '<br /><h2>Dando a devida baixa no valor de R$ '.$valorPago.'</h2><pre>'.print_r($results,1).'</pre>';
    // #####CORETO ?



    // if($results["result"] == 'success')
    // {
    //     $GLOBALS['logatualthxMercadoPago'].= '<br /><h2>Baixa ok. | InvoiceId->'.$GLOBALS['logatualthxMercadoPagoId'].'</h2><pre>'.print_r($results,1).'</pre>';
    //     $GLOBALS['logatualthxMercadoPago'].= '<br /><h2>Dados setados na Baixa</h2><pre>'.print_r($values3,1).'</pre>';
    // }
    // else
    // {
    //     $GLOBALS['logatualthxMercadoPago'].= '<br /><h2>Erro na baixa. | InvoiceId->'.$GLOBALS['logatualthxMercadoPagoId'].'</h2><pre>'.print_r($results,1).'</pre>';
    // }
    mercadoPagoOk();
}


$GLOBALS['logatualthxMercadoPago'].= '<br /><h2>Mercado Pago Envio de Status </h2><pre>'.print_r($_GET,1).'</pre>';



if(isset($_GET['data_id']) && $_GET['type'] == 'payment')
{

	// SDK de Mercado Pago
	require __DIR__ .  '/vendor/autoload.php';
	// Configura credenciais
	MercadoPago\SDK::setAccessToken('APP_USR-393294712257526-100422-41f857a1f6c8fb661b2ebe66b2066df4-165408372');

	$payment = new MercadoPago\Payment();

	$dados = $payment->find_by_id($_GET["data_id"]);

    $GLOBALS['logatualthxMercadoPago'].= '<br /><h2>Mercado Pago Find_by_id </h2><pre>'.print_r($dados,1).'</pre>';



  if($dados->external_reference != null)
  {
    $GLOBALS['logatualthxMercadoPagoId'] = $dados->external_reference;
    $GLOBALS['logatualthxMercadoPago'].= '<br /><h2>Id encontrado: '.$GLOBALS['logatualthxMercadoPagoId'].'. Baixa Iniciada :) </h2>';
 	mercadopagoSetPagamentoM($dados);
  }
  else
  {
    $GLOBALS['logatualthxMercadoPago'].= '<br /><h2>Id não encontrada : '.$GLOBALS['logatualthxMercadoPagoId'].'. Baixa não iniciada :( </h2>';
  	mercadoPagoOk();
  }
}

$GLOBALS['logatualthxMercadoPago'].= '<br /><h2>Nenhum dado recebido</h2>';


mercadoPagoOk();