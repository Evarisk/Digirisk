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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/tpl/accidentinvestigation/digiriskdolibarr_accidentinvestigation_itamami_schema.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   ITAMAMI schema of an accident investigation: the individual, the prescribed task, the material
 *          and the environment cross each other in the real activity, which leads to the accident.
 *          Hidden while no axis is filled in, like the ITAMAMI table of the PDF report.
 *
 * Rendered by view/accidentinvestigation/accidentinvestigation_card.php (inherits its scope). Expects:
 *   $langs, $form
 *   $object    AccidentInvestigation  Investigation whose ITAMAMI axes are drawn
 *   $accident  Accident               Investigated accident
 */

// The real activity is the middle of the schema, the four factors are its corners
$itamamiAxes = [
    'individual'  => ['code' => 'I',  'picto' => 'fa-user'],
    'task'        => ['code' => 'TA', 'picto' => 'fa-clipboard-list'],
    'activity'    => ['code' => '',   'picto' => 'fa-running'],
    'material'    => ['code' => 'MA', 'picto' => 'fa-tools'],
    'environment' => ['code' => 'MI', 'picto' => 'fa-industry'],
];

$itamamiFilled = 0;
foreach (array_keys($itamamiAxes) as $itamamiAxis) {
    if (dol_strlen((string) $object->{'itamami_' . $itamamiAxis}) > 0) {
        $itamamiFilled = 1;
    }
}

if (empty($itamamiFilled)) {
    return;
}

print '<div class="itamami-schema">';
print '<div class="itamami-schema__header">';
print '<span class="itamami-schema__icon"><i class="fas fa-project-diagram"></i></span>';
print '<div>';
print '<div class="itamami-schema__title">' . $langs->trans('ItamamiSchema') . '</div>';
print '<div class="itamami-schema__hint">' . $langs->trans('ItamamiSchemaHelp') . '</div>';
print '</div>';
print '</div>';

print '<div class="itamami-schema__body">';

print '<div class="itamami-schema__factors">';
// Corner to corner diagonals, drawn behind the nodes: only their part between two nodes stays visible
print '<svg class="itamami-schema__links" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">';
print '<line x1="0" y1="0" x2="100" y2="100" vector-effect="non-scaling-stroke"></line>';
print '<line x1="100" y1="0" x2="0" y2="100" vector-effect="non-scaling-stroke"></line>';
print '</svg>';
foreach ($itamamiAxes as $itamamiAxis => $itamamiAxisInfo) {
    $itamamiValue = (string) $object->{'itamami_' . $itamamiAxis};

    print '<div class="itamami-schema__node itamami-schema__node--' . $itamamiAxis . '">';
    print '<div class="itamami-schema__node-header">';
    print '<span class="itamami-schema__badge"><i class="fas ' . $itamamiAxisInfo['picto'] . '"></i>' . ($itamamiAxisInfo['code'] ? '<span>' . $itamamiAxisInfo['code'] . '</span>' : '') . '</span>';
    print '<span class="itamami-schema__label">' . $form->textwithpicto($langs->trans('ItamamiSchema' . ucfirst($itamamiAxis)), $langs->trans('Itamami' . ucfirst($itamamiAxis) . 'Help')) . '</span>';
    print '</div>';
    if (dol_strlen($itamamiValue) > 0) {
        print '<div class="itamami-schema__text">' . dolPrintHTML($itamamiValue) . '</div>';
    } else {
        print '<div class="itamami-schema__text itamami-schema__text--empty">' . $langs->trans('ItamamiSchemaNotFilled') . '</div>';
    }
    print '</div>';
}
print '</div>';

print '<div class="itamami-schema__outcome">';
print '<span class="itamami-schema__arrow" aria-hidden="true"></span>';
print '<div class="itamami-schema__node itamami-schema__node--accident">';
print '<div class="itamami-schema__node-header">';
print '<span class="itamami-schema__badge"><i class="fas fa-user-injured"></i></span>';
print '<span class="itamami-schema__label">' . $langs->trans('Accident') . '</span>';
print '</div>';
print '<div class="itamami-schema__text">';
if ($accident->id > 0) {
    print '<div>' . $accident->getNomUrl() . '</div>';
    print (dol_strlen($accident->label) > 0 ? '<div>' . dol_escape_htmltag($accident->label) . '</div>' : '');
    print (!empty($accident->accident_date) ? '<div class="itamami-schema__date">' . dol_print_date($accident->accident_date, 'dayhour', 'tzuser') . '</div>' : '');
}
print '</div>';
print '</div>';
print '</div>';

print '</div>';
print '</div>';
