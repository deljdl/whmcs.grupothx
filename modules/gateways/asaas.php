<?php
/**
 * Gateway Asaas - Grupo THX
 * Etapa inicial: configuração apenas. Nenhuma cobrança é criada.
 */

if (!defined('WHMCS')) {
    exit('Acesso direto não permitido.');
}

function asaas_MetaData()
{
    return [
        'DisplayName' => 'Asaas - Boleto com Pix',
        'APIVersion' => '1.1',
    ];
}

function asaas_config()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Asaas - Boleto com Pix',
        ],
        'environment' => [
            'FriendlyName' => 'Ambiente',
            'Type' => 'dropdown',
            'Options' => 'sandbox,production',
            'Default' => 'sandbox',
            'Description' => 'Produção não libera a emissão de cobranças.',
        ],
        'apiKey' => [
            'FriendlyName' => 'Chave da API Asaas',
            'Type' => 'password',
            'Size' => '80',
            'Description' => 'Credencial do ambiente selecionado.',
        ],
        'webhookToken' => [
            'FriendlyName' => 'Token do webhook',
            'Type' => 'password',
            'Size' => '80',
            'Description' => 'Token configurado no painel Asaas (não é a chave da API).',
        ],
    ];
}

function asaas_link($params)
{
    // Bloqueio intencional: a emissão só será implementada após validação no Sandbox.
    return '<div class="alert alert-info">Pagamento Asaas em desenvolvimento. '
        . 'Nenhuma cobrança será emitida por este gateway.</div>';
}
