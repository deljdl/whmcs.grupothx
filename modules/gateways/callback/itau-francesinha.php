<?php
# Required File Includes
include("../../../dbconnect.php"); 
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/tarifasTHX.php');


$novaBaixa = false;
if(isset($_POST['token']) && $_POST['token'] == 'r76247wd')
{
	$novaBaixa=true;	
	$_GET['arquivoData'] = date('m-Y');
}
else
{
	$_GET['arquivoData'] = isset($_GET['arquivoData'][3]) ? $_GET['arquivoData'] : date('m-Y');	
}
define('ARQUIVO', 'ReToRnOs-ItAu/logItau-'.$_GET['arquivoData'].'.php');
$abre = fopen(ARQUIVO, 'a');
fclose($abre);


function logItau($dados, $cabecalho=false)
{
	$quebra = chr(13).chr(10);
	
	if($cabecalho)
		$dados = $dados.$quebra.'<tr><th>Carteira</th><th>InvoiceId</th><th>Valor Pago</th><th>Taxa Banco</th><th>Data Pagamento</th><th>ClientId</th><th>Acao</th><th>Mensagem</th><tr>';
		
		
	$abre = fopen(ARQUIVO, 'a');
	$escreve = fwrite($abre, $quebra.$dados);
	fclose($abre);
	
	$abre = fopen('ReToRnOs-ItAu/logItau-Geral.php', 'a');
	$escreve = fwrite($abre, $quebra.$dados);
	fclose($abre);
}		

function getInvoiceValor($invoiceId, $valorPago)
{
	 $prateste = $valorPago;
	 $command = "getinvoice";
	 $adminuser = "deljdl";
	 $values["invoiceid"] = $invoiceId;		 
	 $results = localAPI($command,$values,$adminuser);

	if($valorPago > $results['total'])
	{
		$valorPago = $results['total'];
	}
	 return $valorPago;
}

function baixaViaFrancesinha($invoiceId, $valorPago, $data_pagamento)
{

	$command = "getinvoice";
 	$adminuser = $login_whmcs;
 	$values["invoiceid"] = $invoiceId;
 
	$xml = localAPI($command,$values,$adminuser);
	
	$status = $xml['status'];
	
	if($status == 'error')
		return "</td><td>erro</td><td>{$xml['message']}";
	
	if($xml['status'] != 'Paid')
	{
		
		
		$command = "addinvoicepayment";
		$adminuser = $login_whmcs;
		$values["invoiceid"] = $invoiceId;
		$values["transid"] = $transacaoId;
		$values["amount"] = $valorPago;
		$values["fees"] = TAXA_BOLETO_THX;
		$values["date"] = $data_pagamento;
		$values["gateway"] = 'boleto_itau_sem_registro';
		$values["noemail"] = false;	
		$results = localAPI($command,$values,$adminuser);	
		
				
		return $xml['userid'].'</td><td>Baixa</td><td>'.$results["result"].'</td><td>'.$results["message"];
	
	
	}
	else
		return $xml['userid'].'</td><td>Ignorado</td><td>Baixado Anteriormente';
	

}


?>
<script>
if(location.href==parent.location) {

top.location='//tihospedagem.com.br/thx/addonmodules.php?module=aktuel_sms&tab=francesinha';

}
</script>

<?

if($novaBaixa)
{
	$arquivoretorno = 'ReToRnOs-ItAu/'.$_FILES['arquivo_retorno']['name'];
	
	if(file_exists($arquivoretorno))
		echo ($_FILES['arquivo_retorno']['name'].' ja foi processado anteriormente.<br /><br />');
	else
	{
		logItau('<tr><th colspan="8">Arquivo '.$_FILES['arquivo_retorno']['name']. ' enviado em '.date('d/m/Y G:i:s').'</th></tr>', true);
		
		
		if(move_uploaded_file($_FILES['arquivo_retorno']['tmp_name'],$arquivoretorno))
		{
		
			$ponteiro = fopen ($arquivoretorno, "r");
			$i=0;
			$linha_lida=0;
			$quebra = '<br>';
			
					
			while (!feof ($ponteiro)) 
			{
				
				$linha_lida++;
				$linha = fgets($ponteiro, 4096);
				{
					
					$invoiceId=(int)substr($linha,85,8);
					
					
						
					
					
					if($invoiceId > 0)//190
					{
						
						
						
						$carteira = (int)substr($linha,75,10);					
						$valorPago = (int)substr($linha,152,13);
						$valorPago = $valorPago/100;
						$valorRealPago = number_format($valorPago, 2, '.', '');	
						$valorPago = getInvoiceValor($invoiceId, $valorPago);						
						$valorPago = number_format($valorPago, 2, '.', '');					
						
						
						
						$data_pagamento = date('Y-m-d 8:00', strtotime('-3 days'));
						$retorno = baixaViaFrancesinha($invoiceId, $valorPago, $data_pagamento);
						
							
							logItau('<tr><td>'.$carteira.'</td><td><a href="//tihospedagem.com.br/thx/invoices.php?action=edit&id='.$invoiceId.'" target="_blank">'.$invoiceId.'</a></td><td>'.$valorRealPago.'</td><td>'.TAXA_BOLETO_THX.'</td><td>'.$data_pagamento.'</td><td>'.$retorno.'</td><tr>');
					}
				}
				
			}
			fclose($ponteiro);
		}
	}
		
		
		
		
		
}	

?>
<style>
*{
	margin:0;
	padding:0;
	font-family:Arial, Helvetica, sans-serif;
}
table{
	margin: 0 0 0 -16px;
	border: solid #CCC 1px;
	color: #252323;
	width: 106%;
	border-collapse: collapse;
	/* border-radius: 5px; */
	border: none;
	color: #666;
	text-align:center;
}
table thead, table tfoot{
	font-size: 26px;	
}
table th{
	background-color: #1A4D80;
	padding: 10px;
	color: #FFF;
}
table tr{
	
}

table td{
	padding:10px;
}

fieldset{
	width: 900px;
	border-radius: 10px;
	margin: 10px auto;
	padding: 10px;
	border: solid #1A4D80 4px;
}
legend{
	color: #F00;
	font-size: 18px;
	margin: 10px;
	padding: 10px;
	font-weight: bold;
	border: solid #1A4D80 4px;
	border-radius: 10px;
}
a{
	color:#666;
	text-decoration:none;
}

a:hover{
	text-decoration:underline;	
}
a:visited{
	color:#F00;	
}

</style>
<textarea id="migra" style="    float: left;
    width: 96%;
    border: solid red 1px;
    margin: 25px 0px;
    padding: 10px;">INSERT INTO pagamentos (pag_carteira, pag_invoice, pag_pago,pag_taxa,pag_data,pag_cliente , pag_status, pag_mensagem, pag_arquivo) VALUES &#13;&#10;</textarea>

<textarea id="migra2" style="    float: left;
    width: 96%;
    border: solid red 1px;
    margin: 25px 0px;
    padding: 10px;"></textarea>
	

<fieldset>
	<legend><?=$_GET['arquivoData']?></legend>
    <table>
    <? require_once(ARQUIVO); ?>
    </table>
 </fieldset>
 <script src="//code.jquery.com/jquery-1.11.2.min.js"></script>
 <script>
 	var total_mes =0;
	var valor_linha = 0;
	var boletos = 0;
	var linhas = 0;
	var retorno;
	var arq = '';
 	 $('table tr').each(function (i, row) {
		 valor_linha = $(this).find("td").eq(2).html();
		  retorno = $(this).find("td").eq(7).html();

		if($(this).find('th').eq(1).html() == undefined && $(this).find('th').eq(0).html() != undefined)
		{
			arq = $(this).find('th').eq(0).html().substr(8,12);
		}
		if(valor_linha != undefined)
		{
			if(retorno == 'success')
			{
				total_mes += parseFloat(valor_linha);
				boletos++;
			}
			linhas ++;

			$('#migra').append("(\
				'"+$(this).find('td').eq(0).html()+"',\
				'"+$(this).find('td').eq(1).html()+"',\
				'"+$(this).find('td').eq(2).html()+"',\
				'"+$(this).find('td').eq(3).html()+"',\
				'"+$(this).find('td').eq(4).html()+"',\
				'"+$(this).find('td').eq(5).html()+"',\
				'"+$(this).find('td').eq(6).html()+"',\
				'"+$(this).find('td').eq(7).html()+"',\
				'"+arq+"'\
				),&#13;&#10;");
		}
			
		
		
    });
	var apend = '<tr><td colspan="8"> <strong>'+linhas+'</strong> Linhas - <strong>'+boletos+'</strong> Boletos baixados - Total <strong>R$ '+total_mes.toFixed(2)+'</strong> em <strong><?=$_GET['arquivoData']?></strong></td></tr>';
	$('table').append('<thead>'+apend+'</thead><tfoot>'+apend+'</tfoot>');
 </script>