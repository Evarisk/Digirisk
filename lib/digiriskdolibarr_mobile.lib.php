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
 * \file    lib/digiriskdolibarr_mobile.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Helper functions for the mobile quick-creation interface.
 *          The reusable user signature is the native "ElectronicSignature": a SaturneSignature
 *          record with object_type = "user" and role = "UserSignature" (the one shown on the user card),
 *          shared across the ecosystem — not a Digirisk-specific storage.
 */

/**
 * Get the reusable electronic signature (base64 data URL) of a user.
 * Reads the "UserSignature" SaturneSignature record (the ElectronicSignature shown on the user card).
 *
 * @param  DoliDB $db     Database handler
 * @param  int    $userId User id
 * @return string         Base64 signature data URL, or empty string if none
 */
function digiriskGetUserElectronicSignature(DoliDB $db, int $userId): string
{
    if ($userId <= 0) {
        return '';
    }

    require_once __DIR__ . '/../../saturne/class/saturnesignature.class.php';

    $signatory = new SaturneSignature($db);
    $result    = $signatory->fetch(0, '', ' AND fk_object = ' . ((int) $userId) . ' AND status > 0 AND object_type = "user" AND role = "UserSignature"');

    return ($result > 0) ? (string) $signatory->signature : '';
}

/**
 * Save (create or update) the reusable electronic signature of a user.
 * Uses the standard "UserSignature" SaturneSignature record so it matches the user card widget.
 *
 * @param  DoliDB $db        Database handler
 * @param  User   $user      User performing the action
 * @param  int    $userId    Target user id (the signature owner)
 * @param  string $signature Base64 signature data URL
 * @return int               < 0 if KO, > 0 if OK
 */
function digiriskSaveUserElectronicSignature(DoliDB $db, User $user, int $userId, string $signature): int
{
    if ($userId <= 0) {
        return -1;
    }

    require_once __DIR__ . '/../../saturne/class/saturnesignature.class.php';

    $signatory = new SaturneSignature($db);
    $result    = $signatory->fetch(0, '', ' AND fk_object = ' . ((int) $userId) . ' AND status > 0 AND object_type = "user" AND role = "UserSignature"');
    if ($result <= 0) {
        $signatory->setSignatory($userId, 'user', 'user', [$userId], 'UserSignature');
    }

    $signatory->signature      = $signature;
    $signatory->signature_date = dol_now();

    if ($signatory->update($user, true) > 0) {
        $signatory->setSigned($user, true);
        return 1;
    }

    return -1;
}

/**
 * Validate that a string is a base64 PNG/JPEG image data URL (the format produced by the signature canvas).
 *
 * @param  string $signature Candidate signature string
 * @return bool              True if it looks like a valid image data URL
 */
function digiriskIsValidSignature(string $signature): bool
{
    return (bool) preg_match('#^data:image/(png|jpeg|jpg);base64,[A-Za-z0-9+/=\s]+$#', $signature);
}

/**
 * Convert the value of a native date / datetime-local input into a timestamp.
 * Accepts "YYYY-MM-DD" (prevention plan) and "YYYY-MM-DDTHH:MM" (fire permit).
 *
 * @param  string $value Raw input value
 * @return int           Timestamp, or 0 when the value is empty or malformed
 */
function digiriskMobileParseDateTime(string $value): int
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2}))?$/', $value, $parts)) {
        return 0;
    }

    return (int) dol_mktime(
        isset($parts[4]) ? (int) $parts[4] : 0,
        isset($parts[5]) ? (int) $parts[5] : 0,
        0,
        (int) $parts[2],
        (int) $parts[3],
        (int) $parts[1]
    );
}

/**
 * Create the fire permit lines (types of work) posted by the mobile interface.
 * The mobile interface uses a single location for the whole permit, applied to every line.
 *
 * @param  DoliDB $db             Database handler
 * @param  User   $user           User performing the action
 * @param  int    $firePermitId   Parent fire permit id
 * @param  int    $fkElement      GP/UT the work takes place in
 * @param  array  $categories     Posted danger category positions
 * @param  array  $comments       Posted descriptions, keyed like $categories
 * @param  array  $equipments     Posted used equipments, keyed like $categories
 * @param  object $refLineModule  Numbering module used to compute each line ref
 * @return void
 */
function digiriskMobileCreateFirePermitLines(DoliDB $db, User $user, int $firePermitId, int $fkElement, $categories, $comments, $equipments, $refLineModule)
{
    global $conf;

    if (!is_array($categories) || empty($categories)) {
        return;
    }

    require_once __DIR__ . '/../class/firepermit.class.php';

    foreach ($categories as $index => $category) {
        if ($category === '' || !is_numeric($category)) {
            continue;
        }
        $line                 = new FirePermitLine($db);
        $line->ref            = $refLineModule->getNextValue($line);
        $line->entity         = $conf->entity;
        $line->date_creation  = $db->idate(dol_now());
        $line->status         = FirePermitLine::STATUS_VALIDATED;
        $line->category       = (int) $category;
        $line->description    = isset($comments[$index])   ? $comments[$index]   : '';
        $line->used_equipment = isset($equipments[$index]) ? $equipments[$index] : '';
        $line->fk_firepermit  = $firePermitId;
        $line->fk_element     = $fkElement;
        $line->create($user, true);
    }
}

/**
 * Keep only the digits of a company identifier typed by the user.
 * SIREN and SIRET are commonly written with spaces or dots ("552 100 554 00012").
 *
 * @param  string $value Raw input value
 * @return string        Digits only
 */
function digiriskMobileCleanIdProf(string $value): string
{
    return preg_replace('/[^0-9]/', '', $value);
}

/**
 * A company identifier is either a 9 digit SIREN or a 14 digit SIRET (SIREN + NIC).
 *
 * @param  string $value Cleaned identifier (digits only)
 * @return bool          True when the length matches one of the two formats
 */
function digiriskMobileIsValidIdProf(string $value): bool
{
    return in_array(dol_strlen($value), [9, 14], true);
}

/**
 * Available required-certification options for the mobile quick-creation interfaces (code => label).
 *
 * @return array
 */
function digiriskGetCertificationOptions(): array
{
    return [
        'CACES_R482'        => 'CACES R482 - Engins de chantier',
        'CACES_R483'        => 'CACES R483 - Grues mobiles',
        'CACES_R484'        => 'CACES R484 - Ponts roulants',
        'CACES_R485'        => 'CACES R485 - Gerbeurs',
        'CACES_R486'        => 'CACES R486 - Nacelles (PEMP)',
        'CACES_R489'        => 'CACES R489 - Chariots élévateurs',
        'CACES_R490'        => 'CACES R490 - Grues de chargement',
        'PERMIS_B'          => 'Permis B',
        'PERMIS_C'          => 'Permis C',
        'PERMIS_CE'         => 'Permis CE',
        'PERMIS_D'          => 'Permis D',
        'HABILITATION_ELEC' => 'Habilitation électrique',
        'AIPR'              => 'AIPR',
        'SST'               => 'Sauveteur secouriste du travail (SST)',
        'TRAVAIL_HAUTEUR'   => 'Travail en hauteur',
        'ECHAFAUDAGE'       => 'Montage/démontage échafaudage',
        'ATEX'              => 'ATEX',
        'AMIANTE'           => 'Amiante (SS3/SS4)',
        'PONTIER'           => 'Pontier élingueur',
    ];
}

/**
 * Build a QR code as inline SVG, ready to be dropped in an HTML page.
 *
 * Inline rather than a generated file: the mobile interfaces are shown right after a creation,
 * on site, and must not depend on an extra request to display the code people scan.
 *
 * @param  string $data  Content to encode
 * @param  string $color Foreground colour of the modules
 * @return string        SVG markup scaling to its container, empty string when nothing to encode
 */
function digiriskGetQrCodeSvg(string $data, string $color = '#0f172a'): string
{
    if (!dol_strlen($data)) {
        return '';
    }

    require_once DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/tcpdf_barcodes_2d.php';

    $barcode = new TCPDF2DBarcode($data, 'QRCODE,M');

    // One user unit per module, so the viewBox added below maps to the module grid
    $svg = $barcode->getBarcodeSVGcode(1, 1, $color);

    // TCPDF returns a standalone document: drop the XML prolog and the DOCTYPE, which are invalid inline
    $svg = preg_replace('/^.*?(?=<svg)/s', '', $svg);

    // Swap the fixed pixel size for a viewBox so CSS drives the rendered size
    return preg_replace(
        '/<svg width="([\d.]+)" height="([\d.]+)"/',
        '<svg viewBox="0 0 $1 $2" width="100%" height="100%" preserveAspectRatio="xMidYMid meet" focusable="false" aria-hidden="true"',
        $svg,
        1
    );
}
