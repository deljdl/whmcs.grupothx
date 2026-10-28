<?php

add_hook('AdminAreaHeadOutput', 1, function () {
    return '<script>console.log("HOOK ADMIN HEAD EXECUTADO");</script>';
});

add_hook('AdminAreaFooterOutput', 1, function () {
    return '<div style="position:fixed;top:10px;right:10px;z-index:999999;background:red;color:white;padding:15px">
        TESTE HOOK ADMIN
    </div>';
});