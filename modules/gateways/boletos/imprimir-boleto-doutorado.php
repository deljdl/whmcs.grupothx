<?php
ob_start();
session_start();
if(!isset($_SESSION['admin']) && !isset($_GET['HtGYGTrfrede4543DE'])) die('Sua sessão já foi finalizada. <br /> Atualize seu acesso');

require_once($_SERVER['DOCUMENT_ROOT'].'/classes/funcoes.php');

function enviar_boleto($id)
{
	
	conectar();
	$candidato=mysql_fetch_assoc(mysql_query("SELECT * FROM doutorado WHERE idcadastro = {$_GET['id']}"));
	
	
	if($candidato['vencimento_manual'] == '')
	{	
		$vencimento = explode('/',$candidato['data_matricula']);	
		$data_venc = date('d/m/Y',mktime(0,0,0,$vencimento[1],$vencimento[0] + 10,$vencimento[2]));	
	}
	else
	$data_venc = $candidato['vencimento_manual'];
	
	
	$valor_boleto = $candidato['valor_boleto'] != 0 ? $candidato['valor_boleto'] : '1000.00';
	
	
	$taxa_boleto = 0;
	
	$valor_cobrado = number_format($valor_boleto, 2, ',', ''); // 1582,43 Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
	
	$parcela=$parcelas=1;
	$ano = 2014;
	
	$valor_cobrado = str_replace(",", ".",$valor_cobrado);
	$valor_boleto=number_format($valor_cobrado+$taxa_boleto, 2, ',', '');
	$tabela=4;
	$dadosboleto["nosso_numero"] = '19'.$parcela.$tabela.$candidato['idcadastro']; // Nosso numero - REGRA: Máximo de 8 caracteres!
	$dadosboleto["numero_documento"] = $parcela.'/'.$parcelas;	// Num do pedido ou nosso numero
	$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
	$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
	$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
	$dadosboleto["valor_boleto"] = $valor_boleto; // Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula
	
	// DADOS DO SEU CLIENTE
	$dadosboleto["sacado"] = $candidato['nome'];
	$dadosboleto["endereco1"] = $candidato['endres'];
	$dadosboleto["endereco2"] = "{$candidato['cidade']} - {$candidato['estado']} - CEP: {$candidato['cep']}";
	
	// INFORMACOES PARA O CLIENTE
	$dadosboleto["demonstrativo1"] = "{$candidato['idcadastro']} - {$candidato['nome']}";
	$dadosboleto["demonstrativo2"] = "(+) Turma DOUTORADO - Matricula {$ano} - R$ {$valor_cobrado}";
	$dadosboleto["demonstrativo3"] = "(=) Refere-se à parcela {$parcela} de {$parcelas} da Matrícula {$ano}";
	$dadosboleto["instrucoes1"] = "";
	$dadosboleto["instrucoes2"] = "";
	$dadosboleto["instrucoes3"] = "(=) Em caso de dúvidas entre em contato conosco: financeiro@isped.com.br";
	$dadosboleto["instrucoes4"] = "(=)Sr. Caixa, não receber apos cinco dias do vencimento<br>Refere-se ao pagamento da taxa de matricula do curso de doutorado.";
	
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
enviar_boleto($id);

?>
