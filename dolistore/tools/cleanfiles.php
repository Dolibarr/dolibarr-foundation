<?php
include '../config/settings.inc.php';

$nbfound = 0;
$nbnotfound = 0;


$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit;
}


$mode=isset($argv[1])?$argv[1]:'';

if (empty($mode)) {
	echo "Usage:  cleanfiles.php  test|confirm\n";
	exit;
}

$connect = mysqli_connect(_DB_SERVER_, _DB_USER_, _DB_PASSWD_, _DB_NAME_);

$sql = 'SELECT id_product_download, filename, display_filename FROM ps_product_download';

$resql = mysqli_query($connect, $sql);

if ($resql) {
	$arrayoffiles = array();
	$arrayoflabel = array();

	$obj = mysqli_fetch_row($resql);
	while ($obj = mysqli_fetch_row($resql)) {
		$arrayoffiles[$obj[0]]=$obj[1];
		$arrayoflabel[$obj[0]]=$obj[2];
	}

	if (count($arrayoffiles)) {
		print 'Found '.count($arrayoffiles).' entries of files'."\n";


	} else {
		print 'Failed to find existing files in database.'."\n";
	}

	print 'Scan dir /home/dolibarr/dolistore.com/httpdocs/download'."\n";
	$files = scandir('/home/dolibarr/dolistore.com/httpdocs/download');
	foreach($files as $file) {
		if (in_array($file, $arrayoffiles)) {
			// Search key
			$key = array_search($file, $arrayoffiles);
			print 'File '.$file.' exists in database for module '.$arrayoflabel[$key]."\n";
			$nbfound++;
		} elseif (preg_match('/^[a-f0-9]$/', $file)) {
			print 'File '.$file.' is not into database, we delete it.'."\n";
			$nbnotfound++;
			//unlink('/home/dolibarr/dolistore.com/httpdocs/download/'.$file);
			if ($mode == 'confirm') {
				rename('/home/dolibarr/dolistore.com/httpdocs/download/'.$file, '/tmp/old/'.$file);
			} else {
				print "Disabled in test mode.\n";
			}
		} else {
			print 'File '.$file.' discarded'."\n";
		}
	}
}

print 'Result: found='.$nbfound.' notfound='.$nbnotfound."\n";



