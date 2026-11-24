<?php
require_once dirname(__DIR__, 3).'/init.php';

use WHMCS\Database\Capsule;

header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; frame-ancestors 'none'; base-uri 'none'; form-action 'self'");

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
$motivos = [
    'financeiro' => 'Dificuldades financeiras',
    'tecnico' => 'Problemas técnicos ou de funcionamento',
    'atendimento' => 'Dificuldade com atendimento ou suporte',
    'cobranca' => 'Problemas com cobrança ou pagamento',
    'uso' => 'Não estou utilizando a hospedagem',
    'outro' => 'Outro motivo',
];
$aviso = '';
if (empty($_SESSION['recuperacao_apoio_csrf'])) {
    $_SESSION['recuperacao_apoio_csrf'] = bin2hex(random_bytes(32));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf'] ?? '';
    $motivo = $_POST['motivo'] ?? '';
    $mensagem = $_POST['mensagem'] ?? '';
    if (!is_string($csrf) || !hash_equals($_SESSION['recuperacao_apoio_csrf'], $csrf)
        || !is_string($motivo) || !isset($motivos[$motivo])
        || !is_string($mensagem) || strlen($mensagem) > 4000) {
        $aviso = 'Confira os dados e tente novamente.';
    } else {
        try {
            Capsule::table('mod_recuperacao_thx')->where('id', $p->id)->where('token', $token)
                ->where('status', 'criada')->update([
                    'motivo' => $motivos[$motivo], 'mensagem_apoio' => trim($mensagem),
                    'apoio_at' => date('Y-m-d H:i:s'),
                ]);
            $aviso = 'Sua mensagem foi registrada para nossa equipe. Obrigado por compartilhar.';
        } catch (\Throwable $e) {
            $aviso = 'Não foi possível registrar sua mensagem neste momento.';
        }
    }
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
<p>Sabemos que imprevistos acontecem. Queremos ajudar você a retomar sua hospedagem e entender se existe algo que podemos melhorar, seja no atendimento, na parte técnica ou nas condições de pagamento.</p>
<p><strong><?= $esc($p->dominio) ?></strong><br><?= $esc($p->produto) ?></p>
<section>
<small>Valor das cobranças anteriores a perdoar</small><br><strong><?= $real($p->perdao) ?></strong>
<p>Valor para regularização<br><span class="valor"><?= $real($p->valor) ?></span></p>
<p>Com a confirmação do pagamento do acordo e a conferência das cobranças, nossa proposta é regularizar a hospedagem, cancelar as faturas negociadas e iniciar um novo ciclo a partir do pagamento.</p>
</section>
<h2>Faturas da proposta</h2>
<table><thead><tr><th>Fatura</th><th>Vencimento</th><th>Valor</th></tr></thead><tbody>
<?php foreach (json_decode($p->faturas, true) as $f): ?>
<tr><td>#<?= (int)$f['id'] ?><?= (int)$f['id'] === (int)$p->fatura_pagamento ? ' (a pagar)' : '' ?></td><td><?= $esc(date('d/m/Y', strtotime($f['vencimento']))) ?></td><td><?= $real($f['valor']) ?></td></tr>
<?php endforeach; ?>
</tbody></table>
<section>
<h2>Como podemos ajudar?</h2>
<p>Se algo dificultou a continuidade do serviço, conte para nós. Sua resposta é opcional e não representa aceite da proposta.</p>
<?php if ($aviso !== ''): ?><p role="status"><strong><?= $esc($aviso) ?></strong></p><?php endif; ?>
<form method="post">
<input type="hidden" name="csrf" value="<?= $esc($_SESSION['recuperacao_apoio_csrf']) ?>">
<label for="motivo">O que dificultou a continuidade?</label><br>
<select id="motivo" name="motivo" required style="width:100%;padding:10px;margin:8px 0">
<option value="">Selecione um motivo</option>
<?php foreach ($motivos as $chave => $rotulo): ?>
<option value="<?= $esc($chave) ?>"><?= $esc($rotulo) ?></option>
<?php endforeach; ?>
</select>
<label for="mensagem">Como nossa equipe pode ajudar? (opcional)</label><br>
<textarea id="mensagem" name="mensagem" rows="4" maxlength="1000" style="width:100%;padding:10px;margin:8px 0" placeholder="Não informe senhas ou dados bancários."></textarea>
<button type="submit" style="padding:12px 18px;background:#173b62;color:white;border:0;border-radius:6px;cursor:pointer">Enviar pedido de ajuda</button>
</form>
</section>
<p><strong>Proposta em conferência.</strong> A opção de pagamento ainda não está disponível.</p>
<footer>Nenhuma cobrança foi alterada. Esta página é exclusivamente para conferência da proposta.</footer>
</main></body></html>
