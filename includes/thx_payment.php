<?php
// Dados e apresentação dos pagamentos THX (página de fatura e e-mails).
if (!defined('WHMCS')) {
    exit('Acesso restrito!');
}

use WHMCS\Database\Capsule;

function thx_payment_data($invoiceId, $invoice)
{
    $gateway = (string) ($invoice['paymentmethod'] ?? '');
    $data = [
        'pix' => '',
        'barcode' => '',
        'boleto' => '',
        'total' => (string) ($invoice['total'] ?? ''),
        'due_date' => (string) ($invoice['duedate'] ?? ''),
        'notify_receipt' => false,
    ];

    if ($gateway === 'asaas') {
        $charge = Capsule::schema()->hasTable('asaas_cobrancas')
            ? Capsule::table('asaas_cobrancas')->where('invoice_id', $invoiceId)->first()
            : null;
        if (!$charge) {
            return $data;
        }
        $data['pix'] = (string) ($charge->pix_payload ?? '');
        $data['barcode'] = (string) ($charge->barcode ?? '');
        $data['boleto'] = (string) ($charge->bank_slip_url ?? '');
        $data['due_date'] = (string) ($charge->due_date ?? $data['due_date']);
        return $data;
    }

    if ($gateway === 'gofasgerencianetboleto' && Capsule::schema()->hasTable('gofasgerencianetboleto')) {
        $charge = Capsule::table('gofasgerencianetboleto')
            ->where('invoice_id', $invoiceId)->first();
        if ($charge) {
            $data['pix'] = (string) ($charge->qrcode ?? '');
            $data['barcode'] = (string) ($charge->barcode ?? '');
            $data['boleto'] = (string) ($charge->pdf ?? '');
            return $data;
        }
    }

    // Mantém o Pix padrão THX para faturas sem cobrança de boleto.
    $data['pix'] = (string) file_get_contents('https://pix.grupothx.com/?valor='
        . rawurlencode($data['total']) . '&id=A' . $invoiceId . '&cc=true');
    $data['notify_receipt'] = true;
    return $data;
}

function thx_payment_html($invoiceId, $invoice, $email = false)
{
    $data = thx_payment_data($invoiceId, $invoice);
    $pixCode = htmlspecialchars($data['pix'], ENT_QUOTES, 'UTF-8');
    $barcode = htmlspecialchars($data['barcode'], ENT_QUOTES, 'UTF-8');
    $boletoUrl = htmlspecialchars($data['boleto'], ENT_QUOTES, 'UTF-8');
    $vencimento = htmlspecialchars(date('d/m/Y', strtotime($data['due_date'])), ENT_QUOTES, 'UTF-8');
    $pixImage = $data['pix'] !== ''
        ? 'https://pix.grupothx.com/copiaecolatoqrcode.php?codigo=' . rawurlencode($data['pix'])
        : '';
    $pixLogo = 'https://grupothx.com/imagens/logopix.png';
    $boletoLogo = 'https://grupothx.com/imagens/logoboleto.jpg';

    $boleto = '';
    if ($data['boleto'] !== '' && $data['barcode'] !== '') {
        if ($email) {
            $boleto = '
            <tr><td style="text-align:center;vertical-align:middle"><img src="' . $boletoLogo . '"></td>
            <td style="text-align:center"><p><strong>Código de Barras:&nbsp;</strong></p>'
            . getBarCode($data['barcode']) . '<br>' . $barcode
            . '<p><strong><a style="font-size:12px;border:solid #ccc 1px;display:block;padding:5px;background:red;color:white;text-decoration:none;text-align:center" href="'
            . $boletoUrl . '" target="_blank">Imprimir Boleto</a></strong></p></td></tr>
            <tr style="height:10px"><td colspan="2"><hr style="border:solid red 1px"></td></tr>';
        } else {
            $boleto = '
            <tr><td style="text-align:center;vertical-align:middle;width:20%"><img class="mobilehidden" src="' . $boletoLogo . '"></td>
            <td><br><p><strong><i class="bx bx-barcode-reader"></i> Código de Barras:</strong><br>'
            . getBarCode($data['barcode']) . '<br><span id="codigoBarras">' . $barcode . '</span></p>
            <button class="btn-copy" onclick="copiarTexto(\'codigoBarras\',this,\'Código de barras copiado!\')"><i class="bx bx-copy"></i> Copiar código de barras</button>
            <a class="btn-copy btn-copy2 fright" href="' . $boletoUrl . '" target="_blank" rel="noopener"><i class="ion-printer"></i> Imprimir Boleto</a>
            </td></tr><tr style="height:10px"><td colspan="2" height="10"><hr style="border:solid red 1px"></td></tr>';
        }
    }

    $pix = '';
    if ($data['pix'] !== '') {
        if ($email) {
            $pix = '
            <tr style="height:131px"><td style="padding:20px;text-align:center;vertical-align:middle"><img src="' . $pixLogo . '"></td>
            <td style="padding:20px;text-align:center;vertical-align:middle;width:50%">
            <img style="width:80%;max-width:250px;border:solid #29bbac 4px" src="'
                . htmlspecialchars($pixImage, ENT_QUOTES, 'UTF-8') . '"><br>
            <strong style="font-size:15px;line-height:40px">PIX QR Code e Copia e Cola</strong><br>
            <code style="font-size:12px;border:solid #ccc 1px;display:block;padding:5px;text-decoration:none;white-space:normal;background:none;color:#333">' . $pixCode . '</code>
            </td></tr>';
        } else {
            $pix = '
            <tr style="height:131px"><td style="padding:20px;text-align:center;vertical-align:middle;width:20%">
            <img class="mobilehidden" src="' . $pixLogo . '"></td>
            <td style="padding:20px;text-align:center;vertical-align:middle">
            <img class="qrcode-pix" src="' . htmlspecialchars($pixImage, ENT_QUOTES, 'UTF-8') . '"><br>
            <strong style="font-size:15px;line-height:40px"><i class="ri-pix-line"></i> PIX QR Code e Copia e Cola</strong><br>
            <code id="pixCode" class="copia-cola">' . $pixCode . '</code>
            <button class="btn-copy" onclick="copiarTexto(\'pixCode\',this,\'Código PIX copiado!\')"><i class="bx bx-copy"></i> Copiar PIX</button>
            </td></tr>';
        }
        if ($data['notify_receipt']) {
            $pix .= '<tr><td></td><td><span style="font-size:12px;text-align:center;font-weight:bold;border:solid #ccc 1px;display:block;padding:5px;background:red;color:white">Após o pagamento via PIX não esqueça de responder com o comprovante.</span></td></tr>';
        }
    }

    if ($email) {
        return '<table width="100%"><tbody>
            <tr><td colspan="2">' . getNotaFiscal($invoiceId, 'btn-copy btn-nota', 'display:block;background:#1a3c6a;color:white;text-decoration:none;padding:6px 10px;text-align:right;font-weight:bold;') . '</td></tr>
            <tr style="background-color:red;color:#fff;padding:7px 15px;font-weight:bold;height:17px">
            <td style="padding:5px 10px" colspan="2">Vencimento: ' . $vencimento . '</td></tr>'
            . $boleto . $pix . '</tbody></table>';
    }

    $html = '<div class="thx-vencimento"><i class="fa fa-calendar"></i> Vencimento: ' . $vencimento
        . '</div><h3 class="thx-titulo-pagamento">Pague sua fatura</h3>';
    if ($pix === '' && $boleto === '') {
        return $html . '<p>Dados de pagamento ainda não disponíveis.</p>';
    }
    $html .= '<div class="thx-pagamentos">';
    if ($pix !== '') {
        $html .= '<input type="radio" name="thx-pagamento" id="thx-tab-pix" checked>
            <label for="thx-tab-pix" class="thx-aba thx-aba-pix"><i class="ri-pix-line"></i> PIX</label>';
    }
    if ($boleto !== '') {
        $html .= '<input type="radio" name="thx-pagamento" id="thx-tab-boleto"'
            . ($pix === '' ? ' checked' : '') . '>
            <label for="thx-tab-boleto" class="thx-aba thx-aba-boleto"><i class="bx bx-barcode-reader"></i> Boleto</label>';
    }
    if ($pix !== '') {
        $html .= '<div class="thx-painel thx-painel-pix"><table><tbody>' . $pix . '</tbody></table></div>';
    }
    if ($boleto !== '') {
        $html .= '<div class="thx-painel thx-painel-boleto"><table><tbody>' . $boleto . '</tbody></table></div>';
    }
    return $html . '</div>';
}
