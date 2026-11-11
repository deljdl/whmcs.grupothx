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
        'sandboxApiKey' => [
            'FriendlyName' => 'Token API Sandbox',
            'Type' => 'text',
            'Size' => '80',
            'Description' => 'Credencial exclusiva do ambiente de testes.',
        ],
        'productionApiKey' => [
            'FriendlyName' => 'Token API Produção',
            'Type' => 'text',
            'Size' => '80',
            'Description' => 'Credencial exclusiva da produção.',
        ],
        'sandboxWebhookToken' => [
            'FriendlyName' => 'Token webhook Sandbox',
            'Type' => 'text',
            'Size' => '80',
            'Description' => 'Token gerado no webhook do ambiente Sandbox.',
        ],
        'asaasFee' => [
            'FriendlyName' => 'Tarifa Asaas (R$)',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '0,99',
            'Description' => 'Valor de referência da tarifa por pagamento. Ainda não utilizado nas baixas.',
        ],
        'productionWebhookToken' => [
            'FriendlyName' => 'Token webhook Produção',
            'Type' => 'text',
            'Size' => '80',
            'Description' => 'Token gerado no webhook de Produção.<div style="margin-top:8px;max-width:100%;"><strong>URL para cadastrar no webhook do Asaas:</strong><div style="overflow-wrap:anywhere;word-break:break-all;max-width:100%;"><code>https://central.grupothx.com.br/modules/gateways/callback/asaas.php</code></div><small>Endpoint ainda em desenvolvimento. Não ative os webhooks.</small></div>',
        ],

    ];
}

function asaas_link($params)
{
    // Bloqueio intencional: a emissão só será implementada após validação no Sandbox.
    return '<div class="alert alert-info">Pagamento Asaas em desenvolvimento. '
        . 'Nenhuma cobrança será emitida por este gateway.</div>';
}
