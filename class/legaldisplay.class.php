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
require_once DOL_DOCUMENT_ROOT.'/custom/digiriskdolibarr/lib/digiriskdolibarr.lib.php';
require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");


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
	public $json = array();
	public $import_key;
	public $status;
	public $fk_user_creat;
	public $fk_user_modif;
	public $model_pdf;
	public $model_odt;
	public $note_affich;

	public $fields = array(
		'rowid' =>array('type'=>'integer', 'label'=>'ID', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>10),
		'ref' =>array('type'=>'varchar(50)', 'label'=>'Ref', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'showoncombobox'=>1, 'position'=>15),
		'ref_ext' =>array('type'=>'integer', 'label'=>'Ref ext', 'enabled'=>1, 'visible'=>-1, 'position'=>20),
		'entity' =>array('type'=>'integer', 'label'=>'Entity', 'default'=>1, 'enabled'=>1, 'visible'=>-2, 'notnull'=>1, 'position'=>25),
		'date_creation' =>array('type'=>'datetime', 'label'=>'Date creation', 'enabled'=>1, 'visible'=>-1, 'position'=>30),
		'tms' =>array('type'=>'timestamp', 'label'=>'Tms', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>45),
		'json' =>array('type'=>'text', 'label'=>'Description', 'enabled'=>1, 'visible'=>0, 'position'=>50),
		'import_key' =>array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>1, 'visible'=>-1, 'position'=>130),
		'status' =>array('type'=>'smallint', 'label'=>'Status', 'enabled'=>1, 'visible'=>-1, 'position'=>135),
		'fk_user_creat' =>array('type'=>'integer', 'label'=>'Fk user creat', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>140),
		'fk_user_modif' =>array('type'=>'integer', 'label'=>'Fk user modif', 'enabled'=>1, 'visible'=>-1, 'position'=>145),
		'model_pdf' =>array('type'=>'varchar(255)', 'label'=>'Model pdf', 'enabled'=>1, 'visible'=>0, 'position'=>150),
		'model_odt' =>array('type'=>'varchar(255)', 'label'=>'Model odt', 'enabled'=>1, 'visible'=>0, 'position'=>155),
		'note_affich' =>array('type'=>'varchar(255)', 'label'=>'Note affich', 'enabled'=>1, 'visible'=>0, 'position'=>160),
	);

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

		// REMPLIR JSON

		$labour_doctor = new Contact($this->db);
		$result = $labour_doctor->fetch($this->fk_socpeople_labour_doctor);

		if ($result < 0) dol_print_error('', $labour_doctor->error);
		elseif ($result > 0) {
			$this->json['LegalDisplay']['occupational_health_service']['name'] = $labour_doctor->firstname . " " . $labour_doctor->lastname;
			$this->json['LegalDisplay']['occupational_health_service']['address'] = $labour_doctor->address;
			$this->json['LegalDisplay']['occupational_health_service']['zip'] = $labour_doctor->zip;
			$this->json['LegalDisplay']['occupational_health_service']['town'] = $labour_doctor->town;
			$this->json['LegalDisplay']['occupational_health_service']['phone'] = $labour_doctor->phone_pro;
		}

		$labour_inspector = new Contact($this->db);
		$result = $labour_inspector->fetch($this->fk_socpeople_labour_inspector);
		if ($result < 0) dol_print_error('', $labour_inspector->error);
		elseif ($result > 0) {
			$this->json['LegalDisplay']['detective_work']['name'] = $labour_inspector->firstname . " " . $labour_inspector->lastname;
			$this->json['LegalDisplay']['detective_work']['address'] = $labour_inspector->address;
			$this->json['LegalDisplay']['detective_work']['zip'] = $labour_inspector->zip;
			$this->json['LegalDisplay']['detective_work']['town'] = $labour_inspector->town;
			$this->json['LegalDisplay']['detective_work']['phone'] = $labour_inspector->phone_pro;
		}

		$samu = new Societe($this->db);
		$result = $samu->fetch($this->fk_soc_samu);
		if ($result < 0) dol_print_error('', $samu->error);
		elseif ($result > 0) {
			$this->json['LegalDisplay']['emergency_service']['samu']=$samu->phone;
		}

		$police = new Societe($this->db);
		$result = $police->fetch($this->fk_soc_police);
		if ($result < 0) dol_print_error('', $police->error);
		elseif ($result > 0) {
			$this->json['LegalDisplay']['emergency_service']['police']=$police->phone;
		}

		$pompiers = new Societe($this->db);
		$result = $pompiers->fetch($this->fk_soc_pompiers);
		if ($result < 0) dol_print_error('', $pompiers->error);
		elseif ($result > 0) {
			$this->json['LegalDisplay']['emergency_service']['pompier']=$pompiers->phone;
		}

		$urgency = new Societe($this->db);
		$result = $urgency->fetch($this->fk_soc_urgency);
		if ($result < 0) dol_print_error('', $urgency->error);
		elseif ($result > 0) {
			$this->json['LegalDisplay']['emergency_service']['emergency']=$urgency->phone;
		}

		$rights_defender = new Societe($this->db);
		$result = $rights_defender->fetch($this->fk_soc_rights_defender);
		if ($result < 0) dol_print_error('', $rights_defender->error);
		elseif ($result > 0) {
			$this->json['LegalDisplay']['emergency_service']['right_defender']=$rights_defender->phone;
		}

		$antipoison = new Societe($this->db);
		$result = $antipoison->fetch($this->fk_soc_antipoison);
		if ($result < 0) dol_print_error('', $antipoison->error);
		elseif ($result > 0) {
			$this->json['LegalDisplay']['emergency_service']['poison_control_center']=$antipoison->phone;
		}

		$responsible_prevent = new Societe($this->db);
		$result = $responsible_prevent->fetch($this->fk_soc_responsible_prevent);
		if ($result < 0) dol_print_error('', $responsible_prevent->error);
		elseif ($result > 0) {
			$this->json['LegalDisplay']['safety_rule']['responsible_for_preventing']=$responsible_prevent->name;
			$this->json['LegalDisplay']['safety_rule']['phone']=$responsible_prevent->phone;
		}

		$const = digirisk_dolibarr_fetch_const($this->db);

		// WORKING HOURS a RAJOUTER

		$this->json['LegalDisplay']['safety_rule']['location_of_detailed_instruction']=$conf->global->DIGIRISK_LOCATION_OF_DETAILED_INSTRUCTION;
		$this->json['LegalDisplay']['derogation_schedule']['permanent']=$conf->global->DIGIRISK_DEROGATION_SCHEDULE_PERMANENT;
		$this->json['LegalDisplay']['derogation_schedule']['occasional']=$conf->global->DIGIRISK_DEROGATION_SCHEDULE_OCCASIONAL;
		$this->json['LegalDisplay']['collective_agreement']['title_of_the_applicable_collective_agreement']=$conf->global->DIGIRISK_COLLECTIVE_AGREEMENT_TITLE;
		$this->json['LegalDisplay']['collective_agreement']['location_and_access_terms_of_the_agreement']=$conf->global->DIGIRISK_COLLECTIVE_AGREEMENT_LOCATION;
		$this->json['LegalDisplay']['DUER']['how_access_to_duer']=$conf->global->DIGIRISK_DUER_LOCATION;
		$this->json['LegalDisplay']['rules']['location']=$conf->global->DIGIRISK_RULES_LOCATION;
		$this->json['LegalDisplay']['participation_agreement']['information_procedures']=$conf->global->DIGIRISK_PARTICIPATION_AGREEMENT_INFORMATION_PROCEDURE;

		// Create legal display
		$fuserid = $this->fk_user_creat;
		if (empty($fuserid)) $fuserid = $user->id;

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."digirisk_legaldisplay (";
		$sql .= "ref";
		$sql .= ", entity";
		$sql .= ", date_creation";
		$sql .= ", json";
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
		$sql .= ", '".json_encode($this->json, JSON_UNESCAPED_UNICODE)."'";
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
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."digirisk_legaldisplay");
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
		$sql = "SELECT ";
		$sql .= "t.ref";
		$sql .= ", t.entity";
		$sql .= ", t.date_creation";
		$sql .= ", t.json";
		$sql .= ", t.import_key";
		$sql .= ", t.status";
		$sql .= ", t.fk_user_creat";
		$sql .= ", t.model_pdf";
		$sql .= ", t.model_odt";
		$sql .= ", t.note_affich";


		$sql.= " FROM ".MAIN_DB_PREFIX."digirisk_legaldisplay as t";
		$sql.= " WHERE t.rowid  = ".$id . " AND t.entity IN (".getEntity('digiriskdolibarr').")";@
		//NOTE DEV : SELECT PRIMORDIAL POUR GERER LE MULTICOMPANY

		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id = $id;
				$this->ref = $obj->ref;
				$this->entity = $obj->entity;
				$this->date_creation = $this->db->jdate($obj->date_creation);
				$this->json = $obj->json;
				$this->import_key = $obj->import_key;
				$this->status = $obj->status;
				$this->fk_user_creat = $obj->fk_user_creat;
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

		$error = 0;

		// FONCTION NON UTILISEE DANS LE FONCTIONNEMENT ACTUEL //

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
		$sql = "UPDATE ".MAIN_DB_PREFIX."digirisk_legaldisplay SET";

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
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."digirisk_legaldisplay";
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
	function getNomUrl($withpicto = 0, $max = 0, $short = 0, $moretitle = '', $notooltip = 0, $save_lastsearch_value = -1)
    {
		global $langs, $conf;

        $result = '';

        $url = DOL_URL_ROOT.'/custom/digiriskdolibarr/view/legaldisplay_card.php?id='.$this->id;

        if ($short) return $url;
        $label = '<u>'.$langs->trans("ShowLegalDisplay").'</u>';
        if (!empty($this->ref))
            $label .= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
        if (!empty($this->total_ht))
            $label .= '<br><b>'.$langs->trans('AmountHT').':</b> '.price($this->total_ht, 0, $langs, 0, -1, -1, $conf->currency);
        if (!empty($this->total_tva))
            $label .= '<br><b>'.$langs->trans('VAT').':</b> '.price($this->total_tva, 0, $langs, 0, -1, -1, $conf->currency);
        if (!empty($this->total_ttc))
            $label .= '<br><b>'.$langs->trans('AmountTTC').':</b> '.price($this->total_ttc, 0, $langs, 0, -1, -1, $conf->currency);
        if ($moretitle) $label .= ' - '.$moretitle;

        //if ($option != 'nolink')
        //{
        // Add param to save lastsearch_values or not
        	$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
        	if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
        	if ($add_save_lastsearch_values) $url .= '&save_lastsearch_values=1';
        //}

        $ref = $this->ref;
        if (empty($ref)) $ref = $this->id;

        $linkclose = '';
        if (empty($notooltip))
        {
            if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
            {
                $label = $langs->trans("ShowLegalDisplay");
                $linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose .= ' class="classfortooltip"';
        }

        $linkstart = '<a href="'.$url.'"';
        $linkstart .= $linkclose.'>';
        $linkend = '</a>';

        $result .= $linkstart;
        if ($withpicto) $result .= img_object(($notooltip ? '' : $label), $this->picto, ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
        if ($withpicto != 2) $result .= ($max ?dol_trunc($ref, $max) : $ref);
        $result .= $linkend;

        return $result;
	}

	function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
    {
        global $conf, $langs;
        $langs->load("trips");

	    if (!dol_strlen($modele)) {
		    $modele = 'einstein';

		    if ($this->modelpdf) {
			    $modele = $this->modelpdf;
		    } elseif (!empty($conf->global->EXPENSEREPORT_ADDON_PDF)) {
			    $modele = $conf->global->EXPENSEREPORT_ADDON_PDF;
		    }
	    }

        $modelpath = "/custom/digiriskdolibarr/core/modules/digiriskdolibarr/doc/";

        return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
    }
}

?>
