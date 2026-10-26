<?php
// Conexão com o banco de dados
 $conn = new mysqli(THX_DB_HOST, THX_DB_USERNAME, THX_DB_PASSWORD, THX_DB_NAME);



 function tblupgrades($upgradeid, $conn)
 {
	echo '<br />SQL'.$sql = "SELECT * FROM tblupgrades 
				WHERE tblupgrades.id = {$upgradeid}";

	$resultado = $conn->query($sql);


	// Verifica se há resultados
	if ($resultado->num_rows <= 0)
	{
		return false;
	}
	// Loop através dos resultados
	while ($row = $resultado->fetch_assoc())
	{
		return $row;
	}
 }


 function tblorders($orderid, $conn)
 {
	echo '<br />SQL'.$sql = "SELECT * FROM tblorders 
				WHERE tblorders.id = {$orderid}";

	$resultado = $conn->query($sql);


	// Verifica se há resultados
	if ($resultado->num_rows <= 0)
	{
		return false;
	}
	// Loop através dos resultados
	while ($row = $resultado->fetch_assoc())
	{
		return $row;
	}
 }


 function tblinvoices($invoiceid, $conn)
 {
	echo '<br />SQL'.$sql = "SELECT * FROM tblinvoices 
				WHERE tblinvoices.id = {$invoiceid}";

	$resultado = $conn->query($sql);


	// Verifica se há resultados
	if ($resultado->num_rows <= 0)
	{
		return false;
	}
	// Loop através dos resultados
	while ($row = $resultado->fetch_assoc())
	{
		return $row;
	}
 }

function tblinvoiceitems($invoiceid, $upgradeid,  $conn)
{
	echo '<br />SQL'.$sql = "SELECT * FROM tblinvoiceitems 
				WHERE tblinvoiceitems.invoiceid = {$invoiceid}
					AND relid = {$upgradeid}";

	$resultado = $conn->query($sql);


	// Verifica se há resultados
	if ($resultado->num_rows <= 0)
	{
		return false;
	}
	// Loop através dos resultados
	while ($row = $resultado->fetch_assoc())
	{
		return $row;
	}
}


 function tblhosting($orderid,  $conn)
 {
	echo '<br />SQL'.$sql = "SELECT * FROM tblhosting 
				WHERE tblhosting.orderid = {$orderid}";

	$resultado = $conn->query($sql);


	// Verifica se há resultados
	if ($resultado->num_rows <= 0)
	{
		return false;
	}
	// Loop através dos resultados
	while ($row = $resultado->fetch_assoc())
	{
		return $row;
	}
 }


 $upgradeid = 91;

 $tblupgrades = tblupgrades($upgradeid, $conn);
 echo 'tblupgrades<br /><pre>'.print_r($tblupgrades, 1).'<pre>';

 $tblorders = tblorders($tblupgrades['orderid'], $conn);
 echo 'tblorders<br /><pre>'.print_r($tblorders, 1).'<pre>';

 $tblinvoices = tblinvoices($tblorders['invoiceid'], $conn);
 echo 'tblinvoices<br /><pre>'.print_r($tblinvoices, 1).'<pre>';

 $tblinvoiceitems = tblinvoiceitems($tblinvoices['id'], $upgradeid , $conn);
 echo 'tblinvoiceitems<br /><pre>'.print_r($tblinvoiceitems, 1).'<pre>';

 $tblhosting = tblhosting($tblupgrades['orderid'], $conn);
 echo 'tblhosting<br /><pre>'.print_r($tblhosting, 1).'<pre>';
 