<?php

/* SSL Management */
$useSSL = true;

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/../../init.php');

$foundationfeerate=0.7;

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
$totalnbsell=0;
$totalamount=0;

// TODO Look into a database for last payment date of user
//
if (empty($datestart)) 
{
	//print $cookie->date_add;
	//$datestart=strtotime('YY-mm-DD HH:MM:II',$cookie->date_add);
	$datestart=strtotime($cookie->date_add);
	//$datestart=mktime(0, 0, 0, 6, 1, 2010);	// If not found
	//print 'ee'.$datestart;
}

$publisher=trim($cookie->customer_firstname.' '.$cookie->customer_lastname);

aff(
"Les statistiques sont celles des téléchargements/ventes depuis le dernier paiement reçu pour vos ventes (<b>".date('Y-m-d',$datestart)."</b>) pour l'utilisateur courant (<b>".$publisher."</b>)",
"Statistics are for download/sells since the last payment received for your sells (<b>".date('Y-m-d',$datestart)."</b>) for curent user (<b>".$publisher."</b>)",
$iso_langue_en_cours);

?>
<br><br>

<?php aff("- Pour changer les informations sur votre produit cliquez sur son nom,<br>- Pour changer son image cliquez sur son image", "- To change your product information click on its name,<br>- To change its picture click on its picture", $iso_langue_en_cours); ?>

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
			
		$query = 'SELECT count( `id_order_detail` ) as \'nbra\', sum( `product_price` ) as \'amount\'
					FROM `'._DB_PREFIX_.'order_detail`,  `'._DB_PREFIX_.'orders`
					WHERE `product_id` = '.$id_product.'
					AND `'._DB_PREFIX_.'orders`.`id_order` = `'._DB_PREFIX_.'order_detail`.`id_order`
					AND `'._DB_PREFIX_.'orders`.`valid` = 1
				';
		$subresult = Db::getInstance()->ExecuteS($query);
		$nbr_achats = 0;
		$nbr_amount = 0;
		foreach ($subresult AS $subrow) {
			$nbr_achats = $subrow['nbra'];
			$nbr_amount = $subrow['amount'];
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
	aff("Date du dernier paiement: ","Last payment date: ", $iso_langue_en_cours);
	print '<b>'.date('Y-m-d',$datestart).'</b><br>';
	aff("Montant total gagné: ", "Total amount earned: ", $iso_langue_en_cours);
	print "<b>".$foundationfeerate." x ".$totalamount." = ".round($foundationfeerate*$totalamount,2)."&#8364;</b>";
	print '<br>';
	aff("Il n'est pas possible de recevoir de paiements pour le moment.", "It is not possible to receive payments for the moment.", $iso_langue_en_cours);
	print '<br>';
	print '<br>';
}
else
{

}

include(dirname(__FILE__).'/../../footer.php');
?>