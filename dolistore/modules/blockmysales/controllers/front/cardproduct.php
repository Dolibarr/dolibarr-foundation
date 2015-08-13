<?php

class blockmysalescardproductModuleFrontController extends ModuleFrontController
{
	private $mobile_device=false;

	public function __construct()
	{
		parent::__construct();
		$this->context = Context::getContext();
		include_once $this->module->getLocalPath() . 'blockmysales.php';

		if ($this->context->getMobileDevice() == true)
			$this->mobile_device=true;
	}

	public function setMedia()
	{
		parent::setMedia();

		// Adding CSS style sheet
		$this->context->controller->addCSS(__PS_BASE_URI__.'modules/blockmysales/css/global.css');

		// Adding JS files
		$this->context->controller->addJqueryUI(array('ui.widget', 'ui.tabs'), 'base');
		if ($this->mobile_device == true) $this->context->controller->addJqueryUI(array('ui.draggable'));
		$this->context->controller->addjQueryPlugin('tablednd');
		$this->context->controller->addjQueryPlugin('growl', null, false);

		// Adding TinyMce
		$this->context->controller->addJS(__PS_BASE_URI__.'modules/blockmysales/js/tinymce/tinymce.min.js');
		$this->context->controller->addJS(__PS_BASE_URI__.'modules/blockmysales/js/tinymce.inc.js');

		// Adding admin ajax function
		$this->context->controller->addJS(__PS_BASE_URI__.'modules/blockmysales/js/admin.js');

		// Adding drag and drop for tablet and smartphone
		if ($this->mobile_device == true)
			$this->context->controller->addJS(__PS_BASE_URI__.'modules/blockmysales/js/plugins/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js');
	}

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        $this->displayContent();
    }

    public function displayContent()
	{
		// Get env variables
		$id_shop = (int)Shop::getContextShopID();
		$id_lang = (int)$this->context->language->id;
		$context_customer_id = (int)$this->context->customer->id;
		$customer_id = $context_customer_id;

		$this->context->smarty->assign('manageproductlink', $this->context->link->getModuleLink('blockmysales', 'manageproduct'));
		$this->context->smarty->assign('current_shop_id', $id_shop);
		$this->context->smarty->assign('lang_id', $id_lang);
		$this->context->smarty->assign('mobile_device', $this->mobile_device);

		if (!empty($customer_id))
		{
			$product_id = Tools::getValue('id_p');
			$this->context->smarty->assign('product_id', $product_id);

			$action=false;
			if (Tools::isSubmit('action'))	$action = Tools::getValue('action');
			$this->context->smarty->assign('action', $action);

			$tab=(($action == 'update' || $action == 'uploadfile') ? 'modify' : false);
			if (Tools::isSubmit('tab'))	$tab = Tools::getValue('tab');
			$this->context->smarty->assign('tab', $tab);

			$cancel=false;
			if (Tools::isSubmit('cancel'))	$cancel = true;
			$this->context->smarty->assign('cancel', $cancel);

			if (Tools::isSubmit('id_customer'))	$customer_id = Tools::getValue('id_customer');

			$customer = BlockMySales::getCustomer($this->context->customer, $customer_id);

			if ($customer !== false)
			{
				if (!is_null($customer) && !empty($customer))
				{
					$this->context->smarty->assign('phpself', $this->context->link->getModuleLink('blockmysales', 'cardproduct'));
					$this->context->smarty->assign('ps_bms_templates_dir', _PS_MODULE_DIR_.'blockmysales/views/templates/front');

					$arraylistofproducts=array();

					// Get list of products for customer
					if ($product_id == 'all' || $product_id == 'download')
					{
						$id_product_list='';

						$query = 'SELECT p.id_product, p.reference, pl.name';
						$query.= ' FROM '._DB_PREFIX_.'product as p';
						$query.= ' LEFT JOIN '._DB_PREFIX_.'product_lang as pl ON pl.id_product = p.id_product AND pl.id_lang = '.$id_lang;
						$query.= ' WHERE reference LIKE "c'.$customer_id.'d2%"';
						$result = Db::getInstance()->ExecuteS($query);
						if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
						foreach ($result AS $subrow)
						{
							if (! empty($id_product_list)) $id_product_list.=",";
							$id_product_list.="'".$subrow['id_product']."'";
							$arraylistofproducts[$subrow['id_product']]=$subrow['name'];
						}

						if (empty($id_product_list)) $id_product_list="''";
					}
					else
					{
						// Get information of product into user language
						$query = 'SELECT name, description_short  FROM '._DB_PREFIX_.'product_lang
								WHERE id_product = '.(int)$product_id.'
								AND id_lang = '.$id_lang;
						$subresult = Db::getInstance()->ExecuteS($query);
						$name = "";
						$description_short = "";
						foreach ($subresult AS $subrow) {
							$name = $subrow['name'];
							$description_short = $subrow['description_short'];
						}

						$this->context->smarty->assign('name', $name);
					}

					// Select for sales list
					$query = "SELECT c.id_customer, c.email, c.lastname, c.firstname, c.date_add as cust_date_add, c.date_upd as cust_date_upd,
							od.id_order_detail, od.product_price, od.tax_rate, od.product_id,
							ROUND(od.product_price, 5) as amount_ht,
							ROUND(od.product_price * (100 + od.tax_rate) / 100, 2) as amount_ttc,
							od.reduction_percent, od.reduction_amount, od.product_quantity, od.product_quantity_refunded,
							o.date_add, o.valid
							FROM "._DB_PREFIX_."customer as c, "._DB_PREFIX_."order_detail as od,  "._DB_PREFIX_."orders as o
							WHERE o.id_order = od.id_order AND c.id_customer = o.id_customer";
					if ($product_id == 'all' || $product_id == 'download') $query.=" AND od.product_id IN (".$id_product_list.")";
					else $query.=" AND od.product_id = ".(int)$product_id;

					//prestalog($query);
					$subresult = Db::getInstance()->ExecuteS($query);

					$sales=array();

					if (count($subresult))
					{
						if ($product_id == 'download')
						{
							$csvlines='';
							$i=0;$totalamountearned=0;
							$usebr=false;
							if (Tools::isSubmit('usebr'))
								$usebr = true;

							if (!$usebr)
								$csvlines.= "\n";
							$csvlines.= 'Module sell nb;';
							$csvlines.= 'Customer;';
							$csvlines.= 'Customer date creation;';
							$csvlines.= 'Customer email;';
							$csvlines.= 'Date sell;';
							$csvlines.= 'Product id;';
							$csvlines.= 'Product label;';
							$csvlines.= 'Amount earned;';
							$csvlines.= 'Note';
							if (!$usebr)
								$csvlines.= "\n";
							else
								$csvlines.= "<br>";

							foreach ($subresult AS $subrow)
							{
								$i+=$subrow['product_quantity'];
								$csvlines.= ($subrow['product_quantity']>1?($i+1-$subrow['product_quantity']).'-':'').$i.";";
								$csvlines.= $subrow['lastname'].' '.$subrow['firstname'].";";
								$csvlines.= $subrow['cust_date_add'].";";
								$csvlines.= $subrow['email'].";";
								$csvlines.= $subrow['date_add'].";";
								$csvlines.= $arraylistofproducts[$subrow['product_id']].";";
								if (($subrow['product_quantity'] - $subrow['product_quantity_refunded']) > 0 && $subrow["valid"] == 1)
								{
									$amountearnedunit=(float) ($subrow['amount_ht']-$subrow['reduction_amount']+0);
									if ($subrow['reduction_percent'] > 0) $amountearnedunit=round($amountearnedunit*(100-$subrow['reduction_percent'])/100,5);
									$amountearned=$amountearnedunit*$subrow['product_quantity'];

									$totalamountearned+=$amountearned;

									$csvlines.= $amountearned.";";

									if ($subrow['reduction_amount'] > 0 || $subrow['reduction_percent'] > 0) $csvlines.= round($amountearnedunit,5).' ('.($subrow['amount_ht']+0).')';
									else $csvlines.= round($amountearnedunit,5).($subrow['product_quantity']>1?' x'.$subrow['product_quantity']:'');
								}
								else
								{
									$csvlines.= $this->module->l('Refunded', 'blockmysales').";";
								}
								if (!$usebr)
									$csvlines.= "\r\n";
								else
									$csvlines.= "<br>";
							}

							$this->context->smarty->assign('csvlines', $csvlines);
						}
						else
						{
							$i=0;$totalamountearned=0;
							$colorTabNbr=0;

							foreach ($subresult AS $subrow)
							{
								$i+=$subrow['product_quantity'];

								$sales[$i] = $subrow;

								if ($colorTabNbr%2)
									$sales[$i] = array_merge($sales[$i], array('colorTab' => "#ffffff"));
								else
									$sales[$i] = array_merge($sales[$i], array('colorTab' => "#eeeeee"));

								$sales[$i] = array_merge($sales[$i], array('sale_number' => ($subrow['product_quantity']>1?($i+1-$subrow['product_quantity']).'-':'').$i));

								if ($product_id == 'all')
									$sales[$i] = array_merge($sales[$i], array('sale_product_name' => $arraylistofproducts[$subrow['product_id']]));

								$refunded=false;
								if (($subrow['product_quantity'] - $subrow['product_quantity_refunded']) > 0 && $subrow["valid"] == 1)
								{
									$amountearnedunit=(float) ($subrow['amount_ht']-$subrow['reduction_amount']+0);
									if ($subrow['reduction_percent'] > 0) $amountearnedunit=round($amountearnedunit*(100-$subrow['reduction_percent'])/100,5);
									$amountearned=$amountearnedunit*$subrow['product_quantity'];

									$totalamountearned+=$amountearned;

									if ($subrow['reduction_amount'] > 0 || $subrow['reduction_percent'] > 0)
									{
										$sales[$i] = array_merge($sales[$i], array('sale_amountearnedunit' => round($amountearnedunit,5)));
										$sales[$i] = array_merge($sales[$i], array('sale_amount_ht' => ($subrow['amount_ht']+0)));
									}
									else
									{
										$sales[$i] = array_merge($sales[$i], array('sale_amountearnedunit' => round($amountearnedunit,5).($subrow['product_quantity']>1?' x'.$subrow['product_quantity']:'')));
										$sales[$i] = array_merge($sales[$i], array('sale_amount_ht' => false));
									}
								}
								else
									$refunded=true;

								$sales[$i] = array_merge($sales[$i], array('sale_refunded' => $refunded));

								$colorTabNbr++;
							}

							if (! empty($colorTabNbr) && $colorTabNbr % 2)
								$colorTab="#ffffff";
							else
								$colorTab="#eeeeee";

							$this->context->smarty->assign('colorTab', $colorTab);
							$this->context->smarty->assign('totalamountearned', round($totalamountearned, 2));
						}
					}

					$this->context->smarty->assign('sales', $sales);

					// Modify product
					if (!empty($product_id) && is_numeric($product_id))
					{
						$owner=false;
						$tinymce=false;
						if (BlockMySales::checkProductOwner($customer_id, $product_id))
						{
							require_once dirname(__FILE__) . '/../../config.inc.php';

							$owner=true;
							$product=array();
							$file=array('product_file_path' => null, 'upload' => 1, 'errormsg' => null);
							$languageTAB=array();
							$product_file_name=null;
							$virtual_product_file=null;
							$update_flag=false;
							$addimage_flag=false;
							$tinymce=BlockMySales::getTinyMce($this->context);

							$this->context->smarty->assign('taxes', Tax::getTaxes($id_lang));

							$languages = Language::getLanguages();

							foreach ($languages as $key => $language) {
								$languageTAB[$key]['id_lang'] = $language['id_lang'];
								$languageTAB[$key]['name'] = $language['name'];
								$languageTAB[$key]['iso_code'] = $language['iso_code'];
								$languageTAB[$key]['img'] = _THEME_LANG_DIR_.$language['id_lang'].'.jpg';
							}

							$blockmysales = new BlockMySales();

							/*
							 * Action
							 */

							if ($action == "uploadfile")
							{
								$file = $blockmysales->checkZipFile();

								if (Tools::isSubmit('product_file_name'))	$product_file_name = Tools::getValue('product_file_name');

								if (! empty($_FILES['virtual_product_file']['name']))
								{
									$product_file_name = $_FILES['virtual_product_file']['name'];
									$virtual_product_file=true;
								}
							}
							else if ($action == "update" && !$cancel)
							{
								$update_flag = $blockmysales->updateProduct($product_id, $customer, $languageTAB);
							}
							else if ($action == "addimage")
							{
								$addimage_flag = $blockmysales->addImages($product_id, $customer_id, $languageTAB);
							}
							else if ($action == "deleteimage")
							{
								if (Tools::isSubmit('id_img'))
								{
									$id_image = Tools::getValue('id_img');
									BlockMySales::deleteImages($product_id, $id_image);
								}
							}

							/*
							 * View
							 */

							//recupération des informations
							$query = 'SELECT
									`id_supplier`, `id_manufacturer`, `id_category_default`, `on_sale`, `ean13`, `ecotax`, `quantity`, `price`, `wholesale_price`,
									`reference`, `supplier_reference`, `location`, `weight`, `out_of_stock`, `quantity_discount`, `customizable`, `uploadable_files`, `text_fields`, `active`, `indexed`, `date_add`, `date_upd`
									FROM `'._DB_PREFIX_.'product`
									WHERE `id_product` = '.$product_id.' ';
							$result = Db::getInstance()->ExecuteS($query);
							if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
							foreach ($result AS $row)
							{
								$product['price'] 			= round($row['price'], 2);
								$product['wholesale_price'] = $row['wholesale_price'];
								$product['active'] 			= $row['active'];
								$product['reference'] 		= $row['reference'];
							}


							$query = 'SELECT `id_lang`, `description`, `description_short`, `link_rewrite`, `meta_description`, `meta_keywords`, `meta_title`, `name`, `available_now`, `available_later`
									FROM `'._DB_PREFIX_.'product_lang`
									WHERE `id_product` = '.$product_id.' ';
							$result = Db::getInstance()->ExecuteS($query);
							if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
							foreach ($result AS $row) {

								$product['product_name'][$row['id_lang']] 	= $row['name'];
								$product['resume'][$row['id_lang']]			= $row['description_short'];
								$product['keywords'][$row['id_lang']] 		= $row['meta_keywords'];
								$product['description'][$row['id_lang']] 	= $row['description'];
								$product['link_rewrite'][$row['id_lang']] 	= $row['link_rewrite'];

							}


							$query = 'SELECT `id_category`, `position`
									FROM `'._DB_PREFIX_.'category_product`
									WHERE `id_product` = '.$product_id.' ';
							$result = Db::getInstance()->ExecuteS($query);
							if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
							foreach ($result AS $row) {
								$product['id_category'] = $row['id_category'];
								$product['categories_checkbox'][$row['id_category']] = 1;
							}

							$query = 'SELECT `display_filename`, `filename` FROM `'._DB_PREFIX_.'product_download`
									WHERE `id_product` = '.$product_id.' ';
							$result = Db::getInstance()->ExecuteS($query);
							if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
							foreach ($result AS $row) {
								$product['file_name'] =  $row['display_filename'];
							}

							$categories = Category::getSimpleCategories($id_lang);
							if ($categories)
							{
								foreach($categories as $key => $category)
								{
									$query = 'SELECT c.id_category';
									$query.= ' FROM '._DB_PREFIX_.'category as c';
									$query.= ' WHERE c.id_category = \''.$category['id_category'].'\'';
									$query.= ' AND c.id_category <> 1 AND c.active = 1';
									$result = Db::getInstance()->ExecuteS($query);
									if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
									if (!$result)
										unset($categories[$key]);
								}
							}

							$query = 'SELECT id_image, position, cover
									FROM '._DB_PREFIX_.'image
									WHERE id_product = '.$product_id.'
									ORDER BY position';
							$images = Db::getInstance()->ExecuteS($query);
							if ($result === false) die(Tools::displayError('Invalid SQL query!: '.$query));


							$this->context->smarty->assign('customer', $customer);
							$this->context->smarty->assign('languages', $languageTAB);
							$this->context->smarty->assign('product', $product);
							$this->context->smarty->assign('images', $images);
							$this->context->smarty->assign('categories', $categories);
							$this->context->smarty->assign('file', $file);
							$this->context->smarty->assign('product_file_name', $product_file_name);
							$this->context->smarty->assign('virtual_product_file', $virtual_product_file);
							$this->context->smarty->assign('update_flag', $update_flag);
							$this->context->smarty->assign('addimage_flag', $addimage_flag);
						}

						$this->context->smarty->assign('owner', $owner);
						$this->context->smarty->assign('tinymce', $tinymce);
					}
				}
				else
				{
					// Customer with id '.$customer_id.' can\'t be found.
					$customer_id = null;
				}

				$this->context->smarty->assign('customer_id', $customer_id);
			}
			else
			{
				// Error, you need to be an admin user to view other customers/suppliers.
			}

			$this->setTemplate('card_product.tpl');
		}
		else
		{
			$url = $this->context->link->getModuleLink('blockmysales', 'manageproduct');
			$prefix = $this->context->link->getPageLink('authentication');
			header('Location: '.$prefix.'?back='.urlencode($url));
			exit;
		}
	}
}
