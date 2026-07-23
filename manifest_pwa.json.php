<?php
/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
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
 * \file    manifest_pwa.json.php
 * \ingroup digiriskdolibarr
 * \brief   Manifest of the internal DigiriskDolibarr PWA (prevention plans and fire permits).
 *          Kept separate from manifest.json.php, which serves the public ticket declaration app:
 *          two different start_url and scope means two installable applications.
 */

if (!defined('NOREQUIREUSER')) {
    define('NOREQUIREUSER', 1);
}
if (!defined('NOREQUIRESOC')) {
    define('NOREQUIRESOC', 1);
}
if (!defined('NOREQUIRETRAN')) {
    define('NOREQUIRETRAN', 1);
}
if (!defined('NOTOKENRENEWAL')) {
    define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
    define('NOLOGIN', 1);
}
if (!defined('NOREQUIREMENU')) {
    define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
    define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
    define('NOREQUIREAJAX', 1);
}
if (!defined('NOSESSION')) {
    define('NOSESSION', 1);
}
if (!defined('NOCSRFCHECK')) { // We accept to go on this page from external website
    define('NOCSRFCHECK', 1);
}
if (!defined('NOIPCHECK')) {   // Do not check IP defined into conf $dolibarr_main_restrict_ip
    define('NOIPCHECK', 1);
}
if (!defined('NOBROWSERNOTIF')) {
    define('NOBROWSERNOTIF', 1);
}

require_once __DIR__ . '/../../main.inc.php';

top_httphead('text/json');

// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access
if (empty($dolibarr_nocache)) {
    header('Cache-Control: max-age=10800, public, must-revalidate');
    // For a text/json, we must set an Expires to avoid to have it forced to an expired value by the web server
    header('Expires: ' . gmdate('D, d M Y H:i:s', dol_now('gmt') + 10800) . ' GMT');
} else {
    header('Cache-Control: no-cache');
}

// Global variables definitions
global $conf;

// Initialize technical objects
$manifest = new stdClass();

$manifest->short_name = 'Digirisk';
$manifest->name       = getDolGlobalString('MAIN_INFO_SOCIETE_NOM') . ' - Digirisk';
$manifest->icons      = [];

$img               = new stdClass();
$img->src          = dol_buildpath('/custom/digiriskdolibarr/img/digiriskdolibarr_color_192.png', 1);
$img->type         = 'image/png';
$img->sizes        = '192x192';
$manifest->icons[] = $img;

$img               = new stdClass();
$img->src          = dol_buildpath('/custom/digiriskdolibarr/img/digiriskdolibarr_color_512.png', 1);
$img->type         = 'image/png';
$img->sizes        = '512x512';
$manifest->icons[] = $img;

$manifest->id               = dol_buildpath('/custom/digiriskdolibarr/view/frontend/pwa_home.php', 1);
$manifest->start_url        = dol_buildpath('/custom/digiriskdolibarr/view/frontend/pwa_home.php', 1);
$manifest->background_color = '#ffffff';
$manifest->display          = 'standalone';
$manifest->display_override = ['window-controls-overlay'];
// The mobile creation screens live outside view/frontend/, so the scope covers the whole module
$manifest->scope            = dol_buildpath('/custom/digiriskdolibarr/', 1);
$manifest->theme_color      = '#2b7de9';
$manifest->description      = 'Consultez et creez vos plans de prevention et vos permis de feu depuis votre telephone';

// Shortcuts offered by a long press on the installed application icon
$shortcut              = new stdClass();
$shortcut->name        = 'Nouveau plan de prevention';
$shortcut->short_name  = 'Plan';
$shortcut->url         = dol_buildpath('/custom/digiriskdolibarr/view/preventionplan/preventionplan_mobile_create.php', 1);
$manifest->shortcuts[] = $shortcut;

$shortcut              = new stdClass();
$shortcut->name        = 'Nouveau permis de feu';
$shortcut->short_name  = 'Permis';
$shortcut->url         = dol_buildpath('/custom/digiriskdolibarr/view/firepermit/firepermit_mobile_create.php', 1);
$manifest->shortcuts[] = $shortcut;

$img                     = new stdClass();
$img->src                = dol_buildpath('/custom/digiriskdolibarr/img/digiriskdolibarr_color_512.png', 1);
$img->type               = 'image/png';
$img->sizes              = '512x512';
$img->form_factor        = 'narrow';
$manifest->screenshots[] = $img;

$img                     = new stdClass();
$img->src                = dol_buildpath('/custom/digiriskdolibarr/img/digiriskdolibarr_color_512.png', 1);
$img->type               = 'image/png';
$img->sizes              = '512x512';
$img->form_factor        = 'wide';
$manifest->screenshots[] = $img;

print json_encode($manifest);

