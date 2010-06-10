<!-- Breadcrumb -->
{if isset($smarty.capture.path)}{assign var='path' value=$smarty.capture.path}{/if}
<div class="breadcrumb">
	<a href="{$base_dir}" title="{l s='return to'} {l s='Home'}">{l s='Home'}</a>{if $path}<span class="navigation-pipe">{$navigationPipe|escape:htmlall:'UTF-8'}</span>{$path}{/if}
</div>
<!-- /Breadcrumb -->