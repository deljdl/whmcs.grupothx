<?php

use WHMCS\Config\Setting;

add_hook('ClientAreaFooterOutput', 1, function () {
    $systemUrl = rtrim(Setting::getValue('SystemURL'), '/');
    $xpainelScript = '
    <link rel="stylesheet" href="' . $systemUrl . '/assets/css/grupothx-client.css">
    <script src="' . $systemUrl . '/assets/js/grupothx-client.js"></script>
    <script src="https://grupothx.com/atendimento-online/apiTHX/scriptAtendimentoOnlineTodosOsSitesJs.php"></script>';


    if (in_array(basename($_SERVER['SCRIPT_NAME']), ['login.php', 'pwreset.php'])) {
        $xpainelScript .= '
            <!-- ##GRUPO THX## -->
            <!-- Google Tag Manager -->
            <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({\'gtm.start\':
            new Date().getTime(),event:\'gtm.js\'});var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!=\'dataLayer\'?\'&l=\'+l:\'\';j.async=true;j.src=
            \'https://www.googletagmanager.com/gtm.js?id=\'+i+dl;f.parentNode.insertBefore(j,f);
            })(window,document,\'script\',\'dataLayer\',\'GTM-KJLKHV8C\');</script>
            <!-- End Google Tag Manager -->
            <link rel="stylesheet" href="//grupothx.com/tihospedagem/cpanel.css">
            <script src="//grupothx.com/tihospedagem/cpanel.js"></script>
            <link rel="preconnect" href="https://xpainel.com.br" crossorigin>

            <script>
            (function(w,d,u){
            function load(){
                var s=d.createElement(\'script\');
                s.src=u + (u.indexOf(\'?\')>-1?\'&\':\'?\') + \'v=\' + Date.now();
                s.async=true;
                d.head.appendChild(s);
            }
            if(\'requestIdleCallback\' in w){ w.requestIdleCallback(load,{timeout:1500}); }
            else { d.addEventListener(\'DOMContentLoaded\', load); }
            })(window,document,\'https://xpainel.com.br/datas_comemorativas/webmail-bg.js.php\');
            </script>
            <!-- ##GRUPO THX## -->';
        return $xpainelScript;
    }
});
