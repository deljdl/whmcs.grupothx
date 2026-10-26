<?
		if(isset($_GET['listar_pagas']))
		{
			if($_GET['listar_pagas'] == 'true')
			{
				$_SESSION['listar_pagas'] = true;
			}
			else
			{
				if(isset($_SESSION['listar_pagas']))
				{
					unset($_SESSION['listar_pagas']);
				}
			}
		}
	

		if(isset($_POST['lote']))
		{
			$lote = implode(',', $_POST['lote']);
			$pagarLote = "UPDATE jobs SET job_data_pagamento = NOW() WHERE	job_id IN({$lote})";
			mysql_query($pagarLote);
		}

		$caixain = $caixaout = '';
		if(isset($_GET['pagou']))
		{
			$pagar = "UPDATE jobs SET job_data_pagamento = NOW() WHERE	job_id =".$_GET['job_id'];
			mysql_query($pagar);
		}

		if(isset($_GET['despagou']))
		{
			$pagar = "UPDATE jobs SET job_data_pagamento = '0000-00-00 00:00:00' WHERE	job_id =".$_GET['job_id'];
			mysql_query($pagar);
		}


		if(isset($_GET['apagou']))
		{
			$pagar = "DELETE FROM jobs WHERE job_id =".$_GET['apagou'];
			mysql_query($pagar);
		}

		if(isset($_GET['valor_job']))
		{


			if($_GET['campo'] == 'job_data_inicio' || $_GET['campo'] == 'job_data_deadline')
			{
				//echo($_GET['valor_job']);
				$_GET['valor_job'] = explode('/',$_GET['valor_job']);
				$_GET['valor_job'] = $_GET['valor_job'][2].'-'.$_GET['valor_job'][1].'-'.$_GET['valor_job'][0];
				//die($_GET['valor_job']);
			}

			$_GET['valor_job'] = urldecode($_GET['valor_job']);
			$pagar = "UPDATE jobs SET {$_GET['campo']} = '{$_GET['valor_job']}' WHERE job_id =".$_GET['job_id'];
			//echo ($pagar);
			mysql_query($pagar);


		}

		if(isset($_POST['add_job']))
		{
			$add = "INSERT INTO jobs (job_pj, job_job, job_valor,job_data_inicio, job_data_deadline) VALUES
                ('{$_POST['job_pj']}','{$_POST['job_job']}','{$_POST['job_valor']}', '{$_POST['job_data_inicio']}', '{$_POST['job_data_deadline']}')";
			//die($pagar);
			mysql_query($add) or die('Erro ad add divida<hr />'.mysql_error());
		}

		 $retorno ='';
		 $entrada = $saida = $cobrancas = 0;





		$condicao = isset($_SESSION['listar_pagas']) ? " WHERE job_data_pagamento != '0000-00-00 00:00:00' " : " WHERE job_data_pagamento = '0000-00-00 00:00:00' ";
		$condicaoSoma = isset($_SESSION['listar_pagas']) ? " t.job_data_pagamento != '0000-00-00 00:00:00' " : " t.job_data_pagamento = '0000-00-00 00:00:00' ";
		$sql = "SELECT * FROM jobs {$condicao} ORDER BY job_data_create";
		$jobs = mysql_query($sql);

        $prestadores = '<option value="">Prestador</option>';
        $prestadoresBd = mysql_query("SELECT *,(SELECT SUM(t.job_valor) FROM jobs t WHERE t.job_pj=p.pj_id AND {$condicaoSoma}) as aberto FROM pjs p ORDER BY p.pj_id");
        $faltapagar = '';
        $modalsDetalhes = array();
        while($prestador = mysql_fetch_array($prestadoresBd))
        {

        	$prestadores.='<option value="'.$prestador['pj_id'].'">'.$prestador['pj_nome'].'</option>';
        	$faltapagar.= '
        	<button class="btn btn-primary" type="button" data-toggle="modal" data-target="#myModal'.$prestador['pj_id'].'">
	          '.$prestador['pj_nome'].' <span class="badge">R$ '.$prestador['aberto'].'</span>
	        </button>';

	        $modalsDetalhes[$prestador['pj_id']] = array($prestador, '');
        }


		$pago = $emaberto = 0;

		while($job = mysql_fetch_array($jobs))
		{
			if($job['job_data_pagamento'] == '' || $job['job_data_pagamento'] == '0000-00-00 00:00:00')
			{
				$class = 'danger';
			}
			else
			{
				$class="success";
			}

			$prestadorAtual = str_replace('value="'.$job['job_pj'].'"', 'value="'.$job['job_pj'].'" selected ',$prestadores);


			if($job['job_data_pagamento'] != '0000-00-00 00:00:00')
			{
				$paga = '<span class="label label-'.$class.'" onclick="despagaJob('.$job['job_id'].', '.$job['divida_paga'].')" style="cursor:pointer;">'.strftime('%d/%m/%Y', strtotime($job['job_data_pagamento'])).'</span> ';
				$ckbox = '';
			}
			else
			{
				$paga = '<span class="label label-'.$class.'" onclick="pagaJob('.$job['job_id'].', '.$job['divida_paga'].')" style="cursor:pointer;">'.($job['divida_paga'] == 1 ? 'Paga' : 'Pagar').'</span> ';

				$modalsDetalhes[$job['job_pj']][1].= "<tr><td style=\"padding: 10px\">{$job['job_job']}</td><td style=\"padding: 10px\">R$ {$job['job_valor']}</td></tr>";

				$ckbox = '<input type="checkbox" value="'.$job['job_id'].'" valor="'.$job['job_valor'].'" name="lote[]" onChange="addPagamentoLote(this)" >';
			}



			$caixaout .= '<tr class="'.$class.'"><td>'.$ckbox.'</td><td style="line-height: 15px;"><span contenteditable="true" ondblclick="setEdicaoEmLinha(this, \'job_job\', '.$job['job_id'].')">'.$job['job_job'].'</span><br /><br /><small class="label label-'.$class.'" style="font-size: 70%;">'.$prazo.'</small></td><td> R$ <span contenteditable="true" ondblclick="setEdicaoEmLinha(this, \'job_valor\', '.$job['job_id'].')">'.$job['job_valor'].'</span></td><td><select onChange="setEdicaoEmLinhaSelect(this, \'job_pj\', '.$job['job_id'].')" name="job_pj" class="form-control"> '.$prestadorAtual.'</select></td><td><span contenteditable="true" ondblclick="setEdicaoEmLinha(this, \'job_data_inicio\', '.$job['job_id'].')">'.strftime('%d/%m/%Y', strtotime($job['job_data_inicio'])).'</span></td><td><span contenteditable="true" ondblclick="setEdicaoEmLinha(this, \'job_data_deadline\', '.$job['job_id'].')">'.strftime('%d/%m/%Y', strtotime($job['job_data_deadline'])).'</span></td><td>'.$paga.'<button class="btn btn-danger" style="float: right;padding: 1px 7px;" onClick="javascript:deleteJob('.$job['job_id'].')">x</button></td></tr>';
			$saida += $job['job_valor'];

		}

		$modals = '';
		foreach($modalsDetalhes as $key => $modalJobs)
		{
			$modals.='
			<div id="myModal'.$key.'" class="modal fade" role="dialog">
			  <div class="modal-dialog modal-lg">

			    <!-- Modal content-->
			    <div class="modal-content">
			      <div class="modal-header">
			        <button type="button" class="close" data-dismiss="modal">&times;</button>
			        <h4 class="modal-title">Jobs em aberto '.$modalJobs[0]['pj_nome'].'</h4>
					<p><strong>Dados de Pagamento:</strong><br />'.$modalJobs[0]['pj_dados_bancarios'].'</p>

			      </div>
			      <div class="modal-body">
			      	<table style="width: 100%; border: solid #CCC 1px">
			        	'.$modalJobs[1].'
			        	<tr><th style="padding: 10px" colspan="2"><hr /></th></tr>
			        	<tr><th style="padding: 10px">Total em Aberto</th><th style="padding: 10px">R$ '.$modalJobs[0]['aberto'].'</th></tr>
			        	<tr><th style="padding: 10px" colspan="2"><small>Gerado em '.date('d/m/Y G:i').'</small></th></tr>
			        </table>
			      </div>
			      <div class="modal-footer">
			        <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
			      </div>
			    </div>

			  </div>
			</div>';
		}










				$pago =  number_format($pago,2,'.','');
				$saldo = $entrada - $saida;
				$devendo = $saldo <=0 ? 'danger' : 'success';





		echo "
		<script>
		function pagaJob(id, paga)
		{
			paga = paga == 1 ? 0 : 1;
			url = '{$path}gerenciamento-pj&pagou='+paga+'&job_id='+id;
			location = url;
		}

		function despagaJob(id, paga)
		{
			paga = paga == 1 ? 0 : 1;
			url = '{$path}gerenciamento-pj&despagou='+paga+'&job_id='+id;
			location = url;
		}

		function addPagamentoLote()
		{
			total = 0.00;
			$('input[name=\"lote[]\"]').each(function( index ) {
			  console.log( index + ': ' + $( this ).val() + ' - ' + $( this ).prop('checked') + ' - ' +$( this ).attr('valor') );
			  if($( this ).prop('checked'))
			  {
			  	total+= parseFloat($( this ).attr('valor'));
			  }
			});

			$('#btLotePagamento').html('Pagar R$ ' + total.toFixed(2));
			if(total > 0)
			{
				$('#btLotePagamento').fadeIn();
			}
			else
			{
				$('#btLotePagamento').fadeOut();
			}

			console.log(total.toFixed(2));
		}

		function addJobLote(ck)
		{
			$('input[name=\"lote[]\"]').prop('checked', $(ck).prop('checked'));
			addPagamentoLote();
		}

		function setEdicaoEmLinha(el,campo,id)
		{
			if(! confirm('Editar ????'))
			{
				return false;
			}
			link = '{$path}gerenciamento-pj&valor_job='+el.innerHTML+'&job_id='+id+'&campo='+campo;
			location = link;
		}
		function setEdicaoEmLinhaSelect(el,campo,id)
		{
			if(! confirm('Editar ????'))
			{
				return false;
			}
			link = '{$path}gerenciamento-pj&valor_job='+el.value+'&job_id='+id+'&campo='+campo;
			location = link;
		}
		function deleteJob(id)
		{
			if(! confirm('Excluir ????'))
			{
				return false;
			}
			url = '{$path}gerenciamento-pj&apagou='+id;
			location = url;
		}

		</script>";
?>

<div class="row">
<div class="col-md-12 nopadding" style="margin-bottom: 30px">
		<form method="post">
			<table class="table table-hover">
					<tr>

						<td colspan="1">
							<input type="text" class="form-control" placeholder="Job Name" title="Job Name" required name="job_job" required>
						</td>
						<td colspan="1">
							<input type="text" class="form-control" required placeholder="valor" title="valor" name="job_valor" required style="width: 100px;">
						</td>

						<td colspan="1">
							<select  class="form-control" required  name="job_pj" required>
								<?=$prestadores?> 
							</select>
						</td>

						<td colspan="1">
							<input type="date" class="form-control" required placeholder="Inicio" title="Inicio" name="job_data_inicio" required>
						</td>
						<td colspan="1">
							<input type="date" class="form-control" required placeholder="Deadline" title="Deadline" name="job_data_deadline" required>
						</td>
					</tr>
					<tr>
						<td colspan="5">
							<input style="width:100%; display: block" type="submit" class="btn btn-primary"   name="add_job" value="Cadastrar Novo Job">
						</td>
					</tr>
			</table>
		</form>
	</div>
	<div class="col-md-12 nopadding" style="margin-bottom: 30px">
		<?=$faltapagar?>
		<a class="btn btn-success" style="float:right; margin-left:10px" href="?module=fluxo_de_caixa&tab=gerenciamento-pj&listar_pagas=true" title="Listar Jobs Pagos">P</a>
		<a class="btn btn-danger" style="float:right" href="?module=fluxo_de_caixa&tab=gerenciamento-pj&listar_pagas=false" title="Listar Jobs em Aberto">A</a>


	</div>
	<form method="post" onSubmit="return confirm('Deseja Realmente ' + $('#btLotePagamento').html() + '?')">
		<div class="col-md-12 nopadding" style="border-left: solid #1a4d80 1px;">
			<h2>Jobs</h2>
			<table class="table table-hover">
			<thead>
			<tr>
			<th width="10px"><?=!isset($_SESSION['listar_pagas']) ? '<input type="checkbox" onChange="addJobLote(this)">' : '' ?></th>
			<th colspan="6"><button style="width:100%; display: none" type="submit" class="btn btn-success"  id="btLotePagamento"></button></th>
			</tr>

			<tr>
			<th width="10px"></th>
			<th>Job</th>
			<th>Valor</th>
			<th>PJ</th>
			<th>Inicio</th>
			<th>Deadline</th>
			<th></th>
			</tr>
			</thead>
			<tbody>
				<?=$caixaout?>
			</tbody>
			</table>
		</div>
	</form>
	
</div>
<?=$modals?>