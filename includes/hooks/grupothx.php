<?php


//Laravel DataBase
use WHMCS\Database\Capsule;

//Bloqueia o acesso direto ao arquivo
if (!defined("WHMCS")){
	die("Acesso restrito!");
}


add_hook('EmailPreSend', 1, function($vars) {
	logModuleCall('grupothx_hooks', 'EmailPreSend', 'start', $vars, '');
    
    if (
		$vars['messagename'] === 'Invoice Created' ||
		$vars['messagename'] === 'Invoice Payment Reminder' ||
		$vars['messagename'] === 'First Invoice Overdue Notice' ||
		$vars['messagename'] === 'Second Invoice Overdue Notice' ||
		$vars['messagename'] === 'Third Invoice Overdue Notice' ||
		$vars['messagename'] === 'Invoice Modified' ||
		$vars['messagename'] === 'Negativacao' ||
		$vars['messagename'] === 'Negativacao 1' ||
		$vars['messagename'] === 'Negativacao 2' ||
		$vars['messagename'] === 'Negativacao 3' ||
		$vars['messagename'] === 'Negativacao 3' ||
      $vars['messagename'] === 'fatura-atualizada'
	) {
		$grupothx_merge_fields	= array();

		$invoice	= localAPI( 'GetInvoice', array('invoiceid' => $vars['relid']), false);

		logModuleCall('grupothx_hooks', 'EmailPreSend', 'invoice', $invoice, '');

			// Merge Fields
			$grupothx_merge_fields['grupothx_pix_copia_e_cola']		= file_get_contents('https://pix.grupothx.com/?valor='.$invoice['total'].'&id=A'.$vars['relid'].'&cc=true');

         $boleto = '';

         $pix = '
         <tr style="height:131px">
            <td style="padding: 20px;text-align:center;vertical-align:middle">
               <img src="https://grupothx.com/imagens/logopix.png">
            </td>
            <td style="padding: 20px;text-align: center; vertical-align: middle; width: 50%;">
               <img style="width: 80%;max-width: 250px;border: solid #29bbac 4px" src="https://pix.grupothx.com/?id=A'.$vars['relid'].'&amp;valor='.$invoice['total'].'"><br><strong style="font-size: 15px;line-height: 40px;">PIX QR Code e Copia e Cola (R$ '.$invoice['total'].')</strong><br><code style="font-size: 12px;border:solid #ccc 1px;display:block;padding:5px;text-decoration:none;white-space: normal;background: none;color: #333333;">'.$grupothx_merge_fields['grupothx_pix_copia_e_cola'].'</code>
            </td>
         </tr>
         <tr>
            <td></td>
            <td><span style="font-size: 12px; text-align:center; font-weight:bold; border: solid #CCC 1px; display: block; padding: 5px;background: red;color: white;">Após o pagamento via PIX não esqueça de responder com o comprovante.</span></td>
         </tr>';

         ########################################################################GORFAS BOLETO
         $boleto_gerencianet = array();
			foreach( Capsule::table('gofasgerencianetboleto') -> where('invoice_id', '=', $vars['relid']) -> get(
				array( 'invoice_id', 'charge_id', 'link', 'pdf', 'expire_at', 'total', 'barcode', 'charge_id', 'status', 'api_mode', 'qrcode', 'qrcode_image' ) ) as $key => $value ) {
				$boletos_gerencianet[$key] = json_decode(json_encode($value), true);
			}

         logModuleCall('grupothx_hooks', 'EmailPreSend', 'boleto_gerencianet', $boletos_gerencianet, '');

         if($boletos_gerencianet) //isset($boletos_gerencianet['0']))
         {
               $boleto_gerencianet = $boletos_gerencianet['0'];

               

               // Merge Fields
               $grupothx_merge_fields['grupothx_link']			= $boleto_gerencianet['link'];
               $grupothx_merge_fields['grupothx_pdf']			= $boleto_gerencianet['pdf'];
               $grupothx_merge_fields['grupothx_barcode']		= $boleto_gerencianet['barcode'];
               $grupothx_merge_fields['grupothx_expire_at']	= date('d/m/Y', strtotime($boleto_gerencianet['expire_at']));
               $grupothx_merge_fields['grupothx_total']		= number_format($boleto_gerencianet['total']/100,  2, ',', '.' );
               $grupothx_merge_fields['grupothx_charge_id']	= $boleto_gerencianet['charge_id'];
               $grupothx_merge_fields['grupothx_api_mode']		= $boleto_gerencianet['api_mode'];
               $grupothx_merge_fields['grupothx_qrcode']		= $boleto_gerencianet['qrcode'];

               if(true) //$params['linkbilletonemail'])
               {
                  $grupothx_merge_fields['invoice_link']		= $boleto_gerencianet['link'];
               }

               $boleto = '
               <tr>
                  <td style="text-align:center;vertical-align:middle"><img src="https://grupothx.com/imagens/logoboleto.jpg"></td>
                  <td style="text-align: center;">
                     <p><strong>Código de Barras:&nbsp;</strong></p>
                     '.getBarCode($grupothx_merge_fields['grupothx_barcode']).'
                     '.$grupothx_merge_fields['grupothx_barcode'].'
                     <p><strong><a style="font-size:12px;border:solid #ccc 1px;display:block;padding:5px;background:red;color:white;text-decoration:none;text-align:center" href="'.$grupothx_merge_fields['grupothx_pdf'].'" target="_blank">Imprimir Boleto</a></strong></p>
                  </td>
               </tr>
               <tr style="height:10px">
                  <td colspan="2" height="10"><hr style="border:solid red 1px"></td>
               </tr>';

               $pix = '
               <tr style="height:131px">
                  <td style="padding: 20px;text-align:center;vertical-align:middle">
                     <img src="https://grupothx.com/imagens/logopix.png">
                  </td>
                  <td style="padding: 20px;text-align: center; vertical-align: middle; width: 50%;">
                     <img style="width: 80%;max-width: 250px;border: solid #29bbac 4px" src="https://pix.grupothx.com/copiaecolatoqrcode.php?codigo='.$grupothx_merge_fields['grupothx_qrcode'].'"><br><strong style="font-size: 15px;line-height: 40px;">PIX QR Code e Copia e Cola (R$ '.$grupothx_merge_fields['grupothx_total'].')</strong><br><code style="font-size: 12px;border:solid #ccc 1px;display:block;padding:5px;text-decoration:none;white-space: normal;background: none;color: #333333;">'.$grupothx_merge_fields['grupothx_qrcode'].'</code>
                  </td>
               </tr>';
         }
         ########################################################################GORFAS BOLETO FIM
         


         
         $vencimento = strftime('%d/%m/%Y', strtotime($invoice['duedate']));


         $grupothx_merge_fields['grupothx_html_formas_pagamento'] = '
         <table width="100%">
            <tbody>
               <tr>
                  <td colspan="2">'.getNotaFiscal($vars['relid'], 'btn-copy btn-nota', 'display:block;background: #1a3c6a;color: white;text-decoration: none;padding: 6px 10px;text-align: right;font-weight: bold;').'</td>
               </tr>
               <tr style="background-color:red;color:#ffffff;padding:7px 15px;font-weight:bold;height:17px">
                  <td style="padding: 5px 10px;" colspan="2">Vencimento: '.$vencimento.'</td>
               </tr>
               '.$boleto.'
               '.$pix.'
            </tbody>
         </table>';


			logModuleCall('grupothx_hooks', 'EmailPreSend', 'grupothx_merge_fields', $grupothx_merge_fields, '');

			return $grupothx_merge_fields;
		

    }
	else { // Not
		return;
	}
});



add_hook('EmailTplMergeFields', 1, function($vars) {
   $grupothx = array();
   $grupothx['grupothx_pix_copia_e_cola'] = 'GRUPO THX - PIX Copia e Cola';
   $grupothx['grupothx_html_formas_pagamento'] = 'GRUPO THX - HTML com todas as formas de pagamento disponíveis.';
    return $grupothx;
});