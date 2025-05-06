<?php
/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
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
 *	\file       digiriskdolibarrindex.php
 *	\ingroup    digiriskdolibarr
 *	\brief      Home page of digiriskdolibarr top menu
 */

// Load DigiQuali environment
if (file_exists('digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/digiriskdolibarr.main.inc.php';
} elseif (file_exists('../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} else {
	die('Include of digiriskdolibarr main fails');
}

// Better performance by disabling some features not used in this page
if (!defined('DISABLE_CKEDITOR')) {
    define('DISABLE_CKEDITOR', 1);
}
if (!defined('DISABLE_JQUERY_TABLEDND')) {
    define('DISABLE_JQUERY_TABLEDND', 1);
}
if (!defined('DISABLE_MULTISELECT')) {
    define('DISABLE_MULTISELECT', 1);
}

require_once __DIR__ . '/core/tpl/digiriskdolibarr_projectcreation_action.tpl.php';

$moreParams = [
    'LoadRiskAssessmentDocument' => 1,
    'LoadAccident'               => 1,
    'LoadEvaluator'              => 1,
    'LoadDigiriskResources'      => 1,
    'LoadDigiriskElement'        => 1,
    'LoadSaturneTask'            => 1,
    'LoadRisk'                   => 1,
    'LoadTicketDashboard'        => 1,
    'specialModuleNameLowerCase' => 'digirisk'
];

require_once __DIR__ . '/../saturne/core/tpl/index/index_view.tpl.php';
