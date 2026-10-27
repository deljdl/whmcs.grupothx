<?php
/**
 * Módulo Gerencinet Boleto para WHMCS
 * @author		Mauricio Gofas | gofas.net
 * @see			https://gofas.net/?p=7893
 * @copyright	2017 https://gofas.net
 * @license		https://gofas.net?p=9340
 * @support		https://gofas.net/?p=7856
 * @version		2.2.2
 */

// Reporta todos os erros
//error_reporting( E_ERROR | E_WARNING | E_PARSE );

if(!defined('WHMCS')) { die(''); }
use WHMCS\Database\Capsule;
function gofasgerencianetboleto_config() {

	$module_version = '2.2.2';
	$module_version_int = (int)preg_replace("/[^0-9]/", "", $module_version);

	// WHMCS Version
	foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'Version') -> get( array('value')) as $whmcs_version ) {
		$whmcs_ver		= (int)substr( preg_replace('/[^0-9]/', '', $whmcs_version->value), 0, 3 );
	}
	// menor que 7.2.3
	if ( $whmcs_ver < 723) {
		$admin_config_type = 'text';
	}
	// maior ou igual a 7.2.3
	elseif ( $whmcs_ver >= 723 ) {
		$admin_config_type = 'System';
	}

	// Verify available updates
	if( !function_exists('ggnb_verify_module_updates') ) {
	function ggnb_verify_module_updates() {
  //  		$query = 'https://gofas.net/br/updates/?software=7893';
  //   	$curl = curl_init();
  //   	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
  //   	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
  //   	curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
  //   	curl_setopt($curl, CURLOPT_URL, $query);

		// $result = curl_exec($curl);
  //   	$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		// curl_close($curl);
		return array(
			'http_status' => 200,
			'result' => '2.2.2',
		);
	}}
	$available_update_ = ggnb_verify_module_updates();
	$available_update = $available_update_['result'];
	$available_update_int = (int)preg_replace("/[^0-9]/", "", $available_update);

	if( $available_update_int === $module_version_int ) {
		$available_update_message = '<p class="ggnb_optional_txt"><i class="fas fa-check-square"></i> Você está executando a versão mais recente do módulo. Porque o Jardel removeu a verificação em '.__FILE__.' - '.__LINE__.'</p>';
	}
	if( $available_update_int > $module_version_int ) {
		$available_update_message = '<p style="font-size: 14px;" class="ggnb_required_txt"><i class="fas fa-exclamation-triangle"></i> Atualização disponível, verifique a <a style="color:#CC0000;text-decoration:underline;" href="https://gofas.net/?p=7893" target="_blank">versão '.$available_update.'</a>';
	}
	if( $available_update_int < $module_version_int ) {
		$available_update_message = '<p style="font-size: 14px;" class="ggnb_required_txt"><i class="fas fa-exclamation-triangle"></i> Você está executando uma versão Beta desse módulo.<br>Não recomendamos o uso dessa versão em produção.<br>Baixar versão estável: <a style="color:#CC0000;text-decoration:underline;" href="https://gofas.net/?p=7893" target="_blank">v'.$available_update.'</a>';
	}

	//echo '<pre>module_version: ',$module_version_int,'<br>available_update: ',$available_update_int,'<br>',print_r($available_update_),'<br>',$available_update_message,'</pre>'; // Debug

	$customfields = array();
	$customfields[] = '';
	foreach( Capsule::table('tblcustomfields') -> where( 'type', '=', 'client' ) -> get( array( 'fieldname', 'sortorder', 'id' ) ) as $customfield ) {
		$customfield_id		= $customfield->id;
		$customfield_name	= $customfield->fieldname;
		$customfields[]		= $customfield_id.' - '.$customfield_name;
	}
	$tblticketdepartments = array();
	$tblticketdepartments[] = '';
	foreach( Capsule::table('tblticketdepartments') -> get() as $tblticketdepartments_ ) {
		$tblticketdepartments_id			= $tblticketdepartments_->id;
		$tblticketdepartments_name			= $tblticketdepartments_->name;
		$tblticketdepartments[]				= $tblticketdepartments_id.' - '.$tblticketdepartments_name;
	}
	// Get Config
	$actual_link		= (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	if ( stripos( $actual_link, '/configgateways.php') ) {
		// Local V URL
		$whmcs_url__ = str_replace("\\",'/',(isset($_SERVER['HTTPS']) ? "https://" : "http://").$_SERVER['HTTP_HOST'].substr(getcwd(),strlen($_SERVER['DOCUMENT_ROOT'])));
		$admin_url = $whmcs_url__.'/';
		$vtokens = explode('/', $actual_link);
		$whmcs_admin_path = '/'.$vtokens[sizeof($vtokens)-2].'/';
		$whmcs_url = str_replace( $whmcs_admin_path, '', $admin_url).'/';

		foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'ggnbwhmcsurl') -> get( array( 'value','created_at' ) ) as $ggnbwhmcsurl_ ) {
			$ggnbwhmcsurl					= $ggnbwhmcsurl_->value;
			$ggnbwhmcsurl_created_at			= $ggnbwhmcsurl_->created_at;
		}
		foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'ggnbwhmcsadminurl') -> get( array( 'value','created_at' ) ) as $ggnbwhmcsadminurl_ ) {
			$ggnbwhmcsadminurl				= $ggnbwhmcsadminurl_->value;
			$ggnbwhmcsadminurl_created_at	= $ggnbwhmcsurl_->created_at;
		}
		foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'ggnbwhmcsadminpath') -> get( array( 'value','created_at' ) ) as $ggnbwhmcsadminpath_ ) {
			$ggnbwhmcsadminpath				= $ggnbwhmcsadminpath_->value;
			$ggnbwhmcsadminpath_created_at	= $ggnbwhmcsurl_->created_at;
		}

		if ( !$ggnbwhmcsurl ) {
			// Set config
			try { Capsule::table('tblconfiguration')->insert(array('setting' => 'ggnbwhmcsurl', 'value' => $whmcs_url, 'created_at' => date("Y-m-d H:i:s") , 'updated_at' => date("Y-m-d H:i:s")));}
			catch (\Exception $e) { $e->getMessage(); }

			try { Capsule::table('tblconfiguration')->insert(array('setting' => 'ggnbwhmcsadminurl', 'value' => $admin_url, 'created_at' => date("Y-m-d H:i:s") , 'updated_at' => date("Y-m-d H:i:s")));}
			catch (\Exception $e) { $e->getMessage(); }

			try { Capsule::table('tblconfiguration')->insert(array('setting' => 'ggnbwhmcsadminpath', 'value' => $whmcs_admin_path, 'created_at' => date("Y-m-d H:i:s") , 'updated_at' => date("Y-m-d H:i:s")));}
			catch (\Exception $e) { $e->getMessage(); }
		}

		// Update Settings
		if ( $ggnbwhmcsurl and ($whmcs_url !== $ggnbwhmcsurl) ) {
			try { Capsule::table('tblconfiguration')->where( 'setting', 'ggnbwhmcsurl')->update(array('value' => $whmcs_url, 'created_at' =>  $ggnbwhmcsurl_created_at , 'updated_at' => date("Y-m-d H:i:s")));}
			catch (\Exception $e) {$e->getMessage();}
		}
		if ( $ggnbwhmcsadminurl and ($admin_url !== $ggnbwhmcsadminurl) ) {
			try { Capsule::table('tblconfiguration')->where( 'setting', 'ggnbwhmcsadminurl')->update(array('value' => $admin_url, 'created_at' =>  $ggnbwhmcsadminurl_created_at , 'updated_at' => date("Y-m-d H:i:s")));}
			catch (\Exception $e) {$e->getMessage();}
		}
		if ( $ggnbwhmcsadminpath and ($whmcs_admin_path !== $ggnbwhmcsadminpath) ) {
			try { Capsule::table('tblconfiguration')->where( 'setting', 'ggnbwhmcsadminpath')->update(array('value' => $whmcs_admin_path, 'created_at' =>  $ggnbwhmcsadminpath_created_at , 'updated_at' => date("Y-m-d H:i:s")));}
			catch (\Exception $e) {$e->getMessage();}
		}
	}
	// Verify module addon instalation
	foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'ActiveAddonModules') -> get( array( 'value' ) ) as $ActiveAddonModules_ ) {
			$ActiveAddonModules				= $ActiveAddonModules_->value;
	}

	// Options count
	$opt_num = 1;

	if( strpos( (string)$ActiveAddonModules, 'gofasgerencianet' ) !== false ) {
		if( !function_exists('ggnb_billet_on_email') ) {
			function ggnb_billet_on_email($opt_num) {
			return array(
				'FriendlyName' => $opt_num.'- Informações do Boleto no email',
				'Type' => 'yesno',
				'Default' => 'yes',
				'Description' => 'Adiciona link, linha digitável, vencimento e outras informações do boleto no corpo dos emails de faturas. Essa opção faz o módulo gerar os boletos no momento em que a fatura é gerada, (do contrário o Boleto é gerado no 1º acesso à Fatura). <a style="font-weight: bold;text-decoration:underline;" target="_blank" href="https://gofas.net/?p=7893#mergetags">Veja aqui a lista de tags de mesclagem dosponíveis</a> .',
			);
		}}
		if( !function_exists('ggnb_link_billet_opt') ) {
		function ggnb_link_billet_opt($opt_num) {
			return array(
				'FriendlyName' => $opt_num.'- Substituir link da fatura por link do boleto',
				'Type' => 'yesno',
				'Default' => 'yes',
				'Description' => 'Substitui o URL da Fatura pelo URL do Boleto nos emails de "Nova Fatura" (tag <code>{$invoice_link}</code> do template de email <i>Invoice Created</i>).',
			);
		}}
		if( !function_exists('ggnb_cancel_billet_opt') ) {
		function ggnb_cancel_billet_opt($opt_num) {
			return array(
				'FriendlyName' => $opt_num.'- Cancelar Boleto ao cancelar Fatura',
				'Type' => 'yesno',
				'Default' => 'yes',
				'Description' => '<span class="ggnb_optional_txt">(Opcional)</span> Cancela o Boleto associado à uma fatura quando essa fatura é cancelada no WHMCS.',
			);
		}}
	}
	elseif( strpos( (string)$ActiveAddonModules, 'gofasgerencianet' ) === false ) {
		if( !function_exists('ggnb_billet_on_email') ) {
		function ggnb_billet_on_email($opt_num) {
			return array(
				'FriendlyName' => $opt_num.'- Informações do Boleto no email',
				'Description' => '<span class="gbf_required_txt">Para habilitar essa opção antes você precisa instalar e ativar o <a target="_blank" href="https://gofas.net/?p=7893#installaddon" style="text-decoration:underline; font-weight: bold;">módulo <i>addon Gofas Gerencianet</i></a> incluso no pacote desse módulo.</span>',
			);
		}}
		if( !function_exists('ggnb_link_billet_opt') ) {
		function ggnb_link_billet_opt($opt_num) {
			return array(
				'FriendlyName' => $opt_num.'- Substituir link da fatura por link do boleto',
				'Description' => '<span class="gbf_required_txt">Para habilitar essa opção antes você precisa instalar e ativar o <a target="_blank" href="https://gofas.net/?p=7893#installaddon" style="text-decoration:underline; font-weight: bold;">módulo <i>addon Gofas Gerencianet</i></a> incluso no pacote desse módulo.</span>',
			);
		}}
		if( !function_exists('ggnb_cancel_billet_opt') ) {
		function ggnb_cancel_billet_opt($opt_num) {
			return array(
				'FriendlyName' => $opt_num.'- Cancelar Boleto ao cancelar Fatura',
				'Description' => '<span class="gbf_required_txt">Para habilitar essa opção antes você precisa instalar e ativar o <a target="_blank" href="https://gofas.net/?p=7893#installaddon" style="text-decoration:underline; font-weight: bold;">módulo <i>addon Gofas Gerencianet</i></a> incluso no pacote desse módulo.</span>',
			);
		}}

	}
	/**
	*
	* If extra custom configs, show here
	* since version 2_2_0
	*
	*/

	if( !function_exists( 'ggnb_custom_config') ) {
		function ggnb_custom_config($opt_num,$customfields) {
			$extra_config_ = array();
			$extra_config_['extra_config_intro'] = array('Description' => '<div class="ggnb_separator"><h4>Opções customizadas<h4><p>Consulte <a href="https://gofas.net/?p=11630" target="_blank">este tutorial</a> para mais informações sobre como adicionar opções personalizadas sem editar diretamente o código do módulo, podendo atualiza-lo no futuro sem perder as customizações.</p></div>',);

			foreach( glob( __DIR__.'/customconfig/*.php') as $file ) {
				if ( file_exists($file) ) {
    				include $file;
					$file_name = basename($file, '.php');
					foreach($$file_name as $value) {
						$extra_config_[$file_name] = $value;
					}
				}
			}
			return $extra_config_;
		}
	}

	/// Display Options
	$options_to_display = array(
		// Nome de exibição amigável para o gateway
		'FriendlyName' => array(
			'Type' => 'System',
			'Value' => 'Gofas Gerencianet - Boleto',
			'Size' => '60',
		),

		/*
		 * Separador 1
		 * Configurações Básicas
		 *
		*/
		'separator_1' => array(
			'Description' => '
			<style type="text/css">
			.ggnb_section {
				background: #dcdcdc; padding: 10px 15px 1px;
			}
			.ggnb_separator {
				background: #dcdcdc; padding: 1px 15px 1px;
			}
			.ggnb_separator p {
    			font-size: 12px;
   				 margin: 0px 0px 5px 0px;
			}
			.ggnb_required {
				color: #CC0000;
				font-size: 20px;
				line-height: 0;
			}
			.ggnb_required_txt {
				color: #CC0000;
			}
			.ggnb_optional_txt {
				color: #02bb04;
			}
			#Payment-Gateway-Config-gofasgerencianetboleto td.fieldlabel {
    			background-color: #fff;
    			text-align: right;
    			vertical-align: text-top;
			}
			#Payment-Gateway-Config-gofasgerencianetboleto td.input-inline {
    			display: inline-block;
    			float: left;
    			clear: left;
			}
			#Payment-Gateway-Config-gofasgerencianetboleto td.fieldarea input {
    			margin-right: 5px;
			}
			</style>
			<div class="ggnb_separator">

			<div style="width:145px; float: right;padding: 8px 0px;">
					<a target="_blank" href="https://gofas.net/br/?ref=gbfAdminPanel"><img style=" width: 60px; margin: 0 10px 0 0;" src="'.$ggnbwhmcsurl.'modules/gateways/gofasgerencianetboleto/assets/img/gofas.png"></a>
					<a target="_blank" href="https://gerencianet.com.br/parceiro/gofas/"><img style=" width: 69px;" src="'.$ggnbwhmcsurl.'modules/gateways/gofasgerencianetboleto/assets/img/gerencianet.png"></a>
				</div>
				<div style="margin-left: 10px;">
					<h4 style="padding-top: 5px;">Módulo Gerencinet Boleto para WHMCS v'.$module_version.'</h4>
					'.$available_update_message.'
					<h6>Antes de iniciar a configuração, lembre-se de:</h6>
					<p>- Ler e seguir as instruções da <a style="text-decoration:underline;" target="_blank" href="https://gofas.net/?p=7893">documentação do módulo</a>.</p>
					<p>- Criar um <a style="text-decoration:underline;" target="_blank" href="'.$ggnbwhmcsadminurl.'configcustomfields.php">campo personalizado de cliente</a> para CPF e/ou CNPJ, ou se preferir, criar dois campos distintos, um campo apenas para CPF e outro campo para CNPJ. O módulo identifica os campos do perfil do cliente automaticamente.</p>

					<p>- Criar uma Aplicação e obter as credencians <i>Client_ID</i> e <i>Client _Secret</i> da <a style="text-decoration: underline;" target="_blank" href="https://docs.gerencianet.com.br/">API Gerencianet</a>. Veja <a style="text-decoration: underline;" target="_blank" href="https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2017/04/API_Gerencianet_Client_ID_Client_Secret_.png">nesta imagem</a> onde encontrar essa opção no painel de controle Gerencianet.</p>
				</div>

			</div>',
		),

		// Client ID
		'clientid' => array(
			'FriendlyName' => $opt_num++.'- Client_Id Produção<span class="ggnb_required">*</span>',
			'Type' => 'text',
			'Size' => '60',
			'Default' => '',
			'Description' => '<span class="ggnb_required_txt">(Obrigatório)</span>',
		),
		// Client Secret
		'clientsecret' => array(
			'FriendlyName' => $opt_num++.'- Client_Secret Produção<span class="ggnb_required">*</span>',
			'Type' => 'text',
			'Size' => '60',
			'Default' => '',
			'Description' => '<span class="ggnb_required_txt">(Obrigatório)</span>',
		),
		// Client ID Sandbox
		'clientidsandbox' => array(
			'FriendlyName' => $opt_num++.'- Client_Id Desenvolvimento<span class="ggnb_required">*</span>',
			'Type' => 'text',
			'Size' => '60',
			'Default' => '',
			'Description' => '<span class="ggnb_required_txt">(Obrigatório)</span>',
		),
		// Client Secret Sandbox
		'clientsecretsandbox' => array(
			'FriendlyName' => $opt_num++.'- Client_Secret Desenvolvimento<span class="ggnb_required">*</span>',
			'Type' => 'text',
			'Size' => '60',
			'Default' => '',
			'Description' => '<span class="ggnb_required_txt">(Obrigatório)</span>',
		),
		// Testar?
		'sandbox' => array(
			'FriendlyName' => $opt_num++.'- Modo de Testes / Sandbox',
			'Type' => 'yesno',
			'Default' => 'yes',
			'Description' => 'Marque essa opção para utilizar a API Gerencianet em modo "Desenvolvimento" (modo de testes). <a style="text-decoration: underline;" href="https://docs.gerencianet.com.br/" target="_blank">Painel da API</a>.',
		),
		// Debug?
		'debug' => array(
			'FriendlyName' => $opt_num++.'- Modo Diagnóstico / <i>Debug</i>',
			'Type' => 'yesno',
			'Description' => '<span class="ggnb_optional_txt">(Opcional)</span> <span class="ggnb_required_txt">Cuidado</span>, marque essa opção para exibir na Fatura os dados gerados pela API Gerencianet e a API interna do WHMCS.<br/>Use essa funcionalidade apenas para diagnosticar erros. <a title="↗ Gofas.net" style="text-decoration:underline;" target="_blank" href="https://gofas.net/?p=7899">Tutorial para identificar e corrigir erros</a>.</b>',
		),
		// Log
		'log' => array(
			'FriendlyName' => $opt_num++.'- Salvar Logs',
			'Type' => 'yesno',
			'Default' => 'no',
			'Description' => 'Salva informações de diagnóstico em <a target="_blank" style="text-decoration: underline;" href="'.$ggnbwhmcsadminurl.'systemmodulelog.php">Utilitários > Logs > Log de Módulo</a>. Para funcionar, antes é necessário ativar o debug de módulo clicando em "Ativar Log de Debug". <a target="_blank" style="text-decoration: underline;" href="'.$ggnbwhmcsadminurl.'systemmodulelog.php">VER LOG</a>.',
		),
		// whmcs admin
		'admin' => array(
			'FriendlyName' => $opt_num++.'- Administrador do WHMCS<span class="ggnb_required">*</span>',
			'Type' => $admin_config_type,
			'Size' => '10',
			'Default' => '',
			'Description' => '<span class="ggnb_required_txt">(Obrigatório)</span> Insira o nome de usuário ou ID do administrador com permissão de uso da API interna do WHMCS. Essa opção não é mais necessária em versões do WHMCS > 7.2, recomendamos atualizar seu WHMCS para a versão mais recente.',
		),

		// Tarifas
		'fee' => array(
            'FriendlyName'      => $opt_num++.'- Valor da tarifa por Boleto',
            'Type'              => 'text',
			'Size' => '10',
            'Description'       => '<span class="ggnb_optional_txt">(Opcional)</span> Insira o valor da comissão paga à Gerencianet a cada Boleto com pagamento confirmado. Essa informação servirá para calcular e preencher o campo "Taxas" (fee) da lista de transações do WHMCS. Use ponto(.) para separar casas decimais, ex.: 1.5',
        ),

		// valor mínimo
		'minimunamount' => array(
			'FriendlyName' => $opt_num++.'- Valor mínimo do Boleto',
			'Type' => 'text',
			'Size' => '10',
			'Default' => '5',
			'Description' => '<span class="ggnb_optional_txt">(Opcional)</span> Insira o valor mínimo da fatura para permitir pagamento via Boleto, 5 equivale à R$ 5,00. O valor mínimo padrão é R$5,00.',
		),

		/*
		 * Separador 2
		 * Ações Automatizadas
		 *
		*/

		'separator_2' => array(
			'Description' => '
			<div class="ggnb_separator">
				<h4>Ações Automatizadas</h4>
			</div>',
		),

		// Billet on email
		'billetonemail' => ggnb_billet_on_email($opt_num++),

		// Replace Invoice link for Billet link on email
		'linkbilletonemail' => ggnb_link_billet_opt($opt_num++),

		// Altera a data ou cancela e cria um novo boleto
		'cancelbilletoncancelinvoice' => ggnb_cancel_billet_opt($opt_num++),

		// Altera a data ou cancela e cria um novo boleto
		'cancelbillet' => array(
			'FriendlyName' => $opt_num++.'- Cancelar Boleto Vencido',
			'Type' => 'yesno',
			'Description' => '<span class="ggnb_optional_txt">(Opcional)</span> Cancela o Boleto gerado anteriormente pela Fatura antes de gerar novo Boleto/segunda via. Sem essa opção definida, o módulo altera a data de vencimento do Boleto vencido associado à Fatura, ou gera um novo boleto mas não altera o status do Boleto anterior.',
		),

		// Dias + vencimento
		'diasparavencimento' => array(
            'FriendlyName'      => $opt_num++.'- Dias adicionais para nova data de vencimento de Boletos',
            'Type'              => 'text',
			'Size'				=> '10',
            'Description'       => '<span class="ggnb_optional_txt">(Opcional)</span> Número de dias que serão somados a data do vencimento do Boleto ao gerar segunda via ou atualizar um boleto vencido. Essa opção aplica-se apenas a Faturas vencidas, faturas que ainda não venceram sempre irão gerar Boletos com a mesma data de vencimento da Fatura.',
        ),

		// Notificar admin sobre erros
		'emailonerror' => array(
			'FriendlyName' => $opt_num++.'- Notificar admins do WHMCS sobre erros',
			'Type'          => 'dropdown',
			'Default' 		=> '0',
            'Options'       => $tblticketdepartments,
			'Description' => 'Escolha o departamento de suporte que receberá notificação por email quando houver erros ao gerar o boleto. Esse recurso possibilita uma tomada de ação antes que o cliente contacte o suporte ou desista da compra, como por exemplo, quando o boleto não é gerado por um erro de cadastro do cliente.',
		),
		/*
		 * Separador 3
		 * Campos Personalizados dos Clientes
		 *
		*/
		'separator_3' => array(
			'Description' => '
			<div class="ggnb_separator">
				<h4>Campos Personalizados</h4>
				<p>Certifique-se de ter criado e configurado corretamente os <i title="WHMCS > Opções > Campos personaliz. Clientes" style="cursor: help;">Campos Personalizados de Clientes</i> que se aplicam às regras de negócio da sua empresa. Observações importantes:<br>
				<ul>
					<li>Apenas o campo CPF é obrigatório;<br></li>
					<li>Você pode configurar apenas um campo personalizado para CPF e CNPJ, ou dois campos, um para cada tipo de documento;<br></li>
					<li>O módulo detecta automaticamente os campos CPF e CNPJ pelo nome do campo personalizado.<br></li>
				</ul>
				</p>
			</div>',
		),

		// customfield Desconto- Tipo
		'custom_discount_type' => array(
			'FriendlyName' => $opt_num++.'- Tipo de Desconto Personalizado',
			'Type'          => 'dropdown',
			'Default' 		=> '0',
            'Options'       => $customfields,
			'Description' => 'Selecione o Campo Personalizado de Clientes que define o tipo de desconto personalizado em R$(Reais) e %(Porcentagem). <a style="text-decoration: underline;" href="https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2017/04/WHMCS_-_Campos_Personalizados_dos_Clientes.png" target="_blank">Veja aqui</a> como configurar os <i title="WHMCS > Opções > Campos personaliz. Clientes" style="cursor: help;">Campos Personalizados de Clientes</i>.',
		),
		// customfield Desconto- Valor
		'custom_discount_value' => array(
			'FriendlyName' => $opt_num++.'- Valor do Desconto Personalizado',
			'Type'          => 'dropdown',
			'Default' 		=> '0',
            'Options'       => $customfields,
			'Description' => 'Escolha o <i title="WHMCS > Opções > Campos personaliz. Clientes" style="cursor: help;">Campo Personalizado de Clientes</i> usado para aplicar descontos diferenciados para clientes específicos. Formato: Decimal, separado por ponto. Maior ou igual a 0.00 e menor que o valor da cobrança.',
		),

		/*
		 * Separador 4
		 * Descontos e Acréscimos
		 *
		*/

		'separator_4' => array(
			'Description' => '
			<div class="ggnb_separator">
				<h4>Descontos e Acréscimos</h4>
			</div>',
		),

		// Desconto ou taxa
		'descontooutaxa'      => array(
            'FriendlyName'  => $opt_num++.'- Desconto ou Taxa adicional',
            'Type'          => 'dropdown',
            'Options'       => array(
                '1'         => 'Desconto',
                '2'         => 'Taxa adicional',
            ),
            'Description'   => '<span class="ggnb_optional_txt">(Opcional)</span> Escolha de deseja oferecer desconto ou acrescentar taxa para pagamentos via Boleto.',
        ),
		// % ou R$
		'tipodescontooutaxa'      => array(
            'FriendlyName'  => $opt_num++.'- Tipo de desconto/taxa',
            'Type'          => 'dropdown',
            'Options'       => array(
                '1'         => '% (Porcentagem)',
                '2'         => 'R$ (Reais)',
            ),
            'Description'   => '<span class="ggnb_optional_txt">(Opcional)</span> Escolha se o desconto ou taxa será em Porcentagem ou em Reais',
        ),

		// valor do desconto/taxa
		'valordescontooutaxa' => array(
			'FriendlyName' => $opt_num++.'- Valor do Desconto ou Taxa',
			'Type' => 'text',
			'Size' => '10',
			'Default' => '',
			'Description' => '<span class="ggnb_optional_txt">(Opcional)</span> Valor que será abatido ou acrescentado ao valor total das faturas.',
		),

		// dias antes do vencimento para aplicar desconto
		'diasantesvencadddesconto' => array(
			'FriendlyName' => $opt_num++.'- Validade do desconto',
			'Type' => 'text',
			'Size' => '10',
			'Default' => '0',
			'Description' => '<span class="ggnb_optional_txt">(Opcional)</span> Defina o máximo de dias antes do vencimento para aplicar desconto.<br>
			- Deixe em branco para aplicar desconto mesmo após o vencimento;<br>
			- Insira 0 (zero) para aplicar desconto a Boletos gerados até a data de vencimento da Fatura;<br>
			- Insira de 1 a X, sendo X = <span style="cursor: help;" title="Opções > Configurações Gerais > Configurações de Automação">ao nº de dias antes do vencimento, que as Faturas são geradas</span> para aplicar desconto apenas a Boletos gerados entre 1 e X dias antes da data de vencimento da Fatura.<br>
			Ao ativar essa opção a data de vencimento do Boleto será igual a data de vencimento da Fatura - (menos) o nº de dias definido nesse campo. Será adicionada a instrução ao caixa "não aceitar pagamento após o vencimento".',
		),

		// Multa por atraso
		'multa' => array(
			'FriendlyName' => $opt_num++.'- Multa após o vencimento',
			'Type' => 'text',
			'Size' => '10',
			'Default' => '',
			'Description' => '<span class="ggnb_optional_txt">(Opcional)</span> Multa cobrada após o vencimento (máximo 10%). Use ponto(.) para separar casas decimais, ex.: 1.5',
		),
		// Multa por atraso
		'juros' => array(
			'FriendlyName' => $opt_num++.'- Juros após o vencimento',
			'Type' => 'text',
			'Size' => '10',
			'Default' => '',
			'Description' => '<span class="ggnb_optional_txt">(Opcional)</span> Juros por dia cobrados após o vencimento (Mínimo de 0.001 e máximo de 0.33). Use ponto(.) para separar casas decimais.',
		),

		/*
		 * Separador 5
		 * Exibição da Fatura e do Boleto
		 *
		*/

		'separator_5' => array(
			'Description' => '
			<div class="ggnb_separator">
				<h4>Exibição da Fatura e do Boleto</h4>
			</div>',
		),
		// Linha digitável
		'showbarcode' => array(
			'FriendlyName' => $opt_num++.'- Exibir linha digitável',
			'Type' => 'yesno',
			'Default' => 'yes',
			'Description' => '<span class="ggnb_optional_txt">(Opcional)</span> Exibe a linha digitável/código de barras do Boleto, abaixo do botão "visualizar boleto".',
		),
		// Data de vencimento
		'showduedate' => array(
			'FriendlyName' => $opt_num++.'- Exibir data de Vencimento',
			'Type' => 'yesno',
			'Default' => 'yes',
			'Description' => '<span class="ggnb_optional_txt">(Opcional)</span> Exibe a data de vencimento do Boleto na fatura, abaixo do botão "visualizar boleto".',
		),
		// Exibir informação sobre Desconto / Taxa na fatura
		'exibedescontooutaxa' => array(
			'FriendlyName' => $opt_num++.'- Exibir Desconto / Taxa na fatura',
			'Type' => 'yesno',
			'Default' => 'yes',
			'Description' => '<span class="ggnb_optional_txt">(Opcional)</span> Assinale se desejar informar sobre o Desconto ou Taxa na fatura.',
		),

		// Redirecionar para o link do boleto?
		'redirecttobillet' => array(
			'FriendlyName' => $opt_num++.'- Redirecionar para o Boleto',
			'Type' => 'yesno',
			'Description' => '<span class="ggnb_optional_txt">(Opcional)</span> Redireciona o cliente diretamente para o URL do boleto ao acessar a fatura. Adicione &redirectToBillet=false ao URL da fatura para desativar em acessos específicos, exemplo: https://whmcs.gofas.net/viewinvoice.php?id=4800<b>&redirectToBillet=false</b>. Nos templates de email a mergetag ficaria desta forma: <b>{$invoice_link}&redirectToBillet=false</b>',
		),

		// Botão "Visualizar boleto"
		'paybutton' => array(
			'FriendlyName' => $opt_num++.'- Imagem do botão "Visualizar Boleto"',
			'Type' => 'text',
			'Size' => '90',
			'Default' => '',
			'Description' => '<span class="ggnb_optional_txt">(Opcional)</span><br/>Insira o URL da imagem que será usada como botão "Visualizar Boleto" (tamanho recomendado: 160x43px).',
		),
		// Message
		'message' => array(
			'FriendlyName' => $opt_num++.'- Mensagem ao cliente',
			'Type' => 'text',
			'Size' => '60',
			'Default' => null,
			'Description' => '<span class="ggnb_optional_txt">(Opcional)</span> Inclui no boleto uma mensagem personalizada para o cliente.',
		),
		/*
		 * Separador 6
		 * Instruções do Boleto
		 *
		*/

		'separator_6' => array(
			'Description' => '
			<div class="ggnb_separator">
				<h4>Instruções do Boleto</h4>
				<p>O texto nas linhas de instrução do Boleto devem ser direcionadas ao caixa do banco, nunca para mensagens direcionadas ao cliente, para essa funcionalidade existe o campo acima "Mensagem ao cliente".</p>
				<p>As instruções do Boleto configuradas abaixo serão ignoradas e substituídas pelas instruções padrão da API Gerencianet, quando multa e/ou juros estiverem ativos.</p>
			</div>',
		),
		// Instruções 1
		'instruction1' => array(
			'FriendlyName' => $opt_num++.'- 1ª Instrução do boleto',
			'Type' => 'text',
			'Size' => '60',
			'Default' => 'Após vencimento aceitar pagamento somente no banco emissor.',
			'Description' => '<span class="ggnb_optional_txt">(Opcional)</span> Insira a 1ª linha de instruções do Boleto.',
		),
		// Instruções 2
		'instruction2' => array(
			'FriendlyName' => $opt_num++.'- 2ª Instrução do boleto',
			'Type' => 'text',
			'Size' => '60',
			'Default' => 'Não cobrar juros após o vencimento.',
			'Description' => '<span class="ggnb_optional_txt">(Opcional)</span> Insira a 2ª linha de instruções do Boleto.',
		),
		// Instruções 3
		'instruction3' => array(
			'FriendlyName' => $opt_num++.'- 3ª Instrução do boleto',
			'Type' => 'text',
			'Size' => '60',
			'Default' => 'Não cobrar multa após o vencimento.',
			'Description' => '<span class="ggnb_optional_txt">(Opcional)</span> Insira a 3ª linha de instruções do Boleto.',
		),
		// Instruções 4
		'instruction4' => array(
			'FriendlyName' => $opt_num++.'- 4ª Instrução do boleto',
			'Type' => 'text',
			'Size' => '60',
			'Default' => 'Aceitar apenas pagamento em dinheiro.',
			'Description' => '<span class="ggnb_optional_txt">(Opcional)</span> Insira a 4ª linha de instruções do Boleto.',
		),


	);

	$footer = array('footer' => array(
			'Description' => '<div class="ggnb_section">
			<p>&copy; 2016 - '.date('Y').' <a style="text-decoration:underline;" target="_blank" title="↗ Gofas.net" href="https://gofas.net">Gofas.net</a> | <a style="text-decoration:underline;" target="_blank" title="↗ Gofas.net" href="https://gofas.net/blog/">Versão 2.2.2</a> | <a  style="text-decoration:underline;"target="_blank" title="↗ Documentação" href="https://gofas.net/?p=7893">Documentação</a> | <a style="text-decoration:underline;" target="_blank" title="↗ Fórum de Suporte Gratuito" href="https://gofas.net/?p=7856">Fórum de Suporte Gratuito</a>.</p>
			<p style="font-size: 11px;">
			Ao utilizar esse módulo você concorda com nosso <a style="text-decoration:underline;" target="_blank" title="↗ Contrato de licença de uso de software" href="https://gofas.net?p=9340">contrato de licença de uso de software</a>.
			</p>
			'.$available_update_message.'
			</div>',
		),);
	$extra_config = ggnb_custom_config($opt_num++,$customfields);
	if (is_array($extra_config)){
		$renderize = array_merge($options_to_display,$extra_config,$footer);
	}
	else {
		$renderize = array_merge($options_to_display,$footer);
	}


	// Debug
	//echo '<pre>',print_r($file_name),'</pre>';
	//echo '<pre>',print_r($extra_config),'</pre>';

	// Renderize Options
	return $renderize;
}