<?php

if(!defined('WHMCS')) { die('Esse arquivo não pode ser acessado diretamente'); }
use WHMCS\Database\Capsule;



#REQUIRE TARIFAS OBRIGATÓRIO PARA NÃO DAR ERRO NO CRON
if(empty($_SERVER['DOCUMENT_ROOT']))
{
    $_SERVER['DOCUMENT_ROOT'] = '/home/thxcom/public_html';
}
require_once($_SERVER['DOCUMENT_ROOT'].'/thx/tarifasTHX.php');

/**
 *
 * Verify Instalation
 * @return Array
 *
 */
 
if( !function_exists('ggnb_verifyInstall') ) {
function ggnb_verifyInstall() {
	if ( !Capsule::schema()->hasTable('gofasgerencianetboleto') ) {
    	try {
			Capsule::schema()->create('gofasgerencianetboleto', function($table) {
				// incremented id
        		$table->increments('id');
       			// whmcs info
				$table->string('invoice_id');
				$table->string('charge_id');
				$table->string('link');
				$table->string('pdf');
				$table->string('expire_at');
				$table->string('total');
				$table->string('barcode');
				$table->string('qrcode');
				$table->string('status');
				$table->string('api_mode');
    		});
		}
		catch (\Exception $e) {
    		$error = "Não foi possível criar a tabela do módulo no banco de dados: {$e->getMessage()}";
		}
	}
	if(!$error) {
		return array('sucess'=>1);
	}
	elseif($error) {
		return array('error'=>$error);
	}
}}

/**
 *
 * Consultar informações da transação
 * @ggnb_detail_charge
 *
 */
if (!function_exists('ggnb_detail_charge')) {
function ggnb_detail_charge( $api, $trans_id, $debug_or_log){
	// Verifica se a fatura atual já tem um boleto associado
	try {
		$chargeExist = $api->detailCharge( array('id' => (int)$trans_id ),array());
		$chargeExistStatus = $chargeExist['data']['status'];
		
        if ($debug_or_log) {
			$log_result = array();
            $log_result['chargeExist___']	= '<p class="ok">Boletos gerados por esta fatura - API Gerencianet.</p>';
			$log_result['chargeExist__']	= 'Essa fatura já gerou um boleto.<br/>';
			$log_result['chargeExist_']	= $chargeExist;
			$log_result['chargeExist']	= 'status: '.$chargeExistStatus;
        }
    } catch (GerencianetException $e) {
		if ($debug_or_log) {
            $log_result['chargeExist_']	= '<p class="ok">Resultado da busca por boletos gerados por esta fatura - API Gerencianet.</p>';
            if ($e->code == '3500034') {
				    $log_result['chargeExist']	= '<p>Essa fatura ainda não gerou nenhum boleto.</p>';
				} else {
				    $log_result['chargeExist']	= '<p>Nenhum boleto gerado por essa fatura.</p>';
				}
				$error = $e->error. ' - '.$e->errorDescription;
			}
		} catch (Exception $e) {
			if ($debug_or_log) {
				if ($e->code == '3500034') {
				    $log_result['chargeExist_']	= '<p class="ok">Essa fatura ainda não gerou nenhum boleto.</p>';
					$log_result['chargeExist']	= 'Erro '.$e->code.': '.$e->getMessage();
					
				} else {
				    $log_result['chargeExist_']	= '<p class="erro">Erro '.$e->code.' - API Gerencianet</p>';
					$log_result['chargeExist']	= 'Erro: '.$e->getMessage();
	
				}
				$error = $e->getMessage();
			}
		}
		if ($chargeExist) {
			return array('result'=>$chargeExist, 'log_result' => $log_result,);
		} else {
			return $error;
			return array('error'=>$error, 'log_result' => $log_result,);
		}
		
}
}
/**
 *
 * Cancelar transação
 * ggnb_cancel_charge
 *
 */
if (!function_exists('ggnb_cancel_charge')) {
function ggnb_cancel_charge( $api, $CHARGEEXISTID, $debug_or_log ) {
	try {
				$ID = array('id' => (int)$CHARGEEXISTID);
				
                $cancelCharge = $api->cancelCharge( $ID, array() );
				
                if ($debug_or_log) {
					$log_result = array();
                    $log_result['cancelCharge_']	= '<p class="ok">Transação anterior cancelada para gerar 2ª via do Boleto com multa e juros - API Gerencianet</p>';
                    $log_result['cancelCharge']		= $cancelCharge;
                }
            }
			catch (GerencianetException $e) {
				$error	= '<p class="erro">Erro ao cancelar Boleto anterior: ' . $e->errorDescription . '</p>';
				
                if ($debug_or_log) {
					$log_result = array();
                    $log_result['cancelCharge_']	= '<p class="erro">Erro ao cancelar transação - API Gerencianet</p>';
                    $log_result['cancelCharge']	= $e->errorDescription;
                }
            } catch (Exception $e) {
				$error	= '<p class="erro">Erro ao cancelar Boleto anterior: ' . $e->getMessage() . '</p>';
                if ($debug_or_log) {
					$log_result = array();
                    $log_result['cancelCharge_']	= '<p class="erro">Erro 2 ao cancelar transação - API Gerencianet</p>';
                    $log_result['cancelCharge']	= $e->getMessage();
                }
            }
	if ( !$error ) {
		return array('result'=>(string)'sucess', 'log_result'=> $log_result);
	} elseif ($error) {
		return array('error'=>$error, 'log_result'=> $log_result);
		//return $error;
	}
}
}
/**
 *
 * Atualizar vencimento do Boleto
 * ggnb_update_billet
 *
 */
if (!function_exists('ggnb_update_billet')) {
function ggnb_update_billet( $api, $CHARGEEXISTID, $BILLETDUEDATE, $system_url, $debug_or_log ) {
	try {
		$updateBillet = $api->updateBillet(array('id' => (int)$CHARGEEXISTID), array('expire_at' => $BILLETDUEDATE));

                if ($debug_or_log) {
					$log_result = array();
                    $log_result['updateBillet_']	= '<p class="ok">Data de vencimento do boleto atualizada - API Gerencianet.</p>';
					$log_result['updateBillet_']	= $updateBillet;
				}
			} catch (GerencianetException $e) {
				$error = '<b>Não foi possível atualizar a data de venvimento desse Boleto, por favor <a href="'.$system_url.'/submitticket.php" target="_blank">entre em contato</a> informando o ID da fatura e o código do erro.<br/>Erro: '.$e->code.' - '.$e->errorDescription.'</b>';
				sendErrorsToAdmin($error, 'Erro ao atualizar o vencimento do Boleto no sistema. Linha: '.__LINE__);
				if ($debug_or_log) {
					$log_result['updateBillet_']	=  '<p class="erro">Erro ao atualizar o vencimento do Boleto - API Gerencianet.</p>';
					$log_result['updateBillet']	= $e->errorDescription;
				}
			} catch (Exception $e) {
				$error = '<b>Não foi possível atualizar a data de venvimento desse Boleto, por favor <a href="'.$system_url.'/submitticket.php" target="_blank">entre em contato</a> informando o ID da fatura e erro:<br/>'.$e->getMessage().'</b>';
				sendErrorsToAdmin($error, 'Erro ao atualizar o vencimento do Boleto no sistema. Linha: '.__LINE__);
				if ($debug_or_log) {
					$log_result['updateBillet_']	= '<p class="erro">Erro ao atualizar o vencimento do Boleto - API Gerencianet.</p>';
					$log_result['updateBillet']	= $e->getMessage();
					
				}
			}
	if ( $updateBillet ) {
		//return 'sucess';
		return array('result'=>(string)'sucess', 'log_result'=> $log_result);
	} else {
		//return $error;
		return array('error'=>$error, 'log_result'=> $log_result);
	}
}
}
/**
 *
 * Criar transação
 * @ggnb_create_charge
 *
 */
if (!function_exists('ggnb_create_charge')) {
function ggnb_create_charge( $api, $BODY, $system_url, $debug_or_log) {
	// Gerar transação
				try {
					//die('die jardel ggnb_create_charge<pre>'.print_r($BODY, 1).'</pre>'); //aqui mata antes de gerar o boleto
					$charge = $api->createCharge(array(), $BODY);
					$charge_id = $charge['data']['charge_id'];
					//$chargeCID = $charge['data']['custom_id'];
					
					if ($debug_or_log) {
						$log_result = array();
						$log_result['ggnb_create_charge__']	='<p class="ok">Nova transação gerada com sucesso - API Gerencianet.</p>'; 
						$log_result['ggnb_create_charge_']	= $charge;
						$log_result['ggnb_create_charge']	= 'charge id: ' .$charge_id;
					}
				// Erros
				} catch ( GerencianetException $e) {
					$error = '<b>1) Não foi possível gerar o boleto, por favor <a href="'.$system_url.'/submitticket.php" target="_blank">entre em contato</a> informando o ID da fatura e o código do erro.<br/>Erro: '.$e->code.' - '.
					$e->errorDescription.'</b>';

					sendErrorsToAdmin($error, 'Falha ao gerar a transação no sistema. Linha: '.__LINE__);
					if ($debug_or_log) {
						$log_result = array();
						$log_result['ggnb_create_charge_']	= '<p class="erro">Falha ao gerar a transação - API Gerencianet.</p>';
						$log_result['ggnb_create_charge']	= $e->errorDescription;
						
					}
 				} catch (Exception $e) {
					$error = '<b>2) Não foi possível gerar o boleto, por favor <a href="'.$system_url.'/submitticket.php" target="_blank">entre em contato</a> informando o ID da fatura e o erro.<br/>'.$e->getMessage().'</b>';
					sendErrorsToAdmin($error, 'Falha ao gerar a transação no sistema. Linha: '.__LINE__);
					if ($debug_or_log) {
						$log_result = array();
						$log_result['ggnb_create_charge_']	= '<p class="erro">Falha ao gerar a transação - API Gerencianet.</p>';
						$log_result['ggnb_create_charge']	= $e->getMessage();
						
					}
				} 
				
			if ($charge_id) {
				//return $charge_id;
				return array('result'=>(int)$charge_id, 'log_result'=> $log_result);
			}
			else {
				//return $error;
				return array('error'=>$error, 'log_result'=> $log_result);
			}
 }
}
/**
 *
 * Criar Cobrança
 * @ggnb_pay_charge
 *
 */
if (!function_exists('ggnb_pay_charge')) {
function ggnb_pay_charge( $api, $CHARGEID, $BODY2, $system_url, $debug_or_log) {
	//die('DIE JARDEl ggnb_pay_charge( <pre>'.print_r($CHARGEID,1).'</pre>'); //aqui o boleto já foi gerado :()
	// Erros comuns
	$CPF_ERROR = '<b><span class="ggnberror">Erro ao gerar o Boleto:<br/>CPF incorreto.<br/><a href="'.$system_url.'/clientarea.php?action=details" target="_blank" title="Minha Conta">Atualize aqui seu CPF</a>.</span></b>';

	$CNPJ_ERROR = '<b><span class="ggnberror">Erro ao gerar o Boleto:<br/>CNPJ incorreto.<br/><a href="'.$system_url.'/clientarea.php?action=details" target="_blank" title="Minha Conta">Atualize aqui seu CNPJ</a>.</span></b>';

	$PHONENUMBER_ERROR = '<b><span class="ggnberror">Erro ao gerar o Boleto:<br/>Telefone incorreto.<br/><a href="'.$system_url.'/clientarea.php?action=details" target="_blank" title="Minha Conta">Atualize aqui seu Telefone</a>.</span></b>';

	$NAME_ERROR = '<b><span class="ggnberror">Erro ao gerar o Boleto:<br/>Nome e/ou Sobrenome incorretos.<br/><a href="'.$system_url.'/clientarea.php?action=details" target="_blank" title="Minha Conta">Atualize aqui seu Nome/Sobrenome</a>.</span></b>';

	$PROFILE_ERROR = '<b><span class="ggnberror">Erro ao gerar o Boleto devido a inconsistências no seu cadastro.<br/><a href="'.$system_url.'clientarea.php?action=details" target="_blank" title="Minha Conta">Atualize aqui seu cadastro</a>.</span></b>';
	 
	 // executa pagamento
	 try {
		
		$params2 = array('id' => (int)$CHARGEID);
		$chargePay = $api->payCharge($params2, $BODY2);
		
		if ($debug_or_log) {
			$log_result = array();
			$log_result['chargePay____']	= '<p class="ok">Boleto gerado com sucesso - API Gerencianet.</p>';
			$log_result['chargePay___']	= $chargePay;
			$log_result['chargePay__']	= '<br/>$params2 & $BODY2:<br/>';
			$log_result['chargePay_']	=$params2;
			$log_result['chargePay_BODY2']	= $BODY2;
		}
	// Erros
	} catch (GerencianetException $e) {
		
		$error = '<b><span class="ggnberror">Erro ao gerar o Boleto:<br/>'.$e->errorDescription.'<br/><a href="'.$system_url.'/submitticket.php" target="_blank">Contate o suporte</a> informando o ID da fatura.';
		sendErrorsToAdmin($error, 'Falha ao gerar o boleto - 1ª tentativa. Linha: '.__LINE__);
		if ($debug_or_log) {
			$log_result = array();
			$log_result['chargePay____']	='<p class="erro">Falha ao gerar o boleto - 1ª tentativa - API Gerencianet.</p>';
			$log_result['chargePay___']	=$e;
			
		}

	} catch (Exception $e) {
		
		// Erro "CPF incorreto"
		if( strpos($e->getMessage(), 'cpf') !== false || strpos($e->getMessage(), 'Cpf') !== false ) {
			$chargePayError = $CPF_ERROR;
		}
		// Erro "CNPJ incorreto" - Não Exibir erro
		elseif( strpos($e->getMessage(), 'cnpj') !== false  || strpos($e->getMessage(), 'Cnpj') !== false ) {
			$chargePayError = false;
			$chargePayErrorTryCPF = true;
		}
		// Erro "Telefone incorreto"
		elseif( strpos($e->getMessage(), 'phone_number') !== false) {
			$chargePayError = $PHONENUMBER_ERROR;
		}
		// Erro "Nome incorreto"
		elseif( strpos($e->getMessage(), 'name') !== false) {
			$chargePayError = $NAME_ERROR;
		}
		// Erro no cadastro
		elseif( strpos($e->getMessage(), 'customer') !== false) {
			$chargePayError = $PROFILE_ERROR;
		}
		// Outros erros 
		else {

			$chargePayError = '<b><span class="ggnberror">Erro ao gerar o Boleto:<br/>'.$e->getMessage().'<br/><a href="'.$system_url.'/submitticket.php" target="_blank">Contate o suporte</a> informando o ID da fatura.';
		}
		
		$error = $chargePayError;
		sendErrorsToAdmin(print_r([
			'Mensagem HTML' => $error,
			'Exception Message' => $e->getMessage(),
			'Exception Class' => get_class($e),
			'Arquivo' => $e->getFile(),
			'Linha' => $e->getLine(),
			'Trace' => $e->getTraceAsString(),
		], true), 'Erro ao gerar o Boleto. Linha: '.__LINE__);
		if ($debug_or_log) {
			$log_result = array();
			$log_result['chargePay____']	= '<p class="erro">Falha ao gerar o boleto - 1ª tentativa, - API Gerencianet.</p>';
			$log_result['chargePay___']		= $e->getMessage();
			$log_result['chargePay__']		= '<br/>$params2 & $BODY2:<br/>';
			$log_result['chargePay_']		= $params2;
			$log_result['chargePay_BODY2']	= $BODY2;
		}
	}
	
	// Tentar gerar cobrança novamente com CPF, em caso de CNPJ incorreto
	if ( $chargePayErrorTryCPF ) {

	 	try {
		
			$params2 = array('id' => (int)$CHARGEID);
			$chargePay = $api->payCharge($params2, $BODY2);
		
			if ($debug_or_log) {
				$log_result = array();
				$log_result['chargePay____']	= '<p class="ok">Boleto gerado com sucesso - API Gerencianet.</p>';
				$log_result['chargePay___']	= $chargePay;
			}
		// Erros
		} catch (GerencianetException $e) {

			$error = '<b><span class="ggnberror">Erro ao gerar o Boleto:<br/>'.$e->errorDescription.'<br/><a href="'.$system_url.'/submitticket.php" target="_blank">Contate o suporte</a> informando o ID da fatura.';
			sendErrorsToAdmin($error, 'Falha ao gerar o boleto - 2ª tentativa. Linha: '.__LINE__);
			if ($debug_or_log) {
				$log_result = array();
				$log_result['chargePay____']	= '<p class="erro">Falha ao gerar o boleto - 2ª tentativa - API Gerencianet.</p>';
				$log_result['chargePay___']		= $e->errorDescription;
			}
		
		} catch (Exception $e) {
			// Erro "CPF incorreto"
			if( strpos($e->getMessage(), 'cpf') !== false || strpos($e->getMessage(), 'Cpf') !== false ) {
				$chargePayError = $CPF_ERROR;
			}
			// Erro "CNPJ incorreto" - Não Exibir erro
			elseif( strpos($e->getMessage(), 'cnpj') !== false  || strpos($e->getMessage(), 'Cnpj') !== false ) {
				$chargePayError = false;
				$chargePayErrorTryCPF = true;
			}
			// Erro "Telefone incorreto"
			elseif( strpos($e->getMessage(), 'phone_number') !== false) {
				$chargePayError = $PHONENUMBER_ERROR;
			}
			// Erro "Nome incorreto"
			elseif( strpos($e->getMessage(), 'name') !== false) {
				$chargePayError = $NAME_ERROR;
			}
			// Erro no cadastro
			elseif( strpos($e->getMessage(), 'customer') !== false) {
				$chargePayError = $PROFILE_ERROR;
			}
			// Outros erros 
			else {

				$chargePayError = '<b><span class="ggnberror">Erro ao gerar o Boleto:<br/>'.$e->getMessage().'<br/><a href="'.$system_url.'/submitticket.php" target="_blank">Contate o suporte</a> informando o ID da fatura.';
			}
		
			$error = $chargePayError;
			sendErrorsToAdmin($error, 'Falha ao gerar o boleto. Linha: '.__LINE__);
			if ($debug_or_log) {
				$log_result = array();
				$log_result['chargePay____']	= '<p class="erro">Falha ao gerar o boleto - 1ª tentativa, - API Gerencianet.</p>';
				$log_result['chargePay___']		=$e->getMessage();
			}
		}
	}
		
	if ( !$error ) {
		//return $chargePay;
		return array('result'=>$chargePay, 'log_result'=> $log_result);
	} elseif ( $error ) {
		//return (string)$error;
		return array('error'=>(string)$error, 'log_result'=> $log_result);
	}
}
}
/**
 *
 * Gravar transação no WHMCS
 * @ggnb_add_trans
 *
 */
if (!function_exists('ggnb_add_trans')) {
function ggnb_add_trans( $USERID, $INVOICEID, $CHARGEID, $whmcsAdmin, $api_mode, $system_url, $debug ) {
	$addtransaction = "addtransaction";
 	$addtransvalues['userid'] = $USERID;
 	$addtransvalues['invoiceid'] = $INVOICEID;
 	$addtransvalues['description'] = "Boleto gerado aguardando pagamento.";
 	$addtransvalues['amountin'] = '0.00';
 	$addtransvalues['fees'] = '0.00';
 	$addtransvalues['paymentmethod'] = 'gofasgerencianetboleto';
 	$addtransvalues['transid'] = 'ggnb_'.$api_mode.'_waiting-'.$CHARGEID.'';
 	$addtransvalues['date'] = date('d/m/Y');
	$addtransresults = localAPI( $addtransaction, $addtransvalues, $whmcsAdmin );
	
		if ($debug_or_log and $addtransresults['result'] === 'success' ) {
			$log_result = array();
			$log_result['addtransaction__']	= '<p class="ok">Transação gravada com sucesso - API WHMCS.</p>';
			$log_result['addtransaction_']	=  'ID da Transação: ' . $addtransvalues['transid'] . '<br>';
			$log_result['addtransaction']		= $addtransresults;
			
		} elseif ($debug_or_log and $addtransresults['result'] !== 'success'){
			$log_result = array();
			$log_result['addtransaction_']	= '<p class="erro">Erro ao gravar a transação - API WHMCS.</p>';
			$log_result['addtransaction']	= $addtransresults;
		}
	if ( $addtransresults['result'] === 'success' ) {
		//return $addtransresults;
		return array('result'=>$addtransresults, 'log_result'=> $log_result);
		
	} elseif ($addtransresults['result'] !== 'success') {
		$error = '<b>3) Não foi possível gerar o boleto, por favor <a href="'.$system_url.'/submitticket.php" target="_blank">entre em contato</a> informando o ID da fatura.</b>';
		//return $error;
		return array('error'=>$error, 'log_result'=> $log_result);
	}
}
}


/**
 *
 * Grava Boleto no DB
 *
 */
if( !function_exists('ggnb_store_billet') ) {
function ggnb_store_billet($pay_charge,$invoice_amount,$invoice_id,$debug_or_log,$api_mode) {

	 //die('die jardel ggnb_store_billet<pre>'.print_r($pay_charge, 1).'</pre>'); //aqui o boleto já foi gerado :()
	 $date = str_replace('/', '-', $pay_charge['data']['charges']['0']['dueDate']) ;
	 $dueDate = date("Y-m-d", strtotime($date));
	 $data = array(
				'invoice_id'=>$invoice_id,
				'charge_id'=>$pay_charge['data']['charge_id'],
				'link'=>$pay_charge['data']['link'],
				'pdf'=>$pay_charge['data']['pdf']['charge'],
				'expire_at'=> $pay_charge['data']['expire_at'],
				'total'=> $pay_charge['data']['total'],
				'barcode'=>$pay_charge['data']['barcode'],
				'status'=>$pay_charge['data']['status'],
				'qrcode'=> isset($pay_charge['data']['pix']['qrcode']) ? $pay_charge['data']['pix']['qrcode'] : '',
				'qrcode_image'=> isset($pay_charge['data']['pix']['qrcode_image']) ? $pay_charge['data']['pix']['qrcode_image'] : '',
				'api_mode'=>$api_mode,
			);
	 try {

		$save_billet = Capsule::table('gofasgerencianetboleto') ->insert($data);
		
	}
	catch (\Exception $e) {
		$error = "Não foi possível gravar o Boleto no banco de dados. {$e->getMessage()}";
	}
	
	if ($error) {
		if ($debug_or_log) {
			return array('error'=>$error, 'data'=>$data, 'date'=>$date,'duedate'=>$dueDate, 'save_billet'=>$save_billet);
		}
		else {
			return array('error'=>$error);
		}
	}
	elseif (!$error) {
		if ($debug_or_log) {
			return array('sucess'=>true, 'data'=>$data, 'date'=>$date,'duedate'=>$dueDate, 'save_billet'=>$save_billet);
		}
		else {
			return array('sucess'=>true);
		}
		
	}
		
}}

/**
 *
 * Envia email ao admin em caso de erro
 * ggnb_send_error_email
 *
 */
if (!function_exists('ggnb_send_error_email')) {
function ggnb_send_error_email( $INVOICEID, $USERID, $FNAME, $LNAME, $system_url, $ADMIN, $EOE, $ERROR, $debug_or_log ) {
	$sendEmailonError = "sendadminemail";
 	$sendEOEvalues['customsubject'] = 'Erro ao gerar boleto - fatura #'.$INVOICEID;
	$sendEOEvalues['custommessage'] = '<br/>Olá administrador,<br/>
		Ocorreu uma falha ao gerar um Boleto para a <a href="'.$system_url.'/admin/invoices.php?action=edit&id='.$INVOICEID.'">Fatura #'.$INVOICEID.'</a>.<br/><br/>
		Detalhes do erro:<br/>
		<b>Cliente:</b> <a href="'.$system_url.'/admin/clientssummary.php?userid='.$USERID.'">'.$FNAME.' '.$LNAME.'</a><br/><br/>
		<b>Erro exibido na Fatura:</b><br/><i>"'.$ERROR.'"</i><br/><br/>
		Email gerado de acordo com às configurações do gateway <a title="Ir para as configurações do módulo ↗" href="'.$system_url.'/admin/configgateways.php?updated=gofasgerencianetboleto#m_gofasgerencianetboleto">Gofas Gerencianet Boleto</a>.<br/><br/>';
 	$sendEOEvalues['type'] = 'system';
 	$sendEOEvalues['deptid'] = $EOE;
 	$sendEOEresults = @localAPI($sendEmailonError,$sendEOEvalues,$ADMIN);
		
	if ($debug_or_log and $sendEOEresults['result'] === 'success'){
		$log_result = array();
		$log_result['sendEmailonError_']	= '<p class="ok">Email envido ao admin notificando o erro - API WHMCS.</p>';
		$log_result['sendEmailonError']		= $sendEOEresults;
	}
	elseif($debug_or_log and $sendEOEresults['result'] !== 'success') {
		$log_result = array();
		$log_result['sendEmailonError_']	= '<p class="error">Falha ao enviar email notificando o erro - API WHMCS.</p>';
		$log_result['sendEmailonError']	= $sendEOEresults;
	}
	if ($debug_or_log){
		return array('result'=>$sendEOEresults, 'log_result'=>$log_result,);
 	}
	else {
		return array('result'=>$sendEOEresults);
 	}
}
}