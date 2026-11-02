<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/digiriskdocuments/digiriskdolibarr_companydata_action.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Saves the company data edited from the legal display and information sharing pages
 */

/**
 * The following vars must be defined:
 * Global   : $conf, $db, $langs, $user
 * Variable : $action, $permissiontoeditcompanydata,
 *            $companyDataResources (digirisk resource ref => element type, the ref is also the field name),
 *            $companyDataDates (constant => field name of a selectDate)
 */

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

if ($action == 'update' && GETPOST('cancel', 'alpha')) {
	$action = '';
}

if ($action == 'update' && $permissiontoeditcompanydata) {
	$resources = new DigiriskResources($db);
	$error     = 0;

	foreach ($companyDataResources as $resourceRef => $elementType) {
		// A multiple select posts an array, a single one a scalar, and an empty choice comes as 0 or -1
		$elementIds = GETPOSTISARRAY($resourceRef) ? GETPOST($resourceRef, 'array') : [GETPOSTINT($resourceRef)];
		// setDigiriskResources concatenates the ids in its INSERT without quoting them: cast them
		$elementIds = array_values(array_filter(array_map('intval', $elementIds), function ($elementId) {
			return $elementId > 0;
		}));
		// The element type must be the one of the configuration pages: previous resources are only disabled for the same type
		if ($resources->setDigiriskResources($db, $user->id, $resourceRef, $elementType, $elementIds, $conf->entity) < 0) {
			$error++;
		}
	}

	foreach ($companyDataDates as $constName => $fieldName) {
		$day   = GETPOSTINT($fieldName . 'day');
		$month = GETPOSTINT($fieldName . 'month');
		$year  = GETPOSTINT($fieldName . 'year');
		// Same Y-m-d string as the social configuration page, empty when the date is cleared
		dolibarr_set_const($db, $constName, checkdate($month, $day, $year) ? sprintf('%04d-%02d-%02d', $year, $month, $day) : '', 'date', 0, '', $conf->entity);
	}

	if (!$error) {
		setEventMessages($langs->trans('SetupSaved'), []);
		header('Location: ' . $_SERVER['PHP_SELF']);
		exit;
	}

	setEventMessages($resources->error, [], 'errors');
	$action = 'edit';
}
