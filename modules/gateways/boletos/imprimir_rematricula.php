<? require_once("topoboleto.php");
if(!isset($_SESSION['rematricula']))
ir_para('../../rematricula/');
conectar();
?>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<script language="Javascript" src="../js/funcoes.js"></script>
<title>ISPED Instituto Sul-Americano de Pesquisa e Desenvolvimento</title>
<meta name="ROBOTS" content="ALL" />
<meta name="Description" content="Doutorado em ciências juridicas exvlusivas para brasileiros" />
<meta name="Keywords" content="doutorado, mestrado, gradua??o, intercâmbio, cursos na argentina, direito, ciencias juridicas, universidade de moron" />

<!--[if lte IE 8]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->


<link href="../css/style.css" rel="stylesheet" type="text/css">

<script type="text/javascript" language="javascript" src="../js/jquery.js"></script>
<script type="text/javascript" language="javascript" src="../js/faq.js"></script>
<script type="text/javascript" language="javascript" src="../js/menui.js"></script>
</head>

<body>

<div id="principal">

<div id="sispanel">      
<div class="sistop">
<div class="redbotac" style="cursor:pointer; width:250px;">Central de Rematrículas
  </div>
  
  <div class="redbotacsair" onClick="location='http://www.isped.com.br/rematricula/index.php'" style="cursor:pointer;">Sair
  </div>
  </div>
  <span class="sisredtit">BOLETO DE REMATRÍCULA</span>
    <span class="sisazutit"><img src="../img/um.jpg"></span><br>
<div class="redbar"></div>

<div class="sisleft" style="width:800px; height:auto; border:none; margin:20px;">
<? if($_SESSION['rematricula']['documentacao_pendente']!='') { ?>
<span style="color:#9F1512; font-size:18px;">Lembramos a você estudante, que os documentos abaixo relacionados constam como pendentes em nosso sistema.</span><br>
<p style="text-align:justify; font-size:18px; font-weight:200; font-family:Verdana, Geneva, sans-serif;"><?=nl2br($_SESSION['rematricula']['documentacao_pendente'])?></p>
<span style="color:#9F1512; font-size:18px;">É importante a regularização dessa documentação realizando a entrega até antes do seu retorno ao curso de medicina.</p>
<? } ?>
<div id="imprimivel" style="background-color:#FFFFFF; float:left; margin-left:25px; width:670px;">
<div style="width:670px; border:solid #000000 2px; padding:20px; background-color:#FFFFFF;">








<?php
function mostrar_boleto($parcelas,$parcela){
	
// DADOS DO BOLETO PARA O SEU CLIENTE
$dias_de_prazo_para_pagamento = 3;



//$data_venc = date('d/m/Y',mktime(0,0,0,$GLOBALS['mes_matricula'],$GLOBALS['dia_matricula'] + $dias_de_prazo_para_pagamento,$GLOBALS['ano_matricula']));

$data_venc=mysql_fetch_assoc(mysql_query("SELECT vencimento_rematricula FROM gerenciaveis"));
$data_venc=$data_venc['vencimento_rematricula'];

$taxa_boleto = $_SESSION['rematricula']['saldo'];



$valor_cobrado = number_format(($_SESSION['rematricula']['valor']), 2, ',', ''); // 1582,43 Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
$valor_cobrado = str_replace(",", ".",$valor_cobrado);
$valor_boleto=number_format($valor_cobrado+$taxa_boleto, 2, ',', '');
$tabela=$_SESSION['candidator762']['tabela']=="Cadastro"?1:2;
$dadosboleto["nosso_numero"] = '1913'.$_SESSION['rematricula']['idcadastro']; // Nosso numero - REGRA: Máximo de 8 caracteres!
$dadosboleto["numero_documento"] = '1/1';	// Num do pedido ou nosso numero
$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
$dadosboleto["valor_boleto"] = $valor_boleto; // Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

// DADOS DO SEU CLIENTE
$dadosboleto["sacado"] = $_SESSION['rematricula']['nome'];
$dadosboleto["endereco1"] = $_SESSION['rematricula']['endres'];
$dadosboleto["endereco2"] = "{$_SESSION['rematricula']['cidade']} - {$_SESSION['rematricula']['estado']} - CEP: {$_SESSION['rematricula']['cep']}";

// INFORMACOES PARA O CLIENTE
$dadosboleto["demonstrativo1"] = "{$_SESSION['rematricula']['matricula']} - {$_SESSION['rematricula']['nome']}";
$dadosboleto["demonstrativo2"] = $_SESSION['rematricula']['saldo']>0?"(=) Débito apurado durante o ano letivo anterior R$ ".number_format(($_SESSION['rematricula']['saldo']), 2, ',', ''):'';
$dadosboleto["demonstrativo3"] = "(=) Pagamento da Rematrícula 2013 R$ ".number_format(($_SESSION['rematricula']['valor']), 2, ',', '');
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

$_POST['parcela']=isset($_POST['parcela'])?$_POST['parcela']:1;
mostrar_boleto($_POST['parcelas'], $_POST['parcela']);

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

</div>


</body>
</html>
