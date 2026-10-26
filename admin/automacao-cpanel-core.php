<?php
//automacao-cpanel-core.php

use WHMCS\Database\Capsule;

ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);

require_once '../init.php';
require_once '../tarifasTHX.php';

$admin = getLoggedAdmin();

$idServico = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

if(!$idServico)
{
    die('Serviço inválido');
}

$hospedagem = getDadosHospedagemById($idServico);

if(!$hospedagem)
{
    die('Hospedagem não encontrada');
}

$server = Capsule::table('tblservers')
    ->where('id', $hospedagem['serverid'])
    ->first();

if(!$server)
{
    die('Servidor não encontrado');
}

$quota = consultarQuotaWHM($server, $hospedagem['username']);

if(!$quota['success'])
{
    die('Não foi possível consultar a conta.');
}

$dados = $quota['dados'];

#dominios e subdominios

$listaDominios = array();
$listaSubdominios = array();

$retornoDominios = executarRequisicaoCpanel($server, array(
    'cpanel_jsonapi_user' => $hospedagem['username'],
    'cpanel_jsonapi_apiversion' => 3,
    'cpanel_jsonapi_module' => 'DomainInfo',
    'cpanel_jsonapi_func' => 'list_domains'
));

$dominios = $retornoDominios['retorno']['result']['data'];

if(!empty($dominios['main_domain']))
{
    $listaDominios[] = $dominios['main_domain'];
}

if(!empty($dominios['addon_domains']))
{
    $listaDominios = array_merge(
        $listaDominios,
        $dominios['addon_domains']
    );
}

if(!empty($dominios['parked_domains']))
{
    $listaDominios = array_merge(
        $listaDominios,
        $dominios['parked_domains']
    );
}

if(!empty($dominios['sub_domains']))
{
    $listaSubdominios = $dominios['sub_domains'];
}

$listaDominios = array_unique($listaDominios);
$listaSubdominios = array_unique($listaSubdominios);

sort($listaDominios);
sort($listaSubdominios);


// echo '<h1>Dados Atuais</h1><pre>';
// print_r($dados);
// echo '</pre>';
// echo '<h1>Domínios</h1><pre>';
// print_r($listaDominios);
// echo '</pre>';
// echo '<h1>Subdomínios</h1><pre>';
// print_r($listaSubdominios);
// echo '</pre>';


if(isset($_POST['acao']) && $_POST['acao'] == 'obter_php_fpm')
{
    header('Content-Type: application/json; charset=utf-8');

    $dominio = trim($_POST['dominio']);

    if(!in_array($dominio, $listaDominios) && !in_array($dominio, $listaSubdominios))
    {
        echo json_encode(array(
            'erro' => 'Domínio inválido.'
        ));

        exit;
    }

    $statusFPM = executarRequisicaoWHMPHPFPM(
        $server,
        'php_get_vhost_versions',
        array(
            'vhost' => $dominio
        )
    );

    $fpmAtivo = false;

    if(
        !empty($statusFPM['retorno']['data']['versions']) &&
        is_array($statusFPM['retorno']['data']['versions'])
    )
    {
        foreach($statusFPM['retorno']['data']['versions'] as $vhost)
        {
            if(
                isset($vhost['vhost']) &&
                $vhost['vhost'] == $dominio
            )
            {
                $fpmAtivo = !empty($vhost['php_fpm']);
                break;
            }
        }
    }

    if(!$fpmAtivo)
    {
        echo json_encode(array(
            'php_fpm' => false
        ));

        exit;
    }

    $retorno = executarRequisicaoWHMPHPFPM(
        $server,
        'php_fpm_config_get',
        array(
            'domain' => $dominio
        )
    );

    $retorno['php_fpm'] = true;

    echo json_encode($retorno);

    exit;
}


if(isset($_POST['acao']) && $_POST['acao'] == 'salvar_php_fpm')
{
    $dominio = trim($_POST['dominio']);

    if(!in_array($dominio, $listaDominios) && !in_array($dominio, $listaSubdominios))
    {
        die('Domínio inválido.');
    }


    $statusFPM = executarRequisicaoWHMPHPFPM(
        $server,
        'php_get_vhost_versions',
        array(
            'vhost' => $dominio
        )
    );

    $fpmAtivo = false;

    if(
        !empty($statusFPM['retorno']['data']['versions']) &&
        is_array($statusFPM['retorno']['data']['versions'])
    )
    {
        foreach($statusFPM['retorno']['data']['versions'] as $vhost)
        {
            if(
                isset($vhost['vhost']) &&
                $vhost['vhost'] == $dominio
            )
            {
                $fpmAtivo = !empty($vhost['php_fpm']);
                break;
            }
        }
    }

    if(!$fpmAtivo)
    {
        die('PHP-FPM não está habilitado para este domínio.');
    }




    $config = array(
        array(
            'base_flag_name' => 'pm_max_children',
            'trinary_admin_value' => '0',
            'value' => (string)max(1, (int)$_POST['pm_max_children'])
        ),
        array(
            'base_flag_name' => 'pm_max_requests',
            'trinary_admin_value' => '0',
            'value' => (string)max(0, (int)$_POST['pm_max_requests'])
        ),
        array(
            'base_flag_name' => 'pm_process_idle_timeout',
            'trinary_admin_value' => '0',
            'value' => (string)max(1, (int)$_POST['pm_process_idle_timeout'])
        ),
        array(
            'base_flag_name' => 'error_reporting',
            'trinary_admin_value' => '2',
            'value' => $_POST['php_value_error_reporting']
        ),
        array(
            'base_flag_name' => 'disable_functions',
            'trinary_admin_value' => '1',
            'value' => $_POST['php_admin_value_disable_functions']
        ),
        array(
            'base_flag_name' => 'allow_url_fopen',
            'trinary_admin_value' => '1',
            'value' => $_POST['php_admin_flag_allow_url_fopen']
        ),
        array(
            'base_flag_name' => 'log_errors',
            'trinary_admin_value' => '1',
            'value' => $_POST['php_admin_flag_log_errors']
        ),
        array(
            'base_flag_name' => 'short_open_tag',
            'trinary_admin_value' => '1',
            'value' => $_POST['php_admin_value_short_open_tag']
        )
    );

    $retorno = executarRequisicaoWHMPHPFPM(
        $server,
        'php_fpm_config_set',
        array(
            'domain' => $dominio,
            'config' => $config
        )
    );

    $ok = !empty($retorno['retorno']['metadata']['result']);

    if($ok)
    {
        echo '<div class="alert alert-success">';
        echo '<i class="fa fa-check"></i> Configurações PHP-FPM salvas com sucesso.';
        echo '</div>';
    }
    else
    {
        echo '<div class="alert alert-danger">';
        echo '<i class="fa fa-times"></i> Erro ao salvar as configurações PHP-FPM.';

        if(!empty($retorno['retorno']['metadata']['reason']))
        {
            echo '<br>' . htmlspecialchars($retorno['retorno']['metadata']['reason']);
        }

        echo '</div>';
    }

    exit;
}


if(isset($_POST['acao']) && $_POST['acao'] == 'salvar')
{
    $maxftp = isset($_POST['maxftp_unlimited']) ? 'unlimited' : (int)$_POST['maxftp'];
	$maxpop = isset($_POST['maxpop_unlimited']) ? 'unlimited' : (int)$_POST['maxpop'];
	$maxaddons = isset($_POST['maxaddons_unlimited']) ? 'unlimited' : (int)$_POST['maxaddons'];
	$maxsub = isset($_POST['maxsub_unlimited']) ? 'unlimited' : (int)$_POST['maxsub'];
	$maxlst = isset($_POST['maxlst_unlimited']) ? 'unlimited' : (int)$_POST['maxlst'];
	$maxsql = isset($_POST['maxsql_unlimited']) ? 'unlimited' : (int)$_POST['maxsql'];
	$maxpark = isset($_POST['maxpark_unlimited']) ? 'unlimited' : (int)$_POST['maxpark'];
	$maxpassengerapps = isset($_POST['maxpassengerapps_unlimited']) ? 'unlimited' : (int)$_POST['maxpassengerapps'];
	$max_email_per_hour = isset($_POST['max_email_per_hour_unlimited']) ? 'unlimited' : (int)$_POST['max_email_per_hour'];

    $max_defer_fail_percentage = isset($_POST['max_defer_fail_percentage_unlimited']) ? 'unlimited' : (int)$_POST['max_defer_fail_percentage'];

    $max_emailacct_quota = isset($_POST['max_emailacct_quota_unlimited'])
        ? 'unlimited'
        : (int)$_POST['max_emailacct_quota'];

    $shell = $_POST['shell'] == '1'
        ? '/bin/bash'
        : '/usr/local/cpanel/bin/noshell';

    $params = array(
        'api.version' => 1,
        'user' => $hospedagem['username'],

        'MAXFTP' => $maxftp,
        'MAXPOP' => $maxpop,
        'MAXADDON' => $maxaddons,
        'MAXSUB' => $maxsub,
        'MAXLST' => $maxlst,
        'MAXSQL' => $maxsql,
        'MAXPARK' => $maxpark,
        'MAXPASSENGERAPPS' => $maxpassengerapps,

        'MAX_DEFER_FAIL_PERCENTAGE' => $max_defer_fail_percentage,
        'MAX_EMAIL_PER_HOUR' => $max_email_per_hour,
        'MAX_EMAILACCT_QUOTA' => $max_emailacct_quota,

        'shell' => $shell
    );

    $retorno = executarRequisicaoWHM($server, $params);

    $ok = !empty($retorno['retorno']['metadata']['result']);

    if($ok)
    {
        echo '<div class="alert alert-success">';
        echo '<i class="fa fa-check"></i> Configurações salvas com sucesso.';
        echo '</div>';
    }
    else
    {
        echo '<div class="alert alert-danger">';
        echo '<i class="fa fa-times"></i> Erro ao salvar as configurações.';
        echo '</div>';
    }

    // echo '<pre style="max-height:300px;overflow:auto;">';
    // print_r($retorno);
    // echo '</pre>';

    exit;
}



?>

<?php

$shell = ($dados['shell'] == '/usr/local/cpanel/bin/noshell') ? 'Não' : 'Sim';

function cpanelHelp($texto)
{
    return '<span
        class="cpanel-help"
        title="'.htmlspecialchars($texto, ENT_QUOTES, 'UTF-8').'">?</span>';
}

function cpanelValor($valor)
{
    return ($valor === 'unlimited' || $valor === '0')
        ? $valor
        : $valor;
}

$maxEmailQuotaUnlimited = ($dados['max_emailacct_quota'] == 'unlimited');
$maxFtpUnlimited = ($dados['maxftp'] === 'unlimited');
$maxPopUnlimited = ($dados['maxpop'] === 'unlimited');
$maxLstUnlimited = ($dados['maxlst'] === 'unlimited');
$maxSqlUnlimited = ($dados['maxsql'] === 'unlimited');
$maxParkUnlimited = ($dados['maxparked'] === 'unlimited');
$maxAddonUnlimited = ($dados['maxaddons'] === 'unlimited');
$maxSubUnlimited = ($dados['maxsub'] === 'unlimited');
$maxPassengerUnlimited = isset($dados['maxpassengerapps'])
    && $dados['maxpassengerapps'] === 'unlimited';
$maxEmailHourUnlimited = ($dados['max_email_per_hour'] === 'unlimited');
$maxDeferFailUnlimited = ($dados['max_defer_fail_percentage'] === 'unlimited');
?>
<style>

.cpanel-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    margin-bottom: 15px;
    padding: 15px;
}

.cpanel-section label {
	font-size: 10px;
}

.cpanel-section h4 {
    margin-top: 0;
    font-weight: 600;
}

.cpanel-help {
    display: inline-block;
    width: 16px;
    height: 16px;
    line-height: 16px;
    text-align: center;
    border-radius: 50%;
    background: #777;
    color: #fff;
    font-size: 11px;
    font-weight: bold;
    cursor: help;
    margin-left: 4px;
}

.cpanel-dominios {
    background: #f7f7f7;
    border-radius: 5px;
    padding: 10px 12px;
    max-height: 180px;
    overflow-y: auto;
}

.cpanel-dominios span {
    display: inline-block;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 3px;
    padding: 4px 8px;
    margin: 2px;
    font-size: 12px;
}

.cpanel-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.cpanel-whatsapp {
    background: #25D366;
    border-color: #25D366;
    color: #fff;
}

.cpanel-whatsapp:hover,
.cpanel-whatsapp:focus {
    background: #1ebe5d;
    border-color: #1ebe5d;
    color: #fff;
}

@media(max-width:767px) {
    .cpanel-actions .btn {
        width: 100%;
    }
}

</style>

<form id="formCpanel">

<div class="cpanel-section">

    <h4>
        <i class="fa fa-cogs"></i>
        Configurações da Conta:
        <?php echo htmlspecialchars($dados['domain']); ?>
        (<?php echo htmlspecialchars($dados['user']); ?>)
    </h4>

</div>


<div class="cpanel-section">

    <h4>Dados da Conta</h4>

    <div class="row">

        <div class="col-md-3">
            <div class="form-group">
                <label>
                    Acesso ao terminal
                    <?php echo cpanelHelp('Permite ou bloqueia o acesso SSH à conta.'); ?>
                </label>

                <select class="form-control" name="shell">
                    <option value="0" <?php echo ($shell == 'Não' ? 'selected' : ''); ?>>
                        Não
                    </option>
                    <option value="1" <?php echo ($shell == 'Sim' ? 'selected' : ''); ?>>
                        Sim
                    </option>
                </select>
            </div>
        </div>


        <div class="col-md-3">
			<div class="form-group">
				<label>Contas de FTP</label>

				<input
					type="number"
					class="form-control"
					name="maxftp"
					value="<?php echo $maxFtpUnlimited ? '' : $dados['maxftp']; ?>"
					<?php echo $maxFtpUnlimited ? 'disabled' : ''; ?>
				>

				<label style="font-weight:normal;margin-top:5px;">
					<input
						type="checkbox"
						name="maxftp_unlimited"
						class="cpanel-unlimited"
						data-target="maxftp"
						<?php echo $maxFtpUnlimited ? 'checked' : ''; ?>
					>
					Ilimitado
				</label>
			</div>
		</div>
        <div class="col-md-3">
			<div class="form-group">
				<label>
					Contas de e-mail
					<?php echo cpanelHelp('Quantidade máxima de contas de e-mail que podem ser criadas.'); ?>
				</label>

				<input
					type="number"
					class="form-control"
					name="maxpop"
					value="<?php echo $maxPopUnlimited ? '' : $dados['maxpop']; ?>"
					<?php echo $maxPopUnlimited ? 'disabled' : ''; ?>
				>

				<label style="font-weight:normal;margin-top:5px;">
					<input
						type="checkbox"
						name="maxpop_unlimited"
						class="cpanel-unlimited"
						data-target="maxpop"
						<?php echo $maxPopUnlimited ? 'checked' : ''; ?>
					>
					Ilimitado
				</label>
			</div>
		</div>


        <div class="col-md-3">
            <div class="form-group">
                <label>
                    Listas de endereçamento
                    <?php echo cpanelHelp('Quantidade máxima de listas de discussão (Mailman) que a conta pode criar.'); ?>
                </label>

                <input
					type="number"
					class="form-control"
					name="maxlst"
					value="<?php echo $maxLstUnlimited ? '' : $dados['maxlst']; ?>"
					<?php echo $maxLstUnlimited ? 'disabled' : ''; ?>
				>

				<label style="font-weight:normal;margin-top:5px;">
					<input
						type="checkbox"
						name="maxlst_unlimited"
						class="cpanel-unlimited"
						data-target="maxlst"
						<?php echo $maxLstUnlimited ? 'checked' : ''; ?>
					>
					Ilimitado
				</label>
            </div>
        </div>


        <div class="col-md-3">
            <div class="form-group">
                <label>
                    Bancos de dados SQL
                    <?php echo cpanelHelp('Quantidade máxima de bancos SQL permitidos para a conta.'); ?>
                </label>

                <input
					type="number"
					class="form-control"
					name="maxsql"
					value="<?php echo $maxSqlUnlimited ? '' : $dados['maxsql']; ?>"
					<?php echo $maxSqlUnlimited ? 'disabled' : ''; ?>
				>

				<label style="font-weight:normal;margin-top:5px;">
					<input
						type="checkbox"
						name="maxsql_unlimited"
						class="cpanel-unlimited"
						data-target="maxsql"
						<?php echo $maxSqlUnlimited ? 'checked' : ''; ?>
					>
					Ilimitado
				</label>
            </div>
        </div>


        <div class="col-md-3">
            <div class="form-group">
                <label>
                    Domínios estacionados / aliases
                    <?php echo cpanelHelp('Quantidade máxima de domínios adicionais que funcionam como aliases da conta.'); ?>
                </label>

                <input
					type="number"
					class="form-control"
					name="maxpark"
					value="<?php echo $maxParkUnlimited ? '' : $dados['maxparked']; ?>"
					<?php echo $maxParkUnlimited ? 'disabled' : ''; ?>
				>

				<label style="font-weight:normal;margin-top:5px;">
					<input
						type="checkbox"
						name="maxpark_unlimited"
						class="cpanel-unlimited"
						data-target="maxpark"
						<?php echo $maxParkUnlimited ? 'checked' : ''; ?>
					>
					Ilimitado
				</label>
            </div>
        </div>


        <div class="col-md-3">
            <div class="form-group">
                <label>
                    Domínios adicionais
                    <?php echo cpanelHelp('Quantidade máxima de domínios adicionais independentes dentro da conta.'); ?>
                </label>

                <input
					type="number"
					class="form-control"
					name="maxaddons"
					value="<?php echo $maxAddonUnlimited ? '' : $dados['maxaddons']; ?>"
					<?php echo $maxAddonUnlimited ? 'disabled' : ''; ?>
				>

				<label style="font-weight:normal;margin-top:5px;">
					<input
						type="checkbox"
						name="maxaddons_unlimited"
						class="cpanel-unlimited"
						data-target="maxaddons"
						<?php echo $maxAddonUnlimited ? 'checked' : ''; ?>
					>
					Ilimitado
				</label>
            </div>
        </div>


        <div class="col-md-3">
            <div class="form-group">
                <label>
                    Subdomínios
                    <?php echo cpanelHelp('Quantidade máxima de subdomínios que a conta pode criar.'); ?>
                </label>

                <input
					type="number"
					class="form-control"
					name="maxsub"
					value="<?php echo $maxSubUnlimited ? '' : $dados['maxsub']; ?>"
					<?php echo $maxSubUnlimited ? 'disabled' : ''; ?>
				>

				<label style="font-weight:normal;margin-top:5px;">
					<input
						type="checkbox"
						name="maxsub_unlimited"
						class="cpanel-unlimited"
						data-target="maxsub"
						<?php echo $maxSubUnlimited ? 'checked' : ''; ?>
					>
					Ilimitado
				</label>
            </div>
        </div>


        <div class="col-md-3">
			<div class="form-group">
				<label>
					Aplicações Passenger
					<?php echo cpanelHelp('Quantidade máxima de aplicações que podem utilizar o Phusion Passenger.'); ?>
				</label>

				<input
					type="number"
					class="form-control"
					name="maxpassengerapps"
					value="<?php echo $maxPassengerUnlimited ? '' : (isset($dados['maxpassengerapps']) ? $dados['maxpassengerapps'] : ''); ?>"
					<?php echo $maxPassengerUnlimited ? 'disabled' : ''; ?>
				>

				<label style="font-weight:normal;margin-top:5px;">
					<input
						type="checkbox"
						name="maxpassengerapps_unlimited"
						class="cpanel-unlimited"
						data-target="maxpassengerapps"
						<?php echo $maxPassengerUnlimited ? 'checked' : ''; ?>
					>
					Ilimitado
				</label>
			</div>
		</div>


        <div class="col-md-3">
            <div class="form-group">
                <label>
                    E-mails por hora / domínio
                    <?php echo cpanelHelp('Quantidade máxima de mensagens que a conta pode enviar por hora.'); ?>
                </label>

                <input
					type="number"
					class="form-control"
					name="max_email_per_hour"
					value="<?php echo $maxEmailHourUnlimited ? '' : $dados['max_email_per_hour']; ?>"
					<?php echo $maxEmailHourUnlimited ? 'disabled' : ''; ?>
				>

				<label style="font-weight:normal;margin-top:5px;">
					<input
						type="checkbox"
						name="max_email_per_hour_unlimited"
						class="cpanel-unlimited"
						data-target="max_email_per_hour"
						<?php echo $maxEmailHourUnlimited ? 'checked' : ''; ?>
					>
					Ilimitado
				</label>
            </div>
        </div>


        <div class="col-md-3">
            <div class="form-group">
                <label>
                    Limite de erros de envio
                    <?php echo cpanelHelp('Percentual de mensagens com falha ou adiadas permitido antes do controle de envio ser aplicado.'); ?>
                </label>

                <input
					type="number"
					class="form-control"
					name="max_defer_fail_percentage"
					value="<?php echo $maxDeferFailUnlimited ? '' : $dados['max_defer_fail_percentage']; ?>"
					<?php echo $maxDeferFailUnlimited ? 'disabled' : ''; ?>
				>

				<label style="font-weight:normal;margin-top:5px;">
					<input
						type="checkbox"
						name="max_defer_fail_percentage_unlimited"
						class="cpanel-unlimited"
						data-target="max_defer_fail_percentage"
						<?php echo $maxDeferFailUnlimited ? 'checked' : ''; ?>
					>
					Ilimitado
				</label>
            </div>
        </div>


        <div class="col-md-3">
            <div class="form-group">

                <label>
                    Quota máxima por e-mail
                    <?php echo cpanelHelp('Tamanho máximo, em MB, que uma nova conta de e-mail pode definir para sua caixa postal. Não altera contas já existentes.'); ?>
                </label>

                <input
                    type="number"
                    class="form-control"
                    name="max_emailacct_quota"
                    value="<?php echo $maxEmailQuotaUnlimited ? '' : $dados['max_emailacct_quota']; ?>"
                    <?php echo ($maxEmailQuotaUnlimited ? 'disabled' : ''); ?>
                >

                <label style="font-weight:normal;margin-top:5px;">
                    <input
                        type="checkbox"
						class="cpanel-unlimited"
						data-target="max_emailacct_quota"
                        name="max_emailacct_quota_unlimited"
                        id="max_emailacct_quota_unlimited"
                        <?php echo ($maxEmailQuotaUnlimited ? 'checked' : ''); ?>
                    >
                    Ilimitado
                </label>

            </div>
        </div>


    </div>

</div>

<div class="cpanel-section">
    <h3><i class="fas fa-globe"></i> Domínios da Conta</h3>

    <div class="cpanel-list">
        <?php if(!empty($listaDominios)): ?>

            <?php foreach($listaDominios as $dominio): ?>
                <span class="badge badge-light">
                    <?php echo htmlspecialchars($dominio); ?>
                </span>
            <?php endforeach; ?>

        <?php else: ?>

            <span class="badge badge-light">
                Nenhum domínio encontrado.
            </span>

        <?php endif; ?>
    </div>
</div>

<div class="cpanel-section">
    <h3><i class="fas fa-sitemap"></i> Subdomínios da Conta</h3>

    <div class="cpanel-list">
        <?php if(!empty($listaSubdominios)): ?>

            <?php foreach($listaSubdominios as $subdominio): ?>
                <span class="badge badge-light">
                    <?php echo htmlspecialchars($subdominio); ?>
                </span>
            <?php endforeach; ?>

        <?php else: ?>

            <span class="badge badge-light">
                Nenhum subdomínio encontrado.
            </span>

        <?php endif; ?>
    </div>
</div>


<div class="cpanel-section">

    <div class="cpanel-actions">

        <button
            type="button"
            class="btn btn-primary"
            id="btnSalvarCpanel"
        >
            <i class="fa fa-save"></i>
            Salvar configurações
        </button>


        <button
            type="button"
            class="btn cpanel-whatsapp"
            id="btnCopiarLimitesWhatsApp"
        >
            <i class="fa fa-whatsapp"></i>
            Copiar dados para WhatsApp
        </button>

    </div>

    <hr>

    <div
        id="retornoCpanel"
        style="margin-top:15px;"
    ></div>

</div>

</form>


<form id="formPHPFPM">

    <div class="cpanel-section">

        <h3>
            <i class="fa fa-tachometer"></i>
            Configurações PHP-FPM
        </h3>

        <div class="row">

            <div class="col-md-4">
                <div class="form-group">
                    <label>Domínio</label>

                    <select name="dominio" id="phpFpmDominio" class="form-control" onchange="carregarPHPFPM()" required>
                        <option value="">Escolha o domínio</option>

                        <?php foreach($listaDominios as $dominio): ?>
                            <option value="<?php echo htmlspecialchars($dominio); ?>">
                                <?php echo htmlspecialchars($dominio); ?>
                            </option>
                        <?php endforeach; ?>

                        <?php foreach($listaSubdominios as $dominio): ?>
                            <option value="<?php echo htmlspecialchars($dominio); ?>">
                                <?php echo htmlspecialchars($dominio); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                </div>
            </div>

        </div>

        <hr>
            
        <h4>PHP-FPM</h4>
        <div id="phpFpmConfig" style="display: none;">
            <div class="row">

                <div class="col-md-4">
                    <div class="form-group">
                        <label>
                            Max Children
                            <?php echo cpanelHelp('Número máximo de processos PHP-FPM simultâneos para este domínio.'); ?>
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            name="pm_max_children"
                            id="pm_max_children"
                            min="1"
                        >
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label>
                            Max Requests
                            <?php echo cpanelHelp('Número máximo de requisições que cada processo PHP-FPM atende antes de ser reiniciado.'); ?>
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            name="pm_max_requests"
                            id="pm_max_requests"
                            min="0"
                        >
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label>
                            Process Idle Timeout
                            <?php echo cpanelHelp('Tempo, em segundos, que um processo PHP-FPM ocioso permanece ativo.'); ?>
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            name="pm_process_idle_timeout"
                            id="pm_process_idle_timeout"
                            min="1"
                        >
                    </div>
                </div>

            </div>

            <hr>

            <h4>PHP</h4>

            <div class="row">

                <div class="col-md-6">
                    <div class="form-group">
                        <label>
                            Error Reporting
                            <?php echo cpanelHelp('Define quais tipos de erros, avisos e notificações do PHP serão registrados.'); ?>
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="php_value_error_reporting"
                            id="php_value_error_reporting"
                        >
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>
                            Error Log
                            <?php echo cpanelHelp('Arquivo utilizado pelo PHP-FPM para registrar erros do PHP.'); ?>
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="php_value_error_log"
                            id="php_value_error_log" disabled
                        >
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <label>
                            Disable Functions
                            <?php echo cpanelHelp('Funções PHP desabilitadas para este domínio.'); ?>
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="php_admin_value_disable_functions"
                            id="php_admin_value_disable_functions"
                        >
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>
                            Allow URL Fopen
                            <?php echo cpanelHelp('Permite que funções como fopen e file_get_contents acessem URLs remotas.'); ?>
                        </label>

                        <select
                            class="form-control"
                            name="php_admin_flag_allow_url_fopen"
                            id="php_admin_flag_allow_url_fopen"
                        >
                            <option value="on">on</option>
                            <option value="off">off</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>
                            Log Errors
                            <?php echo cpanelHelp('Ativa ou desativa o registro de erros do PHP.'); ?>
                        </label>

                        <select
                            class="form-control"
                            name="php_admin_flag_log_errors"
                            id="php_admin_flag_log_errors"
                        >
                            <option value="on">on</option>
                            <option value="off">off</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>
                            Document Root
                            <?php echo cpanelHelp('Diretório raiz utilizado pelo PHP-FPM para este domínio.'); ?>
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="php_admin_value_doc_root"
                            id="php_admin_value_doc_root" disabled
                        >
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>
                            Short Open Tag
                            <?php echo cpanelHelp('Permite a utilização da sintaxe curta <? ?> do PHP.'); ?>
                        </label>

                        <select
                            class="form-control"
                            name="php_admin_value_short_open_tag"
                            id="php_admin_value_short_open_tag"
                        >
                            <option value="on">on</option>
                            <option value="off">off</option>
                        </select>
                    </div>
                </div>

            </div>

            <button
                type="button"
                class="btn btn-primary"
                id="btnSalvarPHPFPM"
            >
                <i class="fa fa-save"></i>
                Salvar PHP-FPM
            </button>
        </div>
        <div
            id="retornoPHPFPM"
            style="margin-top:15px;"
        ></div>

    </div>

</form>

<script>

function carregarPHPFPM()
{
    var dominio = $('#phpFpmDominio').val();

    $('#phpFpmConfig').hide();
    $('#phpFpmMensagem').hide();
    $('#retornoPHPFPM').html('');
    if(!dominio || dominio === '')
    {
        $('#retornoPHPFPM').html('<div class="alert alert-warning">Por favor, selecione um domínio.</div>');
        return;
    }

    $('#retornoPHPFPM').html('<i class="fa fa-spinner fa-spin fa-2x"></i><div style="margin-top:10px;">Carregando configurações PHP-FPM...</div>');

    $.post(
        'automacao-cpanel-core.php?id=<?php echo $idServico; ?>',
        {
            acao: 'obter_php_fpm',
            dominio: dominio
        },
        function(retorno){
            if(
                retorno &&
                retorno.php_fpm === false
            )
            {
                $('#phpFpmConfig').hide();
                $('#retornoPHPFPM').html(
                    '<div class="alert alert-warning">' +
                    'PHP-FPM não está habilitado para este domínio.' +
                    '</div>'
                );

                return;
            }

            $('#phpFpmConfig').show();

            if(
                !retorno ||
                !retorno.retorno ||
                !retorno.retorno.data ||
                !retorno.retorno.data.config
            )
            {
                $('#retornoPHPFPM').html(
                    '<div class="alert alert-danger">' +
                    'Não foi possível carregar as configurações PHP-FPM.' +
                    '</div>' +
                    '<pre style="margin-top:10px;max-height:400px;overflow:auto;">' +
                    $('<div>').text(
                        JSON.stringify(retorno, null, 2)
                    ).html() +
                    '</pre>'
                );

                return;
            }

            var config = {};

            $.each(retorno.retorno.data.config, function(i, item){

                config[item.base_flag_name] = item.value;

            });

            $('#pm_max_children').val(
                config.pm_max_children || ''
            );

            $('#pm_max_requests').val(
                config.pm_max_requests || ''
            );

            $('#pm_process_idle_timeout').val(
                config.pm_process_idle_timeout || ''
            );

            $('#php_value_error_reporting').val(
                config.error_reporting || ''
            );

            $('#php_value_error_log').val(
                config.error_log || ''
            );

            $('#php_admin_value_disable_functions').val(
                config.disable_functions || ''
            );

            $('#php_admin_flag_allow_url_fopen').val(
                config.allow_url_fopen || 'on'
            );

            $('#php_admin_flag_log_errors').val(
                config.log_errors || 'on'
            );

            $('#php_admin_value_doc_root').val(
                config.doc_root || ''
            );

            $('#php_admin_value_short_open_tag').val(
                config.short_open_tag || 'on'
            );

            $('#retornoPHPFPM').html('');

        },
        'json'
    );
}



$(document).on('click', '#btnSalvarPHPFPM', function(){

    var btn = $(this);

    btn.prop('disabled', true);

    $('#retornoPHPFPM').html('Salvando...');

    var dados = $('#formPHPFPM')
        .serialize();

    $.post(
        'automacao-cpanel-core.php?id=<?php echo $idServico; ?>',
        dados + '&acao=salvar_php_fpm',
        function(retorno){

            $('#retornoPHPFPM').html(retorno);

        }
    ).always(function(){

        btn.prop('disabled', false);

    });

});



$(document).on('change', '.cpanel-unlimited', function(){

    var campo = $(this).data('target');

    $('input[name="' + campo + '"]')
        .prop('disabled', $(this).prop('checked'));

});


$(document).on('click', '#btnCopiarLimitesWhatsApp', function(){

    var btn = $(this);
    var originalHtml = btn.html();

    var texto =
        "📋 *Dados da hospedagem @<?php echo $dados['domain']; ?>*\n\n" +

        "*Acesso ao terminal:* <?php echo $shell; ?>\n" +
        "*Contas de FTP:* <?php echo $dados['maxftp']; ?>\n" +
        "*Contas de e-mail:* <?php echo $dados['maxpop']; ?>\n" +
        "*Listas de endereçamento:* <?php echo $dados['maxlst']; ?>\n" +
        "*Bancos de dados SQL:* <?php echo $dados['maxsql']; ?>\n" +
        "*Domínios estacionados / aliases:* <?php echo $dados['maxparked']; ?>\n" +
        "*Domínios adicionais:* <?php echo $dados['maxaddons']; ?>\n" +
        "*Subdomínios:* <?php echo $dados['maxsub']; ?>\n" +
        "*Aplicações Passenger:* <?php echo isset($dados['maxpassengerapps']) ? $dados['maxpassengerapps'] : 'N/D'; ?>\n" +
        "*E-mails por hora:* <?php echo $dados['max_email_per_hour']; ?>\n" +
        "*Erros de envio:* <?php echo $dados['max_defer_fail_percentage']; ?>\n" +
        "*Quota por e-mail:* <?php echo $dados['max_emailacct_quota']; ?>\n\n" +

        "🌐 *Domínios:*\n<?php
        foreach($listaDominios as $dominio)
        {
            echo '- '.addslashes($dominio)."\\n";
        }
        ?>\n" +

        "🔹 *Subdomínios:*\n<?php
        foreach($listaSubdominios as $subdominio)
        {
            echo '- '.addslashes($subdominio)."\\n";
        }
        ?>";


    navigator.clipboard.writeText(texto).then(function(){

        btn
            .html('<i class="fa fa-check"></i> Copiado!')
            .removeClass('cpanel-whatsapp')
            .addClass('btn-info');

        setTimeout(function(){

            btn
                .html(originalHtml)
                .removeClass('btn-info')
                .addClass('cpanel-whatsapp');

        }, 2000);

    });

});


$(document).on('click', '#btnSalvarCpanel', function(){

    var btn = $(this);

    btn.prop('disabled', true);

    $('#retornoCpanel').html('Salvando...');

    var dados = $('#formCpanel')
        .find('input, select, textarea')
        .serialize();

    $.post(
        'automacao-cpanel-core.php?id=<?php echo $idServico; ?>',
        dados + '&acao=salvar',
        function(retorno){

            $('#retornoCpanel').html(retorno);

        }
    ).always(function(){

        btn.prop('disabled', false);

    });

});
$(document).on('click', '#retornoCpanel', function(){
	var elemento = document.getElementById("retornoCpanel");
	var range = document.createRange();
	range.selectNode(elemento);
	window.getSelection().removeAllRanges();
	window.getSelection().addRange(range);
	document.execCommand("copy");
	window.getSelection().removeAllRanges();
});
</script>