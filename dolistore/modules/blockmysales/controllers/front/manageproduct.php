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
		require_once dirname(__FILE__) . '/../../config.inc.php';

		// Get env variables
		$active = '';
		$products = array();
		$id_langue_en_cours = (int)$this->context->language->id;
		$customer_id = (int)$this->context->customer->id;

		if (!empty($customer_id))
		{
			if (Tools::isSubmit('id_customer'))	$customer_id = Tools::getValue('id_customer');
			if (Tools::isSubmit('active'))		$active		 = Tools::getValue('active');

			$customer = BlockMySales::getCustomer($this->context->customer, $customer_id);

			if ($customer !== false)
			{
				if (!is_null($customer) && !empty($customer))
				{
					$this->context->smarty->assign('phpself', $this->context->link->getModuleLink('blockmysales', 'manageproduct'));
					$this->context->smarty->assign('ps_bms_templates_dir', _PS_MODULE_DIR_.'blockmysales/views/templates/front');

					$publisher=trim($customer['firstname'].' '.$customer['lastname']);
					$this->context->smarty->assign('publisher', $publisher);

					$company=trim($customer['company']);
					$this->context->smarty->assign('company', $company);

					$country=trim($customer['iso_code']);
					$this->context->smarty->assign('country', $country);

					$iscee=in_array($country,array('AT','BE','IT','DE','DK','ES','FR','GB','GR','LU','MC','NL','PO','PT'));	// Countries using euros
					$commission=$iscee?$commissioncee:$commissionnotcee;
					$this->context->smarty->assign('iscee', $iscee);

					$minamount=($iscee?$minamountcee:$minamountnotcee);
					$this->context->smarty->assign('minamount', $minamount);

					// foundationfreerate
					$foundationfeerate=$commission/100;
					$this->context->smarty->assign('foundationfeerate', $foundationfeerate);
					// totalnbofsell
					$totalnbsell=0;
					$totalnbsellpaid=0;
					// totalamount
					$totalamount=0;
					$totalamountclaimable=0;

					 // Define dateafter and datebefore
					$dateafter=null;
					$datebefore=null;

					if (Tools::isSubmit('dateafter'))	$dateafter = Tools::getValue('dateafter');
					if (Tools::isSubmit('datebefore'))	$datebefore = Tools::getValue('datebefore');

					$this->context->smarty->assign('dateafter', $dateafter);
					$this->context->smarty->assign('datebefore', $datebefore);

					// array to store all invoices already payed
					$dolistoreinvoices=array();

					// Get list of products
					$query = 'SELECT p.id_product, p.reference, p.supplier_reference, p.location, p.active, p.price, p.wholesale_price, pl.name, pl.description_short, pl.link_rewrite';
					$query.= ' FROM '._DB_PREFIX_.'product as p';
					$query.= ' LEFT JOIN '._DB_PREFIX_.'product_lang as pl on pl.id_product = p.id_product AND pl.id_lang = '.$id_langue_en_cours;
					$query.= ' WHERE 1 = 1';
					if ($customer_id != 'all') $query.= ' AND p.reference LIKE "c'.$customer_id.'d2%"';
					if ($active == 'no')  $query.= ' AND active = FALSE';
					if ($active == 'yes') $query.= ' AND active = TRUE';
					$query.= ' ORDER BY pl.name';
					$result = Db::getInstance()->ExecuteS($query);
					if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

					$colorTabNbr = 1;

					if (count($result))
					{
						foreach ($result as $id => $values)	// For each product
						{
							$products[$id] = $values;

							$id_product = $values['id_product'];
							$products[$id]['productcardlink'] = $this->context->link->getModuleLink('blockmysales', 'cardproduct') . '?id_p=' . $id_product; // product card link

							$products[$id] = array_merge($products[$id], array('price_ttc' => round($values['price'] * (100 + $vatrate) / 100, 2)));
							$products[$id]['price'] = round($values['price'], 5);

							//recuperation nom fichier
							$query = 'SELECT display_filename, date_add FROM '._DB_PREFIX_.'product_download WHERE `id_product` = '.$id_product;
							$subresult = Db::getInstance()->ExecuteS($query);
							$filename = "";
							$datedeposit=0;
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

							if ($colorTabNbr%2)
								$products[$id] = array_merge($products[$id], array('colorTab' => "#ffffff"));
							else
								$products[$id] = array_merge($products[$id], array('colorTab' => "#eeeeee"));

							// Calculate totalamount for this product
							$query = "SELECT SUM( od.product_quantity ) as nbra,
									sum( ROUND((od.product_price - od.reduction_amount) * (100 - od.reduction_percent) / 100 * (od.product_quantity - od.product_quantity_refunded) * o.valid, 2) ) as amount_ht,
									sum( ROUND((od.product_price - od.reduction_amount) * (100 - od.reduction_percent) / 100 * (od.product_quantity - od.product_quantity_refunded) * o.valid * (100 + od.tax_rate) / 100, 2) ) as amount_ttc,
									sum( (od.product_quantity - od.product_quantity_refunded) * o.valid) as qtysold,
									min( o.date_add ) as min_date";
							$query.= "	FROM "._DB_PREFIX_."order_detail as od,  "._DB_PREFIX_."orders as o
									WHERE od.product_id = ".$id_product."
									AND o.id_order = od.id_order";
							if ($dateafter)  $query.= " AND date_add >= '".$dateafter." 00:00:00'";
							if ($datebefore) $query.= " AND date_add <= '".$datebefore." 23:59:59'";
							//prestalog("Request to count totalamount ".$query);
							//print '<!-- calculate totalamount '.$query.' -->'."\n";

							$subresult = Db::getInstance()->ExecuteS($query);
							if ($subresult === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

							$products[$id]['nbr_achats'] = 0;
							$products[$id]['nbr_amount'] = 0;
							$products[$id]['nbr_qtysold'] = 0;
							foreach ($subresult AS $subrow) {
								$products[$id]['nbr_achats'] = $subrow['nbra'];
								$products[$id]['nbr_amount'] = $subrow['amount_ht'];
								$products[$id]['nbr_qtysold'] = $subrow['qtysold'];
								if ($subrow['min_date'] && $subrow['qtysold'])
								{
									if (! empty($min_date)) $min_date = min($min_date, $subrow['min_date']);
									else $min_date=$subrow['min_date'];
								}
							}

							$totalnbsell+=$products[$id]['nbr_achats'];
							if ($products[$id]['nbr_amount'] > 0) $totalnbsellpaid+=$products[$id]['nbr_qtysold'];
							$totalamount+=$products[$id]['nbr_amount'];

							// Calculate totalamountclaimable (amount validated supplier can claim)
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
							//prestalog("Request to count totatamountclaimable ".$query);
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
						//prestalog($query);

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

						if ($voucherareok)
						{
							// Calculate totalvoucher
							$query = "SELECT SUM( od.value ) as total_voucher
									FROM "._DB_PREFIX_."order_cart_rule as od,  "._DB_PREFIX_."orders as o
									WHERE od.name LIKE '%C".($customer_id != 'all' ? $customer_id : "%")."'
									AND o.id_order = od.id_order";
							//$query.= " AND o.date_add <= '".date("Y-m-d 23:59:59",time())."'";
							if ($dateafter)  $query.= " AND date_add >= '".$dateafter." 00:00:00'";
							if ($datebefore) $query.= " AND date_add <= '".$datebefore." 23:59:59'";
							//prestalog($query);

							$subresult = Db::getInstance()->ExecuteS($query);
							if ($subresult === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

							$totalvoucher_ttc = 0;
							foreach ($subresult AS $subrow)
							{
								$totalvoucher_ttc += $subrow['total_voucher'];
							}
							$totalvoucher_ht = round($totalvoucher_ttc / (100 + $vatrate) * 100, 2);


							// Calculate totalvoucherclaimable
							$query = "SELECT SUM( od.value ) as total_voucher
									FROM "._DB_PREFIX_."order_cart_rule as od,  "._DB_PREFIX_."orders as o
									WHERE od.name LIKE '%C".($customer_id != 'all' ? $customer_id : "%")."'
									AND o.id_order = od.id_order";
							$query.= " AND date_add <= DATE_ADD('".date("Y-m-d 23:59:59",time())."', INTERVAL - ".$mindelaymonth." MONTH)";
							if ($dateafter)  $query.= " AND date_add >= '".$dateafter." 00:00:00'";
							if ($datebefore) $query.= " AND date_add <= '".$datebefore." 23:59:59'";
							//prestalog($query);

							$subresult = Db::getInstance()->ExecuteS($query);
							if ($subresult === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

							$totalvoucherclaimable_ttc = 0;
							foreach ($subresult AS $subrow)
							{
								$totalvoucherclaimable_ttc += $subrow['total_voucher'];
							}
							$totalvoucherclaimable_ht = round($totalvoucherclaimable_ttc / (100 + $vatrate) * 100, 2);

							// define variables
							$mytotalamount=round($foundationfeerate*($totalamount - $totalvoucher_ht),2);
							$mytotalamountclaimable=round($foundationfeerate*($totalamountclaimable - $totalvoucherclaimable_ht),2);
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
							$dolibarr_main_url_root='http://asso.dolibarr.org/dolibarr/';
							$authentication=array(
									'dolibarrkey'=>$wsdolibarrkey,
									'sourceapplication'=>'DOLISTORE',
									'login'=>$wslogin,
									'password'=>$wspass,
									'entity'=>'');

							$socid=0;
							$foundthirdparty=false;

							// Call the WebService method to find third party id from name or company name.
							$WS_DOL_URL = $dolibarr_main_url_root.'/webservices/server_thirdparty.php';
							//prestalog("Create soapclient_nusoap for URL=".$WS_DOL_URL);
							$soapclient = new soapclient_nusoap($WS_DOL_URL);
							if ($soapclient)
							{
								$soapclient->soap_defencoding='UTF-8';
								$soapclient->decodeUTF8(false);
							}

							if ($customer_id != 'all')
							{
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
								$searchwasdoneon=join(", ",$searchwasdoneonarray);
								
								$this->context->smarty->assign('searchwasdoneon', $searchwasdoneon);

								if (! $foundthirdparty)
								{
									foreach ($allparameters as $parameters)
									{
										$result = $soapclient->call($WS_METHOD, $parameters);
										if (! $result)
										{
											$soapclient_error=$soapclient->error_str;
										}
										else
										{
											$socid=$result['thirdparty']['id'];
											if ($socid)
											{
												$foundthirdparty=true;
												break;
											}
										}
									}
								}
							}
							else
							{
								$foundthirdparty=true;
								$socid='all';
							}

							$this->context->smarty->assign('foundthirdparty', $foundthirdparty);

							// Call the WebService method to get amount received
							$errorcallws=0;
							if ($socid > 0 || $socid == 'all')
							{
								// Define $dolistoreinvoices
								$WS_DOL_URL = $dolibarr_main_url_root.'/webservices/server_supplier_invoice.php';
								$WS_METHOD  = 'getSupplierInvoicesForThirdParty';
								//prestalog("Create soapclient_nusoap for URL=".$WS_DOL_URL);
								$soapclient = new soapclient_nusoap($WS_DOL_URL);
								if ($soapclient)
								{
									$soapclient->soap_defencoding='UTF-8';
									$soapclient->decodeUTF8(false);
								}
								$parameters = array('authentication'=>$authentication,'id'=>$socid,'ref'=>'');
								//prestalog("Call method ".$WS_METHOD." for socid=".$socid);
								$result = $soapclient->call($WS_METHOD,$parameters);
								if (! $result)
								{
									$soapclient_error=$soapclient->error_str;
									$errorcallws++;
								}
								else
								{
									if ($result['result']['result_code'] == 'OK')
									{
										foreach($result['invoices'] as $invoice)
										{
											$dateinvoice=substr($invoice['date_invoice'],0,10);

											// Rule to detect invoice found is for dolistore payment back
											// More info into logs/prestalog.log
											$isfordolistore=0;
											if (preg_match('/dolistore/i',$invoice['note_private'])
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
												$dolistoreinvoices[]=array(
														'id'=>$invoice['id'],
														'ref'=>$invoice['ref'],
														'ref_supplier'=>$invoice['ref_supplier'],
														'status'=>$invoice['status'],
														'date'=>$invoice['date_invoice'],
														'amount_ht'=>$invoice['total_net'],
														'amount_vat'=>$invoice['total_vat'],
														'amount_ttc'=>$invoice['total'],
														'fk_thirdparty'=>$invoice['fk_thirdparty']
												);
											}
										}
									}
									else
									{
										$webservice_error=$WS_METHOD;
										$webservice_error_code=$result['result']['result_code'];
										$webservice_error_label=$result['result']['result_label'];
										$errorcallws++;
									}
								}

								//$errorcallws++; // for debug
								$dolistoreinvoiceslines=array();
								$lastdate='2000-01-01';

								if (empty($errorcallws))
								{
									if (count($dolistoreinvoices))
									{
										$sortdolistoreinvoices = $this->dol_sort_array($dolistoreinvoices,'date');

										$i=0;
										$before2013=0;

										foreach($sortdolistoreinvoices as $item)
										{
											$dolistoreinvoiceslines[$i]='';
											$tmpdate=preg_replace('/(\s|T)00:00:00Z/','',$item['date']);
											if ((strcmp($tmpdate, '2013-01-01') < 0) && empty($before2013))
											{
												$before2013=1;
												$dolistoreinvoiceslines[$i].= sprintf($this->module->l('Before %s:', 'blockmysales'), Tools::displayDate('2013-01-01'))."<br>";
											}
											if ($before2013 && (strcmp($tmpdate, '2013-01-01') >= 0) && empty($after2013))
											{
												$after2013=1;
												$dolistoreinvoiceslines[$i].= sprintf($this->module->l('After %s:', 'blockmysales'), Tools::displayDate('2013-01-01'))."<br>";
											}
											if ($tmpdate > $lastdate) $lastdate = $tmpdate;
											$dolistoreinvoiceslines[$i].= $this->module->l('Date: ', 'blockmysales');
											$dolistoreinvoiceslines[$i].= ' <b>'.Tools::displayDate($tmpdate).'</b> - ';
											if ((strcmp($tmpdate,'2013-01-01') < 0)) $dolistoreinvoiceslines[$i].= ' <b>'.$item['amount_ttc'].$this->module->l('€ incl tax', 'blockmysales');
											else $dolistoreinvoiceslines[$i].= ' <b>'.$item['amount_ht'].$this->module->l('€ excl tax', 'blockmysales');
											$dolistoreinvoiceslines[$i].= '</b>';
											if ($item['ref_supplier'])
											{
												$dolistoreinvoiceslines[$i].= ' - '.$this->module->l('Supplier ref: ', 'blockmysales');
												$dolistoreinvoiceslines[$i].= ' <b>'.$item['ref_supplier'].'</b>';
											}
											if ($item['status'] != 2) $dolistoreinvoiceslines[$i].= ' - '.$this->module->l('Payment in process', 'blockmysales');
											if ($item['ref'] || $customer_id == 'all')
											{
												$dolistoreinvoiceslines[$i].= ' <img title="';
												$dolistoreinvoiceslines[$i].= $this->module->l('Ref Dolibarr -> Invoice: ', 'blockmysales');
												$dolistoreinvoiceslines[$i].= ' '.$item['ref'];
												$dolistoreinvoiceslines[$i].= ' - ';
												$dolistoreinvoiceslines[$i].= $this->module->l('Supplier: ', 'blockmysales');
												$dolistoreinvoiceslines[$i].= ' '.$item['fk_thirdparty'];
												$dolistoreinvoiceslines[$i].= '" src="/img/admin/asterisk.gif">';
											}

											$dolistoreinvoiceslines[$i].= '<br>'."\n";
											if (strcmp($tmpdate,'2013-01-01') < 0) $alreadyreceived+=$item['amount_ttc'];
											else $alreadyreceived+=$item['amount_ht'];

											$i++;
										}

										$this->context->smarty->assign('alreadyreceived', $alreadyreceived);
									}
								}

								$this->context->smarty->assign('dolistoreinvoiceslines', $dolistoreinvoiceslines);
							}
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
								$showremaintoreceive=false;

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

					$newproduct=array(
							'price' => (Tools::isSubmit('price') ? Tools::getValue('price') : 0),
							'active' => 0,
							'file_name' => Tools::getValue('product_file_name'),
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

					//var_dump($newproduct);

					$categories = Category::getSimpleCategories($id_langue_en_cours);
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

					$blockmysales = new BlockMySales();

					/*
					 * Action
					 */

					if ($action == "uploadfile")
					{
						$file = $blockmysales->checkZipFile();

						if (Tools::isSubmit('product_file_name'))
							$product_file_name = Tools::getValue('product_file_name');

						if (! empty($_FILES['virtual_product_file']['name']))
						{
							$product_file_name = $_FILES['virtual_product_file']['name'];
							$virtual_product_file=true;
						}
					}
					else if ($action == "create" && !$cancel)
					{
						$create_flag = $blockmysales->addProduct($customer, $languageTAB);
						if ($create_flag > 0)
						{
							$url = $this->context->link->getModuleLink('blockmysales', 'cardproduct');
							header('Location: '.$url.'?id_p='.$create_flag.'&tab=modify');
							exit;
						}
					}

					$this->context->smarty->assign('create_flag', $create_flag);
					$this->context->smarty->assign('languages', $languageTAB);
					$this->context->smarty->assign('tinymce',BlockMySales::getTinyMce($this->context));
					$this->context->smarty->assign('product', $newproduct);
					$this->context->smarty->assign('categories', $categories);
					$this->context->smarty->assign('file', $file);
					$this->context->smarty->assign('product_file_name', $product_file_name);
					$this->context->smarty->assign('virtual_product_file', $virtual_product_file);
					$this->context->smarty->assign('taxes', Tax::getTaxes($id_langue_en_cours));

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
