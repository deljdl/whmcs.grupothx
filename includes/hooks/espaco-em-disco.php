<?php


//Laravel DataBase
use WHMCS\Database\Capsule;

//Bloqueia o acesso direto ao arquivo
if (!defined("WHMCS")){
	die("Acesso restrito!");
}




add_hook('EmailPreSend', 1, function($vars) {
	logModuleCall('espaco_em_disco_hooks', 'EmailPreSend', 'start', $vars, '');

    if (
		$vars['messagename'] === 'Espaço em Disco Aviso'
	) {
		$espaco_em_disco_merge_fields	= array();

		$dadosArray	= localAPI( 'GetClientsProducts', array('serviceid' => $vars['relid']), false);

        logModuleCall('espaco_em_disco_hooks', 'EmailPreSend', 'serviceArray', $dadosArray, '');

        $dados = $dadosArray['products']['product'][0];

		logModuleCall('espaco_em_disco_hooks', 'EmailPreSend', 'service', $dados, '');


        //die('<pre>'.print_r($dados,1).'</pre>');




        /*
            $espacoTotal = $dados['disklimit'] / 1000;
	$espacoUtilizado = round(($dados['diskusage'] / 1000) , 2);
	$espacoDisponivel = round(($dados['disklimit'] - $dados['diskusage']) / 1000);


	$porcentagemUsada =  round(($dados['diskusage']*100/$dados['disklimit']),2);
	$porcentagemDisponivel =  100 - $porcentagemUsada;


	$mensagemWhats ="
	Olá *{$vars['mergefields']['client_first_name']}* 👋,

	Este é um aviso que sua hospedagem está ficando sem espaço em disco.

	_Dados de seu plano de hospedagem atual:_
	*Domínio:* {$vars['mergefields']['service_domain']}
	*Plano:* {$dados['name']}
	*Espaço total em disco:* {$espacoTotal} GB
	*Espaço já utilizado:*  {$espacoUtilizado} GB  ({$porcentagemUsada}%)
	*Espaço disponível:*  {$espacoDisponivel} GB  ({$porcentagemDisponivel}%)";

        */


        $espacoTotal = $dados['disklimit'] / 1000;
        $espacoUtilizado = round(($dados['diskusage'] / 1000) , 2);
        $espacoDisponivel = round((($dados['disklimit'] - $dados['diskusage']) / 1000), 2);


        $porcentagemUsada =  round(($dados['diskusage']*100/$dados['disklimit']), 2);
        $porcentagemDisponivel =  round((100 - $porcentagemUsada), 2);


        $espaco_em_disco_merge_fields['espaco_em_disco_inicio'] = 'Não usado, serve apenas para separa na listagem e organizar';
        $espaco_em_disco_merge_fields['espaco_em_disco_porcentagem_usada'] = $porcentagemUsada;
        $espaco_em_disco_merge_fields['espaco_em_disco_porcentagem_disponivel'] = $porcentagemDisponivel;
        $espaco_em_disco_merge_fields['espaco_em_disco_total'] = $espacoTotal;
        $espaco_em_disco_merge_fields['espaco_em_disco_usado'] = $espacoUtilizado;
        $espaco_em_disco_merge_fields['espaco_em_disco_disponivel'] = $espacoDisponivel;
        $espaco_em_disco_merge_fields['espaco_em_disco_plano'] = $dados['name'];
        $espaco_em_disco_merge_fields['espaco_em_disco_dominio'] = $dados['domain'];
        $espaco_em_disco_merge_fields['espaco_em_disco_fim'] = 'Não usado, serve apenas para separa na listagem e organizar';

        return $espaco_em_disco_merge_fields;
    }
	else { // Not
		return;
	}
});

add_hook('EmailTplMergeFields', 1, function($vars) {
   $espaco_em_disco_merge_fields = array();
   $espaco_em_disco_merge_fields['espaco_em_disco_inicio'] = 'ESPAÇO EM DISCO 👇';
   $espaco_em_disco_merge_fields['espaco_em_disco_porcentagem_usada'] = 'Quota % Usada';
   $espaco_em_disco_merge_fields['espaco_em_disco_porcentagem_disponivel'] = 'Quota % Disponível';
   $espaco_em_disco_merge_fields['espaco_em_disco_total'] = 'Quota Total';
   $espaco_em_disco_merge_fields['espaco_em_disco_usado'] = 'Quota Utilizado';
   $espaco_em_disco_merge_fields['espaco_em_disco_disponivel'] = 'Quota Disponível';
   $espaco_em_disco_merge_fields['espaco_em_disco_plano'] = 'Quota Plano Contratado';
   $espaco_em_disco_merge_fields['espaco_em_disco_dominio'] = 'Quota Domínio';
   $espaco_em_disco_merge_fields['espaco_em_disco_fim'] = 'ESPAÇO EM DISCO 👆';
    return $espaco_em_disco_merge_fields;
});