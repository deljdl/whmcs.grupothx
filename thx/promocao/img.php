<?php
ob_start();
session_start();
if(isset($_GET['site']))
{
	$imagem = rand(1,5).'.jpg';
}
else
{
	$imagem = rand(1,16).'.jpg';
}
header('Content-Type: image/png');
readfile($imagem);
