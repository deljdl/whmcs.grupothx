
<?php

require_once __DIR__ . '/../../init.php';
require_once __DIR__ . '/../../tarifasTHX.php';

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
        :root { --blue:#173b68;--navy:#0e2440;--red:#e63238;--ink:#20334c;--muted:#607187;--line:#e5ebf3;--bg:#f5f7fb; }
        * {box-sizing:border-box}
        html {scroll-behavior:smooth}
        body {margin:0;color:var(--ink);background:var(--bg);font-family:Arial,Helvetica,sans-serif;line-height:1.65}
        a {color:var(--blue)}
        .container {width:min(1140px,calc(100% - 36px));margin:auto}
        .notice {background:var(--red);color:#fff;font-size:12px;text-align:center;padding:7px 12px;letter-spacing:.02em}
        .site-header {background:#fff;border-bottom:1px solid var(--line);box-shadow:0 3px 18px #102b4d08;position:relative;z-index:5}
        .nav {display:flex;align-items:center;justify-content:space-between;gap:20px;min-height:82px}
        .brand img {display:block;width:120px;max-height:62px;object-fit:contain}
        .nav-links {display:flex;align-items:center;gap:26px}
        .nav-links a {text-decoration:none;color:var(--ink);font-size:14px;font-weight:600;transition:color .2s}
        .nav-links a:hover {color:var(--red)}
        .nav-links .nav-cta {color:#fff;background:var(--blue);padding:10px 18px;border-radius:8px}
        .nav-links .nav-cta:hover {color:#fff;background:#25558b}
        .mobile-menu {display:none}
        .hero {position:relative;overflow:hidden;background:linear-gradient(125deg,#0b1e36 0%,#173b68 70%,#255c95 100%);color:#fff}
        .hero:before,.hero:after {content:"";position:absolute;border:1px solid #ffffff1a;border-radius:50%;pointer-events:none}
        .hero:before {width:620px;height:620px;right:-210px;top:-380px}
        .hero:after {width:380px;height:380px;left:-240px;bottom:-290px}
        .hero .container {position:relative;z-index:1;padding:85px 0 90px;max-width:1140px}
        .eyebrow {display:inline-block;text-transform:uppercase;letter-spacing:.18em;color:#8ec6ff;font-weight:700;font-size:12px;margin-bottom:15px}
        .hero h1 {font-size:clamp(32px,5vw,56px);line-height:1.12;letter-spacing:-.035em;max-width:780px;margin:0 0 20px}
        .hero p {color:#d6e5f5;max-width:650px;font-size:clamp(16px,2vw,19px);margin:0 0 30px}
        .actions {display:flex;gap:12px;flex-wrap:wrap}
        .btn {display:inline-flex;align-items:center;justify-content:center;min-height:46px;border-radius:8px;padding:12px 23px;text-decoration:none;font-weight:700;font-size:14px;transition:transform .2s,background .2s,box-shadow .2s}
        .btn:hover {transform:translateY(-2px)}
        .btn-light {background:#fff;color:var(--blue)}
        .btn-light:hover {background:#e7f1fc}
        .btn-outline {border:1px solid #ffffff70;color:#fff}
        .btn-outline:hover {background:#ffffff15}
        .btn-primary {background:var(--blue);color:#fff}
        .btn-primary:hover {background:#28568e;color:#fff}
        main {padding:55px 0 76px}
        .section-heading {margin:0 0 27px}
        .section-heading .eyebrow {color:var(--red);margin-bottom:8px}
        .section-heading h2 {font-size:clamp(25px,3vw,34px);letter-spacing:-.025em;line-height:1.25;margin:0 0 10px;color:var(--navy)}
        .section-heading p {color:var(--muted);margin:0}
        .destaque {background:#fff;border:1px solid var(--line);border-radius:16px;overflow:hidden;box-shadow:0 15px 50px #102b4d0b}
        .destaque-imagem {width:100%;max-height:430px;object-fit:contain;display:block;background:#f9fbfe}
        .destaque-conteudo {padding:clamp(24px,5vw,50px);overflow-wrap:anywhere;font-size:16px;line-height:1.8}
        .destaque-conteudo>:first-child {margin-top:0}
        .destaque-conteudo>:last-child {margin-bottom:0}
        .destaque-conteudo img,.destaque-conteudo video {max-width:100%;height:auto}
        .destaque-conteudo iframe {max-width:100%}
        .destaque-conteudo h2,.destaque-conteudo h3 {color:var(--blue);line-height:1.3}
        .offer-list {margin-top:65px}
        .grade {display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:22px}
        .card {display:flex;flex-direction:column;background:#fff;border-radius:14px;border:1px solid var(--line);overflow:hidden;box-shadow:0 5px 22px #102b4d08;transition:transform .25s,box-shadow .25s}
        .card:hover {transform:translateY(-6px);box-shadow:0 18px 35px #102b4d1a}
        .card-imagem {background:#f9fbfd;display:block;overflow:hidden}
        .card-imagem img {width:100%;height:210px;object-fit:contain;display:block;transition:transform .4s}
        .card:hover img {transform:scale(1.035)}
        .card-conteudo {display:flex;align-items:flex-start;flex:1;flex-direction:column;padding:25px}
        .card h2,.card h3 {font-size:20px;line-height:1.35;margin:0 0 12px;color:var(--navy)}
        .card p {font-size:14px;color:var(--muted);margin:0 0 24px;flex:1}
        .card .btn {min-height:40px;padding:10px 17px;font-size:13px}
        .other .card-imagem img {height:150px}
        .other .card-conteudo {padding:20px}
        .other .card h3 {font-size:18px}
        .steps {margin-top:72px;padding:38px;background:#fff;border:1px solid var(--line);border-radius:16px}
        .steps-grid {display:grid;grid-template-columns:repeat(3,1fr);gap:25px}
        .step-number {font-size:13px;color:var(--red);font-weight:800;letter-spacing:.12em}
        .step h3 {font-size:18px;color:var(--navy);margin:7px 0}
        .step p {margin:0;color:var(--muted);font-size:14px}
        .bottom-cta {margin-top:60px;padding:45px;background:linear-gradient(120deg,#173b68,#28588b);color:#fff;border-radius:16px;display:flex;justify-content:space-between;align-items:center;gap:25px}
        .bottom-cta h2 {font-size:clamp(23px,3vw,31px);line-height:1.25;margin:0 0 8px}
        .bottom-cta p {margin:0;color:#d8e9fa}
        .bottom-cta .btn {flex-shrink:0}
        .vazio {background:#fff;border:1px solid var(--line);border-radius:14px;text-align:center;padding:40px;color:var(--muted)}
        .site-footer {background:#0d213a;color:#b6c8dc;padding:40px 0 24px}
        .footer-content {display:flex;gap:25px;justify-content:space-between;align-items:center;border-bottom:1px solid #ffffff20;padding-bottom:25px;margin-bottom:20px}
        .footer-content p {margin:4px 0 0;font-size:13px}
        .footer-content img {width:105px;background:#fff;border-radius:6px;padding:5px}
        .footer-links {display:flex;gap:20px}
        .footer-links a {color:#d9e6f4;text-decoration:none;font-size:13px}
        .copyright {font-size:12px;color:#9bb0c7}
        @media (prefers-reduced-motion:no-preference) {
            .hero .container {animation:rise .65s ease-out both}
            @keyframes rise {from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
        }
        @media(max-width:900px) {.grade {grid-template-columns:repeat(2,minmax(0,1fr))}.bottom-cta {align-items:flex-start;flex-direction:column}}
        @media(max-width:650px) {
            .container {width:calc(100% - 30px)}
            .nav {min-height:70px}
            .nav-links {display:none}
            .mobile-menu {display:block;position:relative}
            .mobile-menu summary {list-style:none;cursor:pointer;padding:8px 11px;border:1px solid #ccd7e5;border-radius:6px;font-size:23px;line-height:1}
            .mobile-menu summary::-webkit-details-marker {display:none}
            .mobile-menu[open] .mobile-links {display:grid}
            .mobile-links {position:absolute;right:0;top:44px;width:210px;padding:12px;background:#fff;border:1px solid var(--line);border-radius:9px;box-shadow:0 12px 25px #102b4d25;z-index:10}
            .mobile-links a {padding:10px;text-decoration:none;color:var(--ink);font-weight:600;font-size:14px}
            .hero .container {padding:60px 0 66px}
            .hero h1 {font-size:clamp(30px,9vw,42px)}
            main {padding:38px 0 55px}
            .grade,.steps-grid {grid-template-columns:1fr}
            .card-imagem img,.other .card-imagem img {height:auto;max-height:270px}
            .steps {padding:25px}
            .bottom-cta {margin-top:40px;padding:28px}
            .actions .btn {width:100%}
            .footer-content {align-items:flex-start;flex-direction:column}
        }
    </style>
</head>
<body>

<div class="notice">Grupo THX &nbsp; | &nbsp; Tecnologia, criação e soluções para empresas</div>
<header class="site-header">
    <div class="container nav">
        <a class="brand" href="<?= $esc(rtrim(HTTP_WHMCS, '/') . '/') ?>" aria-label="Grupo THX — início">
            <img src="<?= $esc(rtrim(HTTP_WHMCS, '/') . '/assets/img/logo.png') ?>" alt="Grupo THX">
        </a>
        <nav class="nav-links" aria-label="Navegação principal">
            <a href="<?= $esc($baseUrl) ?>">Promoções</a>
            <a href="#como-funciona">Como funciona</a>
            <a href="<?= $esc(rtrim(HTTP_WHMCS, '/') . '/clientarea.php') ?>">Minha conta</a>
            <a class="nav-cta" href="https://propostas.grupothx.com.br/">Solicitar proposta</a>
        </nav>
        <details class="mobile-menu">
            <summary aria-label="Abrir menu">&#9776;</summary>
            <nav class="mobile-links" aria-label="Navegação móvel">
                <a href="<?= $esc($baseUrl) ?>">Promoções</a>
                <a href="#como-funciona">Como funciona</a>
                <a href="<?= $esc(rtrim(HTTP_WHMCS, '/') . '/clientarea.php') ?>">Minha conta</a>
                <a href="https://propostas.grupothx.com.br/">Solicitar proposta</a>
            </nav>
        </details>
    </div>
</header>
<section class="hero">
    <div class="container">
        <span class="eyebrow">Grupo THX · Soluções digitais</span>
        <?php if ($promocao): ?>
            <h1><?= $esc($promocao->titulo) ?></h1>
            <p><?= $esc($promocao->descricao) ?></p>
        <?php else: ?>
            <h1>Ideias que transformam negócios em resultados.</h1>
            <p>Sites profissionais, lojas virtuais e sistemas sob medida. Descubra as soluções e oportunidades que preparamos para sua empresa.</p>
        <?php endif; ?>
        <div class="actions">
            <a class="btn btn-light" href="<?= $esc($promocao && !empty($promocao->botao_url) ? $promocao->botao_url : 'https://propostas.grupothx.com.br/') ?>">Solicitar proposta sem compromisso</a>
            <a class="btn btn-outline" href="#solucoes">Conhecer soluções</a>
        </div>
    </div>
</section>

<main class="container" id="solucoes">

    <?php if ($promocao): ?>

        <div class="section-heading"><span class="eyebrow">Uma oportunidade para você</span><h2>Conheça esta solução</h2><p>Descubra como podemos ajudar sua empresa a evoluir.</p></div>
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

        <section class="offer-list <?= $promocao ? 'other' : 'all' ?>">
            <div class="section-heading">
                <span class="eyebrow"><?= $promocao ? 'Explore mais' : 'Oportunidades' ?></span>

            <h2>
                <?= $promocao
                    ? 'Conheça também nossas outras soluções'
                    : 'Conheça nossas soluções e promoções' ?>
            </h2>
                <p>Conheça serviços e oportunidades para dar o próximo passo.</p>
            </div>

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

                            <a class="btn btn-primary" href="<?= $esc($url) ?>">
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

    <section id="como-funciona" class="steps">
        <div class="section-heading">
            <span class="eyebrow">Simples e transparente</span>
            <h2>Seu projeto, do jeito certo</h2>
            <p>Um processo pensado para transformar ideias em soluções.</p>
        </div>
        <div class="steps-grid">
            <div class="step"><span class="step-number">01 / CONVERSA</span><h3>Conte o que você precisa</h3><p>Entendemos seu objetivo, suas prioridades e o momento da sua empresa.</p></div>
            <div class="step"><span class="step-number">02 / PROPOSTA</span><h3>Receba uma solução</h3><p>Apresentamos uma proposta personalizada, sem compromisso.</p></div>
            <div class="step"><span class="step-number">03 / REALIZAÇÃO</span><h3>Transforme sua ideia</h3><p>Com tudo alinhado, partimos para a execução do seu projeto.</p></div>
        </div>
    </section>

    <section class="bottom-cta">
        <div><h2>Vamos tirar sua ideia do papel?</h2><p>Converse com a Grupo THX e solicite uma proposta personalizada.</p></div>
        <a class="btn btn-light" href="https://propostas.grupothx.com.br/">Quero solicitar uma proposta</a>
    </section>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-content">
            <div><img src="<?= $esc(rtrim(HTTP_WHMCS, '/') . '/assets/img/logo.png') ?>" alt="Grupo THX"><p>Tecnologia, criação e soluções para o seu negócio.</p></div>
            <nav class="footer-links" aria-label="Links do rodapé">
                <a href="<?= $esc($baseUrl) ?>">Promoções</a>
                <a href="<?= $esc(rtrim(HTTP_WHMCS, '/') . '/clientarea.php') ?>">Minha conta</a>
            </nav>
        </div>
        <div class="copyright">&copy; <?= date('Y') ?> Grupo THX. Todos os direitos reservados.</div>
    </div>
</footer>
<script src="//grupothx.com/atendimento-online/apiTHX/scriptAtendimentoOnlineTodosOsSitesJs.php"></script>
</body>
</html>
