<html><head><title>F2b - Retorno automático no WHMCS</title>
<style type="text/css">
.texto { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 10px; font-weight: normal; color: #000000; text-decoration: none; font-style: normal} 
.textobold { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 12px; font-weight: bold; color: #000000; text-decoration: none; font-style: normal }
.textovermelho { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 10px; font-weight: normal; color: #FF0000; text-decoration: none; font-style: normal } 
</style>
</head><body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">

<?php
# Required File Includes
include("../../../dbconnect.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

require_once("WSBillingStatus.php");
include 'ConfigF2b.php';

$numero_cobranca = '';
$numero_documento = '';
foreach ($_GET as $key => $value) {
	$value = urlencode(stripslashes($value));
	if ($key == 'num_billing') {
		$numero_cobranca = $value;
	}
	if ($key == 'num_document') {
		$numero_documento = $value;
	}
}

// Inicia a classe WSBillingStatus
$WSBillingStatus = new WSBillingStatus();

// Cria o cabeçalho SOAP
$xmlObj = $WSBillingStatus->add_node("","soap-env:Envelope");
$WSBillingStatus->add_attributes($xmlObj, array("xmlns:soap-env" => "http://schemas.xmlsoap.org/soap/envelope/") );
$xmlObj = $WSBillingStatus->add_node($xmlObj,"soap-env:Body");

// Cria  o elemento m:F2bCobranca
$xmlObjF2bCobranca = $WSBillingStatus->add_node($xmlObj,"m:F2bSituacaoCobranca");
$WSBillingStatus->add_attributes($xmlObjF2bCobranca, array("xmlns:m" => "http://www.f2b.com.br/soap/wsbillingstatus.xsd") );

// Cria o elemento mensagem
$xmlObj = $WSBillingStatus->add_node($xmlObjF2bCobranca,"mensagem");
$WSBillingStatus->add_attributes($xmlObj, array("data" => date("Y-m-d"),
                                          "numero" => date("His")));

// Cria o elemento cliente
$xmlObj = $WSBillingStatus->add_node($xmlObjF2bCobranca,"cliente");
$WSBillingStatus->add_attributes($xmlObj, array("conta" => $conta_f2b,
                                          "senha" => $senha_f2b));

// Cria o elemento cobranca
$xmlObjCobranca = $WSBillingStatus->add_node($xmlObjF2bCobranca,"cobranca");

// Deve ser enviado

// ********************** Intervalos de cobranças ************************************
$WSBillingStatus->add_attributes($xmlObjCobranca, array("numero" => $numero_cobranca));
// ***********************************************************************************
// e/ou -------------
//$WSBillingStatus->add_attributes($xmlObjCobranca, array("numero_documento" => "123456"));

$situacao = "";
$valor = "";
$tipo_pagamento = "";
$data_pagamento = "";
$taxa_pagamento = "";
$pay_method = "";

$command = "getinvoice";
$adminuser = $login_whmcs;
$values["invoiceid"] = $numero_documento;

$data = localAPI($command,$values,$adminuser);

$status = '';
if (is_array($data)) {
	foreach($data as $key => $value) {
		if ($key == 'result')
			$resultado = $data[$key];
		if ($key == 'status')
			$status = $data[$key];
		if ($key == 'paymentmethod')
			$pay_method = $data[$key];
			
	}
} else {
	$resultado = $data;
}

if ($resultado=="success") {
	if ($status=="Unpaid") {

		// envia dados
		$WSBillingStatus->send($WSBillingStatus->getXML());
		$resposta = $WSBillingStatus->resposta;
		
		if(strlen($resposta) > 0){
			// Reinicia a classe WSBillingStatus, agora com uma string XML
			$WSBillingStatus = new WSBillingStatus($resposta);

			// LOG 
			$log = $WSBillingStatus->pegaLog();
			if($log["texto"] == "OK"){
				$cobranca = $WSBillingStatus->pegaCobranca();
				
				foreach($cobranca as $key => $value){
					foreach($cobranca[$key] as $key2 => $value2){
						if ($key2 == "situacao")
							$situacao = $value2;
						if ($key2 == "valor_pago")
							$valor = $value2;
						if ($key2 == "tipo_pagamento")
							$tipo_pagamento = $value2;
						if ($key2 == "taxa_pagamento")
							$taxa_pagamento = $value2;
						if ($key2 == "pagamento") {
							$day = substr($value2,8,2);
							$month = substr($value2,5,2);
							$year = substr($value2,0,4);
							$data_pagamento = $year . "-" . $month . "-" . $day;
						}
					}
				}		
	
				if ($situacao=="Paga") {
					$command = "addinvoicepayment";
					$adminuser = $login_whmcs;
					$values["invoiceid"] = $numero_documento;
					$values["transid"] = $numero_cobranca;
					$values["amount"] = $valor;
					$values["fees"] = $taxa_pagamento;
					$values["date"] = $data_pagamento;
					$values["gateway"] = $pay_method;
					$values["noemail"] = false;

					$results = localAPI($command,$values,$adminuser);	
					
					if ($results["result"]=="success") {
						# Result was OK!
						echo "<span class='textobold'>OK!</span>";
					} else {
						# An error occured
						echo "<span class='texto'>Ocorreu um erro:</span> <span class='textovermelho'>". $results["result"]. " - " .$results["message"]."</span>";
					}
				}
			} else {
				foreach($log as $key => $value){
					echo "<span class='texto'>Erro:</span> <span class='textovermelho'>" . $value . "</span>";
				}
			}
		} else {
			echo "<span class='texto'>Erro:</span> <span class='textovermelho'>Sem resposta</span>";
		}
	} else {
		echo "<span class='textovermelho'>A cobrança possui status " . $status . "</span>";
	}
} else {
	# An error occured
	echo "<span class='texto'>Ocorreu um erro:</span> <span class='textovermelho'>". $resultado. "</span>";
}

?>
</body>
</html>