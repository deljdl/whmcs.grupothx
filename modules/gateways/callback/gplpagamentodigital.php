<?php
#------------------------------------------------------------------------------#
# Copyright 2008-2009 Jonatas Esteves                                          #
#------------------------------------------------------------------------------#
# Este arquivo é parte do programa gplpagamentodigital.php                     #
#                                                                              #
# gplpagamentodigital.php é um software livre. Você pode redistribui-lo e/ou   #
# modifica-lo dentro dos termos da Licença Pública Geral GNU como              #
# publicada pela Fundação do Software Livre (FSF) na versão 2 da Licença, ou   #
# (à seu critério) qualquer versão posterior.                                  #
#                                                                              #
# Este programa é distribuido na esperança que possa ser util, mas             #
# SEM NENHUMA GARANTIA. Sem uma garantia implicita de ADEQUAÇÂO a qualquer     #
# MERCADO ou APLICAÇÃO EM PARTICULAR. Veja a Licença Pública Geral GNU         #
# para maiores detalhes.                                                       #
#                                                                              #
# Você deve ter recebido uma cópia da Licença Pública Geral GNU junto com      #
# este programa, se não, informe o desenvolvedor pelo e-mail:                  #
# jntesteves@users.sourceforge.net                                             #
#------------------------------------------------------------------------------#

# Arquivos contendo funções da API do WHMCS.
include("../../../dbconnect.php"); # Conecta com a base de dados do WHMCS.
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

$GATEWAY = getGatewayVariables("gplpagamentodigital");
if (!$GATEWAY["type"]) die("O módulo de pagamento 'GPL Pagamento Digital' não está ativo"); # Confere se o módulo está ativo para receber pagamentos.

# Recebe as variáveis retornadas pelo Pagamento Digital
$status = $_POST['cod_status'];
$invoiceid = $_POST['id_pedido'];
$transid = $_POST['id_transacao'];
$amount = $_POST['valor_original'];
$fee = $_POST['valor_original'] - $_POST['valor_loja'];

$post = "transacao=".$_POST['id_transacao'].
		"&status=".$_POST['status'].
		"&valor_original=".$_POST['valor_original'].
		"&valor_loja=".$_POST['valor_loja'].
		"&token=".$GATEWAY['token'];
$enderecoPost = "https://www.pagamentodigital.com.br/checkout/verify/";

ob_start();
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $enderecoPost);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
curl_exec($ch);
$resposta = ob_get_contents(); # IMPORTANTE: Esse cURL verifica no Pagamento Digital se a transação é verdadeira.
ob_end_clean();
unset($post, $enderecoPost);

$invoiceid = checkCbInvoiceID($invoiceid,$GATEWAY["name"]); # Confere se a ID do pedido é válida, se não, termina o programa.
if (trim($resposta) == "VERIFICADO")
{
	if ($status == 0)
	{
		# Transação em Andamento
		logTransaction($GATEWAY["name"],$_POST,"Transação em Andamento"); # Salva informações da transação no log do WHMCS.
	}
	else if ($status == 1)
	{
		# Transação Concluída
		checkCbTransID($transid); # Confere se a ID dessa transação não está na base de dados e termina o programa se já estiver.
		addInvoicePayment($invoiceid,$transid,$amount,$fee,"gplpagamentodigital"); # Adiciona o pagamento à transação no WHMCS.
		logTransaction($GATEWAY["name"],$_POST,"Transação Concluída"); # Salva informações da transação no log do WHMCS.
	}
	else if ($status == 2)
	{
		# Transação Cancelada
		logTransaction($GATEWAY["name"],$_POST,"Transação Cancelada"); # Salva informações da transação no log do WHMCS.
	}
}
?>
