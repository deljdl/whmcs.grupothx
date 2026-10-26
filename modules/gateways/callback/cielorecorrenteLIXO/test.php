<?
include($_SERVER['DOCUMENT_ROOT']."/dbconnect.php");
include($_SERVER['DOCUMENT_ROOT']."/includes/functions.php");
include($_SERVER['DOCUMENT_ROOT']."/includes/gatewayfunctions.php");
include($_SERVER['DOCUMENT_ROOT']."/includes/invoicefunctions.php");
define (ARQUIVO_CIELO , isset($_POST['order_number']) ? $_POST['order_number'] : 'logs-sem-numero-de-ordem');
define (CIELO_OK, '<status>OK</status>');
function logCielo($dados = '')
{
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

function baixaRecorrenteDominioViaCielo($domainid, $valorPago, $data_pagamento)
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
    

    $valorPago = $valorPago/100;
    $valorPago = $valorPago > $produto['dominio'] ? $produto['dominio'] : $valorPago;
    $valorPago = number_format($valorPago, 2, '.', '');


    ##PEGANDO ULTIMA INVOICE
    $command = 'GetInvoices';
    $postData = array(
        'userid' => $dominio['userid'],
        'status' => 'Unpaid',
    );
    $adminUsername = 'deljdl';

    $allinvoices = localAPI($command, $postData, $adminUsername);

    logCielo('Invoices não pagas localizadas:'.print_r($allinvoices,1));
    
    $invoices=$allinvoices['invoices']['invoice'];

    logCielo('Invoices não pagas separadas:'.print_r($invoices,1));
    
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

                logCielo('Checando -> '."{$item['relid']} ==  {$service_id}");
                if($item['relid'] ==  $domainid)
                {
                	die("Encontrada a invoice baixando ".$invoice['id']);
                	logCielo('Invoice dado:'.print_r($invoiceAtual,1));
                    cieloSetPagamento($invoice['id'],$valorPago,$data_pagamento,'cielorecorrente');
                }
            }
            


    }

  
    die('Invoice não paga não foi localizada. Aplicado crédito:'.print_r($invoice,1));


    logCielo('Invoice não paga não foi localizada. Aplicado crédito:'.print_r($invoice,1));
    
    $command = 'AddCredit';
    $postData = array(
        'clientid' => $produto['clientid'],
        'description' => 'Créditos Adicionados por pagamento Recorrente Via Cartão de Crédito',
        'amount' => $valorPago
    );
    $adminUsername = 'deljdl';

    $xml = localAPI($command, $postData, $adminUsername);

    $status = $xml['status'];

    if($status == 'error')
    {
        die(logCielo('Erro na adção de créditos:'.$xml['message']));//function logCielo($dados = '')
    }
    else
    {
        $command = 'SendEmail';
        $values=$vars;
        $values = array('messagename' => 'credito adicionado', 'id' => $produto['clientid']); 
        $adminuser = 'deljdl';
        $results = localAPI($command,$values,$adminuser);
        
        die(logCielo('Créditos adicionados ok. | Valor: -> '.$valorPago.' | Userid->'.$produto['clientid'].' | Result->'.$xml["result"].' | Message->'.$xml["message"]));
    }


}



die(baixaRecorrenteDominioViaCielo(95, 1, 0));
  