{if $cms}
	{if $content_only}
	<div style="text-align:left; padding:10px;" class="rte">
		{$cms->content}
	</div>
	{else}
	<div class="rte">
		{$cms->content}
	</div>
	{/if}
{else}
	{l s='This page does not exist.'}
{/if}
<br />
{if !$content_only}
<p><a href="{$base_dir}" class="orange_btn">{l s='Home'}</a></p>
{/if}

