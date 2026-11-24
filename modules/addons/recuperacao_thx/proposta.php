<?php
require_once dirname(__DIR__, 3).'/init.php';

use WHMCS\Database\Capsule;
require_once __DIR__.'/recuperacao_thx.php';

header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
$nonce = bin2hex(random_bytes(16));
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; img-src 'self' https://pix.grupothx.com; script-src 'nonce-".$nonce."'; frame-ancestors 'none'; base-uri 'none'; form-action 'self'");

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
$erroPix = '';
$pix = '';
$compartilhadas = [];
$expirada = recuperacao_thx_expirada($p);
try {
    if ($expirada) {
        throw new RuntimeException('O prazo desta negociação foi encerrado. Entre em contato com nossa equipe.');
    }
    $p = recuperacao_thx_proposta_atual($p);
    $compartilhadas = $p->compartilhadas;
    // Não contabiliza a conferência feita pelo administrador como acesso do cliente.
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($p->visualizada_at)) {
        try {
            $usuario = new \WHMCS\Authentication\CurrentUser();
            if (!$usuario->isAuthenticatedAdmin() && Capsule::schema()->hasColumn('mod_recuperacao_thx', 'visualizada_at')) {
                Capsule::table('mod_recuperacao_thx')->where('id', $p->id)->where('token', $token)
                    ->whereIn('status', ['criada', 'enviada', 'pix_visualizado'])->whereNull('visualizada_at')
                    ->update(['visualizada_at' => date('Y-m-d H:i:s')]);
            }
        } catch (\Throwable $e) {
            logActivity('Recuperação THX: falha no registro de visualização da negociação #'.(int)$p->id);
        }
    }
} catch (\Throwable $e) {
    $erroPix = get_class($e) === RuntimeException::class ? $e->getMessage() : 'Proposta em revisão. Entre em contato com nossa equipe.';
}
if (empty($_SESSION['recuperacao_apoio_csrf'])) {
    $_SESSION['recuperacao_apoio_csrf'] = bin2hex(random_bytes(32));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pagar_acordo'])) {
    $csrf = $_POST['csrf'] ?? '';
    if (!is_string($csrf) || !hash_equals($_SESSION['recuperacao_apoio_csrf'], $csrf)) {
        http_response_code(403);
        exit('Solicitação inválida.');
    }
}
if ($erroPix === '' && (($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pagar_acordo'])) || $p->status === 'pix_visualizado')) {
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
        $idPix = 'R'.(int)$p->id.'S'.(int)$p->servico_id; // TXID Pix aceita somente letras e números.
        // Mesmo endpoint de Pix simples usado por includes/thx_payment.php.
        $resposta = @file_get_contents('https://pix.grupothx.com/?'.http_build_query([
            'valor' => number_format((float)$p->valor, 2, '.', ''),
            'id' => $idPix,
            'descricao' => 'Renegociação - Serviço '.(int)$p->servico_id,
            'cc' => 'true',
        ]), false, stream_context_create(['http' => ['timeout' => 8, 'follow_location' => 0]]));
        $codigo = $resposta === false ? '' : trim($resposta);
        if (strlen($codigo) > 1024 || !preg_match('/^000201.+6304[0-9A-Fa-f]{4}$/D', $codigo)) {
            throw new RuntimeException('O gerador de Pix simples não retornou um código válido.');
        }
        // Confere os campos EMV de valor, identificador e CRC antes de apresentar a cobrança.
        $lerCampos = static function ($texto) {
            $campos = [];
            for ($pos = 0, $fim = strlen($texto); $pos < $fim;) {
                $cab = substr($texto, $pos, 4);
                if (strlen($cab) !== 4 || !ctype_digit($cab)) {
                    throw new RuntimeException('Código Pix com formato inválido.');
                }
                $tag = substr($cab, 0, 2);
                $tam = (int)substr($cab, 2);
                $pos += 4;
                if ($pos + $tam > $fim || isset($campos[$tag])) {
                    throw new RuntimeException('Código Pix com campos inválidos.');
                }
                $campos[$tag] = substr($texto, $pos, $tam);
                $pos += $tam;
            }
            return $campos;
        };
        $campos = $lerCampos($codigo);
        $referencia = $lerCampos($campos['62'] ?? '')['05'] ?? '';
        $crc = 0xFFFF;
        foreach (str_split(substr($codigo, 0, -4)) as $byte) {
            $crc ^= ord($byte) << 8;
            for ($bit = 0; $bit < 8; $bit++) {
                $crc = (($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1)) & 0xFFFF;
            }
        }
        if (($campos['53'] ?? '') !== '986') {
            throw new RuntimeException('Moeda Pix incompatível: esperado 986 (BRL).');
        }
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $campos['54'] ?? '')
            || number_format((float)$campos['54'], 2, '.', '') !== number_format((float)$p->valor, 2, '.', '')) {
            throw new RuntimeException('Valor Pix divergente: esperado '.number_format((float)$p->valor, 2, '.', '').', recebido '.($campos['54'] ?? '(ausente)').'.');
        }
        if ($referencia !== $idPix) {
            throw new RuntimeException('Identificador Pix divergente: esperado '.$idPix.', recebido '.($referencia !== '' ? $referencia : '(ausente)').'.');
        }
        if (strtoupper(substr($codigo, -4)) !== sprintf('%04X', $crc)) {
            throw new RuntimeException('CRC do Pix inválido: esperado '.sprintf('%04X', $crc).', recebido '.substr($codigo, -4).'.');
        }

        if (!Capsule::schema()->hasColumn('mod_recuperacao_thx', 'pix_notificado_at')) {
            throw new RuntimeException('O módulo precisa de uma atualização administrativa antes de disponibilizar o Pix.');
        }

        // Uma única transição para a primeira exibição, inclusive propostas testadas antes desta versão.
        $primeira = Capsule::table('mod_recuperacao_thx')->where('id', $p->id)->where('token', $token)
            ->whereIn('status', ['criada', 'enviada', 'pix_visualizado'])->whereNull('pix_notificado_at')
            ->update(['status' => 'pix_visualizado', 'aceita_at' => $p->aceita_at ?: date('Y-m-d H:i:s'),
                'pix_notificado_at' => date('Y-m-d H:i:s')]);
        $atual = Capsule::table('mod_recuperacao_thx')->where('id', $p->id)->where('token', $token)
            ->where('status', 'pix_visualizado')->first();
        if (!$atual) {
            throw new RuntimeException('Proposta indisponível.');
        }
        $p = $atual;
        $pix = $codigo;
        if ($primeira) {
            try {
                $email = new \PHPMailer\PHPMailer\PHPMailer();
                $email->CharSet = 'UTF-8';
                $email->From = 'financeiro@grupothx.com.br';
                $email->FromName = 'Grupo THX';
                $email->AddAddress('deljdl@gmail.com', 'Admin');
                $email->Subject = '[RECUPERAÇÃO THX] Negociação iniciada #'.(int)$p->id;
                $email->Body = "Pix disponibilizado na negociação ".$idPix."\nHospedagem: ".$p->dominio
                    ."\nValor: R$ ".number_format((float)$p->valor, 2, ',', '.')
                    ."\nAguardando confirmação manual do pagamento.";
                if (!$email->Send()) {
                    throw new RuntimeException('Envio não confirmado.');
                }
            } catch (\Throwable $erroEmail) {
                logActivity('Recuperação THX: falha no aviso administrativo da negociação #'.(int)$p->id);
            }
        }
    } catch (\Throwable $e) {
        $erroPix = get_class($e) === RuntimeException::class ? $e->getMessage() : 'Falha ao consultar os dados da negociação. Entre em contato com nossa equipe.';
        logActivity('Recuperação THX: Pix indisponível na negociação #'.(int)$p->id);
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
<p>Preparamos uma condição especial para regularizar os débitos de seu serviço.<br>Nossa equipe está à disposição para auxiliá-lo.</p>
<p><strong><?= $esc($p->dominio) ?></strong><br><?= $esc($p->produto) ?></p>
<section>
<small>Valor total devido</small><br><strong><del><?= $real((float)$p->valor + (float)$p->perdao) ?></del></strong><br><small>Desconto concedido: <?= $real($p->perdao) ?></small>
<p>Valor para regularização<br><span class="valor"><?= $real($p->valor) ?></span></p>
<p>Com a confirmação do pagamento do acordo, cancelaremos as faturas em aberto incluídas nesta negociação.</p>
</section>
<details><summary>Faturas que serão canceladas (<?= count(json_decode($p->faturas, true)) ?>)</summary>
<table><thead><tr><th>Fatura</th><th>Vencimento</th><th>Valor</th></tr></thead><tbody>
<?php foreach (json_decode($p->faturas, true) as $f): ?>
<tr><td>#<?= (int)$f['id'] ?></td><td><?= $esc(date('d/m/Y', strtotime($f['vencimento']))) ?></td><td><?= $real($f['valor']) ?></td></tr>
<?php endforeach; ?>
</tbody></table></details>
<?php if ($compartilhadas): ?>
<p><small>Faturas com outros serviços ou domínios não fazem parte do acordo: <?= $esc(implode(', ', array_map(static function ($id) { return '#'.(int)$id; }, $compartilhadas))) ?>. Essas cobranças precisam de conferência separada.</small></p>
<?php endif; ?>
<?php if ($pix === '' && $erroPix === ''): ?>
<form method="post">
<input type="hidden" name="csrf" value="<?= $esc($_SESSION['recuperacao_apoio_csrf']) ?>">
<p><button class="pagar" type="submit" name="pagar_acordo" value="1"><?= $p->status === 'pix_visualizado' ? 'Tentar carregar Pix novamente' : 'Quero pagar o acordo' ?></button></p>
</form>
<?php endif; ?>
<?php if ($erroPix !== ''): ?>
<p role="alert" style="color:#a12626"><strong><?= $esc($erroPix) ?></strong></p>
<?php endif; ?>
<?php if ($pix !== ''): ?>
<section id="pagamento" style="text-align:center">
<h2>Pagamento via Pix</h2>
<p><strong><?= $real($p->valor) ?></strong> — <?= $esc($idPix) ?></p>
<img alt="QR Code Pix" style="width:100%;max-width:340px;height:auto" src="https://pix.grupothx.com/copiaecolatoqrcode.php?codigo=<?= $esc(rawurlencode($pix)) ?>">
<p><label for="pixCode">Pix copia e cola</label></p>
<textarea id="pixCode" readonly rows="3" style="width:100%;overflow-wrap:anywhere"><?= $esc($pix) ?></textarea>
<button class="pagar" type="button" id="copiarPix" style="cursor:pointer">Copiar código Pix</button>
<p>Após o pagamento, os débitos anteriores do serviço serão cancelados.</p>
</section>
<script nonce="<?= $esc($nonce) ?>">
<?php if (isset($_POST['pagar_acordo'])): ?>document.getElementById('pagamento').scrollIntoView({block:'start'});<?php endif; ?>
let timerCopiarPix;
document.getElementById('copiarPix').addEventListener('click',async function(){
    const campo=document.getElementById('pixCode');
    try {
        await navigator.clipboard.writeText(campo.value);
        this.textContent='Código copiado';
        clearTimeout(timerCopiarPix);
        timerCopiarPix = setTimeout(() => { this.textContent='Copiar código Pix'; }, 2000);
    }
    catch(e) { campo.focus(); campo.select(); }
});
</script>
<?php endif; ?>
<footer>
<input type="hidden" id="botCall" value="{bom} <?= $esc($clienteNome ?: 'cliente') ?>{nl1}Aproveite nossa proposta e pague apenas R$ <?= $real($p->valor) ?>{nl1}Se tiver dúvidas é só fala comigo 🙌😉{end}" >    
<script defer src="https://autoresponder.grupothx.com.br/widget/"></script></footer>

</main></body></html>
