<?php
/**
 * Include custom code in template head
 * © 2017 gofas.net
 *
*/
// Favicon in clientarea
add_hook( 'ClientAreaHeadOutput', 1, function( $vars ) {
	
	$favicon = array();
	$favicon['favicon']	= '<!-- Favicons-->
<link rel="shortcut icon" href="https://grupothx.com/img/favicon.ico" type="image/x-icon"/>
<link rel="apple-touch-icon" type="image/x-icon" href="https://grupothx.com/img/apple-touch-icon-57x57-precomposed.png">
<link rel="apple-touch-icon" type="image/x-icon" sizes="72x72" href="https://grupothx.com/img/apple-touch-icon-72x72-precomposed.png">
<link rel="apple-touch-icon" type="image/x-icon" sizes="114x114" href="https://grupothx.com/img/apple-touch-icon-114x114-precomposed.png">
<link rel="apple-touch-icon" type="image/x-icon" sizes="144x144" href="https://grupothx.com/img/apple-touch-icon-144x144-precomposed.png">
<script>console.log(\'Icons by X-Painel Loaded\')</script>
<!--Icons by X-Painel-->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/Grupo-THX/xpainel-assets/icons.css" />
<!--End Icons by X-Painel-->
';

	return $favicon['favicon'];
		
});
// Favicon in admin panel
add_hook('AdminAreaHeadOutput', 1, function($vars) {
    $favicon = array();
	$favicon['gofas_admin_favicon']	= '<!-- Favicons-->
<link rel="shortcut icon" href="https://grupothx.com/img/favicon.ico" type="image/x-icon"/>
<link rel="apple-touch-icon" type="image/x-icon" href="https://grupothx.com/img/apple-touch-icon-57x57-precomposed.png">
<link rel="apple-touch-icon" type="image/x-icon" sizes="72x72" href="https://grupothx.com/img/apple-touch-icon-72x72-precomposed.png">
<link rel="apple-touch-icon" type="image/x-icon" sizes="114x114" href="https://grupothx.com/img/apple-touch-icon-114x114-precomposed.png">
<link rel="apple-touch-icon" type="image/x-icon" sizes="144x144" href="https://grupothx.com/img/apple-touch-icon-144x144-precomposed.png">
<script>console.log(\'Icons by X-Painel Loaded\')</script>
<!--Icons by X-Painel-->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/Grupo-THX/xpainel-assets/icons.css" />
<!--End Icons by X-Painel-->
';

	return $favicon['gofas_admin_favicon'];

});