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

require_once('/home/thxcom/whmcs.grupothx.com.br/tarifasTHX.php');
/// /home/thxcom/public_html/thx

define('TOKEN_XPAINEL', 'Thayna-Papai-Jardel-Pra-Sempre-85691184');
function xpainel_hook_login($vars) {
    // Your code goes here
}

// Define Client Login Hook Call
add_hook("ClientLogin",1,"xpainel_hook_login");

function xpainel_hook_logout($vars) {
    // Your code goes here
}

// Define Client Logout Hook Call
add_hook("ClientLogout",1,"xpainel_hook_logout");

function xpainel_SuspendAccount($vars) {
	$urlToAction = 'https://xpainel.com.br/classes/lib/ativaClientes.php?token='.TOKEN_XPAINEL.'&action=suspend&conta_id='.$vars['customfields']['Id-X-Painel'];
	//echo '<pre>'.print_r($vars, 1).'</pre>';
	$retorno = my_file_get_contents($urlToAction);
	if($retorno == 'success')
	{
		#send mail
		$command = 'SendEmail';
		$values=$vars;
		$values = array('messagename' => 'X-Painel Suspencao de Conta', 'id' => $vars['serviceid']);
		$adminuser = $login_whmcs;
		$results = localAPI($command,$values,$adminuser);
		return $retorno;
	}

	return $retorno;
}
add_hook("ClientLogin",1,"xpainel_SuspendAccount");

function xpainel_UnsuspendAccount($vars){
	$urlToAction = 'https://xpainel.com.br/classes/lib/ativaClientes.php?token='.TOKEN_XPAINEL.'&action=unsuspend&conta_id='.$vars['customfields']['Id-X-Painel'];
	$retorno = my_file_get_contents($urlToAction);
	if($retorno == 'success')
	{
		#send mail
		$command = 'SendEmail';
	$values=$vars;
	$values = array('messagename' => 'X-Painel Rativacao de Conta', 'id' => $vars['serviceid']);
	$adminuser = $login_whmcs;
	$results = localAPI($command,$values,$adminuser);
	return $retorno;
	}

	return $retorno;
}
add_hook("ClientLogin",1,"xpainel_UnsuspendAccount");


function xpainel_TerminateAccount($vars) {
	$urlToAction = 'https://xpainel.com.br/classes/lib/ativaClientes.php?token='.TOKEN_XPAINEL.'&action=terminate&conta_id='.$vars['customfields']['Id-X-Painel'];
	$retorno = my_file_get_contents($urlToAction);
	if($retorno == 'success')
	{
		#send mail
		$command = 'SendEmail';
		$values=$vars;
		$values = array('messagename' => 'X-Painel Finalizacao de Conta', 'id' => $vars['serviceid']);
		$adminuser = $login_whmcs;
		$results = localAPI($command,$values,$adminuser);
		return $retorno;
	}

	return $retorno;
}
add_hook("ClientLogin",1,"xpainel_TerminateAccount");

function xpainel_CreateAccount($vars) {
	$urlToAction = 'https://xpainel.com.br/classes/lib/ativaClientes.php?token='.TOKEN_XPAINEL.'&action=create&conta_id='.$vars['customfields']['Id-X-Painel'];
	$retorno = my_file_get_contents($urlToAction);
	if($retorno == 'success')
	{
		#send mail
		$command = 'SendEmail';
		$values=$vars;
		$values = array('messagename' => 'X-Painel Nova Conta', 'id' => $vars['serviceid']);
		$adminuser = $login_whmcs;
		$results = localAPI($command,$values,$adminuser);
		return $retorno;
	}

	return $retorno;

}
add_hook("ClientLogin",1,"xpainel_CreateAccount");

