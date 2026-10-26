<? header('Location: https://grupothx.com/thx/pagamento/retorno');
require($_SERVER['DOCUMENT_ROOT']."/thx/init.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/thx/tarifasTHX.php');
$whmcs->load_function('gateway');
$whmcs->load_function('client');
setlocale( LC_ALL, "pt_BR" );
    date_default_timezone_set('America/Sao_Paulo');
$erro = '';


define (ARQUIVO_MERCADOPAGO , isset($_POST['external_reference']) ? $_POST['external_reference'] : 'logs-sem-numero-de-ordem');


$GLOBALS['logatualthxMercadoPago'] = '<p>POST RECEBIDO '.date('d/m/Y G:i:s').'<p/><p>Ordem: '.ARQUIVO_MERCADOPAGO.'<br />POST:<hr /><pre>'.print_r($_POST,true).'</pre><br />GET:<hr /><pre>'.print_r($_GET,true).'</pre><br />SERVER:<hr /><pre>'.print_r($_SERVER,true).'</pre></p>';


function mercadoPagoOk()
{
    enviaLogTHX($GLOBALS['logatualthxMercadoPago']);
	die("<script>location='".HTTP_WHMCS."pagamento/retorno'</script>");
}


logMercadoPagoRetorno('BEGIN:::'.date('d/m/Y G:i').'<br />');


function moeda($valor,$separador=','){
    return str_replace(array(',','.'),'',$valor);
}
function limitaCaracteres($str,$max,$sufixo='')
{
    $str = tiracento($str);
    if(strlen($str) > $max)
        $str=substr($str, 0, $max).$sufixo;
    return $str;
}
function logMercadoPagoRetorno($dados)
{
    $GLOBALS['logatualthxMercadoPago'].= '<p><hr /><pre>'.$dados.'</pre></p>';
    $quebra = chr(13).chr(10);
    $dados='LOG:'.$dados.$quebra;
    $variaveis = $quebra.'POST:'.$quebra.'<pre>'.print_r($_POST,true).'</pre>'.$quebra;
    /*$variaveis.= $quebra.'GET:'.$quebra.'<pre>'.print_r($_GET,true).'</pre>'.$quebra;
    $variaveis.= $quebra.'SERVER:'.$quebra.'<pre>'.print_r($_SERVER,true).'</pre>'.$quebra;*/
    $cabecalho =$quebra.date('d/m/Y G:s:i').$quebra.'INICIO_______________________________________________________________';
    $fim = $quebra.'_______________________________________________________________END';
    $dados = $cabecalho.$dados.$variaveis.$fim;
    $abre = fopen('log-mercadopago/'.ARQUIVO_MERCADOPAGO, 'a');
    $escreve = fwrite($abre, $quebra.$dados);
    fclose($abre);
}

function mercadopagoSetPagamentoM()
{
        $command = "updateinvoice";
        $adminuser = 'deljdl';
        //$values["invoiceid"] = ARQUIVO_MERCADOPAGO;
        $values["paymentmethod"] = 'mercadopago';
        $results = localAPI($command,$values,$adminuser);
        $command = "addinvoicepayment";
        $adminuser = 'deljdl';
        $values["invoiceid"] = ARQUIVO_MERCADOPAGO;
        $values["transid"] = '';
        $values["paymentmethod"] = 'mercadopago';
        //$values["amount"] = $valorPago;
        $values["fees"] = 0;
        $values["date"] = date('Y-m-d G:i:s');//PADRÂO YYYY-MM-DD HH:mm:ss
        $values["noemail"] = false;
        $results = localAPI($command,$values,$adminuser);


        if($results["result"] == 'success')
        {
            logMercadoPagoRetorno('Baixa ok. | InvoiceId->'.ARQUIVO_MERCADOPAGO.' | Result->'.$results["result"].' |  | Message->'.$results["message"]);
            return true;
        }
        else
        {
            logMercadoPagoRetorno('Erro na baixa. | InvoiceId->'.ARQUIVO_MERCADOPAGO.' | Result->'.$results["result"].' |  | Message->'.$results["message"]);
            return false;
        }
        mercadoPagoOk();
}

logMercadoPagoRetorno();





if(isset($_POST['external_reference']) && isset($_POST['payment_status']))
{
	if($_POST['payment_status'] == 'approved')
	{
		mercadopagoSetPagamentoM();
	}
    else
    {
        //$clientsdetails = getClientsDetails($userid);
        //enviaEmailTHX('Pagamento Reprovado', $xml['userid']);
    }
}




$GLOBALS['logatualthxMercadoPago'].= 'Pagamento não efetuado';

mercadoPagoOk();