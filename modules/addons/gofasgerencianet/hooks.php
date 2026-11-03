<?php
/**
 * Módulo Gerencinet Dashboard para WHMCS
 * @author		Mauricio Gofas | gofas.net
 * @see			https://gofas.net/?p=7893
 * @copyright	2017 https://gofas.net
 * @license		https://gofas.net?p=9340
 * @support		https://gofas.net/?p=7856
 * @version		2.2.0
 */
use WHMCS\Database\Capsule;
require_once __DIR__ . '/../../gateways/gofasgerencianetboleto/sdk/efi_rest.php';
use Gerencianet\Exception\GerencianetException;
use Gerencianet\Gerencianet;

// TEMPORARIO: remover apos diagnosticar a fatura 17219.
if (
    !empty($_SESSION['adminid']) &&
    ($_GET['ggnb_debug_17219'] ?? '') === '1' &&
    strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/billing/invoice/17219') !== false
) {
    $debug = ['fatura' => 17219, 'hook_carregado' => true];
    try {
        $invoice = localAPI('GetInvoice', ['invoiceid' => 17219], false);
        $debug['whmcs'] = [
            'result' => $invoice['result'] ?? null,
            'status' => $invoice['status'] ?? null,
            'paymentmethod' => $invoice['paymentmethod'] ?? null,
            'total' => $invoice['total'] ?? null,
        ];
        $saved = Capsule::table('gofasgerencianetboleto')->where('invoice_id', 17219)->first();
        $debug['registro_local'] = $saved ? ['charge_id' => $saved->charge_id, 'tem_link' => !empty($saved->link)] : 'ausente';
        $settings = [];
        foreach (Capsule::table('tblpaymentgateways')->where('gateway', 'gofasgerencianetboleto')->get(['setting', 'value']) as $setting) {
            $settings[$setting->setting] = $setting->value;
        }
        $mode = !empty($settings['sandbox']) ? 'sandbox' : 'live';
        $debug['modo_configurado'] = $mode;
        $transactions = $invoice['transactions']['transaction'] ?? [];
        if (isset($transactions['transid'])) $transactions = [$transactions];
        $debug['quantidade_transacoes'] = count($transactions);
        $chargeId = 0;
        foreach (array_reverse($transactions) as $transaction) {
            $ref = $transaction['transid'] ?? '';
            if (preg_match('/^ggnb_(live|sandbox)_(?:waiting|unpaid|_notification)-(\d+)(?:\[(?:waiting|unpaid)\])?$/', $ref, $match)) {
                $mode = $match[1];
                $chargeId = (int)$match[2];
                break;
            }
        }
        $debug['modo_efi'] = $mode;
        $debug['charge_id_localizado'] = $chargeId;
        if ($chargeId) {
            $api = new Gerencianet([
                'client_id' => $settings[$mode === 'live' ? 'clientid' : 'clientidsandbox'] ?? '',
                'client_secret' => $settings[$mode === 'live' ? 'clientsecret' : 'clientsecretsandbox'] ?? '',
                'sandbox' => $mode === 'sandbox',
            ]);
            $response = $api->detailCharge(['id' => $chargeId], []);
            $data = $response['data'] ?? [];
            $billet = $data['payment']['banking_billet'] ?? [];
            $debug['resposta_efi'] = [
                'charge_id' => $data['charge_id'] ?? null,
                'custom_id' => $data['custom_id'] ?? null,
                'status' => $data['status'] ?? null,
                'total_centavos' => $data['total'] ?? null,
                'data_keys' => array_keys($data),
                'payment_keys' => array_keys((array)($data['payment'] ?? [])),
                'banking_billet_keys' => array_keys((array)$billet),
                'link_presente' => !empty($billet['link']),
                'barcode_presente' => !empty($billet['barcode']),
                'vencimento' => $billet['expire_at'] ?? null,
                'pdf_tipo' => gettype($billet['pdf'] ?? null),
                'pdf_keys' => is_array($billet['pdf'] ?? null) ? array_keys($billet['pdf']) : [],
            ];
        }
    } catch (GerencianetException $e) {
        $debug['erro_efi_original'] = ['code' => $e->code, 'error' => $e->error, 'errorDescription' => $e->errorDescription];
    } catch (\Throwable $e) {
        $debug['erro_php'] = $e->getMessage();
    }
    header('Content-Type: text/html; charset=utf-8');
    die('<h3>DEBUG TEMPORARIO - FATURA 17219 (nenhum envio)</h3><pre>'.htmlspecialchars(print_r($debug, true), ENT_QUOTES, 'UTF-8').'</pre>');
}

// Cancel Billet on cancel invoice
add_hook( 'InvoiceCancelled', 1, function($vars) {
	//logModuleCall('gofasgerencianet','start',$vars );
	// Get Module Params
	foreach( Capsule::table('tblpaymentgateways') -> where('gateway', '=', 'gofasgerencianetboleto') -> get( array( 'setting', 'value') ) as $settings ) {
		$setting_key				= $settings->setting;
		$params["$setting_key"]		= $settings->value;
	}
	if ($params['cancelbilletoncancelinvoice']) {
			// Parâmetros do Módulo
			$sandbox	= $params['sandbox'];
			$debug		= $params['debug'];
			if ( $sandbox ) {
				$sandbox				= true;
				$clientid				= $params['clientidsandbox'];
				$clientsecret			= $params['clientsecretsandbox'];
				$api_mode				= 'sandbox';
			}
			 elseif (!$sandbox) {
				$sandbox				= false;
				$clientid				= $params['clientid'];
				$clientsecret			= $params['clientsecret'];
				$api_mode				= 'live';
			}
			$options	= array("client_id"=>$clientid,"client_secret"=>$clientsecret,"sandbox"=>$sandbox,"debug"=>$debug);
			$api		= new Gerencianet($options);
	}
	$whmcs_admin	= $params['admin'];
	//logModuleCall('gofasgerencianet', 'params', $params);

    $invoice_id	= (int)$vars['invoiceid'];
	$invoice	= localAPI('GetInvoice',array( 'invoiceid' => $invoice_id, ), $whmcs_admin);

	//logModuleCall('gofasgerencianet', 'geninvoices', $invoice);

	// Parâmetros das transações associadas à Fatura
	$trans_idendA				= $invoice['transactions'];
	if($trans_idendA) {
		$trans_idend			= $trans_idendA['transaction'];
	}
	if ($trans_idend) {
		$trans_idp				= end( $trans_idend );
		$trans_id_				= $trans_idp['transid'];

		// Verifica se a transação pertence ao módulo
		if ( (strpos( $trans_id_, 'ggnb') !== false) and (strpos( $trans_id_, 'unpaid') !== false or strpos( $trans_id_, 'waiting') !== false ) and strpos( $trans_id_, $api_mode) ) {
			$trans_id					= (int)preg_replace('/[^0-9]/', '', $trans_id_ ); // ggnb_waiting_213630
		}
		else {
			$trans_id				= false;
		}
	}
	else {
		$trans_id				= false;
	}
	//logModuleCall('gofasgerencianet', 'trans_idendA', $trans_idendA, $trans_idp);

	if ($trans_id) {

		try {
			$id = array('id' => (int)$trans_id);
			$cancelCharge = $api->cancelCharge( $id, array() );
			logModuleCall('gofasgerencianet','cancel_transaction',array('Sucesso:'=>$cancelCharge), '' );
		}
		catch (GerencianetException $e) {
			$error	= 'Erro ao cancelar Boleto: ' . $e->errorDescription;
			logModuleCall('gofasgerencianet','cancel_transaction',array('Error:'=>$error), '' );
		}
		catch (Exception $e) {
			$error	= 'Erro ao cancelar Boleto: ' . $e->getMessage();
			logModuleCall('gofasgerencianet','cancel_transaction',array('Error:'=>$error), '' );
		}

		$UpdateTransaction = localAPI('UpdateTransaction', array('transactionid' => $trans_idp['id'], 'transid' => 'ggnb_'.$api_mode.'_canceled-'.$trans_id, ), $whmcs_admin);
	}
	logModuleCall('gofasgerencianet', 'cancel_billet', $UpdateTransaction, $cancelCharge, '');
} // End function($vars)
);


add_hook('EmailPreSend', 1, function($vars) {
	logModuleCall('gofasgerencianet', 'EmailPreSend', 'start', $vars, '');
    // Get Module Options
	foreach( Capsule::table('tblpaymentgateways') -> where('gateway', '=', 'gofasgerencianetboleto') -> get( array( 'setting', 'value') ) as $settings ) {
		$setting_key				= $settings->setting;
		$params["$setting_key"]		= $settings->value;
	}
	//$billetonemail					= $params['billetonemail'];

	// Invoice Created | Invoice Payment Reminder | First Invoice Overdue Notice |  Second Invoice Overdue Notice |  Third Invoice Overdue Notice
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
		$vars['messagename'] === 'Negativacao 3'
	) {
		$ggnb_merge_fields	= array();
		$invoice			= localAPI( 'GetInvoice', array('invoiceid' => $vars['relid']), false);

		logModuleCall('gofasgerencianet', 'EmailPreSend', 'invoice', $invoice, '');




		if( (float)$invoice['total'] > (float)'0.00' and $invoice['paymentmethod'] === 'gofasgerencianetboleto' ) {
			// Recupera o mesmo boleto na Efí se a gravação local falhou.
			$billet_saved = Capsule::table('gofasgerencianetboleto')->where('invoice_id', $vars['relid'])->first();
			if (!$billet_saved && $invoice['status'] === 'Unpaid') {
				logModuleCall('gofasgerencianet', 'EmailPreSendRecovery', 'inicio', ['invoice_id' => $vars['relid']], '');
				$api_mode = !empty($params['sandbox']) ? 'sandbox' : 'live';
				$transactions = $invoice['transactions']['transaction'] ?? [];
				if (isset($transactions['transid'])) $transactions = [$transactions];
				$charge_id = 0;
				foreach (array_reverse($transactions) as $transaction) {
					$reference = $transaction['transid'] ?? '';
					if (preg_match('/^ggnb_(live|sandbox)_(?:waiting|unpaid|_notification)-(\d+)(?:\[(?:waiting|unpaid)\])?$/', $reference, $match)) {
						$api_mode = $match[1];
						$charge_id = (int)$match[2];
						break;
					}
				}
				logModuleCall('gofasgerencianet', 'EmailPreSendRecovery', 'transacao', ['charge_id' => $charge_id, 'api_mode' => $api_mode, 'quantidade' => count($transactions)], '');
				if ($charge_id) {
					try {
						$api = new Gerencianet([
							'client_id' => $params[$api_mode === 'live' ? 'clientid' : 'clientidsandbox'],
							'client_secret' => $params[$api_mode === 'live' ? 'clientsecret' : 'clientsecretsandbox'],
							'sandbox' => $api_mode === 'sandbox'
						]);
						$charge = $api->detailCharge(['id' => $charge_id], []);
						$info = $charge['data'] ?? [];
						$billet = $info['payment']['banking_billet'] ?? [];
						logModuleCall('gofasgerencianet', 'EmailPreSendRecovery', 'resposta_efi', ['id' => $info['charge_id'] ?? null, 'status' => $info['status'] ?? null, 'campos_boleto' => array_keys($billet)], '');
						if ((int)($info['charge_id'] ?? 0) !== $charge_id ||
							(!empty($info['custom_id']) && (string)$info['custom_id'] !== (string)$vars['relid']) ||
							!in_array($info['status'] ?? '', ['waiting', 'unpaid'], true) ||
							empty($billet['link']) || empty($billet['barcode']) || empty($billet['expire_at']) || empty($billet['pdf']['charge'])) {
							throw new \RuntimeException('A cobrança existente não retornou dados válidos para recuperar o boleto.');
						}
						$data = [
							'invoice_id' => $vars['relid'],
							'charge_id' => $charge_id,
							'link' => $billet['link'],
							'pdf' => $billet['pdf']['charge'],
							'expire_at' => $billet['expire_at'],
							'total' => $info['total'],
							'barcode' => $billet['barcode'],
							'status' => $info['status'],
							'qrcode' => $info['payment']['pix']['qrcode'] ?? '',
							'qrcode_image' => $info['payment']['pix']['qrcode_image'] ?? '',
							'api_mode' => $api_mode,
						];
						Capsule::connection()->transaction(function () use ($vars, $charge_id, $data) {
							Capsule::table('tblinvoices')->where('id', $vars['relid'])->lockForUpdate()->first();
							if (!Capsule::table('gofasgerencianetboleto')->where('invoice_id', $vars['relid'])->exists()) {
								if (Capsule::table('gofasgerencianetboleto')->where('charge_id', $charge_id)->exists()) {
									throw new \RuntimeException('A cobrança já está associada a outra fatura.');
								}
								Capsule::table('gofasgerencianetboleto')->insert($data);
							}
						});
						logModuleCall('gofasgerencianet', 'EmailPreSendRecovery', 'persistencia', ['charge_id' => $charge_id], '');
					} catch (GerencianetException $e) {
						$reason = json_encode(['code' => $e->code, 'error' => $e->error, 'errorDescription' => $e->errorDescription], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
					} catch (\Throwable $e) {
						$reason = $e->getMessage();
					}
					if (!empty($reason)) {
						logModuleCall('gofasgerencianet', 'EmailPreSendRecovery', 'erro', $reason, '');
						$email = [
							'messagename' => 'Mention Notification',
							'customsubject' => 'Efí - falha ao recuperar boleto da fatura #'.$vars['relid'],
							'custommessage' => 'Fatura #'.$vars['relid'].' / Cobrança Efí #'.$charge_id.'<pre>'.htmlspecialchars($reason, ENT_QUOTES, 'UTF-8').'</pre>',
							'type' => 'system',
						];
						if (!empty($params['emailonerror'])) $email['deptid'] = $params['emailonerror'];
						$sendResult = localAPI('sendadminemail', $email, $params['admin'] ?: null);
						logModuleCall('gofasgerencianet', 'EmailPreSendRecovery', 'email_admin', ['result' => $sendResult['result'] ?? null, 'message' => $sendResult['message'] ?? null], '');
					}
				}
				$billet_saved = Capsule::table('gofasgerencianetboleto')->where('invoice_id', $vars['relid'])->first();
			}
			if (!$billet_saved) {
				logModuleCall('gofasgerencianet', 'EmailPreSendRecovery', 'sem_boleto', ['invoice_id' => $vars['relid']], '');
				return;
			}
			$billet_saved = (array)$billet_saved;

			logModuleCall('gofasgerencianet', 'EmailPreSend', 'billet_saved', $billet_saved, '');

			// Merge Fields
			$ggnb_merge_fields['ggnb_link']			= $billet_saved['link'];
			$ggnb_merge_fields['ggnb_pdf']			= $billet_saved['pdf'];
			$ggnb_merge_fields['ggnb_barcode']		= $billet_saved['barcode'];
			$ggnb_merge_fields['ggnb_expire_at']	= date('d/m/Y', strtotime($billet_saved['expire_at']));
			$ggnb_merge_fields['ggnb_total']		= number_format($billet_saved['total']/100,  2, ',', '.' );
			$ggnb_merge_fields['ggnb_charge_id']	= $billet_saved['charge_id'];
			$ggnb_merge_fields['ggnb_api_mode']		= $billet_saved['api_mode'];
			$ggnb_merge_fields['ggnb_qrcode']		= $billet_saved['qrcode'];

			
			

			if($params['linkbilletonemail']) {
				$ggnb_merge_fields['invoice_link']		= $billet_saved['link'];
			}

			$ggnb_merge_fields['ggnb_billet_info']	.= '<br>------------------------------------------------------';
			$ggnb_merge_fields['ggnb_billet_info']	.= '<br>Linha digitável do Boleto:<br><b>'.$billet_saved['barcode'];
			$ggnb_merge_fields['ggnb_billet_info']	.= '</b><br>Vencimento do Boleto: '.date('d/m/Y', strtotime($billet_saved['expire_at'])) ;
			$ggnb_merge_fields['ggnb_billet_info']	.= '<br>Total do Boleto:  R$ '.number_format(($billet_saved['total']/100),  2, ',', '.' ) ;
			$ggnb_merge_fields['ggnb_billet_info']	.= '<br>Código do Boleto: '.$billet_saved['charge_id'];
			$ggnb_merge_fields['ggnb_billet_info']	.= '<br><b><a href="'.$billet_saved['link'].'">Visualizar Boleto em HTML</a></b>';
			$ggnb_merge_fields['ggnb_billet_info']	.= '<br><b><a href="'.$billet_saved['pdf'].'">Visualizar Boleto em PDF</a></b>';
			$ggnb_merge_fields['ggnb_billet_info']	.= '<br>------------------------------------------------------';
			$ggnb_merge_fields['ggnb_debug'] .= "Debug:\n".(string)json_encode($vars).json_encode($invoice);

			logModuleCall('gofasgerencianet', 'EmailPreSend', 'ggnb_merge_fields', $ggnb_merge_fields, '');

			return $ggnb_merge_fields;
		}
		else {
			return;
		}

    }
	else { // Not
		return;
	}
});

// https://developers.whmcs.com/hooks-reference/everything-else/#emailpresend
//Output additional merge fields in the list when editing an email template
add_hook('EmailTplMergeFields', 1, function($vars) {
    $ggnb_merge_fields = array();
	$ggnb_merge_fields['ggnb_billet_info']	= 'GGNB: Informações do boleto';
    $ggnb_merge_fields['ggnb_link']			= 'GGNB: Link para o boleto';
	$ggnb_merge_fields['ggnb_pdf']			= 'GGNB: Link para o boleto em PDF';
	$ggnb_merge_fields['ggnb_barcode']		= 'GGNB: Linha digitável do boleto';
	$ggnb_merge_fields['ggnb_expire_at']	= 'GGNB: Vencimento do boleto';
	$ggnb_merge_fields['ggnb_total']		= 'GGNB: Total do boleto';
	$ggnb_merge_fields['ggnb_charge_id']	= 'GGNB: ID da transação';
	$ggnb_merge_fields['ggnb_api_mode']		= 'GGNB: API mode (sandbox ou live)';
	$ggnb_merge_fields['ggnb_debug']		= 'GGNB: Debug nos emails';
	$ggnb_merge_fields['ggnb_qrcode']		= 'GGNB: Pix Copia e Cola';
    return $ggnb_merge_fields;
});