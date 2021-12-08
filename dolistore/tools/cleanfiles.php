<?php
include '../config/settings.inc.php';

$nbfound = 0;
$nbnotfound = 0;

$connect = mysqli_connect(_DB_SERVER_, _DB_USER_, _DB_PASSWD_, _DB_NAME_);

$sql = 'SELECT filename FROM ps_product_download';

$resql = mysqli_query($connect, $sql);

if ($resql) {
	$obj = mysqli_fetch_row($resql);
	while ($obj = mysqli_fetch_row($resql)) {
		$arrayoffiles[]=$obj[0];
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
			print 'File '.$file.' exists in database.'."\n";
			$nbfound++;
		} elseif (preg_match('/^[a-f0-9]$/', $file)) {
			print 'File '.$file.' is not into database, we delete it.'."\n";
			$nbnotfound++;
			//unlink('/home/dolibarr/dolistore.com/httpdocs/download/'.$file);
			rename('/home/dolibarr/dolistore.com/httpdocs/download/'.$file, '/tmp/old/'.$file);
		} else {
			print 'File '.$file.' discarded'."\n";
		}
	}
}

print 'Result: found='.$nbfound.' notfound='.$nbnotfound."\n";



