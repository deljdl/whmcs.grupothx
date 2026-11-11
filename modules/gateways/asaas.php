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
            'Type' => 'password',
            'Size' => '80',
            'Description' => 'Credencial exclusiva do ambiente de testes. <button type="button" class="btn btn-default btn-xs" onclick="var i=this.closest('td').querySelector('input[type=password], input[type=text]'); if(i){i.type=i.type==='password'?'text':'password';this.textContent=i.type==='password'?'Mostrar':'Ocultar';}">Mostrar</button>',
        ],
        'productionApiKey' => [
            'FriendlyName' => 'Token API Produção',
            'Type' => 'password',
            'Size' => '80',
            'Description' => 'Credencial exclusiva da produção. <button type="button" class="btn btn-default btn-xs" onclick="var i=this.closest('td').querySelector('input[type=password], input[type=text]'); if(i){i.type=i.type==='password'?'text':'password';this.textContent=i.type==='password'?'Mostrar':'Ocultar';}">Mostrar</button>',
        ],
        'sandboxWebhookToken' => [
            'FriendlyName' => 'Token webhook Sandbox',
            'Type' => 'password',
            'Size' => '80',
            'Description' => 'Token gerado no webhook do ambiente Sandbox. <button type="button" class="btn btn-default btn-xs" onclick="var i=this.closest('td').querySelector('input[type=password], input[type=text]'); if(i){i.type=i.type==='password'?'text':'password';this.textContent=i.type==='password'?'Mostrar':'Ocultar';}">Mostrar</button>',
        ],
        'productionWebhookToken' => [
            'FriendlyName' => 'Token webhook Produção',
            'Type' => 'password',
            'Size' => '80',
            'Description' => 'Token gerado no webhook de Produção. <button type="button" class="btn btn-default btn-xs" onclick="var i=this.closest('td').querySelector('input[type=password], input[type=text]'); if(i){i.type=i.type==='password'?'text':'password';this.textContent=i.type==='password'?'Mostrar':'Ocultar';}">Mostrar</button><div style="margin-top:8px;max-width:100%;"><strong>URL para cadastrar no webhook do Asaas:</strong><div style="overflow-wrap:anywhere;word-break:break-all;max-width:100%;"><code>https://central.grupothx.com.br/modules/gateways/callback/asaas.php</code></div><small>Endpoint ainda em desenvolvimento. Não ative os webhooks.</small></div>',
        ],

    ];
}

function asaas_link($params)
{
    // Bloqueio intencional: a emissão só será implementada após validação no Sandbox.
    return '<div class="alert alert-info">Pagamento Asaas em desenvolvimento. '
        . 'Nenhuma cobrança será emitida por este gateway.</div>';
}
