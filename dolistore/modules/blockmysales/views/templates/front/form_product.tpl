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
												{l s='According to foundation status, a vat rate of %1$s will be added to this price, if price is not null. Your %2$s part is calculated onto this final amount.' sprintf=[$vatrate,$commissioncee] mod='blockmysales'}
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
								
								{capture assign=defaulten}
								Module version: <strong>1.0</strong><br>
								Publisher/Licence: <strong>{$publisher}</strong> / <strong>AGPL</strong><br>
								User interface language: <strong>English</strong><br>
								Help/Support: <strong>None / <strike>Forum www.dolibarr.org</strike> / <strike>Mail to contact@publisher.com</strike></strong><br>
								Prerequisites:<br>
								<ul>
									<li> Dolibarr min version: <strong>{$minversion}</strong> </li>
									<li> Dolibarr max version: <strong>{$maxversion}</strong> </li>
								</ul>
								<p>Install:</p>
								<ul>
									<li> Download the archive file of module (.zip file) from web site <a title="http://www.dolistore.com" rel="nofollow" href="http://www.dolistore.com/" target="_blank">DoliStore.com</a> </li>
									<li> Put the file into the root directory of Dolibarr. </li>
									<li> Uncompress the zip file, for example with command </li>
								</ul>
									<div style="text-align: left;" dir="ltr">
										<div style="font-family: monospace;">
											<pre><span>unzip </span>{if $product.file_name}$product.file_name{else}modulefile.zip{/if}</pre>
										</div>
									</div>
								<ul>
									<li> Module or skin is then available and can be activated. </li>
								</ul>
								{/capture}
								
								{capture assign=defaultfr}
								Module version: <strong>1.0</strong><br>
								Editeur/Licence: <strong>{$publisher}</strong> / <strong>AGPL</strong><br>
								Langage interface: <strong>Anglais</strong><br>
								Assistance: <strong>Aucune / <strike>Forum www.dolibarr.org</strike> / <strike>Par mail à contact@editeur.com</strike></strong><br>
								Pr&eacute;requis: <br>
								<ul>
									<li> Dolibarr min version: <strong>{$minversion}</strong> </li>
									<li> Dolibarr max version: <strong>{$maxversion}</strong> </li>
								</ul>
								Installation:<br>
								<ul>
									<li> T&eacute;l&eacute;charger le fichier archive du module (.zip) depuis le site web <a title="http://www.dolistore.com" rel="nofollow" href="http://www.dolistore.com/" target="_blank">DoliStore.com</a> </li>
									<li> Placer le fichier dans le r&eacute;pertoire racine de dolibarr. </li>
									<li> Decompressez le fichier zip, par exemple par la commande </li>
								</ul>
									<div style="text-align: left;" dir="ltr">
										<div style="font-family: monospace;">
											<pre><span>unzip </span>{if $product.file_name}$product.file_name{else}fichiermodule.zip{/if}</pre>
										</div>
									</div>
								<ul>
									<li> Le module ou thème est alors disponible et activable. </li>
								</ul>
								{/capture}
								
								{capture assign=defaultes}
								Versión del Módulo: <strong>1.0</strong><br>
								Creador/Licencia:  <strong>{$publisher}</strong> / <strong>AGPL</strong><br>
								Idioma interfaz usuario: <strong>Inglés</strong><br>
								Ayuda/Soporte: <strong>No / <strike>foro www.dolibarr.org</strike> / <strike>mail a contacto@creador.com</strike></strong><br>
								Prerrequisitos: <br>
								<ul>
									<li> Versión min Dolibarr: <strong>{$minversion}</strong></li>
									<li> Versión max Dolibarr: <strong>{$maxversion}</strong></li>
								</ul>
								Para instalar este módulo:<br>
								<ul>
									<li> Descargar el archivo del módulo (archivo .zip) desde la web <a title="http://www.dolistore.com" rel="nofollow" href="http://www.dolistore.com/" target="_blank">DoliStore.com</a> </li>
									<li> Ponga el archivo en el directorio raíz de Dolibarr.</li>
									<li> Descomprima el zip archivo, por ejamplo usando el comando</li>
								</ul>
									<div style="text-align: left;" dir="ltr">
										<div style="font-family: monospace;">
											<pre><span>unzip </span>{if $product.file_name} $product.file_name{else}fichiermodule.zip{/if}</pre>
										</div>
									</div>
								<ul>
									<li> El módulo o tema está listo para ser activado.</li>
								</ul>
								{/capture}
								
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
											{if $language.iso_code == 'fr'}{$defaultfr}
											{else if $language.iso_code == 'es'}{$defaultes}
											{else}{$defaulten}{/if}
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