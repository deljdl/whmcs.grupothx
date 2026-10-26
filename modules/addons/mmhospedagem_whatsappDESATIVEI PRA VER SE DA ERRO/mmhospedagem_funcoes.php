<?php

require_once("../../../init.php");
use WHMCS\Database\Capsule;

/////////////////////////////////////////////////////////////////////////////
// Sistema de validação /////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////

global $ID_Cliente_Whatsapp;
global $mmhospedagem_whatsapp_numero_whatsapp;
global $mmhospedagem_whatsapp_nomecliente_whatsapp;

$ID_Cliente_Whatsapp = $_POST['cod_cliente'];

// Verificações das tabelas
foreach (WHMCS\Database\Capsule::table('mmhospedagem_whatsapp_validacao')->WHERE('MMHospedagem_ID_WHMCS_CLIENTE', $ID_Cliente_Whatsapp)->get() as $modulowhatsapp_validacao_dados_cliente) {
    $mmhospedagem_whatsapp_codigo_validacao = $modulowhatsapp_validacao_dados_cliente->MMHospedagem_COD_VALIDACAO;
    $mmhospedagem_whatsapp_codigo_validacao_status_restatus = $modulowhatsapp_validacao_dados_cliente->MMHospedagem_STATUS_VALIDACAO;
    $mmhospedagem_whatsapp_numero_whatsapp = $modulowhatsapp_validacao_dados_cliente->MMHospedagem_NUMERO_WHATSAPP;
    $mmhospedagem_whatsapp_nomecliente_whatsapp = $modulowhatsapp_validacao_dados_cliente->MMHospedagem_NOME_WHMCS_CLIENTE;
}

global $lista_mensagens;

/////////////////////////////////////////////////////////////////////////////
// Gera um codigo aleatorio /////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////


if($_POST['whatsapp_action'] == 'whatsapp_campanha_geral'){

	$nome_modulo_buscado = 'Whatsapp API DEDICADO';

    $query2 = "SELECT value FROM tblconfiguration WHERE setting = 'SystemURL' ORDER BY id DESC LIMIT 1";
    $result2 = mysql_query($query2);

    $URL_Sistema_WHMCS_MySQL = mysql_fetch_row($result2);
    $URL_Montado_API_Whatsapp = str_replace('https', 'http', $URL_Sistema_WHMCS_MySQL[0]);

    foreach (WHMCS\Database\Capsule::table('mmhospedagem_whatsapp_configuracao')->WHERE('MMHospedagem_Nome_Modulo', $nome_modulo_buscado)->get() as $modulowhatsapp_configuracao) {
        $whatsapp_origem = $modulowhatsapp_configuracao->mmhospedagem_origem_numero_whatsap;
        $whatsapp_redicionamento_cliente = $modulowhatsapp_configuracao->mmhospedagem_redirecionar_cliente;
    }

    // Verifica o status da conta
    if($_POST['status_conta'] == '1') {
    	$status = "AND A.status = 'Active'";
    } elseif($_POST['status_conta'] == '2') {
    	$status = "AND A.status = 'Inactive'";
    }elseif($_POST['status_conta'] == '3') {
    	$status = "AND A.status = 'Closed'";
    }elseif($_POST['status_conta'] == '4') {
    	$status = "";
    }

	$query = "SELECT A.id, A.firstname, A.lastname, A.email, A.status, B.value FROM tblclients A, tblcustomfieldsvalues B
				WHERE A.id = B.relid
				$status
				AND B.fieldid = '$whatsapp_origem'";

	$result = mysql_query($query);

	while($consulta = mysql_fetch_assoc($result)){
        $mensagem_consular[$consulta['id']] = $consulta;
    }

    $lista_mensagens = array_merge($mensagem_consular);

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


    foreach($lista_mensagens as $k=>$v){

            // API Sem Imagem


            $PostsWhsatapp = http_build_query(
                array(
                    'NumeroWhatsapp' => ltrim(preg_replace('/\D/', '', '55' . $v['value']), 0),
                    'TipoMensagem' => '1',
                    'Mensagem' => $_POST['mensagem_marketing']
                )
            );

            $MetodoEnvio = array('http' => array('method'  => 'POST', 'header'  => 'Content-type: application/x-www-form-urlencoded', 'content' => $PostsWhsatapp));
            $ConteudoXML  = stream_context_create($MetodoEnvio);
            $result = file_get_contents(mmhospedagem_api_whatsapp(), false, $ConteudoXML);


    }

    try {

        $mmalertaitaushopline = header('Location: ./addonmodules.php?module=mmhospedagem_whatsapp&alerta=sucesso');
        return $mmalertaitaushopline;

    } catch (\Exception $e) {
        echo $e->getMessage();
        exit();
        $mmalertaitaushopline = header('Location: ./addonmodules.php?module=mmhospedagem_whatsapp&alerta=erro');
        return $mmalertaitaushopline;
    }

}


if($_POST['codigo_whatsapp'] > '' && $ID_Cliente_Whatsapp > '') {

	if($mmhospedagem_whatsapp_codigo_validacao_status_restatus == '3' && $mmhospedagem_whatsapp_codigo_validacao == $_POST['codigo_whatsapp']) {

		// Envia uma msg para o cliente com o codigo!
		 	try {

				Capsule::connection()->transaction(function ($connectionManager) { 

					global $ID_Cliente_Whatsapp;
					global $mmhospedagem_whatsapp_numero_whatsapp;
					global $mmhospedagem_whatsapp_nomecliente_whatsapp;			

					$connectionManager->table('mmhospedagem_whatsapp_validacao')->WHERE('MMHospedagem_ID_WHMCS_CLIENTE', $ID_Cliente_Whatsapp)->update(
						[
						    'MMHospedagem_STATUS_VALIDACAO' => '4'
						]
					);

					$mmhospedagem_ini_array = parse_ini_file("mmhospedagem_mensagens/Msg_Validacao.MMHospedagem", true);

					$alteracao_stings_msg_whatsapp = array("{MMHospedagem_NomeCliente}");
					$alteracao_stings_msg_whatsapp_novos = array($mmhospedagem_whatsapp_nomecliente_whatsapp);

					$mensagem = str_replace($alteracao_stings_msg_whatsapp, $alteracao_stings_msg_whatsapp_novos, $mmhospedagem_ini_array['Mensagens']['MMHospedagem_Msg_FinalizacaoValidacao']);


					// Aqui se integra com a API

					$PostsWhsatapp = http_build_query(
					    array(
					        'NumeroWhatsapp' => $mmhospedagem_whatsapp_numero_whatsapp,
					        'Mensagem' => $mensagem
					    )
					);

					$MetodoEnvio = array('http' => array('method'  => 'POST', 'header'  => 'Content-type: application/x-www-form-urlencoded', 'content' => $PostsWhsatapp));
					$ConteudoXML  = stream_context_create($MetodoEnvio);
					$result = file_get_contents(mmhospedagem_api_whatsapp(), false, $ConteudoXML);


				});

				echo '<div style="width: 100%; text-align: center; font-size: 12px; background-color: #1FC063; padding: 10px; margin-bottom: 11px; border-radius: 5px; color: #FFF;"><i class="fas fa-check" style="margin-right: 5px;"></i> Sua conta foi validada com sucesso!</div>';


				echo '<script language= "JavaScript">
						setTimeout("document.location = \'./clientarea.php\'",1);
					</script>';

			} catch (\Exception $e) {
			    echo $e->getMessage();
			    exit();
			}



	} else {

		echo '<div style="width: 100%; text-align: center; font-size: 12px; background-color: #FF803C; padding: 10px; margin-bottom: 11px; border-radius: 5px; color: #FFF;"><i class="fas fa-exclamation-triangle" style="margin-right: 5px;"></i> O código informado e invalido!</div>';

	}
 	

} 