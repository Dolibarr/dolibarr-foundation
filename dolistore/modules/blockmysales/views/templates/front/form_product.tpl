								<!-- form_product.tpl -->
								<tr>
									<td colspan="2"><hr></td>
								</tr>
								<tr>
									<td nowrap="nowrap" valign="top">{l s='Module/product name:' mod='blockmysales'}</td>
									<td>
									{foreach from=$languages key=id item=language}
										<div id="product_name_{$language.id_lang}" style="padding-left: 10px; display: {if $language.id_lang == $defaultLanguage}block{else}none{/if};float: left;">
											<input name="product_name_l{$language.id_lang}" id="product_name_l{$language.id_lang}" type="text" size="48" maxlength="100" value="{$product.product_name[$language.id_lang]}" />
										</div>
									{/foreach}
									<div class="displayed_flag">
										<img src="{$base_dir_ssl}img/l/{$defaultLanguage}.jpg" class="pointer" id="language_current_product_name" onclick="toggleLanguageFlags(this);" alt="" />
									</div>
									<br />
									<div id="languages_product_name" class="language_flags">
										{l s='Choose language:' mod='blockmysales'}
										{foreach from=$languages key=id item=language}
											<img src="{$base_dir_ssl}img/l/{$language.id_lang}.jpg" class="pointer" alt="{$language.name}" title="{$language.name}" onclick="changeLanguage('product_name', 'product_name', {$language.id_lang}, '{$language.iso_code}', false);" />
										{/foreach}
									</div>
									</td>
								</tr>
								<tr id="module_name_example">
									<td nowrap="nowrap" valign="top">{l s='Module name example:' mod='blockmysales'}</td>
									<td>
										<div id="module_name_div"></div>
									</td>
								</tr>
								<tr>
									<td colspan="2"><hr></td>
								</tr>
								<!-- Module version -->
								<tr>
									<td nowrap="nowrap" valign="top">{l s='Module version:' mod='blockmysales'}</td>
									<td>
										<input required="required" size="9" maxlength="15" name="module_version" id="module_version" value="{if $product.module_version}{$product.module_version}{else}1.0{/if}" type="text" />
										<br>
										{l s='Add this tag in your large description code for use this value:' mod='blockmysales'}
										<br>
										<strong>{l s='<span class="module_version_desc">auto</span>' mod='blockmysales'}</strong>
									</td>
								</tr>
								<tr>
									<td colspan="2"><hr></td>
								</tr>
								<!-- Dolibarr min -->
								<tr>
									<td nowrap="nowrap" valign="top">{l s='Dolibarr min:' mod='blockmysales'}</td>
									<td>
										<input required="required" size="9" maxlength="6" name="dolibarr_min" id="dolibarr_min" value="{if $product.dolibarr_min}{$product.dolibarr_min}{else}3.1.x{/if}" type="text" />
										<br>
										{l s='Add this tag in your large description code for use this value:' mod='blockmysales'}
										<br>
										<strong>{l s='<span class="dolibarr_min_desc">auto</span>' mod='blockmysales'}</strong>
									</td>
								</tr>
								<tr>
									<td colspan="2"><hr></td>
								</tr>
								<!-- Dolibarr max -->
								<tr>
									<td nowrap="nowrap" valign="top">{l s='Dolibarr max:' mod='blockmysales'}</td>
									<td>
										<input required="required" size="9" maxlength="6" name="dolibarr_max" id="dolibarr_max" value="{if $product.dolibarr_max}{$product.dolibarr_max}{else}4.0.0{/if}" type="text" />
										<br>
										{l s='Add this tag in your large description code for use this value:' mod='blockmysales'}
										<br>
										<strong>{l s='<span class="dolibarr_max_desc">auto</span>' mod='blockmysales'}</strong>
									</td>
								</tr>
								<tr>
									<td colspan="2"><hr></td>
								</tr>
								<!-- Active status -->
								<tr>
									<td valign="top">{l s='Status:' mod='blockmysales'}</td>
									<td>
										<input name="active" id="active_on" value="1"{if $product.active == 1 || $product.active != ""} checked="checked"{/if} type="radio"{if !$customer.admin} disabled="disabled"{/if} />
										<img src="{$base_dir_ssl}img/os/2.gif" alt="Enabled" title="Enabled" style="padding: 0px 5px;"> {l s='Enabled' mod='blockmysales'}
										<br />
										<input name="active" id="active_off" value="0"{if $product.active == 0 || $product.active == ""} checked="checked"{/if} type="radio" />
										<img src="{$base_dir_ssl}img/os/6.gif" alt="Disabled" title="Disabled" style="padding: 0px 5px;"> {l s='Disabled' mod='blockmysales'}
									</td>
								</tr>
								<!-- Reason for disabling -->
								{if !$product.active}
								<tr id="dolibarr_disable_blockinfo" style="display:none">
									<td nowrap="nowrap" valign="top">{l s='Reason for disabling:' mod='blockmysales'}</td>
									<td>
										<div class="alert alert-danger">{if $product.dolibarr_disable_info}{$product.dolibarr_disable_info}{else}{l s='The module has been deactivated by the developer' mod='blockmysales'}{/if}</div>
										{if $product.dolibarr_disable_info}
										<input type="hidden" name="dolibarr_disable_info" id="dolibarr_disable_info" value="{$product.dolibarr_disable_info}" />
										{/if}
										<!--
										<input required="required" size="48" maxlength="255" name="dolibarr_disable_info" id="dolibarr_disable_info" value="{if $product.dolibarr_disable_info}{$product.dolibarr_disable_info}{/if}" type="text" disabled="disabled" />
										<br>
										{l s='Used to define the reason for deactivation of the product sheet.' mod='blockmysales'}
										-->
									</td>
								</tr>
								{/if}
								<tr>
									<td colspan="2"><hr></td>
								</tr>
								<tr>
									<td colspan="2">
										<div>
											{l s='According to Dolibarr license and terms of use of Dolistore, your module is licensed on an OpenSource license.' mod='blockmysales'}
											{l s='This means your work may be modified, reuse and redistribut by anybody, including by Dolibarr core team to include it into official version.' mod='blockmysales'}
											{l s='You can however help project to grow faster by accelerating this process by checking the following box.' mod='blockmysales'}
										<br><br>
										<input type="checkbox" value="1" id="dolibarr_core_include" name="dolibarr_core_include"{if $product.dolibarr_core_include == 1} checked="checked"{/if} />
											<label for="dolibarr_core_include" id="dolibarr_core_include_label">
												{l s='I would like to have my module included in Dolibarr core as soon as possible' mod='blockmysales'}
												{capture name='helpText'}
												{l s='Because a Dolibarr module is a derivative work of Dolibarr, a module must be licensed under a license compatible with Dolibarr gpl v3 license.' mod='blockmysales'}
												{l s='This means your source code may be included into Dolibarr official version to enhance Dolibarr itself. However, "may" does not say "when".' mod='blockmysales'}
												{l s='So if you really want this to happen as soon as possible, just check this box so core team will be informed. This will not guarantee it will be done but increase seriously probability and reduce delay time.' mod='blockmysales'}
												{l s='If your module is included in core, it means the module will then be maintained by Dolibarr developer community, and module will be disabled from Dolistore as soon as dolibarr version including it is publicaly available' mod='blockmysales'}
												{/capture}
												<img src="{$base_dir_ssl}img/admin/help.png" title="{$smarty.capture.helpText}" style="padding: 0px 5px;" data-toggle="tooltip" data-placement="bottom">
											</label>
										</div>
									</td>
								</tr>
								<tr>
									<td colspan="2"><hr></td>
								</tr>
								<!-- File -->
								<tr>
									<td nowrap="nowrap" valign="top">{l s='Distributed package (.zip file for a module or theme)' mod='blockmysales'}</td>
									<td>
										{if (((!$file.upload || $file.upload > 0) && $product_file_name) || $virtual_product_file) && !$file.errormsg}
											<div class="alert alert-success">{l s='File %s ready.' sprintf=$product_file_name mod='blockmysales'}</div>
										{else}
											{$product.file_name}
											<br /><br />
											{l s='New file: Maximum file size is: %s' sprintf=$upload_max_filesize mod='blockmysales'}
											<br />
											<input id="virtual_product_file" name="virtual_product_file" value="" class=""
												onchange="javascript:document.fmysalesprod.action='?action=uploadfile{if $product_id}&id_p={$product_id}{/if}&tab={$tab}';document.fmysalesprod.submit();"
												maxlength="10000000" type="file" />
											{if $file.errormsg}
												<div class="alert alert-danger">{$file.errormsg}</div>
											{/if}
										{/if}
										<br>
										<input type="hidden" name="product_file_name" id="product_file_name" value="{$product_file_name}" />
										<input type="hidden" name="product_file_path" id="product_file_path" value="{$file.product_file_path}" />
									</td>
								</tr>
								<tr id="nbdaysaccessible" {if !$product.price} style="display:none;"{/if}>
									<td nowrap="nowrap" valign="top">{l s='Number of days:' mod='blockmysales'}</td>
									<td>
										<input required="required" size="9" maxlength="7" name="nb_days_accessible" id="nb_days_accessible" value="{if $product.nb_days_accessible}{$product.nb_days_accessible}{else}0{/if}" type="text" />
										{l s='day (s)' mod='blockmysales'}
										<br>
										{l s='Number of days the file will be accessed by clients (enter 0 for unlimited access).' mod='blockmysales'}
									</td>
								</tr>
								<tr>
									<td colspan="2"><hr></td>
								</tr>
								<!-- Price -->
								<tr>
									<td nowrap="nowrap" valign="top">{l s='Sale price (excl tax):' mod='blockmysales'}</td>
									<td>
										<input required="required" size="9" maxlength="7" name="price" id="price" value="{$product.price}" onkeyup="javascript:this.value = this.value.replace(/,/g, '.');" type="text" />
										{l s='Euros ("0" means "free")' mod='blockmysales'}
										{foreach from=$taxes key=id item=taxe}
											{if $taxe.rate == $vatrate}
												<input type="hidden" name="id_tax" id="id_tax" value="{if $taxe.rate > 0}{$taxrulegroupid}{else}0{/if}" />
												<input type="hidden" name="rate_tax" id="rate_tax" value="{$taxe.rate}" />
												<br>
												{l s='According to foundation status, a vat rate of %1$s will be added to this price, if price is not null. Your %2$s part is calculated onto this final amount.' sprintf=[$vatratepercent,$commissioncee] mod='blockmysales'}
												{break}
											{/if}
										{/foreach}
									</td>
								</tr>
								<tr>
									<td colspan="2"><hr></td>
								</tr>
								<!-- Categories -->
								<tr>
									<td width="14%" valign="top">
										{l s='Select categories related to your product (max 3):' mod='blockmysales'}
									</td>
									<td>
										<table border="0" cellspacing="5" cellpadding="0">
										{foreach from=$categories key=id item=category}
											<tr bgcolor="{if $id%2}#FFF4EA{else}#FFDBB7{/if}">
												<td nowrap="nowrap" valign="top" align="left">
													<input name="categories_checkbox_{$category.id_category}" type="checkbox" value="1" {if isset($product.categories_checkbox[$category.id_category])}checked {/if} />
													{$category.name}
												</td>
											</tr>
										{/foreach}
										</table>
									</td>
								</tr>
								<tr>
									<td colspan="2"><hr></td>
								</tr>
								<!-- Summary -->
								<tr>
									<td colspan="2">
										<div id="languages_resume" class="language_flags">
											{l s='Choose language:' mod='blockmysales'}
											{foreach from=$languages key=id item=language}
												<img src="{$base_dir_ssl}img/l/{$language.id_lang}.jpg" class="pointer" alt="{$language.name}" title="{$language.name}" onclick="changeLanguage('resume', 'resume', {$language.id_lang}, '{$language.iso_code}', false, true, true);" />
											{/foreach}
										</div>
										<br />
										<div class="displayed_flag">
										{foreach from=$languages key=id item=language}
											<div class="language_current_resume_{$language.id_lang}" style="display: {if $language.id_lang == $defaultLanguage}block{else}none{/if};">
												{l s='Short description' mod='blockmysales'}
												(<img src="{$base_dir_ssl}img/l/{$language.id_lang}.jpg" class="pointer" id="language_current_resume" onclick="toggleLanguageFlags(this);" alt="" /> {$language.iso_code}, {if $language.iso_code == 'en'}{l s='mandatory' mod='blockmysales'}{else}{l s='optionnal' mod='blockmysales'}{/if}):
											</div>
										{/foreach}
										</div>
										{foreach from=$languages key=id item=language}
										<span class="counter_{$language.id_lang}" data-max="{$PS_PRODUCT_SHORT_DESC_LIMIT}"></span>
										<div id="resume_{$language.id_lang}" style="width: 90%; padding-left: 10px; display: {if $language.id_lang == $defaultLanguage}block{else}none{/if};float: left;">
											<textarea class="rtenockeditor" id="id_resume_{$language.id_lang}" name="resume_{$language.id_lang}" style="width: 100%;" rows="5">{$product.resume[$language.id_lang]}</textarea>
										</div>
										{/foreach}
									</td>
								</tr>
								<tr>
									<td colspan="2"><hr></td>
								</tr>
								<!-- Keywords -->
								<tr>
									<td colspan="2" nowrap="nowrap" valign="top">
										{l s='Keywords:' mod='blockmysales'}
									</td>
								</tr>
								<tr>
									<td colspan="2" nowrap="nowrap">
									{foreach from=$languages key=id item=language}
										<div id="keywords_{$language.id_lang}" style="padding-left: 10px; display: {if $language.id_lang == $defaultLanguage}block{else}none{/if};float: left;">
											<input name="keywords_{$language.id_lang}" type="text" size="48" maxlength="100" value="{$product.keywords[$language.id_lang]}" />
										</div>
									{/foreach}
									<div class="displayed_flag">
										<img src="{$base_dir_ssl}img/l/{$defaultLanguage}.jpg" class="pointer" id="language_current_keywords" onclick="toggleLanguageFlags(this);" alt="" />
									</div>
									<br />
									<div id="languages_keywords" class="language_flags">
										{l s='Choose language:' mod='blockmysales'}
										{foreach from=$languages key=id item=language}
											<img src="{$base_dir_ssl}img/l/{$language.id_lang}.jpg" class="pointer" alt="{$language.name}" title="{$language.name}" onclick="changeLanguage('keywords', 'keywords', {$language.id_lang}, '{$language.iso_code}', false);" />
										{/foreach}
									</div>
									</td>
								</tr>
								<tr>
									<td colspan="2"><hr></td>
								</tr>
								<!-- Long description -->
								<tr>
									<td colspan="2">
										<div id="languages_description" class="language_flags">
											{l s='Choose language:' mod='blockmysales'}
											{foreach from=$languages key=id item=language}
												<img src="{$base_dir_ssl}img/l/{$language.id_lang}.jpg" class="pointer" alt="{$language.name}" title="{$language.name}" onclick="changeLanguage('description', 'description', {$language.id_lang}, '{$language.iso_code}', false, true);" />
											{/foreach}
										</div>
										<br />
										<div class="displayed_flag">
										{foreach from=$languages key=id item=language}
											<div class="language_current_description_{$language.id_lang}" style="display: {if $language.id_lang == $defaultLanguage}block{else}none{/if};">
												{l s='Large description ' mod='blockmysales'}
												(<img src="{$base_dir_ssl}img/l/{$language.id_lang}.jpg" class="pointer" onclick="toggleLanguageFlags(this);" alt="" /> {$language.iso_code}, {if $language.iso_code == 'en'}{l s='mandatory' mod='blockmysales'}{else}{l s='optionnal' mod='blockmysales'}{/if}):
												<br><br>
											</div>
										{/foreach}
										</div>
										{foreach from=$languages key=id item=language}
										<div id="description_{$language.id_lang}" style="padding-left: 10px; display: {if $language.id_lang == $defaultLanguage}block{else}none{/if};float: left;">
											<textarea class="rte" cols="100" rows="10" name="description_{$language.id_lang}">
											{if $product.description[$language.id_lang]}
												{$product.description[$language.id_lang]}
											{else}
												{$default_descriptions[$language.id_lang]}
											{/if}
											</textarea>
										</div>
										{/foreach}
									</td>
								</tr>
								<tr>
									<td colspan="2">
										<div><br>
											<input type="checkbox" id="agreewithtermofuse" name="agreewithtermofuse" />
											<label for="agreewithtermofuse" id="agreewithtermofuselabel">
												{l s='I\'ve read and I agree with terms and conditions of use available on page' mod='blockmysales'}
												<a href="https://www.dolistore.com/content/3-terms-and-conditions-of-use" target="_blank">https://www.dolistore.com/content/3-terms-and-conditions-of-use</a>
											</label>
										</div>
									</td>
								</tr>
								<tr>
									<td colspan="2">
										<div>
											<input type="checkbox" id="agreetoaddwikipage" name="agreetoaddwikipage" />
											<label for="agreetoaddwikipage" id="agreetoaddwikipagelabel">
												{l s='I am aware that dolibarr foundation recommand me to add a page dedicated to my module on the wiki.dolibarr.org visible on' mod='blockmysales'}
												<a href="https://wiki.dolibarr.org/index.php/Category:Complementary_modules" target="_blank">https://wiki.dolibarr.org/index.php/Category:Complementary_modules</a>,
												{l s='This increase visibility of my module and may increase sell if it is a payed module' mod='blockmysales'}
											</label>
										</div>
									</td>
								</tr>
