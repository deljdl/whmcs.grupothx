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
            'Description' => 'Selecione o ambiente usado pelo gateway. Durante os testes, somente o Sandbox será utilizado.',
        ],
        'sandboxSection' => [
            'Type' => 'System',
            'Value' => '<div style="border-top:1px solid #ccc;padding-top:10px;margin-top:5px;"><strong>Sandbox</strong></div>',
        ],
        'sandboxApiKey' => [
            'FriendlyName' => 'Token API Sandbox',
            'Type' => 'text',
            'Size' => '80',
            'Description' => 'Credencial exclusiva do ambiente de testes. <button type="button" class="btn btn-default btn-xs" onclick="var b=this,s=b.nextElementSibling;b.disabled=true;s.textContent=&#39; Testando...&#39;;fetch(&#39;/modules/gateways/asaas/test.php?environment=sandbox&#39;,{credentials:&#39;same-origin&#39;}).then(function(r){return r.text().then(function(t){s.textContent=&#39; &#39;+t;s.style.color=r.ok&&t.includes(&#39;HTTP 200&#39;)?&#39;green&#39;:&#39;#b94a48&#39;;});}).catch(function(){s.textContent=&#39; Falha na comunicação.&#39;;s.style.color=&#39;#b94a48&#39;;}).finally(function(){b.disabled=false;});">Testar conexão Sandbox</button><span role="status" aria-live="polite"></span> <small>Salve o token antes de testar. Consulta somente leitura.</small>',
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
            'Description' => 'Credencial exclusiva da produção. <button type="button" class="btn btn-default btn-xs" onclick="var b=this,s=b.nextElementSibling;b.disabled=true;s.textContent=&#39; Testando...&#39;;fetch(&#39;/modules/gateways/asaas/test.php?environment=production&#39;,{credentials:&#39;same-origin&#39;}).then(function(r){return r.text().then(function(t){s.textContent=&#39; &#39;+t;s.style.color=r.ok&&t.includes(&#39;HTTP 200&#39;)?&#39;green&#39;:&#39;#b94a48&#39;;});}).catch(function(){s.textContent=&#39; Falha na comunicação.&#39;;s.style.color=&#39;#b94a48&#39;;}).finally(function(){b.disabled=false;});">Testar conexão Produção</button><span role="status" aria-live="polite"></span> <small>Salve o token antes de testar. Consulta somente leitura.</small>',
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
