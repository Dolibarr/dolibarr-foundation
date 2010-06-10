{capture name=path}{l s='Forgot your password'}{/capture}
{include file=$tpl_dir./breadcrumb.tpl}

<h2>{l s='Forgot your password'}</h2>

{include file=$tpl_dir./errors.tpl}

{if isset($confirmation)}
<p class="success">{l s='Your password has been successfully reset and has been sent to your e-mail address:'} {$email|escape:'htmlall':'UTF-8'}</p>
{else}
<p>{l s='Please enter your e-mail address used to register. We will e-mail you your new password.'}</p>
<form action="{$request_uri|escape:'htmlall':'UTF-8'}" method="post" class="std">
	<fieldset>
		<p class="text">
			<label for="email">{l s='Type your e-mail address:'}</label>
			<input type="text" id="email" name="email" value="{if isset($smarty.post.email)}{$smarty.post.email|escape:'htmlall'|stripslashes}{/if}" />
		</p>
		<p class="submit">
			<input type="submit" class="button" value="{l s='Retrieve'}" />
		</p>
	</fieldset>
</form>
{/if}
<p class="clear">
	<a href="{$base_dir_ssl}authentication.php" title="{l s='Back to Login'}"><img src="{$img_dir}icon/my-account.gif" alt="{l s='Back to Login'}" class="icon" /></a><a href="{$base_dir}authentication.php" title="{l s='Back to Login'}">{l s='Back to Login'}</a>
</p>