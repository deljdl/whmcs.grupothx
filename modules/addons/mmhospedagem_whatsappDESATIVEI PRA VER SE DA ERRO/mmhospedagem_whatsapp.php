<?php

//////////////////////////////////////////////////////////////////////////////////////////
// Modulo desenvolvido pela MMHospedagem /////////////////////////////////////////////////
// A venda e copia deste modulo sem nossa autorização e totalmente proibida! /////////////
// Versão: 1.0.0 BETA ////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////

if (!defined('WHMCS')) {
    exit('Acesso restrito - Power by www.mmhospedagem.com.br');
}

//////////////////////////////////////////////////////////////////////////////////////////
// Gerencia sessoes //////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////

use WHMCS\Session;

//////////////////////////////////////////////////////////////////////////////////////////
// API Carbon ////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////

use Carbon\Carbon;

//////////////////////////////////////////////////////////////////////////////////////////
// API Laravel DataBase //////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////

use WHMCS\Database\Capsule;

global $nome_modulo_buscado;

$nome_modulo = mmhospedagem_whatsapp_config();
$nome_modulo_buscado = $nome_modulo['name'];

function mmhospedagem_whatsapp_config() {

    $configuracaoMMHospedagemArray = array(
        'name' => 'Whatsapp API DEDICADO',
        'description' => 'Modulo de integração com a API do whatsapp desenvolvido pela MMHospedagem',
        'version' => '5.0.0',
        'language' => 'portuguese-br',
        'author' => 'MMHospedagem - DATA CENTER',
        'premium' => true,
    );

    return $configuracaoMMHospedagemArray;

}

function mmhospedagem_whatsapp_activate() {

    global $CONFIG;
    $default_collation = 'utf8_general_ci';
    $default_charset = 'utf8';

    ////////////////////////////////////////////////////////////////////////
    // Cria a Tabela de configuração ///////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    try {
        Capsule::schema()->create(
            'mmhospedagem_whatsapp_configuracao',
            function ($table) {
                /** @var \Illuminate\Database\Schema\Blueprint $table */
                $table->string('MMHospedagem_Nome_Modulo');
                $table->string('mmhospedagem_licenca');
                $table->string('mmhospedagem_email');
                $table->string('mmhospedagem_origem_numero_whatsap');
                $table->string('mmhospedagem_origem_nascimento');
                $table->string('mmhospedagem_delay_enviar_msg');
                $table->string('mmhospedagem_metododeenvio');
                $table->string('mmhospedagem_assinatura', (int)3000);
            }
        );
    } catch (\Exception $e) {
        echo "Unable to create my_table: {$e->getMessage()}";
    }

    ////////////////////////////////////////////////////////////////////////
    // Cria a Tabela do sistema - contato //////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    try {
        Capsule::schema()->create(
            'mmhospedagem_whatsapp_contato',
            function ($table) {
                /** @var \Illuminate\Database\Schema\Blueprint $table */
                $table->increments('mmhospedagem_contato_id');
                $table->string('mmhospedagem_contato_numero');
                $table->integer('mmhospedagem_grupo_id');
                $table->datetime('mmhospedagem_data');
            }
        );
    } catch (\Exception $e) {
        echo "Unable to create my_table: {$e->getMessage()}";
    }

     ////////////////////////////////////////////////////////////////////////
    // Cria a Tabela do sistema - contato //////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    try {
        Capsule::schema()->create(
            'mmhospedagem_whatsapp_sessoes',
            function ($table) {
                /** @var \Illuminate\Database\Schema\Blueprint $table */
                $table->char('id', 32);
                $table->primary('id');
                $table->string('phone_number');
                $table->enum('status',['active', 'inactive', 'expired']);
                $table->dateTime('last_activity');
            }
        );
    } catch (\Exception $e) {
        echo "Unable to create my_table: {$e->getMessage()}";
    }

    ////////////////////////////////////////////////////////////////////////
    // Cria a Tabela do sistema - grupos ///////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    try {
        Capsule::schema()->create(
            'mmhospedagem_whatsapp_grupos',
            function ($table) {
                /** @var \Illuminate\Database\Schema\Blueprint $table */
                $table->increments('mmhospedagem_grupo_id');
                $table->string('mmhospedagem_nome_grupo');
                $table->datetime('mmhospedagem_data');
                $table->string('mmhospedagem_flag');
            }
        );
    } catch (\Exception $e) {
        echo "Unable to create my_table: {$e->getMessage()}";
    }

    ////////////////////////////////////////////////////////////////////////
    // Cria a Tabela do sistema - entrada //////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    try {
        Capsule::schema()->create(
            'mmhospedagem_whatsapp_entrada',
            function ($table) {
                /** @var \Illuminate\Database\Schema\Blueprint $table */
                $table->increments('mmhospedagem_entrada_id');
                $table->datetime('mmhospedagem_data');
                $table->string('mmhospedagem_remetente');
                $table->string('mmhospedagem_msg', (int)3000);
                $table->integer('mmhospedagem_status');
            }
        );
    } catch (\Exception $e) {
        echo "Unable to create my_table: {$e->getMessage()}";
    }

    ////////////////////////////////////////////////////////////////////////
    // Cria a Tabela do sistema - enviar ///////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    try {
        Capsule::schema()->create(
            'mmhospedagem_whatsapp_enviadas',
            function ($table) {
                /** @var \Illuminate\Database\Schema\Blueprint $table */
                $table->increments('mmhospedagem_enviadas_id');
                $table->datetime('mmhospedagem_data');
                $table->string('mmhospedagem_destino');
                $table->integer('mmhospedagem_grupo_id');
                $table->integer('mmhospedagem_intervalo');
                $table->string('mmhospedagem_msg', (int)3000);
                $table->integer('mmhospedagem_tipo_msg');
                $table->string('mmhospedagem_url_arquivo', (int)3000);
                $table->string('mmhospedagem_arquivo_local', (int)3000);
                $table->string('mmhospedagem_tipo_arquivo', (int)255);
                $table->string('mmhospedagem_erro', (int)3000);
                $table->integer('mmhospedagem_status');
            }
        );
    } catch (\Exception $e) {
        echo "Unable to create my_table: {$e->getMessage()}";
    }

    ////////////////////////////////////////////////////////////////////////
    // Cria a Tabela do sistema - validação ////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    try {
        Capsule::schema()->create(
            'mmhospedagem_whatsapp_validacao',
            function ($table) {
                /** @var \Illuminate\Database\Schema\Blueprint $table */
                $table->increments('MMHospedagem_ID');
                $table->integer('MMHospedagem_ID_WHMCS_CLIENTE');
                $table->string('MMHospedagem_EMAIL_WHMCS_CLIENTE');
                $table->string('MMHospedagem_NUMERO_WHATSAPP', (int)255);
                $table->string('MMHospedagem_NOME_WHMCS_CLIENTE');
                $table->string('MMHospedagem_COD_VALIDACAO');
                $table->integer('MMHospedagem_STATUS_VALIDACAO');
            }
        );
    } catch (\Exception $e) {
        echo "Unable to create my_table: {$e->getMessage()}";
    }

    ////////////////////////////////////////////////////////////////////////
    // Cria a Tabela do sistema - robos ////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    try {
        Capsule::schema()->create(
            'mmhospedagem_whatsapp_robos',
            function ($table) {
                /** @var \Illuminate\Database\Schema\Blueprint $table */
                $table->increments('mmhospedagem_robos_id');
                $table->string('mmhospedagem_robos_titulo', (int)3000);
                $table->string('mmhospedagem_robos_codigo', (int)3000);
                $table->string('mmhospedagem_robos_mensagem', (int)3000);
                $table->integer('mmhospedagem_robos_status');
            }
        );
    } catch (\Exception $e) {
        echo "Unable to create my_table: {$e->getMessage()}";
    }

    ////////////////////////////////////////////////////////////////////////
    // Cria a Tabela do sistema - mensagens ////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    try {
        Capsule::schema()->create(
            'mmhospedagem_whatsapp_mensagens',
            function ($table) {
                /** @var \Illuminate\Database\Schema\Blueprint $table */
                $table->increments('mmhospedagem_msg_id');
                $table->string('mmhospedagem_msg_titulo', (int)8000);
                $table->string('mmhospedagem_msg_corpo', (int)8000);
                $table->integer('mmhospedagem_msg_status');
            }
        );
    } catch (\Exception $e) {
        echo "Unable to create my_table: {$e->getMessage()}";
    }


    $tabelas = array('mmhospedagem_whatsapp_configuracao', 'mmhospedagem_whatsapp_contato', 'mmhospedagem_whatsapp_sessoes', 'mmhospedagem_whatsapp_grupos', 'mmhospedagem_whatsapp_entrada', 'mmhospedagem_whatsapp_enviadas', 'mmhospedagem_whatsapp_validacao', 'mmhospedagem_whatsapp_robos', 'mmhospedagem_whatsapp_mensagens');

    foreach($tabelas as $tabela) {

        $changeCollationQuery = "ALTER TABLE $tabela CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;";

        //echo $changeCollationQuery;
        mysql_query($changeCollationQuery);

    }

    // Adiciona as msgs na tabela mmhospedagem_whatsapp_mensagens
    try {
        Capsule::connection()->transaction(
            function ($connectionManager)
            {

                // Mensagens

                $connectionManager->table('mmhospedagem_whatsapp_mensagens')->insert(
                    [
                        'mmhospedagem_msg_titulo' => 'Alerta Login',
                        'mmhospedagem_msg_corpo' => 'Olá {MMHospedagem_NomeCliente}, tudo bem?

Esse é uma mensagem automática disparada pelo sistema com o exclusivo intuito de informá-lo a respeito de uma autenticação em nosso sistema www.mmhospedagem.com.br/painel/ realizado em *{MMHospedagem_Data}* às *{MMHospedagem_Hora}* através do IP *{MMHospedagem_IP}*, pelo email *{MMHospedagem_EmailCliente}*.

Caso esse acesso não tenha sido realizado por você, entre em contato conosco para reportar o problema!

{MMHospedagem_Assinatura}',
                        'mmhospedagem_msg_status' => 1,
                    ]
                );

                $connectionManager->table('mmhospedagem_whatsapp_mensagens')->insert(
                    [
                        'mmhospedagem_msg_titulo' => 'Alteração de Senha',
                        'mmhospedagem_msg_corpo' => 'Olá {MMHospedagem_NomeCliente}, tudo bem?
Sua senha acabou de ser alterada! Caso não tenha sido você que tenha alterado a senha, pedimos que entre em contato conosco o mais rápido possível!

{MMHospedagem_Assinatura}',
                        'mmhospedagem_msg_status' => 1,
                    ]
                );

                $connectionManager->table('mmhospedagem_whatsapp_mensagens')->insert(
                    [
                        'mmhospedagem_msg_titulo' => 'Fatura Criada',
                        'mmhospedagem_msg_corpo' => 'Olá {MMHospedagem_NomeCliente}, tudo bem?
Estamos passando para te lembrar que a fatura de número *#{MMHospedagem_NumeroFatura}* criada no dia *{MMHospedagem_DataCriacaoFatura}* esta disponível para pagamento!

ID da Fatura: *#{MMHospedagem_NumeroFatura}*
Valor da Fatura: *{MMHospedagem_ValordaFatura}*
Data de Vencimento: *{MMHospedagem_DataVencimentoFatura}*

Para visualisar esta fatura acesse www.mmhospedagem.com.br/painel/viewinvoice.php?id={MMHospedagem_NumeroFatura}
Para emitir a segunda via do boleto acesse www.mmhospedagem.com.br/#!/2via

{MMHospedagem_Assinatura}',
                        'mmhospedagem_msg_status' => 1,
                    ]
                );


                $connectionManager->table('mmhospedagem_whatsapp_mensagens')->insert(
                    [
                        'mmhospedagem_msg_titulo' => 'Lembrete de Vencimento',
                        'mmhospedagem_msg_corpo' => 'Olá {MMHospedagem_NomeCliente}, tudo bem?
Estamos passando para te lembrar que a fatura de número *#{MMHospedagem_NumeroFatura}* criada no dia *{MMHospedagem_DataCriacaoFatura}* esta disponível para pagamento!

ID da Fatura: *#{MMHospedagem_NumeroFatura}*
Valor da Fatura: *{MMHospedagem_ValordaFatura}*
Data de Vencimento: *{MMHospedagem_DataVencimentoFatura}*

Para visualisar esta fatura acesse www.mmhospedagem.com.br/painel/viewinvoice.php?id={MMHospedagem_NumeroFatura}
Para emitir a segunda via do boleto acesse www.mmhospedagem.com.br/#!/2via

{MMHospedagem_Assinatura}',
                        'mmhospedagem_msg_status' => 1,
                    ]
                );

                $connectionManager->table('mmhospedagem_whatsapp_mensagens')->insert(
                    [
                        'mmhospedagem_msg_titulo' => 'Confirmação de Pagamento',
                        'mmhospedagem_msg_corpo' => 'Olá {MMHospedagem_NomeCliente}, tudo bem?
A MMHospedagem vem por meio desta informar que recebemos o pagamento da fatura de nº *#{MMHospedagem_NumeroFatura}* no valor de *{MMHospedagem_ValordaFatura}*.

*ATENÇÃO:* Caso o pagamento tenha sido feito por Cartão de Crédito ou Cartão de Debito constará no extrato do seu cartão como MMHOSPEDAGEM o valor referente ao pagamento da(s) parcela(s) da sua fatura *#{MMHospedagem_NumeroFatura}* na MMHospedagem. Isso ocorre pois a PAYPAL é a responsável pela intermediação do pagamento ou seja será um pagamento válido e autorizado!

{MMHospedagem_Assinatura}',
                        'mmhospedagem_msg_status' => 1,
                    ]
                );

                $connectionManager->table('mmhospedagem_whatsapp_mensagens')->insert(
                    [
                        'mmhospedagem_msg_titulo' => 'Cancelamento de Fatura',
                        'mmhospedagem_msg_corpo' => 'Olá {MMHospedagem_NomeCliente}, tudo bem?
A fatura de número *#{MMHospedagem_NumeroFatura}* no valor de *{MMHospedagem_ValordaFatura}* foi cancelada com sucesso!

{MMHospedagem_Assinatura}',
                        'mmhospedagem_msg_status' => 1,
                    ]
                );

                $connectionManager->table('mmhospedagem_whatsapp_mensagens')->insert(
                    [
                        'mmhospedagem_msg_titulo' => 'Suspensão de Serviço',
                        'mmhospedagem_msg_corpo' => 'Olá {MMHospedagem_NomeCliente} tudo bem?
Infelizmente o serviço abaixo acabou de ser suspenso! Pedimos que entre em contato conosco o mais rápido possível para resolver este problema!

Serviço: *{MMHospedagem_NomeProduto}*
Domínio: *{MMHospedagem_Dominio}*

{MMHospedagem_Assinatura}',
                        'mmhospedagem_msg_status' => 1,
                    ]
                );

                $connectionManager->table('mmhospedagem_whatsapp_mensagens')->insert(
                    [
                        'mmhospedagem_msg_titulo' => 'Reativação do Serviço',
                        'mmhospedagem_msg_corpo' => 'Olá {MMHospedagem_NomeCliente} tudo bem?
Que legal o serviço abaixo acabou de ser reativado!

Serviço: *{MMHospedagem_NomeProduto}*
Domínio: *{MMHospedagem_Dominio}*

{MMHospedagem_Assinatura}',
                        'mmhospedagem_msg_status' => 1,
                    ]
                );

                $connectionManager->table('mmhospedagem_whatsapp_mensagens')->insert(
                    [
                        'mmhospedagem_msg_titulo' => 'Abertura de Ticket',
                        'mmhospedagem_msg_corpo' => 'Olá {MMHospedagem_NomeCliente}, tudo bem?
Você acabou de abrir um chamado em nosso site! Estou passando pra te avisar que normalmente os chamados estão sendo respondidos em {MMHospedagem_TempoResposta}, cálculo obtido entre a média do tempo de reposta entre os chamados abertos/respondidos.

Titulo do Chamado: *{MMHospedagem_TituloChamado}*
ID do Chamado: *#{MMHospedagem_IdChamado}*
Departamento: *{MMHospedagem_DepartamentoChamado}*
Prioridade: *{MMHospedagem_PrioridadeChamado}*

Para visualizar este chamado acesse www.mmhospedagem.com.br/painel/viewticket.php?tid={MMHospedagem_IdChamado}&c={MMHospedagem_KeyChamado}

{MMHospedagem_Assinatura}',
                        'mmhospedagem_msg_status' => 1,
                    ]
                );

                $connectionManager->table('mmhospedagem_whatsapp_mensagens')->insert(
                    [
                        'mmhospedagem_msg_titulo' => 'Resposta do Ticket',
                        'mmhospedagem_msg_corpo' => 'Olá {MMHospedagem_NomeCliente}, tudo bem?
O chamado abaixo acabou de ser atualizado pela nossa equipe!

ID do Chamado: *#{MMHospedagem_IdChamado}*
Titulo do Chamado: *{MMHospedagem_TituloChamado}*
Funcionário Responsável: *{MMHospedagem_ResponsavelChamado}*

Para visualizar este chamado acesse www.mmhospedagem.com.br/painel/viewticket.php?tid={MMHospedagem_IdChamado}&c={MMHospedagem_KeyChamado}

{MMHospedagem_Assinatura}',
                        'mmhospedagem_msg_status' => 1,
                    ]
                );

                $connectionManager->table('mmhospedagem_whatsapp_mensagens')->insert(
                    [
                        'mmhospedagem_msg_titulo' => 'Fechar Ticket',
                        'mmhospedagem_msg_corpo' => 'Olá {MMHospedagem_NomeCliente}, tudo bem?
O chamado abaixo acabou de ser marcado como resolvido!

ID do Chamado: *#{MMHospedagem_IdChamado}*
Titulo do Chamado: *{MMHospedagem_TituloChamado}*

Para visualizar este chamado acesse www.mmhospedagem.com.br/painel/viewticket.php?tid={MMHospedagem_IdChamado}&c={MMHospedagem_KeyChamado}

{MMHospedagem_Assinatura}',
                        'mmhospedagem_msg_status' => 1,
                    ]
                );


                $connectionManager->table('mmhospedagem_whatsapp_mensagens')->insert(
                    [
                        'mmhospedagem_msg_titulo' => 'Cadastro Novo Cliente',
                        'mmhospedagem_msg_corpo' => 'Olá {MMHospedagem_NomeCliente}, tudo bem?

Seja bem vindo à *MMHospedagem*!

Primeiramente gostaríamos de agradecer a preferência por nossa empresa. Muito obrigado! Faremos o possível para lhe oferecer um excelente atendimento.

_Acesso a Central do Cliente_

Você pode acessar nossa central do cliente: www.mmhospedagem.com.br/painel/

Basta entrar com seu e-mail e senha escolhido na contratação, caso tenha esquecido a senha por favor clique em *esqueci minha senha*

Se você não criou a conta, provavelmente um de nossos atendentes deve ter criado pra você por conta de um pedido via WhatsApp ou e-mail, de qualquer forma, esta mensagem apenas confirma que você está cadastrado e pode acessar nossa central quando precisar.

{MMHospedagem_Assinatura}',
                        'mmhospedagem_msg_status' => 1,
                    ]
                );

                $connectionManager->table('mmhospedagem_whatsapp_mensagens')->insert(
                    [
                        'mmhospedagem_msg_titulo' => 'Feliz Aniversario',
                        'mmhospedagem_msg_corpo' => 'Olá {MMHospedagem_NomeCliente}, tudo bem?

Sempre nos sentimos muito felizes por ter pessoas tão distintas e especiais conosco. Mas neste mês a equipe da MMHospedagem está especialmente feliz, porque este é o mês do seu aniversário.

Esperamos que este ano, que começa agora, seja ainda melhor do que o que está a terminar, e que todas as suas iniciativas prosperem e se realizem da melhor maneira.

Receba os nossos mais sinceros votos de parabéns!

{MMHospedagem_Assinatura}',
                        'mmhospedagem_msg_status' => 1,
                    ]
                );

                $connectionManager->table('mmhospedagem_whatsapp_mensagens')->insert(
                    [
                        'mmhospedagem_msg_titulo' => 'Boleto Paghiper',
                        'mmhospedagem_msg_corpo' => 'Olá {MMHospedagem_NomeCliente}, tudo bem?
Estou te enviado o boleto para pagamento referente a fatura de número *#{MMHospedagem_NumeroFatura}* no valor de *{MMHospedagem_ValordaFatura}*

Código de barra:
*{MMHospedagem_CodBarra}*

{MMHospedagem_Assinatura}',
                        'mmhospedagem_msg_status' => 0,
                    ]
                );


            }
        );
    } catch (\Exception $e) {
        echo "Uh oh! Inserting didn't work, but I was able to rollback. {$e->getMessage()}";
    }

    return array('status'=>'success','description'=>'Módulo API Whatsapp ativado com sucesso!');
    return array('status'=>'error','description'=>'Não foi possível ativar o módulo API Whatsapp por causa de um erro desconhecido');

}

function mmhospedagem_whatsapp_deactivate() {

    Capsule::schema()->dropIfExists('mmhospedagem_whatsapp_contato');
    Capsule::schema()->dropIfExists('mmhospedagem_whatsapp_grupos');
    Capsule::schema()->dropIfExists('mmhospedagem_whatsapp_entrada');
    Capsule::schema()->dropIfExists('mmhospedagem_whatsapp_enviadas');
    Capsule::schema()->dropIfExists('mmhospedagem_whatsapp_validacao');
    Capsule::schema()->dropIfExists('mmhospedagem_whatsapp_configuracao');
    Capsule::schema()->dropIfExists('mmhospedagem_whatsapp_robos');
    Capsule::schema()->dropIfExists('mmhospedagem_whatsapp_mensagens');
    Capsule::schema()->dropIfExists('mmhospedagem_whatsapp_sessoes');

    //Retorno
    return array('status'=>'success','description'=>'Módulo API Whatsapp foi desativado com sucesso!');
    return array('status'=>'error','description'=>'Não foi possível desativar o módulo API Whatsapp por causa de um erro desconhecido');

}

function codigocampospersonalizados() {

    global $nome_modulo_buscado;

    $config = array();

    // Consulta a tabela de configuração
    foreach (WHMCS\Database\Capsule::table('mmhospedagem_whatsapp_configuracao')->get() as $moduloitaushoplineconfig) {
        $config[$moduloitaushoplineconfig->option] = $moduloitaushoplineconfig->value;

    }

    foreach (WHMCS\Database\Capsule::table('mmhospedagem_whatsapp_configuracao')->WHERE('MMHospedagem_Nome_Modulo', $nome_modulo_buscado)->get() as $modulowhatsapp_configuracao) {
        $whatsapp_origem = $modulowhatsapp_configuracao->mmhospedagem_origem_numero_whatsap;
    }

    $query = "SELECT * FROM tblcustomfields";
    $result = mysql_query($query);
    $config['linhas'] = "<option value=\"\">Selecione o campo do número whatsapp</option>";

    while ($row = mysql_fetch_array($result)) {
        $selected = ($row["id"] == $whatsapp_origem ? 'selected' : '');
        $config['linhas'] .= "<option value=\"".$row["id"] ."\" ".$selected."> ID : [ " . $row["id"] . " ] - Nome : " . $row["fieldname"] . "</option>";
    }

    return $config;
}



function codigocampospersonalizados_nascimento() {

    global $nome_modulo_buscado;

    $nascimento = array();

    // Consulta a tabela de configuração
    foreach (WHMCS\Database\Capsule::table('mmhospedagem_whatsapp_configuracao')->get() as $moduloitaushoplineconfig) {
        $nascimento[$moduloitaushoplineconfig->option] = $moduloitaushoplineconfig->value;

    }

    foreach (WHMCS\Database\Capsule::table('mmhospedagem_whatsapp_configuracao')->WHERE('MMHospedagem_Nome_Modulo', $nome_modulo_buscado)->get() as $modulowhatsapp_configuracao) {
        $whatsapp_nascimento = $modulowhatsapp_configuracao->mmhospedagem_origem_nascimento;
    }

    $query = "SELECT * FROM tblcustomfields";
    $result = mysql_query($query);
    $nascimento['linhas_nascimento'] = "<option value=\"\">Selecione o campo da data de nascimento</option>";

    while ($row = mysql_fetch_array($result)) {
        $selected = ($row["id"] == $whatsapp_nascimento ? 'selected' : '');
        $nascimento['linhas_nascimento'] .= "<option value=\"".$row["id"] ."\" ".$selected."> ID : [ " . $row["id"] . " ] - Nome : " . $row["fieldname"] . "</option>";
    }

    return $nascimento;
}




function mmhospedagem_whatsapp_output($vars) {

    global $nome_modulo_buscado;

    $config = codigocampospersonalizados();
    $nascimento = codigocampospersonalizados_nascimento();

    ////////////////////////////////////////////////////////////////////////
    // Consulta a tabela de validação //////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    foreach (WHMCS\Database\Capsule::table('mmhospedagem_whatsapp_validacao')->get() as $tabelavalidacaowhatsapp) {
        $totaldecontascadastradas = $tabelavalidacaowhatsapp->MMHospedagem_ID;
        $totaldecontascadastradascontagem = count($totaldecontascadastradas);
        $statusdaconta_whatsapp = $tabelavalidacaowhatsapp->MMHospedagem_STATUS_VALIDACAO;
    }

    ////////////////////////////////////////////////////////////////////////
    // Consulta a tabela de configuração ///////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    foreach (WHMCS\Database\Capsule::table('mmhospedagem_whatsapp_configuracao')->WHERE('MMHospedagem_Nome_Modulo', $nome_modulo_buscado)->get() as $modulowhatsapp_configuracao) {
        $whatsapp_licenca = $modulowhatsapp_configuracao->mmhospedagem_licenca;
        $whatsapp_email_licenca = $modulowhatsapp_configuracao->mmhospedagem_email;
        $whatsapp_metodo_deEnvio = $modulowhatsapp_configuracao->mmhospedagem_metododeenvio;
        $whatsapp_Assinatura_mensagem = $modulowhatsapp_configuracao->mmhospedagem_assinatura;
    }

    ////////////////////////////////////////////////////////////////////////
    // Cria o sistema de paginação /////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    if (!isset($_GET['startrow']) or !is_numeric($_GET['startrow'])) {
      $startrow = 0;
    } else {
      $startrow = (int)$_GET['startrow'];
    }


    if($_POST['whatsapp_action'] == 'whatsapp_configuracoes'){

        try {
            Capsule::connection()->transaction(
                function ($connectionManager)
                {


                    global $configuracao;
                    global $nome_modulo_buscado;


                    $contain = WHMCS\Database\Capsule::table('mmhospedagem_whatsapp_configuracao')->WHERE('MMHospedagem_Nome_Modulo', $nome_modulo_buscado)->get();
                    //print_r($contain);

                    if(count($contain) == 0){
                        $connectionManager->table('mmhospedagem_whatsapp_configuracao')->insert(
                            [
                                'MMHospedagem_Nome_Modulo' => $nome_modulo_buscado,
                                'mmhospedagem_licenca' => $_POST['whatsapp_numero_licenca'],
                                'mmhospedagem_email' => $_POST['whatsapp_email_licenca'],
                                'mmhospedagem_origem_numero_whatsap' => $_POST['whatsapp_origemnumwhatsapp'],
                                'mmhospedagem_origem_nascimento' => $_POST['whatsapp_origemnascimento'],
                                'mmhospedagem_delay_enviar_msg' => $_POST['whatsapp_delay'],
                                'mmhospedagem_metododeenvio' => $_POST['metododeenvio_campotelefone'],
                                'mmhospedagem_assinatura' => $_POST['assinatura_mensagem']

                            ]
                        );
                    }else{
                        $connectionManager->table('mmhospedagem_whatsapp_configuracao')->WHERE('MMHospedagem_Nome_Modulo', $nome_modulo_buscado)->update(
                            [
                                'mmhospedagem_licenca' => $_POST['whatsapp_numero_licenca'],
                                'mmhospedagem_email' => $_POST['whatsapp_email_licenca'],
                                'mmhospedagem_origem_numero_whatsap' => $_POST['whatsapp_origemnumwhatsapp'],
                                'mmhospedagem_origem_nascimento' => $_POST['whatsapp_origemnascimento'],
                                'mmhospedagem_delay_enviar_msg' => $_POST['whatsapp_delay'],
                                'mmhospedagem_metododeenvio' => $_POST['metododeenvio_campotelefone'],
                                'mmhospedagem_assinatura' => $_POST['assinatura_mensagem']
                            ]
                        );
                    }

                }
            );

            $mmalertaitaushopline = header('Location: ./addonmodules.php?module=mmhospedagem_whatsapp&alerta=sucesso');
            return $mmalertaitaushopline;

        } catch (\Exception $e) {
            echo $e->getMessage();
            exit();
            $mmalertaitaushopline = header('Location: ./addonmodules.php?module=mmhospedagem_whatsapp&alerta=erro');
            return $mmalertaitaushopline;
        }


    }

    echo '

    <div class="clear"></div>

    <div id="configuracoes" class="modal fade in" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">×</button>
                    <h4 class="modal-title"><i class="fa fa-cogs" aria-hidden="true"></i> '. $vars['_lang']['mmhospedagem_whatsapp_titulo_configuracoes'] .'</h4>
                </div>

                <form method="POST">

                    <input type="hidden" value="whatsapp_configuracoes" name="whatsapp_action">

                    <div class="modal-body">
                        <div class="input-group" style="width: 100%;">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <i class="fa fa-wrench" aria-hidden="true"></i> '. $vars['_lang']['mmhospedagem_whatsapp_titulo_gerenciar_licenca'] .'
                                </div>

                                <div class="panel-body">
                                    <div class="row" style="width: 100%;">
                                        <div class="col-md-8" style="width: 103%;">

                                            <label>'. $vars['_lang']['mmhospedagem_whatsapp_texto_numero_licenca'] .'</label>
                                            <input class="form-control" type="text" id="whatsapp_numero_licenca" name="whatsapp_numero_licenca" maxlength="100" value="'.$whatsapp_licenca.'">

                                            <div style="height: 10px; width: 100%; clear: both;"></div>

                                            <label>'. $vars['_lang']['mmhospedagem_whatsapp_texto_email_licenca'] .'</label>
                                            <input class="form-control" type="text" id="whatsapp_email_licenca" name="whatsapp_email_licenca" maxlength="100" value="'.$whatsapp_email_licenca.'">

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="modal-body">
                        <div class="input-group" style="width: 100%; margin-top: -30px;">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <i class="fa fa-wrench" aria-hidden="true"></i> '. $vars['_lang']['mmhospedagem_whatsapp_texto_gerenciar_modulo'] .'
                                </div>

                                <div class="panel-body">
                                    <div class="row" style="width: 100%;">
                                        <div class="col-md-8" style="width: 103%;">

                                            <label>Origem Data de Nascimento</label>
                                            <select id="whatsapp_origemnascimento" name="whatsapp_origemnascimento" class="form-control">
                                                '.$nascimento['linhas_nascimento'].'
                                            </select>

                                            <div style="height: 10px; width: 100%; clear: both;"></div>

                                            <label>'. $vars['_lang']['mmhospedagem_whatsapp_texto_metododeenvio'] .'</label>
                                            <select id="metododeenvio_campotelefone" name="metododeenvio_campotelefone" ng-model="metododeenvio_campotelefone" class="form-control">
                                                <option value="">Selecione uma opção</option>
                                                <option value="Padrao" ';

                                                if($whatsapp_metodo_deEnvio == "Padrao") { echo 'selected'; } else { }

                                                echo '>Campo padrão WHMCS [ Telefone ]</option>
                                                <option value="Custom" ';

                                                if($whatsapp_metodo_deEnvio == "Custom") { echo 'selected'; } else { }

                                                echo '>Campo custom WHMCS</option>
                                            </select>


                                            <div style="height: 10px; width: 100%; clear: both;"></div>

                                            <div class="alert alert-warning" role="alert">
                                              Caso tenha selecionado "<strong>Campo custom WHMCS</strong>", voce deve selecionar abaixo a origem do número whatsapp! Sé selecionou "<strong>Campo padrão WHMCS [ Telefone ]</strong>" não selecione nada abaixo!".
                                            </div>


                                            <label>'. $vars['_lang']['mmhospedagem_whatsapp_texto_origem_whatsapp'] .'</label>
                                            <select id="whatsapp_origemnumwhatsapp" name="whatsapp_origemnumwhatsapp" class="form-control">
                                                '.$config['linhas'].'
                                            </select>

                                            <div style="height: 10px; width: 100%; clear: both;"></div>

                                            <label>'. $vars['_lang']['mmhospedagem_whatsapp_texto_delay_send_msg'] .'</label>
                                            <select id="whatsapp_delay" name="whatsapp_delay" class="form-control">
                                                <option value="5">'. $vars['_lang']['mmhospedagem_whatsapp_texto_input_5_segundos'] .'</option>
                                            </select>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-body">
                        <div class="input-group" style="width: 100%; margin-top: -30px;">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <i class="fa fa-wrench" aria-hidden="true"></i> Assinatura das mensagens
                                </div>

                                <div class="panel-body">
                                    <div class="row" style="width: 100%;">
                                        <div class="col-md-8" style="width: 103%;">

                                            <label>Assinatura a ser enviada</label>

                                            <textarea name="assinatura_mensagem" id="assinatura_mensagem" rows="6" class="form-control" tabindex="38">' . $whatsapp_Assinatura_mensagem . '</textarea>

                                            <div style="height: 10px; width: 100%; clear: both;"></div>

                                            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                                                <tbody><tr>
                                                        <td width="60%" class="fieldlabel">Template para envio da assinatura nas mensagens</td>
                                                        <td class="fieldarea">
                                                            <strong>{MMHospedagem_Assinatura}</strong>
                                                        </td>
                                                    </tr></tbody>
                                            </table>


                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="modal-footer" style="margin-top: -13px; z-index: 1000; position: relative;">
                        <button type="submit" class="btn btn-success">'. $vars['_lang']['mmhospedagem_whatsapp_texto_botao_salvar'] .'</button>
                        <button type="button" class="btn btn-danger" data-dismiss="modal">'. $vars['_lang']['mmhospedagem_whatsapp_texto_botao_cancelar'] .'</button>
                    </div>

                </form>

            </div>
        </div>
    </div>



    ';

    if($_GET['acao'] == 'adicionarrobo') {

        echo '


        <div class="tab-pane active" id="tab1">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fab fa-android" aria-hidden="true" style="margin-right: 5px;"></i> '. $vars['_lang']['mmhospedagem_whatsapp_texto_titulo_adicionar_robos_acoes'] .'
                </div>

                <div class="panel-body">
                    <div class="row" style="padding: 0px 10px;">


                    <form method="POST">

                        <input type="hidden" name="adicionar_robo_action" id="adicionar_robo_action" value="adicionar_robo_action">

                        <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                            <tbody>

                                <tr>
                                    <td width="15%" class="fieldlabel">Titulo do Robô </td>
                                    <td class="fieldarea">
                                        <input type="text" name="titulo_robo" id="titulo_robo" class="form-control" value="">
                                    </td>
                                </tr>

                                <tr>
                                    <td width="15%" class="fieldlabel">Gatilho do Robô </td>
                                    <td class="fieldarea">
                                        <input type="text" name="gatilho_robo" id="gatilho_robo" class="form-control" value="">
                                    </td>
                                </tr>

                                <tr>
                                    <td width="15%" class="fieldlabel">Mensagem do Robô </td>
                                    <td class="fieldarea" colspan="3">
                                        <textarea name="mensagem_robo" id="mensagem_robo" rows="4" class="form-control" tabindex="38"></textarea>
                                        <font color="#cccccc"><small>( Mensagem que sera enviada quando o gatilho for usado no whatsapp! )</small></font>
                                    </td>
                                </tr>



                            </tbody>
                        </table>

                        <div class="btn-container">
                            <input type="submit" value="Adicionar novo Robô" class="btn btn-primary" tabindex="39">
                        </div>
                    </form>


                    </div>
                </div>
            </div>
        </div>



        ';

    }


    if($_GET['acao'] == 'editar_robo') {

        ////////////////////////////////////////////////////////////////////////
        // Consulta a tabela de robô pelo id do GET ////////////////////////////
        ////////////////////////////////////////////////////////////////////////

        foreach (WHMCS\Database\Capsule::table('mmhospedagem_whatsapp_robos')->WHERE('mmhospedagem_robos_id', $_GET['id'])->get() as $modulowhatsapp_robo_informacoes) {
            $id_robo = $modulowhatsapp_robo_informacoes->mmhospedagem_robos_id;
            $titulo_robo = $modulowhatsapp_robo_informacoes->mmhospedagem_robos_titulo;
            $gatilho_robo = $modulowhatsapp_robo_informacoes->mmhospedagem_robos_codigo;
            $mensagem_robo = $modulowhatsapp_robo_informacoes->mmhospedagem_robos_mensagem;
        }

        echo '


        <div class="tab-pane active" id="tab1">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="far fa-edit" aria-hidden="true" style="margin-right: 5px;"></i> '. $vars['_lang']['mmhospedagem_whatsapp_texto_titulo_editar_robos_acoes'] . ' [ <strong>' . $_GET['id'] .'</strong> ]
                </div>

                <div class="panel-body">
                    <div class="row" style="padding: 0px 10px;">


                    <form method="POST">

                        <input type="hidden" name="editar_robo_action" id="editar_robo_action" value="editar_robo_action">
                        <input type="hidden" name="editar_robo_id" id="editar_robo_id" value="'.$id_robo.'">

                        <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                            <tbody>

                                <tr>
                                    <td width="15%" class="fieldlabel">Titulo do Robô </td>
                                    <td class="fieldarea">
                                        <input type="text" name="titulo_robo" id="titulo_robo" class="form-control" value="'.$titulo_robo.'">
                                    </td>
                                </tr>

                                <tr>
                                    <td width="15%" class="fieldlabel">Gatilho do Robô </td>
                                    <td class="fieldarea">
                                        <input type="text" name="gatilho_robo" id="gatilho_robo" class="form-control" value="'.$gatilho_robo.'">
                                    </td>
                                </tr>

                                <tr>
                                    <td width="15%" class="fieldlabel">Mensagem do Robô </td>
                                    <td class="fieldarea" colspan="3">
                                        <textarea name="mensagem_robo" id="mensagem_robo" rows="4" class="form-control" tabindex="38">'.$mensagem_robo.'</textarea>
                                        <font color="#cccccc"><small>( Mensagem que sera enviada quando o gatilho for usado no whatsapp! )</small></font>
                                    </td>
                                </tr>



                            </tbody>
                        </table>

                        <div class="btn-container">
                            <input type="submit" value="Salvar alterações" class="btn btn-primary" tabindex="39">
                        </div>
                    </form>


                    </div>
                </div>
            </div>
        </div>

        ';

    }



    if($_GET['acao'] == 'editar_mensagem') {

        ////////////////////////////////////////////////////////////////////////
        // Consulta a tabela de robô pelo id do GET ////////////////////////////
        ////////////////////////////////////////////////////////////////////////

        foreach (WHMCS\Database\Capsule::table('mmhospedagem_whatsapp_mensagens')->WHERE('mmhospedagem_msg_id', $_GET['id'])->get() as $modulowhatsapp_mensagem_informacoes) {
            $id_mensagem = $modulowhatsapp_mensagem_informacoes->mmhospedagem_msg_id;
            $titulo_mensagem = $modulowhatsapp_mensagem_informacoes->mmhospedagem_msg_titulo;
            $corpo_mensagem = $modulowhatsapp_mensagem_informacoes->mmhospedagem_msg_corpo;
            $status_mensagem = $modulowhatsapp_mensagem_informacoes->mmhospedagem_msg_status;
        }

        echo '


        <div class="tab-pane active" id="tab1">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="far fa-edit" aria-hidden="true" style="margin-right: 5px;"></i> Você esta editando a mensagem de titulo [ <strong>' . $titulo_mensagem .'</strong> ]
                </div>

                <div class="panel-body">
                    <div class="row" style="padding: 0px 10px;">

                    <form method="POST">

                        <script type="text/javascript" language="javascript">
                            function addTag_Edicao(tag)
                            {
                            var selection = \'\';
                            var textarea = document.getElementById(\'mensagem_corpo\');

                            // Internet Explorer
                            if (textarea.selectionStart === undefined)
                            {
                                var textrange = document.selection.createRange();
                                var selection = textrange.text;

                                if (selection == \'\')
                                {
                                    alert (\'Sem texto seleccionado.\');
                                }

                                else
                                {
                                    textarea.focus();
                                    textrange .text = tag + selection + tag;
                                }
                            }

                            else
                            {
                                if (textarea.selectionStart != textarea.selectionEnd)
                                {
                                    selection = textarea.value.substring(textarea.selectionStart, textarea.selectionEnd);

                                    var selection_start = textarea.value.substring(0, textarea.selectionStart);
                                    var selection_end = textarea.value.substring(textarea.selectionEnd);

                                }

                                if (selection == \'\')
                                {
                                    alert (\'Sem texto seleccionado.\');
                                }

                                else
                                {
                                    textarea.value = selection_start + tag + selection + tag + selection_end;
                                }
                            }

                            }
                        </script>

                        <input type="hidden" name="editar_mensagem_action" id="editar_mensagem_action" value="editar_mensagem_action">
                        <input type="hidden" name="editar_mensagem_id" id="editar_mensagem_id" value="'.$id_mensagem.'">

                        <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                            <tbody>

                                <tr>
                                    <td width="15%" class="fieldlabel">Corpo da Mensagem</td>
                                    <td class="fieldarea" colspan="3">
                                        <textarea name="mensagem_corpo" id="mensagem_corpo" rows="14" class="form-control" tabindex="38">'.$corpo_mensagem.'</textarea>

                                        <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups" style="position: absolute;     right: 66px;
    margin-top: -297px;">
                                            <div class="btn-group mr-2" role="group" aria-label="First group">
                                                <button style="border-radius: 0px 0px 0px 4px;" type="button" class="btn btn-primary" onclick="addTag_Edicao(\'*\');"><i class="fas fa-bold"></i></button>
                                                <button type="button" class="btn btn-primary" onclick="addTag_Edicao(\'_\');"><i class="fas fa-italic"></i></button>
                                                <button type="button" class="btn btn-primary" onclick="addTag_Edicao(\'~\');"><i class="fas fa-strikethrough"></i></button>
                                                <button style="border-radius: 0px 4px 0px 0px;" type="button" class="btn btn-primary" onclick="addTag_Edicao(\'```\');"><i class="fab fa-autoprefixer"></i></button>
                                            </div>
                                        </div>

                                    </td>
                                </tr>

                            </tbody>
                        </table>



                        <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                            <tbody>';

                            if($titulo_mensagem == 'Alerta Login' || $titulo_mensagem == 'Alteração de Senha' || $titulo_mensagem == 'Fatura Criada' || $titulo_mensagem == 'Lembrete de Vencimento' || $titulo_mensagem == 'Confirmação de Pagamento' || $titulo_mensagem == 'Cancelamento de Fatura' || $titulo_mensagem == 'Suspensão de Serviço' || $titulo_mensagem == 'Reativação do Serviço' || $titulo_mensagem == 'Abertura de Ticket' || $titulo_mensagem == 'Resposta do Ticket' || $titulo_mensagem == 'Resposta do Ticket' || $titulo_mensagem == 'Fechar Ticket' || $titulo_mensagem == 'Cadastro Novo Cliente' || $titulo_mensagem == 'Feliz Aniversario' || $titulo_mensagem == 'Boleto Paghiper') {

                                echo '<tr>
                                    <td width="15%" class="fieldlabel">Nome do Cliente</td>
                                    <td class="fieldarea">
                                        <strong>{MMHospedagem_NomeCliente}</strong>
                                    </td>
                                </tr>

                                <tr>
                                    <td width="15%" class="fieldlabel">Assinatura das mensagens</td>
                                    <td class="fieldarea">
                                        <strong>{MMHospedagem_Assinatura}</strong>
                                    </td>
                                </tr>';

                            }

                            if($titulo_mensagem == 'Alerta Login') {

                                echo '<tr>
                                    <td width="15%" class="fieldlabel">Email do Cliente</td>
                                    <td class="fieldarea">
                                        <strong>{MMHospedagem_EmailCliente}</strong>
                                    </td>
                                </tr>';

                            }

                            if($titulo_mensagem == 'Alerta Login') {

                                echo '<tr>
                                    <td width="15%" class="fieldlabel">Data do Evento</td>
                                    <td class="fieldarea">
                                        <strong>{MMHospedagem_Data}</strong>
                                    </td>
                                </tr>';

                            }

                            if($titulo_mensagem == 'Alerta Login') {

                                echo '<tr>
                                    <td width="15%" class="fieldlabel">Hora do Evento</td>
                                    <td class="fieldarea">
                                        <strong>{MMHospedagem_Hora}</strong>
                                    </td>
                                </tr>';

                            }

                            if($titulo_mensagem == 'Alerta Login') {

                                echo '<tr>
                                    <td width="15%" class="fieldlabel">IP do Evento</td>
                                    <td class="fieldarea">
                                        <strong>{MMHospedagem_IP}</strong>
                                    </td>
                                </tr>';

                            }

                            if($titulo_mensagem == 'Fatura Criada' || $titulo_mensagem == 'Lembrete de Vencimento' || $titulo_mensagem == 'Confirmação de Pagamento' || $titulo_mensagem == 'Cancelamento de Fatura' || $titulo_mensagem == 'Boleto Paghiper') {

                                echo '<tr>
                                    <td width="15%" class="fieldlabel">Número da Fatura</td>
                                    <td class="fieldarea">
                                        <strong>{MMHospedagem_NumeroFatura}</strong>
                                    </td>
                                </tr>';

                            }

                            if($titulo_mensagem == 'Fatura Criada' || $titulo_mensagem == 'Lembrete de Vencimento' || $titulo_mensagem == 'Boleto Paghiper') {

                                echo '<tr>
                                    <td width="15%" class="fieldlabel">Data Criação Fatura</td>
                                    <td class="fieldarea">
                                        <strong>{MMHospedagem_DataCriacaoFatura}</strong>
                                    </td>
                                </tr>';

                            }

                            if($titulo_mensagem == 'Fatura Criada' || $titulo_mensagem == 'Lembrete de Vencimento' || $titulo_mensagem == 'Confirmação de Pagamento' || $titulo_mensagem == 'Cancelamento de Fatura' || $titulo_mensagem == 'Boleto Paghiper') {

                                echo '<tr>
                                    <td width="15%" class="fieldlabel">Valor Fatura</td>
                                    <td class="fieldarea">
                                        <strong>{MMHospedagem_ValordaFatura}</strong>
                                    </td>
                                </tr>';

                            }

                            if($titulo_mensagem == 'Fatura Criada' || $titulo_mensagem == 'Lembrete de Vencimento' || $titulo_mensagem == 'Boleto Paghiper') {

                                echo '<tr>
                                    <td width="15%" class="fieldlabel">Vencimento da Fatura</td>
                                    <td class="fieldarea">
                                        <strong>{MMHospedagem_DataVencimentoFatura}</strong>
                                    </td>
                                </tr>';

                            }

                            if($titulo_mensagem == 'Boleto Paghiper') {

                                echo '<tr>
                                    <td width="15%" class="fieldlabel">Código de Barra</td>
                                    <td class="fieldarea">
                                        <strong>{MMHospedagem_CodBarra}</strong>
                                    </td>
                                </tr>';

                            }


                            if($titulo_mensagem == 'Suspensão de Serviço' || $titulo_mensagem == 'Reativação do Serviço') {

                                echo '<tr>
                                    <td width="15%" class="fieldlabel">Nome do Produto</td>
                                    <td class="fieldarea">
                                        <strong>{MMHospedagem_NomeProduto}</strong>
                                    </td>
                                </tr>';

                            }

                            if($titulo_mensagem == 'Suspensão de Serviço' || $titulo_mensagem == 'Reativação do Serviço') {

                                echo '<tr>
                                    <td width="15%" class="fieldlabel">Domínio do Produto</td>
                                    <td class="fieldarea">
                                        <strong>{MMHospedagem_Dominio}</strong>
                                    </td>
                                </tr>';

                            }

                            if($titulo_mensagem == 'Abertura de Ticket') {

                                echo '<tr>
                                    <td width="15%" class="fieldlabel">Tempo Resposta do Ticket</td>
                                    <td class="fieldarea">
                                        <strong>{MMHospedagem_TempoResposta}</strong>
                                    </td>
                                </tr>';

                            }

                            if($titulo_mensagem == 'Abertura de Ticket' || $titulo_mensagem == 'Resposta do Ticket' || $titulo_mensagem == 'Fechar Ticket' ) {

                                echo '<tr>
                                    <td width="15%" class="fieldlabel">Titulo do Ticket</td>
                                    <td class="fieldarea">
                                        <strong>{MMHospedagem_TituloChamado}</strong>
                                    </td>
                                </tr>';

                            }

                            if($titulo_mensagem == 'Abertura de Ticket' || $titulo_mensagem == 'Resposta do Ticket' || $titulo_mensagem == 'Fechar Ticket') {

                                echo '<tr>
                                    <td width="15%" class="fieldlabel">ID do Ticket</td>
                                    <td class="fieldarea">
                                        <strong>{MMHospedagem_IdChamado}</strong>
                                    </td>
                                </tr>';

                            }

                            if($titulo_mensagem == 'Abertura de Ticket') {

                                echo '<tr>
                                    <td width="15%" class="fieldlabel">Departamento do Ticket</td>
                                    <td class="fieldarea">
                                        <strong>{MMHospedagem_DepartamentoChamado}</strong>
                                    </td>
                                </tr>';

                            }

                            if($titulo_mensagem == 'Abertura de Ticket') {

                                echo '<tr>
                                    <td width="15%" class="fieldlabel">Prioridade do Ticket</td>
                                    <td class="fieldarea">
                                        <strong>{MMHospedagem_PrioridadeChamado}</strong>
                                    </td>
                                </tr>';

                            }

                            if($titulo_mensagem == 'Abertura de Ticket' || $titulo_mensagem == 'Resposta do Ticket' || $titulo_mensagem == 'Fechar Ticket') {

                                echo '<tr>
                                    <td width="15%" class="fieldlabel">Chave KEY do Ticket</td>
                                    <td class="fieldarea">
                                        <strong>{MMHospedagem_KeyChamado}</strong>
                                    </td>
                                </tr>';

                            }

                            if($titulo_mensagem == 'Resposta do Ticket') {

                                echo '<tr>
                                    <td width="15%" class="fieldlabel">Funcionario que Respondeu o Ticket</td>
                                    <td class="fieldarea">
                                        <strong>{MMHospedagem_ResponsavelChamado}</strong>
                                    </td>
                                </tr>';

                            }



                            echo '</tbody>
                        </table>



                        <div class="btn-container">
                            <input type="submit" value="Salvar alterações" class="btn btn-primary" tabindex="39">
                        </div>
                    </form>


                    </div>
                </div>
            </div>
        </div>

        ';

    }


    if($_POST['validacao_manual_action'] == 'validacao_manual_action') {


        try {
            Capsule::connection()->transaction(
                function ($connectionManager)
                {

                   $connectionManager->table('mmhospedagem_whatsapp_validacao')->WHERE('MMHospedagem_ID_WHMCS_CLIENTE', $_POST['id_do_cliente_validacao_manual'])->update(
                        [
                            'MMHospedagem_STATUS_VALIDACAO' => '4'
                        ]
                    );

                }
            );

            $mmalertaitaushopline = header('Location: ./addonmodules.php?module=mmhospedagem_whatsapp&alerta=sucesso');
            return $mmalertaitaushopline;

        } catch (\Exception $e) {
            echo $e->getMessage();
            exit();
            $mmalertaitaushopline = header('Location: ./addonmodules.php?module=mmhospedagem_whatsapp&alerta=erro');
            return $mmalertaitaushopline;
        }

    }


    if($_POST['remover_validacao_manual_action'] == 'remover_validacao_manual_action') {


        try {
            Capsule::connection()->transaction(
                function ($connectionManager)
                {

                   $connectionManager->table('mmhospedagem_whatsapp_validacao')->WHERE('MMHospedagem_ID_WHMCS_CLIENTE', $_POST['remover_id_do_cliente_validacao_manual'])->update(
                        [
                            'MMHospedagem_STATUS_VALIDACAO' => '0'
                        ]
                    );

                }
            );

            $mmalertaitaushopline = header('Location: ./addonmodules.php?module=mmhospedagem_whatsapp&alerta=sucesso');
            return $mmalertaitaushopline;

        } catch (\Exception $e) {
            echo $e->getMessage();
            exit();
            $mmalertaitaushopline = header('Location: ./addonmodules.php?module=mmhospedagem_whatsapp&alerta=erro');
            return $mmalertaitaushopline;
        }

    }




    if($_POST['adicionar_robo_action'] == 'adicionar_robo_action') {


        try {
            Capsule::connection()->transaction(
                function ($connectionManager)
                {

                    $connectionManager->table('mmhospedagem_whatsapp_robos')->insert(
                        [
                            'mmhospedagem_robos_titulo' => $_POST['titulo_robo'],
                            'mmhospedagem_robos_codigo' => $_POST['gatilho_robo'],
                            'mmhospedagem_robos_mensagem' => $_POST['mensagem_robo'],
                            'mmhospedagem_robos_status' => '1'
                        ]
                    );

                }
            );

            $mmalertaitaushopline = header('Location: ./addonmodules.php?module=mmhospedagem_whatsapp&alerta=sucesso');
            return $mmalertaitaushopline;

        } catch (\Exception $e) {
            echo $e->getMessage();
            exit();
            $mmalertaitaushopline = header('Location: ./addonmodules.php?module=mmhospedagem_whatsapp&alerta=erro');
            return $mmalertaitaushopline;
        }


    }


    if($_POST['editar_robo_action'] == 'editar_robo_action') {


        try {
            Capsule::connection()->transaction(
                function ($connectionManager)
                {

                    $connectionManager->table('mmhospedagem_whatsapp_robos')->WHERE('mmhospedagem_robos_id', $_POST['editar_robo_id'])->update(
                        [
                            'mmhospedagem_robos_titulo' => $_POST['titulo_robo'],
                            'mmhospedagem_robos_codigo' => $_POST['gatilho_robo'],
                            'mmhospedagem_robos_mensagem' => $_POST['mensagem_robo'],
                            'mmhospedagem_robos_status' => '1'
                        ]
                    );

                }
            );

            $mmalertaitaushopline = header('Location: ./addonmodules.php?module=mmhospedagem_whatsapp&alerta=sucesso');
            return $mmalertaitaushopline;

        } catch (\Exception $e) {
            echo $e->getMessage();
            exit();
            $mmalertaitaushopline = header('Location: ./addonmodules.php?module=mmhospedagem_whatsapp&alerta=erro');
            return $mmalertaitaushopline;
        }


    }


     if($_GET['acao'] == 'deletar_robo') {

        $query_deletar_robo = 'DELETE FROM mmhospedagem_whatsapp_robos WHERE mmhospedagem_robos_id = '.$_GET['id'].'';
        mysql_query("$query_deletar_robo");

        $mmalertaitaushopline = header('Location: ./addonmodules.php?module=mmhospedagem_whatsapp&alerta=sucesso');
        return $mmalertaitaushopline;

     }



     if($_POST['editar_mensagem_action'] == 'editar_mensagem_action') {


        try {
            Capsule::connection()->transaction(
                function ($connectionManager)
                {

                    $connectionManager->table('mmhospedagem_whatsapp_mensagens')->WHERE('mmhospedagem_msg_id', $_POST['editar_mensagem_id'])->update(
                        [
                            'mmhospedagem_msg_corpo' => $_POST['mensagem_corpo']
                        ]
                    );

                }
            );

            $mmalertaitaushopline = header('Location: ./addonmodules.php?module=mmhospedagem_whatsapp&alerta=sucesso');
            return $mmalertaitaushopline;

        } catch (\Exception $e) {
            echo $e->getMessage();
            exit();
            $mmalertaitaushopline = header('Location: ./addonmodules.php?module=mmhospedagem_whatsapp&alerta=erro');
            return $mmalertaitaushopline;
        }


    }


    if($_GET['acao'] == 'desativar_Mensagem') {

        $id_mensagem = $_GET['id'];

        try {

            $query_mensagens = "UPDATE `mmhospedagem_whatsapp_mensagens` SET `mmhospedagem_msg_status` = '0' WHERE `mmhospedagem_msg_id` = ".$id_mensagem."";
            mysql_query($query_mensagens);

            $mmalertaitaushopline = header('Location: ./addonmodules.php?module=mmhospedagem_whatsapp&alerta=sucesso');
            return $mmalertaitaushopline;

        } catch (\Exception $e) {
            echo $e->getMessage();

            $mmalertaitaushopline = header('Location: ./addonmodules.php?module=mmhospedagem_whatsapp&alerta=erro');
            return $mmalertaitaushopline;
        }

    }

    if($_GET['acao'] == 'ativar_Mensagem') {

        $id_mensagem = $_GET['id'];

        try {

            $query_mensagens = "UPDATE `mmhospedagem_whatsapp_mensagens` SET `mmhospedagem_msg_status` = '1' WHERE `mmhospedagem_msg_id` = ".$id_mensagem."";
            mysql_query($query_mensagens);

            $mmalertaitaushopline = header('Location: ./addonmodules.php?module=mmhospedagem_whatsapp&alerta=sucesso');
            return $mmalertaitaushopline;

        } catch (\Exception $e) {
            echo $e->getMessage();

            $mmalertaitaushopline = header('Location: ./addonmodules.php?module=mmhospedagem_whatsapp&alerta=erro');
            return $mmalertaitaushopline;
        }

    }


    if($_GET['acao'] == 'pausar_envio') {

        $id_mensagem = $_GET['id'];

        try {

            $query_mensagens = "UPDATE `mmhospedagem_whatsapp_enviadas` SET `mmhospedagem_status` = '4' WHERE `mmhospedagem_enviadas_id` = ".$id_mensagem."";
            mysql_query($query_mensagens);

            $mmalertaitaushopline = header('Location: ./addonmodules.php?module=mmhospedagem_whatsapp&alerta=sucesso');
            return $mmalertaitaushopline;

        } catch (\Exception $e) {
            echo $e->getMessage();

            $mmalertaitaushopline = header('Location: ./addonmodules.php?module=mmhospedagem_whatsapp&alerta=erro');
            return $mmalertaitaushopline;
        }

    }

    if($_GET['acao'] == 'start_envio') {

        $id_mensagem = $_GET['id'];

        try {

            $query_mensagens = "UPDATE `mmhospedagem_whatsapp_enviadas` SET `mmhospedagem_status` = '0' WHERE `mmhospedagem_enviadas_id` = ".$id_mensagem."";
            mysql_query($query_mensagens);

            $mmalertaitaushopline = header('Location: ./addonmodules.php?module=mmhospedagem_whatsapp&alerta=sucesso');
            return $mmalertaitaushopline;

        } catch (\Exception $e) {
            echo $e->getMessage();

            $mmalertaitaushopline = header('Location: ./addonmodules.php?module=mmhospedagem_whatsapp&alerta=erro');
            return $mmalertaitaushopline;
        }

    }

    if($_GET['acao'] == 'delete_envio') {

        $id_mensagem = $_GET['id'];

        try {

            $query_mensagens = "DELETE FROM mmhospedagem_whatsapp_enviadas WHERE mmhospedagem_enviadas_id = " . $id_mensagem . "";
            mysql_query($query_mensagens);

            $mmalertaitaushopline = header('Location: ./addonmodules.php?module=mmhospedagem_whatsapp&alerta=sucesso');
            return $mmalertaitaushopline;

        } catch (\Exception $e) {
            echo $e->getMessage();

            $mmalertaitaushopline = header('Location: ./addonmodules.php?module=mmhospedagem_whatsapp&alerta=erro');
            return $mmalertaitaushopline;
        }

    }


    echo '

    <link href="../modules/addons/mmhospedagem_whatsapp/css/mmhospedagem_admin.css" type="text/css" rel="stylesheet" />

    <script type="text/javascript" src="../modules/addons/mmhospedagem_whatsapp/mmhospedagem_javascript/angular/angular.js"></script>
    <script type="text/javascript" src="../modules/addons/mmhospedagem_whatsapp/mmhospedagem_javascript/angular/dirPagination.js"></script>
    <script type="text/javascript" src="../modules/addons/mmhospedagem_whatsapp/mmhospedagem_javascript/app.js"></script>

    <script type="text/javascript" src="https://www.mmhospedagem.com.br/atendimento/php/app.php?widget-init.js"></script>


    <div ng-app="mmhospedagem" class="body_MMHospedagem">


    <div class="Header_MMHospedagem_Template">
        <div class="caixa">

            <div class="logo-mmhospedagem"></div>
            <span>Este software foi desenvolvido pela <strong>MMHospedagem</strong>.</span>

            <div class="right" style="margin-top: 7px;">
                <a class="btn btn-default" data-target="#configuracoes" data-toggle="modal" style="padding: 26px 38px;"><i class="fa fa-cogs" aria-hidden="true"></i> '. $vars['_lang']['mmhospedagem_whatsapp_botao_configuracoes'] .'</a>
            </div>


            <div class="MMHospedagem_Clear"></div>
        </div>
    </div>


    <ul class="Menu_MMHospedagem nav-tabs" role="tablist">
        <li class="active"><a class="tab-top" href="#tab2" role="tab" data-toggle="tab" id="tabLink2" data-tab-id="2" aria-expanded="true">'. $vars['_lang']['mmhospedagem_whatsapp_texto_relatorio'] .'</a></li>
        <li class=""><a class="tab-top" href="#tab3" role="tab" data-toggle="tab" id="tabLink3" data-tab-id="3" aria-expanded="false">'. $vars['_lang']['mmhospedagem_whatsapp_texto_relatorio_msg_enviada'] .'</a></li>
        <li class=""><a class="tab-top" href="#tab6" role="tab" data-toggle="tab" id="tabLink1" data-tab-id="6" aria-expanded="false">Contatos</a></li>
        <li class=""><a class="tab-top" href="#tab5" role="tab" data-toggle="tab" id="tabLink1" data-tab-id="1" aria-expanded="false">Template de Mensagens</a></li>
        <div class="MMHospedagem_Clear"></div>
    </ul>';

    // Tela de alertas

    if($_GET['alerta'] == 'sucesso') {
        echo '<div class="alert alert-success alert-dismissable">
                    <a href="#" class="close" data-dismiss="alert" aria-label="close">×</a>
                    <strong>'. $vars['_lang']['mmhospedagem_whatsapp_alerta_sucesso'] .'</strong>
                </div>';
    }

    if($_GET['alerta'] == 'erro') {
        echo '<div class="alert alert-error alert-dismissable">
                    <a href="#" class="close" data-dismiss="alert" aria-label="close">×</a>
                    <strong>'. $vars['_lang']['mmhospedagem_whatsapp_alerta_erro'] .'</strong>
                </div>';
    }

    echo '



    <div class="Conteudo_MMHospedagem">

        <div class="tab-content admin-tabs ng-scope">



        <div class="tab-content admin-tabs" ng-controller="controllerMMHospedagem">


            <div class="tab-pane fade" id="tab7">
                <div class="row">

                    <div class="col-sm-3">

                        <ul class="nav nav-pills nav-stacked" role="tablist">
                            <li style="background-color: #f8f8f8; border-radius: 7px; border-left: 3px solid #337ab7;"><a ng-model="menu_campanha" ng-click="menu_campanha = \'1\'" href="#nova_campanha" role="tab" data-toggle="tab" id="tabLink2" data-tab-id="2" aria-expanded="false"><i class="fab fa-pushed"></i> Adicionar nova campanha</a></li>

                            <!--
                            <li style="background-color: #f8f8f8; border-radius: 7px; border-left: 3px solid #337ab7;"><a ng-model="menu_campanha" ng-click="menu_campanha = \'2\'" href="#lista_campanhas" role="tab" data-toggle="tab" id="tabLink2" data-tab-id="2" aria-expanded="false"><i class="fas fa-clipboard-list"></i> Lista de campanhas</a></li>
                            -->

                        </ul>

                    </div>


                    <div class="col-sm-9">

                        <div class="alert alert-warning" id="updateEnvironmentIssues" ng-hide="menu_campanha == \'1\' || menu_campanha == \'2\'">
                            <div class="alert-icon">
                                <i class="far fa-hand-point-left"></i>
                            </div>
                            <div class="alert-msg">
                                <strong>Bem vindo(a) ao sistema de marketing</strong><br>
                                Escolhe ao lado o que deseja fazer agora!
                            </div>
                        </div>

                        <div class="tab-pane fade in" id="nova_campanha" ng-if="menu_campanha == \'1\'">

                            <h1 style="margin-top: 16px;"><i class="fa fa-plus-square"></i> Adicionar uma nova campanha!</h1>

                            <div class="alert alert-warning" id="updateEnvironmentIssues">
                                <div class="alert-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="alert-msg">
                                    <strong>A utilização desta ferramenta pode causar bloqueios por parte do whatsapp!</strong><br>
                                    Use esta ferramenta para enviar campanhas de promoção, alertas de novidades ou qualquer outro tipo de mensagem relacionada a sua empresa!
                                </div>
                            </div>


                            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3" style="margin-bottom: 18px;">
                                <tbody>
                                    <tr>

                                        <td width="20%" class="fieldlabel">
                                            Tipo de Campanha
                                        </td>

                                        <td class="fieldarea">
                                            <label class="radio-inline">
                                                <input type="radio" name="TipoMensagem" id="typegen" ng-model="tipo_mensagem" ng-click="tipo_mensagem = \'1\'" ng-checked="tipo_mensagem == \'1\'"> Geral
                                            </label>

                                            <!--
                                            <label class="radio-inline">
                                                <input type="radio" name="TipoMensagem" id="typegen" ng-model="tipo_mensagem" ng-click="tipo_mensagem = \'2\'" ng-checked="tipo_mensagem == \'2\'"> Produtos & Serviços
                                            </label>
                                            -->

                                        </td>

                                    </tr>

                                </tbody>
                            </table>


                            <!-- Geral -->

                            <form method="POST" id="ExecutarEnvioCampanha" onSubmit="sendConsulta();return false;" ng-if="tipo_mensagem == \'1\'">

                                <div class="panel panel-default">

                                    <div class="panel-heading">
                                        <i class="fas fa-bullhorn"></i> Campanha Geral
                                    </div>

                                    <div class="panel-body">
                                        <div class="row">
                                            <div class="col-md-12">

                                                <script type="text/javascript" language="javascript">
                                                    function addTag(tag)
                                                    {
                                                    var selection = \'\';
                                                    var textarea = document.getElementById(\'mensagem_marketing\');

                                                    // Internet Explorer
                                                    if (textarea.selectionStart === undefined)
                                                    {
                                                        var textrange = document.selection.createRange();
                                                        var selection = textrange.text;

                                                        if (selection == \'\')
                                                        {
                                                            alert (\'Sem texto seleccionado.\');
                                                        }

                                                        else
                                                        {
                                                            textarea.focus();
                                                            textrange .text = tag + selection + tag;
                                                        }
                                                    }

                                                    else
                                                    {
                                                        if (textarea.selectionStart != textarea.selectionEnd)
                                                        {
                                                            selection = textarea.value.substring(textarea.selectionStart, textarea.selectionEnd);

                                                            var selection_start = textarea.value.substring(0, textarea.selectionStart);
                                                            var selection_end = textarea.value.substring(textarea.selectionEnd);

                                                        }

                                                        if (selection == \'\')
                                                        {
                                                            alert (\'Sem texto seleccionado.\');
                                                        }

                                                        else
                                                        {
                                                            textarea.value = selection_start + tag + selection + tag + selection_end;
                                                        }
                                                    }

                                                    }
                                                </script>


                                                <input type="hidden" name="whatsapp_action" value="whatsapp_campanha_geral">

                                                <label>Título da Campanha</label>
                                                <input class="form-control" name="titulo_campanha" id="titulo_campanha" value="" required>

                                                <div style="height: 10px; width: 100%; clear: both;"></div>

                                                <label>Status da Conta</label>
                                                <select class="form-control" id="status_conta" name="status_conta" required>
                                                    <option value="">Selecione uma opção</otion>
                                                    <option value="1">Ativo</otion>
                                                    <option value="2">Inativo</otion>
                                                    <option value="3">Fechada</otion>
                                                    <option value="4">Todos os Status</otion>
                                                </select>

                                                <div style="height: 10px; width: 100%; clear: both;"></div>

                                                <label>Essa campanha tera imagem?</label>
                                                <select class="form-control" name="com_ou_sem_imagem" id="com_ou_sem_imagem" ng-model="com_ou_sem_imagem" required>
                                                    <option value="">Selecione uma opção</otion>
                                                    <option value="1">Não</otion>
                                                    <option value="2">Sim</otion>
                                                </select>

                                                <div ng-if="com_ou_sem_imagem == \'2\'">

                                                    <div ng-show="com_ou_sem_imagem == \'2\'" style="height: 10px; width: 100%; clear: both;"></div>

                                                    <div ng-show="com_ou_sem_imagem == \'2\'">
                                                        <label>URL da Imagem</label>
                                                        <input ng-model="url_imagem_campanha" name="url_imagem_campanha" id="url_imagem_campanha" class="form-control" value="">
                                                        <font color="#cccccc"><small>( A URL da imagem deve possui <strong>https://</strong> ou <strong>http://</strong> )</small></font>
                                                    </div>

                                                </div>


                                                <div style="height: 10px; width: 100%; clear: both;"></div>

                                                <label>Mensagem</label>

                                                <textarea ng-model="mensagem_marketing" rows="6" name="mensagem_marketing" id="mensagem_marketing" class="form-control" tabindex="38" style="padding-top: 10px; padding-bottom: 10px;" required></textarea>

                                                <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups" style="position: absolute; right: 15px; margin-top: -142px;">
                                                    <div class="btn-group mr-2" role="group" aria-label="First group">
                                                        <button style="border-radius: 0px 0px 0px 4px;" type="button" class="btn btn-primary" onclick="addTag(\'*\');"><i class="fas fa-bold"></i></button>
                                                        <button type="button" class="btn btn-primary" onclick="addTag(\'_\');"><i class="fas fa-italic"></i></button>
                                                        <button type="button" class="btn btn-primary" onclick="addTag(\'~\');"><i class="fas fa-strikethrough"></i></button>
                                                        <button style="border-radius: 0px 4px 0px 0px;" type="button" class="btn btn-primary" onclick="addTag(\'```\');"><i class="fab fa-autoprefixer"></i></button>
                                                    </div>
                                                </div>

                                                <div class="alert" id="div_carregando" style="display: none; text-align: center;">
                                                    <p style="text-align: center;"><i class="fa fa-spinner fa-pulse fa-fw"></i> &nbsp; Gerando mensagens a serem enviadas!</p>
                                                </div>

                                                <div class="btn-container" style="padding: 29px 0 10px;">
                                                    <input type="submit" value="Enviar campanha" class="btn btn-primary">
                                                </div>

                                                <div id="resultado"></div>

                                                <script type="text/javascript">
                                                    function sendConsulta(){
                                                        $.ajax({
                                                        type: "POST",
                                                        url: "../modules/addons/mmhospedagem_whatsapp/mmhospedagem_funcoes.php",
                                                        data: $("#ExecutarEnvioCampanha").serialize(),
                                                        beforeSend: function(){
                                                            $("#div_carregando").show();
                                                        },
                                                        success: function(data){
                                                            $("#div_carregando").hide();
                                                            $("#resultado").empty().html(data);
                                                        },
                                                        // Se acontecer algum erro é executada essa função
                                                        error: function(erro){
                                                           $("#div_carregando").hide();

                                                        }
                                                        });
                                                    }
                                                </script>

                                            </div>

                                        </div>
                                    </div>

                                </div>

                            </form>


                            <!-- Produtos -->

                            <div class="panel panel-default" ng-if="tipo_mensagem == \'2\'">
                                <div class="panel-heading">
                                    <i class="fas fa-bullhorn"></i> Campanha Produtos & Serviços
                                </div>

                                <div class="panel-body">
                                    <div class="row" style="text-align: center;">

                                        asda1

                                    </div>
                                </div>
                            </div>

                            <!-- Fim -->
                        </div>

                        <!-- Lista de campanhas -->
                        <div class="tab-pane fade" id="lista_campanhas" ng-if="menu_campanha == \'2\'">
                            2
                        </div>

                        <div class="clear"></div>

                    </div>
                </div>


                <div class="clear"></div>

            </div>



            <div class="tab-pane fade" id="tab6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-id-card-alt" aria-hidden="true" style="margin-right: 5px;"></i> Lista de Contatos

                    <form class="form-inline" style="float: right; margin-top: -7px;">
                        <div class="form-group">
                            <input type="text" ng-model="procurar" class="form-control mr-sm-2" type="search" aria-label="Procurar..." placeholder="Procurar...">
                        </div>
                    </form>

                </div>

                <div class="panel-body">
                    <div class="row">

                        <div class="col-md-8" style="width: 100%;">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="text-align: center;">ID</th>
                                        <th style="text-align: center;">Número</th>
                                        <th style="text-align: center;">Nome</th>
                                        <th style="text-align: center;">ID do Cliente</th>
                                        <th style="text-align: center;">Data</th>
                                        <th style="text-align: center;">Ações</th>
                                    </tr>
                                </thead>


                                <tbody ng-init="ConsultaResultadoContatos()">
                                    <tr dir-paginate="contatos in ConsultaResultadoContatos|filter:procurar|itemsPerPage:6" pagination-id="contatos">

                                        <td style="text-align: center; padding-top: 15px;">{{contatos.mmhospedagem_contato_id}}</td>
                                        <td style="text-align: center; padding-top: 15px;">{{contatos.mmhospedagem_contato_numero.substr(2)}}</td>
                                        <td style="text-align: center; padding-top: 15px;">{{contatos.firstname}} {{contatos.lastname}}</td>
                                        <td style="text-align: center; padding-top: 15px;">{{contatos.relid}}</td>
                                        <td style="text-align: center; padding-top: 15px;">{{contatos.mmhospedagem_data}}</td>

                                        <td style="text-align: center;">

                                            <a href="./clientssummary.php?userid={{contatos.relid}}" class="btn btn-default">
                                               <i class="fas fa-id-card-alt" aria-hidden="true" style="margin-right: 5px;"></i> Ver perfil do cliente
                                            </a>

                                        </td>

                                    </tr>
                                </tbody>

                            </table>
                            <div style="height: 10px; width: 100%; clear: both;"></div>

                            <dir-pagination-controls max-size="5" direction-links="true" boundary-links="true" pagination-id="contatos"></dir-pagination-controls>

                        </div>



                    </div>
                </div>
            </div>
        </div>



        <div class="tab-pane fade" id="tab5">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-user" aria-hidden="true" style="margin-right: 5px;"></i> Mensagens do Sistema
                </div>

                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-8" style="width: 100%;">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="text-align: center;">ID</th>
                                        <th style="text-align: center;">Titulo da Mensagem</th>
                                        <th style="text-align: center;">Status</th>
                                        <th style="text-align: center;">Editar</th>
                                        <th style="text-align: center;">Ações</th>
                                    </tr>
                                </thead>

                                <tbody ng-init="ConsultaResultadoMensagens()">
                                    <tr ng-repeat="mensagens in ConsultaResultadoMensagens">

                                        <td style="text-align: center; padding-top: 15px;">
                                            {{mensagens.mmhospedagem_msg_id}}
                                        </td>

                                        <td style="text-align: center; padding-top: 15px;">{{mensagens.mmhospedagem_msg_titulo}}</td>
                                        <td style="text-align: center; padding-top: 8px;">

                                            <div ng-if="mensagens.mmhospedagem_msg_status == 1" class="btn btn-success"><i class="fa fa-check" aria-hidden="true"></i></div>

                                            <div ng-if="mensagens.mmhospedagem_msg_status == 0" class="btn btn-danger"><i class="fas fa-times"></i></div>


                                        </td>

                                        <td style="text-align: center; padding-top: 15px;">

                                            <a style="margin-top: -7px;" href="./addonmodules.php?module=mmhospedagem_whatsapp&acao=editar_mensagem&id={{mensagens.mmhospedagem_msg_id}}" class="btn btn-default">
                                                <i class="far fa-edit"></i> Editar mensagem
                                            </a>

                                        </td>

                                        <td style="text-align: center; padding-top: 15px;">

                                            <a ng-class="{disabled: mensagens.mmhospedagem_msg_status == 0}" style="margin-top: -7px;" ng-href="./addonmodules.php?module=mmhospedagem_whatsapp&acao=desativar_Mensagem&id={{mensagens.mmhospedagem_msg_id}}" class="btn btn-danger">
                                                <i class="far fa-bell-slash"></i> Desativar Envio
                                            </a>

                                            <a ng-class="{disabled: mensagens.mmhospedagem_msg_status == 1}" style="margin-top: -7px;" ng-href="./addonmodules.php?module=mmhospedagem_whatsapp&acao=ativar_Mensagem&id={{mensagens.mmhospedagem_msg_id}}" class="btn btn-success">
                                                <i class="far fa-bell"></i> Ativar Envio
                                            </a>

                                        </td>

                                    </tr>
                                </tbody>
                            </table>
                            <div style="height: 10px; width: 100%; clear: both;"></div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane active" id="tab2">

            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="far fa-envelope-open" aria-hidden="true" style="margin-right: 5px;"></i> '. $vars['_lang']['mmhospedagem_whatsapp_texto_titulo_msg_recebidas'] .'

                    <form class="form-inline" style="float: right; margin-top: -7px;">
                        <div class="form-group">
                            <input type="text" ng-model="procurar" class="form-control" placeholder="Procurar...">
                        </div>
                    </form>

                </div>

                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-8" style="width: 100%;">
                            <table class="table table-hover">
                              <thead>
                                <tr>
                                    <th style="text-align: center;"><i class="fas fa-info-circle" style="margin-right: 5px"></i> '. $vars['_lang']['mmhospedagem_whatsapp_texto_tabelas_recebidas_id'] .'</th>
                                    <th style="text-align: center;"><i class="fas fa-mobile-alt" style="margin-right: 5px"></i> '. $vars['_lang']['mmhospedagem_whatsapp_texto_tabelas_recebidas_remetente'] .'</th>
                                    <th style="text-align: center;"><i class="fas fa-calendar-alt" style="margin-right: 5px"></i> '. $vars['_lang']['mmhospedagem_whatsapp_texto_tabelas_recebidas_dataehora'] .'</th>
                                    <th style="text-align: center;"><i class="fas fa-inbox" style="margin-right: 5px"></i> '. $vars['_lang']['mmhospedagem_whatsapp_texto_tabelas_recebidas_mensagem'] .'</th>
                                  </tr>
                                </thead>

                                <tbody ng-init="ConsultaResultadoCxEntrada()">

                                    <tr dir-paginate="mensagems_recebidas in displayCxEntrada|filter:procurar|itemsPerPage:6" pagination-id="cx_entrada">

                                        <td style="text-align: center; padding-top: 44px;">{{mensagems_recebidas.mmhospedagem_entrada_id}}</td>
                                        <td style="text-align: center; padding-top: 44px;">{{mensagems_recebidas.mmhospedagem_remetente}}</td>
                                        <td style="text-align: center; padding-top: 44px;">{{mensagems_recebidas.mmhospedagem_data}}</td>
                                        <td style="text-align: center;">
                                        <textarea disabled="disabled" rows="4" class="form-control">{{mensagems_recebidas.mmhospedagem_msg}}</textarea>
                                        </td>

                                    </tr>

                                </tbody>

                            </table>
                            <div style=" width: 100%; clear: both;"></div>

                            <dir-pagination-controls max-size="5" direction-links="true" boundary-links="true" pagination-id="cx_entrada"></dir-pagination-controls>

                        </div>
                    </div>
                </div>
            </div>

        </div>';

        $query_contar_msg_enviadas = "SELECT * FROM mmhospedagem_whatsapp_enviadas WHERE mmhospedagem_status = 1 ORDER BY mmhospedagem_enviadas_id DESC";

        $executa_query_contar_msg_enviadas = mysql_query($query_contar_msg_enviadas);

        $contagem_msg_enviadas = mysql_num_rows($executa_query_contar_msg_enviadas); // verifica o número total de registros

        $query_contar_msg_enviadas2 = "SELECT * FROM mmhospedagem_whatsapp_enviadas WHERE mmhospedagem_status = 0 ORDER BY mmhospedagem_enviadas_id DESC";

        $executa_query_contar_msg_enviadas2 = mysql_query($query_contar_msg_enviadas2);

        $contagem_msg_enviadas2 = mysql_num_rows($executa_query_contar_msg_enviadas2); // verifica o número total de registros

        $query_contar_msg_enviadas3 = "SELECT * FROM mmhospedagem_whatsapp_enviadas WHERE mmhospedagem_status = 2 ORDER BY mmhospedagem_enviadas_id DESC";

        $executa_query_contar_msg_enviadas3 = mysql_query($query_contar_msg_enviadas3);

        $contagem_msg_enviadas3 = mysql_num_rows($executa_query_contar_msg_enviadas3); // verifica o número total de registros


        echo '<div class="tab-pane fade" id="tab3">

            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="far fa-envelope-open" style="margin-right: 5px"></i> '. $vars['_lang']['mmhospedagem_whatsapp_texto_titulo_msg_enviadas'] .'

                    <form class="form-inline" style="float: right; margin-top: -7px;">
                        <div class="form-group">
                            <input type="text" ng-model="procurar" class="form-control" placeholder="Procurar...">
                        </div>
                    </form>

                    <div class="btn btn-success" style="float: right; margin-top: -7px; margin-right: 8px; font-size: 12px; cursor: auto;">
                        <i class="far fa-check-circle"></i> Mensagens enviadas <span class="badge badge-light" style="background-color: #fff; color: #000;">'.$contagem_msg_enviadas.'</span>
                    </div>

                    <div class="btn btn-danger" style="float: right; margin-top: -7px; margin-right: 8px; font-size: 12px; cursor: auto;">
                        <i class="far fa-times-circle"></i> Mensagens com erro <span class="badge badge-light" style="background-color: #fff; color: #000;">'.$contagem_msg_enviadas3.'</span>
                    </div>

                    <div class="btn btn-warning" style="float: right; margin-top: -7px; margin-right: 8px; font-size: 12px; cursor: auto;">
                        <i class="far fa-clock"></i> Mensagens aguardando envio <span class="badge badge-light" style="background-color: #fff; color: #000;">'.$contagem_msg_enviadas2.'</span>
                    </div>

                </div>

                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-8" style="width: 100%;">
                            <table class="table table-hover">
                              <thead>
                                <tr>
                                <!--
                                    <th style="text-align: center;"><i class="fas fa-info-circle" style="margin-right: 5px"></i> '. $vars['_lang']['mmhospedagem_whatsapp_texto_tabelas_enviadas_id'] .'</th>
                                -->
                                    <th style="text-align: center;"><i class="fas fa-mobile-alt" style="margin-right: 5px"></i> '. $vars['_lang']['mmhospedagem_whatsapp_texto_tabelas_enviadas_remetente'] .'</th>
                                    <th style="text-align: center;"><i class="fas fa-calendar-alt" style="margin-right: 5px"></i> '. $vars['_lang']['mmhospedagem_whatsapp_texto_tabelas_enviadas_dataehora'] .'</th>
                                    <th style="text-align: center;"><i class="far fa-comments" style="margin-right: 5px"></i> '. $vars['_lang']['mmhospedagem_whatsapp_texto_tabelas_enviadas_mensagem'] .'</th>
                                    <th style="text-align: center;"><i class="far fa-question-circle" style="margin-right: 5px"></i> '. $vars['_lang']['mmhospedagem_whatsapp_texto_tabelas_enviadas_status'] .'</th>
                                    <th style="text-align: center;"><i class="fas fa-charging-station" style="margin-right: 5px"></i> Ações</th>
                                  </tr>
                                </thead>

                                <tbody ng-init="ConsultaResultadoCxEnviadas()">

                                    <tr dir-paginate="mensagems_enviadas in ConsultaResultadoCxEnviadas|filter:procurar|itemsPerPage:6" pagination-id="cx_enviadas">

                                    <!--
                                        <td style="text-align: center; padding-top: 44px;">{{mensagems_enviadas.mmhospedagem_enviadas_id}}</td>
                                    -->
                                        <td style="text-align: center; padding-top: 44px;">
                                            {{mensagems_enviadas.mmhospedagem_destino}}
                                            <div ng-if="mensagems_enviadas.mmhospedagem_status == 2" class="btn btn-danger" style="color: #fff; padding: 5px; font-size: 9px; margin-left: 6px;">
                                                Inválido
                                            </div>
                                        </td>

                                        <td style="text-align: center; padding-top: 44px;">{{mensagems_enviadas.mmhospedagem_data}}</td>
                                        <td style="text-align: center;">
                                        <textarea disabled="disabled" rows="4" class="form-control">{{mensagems_enviadas.mmhospedagem_msg}}</textarea>
</td>

                                        <td style="text-align: center; padding-top: 37px;">

                                            <div ng-if="mensagems_enviadas.mmhospedagem_status == 0" class="btn btn-warning"><i class="far fa-clock"></i></div>

                                            <div ng-if="mensagems_enviadas.mmhospedagem_status == 1" class="btn btn-success"><i class="fa fa-check" aria-hidden="true"></i></div>

                                            <div ng-if="mensagems_enviadas.mmhospedagem_status == 2" class="btn btn-danger"><i class="fas fa-times"></i></div>

                                            <div ng-if="mensagems_enviadas.mmhospedagem_status == 4" class="btn btn-info"><i class="far fa-pause-circle"></i></div>

                                        </td>

                                        <td style="text-align: center; padding-top: 37px;">

                                            <div class="btn-group" role="group" aria-label="Basic example">
                                                <a ng-class="{disabled: mensagems_enviadas.mmhospedagem_status == 0 || mensagems_enviadas.mmhospedagem_status == 1}" href="./addonmodules.php?module=mmhospedagem_whatsapp&acao=start_envio&id={{mensagems_enviadas.mmhospedagem_enviadas_id}}" class="btn btn-primary"><i class="far fa-play-circle"></i></a>
                                                <a ng-class="{disabled: mensagems_enviadas.mmhospedagem_status > 0}" href="./addonmodules.php?module=mmhospedagem_whatsapp&acao=pausar_envio&id={{mensagems_enviadas.mmhospedagem_enviadas_id}}" class="btn btn-info"><i class="far fa-pause-circle"></i></a>


                                                <a ng-class="{disabled: mensagems_enviadas.mmhospedagem_status == 1}" href="./addonmodules.php?module=mmhospedagem_whatsapp&acao=delete_envio&id={{mensagems_enviadas.mmhospedagem_enviadas_id}}" class="btn btn-danger"><i class="far fa-trash-alt"></i></a>
                                            </div>

                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                            <div style=" width: 100%; clear: both;"></div>

                            <dir-pagination-controls max-size="5" direction-links="true" boundary-links="true" pagination-id="cx_enviadas"></dir-pagination-controls>

                        </div>
                    </div>
                </div>

            </div>

        </div>



        <div class="tab-pane fade" id="tab4">

            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fab fa-android" aria-hidden="true" style="margin-right: 5px;"></i> '. $vars['_lang']['mmhospedagem_whatsapp_texto_titulo_robos'] .'
                </div>

                <div class="panel-body">
                    <div class="row">

                    <div class="context-btn-container" style="width: 100%; margin: -15px auto 11px;">
                        <a href="./addonmodules.php?module=mmhospedagem_whatsapp&acao=adicionarrobo" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Adicionar um novo robô</a>
                    </div>

                        <div class="col-md-8" style="width: 100%;">

                            <table class="table table-hover">
                              <thead>
                                <tr>
                                    <th style="text-align: center;">'. $vars['_lang']['mmhospedagem_whatsapp_texto_robos_id'] .'</th>
                                    <th style="text-align: center;">'. $vars['_lang']['mmhospedagem_whatsapp_texto_robos_titulo'] .'</th>
                                    <th style="text-align: center;">'. $vars['_lang']['mmhospedagem_whatsapp_texto_robos_disparo'] .'</th>
                                    <th style="text-align: center;">'. $vars['_lang']['mmhospedagem_whatsapp_texto_robos_acoes'] .'</th>
                                  </tr>
                                </thead>

                                ';

                                /////////////////////////////////////////////////////////////////////////////////
                                // foreach inicio ///////////////////////////////////////////////////////////////
                                /////////////////////////////////////////////////////////////////////////////////

                                $query_robos = "SELECT * FROM mmhospedagem_whatsapp_robos ORDER BY mmhospedagem_robos_id DESC";

                                $exeucao_query_robos = mysql_query("$query_robos");
                                $total_de_robos = mysql_num_rows($exeucao_query_robos); // verifica o número total de registros

                                if($total_de_robos == 0) {

                                echo '
                                <tbody>
                                    <tr>
                                        <td colspan="6" align="center">Não existem robôs para gerar o resultado!</td>
                                    </tr>
                                </tbody>
                                ';

                                } else {

                                    while ($query_robos = mysql_fetch_array($exeucao_query_robos)) {

                                        echo '<tbody>
                                            <tr>
                                                <td style="text-align: center; padding-top: 15px;">'. $query_robos['mmhospedagem_robos_id'] .'</td>
                                                <td style="text-align: center; padding-top: 15px;">'. $query_robos['mmhospedagem_robos_titulo'] .'</td>
                                                <td style="text-align: center; padding-top: 15px;">'. $query_robos['mmhospedagem_robos_codigo'] .'</td>

                                                <td style="text-align: center; padding-top: 15px;">

                                                    <div class="btn-group">
                                                        <a style="margin-top: -7px;" href="./addonmodules.php?module=mmhospedagem_whatsapp&acao=editar_robo&id='. $query_robos['mmhospedagem_robos_id'] .'" class="btn btn-default">
                                                            <i class="far fa-edit"></i> Editar robô
                                                        </a>

                                                        <a style="margin-top: -7px;" href="./addonmodules.php?module=mmhospedagem_whatsapp&acao=deletar_robo&id='. $query_robos['mmhospedagem_robos_id'] .'" class="btn btn-danger">
                                                            <i class="fas fa-trash-alt"></i> Apagar robô
                                                        </a>
                                                    </div>

                                                </td>
                                            </tr>
                                        </tbody>';

                                    }
                                }

                            echo '</table>
                          <div style="height: 10px; width: 100%; clear: both;"></div>

                        </div>

                    </div>
                </div>


            </div>
        </div>

        </div>

        <div class="MMHospedagem_Clear"></div>
    </div>

</div>

<div class="Rodape_MMHospedagem">

        <li>© 2020  - TODOS OS DIREITOS RESERVADOS - WWW.MMHOSPEDAGEM.COM.BR <br>
        CNPJ: <strong>23.847.446/0001-51</strong>
        </li>
        <div class="MMHospedagem_Clear"></div>
    </div>

</div>';

}


function mmhospedagem_whatsapp_clientarea($vars) {


    /////////////////////////////////////////////////////////////////////////////
    // Sistema de validação /////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////

    // ID do cliente que esta logado!
    $id_do_cliente_whatsapp_validacao = Session::get('uid');

    // Verificações das tabelas
    foreach (WHMCS\Database\Capsule::table('mmhospedagem_whatsapp_validacao')->WHERE('MMHospedagem_ID_WHMCS_CLIENTE', $id_do_cliente_whatsapp_validacao)->get() as $modulowhatsapp_validacao_dados_cliente) {
        $mmhospedagem_whatsapp_codigo_validacao_status = $modulowhatsapp_validacao_dados_cliente->MMHospedagem_COD_VALIDACAO;
    }

    $header_validacao = '<link type="text/css" rel="stylesheet" href="./modules/addons/mmhospedagem_whatsapp/css/mmhospedagem.css" />

                        <link rel="stylesheet" href="//use.fontawesome.com/releases/v5.6.3/css/all.css" integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/" crossorigin="anonymous">

                        <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Roboto%3A300&amp;ver=1.0.0" type="text/css" media="all" />
                        <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Open+Sans" type="text/css" media="all" />
                        <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Raleway" type="text/css" media="all" />
                        <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Droid+Sans" type="text/css" media="all" />

                        <script type="text/javascript">
                        jQuery(function($){
                            $("#codigo_whatsapp").mask("999-999");
                        });
                        </script>';

    $input_validacao = '
                        <div class="inputs">
                            <form method="post" id="signin" onSubmit="sendLogin();return false;">

                                <input type="hidden" name="cod_cliente" id="cod_cliente" value="'.$id_do_cliente_whatsapp_validacao.'">

                                <input type="text" name="codigo_whatsapp" id="codigo_whatsapp" required="true" value="">
                                <button class="radios-right" type="submit"><i class="fas fa-fire-alt"></i> Validar agora</button>
                                <button class="radios-left" onclick="window.location.href=\'./clientarea.php?action=details&validacao=1\'"><i class="far fa-edit"></i> Alterar número whatsapp</button>

                                <div class="alert" id="div_carregando" style="display: none; text-align: center;">
                                    <p style="text-align: center;"><i class="fa fa-spinner fa-pulse fa-fw"></i> &nbsp; Verificando o código informado...</p>
                                </div>

                                <div id="segundavia-boleto"></div>

                                <script type="text/javascript">
                                function sendLogin(){
                                    $.ajax({
                                    type: "POST",
                                    url: "./modules/addons/mmhospedagem_whatsapp/mmhospedagem_funcoes.php",
                                    data: $("#signin").serialize(),
                                    beforeSend: function(){
                                        $("#div_carregando").show();
                                    },
                                    success: function(data){
                                        $("#div_carregando").hide();
                                        $("#segundavia-boleto").empty().html(data);
                                    },
                                    // Se acontecer algum erro é executada essa função
                                    error: function(erro){
                                       $("#div_carregando").hide();

                                    }
                                    });
                                }
                                </script>

                            </form>



                        </div>';


    return array(
        'pagetitle' => 'Validação Whatsapp',
        'breadcrumb' => array('index.php?m=mmhospedagem_whatsapp'=>'Validação Whatsapp'),
        'templatefile' => 'mmhospedagem_whatsapp',
        'requirelogin' => true, # accepts true/false
        'forcessl' => false, # accepts true/false
        'vars' => array(
            'header_whatsapp_validacao' => $header_validacao,
            'validacao_whatsapp_input' => $input_validacao,
        ),
    );

}

?>