<?php
/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
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
 * \file    lib/digiriskdolibarr_firepermitround.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Functions of the fire watch rounds done after the hot work of a fire permit.
 */

/**
 * Rounds planned by default after the end of the hot work, in minutes: 30 min, 1 h and 2 h.
 * Two hours is the minimum surveillance recommended after hot work (INRS ED 6030).
 */
const DIGIRISK_FIREPERMIT_ROUND_DEFAULT_DELAYS = '30,60,120';

/**
 * A round planned beyond one day would never be walked by the watcher of the job.
 */
const DIGIRISK_FIREPERMIT_ROUND_MAX_DELAY = 1440;

/**
 * More rounds than this are a typo in the setup rather than a surveillance plan.
 */
const DIGIRISK_FIREPERMIT_ROUND_MAX_COUNT = 12;

/**
 * Elements a watcher may be asked for when recording a round, each set to one of the levels below.
 */
const DIGIRISK_FIREPERMIT_ROUND_ITEMS = ['comment', 'photo', 'geoloc', 'signature'];

const DIGIRISK_FIREPERMIT_ROUND_ITEM_DISABLED = 0;
const DIGIRISK_FIREPERMIT_ROUND_ITEM_OPTIONAL = 1;
const DIGIRISK_FIREPERMIT_ROUND_ITEM_REQUIRED = 2;

/**
 * Turn a typed list of delays ("30, 60, 120") into the sorted list of distinct delays it holds.
 *
 * @param  string $value Delays in minutes, separated by commas, semicolons or spaces
 * @return int[]         Delays in minutes, ascending
 */
function digiriskFirePermitParseRoundDelays(string $value): array
{
    $delays = [];
    foreach (preg_split('/[\s,;]+/', $value, -1, PREG_SPLIT_NO_EMPTY) as $delay) {
        if (!ctype_digit($delay)) {
            continue;
        }
        $delay = (int) $delay;
        if ($delay > 0 && $delay <= DIGIRISK_FIREPERMIT_ROUND_MAX_DELAY) {
            $delays[$delay] = $delay;
        }
    }
    sort($delays);

    return array_slice($delays, 0, DIGIRISK_FIREPERMIT_ROUND_MAX_COUNT);
}

/**
 * Delays of the rounds to plan after the end of the hot work, from the module setup.
 *
 * @return int[] Delays in minutes, ascending
 */
function digiriskFirePermitGetRoundDelays(): array
{
    $delays = digiriskFirePermitParseRoundDelays(getDolGlobalString('DIGIRISKDOLIBARR_FIREPERMIT_ROUND_DELAYS', DIGIRISK_FIREPERMIT_ROUND_DEFAULT_DELAYS));

    // An empty setup would let a permit be closed without any surveillance: keep the minimum
    return !empty($delays) ? $delays : digiriskFirePermitParseRoundDelays(DIGIRISK_FIREPERMIT_ROUND_DEFAULT_DELAYS);
}

/**
 * Write a delay the way people say it: "30 min", "2 h", "1 h 30".
 *
 * @param  int    $minutes Delay in minutes
 * @return string          Readable delay
 */
function digiriskFirePermitFormatDelay(int $minutes): string
{
    if ($minutes < 60) {
        return $minutes . ' min';
    }

    $hours   = intdiv($minutes, 60);
    $minutes = $minutes % 60;

    return $hours . ' h' . ($minutes > 0 ? ' ' . sprintf('%02d', $minutes) : '');
}

/**
 * Level at which an element is asked for when recording a round.
 *
 * @param  string $item One of DIGIRISK_FIREPERMIT_ROUND_ITEMS
 * @return int          DIGIRISK_FIREPERMIT_ROUND_ITEM_DISABLED, _OPTIONAL or _REQUIRED
 */
function digiriskFirePermitRoundItemLevel(string $item): int
{
    // The signature closes the round, as asked in issue #3854: required unless the setup says otherwise
    $default = ($item === 'signature') ? DIGIRISK_FIREPERMIT_ROUND_ITEM_REQUIRED : DIGIRISK_FIREPERMIT_ROUND_ITEM_OPTIONAL;
    $level   = getDolGlobalInt('DIGIRISKDOLIBARR_FIREPERMIT_ROUND_' . strtoupper($item), $default);

    return in_array($level, [DIGIRISK_FIREPERMIT_ROUND_ITEM_DISABLED, DIGIRISK_FIREPERMIT_ROUND_ITEM_OPTIONAL, DIGIRISK_FIREPERMIT_ROUND_ITEM_REQUIRED], true) ? $level : $default;
}

/**
 * Whether the public interface of the rounds is open: the watcher is often an external guard with
 * no Dolibarr account, so the rounds are recorded from a page reached by a secret link.
 *
 * @return bool True when enabled and a key exists
 */
function digiriskFirePermitRoundsPublicEnabled(): bool
{
    return getDolGlobalInt('DIGIRISKDOLIBARR_FIREPERMIT_ROUND_PUBLIC_INTERFACE') && dol_strlen(getDolGlobalString('DIGIRISKDOLIBARR_FIREPERMIT_ROUND_PUBLIC_KEY')) > 0;
}

/**
 * Check the key given to the public interface of the rounds.
 *
 * @param  string $key Key read from the URL
 * @return bool        True when it opens the interface
 */
function digiriskFirePermitRoundsCheckPublicKey(string $key): bool
{
    return digiriskFirePermitRoundsPublicEnabled() && hash_equals(getDolGlobalString('DIGIRISKDOLIBARR_FIREPERMIT_ROUND_PUBLIC_KEY'), $key);
}

/**
 * Link to the public interface of the rounds, to hand to the watcher (or to print as a QR code).
 *
 * @param  int    $firePermitId Fire permit to open directly, 0 for the list of the permits
 * @return string               Absolute URL, empty when the interface is closed
 */
function digiriskFirePermitRoundsPublicUrl(int $firePermitId = 0): string
{
    global $conf;

    if (!digiriskFirePermitRoundsPublicEnabled()) {
        return '';
    }

    $params = ['entity' => $conf->entity, 'key' => getDolGlobalString('DIGIRISKDOLIBARR_FIREPERMIT_ROUND_PUBLIC_KEY')];
    if ($firePermitId > 0) {
        $params['id'] = $firePermitId;
    }

    return dol_buildpath('/custom/digiriskdolibarr/public/firepermit/firepermit_rounds.php', 3) . '?' . http_build_query($params);
}

/**
 * Check what the watcher sent against what the setup asks for.
 *
 * @param  array     $input Values sent: watcher_name, comment, latitude, longitude, signature, has_photo
 * @param  Translate $langs Translation object
 * @return string[]         Error messages, empty when the round can be recorded
 */
function digiriskFirePermitCheckRoundInput(array $input, Translate $langs): array
{
    $errors = [];

    if (!dol_strlen(trim($input['watcher_name']))) {
        $errors[] = $langs->transnoentities('ErrorFieldRequired', $langs->transnoentities('FireWatchName'));
    }

    $isRequired = function (string $item) {
        return digiriskFirePermitRoundItemLevel($item) === DIGIRISK_FIREPERMIT_ROUND_ITEM_REQUIRED;
    };

    if ($isRequired('comment') && !dol_strlen(trim($input['comment']))) {
        $errors[] = $langs->transnoentities('ErrorFieldRequired', $langs->transnoentities('Comment'));
    }
    if ($isRequired('photo') && empty($input['has_photo'])) {
        $errors[] = $langs->transnoentities('FireWatchErrorPhotoRequired');
    }
    if ($isRequired('geoloc') && ($input['latitude'] === null || $input['longitude'] === null)) {
        $errors[] = $langs->transnoentities('FireWatchErrorGeolocRequired');
    }
    if ($isRequired('signature') && !dol_strlen($input['signature'])) {
        $errors[] = $langs->transnoentities('FireWatchErrorSignatureRequired');
    }

    return $errors;
}

/**
 * Read a coordinate posted by the browser geolocation.
 *
 * @param  string     $value Raw value
 * @param  float      $limit Absolute bound (90 for a latitude, 180 for a longitude)
 * @return float|null        Coordinate, null when absent or out of bounds
 */
function digiriskFirePermitParseCoordinate(string $value, float $limit): ?float
{
    if (!is_numeric($value)) {
        return null;
    }
    $coordinate = (float) $value;

    return abs($coordinate) <= $limit ? $coordinate : null;
}

/**
 * Upload context of the photos of a round, as known to saturne_get_upload_token().
 *
 * @param  FirePermitRound $round Round being recorded
 * @return string                 Context
 */
function digiriskFirePermitRoundUploadContext(FirePermitRound $round): string
{
    return 'digiriskdolibarr_firepermit_round_' . ((int) $round->id);
}

/**
 * Temporary directory of the photos of a round: the Saturne media block fills it while the watcher
 * fills the form, and the photos move into the round once it is recorded.
 *
 * Named after an upload token of the session rather than after the round: the page may be public,
 * and a directory anyone could guess would let them drop files into the round of someone else.
 *
 * @param  FirePermitRound $round Round being recorded
 * @return string                 Directory relative to the module output directory
 */
function digiriskFirePermitRoundUploadSubDir(FirePermitRound $round): string
{
    require_once __DIR__ . '/../../saturne/lib/medias.lib.php';
    require_once __DIR__ . '/digiriskdolibarr_mobile.lib.php';

    return DIGIRISK_MOBILE_UPLOAD_DIR . '/' . saturne_get_upload_token(digiriskFirePermitRoundUploadContext($round));
}

/**
 * Saturne media block of the photos of the round being recorded.
 *
 * @param  FirePermitRound $round Round being recorded
 * @return string                 HTML of the block
 */
function digiriskFirePermitRoundMediaBlock(FirePermitRound $round): string
{
    $block = saturne_render_media_block('digiriskdolibarr', digiriskFirePermitRoundUploadSubDir($round), 'firewatch-round', 'digiriskdolibarr,firepermit,write', ['show_photo' => true, 'show_audio' => false, 'show_file' => false]);

    // Nobody is logged in on the public interface: document.php answers its login page to the
    // thumbnail and to the gallery, the image wrapper of Saturne serves them without a session.
    // The gallery carries its URLs as JSON, where the slashes are escaped
    if (defined('NOLOGIN')) {
        $documentUrl  = DOL_URL_ROOT . '/document.php?';
        $viewImageUrl = dol_buildpath('/saturne/utils/viewimage.php', 1) . '?';
        $block        = str_replace([$documentUrl, str_replace('/', '\/', $documentUrl)], [$viewImageUrl, str_replace('/', '\/', $viewImageUrl)], $block);
    }

    return $block;
}

/**
 * Photo taken or removed from the media block of the round form: the Saturne media block posts on
 * the page and expects itself back, refreshed. Only the temporary directory of the round the form
 * records is writable.
 *
 * @param  FirePermit $object Locked fire permit
 * @param  string     $action Requested action: anything but uploadPhoto and deletePhoto returns at once
 * @param  int        $now    Timestamp of the request
 * @return void               Ends the request when it handled the action
 * @throws Exception
 */
function digiriskFirePermitRoundsMediaActions(FirePermit $object, string $action, int $now): void
{
    global $conf, $db;

    if (!in_array($action, ['uploadPhoto', 'deletePhoto'], true)) {
        return;
    }

    // The round the form records: the earliest one whose time has come
    $roundObject   = new FirePermitRound($db);
    $roundToRecord = null;
    foreach ($roundObject->fetchFromFirePermit($object->id) as $round) {
        if ($round->canBePerformed($now)) {
            $roundToRecord = $round;
            break;
        }
    }

    $uploadSubDir = ($roundToRecord !== null && digiriskFirePermitRoundItemLevel('photo') !== DIGIRISK_FIREPERMIT_ROUND_ITEM_DISABLED) ? digiriskFirePermitRoundUploadSubDir($roundToRecord) : '';
    if (!dol_strlen($uploadSubDir) || GETPOST('sub_dir', 'alpha') !== $uploadSubDir) {
        http_response_code(403);
        exit;
    }

    // Not loaded by the public pages
    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

    $uploadDir = $conf->digiriskdolibarr->dir_output . '/' . $uploadSubDir;
    if (!dol_is_dir($uploadDir)) {
        dol_mkdir($uploadDir);
    }

    if ($action == 'uploadPhoto' && getDolGlobalInt('MAIN_UPLOAD_DOC')) {
        // The page can be opened by anyone holding the link: only actual images are written
        $isImageUpload = true;
        foreach ((array) ($_FILES['userfile']['tmp_name'] ?? []) as $uploadedTmpName) {
            if (empty($uploadedTmpName)) {
                continue;
            }
            $fileInfo = new finfo(FILEINFO_MIME_TYPE);
            if (strpos((string) $fileInfo->file($uploadedTmpName), 'image/') !== 0) {
                $isImageUpload = false;
                break;
            }
        }
        if ($isImageUpload) {
            dol_add_file_process($uploadDir, GETPOSTINT('overwrite'), 1, 'userfile', '', null, '', 1);
        }
    } elseif ($action == 'deletePhoto') {
        $photoToDelete = dol_sanitizeFileName(GETPOST('filename', 'alphanohtml'));
        if (dol_strlen($photoToDelete) && dol_is_file($uploadDir . '/' . $photoToDelete)) {
            // disableglob: a bracket in the name would be read as a character class
            dol_delete_file($uploadDir . '/' . $photoToDelete, 1);
        }
    }

    print digiriskFirePermitRoundMediaBlock($roundToRecord);
    exit;
}

/**
 * Photos waiting in the media block of a round, not recorded yet.
 *
 * @param  FirePermitRound $round Round being recorded
 * @return array                  Files as returned by saturne_get_media_files()
 */
function digiriskFirePermitRoundPendingPhotos(FirePermitRound $round): array
{
    return saturne_get_media_files('digiriskdolibarr', digiriskFirePermitRoundUploadSubDir($round), '', '', ['type' => 'image', 'sort_order' => 'asc']);
}

/**
 * Move the photos of the media block into the round, once it is recorded, then forget the upload.
 *
 * @param  FirePermit      $permit Fire permit
 * @param  FirePermitRound $round  Round just recorded
 * @return int                     Number of photos moved, -1 if one of them could not be
 */
function digiriskFirePermitMoveRoundPhotos(FirePermit $permit, FirePermitRound $round): int
{
    global $conf;

    // Not loaded by the public pages
    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

    $photos = digiriskFirePermitRoundPendingPhotos($round);
    if (!empty($photos)) {
        $photoDir = $conf->digiriskdolibarr->multidir_output[$permit->entity ?: $conf->entity] . '/' . $round->getPhotoSubDir($permit->ref);
        if (!dol_is_dir($photoDir) && dol_mkdir($photoDir) < 0) {
            return -1;
        }
        foreach ($photos as $photo) {
            if (!dol_move($photo['fullname'], $photoDir . '/' . $photo['name'], '0', 1, 0, 0)) {
                return -1;
            }
        }
    }

    saturne_invalidate_upload_token(digiriskFirePermitRoundUploadContext($round), 'digiriskdolibarr', DIGIRISK_MOBILE_UPLOAD_DIR);

    return count($photos);
}

/**
 * Photos taken during a round.
 *
 * @param  FirePermit      $permit Fire permit
 * @param  FirePermitRound $round  Round
 * @return array                   List of ['name' => file name, 'url' => URL readable by a logged in user]
 */
function digiriskFirePermitGetRoundPhotos(FirePermit $permit, FirePermitRound $round): array
{
    require_once __DIR__ . '/../../saturne/lib/medias.lib.php';

    return saturne_get_media_files('digiriskdolibarr', $round->getPhotoSubDir($permit->ref), '', '', ['type' => 'image', 'sort_order' => 'asc']);
}

/**
 * Where the surveillance of a fire permit stands.
 *
 * @param  FirePermit        $permit Fire permit
 * @param  FirePermitRound[] $rounds Its rounds, in the order they have to be done
 * @param  int               $now    Timestamp the state is computed for
 * @return array                     ['state' => 'working', 'late', 'due', 'upcoming' or 'done',
 *                                    'nextRound' => FirePermitRound|null, 'doneCount' => int, 'total' => int]
 */
function digiriskFirePermitRoundsSummary(FirePermit $permit, array $rounds, int $now): array
{
    $summary = ['state' => empty($permit->date_work_end) ? 'working' : 'done', 'nextRound' => null, 'doneCount' => 0, 'total' => count($rounds)];

    foreach ($rounds as $round) {
        if ((int) $round->status === FirePermitRound::STATUS_DONE) {
            $summary['doneCount']++;
        } elseif ($summary['nextRound'] === null) {
            $summary['nextRound'] = $round;
            $summary['state']     = $round->getState($now);
        }
    }

    return $summary;
}

/**
 * Counters of the home screen of the application: rounds to walk now, and permits under watch.
 *
 * @return int[] ['toDo' => rounds due or late, 'watched' => locked permits with rounds left]
 */
function digiriskFirePermitRoundsCounters(): array
{
    global $db;

    $permit = new FirePermit($db);

    $sql  = 'SELECT COUNT(r.rowid) as nb_planned, COUNT(DISTINCT r.fk_firepermit) as nb_permits,';
    $sql .= " SUM(CASE WHEN r.date_planned <= '" . $db->idate(dol_now() + FirePermitRound::EARLY_TOLERANCE) . "' THEN 1 ELSE 0 END) as nb_todo";
    $sql .= ' FROM ' . $db->prefix() . 'digiriskdolibarr_firepermit_round as r';
    $sql .= ' INNER JOIN ' . $db->prefix() . 'digiriskdolibarr_firepermit as f ON f.rowid = r.fk_firepermit';
    $sql .= ' WHERE r.status = ' . FirePermitRound::STATUS_PLANNED . ' AND f.status = ' . FirePermit::STATUS_LOCKED;
    $sql .= ' AND f.entity IN (' . getEntity($permit->element) . ')';

    $resql = $db->query($sql);
    $obj   = $resql ? $db->fetch_object($resql) : null;

    return ['toDo' => $obj ? (int) $obj->nb_todo : 0, 'watched' => $obj ? (int) $obj->nb_permits : 0];
}

/**
 * Steps the watcher performs on a permit: declare the end of the hot work, then record each round.
 *
 * Shared by the public interface and the screen of the application, so that a round recorded from
 * one is checked exactly like a round recorded from the other.
 *
 * @param  FirePermit $object      Locked fire permit
 * @param  User       $user        User behind the step (an empty one from the public interface)
 * @param  string     $action      'declareWorkEnd' or 'recordRound', anything else does nothing
 * @param  int        $now         Timestamp of the request
 * @param  string     $eventSuffix Appended to the agenda event, to tell where the step was recorded from
 * @return array                   ['saved' => 'workend', 'round' or '' when nothing was saved, 'errors' => string[]]
 * @throws Exception
 */
function digiriskFirePermitRoundsDoActions(FirePermit $object, User $user, string $action, int $now, string $eventSuffix = ''): array
{
    global $db, $langs;

    $result = ['saved' => '', 'errors' => []];

    // End of the hot work, declared by the watcher on site
    if ($action == 'declareWorkEnd' && empty($object->date_work_end)) {
        $fireWatchName = GETPOST('firewatch_name', 'alphanohtml');
        // Offsets rather than a time to type: the phone of a guard is not set to the timezone of the server
        $endedAgo      = GETPOSTINT('ended_ago');
        if (!in_array($endedAgo, [0, 15, 30, 45, 60], true)) {
            $endedAgo = 0;
        }

        if (!dol_strlen(trim($fireWatchName))) {
            $result['errors'][] = $langs->transnoentities('ErrorFieldRequired', $langs->transnoentities('FireWatchName'));
        } elseif ($object->declareWorkEnd($user, $now - $endedAgo * 60, $fireWatchName, digiriskFirePermitGetRoundDelays()) > 0) {
            digiriskFirePermitAddRoundEvent($db, $object, $user, 'FIREPERMIT_WORKEND', $langs->transnoentities('FireWatchWorkEndEvent', $object->ref), $langs->transnoentities('FireWatchName') . ' : ' . $object->firewatch_name . $eventSuffix);
            $result['saved'] = 'workend';
        } else {
            $result['errors'][] = $langs->transnoentities($object->error);
        }
    }

    // A round walked by the watcher
    if ($action == 'recordRound') {
        $roundId = GETPOSTINT('round_id');
        $round   = new FirePermitRound($db);

        if ($roundId <= 0 || $round->fetch($roundId) <= 0 || (int) $round->fk_firepermit !== (int) $object->id || !$round->canBePerformed($now)) {
            $result['errors'][] = $langs->transnoentities('FireWatchErrorRoundNotAvailable');
            return $result;
        }

        // Sent like the Saturne signature pad sends it, as a JSON string: read raw and decoded here,
        // only an image data URL passes the check below
        $signature = json_decode((string) GETPOST('signature', 'none'));
        if (digiriskFirePermitRoundItemLevel('signature') === DIGIRISK_FIREPERMIT_ROUND_ITEM_DISABLED || !is_string($signature) || !digiriskIsValidSignature($signature)) {
            $signature = '';
        }

        $roundInput = [
            'watcher_name' => GETPOST('watcher_name', 'alphanohtml'),
            'comment'      => digiriskFirePermitRoundItemLevel('comment') !== DIGIRISK_FIREPERMIT_ROUND_ITEM_DISABLED ? GETPOST('comment', 'restricthtml') : '',
            'latitude'     => digiriskFirePermitRoundItemLevel('geoloc') !== DIGIRISK_FIREPERMIT_ROUND_ITEM_DISABLED ? digiriskFirePermitParseCoordinate(GETPOST('latitude', 'alphanohtml'), 90) : null,
            'longitude'    => digiriskFirePermitRoundItemLevel('geoloc') !== DIGIRISK_FIREPERMIT_ROUND_ITEM_DISABLED ? digiriskFirePermitParseCoordinate(GETPOST('longitude', 'alphanohtml'), 180) : null,
            'signature'    => $signature,
            // The photos are already on the server, taken from the media block before the form is sent
            'has_photo'    => digiriskFirePermitRoundItemLevel('photo') !== DIGIRISK_FIREPERMIT_ROUND_ITEM_DISABLED && !empty(digiriskFirePermitRoundPendingPhotos($round)),
        ];
        // A position is a pair: half of it is worth nothing
        if ($roundInput['latitude'] === null || $roundInput['longitude'] === null) {
            $roundInput['latitude']  = null;
            $roundInput['longitude'] = null;
        }

        $result['errors'] = digiriskFirePermitCheckRoundInput($roundInput, $langs);

        if (empty($result['errors'])) {
            $comment = dol_string_nohtmltag((string) $roundInput['comment'], 0);
            if ($round->record($user, $roundInput['watcher_name'], $comment, $roundInput['latitude'], $roundInput['longitude'], $roundInput['signature']) > 0) {
                // A photo left behind stays in the temporary directory: the round itself is recorded
                if ($roundInput['has_photo'] && digiriskFirePermitMoveRoundPhotos($object, $round) < 0) {
                    dol_syslog('digiriskFirePermitRoundsDoActions: photos of round ' . $round->id . ' could not be moved', LOG_WARNING);
                }

                $eventNote  = $langs->transnoentities('FireWatchName') . ' : ' . $round->watcher_name . $eventSuffix;
                $eventNote .= dol_strlen($comment) ? '<br>' . $langs->transnoentities('Comment') . ' : ' . dol_escape_htmltag($comment) : '';
                digiriskFirePermitAddRoundEvent($db, $object, $user, 'FIREPERMIT_ROUND', $langs->transnoentities('FireWatchRoundEvent', $round->position, $object->ref), $eventNote);

                $result['saved'] = 'round';
            } else {
                $result['errors'][] = $langs->transnoentities('FireWatchErrorRoundNotSaved');
            }
        }
    }

    return $result;
}

/**
 * Fire permits the watcher has to deal with, most urgent first: a late round, a round to do now,
 * the next rounds, the work under way, then what is over.
 *
 * @param  int      $now       Timestamp the states are computed for
 * @param  callable $permitUrl Builds the URL of a permit from its id
 * @return array               Rows read by core/tpl/firepermit/firepermit_rounds_public_list.tpl.php
 * @throws Exception
 */
function digiriskFirePermitRoundsListRows(int $now, callable $permitUrl): array
{
    global $db;

    $permit            = new FirePermit($db);
    $roundObject       = new FirePermitRound($db);
    $digiriskResources = new DigiriskResources($db);

    $permits = $permit->fetchForFireWatch();

    // Rounds of every listed permit in one query
    $roundsByPermit = [];
    if (!empty($permits)) {
        $allRounds = $roundObject->fetchAll('ASC', 'position', 0, 0, ['customsql' => 't.fk_firepermit IN (' . implode(',', array_map('intval', array_keys($permits))) . ')']);
        foreach (is_array($allRounds) ? $allRounds : [] as $round) {
            if ($round instanceof FirePermitRound) {
                $roundsByPermit[(int) $round->fk_firepermit][] = $round;
            }
        }
    }

    $stateOrder = ['late' => 0, 'due' => 1, 'upcoming' => 2, 'working' => 3, 'done' => 4];
    $listRows   = [];
    foreach ($permits as $permit) {
        $extSociety = $digiriskResources->fetchResourcesFromObject('ExtSociety', $permit);
        $summary    = digiriskFirePermitRoundsSummary($permit, $roundsByPermit[$permit->id] ?? [], $now);

        $listRows[] = [
            'url'         => $permitUrl($permit->id),
            'ref'         => $permit->ref,
            'label'       => $permit->label,
            'society'     => ($extSociety instanceof Societe && $extSociety->id > 0) ? $extSociety->name : '',
            'state'       => $summary['state'],
            'nextRound'   => $summary['nextRound'],
            'dateEnd'     => $permit->date_end,
            'dateWorkEnd' => $permit->date_work_end,
            'sortKey'     => ($stateOrder[$summary['state']] ?? 4) * 10000000000 + ($summary['nextRound'] !== null ? (int) $summary['nextRound']->date_planned : (int) $permit->date_start),
        ];
    }
    usort($listRows, function ($a, $b) {
        return $a['sortKey'] <=> $b['sortKey'];
    });

    return $listRows;
}

/**
 * What the watcher needs to know about one permit: where the work took place and what it was, its
 * rounds, and the round the form records.
 *
 * @param  FirePermit $object Locked fire permit
 * @param  int        $now    Timestamp the states are computed for
 * @return array              Read by core/tpl/firepermit/firepermit_rounds_public_card.tpl.php: societyName,
 *                            workLocation, workTypes, rounds, roundToRecord, nextReloadAt, roundItemLevels
 * @throws Exception
 */
function digiriskFirePermitRoundsCardData(FirePermit $object, int $now): array
{
    global $db, $langs;

    $digiriskResources = new DigiriskResources($db);
    $digiriskElement   = new DigiriskElement($db);
    $firePermitLine    = new FirePermitLine($db);
    $risk              = new Risk($db);
    $roundObject       = new FirePermitRound($db);

    $extSociety = $digiriskResources->fetchResourcesFromObject('ExtSociety', $object);

    $data = [
        'societyName'     => ($extSociety instanceof Societe && $extSociety->id > 0) ? $extSociety->name : '',
        'workLocation'    => '',
        'workTypes'       => [],
        'rounds'          => $roundObject->fetchFromFirePermit($object->id),
        'roundToRecord'   => null,
        'nextReloadAt'    => 0,
        'roundItemLevels' => [],
    ];

    // Where the hot work took place and what it was, so the watcher knows what to look at
    $workLines = $firePermitLine->fetchAll('', '', 0, 0, ['customsql' => 't.fk_firepermit = ' . ((int) $object->id)]);
    foreach (is_array($workLines) ? $workLines : [] as $workLine) {
        if (!dol_strlen($data['workLocation']) && $workLine->fk_element > 0 && $digiriskElement->fetch($workLine->fk_element) > 0) {
            $data['workLocation'] = $digiriskElement->ref . ' - ' . $digiriskElement->label;
        }
        $workTypeName        = $risk->getFirePermitDangerCategoryName($workLine);
        $data['workTypes'][] = [
            'name'        => ($workTypeName !== -1) ? $langs->trans($workTypeName) : '',
            'description' => (string) $workLine->description,
        ];
    }

    // One round at a time, the earliest one whose time has come
    foreach ($data['rounds'] as $round) {
        if ($data['roundToRecord'] === null && $round->canBePerformed($now)) {
            $data['roundToRecord'] = $round;
        }
        if ($round->getState($now) === 'upcoming' && ($data['nextReloadAt'] === 0 || $round->date_planned < $data['nextReloadAt'])) {
            $data['nextReloadAt'] = (int) $round->date_planned - FirePermitRound::EARLY_TOLERANCE;
        }
    }

    foreach (DIGIRISK_FIREPERMIT_ROUND_ITEMS as $roundItem) {
        $data['roundItemLevels'][$roundItem] = digiriskFirePermitRoundItemLevel($roundItem);
    }

    return $data;
}

/**
 * Trace a step of the surveillance in the agenda of the fire permit.
 *
 * Written here rather than through a trigger: the rounds are mostly recorded from the public
 * interface, where no user is logged in to own the event.
 *
 * @param  DoliDB     $db    Database handler
 * @param  FirePermit $permit Fire permit
 * @param  User       $user  User behind the step (an empty one from the public interface)
 * @param  string     $code  Event code, without the AC_ prefix
 * @param  string     $label Event label
 * @param  string     $note  Event details
 * @return int               Event id if OK, <0 if KO
 */
function digiriskFirePermitAddRoundEvent(DoliDB $db, FirePermit $permit, User $user, string $code, string $label, string $note = ''): int
{
    require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

    $actioncomm               = new ActionComm($db);
    $actioncomm->elementtype  = $permit->element . '@digiriskdolibarr';
    $actioncomm->type_code    = 'AC_OTH_AUTO';
    $actioncomm->code         = 'AC_' . $code;
    $actioncomm->label        = $label;
    $actioncomm->note_private = $note;
    $actioncomm->datep        = dol_now();
    $actioncomm->fk_element   = $permit->id;
    $actioncomm->fk_project   = $permit->fk_project;
    $actioncomm->userownerid  = $user->id > 0 ? $user->id : $permit->fk_user_creat;
    $actioncomm->percentage   = -1;

    return $actioncomm->create($user);
}
