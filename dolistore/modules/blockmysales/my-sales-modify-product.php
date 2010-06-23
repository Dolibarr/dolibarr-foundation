<?php

/* SSL Management */
$useSSL = true;

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/../../init.php');


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
	if ($iso_langue_en_cours == "fr") return $lb_fr; 
	else return $lb_other;
}




//test de l'appartenance 
if (testProductAppartenance($customer_id, $product_id)) {


/*
 * Actions
 */


//Mise a jour du produit
if ($_GET["upd"] == 1) {

	$flagError = 0;
	$status = $_POST['active'];
	
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
		
		if ($product_name == "" || $resume == "" || $description == "") {
			$flagError = 1;
		}
		
		$product_nameTAB[$x] = $product_name;
		$resumeTAB[$x] = $resume;
		$keywordsTAB[$x] = $keywords;
		$descriptionTAB[$x] = $description;
	}
	
	
	if ($flagError == 1) {
		echo "<div style='color:#FF0000'>";echo aff("Tous les champs sont obligatoires.", "All fields are required.", $iso_langue_en_cours); echo " </div>";
	}
	
	//si pas derreur de saisis, traitement en base
	if ($flagError == 0) {
		
		$taxe_id = 0;
		
		//recuperation de la categorie par defaut
		$categories = Category::getSimpleCategories($cookie->id_lang);
	    foreach ($categories AS $categorie) {
			if ($_POST['categories_checkbox_'.$categorie['id_category']] == 1) {
				$id_categorie_default = $categorie['id_category'];
				break;
			}
		}
		
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
		for ($x = 0; $product_nameTAB[$x]; $x++) {			
			$languageTAB[$x]['id_lang'];
			$languageTAB[$x]['name'];
			
			$link_rewrite = str_replace(" ", "-", $product_nameTAB[$x]);
			$link_rewrite = str_replace("'", "-", $link_rewrite);
			$link_rewrite = str_replace('"', "-", $link_rewrite);
			$link_rewrite = str_replace("\\", "", $link_rewrite);
			$link_rewrite = str_replace("/", "", $link_rewrite);
			

			$query = "UPDATE `"._DB_PREFIX_."product_lang` SET 			
			`description`		= '".addslashes($descriptionTAB[$x])."', 
			`description_short`	= '".addslashes($resumeTAB[$x])."',  
			`link_rewrite`		= '".$link_rewrite."',  
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
		
		
		if ($prix_ttc == 0) 
		{
			//cree lien fichier vers fichiers joint
			
		}
		else
		{
			//supprime lien fichier vers fichiers joint
		

		}
		
		//inscription du produit dans ttes les categories choisis
		$query = 'DELETE FROM `'._DB_PREFIX_.'category_product` WHERE `id_product` = '.$product_id;
		$query.= " AND `id_category` <> 1";	// If product was on home, we keep it on home.
		$result = Db::getInstance()->ExecuteS($query);
		if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query! : '.$query));	
		
		$categories = Category::getSimpleCategories($cookie->id_lang);
	    foreach ($categories AS $categorie) {
			
			if ($_POST['categories_checkbox_'.$categorie['id_category']] == 1) {
				$query = 'INSERT INTO `'._DB_PREFIX_.'category_product` (`id_category`, `id_product`, `position`) VALUES
						('.$categorie['id_category'].', '.$product_id.', 1);';
				$result = Db::getInstance()->ExecuteS($query);
				if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query! : '.$query));	
			}
		}
		
		echo "<script>window.location='./my-sales-manage-product.php?id_p=$id_product';</script>";		
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
print aff('Payment for any sell will be first received by the Dolibarr foundation. Every six month, from your account, you can ask your money back (The foundation redistribute 70% of payments, the remaining 30% are kept to help the development of Dolibarr ERP/CRM project)...',
'Toute vente sera d\'abord encaissée par l\'association Dolibarr. Tous les semestres, vous pouvez, via votre compte, réclamer le montant encaissé qui vous sera reversé (L\'association prenant 30% pour soutenir le développement du projet Dolibarr ERP/CRM)...',
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
						plugins : "safari,pagebreak,style,layer,table,advimage,advlink,inlinepopups,media,searchreplace,contextmenu,paste,directionality,fullscreen",

						// Theme options
						theme_advanced_buttons1 : "fullscreen,code,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,formatselect,fontsizeselect",
						theme_advanced_buttons2 : "search,replace,|,bullist,numlist,|,outdent,indent,|,link,unlink,anchor,|,forecolor,backcolor",
						theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,|,sub,sup,|,image,|,pagebreak",
						
						theme_advanced_toolbar_location : "top",
						theme_advanced_toolbar_align : "left",
						width : "100",
						theme_advanced_statusbar_location : "bottom",
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
    <td nowrap="nowrap" valign="top"><?php echo aff("Package diffusé (non modifiable)", "Distributed package (no upgradable)", $iso_langue_en_cours); ?></td>
    <td>
    &nbsp; <?php echo $file_name; ?>
    </td>
  </tr>


  <tr>
    <td colspan="2"><hr></td>
  </tr>


  <tr>
 <!--
    <td nowrap="nowrap" valign="top"><?php echo aff("Prix de vente hors taxe : ", "Pre-tax sale price : ", $iso_langue_en_cours); ?> </td>
    <td>
      <input size="6" maxlength="6" id="priceTE" name="priceTE" onkeyup="javascript:this.value = this.value.replace(/,/g, '.'); priceTI.value=this.value;" type="text"
	   value="<?php if ($_POST["priceTE"] != 0 && $_POST["priceTE"] != "") echo $_POST["priceTE"]; ?>"
	  > Euros 
    </td>
  </tr>
  
  <tr>
    <td nowrap="nowrap" valign="top"><?php echo aff("Taxe : ", "Tax : ", $iso_langue_en_cours); ?></td>
    <td>
    
    <?php
    
		$taxes = Tax::getTaxes($cookie->id_lang);
			
		echo '<select onchange="javascript:priceTI.value=(parseFloat(priceTE.value)+parseFloat(priceTE.value*this.value));" name="id_tax" id="id_tax">';
			echo '<option value="0"';  if ($_POST["id_tax"] == 0 || $_POST["id_tax"] == "") echo "selected='selected'"; echo '>'; echo aff("Sans taxe", "No tax", $iso_langue_en_cours); echo '</option>';
				
			foreach ($taxes AS $taxe) {			
				$taxVal = ($taxe['rate']/100);
				echo '<option value="'.$taxVal.'"';  if ($_POST["id_tax"] == $taxVal) echo "selected='selected'"; echo '>'.$taxe['name'].'</option>';
			}
		echo '</select>';
	?>
    </td>
  </tr>
  --> 	
  
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
    <td width="14%" valign="top" nowrap="nowrap">
    <?php echo aff("Cocher toutes les categories<br />dans lesquelles le produit apparaitra : ", "Mark all checkbox(es) of categories<br />in which product is to appear : ", $iso_langue_en_cours); ?>
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
			if ($languageTAB[$x]['iso_code'] == 'fr') echo ', '.aff("optionnel","optionnal",$iso_langue_en_cours);
			?>):
	</td>
  </tr>
  <tr>
    <td colspan="2" nowrap="nowrap">    	
        	<textarea class="rte" id="resume_<?php echo $languageTAB[$x]['id_lang']; ?>" name="resume_<?php echo $languageTAB[$x]['id_lang']; ?>" cols="40" rows="3" ><?php echo $_POST["resume_".$languageTAB[$x]['id_lang']]; ?></textarea> 
			<br />
    </td>
  </tr>
  <?php } ?>


  <tr>
    <td colspan="2"><hr></td>
  </tr>

  <tr>
    <td nowrap="nowrap" valign="top"><?php echo aff("Mots cl&eacute;s <i>(utilis&eacute; par les<br>moteurs de recherches)</i>: ", "Keywords <i>(used by search<br>engines)</i> : ", $iso_langue_en_cours); ?></td>
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
			if ($languageTAB[$x]['iso_code'] == 'fr') echo ', '.aff("optionnel","optionnal",$iso_langue_en_cours);
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
			$defaultfr='
<p>Module version: <strong>1.0</strong></p>
<p>Editeur: <strong>'.$publisher.'</strong></p>
<p>Pr&eacute;requis: </p>
<ul>
<li> Dolibarr version: <strong>2.8+</strong> </li>
</ul>
<p>Installation:</p>
<ul>
<li> T&eacute;l&eacute;charger le fichier archive du module (.tgz) depuis le site  web <a title="http://www.dolistore.com" rel="nofollow" href="http://www.dolistore.com/" target="_blank">DoliStore.com</a> </li>
<li> Placer le fichier dans le r&eacute;pertoire racine de dolibarr. </li>
<li> Decompressez le fichier par la commande </li>
</ul>
<div style="text-align: left;" dir="ltr">
<div style="font-family: monospace;">
<pre><span>tar</span> <span>-xvf</span> '.($file_name?$file_name:'fichiermodule.tgz').'</pre>
</div>
</div>
<ul>
<li> Le module ou thème est alors disponible et activable. </li>
</ul>';
			$defaulten='
<p>Module version: <strong>1.0</strong></p>
<p>Publisher: <strong>'.$publisher.'</strong></p>
<p>Prerequisites: </p>
<ul>
<li> Dolibarr version: <strong>2.8+</strong> </li>
</ul>
<p>Install:</p>
<ul>
<li> Download the archive file of module (.tgz file) from web site <a title="http://www.dolistore.com" rel="nofollow" href="http://www.dolistore.com/" target="_blank">DoliStore.com</a> </li>
<li> Put the file into the root directory of Dolibarr. </li>
<li> Uncompress the file with command </li>
</ul>
<div style="text-align: left;" dir="ltr">
<div style="font-family: monospace;">
<pre><span>tar</span> <span>-xvf</span> '.($file_name?$file_name:'modulefile.tgz').'</pre>
</div>
</div>
<ul>
<li> Module or skin is then available and can be activated. </li>
</ul>';
			if (empty($_POST["description_".$languageTAB[$x]['id_lang']]))
			{
				if ($languageTAB[$x]['iso_code'] == 'en') print $defaulten;
				if ($languageTAB[$x]['iso_code'] == 'fr') print $defaultfr;
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
																 document.fmysalesmodifiysubprod.action='?upd=1&id_p=<?php echo $_GET['id_p']; ?>';
                                                                 document.fmysalesmodifiysubprod.submit();"
																 >
			<?php echo aff("Modifier ce produit", "Update this product", $iso_langue_en_cours); ?>
		</button>
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