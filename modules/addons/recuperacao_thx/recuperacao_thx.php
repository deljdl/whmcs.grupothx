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
        'version' => '1.0.0',
        'author' => 'Grupo THX',
    ];
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

    echo '<div class="alert alert-info">Modo consulta: nenhuma campanha, cobrança ou exclusão será executada. A classificação é preliminar e exige conferência dos pagamentos e das faturas compartilhadas.</div>';
    echo '<p>Data de referência: '.$hoje->format('d/m/Y').' | 3 meses: '.$esc($limite3).' | 5 meses: '.$esc($limite5).'</p>';

    foreach (['Recuperação (3 a menos de 5 meses)' => $recuperacao,
        'Revisão para encerramento (5 meses ou mais)' => $encerramento] as $titulo => $registros) {
        echo '<h2>'.$esc($titulo).' ('.count($registros).')</h2>';
        if (!$registros) {
            echo '<p>Nenhuma hospedagem encontrada.</p>';
            continue;
        }
        echo '<div class="table-responsive"><table class="table table-striped table-bordered"><thead><tr>
            <th>Cliente</th><th>Hospedagem</th><th>Status</th><th>Vencimento mais antigo</th>
            <th>Dias de atraso</th><th>Itens vencidos (bruto)</th><th>Faturas em aberto do serviço</th>
            </tr></thead><tbody>';
        foreach ($registros as $registro) {
            $s = $registro['servico'];
            $links = [];
            foreach ($registro['faturas'] as $f) {
                $links[] = '<a target="_blank" rel="noopener" href="invoices.php?action=edit&amp;id='.(int)$f->fatura_id.'">#'.(int)$f->fatura_id.'</a> ('.$esc(date('d/m/Y', strtotime($f->duedate))).')';
            }
            echo '<tr>
                <td><a href="clientssummary.php?userid='.(int)$s->userid.'">'.$esc(trim($s->firstname.' '.$s->lastname.' '.$s->companyname)).'</a></td>
                <td><a href="clientsservices.php?userid='.(int)$s->userid.'&amp;id='.(int)$s->id.'">'.$esc($s->produto.' / '.$s->domain).'</a></td>
                <td>'.$esc($s->domainstatus === 'Suspended' ? 'Suspenso' : 'Ativo').'</td>
                <td>'.$esc(date('d/m/Y', strtotime($s->duedate))).'</td>
                <td>'.$hoje->diff(new DateTimeImmutable($s->duedate, $hoje->getTimezone()))->days.'</td>
                <td>R$ '.number_format($registro['valor'], 2, ',', '.').'</td>
                <td>'.implode('<br>', $links).'</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    echo '<p>Valores dos itens antes de eventuais créditos, impostos ou pagamentos parciais. Não representam saldo confirmado do gateway.</p>';
}
