<?php
require_once dirname(__DIR__, 3).'/init.php';

use WHMCS\Database\Capsule;

header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; img-src 'self'; frame-ancestors 'none'; base-uri 'none'; form-action 'self'");

$token = $_GET['t'] ?? '';
$p = null;
if (is_string($token) && preg_match('/^[a-f0-9]{64}$/D', $token)) {
    try {
        $p = Capsule::table('mod_recuperacao_thx')->where('token', $token)->whereIn('status', ['criada', 'enviada', 'pix_visualizado'])->first();
    } catch (\Throwable $e) {
        $p = null;
    }
}
if (!$p) {
    http_response_code(404);
    exit('Proposta indisponível.');
}
$clienteNome = Capsule::table('tblclients')->where('id', $p->cliente_id)->value('firstname');
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pagar_acordo'])) {
    $csrf = $_POST['csrf'] ?? '';
    if (!is_string($csrf) || !hash_equals($_SESSION['recuperacao_apoio_csrf'], $csrf)) {
        http_response_code(403);
        exit('Solicitação inválida.');
    }
    try {
        $dados = json_decode($p->faturas, true);
        if (!$dados || (float)$p->valor <= 0) {
            throw new RuntimeException('Proposta inválida.');
        }
        foreach ($dados as $fatura) {
            $i = Capsule::table('tblinvoices')->where('id', (int)$fatura['id'])
                ->where('userid', $p->cliente_id)->first();
            if (!$i || $i->status !== 'Unpaid' || (float)$i->credit != 0
                || number_format((float)$i->total, 2, '.', '') !== number_format((float)$fatura['valor'], 2, '.', '')
                || Capsule::table('tblaccounts')->where('invoiceid', $i->id)
                    ->where(function ($q) {
                        $q->where('amountin', '!=', 0)->orWhere('amountout', '!=', 0)->orWhere('fees', '!=', 0);
                    })->exists()) {
                throw new RuntimeException('As cobranças foram alteradas. Solicite uma revisão da proposta.');
            }
            $itens = Capsule::table('tblinvoiceitems')->where('invoiceid', $i->id)->get();
            if (!$itens->count()) {
                throw new RuntimeException('Fatura sem itens.');
            }
            foreach ($itens as $item) {
                if ($item->type !== 'Hosting' || (int)$item->relid !== (int)$p->servico_id) {
                    throw new RuntimeException('A composição das cobranças foi alterada.');
                }
            }
        }
        $servico = Capsule::table('tblhosting as h')->join('tblproducts as pr', 'pr.id', '=', 'h.packageid')
            ->where('h.id', $p->servico_id)->where('h.userid', $p->cliente_id)
            ->whereIn('h.domainstatus', ['Active', 'Suspended'])
            ->whereIn('pr.type', ['hostingaccount', 'reselleraccount'])->exists();
        if (!$servico) {
            throw new RuntimeException('Hospedagem indisponível.');
        }
        if ($p->status !== 'pix_visualizado') {
            $alteradas = Capsule::table('mod_recuperacao_thx')->where('id', $p->id)
                ->where('token', $token)->whereIn('status', ['criada', 'enviada'])
                ->update(['status' => 'pix_visualizado', 'aceita_at' => date('Y-m-d H:i:s')]);
            if (!$alteradas) {
                throw new RuntimeException('Proposta indisponível.');
            }
        }
        $urlPix = 'https://pix.grupothx.com/cobranca-layout?'.http_build_query([
            'id' => 'RENEG-'.(int)$p->id,
            'generate' => 'true',
            'conta' => 'nujuridica',
            'valor' => number_format((float)$p->valor, 2, '.', ''),
            'descricao' => 'Grupo THX - Renegociação #'.(int)$p->id,
        ]);
        header('Location: '.$urlPix, true, 303);
        exit;
    } catch (\Throwable $e) {
        $aviso = 'Não foi possível disponibilizar o Pix. Entre em contato para conferência da proposta.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                ->whereIn('status', ['criada', 'enviada', 'pix_visualizado'])->update([
                    'motivo' => $motivos[$motivo], 'mensagem_apoio' => trim($mensagem),
                    'apoio_at' => date('Y-m-d H:i:s'),
                ]);
            $aviso = 'Sua solicitação foi registrada. Nossa equipe poderá avaliar as informações e orientar você.';
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
*{box-sizing:border-box}body{margin:0;background:#f1f5f9;color:#172235;font:16px/1.6 Arial,sans-serif;padding:24px}main{max-width:650px;margin:32px auto;background:#fff;padding:32px;border-radius:14px;border:1px solid #dbe2ea}h1{font-size:28px;line-height:1.2}small{color:#526174}section{padding:20px;background:#f4f7fa;border-radius:8px;margin:20px 0}.valor{font-size:36px;font-weight:bold;color:#14663d}table{width:100%;border-collapse:collapse}td,th{text-align:left;padding:8px;border-bottom:1px solid #e2e8f0}footer{font-size:13px;color:#526174}.cabecalho{aspect-ratio:1200/630;background:#fff url('../../../thx/assets/images/cabecalho-negociacao.jpg') center/cover no-repeat;margin:-18px -18px 16px;border-bottom:1px solid #e2e8f0;border-radius:12px 12px 0 0}main{padding:18px;max-width:560px;margin:12px auto}h1{font-size:24px;margin:8px 0}h2{font-size:18px;margin:8px 0}p{margin:8px 0}section{padding:14px;margin:12px 0}.valor{font-size:30px}details{border:1px solid #e2e8f0;border-radius:8px;padding:12px;margin:10px 0}summary{cursor:pointer;font-weight:bold}.apoio{background:#f4f7fa}.pagar{width:100%;padding:13px;border:0;border-radius:6px;background:#173b62;color:white;font:600 16px Arial,sans-serif}.pagar:disabled{opacity:.6}input,select,textarea,button{font:inherit}@media(max-width:480px){body{padding:8px}main{padding:14px;margin:0 auto}.cabecalho{margin:-14px -14px 12px}h1{font-size:22px}td,th{padding:6px;font-size:13px}}
</style>
</head>
<body><main>
<div class="cabecalho" role="img" aria-label="Grupo THX"></div>
<h1>Olá, <?= $esc($clienteNome ?: 'cliente') ?>!</h1>
<h2>Proposta de regularização</h2>
<p>Preparamos uma condição especial para regularizar sua hospedagem. Nossa equipe está à disposição para auxiliá-lo.</p>
<p><strong><?= $esc($p->dominio) ?></strong><br><?= $esc($p->produto) ?></p>
<section>
<small>Valor total devido</small><br><strong><?= $real((float)$p->valor + (float)$p->perdao) ?></strong><br><small>Desconto concedido: <?= $real($p->perdao) ?></small>
<p>Valor para regularização<br><span class="valor"><?= $real($p->valor) ?></span></p>
<p>Com a confirmação do pagamento do acordo, cancelaremos as faturas em aberto para esse serviço.</p>
</section>
<details><summary>Faturas da proposta (<?= count(json_decode($p->faturas, true)) ?>)</summary>
<table><thead><tr><th>Fatura</th><th>Vencimento</th><th>Valor</th></tr></thead><tbody>
<?php foreach (json_decode($p->faturas, true) as $f): ?>
<tr><td>#<?= (int)$f['id'] ?></td><td><?= $esc(date('d/m/Y', strtotime($f['vencimento']))) ?></td><td><?= $real($f['valor']) ?></td></tr>
<?php endforeach; ?>
</tbody></table></details>
<details class="apoio" <?= $aviso !== '' ? 'open' : '' ?>><summary>Como podemos ajudá-lo?</summary>
<p>Caso tenha enfrentado dificuldades, informe-nos para que possamos avaliar sua situação. O preenchimento é opcional.</p>
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
<button type="submit" style="padding:12px 18px;background:#173b62;color:white;border:0;border-radius:6px;cursor:pointer">Solicitar atendimento</button>
</form>
</details>
<form method="post">
<input type="hidden" name="csrf" value="<?= $esc($_SESSION['recuperacao_apoio_csrf']) ?>">
<p><button class="pagar" type="submit" name="pagar_acordo" value="1"><?= $p->status === 'pix_visualizado' ? 'Visualizar Pix novamente' : 'Quero pagar o acordo' ?></button></p>
</form>
<footer>A regularização será realizada após a confirmação do recebimento do Pix pela nossa equipe.</footer>
</main></body></html>
