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
		
		if ( $trans_id and !$error) {

			$chargeExist_			= ggnb_detail_charge( $api, $trans_id, $debug_or_log );
			if (!is_array($chargeExist_) || empty($chargeExist_['result']['data']['charge_id'])) {
				// A cobrança antiga pode ter sido removida na Efí; nesse caso, permita gerar outra.
				$trans_id = false;
				$chargeExist = array('data' => array('charge_id' => 0, 'total' => 0, 'status' => 'canceled', 'payment' => array('banking_billet' => array('expire_at' => '1970-01-01'))));
			} else {
				$chargeExist = $chargeExist_['result'];
			}
			$chargeExistID			= (int)$chargeExist['data']['charge_id']; // ID da transação gerada pela fatura
			$chargeExistTotal		= (int)$chargeExist['data']['total'];
			$chargeExistStatus		= (string)$chargeExist['data']['status'];
			$chargeExistDuedate		= $chargeExist['data']['payment']['banking_billet']['expire_at'];
			$chargeExistDiscount	= $chargeExist['data']['payment']['discount'];
			if ($chargeExistDiscount and $chargeExistDiscount > 0) {
				$chargeExistTotal	= $chargeExistTotal - $chargeExistDiscount;
			}
			
			if ($debug_or_log ) {
				$log_result['chargeExist'] = $chargeExist_['log_result'];
				$log_result['chargeExistTotal__']	= '$chargeExistTotal: '.$chargeExistTotal.'<br>$invoice_amount : '. $invoice_amount ;
				$log_result['chargeExistTotal_']	.= '<br>$discount_tax_valueRS: '.$discount_tax_valueRS.'<br>';
				if ($chargeExistTotal === $invoice_amount ) {
					$log_result['chargeExistTotal']	.= 'valor da transação existente é igual ao valor da fatura';
				}
				if ($chargeExistTotal !== $invoice_amount ) {
					$log_result['chargeExistTotal']	.= '<br>valor da transação existente é diferente do valor da fatura';
				}
			}
			
			if ($chargeExist_['error']) {
				$error = $chargeExist_['error'];
			}
			///
			if (!$error and $chargeExistID === $trans_id and $chargeExistStatus !== 'canceled' and $chargeExistDuedate >= date('Y-m-d') and $chargeExistTotal === $invoice_amount) {
				$link		= $chargeExist['data']['payment']['banking_billet']['link'];
				$expire_at	= $chargeExistDuedate;
				$barcode	= $chargeExist['data']['payment']['banking_billet']['barcode'];
			}
			///
			elseif (!$error and  ($chargeExistID === $trans_id and $chargeExistStatus !== 'canceled' and $chargeExistDuedate < date('Y-m-d')) and !$configurations and !$cancelBillet ) {
				// edita transação gerada anteriormente
				$updateBillet = ggnb_update_billet( $api, $chargeExistID, $billet_duedate, $system_url, $debug_or_log );
				// segunda via do boleto com multa e juros
				if ( $updateBillet['result'] === 'sucess' ) {	
					$link		= $chargeExist['data']['payment']['banking_billet']['link'];
					$expire_at	= $billet_duedate;
					$barcode	= $chargeExist['data']['payment']['banking_billet']['barcode'];
				}
				else {
					$error = $updateBillet['error'];
				}
				if($debug_or_log){
					$log_result['updateBillet']	= $updateBillet;
				}
			}
			///
			elseif (!$error and ($chargeExistID === $trans_id and $chargeExistStatus === 'canceled' and $chargeExistDuedate >= date('Y-m-d')) ) {
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
					
					if ( is_array($pay_charge) ) {
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
						$error = $pay_charge['error'];
					}
				}
				else {
					$error = $charge_id['error'];
				}
			}
			
			///
			elseif (!$error and $chargeExistID === $trans_id and $chargeExistDuedate >= date('Y-m-d') and $chargeExistTotal !== $invoice_amount ) {
				if($cancelBillet) {
					$cancelCharge = ggnb_cancel_charge( $api, $chargeExistID, $debug_or_log );
					 if($cancelCharge['error']){
						 $error = $cancelCharge['error'];
					 }
					 if($debug_or_log){
						 $log_result['cancelCharge']	= $cancelCharge;
					 }
				}
				
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
					if ( is_array($pay_charge) ) {
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
						$error = $pay_charge['error'];
					}
				}
				else {
						$error = $charge_id['error'];

				}
			}
			
			///
			elseif (!$error and ($chargeExistID === $trans_id and $chargeExistDuedate < date('Y-m-d') ) and ($configurations or $cancelBillet) ) {
				// cancela transação gerada anteriormente
				if ( $chargeExistStatus === 'new' || $chargeExistStatus === 'waiting' || $chargeExistStatus === 'unpaid' ) {
					$cancelCharge = ggnb_cancel_charge( $api, $chargeExistID, $debug_or_log );
					 if($cancelCharge['error']){
						 $error = $cancelCharge['error'];
					 }
					 if($debug_or_log){
						 $log_result['cancelCharge']	= $cancelCharge;
					 }
					// segunda via do boleto com multa e juros
					if ( $cancelCharge['result'] === 'sucess' ) {
						// Criar transação
						$charge_id_ = ggnb_create_charge( $api, $body, $system_url, $debug_or_log); // body
						$charge_id = $charge_id_['result'];
						if($debug_or_log){
							$log_result['charge_id_']	= $charge_id_;
						}
						if($charge_id_['error']) {
							$error = $charge_id_['error'];
						}
					}
					else {
						$error = $cancelCharge['error'];
					}
				}
				elseif ($chargeExistStatus === 'canceled') { // ignora transação cancelada
					$charge_id_ = ggnb_create_charge( $api, $body, $system_url, $debug); // body
					$charge_id = $charge_id_['result'];
					if($debug_or_log){
						$log_result['charge_id_']	= $charge_id_;
					}
					if($charge_id_['error']){
						$error	= $charge_id_['error'];
					}
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
					if ( is_array($pay_charge) ) {
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
					} elseif( is_string($pay_charge) ) {
						$error = $pay_charge['error'];
					}
				} else {
					$error = $charge_id['error'];
					
				}
			}			
		} // End of if ( $trans_id and !$error)
		
		/// The firt billet for the invoice
		elseif ( !$trans_id ) {

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
					if ( is_array($pay_charge) ) {
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
						$error = $pay_charge['error'];
					}
				}
				else {
						$error = $charge_id['error'];
						
				}
			}
			elseif ( (float)$invoiceTotal < (float)$minimunAmount ) {
				$error = '<b><span class="ggnberror">O valor total da fatura é R$'.number_format( $invoiceTotal,  2, ',', '.' ) .', mas o valor mínimo para pagamento via Boleto que é R$'. number_format( $minimunAmount,  2, ',', '.' ) .'.</span></b>';
				
			}
		} // end !$trans_id
		
		// Resultado impresso na área Visível na fatura/checkout
		if( $error ) {
			// Email enviado ao admin em caso de erro
			if ( $emailonError ) {
				$sendEmailonError = ggnb_send_error_email( $invoice_id, $user_id, $firstname, $lastname, $system_url, $whmcsAdmin, $emailonError, $error, $debug);
				if ($debug_or_log){
					$log_result['sendEmailonError']	= $sendEmailonError;
 				}
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