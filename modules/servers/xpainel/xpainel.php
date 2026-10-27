<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

function xpainel_MetaData()
{
    return array(
        'DisplayName' => 'X-Painel',
        'APIVersion' => '1.1',
        'RequiresServer' => false,
    );
}

function xpainel_ConfigOptions()
{
    return array();
}
