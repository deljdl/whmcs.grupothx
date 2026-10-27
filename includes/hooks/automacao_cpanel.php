<?php
//automacao_cpanel.php
//hook para adicionar o botão na página de detalhes do serviço

use WHMCS\Database\Capsule;

add_hook('AdminAreaHeaderOutput', 1, function($vars)
{
    
    #########    REQUIRE SEGURO DO ARQUIVO tarifasTHX.php
    if(!defined('ROOT_WHMCS'))
    {
        return 'Constante ROOT_WHMCS não definida.';
    }

    $configTHX = ROOT_WHMCS . '/tarifasTHX.php';
    if(!file_exists($configTHX))
    {
        return 'Arquivo de configuração não encontrado: '.$configTHX;
    }
    require_once $configTHX;
    #########    FIM REQUIRE SEGURO DO ARQUIVO tarifasTHX.php

    if($vars['filename'] != 'clientsservices')
    {
        return;
    }


    $idServico = 0;

    if(isset($_GET['id']))
    {
        $idServico = (int)$_GET['id'];
    }
    elseif(isset($_GET['productselect']))
    {
        $idServico = (int)$_GET['productselect'];
    }

    if(!$idServico)
    {
        return;
    }


    $textoBotao = '<i class="fa fa-cog"></i>';
    $titleBotao = 'Gerenciar cPanel';


    $hospedagem = getDadosHospedagemById($idServico);

    if($hospedagem)
    {
        $server = Capsule::table('tblservers')
            ->where('id', $hospedagem['serverid'])
            ->first();

        if($server)
        {
            $quota = consultarQuotaWHM($server, $hospedagem['username']);

            $textoBotao = '<i class="bx bxs-package"></i> cPanel';

            $titleBotao =
                'Gerenciar Configuração cPanel'.
                '&#10;&#10;Domínio: '.$hospedagem['domain'].
                '&#10;Plano: '.$quota['dados']['plan'].
                '&#10;Usuário: '.$quota['dados']['user'];
        }
    }


    return '
    <div class="modal fade" id="modalCpanel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4>Gerenciar Pacote cPanel</h4>
                </div>

                <div class="modal-body" id="conteudoCpanel">
                    Carregando...
                </div>

            </div>
        </div>
    </div>

    <script>
    $(function(){

        $("#modcmdbtns").append(
            \'<button type="button" style="background: #337ab7;color: white;margin: 3px;" class="btn btn-default" id="btnCpanel" title="'.$titleBotao.'">'.$textoBotao.'</button>\'
        );

        $(document).on("click", "#btnCpanel", function(){

        $("#conteudoCpanel").html(
            \'<div style="text-align:center;padding:40px;">\' +
                \'<i class="fa fa-spinner fa-spin fa-2x"></i>\' +
                \'<div style="margin-top:10px;">Carregando configurações cPanel...</div>\' +
            \'</div>\'
        );

        $("#modalCpanel").modal("show");

        $.get(
            "automacao-cpanel-core.php?id='.$idServico.'",
            function(html){

                $("#conteudoCpanel").html(html);

            }
        );

    });

    });
    </script>';
});