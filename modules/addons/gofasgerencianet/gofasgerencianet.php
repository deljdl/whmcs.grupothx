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
require_once ROOTDIR . '/includes/gatewayfunctions.php';
use Gerencianet\Exception\GerencianetException;
use Gerencianet\Gerencianet;

function gofasgerencianet_config() {
	
	// Get Module Params
	$params = getGatewayVariables('gofasgerencianetboleto');
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
	$params = getGatewayVariables('gofasgerencianetboleto');
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

	
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && (isset($_POST['invoice_id']) || isset($_POST['trans_id']))) {
    if (empty($_SESSION['efi_conferencia_token']) || !hash_equals($_SESSION['efi_conferencia_token'], (string)($_POST['efi_token'] ?? ''))) {
        echo '<div class="alert alert-danger">Sessão inválida. Recarregue a página.</div>';
        return;
    }
		
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
        $charge = 0;
        if ($transIDend) {
            foreach (array_reverse($transIDend) as $transaction) {
                if (preg_match('/^ggnb_' . preg_quote($api_mode, '/') . '_.*?(?:waiting|unpaid|notification)-([0-9]+)/', (string)($transaction['transid'] ?? ''), $match)) {
                    $charge = (int)$match[1];
                    break;
                }
            }
        }
        $transID = $charge;
        if (!$charge) {
            $error .= 'Nenhuma cobrança Efí válida localizada para esta fatura.';
        }

	} elseif ( $_POST['trans_id'] ) {
		$trans_id = $_POST['trans_id'];
        $charge = ctype_digit((string)$trans_id) ? (int)$trans_id : 0;
        if (!$charge) $error .= 'ID da cobrança inválido.';
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
	if ( !$error and $charge && isset($chargeExistStatus) && $chargeExistStatus !== 'paid' && $chargeExistStatus !== 'canceled' ) {
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
    $searchId = max(0, (int)($_REQUEST['efi_search'] ?? 0));
    $searchValue = $searchId ? (string)$searchId : '';
    $searchHidden = '<input type="hidden" name="efi_search" value="' . $searchValue . '">';
    $url = htmlspecialchars($ggnbwhmcsadminurl . 'addonmodules.php?module=gofasgerencianet', ENT_QUOTES, 'UTF-8');
    // Diagnóstico manual de fatura (somente leitura).
    echo '<div class="panel panel-default" style="margin:15px 0"><div class="panel-heading"><strong>Diagnóstico de fatura Efí</strong></div><div class="panel-body">'
        . '<form method="get" action="addonmodules.php" class="form-inline">'
        . '<input type="hidden" name="module" value="gofasgerencianet">'
        . '<label for="efi-test-id" style="margin-right:8px">ID da fatura</label> '
        . '<input id="efi-test-id" type="number" name="efi_test_invoice" min="1" required class="form-control" style="width:150px" value="'
        . htmlspecialchars((string)max(0, (int)($_GET['efi_test_invoice'] ?? 0)), ENT_QUOTES, 'UTF-8')
        . '"> <button type="submit" class="btn btn-primary">Verificar fatura</button>'
        . '</form><small>Somente consultas: não gera boletos, não registra pagamentos e não altera transações.</small>'
        . '</div></div>';
    // Diagnóstico somente leitura do reconhecimento de transações pelo gateway.
    $testInvoiceId = max(0, (int)($_GET['efi_test_invoice'] ?? 0));
    if ($testInvoiceId) {
        $testInvoice = localAPI('GetInvoice', array('invoiceid' => $testInvoiceId), $whmcs_admin);
        $testLines = array('Fatura: #' . $testInvoiceId, 'Modo Efí: ' . $api_mode);
        if (($testInvoice['result'] ?? '') !== 'success') {
            $testLines[] = 'Erro ao consultar a fatura: ' . ($testInvoice['message'] ?? 'Resposta inválida do WHMCS');
        } else {
            $testLines[] = 'Status WHMCS: ' . ($testInvoice['status'] ?? '');
            $testLines[] = 'Cliente ID: ' . ($testInvoice['userid'] ?? '(ausente)');
            $testLines[] = 'Data de emissão: ' . ($testInvoice['date'] ?? '(ausente)');
            $testLines[] = 'Vencimento: ' . ($testInvoice['duedate'] ?? '(ausente)');
            $testLines[] = 'Forma de pagamento: ' . ($testInvoice['paymentmethod'] ?? '(ausente)');
            $testLines[] = 'Subtotal: R$ ' . number_format((float)($testInvoice['subtotal'] ?? 0), 2, ',', '.');
            $testLines[] = 'Créditos aplicados: R$ ' . number_format((float)($testInvoice['credit'] ?? 0), 2, ',', '.');
            $testLines[] = 'Total: R$ ' . number_format((float)($testInvoice['total'] ?? 0), 2, ',', '.');
            $testLines[] = 'Saldo: R$ ' . number_format((float)($testInvoice['balance'] ?? 0), 2, ',', '.');
            $testLines[] = 'Data do pagamento: ' . ($testInvoice['datepaid'] ?: '(não paga)');
            $testLines[] = '';
            $testLines[] = '--- ITENS DA FATURA ---';
            foreach (($testInvoice['items']['item'] ?? array()) as $item) {
                $testLines[] = ' - ' . ($item['description'] ?? '(sem descrição)')
                    . ' | R$ ' . number_format((float)($item['amount'] ?? 0), 2, ',', '.')
                    . ' | Tipo: ' . ($item['type'] ?? '(ausente)')
                    . ' | Ref.: ' . ($item['relid'] ?? '(ausente)');
            }
            $testLines[] = '';
            $testLines[] = '--- TRANSAÇÕES DA FATURA ---';
            $transactions = $testInvoice['transactions']['transaction'] ?? array();
            if (isset($transactions['transid'])) $transactions = array($transactions);
            $testLines[] = 'Transações retornadas por GetInvoice: ' . count($transactions);
            foreach ($transactions as $transaction) {
                $testLines[] = ' - ' . ($transaction['transid'] ?? '(sem referência)')
                    . ' | Data: ' . ($transaction['date'] ?? '(ausente)')
                    . ' | Entrada: R$ ' . number_format((float)($transaction['amountin'] ?? 0), 2, ',', '.')
                    . ' | Saída: R$ ' . number_format((float)($transaction['amountout'] ?? 0), 2, ',', '.');
            }
            // Reproduz literalmente a seleção da última transação em params/3_invoice.php.
            $last = $transactions ? end($transactions) : array();
            $reference = (string)($last['transid'] ?? '');
            $recognized = strpos($reference, 'ggnb') !== false
                && (strpos($reference, 'unpaid') !== false || strpos($reference, 'waiting') !== false)
                && strpos($reference, $api_mode);
            $chargeId = $recognized ? (int)preg_replace('/[^0-9]/', '', $reference) : 0;
            $testLines[] = '';
            $testLines[] = 'Última referência selecionada: ' . ($reference ?: '(nenhuma)');
            $testLines[] = 'ID reconhecido: ' . ($chargeId ?: 'NENHUM');
            $testLines[] = 'Próxima etapa prevista: ' . ($chargeId ? 'Consultar cobrança existente na Efí' : 'Criar uma cobrança na Efí');
            if ($chargeId) {
                $testLines[] = '';
                $testLines[] = '--- CONSULTA REAL À EFÍ (SOMENTE LEITURA) ---';
                try {
                    $efiResponse = $api->detailCharge(array('id' => $chargeId));
                    $efiData = $efiResponse['data'] ?? array();
                    $efiAmount = (int)($efiData['total'] ?? -1);
                    $invoiceAmount = (int)round((float)($testInvoice['total'] ?? 0) * 100);
                    $efiCustomId = (string)($efiData['custom_id'] ?? ($efiData['metadata']['custom_id'] ?? ''));
                    $testLines[] = 'ID da cobrança: ' . ($efiData['charge_id'] ?? '(ausente)');
                    $testLines[] = 'Status da cobrança: ' . ($efiData['status'] ?? '(ausente)');
                    $testLines[] = 'Criada na Efí: ' . ($efiData['created_at'] ?? '(ausente)');
                    $testLines[] = 'Método de pagamento: ' . ($efiData['payment']['method'] ?? '(ausente)');
                    $testLines[] = 'Vencimento do boleto: ' . ($efiData['payment']['banking_billet']['expire_at'] ?? '(ausente)');
                    $testLines[] = 'Link do boleto: ' . ($efiData['payment']['banking_billet']['link'] ?? '(ausente)');
                    $testLines[] = 'URL de notificação: ' . ($efiData['metadata']['notification_url'] ?? '(ausente)');
                    $testLines[] = 'custom_id: ' . ($efiCustomId ?: '(ausente)');
                    $testLines[] = 'Valor Efí: R$ ' . ($efiAmount >= 0 ? number_format($efiAmount / 100, 2, ',', '.') : '(ausente)');
                    $testLines[] = 'Valor fatura: R$ ' . number_format($invoiceAmount / 100, 2, ',', '.');
                    $testLines[] = 'IDs conferem: ' . (((int)($efiData['charge_id'] ?? 0) === $chargeId && $efiCustomId === (string)$testInvoiceId) ? 'SIM' : 'NÃO');
                    $testLines[] = 'Valores conferem: ' . ($efiAmount === $invoiceAmount ? 'SIM' : 'NÃO');
                } catch (\Throwable $e) {
                    $testLines[] = 'Erro ao consultar a Efí: ' . $e->getMessage();
                }
                $testLines[] = '';
                $testLines[] = '--- REGISTRO WHMCS (SOMENTE LEITURA) ---';
                $expectedTransid = 'ggnb_' . $api_mode . '_waiting-' . $chargeId;
                $matching = Capsule::table('tblaccounts')->where('transid', $expectedTransid)
                    ->get(array('invoiceid', 'transid', 'amountin'));
                $testLines[] = 'Transação de emissão esperada: ' . $expectedTransid;
                $testLines[] = 'Registros com esse ID: ' . count($matching);
                foreach ($matching as $matched) {
                    $testLines[] = ' - Fatura #' . $matched->invoiceid . ' | ' . $matched->transid . ' | R$ ' . $matched->amountin;
                }
                $testLines[] = 'AddTransaction seria necessário agora: ' . (count($matching) ? 'NÃO (transação já registrada)' : 'SIM (ainda não registrada)');
            }
            $testLines[] = '';
            $testLines[] = 'Transações no banco (tblaccounts):';
            $records = Capsule::table('tblaccounts')->where('invoiceid', $testInvoiceId)
                ->orderBy('id', 'asc')->get(array('id', 'transid', 'amountin', 'date', 'description'));
            foreach ($records as $record) {
                $testLines[] = $record->id . ' | ' . $record->transid . ' | Entrada R$ ' . $record->amountin
                    . ' | Data: ' . $record->date . ' | Descrição: ' . ($record->description ?: '(sem descrição)');
            }
        }
        echo '<div class="alert alert-info" style="margin:15px 0"><strong>Teste isolado (somente leitura)</strong>'
            . '<pre style="white-space:pre-wrap;margin-top:10px">'
            . htmlspecialchars(implode("\n", $testLines), ENT_QUOTES, 'UTF-8')
            . '</pre></div>';
    }
    // Mesma origem de callback utilizada pelo gateway na criação de cobranças.
    $expectedCallback = rtrim((string)$ggnbwhmcsurl, '/') . '/modules/gateways/gofasgerencianetboleto/includes/callback.php';
    $callbackConfigured = filter_var($expectedCallback, FILTER_VALIDATE_URL)
        && parse_url($expectedCallback, PHP_URL_SCHEME) === 'https';
    $statuses = array();
    $alerts = array();
    if ($action && !$valid) {
        $alerts[] = 'Sessão expirada. Recarregue a página.';
        $action = '';
    }

    $query = Capsule::table('tblinvoices')->where('status', 'Unpaid')
        ->where('paymentmethod', 'gofasgerencianetboleto');
    if ($searchId) $query->where('id', $searchId);
    $rows = $query->orderBy('date', 'desc')->orderBy('id', 'desc')
        ->offset($searchId ? 0 : ($page - 1) * 20)->limit($searchId ? 1 : 21)->get(array('id', 'userid', 'total', 'duedate', 'date', 'status'));
    $hasNext = !$searchId && count($rows) > 20;
    $rows = array_slice($rows->all(), 0, 20);
    $ids = array_map(function ($r) { return (int)$r->id; }, $rows);
    $clientNames = array();
    $efiTransactions = array();
    if ($rows) {
        $transactions = Capsule::table('tblaccounts')->whereIn('invoiceid', $ids)
            ->where('transid', 'like', 'ggnb_%')->orderBy('id', 'desc')->get(array('invoiceid', 'transid'));
        foreach ($transactions as $transaction) {
            if (!isset($efiTransactions[$transaction->invoiceid]) && strpos((string)$transaction->transid, 'ggnb_') === 0) {
                $efiTransactions[$transaction->invoiceid] = (string)$transaction->transid;
            }
        }
        $clients = Capsule::table('tblclients')->whereIn('id', array_unique(array_map(function ($r) { return (int)$r->userid; }, $rows)))
            ->get(array('id', 'firstname', 'lastname', 'companyname'));
        foreach ($clients as $client) {
            $clientNames[$client->id] = trim($client->firstname . ' ' . $client->lastname);
            if ($client->companyname) $clientNames[$client->id] .= ' (' . $client->companyname . ')';
        }
    }

    if (in_array($action, array('check', 'check_all', 'fix_callback', 'pay'), true)) {
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
                $statuses[$id] = array('text' => 'Sem cobrança identificada');
                continue;
            }
            try {
                $response = $api->detailCharge(array('id' => $chargeId), array());
                $data = $response['data'] ?? array();
                $status = (string)($data['status'] ?? '');
                $amount = (int)($data['total'] ?? -1);
                $expected = (int)round((float)$invoice->total * 100);
                $customId = (string)($data['custom_id'] ?? ($data['metadata']['custom_id'] ?? ''));
                $currentCallback = (string)($data['notification_url'] ?? ($data['metadata']['notification_url'] ?? ''));
                $identifiersMatch = (int)($data['charge_id'] ?? 0) === $chargeId
                    && $customId === (string)$id && $amount === $expected;
                $callbackMismatch = $currentCallback !== '' && $currentCallback !== $expectedCallback;
                if ($action === 'fix_callback') {
                    if (!$callbackConfigured || !$identifiersMatch || !$callbackMismatch) {
                        $alerts[] = 'Fatura #' . $id . ': correção bloqueada; confira a URL e os identificadores da cobrança.';
                    } else {
                        $api->updateChargeMetadata(array('id' => $chargeId), array(
                            'notification_url' => $expectedCallback,
                            'custom_id' => $customId
                        ));
                        // A Efí retorna somente o código de sucesso; conferir por nova consulta.
                        $response = $api->detailCharge(array('id' => $chargeId), array());
                        $data = $response['data'] ?? array();
                        $currentCallback = (string)($data['notification_url'] ?? ($data['metadata']['notification_url'] ?? ''));
                        $callbackMismatch = $currentCallback !== '' && $currentCallback !== $expectedCallback;
                        $alerts[] = 'Fatura #' . $id . ': ' . ($currentCallback === $expectedCallback
                            ? 'URL de callback corrigida e confirmada na Efí.'
                            : 'A Efí recebeu a atualização, mas a URL ainda não foi confirmada. Consulte novamente.');
                    }
                }
                // A baixa é permitida somente após nova consulta e validação integral na Efí.
                $paidVerified = $status === 'paid' && $identifiersMatch;
                $alreadyPaid = Capsule::table('tblaccounts')->where('invoiceid', $id)->where('amountin', '>', 0)->exists()
                    || Capsule::table('tblaccounts')->where('transid', 'efi-manual-' . $chargeId)->exists();
                $paymentDone = false;
                if ($action === 'pay') {
                    if (!$paidVerified || $alreadyPaid) {
                        $alerts[] = 'Fatura #' . $id . ': baixa bloqueada (pagamento não confirmado ou já registrado).';
                    } else {
                        $result = localAPI('AddInvoicePayment', array(
                            'invoiceid' => $id, 'transid' => 'efi-manual-' . $chargeId,
                            'gateway' => 'gofasgerencianetboleto', 'date' => date('Y-m-d H:i:s'),
                            'amount' => $expected / 100, 'noemail' => true
                        ), $whmcs_admin);
                        $paymentDone = ($result['result'] ?? '') === 'success';
                        $alerts[] = 'Fatura #' . $id . ': ' . ($paymentDone
                            ? 'baixa registrada no WHMCS.' : 'não foi possível registrar a baixa: ' . (string)($result['message'] ?? 'erro retornado pelo WHMCS.'));
                    }
                }
                $whmcsStatus = $paymentDone
                    ? Capsule::table('tblinvoices')->where('id', $id)->value('status')
                    : $invoice->status;
                $details = array(
                    'fatura_whmcs' => $id,
                    'cliente_whmcs' => $invoice->userid,
                    'status_whmcs' => $whmcsStatus,
                    'charge_id_local' => $chargeId,
                    'charge_id_efi' => $data['charge_id'] ?? null,
                    'status_efi' => $status,
                    'custom_id_efi' => $customId,
                    'valor_fatura_reais' => (float)$invoice->total,
                    'valor_cobranca_efi_reais' => $amount >= 0 ? $amount / 100 : null,
                    'charge_id_confere' => (int)($data['charge_id'] ?? 0) === $chargeId,
                    'fatura_id_confere' => $customId === (string)$id,
                    'valor_confere' => $amount === $expected,
                    'url_callback_efi' => $currentCallback !== '' ? $currentCallback : 'Não informada pela Efí',
                    'url_callback_esperada' => $expectedCallback,
                    'url_callback_confere' => $currentCallback !== '' ? !$callbackMismatch : null,
                    'retorno_completo_efi' => $response
                );
                $sanitize = function (&$values) use (&$sanitize) {
                    if (!is_array($values)) return;
                    foreach ($values as $key => &$value) {
                        if (preg_match('/token|secret|password|authorization|credential|api_key|client_id/i', (string)$key)) {
                            $value = '[oculto]';
                        } elseif (is_array($value)) {
                            $sanitize($value);
                        }
                    }
                    unset($value);
                };
                $sanitize($details);
                $statuses[$id] = array(
                    'text' => $paymentDone ? 'Baixa registrada no WHMCS' : ($status === 'paid' ? 'Pago na Efí' : ($status ?: 'Resposta incompleta')),
                    'can_pay' => $paidVerified && !$alreadyPaid && !$paymentDone,
                    'payment_done' => $paymentDone,
                    'whmcs_status' => $whmcsStatus,
                    'callback_text' => $currentCallback === '' ? 'URL de callback não informada pela Efí'
                        : ($callbackMismatch ? 'URL de callback divergente' : 'URL de callback correta'),
                    'can_fix_callback' => $callbackConfigured && $callbackMismatch && $identifiersMatch,
                    'debug' => $details
                );
            } catch (\Throwable $e) {
                $detail = $e->getMessage();
                if ($e instanceof GerencianetException && !empty($e->errorDescription)) {
                    $detail = is_array($e->errorDescription)
                        ? ($e->errorDescription['message'] ?? json_encode($e->errorDescription))
                        : (string)$e->errorDescription;
                }
                $detail = preg_replace('/(client_secret|client_id|authorization|bearer|token)\s*[:=]\s*[^\s,}]+/i', '$1=[oculto]', $detail);
                $detail = substr((string)$detail, 0, 500);
                $statuses[$id] = array('text' => 'Erro: ' . $detail);
                $alerts[] = 'Fatura #' . $id . ': ' . $detail;
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
        .efi-cancel-grid{display:grid;grid-template-columns:repeat(2,minmax(280px,1fr));gap:20px;margin-top:16px}
        .efi-cancel-item{border:1px solid #e1e6ee;border-radius:6px;padding:18px;background:#fafbfc}
        .efi-cancel-item form{display:block}
        .efi-cancel-item input{display:block;width:100%;max-width:320px;margin:10px 0 12px}
        @media(max-width:750px){.efi-cancel-grid{grid-template-columns:1fr}}
        .efi-box table{width:100%;border-collapse:collapse}
        .efi-box td,.efi-box th{padding:10px;border-bottom:1px solid #e4e9f0;text-align:left}
        .efi-box th{background:#f5f7fa}
    </style>';
    // Resultados de consultas são exibidos dentro da seção de pagamentos.

    echo '<details class="efi-box"><summary style="cursor:pointer;font-size:17px;font-weight:600">Cancelar boletos (ações administrativas)</summary>
      <p style="margin-top:12px">Escolha uma das opções abaixo. Cada botão atua somente sobre o campo de seu próprio quadro.</p>
      <div class="efi-cancel-grid">
      <div class="efi-cancel-item"><strong>Cancelar usando o número da fatura</strong>
        <p>Localiza a cobrança vinculada à fatura no WHMCS.</p>
        <form method="post" action="' . $url . '" onsubmit="return confirm(\'Cancelar cobrança associada à fatura informada?\')">
          <input type="hidden" name="efi_token" value="' . $csrf . '">
          <label for="efi_cancel_invoice">ID da fatura no WHMCS</label>
          <input id="efi_cancel_invoice" name="invoice_id" type="number" min="1" required placeholder="Ex.: 17241">
          <button class="btn btn-danger" type="submit">Cancelar boleto desta fatura</button>
        </form>
      </div>
      <div class="efi-cancel-item"><strong>Cancelar usando o ID da cobrança</strong>
        <p>Consulta diretamente a cobrança pelo identificador da Efí.</p>
        <form method="post" action="' . $url . '" onsubmit="return confirm(\'Cancelar a cobrança Efí informada?\')">
          <input type="hidden" name="efi_token" value="' . $csrf . '">
          <label for="efi_cancel_charge">ID da cobrança na Efí</label>
          <input id="efi_cancel_charge" name="trans_id" type="number" min="1" required placeholder="Ex.: 1080498367">
          <button class="btn btn-danger" type="submit">Cancelar esta cobrança Efí</button>
        </form>
      </div></div></details>';
    echo '<details id="efi-check-panel" class="efi-box"' . ((isset($_GET['efi_page']) || isset($_GET['efi_search'])) ? ' open' : '') . '><summary style="cursor:pointer;font-size:17px;font-weight:600">Conferir pagamentos na Efí</summary>
      <div id="efi-check-body"><p>Faturas não pagas no WHMCS. Consulte a Efí e confirme individualmente qualquer baixa antes de registrar o pagamento.</p>
      <form method="get" action="' . htmlspecialchars($ggnbwhmcsadminurl . 'addonmodules.php', ENT_QUOTES, 'UTF-8') . '">
        <input type="hidden" name="module" value="gofasgerencianet">
        <label for="efi_search">Buscar fatura pelo ID</label>
        <input id="efi_search" name="efi_search" type="number" min="1" placeholder="Ex.: 17241" value="' . $searchValue . '">
        <button class="btn btn-default" type="submit">Buscar</button>
        ' . ($searchId ? '<a class="btn btn-default" href="' . $url . '&efi_page=1">Limpar</a>' : '') . '
      </form>
      <form method="post" action="' . $url . '">
        ' . $searchHidden . '
        <input type="hidden" name="efi_token" value="' . $csrf . '">
        <input type="hidden" name="efi_page" value="' . $page . '">
        <button class="btn btn-primary" name="efi_action" value="check_all">Consultar todas desta página</button>
      </form>
      <div id="efi-check-feedback">'; 
    foreach ($alerts as $alert) echo '<div class="alert alert-info">' . htmlspecialchars($alert, ENT_QUOTES, 'UTF-8') . '</div>';
    echo '</div>';
    if (count(array_filter($statuses, function ($entry) { return !empty($entry['can_fix_callback']); }))) {
        echo '<button id="efi-fix-all" class="btn btn-warning" type="button" style="margin-top:10px">Corrigir todas as URLs da página</button>';
    }
    echo '<div style="overflow-x:auto;margin-top:15px"><table><thead><tr>
        <th>Fatura</th><th>ID Efí / Transação</th><th>Status WHMCS</th><th>Cliente</th><th>Emissão</th><th>Vencimento</th><th>Valor</th><th>Efí</th><th>Ações</th>
      </tr></thead><tbody>';
    foreach ($rows as $r) {
        $id = (int)$r->id;
        $info = $statuses[$id] ?? array('text' => 'Não consultado');
        echo '<tr data-efi-invoice="' . $id . '"><td><a target="_blank" href="' . htmlspecialchars($ggnbwhmcsadminurl . 'billing/invoice/' . $id, ENT_QUOTES, 'UTF-8') . '">#' . $id . '</a></td>
        <td>' . htmlspecialchars($efiTransactions[$id] ?? '—', ENT_QUOTES, 'UTF-8') . '</td>
        <td>' . htmlspecialchars($info['whmcs_status'] ?? $r->status, ENT_QUOTES, 'UTF-8') . '</td>
        <td>' . htmlspecialchars($clientNames[$r->userid] ?? 'Cliente #' . (int)$r->userid, ENT_QUOTES, 'UTF-8') . '</td>
        <td>' . htmlspecialchars(date('d/m/Y', strtotime($r->date)), ENT_QUOTES, 'UTF-8') . '</td>
        <td>' . htmlspecialchars(date('d/m/Y', strtotime($r->duedate)), ENT_QUOTES, 'UTF-8') . '</td>
        <td>R$ ' . number_format((float)$r->total, 2, ',', '.') . '</td>
        <td>' . htmlspecialchars($info['text'], ENT_QUOTES, 'UTF-8')
         . (!empty($info['callback_text']) ? '<div style="margin-top:4px;' . (!empty($info['can_fix_callback']) ? 'color:#b42318;font-weight:600' : '') . '">' . htmlspecialchars($info['callback_text'], ENT_QUOTES, 'UTF-8') . '</div>' : '')
         . (isset($info['debug']) ? '<details><summary style="cursor:pointer">Detalhes da Efí</summary><pre style="white-space:pre-wrap;font-size:12px;max-width:700px;overflow:auto">' . htmlspecialchars(json_encode($info['debug'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') . '</pre></details>' : '') . '</td><td>
        <form method="post" action="' . $url . '">
          <input type="hidden" name="efi_token" value="' . $csrf . '">
          <input type="hidden" name="efi_page" value="' . $page . '">
          ' . $searchHidden . '
          <input type="hidden" name="efi_invoice" value="' . $id . '">
          <button class="btn btn-default btn-sm" name="efi_action" value="check">Consultar</button>
        </form>';
        if (!empty($info['can_fix_callback'])) echo ' <form method="post" action="' . $url . '">
          <input type="hidden" name="efi_token" value="' . $csrf . '">
          <input type="hidden" name="efi_page" value="' . $page . '">
          ' . $searchHidden . '
          <input type="hidden" name="efi_invoice" value="' . $id . '">
          <button class="btn btn-warning btn-sm" name="efi_action" value="fix_callback">Corrigir URL</button>
        </form>';
        if (!empty($info['can_pay'])) echo ' <form method="post" action="' . $url . '">
          <input type="hidden" name="efi_token" value="' . $csrf . '">
          <input type="hidden" name="efi_page" value="' . $page . '">
          ' . $searchHidden . '
          <input type="hidden" name="efi_invoice" value="' . $id . '">
          <button class="btn btn-success btn-sm" name="efi_action" value="pay">Dar baixa</button>
        </form>';
        echo '</td></tr>';
    }
    if (!$rows) echo '<tr><td colspan="9">Nenhuma fatura pendente nesta página.</td></tr>';
    echo '</tbody></table></div><div style="margin-top:12px">';
    if (!$searchId && $page > 1) echo '<a class="btn btn-default" href="' . $url . '&efi_page=' . ($page - 1) . '">Anterior</a> ';
    if ($hasNext) echo '<a class="btn btn-default" href="' . $url . '&efi_page=' . ($page + 1) . '">Próxima</a>';
    echo '</div></div></details>';

    // Operações assíncronas preservam as consultas das outras faturas na tabela.
    echo <<<'HTML'
<script>
(function () {
    var panel = document.getElementById('efi-check-panel');
    if (!panel) return;
    var busy = false;
    var batchRunning = false;

    function load(form, submitter, link, fromBatch) {
        if (busy || (batchRunning && !fromBatch)) return Promise.resolve();
        var action = submitter ? submitter.value : '';
        if (action === 'pay' && !confirm('Registrar o pagamento desta fatura no WHMCS?')) return;

        var targetId = form && form.elements.efi_invoice ? Number(form.elements.efi_invoice.value) : 0;
        var single = targetId > 0 && action !== 'check_all';
        var body = document.getElementById('efi-check-body');
        var feedback = document.getElementById('efi-check-feedback');
        var controls = panel.querySelectorAll('button');
        var data = form ? new FormData(form) : null;
        if (submitter && submitter.name) data.set(submitter.name, submitter.value);
        var method = form ? form.method.toUpperCase() : 'GET';
        var address = link || form.action;
        if (method === 'GET' && data) address += (address.indexOf('?') < 0 ? '?' : '&') + new URLSearchParams(data).toString();

        busy = true;
        controls.forEach(function (b) { b.disabled = true; });
        if (feedback) feedback.textContent = action === 'pay' ? 'Registrando pagamento...' : 'Consultando Efí...';

        return fetch(address, { method: method, body: method === 'POST' ? data : null, credentials: 'same-origin' })
            .then(function (response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.text();
            })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var updated = doc.getElementById('efi-check-body');
                if (!updated) throw new Error('O servidor não retornou a listagem do módulo.');
                if (single) {
                    var currentRow = Array.from(body.querySelectorAll('tr[data-efi-invoice]'))
                        .find(function (row) { return Number(row.getAttribute('data-efi-invoice')) === targetId; });
                    var newRow = Array.from(updated.querySelectorAll('tr[data-efi-invoice]'))
                        .find(function (row) { return Number(row.getAttribute('data-efi-invoice')) === targetId; });
                    if (currentRow && newRow) currentRow.replaceWith(newRow);
                    else body.innerHTML = updated.innerHTML;
                    var resultFeedback = updated.querySelector('#efi-check-feedback');
                    var currentFeedback = document.getElementById('efi-check-feedback');
                    if (currentFeedback && resultFeedback) currentFeedback.innerHTML = resultFeedback.innerHTML;
                } else {
                    body.innerHTML = updated.innerHTML;
                }
                panel.open = true;
            })
            .catch(function (error) {
                var currentFeedback = document.getElementById('efi-check-feedback');
                if (currentFeedback) currentFeedback.textContent = 'Falha na requisição: ' + error.message;
            })
            .finally(function () {
                busy = false;
                panel.querySelectorAll('button').forEach(function (b) { b.disabled = false; });
            });
    }

    panel.addEventListener('click', function (event) {
        var bulk = event.target.closest('#efi-fix-all');
        if (!bulk || busy || batchRunning) return;
        event.preventDefault();
        batchRunning = true;
        var buttons = Array.from(panel.querySelectorAll('button[name="efi_action"][value="fix_callback"]'));
        var count = 0;
        var errors = 0;
        (async function () {
            for (var button of buttons) {
                await load(button.form, button, null, true);
                count++;
                var feedback = document.getElementById('efi-check-feedback');
                if (feedback && feedback.textContent.indexOf('Falha na requisição:') !== -1) errors++;
            }
        })().finally(function () {
            batchRunning = false;
            var feedback = document.getElementById('efi-check-feedback');
            if (feedback) {
                var summary = document.createElement('div');
                summary.className = 'alert alert-info';
                summary.textContent = 'Correção de URLs concluída: ' + count + ' cobrança(s) processada(s).' + (errors ? ' Falhas de comunicação: ' + errors + '.' : '');
                feedback.prepend(summary);
            }
            var all = panel.querySelector('#efi-fix-all');
            if (all && !panel.querySelector('button[name="efi_action"][value="fix_callback"]')) all.remove();
        });
    });

    panel.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || !panel.contains(form)) return;
        event.preventDefault();
        var submitter = event.submitter || form.querySelector('button[type="submit"], button[name="efi_action"]');
        load(form, submitter, null);
    });

    panel.addEventListener('click', function (event) {
        var link = event.target.closest('a[href]');
        if (!link || !panel.contains(link) || !/efi_page=|efi_search=/.test(link.href)) return;
        event.preventDefault();
        load(null, null, link.href);
    });
})();
</script>
HTML;

    echo '<div style="background:#f4f6f9;padding:10px 15px">Versão ' . htmlspecialchars((string)$version, ENT_QUOTES, 'UTF-8') . ' | Gofas.net</div>';
}
