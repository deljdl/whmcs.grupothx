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
if (!defined("WHMCS")) die("This file cannot be accessed directly");
use WHMCS\Database\Capsule;
include __DIR__ . '/../../gateways/gofasgerencianetboleto/sdk/vendor/autoload.php';
use Gerencianet\Exception\GerencianetException;
use Gerencianet\Gerencianet;

function gofasgerencianet_config() {
	
	// Get Module Params
	foreach( Capsule::table('tblpaymentgateways') -> where('gateway', '=', 'gofasgerencianetboleto') -> get( array( 'setting', 'value') ) as $settings ) {
		$setting_key				= $settings->setting;
		$params["$setting_key"]		= $settings->value;
	}
	// Parâmetros do Módulo
	$sandbox = $params['sandbox'];

	if ( $sandbox ) {
		$clientid				= $params['clientidsandbox'];
		$clientsecret			= $params['clientsecretsandbox'];
		$api_mode				= 'sandbox';
		$api_mode_FriendlyName	= 'Testes (<i>sandbox</i>)';
	
	}
	elseif (!$sandbox) {
		$clientid				= $params['clientid'];
		$clientsecret			= $params['clientsecret'];
		$api_mode				= 'live';
		$api_mode_FriendlyName = 'Produção (<i>live</i>)';
	}

	// Get Admin URL
	foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'ggnbwhmcsadminurl') -> get( array( 'value','created_at' ) ) as $ggnbwhmcsadminurl_ ) {
			$ggnbwhmcsadminurl				= $ggnbwhmcsadminurl_->value;
	}
	// Get WHMCS URL
	foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'ggnbwhmcsurl') -> get( array( 'value','created_at' ) ) as $ggnbwhmcsurl_ ) {
			$ggnbwhmcsurl				= $ggnbwhmcsurl_->value;
	}
	
	if (!$params['clientid'] || !$params['clientidsandbox']) {
		$intro = array(
				'FriendlyName' => '<p style="color: red;font-size: 16px;">ERRO: </p>',
				'Description'=>'<p style="color: red;font-size: 16px;">Verifique se o <a target="_blank" style="color: red; text-decoration:underline;" href="'.$ggnbwhmcsadminurl.'configgateways.php?updated=gofasgerencianetboleto#m_gofasgerencianetboleto">Módulo Gofas Gerencianet Boleto</a> está instalado e configurado corretamente.</p>',
		);
	}
	else {
		$intro = array(
				'FriendlyName' => 'Introdução',
				'Description'=>'<p><a target="_blank" style="text-decoration:underline;" href="'.$ggnbwhmcsadminurl.'configgateways.php?updated=gofasgerencianetboleto#m_gofasgerencianetboleto">Módulo Gofas Gerencinet Boleto</a> instalado e configurado em <b>modo '.$api_mode_FriendlyName.'</b>. <a target="_blank" style="text-decoration:underline;" href="'.$ggnbwhmcsadminurl.'configgateways.php?updated=gofasgerencianetboleto#m_gofasgerencianetboleto">Acesse aqui as configurações</a>.</p>
				<p>Ao ativar esse módulo <i>addon</i> foram geradas <a target="_blank" style="text-decoration:underline;" href="'.$ggnbwhmcsadminurl.'configemailtemplates.php">tags de mesclagem (<i>merge tags</i>)</a> que exibem informações do boleto no corpo do email das faturas.</p><p><a style="font-weight: bold;text-decoration:underline;" target="_blank" href="https://gofas.net/?p=7893#mergetags">Veja aqui a lista de tags de mesclagem disponíveis para os templates de email</a>.
				</p><p>
				Marque abaixo os grupos de administradores que poderão gerenciar as opções da API Gerencianet via WHMCS.<br><b><a target="_blank" style="text-decoration:underline;" href="'.$ggnbwhmcsadminurl.'addonmodules.php?module=gofasgerencianet">Acesse aqui as opções disponíveis desse addon</a></b>.</p>
				',
		);
	}
	
    $configarray = array(
    "name" => "Gofas Gerencianet",
    "description" => "Painel de gerenciamento remoto da API Gerencianet.",
    "version" => '2.0.2',
    "author" => '<a title="Gofas Softwares" href="https://gofas.net/" target="_blank" alt="Gofas"><img src="'.$ggnbwhmcsurl.'modules/addons/gofasgerencianet/lib/logo.png"></a>',
    "fields" => array(
        'intro' => $intro,
		// Crédito
		'footer' => array(
			'Description' => '<div style="background: #dde9f9;padding: 5px 15px;position: absolute; bottom: 0;">Versão 2.0.2 | <a target="_blank" title="↗ Gofas.net" href="https://gofas.net">&copy; '.date('Y').' Gofas.net</a> | <a target="_blank" title="↗ Documentação e Suporte" href="https://github.com/gofas/whmcs-gerencianet-dashboard/">Documentação e Suporte</a><br/></div>',
		),
							  
    ));
    return $configarray;
}

function gofasgerencianet_output($vars) {
 
	// Get Module Params
	foreach( Capsule::table('tblpaymentgateways') -> where('gateway', '=', 'gofasgerencianetboleto') -> get( array( 'setting', 'value') ) as $settings ) {
		$setting_key				= $settings->setting;
		$params["$setting_key"]		= $settings->value;
	}
	// Parâmetros do Módulo
	$sandbox = $params['sandbox'];
	$version	= $vars['version'];
	$debug		= $params['debug'];
	$whmcs_admin	= $vars['admin'];

	if ( $sandbox ) {
		$sandbox				= true;
		$clientid				= $params['clientidsandbox'];
		$clientsecret			= $params['clientsecretsandbox'];
		$api_mode				= 'sandbox';
		$api_mode_FriendlyName	= 'Testes (<i>sandbox</i>)';
	
	}
	elseif (!$sandbox) {
		$sandbox				= false;
		$clientid				= $params['clientid'];
		$clientsecret			= $params['clientsecret'];
		$api_mode				= 'live';
		$api_mode_FriendlyName = 'Produção (<i>live</i>)';
	}
	$options	= array("client_id"=>$clientid,"client_secret"=>$clientsecret,"sandbox"=>$sandbox,"debug"=>$debug); 
	$api		= new Gerencianet($options);

	// Get Admin URL
	foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'ggnbwhmcsadminurl') -> get( array( 'value','created_at' ) ) as $ggnbwhmcsadminurl_ ) {
			$ggnbwhmcsadminurl				= $ggnbwhmcsadminurl_->value;
	}
	// Get WHMCS URL
	foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'ggnbwhmcsurl') -> get( array( 'value','created_at' ) ) as $ggnbwhmcsurl_ ) {
			$ggnbwhmcsurl				= $ggnbwhmcsurl_->value;
	}

	
if ( $_POST['invoice_id'] || $_POST['trans_id'] ) {
		
	// Verifica transações associadas à fatura
	if ( $_POST['invoice_id'] ) {
		
		$invoice_id	= $_POST['invoice_id'];
			
		// Parâmetros da fatura
		$getinvoice					= 'getinvoice';
		$getinvoice_id['invoiceid']	= $invoice_id;
		$getinvoiceResults			= localAPI($getinvoice,$getinvoice_id,$whmcs_admin);
	
		// Parâmetros das transações
		$transIDendA					= $getinvoiceResults['transactions'];
		if($transIDendA) {
			$transIDend					= $transIDendA['transaction'];
		}
		if ($transIDend) {
			$transIDp					= end($transIDend); // Pula para o último resultado do array
			$transID					= preg_replace("/[^0-9]/", "", $transIDp['transid']); // Captura ID da Última transação
			$charge 					= $transID;

		} else {
			$transID					= false;
			$error					.= 'Nenhuma transação que possa ser cancelada foi encontrada para a fatura #'.$invoice_id .' ou essa fatura não existe.';
		}

	} elseif ( $_POST['trans_id'] ) {
		$trans_id = $_POST['trans_id'];
		$charge = $trans_id;
	}
	
	// Verifica se a fatura possui um boleto associado
	if ( $charge ) {
	
		// Verifica se a fatura atual já tem um boleto associado
		try {
			$chargeExist = $api->detailCharge(array('id' => (int)$charge ),array());
			$chargeExistID = $chargeExist['data']['charge_id'];// Captura id da transação gerada anteriormente pela fatura atual		
			$chargeExistStatus = $chargeExist['data']['status'];
		
        	if ($debug) {
            	echo '<pre><b>Resultado da busca pela transação  #'.$charge.'.</b><br/>';
				echo'Essa fatura já gerou um boleto.<br/>';
				print_r($chargeExist);
				echo '<br/>';
				echo $chargeExistStatus;
				echo'<br/></pre>';
        	}
			//$result .= 'Boleto localizado<br/>';
		
    	} catch (GerencianetException $e) {
			if ($debug) {
				echo '<pre><b>Resultado da busca por boletos gerados pela fatura #'.$invoice_id.'.</b><br/>';
            	if ($e->code == '3500034') {
					echo'<b>Essa fatura ainda não gerou nenhum boleto.</b><br/>';
				} else {
				    echo'<b>Erro ao consultar boletos gerados por essa fatura.</b><br/>';
				}
				print_r($e->code);
				echo '<br/>';
				print_r($e->error);
				echo '<br/>';
				print_r($e->errorDescription);
				echo '<br/>';
				echo'<br/></pre>';
			}
			
			$error .= $e->errorDescription['message'].'<br/>';
			
		} catch (Exception $e) {
			if ($debug) {
				echo'<pre><b>Erro de comunicação (API externa - Gerencianet).</b><br/>';
				print_r($e->getMessage());
				echo'<br/></pre>';
			}
			$error .= $e->getMessage().'<br/>';
			
		}
	}
	
	// Cancela
	if ( !$error and $chargeExistStatus !== 'paid' and $chargeExistStatus !== 'canceled' ) {
		try {
                $api = new Gerencianet($options);
                $cancelCharge = $api->cancelCharge(array('id' => (int)$charge ), array());
				
				if ( $cancelCharge['code'] == '200' ) {
					$result .= 'Transação cancelada.';
					
				} else {
					$error				.= 'Transação não cancelada.';
					
				}
				
                if ($debug) {
                    echo '<pre><b>Transação ' . $transID . ' cancelada com sucesso.</b><br/>';
                    print_r($cancelCharge);
					echo '<br/>';
					//echo $cancelCharge['code'];
                    echo '</pre>';
				}
				
            } catch (GerencianetException $e) {
                if ($debug) {
                    echo '<pre><b>Erro ao cancelar transação #' . $charge . '</b><br/>';
                    echo '<br/>';
                    print_r($e->code);
                    echo '<br/>';
                    print_r($e->error);
                    echo '<br/>';
                    print_r($e->errorDescription);
                    echo '<br/></pre>';
                }
				
				$error .= $e->errorDescription['message'];
				
            } catch (Exception $e) {
                if ($debug) {
                    echo '<pre><b>Erro ao cancelar transação' . $charge . '</b><br/>';
                    print_r($e->getMessage());
                    echo '<br/></pre>';
                }
				
				$error .= $e->getMessage();
            }
		}
		// Pago
		elseif ( !$error and $chargeExistStatus == 'paid') {
			$error .= 'A transação #'.$charge.' não pode ser cancelapa pois já foi paga.';
			
		}
		// já cancelada
		elseif ( !$error and $chargeExistStatus == 'canceled') {
			$error .= 'A transação #'.$charge.' já foi cancelada.';
			
		}
		// Não encontrado
		elseif( !$error and $charge and !$chargeExistStatus ) {
			$error .= 'A API não retornou o status da transação, verifique as configurações do módulo e su painel da API Gerencianet.';
		}
	
	// Resultado
	if ($error || $chargeNoCanceled) {
		echo '<pre style="background:red; color: #fff;">';
		echo $error;
		echo '</pre>';
	} elseif ($result) {
		echo '<pre style="background: green;color: #fff;">';
		echo $result . $chargeNoCanceled;
		echo '</pre>';
	}
}
	// Admin form
	echo '<h3>Cancelar Boletos associados às Faturas</h3>';
	echo '<p style="margin-bottom:20px;border-bottom: 1px solid #dde9f9; padding-bottom: 10px;">Cancele transações na API Gerenciante através do ID da <b>Fatura</b> ou ID do <b>Boleto / Transação</b>.</p>';
	echo '	<form style="margin:0px 0px 25px 0px;min-height: 130px;border-bottom: 1px solid #dde9f9;" name="cancel_billet" method="POST" action="'.$ggnbwhmcsadminurl.'addonmodules.php?module=gofasgerencianet" >
    			<div style="margin-bottom:15px;">
					<span>Cancelar Transação através do ID da <b>Fatura</b></span>
					<input type="text" name="invoice_id">
		    		<input type="submit" value="Cancelar Transação">
				</div>
				<div>
					<span>Cancelar Transação através do ID da <b>Transação</b>
					<input type="text" name="trans_id">
		    		<input type="submit" value="Cancelar Transação">
				</div>
			</form>';
	echo '<div style="background: #dde9f9;padding: 5px 15px;">Versão '.$version.' | <a target="_blank" title="↗ Gofas.net" href="https://gofas.net">&copy; 2016 - '.date('Y').' Gofas.net</a><br/></div>';
}