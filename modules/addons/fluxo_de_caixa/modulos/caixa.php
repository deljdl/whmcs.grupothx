<?

		function getParcelasRestantes($dataFinal, $parcelas)
		{

			$date1 = new DateTime();
			$date2 = new DateTime($dataFinal);
			//Repare que inverto a ordem, assim terei a subtração da ultima data pela primeira.
			//Calculando a diferença entre os meses
			$meses = ((int)$date2->format('m') - (int)$date1->format('m'))
			//    e somando com a diferença de anos multiplacado por 12
				+ (((int)$date2->format('y') - (int)$date1->format('y')) * 12);

			return ($parcelas-$meses).'/'.$parcelas;//2
		}


		$formasdepagamento = array(
		'deposito_ou_transferencia' => array('D','Depósito ou Transferência', 0,0, 0, 'N', 'success','deposito_ou_transferencia'),
		'gofasgerencianetboleto' => array('G','Gerencianet Boleto',TAXA_GERENCIANET, 0, 0, 'G', 'danger', 'gofasgerencianetboleto')
		);


		$caixain = $caixaout = '';

		$caixaout.='
		<form method="post">
					<tr>
						<td colspan="1">
							<input type="text" class="form-control" placeholder="Descrição" required name="descricao" required>
						</td>
						<td colspan="1">
							<input type="text" class="form-control" required placeholder="valor" name="valor" required>
						</td>
						<td colspan="1">
							<input type="date" class="form-control" required placeholder="vencimento" name="vencimento" required>
						</td>
						<td>
							<input type="submit" class="btn btn-primary"   name="add_divida" value="Nova Divida">
						</td>
					</tr>
		</form>';
		if(isset($_GET['pagou']))
		{
			$dataPagamento = $_GET['pagou'] == 1 ? "NOW()" : "'0000-00-00 00:00:00'";
			$pagar = "UPDATE dividas SET divida_paga = {$_GET['pagou']}, divida_data_pagamento = {$dataPagamento} WHERE divida_id =".$_GET['divida_id'];
			mysql_query($pagar);
		}

		if(isset($_GET['apagou']))
		{
			$pagar = "DELETE FROM dividas WHERE divida_id =".$_GET['apagou'];
			mysql_query($pagar);
		}

		if(isset($_GET['valor_divida']))
		{
			if($_GET['campo'] == 'divida_vencimento')
			{
				//echo($_GET['valor_divida']);
				$_GET['valor_divida'] = explode('/',$_GET['valor_divida']);
				$_GET['valor_divida'] = $_GET['valor_divida'][2].'-'.$_GET['valor_divida'][1].'-'.$_GET['valor_divida'][0];
				//die($_GET['valor_divida']);
			}
			$_GET['valor_divida'] = urldecode($_GET['valor_divida']);
			$pagar = "UPDATE dividas SET {$_GET['campo']} = '{$_GET['valor_divida']}' WHERE divida_id =".$_GET['divida_id'];
			//die($pagar);
			mysql_query($pagar);
		}

		if(isset($_POST['add_divida']))
		{
			$add = "INSERT INTO dividas (divida_descricao, divida_valor, divida_paga, divida_vencimento, divida_ano, divida_mes) VALUES
                ('{$_POST['descricao']}','{$_POST['valor']}', 0, '".implode('-',array_reverse(explode('/', $_POST['vencimento'])))."', {$_SESSION['Xano']}, {$_SESSION['Xmes']})";
			//die($pagar);
			mysql_query($add) or die('Erro ad add divida<hr />'.mysql_error());
		}


		//http://tihospedagem.com.br/thx/addonmodules.php?module=aktuel_sms&tab=getProdsAtivos&valor_divida=170.00&divida_id=1&campo=divida_valor








		$total = mysql_fetch_assoc(mysql_query("SELECT SUM(total) as total  FROM tblinvoices WHERE MONTH(datepaid) = {$_SESSION['Xmes']} AND YEAR(datepaid) = {$_SESSION['Xano']} AND status = 'paid'"));



		 $retorno ='';
		 $entrada = $saida = $cobrancas = 0;
		 $results = mysql_query("SELECT i.*,c.firstname, c.companyname, i.invoicenum, date_format(i.duedate, '%d/%m/%Y') AS vencimento, date_format(i.datepaid, '%d/%m/%Y') AS pagamento, i.status, i.total  FROM tblinvoices i
									INNER JOIN tblclients c ON c.id = i.userid
										WHERE MONTH(i.datepaid) = {$_SESSION['Xmes']} AND YEAR(i.datepaid) = {$_SESSION['Xano']} AND i.status = 'paid' AND total > 0
											ORDER BY i.datepaid");


		 $aha = 0;

		 while($linha = mysql_fetch_array($results))
		{
			// if($aha <=0)
			// echo '<h1>linha</h1><pre>'.print_r($linha,1).'</pre>';
			// $aha++;
			//echo '<br /><pre>'.print_r($linha,1).'</pre><br />';
			$caixain .= '
			<tr class="default">
				<td style="line-height: 15px;">'.$linha['firstname'].'<br /><small style="font-size: 50%;">'.$linha['companyname'].'</small></td>
				<td><a target="_blank" href="'.HTTP_WHMCS_ADMIN.'invoices.php?action=edit&id='.$linha['id'].'">'.$linha['id'].'</a></td>
				<td>'.$linha['vencimento'].'</td>
				<td>'.$linha['pagamento'].'</td>
				<td style="white-space: nowrap;"> R$ '.$linha['total'].'</td>
				<td><span class="label label-'.$formasdepagamento[$linha['paymentmethod']][6].'" style="float: right;">'.$formasdepagamento[$linha['paymentmethod']][1].'</span></td>
			</tr>';
			$entrada += $linha['total'];
			$cobrancas ++;
			$formasdepagamento[$linha['paymentmethod']][3] ++;
			$formasdepagamento[$linha['paymentmethod']][4] += $linha['total'];


		}


		$dividas = mysql_query("SELECT * FROM dividas WHERE divida_ano={$_SESSION['Xano']} AND divida_mes={$_SESSION['Xmes']} ORDER BY divida_paga,divida_vencimento");
        if(! mysql_num_rows($dividas))
        {
			$sql= "
			INSERT INTO dividas (divida_descricao, divida_valor, divida_paga, divida_vencimento, divida_ano, divida_mes) VALUES
			('VPS Standard - grupothx.com.br', '204.89', 0, '{$_SESSION['Xano']}-{$_SESSION['Xmes']}-01', {$_SESSION['Xano']}, {$_SESSION['Xmes']}),
			('Servidor Dedicado Black - tihospedagem.com', '707.79', 0, '{$_SESSION['Xano']}-{$_SESSION['Xmes']}-21', {$_SESSION['Xano']}, {$_SESSION['Xmes']}),
			('VPS Platinum - lojagavioes.com.br', '316.24', 0, '{$_SESSION['Xano']}-{$_SESSION['Xmes']}-03', {$_SESSION['Xano']}, {$_SESSION['Xmes']}),
			('Servidor EVEO - server2.thxhost.com', '1069.00', 0, '{$_SESSION['Xano']}-{$_SESSION['Xmes']}-13', {$_SESSION['Xano']}, {$_SESSION['Xmes']}),
			('Servidor VPS KingHost - vps1.thxhost.com', '47.90', 0, '{$_SESSION['Xano']}-{$_SESSION['Xmes']}-25', {$_SESSION['Xano']}, {$_SESSION['Xmes']}),
			

			('Licença cPanel', '375.00', 0, '{$_SESSION['Xano']}-{$_SESSION['Xmes']}-17', {$_SESSION['Xano']}, {$_SESSION['Xmes']}),
			('API WhatsApp', '100.00', 0, '{$_SESSION['Xano']}-{$_SESSION['Xmes']}-28', {$_SESSION['Xano']}, {$_SESSION['Xmes']}),
			('NitroNews SBI', '119.00', 0, '{$_SESSION['Xano']}-{$_SESSION['Xmes']}-15', {$_SESSION['Xano']}, {$_SESSION['Xmes']}),


			('Google Workspace ricoolog.com', '274.50', 0, '{$_SESSION['Xano']}-{$_SESSION['Xmes']}-01', {$_SESSION['Xano']}, {$_SESSION['Xmes']}),


			('Contador', '325.50', 0, '{$_SESSION['Xano']}-{$_SESSION['Xmes']}-15', {$_SESSION['Xano']}, {$_SESSION['Xmes']}),
			('Das', '300.00', 0, '{$_SESSION['Xano']}-{$_SESSION['Xmes']}-20', {$_SESSION['Xano']}, {$_SESSION['Xmes']}),
			('GPS', '220.00', 0, '{$_SESSION['Xano']}-{$_SESSION['Xmes']}-20', {$_SESSION['Xano']}, {$_SESSION['Xmes']}),
	

			('Pensão Alimentícia Thayná', '405.25', 0, '{$_SESSION['Xano']}-{$_SESSION['Xmes']}-01', {$_SESSION['Xano']}, {$_SESSION['Xmes']}),
			('Pensão Alimentícia Alice', '405.25', 0, '{$_SESSION['Xano']}-{$_SESSION['Xmes']}-01', {$_SESSION['Xano']}, {$_SESSION['Xmes']}),
			('Convênio Hapvida Notredame', '387.32', 0, '{$_SESSION['Xano']}-{$_SESSION['Xmes']}-20', {$_SESSION['Xano']}, {$_SESSION['Xmes']}),

			('Condomínio Apartamento', '500.00', 0, '{$_SESSION['Xano']}-{$_SESSION['Xmes']}-10', {$_SESSION['Xano']}, {$_SESSION['Xmes']}),
			('Gás Apartamento', '60.00', 0, '{$_SESSION['Xano']}-{$_SESSION['Xmes']}-15', {$_SESSION['Xano']}, {$_SESSION['Xmes']}),
			('Luz Apartamento', '200.00', 0, '{$_SESSION['Xano']}-{$_SESSION['Xmes']}-8', {$_SESSION['Xano']}, {$_SESSION['Xmes']}),
			('IPTU do Apartamento', '77.00', 0, '{$_SESSION['Xano']}-{$_SESSION['Xmes']}-10', {$_SESSION['Xano']}, {$_SESSION['Xmes']}),
			('Claro - Telefone Fixo e Internet', '130.90', 0, '{$_SESSION['Xano']}-{$_SESSION['Xmes']}-20', {$_SESSION['Xano']}, {$_SESSION['Xmes']}),
			('Internet - RedFox', '99.99', 0, '{$_SESSION['Xano']}-{$_SESSION['Xmes']}-10', {$_SESSION['Xano']}, {$_SESSION['Xmes']}),

			('Cartão Efí', '100.00', 0, '{$_SESSION['Xano']}-{$_SESSION['Xmes']}-23', {$_SESSION['Xano']}, {$_SESSION['Xmes']}),

			('Cartão Mercado Pago', '300.00', 0, '{$_SESSION['Xano']}-{$_SESSION['Xmes']}-6', {$_SESSION['Xano']}, {$_SESSION['Xmes']}),


			('Investimentos - Meta R$ 1500.00', '0.00', 1, '{$_SESSION['Xano']}-{$_SESSION['Xmes']}-01', {$_SESSION['Xano']}, {$_SESSION['Xmes']}),
			('Claro - Telefone Móvel', '50.38', 0, '{$_SESSION['Xano']}-{$_SESSION['Xmes']}-15', {$_SESSION['Xano']}, {$_SESSION['Xmes']})
			";
            if(! mysql_query($sql))
            {
                die('Erro ao criar dívidas do mês<br />'.mysql_error());
            }

            $dividas = mysql_query("SELECT * FROM dividas WHERE divida_ano={$_SESSION['Xano']} AND divida_mes={$_SESSION['Xmes']}  ORDER BY divida_paga,divida_vencimento");
        }








		$pago = $emaberto = $dosjobs = 0;
		while($divida = mysql_fetch_array($dividas))
		{

			if($divida['divida_paga'] == 1)
			{
				$ck=' checked';
				$pago += $divida['divida_valor'];
				$class = 'success';
				$prazo = 'Paga em '.strftime('%d/%m/%Y às %H:%M', strtotime($divida['divida_data_pagamento']));
			}
			else
			{
				$emaberto += $divida['divida_valor'];
				$ck = '';
				$diferencadias = 0;
				if(strtotime(date('Y-m-d')) > strtotime($divida['divida_vencimento']))
				{
					$diferenca = strtotime(date('Y-m-d')) - strtotime($divida['divida_vencimento']);
					$diferencadias = floor($diferenca / (60 * 60 * 24));
					$class="danger";
					$prazo = 'Vencida a '.$diferencadias.' dias !!!';
				}
				elseif(strtotime(date('Y-m-d')) == strtotime($divida['divida_vencimento']))
				{
					$class="danger";
					$prazo = 'Vencimento é hoje !!!!';
				}
				else
				{

					$diferenca = strtotime($divida['divida_vencimento']) - strtotime(date('Y-m-d'));
					$diferencadias = floor($diferenca / (60 * 60 * 24));
					if($diferencadias < 6)
					{
						$class="info";
						$prazo = 'Vencimento em '.$diferencadias.' dias !!!!';
					}
					else
					{
						$class="default";
						$prazo = 'Vencimento em '.$diferencadias.' dias !!!!';
					}
				}




			}
			$paga = '<td><span class="label label-'.$class.'" onclick="pagaDivida('.$divida['divida_id'].', '.$divida['divida_paga'].')" style="cursor:pointer;">'.($divida['divida_paga'] == 1 ? 'Paga' : 'Pagar').'</span> <button class="btn btn-danger" style="float: right;padding: 1px 7px;" onClick="javascript:deleteDivida('.$divida['divida_id'].')">x</button></td>';
			$caixaout .= '<tr class="'.$class.'"><td style="line-height: 15px;"><span contenteditable="true" ondblclick="setEdicaoEmLinha(this, \'divida_descricao\', '.$divida['divida_id'].')">'.$divida['divida_descricao'].'</span><br /><br /><small class="label label-'.$class.'" style="font-size: 70%;">'.$prazo.'</small></td><td> R$ <span contenteditable="true" ondblclick="setEdicaoEmLinha(this, \'divida_valor\', '.$divida['divida_id'].')">'.$divida['divida_valor'].'</span></td><td><span contenteditable="true" ondblclick="setEdicaoEmLinha(this, \'divida_vencimento\', '.$divida['divida_id'].')">'.strftime('%d/%m/%Y', strtotime($divida['divida_vencimento'])).'</span></td>'.$paga.'</tr>';
			$saida += $divida['divida_valor'];

		}




		$caixaout .= '
		<tr><th colspan="4"><hr /></th></tr>
		<tr><th class="danger" colspan="4"><h1 style="font-weight: bold;font-size: 27px;color: red;">Jobs do mês</h1></th></tr>';


		$pjs = mysql_query("SELECT * FROM pjs ORDER BY pj_id");

		$totalPagoAllPjs = 0;
		while($pj = mysql_fetch_array($pjs))
		{
			$caixaDoPj = '<tr><th colspan="4"><h1>'.$pj['pj_nome'].'</h1></th></tr>';

			$jobs = mysql_query("SELECT * FROM jobs
									WHERE YEAR(job_data_pagamento) = {$_SESSION['Xano']}
										AND MONTH(job_data_pagamento) = {$_SESSION['Xmes']}
											AND job_pj = {$pj['pj_id']}
												ORDER BY job_data_pagamento");

			$pjlinha = false;
			$totalPago = 0;
			while($job = mysql_fetch_array($jobs))
			{
				$totalPago += $job['job_valor'];
				if($pjlinha != $job['pj_id'])
				{
					$caixaDoPj .= '<tr><th colspan="4">'.$job['pj_nome'].'</th></tr>';
					$pjlinha = $job['pj_id'];
				}
				$caixaDoPj .= '<tr class="success"><td style="line-height: 15px;">'.$job['job_job'].'<br /><br /><small class="label label-'.$class.'" style="font-size: 70%;">'.$job['pj_nome'].'</small></td><td> R$ '.$job['job_valor'].'</td><td>'.strftime('%d/%m/%Y', strtotime($job['job_data_pagamento'])).'</td><td></td></tr>';

				$pago += $job['job_valor'];

				$dosjobs += $job['job_valor'];
			}





			$caixaDoPj .= '<tr><th colspan="4">Total Pago R$ '.number_format($totalPago,2,'.','').'<br /><br /><br /></th></tr>';

			if($totalPago > 0)
			{
				$totalPagoAllPjs += $totalPago;
				$caixaout.=$caixaDoPj;
			}
		}

		$caixaout .= '<tr><th class="danger" colspan="4"><h1 style="font-weight: bold;font-size: 27px;color: red;">Total Pago PJ\'s R$ '.number_format($totalPagoAllPjs,2,'.','').'</h1></th></tr>';


		$gastoBoletos = $boletos * TAXA_BOLETO_THX;

		//$formasdepagamento[$linha['paymentmethod']][3] ++;
		//$formasdepagamento[$linha['paymentmethod']][4] += $linha['total'];
		//'deposito_ou_transferencia' => array('D','Depósito ou Transferência', 0,0, 0),
		//'deposito_ou_transferencia' => array('D','Depósito ou Transferência', 0,0, 0, 'N'),
		//'boleto_itau_sem_registro' => array('B','Boleto S/Registro', 4.50, 0, 0, 'F'),
		//'cielo' => array('C','Cielo',4, 0, 0, 'P')




		$taxas='';
		//echo '<h1>formasdepagamento</h1><pre>'.print_r($formasdepagamento,1).'</pre>';
		foreach ($formasdepagamento as $formP)
		{
			//echo '<h1>formP</h1><pre>'.print_r($formP,1).'</pre>';
			//echo '<h1>formasdepagamentodentro</h1><pre>'.print_r($formasdepagamento,1).'</pre>';
			$totalG = 0;
			if($formP[5] == 'G')
			{
				$totalG  = TAXA_GERENCIANET * $formP[3];

			}
			elseif($formP[5] == 'F')
			{
				$totalG = $formP[2] * $formP[3];
			}
			elseif($formP[5] == 'N')
			{
				$totalG  = TAXA_TRANSFERENCIA_DEPOSITO;

			}

			$saida += $totalG;

			$taxas.='<tr class="'.$formP[6].'"><th>'.$formP[1].'</th><th>'.$formP[3].'</th><th>R$ '.$formP[4].'</th><th> R$ '.number_format($totalG,2,'.','').'</th></tr>';
		}

		$sqlProvisao = "
		SELECT
			SUM(h.amount) AS total_amount
		FROM
			tblhosting h
		JOIN
			tblproducts p ON h.packageid = p.id
		JOIN
			tblinvoiceitems ii ON ii.relid = h.id AND ii.type = 'Hosting'
		JOIN
			tblinvoices i ON ii.invoiceid = i.id
		WHERE
			MONTH(i.duedate) = {$_SESSION['Xmes']}
			AND YEAR(i.duedate) = {$_SESSION['Xano']}

		";

		$provisaoResult = mysql_query($sqlProvisao);
		$totalProvisioned = 0;
		if ($provisaoResult) {
			$row = mysql_fetch_assoc($provisaoResult);
			$totalProvisioned = $row['total_amount'];
		}





				$pago =  number_format($pago,2,'.','');
				$saldo = $entrada - $saida;
				$devendo = $saldo <=0 ? 'danger' : 'success';

				$saldo-=VALOR_EMPRESA;
				$saida+=VALOR_EMPRESA;

		$STHX = '
		<div class="row '.$devendo.'">
			<div class="col-md-12">
				<a href="https://grupothx.com/thx/admin/addonmodules.php?module=fluxo_de_caixa&tab=receita-mensal-estimada" class="btn btn-default" style="background: yellow;font-weight: bold;"type="button">
	              Receita Mensal Estimada <span class="badge">R$ '.number_format($totalProvisioned,2,',','.').'</span>
	            </a>

				<button class="btn btn-primary" type="button">
	              Cobranças <span class="badge"> '.$cobrancas.'</span>
	            </button>
				<button class="btn btn-primary" type="button">
	              Entrada <span class="badge">R$ '.number_format($entrada,2,'.','').'</span>
	            </button>

	            <button class="btn btn-danger" type="button">
	              Saída <span class="badge">R$ '.number_format($saida,2,'.','').'</span>
	            </button>

	            <button class="btn btn-info" type="button">
	              Job\'s <span class="badge">R$ '.number_format($dosjobs,2,'.','').'</span>
	            </button>

	            <button class="btn btn-success" type="button">
	              Pago <span class="badge">R$ '.number_format($pago,2,'.','').'</span>
	            </button>

				<button class="btn btn-danger" type="button">
	              Em Aberto <span class="badge">R$ '.number_format($emaberto,2,'.','').'</span>
	            </button>

				<button class="btn btn-'.$devendo.'" type="button">
	              Saldo do Mês <span class="badge">R$ '.number_format($saldo,2,'.','').'</span>
	            </button>
			</div>
		</div>';

		echo $STHX.'<span class="label label-danger" style="float: right;">To THX '.VALOR_EMPRESA.'</span>';



		$dataatualizacao = '
		<div class="row '.$devendo.'">
		<div class="col-md-12">
		<h3>'.$_SESSION['Xmes'].'-'.$_SESSION['Xano'].'</h3>
		<p>
		Ultima atualização em '.date('d/m/Y à\s G:i:s').'
		</p>
		</div>
		</div>';

		$abre = fopen($_SERVER['DOCUMENT_ROOT']."/saldoTHXmes-{$_SESSION['Xmes']}-{$_SESSION['Xano']}.html", "w+");
		$escreve = fwrite($abre, $dataatualizacao.$STHX);
		fclose($abre);


		echo "
		<script>
		function pagaDivida(id, paga)
		{
			paga = paga == 1 ? 0 : 1;
			url = '{$path}caixa&pagou='+paga+'&divida_id='+id;
			location = url;
		}
		function setEdicaoEmLinha(el,campo,id)
		{
			if(! confirm('Editar ????'))
			{
				return false;
			}
			link = '{$path}caixa&valor_divida='+el.innerHTML+'&divida_id='+id+'&campo='+campo;
			location = link;
		}
		function deleteDivida(id)
		{
			if(! confirm('Excluir ????'))
			{
				return false;
			}
			url = '{$path}caixa&apagou='+id;
			location = url;
		}

		</script>";
?>

<div class="row">
	<div class="col-md-12 nopadding">
		<h2><br /><br /></h2>
		<table class="table table-hover">
			<thead>
				<tr class="info"><th colspan="4">Taxas</th></tr>
				<tr class="default"><th>GATEWAY</th><th>Qtd</th><th>Total</th><th>Taxa</th></tr>
			</thead>
		<tbody>
			<?=$taxas?>
		</tbody>
		</table>
	</div>
	<div class="col-md-6 nopadding" style="border-right: solid #1a4d80 1px;">
		<h2>Entradas</h2>
		<table class="table table-hover">
			<thead>
				<tr>
					<th>Cliente</th>
					<th>Invoice</th>
					<th>Vencimento</th>
					<th>Data Baixa</th>
					<th>Valor</th>
					<th></th>
				</tr>
			</thead>
		<tbody>
			<?=$caixain?>
		</tbody>
		</table>
	</div>
	<div class="col-md-6 nopadding" style="">
		<h2>Saídas</h2>
		<table class="table table-hover">
		<thead>
		<tr>
		<th>Dívida</th>
		<th>Valor</th>
		<th>Vencimento</th>
		<th></th>
		</tr>
		</thead>
		<tbody>
			<?=$caixaout?>
		</tbody>
		</table>
	</div>
</div>