<?php
/* Copyright (C) 2026 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       marketplace/compare.php
 *	\ingroup    marketplace
 *	\brief      Page to compare 2 modules
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}
/**
 * The main.inc.php has been included so the following variable are now defined:
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 * @var Societe $mysoc
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/utils.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';


// Sécurité : restreindre l'accès
if (!$user->hasRight('product', 'read')) {
    accessforbidden();
}

// Récupération des paramètres POST
$ref1 = GETPOST('ref1', 'alphanohtml');
$ref2 = GETPOST('ref2', 'alphanohtml');

$output = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($ref1) || empty($ref2)) {
        $error = "Both product references are required.";
    } else {
		$tmpproduct = new Product($db);
		$tmpproduct->fetch($ref1);
    	$ref1 = $tmpproduct->ref;

		$tmpproduct = new Product($db);
		$tmpproduct->fetch($ref2);
    	$ref2 = $tmpproduct->ref;

        // Secure arguments shell
        $ref1_safe = escapeshellarg($ref1);
        $ref2_safe = escapeshellarg($ref2);

        // Chemin absolu recommandé
        $script = dol_buildpath('/marketplace/scripts/comp.sh', 0);

        if (!file_exists($script)) {
            $error = "Script not found.";
        } else {

            $cmd = $script . " $ref1_safe $ref2_safe 2>&1";

            // Exécution
            $util = new Utils($db);
            $outputfile = '/tmp/comp.txt';
            $resexec = $util->executeCLI($cmd, $outputfile);

            if ($resexec['result'] !== 0) {
                $error = "Execution failed.";
            }else {
            	$output = $resexec['output'];
            }
        }
    }
}



/**
 * View
 */

$form = new Form($db);

llxHeader('', 'Comparison Tool');

print load_fiche_titre("Product Comparison Tool");

?>

<form method="POST">
	<input type="hidden" name="token" value="<?php echo newToken(); ?>">
	<input type="hidden" name="action" value="compare">
    <table class="border centpercent">
        <tr>
            <td>Product Ref 1</td>
            <td>
<?php
            print $form->select_produits($ref1, 'ref1', '', 0, 0, -1);
?>
            </td>
        </tr>
        <tr>
            <td>Product Ref 2</td>
            <td>
<?php
            print $form->select_produits($ref2, 'ref2', '', 0, 0, -1);
?>
        </tr>
    </table>

    <br>
    <input type="submit" class="button" value="Run Comparison">
</form>

<?php

if ($error) {
    print '<div class="error">'.$error.'</div>';
}

if ($output) {
    print '<h3>Result</h3>';
    print '<pre style="background:#f4f4f4;padding:10px;">';
    print dol_nl2br($output);
    print '</pre>';
}

llxFooter();
$db->close();
