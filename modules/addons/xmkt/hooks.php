<?php
/**
 * Addon Module Sample Hook File
 *
 * This is a demo hook file for an addon module. Addon Modules can utilise all of the WHMCS
 * hooks in exactly the same way as a normal hook file would, and can contain multiple hooks.
 *
 * For more info, please refer to the hooks documentation @ http://docs.whmcs.com/Hooks
 *
 * @package    WHMCS
 * @author     WHMCS Limited <development@whmcs.com>
 * @copyright  Copyright (c) WHMCS Limited 2005-2013
 * @license    http://www.whmcs.com/license/ WHMCS Eula
 * @version    $Id$
 * @link       http://www.whmcs.com/
 */

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");


define('TOKEN_MKT', 'Thayna-Papai-Jardel-Pra-Sempre-85691184');
function xmkt_hook_login($vars) {
    // Your code goes here
}

// Define Client Login Hook Call
add_hook("ClientLogin",1,"xmkt_hook_login");

function xmkt_hook_logout($vars) {
    // Your code goes here
}

// Define Client Logout Hook Call
add_hook("ClientLogout",1,"xmkt_hook_logout");

function xmkt_SuspendAccount($vars) {
	return 'success';
    $parametros = http_build_query($vars);
	#send mail
	$command = 'SendEmail'; 
	$values=$vars;
	$values = array('messagename' => 'X-Painel Suspencao de Conta', 'id' => $vars['serviceid']); 
	$adminuser = $login_whmcs;	
	$results = localAPI($command,$values,$adminuser); 

	$retorno = file_get_contents('http://xpainel.com.br/ativaClientes.php?token='.TOKEN_MKT.'&'.$parametros);
    return $retorno;
}
add_hook("ClientLogin",1,"xpaneil_SuspendAccount");

function xmkt_UnsuspendAccount($vars){
	return 'success';
	$parametros = http_build_query($vars);
	#send mail
	$command = 'SendEmail'; 
	$values=$vars;
	$values = array('messagename' => 'X-Painel Rativacao de Conta', 'id' => $vars['serviceid']); 
	$adminuser = $login_whmcs;	
	$results = localAPI($command,$values,$adminuser);
	$retorno = file_get_contents('http://xpainel.com.br/ativaClientes.php?token='.TOKEN_MKT.'&'.$parametros);
    return $retorno;
}
add_hook("ClientLogin",1,"xmkt_UnsuspendAccount");


function xmkt_TerminateAccount($vars) {
    $parametros = http_build_query($vars);
	#send mail
	$command = 'SendEmail'; 
	$values=$vars;
	$values = array('messagename' => 'X-Painel Finalizacao de Conta', 'id' => $vars['serviceid']); 
	$adminuser = $login_whmcs;	
	$results = localAPI($command,$values,$adminuser);
	$retorno = file_get_contents('http://xpainel.com.br/ativaClientes.php?token='.TOKEN_MKT.'&'.$parametros);
    return $retorno;
}
add_hook("ClientLogin",1,"xmkt_TerminateAccount");

function xmkt_CreateAccount($vars) {	
    $parametros = http_build_query($vars);
 
	#send mail
	$command = 'SendEmail'; 
	$values=$vars;
	$values = array('messagename' => 'X-Painel Nova Conta', 'id' => $vars['serviceid']); 
	$adminuser = $login_whmcs;	
	$results = localAPI($command,$values,$adminuser); 
	
	$retorno = file_get_contents('http://xpainel.com.br/ativaClientes.php?token='.TOKEN_MKT.'&'.$parametros);
    return $retorno;
}
add_hook("ClientLogin",1,"xmkt_CreateAccount"); 

 