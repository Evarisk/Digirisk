<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    lib/digiriskdolibarr_media.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Library files with common functions for the Saturne media blocks of the cards and of the public ticket
 */

/**
 * Folders of a path that do not exist yet, the deepest first
 *
 * The Saturne media block creates its folder, and the missing ones above it, as soon as it is
 * displayed: the blocks remove them afterwards, so that looking at a page leaves no empty folder
 * behind. An upload creates them again.
 *
 * @param  string   $dir Full path of the folder
 * @return string[]      Missing folders, from the folder itself up
 */
function digiriskMediaMissingDirs(string $dir): array
{
    $missingDirs = [];
    for (; !dol_is_dir($dir) && $dir !== dirname($dir); $dir = dirname($dir)) {
        $missingDirs[] = $dir;
    }

    return $missingDirs;
}

/**
 * Saturne media block of the photos of a record, shown on its card
 *
 * The block carries the record, so its photo editor tells which photo the record displays and lets
 * another one be picked. The picking button opens the media library of the module.
 *
 * @param  CommonObject $object        Record owning the photos
 * @param  string       $subDir        Folder of the photos, relative to the module folder
 * @param  string       $favoriteField Field of the record holding the photo it displays
 * @param  bool         $canAdd        True when photos can be added to the record
 * @return string                      HTML of the block
 */
function digiriskObjectMediaBlock(CommonObject $object, string $subDir, string $favoriteField, bool $canAdd): string
{
    global $conf;

    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
    require_once __DIR__ . '/../../saturne/lib/medias.lib.php';

    $missingDirs = digiriskMediaMissingDirs($conf->digiriskdolibarr->dir_output . '/' . $subDir);

    $block = saturne_render_media_block('digiriskdolibarr', $subDir, 'object-media', '', [
        'show_photo'     => true,
        'show_audio'     => false,
        'show_file'      => false,
        'show_upload'    => $canAdd,
        'show_library'   => true,
        'object'         => $object,
        'object_subtype' => $favoriteField,
    ]);

    foreach ($missingDirs as $missingDir) {
        dol_delete_dir($missingDir, 1);
    }

    return $block;
}

/**
 * Photo added, taken from the media library or removed from the media block of a record card: the
 * Saturne media block posts on the page and expects itself back, refreshed. The folder comes from the
 * browser, so only the one of the record is writable.
 *
 * A refused action, the record being locked or the right missing, sends the block back unchanged.
 *
 * @param  CommonObject $object        Record of the card
 * @param  string       $action        Requested action: anything but uploadPhoto, linkPhoto and deletePhoto returns at once
 * @param  string       $subDir        Folder of the photos of the record, relative to the module folder
 * @param  string       $favoriteField Field of the record holding the photo it displays
 * @param  bool         $canAdd        True when photos can be added to the record
 * @param  bool         $canDelete     True when photos can be removed from the record
 * @return void                        Ends the request when it handled the action
 * @throws Exception
 */
function digiriskObjectMediaActions(CommonObject $object, string $action, string $subDir, string $favoriteField, bool $canAdd, bool $canDelete): void
{
    global $conf, $db, $langs, $user;

    if (!in_array($action, ['uploadPhoto', 'linkPhoto', 'deletePhoto'], true) || GETPOST('module_name', 'alpha') !== 'digiriskdolibarr') {
        return;
    }

    if ($object->id <= 0 || GETPOST('sub_dir', 'alphanohtml') !== $subDir) {
        http_response_code(403);
        exit;
    }

    if ($action == 'deletePhoto' ? $canDelete : $canAdd) {
        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
        require_once __DIR__ . '/../../saturne/lib/medias.lib.php';

        // Writes the photo with its thumbs and puts it in the library, copies it from the library, or
        // removes it with its thumbs
        require dol_buildpath('/saturne/core/tpl/actions/media_block_actions.tpl.php');

        // The banner, the lists and the documents show the photo the record displays: it must name a
        // photo still in its folder
        $photoDir = $conf->digiriskdolibarr->dir_output . '/' . $subDir;
        if (saturne_favorite_media_needs_update($object, $favoriteField, $photoDir)) {
            $favoritePhoto = '';
            foreach (dol_dir_list($photoDir, 'files', 0, '', '(\.meta|_preview.*\.png)$', 'date', SORT_DESC) as $photo) {
                if (image_format_supported($photo['name']) >= 0) {
                    $favoritePhoto = $photo['name'];
                    break;
                }
            }

            if ($favoritePhoto != $object->$favoriteField) {
                $object->setValueFrom($favoriteField, $favoritePhoto, '', null, 'text', '', $user);
                $object->$favoriteField = $favoritePhoto;
            }
        }
    }

    print digiriskObjectMediaBlock($object, $subDir, $favoriteField, $canAdd);
    exit;
}

/**
 * Upload context of the medias of the public ticket being written, as known to saturne_get_upload_token()
 *
 * @return string Context
 */
function digiriskTicketPublicUploadContext(): string
{
    global $conf;

    return 'digiriskdolibarr_public_ticket_' . ((int) $conf->entity);
}

/**
 * Temporary folder of the medias of the public ticket being written: the media block fills it while the
 * declarant fills the form, and they move into the ticket once it is created.
 *
 * Named after an upload token of the session: anyone can open the page, and a folder they could guess
 * would let them read or fill the one of someone else.
 *
 * @return string Folder relative to the module folder
 */
function digiriskTicketPublicUploadSubDir(): string
{
    require_once __DIR__ . '/../../saturne/lib/medias.lib.php';

    return 'temp/ticket/' . saturne_get_upload_token(digiriskTicketPublicUploadContext());
}

/**
 * Saturne media block of the public ticket being written: photos and voice notes
 *
 * @param  string $subDir Temporary folder of the ticket, see digiriskTicketPublicUploadSubDir()
 * @return string         HTML of the block
 */
function digiriskTicketPublicMediaBlock(string $subDir): string
{
    global $conf;

    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
    require_once __DIR__ . '/../../saturne/lib/medias.lib.php';

    // Every visitor gets a folder: only those who send something keep it
    $missingDirs = digiriskMediaMissingDirs($conf->digiriskdolibarr->dir_output . '/' . $subDir);

    $block = saturne_render_media_block('digiriskdolibarr', $subDir, 'ticket-media', '', ['show_photo' => true, 'show_audio' => true, 'show_file' => false]);

    foreach ($missingDirs as $missingDir) {
        dol_delete_dir($missingDir, 1);
    }

    // Nobody is logged in on the public interface: document.php answers its login page to the medias,
    // the page rendering the block serves those of its own session itself, see
    // digiriskTicketPublicShowMedia(). Every URL sits in an escaped attribute, and the gallery carries
    // them as JSON, where the slashes are escaped
    $documentUrl = DOL_URL_ROOT . '/document.php?';
    $mediaUrl    = dol_escape_htmltag($_SERVER['PHP_SELF']) . '?action=showMedia&amp;';

    return str_replace([$documentUrl, str_replace('/', '\/', $documentUrl)], [$mediaUrl, str_replace('/', '\/', $mediaUrl)], $block);
}

/**
 * Media sent or removed from the media block of the public ticket being written: the Saturne media block
 * posts on the page and expects itself back, refreshed. Only the temporary folder of the session is
 * writable.
 *
 * @param  string $action Requested action: anything but the photo and voice note ones returns at once
 * @param  string $subDir Temporary folder of the ticket, see digiriskTicketPublicUploadSubDir()
 * @return void           Ends the request when it handled the action
 */
function digiriskTicketPublicMediaActions(string $action, string $subDir): void
{
    global $conf, $langs;

    if (!in_array($action, ['uploadPhoto', 'deletePhoto', 'add_audio', 'delete_audio'], true)) {
        return;
    }

    if (GETPOST('module_name', 'alpha') !== 'digiriskdolibarr' || GETPOST('sub_dir', 'alphanohtml') !== $subDir) {
        http_response_code(403);
        exit;
    }

    // Not loaded by the public pages
    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
    require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
    require_once __DIR__ . '/../../saturne/lib/medias.lib.php';

    $uploadDir = $conf->digiriskdolibarr->dir_output . '/' . $subDir;
    if (!dol_is_dir($uploadDir)) {
        dol_mkdir($uploadDir);
    }

    $mediaSizes = ['mini', 'small', 'medium', 'large'];

    if ($action == 'uploadPhoto' && getDolGlobalInt('MAIN_UPLOAD_DOC')) {
        // Anyone holding the link can post here: only actual images are written
        $isAllowed = true;
        foreach ((array) ($_FILES['userfile']['tmp_name'] ?? []) as $uploadedTmpName) {
            if (empty($uploadedTmpName)) {
                continue;
            }
            $fileInfo = new finfo(FILEINFO_MIME_TYPE);
            if (strpos((string) $fileInfo->file($uploadedTmpName), 'image/') !== 0) {
                $isAllowed = false;
                break;
            }
        }

        // Neither indexed nor kept in session: the medias only wait here for the ticket
        if ($isAllowed && dol_add_file_process($uploadDir, GETPOSTINT('overwrite'), -1, 'userfile', '', null, '', 0) > 0) {
            foreach ((array) ($_FILES['userfile']['name'] ?? []) as $uploadedName) {
                $uploadedPath = $uploadDir . '/' . dol_sanitizeFileName($uploadedName);
                if (!dol_is_file($uploadedPath) || image_format_supported($uploadedPath) < 0) {
                    continue;
                }
                foreach ($mediaSizes as $mediaSize) {
                    saturne_vignette($uploadedPath, getDolGlobalInt('SATURNE_MEDIA_MAX_WIDTH_' . dol_strtoupper($mediaSize)), getDolGlobalInt('SATURNE_MEDIA_MAX_HEIGHT_' . dol_strtoupper($mediaSize)), '_' . $mediaSize);
                }
            }

            // The block shows what was sent: the transfer message of the core would only surface on
            // the next page, next to the confirmation of the ticket
            if (!empty($_SESSION['dol_events']['mesgs'])) {
                $_SESSION['dol_events']['mesgs'] = array_values(array_diff($_SESSION['dol_events']['mesgs'], [$langs->trans('FileTransferComplete')]));
            }
        }
    } elseif ($action == 'add_audio' && getDolGlobalInt('MAIN_UPLOAD_DOC') && !empty($_FILES['audio']['tmp_name'])) {
        // The browser records in the container it likes, WebM or MP4, whatever the name it gives
        $fileInfo  = new finfo(FILEINFO_MIME_TYPE);
        $audioType = (string) $fileInfo->file($_FILES['audio']['tmp_name']);
        $isAudio   = strpos($audioType, 'audio/') === 0 || in_array($audioType, ['video/webm', 'video/mp4', 'application/ogg'], true);

        // Named like the other voice notes of Saturne, which its media block lists by extension
        if ($isAudio && $_FILES['audio']['size'] <= getDolGlobalInt('MAIN_UPLOAD_DOC') * 1024) {
            dol_move_uploaded_file($_FILES['audio']['tmp_name'], $uploadDir . '/' . dol_print_date(dol_now(), 'dayhourlog') . '_audio.wav', 0, 0, $_FILES['audio']['error'], 0, 'audio', $uploadDir);
        }
    } elseif ($action == 'deletePhoto' || $action == 'delete_audio') {
        $fileToDelete = dol_sanitizeFileName(GETPOST('filename', 'alphanohtml'));
        if (dol_strlen($fileToDelete) && dol_is_file($uploadDir . '/' . $fileToDelete)) {
            // disableglob: a bracket in the name would be read as a character class
            dol_delete_file($uploadDir . '/' . $fileToDelete, 1);

            foreach ($mediaSizes as $mediaSize) {
                $thumbPath = $uploadDir . '/thumbs/' . saturne_get_thumb_name($fileToDelete, $mediaSize);
                if (dol_is_file($thumbPath)) {
                    dol_delete_file($thumbPath, 1);
                }
            }
        }
    }

    print digiriskTicketPublicMediaBlock($subDir);
    exit;
}

/**
 * Serve a media of the public ticket being written, to the block that shows it
 *
 * Only the photos and voice notes of the temporary folder of the session are read: a declarant sees
 * what they sent, nothing else.
 *
 * @param  string $subDir Temporary folder of the ticket, see digiriskTicketPublicUploadSubDir()
 * @return void           Ends the request
 */
function digiriskTicketPublicShowMedia(string $subDir): void
{
    global $conf;

    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

    $file     = GETPOST('file', 'alphanohtml');
    $filePath = $conf->digiriskdolibarr->dir_output . '/' . $subDir . '/' . basename($file);

    if (dirname($file) !== $subDir || !dol_is_file($filePath)) {
        http_response_code(404);
        exit;
    }

    // A voice note recorded by Chrome or Safari is a WebM or MP4 container
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $fileType = (string) $fileInfo->file($filePath);
    if (!preg_match('/^(image\/(?!svg)|audio\/|video\/(webm|mp4)$)/', $fileType)) {
        http_response_code(404);
        exit;
    }

    top_httphead($fileType);
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: inline; filename="' . dol_sanitizeFileName(basename($file)) . '"');
    header('Content-Length: ' . dol_filesize($filePath));

    readfile(dol_osencode($filePath));
    exit;
}
