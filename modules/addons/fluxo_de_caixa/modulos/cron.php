<?php


require_once(dirname(dirname(dirname(dirname((__DIR__))))) . '/tarifasTHX.php');

$crons = [
    'cron_dividas.php',
    'cron_monitora_pagamentos.php',
    'cron-resumo-diario.php'
];


foreach ($crons as $key => $cron) {
    require_once($cron);
}

// require_once($_SERVER['DOCUMENT_ROOT'].'/thx/modules/addons/fluxo_de_caixa/modulos/cron_dividas.php');

// require_once($_SERVER['DOCUMENT_ROOT'].'/thx/modules/addons/fluxo_de_caixa/modulos/cron_monitora_pagamentos.php');