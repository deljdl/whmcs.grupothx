<?php header("Content-Type: text/html; charset=ISO-8859-1",true);?>
<? 
ob_start();
session_start();
require_once '../../classes/funcoes.php';
conectar();
$list_resultados=array('','Aprovado','Ausente','Aguardando 2° Chamada','Desclassificado');
?>
<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="../../css/stylejdl.css" rel="stylesheet" type="text/css">
<link href="../../css/style.css" rel="stylesheet" type="text/css">