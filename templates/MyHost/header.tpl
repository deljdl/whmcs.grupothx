<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-type" content="text/html; charset={$charset}" />
<title>{$companyname} - {$pagetitle}{if $kbarticle.title} - {$kbarticle.title}{/if}</title>
{if $systemurl}
<base href="{$systemurl}" />
{/if}
<link rel="stylesheet" type="text/css" href="templates/{$template}/style.css" />
<script type="text/javascript" src="includes/jscript/jquery.js"></script>
{$headoutput}
{if $livehelpjs}{$livehelpjs}{/if}
<link rel="shortcut icon" type="image/x-icon" href="https://www.whmcsthemes.com/favicon.png" />
<link href="templates/{$template}/resources/css/style.css" rel="stylesheet" type="text/css" />
</head><body>
<div id="headerwrapper">
<div id="logo"><a href="index.php"></a></div>
<div id="topMenu">
  <ul>
    <li><a href="serverstatus.php">Server Status</a></li>
    {if $loggedin}
    <li><a href="logout.php">Sign Out</a></li>
    {else}
    <li><a href="clientarea.php">Client Login</a></li>
    {/if}
    <li class="nobg"><a href="contact.php">Contact Us</a></li>
  </ul>
  <div class="clr"></div>
</div>
<div id="bannerWrapper">
  <div class="wrapper">
    <div id="menuCon">
      <div id="menu">
        <ul>
          <li class="home"><a href="index.php">Home</a></li>
          <li><a href="sharedhosting.php">Hosting Plans</a></li>
          <li><a href="announcements.php">Latest news</a></li>
          <li><a href="knowledgebase.php">F.A.Q's</a></li>
          <li><a href="clientportal.php">Client Portal</a></li>
        </ul>
      </div>
      <div class="orderNow"><a href="cart.php">Order Now</a></div>
    </div>
    {if $filename eq 'index'}
    {else}
    <div class="SubpageBanner">
      <h1>Free domain name with all plans</h1>
      <ul class="liststyle2">
        <li>UNLIMITED POP Mailboxes</li>
        <li>UNLIMITED MySQL Databases</li>
      </ul>
    </div>
    <div class="clr"></div>
  </div>
</div>
{if $filename eq 'sharedhosting'}
{else}
<div class="wrapper">
<div id="leftCon">
<div class="box2">
<div class="box2Top"></div>
<div class="box2Con">
<h5>{$pagetitle}</h5>
{/if}
{/if} 
