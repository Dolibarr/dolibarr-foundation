<?php

/* SSL Management */
$useSSL = true;

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/../../init.php');
include(dirname(__FILE__).'/lib.php');

// Get env variables
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


$query = 'SELECT `id_product`, `reference` FROM `'._DB_PREFIX_.'product`
			WHERE `reference` like "c'.$customer_id.'d2%"';
$result = Db::getInstance()->ExecuteS($query);

if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));


$id_product = $_GET['id_p'];

//recuperation des informations ds la langue de l'utilisateur
$query = 'SELECT `name`, `description_short`  FROM `'._DB_PREFIX_.'product_lang`
		WHERE `id_product` = '.$id_product.'
		AND `id_lang` = '.$id_langue_en_cours;
$subresult = Db::getInstance()->ExecuteS($query);
$name = "";
$description_short = "";
foreach ($subresult AS $subrow) {
	$name = $subrow['name'];
	$description_short = $subrow['description_short'];
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

?>



<?php echo aff("<h2>Liste des ventes de produit</h2>", "<h2>List of sells for product</h2>", $iso_langue_en_cours); ?>

<?php
echo aff(
'Ventes pour le produit <b>'.$name.'</b>',
'Sells for product <b>'.$name.'</b>',
$iso_langue_en_cours);
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
    <td nowrap="nowrap"><b><?php echo aff("Montant", "Amount", $iso_langue_en_cours); ?></b></td>
    <!--<td nowrap="nowrap"><b><?php echo aff("Supp", "Delete", $iso_langue_en_cours); ?></b></td> -->
  </tr>

<?php
// Calculate totalamount
$query = "SELECT c.id_customer, c.email, c.lastname, c.firstname, c.date_add as cust_date_add, c.date_upd as cust_date_upd, 
			od.id_order_detail, od.product_price, od.reduction_amount, od.product_quantity, od.product_quantity_refunded,
			o.date_add, o.valid
			FROM "._DB_PREFIX_."customer as c, "._DB_PREFIX_."order_detail as od,  "._DB_PREFIX_."orders as o
			WHERE product_id = ".$id_product."
			AND c.id_customer = o.id_customer 
		    AND o.id_order = od.id_order";
prestalog($query);

//print $query;
$subresult = Db::getInstance()->ExecuteS($query);
$nbr_achats = 0;
$nbr_amount = 0;

$i=0;
foreach ($subresult AS $subrow) 
{
	$i++;
	$nbr_achats = $subrow['nbra'];
	$nbr_amount = $subrow['amount'];
	
	$colorTabNbr = 1;
	?>

	<tr bgcolor="<?php echo $colorTab; ?>">
		<td><?php echo $i; ?></td>
		<td><?php 
			echo '<b>'.$subrow['lastname'].' '.$subrow['firstname'].'</b> ('.$subrow['email'].')'; 
			echo '<br>'.aff("Inscrit le: ", "Registered on: ", $iso_langue_en_cours).$subrow['cust_date_add'];
		?>
		</td>
		<td align="center"><?php echo $subrow['date_add']; ?></td>
		<td align="right"><?php 
			if (($subrow['product_quantity'] - $subrow['product_quantity_refunded']) > 0 && $subrow["valid"] == 1)
			{
				if ($subrow['reduction_amount'] > 0) echo ($subrow['product_price']-$subrow['reduction_amount']+0).' ('.($subrow['product_price']+0).')';
				else echo ($subrow['product_price']+0); 
			}
			else
			{
				print aff('Remboursé','Refunded',$iso_langue_en_cours);
			}
		?>
		</td>
	</tr>

	<?php
	$colorTabNbr++;
}
?>
</table>


</td>
</tr>
</table>
</FORM>
<br>
<?php

include(dirname(__FILE__).'/../../footer.php');
?>
