<?php
/* Copyright (C) 2021-2024 EVARISK <technique@evarisk.com>
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
 * \file        class/digiriskdocuments.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a CRUD class file for DigiriskDocuments (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

// Load Saturne libraries.
require_once __DIR__ . '/../../saturne/class/saturnedocuments.class.php';

/**
 * Class for DigiriskDocuments
 */
class DigiriskDocuments extends SaturneDocuments
{
	/**
	 * @var string Module name
	 */
	public $module = 'digiriskdolibarr';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management
	 */
	public $table_element = 'saturne_object_documents';

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db, $module, $element)
	{
		parent::__construct($db, $module, $element);
	}

    /**
     * Create object into database
     *
     * @param  User        $user         User that creates
     * @param  int<0,1>    $noTrigger    0 = launch triggers after, 1 = disable triggers
     * @param  object|null $parentObject Current object
     * @return int<-1,max>               Return integer 0 < if KO, ID of created object if OK
     */
    public function create(User $user, int $noTrigger = 0, ?object $parentObject = null): int
    {
        $this->DigiriskFillJSON();
        return parent::create($user, $noTrigger, $parentObject);
    }

	/**
	 * Function for JSON filling before saving in database
	 *
	 */
	public function DigiriskFillJSON() {
        switch ($this->element) {
			case "legaldisplay":
				$this->json = $this->LegalDisplayFillJSON();
				break;
			case "informationssharing":
				$this->json = $this->InformationsSharingFillJSON();
				break;
            case "auditreportdocument":
                $riskAssessmentDocument = new RiskAssessmentDocument($this->db);
                $this->json = $riskAssessmentDocument->RiskAssessmentDocumentFillJSON();
                break;
			case "riskassessmentdocument":
				$this->json = $this->RiskAssessmentDocumentFillJSON();
				break;
			case "preventionplandocument":
				$this->json = $this->PreventionPlanDocumentFillJSON();
				break;
			case "firepermitdocument":
				$this->json = $this->FirePermitDocumentFillJSON();
				break;
		}
	}

    /**
     * Write information of trigger description
     *
     * @return string Description to display in actioncomm->note_private
     */
    public function getTriggerDescription(): string
    {
        global $langs;

        $className = $this->parent_type;
        if (file_exists( __DIR__ . '/digiriskelement/' . $className .'.class.php')) {
            require_once __DIR__ . '/digiriskelement/' . $className .'.class.php';
        } else if (file_exists( __DIR__ . '/digiriskdolibarrdocuments/' . $className .'.class.php')) {
            require_once __DIR__ . '/digiriskdolibarrdocuments/' . $className .'.class.php';
        }  else {
            require_once __DIR__ . '/' . $className .'.class.php';
        }

        $parentElement = new $className($this->db);
        $parentElement->fetch($this->parent_id);

        $ret  = parent::getTriggerDescription();

        $ret .= $langs->transnoentities('ElementType') . ' : ' . $this->parent_type . '<br>';
        $ret .= $langs->transnoentities('ParentElement') . ' : ' . $parentElement->ref . ' ' . $parentElement->label . '<br>';
        $ret .= $langs->transnoentities('LastMainDoc') . ' : ' . $this->last_main_doc . '<br>';

        return $ret;
    }
}
