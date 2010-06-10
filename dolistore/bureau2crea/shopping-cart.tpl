<script type="text/javascript">
<!--
	var baseDir = '{$base_dir_ssl}';
-->
</script>

{capture name=path}{l s='Your shopping cart'}{/capture}
{include file=$tpl_dir./breadcrumb.tpl}

<h2>{l s='Shopping cart summary'}</h2>

{assign var='current_step' value='summary'}
{include file=$tpl_dir./order-steps.tpl}

{include file=$tpl_dir./errors.tpl}

{if isset($empty)}
	<p class="warning">{l s='Your shopping cart is empty.'}</p>

{else}
{if isset($lastProductAdded) AND $lastProductAdded}
	{foreach from=$products item=product}
		{if $product.id_product == $lastProductAdded.id_product AND (!$product.id_product_attribute OR ($product.id_product_attribute == $lastProductAdded.id_product_attribute))}
			<table class="std cart_last_product">
				<thead>
					<tr>
						<th class="cart_product first_item">&nbsp;</th>
						<th class="cart_description item">{l s='Last added product'}</th>
						<th class="cart_total last_item">&nbsp;</th>
					</tr>
				</thead>
			</table>
			<table class="cart_last_product_content">
				<tr>
					<td class="cart_product"><a href="{$link->getProductLink($product.id_product, $product.link_rewrite, $product.category)|escape:'htmlall':'UTF-8'}"><img src="{$link->getImageLink($product.link_rewrite, $product.id_image, 'small')}" alt="{$product.name|escape:'htmlall':'UTF-8'}" /></a></td>
					<td class="cart_description">
						<h5><a href="{$link->getProductLink($product.id_product, $product.link_rewrite, $product.category)|escape:'htmlall':'UTF-8'}">{$product.name|escape:'htmlall':'UTF-8'}</a></h5>
						{if $product.attributes}<a href="{$link->getProductLink($product.id_product, $product.link_rewrite, $product.category)|escape:'htmlall':'UTF-8'}">{$product.attributes|escape:'htmlall':'UTF-8'}</a>{/if}
					</td>
				</tr>
			</table>
		{/if}
	{/foreach}
{/if}
<p>
	{l s='Your shopping cart contains'} {$productNumber} {if $productNumber > 1}{l s='products'}{else}{l s='product'}{/if}
</p>
<div id="order-detail-content" class="table_block">
	<table id="cart_summary" class="std">
		<thead>
			<tr>
				<th class="cart_product first_item">{l s='Product'}</th>
				<th class="cart_description item">{l s='Description'}</th>
				<th class="cart_ref item">{l s='Ref.'}</th>
				<th class="cart_availability item">{l s='Avail.'}</th>
				<th class="cart_unit item">{l s='Unit price'}</th>
				<th class="cart_quantity item">{l s='Qty'}</th>
				<th class="cart_total last_item">{l s='Total'}</th>
			</tr>
		</thead>
		<tfoot>
			{if $priceDisplay}
				<tr class="cart_total_price">
					<td colspan="6">{l s='Total products (tax excl.):'}</td>
					<td class="price">{convertPrice price=$total_products}</td>
				</tr>
			{/if}
			{if !$priceDisplay || $priceDisplay == 2}
				<tr class="cart_total_price">
					<td colspan="6">{l s='Total products (tax incl.):'}</td>
					<td class="price">{convertPrice price=$total_products_wt}</td>
				</tr>
			{/if}
			{if $total_discounts != 0}
				{if $priceDisplay}
					<tr class="cart_total_voucher">
						<td colspan="6">{l s='Total vouchers (tax excl.):'}</td>
						<td class="price-discount">{convertPrice price=$total_discounts_tax_exc}</td>
					</tr>
				{/if}
				{if !$priceDisplay || $priceDisplay == 2}
					<tr class="cart_total_voucher">
						<td colspan="6">{l s='Total vouchers (tax incl.):'}</td>
						<td class="price-discount">{convertPrice price=$total_discounts}</td>
					</tr>
				{/if}
			{/if}
			{if $total_wrapping > 0}
				{if $priceDisplay}
					<tr class="cart_total_voucher">
						<td colspan="6">{l s='Total gift-wrapping (tax excl.):'}</td>
						<td class="price-discount">{convertPrice price=$total_wrapping_tax_exc}</td>
					</tr>
				{/if}
				{if !$priceDisplay || $priceDisplay == 2}
					<tr class="cart_total_voucher">
						<td colspan="6">{l s='Total gift-wrapping (tax incl.):'}</td>
						<td class="price-discount">{convertPrice price=$total_wrapping}</td>
					</tr>
				{/if}
			{/if}
			{if $shippingCost > 0}
				{if $priceDisplay}
					<tr class="cart_total_delivery">
						<td colspan="6">{l s='Total shipping (tax excl.):'}</td>
						<td class="price">{convertPrice price=$shippingCostTaxExc}</td>
					</tr>
				{/if}
				{if !$priceDisplay || $priceDisplay == 2}
					<tr class="cart_total_delivery">
						<td colspan="6">{l s='Total shipping (tax incl.):'}</td>
						<td class="price">{convertPrice price=$shippingCost}</td>
					</tr>
				{/if}
			{/if}
			{if $priceDisplay}
				<tr class="cart_total_price">
					<td colspan="6">{l s='Total (tax excl.):'}</td>
					<td class="price">{convertPrice price=$total_price_without_tax}</td>
				</tr>
				<tr class="cart_total_voucher">
					<td colspan="6">{l s='Total tax:'}</td>
					<td class="price">{convertPrice price=$total_tax}</td>
				</tr>
			{/if}
			<tr class="cart_total_price">
				<td colspan="6">{l s='Total (tax incl.):'}</td>
				<td class="price">{convertPrice price=$total_price}</td>
			</tr>
			{if $free_ship > 0}
			<tr class="cart_free_shipping">
				<td colspan="6" style="white-space: normal;">{l s='Remaining amount to be added to your cart in order to obtain free shipping:'}</td>
				<td class="price">{convertPrice price=$free_ship}</td>
			</tr>
			{/if}
		</tfoot>
		<tbody>
		{foreach from=$products item=product name=productLoop}
			{assign var='productId' value=$product.id_product}
			{assign var='productAttributeId' value=$product.id_product_attribute}
			{assign var='quantityDisplayed' value=0}
			{* Display the product line *}
			{include file=$tpl_dir./shopping-cart-product-line.tpl}
			{* Then the customized datas ones*}
			{if isset($customizedDatas.$productId.$productAttributeId)}
				{foreach from=$customizedDatas.$productId.$productAttributeId key='id_customization' item='customization'}
					<tr class="alternate_item cart_item">
						<td colspan="5">
							{foreach from=$customization.datas key='type' item='datas'}
								{if $type == $CUSTOMIZE_FILE}
									<div class="customizationUploaded">
										<ul class="customizationUploaded">
											{foreach from=$datas item='picture'}<li><img src="{$pic_dir}{$picture.value}_small" alt="" class="customizationUploaded" /></li>{/foreach}
										</ul>
									</div>
								{elseif $type == $CUSTOMIZE_TEXTFIELD}
									<ul class="typedText">
										{foreach from=$datas item='textField' name='typedText'}<li>{l s='Text #'}{$smarty.foreach.typedText.index+1}{l s=':'} {$textField.value}</li>{/foreach}
									</ul>
								{/if}
							{/foreach}
						</td>
						<td class="cart_quantity">
							<a class="cart_quantity_delete" href="{$base_dir_ssl}cart.php?delete&amp;id_product={$product.id_product|intval}&amp;ipa={$product.id_product_attribute|intval}&amp;id_customization={$id_customization}&amp;token={$token_cart}"><img src="{$img_dir}icon/delete.gif" alt="{l s='Delete'}" title="{l s='Delete this customization'}" class="icon" /></a>
							<p>{$customization.quantity}</p>
							<a class="cart_quantity_up" href="{$base_dir_ssl}cart.php?add&amp;id_product={$product.id_product|intval}&amp;ipa={$product.id_product_attribute|intval}&amp;id_customization={$id_customization}&amp;token={$token_cart}" title="{l s='Add'}"><img src="{$img_dir}icon/quantity_up.gif" alt="{l s='Add'}" /></a><br />
							<a class="cart_quantity_down" href="{$base_dir_ssl}cart.php?add&amp;id_product={$product.id_product|intval}&amp;ipa={$product.id_product_attribute|intval}&amp;id_customization={$id_customization}&amp;op=down&amp;token={$token_cart}" title="{l s='Substract'}"><img src="{$img_dir}icon/quantity_down.gif" alt="{l s='Substract'}" /></a>
						</td>
						<td class="cart_total"></td>
					</tr>
					{assign var='quantityDisplayed' value=$quantityDisplayed+$customization.quantity}
				{/foreach}
				{* If it exists also some uncustomized products *}
				{if $product.quantity-$quantityDisplayed > 0}{include file=$tpl_dir./shopping-cart-product-line.tpl}{/if}
			{/if}
		{/foreach}
		</tbody>
	{if $discounts}
		<tbody>
		{foreach from=$discounts item=discount name=discountLoop}
			<tr class="cart_discount {if $smarty.foreach.discountLoop.last}last_item{elseif $smarty.foreach.discountLoop.first}first_item{else}item{/if}">
				<td class="cart_discount_name" colspan="2">{$discount.name}</td>
				<td class="cart_discount_description" colspan="3">{$discount.description}</td>
				<td class="cart_discount_delete"><a href="{$base_dir_ssl}order.php?deleteDiscount={$discount.id_discount}" title="{l s='Delete'}"><img src="{$img_dir}icon/delete.gif" alt="{l s='Delete'}" class="icon" /></a></td>
				<td class="cart_discount_price"><span class="price-discount">
					{if $discount.value_real > 0}
						{if !$priceDisplay || $priceDisplay == 2}{convertPrice price=$discount.value_real*-1}{if $priceDisplay == 2} {l s='+Tx'}<br />{/if}{/if}
						{if $priceDisplay}{convertPrice price=$discount.value_tax_exc*-1}{if $priceDisplay == 2} {l s='-Tx'}{/if}{/if}
					{/if}
				</span></td>
			</tr>
		{/foreach}
		</tbody>
	{/if}
	</table>
</div>

{if $voucherAllowed}
<div id="cart_voucher" class="table_block">
	{if $errors_discount}
		<ul class="error">
		{foreach from=$errors_discount key=k item=error}
			<li>{$error|escape:'htmlall':'UTF-8'}</li>
		{/foreach}
		</ul>
	{/if}
	<form action="{$base_dir_ssl}order.php" method="post" id="voucher">
		<fieldset>
			<h4>{l s='Vouchers'}</h4>
			<p>
				<label for="discount_name">{l s='Code:'}</label>
				<input type="text" id="discount_name" name="discount_name" value="{if $discount_name}{$discount_name}{/if}" />
			</p>
			<p class="submit"><input type="submit" name="submitDiscount" value="{l s='Add'}" class="button" /></p>
		</fieldset>
	</form>
</div>
{/if}
{$HOOK_SHOPPING_CART}
{if ($carrier->id AND !$virtualCart) OR $delivery->id OR $invoice->id}
<div class="order_delivery">
	{if $delivery->id}
	<ul id="delivery_address" class="address item">
		<li class="address_title">{l s='Delivery address'}</li>
		{if $delivery->company}<li class="address_company">{$delivery->company|escape:'htmlall':'UTF-8'}</li>{/if}
		<li class="address_name">{$delivery->lastname|escape:'htmlall':'UTF-8'} {$delivery->firstname|escape:'htmlall':'UTF-8'}</li>
		<li class="address_address1">{$delivery->address1|escape:'htmlall':'UTF-8'}</li>
		{if $delivery->address2}<li class="address_address2">{$delivery->address2|escape:'htmlall':'UTF-8'}</li>{/if}
		<li class="address_city">{$delivery->postcode|escape:'htmlall':'UTF-8'} {$delivery->city|escape:'htmlall':'UTF-8'}</li>
		<li class="address_country">{$delivery->country|escape:'htmlall':'UTF-8'}</li>
	</ul>
	{/if}
	{if $invoice->id}
	<ul id="invoice_address" class="address alternate_item">
		<li class="address_title">{l s='Invoice address'}</li>
		{if $invoice->company}<li class="address_company">{$invoice->company|escape:'htmlall':'UTF-8'}</li>{/if}
		<li class="address_name">{$invoice->lastname|escape:'htmlall':'UTF-8'} {$invoice->firstname|escape:'htmlall':'UTF-8'}</li>
		<li class="address_address1">{$invoice->address1|escape:'htmlall':'UTF-8'}</li>
		{if $invoice->address2}<li class="address_address2">{$invoice->address2|escape:'htmlall':'UTF-8'}</li>{/if}
		<li class="address_city">{$invoice->postcode|escape:'htmlall':'UTF-8'} {$invoice->city|escape:'htmlall':'UTF-8'}</li>
		<li class="address_country">{$invoice->country|escape:'htmlall':'UTF-8'}</li>
	</ul>
	{/if}
	{if $carrier->id AND !$virtualCart}
	<div id="order_carrier">
		<h4>{l s='Carrier:'}</h4>
		{if isset($carrierPicture)}<img src="{$img_ship_dir}{$carrier->id}.jpg" alt="{l s='Carrier'}" />{/if}
		<span>{$carrier->name|escape:'htmlall':'UTF-8'}</span>
	</div>
	{/if}
</div>
{/if}
<p class="cart_navigation">
	<a href="{$base_dir_ssl}order.php?step=1" class="exclusive" title="{l s='Next'}">{l s='Next'} &raquo;</a>
	<a href="{if $smarty.server.HTTP_REFERER && strstr($smarty.server.HTTP_REFERER, 'order.php')}{$base_dir}index.php{else}{$smarty.server.HTTP_REFERER|escape:'htmlall':'UTF-8'}{/if}" class="button_large" title="{l s='Continue shopping'}">&laquo; {l s='Continue shopping'}</a>
</p>
<p class="clear"><br /><br /></p>
<p class="cart_navigation_extra">
	{$HOOK_SHOPPING_CART_EXTRA}
</p>
{/if}
