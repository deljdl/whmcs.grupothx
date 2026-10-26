{if $filename eq 'index'}
{else}
</div>
</div>
<div class="clr"></div>
</div>
<!--EOF LeftCon-->
<div id="rightCon">
  <div class="sidebox2">
    <h1>Client Menu</h1>
    <ul>
      {if $loggedin}
      <li><a href="clientarea.php" title="{$LANG.clientareanavhome}">{$LANG.clientareanavhome}</a></li>
      <li><a href="clientarea.php?action=details" title="{$LANG.clientareanavdetails}">{$LANG.clientareanavdetails}</a></li>
      <li><a href="clientarea.php?action=products" title="{$LANG.clientareanavservices}">{$LANG.clientareanavservices}</a></li>
      <li><a href="clientarea.php?action=domains" title="{$LANG.clientareanavdomains}">{$LANG.clientareanavdomains}</a></li>
      <li><a href="clientarea.php?action=quotes" title="{$LANG.quotestitle}">{$LANG.quotestitle}</a></li>
      <li><a href="clientarea.php?action=invoices" title="{$LANG.invoices}">{$LANG.invoices}</a></li>
      <li><a href="supporttickets.php" title="{$LANG.clientareanavsupporttickets}">{$LANG.clientareanavsupporttickets}</a></li>
      {else}
      <li><a href="clientportal.php" title="{$LANG.globalsystemname}">{$LANG.globalsystemname}</a></li>
      <li><a href="register.php" title="{$LANG.clientregistertitle}">{$LANG.clientregistertitle}</a></li>
      <li><a href="clientarea.php" title="{$LANG.clientareatitle}">{$LANG.clientareatitle}</a></li>
      <li><a href="announcements.php" title="{$LANG.announcementstitle}">{$LANG.announcementstitle}</a></li>
      <li><a href="knowledgebase.php" title="{$LANG.knowledgebasetitle}">{$LANG.knowledgebasetitle}</a></li>
      <li><a href="submitticket.php" title="{$LANG.supportticketspagetitle}">{$LANG.supportticketssubmitticket}</a></li>
      <li><a href="downloads.php" title="{$LANG.downloadstitle}">{$LANG.downloadstitle}</a></li>
      {/if}
    </ul>
  </div>
  {if $loggedin}
  <div class="sidebox2">
    <h1>Account</h1>
    <p><strong>{$clientsdetails.firstname} {$clientsdetails.lastname} {if $clientsdetails.companyname}({$clientsdetails.companyname}){/if}</strong><br />
      {$clientsdetails.address1}, {$clientsdetails.address2}<br />
      {$clientsdetails.city}, {$clientsdetails.state}, {$clientsdetails.postcode}<br />
      {$clientsdetails.countryname}<br />
      {$clientsdetails.email}<br />
      {$LANG.statsnumproducts}: <strong>{$clientsstats.productsnumactive}</strong> ({$clientsstats.productsnumtotal})<br />
      {$LANG.statsnumdomains}: <strong>{$clientsstats.numactivedomains}</strong> ({$clientsstats.numdomains})<br />
      {$LANG.statsnumtickets}: <strong>{$clientsstats.numtickets}</strong><br />
      {$LANG.statsnumreferredsignups}: <strong>{$clientsstats.numaffiliatesignups}</strong><br />
      {$LANG.statscreditbalance}: <strong>{$clientsstats.creditbalance}</strong><br />
      {$LANG.statsdueinvoicesbalance}: <strong>{if $clientsstats.numdueinvoices>0}<span class="red">{/if}{$clientsstats.dueinvoicesbalance}{if $clientsstats.numdueinvoices>0}</span>{/if}</strong></p>
  </div>
  {else}
  <div class="sidebox2">
    <h1>Client Login</h1>
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
  {/if}
  {/if} </div>
<!--EOF rightCon-->
<div class="clr"></div>
</div>
<div id="footerWrapper">
  <div class="wrapper">
    <div class="sitemap">
      <h1>Clients</h1>
      <ul>
        <li><a href="clientarea.php">Client Area</a></li>
        <li><a href="register.php">Register</a></li>
        <li><a href="pwreset.php">Reset Password</a></li>
      </ul>
    </div>
    <div class="sitemap">
      <h1>Hosting Solutions</h1>
      <ul>
        <li><a href="sharedhosting.php">Shared Hosting</a></li>
        <li><a href="domainchecker.php">Domain Names</a></li>
        <li><a href="cart.php">Order Now</a></li>
      </ul>
    </div>
    <div class="sitemap">
      <h1>Partners</h1>
      <ul>
        <li><a href="http://themeforest.net/?ref=ZumadaLimited" target="_blank">Theme Forest</a></li>
        <li><a href="http://graphicriver.net/?ref=ZumadaLimited" target="_blank">Graphic River</a></li>
        <li><a href="http://codecanyon.net/?ref=ZumadaLimited" target="_blank">Code Canyon</a></li>
      </ul>
    </div>
    <div class="sitemap">
      <h1>WHMCS Templates</h1>
      <ul>
        <li><a href="http://www.whmcsthemes.com/premium-whmcs-templates.php" target="_blank">Premium WHMCS Templates</a></li>
        <li><a href="http://www.whmcsthemes.com/whmcs-templates.php" target="_blank">Low Cost WHMCS Templates</a></li>
        <li><a href="http://www.freewhmcstemplates.com/" target="_blank">Free WHMCS Templates</a></li>
      </ul>
    </div>
    <div class="clr"></div>
    <div class="copyright">&copy; Copyright 2012 - <a href="http://www.whmcsthemes.com" target="_blank">WHMCSThemes.com</a> - All Rights Reserved.</div>
  </div>
</div>
{$footeroutput}
</body></html>