<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-type" content="text/html; charset={$charset}" />


{php}
if($_SERVER['PHP_SELF'] == '/template.php')
{
	function url_amigavel($string)
{
    
     $url_limpa=tiracento($string);
    
     $proibidos=array('/','&','%','(','?','.','\\','#',')','°' );
    
     $url_limpa=str_replace($proibidos,'',$url_limpa);



	$url_limpa = preg_replace("/[^a-zA-Z0-9][-]/", "-",$url_limpa);


    
    
	$url_limpa= strtolower( strip_tags( preg_replace( array( '/[`^~\'"]/', '/([\s]{1,})/', '/[-]{2,}/' ), array( null, '-', '-' ), iconv( 'UTF-8', 'ASCII//TRANSLIT', $url_limpa ) ) ) );



	return $url_limpa;
}

function tiracento($texto)
{
        $trocarIsso = array('à','á','â','ã','ä','å','ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ù','ü','ú','ÿ','À','Á','Â','Ã','Ä','Å','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ñ','Ò','Ó','Ô','Õ','Ö','O','Ù','Ü','Ú','Ÿ',);
        $porIsso = array('a','a','a','a','a','a','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','u','u','u','y','A','A','A','A','A','A','C','E','E','E','E','I','I','I','I','N','O','O','O','O','O','o','U','U','U','Y',);
        $titletext = str_replace($trocarIsso, $porIsso, $texto);
        return $titletext;
}

	$command = "getproducts";
	 $adminuser = "deljdl";
	 $values["pid"] = $_GET['template_id'];
	$result = localAPI($command,$values,$adminuser);
 
	echo '
		<meta name="title" content="Comprar site Pronto : '.$result['products']['product'][0]['name'].'" />
		<meta name="subject" content="Comprar site Pronto : '.$result['products']['product'][0]['name'].'" />
		<meta name="description" content="'.$result['products']['product'][0]['description'].'" />

		<title>Comprar site Pronto : '.$result['products']['product'][0]['name'].'</title>
		<meta itemprop="image" content="https://tihospedagem.com.br/imagens/templates/getcapa.php?template='.url_amigavel($result['products']['product'][0]['name']).'">
		<meta itemprop="description" content="'.$result['products']['product'][0]['name'].'">
		<meta itemprop="name" content="'.$result['products']['product'][0]['name'].' : '.$result['products']['product'][0]['description'].'">
		

		<meta property="og:url" content="'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'">
		<meta property="og:title" content="'.$result['products']['product'][0]['name'].'">
		<meta property="og:description" content="'.$result['products']['product'][0]['description'].'">
		<meta property="og:image" content="https://tihospedagem.com.br/imagens/templates/getcapa.php?template='.url_amigavel($result['products']['product'][0]['name']).'">
		<meta property="og:type" content="url">';
}
else
{
	echo '
		<title>TiHospedagem </title>
		
		<meta name="title" content="TiHospedagem" />
		<meta name="subject" content="A TiHospedagem Oferece serviços de hospedagem de sites, registro de domínio, loja virtual, cloud computing, e-mail profissional, entre outros. Acesse já!" />
		<meta name="description" content="A TiHospedagem Oferece serviços de hospedagem de sites, registro de domínio, loja virtual, cloud computing, e-mail profissional, entre outros. Acesse já!" />

		<meta name="comment" content="A TiHospedagem Oferece serviços de hospedagem de sites, registro de domínio, loja virtual, cloud computing, e-mail profissional, entre outros. Acesse já!">


		<meta property="og:url" content="http://tihospedagem.com.br">
		<meta property="og:title" content="">
		<meta property="og:description" content="A TiHospedagem Oferece serviços de hospedagem de sites, registro de domínio, loja virtual, cloud computing, e-mail profissional, entre outros. Acesse já!">
		<meta property="og:image" content="http://tihospedagem.com.br/imagens/face-share.png">
		<meta property="og:type" content="website">
		
		<meta itemprop="image" content="http://tihospedagem.com.br/imagens/logo.png">
		<meta itemprop="description" content="A TiHospedagem Oferece serviços de hospedagem de sites, registro de domínio, loja virtual, cloud computing, e-mail profissional, entre outros. Acesse já!">
		<meta itemprop="name" content="TiHospedagem , hospedagem para hospedar seu site com qualidade com preço acessível.">';
}

{/php}
<meta name="keywords" content="planos de hospedagem, hospedagem, hospedagem de sites, hospedagem tihospedagem, planos de hospedagem de sites, hospedagem de site" />
<meta name="location" content="Sao Paulo, Brasil">
<meta name="msvalidate.01" content="4294DDDB3A082D04E979D83135498F35" />		


		
		
{if $systemurl}<base href="{$systemurl}" />{/if}
<link rel="stylesheet" type="text/css" href="templates/{$template}/style.css" />
<script type="text/javascript" src="includes/jscript/jquery.js"></script>
{$headoutput}
{if $livehelpjs}{$livehelpjs}{/if}
<link rel="shortcut icon" type="image/x-icon" href="http://tihospedagem.com.br/imagens/favicon.ico" />
<link href="templates/{$template}/resources/css/style.css" rel="stylesheet" type="text/css" />


<script type="javascript" src="imagens/templates/lightbox/js/lightbox-2.6.min.js"></script>
<link href="imagens/templates/lightbox/css/lightbox.css" rel="stylesheet" type="text/css" />
{literal}
<!-- Google Tag Manager -->
<noscript><iframe src="//www.googletagmanager.com/ns.html?id=GTM-P64KVK"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-P64KVK');</script>
<!-- End Google Tag Manager -->
{/literal}
</head>

<body>
{$headeroutput}
<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
  <td class="leftTD" width="50%">&nbsp;</td>
  <td width="950px"><div class="headerWrapper">
      <div class="header">
        <div class="logo"><a href="index.php"><img src="imagens/logo.png" /></a></div>
			<div class="atendimento">
				
			</div>
		
          <div id="menuNew">
            <ul class="menu">
 
			<li><a href="index.php">Home</a></li>
              <li><a href="javascript:void(0)">Produtos</a>
				  <ul>
					<li><a href="hospedagem-de-sites/planos">Hospedagem</a></li>
					<li><a href="email-marketing">E-Mail Marketing</a></li>
					<li><a href="gestor-de-conteudo-para-sites">Gestor de Conteúdo</a></li>
					<li><a href="atendimento-on-line-para-seu-site">Atendimento OnLine</a></li>
					<li><a href="sites-prontos">Sites Prontos</a></li>
					<li><a href="links-patrocinados">Links Patrocinados</a></li>
					<li><a href="criacao-de-sites">Criação de sites</a></li>
					<li><a href="criacao-de-lojas-virtuais">Lojas Virtuais</a></li>
					<li><a href="certificado-digital">Certificado Digital</a></li>
					
				  </ul>
			  </li>
              <li><a href="javascript:void(0);">Domínios</a>
				<ul>
					<li><a href="domainchecker.php">Pesquisar</a></li>
					<li><a href="cart.php?gid=renewals">Renovar</a></li>
					<li><a href="cart.php?a=add&domain=register">Registrar</a></li>
					<li><a href="cart.php?a=add&domain=transfer">Transferir</a></li>					
				</ul>
			  </li>
              <li><a href="knowledgebase.php">F.A.Q's</a></li>
              <li><a href="announcements.php">Anúncios</a></li>
              <li><a href="affiliates.php">Afiliados</a></li>
              <li><a href="contact.php">Contato</a></li>
			  {php} if(isset($_SESSION['cart']['products']) && count($_SESSION['cart']['products']) > 0 ) echo '<li class="cart-jdl"><a title="Você tem '.count($_SESSION['cart']['products']).' itens em seu carrinho" href="cart.php?a=view">&nbsp;&nbsp;'.count($_SESSION['cart']['products']).'</a>&nbsp;&nbsp;</li>';{/php}
			  {if $loggedin}
			  <li style="float:right; padding-left:50px;"><a href="logout.php">Sair</a></li>
			  <li style="float:right;"><a href="javascript:void(0)">Olá , {$loggedinuser.firstname} !</a>
				<ul>
					<li><a href="clientportal.php">Área do Cliente</a></li>
					<li><a href="clientarea.php?action=details">Meus Dados</a></li>
					<li><a href="clientarea.php?action=products">Meus Serviços</a></li>
					<li><a href="clientarea.php?action=domains">Meus Domínios</a></li>
					<li><a href="clientarea.php?action=quotes">Meus Orçamentos</a></li>
					<li><a href="clientarea.php?action=invoices">Minhas Faturas</a></li>
					<li><a href="supporttickets.php ">Meus Tickets</a></li>		
				</ul>
			  </li>
			  {else}
			  <li style="float:right;"><a href="/clientarea.php">Entrar</a></li>
			  <li style="float:right;"><a href="/register.php">Cadastre-se</a></li>
			  {/if}
			 
			</ul>
          </div>
       
      </div>
    </div>
    <div class="banner" style="border-radius:15px; background-color:transparent !important;">
      <div class="bannerCon" style="width:550px;">
        <p>Escolha a melhor opção. </p>
        <ul>
          <li>Servidor <span class="color2">DEDICADO</span></li>
          <li>Tráfego <span class="color2">ILIMITADO</span></li>
          <li>Contas de E-mail <span class="color2">PERSONALIZADAS</span></li>
        </ul>
        <ul>
          <li>99.9% Uptime <span class="color2">GARANTIDO</span><br />
          </li>
          <li>Suporte <span class="color2">24 Horas</span> por dia</li>
		  <li>Gestor de Conteúdo <span class="color2">Incluído</span></li>
        </ul>
        <div class="bannerBtnCon">
          <div class="bannerBtn" style="margin:7px 0 0 369px;"><a href="cart.php?a=add&pid=2">Peça Agora!</a></div>
        </div>
      </div>
    </div></td>
  <td class="rightTD" width="50%">&nbsp;</td>
</tr>
<tr>
  <td class="bgLeft">&nbsp;</td>
  <td><div class="bodyCon">
    <div class="bodyleft">
{if $filename eq 'index' or $filename eq 'sharedhosting'}
{else}
    <div class="blankBox">
    <div class="blankBoxTop"></div>
    <div class="blankBoxContent">
    <h1 class="headingBlank"><span class="color2">{$pagetitle}</span></h1>
{/if}