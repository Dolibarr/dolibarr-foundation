{capture name=path}
	<a href="{$link->getPageLink('my-account', true)|escape:'html'}" title="{l s='Manage my account' mod='blockmysales'}" rel="nofollow">{l s='My account' mod='blockmysales'}</a>
	<span class="navigation-pipe">{$navigationPipe}</span>
	<a href="{$manageproductlink|escape:'html'}" title="{l s='Manage my products' mod='blockmysales'}" rel="nofollow">{l s='Manage my products' mod='blockmysales'}</a>
	<span class="navigation-pipe">{$navigationPipe}</span>
	<span class="navigation_page">{l s='Product card' mod='blockmysales'}</span>
{/capture}
{include file="$tpl_dir./errors.tpl"}

{if $product_id == 'all' || $product_id == 'download'}
	<h2>{l s='List of sells for all products' mod='blockmysales'}</h2>
{else}
	<h2>{l s='Product card:' mod='blockmysales'} <font color="#DF7E00">{$name}</font></h2>
{/if}
<br>
{if $customer_id}
	{if $product_id != 'download'}
		<div id="productcard_tabs">
			<ul>
				<li><a href="#productcard_tabs-1">{l s='List of sells' mod='blockmysales'}</a></li>
				{if $product_id != 'all' && $owner}
					<li><a href="#productcard_tabs-2">{l s='Update this module/plugin' mod='blockmysales'}</a></li>
					<li><a href="#productcard_tabs-3">{l s='Module/plugin pictures' mod='blockmysales'}</a></li>
				{/if}
			</ul>
			<!-- Sales list tab -->
			<div id="productcard_tabs-1">
				<table width="100%" border="0" cellspacing="2" cellpadding="0">
					<tr bgcolor="#CCCCCC">
						<td nowrap="nowrap"><b>{l s='Nb' mod='blockmysales'}</b></td>
						<td nowrap="nowrap"><b>{l s='Name' mod='blockmysales'}</b></td>
						<td nowrap="nowrap" align="center"><b>{l s='Date' mod='blockmysales'}</b></td>
						{if $product_id == 'all'}
							<td nowrap="nowrap"><b>{l s='Product' mod='blockmysales'}</b></td>
						{/if}
						<td nowrap="nowrap"><b>{l s='Amount (excl tax)' mod='blockmysales'}</b></td>
					</tr>
					{if $sales}
						{foreach from=$sales key=id item=sale}
							<tr bgcolor="{$sale.colorTab}">
								<td>{$sale.sale_number}</td>
								<td>
									<b>{$sale.lastname} {$sale.firstname}</b>
									<br>({$sale.email})
									<br>{l s='Registered on:' mod='blockmysales'} {dateFormat date=$sale.cust_date_add full=1}
								</td>
								<td align="center">{dateFormat date=$sale.date_add full=1}</td>
								{if $product_id == 'all'}
									<td>{$sale.sale_product_name}</td>
								{/if}
								<td align="right">
									{if !$sale.sale_refunded}
										{$sale.sale_amountearnedunit}&#8364; {if $sale.sale_amount_ht}({$sale.sale_amount_ht}&#8364;){/if}
									{else}
										{l s='Refunded' mod='blockmysales'}
									{/if}
								</td>
							</tr>
						{/foreach}
						<tr bgcolor="{$colorTab}">
							<td colspan="{if $product_id == 'all'}4{else}3{/if}">{l s='Total excl taxes' mod='blockmysales'}</td>
							<td align="right">{$totalamountearned}&#8364;</td>
						</tr>
					{/if}
				</table>
			</div><!-- End sales list tab -->
			{if $product_id != 'all' && $owner}
				<!-- Modify product tab -->
				<div id="productcard_tabs-2">
					{if $action == 'update' && !$cancel}
						{if $update_flag == 1}
							<div class="alert alert-danger">{l s='All English fields are required.' mod='blockmysales'}</div>
						{else if $update_flag == 3}
							<div class="alert alert-danger">{l s='You have to choose a category.' mod='blockmysales'}</div>
						{else}
							<div class="alert alert-success">{l s='Changes recorded..' mod='blockmysales'}</div>
						{/if}
					{/if}
					<div>
						<form name="fmysalesprod" method="POST" ENCTYPE="multipart/form-data" class="formsubmit"  action="{$phpself}?action=update">
							<table width="100%" border="0" style="padding-bottom: 5px;">
								<tr>
									<td colspan="2"><hr></td>
								</tr>
								<tr>
									<td nowrap="nowrap" valign="top">{l s='Ref module/product:' mod='blockmysales'}</td>
									<td>{$product.reference}</td>
								</tr>
								{include file="$ps_bms_templates_dir/form_product.tpl"}
								<tr>
									<td>
										<table width="100%" border="0" cellspacing="10" cellpadding="0">
											<tr>
												<td colspan="2" nowrap="nowrap" align="center">
													<input name="op" type="hidden" value="{$product.price}">
													<input name="id_p" type="hidden" value="{$product_id}">
													<input name="tab" type="hidden" value="modify">
													<input class="button_large_disabled" id="upd" name="upd" type="submit" value="{l s='Update this product' mod='blockmysales'}" disabled="disabled">
													&nbsp; &nbsp; &nbsp;
													<input class="button_large" id="cancel" name="cancel" type="submit" value="{l s='Cancel' mod='blockmysales'}">
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</form>
					</div>
					
				</div><!-- End modify product tab -->
				<!-- Images product tab -->
				<div id="productcard_tabs-3">
					<form name="fmysalesimgprod" method="POST" ENCTYPE="multipart/form-data" class="std">
						<table>
							<tr>
								<td>
									<table border="0" cellspacing="10" cellpadding="0">
										<tr>
											<td colspan="2"><hr></td>
										</tr>
										<tr>
											<td nowrap="nowrap" valign="top">{l s='Add a new image to this product:' mod='blockmysales'}</td>
											<td nowrap="nowrap">
												<input id="image_product" name="image_product" maxlength="10000000" type="file"><br>
												<p>{l s='Format : JPG or PNG (Maximum file size is: %s)' sprintf=$upload_max_filesize mod='blockmysales'}</p>
											</td>
										</tr>
										<tr>
											<td colspan="2" align="center">
												<a class="button_large" href="javascript:document.fmysalesimgprod.action='?action=addimage&id_p={$product_id}&tab=images'; document.fmysalesimgprod.submit();">
													{l s='Submit this new picture >> ' mod='blockmysales'}
												</a>
											</td>
										</tr>
										{if $action == 'addimage'}
										<tr>
											<td colspan="2" align="center">
											{if !$addimage_flag}
												<div class="alert alert-success">{l s='Image has been linked to module/product.' mod='blockmysales'}</div>
											{else}
												<div class="alert alert-danger">{$addimage_flag}</div>
											{/if}
											</td>
										</tr>
										{/if}
										<tr>
											<td colspan="2"><hr></td>
										</tr>
										{if $images}
											<tr>
												<td nowrap="nowrap" valign="top" colspan="2">
													{l s='Product\'s pictures:' mod='blockmysales'}
												</td>
											</tr>
											{foreach from=$images key=id item=image}
											<tr>
												<td nowrap="nowrap" valign="middle">
													<img src="{$link->getImageLink($product.link_rewrite[$lang_id], $image.id_image, 'large')}" />
												</td>
												<td nowrap="nowrap" valign="middle">
													<a class="delete_product_image btn btn-default" href="javascript:document.fmysalesimgprod.action='?action=deleteimage&id_img={$image.id_image}&id_p={$product_id}&tab=images'; document.fmysalesimgprod.submit();">
														{l s='Delete this picture' mod='blockmysales'}
													</a>
													{if $image.cover}<br>{l s='(Main image of product)' mod='blockmysales'}{/if}
												</td>
											</tr>
											{/foreach}
											<tr>
												<td colspan="2"><br><hr>
											</td>
										{/if}
									</table>
								</td>
							</tr>
						</table>
					</form>
				</div><!-- End images product tab -->
			{/if}
		</div>
	{else}
		{$csvlines}
	{/if}
{else}
	<h3>{l s='This customer id can\'t be found.' mod='blockmysales'}</h3>
{/if}

<script>
	$(function() {
		$( "#productcard_tabs" ).tabs();
		{if $owner}
			{if $tab == 'modify'}
				$( "#productcard_tabs" ).tabs("option", "active", $( "#productcard_tabs" ).find("productcard_tabs-2").index()-1 );
			{/if}
			{if $tab == 'images'}
				$( "#productcard_tabs" ).tabs("option", "active", $( "#productcard_tabs" ).find("productcard_tabs-3").index() );
			{/if}
		{/if}
	});
	$('#agreewithtermofuse').attr('checked', false);
	$('#agreewithtermofuse').change(function () {
		if ($(this).is(':checked')) {
			$('#upd').removeClass('button_large_disabled').addClass('button_large').attr('disabled', false);
		}   
		else {
			$('#upd').removeClass('button_large').addClass('button_large_disabled').attr('disabled', 'disabled');
		}
	});
</script>
{if $tinymce}{$tinymce}{/if}