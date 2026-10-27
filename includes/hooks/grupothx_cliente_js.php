<?php

use WHMCS\Config\Setting;

add_hook('ClientAreaFooterOutput', 1, function () {
    $systemUrl = rtrim(Setting::getValue('SystemURL'), '/');

    return '<link rel="stylesheet" href="' . $systemUrl . '/assets/css/grupothx-client.css"><script src="' . $systemUrl . '/assets/js/grupothx-client.js"></script>';
});
