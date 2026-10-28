<?php

add_hook('AdminAreaHeadOutput', 1, function () {
    return '<script>console.log("HOOK AdminAreaHeadOutput HEAD EXECUTADO");</script>';
});

add_hook('AdminAreaFooterOutput', 1, function () {
    return '<script>console.log("HOOK AdminAreaFooterOutput FOOTER EXECUTADO");</script>';
});

add_hook('AdminAreaPage', 1, function () {
    return '<script>console.log("HOOK AdminAreaPage PAGE EXECUTADO");</script>';
});