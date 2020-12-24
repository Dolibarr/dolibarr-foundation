<?php
require_once "./OpaleTranslator.class.php";

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);

$version = '1.14';
$error = 0;
$foldertmp = '/tmp_tmp';
$langnewzip;

if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You are using PHP for CGI. To execute " . $script_file . " from command line, you must use PHP for CLI mode.\n";
    exit;
}

if (!isset($argv[5])) {
    print "Usage:   " . $script_file . "  source_file.scar dest_file code_source_lang code_dest_lang APIKEY\n";
    print "Example: " . $script_file . "  /tmp/file.scar   /tmp/extract   en_US  fr_FR     123456\n";
    print "Rem:     lang_code to use can be found on https://translate.google.com\n";
    exit;
}

print "***** " . $script_file . " (" . $version . ") *****\n";
print 'Argument 1=' . $argv[1] . "\n";
print 'Argument 2=' . $argv[2] . "\n";
print 'Argument 3=' . $argv[3] . "\n";
print 'Argument 4=' . $argv[4] . "\n";
if (isset($argv[5])) {
    $api = $argv[5];
}

$zip_file = $argv[1];
$dest_dir = $argv[2];
$codeoldlang = $argv[3];
$codenewlang = $argv[4];
$langnewzip = strtoupper(explode('_', $codenewlang)[0]);
if (file_exists($dest_dir . $foldertmp)) {
    echo "Please choose another folder, folder tmp_tmp already exists\n";
    die();
}

$translator = new OpaleTranslator($codeoldlang, $codenewlang, $api);
$extract = $translator->extract($zip_file, $dest_dir . $foldertmp);
if ($extract) {
    echo $GLOBALS['status']['success'];
} else {
    echo $GLOBALS['status']['error'];
    if (is_dir($foldertozip)) {
        $translator->delTmp($foldertozip);
    }
    die();
}
$extract = str_replace('.scar', '', $extract);
$foldertozip = $dest_dir . $foldertmp;
$zipname = explode('-', $extract);
$zipname = $zipname[0] . '-' . $langnewzip;

if ($translator->renameFolders($foldertozip, $extract, $zipname)) {
    echo $GLOBALS['status']['success'];
} else {
    echo $GLOBALS['status']['error'];
    $translator->delTmp($foldertozip);
    die();
}

if (!$translator->editXML($foldertozip)) {
    echo $GLOBALS['status']['error'];
    $translator->delTmp($foldertozip);
    die();
}

if ($translator->outputZipArchive($foldertozip, $zipname, $dest_dir . '/')) {
    echo $GLOBALS['status']['success'];
} else {
    echo $GLOBALS['status']['error'];
    $translator->delTmp($foldertozip);
    die();
}
$translator->delTmp($foldertozip);
print "***** Finished *****\n";
