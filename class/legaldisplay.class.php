<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2019-2020 Eoxia <dev@eoxia.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/custom/digiriskdolibarr/class/legaldisplay.class.php
 *  \ingroup    digiriskdolibarr
 *  \brief      File for legal display class
 */

require_once DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php";
//require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
//require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");


/**
 *	Class to manage legal display objects
 */
class Legaldisplay extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'legaldisplay';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'legaldisplay';

	public $id;

	public $ref;
	public $ref_ext;
	public $entity;
	public $date_creation;
	public $date_debut;
	public $date_fin;
	public $fk_soc_labour_doctor;
	public $fk_soc_labour_inspector;
	public $fk_soc_samu;
	public $fk_soc_police;
	public $fk_soc_urgency;
	public $fk_soc_rights_defender;
	public $fk_soc_antipoison;
	public $fk_soc_responsible_prevent;
	public $tms='';
	public $description;
	public $import_key;
	public $status;
	public $fk_user_creat;
	public $fk_user_modif;
	public $model_pdf;
	public $model_odt;
	public $note_affich;


	/**
	 *  Constructor
	 *
	 *  @param	DoliDb		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *  Create a legal display into database
	 *
	 *  @param	User	$user        User that creates
	 *  @param  int		$notrigger   0=launch triggers after, 1=disable triggers
	 *  @return int      		   	 <0 if KO, Id of created object if OK
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf, $langs;

		$error = 0;

		$now = dol_now();

		// Clean parameters
		$this->description = trim($this->description);
		$this->note_affich = trim($this->note_affich);

		// Check parameters
//		if (empty($this->date_debut) || empty($this->date_fin))
//		{
//			$this->error = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Date'));
//			return -1;
//		}

		// Create legal display
		$fuserid = $this->fk_user_creat;
		if (empty($fuserid)) $fuserid = $user->id;

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."legal_display (";
		$sql .= "ref";
		$sql .= ", entity";
		$sql .= ", date_creation";
		$sql .= ", date_debut";
		$sql .= ", date_fin";
		$sql .= ", fk_soc_labour_doctor";
		$sql .= ", fk_soc_labour_inspector";
		$sql .= ", fk_soc_samu";
		$sql .= ", fk_soc_police";
		$sql .= ", fk_soc_urgency";
		$sql .= ", fk_soc_rights_defender";
		$sql .= ", fk_soc_antipoison";
		$sql .= ", fk_soc_responsible_prevent";
		$sql .= ", description";
		$sql .= ", import_key";
		$sql .= ", status";
		$sql .= ", fk_user_creat";
		$sql .= ", model_pdf";
		$sql .= ", model_odt";
		$sql .= ", note_affich";
		$sql .= ") VALUES (";
		$sql .= " ".(!empty($this->ref) ? "'".$this->db->escape($this->ref)."'" : 'null');
		$sql .= ", ".$conf->entity;
		$sql .= ", '".$this->db->idate($now)."'";
		$sql .= ", '".$this->db->idate($this->date_debut)."'";
		$sql .= ", '".$this->db->idate($this->date_fin)."'";
		$sql .= ", ".(is_numeric($this->fk_soc_labour_doctor) ? $this->fk_soc_labour_doctor : '0');
		$sql .= ", ".(is_numeric($this->fk_soc_labour_inspector) ? $this->fk_soc_labour_inspector : '0');
		$sql .= ", ".(is_numeric($this->fk_soc_samu) ? $this->fk_soc_samu : '0');
		$sql .= ", ".(is_numeric($this->fk_soc_police) ? $this->fk_soc_police : '0');
		$sql .= ", ".(is_numeric($this->fk_soc_urgency) ? $this->fk_soc_urgency : '0');
		$sql .= ", ".(is_numeric($this->fk_soc_rights_defender) ? $this->fk_soc_rights_defender : '0');
		$sql .= ", ".(is_numeric($this->fk_soc_antipoison) ? $this->fk_soc_antipoison : '0');
		$sql .= ", ".(is_numeric($this->fk_soc_responsible_prevent) ? $this->fk_soc_responsible_prevent : '0');
		$sql .= ", '".$this->db->escape($this->description)."'";
		$sql .= ", ".(!empty($this->import_key) ? "'".$this->db->escape($this->import_key)."'" : 'null');
		$sql .= ", ".(is_numeric($this->status) ? $this->status : '0');
		$sql .= ", ".$fuserid;
		$sql .= ", ".(!empty($this->model_pdf) ? "'".$this->db->escape($this->model_pdf)."'" : 'null');
		$sql .= ", ".(!empty($this->model_odt) ? "'".$this->db->escape($this->model_odt)."'" : 'null');
		$sql .= ", ".(!empty($this->note_affich) ? "'".$this->db->escape($this->note_affich)."'" : 'null');
		$sql .= ")";

		dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) { $error++; $this->errors[] = "Error ".$this->db->lasterror(); }

		if (! $error)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."legal_display");

			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				//// Call triggers
				//include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				//$interface=new Interfaces($this->db);
				//$result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
				//if ($result < 0) { $error++; $this->errors=$interface->errors; }
				//// End call triggers
			}
		}

		// Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
				$this->error .= ($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		}
		else
		{
			$this->db->commit();
			return $this->id;
		}
	}


	/**
	 *  Load object in memory from the database
	 *
	 *  @param	int		$id    Id object
	 *  @return int          	<0 if KO, >0 if OK
	 */
	function fetch($id)
	{
		global $langs;
		$sql = "SELECT";
		$sql .= "t.ref";
		$sql .= ", t.entity";
		$sql .= ", t.date_creation";
		$sql .= ", t.date_debut";
		$sql .= ", t.date_fin";
		$sql .= ", t.fk_soc_labour_doctor";
		$sql .= ", t.fk_soc_labour_inspector";
		$sql .= ", t.fk_soc_samu";
		$sql .= ", t.fk_soc_police";
		$sql .= ", t.fk_soc_urgency";
		$sql .= ", t.fk_soc_rights_defender";
		$sql .= ", t.fk_soc_antipoison";
		$sql .= ", t.fk_soc_responsible_prevent";
		$sql .= ", t.description";
		$sql .= ", t.import_key";
		$sql .= ", t.status";
		$sql .= ", t.fk_user_creat";
		$sql .= ", t.model_pdf";
		$sql .= ", t.model_odt";
		$sql .= ", t.note_affich";


		$sql.= " FROM ".MAIN_DB_PREFIX."legal_display as t";
		$sql.= " WHERE t.rowid = ".$id;

		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id    = $obj->rowid;

				$this->ref = $obj->ref;
				$this->ref_ext = $obj->ref_ext;
				$this->entity = $obj->entity;
				$this->date_creation = $this->db->jdate($obj->date_creation);
				$this->tms = $this->db->jdate($obj->tms);
				$this->date_valid = $this->db->jdate($obj->date_valid);
				$this->description = $obj->description;
				$this->import_key = $obj->import_key;
				$this->status = $obj->status;
				$this->fk_user_creat = $obj->fk_user_creat;
				$this->fk_user_modif = $obj->fk_user_modif;
				$this->fk_user_valid = $obj->fk_user_valid;
				$this->model_pdf = $obj->model_pdf;
				$this->model_odt = $obj->model_odt;
				$this->note_affich = $obj->note_affich;


			}
			$this->db->free($resql);

			return 1;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *  Update object into database
	 *
	 *  @param	User	$user        User that modifies
	 *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return int     		   	 <0 if KO, >0 if OK
	 */
	function update($user=0, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		// Clean parameters

		if (isset($this->ref)) $this->ref=trim($this->ref);
		if (isset($this->ref_ext)) $this->ref_ext=trim($this->ref_ext);
		if (isset($this->entity)) $this->entity=trim($this->entity);
		if (isset($this->description)) $this->description=trim($this->description);
		if (isset($this->import_key)) $this->import_key=trim($this->import_key);
		if (isset($this->status)) $this->status=trim($this->status);
		if (isset($this->fk_user_creat)) $this->fk_user_creat=trim($this->fk_user_creat);
		if (isset($this->fk_user_modif)) $this->fk_user_modif=trim($this->fk_user_modif);
		if (isset($this->fk_user_valid)) $this->fk_user_valid=trim($this->fk_user_valid);
		if (isset($this->model_pdf)) $this->model_pdf=trim($this->model_pdf);
		if (isset($this->model_odt)) $this->model_odt=trim($this->model_odt);
		if (isset($this->note_affich)) $this->note_affich=trim($this->note_affich);



		// Check parameters
		// Put here code to add a control on parameters values

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."legal_display SET";

		$sql.= " ref=".(isset($this->ref)?"'".$this->db->escape($this->ref)."'":"null").",";
		$sql.= " ref_ext=".(isset($this->ref_ext)?"'".$this->db->escape($this->ref_ext)."'":"null").",";
		$sql.= " entity=".(isset($this->entity)?$this->entity:"null").",";
		$sql.= " date_creation=".(dol_strlen($this->date_creation)!=0 ? "'".$this->db->idate($this->date_creation)."'" : 'null').",";
		$sql.= " tms=".(dol_strlen($this->tms)!=0 ? "'".$this->db->idate($this->tms)."'" : 'null').",";
		$sql.= " date_valid=".(dol_strlen($this->date_valid)!=0 ? "'".$this->db->idate($this->date_valid)."'" : 'null').",";
		$sql.= " description=".(isset($this->description)?"'".$this->db->escape($this->description)."'":"null").",";
		$sql.= " import_key=".(isset($this->import_key)?$this->import_key:"null").",";
		$sql.= " status=".(isset($this->status)?$this->status:"null").",";
		$sql.= " fk_user_creat=".(isset($this->fk_user_creat)?$this->fk_user_creat:"null").",";
		$sql.= " fk_user_modif=".(isset($this->fk_user_modif)?$this->fk_user_modif:"null").",";
		$sql.= " fk_user_valid=".(isset($this->fk_user_valid)?$this->fk_user_valid:"null").",";
		$sql.= " model_pdf=".(isset($this->model_pdf)?"'".$this->db->escape($this->model_pdf)."'":"null").",";
		$sql.= " model_odt=".(isset($this->model_odt)?"'".$this->db->escape($this->model_odt)."'":"null").",";
		$sql.= " note_affich=".(isset($this->note_affich)?"'".$this->db->escape($this->note_affich)."'":"null")."";


		$sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				//// Call triggers
				//include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				//$interface=new Interfaces($this->db);
				//$result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
				//if ($result < 0) { $error++; $this->errors=$interface->errors; }
				//// End call triggers
			}
		}

		// Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}


	/**
	 *  Delete object in database
	 *
	 *	@param  User	$user        User that deletes
	 *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return	int					 <0 if KO, >0 if OK
	 */
	function delete($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		$this->db->begin();

		if (! $error)
		{
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				//// Call triggers
				//include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				//$interface=new Interfaces($this->db);
				//$result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
				//if ($result < 0) { $error++; $this->errors=$interface->errors; }
				//// End call triggers
			}
		}

		if (! $error)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."legal_display";
			$sql.= " WHERE rowid=".$this->id;

			dol_syslog(get_class($this)."::delete sql=".$sql);
			$resql = $this->db->query($sql);
			if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		}

		// Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}



	/**
	 *	Load an object from its id and create a new one in database
	 *
	 *	@param	int		$fromid     Id of object to clone
	 * 	@return	int					New id of clone
	 */
	function createFromClone($fromid)
	{
		global $user,$langs;

		$error=0;

		$object=new Legaldisplay($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id=0;
		$object->statut=0;

		// Clear fields
		// ...

		// Create clone
		$result=$object->create($user);

		// Other options
		if ($result < 0)
		{
			$this->error=$object->error;
			$error++;
		}

		if (! $error)
		{


		}

		// End
		if (! $error)
		{
			$this->db->commit();
			return $object->id;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Initialise object with example values
	 *	Id must be 0 if object instance is a specimen
	 *
	 *	@return	void
	 */
	function initAsSpecimen()
	{
		$this->id=0;

		$this->ref='';
		$this->ref_ext='';
		$this->entity='';
		$this->date_creation='';
		$this->tms='';
		$this->date_valid='';
		$this->description='';
		$this->import_key='';
		$this->status='';
		$this->fk_user_creat='';
		$this->fk_user_modif='';
		$this->fk_user_valid='';
		$this->model_pdf='';
		$this->model_odt='';
		$this->note_affich='';


	}

}
?>
