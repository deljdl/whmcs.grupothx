<li menuitemname="AAAccount" class="dropdown" id="SSSecondary_Navbar-Account">
<a class="dropdown-toggle" data-toggle="dropdown" href="#">
            Suporte
            &nbsp;<b class="caret"></b>        </a>
        <ul class="dropdown-menu">
			<li><a target="_blank" href="https://grupothx.com/suporte">Acesso Remoto</a></li>
			<li><a target="_blank" href="https://grupothx.com/suporte/TeamViewer9.exe">Acesso Remoto (Agências)</a></li>
        </ul>
</li>
<li menuitemname="AAAccountsss" class="dropdown" id="SSSecondary_Navbar-Account">
<a class="dropdown-toggle" data-toggle="dropdown" href="#">
            Serviços
            &nbsp;<b class="caret"></b>        </a>
        <ul class="dropdown-menu">
            <li><a href="https://grupothx.com/thx/hospedagem-de-sites/planos">Hospedagem</a></li>
            <li><a href="https://grupothx.com/thx/email-marketing">E-Mail Marketing</a></li>
            <li><a href="https://grupothx.com/thx/gestor-de-conteudo-para-sites">Gestor de Conteúdo</a></li>
            <li><a href="https://grupothx.com/thx/atendimento-on-line-para-seu-site">Atendimento OnLine</a></li>
            <li><a href="https://grupothx.com/thx/links-patrocinados">Links Patrocinados</a></li>
            <li><a href="https://grupothx.com/thx/criacao-de-sites">Criação de sites</a></li>
            <li><a href="https://grupothx.com/thx/criacao-de-lojas-virtuais">Lojas Virtuais</a></li>
            <li><a href="https://grupothx.com/thx/certificado-digital">Certificado Digital</a></li>
        </ul>
</li>
{foreach $navbar as $item}
    <li menuItemName="{$item->getName()}"{if $item->hasChildren()} class="dropdown"{elseif $item->getClass()} class="{$item->getClass()}"{/if} id="{$item->getId()}">
        <a {if $item->hasChildren()}class="dropdown-toggle" data-toggle="dropdown" href="#"{else}href="{$item->getUri()}"{/if}{if $item->getAttribute('target')} target="{$item->getAttribute('target')}"{/if}>
            {if $item->hasIcon()}<i class="{$item->getIcon()}"></i>&nbsp;{/if}
            {$item->getLabel()}
            {if $item->hasBadge()}&nbsp;<span class="badge">{$item->getBadge()}</span>{/if}
            {if $item->hasChildren()}&nbsp;<b class="caret"></b>{/if}
        </a>
        {if $item->hasChildren()}
            <ul class="dropdown-menu">
            {foreach $item->getChildren() as $childItem}
                <li menuItemName="{$childItem->getName()}"{if $childItem->getClass()} class="{$childItem->getClass()}"{/if} id="{$childItem->getId()}">
                    <a href="{$childItem->getUri()}"{if $childItem->getAttribute('target')} target="{$childItem->getAttribute('target')}"{/if}>
                        {if $childItem->hasIcon()}<i class="{$childItem->getIcon()}"></i>&nbsp;{/if}
                        {$childItem->getLabel()}
                        {if $childItem->hasBadge()}&nbsp;<span class="badge">{$childItem->getBadge()}</span>{/if}
                    </a>
                </li>
            {/foreach}
            </ul>
        {/if}
    </li>
{/foreach}
