<?php
$db_host = THX_DB_HOST;
$db_port = THX_DB_PORT;
$db_username = THX_DB_USERNAME;
$db_password = THX_DB_PASSWORD;
$db_name = THX_DB_NAME;
$reajuste = 1.0005; // Valor do reajuste

try {
    // Conexão com o banco de dados usando PDO
    $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexão bem-sucedidaaaa!\n";
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}



// Preparando a consulta SQL
$stmt = $pdo->prepare("SELECT * FROM tblaccounts WHERE transid LIKE '%identified%' GROUP BY  invoiceid ORDER BY id DESC");

// Executando a consulta
$stmt->execute();

// Usando fetchAll para obter todos os registros
$invoices = $stmt->fetchAll();
// var_dump($invoices);
    foreach ($invoices as $invoice)
    {
        echo "<hr />
        <br />id: {$invoice['id']}
        <br />invoiceid: {$invoice['invoiceid']}
        <br />date: {$invoice['date']}
        <br />transid: {$invoice['transid']}";
    }







die('<hr />FIM');