{include file=$tpl_dir./breadcrumb.tpl} 
{include file=$tpl_dir./errors.tpl}

{if $category->id AND $category->active}
	<div id="category_block">
        <h2 class="category_title">
            {$category->name|escape:'htmlall':'UTF-8'}
            <span>{$nb_products|intval}&nbsp;{if $nb_products>1}{l s='products'}{else}{l s='product'}{/if}</span>
        </h2>
    
        {if $scenes}
            <!-- Scenes -->
            {include file=$tpl_dir./scenes.tpl scenes=$scenes}
        {else}
            <!-- Category image -->
            {if $category->id_image}
                <img src="{$link->getCatImageLink($category->link_rewrite, $category->id_image, 'category')}" alt="{$category->name|escape:'htmlall':'UTF-8'}" title="{$category->name|escape:'htmlall':'UTF-8'}" id="categoryImage" />
            {/if}
        {/if}
    
        {if $category->description}
            <div class="cat_desc">{$category->description}</div>
        {/if}
        {if $products}
                {include file=$tpl_dir./product-sort.tpl}
		{if $nb_products > 10}
	                {include file=$tpl_dir./pagination.tpl}
		{/if}
                {include file=$tpl_dir./product-list.tpl products=$products}
		{if $nb_products > 10}
	                        {include file=$tpl_dir./pagination.tpl}
		{/if}
            {elseif !isset($subcategories)}
                <p class="warning">{l s='There is no product in this category.'}</p>
            {/if}
     </div>
{elseif $category->id}
	<p class="warning">{l s='This category is currently unavailable.'}</p>
{/if}
