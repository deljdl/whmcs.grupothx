<?php
// Comunicações reais; cancelamento e encerramento exclusivamente em simulação.
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== realpath(__DIR__.'/cron.php')) {
    return;
}
require_once dirname(__DIR__, 2).'/init.php';
require_once dirname(__DIR__, 2).'/modules/addons/recuperacao_thx/recuperacao_thx.php';

$recuperacaoServicoTeste = null;
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $args = array_slice($_SERVER['argv'] ?? [], 1);
    if (count($args) !== 1 || ($args[0] !== '--todos' && !preg_match('/^--servico=([1-9][0-9]*)$/D', $args[0], $m))) {
        echo "Uso: php thx/crons/cron_recuperacao_thx.php --servico=ID (teste individual) ou --todos (envios reais em lote)\n";
        return;
    }
    $recuperacaoServicoTeste = isset($m[1]) ? (int)$m[1] : null;
}
(function () use ($recuperacaoServicoTeste) {
    $hoje = new DateTimeImmutable('today', new DateTimeZone('America/Sao_Paulo'));
    $mes = $hoje->modify('first day of this month')->modify('-2 months');
    $limite = $mes->setDate((int)$mes->format('Y'), (int)$mes->format('m'),
        min((int)$hoje->format('d'), (int)$mes->format('t')))->format('Y-m-d');
    $esc = static function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
    $linhas = [];
    $contagem = [];
    $compartilhadasChecklist = [];
    $linkFaturas = static function ($cliente, $servico) {
        return 'https://central.grupothx.com.br/admin/clientsinvoices.php?userid='.(int)$cliente.'&serviceid='.(int)$servico.'&status=Unpaid';
    };
    $adicionar = static function ($dominio, $acao, $detalhes, $cliente = null, $servico = null) use (&$linhas, &$contagem, $esc, $linkFaturas) {
        $contagem[$acao] = ($contagem[$acao] ?? 0) + 1;
        $link = $cliente && $servico ? '<br><a href="'.$esc($linkFaturas($cliente, $servico)).'">Ver faturas em aberto</a>' : '';
        $linhas[] = '<tr><td>'.$esc($dominio).$link.'</td><td>'.$esc($acao).'</td><td>'.$esc($detalhes).'</td></tr>';
    };
    $lock = fopen(sys_get_temp_dir().'/thx-recuperacao-cron.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) { return; }
    try {
        recuperacao_thx_propostas_tabela();
        [, $servicos] = recuperacao_thx_servicos($hoje, $limite);
        $propostas = \WHMCS\Database\Capsule::table('mod_recuperacao_thx')->get()->keyBy('servico_id');
        if ($recuperacaoServicoTeste !== null) {
            $servicos = array_intersect_key($servicos, [$recuperacaoServicoTeste => true]);
            if (!$servicos && !isset($propostas[$recuperacaoServicoTeste])) {
                $adicionar('Serviço #'.$recuperacaoServicoTeste, 'Bloqueado', 'Serviço não elegível ou inexistente.');
            }
        }
        foreach ($servicos as $id => $registro) {
            if (isset($propostas[$id])) { continue; }
            try {
                $p = recuperacao_thx_criar_proposta($registro, true);
                recuperacao_thx_criar_proposta($registro);
                $adicionar($p->dominio, 'Proposta criada', 'Valor R$ '.$p->valor.'; desconto R$ '.$p->perdao.'; faturas '.implode(', ', array_column(json_decode($p->faturas, true), 'id')).'; excluídas por composição: '.implode(', ', $p->compartilhadas));
            } catch (\Throwable $e) {
                $adicionar($registro['servico']->domain, 'Bloqueado', get_class($e) === RuntimeException::class ? $e->getMessage() : 'Falha na consulta; verificar logs.', $registro['servico']->userid, $registro['servico']->id);
            }
        }
        $propostas = \WHMCS\Database\Capsule::table('mod_recuperacao_thx')->get()->keyBy('servico_id');
        foreach ($propostas as $p) {
            if ($recuperacaoServicoTeste !== null && (int)$p->servico_id !== $recuperacaoServicoTeste) { continue; }
            if (!in_array($p->status, ['criada', 'enviada', 'pix_visualizado', 'paga'], true)) {
                if (!empty($p->pagamento_at) && empty($p->concluida_at)) {
                    $adicionar($p->dominio, 'Regularização pendente', 'Conferir recebimento, gateways, faturas exclusivas, reativação e vencimento.');
                }
                continue;
            }
            try {
                $atual = recuperacao_thx_proposta_atual($p);
                if ($atual->compartilhadas) {
                    $compartilhadasChecklist[] = '<li><strong>'.$esc($p->dominio).'</strong>: fatura(s) #'.implode(', #', array_map('intval', $atual->compartilhadas)).' — fora da negociação; não cancelar. Conferir separadamente os itens de cada serviço. <a href="'.$esc($linkFaturas($p->cliente_id, $p->servico_id)).'">Ver faturas em aberto</a></li>';
                }
                $detalhes = 'Pix R'.(int)$p->id.'S'.(int)$p->servico_id.'; R$ '.$p->valor.'; faturas '.implode(', ', array_column(json_decode($atual->faturas, true), 'id')).'; compartilhadas excluídas: '.implode(', ', $atual->compartilhadas);
                if (!empty($p->pagamento_at)) {
                    $acao = 'Regularização pendente';
                    $detalhes .= '; '.recuperacao_thx_prever_cancelamentos($p).'; reativação e novo vencimento pendentes de conferência.';
                } elseif (recuperacao_thx_expirada($p)) {
                    if ($p->status === 'pix_visualizado' || !empty($p->aceita_at) || !empty($p->pix_notificado_at)) {
                        $acao = 'Encerramento bloqueado — conferir Pix';
                        $detalhes .= '; Não finalizar enquanto o recebimento do Pix não for conferido. '.recuperacao_thx_prever_cancelamentos($p);
                    } elseif (empty($p->aviso_final_at)) {
                        $acao = 'Encerramento bloqueado — aviso final pendente';
                        $detalhes .= '; Não finalizar sem conferir o aviso final e o recebimento. '.recuperacao_thx_prever_cancelamentos($p);
                    } else {
                        $acao = 'Revisar encerramento';
                        $detalhes .= '; '.recuperacao_thx_prever_encerramento($p);
                    }
                } elseif ($p->status === 'pix_visualizado') {
                    $acao = 'Conferir recebimento Pix';
                    $detalhes .= '; Somente se o pagamento for confirmado: '.recuperacao_thx_prever_cancelamentos($p).'; conferir reativação e novo vencimento.';
                } elseif (empty($p->enviada_at)) {
                    $acao = 'Envio de proposta';
                    $detalhes .= '; '.recuperacao_thx_comunicar($atual, 'inicial');
                } else {
                    $inicio = new DateTimeImmutable($p->enviada_at, $hoje->getTimezone());
                    $mesFim = $inicio->modify('first day of this month')->modify('+1 month');
                    $fim = $mesFim->setDate((int)$mesFim->format('Y'), (int)$mesFim->format('m'), min((int)$inicio->format('d'), (int)$mesFim->format('t')));
                    $ultimo = new DateTimeImmutable($p->ultimo_envio_at ?? $p->enviada_at, $hoje->getTimezone());
                    if (empty($p->aviso_final_at) && $hoje >= $fim->modify('-7 days')->setTime(0, 0)) {
                        $acao = 'Aviso final';
                        $detalhes .= '; '.recuperacao_thx_comunicar($atual, 'final');
                    } elseif ($hoje >= $ultimo->modify('+3 days')->setTime(0, 0)) {
                        $acao = 'Reenvio de proposta';
                        $detalhes .= '; '.recuperacao_thx_comunicar($atual, 'reenvio');
                    } else {
                        $acao = 'Aguardar';
                    }
                    $detalhes .= '; prazo final '.$fim->format('d/m/Y');
                }
                $adicionar($p->dominio, $acao, $detalhes, $p->cliente_id, $p->servico_id);
            } catch (\Throwable $e) {
                $adicionar($p->dominio, 'Bloqueado', get_class($e) === RuntimeException::class ? $e->getMessage() : 'Falha na consulta; verificar logs.', $p->cliente_id, $p->servico_id);
            }
        }
    } catch (\Throwable $e) {
        $adicionar('Sistema', 'Erro', get_class($e) === RuntimeException::class ? $e->getMessage() : 'Falha geral na consulta; verificar logs.');
    }
    $html = '<h2>Recuperação THX — Relatório diário</h2><p>Propostas e comunicações reais. Cancelamentos e encerramentos somente em simulação.</p><ul>';
    foreach ($contagem as $acao => $n) { $html .= '<li>'.$esc($acao).': '.$n.'</li>'; }
    $html .= '</ul><table border="1" cellpadding="6" cellspacing="0"><tr><th>Serviço</th><th>Operação / previsão</th><th>Conferência</th></tr>'.implode('', $linhas).'</table>';
    if ($compartilhadasChecklist) {
        $html .= '<h3>Checklist — faturas compartilhadas fora da negociação</h3><ul>'.implode('', $compartilhadasChecklist).'</ul>';
    }
    $html .= '<h3>Checklist do administrador</h3><ol><li>Conferir elegibilidade, serviço e valores de cada proposta.</li><li>Conferir faturas compartilhadas: domínios e outros serviços não podem ser cancelados.</li><li>Testar a prévia de e-mail e WhatsApp, imagem e link curto no módulo.</li><li>Conferir no banco/recebimentos os Pix visualizados e os pagamentos antes de qualquer baixa.</li><li>Conferir Efí/Asaas, aviso final e serviço antes de qualquer encerramento.</li><li>Revisar erros e impedimentos. Não liberar cancelamentos ou encerramentos enquanto houver divergências ou funções financeiras não testadas.</li></ol><p><a href="https://central.grupothx.com.br/admin/addonmodules.php?module=recuperacao_thx">Abrir módulo de recuperação</a></p>';
    try {
        $email = new \PHPMailer\PHPMailer\PHPMailer();
        $email->IsHTML(true);
        $email->CharSet = 'UTF-8';
        $email->From = 'financeiro@grupothx.com.br';
        $email->FromName = 'Grupo THX';
        $email->Subject = '[THX] Recuperação de clientes — '.$hoje->format('d/m/Y').($recuperacaoServicoTeste !== null ? ' — teste serviço #'.$recuperacaoServicoTeste : '');
        $email->Body = $html;
        $email->AddAddress('deljdl@gmail.com', 'Admin');
        if (!$email->Send()) { throw new RuntimeException('Falha no envio do relatório.'); }
        echo '<p>Recuperação THX: relatório diário enviado ao administrador.</p>';
    } catch (\Throwable $e) {
        logActivity('Recuperação THX: falha ao enviar relatório de simulação.');
        echo '<p>Recuperação THX: falha no envio do relatório.</p>';
    }
})();
