<?php

date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$rootDirCrons = dirname(dirname(__DIR__));

$controleDir = $rootDirCrons . '/thx/nao-versionado/controle-cron';

if(!is_dir($controleDir)) {
    mkdir($controleDir, 0755, true);
}

if(!is_writable($controleDir)) {
    die("O diretório de controle não é gravável: $controleDir");
}

require_once $rootDirCrons . '/init.php';
require_once $rootDirCrons . '/tarifasTHX.php';





$host = THX_DB_HOST;
$dbname = THX_DB_NAME;
$user = THX_DB_USERNAME;
$pass = THX_DB_PASSWORD;
$port = THX_DB_PORT;

$pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $user, $pass);


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
$inicioCronThx = $agoraCronThx->modify('-7 days')->setTime(0, 0);

var_dump($agoraCronThx, $inicioCronThx);

foreach ($crons as $arquivo => $agenda) {
    for ($data = $inicioCronThx; $data <= $agoraCronThx; $data = $data->modify('+1 day')) {
        if (!in_array((int) $data->format('N'), $agenda['dias'])) {
            continue;
        }

        foreach ($agenda['horas'] as $hora) {
            $agendada = $data->setTime($hora, 0);

            if ($agendada > $agoraCronThx) {
                continue;
            }

            $controle = $controleDir . '/' . pathinfo($arquivo, PATHINFO_FILENAME)
                . '-' . $agendada->format('Y-m-d-H') . '.ok';

            if (file_exists($controle)) {
                continue;
            }

            require_once $crons[$arquivo]['path'];

            file_put_contents($controle, date('Y-m-d H:i:s'));
        }
    }
}