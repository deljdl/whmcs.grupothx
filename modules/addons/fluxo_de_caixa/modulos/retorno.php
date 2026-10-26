<? $dataInicial = '2014-08-01';
$selectDatas ='<option value="Geral">Geral</option>';


while(strtotime($dataInicial) < strtotime(date('Y-m-d')))
{
	$dataInicial = date('Y-m-d', strtotime("+1 month", strtotime($dataInicial)));
	$dataFormat = date('m-Y',strtotime($dataInicial));
	$selectDatas.='<option value="'.$dataFormat.'">'.$dataFormat.'</option>';
	$i++;
}

echo '
	<div class="row">
		<form action="" method="post" enctype="multipart/form-data">
			<div class="col-md-8">
				<label class="form-control"><span id="ffile">Clique e Escolha o Arquivo Retorno</span> <input onchange="fakeFile(this.value)" style="display: none;" type="file" name="arquivo_retorno" required>
				</label>
			</div>
			<div class="col-md-2">
				<label><input type="checkbox" class="btn btn-primary" name="modedebug" value="1">Mode Debug ?</label>
			</div>
			<div class="col-md-2">
				<input type="submit" class="btn btn-primary" value="Enviar Arquivo">
			</div>
		</form>
		<script>
		function fakeFile(v)
		{
			if(v == "")
			{
				v = "Clique e Escolha o Arquivo Retorno";
			}
			document.getElementById("ffile").innerHTML = v;
		}
		</script>
</div>
<div class="row">
	<div class="col-md-12">';

if($_FILES)
{
	# Required File Includes
	include("../../../dbconnect.php");
	include("../../../includes/functions.php");
	include("../../../includes/gatewayfunctions.php");
	include("../../../includes/invoicefunctions.php");
	require_once($_SERVER['DOCUMENT_ROOT'].'/thx/tarifasTHX.php');


	function logBoleto(
		$pag_valor,
		$pag_pago,
		$pag_invoice,
		$pag_carteira,
		$pag_taxa,


		$pag_cliente,
		$pag_mensagem,
		$pag_status,
		$pag_debug,


		$pag_arquivo,
		$pag_linha,
		$pag_gateway)
	{
		$sql = "INSERT INTO pagamentos (
		pag_valor,
		pag_pago,
		pag_invoice,
		pag_carteira,
		pag_taxa,

		pag_cliente,
		pag_mensagem,
		pag_status,
		pag_debug,




		pag_arquivo,
		pag_linha,
		pag_gateway)
			VALUES (
		'{$pag_valor}',
		'{$pag_pago}',
		'{$pag_invoice}',
		'{$pag_carteira}',
		'{$pag_taxa}',

		'{$pag_cliente}',
		'{$pag_mensagem}',
		'{$pag_status}',
		'{$pag_debug}',


		'{$pag_arquivo}',
		'{$pag_linha}',
		'{$pag_gateway}')";

		if(isset($_POST['modedebug']))
		{
			retorno('SQL: '.$sql);
			return;
		}
		if(mysql_query($sql))
		{
			retorno("Salva em Banco");
		}
		else
		{
			retorno("Erro ao Salvar em Banco", 'danger');
		}

	}

	function retorno($msg, $tipo = 'success')
	{
		echo '
		<div class="row">
			<div class="alert alert-'.$tipo.'">
			  '.$msg.'
			</div>
		</div>';
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

	function baixaViaFrancesinha($invoiceId, $valorPago, $gateway)
	{
		$command = "getinvoice";
	 	$adminuser = $login_whmcs;
	 	$values["invoiceid"] = $invoiceId;

		$xml = localAPI($command,$values,$adminuser);

		$status = $xml['status'];

		if($status == 'error')
		{
			retorno("Erro ao baixar invoice {$invoiceId} com o valor {$valorPago}  e gateway {$gateway[1]}", 'danger');
			return array(0, "Erro","danger", "{$xml['message']}");
		}

		if($xml['status'] != 'Paid')
		{
			retorno(file_get_contents('http://'.$_SERVER['HTTP_HOST'].'/pagamento/methodpaymentUpdate.php?i='.$invoiceId.'&gtw='.$gateway[7]), 'info');
			if(isset($_POST['modedebug']))
			{
				retorno("Invoice {$invoiceId} será baixada com o valor {$valorPago}  com taxa de {$gateway[2]}");
				return array(0,"Baixa",'Não disponível no mode debug', 'Não disponível no mode debug');
			}
			$command = "addinvoicepayment";
			$adminuser = $login_whmcs;
			$values["invoiceid"] = $invoiceId;
			$values["transid"] = $transacaoId;
			$values["amount"] = $valorPago;
			$values["fees"] = $taxa;
			$values["date"] = date('Y-m-d G:i:s');
			$values["gateway"] = $gateway[7];
			$values["noemail"] = false;
			$results = localAPI($command,$values,$adminuser);

			retorno("Invoice {$invoiceId} baixada com o valor {$valorPago}  pelo gateway {$gateway[1]}");

			return array($xml['userid'],"Baixa",$results["result"], $results["message"]);


		}
		else
		{
			retorno("Invoice {$invoiceId} com o valor {$valorPago}  com gateway {$gateway[7]} já foi baixada anteriormente", 'error');
			return array($xml['userid'],"Ignorado","info", "Baixado Anteriormente");
		}


	}

	$arquivoretorno = $_SERVER['DOCUMENT_ROOT'].'/thx/modules/addons/fluxo_de_caixa/ReToRnOs-ItAu/'.$_FILES['arquivo_retorno']['name'];

	if(file_exists($arquivoretorno))
	{
		retorno($_FILES['arquivo_retorno']['name'].' ja foi processado anteriormente.<br /><br />', 'danger');
	}
	else
	{

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

						$tipopag = 'boleto_itau_sem_registro';
						$carteira = (int)substr($linha,75,10);
						if($carteira == 176)
						{
							$invoiceId = substr($invoiceId, 2);
							$tipopag = 'itaushopline';
						}
						$valorPago = (int)substr($linha,152,13);
						$valorPago = $valorPago/100;
						$valorRealPago = number_format($valorPago, 2, '.', '');
						$valorPago = getInvoiceValor($invoiceId, $valorPago);
						$valorPago = number_format($valorPago, 2, '.', '');





						$retorno = baixaViaFrancesinha($invoiceId, $valorPago, $formasdepagamento[$tipopag]);



						logBoleto(
							$valorPago,
							$valorRealPago,
							$invoiceId,
							$carteira,
							$formasdepagamento[$tipopag][2],


							$retorno[0],
							$retorno[1],
							$retorno[2],
							$retorno[3],

							$_FILES['arquivo_retorno']['name'],
							$linha_lida,
							$tipopag);




					}
				}

			}
			fclose($ponteiro);
			if(isset($_POST['modedebug']))
			{
				unlink($arquivoretorno);
			}
		}
		else
		{
			retorno(__FUNCTION__."Erro ao salvar arquivo", 'danger');
		}
	}
}
//======================================BAIXAS

$baixas = '';

		$results = mysql_query("SELECT *  FROM pagamentos
								WHERE MONTH(pag_data) = {$_SESSION['Xmes']} AND YEAR(pag_data) = {$_SESSION['Xano']} ORDER BY pag_data");




		 while($linha = mysql_fetch_array($results))
		{
			$baixas .= '
			<tr class="'.$linha['pag_status'].'">
				<td>'.$linha['pag_id'].'</td>
				<td>'.strftime('%d/%m/%Y às %H:%M', strtotime($linha['pag_data'])).'</td>
				<td><a target="_blank" href="'.HTTP_WHMCS_ADMIN.'clientssummary.php?userid='.$linha['pag_cliente'].'">'.$linha['pag_cliente'].'</a></td>
				<td><a target="_blank" href="'.HTTP_WHMCS_ADMIN.'invoices.php?action=edit&id='.$linha['pag_invoice'].'">'.$linha['pag_invoice'].'</a></td>
				<td>R$ '.$linha['pag_valor'].'</td>
				<td>R$ '.$linha['pag_pago'].'</td>
				<td>'.$linha['pag_carteira'].'</td>
				<td>R$ '.$linha['pag_taxa'].'</td>
				<td><a target="_blank" href="'.HTTP_WHMCS_ADMIN.'modules/addons/fluxo_de_caixa/ReToRnOs-ItAu/'.$linha['pag_arquivo'].'">'.$linha['pag_arquivo'].'</a></td>
				<td>'.$linha['pag_linha'].'</td>
				<td>'.$linha['pag_gateway'].'</td>
				<td>'.$linha['pag_mensagem'].'</td>
				<td>'.$linha['pag_debug'].'</td>
			</tr>';
		}



?>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<h2>Log</h2>
		<table class="table table-hover">
			<thead>
				<tr>
					<th>Id</th>
					<th>Data</th>
					<th>Cliente</th>
					<th>Invoice</th>
					<th>Valor</th>
					<th>Valor Pago</th>
					<th>Carteira</th>
					<th>Taxa</th>
					<th>Arq. Retorno</th>
					<th>Linha</th>
					<th>Gateway</th>
					<th>Ação</th>
					<th>Debug</th>
				</tr>
			</thead>
		<tbody>
			<?=$baixas?>
		</tbody>
		</table>
	</div>
</div>
