{if $filename eq 'index'}
{else}
{if $filename eq 'sharedhosting'}
{else}
{if $langchange}<div align="right">{$setlanguage}</div><br />{/if}
</div>
</div>

<div class="clr"></div>
{/if}
</div>
<div class="bodyRight">
  <div class="siderBox01Con">
    <div class="siderBox01Top">Área do Cliente</div>
    <div class="siderBox01Content">
      <ul class="ulStyle03">
      {if $loggedin}
      <li><img src="templates/{$template}/images/play.png" align="absmiddle" border="0px" /><a href="clientarea.php" title="{$LANG.clientareanavhome}">{$LANG.clientareanavhome}</a></li>
      <li><img src="templates/{$template}/images/play.png" align="absmiddle" border="0px" /><a href="clientarea.php?action=details" title="{$LANG.clientareanavdetails}">{$LANG.clientareanavdetails}</a></li>
      <li><img src="templates/{$template}/images/play.png" align="absmiddle" border="0px" /><a href="clientarea.php?action=products" title="{$LANG.clientareanavservices}">{$LANG.clientareanavservices}</a></li>
      <li><img src="templates/{$template}/images/play.png" align="absmiddle" border="0px" /><a href="clientarea.php?action=domains" title="{$LANG.clientareanavdomains}">{$LANG.clientareanavdomains}</a></li>
      <li><img src="templates/{$template}/images/play.png" align="absmiddle" border="0px" /><a href="clientarea.php?action=quotes" title="{$LANG.quotestitle}">{$LANG.quotestitle}</a></li>
      <li><img src="templates/{$template}/images/play.png" align="absmiddle" border="0px" /><a href="clientarea.php?action=invoices" title="{$LANG.invoices}">{$LANG.invoices}</a></li>
      <li><img src="templates/{$template}/images/play.png" align="absmiddle" border="0px" /><a href="supporttickets.php" title="{$LANG.clientareanavsupporttickets}">{$LANG.clientareanavsupporttickets}</a></li>
      {else}
      <li><img src="templates/{$template}/images/play.png" align="absmiddle" border="0px" /><a href="clientportal.php" title="{$LANG.globalsystemname}">{$LANG.globalsystemname}</a></li>
      <li><img src="templates/{$template}/images/play.png" align="absmiddle" border="0px" /><a href="register.php" title="{$LANG.clientregistertitle}">{$LANG.clientregistertitle}</a></li>
      <li><img src="templates/{$template}/images/play.png" align="absmiddle" border="0px" /><a href="clientarea.php" title="{$LANG.clientareatitle}">{$LANG.clientareatitle}</a></li>
      <li><img src="templates/{$template}/images/play.png" align="absmiddle" border="0px" /><a href="announcements.php" title="{$LANG.announcementstitle}">{$LANG.announcementstitle}</a></li>
      <li><img src="templates/{$template}/images/play.png" align="absmiddle" border="0px" /><a href="knowledgebase.php" title="{$LANG.knowledgebasetitle}">{$LANG.knowledgebasetitle}</a></li>
      <li><img src="templates/{$template}/images/play.png" align="absmiddle" border="0px" /><a href="submitticket.php" title="{$LANG.supportticketspagetitle}">{$LANG.supportticketssubmitticket}</a></li>
      <li><img src="templates/{$template}/images/play.png" align="absmiddle" border="0px" /><a href="downloads.php" title="{$LANG.downloadstitle}">{$LANG.downloadstitle}</a></li>
      {/if}
      </ul>
      <p>&nbsp;</p>
    </div>
  </div>


{if $loggedin}
  <div class="siderBox01Con">
    <div class="siderBox01Top">{$LANG.accountinfo}</div>
    <div class="siderBox01Content">
      <div class="planContent">
<p><strong>{$clientsdetails.firstname} {$clientsdetails.lastname} {if $clientsdetails.companyname}({$clientsdetails.companyname}){/if}</strong><br />
{$clientsdetails.address1}, {$clientsdetails.address2}<br />
{$clientsdetails.city}, {$clientsdetails.state}, {$clientsdetails.postcode}<br />
{$clientsdetails.countryname}<br />
{$clientsdetails.email}<br /><br />
{if $condlinks.addfunds}<img src="templates/{$template}/images/icons/money.gif" alt="Add Funds" width="22" height="22" border="0" class="absmiddle" /> <a href="clientarea.php?action=addfunds">{$LANG.addfunds}</a>{/if}</p>
      </div>
	</div>
  </div>
  <div class="siderBox01Con">
    <div class="siderBox01Top">{$LANG.accountstats}</div>
    <div class="siderBox01Content">
      <div class="planContent">
    <p>{$LANG.statsnumproducts}: <strong>{$clientsstats.productsnumactive}</strong> ({$clientsstats.productsnumtotal})<br />
{$LANG.statsnumdomains}: <strong>{$clientsstats.numactivedomains}</strong> ({$clientsstats.numdomains})<br />
{$LANG.statsnumtickets}: <strong>{$clientsstats.numtickets}</strong><br />
{$LANG.statsnumreferredsignups}: <strong>{$clientsstats.numaffiliatesignups}</strong><br />
{$LANG.statscreditbalance}: <strong>{$clientsstats.creditbalance}</strong><br />
{$LANG.statsdueinvoicesbalance}: <strong>{if $clientsstats.numdueinvoices>0}<span class="red">{/if}{$clientsstats.dueinvoicesbalance}{if $clientsstats.numdueinvoices>0}</span>{/if}</strong></p>
      </div>
	</div>
  </div>
{else}
  <div class="siderBox01Con">
    <div class="siderBox01Top">{$LANG.clientlogin}</div>
    <div class="siderBox01Content">
      <div class="planContent">
<form method="post" action="{$systemsslurl}dologin.php">
  <p><strong>{$LANG.email}</strong><br />
    <input name="username" type="text" size="25" />
  </p>
  <p><strong>{$LANG.loginpassword}</strong><br />
    <input name="password" type="password" size="25" />
  </p>
  <p>
    <input type="checkbox" name="rememberme" />
    {$LANG.loginrememberme}</p>
  <p>
    <input type="submit" class="submitbutton" value="{$LANG.loginbutton}" />
  </p>
</form>
      </div>
	</div>
  </div>
  <div class="siderBox01Con">
    <div class="siderBox01Top">{$LANG.knowledgebasesearch}</div>
    <div class="siderBox01Content">
      <div class="planContent">
<form method="post" action="knowledgebase.php?action=search">
  <p>
    <input name="search" type="text" size="25" /><br /><br />
    <select name="searchin">
      <option value="Knowledgebase">{$LANG.knowledgebasetitle}</option>
      <option value="Downloads">{$LANG.downloadstitle}</option>
    </select>
    <input type="submit" value="{$LANG.go}" />
  </p>
</form>
      </div>
	</div>
  </div>
{/if}
{if $twitterusername}
  <div class="siderBox01Con">
    <div class="siderBox01Top">Parceiros</div>
    <div class="siderBox01Content">
      <div class="planContent">
		<a href="http://xpainel.com/site" target="_blank" title="X-Painel Rental Code - Simples Preciso Correto"><img style="max-width:130px; float:left; margin-top:15px;" src="http://xpainel.com/site/images/main-logo.png" border="0" /></a>
		<a href="http://tiwebdesign.com.br" target="_blank" title="TiWebDesign - Design and Expertise"><img style="max-width:100px; float:right;" src="http://tiwebdesign.com.br/assinatura_email/logo.png" border="0" /></a>
      </div>
	</div>
  </div>
{/if}
{/if}
</div>
</div>
</td>
<td class="bgRight">
</td>
</tr>
<tr><td></td>
<td>
{literal}
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/pt_BR/all.js#xfbml=1&appId=232875226851576";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>


<div class="fb-like-box" data-href="https://www.facebook.com/tihospedagem" data-width="900" data-colorscheme="light" data-show-faces="true" data-header="false" data-stream="false" data-show-border="false"></div>
{/literal}
</td>
<td></td>
</tr>
<tr class="footer-jdl">
  <td class="footerL">&nbsp;</td>
  <td class="footer"><div class="wrapper">
      <div class="siteMapCon">
        <div class="siteMap">
      <h4>Clientes</h4>
      <ul>
        <li><a href="clientarea.php">Área do Cliente</a></li>
        <li><a href="register.php">Cadastro</a></li>
        <li><a href="pwreset.php">Recuperar Senha</a></li>
      </ul>
        </div>
        <div class="siteMap">
      <h4>Produtos</h4>
      <ul>
        <li><a href="hospedagem-de-sites/planos">Hospedagem de Sites</a></li>
        <li><a href="domainchecker.php">Registro de Domínios</a></li>
		<li><a href="atendimento-on-line-para-seu-site">Atendimento OnLine</a></li>
		<li><a href="email-marketing">Email Marketing</a></li>
	  </ul>
        </div>
        <div class="siteMap">
      <h4>Criação</h4>
      <ul>
        <li><a href="criacao-de-sites">Sites</a></li>
		<li><a href="sites-prontos">Sites Prontos</a></li>
		<li><a href="criacao-de-lojas-virtuais">Lojas Virtuais</a></li>
		<li><a href="certificado-digital">Certificado Digital</a></li>
      </ul>
        </div>
        <!--<div class="siteMap noMR">
      <h4>WHMCS</h4>
      <ul>
        <li><a href="http://www.whmcsthemes.com/premium-whmcs-templates.php" target="_blank">Premium Themes</a></li>
        <li><a href="http://www.whmcsthemes.com/whmcs-templates.php" target="_blank">Low Cost Themes</a></li>
        <li><a href="http://www.freewhmcstemplates.com/" target="_blank">Free Themes</a></li>
      </ul>
        </div>-->
        <div class="socailCon">
          <h6>Siga-nos !!!</h6>
          <div class="socailNet"><a href="http://www.facebook.com/tihospedagem" target="_blank"></a><a href="http://www.twitter.com/tihospedagem" target="_blank"></a><a href="https://plus.google.com/b/100537399796746134357/100537399796746134357/about?hl=pt-BR" target="_blank" class="noMR"></a> </div>
          <p>TIHospedagem <br />Uma empresa do <a target="_blank" href="http://grupothx.com.br">Grupo THX</a></p>
        </div>
      </div>
    </div></td>
  <td class="footerR">&nbsp;</td>
</tr>
</table>
{$footeroutput}

<script type="text/javascript">
$('[href="http://www.whmcs.com/"]').parent().remove();
</script>

<!--ATENDIMENTO-->

{php}
echo file_get_contents('http://grupothx.com.br/scriptAtendimentoOnlineTodosOsSites.php');
{/php}

<!--ATENDIMENTO--> 
</body></html>