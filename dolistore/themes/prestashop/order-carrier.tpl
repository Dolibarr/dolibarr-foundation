<script type="text/javascript">
<!--
	var baseDir = '{$base_dir_ssl}';
-->
</script>
<script type="text/javascript" src="{$js_dir}layer.js"></script>
<script type="text/javascript" src="{$content_dir}js/conditions.js"></script>
{if !$virtual_cart && $giftAllowed && $cart->gift == 1}
<script type="text/javascript">{literal}
// <![CDATA[
    $('document').ready( function(){
        $('#gift_div').toggle('slow');
    });
//]]>
{/literal}</script>
{/if}
{include file=$tpl_dir./thickbox.tpl}

{capture name=path}{l s='Shipping'}{/capture}
{include file=$tpl_dir./breadcrumb.tpl}

<h2>{l s='Shipping'}</h2>

{assign var='current_step' value='shipping'}
{include file=$tpl_dir./order-steps.tpl}

{include file=$tpl_dir./errors.tpl}

<form id="form" action="{$base_dir_ssl}order.php" method="post" onsubmit="return acceptCGV('{l s='Please accept the terms of service before the next step.' js=1}');">

{if $conditions}
	<h3 class="condition_title">{l s='Terms of service'}</h3>
	<p class="checkbox">
		<input type="checkbox" name="cgv" id="cgv" value="1" {if $checkedTOS}checked="checked"{/if} />
		<label for="cgv">{l s='I agree with the terms of service and I adhere to them unconditionally.'}</label> <a href="{$base_dir}cms.php?id_cms=3&amp;content_only=1&amp;TB_iframe=true&amp;width=450&amp;height=500&amp;thickbox=true" class="thickbox">{l s='(read)'}</a>
	</p>
{/if}

{if $virtual_cart}
	<input id="input_virtual_carrier" class="hidden" type="hidden" name="id_carrier" value="0" />
{else}
	<h3 class="carrier_title">{l s='Choose your delivery method'}</h3>
	{if $recyclablePackAllowed}
	<p class="checkbox">
		<input type="checkbox" name="recyclable" id="recyclable" value="1" {if $recyclable == 1}checked="checked"{/if} />
		<label for="recyclable">{l s='I agree to receive my order in recycled packaging'}.</label>
	</p>
	{/if}

	{if $carriers && count($carriers)}
	<div class="table_block"><br />
		<table class="std">
			<thead>
				<tr>
					<th class="carrier_action first_item"></th>
					<th class="carrier_name item">{l s='Carrier'}</th>
					<th class="carrier_infos item">{l s='Information'}</th>
					<th class="carrier_price last_item">{l s='Price'}</th>
				</tr>
			</thead>
			<tbody>
			{foreach from=$carriers item=carrier name=myLoop}
				<tr class="{if $smarty.foreach.myLoop.first}first_item{elseif $smarty.foreach.myLoop.last}last_item{/if} {if $smarty.foreach.myLoop.index % 2}alternate_item{else}item{/if}">
					<td class="carrier_action radio">
						<input type="radio" name="id_carrier" value="{$carrier.id_carrier|intval}" id="id_carrier{$carrier.id_carrier|intval}" {if $carrier.id_carrier == $checked || ($checked == 0 && $i == 0) || ($carriers|@sizeof == 1)}checked="checked"{/if} />
					</td>
					<td class="carrier_name">
						<label for="id_carrier{$carrier.id_carrier|intval}">
							{if $carrier.img}<img src="{$carrier.img|escape:'htmlall':'UTF-8'}" alt="{$carrier.name|escape:'htmlall':'UTF-8'}" />{else}{$carrier.name|escape:'htmlall':'UTF-8'}{/if}
						</label>
					</td>
					<td class="carrier_infos">{$carrier.delay|escape:'htmlall':'UTF-8'}</td>
					<td class="carrier_price">
						{if $carrier.price}
							<span class="price">
								{if $priceDisplay == 1}{convertPrice price=$carrier.price_tax_exc}{else}{convertPrice price=$carrier.price}{/if}
							</span>
							{if $priceDisplay == 1} {l s='(tax excl.)'}{else} {l s='(tax incl.)'}{/if}
						{else}
							{l s='Free!'}
						{/if}
					</td>
				</tr>
			{/foreach}
			{$HOOK_EXTRACARRIER}
			</tbody>
		</table>
		<div style="display: none;" id="extra_carrier"></div>
	</div>
	{else}
		<p class="warning">{l s='There is no carrier available that will deliver to this address!'}</td></tr>
	{/if}

	{if $giftAllowed}
		<h3 class="gift_title">{l s='Gift'}</h3>
		<p class="checkbox">
			<input type="checkbox" name="gift" id="gift" value="1" {if $cart->gift == 1}checked="checked"{/if} onclick="$('#gift_div').toggle('slow');" />
			<label for="gift">{l s='I would like the order to be gift-wrapped.'}</label>
			<br />
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			{if $gift_wrapping_price > 0}
				({l s='Additional cost of'}
				<span class="price">
					{if $priceDisplay == 1}{convertPrice price=$total_wrapping_tax_exc}{else}{convertPrice price=$total_wrapping}{/if}
				</span>
				{if $priceDisplay == 1} {l s='(tax excl.)'}{else} {l s='(tax incl.)'}{/if})
			{/if}
		</p>
		<p id="gift_div" class="textarea">
			<label for="gift_message">{l s='If you wish, you can add a note to the gift:'}</label>
			<textarea rows="5" cols="35" id="gift_message" name="gift_message">{$cart->gift_message|escape:'htmlall':'UTF-8'}</textarea>
		</p>
	{/if}
{/if}

	<p class="cart_navigation submit">
		<input type="hidden" name="step" value="3" />
		<input type="hidden" name="back" value="{$back}" />
		<a href="{$base_dir_ssl}order.php?step=1{if $back}&back={$back}{/if}" title="{l s='Previous'}" class="button">&laquo; {l s='Previous'}</a>
		<input type="submit" name="processCarrier" value="{l s='Next'} &raquo;" class="exclusive" />
	</p>
</form>
