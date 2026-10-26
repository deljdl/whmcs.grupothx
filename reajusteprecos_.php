<?php
$db_host = 'DADO ERRADO DE PROPÓSITO POR SEGURANÇA';
$db_port = 'DADO ERRADO DE PROPÓSITO POR SEGURANÇA';
$db_username = 'DADO ERRADO DE PROPÓSITO POR SEGURANÇA';
$db_password = 'DADO ERRADO DE PROPÓSITO POR SEGURANÇA';
$db_name = 'DADO ERRADO DE PROPÓSITO POR SEGURANÇA';
$reajuste = 1.0005; // Valor do reajuste

try {
    // Conexão com o banco de dados usando PDO
    $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexão bem-sucedida!\n";
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

function columnExists($pdo, $tableName, $columnName) {
    $sql = "SHOW COLUMNS FROM $tableName LIKE :column;";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':column', $columnName, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

function createBackupColumn($pdo, $tableName, $columnName, $backupDate) {
    if (!columnExists($pdo, $tableName, $columnName)) {
        $sql = "ALTER TABLE $tableName ADD $columnName DECIMAL(10,2) DEFAULT 0;";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        echo "Coluna $columnName criada com sucesso!\n";
    } else {
        echo "A coluna $columnName já existe.\n";
    }

    // Fazer backup dos valores atuais
    $sqlBackup = "UPDATE $tableName SET $columnName = $columnName, 
                  amount_bkp_quarterly_{$backupDate} = quarterly, 
                  amount_bkp_semiannually_{$backupDate} = semiannually, 
                  amount_bkp_annually_{$backupDate} = annually, 
                  amount_bkp_biennially_{$backupDate} = biennially, 
                  amount_bkp_triennially_{$backupDate} = triennially;";
    $stmtBackup = $pdo->prepare($sqlBackup);
    $stmtBackup->execute();
}

$backupDate = date('Y_m_d');

try {
    // Criar colunas de backup para tblhosting
    createBackupColumn($pdo, 'tblhosting', "amount_bkp_{$backupDate}", $backupDate);

    // Realizar reajuste
    $sql = "UPDATE tblhosting SET amount = amount * :reajuste;";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':reajuste', $reajuste, PDO::PARAM_STR);
    $stmt->execute();

    echo "Reajuste de {$reajuste} para tblhosting realizado com sucesso!\n";

    // Criar colunas de backup para tblpricing
    $pricingColumns = ['monthly', 'quarterly', 'semiannually', 'annually', 'biennially', 'triennially'];
    foreach ($pricingColumns as $column) {
        createBackupColumn($pdo, 'tblpricing', "amount_bkp_{$column}_{$backupDate}", $backupDate);
    }

    // Realizar reajuste
    $sqlPricing = "UPDATE tblpricing SET 
                    monthly = monthly * :reajuste, 
                    quarterly = quarterly * :reajuste, 
                    semiannually = semiannually * :reajuste, 
                    annually = annually * :reajuste, 
                    biennially = biennially * :reajuste, 
                    triennially = triennially * :reajuste;";
    $stmtPricing = $pdo->prepare($sqlPricing);
    $stmtPricing->bindParam(':reajuste', $reajuste, PDO::PARAM_STR);
    $stmtPricing->execute();

    echo "Reajuste de {$reajuste} para tblpricing realizado com sucesso!";
} catch (PDOException $e) {
    die("Erro ao executar consulta: " . $e->getMessage());
}