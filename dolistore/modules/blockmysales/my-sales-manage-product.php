<?php

/* SSL Management */
$useSSL = true;

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/../../init.php');
include(dirname(__FILE__).'/lib.php');

// Get env variables
$id_langue_en_cours = $cookie->id_lang;
$customer_id = $cookie->id_customer;
$publisher=trim($cookie->customer_firstname.' '.$cookie->customer_lastname);
if (! empty($_GET["id_customer"])) $customer_id=$_GET["id_customer"];

// Check if current user is also an employee with admin user
$query = "SELECT id_employee, id_profile, email, active FROM "._DB_PREFIX_."employee
        WHERE lastname = '".addslashes($cookie->customer_lastname)."' and firstname = '".addslashes($cookie->customer_firstname)."'";
$subresult = Db::getInstance()->ExecuteS($query);
if (empty($subresult[0]['id_employee']))    // If not an admin user
{
    if ($customer_id != $cookie->id_customer)
    {
        print 'Error, you need to be an admin user to view other customers/suppliers.';
        die();
    }
}

$query = "SELECT c.id_customer, c.firstname, c.lastname, c.email, c.optin, c.active, c.deleted, a.company";
$query.= " FROM "._DB_PREFIX_."customer as c";
$query.= " LEFT JOIN "._DB_PREFIX_."address as a ON a.id_customer = c.id_customer";
$query.= " WHERE c.id_customer = ".$customer_id;
$subresult = Db::getInstance()->ExecuteS($query);

if (! empty($subresult[0]['active']))
{
    $customer_id=$subresult[0]['id_customer'];
    $publisher=trim($subresult[0]['firstname'].' '.$subresult[0]['lastname']);
    $company=trim($subresult[0]['company']);
}
else
{
    print 'Customer with id '.$customer_id.' can\'t be found.';
    die();
}


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

?>



<?php aff("<h2>Modifier mes modules/produits</h2>", "<h2>Manages my modules/plugins</h2>", $iso_langue_en_cours); ?>
<br />

<?php
// foundationfreerate
$foundationfeerate=0.7;
// totalnbofsell
$totalnbsell=0;
// totalamount
$totalamount=0;
// datestart
if (empty($datestart))
{
    //print $cookie->date_add;
    //$datestart=strtotime('YY-mm-DD HH:MM:II',$cookie->date_add);
    $datestart=strtotime($cookie->date_add);
    //$datestart=mktime(0, 0, 0, 6, 1, 2010);   // If not found
    //print 'ee'.$datestart;
}
//
$dolistoreinvoices=array();

aff(
"Les statistiques sont celles des téléchargements/ventes pour les composants soumis par l'utilisateur courant (<b>".$publisher.($company?" - ".$company:"")."</b>)",
"Statistics are for download/sells of components submited by for current user (<b>".$publisher.($company?" - ".$company:"")."</b>)",
$iso_langue_en_cours);

?>
<br><br>

<?php aff("- Pour changer les informations sur votre produit cliquez sur son nom,<br>- Pour changer son image cliquez sur son image<br>- Pour supprimer un produit, envoyez un mail à contact@dolibarr.org", "- To change your product information click on its name,<br>- To change its picture click on its picture<br>- To remove a product, send a mail to contact@dolibarr.org", $iso_langue_en_cours); ?>

<FORM name="fmysalessubprod" method="POST" ENCTYPE="multipart/form-data" class="std">
<table width="100%" >
<tr>
<td>



<table width="100%" border="0" cellspacing="2" cellpadding="0">
  <tr bgcolor="#CCCCCC">
    <td nowrap="nowrap"><b><?php aff("Image", "Picture", $iso_langue_en_cours); ?></b></td>
    <td nowrap="nowrap"><b><?php aff("Nom", "Name", $iso_langue_en_cours); ?></b></td>
    <td nowrap="nowrap"><b><?php aff("Description", "Description", $iso_langue_en_cours); ?></b></td>
    <td nowrap="nowrap"><b> &nbsp;<?php aff("Nb", "Nb", $iso_langue_en_cours); ?></b> &nbsp;</td>
    <td nowrap="nowrap"><b><?php aff("Montant<br>gagné", "Amount<br>earned", $iso_langue_en_cours); ?></b></td>
    <!--<td nowrap="nowrap"><b><?php aff("Supp", "Delete", $iso_langue_en_cours); ?></b></td> -->
  </tr>

<?php
$min_date = 0;

$query = 'SELECT p.id_product, p.reference, pl.name, pl.description_short';
$query.= ' FROM '._DB_PREFIX_.'product as p';
$query.= ' LEFT JOIN '._DB_PREFIX_.'product_lang as pl on pl.id_product = p.id_product AND pl.id_lang = '.$id_langue_en_cours;
$query.= ' WHERE p.reference like "c'.$customer_id.'d2%"';
$query.= ' ORDER BY pl.name';
$result = Db::getInstance()->ExecuteS($query);

if ($result === false) die(Tools::displayError('Invalid loadLanguage() SQL query!: '.$query));
$id_product = "";

$colorTabNbr = 1;
if (sizeof($result))
{
    foreach ($result AS $row)   // For each product
    {
        $id_product = $row['id_product'];
        $ref_product = $row['reference'];
        $name = $row['name'];
        $description_short = $row['description_short'];

        //recuperation de limage du produit
        $query = 'SELECT `id_image` FROM `'._DB_PREFIX_.'image`
                WHERE `id_product` = '.$id_product.'
                AND cover = 1';
        $subresult = Db::getInstance()->ExecuteS($query);
        $id_image = "";
        foreach ($subresult AS $subrow) {
            $id_image = $subrow['id_image'];
        }

        if ($id_image != "")
            $imgProduct = '../../img/p/'.$id_product.'-'.$id_image.'-small.jpg';
        else
            $imgProduct = '../../img/p/en-default-small.jpg';

        if ($colorTabNbr%2)
            $colorTab="#ffffff";
        else
            $colorTab="#eeeeee";

        // Calculate totalamount
        $query = "SELECT count( id_order_detail ) as nbra, sum( product_price ) as amount, min( date_add ) as min_date
                    FROM "._DB_PREFIX_."order_detail,  "._DB_PREFIX_."orders
                    WHERE product_id = ".$id_product."
                    AND "._DB_PREFIX_."orders.id_order = "._DB_PREFIX_."order_detail.id_order
                    AND "._DB_PREFIX_."orders.valid = 1";
        $subresult = Db::getInstance()->ExecuteS($query);
        $nbr_achats = 0;
        $nbr_amount = 0;
        foreach ($subresult AS $subrow) {
            $nbr_achats = $subrow['nbra'];
            $nbr_amount = $subrow['amount'];
            if ($subrow['min_date'])
            {
                if ($min_date) $min_date = min($min_date,$subrow['min_date']);
                else $min_date=$subrow['min_date'];
            }
        }

        $totalnbsell+=$nbr_achats;
        $totalamount+=$nbr_amount;

        // Calculate totalamount supplier can claim
        $query = "SELECT count( id_order_detail ) as nbra, sum( product_price ) as amount, min( date_add ) as min_date
                    FROM "._DB_PREFIX_."order_detail,  "._DB_PREFIX_."orders
                    WHERE product_id = ".$id_product."
                    AND "._DB_PREFIX_."orders.id_order = "._DB_PREFIX_."order_detail.id_order
                    AND "._DB_PREFIX_."orders.valid = 1
                    AND date_add < '".date("Y-m-d 00:00:00",mktime()-(2*31*24*60*60))."'";

        $subresult = Db::getInstance()->ExecuteS($query);
        $nbr_achats2 = 0;
        $nbr_amount2 = 0;
        foreach ($subresult AS $subrow) {
            $nbr_achats2 = $subrow['nbra'];
            $nbr_amount2 = $subrow['amount'];
            if ($subrow['min_date'])
            {
                if ($min_date2) $min_date2 = min($min_date2,$subrow['min_date']);
                else $min_date2=$subrow['min_date'];
            }
        }

        $totalamountclaimable+=$nbr_amount2;

        ?>

        <tr bgcolor="<?php echo $colorTab; ?>">
            <td><a href="./my-sales-images-product.php?id_p=<?php echo $id_product; ?>"><img src="<?php echo $imgProduct; ?>" border="1" /></a></td>
            <td><a href="./my-sales-modify-product.php?id_p=<?php echo $id_product; ?>"><?php echo $name; ?></a>
            <?php echo "<br>(".$ref_product.")"; ?>
            </td>
            <td>
            <?php echo $description_short; ?>
            </td>
            <td align="right"><a href="./my-sales-list.php?id_p=<?php echo $id_product; ?>"><?php echo $nbr_achats; ?></a></td>
            <td align="right"><?php
            if ($nbr_amount > 0)
            {
                echo ($foundationfeerate*100).'% ';
                aff(' de ',' of ',$iso_langue_en_cours);
                echo '<br>';
            }
            echo round($nbr_amount,2); ?>&#8364;
            </td>
            <!--<td>&nbsp;</td> -->
        </tr>

        <?php
        $colorTabNbr++;
    }
}
else
{
    print '<tr><td>';
    aff("Pas de module soumis", "No submited modules", $iso_langue_en_cours);
    print '</td></tr>';
}
?>
</table>







</td>
</tr>
</table>


</FORM>
<br>
<?php

if ($totalamount > 0)
{
    // define variables
    $mytotalamount=round($foundationfeerate*$totalamount,2);
    $mytotalamountclaimable=round($foundationfeerate*$totalamountclaimable,2);
    $alreadyreceived=0;
    $datelastpayment=0;

    // Search third party and payments already done
    define(NUSOAP_PATH,'nusoap');

    require_once(NUSOAP_PATH.'/nusoap.php');        // Include SOAP
    $dolibarr_main_url_root='http://asso.dolibarr.org/dolibarr/';
    require_once('config.inc.php');
    $authentication=array(
        'dolibarrkey'=>$wsdolibarrkey,
        'sourceapplication'=>'DOLISTORE',
        'login'=>$wslogin,
        'password'=>$wspass,
        'entity'=>'');

    $socid=0;
    $foundthirdparty=false;

    // Call the WebService method to find third party id from name or company name.
    $WS_DOL_URL = $dolibarr_main_url_root.'/webservices/server_thirdparty.php';
    $WS_METHOD  = 'getThirdParty';
    prestalog("Create soapclient_nusoap for URL=".$WS_DOL_URL);
    $soapclient = new soapclient_nusoap($WS_DOL_URL);
    if ($soapclient)
    {
        $soapclient->soap_defencoding='UTF-8';
        $soapclient->decodeUTF8(false);
    }

    if (! $foundthirdparty)
    {
        $parameters = array('authentication'=>$authentication,'id'=>0,'ref'=>$publisher);
        prestalog("Call method ".$WS_METHOD." for ref=".$publisher);
        $result = $soapclient->call($WS_METHOD,$parameters);
        if (! $result)
        {
            print 'Error '.$soapclient->error_str;
            die;
        }

        //var_dump($result);
        $socid=$result['thirdparty']['id'];
        if ($socid)
        {
            $foundthirdparty=true;
            //var_dump($socid);
        }
    }

    if (! $foundthirdparty)
    {
        $parameters = array('authentication'=>$authentication,'id'=>0,'ref'=>$company);
        prestalog("Call method ".$WS_METHOD." for ref=".$company);
        $result = $soapclient->call($WS_METHOD,$parameters);
        if (! $result)
        {
            print 'Error '.$soapclient->error_str;
            die;
        }
        //var_dump($result);
        $socid=$result['thirdparty']['id'];
        if ($socid)
        {
            $foundthirdparty=true;
            //var_dump($socid);
        }
    }

    // Call the WebService method to get amount received
    if ($socid)
    {
        // Define $datelastpayment and $alreadyreceived
        $WS_DOL_URL = $dolibarr_main_url_root.'/webservices/server_supplier_invoice.php';
        $WS_METHOD  = 'getSupplierInvoicesForThirdParty';
        prestalog("Create soapclient_nusoap for URL=".$WS_DOL_URL);
        $soapclient = new soapclient_nusoap($WS_DOL_URL);
        if ($soapclient)
        {
            $soapclient->soap_defencoding='UTF-8';
            $soapclient->decodeUTF8(false);
        }
        $parameters = array('authentication'=>$authentication,'id'=>$socid,'ref'=>'');
        prestalog("Call method ".$WS_METHOD." for socid=".$socid);
        $result = $soapclient->call($WS_METHOD,$parameters);
        if (! $result)
        {
            print 'Error '.$soapclient->error_str;
            die;
        }
        if ($result['result']['result_code'] == 'OK')
        {
            foreach($result['invoices'] as $invoice)
            {
                $isfordolistore=0;
                if (preg_match('/dolistore/i',$invoice['note'])) $isfordolistore=1;
                if (! $isfordolistore)
                {
                    foreach($invoice['lines'] as $line)
                    {
                        if (preg_match('/dolistore/i',$line['desc']))
                        {
                            $isfordolistore++;
                        }
                    }
                }
                if ($isfordolistore)
                {
                    $dolistoreinvoices[]=array('date'=>$invoice['date_invoice'],'amount'=>$invoice['total']);
                    $alreadyreceived+=$invoice['total'];
                }
            }
        }
    }


    print '<h2>';
    aff("Vos informations revenus", "Your payment information", $iso_langue_en_cours);
    print '</h2>';

    // Total amount earned
    aff("Montant total gagné: ", "Total amount earned: ", $iso_langue_en_cours);
    print "<b>".($foundationfeerate*100)."% x ".$totalamount." = ".$mytotalamount."&#8364;</b>";
    print '<br>';
    // Total amount you can claim
    aff("Montant total validé: ", "Total validated amount: ", $iso_langue_en_cours);
    print "<b>".($foundationfeerate*100)."% x ".$totalamountclaimable." = ".$mytotalamountclaimable."&#8364;</b>";
    aff(" &nbsp; (toute vente n'est validée complètement qu'après un délai de 2 mois de rétractation)", "&nbsp; (any sell is validated after a 2 month delay)", $iso_langue_en_cours);
    print '<br>';
    // List of payments
    if (sizeof($dolistoreinvoices))
    {
        aff("Reversements déjà reçus: ","Last payments recevied: ", $iso_langue_en_cours);
        print '<br>'."\n";
        foreach($dolistoreinvoices as $item)
        {
            aff("Date: ","Date: ", $iso_langue_en_cours);
            print ' <b>'.preg_replace('/\s00:00:00Z/','',$item['date']).'</b> - ';
            aff("Montant: ","Amount: ", $iso_langue_en_cours);
            print ' <b>'.$item['amount'].'&#8364;</b><br>'."\n";
            //var_dump($dolistoreinvoices);
        }
    }
    else
    {
        aff("Date du dernier reversement des gains: ","Last payment date: ", $iso_langue_en_cours);
        if ($datelastpayment) print '<b>'.date('Y-m-d',$datelastpayment).'</b>';
        else print aff("<b>Aucun reversement reçu</b>","<b>No payment received yet</b>", $iso_langue_en_cours);
        print '<br>';
    }
    print '<br>';

    // Remain to receive
    aff("Montant restant à réclamer: ","Remained amount to claim: ", $iso_langue_en_cours);
    $remaintoreceive=$mytotalamountclaimable-$alreadyreceived;
    print '<b><font color="#DF7E00">'.round($remaintoreceive,2)."&#8364;</font></b>";
    print '<br><br>';

    // Message to claim
    if ($remaintoreceive)
    {
        $minamount=50;
        if ($remaintoreceive > $minamount)
        {
            aff("Vous pouvez réclamer le montant restant à payer en envoyant une facture à <b>Association Dolibarr</b>, du montant restant à percevoir, par mail à <b>dolistore@dolibarr.org</b>, en indiquant vos coordonnées bancaires pour le virement (RIB ou SWIFT).",
                    "You can claim remained amount to pay by sending an invoice to <b>Association Dolibarr</b>, with remain to pay, by email to <b>dolistore@dolibarr.org</b>. Don't forget to add your bank account number for bank transaction (BIC ou SWIFT).", $iso_langue_en_cours);
            print '<br>';
        }
        else
        {
            aff("Il n'est pas possible de réclamer de reversements pour le moment (montant inférieur à ".$minamount." euros).","It is not possible to claim payments for the moment (amount lower than ".$minamount." euros).", $iso_langue_en_cours);
            print '<br>';
        }
    }
    else
    {
        aff("Il n'est pas possible de réclamer de reversements pour le moment. Votre solde est nul.", "It is not possible to claim payments for the moment. Your sold is null.", $iso_langue_en_cours);
        print '<br>';
    }
    print '<br>';

}
else
{
    aff("Aucun gain généré pour le moment.", "Nothing earned for the moment.", $iso_langue_en_cours);
}

include(dirname(__FILE__).'/../../footer.php');
?>