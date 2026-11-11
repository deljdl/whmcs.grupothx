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
        'sandboxSection' => [
            'Type' => 'System',
            'Value' => '<div style="border-top:1px solid #ccc;padding-top:10px;margin-top:5px;"><strong>Sandbox</strong></div>',
        ],
        'sandboxApiKey' => [
            'FriendlyName' => 'Token API Sandbox',
            'Type' => 'text',
            'Size' => '80',
            'Description' => 'Credencial exclusiva do ambiente de testes.',
        ],
        'sandboxWebhookToken' => [
            'FriendlyName' => 'Token webhook Sandbox',
            'Type' => 'text',
            'Size' => '80',
            'Description' => 'Token gerado no webhook do ambiente Sandbox.',
        ],
        'productionSection' => [
            'Type' => 'System',
            'Value' => '<div style="border-top:1px solid #ccc;padding-top:10px;margin-top:5px;"><strong>Produção</strong></div>',
        ],
        'productionApiKey' => [
            'FriendlyName' => 'Token API Produção',
            'Type' => 'text',
            'Size' => '80',
            'Description' => 'Credencial exclusiva da produção.',
        ],
        'productionWebhookToken' => [
            'FriendlyName' => 'Token webhook Produção',
            'Type' => 'text',
            'Size' => '80',
            'Description' => 'Token gerado no webhook de Produção.',
        ],
        'webhookSection' => [
            'Type' => 'System',
            'Value' => '<div style="padding-top:5px;"><strong>URL para cadastrar no webhook do Asaas:</strong><div style="overflow-wrap:anywhere;word-break:break-all;"><code>https://central.grupothx.com.br/modules/gateways/callback/asaas.php</code></div><small>Endpoint ainda em desenvolvimento. Não ative os webhooks.</small></div>',
        ],
        'otherSection' => [
            'Type' => 'System',
            'Value' => '<div style="border-top:1px solid #ccc;padding-top:10px;margin-top:5px;"><strong>Outros</strong></div>',
        ],
        'asaasFee' => [
            'FriendlyName' => 'Tarifa Asaas (R$)',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '0,99',
            'Description' => 'Valor de referência da tarifa por pagamento. Ainda não utilizado nas baixas.',
        ],

    ];
}

function asaas_link($params)
{
    // Bloqueio intencional: a emissão só será implementada após validação no Sandbox.
    return '<div class="alert alert-info">Pagamento Asaas em desenvolvimento. '
        . 'Nenhuma cobrança será emitida por este gateway.</div>';
}
