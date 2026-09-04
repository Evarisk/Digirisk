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
 * @param  array  $risks          Types of work, each ['category', 'description', 'used_equipment']
 * @param  object $refLineModule  Numbering module used to compute each line ref
 * @return void
 */
function digiriskMobileCreateFirePermitLines(DoliDB $db, User $user, int $firePermitId, int $fkElement, array $risks, $refLineModule)
{
    global $conf;

    if (empty($risks)) {
        return;
    }

    require_once __DIR__ . '/../class/firepermit.class.php';

    foreach ($risks as $riskEntry) {
        $line                 = new FirePermitLine($db);
        $line->ref            = $refLineModule->getNextValue($line);
        $line->entity         = $conf->entity;
        $line->date_creation  = $db->idate(dol_now());
        $line->status         = FirePermitLine::STATUS_VALIDATED;
        $line->category       = (int) $riskEntry['category'];
        $line->description    = $riskEntry['description'];
        $line->used_equipment = $riskEntry['used_equipment'];
        $line->fk_firepermit  = $firePermitId;
        $line->fk_element     = $fkElement;
        $line->create($user, true);
    }
}

/**
 * Save the weekly schedules of an object created through a mobile interface.
 *
 * The Saturne schedules keep one line per day ("morning afternoon"), and an object only ever has a
 * single active record: the existing one is updated rather than stacking a second.
 *
 * @param  DoliDB $db          Database handler
 * @param  User   $user        User performing the action
 * @param  string $elementType Object element (preventionplan, firepermit...)
 * @param  int    $elementId   Object id
 * @param  array  $schedules   Day name => schedule line
 * @return int                 < 0 if KO, > 0 if OK
 */
function digiriskMobileSaveSchedules(DoliDB $db, User $user, string $elementType, int $elementId, array $schedules): int
{
    require_once __DIR__ . '/../../saturne/class/saturneschedules.class.php';

    $saturneSchedules = new SaturneSchedules($db);
    $saturneSchedules->fetch(0, '', ' AND element_type = "' . $db->escape($elementType) . '" AND element_id = ' . $elementId . ' AND status = 1');

    $saturneSchedules->element_type = $elementType;
    $saturneSchedules->element_id   = $elementId;
    $saturneSchedules->status       = 1;
    foreach ($schedules as $day => $value) {
        $saturneSchedules->$day = $value;
    }

    return ($saturneSchedules->id > 0) ? $saturneSchedules->update($user) : $saturneSchedules->create($user);
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
 * Find the third party an exterior company block describes, before creating a new one.
 *
 * The mobile forms let the user either pick a company in the list or type its details. Typing them
 * used to create a new third party every single time, so a company already on file but whose SIREN
 * is missing or written differently came back as a duplicate. Match on the identifier first, then
 * fall back on the company name, which is what the user actually recognises.
 *
 * @param  DoliDB $db      Database handler
 * @param  string $idProf  SIREN or SIRET as typed (may hold spaces or dots)
 * @param  string $name    Company name as typed
 * @return int             Id of the third party found, 0 when there is none
 */
function digiriskMobileFindThirdparty(DoliDB $db, string $idProf = '', string $name = ''): int
{
    require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

    $idProfClean = digiriskMobileCleanIdProf($idProf);

    if (digiriskMobileIsValidIdProf($idProfClean)) {
        // A SIRET starts with the SIREN of its company, so comparing the first 9 digits of both
        // columns matches whichever of the two was typed and whichever the company has on file.
        // Done in SQL rather than through fetch() because stored values may carry spaces or dots.
        $sirenPart = substr($idProfClean, 0, 9);

        $sql  = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . 'societe';
        $sql .= ' WHERE entity IN (' . getEntity('societe') . ')';
        $sql .= "   AND (REPLACE(REPLACE(REPLACE(siren, ' ', ''), '.', ''), '-', '') = '" . $db->escape($sirenPart) . "'";
        $sql .= "    OR LEFT(REPLACE(REPLACE(REPLACE(siret, ' ', ''), '.', ''), '-', ''), 9) = '" . $db->escape($sirenPart) . "')";
        $sql .= ' ORDER BY rowid ASC';

        $resql = $db->query($sql);
        if ($resql && $db->num_rows($resql)) {
            $obj = $db->fetch_object($resql);
            return (int) $obj->rowid;
        }
    }

    $name = trim($name);
    if (dol_strlen($name)) {
        $thirdparty = new Societe($db);
        if ($thirdparty->fetch(0, $name) > 0) {
            return (int) $thirdparty->id;
        }
    }

    return 0;
}

/**
 * Signature state of a prevention plan, as a single value two pages can compare.
 *
 * The mobile success screen is often left open on a desk while the signature happens elsewhere (the
 * phone that scanned the QR code, the link opened from the mail). Rendering this value with the page
 * and polling for it lets the screen notice the signature instead of waiting for a manual reload.
 *
 * @param  DoliDB $db     Database handler
 * @param  object $object Prevention plan
 * @return array          ['signed_count' => int, 'status' => int, 'state' => string]
 */
function digiriskMobileGetSignatureState(DoliDB $db, $object): array
{
    require_once __DIR__ . '/../../saturne/class/saturnesignature.class.php';

    $signatory   = new SaturneSignature($db, 'digiriskdolibarr', $object->element);
    $signatories = $signatory->fetchSignatory('', (int) $object->id, $object->element);

    $parts       = [];
    $signedCount = 0;

    if (is_array($signatories)) {
        foreach ($signatories as $roleSignatories) {
            foreach ((array) $roleSignatories as $roleSignatory) {
                $isSigned = !empty($roleSignatory->signature);
                if ($isSigned) {
                    $signedCount++;
                }
                // The email date belongs to the state as well: a request resent from another tab
                // must refresh the screen too, otherwise it keeps claiming the mail never left.
                $parts[] = (int) $roleSignatory->id . ':' . ($isSigned ? 1 : 0)
                    . ':' . (int) $roleSignatory->signature_date
                    . ':' . (int) $roleSignatory->last_email_sent_date;
            }
        }
    }

    sort($parts);

    return [
        'signed_count' => $signedCount,
        'status'       => (int) $object->status,
        'state'        => md5(implode('|', $parts) . '#' . (int) $object->status),
    ];
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
 * Icons of the mobile progress strip, keyed by step.
 *
 * Inline SVG rather than an icon font: the strip colours each icon with the state of its step, and
 * the same drawings are shown by the creation form and by the success screen of both interfaces.
 *
 * @return array Step key => ['viewBox' => string, 'svg' => string]
 */
function digiriskMobileWorkflowIcons(): array
{
    return [
        'created' => [
            'viewBox' => '0 0 100 100',
            'svg'     => '<path d="M30 15 H20 C14.5 15 10 19.5 10 25 V85 C10 90.5 14.5 95 20 95 H80 C85.5 95 90 90.5 90 85 V25 C90 19.5 85.5 15 80 15 H70" fill="none" stroke="currentColor" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/><rect x="35" y="5" width="30" height="15" rx="5" fill="none" stroke="currentColor" stroke-width="8"/><circle cx="50" cy="15" r="3" fill="currentColor"/><line x1="25" y1="40" x2="75" y2="40" stroke="currentColor" stroke-width="6" stroke-linecap="round"/><line x1="25" y1="55" x2="75" y2="55" stroke="currentColor" stroke-width="6" stroke-linecap="round"/><line x1="25" y1="70" x2="50" y2="70" stroke="currentColor" stroke-width="6" stroke-linecap="round"/><circle cx="75" cy="75" r="25" fill="#ffffff"/><circle cx="75" cy="75" r="20" fill="currentColor"/><path d="M65 75 L72 82 L85 65" fill="none" stroke="#ffffff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>',
        ],
        'user' => [
            'viewBox' => '0 0 448 512',
            'svg'     => '<path d="M224 256c70.7 0 128-57.3 128-128S294.7 0 224 0 96 57.3 96 128s57.3 128 128 128zm95.8 32.6L272 480l-32-136 32 56h-96l32-56-32 136-47.8-191.4C56.9 292 0 350.3 0 422.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-41.6c0-72.1-56.9-130.4-128.2-133.8z"/>',
        ],
        'company' => [
            'viewBox' => '0 0 512 512',
            'svg'     => '<path d="M480 288c0-80.25-49.28-148.92-119.19-177.62L320 192V80a16 16 0 0 0-16-16h-96a16 16 0 0 0-16 16v112l-40.81-81.62C81.28 139.08 32 207.75 32 288v64h448zm16 96H16a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16h480a16 16 0 0 0 16-16v-32a16 16 0 0 0-16-16z"/>',
        ],
        'lock' => [
            'viewBox' => '0 0 24 24',
            'svg'     => '<path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/>',
        ],
        'archive' => [
            'viewBox' => '0 0 24 24',
            'svg'     => '<path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>',
        ],
    ];
}

/**
 * Progress strip of the mobile interfaces: where the object stands, step by step.
 *
 * Rendered as a string rather than a template because the success screen composes it with the other
 * blocks it hands to the shared success template. The creation form shows it compact, the success
 * screen airier, but both draw the very same strip so one interface never drifts from the other.
 *
 * @param  array  $steps   Steps, each ['title', 'status', 'date', 'done', 'current', 'viewBox', 'svg']
 * @param  string $refHtml Ready to print reference of the object, shown on the right of the title
 * @param  bool   $compact True on the creation form, where the strip sits inside a card
 * @return string          HTML of the strip
 */
function digiriskMobileRenderWorkflow(array $steps, string $refHtml = '', bool $compact = false): string
{
    global $langs;

    $out  = '<div style="margin-bottom: ' . ($compact ? '10' : '20') . 'px; border-bottom: 1px dashed #eaeaea; padding-bottom: ' . ($compact ? '10' : '15') . 'px;">';
    $out .= '<div class="digirisk-mobile-extsign__title" style="margin-bottom: ' . ($compact ? '10' : '25') . 'px; padding: 0 5px; display: flex; justify-content: space-between; align-items: center;">';
    $out .= '<div style="color: #4a55d1; font-weight: bold; font-size: 1.1em; text-transform: uppercase;"><i class="fas fa-chart-line" style="margin-right: 5px;"></i> ' . $langs->trans('MobileProgress') . '</div>';
    if (dol_strlen($refHtml)) {
        $out .= '<div style="font-size: 0.9em;">' . $refHtml . '</div>';
    }
    $out .= '</div>';
    $out .= '<div style="display: flex; justify-content: space-between; overflow-x: auto; padding-bottom: ' . ($compact ? '0' : '10') . 'px; margin: 0 5px;">';

    foreach ($steps as $index => $step) {
        // A step being played counts as reached: only what is still to do is shown in red
        $isReached   = !empty($step['done']) || !empty($step['current']);
        $colorCircle = $isReached ? '#347244' : '#c94236';
        $bgColorBadg = $isReached ? '#e6f2e9' : '#fbeae9';
        $textColor   = $isReached ? '#2d6a3c' : '#c33a2f';

        $isLast = ($index === count($steps) - 1);

        $out .= '<div style="display: flex; flex-direction: column; align-items: center; min-width: 90px; text-align: center; position: relative; flex: 1; padding: 0 2px;">';

        $out .= '<div style="font-size: 0.7em; font-weight: bold; color: #333; margin-bottom: 10px; height: 28px; line-height: 1.2; display: flex; align-items: flex-end; justify-content: center;">';
        $out .= '<span>' . $step['title'] . '</span>';
        $out .= '</div>';

        // Dashed connector, aligned with the middle of the circles
        if (!$isLast) {
            $out .= '<div style="position: absolute; top: 58px; left: 50%; width: 100%; height: 0px; border-top: 2px dashed #999; z-index: 1;"></div>';
        }

        $out .= '<div style="width: 40px; height: 40px; border-radius: 50%; border: 2px solid ' . $colorCircle . '; display: flex; align-items: center; justify-content: center; background: #fff; z-index: 2; margin-bottom: 10px;">';
        // Stroked drawings take their colour from the text, filled ones from the fill attribute
        $fillAttr = (strpos($step['svg'], 'stroke=') !== false) ? 'fill="none" style="color: ' . $colorCircle . ';"' : 'fill="' . $colorCircle . '"';
        $out     .= '<svg viewBox="' . $step['viewBox'] . '" ' . $fillAttr . ' width="24px" height="24px">' . $step['svg'] . '</svg>';
        $out     .= '</div>';

        $statusText = $step['status'];
        if (!empty($step['date'])) {
            $statusText .= '<br>' . $step['date'];
        }

        $out .= '<div style="background: ' . $bgColorBadg . '; color: ' . $textColor . '; padding: 4px 6px; border-radius: 15px; font-size: 0.65em; font-weight: bold; display: inline-block; line-height: 1.2; text-align: center;">';
        $out .= $statusText;
        $out .= '</div>';

        $out .= '</div>';
    }

    $out .= '</div>';
    $out .= '</div>';

    return $out;
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
