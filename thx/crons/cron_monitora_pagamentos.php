<?

$hoje = date('Y-m-d');
$dataLimite = date('Y-m-d', strtotime($hoje. ' - 3 months'));

$mes = date('m');

$pdo = new PDO("mysql:host=".THX_DB_HOST.";port=".THX_DB_PORT.";dbname=".THX_DB_NAME.";charset=utf8", THX_DB_USERNAME, THX_DB_PASSWORD);

/*
|--------------------------------------------------------------------------
| CLIENTES COM FATURAS EM ABERTO
| NÃO ALTERADO
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            CONCAT(tblclients.firstname, ' ', tblclients.lastname, ' [',tblclients.companyname,']') as cliente,
            tblclients.id as cliente_id,
            tblclients.status as cliente_status,
            tblinvoices.status as fatura_status,
            tblinvoices.id as fatura_id,
            tblinvoices.total as fatura_total,
            tblinvoices.duedate as fatura_vencimento,
            tblinvoices.datepaid as fatura_pagamento
        FROM tblinvoices
        INNER JOIN tblclients ON tblinvoices.userid=tblclients.id
        WHERE tblclients.status = 'Active'
            AND tblinvoices.status NOT IN ('Paid', 'Cancelled', 'Draft')
            AND tblinvoices.duedate < '{$dataLimite}'
            AND tblinvoices.datepaid = '0000-00-00 00:00:00'
        GROUP BY tblclients.id";

$result = $pdo->query($sql);
$clientes = $result->fetchAll(PDO::FETCH_ASSOC);

$tableStyle = 'style="width: 1000px;margin: 0;border: solid #CCCCCC 1px;border-collapse: collapse;font-family: Arial, sans-serif;color: #333333;text-align: left;"';
$tableTdStyle = 'style="padding: 5px 10px; border: solid #CCCCCC 1px;"';
$tableTdStyleRight = 'style="padding: 5px 10px; border: solid #CCCCCC 1px; text-align: right"';

$table = '';
$tableRows = '';

$table = '<table '.$tableStyle.'>';
$table.= '<thead>';

$table.='
<tr>
    <th '.$tableTdStyle.'>Cliente</th>
    <th '.$tableTdStyle.'>Valor</th>
    <th '.$tableTdStyle.'>Vencimento</th>
    <th></th>
    <th></th>
</tr>';

foreach($clientes as $cliente)
{
    $table.= '
    <tr>
        <td '.$tableTdStyle.'>'.$cliente['cliente'].'</td>
        <td '.$tableTdStyle.'>R$ '.$cliente['fatura_total'].'</td>
        <td '.$tableTdStyle.'>'.strftime('%d/%m/%Y', strtotime($cliente['fatura_vencimento'])).'</td>
        <td '.$tableTdStyle.'><a target="_blank" href="'.HTTP_WHMCS_ADMIN.'/clientssummary.php?userid='.$cliente['cliente_id'].'">CLIENTE</a></td>
        <td '.$tableTdStyle.'><a target="_blank" href="'.HTTP_WHMCS_ADMIN.'/invoices.php?action=edit&id='.$cliente['fatura_id'].'">FATURA</a></td>
        <td '.$tableTdStyle.'><a target="_blank" href="'.HTTP_WHMCS_ADMIN.'/clientsservices.php?userid='.$cliente['cliente_id'].'">PRODUTOS</a></td>
    </tr>';
}

$table.= '<tfoot>';
$table.= '<tr><th '.$tableTdStyleRight.'></th><th '.$tableTdStyle.'></th><th '.$tableTdStyle.'></th></tr>';
$table.= '</tfoot>';
$table.= '</table>';


/*
|--------------------------------------------------------------------------
| ANOMALIAS DE COBRANÇA
|--------------------------------------------------------------------------
|
| 1. Serviço ativo com vencimento atrasado há mais de 3 meses
| 2. Serviço mensal com vencimento mais de 2 meses no futuro
|
*/

$sql = "SELECT
            h.id,
            h.userid,
            CONCAT(c.firstname, ' ', c.lastname, ' [', c.companyname, ']') AS cliente,
            h.domain,
            h.amount,
            h.billingcycle,
            h.nextduedate,

            (
                SELECT i.datepaid
                FROM tblinvoiceitems ii
                INNER JOIN tblinvoices i ON i.id = ii.invoiceid
                WHERE ii.type = 'Hosting'
                    AND ii.relid = h.id
                    AND i.status = 'Paid'
                    AND i.datepaid IS NOT NULL
                    AND i.datepaid != '0000-00-00 00:00:00'
                ORDER BY i.datepaid DESC
                LIMIT 1
            ) AS ultimo_pagamento,

            (
                SELECT i.total
                FROM tblinvoiceitems ii
                INNER JOIN tblinvoices i ON i.id = ii.invoiceid
                WHERE ii.type = 'Hosting'
                    AND ii.relid = h.id
                    AND i.status = 'Paid'
                    AND i.datepaid IS NOT NULL
                    AND i.datepaid != '0000-00-00 00:00:00'
                ORDER BY i.datepaid DESC
                LIMIT 1
            ) AS valor_ultimo_pagamento

        FROM tblhosting h
        INNER JOIN tblclients c ON c.id = h.userid

        WHERE h.domainstatus = 'Active'
            AND h.billingcycle != 'Free Account'
            AND h.nextduedate != '0000-00-00'
            AND (
                h.nextduedate < '{$dataLimite}'
                OR (
                    h.billingcycle = 'Monthly'
                    AND h.nextduedate > DATE_ADD('{$hoje}', INTERVAL 2 MONTH)
                )
            )

        ORDER BY h.nextduedate";

$result = $pdo->query($sql);
$anomalias = $result->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| TABELA DE ANOMALIAS
|--------------------------------------------------------------------------
*/

$tableAnomalias = '';

$tableAnomalias .= '<table '.$tableStyle.'>';

$tableAnomalias .= '
<tr>
    <th '.$tableTdStyle.'>Cliente</th>
    <th '.$tableTdStyle.'>Domínio</th>
    <th '.$tableTdStyle.'>Valor</th>
    <th '.$tableTdStyle.'>Ciclo</th>
    <th '.$tableTdStyle.'>Próximo Vencimento</th>
    <th '.$tableTdStyle.'>Último Pagamento</th>
    <th '.$tableTdStyle.'>Valor Pago</th>
    <th></th>
    <th></th>
</tr>';

foreach($anomalias as $anomalia)
{
    $motivo = '';

    if($anomalia['nextduedate'] < $dataLimite)
    {
        $motivo = 'Vencimento atrasado';
    }
    elseif($anomalia['billingcycle'] == 'Monthly')
    {
        $motivo = 'Vencimento muito distante';
    }

    $tableAnomalias .= '
    <tr>
        <td '.$tableTdStyle.'>'.$anomalia['cliente'].'</td>
        <td '.$tableTdStyle.'>'.$anomalia['domain'].'</td>
        <td '.$tableTdStyle.'>R$ '.$anomalia['amount'].'</td>
        <td '.$tableTdStyle.'>'.$anomalia['billingcycle'].'</td>
        <td '.$tableTdStyle.'>'.strftime('%d/%m/%Y', strtotime($anomalia['nextduedate'])).'</td>
        <td '.$tableTdStyle.'>'.(
            $anomalia['ultimo_pagamento']
            ? strftime('%d/%m/%Y', strtotime($anomalia['ultimo_pagamento']))
            : 'NUNCA'
        ).'</td>
        <td '.$tableTdStyle.'>R$ '.(
            $anomalia['valor_ultimo_pagamento'] !== null
            ? $anomalia['valor_ultimo_pagamento']
            : '0.00'
        ).'</td>
        <td '.$tableTdStyle.'>
            <a target="_blank" href="'.HTTP_WHMCS_ADMIN.'/clientssummary.php?userid='.$anomalia['userid'].'">CLIENTE</a>
        </td>
        <td '.$tableTdStyle.'>
            <a target="_blank" href="'.HTTP_WHMCS_ADMIN.'/clientsservices.php?userid='.$anomalia['userid'].'">PRODUTOS</a>
        </td>
    </tr>';
}

$tableAnomalias .= '</table>';


/*
|--------------------------------------------------------------------------
| SERVIÇOS SEM COBRANÇA / PAGAMENTO
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            h.id,
            h.userid,
            CONCAT(c.firstname, ' ', c.lastname, ' [', c.companyname, ']') AS cliente,
            h.domain,
            h.amount,
            h.billingcycle,
            h.nextduedate,

            (
                SELECT i.datepaid
                FROM tblinvoiceitems ii
                INNER JOIN tblinvoices i ON i.id = ii.invoiceid
                WHERE ii.type = 'Hosting'
                    AND ii.relid = h.id
                    AND i.status = 'Paid'
                    AND i.datepaid != '0000-00-00 00:00:00'
                ORDER BY i.datepaid DESC
                LIMIT 1
            ) AS ultimo_pagamento,

            (
                SELECT i.total
                FROM tblinvoiceitems ii
                INNER JOIN tblinvoices i ON i.id = ii.invoiceid
                WHERE ii.type = 'Hosting'
                    AND ii.relid = h.id
                    AND i.status = 'Paid'
                    AND i.datepaid != '0000-00-00 00:00:00'
                ORDER BY i.datepaid DESC
                LIMIT 1
            ) AS valor_ultimo_pagamento

        FROM tblhosting h
        INNER JOIN tblclients c ON c.id = h.userid

        WHERE h.domainstatus = 'Active'
            AND h.billingcycle IN (
                'Monthly',
                'Quarterly',
                'Semi-Annually',
                'Annually',
                'Biennially'
            )
            AND (
                (
                    h.billingcycle = 'Monthly'
                    AND (
                        (
                            SELECT MAX(i.datepaid)
                            FROM tblinvoiceitems ii
                            INNER JOIN tblinvoices i ON i.id = ii.invoiceid
                            WHERE ii.type = 'Hosting'
                                AND ii.relid = h.id
                                AND i.status = 'Paid'
                                AND i.datepaid != '0000-00-00 00:00:00'
                        ) IS NULL
                        OR
                        (
                            SELECT MAX(i.datepaid)
                            FROM tblinvoiceitems ii
                            INNER JOIN tblinvoices i ON i.id = ii.invoiceid
                            WHERE ii.type = 'Hosting'
                                AND ii.relid = h.id
                                AND i.status = 'Paid'
                                AND i.datepaid != '0000-00-00 00:00:00'
                        ) < DATE_SUB('{$hoje}', INTERVAL 2 MONTH)
                    )
                )

                OR

                (
                    h.billingcycle = 'Quarterly'
                    AND (
                        (
                            SELECT MAX(i.datepaid)
                            FROM tblinvoiceitems ii
                            INNER JOIN tblinvoices i ON i.id = ii.invoiceid
                            WHERE ii.type = 'Hosting'
                                AND ii.relid = h.id
                                AND i.status = 'Paid'
                                AND i.datepaid != '0000-00-00 00:00:00'
                        ) IS NULL
                        OR
                        (
                            SELECT MAX(i.datepaid)
                            FROM tblinvoiceitems ii
                            INNER JOIN tblinvoices i ON i.id = ii.invoiceid
                            WHERE ii.type = 'Hosting'
                                AND ii.relid = h.id
                                AND i.status = 'Paid'
                                AND i.datepaid != '0000-00-00 00:00:00'
                        ) < DATE_SUB('{$hoje}', INTERVAL 4 MONTH)
                    )
                )

                OR

                (
                    h.billingcycle = 'Semi-Annually'
                    AND (
                        (
                            SELECT MAX(i.datepaid)
                            FROM tblinvoiceitems ii
                            INNER JOIN tblinvoices i ON i.id = ii.invoiceid
                            WHERE ii.type = 'Hosting'
                                AND ii.relid = h.id
                                AND i.status = 'Paid'
                                AND i.datepaid != '0000-00-00 00:00:00'
                        ) IS NULL
                        OR
                        (
                            SELECT MAX(i.datepaid)
                            FROM tblinvoiceitems ii
                            INNER JOIN tblinvoices i ON i.id = ii.invoiceid
                            WHERE ii.type = 'Hosting'
                                AND ii.relid = h.id
                                AND i.status = 'Paid'
                                AND i.datepaid != '0000-00-00 00:00:00'
                        ) < DATE_SUB('{$hoje}', INTERVAL 8 MONTH)
                    )
                )

                OR

                (
                    h.billingcycle = 'Annually'
                    AND (
                        (
                            SELECT MAX(i.datepaid)
                            FROM tblinvoiceitems ii
                            INNER JOIN tblinvoices i ON i.id = ii.invoiceid
                            WHERE ii.type = 'Hosting'
                                AND ii.relid = h.id
                                AND i.status = 'Paid'
                                AND i.datepaid != '0000-00-00 00:00:00'
                        ) IS NULL
                        OR
                        (
                            SELECT MAX(i.datepaid)
                            FROM tblinvoiceitems ii
                            INNER JOIN tblinvoices i ON i.id = ii.invoiceid
                            WHERE ii.type = 'Hosting'
                                AND ii.relid = h.id
                                AND i.status = 'Paid'
                                AND i.datepaid != '0000-00-00 00:00:00'
                        ) < DATE_SUB('{$hoje}', INTERVAL 14 MONTH)
                    )
                )

                OR

                (
                    h.billingcycle = 'Biennially'
                    AND (
                        (
                            SELECT MAX(i.datepaid)
                            FROM tblinvoiceitems ii
                            INNER JOIN tblinvoices i ON i.id = ii.invoiceid
                            WHERE ii.type = 'Hosting'
                                AND ii.relid = h.id
                                AND i.status = 'Paid'
                                AND i.datepaid != '0000-00-00 00:00:00'
                        ) IS NULL
                        OR
                        (
                            SELECT MAX(i.datepaid)
                            FROM tblinvoiceitems ii
                            INNER JOIN tblinvoices i ON i.id = ii.invoiceid
                            WHERE ii.type = 'Hosting'
                                AND ii.relid = h.id
                                AND i.status = 'Paid'
                                AND i.datepaid != '0000-00-00 00:00:00'
                        ) < DATE_SUB('{$hoje}', INTERVAL 26 MONTH)
                    )
                )
            )

        ORDER BY h.nextduedate";

$result = $pdo->query($sql);
$semPagamento = $result->fetchAll(PDO::FETCH_ASSOC);

$tableSemPagamento = '<table '.$tableStyle.'>';

$tableSemPagamento .= '
<tr>
    <th '.$tableTdStyle.'>Cliente</th>
    <th '.$tableTdStyle.'>Domínio</th>
    <th '.$tableTdStyle.'>Valor</th>
    <th '.$tableTdStyle.'>Ciclo</th>
    <th '.$tableTdStyle.'>Próximo Vencimento</th>
    <th '.$tableTdStyle.'>Último Pagamento</th>
    <th '.$tableTdStyle.'>Valor Pago</th>
    <th></th>
</tr>';

foreach($semPagamento as $servico)
{
    $tableSemPagamento .= '
    <tr>
        <td '.$tableTdStyle.'>'.$servico['cliente'].'</td>
        <td '.$tableTdStyle.'>'.$servico['domain'].'</td>
        <td '.$tableTdStyle.'>R$ '.$servico['amount'].'</td>
        <td '.$tableTdStyle.'>'.$servico['billingcycle'].'</td>
        <td '.$tableTdStyle.'>'.date('d/m/Y', strtotime($servico['nextduedate'])).'</td>
        <td '.$tableTdStyle.'>'.(
            $servico['ultimo_pagamento']
            ? date('d/m/Y', strtotime($servico['ultimo_pagamento']))
            : '<strong style="color:red">NUNCA</strong>'
        ).'</td>
        <td '.$tableTdStyle.'>R$ '.(
            $servico['valor_ultimo_pagamento'] !== null
            ? $servico['valor_ultimo_pagamento']
            : '0.00'
        ).'</td>
        <td '.$tableTdStyle.'>
            <a target="_blank" href="'.HTTP_WHMCS_ADMIN.'/clientsservices.php?userid='.$servico['userid'].'">PRODUTOS</a>
        </td>
    </tr>';
}

$tableSemPagamento .= '</table>';


/*
|--------------------------------------------------------------------------
| HTML
|--------------------------------------------------------------------------
*/

$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Monitoramento de cobranças</title>
</head>
<body>

<p>
    '.'Relatório de clientes inativos ou com faturas em aberto desde '.date('d/m/Y', strtotime($dataLimite)).'
    <br />
    <strong>
        Considere excluir o cliente ou a fatura.
        <br />
        <span style="color:red">Não esqueça de excluir os produtos e domínios antes de excluir o cliente</span>
    </strong>.'.'
</p>

'.$table.'
<hr />

<h2>SERVIÇOS SEM PAGAMENTO</h2>

<p>
    Serviços ativos que não possuem pagamento dentro do período esperado para o ciclo de cobrança.
</p>

'.$tableSemPagamento.'
<hr />


<h2>ANOMALIAS DE COBRANÇA</h2>

<p>
    Serviços ativos com vencimento atrasado há mais de 3 meses
    ou serviços mensais com vencimento mais de 2 meses no futuro.
</p>

'.$tableAnomalias.'

</body>
</html>';

/*
|--------------------------------------------------------------------------
| EMAIL
|--------------------------------------------------------------------------
*/


$email = new \PHPMailer\PHPMailer\PHPMailer();
$email->WordWrap = 50;
$email->Priority = 1;
$email->IsHTML(true);
$email->From = 'financeiro@grupothx.com.br';
$email->FromName = 'Grupo THX';
$email->Subject = 'Clientes com faturas em aberto / Anomalias de cobrança';
$email->Body = $html;
$email->CharSet = "UTF-8";
$email->Encoding = 'base64';
$email->AddAddress('deljdl@gmail.com', 'Admin');

$envio = $email->Send();