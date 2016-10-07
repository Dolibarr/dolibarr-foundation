<?php

if (!defined('_PS_VERSION_'))
	exit;

if (!defined('_CAN_LOAD_FILES_'))
	exit;

class BlockMySales extends Module
{
	public $create_errors = array();
	public $update_errors = array();

	private $_html = '';
	private $post_errors = array();

	private $merchant_mails;
	private $merchant_order;

	const __MA_MAIL_DELIMITOR__ = "\n";

	public function __construct()
	{
		$this->name = 'blockmysales';
		$this->author = 'DolibarrDev';
		$this->tab = 'front_office_features';
		$this->version = '2.2';

		//$this->bootstrap = true;
		parent::__construct();

		if ($this->id)
			$this->init();

		$this->displayName = $this->l('My modules/products block');
		$this->description = $this->l('Displays a block to submit/edit its own product');
	}

	private function init()
	{
		$this->merchant_mails = str_replace(',', self::__MA_MAIL_DELIMITOR__, (string)Configuration::get('MA_MERCHANT_MAILS'));
		$this->merchant_order = (int)Configuration::get('MA_MERCHANT_ORDER');
	}

	public function install()
	{
		if (!parent::install() ||
				!Configuration::updateValue('BLOCKMYSALES_VERSION', $this->version) ||
				!$this->registerHook('displayCustomerAccount') ||
				!$this->registerHook('displayMyAccountBlock') ||
				!$this->registerHook('actionValidateOrder') ||
				!$this->installSql()
		)
			return false;

		return true;
	}

	public function installSql()
	{
		$module_version = Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'product` ADD COLUMN IF NOT EXISTS `module_version` varchar(7)');
		$dolibarr_min = Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'product` ADD COLUMN IF NOT EXISTS `dolibarr_min` varchar(5)');
		$dolibarr_min_status = Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'product` ADD COLUMN IF NOT EXISTS `dolibarr_min_status` tinyint(1) DEFAULT 0');
		$dolibarr_max = Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'product` ADD COLUMN IF NOT EXISTS `dolibarr_max` varchar(5)');
		$dolibarr_max_status = Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'product` ADD COLUMN IF NOT EXISTS `dolibarr_max_status` tinyint(1) DEFAULT 0');
		$dolibarr_core_include = Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'product` ADD COLUMN IF NOT EXISTS `dolibarr_core_include` tinyint(1) DEFAULT 0');

		return ($module_version & $dolibarr_min & $dolibarr_min_status & $dolibarr_max & $dolibarr_max_status & $dolibarr_core_include);
	}

	public function uninstall()
	{
		if (!parent::uninstall() //||
				//!$this->unregisterHook('displayCustomerAccount') ||
				//!$this->unregisterHook('displayMyAccountBlock') ||
				//!$this->unregisterHook('actionValidateOrder') ||
				//!Configuration::deleteByName('BLOCKMYSALES_VERSION') ||
				//!Configuration::deleteByName('BLOCKMYSALES_FILELOG_PATH') ||
				//!Configuration::deleteByName('BLOCKMYSALES_WEBSERVICES_URL') ||
				//!Configuration::deleteByName('BLOCKMYSALES_WEBSERVICES_LOGIN') ||
				//!Configuration::deleteByName('BLOCKMYSALES_WEBSERVICES_PASSWORD') ||
				//!Configuration::deleteByName('BLOCKMYSALES_WEBSERVICES_SECUREKEY') ||
				//!Configuration::deleteByName('BLOCKMYSALES_CEEZONEID') ||
				//!Configuration::deleteByName('BLOCKMYSALES_VATRATE') ||
				//!Configuration::deleteByName('BLOCKMYSALES_VATNUMBER') ||
				//!Configuration::deleteByName('BLOCKMYSALES_COMMISSIONCEE') ||
				//!Configuration::deleteByName('BLOCKMYSALES_MINAMOUNTCEE') ||
				//!Configuration::deleteByName('BLOCKMYSALES_COMMISSIONNOTCEE') ||
				//!Configuration::deleteByName('BLOCKMYSALES_MINAMOUNTNOTCEE') ||
				//!Configuration::deleteByName('BLOCKMYSALES_TAXRULEGROUPID') ||
				//!Configuration::deleteByName('BLOCKMYSALES_MINDELAYMONTH') ||
				//!Configuration::deleteByName('BLOCKMYSALES_NBDAYSACCESSIBLE') ||
				//!Configuration::deleteByName('BLOCKMYSALES_DESCRIPTIONS')
		)
			return false;

		return true;
	}

	public function getContent()
	{
		if (!empty($_POST) && Tools::isSubmit('submitSave'))
		{
			$this->postValidation();
			if (!count($this->post_errors))
				$this->postProcess();
			else
				foreach ($this->post_errors as $err)
					$this->_html .= $this->displayError($err);
		}

		/* var to report */
		$module_dir = _MODULE_DIR_.$this->name;
		$id_shop = (int)Context::getContext()->shop->id;

		$filelog = Configuration::get('BLOCKMYSALES_FILELOG_PATH');

		$webservices_url = Configuration::get('BLOCKMYSALES_WEBSERVICES_URL');
		$webservices_login = Configuration::get('BLOCKMYSALES_WEBSERVICES_LOGIN');
		$webservices_password = Configuration::get('BLOCKMYSALES_WEBSERVICES_PASSWORD');
		$webservices_securekey = Configuration::get('BLOCKMYSALES_WEBSERVICES_SECUREKEY');

		$ceezoneid = Configuration::get('BLOCKMYSALES_CEEZONEID');
		$zones = Zone::getZones(true);
		$zones = array_merge($zones, array(array('id_zone' => 0, 'name' => $this->l('Any zone'))));
		foreach ($zones as $key => $row) {
			$id_zone[$key]  = $row['id_zone'];
		}
		array_multisort($id_zone, SORT_ASC, $zones);

		$vatrate = Configuration::get('BLOCKMYSALES_VATRATE');
		$vatnumber = Configuration::get('BLOCKMYSALES_VATNUMBER');

		$commissioncee = Configuration::get('BLOCKMYSALES_COMMISSIONCEE');
		$minamountcee = Configuration::get('BLOCKMYSALES_MINAMOUNTCEE');
		$commissionnotcee = Configuration::get('BLOCKMYSALES_COMMISSIONNOTCEE');
		$minamountnotcee = Configuration::get('BLOCKMYSALES_MINAMOUNTNOTCEE');

		$taxrulegroupid = Configuration::get('BLOCKMYSALES_TAXRULEGROUPID');
		$taxrulesgroups = TaxRulesGroup::getTaxRulesGroupsForOptions();

		$mindelaymonth = Configuration::get('BLOCKMYSALES_MINDELAYMONTH');

		/* Languages */
		$defaultLanguage = (int)(Configuration::get('PS_LANG_DEFAULT'));
		$languages = Language::getLanguages();
		$iso = Language::getIsoById($defaultLanguage);
		$isoTinyMCE = (file_exists(_PS_ROOT_DIR_.'/js/tiny_mce/langs/'.$iso.'.js') ? $iso : 'en');
		$ad = dirname($_SERVER['PHP_SELF']);

		$tiny_mce_code = '
			<script type="text/javascript" src="'.__PS_BASE_URI__.'js/tiny_mce/tiny_mce.js"></script>
			<script type="text/javascript" src="'.__PS_BASE_URI__.'js/tinymce.inc.js"></script>
			<script type="text/javascript">
			var iso = "'.$iso.'";
			var pathCSS = "'._THEME_CSS_DIR_.'";
			var ad = "'.$ad.'";
			$(document).ready(function(){
					tinySetup({
						editor_selector :"autoload_rte",
						theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull|cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,undo,redo",
						theme_advanced_buttons2 : "link,unlink,anchor,image,cleanup,code,|,forecolor,backcolor,|,hr,removeformat,visualaid,|,charmap,media,|,ltr,rtl,|,fullscreen",
						theme_advanced_buttons3 : "",
						theme_advanced_buttons4 : ""
					});
			});
			</script>
			';

		$this->_html .= $tiny_mce_code . '
		<script type="text/javascript">id_language = Number('.$defaultLanguage.');</script>
		<link rel="stylesheet" type="text/css" href="'._MODULE_DIR_.$this->name.'/css/admin.css" />';

		$this->_html .= '<h2>'.$this->displayName.' Version '.Configuration::get('BLOCKMYSALES_VERSION').'</h2>';

		$displayFlags = $this->displayFlags($languages, $defaultLanguage, 'html_rte', 'html_rte', true);

		$nbdaysaccessible = Configuration::get('BLOCKMYSALES_NBDAYSACCESSIBLE');
		$descriptions = json_decode(Configuration::get('BLOCKMYSALES_DESCRIPTIONS'), true);

		$this->context->smarty->assign(array(
				'moduleDir'				=> $module_dir,
				'filelog'				=> $filelog,
				'defaultLanguage'		=> $defaultLanguage,
				'languages'				=> $languages,
				'tiny_mce_code'			=> $tiny_mce_code,
				'displayFlags'			=> $displayFlags,
				'nbdaysaccessible'		=> $nbdaysaccessible,
				'descriptions'			=> $descriptions,
				'webservices_url'		=> $webservices_url,
				'webservices_login'		=> $webservices_login,
				'webservices_password'	=> $webservices_password,
				'webservices_securekey'	=> $webservices_securekey,
				'zones'					=> $zones,
				'ceezoneid'				=> $ceezoneid,
				'vatrate'				=> $vatrate,
				'vatnumber'				=> $vatnumber,
				'commissioncee'			=> $commissioncee,
				'minamountcee'			=> $minamountcee,
				'commissionnotcee'		=> $commissionnotcee,
				'minamountnotcee'		=> $minamountnotcee,
				'taxrulegroupid'		=> $taxrulegroupid,
				'taxrulesgroups'		=> $taxrulesgroups,
				'mindelaymonth'			=> $mindelaymonth
		));

		return $this->_html .= $this->fetchTemplate('back_office.tpl');
	}

	/**
	 * for validate data
	 */
	private function postValidation()
	{
		$languages = Language::getLanguages();

		foreach ($languages as $language)
		{
			$value = Tools::getValue('descriptions_' . $language['id_lang']);

			if ($language['iso_code'] == 'en' && empty($value))
				$this->post_errors[] = $this->l('The English language is mandatory!');

			if (!Validate::isCleanHtml($value))
				$this->post_errors[] = $this->l('Invalid HTML field, javascript is forbidden');
		}

		$filelog = Tools::getValue('filelog');
		if (!empty($filelog))
		{
			$file=@fopen($filelog,"a+");
			if (!$file)
			{
				$this->post_errors[] = $this->l('The log file can be created or is not writable');
			}
			else
			{
				@fwrite($file,'');
				@fclose($file);
			}
		}
	}

	/**
	 * for record data
	 */
	private function postProcess()
	{
		$descriptions = array();
		$languages = Language::getLanguages();

		foreach ($languages as $language)
		{
			$descriptions[$language['id_lang']] = htmlentities(stripslashes(Tools::getValue('descriptions_' . $language['id_lang'])), ENT_COMPAT, 'UTF-8');
		}

		if (Configuration::updateValue('BLOCKMYSALES_WEBSERVICES_URL', Tools::getValue('webservices_url'))
				&& Configuration::updateValue('BLOCKMYSALES_WEBSERVICES_LOGIN', Tools::getValue('webservices_login'))
				&& Configuration::updateValue('BLOCKMYSALES_WEBSERVICES_PASSWORD', Tools::getValue('webservices_password'))
				&& Configuration::updateValue('BLOCKMYSALES_WEBSERVICES_SECUREKEY', Tools::getValue('webservices_securekey'))
				&& Configuration::updateValue('BLOCKMYSALES_FILELOG_PATH', Tools::getValue('filelog'))
				&& Configuration::updateValue('BLOCKMYSALES_CEEZONEID', Tools::getValue('ceezoneid'))
				&& Configuration::updateValue('BLOCKMYSALES_VATRATE', Tools::getValue('vatrate'))
				&& Configuration::updateValue('BLOCKMYSALES_VATNUMBER', Tools::getValue('vatnumber'))
				&& Configuration::updateValue('BLOCKMYSALES_COMMISSIONCEE', Tools::getValue('commissioncee'))
				&& Configuration::updateValue('BLOCKMYSALES_MINAMOUNTCEE', Tools::getValue('minamountcee'))
				&& Configuration::updateValue('BLOCKMYSALES_COMMISSIONNOTCEE', Tools::getValue('commissionnotcee'))
				&& Configuration::updateValue('BLOCKMYSALES_MINAMOUNTNOTCEE', Tools::getValue('minamountnotcee'))
				&& Configuration::updateValue('BLOCKMYSALES_TAXRULEGROUPID', Tools::getValue('taxrulegroupid'))
				&& Configuration::updateValue('BLOCKMYSALES_MINDELAYMONTH', Tools::getValue('mindelaymonth'))
				&& Configuration::updateValue('BLOCKMYSALES_NBDAYSACCESSIBLE', Tools::getValue('nbdaysaccessible'))
				&& Configuration::updateValue('BLOCKMYSALES_DESCRIPTIONS', json_encode($descriptions))
			)
		{
			$this->_html .= $this->displayConfirmation($this->l('Configuration updated'));
		}
		else
			$this->_html .= $this->displayError($this->l('Cannot save settings'));
	}

	public function hookDisplayCustomerAccount($params)
	{
		$this->smarty->assign('show_icon', true);

		$link = $this->context->link->getModuleLink('blockmysales', 'manageproduct');
		$this->smarty->assign('link_module', $link);

		return $this->display(__FILE__, 'my-account.tpl');
	}

	public function hookMyAccountBlock($params)
	{
		return $this->hookDisplayMyAccountBlock($params);
	}

	public function hookDisplayMyAccountBlock($params)
	{
		$this->smarty->assign('show_icon', false);

		$link = $this->context->link->getModuleLink('blockmysales', 'manageproduct');
		$this->smarty->assign('link_module', $link);

		return $this->display(__FILE__, 'my-account.tpl');
	}

	public function hookActionValidateOrder($params)
	{
		if (!$this->merchant_order || empty($this->merchant_mails))
			return;

		include_once(dirname(__FILE__).'/../mailalerts/MailAlert.php');

		// Getting differents vars
		$context = Context::getContext();
		$id_lang = (int)$context->language->id;
		$id_shop = (int)$context->shop->id;
		$currency = $params['currency'];
		$order = $params['order'];
		$customer = $params['customer'];
		$configuration = Configuration::getMultiple(
				array(
						'PS_SHOP_EMAIL',
						'PS_MAIL_METHOD',
						'PS_MAIL_SERVER',
						'PS_MAIL_USER',
						'PS_MAIL_PASSWD',
						'PS_SHOP_NAME',
						'PS_MAIL_COLOR'
				), $id_lang, null, $id_shop
		);
		$delivery = new Address((int)$order->id_address_delivery);
		$invoice = new Address((int)$order->id_address_invoice);
		$order_date_text = Tools::displayDate($order->date_add);
		$carrier = new Carrier((int)$order->id_carrier);
		$message = $this->getAllMessages($order->id);

		if (!$message || empty($message))
			$message = $this->l('No message');

		$items_table = '';

		//FHE : Add array to keep product id of the order
		$idprods=array();

		$products = $params['order']->getProducts();
		$customized_datas = Product::getAllCustomizedDatas((int)$params['cart']->id);
		Product::addCustomizationPrice($products, $customized_datas);
		foreach ($products as $key => $product)
		{
			//FHE : Add array to keep product id of the order
			$idprods[]=$product['product_id'];

			$unit_price = Product::getTaxCalculationMethod($customer->id) == PS_TAX_EXC ? $product['product_price'] : $product['product_price_wt'];

			$customization_text = '';
			if (isset($customized_datas[$product['product_id']][$product['product_attribute_id']]))
			{
				foreach ($customized_datas[$product['product_id']][$product['product_attribute_id']][$order->id_address_delivery] as $customization)
				{
					if (isset($customization['datas'][Product::CUSTOMIZE_TEXTFIELD]))
						foreach ($customization['datas'][Product::CUSTOMIZE_TEXTFIELD] as $text)
							$customization_text .= $text['name'].': '.$text['value'].'<br />';

						if (isset($customization['datas'][Product::CUSTOMIZE_FILE]))
							$customization_text .= count($customization['datas'][Product::CUSTOMIZE_FILE]).' '.$this->l('image(s)').'<br />';

						$customization_text .= '---<br />';
				}
				if (method_exists('Tools', 'rtrimString'))
					$customization_text = Tools::rtrimString($customization_text, '---<br />');
				else
					$customization_text = preg_replace('/---<br \/>$/', '', $customization_text);
			}

			$items_table .=
			'<tr style="background-color:'.($key % 2 ? '#DDE2E6' : '#EBECEE').';">
					<td style="padding:0.6em 0.4em;">'.$product['product_reference'].'</td>
					<td style="padding:0.6em 0.4em;">
						<strong>'
								.$product['product_name']
								.(isset($product['attributes_small']) ? ' '.$product['attributes_small'] : '')
								.(!empty($customization_text) ? '<br />'.$customization_text : '')
								.'</strong>
					</td>
					<td style="padding:0.6em 0.4em; text-align:right;">'.Tools::displayPrice($unit_price, $currency, false).'</td>
					<td style="padding:0.6em 0.4em; text-align:center;">'.(int)$product['product_quantity'].'</td>
					<td style="padding:0.6em 0.4em; text-align:right;">'
								.Tools::displayPrice(($unit_price * $product['product_quantity']), $currency, false)
								.'</td>
				</tr>';
		}
		foreach ($params['order']->getCartRules() as $discount)
		{
			$items_table .=
			'<tr style="background-color:#EBECEE;">
						<td colspan="4" style="padding:0.6em 0.4em; text-align:right;">'.$this->l('Voucher code:').' '.$discount['name'].'</td>
					<td style="padding:0.6em 0.4em; text-align:right;">-'.Tools::displayPrice($discount['value'], $currency, false).'</td>
			</tr>';
		}
		if ($delivery->id_state)
			$delivery_state = new State((int)$delivery->id_state);
		if ($invoice->id_state)
			$invoice_state = new State((int)$invoice->id_state);

		if (Product::getTaxCalculationMethod($customer->id) == PS_TAX_EXC)
			$total_products = $order->getTotalProductsWithoutTaxes();
		else
			$total_products = $order->getTotalProductsWithTaxes();

		// Filling-in vars for email
		$template_vars = array(
				'{firstname}' => $customer->firstname,
				'{lastname}' => $customer->lastname,
				'{email}' => $customer->email,
				'{delivery_block_txt}' => MailAlert::getFormatedAddress($delivery, "\n"),
				'{invoice_block_txt}' => MailAlert::getFormatedAddress($invoice, "\n"),
				'{delivery_block_html}' => MailAlert::getFormatedAddress(
						$delivery, '<br />', array(
								'firstname' => '<span style="color:'.$configuration['PS_MAIL_COLOR'].'; font-weight:bold;">%s</span>',
								'lastname' => '<span style="color:'.$configuration['PS_MAIL_COLOR'].'; font-weight:bold;">%s</span>'
						)
				),
				'{invoice_block_html}' => MailAlert::getFormatedAddress(
						$invoice, '<br />', array(
								'firstname' => '<span style="color:'.$configuration['PS_MAIL_COLOR'].'; font-weight:bold;">%s</span>',
								'lastname' => '<span style="color:'.$configuration['PS_MAIL_COLOR'].'; font-weight:bold;">%s</span>'
						)
				),
				'{delivery_company}' => $delivery->company,
				'{delivery_firstname}' => $delivery->firstname,
				'{delivery_lastname}' => $delivery->lastname,
				'{delivery_address1}' => $delivery->address1,
				'{delivery_address2}' => $delivery->address2,
				'{delivery_city}' => $delivery->city,
				'{delivery_postal_code}' => $delivery->postcode,
				'{delivery_country}' => $delivery->country,
				'{delivery_state}' => $delivery->id_state ? $delivery_state->name : '',
				'{delivery_phone}' => $delivery->phone ? $delivery->phone : $delivery->phone_mobile,
				'{delivery_other}' => $delivery->other,
				'{invoice_company}' => $invoice->company,
				'{invoice_firstname}' => $invoice->firstname,
				'{invoice_lastname}' => $invoice->lastname,
				'{invoice_address2}' => $invoice->address2,
				'{invoice_address1}' => $invoice->address1,
				'{invoice_city}' => $invoice->city,
				'{invoice_postal_code}' => $invoice->postcode,
				'{invoice_country}' => $invoice->country,
				'{invoice_state}' => $invoice->id_state ? $invoice_state->name : '',
				'{invoice_phone}' => $invoice->phone ? $invoice->phone : $invoice->phone_mobile,
				'{invoice_other}' => $invoice->other,
				'{order_name}' => $order->reference,
				'{shop_name}' => $configuration['PS_SHOP_NAME'],
				'{date}' => $order_date_text,
				'{carrier}' => (($carrier->name == '0') ? $configuration['PS_SHOP_NAME'] : $carrier->name),
				'{payment}' => Tools::substr($order->payment, 0, 32),
				'{items}' => $items_table,
				'{total_paid}' => Tools::displayPrice($order->total_paid, $currency),
				'{total_products}' => Tools::displayPrice($total_products, $currency),
				'{total_discounts}' => Tools::displayPrice($order->total_discounts, $currency),
				'{total_shipping}' => Tools::displayPrice($order->total_shipping, $currency),
				'{total_tax_paid}' => Tools::displayPrice(
						($order->total_products_wt - $order->total_products) + ($order->total_shipping_tax_incl - $order->total_shipping_tax_excl),
						$currency,
						false
				),
				'{total_wrapping}' => Tools::displayPrice($order->total_wrapping, $currency),
				'{currency}' => $currency->sign,
				'{message}' => $message
		);

		//FHE : Find sellers to send him a mail that a module have been buy
		foreach($idprods as $idprod) {

			Logger::addLog('mailalerts: idprod= '.$idprod, 1);

			$query = 'SELECT p.reference';
			$query.= ' FROM '._DB_PREFIX_.'product as p';
			$query.= ' WHERE p.id_product="'.$idprod.'"';

			Logger::addLog('mailalerts: $query= '.$query, 1);

			$result = Db::getInstance()->executeS($query);
			if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

			if (count($result))
			{
				foreach ($result as $row)	// For each product
				{
					$ref_product = $row['reference'];
					//Find the id customer
					$d2indice = strpos($ref_product,'d2');

					Logger::addLog('mailalerts: $ref_product= '.$ref_product, 1);

					if ($d2indice!==false) {
						$id_sellers = substr($ref_product, 1, $d2indice);

						Logger::addLog('mailalerts: $id_sellers= '.$id_sellers, 1);

						//Find sellers email
						$queryemail = 'SELECT c.email';
						$queryemail.= ' FROM '._DB_PREFIX_.'customer as c';
						$queryemail.= ' WHERE c.id_customer="'.$id_sellers.'"';

						Logger::addLog('mailalerts: $queryemail= '.$queryemail, 1);

						$resultemail = Db::getInstance()->executeS($queryemail);
						if ($resultemail === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$queryemail));
						if (count($resultemail))
						{
							foreach ($resultemail as $rowemail)
							{
								Logger::addLog('mailalerts: $rowemail[email]= '.$rowemail['email'], 1);

								Mail::Send(
										$id_lang,
										'new_order',
										sprintf(Mail::l('New order : #%d - %s', $id_lang), $order->id, $order->reference),
										$template_vars,
										$rowemail['email'],
										null,
										$configuration['PS_SHOP_EMAIL'],
										$configuration['PS_SHOP_NAME'],
										null,
										null,
										dirname(__FILE__).'/mails/',
										null,
										$id_shop
								);
							}
						}
					}
				}
			}

		}
	}

	public static function getCustomer($context_customer, $request_customer_id)
	{
		$context_customer_id = (int)$context_customer->id;
		$firstname = $context_customer->firstname;
		$lastname = $context_customer->lastname;
		$admin = false;

		// Check if current user is also an employee with admin user
		$query = "SELECT id_employee, id_profile, email, active FROM "._DB_PREFIX_."employee WHERE lastname = '".addslashes($lastname)."' AND firstname = '".addslashes($firstname)."'";
		$subresult = Db::getInstance()->ExecuteS($query);
		if (empty($subresult[0]['id_employee'])) // If not an admin user
		{
			if ($context_customer_id != $request_customer_id)
				return false;
		}
		else
			$admin = true;

		// Get publisher, company and country
		$query = "SELECT c.id_customer, c.firstname, c.lastname, c.email, c.optin, c.active, c.deleted, a.company, a.city, a.id_country, co.iso_code";
		$query.= " FROM "._DB_PREFIX_."customer as c";
		$query.= " LEFT JOIN "._DB_PREFIX_."address as a ON a.id_customer = c.id_customer AND a.deleted = 0";
		$query.= " LEFT JOIN "._DB_PREFIX_."country as co ON a.id_country = co.id_country";
		if ($request_customer_id != 'all') $query.= " WHERE c.id_customer = " . $request_customer_id;
		$subresult = Db::getInstance()->ExecuteS($query);

		if (! empty($subresult[0]['active']))
		{
			$subresult[0] = array_merge($subresult[0], array('admin' => $admin));
			return $subresult[0];
		}
		else
			return null;
	}

	public static function getCustomerCountry($id_lang, $id_customer)
	{
		$country_name = false;

		// Get customer country
		$query = "SELECT a.id_country
				FROM "._DB_PREFIX_."address as a
				WHERE a.id_customer = " . $id_customer;

		$cresult = Db::getInstance()->ExecuteS($query);

		if (count($cresult))
		{
			foreach ($cresult as $country)
			{
				$country_name = Country::getNameById($id_lang, $country['id_country']);
				break; // only the first
			}
		}

		return $country_name;
	}

	/**
	 *
	 * @param unknown $customer_id
	 * @param unknown $product_id
	 * @return boolean
	 */
	public static function checkProductOwner($customer_id, $product_id)
	{
		$query = 'SELECT `id_product` FROM `'._DB_PREFIX_.'product`
				WHERE `reference` LIKE "c'.$customer_id.'d2%"
				AND `id_product` = '.$product_id;
		$result = Db::getInstance()->ExecuteS($query);

		$id_product = "";
		foreach ($result AS $row)
			$id_product = $row['id_product'];
		if ($id_product != "")
			return true;
		else
			return false;
	}

	/**
	 *
	 */
	public function checkZipFile()
	{
		$error=0;
		$return = array(
				'product_file_path' => null,
				'upload' => 1,
				'errormsg' => null
		);

		$originalfilename=$_FILES['virtual_product_file']['name'];

		self::prestalog("Upload or reupload file " . $originalfilename);

		if ($_FILES['virtual_product_file']['error'])
		{
			switch ($_FILES['virtual_product_file']['error'])
			{
				case 1: // UPLOAD_ERR_INI_SIZE
					$return['errormsg'] = $this->l('File size is higher than server limit !');
					break;
				case 2: // UPLOAD_ERR_FORM_SIZE
					$return['errormsg'] = $this->l('File size if higher than limit in HTML form !');
					break;
				case 3: // UPLOAD_ERR_PARTIAL
					$return['errormsg'] = $this->l('File transfert was aborted !');
					break;
				case 4: // UPLOAD_ERR_NO_FILE
					$return['errormsg'] = $this->l('File name was not defined or file size is null !');
					break;
			}
			$return['upload'] = -1;
			$error++;
		}

		if (! $error && ! preg_match('/(\.apk|\.odt|\.pdf|\.svg|\.zip|\.txt)$/i',$originalfilename))
		{
			$return['errormsg'] = $this->l('Package seems to not respect some rules:').'<br>';
			$return['errormsg'].= $this->l('Package file name must end with extension .odt, .pdf, .svg, .zip, .txt or .apk');
			$return['upload'] = -1;
			$error++;
		}

		if (! $error && preg_match('/(\.txt)$/i',$originalfilename) && ! preg_match('/(README)\.txt$/i',$originalfilename))
		{
			$return['errormsg'] = $this->l('Package seems to not respect some rules:').'<br>';
			$return['errormsg'].= $this->l('A .txt file must be named README.txt');
			$return['upload'] = -1;
			$error++;
		}

		if (! $error && preg_match('/(\.zip)$/i',$originalfilename))
		{
			//if (! preg_match('/^module([_a-zA-Z0-9]*)_([_a-zA-Z0-9]+)\-([0-9]+)\.([0-9\.]+)(\.zip)$/i',$originalfilename)
			if (! preg_match('/^module([a-zA-Z0-9]*)_([-a-zA-Z0-9]+)([_a-zA-Z0-9]*)\-([0-9]+)\.([0-9\.]+)(\.zip)$/i',$originalfilename)
					&& ! preg_match('/^theme([_a-zA-Z0-9]*)_([_a-zA-Z0-9]+)\-([0-9]+)\.([0-9\.]+)(\.zip)$/i',$originalfilename))
			{
				$return['errormsg'] = $this->l('Package seems to not respect some rules:').'<br>';
				$return['errormsg'].= $this->l('Package file name must match module_mypackage-x.y(.z).zip').'<br>';
				$return['errormsg'].= $this->l('Try to build your package with a recent Dolibarr official tool (\'htdocs/build/makepack-dolibarrmodule.pl\' or \'htdocs/build/makepack-dolibarrtheme.pl\' for themes)');
				$return['upload'] = -1;
				$error++;
			}
		}

		if (! $error && preg_match('/(\.zip)$/i',$originalfilename))
		{
			$zip = new ZipArchive();
			$res = $zip->open($_FILES['virtual_product_file']['tmp_name']);
			if ($res === TRUE)
			{
				$resarray=$this->validateZipFile($zip,$originalfilename,$_FILES['virtual_product_file']['tmp_name']);
				//$zip->close();	// already close by validateZipFile
				$error=$resarray['error'];
				$return['upload']=$resarray['upload'];
				$return['errormsg'] = $resarray['errormsg'];
			}
			else
			{
				$return['errormsg'] = $this->l('File can\'t be analyzed. Is it a true zip file ?').'<br>';
				$return['errormsg'].= $this->l('If you think this is an error, send your package by email at contact@dolibarr.org');
				$return['upload'] = -1;
				$error++;
			}
		}

		if (! $error)
		{
			$newfilename = ProductDownload::getNewFilename(); // Return Sha1 file name
			$chemin_destination = _PS_DOWNLOAD_DIR_.$newfilename;

			self::prestalog("Move file ".$_FILES['virtual_product_file']['tmp_name']." to ".$chemin_destination);

			if (move_uploaded_file($_FILES['virtual_product_file']['tmp_name'], $chemin_destination) != true)
			{
				$return['errormsg'] = $this->l('file copy impossible for the moment, please try again later');
				$return['upload'] = -1;
				$chemin_destination=null;
			}

			$return['product_file_path'] = $chemin_destination;
		}

		return $return;
	}

	/**
	 * Check that a zip file is Dolibarr rule compliant
	 */
	private function validateZipFile(&$zip,$originalfilename,$zipfile)
	{
		global $count, $results;

		$error=0;
		$return = array(
				'error' => 0,
				'errormsg'=> null,
				'upload' => 0
		);

		self::prestalog("Validate zip file " . $originalfilename);
		$subdir=basename($zipfile);
		//$dir='/home/dolibarr/dolistore.com/tmp/'.$subdir;
		$dir=sys_get_temp_dir().'/'.$subdir;
		mkdir($dir);
		$zip->extractTo($dir.'/');
		$zip->close();

		// First we check if we need to change $dir (for zip that are module/htdocs/module instead of htdocs/module)
		if (! $error && $dh = opendir($dir))
		{
			$nbofsubdirs=0;
			while (($file = readdir($dh)) !== false)
			{
				if ($file == '.' || $file == '..') continue;
				self::prestalog("We check if dir ".$dir.'/'.$file.'/htdocs exists');
				if (is_dir($dir.'/'.$file.'/htdocs'))
				{
					self::prestalog('Dir '.$dir.'/'.$file.'/htdocs exists. So we use dir='.$dir.'/'.$file.' as root for package to analyse.');
					$dir=$dir.'/'.$file;
					break;
				}
			}
			closedir($dh);
		}

		// Analyze files
		$ismodule=$istheme=0;
		if (is_dir($dir.'/scripts')) $ismodule='module';
		if (is_dir($dir.'/htdocs/themes')) $istheme='theme';
		//if (preg_match('/^module([_a-zA-Z0-9]*)_([_a-zA-Z0-9]+)\-([0-9]+)\.([0-9\.]+)(\.zip)$/i',$originalfilename,$reg))
		if (preg_match('/^module([a-zA-Z0-9]*)_([-a-zA-Z0-9]+)([_a-zA-Z0-9]*)\-([0-9]+)\.([0-9\.]+)(\.zip)$/i',$originalfilename,$reg))
		{
			$ismodule=$reg[2];
			$extmoduleornot=$reg[1];
			if ($extmoduleornot) $ismodule=0;
		}
		if (preg_match('/^theme_([_a-zA-Z0-9]+)\-([0-9]+)\.([0-9\.]+)(\.zip)$/i',$originalfilename,$reg)) $istheme=$reg[1];
		if (! empty($ismodule) || ! empty($istheme))
		{
			self::prestalog("file ismodule=".$ismodule." istheme=".$istheme);
			// It's a module or theme file
			if (! $error && (! empty($ismodule) || ! empty($istheme)) && $dh = opendir($dir))
			{
				$nbofsubdirs=0; $direrror='';
				while (($file = readdir($dh)) !== false)
				{
					if ($file == '.' || $file == '..' || $file == 'README' || $file == 'README.txt') continue;
					self::prestalog("subdirs found for package:".$file);
					$nbofsubdirs++;
					$alloweddirs=array('htdocs','docs','scripts','test','build',($ismodule?$ismodule:($istheme?$istheme:'')));
					if (! in_array($file,$alloweddirs))
					{
						$error++;
						$direrror=$file;
						break;
					}
				}
				if ($error)
				{
					$return['errormsg'].= $this->l('Validation of zip file fails.').'<br>';
					$return['errormsg'].= $this->l('Sorry, a module file can only contains, into zip root:').'<br>';
					$return['errormsg'].= $this->l('- only 1 directory matching your module or theme name,').'<br>';
					$return['errormsg'].= $this->l('- or several directories matching following names: ./htdocs/yourmodulename, ./docs, ./scripts, ./test or ./build.').'<br>';
					$return['errormsg'].= $this->l('But we found a directory or file with name:').' '.$direrror.'<br><br>'."\n";
				}
				closedir($dh);
			}
			// It's a module or theme file (check htdocs directory)
			if (! $error && ! empty($ismodule) && is_dir($dir.'/htdocs') && $dh = opendir($dir.'/htdocs'))
			{
				self::prestalog("we scan ".$dir."/htdocs to be sure there is only one directory (with name of your module) into htdocs");
				$nbofsubdir=0;
				self::prestalog("check there is only one dir into htdocs");
				while (($file = readdir($dh)) !== false)
				{
					if ($file == '.' || $file == '..' || $file == 'README' || $file == 'README.txt') continue;
					if ($file == 'includes') continue;		// For old dolibarr version compatibility
					self::prestalog("we found ".$file);
					$nbofsubdir++;
				}
				closedir($dh);
				if ($nbofsubdir >= 2)
				{
					$return['errormsg'].= $this->l('Warning, starting with Dolibarr 3.3 version, a module file can contains only one dir with name of module (into root of zip or into the htdocs directory)').'<br><br>';
					$error++;
				}
			}
			// Check "custom" compatibility
			if (! $error && ! empty($ismodule))
			{
				$count = 0;
				$results = array();

				$search = array(
						0 => array(
								'name'		=> 'main',
								'types'		=> array('php'),
								'pattern'	=> '(require|include).*(main|master)\.inc\.php',
								'multiple'	=> true
						),
						1 => array(
								'name'		=> 'dol_include_once',
								'types'		=> array('php', 'class.php', 'lib.php', 'modules.php'),
								'pattern'	=> 'dol\_include\_once\([\"\']\/([^\/]*)\/.*;',
								'contain'	=> array($ismodule)
						),
						2 => array(
								'name'			=> 'dol_document_root',
								'types'			=> array('php', 'class.php', 'lib.php', 'modules.php'),
								'pattern'		=> '(require|include)(_once)?\(?(.*)[\"\']+\)?;',
								'id'			=> 3,		// eg. Use $regs[3] for test instead $regs[1]
								'contain'		=> array('DOL_DOCUMENT_ROOT'),
								'notcontain'	=> array($ismodule),
								'strict'		=> true		// if true ('contain' && 'notcontain'), if false or not use ('contain' || 'notcontain')
						)
				);
				$this->getDirContents($dir, $search);

				if (!empty($count))
				{
					foreach($results as $result)
					{
						if ($result['testname'] == 'main')
						{
							$return['errormsg'].= sprintf($this->l('The call of main.inc.php or master.inc.php in the file "%s" is not compatible with the custom directory.'), $result['filename']).'<br><br>';
						}
						else if ($result['testname'] == 'dol_include_once')
						{
							$return['errormsg'].= sprintf($this->l('The call of dol_include_once of the file "%s" is wrong, we must correct the line below:'), $result['filename']).'<br>';
							$return['errormsg'].= $result['line'].'<br><br>';
						}
						else if ($result['testname'] == 'dol_document_root')
						{
							$return['errormsg'].= sprintf($this->l('The call of your module class should not be done with DOL_DOCUMENT_ROOT, the file "%s" is wrong, we must correct the line below with "dol_include_once":'), $result['filename']).'<br>';
							$return['errormsg'].= $result['line'].'<br><br>';
						}
					}

					self::prestalog("file ".$originalfilename." is not compatible with custom directory!", LOG_ERR);

					$error++;
				}
			}
		}
		else
			self::prestalog("file ".$originalfilename." is not a module and not a theme!", LOG_WARNING);

		if (!empty($error))
		{
			$link = '<a target="_blank" href="http://wiki.dolibarr.org/index.php/Module_development#Tree_of_path_for_new_module_files_.28required.29">Dolibarr wiki developer documentation for allowed tree</a>';
			$return['errormsg'].= $this->l('Your zip file does not look to match Dolibarr package rules.').'<br>';
			$return['errormsg'].= sprintf($this->l('See %s:'), $link).'<br>';
			$return['errormsg'].= $this->l('Remind: A module can not provide directories/files found into Dolibarr standard distribution.')."<br>\n";
			$return['errormsg'].= $this->l('If you think this is an error or don\'t undertand this message, send your package by email at contact@dolibarr.org');
			$return['upload'] = -1;
			$error++;
		}

		$return['error'] = $error;

		return $return;
	}

	private function getDirContents($dir, $search = array(), &$results = array())
	{
		global $count, $results;

		$files = scandir($dir);

		foreach ($files as $key => $value)
		{
			$path = realpath($dir . DIRECTORY_SEPARATOR . $value);
			if (!is_dir($path))
			{
				$content = file_get_contents($path);
				$fileName = basename($path);
				$file_array = explode(".", $fileName);

				foreach ($search as $pattern)
				{
					if (count($file_array) > 1)
					{
						$ext = $file_array[1];
						if (!empty($file_array[2])) {
							$ext = $file_array[1] . '.' . $file_array[2];
						}

						if (in_array($ext, $pattern['types']))
						{
							//echo $path.'<br>';
							//echo $ext.'<br>';
							//echo implode(',', $pattern['types']).'<br>';

							if (!empty($pattern['multiple']))
							{
								preg_match_all('/' . $pattern['pattern'] . '/', $content, $regs);
								// Error if only one result (0 is ok, >1 is ok)
								if (!empty($regs) && count($regs[0]) == 1)
								{
									$results[] = array(
											'testname'	=> $pattern['name'],
											'filename'	=> $fileName
									);
									$count++;
								}
							}
							else
							{
								$id = (!empty($pattern['id']) ? $pattern['id'] : 1);
								preg_match_all('/' . $pattern['pattern'] . '/', $content, $regs);
								if (!empty($regs) && !empty($regs[$id]))
								{
									foreach($regs[$id] as $i => $string)
									{
										if (!empty($pattern['contain']) && is_array($pattern['contain']))
										{
											foreach ($pattern['contain'] as $contain)
											{
												// Mode strict true : doit contenir && ne pas contenir
												if (!empty($pattern['notcontain']) && !empty($pattern['strict']) && is_array($pattern['notcontain']))
												{
													foreach ($pattern['notcontain'] as $notcontain)
													{
														if (strstr($string, $contain) && strstr($string, $notcontain))
														{
															$results[] = array(
																	'testname'	=> $pattern['name'],
																	'filename'	=> $fileName,
																	'line'		=> $regs[0][$i]
															);
															$count++;
														}
													}
												}
												// Mode strict false : doit contenir
												else if (!strstr($string, $contain))
												{
													$results[] = array(
															'testname'	=> $pattern['name'],
															'filename'	=> $fileName,
															'line'		=> $regs[0][$i]
													);
													$count++;
												}
											}
										}
										// Ou ne doit pas contenir
										if (empty($count) && !empty($pattern['notcontain']) && empty($pattern['strict']) && is_array($pattern['notcontain']))
										{
											foreach ($pattern['notcontain'] as $notcontain)
											{
												if (strstr($string, $notcontain))
												{
													$results[] = array(
															'testname'	=> $pattern['name'],
															'filename'	=> $fileName,
															'line'		=> $regs[0][$i]
													);
													$count++;
												}
											}
										}
									}
								}
							}
							//var_dump($regs);
						}
					}
				}

			}
			else if ($value != "." && $value != "..") {
				$this->getDirContents($path, $search, $results);
			}
		}
	}

	/**
	 *
	 * @param unknown $customer
	 */
	public function addProduct($customer)
	{
		$flagError = 0;
		$limit = (Configuration::get('PS_PRODUCT_SHORT_DESC_LIMIT') ? Configuration::get('PS_PRODUCT_SHORT_DESC_LIMIT') : 400);

		$id_shop = (int)Shop::getContextShopID();
		$id_lang = (int)$this->context->language->id;
		$languages = Language::getLanguages();

		$status = (Tools::isSubmit('active') ? Tools::getValue('active') : 0);
		if (!$customer['admin']) $status = 0;

		$product_file_name = Tools::getValue('product_file_name');
		$product_file_path = Tools::getValue('product_file_path');

		//self::prestalog("We click on 'Submit this product' button: product_file_name=".$product_file_name." - product_file_path=".$product_file_path." - upload=".$upload);

		if (empty($product_file_path) || (empty($product_file_name) && empty($_FILES['virtual_product_file']['name'])))
		{
			$this->create_errors[] = $this->l('You have to upload a product.');
			$flagError--;
		}

		if ($flagError == 0)
		{
			$productlang = array();
			$mandatory_id_lang = Language::getIdByIso('en');

			//prise des libelles
			foreach ($languages as $language)
			{
				$product_name = $resume = $keywords = $description = "";
				$product_name = trim(Tools::getValue('product_name_l'.$language['id_lang']));
				$resume = Tools::getValue('resume_'.$language['id_lang']);
				$keywords = trim(Tools::getValue('keywords_'.$language['id_lang']));
				$description = Tools::getValue('description_'.$language['id_lang']);

				if ($language['iso_code'] == "en" && ($product_name == "" || $resume == "" || $description == "" || $keywords == ""))
				{
					$this->create_errors[] = $this->l('All English fields are required.');
					$flagError--;
				}
				else
				{
					if ($language['iso_code'] != "en" && $product_name == "") {
						$product_name = (isset($productlang[$mandatory_id_lang]['name'])?$productlang[$mandatory_id_lang]['name']:'');
					}
					if ($language['iso_code'] != "en" && $resume == "") {
						$resume = (isset($productlang[$mandatory_id_lang]['resume'])?$productlang[$mandatory_id_lang]['resume']:'');
					}
					if ($language['iso_code'] != "en" && $description == "") {
						$description = (isset($productlang[$mandatory_id_lang]['description'])?$productlang[$mandatory_id_lang]['description']:'');
					}
					if ($language['iso_code'] != "en" && $keywords == "") {
						$keywords = (isset($productlang[$mandatory_id_lang]['keywords'])?$productlang[$mandatory_id_lang]['keywords']:'');
					}

					$productlang[$language['id_lang']]['name'] = $product_name;
					$productlang[$language['id_lang']]['resume'] = $resume;
					$productlang[$language['id_lang']]['keywords'] = $keywords;
					$productlang[$language['id_lang']]['description'] = $description;

					$resumeLength = Tools::strlen(strip_tags($resume)) - 5;
					if ($resumeLength > $limit) {
						$this->create_errors[] = sprintf(
								$this->l('This description short field (%1$s) is too long: %2$d chars max (current count %3$d).'),
								$language['name'],
								$limit,
								$resumeLength
								);

						$flagError--;
					}
				}
			}

			//recuperation de la categorie par defaut
			$id_categorie_default="";
			$categories = Category::getSimpleCategories($id_lang);
			foreach ($categories AS $categorie) {
				$categories_checkbox = Tools::getValue('categories_checkbox_'.$categorie['id_category']);
				if (! empty($categories_checkbox) && $categories_checkbox == 1) {
					$id_categorie_default = $categorie['id_category'];
					break;
				}
			}
			if ($id_categorie_default == "")
			{
				$this->create_errors[] = $this->l('You have to choose a category.');
				$flagError--;
			}
		}

		//si pas derreur de saisis, traitement en base
		if ($flagError == 0)
		{
			$taxe_rate = Tools::getValue('rate_tax');
			$taxe_id = Tools::getValue('id_tax');

			// Define prices
			$prix_ht = (Tools::isSubmit('price') ? Tools::getValue('price') : 0);
			//$prix_ttc = round($prix_ht * (100 + (float) $taxe_rate) / 100, 2);

			//prise des date
			$dateToday = date ("Y-m-d");
			$dateNow = date ("Y-m-d H:i:s");
			$dateRef = date ("YmdHis");

			//reference du produit
			$reference = 'c'.$customer['id_customer'].'d'.$dateRef;

			$qty=1000;
			if ($prix_ht == 0) {
				$taxe_id=0;
				$qty=0;
			}

			// Module version
			$module_version = (Tools::isSubmit('module_version') ? Tools::getValue('module_version') : null);

			// Module version
			$dolibarr_core_include = (Tools::isSubmit('dolibarr_core_include') ? Tools::getValue('dolibarr_core_include') : 0);

			// Dolibarr min/max compatibility
			$dolibarr_min = (Tools::isSubmit('dolibarr_min') ? Tools::getValue('dolibarr_min') : null);
			$dolibarr_min_status = (Tools::isSubmit('dolibarr_min_status') ? Tools::getValue('dolibarr_min_status') : 0);
			$dolibarr_max = (Tools::isSubmit('dolibarr_max') ? Tools::getValue('dolibarr_max') : null);
			$dolibarr_max_status = (Tools::isSubmit('dolibarr_max_status') ? Tools::getValue('dolibarr_max_status') : 0);

			//insertion du produit en base
			$query = 'INSERT INTO `'._DB_PREFIX_.'product` (
					`id_supplier`, `id_manufacturer`, `id_tax_rules_group`, `id_category_default`, `on_sale`, `ean13`, `ecotax`, `is_virtual`,
					`quantity`, `price`, `wholesale_price`, `reference`, `supplier_reference`, `location`, `weight`, `out_of_stock`,
					`quantity_discount`, `customizable`, `uploadable_files`, `text_fields`,	`active`, `indexed`, `date_add`, `date_upd`,
					`module_version`, `dolibarr_min`, `dolibarr_min_status`, `dolibarr_max`, `dolibarr_max_status`, `dolibarr_core_include`
					) VALUES (
		            0, 0, '.$taxe_id.', '.$id_categorie_default.', 0, 0, 0.00, 1, '.$qty.', '.$prix_ht.', '.$prix_ht.', \''.$reference.'\', \'\', \'\', 0, 0, 0, 0, 0, 0,
		            '.$status.', 1, \''.$dateNow.'\', \''.$dateNow.'\', \''.$module_version.'\', \''.$dolibarr_min.'\', '.$dolibarr_min_status.', \''.$dolibarr_max.'\', '.$dolibarr_max_status.', '.$dolibarr_core_include.'
			)';

			$result = Db::getInstance()->Execute($query);
			if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

			// Get product id
			$query = 'SELECT `id_product` FROM `'._DB_PREFIX_.'product`
					WHERE `reference` = \''.$reference.'\'
					AND `date_add` = \''.$dateNow.'\' ';
			$result = Db::getInstance()->ExecuteS($query);
			if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
			foreach ($result AS $row)
			{
				$id_product = $row['id_product'];
			}

			$flagError = $id_product; // For return the product id

			//insertion du produit sur le shop
			$query = 'INSERT INTO '._DB_PREFIX_.'product_shop (
					`id_product`, `id_shop`, `id_tax_rules_group`, `id_category_default`, `on_sale`, `ecotax`,
					`price`, `wholesale_price`,	`customizable`, `uploadable_files`, `text_fields`,	`active`, `indexed`, `date_add`, `date_upd`
					) VALUES (
		            '.$id_product.', '.$id_shop.', '.$taxe_id.', '.$id_categorie_default.', 0, 0.00, '.$prix_ht.', '.$prix_ht.', 0, 0, 0, '.$status.', 1, \''.$dateNow.'\', \''.$dateNow.'\'
			)';

			$result = Db::getInstance()->Execute($query);
			if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

			// insertion du produit en stock
			$query = 'INSERT INTO `'._DB_PREFIX_.'stock_available` (
					`id_product`, `id_product_attribute`, `id_shop`, `id_shop_group`, `quantity`, `depends_on_stock`, `out_of_stock`
					) VALUES (
		            '.$id_product.', 0, '.$id_shop.', 0, '.$qty.', 0, 0
			)';

			$result = Db::getInstance()->Execute($query);
			if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

			foreach ($productlang as $id_lang => $product)
			{
				// Add language description of product
				$link_rewrite = preg_replace('/[^a-zA-Z0-9-]/','-', $product['name']);

				$query = 'INSERT INTO `'._DB_PREFIX_.'product_lang` (
						`id_product`, `id_lang`, `description`, `description_short`, `link_rewrite`, `meta_description`, `meta_keywords`, `meta_title`, `name`, `available_now`, `available_later`
						) VALUES (
						'.$id_product.", ".$id_lang.", '".addslashes($product['description'])."', '".addslashes($product['resume'])."', '".addslashes($link_rewrite)."', '".addslashes($product['name'])."',
						'".addslashes($product['keywords'])."', '".addslashes($product['name'])."', '".addslashes($product['name'])."', '".$this->l('Available')."', '".$this->l('In build')."')";
				$result = Db::getInstance()->Execute($query);
				if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query! : '.$query));

				// Add tag description of product
				$tags=preg_split('/[\s,]+/', $product['keywords']);
				foreach($tags as $tag)
				{
					$id_tag=0;

					// Search existing tag
					$query = 'SELECT `id_tag` FROM `'._DB_PREFIX_.'tag`	WHERE `id_lang` = \''.$id_lang.'\' AND name = \''.addslashes($tag).'\' ';
					$result = Db::getInstance()->ExecuteS($query);
					if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
					foreach ($result AS $row)
					{
						$id_tag = $row['id_tag'];
						//self::prestalog("tag id for id_lang ".$id_lang.", name ".$tag." is ".$id_tag);
					}

					if (empty($id_tag))
					{
						$query = 'INSERT INTO `'._DB_PREFIX_.'tag` (`id_lang`, `name`) VALUES (\''.$id_lang.'\', \''.addslashes($tag).'\')';
						$result = Db::getInstance()->Execute($query);
						//if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query! : '.$query));

						$id_tag = Db::getInstance()->Insert_ID();
						//self::prestalog("We created tag for id_lang ".$id_lang.", name ".$tag.", id is ".$id_tag);
					}

					if (! empty($id_tag) && $id_tag > 0)
					{
						// Add tag link
						$query = 'INSERT INTO `'._DB_PREFIX_.'product_tag` (`id_product`, `id_tag`) VALUES (\''.$id_product.'\', \''.$id_tag.'\')';
						$result = Db::getInstance()->Execute($query);

						//self::prestalog("We insert link id_product ".$id_product.", id_tag ".$id_tag);
					}
				}
			}

			//mise en base du lien avec le produit telechargeable
			$product_file_newname = basename($product_file_path);
			$nbdaysaccessible = Configuration::get('BLOCKMYSALES_NBDAYSACCESSIBLE');
			$nb_days_accessible = (Tools::isSubmit('nb_days_accessible') ? Tools::getValue('nb_days_accessible') : (!empty($nbdaysaccessible) ? $nbdaysaccessible : 365));

			$query = 'INSERT INTO `'._DB_PREFIX_.'product_download` (`id_product`, `display_filename`, `filename`, `date_add`, `date_expiration`, `nb_days_accessible`, `nb_downloadable`, `active`) VALUES (
					'.$id_product.', "'.$product_file_name.'", "'.$product_file_newname.'", "'.$dateNow.'", "0000-00-00 00:00:00", "'.$nb_days_accessible.'", 0, 1
			)';
			$result = Db::getInstance()->Execute($query);
			if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query! : '.$query));

			// Si produit gratuit mise en piece jointe du fichier
			if ($prix_ht == 0)
			{
				//mise dans la base des fichiers joints
				$query = 'INSERT INTO `'._DB_PREFIX_.'attachment` (`file`, `file_name`, `mime`) VALUES ("'.$product_file_newname.'", "'.$product_file_name.'", "binary/octet-stream");';
				$result = Db::getInstance()->Execute($query);

				//recuperation de l'id du fichier joint
				$query = 'SELECT `id_attachment` FROM `'._DB_PREFIX_.'attachment`
						WHERE `file` = "'.$product_file_newname.'"';
				$result = Db::getInstance()->ExecuteS($query);
				if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
				foreach ($result AS $row)
					$id_attachment = $row['id_attachment'];

				//set des nom du fichier en toute les langues
				foreach ($languages as $language) {
					$id_lang = $language['id_lang'];
					$query = 'INSERT INTO `'._DB_PREFIX_.'attachment_lang` (`id_attachment`, `id_lang`, `name`, `description`) VALUES ('.$id_attachment.', '.$id_lang.', "'.$product_file_name.'", "")';
					$result = Db::getInstance()->Execute($query);
				}

				//cree lien fichier vers fichiers joint
				$query = 'INSERT INTO `'._DB_PREFIX_.'product_attachment` (`id_product`, `id_attachment`) VALUES ('.$id_product.', '.$id_attachment.')';
				$result = Db::getInstance()->Execute($query);

				// Désactive la mise en panier et l'affichage du prix dans la table product
				$query = 'UPDATE `'._DB_PREFIX_.'product` SET `available_for_order` = 0, `show_price` = 0, `cache_has_attachments` = 1 WHERE `id_product` = '.$id_product;
				$result = Db::getInstance()->Execute($query);

				// Désactive la mise en panier et l'affichage du prix dans la table product_shop
				$query = 'UPDATE `'._DB_PREFIX_.'product_shop` SET `available_for_order` = 0, `show_price` = 0 WHERE `id_product` = '.$id_product;
				$result = Db::getInstance()->Execute($query);
			}
			else
			{
				//self::prestalog("price is ".$prix_ttc.", so not null, so we do not add file to download tabs");
			}

			//inscription du produit dans ttes les categories choisis
			foreach ($categories AS $categorie) {
				$categories_checkbox = Tools::getValue('categories_checkbox_'.$categorie['id_category']);
				if (! empty($categories_checkbox) && $categories_checkbox == 1 && $categorie['id_category'] != 1) {
					$query = 'INSERT INTO `'._DB_PREFIX_.'category_product` (`id_category`, `id_product`, `position`) VALUES ('.$categorie['id_category'].', '.$id_product.', 1);';
					//self::prestalog("Add category of product sql=".$query);
					$result = Db::getInstance()->Execute($query);
					if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query! : '.$query));
				}
			}
		}

		return $flagError;
	}

	/**
	 *
	 * @param unknown $id_product
	 * @param unknown $customer
	 * @return number
	 */
	public function updateProduct($id_product, $customer)
	{
		$flagError = 0;
		$limit = (Configuration::get('PS_PRODUCT_SHORT_DESC_LIMIT') ? Configuration::get('PS_PRODUCT_SHORT_DESC_LIMIT') : 400);
		$productlang = array();

		$id_shop = (int)Shop::getContextShopID();
		$id_lang = (int)$this->context->language->id;
		$languages = Language::getLanguages();
		$mandatory_id_lang = Language::getIdByIso('en');

		$status = (Tools::isSubmit('active') ? Tools::getValue('active') : -1);
		if (!$customer['admin']) $status = -1;

		// repair stock_available table
		/*
		$query = 'SELECT `id_product`, `price` FROM `'._DB_PREFIX_.'product`';
		$result = Db::getInstance()->ExecuteS($query);
		if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
		foreach ($result AS $row)
		{
			$id_prod = $row['id_product'];
			$price = $row['price'];
			$qty = 1000;
			if ($price == 0) $qty = 0;

			$query = 'SELECT `id_product` FROM `'._DB_PREFIX_.'stock_available` WHERE `id_product` = '.$id_prod;
			$result = Db::getInstance()->ExecuteS($query);
			if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
			if (empty($result))
			{
				// insertion du produit en stock
				$query = 'INSERT INTO `'._DB_PREFIX_.'stock_available` (
					`id_product`, `id_product_attribute`, `id_shop`, `id_shop_group`, `quantity`, `depends_on_stock`, `out_of_stock`
					) VALUES (
		            '.$id_prod.', 0, '.$id_shop.', 0, '.$qty.', 0, 0
				)';

				$result = Db::getInstance()->Execute($query);
				if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
				echo $query.'<br>';
			}
		}
		*/
		//self::prestalog("We click on 'Update this product' button: product_file_name=".$product_file_name." - product_file_path=".$product_file_path." - upload=".$upload);

		//prise des libelles
		foreach ($languages as $language)
		{
			$product_name = $resume = $keywords = $description = "";
			$product_name = trim(Tools::getValue('product_name_l'.$language['id_lang']));
			$resume = Tools::getValue('resume_'.$language['id_lang']);
			$keywords = trim(Tools::getValue('keywords_'.$language['id_lang']));
			$description = Tools::getValue('description_'.$language['id_lang']);

			if ($language['iso_code'] == "en" && ($product_name == "" || $resume == "" || $description == "" || $keywords == ""))
			{
				$this->update_errors[] = $this->l('All English fields are required.');
				$flagError--;
			}
			else
			{
				if ($language['iso_code'] != "en" && $product_name == "") {
					$product_name = (isset($productlang[$mandatory_id_lang]['name'])?$productlang[$mandatory_id_lang]['name']:'');
				}
				if ($language['iso_code'] != "en" && $resume == "") {
					$resume = (isset($productlang[$mandatory_id_lang]['resume'])?$productlang[$mandatory_id_lang]['resume']:'');
				}
				if ($language['iso_code'] != "en" && $description == "") {
					$description = (isset($productlang[$mandatory_id_lang]['description'])?$productlang[$mandatory_id_lang]['description']:'');
				}
				if ($language['iso_code'] != "en" && $keywords == "") {
					$keywords = (isset($productlang[$mandatory_id_lang]['keywords'])?$productlang[$mandatory_id_lang]['keywords']:'');
				}

				$productlang[$language['id_lang']]['name'] = $product_name;
				$productlang[$language['id_lang']]['resume'] = $resume;
				$productlang[$language['id_lang']]['keywords'] = $keywords;
				$productlang[$language['id_lang']]['description'] = $description;

				$resumeLength = Tools::strlen(strip_tags($resume)) - 5;
				if ($resumeLength > $limit) {
					$this->update_errors[] = sprintf(
							$this->l('This description short field (%1$s) is too long: %2$d chars max (current count %3$d).'),
							$language['name'],
							$limit,
							$resumeLength
							);

					$flagError--;
				}
			}
		}

		//recuperation de la categorie par defaut
		$id_categorie_default="";
		$categories = Category::getSimpleCategories($id_lang);
		foreach ($categories AS $categorie) {
			$categories_checkbox = Tools::getValue('categories_checkbox_'.$categorie['id_category']);
			if (! empty($categories_checkbox) && $categories_checkbox == 1) {
				$id_categorie_default = $categorie['id_category'];
				break;
			}
		}
		if ($id_categorie_default == "")
		{
			$this->update_errors[] = $this->l('You have to choose a category.');
			$flagError--;
		}

		//si pas derreur de saisis, traitement en base
		if ($flagError == 0)
		{
			$taxe_rate = Tools::getValue('rate_tax');
			$taxe_id = Tools::getValue('id_tax');

			// Define prices
			$prix_ht = (Tools::isSubmit('price') ? Tools::getValue('price') : 0);
			//$prix_ttc = round($prix_ht * (100 + (float) $taxe_rate) / 100, 2);

			//gestion des fichiers selon prix
			$oldPrice = round(Tools::isSubmit('op') ? Tools::getValue('op') : 0, 2);
			$newPrice = round(Tools::isSubmit('price') ? Tools::getValue('price') : 0, 2);

			//prise des date
			$dateToday = date ("Y-m-d");
			$dateNow = date ("Y-m-d H:i:s");
			$dateRef = date ("YmdHis");

			//reference du produit
			$reference = 'c'.$customer['id_customer'].'d'.$dateRef;

			$qty=1000;
			if ($prix_ht == 0) {
				$taxe_id=0;
				$qty=0;
			}

			// Module version
			$module_version = (Tools::isSubmit('module_version') ? Tools::getValue('module_version') : null);

			// Module version
			$dolibarr_core_include = (Tools::isSubmit('dolibarr_core_include') ? Tools::getValue('dolibarr_core_include') : 0);

			// Dolibarr min/max compatibility
			$dolibarr_min = (Tools::isSubmit('dolibarr_min') ? Tools::getValue('dolibarr_min') : null);
			$dolibarr_min_status = (Tools::isSubmit('dolibarr_min_status') ? Tools::getValue('dolibarr_min_status') : 0);
			$dolibarr_max = (Tools::isSubmit('dolibarr_max') ? Tools::getValue('dolibarr_max') : null);
			$dolibarr_max_status = (Tools::isSubmit('dolibarr_max_status') ? Tools::getValue('dolibarr_max_status') : 0);

			//Mise a jour du produit en base
			$query = 'UPDATE `'._DB_PREFIX_.'product` SET
					`id_category_default` 	= '.$id_categorie_default.',
					`quantity` 				= '.$qty.',
					`price` 				= '.$prix_ht.',
					`wholesale_price` 		= '.$prix_ht.',
					`id_tax_rules_group`	= '.$taxe_id.',
					`reference` 			= \''.$reference.'\',
					`module_version` 		= \''.$module_version.'\',
					`dolibarr_min` 			= \''.$dolibarr_min.'\',
					`dolibarr_min_status` 	= '.$dolibarr_min_status.',
					`dolibarr_max` 			= \''.$dolibarr_max.'\',
					`dolibarr_max_status` 	= '.$dolibarr_max_status.',
					`dolibarr_core_include` = '.$dolibarr_core_include.',';
			if ($status >= 0) $query.= ' `active` = '.$status.',';		// We don't change if status is -1
			if ($oldPrice == 0 && $newPrice > 0) {
				$query.= ' `available_for_order` = 1, `show_price` = 1, `cache_has_attachments` = 0,';
			}
			$query.= ' `indexed` 			= 1,
					`date_upd` 				= \''.$dateNow.'\'
					WHERE `id_product` = '.$id_product.' ';
			$result = Db::getInstance()->Execute($query);
			if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

			//Mise a jour du produit en base pour la boutique
			$query = 'UPDATE `'._DB_PREFIX_.'product_shop` SET
					`id_category_default` 	= '.$id_categorie_default.',
					`price` 				= '.$prix_ht.',
					`wholesale_price` 		= '.$prix_ht.',
					`id_tax_rules_group`	= '.$taxe_id.',';
			if ($status >= 0) $query.= ' `active` = '.$status.',';		// We don't change if status is -1
			if ($oldPrice == 0 && $newPrice > 0) {
				$query.= ' `available_for_order` = 1, `show_price` = 1,';
			}
			$query.= ' `indexed` 			= 1,
					`date_upd` 				= \''.$dateNow.'\'
					WHERE `id_product` = '.$id_product.' ';
			$result = Db::getInstance()->Execute($query);
			if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

			// Delete tag description of product
			$query = "DELETE FROM "._DB_PREFIX_."product_tag WHERE id_product = ".$id_product;
			$result = Db::getInstance()->Execute($query);
			//self::prestalog("We delete all links to tags for id_product ".$id_product);

			foreach ($productlang as $id_lang => $product)
			{
				//mise en base des libelle anglais et fr et autre s'il y a
				$link_rewrite = preg_replace('/[^a-zA-Z0-9-]/','-', $product['name']);

				$query = "UPDATE `"._DB_PREFIX_."product_lang` SET
						`description`		= '".addslashes($product['description'])."',
						`description_short`	= '".addslashes($product['resume'])."',
						`link_rewrite`		= '".addslashes($link_rewrite)."',
						`meta_description`	= '".addslashes($product['name'])."',
						`meta_keywords`		= '".addslashes($product['keywords'])."',
						`meta_title`		= '".addslashes($product['name'])."',
						`name`				= '".addslashes($product['name'])."',
						`available_now`		= '".$this->l('Available')."',
						`available_later`	= '".$this->l('In build')."'
						WHERE `id_lang`	= ".$id_lang."
						AND `id_product` = ".$id_product;

				$result = Db::getInstance()->Execute($query);
				if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query! : '.$query));

				// Add tag description of product
				$tags=preg_split('/[\s,]+/', $product['keywords']);
				foreach($tags as $tag)
				{
					$id_tag=0;

					// Search existing tag
					$query = 'SELECT `id_tag` FROM `'._DB_PREFIX_.'tag` WHERE `id_lang` = \''.$id_lang.'\' AND `name` = \''.addslashes($tag).'\' ';
					$result = Db::getInstance()->ExecuteS($query);
					if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
					foreach ($result AS $row)
					{
						$id_tag = $row['id_tag'];
						//self::prestalog("tag id for id_lang ".$id_lang.", name ".$tag." is ".$id_tag);
					}

					if (empty($id_tag))
					{
						$query = "INSERT INTO `"._DB_PREFIX_."tag` (`id_lang`, `name`) VALUES ('".$id_lang."', '".addslashes($tag)."')";
						$result = Db::getInstance()->Execute($query);
						//if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query! : '.$query));

						$id_tag = Db::getInstance()->Insert_ID();
						//self::prestalog("We created tag for id_lang ".$id_lang.", name ".$tag.", id is ".$id_tag);
					}

					if (! empty($id_tag) && $id_tag > 0)
					{
						// Add tag link
						$query = "INSERT INTO `"._DB_PREFIX_."product_tag` (`id_product`, `id_tag`) VALUES ('".$id_product."', '".$id_tag."')";
						$result = Db::getInstance()->Execute($query);

						//self::prestalog("We insert link id_product ".$id_product.", id_tag ".$id_tag);
					}
				}
			}

			$product_file_name = Tools::getValue('product_file_name');
			$product_file_path = Tools::getValue('product_file_path');
			$newfile = ($product_file_path ? 1 : 0);

			$nbdaysaccessible = Configuration::get('BLOCKMYSALES_NBDAYSACCESSIBLE');
			$nb_days_accessible = (Tools::isSubmit('nb_days_accessible') ? Tools::getValue('nb_days_accessible') : (!empty($nbdaysaccessible) ? $nbdaysaccessible : 365));

			//mise en base du lien avec le produit telechargeable
			if ($newfile)
			{
				$product_file_newname = basename($product_file_path);

				$query = 'DELETE FROM `'._DB_PREFIX_.'product_download` WHERE `id_product` = '.$id_product.';';
				$result1 = Db::getInstance()->Execute($query);

				$query = 'INSERT INTO `'._DB_PREFIX_.'product_download` (`id_product`, `display_filename`, `filename`, `date_add`, `date_expiration`, `nb_days_accessible`, `nb_downloadable`, `active`) VALUES (
						'.$id_product.', "'.$product_file_name.'", "'.$product_file_newname.'", "'.$dateNow.'", "0000-00-00 00:00:00", "'.$nb_days_accessible.'", 0, 1)';
				//self::prestalog("A new file is asked: We add it into product_download query=".$query);
				$result = Db::getInstance()->Execute($query);
				if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query! : '.$query));
			}
			else
			{
				// update number of days accessible
				$query = 'UPDATE `'._DB_PREFIX_.'product_download` SET `nb_days_accessible` = "'.$nb_days_accessible.'" WHERE `id_product` = '.$id_product;
				$result = Db::getInstance()->Execute($query);

				//recup des infos fichier
				$query = 'SELECT `display_filename`, `filename` FROM `'._DB_PREFIX_.'product_download`
						  WHERE `id_product` = '.$id_product.' ';
				//self::prestalog("No new file, we search old value query=".$query);
				$result = Db::getInstance()->ExecuteS($query);
				if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
				foreach ($result AS $row)
				{
					$product_file_name = $row['display_filename'];
					$product_file_path = $row['filename'];
				}
			}

			// Si un fichier a ete modifier ou le prix modifie
			if ($newfile || $oldPrice != $newPrice)
			{
				if ($newfile || $newPrice > 0)
				{
					//delete des attachments
					$query = 'SELECT `id_attachment` FROM `'._DB_PREFIX_.'product_attachment`
						WHERE `id_product` = '.$id_product.' ';
					//self::prestalog("Search list of attachment to delete sql=".$query);
					$result = Db::getInstance()->ExecuteS($query);
					if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
					foreach ($result AS $row)
					{
						$id_attachment =  $row['id_attachment'];
						//self::prestalog("Delete attachment num ".$id_attachment);

						if ($id_attachment > 0)
						{
							$query = 'DELETE FROM `'._DB_PREFIX_.'attachment` WHERE `id_attachment` = '.$id_attachment.';';
							$result1 = Db::getInstance()->Execute($query);

							$query = 'DELETE FROM `'._DB_PREFIX_.'attachment_lang` WHERE `id_attachment` = '.$id_attachment.';';
							$result2 = Db::getInstance()->Execute($query);

							$query = 'DELETE FROM `'._DB_PREFIX_.'product_attachment` WHERE `id_attachment` = '.$id_attachment.';';
							$result3 = Db::getInstance()->Execute($query);
						}
					}
				}
				if (($newfile && $newPrice == 0) || ($newPrice == 0 && $oldPrice > 0))
				{
					$product_file_newname = basename($product_file_path);

					//creation dun attachement
					$query = 'INSERT INTO `'._DB_PREFIX_.'attachment` (`file`, `file_name`, `mime`) VALUES ("'.$product_file_newname.'", "'.$product_file_name.'", "binary/octet-stream");';
					//self::prestalog("Add attachment sql=".$query);
					$result = Db::getInstance()->Execute($query);

					$query = 'SELECT `id_attachment` FROM `'._DB_PREFIX_.'attachment`
							WHERE `file` = "'.$product_file_newname.'"';
					$result = Db::getInstance()->ExecuteS($query);
					if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
					foreach ($result AS $row)
					{
						$id_attachment = $row['id_attachment'];
						//self::prestalog("Add attachment for num ".$id_attachment);

						foreach ($languages as $language)
						{
							$id_lang = $language['id_lang'];
							$query = 'INSERT INTO `'._DB_PREFIX_.'attachment_lang` (`id_attachment`, `id_lang`, `name`, `description`) VALUES ('.$id_attachment.', '.$id_lang.', "'.$product_file_name.'", "")';
							$result = Db::getInstance()->Execute($query);
						}

						$query = 'INSERT INTO `'._DB_PREFIX_.'product_attachment` (`id_product`, `id_attachment`) VALUES ('.$id_product.', '.$id_attachment.')';
						$result = Db::getInstance()->Execute($query);
					}

					// Désactive la mise en panier et l'affichage du prix dans la table product
					$query = 'UPDATE `'._DB_PREFIX_.'product` SET `available_for_order` = 0, `show_price` = 0, `cache_has_attachments` = 1 WHERE `id_product` = '.$id_product;
					$result = Db::getInstance()->Execute($query);

					// Désactive la mise en panier et l'affichage du prix dans la table product_shop
					$query = 'UPDATE `'._DB_PREFIX_.'product_shop` SET `available_for_order` = 0, `show_price` = 0 WHERE `id_product` = '.$id_product;
					$result = Db::getInstance()->Execute($query);
				}
			}

			//inscription du produit dans ttes les categories choisis
			$query = 'DELETE FROM `'._DB_PREFIX_.'category_product` WHERE `id_product` = '.$id_product;
			$query.= " AND `id_category` <> 1";	// If product was on home, we keep it on home.
			//self::prestalog("Delete category of product sql=".$query);
			$result = Db::getInstance()->Execute($query);
			if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query! : '.$query));

			foreach ($categories AS $categorie) {
				$categories_checkbox = Tools::getValue('categories_checkbox_'.$categorie['id_category']);
				if (! empty($categories_checkbox) && $categories_checkbox == 1 && $categorie['id_category'] != 1) {
					$query = 'INSERT INTO `'._DB_PREFIX_.'category_product` (`id_category`, `id_product`, `position`) VALUES ('.$categorie['id_category'].', '.$id_product.', 1);';
					//self::prestalog("Add category of product sql=".$query);
					$result = Db::getInstance()->Execute($query);
					if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query! : '.$query));
				}
			}
		}

		if (empty($flagError)) {
			unset($_POST["product_file_path"]);
		}

		return $flagError;
	}

	/**
	 *
	 */
	public function addImages($id_product, $customer_id)
	{
		$flagError = false;

		$languages = Language::getLanguages();

		if ($_FILES['image_product']['error'])
		{
			switch ($_FILES['image_product']['error'])
			{
				case 1: // UPLOAD_ERR_INI_SIZE
					$flagError = $this->l('File size is higher than server limit !');
					break;
				case 2: // UPLOAD_ERR_FORM_SIZE
					$flagError = $this->l('File size if higher than limit in HTML form !');
					break;
				case 3: // UPLOAD_ERR_PARTIAL
					$flagError = $this->l('File transfert was aborted !');
					break;
				case 4: // UPLOAD_ERR_NO_FILE
					$flagError = $this->l('File name was not defined or file size is null !');
					break;
			}
		}
		else
		{
			//check  de l'exctention
			preg_match('/\.([a-zA-Z0-9]+)$/',$_FILES['image_product']['name'],$reg);
			$extention_image = strtolower($reg[1]);
			if (! in_array($extention_image, array("jpg","png","gif","jpeg"))) {
				$flagError = sprintf($this->l('Your picture must have JPG, GIF or PNG format, the format %s is not authorized.'), strtoupper($extention_image));
			}

			//si pas derreur de saisis, traitement en base
			if (!$flagError)
			{
				//check du remplissage des champs
				foreach ($languages as $language)
				{
					$image_description = "";
					$image_description = isset($_POST["legende_image_".$language['id_lang']])?$_POST["legende_image_".$language['id_lang']]:'';

					/*if ($image_description[$x] == "") {
					 echo "<div style='color:#FF0000'>"; echo aff("Tous les champs sont obligatoires.", "All fields are required.", $iso_langue_en_cours); echo " </div>";
					 $flagError = 1;
					 break;
					 }*/
				}

				$chemin_destination = _PS_TMP_IMG_DIR_.time()."c".intval($customer_id);

				if (move_uploaded_file($_FILES['image_product']['tmp_name'], $chemin_destination) != true)
				{
					$flagError = $this->l('file copy impossible for the moment, please try again later');
				}
				else
				{
					$id_shop = (int)Shop::getContextShopID();

					//prise des parametres de place de l'image en base
					$query = 'SELECT `id_image` FROM `'._DB_PREFIX_.'image`	WHERE `id_product` = '.$id_product;
					$result = Db::getInstance()->ExecuteS($query);
					if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

					$position = 1;
					$is_cover = 1;
					foreach ($result AS $row) {
						$id_image = $row['id_image'];
						$position++;
						$is_cover = 0;
					}

					//insertion de l'image en base
					$query = 'INSERT INTO `'._DB_PREFIX_.'image` (`id_product`, `position`, `cover`) VALUES ('.$id_product.', '.$position.', '.($is_cover?$is_cover:"null").')';
					$result = Db::getInstance()->Execute($query);
					if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

					//recuperation de l'id de l'image
					$query = 'SELECT `id_image` FROM `'._DB_PREFIX_.'image`	WHERE `id_product` = '.$id_product.' AND `position` = '.$position;
					$result = Db::getInstance()->ExecuteS($query);
					if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

					foreach ($result AS $row)
						$id_image = $row['id_image'];

					//insertion de l'image shop
					$query = 'INSERT INTO `'._DB_PREFIX_.'image_shop` (`id_product`, `id_image`, `id_shop`, `cover`) VALUES ('.$id_product.', '.$id_image.', '.$id_shop.', '.($is_cover?$is_cover:"null").')';
					$result = Db::getInstance()->Execute($query);
					if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));


					foreach ($languages as $language)
					{
						$image_description = "";
						if (Tools::isSubmit('legende_image_'.$language['id_lang']))
						{
							$image_description = Tools::getValue('product_file_name');
							$image_description = addslashes(strip_tags($image_description));
						}

						$query = 'INSERT INTO `'._DB_PREFIX_.'image_lang` (`id_image`, `id_lang`, `legend`) VALUES ('.$id_image.', '.$language['id_lang'].', "'.$image_description.'")';
						$result = Db::getInstance()->Execute($query);
						if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
					}

					//duplication de l'image en bons formats et noms
					$dir_image = _PS_PROD_IMG_DIR_.Image::getImgFolderStatic($id_image);
					if (!file_exists($dir_image)) {
						mkdir($dir_image, 0777, true);
					}
					$fichier_img_original = $dir_image.$id_image.'.'.$extention_image;

					rename($chemin_destination, $fichier_img_original);
					@unlink($chemin_destination);	// If rename fails, we delete

					if ($extention_image != 'jpg')
					{
						// We convert from xxx to jpg
						$this->vignette($fichier_img_original, -1,  -1, '', 90, '', 2);
					}

					// select all images type
					$query = 'SELECT `name`, `width`, `height` FROM `'._DB_PREFIX_.'image_type` WHERE `products` = 1';
					$result = Db::getInstance()->ExecuteS($query);
					if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

					foreach ($result AS $row)
					{
						$this->vignette($fichier_img_original, $row['width'], $row['height'], '-'.$row['name'], 90, '', 2);
					}
				}
			}
		}

		return $flagError;
	}

	public static function deleteImages($id_product, $id_image)
	{
		//check de si l'image etait la couverture du produit, si oui alors on decalle cette couverture
		$query = 'SELECT `cover` FROM `'._DB_PREFIX_.'image` WHERE `id_image` = '.$id_image;
		$result = Db::getInstance()->ExecuteS($query);
		if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

		$is_cover = 0;
		foreach ($result AS $row)
			$is_cover = $row['cover'];

		if ($is_cover == 1)
		{
			$query = 'SELECT `id_image` FROM `'._DB_PREFIX_.'image` WHERE `id_product` = '.$id_product.' AND `id_image` <> '.$id_image;
			$result = Db::getInstance()->ExecuteS($query);
			if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

			$next_id_image = "";
			foreach ($result AS $row) {
				$next_id_image = $row['id_image'];
				break;
			}

			if ($next_id_image != "")
			{
				$query = 'UPDATE `'._DB_PREFIX_.'image` SET `cover` = NULL WHERE `id_image` ='.$id_image;
				$result = Db::getInstance()->Execute($query);
				if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

				$query = 'UPDATE `'._DB_PREFIX_.'image` SET `cover` = 1 WHERE `id_image` ='.$next_id_image;
				$result = Db::getInstance()->Execute($query);
				if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

				$query = 'UPDATE `'._DB_PREFIX_.'image_shop` SET `cover` = NULL WHERE `id_image` ='.$id_image;
				$result = Db::getInstance()->Execute($query);
				if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

				$query = 'UPDATE `'._DB_PREFIX_.'image_shop` SET `cover` = 1 WHERE `id_image` ='.$next_id_image;
				$result = Db::getInstance()->Execute($query);
				if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
			}
		}

		//delete des images en base et reele
		$query = 'DELETE FROM `'._DB_PREFIX_.'image` WHERE `id_image` = '.$id_image;
		$result = Db::getInstance()->Execute($query);
		if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

		$query = 'DELETE FROM `'._DB_PREFIX_.'image_lang` WHERE `id_image` = '.$id_image;
		$result = Db::getInstance()->Execute($query);
		if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

		$query = 'DELETE FROM `'._DB_PREFIX_.'image_shop` WHERE `id_image` = '.$id_image;
		$result = Db::getInstance()->Execute($query);
		if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

		// select all images type
		$query = 'SELECT `name` FROM `'._DB_PREFIX_.'image_type` WHERE `products` = 1';
		$result = Db::getInstance()->ExecuteS($query);
		if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

		foreach ($result AS $row)
		{
			$image = _PS_PROD_IMG_DIR_.Image::getImgFolderStatic($id_image).$id_image.'-'.$row['name'].'.jpg';
			if (file_exists($image))
				unlink($image);
		}

		// delete original image
		$image = _PS_PROD_IMG_DIR_.Image::getImgFolderStatic($id_image).$id_image.'.jpg';
		if (file_exists($image))
			unlink($image);
	}

	/**
	 * Create 2 thumbnails from an image file: one small and one mini (Supported extensions are gif, jpg, png and bmp)
	 * With file=myfile.jpg -> myfile_small.jpg
	 *
	 * @param file Chemin du fichier image a redimensionner
	 * @param maxWidth Largeur maximum que dois faire la miniature (-1=unchanged, 160 par defaut)
	 * @param maxHeight Hauteur maximum que dois faire l'image (-1=unchanged, 120 par defaut)
	 * @param extName Extension pour differencier le nom de la vignette
	 * @param quality Quality of compression (0=worst, 100=best)
	 * @param targetformat New format of target (1,2,3,4, no change if empty)
	 * @return string Full path of thumb
	 */
	private function vignette($file, $maxWidth = 160, $maxHeight = 120, $extName='_small', $quality=50, $outdir='thumbs', $targetformat=0)
	{
		// Clean parameters
		$file=trim($file);

		// Check parameters
		if (! $file)
		{
			// Si le fichier n'a pas ete indique
			return 'Bad parameter file';
		}
		elseif (! file_exists($file))
		{
			// Si le fichier passe en parametre n'existe pas
			return "ErrorFileNotFound";
		}
		elseif(!is_numeric($maxWidth) || empty($maxWidth) || $maxWidth < -1)
		{
			// Si la largeur max est incorrecte (n'est pas numerique, est vide, ou est inferieure a 0)
			return 'Wrong value for parameter maxWidth';
		}
		elseif(!is_numeric($maxHeight) || empty($maxHeight) || $maxHeight < -1)
		{
			// Si la hauteur max est incorrecte (n'est pas numerique, est vide, ou est inferieure a 0)
			return 'Wrong value for parameter maxHeight';
		}

		$fichier = realpath($file); // Chemin canonique absolu de l'image
		$dir = dirname($file); // Chemin du dossier contenant l'image

		$infoImg = getimagesize($fichier); // Recuperation des infos de l'image
		$imgWidth = $infoImg[0]; // Largeur de l'image
		$imgHeight = $infoImg[1]; // Hauteur de l'image

		if ($maxWidth  == -1) $maxWidth=$infoImg[0]; 	// If size is -1, we keep unchanged
		if ($maxHeight == -1) $maxHeight=$infoImg[1]; 	// If size is -1 we keep unchanged

		$imgfonction='';
		switch($infoImg[2])
		{
			case 1: // IMG_GIF
				$imgfonction = 'imagecreatefromgif';
				break;
			case 2: // IMG_JPG
				$imgfonction = 'imagecreatefromjpeg';
				break;
			case 3: // IMG_PNG
				$imgfonction = 'imagecreatefrompng';
				break;
			case 4: // IMG_WBMP
				$imgfonction = 'imagecreatefromwbmp';
				break;
		}
		if ($imgfonction)
		{
			if (! function_exists($imgfonction))
			{
				// Fonctions de conversion non presente dans ce PHP
				return 'Creation de vignette impossible. Ce PHP ne supporte pas les fonctions du module GD '.$imgfonction;
			}
		}

		// On cree le repertoire contenant les vignettes
		$dirthumb = $dir.($outdir?'/'.$outdir:''); // Chemin du dossier contenant les vignettes

		// Initialisation des variables selon l'extension de l'image
		switch($infoImg[2])
		{
			case 1: // Gif
				$img = imagecreatefromgif($fichier);
				$extImg = '.gif'; // Extension de l'image
				break;
			case 2: // Jpg
				$img = imagecreatefromjpeg($fichier);
				$extImg = '.jpg'; // Extension de l'image
				break;
			case 3: // Png
				$img = imagecreatefrompng($fichier);
				$extImg = '.png';
				break;
			case 4: // Bmp
				$img = imagecreatefromwbmp($fichier);
				$extImg = '.bmp';
				break;
		}

		// Initialisation des dimensions de la vignette si elles sont superieures a l'original
		if($maxWidth > $imgWidth){ $maxWidth = $imgWidth; }
		if($maxHeight > $imgHeight){ $maxHeight = $imgHeight; }

		$whFact = $maxWidth/$maxHeight; // Facteur largeur/hauteur des dimensions max de la vignette
		$imgWhFact = $imgWidth/$imgHeight; // Facteur largeur/hauteur de l'original

		// Fixe les dimensions de la vignette
		if($whFact < $imgWhFact){
			// Si largeur determinante
			$thumbWidth = $maxWidth;
			$thumbHeight = $thumbWidth / $imgWhFact;
		} else {
			// Si hauteur determinante
			$thumbHeight = $maxHeight;
			$thumbWidth = $thumbHeight * $imgWhFact;
		}
		$thumbHeight=round($thumbHeight);
		$thumbWidth=round($thumbWidth);

		// Define target format
		if (empty($targetformat)) $targetformat=$infoImg[2];

		// Create empty image
		if ($targetformat == 1)
		{
			// Compatibilite image GIF
			$imgThumb = imagecreate($thumbWidth, $thumbHeight);
		}
		else
		{
			$imgThumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
		}

		// Activate antialiasing for better quality
		if (function_exists('imageantialias'))
		{
			imageantialias($imgThumb, true);
		}

		// This is to keep transparent alpha channel if exists (PHP >= 4.2)
		if (function_exists('imagesavealpha'))
		{
			imagesavealpha($imgThumb, true);
		}

		// Initialisation des variables selon l'extension de l'image
		switch($targetformat)
		{
			case 1: // Gif
				$trans_colour = imagecolorallocate($imgThumb, 255, 255,
				255); // On procede autrement pour le format GIF
				imagecolortransparent($imgThumb,$trans_colour);
				$extImgTarget = '.gif';
				$newquality='NU';
				break;
			case 2: // Jpg
				$trans_colour = imagecolorallocatealpha($imgThumb, 255, 255,
				255, 0);
				$extImgTarget = '.jpg';
				$newquality=$quality;
				break;
			case 3: // Png
				imagealphablending($imgThumb,false); // Pour compatibilite sur certain systeme
				$trans_colour = imagecolorallocatealpha($imgThumb, 255, 255,
						255, 127); // Keep transparent channel
				$extImgTarget = '.png';
				$newquality=$quality-100;
				$newquality=round(abs($quality-100)*9/100);
				break;
			case 4: // Bmp
				$trans_colour = imagecolorallocatealpha($imgThumb, 255, 255,
				255, 0);
				$extImgTarget = '.bmp';
				$newquality='NU';
				break;
		}
		if (function_exists("imagefill")) imagefill($imgThumb, 0, 0, $trans_colour);

		imagecopyresampled($imgThumb, $img, 0, 0, 0, 0, $thumbWidth,
				$thumbHeight, $imgWidth, $imgHeight); // Insere l'image de base redimensionnee

		$fileName = preg_replace('/(\.gif|\.jpeg|\.jpg|\.png|\.bmp)$/i','',$file); // On enleve extension quelquesoit la casse
		$fileName = basename($fileName);
		$imgThumbName = $dirthumb.'/'.$fileName.$extName.$extImgTarget; // Chemin complet du fichier de la vignette

		// Check if permission are ok
		//$fp = fopen($imgThumbName, "w");
		//fclose($fp);

		// Create image on disk
		switch($targetformat)
		{
			case 1: // Gif
				imagegif($imgThumb, $imgThumbName);
				break;
			case 2: // Jpg
				imagejpeg($imgThumb, $imgThumbName, $newquality);
				break;
			case 3: // Png
				imagepng($imgThumb, $imgThumbName, $newquality);
				break;
			case 4: // Bmp
				image2wmp($imgThumb, $imgThumbName);
				break;
		}

		// Set permissions on file
		// @chmod($imgThumbName, octdec($conf->global->MAIN_UMASK));

		// Free memory
		imagedestroy($imgThumb);

		return $imgThumbName;
	}

	public static function getTinyMce($context, $module)
	{
		$iso = $context->language->iso_code;
		$isoTinyMCE = (file_exists(_PS_ROOT_DIR_.'/js/tiny_mce/langs/'.$iso.'.js') ? $iso : 'en');
		$lang_url = _PS_JS_DIR_.'tiny_mce/langs/'.$isoTinyMCE.'.js';
		$ad = dirname($_SERVER['PHP_SELF']);

		$tiny_mce_code = "
			<script>
				var iso = '".$isoTinyMCE."';
				var ad = '".$ad."';
				var lang_url = '".$lang_url."';
				$(document).ready(function(){
					tinySetup({
						setup : function(ed) {
							ed.on('keydown', function(ed, e) {
								tinyMCE.triggerSave();
								textarea = $('#' + tinymce.activeEditor.id);
								var currId = textarea.parent('div').attr('id');
								var id = parseInt(currId.match(/([0-9]+)$/g));
								if (currId == 'resume_' + id) {
									var max = $('.counter_' + id).data('max');
									if (max != 'none')
									{
										count = tinyMCE.activeEditor.getBody().textContent.length;
										rest = max - count;
										if (rest < 0)
											$('.counter_' + id).html('<span style=\"color:red;\">".$module->l('Maximum', 'blockmysales')." '+ max +' ".$module->l('characters', 'blockmysales')." : '+rest+'</span>');
										else
											$('.counter_' + id).html(' ');
									}
								}
							});
						}
					});
					$('#price').on('change keyup paste', function() {
						if ($(this).val() > 0)
							$('#nbdaysaccessible').show();
						else
							$('#nbdaysaccessible').hide();
					});
				});
			</script>
			";

		return $tiny_mce_code;
	}

	public function getAllMessages($id)
	{
		$messages = Db::getInstance()->executeS('
			SELECT `message`
			FROM `'._DB_PREFIX_.'message`
			WHERE `id_order` = '.(int)$id.'
			ORDER BY `id_message` ASC');
		$result = array();
		foreach ($messages as $message)
			$result[] = $message['message'];

		return implode('<br/>', $result);
	}

	private function fetchTemplate($name)
	{
		$views = 'views/templates/';
		if (@filemtime(dirname(__FILE__).'/'.$views.'hook/'.$name))
			return $this->display(__FILE__, $views.'hook/'.$name);
		elseif (@filemtime(dirname(__FILE__).'/'.$views.'front/'.$name))
			return $this->display(__FILE__, $views.'front/'.$name);
		elseif (@filemtime(dirname(__FILE__).'/'.$views.'admin/'.$name))
			return $this->display(__FILE__, $views.'admin/'.$name);
	}

	/**
	 *
	 */
	public static function prestalog($message, $level=LOG_INFO)
	{
		$filelog = Configuration::get('BLOCKMYSALES_FILELOG_PATH');

		if (!empty($filelog))
		{
			$file=fopen($filelog,"a+");
			if ($file)
			{
				$ip='???';  // $ip contains information to identify computer that run the code
				if (! empty($_SERVER["REMOTE_ADDR"])) $ip=$_SERVER["REMOTE_ADDR"];          // In most cases.
				else if (! empty($_SERVER['SERVER_ADDR'])) $ip=$_SERVER['SERVER_ADDR'];     // This is when PHP session is ran inside a web server but not inside a client request (example: init code of apache)
				else if (! empty($_SERVER['COMPUTERNAME'])) $ip=$_SERVER['COMPUTERNAME'].(empty($_SERVER['USERNAME'])?'':'@'.$_SERVER['USERNAME']); // This is when PHP session is ran outside a web server, like from Windows command line (Not always defined, but usefull if OS defined it).
				else if (! empty($_SERVER['LOGNAME'])) $ip='???@'.$_SERVER['LOGNAME'];  // This is when PHP session is ran outside a web server, like from Linux command line (Not always defined, but usefull if OS defined it).

				$liblevelarray=array(LOG_ERR=>'ERROR',LOG_WARNING=>'WARN',LOG_INFO=>'INFO',LOG_DEBUG=>'DEBUG');
				$liblevel=$liblevelarray[$level];
				if (! $liblevel) $liblevel='UNDEF';

				$message=strftime("%Y-%m-%d %H:%M:%S", time())." ".sprintf("%-5s",$liblevel)." ".sprintf("%-15s",$ip)." ".$message;

				fwrite($file,$message."\n");
				fclose($file);
				// This is for log file, we do not change permissions
			}
		}
	}

	public static function formatSizeUnits($bytes, $decimal = 0)
	{
		if ($bytes >= 1073741824)
		{
			$bytes = number_format($bytes / 1073741824, (int)$decimal) . ' GB';
		}
		elseif ($bytes >= 1048576)
		{
			$bytes = number_format($bytes / 1048576, (int)$decimal) . ' MB';
		}
		elseif ($bytes >= 1024)
		{
			$bytes = number_format($bytes / 1024, (int)$decimal) . ' KB';
		}
		elseif ($bytes > 1)
		{
			$bytes = $bytes . ' bytes';
		}
		elseif ($bytes == 1)
		{
			$bytes = $bytes . ' byte';
		}
		else
		{
			$bytes = '0 bytes';
		}

		return $bytes;
	}

}
