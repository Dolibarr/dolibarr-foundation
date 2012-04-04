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
$product_id = $_GET['id_p']?$_GET['id_p']:$_POST['id_p'];
if (! empty($_GET["id_customer"])) $customer_id=$_GET["id_customer"];


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




//test de l'appartenance
if (testProductAppartenance($customer_id, $product_id)) {


/*
 * Actions
 */


//upload du fichier
if ($_GET["up"] == 1)
{
	$error=0;

	prestalog("Upload or reupload file ".$_FILES['virtual_product_file']['tmp_name']);

	$originalfilename=$_FILES['virtual_product_file']['name'];
	if ($_FILES['virtual_product_file']['error']) {
		  switch ($_FILES['virtual_product_file']['error']){
				   case 1: // UPLOAD_ERR_INI_SIZE
				   echo "<div style='color:#FF0000'>File size is higher than server limit ! </div>";
				   break;
				   case 2: // UPLOAD_ERR_FORM_SIZE
				   echo "<div style='color:#FF0000'>File size if higher than limit in HTML form ! </div>";
				   break;
				   case 3: // UPLOAD_ERR_PARTIAL
				   echo "<div style='color:#FF0000'>File transfert was aborted ! </div>";
				   break;
				   case 4: // UPLOAD_ERR_NO_FILE
				   echo "<div style='color:#FF0000'>File name was not defined or file size is null ! </div>";
				   break;
		  }
		$upload=-1;
	}
	
	if (! $error && preg_match('/(\.zip|\.tgz)$/i',$originalfilename))
	{
		$rulesfr="";
		$rulesen='';
		if (! preg_match('/^module_([_a-zA-Z0-9]+)\-([0-9]+)\.([0-9\.]+)(\.zip|\.tgz)$/i',$originalfilename)
			&& ! preg_match('/^theme_([_a-zA-Z0-9]+)\-([0-9]+)\.([0-9\.]+)(\.zip|\.tgz)$/i',$originalfilename))
		{
			$rulesfr.="Le nom du fichier package doit avoir un nom du type module_monpackage-x.y(.z).zip<br>";
			$rulesfr.="Essayer de fabriquer votre package avec un outil Dolibarr officiel récent ('htdocs/build/makepack-dolibarrmodule.pl' pour les modules ou ''htdocs/build/makepack-dolibarrtheme.pl' pour les themes).";
			$rulesen.="Package file name must match module_mypackage-x.y(.z).zip<br>";
			$rulesen.="Try to build your package with a recent Dolibarr official tool ('htdocs/build/makepack-dolibarrmodule.pl' or 'htdocs/build/makepack-dolibarrtheme.pl' for themes)";
			echo "<div style='color:#FF0000'>".aff("Le package ne respecte pas certaines regles:<br>".$rulesfr,"Package seems to not respect some rules:<br>".$rulesen,$iso_langue_en_cours)."</div>";
			echo "<br>";
			$upload=-1;
			$error++;
		}
	}

	if (! $error)
	{
		$newfilename = ProductDownload::getNewFilename(); // Return Sha1 file name
        //$newfilename = ProductDownload::getNewFilename()."_".intval($cookie->id_customer);
		$chemin_destination = _PS_DOWNLOAD_DIR_.$newfilename;
		
		$zip = new ZipArchive();
		$res = $zip->open($_FILES['virtual_product_file']['tmp_name']);
		if ($res === TRUE) 
		{
			$resarray=validateZipFile($zip,$originalfilename,$_FILES['virtual_product_file']['tmp_name']);
			$zip->close();
			$error=$resarray['error'];
			$upload=$resarray['upload'];
		}
		else 
		{
			echo "<div style='color:#FF0000'>File can't be analyzed. Is it a true zip file ?<br>";
			echo "If you think this is an error, send your package by email at contact@dolibarr.org";
			echo "</div>";
			$upload=-1;
			$error++;
		}
	}

	if (! $error)
	{
        prestalog("Move file ".$_FILES['virtual_product_file']['tmp_name']." to ".$chemin_destination);

		if (move_uploaded_file($_FILES['virtual_product_file']['tmp_name'], $chemin_destination) != true) 
		{
			echo "<div style='color:#FF0000'>file copy impossible for the moment, please try again later </div>";
			$upload=-1;
		}
		else
		{
			$upload=1;
		}
	}
}


//Mise a jour du produit
if ($_GET["upd"] == 1) {

	$flagError = 0;
	$status = $_POST['active'];
	$product_file_name = $_POST["product_file_name"];
	$product_file_path = $_POST["product_file_path"];

	prestalog("We click on 'Update this product' button: product_file_name=".$product_file_name." - product_file_path=".$product_file_path." - upload=".$upload);

	//prise des prix
	$prix_ttc = $_POST["priceTI"];
	$prix_ht = $prix_ttc;

	if ($status == "" || $prix_ht == "")
		$flagError = 1;

	//prise des libelles
	for ($x = 0; $languageTAB[$x]; $x++ ) {

		$product_name = $resume = $description = "";
		$product_name = $_POST["product_name_l".$languageTAB[$x]['id_lang']];
		$resume = $_POST["resume_".$languageTAB[$x]['id_lang']];
		$keywords = $_POST["keywords_".$languageTAB[$x]['id_lang']];
		$description = $_POST["description_".$languageTAB[$x]['id_lang']];

		if ($languageTAB[$x]['iso_code'] == "en" && ($product_name == "" || $resume == "" || $description == "" || $keywords == "")) {
			$flagError = 1;
		} else {

			if ($languageTAB[$x]['iso_code'] != "en" && $product_name == "") {
				$product_name = $product_nameTAB[0];
			}
			if ($languageTAB[$x]['iso_code'] != "en" && $resume == "") {
				$resume = $resumeTAB[0];
			}
			if ($languageTAB[$x]['iso_code'] != "en" && $description == "") {
				$description = $descriptionTAB[0];
			}
			if ($languageTAB[$x]['iso_code'] != "en" && $keywords == "") {
				$keywords = $keywordsTAB[0];
			}
		}

		$product_nameTAB[$x] = $product_name;
		$resumeTAB[$x] = $resume;
		$keywordsTAB[$x] = $keywords;
		$descriptionTAB[$x] = $description;
	}

	//recuperation de la categorie par defaut
	$categories = Category::getSimpleCategories($cookie->id_lang);
	foreach ($categories AS $categorie) {
		if ($_POST['categories_checkbox_'.$categorie['id_category']] == 1) {
			$id_categorie_default = $categorie['id_category'];
			break;
		}
	}
	if ($id_categorie_default == "") $flagError = 3;


	//si pas derreur de saisis, traitement en base
	if ($flagError == 0) {

		$taxe_id = 0;

		//prise des date
		$dateToday = date ("Y-m-d");
		$dateNow = date ("Y-m-d H:i:s");
		$dateRef = date ("YmdHis");

		//reference du produit
		$reference = 'c'.$cookie->id_customer.'d'.$dateRef;

		$qty=1000;
		if ($prix_ttc == 0) $qty=0;

		//Mise a jour du produit en base
		$query = 'UPDATE `'._DB_PREFIX_.'product` SET
				`id_tax`				= '.$taxe_id.',
				`id_category_default` 	= '.$id_categorie_default.',
				`quantity` 				= '.$qty.',
				`price` 				= '.$prix_ht.',
				`wholesale_price` 		= '.$prix_ttc.',
				`reduction_from` 		= \''.$dateToday.'\',
				`reduction_to` 			= \''.$dateToday.'\',
				`reference` 			= \''.$reference.'\',
				`active` 				= '.$status.',
				`indexed` 				= 1,
				`date_upd` 				= \''.$dateNow.'\'
				WHERE `id_product` = '.$product_id.' ';
		$result = Db::getInstance()->ExecuteS($query);
		if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));


		//mise en base des libelle anglais et fr et autre s'il y a
		for ($x = 0; $product_nameTAB[$x]; $x++)
		{
			$languageTAB[$x]['id_lang'];
			$languageTAB[$x]['name'];

            $link_rewrite = preg_replace('/[^a-zA-Z0-9-]/','-', $product_nameTAB[$x]);

			$query = "UPDATE `"._DB_PREFIX_."product_lang` SET
			`description`		= '".addslashes($descriptionTAB[$x])."',
			`description_short`	= '".addslashes($resumeTAB[$x])."',
			`link_rewrite`		= '".addslashes($link_rewrite)."',
			`meta_description`	= '".addslashes($product_nameTAB[$x])."',
			`meta_keywords`		= '".addslashes($keywordsTAB[$x])."',
			`meta_title`		= '".addslashes($product_nameTAB[$x])."',
			`name`				= '".addslashes($product_nameTAB[$x])."',
			`available_now`		= '".aff("Disponible", "Available", $iso_langue_en_cours)."',
			`available_later`	= '".aff("En fabrication", "In build", $iso_langue_en_cours)."'
			WHERE `id_lang`	= ".$languageTAB[$x]['id_lang']."
			AND `id_product` = ".$product_id;

			$result = Db::getInstance()->ExecuteS($query);
			if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query! : '.$query));
		}

		$newfile = ($product_file_path?1:0);

		$id_product = $product_id;

		// Delete tag description of product
		$query = "DELETE FROM "._DB_PREFIX_."product_tag WHERE id_product = ".$id_product;
		$result = Db::getInstance()->ExecuteS($query);
		prestalog("We delete all links to tags for id_product ".$id_product);

		// Add tag description of product
		for ($x = 0; $product_nameTAB[$x]; $x++)
		{
			$id_lang=$languageTAB[$x]['id_lang'];
			$tags=preg_split('/[\s,]+/',$keywordsTAB[$x]);
			foreach($tags as $tag)
			{
				$id_tag=0;

				// Search existing tag
				$query = 'SELECT id_tag FROM '._DB_PREFIX_.'tag
				WHERE id_lang = \''.$id_lang.'\'
				AND name = \''.addslashes($tag).'\' ';
				$result = Db::getInstance()->ExecuteS($query);
				if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
				foreach ($result AS $row)
				{
					$id_tag = $row['id_tag'];
					prestalog("tag id for id_lang ".$id_lang.", name ".$tag." is ".$id_tag);
				}

				if (empty($id_tag))
				{
					$query = "INSERT INTO "._DB_PREFIX_."tag(id_lang, name) VALUES ('".$id_lang."', '".addslashes($tag)."')";
					$result = Db::getInstance()->ExecuteS($query);
					//if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query! : '.$query));

					$id_tag = Db::getInstance()->Insert_ID();
					prestalog("We created tag for id_lang ".$id_lang.", name ".$tag.", id is ".$id_tag);
				}

				if (! empty($id_tag) && $id_tag > 0)
				{
					// Add tag link
					$query = "INSERT INTO "._DB_PREFIX_."product_tag(id_product, id_tag) VALUES ('".$id_product."', '".$id_tag."')";
					$result = Db::getInstance()->ExecuteS($query);

					prestalog("We insert link id_product ".$id_product.", id_tag ".$id_tag);
				}
			}
		}

		//mise en base du lien avec le produit telechargeable
		if ($newfile)
		{
			$product_file_newname = basename($product_file_path);

			$query = 'DELETE FROM `'._DB_PREFIX_.'product_download` WHERE `id_product` = '.$product_id.';';
			$result1 = Db::getInstance()->ExecuteS($query);

			$query = 'INSERT INTO `'._DB_PREFIX_.'product_download` (`id_product`, `display_filename`, `physically_filename`, `date_deposit`, `date_expiration`, `nb_days_accessible`, `nb_downloadable`, `active`) VALUES (
			'.$product_id.', "'.$product_file_name.'", "'.$product_file_newname.'", "'.$dateNow.'", "0000-00-00 00:00:00", 3650, 0, 1
			)';
			prestalog("A new file is asked: We add it into product_download query=".$query);
			$result = Db::getInstance()->ExecuteS($query);
			if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query! : '.$query));
		} 
		else
		{ 
			//recup des infos fichier
			$query = 'SELECT `display_filename`, `physically_filename` FROM `'._DB_PREFIX_.'product_download`
						  WHERE `id_product` = '.$product_id.' ';
			prestalog("No new file, we search old value query=".$query);
			$result = Db::getInstance()->ExecuteS($query);
			if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
			foreach ($result AS $row) 
			{
				$product_file_name = $row['display_filename'];
				$product_file_path = $row['physically_filename'];
			}
		}

		//gestion des fichiers selon prix
		$oldPrice = round($_GET["op"],2);
		$newPrice =  round($_POST["priceTI"],2);

		// Si un fichier a ete modifier ou le prix modifie
		if ($newfile || $oldPrice != $newPrice) 
		{
			if ($newfile || $newPrice > 0) 
			{
				//delete des attachments
				$query = 'SELECT `id_attachment` FROM `'._DB_PREFIX_.'product_attachment`
						WHERE `id_product` = '.$product_id.' ';
				prestalog("Search list of attachment to delete sql=".$query);
				$result = Db::getInstance()->ExecuteS($query);
				if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
				foreach ($result AS $row)
				{
					$id_attachment =  $row['id_attachment'];
					prestalog("Delete attachment num ".$id_attachment);

					if ($id_attachment > 0) 
					{
						$query = 'DELETE FROM `'._DB_PREFIX_.'attachment` WHERE `id_attachment` = '.$id_attachment.';';
						$result1 = Db::getInstance()->ExecuteS($query);

						$query = 'DELETE FROM `'._DB_PREFIX_.'attachment_lang` WHERE `id_attachment` = '.$id_attachment.';';
						$result2 = Db::getInstance()->ExecuteS($query);

						$query = 'DELETE FROM `'._DB_PREFIX_.'product_attachment` WHERE `id_attachment` = '.$id_attachment.';';
						$result3 = Db::getInstance()->ExecuteS($query);
					}
				}
			}
			if (($newfile && $newPrice == 0) || ($newPrice == 0 && $oldPrice > 0)) 
			{
				$product_file_newname = basename($product_file_path);

				//creation dun attachement
				$query = 'INSERT INTO `'._DB_PREFIX_.'attachment` (`file`, `mime`) VALUES ("'.$product_file_newname.'", "binary/octet-stream");';
				prestalog("Add attachment sql=".$query);
				$result = Db::getInstance()->ExecuteS($query);

				$query = 'SELECT `id_attachment` FROM `'._DB_PREFIX_.'attachment`
				WHERE `file` = "'.$product_file_newname.'"';
				$result = Db::getInstance()->ExecuteS($query);
				if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
				foreach ($result AS $row)
				{
					$id_attachment = $row['id_attachment'];
					prestalog("Add attachment for num ".$id_attachment);

					for ($x = 0; $languageTAB[$x]; $x++ ) {
						$id_lang = $languageTAB[$x]['id_lang'];
						$query = 'INSERT INTO `'._DB_PREFIX_.'attachment_lang` (`id_attachment`, `id_lang`, `name`, `description`) VALUES ('.$id_attachment.', '.$id_lang.', "'.$product_file_name.'", "")';
						$result = Db::getInstance()->ExecuteS($query);
					}

					$query = 'INSERT INTO `'._DB_PREFIX_.'product_attachment` (`id_product`, `id_attachment`) VALUES ('.$product_id.', '.$id_attachment.')';
					$result = Db::getInstance()->ExecuteS($query);
				}
			}
		}

		//inscription du produit dans ttes les categories choisis
		$query = 'DELETE FROM `'._DB_PREFIX_.'category_product` WHERE `id_product` = '.$product_id;
		$query.= " AND `id_category` <> 1";	// If product was on home, we keep it on home.
		prestalog("Delete category of product sql=".$query);
		$result = Db::getInstance()->ExecuteS($query);
		if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query! : '.$query));

		$categories = Category::getSimpleCategories($cookie->id_lang);
	    foreach ($categories AS $categorie) {

			if ($_POST['categories_checkbox_'.$categorie['id_category']] == 1) {
				$query = 'INSERT INTO `'._DB_PREFIX_.'category_product` (`id_category`, `id_product`, `position`) VALUES
						('.$categorie['id_category'].', '.$product_id.', 1);';
				prestalog("Add category of product sql=".$query);
				$result = Db::getInstance()->ExecuteS($query);
				if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query! : '.$query));
			}
		}

		//echo "<script>window.location='./my-sales-manage-product.php?id_p=$id_product';</script>";
	}

	if ($flagError == 1) {
		echo "<div style='color:#FF0000'>";echo aff("Tous les champs Anglais sont obligatoires.", "All English fields are required.", $iso_langue_en_cours); echo " </div>";
	}

	if ($flagError == 3) {
		echo "<div style='color:#FF0000'>";echo aff("Vous devez choisir une categorie", "You have to choose a category", $iso_langue_en_cours); echo " </div><br>";
	}

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


$query = 'SELECT `id_lang`, `description`, `description_short`, `link_rewrite`, `meta_description`, `meta_keywords`, `meta_title`, `name`, `available_now`, `available_later` FROM `'._DB_PREFIX_.'product_lang`
		  WHERE `id_product` = '.$product_id.' ';
$result = Db::getInstance()->ExecuteS($query);
if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
foreach ($result AS $row) {

	$_POST["product_name_l".$row['id_lang']]= $row['name'];
	$_POST["resume_".$row['id_lang']] 		= $row['description_short'];
	$_POST["keywords_".$row['id_lang']] 	= $row['meta_keywords'];
	$_POST["description_".$row['id_lang']] 	= $row['description'];

}


$query = 'SELECT `id_category`, `position` FROM `'._DB_PREFIX_.'category_product`
		  WHERE `id_product` = '.$product_id.' ';
$result = Db::getInstance()->ExecuteS($query);
if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
foreach ($result AS $row) {
	$id_category = $row['id_category'];
	$_POST['categories_checkbox_'.$id_category] = 1;
}

$query = 'SELECT `display_filename`, `physically_filename` FROM `'._DB_PREFIX_.'product_download`
		  WHERE `id_product` = '.$product_id.' ';
$result = Db::getInstance()->ExecuteS($query);
if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
foreach ($result AS $row) {
	$file_name =  $row['display_filename'];
}


/*
 *	View
 */

echo aff("<h2>Modifier mes modules/produits</h2>", "<h2>Update a module/plugin</h2>", $iso_langue_en_cours);
?>
<br />

<?php
print aff(
'Toute vente sera d\'abord encaissée par l\'association Dolibarr. Tous les semestres, vous pouvez, via votre compte, réclamer le montant encaissé qui vous sera reversé (L\'association prenant '.(100-$commission).'% pour soutenir le développement du projet Dolibarr ERP/CRM)...',
'Payment for any sell will be first received by the Dolibarr foundation. Every six month, from your account, you can ask your money back (The foundation redistribute '.$commission.'% of payments, the remaining '.(100-$commission).'% are kept to help the development of Dolibarr ERP/CRM project)...',
$iso_langue_en_cours);

echo '

<script type="text/javascript" src="'.__PS_BASE_URI__.'js/tinymce/jscripts/tiny_mce/jquery.tinymce.js"></script>
			<script type="text/javascript">
			function tinyMCEInit(element)
			{
				$().ready(function() {
					$(element).tinymce({
						// Location of TinyMCE script
						script_url : \''.__PS_BASE_URI__.'js/tinymce/jscripts/tiny_mce/tiny_mce.js\',
						// General options
						theme : "advanced",
						plugins : "safari,style,layer,table,advlink,inlinepopups,media,contextmenu,paste,directionality,fullscreen",

						// Theme options
						theme_advanced_buttons1 : "fullscreen,code,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,|,bullist,numlist,|,link,unlink,|,forecolor",
						theme_advanced_buttons2 : "",
						theme_advanced_buttons3 : "",

						theme_advanced_toolbar_location : "top",
						theme_advanced_toolbar_align : "left",
						width : "100%",
						theme_advanced_resizing : true,
						content_css : "'.__PS_BASE_URI__.'themes/'._THEME_NAME_.'/css/global.css",
						// Drop lists for link/image/media/template dialogs
						//template_external_list_url : "lists/template_list.js",
						external_link_list_url : "lists/link_list.js",
						external_image_list_url : "lists/image_list.js",
						//media_external_list_url : "lists/media_list.js",
						elements : "nourlconvert",
						convert_urls : false,
						language : "'.(file_exists(_PS_ROOT_DIR_.'/js/tinymce/jscripts/tiny_mce/langs/'.$iso_langue_en_cours.'.js') ? $iso_langue_en_cours : 'en').'"
					});
				});
			}
			tinyMCEInit(\'textarea.rte\');
			</script>

			<script language="javascript">
				function maxlength(text,length) {
					if(text.innerText.length>length)
						text.innerText=text.innerText.substr(0,length);
				}
			</script>

';

?>

<FORM name="fmysalesmodifiysubprod" method="POST" ENCTYPE="multipart/form-data" class="formsubmit">

<table width="100%" border="0" style="padding-bottom: 5px;">

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
    <td nowrap="nowrap" valign="top"><?php echo aff("Nom du module/produit", "Module/product name : ", $iso_langue_en_cours); ?> </td>
    <td>
    	<?php for ($x = 0; $languageTAB[$x]; $x++ ) { ?>
        	<input name="product_name_l<?php echo $languageTAB[$x]['id_lang']; ?>" type="text" size="22" maxlength="100" value="<?php echo $_POST["product_name_l".$languageTAB[$x]['id_lang']]; ?>" />
			<img src="<?php echo $languageTAB[$x]['img']; ?>" alt="<?php echo $languageTAB[$x]['iso_code']; ?>"> <?php echo $languageTAB[$x]['iso_code']; ?>
            <br />
        <?php } ?>
    </td>
  </tr>

  <tr>
    <td colspan="2"><hr></td>
  </tr>

  <tr>
    <td valign="top">Status : </td>
    <td>
    <input name="active" id="active_on" value="1" <?php if ($_POST['active'] == 1 || $_POST['active'] == "") echo "checked='checked'"; ?> type="radio" style="border:none">
    <img src="../../img/os/2.gif" alt="Enabled" title="Enabled" style="padding: 0px 5px;"> <?php echo aff("Actif", "Enabled", $iso_langue_en_cours); ?>
    <br />
	<input name="active" id="active_off" value="0" <?php if ($_POST['active'] == 0 && $_POST['active'] != "") echo "checked='checked'"; ?> type="radio" style="border:none">
    <img src="../../img/os/6.gif" alt="Disabled" title="Disabled" style="padding: 0px 5px;"> <?php echo aff("Inactif", "Disabled", $iso_langue_en_cours); ?>
    </td>
  </tr>


  <tr>
    <td colspan="2"><hr></td>
  </tr>


  <tr>
    <td nowrap="nowrap" valign="top"><?php echo aff("Package diffusé<br>(fichier .zip pour<br>un module ou theme)", "Distributed package<br>(.zip file for<br> a module or theme)", $iso_langue_en_cours); ?></td>
    <td>
	<?php echo $file_name; ?><br /><br />

        <?php
		if ($upload >= 0 && ($_POST["product_file_name"] != "" || $_FILES['virtual_product_file']['name'] != "")) 
		{
			if ($_POST["product_file_name"] != "") $file_name = $_POST["product_file_name"];
			if ($_FILES['virtual_product_file']['name'] != "") $file_name = $_FILES['virtual_product_file']['name'];
			echo aff("Fichier ".$file_name." prêt.","File ".$file_name." ready.",$iso_langue_en_cours);

		}
		else 
		{
		?>
			<?php echo aff("Nouveau: Taille maximal du fichier: ".ini_get('upload_max_filesize'),"New file: Maximum file size is: ".ini_get('upload_max_filesize'), $iso_langue_en_cours); ?>
            <br />
	        <input id="virtual_product_file" name="virtual_product_file" value="" class="" onchange="javascript:
    																					document.fmysalesmodifiysubprod.action='?up=1&id_p=<?php echo $product_id; ?>';
                                                                                        document.fmysalesmodifiysubprod.submit();" maxlength="10000000" type="file">
        	<?php
		}
		?>
		<br>
		<input type="hidden" name="product_file_name" id="product_file_name" value="<?php if ($_POST["product_file_name"] != "") echo $_POST["product_file_name"]; if ($_FILES['virtual_product_file']['name'] != "") echo $_FILES['virtual_product_file']['name']; ?>" >
		<input type="hidden" name="product_file_path" id="product_file_path" value="<?php if ($_POST["product_file_path"] != "") echo $_POST["product_file_path"]; if ($chemin_destination != "") echo $chemin_destination; ?>" >
    </td>
  </tr>


  <tr>
    <td colspan="2"><hr></td>
  </tr>


   <tr>
    <td nowrap="nowrap" valign="top"><?php echo aff("Prix de vente TTC : ", "Sale price (incl tax) : ", $iso_langue_en_cours); ?></td>
    <td>
        <input size="6" maxlength="6" name="priceTI" id="priceTI" value="<?php if ($_POST["priceTI"] != 0 && $_POST["priceTI"] != "") echo round($_POST["priceTI"],2); else print '0'; ?>" onkeyup="javascript:this.value = this.value.replace(/,/g, '.');" type="text"> Euros
    </td>
  </tr>


  <tr>
    <td colspan="2"><hr></td>
  </tr>



  <tr>
    <td width="14%" valign="top">
    <?php echo aff("Cocher toutes les categories dans lesquelles le produit apparaitra : ", "Mark all checkbox(es) of categories in which product is to appear : ", $iso_langue_en_cours); ?>
 	</td>
    <td width="86%">
		<?php

		echo '<table width="100%" border="0" cellspacing="5" cellpadding="0">';

        $categories = Category::getSimpleCategories($cookie->id_lang);

        $x = 0;
        foreach ($categories AS $categorie) {

			$query = 'SELECT `active` FROM `'._DB_PREFIX_.'category`
			WHERE `id_category` = \''.$categorie['id_category'].'\'
			';
			$result = Db::getInstance()->ExecuteS($query);
			if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
			foreach ($result AS $row)
				$active = $row['active'];

			if ($categorie['id_category'] > 1 && $active == 1) {

				if ($x%2)
				 $bgcolor="#FFF4EA";
				else
				 $bgcolor="#FFDBB7";

				echo '<tr bgcolor="'.$bgcolor.'">
						<td nowrap="nowrap" valign="top" align="left">
							<input name="categories_checkbox_'.$categorie['id_category'].'" type="checkbox" value="1" ';

				if ($_POST['categories_checkbox_'.$categorie['id_category']] == 1) echo " checked ";

				echo ' />
							'.$categorie['name'].'
						</td>
					</tr>';
				$x++;
			}
        }
		echo '</table>';
        ?>
       </td>
  </tr>


  <tr>
    <td colspan="2"><hr></td>
  </tr>



  <?php for ($x = 0; $languageTAB[$x]; $x++ ) { ?>
  <tr>
    <td colspan="2" nowrap="nowrap" valign="top"><?php echo aff("R&eacute;sum&eacute ", "Short description ", $iso_langue_en_cours); ?>
	(<img src="<?php echo $languageTAB[$x]['img']; ?>" alt="<?php echo $languageTAB[$x]['iso_code']; ?>">
		<?php echo $languageTAB[$x]['iso_code'];
			if ($languageTAB[$x]['iso_code'] == 'en') echo ', '.aff("obligatoire","mandatory",$iso_langue_en_cours);
			else echo ', '.aff("optionnel","optionnal",$iso_langue_en_cours);
			?>):
    <input type="text" id="resumeLength_<?php echo $languageTAB[$x]['id_lang']; ?>" value="400" size="2" width="3" style="border:0; font-size:10px; color:#333333;"> <?php echo aff("caractères restant", "characters left", $iso_langue_en_cours); ?>.
	</td>
  </tr>
  <tr>
    <td colspan="2" nowrap="nowrap">
        	<textarea id="resume_<?php echo $languageTAB[$x]['id_lang']; ?>" name="resume_<?php echo $languageTAB[$x]['id_lang']; ?>"
            onkeyup="javascript:resumeLength_<?php echo $languageTAB[$x]['id_lang']; ?>.value=parseInt(400-this.value.length); if(this.value.length>=400)this.value=this.value.substr(0,399);"
            onkeydown="javascript:resumeLength_<?php echo $languageTAB[$x]['id_lang']; ?>.value=parseInt(400-this.value.length); if(this.value.length>=400)this.value=this.value.substr(0,399);"
            onchange="javascript:resumeLength_<?php echo $languageTAB[$x]['id_lang']; ?>.value=parseInt(400-this.value.length); if(this.value.length>=400)this.value=this.value.substr(0,399);"
            cols="40" rows="3"><?php echo $_POST["resume_".$languageTAB[$x]['id_lang']]; ?></textarea>
			<br />
    </td>
  </tr>
  <?php } ?>


  <tr>
    <td colspan="2"><hr></td>
  </tr>

  <tr>
    <td nowrap="nowrap" valign="top"><?php echo aff("Mots cl&eacute;s : ", "Keywords : ", $iso_langue_en_cours); ?></td>
    <td nowrap="nowrap">
        <?php for ($x = 0; $languageTAB[$x]; $x++ ) { ?>
        	<input name="keywords_<?php echo $languageTAB[$x]['id_lang']; ?>" type="text" size="26" maxlength="100" value="<?php echo $_POST["keywords_".$languageTAB[$x]['id_lang']]; ?>" />
			<img src="<?php echo $languageTAB[$x]['img']; ?>" alt="<?php echo $languageTAB[$x]['iso_code']; ?>"> <?php echo $languageTAB[$x]['iso_code']; ?>
            <br />
        <?php } ?>
    </td>
  </tr>

  <tr>
    <td colspan="2"><hr></td>
  </tr>

  <?php for ($x = 0; $languageTAB[$x]; $x++ ) { ?>
    <tr>
        <td colspan="2">
        	<?php echo aff("Description large : ", "Large description : ", $iso_langue_en_cours); ?>
            (<img src="<?php echo $languageTAB[$x]['img']; ?>" alt="<?php echo $languageTAB[$x]['iso_code']; ?>">
			<?php echo $languageTAB[$x]['iso_code'];
			if ($languageTAB[$x]['iso_code'] == 'en') echo ', '.aff("obligatoire","mandatory",$iso_langue_en_cours);
			else echo ', '.aff("optionnel","optionnal",$iso_langue_en_cours);
			?>):
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <textarea class="rte" cols="100" rows="10"
			  id="description_<?php echo $languageTAB[$x]['id_lang']; ?>"
			name="description_<?php echo $languageTAB[$x]['id_lang']; ?>">
			<?php

			$publisher=trim($cookie->customer_firstname.' '.$cookie->customer_lastname);
			$defaulten='
Module version: <strong>1.0</strong><br>
Publisher/Licence: <strong>'.$publisher.'</strong> / <strong>GPL</strong><br>
User interface language: <strong>English</strong><br>
Help/Support: <strong>None / <strike>Forum www.dolibarr.org</strike> / <strike>Mail to contact@publisher.com</strike></strong><br>
</ul>
Prerequisites:<br>
<ul>
<li> Dolibarr version: <strong>'.$minversion.'+</strong> </li>
</ul>
<p>Install:</p>
<ul>
<li> Download the archive file of module (.zip file) from web site <a title="http://www.dolistore.com" rel="nofollow" href="http://www.dolistore.com/" target="_blank">DoliStore.com</a> </li>
<li> Put the file into the root directory of Dolibarr. </li>
<li> Uncompress the zip file, for example with command </li>
<div style="text-align: left;" dir="ltr">
<div style="font-family: monospace;">
<pre><span>tar</span> <span>-xvf</span> '.($file_name?$file_name:'modulefile.zip').'</pre>
</div>
</div>
<li> Module or skin is then available and can be activated. </li>
</ul>';
			$defaultfr='
Module version: <strong>1.0</strong><br>
Editeur/Licence: <strong>'.$publisher.'</strong> / <strong>GPL</strong><br>
Langage interface: <strong>Anglais</strong><br>
Assistance: <strong>Aucune / <strike>Forum www.dolibarr.org</strike> / <strike>Par mail à contact@editeur.com</strike></strong><br>
Pr&eacute;requis: <br>
<ul>
<li> Dolibarr version: <strong>'.$minversion.'+</strong> </li>
</ul>
Installation:<br>
<ul>
<li> T&eacute;l&eacute;charger le fichier archive du module (.zip) depuis le site  web <a title="http://www.dolistore.com" rel="nofollow" href="http://www.dolistore.com/" target="_blank">DoliStore.com</a> </li>
<li> Placer le fichier dans le r&eacute;pertoire racine de dolibarr. </li>
<li> Decompressez le fichier zip, par exemple par la commande </li>
<div style="text-align: left;" dir="ltr">
<div style="font-family: monospace;">
<pre><span>tar</span> <span>-xvf</span> '.($file_name?$file_name:'fichiermodule.zip').'</pre>
</div>
</div>
<li> Le module ou thème est alors disponible et activable. </li>
</ul>';
			$defaultes='
Versión del Módulo: <strong>1.0</strong><br>
Creador/Licencia:  <strong>'.$publisher.'</strong> / <strong>GPL</strong><br>
Idioma interfaz usuario: <strong>Inglés</strong><br>
Ayuda/Soporte: <strong>No / <strike>foro www.dolibarr.org</strike> / <strike>mail a contacto@creador.com</strike></strong><br>
Prerrequisitos: <br>
<ul>   
<li> Versión Dolibarr: <strong>'.$minversion.'+</strong></li>
</ul>
Para instalar este módulo:<br>
<ul>
<li> Descargar el archivo del módulo (archivo .zip) desde la web <a title="http://www.dolistore.com" rel="nofollow" href="http://www.dolistore.com/" target="_blank">DoliStore.com</a> </li>
<li> Ponga el archivo en el directorio raíz de Dolibarr.</li>
<li> Descomprima el zip archivo, por ejamplo usando el comando</li>
<div style="text-align: left;" dir="ltr">
<div style="font-family: monospace;">
<pre><span>tar</span> <span>-xvf</span> '.($file_name?$file_name:'fichiermodule.zip').'</pre>
</div>
</div>
<li> El módulo o tema está listo para ser activado.</li>
</ul>';
			if (empty($_POST["description_".$languageTAB[$x]['id_lang']]))
			{
				if ($languageTAB[$x]['iso_code'] == 'en') print $defaulten;
				if ($languageTAB[$x]['iso_code'] == 'fr') print $defaultfr;
				if ($languageTAB[$x]['iso_code'] == 'es') print $defaultes;
			}
			else echo $_POST["description_".$languageTAB[$x]['id_lang']];

			?>
            </textarea>
        </td>
    </tr>
   <?php } ?>
  <tr>
    <td colspan="2"><br></td>
  </tr>
</table>




<table width="100%" border="0" cellspacing="10" cellpadding="0">
  <tr>
	    <td colspan="2" nowrap="nowrap" align="center">
		<button style="font-weight: 700;" type="button" onclick="javascript:
																 document.fmysalesmodifiysubprod.action='?upd=1&op=<?php echo $_POST["priceTI"]; ?>&id_p=<?php echo $product_id; ?>';
                                                                 document.fmysalesmodifiysubprod.submit();"
																 >
			<?php echo aff("Modifier ce produit", "Update this product", $iso_langue_en_cours); ?>
		</button>
		 &nbsp; &nbsp; &nbsp; &nbsp;
		<button type="button" onclick="JavaScript:alert('<?php echo aff("Enregistrement abandonné", "Recording canceled", $iso_langue_en_cours); ?>');
        											document.fmysalesmodifiysubprod.action='/modules/blockmysales/my-sales-manage-product.php';
                                                    document.fmysalesmodifiysubprod.submit();">
			<?php echo aff("Annuler", "Cancel", $iso_langue_en_cours); ?>
	</td>
  </tr>

</table>


</td>
</tr>
</table>

</FORM>


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
