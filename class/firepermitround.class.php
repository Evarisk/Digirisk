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
 * \file    class/firepermitround.class.php
 * \ingroup digiriskdolibarr
 * \brief   CRUD class of the fire watch rounds done after the hot work of a fire permit.
 *
 * Once the hot work is over, the safety watcher ("surveillant de securite", INRS ED 6030) has to
 * walk the place several times to catch a smouldering fire. Each expected round is planned when
 * the end of the work is declared, at a delay taken from the module setup (30 min, 1 h and 2 h by
 * default), and stays planned until the watcher records it.
 */

// Load Saturne libraries
require_once __DIR__ . '/../../saturne/class/saturneobject.class.php';

/**
 * Class for FirePermitRound
 */
class FirePermitRound extends SaturneObject
{
    /**
     * @var string Module name
     */
    public $module = 'digiriskdolibarr';

    /**
     * @var string Element type of object
     */
    public $element = 'firepermitround';

    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element = 'digiriskdolibarr_firepermit_round';

    /**
     * @var int<0, 1>|string Does this object support multicompany module ?
     */
    public $ismultientitymanaged = 1;

    /**
     * @var string Name of icon for the round
     */
    public string $picto = 'fontawesome_fa-walking_fas_#d35968';

    public const STATUS_PLANNED = 0;
    public const STATUS_DONE    = 1;

    /**
     * A round recorded a few minutes before its time still counts: the watcher is already on site
     */
    public const EARLY_TOLERANCE = 300;

    /**
     * Past this delay after its planned time, a round not recorded yet is shown as late
     */
    public const LATE_TOLERANCE = 900;

    /**
     * Role of the watcher's signature, stored by Saturne with the round as object
     */
    public const SIGNATORY_ROLE = 'FireWatcher';

    /**
     * @var array Array with all fields and their property. Do not use it as a static var. It may be modified by constructor
     */
    public $fields = [
        'rowid'         => ['type' => 'integer',      'label' => 'TechnicalID',          'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1],
        'entity'        => ['type' => 'integer',      'label' => 'Entity',               'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 0, 'index' => 1],
        'date_creation' => ['type' => 'datetime',     'label' => 'DateCreation',         'enabled' => 1, 'position' => 20,  'notnull' => 1, 'visible' => 0],
        'tms'           => ['type' => 'timestamp',    'label' => 'DateModification',     'enabled' => 1, 'position' => 30,  'notnull' => 0, 'visible' => 0],
        'status'        => ['type' => 'smallint',     'label' => 'Status',               'enabled' => 1, 'position' => 40,  'notnull' => 1, 'visible' => 0, 'default' => 0, 'index' => 1],
        'position'      => ['type' => 'integer',      'label' => 'Position',             'enabled' => 1, 'position' => 50,  'notnull' => 1, 'visible' => 0, 'default' => 0],
        'delay_minutes' => ['type' => 'integer',      'label' => 'FireWatchRoundDelay',  'enabled' => 1, 'position' => 60,  'notnull' => 1, 'visible' => 0],
        'date_planned'  => ['type' => 'datetime',     'label' => 'FireWatchRoundPlanned','enabled' => 1, 'position' => 70,  'notnull' => 1, 'visible' => 0],
        'date_done'     => ['type' => 'datetime',     'label' => 'FireWatchRoundDone',   'enabled' => 1, 'position' => 80,  'notnull' => 0, 'visible' => 0],
        'watcher_name'  => ['type' => 'varchar(255)', 'label' => 'FireWatchName',        'enabled' => 1, 'position' => 90,  'notnull' => 0, 'visible' => 0],
        'description'   => ['type' => 'text',         'label' => 'Comment',              'enabled' => 1, 'position' => 100, 'notnull' => 0, 'visible' => 0],
        'latitude'      => ['type' => 'double(24,8)', 'label' => 'Latitude',             'enabled' => 1, 'position' => 110, 'notnull' => 0, 'visible' => 0],
        'longitude'     => ['type' => 'double(24,8)', 'label' => 'Longitude',            'enabled' => 1, 'position' => 120, 'notnull' => 0, 'visible' => 0],
        'fk_firepermit' => ['type' => 'integer',      'label' => 'FirePermit',           'enabled' => 1, 'position' => 140, 'notnull' => 1, 'visible' => 0, 'index' => 1],
        'fk_user_creat' => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'position' => 150, 'notnull' => 0, 'visible' => 0, 'foreignkey' => 'user.rowid'],
        'fk_user_modif' => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif',  'enabled' => 1, 'position' => 160, 'notnull' => 0, 'visible' => 0, 'foreignkey' => 'user.rowid'],
    ];

    /**
     * @var int|null Technical id
     */
    public $rowid;

    public $entity;
    public $date_creation;
    public $tms;
    public $status;

    /**
     * @var int|null Order of the round after the end of the work, from 1
     */
    public $position;

    /**
     * @var int|null Delay of the round after the end of the work, in minutes
     */
    public $delay_minutes;

    /**
     * @var int|string|null Time the round is expected at (timestamp)
     */
    public $date_planned;

    /**
     * @var int|string|null Time the round was recorded at (timestamp), empty while planned
     */
    public $date_done;

    /**
     * @var string|null Person who walked the round
     */
    public $watcher_name;

    /**
     * @var string|null What the watcher noticed
     */
    public $description;

    /**
     * @var float|null Latitude of the phone when the round was recorded
     */
    public $latitude;

    /**
     * @var float|null Longitude of the phone when the round was recorded
     */
    public $longitude;

    /**
     * @var int|null Fire permit the round belongs to
     */
    public $fk_firepermit;

    public $fk_user_creat;
    public $fk_user_modif;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db, $this->module, $this->element);
    }

    /**
     * Fetch the rounds of a fire permit, in the order they have to be done.
     *
     * @param  int               $firePermitId Fire permit id
     * @return FirePermitRound[]               Rounds, empty when none is planned
     */
    public function fetchFromFirePermit(int $firePermitId): array
    {
        if ($firePermitId <= 0) {
            return [];
        }

        $records = $this->fetchAll('ASC', 'position', 0, 0, ['customsql' => 't.fk_firepermit = ' . $firePermitId]);

        $rounds = [];
        foreach (is_array($records) ? $records : [] as $record) {
            if ($record instanceof self) {
                $rounds[] = $record;
            }
        }

        return $rounds;
    }

    /**
     * Where the round stands at a given time.
     *
     * @param  int    $now Timestamp the state is computed for
     * @return string      'done', 'late', 'due' or 'upcoming'
     */
    public function getState(int $now): string
    {
        if ((int) $this->status === self::STATUS_DONE) {
            return 'done';
        }

        $plannedAt = (int) $this->date_planned;
        if ($now > $plannedAt + self::LATE_TOLERANCE) {
            return 'late';
        }
        if ($now >= $plannedAt - self::EARLY_TOLERANCE) {
            return 'due';
        }

        return 'upcoming';
    }

    /**
     * A round can be recorded once its time has come, and only once: walking the place at 30 minutes
     * would not stand for the round expected two hours after the work.
     *
     * @param  int  $now Timestamp of the attempt
     * @return bool      True when the watcher may record it now
     */
    public function canBePerformed(int $now): bool
    {
        return in_array($this->getState($now), ['due', 'late'], true);
    }

    /**
     * Record the round as done by the watcher, now.
     *
     * @param  User       $user        User recording it (an empty one from the public interface)
     * @param  string     $watcherName Name of the person who walked the place
     * @param  string     $comment     What the watcher noticed
     * @param  float|null $latitude    Latitude of the phone when recording, null when not shared
     * @param  float|null $longitude   Longitude of the phone when recording, null when not shared
     * @param  string     $signature   Signature of the watcher as an image data URL, empty when none
     * @return int                     >0 if OK, <0 if KO
     */
    public function record(User $user, string $watcherName, string $comment, ?float $latitude, ?float $longitude, string $signature): int
    {
        $this->status       = self::STATUS_DONE;
        $this->date_done    = dol_now();
        $this->watcher_name = dol_trunc(trim($watcherName), 255, 'right', 'UTF-8', 1);
        $this->description  = trim($comment);
        $this->latitude     = $latitude;
        $this->longitude    = $longitude;

        // A round marked done without the signature it requires would be worse than no round at all
        $this->db->begin();
        if ($this->update($user, 1) <= 0 || (dol_strlen($signature) && $this->saveSignature($user, $signature) <= 0)) {
            $this->db->rollback();
            return -1;
        }
        $this->db->commit();

        return 1;
    }

    /**
     * Store the signature of the watcher the Saturne way, with the round as object.
     *
     * No trigger: Saturne would file an agenda event against the round, which has no card of its
     * own, and the round already logs its own event on the fire permit.
     *
     * @param  User   $user      User recording the round (an empty one from the public interface)
     * @param  string $signature Signature as an image data URL
     * @return int               >0 if OK, <0 if KO
     */
    public function saveSignature(User $user, string $signature): int
    {
        require_once DOL_DOCUMENT_ROOT . '/core/lib/ticket.lib.php';
        require_once __DIR__ . '/../../saturne/class/saturnesignature.class.php';

        $signatory = new SaturneSignature($this->db, $this->module, $this->element);

        $signatory->entity         = $this->entity;
        // Typed string in Saturne and filled for every signatory: a row without it cannot be fetched back
        $signatory->signature_url  = generate_random_id();
        $signatory->status         = SaturneSignature::STATUS_SIGNED;
        $signatory->role           = self::SIGNATORY_ROLE;
        $signatory->lastname       = $this->watcher_name;
        $signatory->signature      = $signature;
        $signatory->signature_date = $this->date_done;
        $signatory->attendance     = SaturneSignature::ATTENDANCE_PRESENT;
        $signatory->module_name    = $this->module;
        $signatory->object_type    = $this->element;
        $signatory->fk_object      = $this->id;
        // Recorded from the application: the signatory is the user; from the public page, only the typed name is known
        $signatory->element_type   = ($user->id > 0) ? 'user' : '';
        $signatory->element_id     = ($user->id > 0) ? $user->id : 0;
        if ($this->latitude !== null && $this->longitude !== null) {
            $signatory->signature_location = $this->latitude . ', ' . $this->longitude;
        }

        $result = $signatory->create($user, 1);
        if ($result <= 0) {
            $this->error  = $signatory->error;
            $this->errors = $signatory->errors;
        }

        return $result;
    }

    /**
     * Signature of the watcher who recorded the round.
     *
     * @return string Image data URL, empty when the round was recorded without one
     */
    public function getSignature(): string
    {
        require_once __DIR__ . '/../../saturne/class/saturnesignature.class.php';

        $signatory   = new SaturneSignature($this->db, $this->module, $this->element);
        $signatories = $signatory->fetchSignatory(self::SIGNATORY_ROLE, $this->id, $this->element);
        if (!is_array($signatories)) {
            return '';
        }

        foreach ($signatories as $signatoryItem) {
            if ((int) $signatoryItem->status === SaturneSignature::STATUS_SIGNED && dol_strlen((string) $signatoryItem->signature)) {
                return (string) $signatoryItem->signature;
            }
        }

        return '';
    }

    /**
     * Relative directory, inside the module output directory, of the photos taken during the round.
     *
     * @param  string $firePermitRef Reference of the fire permit
     * @return string                Directory relative to the module output directory
     */
    public function getPhotoSubDir(string $firePermitRef): string
    {
        return 'firepermit/' . dol_sanitizeFileName($firePermitRef) . '/rounds/' . ((int) $this->id);
    }
}
