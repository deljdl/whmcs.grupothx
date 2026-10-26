<?php

if (!defined("WHMCS"))
   die("This file cannot be accessed directly");



#REQUIRE TARIFAS OBRIGATÓRIO PARA NÃO DAR ERRO NO CRON
if(empty($_SERVER['DOCUMENT_ROOT']))
{
    $_SERVER['DOCUMENT_ROOT'] = '/home/thxcom/whmcs.grupothx.com.br/';
}
require_once($_SERVER['DOCUMENT_ROOT'].'/tarifasTHX.php');




add_hook('PreCronJob', 1, function($vars) {
    // É executado antes da execução diária do cron de automação.
    $envio = enviaLogTHXJdl('Cron iniciou  execução diária de automação (PreCronJob) em '.date('d/m/Y à\s G:i'));
    logModuleCall('/includes/hooks/check_cron.php', 'Check Cron PreCronJob' , $vars, $envio, $vars);
    alexa('Cron iniciou  execução diária de automação');
});

add_hook('AfterCronJob', 1, function($vars) {
    // Executa cada vez que o sistema chama o cron job do sistema. Isso ocorre após a conclusão de todas as tarefas agendadas.
    //$envio = enviaLogTHXJdl('Cron rodou (AfterCronJob) em '.date('d/m/Y à\s G:i'));
    //logModuleCall('/includes/hooks/check_cron.php', 'Check Cron AfterCronJob' , $vars, $envio, $vars);
});

add_hook('DailyCronJob', 1, function($vars) {
    // É executado no final da execução diária do cron de automação.
    $envio = enviaLogTHXJdl('Cron finalizou execução diária (DailyCronJob) em '.date('d/m/Y à\s G:i'));
    logModuleCall('/includes/hooks/check_cron.php', 'Check Cron DailyCronJob' , $vars, $envio, $vars);
    alexa('Cron finalizou  execução diária de automação');
});


add_hook('DailyCronJobPreEmail', 1, function($vars) {
    // É executado após a conclusão das tarefas, mas antes do envio do relatório por e-mail.
    $envio = enviaLogTHXJdl('Cron conclusão das tarefas (DailyCronJobPreEmail) em '.date('d/m/Y à\s G:i'));
    logModuleCall('/includes/hooks/check_cron.php', 'Check Cron DailyCronJobPreEmail' , $vars, $envio, $vars);
});

add_hook('PopEmailCollectionCronCompleted', 1, function($vars) {
    // Executa quando o cron de coleta de e-mail POP é concluído.
    //$envio = enviaLogTHXJdl('Cron de coleta de e-mail POP concluído (PopEmailCollectionCronCompleted) em '.date('d/m/Y à\s G:i'));
    //logModuleCall('/includes/hooks/check_cron.php', 'Check Cron PopEmailCollectionCronCompleted' , $vars, $envio, $vars);
});

