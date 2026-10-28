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
        'horas' => [8, 12, 16],
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
        $agendadaCronThx = $agoraCronThx->setTime($horaCronThx, 0);

        if ($agendadaCronThx > $agoraCronThx) {
            echo '<br />Hora agendada ainda não chegou: ' . $agendadaCronThx->format('Y-m-d H:i:s');
            continue;
        }

        $controleCronThx = $controleDirCronThx . '/' . $arquivoCronThx . '-' . $agendadaCronThx->format('Y-m-d-H') . '.ok';

        if (file_exists($controleCronThx) || $jaRodouNessaHoraCronThx) {
            echo '<br />Cron já executado ou já executado nesta chamada: ' . $controleCronThx;
            continue;
        }

        require $agendaCronThx['path'];

        echo '<br />Executado cron: ' . $arquivoCronThx . ' para a hora agendada: ' . $agendadaCronThx->format('Y-m-d H:i:s');

        file_put_contents($controleCronThx, date('Y-m-d H:i:s'));

        echo '<br />Controle criado: ' . $controleCronThx;
        echo '<hr />';

        $jaRodouNessaHoraCronThx = true;
    }
}