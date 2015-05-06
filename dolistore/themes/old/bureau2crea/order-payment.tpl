<script type="text/javascript">
<!--
	var baseDir = '{$base_dir_ssl}';
-->
</script>

{capture name=path}{l s='Your payment method'}{/capture}
{include file=$tpl_dir./breadcrumb.tpl}

<h2>{l s='Choose your payment method'}</h2>

{assign var='current_step' value='payment'}
{include file=$tpl_dir./order-steps.tpl}

{include file=$tpl_dir./errors.tpl}

{if $HOOK_PAYMENT}
	<h4>{l s='Please choose the payment method you want to use to pay the amount of'}&nbsp;<span class="price">{convertPrice price=$total_price}</span> {l s='(tax incl.)'}</h4><br />
	{$HOOK_PAYMENT}
{else}
	<p class="warning">{l s='No payment modules have been installed yet.'}</p>
{/if}

<p class="cart_navigation"><a href="{$base_dir_ssl}order.php?step=2" title="{l s='Previous'}" class="button">&laquo; {l s='Previous'}</a></p>
