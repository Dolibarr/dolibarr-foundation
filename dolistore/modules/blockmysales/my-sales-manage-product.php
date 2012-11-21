<?php

/* SSL Management */
$useSSL = true;

require_once('config.inc.php');
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/../../init.php');
include(dirname(__FILE__).'/lib.php');


// Get env variables
$id_langue_en_cours = $cookie->id_lang;
$customer_id = $cookie->id_customer;
$publisher=trim($cookie->customer_firstname.' '.$cookie->customer_lastname);
if (! empty($_GET["id_customer"]))  $customer_id=$_GET["id_customer"];
if (! empty($_POST["id_customer"])) $customer_id=$_POST["id_customer"];


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
echo aff("Les statistiques sont celles des téléchargements/ventes pour les composants soumis par l'utilisateur courant (<b>".$publisher.($company?" - ".$company:"").($country?" - ".$country:"")."</b>)",
"Statistics are for download/sells of components submited by for current user (<b>".$publisher.($company?" - ".$company:"").($country?" - ".$country:"")."</b>)",
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
    <td nowrap="nowrap"><b><?php echo aff("Montant<br>gagné", "Amount<br>earned", $iso_langue_en_cours); ?></b></td>
    <!--<td nowrap="nowrap"><b><?php echo aff("Supp", "Delete", $iso_langue_en_cours); ?></b></td> -->
  </tr>

<?php
$min_date = 0;

// Get list of products
$query = 'SELECT p.id_product, p.reference, p.supplier_reference, p.location, p.active, p.price, pl.name, pl.description_short';
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
		//$query = "SELECT count( od.id_order_detail ) as nbra, 
		$query = "SELECT SUM( od.product_quantity ) as nbra, 
					sum( (od.product_price - od.reduction_amount) * (100 - od.reduction_percent) / 100 * (od.product_quantity - od.product_quantity_refunded) * o.valid) as amount,
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
			$nbr_amount = $subrow['amount'];
			$nbr_qtysold = $subrow['qtysold'];
			if ($subrow['min_date'] && $subrow['qtysold'])
			{
				if ($min_date) $min_date = min($min_date,$subrow['min_date']);
				else $min_date=$subrow['min_date'];
			}
		}

		$totalnbsell+=$nbr_achats;
		if ($nbr_amount > 0) $totalnbsellpaid+=$nbr_qtysold;
		$totalamount+=$nbr_amount;

		// Calculate totalamountclaimable (amount validated supplier can claim)
		$query = "SELECT SUM( od.product_quantity ) as nbra, 
					sum( (od.product_price - od.reduction_amount) * (100 - od.reduction_percent) / 100 * (od.product_quantity - od.product_quantity_refunded) * o.valid) as amount,
					sum( (od.product_quantity - od.product_quantity_refunded) * o.valid) as qtysold,
					min( o.date_add ) as min_date
					FROM "._DB_PREFIX_."order_detail as od,  "._DB_PREFIX_."orders as o
					WHERE od.product_id = ".$id_product."
					AND o.id_order = od.id_order
					AND date_add < DATE_ADD('".date("Y-m-d 00:00:00",mktime())."', INTERVAL - ".$mindelaymonth." MONTH)";	// 2 month
		if ($dateafter)  $query.= " AND date_add >= '".$dateafter." 00:00:00'";
		if ($datebefore) $query.= " AND date_add <= '".$datebefore." 23:59:59'";
		prestalog($query);

		$subresult = Db::getInstance()->ExecuteS($query);
		if ($subresult === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

		$nbr_amount2 = 0;
		foreach ($subresult AS $subrow) {
			$nbr_amount2 = $subrow['amount'];
			if ($subrow['min_date'] && $subrow['qtysold'])
			{
				if ($min_date2) $min_date2 = min($min_date2,$subrow['min_date']);
				else $min_date2=$subrow['min_date'];
			}
		}

		$totalamountclaimable+=$nbr_amount2;

		// Show line of product
		?>
		<tr bgcolor="<?php echo $colorTab; ?>">
		    <td valign="top"><a href="./my-sales-images-product.php?id_p=<?php echo $id_product; ?>"><img src="<?php echo $imgProduct; ?>" border="1" /></a></td>
		    <td><a href="./my-sales-modify-product.php?id_p=<?php echo $id_product; ?>"><?php echo $name; ?></a> (<?php echo round($price); ?>€)
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
?>
</table>







</td>
</tr>
</table>


</FORM>
<br>
<?php

// define variables
$mytotalamount=round($foundationfeerate*$totalamount,2);
$mytotalamountclaimable=round($foundationfeerate*$totalamountclaimable,2);
$alreadyreceived=0;
$datelastpayment=0;

// Search third party and payments already done
define(NUSOAP_PATH,'nusoap');

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
}
else $socid='all';

// Call the WebService method to get amount received
if ($socid)
{
	// Define $datelastpayment and $alreadyreceived
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
	    die;
	}
	if ($result['result']['result_code'] == 'OK')
	{
		foreach($result['invoices'] as $invoice)
		{
			$dateinvoice=substr($invoice['date_invoice'],0,10);

			$isfordolistore=0;
			if (preg_match('/dolistore/i',$invoice['note'])
				&& ! preg_match('/agios/i',$invoice['ref_supplier'])
				&& ! preg_match('/frais/i',$invoice['ref_supplier'])
			) $isfordolistore=1;
			if (! $isfordolistore)
			{
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
					'amount'=>$invoice['total'],
					'fk_thirdparty'=>$invoice['fk_thirdparty']
				);
				$alreadyreceived+=$invoice['total'];
			}
		}
	}
	else
	{
		print 'Error during call of web service '.$WS_METHOD.' result='.$result['result']['result_code'];
	}
}

print '<h2>';
echo aff("Vos informations revenus", "Your payment information", $iso_langue_en_cours);
print '</h2>';

print '<form name="filter" action"'.$_SERVER["PHP_SELF"].'" method="POST">';
print aff('Filtre date entre','Filter on date between', $iso_langue_en_cours);
print '<input type="text" name="dateafter" value="'.$_POST["dateafter"].'" size="11">';
print ' '.aff('et','and', $iso_langue_en_cours);
print '<input type="text" name="datebefore" value="'.$_POST["datebefore"].'" size="11">';
print ' (YYYY-MM-DD) &nbsp;';
print '<input type="submit" name="submit" value="'.aff("Rafraichir","Refresh",$iso_langue_en_cours).'">';
print '<input type="hidden" name="id_customer" value="'.$id_customer.'">';
print '<br>';
print '</form>';

// Total number of sells
echo aff("Nombre de total de ventes payantes: ", "Number of paid sells: ", $iso_langue_en_cours);
print "<b>".$totalnbsellpaid."</b>";
print '<br>';
// Total amount earned
echo aff("Montant total gagné: ", "Total amount earned: ", $iso_langue_en_cours);
print "<b>".($foundationfeerate*100)."% x ".$totalamount." = ".$mytotalamount."&#8364;</b>";
print '<br>';
// Total amount you can claim
echo aff("Montant total validé: ", "Total validated amount: ", $iso_langue_en_cours);
print "<b>".($foundationfeerate*100)."% x ".$totalamountclaimable." = ".$mytotalamountclaimable."&#8364;</b>";
echo aff(" &nbsp; (toute vente n'est validée complètement qu'après un délai de ".$mindelaymonth." mois de rétractation)", "&nbsp; (any sell is validated after a ".$mindelaymonth." month delay)", $iso_langue_en_cours);
print '<br>';
// List of payments
if (count($dolistoreinvoices))
{
	print '<br>'."\n";
	echo aff(($customer_id == 'all'?"Gains déjà reversés (factures comportant 'dolistore'): ":"Reversements déjà reçus"),($customer_id == 'all'?"Payments already distributed (invoices with 'dolistore')":"Last payments received "), $iso_langue_en_cours);
	print '<br>'."\n";
	$sortdolistoreinvoices=dol_sort_array($dolistoreinvoices,'date');
	foreach($sortdolistoreinvoices as $item)
	{
		echo aff("Date: ","Date: ", $iso_langue_en_cours);
		print ' <b>'.preg_replace('/\s00:00:00Z/','',$item['date']).'</b> - ';
		//echo aff("Montant: ","Amount: ", $iso_langue_en_cours);
		print ' <b>'.$item['amount'].'&#8364;</b>';
		if ($item['ref_supplier'])
		{
			echo ' - '.aff("Ref fourn: ","Supplier ref: ", $iso_langue_en_cours);
			print ' <b>'.$item['ref_supplier'].'</b>';
		}
		if ($item['status'] != 2) print ' - '.aff("Paiement en cours", "Payment inprocess", $iso_langue_en_cours);
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
	// Remain to receive now
	echo aff("Montant restant à réclamer à ce jour: ","Remained amount to claim today: ", $iso_langue_en_cours);
	$remaintoreceive=$mytotalamountclaimable-$alreadyreceived;
	print '<b><font color="#DF7E00">'.round($remaintoreceive,2)."&#8364;</font></b><br>";
	// Remain to receive in 2 months
	echo aff("Montant restant à réclamer dans ".$mindelaymonth." mois: ","Remained amount to claim in ".$mindelaymonth." month: ", $iso_langue_en_cours);
	$remaintoreceivein2month=$mytotalamount-$alreadyreceived;
	print '<b><font color="#DF7E00">'.round($remaintoreceivein2month,2)."&#8364;</font></b><br>";
	print '<br>';

	// Message to claim
	if ($remaintoreceive)
	{
		$minamount=($iscee?$minamountcee:$minamountnotcee);
		echo aff("Montant minimum pour réclamer le reversement pour votre pays (<strong>".$country."</strong>): <strong>".$minamount."</strong> euros.","Minimum amount to claim payments for your country (<strong>".$country."</strong>): <strong>".$minamount."</strong> euros.", $iso_langue_en_cours).'<br>';
//		echo aff("Montant commission transfert bancaire pour votre pays (<strong>".$country."</strong>): <strong>".($iscee?'Gratuit':' environ 30 euros')."</strong>.","Fee for bank transfert for your country (<strong>".$country."</strong>): <strong>".($iscee?'Free':'around 30 euros')."</strong>.", $iso_langue_en_cours).'<br>';
		echo aff("Montant commission frais change pour votre monnaie (<strong>".$country."</strong>): <strong>".($iscee?'Gratuit':'selon votre banque')."</strong>.","Charge for change for your currency (<strong>".$country."</strong>): <strong>".($iscee?'Free':'depends on your bank')."</strong>.", $iso_langue_en_cours).'<br>';
		print '<br>';

		if ($customer_id != 'all')
		{
			if ($remaintoreceive > $minamount)
			{
				echo aff("Vous pouvez réclamer le montant restant à payer en envoyant une facture à <b>Association Dolibarr</b>, du montant restant à percevoir, par mail à <b>dolistore@dolibarr.org</b>, en indiquant vos coordonnées bancaires pour le virement (RIB ou SWIFT).",
						"You can claim remained amount to pay by sending an invoice to <b>Association Dolibarr</b>, with remain to pay, by email to <b>dolistore@dolibarr.org</b>. Don't forget to add your bank account number for bank transaction (BIC ou SWIFT).", $iso_langue_en_cours);
				print '<br>';
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
