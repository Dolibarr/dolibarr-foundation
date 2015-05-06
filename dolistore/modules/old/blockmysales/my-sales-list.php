<?php

/* SSL Management */
$useSSL = true;

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/../../init.php');

// Get env variables
$id_product = $_GET['id_p'];
$id_langue_en_cours = $cookie->id_lang;
$customer_id = $cookie->id_customer;
$publisher=trim($cookie->customer_firstname.' '.$cookie->customer_lastname);
if (! empty($_GET["id_customer"])) $customer_id=$_GET["id_customer"];

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

$query = "SELECT c.id_customer, c.firstname, c.lastname, c.email, c.optin, c.active, c.deleted, a.company";
$query.= " FROM "._DB_PREFIX_."customer as c";
$query.= " LEFT JOIN "._DB_PREFIX_."address as a ON a.id_customer = c.id_customer";
$query.= " WHERE c.id_customer = ".$customer_id;
$subresult = Db::getInstance()->ExecuteS($query);

if (! empty($subresult[0]['active']))
{
	$customer_id=$subresult[0]['id_customer'];
	$publisher=trim($subresult[0]['firstname'].' '.$subresult[0]['lastname']);
	$company=trim($subresult[0]['company']);
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

$arraylistofproducts=array();

// Get list of products for customer
if ($id_product == 'all' || $id_product == 'download')
{
	$id_product_list='';

	$query = 'SELECT p.id_product, p.reference, pl.name';
	$query.= ' FROM '._DB_PREFIX_.'product as p';
	$query.= ' LEFT JOIN '._DB_PREFIX_.'product_lang as pl ON pl.id_product = p.id_product AND pl.id_lang = '.$id_langue_en_cours;
	$query.= ' WHERE reference like "c'.$customer_id.'d2%"';
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
			WHERE id_product = '.$id_product.'
			AND id_lang = '.$id_langue_en_cours;
	$subresult = Db::getInstance()->ExecuteS($query);
	$name = "";
	$description_short = "";
	foreach ($subresult AS $subrow) {
		$name = $subrow['name'];
		$description_short = $subrow['description_short'];
	}

	// Get image of product
	$query = 'SELECT id_image FROM '._DB_PREFIX_.'image
			WHERE id_product = '.$id_product.'
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

}

// Select for list
$query = "SELECT c.id_customer, c.email, c.lastname, c.firstname, c.date_add as cust_date_add, c.date_upd as cust_date_upd, 
			od.id_order_detail, od.product_price, od.tax_rate, od.product_id,
			ROUND(od.product_price, 5) as amount_ht, 
			ROUND(od.product_price * (100 + od.tax_rate) / 100, 2) as amount_ttc, 
			od.reduction_percent, od.reduction_amount, od.product_quantity, od.product_quantity_refunded,
			o.date_add, o.valid
			FROM "._DB_PREFIX_."customer as c, "._DB_PREFIX_."order_detail as od,  "._DB_PREFIX_."orders as o
			WHERE o.id_order = od.id_order AND c.id_customer = o.id_customer";
if ($id_product == 'all' || $id_product == 'download') $query.=" AND od.product_id IN (".$id_product_list.")";
else $query.=" AND od.product_id = ".$id_product;

prestalog($query);


if ($id_product == 'download')
{
	$subresult = Db::getInstance()->ExecuteS($query);

	$i=0;$totalamountearned=0;
	$colorTabNbr=0;

	print 'Module sell nb;';
	print 'Customer;';
	print 'Customer date creation;';
	print 'Customer email;';
	print 'Date sell;';
	print 'Product id;';
	print 'Product label;';
	print 'Amount earned;';
	print 'Note';
	print "\n";

	foreach ($subresult AS $subrow) 
	{
		$i+=$subrow['product_quantity'];
		print ($subrow['product_quantity']>1?($i+1-$subrow['product_quantity']).'-':'').$i.";";
		print $subrow['lastname'].' '.$subrow['firstname'].";";
		print $subrow['cust_date_add'].";";
		print $subrow['email'].";";
		print $subrow['date_add'].";";
		print $arraylistofproducts[$subrow['product_id']].";";
		if (($subrow['product_quantity'] - $subrow['product_quantity_refunded']) > 0 && $subrow["valid"] == 1)
		{
			$amountearnedunit=(float) ($subrow['amount_ht']-$subrow['reduction_amount']+0);
			if ($subrow['reduction_percent'] > 0) $amountearnedunit=round($amountearnedunit*(100-$subrow['reduction_percent'])/100,5);
			$amountearned=$amountearnedunit*$subrow['product_quantity'];

			$totalamountearned+=$amountearned;
			
			print $amountearned.";";

			if ($subrow['reduction_amount'] > 0 || $subrow['reduction_percent'] > 0) echo round($amountearnedunit,5).' ('.($subrow['amount_ht']+0).')';
			else echo round($amountearnedunit,5).($subrow['product_quantity']>1?' x'.$subrow['product_quantity']:'');
		}
		else
		{
			print aff('Rejeté','Refunded',$iso_langue_en_cours).";";
		}
		print "\r\n";
	}

	exit;
}


// HTML header

include(dirname(__FILE__).'/../../header.php');

if ($id_product == 'all') 
{
	echo aff("<h2>Liste des ventes de tous les produits</h2>", "<h2>List of sells for all products</h2>", $iso_langue_en_cours);
}
else
{
	echo aff("<h2>Liste des ventes de produit</h2>", "<h2>List of sells for product</h2>", $iso_langue_en_cours);

	echo aff('Ventes pour le produit <b>'.$name.'</b>', 'Sells for product <b>'.$name.'</b>', $iso_langue_en_cours);
}

?>


<br><br>

<FORM name="fmysalessubprod" method="POST" ENCTYPE="multipart/form-data" class="std">
<table width="100%" >
<tr>
<td>

<table width="100%" border="0" cellspacing="2" cellpadding="0">
  <tr bgcolor="#CCCCCC">
    <td nowrap="nowrap"><b><?php echo aff("Num", "Nb", $iso_langue_en_cours); ?></b></td>
    <td nowrap="nowrap"><b><?php echo aff("Nom", "Name", $iso_langue_en_cours); ?></b></td>
	<td nowrap="nowrap" align="center"><b><?php echo aff("Date", "Date", $iso_langue_en_cours); ?></b></td>
	<?php
		if ($id_product == 'all') print '<td>'.aff("Product", "Produit", $iso_langue_en_cours).'</td>';
	?>
    <td nowrap="nowrap"><b><?php echo aff("Montant HT", "Amount (excl tax)", $iso_langue_en_cours); ?></b></td>
    <!--<td nowrap="nowrap"><b><?php echo aff("Supp", "Delete", $iso_langue_en_cours); ?></b></td> -->
  </tr>

<?php

//print $query;
$subresult = Db::getInstance()->ExecuteS($query);

$i=0;$totalamountearned=0;
$colorTabNbr=0;
foreach ($subresult AS $subrow) 
{
	$i+=$subrow['product_quantity'];
	
	if (! empty($colorTabNbr) && $colorTabNbr % 2)
		$colorTab="#ffffff";
	else
		$colorTab="#eeeeee";
	?>

	<tr bgcolor="<?php echo $colorTab; ?>">
		<td><?php echo ($subrow['product_quantity']>1?($i+1-$subrow['product_quantity']).'-':'').$i; ?></td>
		<td><?php 
			echo '<b>'.$subrow['lastname'].' '.$subrow['firstname'].'</b><br>('.$subrow['email'].')'; 
			echo '<br>'.aff("Inscrit le: ", "Registered on: ", $iso_langue_en_cours).$subrow['cust_date_add'];
		?>
		</td>
		<td align="center"><?php echo $subrow['date_add']; ?></td>
		<?php
			if ($id_product == 'all') print '<td>'.$arraylistofproducts[$subrow['product_id']].'</td>';
		?>
		<td align="right"><?php 
			if (($subrow['product_quantity'] - $subrow['product_quantity_refunded']) > 0 && $subrow["valid"] == 1)
			{
				$amountearnedunit=(float) ($subrow['amount_ht']-$subrow['reduction_amount']+0);
				if ($subrow['reduction_percent'] > 0) $amountearnedunit=round($amountearnedunit*(100-$subrow['reduction_percent'])/100,5);
				$amountearned=$amountearnedunit*$subrow['product_quantity'];

				$totalamountearned+=$amountearned;
				
				if ($subrow['reduction_amount'] > 0 || $subrow['reduction_percent'] > 0) echo round($amountearnedunit,5).' ('.($subrow['amount_ht']+0).')';
				else echo round($amountearnedunit,5).($subrow['product_quantity']>1?' x'.$subrow['product_quantity']:'');
			}
			else
			{
				print aff('Rejeté','Refunded',$iso_langue_en_cours);
			}
		?>
		</td>
	</tr>

	<?php
	$colorTabNbr++;
}

if (! empty($colorTabNbr) && $colorTabNbr % 2)
	$colorTab="#ffffff";
else
	$colorTab="#eeeeee";

$colspan=3;
if ($id_product == 'all') $colspan++;
?>
<tr bgcolor="<?php echo $colorTab; ?>"><td colspan="<?php echo $colspan; ?>"><?php echo aff("Total HT", "Total excl taxes", $iso_langue_en_cours); ?></td><td align="right"><?php echo round($totalamountearned,2); ?></td></tr>
</table>


</td>
</tr>
</table>
</FORM>
<br>
<?php

include(dirname(__FILE__).'/../../footer.php');
?>
