<?php

function templates_ConfigOptions() {

	# Should return an array of the module options for each product - maximum of 24

    $configarray = array(
     "Categoria" => array( "Type" => "dropdown", "Options" => "Ecommerce,Moda,Empresas,Agencias,Responsivos,Animais"),
	);

	return $configarray;

}
function templates_AdminCustomButtonArray() {
    $buttonarray = array(
	 "Reenviar Template" => "enviartemplate",
	);
	return $buttonarray;
}

function templates_enviartemplate($params) {
	/*$command = "sendemail";
 	$values["customsubject"] = "Prezado Cliente";
 	$values["custommessage"] = "<p>Esse é apenas um agradecimento por sempre confiar em nossos serviços. Bom Final de semana</p>";*/
	
	$parametros = http_build_query($vars);
	#send mail
	$command = 'SendEmail'; 
	$values=$vars;
	$values = array('messagename' => 'X-Painel Suspencao de Conta', 'id' => $vars['serviceid']); 
	$adminuser = $login_whmcs;	
	$results = localAPI($command,$values,$adminuser);

 
 	$result = localAPI($command, $values, 'deljdl');
	
	if($result != 'sucess')
 		die('<pre>'.print_r($result, true));
	
	return $result;	
}

function templates_CreateAccount($params) {

	return templates_enviartemplate($params);
	
	
	$command = "sendemail";
 $values["customsubject"] = "Prezado Cliente";
 $values["custommessage"] = "<p>Esse é apenas um agradecimento por sempre confiar em nossos serviços. Bom Final de semana</p>";
 $values["id"] = 139;
 
 $result = localAPI($command, $values, 'deljdl');
 
 if($result != 'sucess')
 	die('<pre>'.print_r($result, true));
	/*
	Array
(
    [accountid] => 161
    [serviceid] => 161
    [userid] => 139
    [domain] => 
    [username] => 
    [password] => WaW45v1oz2
    [packageid] => 18
    [pid] => 18
    [serverid] => 0
    [type] => other
    [producttype] => other
    [moduletype] => templates
    [configoption1] => Agencias
    [configoption2] => 
    [configoption3] => 
    [configoption4] => 
    [configoption5] => 
    [configoption6] => 
    [configoption7] => 
    [configoption8] => 
    [configoption9] => 
    [configoption10] => 
    [configoption11] => 
    [configoption12] => 
    [configoption13] => 
    [configoption14] => 
    [configoption15] => 
    [configoption16] => 
    [configoption17] => 
    [configoption18] => 
    [configoption19] => 
    [configoption20] => 
    [configoption21] => 
    [configoption22] => 
    [configoption23] => 
    [configoption24] => 
    [customfields] => Array
        (
        )

    [configoptions] => Array
        (
        )

    [clientsdetails] => Array
        (
            [userid] => 139
            [id] => 139
            [firstname] => Jardel
            [lastname] => Jacomini
            [fullname] => Jardel Jacomini
            [companyname] => Grupo THXTestes
            [email] => testes@grupothx.com.br
            [address1] => Rua Milton Ferreira Franco
            [address2] => 
            [city] => Aruja
            [fullstate] => SP
            [state] => SP
            [postcode] => 07417-160
            [countrycode] => BR
            [country] => BR
            [statecode] => SP
            [countryname] => Brazil
            [phonecc] => 55
            [phonenumber] => 1146550330
            [phonenumberformatted] => +55.1146550330
            [billingcid] => 0
            [notes] => 
            [password] => 4740de3898b698761abc98b78caaccb5:oxWMw
            [twofaenabled] => 
            [currency] => 1
            [defaultgateway] => 
            [cctype] => 
            [cclastfour] => 
            [securityqid] => 0
            [securityqans] => 
            [groupid] => 0
            [status] => Active
            [credit] => 0.00
            [taxexempt] => 
            [latefeeoveride] => 
            [overideduenotices] => 
            [separateinvoices] => 
            [disableautocc] => 
            [emailoptout] => 0
            [overrideautoclose] => 0
            [language] => 
            [lastlogin] => Date: 28/03/2015 18:13
IP Address: 186.214.120.113
Host: 186.214.120.113.static.host.gvt.net.br
            [customfields1] => on
            [customfields] => Array
                (
                    [0] => Array
                        (
                            [id] => 1
                            [value] => on
                        )

                    [1] => Array
                        (
                            [id] => 2
                            [value] => 11985691184
                        )

                    [2] => Array
                        (
                            [id] => 3
                            [value] => 
                        )

                )

            [customfields2] => 11985691184
            [customfields3] => 
        )

    [server] => 
    [serversecure] => 
    [serveraccesshash] => 
    [serverpassword] => 
    [serverusername] => 
    [serverhostname] => 
    [serverip] => 
    [action] => create
)
	*/
	
	
	

}


?>