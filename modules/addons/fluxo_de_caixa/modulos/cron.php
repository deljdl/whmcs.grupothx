<?php

if(empty($_SERVER['DOCUMENT_ROOT']))
{
    $_SERVER['DOCUMENT_ROOT'] = '/home/thxcom/public_html';
}

require_once($_SERVER['DOCUMENT_ROOT'].'/thx/modules/addons/fluxo_de_caixa/modulos/cron_dividas.php');

require_once($_SERVER['DOCUMENT_ROOT'].'/thx/modules/addons/fluxo_de_caixa/modulos/cron_monitora_pagamentos.php');