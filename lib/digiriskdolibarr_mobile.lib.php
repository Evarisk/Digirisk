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

// Load Dolibarr libraries — the risk photo helpers work on the object document directory
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

// Load Saturne libraries — photos are captured through the Saturne media block
require_once __DIR__ . '/../../saturne/lib/medias.lib.php';

/**
 * Directory, relative to the module output directory, holding the photos of the mobile
 * interfaces while they are not attached to an object yet.
 */
if (!defined('DIGIRISK_MOBILE_UPLOAD_DIR')) {
    define('DIGIRISK_MOBILE_UPLOAD_DIR', 'mobile_upload');
}

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
 * Directory holding the photos taken for one risk of a mobile-created object.
 *
 * Keyed by danger category rather than by line ref: the edit path replaces the lines (new refs),
 * while the category identifies the risk for the whole life of the object.
 *
 * @param  string $elementType Object element (preventionplan, firepermit...)
 * @param  string $objectRef   Object reference
 * @param  int    $category    Danger category position
 * @return string              Absolute directory path, without trailing slash
 */
function digiriskMobileRiskPhotoDir(string $elementType, string $objectRef, int $category): string
{
    global $conf;

    // dir_output, like the Saturne media helpers reading those photos back
    return $conf->digiriskdolibarr->dir_output . '/' . $elementType . '/' . dol_sanitizeFileName($objectRef) . '/risks/' . $category;
}

/**
 * Sub directory, relative to the module output directory, where the Saturne media block stores
 * the photos of one risk block while the form is being filled in.
 *
 * Uploads happen before the object exists (and before the danger category is even known for a
 * brand new block), so they land in a per-session token directory keyed by the block index.
 *
 * @param  string     $uploadToken Upload token of the form (see saturne_get_upload_token)
 * @param  int|string $blockIndex  Index of the risk block in the form
 * @return string                  Sub directory, without leading nor trailing slash
 */
function digiriskMobileRiskUploadSubDir(string $uploadToken, $blockIndex): string
{
    return DIGIRISK_MOBILE_UPLOAD_DIR . '/' . $uploadToken . '/' . $blockIndex;
}

/**
 * List the photos already stored for one risk.
 *
 * @param  string $elementType Object element (preventionplan, firepermit...)
 * @param  string $objectRef   Object reference
 * @param  int    $category    Danger category position
 * @return array               List of ['name' => file name, 'url' => displayable URL]
 */
function digiriskMobileGetRiskPhotos(string $elementType, string $objectRef, int $category): array
{
    $photoSubDir = $elementType . '/' . dol_sanitizeFileName($objectRef) . '/risks/' . $category;

    return saturne_get_media_files('digiriskdolibarr', $photoSubDir, '', '', ['type' => 'image', 'sort_order' => 'asc']);
}

/**
 * Move the photos uploaded for one risk block from the temporary upload directory to the
 * final one, keyed by danger category. The final directory is emptied first, so removing a
 * photo in the form removes it for good.
 *
 * @param  string     $elementType Object element (preventionplan, firepermit...)
 * @param  string     $objectRef   Object reference
 * @param  int        $category    Danger category position
 * @param  string     $uploadToken Upload token of the form
 * @param  int|string $blockIndex  Index of the risk block in the form
 * @return int                     Number of photos moved
 */
function digiriskMobileMoveRiskPhotos(string $elementType, string $objectRef, int $category, string $uploadToken, $blockIndex): int
{
    $uploadedPhotos = saturne_get_media_files('digiriskdolibarr', digiriskMobileRiskUploadSubDir($uploadToken, $blockIndex), '', '', ['type' => 'image']);
    $photoDir       = digiriskMobileRiskPhotoDir($elementType, $objectRef, $category);

    if (dol_is_dir($photoDir)) {
        dol_delete_dir_recursive($photoDir);
    }
    if (empty($uploadedPhotos)) {
        return 0;
    }
    dol_mkdir($photoDir);

    $moved = 0;
    foreach ($uploadedPhotos as $uploadedPhoto) {
        if (dol_move($uploadedPhoto['fullname'], $photoDir . '/' . $uploadedPhoto['name'], '0', 1, 0, 0)) {
            $moved++;
        }
    }

    return $moved;
}

/**
 * Copy the photos already attached to a risk into the temporary upload directory, so the media
 * block of the edit form shows them and can remove them like the freshly taken ones.
 * Does nothing once the temporary directory exists: the user changes must not be undone by a reload.
 *
 * @param  string     $elementType Object element (preventionplan, firepermit...)
 * @param  string     $objectRef   Object reference
 * @param  int        $category    Danger category position
 * @param  string     $uploadToken Upload token of the form
 * @param  int|string $blockIndex  Index of the risk block in the form
 * @return void
 */
function digiriskMobileSeedRiskPhotos(string $elementType, string $objectRef, int $category, string $uploadToken, $blockIndex)
{
    global $conf;

    $uploadDir = $conf->digiriskdolibarr->dir_output . '/' . digiriskMobileRiskUploadSubDir($uploadToken, $blockIndex);
    if (dol_is_dir($uploadDir)) {
        return;
    }

    $photoDir = digiriskMobileRiskPhotoDir($elementType, $objectRef, $category);
    if (!dol_is_dir($photoDir)) {
        return;
    }

    dol_mkdir($uploadDir);
    foreach (dol_dir_list($photoDir, 'files', 0, '', '(\.meta|_preview.*\.png)$') as $photoFile) {
        dol_copy($photoFile['fullname'], $uploadDir . '/' . $photoFile['name'], '0', 1);
    }
}

/**
 * Delete the photo directories of the risks that are no longer part of the object.
 *
 * @param  string $elementType     Object element (preventionplan, firepermit...)
 * @param  string $objectRef       Object reference
 * @param  array  $keptCategories  Danger category positions still submitted
 * @return void
 */
function digiriskMobileCleanRiskPhotoDirs(string $elementType, string $objectRef, array $keptCategories)
{
    global $conf;

    $risksDir = $conf->digiriskdolibarr->dir_output . '/' . $elementType . '/' . dol_sanitizeFileName($objectRef) . '/risks';
    if (!is_dir($risksDir)) {
        return;
    }

    foreach (dol_dir_list($risksDir, 'directories', 0) as $categoryDir) {
        if (!in_array((int) $categoryDir['name'], $keptCategories, true)) {
            dol_delete_dir_recursive($categoryDir['fullname']);
        }
    }
}

/**
 * Available required-certification options for the mobile quick-creation interfaces (code => label).
 * Read from the "Certifications et habilitations" dictionary, so new entries only need the
 * dictionary editor of Dolibarr.
 *
 * @param  bool  $activeOnly True to keep only the entries currently enabled in the dictionary.
 *                           Pass false to label what an object already stores: a certification
 *                           disabled afterwards must still be displayed and kept on save.
 * @return array             Certification code => label
 */
function digiriskGetCertificationOptions(bool $activeOnly = true): array
{
    global $conf, $db;

    $sql  = 'SELECT ref, label FROM ' . MAIN_DB_PREFIX . 'c_digiriskdolibarr_certification';
    $sql .= ' WHERE entity IN (0, ' . ((int) $conf->entity) . ')';
    $sql .= $activeOnly ? ' AND active = 1' : '';
    $sql .= ' ORDER BY position ASC, label ASC';

    $resql = $db->query($sql);
    if (!$resql) {
        // Dictionary table not created yet (module not reactivated since the update): fall back on
        // the values it is seeded with, so the pickers keep working and nothing is lost on save
        return digiriskGetDefaultCertificationOptions();
    }

    $options = [];
    while ($obj = $db->fetch_object($resql)) {
        $options[$obj->ref] = $obj->label;
    }
    $db->free($resql);

    return $options;
}

/**
 * Certifications the dictionary is seeded with. Only used while the dictionary table does not
 * exist yet, the dictionary is the reference once the module has been reactivated.
 *
 * @return array Certification code => label
 */
function digiriskGetDefaultCertificationOptions(): array
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
