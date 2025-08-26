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
 * \file    lib/digiriskdolibarr.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Library files with common functions for admin conf
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function digiriskdolibarr_admin_prepare_head(): array
{
    // Global variables definitions
    global $conf, $langs;

    // Load translation files required by the page
    saturne_load_langs();

    // Initialize values
    $h    = 0;
    $head = [];

    $head[$h][0] = dol_buildpath('/digiriskdolibarr/admin/config/riskassessmentdocument.php', 1);
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-exclamation-triangle pictofixedwidth"></i>' . $langs->trans('RiskAssessmentDocument') : '<i class="fas fa-exclamation-triangle"></i>';
    $head[$h][2] = 'riskassessmentdocument';
    $h++;

    $head[$h][0] = dol_buildpath('/digiriskdolibarr/admin/config/digiriskelement.php', 1);
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-network-wired pictofixedwidth"></i>' . $langs->trans('Organization') : '<i class="fas fa-network-wired"></i>';
    $head[$h][2] = 'digiriskelement';
    $h++;

    $head[$h][0] = dol_buildpath('/digiriskdolibarr/admin/config/preventionplan.php', 1);
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-info pictofixedwidth"></i>' . $langs->trans('PreventionPlan') : '<i class="fas fa-info"></i>';
    $head[$h][2] = 'preventionplan';
    $h++;

    $head[$h][0] = dol_buildpath('/digiriskdolibarr/admin/config/firepermit.php', 1);
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-fire-alt pictofixedwidth"></i>' . $langs->trans('FirePermit') : '<i class="fas fa-fire-alt"></i>';
    $head[$h][2] = 'firepermit';
    $h++;

    $head[$h][0] = dol_buildpath('/digiriskdolibarr/admin/config/accident.php', 1);
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-user-injured pictofixedwidth"></i>' . $langs->trans('Accident') : '<i class="fas fa-user-injured"></i>';
    $head[$h][2] = 'accident';
    $h++;

    $head[$h][0] = dol_buildpath('/digiriskdolibarr/admin/ticket/ticket.php', 1);
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fa fa-ticket-alt pictofixedwidth"></i>' . $langs->trans('WHSRegister') : '<i class="fas fa-ticket-alt"></i>';
    $head[$h][2] = 'ticket';
    $h++;

    $head[$h][0] = dol_buildpath('/digiriskdolibarr/admin/config/digiai.php', 1);
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fa fa-magic pictofixedwidth"></i>' . $langs->trans('DigiAI') : '<i class="fas fa-magic"></i>';
    $head[$h][2] = 'digiai';
    $h++;

    $head[$h][0] = dol_buildpath('/digiriskdolibarr/admin/event.php', 1);
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-calendar-alt pictofixedwidth"></i>' . $langs->trans('Events') : '<i class="fas fa-calendar-alt"></i>';
    $head[$h][2] = 'event';
    $h++;

    $head[$h][0] = dol_buildpath('/saturne/admin/pwa.php', 1). '?module_name=DigiriskDolibarr&start_url=' . dol_buildpath('/custom/digiriskdolibarr/public/ticket/create_ticket.php', 3);
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-mobile pictofixedwidth"></i>' . $langs->trans('PWA') : '<i class="fas fa-mobile"></i>';
    $head[$h][2] = 'pwa';
    $h++;

    $head[$h][0] = dol_buildpath('/saturne/admin/documents.php?module_name=DigiriskDolibarr', 1);
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-file-alt pictofixedwidth"></i>' . $langs->trans('YourDocuments') : '<i class="fas fa-file-alt"></i>';
    $head[$h][2] = 'documents';
    $h++;

    $head[$h][0] = dol_buildpath('/digiriskdolibarr/admin/setup.php', 1);
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-cog pictofixedwidth"></i>' . $langs->trans('ModuleSettings') : '<i class="fas fa-cog"></i>';
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = dol_buildpath('/saturne/admin/about.php?module_name=DigiriskDolibarr', 1);
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fab fa-readme pictofixedwidth"></i>' . $langs->trans('About') : '<i class="fab fa-readme"></i>';
    $head[$h][2] = 'about';
    $h++;

    complete_head_from_modules($conf, $langs, null, $head, $h, 'digiriskdolibarr');

    complete_head_from_modules($conf, $langs, null, $head, $h, 'digiriskdolibarr@digiriskdolibarr', 'remove');

    return $head;
}
