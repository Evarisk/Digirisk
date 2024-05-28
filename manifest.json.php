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
 * \file    manifest.json.php
 * \ingroup digiriskdolibarr
 * \brief   File for The Web App
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

// Initialize technical objects
$manifest = new stdClass();

$manifest->short_name = 'DigiriskDolibarr';
$manifest->name       = 'DigiriskDolibarr';
$manifest->icons      = [];

$img               = new stdClass();
$img->src          = dol_buildpath('/custom/digiriskdolibarr/img/digiriskdolibarr_color.svg', 1);
$img->type         = 'image/svg+xml';
$img->sizes        = '150x150';
$manifest->icons[] = $img;

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

$manifest->id               = dol_buildpath('/custom/digiriskdolibarr/public/create_ticket.php', 1);
$manifest->start_url        = dol_buildpath('/custom/digiriskdolibarr/public/create_ticket.php', 1);
$manifest->background_color = '#ffffff';
$manifest->display          = 'standalone';
$manifest->display_override = ['window-controls-overlay'];
$manifest->scope            = dol_buildpath('/custom/digiriskdolibarr/', 1);
$manifest->theme_color      = '#ffffff';
$manifest->description      = 'Gérez les risques de votre entreprise et créez votre Document Unique en toute simplicité';

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
