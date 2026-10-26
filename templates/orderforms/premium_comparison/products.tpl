<link rel="stylesheet" type="text/css" href="{$WEB_ROOT}/templates/orderforms/{$carttpl}/css/style.css" property="stylesheet" />
<script>
jQuery(document).ready(function () {
    jQuery('#btnShowSidebar').click(function () {
        if (jQuery(".product-selection-sidebar").is(":visible")) {
            jQuery('.row-product-selection').css('left','0');
            jQuery('.product-selection-sidebar').fadeOut();
            jQuery('#btnShowSidebar').html('<i class="fas fa-arrow-circle-right"></i> {$LANG.showMenu}');
        } else {
            jQuery('.product-selection-sidebar').fadeIn();
            jQuery('.row-product-selection').css('left','300px');
            jQuery('#btnShowSidebar').html('<i class="fas fa-arrow-circle-left"></i> {$LANG.hideMenu}');
        }
    });
});
</script>

{if $showSidebarToggle}
    <button type="button" class="btn btn-default btn-sm" id="btnShowSidebar">
        <i class="fas fa-arrow-circle-right"></i>
        {$LANG.showMenu}
    </button>
{/if}

<div class="row row-product-selection">
    <div class="col-xs-3 product-selection-sidebar" id="premiumComparisonSidebar">
        {include file="orderforms/standard_cart/sidebar-categories.tpl"}
    </div>
    <div class="col-xs-12">

        <div id="order-premium_comparison">
            <div class="main-container price-01">
                <div class="txt-center">
                    <h3 id="headline">
                        {if $productGroup.headline}
                            {$productGroup.headline}
                        {else}
                            {$productGroup.name}
                        {/if}
                    </h3>
                    {if $productGroup.tagline}
                        <h5 id="tagline">
                            {$productGroup.tagline}
                        </h5>
                    {/if}
                    {if $errormessage}
                        <div class="alert alert-danger">
                            {$errormessage}
                        </div>
                    {/if}
                </div>
                <div id="products" class="price-table-container">
                    <ul>
                                                    <li id="product1">
                                <div class="price-table">
                                    <div class="top-head">
                                        <div class="top-area">
                                            <h4 id="product1-name">Hospedagem de Sites</h4>
                                        </div>
                                                                                
                                        <div class="price-area">
                                            <div class="price" id="product1-price">
                                                                                                                                                        <span>R$ 47.75</span>/Trimestrais
                                                    <br>
                                                                                                                                                </div>
                                                                                            <a href="/thx/cart.php?a=add&amp;pid=125" class="order-button" id="product1-order-button">
                                                    Assinar agora
                                                </a>
                                            
                                        </div>
                                    </div>
                                    <ul>
                                                                                    <li id="product1-feature1">
                                                1GB <br>Espaço em Disco
                                            </li>
                                                                                    <li id="product1-feature2">
                                                ILIMITADO <br>Tráfego
                                            </li>
                                                                                    <li id="product1-feature3">
                                                3 <br>Contas de E-mail
                                            </li>
                                                                                    <li id="product1-feature4">
                                                1 <br>Bases de Dados
                                            </li>
                                                                                    <li id="product1-feature5">
                                                50 <br>Máximo de E-mails Enviados/Hora
                                            </li>
                                                                                    <li id="product1-feature6">
                                                WhatsApp <br> Suporte Nível 1
                                            </li>
                                                                                    <li id="product1-feature7">
                                                Tickets <br> Suporte Nível 2
                                            </li>
                                                                                    <li id="product1-feature8">
                                                E-mail <br> Suporte Nível 3
                                            </li>
                                                                            </ul>
                                </div>
                            </li>
                                                    <li id="product2">
                                <div class="price-table">
                                    <div class="top-head">
                                        <div class="top-area">
                                            <h4 id="product2-name">TIH - Master </h4>
                                        </div>
                                                                                
                                        <div class="price-area">
                                            <div class="price" id="product2-price">
                                                                                                                                                        <span>R$ 32.95</span>/Mensais
                                                    <br>
                                                                                                                                                </div>
                                                                                            <a href="/thx/cart.php?a=add&amp;pid=3" class="order-button" id="product2-order-button">
                                                    Assinar agora
                                                </a>
                                            
                                        </div>
                                    </div>
                                    <ul>
                                                                                    <li id="product2-feature1">
                                                10GB <br>Espaço em Disco
                                            </li>
                                                                                    <li id="product2-feature2">
                                                ILIMITADO <br>Tráfego
                                            </li>
                                                                                    <li id="product2-feature3">
                                                3 <br>Usuários FTP
                                            </li>
                                                                                    <li id="product2-feature4">
                                                30 <br>Contas de E-mail
                                            </li>
                                                                                    <li id="product2-feature5">
                                                3 <br>Bases de Dados
                                            </li>
                                                                                    <li id="product2-feature6">
                                                3 <br>Subdomínios
                                            </li>
                                                                                    <li id="product2-feature7">
                                                3 <br>Dominios Estacionados
                                            </li>
                                                                                    <li id="product2-feature8">
                                                1500 <br>Máximo de E-mails Enviados/Hora
                                            </li>
                                                                            </ul>
                                </div>
                            </li>
                                                    <li id="product3">
                                <div class="price-table">
                                    <div class="top-head">
                                        <div class="top-area">
                                            <h4 id="product3-name">TIH - Premium</h4>
                                        </div>
                                                                                
                                        <div class="price-area">
                                            <div class="price" id="product3-price">
                                                                                                                                                        <span>R$ 43.95</span>/Mensais
                                                    <br>
                                                                                                                                                </div>
                                                                                            <a href="/thx/cart.php?a=add&amp;pid=11" class="order-button" id="product3-order-button">
                                                    Assinar agora
                                                </a>
                                            
                                        </div>
                                    </div>
                                    <ul>
                                                                                    <li id="product3-feature1">
                                                15GB <br>Espaço em Disco
                                            </li>
                                                                                    <li id="product3-feature2">
                                                ILIMITADO <br>Tráfego
                                            </li>
                                                                                    <li id="product3-feature3">
                                                5 <br>Usuários FTP
                                            </li>
                                                                                    <li id="product3-feature4">
                                                45 <br>Contas de E-mail
                                            </li>
                                                                                    <li id="product3-feature5">
                                                15 <br>Bases de Dados
                                            </li>
                                                                                    <li id="product3-feature6">
                                                5 <br>Subdomínios
                                            </li>
                                                                                    <li id="product3-feature7">
                                                5 <br>Dominios Estacionados
                                            </li>
                                                                                    <li id="product3-feature8">
                                                200 <br>Máximo de E-mails Enviados/Hora
                                            </li>
                                                                            </ul>
                                </div>
                            </li>
                                                    <li id="product4">
                                <div class="price-table">
                                    <div class="top-head">
                                        <div class="top-area">
                                            <h4 id="product4-name">TIH - Especial</h4>
                                        </div>
                                                                                
                                        <div class="price-area">
                                            <div class="price" id="product4-price">
                                                                                                                                                        <span>R$ 65.95</span>/Mensais
                                                    <br>
                                                                                                                                                </div>
                                                                                            <a href="/thx/cart.php?a=add&amp;pid=32" class="order-button" id="product4-order-button">
                                                    Assinar agora
                                                </a>
                                            
                                        </div>
                                    </div>
                                    <ul>
                                                                                    <li id="product4-feature1">
                                                20GB <br>Espaço em Disco
                                            </li>
                                                                                    <li id="product4-feature2">
                                                ILIMITADO <br>Tráfego
                                            </li>
                                                                                    <li id="product4-feature3">
                                                7 <br>Usuários FTP
                                            </li>
                                                                                    <li id="product4-feature4">
                                                75 <br>Contas de E-mail
                                            </li>
                                                                                    <li id="product4-feature5">
                                                18 <br>Bases de Dados
                                            </li>
                                                                                    <li id="product4-feature6">
                                                7 <br>Subdomínios
                                            </li>
                                                                                    <li id="product4-feature7">
                                                7 <br>Dominios Estacionados
                                            </li>
                                                                                    <li id="product4-feature8">
                                                2 <br>Domínios adicionais
                                            </li>
                                                                                    <li id="product4-feature9">
                                                250 <br>Máximo de E-mails Enviados/Hora
                                            </li>
                                                                            </ul>
                                </div>
                            </li>
                                                    <li id="product5">
                                <div class="price-table">
                                    <div class="top-head">
                                        <div class="top-area">
                                            <h4 id="product5-name">TIH Professional</h4>
                                        </div>
                                                                                
                                        <div class="price-area">
                                            <div class="price" id="product5-price">
                                                                                                                                                        <span>R$ 108.90</span>/Mensais
                                                    <br>
                                                                                                                                                </div>
                                                                                            <a href="/thx/cart.php?a=add&amp;pid=4" class="order-button" id="product5-order-button">
                                                    Assinar agora
                                                </a>
                                            
                                        </div>
                                    </div>
                                    <ul>
                                                                                    <li id="product5-feature1">
                                                25GB <br>Espaço em Disco
                                            </li>
                                                                                    <li id="product5-feature2">
                                                ILIMITADO <br>Tráfego
                                            </li>
                                                                                    <li id="product5-feature3">
                                                10 <br>Usuários FTP
                                            </li>
                                                                                    <li id="product5-feature4">
                                                150 <br>Contas de E-mail
                                            </li>
                                                                                    <li id="product5-feature5">
                                                20 <br>Bases de Dados
                                            </li>
                                                                                    <li id="product5-feature6">
                                                10 <br>Subdomínios
                                            </li>
                                                                                    <li id="product5-feature7">
                                                10 <br>Dominios Estacionados
                                            </li>
                                                                                    <li id="product5-feature8">
                                                4 <br>Domínios adicionais
                                            </li>
                                                                                    <li id="product5-feature9">
                                                300 <br>Máximo de E-mails Enviados/Hora
                                            </li>
                                                                            </ul>
                                </div>
                            </li>
                                                    <li id="product6">
                                <div class="price-table">
                                    <div class="top-head">
                                        <div class="top-area">
                                            <h4 id="product6-name">TIH - Sem Limite</h4>
                                        </div>
                                                                                
                                        <div class="price-area">
                                            <div class="price" id="product6-price">
                                                                                                                                                        <span>R$ 163.60</span>/Mensais
                                                    <br>
                                                                                                                                                </div>
                                                                                            <a href="/thx/cart.php?a=add&amp;pid=10" class="order-button" id="product6-order-button">
                                                    Assinar agora
                                                </a>
                                            
                                        </div>
                                    </div>
                                    <ul>
                                                                                    <li id="product6-feature1">
                                                50GB <br>Espaço em Disco
                                            </li>
                                                                                    <li id="product6-feature2">
                                                ILIMITADO <br>Tráfego
                                            </li>
                                                                                    <li id="product6-feature3">
                                                15 <br>Usuários FTP
                                            </li>
                                                                                    <li id="product6-feature4">
                                                ILIMITADO <br>Contas de E-mail
                                            </li>
                                                                                    <li id="product6-feature5">
                                                ILIMITADO <br>Bases de Dados
                                            </li>
                                                                                    <li id="product6-feature6">
                                                10 <br>Subdomínios
                                            </li>
                                                                                    <li id="product6-feature7">
                                                10 <br>Dominios Estacionados
                                            </li>
                                                                                    <li id="product6-feature8">
                                                10 <br>Domínios adicionais
                                            </li>
                                                                                    <li id="product6-feature9">
                                                500 <br>Máximo de E-mails Enviados/Hora
                                            </li>
                                                                            </ul>
                                </div>
                            </li>
                                            </ul>
                </div>
            </div>
            {if count($productGroup.features) > 0}
                <div class="includes-features">
                    <div class="row clearfix">
                        <div class="col-md-12">
                            <div class="head-area">
                                <span>
                                    {$LANG.orderForm.includedWithPlans}
                                </span>
                            </div>
                            <ul class="list-features">
                                {foreach $productGroup.features as $features}
                                    <li>{$features.feature}</li>
                                {/foreach}
                            </ul>
                        </div>
                    </div>
                </div>
            {/if}
        </div>

    </div>
</div>
