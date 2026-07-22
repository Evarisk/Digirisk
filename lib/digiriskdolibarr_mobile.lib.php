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
 * Available required-certification options for the mobile prevention plan interface (code => label).
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
