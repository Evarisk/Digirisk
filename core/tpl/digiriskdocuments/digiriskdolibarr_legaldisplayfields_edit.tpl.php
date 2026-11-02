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
 * \file    core/tpl/digiriskdocuments/digiriskdolibarr_legaldisplayfields_edit.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Edit rows of the company security data shown on the legal display page
 */

/**
 * The following vars must be defined:
 * Global   : $conf, $langs
 * Variable : $form, $allLinks (DigiriskResources::fetchDigiriskResources())
 */

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}

require __DIR__ . '/digiriskdolibarr_labourfields_edit.tpl.php';

// The phone number shown on the legal display is the one of the chosen third party
$emergencyServices = [
	'SAMU'                => 'fa-hospital-alt',
	'Pompiers'            => 'fa-ambulance',
	'Police'              => 'fa-car',
	'AllEmergencies'      => 'fa-phone',
	'RightsDefender'      => 'fa-gavel',
	'PoisonControlCenter' => 'fa-skull-crossbones',
];

foreach ($emergencyServices as $emergencyService => $emergencyServiceIcon) {
	print '<tr>';
	print '<td class="titlefield"><i class="fas ' . $emergencyServiceIcon . '"></i> ' . $langs->trans($emergencyService) . '</td>';
	print '<td>' . img_picto('', 'company', 'class="pictofixedwidth"') . $form->select_company($allLinks[$emergencyService]->id[0] ?? 0, $emergencyService, '', 'SelectThirdParty', 1, 0, [], 0, 'minwidth300 maxwidth500 widthcentpercentminusx') . '</td>';
	print '</tr>';
}

// Responsible to notify
print '<tr>';
print '<td class="titlefield"><i class="fas fa-user"></i> ' . $langs->trans('ResponsibleToNotify') . '</td>';
print '<td>' . img_picto('', 'user', 'class="pictofixedwidth"') . $form->select_dolusers($allLinks['Responsible']->id[0] ?? 0, 'Responsible', 1, null, 0, '', '', $conf->entity, 0, 0, '(u.statut:=:1)', 0, '', 'minwidth300 maxwidth500 widthcentpercentminusx') . '</td>';
print '</tr>';
