{capture name=path}{l s='New products'}{/capture}
{include file=$tpl_dir./breadcrumb.tpl}

<h2>{l s='New products'}</h2>

{if $products}
	{include file=$tpl_dir./product-sort.tpl}
	{include file=$tpl_dir./product-list.tpl products=$products}
	{include file=$tpl_dir./pagination.tpl}
{else}
	<p class="warning">{l s='No new products.'}</p>
{/if}
