{capture name=path}{l s='Products'}{/capture}
{include file=$tpl_dir./breadcrumb.tpl}



{if $products}
    {include file=$tpl_dir./product-sort.tpl}
    {include file=$tpl_dir./product-list.tpl products=$products}
    {include file=$tpl_dir./pagination.tpl}
{else}
    
{l s='No products.'}

{/if}
