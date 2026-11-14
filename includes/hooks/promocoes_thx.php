
<?php

add_hook('EmailPreSend', 1, function ($vars) {
	
	$html = '
	<a href="https://central.grupothx.com.br/thx/promocao">
		<img style="width:100%; max-width:700px;" src="https://central.grupothx.com.br/thx/promocao/img.php"  alt="Conheça nossas Promoções" />
	</a>';

	echo $html;
	die('<pre>'.print_r($vars, true).'</pre>');
    return [
        'promocoes_thx_html' => $html
    ];
});
