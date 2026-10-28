<?php

add_hook('AdminAreaHeadOutput', 1, function ($vars) {
    return '
        <link rel="stylesheet" href="/thx/assets/css/admin.css">
        <script src="/thx/assets/js/admin.js"></script>
    ';
});