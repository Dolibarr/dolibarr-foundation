<?php

/* SSL Management */
$useSSL = true;

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/../../init.php');
include(dirname(__FILE__).'/lib.php');

$id_langue_en_cours = $cookie->id_lang;
$customer_id = $cookie->id_customer;
$product_id = $_GET['id_p'];



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



function testProductAppartenance($customer_id, $product_id) {
	$query = 'SELECT `id_product` FROM `'._DB_PREFIX_.'product`
				WHERE `reference` like "c'.$customer_id.'d2%"
				AND `id_product` = '.$product_id;
	$result = Db::getInstance()->ExecuteS($query);
	if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
	$id_product = "";
	foreach ($result AS $row)
		$id_product = $row['id_product'];
	if ($id_product != "")
		return true;
	else
		return false;

}



function aff($lb_fr, $lb_other, $iso_langue_en_cours) {
	if ($iso_langue_en_cours == "fr") echo $lb_fr;
	else echo $lb_other;
}


//test de l'appartenance
if (testProductAppartenance($customer_id, $product_id)) {



	//upload du fichier
	if ($_GET["up"] == 1) {
		if ($_FILES['image_product']['error']) {
				  switch ($_FILES['image_product']['error']){
						   case 1: // UPLOAD_ERR_INI_SIZE
						   echo "Le fichier dépasse la limite autorisée par le serveur !";
						   break;
						   case 2: // UPLOAD_ERR_FORM_SIZE
						   echo "Le fichier dépasse la limite autorisée dans le formulaire HTML !";
						   break;
						   case 3: // UPLOAD_ERR_PARTIAL
						   echo "L'envoi du fichier a été interrompu pendant le transfert !";
						   break;
						   case 4: // UPLOAD_ERR_NO_FILE
						   echo "Le fichier que vous avez envoyé a une taille nulle !";
						   break;
				  }
		}
		else {

			$flagError = 0;

			//check  de l'exctention
			preg_match('/\.([a-zA-Z0-9]+)$/',$_FILES['image_product']['name'],$reg);
			$extention_image = strtolower($reg[1]);
			if (! in_array($extention_image, array("jpg","png","gif","jpeg"))) {
			//if (! in_array($extention_image, array("jpg","jpeg"))) {
				echo "<div style='color:#FF0000'>";aff("Votre image n'est pas en .jpg, le format .".$extention_image." n'est pas autorise.",
													   "Your picture must have .jpg format, the format .".$extention_image." is not authorized.", $iso_langue_en_cours); echo " </div>";
				$flagError = 1;
			}

			//check du remplissage des champs
			for ($x = 0; $languageTAB[$x]; $x++ ) {

				$image_description = "";
				$image_description = $_POST["legende_image_".$languageTAB[$x]['id_lang']];

				/*if ($image_description[$x] == "") {
					echo "<div style='color:#FF0000'>"; aff("Tous les champs sont obligatoires.", "All fields are required.", $iso_langue_en_cours); echo " </div>";
					$flagError = 1;
					break;
				}*/
			}


			//si pas derreur de saisis, traitement en base
			if ($flagError == 0) {

				$chemin_destination = '../../img/p/'.time()."c".intval($cookie->id_customer);

				if (move_uploaded_file($_FILES['image_product']['tmp_name'], $chemin_destination) != true) {
					echo "<div style='color:#FF0000'>file copy impossible for the moment, please try again later </div>";
				} else {

					//prise des parametres de place de l'image en base
					$query = 'SELECT `id_image` FROM `'._DB_PREFIX_.'image`
					WHERE `id_product` = '.$product_id.'
					';
					$result = Db::getInstance()->ExecuteS($query);
					if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
					$position = 1;
					$is_cover = 1;
					foreach ($result AS $row) {
						$id_image = $row['id_image'];
						$position++;
						$is_cover = 0;
					}

					//insertion de limage en base
					$query = 'INSERT INTO `'._DB_PREFIX_.'image` (
						`id_product`, `position`, `cover`
						) VALUES (
						'.$product_id.', '.$position.', '.$is_cover.'
						)';
					$result = Db::getInstance()->ExecuteS($query);
					if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));


					//recuperation de l'id de l'image
					$query = 'SELECT `id_image` FROM `'._DB_PREFIX_.'image`
					WHERE `id_product` = '.$product_id.'
					AND `position` = '.$position.'
					AND `cover` = '.$is_cover.'
					';
					$result = Db::getInstance()->ExecuteS($query);
					if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
					foreach ($result AS $row)
						$id_image = $row['id_image'];


					for ($x = 0; $languageTAB[$x]; $x++ ) {

						$image_description = "";
						$image_description = $_POST["legende_image_".$languageTAB[$x]['id_lang']];
						$image_description = addslashes(strip_tags($image_description));

						$query = 'INSERT INTO `'._DB_PREFIX_.'image_lang` (
						`id_image`, `id_lang`, `legend`
						) VALUES (
						'.$id_image.', '.$languageTAB[$x]['id_lang'].', "'.$image_description.'"
						)';

						$result = Db::getInstance()->ExecuteS($query);
						if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
					}

					//duplication de l'image en bons formats et noms
					$fichier_img_original 	= '../../img/p/'.$product_id.'-'.$id_image.'.'.$extention_image;

					rename($chemin_destination, $fichier_img_original);
					unlink($chemin_destination);

					if ($extention_image != 'jpg')
					{
						// We convert from xxx to jpg
						vignette($fichier_img_original, -1,  -1, 	'', 	50, '../../img/p/', 2);
					}
					vignette($fichier_img_original, 45,  45, 	'-small', 	50, '../../img/p/', 2);
					vignette($fichier_img_original, 70,  70, 	'-medium', 	50, '../../img/p/', 2);
					vignette($fichier_img_original, 250, 250, 	'-large',	50, '../../img/p/', 2);
					vignette($fichier_img_original, 80,   80, 	'-home', 	50, '../../img/p/', 2);
					vignette($fichier_img_original, 600, 600, 	'-thickbox',50, '../../img/p/', 2);


					echo "<script>alert('";
					aff("L image a été liée au module/produit.", "Image has been linked to module/product.", $iso_langue_en_cours);
					echo "'); </script>";

				}
			}
		}
	}




	//suppression dune image
	if ($_GET['del'] != "") {

		$id_image = $_GET['del'];


		//check de si l'image etait la couverture du produit, si oui alors on decalle cette couverture
		$query = 'SELECT `cover` FROM `'._DB_PREFIX_.'image` WHERE `id_image` = '.$id_image;
		$result = Db::getInstance()->ExecuteS($query);
		if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
		foreach ($result AS $row)
			$is_cover = $row['cover'];

		if ($is_cover == 1) {

			$query = 'SELECT `id_image` FROM `'._DB_PREFIX_.'image` WHERE `id_product` = '.$product_id;
			$result = Db::getInstance()->ExecuteS($query);
			if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
			$next_id_image = "";
			foreach ($result AS $row)
				$next_id_image = $row['id_image'];

			if ($next_id_image != "") {
				$query = 'UPDATE `'._DB_PREFIX_.'image` SET `cover` = 1 WHERE `id_image` ='.$next_id_image;
				$result = Db::getInstance()->ExecuteS($query);
				if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
			}
		}


		//delete des images en base et reele
		$query = 'DELETE FROM `'._DB_PREFIX_.'image`
				  WHERE `id_image` = '.$id_image.'
				  ';
		$result = Db::getInstance()->ExecuteS($query);
		if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

		$query = 'DELETE FROM `'._DB_PREFIX_.'image_lang`
				  WHERE `id_image` = '.$id_image.'
				  ';
		$result = Db::getInstance()->ExecuteS($query);
		if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));

		$fichier_img_original 	= '../../img/p/'.$product_id.'-'.$id_image.'.jpg';
		$fichier_img_home 		= '../../img/p/'.$product_id.'-'.$id_image.'-home.jpg';
		$fichier_img_large 		= '../../img/p/'.$product_id.'-'.$id_image.'-large.jpg';
		$fichier_img_medium 	= '../../img/p/'.$product_id.'-'.$id_image.'-medium.jpg';
		$fichier_img_small 		= '../../img/p/'.$product_id.'-'.$id_image.'-small.jpg';
		$fichier_img_tickbox 	= '../../img/p/'.$product_id.'-'.$id_image.'-thickbox.jpg';

		unlink($fichier_img_original);
		unlink($fichier_img_home);
		unlink($fichier_img_large);
		unlink($fichier_img_medium);
		unlink($fichier_img_small);
		unlink($fichier_img_tickbox);
	}



//recupération des informations
$query = 'SELECT
`id_supplier`, `id_manufacturer`, `id_tax`, `id_category_default`, `id_color_default`, `on_sale`, `ean13`, `ecotax`, `quantity`, `price`, `wholesale_price`, `reduction_price`, `reduction_percent`, `reduction_from`, `reduction_to`,
`reference`, `supplier_reference`, `location`, `weight`, `out_of_stock`, `quantity_discount`, `customizable`, `uploadable_files`, `text_fields`, `active`, `indexed`, `date_add`, `date_upd`
FROM `'._DB_PREFIX_.'product`
WHERE `id_product` = '.$product_id.' ';
$result = Db::getInstance()->ExecuteS($query);
if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
foreach ($result AS $row) {
	$wholesale_price = $row['wholesale_price'];
	$active = $row['active'];
	$reference = $row['reference'];

	$_POST['active'] = $active;
	$_POST["priceTI"] = $wholesale_price;
}

?>


<?php aff("<h2>Images de module/produits</h2>", "<h2>module/plugin pictures</h2>", $iso_langue_en_cours); ?>
<br />

<FORM name="fmysalesimgprod" method="POST" ENCTYPE="multipart/form-data" class="std">

<table width="100%" >
<tr>
<td>



<table width="100%" border="0" cellspacing="10" cellpadding="0">

 <tr>
    <td colspan="2"><hr></td>
  </tr>

  <tr>
    <td nowrap="nowrap" valign="top"><?php echo aff("Référence module/produit", "Ref module/product : ", $iso_langue_en_cours); ?> </td>
	<td>&nbsp; <?php echo $reference; ?></td>
  </tr>

  <tr>
    <td colspan="2"><hr></td>
  </tr>


  <tr>
    <td nowrap="nowrap" valign="top"><?php aff("Ajoutez une nouvelle image<br>à ce produit : ", "Add a new image<br>to this product : ", $iso_langue_en_cours); ?></td>
    <td nowrap="nowrap">
    <input id="image_product" name="image_product" maxlength="10000000" type="file"><br>
    <p>
    <?php echo "Format : JPG or PNG &nbsp; &nbsp; &nbsp; (".ini_get('upload_max_filesize')." Max)"; ?>
    </p>
    </td>
  </tr>

<!--
  <tr>
    <td nowrap="nowrap" valign="top"><?php aff("Legende : ", "Legend : ", $iso_langue_en_cours); ?></td>
    <td nowrap="nowrap">
   		<?php for ($x = 0; $languageTAB[$x]; $x++ ) { ?>
        	<input type="text" name="legende_image_<?php echo $languageTAB[$x]['id_lang']; ?>" value="<?php echo $_POST["legende_image_".$languageTAB[$x]['id_lang']]; ?>" size="30" maxlength="100">
			<?php echo $languageTAB[$x]['iso_code']; ?>
            <img src="<?php echo $languageTAB[$x]['img']; ?>" alt="<?php echo $languageTAB[$x]['iso_code']; ?>"><br />
        <?php } ?>
    </td>
  </tr>
-->

  <tr>
    <td colspan="2" align="center"><br>
    	<a href="javascript:document.fmysalesimgprod.action='?up=1&id_p=<?php echo $_GET['id_p']; ?>'; document.fmysalesimgprod.submit();">
          <?php aff("Soumettre cette nouvelle image >> ", "Submit this new picture >> ", $iso_langue_en_cours); ?>
        </a>
    </td>
  </tr>

  <tr>
    <td colspan="2"><hr></td>
  </tr>
</table>



<table width="100%" border="0" cellspacing="10" cellpadding="0">
   <?php
   $query = 'SELECT `id_image` FROM `'._DB_PREFIX_.'image`
			 WHERE `id_product` = '.$product_id.'
			';
	$result = Db::getInstance()->ExecuteS($query);
	if ($result === false) die(Tools::displayError('Invalid SQL query!: '.$query));

	if (sizeof($result) > 0)
	{
		?>
		  <tr>
			<td nowrap="nowrap" valign="top" colspan="2"><?php aff("Images de ce produit : ", "Product's pictures : ", $iso_langue_en_cours); ?></td>
		  </tr>
		<?php
		foreach ($result AS $row)
		{
		   $id_image = $row['id_image'];

		?>
		  <tr>
			<td nowrap="nowrap" valign="top">
				<img src="../../img/p/<?php echo "$product_id-$id_image";?>-large.jpg" />
			</td>
			<td nowrap="nowrap" valign="top">
				<a href="javascript:document.fmysalesimgprod.action='?del=<?php echo $id_image; ?>&id_p=<?php echo $_GET['id_p']; ?>'; document.fmysalesimgprod.submit();"><?php aff("Supprimer cette image", "Delete this picture", $iso_langue_en_cours); ?></a>
			</td>
		  </tr>
		<?php
		}
	}
	//else
	//{
			//aff("Aucune pour le moment (Cliquer sur Soumettre image) : ", "None for the moment (Click on submit now) : ", $iso_langue_en_cours);
	//}

	if (sizeof($result) > 0)
	{
		?>
		<tr>
			<td colspan="2"><br><hr></td>
		</tr>
		<?php
	}
	?>
</table>





<table width="100%" border="0" cellspacing="10" cellpadding="0">
  <tr>
    <td nowrap="nowrap" valign="top" align="center" colspan="2">

        <button <?php print sizeof($result)?'':'disabled="true"'; ?> type="button" onclick="JavaScript: alert('<?php aff("Votre module/produit a été mis à jour", "Module/product updated", $iso_langue_en_cours); ?>'); window.location='./my-sales-manage-product.php';">
	        <?php aff("Terminer", "Finish", $iso_langue_en_cours); ?>
		</button>

     </td>
  </tr>
</table>



</td>
</tr>
</table>

</form>

<?php

} //fin de test appartenance
else
{
	aff("<div style='color:#FF0000'>Vous n'etes pas le proprietaire de ce produit<br> </div>", "<div style='color:#FF0000'>This product is not yours<br> </div>", $iso_langue_en_cours);
}

?>

<?php

include(dirname(__FILE__).'/../../footer.php');

?>
