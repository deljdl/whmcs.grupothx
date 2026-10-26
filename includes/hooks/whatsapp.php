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
    $_SERVER['DOCUMENT_ROOT'] = '/home/thxcom/public_html';
}
require_once($_SERVER['DOCUMENT_ROOT'].'/tarifasTHX.php');

function sendCobranca($vars)
{

		// Get Module Options
		foreach( Capsule::table('tblpaymentgateways') -> where('gateway', '=', 'gofasgerencianetboleto') -> get( array( 'setting', 'value') ) as $settings ) {
			$setting_key				= $settings->setting;
			$params["$setting_key"]		= $settings->value;
		}
		$ggnb_merge_fields	= array();
		$invoice			= localAPI( 'GetInvoice', array('invoiceid' => $vars['relid']), false);



		if( (float)$invoice['total'] > (float)'0.00' )// removi and $invoice['paymentmethod'] === 'gofasgerencianetboleto'
		{
			// Saved Billets
			$billet_saved = array();
			foreach( Capsule::table('gofasgerencianetboleto') -> where('invoice_id', '=', $vars['relid']) -> get(
				array( 'invoice_id', 'charge_id', 'link', 'pdf', 'expire_at', 'total', 'barcode', 'charge_id', 'status', 'api_mode', 'qrcode' ) ) as $key => $value ) {
				$billets_for_invoice[$key]					= json_decode(json_encode($value), true);
			}

			$billet_saved = $billets_for_invoice['0']; // Array

			$vars['mergefields']['client_first_name'] = trim($vars['mergefields']['client_first_name']);



			$whatsInvoiceId = $invoice['invoiceid'];
			$whatsNumber = $vars['mergefields']['client_custom_fields'][1];
			$whatsName = trim($vars['mergefields']['client_first_name']);
			$whatsValor =  $invoice['total']; //number_format($varsArray['*price'],  2, ',', '.' );
			$whatsVencimento = $vars['mergefields']['invoice_date_due']; //date('d/m/Y', strtotime($billet_saved['expire_at']));


			$whatsMessageType = "Sua fatura *#{$whatsInvoiceId}*, no valor de *R$ {$whatsValor}* e com vencimento *".$whatsVencimento."* já está disponível para pagamento.";

			if($vars['messagename'] === 'Invoice Created')
			{
				$whatsMessageType = "Sua fatura *#{$whatsInvoiceId}*, no valor de *R$ {$whatsValor}* e com vencimento *".$whatsVencimento."* foi gerada e já está disponível para pagamento.";
			}

			if($vars['messagename'] === 'fatura-atualizada')
			{
				$whatsMessageType = "Sua fatura *#{$whatsInvoiceId}*, no valor de *R$ {$whatsValor}* e com vencimento *".$whatsVencimento."* foi atualizada e já está disponível para pagamento.";
			}

			if($vars['messagename'] === 'First Invoice Overdue Notice' || $vars['messagename'] === 'Second Invoice Overdue Notice' || $vars['messagename'] === 'Third Invoice Overdue Notice')
			{
				$whatsMessageType = "Sua fatura *#{$whatsInvoiceId}*, no valor de *R$ {$whatsValor}* e com vencimento *".$whatsVencimento."* está com o pagamento em atraso.";
			}

			$linkUrl = getUrlCurta('https://grupothx.com/viewinvoice?i='.criptX($whatsInvoiceId));

			$detalhes = '*'.$linkUrl.'*';
			$mensagemHello = "Olá *{$whatsName}*! 👋";


			$mensagemWhats ="

			{$whatsMessageType}

			{$detalhes}";

			$title = 'Clique aqui para pagar sua fatura com segurança';
			$linkDescription = 'Pague aqui sua fatura';
			$imagem = 'https://tihospedagem.com.br/thx-host-assets/ogimage-whmcs.png';



			$whatsNumber = normalizaTelefone($whatsNumber);

			if(!empty($whatsNumber))
			{
				$whatsEnvio = enviaWhatsApp(
				$whatsNumber,
				clearMsgToWhatsAppSend($mensagemHello.$mensagemWhats),
				$imagem,
				'',
				'',
				2,
				$linkDescription,
				$title,
				$linkUrl);
			}


			$contatos = getClientContacts($vars['mergefields']['client_id']);

			foreach($contatos as $contato)
			{
				$telefone = normalizaTelefone($contato['phonenumber']);
				$nome = !empty($contato['firstname']) ? $contato['firstname'] : $whatsName;
				if(!empty($telefone) && $telefone != $whatsNumber)
				{
					$mensagemHello = "Olá *{$nome}*! 👋";

					enviaWhatsApp(
						$telefone,
						clearMsgToWhatsAppSend($mensagemHello.$mensagemWhats),
						$imagem,
						'',
						'',
						2,
						$linkDescription,
						$title,
						$linkUrl);
				}
			}









			logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__, $vars, '', $whatsEnvio);

			return $whatsEnvio;
		}
}

function sendPassword($vars)
{
	$whatsNumber = $vars['mergefields']['client_custom_fields'][1];
	$vars['mergefields']['client_first_name'] = trim($vars['mergefields']['client_first_name']);
	$mensagemWhats ="
	Olá *{$vars['mergefields']['client_first_name']}* ! 👋

	Seus dados de acesso ao sistema foram alterados !!
	*Email:* {$vars['mergefields']['client_email']}
	*Senha:* {$vars['mergefields']['client_password']}

	Para acessar o sistema ou alterar sua senha acesse o link abaixo:
	".getUrlCurta(HTTP_WHMCS.'/clientarea.php');
	$mensagemWhats = clearMsgToWhatsAppSend($mensagemWhats);

		$whatsEnvio = enviaWhatsApp($whatsNumber , $mensagemWhats);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', $whatsEnvio);
}

function clearMsgToWhatsAppSend($str)
{
	$de = array(
		'TIH - ',
		'<hr />',
		'<strong>',
		'</strong>',
		'\n</strong>',
		"\n</strong>",
		'<em>',
		'</em>',
		'<h3>',
		'</h3>',
		'<p>',
		'</p>'
	);
	$to = array(
		'Hospedagem de Sites TIH - ',
		"\\n---------------\\n",
		'*',
		'*',
		'*',
		'*',
		'_',
		'_',
		'*',
		'*',
		'\\n',
		''
		);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__.'NAME ADD' , 'Request', '', 'RESPONSE');
	//logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__.'NAME ADD' , Request, Response, result ?);
	$str1 = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $str);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__.' REMOVE STYLES' , $str, '', $str1);
	$str2 = preg_replace('/(<[^>]+) class=".*?"/i', '$1', $str1);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__.' REMOVE Class' , $str1, '', $str2);
	$str3 = preg_replace('/(<[^>]+) id=".*?"/i', '$1', $str2);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__.' REMOVE Class' , $str2, '', $str3);
	$str4 = str_replace($de, $to, $str3);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__.' REPLACE TAGS' , $str3, '', $str4);
	$str5 = strip_tags($str4);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__.' REMOVE TAGS' , $str4, '', $str5);
	return trim(preg_replace('/\t+/', '', $str5));
}

function sendMessageWhatsAppInvoiceCancelled($vars)
{
	$dadosInvoice = getDadosInvoiceById($vars['invoiceid']);
	$dadosCliente =	getDadosClienteById($dadosInvoice['userid']);

	if($dadosCliente['customfields1'] == 'on')
	{
		return envioFoiBloqueadoPeloCliente($vars);
	}


	$valor = $dadosInvoice['total'];
	$vencimento = date('d/m/Y', strtotime($dadosInvoice['duedate']));

	$whatsNumber = $dadosCliente['customfields2'];
	$dadosCliente['firstname'] = trim($dadosCliente['firstname']);
	$mensagemWhats ="
	Olá *{$dadosCliente['firstname']}*! 👋

	Sua fatura *#{$dadosInvoice['invoiceid']}*, no valor de *R$ {$dadosInvoice['total']}* e vencimento *{$vencimento}* foi *CANCELADA* com sucesso !";

	$mensagemWhats = clearMsgToWhatsAppSend($mensagemWhats);

		$whatsEnvio = enviaWhatsApp($whatsNumber , $mensagemWhats);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', $whatsEnvio);
}

function sendCobrancaPaga($vars)
{
	$dadosInvoice = getDadosInvoiceById($vars['relid']);
	$dadosCliente =	getDadosClienteById($dadosInvoice['userid']);
	$valor = $dadosInvoice['total'];
	$vencimento = date('d/m/Y', strtotime($dadosInvoice['duedate']));


	$whatsNumber = $dadosCliente['customfields2'];
	$dadosCliente['firstname'] = trim($dadosCliente['firstname']);
	$mensagemWhats ="
	Olá *{$dadosCliente['firstname']}*! 👋

	Sua fatura *#{$dadosInvoice['invoiceid']}*, no valor de *R$ {$dadosInvoice['total']}* e vencimento *{$vencimento}* teve o *PAGAMENTO CONFIRMADO*";

	$mensagemWhats = clearMsgToWhatsAppSend($mensagemWhats);

		$whatsEnvio = enviaWhatsApp($whatsNumber , $mensagemWhats);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', $whatsEnvio);
	alexa('Nova entrada de R$ '.str_replace('.',',',$dadosInvoice['total']).'. Paga por '.$dadosCliente['firstname'].', '.$dadosCliente['companyname']);
}

function sendMessageXPainelReativacaoDeConta($vars)
{
	$dadosCliente =	getDadosClienteById($vars['mergefields']['client_id']);

	$whatsNumber = $dadosCliente['customfields2'];
	$dadosCliente['firstname'] = trim($dadosCliente['firstname']);
	$mensagemWhats ="
	Olá *{$dadosCliente['firstname']}*! 👋

	O Gestor de Conteúdo X-Painel Rental Code *foi reativado* para *{$vars['mergefields']['service_domain']}*.";

	$mensagemWhats = clearMsgToWhatsAppSend($mensagemWhats);

		$whatsEnvio = enviaWhatsApp($whatsNumber , $mensagemWhats);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', $whatsEnvio);
}

function sendFelizAniversario($vars)
{
	$whatsNumber = $vars['mergefields']['client_custom_fields'][1];
	$vars['mergefields']['client_first_name'] = trim($vars['mergefields']['client_first_name']);
	$mensagemWhats ="
	Olá *{$vars['mergefields']['client_first_name']}*! 👋 hoje e o seu dia especial !!!

	*FELIZ ANIVERSÁRIO!*

	Muita saúde, paz, harmonia, sucesso e incontáveis realizações!
	Esperamos que no próximo ano possamos, mais uma vez, celebrar muitas conquistas.
	Desejamos neste novo ano de vida que se inicia, que todos os caminhos que você percorrer agreguem sabedoria para sua vida pessoal e profissional!
	Seja feliz!";
	$mensagemWhats = clearMsgToWhatsAppSend($mensagemWhats);

		$whatsEnvio = enviaWhatsApp($whatsNumber , $mensagemWhats);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', $whatsEnvio);
}

function sendSupportTicketOpened($vars)
{

	$whatsNumber = $vars['mergefields']['client_custom_fields'][1];
	$vars['mergefields']['client_first_name'] = trim($vars['mergefields']['client_first_name']);
	$mensagemWhats ="
	Olá *{$vars['mergefields']['client_first_name']}*! 👋

	Seu *Chamado* para atendimento foi *{$vars['mergefields']['ticket_status']}*.

	*Departamento:*
	{$vars['mergefields']['ticket_department']}

	*Assunto:*
	{$vars['mergefields']['ticket_subject']}

	Para acessar esse *Chamado* agora acesse o link abaixo:
	".getUrlCurta($vars['mergefields']['ticket_url']);

	$mensagemWhats = clearMsgToWhatsAppSend($mensagemWhats);

	$whatsEnvio = enviaWhatsApp($whatsNumber , $mensagemWhats);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', $whatsEnvio);
	criaTarefaKamban("Responder chamado do cliente {$vars['mergefields']['client_first_name']}, {$vars['mergefields']['client_company_name']}<br /><br /><strong>Assunto:</strong><br />{$vars['mergefields']['ticket_subject']}");
	alexa("Novo chamado aberto no W H M C S pelo cliente {$vars['mergefields']['client_first_name']}, {$vars['mergefields']['client_company_name']}");
}


function sendMessageWhatsSupportTicketOpenedbyAdmin($vars)
{

	$whatsNumber = $vars['mergefields']['client_custom_fields'][1];
	$vars['mergefields']['client_first_name'] = trim($vars['mergefields']['client_first_name']);
	$mensagemWhats ="
	Olá *{$vars['mergefields']['client_first_name']}*! 👋

	Nossa equipe abriu um *Chamado* para atendimento.

	*Departamento:*
	{$vars['mergefields']['ticket_department']}

	*Assunto:*
	{$vars['mergefields']['ticket_subject']}

	Para responder esse *Chamado* agora acesse o link abaixo:
	".getUrlCurta($vars['mergefields']['ticket_url']);

	$mensagemWhats = clearMsgToWhatsAppSend($mensagemWhats);

	$whatsEnvio = enviaWhatsApp($whatsNumber , $mensagemWhats);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', $whatsEnvio);

	/*.*/
}


function sendMessageWhatsSupportTicketReply($vars)
{

	$whatsNumber = $vars['mergefields']['client_custom_fields'][1];
	$resumo = getParagrafo($vars['mergefields']['ticket_message']);
	$vars['mergefields']['client_first_name'] = trim($vars['mergefields']['client_first_name']);
	$mensagemWhats ="
	Olá *{$vars['mergefields']['client_first_name']}*! 👋

	Seu *Chamado* para atendimento foi *Respondido*.

	*Departamento:*
	{$vars['mergefields']['ticket_department']}

	*Assunto:*
	{$vars['mergefields']['ticket_subject']}

	*Mensagem:*
	{$resumo}

	Para responder esse *Chamado* agora acesse o link abaixo:
	".getUrlCurta($vars['mergefields']['ticket_url']);

	$mensagemWhats = clearMsgToWhatsAppSend($mensagemWhats);

	$whatsEnvio = enviaWhatsApp($whatsNumber , $mensagemWhats);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', $whatsEnvio);

	/*.*/
}

function getParagrafo($str)
{
	$paragrafo = $str;
	$paragrafo = strip_tags($paragrafo);
	$paragrafo = trim($paragrafo, '<br />');
	$paragrafo = trim($paragrafo, '<p>');
	$paragrafo = trim($paragrafo, '</p>');
	$paragrafo = explode('------------------------------', $paragrafo);
	return $paragrafo[0];
}

function sendMessageWhatsServiceUnsuspensionSuccessful($vars)
{
	$dados = getDadosClienteById($vars['client_id']);


	$whatsNumber = $dados['customfields']['customfields2'];
	$vars['client']['firstname'] = trim($vars['client']['firstname']);
	$mensagemWhats ="
	Olá *{$vars['client']['firstname']}*! 👋

	Estamos muito felizes pois a suspensão de seu serviço foi removida e *seu serviço está ativo 😉*.

	*Serviço:* {$vars['mergefields']['service_product']}
	*Domínio:* {$vars['mergefields']['service_domain']}";

	$mensagemWhats = clearMsgToWhatsAppSend($mensagemWhats);

	$whatsEnvio = enviaWhatsApp($whatsNumber , $mensagemWhats);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', $whatsEnvio);
	alexa("Suspensão do serviço {$vars['mergefields']['service_product']} {$vars['mergefields']['service_domain']} de {$dados['firstname']}, {$dados['companyname']} foi cancelada.");
}

function sendMessageWhatsAvisoExcluirSite($vars)
{

	$dados = getDadosClienteById($vars['mergefields']['client_id']);

	$whatsNumber = $dados['customfields2'];
	$vars['client']['firstname'] = trim($vars['client']['firstname']);
	$servico = trim($vars['mergefields']['service_product_name']);
	$dominio = trim($vars['mergefields']['service_domain']);

	$mensagemWhats ="
	Olá *{$dados['firstname']}*! 👋

	Informamos que o serviço *{$servico}* sob o domínio *{$dominio}* será excluído em *3 dias* e todos os arquivos serão apagados.

	Depois dessa ação, nenhum arquivo poderá ser recuperado.

	*Este procedimento é  irreversível*.

	Nos informe caso queira baixar os seus arquivos.

	*Atenção: Para o envio dos arquivos a hospedagem deve obrigatoriamente estar ativa.*";


	$mensagemWhats = clearMsgToWhatsAppSend($mensagemWhats);

	$whatsEnvio = enviaWhatsApp($whatsNumber , $mensagemWhats);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', $whatsEnvio);
}


function sendMessageWhatsEspacoEmDiscoAviso($vars)
{

	//echo('<pre>'.print_r($vars, 1).'</pre><hr />');
	$dados = getDadosHospedagemById($vars['relid']);
	//echo('<pre>'.print_r($dados, 1).'</pre><hr />');
	//$buceta = implode('!', $vars);
	$whatsNumber = $vars['mergefields']['client_custom_fields'][1];
	$vars['mergefields']['client_first_name'] = trim($vars['mergefields']['client_first_name']);
	$vars['mergefields']['service_product_name'] = trim($vars['mergefields']['service_product_name']);
	$espacoTotal = $dados['disklimit'] / 1000;
	$espacoUtilizado = round(($dados['diskusage'] / 1000) , 2);
	$espacoDisponivel = round((($dados['disklimit'] - $dados['diskusage']) / 1000), 2);


	$porcentagemUsada =  round(($dados['diskusage']*100/$dados['disklimit']), 2);
	$porcentagemDisponivel =  100 - $porcentagemUsada;


	$mensagemWhats ="
	Olá *{$vars['mergefields']['client_first_name']}*! 👋

	Este é um aviso que sua hospedagem está ficando sem espaço em disco.

	_Dados de seu plano de hospedagem atual:_
	*Domínio:* {$vars['mergefields']['service_domain']}
	*Plano:* {$dados['name']}
	*Espaço total em disco:* {$espacoTotal} GB
	*Espaço já utilizado:*  {$espacoUtilizado} GB  ({$porcentagemUsada}%)
	*Espaço disponível:*  {$espacoDisponivel} GB  ({$porcentagemDisponivel}%)";




	$mensagemWhats = clearMsgToWhatsAppSend($mensagemWhats);



	$whatsEnvio = enviaWhatsApp($whatsNumber , $mensagemWhats);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', $whatsEnvio);
}


function sendMessageWhatsServiceUnsuspensionNotification($vars)
{
	$whatsNumber = $vars['mergefields']['client_custom_fields'][1];
	$vars['mergefields']['client_first_name'] = trim($vars['mergefields']['client_first_name']);
	$mensagemWhats ="
	Olá *{$vars['mergefields']['client_first_name']}*! 👋

	Estamos muito felizes pois a suspensão de seu serviço foi removida e *seu serviço está ativo 😉*.

	*Serviço:* {$vars['mergefields']['service_product_name']}
	*Domínio:* {$vars['mergefields']['service_domain']}";

	$mensagemWhats = clearMsgToWhatsAppSend($mensagemWhats);

	$whatsEnvio = enviaWhatsApp($whatsNumber , $mensagemWhats);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', $whatsEnvio);
	alexa("Suspensão do serviço {$vars['mergefields']['service_product_name']}, {$vars['mergefields']['service_domain']} de {$vars['mergefields']['client_first_name']}, {$vars['mergefields']['client_company_name']} foi cancelada");
}


function sendMessageWhatsServiceSuspensionNotification($vars)
{

	$whatsNumber = $vars['mergefields']['client_custom_fields'][1];
	$vars['mergefields']['client_first_name'] = trim($vars['mergefields']['client_first_name']);
	$mensagemWhats ="
	Olá *{$vars['mergefields']['client_first_name']}*! 👋

	Estamos muito tristes pois seu serviço foi *suspenso 😔*.

	*Serviço:*
	{$vars['mergefields']['service_product_name']}
	*Domínio:* {$vars['mergefields']['service_domain']}
	*Motivo:*
	{$vars['mergefields']['service_suspension_reason']}";

	$mensagemWhats = clearMsgToWhatsAppSend($mensagemWhats);

	$whatsEnvio = enviaWhatsApp($whatsNumber , $mensagemWhats);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', $whatsEnvio);
	alexa("Suspensão do serviço {$vars['mergefields']['service_product_name']}, {$vars['mergefields']['service_domain']} de {$vars['mergefields']['client_first_name']}, {$vars['mergefields']['client_company_name']} foi ativada");
}

function sendMessageWhatsDomainRegistrationConfirmation($vars)
{
	$whatsNumber = $vars['mergefields']['client_custom_fields'][1];
	$vars['mergefields']['client_first_name'] = trim($vars['mergefields']['client_first_name']);
	$mensagemWhats ="
	Olá *{$vars['mergefields']['client_first_name']}*! 👋

	Obrigado por *registrar seu domínio* conosco.
	O registro do dominio *{$vars['mergefields']['domain_name']}* foi efetuado com sucesso.";

	$mensagemWhats = clearMsgToWhatsAppSend($mensagemWhats);

	$whatsEnvio = enviaWhatsApp($whatsNumber , $mensagemWhats);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', $whatsEnvio);
	criaTarefaKamban("Registrar domínio:<br />Domínio: {$vars['mergefields']['domain_name']}<br />Período: {$vars['mergefields']['domain_reg_period']}<br />Cliente:{$vars['mergefields']['client_first_name']}<br />Empresa:{$vars['mergefields']['client_company_name']}");
	alexa("Registre o domínio  {$vars['mergefields']['domain_name']} para {$vars['mergefields']['client_first_name']}, {$vars['mergefields']['client_company_name']}.");

}



function sendMessageWhatsDomainRenewalSuccessful($vars)
{
	//echo '->'.__LINE__; die('<pre>'.print_r($vars,1).'</pre>');
	$dados = getDadosHospedagemById($vars['mergefields']['client_id']);
	$whatsNumber = $vars['mergefields']['client_custom_fields'][1];
	$vars['mergefields']['client_first_name'] = trim($vars['mergefields']['client_first_name']);
	$mensagemWhats ="
	Olá *{$vars['mergefields']['client_first_name']}*! 👋

	Seu domínio *{$vars['mergefields']['domain_name']}* foi renovado para mais *{$vars['mergefields']['domain_reg_period']}*.";

	$mensagemWhats = clearMsgToWhatsAppSend($mensagemWhats);

	$whatsEnvio = enviaWhatsApp($whatsNumber , $mensagemWhats);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', $whatsEnvio);
}

function sendMessageWhatsDomainRenewalConfirmation($vars)
{
	$dados = getDadosHospedagemById($vars['mergefields']['client_id']);
	$whatsNumber = $vars['mergefields']['client_custom_fields'][1];
	$vars['mergefields']['client_first_name'] = trim($vars['mergefields']['client_first_name']);
	$mensagemWhats ="
   Olá *{$vars['mergefields']['client_first_name']}* ! 👋

	O registro do domínio *{$vars['mergefields']['domain_name']}* foi renovado com sucesso para mais *{$vars['mergefields']['domain_reg_period']}*.
	Próximo vencimento: *{$vars['mergefields']['domain_expiry_date']}*.";

	$mensagemWhats = clearMsgToWhatsAppSend($mensagemWhats);

	$whatsEnvio = enviaWhatsApp($whatsNumber , $mensagemWhats);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', $whatsEnvio);
	criaTarefaKamban("Renovar o domínio {$vars['mergefields']['domain_name']}<br />Período: {$vars['mergefields']['domain_reg_period']}<br />Cliente:{$vars['mergefields']['client_first_name']}<br />Empresa:{$vars['mergefields']['client_company_name']}");
	alexa("Renove o domínio  {$vars['mergefields']['domain_name']}. Para {$dados['firstname']}, da empresa {$dados['companyname']}.");
}





function sendMessageWhatsHostingAccountWelcomeEmail($vars)
{

	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', $vars);

}

function sendMessageWhatsCancellationRequestConfirmation($vars)
{

	$whatsNumber = $vars['mergefields']['client_custom_fields'][1];
	$vars['mergefields']['client_first_name'] = trim($vars['mergefields']['client_first_name']);
	$mensagemWhats ="
	Olá *{$vars['mergefields']['client_first_name']}*! 👋

	Recebemos um pedido de cancelamento para um de seus serviços/produtos.
	Um email foi enviado para *confirmar o cancelamento*.
	Responda esse email *confirmando sua solicitação*.

	*É importante saber que, após o CANCELAMENTO, todos os dados todos os dados ligados ao serviço serão apagados.*";

	$mensagemWhats = clearMsgToWhatsAppSend($mensagemWhats);

	$whatsEnvio = enviaWhatsApp($whatsNumber , $mensagemWhats);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', $whatsEnvio);

}

function avisaEnvioParaAdmin($vars)
{
	$mensagemWhats = '
	Oi *Administrador Grupo THX*,

	*'.$vars['messagename'].'* não foi tratado 😉

	[WHMCS/includes/hooks/whatsapp.php]

	[VARS]
	'.print_r($vars,1).'
	[/VARS]

	[DADOS CLIENTE]
	'.print_r(getDadosClienteById($vars['mergefields']['client_id']), 1).'
	[/DADOS CLIENTE]

	END';

	$mensagemWhats = clearMsgToWhatsAppSend($mensagemWhats);
	enviaWhatsApp(WHATSAPP_ADMIN_JARDEL , $mensagemWhats);

	$mensagemWhats = getEmailTemplate($vars['messagename'], $vars);
	$mensagemWhats = replaceVars($mensagemWhats,$vars);
	$mensagemWhats = clearMsgToWhatsAppSend($mensagemWhats);
	enviaWhatsApp(WHATSAPP_ADMIN_JARDEL , $mensagemWhats);
}


function sendMessageWhatsClientSignupEmail($vars)
{
	$whatsNumber = $vars['mergefields']['client_custom_fields'][1];
	$vars['mergefields']['client_first_name'] = trim($vars['mergefields']['client_first_name']);
	$mensagemWhats ="
	Obrigado *{$vars['mergefields']['client_first_name']}* 🙌🙌,

	Agradecemos por escolher o *Grupo THX* como seu fornecedor de soluções e serviços de alta tecnologia.

	*A conta para a sua empresa foi criada com sucesso !!!*

	Agora você terá acesso a diversos serviços de qualidade e os melhores serviços de atendimento ao cliente.

	É de nosso interesse sempre atingir a plena satisfação de nossos clientes.

	É uma imensa satisfação ter você como nosso cliente.

	Sabemos que a concorrência e grande em todos os setores e , por isso mesmo, reiteramos nosso agradecimento por nos escolher.


	*Seguem seus dados de acesso:*

	*Email:* {$vars['mergefields']['client_email']}
	*Senha:* {$vars['mergefields']['client_password']}
	__________________________________

	*Central do Cliente:* ".getUrlCurta(HTTP_WHMCS.'/clientarea.php')."

	*Boas-vindas ao Grupo THX.*

	*O nosso sucesso depende diretamente do seu.*";

	$mensagemWhats = clearMsgToWhatsAppSend($mensagemWhats);

	$whatsEnvio = enviaWhatsApp($whatsNumber , $mensagemWhats);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', $whatsEnvio);
}


function envioFoiBloqueadoPeloCliente($vars)
{
	$mensagemWhats = '
	Oi *Administrador Grupo THX*,

	*'.$vars['messagename'].'* não foi enviado para o *'.$vars['mergefields']['client_custom_fields'][1].'* conforme sua definição 😉';

	enviaWhatsApp(WHATSAPP_ADMIN_JARDEL , $mensagemWhats);
}



function sendMessageWhatsApp($vars)
{
	if($vars['mergefields']['client_custom_fields'][0] == 'on')
	{
		return envioFoiBloqueadoPeloCliente($vars);
	}

	logModuleCall('sendMessageWhatsApp', $vars['messagename'], $vars, $vars, 'LOG GERAL');

	switch ($vars['messagename']) {
		case 'fatura-atualizada':
			return sendCobranca($vars);
		break;

		case 'Invoice Created':
			return sendCobranca($vars);
		break;

		case 'Invoice Payment Reminder':
			return sendCobranca($vars);
		break;

		case 'First Invoice Overdue Notice':
			return sendCobranca($vars);
		break;

		case 'Second Invoice Overdue Notice':
			return sendCobranca($vars);
		break;

		case 'Third Invoice Overdue Notice':
			return sendCobranca($vars);
		break;

		case 'Automated Password Reset':
			return sendPassword($vars);
		break;

		case 'Invoice Payment Confirmation':
			return sendCobrancaPaga($vars);
		break;

		case 'Support Ticket Opened':
			return sendSupportTicketOpened($vars);
		break;

		case 'Feliz Aniversario':
			return sendFelizAniversario($vars);
		break;

		case 'Support Ticket Reply':
			return sendMessageWhatsSupportTicketReply($vars);
		break;

		case 'Service Suspension Notification':
			return sendMessageWhatsServiceSuspensionNotification($vars);
		break;

		case 'Service Unsuspension Notification':
			return sendMessageWhatsServiceUnsuspensionNotification($vars);
		break;

		case 'Domain Registration Confirmation':
			return sendMessageWhatsDomainRegistrationConfirmation($vars);
		break;

		case 'Domain Renewal Successful':
			return sendMessageWhatsDomainRenewalSuccessful($vars);
		break;

		case 'Cancellation Request Confirmation':
			return sendMessageWhatsCancellationRequestConfirmation($vars);
		break;

		case 'Domain Renewal Confirmation':
			return sendMessageWhatsDomainRenewalConfirmation($vars);
		break;

		case 'Support Ticket Opened by Admin':
			return sendMessageWhatsSupportTicketOpenedbyAdmin($vars);
		break;

		case 'SUSPESÃO SPAM':
			return sendMessageWhatsSUSPESAOSPAM($vars);
		break;

		case 'Aviso de Spam':
			return sendMessageWhatsSUSPESAOSPAM($vars);
		break;

		case 'X-Painel Rativacao de Conta':
			return sendMessageXPainelReativacaoDeConta($vars);
		break;

		case 'Hosting Account Welcome Email':
			return sendMessageWhatsHostingAccountWelcomeEmail($vars);
		break;

		case 'Service Unsuspension Successful':
			return sendMessageWhatsServiceUnsuspensionSuccessful($vars);
		break;

		case 'Espaço em Disco Aviso':
			return sendMessageWhatsEspacoEmDiscoAviso($vars);
		break;

		case 'Client Signup Email':
			return sendMessageWhatsClientSignupEmail($vars);
		break;

		case 'Excluir do Servidor em 3 dias':
			return sendMessageWhatsAvisoExcluirSite($vars);
		break;

		case 'Custom Admin Temp':
			return;
		break;

		default:
			#COEMNTEI PORQUE NÃO AGUENTO MAIS RECEBER MENSAGEM VAZIA !!!!! EM 06/09/2022 ÀS 09:57 ahhhhhhHHH
			return avisaEnvioParaAdmin($vars);
		break;
	}
}



//Função do Módulo
function sendWhatsAppInvoicePaymentReminder($vars){

}
function sendWhatsAppEmailPreSend($vars){
	return sendMessageWhatsApp($vars);
}

function sendMessageWhatsAdminLogin($vars)
{

	if (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === 'api.php') {
		return;
	}
	$mensagemWhats ="
	*Novo login:*
	*Usuário:* {$vars['username']}
	*Data:* ".date('d/m/Y às G:i:s')."
	*Ip:* {$_SERVER['REMOTE_ADDR']}";

	$mensagemWhats = clearMsgToWhatsAppSend($mensagemWhats);

	$whatsEnvio = enviaWhatsApp(WHATSAPP_ADMIN_JARDEL , $mensagemWhats);

	alexa("Novo login do administrador.");
}

function sendMessageWhatsClientLogin($vars)
{
	$dados = getDadosClienteById($vars['userid']);


	$mensagemWhats ="
	*Novo login*:

	*Id:* {$dados['userid']}
	*Usuário:* {$dados['firstname']} {$dados['lastname']}
	*Email:* {$dados['email']}
	*Empresa:* {$dados['companyname']}
	*Data:* ".date('d/m/Y às G:i:s')."
	*Ip:* {$_SERVER['REMOTE_ADDR']}";

	$mensagemWhats = clearMsgToWhatsAppSend($mensagemWhats);

		$whatsEnvio = enviaWhatsApp(WHATSAPP_ADMIN_JARDEL , $mensagemWhats);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', $whatsEnvio);
	alexa("Novo login do cliente {$dados['firstname']}, {$dados['companyname']}.");
}



function sendMessageWhatsTicketAdminReply($vars)
{
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', '');
}

function sendMessageWhatsTicketClose($vars)
{
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', '');

	$ticket = getDadosTicketById($vars['ticketid']);
	$cliente = getDadosClienteById($ticket['userid']);

	$cliente['firstname'] = trim($cliente['firstname']);
	$whatsNumber = $cliente['customfields2'];
	$mensagemWhats ="
	Olá *{$cliente['firstname']}*! 👋

	O *Chamado* *#{$ticket['ticketid']}* foi *FECHADO* por nossa equipe.

	*Departamento:*
	{$ticket['deptname']}

	*Assunto:*
	{$ticket['subject']}

	Caso ainda precise de suporte, abra um novo *Chamado* no link abaixo:
	".getUrlCurta(HTTP_WHMCS."/submitticket.php");

	$mensagemWhats = clearMsgToWhatsAppSend($mensagemWhats);

	$whatsEnvio = enviaWhatsApp($whatsNumber , $mensagemWhats);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', $whatsEnvio);



	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $dados, '', '');
}

function sendMessageAfterProductUpgrade($vars)
{
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', '');
	return;
}

function sendMessagePreUpgradeCheckout($vars)
{
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', '');
	return;
}



function sendMessageAfterModuleTerminate($vars)
{
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', '');
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $params, '', '');
}

function sendMessageWhatsTicketOpen($vars)
{
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', '');
}

function sendMessageWhatsSUSPESAOSPAM($vars)
{
	$whatsNumber = $vars['mergefields']['client_custom_fields'][1];
	$vars['mergefields']['client_first_name'] = trim($vars['mergefields']['client_first_name']);
	$mensagemWhats ="
	Olá *{$vars['mergefields']['client_first_name']}* 😔,

	Sentimos muito informar que uma conta de e-mail de seu domínio *{$vars['mergefields']['service_domain']}* foi suspensa para sua própria segurança.

	Em nossas auditorias concluímos que um dos e-mails de sua conta enviou *uma quantidade enorme de e-mails* apenas nas últimas horas.
	Isso nos leva a crer que seu dispositivo deva estar *infectado* com algum *Vírus* ou *Malware*.

	Precisamos que verifiquem por favor as causas do problema e nos informem as providencias tomadas para que assim sua conta possa ser restabelecida.
	Agradecemos sua compreensão e aguardamos seu retorno caso precise de ajuda.";

	$mensagemWhats = clearMsgToWhatsAppSend($mensagemWhats);

	$whatsEnvio = enviaWhatsApp($whatsNumber , $mensagemWhats);
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', $whatsEnvio);
}

#PADRAO NAO ALTERAR SO COPIAR PRA CIMA #########################################
function functionname($vars)
{
	logModuleCall('/includes/hooks/whatsapp.php', __FUNCTION__ , $vars, '', '');
}
################################################################################

//Adicionar Função ao CronJob do WHMCS
#INVOICES
add_hook("InvoicePaymentReminder",1,"sendWhatsAppInvoicePaymentReminder");
add_hook("EmailPreSend", 1, "sendWhatsAppEmailPreSend");
add_hook("InvoiceCancelled", 1, "sendMessageWhatsAppInvoiceCancelled");
#TICKETS
add_hook("TicketAdminReply", 1, "sendMessageWhatsTicketAdminReply");
add_hook("TicketClose", 1, "sendMessageWhatsTicketClose");
add_hook("TicketOpen", 1, "sendMessageWhatsTicketOpen");
#LOGIN
add_hook("AdminLogin", 1, "sendMessageWhatsAdminLogin");
add_hook("ClientLogin", 1, "sendMessageWhatsClientLogin");
#UP AND DONWGRADE
add_hook("AfterProductUpgrade", 1, "sendMessageAfterProductUpgrade");
add_hook("PreUpgradeCheckout", 1, "sendMessagePreUpgradeCheckout");
#terminate aviso
add_hook('AfterModuleTerminate', 1, "sendMessageAfterModuleTerminate");