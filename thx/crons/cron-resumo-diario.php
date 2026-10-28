<?php

// Obtém o número do dia da semana (1 = segunda-feira, 7 = domingo)
$diaSemana = date('N');

if ($diaSemana >= 6) {
    echo "Hoje é final de semana. Script interrompido.";
    exit(); // Para a execução do script
}


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


$totais = array();


$ultimo_dia_mes_3 = cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));

$valorDoDia = $valorDoMes = 0;
// Loop pelos últimos 90 dias
for ($i = 1; $i <= $ultimo_dia_mes_3; $i++) {

    // Extrai o dia e o mês da data
    $dia = $i;
    $mes = date('m');
	$ano = date('Y');


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

			if($ano == date('Y') && $mes == date('m') && $dia == date('d'))
			{
				$valorDoDia = $row['total'];
			}

			$valorDoMes += $row['total'];
		}
	}
}



// Define a timezone para a sua região
date_default_timezone_set('America/Sao_Paulo');

// Obtém a hora atual
$hora = date('G');

// Define a saudação com base na hora
if ($hora >= 5 && $hora < 12) {
    $saudacao = "Ótimo dia ";
} elseif ($hora >= 12 && $hora < 18) {
    $saudacao = "Ótima tarde ";
} else {
    $saudacao = "Ótima noite ";
}



$text = '
'.$saudacao.' Jardel.
Resumo da empresa Grupo THX:
'.($valorDoDia > 0 ? 'Entrada diária: R$ '.number_format($valorDoDia, 2,',', '.').'.' : '').'
Entrada mensal: R$ '.number_format($valorDoMes, 2,',', '.').'.';




$sql = "SELECT count(1) as total, SUM(total) as totalr FROM tblinvoices WHERE duedate < NOW() AND status = 'Unpaid'";

	$resultado = $conn->query($sql);



	// Verifica se há resultados
	if ($resultado->num_rows > 0)
	{
		// Loop através dos resultados
		while ($row = $resultado->fetch_assoc())
		{
			$text.= '
			Há '.$row['total'].' faturas em atraso no valor total de R$ '.number_format($row['totalr'], 2,',', '.').'.';
		}
	}


	$sql = "SELECT count(1) as total  FROM tbltickets WHERE status = 'Open'";

	$resultado = $conn->query($sql);



	// Verifica se há resultados
	if ($resultado->num_rows > 0)
	{
		while ($row = $resultado->fetch_assoc())
		{
			if($row['total'] > 0)
			{
				$text.= '
				Temos '.$row['total'].' chamado'.($row['total'] > 1 ? 's' : '').' aguardando resposta.';
			}
		}
	}


	$ano = date('Y');
	$mes = date('m');
	$sql = "SELECT SUM(divida_valor) as total  FROM dividas WHERE divida_paga = 0 AND divida_ano = '{$ano}' AND divida_mes = '{$mes}'";

	$resultado = $conn->query($sql);



	// Verifica se há resultados
	if ($resultado->num_rows > 0)
	{
		// Loop através dos resultados
		while ($row = $resultado->fetch_assoc())
		{
			if($row['total'] > 0)
			{
				$text.= '
				Total de dívidas em aberto para o mês: R$ '.number_format($row['total'], 2,',', '.').'.';
			}
			else
			{
				$text.= '
				Não há dívidas em aberto para esse mês.';
			}
		}
	}





$text=urlencode($text);
        $url = 'https://alexa.grupothx.com.br/send-voice?message='.$text;



$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$response = curl_exec($ch);
curl_close($ch);