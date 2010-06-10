<script type="text/javascript" src="{$js_dir}tools/treeManagement.js"></script>

<!-- Block categories module -->
<div id="horizontal_block">
	<h4>{l s='Categories' mod='blockhorizontal'}</h4>
	<div class="block_content">
		<ul class="tree {if $isDhtml}dhtml{/if}">
		{foreach from=$blockCategTree.children item=child name=blockCategTree}
			{if $smarty.foreach.blockCategTree.last}
						{include file=$branche_tpl_path node=$child last='true'}
			{else}
						{include file=$branche_tpl_path node=$child}
			{/if}
		{/foreach}
		</ul>
	</div>
</div>
<script type="text/javascript">
// <![CDATA[
	// we hide the tree only if JavaScript is activated
	$('div#horizontal_block ul.dhtml').hide();
// ]]>
</script>
<!-- /Block categories module -->
