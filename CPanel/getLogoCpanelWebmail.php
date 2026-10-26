<?
if(isset($_GET['refresh']) && $_GET['refresh'] == 'r76247wd')
{
	$host ='localhost';
	$name ='xpainel_xpainel';
	$user ='xpainel_jdl';
	$pass ='t5y6u7i8xscdvfbg';
	$type ='mysql';
	$port =3306;
	
	$conn = mysql_connect('xpainel.com',$user, $pass);
	$sel = mysql_select_db($name);
	$clientes = mysql_query("SELECT conta_logomarca, host FROM xpainel_xpainel.xpainel_conta");
	while($cliente = mysql_fetch_array($clientes))
	{
	
		$extencao = explode('.',$cliente['conta_logomarca']);
		copy($cliente['conta_logomarca'], $cliente['host'].'.'.end($extencao));	
	}
}


$imagem='default.png';
if(isset($_GET['dominio']))
{
	$_GET['dominio'] = str_replace('webmail.','',$_GET['dominio']);
	$ext = array('.png', '.jpg', '.gif');
	foreach($ext as $f)
	{
		if(file_exists($_GET['dominio'].$f))
		{
			$imagem=$_GET['dominio'].$f;
			break;
		}
	}
}
header('Content-Type: image/png');
readfile($imagem);
