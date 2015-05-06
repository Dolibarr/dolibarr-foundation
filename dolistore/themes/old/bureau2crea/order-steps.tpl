{* Assign a value to 'current_step' to display current style *}
<!-- Steps -->
<ul class="step" id="order_step">
	<li class="resume_step{if $current_step=='summary'}On{else}{if $current_step=='payment' || $current_step=='shipping' || $current_step=='address' || $current_step=='login'}Ok{else}Off{/if}{/if}">
		{if $current_step=='payment' || $current_step=='shipping' || $current_step=='address' || $current_step=='login'}
		<a href="{$base_dir_ssl}order.php">
			{l s='Summary'}
		</a>
		{else}
		{l s='Summary'}
		{/if}
	</li>
	<li class="login_step{if $current_step=='login'}On{else}{if $current_step=='payment' || $current_step=='shipping' || $current_step=='address'}Ok{else}Off{/if}{/if}">
		{if $current_step=='payment' || $current_step=='shipping' || $current_step=='address'}
		<a href="{$base_dir_ssl}order.php?step=1">
			{l s='Login'}
		</a>
		{else}
		{l s='Login'}
		{/if}
	</li>
	<li class="address_step{if $current_step=='address'}On{else}{if $current_step=='payment' || $current_step=='shipping'}Ok{else}Off{/if}{/if}">
		{if $current_step=='payment' || $current_step=='shipping'}
		<a href="{$base_dir_ssl}order.php?step=1">
			{l s='Address'}
		</a>
		{else}
		{l s='Address'}
		{/if}
	</li>
	<li class="shipping_step{if $current_step=='shipping'}On{else}{if $current_step=='payment'}Ok{else}Off{/if}{/if}">
		{if $current_step=='payment'}
		<a href="{$base_dir_ssl}order.php?step=2">
			{l s='Shipping'}
		</a>
		{else}
		{l s='Shipping'}
		{/if}
	</li>
	<li id="step_end" class="payment_step{if $current_step=='payment'}On{else}Off{/if}">
		{l s='Payment'}
	</li>
</ul>
<!-- /Steps -->
