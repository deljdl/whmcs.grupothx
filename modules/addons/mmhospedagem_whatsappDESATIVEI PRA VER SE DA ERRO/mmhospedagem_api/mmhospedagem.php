<?php

//////////////////////////////////////////////////////////////////////////////////////////
// Modulo desenvolvido pela MMHospedagem /////////////////////////////////////////////////
// A venda e copia deste modulo sem nossa autorização e totalmente proibida! /////////////
// Versão: 1.0.0 BETA ////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////

require_once("../../../../init.php");

//////////////////////////////////////////////////////////////////////////////////////////
// API Carbon ////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////

use Carbon\Carbon;

//////////////////////////////////////////////////////////////////////////////////////////
// API Laravel DataBase //////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////

use WHMCS\Database\Capsule;

global $numeroLicenca;
global $resultado;
global $ARRAY;
global $phone;
global $body;
global $tipo_mensagem;
global $urlImagem;
global $ArquivoLocalArquivo;
global $TipoArquivoEnvio;


if($_GET['api_whatsapp'] == 'enviarTexto') {

    global $numeroLicenca;
    global $resultado;
    global $ARRAY;
    global $phone;
    global $body;
    global $tipo_mensagem;

    // Envia a Msg

    header('Access-Control-Allow-Origin: *');
    header("Content-Type: application/json; charset=UTF-8");

    $phone = $_POST["NumeroWhatsapp"];
    $body = $_POST["Mensagem"];
    $tipo_mensagem = $_POST["TipoMensagem"];
    $urlImagem = $_POST["urlImagem"];

    if(strlen($_POST["NumeroWhatsapp"]) >= 12 || $_POST["NumeroWhatsapp"] > 55 || $_POST["Mensagem"]) {

        if(CheckLicencaWhatsapp() == "Ativo") {

            // Envia uma msg para o cliente com o codigo!

            try {

                Capsule::connection()->transaction(
                    function ($connectionManager)
                        {


                        global $ARRAY;
                        global $phone;
                        global $body;
                        global $resultado;
                        global $tipo_mensagem;

                        $resultado = 'Send';

                        $ARRAY = array(
                            'StatusLicenca' => CheckLicencaWhatsapp(),
                            'NumeroWhatsapp' => $phone,
                            'Mensagem' => $body,
                            'TipoMsg' => $tipo_mensagem,
                            'resultado' => $resultado
                        );

                        // Trata as strings para o template da mensagem
                        $alteracao_stings_msg_whatsapp = array(
                            "amp;"
                        );

                        $alteracao_stings_msg_whatsapp_novos = array(
                            ''
                        );

                        $MMHospedagem_Whatsapp_Mensagem = str_replace(
                            $alteracao_stings_msg_whatsapp,
                            $alteracao_stings_msg_whatsapp_novos,
                            $ARRAY['Mensagem']
                        );

                        $connectionManager->table('mmhospedagem_whatsapp_enviadas')->insert(
                            [
                                'mmhospedagem_data' => horaEnvioWhasapp(),
                                'mmhospedagem_destino' => $ARRAY['NumeroWhatsapp'],
                                'mmhospedagem_intervalo' => (int)4,
                                'mmhospedagem_msg' => $MMHospedagem_Whatsapp_Mensagem,
                                'mmhospedagem_tipo_msg' => (int)$ARRAY['TipoMsg'],
                                'mmhospedagem_status' => (int)0
                            ]
                        );
                    }
                );



            } catch (\Exception $e) {

                $resultado = 'MySQL Error';

                $ARRAY = array(
                    'StatusLicenca' => CheckLicencaWhatsapp(),
                    'NumeroWhatsapp' => $phone,
                    'Mensagem' => $body,
                    'resultado' => $resultado,
                    'erro' => $e->getMessage()
                );

            }


        } else {

            $resultado = 'Error';

            $ARRAY = array(
                'LicencaWhatsapp' => CheckLicencaWhatsapp(),
                'resultado' => $resultado
            );

        }

        echo json_encode($ARRAY);

    }

}


if($_GET['api_whatsapp'] == 'enviarArquivo') {

    global $numeroLicenca;
    global $resultado;
    global $ARRAY;
    global $phone;
    global $body;
    global $tipo_mensagem;
    global $urlImagem;
    global $ArquivoLocalArquivo;
    global $TipoArquivoEnvio;

    // Envia a Msg

    header('Access-Control-Allow-Origin: *');
    header("Content-Type: application/json; charset=UTF-8");

    $phone = $_POST["NumeroWhatsapp"];
    $body = $_POST["Mensagem"];
    $tipo_mensagem = $_POST["TipoMensagem"];
    $urlImagem = htmlspecialchars_decode($_POST["UrlImagem"]);
    $ArquivoLocalArquivo = $_POST["ConverteArquivoLocal"];
    $TipoArquivoEnvio = $_POST["TipoArquivoEnvio"];

    if(strlen($_POST["NumeroWhatsapp"]) >= 12 || $_POST["NumeroWhatsapp"] > 55 || $_POST["Mensagem"]) {

        if(CheckLicencaWhatsapp() == "Ativo") {

            // Envia uma msg para o cliente com o codigo!
            try {

                Capsule::connection()->transaction(
                    function ($connectionManager)
                    {

                        global $ARRAY;
                        global $phone;
                        global $body;
                        global $resultado;
                        global $tipo_mensagem;
                        global $urlImagem;
                        global $ArquivoLocalArquivo;
                        global $TipoArquivoEnvio;

                        $resultado = 'Send';
                        $data_imagem = new DateTime();
                        $extensao_imagem = substr($urlImagem, -3);
                        $tratar_imagem_whatsapp_mmhospedagem = md5($urlImagem . $data_imagem->format('d-m-Y H:i:s')) . '.' . $extensao_imagem;

                        $ARRAY = array(
                            'StatusLicenca' => CheckLicencaWhatsapp(),
                            'NumeroWhatsapp' => $phone,
                            'Mensagem' => $body,
                            'TipoMsg' => $tipo_mensagem,
                            'UrlImagem' => $urlImagem,
                            'ConverteArquivoLocal' => $ArquivoLocalArquivo,
                            'TipoArquivoEnvio' => $TipoArquivoEnvio,
                            'resultado' => $resultado
                        );

                        // Trata as strings para o template da mensagem
                        $alteracao_stings_msg_whatsapp = array(
                            "amp;"
                        );

                        $alteracao_stings_msg_whatsapp_novos = array(
                            ''
                        );

                        $MMHospedagem_Whatsapp_Mensagem = str_replace(
                            $alteracao_stings_msg_whatsapp,
                            $alteracao_stings_msg_whatsapp_novos,
                            $ARRAY['Mensagem']
                        );

                        $connectionManager->table('mmhospedagem_whatsapp_enviadas')->insert(
                            [
                                'mmhospedagem_data' => horaEnvioWhasapp(),
                                'mmhospedagem_destino' => $ARRAY['NumeroWhatsapp'],
                                'mmhospedagem_intervalo' => (int)4,
                                'mmhospedagem_msg' => $MMHospedagem_Whatsapp_Mensagem,
                                'mmhospedagem_tipo_msg' => (int)$ARRAY['TipoMsg'],
                                'mmhospedagem_url_arquivo' => htmlspecialchars_decode($ARRAY['UrlImagem']),
                                'mmhospedagem_arquivo_local' => $ARRAY['ConverteArquivoLocal'],
                                'mmhospedagem_tipo_arquivo' => $ARRAY['TipoArquivoEnvio'],
                                'mmhospedagem_status' => (int)0
                            ]
                        );
                    }
                );



            } catch (\Exception $e) {

                $resultado = 'MySQL Error';

                $ARRAY = array(
                    'StatusLicenca' => CheckLicencaWhatsapp(),
                    'NumeroWhatsapp' => $phone,
                    'Mensagem' => $body,
                    'resultado' => $resultado,
                    'erro' => $e->getMessage()
                );

            }


        } else {

            $resultado = 'Error';

            $ARRAY = array(
                'LicencaWhatsapp' => CheckLicencaWhatsapp(),
                'resultado' => $resultado
            );

        }

        echo json_encode($ARRAY);

    }

}


// Sistema de consulta MySQL

if($_GET['api_whatsapp'] == 'read_cx_entrada') {

    if(CheckLicencaWhatsapp() == "Ativo") {

        if (!defined('WHMCS')) {
            exit('Acesso restrito - Power by www.mmhospedagem.com.br');
        }


        if(!$_SESSION['adminid']) {

            echo 'Rala peito malandrinho! Acabei de pegar seu IP, MAC e serial da placa mãe e guardei aqui comigo!';

        } else {

            $query_mensagens_entrada = "SELECT * FROM mmhospedagem_whatsapp_entrada ORDER BY mmhospedagem_entrada_id DESC";

            $executa_query_mensagens_entrada = mysql_query($query_mensagens_entrada);

            $tr = mysql_num_rows($executa_query_mensagens_entrada); // verifica o número total de registros



            while ($dados = mysql_fetch_array($executa_query_mensagens_entrada)) {

                 $data[] = $dados;

            }

            print json_encode($data);

        }

    }

}

if($_GET['api_whatsapp'] == 'read_cx_enviadas') {

    if(CheckLicencaWhatsapp() == "Ativo") {

        if (!defined('WHMCS')) {
            exit('Acesso restrito - Power by www.mmhospedagem.com.br');
        }

        if(!$_SESSION['adminid']) {

            echo 'Rala peito malandrinho! Acabei de pegar seu IP, MAC e serial da placa mãe e guardei aqui comigo!';

        } else {

            $query_mensagens_enviadas = "SELECT * FROM mmhospedagem_whatsapp_enviadas ORDER BY mmhospedagem_enviadas_id DESC";

            $executa_query_mensagens_enviadas = mysql_query($query_mensagens_enviadas);

            $tr_msg_enviadas = mysql_num_rows($executa_query_mensagens_enviadas); // verifica o número total de registros

            while ($dados = mysql_fetch_array($executa_query_mensagens_enviadas)) {

                 $data[] = $dados;

            }

            print json_encode($data);

        }

    }

}

if($_GET['api_whatsapp'] == 'read_mensagens') {

    if(CheckLicencaWhatsapp() == "Ativo") {

        if (!defined('WHMCS')) {
            exit('Acesso restrito - Power by www.mmhospedagem.com.br');
        }

        if(!$_SESSION['adminid']) {

            echo 'Rala peito malandrinho! Acabei de pegar seu IP, MAC e serial da placa mãe e guardei aqui comigo!';

        } else {

            $query_mensagens_api = "SELECT * FROM mmhospedagem_whatsapp_mensagens ORDER BY mmhospedagem_msg_id DESC";

            $executa_query_mensagens_api = mysql_query($query_mensagens_api);

            $tr_msg_api = mysql_num_rows($executa_query_mensagens_api); // verifica o número total de registros

            while ($dados = mysql_fetch_array($executa_query_mensagens_api)) {

                 $data[] = $dados;

            }

            print json_encode($data);

        }

    }

}

if($_GET['api_whatsapp'] == 'pdf_fatura') {

    if(CheckLicencaWhatsapp() == "Ativo") {

        $id_fatura = $_GET['id'];

        try {

            header("Content-type:application/pdf");
            //header("Content-Disposition:attachment;filename=invoice_2442.pdf");

            $pdfdata = pdfInvoice($id_fatura);

            echo $pdfdata;

        }
        catch (Exception $e) {

          $apiresults = array(
            "result" => "error",
            "message" => $e->getMessage()
          );

          print_r($apiresults);

        }


    }

}



if($_GET['api_whatsapp'] == 'read_contatos') {

    if(CheckLicencaWhatsapp() == "Ativo") {

        if (!defined('WHMCS')) {
            exit('Acesso restrito - Power by www.mmhospedagem.com.br');
        }

        if(!$_SESSION['adminid']) {

            echo 'Rala peito malandrinho! Acabei de pegar seu IP, MAC e serial da placa mãe e guardei aqui comigo!';

        } else {

            $nome_modulo_buscado = 'Whatsapp API DEDICADO';
            //$iddocliente = $vars['userid'];

            foreach (WHMCS\Database\Capsule::table('mmhospedagem_whatsapp_configuracao')->WHERE('MMHospedagem_Nome_Modulo', $nome_modulo_buscado)->get() as $modulowhatsapp_configuracao) {
                $whatsapp_origem = $modulowhatsapp_configuracao->mmhospedagem_origem_numero_whatsap;
            }


            $query_lista_contatos = "SELECT mmhospedagem_contato_id, mmhospedagem_contato_numero, firstname, lastname, relid, mmhospedagem_data
                                        FROM tblcustomfieldsvalues A, tblclients B, mmhospedagem_whatsapp_contato C
                                        WHERE A.fieldid = '$whatsapp_origem'
                                        AND replace(replace(replace(replace(SUBSTR(C.mmhospedagem_contato_numero, 3),' ',''),'(',''),')',''),'-','') LIKE replace(replace(replace(replace(A.value,' ',''),'(',''),')',''),'-','')
                                        AND A.relid LIKE B.id
                                        AND B.status = 'Active'
                                        ORDER BY mmhospedagem_contato_id DESC";

            $executa_query_lista_contatos = mysql_query($query_lista_contatos);

            $tr_lista_contatos = mysql_num_rows($executa_query_lista_contatos); // verifica o número total de registros

            while ($dados = mysql_fetch_array($executa_query_lista_contatos)) {

                //$data[] = $dados;


                $data[] = $dados;

            }

            print json_encode($data);
            //print json_encode($sistema_contatos);

        }

    }

}