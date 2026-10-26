<?php

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

$hook = array(
    'hook' => 'InvoiceCreated',
    'function' => 'mmhospedagem_InvoiceCreated'
);

if (!function_exists('mmhospedagem_InvoiceCreated')) {

	function mmhospedagem_InvoiceCreated($MMHospedagem) {

		$numeroLicenca = verificacao_licenca_whatsapp_mmhospedagem();

		$nome_modulo_buscado = 'Whatsapp API DEDICADO';
		$numerodafatura = $MMHospedagem['invoiceid'];

		foreach (WHMCS\Database\Capsule::table('mmhospedagem_whatsapp_configuracao')->WHERE('MMHospedagem_Nome_Modulo', $nome_modulo_buscado)->get() as $modulowhatsapp_configuracao) {
		    $whatsapp_origem = $modulowhatsapp_configuracao->mmhospedagem_origem_numero_whatsap;
		    $whatsapp_redicionamento_cliente = $modulowhatsapp_configuracao->mmhospedagem_redirecionar_cliente;
		    $whatsapp_metodo_deEnvio = $modulowhatsapp_configuracao->mmhospedagem_metododeenvio;
		    $whatsapp_Assinatura_mensagem = $modulowhatsapp_configuracao->mmhospedagem_assinatura;
		}

		// ID do Cliente pela fatura
		foreach (Capsule::table('tblinvoices')->where([['id','=',$numerodafatura],])->get() as $client) {
	    	$iddocliente = $client->userid;
	    	$datadopagamento = $client->datepaid;
			$datadecriacaofatura = explode('-',$client->date);
			$datadevencimentofatura = explode('-',$client->duedate);
	    	$valordafaturacomponto = number_format($client->total, 2, '.', '');
	    	$valordafaturacomvirgula = number_format($client->total, 2, ',', '');
		}

		// Dados do Cliente tblclients
		foreach (Capsule::table('tblclients')->where([['id','=',$iddocliente],])->get() as $informacoesdocliente) {
	    	$primeironome = $informacoesdocliente->firstname;
	    	$sobrenome = $informacoesdocliente->lastname;
	    	$email = $informacoesdocliente->email;
	    	$telefone = $informacoesdocliente->phonenumber;
	    	$endereço = $informacoesdocliente->address1;
	    	$bairro = $informacoesdocliente->address2;
	    	$cidade = $informacoesdocliente->city;
	    	$estato = $informacoesdocliente->state;
	    	$cep = $informacoesdocliente->postcode;
		}

		if($whatsapp_metodo_deEnvio == 'Custom') {

			foreach (Capsule::table('tblcustomfieldsvalues')->where([['fieldid','=',$whatsapp_origem],['relid','=',$iddocliente],])->get() as $dados) {
				$mmhospedagem_numerowhatsapp = '55' . $dados->value;
			}

		} else {

			$mmhospedagem_numerowhatsapp = $telefone;

		}

		foreach (WHMCS\Database\Capsule::table('mmhospedagem_whatsapp_mensagens')->WHERE('mmhospedagem_msg_titulo', 'Fatura Criada')->get() as $modulowhatsapp_mensagem_informacoes) {
	        $corpo_mensagem = $modulowhatsapp_mensagem_informacoes->mmhospedagem_msg_corpo;
	        $status_mensagem = $modulowhatsapp_mensagem_informacoes->mmhospedagem_msg_status;
	    }

	    // Personalização v2.0.3
	    $query = "SELECT value
	    FROM tblconfiguration
	    WHERE setting = 'SystemURL'
	    ORDER BY id DESC
	    LIMIT 1";
	    $result = mysql_query($query);

	    while($fetch = mysql_fetch_row($result)){

	        $URL_Sistema_WHMCS_MySQL = $fetch[0];

	    }

	    $URL_Montado_API_Whatsapp = str_replace('https', 'http', $URL_Sistema_WHMCS_MySQL) . '/modules/addons/mmhospedagem_whatsapp/mmhospedagem_api/';

	    if(($status_mensagem >= "1") && ($mmhospedagem_numerowhatsapp != "") && ($mmhospedagem_numerowhatsapp != "0")) {

		    // Trata as strings para o template da mensagem
			$alteracao_stings_msg_whatsapp = array(
				"{MMHospedagem_NomeCliente}",
				"{MMHospedagem_NumeroFatura}",
				"{MMHospedagem_ValordaFatura}",
				"{MMHospedagem_DataCriacaoFatura}",
				"{MMHospedagem_DataVencimentoFatura}",
				"{MMHospedagem_Assinatura}"
			);

			$alteracao_stings_msg_whatsapp_novos = array(
				$primeironome, //$primeironome . ' ' . $sobrenome
				$numerodafatura, 'R$' . $valordafaturacomvirgula,
				$datadecriacaofatura[2] . '/' . $datadecriacaofatura[1] . '/' . $datadecriacaofatura[0],
				$datadevencimentofatura[2] . '/' . $datadevencimentofatura[1] . '/' . $datadevencimentofatura[0],
				$whatsapp_Assinatura_mensagem
			);

			$MMHospedagem_Whatsapp_Mensagem = str_replace(
				$alteracao_stings_msg_whatsapp,
				$alteracao_stings_msg_whatsapp_novos,
				$corpo_mensagem
			);

			// Aqui se integra com a API

			$PostsWhsatapp = http_build_query(
			    array(
			        'NumeroWhatsapp' => ltrim(preg_replace('/\D/', '', $mmhospedagem_numerowhatsapp), 0),
			        'TipoMensagem' => '1', /* Use 1 para texto e 2 para Arquivo */
			        'Mensagem' => $MMHospedagem_Whatsapp_Mensagem
			    )
			);

			$MetodoEnvio = array('http' => array('method'  => 'POST', 'header'  => 'Content-type: application/x-www-form-urlencoded', 'content' => $PostsWhsatapp));
			$ConteudoXML  = stream_context_create($MetodoEnvio);
			$result = file_get_contents(mmhospedagem_api_whatsapp(), false, $ConteudoXML);

			logactivity('MMHospedagem_Whatsapp: ' . $result);

		}

	}

}

return $hook;