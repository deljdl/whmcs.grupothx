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

define('DEBUG_EMAILS', false);

// Configuração do diretório de armazenamento de credenciais
define('EMAIL_STORAGE_DIR', __DIR__ . '/email-data-kutyfkuvkjhfhNOTACESS/');
// Chave secreta para gerar nomes de arquivos impossíveis de adivinhar.
// RECOMENDAÇÃO: Mude a string abaixo para uma chave aleatória sua.
define('EMAIL_STORAGE_SECRET', 'SuaChaveSecreta_@THX_2024');


function executarAcaoWHM($server, $funcao, $params = array())
{
    $url = 'https://' . $server->hostname . ':2087/json-api/' . $funcao;

    $params['api.version'] = 1;

    $ch = curl_init();

    curl_setopt_array($ch, array(
        CURLOPT_URL => $url . '?' . http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER => getAuthHeader($server),
        CURLOPT_TIMEOUT => 60
    ));

    $raw = curl_exec($ch);
    $error = curl_error($ch);

    curl_close($ch);

    if($error)
    {
        return array(
            'error_msg' => 'Erro de Conexão cURL: ' . $error,
            'raw' => $raw,
            'params' => $params
        );
    }

    $decoded = json_decode($raw, true);

    if(json_last_error() !== JSON_ERROR_NONE)
    {
        return array(
            'error_msg' => 'Resposta inválida do servidor (Não é JSON)',
            'raw' => $raw,
            'params' => $params
        );
    }

    return array(
        'params' => $params,
        'retorno' => $decoded,
        'raw' => $raw
    );
}


function alterarStatusEnvioEmail($hospedagem, $server, $email, $status)
{
    $funcao = ($status == 'bloquear')
        ? 'suspend_outgoing'
        : 'unsuspend_outgoing';

    $params = array(
        'cpanel_jsonapi_user'       => strtolower($hospedagem['username']),
        'cpanel_jsonapi_apiversion' => 3,
        'cpanel_jsonapi_module'     => 'Email',
        'cpanel_jsonapi_func'       => $funcao,
        'email'                     => $email
    );

    return executarAcaoWHM(
        $server,
        'cpanel',
        $params
    );
}


function getCaminhoArquivo($idServico) {
    $hash = hash_hmac('sha256', $idServico, EMAIL_STORAGE_SECRET);
    return EMAIL_STORAGE_DIR . "data_{$hash}.json";
}

function getEmailsGerenciados($idServico) {
    $arquivo = getCaminhoArquivo($idServico);
    if (!file_exists($arquivo)) return [];
    $conteudo = file_get_contents($arquivo);
    return json_decode($conteudo, true) ?: [];
}

function salvarCredencialEmail($idServico, $email, $senha, $criadoAgora = false) {
    if (!is_dir(EMAIL_STORAGE_DIR)) {
        mkdir(EMAIL_STORAGE_DIR, 0755, true);
        file_put_contents(EMAIL_STORAGE_DIR . '.htaccess', "deny from all");
    }
    $arquivo = getCaminhoArquivo($idServico);
    $emails = getEmailsGerenciados($idServico);

    $jaEraCriadoPeloScript = (isset($emails[$email]) && !empty($emails[$email]['criadoPeloScript']));

    $emails[$email] = [
        'senha' => $senha,
        'data' => date('Y-m-d H:i:s'),
        'criadoPeloScript' => ($criadoAgora || $jaEraCriadoPeloScript)
    ];
    file_put_contents($arquivo, json_encode($emails, JSON_PRETTY_PRINT));
}

function removerCredencialEmail($idServico, $email) {
    $arquivo = getCaminhoArquivo($idServico);
    $emails = getEmailsGerenciados($idServico);
    if (isset($emails[$email])) {
        unset($emails[$email]);
        file_put_contents($arquivo, json_encode($emails, JSON_PRETTY_PRINT));
    }
}

function getHeaderWhatsApp($dominio, $acao = 'criado', $quantidade = 1)
{
    if ($acao == 'senha') {
        $texto = $quantidade > 1
            ? "Senhas de e-mails corporativos @*" . $dominio . "* alteradas"
            : "Senha de e-mail corporativo @*" . $dominio . "* alterada";
    }
    elseif ($acao == 'excluido') {
        $texto = $quantidade > 1
            ? "E-mails corporativos @*" . $dominio . "* excluídos"
            : "E-mail corporativo @*" . $dominio . "* excluído";
    }
    else {
        $texto = $quantidade > 1
            ? "E-mails corporativos @*" . $dominio . "* criados"
            : "E-mail corporativo @*" . $dominio . "* criado";
    }

    return "_" . $texto . ":_\n\n";
}

function getFooterWhatsApp($dominio) {
    return "Para acessar seu e-mail pelo navegador acesse o o link https://webmail." . $dominio;
}

function getFooterSimpleWhatsApp($dominio) {
    return "Para acessar seu e-mail pelo navegador acesse o o link https://webmail." . $dominio;
}

$idServico = (int)$_GET['id'];


$acao = isset($_POST['acao']) ? $_POST['acao'] : '';

if(!$idServico)
{
    echo '<pre>'.print_r($_GET, true).'</pre>';
    die('Serviço inválido');
}

$hospedagem = getDadosHospedagemById($idServico);

$server = Capsule::table('tblservers')
    ->where('id', $hospedagem['serverid'])
    ->first();

if($acao == 'alterarStatusEnvioConta')
{
    header('Content-Type: application/json');

    $status = isset($_POST['status']) ? $_POST['status'] : '';

    if(!in_array($status, array('bloquear', 'desbloquear')))
    {
        echo json_encode(array(
            'sucesso' => false,
            'mensagem' => 'Ação inválida.'
        ));
        exit;
    }

    $funcao = ($status == 'bloquear')
        ? 'suspend_outgoing_email'
        : 'unsuspend_outgoing_email';

    $resultado = executarAcaoWHM(
        $server,
        $funcao,
        array(
            'user' => $hospedagem['username']
        )
    );

    ob_start();

    echo '<h4>Controle de envio da conta</h4>';

    echo '<b>Conta:</b> '
        .htmlspecialchars($hospedagem['username'], ENT_QUOTES, 'UTF-8')
        .'<br>';

    echo '<b>Domínio principal:</b> '
        .htmlspecialchars($hospedagem['domain'], ENT_QUOTES, 'UTF-8')
        .'<br>';

    echo '<b>Ação WHM:</b> '.$funcao.'<br><br>';

    if(isset($resultado['error_msg']))
    {
        echo '<div class="alert alert-danger">';
        echo '<b>Erro de comunicação:</b><br>';
        echo htmlspecialchars($resultado['error_msg'], ENT_QUOTES, 'UTF-8');
        echo '</div>';

        echo '<h4>Resposta:</h4>';
        echo '<pre>'.htmlspecialchars(print_r($resultado, true), ENT_QUOTES, 'UTF-8').'</pre>';

        $debug = ob_get_clean();

        echo json_encode(array(
            'sucesso' => false,
            'mensagem' => $resultado['error_msg'],
            'debug' => $debug
        ));
        exit;
    }

    echo '<h4>Resposta da API WHM</h4>';
    echo '<pre>'.htmlspecialchars(print_r($resultado, true), ENT_QUOTES, 'UTF-8').'</pre>';

    $retorno = $resultado['retorno'];

    if(
        isset($retorno['metadata']['result']) &&
        $retorno['metadata']['result'] == 1
    )
    {
        $mensagem = ($status == 'bloquear')
            ? 'Envio da conta bloqueado.'
            : 'Envio da conta desbloqueado.';

        echo '<div class="alert alert-success">';
        echo '<b>✓ '.$mensagem.'</b><br><br>';

        if($status == 'bloquear')
        {
            echo 'Toda a conta foi bloqueada para envio externo.';
        }
        else
        {
            echo 'Toda a conta foi desbloqueada para envio externo.';
        }

        echo '</div>';

        $debug = ob_get_clean();

        echo json_encode(array(
            'sucesso' => true,
            'mensagem' => $mensagem,
            'debug' => $debug
        ));
        exit;
    }

    $mensagem = 'O WHM não confirmou a operação.';

    if(isset($retorno['metadata']['reason']))
    {
        $mensagem .= ' '.$retorno['metadata']['reason'];
    }

    echo '<div class="alert alert-danger">';
    echo '<b>'.$mensagem.'</b>';
    echo '</div>';

    $debug = ob_get_clean();

    echo json_encode(array(
        'sucesso' => false,
        'mensagem' => $mensagem,
        'debug' => $debug
    ));
    exit;
}


if($acao == 'alterarStatusEnvio')
{
    header('Content-Type: application/json');

    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $status = isset($_POST['status']) ? $_POST['status'] : '';

    if(!$email || !in_array($status, array('bloquear', 'desbloquear')))
    {
        echo json_encode(array(
            'sucesso' => false,
            'mensagem' => 'Dados inválidos.'
        ));
        exit;
    }

    $resultado = alterarStatusEnvioEmail(
        $hospedagem,
        $server,
        $email,
        $status
    );

    if(isset($resultado['error_msg']))
    {
        echo json_encode(array(
            'sucesso' => false,
            'mensagem' => $resultado['error_msg']
        ));
        exit;
    }

    $retorno = $resultado['retorno'];

    if(
        isset($retorno['result']) &&
        isset($retorno['result']['status']) &&
        $retorno['result']['status'] == 1
    )
    {
        echo json_encode(array(
            'sucesso' => true,
            'mensagem' => ($status == 'bloquear')
                ? 'Envio bloqueado.'
                : 'Envio desbloqueado.'
        ));
        exit;
    }

    $mensagem = 'Não foi possível alterar o status de envio.';

    if(isset($retorno['result']['errors'][0]))
    {
        $mensagem = $retorno['result']['errors'][0];
    }

    echo json_encode(array(
        'sucesso' => false,
        'mensagem' => $mensagem
    ));
    exit;
}

if($acao == 'testarEnvio')
{
    testarEnvio($hospedagem);
    exit;
}


if($acao == 'criarEmails')
{
    criarEmails($hospedagem, $server);
    exit;
}

if($acao == 'alterarSenha')
{
    alterarSenha($hospedagem, $server);
    exit;
}

if($acao == 'alterarSenhaLote')
{
    alterarSenhaLote($hospedagem, $server);
    exit;
}

if($acao == 'excluirEmail')
{
    excluirEmail($hospedagem, $server);
    exit;
}

if($acao == 'excluirEmailLote')
{
    excluirEmailLote($hospedagem, $server);
    exit;
}

$emails = getEmails($hospedagem, $server);

echo '

<!--Icons by X-Painel-->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/Grupo-THX/xpainel-assets/icons.css" />
<!--End Icons by X-Painel-->
<style>
#resultadoEmails {
	margin: 15px 0;
	max-height: 30px;
	display: block;
	overflow: auto;
	border: solid #CCCCCC 1px;
	display: none;
	transition: max-height 0.5s ease;
	padding: 5px;
}
#resultadoEmails pre{ font-size: 10px; }
#resultadoEmails h4 { margin: 5px 0; font-size: 10px; font-weight: bold; }
.resultadoEmailsAberto { max-height: none !important; }
#areaWhatsApp { margin: 15px 0; }
#tabelaEmails tbody tr { border-left: solid transparent 5px; border-right: solid transparent 5px; transition: border-color 0.3s ease; }
#tabelaEmails tbody tr:hover { border-left: solid #2e6da4 5px; border-right: solid #2e6da4 5px; }
#textoWhatsApp { height: 1px;overflow: hidden; width: 1px; }

.btnStatusEnvio {
    border: 0;
    cursor: pointer;
}

.btn-block-all-core {
    float: right;
	margin: -85px 60px 0 0;
}

</style>
<div id="resultadoEmails" onclick="copyThisContents(this)"></div>
<div id="areaWhatsApp"></div>';

debugEmails('SERVER', $server);
debugEmails('EMAILS', $emails);
debugEmails('HOSPEDAGEM', $hospedagem);



$emailsValidos = filtrarEmails($emails, $hospedagem);
$countEmails = count($emailsValidos);
$quota = consultarQuotaWHM($server, $hospedagem['username']);

$percentual = $quota['percentual'];

echo renderListaEmails(
    $emailsValidos,
    $countEmails,
    $percentual,
    $hospedagem,
    $quota
);

echo renderFormularioNovoEmail();

function testarEnvio($hospedagem)
{
    header('Content-Type: application/json');

    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    $destinatariosFixos = array(
        'deljdl+test@gmail.com',
        'deljdl@proton.me',
        'deljdl@hotmail.com',
        'deljdl@yahoo.com.br',
        'ping@tools.mxtoolbox.com',
        'deljdl@zohomail.com',
        'deljdl@icloud.com'
    );

    $destinatarios = isset($_POST['destinatarios'])
        ? $_POST['destinatarios']
        : array();

    $destinatarios = array_unique(
        array_merge($destinatariosFixos, $destinatarios)
    );

    if(!$email || !is_array($destinatarios))
    {
        echo json_encode(array(
            'status' => 'ERRO',
            'mensagem' => 'Remetente inválido.'
        ));
        exit;
    }

    $emailsGerenciados = getEmailsGerenciados($hospedagem['id']);

    if(
        !isset($emailsGerenciados[$email]) ||
        empty($emailsGerenciados[$email]['senha'])
    )
    {
        echo json_encode([
            'status' => 'ERRO',
            'mensagem' => 'A senha deste e-mail não está salva no sistema.'
        ]);
        exit;
    }

    $senha = $emailsGerenciados[$email]['senha'];

    $dominio = $hospedagem['domain'];
    $servidorSMTP = 'mail.' . $dominio;

    $dataHora = date('d/m/Y, H:i:s');

    $hora = (int) date('H');

    if ($hora < 12) {
        $saudacao = 'Bom dia';
    } elseif ($hora < 18) {
        $saudacao = 'Boa tarde';
    } else {
        $saudacao = 'Boa noite';
    }

    $assunto = 'Teste de envio do suporte - ' . $dataHora;

    $mensagem = <<<HTML
<div style="margin:0;padding:30px 15px;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#333;">

    <div style="width:100%;max-width:600px;margin:0 auto;background:#ffffff;border:1px solid #e1e5e9;border-radius:8px;overflow:hidden;">

        <div style="background:#245b87;padding:24px 30px;text-align:center;">
            <div style="font-size:22px;font-weight:bold;color:#ffffff;">
                Teste de envio de e-mail
            </div>

            <div style="font-size:13px;color:#dceaf5;margin-top:6px;">
                Equipe de Suporte
            </div>
        </div>

        <div style="padding:30px;">

            <div style="font-size:17px;font-weight:bold;color:#333;margin-bottom:20px;">
                {$saudacao},
            </div>

            <div style="font-size:14px;line-height:1.6;color:#555;margin-bottom:18px;">
                Este é um envio de teste realizado pela nossa equipe de suporte,
                com o objetivo de garantir que o sistema de envio de e-mails
                esteja funcionando corretamente.
            </div>

            <div style="font-size:14px;line-height:1.6;color:#555;margin-bottom:22px;">
                Seguimos trabalhando continuamente para oferecer a melhor
                experiência possível em nossos serviços.
            </div>

            <div style="background:#f5f8fb;border:1px solid #dfe8ef;border-radius:6px;padding:14px 16px;margin-bottom:22px;">
                <div style="font-size:12px;color:#777;margin-bottom:4px;">
                    DATA DO TESTE
                </div>

                <div style="font-size:14px;font-weight:bold;color:#245b87;">
                    📅 {$dataHora}
                </div>
            </div>

            <div style="font-size:14px;line-height:1.6;color:#555;margin-bottom:22px;">
                Caso tenha qualquer dúvida ou não reconheça este envio,
                por favor, responda a este e-mail que teremos prazer em ajudar.
            </div>

            <div style="font-size:14px;line-height:1.6;color:#555;">
                Atenciosamente,<br>
                <strong style="color:#333;">Equipe de Suporte</strong>
            </div>

        </div>

        <div style="background:#f4f6f8;border-top:1px solid #e1e5e9;padding:16px 30px;text-align:center;">
            <div style="font-size:11px;color:#888;">
                Esta é uma mensagem automática de teste de envio.
            </div>
        </div>

    </div>

</div>
HTML;

    $mensagemTexto = "{$saudacao},

Este é um envio de teste realizado pela nossa equipe de suporte, com o objetivo de garantir que o sistema de envio de e-mails esteja funcionando corretamente.

Seguimos trabalhando continuamente para oferecer a melhor experiência possível em nossos serviços.

Data do teste: {$dataHora}.

Caso tenha qualquer dúvida ou não reconheça este envio, por favor, responda a este e-mail que teremos prazer em ajudar.

Atenciosamente,
Equipe de Suporte

Esta é uma mensagem automática de teste de envio.";

    $socket = @stream_socket_client(
        'ssl://' . $servidorSMTP . ':465',
        $errno,
        $errstr,
        20,
        STREAM_CLIENT_CONNECT
    );

    if(!$socket)
    {
        echo json_encode([
            'status' => 'ERRO',
            'mensagem' => 'Não foi possível conectar ao SMTP: ' . $errstr
        ]);
        exit;
    }

    function smtpComandoTeste($socket, $comando)
    {
        fwrite($socket, $comando . "\r\n");

        $resposta = '';

        while($linha = fgets($socket, 515))
        {
            $resposta .= $linha;

            if(strlen($linha) >= 4 && $linha[3] == ' ')
                break;
        }

        return $resposta;
    }

    $resposta = fgets($socket, 515);

    smtpComandoTeste($socket, 'EHLO ' . $dominio);

    smtpComandoTeste($socket, 'AUTH LOGIN');

    $respostaUsuario = fgets($socket, 515);

    fwrite($socket, base64_encode($email) . "\r\n");

    $respostaSenha = fgets($socket, 515);

    fwrite($socket, base64_encode($senha) . "\r\n");

    $auth = fgets($socket, 515);

    if(strpos($auth, '235') === false)
    {
        fclose($socket);

        echo json_encode([
            'status' => 'ERRO',
            'mensagem' => 'Falha na autenticação SMTP.',
            'resposta' => trim($auth)
        ]);
        exit;
    }

    $boundary = '=_Part_' . md5(uniqid());

    $corpo =
        '--' . $boundary . "\r\n" .
        'Content-Type: text/plain; charset=UTF-8' . "\r\n" .
        'Content-Transfer-Encoding: quoted-printable' . "\r\n\r\n" .
        quoted_printable_encode($mensagemTexto) . "\r\n\r\n" .

        '--' . $boundary . "\r\n" .
        'Content-Type: text/html; charset=UTF-8' . "\r\n" .
        'Content-Transfer-Encoding: quoted-printable' . "\r\n\r\n" .
        quoted_printable_encode($mensagem) . "\r\n\r\n" .

        '--' . $boundary . "--\r\n";

    $respostasEnvio = array();

    foreach($destinatarios as $destinatario)
    {
        $destinatario = trim($destinatario);

        if(!$destinatario)
            continue;

        /*
         * Cada destinatário recebe uma mensagem SMTP independente.
         */

        smtpComandoTeste(
            $socket,
            'MAIL FROM:<' . $email . '>'
        );

        smtpComandoTeste(
            $socket,
            'RCPT TO:<' . $destinatario . '>'
        );

        smtpComandoTeste(
            $socket,
            'DATA'
        );

        /*
         * O destinatário atual aparece somente no To:.
         * Não existe CC nem BCC.
         */

        $headers =
            'From: ' . $email . "\r\n" .
            'To: ' . $destinatario . "\r\n" .
            'Subject: ' . $assunto . "\r\n" .
            'Date: ' . date('r') . "\r\n" .
            'Message-ID: <' . uniqid('', true) . '@' . $dominio . ">\r\n" .
            'MIME-Version: 1.0' . "\r\n" .
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"' . "\r\n";

        fwrite(
            $socket,
            $headers .
            "\r\n" .
            $corpo .
            "\r\n.\r\n"
        );

        /*
         * Aguarda a resposta final do SMTP para esta mensagem.
         */

        $respostaEnvio = '';

        while($linha = fgets($socket, 515))
        {
            $respostaEnvio .= $linha;

            if(strlen($linha) >= 4 && $linha[3] == ' ')
                break;
        }

        $respostasEnvio[] = array(
            'destinatario' => $destinatario,
            'resposta' => trim($respostaEnvio)
        );
    }

    /*
     * Fecha a sessão SMTP somente depois de todos os destinatários.
     */

    smtpComandoTeste($socket, 'QUIT');

    fclose($socket);

    /*
     * Verifica se algum destinatário foi recusado.
     */

    $falhas = array();

    foreach($respostasEnvio as $envio)
    {
        if(strpos($envio['resposta'], '250') === false)
        {
            $falhas[] = $envio;
        }
    }

    if(count($falhas))
    {
        echo json_encode([
            'status' => 'ERRO',
            'mensagem' => 'Um ou mais destinatários não foram aceitos pelo SMTP.',
            'respostas' => $respostasEnvio
        ]);
        exit;
    }

    /*
     * Mantém a última resposta para compatibilidade com o restante do fluxo.
     */

    $respostaEnvio = '';

    foreach($respostasEnvio as $envio)
    {
        $respostaEnvio .= $envio['destinatario'] . ': ' .
            $envio['resposta'] . "\n";
    }

    $idExim = '';

    $assuntoLog = addslashes($assunto);

    $comando = "grep 'T=\"{$assuntoLog}\"' /var/log/exim_mainlog";

    if(strpos($respostaEnvio, '250') === false)
    {
        echo json_encode([
            'status' => 'ERRO',
            'mensagem' => 'O SMTP não confirmou o envio.',
            'resposta' => trim($respostaEnvio),
            'comando' => $comando
        ]);
        exit;
    }

    echo json_encode([
        'status' => 'OK',
        'remetente' => $email,
        'destinatarios' => $destinatarios,
        'dataHora' => $dataHora,
        'idExim' => $idExim,
        'resposta' => trim($respostaEnvio),
        'comando' => $comando
    ]);
}


function alterarSenha($hospedagem, $server)
{
    $emailCompleto = trim($_POST['email']);
    if(!$emailCompleto) die('E-mail inválido');

    $parts = explode('@', $emailCompleto);
    $user = $parts[0];
    $novaSenha = gerarSenha();

    $retorno = alterarSenhaAPI($hospedagem, $server, $user, $novaSenha);
    $ok = !empty($retorno['retorno']['cpanelresult']['data'][0]['result']);

    if($ok)
    {
        salvarCredencialEmail($hospedagem['id'], $emailCompleto, $novaSenha, false);
        $texto = getHeaderWhatsApp($hospedagem['domain'], 'senha') . "*Email:* " . $emailCompleto . "\n*Senha:* " . $novaSenha . "\n\n" . getFooterWhatsApp($hospedagem['domain']);
        header('Content-Type: application/json');
        ob_start();
        echo '<h4>Sucesso: '.$emailCompleto.'</h4><pre>'; print_r($retorno); echo '</pre>';
        echo json_encode(['status' => 'OK', 'texto' => $texto, 'debug' => ob_get_clean()]);
        exit;
    }

    header('Content-Type: application/json');
    ob_start();
    echo '<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert">&times;</button>Erro ao alterar a senha do e-mail: '.$emailCompleto.'</div>';
    echo '<pre>'; print_r($retorno); echo '</pre>';
    echo json_encode(['status' => 'ERRO', 'html' => ob_get_clean()]);
}

function alterarSenhaLote($hospedagem, $server)
{
    $emails = $_POST['emails'];
    if(!is_array($emails)) die('Nenhum e-mail selecionado');

    $resultadoWhatsApp = array();
    ob_start();

    foreach($emails as $emailCompleto) {
        $parts = explode('@', $emailCompleto);
        $user = $parts[0];
        $novaSenha = gerarSenha();

        $retorno = alterarSenhaAPI($hospedagem, $server, $user, $novaSenha);
        $ok = !empty($retorno['retorno']['cpanelresult']['data'][0]['result']);

        if($ok) {
            salvarCredencialEmail($hospedagem['id'], $emailCompleto, $novaSenha, false);
        }

        $resultadoWhatsApp[] = array(
            'email' => $emailCompleto,
            'senha' => $novaSenha,
            'ok'    => $ok,
            'erro'  => !$ok ? 'Erro na API' : ''
        );

        echo '<h4>Processando: '.$emailCompleto.'</h4><pre>'; print_r($retorno); echo '</pre><hr>';
    }

    $debug = ob_get_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'OK',
        'debug' => $debug,
        'html' => gerarMensagemWhatsApp($resultadoWhatsApp, $hospedagem['domain'])
    ]);
}

function alterarSenhaAPI($hospedagem, $server, $email, $senha)
{
    $params = array(
        'cpanel_jsonapi_user'       => $hospedagem['username'],
        'cpanel_jsonapi_apiversion' => 2,
        'cpanel_jsonapi_module'     => 'Email',
        'cpanel_jsonapi_func'       => 'passwdpop',
        'email'                     => $email,
        'domain'                    => $hospedagem['domain'],
        'password'                  => $senha
    );
    return executarRequisicaoCpanel($server, $params);
}

function excluirEmail($hospedagem, $server)
{
    $email = trim($_POST['email']);
    if(!$email) die('E-mail inválido');

    // Validação de Segurança no PHP: Verifica se foi criado pelo script
    // $emailsGerenciados = getEmailsGerenciados($hospedagem['id']);
    // if (!isset($emailsGerenciados[$email]) || empty($emailsGerenciados[$email]['criadoPeloScript'])) {
    //     header('Content-Type: application/json');
    //     echo json_encode(['status' => 'ERRO', 'html' => '<div class="alert alert-danger">Ação não permitida: Esta conta não foi criada pelo script.</div>']);
    //     exit;
    // }

    $retorno = excluirEmailAPI($hospedagem, $server, $email);
    $ok = !empty($retorno['retorno']['cpanelresult']['data'][0]['result']);

    header('Content-Type: application/json');
    if($ok) {
        removerCredencialEmail($hospedagem['id'], $email);
        echo json_encode(['status' => 'OK']);
        exit;
    }

    ob_start();
    echo '<h4>Erro ao excluir: '.$email.'</h4>';
    echo '<pre>'; print_r($retorno); echo '</pre>';
    echo json_encode(['status' => 'ERRO', 'html' => ob_get_clean()]);
}

function excluirEmailLote($hospedagem, $server)
{
    $emails = $_POST['emails'];
    if(!is_array($emails)) die('Nenhum e-mail selecionado');

    ob_start();
    $emailsGerenciados = getEmailsGerenciados($hospedagem['id']);
    $excluidos = [];

    foreach($emails as $email) {
        // Validação de Segurança individual para o lote
        // if (!isset($emailsGerenciados[$email]) || empty($emailsGerenciados[$email]['criadoPeloScript'])) {
        //     echo '<h4>Ignorado: '.$email.'</h4><p class="text-warning">Ação não permitida: Esta conta não foi criada pelo script.</p><hr>';
        //     continue;
        // }

        $retorno = excluirEmailAPI($hospedagem, $server, $email);
        $ok = !empty($retorno['retorno']['cpanelresult']['data'][0]['result']);
        if($ok) {
            removerCredencialEmail($hospedagem['id'], $email);
            $excluidos[] = $email;
        }
        echo '<h4>Excluindo: '.$email.'</h4><pre>'; print_r($retorno); echo '</pre><hr>';
    }

    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'OK',
        'debug' => ob_get_clean(),
        'excluidos' => $excluidos
    ]);
}

function excluirEmailAPI($hospedagem, $server, $email)
{
    $params = array(
        'cpanel_jsonapi_user'       => $hospedagem['username'],
        'cpanel_jsonapi_apiversion' => 2,
        'cpanel_jsonapi_module'     => 'Email',
        'cpanel_jsonapi_func'       => 'delpop',
        'email'                     => $email
    );
    return executarRequisicaoCpanel($server, $params);
}

function normalizarEmail($texto)
{
    $texto = trim($texto);
    if(strpos($texto, '@') !== false) return strtolower(substr($texto, 0, strpos($texto, '@')));

    $texto = strtolower($texto);
    $trocas = array(
        'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
        'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i','ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o',
        'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c'
    );
    $texto = strtr($texto, $trocas);
    $texto = str_replace(array('-', '_'), ' ', $texto);
    $texto = preg_replace('/[^a-z0-9._ -]/', '', $texto);
    $texto = preg_replace('/\s+/', '.', $texto);
    return trim($texto, '.');
}

function gerarMensagemWhatsApp($emails, $dominio)
{
    $ok = array(); $erros = array();
    foreach($emails as $email) {
        if($email['ok']) $ok[] = $email; else $erros[] = $email;
    }

    $htmlErros = '';
    if($erros) {
        $htmlErros .= '<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>Falha na criação:</strong><br><br>';
        foreach($erros as $erro) {
            $htmlErros .= '<b>'.$erro['email'].'</b> => <span class="text-danger">'.$erro['erro'].'</span><br>';
        }
        $htmlErros .= '</div>';
    }

    if(!$ok) return $htmlErros . '<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert">&times;</button>Nenhuma conta criada.</div>';

    $texto = getHeaderWhatsApp($dominio, 'criado', count($ok));
    foreach($ok as $email) {
        $texto .= "*Email:* " . $email['email'] . "\n*Senha:* " . $email['senha'] . "\n\n";
    }
    $texto .= getFooterWhatsApp($dominio);

    return $htmlErros . '<hr><button class="btn btn-success" onclick="copiarWhatsApp(this)"><i class="fa fa-whatsapp"></i> Copiar WhatsApp</button>
    <textarea id="textoWhatsApp">'.htmlspecialchars($texto).'</textarea>';
}

function renderListaEmails($emailsValidos, $countEmails, $percentual, $hospedagem, $quota)
{
    ob_start();
    $envioContaSuspenso = isset($quota['dados']['outgoing_mail_suspended'])
        ? (int)$quota['dados']['outgoing_mail_suspended']
        : 0;
    ?>

    <div id="areaListaEmails">

        <div class="row">

            <div class="col-md-6">
                <h4 style="margin-top:0">
                    <?=$countEmails?> contas de e-mail
                    <small><?=$hospedagem['domain']?></small>
                </h4>
            </div>

            <div class="col-md-6 text-right">

            

                    <?php if($envioContaSuspenso): ?>

                        <button class="btn btn-sm btn-success btn-block-all-core" id="btnBloquearConta" data-status="desbloquear">
                            <i class="fa fa-unlock"></i> Desbloquear Envio da Conta
                        </button>

                    <?php else: ?>

                        <button class="btn btn-sm btn-danger btn-block-all-core" id="btnBloquearConta" data-status="bloquear">
                            <i class="fa fa-ban"></i> Bloquear Envio da Conta
                        </button>

                    <?php endif; ?>


                <button class="btn btn-info" id="btnRefreshLista" style="margin-right:5px" title="Atualizar Lista">
                    <i class="fa fa-refresh"></i>
                </button>
                <button class="btn btn-default" id="btnCopiarInstrucoes" style="margin-right:5px" title="Copiar Instruções de Configuração">
                    <i class="fa fa-info-circle"></i>
                </button>
                <button class="btn btn-default" id="btnCopiarLista" style="margin-right:5px" title="Copiar Lista de E-mails">
                    <i class="fa fa-list"></i>
                </button>
                <button class="btn btn-default" id="btnCopiarListaSenhas" style="margin-right:5px" title="Copiar Lista com Senhas">
                    <i class="fa fa-key"></i>
                </button>
                <button class="btn btn-success" id="btnNovoEmail" title="Criar Nova Conta de E-mail">
                    <i class="fa fa-plus"></i>
                </button>
            </div>

        </div>

        <div class="row" style="margin-top:15px; margin-bottom: 10px;">
            <div class="col-md-12">
                <div class="well well-sm" style="margin-bottom:0">
                    <span style="margin-right:10px"><b>Ações em massa:</b> (<span id="countSelecionados">0</span> selecionados)</span>
                    
                    <button class="btn btn-sm btn-primary" id="btnAlterarSenhaMassa">Alterar Senhas</button>
                    <button class="btn btn-sm btn-danger" id="btnExcluirMassa">Excluir Selecionados</button>
                </div>
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
                <strong><?=number_format($quota['diskused_gb'], 2, ',', '.')?> GB</strong>
                de
                <strong><?=number_format($quota['disklimit_gb'], 2, ',', '.')?> GB</strong>
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

        <?=renderTabelaEmails($emailsValidos, $hospedagem)?>

    </div>

    <?php
    return ob_get_clean();
}


function renderTabelaEmails($emails, $hospedagem)
{
    ob_start();
    $idServico = $hospedagem['id'];
    $emailsGerenciados = getEmailsGerenciados($idServico);
    ?>

    <table id="tabelaEmails" class="table table-condensed table-striped">

        <thead>
            <tr>
                <th width="30"><input type="checkbox" id="selectAll"></th>
                <th>E-mail</th>
                <th width="">Cota</th>
                <th width="">Uso</th>
                <th width="">%</th>
                <th width="180"></th>
            </tr>
        </thead>

        <tbody>

        <?php foreach($emails as $email): ?>
            <?php
                $infoLocal = isset($emailsGerenciados[$email['email']]) ? $emailsGerenciados[$email['email']] : null;
                $temSenhaSalva = !empty($infoLocal['senha']);
                $foiCriadoPeloScript = !empty($infoLocal['criadoPeloScript']);

                $textoWhatsApp = "";
                if ($temSenhaSalva) {
                    $senhaExibicao = $infoLocal['senha'];
                    $textoWhatsApp = getHeaderWhatsApp($hospedagem['domain']) . "*Email:* " . $email['email'] . "\n*Senha:* " . $senhaExibicao . "\n\n" . getFooterSimpleWhatsApp($hospedagem['domain']);
                }


                $usoEmail = isset($email['humandiskused'])
                    ? $email['humandiskused']
                    : $email['diskused'].' MB';

                $limiteEmail = isset($email['humandiskquota'])
                    ? $email['humandiskquota']
                    : $email['diskquota'].' MB';

                $percentualEmail = isset($email['diskusedpercent'])
                    ? $email['diskusedpercent']
                    : 0;

            ?>

            <tr>
                <td>
                    <input type="checkbox" class="emailCheckbox" value="<?=$email['email']?>" title="Selecionar para ações em massa">
                </td>
                <td><?=$email['email']?></td>
                <td><?=$limiteEmail?></td>
                <td><?=$usoEmail?></td>
                <td><?=$percentualEmail?>%</td>

                <td>
                    <?php
                    $labels = [];
                    $sLog = isset($email['suspended_login']) ? $email['suspended_login'] : 0;
                    $sInc = isset($email['suspended_incoming']) ? $email['suspended_incoming'] : 0;
                    $sOut = isset($email['suspended_outgoing'])
                        ? $email['suspended_outgoing']
                        : 'N/A';

                    if ($sOut == 1)
                    {
                        $labels[] =
                            '<button type="button" class="btn btn-xs btn-danger btnStatusEnvio" '.
                            'data-email="'.htmlspecialchars($email['email'], ENT_QUOTES, 'UTF-8').'" '.
                            'data-status="desbloquear" '.
                            'title="Envio bloqueado — clique para desbloquear">'.
                            '<i class="fa fa-unlock"></i> Desbloquear Envio</button>';
                    }
                    else
                    {
                        $labels[] =
                            '<button type="button" class="btn btn-xs btn-success btnStatusEnvio" '.
                            'data-email="'.htmlspecialchars($email['email'], ENT_QUOTES, 'UTF-8').'" '.
                            'data-status="bloquear" '.
                            'title="Envio ativo — clique para bloquear">'.
                            '<i class="fa fa-ban"></i> Bloquear Envio</button>';
                    }

                    // Selo API contendo o retorno bruto para debug individual
                    //$labels[] = '<span class="label label-default" style="cursor:help" title="'.htmlspecialchars(json_encode($email)).'">API</span>';

                    echo '<div>' . implode(' ', $labels) . '</div>';
                    ?>
                </td>

                <td style="text-align:right">

                    <button
                        class="btn btn-xs btn-primary alterarSenha"
                        data-email="<?=$email['email']?>"
                        title="Alterar Senha"
                        data-original-html='<i class="fa fa-pencil"></i>'
                    >
                        <i class="fa fa-pencil"></i>
                    </button>

                    <button
                        class="btn btn-xs btn-success btnCopiarIndividual"
                        style="<?= $temSenhaSalva ? '' : 'display:none' ?>"
                        data-email="<?=$email['email']?>"
                        data-senha="<?=htmlspecialchars($senhaExibicao)?>"
                        data-texto="<?=htmlspecialchars($textoWhatsApp)?>"
                        onclick="copiarTextoSimples(this)"
                        title="Enviar Dados Salvos"
                    >
                        <i class="fa fa-whatsapp"></i>
                    </button>

                    <button
                        class="btn btn-xs btn-default btnCopiarSenha"
                        style="<?= $temSenhaSalva ? '' : 'display:none' ?>"
                        data-senha="<?=htmlspecialchars($senhaExibicao)?>"
                        title="Copiar apenas a senha"
                    >
                        <i class="fa fa-key"></i>
                    </button>

                    <button
                        class="btn btn-xs btn-info btnTestarEnvio"
                        style="<?= $temSenhaSalva ? '' : 'display:none' ?>"
                        data-email="<?=$email['email']?>"
                        title="Testar envio"
                    >
                        <i class="fa fa-paper-plane"></i>
                    </button>

                    <?php // if($foiCriadoPeloScript): ?>
                    <button
                        class="btn btn-xs btn-danger excluirEmail"
                        data-email="<?=$email['email']?>"
                        title="Excluir E-mail"
                        data-original-html='<i class="fa fa-trash"></i>'
                    >
                        <i class="fa fa-trash"></i>
                    </button>
                    <?php // endif; ?>

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

                <button class="btn btn-success" id="btnCriarEmails">
                    Criar Contas
                </button>

                <button class="btn btn-default" id="btnCancelarEmails">
                    Cancelar
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
    // Limpa espaços, remove linhas vazias e ignora duplicatas na lista enviada
    $emails = array_unique(array_filter(array_map('trim', explode("\n", $emails))));
    $resultadoWhatsApp = array();

    ob_start();
    foreach($emails as $emailOriginal)
    {
        $emailOriginal = trim($emailOriginal);
        if(!$emailOriginal) continue;

        $email = normalizarEmail($emailOriginal);
        $senha = gerarSenha();
        $resultado = criarEmail($hospedagem, $server, $email, $senha);

        $dados = isset($resultado['retorno']['cpanelresult']['data'][0]) ? $resultado['retorno']['cpanelresult']['data'][0] : array();
        $ok = !empty($dados['result']);
        $erro = !$ok ? (isset($dados['reason']) ? $dados['reason'] : 'Erro desconhecido') : '';

        if($ok) {
            salvarCredencialEmail($hospedagem['id'], $email.'@'.$hospedagem['domain'], $senha, true);
        }

        $resultadoWhatsApp[] = array(
            'email' => $email.'@'.$hospedagem['domain'],
            'senha' => $senha,
            'ok'    => $ok,
            'erro'  => $erro
        );

        echo '<h4>'.$emailOriginal.' → '.$email.'@'.$hospedagem['domain'].'</h4>';
        echo '<pre>'; print_r($resultado); echo '</pre><hr>';
    }

    $debug = ob_get_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'OK',
        'debug' => $debug,
        'html' => gerarMensagemWhatsApp($resultadoWhatsApp, $hospedagem['domain'])
    ]);
    exit;
}

function criarEmail($hospedagem, $server, $conta, $senha)
{
    $params = array(
        'cpanel_jsonapi_user'       => $hospedagem['username'],
        'cpanel_jsonapi_apiversion' => 2,
        'cpanel_jsonapi_module'     => 'Email',
        'cpanel_jsonapi_func'       => 'addpop',
        'email'                     => $conta,
        'domain'                    => $hospedagem['domain'],
        'password'                  => $senha,
        'quota'                     => 0
    );
    return executarRequisicaoCpanel($server, $params);
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
        '<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert">&times;</button>'.$mensagem.'</div>'
    );
}



function formatarMB($mb, $isLimit = false)
{
    if ($isLimit && ($mb <= 0 || $mb >= 999999)) return "Ilimitado";
    if ($mb < 1024) return $mb . " MB";

    $gb = $mb / 1024;
    if ($gb < 1024) return number_format($gb, 2, ',', '.') . " GB";

    return number_format($gb / 1024, 2, ',', '.') . " TB";
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



function getEmails($hospedagem, $server)
{
    $resultado = getEmailsCpanel($hospedagem, $server);

    // --- NOVO DEBUG: Mostra o RAW da API para getEmails se DEBUG_EMAILS for true ---
    if (DEBUG_EMAILS) {
        echo '<script>$("#resultadoEmails").show();</script>';
        echo '<h4>Raw API Response for getEmails:</h4><button type="button" class="close" onclick="$(\'#resultadoEmails\').hide()">&times;</button>';
        echo '<pre>'.htmlspecialchars($resultado['raw']).'</pre>';
        echo '<hr>';
    }
    // --- FIM NOVO DEBUG ---

    if(isset($resultado['error_msg'])) {
        echo '<script>$("#resultadoEmails").show();</script>';
        echo '<h4>Erro Crítico na Chamada:</h4><div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert">&times;</button>'.$resultado['error_msg'].'</div>';
        echo '<b>Dados brutos:</b><pre>'.htmlspecialchars($resultado['raw']).'</pre>';
        return array();
    }

    $retorno = $resultado['retorno'];

    if(
        empty($retorno)
        || !isset($retorno['result']['data'])
        || !empty($retorno['result']['errors'])
    )
    {
        echo '<script>$("#resultadoEmails").show();</script>';
        $msgErro = isset($retorno['result']['errors'])
            ? $retorno['result']['errors']
            : 'Erro desconhecido ao listar contas.';

        echo '<h4>Resposta da API cPanel:</h4>';
        echo '<div class="alert alert-warning alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert">&times;</button>'.$msgErro.'</div>';
        echo '<b>Raw JSON:</b><pre>'.htmlspecialchars($resultado['raw']).'</pre>';

        return array();
    }

    return $retorno['result']['data'];
}

function gerarSenha()
{
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    // Inicia com um número de 1 a 9
    $senha = (string)random_int(1, 9);

    // Adiciona mais 8 caracteres (letras minúsculas e números)
    for ($i = 0; $i < 12; $i++) {
        $senha .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $senha;
}

?>

<script> 


$(document).off('click', '.btnTestarEnvio').on('click', '.btnTestarEnvio', function(){

    var email = $(this).data('email');

    var modal = `
        <div class="modal fade" id="modalTestarEnvio">
            <div class="modal-dialog">
                <div class="modal-content">

                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">
                            Testar envio
                        </h4>
                    </div>

                    <div class="modal-body">

                        <p>
                            <b>Remetente:</b><br>
                            ${email}
                        </p>

                        <label>Destinatários do teste</label>

                        <textarea
                            id="destinatariosTeste"
                            class="form-control"
                            rows="7"
                            placeholder="um e-mail por linha"
                        ></textarea>

                        <div id="resultadoTesteEnvio" style="margin-top:15px"></div>

                    </div>

                    <div class="modal-footer">

                    <button
    type="button"
    class="btn btn-default"
    onclick="window.open('https://www.mail-tester.com/', '_blank')"
>
    <i class="fa fa-external-link"></i> Abrir Mail Tester
</button>

                        <button
                            type="button"
                            class="btn btn-default"
                            data-dismiss="modal"
                        >
                            Cancelar
                        </button>

                        <button
                            type="button"
                            class="btn btn-primary"
                            id="btnExecutarTesteEnvio"
                        >
                            <i class="fa fa-paper-plane"></i>
                            Enviar Teste
                        </button>

                    </div>

                </div>
            </div>
        </div>
    `;

    $('#modalTestarEnvio').remove();
    $('body').append(modal);

    $('#modalTestarEnvio').data('email', email).modal('show');

});


$(document).off('click', '#btnExecutarTesteEnvio').on('click', '#btnExecutarTesteEnvio', function(){

    var btn = $(this);
    var email = $('#modalTestarEnvio').data('email');

    var destinatarios = $('#destinatariosTeste').val()
        .split(/\r?\n/)
        .map(function(email){
            return email.trim();
        })
        .filter(function(email){
            return email !== '';
        });

   

    btn.prop('disabled', true).html(
        '<i class="fa fa-spinner fa-spin"></i> Enviando...'
    );

    $('#resultadoTesteEnvio').html(
        '<div class="alert alert-info">Enviando teste, aguarde...</div>'
    );

    $.post(
        'automacao-emails-core.php?id=<?=$idServico?>',
        {
            acao: 'testarEnvio',
            email: email,
            destinatarios: destinatarios
        },
        function(retorno){

            if(retorno.status === 'OK')
            {
                $('#resultadoTesteEnvio').html(
                    '<div class="alert alert-success">' +
                        '<b>✓ Teste enviado com sucesso.</b><br><br>' +

                        '<b>Remetente:</b> ' +
                        $('<div>').text(retorno.remetente).html() +
                        '<br>' +

                        '<b>Destinatários:</b> ' +
                        retorno.destinatarios.join(', ') +
                        '<br>' +

                        '<b>ID Exim:</b> ' +
                        $('<div>').text(retorno.idExim || 'Não identificado').html() +
                        '<br><br>' +

                        '<b>Comando SSH:</b>' +
                        '<pre id="comandoSSH" style="margin-top:5px;cursor:pointer">' +
                        $('<div>').text(retorno.comando).html() +
                        '</pre>' +

                        '<small>Clique no comando para copiar.</small>' +

                    '</div>'
                );
            }
            else
            {
                $('#resultadoTesteEnvio').html(
                    '<div class="alert alert-danger">' +
                        '<b>Falha no envio.</b><br><br>' +
                        $('<div>').text(retorno.mensagem || 'Erro desconhecido.').html() +

                        (retorno.resposta
                            ? '<br><br><b>Resposta SMTP:</b><pre>' +
                              $('<div>').text(retorno.resposta).html() +
                              '</pre>'
                            : ''
                        ) +

                        (retorno.comando
                            ? '<br><b>Comando SSH:</b><pre>' +
                              $('<div>').text(retorno.comando).html() +
                              '</pre>'
                            : ''
                        ) +

                    '</div>'
                );
            }

        },
        'json'
    )
    .fail(function(xhr){

        $('#resultadoTesteEnvio').html(
            '<div class="alert alert-danger">' +
                '<b>Erro de comunicação.</b><br>' +
                $('<div>').text(xhr.responseText).html() +
            '</div>'
        );

    })
    .always(function(){

        btn.prop('disabled', false).html(
            '<i class="fa fa-paper-plane"></i> Enviar Teste'
        );

    });

});


$(document).off('click', '#comandoSSH').on('click', '#comandoSSH', function(){

    var comando = $(this).text().trim();
    var elemento = $(this);

    navigator.clipboard.writeText(comando).then(function(){

        var original = elemento.html();

        elemento.html(
            '<i class="fa fa-check"></i> Comando copiado!'
        ).addClass('text-success');

        setTimeout(function(){
            elemento.html(original).removeClass('text-success');
        }, 1500);

    }).catch(function(){

        alert('Não foi possível copiar o comando.');

    });

});

$(document).off('click', '#btnBloquearConta').on('click', '#btnBloquearConta', function(){

    var btn = $(this);
    var status = btn.data('status') || 'bloquear';

    if(!btn.hasClass('btn-warning')){

        var textoConfirmacao = (status == 'bloquear')
            ? 'Confirmar Bloqueio da Conta?'
            : 'Confirmar Desbloqueio da Conta?';

        btn.addClass('btn-warning').text(textoConfirmacao);

        setTimeout(function(){

            btn.removeClass('btn-warning');

            if(status == 'bloquear')
            {
                btn
                    .removeClass('btn-warning btn-danger')
                    .addClass('btn-success')
                    .html('<i class="fa fa-unlock"></i> Desbloquear Envio da Conta')
                    .data('status', 'desbloquear');
            }
            else
            {
                btn
                    .removeClass('btn-warning btn-success')
                    .addClass('btn-danger')
                    .html('<i class="fa fa-ban"></i> Bloquear Envio da Conta')
                    .data('status', 'bloquear');
            }

        }, 5000);

        return;
    }

    var mensagem = (status == 'bloquear')
        ? 'Bloqueando envio da conta, aguarde...'
        : 'Desbloqueando envio da conta, aguarde...';

    $('#resultadoEmails')
        .show()
        .html(mensagem);

    btn.prop('disabled', true);

    $.post(
        'automacao-emails-core.php?id=<?=$idServico?>',
        {
            acao: 'alterarStatusEnvioConta',
            status: status
        },
        function(retorno){

            $('#resultadoEmails')
                .show()
                .html(retorno.debug || 'Sem retorno de debug.');

            if(retorno.sucesso)
            {
                if(status == 'bloquear')
                {
                    btn.removeClass('btn-warning btn-danger')
                       .addClass('btn-success')
                       .html('<i class="fa fa-check"></i> Envio da Conta Bloqueado');

                    btn.data('status', 'desbloquear');
                }
                else
                {
                    btn.removeClass('btn-warning btn-success')
                       .addClass('btn-danger')
                       .html('<i class="fa fa-unlock"></i> Desbloquear Envio da Conta');

                    btn.data('status', 'bloquear');
                }
            }
            else
            {
                if(status == 'bloquear'){
                    btn.removeClass('btn-warning')
                       .html('<i class="fa fa-ban"></i> Bloquear Envio da Conta');
                }
                else{
                    btn.removeClass('btn-warning')
                       .html('<i class="fa fa-unlock"></i> Desbloquear Envio da Conta');
                }
            }

        },
        'json'
    ).fail(function(xhr){

        $('#resultadoEmails')
            .show()
            .html(
                '<h4>Falha na comunicação</h4>' +
                '<pre>' +
                $('<div>').text(xhr.responseText).html() +
                '</pre>'
            );

        if(status == 'bloquear'){
            btn.removeClass('btn-warning')
               .html('<i class="fa fa-ban"></i> Bloquear Envio da Conta');
        }
        else{
            btn.removeClass('btn-warning')
               .html('<i class="fa fa-unlock"></i> Desbloquear Envio da Conta');
        }

    }).always(function(){

        btn.prop('disabled', false);

    });

});


$(document).off('click', '.btnCopiarSenha').on('click', '.btnCopiarSenha', function(){
    var btn = $(this);
    var senha = btn.data('senha');
    var originalHtml = btn.html();

    navigator.clipboard.writeText(senha).then(function(){
        btn.html('<i class="fa fa-key"></i> Copiado!')
           .removeClass('btn-default')
           .addClass('btn-info');

        setTimeout(function(){
            btn.html(originalHtml)
               .removeClass('btn-info')
               .addClass('btn-default');
        }, 2000);
    });
});


$(document).off('click', '.btnStatusEnvio').on('click', '.btnStatusEnvio', function(){

    var btn = $(this);
    var email = btn.data('email');
    var status = btn.data('status');

    if(!btn.hasClass('btn-warning')){

        var textoConfirmacao = (status == 'bloquear')
            ? 'Confirmar Bloqueio?'
            : 'Confirmar Desbloqueio?';

        btn.addClass('btn-warning').html(textoConfirmacao);

        setTimeout(function(){

            btn.removeClass('btn-warning');

            if(status == 'bloquear'){
                btn.html('<i class="fa fa-ban"></i> Bloquear Envio');
            }
            else{
                btn.html('<i class="fa fa-unlock"></i> Desbloquear Envio');
            }

        }, 5000);

        return;
    }

    $('#resultadoEmails')
        .show()
        .html(
            (status == 'bloquear')
                ? 'Bloqueando envio de ' + email + ', aguarde...'
                : 'Desbloqueando envio de ' + email + ', aguarde...'
        );

    btn.prop('disabled', true);

    $.post(
        'automacao-emails-core.php?id=<?=$idServico?>',
        {
            acao: 'alterarStatusEnvio',
            email: email,
            status: status
        },
        function(retorno){

            $('#resultadoEmails')
                .show()
                .html(retorno.debug || retorno.mensagem || 'Sem retorno.');

            if(retorno.sucesso)
            {
                if(status == 'bloquear')
                {
                    btn.removeClass('btn-warning btn-success')
                       .addClass('btn-danger')
                       .html('<i class="fa fa-unlock"></i> Desbloquear Envio')
                       .attr('title', 'Envio bloqueado — clique para desbloquear')
                       .data('status', 'desbloquear');
                }
                else
                {
                    btn.removeClass('btn-warning btn-danger')
                       .addClass('btn-success')
                       .html('<i class="fa fa-ban"></i> Bloquear Envio')
                       .attr('title', 'Envio ativo — clique para bloquear')
                       .data('status', 'bloquear');
                }
            }
            else
            {
                btn.removeClass('btn-warning');
            }

        },
        'json'
    ).fail(function(xhr){

        $('#resultadoEmails')
            .show()
            .html(
                '<h4>Falha na comunicação</h4>' +
                '<pre>' +
                $('<div>').text(xhr.responseText).html() +
                '</pre>'
            );

        btn.removeClass('btn-warning');

    }).always(function(){

        btn.prop('disabled', false);

    });

});


$(document).on('keyup', '#filtroEmail', function() {

    var valor = $(this).val().toLowerCase();

    $('#tabelaEmails tbody tr').each(function() {

        $(this).toggle(
            $(this).text().toLowerCase().indexOf(valor) > -1
        );

    });

});

$(document).on('click', '#btnNovoEmail', function(){

    $('#areaListaEmails').hide();
	$('#areaNovaConta').show();

});

$(document).on('click', '#btnCancelarEmails', function(){

    $('#areaNovaConta').hide();
	$('#areaListaEmails').show();

});

$(document).off('click', '#btnCriarEmails').on('click', '#btnCriarEmails', function(){

    var botao = $(this);

    botao.prop('disabled', true).html('Criando...');
    $('#resultadoEmails').show().html('Processando, aguarde...');

    $.post(
        'automacao-emails-core.php?id=<?=$idServico?>',
        {
            acao: 'criarEmails',
            emails: $('#emailsLote').val()
        },
        function(retorno){
            $('#areaWhatsApp').html(retorno.html);
            $('#resultadoEmails').html('<h4>Result</h4><br />'+retorno.debug).addClass('resultadoEmailsAbertoNOT').show();
            $('#emailsLote').val('');

            // Recarrega a listagem para mostrar os novos emails (ou os existentes)
            $.get('automacao-emails-core.php?id=<?=$idServico?>&t=' + new Date().getTime(), function(data){
                // Extrai apenas a parte da lista do HTML retornado
                var novoConteudo = $('<div/>').append(data).find('#areaListaEmails').html();
                $('#areaListaEmails').html(novoConteudo);

                // Volta para a tela da lista automaticamente
                $('#areaNovaConta').hide();
                $('#areaListaEmails').show();

                atualizarContador();
            });
        },
        'json'
    )
    .fail(function(){
        $('#resultadoEmails').html('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert">&times;</button>Erro ao processar.</div>');
    })
    .always(function(){
        botao.prop('disabled', false).html('Criar Contas');
    });
});

$(document).off('click', '#btnVoltarListagem').on('click', '#btnVoltarListagem', function(){
    $('#btnCancelarEmails').trigger('click');
});
$(document).off('click', '.alterarSenha').on('click', '.alterarSenha', function(){

    var btn = $(this);
    var email = btn.data('email');
    var originalHtml = btn.data('original-html');

    if(!btn.hasClass('btn-warning')){
        btn.addClass('btn-warning').html('Confirmar?');
        setTimeout(function(){ btn.removeClass('btn-warning').html(originalHtml); }, 4000);
        return;
    }

    $('#resultadoEmails').show().html('Alterando senha, aguarde...');

    $.post(
        'automacao-emails-core.php?id=<?=$idServico?>',
        {
            acao: 'alterarSenha',
            email: email
        },
        function(retorno){
            btn.removeClass('btn-warning').html(originalHtml);
            if(retorno.status === 'OK') {
                // Encontra o botão de copiar na mesma célula da tabela
                btn.siblings('.btnCopiarIndividual')
                   .attr('data-texto', retorno.texto)
                   .show();
                $('#resultadoEmails').html(retorno.debug);
            } else {
                $('#resultadoEmails').html(retorno.html || retorno);
            }
        },
        'json'
    ).fail(function(err) {
        btn.removeClass('btn-warning').html(originalHtml);
        $('#resultadoEmails').show().html('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert">&times;</button>Erro de comunicação com o servidor.</div>');
    });

});

$(document).off('click', '.excluirEmail').on('click', '.excluirEmail', function(){

    var btn = $(this);
    var email = btn.data('email');
    var originalHtml = btn.data('original-html');

    if(!btn.hasClass('btn-warning')){
        btn.addClass('btn-warning').html('Confirmar?');
        setTimeout(function(){ btn.removeClass('btn-warning').html(originalHtml); }, 4000);
        return;
    }

    $.post(
        'automacao-emails-core.php?id=<?=$idServico?>',
        {
            acao: 'excluirEmail',
            email: email
        },
        function(retorno){
            if(retorno.status === 'OK')
            {
                $('[data-email="' + email + '"]')
                    .closest('tr')
                    .remove();
            } else {
                btn.removeClass('btn-warning').html(originalHtml);
                $('#resultadoEmails').show().html(retorno.html || 'Erro ao excluir');
            }
        },
        'json'
    );
});

// Selecionar Todos
$(document).off('change', '#selectAll').on('change', '#selectAll', function() {
    $('.emailCheckbox:visible').prop('checked', $(this).prop('checked'));
    atualizarContador();
});

// Seleção com Shift + Click e Contador
var lastChecked = null;
$(document).off('click', '.emailCheckbox').on('click', '.emailCheckbox', function(e) {
    var $checkboxes = $('.emailCheckbox:visible');

    if (e.shiftKey && lastChecked) {
        var start = $checkboxes.index(this);
        var end = $checkboxes.index(lastChecked);

        $checkboxes.slice(Math.min(start, end), Math.max(start, end) + 1)
                   .prop('checked', lastChecked.checked);
    }

    lastChecked = this;
    atualizarContador();

    // Desmarcar o "Selecionar Todos" se um for desmarcado manualmente
    if (!$(this).prop('checked')) {
        $('#selectAll').prop('checked', false);
    }
});

// Ação em Massa: Alterar Senha
$(document).off('click', '#btnAlterarSenhaMassa').on('click', '#btnAlterarSenhaMassa', function(){
    var btn = $(this);
    var selecionados = $('.emailCheckbox:checked').map(function(){ return $(this).val(); }).get();

    if(selecionados.length === 0) return;

    if(!btn.hasClass('btn-warning')){
        btn.addClass('btn-warning').text('Confirmar Alterar '+selecionados.length+' senhas?');
        setTimeout(function(){ btn.removeClass('btn-warning').text('Alterar Senhas'); }, 5000);
        return;
    }

    $('#resultadoEmails').show().html('Processando alteração em massa...');

    $.post(
        'automacao-emails-core.php?id=<?=$idServico?>',
        { acao: 'alterarSenhaLote', emails: selecionados },
        function(retorno){
            btn.removeClass('btn-warning').text('Alterar Senhas');
            if(retorno.status === 'OK') {
                $('#areaWhatsApp').html(retorno.html);
                $('#resultadoEmails').html(retorno.debug);
            }
            atualizarContador();
        },
        'json'
    );
});

// Ação em Massa: Excluir
$(document).off('click', '#btnExcluirMassa').on('click', '#btnExcluirMassa', function(){
    var btn = $(this);
    var selecionados = $('.emailCheckbox:checked').map(function(){ return $(this).val(); }).get();

    if(selecionados.length === 0) return;

    if(!btn.hasClass('btn-warning')){
        btn.addClass('btn-warning').text('Confirmar Exclusão de '+selecionados.length+' contas?');
        setTimeout(function(){ btn.removeClass('btn-warning').text('Excluir Selecionados'); }, 5000);
        return;
    }

    $('#resultadoEmails').show().html('Excluindo contas...');

    $.post(
        'automacao-emails-core.php?id=<?=$idServico?>',
        { acao: 'excluirEmailLote', emails: selecionados },
        function(retorno){
            btn.removeClass('btn-warning').text('Excluir Selecionados');
            if(retorno.status === 'OK') {
                $('#resultadoEmails').html(retorno.debug);
                if(retorno.excluidos){
                    retorno.excluidos.forEach(function(email) {
                        $('.emailCheckbox[value="' + email + '"]').closest('tr').fadeOut(function(){ $(this).remove(); });
                    });
                }
                setTimeout(atualizarContador, 600);
            }
        },
        'json'
    );
});

function atualizarContador() {
    var n = $('.emailCheckbox:checked').length;
    $('#countSelecionados').text(n);
    if (n === 0) $('#selectAll').prop('checked', false);
}

$(document).off('click', '#btnCopiarLista').on('click', '#btnCopiarLista', function(){
    var dominio = "<?=$hospedagem['domain']?>";
    var $visiveis = $('#tabelaEmails tbody tr:visible');
    var total = $visiveis.length;
    var texto = "📋 *Lista de E-mails @" + dominio + "*\n📋*" + total + " e-mail" + (total !== 1 ? "s" : "") + "*\n\n";

    $visiveis.each(function(){
        var email = $(this).find('td:eq(1)').text().trim();
        texto += "📧 " + email + "\n";
    });

    texto += "\n\n";
    texto += "🌐 *Para acessar os e-mails @" + dominio + " pelo navegador acesse o o link:* https://webmail." + dominio;

    var btn = $(this);
    var originalHtml = btn.html();

    navigator.clipboard.writeText(texto).then(function() {
        btn.html('<i class="fa fa-list"></i> Copiado!').addClass('btn-info');
        setTimeout(function(){
            btn.html(originalHtml).removeClass('btn-info');
        }, 2000);
    });
});

$(document).off('click', '#btnCopiarListaSenhas').on('click', '#btnCopiarListaSenhas', function(){
    var dominio = "<?=$hospedagem['domain']?>";
    var total = $('#tabelaEmails tbody tr:visible').length;
    var encontrados = 0;
    var texto = "";


    $('#tabelaEmails tbody tr:visible').each(function(){
        var email = $(this).find('td:eq(1)').text().trim();
        var btnWhats = $(this).find('.btnCopiarIndividual');
        var senha = btnWhats.data('senha');

        if(btnWhats.is(':visible') && senha){
            texto += "*Email:* " + email + "\n*Senha:* " + senha + "\n\n";
            encontrados++;
        }
    });

    if(encontrados === 0) {
        alert('Nenhuma senha salva encontrada para os e-mails visíveis.');
        return;
    }

    texto = "📋 *Lista de E-mails @" + dominio + "*\n📋*" + encontrados + " e-mail" + (encontrados !== 1 ? "s" : "") + " com as senhas salvas no sistema*\n\n" + texto

    texto += "-------------\n\n";
    texto += "Para acessar os e-mails @" + dominio + " pelo navegador acesse o o link https://webmail." + dominio + "\n\n";
    texto += "Você pode solicitar a alteração das senhas dos e-mails sempre que desejar.\n\n";
    texto += "Caso precise configurar e-mails @" + dominio + " em gerenciadores de e-mail (*outlook*, *livemail*, *gmail*, *hotmail*, etc) ou em outros dispositivos (*celular*, *ipad*, *iphone*, etc) basta solicitar.\n\n";
    texto += "Sempre que precisar que um e-mail seja criado ou excluído é só falar conosco por qualquer um dos canais de atendimento.";

    var btn = $(this);
    var originalHtml = btn.html();

    navigator.clipboard.writeText(texto).then(function() {
        btn.html('<i class="fa fa-key"></i> Copiado!').addClass('btn-info');
        setTimeout(function(){
            btn.html(originalHtml).removeClass('btn-info');
        }, 2000);
    });
});

$(document).off('click', '#btnCopiarInstrucoes').on('click', '#btnCopiarInstrucoes', function(){
    var dominio = "<?=$hospedagem['domain']?>";
    var texto = "Para configurar os e-mails @" + dominio + " em gerenciadores de e-mail (*outlook*, *livemail*, *gmail*, *hotmail*, etc) ou em outros dispositivos (*celular*, *ipad*, *iphone*, etc)\n\n" +
                "_Com SSL/TLS (Recomendado)_\n" +
                "*Servidor de Entrada:* mail." + dominio + "\n" +
                "*Porta do servidor de Entrada:* 993 para IMAP e 995 para POP3\n" +
                "*Servidor de Saída:* mail." + dominio + "\n" +
                "*Porta do servidor de Saída:* 465\n\n" +
                "_Sem SSL/TLS_\n" +
                "*Servidor de Entrada:* mail." + dominio + "\n" +
                "*Porta do servidor de Entrada:* 143 para IMAP e 110 para POP3\n" +
                "*Servidor de Saída:* mail." + dominio + "\n" +
                "*Porta do servidor de Saída:* 587\n\n" +
                "-------------\n\n" +
                "Para acessar os e-mails @" + dominio + " pelo navegador acesse o o link https://webmail." + dominio + "\n\n" +
                "Você pode alterar as senhas dos e-mails sempre que desejar.\n\n" +
                "Caso precise configurar e-mails @" + dominio + " em gerenciadores de e-mail (*outlook*, *livemail*, *gmail*, *hotmail*, etc) ou em outros dispositivos (*celular*, *ipad*, *iphone*, etc) basta solicitar.\n\n" +
                "Sempre que precisar que um e-mail seja criado ou excluído é só falar conosco por qualquer um dos canais de atendimento.";

    var btn = $(this);
    var originalHtml = btn.html();

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(texto).then(function() {
            btn.html('<i class="fa fa-info-circle"></i> Copiado!').addClass('btn-info');
            setTimeout(function(){
                btn.html(originalHtml).removeClass('btn-info');
            }, 2000);
        });
    } else {
        var $temp = $("<textarea>");
        $("body").append($temp);
        $temp.val(texto).select();
        document.execCommand("copy");
        $temp.remove();
        btn.html('<i class="fa fa-info-circle"></i> Copiado!').addClass('btn-info');
        setTimeout(function(){
            btn.html(originalHtml).removeClass('btn-info');
        }, 2000);
    }
});


function copyThisContents(element) {

    //this.classList.toggle(\'resultadoEmailsAberto\')
    var $element = $(element);
    var texto = $element.text();

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(texto).then(function() {
            //alert('Conteúdo copiado!');
        });
    } else {
        var $temp = $("<textarea>");
        $("body").append($temp);
        $temp.val(texto).select();
        document.execCommand("copy");
        $temp.remove();
        //alert('Conteúdo copiado!');
    }
}

function copiarWhatsApp(btn)
{
    var $btn = $(btn);
    var originalHtml = $btn.html();
    var texto = $('#textoWhatsApp').val();

    navigator.clipboard.writeText(texto).then(function() {
        $btn.html('<i class="fa fa-whatsapp"></i> Copiado!').removeClass('btn-success').addClass('btn-info');
        setTimeout(function(){
            $btn.html(originalHtml).removeClass('btn-info').addClass('btn-success');
        }, 2000);
    }).catch(function() {
        $('#textoWhatsApp').select();
        document.execCommand('copy');
        $btn.html('<i class="fa fa-whatsapp"></i> Copiado!').removeClass('btn-success').addClass('btn-info');
        setTimeout(function(){
            $btn.html(originalHtml).removeClass('btn-info').addClass('btn-success');
        }, 2000);
    });
}

function copiarTextoSimples(btn) {
    var $btn = $(btn);
    var texto = $btn.attr('data-texto');
    if(!texto) return;

    var originalHtml = $btn.html();

    navigator.clipboard.writeText(texto).then(function() {
        $btn.html('<i class="fa fa-whatsapp"></i> Copiado!').removeClass('btn-success').addClass('btn-info');
        setTimeout(function(){
            $btn.html(originalHtml).removeClass('btn-info').addClass('btn-success');
        }, 2000);
    }).catch(function() {
        // Fallback para navegadores antigos
        var $temp = $("<textarea>");
        $("body").append($temp);
        $temp.val(texto).select();
        document.execCommand("copy");
        $temp.remove();
        $btn.html('<i class="fa fa-whatsapp"></i> Copiado!').removeClass('btn-success').addClass('btn-info');
        setTimeout(function(){
            $btn.html(originalHtml).removeClass('btn-info').addClass('btn-success');
        }, 2000);
    });
}

$(document).off('click', '#btnRefreshLista').on('click', '#btnRefreshLista', function(){
    var btn = $(this);
    var originalHtml = btn.html();

    btn.prop('disabled', true).html('<i class="fa fa-refresh fa-spin"></i>');

    $.get('automacao-emails-core.php?id=<?=$idServico?>&t=' + new Date().getTime(), function(data){
        var novoConteudo = $('<div/>').append(data).find('#areaListaEmails').html();
        $('#areaListaEmails').html(novoConteudo);
        atualizarContador();
        btn.prop('disabled', false).html(originalHtml);
    });
});
</script>