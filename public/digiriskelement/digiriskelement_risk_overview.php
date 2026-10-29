<?php
/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
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
 * \file    public/digiriskelement/digiriskelement_risk_overview.php
 * \ingroup digiriskdolibarr
 * \brief   Public overview of the risks of the groupments and work units, in three tabs: the risks
 *          of the INRS brochure ED 840, the strenuousness factors and the psychosocial risks.
 *
 * Meant for the people the single document is presented to, who have no Dolibarr account: the page
 * is reached through a link carrying a secret key, set up in the risk assessment document
 * configuration. It shows counts and levels; a number of risks details on hover the worst risks it
 * counts (description, cotation, photo and comment of their assessment).
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if (!defined('NOLOGIN'))        define('NOLOGIN', '1');
if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', '1');
if (!defined('NOIPCHECK'))      define('NOIPCHECK', '1');
if (!defined('NOBROWSERNOTIF')) define('NOBROWSERNOTIF', '1');

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../class/riskanalysis/riskassessment.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_riskoverview.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $mysoc;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$key    = GETPOST('key', 'alphanohtml');
$tab    = GETPOST('tab', 'aZ09');
$action = GETPOST('action', 'aZ09');
$entity = !isModEnabled('multicompany') ? $conf->entity : GETPOSTINT('entity');

if ($entity > 0) {
    $conf->setEntityValues($db, $entity);
}

if (!in_array($tab, DIGIRISK_RISK_OVERVIEW_TABS, true)) {
    $tab = DIGIRISK_RISK_OVERVIEW_TABS[0];
}

// Nobody is logged in
$user = new User($db);

$hookmanager->initHooks(['riskoverviewpublic', 'saturnepublicinterface']);

// The key opens the page: without it, nothing about the risks is shown
$accessAllowed = digiriskRiskOverviewCheckPublicKey($key);

$tabUrl = function (string $tabName) use ($key) {
    global $conf;
    return $_SERVER['PHP_SELF'] . '?' . http_build_query(['entity' => $conf->entity, 'key' => $key, 'tab' => $tabName]);
};

$overviewRows    = [];
$overviewColumns = [];
if ($accessAllowed) {
    $digiriskElement           = new DigiriskElement($db);
    $activeDigiriskElementList = $digiriskElement->getActiveDigiriskElements();
    if (!is_array($activeDigiriskElementList)) {
        $activeDigiriskElementList = [];
    }

    // Psychosocial assessment of each work unit, as shown by the risk list: the template fills these
    $rpsLines         = [];
    $rpsSubCategories = [];
    $rpsScale         = [];
    require __DIR__ . '/../../core/tpl/riskanalysis/risk/digiriskdolibarr_rps_scope_data.tpl.php';

    // Every tab is rendered, the visitor switches between them without reloading the page
    $overviewRows = digiriskRiskOverviewRows($db, $activeDigiriskElementList, $rpsLines, $rpsSubCategories);
    foreach (array_keys(DIGIRISK_RISK_OVERVIEW_CATEGORIES) as $categoryTab) {
        $overviewColumns[$categoryTab] = digiriskRiskOverviewCategoryColumns($categoryTab);
    }
    $overviewColumns['rps'] = digiriskRiskOverviewFactorColumns($rpsSubCategories);

    // The same tables as an Excel workbook, one sheet per tab
    if ($action == 'export') {
        digiriskRiskOverviewExportXlsx($overviewRows, $overviewColumns, $rpsScale);
        $db->close();
        exit;
    }
}

$exportUrl = $_SERVER['PHP_SELF'] . '?' . http_build_query(['entity' => $conf->entity, 'key' => $key, 'action' => 'export']);

/*
 * View
 */

$title = $langs->trans('RiskOverviewPublicTitle');

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

if (!$accessAllowed) {
    http_response_code(403);
}

saturne_header(0, '', $title, '', '', 0, 0, [], [], '', 'page-public-card digirisk-risk-overview-public');

require __DIR__ . '/../../core/tpl/digiriskelement/digiriskelement_risk_overview_public.tpl.php';

llxFooter('', 'public');
$db->close();
