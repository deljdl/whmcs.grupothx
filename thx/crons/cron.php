<?php

date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$rootDirCrons = dirname(dirname(__DIR__));

$controleDir = $rootDirCrons . '/thx/nao-versionado/controle-cron';

if (!is_dir($controleDir)) {
    mkdir($controleDir, 0755, true);
}

if (!is_writable($controleDir)) {
    die("O diretório de controle não é gravável: $controleDir");
}

require_once $rootDirCrons . '/init.php';
require_once $rootDirCrons . '/tarifasTHX.php';

$crons = [
    'cron_dividas' => [
        'path' => $rootDirCrons . '/thx/crons/cron_dividas.php',
        'dias' => [1, 3, 5],
        'horas' => [10],
    ],
    'cron_monitora_pagamentos' => [
        'path' => $rootDirCrons . '/thx/crons/cron_monitora_pagamentos.php',
        'dias' => [1, 2, 3, 4, 5],
        'horas' => [8, 12, 16],
    ],
    'cron_resumo_diario' => [
        'path' => $rootDirCrons . '/thx/crons/cron-resumo-diario.php',
        'dias' => [1, 2, 3, 4, 5, 6, 7],
        'horas' => [23],
    ],
];

$agoraCronThx = new DateTimeImmutable();

foreach ($crons as $arquivo => $agenda) {
    if (!in_array((int) $agoraCronThx->format('N'), $agenda['dias'])) {
        continue;
    }

    foreach ($agenda['horas'] as $hora) {
        $agendada = $agoraCronThx->setTime($hora, 0);

        if ($agendada > $agoraCronThx) {
            continue;
        }

        $controle = $controleDir . '/' . $arquivo . '-' . $agendada->format('Y-m-d-H') . '.ok';

        if (file_exists($controle)) {
            continue;
        }

        require $agenda['path'];

        file_put_contents($controle, date('Y-m-d H:i:s'));
    }
}