<html>
<head>
<title>{$companyname} - {* This code should be uncommented for EU companies using the sequential invoice numbering so that when unpaid it is shown as a proforma invoice {if $status eq "Paid"}*}{$LANG.invoicenumber}{*{else}{$LANG.proformainvoicenumber}{/if}*}{$invoicenum}</title>
<link rel="stylesheet" type="text/css" href="templates/{$template}/invoicestyle.css">
</head>
<body bgcolor="#efefef">

{if $error}
<p style="color:#cc0000;">{$LANG.invoiceserror}</p>
{else}

<table id="wrapper" cellspacing="1" cellpadding="10" bgcolor="#cccccc" align="center"><tr><td bgcolor="#ffffff">

<table width="100%"><tr><td width="50%">

{if $logo}<p><img src="http://tihospedagem.com.br/imagens/grupothx-boleto.png"></p>{else}<h1>{$companyname}</h1>{/if}

</td><td width="50%" align="center">

{if $status eq "Unpaid"}
<font class="unpaid">{$LANG.invoicesunpaid}</font><br />
{if $allowchangegateway}
<form method="post" action="{$smarty.server.PHP_SELF}?id={$invoiceid}">{$gatewaydropdown}</form>
{else}
{$paymentmethod}<br />
{/if}
{$paymentbutton}
{elseif $status eq "Paid"}
<font class="paid">{$LANG.invoicespaid}</font><br />
{$paymentmethod}<br />
({$datepaid})
{elseif $status eq "Refunded"}
<font class="refunded">{$LANG.invoicesrefunded}</font>
{elseif $status eq "Cancelled"}
<font class="cancelled">{$LANG.invoicescancelled}</font>
{elseif $status eq "Collections"}
<font class="collections">{$LANG.invoicescollections}</font>
{/if}

</td></tr></table>

{if $smarty.get.paymentsuccess}
<p align="center" class="paid">{$LANG.invoicepaymentsuccessconfirmation}</p>
{elseif $smarty.get.pendingreview}
<p align="center" class="paid">{$LANG.invoicepaymentpendingreview}</p>
{elseif $smarty.get.paymentfailed}
<p align="center" class="unpaid">{$LANG.invoicepaymentfailedconfirmation}</p>
{elseif $offlinepaid}
<p align="center" class="refunded">{$LANG.invoiceofflinepaid}</p>
{else}
<br />
{/if}

{if $manualapplycredit}
<div class="creditbox">{$LANG.invoiceaddcreditdesc1} {$totalcredit}. {$LANG.invoiceaddcreditdesc2}<br />
<form method="post" action="{$smarty.server.PHP_SELF}?id={$invoiceid}"><input type="hidden" name="applycredit" value="true" />
{$LANG.invoiceaddcreditamount}: <input type="text" name="creditamount" size="10" value="{$creditamount}" /> <input type="submit" value="{$LANG.invoiceaddcreditapply}" />
</form></div>
<br />
{/if}

<table width="100%" id="invoicetoptables" cellspacing="0"><tr><td width="50%" id="invoicecontent" style="border:1px solid #cccccc">

<table width="100%" height="120" cellspacing="0" cellpadding="10" id="invoicetoptables"><tr><td id="invoicecontent" valign="top" style="border:1px solid #cccccc">

<strong>{$LANG.invoicesinvoicedto}</strong><br />
{if $clientsdetails.companyname}{$clientsdetails.companyname}<br />{/if}
{$clientsdetails.firstname} {$clientsdetails.lastname}<br />
{$clientsdetails.address1}, {$clientsdetails.address2}<br />
{$clientsdetails.city}, {$clientsdetails.state}, {$clientsdetails.postcode}<br />
{$clientsdetails.country}
{if $customfields}
<br /><br />
{foreach from=$customfields item=customfield}
{$customfield.fieldname}: {$customfield.value}<br />
{/foreach}
{/if}

</td></tr></table>

</td><td width="50%" id="invoicecontent" style="border:1px solid #cccccc;border-left:0px;">

<table width="100%" height="120" cellspacing="0" cellpadding="10" id="invoicetoptables"><tr><td id="invoicecontent" valign="top" style="border:1px solid #cccccc">

<strong>{$LANG.invoicespayto}</strong><br />
{$payto}

</td></tr></table>

</td></tr></table>

<p><strong>{* This code should be uncommented for EU companies using the sequential invoice numbering so that when unpaid it is shown as a proforma invoice {if $status eq "Paid"}*}{$LANG.invoicenumber}{*{else}{$LANG.proformainvoicenumber}{/if}*}{$invoicenum}</strong><br />
{$LANG.invoicesdatecreated}: {$datecreated}<br />
{$LANG.invoicesdatedue}: {$datedue}</p>

<table cellspacing="0" id="invoiceitemstable" align="center"><tr><td id="invoiceitemsheading" align="center" width="70%" style="border:1px solid #cccccc;border-bottom:0px;"><strong>{$LANG.invoicesdescription}</strong></td><td id="invoiceitemsheading" align="center" width="30%" style="border:1px solid #cccccc;border-left:0px;border-bottom:0px;"><strong>{$LANG.invoicesamount}</strong></td></tr>
{foreach key=num item=invoiceitem from=$invoiceitems}
<tr bgcolor=#ffffff><td id="invoiceitemsrow" style="border:1px solid #cccccc;border-bottom:0px;">{$invoiceitem.description}{if $invoiceitem.taxed eq "true"} *{/if}</td><td align="center" id="invoiceitemsrow" style="border:1px solid #cccccc;border-bottom:0px;border-left:0px;">{$invoiceitem.amount}</td></tr>
{/foreach}
<tr><td id="invoiceitemsheading" style="border:1px solid #cccccc;border-bottom:0px;"><div align="right">{$LANG.invoicessubtotal}:&nbsp;</div></td><td id="invoiceitemsheading" align="center" style="border:1px solid #cccccc;border-bottom:0px;border-left:0px;"><strong>{$subtotal}</strong></td></tr>
{if $taxrate}<tr><td id="invoiceitemsheading" style="border:1px solid #cccccc;border-bottom:0px;"><div align="right">{$taxrate}% {$taxname}:&nbsp;</div></td><td id="invoiceitemsheading" align="center" style="border:1px solid #cccccc;border-bottom:0px;border-left:0px;"><strong>{$tax}</strong></td></tr>{/if}
{if $taxrate2}<tr><td id="invoiceitemsheading" style="border:1px solid #cccccc;border-bottom:0px;"><div align="right">{$taxrate2}% {$taxname2}:&nbsp;</div></td><td id="invoiceitemsheading" align="center" style="border:1px solid #cccccc;border-bottom:0px;border-left:0px;"><strong>{$tax2}</strong></td></tr>{/if}
<tr><td id="invoiceitemsheading" style="border:1px solid #cccccc;border-bottom:0px;"><div align="right">{$LANG.invoicescredit}:&nbsp;</div></td><td id="invoiceitemsheading" align="center" style="border:1px solid #cccccc;border-bottom:0px;border-left:0px;"><strong>{$credit}</strong></td></tr>
<tr><td id="invoiceitemsheading" style="border:1px solid #cccccc;"><div align="right">{$LANG.invoicestotal}:&nbsp;</div></td><td id="invoiceitemsheading" align="center" style="border:1px solid #cccccc;border-left:0px;"><strong>{$total}</strong></td></tr>
</table>

{if $taxrate}<p>* {$LANG.invoicestaxindicator}</p>{/if}

<p><strong>{$LANG.invoicestransactions}</strong></p>

<table cellspacing="0" id="invoiceitemstable" align="center"><tr><td id="invoiceitemsheading" align="center" width="30%" style="border:1px solid #cccccc"><strong>{$LANG.invoicestransdate}</strong></td><td id="invoiceitemsheading" align="center" width="25%" style="border:1px solid #cccccc;border-left:0px;"><strong>{$LANG.invoicestransgateway}</strong></td><td id="invoiceitemsheading" align="center" width="25%" style="border:1px solid #cccccc;border-left:0px;"><strong>{$LANG.invoicestransid}</strong></td><td id="invoiceitemsheading" align="center" width="20%" style="border:1px solid #cccccc;border-left:0px;"><strong>{$LANG.invoicestransamount}</strong></td></tr>
{foreach key=num item=transaction from=$transactions}
<tr bgcolor=#ffffff><td id="invoiceitemsrow" align="center" style="border:1px solid #cccccc;border-top:0px;">{$transaction.date}</td><td align="center" id="invoiceitemsrow" style="border:1px solid #cccccc;border-left:0px;border-top:0px;">{$transaction.gateway}</td><td align="center" id="invoiceitemsrow" style="border:1px solid #cccccc;border-left:0px;border-top:0px;">{$transaction.transid}</td><td align="center" id="invoiceitemsrow" style="border:1px solid #cccccc;border-left:0px;border-top:0px;">{$transaction.amount}</td></tr>
{foreachelse}
<tr bgcolor=#ffffff><td id="invoiceitemsrow" colspan=4 align="center" style="border:1px solid #cccccc;border-top:0px;">{$LANG.invoicestransnonefound}</td></tr>
{/foreach}
<tr><td id="invoiceitemsheading" width="30%" style="border:1px solid #cccccc;border-top:0px;" colspan=3><DIV ALIGN="right"><strong>{$LANG.invoicesbalance}:&nbsp;</strong></DIV></td><td id="invoiceitemsheading" align="center" width="20%" style="border:1px solid #cccccc;border-left:0px;border-top:0px;"><strong>{$balance}</strong></td></tr>
</table>

{if $notes}
<p>{$LANG.invoicesnotes}: {$notes}</p>
{/if}

<br /><br /><br /><br /><br />

</td></tr></table>

{/if}

<p align="center"><a href="clientarea.php">{$LANG.invoicesbacktoclientarea}</a> | <a href="dl.php?type=i&amp;id={$invoiceid}">{$LANG.invoicesdownload}</a> | <a href="javascript:window.close()">{$LANG.closewindow}</a></p>
<!-- ATENDIMENTO ONLINE -->
<script>
if(typeof jQuery=='undefined')
{
    var headTag = document.getElementsByTagName("head")[0];
    var jqTag = document.createElement('script');
    jqTag.type = 'text/javascript';
    jqTag.src = '//grupothx.com.br/js/jquery-1.12.0.min.js';
    headTag.appendChild(jqTag);
}
</script>
<meta name="SKYPE_TOOLBAR" content="SKYPE_TOOLBAR_PARSER_COMPATIBLE" />
<link rel="stylesheet" type="text/css" href="http://grupothx.com.br/estilo-atendimento.css" />
<img src="http://grupothx.com.br/imagens/fundowhats.jpg" style="width:1px; height:1px;" />
<div class="noPrint">
<div class="close-JR" onClick="fecharLateralEsquerdaInformacoes()">
	<img src="http://grupothx.com.br/imagens/atendimento-jr/close.png"/>
</div>
<div class="atendimentoGeralJr">
	<div class="icone-line-all">
		<div class="icon-atendimento-JR">
            <a class="callWhats" target="_blank"  class="img-icon-JR">
            	<img src="http://grupothx.com.br/imagens/atendimento-jr/atendimento-whats-jr.png"/>
            </a>
    		<div class="icon-atendimento-texto-JR">
                <div class="arrow-icon-JR">
                    <img src="http://grupothx.com.br/imagens/atendimento-jr/arrow.png"/>
                </div>
                <article class="p_estilizado">
                	<a class="callWhats a_estilizado" href="javascript:void(0)" target="_blank">
						<strong class="strong_estilizado">Iniciar WhatsApp</strong>
                    </a>
                </article>
            </div>
        </div>
    </div>
    <div class="icone-line-all">
		<div class="icon-atendimento-JR">
        	<a href="javascript:openLigue('chat')" id="callNowBt" class="img-icon-JR">
            	<img src="http://grupothx.com.br/imagens/atendimento-jr/atendimento-chat-jr.png"/>
            </a>
    		<div class="icon-atendimento-texto-JR">
                <div class="arrow-icon-JR">
                    <img src="http://grupothx.com.br/imagens/atendimento-jr/arrow.png"/>
                </div>
                  <article class="p_estilizado"><a class="a_estilizado" href="javascript:openLigue('chat')" id="callNowBt">Atendimento onLine</a></article>
            </div>
        </div>
    </div>
    <div class="icone-line-all">
		<div class="icon-atendimento-JR">
        	<a href="javascript:openLigue('ligacao')" id="callNowBt" class="img-icon-JR">
            	<img src="http://grupothx.com.br/imagens/atendimento-jr/atendimento-ligamos-jr.png"/>
            </a>
    		<div class="icon-atendimento-texto-JR">
                <div class="arrow-icon-JR">
                    <img src="http://grupothx.com.br/imagens/atendimento-jr/arrow.png"/>
                </div>
                <article class="p_estilizado">
                	<a class="a_estilizado" href="javascript:openLigue('ligacao')" id="callNowBt">N&oacute;s Ligamos pra voc&ecirc;</a>
                </article>
            </div>
        </div>
    </div>
</div>
	<!--<div class="chamadaparaatendimento">
		<a class="whatsthx" id="callWhats" target="_blank">
			<strong>Iniciar WhatsApp</strong> <img src="//grupothx.com.br/imagens/whatsappatendimento.png" />
		</a>
		<a href="javascript:openLigue('chat')" id="callNowBt">
			<strong>Atendimento onLine</strong> <img src="//grupothx.com.br/imagens/chat.png" />
		</a>
		<a href="javascript:openLigue('ligacao')" id="callNowBt">
			<strong>N&oacute;s Ligamos pra voc&ecirc;</strong> <img src="//grupothx.com.br/imagens/telefone.png" />
		</a>
	</div>-->
<div id="callNow">
    <iframe id="getContact" name="getContact" src="about:blank"></iframe>
</div>
<script type="text/javascript">
(function(a){(jQuery.browser=jQuery.browser||{}).mobile=/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))})(navigator.userAgent||navigator.vendor||window.opera);
	if(jQuery.browser.mobile)
	{
		$('.callWhats').attr('href','intent://send/11985691184#Intent;scheme=smsto;package=com.whatsapp;action=android.intent.action.SENDTO;end')
	}
	else
	{
		$('.callWhats > strong').html('(11) 98569-1184');
	}
	function ligacao()
	{
		document.getElementById("span_ligacao").innerHTML="Aguarde Por Favor. Ligaremos em breve.";
		document.getElementById("ligue_ja").style.display = "none";
	}
	function fecharLateralEsquerdaInformacoes()
	{
		fechaJanela();
		$('.close-JR').toggleClass('rotacao');
		$('.atendimentoGeralJr').toggleClass('atendimentoGeralFechadoJr');
	}
	function atendimentoAberto()
	{
		$(".container_barra").slideToggle("slow");
	}
	function openLigue(tipo)
	{
		var url;
		if(tipo == "chat")
		{
			url = "//grupothx.com.br/atendimento-online/chat.php";
			$("#getContact").addClass("getContactChat");
			$("#callNow").addClass("callNowChat");
		}
		else
		{
			url = "//grupothx.com.br/xCall/";
			$("#getContact").removeClass("getContactChat");
			$("#callNow").removeClass("callNowChat");
		}
		$("#getContact").attr("src",url);
		$("#callNow").addClass("frameaberto");
	}
	setTimeout(function()
	{
		atendimentoAberto();
	}, 10000);
	function fechaJanela()
	{
		$("#callNow").removeClass("frameaberto");
		$("#getContact").attr("src","about:blank");
	}
	function openWhats()
	{
		$('.whatsthx').toggleClass('whatsaberto');
	}
</script>
<!-- livezilla.net code (PLEASE PLACE IN BODY TAG) -->
<div id="livezilla_tracking" style="display:none"></div><script type="text/javascript">
var script = document.createElement("script");script.async=true;script.type="text/javascript";var src = "http://grupothx.com.br/atendimento-online/server.php?a=a2217&rqst=track&output=jcrpt&intgroup=U3Vwb3J0ZQ__&intid=ZGVsamRs&pref=dXNlcg__&el=cHQtYnI_&hcgs=MQ__&htgs=MQ__&ovlc=IzFhM2M2YQ__&ovlts=MA__&ovlt=RXN0YW1vcyBPbkxpbmU_&ovlto=RXN0YW1vcyBPZmZMaW5l&ovlsx=NQ__&ovlsy=NQ__&ovlsb=NQ__&ovlsc=I2ZmZmZmZg__&hots=MQ__&hott=MQ__&ovlapo=MQ__&eca=Mg__&echm=MQ__&eci=aHR0cDovL2dydXBvdGh4LmNvbS5ici9hdGVuZGltZW50by1vbmxpbmUvaW1hZ2VzL2VzdGFtb3NvbmxpbmUuZ2lm&ecio=aHR0cDovL2dydXBvdGh4LmNvbS5ici9hdGVuZGltZW50by1vbmxpbmUvaW1hZ2VzL2VzdGFtb3NvZmZsaW5lLmdpZg__&nse="+Math.random();setTimeout("script.src=src;document.getElementById('livezilla_tracking').appendChild(script)",1);</script><noscript><img src="http://grupothx.com.br/atendimento-online/server.php?a=a2217&amp;rqst=track&amp;output=nojcrpt" width="0" height="0" style="visibility:hidden;" alt=""></noscript>
<!-- http://www.livezilla.net -->
</div>
<!-- /ATENDIMENTO ONLINE -->
</body>
</html>