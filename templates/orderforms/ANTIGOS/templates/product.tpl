<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/style.css" />
{literal}
<style>
.tih-templates{
	width:205px;
	float:left;
}
.elevadasImgs:hover {
-webkit-transform: scale(4);
-moz-transform: scale(4);
-o-transform: scale(4);
transform: scale(4);
-webkit-transform-origin: top ;
-moz-transform-origin: top ;
-o-transform-origin: top ;
transform-origin: top ;

}
.elevadasImgs{
border-radius: 3px 3px 3px 3px;
box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 0 8px rgba(82, 168, 236, 0.6);
-moz-transition: all 0.5s ease-in;
-o-transition: all 0.5s ease-in;
-webkit-transition: all 0.5s ease-in;
-ms-transition: all 0.5s ease-in;
transition: all 0.5s ease-in;
max-width: 170px;
max-height: 190px;
}
.tih-templates-precos{
	text-align:center;
}
.tih-templates-precos strong{
	font-size:15px;
	vertical-align:top;
	color:#BF0101;
}

.tih-templates-precos span{
	line-height:90px;
	font-size:25px;
	color:#666;
	font-weight:bold;
}
.bodyRight{
	display:none;
}
.blankBoxContent{
width:915px;
background-color:#FFF;
}
#order-verticalsteps .maincontent{
	margin:0 auto;
}
#order-verticalsteps .maincontent .orderbox {
	  margin: 15px 7px;
	  float: left;
	  width: 420px;
	  height: 230px;
	  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 0 8px rgba(191, 1, 1, 1);
	  background-color: transparent;
}
	  
#order-verticalsteps .maincontent table tr td {
  padding: 0;
 
}

.thumbsTi{
	width: 30px;
	height: 205px;
	float: left;
	z-index:-999;
	cursor:pointer;
}
.thumbsTi img{
	max-height:25px !important;
	max-width:25px !important;
	float:left;
	margin:2px;
	clear:none;
	z-index:-999;
	
}
.screenshots{
	float: left;
	width: 200px;
	height: 230px;
}
.imagensTemplates{
	float: left;
	width: 30px;
	height: 200px;
}
.imagensTemplates img{
	max-height:25px !important;
	max-width:25px !important;
	float:left;
	margin:2px;
	clear:none;
	z-index:-999;
}
.imagemTemplates{
	float: left;
	width: 200px;
	height: 200px;
	text-align:center;
}
.imagemTemplates a{
	font-size:18px;
	color:#F00;
	line-height:30px;
	font-weight: bold;
}
.imagemTemplates div{
	height:200px;
}
.botoesTemplates{
	float: right;
	width: 210px;
	height: 175px;
}
.descricao{
	height:170px;
	width:100%;
	float:left;
	overflow: hidden;
	padding-bottom: 5px;
}
.descricao h1{
	font-size: 15px !important;
	color: #BF0101;
	text-align: center;
 }
.botoes span{
	font-size: 30px;
	color: #BF0101;
	text-shadow: 0px 0px 6px rgba(191, 1, 1, 0.22);
	font-weight: 600;
}
.botoes{
	height:50px;
	width:100%;
	float:left;
	text-align: center;
}
.botoes input[type=submit]
{
	margin-top:10px;
}
</style>
{/literal}
<div id="order-verticalsteps">

{include file="orderforms/verticalsteps/templates.tpl" step=1}

<div class="maincontent">
<!--
<div class="left">

<form method="get" action="{$smarty.server.PHP_SELF}">
<p>{$LANG.ordercategories}: <select name="gid" onchange="submit()">
{foreach key=num item=productgroup from=$productgroups}
<option value="{$productgroup.gid}"{if $gid eq $productgroup.gid} selected="selected"{/if}>{$productgroup.name}</option>
{/foreach}
{if $loggedin}
<option value="addons">{$LANG.cartproductaddons}</option>
{if $renewalsenabled}<option value="renewals">{$LANG.domainrenewals}</option>{/if}
{/if}
{if $registerdomainenabled}<option value="domains">{$LANG.orderdomainregonly}</option>{/if}
</select></p>
</form>

</div>
-->
<div class="right">

{if !$loggedin && $currencies}
<form method="post" action="cart.php?gid={$gid}">
<p align="right">{$LANG.choosecurrency}: <select name="currency" onchange="submit()">{foreach from=$currencies item=curr}
<option value="{$curr.id}"{if $curr.id eq $currency.id} selected{/if}>{$curr.code}</option>
{/foreach}</select> <input type="submit" value="{$LANG.go}" /></p>
</form>
{/if}

</div>

<br /><br /><br />
{foreach key=num item=product from=$products}
<div class="orderbox">
	<form method="post" action="{$smarty.server.PHP_SELF}?a=add&{if $product.bid}bid={$product.bid}{else}pid={$product.pid}{/if}">
		<div class="screenshots">
			<div class="imagemTemplates">
				<div>
					<img style="z-index={php}echo $zindex-=10;{/php}" class="elevadasImgs" id="principal_screen{$product.pid}" ondblclick="window.open('{$whmcs_url}/imagens/templates/getcapa.php?template={$product.name}&debug')"  title="Template: {$product.name}" src="{$whmcs_url}/imagens/templates/getcapa.php?template={$product.name}" />
				</div>
				<a href="#">Ver Mais Detalhes</a>
			</div>
		</div>
		<div class="botoesTemplates">
			<div class="descricao">
				<h1>{$product.name}</h1>
				<div>
					{$product.description}
				</div>
			</div>
			<div class="botoes">
				<span>{if $product.qty!=""}<em>({$product.qty} {$LANG.orderavailable})</em>{/if}
				{if $product.freedomain}<em>{$LANG.orderfreedomainregistration} {$LANG.orderfreedomaindescription}</em>{/if}
				{if $product.bid}
				<strong>{$LANG.bundledeal}</strong>{if $product.displayprice} {$product.displayprice}{/if}
				{elseif $product.paytype eq "free"}
				{$LANG.orderfree}
				<input type="hidden" name="billingcycle" value="free" />
				{elseif $product.paytype eq "onetime"}
				{if $product.pricing.hasconfigoptions}{$LANG.startingfrom} {/if}{$product.pricing.onetime} 
				<span>
				{/if}
				<input type="hidden" name="billingcycle" value="onetime" />
				
				<input type="submit" value="Comprar Este Template"{if $product.qty eq "0"} disabled{/if} />
			</div>
		</div>
	</form>
</div>
	
{/foreach}

<div style="float:left"><img align="left" src="images/padlock.gif" border="0" alt="Secure Transaction" style="padding-right: 10px;" /> {$LANG.ordersecure} (<strong>{$ipaddress}</strong>) {$LANG.ordersecure2}</div>

</div>

<div class="clear"></div>

</div>