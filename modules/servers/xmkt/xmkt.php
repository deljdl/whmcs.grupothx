<?php
function xmkt_config() {
    $configarray = array(
    "name" => "Grupo THX - E-mail Marketing",
    "description" => 'Sistema de Envio de E-mail Marketing Grupo THX - <a target="_blank" href="http://grupothx.inf.br/site">Grupo THX - E-mail Marketing</a>',
    "version" => "1.0",
    "author" => "Grupo THX",
    "language" => "portuguese",
    "fields" => array(
        "option1" => array ("FriendlyName" => "Token", "Type" => "text", "Size" => "80", "Description" => "Token de Acesso ao sistema X-Painel", "Default" => "", )
    ));
    return $configarray;
}


function xmkt_ConfigOptions() {

	# Should return an array of the module options for each product - maximum of 24

    $configarray = array(
     "API" => array( "Type" => "text"),
	 "TOKEN" => array( "Type" => "text"),
	 "URL" => array( "Type" => "text"),
	);

	return $configarray;

}


function xmkt_AdminCustomButtonArray() {
    $buttonarray = array(
	 "Reenviar Senha" => "ReenviarSenha",
	);
	return $buttonarray;
}

function xmkt_ReenviarSenha($params) 
{	
	if(! checaLoginSenha($params['username'], $params['password']))
	{
		return 'Login e senha são obrigatórios. Use a senha q1w2e3r4 pois há uma falha na api.';	
	}
	
	   
	$command = "sendemail";
	$adminuser = "deljdl";
	$values["messagename"] = "Serviço de E-mail Marketing Instalado e Ativado - Seus Dados de Acesso";  # <------ Custom Email Template
	$values["id"] = $params['serviceid'];
 
	$results = localAPI($command,$values,$adminuser);
	
	 
	if($results['result'] == 'success')
	{
		 return 'success';
	}
		 return $results['message'];
}

function xmkt_TerminateAccount($params) 
{	
	
	$data = array(
		'token' => $params['configoption2'],
		'action' => 'xmktTerminateAccount',		
		'username' => $params['username']						 
	);


	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $params['configoption1']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FAILONERROR, true);
	curl_setopt($ch, CURLOPT_POST, true);
	if (!ini_get('safe_mode') && ini_get('open_basedir') == '') {
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	}
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	
	$pageData = curl_exec($ch);
	curl_close($ch);
	
	
	if (!$pageData) 
	{
		return curl_error($ch);
	}
	return $pageData;

}


function xmkt_SuspendAccount($params) 
{
	
	$data = array(
		'token' => $params['configoption2'],
		'action' => 'xmktSetStatusUser',				
		'username' => $params['username'],
		'status' => 0						 
	);


	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $params['configoption1']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FAILONERROR, true);
	curl_setopt($ch, CURLOPT_POST, true);
	if (!ini_get('safe_mode') && ini_get('open_basedir') == '') {
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	}
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	
	$pageData = curl_exec($ch);
	curl_close($ch);
	
	if (!$pageData) 
	{
		return curl_error($ch);
	}
	
	
	if($pageData ==  'success')
	{
		$adminuser = "deljdl";
		
		$command = "updateclientproduct";
		$values["status"] = "Suspended";
		$values["serviceid"] = $params['serviceid'];
		
		$results = localAPI($command,$values,$adminuser);
		
		$command = "sendemail";
		$values["messagename"] = "Service Suspension Notification";
		$values["id"] = $params['serviceid'];
		
		$results = localAPI($command,$values,$adminuser);
		
		if($results['result'] == 'success')
		{
			 return 'success';
		}
		
		return 'Conta Suspensa no Servidor . Erro ao Suspender Conta no WHMCS';
	}
	return $pageData;
}


function xmkt_UnsuspendAccount($params)  
{
	
	
	$data = array(
		'token' => $params['configoption2'],
		'action' => 'xmktSetStatusUser',				
		'username' => $params['username'],
		'status' => 1						 
	);


	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $params['configoption1']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FAILONERROR, true);
	curl_setopt($ch, CURLOPT_POST, true);
	if (!ini_get('safe_mode') && ini_get('open_basedir') == '') {
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	}
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	
	$pageData = curl_exec($ch);
	curl_close($ch);
	
	if (!$pageData) 
	{
		return curl_error($ch);
	}
	
	
	if($pageData ==  'success')
	{
		$adminuser = "deljdl";
		
		$command = "updateclientproduct";
		$values["status"] = "Active";
		$values["serviceid"] = $params['serviceid'];
		
		$results = localAPI($command,$values,$adminuser);
		
		$command = "sendemail";
		$values["messagename"] = "Reativação de Serviço";
		$values["id"] = $params['serviceid'];
		
		$results = localAPI($command,$values,$adminuser);
		
		if($results['result'] == 'success')
		{
			 return 'success';
		}
		
		
		
		return 'Conta Suspensa no Servidor . Erro ao Suspender Conta no WHMCS';
	}
	return $pageData;
}

function checaLoginSenha($username, $password)
{
	if($username == '' || $password != 'q1w2e3r4')
	{
		return false;
	}
	return true;
}

function xmkt_CreateAccount($params) 
{						
	if(! checaLoginSenha($params['username'], $params['password']))
	{
		return 'Login e senha são obrigatórios. Use a senha q1w2e3r4 pois há uma falha na api.';	
	}
	
	$command = "getproducts";
	$adminuser = "deljdl";
	$values["pid"] = $params['pid'];
	$results = localAPI($command,$values,$adminuser);
	
	
	
	
	
	
	
	$data = array(
		'token' => $params['configoption2'],
		'action' => 'xmktCreateUser',				
		'groupid' => $results['products']['product'][0]['name'],
		'username' => $params['username'],
		'password' => 'b69510f4f87eceadce5bf86eabcb5b4a',
		'createdate' => time(),
		'status' => 1,
		'fullname' => $params['clientsdetails']['fullname'],
		'emailaddress' => $params['clientsdetails']['email'],
		'settings' => 'a:0:{}',
		'usertimezone' => 'GMT-3:00'						 
	);


	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $params['configoption1']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FAILONERROR, true);
	curl_setopt($ch, CURLOPT_POST, true);
	if (!ini_get('safe_mode') && ini_get('open_basedir') == '') {
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	}
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	
	$pageData = curl_exec($ch);
	
	
	
	if ($pageData === false) 
	{
		return curl_error($ch);
	}
	
	
	curl_close($ch);
	if(! strstr($pageData, 'Error:'))
	{	
		$command = "sendemail";
		$adminuser = "deljdl";
		$values["messagename"] = "Serviço de E-mail Marketing Instalado e Ativado - Seus Dados de Acesso";  # <------ Custom Email Template
		$values["id"] = $params['serviceid'];
		$results = localAPI($command,$values,$adminuser);
		
		if($results['result'] == 'success')
		{
			 return 'success';
		}
		
	}
	return $pageData;

}


?>