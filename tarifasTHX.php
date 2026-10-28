<?php
require_once __DIR__ . '/configuration.php';


//Laravel DataBase
use WHMCS\Database\Capsule;



function normalizaTelefone($telefone)
{
    $telefone = preg_replace('/\D/', '', (string)$telefone);

    if (substr($telefone, 0, 2) == '55') {
        $telefone = substr($telefone, 2);
    }

    return $telefone;
}

function criptX($text)
{
    return rtrim(strtr(
        base64_encode(openssl_encrypt($text, 'AES-256-CBC', CRYPT_KEY, OPENSSL_RAW_DATA, CRYPT_IV)),
        '+/',
        '-_'
    ), '=');
}


function decriptX($text)
{
    $text = strtr($text, '-_', '+/');
    $text .= str_repeat('=', (4 - strlen($text) % 4) % 4);

    return openssl_decrypt(
        base64_decode($text),
        'AES-256-CBC',
        CRYPT_KEY,
        OPENSSL_RAW_DATA,
        CRYPT_IV
    );
}

function linhaDigitavelParaCodigoBarras($linha)
{
    $linha = preg_replace('/\D/', '', $linha);

    if (strlen($linha) != 47) {
        return false;
    }

    return
        substr($linha, 0, 4) .      // Banco + moeda
        substr($linha, 32, 1) .     // DV geral
        substr($linha, 33, 14) .    // Fator + valor
        substr($linha, 4, 5) .      // Campo 1
        substr($linha, 10, 10) .    // Campo 2
        substr($linha, 21, 10);     // Campo 3
}
function getBarCode($codigo, $imgStyle = 'display: block')
{
    require_once ROOT_WHMCS . '/barcode/I2of5.php';

    $codigo = linhaDigitavelParaCodigoBarras($codigo);

    if (!$codigo) {
        return false;
    }

    $cacheDir = $_SERVER['DOCUMENT_ROOT'] . '/thx/barcodes';
    $cacheUrl = rtrim(HTTP_WHMCS, '/') . '/thx/barcodes';



    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0777, true);
    }

    $file = $cacheDir . '/' . md5($codigo) . '.png';

    if (!file_exists($file)) {
        $tmp = I2of5::picture($codigo);
        copy($tmp, $file);
        $status = 'gerado';
    } else {
        $status = 'cache';
    }

    return sprintf(
		'<img class="barcode" src="%s/%s" data-cache="%s" title="Código de barras" style="%s">',
		$cacheUrl,
		basename($file),
		$status,
		$imgStyle
	);
}


function getNotaFiscal($idFatura, $class='btn-copy btn-nota', $style = 'display: block')
{
    $arquivo = md5(NOTAS_TOKEN . $idFatura) . '.pdf';

    $path = ROOT_WHMCS . '/thx/notasfiscais/' . $arquivo;

    if (!file_exists($path)) {
        return '';
    }

    $url = rtrim(HTTP_WHMCS, '/') . '/thx/notasfiscais/' . $arquivo;

    return sprintf(
        '<a href="%s" target="_blank" title="Baixar Nota Fiscal" class="%s" style="%s">📄 Baixar Nota Fiscal</a>',
        $url,
        $class,
        $style
    );
}

#Funcões de manipulação de contas direto no WHM

function getAuthHeader($server)
{
	$accesshash = decryptPassword($server->accesshash);
    return array(
        'Authorization: WHM '.$server->username.':'.$accesshash
    );
}

// function getEmailsCpanel($hospedagem, $server)
// {
// 		$params = array(
// 		'cpanel_jsonapi_user'       => $hospedagem['username'],
// 		'cpanel_jsonapi_apiversion' => 2,
// 		'cpanel_jsonapi_module'     => 'Email',
// 		'cpanel_jsonapi_func'       => 'listpops'
// 	);

//     return executarRequisicaoCpanel($server, $params);
// }

function getEmailsCpanel($hospedagem, $server)
{
    $params = array(
        'cpanel_jsonapi_user'       => $hospedagem['username'],
        'cpanel_jsonapi_apiversion' => 3,
        'cpanel_jsonapi_module'     => 'Email',
        'cpanel_jsonapi_func'       => 'list_pops_with_disk',
        'get_restrictions'          => 1
    );

    return executarRequisicaoCpanel($server, $params);
}

function decryptPassword($password)
{
    $resultado = localAPI('DecryptPassword', array(
        'password2' => (string) $password
    ), false);

    if (is_array($resultado) && isset($resultado['result']) && $resultado['result'] === 'success' && isset($resultado['password'])) {
        return (string) $resultado['password'];
    }

    return $password;
}


function executarRequisicaoCpanel($server, $params)
{
    $url = 'https://' . $server->hostname . ':2087/json-api/cpanel';
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url . '?' . http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER => getAuthHeader($server),
        CURLOPT_TIMEOUT => 60
    ));

    $raw = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if($error) {
        return array('error_msg' => "Erro de Conexão cURL: " . $error, 'raw' => $raw, 'params' => $params);
    }

    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return array('error_msg' => "Resposta inválida do servidor (Não é JSON)", 'raw' => $raw, 'params' => $params);
    }

    $resultado = array('params' => $params, 'retorno' => $decoded, 'raw' => $raw);

    return $resultado;
}

function executarRequisicaoWHM($server, $params)
{
    $url = 'https://' . $server->hostname . ':2087/json-api/modifyacct';

    $ch = curl_init();

    curl_setopt_array($ch, array(
        CURLOPT_URL => $url . '?' . http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER => getAuthHeader($server),
        CURLOPT_TIMEOUT => 60
    ));

    $raw = curl_exec($ch);
    $error = curl_error($ch);

    curl_close($ch);

    if($error)
    {
        return array(
            'error_msg' => 'Erro de Conexão cURL: ' . $error,
            'raw' => $raw,
            'params' => $params
        );
    }

    $decoded = json_decode($raw, true);

    if(json_last_error() !== JSON_ERROR_NONE)
    {
        return array(
            'error_msg' => 'Resposta inválida do servidor (Não é JSON)',
            'raw' => $raw,
            'params' => $params
        );
    }

    return array(
        'params' => $params,
        'retorno' => $decoded,
        'raw' => $raw
    );
}

function getLoggedAdmin()
{
    if (empty($_SESSION['adminid']))
    {
        http_response_code(403);

        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            header('Content-Type: application/json');

            echo json_encode(array(
                'success' => false,
                'message' => 'Sessão administrativa inválida.'
            ));
        }
        else
        {
            die('Sessão administrativa inválida.');
        }

        exit;
    }

    $admin = Capsule::table('tbladmins')
        ->where('id', $_SESSION['adminid'])
        ->first();

    if (!$admin)
    {
        http_response_code(403);

        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            header('Content-Type: application/json');

            echo json_encode(array(
                'success' => false,
                'message' => 'Administrador não encontrado.'
            ));
        }
        else
        {
            die('Administrador não encontrado.');
        }

        exit;
    }

    return $admin;
}
function executarRequisicaoWHMPHPFPM($server, $acao, $dados)
{
    $url = 'https://' . $server->hostname . ':2087/json-api/' . $acao;

    $headers = getAuthHeader($server);

    // Remove eventual Content-Type definido pela função de autenticação.
    $headers = array_filter($headers, function($header) {
        return stripos($header, 'Content-Type:') !== 0;
    });

    $headers[] = 'Content-Type: application/json';

    $dados['api.version'] = 1;

	$json = json_encode($dados);

    $ch = curl_init();

    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $json,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 60
    ));

    $raw = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    return array(
        'success' => empty($error),
        'http_code' => $httpCode,
        'curl_error' => $error,
        'url' => $url,
        'request' => $dados,
        'raw' => $raw,
        'retorno' => json_decode($raw, true)
    );
}


function reportarErro($mensagem)
{
    error_log('[WHMCS] ' . $mensagem);

    @error_log(
        $mensagem,
        1,
        'deljdl@gmail.com'
    );
}

function alterarQuotaWHM($server, $usuarioCpanel, $gb)
{
    $quota = ($gb < 1000) ? $gb * 1000 : $gb;

	$url = 'https://' . $server->hostname .
		':2087/json-api/editquota?api.version=1'
		. '&user=' . urlencode($usuarioCpanel)
		. '&quota=' . $quota;

	$ch = curl_init($url);

	curl_setopt_array($ch, array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => false,
		CURLOPT_HTTPHEADER => getAuthHeader($server),
		CURLOPT_TIMEOUT => 60
	));

    $retorno = curl_exec($ch);

	if (curl_errno($ch))
	{
		$erro = curl_error($ch);

		logModuleCall(
			'DiskQuota',
			'editquota',
			array(
				'url'   => $url,
				'user'  => $usuarioCpanel,
				'quota' => $quota
			),
			$erro
		);

		curl_close($ch);

		return array(
			'success' => false,
			'message' => $erro
		);
	}

	curl_close($ch);

	$json = json_decode($retorno, true);

	logModuleCall(
		'DiskQuota',
		'editquota',
		array(
			'url'   => $url,
			'user'  => $usuarioCpanel,
			'quota' => $quota
		),
		$retorno
	);

    if (
        isset($json['metadata']['result']) &&
        $json['metadata']['result'] == 1
    ) {
        return array(
            'success' => true
        );
    }

    return array(
        'success' => false,
        'message' => isset($json['metadata']['reason'])
            ? $json['metadata']['reason']
            : $retorno
    );
}

function consultarQuotaWHM($server, $usuarioCpanel)
{
    $url = 'https://' . $server->hostname .
        ':2087/json-api/accountsummary?api.version=1'
        . '&user=' . urlencode($usuarioCpanel);

    $ch = curl_init($url);

    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER     => getAuthHeader($server),
        CURLOPT_TIMEOUT        => 60
    ));

    $retorno = curl_exec($ch);

    if (curl_errno($ch))
    {
        $erro = curl_error($ch);

        logModuleCall(
            'DiskQuota',
            'accountsummary',
            array(
                'url'  => $url,
                'user' => $usuarioCpanel
            ),
            $erro
        );

        curl_close($ch);

        return array(
            'success' => false,
            'message' => $erro
        );
    }

    curl_close($ch);

    $json = json_decode($retorno, true);

    logModuleCall(
        'DiskQuota',
        'accountsummary',
        array(
            'url'  => $url,
            'user' => $usuarioCpanel
        ),
        $retorno
    );

    if (
        isset($json['metadata']['result']) &&
        $json['metadata']['result'] == 1 &&
        !empty($json['data']['acct'][0])
    ) {
        $dados = $json['data']['acct'][0];

        $diskusedMB  = (int)str_replace('M', '', $dados['diskused']);
        $disklimitMB = (int)str_replace('M', '', $dados['disklimit']);

        return array(
            'success' => true,

            // Valores originais retornados pela API
            'diskused'  => $dados['diskused'],
            'disklimit' => $dados['disklimit'],

            // Valores numéricos
            'diskused_mb'  => $diskusedMB,
            'disklimit_mb' => $disklimitMB,

            // Valores em GB
            'diskused_gb'  => round($diskusedMB / 1000, 2),
            'disklimit_gb' => round($disklimitMB / 1000, 2),

            // Percentual
            'percentual' => ($disklimitMB > 0)
                ? round(($diskusedMB / $disklimitMB) * 100)
                : 0,

            // Dados completos da API
            'dados' => $dados
        );
    }

    return array(
        'success' => false,
        'message' => isset($json['metadata']['reason'])
            ? $json['metadata']['reason']
            : $retorno
    );
}
#Funcões de manipulação de contas direto no WHM / Fim


function getUrlCurta($url, $title = '', $description = '',  $hash = '', $img = '')
{
	$curl = curl_init();

	curl_setopt_array($curl, array(
	CURLOPT_URL => 'https://l.grupothx.com.br/api/shortlinks',
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => '',
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 0,
	CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => 'POST',
	CURLOPT_POSTFIELDS =>'{
		"url": "'.$url.'",
		"title": "'.$title.'",
		"img": "'.$img.'",
		"description": "'.$description.'",
		"hash": "'.$hash.'"
	}',
	CURLOPT_HTTPHEADER => array(
		'Content-Type: application/json',
		'token: '.TOKEN_SHORT_LINKS
	),
	));

	$response = curl_exec($curl);
	curl_close($curl);

	$response = json_decode($response);

	if(isset($response->erro))
	{
		return $url;
	}

	return $response->url;
}

##retirados do hook whatsapp
function getDadosInvoiceById($id)
{

	$ret = localAPI( 'GetInvoice', array('invoiceid' => $id), false);
	logModuleCall('/tarifasTHX.php', __FUNCTION__ , $ret, '', '');
	return $ret;
}


function getClientContacts($clientId)
{
    $ret = Capsule::select("
        SELECT *
        FROM tblcontacts
        WHERE userid = " . (int)$clientId
    );

    return $ret ? json_decode(json_encode($ret), true) : [];
}


function geraComandoNFSE(
	$invoiceId,
	$invoice = array(),
	$dadosCliente = array(),
	$invoiceItens = array()
)
{

	$invoiceId =
		(int)
		$invoiceId;

	if(empty($invoiceId))
	{
		return false;
	}


	if(empty($invoice))
	{
		$invoice =
			getDadosInvoiceById(
				$invoiceId
			);
	}

	if(
		empty($invoice)
		|| empty($invoice['userid'])
	)
	{
		return false;
	}



	if(empty($dadosCliente))
	{
		$dadosCliente =
			getDadosClienteById(
				$invoice['userid']
			);
	}

	if(empty($dadosCliente))
	{
		return false;
	}

	if(empty($invoiceItens))
	{
		$invoiceItens =
			Capsule::select(
				"SELECT *
				FROM tblinvoiceitems
				WHERE invoiceid = {$invoiceId}"
			);
	}

	$descricaoServico =
		'Itens da Cobrança '.
		$invoiceId.
		"\n";

	foreach($invoiceItens as $item)
	{
		$descricaoItem =
			trim(
				strip_tags(
					$item->description
				)
			);

		// remove excesso de espaços/quebras
		$descricaoItem =
			preg_replace(
				'/\s+/',
				' ',
				$descricaoItem
			);

		$descricaoServico .=
			$descricaoItem.
			' - R$ '.
			moeda2(
				$item->amount
			).
			"\n";
	}

	$descricaoServico .=
		"\nTotal: R$ ".
		moeda2(
			$invoice['total']
		);

	$logradouro =
	!empty(
		$dadosCliente['address1']
	)
	?
	$dadosCliente['address1']
	:
	'';


	$numero = '';

	if(
		strpos(
			$dadosCliente['address1'],
			','
		) !== false
	)
	{
		$posicaoVirgula =
			strrpos(
				$dadosCliente['address1'],
				','
			);

		$logradouro =
			trim(
				substr(
					$dadosCliente['address1'],
					0,
					$posicaoVirgula
				)
			);

		$numero =
			trim(
				substr(
					$dadosCliente['address1'],
					$posicaoVirgula + 1
				)
			);
	}

	$jsonNota = json_encode(
		array(

			'simples_nacional' =>
				array(
					'regime_apuracao' => '1',
					'aliquota' => 4
				),

			'tomador' =>
				array(
					'nome' =>
						$dadosCliente['firstname'],

					'empresa' =>
						$dadosCliente['companyname'],

					'cpf_cnpj' =>
						soNumeros(
							$dadosCliente['customfields3']
						),

					'telefone' =>
						soNumeros(
							$dadosCliente['phonenumber']
						),

					'email' =>
						$dadosCliente['email'],

					'endereco' =>
						array(
							'cep' =>
								soNumeros(
									$dadosCliente['postcode']
								),

							'logradouro' =>
								$logradouro,

							'numero' =>
							!empty($numero)
							? $numero
							: 'S/N',

							'bairro' =>
								$dadosCliente['address2'],

							'estado' =>
								$dadosCliente['state'],

							'cidade' =>
								$dadosCliente['city'],

							'complemento' =>
								''
						)
				),

			'servico' =>
				array(
					'descricao' =>
						str_replace(
							array(
								"\r\n",
								"\r",
								"\n"
							),
							'\n',
							$descricaoServico
						),

					'valor' =>
						(float)
						$invoice['total']
				),

			'fatura' =>
				array(
					'numero' =>
						$invoiceId,

					'vencimento' =>
						$invoice['duedate'],

					'link_visualizacao' =>
						getUrlCurta(
							'https://grupothx.com/viewinvoice?i='.
							base64_encode(
								$invoiceId
							)
						)
				),

			'issqn' =>
				array(
					'caso_especial' =>
						false
				)

		), JSON_UNESCAPED_UNICODE
	);

	return
	'NFSE.start(JSON.parse(new TextDecoder().decode(Uint8Array.from(atob("' .
	base64_encode(
		$jsonNota
	) .
	'"), c => c.charCodeAt(0)))))';
}

function botaoNFSE($invoiceId)
{
	$comando = geraComandoNFSE($invoiceId);

	if(!$comando){
		return '';
	}

	$comando = json_encode($comando);

	return '
	<a
		class="btn btn-warning"
		onclick=\'
			var comando = '.$comando.';

			navigator.clipboard.writeText(comando);

			window.open(
				"https://www.nfse.gov.br/EmissorNacional/DPS/Pessoas?auto_nfse=1",
				"_blank"
			);

			return false;
		\'
		title="Gerar NFS-e '.$invoiceId.'"
	>
		<i class="fas fa-bolt"></i>
		Gerar NFS-e
	</a>';
}



function getDadosClienteById($id)
{
	$ret = localAPI( 'GetClientsDetails', array('clientid' => $id), false);
	logModuleCall('/tarifasTHX.php', __FUNCTION__ , $ret, '', '');
	return $ret;
}

function getDadosHospedagemById($id)
{
    $ret = localAPI('GetClientsProducts', ['serviceid' => $id], false);
    logModuleCall('/tarifasTHX.php', __FUNCTION__, ['serviceid' => $id], $ret, '', []);

    return isset($ret['products']['product'][0]) ? $ret['products']['product'][0] : [];
}
function getDadosTicketById($id)
{
	$ret = localAPI( 'GetTicket', array('ticketid' => $id), false);
	logModuleCall('/tarifasTHX.php', __FUNCTION__ , $ret, '', '');
	return $ret;
}



function getEmailTemplate($name, $vars)
{
	$assunto = '-';
	$html = '--';

	foreach( Capsule::table('tblemailtemplates') -> where('name', '=', $name) -> get() as $template )
	{
			$assunto = $template->subject;
			$html = $template->message;
	}

	logModuleCall('/tarifasTHX.php', __FUNCTION__ , $vars, $assunto, $html);
	return $html;
}

function replaceVars($str, $variaveis)
{
    logModuleCall('/tarifasTHX.php', __FUNCTION__, $variaveis, $variaveis, $str);

    if (isset($variaveis['mergefields'])) {
        foreach ($variaveis['mergefields'] as $key => $valor) {
            if (is_scalar($valor) || $valor === null) {
                $str = str_replace('{$' . $key . '}', (string) $valor, $str);
            }
        }

        return $str;
    }

    return $str;
}
##fim hoohk whatsapp
function soNumeros($str)
{
	return preg_replace("/[^0-9]/", "", $str);
}

function setDescontoAvista($valor)
{
	try
	{
		$valor -= 2.5;
		return @number_format($valor, 2, '.', '');
	}
	catch( Exception $e )
	{
		X::sendErrors($e->getMessage(), __CLASS__.'>'.__FUNCTION__.'>'.__LINE__);
	}
}

function moeda2($valor,$separador='.')
{
	try
	{
		return @number_format($valor, 2, $separador, '');
	}
	catch( Exception $e )
	{
		X::sendErrors($e->getMessage(), __CLASS__.'>'.__FUNCTION__.'>'.__LINE__);
	}
}

function checkJuros($valor, $vencimento)
{
	$ret = array('valor' => $valor, 'multa' => 0, 'juros' => 0, 'dias_atrasados' => 0);


	$diferenca = strtotime(date('Y-m-d')) - strtotime($vencimento);
    $ret['dias_atrasados'] = (int)floor( $diferenca / (60 * 60 * 24));

    if($ret['dias_atrasados'] > LIMITE_ATRAZO)
    {
		$ret['multa'] = $valor * PERCENTUAL_MULTA;
		$ret['juros'] = $valor * (PERCENTUAL_JUROS * $ret['dias_atrasados']);
		$ret['valor'] += ($ret['multa'] + $ret['juros']);
		$ret['valor'] = round($ret['valor'], 2);
		$ret['multa'] = round($ret['multa'], 2);
		$ret['juros'] = round($ret['juros'], 2);
		$ret['dias_atrasados'] = ($ret['dias_atrasados']);
	}
	else
	{
		$ret['dias_atrasados'] = 0;
	}


	//echo $ret['dias_atrasados'];

	return $ret;


}

function formataData($data)
{
	$padrao = '%d de %B de %Y';
	return strftime($padrao, strtotime($data));
}


function alexa($msg)
{
	$msg = urlencode($msg);
	my_file_get_contents('https://alexa.grupothx.com.br/send-voice?message='.$msg);
}


function criaTarefaKamban($tarefa, $prazo = '1 hora', $cliente = 'WHMCS')
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://kamban.grupothx.com.br/addtarefa/'.urlencode($tarefa).'/'.urlencode($prazo).'/'.urlencode($cliente).'/'.urlencode('https://grupothx.com.br/whmcs-ico.png'));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	$response = curl_exec($ch);
	curl_close($ch);
}

function my_file_get_contents( $site_url ){
	$ch = curl_init();
	$timeout = 5; // set to zero for no timeout
	curl_setopt ($ch, CURLOPT_URL, $site_url);
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	ob_start();
	curl_exec($ch);
	curl_close($ch);
	$file_contents = ob_get_contents();
	ob_end_clean();
	return $file_contents;
}

//?TOKEN_CIELO=hTHXgTFRDE4321EGrupoDERFRE321lL
$formasdepagamento = array(
'deposito_ou_transferencia' => array('D','Depósito ou Transferência', 0,0, 0, 'N', 'success','deposito_ou_transferencia'),
'gofasgerencianetboleto' => array('G','Gerencianet Boleto',TAXA_GERENCIANET, 0, 0, 'G', 'danger', 'gofasgerencianetboleto')
);

function logoffX()
{
	#LOGOFF DE SEGURANCA RECOLOCAR ASSIMQUE PUDER
	 if(isset($_SESSION))
	 {
	 	unset($_SESSION);
	 	session_destroy();
	 }
}
function logoffSecurity()
{
	if(isset($_GET['nowlogin']))
	{
		return logoffX();
	}
	if(! isset($_SERVER['HTTP_REFERER'][1]))
	{
		return logoffX();
	}
	$referer = parse_url($_SERVER['HTTP_REFERER']);
	if($referer['host'] != DOMINIO_WHMCS)
	{
		return logoffX();
	}
	return;
}
function tiracento($texto)
{
    $trocarIsso = array('à','á','â','ã','ä','å','ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ù','ü','ú','ÿ','À','Á','Â','Ã','Ä','Å','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ñ','Ò','Ó','Ô','Õ','Ö','O','Ù','Ü','Ú','Ÿ');
    $porIsso = array('a','a','a','a','a','a','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','u','u','u','y','A','A','A','A','A','A','C','E','E','E','E','I','I','I','I','N','O','O','O','O','O','o','U','U','U','Y');
    $titletext = str_replace($trocarIsso, $porIsso, $texto);
    return $titletext;
}

function enviaEmailTHX($template, $id)
{
	    $command = 'SendEmail';
        $values=$vars;
       	$values = array('messagename' => $template, 'id' => $id);
        $adminuser = 'deljdl';
        $results = localAPI($command,$values,$adminuser);
        if(isset($results['result']) && $results['result'] == 'success')
        {
            return true;
        }
        return false;
}

function getDadosBoletoHtmlTHX($invoiceId, $html = false)
{
	return;
	$ret = '';

	foreach (Capsule::table('gofasgerencianetboleto')->where("invoice_id", '=', $invoiceId)->get() as $gorfas)
	{
		if($html)
		{
			$ret='
			<br />Linha Digitável do Boleto
			<br /><strong>'.$gorfas->barcode.'</strong>
			<br /><br />
			Vencimento do Boleto
			<br />
			<strong>'.$gorfas->expire_at.'</strong>
			<br /><br />
			<a href="'.$gorfas->link.'"><strong>Imprimir Boleto</strong></a>
			<br />
			Caso seu programa de email bloqueie o link acima, copie o endereço abaixo:
			<br />'.$gorfas->link.'<br />';
		}
		else
		{
			$ret=
'Linha Digitável do Boleto
*'.$gorfas->barcode.'*


Vencimento do Boleto
*'.$gorfas->expire_at.'*


Imprimir Boleto
*'.$gorfas->link.'*';
		}
	}

	return $ret;
}

function enviaWhatsApp($whatsNumber , $msg, $imagem = '', $code = '', $document = '', $tipo_msg = 1, $title = '', $linkDescription = '', $linkUrl = '')
{
	if($whatsNumber == '')
	{
		return false;
	}

	if($whatsNumber == WHATSAPP_ADMIN_JARDEL)
	{
		logSlack($msg.'\nImagem:'.$imagem, 'Nova mensagem whatsapp desviada para o SLACK');
		return true;
	}

	preg_match_all('!\d+!', $whatsNumber, $matches);
	$whatsNumber = implode('',$matches[0]);
	$msg =strip_tags($msg);


	$ch = curl_init('https://grupothx.com/atendimento-online/apiTHX/bot/');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS,
	[
		'tk' => 'tgftRFDEtTHHHHX3232WQWaswq123XpaInelNova',
		'function' => 'sendWhatsApp',
		'whatsnumber' => $whatsNumber,
		'mensagem' => $msg,
		'imagemcabecalho' => $imagem,
		'tipo_msg' => $tipo_msg,
		'code' => $code,
		'document' => $document,
		'arquivo_local' => $title,
		'tipo_arquivo' => $linkDescription,
		'url_arquivo' => $linkUrl
	]);
	curl_setopt($ch, CURLOPT_USERPWD, 'username'.':'.'senha');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	$resultado = curl_exec($ch);
	//die(var_dump($resultado));
	$resultado = json_decode($resultado);
	
	curl_close ($ch);
	return true;

	logModuleCall('/tarifasTHX.php', __FUNCTION__ , $sql, '', $sql);

	logModuleCall('/enviaWhatsApp', __FUNCTION__, $whatsNumber, '', $msg);
	return $ret ? "Mensagem salva para envio\n\nWhatsApp:\n{$whatsNumber}\n\nMensagem:\n{$msg}" : false;
}

function logSlack($msg, $assunto = '')
{
		$message = '{"text": "*'.$assunto.'* ```'.$msg.'```"}';
		$ch = curl_init(URL_LOG_SLACK);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
}


function enviaLogTHX($custommessage)
{
	return logSlack($custommessage, 'enviaLogTHX');
}


function enviaLogTHXJdl($msg)
{
	return logSlack($msg, "Notificação Grupo THX Log");
	$command = "sendadminemail";
	 $adminuser = "deljdl"; // nome de usuário do Admin
	 $values["messagename"] = "Mention Notification";
	 $values["custommessage"] = $msg;
	 $values["customsubject"] = "Notificação Grupo THX Log :: ".date('d/m/Y G:s');
	 $results = localAPI($command,$values,$adminuser);
	 return $results;
}

function sendErrorsToAdmin($msg, $subject ='Houve um erro no sistema')
{
	if (!is_string($msg)) {
        $msg = print_r($msg, true);
    }

	$command = "sendadminemail";
	$adminuser = "deljdl"; // nome de usuário do Admin
	$values["messagename"] = "Mention Notification";
	$values["custommessage"] = $msg;
	$values["customsubject"] = $subject;
	$results = localAPI($command,$values,$adminuser);

	criaTarefaKamban($msg, '1 hora', $subject);
	alexa($subject);

	return $results;
}

function logGateways($dados, $path = false, $filename = false){


	if(! $path)
	{
		$path = date('d-m-Y');
	}

	if(! $filename)
	{
		$filename = 'log.txt';
	}

	$dirToLog = ROOT_WHMCS.'thx/logs-gateways/'.$path;

	if(! is_dir($dirToLog))
	{
		mkdir($dirToLog);
	}

	$quebra = chr(13).chr(10);
    $cabecalho = $quebra.date('d/m/Y G:s:i').$quebra;
    $fim = $quebra.$quebra.$quebra;
    $dados = $cabecalho.'<pre>'.print_r($dados,1).'</pre>'.$fim;
	$dados.= '<hr />POST<pre>'.print_r($_POST,1).'</pre>'.$fim;
	$dados.= '<hr />GET<pre>'.print_r($_GET,1).'</pre>'.$fim;
    $abre = fopen($dirToLog.'/'.$filename, 'a');
    $escreve = fwrite($abre, $quebra.$dados);
    fclose($abre);
}

function logGrupoTHX($dados, $path, $filename){
	$quebra = chr(13).chr(10);
    $cabecalho = $quebra.date('d/m/Y G:s:i').$quebra;
    $fim = $quebra.$quebra.$quebra;
    $dados = $cabecalho.'<pre>'.print_r($dados,1).'</pre>'.$fim;
    $abre = fopen(ROOT_WHMCS.'thx/logs-gateways-todos/'.$path.'/'.$filename, 'a');
    $escreve = fwrite($abre, $quebra.$dados);
    fclose($abre);
}


function criaNovaFatura($userid, $descricao, $valor, $diasParaVencimento = 1, $avisaAgoraPorEmail = '1')
{
	logSlack("$userid, $descricao, $valor, $diasParaVencimento, $avisaAgoraPorEmail", 'criaNovaFatura WHMCS');

	$command = 'CreateInvoice';
	$postData = array(
	    'userid' => $userid,
	    'status' => 'Unpaid',
	    'sendinvoice' => $avisaAgoraPorEmail,
	    'paymentmethod' => '',
	    'taxrate' => '',
	    'date' => date('Y-m-d'),
	    'duedate' => date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') + $diasParaVencimento, date('Y'))),
	    'itemdescription1' => $descricao,
	    'itemamount1' => $valor,
	    'itemtaxed1' => '0',
	    'autoapplycredit' => '0'
	);

	logSlack(print_r($postData, 1), 'criaNovaFatura WHMCS postData');


	$adminUsername = ADMIN_THX; // Optional for WHMCS 7.2 and later

	$result = localAPI($command, $postData, $adminUsername);


	if(isset($result['invoiceid']))
	{
		##############################Tentando atualizar o valor
		$command = 'UpdateInvoice';
		$postData = array(
			'invoiceid' => $result['invoiceid'],
			'itemamount' => array(1 => $valor),
		);

		$results = localAPI($command, $postData, $adminUsername);
		logSlack(print_r($postData, 1), 'UpdateInvoice WHMCS postData');
		logSlack(print_r($results, 1), 'UpdateInvoice WHMCS results');

	}
	##############################TEntando atualizar o valor fim

	// Array
	// (
	//     [result] => success
	//     [invoiceid] => 7044
	//     [status] => Unpaid
	// )

	return isset($result['invoiceid']) ? $result['invoiceid'] : 0;
}

function checkLoginWHMCS()
{
    if(empty($_SESSION['adminid']) || $_SESSION['adminid'] != 1)
    {
        http_response_code(403);
        die('Acesso negado');
    }

	//chamada e includes
	//if(!function_exists('checkLoginWHMCS')) die('Acesso negado'); checkLoginWHMCS();
}