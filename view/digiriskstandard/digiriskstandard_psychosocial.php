<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    view/digiriskstandard/digiriskstandard_psychosocial.php
 * \ingroup digiriskdolibarr
 * \brief   Psychosocial risks of the company (level 0): qualification of every work unit,
 *          taken once for the whole organisation, whatever its groupments
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

// Load Digirisk libraries
require_once __DIR__ . '/../../class/digiriskstandard.class.php';
require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskstandard.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $moduleNameLowerCase, $user;

// Load translation files required by the page
saturne_load_langs();

// Initialize technical objects
$object          = new DigiriskStandard($db);
$digiriskElement = new DigiriskElement($db);
$project         = new Project($db);

$hookmanager->initHooks(['digiriskstandardpsychosocial', 'digiriskstandardview', 'globalcard']); // Note that conf->hooks_modules contains array

// Load object
$object->fetch(getDolGlobalInt('DIGIRISKDOLIBARR_ACTIVE_STANDARD'));

// Security check - Qualifying a work unit is saved by core/ajax/save_rps_assessment.php, which checks the risk write right
$permissiontoread = $user->rights->digiriskdolibarr->digiriskstandard->read && $user->rights->digiriskdolibarr->risk->read;
saturne_check_access($permissiontoread);

/*
 * View
 */

$title   = $langs->trans('PsychosocialRisks');
$helpUrl = 'FR:Module_Digirisk';

digirisk_header($title, $helpUrl);

// Part to show record
saturne_get_fiche_head($object, 'standardPsychosocial', $title);

// Object card
// Project
$moreHtmlRef = '<div class="refidno">';
$project->fetch(getDolGlobalInt('DIGIRISKDOLIBARR_DU_PROJECT'));
$moreHtmlRef .= $langs->trans('Project') . ' : ' . getNomUrlProject($project, 1, 'blank', 1);
$moreHtmlRef .= '</div>';

$moduleNameLowerCase = 'mycompany';
saturne_banner_tab($object, 'ref', 'none', 0, 'ref', 'ref', $moreHtmlRef, true);
$moduleNameLowerCase = 'digiriskdolibarr';

print '<div class="fichecenter"><br>';

// Every work unit of the organisation, grouped under its groupment: no groupment is mandatory,
// so the qualification is no longer carried by the first one
$activeDigiriskElementList = $digiriskElement->getActiveDigiriskElements();
if (!is_array($activeDigiriskElementList)) {
    $activeDigiriskElementList = [];
}

require_once __DIR__ . '/../../core/tpl/riskanalysis/risk/digiriskdolibarr_rps_scope_data.tpl.php';
if (empty($rpsLines)) {
    print '<div class="opacitymedium">' . $langs->trans('PsychosocialRisksNoWorkUnit') . '</div>';
} else {
    require_once __DIR__ . '/../../core/tpl/riskanalysis/risk/digiriskdolibarr_rps_scope_view.tpl.php';
    require_once __DIR__ . '/../../core/tpl/riskanalysis/risk/digiriskdolibarr_rps_dict_modal.tpl.php';
}

print '</div>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
