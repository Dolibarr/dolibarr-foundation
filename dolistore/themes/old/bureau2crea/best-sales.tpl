{capture name=path}{l s='Top sellers'}{/capture}
{include file=$tpl_dir./breadcrumb.tpl}

<h2>{l s='Top sellers'}</h2>

{if $products}
	{include file=$tpl_dir./product-sort.tpl}
	{include file=$tpl_dir./product-list.tpl products=$products}
	{include file=$tpl_dir./pagination.tpl}
{else}
	<p class="warning">{l s='No top sellers.'}</p>
{/if}
