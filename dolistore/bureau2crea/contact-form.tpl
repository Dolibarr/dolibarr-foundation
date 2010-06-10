{capture name=path}{l s='Contact'}{/capture}
{include file=$tpl_dir./breadcrumb.tpl}

<h2>{l s='Contact us'}</h2>

{if isset($confirmation)}
	<p>{l s='Your message has been successfully sent to our team.'}</p>
	<ul class="footer_links">
		<li><a href="{$base_dir}"><img class="icon" alt="" src="{$img_dir}icon/home.gif"/></a><a href="{$base_dir}">{l s='Home'}</a></li>
	</ul>
{else}
	<p class="bold">{l s='For questions about an order or for information about our products'}.</p>
	{include file=$tpl_dir./errors.tpl}
	<form action="{$request_uri|escape:'htmlall':'UTF-8'}" method="post" class="std">
		<fieldset>
			<h3>{l s='Send a message'}</h3>
			<p class="select">
				<label for="id_contact">{l s='Subject'}</label>
				<select id="id_contact" name="id_contact" onchange="showElemFromSelect('id_contact', 'desc_contact')">
					<option value="0">{l s='-- Choose --'}</option>
				{foreach from=$contacts item=contact}
					<option value="{$contact.id_contact|intval}" {if isset($smarty.post.id_contact) && $smarty.post.id_contact == $contact.id_contact}selected="selected"{/if}>{$contact.name|escape:'htmlall':'UTF-8'}</option>
				{/foreach}
				</select>
			</p>
			<p id="desc_contact0" class="desc_contact">&nbsp;</p>
		{foreach from=$contacts item=contact}
			<p id="desc_contact{$contact.id_contact|intval}" class="desc_contact" style="display:none;">
			<label>&nbsp;</label>{$contact.description|escape:'htmlall':'UTF-8'}</p>
		{/foreach}
		<p class="text">
			<label for="email">{l s='E-mail address'}</label>
			<input type="text" id="email" name="from" value="{$email}" />
		</p>
		<p class="textarea">
			<label for="message">{l s='Message'}</label>
			 <textarea id="message" name="message" rows="7" cols="35">{if isset($smarty.post.message)}{$smarty.post.message|escape:'htmlall':'UTF-8'|stripslashes}{/if}</textarea>
		</p>
		<p class="submit">
			<input type="submit" name="submitMessage" id="submitMessage" value="{l s='Send'}" class="button_large" />
		</p>
	</fieldset>
</form>
{/if}
