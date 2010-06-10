<?php
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/../../init.php');


$id_langue_en_cours = $cookie->id_lang;

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




//upload du fichier
if ($_GET["up"] == 1) {
	if ($_FILES['virtual_product_file']['error']) {
			  switch ($_FILES['virtual_product_file']['error']){
					   case 1: // UPLOAD_ERR_INI_SIZE
					   echo "Le fichier depasse la limite autorisee par le serveur !";
					   break;
					   case 2: // UPLOAD_ERR_FORM_SIZE
					   echo "Le fichier depasse la limite autorisee dans le formulaire HTML !";
					   break;
					   case 3: // UPLOAD_ERR_PARTIAL
					   echo "L'envoi du fichier a ete interrompu pendant le transfert !";
					   break;
					   case 4: // UPLOAD_ERR_NO_FILE
					   echo "Le fichier que vous avez envoye a une taille nulle !";
					   break;
			  }
	}
	else {
		$chemin_destination = '../../download/897d277ed8393a0bf9c817ed57b1'.time()."c".intval($cookie->id_customer);
		
		if (move_uploaded_file($_FILES['virtual_product_file']['tmp_name'], $chemin_destination) != true) {
			echo "<div style='color:#FF0000'>file copy impossible for the moment, please try again later </div>";
		}

	}
}




//soumission du produit
if ($_GET["sub"] == 1) {

	$flagError = 0;
	$status = $_POST['active'];
	$product_file_name = $_POST["product_file_name"];
	$product_file_path = $_POST["product_file_path"];
	
	//prise des prix
	//$prix_ht = $_POST["priceTE"];
	$prix_ttc = $_POST["priceTI"];
	$prix_ht = $prix_ttc;
	
	if ($status == "" || $product_file_name == "" || $product_file_path == "" || $prix_ht == "" || $prix_ttc == "" ) 
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
		
		//recuperation de la taxe
		/*$taxe_input = $_POST["id_tax"];
		$taxes = Tax::getTaxes($cookie->id_lang);
					
		foreach ($taxes AS $taxe) {
			$taxVal = ($taxe['rate']/100);
			
			if ($taxVal == $taxe_input)
				$taxe_id = $taxe['id_tax'];
		}
		if ($taxe_id == "") $taxe_id = 0;*/
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
		
		//insertion du produit en base
		$query = 'INSERT INTO `'._DB_PREFIX_.'product` (
		`id_supplier`, `id_manufacturer`, `id_tax`, `id_category_default`, `id_color_default`, `on_sale`, `ean13`, `ecotax`, `quantity`, `price`, `wholesale_price`, `reduction_price`, `reduction_percent`, `reduction_from`, `reduction_to`, 
		`reference`, `supplier_reference`, `location`, `weight`, `out_of_stock`, `quantity_discount`, `customizable`, `uploadable_files`, `text_fields`, `active`, `indexed`, `date_add`, `date_upd`
		) VALUES (
		0, 0, '.$taxe_id.', '.$id_categorie_default.', 0, 0, \'\', 0.00, 1000, '.$prix_ht.', '.$prix_ttc.', 0.00, 0, \''.$dateToday.'\', \''.$dateToday.'\', 
		\''.$reference.'\', \'\', \'\', 0, 0, 0, 0, 0, 0, '.$status.', 1, \''.$dateNow.'\', \''.$dateNow.'\'
		)';
			
		$result = Db::getInstance()->ExecuteS($query);
		if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
		
		
		//recuperation de l'id du produit	
		$query = 'SELECT `id_product` FROM `'._DB_PREFIX_.'product` 
		WHERE `reference` = \''.$reference.'\' 
		AND `date_add` = \''.$dateNow.'\' ';
		$result = Db::getInstance()->ExecuteS($query);
		if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
		foreach ($result AS $row)
			$id_product = $row['id_product'];
		
				
		//mise en base des libelle anglais et fr et autre s'il y a
		for ($x = 0; $product_nameTAB[$x]; $x++) {			
			$languageTAB[$x]['id_lang'];
			$languageTAB[$x]['name'];
			
			$link_rewrite = str_replace(" ", "-", $product_nameTAB[$x]);
			$link_rewrite = str_replace("'", "-", $link_rewrite);
			$link_rewrite = str_replace('"', "-", $link_rewrite);
			$link_rewrite = str_replace("\\", "", $link_rewrite);
			$link_rewrite = str_replace("/", "", $link_rewrite);
			

			$query = 'INSERT INTO `'._DB_PREFIX_.'product_lang` (`id_product`, `id_lang`, `description`, `description_short`, `link_rewrite`, `meta_description`, `meta_keywords`, `meta_title`, `name`, `available_now`, `available_later`)
			 VALUES ('.$id_product.", ".$languageTAB[$x]['id_lang'].", '".addslashes($descriptionTAB[$x])."', '".addslashes($resumeTAB[$x])."', '".$link_rewrite."', '".addslashes($product_nameTAB[$x])."', '".addslashes($keywordsTAB[$x])."', '".addslashes($product_nameTAB[$x])."', '".addslashes($product_nameTAB[$x])."', '".aff("Disponible", "Available", $iso_langue_en_cours)."', '".aff("En fabrication", "In build", $iso_langue_en_cours)."')";
			$result = Db::getInstance()->ExecuteS($query);
			if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query! : '.$query));	
		}
		
		
		//mise en base du lien avec le produit telechargeable		
		$product_file_path = str_replace("../../download/", "", $product_file_path);
		
		$query = 'INSERT INTO `'._DB_PREFIX_.'product_download` (`id_product`, `display_filename`, `physically_filename`, `date_deposit`, `date_expiration`, `nb_days_accessible`, `nb_downloadable`, `active`) VALUES (
		'.$id_product.', "'.$product_file_name.'", "'.$product_file_path.'", "'.$dateNow.'", "0000-00-00 00:00:00", 3650, 0, '.$status.'
		)';
		$result = Db::getInstance()->ExecuteS($query);
		if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query! : '.$query));	
		
		
		//inscritption du produit dans ttes les categories choisis
		$categories = Category::getSimpleCategories($cookie->id_lang);
	    foreach ($categories AS $categorie) {
			
			if ($_POST['categories_checkbox_'.$categorie['id_category']] == 1) {
				$query = 'INSERT INTO `'._DB_PREFIX_.'category_product` (`id_category`, `id_product`, `position`) VALUES
						('.$categorie['id_category'].', '.$id_product.', 1);';
				$result = Db::getInstance()->ExecuteS($query);
				if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query! : '.$query));	
			}
		}
		
		echo "<script>window.location='./my-sales-images-product.php?id_p=$id_product';</script>";		
	}
}



//annuler le produit
if ($_GET['cel'] == 1) {
	unlink ($_POST["product_file_path"]);
	echo "<script>window.location='../../index.php';</script>";
}




function aff($lb_fr, $lb_other, $iso_langue_en_cours) {
	if ($iso_langue_en_cours == "fr") return $lb_fr; 
	else return $lb_other;
}


?>

	

<?php echo aff("<h2>Soumettre un module/produit</h2>", "<h2>Submit a module/plugin</h2>", $iso_langue_en_cours); ?>
<br />

<?php
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
						theme_advanced_buttons1 : "fullscreen,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,formatselect,fontselect,fontsizeselect,help",
						theme_advanced_buttons2 : "pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,cleanup,code,|,forecolor,backcolor",
						theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,media,image",
						theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,pagebreak",
						
						theme_advanced_toolbar_location : "top",
						theme_advanced_toolbar_align : "left",
						width : "100",
						theme_advanced_statusbar_location : "bottom",
						theme_advanced_resizing : true,
						content_css : "'.__PS_BASE_URI__.'themes/'._THEME_NAME_.'/css/global.css",
						// Drop lists for link/image/media/template dialogs
						template_external_list_url : "lists/template_list.js",
						external_link_list_url : "lists/link_list.js",
						external_image_list_url : "lists/image_list.js",
						media_external_list_url : "lists/media_list.js",
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

<FORM name="fmysalessubprod" method="POST" ENCTYPE="multipart/form-data" class="std">
<table width="100%" >  
<tr>
<td>

<table width="100%" border="0" cellspacing="10" cellpadding="0">  
  <tr>
    <td colspan="2"><hr></td>
  </tr>
  <tr>
    <td nowrap="nowrap" valign="top"><?php echo aff("Nom du module/produit", "Module/product name : ", $iso_langue_en_cours); ?> </td>
    <td>
    	<?php for ($x = 0; $languageTAB[$x]; $x++ ) { ?>
        	<input name="product_name_l<?php echo $languageTAB[$x]['id_lang']; ?>" type="text" size="30" maxlength="100" value="<?php echo $_POST["product_name_l".$languageTAB[$x]['id_lang']]; ?>" /> 
			<?php echo $languageTAB[$x]['iso_code']; ?> 
            <img src="<?php echo $languageTAB[$x]['img']; ?>" alt="<?php echo $languageTAB[$x]['iso_code']; ?>"><br /><br />
        <?php } ?>
        <br />
    </td>
  </tr>
  <tr>
    <td>Status : </td>
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
    <td nowrap="nowrap" valign="top"><?php echo aff("Package à diffuser<br>(fichier .tgz pour un module)", "Package to distribute<br>(.tgz file for a module)", $iso_langue_en_cours); ?></td>
    <td>
        <?php 
		if ($_POST["product_file_name"] != "" || $_FILES['virtual_product_file']['name'] != "") { 
			if ($_POST["product_file_name"] != "") $file_name = $_POST["product_file_name"];
			if ($_FILES['virtual_product_file']['name'] != "") $file_name = $_FILES['virtual_product_file']['name'];
			echo aff("Fichier ".$file_name." prêt.","File ".$file_name." ready.",$iso_langue_en_cours);
			
		} else { ?>
			<?php echo aff("Taille maximal du fichier: ".ini_get('upload_max_filesize'),"Maximum file size is: ".ini_get('upload_max_filesize'), $iso_langue_en_cours); ?>
            <br />
	        <input id="virtual_product_file" name="virtual_product_file" value="" class="" onchange="javascript:
    																					document.fmysalessubprod.action='?up=1';
                                                                                        document.fmysalessubprod.submit();" maxlength="10000000" type="file">
        	<?php 
		}
		?>
				
		<br><input type="hidden" name="product_file_name" id="product_file_name" value="<?php if ($_POST["product_file_name"] != "") echo $_POST["product_file_name"]; if ($_FILES['virtual_product_file']['name'] != "") echo $_FILES['virtual_product_file']['name'];?>" >
		<br><input type="hidden" name="product_file_path" id="product_file_path" value="<?php if ($_POST["product_file_path"] != "") echo $_POST["product_file_path"]; if ($chemin_destination != "") echo $chemin_destination;?>" >
    </td>
  </tr>


  <tr>
    <td colspan="2"><hr></td>
  </tr>


  <tr>
 <!--
    <td nowrap="nowrap" valign="top"><?php echo aff("Prix de vente hors taxe : ", "Pre-tax sale price : ", $iso_langue_en_cours); ?> </td>
    <td>
      <input size="11" maxlength="14" id="priceTE" name="priceTE" onkeyup="javascript:this.value = this.value.replace(/,/g, '.'); priceTI.value=this.value;" type="text"
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
        <input size="11" maxlength="14" name="priceTI" id="priceTI" value="<?php if ($_POST["priceTI"] != 0 && $_POST["priceTI"] != "") echo $_POST["priceTI"]; ?>" onkeyup="javascript:this.value = this.value.replace(/,/g, '.');" type="text">
		<?php print aff(" Euros (0 si gratuit)"," Euros (0 if free)", $iso_langue_en_cours); ?>
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
	<i>(<?php echo $languageTAB[$x]['iso_code']; ?> 
            <img src="<?php echo $languageTAB[$x]['img']; ?>" alt="<?php echo $languageTAB[$x]['iso_code']; ?>">):</i>
	</td>
  </tr>
  <tr>
    <td colspan="2" nowrap="nowrap">    	
        	<textarea id="resume_<?php echo $languageTAB[$x]['id_lang']; ?>" name="resume_<?php echo $languageTAB[$x]['id_lang']; ?>" cols="40" rows="3" ><?php echo $_POST["resume_".$languageTAB[$x]['id_lang']]; ?></textarea> 
			<br />
    </td>
  </tr>
  <?php } ?>

  <tr>
    <td colspan="2"><hr></td>
  </tr>

  <tr>
    <td nowrap="nowrap" valign="top"><?php echo aff("Mots cl&eacute;s <i>(utilis&eacute; par les <br> moteurs de recherches)</i>: ", "Keywords <i>(used by search engines)</i> : ", $iso_langue_en_cours); ?></td>
    <td nowrap="nowrap">    	
        <?php for ($x = 0; $languageTAB[$x]; $x++ ) { ?>
        	<input name="keywords_<?php echo $languageTAB[$x]['id_lang']; ?>" type="text" size="32" maxlength="100" value="<?php echo $_POST["keywords_".$languageTAB[$x]['id_lang']]; ?>" /> 
			<?php echo $languageTAB[$x]['iso_code']; ?> 
            <img src="<?php echo $languageTAB[$x]['img']; ?>" alt="<?php echo $languageTAB[$x]['iso_code']; ?>"><br /><br />
        <?php } ?>
    </td>
  </tr>

  <tr>
    <td colspan="2"><hr></td>
  </tr>
  
  <?php for ($x = 0; $languageTAB[$x]; $x++ ) { ?>
    <tr>
        <td colspan="2">
        	<?php echo aff("Description large ", "Large description ", $iso_langue_en_cours); ?> 
            <i>(<img src="<?php echo $languageTAB[$x]['img']; ?>" alt="<?php echo $languageTAB[$x]['iso_code']; ?>"> <?php echo $languageTAB[$x]['iso_code']; ?>)</i> :
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
<p>Licence: <strong>GPL</strong></p>
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
<p>License: <strong>GPL</strong></p>
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
    <td colspan="2"><hr></td>
  </tr>
</table>




<table width="100%" border="0" cellspacing="10" cellpadding="0">  
  <tr>
	    <td colspan="2" nowrap="nowrap" align="center">
		<button style="font-weight: 700;" type="button" onclick="javascript:
																 document.fmysalessubprod.action='?sub=1';
                                                                 document.fmysalessubprod.submit();"
																 >
			<?php echo aff("Valider ce produit", "Submit this product", $iso_langue_en_cours); ?>
		</button>
		 &nbsp; &nbsp; &nbsp; &nbsp;
		<button type="button" onclick="JavaScript:alert('<?php echo aff("Enregistrement abandonné", "Record canceled", $iso_langue_en_cours); ?>'); 
        											document.fmysalessubprod.action='?cel=1';
                                                    document.fmysalessubprod.submit();">
			<?php echo aff("Annuler", "Cancel", $iso_langue_en_cours); ?>
		</button>
	</td>
  </tr>
  
  <tr>
    <td colspan="2"><hr></td>
  </tr>
</table>


</td>
</tr>
</table>


</FORM>
<?php
include(dirname(__FILE__).'/../../footer.php');
?>