<?php
//Laravel DataBase
use WHMCS\Database\Capsule;

//Bloqueia o acesso direto ao arquivo
if (!defined("WHMCS")){
	die("Acesso restrito!");
}

#REQUIRE TARIFAS OBRIGATÓRIO PARA NÃO DAR ERRO NO CRON
if(empty($_SERVER['DOCUMENT_ROOT']))
{
    $_SERVER['DOCUMENT_ROOT'] = '/home/thxcom/whmcs.grupothx.com.br';
}
require_once($_SERVER['DOCUMENT_ROOT'].'/tarifasTHX.php');

add_hook('InvoicePaid', 1, function($vars) {

    $vars['invoiceid'];

    $url = 'https://propostas.grupothx.com.br/updatePaymentRest.php?tk='.TOKEN_PROPOSTAS_THX.'&whmcsinvoiceid='.$vars['invoiceid'];
    $ch = curl_init();
    $timeout = 5; // set to zero for no timeout
    curl_setopt ($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    ob_start();
    curl_exec($ch);
    curl_close($ch);
    $file_contents = ob_get_contents();
    ob_end_clean();

    $mensagemWhats = "
	Oi *Administrador Grupo THX*,

	*/includes/hooks/updatePropostas.php InvoicePaid* foi capturado 😉

    Foi acionado o updatePropostas para a proposta com whmcsinvoiceid = {$vars['invoiceid']}.
    O retorno foi:
    {$file_contents}
    ";

	$mensagemWhats = clearMsgToWhatsAppSend($mensagemWhats);
	enviaWhatsApp(WHATSAPP_ADMIN_JARDEL , $mensagemWhats);

	logModuleCall('/includes/hooks/updatePropostas.php', __FUNCTION__ , $vars, '', $mensagemWhats);
});
