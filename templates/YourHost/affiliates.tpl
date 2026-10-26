{if $inactive}
<p>{$LANG.affiliatesdisabled}</p>
{else}
<h1>Bem Vindo <span style="color:#BF0C0C">{$loggedinuser.firstname}</span> </h1>
<h2>Aqui você encontra seus relatórios e ferramentas para divulgar seu link de afiliado.</h2>
<p><h2>Leia atentamente as regras.</h2>
<textarea style="width:550px; height:100px" readonly>
1 - É necessário ser cliente e ter pelo menos 1 serviço ativo ou ser funcionário direto da empresa para participar do programa de afiliados.
2 - Caso algum cliente que não tenha nenhum serviço ativo passar a utilizar o programa de afiliados o bonus e a comissão serão contabilizados normalmente no entanto o valor não poderá ser resgatado e mesmo que futuramente seja adquirido algum serviço o bonus e comissões contabilizados até esta data serão eliminados e passarão a contar a partir do momento da contratação do serviço.
3 - Caso seja cliente e esteja participando do programa de afiliados dentro das normas dos itens 1 e 2 mesmo e queira cancelar seus serviços poderá continuar recebendo comissões sobre os seus indicados, desde que os seus serviços cancelados não sejam recadastrados por algum de seus indicados.
4 - É proibido fazer cadastro duplicado visando receber porcentagem sobre o serviço do seu próprio uso, caso seja descoberto terá o cadastro inabilitado do programa de afiliados e não poderá resgatar o valor já em conta.
5 - No momento de entrar para o Programa de Afiliados receberá um bonus no valor de R$ 10,00 sobre o qual será somado as comissões e poderá ser regatado quando atingir o valor necessário para resgate.
6 - A comissão será de 20% sobre o valor pago por seus afiliados
7 - A retirada do valor poderá ser solicitada quando alcançar R$ 25,00
8 - O valor quando requerido será utilizado primeiramente para cobrir faturas em aberto em seu cadastro o restante ficará como créditos em sua conta cobrindo qualquer cobrança futuraque seja gerada em seu cadastro.
9 - Para entrar no Programa de Afiliados acesse a Central do Cliente com seu usuário e senha e clique na opção "Afiliados" e então no botão que aparecerá após as instruções básicas do programa.
10 - Todos seus ganhos inclusive quantidade de indicados e outros dados poderão ser acompanhados em tempo real pela opção "Afiliados" na Central do Cliente.
11 - É proibído o uso de qualquer tipo de prática ilegal para conseguir indicados, incluindo envio de spam, em caso de descumprimento desta norma todos os recursos já contabilizados serão bloqueados.
12 - A única forma de receber a comissão é seu indicado fazer a compra acessando através do seu link exlusivo que está disponível na Central do Cliente na opção "Afiliados" não aceitaremos pedidos de inclusão manual.
13 - Todos os serviços dispostos em nosso site proporcionam ganhos com o Sistema de Afiliados com excesão do serviço de Registro de Domínios
14 - O valor pago pelo seu indicado será contabilizado em sua conta a cada pagamento e não apenas no primeiro pagamento, ou seja caso seu indicado faça pagamento mensal por 3 anos você receberá sua comissão mensalmente durante todo o período.
15 - O programa de afiliados poderá ser suspenso ou finalizado em qualquer, em caso de suspensão os valores abaixo do mínimo para retirada serão bloqueados até o retorno, valores acima do mínimo para retirada poderão ser sacados, em caso de cancelamento quem não tiver atingido o valor mínimo também poderá retirar mais neste caso o valor do bonus inicial será descontado.
16 - No caso de funcionários internos da empresa Grupo THX a validade das afiliações será mantida apenas enquanto o funcionário tiver ligação direta com a empresa. Visto que os clientes foram adicionados usando os recursos da empresa.
17 - Ao se tornar um afiliado você concorda integralmente com os termos acima. 
</textarea>
<hr style="background-color:#BF0101; height:2px">
<table border="0" width="100%" cellpadding="5" cellspacing="5">
<tr height="150">
<td><h2>{$LANG.affiliatesreferallink}:</h2><input readonly type="text" size="60" value="{$referrallink}"><br /> Qualquer cliente que acessar seu link terá salvo no computador dele seu código de indicação. Assim , mesmo que ele não contrate o serviço no ato do seu contato, sua indicação será contabilizada.</td>
<td rowspan="3" width="170px" align="right"><img src="//tihospedagem.com.br/imagens/anuncio1.jpg" border="0" /></td>
</tr>
<tr height="200">
<td>
	<h2>Seu código para divulgação de seu banner:</h2>
  <textarea readonly style="width:400px; height:100px;"><a titlle="TiHospedagem" href="{$referrallink}"><img src="//tihospedagem.com.br/imagens/anuncio1.jpg" border="0" /></a></textarea>
  Qualquer cliente que acessar seu banner terá salvo no computador dele seu código de indicação. Assim , mesmo que ele não contrate o serviço no ato do seu contato, sua indicação será contabilizada.
</td>
</tr>
<tr><td>
<p><h2>Espalhe seu link pelas redes e ganhe dinheiro.</h2></p>
<table width="100%"><tr valign="top"><td>
{literal}
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/pt_BR/all.js#xfbml=1&appId=232875226851576";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
{/literal}
<fb:share-button style="border:solid #EEEEEE 1px; height:37px; padding-top:5px; overflow:hidden;" href="{$referrallink}" type="box_count"></fb:share-button><br>
<a onClick="window.open('http://www.facebook.com/sharer.php?s=100&amp;p[title]=Conheça a TiHospedagem&amp;p[summary]=TiHospedagem - Hospede seu Site em um local seguro de com preço acessível. Seu site no ar no mesmo dia. Migração ou Instalação Gratuita. Fazemos toda a configuração sem custo nenhum.&amp;p[url]={$referrallink}&amp;&amp;p[images][0]=http://tihospedagem.com.br/imagens/face-share.png','sharer','toolbar=0,status=0,width=548,height=325');" href="javascript: void(0)">
<img src="imagens/afiliado-facebook.png" border="0" />
</a>
</td>
<td>
        <a href="https://twitter.com/share" target="_blank" class="twitter-share-button" data-url="{$referrallink}" data-text="TiHospedagem - Crie  seu site e hospede em um local seguro de com preço acessível."  data-lang="pt-br"  data-count="vertical"></a>

    {literal}<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="https://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>{/literal}
</td>

</tr>
</table>
</td></tr>
</table>
<hr style="background-color:#BF0101; height:2px">
<p><h2>Resumo de sua conta de Afiliados:</h2></p>
<table width="100%" cellpadding="0" cellspacing="0" class="frame" border="0">
  <tr>
    <td><table width="100%" border="0" align="center" cellpadding="10" cellspacing="0">
        <tr>
          <td width="230" class="fieldarea">{$LANG.affiliatesvisitorsreferred}:</td>
          <td>{$visitors}</td>
        </tr>
        <tr>
          <td width="230" class="fieldarea">{$LANG.affiliatessignups}:</td>
          <td>{$signups}</td>
        </tr>
        <tr>
          <td width="230" class="fieldarea">{$LANG.affiliatesconversionrate}:</td>
          <td>{$conversionrate}%</td>
        </tr>
    </table></td>
  </tr>
</table>
<br />
<table width="100%" cellpadding="0" cellspacing="0" class="frame" border="0">
  <tr>
    <td><table width="100%" border="0" align="center" cellpadding="10" cellspacing="0">
        <tr>
          <td width="230" class="fieldarea">{$LANG.affiliatescommissionspending}:</td>
          <td>{$pendingcommissions}</td>
        </tr>
        <tr>
          <td width="230" class="fieldarea">{$LANG.affiliatescommissionsavailable}:</td>
          <td>{$balance}</td>
        </tr>
        <tr>
          <td width="230" class="fieldarea">{$LANG.affiliateswithdrawn}:</td>
          <td>{$withdrawn}</td>
        </tr>
    </table></td>
  </tr>
</table>
{if $withdrawrequestsent}
<p align="center">{$LANG.affiliateswithdrawalrequestsuccessful}</p>
{else}
{if $withdrawlevel}
<p align="center">
  <input type="button" value="{$LANG.affiliatesrequestwithdrawal}" onclick="window.location='{$smarty.server.PHP_SELF}?action=withdrawrequest'" class="button">
</p>
{/if}
{/if}
<h2>{$LANG.affiliatesreferals}</h2>
<table width="100%" border="0" align="center" cellpadding="10" cellspacing="0" class="data">
  <tr>
    <th>{$LANG.affiliatessignupdate}</th>
    <th>{$LANG.orderproduct}</th>
    <th>{$LANG.affiliatesamount}</th>
    <th>{$LANG.affiliatescommission}</th>
    <th>{$LANG.affiliatesstatus}</th>
  </tr>
  {foreach key=num item=referral from=$referrals}
  <tr>
    <td>{$referral.date}</td>
    <td>{$referral.service}</td>
    <td>{$referral.amountdesc}</td>
    <td>{$referral.commission}</td>
    <td>{$referral.status}</td>
  </tr>
  {foreachelse}
  <tr>
    <td colspan="5">{$LANG.affiliatesnosignups}</td>
  </tr>
  {/foreach}
</table>
<hr style="background-color:#BF0101; height:2px">
{if $affiliatelinkscode}
<p><strong>{$LANG.affiliateslinktous}</strong></p>
<p align="center">{$affiliatelinkscode}</p>
{/if}

{/if}