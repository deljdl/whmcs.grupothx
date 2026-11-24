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
        'mensagem_apoio' => 'text', 'apoio_at' => 'dateTime', 'pix_notificado_at' => 'dateTime',
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

    $recuperacao = $servicos;

    recuperacao_thx_propostas_tabela();
    if (empty($_SESSION['recuperacao_thx_csrf'])) {
        $_SESSION['recuperacao_thx_csrf'] = bin2hex(random_bytes(32));
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['criar_proposta'])) {
        try {
            if (!hash_equals($_SESSION['recuperacao_thx_csrf'], (string)($_POST['csrf_recuperacao'] ?? ''))) {
                throw new RuntimeException('Solicitação inválida.');
            }
            $id = (int)$_POST['criar_proposta'];
            if (Capsule::table('mod_recuperacao_thx')->where('servico_id', $id)->exists()) {
                throw new RuntimeException('Já existe uma proposta para este serviço.');
            }
            $encontrado = false;
            foreach ($servicos as $registro) {
                if ((int)$registro['servico']->id === $id) {
                    recuperacao_thx_criar_proposta($registro);
                    $encontrado = true;
                    break;
                }
            }
            if (!$encontrado) {
                throw new RuntimeException('Hospedagem não elegível.');
            }
            echo '<div class="alert alert-success">Proposta criada. Nenhuma mensagem ou cobrança foi enviada.</div>';
        } catch (RuntimeException $e) {
            echo '<div class="alert alert-warning">'.nl2br($esc($e->getMessage())).'</div>';
        } catch (\Throwable $e) {
            echo '<div class="alert alert-danger">Não foi possível criar a proposta.</div>';
        }
    }
    $propostas = Capsule::table('mod_recuperacao_thx')->get()->keyBy('servico_id');
    $urlBase = rtrim((string)Capsule::table('tblconfiguration')->where('setting', 'SystemURL')->value('value'), '/');
    echo '<div class="alert alert-info">Modo proposta: nenhuma campanha, cobrança ou exclusão será executada. A classificação é preliminar e exige conferência dos pagamentos e das faturas compartilhadas.</div>';
    echo '<p>Data de referência: '.$hoje->format('d/m/Y').' | Elegíveis a partir de 2 meses de atraso: '.$esc($limite2).'</p>';
    echo '<form id="simulacao-negociacoes" method="post"><input type="hidden" name="csrf_recuperacao" value="'.$esc($_SESSION['recuperacao_thx_csrf']).'">
        <label><input type="checkbox" id="selecionar-todas-negociacoes"> Selecionar todas para negociação</label>
        <button type="submit" class="btn btn-primary" name="simular_negociacoes" value="1">Simular envio (e-mail e WhatsApp)</button></form>';
    echo '<script>document.getElementById("selecionar-todas-negociacoes").addEventListener("change",function(){document.querySelectorAll(".selecionar-negociacao").forEach(e=>e.checked=this.checked);});</script>';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simular_negociacoes'])) {
        if (!is_string($_POST['csrf_recuperacao'] ?? null) || !hash_equals($_SESSION['recuperacao_thx_csrf'], $_POST['csrf_recuperacao'])) {
            echo '<div class="alert alert-warning">Solicitação inválida.</div>';
        } else {
            $selecionados = array_map('intval', (array)($_POST['servicos_simulacao'] ?? []));
            echo '<h2>Simulação de envio — nenhuma mensagem enviada</h2>';
            if (!$selecionados) {
                echo '<p>Selecione ao menos uma hospedagem.</p>';
            }
            foreach ($servicos as $registro) {
                $s = $registro['servico'];
                if (!in_array((int)$s->id, $selecionados, true)) {
                    continue;
                }
                try {
                    $p = isset($propostas[$s->id]) ? recuperacao_thx_proposta_atual($propostas[$s->id]) : recuperacao_thx_criar_proposta($registro, true);
                    if (!in_array($p->status, ['criada', 'enviada'], true)) {
                        throw new RuntimeException('Proposta já iniciada ou encerrada.');
                    }
                    if (recuperacao_thx_expirada($p)) {
                        throw new RuntimeException('Prazo da negociação encerrado.');
                    }
                    $cliente = Capsule::table('tblclients')->where('id', $s->userid)->first(['firstname', 'email', 'phonenumber']);
                    if (!$cliente) {
                        throw new RuntimeException('Cliente não localizado.');
                    }
                    $original = 'R$ '.number_format((float)$p->valor + (float)$p->perdao, 2, ',', '.');
                    $valor = 'R$ '.number_format((float)$p->valor, 2, ',', '.');
                    $link = $p->token ? $urlBase.'/modules/addons/recuperacao_thx/proposta.php?t='.$p->token : '[Link gerado ao criar a proposta]';
                    $assunto = 'Condição especial para regularizar sua hospedagem';
                    $mensagem = "Olá, ".$cliente->firstname."!\n\nPreparamos uma condição especial para regularizar os débitos de seu serviço de hospedagem (".$s->domain.").\n\nDe ".$original." por apenas ".$valor.".\n\nCom a confirmação do pagamento, os débitos anteriores serão cancelados e o serviço será reativado, caso esteja suspenso.\n\nConfira sua proposta:\n".$link."\n\nNossa equipe está à disposição para auxiliá-lo.";
                    echo '<div class="panel panel-default"><div class="panel-heading"><strong>'.$esc($s->domain).'</strong></div><div class="panel-body">'
                        .'<p><strong>E-mail:</strong> '.$esc($cliente->email).'<br><strong>Assunto:</strong> '.$esc($assunto).'</p><pre style="white-space:pre-wrap">'.$esc($mensagem).'</pre>'
                        .'<p><strong>WhatsApp:</strong> '.$esc($cliente->phonenumber).'</p><pre style="white-space:pre-wrap">'.$esc($mensagem).'</pre></div></div>';
                } catch (RuntimeException $e) {
                    echo '<div class="alert alert-warning">'.$esc($s->domain).': '.nl2br($esc(get_class($e) === RuntimeException::class ? $e->getMessage() : 'Falha na conferência.')).'</div>';
                } catch (\Throwable $e) {
                    echo '<div class="alert alert-warning">'.$esc($s->domain).': Falha na conferência.</div>';
                }
            }
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
        $selecionavel = isset($servicos[$s->id]) && (!$p || (in_array($p->status, ['criada', 'enviada'], true) && !recuperacao_thx_expirada($p)));
        $checkbox = $selecionavel ? '<label><input type="checkbox" class="selecionar-negociacao" form="simulacao-negociacoes" name="servicos_simulacao[]" value="'.(int)$s->id.'"> Negociar</label><br>' : '';
        $identificador = $valores = '—';
        $status = 'Sem proposta';
        $acao = '—';
        if ($p) {
            $identificador = '#'.(int)$p->id.'<br><strong>R'.(int)$p->id.'S'.(int)$p->servico_id.'</strong><br><small>'.$esc($p->created_at).'</small>';
            $status = $esc($statusNegociacao[$p->status] ?? $p->status);
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
                $acao = '<a class="btn btn-default btn-xs" target="_blank" rel="noopener noreferrer" href="'.$esc($urlBase.'/modules/addons/recuperacao_thx/proposta.php?t='.$p->token).'">Abrir proposta</a>';
            }
            $valores = '<small>Total: R$ '.number_format((float)$p->valor + (float)$p->perdao, 2, ',', '.').'</small><br><strong>R$ '.number_format((float)$p->valor, 2, ',', '.').'</strong><br><small>Desconto: R$ '.number_format((float)$p->perdao, 2, ',', '.').'</small>';
            if ($p->status === 'enviada' && $expirada && empty($p->aceita_at) && empty($p->pagamento_at) && empty($p->concluida_at)) {
                $acao .= '<br><button type="button" class="btn btn-danger btn-xs" style="white-space:normal" disabled>Cancelar cobranças e finalizar serviço</button><br><small>Aguardando validação</small>';
            }
        } elseif (isset($servicos[$s->id])) {
            $acao = '<form method="post"><input type="hidden" name="csrf_recuperacao" value="'.$esc($_SESSION['recuperacao_thx_csrf']).'"><button class="btn btn-primary btn-xs" name="criar_proposta" value="'.(int)$s->id.'">Criar proposta</button></form>';
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
