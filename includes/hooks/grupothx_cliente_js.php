<?php

use WHMCS\Config\Setting;

add_hook('ClientAreaFooterOutput', 1, function () {
    $systemUrl = rtrim(Setting::getValue('SystemURL'), '/');

    return '<script src="' . $systemUrl . '/assets/js/grupothx-client.js"></script>';
});
