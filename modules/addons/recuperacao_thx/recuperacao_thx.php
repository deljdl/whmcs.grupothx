<?php

use WHMCS\Database\Capsule;

if (!defined('WHMCS')) {
    exit('Acesso restrito');
}

function recuperacao_thx_config()
{
    return [
        'name' => 'Recuperação de Hospedagens THX',
        'description' => 'Identificação de hospedagens inadimplentes para recuperação ou encerramento.',
        'version' => '1.2.0',
        'author' => 'Grupo THX',
    ];
}


function recuperacao_thx_propostas_tabela()
{
    if (!Capsule::schema()->hasTable('mod_recuperacao_thx')) {
        Capsule::schema()->create('mod_recuperacao_thx', function ($t) {
            $t->increments('id');
            $t->unsignedInteger('servico_id')->unique();
            $t->unsignedInteger('cliente_id');
            $t->string('token', 64)->unique();
            $t->string('dominio', 255);
            $t->string('produto', 255);
            $t->unsignedInteger('fatura_pagamento');
            $t->text('faturas');
            $t->decimal('valor', 16, 2);
            $t->decimal('perdao', 16, 2);
            $t->string('status', 32)->default('criada');
            $t->dateTime('created_at');
        });
    }
    // Atualização aditiva: preserva negociações existentes.
    $campos = [
        'enviada_at' => 'dateTime', 'aceita_at' => 'dateTime',
        'aviso_final_at' => 'dateTime', 'pagamento_at' => 'dateTime',
        'concluida_at' => 'dateTime', 'motivo' => 'string',
        'mensagem_apoio' => 'text', 'apoio_at' => 'dateTime', 'pix_notificado_at' => 'dateTime', 'visualizada_at' => 'dateTime', 'url_curta' => 'string', 'ultimo_envio_at' => 'dateTime', 'controle_envios' => 'text',
    ];
    foreach ($campos as $nome => $tipo) {
        if (!Capsule::schema()->hasColumn('mod_recuperacao_thx', $nome)) {
            Capsule::schema()->table('mod_recuperacao_thx', function ($t) use ($nome, $tipo) {
                $t->$tipo($nome)->nullable();
            });
        }
    }
}

function recuperacao_thx_criar_proposta($registro, $simular = false)
{
    $s = $registro['servico'];
    $hoje = (new DateTimeImmutable('today', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
    $faturas = array_values(array_filter($registro['faturas'], static function ($f) use ($hoje) {
        return $f->duedate <= $hoje;
    }));
    if (!$faturas) {
        throw new RuntimeException('Não há faturas vencidas elegíveis.');
    }

    $dados = [];
    $compartilhadas = [];
    foreach ($faturas as $f) {
        $prefixo = 'Fatura #'.(int)$f->fatura_id.': ';
        $i = Capsule::table('tblinvoices')->where('id', $f->fatura_id)->where('userid', $s->userid)->first();
        if (!$i) {
            throw new RuntimeException($prefixo.'não encontrada para este cliente.');
        }
        $motivos = [];
        if ($i->status !== 'Unpaid') {
            $motivos[] = 'status atual: '.$i->status.' (esperado: Unpaid)';
        }
        $itens = Capsule::table('tblinvoiceitems')->where('invoiceid', $f->fatura_id)->get();
        $outrosItens = $itens->filter(static function ($item) use ($s) {
            return $item->type !== 'Hosting' || (int)$item->relid !== (int)$s->id;
        });
        if ($outrosItens->count()) {
            $compartilhadas[] = (int)$i->id;
            continue;
        }
        if (!$itens->count()) {
            $motivos[] = 'nenhum item encontrado';
        }
        if ((float)$i->total <= 0) {
            $motivos[] = 'valor total inválido: R$ '.number_format((float)$i->total, 2, ',', '.');
        }
        if ((float)$i->credit != 0) {
            $motivos[] = 'crédito aplicado: R$ '.number_format((float)$i->credit, 2, ',', '.');
        }
        $transacoes = Capsule::table('tblaccounts')->where('invoiceid', $f->fatura_id)
            ->get(['id', 'gateway', 'transid', 'amountin', 'amountout', 'fees']);
        foreach ($transacoes as $tr) {
            // Aceita somente referências conhecidas de controle, sem valores financeiros.
            $zerado = (float)$tr->amountin == 0 && (float)$tr->amountout == 0 && (float)$tr->fees == 0;
            $referencia = (string)$tr->transid;
            $controleEfi = $tr->gateway === 'gofasgerencianetboleto'
                && preg_match('/^ggnb_(?:live|sandbox)_(?:(?:waiting|unpaid|canceled|cancelled)-[0-9]+|_notification-[0-9]+\[(?:new|waiting|unpaid|canceled|cancelled|expired)\])$/D', $referencia);
            $controleAsaas = $tr->gateway === 'asaas'
                && preg_match('/^asaas_(?:production|sandbox)_(?:waiting-pay_[A-Za-z0-9]+|_notification-pay_[A-Za-z0-9]+\[(?:PAYMENT_CREATED|PAYMENT_UPDATED|PAYMENT_OVERDUE|PAYMENT_DELETED|PAYMENT_RESTORED|PAYMENT_BANK_SLIP_VIEWED|PAYMENT_CHECKOUT_VIEWED)\])$/D', $referencia);
            if ($zerado && ($controleEfi || $controleAsaas)) {
                continue;
            }
            $motivos[] = 'registro #'.(int)$tr->id.' em tblaccounts (gateway: '.$tr->gateway
                .', entrada: R$ '.number_format((float)$tr->amountin, 2, ',', '.')
                .', saída: R$ '.number_format((float)$tr->amountout, 2, ',', '.')
                .', taxas: R$ '.number_format((float)$tr->fees, 2, ',', '.').')'
                .', referência: '.$referencia
                .($zerado ? ' — registro zerado não reconhecido como controle seguro' : ' — movimentação financeira');
        }
        if ($motivos) {
            throw new RuntimeException($prefixo."\n".implode("\n", $motivos).'.');
        }
        $dados[] = ['id' => (int)$i->id, 'vencimento' => $i->duedate, 'valor' => (string)$i->total];
    }

    if (!$dados) {
        throw new RuntimeException('Não há faturas exclusivas do serviço disponíveis para negociação.');
    }
    $ultima = end($dados);
    $perdao = 0;
    foreach (array_slice($dados, 0, -1) as $item) {
        $perdao += (int)round((float)$item['valor'] * 100);
    }

    $proposta = [
        'servico_id' => $s->id, 'cliente_id' => $s->userid,
        'token' => $simular ? null : bin2hex(random_bytes(32)), 'dominio' => $s->domain,
        'produto' => $s->produto, 'fatura_pagamento' => $ultima['id'],
        'faturas' => json_encode($dados), 'valor' => $ultima['valor'],
        'perdao' => number_format($perdao / 100, 2, '.', ''),
        'status' => 'criada', 'created_at' => date('Y-m-d H:i:s'),
    ];
    if ($simular) {
        $proposta['compartilhadas'] = $compartilhadas;
        return (object)$proposta;
    }
    Capsule::table('mod_recuperacao_thx')->insert($proposta);
}

// Prazo contado do primeiro envio, sem reiniciar a cada lembrete.
function recuperacao_thx_expirada($p)
{
    if (empty($p->enviada_at)) {
        return false;
    }
    $inicio = new DateTimeImmutable($p->enviada_at, new DateTimeZone('America/Sao_Paulo'));
    $mes = $inicio->modify('first day of this month')->modify('+4 months');
    $fim = $mes->setDate((int)$mes->format('Y'), (int)$mes->format('m'),
        min((int)$inicio->format('d'), (int)$mes->format('t')));
    return new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo')) >= $fim;
}

// Consulta as faturas vencidas atuais, preservando o valor pactuado e o registro original.
function recuperacao_thx_proposta_atual($p)
{
    $s = Capsule::table('tblhosting as h')->join('tblproducts as pr', 'pr.id', '=', 'h.packageid')
        ->where('h.id', $p->servico_id)->where('h.userid', $p->cliente_id)
        ->whereIn('h.domainstatus', ['Active', 'Suspended'])->whereIn('pr.type', ['hostingaccount', 'reselleraccount'])
        ->first(['h.id', 'h.userid', 'h.domain', 'pr.name as produto']);
    if (!$s) {
        throw new RuntimeException('Hospedagem indisponível.');
    }
    $faturas = Capsule::table('tblinvoices as i')->join('tblinvoiceitems as ii', 'ii.invoiceid', '=', 'i.id')
        ->where('i.userid', $p->cliente_id)->where('i.status', 'Unpaid')
        ->where('ii.type', 'Hosting')->where('ii.relid', $p->servico_id)
        ->where('i.duedate', '>', '0000-00-00')
        ->select('i.id as fatura_id', 'i.duedate')->distinct()->orderBy('i.duedate')->orderBy('i.id')->get()->all();
    $atual = recuperacao_thx_criar_proposta(['servico' => $s, 'faturas' => $faturas], true);
    $dados = json_decode($atual->faturas, true);
    $originais = json_decode($p->faturas, true);
    if (!$originais || !$dados) {
        throw new RuntimeException('Proposta inválida.');
    }
    $porId = array_column($dados, null, 'id');
    foreach ($originais as $f) {
        if (!isset($porId[$f['id']]) || (float)$porId[$f['id']]['valor'] !== (float)$f['valor']) {
            throw new RuntimeException('Uma fatura original foi alterada. A proposta precisa de revisão.');
        }
    }
    $total = array_sum(array_map(static function ($f) {
        return (int)round((float)$f['valor'] * 100);
    }, $dados));
    $valor = (int)round((float)$p->valor * 100);
    if ($valor <= 0 || $total < $valor) {
        throw new RuntimeException('Valor da proposta incompatível.');
    }
    $atualizada = clone $p;
    $atualizada->faturas = $atual->faturas;
    $atualizada->perdao = number_format(($total - $valor) / 100, 2, '.', '');
    $atualizada->compartilhadas = $atual->compartilhadas;
    return $atualizada;
}

function recuperacao_thx_servicos($hoje, $limite2)
{
    // Apenas produtos Shared Hosting e Reseller Hosting; não inclui VPS, domínios ou outros serviços.
    $faturas = Capsule::table('tblhosting as h')
        ->join('tblproducts as p', 'p.id', '=', 'h.packageid')
        ->join('tblclients as c', 'c.id', '=', 'h.userid')
        ->join('tblinvoiceitems as ii', function ($join) {
            $join->on('ii.relid', '=', 'h.id')->where('ii.type', '=', 'Hosting');
        })
        ->join('tblinvoices as i', function ($join) {
            $join->on('i.id', '=', 'ii.invoiceid')->on('i.userid', '=', 'h.userid');
        })
        ->whereIn('p.type', ['hostingaccount', 'reselleraccount'])
        ->whereIn('h.domainstatus', ['Active', 'Suspended'])
        ->where('i.status', 'Unpaid')
        ->where('i.duedate', '>', '0000-00-00')
        ->select('h.id', 'h.userid', 'h.domain', 'h.domainstatus', 'p.name as produto',
            'c.firstname', 'c.lastname', 'c.companyname', 'i.id as fatura_id', 'i.duedate')
        ->selectRaw('SUM(ii.amount) as valor')
        ->groupBy('h.id', 'h.userid', 'h.domain', 'h.domainstatus', 'p.name',
            'c.firstname', 'c.lastname', 'c.companyname', 'i.id', 'i.duedate')
        ->havingRaw('SUM(ii.amount) > 0')
        ->orderBy('i.duedate')->orderBy('i.id')->get();

    $servicos = [];
    foreach ($faturas as $fatura) {
        if (!isset($servicos[$fatura->id])) {
            if ($fatura->duedate > $limite2) {
                continue;
            }
            $servicos[$fatura->id] = ['servico' => $fatura, 'faturas' => [], 'valor' => 0];
        }
        $servicos[$fatura->id]['faturas'][] = $fatura;
        if ($fatura->duedate < $hoje->format('Y-m-d')) {
            $servicos[$fatura->id]['valor'] += (float)$fatura->valor;
        }
    }

    return [$faturas, $servicos];
}

function recuperacao_thx_link($p, $urlBase)
{
    $url = $urlBase.'/modules/addons/recuperacao_thx/proposta.php?t='.$p->token;
    if (!empty($p->url_curta) && preg_match('~^https://l\.grupothx\.com\.br/[^\s]+$~D', $p->url_curta)) {
        return $p->url_curta;
    }
    try {
        require_once dirname(__DIR__, 3).'/tarifasTHX.php';
        $curta = getUrlCurta($url, 'Condição especial para regularizar seus débitos',
            'Confira sua proposta de regularização', '', $urlBase.'/thx/assets/images/cabecalho-negociacao.jpg', 5);
        if (is_string($curta) && preg_match('~^https://l\.grupothx\.com\.br/[^\s]+$~D', $curta)) {
            Capsule::table('mod_recuperacao_thx')->where('id', $p->id)->where('token', $p->token)
                ->update(['url_curta' => $curta]);
            $p->url_curta = $curta;
            return $curta;
        }
    } catch (\Throwable $e) {
        logActivity('Recuperação THX: falha ao encurtar proposta #'.(int)$p->id);
    }
    return $url;
}


function recuperacao_thx_prever_cancelamentos($p)
{
    $atual = recuperacao_thx_proposta_atual($p);
    $ids = array_column(json_decode($atual->faturas, true), 'id');
    $linhas = [];
    foreach ($ids as $id) {
        $f = Capsule::table('tblinvoices')->where('id', $id)->first(['id', 'total', 'paymentmethod']);
        $linhas[] = '#'.$id.' R$ '.$f->total.' ('.$f->paymentmethod.')';
    }
    return 'SIMULAÇÃO: conferir e cancelar cobranças nos gateways e faturas exclusivas: '.implode('; ', $linhas)
        .'. Não cancelar compartilhadas: '.implode(', ', $atual->compartilhadas).'. Conferir pagamentos externos antes de qualquer alteração.';
}

function recuperacao_thx_prever_encerramento($p)
{
    return recuperacao_thx_prever_cancelamentos($p).'; SIMULAÇÃO: finalizar somente serviço #'.(int)$p->servico_id.' ('.$p->dominio.'). Conferir Pix e aviso final antes da exclusão.';
}

function recuperacao_thx_comunicar($p, $tipo)
{
    require_once dirname(__DIR__, 3).'/tarifasTHX.php';
    $base = rtrim((string)Capsule::table('tblconfiguration')->where('setting', 'SystemURL')->value('value'), '/');
    $link = recuperacao_thx_link($p, $base);
    if (!preg_match('~^https://l\.grupothx\.com\.br/[^\s]+$~D', $link)) {
        throw new RuntimeException('Link curto indisponível; nenhum envio realizado.');
    }
    $cliente = Capsule::table('tblclients')->where('id', $p->cliente_id)->first();
    if (!$cliente) { throw new RuntimeException('Cliente não encontrado.'); }
    $imagem = $base.'/thx/assets/images/cabecalho-negociacao.jpg';
    $assunto = 'Condição especial para regularizar seus débitos';
    $original = 'R$ '.number_format((float)$p->valor + (float)$p->perdao, 2, ',', '.');
    $valor = 'R$ '.number_format((float)$p->valor, 2, ',', '.');
    $msg = "Olá, ".$cliente->firstname."!\n\nPreparamos uma condição especial para regularizar os débitos de seu serviço de hospedagem (".$p->dominio.").\n\nDe ".$original." por apenas ".$valor.".\n\nCom a confirmação do pagamento, os débitos anteriores serão cancelados e o serviço será reativado.\n\nConfira sua proposta:\n".$link;
    if ($tipo === 'final') {
        $assunto = 'Aviso final para regularizar sua hospedagem';
        $msg = "Atenção: o prazo para regularizar sua hospedagem está próximo do encerramento. Após esse prazo, o serviço poderá ser finalizado e seus arquivos e e-mails excluídos definitivamente.\n\n".$msg;
    }
    $controle = json_decode($p->controle_envios ?? '', true) ?: [];
    $chave = $tipo === 'final' ? 'final' : 'proposta';
    $agora = date('Y-m-d H:i:s');
    $lote = $controle[$chave] ?? [];
    if ($tipo === 'reenvio' && !empty($lote['concluido'])) { $lote = []; }
    $salvar = static function () use (&$controle, &$lote, $chave, $p) {
        $controle[$chave] = $lote;
        Capsule::table('mod_recuperacao_thx')->where('id', $p->id)->update(['controle_envios' => json_encode($controle)]);
    };
    $resultado = [];
    foreach (['email', 'whatsapp'] as $canal) {
        if (isset($lote[$canal])) {
            $resultado[] = $canal.': '.$lote[$canal];
            continue;
        }
        // Registra antes da chamada externa: resultado incerto não será reenviado automaticamente.
        $lote[$canal] = 'tentativa incerta';
        $salvar();
        try {
            if ($canal === 'email') {
                if (!filter_var($cliente->email, FILTER_VALIDATE_EMAIL)) { throw new RuntimeException('E-mail inválido'); }
                $esc = static function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
                $html = '<div style="max-width:600px;margin:auto;font-family:Arial"><img src="'.$esc($imagem).'" alt="Grupo THX" style="width:100%;height:auto"><p>'.nl2br($esc($msg)).'</p><p><a href="'.$esc($link).'" style="display:inline-block;padding:12px;background:#173b62;color:white">Abrir proposta</a></p></div>';
                $retorno = enviaEmailTHX('', $cliente->id, $assunto, $html, true);
                if (($retorno['result'] ?? '') !== 'success') {
                    throw new RuntimeException('WHMCS: '.($retorno['message'] ?? 'Envio não confirmado'));
                }
                $lote[$canal] = 'aceito';
            } else {
                // A preferência de bloqueio do WhatsApp existente é o primeiro campo customizado de cliente.
                $campo = Capsule::table('tblcustomfields')->where('type', 'client')->orderBy('sortorder')->orderBy('id')->first(['id', 'fieldname']);
                if (!$campo || !preg_match('/whats/i', $campo->fieldname)) {
                    $lote[$canal] = 'bloqueado: conferir campo de preferência WhatsApp';
                } elseif (Capsule::table('tblcustomfieldsvalues')->where('fieldid', $campo->id)->where('relid', $cliente->id)->value('value') === 'on') {
                    $lote[$canal] = 'bloqueado pelo cliente';
                } else {
                    $telefone = normalizaTelefone($cliente->phonenumber);
                    if ($telefone === '') { throw new RuntimeException('Telefone inválido'); }
                    $ch = curl_init('https://autoresponder.grupothx.com.br/api/createMessage');
                    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => json_encode(['whatsnumber' => $telefone, 'mensagem' => $msg,
                            'imagemcabecalho' => $imagem, 'tipo_msg' => 2, 'arquivo_local' => 'Confira sua negociação',
                            'tipo_arquivo' => $assunto, 'url_arquivo' => $link, 'prioridade' => 0]),
                        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
                        CURLOPT_CONNECTTIMEOUT => 2, CURLOPT_TIMEOUT => 5, CURLOPT_FOLLOWLOCATION => false]);
                    $body = curl_exec($ch);
                    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    $resp = json_decode((string)$body, true);
                    if ($http !== 202 || ($resp['status'] ?? $resp['success'] ?? false) !== true) {
                        throw new RuntimeException('Fila não confirmada (HTTP '.$http.')');
                    }
                    $lote[$canal] = 'na fila';
                }
            }
        } catch (\Throwable $e) {
            $lote[$canal] = 'revisão necessária: '.(get_class($e) === RuntimeException::class ? $e->getMessage() : 'falha não confirmada');
        }
        $salvar();
        $resultado[] = $canal.': '.$lote[$canal];
    }
    $ok = ($lote['email'] ?? '') === 'aceito' || ($lote['whatsapp'] ?? '') === 'na fila';
    if ($ok && empty($lote['concluido'])) {
        $update = ['ultimo_envio_at' => $agora];
        if (empty($p->enviada_at)) {
            $update['enviada_at'] = $agora;
        }
        if ($tipo === 'final') { $update['aviso_final_at'] = $agora; }
        Capsule::table('mod_recuperacao_thx')->where('id', $p->id)->update($update);
        Capsule::table('mod_recuperacao_thx')->where('id', $p->id)->where('status', 'criada')->update(['status' => 'enviada']);
        $lote['concluido'] = $agora;
        $salvar();
    }
    return implode('; ', $resultado);
}

function recuperacao_thx_output($vars)
{
    $hoje = new DateTimeImmutable('today', new DateTimeZone('America/Sao_Paulo'));
    // Subtração de meses pelo calendário, sem transbordar para o mês seguinte.
    $limite = static function ($meses) use ($hoje) {
        $mes = $hoje->modify('first day of this month')->modify('-'.$meses.' months');
        return $mes->setDate((int)$mes->format('Y'), (int)$mes->format('m'),
            min((int)$hoje->format('d'), (int)$mes->format('t')))->format('Y-m-d');
    };
    $limite2 = $limite(2);
    $esc = static function ($valor) {
        return htmlspecialchars((string)$valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    };

    [$faturas, $servicos] = recuperacao_thx_servicos($hoje, $limite2);

    $recuperacao = $servicos;

    recuperacao_thx_propostas_tabela();
    if (empty($_SESSION['recuperacao_thx_csrf'])) {
        $_SESSION['recuperacao_thx_csrf'] = bin2hex(random_bytes(32));
    }
    $propostas = Capsule::table('mod_recuperacao_thx')->get()->keyBy('servico_id');
    $urlBase = rtrim((string)Capsule::table('tblconfiguration')->where('setting', 'SystemURL')->value('value'), '/');
    echo '<div class="alert alert-info">Envio real de propostas por e-mail e WhatsApp. Cancelamentos de faturas e finalização de serviços permanecem em simulação.</div>';
    echo '<p>Data de referência: '.$hoje->format('d/m/Y').' | Elegíveis a partir de 2 meses de atraso: '.$esc($limite2).'</p>';
    echo '<form id="simulacao-negociacoes" method="post"><input type="hidden" name="csrf_recuperacao" value="'.$esc($_SESSION['recuperacao_thx_csrf']).'">
        <label><input type="checkbox" id="selecionar-todas-negociacoes"> Selecionar todas para negociação</label>
        <button type="submit" class="btn btn-primary" name="enviar_negociacoes" value="1" onclick="return confirm(&quot;Enviar propostas reais aos serviços selecionados?&quot;)">Enviar propostas (e-mail e WhatsApp)</button></form>';
    echo '<script>document.getElementById("selecionar-todas-negociacoes").addEventListener("change",function(){document.querySelectorAll(".selecionar-negociacao").forEach(e=>e.checked=this.checked);});</script>';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['enviar_negociacoes']) || isset($_POST['criar_proposta']))) {
        if (!is_string($_POST['csrf_recuperacao'] ?? null) || !hash_equals($_SESSION['recuperacao_thx_csrf'], $_POST['csrf_recuperacao'])) {
            echo '<div class="alert alert-warning">Solicitação inválida.</div>';
        } else {
            $selecionados = isset($_POST['criar_proposta']) ? [(int)$_POST['criar_proposta']] : array_map('intval', (array)($_POST['servicos_simulacao'] ?? []));
            echo '<h2>Resultado do envio</h2>';
            $relatorioEnvio = [];
            if (!$selecionados) {
                echo '<p>Selecione ao menos uma hospedagem.</p>';
            }
            $lock = fopen(sys_get_temp_dir().'/thx-recuperacao-cron.lock', 'c');
            if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
                echo '<div class="alert alert-warning">Outra execução de recuperação está em andamento.</div>';
            } else {
                try {
                    foreach ($servicos as $registro) {
                        $s = $registro['servico'];
                        if (!in_array((int)$s->id, $selecionados, true)) { continue; }
                        try {
                            $p = Capsule::table('mod_recuperacao_thx')->where('servico_id', $s->id)->first();
                            if (!$p) {
                                recuperacao_thx_criar_proposta($registro);
                                $p = Capsule::table('mod_recuperacao_thx')->where('servico_id', $s->id)->first();
                            }
                            if (!$p || $p->status !== 'criada' || !empty($p->enviada_at) || recuperacao_thx_expirada($p)) {
                                throw new RuntimeException('Proposta já enviada, iniciada ou encerrada.');
                            }
                            $p = recuperacao_thx_proposta_atual($p);
                            $resultado = recuperacao_thx_comunicar($p, 'inicial');
                            $relatorioEnvio[] = $s->domain.': '.$resultado;
                            echo '<div class="alert alert-info">'.$esc($s->domain).': '.$esc($resultado).'</div>';
                        } catch (\Throwable $e) {
                            $relatorioEnvio[] = $s->domain.': '.(get_class($e) === RuntimeException::class ? $e->getMessage() : 'Falha no processamento; verificar logs.');
                            echo '<div class="alert alert-warning">'.$esc(end($relatorioEnvio)).'</div>';
                        }
                    }
                } finally {
                    flock($lock, LOCK_UN);
                    fclose($lock);
                }
            }
            if ($relatorioEnvio) {
                try {
                    $email = new \PHPMailer\PHPMailer\PHPMailer();
                    $email->CharSet = 'UTF-8';
                    $email->IsHTML(true);
                    $email->From = 'financeiro@grupothx.com.br';
                    $email->FromName = 'Grupo THX';
                    $email->AddAddress('deljdl@gmail.com', 'Admin');
                    $email->Subject = '[THX] Recuperação de clientes — envio manual';
                    $email->Body = '<h2>Resultado dos envios</h2>'.nl2br($esc(implode("\n", $relatorioEnvio))).'<p>Confira a entrada do WhatsApp na fila, o e-mail e o link curto. Cancelamento e encerramento permanecem em simulação.</p>';
                    if (!$email->Send()) { throw new RuntimeException($email->ErrorInfo); }
                    echo '<div class="alert alert-success">Relatório administrativo enviado.</div>';
                } catch (\Throwable $e) {
                    echo '<div class="alert alert-warning">Falha no relatório administrativo: '.$esc($e->getMessage()).'</div>';
                }
            }
            $propostas = Capsule::table('mod_recuperacao_thx')->get()->keyBy('servico_id');
        }
    }

    // Uma linha por serviço, incluindo negociações que já saíram da lista de inadimplência.
    $registros = $recuperacao;
    foreach ($propostas as $p) {
        if (isset($registros[$p->servico_id])) {
            continue;
        }
        $s = Capsule::table('tblhosting as h')
            ->leftJoin('tblproducts as pr', 'pr.id', '=', 'h.packageid')
            ->leftJoin('tblclients as c', 'c.id', '=', 'h.userid')
            ->where('h.id', $p->servico_id)->where('h.userid', $p->cliente_id)
            ->first(['h.id', 'h.userid', 'h.domain', 'h.domainstatus', 'pr.name as produto',
                'c.firstname', 'c.lastname', 'c.companyname']);
        if (!$s) {
            $s = (object)['id' => $p->servico_id, 'userid' => $p->cliente_id, 'domain' => $p->dominio,
                'domainstatus' => 'Indisponível', 'produto' => $p->produto, 'firstname' => 'Cliente #'.$p->cliente_id,
                'lastname' => '', 'companyname' => ''];
        }
        $s->duedate = null;
        $abertas = $faturas->filter(static function ($f) use ($s) {
            return (int)$f->id === (int)$s->id && (int)$f->userid === (int)$s->userid;
        })->values()->all();
        $valor = 0;
        foreach ($abertas as $f) {
            if ($f->duedate <= $hoje->format('Y-m-d')) {
                $valor += (float)$f->valor;
                if (!$s->duedate) {
                    $s->duedate = $f->duedate;
                }
            }
        }
        $registros[$p->servico_id] = ['servico' => $s, 'faturas' => $abertas, 'valor' => $valor];
    }

    echo '<h2>Negociações de hospedagem ('.count($registros).')</h2>';
    echo '<div class="table-responsive" style="overflow-x:auto"><table class="table table-striped table-bordered" style="width:100%;min-width:1250px;table-layout:fixed;overflow-wrap:anywhere">
        <colgroup><col style="width:14%"><col style="width:18%"><col style="width:8%"><col style="width:9%"><col style="width:11%"><col style="width:12%"><col style="width:10%"><col style="width:9%"><col style="width:9%"></colgroup>
        <thead><tr><th>Cliente</th><th>Hospedagem</th><th>Status do serviço</th><th>Atraso</th><th>Faturas em aberto do serviço</th><th>Negociação / ID Pix</th><th>Valores do acordo</th><th>Status da negociação</th><th>Ações</th></tr></thead><tbody>';
    if (!$registros) {
        echo '<tr><td colspan="9">Nenhuma hospedagem encontrada.</td></tr>';
    }
    $statusServico = ['Active' => 'Ativo', 'Suspended' => 'Suspenso', 'Terminated' => 'Finalizado', 'Cancelled' => 'Cancelado'];
    $statusNegociacao = ['criada' => 'Criada', 'enviada' => 'Enviada', 'pix_visualizado' => 'Pix visualizado',
        'paga' => 'Paga', 'concluida' => 'Concluída', 'cancelada' => 'Cancelada', 'encerrada' => 'Encerrada'];
    foreach ($registros as $registro) {
        $s = $registro['servico'];
        $p = $propostas[$s->id] ?? null;
        $links = [];
        foreach ($registro['faturas'] as $f) {
            $links[] = '<a target="_blank" rel="noopener" href="invoices.php?action=edit&amp;id='.(int)$f->fatura_id.'">#'.(int)$f->fatura_id.'</a> ('.$esc(date('d/m/Y', strtotime($f->duedate))).')';
        }
        $selecionavel = isset($servicos[$s->id]) && (!$p || ($p->status === 'criada' && empty($p->enviada_at) && !recuperacao_thx_expirada($p)));
        $checkbox = $selecionavel ? '<label><input type="checkbox" class="selecionar-negociacao" form="simulacao-negociacoes" name="servicos_simulacao[]" value="'.(int)$s->id.'"> Negociar</label><br>' : '';
        $identificador = $valores = '—';
        $status = 'Sem proposta';
        $acao = '—';
        if ($p) {
            $identificador = '#'.(int)$p->id.'<br><strong>R'.(int)$p->id.'S'.(int)$p->servico_id.'</strong><br><small>'.$esc($p->created_at).'</small>';
            $status = $esc(in_array($p->status, ['criada', 'enviada'], true) && !empty($p->visualizada_at)
                ? 'Visualizada' : ($statusNegociacao[$p->status] ?? $p->status));
            $etapas = [];
            foreach (['created_at' => 'Criação', 'enviada_at' => 'Envio', 'visualizada_at' => 'Primeira abertura',
                'aceita_at' => 'Aceite', 'pix_notificado_at' => 'Primeiro Pix disponibilizado',
                'pagamento_at' => 'Pagamento confirmado', 'aviso_final_at' => 'Aviso final',
                'concluida_at' => 'Conclusão'] as $campo => $rotulo) {
                if (!empty($p->$campo)) {
                    $etapas[] = $esc($rotulo).': '.$esc(date('d/m/Y H:i', strtotime($p->$campo)));
                }
            }
            if ($etapas) {
                $status .= '<details style="margin-top:5px"><summary style="cursor:pointer">Etapas</summary><small>'.implode('<br>', $etapas).'</small></details>';
            }
            $ativa = in_array($p->status, ['criada', 'enviada', 'pix_visualizado'], true);
            $expirada = $ativa && recuperacao_thx_expirada($p);
            if ($expirada) {
                $status .= '<br><small>Prazo encerrado</small>';
            }
            if ($ativa) {
                try {
                    $p = recuperacao_thx_proposta_atual($p);
                } catch (\Throwable $e) {
                    $status .= '<br><small>Revisão necessária</small>';
                }
                $acao = '<a class="btn btn-default btn-xs" target="_blank" rel="noopener noreferrer" href="'.$esc(recuperacao_thx_link($p, $urlBase)).'">Abrir proposta</a>';
            }
            $valores = '<small>Total: R$ '.number_format((float)$p->valor + (float)$p->perdao, 2, ',', '.').'</small><br><strong>R$ '.number_format((float)$p->valor, 2, ',', '.').'</strong><br><small>Desconto: R$ '.number_format((float)$p->perdao, 2, ',', '.').'</small>';
            if ($p->status === 'enviada' && $expirada && empty($p->aceita_at) && empty($p->pagamento_at) && empty($p->concluida_at)) {
                $acao .= '<br><button type="button" class="btn btn-danger btn-xs" style="white-space:normal" disabled>Cancelar cobranças e finalizar serviço</button><br><small>Aguardando validação</small>';
            }
        } elseif (isset($servicos[$s->id])) {
            $acao = '<form method="post"><input type="hidden" name="csrf_recuperacao" value="'.$esc($_SESSION['recuperacao_thx_csrf']).'"><button class="btn btn-primary btn-xs" name="criar_proposta" value="'.(int)$s->id.'" onclick="return confirm(&quot;Enviar proposta real para este serviço?&quot;)">Criar e enviar proposta</button></form>';
        }
        $atraso = $s->duedate ? $esc(date('d/m/Y', strtotime($s->duedate))).'<br><small>'.$hoje->diff(new DateTimeImmutable($s->duedate, $hoje->getTimezone()))->days.' dias</small>' : '—';
        echo '<tr>
            <td>'.$checkbox.'<a href="clientssummary.php?userid='.(int)$s->userid.'">'.$esc(trim($s->firstname.' '.$s->lastname.' '.$s->companyname)).'</a></td>
            <td><a href="clientsservices.php?userid='.(int)$s->userid.'&amp;id='.(int)$s->id.'">'.$esc($s->produto.' / '.$s->domain).'</a></td>
            <td>'.$esc($statusServico[$s->domainstatus] ?? $s->domainstatus).'</td>
            <td>'.$atraso.'</td>
            <td><a target="_blank" rel="noopener" href="clientsinvoices.php?userid='.(int)$s->userid.'&amp;serviceid='.(int)$s->id.'">Ver faturas do serviço</a><br><details><summary style="cursor:pointer">'.count($links).' fatura(s)</summary><div style="margin-top:8px">'.implode('<br>', $links).'</div></details><small>Vencidos: R$ '.number_format($registro['valor'], 2, ',', '.').'</small></td>
            <td>'.$identificador.'</td><td>'.$valores.'</td><td>'.$status.'</td><td>'.$acao.'</td></tr>';
    }
    echo '</tbody></table></div>';
    echo '<p>Valores dos itens antes de eventuais créditos, impostos ou pagamentos parciais. Não representam saldo confirmado do gateway.</p>';
}
