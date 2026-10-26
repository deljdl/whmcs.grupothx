<?

require($_SERVER['DOCUMENT_ROOT']."/thx/init.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/thx/tarifasTHX.php');
$whmcs->load_function('gateway');
$whmcs->load_function('client');
setlocale( LC_ALL, "pt_BR" );
    date_default_timezone_set('America/Sao_Paulo');
$erro = '';


define (ARQUIVO_MERCADOPAGO , isset($_POST['external_reference']) ? $_POST['external_reference'] : 'logs-sem-numero-de-ordem');


$GLOBALS['logatualthxMercadoPago'] = '<p>POST RECEBIDO '.date('d/m/Y G:i:s').'<p/><p>Ordem: '.ARQUIVO_MERCADOPAGO.'<br />POST:<hr /><pre>'.print_r($_POST,true).'</pre><br />GET:<hr /><pre>'.print_r($_GET,true).'</pre><br />SERVER:<hr /><pre>'.print_r($_SERVER,true).'</pre></p>';



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
    $abre = fopen('log-cielo/'.ARQUIVO_CIELO, 'a');
    $escreve = fwrite($abre, $quebra.$dados);
    fclose($abre);
}