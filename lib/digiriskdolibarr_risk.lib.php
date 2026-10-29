<?php
/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
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
 * \file    lib/digiriskdolibarr_risk.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Library files with common functions for risk
 */

/**
 * Prepare risk pages header
 *
 * A risk carries neither note nor document nor agenda event: the card is its only tab, the other
 * modules of the module adding theirs through the hook saturne_object_prepare_head() calls.
 *
 * @param  Risk  $object Risk
 * @return array $head   Array of tabs
 * @throws Exception
 */
function risk_prepare_head(Risk $object): array
{
    // Load translation files required by the page
    saturne_load_langs();

    $head = [];

    $moreParams = [
        'specialName' => 'RiskCard'
    ];

    return saturne_object_prepare_head($object, $head, $moreParams, false, false, false, false);
}

/**
 * Saturne media block of the photos of a risk assessment, shown in the risk modals
 *
 * A risk or an assessment being created has no reference yet: its photos wait in a work folder its
 * creation moves them out of, see digiriskRiskAssessmentMovePhotos(). An existing assessment keeps
 * them in its own folder.
 *
 * A psychosocial assessment also takes the documents of its report (filled grid, minutes), in the
 * subfolder "documents" of its photos: the gallery and the list of the documents never show the
 * same file twice.
 *
 * @param  string              $subDir         Folder of the photos, relative to the module folder
 * @param  RiskAssessment|null $riskAssessment Assessment owning the folder, null for a work folder
 * @param  bool                $withFiles      Also take documents, not only photos
 * @return string                              HTML of the block
 */
function digiriskRiskAssessmentMediaBlock(string $subDir, ?RiskAssessment $riskAssessment = null, bool $withFiles = false): string
{
    global $conf;

    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
    require_once __DIR__ . '/../../saturne/lib/medias.lib.php';

    // The picking button opens the media library of the module, rendered by the risk pages
    $options = ['show_photo' => true, 'show_audio' => false, 'show_file' => $withFiles, 'file_sub_dir' => $subDir . '/documents', 'show_library' => true];

    // The lists show one photo of the assessment: the editor lets it be picked among the others
    if ($riskAssessment !== null && $riskAssessment->id > 0) {
        $options['object'] = $riskAssessment;
    }

    // Derived from the folder, so the block sent back after an upload is found again by its id
    $prefix = 'risk-media-' . str_replace('/', '-', $subDir);

    $photoDir      = $conf->digiriskdolibarr->dir_output . '/' . $subDir;
    $photoDirExist = dol_is_dir($photoDir);

    $block = saturne_render_media_block('digiriskdolibarr', $subDir, $prefix, '', $options);

    // The block creates its folder as soon as it is displayed, and a risk list displays one per
    // risk and per assessment: one left unused would stay behind empty. An upload creates it again.
    if (!$photoDirExist) {
        dol_delete_dir($photoDir, 1);
    }

    return $block;
}

/**
 * Photo added, taken from the media library or removed from a media block of the risk modals: the
 * Saturne media block posts on the page and expects itself back, refreshed. The folder comes from
 * the browser, so only the work folders of a risk or an assessment being created and the folder of
 * an existing assessment are writable. Documents are only taken by a psychosocial assessment.
 *
 * @param  string $action Requested action: anything but uploadPhoto, linkPhoto, deletePhoto, uploadFile and deleteFile returns at once
 * @return void           Ends the request when it handled the action
 * @throws Exception
 */
function digiriskRiskAssessmentMediaActions(string $action): void
{
    global $conf, $db, $langs, $user;

    if (!in_array($action, ['uploadPhoto', 'linkPhoto', 'deletePhoto', 'uploadFile', 'deleteFile'], true) || GETPOST('module_name', 'alpha') !== 'digiriskdolibarr') {
        return;
    }

    require_once __DIR__ . '/../class/riskanalysis/riskassessment.class.php';

    $subDir         = GETPOST('sub_dir', 'alphanohtml');
    $riskAssessment = null;
    $isAllowed      = false;

    // A document goes to the subfolder "documents" of the folder of the block
    $isDocument = in_array($action, ['uploadFile', 'deleteFile'], true);
    $blockDir   = $isDocument ? preg_replace('/\/documents$/', '', $subDir) : $subDir;

    if (preg_match('/^riskassessment\/tmp\/RA0(\/[A-Za-z0-9_-]+)?$/', $blockDir)) {
        // Work folder of a new risk, or of a new assessment named after its risk
        $isAllowed = true;
    } elseif (preg_match('/^riskassessment\/([A-Za-z0-9_-]+)$/', $blockDir, $matches) && $matches[1] !== 'tmp') {
        $riskAssessment = new RiskAssessment($db);
        $isAllowed      = $riskAssessment->fetch(0, $matches[1]) > 0;
    }

    // The work folder of a psychosocial assessment (digiriskRiskAssessmentPsychosocialWorkDir()), or
    // the folder of one, also takes documents
    $withFiles = preg_match('/^riskassessment\/tmp\/RA0\/RPS\d+$/', $blockDir) || ($riskAssessment !== null && $riskAssessment->method == 'rps');

    if (!$isAllowed || ($isDocument && (!$withFiles || $blockDir === $subDir))) {
        http_response_code(403);
        exit;
    }

    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
    require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

    // Writes the photo with its thumbs, copies it from the library, or removes a photo or a document
    require dol_buildpath('/saturne/core/tpl/actions/media_block_actions.tpl.php');

    // Saturne leaves the documents to the page: any type, without thumbs
    if ($action == 'uploadFile' && getDolGlobalInt('MAIN_UPLOAD_DOC')) {
        $fileDir = $conf->digiriskdolibarr->dir_output . '/' . $subDir;
        if (!dol_is_dir($fileDir)) {
            dol_mkdir($fileDir);
        }
        dol_add_file_process($fileDir, 0, 1, 'userfile', '', null, '', 0);
    }

    // The lists show the favorite photo of an assessment: it must name a photo still in its folder
    if ($riskAssessment !== null) {
        $photoDir = $conf->digiriskdolibarr->dir_output . '/' . $blockDir;

        if (saturne_favorite_media_needs_update($riskAssessment, 'photo', $photoDir)) {
            $favoritePhoto = '';
            foreach (dol_dir_list($photoDir, 'files', 0, '', '(\.meta|_preview.*\.png)$', 'date', SORT_DESC) as $photo) {
                if (image_format_supported($photo['name']) >= 0) {
                    $favoritePhoto = $photo['name'];
                    break;
                }
            }

            if ($favoritePhoto != $riskAssessment->photo) {
                $riskAssessment->setValueFrom('photo', $favoritePhoto, '', null, 'text', '', $user);
                $riskAssessment->photo = $favoritePhoto;
            }
        }
    }

    print digiriskRiskAssessmentMediaBlock($blockDir, $riskAssessment, $withFiles);
    exit;
}

/**
 * Work folder of the psychosocial assessment of a work unit, while it is being typed
 *
 * Inside the work folder of a new risk, which only moves its own files: the classic risk modals of
 * the same page never take these.
 *
 * @param  int    $elementId Work unit or groupment assessed
 * @return string            Folder, relative to the module folder
 */
function digiriskRiskAssessmentPsychosocialWorkDir(int $elementId): string
{
    return 'riskassessment/tmp/RA0/RPS' . $elementId;
}

/**
 * Move the photos taken while a risk or an assessment was being created into the folder of the
 * new assessment, and make sure it shows one of them. The documents of a psychosocial report follow.
 *
 * Called before the assessment is created: the favorite photo is set on the object, create()
 * stores it.
 *
 * @param  string         $workDir        Work folder the media block wrote the photos to
 * @param  RiskAssessment $riskAssessment Assessment being created, its reference already set
 * @return void
 */
function digiriskRiskAssessmentMovePhotos(string $workDir, RiskAssessment $riskAssessment): void
{
    global $conf;

    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
    require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
    require_once __DIR__ . '/../../saturne/lib/medias.lib.php';

    $photoDir = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/' . $riskAssessment->ref;
    $moved    = [];

    // Files only: the work folder of a new risk also holds the work folders of new assessments
    foreach (dol_dir_list($workDir, 'files', 0, '', '(\.meta|_preview.*\.png)$', 'date', SORT_DESC) as $photo) {
        if (!dol_is_dir($photoDir)) {
            dol_mkdir($photoDir);
        }

        if (!dol_move($photo['fullname'], $photoDir . '/' . $photo['name'])) {
            continue;
        }

        if (image_format_supported($photo['name']) >= 0) {
            foreach (['mini', 'small', 'medium', 'large'] as $size) {
                saturne_vignette($photoDir . '/' . $photo['name'], getDolGlobalInt('SATURNE_MEDIA_MAX_WIDTH_' . dol_strtoupper($size)), getDolGlobalInt('SATURNE_MEDIA_MAX_HEIGHT_' . dol_strtoupper($size)), '_' . $size);
            }
            $moved[] = $photo['name'];
        }
    }

    // The documents of a psychosocial report keep their own subfolder
    foreach (dol_dir_list($workDir . '/documents', 'files') as $document) {
        if (!dol_is_dir($photoDir . '/documents')) {
            dol_mkdir($photoDir . '/documents');
        }
        dol_move($document['fullname'], $photoDir . '/documents/' . $document['name']);
    }
    dol_delete_dir($workDir . '/documents', 1);

    // The thumbs of the work folder were made for the photos that just left it. The folder goes
    // with them once empty, the next upload creates it again
    foreach (dol_dir_list($workDir . '/thumbs', 'files') as $thumb) {
        dol_delete_file($thumb['fullname']);
    }
    dol_delete_dir($workDir . '/thumbs', 1);
    dol_delete_dir($workDir, 1);

    if (!empty($moved) && saturne_favorite_media_needs_update($riskAssessment, 'photo', $photoDir)) {
        $riskAssessment->photo = $moved[0];
    }
}

/**
 * Timestamp of the deadline of a task, typed as a day in the risk modals
 *
 * A datetime-local input left without its time sends nothing, the modals ask for a day only. The
 * deadline lasts until the end of that day, so a task due on the day it starts stays after its start
 * date, and is read in the timezone the modals print it in, so it shows back on the same day.
 *
 * @param  string $date Value of the date input, "YYYY-MM-DD"
 * @return int          Timestamp, or 0 when the input is empty or malformed
 */
function digiriskTaskDeadlineToTimestamp(string $date): int
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts)) {
        return 0;
    }

    return (int) dol_mktime(23, 59, 59, (int) $parts[2], (int) $parts[3], (int) $parts[1]);
}
