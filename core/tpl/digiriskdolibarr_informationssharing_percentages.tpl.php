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
 * or see https://www.gnu.org/
 */

// foreach to recolt all the values
foreach ($labourDoctor as $value) {
    $labourDoctorTotal++;
    if (dol_strlen($value) > 0) {
        $LabourDoctorCheck++;
    }
}

foreach ($detectiveWork as $value) {
    $detectiveWorkTotal++;
    if (dol_strlen($value) > 0) {
        $detectiveWorkCheck++;
    }
}

foreach ($harassmentOfficer as $value) {
    $harassmentOfficerTotal++;
    if (dol_strlen($value) > 0) {
        $harassmentOfficerCheck++;
    }
}

foreach ($deleguePersonnel as $value) {
    $deleguePersonnelTotal++;
    if (dol_strlen($value) > 0) {
        $deleguePersonnelCheck++;
    }
}

foreach ($membreComitee as $value) {
    $membreComiteeTotal++;
    if (dol_strlen($value) > 0) {
        $membreComiteeCheck++;
    }
}

// calculs for the percentage of the gauge
$percentageLabourDoctor = $LabourDoctorCheck / $labourDoctorTotal * 100;
$percentageDetectiveWork = $detectiveWorkCheck / $detectiveWorkTotal * 100;
$percentageHarassmentOfficer = $harassmentOfficerCheck / $harassmentOfficerTotal * 100;
$percentageDeleguePersonnel = $deleguePersonnelCheck / $deleguePersonnelTotal * 100;
$percentageMembreComitee = $membreComiteeCheck / $membreComiteeTotal * 100;
