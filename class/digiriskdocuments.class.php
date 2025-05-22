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
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db, $module, $element)
	{
		parent::__construct($db, $module, $element);
	}

    /**
     * Create object into database
     *
     * @param  User $user      User that creates
     * @param  bool $notrigger false = launch triggers after, true = disable triggers
     * @return int             0 < if KO, ID of created object if OK
     */
    public function create(User $user, bool $notrigger = false, object $parentObject = null): int
    {
        $this->DigiriskFillJSON();
        return parent::create($user, $notrigger, $parentObject);
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
	 *	Load the info information of the object
	 *
	 *	@param  int		$id       ID of object
	 *	@return	int
	 */
	public function info($id)
	{
		$fieldlist = $this->getFieldList();

		if (empty($fieldlist)) return 0;

		$sql = 'SELECT '.$fieldlist;
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql .= ' WHERE t.rowid = '.$id;
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
//				if ($obj->fk_user_author)
//				{
//					$cuser = new User($this->db);
//					$cuser->fetch($obj->fk_user_author);
//					$this->user_creation = $cuser;
//				}
//
//				if ($obj->fk_user_valid)
//				{
//					$vuser = new User($this->db);
//					$vuser->fetch($obj->fk_user_valid);
//					$this->user_validation = $vuser;
//				}
//
//				if ($obj->fk_user_cloture)
//				{
//					$cluser = new User($this->db);
//					$cluser->fetch($obj->fk_user_cloture);
//					$this->user_cloture = $cluser;
//				}

				$this->date_creation = $this->db->jdate($obj->date_creation);
				//$this->date_modification = $this->db->jdate($obj->datem);
				//$this->date_validation   = $this->db->jdate($obj->datev);
			}

			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
	}

    /**
     * Write information of trigger description
     *
     * @param  Object $object Object calling the trigger
     * @return string         Description to display in actioncomm->note_private
     */
    public function getTriggerDescription(SaturneObject $object): string
    {
        global $langs;

        $className = $object->parent_type;
        if (file_exists( __DIR__ . '/digiriskelement/' . $className .'.class.php')) {
            require_once __DIR__ . '/digiriskelement/' . $className .'.class.php';
        } else if (file_exists( __DIR__ . '/digiriskdolibarrdocuments/' . $className .'.class.php')) {
            require_once __DIR__ . '/digiriskdolibarrdocuments/' . $className .'.class.php';
        }  else {
            require_once __DIR__ . '/' . $className .'.class.php';
        }

        $parentElement = new $className($this->db);
        $parentElement->fetch($object->parent_id);

        $ret  = parent::getTriggerDescription($object);

        $ret .= $langs->transnoentities('ElementType') . ' : ' . $object->parent_type . '<br>';
        $ret .= $langs->transnoentities('ParentElement') . ' : ' . $parentElement->ref . ' ' . $parentElement->label . '<br>';
        $ret .= $langs->transnoentities('LastMainDoc') . ' : ' . $object->last_main_doc . '<br>';

        return $ret;
    }
}
