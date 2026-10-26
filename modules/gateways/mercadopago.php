<?php
/*
* Simple External URL gateway module for WHMCS
* Free - GNU license
* (c) 2011 - Nasos Liagos
* www.valuehost.com.gr
*/
function mercadopago_config() {
	$configarray = array(
	"FriendlyName" => array("Type" => "System", "Value"=>"MercadoPago"),
	"URL" => array("FriendlyName" => "URL", "Type" => "text", "Size" => "60", "Description" => "URL", ),
	"URLtext" => array("FriendlyName" => "Botão", "Type" => "text", "Size" => "60", "Description" => "Título do botão", ),
	"URLtarget" => array("FriendlyName" => "Target", "Type" => "text", "Size" => "20", "Value" => "_blank", "Description" => "Janela de destino", ),
	);
	return $configarray;
}

function mercadopago_link($params) {
	$code = '<a class="btn btn-success" style="font-size: 25px;" href ="'.$params['URL'].'?invoiceid='.$_GET['id'].'"><i class="fa fa-money" aria-hidden="true"></i> Pagar</a>';
	return $code;
}
?> 