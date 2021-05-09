<?php

class blockmysalesmanageproductModuleFrontController extends ModuleFrontController
{
	public function __construct()
	{
		parent::__construct();
		$this->context = Context::getContext();
		include_once $this->module->getLocalPath() . 'blockmysales.php';
	}

	public function setMedia()
	{
		parent::setMedia();

		// Adding TinyMce
		$this->context->controller->addJS(__PS_BASE_URI__.'modules/blockmysales/js/tinymce/tinymce.min.js');
		$this->context->controller->addJS(__PS_BASE_URI__.'modules/blockmysales/js/tinymce.inc.js');

		// Adding JS files
		$this->context->controller->addJqueryUI(array('ui.widget', 'ui.tabs', 'ui.datepicker'), 'base');

		// Adding CSS style sheet
		$this->context->controller->addCSS(__PS_BASE_URI__.'modules/blockmysales/css/global.css');
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
		$active = '';
		$products = array();
		$id_lang = (int) $this->context->language->id;
		$customer_id = (int) $this->context->customer->id;

		if (!empty($customer_id))	// If we are a logged user.
		{
			if (Tools::isSubmit('id_customer'))	$customer_id = Tools::getValue('id_customer');		// Set to a given id or to 'all'
			if (Tools::isSubmit('active'))		$active		 = Tools::getValue('active');

			if ($customer_id !== 'all') {
				$customer_id = (int) $customer_id;
			}

			$customer = BlockMySales::getCustomer($this->context->customer, $customer_id);
			// Here customer can be one record of customer (if id_customer=int)  or an array of all customers (if id_customer=all)
			if ($customer_id != 'all')
			{
				$arrayofcustomers = array($customer);
			}
			else
			{
				$arrayofcustomers = $customer;
			}

			if ($customer !== false)
			{
				if (!is_null($customer) && !empty($customer))
				{
					if ($customer_id != 'all') {
						BlockMySales::prestalog("getCustomer we got the first record address (firsname, lastname and country)");
					} else {
						BlockMySales::prestalog("getCustomer we got an array of customers with count=".count($customer));
					}

					$this->context->smarty->assign('phpself', $this->context->link->getModuleLink('blockmysales', 'manageproduct'));
					$this->context->smarty->assign('cardproduct', $this->context->link->getModuleLink('blockmysales', 'cardproduct'));
					$this->context->smarty->assign('ps_bms_templates_dir', _PS_MODULE_DIR_.'blockmysales/views/templates/front');

					$defaultLanguage = (int)(Configuration::get('PS_LANG_DEFAULT'));
					$this->context->smarty->assign('defaultLanguage', $defaultLanguage);

					$this->context->smarty->assign('upload_max_filesize', BlockMySales::formatSizeUnits(Tools::getMaxUploadSize()));

					$publisher=trim($customer['firstname'].' '.$customer['lastname']);
					$this->context->smarty->assign('publisher', $publisher);

					$company=trim($customer['company']);
					$this->context->smarty->assign('company', $company);

					$country=trim($customer['iso_code']);
					$this->context->smarty->assign('country', $country);

					$vatrate = Configuration::get('BLOCKMYSALES_VATRATE');
					$this->context->smarty->assign('vatrate', $vatrate);
					$this->context->smarty->assign('vatratepercent', $vatrate.'%');

					$vatnumber = Configuration::get('BLOCKMYSALES_VATNUMBER');
					$this->context->smarty->assign('vatnumber', $vatnumber);

					$commissioncee = Configuration::get('BLOCKMYSALES_COMMISSIONCEE');
					$this->context->smarty->assign('commissioncee', $commissioncee.'%');

					$commissionnotcee = Configuration::get('BLOCKMYSALES_COMMISSIONNOTCEE');

					$ceezoneid = Configuration::get('BLOCKMYSALES_CEEZONEID');
					$ceecountries = Country::getCountriesByZoneId($ceezoneid, $id_lang);
					foreach($ceecountries as $ceecountry)
					{
						$countries[] = $ceecountry['iso_code'];
					}
					$iscee=in_array($country, $countries);	// Countries using euros
					$commission=$iscee?$commissioncee:$commissionnotcee;
					$this->context->smarty->assign('iscee', $iscee);

					$minamountcee = Configuration::get('BLOCKMYSALES_MINAMOUNTCEE');
					$minamountnotcee = Configuration::get('BLOCKMYSALES_MINAMOUNTNOTCEE');
					$minamount=($iscee?$minamountcee:$minamountnotcee);
					$this->context->smarty->assign('minamount', $minamount);

					$taxrulegroupid = Configuration::get('BLOCKMYSALES_TAXRULEGROUPID');
					$this->context->smarty->assign('taxrulegroupid', $taxrulegroupid);

					$mindelaymonth = Configuration::get('BLOCKMYSALES_MINDELAYMONTH');
					$this->context->smarty->assign('mindelaymonth', $mindelaymonth);

					// foundationfreerate
					$foundationfeerate=$commission/100;
					$this->context->smarty->assign('foundationfeerate', $foundationfeerate);
					// totalnbofsell
					$totalnbsell=0;
					$totalnbsellpaid=0;
					// totalamount
					$totalamount=0;
					$totalamountclaimable=0;
					$totalamountforcustomer=array();
					$totalamountclaimableforcustomer=array();
					$totalamountalreadyreceivedforcustomer=array();

					// Define dateafter and datebefore
					$dateafter=null;
					$datebefore=null;

					if (Tools::isSubmit('dateafter'))	$dateafter = Tools::getValue('dateafter');
					if (Tools::isSubmit('datebefore'))	$datebefore = Tools::getValue('datebefore');

					$this->context->smarty->assign('dateafter', $dateafter);
					$this->context->smarty->assign('datebefore', $datebefore);

					// array to store all invoices already payed
					$dolistoreinvoices=array();

					// Get list of products (for the customer or for everybody)
					$query = 'SELECT p.id_product, p.reference, p.supplier_reference, p.location, p.active, p.price, p.wholesale_price, p.dolibarr_min, p.dolibarr_max, pl.name, pl.description_short, pl.link_rewrite';
					$query.= ' FROM '._DB_PREFIX_.'product as p';
					$query.= ' LEFT JOIN '._DB_PREFIX_.'product_lang as pl on pl.id_product = p.id_product AND pl.id_lang = '.$id_lang;
					$query.= ' WHERE 1 = 1';
					if ($customer_id != 'all') {
						$query.= ' AND p.reference LIKE "c'.$customer_id.'d2%"';
					} else {
						$query.= ' AND p.reference LIKE "c%d2%"';
					}
					if ($active == 'no')  $query.= ' AND active = FALSE';
					if ($active == 'yes') $query.= ' AND active = TRUE';
					$query.= ' ORDER BY pl.name';
					$result = Db::getInstance()->ExecuteS($query);
					if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

					$colorTabNbr = 1;

					if (count($result))	// If 1 or several products found for the seller or for everybody
					{
						BlockMySales::prestalog("Customer with id = ".$customer_id." has ".count($result)." products");		// $customer_id can be 'all'
						foreach ($result as $id => $values)	// Loop to show each product
						{
							$products[$id] = $values;

							if (!empty($products[$id]['dolibarr_max']))
							{
								if (!empty($products[$id]['dolibarr_min']))
								{
									$products[$id]['name'] = $products[$id]['name'] . ' ' . $products[$id]['dolibarr_min'] . ' - ' . $products[$id]['dolibarr_max'];
								}
								else
								{
									$products[$id]['name'] = $products[$id]['name'] . ' ' . $products[$id]['dolibarr_max'];
								}
							}

							$id_product = $values['id_product'];
							$ref_product = $values['reference'];
							$products[$id]['productcardlink'] = $this->context->link->getModuleLink('blockmysales', 'cardproduct') . '?id_p=' . $id_product; // product card link

							$products[$id] = array_merge($products[$id], array('price_ttc' => round($values['price'] * (100 + $vatrate) / 100, 2)));
							$products[$id]['price'] = round($values['price'], 5);

							if ($customer_id != 'all') {
								//recuperation nom fichier
								$query = 'SELECT display_filename, date_add FROM '._DB_PREFIX_.'product_download WHERE `id_product` = '.$id_product;
								$subresult = Db::getInstance()->ExecuteS($query);
								foreach ($subresult AS $subrow) {
									$products[$id] = array_merge($products[$id], array('filename' => $subrow['display_filename']));
									$products[$id] = array_merge($products[$id], array('datedeposit' => $subrow['date_add']));
								}

								//recuperation de l'image du produit
								$cover = Product::getCover($id_product);
								// get Image by id
								if (count($cover) > 0)
									$image_url = Context::getContext()->link->getImageLink($values['link_rewrite'], $cover['id_image'], 'small');
								else
									$image_url = _THEME_PROD_DIR_.'en-default-small.jpg';

								$products[$id] = array_merge($products[$id], array('image_url' => $image_url));
							} else {
								$products[$id] = array_merge($products[$id], array('filename' => 'not available in id_customer=all mode'));
								$products[$id] = array_merge($products[$id], array('datedeposit' => 'not available in id_customer=all mode'));
								$products[$id] = array_merge($products[$id], array('image_url' => 'not available in id_customer=all mode'));
							}

							if ($colorTabNbr % 2)
								$products[$id] = array_merge($products[$id], array('colorTab' => "#ffffff"));
							else
								$products[$id] = array_merge($products[$id], array('colorTab' => "#eeeeee"));

							// Calculate totalamount (total of sales) for this product
							$query = "SELECT SUM( od.product_quantity ) as nbra,
									sum( ROUND((od.product_price - od.reduction_amount) * (100 - od.reduction_percent) / 100 * GREATEST(0, (CAST(od.product_quantity AS SIGNED) - CAST(od.product_quantity_refunded AS SIGNED))) * o.valid, 2) ) as amount_ht,
									sum( ROUND((od.product_price - od.reduction_amount) * (100 - od.reduction_percent) / 100 * GREATEST(0, (CAST(od.product_quantity AS SIGNED) - CAST(od.product_quantity_refunded AS SIGNED))) * o.valid * (100 + od.tax_rate) / 100, 2) ) as amount_ttc,
									sum( GREATEST(0, (CAST(od.product_quantity AS SIGNED) - CAST(od.product_quantity_refunded AS SIGNED))) * o.valid) as qtysold,
									min( o.date_add ) as min_date";
							$query.= "	FROM "._DB_PREFIX_."order_detail as od,  "._DB_PREFIX_."orders as o
									WHERE od.product_id = ".$id_product."
									AND o.id_order = od.id_order";
							if ($dateafter)  $query.= " AND date_add >= '".$dateafter." 00:00:00'";
							if ($datebefore) $query.= " AND date_add <= '".$datebefore." 23:59:59'";
							BlockMySales::prestalog("Request ".$colorTabNbr." to count totalamount ".$query);
							//print '<!-- calculate totalamount '.$query.' -->'."\n";

							$subresult = Db::getInstance()->ExecuteS($query);
							if ($subresult === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

							$products[$id]['nbr_achats'] = 0;
							$products[$id]['nbr_amount'] = 0;
							$products[$id]['nbr_qtysold'] = 0;
							foreach ($subresult AS $subrow) {
								$products[$id]['nbr_achats'] = $subrow['nbra'];
								$products[$id]['nbr_amount'] = $subrow['amount_ht'];
								$products[$id]['nbr_qtysold'] = $subrow['qtysold'];		// qty on real sales (we exclude refund)
								if ($subrow['min_date'] && $subrow['qtysold'])
								{
									if (! empty($min_date)) $min_date = min($min_date, $subrow['min_date']);
									else $min_date=$subrow['min_date'];
								}
							}

							$id_customer_of_product = preg_replace('/d2.*$/', '', $ref_product);
							$id_customer_of_product = preg_replace('/^c/', '', $id_customer_of_product);

							$totalnbsell+=$products[$id]['nbr_achats'];
							if ($products[$id]['nbr_amount'] > 0) $totalnbsellpaid+=$products[$id]['nbr_qtysold'];
							$totalamount+=$products[$id]['nbr_amount'];

							$totalamountforcustomer[$id_customer_of_product] += $products[$id]['nbr_amount'];

							// Calculate totalamountclaimable (amount validated that a supplier can claim)
							$query = "SELECT SUM( od.product_quantity ) as nbra,
									sum( ROUND((od.product_price - od.reduction_amount) * (100 - od.reduction_percent) / 100 * (od.product_quantity - od.product_quantity_refunded) * o.valid, 2) ) as amount_ht,
									sum( ROUND((od.product_price - od.reduction_amount) * (100 - od.reduction_percent) / 100 * (od.product_quantity - od.product_quantity_refunded) * o.valid * (100 + od.tax_rate) / 100, 2) ) as amount_ttc,
									sum( (od.product_quantity - od.product_quantity_refunded) * o.valid) as qtysold,
									min( o.date_add ) as min_date
									FROM "._DB_PREFIX_."order_detail as od,  "._DB_PREFIX_."orders as o
									WHERE od.product_id = ".$id_product."
									AND o.id_order = od.id_order
									AND o.date_add <= DATE_ADD('".date("Y-m-d 23:59:59",time())."', INTERVAL - ".$mindelaymonth." MONTH)";
							if ($dateafter)  $query.= " AND date_add >= '".$dateafter." 00:00:00'";
							if ($datebefore) $query.= " AND date_add <= '".$datebefore." 23:59:59'";
							BlockMySales::prestalog("Request ".$colorTabNbr." to count totatamountclaimable ".$query);
							//print '<!-- calculate totatamountclaimable '.$query.' -->'."\n";

							$subresult = Db::getInstance()->ExecuteS($query);
							if ($subresult === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

							$nbr_amount2 = 0;
							foreach ($subresult AS $subrow) {
								$nbr_amount2 = $subrow['amount_ht'];
								if ($subrow['min_date'] && $subrow['qtysold'])
								{
									if (! empty($min_date2)) $min_date2 = min($min_date2,$subrow['min_date']);
									else $min_date2=$subrow['min_date'];
								}
							}

							$totalamountclaimable+=$nbr_amount2;

							$totalamountclaimableforcustomer[$id_customer_of_product]+=$nbr_amount2;

							$colorTabNbr++;
						}
						//var_dump($products);

						$voucherareok=true;
						$badvoucherlist=array();
						$soapclient_error=false;
						$webservice_error=false;
						$webservice_error_code=false;
						$webservice_error_label=false;

						// Check there is no bad voucher name (All voucher must be named "xxxxCidseller").
						// Having bad voucher name makes to forget to remove discounts.
						$query = "SELECT od.name, od.id_order FROM "._DB_PREFIX_."order_cart_rule as od";
						BlockMySales::prestalog("Now scan existing vouchers ".$query);

						$subresult = Db::getInstance()->ExecuteS($query);
						if ($subresult === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

						$i=0;
						foreach ($subresult AS $subrow)
						{
							$vouchername = $subrow['name'];
							$idorder = $subrow['id_order'];
							if (! preg_match('/.*C[0-9]+$/', $vouchername))
							{
								$badvoucherlist[$i]['vouchername'] = $vouchername;
								$badvoucherlist[$i]['idorder'] = $idorder;
								$voucherareok=false;
								$i++;
							}
						}

						$this->context->smarty->assign('voucherareok', $voucherareok);

						if ($voucherareok)	// Previous check test is ok, we can continue
						{
							// Calculate totalvoucher
							$query = "SELECT od.name, SUM( od.value ) as total_voucher
									FROM "._DB_PREFIX_."order_cart_rule as od,  "._DB_PREFIX_."orders as o
									WHERE o.id_order = od.id_order";
							if ($customer_id != 'all') {
								$query .= " AND od.name LIKE '%C".$customer_id."'";
							} else {
								$query .= " AND (od.name LIKE '%C__' OR od.name LIKE '%C___')";
							}
							//$query.= " AND o.date_add <= '".date("Y-m-d 23:59:59",time())."'";
							if ($dateafter)  $query.= " AND date_add >= '".$dateafter." 00:00:00'";
							if ($datebefore) $query.= " AND date_add <= '".$datebefore." 23:59:59'";
							$query .= ' GROUP BY od.name';
							BlockMySales::prestalog($query);

							$subresult = Db::getInstance()->ExecuteS($query);
							if ($subresult === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

							$totalvoucher_ttc = 0;
							foreach ($subresult AS $subrow)
							{
								$totalvoucher_ttc += $subrow['total_voucher'];

								$id_customer_of_product = preg_replace('/^.*c(\d+)$/i', '\1', $subrow['name']);
								BlockMySales::prestalog("We remove ".$subrow['total_voucher']." from totalamountforcustomer of customer id =".$id_customer_of_product);
								$totalamountforcustomer[$id_customer_of_product] -= $subrow['total_voucher'];
							}
							$totalvoucher_ht = round($totalvoucher_ttc / (100 + $vatrate) * 100, 2);


							// Calculate totalvoucherclaimable
							$query = "SELECT od.name, SUM( od.value ) as total_voucher
									FROM "._DB_PREFIX_."order_cart_rule as od,  "._DB_PREFIX_."orders as o
									WHERE o.id_order = od.id_order";
							if ($customer_id != 'all') {
								$query .= " AND od.name LIKE '%C".$customer_id."'";
							} else {
								$query .= " AND (od.name LIKE '%C__' OR od.name LIKE '%C___')";
							}
							$query.= " AND date_add <= DATE_ADD('".date("Y-m-d 23:59:59",time())."', INTERVAL - ".$mindelaymonth." MONTH)";
							if ($dateafter)  $query.= " AND date_add >= '".$dateafter." 00:00:00'";
							if ($datebefore) $query.= " AND date_add <= '".$datebefore." 23:59:59'";
							$query .= ' GROUP BY od.name';
							BlockMySales::prestalog($query);

							$subresult = Db::getInstance()->ExecuteS($query);
							if ($subresult === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

							$totalvoucherclaimable_ttc = 0;
							foreach ($subresult AS $subrow)
							{
								$totalvoucherclaimable_ttc += $subrow['total_voucher'];

								$id_customer_of_product = preg_replace('/^.*c(\d+)$/i', '\1', $subrow['name']);
								BlockMySales::prestalog("We remove ".$subrow['total_voucher']." from totalamountclaimableforcustomer of customer id =".$id_customer_of_product);
								$totalamountclaimableforcustomer[$id_customer_of_product] -= $subrow['total_voucher'];
							}
							$totalvoucherclaimable_ht = round($totalvoucherclaimable_ttc / (100 + $vatrate) * 100, 2);



							// define variables
							$mytotalamount=round($foundationfeerate*($totalamount - $totalvoucher_ht), 2);
							$mytotalamountclaimable=round($foundationfeerate*($totalamountclaimable - $totalvoucherclaimable_ht), 2);
							$alreadyreceived=0;
							$datelastpayment=0;

							$this->context->smarty->assign('totalnbsellpaid', $totalnbsellpaid);
							$this->context->smarty->assign('totalvoucher_ht', $totalvoucher_ht);
							$this->context->smarty->assign('totalamount', $totalamount);
							$this->context->smarty->assign('mytotalamount', $mytotalamount);
							$this->context->smarty->assign('mytotalamountclaimable', $mytotalamountclaimable);
							$this->context->smarty->assign('totalamountclaimable', $totalamountclaimable);
							$this->context->smarty->assign('totalvoucherclaimable_ht', $totalvoucherclaimable_ht);

							// Search third party and payments already done
							define('NUSOAP_PATH', dirname(__FILE__) . '/../../nusoap');

							require_once(NUSOAP_PATH.'/nusoap.php');        // Include SOAP
							$dolibarr_webservices_url = Configuration::get('BLOCKMYSALES_WEBSERVICES_URL');
							$authentication=array(
							'dolibarrkey' => Configuration::get('BLOCKMYSALES_WEBSERVICES_SECUREKEY'),
							'sourceapplication' => 'DOLISTORE',
							'login' => Configuration::get('BLOCKMYSALES_WEBSERVICES_LOGIN'),
							'password' => Configuration::get('BLOCKMYSALES_WEBSERVICES_PASSWORD'),
							'entity' => '');

							// Call the WebService method to find third party id from name or company name.
							$WS_DOL_URL = $dolibarr_webservices_url . '/server_thirdparty.php';
							//BlockMySales::prestalog("Create soapclient_nusoap for URL=".$WS_DOL_URL);
							$soapclient = new soapclient_nusoap($WS_DOL_URL);
							if ($soapclient)
							{
								$soapclient->soap_defencoding='UTF-8';
								$soapclient->decodeUTF8(false);
							}

							$socid = 0;
							$listofsocid = array();
							$searchwasdoneon = '';

							$dolistoreinvoicesoutput=array();
							$dolistoreinvoicesoutput[-1] = '';

							$i = 0;
							foreach($arrayofcustomers as $customer) {
								$i++;

								$publisher = trim($customer['firstname'].' '.$customer['lastname']);
								$company = trim($customer['company']);

								// Search for each $publisher / $company to get and set $socid to list of companies
								$WS_METHOD  = 'getThirdParty';

								$allparameters = array();
								$searchwasdoneonarray = array();
								$allparameters[] = array('authentication'=>$authentication,'id'=>0,'ref'=>$publisher); $searchwasdoneonarray[] = $publisher;
								if ($company)
								{
									$allparameters[] = array('authentication'=>$authentication,'id'=>0,'ref'=>$company); $searchwasdoneonarray[] =$company;
									$allparameters[] = array('authentication'=>$authentication,'id'=>0,'ref'=>$company.' ('.$publisher.')'); $searchwasdoneonarray[] = $company.' ('.$publisher.')';
									$allparameters[] = array('authentication'=>$authentication,'id'=>0,'ref'=>$publisher.' ('.$company.')'); $searchwasdoneonarray[] = $publisher.' ('.$company.')';
								}
								$searchwasdoneon .= ($searchwasdoneon ? ',<br>' : '').join(", ",$searchwasdoneonarray);

								$foundThirdpartyIntoDolibarr = false;
								foreach ($allparameters as $parameters)	// Loop on each set of search
								{
									BlockMySales::prestalog("Call method ".$WS_METHOD." #".$i." customer['id']=".$customer['id_customer']);
									$result = $soapclient->call($WS_METHOD, $parameters);
									if (! $result)
									{
										$soapclient_error=$soapclient->error_str;
									}
									else
									{
										$socid=$result['thirdparty']['id'];
										if ($socid > 0)
										{
											$foundThirdpartyIntoDolibarr = true;
											if (empty($listofsocid[$customer['id_customer']])) {
												$listofsocid[$customer['id_customer']] = $socid;
											} else {
												if ($listofsocid[$customer['id_customer']] != $socid) {
													// Found 2 different thirdparties
													$dolistoreinvoicesoutput[-1] .= 'WARNING: publisher='.$publisher.' and company='.$company.' was found twice into Dolibarr with id '.$listofsocid[$customer['id_customer']].' and '.$socid.' - <a href="/fr/module/blockmysales/manageproduct?id_customer='.$customer['id_customer'].'" target="_blank">Check</a><br>'."\n";
												}
											}
										} else {
											if ($result['result']['result_code'] == 'DUPLICATE_FOUND') {
												$dolistoreinvoicesoutput[-1] .= 'WARNING: publisher='.$publisher.' and company='.$company.' was found twice into Dolibarr when searching on '.join(',', $parameters).' - <a href="/fr/module/blockmysales/manageproduct?id_customer='.$customer['id_customer'].'" target="_blank">Check</a><br>'."\n";
												$searchwasdoneon .= '<br>WARNING: publisher='.$publisher.' and company='.$company.' was found twice into Dolibarr when searching on '.join(',', $parameters)."<br>\n";
												break;
											}
										}
									}
								}

								$this->context->smarty->assign('searchwasdoneon', $searchwasdoneon);

								if (!$foundThirdpartyIntoDolibarr) {
									if ($customer_id == 'all') {
										$dolistoreinvoicesoutput[-1] .= 'WARNING: publisher='.$publisher.' and company='.$company.' was not found into Dolibarr - <a href="/fr/module/blockmysales/manageproduct?id_customer='.$customer['id_customer'].'" target="_blank">Check</a><br>'."\n";
									}
								}
							}


							$this->context->smarty->assign('foundthirdparty', count($listofsocid));

							// Call the WebService method to get amount received
							$errorcallws=0;
							$lastdate='2000-01-01';

							// Define $dolistoreinvoices
							$WS_DOL_URL = $dolibarr_webservices_url . '/server_supplier_invoice.php';
							$WS_METHOD  = 'getSupplierInvoicesForThirdParty';
							//BlockMySales::prestalog("Create soapclient_nusoap for URL=".$WS_DOL_URL);
							$soapclient = new soapclient_nusoap($WS_DOL_URL);
							if ($soapclient)
							{
								$soapclient->soap_defencoding='UTF-8';
								$soapclient->decodeUTF8(false);
							}

							BlockMySales::prestalog("We loop on the ".count($listofsocid)." companies to get invoices");

							$i = 0;
							foreach($listofsocid as $id_customer => $socid)
							{
								$i++;

								// Make one call for each thirdparty
								$parameters = array('authentication'=>$authentication, 'id'=>$socid, 'ref'=>'');
								BlockMySales::prestalog("Call method ".$WS_METHOD." #".$i." for socid=".$socid);
								$result = $soapclient->call($WS_METHOD,$parameters);
								if (! $result)
								{
									$soapclient_error=$soapclient->error_str;
									$errorcallws++;
									BlockMySales::prestalog("Call method ".$WS_METHOD." error ".$soapclient_error);
									$dolistoreinvoicesoutput[-1] .= "ERRROR For socid = ".$socid." ".$soapclient_error."<br>";
								}
								else
								{
									if ($result['result']['result_code'] == 'OK')
									{
										BlockMySales::prestalog("Call method ".$WS_METHOD." OK");
										if (empty($result['invoices'])) {
											// Try to retreive name of publisher
											$publisher = '';
											$company = '';
											foreach($arrayofcustomers as $customer) {
												if ($id_customer == $customer['id_customer']) {
													$publisher = trim($customer['firstname'].' '.$customer['lastname']);
													$company = trim($customer['company']);
													break;
												}
											}
											if ($customer_id == 'all') {
												$dolistoreinvoicesoutput[-1] .= 'WARNING: publisher id='.$id_customer.' publisher='.$publisher.' and company='.$company.' found into Dolibarr id='.$socid.' has never claimed its balance - <a href="/fr/module/blockmysales/manageproduct?id_customer='.$id_customer.'" target="_blank">Check</a><br>'."\n";
											}
										}
										foreach($result['invoices'] as $invoice)
										{
											$dateinvoice=substr($invoice['date_invoice'], 0, 10);

											// Rule to detect invoice found is for dolistore payment back
											// More info into logs/prestalog.log
											$isfordolistore=0;
											if ((preg_match('/dolistore/i',$invoice['note_private']) || preg_match('/dolistore/i',$invoice['label']))
												&& ! preg_match('/agios/i',$invoice['ref_supplier'])
												&& ! preg_match('/frais/i',$invoice['ref_supplier'])
												&& ! preg_match('/comDolistore/i',$invoice['ref_supplier'])
												) $isfordolistore=1;

											if (! $isfordolistore)
											{
												if (count($invoice['lines']) < 1)
												{
													// TODO à traiter
													//print 'Error during call of web service '.$WS_METHOD.'. Result='.$result['result']['result_code'].'. No lines for invoice found.';
													$errorcallws++;
													break;
												}

												foreach($invoice['lines'] as $line)
												{
													if (preg_match('/dolistore/i',$line['desc'])
														&& ! preg_match('/Remboursement certificat|Remboursement domaine/i',$line['desc'])
														&& ! preg_match('/agios/i',$invoice['ref_supplier'])
														&& ! preg_match('/frais/i',$invoice['ref_supplier'])
														&& ! preg_match('/comDolistore/i',$invoice['ref_supplier'])
														)
													{
														$isfordolistore++;
													}
												}
											}

											if ($isfordolistore)
											{
												// Array of already received and paid invoices
												$dolistoreinvoices[]=array(
													'dolistore_customer_id'=>$id_customer,
													'id'=>$invoice['id'],
													'ref'=>$invoice['ref'],
													'ref_supplier'=>$invoice['ref_supplier'],
													'status'=>$invoice['status'],
													'date'=>$invoice['date_invoice'],
													'datenohour'=>$dateinvoice,
													'amount_ht'=>$invoice['total_net'],
													'amount_vat'=>$invoice['total_vat'],
													'amount_ttc'=>$invoice['total'],
													'fk_thirdparty'=>$invoice['fk_thirdparty']
												);

												// Add amount already received for this customer
												$totalamountalreadyreceivedforcustomer[$id_customer]+=$invoice['total_net'];
											}
										}
									}
									else
									{
										$dolistoreinvoicesoutput[-1] .= "ERROR Result code not OK for socid = ".$socid."<br>";
										$webservice_error=$WS_METHOD;
										$webservice_error_code=$result['result']['result_code'];
										$webservice_error_label=$result['result']['result_label'];
										$errorcallws++;
									}
								}
							}

							//$errorcallws++; // for debug

							if (empty($errorcallws))
							{
								if (count($dolistoreinvoices))
								{
									$this->context->smarty->assign('nbofsupplierinvoices', count($dolistoreinvoices));

									$sortdolistoreinvoices = $this->dol_sort_array($dolistoreinvoices,'date');

									$i=0;
									$before2013=0;
									$after2013=0;

									foreach($sortdolistoreinvoices as $item)
									{
										$dolistoreinvoicesoutput[$i]='';
										$tmpdate=preg_replace('/(\s|T)00:00:00Z/','',$item['date']);
										if ((strcmp($tmpdate, '2013-01-01') < 0) && empty($before2013))
										{
											$before2013=1;
											$dolistoreinvoicesoutput[$i].= sprintf($this->module->l('Before %s:', 'blockmysales'), Tools::displayDate('2013-01-01'))."<br>\n";
										}
										if ($before2013 && (strcmp($tmpdate, '2013-01-01') >= 0) && empty($after2013))
										{
											$after2013=1;
											$dolistoreinvoicesoutput[$i].= sprintf($this->module->l('After %s:', 'blockmysales'), Tools::displayDate('2013-01-01'))."<br>\n";
										}
										if ($tmpdate > $lastdate) $lastdate = $tmpdate;
										$dolistoreinvoicesoutput[$i].= $this->module->l('Date: ', 'blockmysales');
										$dolistoreinvoicesoutput[$i].= ' <b>'.Tools::displayDate($tmpdate).'</b> - ';
										if ((strcmp($tmpdate,'2013-01-01') < 0)) $dolistoreinvoicesoutput[$i].= ' <b>'.$item['amount_ttc'].$this->module->l('€ incl tax', 'blockmysales');
										else $dolistoreinvoicesoutput[$i].= ' <b>'.$item['amount_ht'].$this->module->l('€ excl tax', 'blockmysales');
										$dolistoreinvoicesoutput[$i].= '</b>';
										if ($item['ref_supplier'])
										{
											$dolistoreinvoicesoutput[$i].= ' - '.$this->module->l('Supplier ref: ', 'blockmysales');
											$dolistoreinvoicesoutput[$i].= ' <b>'.$item['ref_supplier'].'</b>';
										}
										if ($item['status'] != 2) $dolistoreinvoicesoutput[$i].= ' - '.$this->module->l('Payment in process', 'blockmysales');
										if ($item['ref'] || $customer_id == 'all')
										{
											$dolistoreinvoicesoutput[$i].= ' <img title="';
											$dolistoreinvoicesoutput[$i].= $this->module->l('Ref Dolibarr -> Invoice: ', 'blockmysales');
											$dolistoreinvoicesoutput[$i].= ' '.$item['ref'];
											$dolistoreinvoicesoutput[$i].= ' - ';
											$dolistoreinvoicesoutput[$i].= $this->module->l('Supplier: ', 'blockmysales');
											$dolistoreinvoicesoutput[$i].= ' '.$item['fk_thirdparty'];
											$dolistoreinvoicesoutput[$i].= '" src="/img/admin/asterisk.gif">';
										}

										$dolistoreinvoicesoutput[$i].= '<br>'."\n";
										if (strcmp($tmpdate,'2013-01-01') < 0) $alreadyreceived+=$item['amount_ttc'];
										else $alreadyreceived+=$item['amount_ht'];

										$i++;
									}

									$this->context->smarty->assign('alreadyreceived', $alreadyreceived);
								}
							}

							// Complete log with amount to claim per company
							if ($customer_id == 'all') {
								foreach($totalamountforcustomer as $id_customer_of_product => $value) {
									$tmpmessage= 'OK Customer with id '.$id_customer_of_product.' in dolistore has sold for '.$totalamountclaimableforcustomer[$id_customer_of_product].' ('.$value.' in 1 month)';
									$tmpmessage.= '. Can ask '.round(($foundationfeerate * $totalamountclaimableforcustomer[$id_customer_of_product]) - $totalamountalreadyreceivedforcustomer[$id_customer_of_product], 2);
									$tmpmessage.= ' ('.round(($foundationfeerate * $totalamountforcustomer[$id_customer_of_product]) - $totalamountalreadyreceivedforcustomer[$id_customer_of_product], 2).' in 1 month) <a href="/fr/module/blockmysales/manageproduct?id_customer='.$id_customer_of_product.'" target="_blank">Check</a>';
									BlockMySales::prestalog($tmpmessage);
									$dolistoreinvoicesoutput[-1] .= $tmpmessage."<br>\n";
								}
							}

							$this->context->smarty->assign('dolistoreinvoiceslines', $dolistoreinvoicesoutput);

							//$alreadyreceived=0; // for debug
							$this->context->smarty->assign('soapclient_error', $soapclient_error);
							$this->context->smarty->assign('webservice_error', $webservice_error);
							$this->context->smarty->assign('webservice_error_code', $webservice_error_code);
							$this->context->smarty->assign('webservice_error_label', $webservice_error_label);

							if (empty($dateafter) && empty($datebefore))
							{
								$showremaintoreceive=true;

								$remaintoreceivein2month = round($mytotalamount - $alreadyreceived, 2);
								$this->context->smarty->assign('remaintoreceivein2month', $remaintoreceivein2month);

								$remaintoreceive = round($mytotalamountclaimable - $alreadyreceived, 2);
								$this->context->smarty->assign('remaintoreceive', $remaintoreceive);

								$firstdayofmonth=date("Y-m",time()).'-01';
								$this->context->smarty->assign('firstdayofmonth', $firstdayofmonth);

								if ((strcmp($lastdate,$firstdayofmonth) > 0) && $customer_id != 'all') $showremaintoreceive=false;
							}
							else
							{
								$showremaintoreceive=false;
							}

							$this->context->smarty->assign('showremaintoreceive', $showremaintoreceive);
						}
						else
						{
							$this->context->smarty->assign('badvoucherlist', $badvoucherlist);
						}
					}

					/*
					 * New product
					 */

					$nbdaysaccessible = Configuration::get('BLOCKMYSALES_NBDAYSACCESSIBLE');

					$newproduct=array(
						'price' => (Tools::isSubmit('price') ? Tools::getValue('price') : 0),
						'active' => 0,
						'file_name' => Tools::getValue('product_file_name'),
						'module_version' => Tools::getValue('module_version'),
						'dolibarr_min' => Tools::getValue('dolibarr_min'),
						'dolibarr_max' => Tools::getValue('dolibarr_max'),
						'dolibarr_core_include' => (Tools::isSubmit('dolibarr_core_include') ? Tools::getValue('dolibarr_core_include') : 0),
						'dolibarr_disable_info' => Tools::getValue('dolibarr_disable_info'),
						'nb_days_accessible' => (Tools::isSubmit('nb_days_accessible') ? Tools::getValue('nb_days_accessible') : (!empty($nbdaysaccessible) ? $nbdaysaccessible : 365)),
						'product_name' => array(),
						'resume' => array(),
						'keywords' => array(),
						'description' => array()
					);
					$languageTAB=array();
					$file=array(
						'product_file_path' => Tools::getValue('product_file_path'),
						'upload' => 1,
						'errormsg' => null
					);
					$product_file_name=Tools::getValue('product_file_name');
					$virtual_product_file=null;
					$create_flag=false;

					$this->context->smarty->assign('product_id', false);
					$this->context->smarty->assign('PS_PRODUCT_SHORT_DESC_LIMIT', Configuration::get('PS_PRODUCT_SHORT_DESC_LIMIT') ? Configuration::get('PS_PRODUCT_SHORT_DESC_LIMIT') : 400);

					$action=false;
					if (Tools::isSubmit('action'))	$action = Tools::getValue('action');
					$this->context->smarty->assign('action', $action);

					$tab=(($action == 'create' || $action == 'uploadfile') ? 'submit' : (Tools::isSubmit('tab') ? Tools::getValue('tab') : false));
					$this->context->smarty->assign('tab', $tab);

					$cancel=false;
					if (Tools::isSubmit('cancel'))	$cancel = true;
					$this->context->smarty->assign('cancel', $cancel);

					$this->context->smarty->assign('customer', $customer);

					$languages = Language::getLanguages();

					foreach ($languages as $key => $language) {

						$languageTAB[$key]['id_lang'] = $language['id_lang'];
						$languageTAB[$key]['name'] = $language['name'];
						$languageTAB[$key]['iso_code'] = $language['iso_code'];
						$languageTAB[$key]['img'] = _THEME_LANG_DIR_.$language['id_lang'].'.jpg';

						$newproduct['product_name'][$language['id_lang']] 	= trim(Tools::getValue('product_name_l'.$language['id_lang']));
						$newproduct['resume'][$language['id_lang']]			= Tools::getValue('resume_'.$language['id_lang']);
						$newproduct['keywords'][$language['id_lang']] 		= trim(Tools::getValue('keywords_'.$language['id_lang']));
						$newproduct['description'][$language['id_lang']] 	= Tools::getValue('description_'.$language['id_lang']);
					}

					// Define default value
					if (Tools::isSubmit('id_p') && is_numeric(Tools::getValue('id_p')))
					{
						// Get product id
						$query = 'SELECT p.id_product, p.price, p.dolibarr_disable_info, p.module_version, p.dolibarr_min, p.dolibarr_max, p.dolibarr_core_include, pl.description, pl.description_short, pl.meta_description, pl.meta_keywords, pl.meta_title, pl.name, pl.id_lang';
						$query.= ' FROM '._DB_PREFIX_.'product as p, '._DB_PREFIX_.'product_lang as pl, '._DB_PREFIX_.'lang as l WHERE l.id_lang = pl.id_lang AND p.id_product = pl.id_product AND p.id_product = '.((int) Tools::getValue('id_p'));
						$result = Db::getInstance()->ExecuteS($query);
						if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

						foreach ($result AS $row)
						{
							$newproduct['product_name'][$row['id_lang']] 	= $row['name'];
							$newproduct['keywords'][$row['id_lang']] 		= $row['meta_keywords'];
							$newproduct['resume'][$row['id_lang']] 			= $row['description_short'];
							$newproduct['description'][$row['id_lang']] 	= $row['description'];
							$newproduct['price'] 							= round($row['price'], 2);
							$newproduct['module_version'] 					= $row['module_version'];
							$newproduct['dolibarr_min'] 					= $row['dolibarr_min'];
							$newproduct['dolibarr_max'] 					= $row['dolibarr_max'];
							$newproduct['dolibarr_core_include'] 			= $row['dolibarr_core_include'];
							$newproduct['dolibarr_disable_info'] 			= $row['dolibarr_disable_info'];
						}
					}

					//var_dump($newproduct);

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

						foreach ($categories AS $categorie) {
							$categories_checkbox = Tools::getValue('categories_checkbox_'.$categorie['id_category']);
							if (! empty($categories_checkbox) && $categories_checkbox == 1 && $categorie['id_category'] != 1) {
								$newproduct['categories_checkbox'][$categorie['id_category']] = true;
							}
						}
					}

					/*
					 * Action
					 */

					if ($action == "uploadfile")
					{
						$blockmysales = new BlockMySales();
						$file = $blockmysales->checkZipFile();

						if (Tools::isSubmit('product_file_name'))
						{
							$product_file_name = Tools::getValue('product_file_name');
						}

						if (! empty($_FILES['virtual_product_file']['name']))
						{
							$product_file_name = $_FILES['virtual_product_file']['name'];
							$virtual_product_file=true;
						}
					}
					else if ($action == "create" && !$cancel)
					{
						$blockmysales = new BlockMySales();
						$create_flag = $blockmysales->addProduct($customer);
						if ($create_flag > 0)
						{
							$url = $this->context->link->getModuleLink('blockmysales', 'cardproduct');
							header('Location: '.$url.'?id_customer='.$customer_id.'&id_p='.$create_flag.'&tab=modify');
							exit;
						}
						else
						{
							$this->context->smarty->assign('create_errors', $this->module->displayError($blockmysales->create_errors));
						}

						$this->context->smarty->assign('create_flag', $create_flag);
					}

					$descriptions = json_decode(Configuration::get('BLOCKMYSALES_DESCRIPTIONS'), true);
					foreach ($descriptions as $key => $value)
					{
						$default_descriptions[$key] = str_replace('{$publisher}', $publisher, $value);
						$default_descriptions[$key] = str_replace('{$product_file_name}', (!empty($product_file_name)?$product_file_name:'fichiermodule.zip'), $default_descriptions[$key]);
						if (!empty($newproduct['description']))
							$newproduct['description'][$key] = str_replace('fichiermodule.zip', (!empty($product_file_name)?$product_file_name:'fichiermodule.zip'), $newproduct['description'][$key]);
					}
					$this->context->smarty->assign('default_descriptions', $default_descriptions);

					$this->context->smarty->assign('languages', $languageTAB);
					$this->context->smarty->assign('tinymce',BlockMySales::getTinyMce($this->context, $this->module));
					$this->context->smarty->assign('product', $newproduct);
					$this->context->smarty->assign('categories', $categories);
					$this->context->smarty->assign('file', $file);
					$this->context->smarty->assign('product_file_name', $product_file_name);
					$this->context->smarty->assign('virtual_product_file', $virtual_product_file);
					$this->context->smarty->assign('taxes', Tax::getTaxes($id_lang));

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

			$this->context->smarty->assign('products', $products);
			$this->setTemplate('manage_product.tpl');
		}
		else
		{
			$url = $this->context->link->getModuleLink('blockmysales', 'manageproduct');
			$prefix = $this->context->link->getPageLink('authentication');
			header('Location: '.$prefix.'?back='.urlencode($url));
			exit;
		}
	}

	/**
	 * 	Advanced sort array by second index function, which produces ascending (default)
	 *  or descending output and uses optionally natural case insensitive sorting (which
	 *  can be optionally case sensitive as well).
	 *
	 *  @param      array		$array      		Array to sort
	 *  @param      string		$index				Key in array to use for sorting criteria
	 *  @param      int			$order				Sort order
	 *  @param      int			$natsort			1=use "natural" sort (natsort), 0=use "standard sort (asort)
	 *  @param      int			$case_sensitive		1=sort is case sensitive, 0=not case sensitive
	 *  @return     array							Sorted array
	 */
	private function dol_sort_array(&$array, $index, $order='asc', $natsort=0, $case_sensitive=0)
	{
		// Clean parameters
		$order=strtolower($order);

		$sizearray=count($array);
		if (is_array($array) && $sizearray>0)
		{
			foreach(array_keys($array) as $key) $temp[$key]=$array[$key][$index];
			if (!$natsort) ($order=='asc') ? asort($temp) : arsort($temp);
			else
			{
				($case_sensitive) ? natsort($temp) : natcasesort($temp);
				if($order!='asc') $temp=array_reverse($temp,TRUE);
			}
			foreach(array_keys($temp) as $key) (is_numeric($key))? $sorted[]=$array[$key] : $sorted[$key]=$array[$key];
			return $sorted;
		}
		return $array;
	}
}
