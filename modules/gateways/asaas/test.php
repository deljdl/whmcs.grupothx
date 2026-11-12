<?php
/**
 * Teste de leitura da API Asaas Sandbox ou Produção.
 * Não cria clientes, cobranças ou alterações no WHMCS.
 */
require_once __DIR__ . '/../../../init.php';

if (empty($_SESSION['adminid'])) {
    http_response_code(403);
    exit('Acesso permitido somente a administradores autenticados no WHMCS.');
}

require_once __DIR__ . '/../../../includes/gatewayfunctions.php';

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');

$environment = $_GET['environment'] ?? '';
if (!in_array($environment, ['sandbox', 'production'], true)) {
    http_response_code(400);
    exit('Ambiente inválido.');
}

$params = getGatewayVariables('asaas');
$keyField = $environment === 'sandbox' ? 'sandboxApiKey' : 'productionApiKey';
$key = trim((string) ($params[$keyField] ?? ''));
$apiUrl = $environment === 'sandbox'
    ? 'https://api-sandbox.asaas.com/v3/customers?limit=1'
    : 'https://api.asaas.com/v3/customers?limit=1';
$environmentName = $environment === 'sandbox' ? 'Sandbox' : 'Produção';

if (empty($params['type'])) {
    exit('Asaas não está ativo no WHMCS.');
}
if ($key === '') {
    exit('Token API ' . $environmentName . ' não configurado. Salve a configuração e tente novamente.');
}
if (!function_exists('curl_init')) {
    exit('Extensão cURL indisponível no PHP.');
}

$curl = curl_init($apiUrl);
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_HTTPHEADER => [
        'accept: application/json',
        'access_token: ' . $key,
        'User-Agent: GrupoTHX-WHMCS-Asaas/1.0',
    ],
]);

$response = curl_exec($curl);
$status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);
curl_close($curl);

if ($response === false) {
    exit('Falha de conexão com ' . $environmentName . ': ' . $error);
}
if ($status === 200) {
    exit('Conexão com o Asaas ' . $environmentName . ' realizada com sucesso (HTTP 200). Nenhuma cobrança foi criada.');
}

echo 'Falha no teste de conexão. HTTP ' . $status . '.';
if ($status === 401) {
    echo ' Verifique se o token pertence ao ambiente ' . $environmentName . '.';
}
