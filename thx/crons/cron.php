<?php

date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$rootDirCronThx = dirname(dirname(__DIR__));

$controleDirCronThx = $rootDirCronThx . '/thx/nao-versionado/controle-cron';

if (!is_dir($controleDirCronThx)) {
    mkdir($controleDirCronThx, 0755, true);
}

if (!is_writable($controleDirCronThx)) {
    die("O diretório de controle não é gravável: $controleDirCronThx");
}

$alertaCronThx = static function ($cron, $erro, $arquivo, $linha) {
    $mensagem = "[CRON THX] Falha em ".$cron."\n"
        ."Horário: ".date('d/m/Y H:i:s')."\n"
        ."Erro: ".$erro."\n"
        ."Arquivo: ".$arquivo."\n"
        ."Linha: ".$linha."\n";
    error_log($mensagem);
    if (!error_log($mensagem, 1, 'deljdl@gmail.com')) {
        error_log('[CRON THX] Não foi possível enviar o alerta por e-mail.');
    }
};

$lockCronThx = fopen($controleDirCronThx . '/cron-thx.lock', 'c');
if (!$lockCronThx) {
    $alertaCronThx('cron.php', 'Não foi possível abrir o arquivo de bloqueio', __FILE__, __LINE__);
    return;
}
if (!flock($lockCronThx, LOCK_EX | LOCK_NB)) {
    fclose($lockCronThx);
    return;
}

$cronAtivoThx = null;
register_shutdown_function(static function () use (&$cronAtivoThx, $alertaCronThx) {
    if ($cronAtivoThx === null) return;
    $erro = error_get_last();
    $alertaCronThx(
        $cronAtivoThx,
        'Execução interrompida antes de concluir'.($erro ? ' (tipo PHP '.$erro['type'].')' : ''),
        $erro['file'] ?? __FILE__,
        $erro['line'] ?? 0
    );
});

require_once $rootDirCronThx . '/init.php';
require_once $rootDirCronThx . '/tarifasTHX.php';

$cronsCronThx = [
    'cron_dividas' => [
        'path' => $rootDirCronThx . '/thx/crons/cron_dividas.php',
        'dias' => [1, 2, 3, 4, 5],
        'horas' => [9],
    ],
    'cron_monitora_pagamentos' => [
        'path' => $rootDirCronThx . '/thx/crons/cron_monitora_pagamentos.php',
        'dias' => [1, 2, 3, 4, 5],
        'horas' => [8],
    ],
    'cron_recuperacao_thx' => [
        'path' => $rootDirCronThx . '/thx/crons/cron_recuperacao_thx.php',
        'dias' => [1, 2, 3, 4, 5, 6, 7],
        'horas' => [10],
    ],
    'cron_resumo_diario' => [
        'path' => $rootDirCronThx . '/thx/crons/cron-resumo-diario.php',
        'dias' => [1, 2, 3, 4, 5],
        'horas' => [11],
    ],
    'quota-check' => [
        'path' => $rootDirCronThx . '/thx/crons/quota-check.php',
        'dias' => [1, 3, 5],
        'horas' => [9],
    ],
    // 'libera-mensagens-whatsapp' => [
    //     'path' => $rootDirCronThx . '/thx/crons/liberaMensagensWhatsApp.php',
    //     'dias' => [1, 2, 3, 4, 5, 6, 7],
    //     'horas' => [8],
    // ],
];

$agoraCronThx = new DateTimeImmutable();

echo '<h3>Início da execução do cron THX</h3>';
echo '<br />Dia atual: ' . $agoraCronThx->format('N');
echo '<br />Hora atual: ' . $agoraCronThx->format('H:i:s');
echo '<hr />';

foreach ($cronsCronThx as $arquivoCronThx => $agendaCronThx) {
    echo '<br />Verificando cron: ' . $arquivoCronThx . '<br />';

    if (!in_array((int) $agoraCronThx->format('N'), $agendaCronThx['dias'])) {
        echo '<br />Dia atual não está na lista de dias agendados para este cron.';
        echo '<br />Dias agendados: ' . implode(', ', $agendaCronThx['dias']);
        echo '<hr />';
        continue;
    }

    $jaRodouNessaHoraCronThx = false;

    foreach ($agendaCronThx['horas'] as $horaCronThx) {
        $agendadaCronThx = $agoraCronThx->setTime($horaCronThx, $agendaCronThx['minuto'] ?? 0);

        if ($agendadaCronThx > $agoraCronThx) {
            echo '<br />Hora agendada ainda não chegou: ' . $agendadaCronThx->format('Y-m-d H:i:s');
            continue;
        }

        $controleCronThx = $controleDirCronThx . '/' . $arquivoCronThx . '-' . $agendadaCronThx->format('Y-m-d-H') . '.ok';

        if (file_exists($controleCronThx) || $jaRodouNessaHoraCronThx) {
            echo '<br />Cron já executado ou já executado nesta chamada: ' . $arquivoCronThx;
            continue;
        }

        $cronAtivoThx = $arquivoCronThx;
        try {
            require $agendaCronThx['path'];

            if (file_put_contents($controleCronThx, date('Y-m-d H:i:s')) === false) {
                throw new \RuntimeException('Falha ao gravar controle do cron');
            }

            echo '<br />Executado cron: ' . $arquivoCronThx . ' para a hora agendada: ' . $agendadaCronThx->format('Y-m-d H:i:s');
            echo '<br />Controle criado: ' . $arquivoCronThx;
            echo '<hr />';

            $jaRodouNessaHoraCronThx = true;
        } catch (\Throwable $e) {
            $alertaCronThx($arquivoCronThx, get_class($e).': '.$e->getMessage(), $e->getFile(), $e->getLine());
            echo '<br />Falha no cron: ' . $arquivoCronThx . '<hr />';
        } finally {
            $cronAtivoThx = null;
        }
    }
}
echo '<h3>Fim da execução do cron THX</h3>';