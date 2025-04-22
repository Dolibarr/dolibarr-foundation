<?php
/* Copyright (C) 2025        Mohamed DAOUD              <mdaoud@dolicloud.com>
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

use Luracast\Restler\RestException;

include_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
include_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
include_once DOL_DOCUMENT_ROOT . '/core/lib/website.lib.php';


/**
 * \file    dolibarr-foundation/marketplace/class/api_marketplace.class.php
 * \ingroup product
 * \brief   File for API management of products.
 */

/**
 * API class for product
 *
 * @access public
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Marketplace extends DolibarrApi
{
    /**
     * @var Product {@type Product}
     */
    public $product;

    /**
     * Fixed key for authentication
     */
    private $fixedKey;

    /**
     * Constructor
     *
     * @url     GET /
     */
    public function __construct()
    {
        global $db;
        $this->db = $db;

        $this->fixedKey = getDolGlobalString('MARKETPLACE_PUBLIC_API_KEY');
    }

    /**
     * List products
     *
     * Get a list of products
     *
     * @param int              $categorieid          Category ID
     * @param string           $sortfield            Sort field
     * @param string           $sortorder            Sort order
     * @param int              $limit                Limit for list
     * @param int              $page                 Page number
     * @param string           $search               Search keywords
     * @param string           $lang                 Language
     * @return  array                                Array of Product objects
     *
     * @throws RestException 403 Not allowed
     * @throws RestException 503 System error
     *
     * @url GET /products/
     */
    public function list($categorieid = 0, $sortfield = "datec", $sortorder = 'DESC', $limit = 11, $page = 1, $search = '', $lang = 'en_US')
    {

        $headers = getallheaders();
        $apiKey = isset($headers['DOLAPIKEY']) ? $headers['DOLAPIKEY'] : (isset($_GET['apikey']) ? $_GET['apikey'] : null);
        if ($apiKey !== $this->fixedKey) {
            throw new RestException(403, 'Invalid API key');
        }

        // Length of $search must be at least 2 characters
        if (!empty($search) && strlen(str_replace(' ', '', $search)) < 2) {
            throw new RestException(503, 'Search string must be at least 2 characters');
        }

        if ($categorieid == 0) {
            $categorieid = getDolGlobalInt("MARKETPLACE_ROOT_CATEGORY_ID");
        }

        // Check if the category exists and belongs to the marketplace
        $categstatic = new Categorie($this->db);
        $fulltree = $categstatic->get_full_arbo(Categorie::TYPE_PRODUCT, getDolGlobalInt("MARKETPLACE_ROOT_CATEGORY_ID"), 1);

        if ($categorieid <= 0 || !is_array($fulltree) || !array_key_exists($categorieid, $fulltree)) {
            throw new RestException(503, 'Category not found');
        }

        $lang_array = array('en_US', 'fr_FR', 'es_ES', 'it_IT', 'de_DE');
		if (!in_array($lang, $lang_array)) {
			$lang = 'en_US';
		}

        $root_category_id = $categorieid;
        $current_lang = $lang;
        $isHomePage = false;
        $page_no = $page;
        $orderby = $sortfield;
        $search_words = $search;
        $orderway = $sortorder;

        // Check the filter on version and clean the $search_words.
        // First check into the search field
        $tag = "";
        $reg = array();
        if (preg_match('/(^|\s)(V\d+)(\s|$)/i', $search_words, $reg)) {
            $tag = $reg[2];
            $search_words = preg_replace('/(^|\s)(V\d+)(\s|$)/i', '', $search_words);
        }
        // Now check on the forced parameter $tag
        if ($tag && $tag != 'Specials') {
            $search_words .= ($search_words ? " " : "") . $tag;
        }

        $obj_ret = array();

        $filter = 'o.tosell = 1';
        if (!empty($search_words)) {
            $keywords = explode(" ", $search_words);
            $request = '';
            $order = '';

            foreach ($keywords as $key => $value) {
                if (preg_match('/(^|\s)(V\d+)(\s|$)/i', $value, $reg)) {
                    continue;
                }

                $request .= " AND ";
                $value = $this->db->escape($this->db->escapeforlike($value));

                // Build the search conditions for labels, notes, and vendor names
                $request .= "(ol.label LIKE '%" . $value . "%' OR 
                            ol.note LIKE '" . $value . " %'  OR 
                            ol.note LIKE '% " . $value . " %'  OR 
                            ol.note LIKE '% " . $value . "' OR
                            ol.note LIKE '%>" . $value . " %' OR
                            ol.note LIKE '% " . $value . "<%' OR
                            o.ref LIKE '%" . $value . "%' OR 
                            pe.marketplace_module_keywords REGEXP '(^|, )" . $value . "(,|$)' OR 
                            s.nom LIKE '%" . $value . "%' OR
                            s.name_alias LIKE '%" . $value . "%')";

                // Define the order of results based on matching criteria
                // Level 1: Full match in the label
                $order .= "WHEN ol.label = '" . $value . "' THEN 0 ";
                $order .= "WHEN ol.label LIKE '" . $value . " %' THEN 1 ";
                $order .= "WHEN ol.label LIKE '% " . $value . " %' THEN 1 ";
                $order .= "WHEN ol.label LIKE '% " . $value . "' THEN 1 ";
                $order .= "WHEN o.ref LIKE '" . $value . "%' THEN 1 ";

                // Level 2: Partial match in the label or vendor name
                $order .= "WHEN ol.label LIKE '%" . $value . "%' THEN 2 ";
                $order .= "WHEN pe.marketplace_module_keywords LIKE '%" . $value . "%' THEN 2 ";
                $order .= "WHEN s.nom LIKE '%" . $value . "%' THEN 2 ";
                $order .= "WHEN s.name_alias LIKE '%" . $value . "%' THEN 2 ";

                // Level 3: Full Match in the notes(Long description) field
                $order .= "WHEN ol.note LIKE '" . $value . " %' THEN 3 ";
                $order .= "WHEN ol.note LIKE '% " . $value . " %' THEN 3 ";
                $order .= "WHEN ol.note LIKE '% " . $value . "' THEN 3 ";
                $order .= "WHEN ol.note LIKE '%>" . $value . " %' THEN 3 ";
                $order .= "WHEN ol.note LIKE '% " . $value . "<%' THEN 3 ";

                //$order .= "WHEN ol.description LIKE '%" . $value . "%' THEN 4 ";
            }

            // Finalize filter and order
            $filter .= $request;
            if ($order) {
                $order = "ORDER BY CASE ".$order." END, o.datec DESC";  // Complete the order clause
            }
        }


        switch ($orderby) {
            case 'price':
                $sortfield = 'price_ttc';
                break;
            case 'name':
                $sortfield = 'label';
                break;
            case 'reference':
                $sortfield = 'ref';
                break;
            default:
                $sortfield = 'datec';
                break;
        }

        switch ($orderway) {
            case 'asc':
                $sortorder = "ASC";
                break;
            case 'desc':
                $sortorder = "DESC";
                break;
            default:
                $sortorder = "DESC";
                break;
        }

        $offset = ($page_no - 1) * $limit;

        // Count SQL - Replaced with a select COUNT()
        $countSql = "SELECT COUNT(DISTINCT c.fk_product) as count";
        $countSql .= " FROM llx_categorie_product as c";
        $countSql .= " INNER JOIN llx_product as o ON c.fk_product = o.rowid";
        $countSql .= " INNER JOIN llx_product_lang as ol ON ol.fk_product = o.rowid";
        $countSql .= " LEFT JOIN llx_product_fournisseur_price as pfp ON pfp.fk_product = o.rowid";
        $countSql .= " LEFT JOIN llx_societe as s ON pfp.fk_soc = s.rowid";
        $countSql .= " LEFT JOIN llx_product_extrafields as pe ON pe.fk_object = o.rowid";
        $countSql .= " WHERE o.entity IN (1) AND c.fk_categorie = " . ((int) $root_category_id);
        $countSql .= " AND ol.lang = '" . $this->db->escape($current_lang) . "'";
        $countSql .= " AND " . $filter;

        $resql = $this->db->query($countSql);
        $count_prods = 0;
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            $count_prods = $obj->count;
        }


        // PRODUCT SQL
        $sql = "SELECT c.fk_product as id, o.ref, o.ref_ext, o.datec, o.price_ttc, ol.label, ol.description, o.tms, pe.marketplace_min_version as dolibarr_min, pe.marketplace_max_version as dolibarr_max, pe.marketplace_module_version as module_version ";
        $sql .= "FROM llx_product as o ";
        $sql .= "INNER JOIN llx_categorie_product as c ON c.fk_product = o.rowid ";
        $sql .= "INNER JOIN llx_product_lang as ol ON ol.fk_product = o.rowid ";
        $sql .= "LEFT JOIN llx_product_fournisseur_price as pfp ON pfp.fk_product = o.rowid ";
        $sql .= "LEFT JOIN llx_societe as s ON pfp.fk_soc = s.rowid ";
        $sql .= "LEFT JOIN llx_product_extrafields as pe ON pe.fk_object = o.rowid ";
        $sql .= "WHERE o.entity IN (1) AND c.fk_categorie = " . ((int) $root_category_id) . " AND ";
        $sql .= "ol.lang = '" . $this->db->escape($current_lang) . "' AND ";
        $sql .= $filter;
        $sql .= " GROUP BY c.fk_product, o.ref, o.ref_ext, ol.label, ol.description, o.datec, o.tms, o.price_ttc, s.nom, s.name_alias"; // Added GROUP BY clause to handle multiple supplier prices

        $searchwithouttag = trim(preg_replace('/(^|\s)(V\d+)(\s|$)/i', '', $search_words));
        if ($sortfield == 'datec' && $sortorder == 'DESC' && !empty($searchwithouttag)) {
            $sql .= " " . $order;
        } else {
            if ($isHomePage) {
                $sql .= " ORDER BY RAND() ";
            } else {
                $sql .= $this->db->order($sortfield, $sortorder);
            }
        }

        if ($limit > 0 || $offset > 0) {
            $sql .= $this->db->plimit($limit + 1, $offset);
        }
        //print $sql;

        $result = $this->db->query($sql);
        if ($result) {
            $num = $this->db->num_rows($result);
            $i = 0;
            while ($i < $num && $i < $limit) {
                $obj = $this->db->fetch_object($result);

                // Get cover photo URL
                $obj->cover_photo_url = $this->getCoverPhotoUrl($obj->id);

                // If a free module, return download link
                if ($obj->price_ttc == 0) {
                    $obj->download_link = "/_service_download.php?t=free&p=" . $obj->id;
                }
                $obj_ret[] = $obj;
                $i++;
            }
        }

        return array('products' => $obj_ret, 'total' => $count_prods);
    }

    /**
     * List products categories
     *
     * Get a list of product categories
     *
     * @url GET /categories/
     * @return array Array of organized categories
     * @throws RestException 503 System error
     */
    public function listCategories($lang = 'en_US') {

        $headers = getallheaders();
        $apiKey = $headers['DOLAPIKEY'] ?? $_GET['apikey'] ?? null;
        if ($apiKey !== $this->fixedKey) {
            throw new RestException(403, 'Invalid API key');
        }

        $mcid = getDolGlobalInt("MARKETPLACE_ROOT_CATEGORY_ID");
        if (!$mcid) {
            throw new RestException(503, 'Marketplace root category ID not defined');
        }

        $lang_array = array('en_US', 'fr_FR', 'es_ES', 'it_IT', 'de_DE');
		if (!in_array($lang, $lang_array)) {
			$lang = 'en_US';
		}

        $organized_tree = $this->getOrganizedTree($mcid, 'position', $lang);
        return $organized_tree;
    }

    /**
     * Get organized tree of categories
     *
     * @param int       $id Root category ID
     * @param string    $sort Sort field
     * @param string    $lang Language
     * @return array Organized tree of categories
     */
    private function getOrganizedTree($id, $sort = 'position', $lang = 'en_US') {

        $root_cat_object = new Categorie($this->db);
        $result = $root_cat_object->fetch($id);
        if (!$result) {
            return array();
        }

        $root_category_id = $root_cat_object->id;
        $root_category_type = $root_cat_object->type;
        $cats = $root_cat_object->get_filles();
        if (count($cats) < 1) {
            return array();
        } else {
            $categstatic = new Categorie($this->db);
            $fulltree = $categstatic->get_full_arbo($root_category_type, $root_category_id, 1, $lang);
            $organized_tree = $this->buildTree($fulltree, $root_category_id);
            usort($organized_tree, $this->buildSorter($sort));
            return $organized_tree;
        }
    }

    /**
     * Build tree of categories
     *
     * @param array $elements Elements to organize
     * @param int $parentId Parent ID
     * @return array Organized tree
     */
    private function buildTree(array &$elements, $parentId = 0) {
        $branch = array();
        foreach ($elements as $element) {
            // Remove usefull fields
            unset($element['visible'], $element['picto'], $element['fullpath'], $element['fulllabel'], $element['ref_ext']);

            if ($element['fk_parent'] == $parentId) {
                $children = $this->buildTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[$element['rowid']] = $element;
            }
        }
        return $branch;
    }

    /**
     * Build sorter function
     *
     * @param string $key Sort key
     * @return callable Sorter function
     */
    private function buildSorter($key) {
        return function ($a, $b) use ($key) {
            return strnatcmp($a[$key], $b[$key]);
        };
    }

    function getCoverPhotoUrl($productId) {

        $product = new Product($this->db);
        $product->fetch($productId);

        $url = "#";
        $files = getPublicFilesOfObject($product);

        foreach ($files as $key => $file) {
            $filename = $file['filename'];
            // Only consider files containing "image_cover"
            if (str_contains($filename, 'image_cover')) {
                $url = $file['url'];
            }
        }

        return $url;
    }

}