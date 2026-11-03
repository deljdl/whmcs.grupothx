<?php
use WHMCS\Authentication\CurrentUser;
use WHMCS\Database\Capsule;

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/tarifasTHX.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(404);
    exit('Fatura não encontrada.');
}

$usuario = new CurrentUser();
$cliente = $usuario->client();

if (!$cliente && !$usuario->isAuthenticatedAdmin()) {
    header('Location: /clientarea.php', true, 302);
    exit;
}

$clienteFatura = Capsule::table('tblinvoices')->where('id', $id)->value('userid');
if (!$clienteFatura) {
    http_response_code(404);
    exit('Fatura não encontrada.');
}
if (!$usuario->isAuthenticatedAdmin() && (int)$cliente->id !== (int)$clienteFatura) {
    http_response_code(403);
    exit('Acesso negado.');
}

header('Cache-Control: no-store');
header('Location: /viewinvoice-thx/' . rawurlencode(criptX((string)$id)), true, 302);
exit;
