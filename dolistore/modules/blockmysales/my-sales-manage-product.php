<?php

/* SSL Management */
$useSSL = true;

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/../../init.php');
include(dirname(__FILE__).'/lib.php');

$id_langue_en_cours = $cookie->id_lang;
$customer_id = $cookie->id_customer;

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




function aff($lb_fr, $lb_other, $iso_langue_en_cours) {
	if ($iso_langue_en_cours == "fr") echo $lb_fr;
	else echo $lb_other;
}


?>



<?php aff("<h2>Modifier mes modules/produits</h2>", "<h2>Manages my modules/plugins</h2>", $iso_langue_en_cours); ?>
<br />

<?php
// foundationfreerate
$foundationfeerate=0.7;
// totalnbofsell
$totalnbsell=0;
// totalamount
$totalamount=0;
// datestart
// TODO Read first sell to detect datestart
if (empty($datestart))
{
	//print $cookie->date_add;
	//$datestart=strtotime('YY-mm-DD HH:MM:II',$cookie->date_add);
	$datestart=strtotime($cookie->date_add);
	//$datestart=mktime(0, 0, 0, 6, 1, 2010);	// If not found
	//print 'ee'.$datestart;
}
// datelastpayment
// TODO Read into database value for datelastpayment
// datetoclaim
$datetoclaim=0;
if (empty($datelastpayment)) $datetoclaim=$datestart+(3600*24*30*6);
else $datetoclaim=$datestart+(3600*24*30*6);


$publisher=trim($cookie->customer_firstname.' '.$cookie->customer_lastname);

aff(
"Les statistiques sont celles des téléchargements/ventes depuis le dernier paiement reçu ".($datelastpayment?"(<b>".date('d/m/Y',$datelastpayment)."</b>)":"")." pour les ventes des composants soumis par l'utilisateur courant (<b>".$publisher."</b>)",
"Statistics are for download/sells since the last payment received ".($datelastpayment?"(<b>".date('Y-m-d',$datelastpayment)."</b>)":"")." for your sells of components submited by for curent user (<b>".$publisher."</b>)",
$iso_langue_en_cours);

?>
<br><br>

<?php aff("- Pour changer les informations sur votre produit cliquez sur son nom,<br>- Pour changer son image cliquez sur son image<br>- Pour supprimer un produit, envoyez un mail à contact@dolibarr.org", "- To change your product information click on its name,<br>- To change its picture click on its picture<br>- To remove a product, send a mail to contact@dolibarr.org", $iso_langue_en_cours); ?>

<FORM name="fmysalessubprod" method="POST" ENCTYPE="multipart/form-data" class="std">
<table width="100%" >
<tr>
<td>



<table width="100%" border="0" cellspacing="2" cellpadding="0">
  <tr bgcolor="#CCCCCC">
    <td nowrap="nowrap"><b><?php aff("Image", "Picture", $iso_langue_en_cours); ?></b></td>
    <td nowrap="nowrap"><b><?php aff("Nom", "Name", $iso_langue_en_cours); ?></b></td>
	<td nowrap="nowrap"><b><?php aff("Description", "Description", $iso_langue_en_cours); ?></b></td>
    <td nowrap="nowrap"><b> &nbsp;<?php aff("Nb", "Nb", $iso_langue_en_cours); ?></b> &nbsp;</td>
    <td nowrap="nowrap"><b><?php aff("Montant<br>gagné", "Amount<br>earned", $iso_langue_en_cours); ?></b></td>
    <!--<td nowrap="nowrap"><b><?php aff("Supp", "Delete", $iso_langue_en_cours); ?></b></td> -->
  </tr>

<?php
$min_date = 0;

$query = 'SELECT `id_product`, `reference` FROM `'._DB_PREFIX_.'product`
			WHERE `reference` like "c'.$customer_id.'d2%"';
$result = Db::getInstance()->ExecuteS($query);

if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
$id_product = "";

$colorTabNbr = 1;
foreach ($result AS $row)
{
	$id_product = $row['id_product'];
	$ref_product = $row['reference'];

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

	$query = "SELECT count( id_order_detail ) as nbra, sum( product_price ) as amount, min( date_add ) as min_date
				FROM "._DB_PREFIX_."order_detail,  "._DB_PREFIX_."orders
				WHERE product_id = ".$id_product."
				AND "._DB_PREFIX_."orders.id_order = "._DB_PREFIX_."order_detail.id_order
				AND "._DB_PREFIX_."orders.valid = 1";
	$subresult = Db::getInstance()->ExecuteS($query);
	$nbr_achats = 0;
	$nbr_amount = 0;
	foreach ($subresult AS $subrow) {
		$nbr_achats = $subrow['nbra'];
		$nbr_amount = $subrow['amount'];
		if ($subrow['min_date'])
		{
			if ($min_date) $min_date = min($min_date,$subrow['min_date']);
			else $min_date=$subrow['min_date'];
		}
	}

	$totalnbsell+=$nbr_achats;
	$totalamount+=$nbr_amount;

	?>

	<tr bgcolor="<?php echo $colorTab; ?>">
        <td><a href="./my-sales-images-product.php?id_p=<?php echo $id_product; ?>"><img src="<?php echo $imgProduct; ?>" border="1" /></a></td>
        <td><a href="./my-sales-modify-product.php?id_p=<?php echo $id_product; ?>"><?php echo $name; ?></a>
		<?php echo "<br>(".$ref_product.")"; ?>
		</td>
        <td>
		<?php echo $description_short; ?>
		</td>
        <td align="right"><?php echo $nbr_achats; ?></td>
        <td align="right"><?php
		if ($nbr_amount > 0)
		{
			echo ($foundationfeerate*100).'% ';
			aff(' de ',' of ',$iso_langue_en_cours);
			echo '<br>';
		}
		echo round($nbr_amount,2); ?>&#8364;
		</td>
        <!--<td>&nbsp;</td> -->
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

if ($totalamount > 0)
{
	// define variables
	$mytotalamount=round($foundationfeerate*$totalamount,2);
    $alreadyreceived=0;
    $datelastpayment=0;
	$datetoclaim=0;
	if ($min_date) $datetoclaim=(strtotime($min_date) + 3*30*24*60*60);
	else $datetoclaim=(mktime() + 3*30*24*60*60);

    // Search third party and payments already done
	define(NUSOAP_PATH,'nusoap');

    require_once(NUSOAP_PATH.'/nusoap.php');        // Include SOAP
    $dolibarr_main_url_root='http://asso.dolibarr.org/dolibarr/';
    $dolibarrkey='dolibarr-an';

    $WS_DOL_URL = $dolibarr_main_url_root.'/webservices/server_thirdparty.php';
    $WS_METHOD  = 'getThirdParty';

    // Set the WebService URL
    prestalog("Create soapclient_nusoap for URL=".$WS_DOL_URL);
    $soapclient = new soapclient_nusoap($WS_DOL_URL);
    if ($soapclient)
    {
        $soapclient->soap_defencoding='UTF-8';
    }

    // Call the WebService method and store its result in $result.
    $authentication=array(
        'dolibarrkey'=>$dolibarrkey,
        'sourceapplication'=>'DEMO',
        'login'=>'admin',
        'password'=>'changeme',
        'entity'=>'');
    $parameters = array('authentication'=>$authentication,'id'=>0,'ref'=>$publisher);
    prestalog("Call method ".$WS_METHOD);
    $result = $soapclient->call($WS_METHOD,$parameters);
    if (! $result)
    {
        print $soapclient->error_str;
        exit;
    }
    $socid=$result['thirdparty']['id'];
    if ($socid)
    {
		var_dump($result);
    }

	print '<h2>';
	aff("Vos informations revenus", "Your payment information", $iso_langue_en_cours);
	print '</h2>';

	// Total amount earned
	aff("Montant total gagné: ", "Total amount earned: ", $iso_langue_en_cours);
	print "<b>".$foundationfeerate." x ".$totalamount." = ".$mytotalamount."&#8364;</b>";
	print '<br>';
	// Last payment date
	aff("Date du dernier reversement des gains: ","Last payment date: ", $iso_langue_en_cours);
	if ($datelastpayment) print '<b>'.date('Y-m-d',$datelastpayment).'</b>';
	else print aff("<b>Aucun reversement reçu</b>","<b>No payment received yet</b>", $iso_langue_en_cours);
	print '<br>';
	print '<br>';
	// Remain to receive
	aff("Montant restant à percevoir: ","Remained amount to receive: ", $iso_langue_en_cours);
	$remaintoreceive=$mytotalamount-$alreadyreceived;
	print "<b>".round($remaintoreceive,2)."&#8364;</b>";
	print '<br>';
	// Date to claim
	if ($remaintoreceive)
	{
		aff("Date pour réclamer le solde: <b>".date('d/m/Y',$datetoclaim).'</b>',"Date to claim remain to pay: <b>".date('Y-m-d',$datetoclaim).'</b>', $iso_langue_en_cours);
		print '<br>';
		if ($datetoclaim < mktime())
		{
			aff("Vous pouvez réclamer le montant restant à payer en envoyant une facture à <b>Association Dolibarr</b>, du montant restant à percevoir, par mail à <b>dolistore@dolibarr.org</b>, en indiquant vos coordonnées bancaires pour le virement.","You can claim remain amount to pay by sending an invoice to <b>Association Dolibarr</b>, with remain to pay, by email to <b>dolistore@dolibarr.org</b>. Don't forget to add your bank account IBAN or BIC number for bank transaction.", $iso_langue_en_cours);
			print '<br>';
		}
		else
		{
			aff("Il n'est pas possible de réclamer de reversements pour le moment. Le dernier paiement est trop récent (3 mois minimum).","It is not possible to claim payments for the moment. Last payment is too recent (3 month minimum).", $iso_langue_en_cours);
			print '<br>';
		}
	}
	else
	{
		aff("Il n'est pas possible de réclamer de reversements pour le moment. Votre solde étant nul.", "It is not possible to claim payments for the moment. Your sold is null.", $iso_langue_en_cours);
		print '<br>';
	}
	print '<br>';

}
else
{
	aff("Aucun gain généré pour le moment.", "Nothing earned for the moment.", $iso_langue_en_cours);
}

include(dirname(__FILE__).'/../../footer.php');
?>
