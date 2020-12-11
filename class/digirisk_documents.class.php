<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2020 Eoxia <dev@eoxia.com>
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
 *  \file       htdocs/custom/digiriskdolibarr/class/digirisk_documents.class.php
 *  \ingroup    digiriskdolibarr
 *  \brief      File for digirisk documents class
 */

require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";
require_once DOL_DOCUMENT_ROOT.'/custom/digiriskdolibarr/lib/digiriskdolibarr.lib.php';
require_once DOL_DOCUMENT_ROOT."/societe/class/societe.class.php";


/**
 *	Class to manage Digirisk documents objects
 */
class DigiriskDocuments extends CommonObject
{
	//@todo a check si c'est var/public/global ou rien car déja instancié ?
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'digirisk_documents';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'digirisk_documents';

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
	public $type;

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
		'type' =>array('type'=>'varchar(50)', 'label'=>'Type', 'enabled'=>1, 'visible'=>0, 'position'=>160)
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
		$sql .= ", t.ref_ext";
		$sql .= ", t.entity";
		$sql .= ", t.date_creation";
		$sql .= ", t.json";
		$sql .= ", t.import_key";
		$sql .= ", t.status";
		$sql .= ", t.fk_user_creat";
		$sql .= ", t.model_pdf";
		$sql .= ", t.model_odt";
		$sql .= ", t.type";

		$sql.= " FROM ".MAIN_DB_PREFIX."digirisk_documents"." as t";
		$sql.= " WHERE t.rowid  = ".$id . " AND t.entity IN (".getEntity('digiriskdolibarr').")";
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
				$this->ref_ext = $obj->ref_ext;
				$this->entity = $obj->entity;
				$this->date_creation = $this->db->jdate($obj->date_creation);
				$this->json = $obj->json;
				$this->import_key = $obj->import_key;
				$this->status = $obj->status;
				$this->fk_user_creat = $obj->fk_user_creat;
				$this->fk_user_modif = $obj->fk_user_modif;
				$this->model_pdf = $obj->model_pdf;
				$this->model_odt = $obj->model_odt;
				$this->type = $obj->type;

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
	 *  Generate documents into database
	 *
	 * @param	string	$modele       Model name (ODT/PDF)
	 * @param	string	$outputlangs  Language
	 * @param	int		$hidedetails  hide details in documents
	 * @param	int		$hidedesc     hide description in documents
	 * @param	int		$hideref      hide ref in documents
	 * @param	array   $moreparams   add parameters
	 * @return  int          	      <0 if KO, >0 if OK
	 */
	function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
    {
        global $conf;

	    if (!dol_strlen($modele)) {
	    	//@todo check $conf->global->EXPENSEREPORT_ADDON_PDF
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
