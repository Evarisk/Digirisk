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

/**
 * \file    core/modules/digiriskdolibarr/mod_objects_custom.php
 * \ingroup digiriskdolibarr
 * \brief   File that contains the numbering module rules for all the custom objects
 */

// Load Saturne libraries
require_once __DIR__ . '/../../../../saturne/core/modules/saturne/modules_saturne.php';

/**
 * Class of file that contains the numbering module rules for all the custom objects
 */
class mod_objects_custom extends CustomModeleNumRefSaturne
{
    /**
     * @var string Models names
     */
    public string $name = 'Greip';

    public function __construct()
    {
        $modelsNames = ['Peggy', 'Siarnaq', 'Greip', 'Mundilfari', 'Gridr', 'Gerd', 'Gunnlod', 'Calypso', 'Fornjot', 'Bestla', 'Angrboda', 'Thiazzi', 'Eggther', 'Geirrod', 'Hati', 'Curtiss', 'Wright', 'Richthofen', 'Bebhionn', 'Bleriot', 'Earhart', 'Sirius', 'Hinkler', 'Alvaldi', 'Canopus', 'Tarqeq', 'Jarnsaxa', 'Greip'];
        $modelsConfs = ['ACCIDENTINVESTIGATION_PEGGY', 'ACCIDENTINVESTIGATIONDOCUMENT_SIARNAQ', 'FIREPERMITDOCUMENT_GREIP', 'GROUPMENTDOCUMENT_MUNDILFARI', 'INFORMATIONSSHARING_GRIDR', 'LEGALDISPLAY_GERD', 'LISTINGRISKSACTION_GUNNLOD', 'LISTINGRISKSDOCUMENT_CALYPSO', 'LISTINGRISKSPHOTO_FORNJOT', 'PREVENTIONPLANDOCUMENT_BESTLA', 'PROJECTDOCUMENT_ANGRBODA', 'REGISTERDOCUMENT_THIAZZI', 'RISKASSESSMENTDOCUMENT_EGGTHER', 'TICKETDOCUMENT_GEIRROD', 'WORKUNITDOCUMENT_HATI', 'ACCIDENT_CURTISS', 'ACCIDENTLESION_WRIGHT', 'ACCIDENTWORKSTOP_RICHTHOFEN', 'EVALUATOR_BEBHIONN', 'FIREPERMIT_BLERIOT', 'FIREPERMITDET_EARHART', 'GROUPMENT_SIRIUS', 'PREVENTIONPLAN_HINKLER', 'PREVENTIONPLANDET_ALVALDI', 'WORKUNIT_CANOPUS', 'RISK_TARQEQ', 'RISKASSESSMENT_JARNSAXA', 'RISKSIGN_GREIP'];

        foreach ($modelsConfs as $conf) {
            $refMod = getDolGlobalString('digiriskdolibarr_' . $conf . '_addon');
            if (dol_strlen($refMod)) {
                $refModSplitted = preg_split('/\{/', $refMod);
                if (is_array($refModSplitted) && !empty($refModSplitted)) {
                    $suffix       = preg_replace('/}/', '', $refModSplitted[1]);
                    $this->prefix = $refModSplitted[0];
                    $this->suffix = $suffix;
                }
            }
        }
    }
}
