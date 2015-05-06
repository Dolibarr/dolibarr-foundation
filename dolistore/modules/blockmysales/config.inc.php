<?php
$wsdolibarrkey='dolibarrkeyasso';
$wslogin='dolibarr';
$wspass='d0libarr-an';

$commissioncee=80;
$this->context->smarty->assign('commissioncee', $commissioncee.'%');

$commissionnotcee=80;

$minversion='3.1';
$this->context->smarty->assign('minversion', $minversion);

$maxversion='3.7+';
$this->context->smarty->assign('maxversion', $maxversion);

$minamountcee=50;
$minamountnotcee=200;

$mindelaymonth=1;
$this->context->smarty->assign('mindelaymonth', $mindelaymonth);

$vatrate=20;
$this->context->smarty->assign('vatrate', $vatrate);

$vatnumber='FR87520339938';
$this->context->smarty->assign('vatnumber', $vatnumber);

//$taxrulegroupid=1;	// rowid of tax rule group into database
$taxrulegroupid=6;	// rowid of tax rule group into database
$this->context->smarty->assign('taxrulegroupid', $taxrulegroupid);

$this->context->smarty->assign('upload_max_filesize', ini_get('upload_max_filesize'));

?>
