<?php
/**
 * Módulo Gerencinet Boleto para WHMCS
 * @author		Mauricio Gofas | gofas.net
 * @see			https://gofas.net/?p=7893
 * @copyright	2017 https://gofas.net
 * @license		https://gofas.net?p=9340
 * @support		https://gofas.net/?p=7856
 * @version		2.2.1
 */

if(!defined('WHMCS')) { die('Esse arquivo não pode ser acessado diretamente'); }

// CSS da fatura
$css		= '<style type="text/css">a, a:hover {cursor: pointer;}.ggnbp {font-size:12px;margin: 0;}.ggnbspan{font-size:12px;}span.ggnberror {color: red;}';
$css		.= '
	.debug {
		padding:5px;
	}
	.debug .ok {
		color:#5cb85c;
		font-weight: 600;
	}
	.debug a,
	.debug p a {
		text-decoration: underline;
	}
	.erro,
	.debug .erro {
		color: red;
	}
	#ggnbclic {
		font-size:13px; font-weight: 700;color: #458ec9;
	}
	#linDig {
		font-size: 11px; border-bottom: 1px solid #9E9E9E; max-width: 360px; margin: 0 auto; padding: 0px 0px 10px 0px;
	}
	#ggnbbilletinfo {
		text-align: right; max-width: 300px; margin: 10px auto;
	}
	div#ggnbbilletinfo p {
    	line-height: 1;
	}';
if ( !$params['paybutton'] ) {
	$css .= '
		a#ggnbviewbillet {
			background: #1992c6;
			color: #fff;
			border:none;
			padding:10px 20px;
			position: relative;
			top: 8px;
			cursor:pointer;
		}
		a#ggnbviewbillet:hover, a#ggnbviewbillet:active {
			background:#20b1ef;
			text-decoration: none;
			cursor: pointer;
		}
	</style>';
}
if($params['paybutton']){
	$css .= '</style>';
}