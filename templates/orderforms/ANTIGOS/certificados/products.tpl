<script type="text/javascript" src="templates/orderforms/{$carttpl}/js/main.js"></script>
<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/style.css" />
{literal}
<style>
.bodyRight{
	display:none;
}
.blankBoxContent {
  width: 910px;
  padding: 20px;
  float: left;
}
#order-modern .product {
  min-height: 545px;
  float: left;
  display: table;
  position: relative;
}
.certImage {
  float: left;
  width: 100%;
  height: 115px;
  text-align: center;
}
#order-modern .product .pricing {
  float: left;
  width: 100%;
  /* border: solid red 1px; */
  text-align: center;
  color: #BF0101;
  line-height: 24px;
}
#order-modern .product .ordernowbox {
  margin: 15px 0 0 0;
  text-align: center;
  margin-top: 15px;
  float: left;
  width: 100%;
}
.precos{
	  float: left;
  width: 100%;
  margin-bottom: 10px;
}
#order-modern .product .description {
  font-size: 15px;
  margin: 0px;
  height: 270px;
  float: left;
  display: table;
  width: 100%;
  line-height: 19px;
}
#order-modern .product .name {
  font-family: Lucida Sans Unicode,Lucida Grande,sans-serif;
  font-weight: normal;
  font-size: 27px;
  margin-bottom: 22px;
  color: #BF0101;
  text-align: center;
}
</style>
{/literal}
<div id="order-modern">

<h1>{$groupname}</h1>

<div class="clear"></div>

{if !$loggedin && $currencies}
<div id="currencychooser">
{foreach from=$currencies item=curr}
<a href="cart.php?gid={$gid}&currency={$curr.id}"><img src="images/flags/{if $curr.code eq "AUD"}au{elseif $curr.code eq "CAD"}ca{elseif $curr.code eq "EUR"}eu{elseif $curr.code eq "GBP"}gb{elseif $curr.code eq "INR"}in{elseif $curr.code eq "JPY"}jp{elseif $curr.code eq "USD"}us{elseif $curr.code eq "ZAR"}za{else}na{/if}.png" border="0" alt="" /> {$curr.code}</a>
{/foreach}
</div>
<div class="clear"></div>
{else}
<br />
{/if}

{foreach from=$products key=num item=product}
<div class="products">
<div class="product" id="product{$num}" onclick="window.location='cart.php?a=add&{if $product.bid}bid={$product.bid}{else}pid={$product.pid}{/if}'">
<div class="certImage"><img src="imagens/certificados/{$product.pid}.gif" /></div>


<div class="name">
    {$product.name}{if $product.qty!=""} <span class="qty">({$product.qty} {$LANG.orderavailable})</span>{/if}
</div>

{foreach from=$product.features key=feature item=value}
<span class="prodfeature"><span class="feature">{$feature}</span><br />{$value}</span>
{/foreach}

<div class="clear"></div>

<div class="description">{$product.featuresdesc}</div>

<div class="precos">
	<hr />
	<div class="pricing">
		<span class=pricing">Validade de 1 ano : R$ {$product.pricing.rawpricing.annually}</span><br />
		<span class="pricing">Validade de 2 anos : R$ {$product.pricing.rawpricing.biennially}</span><br />
		<span class="pricing">Validade de 3 anos : R$ {$product.pricing.rawpricing.triennially}</span>
	</div>

	<form method="post" action="cart.php?a=add&{if $product.bid}bid={$product.bid}{else}pid={$product.pid}{/if}">
	<div class="ordernowbox"><input type="submit" value="{$LANG.ordernowbutton} &raquo;" class="ordernow" /></div>
	</form>
	</div>
</div>
</div>
{if $num % 2}<div class="clear"></div>
{/if}
{/foreach}

<div class="clear"></div>

{if !$loggedin && $currencies}
<div id="currencychooser">
{foreach from=$currencies item=curr}
<a href="cart.php?gid={$gid}&currency={$curr.id}"><img src="images/flags/{if $curr.code eq "AUD"}au{elseif $curr.code eq "CAD"}ca{elseif $curr.code eq "EUR"}eu{elseif $curr.code eq "GBP"}gb{elseif $curr.code eq "INR"}in{elseif $curr.code eq "JPY"}jp{elseif $curr.code eq "USD"}us{elseif $curr.code eq "ZAR"}za{else}na{/if}.png" border="0" alt="" /> {$curr.code}</a>
{/foreach}
</div>
<div class="clear"></div>
{/if}

<br />
<br />

</div>