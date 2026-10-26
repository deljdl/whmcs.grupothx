<?php
require_once 'config.php';

define('NEG_DAYS', 80); // troque aqui o número de dias

// ===== SQL (saldo real por fatura) =====
$sql = "
    SELECT
        c.id AS client_id,
        CONCAT(c.firstname,' ',c.lastname) AS nome,
        c.email,
        COUNT(i.id) AS qtd_faturas,
        SUM(GREATEST(i.total - i.credit - IFNULL(p.paid,0), 0)) AS saldo_total,
        MIN(i.duedate) AS primeira_vencida,
        DATEDIFF(CURDATE(), MIN(i.duedate)) AS dias_atraso_max
    FROM tblinvoices i
    JOIN tblclients c ON c.id = i.userid
    LEFT JOIN (
        SELECT invoiceid, SUM(amountin - amountout) AS paid
        FROM tblaccounts
        GROUP BY invoiceid
    ) p ON p.invoiceid = i.id
    WHERE i.status = 'Unpaid'
      AND i.duedate != '0000-00-00'
      AND DATEDIFF(CURDATE(), i.duedate) >= ?
      AND (i.total - i.credit - IFNULL(p.paid,0)) > 0
    GROUP BY c.id, c.firstname, c.lastname, c.email
    HAVING saldo_total > 0
    ORDER BY saldo_total DESC
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(NEG_DAYS));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "ERRO: ".$e->getMessage();
    exit;
}

// ===== Helpers =====
function money_br($v){ return 'R$ '.number_format((float)$v, 2, ',', '.'); }
$agora = date('d/m/Y H:i');
$totalClientes = is_array($rows) ? count($rows) : 0;

$totalSaldo = 0.0;
if ($rows) {
    foreach ($rows as $r) {
        $totalSaldo += (float)$r['saldo_total'];
    }
}
?>
<!doctype html>
<meta charset="utf-8">
<title>Devedores (≥ <?=NEG_DAYS?> dias)</title>
<style>
  body{font:14px/1.5 Arial, sans-serif; margin:20px; color:#222}
  h2{margin:0 0 8px}
  .meta{color:#555;margin:0 0 15px}
  code{background:#f5f5f5;padding:1px 4px;border-radius:3px}
  table{border-collapse:collapse; width:100%; margin-top:8px}
  th,td{border:1px solid #e5e5e5; padding:6px 8px; vertical-align:top}
  th{background:#f7f7f7; text-align:left}
  td.right{text-align:right; white-space:nowrap}
  .empty{padding:12px; background:#fffbe6; border:1px solid #ffe58f; display:inline-block}
  .totais{margin:8px 0 12px}
</style>

<h2>Clientes com faturas em aberto ≥ <?=NEG_DAYS?> dias</h2>
<p class="meta">
  Gerado em <strong><?=$agora?></strong> ·
  Critério: <code>status='Unpaid'</code>, <code>DATEDIFF(CURDATE(), duedate) ≥ <?=NEG_DAYS?></code> e saldo &gt; 0
</p>

<?php if (!$rows): ?>
  <div class="empty">Nenhum cliente elegível.</div>
<?php else: ?>
  <div class="totais">
    <strong>Total de clientes:</strong> <?=$totalClientes?> ·
    <strong>Saldo agregado:</strong> <?=money_br($totalSaldo)?>
  </div>

  <table>
    <thead>
      <tr>
        <th>ClientID</th>
        <th>Cliente</th>
        <th>Email</th>
        <th class="right">Qtde Faturas</th>
        <th class="right">Saldo</th>
        <th>Primeira vencida</th>
        <th class="right">Dias atraso máx</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?=htmlentities($r['client_id'])?></td>
        <td><?=htmlentities($r['nome'])?></td>
        <td><?=htmlentities($r['email'])?></td>
        <td class="right"><?=htmlentities($r['qtd_faturas'])?></td>
        <td class="right"><?=money_br($r['saldo_total'])?></td>
        <td><?=htmlentities($r['primeira_vencida'])?></td>
        <td class="right"><?=htmlentities($r['dias_atraso_max'])?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
