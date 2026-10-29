<!doctype html>
<html lang="en">
<head>
    <meta charset="{$charset}" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Área do cliente Grupo THX</title>


    

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Central Grupo THX">
    <meta property="og:title" content="Área do cliente Grupo THX">
    <meta property="og:description" content="Acesse seus produtos serviços e pagamentos.">
    <meta property="og:url" content="{$systemurl}{$smarty.server.REQUEST_URI}">
    <meta property="og:logo" content="{$systemurl}thx/assets/images/ogimage.png" />
    <meta property="og:image" content="{$systemurl}thx/assets/images/ogimage.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="description" content="Acesse seus produtos serviços e pagamentos.">

    <!-- Favicons-->
    <link rel="shortcut icon" href="{$systemurl}thx/assets/favicon/favicon.ico" type="image/x-icon"/>
    <link rel="apple-touch-icon" type="image/x-icon" sizes="192x192" href="{$systemurl}thx/assets/favicon/android-chrome-192x192.png">
    <link rel="apple-touch-icon" type="image/x-icon" sizes="512x114" href="{$systemurl}thx/assets/favicon/android-chrome-512x512.png">
    <link rel="apple-touch-icon" type="image/x-icon" sizes="32x32" href="{$systemurl}thx/assets/favicon/favicon-32x32.png">



    <meta name="twitter:card" content="summary_large_image">



    {include file="$template/includes/head.tpl"}
    {$headoutput}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('p').forEach(function (element) {
                if (/^Powered by\\s+WHMCompleteSolution/i.test(element.textContent.trim())) {
                    element.remove();
                }
            });

            document.querySelectorAll('.card-title').forEach(function (title) {
                if (title.textContent.trim().toLowerCase() === 'últimas notícias') {
                    var panel = title.closest('[menuitemname]') || title.closest('.card');
                    if (panel) {
                        panel.remove();
                    }
                }
            });
        });
    </script>
    <style>
        .providerPreLinking + p { display: none !important; }

        #header .navbar .client-area-menu-toggle {
            min-width: 44px;
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: auto;
            font-size: 1.35rem;
        }

        #header .navbar #clientAreaNavigation {
            flex-basis: 100%;
            width: 100%;
            margin-top: .5rem;
        }

        #header .navbar #clientAreaNavigation .navbar-nav {
            display: flex;
            flex-direction: column;
            width: 100%;
            padding: .5rem;
            border: 1px solid #dee2e6;
            border-radius: .35rem;
            background: #fff;
        }

        #header .navbar #clientAreaNavigation .navbar-nav > li {
            position: relative;
            width: 100%;
        }

        #header .navbar #clientAreaNavigation .navbar-nav > li > a {
            display: flex;
            align-items: center;
            min-height: 44px;
            padding: .65rem .75rem;
            white-space: normal;
            border-radius: .3rem;
        }

        #header .navbar #clientAreaNavigation .navbar-nav > li > a:hover,
        #header .navbar #clientAreaNavigation .navbar-nav > li > a:focus {
            background: #f1f3f5;
        }

        #header .navbar .navbar-nav .dropdown-menu {
            z-index: 1050;
        }
    </style>
</head>
<body data-phone-cc-input="{$phoneNumberInputStyle}">
    {if $captcha}{$captcha->getMarkup()}{/if}
    {$headeroutput}

    <header id="header" class="header">
        {if $loggedin}
            <div class="topbar">
                <div class="container">
                    <div class="d-flex">
                        <div class="mr-auto">
                            <button type="button" class="btn" data-toggle="popover" id="accountNotifications" data-placement="bottom">
                                <i class="far fa-flag"></i>
                                {if count($clientAlerts) > 0}
                                    {count($clientAlerts)}
                                    <span class="d-none d-sm-inline">{lang key='notifications'}</span>
                                {else}
                                    <span class="d-sm-none">0</span>
                                    <span class="d-none d-sm-inline">{lang key='nonotifications'}</span>
                                {/if}
                            </button>
                            <div id="accountNotificationsContent" class="w-hidden">
                                <ul class="client-alerts">
                                {foreach $clientAlerts as $alert}
                                    <li>
                                        <a href="{$alert->getLink()}">
                                            <i class="fas fa-fw fa-{if $alert->getSeverity() == 'danger'}exclamation-circle{elseif $alert->getSeverity() == 'warning'}exclamation-triangle{elseif $alert->getSeverity() == 'info'}info-circle{else}check-circle{/if}"></i>
                                            <div class="message">{$alert->getMessage()}</div>
                                        </a>
                                    </li>
                                {foreachelse}
                                    <li class="none">
                                        {lang key='notificationsnone'}
                                    </li>
                                {/foreach}
                                </ul>
                            </div>
                        </div>

                        <div class="ml-auto">
                            <div class="input-group active-client" role="group">
                                <div class="input-group-prepend d-none d-md-inline">
                                    <span class="input-group-text">{lang key='loggedInAs'}:</span>
                                </div>
                                <div class="btn-group">
                                    <a href="{$WEB_ROOT}/clientarea.php?action=details" class="btn btn-active-client">
                                        <span>
                                            {if $client.companyname}
                                                {$client.companyname}
                                            {else}
                                                {$client.fullName}
                                            {/if}
                                        </span>
                                    </a>
                                    <a href="{routePath('user-accounts')}" class="btn" data-toggle="tooltip" data-placement="bottom" title="Switch Account">
                                        <i class="fad fa-random"></i>
                                    </a>
                                    {if $adminMasqueradingAsClient || $adminLoggedIn}
                                        <a href="{$WEB_ROOT}/logout.php?returntoadmin=1" class="btn btn-return-to-admin" data-toggle="tooltip" data-placement="bottom" title="{if $adminMasqueradingAsClient}{lang key='adminmasqueradingasclient'} {lang key='logoutandreturntoadminarea'}{else}{lang key='adminloggedin'} {lang key='returntoadminarea'}{/if}">
                                            <i class="fas fa-redo-alt"></i>
                                            <span class="d-none d-md-inline-block">{lang key="admin.returnToAdmin"}</span>
                                        </a>
                                    {/if}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        {/if}

        <div class="navbar navbar-light">
            <div class="container mt-2 mb-2 d-flex justify-content-between align-items-center">
                <a class="navbar-brand" href="{$WEB_ROOT}/index.php">
                    {if $assetLogoPath}
                        <img src="{$assetLogoPath}" alt="{$companyname}" class="logo-img">
                    {else}
                        {$companyname}
                    {/if}
                </a>
                {if $loggedin}
                    <button
                        class="btn btn-outline-secondary client-area-menu-toggle"
                        type="button"
                        data-toggle="collapse"
                        data-target="#clientAreaNavigation"
                        aria-controls="clientAreaNavigation"
                        aria-expanded="false"
                        aria-label="Abrir menu da área do usuário"
                    >
                        <i class="fas fa-bars" aria-hidden="true"></i>
                    </button>
                    <div class="collapse" id="clientAreaNavigation">
                        <ul class="navbar-nav">
                            {include file="$template/includes/navbar.tpl" navbar=$primaryNavbar}
                            {include file="$template/includes/navbar.tpl" navbar=$secondaryNavbar}
                        </ul>
                    </div>
                {/if}
            </div>
        </div>
    </header>

    {include file="$template/includes/network-issues-notifications.tpl"}

    <nav class="master-breadcrumb" aria-label="breadcrumb">
        <div class="container">
            {include file="$template/includes/breadcrumb.tpl"}
        </div>
    </nav>

    {include file="$template/includes/validateuser.tpl"}
    {include file="$template/includes/verifyemail.tpl"}


    <section id="main-body">
        <div class="{if !$skipMainBodyContainer}container{/if}">
            <div class="row">

            {if !$inShoppingCart && ($primarySidebar->hasChildren() || $secondarySidebar->hasChildren())}
                <div class="col-lg-4 col-xl-3">
                    <div class="sidebar">
                        {include file="$template/includes/sidebar.tpl" sidebar=$primarySidebar}
                    </div>
                    {if !$inShoppingCart && $secondarySidebar->hasChildren()}
                        <div class="d-none d-lg-block sidebar">
                            {include file="$template/includes/sidebar.tpl" sidebar=$secondarySidebar}
                        </div>
                    {/if}
                </div>
            {/if}
            <div class="{if !$inShoppingCart && ($primarySidebar->hasChildren() || $secondarySidebar->hasChildren())}col-lg-8 col-xl-9{else}col-12{/if} primary-content">
