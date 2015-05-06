{capture name=path}{l s='Suppliers'}{/capture}
{include file=$tpl_dir./breadcrumb.tpl}

<h2>{l s='Suppliers'}</h2>

{if isset($errors) AND $errors}
	{include file=$tpl_dir./errors.tpl}
{else}

	<p>{if $nbSuppliers > 1}{l s='There are'} <span class="bold">{$nbSuppliers} {l s='suppliers.'}</span>{else}{l s='There is'} <span class="bold">{$nbSuppliers} {l s='supplier.'}</span>{/if}</p>

{if $nbSuppliers > 0}
	{include file=$tpl_dir./product-sort.tpl}
	<ul id="suppliers_list">
	{foreach from=$suppliers item=supplier}
		<li>
			<div class="left_side">

				<!-- logo -->
				<div class="logo">
				{if $supplier.nb_products > 0}
				<a href="{$link->getsupplierLink($supplier.id_supplier, $supplier.link_rewrite)|escape:'htmlall':'UTF-8'}" title="{$supplier.name|escape:'htmlall':'UTF-8'}">
				{/if}
					<img src="{$img_sup_dir}{$supplier.image|escape:'htmlall':'UTF-8'}-medium.jpg" alt="" />
				{if $supplier.nb_products > 0}
				</a>
				{/if}
				</div>

				<!-- name -->
				<h3>
					{if $supplier.nb_products > 0}
					<a href="{$link->getsupplierLink($supplier.id_supplier, $supplier.link_rewrite)|escape:'htmlall':'UTF-8'}">
					{/if}
					{$supplier.name|truncate:60:'...'|escape:'htmlall':'UTF-8'}
					{if $supplier.nb_products > 0}
					</a>
					{/if}
				</h3>
				<p class="description">
				{if $supplier.nb_products > 0}
					<a href="{$link->getsupplierLink($supplier.id_supplier, $supplier.link_rewrite)|escape:'htmlall':'UTF-8'}">
				{/if}
				{$supplier.description|escape:'htmlall':'UTF-8'}
				{if $supplier.nb_products > 0}
				</a>
				{/if}
				</p>

			</div>

			<div class="right_side">
			
			{if $supplier.nb_products > 0}
				<a href="{$link->getsupplierLink($supplier.id_supplier, $supplier.link_rewrite)|escape:'htmlall':'UTF-8'}">
			{/if}
				<span>{$supplier.nb_products|intval} {if $supplier.nb_products > 1}{l s='products'}{else}{l s='products'}{/if}</span>
			{if $supplier.nb_products > 0}
				</a>
			{/if}

			{if $supplier.nb_products > 0}
				<a class="button" href="{$link->getsupplierLink($supplier.id_supplier, $supplier.link_rewrite)|escape:'htmlall':'UTF-8'}">{l s='view products'}</a>
			{/if}

			</div>
			<br class="clear"/>
		</li>
	{/foreach}
	</ul>
	{include file=$tpl_dir./pagination.tpl}
{/if}
{/if}
