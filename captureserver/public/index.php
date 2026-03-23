<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2019	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin			<regis.houssin@inodbox.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * For paypal test: https://developer.paypal.com/
 * For paybox test: ???
 */

/**
 *     	\file       htdocs/captureserver/public/index.php
 *		\ingroup    core
 *		\brief      Endpoint provided by module captureserver
 */

define("NOLOGIN", '1');		   // This means this output page does not require to be logged.
define('NOREQUIRETRAN', '1');  // Do not load object $langs
define("NOCSRFCHECK", '1');	   // We accept to go on this page from external web site.
define('NOTOKENRENEWAL', '1');
define('NOIPCHECK', '1');
define('NOREQUIREMENU', '1');
define('NOSESSION', '1');

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// TODO This should be useless. Because entity must be retreive from object ref and not from url.
$entity=(! empty($_GET['entity']) ? (int) $_GET['entity'] : (! empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) define("DOLENTITY", $entity);

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include str_replace("..", "", $_SERVER["CONTEXT_DOCUMENT_ROOT"])."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include dirname(substr($tmp, 0, ($i+1)))."/main.inc.php";
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php")) $res=@include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res=@include "../../../main.inc.php";
if (! $res) die("Include of main fails");
/**
 * @var DoliDB	$db
 * @var User	$user
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once '../class/captureserver.class.php';

$action = GETPOST('action', 'aZ09');

// Security check
if (!isModEnabled("captureserver")) {
	accessforbidden("Module not enabled");
}

// Input are:
// type ('invoice','order','contractline'),
// id (object id),
// amount (required if id is empty),
// tag (a free text, required if type is empty)
// currency (iso code)

if (! $action) {
	dol_syslog('ERROR ErrorBadParameters - action missing', LOG_WARNING, 0, '_captureserver');
	print "ERROR ErrorBadParameters - action missing";
	exit;
}


// Complete urls for post treatment
$SECUREKEY = GETPOST("securekey", 'alpha');	        // Secure key
if ($SECUREKEY && $SECUREKEY != getDolGlobalString("CAPTURESERVER_SECURITY_KEY")) {
	dol_syslog('ERROR ErrorBadSecureKey - Bad value of securekey parameter', LOG_WARNING, 0, '_captureserver');
	accessforbidden("ERROR Acces not allowed. Bad value of securekey parameter");
}


/*
 * Actions
 */

// None


/*
 * View
 */

header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Access-Control-Allow-Origin: *");

dol_syslog('----- Capture server was called with action='.$action, LOG_NOTICE, 0, '_captureserver');

print '----- Capture server was called with action='.$action;

if ($action == 'dolibarrping' || $action == 'dolibarrregistration' || $action == 'dolibarrpushcounter') {
	$hash_algo = GETPOST('hash_algo', 'aZ09');
	$hash_unique_id = GETPOST('hash_unique_id', 'aZ09');
	$version = GETPOST('version', 'aZ09');

	if (empty($hash_algo) || empty($hash_unique_id)) {
		print "\n".'<br>Bad value for parameter hash_algo or hash_unique_id';
	} else {
		$maxsize = getDolGlobalInt('CAPTURE_SERVER_MAX_SIZE_OF_CAPTURED_CONTENT', 4096);
		if (is_array($_POST) && strlen(join('', $_POST)) > $maxsize) {
			$contenttoinsert = 'Content larger than limit of '.$maxsize;
		} else {
			$contenttoinsert = json_encode($_POST);
		}

		dol_syslog('content received: '.var_export($_POST, true), LOG_DEBUG, 0, '_captureserver');

		// Insert into database using implicit Transactions
		$captureserver = new CaptureServer($db);
		$result = $captureserver->fetch(0, $action.'_'.$hash_unique_id);	// Unique key is on $action.'_'.$hash_unique_id

		if ($result > 0) {
			dol_syslog('Record already found for key '.$action.'_'.$hash_unique_id, LOG_DEBUG, 0, '_captureserver');

			// Update fields
			$captureserver->comment = 'Message received for update at '.dol_print_date(dol_now(), 'dayhourlog').' - from hash '.$hash_unique_id.' - version '.$version;
			$captureserver->label = 'Message by v'.$version;
			$captureserver->content = $contenttoinsert;
			$captureserver->qty++;

			if ($action == 'dolibarrregistration' || $action == 'dolibarrpushcounter') {
				$tmparray = json_decode($contenttoinsert, true, 2);
				dol_syslog('content after jsondecode: '.var_export($tmparray, true), LOG_DEBUG, 0, '_captureserver');

				if (is_array($tmparray)) {
					if ($action == 'dolibarrregistration') {
						$captureserver->registername = $tmparray['company_name'] ?? 'unknown';
						$captureserver->registeremail = $tmparray['company_email'] ?? 'unknown';
						$captureserver->registerprofid = $tmparray['company_idprof1'] ?? 'unknown';
						$captureserver->versiondolibarr = $tmparray['version_full'] ?? 'unknown';
						$captureserver->versionblockedlog = $tmparray['versionblockedlog_full'] ?? 'unknown';
					}

					if ($action == 'dolibarrpushcounter') {
						if (!empty($captureserver->datesys) && $captureserver->datesys >= $tmparray['datesys']) {
							// We discard event, it is a deprecated event that arrived too late
							dol_syslog("The event arrived with datesys=".$tmparray['datesys']." that is before the last event recorded for ".$captureserver->datesys.", so we discard it", LOG_WARNING, 0, '_captureserver');
						} else {
							$dbpreviousrowid = $captureserver->previousrowid;		// For example i have 432 in db  and i receive  432  instead of  433
							$dblastrowid = $captureserver->lastrowid;				// For example i have 433 in db  and i receive  434  instead of  434

							// I received a new message with

							$captureserver->lastrowid = $tmparray['lastrowid'] ?? null;
							$captureserver->lastsignature = $tmparray['lastsignature'] ?? null;
							$captureserver->lastdatecreation = $tmparray['lastdatecreation'] ?? null;

							$captureserver->previousrowid = $tmparray['previousrowid'] ?? null;
							$captureserver->previoussignature = $tmparray['previoussignature'] ?? null;
							$captureserver->previousdatecreation = $tmparray['previousdatecreation'] ?? null;

							$captureserver->datesys = $tmparray['datesys'] ?? null;

							if ((int) $dblastrowid && (int) $captureserver->previousrowid
								&& $captureserver->previousrowid < $dblastrowid) {
								// Alert a record was deleted or a backup was restored
								$captureserver2 = new CaptureServer($db);
								$captureserver2->type = 'deletion_or_backup_restoration';

								$captureserver2->ref = 'deletion_or_backup_restoration_'.$hash_unique_id;
								$captureserver2->qty = 1;
								$captureserver2->status = 1;

								$captureserver2->comment = 'Deletion or backup restoration detected the '.dol_print_date(dol_now(), 'dayhourlog').' (first case: we got a rowid of '.$dblastrowid.' and a new message said previous was '.$captureserver->previousrowid.') - from hash '.$hash_unique_id.' - version '.$version;
								$captureserver2->label = 'Anomaly detected';
								$captureserver2->content = $contenttoinsert;

								$captureserver2->registerid = $hash_unique_id;

								dol_syslog($captureserver2->comment, LOG_NOTICE, 0, '_captureserver');

								// Test if entry already exists for the same day, increase qty, if not create a new one (so we limit problem tracking to 1 per day).
								// TODO
								$captureserver2->create($user);
							}
						}
					}
				}
			}

			dol_syslog("Update record", LOG_DEBUG, 0, '_captureserver');
			$captureserver->update($user);

			// Send to DataDog (metric + event)
			if ($action == 'dolibarrping' || $action == 'dolibarrregistration') {
				if (getDolGlobalString('CAPTURESERVER_DATADOG_UPDATE_ENABLED')) {
					try {
						dol_include_once('/captureserver/core/includes/php-datadogstatsd/src/DogStatsd.php');

						$arrayconfig=array();
						if (getDolGlobalString('CAPTURESERVER_DATADOG_APIKEY')) {
							$arrayconfig=array('apiKey'=>getDolGlobalString('CAPTURESERVER_DATADOG_APIKEY'), 'app_key' => getDolGlobalString('CAPTURESERVER_DATADOG_APPKEY'));
						}

						$statsd = new DataDog\DogStatsd($arrayconfig);

						$phpversion = join('.', array_slice(explode('.', GETPOST('php_version', 'alphanohtml')), 0, 2));
						$dolversion = GETPOST('version', 'alphanohtml');

						$arraytags = array('version'=>$dolversion, 'dbtype'=>GETPOST('dbtype', 'alphanohtml'), 'country_code'=>GETPOST('country_code', 'aZ09'), 'php_version'=>$phpversion);

						dol_syslog("Send info to datadog", LOG_DEBUG, 0, '_captureserver');

						$statsd->increment('captureserver.'.$action.'-update', 1, $arraytags);
					} catch (Exception $e) {
						dol_syslog("Error in sending info to datadog", LOG_WARNING, 0, '_captureserver');
					}
				}
			}

			print "<br>\n".'Event updated';
		} elseif ($result == 0) {
			dol_syslog('No record found for key '.$action.'_'.$hash_unique_id.' so we will create it', LOG_DEBUG, 0, '_captureserver');

			$captureserver->type = $action;
			$captureserver->ref = $action.'_'.$hash_unique_id;
			$captureserver->qty = 1;
			$captureserver->status = 1;

			$captureserver->comment = 'Message received at '.dol_print_date(dol_now(), 'dayhourlog').' - from hash '.$hash_unique_id.' - version '.$version;
			$captureserver->label = 'Message by v'.$version;
			$captureserver->content = $contenttoinsert;

			$captureserver->registerid = $hash_unique_id;

			if ($action == 'dolibarrregistration' || $action == 'dolibarrpushcounter') {
				$tmparray = json_decode($contenttoinsert, true, 2);
				dol_syslog('content after jsondecode: '.var_export($tmparray, true), LOG_DEBUG, 0, '_captureserver');

				if (is_array($tmparray)) {
					if ($action == 'dolibarrregistration') {
						$captureserver->registername = $tmparray['company_name'] ?? 'unknown';
						$captureserver->registeremail = $tmparray['company_email'] ?? 'unknown';
						$captureserver->registerprofid = $tmparray['company_idprof1'] ?? 'unknown';
					}

					if ($action == 'dolibarrpushcounter') {
						$captureserver->lastrowid = $tmparray['lastrowid'] ?? null;
						$captureserver->lastsignature = $tmparray['lastsignature'] ?? null;
						$captureserver->previousrowid = $tmparray['previousrowid'] ?? null;
						$captureserver->previoussignature = $tmparray['previoussignature'] ?? null;
					}
				}
			}

			$result = $captureserver->create($user);

			if ($action == 'dolibarrping' || $action == 'dolibarrregistration') {
				if (getDolGlobalString('CAPTURESERVER_DATADOG_ENABLED')) {
					try {
						dol_include_once('/captureserver/core/includes/php-datadogstatsd/src/DogStatsd.php');

						$arrayconfig=array();
						if (getDolGlobalString('CAPTURESERVER_DATADOG_APIKEY')) {
							$arrayconfig=array('apiKey'=>getDolGlobalString('CAPTURESERVER_DATADOG_APIKEY'), 'app_key' => getDolGlobalString('CAPTURESERVER_DATADOG_APPKEY'));
						}

						$statsd = new DataDog\DogStatsd($arrayconfig);

						$phpversion = join('.', array_slice(explode('.', GETPOST('php_version', 'alphanohtml')), 0, 2));
						$dolversion = GETPOST('version', 'alphanohtml');
						$dbversion = GETPOST('db_version', 'alphanohtml');
						$distrib = GETPOST('distrib', 'alphanohtml');

						// Protection against too accurate versions
						$dbversion = preg_replace('/[\.\-]\d*ubuntu.*/i', '', $dbversion);
						$dbversion = preg_replace('/([\.\-]\d*mariadb).*/i', '\1', $dbversion);

						// Protection against too accurate versions
						$osversionarray = preg_split('/\.\-/', GETPOST('os_version', 'alphanohtml'));
						$osversion = '';
						$i = 0;
						foreach($osversionarray as $osversioncursor) {
							if ($i >= 4) {
								break;
							}
							$osversion .= (($i > 1) ? '.' : '').$osversioncursor;
							$i++;
						}
						$arraytags=array('version'=>$dolversion, 'dbtype'=>GETPOST('dbtype', 'alphanohtml'), 'country_code'=>GETPOST('country_code', 'aZ09'), 'php_version'=>$phpversion, 'db_version'=>$dbversion, 'os_version'=>$osversion, 'distrib'=>$distrib);

						dol_syslog("Send info to datadog", LOG_DEBUG, 0, '_captureserver');

						$statsd->increment('captureserver.'.$action.'-add', 1, $arraytags);
					} catch (Exception $e) {
						dol_syslog("Error in sending info to datadog", LOG_WARNING, 0, '_captureserver');
					}
				}
			}

			print "<br>\n".'Event added';

			// Should return http 200 by default.
		} else {
			print "<br>\n".'Error during try to fetch record';
			http_response_code(500);
		}
	}

	dol_syslog('Process complete', LOG_DEBUG, 0, '_captureserver');
} else {
	dol_syslog('ERROR Action '.$action.' not supported', LOG_NOTICE, 0, '_captureserver');

	print "<br>\n".'Action not supported';
	http_response_code(400);
}

$db->close();
