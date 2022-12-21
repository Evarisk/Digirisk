<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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

/**
 * Class for DigiriskDocuments
 */
class DigiriskDocuments extends CommonObject
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string[] Array of error strings
	 */
	public $errors = array();

	/**
	 * @var int The object identifier
	 */
	public $id;

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'digiriskdocuments';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'digiriskdolibarr_digiriskdocuments';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for digiriskdocuments. Must be the part after the 'object_' into object_digiriskdocuments.png
	 */
	public $picto = 'digiriskdocuments@digiriskdolibarr';

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid'         => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'comment'=>"Id"),
		'ref'           => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>4, 'noteditable'=>'1', 'default'=>'(PROV)', 'index'=>1, 'searchall'=>1, 'showoncombobox'=>'1', 'comment'=>"Reference of object"),
		'ref_ext'       => array('type'=>'varchar(128)', 'label'=>'RefExt', 'enabled'=>'1', 'position'=>20, 'notnull'=>0, 'visible'=>0,),
		'entity'        => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>0,),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>40, 'notnull'=>1, 'visible'=>0,),
		'tms'           => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>0,),
		'import_key'    => array('type'=>'integer', 'label'=>'ImportKey', 'enabled'=>'1', 'position'=>60, 'notnull'=>1, 'visible'=>0,),
		'status'        => array('type'=>'smallint', 'label'=>'Status', 'enabled'=>'1', 'position'=>70, 'notnull'=>0, 'visible'=>0,),
		'type'          => array('type'=>'varchar(128)', 'label'=>'Type', 'enabled'=>'1', 'position'=>80, 'notnull'=>0, 'visible'=>0,),
		'json'          => array('type'=>'text', 'label'=>'JSON', 'enabled'=>'1', 'position'=>90, 'notnull'=>0, 'visible'=>0,),
		'model_pdf'     => array('type'=>'varchar(255)', 'label'=>'Model pdf', 'enabled'=>'1', 'position'=>100, 'notnull'=>-1, 'visible'=>0,),
		'model_odt'     => array('type'=>'varchar(255)', 'label'=>'Model ODT', 'enabled'=>'1', 'position'=>110, 'notnull'=>0, 'visible'=>0,),
		'last_main_doc' => array('type'=>'varchar(128)', 'label'=>'LastMainDoc', 'enabled'=>'1', 'position'=>120, 'notnull'=>0, 'visible'=>0,),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>130, 'notnull'=>1, 'visible'=>0, 'foreignkey'=>'user.rowid',),
		'parent_type'   => array('type'=>'varchar(255)', 'label'=>'Parent_type', 'enabled'=>'1', 'position'=>140, 'notnull'=>1, 'visible'=>0, 'default'=>1,),
		'parent_id'     => array('type'=>'integer', 'label'=>'Parent_id', 'enabled'=>'1', 'position'=>150, 'notnull'=>1, 'visible'=>0, 'default'=>1,),
	);

	public $rowid;
	public $ref;
	public $ref_ext;
	public $entity;
	public $date_creation;
	public $tms;
	public $import_key;
	public $status;
	public $type;
	public $json;
	public $model_pdf;
	public $model_odt;
	public $last_main_doc;
	public $fk_user_creat;
	public $parent_type;
	public $parent_id;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible'] = 0;
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled'] = 0;

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val)
		{
			if (isset($val['enabled']) && empty($val['enabled']))
			{
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs))
		{
			foreach ($this->fields as $key => $val)
			{
				if (is_array($val['arrayofkeyval']))
				{
					foreach ($val['arrayofkeyval'] as $key2 => $val2)
					{
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             0 < if KO, ID of created object if OK
	 */
	public function create(User $user, $notrigger = false, $parentObject = 0)
	{
		global $conf;

		$now = dol_now();

		$this->ref_ext       = 'digirisk_' . $this->ref;
		$this->date_creation = $this->db->idate($now);
		$this->tms           = $now;
		$this->import_key    = "";
		$this->status        = 1;
		$this->type          = $this->element;

		$this->fk_user_creat = $user->id ?: 1;

		if ($parentObject->id > 0) {
			$this->parent_id     = $parentObject->id;
			$this->parent_type   = $parentObject->element_type ?: $parentObject->element;
		} else {
			$this->parent_id    = $conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD;
			$this->parent_type  = 'digiriskstandard';
		}

		$this->DigiriskFillJSON($this);
		$this->element = $this->element . '@digiriskdolibarr';
		return $this->createCommon($user, $notrigger);
	}
	/**
	 * Function for JSON filling before saving in database
	 *
	 * @param $object
	 */
	public function DigiriskFillJSON($object) {
		switch ($object->element) {
			case "legaldisplay":
				$this->json = $this->LegalDisplayFillJSON($object);
				break;
			case "informationssharing":
				$this->json = $this->InformationsSharingFillJSON($object);
				break;
			case "riskassessmentdocument":
				$this->json = $this->RiskAssessmentDocumentFillJSON($object);
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
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         0 < if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		return $this->fetchCommon($id, $ref);
	}

	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param string $sortorder Sort Order
	 * @param string $sortfield Sort field
	 * @param int $limit limit
	 * @param int $offset Offset
	 * @param array $filter Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
	 * @param string $filtermode Filter mode (AND/OR)
	 * @return array|int                 int <0 if KO, array of pages if OK
	 * @throws Exception
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = 'SELECT ';
		$sql .= $this->getFieldList();
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql .= ' WHERE t.entity IN ('.getEntity($this->table_element).')';
		else $sql .= ' WHERE 1 = 1';
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key.'='.$value;
				}
				elseif (strpos($key, 'date') !== false) {
					$sqlwhere[] = $key.' = \''.$this->db->idate($value).'\'';
				}
				elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				}
				else {
					$sqlwhere[] = $key.' LIKE \'%'.$this->db->escape($value).'%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND ('.implode(' '.$filtermode.' ', $sqlwhere).')';
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= ' '.$this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num))
			{
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             0 < if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             0 < if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		return $this->deleteCommon($user, $notrigger);
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
	 * Initialise object with example values
	 * ID must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		$this->initAsSpecimenCommon();
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 *  @param	    string		$modele			       Force template to use ('' to not force)
	 *  @param		Translate	$outputlangs	       Objet langs use for translation
	 *  @param      int			$hidedetails           Hide details of lines
	 *  @param      int			$hidedesc              Hide description
	 *  @param      int			$hideref               Hide ref
	 *  @param      null|array  $moreparams            Array to provide more information
	 *  @param      bool        $preventrecursivecall  Prevent recursive on commonGenerateDocument hook
	 *  @return     int         				       0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null, $preventrecursivecall = false)
	{
		global $langs;
		$langs->load("digiriskdolibarr@digiriskdolibarr");

		$modelpath = "custom/digiriskdolibarr/core/modules/digiriskdolibarr/digiriskdocuments/".$this->element."/";

		if ($preventrecursivecall) {
			$moreparams['preventrecursivecall'] = true;
		}

		$result = $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, ($preventrecursivecall ? $moreparams : $moreparams['object']));

		$this->call_trigger(strtoupper($this->type).'_GENERATE', $moreparams['user']);

		return $result;
	}

	/**
	 *  Fill risk data for ODT.
	 *
	 * @param  odf			$odfHandler 		Object odfHandler for ODT
	 * @param  Object		$object 			Object source to build document
	 * @param  Translate 	$outputlangs 		Lang output object
	 * @param  array 		$tmparray 			Array filled with data
	 * @param  string 		$file 				Filename
	 * @param  array 	 	$risks 				Array data of risks
	 *
	 * @return void
	 * @throws Exception
	 */
	public function fillRiskData($odfHandler, $object, $outputlangs, $tmparray, $file, $risks)
	{
		global $action, $conf, $hookmanager, $langs;

		$digiriskelementobject = new DigiriskElement($this->db);

		for ($i = 1; $i <= 4; $i++ ) {
			$listlines = $odfHandler->setSegment('risk' . $i);
			if (is_array($risks) && ! empty($risks)) {
				$digiriskelementobject->fetch($conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH);
				$trashList = $digiriskelementobject->getMultiEntityTrashList();
				foreach ($risks as $line) {
					if ( ! in_array($line->fk_element, $trashList) && $line->fk_element > 0) {
						$tmparray['actionPreventionUncompleted'] = "";
						$tmparray['actionPreventionCompleted']   = "";
						$lastEvaluation                          = $line->lastEvaluation;

						if ($lastEvaluation->cotation >= 0 && !empty($lastEvaluation) && is_object($lastEvaluation)) {
							$scale = $lastEvaluation->get_evaluation_scale();

							if ($scale == $i) {
								$element = new DigiriskElement($this->db);
								$linked_element = new DigiriskElement($this->db);
								$element->fetch($line->fk_element);
								$linked_element->fetch($line->appliedOn);

								if ($conf->global->DIGIRISKDOLIBARR_SHOW_RISK_ORIGIN) {
									$nomElement = (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS) ? 'S' . $element->entity . ' - ' : '') . $element->ref . ' - ' . $element->label;
									if ($line->fk_element != $line->appliedOn) {
										$nomElement .= "\n" . $langs->trans('AppliedOn') . ' ' . $linked_element->ref . ' - ' . $linked_element->label;
									}
								} else {
									if ($linked_element->id > 0) {
										$nomElement = "\n" . $linked_element->ref . ' - ' . $linked_element->label;
									} else {
										$nomElement = "\n" . $element->ref . ' - ' . $element->label;
									}
								}

								$tmparray['nomElement']            = $nomElement;
								$tmparray['nomDanger']             = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $line->get_danger_category($line) . '.png';
								$tmparray['nomPicto']              = (!empty($conf->global->DIGIRISKDOLIBARR_DOCUMENT_SHOW_PICTO_NAME) ? $line->get_danger_category_name($line) : ' ');
								$tmparray['identifiantRisque']     = $line->ref . ' - ' . $lastEvaluation->ref;
								$tmparray['quotationRisque']       = $lastEvaluation->cotation ?: 0;
								$tmparray['descriptionRisque']     = $line->description;
								$tmparray['commentaireEvaluation'] = $lastEvaluation->comment ? dol_print_date((($conf->global->DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE && (!empty($lastEvaluation->date_riskassessment))) ? $lastEvaluation->date_riskassessment : $lastEvaluation->date_creation), 'dayreduceformat') . ': ' . $lastEvaluation->comment : '';

								$related_tasks = $line->get_related_tasks($line);
								$usertmp = new User($this->db);

								if (!empty($related_tasks) && is_array($related_tasks)) {
									foreach ($related_tasks as $related_task) {
										$AllInitiales = '';
										$related_task_contact_ids = $related_task->getListContactId();
										if (!empty($related_task_contact_ids) && is_array($related_task_contact_ids)) {
											foreach ($related_task_contact_ids as $related_task_contact_id) {
												$usertmp->fetch($related_task_contact_id);
												$AllInitiales .= strtoupper(str_split($usertmp->firstname, 1)[0] . str_split($usertmp->lastname, 1)[0] . ',');
											}
										}

										$contactslistinternal = $related_task->liste_contact(-1, 'internal');
										$responsible          = '';

										if (!empty($contactslistinternal) && is_array($contactslistinternal)) {
											foreach ($contactslistinternal as $contactlistinternal) {
												if ($contactlistinternal['code'] == 'TASKEXECUTIVE') {
													$responsible .= $contactlistinternal['firstname'] . ' ' . $contactlistinternal['lastname'] . ', ';
												}
											}
										}

										if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS) {
											$tmparray = $related_task->getSummaryOfTimeSpent();
											if ($tmparray['total_duration'] > 0 && !empty($related_task->planned_workload)) {
												$task_progress = round($tmparray['total_duration'] / $related_task->planned_workload * 100, 2);
											} else {
												$task_progress = 0;
											}
										} else {
											$task_progress = $related_task->progress;
										}

										if ($task_progress == 100) {
											if ($conf->global->DIGIRISKDOLIBARR_WORKUNITDOCUMENT_SHOW_TASK_DONE > 0) {
												(($related_task->ref) ? $tmparray['actionPreventionCompleted'] .= $langs->trans('Ref') . ' : ' . $related_task->ref . "\n" : '');
												(($responsible) ? $tmparray['actionPreventionCompleted'] .= $langs->trans('Responsible') . ' : ' . $responsible . "\n" : '');
												$tmparray['actionPreventionCompleted'] .= $langs->trans('DateStart') . ' : ';
												if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE && !empty($related_task->date_start)) {
													$tmparray['actionPreventionCompleted'] .= dol_print_date(($related_task->date_start), 'dayreduceformat');
												} else {
													$tmparray['actionPreventionCompleted'] .= dol_print_date(($related_task->date_c), 'dayreduceformat');
												}
												if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && !empty($related_task->date_end)) {
													$tmparray['actionPreventionCompleted'] .= "\n" . $langs->transnoentities('Deadline') . ' : ' . dol_print_date($related_task->date_end, 'dayreduceformat') . "\n";
												} else {
													$tmparray['actionPreventionCompleted'] .= ' - ' . $langs->transnoentities('Deadline') . ' : ' . $langs->trans('NoData') . "\n";
												}
												$tmparray['actionPreventionCompleted'] .= $langs->trans('Budget') . ' : ' . price($related_task->budget_amount, 0, $langs, 1, 0, 0, $conf->currency) . "\n";
												(($AllInitiales) ? $tmparray['actionPreventionCompleted'] .= $langs->trans('ContactsAction') . ' : ' . $AllInitiales . "\n" : '');
												(($related_task->label) ? $tmparray['actionPreventionCompleted'] .= $langs->trans('Label') . ' : ' . $related_task->label . "\n" : '');
												(($related_task->description) ? $tmparray['actionPreventionCompleted'] .= $langs->trans('Description') . ' : ' . $related_task->description . "\n" : '');
												$tmparray['actionPreventionCompleted'] .= "\n";
											} else {
												$tmparray['actionPreventionCompleted'] = $langs->transnoentities('ActionPreventionCompletedTaskDone');
											}
										} else {
											(($related_task->ref) ? $tmparray['actionPreventionUncompleted'] .= $langs->trans('Ref') . ' : ' . $related_task->ref . "\n" : '');
											(($responsible) ? $tmparray['actionPreventionUncompleted'] .= $langs->trans('Responsible') . ' : ' . $responsible . "\n" : '');
											$tmparray['actionPreventionUncompleted'] .= $langs->trans('DateStart') . ' : ';
											if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE && !empty($related_task->date_start)) {
												$tmparray['actionPreventionUncompleted'] .= dol_print_date(($related_task->date_start), 'dayreduceformat');
											} else {
												$tmparray['actionPreventionUncompleted'] .= dol_print_date(($related_task->date_c), 'dayreduceformat');
											}
											if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && !empty($related_task->date_end)) {
												$tmparray['actionPreventionUncompleted'] .= "\n" . $langs->transnoentities('Deadline') . ' : ' . dol_print_date($related_task->date_end, 'dayreduceformat') . "\n";
											} else {
												$tmparray['actionPreventionUncompleted'] .= ' - ' . $langs->transnoentities('Deadline') . ' : ' . $langs->trans('NoData') . "\n";
											}
											$tmparray['actionPreventionUncompleted'] .= $langs->trans('Budget') . ' : ' . price($related_task->budget_amount, 0, $langs, 1, 0, 0, $conf->currency) . ' - ';
											$tmparray['actionPreventionUncompleted'] .= $langs->trans('DigiriskProgress') . ' : ' . ($task_progress ?: 0) . ' %' . "\n";
											(($AllInitiales) ? $tmparray['actionPreventionUncompleted'] .= $langs->trans('ContactsAction') . ' : ' . $AllInitiales . "\n" : '');
											(($related_task->label) ? $tmparray['actionPreventionUncompleted'] .= $langs->trans('Label') . ' : ' . $related_task->label . "\n" : '');
											(($related_task->description) ? $tmparray['actionPreventionUncompleted'] .= $langs->trans('Description') . ' : ' . $related_task->description . "\n" : '');
											$tmparray['actionPreventionUncompleted'] .= "\n";
										}
									}
								} else {
									$tmparray['actionPreventionUncompleted'] = "";
									$tmparray['actionPreventionCompleted']   = "";
								}

								if (dol_strlen($lastEvaluation->photo) && $lastEvaluation !== 'undefined') {
									$entity                    = $lastEvaluation->entity > 1 ? '/' . $lastEvaluation->entity : '';
									$path                      = DOL_DATA_ROOT . $entity . '/digiriskdolibarr/riskassessment/' . $lastEvaluation->ref;
									$thumb_name                = getThumbName($lastEvaluation->photo);
									$image                     = $path . '/thumbs/' . $thumb_name;
									$tmparray['photoAssociee'] = $image;
								} else {
									$tmparray['photoAssociee'] = $langs->transnoentities('NoFileLinked');
								}

								unset($tmparray['object_fields']);

								complete_substitutions_array($tmparray, $outputlangs, $object, $line, "completesubstitutionarray_lines");
								// Call the ODTSubstitutionLine hook
								$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray, 'line' => $line);
								$hookmanager->executeHooks('ODTSubstitutionLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
								foreach ($tmparray as $key => $val) {
									try {
										if ($key == 'photoAssociee') {
											if (file_exists($val)) {
												$listlines->setImage($key, $val);
											} else {
												$listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
											}
										} elseif ($key == 'nomDanger') {
											if (file_exists($val)) {
												$listlines->setImage($key, $val);
											} else {
												$listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
											}
										} elseif (empty($val) && $val != '0') {
											$listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
										} else {
											$listlines->setVars($key, html_entity_decode($val, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
										}
									} catch (OdfException $e) {
										dol_syslog($e->getMessage());
									}
								}
								$listlines->merge();
							}
						}
					}
				}
			} else {
				$tmparray['nomElement']                  = $langs->trans('NoData');
				$tmparray['nomDanger']                   = $langs->trans('NoData');
				$tmparray['nomPicto']                    = $langs->trans('NoData');
				$tmparray['identifiantRisque']           = $langs->trans('NoData');
				$tmparray['quotationRisque']             = $langs->trans('NoData');
				$tmparray['descriptionRisque']           = $langs->trans('NoDescriptionThere');
				$tmparray['commentaireEvaluation']       = $langs->trans('NoRiskThere');
				$tmparray['actionPreventionUncompleted'] = $langs->trans('NoTaskUnCompletedThere');
				$tmparray['actionPreventionCompleted']   = $langs->trans('NoTaskCompletedThere');
				$tmparray['photoAssociee']               = $langs->transnoentities('NoFileLinked');
				foreach ($tmparray as $key => $val) {
					try {
						if (empty($val)) {
							$listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
						} else {
							$listlines->setVars($key, html_entity_decode($val, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
						}
					} catch (SegmentException $e) {
						dol_syslog($e->getMessage());
					}
				}
				$listlines->merge();
			}
			$odfHandler->mergeSegment($listlines);
		}
	}
}
