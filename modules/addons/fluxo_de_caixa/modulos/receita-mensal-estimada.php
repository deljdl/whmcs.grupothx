<?
						$ssql = "
						SELECT 
							h.id AS hosting_id,
							h.userid,
							h.domain,
							p.name AS product_name,
							h.billingcycle,
							h.amount AS total_amount,
							ROUND(
								ABS(
									CASE 
										WHEN h.billingcycle = 'Monthly' THEN h.amount
										WHEN h.billingcycle = 'Quarterly' THEN h.amount / 3
										WHEN h.billingcycle = 'Semi-Annually' THEN h.amount / 6
										WHEN h.billingcycle = 'Annually' THEN h.amount / 12
										WHEN h.billingcycle = 'Biennially' THEN h.amount / 24
										WHEN h.billingcycle = 'Triennially' THEN h.amount / 36
										ELSE 0 
									END
								), 2
							) AS equivalent_monthly_amount,
							DATE_FORMAT(i.duedate, '%d/%m/%Y') AS vencimento_formatado,
							i.duedate,
							i.status
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
						ORDER BY 
    						i.duedate ASC";

						
						$results = mysql_query($ssql);
					
						$total = $totalPago = $totalAberto = $totalVencido = 0;
						$hoje = date('Y-m-d'); // Data atual no formato Y-m-d
						while($linha = mysql_fetch_array($results))
						{
							$class = 'default';
							if ($linha['status'] == 'Paid') {
								$class = 'success'; // Pagamento efetuado
								$totalPago+=$linha['total_amount'];
							} elseif ($linha['duedate'] < $hoje) {
								$class = 'danger'; // Já venceu e não foi pago
								$totalVencido+=$linha['total_amount'];
							} else {
								$class = 'info'; // Ainda não venceu e não foi pago
								$totalAberto+=$linha['total_amount'];
							}

							$detalhar.= '			
							<tr class="'.$class.'">
								<td>'.$linha['domain'].'</td>
								<td>'.$linha['vencimento_formatado'].'</td>
								<td>'.$linha['product_name'].'</td>				
								<td>'.$linha['billingcycle'].'</td>
								<td>R$ '.number_format($linha['equivalent_monthly_amount'],2,',','.').'</td>
								<td>R$ '.number_format($linha['total_amount'],2,',','.').'</td>
							</tr>';

							$total+=$linha['total_amount'];
							
						}

						$detalhar = '
							<div class="col-md-12 nopadding" >
								<table class="table table-hover">
									<thead>
										<tr>
											<th>Domínio</th>
											<th>Vencimento</th>
											<th>Produto</th>
											<th>Ciclo</th>
											<th>Equivalente Mensal</th>
											<th>Total</th>
										</tr>
									</thead>
								<tbody>
									'.$detalhar.'
								</tbody>
								<tfoot>
									<tr>
										<th colspan="5" style="text-align: right;">Receita Mensal Estimada</th>
										<th>R$ '.number_format($total,2,',','.').'</th>
									</tr>
									<tr>
										<th colspan="5" style="text-align: right;">Total já Pago</th>
										<th>R$ '.number_format($totalPago,2,',','.').'</th>
									</tr>
									<tr>
										<th colspan="5" style="text-align: right;">Total em aberto</th>
										<th>R$ '.number_format($totalAberto,2,',','.').'</th>
									</tr>
									<tr>
										<th colspan="5" style="text-align: right;">Total Vencido</th>
										<th>R$ '.number_format($totalVencido,2,',','.').'</th>
									</tr>
								</tfoot>
								</table>
							</div>';

?>
<div class="row">
<?=$detalhar?>
</div>