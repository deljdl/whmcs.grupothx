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
        'version' => '1.1.0',
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
}

function recuperacao_thx_criar_proposta($registro)
{
    $s = $registro['servico'];
    $hoje = date('Y-m-d');
    $faturas = array_values(array_filter($registro['faturas'], static function ($f) use ($hoje) {
        return $f->duedate <= $hoje;
    }));
    if (count($faturas) < 2) {
        throw new RuntimeException('É necessário haver pelo menos duas faturas vencidas.');
    }

    $dados = [];
    foreach ($faturas as $f) {
        $i = Capsule::table('tblinvoices')->where('id', $f->fatura_id)->where('userid', $s->userid)
            ->where('status', 'Unpaid')->first();
        $itens = Capsule::table('tblinvoiceitems')->where('invoiceid', $f->fatura_id)->get();
        if (!$i || !$itens->count() || (float)$i->total <= 0 || (float)$i->credit != 0
            || Capsule::table('tblaccounts')->where('invoiceid', $f->fatura_id)->exists()) {
            throw new RuntimeException('Fatura com crédito, transação ou situação incompatível. Confira manualmente.');
        }
        foreach ($itens as $item) {
            if ($item->type !== 'Hosting' || (int)$item->relid !== (int)$s->id) {
                throw new RuntimeException('Existe fatura compartilhada ou com outros itens. Confira manualmente.');
            }
        }
        $dados[] = ['id' => (int)$i->id, 'vencimento' => $i->duedate, 'valor' => (string)$i->total];
    }

    $ultima = end($dados);
    $perdao = 0;
    foreach (array_slice($dados, 0, -1) as $item) {
        $perdao += (int)round((float)$item['valor'] * 100);
    }

    Capsule::table('mod_recuperacao_thx')->insert([
        'servico_id' => $s->id, 'cliente_id' => $s->userid,
        'token' => bin2hex(random_bytes(32)), 'dominio' => $s->domain,
        'produto' => $s->produto, 'fatura_pagamento' => $ultima['id'],
        'faturas' => json_encode($dados), 'valor' => $ultima['valor'],
        'perdao' => number_format($perdao / 100, 2, '.', ''),
        'status' => 'criada', 'created_at' => date('Y-m-d H:i:s'),
    ]);
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
    $limite3 = $limite(3);
    $limite5 = $limite(5);
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
            if ($fatura->duedate > $limite3) {
                continue;
            }
            $servicos[$fatura->id] = ['servico' => $fatura, 'faturas' => [], 'valor' => 0];
        }
        $servicos[$fatura->id]['faturas'][] = $fatura;
        if ($fatura->duedate < $hoje->format('Y-m-d')) {
            $servicos[$fatura->id]['valor'] += (float)$fatura->valor;
        }
    }

    $recuperacao = $encerramento = [];
    foreach ($servicos as $registro) {
        if ($registro['servico']->duedate <= $limite5) {
            $encerramento[] = $registro;
        } else {
            $recuperacao[] = $registro;
        }
    }

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
            foreach ($recuperacao as $registro) {
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
            echo '<div class="alert alert-warning">'.$esc($e->getMessage()).'</div>';
        } catch (\Throwable $e) {
            echo '<div class="alert alert-danger">Não foi possível criar a proposta.</div>';
        }
    }
    $propostas = Capsule::table('mod_recuperacao_thx')->get()->keyBy('servico_id');
    $urlBase = rtrim((string)Capsule::table('tblconfiguration')->where('setting', 'SystemURL')->value('value'), '/');
    echo '<div class="alert alert-info">Modo proposta: nenhuma campanha, cobrança ou exclusão será executada. A classificação é preliminar e exige conferência dos pagamentos e das faturas compartilhadas.</div>';
    echo '<p>Data de referência: '.$hoje->format('d/m/Y').' | 3 meses: '.$esc($limite3).' | 5 meses: '.$esc($limite5).'</p>';

    foreach (['Recuperação (3 a menos de 5 meses)' => $recuperacao,
        'Revisão para encerramento (5 meses ou mais)' => $encerramento] as $titulo => $registros) {
        echo '<h2>'.$esc($titulo).' ('.count($registros).')</h2>';
        if (!$registros) {
            echo '<p>Nenhuma hospedagem encontrada.</p>';
            continue;
        }
        foreach ($registros as $registro) {
            $s = $registro['servico'];
            $links = [];
            foreach ($registro['faturas'] as $f) {
                $links[] = '<a target="_blank" rel="noopener" href="invoices.php?action=edit&amp;id='.(int)$f->fatura_id.'">#'.(int)$f->fatura_id.'</a> ('.$esc(date('d/m/Y', strtotime($f->duedate))).')';
            }
            $acao = '—';
            if (isset($propostas[$s->id])) {
                $acao = '<a class="btn btn-default" target="_blank" rel="noopener noreferrer" href="'.$esc($urlBase.'/modules/addons/recuperacao_thx/proposta.php?t='.$propostas[$s->id]->token).'">Abrir proposta</a>';
            } elseif ($s->duedate > $limite5) {
                $acao = '<form method="post"><input type="hidden" name="csrf_recuperacao" value="'.$esc($_SESSION['recuperacao_thx_csrf']).'"><button class="btn btn-primary" name="criar_proposta" value="'.(int)$s->id.'">Criar proposta</button></form>';
            }
            echo '<div class="table-responsive"><table class="table table-striped table-bordered"><thead><tr>
                <th>Cliente</th><th>Hospedagem</th><th>Status</th><th>Vencimento mais antigo</th>
                <th>Dias de atraso</th><th>Itens vencidos (bruto)</th><th>Faturas em aberto do serviço</th><th>Proposta</th>
                </tr></thead><tbody>';
            echo '<tr>
                <td><a href="clientssummary.php?userid='.(int)$s->userid.'">'.$esc(trim($s->firstname.' '.$s->lastname.' '.$s->companyname)).'</a></td>
                <td><a href="clientsservices.php?userid='.(int)$s->userid.'&amp;id='.(int)$s->id.'">'.$esc($s->produto.' / '.$s->domain).'</a></td>
                <td>'.$esc($s->domainstatus === 'Suspended' ? 'Suspenso' : 'Ativo').'</td>
                <td>'.$esc(date('d/m/Y', strtotime($s->duedate))).'</td>
                <td>'.$hoje->diff(new DateTimeImmutable($s->duedate, $hoje->getTimezone()))->days.'</td>
                <td>R$ '.number_format($registro['valor'], 2, ',', '.').'</td>
                <td><details><summary style="cursor:pointer">'.count($links).' fatura(s)</summary><div style="margin-top:8px">'.implode('<br>', $links).'</div></details></td><td>'.$acao.'</td></tr>';
            echo '</tbody></table></div>';
        }
    }

    echo '<p>Valores dos itens antes de eventuais créditos, impostos ou pagamentos parciais. Não representam saldo confirmado do gateway.</p>';
}
