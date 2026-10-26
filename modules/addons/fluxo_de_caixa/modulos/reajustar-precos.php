<?php
require_once('config.php');

$percent = isset($_GET['percent']) ? (float)$_GET['percent'] : 10;
$executar = isset($_POST['executar']) && $_POST['executar'] == '1';

// Buscar serviços
$stmt = $pdo->query("SELECT id, userid, domain, amount FROM tblhosting ORDER BY id ASC");
$servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar produtos
$stmtPricing = $pdo->query("SELECT p.relid, pr.name, p.monthly FROM tblpricing p 
    LEFT JOIN tblproducts pr ON pr.id = p.relid 
    WHERE p.type = 'product'");
$produtos = $stmtPricing->fetchAll(PDO::FETCH_ASSOC);

// Totais antes/depois
$totalAntes = 0;
$totalDepois = 0;
foreach ($servicos as $servico) {
    $totalAntes += $servico['amount'];
    $totalDepois += round($servico['amount'] * (1 + $percent/100), 2);
}
foreach ($produtos as $produto) {
    $totalAntes += $produto['monthly'];
    $totalDepois += round($produto['monthly'] * (1 + $percent/100), 2);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updates'])) {
    echo "<h2>Executando com percentual {$percent}%</h2>";

    if ($executar) {
        $pdo->beginTransaction();
    }

    echo "<h3>tblhosting (clientes)</h3><pre>";
    foreach ($_POST['updates'] as $id => $newValue) {
        $newValue = str_replace(',', '.', $newValue);
        $floatValue = (float)$newValue;
        $intId = (int)$id;

        if ($executar) {
            $stmt = $pdo->prepare("UPDATE tblhosting SET amount = :amount WHERE id = :id");
            $stmt->execute([
                ':amount' => $floatValue,
                ':id' => $intId
            ]);
        }

        echo "UPDATE tblhosting SET amount = {$floatValue} WHERE id = {$intId};\n";
    }
    echo "</pre>";

    echo "<h3>tblpricing (produtos)</h3><pre>";
    foreach ($produtos as $row) {
        $novo = round($row['monthly'] * (1 + $percent/100), 2);
        if ($executar) {
            $stmtProd = $pdo->prepare("UPDATE tblpricing SET monthly = :monthly WHERE type='product' AND relid = :relid");
            $stmtProd->execute([
                ':monthly' => $novo,
                ':relid' => $row['relid']
            ]);
        }
        echo "UPDATE tblpricing SET monthly = {$novo} WHERE type='product' AND relid = {$row['relid']}; -- {$row['name']}\n";
    }
    echo "</pre>";

    if ($executar) {
        $pdo->commit();
        echo "<p style='color:green'>Banco atualizado com sucesso.</p>";
    } else {
        echo "<p style='color:blue'>⚠️ Modo simulação - nada foi alterado.</p>";
    }

    echo "<p><b>Total antes:</b> R$ " . number_format($totalAntes, 2, ',', '.') . 
         " | <b>Total após:</b> R$ " . number_format($totalDepois, 2, ',', '.') . "</p>";

}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ajuste de Preços</title>
</head>
<body>
    <h2>Ajuste de preços em <?= htmlspecialchars($percent) ?>%</h2>
    <p><b>Total antes:</b> R$ <?= number_format($totalAntes, 2, ',', '.') ?>
    | <b>Total após:</b> R$ <?= number_format($totalDepois, 2, ',', '.') ?></p>

    <form method="get" action="addonmodules.php">
        <input type="hidden" name="module" value="fluxo_de_caixa">
        <input type="hidden" name="tab" value="reajustar-precos">
        <label>Percentual (%):
            <input type="number" step="0.01" name="percent" value="<?= htmlspecialchars($percent) ?>">
        </label>
        <button type="submit">Calcular</button>
    </form>

    <form method="post">
        <h3>Serviços (tblhosting)</h3>
        <table border="1" cellpadding="5">
            <tr>
                <th>ID</th>
                <th>Usuário</th>
                <th>Domínio</th>
                <th>Valor Atual</th>
                <th>Valor Proposto</th>
                <th>Editar</th>
            </tr>
            <?php foreach ($servicos as $servico): 
                $novoValor = round($servico['amount'] * (1 + $percent/100), 2);
            ?>
            <tr>
                <td><?= $servico['id'] ?></td>
                <td><?= $servico['userid'] ?></td>
                <td><?= htmlspecialchars($servico['domain']) ?></td>
                <td>R$ <?= number_format($servico['amount'], 2, ',', '.') ?></td>
                <td>R$ <?= number_format($novoValor, 2, ',', '.') ?></td>
                <td>
                    <input type="text" name="updates[<?= $servico['id'] ?>]" value="<?= number_format($novoValor, 2, ',', '.') ?>">
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <h3>Produtos (tblpricing)</h3>
        <table border="1" cellpadding="5">
            <tr>
                <th>ID Produto</th>
                <th>Produto</th>
                <th>Mensal Atual</th>
                <th>Mensal Proposto</th>
            </tr>
            <?php foreach ($produtos as $produto): 
                $novoMonthly = round($produto['monthly'] * (1 + $percent/100), 2);
            ?>
            <tr>
                <td><?= $produto['relid'] ?></td>
                <td><?= htmlspecialchars($produto['name']) ?></td>
                <td>R$ <?= number_format($produto['monthly'], 2, ',', '.') ?></td>
                <td>R$ <?= number_format($novoMonthly, 2, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <br>
        <label><input type="checkbox" name="executar" value="1"> Executar UPDATE no banco</label>
        <br><br>
        <button type="submit" onclick="return confirm('<?= $executar ? '⚠️ Isso vai atualizar o banco. Continuar?' : 'Mostrar apenas simulação?' ?>')">
            <?= $executar ? '🚀 Rodar UPDATE no banco' : '🔍 Mostrar SQLs simulados' ?>
        </button>
    </form>
</body>
</html>
