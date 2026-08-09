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
 * Return the tuto image illustrating a configuration setting
 *
 * Images live in img/config_tuto/<page>/<slug>.png and are optional: a missing file
 * renders an empty cell instead of a broken image.
 *
 * @param  string $page Configuration page the image belongs to (sub directory name)
 * @param  string $slug Image name, without its .png extension
 * @param  string $alt  Alternative text, usually the setting label
 * @return string       HTML img tag, empty string when the file does not exist
 */
function digiriskdolibarr_tuto_image(string $page, string $slug, string $alt = ''): string
{
    $file = '/digiriskdolibarr/img/config_tuto/' . $page . '/' . $slug . '.png';

    if (!dol_is_file(dol_buildpath($file, 0))) {
        return '';
    }

    return '<img class="config-tuto" loading="lazy" src="' . dol_buildpath($file, 1) . '" alt="' . dol_escape_htmltag($alt) . '">';
}

/**
 * Print the styles and scripts displaying a tuto image full size when clicked
 *
 * Safe to call several times: only the first call prints something.
 *
 * @return void
 */
function digiriskdolibarr_tuto_overlay()
{
    static $printed = false;

    if ($printed) {
        return;
    }
    $printed = true;

    print '<style>
.config-tuto { width: 150px; max-width: 100%; border: 1px solid #ddd; border-radius: 4px; cursor: zoom-in; vertical-align: middle; }
.config-tuto-overlay { display: none; position: fixed; inset: 0; z-index: 1200; background: rgba(0, 0, 0, .7); cursor: zoom-out; }
.config-tuto-overlay.opened { display: flex; align-items: center; justify-content: center; }
.config-tuto-overlay img { max-width: 90vw; max-height: 90vh; border-radius: 4px; box-shadow: 0 4px 24px rgba(0, 0, 0, .5); }
</style>';

    print '<div class="config-tuto-overlay"><img src="" alt=""></div>';

    print '<script>
jQuery(document).ready(function() {
    var overlay = jQuery(".config-tuto-overlay");

    jQuery(".config-tuto").on("click", function() {
        overlay.find("img").attr("src", jQuery(this).attr("src")).attr("alt", jQuery(this).attr("alt"));
        overlay.addClass("opened");
    });

    overlay.on("click", function() {
        overlay.removeClass("opened");
    });

    jQuery(document).on("keydown", function(event) {
        if (event.key === "Escape") {
            overlay.removeClass("opened");
        }
    });
});
</script>';
}

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

    $head[$h][0] = dol_buildpath('/digiriskdolibarr/admin/config/actionplan.php', 1);
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-columns pictofixedwidth"></i>' . $langs->trans('ActionPlan') : '<i class="fas fa-columns"></i>';
    $head[$h][2] = 'actionplan';
    $h++;

    $head[$h][0] = dol_buildpath('/digiriskdolibarr/admin/config/ticket_kanban.php', 1);
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-th-large pictofixedwidth"></i>' . $langs->trans('TicketKanban') : '<i class="fas fa-th-large"></i>';
    $head[$h][2] = 'ticket_kanban';
    $h++;

    $head[$h][0] = dol_buildpath('/digiriskdolibarr/admin/config/firepermit.php', 1);
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-fire-alt pictofixedwidth"></i>' . $langs->trans('FirePermit') : '<i class="fas fa-fire-alt"></i>';
    $head[$h][2] = 'firepermit';
    $h++;

    $head[$h][0] = dol_buildpath('/digiriskdolibarr/admin/config/accident.php', 1);
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-user-injured pictofixedwidth"></i>' . $langs->trans('Accident') : '<i class="fas fa-user-injured"></i>';
    $head[$h][2] = 'accident';
    $h++;

    $head[$h][0] = dol_buildpath('/digiriskdolibarr/admin/config/meteovigilance.php', 1);
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-cloud-sun-rain pictofixedwidth"></i>' . $langs->trans('MeteoVigilance') : '<i class="fas fa-cloud-sun-rain"></i>';
    $head[$h][2] = 'meteovigilance';
    $h++;

    $head[$h][0] = dol_buildpath('/digiriskdolibarr/admin/ticket/ticket.php', 1);
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fa fa-ticket-alt pictofixedwidth"></i>' . $langs->trans('WHSRegister') : '<i class="fas fa-ticket-alt"></i>';
    $head[$h][2] = 'ticket';
    $h++;

    $head[$h][0] = dol_buildpath('/digiriskdolibarr/admin/config/digiai.php', 1);
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fa fa-magic pictofixedwidth"></i>' . $langs->trans('DigiAI') : '<i class="fas fa-magic"></i>';
    $head[$h][2] = 'digiai';
    $h++;

    $head[$h][0] = dol_buildpath('/digiriskdolibarr/admin/config/product.php', 1);
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-cube pictofixedwidth"></i>' . $langs->trans('Product') : '<i class="fas fa-cube"></i>';
    $head[$h][2] = 'product';
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
