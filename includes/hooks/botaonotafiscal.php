<?php

use WHMCS\Database\Capsule;

add_hook('AdminAreaHeaderOutput', 1, function($vars)
{

    
    echo '<pre>';
    var_dump($_GET);
    echo '</pre>';
    echo $_SERVER['SCRIPT_FILENAME'];
    if (!empty($_GET['verificar_nota'])) {
        $idFaturaParaEnvio = $_GET['verificar_nota'];
        $arquivoName = md5(NOTAS_TOKEN . $idFaturaParaEnvio) . '.pdf';
        $pathSavedPdfNota = ROOT_WHMCS . 'thx/notasfiscais/' . $arquivoName;

        header('Content-Type: application/json');
        echo json_encode(['existe' => file_exists($pathSavedPdfNota)]);
        exit;
    }




    $debugMail = '';
    $pathSavedPdfNota = false;
    if ($vars['filename'] == 'invoices' || $vars['filename'] == 'clientsinvoices' || (isset($vars['pageicon']) && $vars['pageicon'] == 'invoices'))
    {
        require_once(dirname(dirname(__DIR__)) . '/tarifasTHX.php');

        function urlAtual()
        {
            {
                $protocolo =  isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? "https://" : 'http://';
                $retorno = $protocolo.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
                return $retorno;
            }
        }

        if(! empty($_GET['checarnota']))
        {
            $idFaturaParaEnvio = $_GET['checarnota'];
            $arquivoName = md5(NOTAS_TOKEN.$idFaturaParaEnvio).'.pdf';
            $pathSavedPdfNota = ROOT_WHMCS.'/thx/notasfiscais/'.$arquivoName;

            $blocoGerarNota = '
            <div class="col-md-12" style="margin-bottom:15px;">
                <div style="padding:15px;border:1px solid #ddd;border-radius:8px;background:#f8f9fa;">

                    <h4 style="margin-top:0;">
                        <i class="fas fa-sync-alt text-warning"></i>
                        Gerar nota fiscal
                    </h4>

                    <p style="margin-bottom:15px;">
                        Gere ou regenere a nota fiscal desta fatura.
                    </p>

                    '.botaoNFSE($_GET['checarnota']).'<br /><br />

                </div>
            </div>';

            if(file_exists($pathSavedPdfNota))
            {
                die('
                <div class="row" style="margin-top:15px;">

                    <div class="col-md-6" style="margin-bottom:15px;">
                        <div style="padding:15px;border:1px solid #ddd;border-radius:8px;background:#f8f9fa;">

                            <h4 style="margin-top:0;">
                                <i class="fas fa-check-circle text-success"></i>
                                Nota já enviada
                            </h4>

                            <p style="margin-bottom:15px;">
                                A nota <strong>#'.$_GET['checarnota'].'</strong> já foi enviada.
                            </p>

                            <a class="btn btn-primary" href="'.urlAtual().'&reenviar=true">
                                <i class="fas fa-file-invoice" style="margin-right:5px;"></i>
                                Reenviar a mesma nota
                            </a>

                        </div>
                    </div>

                    <div class="col-md-6" style="margin-bottom:15px;">
                        <div style="padding:15px;border:1px solid #ddd;border-radius:8px;background:#f8f9fa;">

                            <h4 style="margin-top:0;">
                                <i class="fas fa-file-pdf text-success"></i>
                                Nota enviada
                            </h4>

                            <p style="margin-bottom:15px;">
                                Visualizar a nota fiscal já enviada.
                            </p>

                            <a class="btn btn-success" target="_blank" href="'.HTTP_WHMCS.'notasfiscais/'.$arquivoName.'">
                                <i class="fas fa-file-invoice" style="margin-right:5px;"></i>
                                Abrir Nota Enviada
                            </a>

                        </div>
                    </div>

                    '.$blocoGerarNota.'

                </div>');
            }
            else
            {
                die('
                <div class="row" style="margin-top:15px;">

                    <div class="col-md-6" style="margin-bottom:15px;">
                        <div style="padding:15px;border:1px solid #ddd;border-radius:8px;background:#f8f9fa;">

                            <h4 style="margin-top:0;">
                                <i class="fas fa-exclamation-circle text-danger"></i>
                                Nota ainda não enviada
                            </h4>

                            <p style="margin-bottom:0;">
                                A nota <strong>#'.$_GET['checarnota'].'</strong> ainda <strong>não</strong> foi enviada.
                            </p>

                        </div>
                    </div>

                    '.$blocoGerarNota.'

                </div>');
            }

        }



        if(!empty($_FILES['notafiscal']))
        {
            $idFaturaParaEnvio = $_POST['iddafatura'];
            $arquivoName = md5(NOTAS_TOKEN.$idFaturaParaEnvio).'.pdf';
            $pathSavedPdfNota = ROOT_WHMCS.'thx/notasfiscais/'.$arquivoName;
            move_uploaded_file($_FILES['notafiscal']['tmp_name'], $pathSavedPdfNota);
        }

        $linkNota = getUrlCurta(HTTP_WHMCS."notasfiscais/{$arquivoName}");


        if($pathSavedPdfNota)
        {

            $dadosCliente = Capsule::select("SELECT * FROM tblinvoices
                                                INNER JOIN tblclients ON tblinvoices.userid = tblclients.id
                                                        WHERE tblinvoices.id =  {$idFaturaParaEnvio}");

            $dadosContato = Capsule::select("SELECT * FROM tblcustomfieldsvalues WHERE relid =  {$dadosCliente[0]->userid} AND fieldid IN(2, 1) ORDER BY fieldid");




            $whatsapp = isset($dadosContato[0]->value) && $dadosContato[0]->value != 'on' ? $dadosContato[1]->value : false;


            $contatosCliente = Capsule::select("SELECT * FROM tblcontacts WHERE userid =  {$dadosCliente[0]->userid}");

            if($whatsapp)
            {

                $msgWhats = "
                Olá *{$dadosCliente[0]->firstname}* 👋,

Sua *Nota Fiscal* referente aos serviços da fatura *#{$idFaturaParaEnvio}*
no valor de *R$ {$dadosCliente[0]->total}* já foi emitida e enviada aqui e *em seu email*.

*Você também pode baixar sua nota fiscal clicando no link abaixo:*
{$linkNota}";
                if(DEBUG_NOTAS)
                {
                    enviaWhatsApp(WHATSAPP_ADMIN_JARDEL , $msgWhats);
                    $debugMail.= "<br />Whatsapp enviado para: {$whatsapp} {$dadosCliente[0]->firstname}";
                }
                else
                {
                    enviaWhatsApp($whatsapp , $msgWhats);
                    $debugMail.= "<br />Whatsapp enviado para: {$whatsapp} {$dadosCliente[0]->firstname}";
                }

            }





            $invoiceItens = Capsule::select("SELECT * FROM tblinvoiceitems WHERE invoiceid =  {$idFaturaParaEnvio}");


            $itensdafatura = '
            <table style="color: #666; font-size: 14px; width: 100%;" border="0" cellspacing="0" cellpadding="5" align="center">
                <tbody>
                    <tr class="tabela">
                        <td style="background-color: #1a3c6a; color: #fff; padding-left: 15px;" height="23"><strong>Itens da Fatura</strong></td>
                    </tr>
                </tbody>
            </table>';

            foreach($invoiceItens as $item)
            {
                $itensdafatura.= '
                <table style="width: 100%; margin: 0;">
                    <tbody>
                    <tr>
                    <td style="text-align: left; padding: 3px; border: solid #CCC 1px;"><strong>'.$item->description.'</strong></td>
                    <td style="text-align: right; padding: 3px; border: solid #CCC 1px; width: 100px;"><strong>R$ '.$item->amount.'</strong></td>
                    </tr>
                    </tbody>
                </table>';
            }

            $itensdafatura.='
            <table style="width: 100%; margin: 0;">
                <tbody>
                    <tr>
                        <td style="text-align: right; padding: 3px; border: solid #CCC 1px;"><strong>Total</strong></td>
                        <td style="text-align: right; padding: 3px; border: solid #CCC 1px; width: 100px;"><strong> R$ '.$dadosCliente[0]->total.'</strong></td>
                    </tr>
                </tbody>
            </table>';


            $dadoscabecalho = Capsule::select("SELECT value FROM tblconfiguration WHERE setting IN('EmailGlobalHeader', 'EmailGlobalFooter') ORDER BY setting");
            $html = html_entity_decode($dadoscabecalho[1]->value);
            $html.='
            <table style="font-size: 14px; color: #666; width: 100%;" border="0" cellspacing="0" cellpadding="0">
                <tbody>
                    <tr>
                    <td width="100%">
                    <h3 id="grupothx_emails_titulo" class="grupothx_emails_titulo">Olá, '.$dadosCliente[0]->firstname.' :)</h3>
                    <p>Segue anexo a <strong>Nota Fiscal</strong> referente aos serviços da fatura <strong>#'.$idFaturaParaEnvio.'</strong>.</p>
                    <p>Você também pode baixar sua nota fiscal <strong><a target="_blank" href="'.$linkNota.'">clicando aqui</a>.</strong></p>
                    '.$itensdafatura.'
                    </td>
                    </tr>
                </tbody>
            </table>';



            $html.= html_entity_decode($dadoscabecalho[0]->value);




            $msg = '';
            $msg = '<div class="successbox"><strong><span class="title">Nota enviada com sucesso</span></strong><br>A nota fiscal foi enviada para o cliente</div>';
            require_once($_SERVER['DOCUMENT_ROOT'].'/thx/vendor/phpmailer/phpmailer/PHPMailerAutoload.php');
            $email = new PHPMailer();
            $email->WordWrap = 50;               // set word wrap
            $email->Priority = 1;
            $email->IsHTML(true);
            $email->From      = 'financeiro@grupothx.com.br';
            $email->FromName  = 'Grupo THX';
            $email->Subject   = 'Grupo THX - Nota Fiscal da Fatura '.$idFaturaParaEnvio;
            $email->Body      = utf8_decode($html);

            if(DEBUG_NOTAS)
            {
                $emailRand = 'deljdl+'.rand(1,1000).'@gmail.com';
                $email->AddAddress($emailRand, $dadosCliente[0]->firstname);
                $debugMail.= "<br />Email enviado para CLIENTE: {$dadosCliente[0]->email} {$dadosCliente[0]->firstname}";
            }
            else
            {
                $email->AddAddress( $dadosCliente[0]->email, $dadosCliente[0]->firstname);
                $debugMail.= "<br />Email  enviado para CLIENTE: : {$dadosCliente[0]->email} {$dadosCliente[0]->firstname}";
            }

            $email->AddBcc( 'deljdl@gmail.com', $dadosCliente[0]->firstname);


            foreach($contatosCliente as $contato)
            {

                if(DEBUG_NOTAS)
                {
                    $emailRand = 'deljdl+'.rand(1,1000).'@gmail.com';
                    $email->AddBcc($emailRand, 'CONTATO: '.$contato->firstname);
                    $debugMail.= "<br />Email enviado para CONTATO: {$contato->email} {$contato->firstname}";
                }
                else
                {
                    $emailRand = 'deljdl+'.rand(1,1000).'@gmail.com';
                    $email->AddBcc($contato->email,$contato->firstname);
                    $debugMail.= "<br />Email enviado para CONTATO: {$contato->email} {$contato->firstname}";
                }

            }
            $email->AddAttachment( $pathSavedPdfNota , 'Grupo THX - Nota Fiscal da Fatura '.$idFaturaParaEnvio.'.pdf' );

            $email->Send();

            alexa('Nota Fiscal da fatura '.$idFaturaParaEnvio.' enviada para '.$dadosCliente[0]->firstname);

            if(DEBUG_NOTAS)
            {
                enviaLogTHXJdl($debugMail);
            }

            die("<script>top.location='".HTTP_WHMCS_ADMIN."clientsinvoices.php?userid={$dadosCliente[0]->userid}&notaEnviada={$idFaturaParaEnvio}'</script>");
        }

        $notaEnviada = !empty($_GET['notaEnviada']) ? $_GET['notaEnviada'] : 0;
        $output .= <<<HTML
                    NOTANOTANOTA
                    <div class="modal whmcs-modal fade" id="modalNotas" role="dialog">
                        <div class="modal-dialog">
                            <div class="modal-content panel panel-primary">
                                <div class="modal-header panel-heading">
                                <button type="button" class="close" data-dismiss="modal" style="color: #FFFFFF;">&times;</button>
                                <h4 class="modal-title">Envio de Nota Fiscal para Fatura <span id="faturaselecionada"></span></h4>
                                </div>
                                <div class="modal-body panel-body">
                                    <form id="sendnotafiscal" action="">

                                        <div class="row" style="margin-top:15px;">

                                            <div class="col-md-12">
                                                <div style="padding:15px;border:1px solid #ddd;border-radius:8px;background:#f8f9fa;">

                                                    <h4 style="margin-top:0;">
                                                        <i class="fas fa-file-upload text-primary"></i>
                                                        Enviar nova nota fiscal
                                                    </h4>

                                                    <p style="margin-bottom:15px;">
                                                        Selecione o PDF da nota fiscal para envio manual.
                                                    </p>

                                                    <div class="form-group" style="margin-bottom:15px;">
                                                        <label style="font-weight:600;display:block;margin-bottom:8px;">
                                                            Procurar PDF da Nota Fiscal:
                                                        </label>

                                                        <input
                                                            type="file"
                                                            name="notafiscal"
                                                            required
                                                            class="form-control"
                                                        >
                                                    </div>

                                                    <button class="btn btn-primary" type="submit" style="width: 100%;">
                                                        <i class="fas fa-paper-plane" style="margin-right:5px;"></i>
                                                        Enviar Nova Nota Fiscal
                                                    </button>

                                                    <input
                                                        type="hidden"
                                                        name="iddafatura"
                                                        id="iddafatura"
                                                        value=""
                                                        required
                                                    >

                                                </div>
                                            </div>

                                        </div>

                                    </form>

                                    <div class="row" style="margin-top: 10px; padding:5px 20px" id="notajaenviada"></div>
                                </div>
                                <div class="modal-footer panel-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <script type="text/javascript">
                        function openModalNota(id)
                        {
                            //console.log(window.location.href+'&checarnota='+id)

                            $.get(window.location.href+'&checarnota='+id, function( data ) {
                                $('#notajaenviada').html(data);

                                document.getElementById("sendnotafiscal").setAttribute("method", "POST");
                                document.getElementById("sendnotafiscal").setAttribute("enctype", "multipart/form-data");
                                document.getElementById("sendnotafiscal").setAttribute("action", window.location.href);


                                $("#iddafatura").val(id);
                                $("#faturaselecionada").text(id);
                                $('#modalNotas').modal();

                            });
                        }
                    	$(document).ready(function() {
    btnota = '\
    <div style="text-align: right; padding: 10px 250px 0 0;">\
        <a class="btn btn-primary" style="color: #FFFFFF;font-size: 15px;cursor:pointer;margin: 0 5px 0 0;" title="Enviar Nota Fiscal {$_GET['id']}" href="javascript:openModalNota({$_GET['id']})"><i class="fas fa-file-invoice" title="Reenviar Nota Fiscal {$_GET['id']}"></i> Enviar Nota</a>\
    </div>';

    if (document.getElementById("tab1")) {
        $('#tab1').append(btnota);
    } else {
        notaEnviada = {$notaEnviada};
        var rows = $('.datatable td').first().closest('tbody').children('tr');

        $.each(rows, function(i, e) {
            $(e).children('*').last().clone().html('').insertBefore($(e).children('*').last());
        });

        rows.first().children('th').eq(-2).text('');

        $('.datatable tr').each(function() {
            let id = $(this).find('td:last').find('a').attr('onclick');
            let ida = String(id);
            let idfatura = ida.replace("doDelete('", '').replace("');return false", '');

            let button = $(this).find("td:eq(9)");
            button.css('text-align', 'center');

            // Carrega o botão inicialmente como azul
            let botao = $('<a class="btn btn-primary abrir-modal-nota" data-id="' + idfatura + '" style="color: #FFFFFF;font-size: 10px;cursor:pointer;float: left;margin: 0 5px 0 0;" title="Enviar Nota Fiscal ' + idfatura + '"><i class="fas fa-file-invoice"></i> Enviar Nota</a>');

            button.html(botao);

            // Faz a verificação da nota em background (assíncrono)
            $.getJSON(window.location.href + '&verificar_nota=' + idfatura, function(response) {
                if (response.existe) {
                    botao.removeClass('btn-primary').addClass('btn-success'); // Apenas troca a cor do botão
                }
            });
        });

        // Adiciona evento para abrir o modal ao clicar no botão
        $(document).on("click", ".abrir-modal-nota", function() {
            let idfatura = $(this).data("id");
            openModalNota(idfatura);
        });
    }

    {$jsListInvoices}
});



                    </script>
HTML;

        return $output;
    }
});