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
						<td nowrap="nowrap"><b>{l s='#' mod='blockmysales'}</b></td>
						<td nowrap="nowrap"><b>{l s='ID Order' mod='blockmysales'}</b></td>
						<td nowrap="nowrap"><b>{l s='Name' mod='blockmysales'}</b></td>
						<td nowrap="nowrap" align="center"><b>{l s='Date' mod='blockmysales'}</b></td>
						{if $product_id == 'all'}
							<td nowrap="nowrap"><b>{l s='Product' mod='blockmysales'}</b></td>
						{/if}
						<td nowrap="nowrap"><b>{l s='Amount excl tax' mod='blockmysales'}<br>{l s='(in parenthesis, amount without discount)' mod='blockmysales'}</b></td>
					</tr>
					{if $sales}
						{foreach from=$sales key=id item=sale}
							<tr bgcolor="{$sale.colorTab}">
								<td>{$sale.sale_number}</td>
								<td>{$sale.id_order}</td>
								<td>
									<b>{$sale.lastname} {$sale.firstname} {if $sale.customer_country}({$sale.customer_country}){else}{l s='Unknown' mod='blockmysales'}{/if}</b>
									<br>({$sale.email})
									<br>{l s='Registered on:' mod='blockmysales'} {dateFormat date=$sale.cust_date_add full=1}
								</td>
								<td align="center">{dateFormat date=$sale.date_add full=1}</td>
								{if $product_id == 'all'}
									<td>{$sale.sale_product_name}</td>
								{/if}
								<td align="right"><!-- amount for this product/sell -->
									{if !$sale.sale_refunded}
										{$sale.sale_amountearnedunit}&#8364; {if $sale.sale_amount_ht}({$sale.sale_amount_ht}&#8364;){/if}
									{else}
										{l s='Refunded' mod='blockmysales'}
									{/if}
								</td>
							</tr>
						{/foreach}
						<tr bgcolor="{$colorTab}">
							<td colspan="{if $product_id == 'all'}5{else}4{/if}">{l s='Total excl taxes' mod='blockmysales'}</td>
							<td align="right">{$totalamountearned}&#8364;</td>
						</tr>
					{/if}
				</table>
			</div><!-- End sales list tab -->
			{if $product_id != 'all' && $owner}
				<!-- Modify product tab -->
				<div id="productcard_tabs-2">
					{if $action == 'update' && !$cancel}
						{if $update_flag < 0}
							{$update_errors}
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
									<td>{$product.reference}
									<input name="ref_p" type="hidden" value="{$product.reference}">
									</td>
								</tr>
								{include file="$ps_bms_templates_dir/form_product.tpl"}
								<!-- Last line with hidden vars -->
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
				<div id="productcard_tabs-3" class="bootstrap">
					<form name="fmysalesimgprod" method="POST" ENCTYPE="multipart/form-data" class="std">
						<table width="100%">
							<tr>
								<td>
									<table id="addImageTable">
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
									</table>
									{if $images}
										<table id="imageTable" class="table tableDnd">
											<thead>
												<tr class="nodrag nodrop">
													<td nowrap="nowrap" valign="top">
														{l s='Image' mod='blockmysales'}
													</td>
													<td nowrap="nowrap" valign="top">
														{l s='Position' mod='blockmysales'}
													</td>
													<td nowrap="nowrap" valign="top">
														{l s='Cover image' mod='blockmysales'}
													</td>
												</tr>
											</thead>
											<tbody id="imageList">
											{foreach from=$images key=id item=image}
												<tr id="{$image.id_image}" class="tablet">
													<td nowrap="nowrap" valign="middle">
														<img src="{$link->getImageLink($product.link_rewrite[$lang_id], $image.id_image, 'large')}" />
													</td>
													<td id="td_{$image.id_image}" class="pointer dragHandle positionImage">
														<div class="dragGroup icon-move">
															<div class="positions">
																{$image.position}
															</div>
														</div>
													</td>
													<td class="cover" nowrap="nowrap" valign="middle">
														<div class="bms-hand">{if $image.cover}<i class="icon-check-sign covered{if $mobile_device} icon-2x{/if}"></i>{else}<i class="icon-check-empty covered{if $mobile_device} icon-2x{/if}"></i>{/if}</div>
													</td>
													<td nowrap="nowrap" valign="middle">
														<a class="delete_product_image btn btn-default" href="javascript:document.fmysalesimgprod.action='?action=deleteimage&id_img={$image.id_image}&id_p={$product_id}&tab=images'; document.fmysalesimgprod.submit();">
															<i class="icon-trash"></i>
														</a>
													</td>
												</tr>
											{/foreach}
											</tbody>
										</table>
									{/if}
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
				if ($('#active_off').is(':checked')) {
					$('#dolibarr_disable_blockinfo').show();
				}
			{/if}
			{if $tab == 'images'}
				$( "#productcard_tabs" ).tabs("option", "active", $( "#productcard_tabs" ).find("productcard_tabs-3").index() );
			{/if}
		{/if}
	});
	{literal}
	function toggleLanguageFlags(elt)
	{
		$(elt).parents('.displayed_flag').siblings('.language_flags').toggle();
	}
	function changeLanguage(field, fieldsString, id_language_new, iso_code, hidelanguages = true, textarea = false, counter = false)
	{
	    $('div[id^='+field+'_]').hide();
	    if (textarea) {
	    	if (counter) {
	    		$('span[class^=counter_]').hide();
	    	}
	        $('div[class^=language_current_'+field+']').hide();
	    }
		var fields = fieldsString.split('¤');
		var base_dir_ssl = '{/literal}{$base_dir_ssl}{literal}'
		for (var i = 0; i < fields.length; ++i)
		{
			$('div[id^='+fields[i]+'_]').hide();
			$('#'+fields[i]+'_'+id_language_new).show();
			if (textarea) {
				if (counter) {
					$('span[class^=counter_'+id_language_new+']').show();
				}
				$('.'+'language_current_'+fields[i]+'_'+id_language_new).show();
			} else {
				$('#'+'language_current_'+fields[i]).attr('src', base_dir_ssl + 'img/l/' + id_language_new + '.jpg');
			}
		}
		if (hidelanguages) {
			$('#languages_' + field).hide();
		}
		id_language = id_language_new;
	}
	$(document).ready(function() {
		{/literal}
		var current_shop_id = {$current_shop_id|intval};
		var languages = {$languages|@json_encode nofilter};
		var productactive = {$product.active};
		var disableinfo = "{if $product.dolibarr_disable_info}{$product.dolibarr_disable_info}{else}null{/if}";
		{literal}
		var originalOrder = false;

		$("#imageTable").tableDnD({	
			dragHandle: 'dragHandle',
			onDragClass: 'myDragClass',
			onDragStart: function(table, row) {
				originalOrder = $.tableDnD.serialize();
				reOrder = ':even';
				if (table.tBodies[0].rows[1] && $('#' + table.tBodies[0].rows[1].id).hasClass('alt_row'))
					reOrder = ':odd';
				$(table).find('#' + row.id).parent('tr').addClass('myDragClass');
			},
			onDrop: function(table, row) {
				if (originalOrder != $.tableDnD.serialize()) {
					current = $(row).attr("id");
					stop = false;
					image_up = '{';
					$('#imageList').find('tr').each(function(i) {
						$("#td_" +  $(this).attr("id")).html('<div class="dragGroup icon-move"><div class="positions">'+(i + 1)+'</div></div>');
						if (!stop || (i + 1) == 2)
							image_up += '"' + $(this).attr("id") + '" : ' + (i + 1) + ',';
					});
					image_up = image_up.slice(0, -1);
					image_up += '}';
					updateImagePosition(image_up);
				}
			}
		});
		$('.covered').die().live('click', function(e) {
			e.preventDefault();
			id = $(this).parent().parent().parent().attr('id');
			$("#imageList .cover i").each( function(i) {
				$(this).removeClass('icon-check-sign').addClass('icon-check-empty');
			});
			$(this).removeClass('icon-check-empty').addClass('icon-check-sign');

			if (current_shop_id != 0)
				$('#' + current_shop_id + id).attr('check', true);
			else
				$(this).parent().parent().parent().children('td input').attr('check', true);
			doAdminAjax({
				"action":"updateCover",
				"id_image":id,
				"id_product" : {/literal}{$product_id}{literal},
				"token" : "{/literal}{$token|escape:'html':'UTF-8'}{literal}",
				"ajax" : 1 }
			);
		});
		getModuleName();
		setKeywords(true);
		$('#product_name_l1').on('keyup', function(e) {
			getModuleName();
		});
		$('#module_version').on('keyup', function(e) {
			var self = $(this);
			checkVersionFormat(e, self);
		});
		$('#dolibarr_min').on('keyup', function(e) {
			var self = $(this);
			checkVersionFormat(e, self);
			getModuleName();
			setKeywords();
		});
		$('#dolibarr_max').on('keyup', function(e) {
			var self = $(this);
			checkVersionFormat(e, self);
			getModuleName();
			setKeywords();
		});
		if (!productactive && disableinfo) {
			$('#dolibarr_disable_blockinfo').show();
		}
		$('#active_off').change(function () {
			if (this.checked) {
				$('#dolibarr_disable_blockinfo').show();
			}
		});
		$('#active_on').change(function () {
			if (this.checked) {
				$('#dolibarr_disable_blockinfo').hide();
			}
		});
		$('#upd').css('opacity', '0.5');
		$('#agreewithtermofuse, #agreetoaddwikipage').attr('checked', false);
		$('#agreewithtermofuse').change(function () {
			if ($(this).is(':checked') && $('#agreetoaddwikipage').is(':checked')) {
				$('#upd').removeClass('button_large_disabled').addClass('button_large').attr('disabled', false).css('opacity', '');
			}   
			else {
				$('#upd').removeClass('button_large').addClass('button_large_disabled').attr('disabled', 'disabled').css('opacity', '0.5');
			}
		});
		$('#agreetoaddwikipage').change(function () {
			if ($(this).is(':checked') && $('#agreewithtermofuse').is(':checked')) {
				$('#upd').removeClass('button_large_disabled').addClass('button_large').attr('disabled', false).css('opacity', '');
			}   
			else {
				$('#upd').removeClass('button_large').addClass('button_large_disabled').attr('disabled', 'disabled').css('opacity', '0.5');
			}
		});
		function checkVersionFormat(e, self) {
			self.val(self.val().replace(/[^0-9\.]/g, ''));
			if ((e.which != 46 || self.val().indexOf('.') != -1) && (e.which < 48 || e.which > 57))
			{
				e.preventDefault();
			}
		}
		function getModuleName() {
			var name = $('#product_name_l1').val();
			var dolibarr_min = $('#dolibarr_min').val();
			var dolibarr_max = $('#dolibarr_max').val();
			var version = (typeof dolibarr_min != 'undefined' && dolibarr_min ? dolibarr_min + ' - ' : '') + dolibarr_max;
			if (typeof name != 'undefined' && name && typeof version != 'undefined' && version) {
				$('#module_name_div').html(name + ' ' + version);
				$('#module_name_example').show();
			}
		}
		function setKeywords(split = false) {
			var dolibarr_min = $('#dolibarr_min').val().split(".");
			var dolibarr_max = $('#dolibarr_max').val().split(".");
			if (dolibarr_min[0] && dolibarr_max[0]) {
				var i;
				var max = ((dolibarr_max[0] - dolibarr_min[0]) + 1);
				$.each(languages, function(key, language) {
					var min = dolibarr_min[0];
					var dolibarr_tags = [];
					if (split == true) {
						var keywords = $("#keywords_" + language.id_lang + " input[name='keywords_" + language.id_lang + "']").val().split(",");
						keywords = keywords.filter(function(elem) {
							return $.isNumeric(elem) == false;
						});
						keywords.join(',');
						$("#keywords_" + language.id_lang + " input[name='keywords_" + language.id_lang + "']").val(keywords);
					}
					for (i = 0; i < max; i++) {
						dolibarr_tags.push(min.toString());
						min++;
					}
					dolibarr_tags.join(',');
					$("#keywords_" + language.id_lang + " input[name='dolibarr_keywords_" + language.id_lang + "']").val(dolibarr_tags);
				});
			}
		}
		function updateImagePosition(json) {
			doAdminAjax(
			{
				"action":"updateImagePosition",
				"json":json,
				"token" : "{/literal}{$token|escape:'html':'UTF-8'}{literal}",
				"ajax" : 1
			});
		}
	});
	{/literal}
	{if $mobile_device}$('.tablet').draggable();{/if}
</script>
{if $tinymce}{$tinymce}{/if}

