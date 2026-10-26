<?php


#$ativar = file_get_contents('http://xpainel.com.br/ativaClientes.php?ativar=1&id=123123');



if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

function xmkt_config() {
    $configarray = array(
    "name" => "E-mail Marketing",
    "description" => 'Gestor de Conte&uacute;do Universal - <a target="_blank" href="http://xpainel.com.br/site">X-Painel</a>',
    "version" => "1.0",
    "author" => "Grupo THX",
    "language" => "portuguese",
    "fields" => array(
        "option1" => array ("FriendlyName" => "Token", "Type" => "text", "Size" => "80", "Description" => "Token de Acesso ao sistema X-Painel", "Default" => "", )
    ));
    return $configarray;
}

function xmkt_activate() {

   		return array('status'=>'success','description'=>'Acesso ao Gestor de Conteúdo X-Painel Ativado');
    	return array('status'=>'error','description'=>'Erro ao Ativar Acesso ao Gestor de Conteúdo X-Painel');
    	return array('status'=>'info','description'=>'Acesso ao Gestor de Conteúdo X-Painel Ativado');
		

}

function xmkt_deactivate() {

	return array('status'=>'success','description'=>'Acesso ao Gestor de Conteúdo X-Painel Desativado');
	return array('status'=>'error','description'=>'Erro ao Desativar Acesso ao Gestor de Conteúdo X-Painel');
	return array('status'=>'info','description'=>'Acesso ao Gestor de Conteúdo X-Painel Desativado');

}

function xmkt_upgrade($vars) {

   return false;

}



function xmkt_output($vars) {

    $modulelink = $vars['modulelink'];
    $version = $vars['version'];
    $option1 = $vars['option1'];
    $option2 = $vars['option2'];
    $option3 = $vars['option3'];
    $option4 = $vars['option4'];
    $option5 = $vars['option5'];
    $LANG = $vars['_lang'];

    echo '<p>'.$LANG['intro'].'</p>
<p>'.$LANG['description'].'</p>
<p>'.$LANG['documentation'].'</p>';

}

function xmkt_sidebar($vars) {

    $modulelink = $vars['modulelink'];
    $version = $vars['version'];
    $option1 = $vars['option1'];
    $option2 = $vars['option2'];
    $option3 = $vars['option3'];
    $option4 = $vars['option4'];
    $option5 = $vars['option5'];
    $LANG = $vars['_lang'];

    $sidebar = '<span class="header"><img src="images/icons/addonmodules.png" class="absmiddle" width="16" height="16" /> Example</span>
<ul class="menu">
        <li><a href="#">Demo Sidebar Content</a></li>
        <li><a href="#">Version: '.$version.'</a></li>
    </ul>';
    return $sidebar;

}