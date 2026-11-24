<?php
require_once dirname(__DIR__, 3).'/init.php';

use WHMCS\Database\Capsule;

header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; frame-ancestors 'none'; base-uri 'none'");

$token = $_GET['t'] ?? '';
$p = null;
if (is_string($token) && preg_match('/^[a-f0-9]{64}$/D', $token)) {
    try {
        $p = Capsule::table('mod_recuperacao_thx')->where('token', $token)->where('status', 'criada')->first();
    } catch (\Throwable $e) {
        $p = null;
    }
}
if (!$p) {
    http_response_code(404);
    exit('Proposta indisponível.');
}
$esc = static function ($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$real = static function ($v) {
    return 'R$ '.number_format((float)$v, 2, ',', '.');
};
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Proposta de recuperação | Grupo THX</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f1f5f9;color:#172235;font:16px/1.6 Arial,sans-serif;padding:24px}main{max-width:650px;margin:32px auto;background:#fff;padding:32px;border-radius:14px;border:1px solid #dbe2ea}h1{font-size:28px;line-height:1.2}small{color:#526174}section{padding:20px;background:#f4f7fa;border-radius:8px;margin:20px 0}.valor{font-size:36px;font-weight:bold;color:#14663d}table{width:100%;border-collapse:collapse}td,th{text-align:left;padding:8px;border-bottom:1px solid #e2e8f0}footer{font-size:14px;color:#526174}@media(max-width:480px){body{padding:12px}main{padding:20px;margin:12px auto}}
</style>
</head>
<body><main>
<strong>GRUPO THX</strong>
<h1>Recupere sua hospedagem</h1>
<p>Uma oportunidade para regularizar sua hospedagem com condições especiais.</p>
<p><strong><?= $esc($p->dominio) ?></strong><br><?= $esc($p->produto) ?></p>
<section>
<small>Valor das cobranças anteriores a perdoar</small><br><strong><?= $real($p->perdao) ?></strong>
<p>Valor para regularização<br><span class="valor"><?= $real($p->valor) ?></span></p>
<p>Após a confirmação do pagamento da última fatura e a validação da negociação, as cobranças anteriores elegíveis poderão ser canceladas.</p>
</section>
<h2>Faturas da proposta</h2>
<table><thead><tr><th>Fatura</th><th>Vencimento</th><th>Valor</th></tr></thead><tbody>
<?php foreach (json_decode($p->faturas, true) as $f): ?>
<tr><td>#<?= (int)$f['id'] ?><?= (int)$f['id'] === (int)$p->fatura_pagamento ? ' (a pagar)' : '' ?></td><td><?= $esc(date('d/m/Y', strtotime($f['vencimento']))) ?></td><td><?= $real($f['valor']) ?></td></tr>
<?php endforeach; ?>
</tbody></table>
<p><strong>Proposta em conferência.</strong> A opção de pagamento ainda não está disponível.</p>
<footer>Nenhuma cobrança foi alterada. Esta página é exclusivamente para conferência da proposta.</footer>
</main></body></html>
