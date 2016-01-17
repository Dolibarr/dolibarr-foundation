								<tr>
									<td colspan="2"><hr></td>
								</tr>
								<tr>
									<td nowrap="nowrap" valign="top">{l s='Module/product name:' mod='blockmysales'}</td>
									<td>
									{foreach from=$languages key=id item=language}
										<input name="product_name_l{$language.id_lang}" type="text" size="48" maxlength="100" value="{$product.product_name[$language.id_lang]}" />
										<img src="{$language.img}" alt="{$language.iso_code}"> {$language.iso_code}<br />
									{/foreach}
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
									<td colspan="2" nowrap="nowrap" valign="top">
										{l s='Short description' mod='blockmysales'}
										(<img src="{$language.img}" alt="{$language.iso_code}"> {$language.iso_code}, {if $language.iso_code == 'en'}{l s='mandatory' mod='blockmysales'}{else}{l s='optionnal' mod='blockmysales'}{/if}):
										<input type="text" id="resumeLength_{$language.id_lang}" value="400" size="2" width="3" style="border:0; font-size:10px; color:#333333;" /> {l s='characters left' mod='blockmysales'}.
									</td>
								</tr>
								<tr>
									<td colspan="2" nowrap="nowrap">
										<textarea class="rte" id="resume_{$language.id_lang}" name="resume_{$language.id_lang}"
											onkeyup="javascript:resumeLength_{$language.id_lang}.value=parseInt(400-this.value.length); if(this.value.length>=400)this.value=this.value.substr(0,399);"
											onkeydown="javascript:resumeLength_{$language.id_lang}.value=parseInt(400-this.value.length); if(this.value.length>=400)this.value=this.value.substr(0,399);"
											onchange="javascript:resumeLength_{$language.id_lang}.value=parseInt(400-this.value.length); if(this.value.length>=400)this.value=this.value.substr(0,399);"
											style="width: 100%;" rows="5">{$product.resume[$language.id_lang]}</textarea>
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
											<label for="agreewithtermofuse" id="agreewithtermofuselabel">{l s='I\'ve read and I agree with terms and conditions of use available on page' mod='blockmysales'}
												<a href="http://www.dolistore.com/content/3-terms-and-conditions-of-use" target="_blank">http://www.dolistore.com/content/3-terms-and-conditions-of-use</a>
											</label>
										</div>
									</td>
								</tr>