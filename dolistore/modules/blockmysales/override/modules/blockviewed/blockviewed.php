<?php
if (!defined('_PS_VERSION_'))
	exit;

class BlockViewedOverride extends BlockViewed
{
	public function hookRightColumn($params)
	{
		$productsViewed = (isset($params['cookie']->viewed) && !empty($params['cookie']->viewed)) ? array_slice(array_reverse(explode(',', $params['cookie']->viewed)), 0, Configuration::get('PRODUCTS_VIEWED_NBR')) : array();

		if (count($productsViewed))
		{
			$defaultCover = Language::getIsoById($params['cookie']->id_lang).'-default';

			$productIds = implode(',', array_map('intval', $productsViewed));
			$productsImages = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT MAX(image_shop.id_image) id_image, p.id_product, p.dolibarr_min, p.dolibarr_min_status, p.dolibarr_max, p.dolibarr_max_status, il.legend, product_shop.active, pl.name, pl.description_short, pl.link_rewrite, cl.link_rewrite AS category_rewrite
			FROM '._DB_PREFIX_.'product p
			'.Shop::addSqlAssociation('product', 'p').'
			LEFT JOIN '._DB_PREFIX_.'product_lang pl ON (pl.id_product = p.id_product'.Shop::addSqlRestrictionOnLang('pl').')
			LEFT JOIN '._DB_PREFIX_.'image i ON (i.id_product = p.id_product)'.
			Shop::addSqlAssociation('image', 'i', false, 'image_shop.cover=1').'
			LEFT JOIN '._DB_PREFIX_.'image_lang il ON (il.id_image = image_shop.id_image AND il.id_lang = '.(int)($params['cookie']->id_lang).')
			LEFT JOIN '._DB_PREFIX_.'category_lang cl ON (cl.id_category = product_shop.id_category_default'.Shop::addSqlRestrictionOnLang('cl').')
			WHERE p.id_product IN ('.$productIds.')
			AND pl.id_lang = '.(int)($params['cookie']->id_lang).'
			AND cl.id_lang = '.(int)($params['cookie']->id_lang).'
			GROUP BY product_shop.id_product'
			);

			$productsImagesArray = array();
			foreach ($productsImages as $pi)
				$productsImagesArray[$pi['id_product']] = $pi;

			$productsViewedObj = array();
			foreach ($productsViewed as $productViewed)
			{
				$obj = (object)'Product';
				if (!isset($productsImagesArray[$productViewed]) || (!$obj->active = $productsImagesArray[$productViewed]['active']))
					continue;
				else
				{
					$obj->id = (int)($productsImagesArray[$productViewed]['id_product']);
					$obj->id_image = (int)$productsImagesArray[$productViewed]['id_image'];
					$obj->cover = (int)($productsImagesArray[$productViewed]['id_product']).'-'.(int)($productsImagesArray[$productViewed]['id_image']);
					$obj->legend = $productsImagesArray[$productViewed]['legend'];
					$obj->name = $productsImagesArray[$productViewed]['name'];
					$obj->description_short = $productsImagesArray[$productViewed]['description_short'];
					$obj->link_rewrite = $productsImagesArray[$productViewed]['link_rewrite'];
					$obj->category_rewrite = $productsImagesArray[$productViewed]['category_rewrite'];
					// $obj is not a real product so it cannot be used as argument for getProductLink()
					$obj->product_link = $this->context->link->getProductLink($obj->id, $obj->link_rewrite, $obj->category_rewrite);
					$obj->dolibarr_min = $productsImagesArray[$productViewed]['dolibarr_min'];
					$obj->dolibarr_min_status = $productsImagesArray[$productViewed]['dolibarr_min_status'];
					$obj->dolibarr_max = $productsImagesArray[$productViewed]['dolibarr_max'];
					$obj->dolibarr_max_status = $productsImagesArray[$productViewed]['dolibarr_max_status'];

					if (!isset($obj->cover) || !$productsImagesArray[$productViewed]['id_image'])
					{
						$obj->cover = $defaultCover;
						$obj->legend = '';
					}
					$productsViewedObj[] = $obj;
				}
			}

			if (!count($productsViewedObj))
				return;

			$this->smarty->assign(array(
					'productsViewedObj' => $productsViewedObj,
					'mediumSize' => Image::getSize('medium')));

			return $this->display(__FILE__, 'blockviewed.tpl');
		}
		return;
	}
}
