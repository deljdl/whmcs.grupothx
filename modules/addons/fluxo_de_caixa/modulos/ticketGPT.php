<?php
require_once('config.php');



try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Pega todos os servidores
    $sql = "SELECT id, name, monthlycost FROM tblservers";
    $stmt = $pdo->query($sql);

    echo "<h2>Servidores WHMCS</h2>";
    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse; width:100%;'>";
    echo "<tr style='background:#f0f0f0;'>
            <th>ID</th>
            <th>Nome do Servidor</th>
            <th>Clientes Ativos</th>
            <th>Valor Pago (mensal)</th>
            <th>Valor Recebido (mensal)</th>
            <th>Saldo</th>
          </tr>";

    $saldoTotal = 0;

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = $row['id'];
        $name = htmlspecialchars($row['name']);
        $monthlyCost = floatval($row['monthlycost']);

        // Conta clientes ativos/suspensos
        $sqlClients = "SELECT COUNT(*) FROM tblhosting WHERE server = :serverId AND domainstatus IN ('Active', 'Suspended')";
        $stmtClients = $pdo->prepare($sqlClients);
        $stmtClients->execute(['serverId' => $id]);
        $numClients = $stmtClients->fetchColumn();

        // Calcula receita mensalizada
        $sqlRevenue = "
            SELECT amount, billingcycle 
            FROM tblhosting 
            WHERE server = :serverId AND domainstatus IN ('Active', 'Suspended')
        ";
        $stmtRevenue = $pdo->prepare($sqlRevenue);
        $stmtRevenue->execute(['serverId' => $id]);
        $totalMonthlyRevenue = 0;

        while ($hosting = $stmtRevenue->fetch(PDO::FETCH_ASSOC)) {
			$amount = floatval($hosting['amount']);
			$cycle = strtolower(trim($hosting['billingcycle']));

			switch ($cycle) {
				case 'monthly':
					$divisor = 1; break;
				case 'quarterly':
					$divisor = 3; break;
				case 'semi-annually':
				case 'semiannually':
					$divisor = 6; break;
				case 'annually':
					$divisor = 12; break;
				case 'biennially':
					$divisor = 24; break;
				case 'triennially':
					$divisor = 36; break;
				default:
					$divisor = 1;
			}

			$monthlyAmount = ($divisor > 0) ? ($amount / $divisor) : $amount;
			$totalMonthlyRevenue += $monthlyAmount;
		}

        $saldo = $totalMonthlyRevenue - $monthlyCost;
        $saldoTotal += $saldo;

        echo "<tr>
                <td align='center'>{$id}</td>
                <td>{$name}</td>
                <td align='center'>{$numClients}</td>
                <td align='right'>R$ ".number_format($monthlyCost,2,',','.')."</td>
                <td align='right'>R$ ".number_format($totalMonthlyRevenue,2,',','.')."</td>
                <td align='right'><strong>R$ ".number_format($saldo,2,',','.')."</strong></td>
              </tr>";
    }

    echo "<tr style='background:#f0f0f0; font-weight:bold;'>
            <td colspan='5' align='right'>Saldo Total:</td>
            <td align='right'>R$ ".number_format($saldoTotal,2,',','.')."</td>
          </tr>";

    echo "</table>";

} catch (PDOException $e) {
    echo "Erro na conexão ou consulta: " . $e->getMessage();
}