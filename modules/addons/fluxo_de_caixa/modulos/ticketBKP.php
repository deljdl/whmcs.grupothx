<?
	$tihospedagem_com =  mysql_fetch_row (mysql_query("
			SELECT SUM(valor_mensal_equivalente) as total FROM (
				SELECT
		CASE
				WHEN billingcycle = 'Quarterly' THEN (amount/4)
			WHEN billingcycle = 'Annually' THEN (amount/12)
			WHEN billingcycle = 'Semi-Annually' THEN (amount/6)
			WHEN billingcycle = 'Biennially' THEN (amount/24)
			WHEN billingcycle = 'Triennially' THEN (amount/36)
				ELSE amount
		END as valor_mensal_equivalente

			FROM tblhosting WHERE domainstatus = 'Active' AND server = 8 AND billingcycle NOT IN ('One Time', 'Free Account')
				) as lista"));


				$tihospedagem_com_br =  mysql_fetch_row (mysql_query("
				SELECT SUM(valor_mensal_equivalente) as total FROM (
					SELECT
			CASE
					WHEN billingcycle = 'Quarterly' THEN (amount/4)
				WHEN billingcycle = 'Annually' THEN (amount/12)
				WHEN billingcycle = 'Semi-Annually' THEN (amount/6)
				WHEN billingcycle = 'Biennially' THEN (amount/24)
				WHEN billingcycle = 'Triennially' THEN (amount/36)
					ELSE amount
			END as valor_mensal_equivalente

				FROM tblhosting WHERE domainstatus = 'Active' AND server = 12 AND billingcycle NOT IN ('One Time', 'Free Account')
					) as lista"));


					$server1thxhostcom =  mysql_fetch_row (mysql_query("
				SELECT SUM(valor_mensal_equivalente) as total FROM (
					SELECT
			CASE
					WHEN billingcycle = 'Quarterly' THEN (amount/4)
				WHEN billingcycle = 'Annually' THEN (amount/12)
				WHEN billingcycle = 'Semi-Annually' THEN (amount/6)
				WHEN billingcycle = 'Biennially' THEN (amount/24)
				WHEN billingcycle = 'Triennially' THEN (amount/36)
					ELSE amount
			END as valor_mensal_equivalente

				FROM tblhosting WHERE domainstatus = 'Active' AND server = 14 AND billingcycle NOT IN ('One Time', 'Free Account')
					) as lista"));



					$outros_servicos =  mysql_fetch_row (mysql_query("
					SELECT SUM(valor_mensal_equivalente) as total FROM (
						SELECT
				  CASE
						WHEN billingcycle = 'Quarterly' THEN (amount/4)
					  WHEN billingcycle = 'Annually' THEN (amount/12)
					  WHEN billingcycle = 'Semi-Annually' THEN (amount/6)
					  WHEN billingcycle = 'Biennially' THEN (amount/24)
					  WHEN billingcycle = 'Triennially' THEN (amount/36)
						ELSE amount
				  END as valor_mensal_equivalente

					FROM tblhosting WHERE domainstatus = 'Active' AND server NOT IN (12,8) AND billingcycle NOT IN ('One Time', 'Free Account')
						) as lista"));


					$registrodominios = mysql_fetch_row (mysql_query("
						SELECT (COUNT(1) * 10 / 12) FROM tbldomains"));







			$total = ($tihospedagem_com[0] + $tihospedagem_com_br[0] + $server1thxhostcom[0] + $outros_servicos[0] + $registrodominios[0]);
			$ciclos = [
				'Monthly' => 'Mensal',
				'Quarterly' => 'Trimestral',
				'Semi-Annually' => 'Semestral',
				'Annually' => 'Anual',
				'Biennially' => 'Bianual',
				'Triennially' => 'Trianual',
				1 => 'Anual',
				2 => 'Bianual',
				3 => 'Trianual'
			];
			$detalhar = '';
			if(isset($_GET['detalhar']))
			{

					if($_GET['detalhar'] == 'tihospedagem.com')
					{
						$results = mysql_query("
							SELECT * FROM (
								SELECT *,
									CASE
											WHEN billingcycle = 'Quarterly' THEN (amount/4)
										WHEN billingcycle = 'Annually' THEN (amount/12)
										WHEN billingcycle = 'Semi-Annually' THEN (amount/6)
										WHEN billingcycle = 'Biennially' THEN (amount/24)
										WHEN billingcycle = 'Triennially' THEN (amount/36)
											ELSE amount
									END as valor_mensal_equivalente

									FROM tblhosting WHERE domainstatus = 'Active' AND server = 8 AND billingcycle NOT IN ('One Time', 'Free Account') ORDER by domain
								) as lista"
							);
					}
					elseif($_GET['detalhar'] == 'tihospedagem.com.br')
					{
						$results = mysql_query("
							SELECT * FROM (
								SELECT *,
									CASE
											WHEN billingcycle = 'Quarterly' THEN (amount/4)
										WHEN billingcycle = 'Annually' THEN (amount/12)
										WHEN billingcycle = 'Semi-Annually' THEN (amount/6)
										WHEN billingcycle = 'Biennially' THEN (amount/24)
										WHEN billingcycle = 'Triennially' THEN (amount/36)
											ELSE amount
									END as valor_mensal_equivalente

									FROM tblhosting WHERE domainstatus = 'Active' AND server = 12 AND billingcycle NOT IN ('One Time', 'Free Account') ORDER by domain
								) as lista"
							);
					}
					elseif($_GET['detalhar'] == 'server1.thxhost.com')
					{
						$results = mysql_query("
							SELECT * FROM (
								SELECT *,
									CASE
											WHEN billingcycle = 'Quarterly' THEN (amount/4)
										WHEN billingcycle = 'Annually' THEN (amount/12)
										WHEN billingcycle = 'Semi-Annually' THEN (amount/6)
										WHEN billingcycle = 'Biennially' THEN (amount/24)
										WHEN billingcycle = 'Triennially' THEN (amount/36)
											ELSE amount
									END as valor_mensal_equivalente

									FROM tblhosting WHERE domainstatus = 'Active' AND server = 14 AND billingcycle NOT IN ('One Time', 'Free Account') ORDER by domain
								) as lista"
							);
					}
					elseif($_GET['detalhar'] == 'outrosservicos')
					{
						$results = mysql_query("
							SELECT * FROM (
								SELECT *,
									CASE
											WHEN billingcycle = 'Quarterly' THEN (amount/4)
										WHEN billingcycle = 'Annually' THEN (amount/12)
										WHEN billingcycle = 'Semi-Annually' THEN (amount/6)
										WHEN billingcycle = 'Biennially' THEN (amount/24)
										WHEN billingcycle = 'Triennially' THEN (amount/36)
											ELSE amount
									END as valor_mensal_equivalente

									FROM tblhosting WHERE domainstatus = 'Active' AND server NOT IN (12,8) AND billingcycle NOT IN ('One Time', 'Free Account') ORDER by domain
								) as lista"
							);
					}
					elseif($_GET['detalhar'] == 'registrodominios')
					{
						$results = mysql_query("
						SELECT domain, registrationperiod as billingcycle, recurringamount as amount, 0.83 as	valor_mensal_equivalente
							FROM tbldomains"
						);
					}

						while($linha = mysql_fetch_array($results))
						{
							//echo '<pre>'.print_r($linha, 1).'</pre>';
							$detalhar.= '
							<tr>
								<td>'.$linha['domain'].'</td>
								<td>'.$ciclos[$linha['billingcycle']].'</td>
								<td>R$ '.$linha['amount'].'</td>
								<td>R$ '.number_format($linha['valor_mensal_equivalente'],2,'.','').'</td>
							</tr>';
						}

						$detalhar = '
							<div class="col-md-12 nopadding" >
								<table class="table table-hover">
									<thead>
										<tr>
											<th>Domínio</th>
											<th>Ciclo</th>
											<th>Valor</th>
											<th>Valor Eq Mensal</th>
										</tr>
									</thead>
								<tbody>
									'.$detalhar.'
								</tbody>
								</table>
							</div>';
			}



?>
<div class="row">
	<div class="col-md-12 nopadding" >
		<table class="table table-hover">
			<thead>
				<tr>
					<th>Serviço</th>
					<th>Ticket Médio Mensal</th>
					<th>Custo Médio Mensal</th>
					<th>Saldo Médio Mensal</th>
					<th></th>
				</tr>
			</thead>
		<tbody>
			<tr>
				<td>TiHospedagem COM</td>
				<td>R$ <?=number_format($tihospedagem_com[0],2,'.','')?></td>
				<td>R$ 795.19</td>
				<td>R$ <?=number_format(($tihospedagem_com[0] - 795.19),2,'.','')?></td>
				<td><a class="label label-danger"  href="addonmodules.php?module=fluxo_de_caixa&tab=ticket&detalhar=tihospedagem.com">Detalhar</a></td>

			</tr>

			<tr>
				<td>TiHospedagem BR</td>
				<td>R$ <?=number_format($tihospedagem_com_br[0],2,'.','')?></td>
				<td>R$ 795.19</td>
				<td>R$ <?=number_format(($tihospedagem_com_br[0]-795.19),2,'.','')?></td>
				<td><a class="label label-danger"  href="addonmodules.php?module=fluxo_de_caixa&tab=ticket&detalhar=tihospedagem.com.br">Detalhar</a></td>

			</tr>

			<tr>
				<td>Server1 THXHOST</td>
				<td>R$ <?=number_format($server1thxhostcom[0],2,'.','')?></td>
				<td>R$ 1072.89</td>
				<td>R$ <?=number_format(($server1thxhostcom[0] - 1072.89),2,'.','')?></td>
				<td><a class="label label-danger"  href="addonmodules.php?module=fluxo_de_caixa&tab=ticket&detalhar=server1.thxhost.com">Detalhar</a></td>

			</tr>

			<tr>
				<td>Registro Domínios</td>
				<td>R$ <?=number_format($registrodominios[0],2,'.','')?></td>
				<td>R$ 0.00</td>
				<td>R$ <?=number_format($registrodominios[0],2,'.','')?></td>
				<td><a class="label label-danger"  href="addonmodules.php?module=fluxo_de_caixa&tab=ticket&detalhar=registrodominios">Detalhar</a></td>

			</tr>
			<tr>
				<td>Outros Serviços</td>
				<td>R$ <?=number_format($outros_servicos[0],2,'.','')?></td>
				<td>R$ 0.00</td>
				<td>R$ <?=number_format($outros_servicos[0],2,'.','')?></td>
				<td><a class="label label-danger"  href="addonmodules.php?module=fluxo_de_caixa&tab=ticket&detalhar=outrosservicos">Detalhar</a></td>

			</tr>


			<tr>
					<th>Total</th>
					<th>R$ <?=number_format($total,2,'.','')?></th>
					<td>R$ 2663.27</td>
					<th>R$ <?=number_format(($total- 2663.27),2,'.','')?></th>
					<th></th>
				</tr>
		</tbody>
		</table>
	</div>
	<div class="col-md-12 nopadding">
	<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['Dia', 'TihospedagemCOM', 'TihospedagemBR', 'Server1ThxHost', 'Domínios', 'Outros'],
			 <?
				$results = mysql_query("SELECT *, DATE_FORMAT(grafico_hospedagens_data, '%d/%m/%y') as data_formatada  FROM grafico_hospedagens ORDER BY grafico_hospedagens_data DESC");
				while($linha = mysql_fetch_array($results)) {
			?>
          ['<?=$linha['data_formatada']?>',  <?=$linha['grafico_hospedagens_media_tihospedagemcom']?>, <?=$linha['grafico_hospedagens_media_tihospedagemcombr']?>, <?=$linha['grafico_hospedagens_media_server1thxhostcom']?>, <?=$linha['grafico_hospedagens_media_dominios']?>, <?=$linha['grafico_hospedagens_media_outrosservicos']?>],
			<? } ?>
        ]);

        var options = {
          title: 'Ticket Médio da Empresa',
          curveType: 'function',
          legend: { position: 'bottom' },
			 hAxis: {
				direction:-1,
				slantedText:true,
				slantedTextAngle:90 // here you can even use 180
			}
        };

        var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));

        chart.draw(data, options);
      }
    </script>
    <div id="curve_chart" style="width: 100%; height: 700px"></div>
	</div>


	<?=$detalhar?>



</div>