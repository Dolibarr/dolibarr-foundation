<?php

/**
 * Class TagCore
 */
class Tag extends TagCore
{

    /**
     * Get main tags
     *
     * @param int $idLang Language ID
     * @param int $nb     number
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     */
    public static function getMainTags($idLang, $nb = 10)
    {
        $context = Context::getContext();

        $datas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
                        SELECT DISTINCT t.name as name
                        FROM `'._DB_PREFIX_.'tag` t
                        WHERE t.`name` REGEXP \'^[0-9]{1}[0-9]{0,1}\.[0-9]{1}$\'
                        ORDER BY name DESC
                        LIMIT '.(int) $nb);

        $ret=array();
        if (!empty($datas)) {
        	$i=0;
        	foreach($datas as $data)
        	{
        		$ret[$i]['name'] = $data['name'];
        		$ret[$i]['times'] = '1';
        		$i++;
        	}
        	rsort($ret);
        }

        // original
        /*$data = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
				         SELECT t.name, counter AS times
				         FROM `'._DB_PREFIX_.'tag_count` pt
				         LEFT JOIN `'._DB_PREFIX_.'tag` t ON (t.id_tag = pt.id_tag)
				         WHERE pt.id_group = 0 AND pt.`id_lang` = '.(int)$idLang.' AND pt.`id_shop` = '.(int)$context->shop->id.'
				         ORDER BY times DESC
				         LIMIT '.(int)$nb);*/

        return $data;
    }

}
