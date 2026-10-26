<?php
// Conexão com o banco de dados
 $conn = new mysqli(THX_DB_HOST, THX_DB_USERNAME, THX_DB_PASSWORD, THX_DB_NAME);


// Verifica conexão
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// Consulta SQL para recuperar os valores dos pagamentos por dia
// $sql = "SELECT
// DATE_FORMAT(date(datepaid), '%d') AS dia,
// DATE_FORMAT(date(datepaid), '%m') AS mes,
// total FROM tblinvoices
// WHERE datepaid != '0000-00-00 00:00:00'
// ORDER BY datepaid";

// $result = $conn->query($sql);

// // Array para armazenar os resultados formatados
 $data = array();




// Array para armazenar os dias e meses
$limiteDiasListar = 360;

$totais = $dataDiaria = array();

// Loop pelos últimos 90 dias
for ($i = $limiteDiasListar; $i >= 0; $i--) {
    // Obtém a data atual menos $i dias
    $dataa = date('Y-m-d', strtotime("-$i days"));

    // Extrai o dia e o mês da data
    $dia = date('d', strtotime($dataa));
    $mes = date('m', strtotime($dataa));
	$ano = date('Y', strtotime($dataa));


	// Consulta SQL
	$sql = "SELECT
	SUM(total) as total FROM tblinvoices
	WHERE MONTH(datepaid) = '{$mes}' AND DAY(datepaid) = '{$dia}' AND YEAR(datepaid) = '{$ano}' AND total > 0";

	$resultado = $conn->query($sql);



	// Verifica se há resultados
	if ($resultado->num_rows > 0)
	{
		// Loop através dos resultados
		while ($row = $resultado->fetch_assoc())
		{


			$chaveDiaria = "$mes-$ano";
			if($ano == date('Y') && $mes == date('m'))
			{
				$chaveDiaria = "$dia-$mes-$ano";
				$dataDiaria[] = array(
					'dia' => $dia,
					'valor' => floatval($row['total'])
				);
			}

			if(isset($totais[$chaveDiaria]))
			{
				$totais[$chaveDiaria] += $row['total'];
			}
			else
			{
				$totais[$chaveDiaria] = $row['total'];
			}


			$data[] = array(
				'dia' => $dia,
				'mes' => $mes,
				'valor' => floatval($row['total'])
			);
		}
	}
}
$anteriores = '<h1>Totais dos meses anteriores:</h1>';
$titulo = false;
$totalmesatual = 0;
$domes = '<h1>Totais do mês atual:</h1>';
foreach($totais as $mes => $total)
{
	if(strlen($mes) > 8)
	{
		if(! $titulo)
		{
			$titulo = true;
		}

		$domes.= '
		<button class="btn btn-danger" style="margin: 5px 5px 5px 0;width: 175px;" type="button">
			'.$mes.' <span class="badge">R$ '.number_format($total, 2,',', '.').'</span>
		</button>';
		$totalmesatual+=$total;
	}
	else
	{
		$anteriores.= '
		<button class="btn btn-primary" style="margin: 5px 5px 5px 0;width: 175px;" type="button">
			'.$mes.' <span class="badge">R$ '.number_format($total, 2,',', '.').'</span>
		</button>';
	}
}

$anteriores.= '
<button class="btn btn-danger"  style="margin: 5px 5px 5px 0;width: 175px;" type="button">
	'.date('m-Y').' <span class="badge">R$ '.number_format($totalmesatual, 2,',', '.').'</span>
</button>';

?>
<div class="row">
	<div class="col-md-12">
		<?=$domes?>
	</div>
	<div class="col-md-12">
		<div id="curve_chart_diario" style="width: 100%; height: 100%"></div>
	</div>
	<div class="col-md-12">
		<?=$anteriores?>
	</div>
	<div class="col-md-12">
		<div id="curve_chart" style="width: 100%; height: 100%"></div>
	</div>
</div>
<?

// Fecha a conexão com o banco de dados
// $conn->close();
?>
<script type='text/javascript' src='https://www.gstatic.com/charts/loader.js'></script>
<script type='text/javascript'>
        google.charts.load('current', {packages: ['corechart', 'line']});
        google.charts.setOnLoadCallback(drawChart);
		google.charts.setOnLoadCallback(drawChartDiaria);



        function drawChart()
		{
          	var data = new google.visualization.DataTable();
			data.addColumn('string', 'Dia');
			data.addColumn('number', 'Valor');

			data.addRows([
				<?
				foreach ($data as $row) {
					echo "['" . $row['dia'] . "/" . $row['mes'] . "', " . $row['valor'] . "],";
				}
				?>
			]);

			var options = {
			title: 'Valores diários dos pagamentos dos últimos <?=$limiteDiasListar?> dias',
			curveType: 'function',
			legend: { position: 'bottom' }
			};

			var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));

			chart.draw(data, options);
        }

		function drawChartDiaria()
		{
			var data = new google.visualization.DataTable();
			data.addColumn('string', 'Dia');
			data.addColumn('number', 'Valor');

			data.addRows([
			<?
			foreach ($dataDiaria as $row) {
				echo "['" . $row['dia'] . "', " . $row['valor'] . "],";
			}
			?>
			]);

			var options = {
			title: 'Totais dos dias do mes atual',
			curveType: 'function',
			legend: { position: 'bottom' }
			};

			var chart = new google.visualization.LineChart(document.getElementById('curve_chart_diario'));

			chart.draw(data, options);
        }
      </script>