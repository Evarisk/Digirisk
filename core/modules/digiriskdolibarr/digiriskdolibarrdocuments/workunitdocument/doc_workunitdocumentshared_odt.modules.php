<?php
/* Copyright (C) 2021-2025 EVARISK <technique@evarisk.com>
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
 * \file    core/modules/digiriskdolibarr/digiriskdolibarrdocuments/workunitdocument/doc_workunitdocumentshared_odt.modules.php
 * \ingroup digiriskdolibarr
 * \brief   File of class to build ODT workunit document listing shared (multi-entity) risks
 */

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/doc_workunitdocumentinherited_odt.modules.php';

/**
 * Class to build documents using ODF templates generator
 *
 * Same generation logic as doc_workunitdocumentinherited_odt: the shared/inherited distinction is purely the
 * ODT template (this model is bound to a template containing only the sharedRisks1..4 segment set).
 */
class doc_workunitdocumentshared_odt extends doc_workunitdocumentinherited_odt
{
}
