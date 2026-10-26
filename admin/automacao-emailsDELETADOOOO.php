<?php
//core do hook para adicionar o botão na página de detalhes do serviço
use WHMCS\Database\Capsule;

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../init.php';
require_once '../tarifasTHX.php';


if(!function_exists('checkLoginWHMCS')) die('Acesso negado'); checkLoginWHMCS();




define('DEBUG_EMAILS', false);

$idServico = (int)$_GET['id'];


$acao = isset($_POST['acao']) ? $_POST['acao'] : '';

if(!$idServico)
{
    die('Serviço inválido');
}

$hospedagem = getDadosHospedagemById($idServico);

$server = Capsule::table('tblservers')
    ->where('id', $hospedagem['serverid'])
    ->first();


if($acao == 'criarEmails')
{
    criarEmails($hospedagem, $server);
    exit;
}




if($acao == 'excluirEmail')
{
    excluirEmail(
        $hospedagem,
        $server
    );

    exit;
}


$emails = getEmails($hospedagem, $server);


echo '
<style>
#resultadoEmails
{
	margin: 15px 0;
	max-height: 50px;
	display: block;
	overflow: auto;
	border: solid red 1px;
    display: none;
    transition: max-height 0.5s ease;
}

#resultadoEmails pre{
    font-size: 1opx;
}

#resultadoEmails h4 {
	margin: 5px 0;
	font-size: 10px;
	font-weight: bold;
}


#resultadoEmails .resultadoEmailsAberto
{
    max-height: none;
} 

</style>    



<div id="resultadoEmails" onclick="this.classList.toggle(\'resultadoEmailsAberto\')"></div>';

debugEmails('SERVER', $server);
debugEmails('EMAILS', $emails);
debugEmails('HOSPEDAGEM', $hospedagem);




$emailsValidos = filtrarEmails($emails, $hospedagem);

$countEmails = count($emailsValidos);



$percentual = getPercentualDisco($hospedagem);




echo renderListaEmails(
    $emailsValidos,
    $countEmails,
    $percentual,
    $hospedagem
);

echo renderFormularioNovoEmail();


function alterarSenha($hospedagem, $server)
{ 
    $emailCompleto = trim($_POST['email']);

    if(!$emailCompleto)
    {
        die('E-mail inválido');
    }

    // Divide o e-mail para pegar apenas o usuário (antes do @)
    $parts = explode('@', $emailCompleto);
    $user = $parts[0];

    $novaSenha = gerarSenha();

    $retorno = alterarSenhaAPI(
        $hospedagem,
        $server,
        $user,
        $novaSenha
    );

    $ok = !empty(
        $retorno['retorno']['cpanelresult']['data'][0]['result']
    );

    if($ok)
    {
        $resultadoWhatsApp = array(
            array(
                'email' => $emailCompleto,
                'senha' => $novaSenha,
                'ok'    => true,
                'erro'  => ''
            )
        );

        echo gerarMensagemWhatsApp($resultadoWhatsApp, $hospedagem['domain']);
        exit;
    }

    echo '<div class="alert alert-danger">Erro ao alterar a senha.</div>';
    echo '<pre>';
    print_r($retorno);
    echo '</pre>';
}


function alterarSenhaAPI($hospedagem, $server, $email, $senha)
{
    $url = 'https://' . $server->hostname . ':2087/json-api/cpanel';

    $params = array(
        'cpanel_jsonapi_user'       => $hospedagem['username'],
        'cpanel_jsonapi_apiversion' => 2,
        'cpanel_jsonapi_module'     => 'Email',
        'cpanel_jsonapi_func'       => 'passwd_pop',
        'email'                     => $email,
        'domain'                    => $hospedagem['domain'],
        'password'                  => $senha
    );

    $ch = curl_init();

    curl_setopt_array($ch, array(
        CURLOPT_URL => $url . '?' . http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => getAuthHeader($server)
    ));

    $retorno = curl_exec($ch);

    if(curl_errno($ch))
    {
        return array(
            'curl_error' => curl_error($ch)
        );
    }

    curl_close($ch);

    return array(
        'params' => $params,
        'retorno' => json_decode($retorno, true),
        'raw' => $retorno
    );
}


function excluirEmail($hospedagem, $server)
{ 
    $email = trim($_POST['email']);

    if(!$email)
    {
        die('E-mail inválido');
    }

    $emails = getEmails($hospedagem, $server);
    $encontrado = false;

    foreach($emails as $item)
    {
        if($item['email'] == $email)
        {
            $encontrado = true;
            break;
        }
    }

    if(!$encontrado)
    {
        die('E-mail não encontrado');
    }

    $retorno = excluirEmailAPI(
        $hospedagem,
        $server,
        $email
    );

    $ok = !empty(
        $retorno['retorno']['cpanelresult']['data'][0]['result']
    );

    if($ok)
    {
        die('OK');
    }

    echo '<pre>';
    print_r($retorno);
    echo '</pre>';
}


function excluirEmailAPI($hospedagem, $server, $email)
{
    $url = 'https://' . $server->hostname . ':2087/json-api/cpanel';

    $params = array(
        'cpanel_jsonapi_user'       => $hospedagem['username'],
        'cpanel_jsonapi_apiversion' => 2,
        'cpanel_jsonapi_module'     => 'Email',
        'cpanel_jsonapi_func'       => 'delpop',
        'email'                     => $email
    );

    $ch = curl_init();

    curl_setopt_array($ch, array(
        CURLOPT_URL => $url . '?' . http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => getAuthHeader($server)
    ));

    $retorno = curl_exec($ch);

    curl_close($ch);

    return array(
        'params' => $params,
        'retorno' => json_decode($retorno, true),
        'raw' => $retorno
    );
}

function normalizarEmail($texto)
{
    $texto = trim($texto);

    if(strpos($texto, '@') !== false)
    {
        return strtolower(
            substr($texto, 0, strpos($texto, '@'))
        );
    }

    $texto = strtolower($texto);

    $trocas = array(
        'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a',
        'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
        'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
        'ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o',
        'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u',
        'ç'=>'c'
    );

    $texto = strtr($texto, $trocas);

    $texto = str_replace(array('-', '_'), ' ', $texto);

    $texto = preg_replace('/[^a-z0-9 ]/', '', $texto);

    $texto = preg_replace('/\s+/', '.', $texto);

    return trim($texto, '.');
}


function gerarMensagemWhatsApp($emails, $dominio)
{
    $ok = array();

    $erros = array();

    foreach($emails as $email)
    {
        if($email['ok'])
        {
            $ok[] = $email;
        }
        else
        {
            $erros[] = $email;
        }
    }

    $htmlErros = '';

    if($erros)
    {
        $htmlErros .= '
        <div class="alert alert-warning">
            <strong>Falha na criação (Motivo retornado pelo cPanel):</strong><br><br>';

        foreach($erros as $erro)
        {
            $htmlErros .=
                '<b>' . $erro['email'] . '</b>'
                .' => '
                .'<span class="text-danger">' . $erro['erro'] . '</span>'
                .'<br>';
        }

        $htmlErros .= '</div>';
    }

    if(!$ok)
    {
        return $htmlErros . '<div class="alert alert-danger">
            Nenhuma conta foi criada com sucesso. Verifique os motivos acima.
        </div>';
    }

    $texto = '';

    if(count($ok) == 1)
    {
        $texto .= "_Conta de e-mail criada/alterada_\n\n";

        $texto .= "*E-mail:* ".$ok[0]['email']."\n";
        $texto .= "*Senha:* ".$ok[0]['senha']."\n";
        $texto .= "*WebMail:* https://webmail.".$dominio;
    }
    else
    {
        $texto .= "_Seu e-mail corporativo @".$dominio." já foi criado:_\n\n";

        foreach($ok as $email)
        {
            $texto .= "*Email:* ".$email['email']."\n";
            $texto .= "*Senha:* ".$email['senha']."\n\n";
        }

        $texto .=
"\nPara configurar seus e-mails em gerenciadores de e-mail (*outlook*, *livemail*, *gmail*, *hotmail*, etc) ou em outros dispositivos (*celular*, *ipad*, *iphone*, etc)

_Com SSL/TLS (Recomendado)_
*Servidor de Entrada:* mail.".$dominio."
*Porta do servidor de Entrada:* 993 para IMAP e 995 para POP3
*Servidor de Saída:* mail.".$dominio."
*Porta do servidor de Saída:* 465

_Sem SSL/TLS_
*Servidor de Entrada:* mail.".$dominio."
*Porta do servidor de Entrada:* 143 para IMAP e 110 para POP3
*Servidor de Saída:* mail.".$dominio."
*Porta do servidor de Saída:* 587

Para acessar seu e-mail pelo navegador:
https://webmail.".$dominio;
    }


    return $htmlErros . '
<hr>

<button
    class="btn btn-success"
    onclick="copiarWhatsApp()"
>
    Copiar WhatsApp
</button>

<textarea
    id="textoWhatsApp"
    style="
        width:100%;
        height:350px;
        margin-top:10px;
    "
>'.htmlspecialchars($texto).'</textarea>';
}

function renderListaEmails(
    $emailsValidos,
    $countEmails,
    $percentual,
    $hospedagem
)
{
    ob_start();
    ?>

    <div id="areaListaEmails">

        <div class="row">

            <div class="col-md-8">
                <h4 style="margin-top:0">
                    <?=$countEmails?> contas de e-mail
                    <small><?=$hospedagem['domain']?></small>
                </h4>
            </div>

            <div class="col-md-4 text-right">
                <button class="btn btn-success" id="btnNovoEmail">
                    Nova Conta
                </button>
            </div>

        </div>

        <div class="row" style="margin-top:15px">

            <div class="col-md-8">
                <input
                    type="text"
                    id="filtroEmail"
                    class="form-control"
                    placeholder="Pesquisar e-mail..."
                >
            </div>

            <div class="col-md-4 text-right">
                <strong><?=$hospedagem['diskusage']?> MB</strong>
                de
                <strong><?=$hospedagem['disklimit']?> MB</strong>
            </div>

        </div>

        <div class="progress" style="margin-top:10px">
            <div
                class="progress-bar progress-bar-info"
                style="width:<?=$percentual?>%"
            >
                <?=$percentual?>%
            </div>
        </div>

        <?=renderTabelaEmails($emailsValidos)?>

    </div>

    <?php
    return ob_get_clean();
}


function renderTabelaEmails($emails)
{
    ob_start();
    ?>

    <table id="tabelaEmails" class="table table-condensed table-striped">

        <thead>
            <tr>
                <th>E-mail</th>
                <th width="120">Status</th>
                <th width="180">Ações</th>
            </tr>
        </thead>

        <tbody>

        <?php foreach($emails as $email): ?>

            <tr>

                <td><?=$email['email']?></td>

                <td>Ativo</td>

                <td>

                    <button
                        class="btn btn-xs btn-primary alterarSenha"
                        data-email="<?=$email['email']?>"
                    >
                        Alterar Senha
                    </button>

                    <button
                        class="btn btn-xs btn-danger excluirEmail"
                        data-email="<?=$email['email']?>"
                    >
                        Excluir
                    </button>

                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

    <?php
    return ob_get_clean();
}


function renderFormularioNovoEmail()
{
    ob_start();
    ?>

    <div id="areaNovaConta" style="display:none">

        <div class="panel panel-default">

            <div class="panel-heading">
                Nova Conta de E-mail
            </div>

            <div class="panel-body">

                <div class="form-group">

                    <label>Contas (uma por linha)</label>

                    <textarea
                        id="emailsLote"
                        class="form-control"
                        rows="8"
                    ></textarea>

                </div>

                

                <button
                    class="btn btn-success"
                    id="btnCriarEmails"
                >
                    Criar Contas
                </button>

                <button
                    class="btn btn-default"
                    id="btnCancelarEmails"
                >
                    Cancelar
                </button>

                <button
                    class="btn btn-info"
                    id="btnVoltarListagem"
                    style="margin-left: 10px;"
                >
                    Voltar para a listagem
                </button>

				

            </div>

        </div>

    </div>

    <?php
    return ob_get_clean();
}


function criarEmails($hospedagem, $server)
{
    $emails = trim($_POST['emails']);
    $emails = explode("\n", $emails);

    $resultadoWhatsApp = array();

    
  
    foreach($emails as $emailOriginal)
    {
        $emailOriginal = trim($emailOriginal);

        if(!$emailOriginal)
        {
            continue;
        }

        $email = normalizarEmail($emailOriginal);

        $senha = gerarSenha();

        $resultado = criarEmail(
            $hospedagem,
            $server,
            $email,
            $senha
        );


        $dados = isset(
    $resultado['retorno']['cpanelresult']['data'][0]
)
    ? $resultado['retorno']['cpanelresult']['data'][0]
    : array();

$ok = !empty($dados['result']);

$erro = '';

if(!$ok)
{
    $erro = isset($dados['reason'])
        ? $dados['reason']
        : 'Erro desconhecido';
}

$resultadoWhatsApp[] = array(
    'email' => $email.'@'.$hospedagem['domain'],
    'senha' => $senha,
    'ok'    => $ok,
    'erro'  => $erro
);

        echo '<h4>';
        echo $emailOriginal;
        echo ' → ';
        echo $email.'@'.$hospedagem['domain'];
        echo '</h4>';

        echo '<pre>';
        print_r($resultado);
        echo '</pre><hr>';
    }

    echo '<hr><hr>';


    echo gerarMensagemWhatsApp(
    $resultadoWhatsApp,
    $hospedagem['domain']
);

    exit;
}


function criarEmail($hospedagem, $server, $conta, $senha)
{
    $url = 'https://' . $server->hostname . ':2087/json-api/cpanel';

    $params = array(
        'cpanel_jsonapi_user'       => $hospedagem['username'],
        'cpanel_jsonapi_apiversion' => 2,
        'cpanel_jsonapi_module'     => 'Email',
        'cpanel_jsonapi_func'       => 'addpop',
        'email'                     => $conta,
        'domain'                    => $hospedagem['domain'],
        'password'                  => $senha,
        'quota'                     => 250
    );

    $ch = curl_init();

    curl_setopt_array($ch, array(
        CURLOPT_URL => $url . '?' . http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => getAuthHeader($server)
    ));


    $retorno = curl_exec($ch);

    if(curl_errno($ch))
    {
        return array(
            'curl_error' => curl_error($ch)
        );
    }

    curl_close($ch);

    return array(
        'params' => $params,
        'retorno' => json_decode($retorno, true),
        'raw' => $retorno
    );
}


function debugEmails($titulo, $dados)
{
    if(!DEBUG_EMAILS)
    {
        return;
    }

    echo '<strong>'.$titulo.'</strong><br><pre>';
    print_r($dados);
    echo '</pre><hr>';
}

function erroApi($mensagem)
{
    die(
        '<div class="alert alert-danger">'.$mensagem.'</div>'
    );
}

function getPercentualDisco($hospedagem)
{
    if($hospedagem['disklimit'] <= 0)
    {
        return 0;
    }

    return round(
        ($hospedagem['diskusage'] / $hospedagem['disklimit']) * 100
    );
}

function filtrarEmails($emails, $hospedagem)
{
    $retorno = array();

    foreach($emails as $email)
    {
        if(
            !empty($email['email'])
            && $email['email'] != $hospedagem['username']
        )
        {
            $retorno[] = $email;
        }
    }

    return $retorno;
}

function getAuthHeader($server)
{
    return array(
        'Authorization: WHM '.$server->username.':'.$server->accesshash
    );
}


function getEmails($hospedagem, $server)
{

    $url = 'https://' . $server->hostname . ':2087/json-api/cpanel';

	$params = array(
		'cpanel_jsonapi_user'       => $hospedagem['username'],
		'cpanel_jsonapi_apiversion' => 2,
		'cpanel_jsonapi_module'     => 'Email',
		'cpanel_jsonapi_func'       => 'listpops'
	);

    $ch = curl_init();

    curl_setopt_array($ch, array(
        CURLOPT_URL => $url . '?' . http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => getAuthHeader($server)
    ));

    $retorno = curl_exec($ch);

    curl_close($ch);

    $retorno = json_decode($retorno, true);

	if(
		empty($retorno)
		|| !empty($retorno['reason'])
		|| (isset($retorno['result']) && !$retorno['result'])
	)
	{
		erroApi(
			isset($retorno['reason'])
				? $retorno['reason']
				: 'Erro ao consultar API do cPanel'
		);
	}

	//die('<pre>'.print_r($retorno, true).'</pre>');

    return $retorno['cpanelresult']['data'];


}


function gerarSenha()
{
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';

    $senha = rand(1, 9);

    for($i = 0; $i < 10; $i++)
    {
        $senha .= $chars[rand(0, strlen($chars) - 1)];
    }

    return $senha;
}

?>

<script>
$(document).on('keyup', '#filtroEmail', function() {

    var valor = $(this).val().toLowerCase();

    $('#tabelaEmails tbody tr').each(function() {

        $(this).toggle(
            $(this).text().toLowerCase().indexOf(valor) > -1
        );

    });

});

$(document)
    .off('click', '#btnNovoEmail')
    .on('click', '#btnNovoEmail', function(){

    $('#areaListaEmails').hide();
	$('#areaNovaConta').show();

});

$(document)
    .off('click', '#btnCancelarEmails')
    .on('click', '#btnCancelarEmails', function(){

    $('#areaNovaConta').hide();
	$('#areaListaEmails').show();

});

$(document)
    .off('click', '#btnCriarEmails')
    .on('click', '#btnCriarEmails', function(){

    var botao = $(this);

    botao
        .prop('disabled', true)
        .html('Criando...');

    $('#resultadoEmails')
        .show()
        .html('Processando, aguarde...');

    $.post(
        'automacao-emails 2.php?id=<?=$idServico?>',
        {
            acao: 'criarEmails',
            emails: $('#emailsLote').val()
            
        },
        function(retorno){

            $('#resultadoEmails').html(retorno);

        }
    )
    .fail(function(){

        $('#resultadoEmails').html(
            '<div class="alert alert-danger">Erro ao processar.</div>'
        );

    })
    .always(function(){

        botao
            .prop('disabled', false)
            .html('Criar Contas');

    });

});

$(document)
    .off('click', '#btnVoltarListagem')
    .on('click', '#btnVoltarListagem', function(){

    $('#btnCancelarEmails').trigger('click');

});


function copiarWhatsApp()
{
    $('#textoWhatsApp').select();

    document.execCommand('copy');

    alert('Texto copiado');
}


$(document)
    .off('click', '.alterarSenha')
    .on('click', '.alterarSenha', function(){

    var email = $(this).data('email');

    if(
        !confirm(
            'Alterar a senha do e-mail:\n\n' + email + '?'
        )
    ){
        return;
    }

    $('#resultadoEmails').show().html('Alterando senha, aguarde...');

    $.post(
        'automacao-emails 2.php?id=<?=$idServico?>',
        {
            acao: 'alterarSenha',
            email: email
        },
        function(retorno){
            $('#resultadoEmails').html(retorno);
        }
    );

});

$(document)
    .off('click', '.excluirEmail')
    .on('click', '.excluirEmail', function(){

    var email = $(this).data('email');

    if(
        !confirm(
            'Excluir o e-mail:\n\n' + email + '?'
        )
    ){
        return;
    }

   $.post(
        'automacao-emails 2.php?id=<?=$idServico?>',
        {
            acao: 'excluirEmail',
            email: email
        },
        function(retorno){

            if(retorno == 'OK')
            {
                $('[data-email="' + email + '"]')
                    .closest('tr')
                    .remove();

                return;
            }

            $('#resultadoEmails')
                .show()
                .text(retorno);

        }
    );

});





</script>