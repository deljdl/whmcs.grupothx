
<?php

use WHMCS\Database\Capsule;

require_once ROOTDIR . '/tarifasTHX.php';

add_hook('EmailPreSend', 1, function ($vars) {
	logModuleCall('promocoes_thx', 'EmailPreSend', $vars, 'A',	'B');
	$clienteId = (int) ($vars['mergefields']['client_id'] ?? 0);



	try {
		$campanha = getPromocao();
	} catch (\Throwable $e) {
		error_log('Promoções THX: falha ao selecionar promoção para e-mail.');
		return ['promocoes_thx_html' => ''];
	}

	if (!$campanha) {
		return ['promocoes_thx_html' => ''];
	}

	$token = $clienteId;
	$token .= '|' . $campanha->id;

	$token = criptX($token);

	$url = HTTP_WHMCS . '/thx/promocao/'.$campanha->slug.'/' . urlencode($token);
	$urlImg = $campanha->urlImg;

	$url = getUrlCurta($url);

	$html = '
	<a href="' . $url . '">
		<img style="width:100%; max-width:700px;" src="' . $urlImg . '"  alt="' . $campanha->titulo . '" title="' . $campanha->titulo . '" />
	</a>';

    return [
        'promocoes_thx_html' => $html
    ];
});
