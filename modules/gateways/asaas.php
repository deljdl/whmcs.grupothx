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
        'minimumAmount' => [
            'FriendlyName' => 'Valor mínimo do Boleto (R$)',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '5',
            'Description' => 'Valor mínimo da fatura para permitir emissão de boleto. Ex.: 5 equivale a R$ 5,00.',
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
    // Proteção obrigatória: nenhuma chamada financeira no ambiente de Produção.
    if (($params['environment'] ?? 'sandbox') !== 'sandbox') {
        return '<div class="alert alert-warning">Emissão em Produção ainda bloqueada durante os testes.</div>';
    }

    require_once __DIR__ . '/asaas/api.php';

    try {
        $charge = asaas_get_or_create_charge($params);
        $key = (string) ($params['sandboxApiKey'] ?? '');
        $id = (string) $charge['charge_id'];
        $boleto = (string) ($charge['bank_slip_url'] ?: $charge['invoice_url']);
        $html = '<div class="alert alert-info"><strong>Asaas Sandbox</strong> — cobrança de teste, sem valor financeiro real.</div>';

        if ($boleto !== '' && preg_match('~^https://[a-z0-9.-]*asaas\.com(?:\.br)?/~i', $boleto)) {
            $html .= '<p><a href="' . htmlspecialchars($boleto, ENT_QUOTES, 'UTF-8')
                . '" target="_blank" rel="noopener">Visualizar boleto bancário</a></p>';
        }

        try {
            $pix = asaas_api($key, 'GET', 'payments/' . rawurlencode($id) . '/pixQrCode');
            if (!empty($pix['encodedImage'])) {
                $html .= '<p><img alt="QR Code Pix" style="max-width:180px" src="data:image/png;base64,'
                    . htmlspecialchars((string) $pix['encodedImage'], ENT_QUOTES, 'UTF-8') . '"></p>';
            }
            if (!empty($pix['payload'])) {
                $html .= '<label>Pix Copia e Cola</label><textarea readonly class="form-control" rows="3" onclick="this.select()">'
                    . htmlspecialchars((string) $pix['payload'], ENT_QUOTES, 'UTF-8') . '</textarea>';
            }
        } catch (\Throwable $exception) {
            $html .= '<p>Pix temporariamente indisponível. Utilize o boleto.</p>';
        }
        return $html;
    } catch (\Throwable $exception) {
        logModuleCall('asaas', 'sandbox_invoice_error', [
            'invoice_id' => (int) ($params['invoiceid'] ?? 0),
        ], ['error' => $exception->getMessage()]);
        return '<div class="alert alert-warning">Asaas Sandbox: '
            . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
    }
}
