<?php

require_once('config.php'); // Adjust the path as necessary
$dia = date('Y-m-d');

// ########### CALCULA AS MEDIAS
function calculaMedia($pdo, $server = null, $excluir = false) {
    if ($server === null) {
        $where = "";
    } elseif ($excluir) {
        $where = "AND server NOT IN ($server)";
    } else {
        $where = "AND server = $server";
    }

    $sql = "
        SELECT 
            COALESCE(SUM(
                CASE
                    WHEN billingcycle = 'Quarterly' THEN (amount/3)
                    WHEN billingcycle = 'Semi-Annually' THEN (amount/6)
                    WHEN billingcycle = 'Annually' THEN (amount/12)
                    WHEN billingcycle = 'Biennially' THEN (amount/24)
                    WHEN billingcycle = 'Triennially' THEN (amount/36)
                    ELSE amount
                END
            ),0) as total
        FROM tblhosting
        WHERE domainstatus = 'Active'
          AND billingcycle NOT IN ('One Time', 'Free Account')
          $where
    ";
    return floatval($pdo->query($sql)->fetchColumn());
}

// ########### CALCULA OUTROS
$tihospedagem_com = calculaMedia($pdo, 8);
$tihospedagem_com_br = calculaMedia($pdo, 12);
$server1_thxhost_com = calculaMedia($pdo, 14);
$outros_servicos = calculaMedia($pdo, "8,12,14", true);

// ########### CALCULA DOMINIOS
$registrodominios = floatval($pdo->query("SELECT (COUNT(1) * 10 / 12) FROM tbldomains")->fetchColumn());

// ########### CALCULA CUSTOS DIRETOS DO BANCO
$custos = $pdo->query("SELECT id, monthlycost FROM tblservers WHERE id IN (8,12,14)")->fetchAll(PDO::FETCH_KEY_PAIR);

$custo8 = floatval(isset($custos[8]) ? $custos[8] : 0);
$custo12 = floatval(isset($custos[12]) ? $custos[12] : 0);
$custo14 = floatval(isset($custos[14]) ? $custos[14] : 0);

// ########### CALCULA VALORES TOTAIS
$totalHospedagens = $tihospedagem_com + $tihospedagem_com_br + $server1_thxhost_com;
$total = $totalHospedagens + $outros_servicos + $registrodominios;

// ########### CHECA EXISTENTE
$checa = $pdo->query("SELECT * FROM grafico_hospedagens ORDER BY grafico_hospedagens_data DESC LIMIT 1");
$diasalvo = $checa->fetch(PDO::FETCH_ASSOC);

if($diasalvo && date('Y-m-d', strtotime($diasalvo['grafico_hospedagens_data'])) == $dia) {
    die('Já tem registro para '.$dia);
}
if($diasalvo && $total == $diasalvo['grafico_media_geral']) {
    die('Valor se mantém em R$ '.$total);
}

// ########### INSERE NO GRAFICO_HOSPEDAGENS
$pdo->prepare("
    INSERT INTO grafico_hospedagens
    (
        grafico_hospedagens_media,
        grafico_hospedagens_valor,
        grafico_hospedagens_data,

        grafico_hospedagens_media_tihospedagemcom,
        grafico_hospedagens_valor_tihospedagemcom,

        grafico_hospedagens_media_tihospedagemcombr,
        grafico_hospedagens_valor_tihospedagemcombr,

        grafico_hospedagens_media_server1thxhostcom,
        grafico_hospedagens_valor_server1thxhostcom,

        grafico_hospedagens_media_dominios,
        grafico_hospedagens_valor_dominios,

        grafico_hospedagens_media_outrosservicos,
        grafico_hospedagens_valor_outrosservicos,
        grafico_media_geral
    )
    VALUES
    (
        :totalHospedagens, 
        :custoHospedagens, 
        NOW(),
        :tihospedagem_com, :custo8,
        :tihospedagem_com_br, :custo12,
        :server1_thxhost_com, :custo14,
        :registrodominios, :registrodominios,
        :outros_servicos, 0,
        :total
    )
")->execute([
    ':totalHospedagens' => $totalHospedagens,
    ':custoHospedagens' => $custo8 + $custo12 + $custo14,
    ':tihospedagem_com' => $tihospedagem_com,
    ':custo8' => $custo8,
    ':tihospedagem_com_br' => $tihospedagem_com_br,
    ':custo12' => $custo12,
    ':server1_thxhost_com' => $server1_thxhost_com,
    ':custo14' => $custo14,
    ':registrodominios' => $registrodominios,
    ':outros_servicos' => $outros_servicos,
    ':total' => $total
]);

die("Nova média salva R$ ".number_format($total,2,',','.'));
