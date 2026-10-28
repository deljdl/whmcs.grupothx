<?
require_once('../xpainel/load.php');

//0=Nova, 1=Enviada, 2=Erro no Envio, 3=Aguardando Liberação de envio
$horaAtual = date('G');

$envioLiberado = $horaAtual >= 8 && $horaAtual <= 23;

$conn = new mysqli(WHATSAPP_DB_HOST, WHATSAPP_DB_USERNAME, WHATSAPP_DB_PASSWORD, WHATSAPP_DB_NAME);
$conn->set_charset('utf8mb4');

if($envioLiberado)
{
	$grupothx_status = date('d/m/Y G:i:s').' : Envio liberado THX';
	$sql = "UPDATE whatsapp_enviadas SET status = 0, grupothx_status = CONCAT(grupothx_status, CHAR(13), '{$grupothx_status}') WHERE status = 3";
	$ret = $conn->query($sql);
}


$query = "SELECT * FROM whatsapp_enviadas WHERE status=0 ORDER BY enviadas_id";
$result = $conn->query($query);

$liberadasAuto = 0;

while($row = $result->fetch_array(MYSQLI_ASSOC))
{

	$grupothx_status = date('d/m/Y G:i:s')
		.' : Iniciando envio'
		.' [PID='.getmypid().']'
		.' [ID='.$row['enviadas_id'].']';

	$sql = "UPDATE whatsapp_enviadas
			SET status = 7,
				grupothx_status = CONCAT(grupothx_status, CHAR(13), '".addslashes($grupothx_status)."')
			WHERE enviadas_id = {$row['enviadas_id']}
			AND status = 0";

	$conn->query($sql);


    if($conn->affected_rows == 0)
    {
        continue;
    }


	sleep(5);

	$envio = sendWhatsAppNow($row);

	$retornoApi = addslashes(isset($envio['response_raw']) ? $envio['response_raw'] : '');

	if(isset($envio['erro']))
	{
		$envio['erro'] = addslashes($envio['erro']);
		$grupothx_status = date('d/m/Y G:i:s').' : '.$envio['erro'];

		$sql = "UPDATE whatsapp_enviadas
				SET status = 2,
					erro = '{$envio['erro']}',
					retorno_api = '{$retornoApi}',
					grupothx_status = CONCAT(grupothx_status, CHAR(13), '{$grupothx_status}')
				WHERE enviadas_id = {$row['enviadas_id']}";
	}
	else
	{
		$grupothx_status = date('d/m/Y G:i:s').' : Mensagem encaminhada à fila e será enviada em breve';

		$sql = "UPDATE whatsapp_enviadas
				SET status = 1,
					messageId='{$envio['messageId']}',
					zaapId='{$envio['zaapId']}',
					retorno_api = '{$retornoApi}',
					grupothx_status = CONCAT(grupothx_status, CHAR(13), '{$grupothx_status}')
				WHERE enviadas_id = {$row['enviadas_id']}";
	}

	$conn->query($sql);
	$liberadasAuto++;
}
$conn->close();

if(!empty($_GET['auto']))
{
	$msg = ":clock8: Liberação automática para {$liberadasAuto} mensagens ativada às ".date('d/m/Y G:i:s');

	//$message = '{"text":"'.$msg.'\n*'.APP_NAME.'* \n*Data:*['.date('d/m/Y G:i').']\n*IP:*['.$_SERVER['REMOTE_ADDR'].']\n'.$tipo.'"}';
	$message = '{"text": "*'.$msg.'*"}';
	$ch = curl_init(SLACK_WEBHOOK_URL); // Defina a URL nova em configuração privada, fora do repositório.
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$result = curl_exec($ch);
	curl_close($ch);
}

die($envioLiberado ? 'Mensagem enviada' : 'Aguardando Liberação de Envio');

function sendWhatsAppNow($row)
{
	$row['destino'] = str_replace('+55','',$row['destino']);

    if(!strstr($row['destino'], 'group'))
	{
		if(!strstr($row['destino'], '+'))
		{
			$row['destino'] = '+55'.$row['destino'];
		}
	}

	$row['msg'] =trim(preg_replace('/\t+/', '', $row['msg']));


    $instance = ZAPI_INSTANCE;
    $token = ZAPI_TOKEN;
    $url = "https://api.z-api.io/instances/{$instance}/token/{$token}";
    $erro = false;

	if($row['imagemcabecalho'] != '')
	{
		if($row['tipo_msg'] == 2)
		{
			$data = array(
				'phone' => $row['destino'],
				'message' => $row['msg'],
				'image' => $row['imagemcabecalho'],
				'linkUrl' => $row['url_arquivo'],
				"title" => $row['arquivo_local'],
				"linkDescription" => $row['tipo_arquivo'],
				"linkType" => "LARGE"
			);
			$client = curl_init("{$url}/send-image");
		}
		else
		{
			$data = array(
			'phone' => $row['destino'],
			'caption' => $row['msg'],
			'image' => $row['imagemcabecalho']);
			$client = curl_init("{$url}/send-image");
		}

		
	}
	elseif($row['code'] != '')
	{
		$data = array(
			'phone' => $row['destino'],
			'message' => $row['msg'],
			'code' => $row['code']);

			$client = curl_init("{$url}/send-button-otp"); 
	}
	elseif($row['document'] != '')
	{
		$data = array(
			'phone' => $row['destino'],
			'fileName' => $row['msg'],
			'document' => $row['document']);
			$extension = pathinfo($row['document'], PATHINFO_EXTENSION);
			$client = curl_init("{$url}/send-document/{$extension}");
	}
	else
	{
		$data = array(
			'phone' => $row['destino'],
			'message' => $row['msg'],
			'reference' => $row['enviadas_id']);

		$client = curl_init("{$url}/send-text");
	}

    $data_string = json_encode($data);

        $headers = array(
        "Content-Type: application/json",
		"Client-Token: ".ZAPI_TOKEN,
        "Content-Length: " . strlen($data_string)
    );

    curl_setopt($client, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($client, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($client, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($client, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($client, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($client, CURLOPT_TIMEOUT, 3);

    $response = curl_exec($client);

	$info = curl_getinfo($client);

	$ret = json_decode($response, true);

	$retornoApi = json_encode(array(
		'url' => curl_getinfo($client, CURLINFO_EFFECTIVE_URL),
		'http_code' => $info["http_code"],
		'response' => json_decode($response, true)
	));

	$ret['response_raw'] = $retornoApi;

	if ($info["http_code"] != 200)
	{
		return array(
			'erro' => $info["http_code"].' : '.curl_error($client),
			'response_raw' => $retornoApi
		);
	}

	if (empty($ret['messageId']))
	{
		return array(
			'erro' => 'Z-API: '.$response,
			'response_raw' => $retornoApi
		);
	}

	return $ret;
}