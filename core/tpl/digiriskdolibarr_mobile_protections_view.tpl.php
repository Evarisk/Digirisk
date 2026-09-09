<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/digiriskdolibarr_mobile_protections_view.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Read-only display of the protections (EPI) and required certifications captured by the
 *          mobile quick-creation interfaces. Shared by the prevention plan and the fire permit cards.
 *          Expects: $langs, $object (a fetched object carrying the mobile_* extrafields).
 */

global $langs;

require_once __DIR__ . '/../../lib/digiriskdolibarr_mobile.lib.php';

$object->fetch_optionals();

// Protections (EPI). The ones tied to a risk are displayed with their risk, not here.
$mobileProtections = !empty($object->array_options['options_mobile_protections']) ? json_decode($object->array_options['options_mobile_protections'], true) : [];
if (is_array($mobileProtections)) {
    $mobileProtections = array_filter($mobileProtections, function ($mobileProtection) {
        return empty($mobileProtection['risk_category']);
    });
}
if (is_array($mobileProtections) && !empty($mobileProtections)) {
    $signalisationFile       = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/signalisationCategories.json';
    $signalisationCategories = file_exists($signalisationFile) ? json_decode(file_get_contents($signalisationFile), true) : [];
    $protectionMap           = [];
    if (is_array($signalisationCategories)) {
        foreach ($signalisationCategories as $signalisationCategory) {
            $protectionMap[$signalisationCategory['position']] = $signalisationCategory;
        }
    }

    print '<div class="digirisk-protections-view">';
    print '<div class="digirisk-protections-view__title"><i class="fas fa-hard-hat"></i> ' . $langs->trans('MobilePPProtections') . '</div>';
    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>' . $langs->trans('Label') . '</td>';
    print '<td>' . $langs->trans('Comment') . '</td>';
    print '<td class="center">' . $langs->trans('MobilePPMandatory') . '</td>';
    print '</tr>';
    foreach ($mobileProtections as $mobileProtection) {
        if (!isset($protectionMap[$mobileProtection['position']])) {
            continue;
        }
        $protectionCategory = $protectionMap[$mobileProtection['position']];
        $thumb              = DOL_URL_ROOT . '/custom/digiriskdolibarr/img/' . $protectionCategory['name_thumbnail'];
        print '<tr class="oddeven">';
        print '<td class="nowraponall">';
        print '<img class="cell-risk-view__pic valignmiddle marginrightonly" src="' . $thumb . '" alt="" title="' . dol_escape_htmltag($protectionCategory['name']) . '">';
        print '<span class="valignmiddle">' . dol_escape_htmltag($protectionCategory['name']) . '</span>';
        print '</td>';
        print '<td class="wordbreak">' . (!empty($mobileProtection['comment']) ? dol_escape_htmltag($mobileProtection['comment']) : '<span class="opacitymedium">-</span>') . '</td>';
        print '<td class="center">' . (!empty($mobileProtection['mandatory']) ? '<span class="badge badge-info">' . $langs->trans('Yes') . '</span>' : '<span class="opacitymedium">' . $langs->trans('No') . '</span>') . '</td>';
        print '</tr>';
    }
    print '</table>';
    print '</div>';
    print '</div>';
}

// Required certifications
$mobileCertifications = !empty($object->array_options['options_mobile_certifications']) ? json_decode($object->array_options['options_mobile_certifications'], true) : [];
if (is_array($mobileCertifications) && !empty($mobileCertifications)) {
    $certificationOptions = digiriskGetCertificationOptions(false);
    print '<div class="digirisk-protections-view">';
    print '<div class="digirisk-protections-view__title"><i class="fas fa-id-badge"></i> ' . $langs->trans('MobilePPCertifications') . '</div>';
    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>' . $langs->trans('Label') . '</td>';
    print '<td class="center">' . $langs->trans('MobilePPMandatory') . '</td>';
    print '</tr>';
    foreach ($mobileCertifications as $mobileCertification) {
        $certLabel = isset($certificationOptions[$mobileCertification['code']]) ? $certificationOptions[$mobileCertification['code']] : $mobileCertification['code'];
        print '<tr class="oddeven">';
        print '<td class="wordbreak">' . dol_escape_htmltag($certLabel) . '</td>';
        print '<td class="center">' . (!empty($mobileCertification['mandatory']) ? '<span class="badge badge-info">' . $langs->trans('Yes') . '</span>' : '<span class="opacitymedium">' . $langs->trans('No') . '</span>') . '</td>';
        print '</tr>';
    }
    print '</table>';
    print '</div>';
    print '</div>';
}
