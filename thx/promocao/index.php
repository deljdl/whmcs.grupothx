<?php

echo '<pre>';
print_r($_GET);
echo '</pre>';


$imagens = glob(__DIR__ . '/*.jpg');
$promocoes = [];

foreach ($imagens as $imagem) {
    $id = pathinfo($imagem, PATHINFO_FILENAME);
    if (ctype_digit($id)) {
        $promocoes[(int)$id] = basename($imagem);
    }
}

ksort($promocoes, SORT_NUMERIC);
$destaque = filter_input(INPUT_GET, 'promo', FILTER_VALIDATE_INT);
if ($destaque && isset($promocoes[$destaque])) {
    $imagem = $promocoes[$destaque];
    unset($promocoes[$destaque]);
    $promocoes = [$destaque => $imagem] + $promocoes;
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index,follow">
    <title>Promoções e Serviços | Grupo THX</title>
    <meta name="description" content="Conheça as promoções, serviços e soluções do Grupo THX.">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #26384d; background: #f4f7fb; }
        header { background: #1a3c6a; color: #fff; text-align: center; padding: 38px 20px; }
        header h1 { margin: 0 0 10px; font-size: clamp(26px, 4vw, 38px); }
        header p { margin: 0; color: #e0eaf5; }
        main { max-width: 1160px; margin: 35px auto; padding: 0 18px; }
        .grade { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 310px), 1fr)); gap: 22px; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(20, 48, 84, .06); }
        .card.destaque { grid-column: 1 / -1; border: 2px solid #1a3c6a; }
        .card img { width: 100%; height: auto; display: block; }
        .conteudo { padding: 20px; }
        .conteudo h2 { font-size: 19px; margin: 0 0 9px; }
        .conteudo p { color: #607086; line-height: 1.5; margin: 0 0 18px; }
        .botao { display: inline-block; background: #1a3c6a; color: #fff; padding: 11px 18px; border-radius: 6px; text-decoration: none; font-weight: bold; }
        .botao:hover { background: #28568e; }
        footer { text-align: center; padding: 25px; color: #78879a; font-size: 13px; }
    </style>
</head>
<body>
<header>
    <h1>Promoções e Serviços</h1>
    <p>Conheça as oportunidades e soluções do Grupo THX.</p>
</header>
<main>
    <?php if (!$promocoes): ?>
        <p>Nenhuma promoção disponível no momento.</p>
    <?php else: ?>
        <div class="grade">
            <?php foreach ($promocoes as $id => $imagem): ?>
                <article class="card<?= $id === $destaque ? ' destaque' : '' ?>">
                    <img src="<?= htmlspecialchars($imagem, ENT_QUOTES, 'UTF-8') ?>" alt="Promoção <?= $id ?>" loading="lazy">
                    <div class="conteudo">
                        <h2>Promoção <?= $id ?></h2>
                        <p>Conheça esta oportunidade do Grupo THX.</p>
                        <?php if ($id !== $destaque): ?>
                            <a class="botao" href="?promo=<?= $id ?>">Ver em destaque</a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<footer>Grupo THX — Soluções para o seu negócio</footer>
</body>
</html>
