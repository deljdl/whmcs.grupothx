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
require_once __DIR__ . '/../../gateways/gofasgerencianetboleto/sdk/efi_rest.php';
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

    // Pagamentos: consulta isolada, sem alterar cobranças na Efí.
    if (empty($_SESSION['efi_conferencia_token'])) {
        $_SESSION['efi_conferencia_token'] = bin2hex(random_bytes(24));
    }
    $csrf = htmlspecialchars($_SESSION['efi_conferencia_token'], ENT_QUOTES, 'UTF-8');
    $post = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    $valid = $post && hash_equals($_SESSION['efi_conferencia_token'], (string)($_POST['efi_token'] ?? ''));
    $action = (string)($_POST['efi_action'] ?? '');
    $page = max(1, (int)($_REQUEST['efi_page'] ?? 1));
    $url = htmlspecialchars($ggnbwhmcsadminurl . 'addonmodules.php?module=gofasgerencianet', ENT_QUOTES, 'UTF-8');
    $statuses = array();
    $alerts = array();
    if ($action && !$valid) {
        $alerts[] = 'Sessão expirada. Recarregue a página.';
        $action = '';
    }

    $rows = Capsule::table('tblinvoices')->where('status', 'Unpaid')
        ->where('paymentmethod', 'gofasgerencianetboleto')->orderBy('id', 'desc')
        ->offset(($page - 1) * 20)->limit(21)->get(array('id', 'total', 'duedate'));
    $hasNext = count($rows) > 20;
    $rows = array_slice($rows->all(), 0, 20);
    $ids = array_map(function ($r) { return (int)$r->id; }, $rows);

    if (in_array($action, array('check', 'check_all', 'pay'), true)) {
        $requested = (int)($_POST['efi_invoice'] ?? 0);
        $targets = $action === 'check_all' ? $ids : (in_array($requested, $ids, true) ? array($requested) : array());
        if (!$targets) $alerts[] = 'Fatura não encontrada na página atual.';
        foreach ($targets as $id) {
            $invoice = Capsule::table('tblinvoices')->where('id', $id)
                ->where('status', 'Unpaid')->where('paymentmethod', 'gofasgerencianetboleto')->first();
            if (!$invoice) continue;
            $boleto = Capsule::table('gofasgerencianetboleto')->where('invoice_id', $id)
                ->where('api_mode', $api_mode)->orderBy('id', 'desc')->first();
            $chargeId = $boleto ? (int)$boleto->charge_id : 0;
            if (!$chargeId) {
                $history = Capsule::table('tblaccounts')->where('invoiceid', $id)
                    ->orderBy('id', 'desc')->limit(20)->get(array('transid'));
                foreach ($history as $t) {
                    if (preg_match('/^ggnb_.*?(?:waiting|unpaid|notification)-([0-9]+)/', (string)$t->transid, $m)) {
                        $chargeId = (int)$m[1];
                        break;
                    }
                }
            }
            if (!$chargeId) {
                $statuses[$id] = array('text' => 'Sem cobrança identificada', 'paid' => false);
                continue;
            }
            try {
                $response = $api->detailCharge(array('id' => $chargeId), array());
                $data = $response['data'] ?? array();
                $status = (string)($data['status'] ?? '');
                $amount = (int)($data['total'] ?? -1);
                $expected = (int)round((float)$invoice->total * 100);
                $customId = (string)($data['custom_id'] ?? ($data['metadata']['custom_id'] ?? ''));
                $paid = $status === 'paid' && (int)($data['charge_id'] ?? 0) === $chargeId
                    && $customId === (string)$id && $amount === $expected;
                $statuses[$id] = array('text' => $paid ? 'Pago na Efí' : ($status ?: 'Resposta incompleta'), 'paid' => $paid);
                if ($action === 'pay') {
                    $already = Capsule::table('tblaccounts')->where('invoiceid', $id)->where('amountin', '>', 0)->exists()
                        || Capsule::table('tblaccounts')->where('transid', 'efi-manual-' . $chargeId)->exists();
                    if (!$paid || $already) {
                        $alerts[] = 'Fatura #' . $id . ': baixa bloqueada (pagamento não validado ou já registrado).';
                        continue;
                    }
                    $result = localAPI('AddInvoicePayment', array(
                        'invoiceid' => $id, 'transid' => 'efi-manual-' . $chargeId,
                        'gateway' => 'gofasgerencianetboleto', 'date' => date('Y-m-d H:i:s'),
                        'amount' => $expected / 100, 'noemail' => true
                    ), $whmcs_admin);
                    $alerts[] = ($result['result'] ?? '') === 'success'
                        ? 'Fatura #' . $id . ': baixa registrada.'
                        : 'Fatura #' . $id . ': erro ao registrar baixa.';
                }
            } catch (\Throwable $e) {
                $statuses[$id] = array('text' => 'Erro na consulta', 'paid' => false);
                $alerts[] = 'Fatura #' . $id . ': falha ao consultar Efí.';
            }
        }
    }

    echo '<style>
        .efi-box{border:1px solid #dfe5ed;border-radius:8px;background:#fff;padding:20px;margin:18px 0}
        .efi-box h3{margin:0 0 8px}.efi-box p{color:#64748b;margin-bottom:16px}
        .efi-box .efi-line{display:flex;align-items:end;gap:12px;flex-wrap:wrap;margin:12px 0}
        .efi-box label{display:block;margin:0 0 5px;font-weight:600}
        .efi-box input{height:35px;padding:6px 10px;border:1px solid #ccd5e0;border-radius:4px}
        .efi-box form{display:inline-block;margin:0}
        .efi-box table{width:100%;border-collapse:collapse}
        .efi-box td,.efi-box th{padding:10px;border-bottom:1px solid #e4e9f0;text-align:left}
        .efi-box th{background:#f5f7fa}
    </style>';
    foreach ($alerts as $alert) echo '<div class="alert alert-info">' . htmlspecialchars($alert, ENT_QUOTES, 'UTF-8') . '</div>';

    echo '<div class="efi-box"><h3>Conferir pagamentos na Efí</h3>
      <p>Faturas não pagas no WHMCS. A consulta não faz baixas. Consulte e confirme individualmente cada pagamento.</p>
      <form method="post" action="' . $url . '">
        <input type="hidden" name="efi_token" value="' . $csrf . '">
        <input type="hidden" name="efi_page" value="' . $page . '">
        <button class="btn btn-primary" name="efi_action" value="check_all">Consultar todas desta página</button>
      </form>
      <div style="overflow-x:auto;margin-top:15px"><table><thead><tr>
        <th>Fatura</th><th>Valor</th><th>Vencimento</th><th>Efí</th><th>Ações</th>
      </tr></thead><tbody>';
    foreach ($rows as $r) {
        $id = (int)$r->id;
        if ($action === 'pay' && $id === (int)($_POST['efi_invoice'] ?? 0)
            && Capsule::table('tblinvoices')->where('id', $id)->value('status') !== 'Unpaid') continue;
        $info = $statuses[$id] ?? array('text' => 'Não consultado', 'paid' => false);
        echo '<tr><td>#' . $id . '</td><td>R$ ' . number_format((float)$r->total, 2, ',', '.') . '</td>
        <td>' . htmlspecialchars((string)$r->duedate, ENT_QUOTES, 'UTF-8') . '</td>
        <td>' . htmlspecialchars($info['text'], ENT_QUOTES, 'UTF-8') . '</td><td>
        <form method="post" action="' . $url . '">
          <input type="hidden" name="efi_token" value="' . $csrf . '">
          <input type="hidden" name="efi_page" value="' . $page . '">
          <input type="hidden" name="efi_invoice" value="' . $id . '">
          <button class="btn btn-default btn-sm" name="efi_action" value="check">Consultar</button>
        </form>';
        if ($info['paid']) echo ' <form method="post" action="' . $url . '" onsubmit="return confirm(\'Registrar pagamento da fatura #' . $id . ' no WHMCS?\')">
          <input type="hidden" name="efi_token" value="' . $csrf . '">
          <input type="hidden" name="efi_page" value="' . $page . '">
          <input type="hidden" name="efi_invoice" value="' . $id . '">
          <button class="btn btn-success btn-sm" name="efi_action" value="pay">Dar baixa</button>
        </form>';
        echo '</td></tr>';
    }
    if (!$rows) echo '<tr><td colspan="5">Nenhuma fatura pendente nesta página.</td></tr>';
    echo '</tbody></table></div><div style="margin-top:12px">';
    if ($page > 1) echo '<a class="btn btn-default" href="' . $url . '&efi_page=' . ($page - 1) . '">Anterior</a> ';
    if ($hasNext) echo '<a class="btn btn-default" href="' . $url . '&efi_page=' . ($page + 1) . '">Próxima</a>';
    echo '</div></div>';

    echo '<div class="efi-box"><h3>Cancelar boletos</h3>
      <p>Cancelamento manual de cobranças na Efí. Confira o ID antes de confirmar.</p>
      <form method="post" action="' . $url . '" onsubmit="return confirm(\'Cancelar cobrança associada à fatura?\')">
        <div class="efi-line"><div><label>ID da fatura</label><input name="invoice_id" type="number" min="1" required></div>
        <button class="btn btn-danger" type="submit">Cancelar pela fatura</button></div>
      </form>
      <form method="post" action="' . $url . '" onsubmit="return confirm(\'Cancelar esta cobrança na Efí?\')">
        <div class="efi-line"><div><label>ID da cobrança Efí</label><input name="trans_id" type="number" min="1" required></div>
        <button class="btn btn-danger" type="submit">Cancelar pela cobrança</button></div>
      </form></div>';
    echo '<div style="background:#f4f6f9;padding:10px 15px">Versão ' . htmlspecialchars((string)$version, ENT_QUOTES, 'UTF-8') . ' | Gofas.net</div>';
}
