<?php
/**
 * Módulo Gerencinet Boleto para WHMCS
 * @author		Mauricio Gofas | gofas.net
 * @see			https://gofas.net/?p=7893
 * @copyright	2017 https://gofas.net
 * @license		https://gofas.net?p=9340
 * @support		https://gofas.net/?p=7856
 * @version		2.2.2
 */

if(!defined('WHMCS')) { die('Esse arquivo não pode ser acessado diretamente'); }
use WHMCS\Database\Capsule;
require_once __DIR__.'/gofasgerencianetboleto/sdk/efi_rest.php';
use Gerencianet\Exception\GerencianetException;
use Gerencianet\Gerencianet;
include __DIR__.'/gofasgerencianetboleto/includes/configuration.php';

function gofasgerencianetboleto_link( $params ) {

	// Verify if generate billet
	if ( (stripos($_SERVER['REQUEST_URI'], 'viewinvoice') ) or 
	( !stripos($_SERVER['REQUEST_URI'], 'viewinvoice') and ($params['billetonemail'] ) ) ) {
		$generate_billet = true;
	}
	
	include __DIR__.'/gofasgerencianetboleto/includes/params.php';
	
	if ( $generate_billet ) {
		include __DIR__.'/gofasgerencianetboleto/includes/functions.php';

		// Verify Database
		 $ggnb_verifyInstall = ggnb_verifyInstall();
		 if($debug_or_log){
			 $log_result['ggnb_verifyInstall']	= $ggnb_verifyInstall;
		}
		if($ggnb_verifyInstall['error']) {
			 $error = $ggnb_verifyInstall['error'];
		 }
    	if(!$error){
			$api = new Gerencianet($opt);
		}
		/**
		*
		* Transactions verifycation
		*
		*/
		
        // A cobrança emitida é imutável. Utilizar primeiro o boleto salvo no WHMCS.
        // Se existir um ID de cobrança, jamais emitir outro boleto automaticamente.
        $boletoLocal = Capsule::table('gofasgerencianetboleto')
            ->where('invoice_id', $invoice_id)
            ->where('api_mode', $api_mode)
            ->when($trans_id, function ($query) use ($trans_id) {
                return $query->where('charge_id', $trans_id);
            })
            ->orderBy('charge_id', 'desc')
            ->first();

        // Um boleto salvo no banco também impede reemissão caso sua
        // transação não tenha sido gravada corretamente no WHMCS.
        if (!$trans_id && $boletoLocal && (int)$boletoLocal->charge_id > 0) {
            $trans_id = (int)$boletoLocal->charge_id;
        }

        if ($trans_id && !$error) {
            $chargeExistID = (int)$trans_id;
            $link = trim((string)($boletoLocal->link ?? ''));
            $barcode = trim((string)($boletoLocal->barcode ?? ''));
            $expire_at = (string)($boletoLocal->expire_at ?? '');
            $pix = array(
                'qrcode' => (string)($boletoLocal->qrcode ?? ''),
                'qrcode_image' => (string)($boletoLocal->qrcode_image ?? '')
            );

            // Não consultar a Efí para boleto completo. Pix pode ser opcional
            // para boletos antigos; sua ausência isolada não bloqueia o pagamento.
            if ($link === '' || $barcode === '') {
                $chargeExist_ = ggnb_detail_charge($api, $trans_id, $debug_or_log);
                $chargeData = $chargeExist_['result']['data'] ?? array();

                if (empty($chargeData)) {
                    $error = 'Não foi possível recuperar a cobrança existente '.$trans_id.': '
                        .($chargeExist_['error'] ?? 'resposta vazia da API Efí');
                } elseif ((int)($chargeData['charge_id'] ?? 0) !== (int)$trans_id) {
                    $error = 'A Efí retornou uma cobrança diferente da solicitada ('.$trans_id.').';
                } else {
                    $billetData = $chargeData['payment']['banking_billet'] ?? array();
                    if ($link === '') $link = trim((string)($billetData['link'] ?? $chargeData['link'] ?? ''));
                    if ($barcode === '') $barcode = trim((string)($billetData['barcode'] ?? $chargeData['barcode'] ?? ''));
                    if ($expire_at === '') $expire_at = (string)($billetData['expire_at'] ?? $chargeData['expire_at'] ?? '');
                    $pixApi = $chargeData['pix'] ?? $billetData['pix'] ?? array();
                    if (empty($pix['qrcode'])) $pix['qrcode'] = (string)($pixApi['qrcode'] ?? '');
                    if (empty($pix['qrcode_image'])) $pix['qrcode_image'] = (string)($pixApi['qrcode_image'] ?? '');

                    // Preencher somente lacunas, sem atualizar valor ou vencimento.
                    try {
                        if ($boletoLocal) {
                            $campos = array();
                            if (empty($boletoLocal->link) && $link !== '') $campos['link'] = $link;
                            if (empty($boletoLocal->barcode) && $barcode !== '') $campos['barcode'] = $barcode;
                            if (empty($boletoLocal->qrcode) && $pix['qrcode'] !== '') $campos['qrcode'] = $pix['qrcode'];
                            if (empty($boletoLocal->qrcode_image) && $pix['qrcode_image'] !== '') $campos['qrcode_image'] = $pix['qrcode_image'];
                            if ($campos) {
                                Capsule::table('gofasgerencianetboleto')
                                    ->where('invoice_id', $invoice_id)
                                    ->where('api_mode', $api_mode)
                                    ->where('charge_id', $trans_id)
                                    ->update($campos);
                            }
                        } else {
                            Capsule::table('gofasgerencianetboleto')->insert(array(
                                'invoice_id' => $invoice_id,
                                'charge_id' => $trans_id,
                                'api_mode' => $api_mode,
                                'status' => (string)($chargeData['status'] ?? 'waiting'),
                                'total' => (int)($chargeData['total'] ?? 0),
                                'expire_at' => $expire_at,
                                'link' => $link,
                                'pdf' => (string)($billetData['pdf']['charge'] ?? ''),
                                'barcode' => $barcode,
                                'qrcode' => $pix['qrcode'],
                                'qrcode_image' => $pix['qrcode_image']
                            ));
                        }
                    } catch (\Throwable $e) {
                        logActivity('Efí: falha ao restaurar boleto no banco: '.json_encode(array(
                            'invoice_id' => $invoice_id, 'charge_id' => $trans_id,
                            'erro' => $e->getMessage()
                        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    }
                }

                if ($error) {
                    logActivity('Efí: consulta de boleto existente falhou: '.json_encode(array(
                        'invoice_id' => $invoice_id,
                        'charge_id' => $trans_id,
                        'possui_link_local' => !empty($boletoLocal->link),
                        'possui_barcode_local' => !empty($boletoLocal->barcode),
                        'possui_pix_local' => !empty($pix['qrcode']) || !empty($pix['qrcode_image']),
                        'erro' => $error,
                        'origem' => strtok((string)($_SERVER['REQUEST_URI'] ?? 'cron'), '?')
                    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
            }
            if (!$error && $link === '') {
                $error = 'A cobrança '.$trans_id.' já existe, mas seu link de pagamento está ausente.';
            }
            if ($debug_or_log) {
                $log_result['boleto_local'] = array(
                    'invoice_id' => $invoice_id, 'charge_id' => $trans_id,
                    'link_presente' => $link !== '', 'barcode_presente' => $barcode !== '',
                    'pix_presente' => !empty($pix['qrcode']) || !empty($pix['qrcode_image']),
                    'consultou_efi' => !$boletoLocal || empty($boletoLocal->link) || empty($boletoLocal->barcode)
                );
            }
        }

		/// The firt billet for the invoice
		elseif ( !$trans_id and !$error ) {

			if ( (float)$invoiceTotal >= (float)$minimunAmount) {
				// Criar transação
				//die('<pre>'.print_r(get_defined_vars(),1).'</pre>');
				//die('die jardel gofasgerencianetboleto_link <pre>'.print_r($body, 1).'</pre>'); //aqui mata antes de gerar o boleto // aqui o primeiro boleto é gerado // parei aqui 666
				$charge_id_ = ggnb_create_charge( $api, $body, $system_url, $debug_or_log); // body
				$charge_id = $charge_id_['result'];
				if($debug_or_log){
					$log_result['charge_id_']	= $charge_id_;
				}
				if($charge_id_['error']){
					$error	= $charge_id_['error'];
				}
				// Definir método de pagamanto e Gerar a Cobrança (retorna link do boleto etc)
				if ( is_int($charge_id) ) {
					$pay_charge_	= ggnb_pay_charge( $api, $charge_id, $body2, $system_url, $debug_or_log);
					$pay_charge		= $pay_charge_['result'];
					if($debug_or_log){
						$log_result['pay_charge']	= $pay_charge_;
					}
					if($pay_charge_['error']){
						$error	= $pay_charge_['error'];
					}
					if ( is_array($pay_charge) && !empty($pay_charge['data']['link']) ) {
						$link		= $pay_charge['data']['link'];
						$expire_at	= $pay_charge['data']['expire_at'];
						$barcode	= $pay_charge['data']['barcode'];
						// Cria transação no whmcs "Boleto gerado aguardando pagamento."
						$addtrans = ggnb_add_trans($user_id,$invoice_id,$charge_id,$whmcsAdmin, $api_mode, $system_url, $debug_or_log );
						if($addtrans['error']) {
							$error	= $addtrans['error'];
						}
						if($debug_or_log){
							$log_result['addtrans']	= $addtrans;
						}
						// Save billet on DB
						$ggnb_store_billet = ggnb_store_billet($pay_charge,$invoice_amount,$invoice_id,$debug_or_log,$api_mode);
						if($debug_or_log){
							$log_result['ggnb_store_billet']	= $ggnb_store_billet;
						}
						if ($ggnb_store_billet['error']) {
							$error = $ggnb_store_billet['error'];
						}
					}
					elseif( is_string($pay_charge) ) {
						$error = $pay_charge_['error'] ?? $error;
					}
				}
				else {
						$error = $charge_id_['error'] ?? $error;
						
				}
			}
			elseif ( (float)$invoiceTotal < (float)$minimunAmount ) {
				$error = '<b><span class="ggnberror">O valor total da fatura é R$'.number_format( $invoiceTotal,  2, ',', '.' ) .', mas o valor mínimo para pagamento via Boleto que é R$'. number_format( $minimunAmount,  2, ',', '.' ) .'.</span></b>';
				
			}
		} // end !$trans_id
		
		// Resultado impresso na área Visível na fatura/checkout
		if (!$error && empty($link)) {
			$error = 'A Efí não retornou o link do boleto.';
		}
		if( $error ) {
			// Envia ao administrador independentemente das opções do gateway.
			$invoiceDetails = array(
				'Status da fatura' => $GetInvoiceResults['status'] ?? '',
				'Valor (R$)' => $GetInvoiceResults['total'] ?? '',
				'Vencimento' => $GetInvoiceResults['duedate'] ?? '',
				'ID do cliente' => $user_id,
				'E-mail do cliente' => $params['clientdetails']['email'] ?? '',
				'Empresa do cadastro' => $params['clientdetails']['companyname'] ?? '',
				'Telefone do cadastro' => $params['clientdetails']['phonenumber'] ?? '',
				'Documento fiscal do cadastro' => $params['clientdetails']['tax_id'] ?? '',
				'CPF do cadastro' => $cpf_customfield_value ?? '',
				'CNPJ do cadastro' => $cnpj_customfield_value ?? '',
				'Nome enviado à Efí' => $body2['payment']['banking_billet']['customer']['name'] ?? '',
				'CPF enviado à Efí' => $body2['payment']['banking_billet']['customer']['cpf'] ?? '',
				'CNPJ enviado à Efí' => $body2['payment']['banking_billet']['customer']['juridical_person']['cnpj'] ?? '',
				'Razão social enviada à Efí' => $body2['payment']['banking_billet']['customer']['juridical_person']['corporate_name'] ?? '',
				'Telefone enviado à Efí' => $body2['payment']['banking_billet']['customer']['phone_number'] ?? '',
				'Transação no WHMCS' => $trans_id_ ?? '',
			);
			$operation = 'processar boleto';
			if (!empty($chargeExist_['error']) && $error === $chargeExist_['error']) {
				$operation = 'consultar boleto existente';
			} elseif (!empty($addtrans['error']) && $error === $addtrans['error']) {
				$operation = 'registrar boleto no WHMCS';
			} elseif (!empty($charge_id_['error']) && $error === $charge_id_['error']) {
				$operation = 'criar cobrança na Efí';
			} elseif (!empty($pay_charge_['error']) && $error === $pay_charge_['error']) {
				$operation = 'emitir boleto na Efí';
			}
			$invoiceDetails['Operação que falhou'] = $operation;
			$invoiceDetails['ID da cobrança existente'] = $trans_id ?: ($chargeExistID ?? '');
			$sendEmailonError = ggnb_send_error_email( $invoice_id, $user_id, $firstname, $lastname, $system_url, $whmcsAdmin, $emailonError, $error, $debug_or_log, $addtrans['api_error'] ?? $pay_charge_['api_error'] ?? $charge_id_['api_error'] ?? '', $invoiceDetails, $operation );
			if ($debug_or_log) {
				$log_result['sendEmailonError'] = $sendEmailonError;
			}
			
			
			// Debug
			include __DIR__.'/gofasgerencianetboleto/includes/debug.php';
			return $error . $css;
		}
		elseif ( !$error and $redirectToBillet and stripos($_SERVER['REQUEST_URI'], 'viewinvoice.php') ) {
			header_remove();
			header("Location: $link",TRUE,303);
			exit;
		}
		elseif( !$error and !$redirectToBillet and stripos($_SERVER['REQUEST_URI'], 'viewinvoice.php')) {
			
			//////////// Resultado ///////////
			$expire_at_v = strtotime($expire_at);
			$expire_at_br = date('d/m/Y',$expire_at_v);
			$result .= '<a target="_blank" title="Visualizar Boleto" id="ggnbviewbillet" href="'.$link.'">'.$payButton.'</a><br/><br/>';
						
			if ( $showBarCode ) {
				$result .= '<br><p id="ggnbclic">Clique para copiar a Linha Digitável do Boleto:</p>
				<p id="linDig" onfocus="select_all_and_copy(this)" onclick="select_all_and_copy(this)">'.$barcode.'</p>';
			}
			if ( ($discount_tax_value and $discount_tax_visible) || $showDueDate ) {
				$result .= '<div id="ggnbbilletinfo">';
			}
			if ( $discount_tax_visible and $discount_tax_visible_message) {
				$result .=  $discount_tax_visible_message;
			}
			if ( $showDueDate ) {
				$result .= '<p>Vencimento do Boleto: ' . $expire_at_br . '</p>';
			}
			if ( ($discount_tax_value and $discount_tax_visible) || $showDueDate ) {
				$result .= '</div>';
			}
			// Finalize
			if($debug){
				$define_version = '?v='.time();
			}
			elseif(!$debug) {
				$define_version = '';
			}
			$result .= '<script type="text/javascript" src="'.$whmcs_url.'modules/gateways/gofasgerencianetboleto/assets/js/copy.js'.$define_version.'" charset="UTF-8">
</script>';
			// Debug
			include __DIR__.'/gofasgerencianetboleto/includes/debug.php';
			return $result . $css;
		}
	} // End of if ( $generate_billet )
	else {
		$log_result['generate_billet_false_']	= 'Boleto não foi gerado ao gerar a fatura';
		$log_result['params']	= $params;
		if (true) {
			logModuleCall("gofasgerencianetboleto","generate_billet_false",array('invoice_id'=>$invoice_id,'user_id'=>$user_id),"", $log_result);
		}
	}
} // End of gofasgerencianetboleto_link