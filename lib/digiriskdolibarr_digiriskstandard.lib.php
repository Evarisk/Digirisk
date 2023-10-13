<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * \file    lib/digiriskdolibarr_digiriskstandard.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Library files with common functions for digirisk standard
 */

/**
 * Prepare digirisk standard pages header
 *
 * @param  DigiriskStandard $object Digirisk standard
 * @return array            $head   Array of tabs
 * @throws Exception
 */
function digiriskstandard_prepare_head(DigiriskStandard $object): array
{
    // Global variables definitions
    global $conf, $langs, $user;

    // Load translation files required by the page
    saturne_load_langs();

    // Initialize values
    $h    = 0;
    $head = [];

    $head[$h][0] = dol_buildpath('/digiriskdolibarr/view/digiriskstandard/digiriskstandard_card.php', 1);
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-info-circle pictofixedwidth"></i>' . $langs->trans('Informations') : '<i class="fas fa-info-circle"></i>';
    $head[$h][2] = 'standardCard';
    $h++;

    if ($user->rights->digiriskdolibarr->legaldisplay->read) {
        $head[$h][0] = dol_buildpath("/digiriskdolibarr/view/digiriskstandard/digiriskstandard_legaldisplay.php", 1);
        $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-file pictofixedwidth"></i>' . $langs->trans('LegalDisplay') : '<i class="fas fa-file"></i>';
        $head[$h][2] = 'standardLegalDisplay';
        $h++;
    }

    if ($user->rights->digiriskdolibarr->informationssharing->read) {
        $head[$h][0] = dol_buildpath('/digiriskdolibarr/view/digiriskstandard/digiriskstandard_informationssharing.php', 1);
        $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-comment-dots pictofixedwidth"></i>' . $langs->trans('InformationsSharing') : '<i class="fas fa-comment-dots"></i>';
        $head[$h][2] = 'standardInformationsSharing';
        $h++;
    }

    if ($user->rights->digiriskdolibarr->listingrisksaction->read) {
        $head[$h][0] = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_listingrisksaction.php', 1) . '?type=standard';
        $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-exclamation pictofixedwidth"></i>' . $langs->trans('ListingRisksAction') : '<i class="fas fa-exclamation"></i>';
        $head[$h][2] = 'elementListingRisksAction';
        $h++;
    }

    if ($user->rights->digiriskdolibarr->listingrisksphoto->read) {
        $head[$h][0] = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_listingrisksphoto.php', 1) . '?type=standard';
        $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-images pictofixedwidth"></i>' . $langs->trans('ListingRisksPhoto') : '<i class="fas fa-images"></i>';
        $head[$h][2] = 'elementListingRisksPhoto';
        $h++;
    }

    if ($user->rights->digiriskdolibarr->riskassessmentdocument->read) {
        $head[$h][0] = dol_buildpath('/digiriskdolibarr/view/digiriskstandard/digiriskstandard_riskassessmentdocument.php', 1);
        $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-file-alt pictofixedwidth"></i>' . $langs->trans("RiskAssessmentDocument") : '<i class="fas fa-file-alt"></i>';
        $head[$h][2] = 'standardRiskAssessmentDocument';
        $h++;
    }

    $head[$h][0] = dol_buildpath('/saturne/view/saturne_agenda.php', 1) . '?id=' . $object->id . '&module_name=DigiriskDolibarr&object_type=digiriskstandard&handle_photo=true';
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-calendar-alt pictofixedwidth"></i>' . $langs->trans('Events') : '<i class="fas fa-calendar-alt"></i>';
    $head[$h][2] = 'agenda';
    $h++;

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'digiriskstandard@digiriskdolibarr');

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'digiriskstandard@digiriskdolibarr', 'remove');

    return $head;
}
