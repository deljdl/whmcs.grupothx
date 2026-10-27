<?php

use WHMCS\Database\Capsule;

add_hook('AdminAreaHeaderOutput', 1, function($vars)
{
    if ($vars['filename'] != 'clientsservices') {
        return;
    }

    $idServico = 0;

    if (isset($_GET['id'])) {
        $idServico = (int)$_GET['id'];
    } elseif (isset($_GET['productselect'])) {
        $idServico = (int)$_GET['productselect'];
    }

    if(!$idServico) {
        return;
    }

    $hospedagem = getDadosHospedagemById($idServico);

    $textoBotao = '<i class="fas fa-hdd"></i>';
    $titleBotao = 'Gerenciar Espaço em Disco';

    if ($hospedagem)
    {
        $server = Capsule::table('tblservers')
            ->where('id', $hospedagem['serverid'])
            ->first();

        if ($server)
        {
            $quota = consultarQuotaWHM($server, $hospedagem['username']);

            if (!empty($quota['success']))
            {
                $livre = round(
                    $quota['disklimit_gb'] - $quota['diskused_gb'],
                    2
                );

                $textoBotao .= ' '
                    . $quota['percentual']
                    . '% de '
                    . number_format($quota['disklimit_gb'], 0, ',', '.')
                    . ' GB';

                $titleBotao =
                'Espaço em Disco&#10;&#10;' .
                'Utilizado: ' . number_format($quota['diskused_gb'], 2, ',', '.') . ' GB&#10;' .
                'Disponível: ' . number_format($livre, 2, ',', '.') . ' GB&#10;' .
                'Limite: ' . number_format($quota['disklimit_gb'], 2, ',', '.') . ' GB&#10;' .
                'Uso: ' . $quota['percentual'] . '%';
            }
        }
        else
        {
            $textoBotao .= ' Servidor não encontrado';
            $titleBotao = 'Servidor não encontrado';
        }
    }

    return '
    <div class="modal fade" id="modalDisco">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4>Espaço em Disco</h4>
                </div>

                <div class="modal-body" id="conteudoDisco">
                    Carregando...
                </div>

            </div>
        </div>
    </div>

    <script>
    $(function(){

        $("#modcmdbtns").append(
            \'<button type="button"  style="background:#337ab7;color:#fff;margin:3px;" class="btn btn-default" id="btnDisco" title="' . $titleBotao . '">' . $textoBotao . '</button>\'
        );

        
        $(document).on("click", "#btnDisco", function(){

            $("#conteudoDisco").html(
                \'<div style="text-align:center;padding:40px;">\' +
                    \'<i class="fa fa-spinner fa-spin fa-2x"></i>\' +
                    \'<div style="margin-top:10px;">Carregando configurações de disco...</div>\' +
                \'</div>\'
            );

            $("#modalDisco").modal("show");

            $.get(
                "update-disk-quota-core.php?id=' . $idServico . '",
                function(html){
                    $("#conteudoDisco").html(html);
                }
            );

        });


    });
    </script>';
});