{*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com> iNodbox <info@inodbox.com>
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<form action="{$smarty.server.REQUEST_URI|escape:'htmlall'}" method="post" class="form">
	<fieldset><legend><img src="{$moduleDir|escape:'htmlall'}/img/logo_petit.png" alt="" />{l s='Dolibarr Webservices' mod='blockmysales'}</legend>
		<label>{l s='Webservices URL' mod='blockmysales'} : </label>
		<div class="margin-form">
			<input type="text" size="45" name="webservices_url" value="{if isset($webservices_url)}{$webservices_url|escape:'htmlall':'UTF-8'}{/if}" />
            <p>{l s='URL of your Dolibarr webservices.' mod='blockmysales'}</p>
        </div>
        <label>{l s='Webservices login' mod='blockmysales'} : </label>
        <div class="margin-form">
			<input type="text" size="45" name="webservices_login" value="{if isset($webservices_login)}{$webservices_login|escape:'htmlall':'UTF-8'}{/if}" />
            <p>{l s='Login of your Dolibarr webservices.' mod='blockmysales'}</p>
        </div>
        <label>{l s='Webservices password' mod='blockmysales'} : </label>
        <div class="margin-form">
			<input type="text" size="45" name="webservices_password" value="{if isset($webservices_password)}{$webservices_password|escape:'htmlall':'UTF-8'}{/if}" />
            <p>{l s='Password of your Dolibarr webservices.' mod='blockmysales'}</p>
        </div>
        <label>{l s='Webservices secure key' mod='blockmysales'} : </label>
        <div class="margin-form">
			<input type="text" size="45" name="webservices_securekey" value="{if isset($webservices_securekey)}{$webservices_securekey|escape:'htmlall':'UTF-8'}{/if}" />
            <p>{l s='Secure key of your Dolibarr webservices.' mod='blockmysales'}</p>
        </div>
	</fieldset>
	<div class="clear">&nbsp;</div>
	<fieldset><legend><img src="{$moduleDir|escape:'htmlall'}/img/logo_petit.png" alt="" />{l s='Parameters' mod='blockmysales'}</legend>
		<label>{l s='EEC zone' mod='blockmysales'} : </label>
        <div class="margin-form">
            <select name="ceezoneid">
				{foreach from=$zones item=zone}
					{if $zone.id_zone == $ceezoneid}
						<option value="{$zone.id_zone|escape:'htmlall':'UTF-8'}" selected>{$zone.id_zone|escape:'htmlall':'UTF-8'} - {$zone.name|escape:'htmlall':'UTF-8'}</option>
					{else}
						<option value="{$zone.id_zone|escape:'htmlall':'UTF-8'}">{$zone.id_zone|escape:'htmlall':'UTF-8'} - {$zone.name|escape:'htmlall':'UTF-8'}</option>
					{/if}
				{/foreach}
			</select>
			<p>{l s='Select the EEC zone' mod='blockmysales'}</p>
        </div>
		<label>{l s='VAT rate' mod='blockmysales'} : </label>
        <div class="margin-form">
			<input type="text" size="5" name="vatrate" value="{if isset($vatrate)}{$vatrate|escape:'htmlall':'UTF-8'}{/if}" />%
            <p>{l s='VAT rate of your country' mod='blockmysales'}</p>
        </div>
		<label>{l s='VAT number' mod='blockmysales'} : </label>
        <div class="margin-form">
			<input type="text" size="20" name="vatnumber" value="{if isset($vatnumber)}{$vatnumber|escape:'htmlall':'UTF-8'}{/if}" />
            <p>{l s='VAT number of your company' mod='blockmysales'}</p>
        </div>
		<label>{l s='Commission rate EEC' mod='blockmysales'} : </label>
        <div class="margin-form">
			<input type="text" size="5" name="commissioncee" value="{if isset($commissioncee)}{$commissioncee|escape:'htmlall':'UTF-8'}{/if}" />%
            <p>{l s='Commission rate for the EEC members' mod='blockmysales'}</p>
        </div>
        <label>{l s='Minimum amount EEC' mod='blockmysales'} : </label>
        <div class="margin-form">
			<input type="text" size="5" name="minamountcee" value="{if isset($minamountcee)}{$minamountcee|escape:'htmlall':'UTF-8'}{/if}" />&#8364;
            <p>{l s='Minimum amount to be to request payment for the EEC members' mod='blockmysales'}</p>
        </div>
        <label>{l s='Commission rate outside EEC' mod='blockmysales'} : </label>
        <div class="margin-form">
			<input type="text" size="5" name="commissionnotcee" value="{if isset($commissionnotcee)}{$commissionnotcee|escape:'htmlall':'UTF-8'}{/if}" />%
            <p>{l s='Commission rate for the no EEC members' mod='blockmysales'}</p>
        </div>
        <label>{l s='Minimum amount outside EEC' mod='blockmysales'} : </label>
        <div class="margin-form">
			<input type="text" size="5" name="minamountnotcee" value="{if isset($minamountnotcee)}{$minamountnotcee|escape:'htmlall':'UTF-8'}{/if}" />&#8364;
            <p>{l s='Minimum amount to be to request payment for the no EEC members' mod='blockmysales'}</p>
        </div>
		<label>{l s='Tax rules group' mod='blockmysales'} : </label>
        <div class="margin-form">
            <select name="taxrulegroupid">
				{foreach from=$taxrulesgroups item=taxrulesgroup}
					{if $taxrulesgroup.id_tax_rules_group == $taxrulegroupid}
						<option value="{$taxrulesgroup.id_tax_rules_group|escape:'htmlall':'UTF-8'}" selected>{$taxrulesgroup.id_tax_rules_group|escape:'htmlall':'UTF-8'} - {$taxrulesgroup.name|escape:'htmlall':'UTF-8'}</option>
					{else}
						<option value="{$taxrulesgroup.id_tax_rules_group|escape:'htmlall':'UTF-8'}">{$taxrulesgroup.id_tax_rules_group|escape:'htmlall':'UTF-8'} - {$taxrulesgroup.name|escape:'htmlall':'UTF-8'}</option>
					{/if}
				{/foreach}
			</select>
			<p>{l s='Select the tax rules group' mod='blockmysales'}</p>
        </div>
        <label>{l s='Delay before payment (in months)' mod='blockmysales'} : </label>
        <div class="margin-form">
			<input type="text" size="5" name="mindelaymonth" value="{if isset($mindelaymonth)}{$mindelaymonth|escape:'htmlall':'UTF-8'}{/if}" />
            <p>{l s='Minimum period before which payment can be claimed' mod='blockmysales'}</p>
        </div>
	</fieldset>
	<fieldset><legend><img src="{$moduleDir|escape:'htmlall'}/img/logo_petit.png" alt="" />{l s='Product card' mod='blockmysales'}</legend>
		<label>{l s='Number of days' mod='blockmysales'} : </label>
        <div class="margin-form">
			<input type="text" size="5" name="nbdaysaccessible" value="{if isset($nbdaysaccessible)}{$nbdaysaccessible|escape:'htmlall':'UTF-8'}{/if}" />
            <p>{l s='Default of number of days the file will be accessed by clients' mod='blockmysales'}</p>
        </div>
		<label>{l s='Default long description' mod='blockmysales'} : </label>
		<div class="margin-form">
			{foreach from=$languages item=language}
			<div id="html_rte_{$language.id_lang}" style="padding-left: 10px; display: {if $language.id_lang == $defaultLanguage}block{else}none{/if};float: left;">
				<textarea style="min-width: 400px; min-height: 300px;" class="rte autoload_rte" id="descriptions_{$language.id_lang}" name="descriptions_{$language.id_lang}">{if $descriptions[$language.id_lang]}{$descriptions[$language.id_lang]}{/if}</textarea>
			</div>
			{/foreach}
		</div>
		<span class="clear">&nbsp;</span>
		{$displayFlags}
		<div class="clear">&nbsp;</div>
		<div class="margin-form">
            <input type="submit" value="{l s='Save' mod='blockmysales'}" name="submitSave" class="button" style="margin:10px 0px 0px 25px;" />
        </div>
	</fieldset>
</form>