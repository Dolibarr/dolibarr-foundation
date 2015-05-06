<script type="text/javascript">
<!--
	var baseDir = '{$base_dir_ssl}';
	var addresses = new Array();
	{foreach from=$addresses key=k item=address}
		addresses[{$address.id_address|intval}] = new Array('{$address.company|addslashes}', '{$address.firstname|addslashes}', '{$address.lastname|addslashes}', '{$address.address1|addslashes}', '{$address.address2|addslashes}', '{$address.postcode|addslashes}', '{$address.city|addslashes}', '{$address.country|addslashes}', '{$address.state|default:''|addslashes}');
	{/foreach}
-->
</script>
<script type="text/javascript" src="{$js_dir}order-address.js"></script>

{capture name=path}{l s='Addresses'}{/capture}
{include file=$tpl_dir./breadcrumb.tpl}

<h2>{l s='Addresses'}</h2>

{assign var='current_step' value='address'}
{include file=$tpl_dir./order-steps.tpl}

{include file=$tpl_dir./errors.tpl}

<form action="{$base_dir_ssl}order.php" method="post">
	<div class="addresses">
		<p class="address_delivery select">
			<label for="id_address_delivery">{l s='Choose a delivery address:'}</label>
			<select name="id_address_delivery" id="id_address_delivery" class="address_select" onchange="updateAddressesDisplay();">
			{foreach from=$addresses key=k item=address}
				<option value="{$address.id_address|intval}" {if $address.id_address == $cart->id_address_delivery}selected="selected"{/if}>{$address.alias|escape:'htmlall':'UTF-8'}</option>
			{/foreach}
			</select>
		</p>
		<p class="checkbox">
			<input type="checkbox" name="same" id="addressesAreEquals" value="1" onclick="updateAddressesDisplay();" {if $cart->id_address_invoice == $cart->id_address_delivery || $addresses|@count == 1}checked="checked"{/if} />
			<label for="addressesAreEquals">{l s='Use the same address for billing.'}</label>
		</p>
		<p id="address_invoice_form" class="select" {if $cart->id_address_invoice == $cart->id_address_delivery}style="display: none;"{/if}>
		{if $addresses|@count > 1}
			<label for="id_address_invoice" class="strong">{l s='Choose a billing address:'}</label>
			<select name="id_address_invoice" id="id_address_invoice" class="address_select" onchange="updateAddressesDisplay();">
			{section loop=$addresses step=-1 name=address}
				<option value="{$addresses[address].id_address|intval}" {if $addresses[address].id_address == $cart->id_address_invoice && $cart->id_address_delivery != $cart->id_address_invoice}selected="selected"{/if}>{$addresses[address].alias|escape:'htmlall':'UTF-8'}</option>
			{/section}
			</select>
			{else}
				<a style="margin-left: 221px;" href="{$base_dir_ssl}address.php?back=order.php&amp;step=1&select_address=1" title="{l s='Add'}" class="button_large">{l s='Add a new address'}</a>
			{/if}
		</p>
		<div class="clear"></div>
		<ul class="address item" id="address_delivery">
			<li class="address_title">{l s='Your delivery address'}</li>
			<li class="address_company"></li>
			<li class="address_name"></li>
			<li class="address_address1"></li>
			<li class="address_address2"></li>
			<li class="address_city"></li>
			<li class="address_country"></li>
			<li class="address_update"><a href="{$base_dir_ssl}address.php?id_address={$address.id_address|intval}&amp;back=order.php&amp;step=1" title="{l s='Update'}">{l s='Update'}</a></li>
		</ul>
		<ul class="address alternate_item" id="address_invoice">
			<li class="address_title">{l s='Your billing address'}</li>
			<li class="address_company"></li>
			<li class="address_name"></li>
			<li class="address_address1"></li>
			<li class="address_address2"></li>
			<li class="address_city"></li>
			<li class="address_country"></li>
			<li class="address_update"><a href="{$base_dir_ssl}address.php?id_address={$address.id_address|intval}&amp;back=order.php&amp;step=1" title="{l s='Update'}">{l s='Update'}</a></li>
		</ul>
		<br class="clear" />
		<p class="address_add submit">
			<a href="{$base_dir_ssl}address.php?back=order.php&amp;step=1" title="{l s='Add'}" class="button_large">{l s='Add a new address'}</a>
		</p>
		<div id="ordermsg">
			<p>{l s='If you want to leave us comment about your order, please write it below.'}</p>
			<p class="textarea"><textarea cols="60" rows="3" name="message">{$oldMessage}</textarea></p>
		</div>
	</div>
	<p class="cart_navigation submit">
		<input type="hidden" class="hidden" name="step" value="2" />
		<input type="hidden" name="back" value="{$back}" />
		<a href="{$base_dir_ssl}order.php?step=0{if $back}&back={$back}{/if}" title="{l s='Previous'}" class="button">&laquo; {l s='Previous'}</a>
		<input type="submit" name="processAddress" value="{l s='Next'} &raquo;" class="exclusive" />
	</p>
</form>