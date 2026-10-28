<?php

$rootDirCrons = dirname(dirname(dirname(dirname(__DIR__))));
die($rootDirCrons);


$baseDir = dirname(dirname(dirname(dirname(__DIR__))));

require_once $baseDir . '/init.php';
require_once $baseDir . '/tarifasTHX.php';

$controleDir = '/nao-versionado/controle-cron';

if (!is_dir($controleDir)) {
    mkdir($controleDir, 0755, true);
}

$crons = [
    'cron_dividas.php' => [
        'dias' => [1, 3, 5],
        'horas' => [10],
    ],
    'cron_monitora_pagamentos.php' => [
        'dias' => [1, 2, 3, 4, 5],
        'horas' => [8, 12, 16],
    ],
    'cron-resumo-diario.php' => [
        'dias' => [1, 2, 3, 4, 5, 6, 7],
        'horas' => [23],
    ],
];

$agora = new DateTimeImmutable();
$inicio = $agora->modify('-7 days')->setTime(0, 0);

foreach ($crons as $arquivo => $agenda) {
    for ($data = $inicio; $data <= $agora; $data = $data->modify('+1 day')) {
        if (!in_array((int) $data->format('N'), $agenda['dias'])) {
            continue;
        }

        foreach ($agenda['horas'] as $hora) {
            $agendada = $data->setTime($hora, 0);

            if ($agendada > $agora) {
                continue;
            }

            $controle = $controleDir . '/' . pathinfo($arquivo, PATHINFO_FILENAME)
                . '-' . $agendada->format('Y-m-d-H') . '.ok';

            if (file_exists($controle)) {
                continue;
            }

            require $baseDir . '/thx/modules/addons/fluxo_de_caixa/modulos/' . $arquivo;

            file_put_contents($controle, date('Y-m-d H:i:s'));
        }
    }
}