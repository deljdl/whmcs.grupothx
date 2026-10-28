<?php
die('Disabled');
require_once __DIR__ . '/init.php';

require_once __DIR__ . '/tarifasTHX.php';

echo '<pre>';
var_dump(getDadosClienteById(496));
echo '</pre>';