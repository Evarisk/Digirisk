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
foreach ($labourDoctorTime as $value) {
    $labourDoctorTimeTotal++;
    if (dol_strlen($value) > 0) {
        $labourDoctorTimeCheck++;
    }
}
foreach ($detectiveWork as $value) {
    $detectiveWorkTotal++;
    if (dol_strlen($value) > 0) {
        $detectiveWorkCheck++;
    }
}
foreach ($labourDetectiveWorkTime as $value) {
    $labourDetectiveWorkTimeTotal++;
    if (dol_strlen($value) > 0) {
        $labourDetectiveWorkTimeCheck++;
    }
}
foreach ($emergencyService as $value) {
    $emergencyServiceTotal++;
    if (dol_strlen($value) > 0) {
        $emergencyServiceCheck++;
    }
}
foreach ($safetyRule as $value) {
    $safetyRuleTotal++;
    if (dol_strlen($value) > 0) {
        $safetyRuleCheck++;
    }
}
foreach ($workingHour as $value) {
    $workingHourTotal++;
    if (dol_strlen($value) > 0) {
        $workingHourCheck++;
    }
}
foreach ($parameters as $value) {
    $parametersTotal++;
    if (dol_strlen($value) > 0) {
        $parametersCheck++;
    }
}

$percentageLabourDoctor      = $LabourDoctorCheck / $labourDoctorTotal * 100;
$percentageLabourDoctorTime  = $labourDoctorTimeCheck / $labourDoctorTimeTotal * 100;
$percentageDetectiveWork     = $detectiveWorkCheck / $detectiveWorkTotal * 100;
$percentageDetectiveWorkTime = $labourDetectiveWorkTimeCheck / $labourDetectiveWorkTimeTotal * 100;
$percentageEmergencyService  = $emergencyServiceCheck / $emergencyServiceTotal * 100;
$percentageSafetyRule        = $safetyRuleCheck / $safetyRuleTotal * 100;
$percentageWorkingHour       = $workingHourCheck / $workingHourTotal * 100;
$percentageParameters        = $parametersCheck / $parametersTotal * 100;
