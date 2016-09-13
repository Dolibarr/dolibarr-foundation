								<tr>
									<td colspan="2"><hr></td>
								</tr>
								<tr>
									<td nowrap="nowrap" valign="top">{l s='Module/product name:' mod='blockmysales'}</td>
									<td>
									{foreach from=$languages key=id item=language}
										<input name="product_name_l{$language.id_lang}" id="product_name_l{$language.id_lang}" type="text" size="48" maxlength="100" value="{$product.product_name[$language.id_lang]}" />
										<img src="{$language.img}" alt="{$language.iso_code}"> {$language.iso_code}<br />
									{/foreach}
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
										<input required="required" size="9" maxlength="7" name="module_version" id="module_version" value="{if $product.module_version}{$product.module_version}{else}1.0{/if}" type="text" />
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
										<input required="required" size="9" maxlength="5" name="dolibarr_min" id="dolibarr_min" value="{if $product.dolibarr_min}{$product.dolibarr_min}{else}3.1.x{/if}" type="text" />
										<br>
										{l s='Add this tag in your large description code for use this value:' mod='blockmysales'}
										<br>
										<strong>{l s='<span class="dolibarr_min_desc">auto</span>' mod='blockmysales'}</strong>
										<br><br>
										<input name="dolibarr_min_status" id="dolibarr_min_status" value="1"{if $product.dolibarr_min_status == 1 && $product.dolibarr_max_status == 1} checked="checked"{/if} type="checkbox"{if $product.dolibarr_max_status != 1} disabled="disabled"{/if} />
										{l s='Add this value in the module name' mod='blockmysales'}
									</td>
								</tr>
								<tr>
									<td colspan="2"><hr></td>
								</tr>
								<!-- Dolibarr max -->
								<tr>
									<td nowrap="nowrap" valign="top">{l s='Dolibarr max:' mod='blockmysales'}</td>
									<td>
										<input required="required" size="9" maxlength="5" name="dolibarr_max" id="dolibarr_max" value="{if $product.dolibarr_max}{$product.dolibarr_max}{else}4.0.0{/if}" type="text" />
										<br>
										{l s='Add this tag in your large description code for use this value:' mod='blockmysales'}
										<br>
										<strong>{l s='<span class="dolibarr_max_desc">auto</span>' mod='blockmysales'}</strong>
										<br><br>
										<input name="dolibarr_max_status" id="dolibarr_max_status" value="1"{if $product.dolibarr_max_status == 1} checked="checked"{/if} type="checkbox" />
										{l s='Add this value in the module name' mod='blockmysales'}
									</td>
								</tr>
								<tr>
									<td colspan="2"><hr></td>
								</tr>
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
								<tr>
									<td colspan="2"><hr></td>
								</tr>
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
										<input required="required" size="9" maxlength="7" name="nb_days_accessible" id="nb_days_accessible" value="{$product.nb_days_accessible}" type="text" />
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
										{l s='Mark all checkbox(es) of categories in which product is to appear:' mod='blockmysales'}
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
								{foreach from=$languages key=id item=language}
								<tr>
									<td colspan="2" nowrap="nowrap">
										<div class="short-description">
											{l s='Short description' mod='blockmysales'}
											(<img src="{$language.img}" alt="{$language.iso_code}"> {$language.iso_code}, {if $language.iso_code == 'en'}{l s='mandatory' mod='blockmysales'}{else}{l s='optionnal' mod='blockmysales'}{/if}):
											<span class="counter" data-max="{$PS_PRODUCT_SHORT_DESC_LIMIT}"></span><br><br>
											<textarea class="rte" id="resume_{$language.id_lang}" name="resume_{$language.id_lang}"	style="width: 100%;" rows="5">{$product.resume[$language.id_lang]}</textarea>
										</div>
									</td>
								</tr>
								{/foreach}
								<tr>
									<td colspan="2"><hr></td>
								</tr>
								<!-- Keywords -->
								<tr>
									<td colspan="2" nowrap="nowrap" valign="top">{l s='Keywords:' mod='blockmysales'}</td>
								</tr>
								<tr>
									<td colspan="2" nowrap="nowrap">
									{foreach from=$languages key=id item=language}
										<input name="keywords_{$language.id_lang}" type="text" size="48" maxlength="100" value="{$product.keywords[$language.id_lang]}" />
										<img src="{$language.img}" alt="{$language.iso_code}"> {$language.iso_code}
										<br />
									{/foreach}
									</td>
								</tr>
								<tr>
									<td colspan="2"><hr></td>
								</tr>
								{foreach from=$languages key=id item=language}
								<tr>
									<td colspan="2">
										{l s='Large description:' mod='blockmysales'}
										(<img src="{$language.img}" alt="{$language.iso_code}"> {$language.iso_code}, {if $language.iso_code == 'en'}{l s='mandatory' mod='blockmysales'}{else}{l s='optionnal' mod='blockmysales'}{/if}):
									</td>
								</tr>
								<tr>
									<td colspan="2">
										<textarea class="rte" cols="100" rows="10" id="description_{$language.id_lang}" name="description_{$language.id_lang}">
										{if $product.description[$language.id_lang]}
											{$product.description[$language.id_lang]}
										{else}
											{$default_descriptions[$language.id_lang]}
										{/if}
										</textarea>
									</td>
								</tr>
								<tr>
									<td colspan="2"><br></td>
								</tr>
								{/foreach}
								<tr>
									<td colspan="2">
										<div>
											<input type="checkbox" id="agreewithtermofuse" name="agreewithtermofuse" />
											<label for="agreewithtermofuse" id="agreewithtermofuselabel">
												{l s='I\'ve read and I agree with terms and conditions of use available on page' mod='blockmysales'}
												<a href="http://www.dolistore.com/content/3-terms-and-conditions-of-use" target="_blank">http://www.dolistore.com/content/3-terms-and-conditions-of-use</a>
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
												<a href="http://wiki.dolibarr.org/index.php/Category:Complementary_modules" target="_blank">http://wiki.dolibarr.org/index.php/Category:Complementary_modules</a>,
												{l s='This increase visibility of my module and may increase sell if it is a payed module' mod='blockmysales'}
											</label>
										</div>
									</td>
								</tr>