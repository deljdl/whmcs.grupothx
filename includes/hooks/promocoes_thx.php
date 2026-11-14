
<?php

use WHMCS\Database\Capsule;

require_once ROOTDIR . '/tarifasTHX.php';

add_hook('EmailPreSend', 1, function ($vars) {

	$clienteId = (int) ($vars['mergefields']['client_id'] ?? 0);
	

	$campanha = Capsule::table('mod_promocoes_thx')
		->where('ativo', 1)
		->inRandomOrder()
		->first();

	if (!$campanha) {
		return [];
	}

	$token = criptX($clienteId);

	$url = HTTP_WHMCS . '/thx/promocao/?promo=' . $campanha->id . '&cliente=' . urlencode($token);
	$urlImg = HTTP_WHMCS . '/thx/promocao/' . $campanha->imagem;

	$url = getUrlCurta($url);



	
	$html = '
	<a href="' . $url . '">
		<img style="width:100%; max-width:700px;" src="' . $urlImg . '"  alt="' . $campanha->titulo . '" title="' . $campanha->titulo . '" />
	</a>';

	echo $html;
	die('<pre>'.print_r($vars, true).'</pre>');
    return [
        'promocoes_thx_html' => $html
    ];
});
