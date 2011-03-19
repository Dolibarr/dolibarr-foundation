<?php

/* SSL Management */
$useSSL = true;

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/../../init.php');
include(dirname(__FILE__).'/lib.php');


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

	//echo $languageTAB[$x]['id_lang']." | ".$languageTAB[$x]['name']." | ".$languageTAB[$x]['iso_code']." <br>";

	$x++;
}




/*
 * View
 */

echo aff("<h2>Soumettre un module/produit</h2>", "<h2>Submit a module/plugin</h2>", $iso_langue_en_cours);
?>
<br />

<?php
print aff(
'Pour soumettre votre propre module/extension/fichier sur DoliStore (en vente ou gracieusement), il vous faut d\'abord vous logguer sous votre compte (ou créer un compte si vous n\'en avez pas).',
'To submit your own module/addon/file for download on DoliStore, you must first login to your account (or create one if you don\'t have one).',
$iso_langue_en_cours);

print '<br><br><hr><br>';

print aff(
'<a href="/my-account.php">Cliquer ici pour cela...</a>',
'<a href="/my-account.php">Click here for this...</a>',
$iso_langue_en_cours);


include(dirname(__FILE__).'/../../footer.php');
?>
