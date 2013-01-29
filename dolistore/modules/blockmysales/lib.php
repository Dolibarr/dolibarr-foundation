<?php


/**
 *
 */
function aff($lb_fr, $lb_other, $iso_langue_en_cours) {
	if ($iso_langue_en_cours == "fr") return $lb_fr;
	else return $lb_other;
}


/**
 *
 */
function prestalog($message, $level=LOG_INFO)
{
    $filelog="prestalog.log";
    $file=@fopen($filelog,"a+");
    if ($file)
    {
        $ip='???';  // $ip contains information to identify computer that run the code
        if (! empty($_SERVER["REMOTE_ADDR"])) $ip=$_SERVER["REMOTE_ADDR"];          // In most cases.
        else if (! empty($_SERVER['SERVER_ADDR'])) $ip=$_SERVER['SERVER_ADDR'];     // This is when PHP session is ran inside a web server but not inside a client request (example: init code of apache)
        else if (! empty($_SERVER['COMPUTERNAME'])) $ip=$_SERVER['COMPUTERNAME'].(empty($_SERVER['USERNAME'])?'':'@'.$_SERVER['USERNAME']); // This is when PHP session is ran outside a web server, like from Windows command line (Not always defined, but usefull if OS defined it).
        else if (! empty($_SERVER['LOGNAME'])) $ip='???@'.$_SERVER['LOGNAME'];  // This is when PHP session is ran outside a web server, like from Linux command line (Not always defined, but usefull if OS defined it).

        $liblevelarray=array(LOG_ERR=>'ERROR',LOG_WARNING=>'WARN',LOG_INFO=>'INFO',LOG_DEBUG=>'DEBUG');
        $liblevel=$liblevelarray[$level];
        if (! $liblevel) $liblevel='UNDEF';

        $message=strftime("%Y-%m-%d %H:%M:%S", mktime())." ".sprintf("%-5s",$liblevel)." ".sprintf("%-15s",$ip)." ".$message;

        fwrite($file,$message."\n");
        fclose($file);
        // This is for log file, we do not change permissions
    }
}


/**
 * Check that a zip file is Dolibarr rule compliant
 */
function validateZipFile(&$zip,$originalfilename,$zipfile)
{
	$error=0;

	prestalog("Validate zip file ".$zipfile);
	$subdir=basename($zipfile);
	$dir='/home/dolibarr/dolistore.com/tmp/'.$subdir;
	mkdir($dir);
	$zip->extractTo($dir.'/');
	$zip->close();

	// Analyze files
	$upload=0;
	$validation=1;
	$ismodule=$istheme=0;
	if (is_dir($dir.'/scripts')) $ismodule='module';
	if (is_dir($dir.'/htdocs/themes')) $istheme='theme';
	if (preg_match('/^module_([_a-zA-Z0-9]+)\-([0-9]+)\.([0-9\.]+)(\.zip|\.tgz)$/i',$originalfilename,$reg)) $ismodule=$reg[1];
	if (preg_match('/^theme_([_a-zA-Z0-9]+)\-([0-9]+)\.([0-9\.]+)(\.zip|\.tgz)$/i',$originalfilename,$reg)) $istheme=$reg[1];
	if ($ismodule || $istheme)
	{
		prestalog("file ismodule=".$ismodule." istheme=".$istheme);
		// It's a module or theme file
		if (! $error && ($ismodule || $istheme) && $dh = opendir($dir)) 
		{
			$nbofsubdirs=0;
			while (($file = readdir($dh)) !== false) 
			{
				if ($file == '.' || $file == '..') continue;
				prestalog("subdirs found for package:".$file);
				$nbofsubdirs++;
				$alloweddirs=array('htdocs','docs','scripts','test','build',($ismodule?$ismodule:($istheme?$istheme:'')));
				if (! in_array($file,$alloweddirs))
				{
					$upload=-1;
					$error++;
					break;
				}
			}
			if ($error)
			{
				echo "<div style='color:#FF0000'>Sorry, a module file can only contains, into zip root:<br>\nOnly one directory matching module or theme name<br>\nor several directories matching following names: ".join(',',$alloweddirs)."<br>\n";
				echo "If you think this is an error, send your package by email at contact@dolibarr.org";
				echo "</div>";
			}
			closedir($dh);
		}				
		// It's a module or theme file
		if (! $error && $ismodule && is_dir($dir.'/htdocs') && $dh = opendir($dir.'/htdocs')) 
		{
			prestalog("we scan ".$dir."/htdocs to be sure there is only one directory into htdocs");
			$nbofsubdir=0;
			prestalog("check there is only one dir into htdocs");
			while (($file = readdir($dh)) !== false) 
			{
				if ($file == '.' || $file == '..') continue;
				if ($file == 'includes') continue;		// For old dolibarr version compatibility
				prestalog("we found ".$file);
				$nbofsubdir++;
			}
			closedir($dh);
#			if ($nbofsubdir >= 2)
#			{
#				echo "<div style='color:#FF0000'>Sorry, starting with Dolibarr 3.2 version, a module file can contains only one dir with name of module into the htdocs directory.";
#				echo 'See <a href="http://wiki.dolibarr.org/index.php/Module_development#Tree_of_path_for_new_module_files_.28required.29">Dolibarr wiki developer documentation for allowed tree</a>.';
#				echo "</div>";
#				$upload=-1;
#				$error++;
#			}
		}				
	}

	if (! $validation)
	{
		echo "<div style='color:#FF0000'>Your zip file does not look to match Dolibarr package rules.";
		echo "If you think this is an error, send your package by email at contact@dolibarr.org";
		echo "</div>";
		$upload=-1;
		$error++;
	}

	return array('error'=>$error,'upload'=>$upload);
}


/**
 * Create 2 thumbnails from an image file: one small and one mini (Supported extensions are gif, jpg, png and bmp)
 * With file=myfile.jpg -> myfile_small.jpg
 *
 * @param file Chemin du fichier image a redimensionner
 * @param maxWidth Largeur maximum que dois faire la miniature (-1=unchanged, 160 par defaut)
 * @param maxHeight Hauteur maximum que dois faire l'image (-1=unchanged, 120 par defaut)
 * @param extName Extension pour differencier le nom de la vignette
 * @param quality Quality of compression (0=worst, 100=best)
 * @param targetformat New format of target (1,2,3,4, no change if empty)
 * @return string Full path of thumb
 */
function vignette($file, $maxWidth = 160, $maxHeight = 120, $extName='_small', $quality=50, $outdir='thumbs', $targetformat=0)
{
	// Clean parameters
	$file=trim($file);

	// Check parameters
	if (! $file)
	{
		// Si le fichier n'a pas ete indique
		return 'Bad parameter file';
	}
	elseif (! file_exists($file))
	{
		// Si le fichier passe en parametre n'existe pas
		return "ErrorFileNotFound";
	}
	elseif(!is_numeric($maxWidth) || empty($maxWidth) || $maxWidth < -1)
	{
		// Si la largeur max est incorrecte (n'est pas numerique, est vide, ou est inferieure a 0)
		return 'Wrong value for parameter maxWidth';
	}
	elseif(!is_numeric($maxHeight) || empty($maxHeight) || $maxHeight < -1)
	{
		// Si la hauteur max est incorrecte (n'est pas numerique, est vide, ou est inferieure a 0)
		return 'Wrong value for parameter maxHeight';
	}

	$fichier = realpath($file); // Chemin canonique absolu de l'image
	$dir = dirname($file); // Chemin du dossier contenant l'image

	$infoImg = getimagesize($fichier); // Recuperation des infos de l'image
	$imgWidth = $infoImg[0]; // Largeur de l'image
	$imgHeight = $infoImg[1]; // Hauteur de l'image

	if ($maxWidth  == -1) $maxWidth=$infoImg[0]; 	// If size is -1, we keep unchanged
	if ($maxHeight == -1) $maxHeight=$infoImg[1]; 	// If size is -1 we keep unchanged

	$imgfonction='';
	switch($infoImg[2])
	{
		case 1: // IMG_GIF
			$imgfonction = 'imagecreatefromgif';
			break;
		case 2: // IMG_JPG
			$imgfonction = 'imagecreatefromjpeg';
			break;
		case 3: // IMG_PNG
			$imgfonction = 'imagecreatefrompng';
			break;
		case 4: // IMG_WBMP
			$imgfonction = 'imagecreatefromwbmp';
			break;
	}
	if ($imgfonction)
	{
		if (! function_exists($imgfonction))
		{
			// Fonctions de conversion non presente dans ce PHP
			return 'Creation de vignette impossible. Ce PHP ne supporte pas les fonctions du module GD '.$imgfonction;
		}
	}

	// On cree le repertoire contenant les vignettes
	$dirthumb = $dir.($outdir?'/'.$outdir:''); // Chemin du dossier contenant les vignettes

	// Initialisation des variables selon l'extension de l'image
	switch($infoImg[2])
	{
		case 1: // Gif
			$img = imagecreatefromgif($fichier);
			$extImg = '.gif'; // Extension de l'image
			break;
		case 2: // Jpg
			$img = imagecreatefromjpeg($fichier);
			$extImg = '.jpg'; // Extension de l'image
			break;
		case 3: // Png
			$img = imagecreatefrompng($fichier);
			$extImg = '.png';
			break;
		case 4: // Bmp
			$img = imagecreatefromwbmp($fichier);
			$extImg = '.bmp';
			break;
	}

	// Initialisation des dimensions de la vignette si elles sont superieures a l'original
	if($maxWidth > $imgWidth){ $maxWidth = $imgWidth; }
	if($maxHeight > $imgHeight){ $maxHeight = $imgHeight; }

	$whFact = $maxWidth/$maxHeight; // Facteur largeur/hauteur des dimensions max de la vignette
	$imgWhFact = $imgWidth/$imgHeight; // Facteur largeur/hauteur de l'original

	// Fixe les dimensions de la vignette
	if($whFact < $imgWhFact){
		// Si largeur determinante
		$thumbWidth = $maxWidth;
		$thumbHeight = $thumbWidth / $imgWhFact;
	} else {
		// Si hauteur determinante
		$thumbHeight = $maxHeight;
		$thumbWidth = $thumbHeight * $imgWhFact;
	}
	$thumbHeight=round($thumbHeight);
	$thumbWidth=round($thumbWidth);

	// Define target format
	if (empty($targetformat)) $targetformat=$infoImg[2];

	// Create empty image
	if ($targetformat == 1)
	{
		// Compatibilite image GIF
		$imgThumb = imagecreate($thumbWidth, $thumbHeight);
	}
	else
	{
		$imgThumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
	}

	// Activate antialiasing for better quality
	if (function_exists('imageantialias'))
	{
		imageantialias($imgThumb, true);
	}

	// This is to keep transparent alpha channel if exists (PHP >= 4.2)
	if (function_exists('imagesavealpha'))
	{
		imagesavealpha($imgThumb, true);
	}

	// Initialisation des variables selon l'extension de l'image
	switch($targetformat)
	{
		case 1: // Gif
			$trans_colour = imagecolorallocate($imgThumb, 255, 255,
			255); // On procede autrement pour le format GIF
			imagecolortransparent($imgThumb,$trans_colour);
			$extImgTarget = '.gif';
			$newquality='NU';
			break;
		case 2: // Jpg
			$trans_colour = imagecolorallocatealpha($imgThumb, 255, 255,
			255, 0);
			$extImgTarget = '.jpg';
			$newquality=$quality;
			break;
		case 3: // Png
			imagealphablending($imgThumb,false); // Pour compatibilite sur certain systeme
			$trans_colour = imagecolorallocatealpha($imgThumb, 255, 255,
			255, 127); // Keep transparent channel
			$extImgTarget = '.png';
			$newquality=$quality-100;
			$newquality=round(abs($quality-100)*9/100);
			break;
		case 4: // Bmp
			$trans_colour = imagecolorallocatealpha($imgThumb, 255, 255,
			255, 0);
			$extImgTarget = '.bmp';
			$newquality='NU';
			break;
	}
	if (function_exists("imagefill")) imagefill($imgThumb, 0, 0, $trans_colour);

	imagecopyresampled($imgThumb, $img, 0, 0, 0, 0, $thumbWidth,
	$thumbHeight, $imgWidth, $imgHeight); // Insere l'image de base redimensionnee

	$fileName = preg_replace('/(\.gif|\.jpeg|\.jpg|\.png|\.bmp)$/i','',$file); // On enleve extension quelquesoit la casse
	$fileName = basename($fileName);
	$imgThumbName = $dirthumb.'/'.$fileName.$extName.$extImgTarget; // Chemin complet du fichier de la vignette

	// Check if permission are ok
	//$fp = fopen($imgThumbName, "w");
	//fclose($fp);

	// Create image on disk
	switch($targetformat)
	{
		case 1: // Gif
		imagegif($imgThumb, $imgThumbName);
		break;
		case 2: // Jpg
		imagejpeg($imgThumb, $imgThumbName, $newquality);
		break;
		case 3: // Png
		imagepng($imgThumb, $imgThumbName, $newquality);
		break;
		case 4: // Bmp
		image2wmp($imgThumb, $imgThumbName);
		break;
	}

	// Set permissions on file
	// @chmod($imgThumbName, octdec($conf->global->MAIN_UMASK));

	// Free memory
	imagedestroy($imgThumb);

	return $imgThumbName;
}




?>
