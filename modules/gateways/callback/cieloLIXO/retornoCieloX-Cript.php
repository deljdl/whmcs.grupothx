<? 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require("../../../../init.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/thx/tarifasTHX.php');
$whmcs->load_function('gateway');
$whmcs->load_function('client');
setlocale( LC_ALL, "pt_BR" );
    date_default_timezone_set('America/Sao_Paulo');



define (ARQUIVO_CIELO , isset($_POST['order_number']) ? $_POST['order_number'] : 'logs-sem-numero-de-ordem');

$GLOBALS['logatualthxCielo'] = '<p>POST RECEBIDO '.date('d/m/Y G:i:s').'<p/><p>Ordem: '.ARQUIVO_CIELO.'<br />POST:<hr /><pre>'.print_r($_POST,true).'</pre><br />GET:<hr /><pre>'.print_r($_GET,true).'</pre><br />SERVER:<hr /><pre>'.print_r($_SERVER,true).'</pre></p>';
function cielok()
{
    enviaLogTHX($GLOBALS['logatualthxCielo']);
	die('<status>OK</status>');
}
logCielo('BEGIN:::'.date('d/m/Y G:i').'<br />');


function logCielo($dados = '')
{
    $GLOBALS['logatualthxCielo'].= '<p><hr /><pre>'.$dados.'</pre></p>';
    $quebra = chr(13).chr(10);
    $dados='LOG:'.$dados.$quebra;
    $variaveis = $quebra.'POST:'.$quebra.'<pre>'.print_r($_POST,true).'</pre>'.$quebra;
    /*$variaveis.= $quebra.'GET:'.$quebra.'<pre>'.print_r($_GET,true).'</pre>'.$quebra;
    $variaveis.= $quebra.'SERVER:'.$quebra.'<pre>'.print_r($_SERVER,true).'</pre>'.$quebra;*/
    $cabecalho =$quebra.date('d/m/Y G:s:i').$quebra.'INICIO_______________________________________________________________';
    $fim = $quebra.'_______________________________________________________________END';
    $dados = $cabecalho.$dados.$variaveis.$fim;
    $abre = fopen('log-cielo/'.ARQUIVO_CIELO, 'a');
    $escreve = fwrite($abre, $quebra.$dados);
    fclose($abre);
}

function cieloSetPagamento($invoiceId,$valorPago,$tipoCielo)
{
        // $command = "updateinvoice";
        // $adminuser = 'deljdl';
        // $values["invoiceid"] = $invoiceId;
        // $values["paymentmethod"] = $tipoCielo;
        // $results = localAPI($command,$values,$adminuser);


        // $command = "addinvoicepayment";
        // $adminuser = 'deljdl';
        // $values["invoiceid"] = $invoiceId;
        // $values["transid"] = '';
        // $values["amount"] = $valorPago;
        // $values["fees"] = 0;
        // $values["date"] = date('Y-m-d G:i:s');//PADRÂO YYYY-MM-DD HH:mm:ss
        // $values["noemail"] = false;
        // $results = localAPI($command,$values,$adminuser);

        $results["result"] = false;
        if($results["result"] == 'success')
        {
            logCielo('Baixa ok. | InvoiceId->'.$invoiceId.' | Result->'.$results["result"].' | ValorPago ->'.$valorPago.' | Message->'.$results["message"]);
            return true;
        }
        else
        {
            logCielo('Erro na baixa. | InvoiceId->'.$invoiceId.' | Result->'.$results["result"].' | ValorPago ->'.$valorPago.' | Message->'.$results["message"]);
            return false;
        }
        cielok();
}

function baixaViaCielo($invoiceId, $valorPago)
{
    $command = "getinvoice";
    $adminuser = 'deljdl';
    $values["invoiceid"] = $invoiceId;

    $xml = localAPI($command,$values,$adminuser);

    $valorPago = getValorCielo($valorPago);
    $valorPago = $valorPago > $xml['total'] ? $xml['total'] : $valorPago;



    $status = $xml['status'];

    if($status == 'error')
    {
        logCielo('Erro na baixa:'.$xml['message']);
        return false;
    }

    if($_POST['payment_status'] == 2)
    {
        if($xml['status'] != 'Paid')
        {
            return cieloSetPagamento($invoiceId,$valorPago,'cielo');
        }
        else
        {
            logCielo('STATUS NÃO ALTERADO : userid->'.$xml['status']);
            return false;
        }
    }
    else
    {
        logCielo('Pagamento Ainda não Aprovado Invoice  '.$invoiceId);
        enviaEmailTHX('Pagamento Reprovado', $xml['userid']);
    }

    cielok();


}
function getValorCielo($valor)
{
    $valor = $valor/100;
    return  number_format($valor, 2, '.', '');
}
function addCreditoCielo($cliente, $valor, $log)
{
    $valor = getValorCielo($valor);
    logCielo('Invoice não paga não foi localizada. Verifique se a aplicação de crédito é devida:'.print_r($log,true));
   /* $command = 'AddCredit';
    $postData = array(
        'clientid' => $cliente,
        'description' => 'Créditos Adicionados por pagamento Recorrente Via Cartão de Crédito',
        'amount' => $valor
    );
    $adminUsername = 'deljdl';

    $xml = localAPI($command, $postData, $adminUsername);

    $status = $xml['status'];

    if($status == 'error')
    {
        logCielo('Erro na adção de créditos:'.$xml['message']);
    }
    else
    {
        enviaEmailTHX('credito adicionado', $cliente);

        logCielo('Créditos adicionados ok. | Valor: -> '.$valor.' | Userid->'.$produto['clientid'].' | Result->'.$xml["result"].' | Message->'.$xml["message"]);
    }*/
    cielok();
}
function baixaRecorrenteServicoViaCielo($service_id, $valorPago)
{

    $service_id = str_replace('s','',$service_id);

    $command = 'GetClientsProducts';
    $postData = array(
    'serviceid' => $service_id,
    );
    $adminUsername = 'deljdl';
    $produto = localAPI($command, $postData, $adminUsername);
    $produto = $produto['products']['product'][0];

    ##PEGANDO ULTIMA INVOICE
    $command = 'GetInvoices';
    $postData = array(
        'userid' => $produto['clientid'],
        'status' => 'Unpaid',
    );
    $adminUsername = 'deljdl';

    $allinvoices = localAPI($command, $postData, $adminUsername);

    logCielo('Invoices não pagas localizadas:'.print_r($allinvoices,true));

    $invoices=$allinvoices['invoices']['invoice'];

    logCielo('Invoices não pagas separadas:'.print_r($invoices,true));




    foreach ($invoices as $invoice)
    {

        logCielo('Invoice não paga localizada:'.print_r($invoice,true));

        $command = 'GetInvoice';
        $postData = array(
            'invoiceid' => $invoice['id'],
        );
        $adminUsername = 'deljdl';

        $invoiceAtual = localAPI($command, $postData, $adminUsername);
        logCielo('Invoice dado:'.print_r($invoiceAtual,true));
        foreach($invoiceAtual['items']['item'] as $item)
        {
            logCielo('Checando -> '."{$item['relid']} ==  {$service_id}");
            if($item['relid'] ==  $service_id)
            {
            	if($_POST['payment_status'] == 2)
                {

                    baixaViaCielo($invoice['id'],$valorPago,'cielorecorrente');
                    enviaEmailTHX('Pagamento Recorrente Produto', $service_id);
                }
                else
				{
                	$aviso = enviaEmailTHX('Pagamento Reprovado', $produto['clientid'] );
                    if($aviso)
                    {
                        logcielo('Cliente avisado que pagamento não foi aprovado');
                    }
                    else
                    {
                        logCielo('Erro ao avisar que pagamento não foi aprovado');
                    }
                }

                cielok();
            }
        }
    }

  	if($_POST['payment_status'] == 2)
    {
    	return addCreditoCielo($produto['clientid'], $valorPago, $invoices);
	}
    cielok();
}

function baixaRecorrenteDominioViaCielo($domainid, $valorPago)
{


    $domainid = str_replace('d','',$domainid);
    $command = 'GetClientsDomains';
    $postData = array(
        'domainid' => $domainid,
        'limitnum' => 1,
    );
    $adminUsername = 'deljdl';

    $dominio = localAPI($command, $postData, $adminUsername);
    $dominio = $dominio['domains']['domain'][0];


    ##PEGANDO ULTIMA INVOICE
    $command = 'GetInvoices';
    $postData = array(
        'userid' => $dominio['userid'],
        'status' => 'Unpaid',
    );
    $adminUsername = 'deljdl';

    $allinvoices = localAPI($command, $postData, $adminUsername);

    logCielo('Invoices não pagas localizadas:'.print_r($allinvoices,true));

    $invoices=$allinvoices['invoices']['invoice'];

    logCielo('Invoices não pagas separadas:'.print_r($invoices,true));

    foreach ($invoices as $invoice)
    {
            $command = 'GetInvoice';
            $postData = array(
                'invoiceid' => $invoice['id'],
            );
            $adminUsername = 'deljdl';

            $invoiceAtual = localAPI($command, $postData, $adminUsername);
            foreach($invoiceAtual['items']['item'] as $item)
            {

                logCielo('Checando -> '."{$item['relid']} ==  {$domainid}");
                if($item['relid'] ==  $domainid)
                {
                    logCielo('Invoice dado:'.print_r($invoiceAtual,true));

					if($_POST['payment_status'] == 2)
					{
                        baixaViaCielo($invoice['id'],$valorPago,'cielorecorrente');
                        enviaEmailTHX('Pagamento Recorrente Dominio', $domainid);
                    }
                    else
                    {
                        $aviso = enviaEmailTHX('Pagamento Reprovado', $dominio['userid'] );
                    }

                    cielok();
                }
            }
    }

    if($_POST['payment_status'] == 2)
    {
    	return addCreditoCielo($dominio['userid'], $valorPago, $invoices);
	}

    cielok();
}

/*

POSSIBILIDADES


payment_method_type

VALOR   DESCRIÇÃO
1   Cartão de Crédito
2   Boleto Bancário
3   Débito Online
4   Cartão de Débito

payment_method_brand
VALOR   DESCRIÇÃO
1   Visa
2   Mastercad
3   AmericanExpress
4   Diners
5   Elo
6   Aura
7   JCB
PAYMENT_METHOD_BANK

VALOR   DESCRIÇÃO
1   Banco do Brasil
2   Bradesco


payment_status
1   Pendente (Para todos os meios de pagamento)
2   Pago (Para todos os meios de pagamento)
3   Negado (Somente para Cartão Crédito)
4   Expirado (Cartões de Crédito e Boleto)
5   Cancelado (Para cartões de crédito)
6   Não Finalizado (Todos os meios de pagamento)
7   Autorizado (somente para Cartão de Crédito)
8   Chargeback (somente para Cartão de Crédito)
*/

if(! isset($_GET['TOKEN_CIELO']) || $_GET['TOKEN_CIELO'] != TOKEN_CIELO)
{
    logCielo('TOKEN_CIELO inválido !!');
    cielok();
}

if(! isset($_POST['order_number']))
{
    logCielo('Sem Numero de Ordem<br /><pre>'.print_r($_POST,1).'</pre>');
    cielok();
}

if(! isset($_POST['payment_status']))
{
    logCielo('Sem status');
    cielok();
}

if(strstr($_POST['order_number'], 's'))
{
	baixaRecorrenteServicoViaCielo($_POST['order_number'], $_POST['amount']);
}
elseif(strstr($_POST['order_number'], 'd'))
{
	baixaRecorrenteDominioViaCielo($_POST['order_number'], $_POST['amount']);
}
else
{
    baixaViaCielo($_POST['order_number'], $_POST['amount']);
}


cielok();




