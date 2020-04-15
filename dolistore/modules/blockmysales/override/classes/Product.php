<?php
class Product extends ProductCore
{
	public $module_version;
	public $dolibarr_min;
	public $dolibarr_max;
	public $dolibarr_core_include;
	public $dolibarr_disable_info;

	public function __construct($id_product = null, $full = false, $id_lang = null, $id_shop = null, Context $context = null)
	{
		Product::$definition['fields']['module_version'] =  array('type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 4);
		Product::$definition['fields']['dolibarr_min'] =  array('type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 6);
		Product::$definition['fields']['dolibarr_max'] =  array('type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 6);
		Product::$definition['fields']['dolibarr_core_include'] =  array('type' => self::TYPE_BOOL, 'validate' => 'isBool');
		Product::$definition['fields']['dolibarr_disable_info'] =  array('type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255);

		parent::__construct($id_product, $full, $id_lang, $id_shop, $context);
	}
}