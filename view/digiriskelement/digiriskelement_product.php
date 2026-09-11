<?php
/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    view/digiriskelement/digiriskelement_product.php
 * \ingroup digiriskdolibarr
 * \brief   Page to associate products to a digirisk element
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Load Digirisk libraries
require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/digiriskelement_product.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskelement.lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $moduleName, $moduleNameLowerCase, $moduleNameUpperCase, $user;

// Load translation files required by the page
saturne_load_langs();
$langs->load("products");

// Get parameters
$id     = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');

// Initialize technical objects
$object = new DigiriskElement($db);

$hookmanager->initHooks(['digiriskelementproduct', 'digiriskelementview', 'globalcard']);

// Load object
require_once DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once

// Security check
$permissionToRead = $user->rights->digiriskdolibarr->digiriskelement->read;
$permissionToAdd = $user->rights->digiriskdolibarr->digiriskelement->write; // adjust if there is a specific right
saturne_check_access($permissionToRead, $object);

/*
 * Actions
 */
if ($action == 'add' && $permissionToAdd) {
    $fk_product = GETPOST('fk_product', 'int');
    if ($fk_product > 0) {
        $assoc = new DigiriskElementProduct($db);
        
        // Prevent duplicates
        $existing = $assoc->fetchAllByElement($object->id);
        $already_linked = false;
        foreach ($existing as $e) {
            if ($e->fk_product == $fk_product) {
                $already_linked = true;
                break;
            }
        }
        
        if ($already_linked) {
            setEventMessages($langs->trans('RecordAlreadyExists'), null, 'warnings');
        } else {
            $assoc->fk_digiriskelement = $object->id;
            $assoc->fk_product = $fk_product;
            $result = $assoc->create($user);
            if ($result < 0) {
                setEventMessages($assoc->error, $assoc->errors, 'errors');
            } else {
                setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
            }
        }
    }
}

if ($action == 'delete' && $permissionToAdd) {
    $assoc_id = GETPOST('assoc_id', 'int');
    if ($assoc_id > 0) {
        $assoc = new DigiriskElementProduct($db);
        if ($assoc->fetch($assoc_id) > 0) {
            $assoc->delete($user);
            setEventMessages($langs->trans('RecordDeleted'), null, 'mesgs');
        }
    }
}


/*
 * View
 */

$title   = $langs->trans('ProductsServices');
$helpUrl = 'FR:Module_Digirisk';

digirisk_header($title, $helpUrl);

// Part to show record
saturne_get_fiche_head($object, 'elementProduct', $title);

// Object card
list($morehtmlref, $moreParams) = $object->getBannerTabContent();
saturne_banner_tab($object,'ref','none', 0, 'ref', 'ref', $morehtmlref, true, $moreParams);

print '<div class="fichecenter"><br>';

$sql = "SHOW TABLES LIKE '" . MAIN_DB_PREFIX . "digiriskdolibarr_digiriskelement_product'";
$res = $db->query($sql);
$table_exists = ($res && $db->num_rows($res) > 0);

if (!$table_exists) {
    $link = dol_buildpath('/admin/modules.php', 1);
    print '<div class="warning">' . $langs->trans('DigiriskUpdateRequiredForProducts', $link) . '</div>';
} else {

// Add product form
if ($permissionToAdd) {
    require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
    $form = new Form($db);
    
    print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="add">';
    print '<input type="hidden" name="id" value="' . $object->id . '">';
    
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>' . $langs->trans("LinkProductService") . '</td>';
    print '</tr>';
    print '<tr class="impair">';
    print '<td>';
    print $form->select_produits('', 'fk_product', '', 0, 0, 1, 2, '', 1, array(), 0, '1', 0, 'maxwidth300');
    print '&nbsp;<input type="submit" class="button" value="' . $langs->trans("Add") . '">';
    print '</td>';
    print '</tr>';
    print '</table>';
    print '</form>';
    print '<br>';
}


// List of products
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Ref') . '</td>';
print '<td>' . $langs->trans('Label') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td align="center"></td>';
print '</tr>';

$assoc_obj = new DigiriskElementProduct($db);
$assocs = $assoc_obj->fetchAllByElement($object->id);

if (!empty($assocs)) {
    $product = new Product($db);
    foreach ($assocs as $assoc) {
        if ($product->fetch($assoc->fk_product) > 0) {
            print '<tr class="oddeven">';
            print '<td>' . $product->getNomUrl(1) . '</td>';
            print '<td>' . $product->label . '</td>';
            print '<td>' . dol_htmlcleanlastbr($product->description) . '</td>';
            print '<td align="center">';
            if ($permissionToAdd) {
                print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=delete&assoc_id=' . $assoc->id . '&token=' . newToken() . '">';
                print '<i class="fas fa-trash-alt"></i>';
                print '</a>';
            }
            print '</td>';
            print '</tr>';
        }
    }
} else {
    print '<tr class="oddeven"><td colspan="4" class="opacitymedium">' . $langs->trans('None') . '</td></tr>';
}

print '</table>';

} // End if table_exists

print '</div>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
