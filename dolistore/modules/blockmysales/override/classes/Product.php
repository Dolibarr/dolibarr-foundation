<?php
class Product extends ProductCore
{
	public $module_version;
	public $dolibarr_min;
	public $dolibarr_min_status;
	public $dolibarr_max;
	public $dolibarr_max_status;
	public $dolibarr_core_include;

	public function __construct($id_product = null, $full = false, $id_lang = null, $id_shop = null, Context $context = null)
	{
		Product::$definition['fields']['module_version'] =  array('type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 7);
		Product::$definition['fields']['dolibarr_min'] =  array('type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 5);
		Product::$definition['fields']['dolibarr_min_status'] =  array('type' => self::TYPE_BOOL, 'validate' => 'isBool');
		Product::$definition['fields']['dolibarr_max'] =  array('type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 5);
		Product::$definition['fields']['dolibarr_max_status'] =  array('type' => self::TYPE_BOOL, 'validate' => 'isBool');
		Product::$definition['fields']['dolibarr_core_include'] =  array('type' => self::TYPE_BOOL, 'validate' => 'isBool');

		parent::__construct($id_product, $full, $id_lang, $id_shop, $context);
	}
}