<?
$conn = new PDO('mysql:host='.THX_DB_HOST.';dbname='.THX_DB_NAME, THX_DB_USERNAME, THX_DB_PASSWORD);




$alexa = '';
$alexasCount=1;
$alexasSoma = 0;

$sql = "SELECT * FROM dividas WHERE divida_paga=0 ORDER BY divida_vencimento";
$result = $conn->query($sql);
$dividas = $result->fetchAll(PDO::FETCH_ASSOC);

$tableStyle = 'style="width: 1000px;margin: 0;border: solid #CCCCCC 1px;border-collapse: collapse;font-family: Arial, sans-serif;color: #333333;text-align: left;"';
$tableTdStyle = 'style="padding: 5px 10px; border: solid #CCCCCC 1px;"';
$tableTdStyleRight = 'style="padding: 5px 10px; border: solid #CCCCCC 1px; text-align: right"';
$table = '';
$tableRows = '';
$table = '<table '.$tableStyle.'>';
$table.= $caption ? '<caption>Dívidas</caption>' : '';
$table.= '<thead>';

$table.='
<tr>
    <th '.$tableTdStyle.'>Dívida</th>
    <th '.$tableTdStyle.'>Valor</th>
    <th '.$tableTdStyle.'>Vencimento</th>
</tr>';

$total = 0;

foreach($dividas as $divida)
{
    $total+=$divida['divida_valor'];
    $diferencadias = 0;
    if(strtotime(date('Y-m-d')) > strtotime($divida['divida_vencimento']))
    {
        $diferenca = strtotime(date('Y-m-d')) - strtotime($divida['divida_vencimento']);
        $diferencadias = floor($diferenca / (60 * 60 * 24));
        $class="red; color: white";
        $prazo = 'Vencida a '.$diferencadias.' dias !!!';
    }
    elseif(strtotime(date('Y-m-d')) == strtotime($divida['divida_vencimento']))
    {
        $class="#ff000017";
        $prazo = 'Vencimento é hoje !!!!';

        $tarefa = '
        <strong>Contas a pagar:</strong><br />
        <hr />
        <strong>Beneficiário/Dívida:</strong><br />'.mb_convert_encoding($divida['divida_descricao'], "UTF-8", "ISO-8859-1").'
        <strong>| Valor:</strong> R$ '.$divida['divida_valor'].' | <strong>Vencimento:</strong> '.strftime('%d/%m/%Y', strtotime($divida['divida_vencimento']));

        criaTarefaKamban($tarefa, $divida['divida_vencimento'], 'Pagamentos');
        sleep(5);
        $alexa.= $alexasCount.'º Beneficiário:, '.$divida['divida_descricao'].', no valor de R$'.str_replace('.', ',', $divida['divida_valor']).'<break time="1s"/>';
        $alexasSoma+=$divida['divida_valor'];
        $alexasCount++;
    }
    else
    {

        $diferenca = strtotime($divida['divida_vencimento']) - strtotime(date('Y-m-d'));
        $diferencadias = floor($diferenca / (60 * 60 * 24));
        if($diferencadias < 6)
        {
            $class="white";
            $prazo = 'Vencimento em '.$diferencadias.' dias !!!!';
        }
        else
        {
            $class="#3fff003d";
            $prazo = 'Vencimento em '.$diferencadias.' dias !!!!';

        }
    }


    $table.= '
    <tr style="background: '.$class.'">
        <td '.$tableTdStyle.'>'.$divida['divida_descricao'].'</td>
        <td '.$tableTdStyle.'>R$ '.$divida['divida_valor'].'</td>
        <td '.$tableTdStyle.'>'.strftime('%d/%m/%Y', strtotime($divida['divida_vencimento'])).'<br />'.$prazo.'</td>
    </tr>';
    
}



$table.= '<tfoot>';
$table.= '<tr><th '.$tableTdStyleRight.'>Total em aberto</th><th '.$tableTdStyle.'>R$ '.$total.'</th><th '.$tableTdStyle.'></th></tr>';
$table.= '</tfoot>';
$table.= '</table>';

$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Dívidas</title>
</head>
<body>
<p>Relatório de dívidas em aberto.</p>
<hr />
    '.$table.'
</body>
</html>';

$email = new \PHPMailer\PHPMailer\PHPMailer();
$email->WordWrap = 50;               // set word wrap
$email->Priority = 1;
$email->IsHTML(true);
$email->From      = 'financeiro@grupothx.com.br';
$email->FromName  = 'Grupo THX';
$email->Subject   = 'Dívidas em aberto';
$email->Body      = $html;
$email ->CharSet  = "UTF-8";
$email->Encoding = 'base64';
$email->AddAddress('deljdl@gmail.com', 'Admin');
$envio = $email->Send();

// [divida_id] => 1032
// [divida_descricao] => Carro New Fiesta 34/50
// [divida_valor] => 1070.08
// [divida_paga] => 1
// [divida_vencimento] => 2023-07-21
// [divida_ano] => 2023
// [divida_mes] => 7
// [divida_data_pagamento] => 2023-07-12 14:56:33

//sleep(10);
if($alexa != '')
{
    $avisoCobrancas = 'Você tem pagamentos a serem feitos hoje: <break time="1s"/>'.$alexa.'
    O total de pagamentos para hoje é de R$'.str_replace('.', ',', $alexasSoma);
    
    alexa($avisoCobrancas);
    die($avisoCobrancas);
}
