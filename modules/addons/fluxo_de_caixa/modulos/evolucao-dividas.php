<?php
$conn = new mysqli(THX_DB_HOST, THX_DB_USERNAME, THX_DB_PASSWORD, THX_DB_NAME);
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$tipos = [
    'WhatsApp API' => ['WhatsApp', 'API WhatsApp', 'Wassenger'],
    'Internet' => ['Internet - RedFox', 'Internet - Sem Limite', 'Internet'],
    'Água' => ['Agua'],
	'Locaweb' => ['Locaweb'],
    'Pensão' => ['Thayna', 'Alice', 'Pensao'],
    'Luz' => ['Luz', 'Luz Apartamento'],
    'GPS' => ['GPS', 'GPS - INSS'],
	'Condomínio Apartamento' => ['Condominio Apartamento'],
	'Gás Apartamento' => ['Gas Apartamento'],
	'Servidor Dedicado Black - tihospedagem.com' => ['Servidor Dedicado Black - tihospedagem.com'],
	'Servidor Dedicado Black - tihospedagem.com.br' => ['Servidor Dedicado Black - tihospedagem.com.br'],
    'Servidores Dedicados' => ['Servidor Dedicado Black - tihospedagem.com', 'Servidor Dedicado Black - tihospedagem.com.br'],
    'VPS' => ['VPS Grupo THX', 'VPS Standard - grupothx.com.br', 'VPS'],
	'Claro' => ['Claro - Telefone Fixo', 'Claro - Telefone Móvel'],
	'Claro - Telefone Móvel' => ['Claro - Telefone Móvel'],
	'Vimeo' => ['Vimeo'],
    'Academia' => ['Academia'],
    'Barbeiro' => ['Barbeiro'],
    'Contador' => ['Contador'],
    'Seguro' => ['Seguro City'],
    'Investimentos' => ['Investimento', 'Meta R$'],
    'Salario Jardel' => ['Salario Jardel', 'Jardel Pagamento'],
    'IPTU' => ['IPTU'],
    'Tudo' => [''] // Pega tudo
];


$dadosGraficos = [];

foreach ($tipos as $nomeGrupo => $termos) {
    $where = [];

    foreach ($termos as $termo) {
        $termo = $conn->real_escape_string(trim($termo));
        $where[] = $termo ? "divida_descricao LIKE '%$termo%'" : "1=1";
    }

    $sql = "
        SELECT
            DATE_FORMAT(divida_vencimento, '%Y-%m') AS mes,
            SUM(divida_valor) AS total
        FROM dividas
        WHERE " . implode(' OR ', $where) . "
        GROUP BY mes
        ORDER BY mes
    ";

    $resultado = $conn->query($sql);
    if (!$resultado) {
        echo "Erro na query para grupo <b>$nomeGrupo</b>: " . $conn->error . "<br>";
        continue;
    }

    $labels = [];
    $valores = [];
    $min = 999999;
    $max = 0;
    $totalSum = 0;

    while ($row = $resultado->fetch_assoc()) {
        $labels[] = $row['mes'];
        $valores[] = (float) $row['total'];
        $min = min($min, $row['total']);
        $max = max($max, $row['total']);
        $totalSum += $row['total'];
    }

    if (!empty($labels)) {
        $dadosGraficos[] = [
            'tipo' => $nomeGrupo . ' | Mínimo: R$ ' . number_format($min, 2, ',', '.') .
                      ' | Máximo: R$ ' . number_format($max, 2, ',', '.') .
                      ' | Total: R$ ' . number_format($totalSum, 2, ',', '.') .
                      ' | Média: R$ ' . number_format($totalSum / count($labels), 2, ',', '.') .
                      ' | Cobranças: ' . count($labels),
            'labels' => $labels,
            'valores' => $valores
        ];
    }
}

?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div id="graficos-container"></div>

<script>
const dados = <?= json_encode($dadosGraficos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>;

dados.forEach((grafico, index) => {
    const container = document.getElementById('graficos-container');

    const canvas = document.createElement('canvas');
    canvas.id = 'grafico_' + index;
    canvas.height = 100;
    container.appendChild(document.createElement('hr'));
    container.appendChild(document.createElement('h4')).innerText = grafico.tipo;
    container.appendChild(canvas);

    new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: grafico.labels,
            datasets: [{
                label: 'Valor total',
                data: grafico.valores,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0,123,255,0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: val => 'R$ ' + val
                    }
                }
            }
        }
    });
});
</script>
