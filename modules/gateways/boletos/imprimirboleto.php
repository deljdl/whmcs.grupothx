<? require_once("topoboleto.php");
if(!isset($_SESSION['candidator762']))
ir_para('../login.php');
conectar();
if($_SESSION['candidator762']['matriculado'] != 1)
header('Location: ../matricula.php');

?>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<script language="Javascript" src="../js/funcoes.js"></script>
<title>ISPED Instituto Sul-Americano de Pesquisa e Desenvolvimento</title>
<meta name="ROBOTS" content="ALL" />
<meta name="Description" content="Doutorado em ciências juridicas exvlusivas para brasileiros" />
<meta name="Keywords" content="doutorado, mestrado, gradua??o, interc?mbio, cursos na argentina, direito, ciencias juridicas, universidade de moron" />

<!--[if lte IE 8]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->


<link href="../css/style.css" rel="stylesheet" type="text/css">

<script type="text/javascript" language="javascript" src="../js/jquery.js"></script>
<script type="text/javascript" language="javascript" src="../js/faq.js"></script>
<script type="text/javascript" language="javascript" src="../js/menui.js"></script>


<script>
function calcula(parcelas)
{
if(parcelas==1)	
document.getElementById('parcelas_maximo').innerHTML= '<option value="">Escolha a parcela</option><option value="1">Parcela Única</option>';


if(parcelas==2)	
document.getElementById('parcelas_maximo').innerHTML= '<option value="">Escolha a parcela</option><option value="1">Parcela 1</option><option value="2">Parcela 2</option>';



if(parcelas==3)	
document.getElementById('parcelas_maximo').innerHTML= '<option value="">Escolha a parcela</option><option value="1">Parcela 1</option><option value="2">Parcela 2</option><option value="3">Parcela 3</option>';
}

function verifica(){
if(document.getElementById('parcela')!='' && document.getElementById('parcelas')!='')
return true;
else
return false; 	
	
}

</script>
</head>

<body>

<div id="principal">

<div id="sispanel">      
<div class="sistop">
<div class="redbotac" style="cursor:pointer;" onClick="location='../index.php'">Home de Candidato
  </div>
  
  <div class="redbotacsair" onClick="location='http://www.isped.com.br/sistema/index.php'" style="cursor:pointer;">Sair
  </div>
  </div>
  <span class="sisredtit">BOLETO DE MATRÍCULA</span>
    <span class="sisazutit"><img src="../img/um.jpg"></span><br>
<div class="redbar"></div>
<? 

//$_SESSION['candidator762']['data_matricula']='01/11/2012';
//$_SESSION['candidator762']['data']='2012-11-30 13:11:49';
$limite='29/11/2012';


$_SESSION['candidator762']['data']=substr($_SESSION['candidator762']['data'],0,10);
$datacadastro=explode('-',$_SESSION['candidator762']['data']);
$datamatricula=explode('/',$_SESSION['candidator762']['data_matricula']);
$datalimite=explode('/',$limite);

$GLOBALS['dia_cadastro']=$datacadastro[2];
$GLOBALS['mes_cadastro']=$datacadastro[1];
$GLOBALS['ano_cadastro']=$datacadastro[0];


$GLOBALS['dia_matricula']=$datamatricula[0];
$GLOBALS['mes_matricula']=$datamatricula[1];
$GLOBALS['ano_matricula']=$datamatricula[2];

$GLOBALS['dia_limite']=$datalimite[0];
$GLOBALS['mes_limite']=$datalimite[1];
$GLOBALS['ano_limite']=$datalimite[2];


$GLOBALS['mkdatacadastro']=mktime(0,0,0,(int)$GLOBALS['mes_cadastro'],(int)$GLOBALS['dia_cadastro'],(int)$ano_cadastro);
$GLOBALS['mkdatalimite']=mktime(0,0,0,(int)$GLOBALS['mes_limite'],(int)$GLOBALS['dia_limite'],(int)$GLOBALS['ano_limite']);

//echo '<br>Data Limite=> '.$GLOBALS['dia_limite'].''.$GLOBALS['mes_limite'].''.$GLOBALS['ano_limite'];

//echo '<br>Data Matricula=> '.$GLOBALS['dia_matricula'].''.$GLOBALS['mes_matricula'].''.$GLOBALS['ano_matricula'];;

//echo '<br>Data Cadastro=> '.$GLOBALS['dia_cadastro'].''.$GLOBALS['mes_cadastro'].''.$ano_cadastro;

/*
if($_SESSION['candidator762']['cpf']=='11310080810' || ($GLOBALS['mkdatalimite'] >= $GLOBALS['mkdatacadastro'] && $_SESSION['candidator762']['cpf']!='07737803602')) { // <<<<   esporadico?> 
<form method="post" action="imprimirboleto.php" id="form_boleto" onSubmit="return verifica()">
<span class="sisola" style="margin-left:40px;"> Forma de Pagamento </span><select style="border-radius:0; padding:5px; width:250px; background-image:none;" name="parcelas" id="parcelas" onChange="calcula(this.value)"  required>
<option value="">Escolha uma forma de pagamento</option>
<option value="1">À vista | R$ 1582,43 </option><option value="2">2 Vezes de R$ 791,22</option><option value="3">3 Vezes de R$ 527,48</option>
</select>
<select name="parcela" id="parcelas_maximo" style="border-radius:0; padding:5px; background-image:none;" required><option value="">Escolha a parcela</option> <option value="1">Parcela 1</option> <option value="2">Parcela 2</option> <option value="3">Parcela 3</option></select><input type="submit" style="width:auto;
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
-webkit-border-radius: 5px;" value="Gerar Boleto">
</form>
<? } 

else
*/
$_POST['parcelas']=1;
?>
<? if(isset($_POST['parcelas'])) { ?>
<div class="sisleft" style="width:800px; height:auto; border:none; margin:20px;">
<div id="imprimivel" style="background-color:#FFFFFF; float:left; margin-left:25px; width:670px;">
<div style="width:670px; border:solid #000000 2px; padding:20px; background-color:#FFFFFF;">








<?php
function mostrar_boleto($parcelas,$parcela,$valorBoleto,$turma,$ano){
	
// DADOS DO BOLETO PARA O SEU CLIENTE
$dias_de_prazo_para_pagamento = 3;



$data_venc = date('d/m/Y',mktime(0,0,0,$GLOBALS['mes_matricula'],$GLOBALS['dia_matricula'] + $dias_de_prazo_para_pagamento,$GLOBALS['ano_matricula']));




$taxa_boleto = 0;



/*
if($GLOBALS['mkdatalimite'] >= $GLOBALS['mkdatacadastro'] || $_SESSION['candidator762']['cpf']=='11310080810')
{
	
$data_matricula=list($dia, $mes, $ano) = explode('/', $_SESSION['candidator762']['data_matricula']);

if($parcelas==1)
{
	
$data_venc = '07/12/2012'; //$data_venc = date('d/m/Y',mktime(0,0,0,$mes,$dia + $dias_de_prazo_para_pagamento,$ano));
if($_SESSION['candidator762']['cpf']=='11310080810') $data_venc = '20/01/2013'; 
}
else
{
if($parcela==1)
{
$data_venc = '07/12/2012';
if($_SESSION['candidator762']['cpf']=='11310080810') $data_venc = '20/01/2013'; 
}
else
if($parcela==2)
{
$data_venc = '07/01/2013';
if($_SESSION['candidator762']['cpf']=='11310080810') $data_venc = '20/02/2013'; 
}
else
if($parcela==3)
{
$data_venc = '07/02/2013';
if($_SESSION['candidator762']['cpf']=='11310080810') $data_venc = '20/03/2013'; 
}
}
}
*/
$valor_cobrado = number_format(($valorBoleto/$parcelas), 2, ',', ''); // 1582,43 Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal

//############ boleto diferente
#######################################################################################################################################
/*if($_SESSION['candidator762']['cpf']=='95763732391')
{
$data_venc = '12/03/2013';
$parcela=1;																								
$parcelas=1;	
$valor_cobrado = number_format((791.22/$parcelas), 2, ',', '');
}*/
#######################################################################################################################################


$valor_cobrado = str_replace(",", ".",$valor_cobrado);
$valor_boleto=number_format($valor_cobrado+$taxa_boleto, 2, ',', '');
$tabela=$_SESSION['candidator762']['tabela']=="Cadastro"?1:2;
$dadosboleto["nosso_numero"] = '19'.$parcela.$tabela.$_SESSION['candidator762']['idcadastro']; // Nosso numero - REGRA: Máximo de 8 caracteres!
$dadosboleto["numero_documento"] = $parcela.'/'.$parcelas;	// Num do pedido ou nosso numero
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
$dadosboleto["demonstrativo2"] = "(+) Turma MEDICINA - TURMA {$turma} - Matricula {$ano} - R$ {$valor_cobrado}";
$dadosboleto["demonstrativo3"] = "(=) Refere-se à parcela {$parcela} de {$parcelas} da Matrícula {$ano}";
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
mostrar_boleto($_POST['parcelas'], $_POST['parcela'],1725.00,5,2014);

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
