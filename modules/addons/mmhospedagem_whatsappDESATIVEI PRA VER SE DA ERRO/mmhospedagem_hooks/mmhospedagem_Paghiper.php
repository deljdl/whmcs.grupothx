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
    'hook' => 'EmailPreSend',
    'function' => 'mmhospedagem_PagHiper'
);

if (!function_exists('mmhospedagem_PagHiper')) {

	function mmhospedagem_PagHiper($MMHospedagem) {

		$numeroLicenca = verificacao_licenca_whatsapp_mmhospedagem();

		$nome_modulo_buscado = 'Whatsapp API DEDICADO';

		foreach (WHMCS\Database\Capsule::table('mmhospedagem_whatsapp_configuracao')->WHERE('MMHospedagem_Nome_Modulo', $nome_modulo_buscado)->get() as $modulowhatsapp_configuracao) {
		    $whatsapp_origem = $modulowhatsapp_configuracao->mmhospedagem_origem_numero_whatsap;
		    $whatsapp_redicionamento_cliente = $modulowhatsapp_configuracao->mmhospedagem_redirecionar_cliente;
		    $whatsapp_metodo_deEnvio = $modulowhatsapp_configuracao->mmhospedagem_metododeenvio;
		    $whatsapp_Assinatura_mensagem = $modulowhatsapp_configuracao->mmhospedagem_assinatura;
		    $whatsapp_Nascimento = $modulowhatsapp_configuracao->mmhospedagem_origem_nascimento;
		}

		foreach (WHMCS\Database\Capsule::table('mmhospedagem_whatsapp_mensagens')->WHERE('mmhospedagem_msg_titulo', 'Boleto Paghiper')->get() as $modulowhatsapp_mensagem_informacoes) {
	        $corpo_mensagem = $modulowhatsapp_mensagem_informacoes->mmhospedagem_msg_corpo;
	        $status_mensagem = $modulowhatsapp_mensagem_informacoes->mmhospedagem_msg_status;
	    }


	    if(($status_mensagem >= "1")) {

	    	$invoice_id = $MMHospedagem['relid'];

	    	$MMHospedagem_Mensagem_Enviada = $MMHospedagem['messagename'];

	    	$MMHospedagem_Templates_Email_SEND = array(
	    		'Invoice Created', 'Invoice Payment Reminder',
	    		'First Invoice Overdue Notice',
	    		'Second Invoice Overdue Notice',
	    		'Third Invoice Overdue Notice'
	    	);

	    	if(in_array($MMHospedagem_Mensagem_Enviada, $MMHospedagem_Templates_Email_SEND)) {

	    		$MMHospedagem_invoice = mysql_fetch_array(mysql_query("SELECT tblinvoices.*,tblclients.id as client_id, tblclients.email FROM tblinvoices INNER JOIN tblclients ON tblclients.id=tblinvoices.userid WHERE tblinvoices.id='$invoice_id'"));

	    		$MMHospedagem_WHMCS_URL = rtrim(\App::getSystemUrl(),"/");

	    		$MMHospedagem_json = file_get_contents($MMHospedagem_WHMCS_URL."/modules/gateways/paghiper.php?invoiceid=".$invoice_id."&uuid=".$MMHospedagem_invoice['client_id']."&mail=".$MMHospedagem_invoice['email']."&json=1");

	    		$MMHospedagem_resultado = json_decode($MMHospedagem_json);

	    		//

		        // ID do Cliente pela fatura
				foreach (Capsule::table('tblinvoices')->where([['id','=',$invoice_id],])->get() as $client) {
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

				$CODIGO_DE_BARRA = (isset($MMHospedagem_resultado->bank_slip)) ? $MMHospedagem_resultado->bank_slip->digitable_line : $MMHospedagem_resultado->digitable_line;
				$URL_PDF  = (isset($MMHospedagem_resultado->bank_slip)) ? $MMHospedagem_resultado->bank_slip->url_slip_pdf : $MMHospedagem_resultado->url_slip_pdf;

		        // Trata as strings para o template da mensagem
				$alteracao_stings_msg_whatsapp = array(
					"{MMHospedagem_NomeCliente}",
					"{MMHospedagem_NumeroFatura}",
					"{MMHospedagem_ValordaFatura}",
					"{MMHospedagem_DataCriacaoFatura}",
					"{MMHospedagem_DataVencimentoFatura}",
					"{MMHospedagem_CodBarra}",
					"{MMHospedagem_Assinatura}"
				);

				$alteracao_stings_msg_whatsapp_novos = array(
					$primeironome,//$primeironome . ' ' . $sobrenome
					$invoice_id,
					'R$' . $valordafaturacomvirgula,
					$datadecriacaofatura[2] . '/' . $datadecriacaofatura[1] . '/' . $datadecriacaofatura[0],
					$datadevencimentofatura[2] . '/' . $datadevencimentofatura[1] . '/' . $datadevencimentofatura[0],
					$CODIGO_DE_BARRA,
					$whatsapp_Assinatura_mensagem
				);

				$MMHospedagem_Whatsapp_Mensagem = str_replace(
					$alteracao_stings_msg_whatsapp,
					$alteracao_stings_msg_whatsapp_novos,
					$corpo_mensagem
				);


		        if(($URL_PDF != "") && ($mmhospedagem_numerowhatsapp != "") && ($mmhospedagem_numerowhatsapp != "0")) {

		        	// Aqui se integra com a API

		        	try {

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

					} catch (\Exception $e) {
		                echo "Unable to create my_table: {$e->getMessage()}";
		            }

		        	try {

		                // Sistema para envio de arquivo
		                // Descomentar caso necessario!
		                $data_imagem = new DateTime();
		                $extensao_imagem = substr($PostsWhsatapp['urlImagem'], -3);
		                $tratar_PDF_whatsapp_mmhospedagem = md5($PostsWhsatapp['urlImagem'] . $data_imagem->format('d-m-Y H:i:s')) . '.pdf';

		                $PostsWhsatapp = http_build_query(
		                    array(
		                        'NumeroWhatsapp' => ltrim(preg_replace('/\D/', '', $mmhospedagem_numerowhatsapp), 0),
		                        'TipoMensagem' => '2', /* Use 1 para texto e 2 para Arquivo */
		                        'Mensagem' => 'Boleto_' . $tratar_PDF_whatsapp_mmhospedagem,

		                        // Sistema para envio de arquivo
		                        // Use UrlImagem para enviar uma imagem
		                        // Descomentar caso necessario!
		                        'UrlImagem' => $URL_PDF,
		                        'TipoArquivoEnvio' => 'Arquivo',
		                        'ConverteArquivoLocal' => 'Boleto_' . $tratar_PDF_whatsapp_mmhospedagem

		                    )
		                );

		                $MetodoEnvio = array('http' => array('method'  => 'POST', 'header'  => 'Content-type: application/x-www-form-urlencoded', 'content' => $PostsWhsatapp));
		                $ConteudoXML  = stream_context_create($MetodoEnvio);
		                $result = file_get_contents(str_replace('https', 'http', $MMHospedagem_WHMCS_URL) . "/modules/addons/mmhospedagem_whatsapp/mmhospedagem_api/mmhospedagem?api_whatsapp=enviarArquivo", false, $ConteudoXML);

		            } catch (\Exception $e) {
		                echo "Unable to create my_table: {$e->getMessage()}";
		            }

		        }

	    	}

	    }

	    $merge_fields = [];
		return $merge_fields;

	}
}

return $hook;