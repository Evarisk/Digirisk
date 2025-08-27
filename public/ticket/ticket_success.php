<?php
/* Copyright (C) 2021-2024 EVARISK <technique@evarisk.com>
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
 * \file    public/ticket/ticket_success.php
 * \ingroup digiriskdolibarr
 * \brief   Public page to view success on ticket
 */

if (!defined('NOTOKENRENEWAL')) {
    define('NOTOKENRENEWAL', 1);
}
if (!defined('NOREQUIREMENU')) {
    define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
    define('NOREQUIREHTML', 1);
}
if (!defined('NOLOGIN')) {      // This means this output page does not require to be logged
    define('NOLOGIN', 1);
}
if (!defined('NOCSRFCHECK')) {  // We accept to go on this page from external website
    define('NOCSRFCHECK', 1);
}
if (!defined('NOIPCHECK')) {    // Do not check IP defined into conf $dolibarr_main_restrict_ip
    define('NOIPCHECK', 1);
}
if (!defined('NOBROWSERNOTIF')) {
    define('NOBROWSERNOTIF', 1);
}

// Load DigiriskDolibarr environment
if (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$trackID = GETPOST('track_id', 'alpha');

// Initialize technical objects
$object = new Ticket($db);
$category = new Categorie($db);

$hookmanager->initHooks(['publicticket', 'saturnepublicinterface']); // Note that conf->hooks_modules contains array

// Load object
$object->fetch(0, '', $trackID);
$categories = $category->containing($object->id, $object->element);

$childrenMap = [];
$idMap       = [];
foreach ($categories as $cat) {
    $childrenMap[$cat->fk_parent][] = $cat;
    $idMap[$cat->id]                = $cat;
}
$path    = [];
$current = $idMap[$conf->global->DIGIRISKDOLIBARR_TICKET_MAIN_CATEGORY] ?? null;
while ($current) {
    $path[] = $current->id;

    if (isset($childrenMap[$current->id])) {
        $current = $childrenMap[$current->id][0];
    } else {
        break;
    }
}
$successMessage = $langs->transnoentities('YouMustNotifyYourHierarchy');
foreach ($path as $catId) {
    $cat    = $idMap[$catId];
    $config = json_decode($cat->array_options['options_ticket_category_config'], true) ?? [];
    if (!empty($config['success_message'])) {
        $successMessage = $config['success_message'];
    }
}

$substitutionArray = getCommonSubstitutionArray($langs, 0, null, $object);
complete_substitutions_array($substitutionArray, $langs, $object);
$ticketSuccessMessage = make_substitutions($successMessage, $substitutionArray);

/*
 * View
 */

$title = $langs->trans('PublicTicket');

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

saturne_header(0,'', $title, '', '', 0, 0, [], [], '', 'page-public-card');

if (!getDolGlobalInt('DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE')) {
    print $langs->transnoentities('TicketPublicInterfaceForbidden');
    exit;
}

?>
<div class="public-card__container" data-public-interface="true">
    <div class="public-card__header">
        <div class="header-information center">
            <div class="left"><a href="<?php echo dol_buildpath('/custom/digiriskdolibarr/public/ticket/create_ticket.php?entity=' . $conf->entity, 1); ?>" class="information-back"><i class="fas fa-sm fa-chevron-left paddingright"></i><?php echo $langs->trans('Back'); ?></a></div>
            <div class="information-title"><?php echo $langs->trans('TicketSuccess') . ' <b>' . $object->ref . '</b>'; ?></div>
            <span class="wpeo-notice notice-info left" style="margin-left: 16%; width: 70%">
                <span class="notice-content">
                    <span class="notice-subtitle"><?php echo $langs->transnoentities($ticketSuccessMessage); ?></span>
                </span>
            </span>
        </div>
    </div>
</div>
<?php

// End of page
llxFooter('', 'public');
$db->close();
