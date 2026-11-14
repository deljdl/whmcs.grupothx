
<?php

$slug = $_GET['slug'] ?? '';
$token = $_GET['token'] ?? '';

$promocao = $slug !== '' ? getPromocao($slug) : null;

if ($slug !== '' && (!$promocao || !$promocao->ativo)) {
    http_response_code(404);
    die('Promoção não encontrada.');
}

$promocoes = getPromocoes($promocao->id ?? 0);

$esc = static function ($valor) {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
};

$baseUrl = rtrim(HTTP_WHMCS, '/') . '/thx/promocao';

$urlPromocao = static function ($item) use ($baseUrl, $token) {
    return $baseUrl . '/' . rawurlencode($item->slug)
        . ($token !== '' ? '/' . rawurlencode($token) : '');
};

$imagemPromocao = static function ($item) use ($baseUrl) {
    return !empty($item->imagem)
        ? $baseUrl . '/' . rawurlencode($item->imagem)
        : '';
};

// SEO
$titulo = $promocao
    ? ($promocao->seo_titulo ?: $promocao->titulo)
    : 'Promoções e Serviços | Grupo THX';

$descricao = $promocao
    ? ($promocao->seo_descricao ?: $promocao->descricao)
    : 'Conheça as soluções e promoções do Grupo THX em criação de sites, lojas virtuais, sistemas web e serviços digitais.';

$canonical = $baseUrl . ($promocao ? '/' . $promocao->slug : '');

$imagemSeo = $promocao
    ? $imagemPromocao($promocao)
    : (!empty($promocoes[0]) ? $imagemPromocao($promocoes[0]) : '');

$seoTitulo = $titulo;

if (!str_contains(mb_strtolower($seoTitulo), 'grupo thx')) {
    $seoTitulo .= ' | Grupo THX';
}

?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= $esc($seoTitulo) ?></title>
    <meta name="description" content="<?= $esc($descricao) ?>">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="<?= $esc($canonical) ?>">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="Grupo THX">
    <meta property="og:title" content="<?= $esc($seoTitulo) ?>">
    <meta property="og:description" content="<?= $esc($descricao) ?>">
    <meta property="og:url" content="<?= $esc($canonical) ?>">

    <?php if ($imagemSeo): ?>
        <meta property="og:image" content="<?= $esc($imagemSeo) ?>">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="<?= $esc($imagemSeo) ?>">
    <?php else: ?>
        <meta name="twitter:card" content="summary">
    <?php endif; ?>

    <meta name="twitter:title" content="<?= $esc($seoTitulo) ?>">
    <meta name="twitter:description" content="<?= $esc($descricao) ?>">

    <style>
        :root {
            --azul: #1a3c6a;
            --azul-hover: #28568e;
            --texto: #26384d;
            --cinza: #64748b;
            --fundo: #f4f7fb;
            --borda: #e2e8f0;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--texto);
            background: var(--fundo);
            line-height: 1.6;
        }

        a { color: var(--azul); }

        .container {
            width: min(1160px, calc(100% - 36px));
            margin: auto;
        }

        .topo {
            background: linear-gradient(135deg, #102c50, #24538b);
            color: #fff;
            padding: 52px 0;
            text-align: center;
        }

        .topo h1 {
            margin: 0 0 12px;
            font-size: clamp(28px, 4vw, 42px);
            line-height: 1.2;
        }

        .topo p {
            margin: auto;
            max-width: 760px;
            font-size: clamp(15px, 2vw, 18px);
            color: #dae8f7;
        }

        .topo a { color: #fff; }

        main {
            padding-top: 36px;
            padding-bottom: 55px;
        }

        .destaque {
            background: #fff;
            border: 1px solid var(--borda);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 8px 35px rgba(20,48,84,.06);
        }

        .destaque-imagem {
            width: 100%;
            max-height: 520px;
            object-fit: contain;
            display: block;
            background: #fff;
        }

        .destaque-conteudo {
            padding: clamp(22px, 4vw, 42px);
            font-size: 16px;
            line-height: 1.8;
            overflow-wrap: anywhere;
        }

        .destaque-conteudo > :first-child { margin-top: 0; }
        .destaque-conteudo > :last-child { margin-bottom: 0; }

        .destaque-conteudo img,
        .destaque-conteudo video {
            max-width: 100%;
            height: auto;
        }

        .destaque-conteudo iframe {
            max-width: 100%;
        }

        .destaque-conteudo h2,
        .destaque-conteudo h3 {
            color: var(--azul);
            line-height: 1.3;
        }

        .secao-titulo {
            margin: 0 0 26px;
            font-size: clamp(23px, 3vw, 30px);
            text-align: center;
            line-height: 1.3;
        }

        .outras-promocoes {
            margin-top: 55px;
        }

        .grade {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 24px;
        }

        .card {
            background: #fff;
            border: 1px solid var(--borda);
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform .2s, box-shadow .2s;
            box-shadow: 0 4px 18px rgba(20,48,84,.05);
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(20,48,84,.12);
        }

        .card-imagem {
            display: block;
            background: #fff;
        }

        .card-imagem img {
            display: block;
            width: 100%;
            height: 220px;
            object-fit: contain;
        }

        .card-conteudo {
            padding: 23px;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .card h2,
        .card h3 {
            font-size: 20px;
            line-height: 1.35;
            margin: 0 0 12px;
        }

        .card p {
            color: var(--cinza);
            font-size: 14px;
            line-height: 1.7;
            margin: 0 0 22px;
            flex: 1;
        }

        .botao {
            display: inline-block;
            padding: 12px 22px;
            border-radius: 7px;
            background: var(--azul);
            color: #fff;
            font-weight: bold;
            font-size: 14px;
            text-align: center;
            text-decoration: none;
            transition: background .2s;
        }

        .botao:hover {
            background: var(--azul-hover);
            color: #fff;
        }

        .outras-promocoes .card-imagem img {
            height: 160px;
        }

        .outras-promocoes .card h3 {
            font-size: 18px;
        }

        .outras-promocoes .card-conteudo {
            padding: 18px;
        }

        .vazio {
            padding: 55px 20px;
            background: #fff;
            border: 1px solid var(--borda);
            border-radius: 12px;
            text-align: center;
            color: var(--cinza);
        }

        footer {
            background: #eaf0f7;
            text-align: center;
            padding: 28px 18px;
            color: #64748b;
            font-size: 13px;
        }

        footer a {
            text-decoration: none;
            font-weight: bold;
        }

        @media (max-width: 900px) {
            .grade {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .container { width: calc(100% - 28px); }

            .topo { padding: 36px 0; }

            main {
                padding-top: 20px;
                padding-bottom: 35px;
            }

            .grade {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .card-imagem img,
            .outras-promocoes .card-imagem img {
                height: auto;
                max-height: 300px;
            }

            .card-conteudo {
                padding: 20px;
            }

            .destaque-conteudo {
                padding: 20px;
                font-size: 15px;
            }

            .outras-promocoes { margin-top: 38px; }

            .botao { width: 100%; }
        }
    </style>
</head>
<body>

<header class="topo">
    <div class="container">

        <?php if ($promocao): ?>

            <h1><?= $esc($promocao->titulo) ?></h1>
            <p><?= $esc($promocao->descricao) ?></p>

        <?php else: ?>

            <h1>Promoções e Serviços</h1>
            <p>
                Soluções digitais, tecnologia e oportunidades
                para impulsionar o seu negócio.
            </p>

        <?php endif; ?>

    </div>
</header>

<main class="container">

    <?php if ($promocao): ?>

        <article class="destaque">

            <?php if ($imagemPromocao($promocao)): ?>
                <img
                    class="destaque-imagem"
                    src="<?= $esc($imagemPromocao($promocao)) ?>"
                    alt="<?= $esc($promocao->titulo) ?>"
                    width="1200"
                    loading="eager"
                >
            <?php endif; ?>

            <?php if (trim((string)$promocao->conteudo) !== ''): ?>
                <div class="destaque-conteudo">
                    <?= $promocao->conteudo ?>
                </div>
            <?php endif; ?>

        </article>

    <?php endif; ?>


    <?php if (count($promocoes)): ?>

        <section class="<?= $promocao ? 'outras-promocoes' : 'todas-promocoes' ?>">

            <h2 class="secao-titulo">
                <?= $promocao
                    ? 'Conheça também nossas outras soluções'
                    : 'Conheça nossas soluções e promoções' ?>
            </h2>

            <div class="grade">

                <?php foreach ($promocoes as $item): ?>

                    <?php
                        $url = $urlPromocao($item);
                        $img = $imagemPromocao($item);
                    ?>

                    <article class="card">

                        <?php if ($img): ?>
                            <a class="card-imagem" href="<?= $esc($url) ?>">
                                <img
                                    src="<?= $esc($img) ?>"
                                    alt="<?= $esc($item->titulo) ?>"
                                    loading="lazy"
                                >
                            </a>
                        <?php endif; ?>

                        <div class="card-conteudo">

                            <?php if ($promocao): ?>
                                <h3><?= $esc($item->titulo) ?></h3>
                            <?php else: ?>
                                <h2><?= $esc($item->titulo) ?></h2>
                            <?php endif; ?>

                            <p><?= $esc($item->descricao) ?></p>

                            <a class="botao" href="<?= $esc($url) ?>">
                                Saiba mais
                            </a>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>
        </section>

    <?php elseif (!$promocao): ?>

        <div class="vazio">
            <h2>Nenhuma promoção disponível no momento</h2>
            <p>Em breve teremos novas oportunidades para você.</p>
        </div>

    <?php endif; ?>

</main>

<footer>
    &copy; <?= date('Y') ?> Grupo THX — Soluções para o seu negócio
</footer>
<script src="//grupothx.com/atendimento-online/apiTHX/scriptAtendimentoOnlineTodosOsSitesJs.php"></script>
</body>
</html>
