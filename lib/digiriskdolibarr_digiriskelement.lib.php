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
 * \file    lib/digiriskdolibarr_digiriskelement.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Library files with common functions for digirisk element
 */

/**
 * Prepare digirisk element pages header
 *
 * @param  DigiriskElement $object Digirisk element
 * @return array           $head  Array of tabs
 * @throws Exception
 */
function digiriskelement_prepare_head(DigiriskElement $object)
{
    // Global variables definitions
    global $conf, $langs, $user;

    // Load translation files required by the page
    saturne_load_langs();

    // Initialize values
    $h    = 1;
    $head = [];

    if ($object->id > 0) {
        if ($user->rights->digiriskdolibarr->digiriskelement->read) {
            $head[$h][0] = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_informations.php', 1) . '?id=' . $object->id;
            $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-info-circle pictofixedwidth"></i>' . $langs->trans('DigiriskStandardInformation') : '<i class="fas fa-info-circle pictofixedwidth"></i>';
            $head[$h][2] = 'elementInformations';
            $h++;
        }

        if ($user->rights->digiriskdolibarr->risk->read) {
            $head[$h][0] = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_risk.php', 1) . '?id=' . $object->id . '&type=risk';
            $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-exclamation-triangle pictofixedwidth"></i>' . $langs->trans('Risks') : '<i class="fas fa-exclamation-triangle"></i>';
            $head[$h][2] = 'elementRisk';
            $h++;

            $head[$h][0] = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_risk.php', 1) . '?id=' . $object->id . '&type=riskenvironmental';
            $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-leaf pictofixedwidth"></i>' . $langs->trans('RiskEnvironmentals') : '<i class="fas fa-leaf"></i>';
            $head[$h][2] = 'elementRiskenvironmental';
            $h++;
        }

        if ($user->rights->digiriskdolibarr->evaluator->read) {
            $head[$h][0] = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_evaluator.php', 1) . '?id=' . $object->id;
            $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-user-check pictofixedwidth"></i>' . $langs->trans('Evaluators') : '<i class="fas fa-user-check"></i>';
            $head[$h][2] = 'elementEvaluator';
            $h++;
        }

        if ($user->rights->digiriskdolibarr->risksign->read) {
            $head[$h][0] = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_risksign.php', 1) . '?id=' . $object->id;
            $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-map-signs pictofixedwidth"></i>' . $langs->trans('RiskSigns') : '<i class="fas fa-map-signs"></i>';
            $head[$h][2] = 'elementRiskSign';
            $h++;
        }

        if ($user->rights->digiriskdolibarr->accident->read) {
            $head[$h][0] = dol_buildpath('/digiriskdolibarr/view/accident/accident_list.php', 1) . '?fromid=' . $object->id;
            $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-user-injured pictofixedwidth"></i>' . $langs->trans('Accidents') : '<i class="fas fa-user-injured"></i>';
            $head[$h][2] = 'elementAccidents';
            $h++;
        }

        if ($object->element_type == 'groupment') {
            if ($user->rights->digiriskdolibarr->listingrisksaction->read && $user->rights->digiriskdolibarr->listingrisksphoto->read && $user->rights->digiriskdolibarr->listingrisksdocument->read) {
                $head[$h][0] = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_listingrisksdocument.php', 1) . '?id=' . $object->id;
                $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-list pictofixedwidth"></i>' . $langs->trans('ListingRisksDocument') : '<i class="fas fa-list"></i>';
                $head[$h][2] = 'elementListingRisksDocument';
                $h++;
            }

            if ($user->rights->digiriskdolibarr->listingrisksenvironmentalaction->read) {
                $head[$h][0] = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_environment.php', 1) . '?id=' . $object->id;
                $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-list pictofixedwidth"></i>' . $langs->trans('Environment') : '<i class="fas fa-list"></i>';
                $head[$h][2] = 'elementEnvironment';
                $h++;
            }
        }

        $head[$h][0] = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_register.php', 1) . '?id=' . $object->id;
        $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fa fa-ticket-alt pictofixedwidth"></i>' . $langs->trans('Registers') : '<i class="fas fa-ticket-alt"></i>';
        $head[$h][2] = 'elementRegister';
    }

    $moreParams['showNav']     = 0;
    $moreParams['handlePhoto'] = 'true';

    return saturne_object_prepare_head($object, $head, $moreParams);
}
