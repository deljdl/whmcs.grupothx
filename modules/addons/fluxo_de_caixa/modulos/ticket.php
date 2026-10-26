<?php
require_once('config.php');

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

$dadosServidores = [];
$total = 0;
$totalCusto = 0;
$totalClientes = 0;

// ==========================
// TABELA PRINCIPAL SERVIDORES
// ==========================
$sql = "
    SELECT s.id AS server_id,
           s.name AS server_name,
           s.monthlycost AS custo_mensal,
           COUNT(h.id) AS clientes,
           COALESCE(SUM(
                CASE
                    WHEN h.billingcycle = 'Quarterly' THEN (h.amount/3)
                    WHEN h.billingcycle = 'Semi-Annually' THEN (h.amount/6)
                    WHEN h.billingcycle = 'Annually' THEN (h.amount/12)
                    WHEN h.billingcycle = 'Biennially' THEN (h.amount/24)
                    WHEN h.billingcycle = 'Triennially' THEN (h.amount/36)
                    ELSE h.amount
                END
           ),0) AS ticket_mensal
    FROM tblservers s
    LEFT JOIN tblhosting h ON h.server = s.id 
        AND h.domainstatus IN ('Active','Suspended') 
        AND h.billingcycle NOT IN ('One Time', 'Free Account')
    GROUP BY s.id
    ORDER BY s.id
";
$stmt = $pdo->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$idsServidores = [];

foreach ($results as $row) {
    $serverId = $row['server_id'];
    $nome = $row['server_name'] ?: "Servidor #$serverId";
    $ticket = floatval($row['ticket_mensal']);
    $custo = floatval($row['custo_mensal']);
    $saldo = $ticket - $custo;
    $clientes = intval($row['clientes']);

    $dadosServidores[] = [
        'nome' => $nome,
        'ticket' => $ticket,
        'custo' => $custo,
        'saldo' => $saldo,
        'clientes' => $clientes,
        'detalhe' => "?module=fluxo_de_caixa&tab=ticket&detalhar=server_$serverId"
    ];

    $total += $ticket;
    $totalCusto += $custo;
    $totalClientes += $clientes;

    $idsServidores[] = $serverId;
}

// ==========================
// OUTROS SERVIÇOS
// ==========================
$idsString = count($idsServidores) > 0 ? implode(',', $idsServidores) : '0';
$sql = "
    SELECT 
        COUNT(id) AS clientes,
        COALESCE(SUM(
            CASE
                WHEN billingcycle = 'Quarterly' THEN (amount/3)
                WHEN billingcycle = 'Semi-Annually' THEN (amount/6)
                WHEN billingcycle = 'Annually' THEN (amount/12)
                WHEN billingcycle = 'Biennially' THEN (amount/24)
                WHEN billingcycle = 'Triennially' THEN (amount/36)
                ELSE amount
            END
        ),0) AS ticket_mensal
    FROM tblhosting
    WHERE domainstatus = 'Active'
      AND billingcycle NOT IN ('One Time', 'Free Account')
      AND (server NOT IN ($idsString) OR server IS NULL)
";
$rowOutros = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);

$ticketOutros = floatval($rowOutros['ticket_mensal']);
$clientesOutros = intval($rowOutros['clientes']);
$dadosServidores[] = [
    'nome' => "Outros Serviços",
    'ticket' => $ticketOutros,
    'custo' => 0,
    'saldo' => $ticketOutros,
    'clientes' => $clientesOutros,
    'detalhe' => "?module=fluxo_de_caixa&tab=ticket&detalhar=outrosservicos"
];
$total += $ticketOutros;
$totalClientes += $clientesOutros;

// ==========================
// DOMÍNIOS
// ==========================
$sql = "SELECT (COUNT(1) * 10 / 12) AS mensal, COUNT(1) AS total FROM tbldomains";
$resDominios = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
$mensalDominios = floatval($resDominios['mensal']);
$totalDominios = intval($resDominios['total']);
$dadosServidores[] = [
    'nome' => "Registro Domínios",
    'ticket' => $mensalDominios,
    'custo' => 0,
    'saldo' => $mensalDominios,
    'clientes' => $totalDominios,
    'detalhe' => "?module=fluxo_de_caixa&tab=ticket&detalhar=registrodominios"
];
$total += $mensalDominios;
$totalClientes += $totalDominios;

// ==========================
// DETALHAR CLIENTES
// ==========================
$detalhar = '';
if (isset($_GET['detalhar'])) {
    $tipo = $_GET['detalhar'];
    if (preg_match('/^server_(\d+)$/', $tipo, $m)) {
        $serverId = intval($m[1]);
        $sql = "
            SELECT domain, billingcycle, amount,
                   CASE
                        WHEN billingcycle = 'Quarterly' THEN (amount/3)
                        WHEN billingcycle = 'Semi-Annually' THEN (amount/6)
                        WHEN billingcycle = 'Annually' THEN (amount/12)
                        WHEN billingcycle = 'Biennially' THEN (amount/24)
                        WHEN billingcycle = 'Triennially' THEN (amount/36)
                        ELSE amount
                   END AS valor_mensal_equivalente
            FROM tblhosting
            WHERE domainstatus = 'Active' AND server = :server AND billingcycle NOT IN ('One Time', 'Free Account')
            ORDER BY domain
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['server' => $serverId]);
    }
    elseif ($tipo == 'outrosservicos') {
        $sql = "
            SELECT domain, billingcycle, amount,
                   CASE
                        WHEN billingcycle = 'Quarterly' THEN (amount/3)
                        WHEN billingcycle = 'Semi-Annually' THEN (amount/6)
                        WHEN billingcycle = 'Annually' THEN (amount/12)
                        WHEN billingcycle = 'Biennially' THEN (amount/24)
                        WHEN billingcycle = 'Triennially' THEN (amount/36)
                        ELSE amount
                   END AS valor_mensal_equivalente
            FROM tblhosting
            WHERE domainstatus = 'Active' 
              AND billingcycle NOT IN ('One Time', 'Free Account')
              AND (server NOT IN ($idsString) OR server IS NULL)
            ORDER BY domain
        ";
        $stmt = $pdo->query($sql);
    }
    elseif ($tipo == 'registrodominios') {
        $sql = "
            SELECT domain, registrationperiod as billingcycle, recurringamount as amount, 
                   (10/12) AS valor_mensal_equivalente
            FROM tbldomains
            ORDER BY domain
        ";
        $stmt = $pdo->query($sql);
    }

    $detalhar .= '<div class="col-md-12 nopadding" >
                    <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Domínio</th>
                            <th>Ciclo</th>
                            <th>Valor</th>
                            <th>Valor Eq Mensal</th>
                        </tr>
                    </thead>
                    <tbody>';

    while ($linha = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $detalhar .= '<tr>
                        <td>'.htmlspecialchars($linha['domain']).'</td>
                        <td>'.$ciclos[$linha['billingcycle']].'</td>
                        <td>R$ '.number_format($linha['amount'],2,',','.').'</td>
                        <td>R$ '.number_format($linha['valor_mensal_equivalente'],2,',','.').'</td>
                      </tr>';
    }

    $detalhar .= '</tbody></table></div>';
}

// ==========================
// GRÁFICO GOOGLE CHARTS
// ==========================
$graficoQuery = $pdo->query("SELECT *, DATE_FORMAT(grafico_hospedagens_data, '%d/%m/%y') AS data_formatada 
                            FROM grafico_hospedagens 
                            ORDER BY grafico_hospedagens_data DESC");
$dadosGrafico = $graficoQuery->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="row">
    <div class="col-md-12 nopadding">
        <table class="table table-hover" style="width:100%;border-collapse:collapse;" border="1" cellpadding="8" cellspacing="0">
            <thead>
                <tr style="background:#f0f0f0;">
                    <th>Serviço</th>
                    <th>Clientes</th>
                    <th>Ticket Médio Mensal</th>
                    <th>Custo Médio Mensal</th>
                    <th>Saldo Médio Mensal</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dadosServidores as $d): ?>
                <tr style="<?= ($d['saldo'] < 0) ? 'background:#ffe5e5;' : '' ?>">
                    <td><?= htmlspecialchars($d['nome']) ?></td>
                    <td align="center"><?= $d['clientes'] ?></td>
                    <td align="right">R$ <?= number_format($d['ticket'],2,',','.') ?></td>
                    <td align="right">R$ <?= number_format($d['custo'],2,',','.') ?></td>
                    <td align="right"><strong>R$ <?= number_format($d['saldo'],2,',','.') ?></strong></td>
                    <td><a class="label label-danger" href="<?= $d['detalhe'] ?>">Detalhar</a></td>
                </tr>
                <?php endforeach; ?>
                <tr style="background:#f0f0f0;font-weight:bold;">
                    <td>Total</td>
                    <td align="center"><?= $totalClientes ?></td>
                    <td align="right">R$ <?= number_format($total,2,',','.') ?></td>
                    <td align="right">R$ <?= number_format($totalCusto,2,',','.') ?></td>
                    <td align="right">R$ <?= number_format(($total-$totalCusto),2,',','.') ?></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
		<h1 style="text-align: center;color: red;font-size: 50px;">Saldo Mensal R$ <?= number_format(($total-$totalCusto),2,',','.') ?></h1>
    </div>
    <div class="col-md-12 nopadding">
        <script src="https://www.gstatic.com/charts/loader.js"></script>
        <script>
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawChart);
        function drawChart() {
            var data = google.visualization.arrayToDataTable([
                ['Dia', 'TihospedagemCOM', 'TihospedagemBR', 'Server1ThxHost', 'Domínios', 'Outros'],
                <?php foreach ($dadosGrafico as $linha): ?>
                ['<?= $linha['data_formatada'] ?>',
                 <?= floatval($linha['grafico_hospedagens_media_tihospedagemcom']) ?>,
                 <?= floatval($linha['grafico_hospedagens_media_tihospedagemcombr']) ?>,
                 <?= floatval($linha['grafico_hospedagens_media_server1thxhostcom']) ?>,
                 <?= floatval($linha['grafico_hospedagens_media_dominios']) ?>,
                 <?= floatval($linha['grafico_hospedagens_media_outrosservicos']) ?>],
                <?php endforeach; ?>
            ]);
            var options = {
                title: 'Ticket Médio da Empresa',
                curveType: 'function',
                legend: { position: 'bottom' },
                hAxis: {
                    direction:-1,
                    slantedText:true,
                    slantedTextAngle:90
                }
            };
            var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));
            chart.draw(data, options);
        }
        </script>
        <div id="curve_chart" style="width: 100%; height: 700px"></div>
    </div>
    <?= $detalhar ?>
</div>
