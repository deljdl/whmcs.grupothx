<?php
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



	$textoBotao = '<i class="fa fa-envelope-o"></i>';
	$titleBotao = 'Gerenciar E-mails';

	$hospedagem = getDadosHospedagemById($idServico);

	if ($hospedagem)
	{
		$server = Capsule::table('tblservers')
			->where('id', $hospedagem['serverid'])
			->first();

		if ($server)
		{
			$quota = consultarQuotaWHM($server, $hospedagem['username']);

			$emails = getEmailsCpanel($hospedagem, $server);

			$totalEmails = count($emails['retorno']['result']['data']);

			$maxEmails = $quota['dados']['maxpop'];
			$maxEmailsBotao = ($maxEmails == 'unlimited') ? '∞' : $maxEmails;

			$textoBotao = '<i class="fa fa-envelope-o"></i> '.$totalEmails.'/'.$maxEmailsBotao;

			$titleBotao =
    'Gerenciar Contas de E-mail'.
    '&#10;&#10;Domínio: '.$hospedagem['domain'].
    '&#10;Plano: '.$quota['dados']['plan'].
    '&#10;Usuário: '.$quota['dados']['user'].
    '&#10;&#10;Contas: '.$totalEmails.' de '.$maxEmails.
    '&#10;Conta principal: '.$hospedagem['username'].
    '&#10;Quota por conta: '.($quota['dados']['max_emailacct_quota'] == 'unlimited' ? 'Ilimitada' : $quota['dados']['max_emailacct_quota']).
    '&#10;Envios por hora: '.$quota['dados']['max_email_per_hour'].
    '&#10;Listas de e-mail: '.$quota['dados']['maxlst'].
    '&#10;&#10;Espaço em disco: '.number_format($quota['diskused_gb'], 2, ',', '.').' GB de '.number_format($quota['disklimit_gb'], 2, ',', '.').' GB ('.$quota['percentual'].'%)'.
    '&#10;Inodes: '.number_format($quota['dados']['inodesused'], 0, ',', '.').
    (($quota['dados']['inodeslimit'] != 'unlimited')
        ? ' de '.number_format($quota['dados']['inodeslimit'], 0, ',', '.')
        : ' (Ilimitado)');
		}
	}


    return '
	<div class="modal fade" id="modalEmails">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">

				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<h4>Contas de E-mail</h4>
				</div>

				<div class="modal-body" id="conteudoEmails">
					Carregando...
				</div>

			</div>
		</div>
	</div>
    <script>
    $(function(){

        $("#modcmdbtns").append(
            \'<button type="button" style="background: #337ab7;color: white;margin: 3px;" class="btn btn-default" id="btnEmails" title="'.$titleBotao.'">'.$textoBotao.'</button>\'
        );

        
        $(document).on("click", "#btnEmails", function(){

            $("#conteudoEmails").html(
                \'<div style="text-align:center;padding:40px;">\' +
                    \'<i class="fa fa-spinner fa-spin fa-2x"></i>\' +
                    \'<div style="margin-top:10px;">Carregando contas de e-mail...</div>\' +
                \'</div>\'
            );

            $("#modalEmails").modal("show");

            $.get(
                "automacao-emails-core.php?id=' . $idServico . '",
                function(html){
                    $("#conteudoEmails").html(html);
                }
            );

        });


    });
    </script>';
});