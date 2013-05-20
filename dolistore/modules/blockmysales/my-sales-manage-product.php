<?php

//error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

/* SSL Management */
$useSSL = true;

require_once('config.inc.php');
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
//include(dirname(__FILE__).'/../../init.php');
include(dirname(__FILE__).'/lib.php');


// Get env variables
$id_langue_en_cours = $cookie->id_lang;
$customer_id = $cookie->id_customer;
$publisher=trim($cookie->customer_firstname.' '.$cookie->customer_lastname);
if (! empty($_GET["id_customer"]))  $customer_id=$_GET["id_customer"];
if (! empty($_POST["id_customer"])) $customer_id=$_POST["id_customer"];
$admin=0;

// Check if current user is also an employee with admin user
$query = "SELECT id_employee, id_profile, email, active FROM "._DB_PREFIX_."employee
		WHERE lastname = '".addslashes($cookie->customer_lastname)."' and firstname = '".addslashes($cookie->customer_firstname)."'";
$subresult = Db::getInstance()->ExecuteS($query);
if (empty($subresult[0]['id_employee']))	// If not an admin user
{
	if ($customer_id != $cookie->id_customer)
	{
		print 'Error, you need to be an admin user to view other customers/suppliers.';
		die();
	}
}
else $admin=1;

// Get publisher, company and country
$query = "SELECT c.id_customer, c.firstname, c.lastname, c.email, c.optin, c.active, c.deleted, a.company, a.city, a.id_country, co.iso_code";
$query.= " FROM "._DB_PREFIX_."customer as c";
$query.= " LEFT JOIN "._DB_PREFIX_."address as a ON a.id_customer = c.id_customer AND a.deleted = 0";
$query.= " LEFT JOIN "._DB_PREFIX_."country as co ON a.id_country = co.id_country";
if ($customer_id != 'all') $query.= " WHERE c.id_customer = ".$customer_id;
$subresult = Db::getInstance()->ExecuteS($query);

if (! empty($subresult[0]['active']))
{
	$publisher=trim($subresult[0]['firstname'].' '.$subresult[0]['lastname']);
	$company=trim($subresult[0]['company']);
	$country=trim($subresult[0]['iso_code']);
}
else
{
	print 'Customer with id '.$customer_id.' can\'t be found.';
	die();
}


prestalog("Acces to page my-sales-manage-product.php ".$publisher." ".$company);

$languages = Language::getLanguages();

$x = 0;
foreach ($languages AS $language) {
	$languageTAB[$x]['id_lang'] = $language['id_lang'];
	$languageTAB[$x]['name'] = $language['name'];
	$languageTAB[$x]['iso_code'] = $language['iso_code'];
	$languageTAB[$x]['img'] = '../../img/l/'.$language['id_lang'].'.jpg';

	if ($language['id_lang'] == $id_langue_en_cours)
		$iso_langue_en_cours = $language['iso_code'];

	$x++;
}

?>



<?php echo aff("<h2>Modifier mes modules/produits</h2>", "<h2>Manages my modules/plugins</h2>", $iso_langue_en_cours); ?>
<br />

<?php
$iscee=in_array($country,array('AT','BE','CH','IT','DE','DK','ES','FR','GB','LU','MC','NL','UK','PO','PT'));
$commission=$iscee?$commissioncee:$commissionnotcee;

// foundationfreerate
$foundationfeerate=$commission/100;
// totalnbofsell
$totalnbsell=0;
$totalnbsellpaid=0;
// totalamount
$totalamount=0;
$totalamountclaimable=0;
// datestart
if (empty($datestart))
{
	//print $cookie->date_add;
	//$datestart=strtotime('YY-mm-DD HH:MM:II',$cookie->date_add);
	$datestart=strtotime($cookie->date_add);
	//$datestart=mktime(0, 0, 0, 6, 1, 2010);	// If not found
	//print 'ee'.$datestart;
}

// Define dateafter and datebefore
$dateafter='';
$datebefore='';
if (! empty($_POST['dateafter']))
{
	if (preg_match('/[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]/',$_POST['dateafter'])) 
	{
		$dateafter=$_POST['dateafter'];
	}
	else $_POST['dateafter']='';
}
if (! empty($_POST['datebefore']))
{
	if (preg_match('/[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]/',$_POST['datebefore'])) 
	{
		$datebefore=$_POST['datebefore'];
	}
	else $_POST['datebefore']='';
}

// array to store all invoices already payed
$dolistoreinvoices=array();

if ($customer_id != 'all')
{
echo aff("Les statistiques sont celles des téléchargements/ventes pour les composants soumis par l'utilisateur courant:<br><b>Nom: ".$publisher.($company?"<br>Société: ".$company:"").($country?"<br>Pays: ".$country:"")."</b>",
"Statistics are for download/sells of components submited by for current user:<br><b>Name: ".$publisher.($company?"<br>Company: ".$company:"").($country?"<br>Country: ".$country:"")."</b>",
$iso_langue_en_cours);
}
else
{
echo aff("Les statistiques sont celles des téléchargements/ventes pour les composants soumis par tous.",
"Statistics are for download/sells of components submited by everybody.",
$iso_langue_en_cours);
}
?>
<br><br>

<?php echo aff("- Pour changer les informations sur votre produit cliquez sur son nom,<br>- Pour changer son image cliquez sur son image<br>- Pour supprimer ou désactriver un produit, envoyez un mail à contact@dolibarr.org", "- To change your product information click on its name,<br>- To change its picture click on its picture<br>- To remove or disable a product, send a mail to contact@dolibarr.org", $iso_langue_en_cours); ?>

<FORM name="fmysalessubprod" method="POST" ENCTYPE="multipart/form-data" class="std">
<table width="100%" >
<tr>
<td>



<table width="100%" border="0" cellspacing="2" cellpadding="0">
  <tr bgcolor="#CCCCCC">
    <td nowrap="nowrap"><b><?php echo aff("Image", "Picture", $iso_langue_en_cours); ?></b></td>
    <td nowrap="nowrap"><b><?php echo aff("Produit", "Product", $iso_langue_en_cours); ?></b></td>
    <td nowrap="nowrap"><b> &nbsp;<?php echo aff("Nb", "Nb", $iso_langue_en_cours); ?></b> &nbsp;</td>
    <td nowrap="nowrap"><b><?php echo aff("Montant<br>gagné HT", "Amount<br>earned (excl tax)", $iso_langue_en_cours); ?></b></td>
    <!--<td nowrap="nowrap"><b><?php echo aff("Supp", "Delete", $iso_langue_en_cours); ?></b></td> -->
  </tr>

<?php
$min_date = 0;

// Get list of products
$query = 'SELECT p.id_product, p.reference, p.supplier_reference, p.location, p.active, p.price, p.wholesale_price, pl.name, pl.description_short';
$query.= ' FROM '._DB_PREFIX_.'product as p';
$query.= ' LEFT JOIN '._DB_PREFIX_.'product_lang as pl on pl.id_product = p.id_product AND pl.id_lang = '.$id_langue_en_cours;
if ($customer_id != 'all') $query.= ' WHERE p.reference like "c'.$customer_id.'d2%"';
$query.= ' ORDER BY pl.name';
$result = Db::getInstance()->ExecuteS($query);
if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

$id_product = "";

$colorTabNbr = 1;
if (sizeof($result))
{
	foreach ($result AS $row)	// For each product
	{
		$id_product = $row['id_product'];
		$ref_product = $row['reference'];
		$name = $row['name'];
		$description_short = $row['description_short'];
		$supplier_reference = $row['supplier_reference'];
		$location = $row['location'];
		$active = $row['active'];
		$price = $row['price'];
		$price_ttc = round($row['price'] * (100 + $vatrate) / 100, 2);

		//recuperation nom fichier
		$query = 'SELECT display_filename, date_deposit FROM '._DB_PREFIX_.'product_download
				WHERE `id_product` = '.$id_product;
		$subresult = Db::getInstance()->ExecuteS($query);
		$filename = "";
		$datedeposit=0;
		foreach ($subresult AS $subrow) {
			$filename = $subrow['display_filename'];
			$datedeposit = $subrow['date_deposit'];
		}

		//recuperation de limage du produit
		$query = 'SELECT `id_image` FROM `'._DB_PREFIX_.'image`
				WHERE `id_product` = '.$id_product.'
				AND cover = 1';
		$subresult = Db::getInstance()->ExecuteS($query);
		$id_image = "";
		foreach ($subresult AS $subrow) {
			$id_image = $subrow['id_image'];
		}

		if ($id_image != "")
			$imgProduct = '../../img/p/'.$id_product.'-'.$id_image.'-small.jpg';
		else
			$imgProduct = '../../img/p/en-default-small.jpg';

		if ($colorTabNbr%2)
			$colorTab="#ffffff";
		else
			$colorTab="#eeeeee";

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
		prestalog("Request to count product sells ".$query);

		$subresult = Db::getInstance()->ExecuteS($query);
		if ($subresult === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

		$nbr_achats = 0;
		$nbr_amount = 0;
		foreach ($subresult AS $subrow) {
			$nbr_achats = $subrow['nbra'];
			$nbr_amount = $subrow['amount_ht'];
			$nbr_qtysold = $subrow['qtysold'];
			if ($subrow['min_date'] && $subrow['qtysold'])
			{
				if (! empty($min_date)) $min_date = min($min_date,$subrow['min_date']);
				else $min_date=$subrow['min_date'];
			}
		}

		$totalnbsell+=$nbr_achats;
		if ($nbr_amount > 0) $totalnbsellpaid+=$nbr_qtysold;
		$totalamount+=$nbr_amount;

		// Calculate totalamountclaimable (amount validated supplier can claim)
		$query = "SELECT SUM( od.product_quantity ) as nbra, 
					sum( ROUND((od.product_price - od.reduction_amount) * (100 - od.reduction_percent) / 100 * (od.product_quantity - od.product_quantity_refunded) * o.valid, 2) ) as amount_ht,
					sum( ROUND((od.product_price - od.reduction_amount) * (100 - od.reduction_percent) / 100 * (od.product_quantity - od.product_quantity_refunded) * o.valid * (100 + od.tax_rate) / 100, 2) ) as amount_ttc,
					sum( (od.product_quantity - od.product_quantity_refunded) * o.valid) as qtysold,
					min( o.date_add ) as min_date
					FROM "._DB_PREFIX_."order_detail as od,  "._DB_PREFIX_."orders as o
					WHERE od.product_id = ".$id_product."
					AND o.id_order = od.id_order
					AND date_add <= DATE_ADD('".date("Y-m-d 23:59:59",mktime())."', INTERVAL - ".$mindelaymonth." MONTH)";
		if ($dateafter)  $query.= " AND date_add >= '".$dateafter." 00:00:00'";
		if ($datebefore) $query.= " AND date_add <= '".$datebefore." 23:59:59'";
		prestalog($query);

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

		// Show line of product
		?>
		<tr bgcolor="<?php echo $colorTab; ?>">
		    <td valign="top"><a href="./my-sales-images-product.php?id_p=<?php echo $id_product; ?>"><img src="<?php echo $imgProduct; ?>" border="1" /></a></td>
		    <td><a href="./my-sales-modify-product.php?id_p=<?php echo $id_product; ?>"><?php echo $name; ?></a><br><?php 
				echo aff('Current price: '.round($price,5).'€ excl tax, '.round($price_ttc).'€ incl tax', 'Prix actuel: '.round($price,5).'€ HT, '.round($price_ttc,2).'€ TTC', $iso_langue_en_cours);
			?>
			<?php 
			print '<br>';
			echo $description_short; 
			echo aff("Réf", "Ref", $iso_langue_en_cours).': '.$ref_product.""; 
			if ($active) echo '<img src="../../img/os/2.gif" alt="Enabled" title="Enabled" style="padding: 0px 5px;">';
			else echo '<img src="../../img/os/6.gif" alt="Enabled" title="Enabled" style="padding: 0px 5px;">';
			print '<br>';			
			print aff("Fichier", "File", $iso_langue_en_cours).': '.$filename.'<br>'.aff("Date","Date", $iso_langue_en_cours).' '.$datedeposit;
			?>
			</td>
		    <td align="right" nowrap="nowrap">
				<a href="./my-sales-list.php?id_p=<?php echo $id_product; ?>"><?php echo $nbr_qtysold; ?></a>
				<?php if ($nbr_achats && $nbr_qtysold != $nbr_achats) echo '<br>+'.($nbr_achats-$nbr_qtysold).' '.aff('rejeté','refunded', $iso_langue_en_cours).'<br>'; ?>
			</td>
		    <td align="right"><?php
			if ($nbr_amount > 0)
			{
				echo ($foundationfeerate*100).'% ';
				echo aff(' de ',' of ',$iso_langue_en_cours);
				echo '<br>';
			}
			echo round($nbr_amount,2); ?>&#8364;
			</td>
		    <!--<td>&nbsp;</td> -->
		</tr>

		<?php
		$colorTabNbr++;
	}
}
else
{
	print '<tr><td>';
	echo aff("Pas de module soumis", "No submited modules", $iso_langue_en_cours);
	print '</td></tr>';
}


// Calculate totalvoucher
$query = "SELECT SUM( od.value ) as total_voucher
			FROM "._DB_PREFIX_."order_discount as od,  "._DB_PREFIX_."orders as o
			WHERE od.name like '%C".($customer_id != 'all' ? $customer_id : "%")."'
			AND o.id_order = od.id_order";
$query.= " AND date_add <= '".date("Y-m-d 23:59:59",mktime())."'";
if ($dateafter)  $query.= " AND date_add >= '".$dateafter." 00:00:00'";
if ($datebefore) $query.= " AND date_add <= '".$datebefore." 23:59:59'";
prestalog($query);

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
			FROM "._DB_PREFIX_."order_discount as od,  "._DB_PREFIX_."orders as o
			WHERE od.name like '%C".($customer_id != 'all' ? $customer_id : "%")."'
			AND o.id_order = od.id_order";
$query.= " AND date_add <= DATE_ADD('".date("Y-m-d 23:59:59",mktime())."', INTERVAL - ".$mindelaymonth." MONTH)";
if ($dateafter)  $query.= " AND date_add >= '".$dateafter." 00:00:00'";
if ($datebefore) $query.= " AND date_add <= '".$datebefore." 23:59:59'";
prestalog($query);

$subresult = Db::getInstance()->ExecuteS($query);
if ($subresult === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

$totalvoucherclaimable_ttc = 0;
foreach ($subresult AS $subrow) 
{
	$totalvoucherclaimable_ttc += $subrow['total_voucher'];
}
$totalvoucherclaimable_ht = round($totalvoucherclaimable_ttc / (100 + $vatrate) * 100, 2);


?>
</table>







</td>
</tr>
</table>


</FORM>
<br>
<?php

// define variables
$mytotalamount=round($foundationfeerate*($totalamount - $totalvoucher_ht),2);
$mytotalamountclaimable=round($foundationfeerate*($totalamountclaimable - $totalvoucherclaimable_ht),2);
$alreadyreceived=0;
$datelastpayment=0;

// Search third party and payments already done
define('NUSOAP_PATH','nusoap');

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
prestalog("Create soapclient_nusoap for URL=".$WS_DOL_URL);
$soapclient = new soapclient_nusoap($WS_DOL_URL);
if ($soapclient)
{
    $soapclient->soap_defencoding='UTF-8';
	$soapclient->decodeUTF8(false);
}

if ($customer_id != 'all')
{
	if (! $foundthirdparty)
	{
		$parameters = array('authentication'=>$authentication,'id'=>0,'ref'=>$publisher);
		$WS_METHOD  = 'getThirdParty';
		prestalog("Call method ".$WS_METHOD." for ref=".$publisher);
		$result = $soapclient->call($WS_METHOD,$parameters);
		if (! $result)
		{
		    print 'Error '.$soapclient->error_str;
		    die;
		}

		//var_dump($result);
		$socid=$result['thirdparty']['id'];
		if ($socid)
		{
			$foundthirdparty=true;
			//var_dump($socid);
		}
	}

	if (! $foundthirdparty)
	{
		$parameters = array('authentication'=>$authentication,'id'=>0,'ref'=>$company);
		$WS_METHOD  = 'getThirdParty';
		prestalog("Call method ".$WS_METHOD." for ref=".$company);
		$result = $soapclient->call($WS_METHOD,$parameters);
		if (! $result)
		{
		    print 'Error '.$soapclient->error_str;
		    die;
		}
		//var_dump($result);
		$socid=$result['thirdparty']['id'];
		if ($socid)
		{
			$foundthirdparty=true;
			//var_dump($socid);
		}
	}

	if (! $foundthirdparty && $admin)
	{
		print '<br>';
		print aff('Le tiers "'.$company.'" n\'a pas été trouvé dans le système.','Third party "'.$company.'" was not found into system', $iso_langue_en_cours);
		print '<br><br>';
	}
}
else $socid='all';

// Call the WebService method to get amount received
$errorcallws=0;
if ($socid)
{
	// Define $dolistoreinvoices
	$WS_DOL_URL = $dolibarr_main_url_root.'/webservices/server_supplier_invoice.php';
	$WS_METHOD  = 'getSupplierInvoicesForThirdParty';
	prestalog("Create soapclient_nusoap for URL=".$WS_DOL_URL);
	$soapclient = new soapclient_nusoap($WS_DOL_URL);
	if ($soapclient)
	{
	    $soapclient->soap_defencoding='UTF-8';
		$soapclient->decodeUTF8(false);
	}
	$parameters = array('authentication'=>$authentication,'id'=>$socid,'ref'=>'');
	prestalog("Call method ".$WS_METHOD." for socid=".$socid);
	$result = $soapclient->call($WS_METHOD,$parameters);
	if (! $result)
	{
	    print 'Error '.$soapclient->error_str;
		$errorcallws++;
	}

	if ($result['result']['result_code'] == 'OK')
	{
		foreach($result['invoices'] as $invoice)
		{
			$dateinvoice=substr($invoice['date_invoice'],0,10);

			$isfordolistore=0;
			if (preg_match('/dolistore/i',$invoice['note_private'])
				&& ! preg_match('/agios/i',$invoice['ref_supplier'])
				&& ! preg_match('/frais/i',$invoice['ref_supplier'])
			) $isfordolistore=1;

			if (! $isfordolistore)
			{
				if (count($invoice['lines']) < 1)
				{
					print 'Error during call of web service '.$WS_METHOD.'. Result='.$result['result']['result_code'].'. No lines for invoice found.';
					$errorcallws++;
					break;
				}

				foreach($invoice['lines'] as $line)
				{
					if (preg_match('/dolistore/i',$line['desc'])
						&& ! preg_match('/Remboursement certificat|Remboursement domaine/i',$line['desc'])
						&& ! preg_match('/agios/i',$invoice['ref_supplier'])
						&& ! preg_match('/frais/i',$invoice['ref_supplier'])
					)
					{
						$isfordolistore++;
					}
				}
			}
			//print 'date='.$dateinvoice.' isfordolistore='.$isfordolistore;exit;

			/*print $dateinvoice.'-'.$dateafter.'-'.$datebefore.'<br>';
			if ($datebefore && $datebefore < $dateinvoice) $isfordolistore=0;
			if ($dateafter && $dateafter > $dateinvoice) $isfordolistore=0;*/

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
		print 'Error during call of web service '.$WS_METHOD.' result='.$result['result']['result_code'].' '.$result['result']['result_label'];
		$errorcallws++;
	}
}

print '<h2>';
echo aff("Vos informations revenus", "Your payment information", $iso_langue_en_cours);
print '</h2>';

if (empty($errorcallws))
{
	print '<form name="filter" action"'.$_SERVER["PHP_SELF"].'" method="POST">';
	print aff('Filtre date entre ','Filter on date between ', $iso_langue_en_cours);
	print '<input type="text" name="dateafter" value="'.(empty($_POST["dateafter"])?'':$_POST["dateafter"]).'" size="11">';
	print ' '.aff('et','and', $iso_langue_en_cours).' ';
	print '<input type="text" name="datebefore" value="'.(empty($_POST["datebefore"])?'':$_POST["datebefore"]).'" size="11">';
	print ' (YYYY-MM-DD) &nbsp;';
	print '<input type="submit" name="submit" value="'.aff("Rafraichir","Refresh",$iso_langue_en_cours).'" class="button">';
	print '<input type="hidden" name="id_customer" value="'.(empty($id_customer)?'':$id_customer).'">';
	print '<br>';
	print '</form>';

	// Total number of sells
	echo aff("Nombre de total de ventes payantes: ", "Number of paid sells: ", $iso_langue_en_cours);
	print "<b>".$totalnbsellpaid."</b>";
	print '<br>';
	// Total payment received
	echo aff("Montant total ventes faites reçus: ", "Total of sells done: ", $iso_langue_en_cours);
	print "<b>".($foundationfeerate*100)."% x ";
	if ($totalvoucher_ht) print "(";
	print $totalamount;
	if ($totalvoucher_ht) print " - ".$totalvoucher_ht."**)";
	print " = ".$mytotalamount."&#8364;";
	echo aff(" HT"," excl tax", $iso_langue_en_cours);
	print '</b><br>';
	// Total amount you can claim
	echo aff("Montant total ventes validées: ", "Total validated sells: ", $iso_langue_en_cours);
	print "<b>".($foundationfeerate*100)."% x ";
	if ($totalvoucherclaimable_ht) print "(";
	print $totalamountclaimable;
	if ($totalvoucherclaimable_ht) print " - ".$totalvoucherclaimable_ht."**)";
	print " = ".$mytotalamountclaimable."&#8364;";
	echo aff(" HT*"," excl tax*", $iso_langue_en_cours);
	print "</b><br>";
	echo aff("* Toute vente n'est validée complètement qu'après un délai de ".$mindelaymonth." mois de rétractation", "** any sell is validated after a ".$mindelaymonth." month delay", $iso_langue_en_cours);
	print '<br>';
	// Total amount of voucher you give
	if ($totalvoucherclaimable_ht || $totalvoucher_ht) 
	{
		echo aff("** Montant total de bons de réductions accordés HT", "** Total amount of vouchers offered excl tax", $iso_langue_en_cours);
		print '<br>';
	}
	
	// List of payments
	if (count($dolistoreinvoices))
	{
		print '<br>'."\n";
		echo aff(($customer_id == 'all'?"Gains déjà reversés (factures comportant 'dolistore' sur lignes ou en note privée): ":"Reversements déjà reçus (toutes dates)"),($customer_id == 'all'?"Payments already distributed (invoices with 'dolistore')":"Payments received back (all time)"), $iso_langue_en_cours);
		print '<br>'."\n";
		$sortdolistoreinvoices=dol_sort_array($dolistoreinvoices,'date');
		$before2013=0;
		foreach($sortdolistoreinvoices as $item)
		{
			$tmpdate=preg_replace('/(\s|T)00:00:00Z/','',$item['date']);
			if ((strcmp($tmpdate, '2013-01-01') < 0) && empty($before2013))
			{
				$before2013=1;
				print aff("Avant le 2013-01-01:<br>","Before 2013-01-01:<br>",$iso_langue_en_cours);
			}
			if ($before2013 && (strcmp($tmpdate, '2013-01-01') >= 0) && empty($after2013))
			{
				$after2013=1;
				print aff("Après le 2013-01-01:<br>","After 2013-01-01:<br>",$iso_langue_en_cours);
			}
			echo aff("Date: ","Date: ", $iso_langue_en_cours);
			print ' <b>'.$tmpdate.'</b> - ';
			if ((strcmp($tmpdate,'2013-01-01') < 0)) print ' <b>'.$item['amount_ttc'].'&#8364;';
			else print ' <b>'.$item['amount_ht'].'&#8364;';
			if ((strcmp($tmpdate,'2013-01-01') < 0)) print aff(" TTC"," incl tax", $iso_langue_en_cours);
			else print aff(" HT"," excl tax", $iso_langue_en_cours);
			print '</b>';
			if ($item['ref_supplier'])
			{
				echo ' - '.aff("Ref fourn: ","Supplier ref: ", $iso_langue_en_cours);
				print ' <b>'.$item['ref_supplier'].'</b>';
			}
			if ($item['status'] != 2) print ' - '.aff("Paiement en cours", "Payment in process", $iso_langue_en_cours);
			if ($item['ref'] || $customer_id == 'all') 
			{
				print ' <img title="';
				echo aff("Ref Dolibarr -> Facture: ","Ref Dolibarr -> Invoice: ", $iso_langue_en_cours);
				print ' '.$item['ref'];
				//if ($customer_id == 'all')
				//{
					print ' - ';
					echo aff("Fournisseur: ","Supplier: ", $iso_langue_en_cours);
					print ' '.$item['fk_thirdparty'];
				//}
				print '" src="/img/admin/asterisk.gif">';
			}

			print '<br>'."\n";
			if (strcmp($tmpdate,'2013-01-01') < 0) $alreadyreceived+=$item['amount_ttc'];
			else $alreadyreceived+=$item['amount_ht'];
			//var_dump($dolistoreinvoices);
		}
	}
	else
	{
		echo aff("Date du dernier reversement des gains: ","Last payment date: ", $iso_langue_en_cours);
		if ($datelastpayment) print '<b>'.date('Y-m-d',$datelastpayment).'</b>';
		else print aff("<b>Aucun reversement reçu</b>","<b>No payment received yet</b>", $iso_langue_en_cours);
		print '<br>';
	}
	print '<br>';

	if (empty($dateafter) && empty($datebefore))
	{
		// Remain to receive in 1 months
		echo aff("Montant restant à réclamer dans ".$mindelaymonth." mois: ","Remained amount to claim in ".$mindelaymonth." month: ", $iso_langue_en_cours);
		//print "$mytotalamount - $alreadyreceived";
		$remaintoreceivein2month = $mytotalamount - $alreadyreceived;
		print '<b><font color="#DF7E00">'.round($remaintoreceivein2month,2)."&#8364;";
		echo aff(" HT"," excl tax", $iso_langue_en_cours);
		print "</font></b>";
		print '<br>';
		// Remain to receive now
		echo aff("Montant restant à réclamer à ce jour: ","Remained amount to claim today: ", $iso_langue_en_cours);
		$remaintoreceive = $mytotalamountclaimable - $alreadyreceived;
		print '<b><font color="#DF7E00">'.round($remaintoreceive,2)."&#8364;";
		echo aff(" HT"," excl tax", $iso_langue_en_cours);
		print "</font></b>";
		print '<br>';
		print "<br>";

		// Message to claim
		if ($remaintoreceive)
		{
			$minamount=($iscee?$minamountcee:$minamountnotcee);
			echo aff("Montant minimum pour réclamer le reversement pour votre pays (<strong>".$country."</strong>): <strong>".$minamount."</strong>&#8364;","Minimum amount to claim payments for your country (<strong>".$country."</strong>): <strong>".$minamount."</strong>&#8364;.", $iso_langue_en_cours).'<br>';
			echo aff("Montant commission frais change pour votre monnaie (<strong>".$country."</strong>): <strong>".($iscee?'Gratuit':'selon votre banque')."</strong>.","Charge for change for your currency (<strong>".$country."</strong>): <strong>".($iscee?'Free':'depends on your bank')."</strong>.", $iso_langue_en_cours).'<br>';
			print '<br>';

			if ($customer_id != 'all')
			{
				if ($remaintoreceive > $minamount)
				{
					echo aff('Vous pouvez réclamer le montant restant à payer en envoyant une facture à <b>Association Dolibarr, France</b>, du montant restant à percevoir (Total HT = <font color="#DF7E00">'.round($remaintoreceive,2).'&#8364;</font>), par mail à <b>dolistore@dolibarr.org</b>, en indiquant vos coordonnées bancaires pour le virement (RIB ou SWIFT). Si vous avez besoin des informations sur l\'association:<br>Raison sociale: Association Dolibarr<br>Numéro de TVA '.$vatnumber.'<br>Adresse: 265, rue de la vallée, 45160 Olivet, FRANCE.<br>Web: <a href="http://asso.dolibarr.org/" target="_blank">http://asso.dolibarr.org/</a>',
							'You can claim remained amount to pay by sending an invoice to <b>Association Dolibarr, France</b>, with remain to pay (Total excl tax = <font color="#DF7E00">'.round($remaintoreceive,2).'&#8364;</font>), by email to <b>dolistore@dolibarr.org</b>. Don\'t forget to add your bank account number for bank transaction (BIC ou SWIFT).<br>If you need information about foundation:<br>Name: Association Dolibarr<br>VAT number: '.$vatnumber.'<br>Address: 265, rue de la vallée, 45160 Olivet, FRANCE<br>Web: <a href="http://asso.dolibarr.org/" target="_blank">http://asso.dolibarr.org/</a>', $iso_langue_en_cours);
					print '<br>';
					//echo aff("Le taux de TVA à appliquer sur la facture est de zero (y compris pour les sociétés européennes car bénéficiant du facture intra VAT, VAT de l'association FRXXXXX)",
					//		"VAT rate to use into your invoice is zero", $iso_langue_en_cours);
				}
				else
				{
					echo aff("Il n'est pas possible de réclamer de reversements pour le moment (montant inférieur à ".$minamount." euros).","It is not possible to claim payments for the moment (amount lower than ".$minamount." euros).", $iso_langue_en_cours);
					print '<br>';
				}
			}
		}
		else
		{
			if ($customer_id != 'all')
			{
				echo aff("Il n'est pas possible de réclamer de reversements pour le moment. Votre solde est nul.", "It is not possible to claim payments for the moment. Your sold is null.", $iso_langue_en_cours);
				print '<br>';
			}
		}
		print '<br>';
	}
}
else
{
	echo aff("Due à un problème technique, vos informations paiements ne sont acutellement pas disponibles.", "Due to a technical problem, your payment information are not available for the moment.", $iso_langue_en_cours);
}


include(dirname(__FILE__).'/../../footer.php');



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
function dol_sort_array(&$array, $index, $order='asc', $natsort=0, $case_sensitive=0)
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

?>
