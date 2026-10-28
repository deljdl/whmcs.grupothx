<?php


require_once(dirname(dirname(dirname(dirname((__DIR__))))) . '/tarifasTHX.php');

$crons = [
    'cron_dividas' => 'modules/addons/fluxo_de_caixa/modulos/cron_dividas.php',
    'cron_monitora_pagamentos' => 'modules/addons/fluxo_de_caixa/modulos/cron_monitora_pagamentos.php',
];


foreach ($crons as $key => $cron) {
    echo '<br />' . $key . ' - ' . $cron;
    //require_once($_SERVER['DOCUMENT_ROOT'] . $cron);
}

// require_once($_SERVER['DOCUMENT_ROOT'].'/thx/modules/addons/fluxo_de_caixa/modulos/cron_dividas.php');

// require_once($_SERVER['DOCUMENT_ROOT'].'/thx/modules/addons/fluxo_de_caixa/modulos/cron_monitora_pagamentos.php');