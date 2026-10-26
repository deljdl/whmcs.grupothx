<?php
define('TK', 'gtfdrsgchgcgfhxsd543w54e543drdc4');
//raiz /thx ondse estou
require_once('init.php');
require_once('tarifasTHX.php');
use WHMCS\Database\Capsule;


if(! isset($_REQUEST['tk']) || $_REQUEST['tk'] != TK)
{
	die(json_encode(null));
}


function sendRemoteWhats()
{
	enviaWhatsApp($_REQUEST['whats'] ,$_REQUEST['mensagem']);
}

function GetClientsDetailsx()
{
	if(empty($_GET['data']))
	{
		die('No Thanks :'.__LINE__.')');
	}
	$user = $_GET['data'];
	$user = trim($user);
	$user = addslashes($user);
	$user = htmlentities($user);



	$data = Capsule::table('tblclients')
		->join('tblcustomfieldsvalues', 'tblclients.id', '=', 'tblcustomfieldsvalues.relid')
		->where(function($query) use ($user) { // Use $user dentro da função
			$query->whereRaw("CONCAT(tblclients.firstname, ' ', lastname) = ?", [$user])
				->orWhere('tblcustomfieldsvalues.value', '=', $user);
		})
		->where('tblcustomfieldsvalues.fieldid', '=', 2) // Especifica o custom field ID
		->first();
	die(json_encode($data));
 }

function GetInvoicesx()
{
	$command = 'GetInvoices';
	$postData = array(
	    'userid' => $_REQUEST['id'],
	    'status' => 'Unpaid',
	);
	$adminUsername = 'deljdl'; // Optional for WHMCS 7.2 and later
	die(json_encode(localAPI($command, $postData, $adminUsername)));
 }

 function getDadosPagamento()
 {
 	foreach (Capsule::table('gofasgerencianetboleto')->where("invoice_id", '=', $_REQUEST['id'])->get() as $gorfas)
	{

			$ret=
			'Valor da Fatura:
			*R$ '.(number_format(($gorfas->total/100),  2, ',', '.' )).'*

			Vencimento do Boleto
			*'.date('d/m/Y', strtotime($gorfas->expire_at)).'*

			Linha Digitável do Boleto
			*'.$gorfas->barcode.'*

			Imprimir Boleto *'.$gorfas->link.'*

			_Dados para Transferência Bancária_

			*Chave Pix:*  CNPJ: 36.726.535/0001-91

			*Nome:* Grupo THX
			*CNPJ:* 36.726.535/0001-91 
			*Banco:* 260 - Nu Pagamentos S.A (NuBank)
			*Agência:* 0001
			*Conta:* 50920344-7

			*Valor:* '.(number_format(($gorfas->total/100),  2, ',', '.' ));

	}

	echo $ret;
 }

function GetProductsx()
{
	$ret = 'Por favor digite o número correspondente ao serviço que deseja contratar:\n\n';
	foreach (Capsule::table('tblproducts')->where("gid", '=', $_REQUEST['gid'])->get() as $servico)
	{

		if($servico->hidden == 0)
		{
			$ret.='*['.$servico->id.']* '.$servico->name."\n";
		}

	}

	echo $ret;
}

function GetProductx()
{
	$ret = 'Contratação de serviço:\n\n';
	foreach (Capsule::table('tblproducts')->where("id", '=', $_REQUEST['pid'])->get() as $servico)
	{

		if($servico->hidden == 0)
		{
			$ret.='
			*Serviço:*
			'.$servico->name.'

			*Descrição*:
			'.$servico->description.'

			*Clique no link abaixo para contratar:*
			*https://grupothx.com/thx/cart.php?a=add&pid='.$servico->id."*";
		}

	}

	echo $ret;
}

function GetProductsGroupsx()
{
	$ret = 'Por favor digite o número correspondente ao serviço que deseja contratar:\n\n';
	foreach (Capsule::table('tblproductgroups')->where("hidden", '=', 0)->get() as $group)
	{

			$ret.=
			'*['.$group->id.']* '.$group->name."\n";

	}
	echo $ret;
}

if(isset($_REQUEST['function']) && $_REQUEST['function'] == 'GetProductsGroupsx')
{
	GetProductsGroupsx();
}

if(isset($_REQUEST['function']) && $_REQUEST['function'] == 'GetProductx')
{
	GetProductx();
}

if(isset($_REQUEST['function']) && $_REQUEST['function'] == 'GetProductsx')
{
	GetProductsx();
}

if(isset($_REQUEST['function']) && $_REQUEST['function'] == 'getDadosPagamento')
{
	getDadosPagamento();
}

if(isset($_REQUEST['function']) && $_REQUEST['function'] == 'GetClientsDetailsx')
{
	GetClientsDetailsx();
}

if(isset($_REQUEST['function']) && $_REQUEST['function'] == 'sendRemoteWhats')
{
	sendRemoteWhats();
}

if(isset($_REQUEST['function']) && $_REQUEST['function'] == 'GetInvoicesx')
{
	GetInvoicesx();
}