{*
* 2007-2014 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2014 PrestaShop SA
*  @version  Release: $Revision$
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}


{if $dolibarr_support}
<li class="box-info2-product">
	<b>{l s='Support available at:' mod='blockmysales'}</b>: <span>{$dolibarr_support}</span>
</li>
{/if}

{if $nbofsells < 5}
	<li class="box-info2-product">
	<b>{l s='This module has not been sold enough or has been on sale for a too short to have statistics' mod='blockmysales'}</b>
	</li>
{else}
	{if $id_product}
	<li class="box-info2-product">
		{l s='This module has been purchased' mod='blockmysales'} <span class="price">{$nbofsells}</span> {l s='times' mod='blockmysales'}
		<br>
		<b>{l s='Negative feedback rate' mod='blockmysales'}</b>: <span class="price">{$dissatisfaction_rate * 100|floatval}%</span>
		<br>
	</li>
	{/if}
{/if}
