<?php
//core do hook para adicionar o botão na página de detalhes do serviço
use WHMCS\Database\Capsule;

ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0); // Evita timeout em contas com muitos e-mails

require_once '../init.php';
require_once '../tarifasTHX.php';


$admin = getLoggedAdmin();
$adminNome = $admin->username;

$idServico = 0;

if (isset($_POST['id']))
{
    $idServico = (int)$_POST['id'];
}
elseif (isset($_GET['id']))
{
    $idServico = (int)$_GET['id'];
}



$servico = Capsule::table('tblhosting')
    ->where('id', $idServico)
    ->first();

if (!$servico)
{
    die('Serviço não encontrado.');
}

$produto = Capsule::table('tblproducts')
    ->where('id', $servico->packageid)
    ->first();

if (!$produto)
{
    die('Plano não encontrado.');
}







$espaco = round($produto->configoption3 / 1000);

createDiskHistorySnapshot(
    $idServico,
    $produto,
    $servico,
    $adminNome,
    $espaco
);

$espacoPlano = $espaco;
$valorPlano  = (float)$servico->amount;

$espacoAtual = $espacoPlano;
$valorAtual  = $valorPlano;

$historico = getDiskHistory($idServico);
if (!empty($historico['history']))
{
    $ultimo = end($historico['history']);

    $espacoAtual = (int)$ultimo['gb_after'];
    $valorAtual  = (float)$ultimo['price_after'];
}


$hospedagem = getDadosHospedagemById($idServico);


if (!$hospedagem)
{
    die('Hospedagem não encontrada.');
}

$server = Capsule::table('tblservers')
    ->where('id', $hospedagem['serverid'])
    ->first();

if (!$server)
{
    die('Servidor não encontrado.');
}



if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
    header('Content-Type: application/json');

    $gbNovo    = (int)$_POST['gb'];
    $valorNovo = (float)str_replace(',', '.', $_POST['valor']);
    $motivo    = ($gbNovo > $espacoAtual) ? 'Upgrade' : 'Downgrade';

    // altera a quota no WHM
    $retorno = alterarQuotaWHM(
        $server,
        $hospedagem['username'],
        $gbNovo
    );



    if (empty($retorno['success']))
    {
        echo json_encode($retorno);
        exit;
    }

    try
    {
        Capsule::table('tblhosting')
            ->where('id', $idServico)
            ->update(array(
                'amount' => $valorNovo
            ));
    }
    catch (Exception $e)
    {
        echo json_encode(array(
            'success' => false,
            'message' => $e->getMessage()
        ));
        exit;
    }

    // grava histórico somente se a quota foi alterada
    addDiskHistory($idServico, array(
        'id'             => uniqid(),
        'date'           => date('Y-m-d H:i:s'),
        'admin'          => $adminNome,
        'reason'         => $motivo,
        'plan'           => $produto->name,
        'cpanel_package' => $produto->configoption1,
        'gb_change'      => $gbNovo - $espacoAtual,
        'gb_before'      => $espacoAtual,
        'gb_after'       => $gbNovo,
        'price_change'   => round($valorNovo - $valorAtual, 2),
        'price_before'   => $valorAtual,
        'price_after'    => $valorNovo
    ));

    echo json_encode(array(
        'success' => true,
        'amount'  => $valorNovo
    ));
    exit;
}






function tabelaAndProgressHtml($quota)
{
    $discoUsadoMB  = $quota['diskused_mb'];
    $discoLimiteMB = $quota['disklimit_mb'];

    $discoUsadoGB  = $quota['diskused_gb'];
    $discoLimiteGB = $quota['disklimit_gb'];

    $discoLivreGB  = round(
        ($discoLimiteMB - $discoUsadoMB) / 1000,
        2
    );

$percentualDisco = $quota['percentual'];

    if ($percentualDisco >= 90)
        $cor = 'progress-bar-danger';
    elseif ($percentualDisco >= 75)
        $cor = 'progress-bar-warning';
    else
        $cor = 'progress-bar-success';

    return '
    <div class="panel panel-default">

        <div class="panel-heading">
            <strong>Uso atual do disco <small style="float: right;">Atualizado em '.date('d/m/Y H:i', time()).'</small></strong>
        </div>

        <div class="panel-body">



            <div class="progress" style="height:22px;margin-bottom:10px;">
                <div class="progress-bar '.$cor.'"
                     role="progressbar"
                     style="width:'.$percentualDisco.'%;">
                    '.$percentualDisco.'%
                </div>
            </div>

            <table class="table table-condensed" style="margin-bottom:0;">
                <tr>
                    <th>Utilizado</th>
                    <th>Disponível</th>
                    <th>Limite</th>
                </tr>
                <tr>
                    <td>'.number_format($discoUsadoGB,2,',','.').' GB</td>
                    <td>'.number_format($discoLivreGB,2,',','.').' GB</td>
                     <td>'.number_format($discoLimiteGB,2,',','.').' GB</td>
                </tr>
            </table>

        </div>

    </div>';
}

function calcularValorDisco($gbAdicional)
{
    $valor = 0;

    for ($i = 0; $i < $gbAdicional; $i++)
    {
        $valor += DISK_GB_PRICE * pow(DISK_GB_FACTOR, $i);
    }

    return round($valor, 2);
}

function getDiskHistoryFile($serviceid)
{
    return DISK_DIR_HISTORY . $serviceid . '.json';
}

function getDiskHistory($serviceid)
{
    $arquivo = getDiskHistoryFile($serviceid);

    if (!file_exists($arquivo))
    {
        return array();
    }

    return json_decode(file_get_contents($arquivo), true);
}

function saveDiskHistory($serviceid, $dados)
{
    if (!is_dir(DISK_DIR_HISTORY))
    {
        mkdir(DISK_DIR_HISTORY, 0777, true);
    }

    file_put_contents(
        getDiskHistoryFile($serviceid),
        json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

function addDiskHistory($serviceid, $item)
{
    $arquivo = getDiskHistoryFile($serviceid);

    if (file_exists($arquivo))
    {
        $dados = json_decode(file_get_contents($arquivo), true);
    }
    else
    {
        if (!is_dir(DISK_DIR_HISTORY))
        {
            mkdir(DISK_DIR_HISTORY, 0777, true);
        }

        $dados = array(
            'serviceid'      => $serviceid,
            'created'        => date('Y-m-d H:i:s'),
            'updated'        => date('Y-m-d H:i:s'),
            'history'        => array()
        );
    }

    $dados['updated'] = date('Y-m-d H:i:s');
    $dados['history'][] = $item;

    file_put_contents(
        $arquivo,
        json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}


function createDiskHistorySnapshot($serviceid, $produto, $servico, $adminNome, $espaco)
{
    $arquivo = getDiskHistoryFile($serviceid);

    if (file_exists($arquivo))
    {
        return;
    }

    if (!is_dir(DISK_DIR_HISTORY))
    {
        mkdir(DISK_DIR_HISTORY, 0777, true);
    }

    $dados = array(
        'serviceid'      => $serviceid,
        'plan'           => $produto->name,
        'cpanel_package' => $produto->configoption1,
        'created'        => date('Y-m-d H:i:s'),
        'updated'        => date('Y-m-d H:i:s'),
       'history' => array(
            array(
                'id'             => uniqid(),
                'date'           => date('Y-m-d H:i:s'),
                'admin'          => $adminNome,
                'reason'         => 'Snapshot Inicial',
                'plan'           => $produto->name,
                'cpanel_package' => $produto->configoption1,
                'gb_change'      => 0,
                'gb_before'      => $espaco,
                'gb_after'       => $espaco,
                'price_change'   => 0,
                'price_before'   => (float)$servico->amount,
                'price_after'    => (float)$servico->amount
            )
        )
    );

    file_put_contents(
        $arquivo,
        json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

$quota = consultarQuotaWHM(
    $server,
    $hospedagem['username']
);

?>
<?=tabelaAndProgressHtml($quota) ?>


<?
$ciclos = [
    'Monthly'        => 'Mensal',
    'Quarterly'      => 'Trimestral',
    'Semi-Annually'  => 'Semestral',
    'Annually'       => 'Anual',
    'Biennially'     => 'Bienal',
    'Triennially'    => 'Trienal',
    'One Time'       => 'Único'
];

$ciclo = isset($ciclos[$servico->billingcycle])
    ? $ciclos[$servico->billingcycle]
    : $servico->billingcycle;

$multiplicadorCiclo = [
    'Monthly'       => 1,
    'Quarterly'     => 3,
    'Semi-Annually' => 6,
    'Annually'      => 12,
    'Biennially'    => 24,
    'Triennially'   => 36
];

$mesesCiclo = isset($multiplicadorCiclo[$servico->billingcycle])
    ? $multiplicadorCiclo[$servico->billingcycle]
    : 1;
?>


<table class="table table-bordered table-striped">
<tr>
    <th>Plano</th>
    <th>Plano Base</th>
     <th>Espaço Disponibilizado</th>
     <th>Valor Atual</th>

</tr>

<tr>
    <td><?= $produto->name ?></td>
    <td><?= $espacoPlano ?> GB</td>
    <td><?= $espacoAtual ?> GB</td>
    <td>R$ <?= number_format($valorAtual,2,',','.') ?></td>
</tr>
</table>

<table class="table table-bordered table-striped">
    <tr>
        <th>Periodicidade</th>
        <th>Próximo Vencimento</th>
        <th>Dias Restantes do Ciclo</th>
    </tr>
    <tr>
        <td><?= $ciclos[$servico->billingcycle] ?></td>
        <td id="proximoVencimento"><?= date('d/m/Y', strtotime($servico->nextduedate)) ?></td>
        <td id="diasRestantesCiclo">Calculando...</td>
    </tr>
</table>


<div class="row" style="margin:20px 0;">

    <div class="col-sm-5 text-center">
        <label style="display:block;margin-bottom:8px;">Alterar Quota de Espaço</label>

        <button class="btn btn-default" id="menosQuota">-</button>

        <input
            id="novaQuota"
            type="text"
            class="form-control text-center"
            style="display:inline-block;width:120px;font-size:28px;font-weight:bold;border: none;"
            min="<?=$espacoPlano?>"
            value="<?=$espacoAtual?>">

        <button class="btn btn-default" id="maisQuota">+</button>
    </div>

    <div class="col-sm-4 text-center">
        <label style="display:block;margin-bottom:8px;">Novo Valor <?=$ciclo?></label>

        <div id="novoValor"
             style="font-size:28px;font-weight:bold;color:#3c763d;">
            R$ <?=number_format($valorAtual,2,',','.')?>
        </div>
    </div>

    <div class="col-sm-3 text-center" style="padding-top:28px;">
        <button class="btn btn-success btn-lg" id="salvarQuota">
            Alterar Plano
        </button>
    </div>



    <button class="btn btn-info" id="copiarMensagem">
    Copiar Mensagem
</button>


<button class="btn btn-warning" id="abrirCobranca" style="display: none;">
    Gerar Pix de: R$ 0,00
</button>
</div>

<?php

$historico = getDiskHistory($idServico);

if (!empty($historico['history']))
{
    echo '<details style="margin-top:20px;">
    <summary>
        <h2 style="margin:0; text-align:center; cursor: pointer;">Histórico do Plano ('.count($historico['history']).' alterações)</h2>
    </summary>';
    echo '<table class="table table-bordered table-striped">';
    echo '<tr>';
    echo '<th>Data</th>';
    echo '<th>Plano</th>';
    echo '<th>GB</th>';
    echo '<th>Valor</th>';
    echo '<th>Motivo</th>';
    echo '<th>Admin</th>';
    echo '</tr>';

    foreach (array_reverse($historico['history']) as $item)
    {
        echo '<tr>';
        echo '<td>'.date('d/m/Y H:i', strtotime($item['date'])).'</td>';
        echo '<td>'.$item['plan'].'</td>';
        echo '<td>'.$item['gb_before'].' → '.$item['gb_after'].' GB</td>';
        echo '<td>R$ '.number_format($item['price_before'],2,',','.').' → R$ '.number_format($item['price_after'],2,',','.').'</td>';
        echo '<td>'.$item['reason'].'</td>';
        echo '<td>'.$item['admin'].'</td>';
        echo '</tr>';
    }

    echo '</table>';
    echo '</details>';
}


?>

<script>

var consultarQuotaWHM = <?php echo json_encode(consultarQuotaWHM($server, $hospedagem['username'])); ?>;
console.log('consultarQuotaWHM:', consultarQuotaWHM);

var maxEmails = consultarQuotaWHM.dados.maxpop ?? 0;
console.log('maxEmails:', maxEmails);

var quota = document.getElementById('novaQuota');

var valorBase = <?= (float)$valorAtual ?>;
var espacoBase = <?= (int)$espacoAtual ?>;
var quotaMin = <?= (int)$espacoPlano ?>;
var valorMin = <?= isset($historico['history'][0]['price_after']) ? (float)$historico['history'][0]['price_after'] : (float)$valorPlano ?>;

console.log('valorBase:', valorBase);
console.log('espacoBase:', espacoBase);
console.log('quotaMin:', quotaMin);

function calcularDiasCobranca()
{
    var vencimento = '<?= $servico->nextduedate ?>';
    var partes = vencimento.split('-');

    var dataVencimento = new Date(
        Number(partes[0]),
        Number(partes[1]) - 1,
        Number(partes[2])
    );

    var hoje = new Date();
    hoje.setHours(0, 0, 0, 0);
    dataVencimento.setHours(0, 0, 0, 0);

    var diasRestantes = Math.round(
        (dataVencimento - hoje) / 86400000
    );

    var dataInicioCiclo = new Date(dataVencimento);
    dataInicioCiclo.setMonth(
        dataInicioCiclo.getMonth() - <?= (int)$mesesCiclo ?>
    );

    var diasCiclo = Math.round(
        (dataVencimento - dataInicioCiclo) / 86400000
    );

    return {
        diasRestantes: diasRestantes,
        diasCiclo: diasCiclo,
        dataVencimento: dataVencimento.toLocaleDateString('pt-BR')
    };
}

function atualizarBotoesCobranca()
{
    var gbNovo = parseInt(quota.value) || quotaMin;
    var valorAtual = <?= number_format($valorAtual, 2, '.', '') ?>;

    var valorNovo = parseFloat(
        document.getElementById('novoValor').innerText
            .replace('R$', '')
            .replace(',', '.')
            .trim()
    );

    var diferenca = valorNovo - valorAtual;
    var botao = document.getElementById('abrirCobranca');

    if (gbNovo <= espacoBase || diferenca <= 0)
    {
        botao.style.display = 'none';
        return;
    }

    var dias = calcularDiasCobranca();

    var valorIntegral = diferenca;
    var valorProporcional = valorIntegral;

    if (dias.diasRestantes > 0 && dias.diasCiclo > 0)
    {
        var calculado = diferenca * dias.diasRestantes / dias.diasCiclo;

        if (isFinite(calculado) && calculado > 0)
        {
            valorProporcional = Math.min(calculado, valorIntegral);
        }
    }

    botao.style.display = 'inline-block';
    botao.innerText =
        'Gerar Pix de: R$ ' + valorProporcional.toFixed(2).replace('.', ',');
}


function atualizarQuota()
{
    var gb = Math.max(parseInt(quota.value) || quotaMin, quotaMin);
    var adicional = gb - espacoBase;

    var novoValor = valorBase + (adicional * <?=DISK_GB_PRICE?> * <?= $mesesCiclo ?>);

    novoValor = Math.max(novoValor, valorMin);

    quota.value = gb;

    document.getElementById('novoValor').innerHTML =
        'R$ ' + novoValor.toFixed(2).replace('.', ',');

    atualizarBotoesCobranca();
}



document.getElementById('salvarQuota').onclick = function()
{
    var gb = parseInt(quota.value);

    var valor = document.getElementById('novoValor')
        .innerText
        .replace('R$','')
        .trim();

    //verifica se houve alteração na quota ou no valor
    if (gb === <?= (int)$espacoAtual ?>)
    {
        alert('Não houve alteração na quota de espaço.');
        return;
    }

    if (!confirm(
        'Confirma a alteração do plano?\n\n' +
        'Espaço: ' + gb + ' GB\n' +
        'Valor: R$ ' + valor + '\n\n' +
        'Essa alteração atualizará a quota no servidor e o valor do serviço no WHMCS.'
    )) {
        return;
    }


    $.post(
        'update-disk-quota-core.php',
        {
            id: <?php echo $idServico; ?>,
            gb: gb,
            valor: valor
        }
    )
    .done(function(retorno){

        console.group('Resposta do PHP');
        console.log(retorno);
        console.groupEnd();

        if (typeof retorno === 'object')
            console.table(retorno);

        if (retorno.success)
        {
            document.getElementById('inputAmount').value = retorno.amount;

            alert(
                'Quota alterada com sucesso!\n\n' +
                'O valor foi atualizado no formulário do WHMCS, mas ainda NÃO foi salvo nele.\n\n' +
                'Clique em "Salvar alterações" no formulário do WHMCS para concluir.'
            );
        }

    })
    .fail(function(xhr, status, error){

        console.group('Erro AJAX');
        console.log('Status HTTP:', xhr.status);
        console.log('Status AJAX:', status);
        console.log('Erro:', error);
        console.log('Resposta:', xhr.responseText);
        console.groupEnd();

    });
};


document.getElementById('copiarMensagem').onclick = async function ()
{
    var botao = this;
    var gbNovo = parseInt(quota.value);

    var upOrDown = gbNovo > <?= $espacoAtual ?> ? 'UPGRADE' : 'DOWNGRADE';

    var valorAtual = <?= number_format($valorAtual, 2, '.', '') ?>;
    var valorBasePlano = <?= isset($historico['history'][0]['price_after']) ? (float)$historico['history'][0]['price_after'] : (float)$valorPlano ?>;

    var valorNovo = parseFloat(
        document.getElementById('novoValor').innerText
            .replace('R$', '')
            .replace(',', '.')
            .trim()
    );

    var dias = calcularDiasCobranca();

            var valorProporcional = (
                (valorNovo - valorAtual) *
                dias.diasRestantes /
                dias.diasCiclo
            ).toFixed(2);

    var texto = `Seu plano de hospedagem atual:\n\n`;

    texto += `--------------------\n`;
    texto += `*Plano:* <?= addslashes($produto->name) ?>\n`;
    texto += `*Espaço mínimo do plano:* ${quotaMin} GB\n`;
    texto += maxEmails > 0 ? `*Máximo de contas de email:* ${maxEmails}\n` : '';
    texto += `*Espaço atual:* <?= $espacoAtual ?> GB\n`;
    texto += `*Valor base:* R$ ${valorBasePlano.toFixed(2).replace('.', ',')} / <?=$ciclo?>\n`;
    texto += `*Valor atual:* R$ ${valorAtual.toFixed(2).replace('.', ',')} / <?=$ciclo?>\n`;
    texto += `*Próximo vencimento:* ${dias.dataVencimento}\n`;
    texto += `*Dias restantes:* ${dias.diasRestantes}\n`; //de ${dias.diasCiclo} dias

    if (gbNovo != <?= $espacoAtual ?> || valorNovo != valorAtual)
    {
        texto += `\n--------------------\n\n`;
        texto += `*Orçamento de ${upOrDown}*\n\n`;
        texto += `De <?= $espacoAtual ?> GB para ${gbNovo} GB de espaço em disco.\n\n`;

        texto += `--------------------\n`;
        texto += `*Novo espaço total:* ${gbNovo} GB\n`;
        texto += `*Novo valor:* R$ ${valorNovo.toFixed(2).replace('.', ',')} / <?=$ciclo?>`;
        texto += `\n--------------------\n\n`;
        texto += `*Diferença:* R$ ${(valorNovo - valorAtual).toFixed(2).replace('.', ',')} / <?=$ciclo?>`;


        if (upOrDown === 'UPGRADE') {
            var valorMenor = Math.min(valorProporcional, (valorNovo - valorAtual)).toFixed(2);
            texto += `\n\n*Valor a pagar:* R$ ${valorMenor.replace('.', ',')}`;


            texto += `\n\nPosso faturar o valor informado para o UPGRADE e ajustar o valor do plano? 🤔`;
        } else {
            texto += `\n\nPosso aplicar o DOWNGRADE e ajustar o valor do plano? 🤔`;
        }
    }

    await navigator.clipboard.writeText(texto);

    botao.disabled = true;
    botao.innerHTML = '<i class="fa fa-check"></i> Copiado';

    setTimeout(function ()
    {
        botao.disabled = false;
        botao.innerHTML = '<i class="fa fa-copy"></i> Copiar Mensagem';
    }, 2000);
};

document.getElementById('abrirCobranca').onclick = function ()
{
    var gbNovo = parseInt(quota.value);
    var valorAtual = <?= number_format($valorAtual, 2, '.', '') ?>;

    var valorNovo = parseFloat(
        document.getElementById('novoValor').innerText
            .replace('R$', '')
            .replace(',', '.')
            .trim()
    );

    var planDiference = gbNovo - <?= (int)$espacoAtual ?>;

    if (planDiference === 0)
    {
        alert('Não houve alteração na quota de espaço.\nAltere a quota antes de gerar a cobrança.');
        return;
    }

    if (planDiference < 0)
    {
        alert('Downgrade não gera cobrança.');
        return;
    }

    var diferenca = valorNovo - valorAtual;

    if (diferenca <= 0 || !isFinite(diferenca))
    {
        alert('O valor da diferença entre os planos é inválido.');
        return;
    }

    // Valor integral: diferença de um ciclo completo
    var valorIntegral = diferenca;

    // Tenta calcular o valor proporcional
    var valorProporcional = null;

    try
    {
        var vencimento = '<?= $servico->nextduedate ?>';
        var partes = vencimento.split('-');

        var dataVencimento = new Date(
            Number(partes[0]),
            Number(partes[1]) - 1,
            Number(partes[2])
        );

        var hoje = new Date();
        hoje.setHours(0, 0, 0, 0);
        dataVencimento.setHours(0, 0, 0, 0);

        var diasRestantes = Math.round(
            (dataVencimento - hoje) / 86400000
        );

        var mesesCiclo = <?= (int)$mesesCiclo ?>;

        var dataInicioCiclo = new Date(dataVencimento);
        dataInicioCiclo.setMonth(
            dataInicioCiclo.getMonth() - mesesCiclo
        );

        var diasCiclo = Math.round(
            (dataVencimento - dataInicioCiclo) / 86400000
        );

        if (
            !isNaN(dataVencimento.getTime()) &&
            diasRestantes > 0 &&
            diasCiclo > 0
        )
        {
            var calculado = diferenca * diasRestantes / diasCiclo;

            if (isFinite(calculado) && calculado > 0)
            {
                valorProporcional = calculado;
            }
        }
    }
    catch (e)
    {
        valorProporcional = null;
    }

    // Usa o menor valor quando ambos são válidos.
    // Se o proporcional falhar, usa o integral.
    var valor = (
        valorProporcional !== null
            ? Math.min(valorProporcional, valorIntegral)
            : valorIntegral
    ).toFixed(2);

    var descricao =
        'Upgrade proporcional de plano de Hospedagem ' +
        '<?= addslashes($produto->name) ?> - ' +
        '<?= (int)$espacoAtual ?>GB para ' + gbNovo + 'GB';

    var url =
        'https://pix.grupothx.com/cobranca-layout'
        + '?id=UPGRADE-PLAN-<?= (int)$idServico ?>'
        + '&generate=true'
        + '&conta=nujuridica'
        + '&valor=' + encodeURIComponent(valor)
        + '&descricao=' + encodeURIComponent(descricao);

    window.open(url, '_blank');
};


var timer = null;
var loop = null;

function repetir(btn, delta)
{
    var timer;
    var loop;

    function alterar()
    {
        var v = parseInt(quota.value);

        if (delta < 0 && v <= parseInt(quota.min))
            return;

        quota.value = v + delta;
        atualizarQuota();
    }

    btn.addEventListener('mousedown', function ()
    {
        alterar();

        timer = setTimeout(function ()
        {
            loop = setInterval(alterar, 60);
        }, 500);
    });

    ['mouseup','mouseleave'].forEach(function(e)
    {
        btn.addEventListener(e,function()
        {
            clearTimeout(timer);
            clearInterval(loop);
        });
    });
}

repetir(document.getElementById('maisQuota'),1);
repetir(document.getElementById('menosQuota'),-1);

atualizarQuota();

quota.addEventListener('input', atualizarQuota);

window.addEventListener('mouseup', function () {
    clearTimeout(timer);
    clearInterval(loop);
});

document.getElementById('diasRestantesCiclo').textContent =
    calcularDiasCobranca().diasRestantes + ' dias';
</script>