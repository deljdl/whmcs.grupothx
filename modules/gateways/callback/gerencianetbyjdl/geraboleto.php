<?php
require($_SERVER['DOCUMENT_ROOT']."/thx/init.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/thx/tarifasTHX.php');
$whmcs->load_function('gateway');
$whmcs->load_function('client');
setlocale( LC_ALL, "pt_BR" );
date_default_timezone_set('America/Sao_Paulo');
$erro = '';

logGrupoTHX('Acessado', 'gerencianet', date('G:i'));


   require __DIR__ . '/vendor/autoload.php'; // caminho relacionado a SDK

   use Gerencianet\Exception\GerencianetException;
   use Gerencianet\Gerencianet;

   $clientId = GERENCIANET_CLIENTID;// insira seu Client_Id, conforme o ambiente (Des ou Prod)
   $clientSecret = GERENCIANET_CLIENTESECRET; // insira seu Client_Secret, conforme o ambiente (Des ou Prod)

    $options = [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'sandbox' => GERENCIANET_SANDBOX // altere conforme o ambiente (true = desenvolvimento e false = producao)
    ];
    
   $item_1 = [
       'name' => 'Item 1', // nome do item, produto ou serviço
       'amount' => 1, // quantidade
       'value' => 1000 // valor (1000 = R$ 10,00) (Obs: É possível a criação de itens com valores negativos. Porém, o valor total da fatura deve ser superior ao valor mínimo para geração de transações.)
   ];
   $items = [
       $item_1
   ];
   $metadata = array(
      'notification_url'=>'https://grupothx.com/thx/modules/gateways/callback/gerencianetbyjdl/gerencia-recebe0notificacoes.php',
      'custom_id' => '666'); //Url de notificações
   $customer = [
       'name' => 'Pedro Jacomini 666 custom_id', // nome do cliente
       'cpf' => '29466511858', // cpf válido do cliente
       'phone_number' => '11985691184', // telefone do cliente
   ];
   // $discount = [ // configuração de descontos
   //     'type' => 'currency', // tipo de desconto a ser aplicado
   //     'value' => 50 // valor de desconto 
   // ];
   $configurations = [ // configurações de juros e mora
       'fine' => 0, // porcentagem de multa
       'interest' => 0 // porcentagem de juros
   ];
   // $conditional_discount = [ // configurações de desconto condicional
   //     'type' => 'percentage', // seleção do tipo de desconto 
   //     'value' => 500, // porcentagem de desconto
   //     'until_date' => '2019-08-30' // data máxima para aplicação do desconto
   // ];
   $bankingBillet = [
       'expire_at' => '2019-10-22', // data de vencimento do titulo
       'message' => 'teste\nteste\nteste\nteste', // mensagem a ser exibida no boleto
       'customer' => $customer
   ];
   $payment = [
       'banking_billet' => $bankingBillet // forma de pagamento (banking_billet = boleto)
   ];
   $body = [
       'items' => $items,
       'metadata' =>$metadata,
       'payment' => $payment
   ];
   try {
     $api = new Gerencianet($options);
     $pay_charge = $api->oneStep([],$body);
     echo '<pre>';
     print_r($pay_charge);
     echo '<pre>';
     
    } catch (GerencianetException $e) {
       print_r($e->code);
       print_r($e->error);
       print_r($e->errorDescription);
   } catch (Exception $e) {
       print_r($e->getMessage());
   }