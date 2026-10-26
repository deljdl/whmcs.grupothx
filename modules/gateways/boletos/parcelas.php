<? if(isset($_POST['parcelamento'])) { ?>
<div class="sisleft" style="width:800px; height:auto; border:none; margin:20px;">
<div id="imprimivel" style="background-color:#FFFFFF; float:left; margin-left:25px; width:670px;">
<div style="width:670px; border:solid #000000 2px; padding:20px; background-color:#FFFFFF;">








<?php
function mostrar_boleto(){
// DADOS DO BOLETO PARA O SEU CLIENTE
$dias_de_prazo_para_pagamento = 3;
$taxa_boleto = 0;
//$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400)); // Prazo de X dias OU informe data: "13/04/2006";
$data_venc = 

$data_matricula=list($dia, $mes, $ano) = explode('/', $_SESSION['candidator762']['data_matricula']);


$data_venc = date('d/m/Y',mktime(0,0,0,$mes,$dia + $dias_de_prazo_para_pagamento,$ano));

$valor_cobrado = "1582,43"; // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
$valor_cobrado = str_replace(",", ".",$valor_cobrado);
$valor_boleto=number_format($valor_cobrado+$taxa_boleto, 2, ',', '');
$tabela=$_SESSION['candidator762']['tabela']=="Cadastro"?1:2;
$dadosboleto["nosso_numero"] = '190'.$tabela.$_SESSION['candidator762']['idcadastro']; // Nosso numero - REGRA: Máximo de 8 caracteres!
$dadosboleto["numero_documento"] = '1/12';	// Num do pedido ou nosso numero
$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
$dadosboleto["valor_boleto"] = $valor_boleto; // Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

// DADOS DO SEU CLIENTE
$dadosboleto["sacado"] = $_SESSION['candidator762']['nome'];
$dadosboleto["endereco1"] = $_SESSION['candidator762']['endres'];
$dadosboleto["endereco2"] = "{$_SESSION['candidator762']['cidade']} - {$_SESSION['candidator762']['estado']} - CEP: {$_SESSION['candidator762']['cep']}";

// INFORMACOES PARA O CLIENTE
$dadosboleto["demonstrativo1"] = "{$_SESSION['candidator762']['idcadastro']} - {$_SESSION['candidator762']['nome']}";
$dadosboleto["demonstrativo2"] = "(+) Turma MEDICINA - TURMA 4 - Matricula 2013 = R$ {$valor_cobrado}";
$dadosboleto["demonstrativo3"] = "";
$dadosboleto["instrucoes1"] = "";
$dadosboleto["instrucoes2"] = "";
$dadosboleto["instrucoes3"] = "(=) Em caso de dúvidas entre em contato conosco: financeiro@isped.com.br";
$dadosboleto["instrucoes4"] = "(=)Sr. Caixa, não receber apos cinco dias do vencimento<br>Refere-se ao pagamento da taxa de matricula do curso de medicina.";

// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
$dadosboleto["quantidade"] = "";
$dadosboleto["valor_unitario"] = "";
$dadosboleto["aceite"] = "N";	
$dadosboleto["especie"] = "R$";
$dadosboleto["especie_doc"] = "";


// ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //


// DADOS DA SUA CONTA - ITAÚ
$dadosboleto["agencia"] = "1664"; // Num da agencia, sem digito
$dadosboleto["conta"] = "04440";	// Num da conta, sem digito
$dadosboleto["conta_dv"] = "9"; // Digito do Num da conta

// DADOS PERSONALIZADOS - ITAÚ
$dadosboleto["carteira"] = "175"; // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157

// SEUS DADOS
$dadosboleto["identificacao"] = "ISPED Instituto Sul-Americano de Pesquisa e Desenvolvimento";
$dadosboleto["cpf_cnpj"] = "12.295.018/0001-47";
$dadosboleto["endereco"] = "Rua Madre de Deus, 499 – 1°andar Móoca –  – CEP 03119-000 ";
$dadosboleto["cidade_uf"] = "São Paulo/SP";
$dadosboleto["cedente"] = "ISPED - CNPJ: 12.295.018/0001-47";

// NÃO ALTERAR!
include("include/funcoes_itau.php");
include("include/layout_itau.php");
}


mostrar_boleto();

?>












</div>

<input type="button"   style="width:auto;
height:24px;
display:inline-block;
background-color:#900;
text-align:center;
font-size:16px;
color:#FFF;
padding-left:10px;
padding-right:10px;
padding-top:0px;
font-family:arial;
cursor:pointer;
border-radius: 5px; 
-moz-border-radius: 5px; 
-webkit-border-radius: 5px; float:left; margin-top:40px;" value="Imprimir" onclick="printDiv()" />






 
 
 
  <script>
 function printDiv() {
    var oPrint, oJan;
    oPrint = window.document.getElementById('imprimivel').innerHTML;
    oJan = window.open('tela');
    oJan.document.write(oPrint);
    oJan.window.print();
       oJan.document.close();
       oJan.focus();
}
 
 </script>

</div>

<? } ?>
</div>


</body>
</html>
