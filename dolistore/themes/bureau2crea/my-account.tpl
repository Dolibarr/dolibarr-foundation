<script type="text/javascript">
<!--
	var baseDir = '{$base_dir_ssl}';
-->
</script>

{capture name=path}{l s='My account'}{/capture}
{include file=$tpl_dir./breadcrumb.tpl}

<h2>{l s='My account'}</h2>
<h4>{l s='Welcome to your account. Here you can manage your addresses and orders.'}</h4>
<ul>
	<li><a href="{$base_dir_ssl}history.php" title="{l s='Orders'}" class="back_btn">{l s='History and details of your orders'}</a></li>
	{if $returnAllowed}
		<li><a href="{$base_dir_ssl}order-follow.php" title="{l s='Merchandise returns'}" class="back_btn">{l s='Merchandise returns'}</a></li>
	{/if}
	<li><a href="{$base_dir_ssl}order-slip.php" title="{l s='Credit slips'}" class="back_btn">{l s='Credit slips'}</a></li>
	<li><a href="{$base_dir_ssl}addresses.php" title="{l s='Addresses'}" class="back_btn">{l s='Your addresses'}</a></li>
	<li><a href="{$base_dir_ssl}identity.php" title="{l s='Information'}" class="back_btn">{l s='Your personal information'}</a></li>
	{if $voucherAllowed}
		<li><a href="{$base_dir_ssl}discount.php" title="{l s='Vouchers'}" class="back_btn">{l s='Your vouchers'}</a></li>
	{/if}
	{$HOOK_CUSTOMER_ACCOUNT}
</ul>
<p><a href="{$base_dir}" title="{l s='Home'}" class="orange_btn">{l s='Home'}</a></p>