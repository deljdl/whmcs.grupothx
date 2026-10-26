<?php

// Sistema desenvolvido pela MMHospedagem
// Powerby Maik Venancio
// Whatsapp: 62984879620
// Site: www.mmhospedagem.com.br

if (!defined('WHMCS')) {
	exit('Acesso restrito');//- Power by www.mmhospedagem.com.br
}

//////////////////////////////////////////////////////////////////////////////////////////
// Gerencia sessoes //////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////

use WHMCS\Session;

//////////////////////////////////////////////////////////////////////////////////////////
// API Carbon ////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////

use Carbon\Carbon;

//////////////////////////////////////////////////////////////////////////////////////////
// API Laravel DataBase //////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////

use WHMCS\Database\Capsule;

global $numeroLicenca;
global $resultado;
global $ARRAY;
global $phone;
global $body;
global $tipo_mensagem;

include "mmhospedagem_custom/mmhospedagem_cod_custom.php";


//Verifica a licença WHMCS
function VerificacaoLicencaWhatsapp($licensekey) {
    // -----------------------------------
    //  -- Configuration Values --
    // -----------------------------------

    // Enter the url to your WHMCS installation here
    $whmcsurl = 'http://www.mmhospedagem.com.br/painel/';
    // Must match what is specified in the MD5 Hash Verification field
    // of the licensing product that will be used with this check.
    $licensing_secret_key = 'MMHospedagemWhatsappAPISistemaAPI';
    // The number of days to wait between performing remote license checks
    $localkeydays = 15;
    // The number of days to allow failover for after local key expiry
    $allowcheckfaildays = 5;

    // -----------------------------------
    //  -- Do not edit below this line --
    // -----------------------------------

    $check_token = time() . md5(mt_rand(1000000000, 9999999999) . $licensekey);
    $checkdate = date("Ymd");
    $domain = $_SERVER['SERVER_NAME'];
    $usersip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'];
    $dirpath = dirname(__FILE__);
    $verifyfilepath = 'modules/servers/licensing/verify.php';
    $localkeyvalid = false;
    if ($localkey) {
        $localkey = str_replace("\n", '', $localkey); # Remove the line breaks
        $localdata = substr($localkey, 0, strlen($localkey) - 32); # Extract License Data
        $md5hash = substr($localkey, strlen($localkey) - 32); # Extract MD5 Hash
        if ($md5hash == md5($localdata . $licensing_secret_key)) {
            $localdata = strrev($localdata); # Reverse the string
            $md5hash = substr($localdata, 0, 32); # Extract MD5 Hash
            $localdata = substr($localdata, 32); # Extract License Data
            $localdata = base64_decode($localdata);
            $localkeyresults = unserialize($localdata);
            $originalcheckdate = $localkeyresults['checkdate'];
            if ($md5hash == md5($originalcheckdate . $licensing_secret_key)) {
                $localexpiry = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - $localkeydays, date("Y")));
                if ($originalcheckdate > $localexpiry) {
                    $localkeyvalid = true;
                    $results = $localkeyresults;
                    $validdomains = explode(',', $results['validdomain']);
                    if (!in_array($_SERVER['SERVER_NAME'], $validdomains)) {
                        $localkeyvalid = false;
                        $localkeyresults['status'] = "Invalid";
                        $results = array();
                    }
                    $validips = explode(',', $results['validip']);
                    if (!in_array($usersip, $validips)) {
                        $localkeyvalid = false;
                        $localkeyresults['status'] = "Invalid";
                        $results = array();
                    }
                    $validdirs = explode(',', $results['validdirectory']);
                    if (!in_array($dirpath, $validdirs)) {
                        $localkeyvalid = false;
                        $localkeyresults['status'] = "Invalid";
                        $results = array();
                    }
                }
            }
        }
    }
    if (!$localkeyvalid) {
        $responseCode = 0;
        $postfields = array(
            'licensekey' => $licensekey,
            'domain' => $domain,
            'ip' => $usersip,
            'dir' => $dirpath,
        );
        if ($check_token) $postfields['check_token'] = $check_token;
        $query_string = '';
        foreach ($postfields AS $k=>$v) {
            $query_string .= $k.'='.urlencode($v).'&';
        }
        if (function_exists('curl_exec')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $whmcsurl . $verifyfilepath);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $data = curl_exec($ch);
            $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        } else {
            $responseCodePattern = '/^HTTP\/\d+\.\d+\s+(\d+)/';
            $fp = @fsockopen($whmcsurl, 80, $errno, $errstr, 5);
            if ($fp) {
                $newlinefeed = "\r\n";
                $header = "POST ".$whmcsurl . $verifyfilepath . " HTTP/1.0" . $newlinefeed;
                $header .= "Host: ".$whmcsurl . $newlinefeed;
                $header .= "Content-type: application/x-www-form-urlencoded" . $newlinefeed;
                $header .= "Content-length: ".@strlen($query_string) . $newlinefeed;
                $header .= "Connection: close" . $newlinefeed . $newlinefeed;
                $header .= $query_string;
                $data = $line = '';
                @stream_set_timeout($fp, 20);
                @fputs($fp, $header);
                $status = @socket_get_status($fp);
                while (!@feof($fp)&&$status) {
                    $line = @fgets($fp, 1024);
                    $patternMatches = array();
                    if (!$responseCode
                        && preg_match($responseCodePattern, trim($line), $patternMatches)
                    ) {
                        $responseCode = (empty($patternMatches[1])) ? 0 : $patternMatches[1];
                    }
                    $data .= $line;
                    $status = @socket_get_status($fp);
                }
                @fclose ($fp);
            }
        }
        if ($responseCode != 200) {
            $localexpiry = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - ($localkeydays + $allowcheckfaildays), date("Y")));
            if ($originalcheckdate > $localexpiry) {
                $results = $localkeyresults;
            } else {
                $results = array();
                $results['status'] = "Invalid";
                $results['description'] = "Remote Check Failed";
                return $results;
            }
        } else {
            preg_match_all('/<(.*?)>([^<]+)<\/\\1>/i', $data, $matches);
            $results = array();
            foreach ($matches[1] AS $k=>$v) {
                $results[$v] = $matches[2][$k];
            }
        }
        if (!is_array($results)) {
            die("Invalid License Server Response");
        }
        if ($results['md5hash']) {
            if ($results['md5hash'] != md5($licensing_secret_key . $check_token)) {
                $results['status'] = "Invalid";
                $results['description'] = "MD5 Checksum Verification Failed";
                return $results;
            }
        }
        if ($results['status'] == "Active") {
            $results['checkdate'] = $checkdate;
            $data_encoded = serialize($results);
            $data_encoded = base64_encode($data_encoded);
            $data_encoded = md5($checkdate . $licensing_secret_key) . $data_encoded;
            $data_encoded = strrev($data_encoded);
            $data_encoded = $data_encoded . md5($data_encoded . $licensing_secret_key);
            $data_encoded = wordwrap($data_encoded, 80, "\n", true);
            $results['localkey'] = $data_encoded;
        }
    }
    unset($postfields,$data,$matches,$whmcsurl,$licensing_secret_key,$checkdate,$usersip,$localkeydays,$allowcheckfaildays,$md5hash);
    return $results;
}

function CheckLicencaWhatsapp() {

    global $numeroLicenca;

    $VerificaLicenca = VerificacaoLicencaWhatsapp($numeroLicenca);

    if($VerificaLicenca['status'] == 'Active') {
        $mensagem = "Ativo";
    }

    if($VerificaLicenca['status'] == 'Invalid') {
        $mensagem = "Invalido";
    }

    if($VerificaLicenca['status'] == 'Suspended') {
        $mensagem = "Suspenso";
    }

    return $mensagem;

}


function horaEnvioWhasapp() {

    date_default_timezone_set('America/Sao_Paulo');
    $dataWhatsapp_Send = date('Y-m-d H:i:00');

    return $dataWhatsapp_Send;

}

function verificacao_licenca_whatsapp_mmhospedagem() {

    $nome_modulo_buscado = 'Whatsapp API DEDICADO';

    foreach (WHMCS\Database\Capsule::table('mmhospedagem_whatsapp_configuracao')->WHERE('MMHospedagem_Nome_Modulo', $nome_modulo_buscado)->get() as $modulowhatsapp_configuracao) {
        $numero_da_licenca = $modulowhatsapp_configuracao->mmhospedagem_licenca;
    }

    return $numero_da_licenca;

}

$numeroLicenca = verificacao_licenca_whatsapp_mmhospedagem();

//////////////////////////////////////////////////////////////////////////////////////////
// Gera a URL da API /////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////

function mmhospedagem_whatsapp_pegarIP() {
    if (!empty( $_SERVER['HTTP_CLIENT_IP'] )) {

        $ipvisitante = $_SERVER['HTTP_CLIENT_IP'];
    }
    else {
        if (!empty( $_SERVER['HTTP_X_FORWARDED_FOR'] )) {

            $ipvisitante = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        else {
            if (!empty( $_SERVER['REMOTE_ADDR'] )) {

                $ipvisitante = $_SERVER['REMOTE_ADDR'];
            }
            else {

                $ipvisitante = $_SERVER['HTTP_CF_CONNECTING_IP'];
            }
        }
    }

    return $ipvisitante;
}

function mmhospedagem_api_whatsapp() {

    $query = "SELECT value
    FROM tblconfiguration
    WHERE setting = 'SystemURL'
    ORDER BY id DESC
    LIMIT 1";
    $result = mysql_query($query);


    try {

        while($fetch = mysql_fetch_row($result)){

            $URL_Sistema_WHMCS_MySQL = $fetch[0];

        }

    } catch (\Exception $e) {
        echo $e->getMessage();
    }

    $URL_Montado_API_Whatsapp = str_replace('https', 'http', $URL_Sistema_WHMCS_MySQL) . '/modules/addons/mmhospedagem_whatsapp/mmhospedagem_api/mmhospedagem?api_whatsapp=enviarTexto';

    return $URL_Montado_API_Whatsapp;

}

// Checa se há um token ativo. Se não, gera um novo para o atendimento

function maybe_create_session($tel_no, $create_new = NULL) {
    $phone_number = $tel_no;

    //echo 'Running session create';

    if(!empty($phone_number) && $phone_number !== 0) {

        $query = "SELECT `id` from mmhospedagem_whatsapp_sessoes WHERE `phone_number` = '$phone_number' AND `status` = 'active' AND `id` IS NOT NULL LIMIT 1;";
        $result = mysql_query($query);
        $existing_token = array_shift(mysql_fetch_assoc($result));

        //print_r($existing_token);

        if(empty( $existing_token ) && isset($create_new) && $create_new == true) {

            $token = create_token($phone_number);

            $query = "INSERT INTO mmhospedagem_whatsapp_sessoes (`id`, `phone_number`, `status`, `last_activity`) VALUES ('$token', '$phone_number', 'active', CURRENT_TIMESTAMP())";
            $result = mysql_query($query);

            if($result)
                return array('result' => true, 'token' => $token, 'existing_token' => 0);

        } else {

            if(!empty($existing_token))
                return array('result' => true, 'token' => $existing_token, 'existing_token' => 1);
        }
    }

    return array('result' => false);

}

// Renova o timestamp de ultima atividade do token

function renew_token($token) {

    $query = "UPDATE mmhospedagem_whatsapp_sessoes SET `last_activity` = CURRENT_TIMESTAMP() WHERE `id` = '$token' AND `status` = 'active'";
    $result = mysql_query($query);

    if($result)
        return true;

    return false;

}

// Expiramos sessões para enviar mensagem de encerramento de contato posteriormente (na próxima cronjob imediatamente após essa ação)

function mark_sessions_as_pending() {

    $query = "UPDATE mmhospedagem_whatsapp_sessoes SET `status` = 'expired' WHERE `last_activity` < DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND `status` = 'active'";
    $result = mysql_query($query);

    if($result)
        return true;

    return false;

}

// Finalizar tokens de atendimento pendentes
function finalize_pending_sessions() {

    $support_token_expired_msg = 'Por falta de comunicação, encerramos o atendimento. Obrigado por contar com nosso suporte!';

    $query = "SELECT * FROM mmhospedagem_whatsapp_sessoes WHERE `status` = 'expired'";
    $result = mysql_query($query);

    $number_list = array();

    $update_query = "INSERT INTO mmhospedagem_whatsapp_sessoes (`id`, `status`) VALUES ";
    while( $expired_token = mysql_fetch_assoc($result) ) {

        if(!empty($expired_token['phone_number'])) {
            $token_id = $expired_token['id'];
            $update_query .= "('$token_id','inactive') ".PHP_EOL;
            $number_list[] = array(2 => $expired_token['phone_number'], 8 => $support_token_expired_msg, 'update_token' => false);

            // Aqui se integra com a API

            $PostsWhsatapp = http_build_query(
                array(
                    'NumeroWhatsapp' => $expired_token['phone_number'],
                    'TipoMensagem' => '1', /* Use 1 para texto e 2 para Arquivo */
                    'Mensagem' => $support_token_expired_msg
                )
            );

            $MetodoEnvio = array('http' => array('method'  => 'POST', 'header'  => 'Content-type: application/x-www-form-urlencoded', 'content' => $PostsWhsatapp));
            $ConteudoXML  = stream_context_create($MetodoEnvio);
            $result = file_get_contents(mmhospedagem_api_whatsapp(), false, $ConteudoXML);

        }

    }
    $update_query .= "ON DUPLICATE KEY UPDATE `status` = 'inactive';";

    if(!empty($number_list)) {
        $update_result = mysql_query($update_query);

        return $number_list;
    }

    return array();
}

// Funções auxiliares do gerenciador de sessões
// Cria token único para cada sessão
function create_token($tel_no) {
    $unique_token = date().$tel_no;
    return md5(uniqid($unique_token, true));
}

// Trata o número de telefone a fim de padronizar as consultas no banco
function treat_phone_number($tel_no) {
    //echo $tel_no;
    //$treated_tel_no = ltrim(preg_replace('/\D/', '', $tel_no), 0);
    return $tel_no;
}

// Método seguro para checar se o token é pré-existente
function is_existing_token($token) {

    if(is_array($token) && array_key_exists('existing_token', $token) && $token['existing_token'] == 1)
        return true;

    return false;

}

// Novo sistema de hooks

require_once("mmhospedagem_classes.php");

$mmhospedagem_class = new MMHospedagem_Whatsapp();

$mmhospedagem_hooks = $mmhospedagem_class->getHooks();

foreach ($mmhospedagem_hooks as $hook) {

    add_hook($hook['hook'], 1, $hook['function'], "");

}