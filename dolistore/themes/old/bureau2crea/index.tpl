{$HOOK_HOME}

<br><br>
{l s='MoreProductsOnCategories'}<br>
<br>
{foreach from=$categories item=category name=categories}
<!--
{php}
  $myvar = $this->get_template_vars('category'); var_dump($myvar);
{/php}
-->
<a href="{$category.id_category}-{$category.link_rewrite}">{$category.name}...</a><br>
{/foreach}

