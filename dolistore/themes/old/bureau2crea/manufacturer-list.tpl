{capture name=path}{l s='Manufacturers'}{/capture}
{include file=$tpl_dir./breadcrumb.tpl}

<h2>{l s='Manufacturers'}</h2>

{if isset($errors) AND $errors}
	{include file=$tpl_dir./errors.tpl}
{else}

	<p>{if $nbManufacturers > 1}{l s='There are'} <span class="bold">{$nbManufacturers} {l s='manufacturers.'}</span>{else}{l s='There is'} <span class="bold">{$nbManufacturers} {l s='manufacturer.'}</span>{/if}</p>

{if $nbManufacturers > 0}
	{include file=$tpl_dir./product-sort.tpl}
	<ul id="manufacturers_list">
	{foreach from=$manufacturers item=manufacturer}
		<li>
			<div class="left_side">
				<!-- logo -->
				<div class="logo">
				{if $manufacturer.nb_products > 0}<a href="{$link->getmanufacturerLink($manufacturer.id_manufacturer, $manufacturer.link_rewrite)|escape:'htmlall':'UTF-8'}" title="{$manufacturer.name|escape:'htmlall':'UTF-8'}">{/if}
					<img src="{$img_manu_dir}{$manufacturer.image|escape:'htmlall':'UTF-8'}-medium.jpg" alt="" />
				{if $manufacturer.nb_products > 0}</a>{/if}
				</div>
				<!-- name -->
				<h3>
					{if $manufacturer.nb_products > 0}<a href="{$link->getmanufacturerLink($manufacturer.id_manufacturer, $manufacturer.link_rewrite)|escape:'htmlall':'UTF-8'}">{/if}
					{$manufacturer.name|truncate:60:'...'|escape:'htmlall':'UTF-8'}
					{if $manufacturer.nb_products > 0}</a>{/if}
				</h3>
				<p class="description rte">
				{if $manufacturer.nb_products > 0}<a href="{$link->getmanufacturerLink($manufacturer.id_manufacturer, $manufacturer.link_rewrite)|escape:'htmlall':'UTF-8'}">{/if}
				{$manufacturer.description}
				{if $manufacturer.nb_products > 0}</a>{/if}
				</p>
			</div>

			<div class="right_side">
			{if $manufacturer.nb_products > 0}<a href="{$link->getmanufacturerLink($manufacturer.id_manufacturer, $manufacturer.link_rewrite)|escape:'htmlall':'UTF-8'}">{/if}
				<span>{$manufacturer.nb_products|intval} {if $manufacturer.nb_products > 1}{l s='products'}{else}{l s='product'}{/if}</span>
			{if $manufacturer.nb_products > 0}</a>{/if}

			{if $manufacturer.nb_products > 0}
				<a class="button" href="{$link->getmanufacturerLink($manufacturer.id_manufacturer, $manufacturer.link_rewrite)|escape:'htmlall':'UTF-8'}">{l s='view products'}</a>
			{/if}

			</div>
			<br class="clear"/>
		</li>
	{/foreach}
	</ul>
	{include file=$tpl_dir./pagination.tpl}
{/if}
{/if}
