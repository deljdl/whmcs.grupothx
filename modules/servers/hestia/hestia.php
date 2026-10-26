<?php
// This module sponsered by our good friends from wexcloud.com


add_hook('AdminAreaHeaderOutput', 1, function($vars)
{
    $output .= <<<HTML
    <script type="text/javascript">
    $(document).ready(function(){
        $("#modcmdbtns").append(' <button type="button" class="btn btn-danger"><i class="fa fa-shower" aria-hidden="true"></i> Botão Novo!!!!</button>');
    });
    </script>
HTML;

    return $output;
});

function hestia_ConfigOptions() {

    $configarray = array(
     "Package Name" => array( "Type" => "text", "Default" => "default"),
     "SSH Access" => array( "Type" => "yesno", "Description" => "Tick to grant access", ),
     "IP Address (optional)" => array( "Type" => "text" ),
    );
    return $configarray;

}
function hestia_CreateAccount($params) {

    $hst_hostname = $params["serverhostname"];
    $hst_port = '8083';
    $hst_username = $params["serverusername"];
    $hst_password = $params["serverpassword"];
    $hst_returncode = 'yes';

    // Dados do usuário
    $username = $params["username"];
    $password = $params["password"];
    $email = $params["clientsdetails"]["email"];
    $package = $params["configoption1"];
    $first_name = $params["clientsdetails"]["firstname"];
    $last_name = $params["clientsdetails"]["lastname"];

    // --- 1) Cria o usuário (v-add-user)
    $postvars = [
        'user' => $hst_username,
        'password' => $hst_password,
        'returncode' => $hst_returncode,
        'cmd' => 'v-add-user',
        'arg1' => $username,
        'arg2' => $password,
        'arg3' => $email,
        'arg4' => $package,
        'arg5' => $first_name,
        'arg6' => $last_name
    ];

    $postdata = http_build_query($postvars);
    $url = 'https://' . $hst_hostname . ':' . $hst_port . '/api/';
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
    $answer = curl_exec($curl);
    curl_close($curl);

    logModuleCall('hestia', 'CreateAccount-User', $postvars, $answer);

    // Se já existe, ignora (4 = E_EXISTS)
    if (trim($answer) !== '0' && trim($answer) !== '4') {
        $errorParse = hestia_parse_error($answer);
        return "Erro ao criar usuário: [$errorParse]";
    }

    // --- 2) Cria o domínio (v-add-domain)
    // --- 2) Cria o domínio (v-add-domain)
$domain = $params["domain"];
$ip = 'SEU_IP_AQUI'; // ou pode pegar via API do Hestia ou fixo

if (!empty($domain)) {
    $postvars = [
        'user' => $hst_username,
        'password' => $hst_password,
        'returncode' => $hst_returncode,
        'cmd' => 'v-add-domain',
        'arg1' => $username,
        'arg2' => $domain,
        'arg3' => $ip  // <<< ESSA LINHA ESTAVA FALTANDO
    ];

    $postdata = http_build_query($postvars);
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
    $answer = curl_exec($curl);
    $curl_error = curl_error($curl);
    curl_close($curl);

    logModuleCall('hestia', 'CreateAccount-Domain', [
        'postvars' => $postvars,
        'curl_error' => $curl_error
    ], $answer);

    if (trim($answer) !== '0' && trim($answer) !== '4') {
        $errorParse = hestia_parse_error($answer);
        return "Erro ao criar domínio: [$errorParse]";
    }
}

    return 'success';
}











function hestia_TerminateAccount($params) {

    // Execute only if there is assigned server
    if ($params["server"] == 1) {

        // Prepare variables
        $postvars = array(
          'user' => $params["serverusername"],
          'password' => $params["serverpassword"],
          'hash' => $params["serveraccesshash"],
          'cmd' => 'v-delete-user',
          'arg1' => $params["username"]
        );
        $postdata = http_build_query($postvars);

        // Delete user account
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://' . $params["serverhostname"] . ':8083/api/');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        $answer = curl_exec($curl);
    }

	logModuleCall('vesta','TerminateAccount','https://'.$params["serverhostname"].':8083/api/'.$postdata,$answer);

    if($answer == 'OK') {
        $result = "success";
    } else {
        $result = $answer;
    }

    return $result;
}

function hestia_SuspendAccount($params) {

    // Execute only if there is assigned server
    if ($params["server"] == 1) {

        // Prepare variables
        $postvars = array(
          'user' => $params["serverusername"],
          'password' => $params["serverpassword"],
          'hash' => $params["serveraccesshash"],
          'cmd' => 'v-suspend-user',
          'arg1' => $params["username"]
        );
        $postdata = http_build_query($postvars);

        // Susupend user account
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://' . $params["serverhostname"] . ':8083/api/');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        $answer = curl_exec($curl);
    }

	logModuleCall('vesta','SuspendAccount','https://'.$params["serverhostname"].':8083/api/'.$postdata,$answer);

    if($answer == 'OK') {
        $result = "success";
    } else {
        $result = $answer;
    }

    return $result;
}

function hestia_UnsuspendAccount($params) {

    // Execute only if there is assigned server
    if ($params["server"] == 1) {

        // Prepare variables
        $postvars = array(
          'user' => $params["serverusername"],
          'password' => $params["serverpassword"],
          'hash' => $params["serveraccesshash"],
          'cmd' => 'v-unsuspend-user',
          'arg1' => $params["username"]
        );
        $postdata = http_build_query($postvars);

        // Unsusupend user account
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://' . $params["serverhostname"] . ':8083/api/');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        $answer = curl_exec($curl);
    }

    logModuleCall('vesta','UnsuspendAccount','https://'.$params["serverhostname"].':8083/api/'.$postdata,$answer);

    if($answer == 'OK') {
        $result = "success";
    } else {
        $result = $answer;
    }

    return $result;
}

function hestia_ChangePassword($params) {

    // Execute only if there is assigned server
    if ($params["server"] == 1) {

        // Prepare variables
        $postvars = array(
          'user' => $params["serverusername"],
          'password' => $params["serverpassword"],
          'hash' => $params["serveraccesshash"],
          'cmd' => 'v-change-user-password',
          'arg1' => $params["username"],
          'arg2' => $params["password"]
        );
        $postdata = http_build_query($postvars);

        // Change user package
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://' . $params["serverhostname"] . ':8083/api/');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        $answer = curl_exec($curl);
    }

	logModuleCall('vesta','ChangePassword','https://'.$params["serverhostname"].':8083/api/'.$postdata,$answer);

    if($answer == 'OK') {
        $result = "success";
    } else {
        $result = $answer;
    }
    
    return $result;
}

function hestia_ChangePackage($params) {

    // Execute only if there is assigned server
    if ($params["server"] == 1) {

        // Prepare variables
        $postvars = array(
          'user' => $params["serverusername"],
          'password' => $params["serverpassword"],
          'hash' => $params["serveraccesshash"],
          'cmd' => 'v-change-user-package',
          'arg1' => $params["username"],
          'arg2' => $params["configoption1"]
        );
        $postdata = http_build_query($postvars);

        // Change user package
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://' . $params["serverhostname"] . ':8083/api/');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        $answer = curl_exec($curl);
    }

	logModuleCall('vesta','ChangePackage','https://'.$params["serverhostname"].':8083/api/'.$postdata,$answer);

    if($answer == 'OK') {
        $result = "success";
    } else {
        $result = $answer;
    }

    return $result;
}

function hestia_ClientArea($params) {

    $code = '<form action="https://'.$params["serverhostname"].':8083/login/" method="post" target="_blank">
<input type="hidden" name="user" value="'.$params["username"].'" />
<input type="hidden" name="password" value="'.$params["password"].'" />
<input type="submit" value="Login to Control Panel" />
<input type="button" value="Login to Webmail" onClick="window.open(\'http://'.$serverhostname.'/webmail\')" />
</form>';
    return $code;

}

function hestia_AdminLink($params) {

    $code = '<form action="https://'.$params["serverhostname"].':8083/login/" method="post" target="_blank">
<input type="hidden" name="user" value="'.$params["serverusername"].'" />
<input type="hidden" name="password" value="'.$params["serverpassword"].'" />
<input type="submit" value="Login to Control Panel" />
</form>';
    return $code;

}

function hestia_LoginLink($params) {

    echo "<a href=\"https://".$params["serverhostname"].":8083/login/\" target=\"_blank\" style=\"color:#cc0000\">control panel</a>";

}

function hestia_UsageUpdate($params) {

    // Prepare variables
    $postvars = array(
      'user' => $params["serverusername"],
      'password' => $params["serverpassword"],
      'hash' => $params["serveraccesshash"],
      'cmd' => 'v-list-users',
      'arg1' => 'json'
    );
    $postdata = http_build_query($postvars);

    // Get user stats
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, 'https://' . $params["serverhostname"] . ':8083/api/');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
    $answer = curl_exec($curl);

    // Decode json data
    $results = json_decode($answer, true);

    // Loop through results and update DB
    foreach ($results AS $user=>$values) {
        update_query("tblhosting",array(
          "diskusage"=>$values['U_DISK'],
          "disklimit"=>$values['DISK_QUOTA'],
          "bwusage"=>$values['U_BANDWIDTH'],
          "bwlimit"=>$values['BANDWIDTH'],
          "lastupdate"=>"now()",
        ),array("server"=>$params['serverid'], "username"=>$user));
    }

}


##########################################
function hestia_parse_error($code) {
    $errors = array(
        0  => 'Sucesso.',
        1  => 'E_ARGS - Argumentos insuficientes.',
        2  => 'E_INVALID - Objeto ou argumento inválido.',
        3  => 'E_NOTEXIST - Objeto não existe.',
        4  => 'E_EXISTS - Objeto já existe.',
        5  => 'E_SUSPENDED - Objeto já está suspenso.',
        6  => 'E_UNSUSPENDED - Objeto já está ativo.',
        7  => 'E_INUSE - Objeto está em uso por outro.',
        8  => 'E_LIMIT - Limite de pacote excedido.',
        9  => 'E_PASSWORD - Senha inválida.',
        10 => 'E_FORBIDEN - Acesso proibido.',
        11 => 'E_DISABLED - Subsistema desabilitado.',
        12 => 'E_PARSING - Configuração quebrada.',
        13 => 'E_DISK - Sem espaço em disco.',
        14 => 'E_LA - Servidor sobrecarregado.',
        15 => 'E_CONNECT - Falha de conexão.',
        16 => 'E_FTP - FTP não responde.',
        17 => 'E_DB - Banco de dados não responde.',
        18 => 'E_RDD - RRDtool falhou.',
        19 => 'E_UPDATE - Falha na atualização.',
        20 => 'E_RESTART - Falha ao reiniciar serviço.'
    );

    if (isset($errors[$code])) {
        return $errors[$code];
    } else {
        return 'Erro desconhecido: ' . $code;
    }
}

?>