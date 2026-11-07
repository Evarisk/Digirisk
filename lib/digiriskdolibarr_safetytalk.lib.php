<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    lib/digiriskdolibarr_safetytalk.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Safety talks (causeries) of the mobile application.
 *
 * A cycle of safety talks is a Dolibarr project carrying the CAUSERIE tag, its participants are the
 * internal contributors of the project. Each talk is a task of the project, held on its own date, and
 * diffused with DoliLetter: the people of the project are the signatories of its spread, and the
 * files of the task (photos, videos, documents) are what they read before signing.
 */

require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
// generate_random_id(), the signature key DoliLetter gives to the people of a spread
require_once DOL_DOCUMENT_ROOT . '/core/lib/ticket.lib.php';

dol_include_once('/saturne/class/saturnesignature.class.php');
dol_include_once('/doliletter/class/doliletterattendancesheet.class.php');
dol_include_once('/doliletter/lib/doliletter_spread.lib.php');

require_once __DIR__ . '/../class/riskanalysis/risk.class.php';

// Project tag of the safety talk cycles, below the QHSE tag of Digirisk like DU, PP, FP...
const DIGIRISKDOLIBARR_SAFETYTALK_TAG = 'CAUSERIE';

// Spread type of a talk: a project task, as DoliLetter and Saturne name it
const DIGIRISKDOLIBARR_SAFETYTALK_SPREAD_TYPE = 'project_task';

// Extra field of the task keeping the theme of the talk (position of a Digirisk danger category)
const DIGIRISKDOLIBARR_SAFETYTALK_THEME_FIELD = 'digirisk_safetytalk_theme';

// Contact type of the participants of a cycle
const DIGIRISKDOLIBARR_SAFETYTALK_PARTICIPANT = 'PROJECTCONTRIBUTOR';

// Extra field of the project keeping the other people of a cycle, without a Dolibarr account (JSON list of
// firstname, lastname, email)
const DIGIRISKDOLIBARR_SAFETYTALK_GUESTS_FIELD = 'digirisk_safetytalk_guests';

/**
 * Whether DoliLetter, which serves the spread of the talks, can be used.
 *
 * @return bool
 */
function digiriskSafetyTalkSpreadAvailable(): bool
{
    return isModEnabled('doliletter') && class_exists('DoliletterAttendanceSheet') && function_exists('doliletter_spread_ensure_attendance_sheet');
}

/**
 * Create what the talks need and a module activation would otherwise bring: the project tag, the theme
 * extra field of the tasks and the one of the other people of a cycle. Done on the talk screens so they
 * work right after an update.
 *
 * @param  DoliDB $db   Database handler
 * @param  User   $user User doing the setup
 * @return int          ID of the project tag, < 0 if KO
 */
function digiriskSafetyTalkEnsureSetup(DoliDB $db, User $user): int
{
    global $extrafields;

    require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

    $fields = [
        'projet_task' => [DIGIRISKDOLIBARR_SAFETYTALK_THEME_FIELD => ['Label' => 'SafetyTalkTheme', 'type' => 'varchar', 'length' => 20, 'elementtype' => ['projet_task'], 'position' => 43630270, 'list' => 0, 'enabled' => "isModEnabled('digiriskdolibarr')", 'langfile' => 'digiriskdolibarr@digiriskdolibarr']],
        'projet'      => [DIGIRISKDOLIBARR_SAFETYTALK_GUESTS_FIELD => ['Label' => 'SafetyTalkGuests', 'type' => 'text', 'elementtype' => ['projet'], 'position' => 43630271, 'list' => 0, 'enabled' => "isModEnabled('digiriskdolibarr')", 'langfile' => 'digiriskdolibarr@digiriskdolibarr']],
    ];

    $extraFields = new ExtraFields($db);
    foreach ($fields as $elementType => $field) {
        $extraFields->fetch_name_optionals_label($elementType);
        if (!isset($extraFields->attributes[$elementType]['label'][key($field)])) {
            saturne_manage_extrafields($field);
            // The extra fields of the page are cached: without a reload, the field just created would be left
            // out of the first save (the theme of the first talk, the people of the first cycle)
            if (is_object($extrafields)) {
                $extrafields->fetch_name_optionals_label($elementType, true);
            }
        }
    }

    return digiriskSafetyTalkGetTagId($db, $user, true);
}

/**
 * ID of the project tag of the safety talk cycles.
 *
 * @param  DoliDB $db     Database handler
 * @param  User   $user   User creating the tag when it is missing
 * @param  bool   $create Create the tag when it does not exist yet
 * @return int            ID of the tag, 0 when missing and not created, < 0 if KO
 */
function digiriskSafetyTalkGetTagId(DoliDB $db, User $user, bool $create = false): int
{
    $tag = new Categorie($db);
    if ($tag->fetch(0, DIGIRISKDOLIBARR_SAFETYTALK_TAG, 'project') > 0) {
        return (int) $tag->id;
    }
    if (!$create) {
        return 0;
    }

    $qhseTag        = new Categorie($db);
    $tag->label     = DIGIRISKDOLIBARR_SAFETYTALK_TAG;
    $tag->type      = 'project';
    $tag->fk_parent = ($qhseTag->fetch(0, 'QHSE', 'project') > 0) ? (int) $qhseTag->id : 0;

    return ($tag->create($user) > 0) ? (int) $tag->id : -1;
}

/**
 * Whether a project is a cycle of safety talks.
 *
 * @param  DoliDB  $db      Database handler
 * @param  Project $project Project, fetched
 * @return bool
 */
function digiriskSafetyTalkIsCycle(DoliDB $db, Project $project): bool
{
    global $user;

    $tagId = digiriskSafetyTalkGetTagId($db, $user);
    if ($tagId <= 0 || $project->id <= 0) {
        return false;
    }

    $resql = $db->query('SELECT fk_project FROM ' . MAIN_DB_PREFIX . 'categorie_project WHERE fk_categorie = ' . $tagId . ' AND fk_project = ' . ((int) $project->id));

    return $resql && $db->num_rows($resql) > 0;
}

/**
 * Cycles of safety talks the user may see, newest first.
 *
 * @param  DoliDB $db     Database handler
 * @param  User   $user   Connected user
 * @param  string $search Words searched in the ref and the title
 * @param  string $status '' for every cycle, else a project status
 * @param  int    $page   Page, from 0
 * @param  int    $limit  Number of cycles per page
 * @return array          [Project[], total number, number of pages]
 */
function digiriskSafetyTalkFetchCycles(DoliDB $db, User $user, string $search, string $status, int $page, int $limit): array
{
    $tagId = digiriskSafetyTalkGetTagId($db, $user);
    if ($tagId <= 0) {
        return [[], 0, 0];
    }

    $sqlFrom  = ' FROM ' . MAIN_DB_PREFIX . 'projet as p';
    $sqlFrom .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'categorie_project as cp ON cp.fk_project = p.rowid AND cp.fk_categorie = ' . $tagId;
    $sqlFrom .= ' WHERE p.entity IN (' . getEntity('project') . ')';
    if ($status !== '') {
        $sqlFrom .= ' AND p.fk_statut = ' . ((int) $status);
    }
    foreach (preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY) as $term) {
        $like     = "'%" . $db->escapeforlike($db->escape($term)) . "%'";
        $sqlFrom .= ' AND (p.ref LIKE ' . $like . ' OR p.title LIKE ' . $like . ')';
    }
    // Without the right to read every project, only the ones the user is a contact of (or public)
    if (!$user->hasRight('projet', 'all', 'lire')) {
        $authorizedProjects = (new Project($db))->getProjectsAuthorizedForUser($user, 0, 1);
        $sqlFrom           .= ' AND p.rowid IN (' . (dol_strlen($authorizedProjects) ? $db->sanitize($authorizedProjects) : '0') . ')';
    }

    $total = 0;
    $resql = $db->query('SELECT COUNT(p.rowid) as nb' . $sqlFrom);
    if ($resql && ($obj = $db->fetch_object($resql))) {
        $total = (int) $obj->nb;
    }

    $cycles = [];
    $resql  = $db->query('SELECT p.rowid' . $sqlFrom . ' ORDER BY p.rowid DESC' . $db->plimit($limit, $limit * $page));
    while ($resql && ($obj = $db->fetch_object($resql))) {
        $cycle = new Project($db);
        if ($cycle->fetch($obj->rowid) > 0) {
            $cycles[] = $cycle;
        }
    }

    return [$cycles, $total, (int) ceil($total / max(1, $limit))];
}

/**
 * Counters of the home screen, over the open cycles the user may see: talks still to come, and people
 * of their spreads who have not signed yet.
 *
 * @param  DoliDB $db   Database handler
 * @param  User   $user Connected user
 * @return array        ['upcoming' => int, 'toSign' => int]
 */
function digiriskSafetyTalkHomeCounters(DoliDB $db, User $user): array
{
    $counters = ['upcoming' => 0, 'toSign' => 0];

    $tagId = digiriskSafetyTalkGetTagId($db, $user);
    if ($tagId <= 0) {
        return $counters;
    }

    $sqlCycles  = ' INNER JOIN ' . MAIN_DB_PREFIX . 'projet as p ON p.rowid = t.fk_projet';
    $sqlCycles .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'categorie_project as cp ON cp.fk_project = p.rowid AND cp.fk_categorie = ' . $tagId;
    $sqlWhere   = ' WHERE p.entity IN (' . getEntity('project') . ') AND p.fk_statut = ' . Project::STATUS_VALIDATED;
    if (!$user->hasRight('projet', 'all', 'lire')) {
        $authorizedProjects = (new Project($db))->getProjectsAuthorizedForUser($user, 0, 1);
        $sqlWhere          .= ' AND p.rowid IN (' . (dol_strlen($authorizedProjects) ? $db->sanitize($authorizedProjects) : '0') . ')';
    }

    $startOfToday = dol_mktime(0, 0, 0, (int) dol_print_date(dol_now(), '%m', 'tzuserrel'), (int) dol_print_date(dol_now(), '%d', 'tzuserrel'), (int) dol_print_date(dol_now(), '%Y', 'tzuserrel'), 'tzuserrel');
    $resql        = $db->query('SELECT COUNT(t.rowid) as nb FROM ' . MAIN_DB_PREFIX . 'projet_task as t' . $sqlCycles . $sqlWhere . " AND t.dateo >= '" . $db->idate($startOfToday) . "'");
    if ($resql && ($obj = $db->fetch_object($resql))) {
        $counters['upcoming'] = (int) $obj->nb;
    }

    if (digiriskSafetyTalkSpreadAvailable()) {
        $sql  = 'SELECT COUNT(s.rowid) as nb FROM ' . MAIN_DB_PREFIX . 'saturne_object_signature as s';
        $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . "doliletter_attendance_sheet as a ON a.rowid = s.fk_object AND s.object_type = 'doliletter_attendance_sheet'";
        $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . "projet_task as t ON t.rowid = a.fk_object AND a.object_type = '" . DIGIRISKDOLIBARR_SAFETYTALK_SPREAD_TYPE . "'";
        $sql .= $sqlCycles . $sqlWhere;
        $sql .= ' AND s.status > 0 AND s.status <> ' . SaturneSignature::STATUS_SIGNED . " AND (s.signature IS NULL OR s.signature = '')";

        $resql = $db->query($sql);
        if ($resql && ($obj = $db->fetch_object($resql))) {
            $counters['toSign'] = (int) $obj->nb;
        }
    }

    return $counters;
}

/**
 * Status badge of a cycle on the PWA cards: open or closed, the two states of a project in use.
 *
 * @param  Project $cycle Cycle, fetched
 * @return string         HTML of the badge
 */
function digiriskSafetyTalkCycleStatusHtml(Project $cycle): string
{
    $isClosed = ((int) $cycle->status === Project::STATUS_CLOSED);

    return '<span class="badge badge-status badge-' . ($isClosed ? 'status6' : 'status4') . '" title="' . dol_escape_htmltag(strip_tags($cycle->getLibStatut(1))) . '"><i class="fas ' . ($isClosed ? 'fa-lock' : 'fa-comments') . '"></i></span>';
}

/**
 * Participants of a cycle: its internal contributors.
 *
 * @param  Project $project Cycle, fetched
 * @return array<int, int>  User ID => ID of the contact link
 */
function digiriskSafetyTalkGetParticipants(Project $project): array
{
    $participants = [];
    $contacts     = $project->liste_contact(-1, 'internal', 0, DIGIRISKDOLIBARR_SAFETYTALK_PARTICIPANT);
    if (is_array($contacts)) {
        foreach ($contacts as $contact) {
            $participants[(int) $contact['id']] = (int) $contact['rowid'];
        }
    }

    return $participants;
}

/**
 * Set the people of a cycle, users and people without account, and pass the change on to the spread of
 * each of its talks.
 *
 * Only what changed is passed on: a new person joins every talk of the cycle, a person removed leaves the
 * talks they have not signed yet (a signature already given stays, it is the proof of their attendance).
 * Someone taken out of one talk alone is not brought back by a later change of the cycle.
 *
 * @param  DoliDB  $db      Database handler
 * @param  Project $project Cycle, fetched
 * @param  int[]   $userIds Users who take part in the cycle
 * @param  array   $guests  People without account, each firstname, lastname, email
 * @param  User    $user    User doing the change
 * @return int              Negative if KO, positive if OK
 */
function digiriskSafetyTalkSetParticipants(DoliDB $db, Project $project, array $userIds, array $guests, User $user): int
{
    $userIds      = array_values(array_unique(array_filter(array_map('intval', $userIds))));
    $participants = digiriskSafetyTalkGetParticipants($project);

    $addedUserIds = [];
    foreach ($userIds as $userId) {
        if (!isset($participants[$userId])) {
            if ($project->add_contact($userId, DIGIRISKDOLIBARR_SAFETYTALK_PARTICIPANT, 'internal') < 0) {
                return -1;
            }
            $addedUserIds[] = $userId;
        }
    }

    $removedUserIds = [];
    foreach ($participants as $userId => $contactLinkId) {
        if (!in_array($userId, $userIds, true)) {
            $project->delete_contact($contactLinkId);
            $removedUserIds[] = $userId;
        }
    }

    // People without account: kept with the cycle, in an extra field of the project
    $project->fetch_optionals();
    $oldGuests = [];
    foreach (digiriskSafetyTalkGetGuests($project) as $guest) {
        $oldGuests[digiriskSafetyTalkGuestKey($guest)] = $guest;
    }
    $newGuests = [];
    foreach ($guests as $guest) {
        $newGuests[digiriskSafetyTalkGuestKey($guest)] = $guest;
    }
    $project->array_options['options_' . DIGIRISKDOLIBARR_SAFETYTALK_GUESTS_FIELD] = empty($newGuests) ? '' : json_encode(array_values($newGuests));
    if ($project->insertExtraFields() < 0) {
        return -1;
    }
    $addedGuests      = array_values(array_diff_key($newGuests, $oldGuests));
    $removedGuestKeys = array_keys(array_diff_key($oldGuests, $newGuests));

    if (!empty($addedUserIds) || !empty($removedUserIds) || !empty($addedGuests) || !empty($removedGuestKeys)) {
        foreach (digiriskSafetyTalkFetchTalks($db, $project) as $talk) {
            if (digiriskSafetyTalkSyncSpread($db, $talk, $addedUserIds, $user, $removedUserIds, $addedGuests, $removedGuestKeys) < 0) {
                $project->error = $talk->error;
                return -1;
            }
        }
    }

    return 1;
}

/**
 * Talks of a cycle, in the order they are held.
 *
 * @param  DoliDB  $db      Database handler
 * @param  Project $project Cycle, fetched
 * @return Task[]
 */
function digiriskSafetyTalkFetchTalks(DoliDB $db, Project $project): array
{
    $talks = [];
    $sql   = 'SELECT t.rowid FROM ' . MAIN_DB_PREFIX . 'projet_task as t';
    $sql  .= ' WHERE t.fk_projet = ' . ((int) $project->id);
    $sql  .= ' ORDER BY t.dateo IS NULL, t.dateo ASC, t.rowid ASC';

    $resql = $db->query($sql);
    while ($resql && ($obj = $db->fetch_object($resql))) {
        $talk = new Task($db);
        if ($talk->fetch($obj->rowid) > 0) {
            $talk->fetch_optionals();
            $talks[] = $talk;
        }
    }

    return $talks;
}

/**
 * Danger category the theme of a talk refers to.
 *
 * @param  string     $theme Position of the danger category, as stored on the task
 * @return array|null        Category (name, thumbnail_name, position, src), null for a talk without theme
 */
function digiriskSafetyTalkGetTheme(string $theme): ?array
{
    if (!dol_strlen($theme) || !is_numeric($theme)) {
        return null;
    }

    foreach (Risk::getDangerCategories() as $dangerCategory) {
        if ((int) $dangerCategory['position'] === (int) $theme) {
            $dangerCategory['src'] = dol_buildpath('/digiriskdolibarr/img/categorieDangers/' . $dangerCategory['thumbnail_name'] . '.png', 1);
            return $dangerCategory;
        }
    }

    return null;
}

/**
 * Date of a talk typed in a datetime-local input, read in the time zone of the user: the one its screens
 * show it back in. dol_mktime() would read it in the time zone of the server by default.
 *
 * @param  string $value Raw input value, "YYYY-MM-DDTHH:MM"
 * @return int           Timestamp, 0 when the value is empty or malformed
 */
function digiriskSafetyTalkParseDate(string $value): int
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})$/', $value, $parts)) {
        return 0;
    }

    return (int) dol_mktime((int) $parts[4], (int) $parts[5], 0, (int) $parts[2], (int) $parts[3], (int) $parts[1], 'tzuserrel');
}

/**
 * When a talk stands compared to now: still to come, today, or held.
 *
 * @param  Task   $talk Talk, fetched
 * @return string       upcoming, today or done; undated for a talk without date
 */
function digiriskSafetyTalkGetPeriod(Task $talk): string
{
    if (empty($talk->date_start)) {
        return 'undated';
    }

    $today = dol_print_date(dol_now(), '%Y-%m-%d', 'tzuserrel');
    $day   = dol_print_date($talk->date_start, '%Y-%m-%d', 'tzuserrel');
    if ($day === $today) {
        return 'today';
    }

    return ($day > $today) ? 'upcoming' : 'done';
}

/**
 * Attendance sheet backing the spread of a talk, as DoliLetter finds it.
 *
 * @param  DoliDB $db     Database handler
 * @param  int    $talkId ID of the talk
 * @return DoliletterAttendanceSheet Fetched when the spread exists, empty otherwise
 */
function digiriskSafetyTalkGetAttendanceSheet(DoliDB $db, int $talkId): DoliletterAttendanceSheet
{
    $attendanceSheet = new DoliletterAttendanceSheet($db, 'doliletter');
    $attendanceSheet->fetch(0, '', " AND object_type = '" . DIGIRISKDOLIBARR_SAFETYTALK_SPREAD_TYPE . "' AND fk_object = " . $talkId);

    return $attendanceSheet;
}

/**
 * People of the spread of a talk.
 *
 * @param  DoliDB $db     Database handler
 * @param  int    $talkId ID of the talk
 * @return SaturneSignature[]
 */
function digiriskSafetyTalkGetSignatories(DoliDB $db, int $talkId): array
{
    if (!digiriskSafetyTalkSpreadAvailable()) {
        return [];
    }

    $attendanceSheet = digiriskSafetyTalkGetAttendanceSheet($db, $talkId);
    if ($attendanceSheet->id <= 0) {
        return [];
    }

    $signatory   = new SaturneSignature($db, 'doliletter', $attendanceSheet->element);
    $signatories = $signatory->fetchSignatories($attendanceSheet->id, $attendanceSheet->element);

    return is_array($signatories) ? array_values($signatories) : [];
}

/**
 * Whether a person of a spread has signed.
 *
 * @param  SaturneSignature $signatory Person of the spread
 * @return bool
 */
function digiriskSafetyTalkHasSigned(SaturneSignature $signatory): bool
{
    return (int) $signatory->status === SaturneSignature::STATUS_SIGNED || dol_strlen((string) $signatory->signature) > 0;
}

/**
 * Key telling a person without account apart in a cycle and in the spreads: name and email, case ignored.
 *
 * @param  array|SaturneSignature $person Person (firstname, lastname, email) or person of a spread
 * @return string
 */
function digiriskSafetyTalkGuestKey($person): string
{
    $person = is_object($person) ? ['firstname' => $person->firstname, 'lastname' => $person->lastname, 'email' => $person->email] : $person;

    return dol_strtolower(trim(($person['firstname'] ?? '') . ' ' . ($person['lastname'] ?? ''))) . '|' . dol_strtolower(trim((string) ($person['email'] ?? '')));
}

/**
 * Clean the people without account typed in a form, one row per person: empty rows are left out.
 *
 * @param  array    $firstnames Firstnames, one per row
 * @param  array    $lastnames  Lastnames, one per row
 * @param  array    $emails     Emails, one per row
 * @param  array    $errors     Filled with the reason of each row refused
 * @return array                People, each ['firstname', 'lastname', 'email']
 */
function digiriskSafetyTalkCleanGuests(array $firstnames, array $lastnames, array $emails, array &$errors = []): array
{
    global $langs;

    $guests = [];
    foreach (array_keys($firstnames + $lastnames + $emails) as $row) {
        $guest = [
            'firstname' => trim(dol_string_nohtmltag((string) ($firstnames[$row] ?? ''))),
            'lastname'  => trim(dol_string_nohtmltag((string) ($lastnames[$row] ?? ''))),
            'email'     => trim(dol_string_nohtmltag((string) ($emails[$row] ?? ''))),
        ];
        if (!dol_strlen($guest['firstname'] . $guest['lastname'] . $guest['email'])) {
            continue;
        }
        if (!dol_strlen($guest['firstname'] . $guest['lastname'])) {
            $errors[] = $langs->transnoentities('SafetyTalkErrorPersonName');
            continue;
        }
        if (dol_strlen($guest['email']) && !isValidEmail($guest['email'])) {
            $errors[] = $langs->transnoentities('SafetyTalkErrorPersonEmail', $guest['email']);
            continue;
        }
        $guests[digiriskSafetyTalkGuestKey($guest)] = $guest;
    }

    return array_values($guests);
}

/**
 * People of a cycle without Dolibarr account (temporary workers, subcontractors, new arrivals).
 *
 * @param  Project $project Cycle, fetched with its extra fields
 * @return array            People, each ['firstname', 'lastname', 'email']
 */
function digiriskSafetyTalkGetGuests(Project $project): array
{
    $guests = json_decode((string) ($project->array_options['options_' . DIGIRISKDOLIBARR_SAFETYTALK_GUESTS_FIELD] ?? ''), true);

    return is_array($guests) ? $guests : [];
}

/**
 * Names of everyone a cycle is for: its users, then its people without account.
 *
 * @param  DoliDB    $db     Database handler
 * @param  Project   $cycle  Cycle, fetched with its extra fields
 * @param  Translate $langs  Translation handler
 * @return array             Each ['name', 'guest' => bool]
 */
function digiriskSafetyTalkGetPeopleNames(DoliDB $db, Project $cycle, Translate $langs): array
{
    $people = [];
    foreach (array_keys(digiriskSafetyTalkGetParticipants($cycle)) as $participantId) {
        $participant = new User($db);
        if ($participant->fetch($participantId) > 0) {
            $people[] = ['name' => $participant->getFullName($langs), 'guest' => false];
        }
    }
    foreach (digiriskSafetyTalkGetGuests($cycle) as $guest) {
        $people[] = ['name' => trim($guest['firstname'] . ' ' . $guest['lastname']), 'guest' => true];
    }

    return $people;
}

/**
 * Add one person to the spread of a talk, as DoliLetter records the people it adds itself.
 *
 * @param  DoliDB                    $db              Database handler
 * @param  DoliletterAttendanceSheet $attendanceSheet Attendance sheet of the spread
 * @param  array                     $person          ['user' => User] for a user, else firstname, lastname, email
 * @param  User                      $user            User doing the change
 * @return int                                        Negative if KO, ID of the person in the spread if OK
 */
function digiriskSafetyTalkCreateSignatory(DoliDB $db, DoliletterAttendanceSheet $attendanceSheet, array $person, User $user): int
{
    $signatory = new SaturneSignature($db, 'doliletter', $attendanceSheet->element);
    if (!empty($person['user'])) {
        $signatory->element_id   = $person['user']->id;
        $signatory->element_type = 'user';
        $signatory->firstname    = $person['user']->firstname;
        $signatory->lastname     = $person['user']->lastname;
        $signatory->email        = $person['user']->email;
    } else {
        $signatory->element_id   = 0;
        $signatory->element_type = DOLILETTER_SPREAD_EXTERNAL_ELEMENT_TYPE;
        $signatory->firstname    = $person['firstname'];
        $signatory->lastname     = $person['lastname'];
        $signatory->email        = $person['email'];
    }
    $signatory->role          = '';
    $signatory->object_type   = $attendanceSheet->element;
    $signatory->fk_object     = $attendanceSheet->id;
    $signatory->module_name   = 'doliletter';
    $signatory->status        = SaturneSignature::STATUS_PENDING_SIGNATURE;
    $signatory->signature_url = generate_random_id();

    $result = $signatory->create($user);
    if ($result < 0) {
        $attendanceSheet->error  = $signatory->error;
        $attendanceSheet->errors = $signatory->errors;
        return -1;
    }

    $attendanceSheet->context = ['user' => doliletter_spread_get_signatory_name($signatory), 'old_user' => ''];
    $attendanceSheet->call_trigger('SPREAD_ADD_USER', $user);

    return $result;
}

/**
 * Attendance sheet of the spread of a talk, created when the talk has none yet.
 *
 * @param  DoliDB $db   Database handler
 * @param  Task   $talk Talk, fetched
 * @param  User   $user User doing the change
 * @return DoliletterAttendanceSheet|null The sheet, null if KO (the reason in $talk->error)
 */
function digiriskSafetyTalkEnsureSpread(DoliDB $db, Task $talk, User $user): ?DoliletterAttendanceSheet
{
    $attendanceSheet = digiriskSafetyTalkGetAttendanceSheet($db, (int) $talk->id);
    if (doliletter_spread_ensure_attendance_sheet($attendanceSheet, saturne_get_objects_metadata(), DIGIRISKDOLIBARR_SAFETYTALK_SPREAD_TYPE, (int) $talk->id, $user) < 0) {
        $talk->error = $attendanceSheet->error;
        return null;
    }

    return $attendanceSheet;
}

/**
 * Put people of the cycle in the spread of one talk, and take out the ones who left the cycle.
 *
 * The spread is created on the way. A person already in it is not added twice, and only the people
 * listed as removed leave it, while they have not signed: someone added to this talk alone stays.
 *
 * @param  DoliDB   $db               Database handler
 * @param  Task     $talk             Talk, fetched
 * @param  int[]    $userIds          Users to add
 * @param  User     $user             User doing the change
 * @param  int[]    $removedUserIds   Users who left the cycle
 * @param  array    $guests           People without account to add, each firstname, lastname, email
 * @param  string[] $removedGuestKeys People without account who left the cycle (digiriskSafetyTalkGuestKey())
 * @return int                        Negative if KO, number of people added if OK
 */
function digiriskSafetyTalkSyncSpread(DoliDB $db, Task $talk, array $userIds, User $user, array $removedUserIds = [], array $guests = [], array $removedGuestKeys = []): int
{
    if (!digiriskSafetyTalkSpreadAvailable()) {
        return 0;
    }

    $attendanceSheet = digiriskSafetyTalkEnsureSpread($db, $talk, $user);
    if ($attendanceSheet === null) {
        return -1;
    }

    $users  = [];
    $others = [];
    foreach (digiriskSafetyTalkGetSignatories($db, (int) $talk->id) as $signatory) {
        if ($signatory->element_type == 'user') {
            $users[(int) $signatory->element_id] = $signatory;
        } else {
            $others[digiriskSafetyTalkGuestKey($signatory)] = $signatory;
        }
    }

    $added   = 0;
    $persons = [];
    foreach ($userIds as $userId) {
        $participant = new User($db);
        if (!isset($users[$userId]) && $participant->fetch($userId) > 0) {
            $persons[] = ['user' => $participant];
        }
    }
    foreach ($guests as $guest) {
        if (!isset($others[digiriskSafetyTalkGuestKey($guest)])) {
            $persons[] = $guest;
        }
    }
    foreach ($persons as $person) {
        if (digiriskSafetyTalkCreateSignatory($db, $attendanceSheet, $person, $user) < 0) {
            $talk->error  = $attendanceSheet->error;
            $talk->errors = $attendanceSheet->errors;
            return -1;
        }
        $added++;
    }

    foreach ($removedUserIds as $userId) {
        if (isset($users[$userId]) && !digiriskSafetyTalkHasSigned($users[$userId])) {
            $users[$userId]->delete($user);
        }
    }
    foreach ($removedGuestKeys as $guestKey) {
        if (isset($others[$guestKey]) && !digiriskSafetyTalkHasSigned($others[$guestKey])) {
            $others[$guestKey]->delete($user);
        }
    }

    return $added;
}

/**
 * Add someone to one talk only, on the spot: a person without account present that day.
 *
 * @param  DoliDB $db     Database handler
 * @param  Task   $talk   Talk, fetched
 * @param  array  $person firstname, lastname, email (cleaned by digiriskSafetyTalkCleanGuests())
 * @param  User   $user   User doing the change
 * @return int            Negative if KO, 0 when already in the spread, ID of the person in the spread if added
 */
function digiriskSafetyTalkAddPerson(DoliDB $db, Task $talk, array $person, User $user): int
{
    $attendanceSheet = digiriskSafetyTalkEnsureSpread($db, $talk, $user);
    if ($attendanceSheet === null) {
        return -1;
    }

    foreach (digiriskSafetyTalkGetSignatories($db, (int) $talk->id) as $signatory) {
        if ($signatory->element_type != 'user' && digiriskSafetyTalkGuestKey($signatory) === digiriskSafetyTalkGuestKey($person)) {
            return 0;
        }
    }

    return digiriskSafetyTalkCreateSignatory($db, $attendanceSheet, $person, $user);
}

/**
 * Take someone out of the spread of one talk, while they have not signed.
 *
 * @param  DoliDB $db          Database handler
 * @param  Task   $talk        Talk, fetched
 * @param  int    $signatoryId ID of the person in the spread
 * @param  User   $user        User doing the change
 * @return int                 Negative if KO (not of this talk, or already signed), positive if OK
 */
function digiriskSafetyTalkRemovePerson(DoliDB $db, Task $talk, int $signatoryId, User $user): int
{
    foreach (digiriskSafetyTalkGetSignatories($db, (int) $talk->id) as $signatory) {
        if ((int) $signatory->id === $signatoryId) {
            return digiriskSafetyTalkHasSigned($signatory) ? -1 : $signatory->delete($user);
        }
    }

    return -1;
}

/**
 * Public page of the spread of a talk, or the page of one of its people.
 *
 * @param  int                   $talkId    ID of the talk
 * @param  SaturneSignature|null $signatory Person of the spread, for their own page
 * @return string                           Absolute URL, empty when DoliLetter is missing
 */
function digiriskSafetyTalkGetSpreadUrl(int $talkId, ?SaturneSignature $signatory = null): string
{
    if (!isModEnabled('doliletter')) {
        return '';
    }

    $url = dol_buildpath('/doliletter/public/spread/add_spread.php', 3) . '?id=' . $talkId . '&object_type=' . DIGIRISKDOLIBARR_SAFETYTALK_SPREAD_TYPE;
    if ($signatory !== null) {
        $url .= '&sign=' . urlencode($signatory->signature_url);
    }

    return $url;
}

/**
 * Short link of Saturne to an address, the one printed under the QR codes. Created at the first call and
 * reused afterwards; the address itself is kept when Saturne cannot shorten it.
 *
 * @param  DoliDB $db   Database handler
 * @param  string $url  Address to shorten
 * @param  User   $user User creating the short link
 * @return string       Short link, or the address
 */
function digiriskSafetyTalkShortUrl(DoliDB $db, string $url, User $user): string
{
    dol_include_once('/saturne/class/saturneredirection.class.php');

    if (!class_exists('SaturneRedirection')) {
        return $url;
    }
    // Nothing to gain when the root of the short links is about as long as the address
    if (dol_strlen(SaturneRedirection::getShortUrlRoot()) + 1 + SaturneRedirection::CODE_LENGTH >= dol_strlen($url)) {
        return $url;
    }

    $saturneRedirection = new SaturneRedirection($db);

    return $saturneRedirection->getShortUrl($url, $user) ?: $url;
}

/**
 * Whether the spread page can show a file in full: images, videos, sounds and PDF. The other files
 * are offered for download.
 *
 * @param  string $filename Name of the file
 * @return bool
 */
function digiriskSafetyTalkIsReadable(string $filename): bool
{
    $mimeType = dol_mimetype($filename);

    return image_format_supported($filename) >= 0 || strpos($mimeType, 'video/') === 0 || strpos($mimeType, 'audio/') === 0 || $mimeType == 'application/pdf';
}

/**
 * Files and links of a talk, as its spread offers them.
 *
 * @param  Task  $talk Talk, fetched
 * @return array       Documents built by doliletter_spread_get_documents(), oldest first
 */
function digiriskSafetyTalkGetDocuments(Task $talk): array
{
    if (!digiriskSafetyTalkSpreadAvailable()) {
        return [];
    }

    $documents = doliletter_spread_get_documents($talk, $talk->table_element, DIGIRISKDOLIBARR_SAFETYTALK_SPREAD_TYPE);
    usort($documents, fn ($a, $b) => $a['date'] <=> $b['date']);

    return $documents;
}

/**
 * Where the Saturne media block of a talk stores its files: the documents of the task, as its Documents tab
 * does, or a temporary folder of the form session while the talk is not created yet.
 *
 * @param  Project   $cycle       Cycle, fetched
 * @param  Task|null $talk        Talk, null on the creation form
 * @param  string    $uploadToken Token of the form session (saturne_get_upload_token()), for a new talk
 * @return array                  ['module', 'subdir', 'dir'] as the media block resolves them
 */
function digiriskSafetyTalkMediaContext(Project $cycle, ?Task $talk, string $uploadToken = ''): array
{
    global $conf;

    if ($talk !== null && $talk->id > 0) {
        $subDir = dol_sanitizeFileName($cycle->ref) . '/' . dol_sanitizeFileName($talk->ref);
        return ['module' => 'project', 'subdir' => $subDir, 'dir' => $conf->project->dir_output . '/' . $subDir];
    }

    $subDir = DIGIRISK_MOBILE_UPLOAD_DIR . '/' . $uploadToken . '/safetytalk';
    return ['module' => 'digiriskdolibarr', 'subdir' => $subDir, 'dir' => $conf->digiriskdolibarr->dir_output . '/' . $subDir];
}

/**
 * Saturne media block of a talk: photos through its photo editor, videos, sounds and documents through its
 * file picker, each one sent as soon as picked.
 *
 * @param  array  $context Media context (digiriskSafetyTalkMediaContext())
 * @param  string $prefix  Prefix of the ids of the block
 * @return string          HTML of the block
 */
function digiriskSafetyTalkMediaBlock(array $context, string $prefix): string
{
    return saturne_render_media_block($context['module'], $context['subdir'], $prefix, 'projet,creer', [
        'show_photo'  => true,
        'show_audio'  => false,
        'show_file'   => true,
        // The block offers office documents only: a talk is mostly a film to show
        'file_accept' => 'video/*,audio/*,.pdf,.doc,.docx,.odt,.xls,.xlsx,.ods,.ppt,.pptx,.odp,.txt',
    ]);
}

/**
 * Answer the actions of the Saturne media block of a talk (uploadPhoto, uploadFile, deletePhoto, deleteFile):
 * write or remove in the folder of the block, then send back the refreshed block, which its script puts in
 * place. Once the talk exists, its files are indexed on the task and diffused.
 *
 * @param  string    $action  Requested action
 * @param  array     $context Media context (digiriskSafetyTalkMediaContext())
 * @param  string    $prefix  Prefix of the ids of the block
 * @param  Task|null $talk    Talk, null on the creation form
 * @param  User      $user    User doing the change
 * @return bool               False when the action is not one of the block
 */
function digiriskSafetyTalkMediaAction(string $action, array $context, string $prefix, ?Task $talk, User $user): bool
{
    global $langs;

    if (!in_array($action, ['uploadPhoto', 'uploadFile', 'deletePhoto', 'deleteFile'], true)) {
        return false;
    }
    // Only the folder of this block is writable: the sub directory comes from the browser
    if (GETPOST('sub_dir', 'alphanohtml') !== $context['subdir'] || dol_strtolower(GETPOST('module_name', 'alpha')) !== $context['module']) {
        accessforbidden($langs->trans('NotEnoughPermissions'), 0, 0, 1);
    }

    $talk = ($talk !== null && $talk->id > 0) ? $talk : null;
    $dir  = $context['dir'];
    if (!dol_is_dir($dir)) {
        dol_mkdir($dir);
    }

    if (($action == 'uploadPhoto' || $action == 'uploadFile') && getDolGlobalInt('MAIN_UPLOAD_DOC')) {
        $uploaded = isset($_FILES['userfile']['tmp_name']) ? array_filter((array) $_FILES['userfile']['tmp_name']) : [];
        $allowed  = true;
        // The photo button only takes images, on their real type
        if ($action == 'uploadPhoto') {
            $fileInfo = new finfo(FILEINFO_MIME_TYPE);
            foreach ($uploaded as $tmpName) {
                $allowed = $allowed && strpos((string) $fileInfo->file($tmpName), 'image/') === 0;
            }
        }
        if (!$allowed) {
            setEventMessages($langs->trans('ErrorFileNotAnImage'), [], 'errors');
        } elseif (!empty($uploaded)) {
            // A phone names its files alike (image.jpg, video.mp4): a second one takes a free name rather than
            // being refused, except an edited photo sent back to replace its original
            if (!GETPOSTINT('overwrite')) {
                digiriskSafetyTalkFreeUploadNames($dir);
            }
            $knownIds = $talk !== null ? array_column(array_filter(digiriskSafetyTalkGetDocuments($talk), fn ($document) => $document['type'] == 'file'), 'id') : [];
            // An edited photo is sent back under its own name and replaces the original. Indexed on the task
            // once it exists (last argument)
            if (dol_add_file_process($dir, GETPOSTINT('overwrite') ? 1 : 0, 1, 'userfile', '', null, '', 1, $talk) > 0) {
                if ($action == 'uploadPhoto') {
                    foreach ((array) $_FILES['userfile']['name'] as $uploadedName) {
                        digiriskSafetyTalkPhotoThumbnails($dir . '/' . digiriskSafetyTalkStoredName((string) $uploadedName));
                    }
                }
                if ($talk !== null) {
                    digiriskSafetyTalkShareNewDocuments($talk, $knownIds, $user);
                }
            }
        }
    } elseif ($action == 'deletePhoto' || $action == 'deleteFile') {
        $fileName = dol_sanitizeFileName(GETPOST('filename', 'alphanohtml'));
        if (dol_strlen($fileName) && dol_is_file($dir . '/' . $fileName)) {
            // The index of the file goes with it; its thumbnails too
            dol_delete_file($dir . '/' . $fileName, 0, 0, 0, $talk);
            foreach (dol_dir_list($dir . '/thumbs', 'files', 0, '^' . preg_quote(pathinfo($fileName, PATHINFO_FILENAME), '/') . '_') as $thumbnail) {
                dol_delete_file($thumbnail['fullname'], 0, 1);
            }
        }
    }

    // The messages of the request would show on the next page: the errors go back with the block, for the
    // script of the screen to show them, the refreshed block is the confirmation
    $mediaErrors = array_merge((array) ($_SESSION['dol_events']['errors'] ?? []), (array) ($_SESSION['dol_events']['warnings'] ?? []));
    unset($_SESSION['dol_events']['errors'], $_SESSION['dol_events']['warnings'], $_SESSION['dol_events']['mesgs']);

    print digiriskSafetyTalkMediaBlock($context, $prefix);
    print '<div class="hidden" id="safetytalk-media-errors" data-errors="' . dol_escape_htmltag(json_encode(array_values(array_map(fn ($error) => dol_string_nohtmltag(dol_html_entity_decode((string) $error, ENT_QUOTES), 1), $mediaErrors)))) . '"></div>';
    return true;
}

/**
 * Give the files being sent through the media block a name free in their folder: a phone names its shots
 * and its films alike, the second one would be refused.
 *
 * @param  string $dir Folder the files go to
 * @return void
 */
function digiriskSafetyTalkFreeUploadNames(string $dir)
{
    if (empty($_FILES['userfile']['name'])) {
        return;
    }

    $names = (array) $_FILES['userfile']['name'];
    $taken = [];
    foreach ($names as $index => $name) {
        $stored = digiriskSafetyTalkStoredName((string) $name);
        $info   = pathinfo($stored);
        $suffix = 1;
        while (dol_is_file($dir . '/' . $stored) || in_array($stored, $taken, true)) {
            $stored = $info['filename'] . '-' . $suffix++ . (isset($info['extension']) ? '.' . $info['extension'] : '');
        }
        $taken[]       = $stored;
        $names[$index] = $stored;
    }
    $_FILES['userfile']['name'] = is_array($_FILES['userfile']['name']) ? $names : reset($names);
}

/**
 * Name an uploaded file is stored under, the way dol_add_file_process() writes it: cleaned, its extension
 * lowercased (a phone shot IMG_0001.JPG is stored as IMG_0001.jpg). The thumbnails must use that name.
 *
 * @param  string $fileName Name the browser sent
 * @return string           Name of the stored file
 */
function digiriskSafetyTalkStoredName(string $fileName): string
{
    $info = pathinfo(trim($fileName));

    return dol_string_nohtmltag(dol_sanitizeFileName($info['filename'] . (!empty($info['extension']) ? '.' . strtolower($info['extension']) : '')));
}

/**
 * Thumbnails of a photo, in the sizes the media blocks of Saturne show.
 *
 * @param  string $path Full path of the photo
 * @return void
 */
function digiriskSafetyTalkPhotoThumbnails(string $path)
{
    if (!dol_is_file($path) || image_format_supported($path) < 0) {
        return;
    }
    foreach (['mini', 'small', 'medium', 'large'] as $size) {
        saturne_vignette($path, getDolGlobalInt('SATURNE_MEDIA_MAX_WIDTH_' . dol_strtoupper($size)), getDolGlobalInt('SATURNE_MEDIA_MAX_HEIGHT_' . dol_strtoupper($size)), '_' . $size);
    }
}

/**
 * Diffuse the files of a talk added since a list of known ones: shown in full on the spread page when the
 * browser can (photos, videos, sounds, PDF), offered for download otherwise.
 *
 * @param  Task  $talk     Talk, fetched
 * @param  int[] $knownIds Files of the talk in the index before the addition
 * @param  User  $user     User adding the files
 * @return int             Number of files diffused
 */
function digiriskSafetyTalkShareNewDocuments(Task $talk, array $knownIds, User $user): int
{
    $added = 0;
    foreach (digiriskSafetyTalkGetDocuments($talk) as $document) {
        if ($document['type'] != 'file' || in_array($document['id'], $knownIds, true)) {
            continue;
        }

        $mode = digiriskSafetyTalkIsReadable($document['name']) ? DOLILETTER_SPREAD_DOCUMENT_READ : DOLILETTER_SPREAD_DOCUMENT_DOWNLOAD;
        if (doliletter_spread_set_document_mode($talk, $talk->table_element, DIGIRISKDOLIBARR_SAFETYTALK_SPREAD_TYPE, 'file', $document['id'], $mode, $user) > 0) {
            $added++;
        }
    }

    return $added;
}

/**
 * Bring the files added on the creation form of a talk, kept in the temporary folder of the form session,
 * into the documents of the talk just created: moved with their thumbnails, indexed on the task and diffused.
 *
 * @param  Task   $talk        Talk just created, fetched
 * @param  array  $tempContext Media context of the creation form (digiriskSafetyTalkMediaContext())
 * @param  array  $context     Media context of the talk
 * @param  User   $user        User creating the talk
 * @return int                 Number of files brought
 */
function digiriskSafetyTalkAttachUploads(Task $talk, array $tempContext, array $context, User $user): int
{
    if (!dol_is_dir($tempContext['dir'])) {
        return 0;
    }
    if (!dol_is_dir($context['dir'] . '/thumbs')) {
        dol_mkdir($context['dir'] . '/thumbs');
    }

    $knownIds = array_column(array_filter(digiriskSafetyTalkGetDocuments($talk), fn ($document) => $document['type'] == 'file'), 'id');
    $moved    = 0;
    // Every type: saturne_get_media_files() would only list images and sounds, the others would be lost
    foreach (dol_dir_list($tempContext['dir'], 'files', 0, '', '(\.meta|_preview.*\.png)$') as $file) {
        if (dol_move($file['fullname'], $context['dir'] . '/' . $file['name'], '0', 1, 0, 0)) {
            addFileIntoDatabaseIndex($context['dir'], $file['name'], $file['name'], 'uploaded', 0, $talk);
            $moved++;
        }
    }
    foreach (dol_dir_list($tempContext['dir'] . '/thumbs', 'files') as $thumbnail) {
        dol_move($thumbnail['fullname'], $context['dir'] . '/thumbs/' . $thumbnail['name'], '0', 1, 0, 0);
    }
    dol_delete_dir_recursive($tempContext['dir']);

    digiriskSafetyTalkShareNewDocuments($talk, $knownIds, $user);

    return $moved;
}

/**
 * Add a link to a talk (a video hosted elsewhere, a web page), offered on the spread page to open it.
 *
 * @param  DoliDB $db    Database handler
 * @param  Task   $talk  Talk, fetched
 * @param  string $url   Address of the link
 * @param  string $label Label of the link, the address when empty
 * @param  User   $user  User adding the link
 * @return int           Negative if KO, ID of the link if OK
 */
function digiriskSafetyTalkAddLink(DoliDB $db, Task $talk, string $url, string $label, User $user): int
{
    require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';

    if (!preg_match('/^https?:\/\//i', $url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        return -1;
    }

    $link             = new Link($db);
    $link->url        = $url;
    $link->label      = dol_strlen($label) ? $label : $url;
    $link->objecttype = DIGIRISKDOLIBARR_SAFETYTALK_SPREAD_TYPE;
    $link->objectid   = $talk->id;
    $link->datea      = dol_now();

    $linkId = $link->create($user);
    if ($linkId <= 0) {
        return -1;
    }

    // Offered as a button to open it: shown in full, the spread page would frame the site, which most
    // video sites refuse, and leave an empty box
    $link->array_options['options_favorite'] = 0;
    $link->insertExtraFields();

    return $linkId;
}

/**
 * Remove a file or a link of a talk.
 *
 * @param  DoliDB $db   Database handler
 * @param  Task   $talk Talk, fetched
 * @param  string $type 'file' or 'link'
 * @param  int    $id   ID of the file in the index, or of the link
 * @param  User   $user User doing the removal
 * @return int          Negative if KO, positive if OK
 */
function digiriskSafetyTalkDeleteDocument(DoliDB $db, Task $talk, string $type, int $id, User $user): int
{
    require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';
    require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';

    // Only a document of this talk: any other ID would let the screen remove a file of another record
    $isDocumentOfTalk = false;
    foreach (digiriskSafetyTalkGetDocuments($talk) as $document) {
        $isDocumentOfTalk = $isDocumentOfTalk || ($document['type'] == $type && $document['id'] == $id);
    }
    if (!$isDocumentOfTalk) {
        return -1;
    }

    if ($type == 'link') {
        $link = new Link($db);
        return ($link->fetch($id) > 0) ? $link->delete($user) : -1;
    }

    $ecmFile = new EcmFiles($db);
    if ($ecmFile->fetch($id) <= 0) {
        return -1;
    }

    return dol_delete_file(DOL_DATA_ROOT . '/' . $ecmFile->filepath . '/' . $ecmFile->filename, 0, 0, 0, $talk) ? 1 : -1;
}

/**
 * Remove a talk: its spread, its documents and the task. Refused once someone has signed, the
 * signatures are the proof of the attendance.
 *
 * @param  DoliDB $db   Database handler
 * @param  Task   $talk Talk, fetched
 * @param  User   $user User doing the removal
 * @return int          Negative if KO, positive if OK
 */
function digiriskSafetyTalkDelete(DoliDB $db, Task $talk, User $user): int
{
    $signatories = digiriskSafetyTalkGetSignatories($db, (int) $talk->id);
    foreach ($signatories as $signatory) {
        if (digiriskSafetyTalkHasSigned($signatory)) {
            return -2;
        }
    }

    $db->begin();

    // Soft deletion, as DoliLetter removes a person from a spread: the hard one of the attendance sheet
    // fails on its extra fields table, which DoliLetter declares but never creates
    foreach ($signatories as $signatory) {
        if ($signatory->delete($user) < 0) {
            $db->rollback();
            return -1;
        }
    }

    if (digiriskSafetyTalkSpreadAvailable()) {
        $attendanceSheet = digiriskSafetyTalkGetAttendanceSheet($db, (int) $talk->id);
        if ($attendanceSheet->id > 0 && $attendanceSheet->delete($user) < 0) {
            $db->rollback();
            return -1;
        }

        foreach (digiriskSafetyTalkGetDocuments($talk) as $document) {
            if ($document['type'] == 'link') {
                digiriskSafetyTalkDeleteDocument($db, $talk, 'link', $document['id'], $user);
            }
        }
        $documentDir = doliletter_spread_get_document_dir($talk);
    }

    if ($talk->delete($user) <= 0) {
        $db->rollback();
        return -1;
    }

    $db->commit();

    if (!empty($documentDir) && dol_is_dir($documentDir)) {
        dol_delete_dir_recursive($documentDir);
    }

    return 1;
}

/**
 * Send to the people of a talk who have not signed yet the link to their own spread page, with the
 * spread email template of DoliLetter.
 *
 * @param  DoliDB    $db    Database handler
 * @param  Task      $talk  Talk, fetched
 * @param  User      $user  User sending the emails
 * @param  Translate $langs Translation handler
 * @return array            ['sent' => string[] (emails), 'failed' => string[] (names), 'error' => string]
 */
function digiriskSafetyTalkSendSignatureLinks(DoliDB $db, Task $talk, User $user, Translate $langs): array
{
    require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
    dol_include_once('/saturne/class/saturnemail.class.php');

    $langs->loadLangs(['doliletter@doliletter', 'signature@saturne']);

    $result = ['sent' => [], 'failed' => [], 'error' => ''];
    if (!dol_strlen(getDolGlobalString('MAIN_MAIL_EMAIL_FROM')) || (!dol_strlen(getDolGlobalString('MAIN_MAIL_SMTPS_ID')) && getDolGlobalInt('SATURNE_USE_ALL_EMAIL_MODE') <= 0)) {
        $result['error'] = $langs->transnoentities('SafetyTalkMailNotConfigured');
        return $result;
    }

    // The same message as the one DoliLetter sends from the spread page
    $saturneMail = new SaturneMail($db, 'doliletter', DIGIRISKDOLIBARR_SAFETYTALK_SPREAD_TYPE);
    $hasTemplate = getDolGlobalInt('DOLILETTER_EMAIL_TEMPLATE_SPREAD') > 0 && $saturneMail->fetch(getDolGlobalInt('DOLILETTER_EMAIL_TEMPLATE_SPREAD')) > 0;
    $topic       = $hasTemplate ? $saturneMail->topic : $langs->transnoentities('EmailSpreadTopic');
    $content     = $hasTemplate ? $saturneMail->content : $langs->transnoentities('EmailSpreadContent');

    foreach (digiriskSafetyTalkGetSignatories($db, (int) $talk->id) as $signatory) {
        if (digiriskSafetyTalkHasSigned($signatory)) {
            continue;
        }

        $sendTo = $signatory->email;
        if ($signatory->element_type == 'user') {
            $participant = new User($db);
            if ($participant->fetch($signatory->element_id) > 0 && dol_strlen($participant->email)) {
                $sendTo = $participant->email;
            }
        }
        if (!dol_strlen($sendTo) || !isValidEmail($sendTo)) {
            $result['failed'][] = doliletter_spread_get_signatory_name($signatory);
            continue;
        }

        $substitutionArray                              = getCommonSubstitutionArray($langs, 0, null, $talk);
        $substitutionArray['__OBJECT_ELEMENT__']        = dol_strtolower($langs->transnoentities('SafetyTalk'));
        $substitutionArray['__REF__']                   = $talk->ref;
        $substitutionArray['__SATURNE_SIGNATORY_URL__'] = '<a href="' . digiriskSafetyTalkGetSpreadUrl((int) $talk->id, $signatory) . '" target="_blank">' . $langs->transnoentities('SignatureEmailURL') . '</a>';
        complete_substitutions_array($substitutionArray, $langs, $talk);

        $mailFile = new CMailFile(make_substitutions($topic, $substitutionArray), $sendTo, getDolGlobalString('MAIN_MAIL_EMAIL_FROM'), make_substitutions($content, $substitutionArray), [], [], [], '', '', 0, -1, '', '', '', '', 'mail');
        if (empty($mailFile->error) && $mailFile->sendfile()) {
            $signatory->last_email_sent_date = dol_now();
            $signatory->update($user, 1);
            $result['sent'][] = $sendTo;
        } else {
            $result['failed'][] = doliletter_spread_get_signatory_name($signatory);
            $result['error']    = dol_string_nohtmltag((string) $mailFile->error, 1);
        }
    }

    return $result;
}
