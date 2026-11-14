
<?php

add_hook('EmailPreSend', 1, function ($vars) {
    return [
        'promocoes_thx_html' => '<div style="padding:15px;background:#eef5ff;text-align:center">
            <strong>TESTE PROMOÇÕES THX</strong>
            <p>Conteúdo inserido automaticamente pelo hook.</p>
        </div>'
    ];
});
