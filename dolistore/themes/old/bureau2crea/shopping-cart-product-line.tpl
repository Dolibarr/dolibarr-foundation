<tr class="{if $smarty.foreach.productLoop.last}last_item{elseif $smarty.foreach.productLoop.first}first_item{/if}{if isset($customizedDatas.$productId.$productAttributeId) AND $quantityDisplayed == 0}alternate_item{/if} cart_item">
	<td class="cart_product">
		<a href="{$link->getProductLink($product.id_product, $product.link_rewrite, $product.category)|escape:'htmlall':'UTF-8'}"><img src="{$link->getImageLink($product.link_rewrite, $product.id_image, 'small')}" alt="{$product.name|escape:'htmlall':'UTF-8'}" /></a>
	</td>
	<td class="cart_description">
		<h5><a href="{$link->getProductLink($product.id_product, $product.link_rewrite, $product.category)|escape:'htmlall':'UTF-8'}">{$product.name|escape:'htmlall':'UTF-8'}</a></h5>
		{if $product.attributes}<a href="{$link->getProductLink($product.id_product, $product.link_rewrite, $product.category)|escape:'htmlall':'UTF-8'}">{$product.attributes|escape:'htmlall':'UTF-8'}</a>{/if}
	</td>
	<td class="cart_ref">{if $product.reference}{$product.reference|escape:'htmlall':'UTF-8'}{else}--{/if}</td>
	<td class="cart_availability">
		{if $product.active AND ($product.allow_oosp OR $product.stock_quantity > 0)}
			<img src="{$img_dir}icon/available.gif" alt="{l s='Available'}" />
		{else}
			<img src="{$img_dir}icon/unavailable.gif" alt="{l s='Out of stock'}" />
		{/if}
	</td>
	<td class="cart_unit">
		<span class="price">
			{convertPrice price=$product.price} {l s='-Tx'}
		</span>
	</td>
	<td class="cart_quantity"{if isset($customizedDatas.$productId.$productAttributeId) AND $quantityDisplayed == 0} style="text-align: center;"{/if}>
		{if isset($customizedDatas.$productId.$productAttributeId) AND $quantityDisplayed == 0}{$product.customizationQuantityTotal}{/if}
		{if !isset($customizedDatas.$productId.$productAttributeId) OR $quantityDisplayed > 0}
			<a class="cart_quantity_delete" href="{$base_dir_ssl}cart.php?delete&amp;id_product={$product.id_product|intval}&amp;ipa={$product.id_product_attribute|intval}&amp;token={$token_cart}" title="{l s='Delete'}"><img src="{$img_dir}icon/delete.gif" alt="{l s='Delete'}" class="icon" /></a>
		<p>{if $quantityDisplayed == 0 AND isset($customizedDatas.$productId.$productAttributeId)}{$customizedDatas.$productId.$productAttributeId|@count}{else}{$product.quantity-$quantityDisplayed}{/if}</p>
			<a class="cart_quantity_up" href="{$base_dir_ssl}cart.php?add&amp;id_product={$product.id_product|intval}&amp;ipa={$product.id_product_attribute|intval}&amp;token={$token_cart}" title="{l s='Add'}"><img src="{$img_dir}icon/quantity_up.gif" alt="{l s='Add'}" /></a><br />
			<a class="cart_quantity_down" href="{$base_dir_ssl}cart.php?add&amp;id_product={$product.id_product|intval}&amp;ipa={$product.id_product_attribute|intval}&amp;op=down&amp;token={$token_cart}" title="{l s='Subtract'}"><img src="{$img_dir}icon/quantity_down.gif" alt="{l s='Subtract'}" /></a>
		{/if}
	</td>
	<td class="cart_total">
		<span class="price">
			{if $quantityDisplayed == 0 AND isset($customizedDatas.$productId.$productAttributeId)}
				{if !$priceDisplay || $priceDisplay == 2}{convertPrice price=$product.total_customization_wt}{if $priceDisplay == 2} {l s='+Tx'}{/if}{/if}{if $priceDisplay == 2}<br />{/if}
				{if $priceDisplay}{convertPrice price=$product.total_customization}{if $priceDisplay == 2} {l s='-Tx'}{/if}{/if}
			{else}
				{if !$priceDisplay || $priceDisplay == 2}{convertPrice price=$product.total_wt}{if $priceDisplay == 2} {l s='+Tx'}{/if}{/if}{if $priceDisplay == 2}<br />{/if}
				{if $priceDisplay}{convertPrice price=$product.total}{if $priceDisplay == 2} {l s='-Tx'}{/if}{/if}
			{/if}
		</span>
	</td>
</tr>
