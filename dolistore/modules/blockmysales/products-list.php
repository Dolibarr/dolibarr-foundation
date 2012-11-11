<?php
/*
 *	/products-list.php?p=-1 => Show all products
 *  
 */
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');

include(dirname(__FILE__).'/../../product-sort.php');


function getProducts2($id_lang, $start, $limit, $orderBy, $orderWay, $id_category = false, $only_active = false)
	{
		if ($orderBy == 'id_product' OR	$orderBy == 'price' OR	$orderBy == 'date_add')
			$orderByPrefix = 'p';
		elseif ($orderBy == 'name')
			$orderByPrefix = 'pl';
		elseif ($orderBy == 'position')
			$orderByPrefix = 'c';

		$rq = Db::getInstance()->ExecuteS('
		SELECT p.*, pl.* , t.`rate` AS tax_rate, m.`name` AS manufacturer_name, s.`name` AS supplier_name
		FROM `'._DB_PREFIX_.'product` p
		LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.`id_product` = pl.`id_product`)
		LEFT JOIN `'._DB_PREFIX_.'tax` t ON (t.`id_tax` = p.`id_tax`)
		LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON (m.`id_manufacturer` = p.`id_manufacturer`)
		LEFT JOIN `'._DB_PREFIX_.'supplier` s ON (s.`id_supplier` = p.`id_supplier`)'.
		($id_category ? 'LEFT JOIN `'._DB_PREFIX_.'category_product` c ON (c.`id_product` = p.`id_product`)' : '').'
		WHERE pl.`id_lang` = '.intval($id_lang).
		($id_category ? ' AND c.`id_category` = '.intval($id_category) : '').
		($only_active ? ' AND p.`active` = 1' : '').
		($limit > 0 ? ' LIMIT '.intval($start).','.intval($limit) : '')
		);

		return ($rq);
	}

if (empty($_GET['n'])) { $n=0; } else { $n=$_GET['n']; }
if (empty($_GET['p'])) { $p=1; } else { $p=$_GET['p']; }

$nbProducts=count(Product::getProductsProperties(intval($cookie->id_lang), getProducts2(intval($cookie->id_lang), NULL, NULL, $orderBy, $orderWay, false, true)));

if ($p == -1) { $n=(string) $nbProducts; $p="1"; }

include(dirname(__FILE__).'/../../pagination.php');
$smarty->assign(array(
    'products' => Product::getProductsProperties(intval($cookie->id_lang), getProducts2(intval($cookie->id_lang), intval(($p - 1) * $n), $n, $orderBy, $orderWay, false, true)),
    'nbProducts' => intval($nbProducts)));

$smarty->display(dirname(__FILE__).'/products-list.tpl');

include(dirname(__FILE__).'/../../footer.php');

?>
