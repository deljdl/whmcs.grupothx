
<?php

require_once __DIR__ . '/../../init.php';
require_once __DIR__ . '/../../tarifasTHX.php';

$slug = $_GET['slug'] ?? '';
$token = $_GET['token'] ?? '';

$dadosToken = $token !== '' ? explode('|', (string) decriptX($token), 2) : [];
$clienteIdentificado = isset($dadosToken[0], $dadosToken[1])
    && ctype_digit($dadosToken[0])
    && (int) $dadosToken[0] > 0;

$destinoProposta = $clienteIdentificado
    ? 'https://propostas.grupothx.com.br/'
    : '#solicitar-proposta';

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

    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-KJLKHV8C');</script>
    <!-- End Google Tag Manager -->

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
        --blue: #173b68;
        --blue-dark: #0e2440;
        --blue-soft: #285b95;
        --red: #e63238;
        --text: #20334c;
        --muted: #607187;
        --line: #e5ebf3;
        --bg: #f5f7fb;
        --shadow: 0 12px 38px rgba(16, 43, 77, .08);
        --radius: 16px;
    }

    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }

    body {
        margin: 0;
        color: var(--text);
        background: var(--bg);
        font-family: Arial, Helvetica, sans-serif;
        line-height: 1.65;
    }

    a { color: var(--blue); }
    img { max-width: 100%; }

    .container {
        width: min(1140px, calc(100% - 36px));
        margin: auto;
    }

    .notice {
        background: var(--red);
        color: #fff;
        text-align: center;
        font-size: 12px;
        padding: 7px 12px;
        letter-spacing: .02em;
    }

    .site-header {
        position: sticky;
        top: 0;
        z-index: 30;
        background: rgba(255,255,255,.96);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid var(--line);
        box-shadow: 0 3px 18px rgba(16, 43, 77, .05);
    }

    .nav {
        min-height: 82px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
    }

    .brand img {
        width: 120px;
        max-height: 62px;
        object-fit: contain;
        display: block;
    }

    .nav-links {
        display: flex;
        align-items: center;
        gap: 24px;
    }

    .nav-links a,
    .mobile-links a {
        text-decoration: none;
        color: var(--text);
        font-size: 14px;
        font-weight: 700;
        transition: color .2s ease;
    }

    .nav-links a:hover,
    .mobile-links a:hover { color: var(--red); }

    .nav-cta {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--blue);
        color: #fff !important;
        padding: 10px 18px;
        border-radius: 9px;
        box-shadow: 0 10px 20px rgba(23,59,104,.18);
    }

    .nav-cta:hover { background: var(--blue-soft); }

    .mobile-menu { display: none; }

    .hero {
        position: relative;
        overflow: hidden;
        color: #fff;
        background:
            radial-gradient(circle at top right, rgba(255,255,255,.14), transparent 24%),
            linear-gradient(128deg, #0b1e36 0%, #173b68 68%, #2a629a 100%);
    }

    .hero::before,
    .hero::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        border: 1px solid rgba(255,255,255,.12);
        pointer-events: none;
    }

    .hero::before {
        width: 640px;
        height: 640px;
        right: -210px;
        top: -410px;
    }

    .hero::after {
        width: 360px;
        height: 360px;
        left: -180px;
        bottom: -220px;
    }

    .hero .container {
        position: relative;
        z-index: 1;
        padding: 82px 0 92px;
        animation: fadeUp .8s ease both;
    }

    .eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-transform: uppercase;
        letter-spacing: .16em;
        color: #8ec6ff;
        font-weight: 800;
        font-size: 12px;
        margin-bottom: 14px;
    }

    .hero h1 {
        font-size: clamp(32px, 5vw, 56px);
        line-height: 1.12;
        letter-spacing: -.035em;
        margin: 0 0 18px;
        max-width: 800px;
    }

    .hero p {
        margin: 0;
        max-width: 680px;
        color: #d9e8f7;
        font-size: clamp(16px, 2vw, 19px);
    }

    .hero-points {
        margin: 26px 0 0;
        padding: 0;
        list-style: none;
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .hero-points li {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 14px;
        border-radius: 999px;
        background: rgba(255,255,255,.08);
        border: 1px solid rgba(255,255,255,.10);
        color: #f1f7ff;
        font-size: 13px;
    }

    .actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 28px;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        min-height: 46px;
        padding: 12px 22px;
        border-radius: 9px;
        text-decoration: none;
        font-weight: 700;
        font-size: 14px;
        transition: transform .22s ease, box-shadow .22s ease, background .22s ease, color .22s ease;
    }

    .btn:hover { transform: translateY(-2px); }

    .btn-light {
        background: #fff;
        color: var(--blue);
        box-shadow: 0 12px 24px rgba(0,0,0,.12);
    }

    .btn-light:hover { background: #edf4fb; }

    .btn-outline {
        border: 1px solid rgba(255,255,255,.5);
        color: #fff;
    }

    .btn-outline:hover { background: rgba(255,255,255,.08); }

    .btn-primary {
        background: var(--blue);
        color: #fff;
    }

    .btn-primary:hover {
        background: var(--blue-soft);
        color: #fff;
        box-shadow: 0 12px 24px rgba(23,59,104,.16);
    }

    main {
        padding: 56px 0 76px;
    }

    .section-heading {
        margin: 0 0 28px;
        animation: fadeUp .8s ease both;
    }

    .section-heading .eyebrow {
        color: var(--red);
        margin-bottom: 10px;
    }

    .section-heading h2 {
        margin: 0 0 10px;
        color: var(--blue-dark);
        font-size: clamp(25px, 3vw, 34px);
        line-height: 1.2;
        letter-spacing: -.02em;
    }

    .section-heading p {
        margin: 0;
        color: var(--muted);
    }

    .destaque {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow);
        animation: fadeUp .8s ease both;
    }

    .destaque-imagem {
        width: 100%;
        max-height: 430px;
        object-fit: contain;
        background: #f9fbfe;
        display: block;
    }

    .destaque-conteudo {
        padding: clamp(24px, 5vw, 50px);
        font-size: 16px;
        line-height: 1.82;
        overflow-wrap: anywhere;
    }

    .destaque-conteudo > :first-child { margin-top: 0; }
    .destaque-conteudo > :last-child { margin-bottom: 0; }

    .destaque-conteudo h2,
    .destaque-conteudo h3 {
        color: var(--blue);
        line-height: 1.3;
    }

    .destaque-conteudo img,
    .destaque-conteudo iframe,
    .destaque-conteudo video {
        max-width: 100%;
        height: auto;
    }

    .offer-list { margin-top: 64px; }

    .grade {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 22px;
    }

    .card {
        display: flex;
        flex-direction: column;
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 6px 24px rgba(16,43,77,.06);
        transition: transform .28s ease, box-shadow .28s ease, border-color .28s ease;
        opacity: 0;
        transform: translateY(22px);
        animation: fadeUp .75s ease forwards;
    }

    .card:nth-child(2) { animation-delay: .08s; }
    .card:nth-child(3) { animation-delay: .16s; }
    .card:nth-child(4) { animation-delay: .24s; }
    .card:nth-child(5) { animation-delay: .32s; }
    .card:nth-child(6) { animation-delay: .40s; }

    .card:hover {
        transform: translateY(-7px);
        box-shadow: 0 18px 36px rgba(16,43,77,.14);
        border-color: #d2deec;
    }

    .card-imagem {
        display: block;
        background: #f9fbfd;
        overflow: hidden;
    }

    .card-imagem img {
        width: 100%;
        height: 210px;
        object-fit: contain;
        display: block;
        transition: transform .35s ease;
    }

    .card:hover .card-imagem img { transform: scale(1.03); }

    .card-conteudo {
        display: flex;
        flex: 1;
        flex-direction: column;
        align-items: flex-start;
        padding: 24px;
    }

    .card h2,
    .card h3 {
        margin: 0 0 12px;
        color: var(--blue-dark);
        line-height: 1.35;
        font-size: 20px;
    }

    .card p {
        margin: 0 0 22px;
        color: var(--muted);
        font-size: 14px;
        flex: 1;
    }

    .other .card-imagem img { height: 150px; }
    .other .card-conteudo { padding: 20px; }
    .other .card h3 { font-size: 18px; }

    .process {
        margin-top: 74px;
        padding: 38px;
        background: linear-gradient(180deg, #fff 0%, #fbfcfe 100%);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        box-shadow: 0 8px 30px rgba(16,43,77,.05);
        animation: fadeUp .8s ease both;
    }

    .process-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
    }

    .step {
        padding: 24px;
        border-radius: 14px;
        background: #fff;
        border: 1px solid var(--line);
        transition: transform .24s ease, box-shadow .24s ease;
    }

    .step:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 26px rgba(16,43,77,.08);
    }

    .step-icon {
        width: 54px;
        height: 54px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #edf4fb 0%, #dfeefe 100%);
        color: var(--blue);
        font-size: 21px;
        margin-bottom: 16px;
    }

    .step-number {
        display: block;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .14em;
        color: var(--red);
        margin-bottom: 8px;
    }

    .step h3 {
        margin: 0 0 10px;
        color: var(--blue-dark);
        font-size: 18px;
    }

    .step p {
        margin: 0;
        color: var(--muted);
        font-size: 14px;
    }

    .mini-cta {
        margin-top: 54px;
        padding: 30px;
        background: #fff;
        border: 1px solid var(--line);
        border-radius: var(--radius);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        box-shadow: 0 10px 24px rgba(16,43,77,.05);
        animation: fadeUp .8s ease both;
    }

    .mini-cta h2 {
        margin: 0 0 8px;
        color: var(--blue-dark);
        font-size: clamp(22px, 3vw, 30px);
        line-height: 1.2;
    }

    .mini-cta p {
        margin: 0;
        color: var(--muted);
    }

    .vazio {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 14px;
        text-align: center;
        padding: 40px;
        color: var(--muted);
    }

    .site-footer {
        background: #fff;
        border-top: 1px solid var(--line);
        padding: 34px 0 24px;
    }

    .footer-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        padding-bottom: 18px;
    }

    .footer-brand {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .footer-brand img {
        width: 90px;
        display: block;
    }

    .footer-brand p {
        margin: 0;
        color: var(--muted);
        font-size: 13px;
    }

    .footer-links {
        display: flex;
        flex-wrap: wrap;
        gap: 18px;
    }

    .footer-links a {
        text-decoration: none;
        color: var(--text);
        font-size: 13px;
        font-weight: 700;
    }

    .copyright {
        padding-top: 16px;
        border-top: 1px solid var(--line);
        font-size: 12px;
        color: #8899ad;
    }

    @keyframes fadeUp {
        from {
            opacity: 0;
            transform: translateY(22px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 930px) {
        .grade,
        .process-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .mini-cta {
            flex-direction: column;
            align-items: flex-start;
        }
    }

    @media (max-width: 700px) {
        .nav-links { display: none; }

        .mobile-menu {
            display: block;
            position: relative;
        }

        .mobile-menu summary {
            list-style: none;
            cursor: pointer;
            font-size: 24px;
            line-height: 1;
            padding: 8px 11px;
            border-radius: 8px;
            border: 1px solid #ccd7e5;
            background: #fff;
        }

        .mobile-menu summary::-webkit-details-marker { display: none; }

        .mobile-links {
            position: absolute;
            top: 44px;
            right: 0;
            width: 220px;
            display: grid;
            gap: 2px;
            padding: 10px;
            border-radius: 10px;
            border: 1px solid var(--line);
            background: #fff;
            box-shadow: 0 14px 28px rgba(16,43,77,.18);
        }

        .mobile-links a { padding: 10px; }

        .hero .container { padding: 62px 0 72px; }
        main { padding: 40px 0 56px; }
        .container { width: calc(100% - 30px); }

        .grade,
        .process-grid {
            grid-template-columns: 1fr;
        }

        .card-imagem img,
        .other .card-imagem img {
            height: auto;
            max-height: 270px;
        }

        .card-conteudo,
        .other .card-conteudo,
        .destaque-conteudo,
        .process,
        .mini-cta {
            padding: 22px;
        }

        .actions .btn { width: 100%; }
        .hero-points { flex-direction: column; align-items: flex-start; }
        .footer-content,
        .footer-brand { flex-direction: column; align-items: flex-start; }
    }
  
    /* Identidade visual Grupo THX */
    body { background: #eaf0f7; }
    .hero { background: radial-gradient(circle at 89% 18%, rgba(230,45,58,.20), transparent 31%),linear-gradient(128deg,#080e19,#102a4c 62%,#163e70); }
    .hero::before { border-color:rgba(230,45,58,.25); }
    .hero .eyebrow { color:#ff9ca3; }
    .notice { background:linear-gradient(90deg,#a91928,#eb303c,#aa192a); }
    .hero .btn-light { background:#ec3440;color:#fff;box-shadow:0 14px 28px rgba(236,52,64,.20); }
    .hero .btn-light:hover { background:#ca2030; }
    .section-heading .eyebrow { color:#d72d39; }
    .section-heading h2 { color:#111e31; }
    .destaque { border-top:4px solid #e63238; }
    .card { border-bottom:3px solid #1b467c; }
    .card:hover { border-bottom-color:#e63238; }
    .process { background:linear-gradient(135deg,#0b1729,#13375e 78%,#1c4e7e);border:0;box-shadow:0 18px 48px rgba(8,25,48,.18); }
    .process .section-heading h2,.process .step h3 { color:#fff; }
    .process .section-heading p,.process .step p { color:#c8d8ec; }
    .process .section-heading .eyebrow { color:#ff9a9d; }
    .process .step { background:rgba(255,255,255,.055);border-color:rgba(255,255,255,.12); }
    .process .step:hover { background:rgba(255,255,255,.10); }
    .process .step-icon { color:#fff;background:#e63238; }
    .process .step-number { color:#ff8d96; }
    .mini-cta { background:#122f53;border:0;color:#fff; }
    .mini-cta h2 { color:#fff; }
    .mini-cta p { color:#c9d7e8; }
    .mini-cta .btn { background:#e63238; }
    .mini-cta .btn:hover { background:#ca202d; }
    .benefits { padding:45px 0 5px; }
    .benefits-grid { display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px; }
    .benefit { padding:26px;border-radius:14px;background:#142d4c;color:#fff;border:1px solid rgba(255,255,255,.08);box-shadow:0 12px 34px rgba(16,43,77,.10);transition:transform .25s,box-shadow .25s; }
    .benefit:nth-child(2) { background:#1b426f; }
    .benefit:nth-child(3) { background:#9f2232; }
    .benefit:hover { transform:translateY(-6px);box-shadow:0 20px 38px rgba(16,43,77,.2); }
    .benefit i { font-size:25px;color:#fff;margin-bottom:15px;display:block; }
    .benefit h3 { color:#fff;font-size:19px;margin:0 0 8px; }
    .benefit p { color:#e2e9f4;font-size:14px;margin:0; }
    .lead-section { margin:58px 0 12px;padding:35px;border-radius:16px;background:#fff;display:grid;grid-template-columns:1fr 1.1fr;gap:32px;box-shadow:0 12px 38px rgba(16,43,77,.09);border-top:5px solid #e63238; }
    .lead-section h2 { font-size:clamp(25px,3vw,33px);line-height:1.2;margin:0 0 15px;color:#101e32; }
    .lead-section p { color:#596e88; }
    .lead-section label { font-size:13px;font-weight:700;display:block;margin-bottom:6px;color:#26384d; }
    .lead-section input,.lead-section textarea { display:block;width:100%;padding:12px 13px;border:1px solid #cbd7e5;border-radius:8px;font:inherit;color:#20334c;background:#f9fbfe; }
    .lead-section textarea { min-height:104px;resize:vertical; }
    .lead-section input:focus,.lead-section textarea:focus { outline:2px solid #3777b7;outline-offset:1px; }
    .lead-section .field { margin-bottom:14px; }
    .lead-section button { border:0;cursor:pointer;width:100%;background:#e63238;color:#fff; }
    .lead-section button:hover { background:#ba2430; }
    .lead-section small { display:block;margin-top:12px;color:#6a7c90;line-height:1.5; }
    .section-heading,.benefit,.lead-section { scroll-margin-top:110px; }
    @media(max-width:850px){ .benefits-grid { grid-template-columns:1fr; }.lead-section { grid-template-columns:1fr; } }
    @media(max-width:650px){ .lead-section {padding:23px;}.benefits {padding-top:30px;} }
    @media(prefers-reduced-motion:reduce){
        *,*::before,*::after {animation-duration:.01ms!important;transition-duration:.01ms!important;scroll-behavior:auto!important;}
    }

</style>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <!--Icons by X-Painel-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/Grupo-THX/xpainel-assets/icons.css" />
    <!--End Icons by X-Painel-->
</head>
<body>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KJLKHV8C"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->


<div class="notice">Grupo THX · Tecnologia, criação e soluções digitais para empresas</div>

<header class="site-header">
    <div class="container nav">
        <a class="brand" href="#" aria-label="Grupo THX - início">
            <img src="<?= $esc(rtrim(HTTP_WHMCS, '/') . '/assets/img/logo.png') ?>" alt="Grupo THX">
        </a>

        <nav class="nav-links" aria-label="Navegação principal">
            <!-- <a href="<?= $esc($baseUrl) ?>"><i class="fa-solid fa-bullhorn"></i> Promoções</a> -->
            <a href="#como-funciona"><i class="fa-solid fa-gears"></i> Como funciona</a>
            <!-- <a href="<?= $esc(rtrim(HTTP_WHMCS, '/') . '/clientarea.php') ?>"><i class="fa-solid fa-user"></i> Minha conta</a> -->
            <a class="nav-cta" href="<?= $esc($destinoProposta) ?>"><i class="fa-solid fa-file-signature"></i> Solicitar proposta</a>
        </nav>

        <details class="mobile-menu">
            <summary aria-label="Abrir menu"><i class="fa-solid fa-bars"></i></summary>
            <nav class="mobile-links" aria-label="Navegação móvel">
                <!-- <a href="<?= $esc($baseUrl) ?>">Promoções</a> -->
                <a href="#como-funciona">Como funciona</a>
                <!-- <a href="<?= $esc(rtrim(HTTP_WHMCS, '/') . '/clientarea.php') ?>">Minha conta</a> -->
                <a href="<?= $esc($destinoProposta) ?>">Solicitar proposta</a>
            </nav>
        </details>
    </div>
</header>

<section class="hero">
    <div class="container">
        <span class="eyebrow"><i class="fa-solid fa-microchip"></i> Grupo THX · Soluções em tecnologia</span>

        <?php if ($promocao): ?>
            <h1><?= $esc($promocao->titulo) ?></h1>
            <p><?= $esc($promocao->descricao) ?></p>
        <?php else: ?>
            <h1>Criação digital com estratégia, tecnologia e foco em resultado.</h1>
            <p>Desenvolvemos sites, lojas virtuais e soluções web sob medida para empresas que querem evoluir sua presença digital com profissionalismo.</p>
        <?php endif; ?>

        <div class="actions">
            <a class="btn btn-light" href="<?= $esc($destinoProposta) ?>"><i class="fa-solid fa-paper-plane"></i> Solicitar proposta sem compromisso</a>
        </div>
    </div>
</section>

<main class="container" id="solucoes">

    <?php if ($promocao): ?>
        <div class="section-heading">
            <span class="eyebrow"><i class="fa-solid fa-star"></i> Solução em destaque</span>
            <h2>Conheça esta oportunidade</h2>
            <p>Uma apresentação objetiva para você avaliar esta solução com tranquilidade.</p>
        </div>

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
                <span class="eyebrow"><i class="fa-solid fa-layer-group"></i> <?= $promocao ? 'Explore mais' : 'Soluções e oportunidades' ?></span>
                <h2><?= $promocao ? 'Conheça também outras soluções da Grupo THX' : 'Escolha a solução ideal para o seu momento' ?></h2>
                <p>Selecione uma opção e veja como podemos ajudar sua empresa a crescer com tecnologia.</p>
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
                                <i class="fa-solid fa-arrow-right"></i> Saiba mais
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


    <section class="benefits" aria-labelledby="benefits-title">
        <div class="section-heading">
            <span class="eyebrow"><i class="fa-solid fa-shield-halved"></i> Por que a Grupo THX</span>
            <h2 id="benefits-title">Tecnologia com propósito, do planejamento à entrega</h2>
            <p>Projetos desenvolvidos de acordo com as necessidades reais de cada negócio.</p>
        </div>
        <div class="benefits-grid">
            <article class="benefit"><i class="fa-solid fa-code"></i><h3>Desenvolvimento sob medida</h3><p>Sites e sistemas pensados para seus objetivos, com atenção à usabilidade, desempenho e evolução.</p></article>
            <article class="benefit"><i class="fa-solid fa-comments"></i><h3>Atendimento próximo</h3><p>Conversa direta, entendimento das prioridades e uma proposta clara para sua decisão.</p></article>
            <article class="benefit"><i class="fa-solid fa-chart-line"></i><h3>Foco no seu negócio</h3><p>Tecnologia aplicada para fortalecer sua presença digital e apoiar novos resultados.</p></article>
        </div>
    </section>

    <section id="como-funciona" class="process">
        <div class="section-heading">
            <span class="eyebrow"><i class="fa-solid fa-diagram-project"></i> Como funciona</span>
            <h2>O processo da sua proposta</h2>
            <p>Trabalhamos de forma clara, profissional e sem complicação.</p>
        </div>

        <div class="process-grid">
            <article class="step">
                <div class="step-icon"><i class="fa-solid fa-file-circle-plus"></i></div>
                <span class="step-number">01 / SOLICITAÇÃO</span>
                <h3>Você solicita sua proposta</h3>
                <p>Conte o que sua empresa precisa e compartilhe os objetivos do projeto. É simples e sem compromisso.</p>
            </article>

            <article class="step">
                <div class="step-icon"><i class="fa-solid fa-file-lines"></i></div>
                <span class="step-number">02 / APRESENTAÇÃO</span>
                <h3>Enviamos a proposta sem compromisso</h3>
                <p>Analisamos sua necessidade e apresentamos uma proposta com escopo, investimento e condições para sua avaliação.</p>
            </article>

            <article class="step">
                <div class="step-icon"><i class="fa-solid fa-rocket"></i></div>
                <span class="step-number">03 / APROVAÇÃO</span>
                <h3>Você aprova e iniciamos o projeto</h3>
                <p>Após sua aprovação, alinhamos o cronograma e iniciamos o desenvolvimento com acompanhamento em cada etapa.</p>
            </article>
        </div>
    </section>


    <?php if (!$clienteIdentificado): ?>
    <section id="solicitar-proposta" class="lead-section">
        <div>
            <span class="eyebrow" style="color:#d62e3b"><i class="fa-solid fa-paper-plane"></i> Faça seu pedido</span>
            <h2>Conte sobre o projeto que você tem em mente</h2>
            <p>Preencha os dados e envie sua solicitação diretamente ao WhatsApp comercial da Grupo THX. Nossa equipe poderá entender sua necessidade e preparar uma proposta sem compromisso.</p>
            <p><i class="fa-solid fa-lock" style="color:#d62e3b"></i> Seus dados serão usados para responder à solicitação.</p>
        </div>
        <form id="form-solicitar-proposta">
            <div class="field"><label for="lead-nome">Nome</label><input id="lead-nome" name="nome" type="text" autocomplete="name" maxlength="120" required></div>
            <div class="field"><label for="lead-email">E-mail</label><input id="lead-email" name="email" type="email" autocomplete="email" maxlength="180" required></div>
            <div class="field"><label for="lead-telefone">WhatsApp</label><input id="lead-telefone" name="telefone" type="tel" autocomplete="tel" maxlength="25" required></div>
            <div class="field"><label for="lead-mensagem">O que você precisa?</label><textarea id="lead-mensagem" name="mensagem" maxlength="1500" placeholder="Ex.: criar um site institucional, modernizar o atual ou desenvolver um sistema." required></textarea></div>
            <button type="submit" class="btn"><i class="fa-brands fa-whatsapp"></i> Continuar no WhatsApp</button>
            <small>Ao continuar, o WhatsApp abrirá com sua solicitação preenchida. Confirme o envio da mensagem para falar com nossa equipe.</small>
        </form>
    </section>
    <?php endif; ?>
    <section class="mini-cta">
        <div>
            <h2>Pronto para transformar sua ideia em projeto?</h2>
            <p>Solicite uma proposta e receba um atendimento profissional, com foco na melhor solução para o seu negócio.</p>
        </div>
        <a class="btn btn-primary" href="<?= $esc($destinoProposta) ?>">
            <i class="fa-solid fa-paper-plane"></i> Solicitar proposta
        </a>
    </section>
</main>

<!-- <footer class="site-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-brand">
                <img src="<?= $esc(rtrim(HTTP_WHMCS, '/') . '/assets/img/logo.png') ?>" alt="Grupo THX">
                <p>Tecnologia, criação e soluções digitais para o seu negócio.</p>
            </div>

            <nav class="footer-links" aria-label="Links do rodapé">
                <a href="<?= $esc($baseUrl) ?>">Promoções</a>
                <a href="#como-funciona">Como funciona</a>
                <a href="<?= $esc(rtrim(HTTP_WHMCS, '/') . '/clientarea.php') ?>">Minha conta</a>
            </nav>
        </div>

        <div class="copyright">&copy; <?= date('Y') ?> Grupo THX. Todos os direitos reservados.</div>
    </div>
</footer> -->
<script src="//grupothx.com/atendimento-online/apiTHX/scriptAtendimentoOnlineTodosOsSitesJs.php"></script>

<script>
(function () {
    const form = document.getElementById('form-solicitar-proposta');
    if (!form) return;
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const fields = new FormData(form);
        const texto = [
            'Solicitação de proposta - Grupo THX',
            'Campanha: <?= $esc($promocao->titulo ?? 'Promoções e Serviços') ?>',
            'URL: <?= $esc($canonical) ?>',
            'Nome: ' + fields.get('nome'),
            'E-mail: ' + fields.get('email'),
            'WhatsApp: ' + fields.get('telefone'),
            'Projeto: ' + fields.get('mensagem')
        ].join('\n');
        window.location.href = 'https://wa.me/551146550330?text=' + encodeURIComponent(texto);
    });
})();
</script>
</body>
</html>
