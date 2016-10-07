<!-- Block mysales module -->
<div class="block myaccount">
	<h4>{l s='My sales' mod='blockmysales'}</h4>
	<div class="block_content">
		<ul class="bullet">
{if $logged}
			<li><a href="{$base_dir_ssl}modules/blockmysales/my-sales-submit-product.php" title="">{l s='Submit a product' mod='blockmysales'}</a></li>			
			<li><a href="{$base_dir_ssl}modules/blockmysales/my-sales-manage-product.php" title="">{l s='Manage my products' mod='blockmysales'}</a></li>
{else}
			<li><a href="{$base_dir_ssl}modules/blockmysales/my-sales-log-first.php" title="">{l s='Submit a product' mod='blockmysales'}</a></li>			
			<!--<li><a href="{$base_dir_ssl}my-sales-stats.php" title="">{l s='My sales stats' mod='blockmysales'}</a></li>-->
{/if}
			{$HOOK_BLOCK_MY_SALES}
		</ul>		
	</div>
</div>
<!-- /Block mysales module -->
