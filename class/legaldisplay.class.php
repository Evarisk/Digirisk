<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * \file        class/legaldisplay.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a CRUD class file for LegalDisplay (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT .'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT .'/user/class/user.class.php';
dol_include_once('/digiriskdolibarr/class/digiriskdocuments.class.php');
dol_include_once('/digiriskdolibarr/class/digiriskresources.class.php');
dol_include_once('/digiriskdolibarr/class/openinghours.class.php');

/**
 * Class for LegalDisplay
 */
class LegalDisplay extends DigiriskDocuments
{

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $element = 'legaldisplay';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for legaldisplay. Must be the part after the 'object_' into object_legaldisplay.png
	 */
	public $picto = 'legaldisplay@digiriskdolibarr';

	/**
	 *  'type' if the field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM)
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
	 *  'help' is a string visible as a tooltip on field
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arraykeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// If this object has a subtable with lines

	/**
	 * @var int    Name of subtable line
	 */
	//public $table_element_line = 'digiriskdolibarr_legaldisplayline';

	/**
	 * @var int    Field with ID of parent key if this object has a parent
	 */
	//public $fk_element = 'fk_legaldisplay';

	/**
	 * @var int    Name of subtable class that manage subtable lines
	 */
	//public $class_element_line = 'LegalDisplayline';

	/**
	 * @var array	List of child tables. To test if we can delete object.
	 */
	//protected $childtables = array();

	/**
	 * @var array    List of child tables. To know object to delete on cascade.
	 *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	 *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	 */
	//protected $childtablesoncascade = array('digiriskdolibarr_legaldisplaydet');

	/**
	 * @var LegalDisplayLine[]     Array of subtable lines
	 */
	//public $lines = array();



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

		// Example to show how to set values of fields definition dynamically
		/*if ($user->rights->digiriskdolibarr->legaldisplay->read) {
			$this->fields['myfield']['visible'] = 1;
			$this->fields['myfield']['noteditable'] = 0;
		}*/

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
	public function LegaldisplayFillJSON($object) {

		global $langs, $conf;

		$resources 			= new DigiriskResources($this->db);
		$digirisk_resources = $resources->digirisk_dolibarr_fetch_resources();

		$thirdparty_openinghours = new Openinghours($this->db);

		// 		*** JSON FILLING ***
		if (!empty ($digirisk_resources )) {

			$labour_doctor_societe = new Societe($this->db);
			$result = $labour_doctor_societe->fetch($digirisk_resources['LabourDoctorSociety']->id[0]);

			if ($result < 0) dol_print_error($langs->trans('NoLabourDoctorAssigned'), $labour_doctor_societe->error);
			elseif ($result > 0) {

				$labour_doctor_openinghours = $thirdparty_openinghours->fetch_by_element($labour_doctor_societe->id, $labour_doctor_societe->element);
				$json['LegalDisplay']['occupational_health_service']['openinghours'] = "\r\n" . $labour_doctor_openinghours->day0 . "\r\n" . $labour_doctor_openinghours->day1 . "\r\n" . $labour_doctor_openinghours->day2 . "\r\n" . $labour_doctor_openinghours->day3 . "\r\n" . $labour_doctor_openinghours->day4 . "\r\n" . $labour_doctor_openinghours->day5 . "\r\n" . $labour_doctor_openinghours->day6;
			}

			$labour_doctor_contact = new Contact($this->db);
			$result = $labour_doctor_contact->fetch($digirisk_resources['LabourDoctorContact']->id[0]);

			if ($result < 0) dol_print_error($langs->trans('NoLabourDoctorAssigned'), $labour_doctor_contact->error);
			elseif ($result > 0) {
				$json['LegalDisplay']['occupational_health_service']['name']    = $labour_doctor_contact->firstname . " " . $labour_doctor_contact->lastname;
				$json['LegalDisplay']['occupational_health_service']['address'] = preg_replace('/\s\s+/', ' ', $labour_doctor_contact->address);
				$json['LegalDisplay']['occupational_health_service']['zip']     = $labour_doctor_contact->zip;
				$json['LegalDisplay']['occupational_health_service']['town']    = $labour_doctor_contact->town;
				$json['LegalDisplay']['occupational_health_service']['phone']   = $labour_doctor_contact->phone_pro;
			}

			$labour_inspector_societe = new Societe($this->db);
			$result = $labour_inspector_societe->fetch($digirisk_resources['LabourInspectorSociety']->id[0]);

			if ($result < 0) dol_print_error($langs->trans('NoLabourInspectorAssigned'), $labour_inspector_societe->error);
			elseif ($result > 0) {

				$labour_inspector_openinghours = $thirdparty_openinghours->fetch_by_element($labour_inspector_societe->id, $labour_inspector_societe->element);
				$json['LegalDisplay']['detective_work']['openinghours'] = "\r\n" . $labour_inspector_openinghours->day0 . "\r\n" . $labour_inspector_openinghours->day1 . "\r\n" . $labour_inspector_openinghours->day2 . "\r\n" . $labour_inspector_openinghours->day3 . "\r\n" . $labour_inspector_openinghours->day4 . "\r\n" . $labour_inspector_openinghours->day5 . "\r\n" . $labour_inspector_openinghours->day6;

			}

			$labour_inspector_contact = new Contact($this->db);
			$result = $labour_inspector_contact->fetch($digirisk_resources['LabourInspectorContact']->id[0]);

			if ($result < 0) dol_print_error($langs->trans('NoLabourInspectorAssigned'), $labour_inspector_contact->error);
			elseif ($result > 0) {
				$json['LegalDisplay']['detective_work']['name']    = $labour_inspector_contact->firstname . " " . $labour_inspector_contact->lastname;
				$json['LegalDisplay']['detective_work']['address'] = preg_replace('/\s\s+/', ' ', $labour_inspector_contact->address);
				$json['LegalDisplay']['detective_work']['zip']     = $labour_inspector_contact->zip;
				$json['LegalDisplay']['detective_work']['town']    = $labour_inspector_contact->town;
				$json['LegalDisplay']['detective_work']['phone']   = $labour_inspector_contact->phone_pro;
			}

			$samu = new Societe($this->db);
			$result = $samu->fetch($digirisk_resources['SAMU']->id[0]);

			if ($result < 0) dol_print_error($langs->trans('NoSamuAssigned'), $samu->error);
			elseif ($result > 0) {
				$json['LegalDisplay']['emergency_service']['samu'] = $samu->phone;
			}

			$police = new Societe($this->db);
			$result = $police->fetch($digirisk_resources['Police']->id[0]);

			if ($result < 0) dol_print_error($langs->trans('NoPoliceAssigned'), $police->error);
			elseif ($result > 0) {
				$json['LegalDisplay']['emergency_service']['police'] = $police->phone;
			}

			$pompier = new Societe($this->db);
			$result = $pompier->fetch($digirisk_resources['Pompiers']->id[0]);

			if ($result < 0) dol_print_error($langs->trans('NoPoliceAssigned'), $pompier->error);
			elseif ($result > 0) {
				$json['LegalDisplay']['emergency_service']['pompier'] = $pompier->phone;
			}

			$emergency = new Societe($this->db);
			$result = $emergency->fetch($digirisk_resources['AllEmergencies']->id[0]);

			if ($result < 0) dol_print_error($langs->trans('NoAllEmergenciesAssigned'), $emergency->error);
			elseif ($result > 0) {
				$json['LegalDisplay']['emergency_service']['emergency'] = $emergency->phone;
			}

			$rights_defender = new Societe($this->db);
			$result = $rights_defender->fetch($digirisk_resources['RightsDefender']->id[0]);

			if ($result < 0) dol_print_error($langs->trans('NoRightsDefenderAssigned'), $rights_defender->error);
			elseif ($result > 0) {
				$json['LegalDisplay']['emergency_service']['right_defender'] = $rights_defender->phone;
			}

			$antipoison = new Societe($this->db);
			$result = $antipoison->fetch($digirisk_resources['Antipoison']->id[0]);

			if ($result < 0) dol_print_error($langs->trans('NoRightsDefenderAssigned'), $antipoison->error);
			elseif ($result > 0) {
				$json['LegalDisplay']['emergency_service']['poison_control_center'] = $antipoison->phone;
			}

			$responsible_prevent = new User($this->db);
			$result = $responsible_prevent->fetch($digirisk_resources['Responsible']->id[0]);

			if ($result < 0) dol_print_error($langs->trans('NoResponsibleAssigned'), $responsible_prevent->error);
			elseif ($result > 0) {
				$json['LegalDisplay']['safety_rule']['responsible_for_preventing'] = $responsible_prevent->firstname . " " . $responsible_prevent->lastname;
				$json['LegalDisplay']['safety_rule']['phone']                      = $responsible_prevent->office_phone;
			}

			$opening_hours_monday    = explode(' ', $conf->global->MAIN_INFO_OPENINGHOURS_MONDAY);
			$opening_hours_tuesday   = explode(' ', $conf->global->MAIN_INFO_OPENINGHOURS_TUESDAY);
			$opening_hours_wednesday = explode(' ', $conf->global->MAIN_INFO_OPENINGHOURS_WEDNESDAY);
			$opening_hours_thursday  = explode(' ', $conf->global->MAIN_INFO_OPENINGHOURS_THURSDAY);
			$opening_hours_friday    = explode(' ', $conf->global->MAIN_INFO_OPENINGHOURS_FRIDAY);
			$opening_hours_saturday  = explode(' ', $conf->global->MAIN_INFO_OPENINGHOURS_SATURDAY);
			$opening_hours_sunday    = explode(' ', $conf->global->MAIN_INFO_OPENINGHOURS_SUNDAY);

			$json['LegalDisplay']['working_hour']['monday_morning']    = $opening_hours_monday[0];
			$json['LegalDisplay']['working_hour']['tuesday_morning']   = $opening_hours_tuesday[0];
			$json['LegalDisplay']['working_hour']['wednesday_morning'] = $opening_hours_wednesday[0];
			$json['LegalDisplay']['working_hour']['thursday_morning']  = $opening_hours_thursday[0];
			$json['LegalDisplay']['working_hour']['friday_morning']    = $opening_hours_friday[0];
			$json['LegalDisplay']['working_hour']['saturday_morning']  = $opening_hours_saturday[0];
			$json['LegalDisplay']['working_hour']['sunday_morning']    = $opening_hours_sunday[0];

			$json['LegalDisplay']['working_hour']['monday_afternoon']    = $opening_hours_monday[1];
			$json['LegalDisplay']['working_hour']['tuesday_afternoon']   = $opening_hours_tuesday[1];
			$json['LegalDisplay']['working_hour']['wednesday_afternoon'] = $opening_hours_wednesday[1];
			$json['LegalDisplay']['working_hour']['thursday_afternoon']  = $opening_hours_thursday[1];
			$json['LegalDisplay']['working_hour']['friday_afternoon']    = $opening_hours_friday[1];
			$json['LegalDisplay']['working_hour']['saturday_afternoon']  = $opening_hours_saturday[1];
			$json['LegalDisplay']['working_hour']['sunday_afternoon']    = $opening_hours_sunday[1];

			$json['LegalDisplay']['safety_rule']['location_of_detailed_instruction']                      = $conf->global->DIGIRISK_LOCATION_OF_DETAILED_INSTRUCTION;
			$json['LegalDisplay']['derogation_schedule']['permanent']                                     = $conf->global->DIGIRISK_DEROGATION_SCHEDULE_PERMANENT;
			$json['LegalDisplay']['derogation_schedule']['occasional']                                    = $conf->global->DIGIRISK_DEROGATION_SCHEDULE_OCCASIONAL;
			$json['LegalDisplay']['collective_agreement']['title_of_the_applicable_collective_agreement'] = $conf->global->DIGIRISK_COLLECTIVE_AGREEMENT_TITLE;
			$json['LegalDisplay']['collective_agreement']['title_of_the_applicable_collective_agreement'] = $conf->global->DIGIRISK_COLLECTIVE_AGREEMENT_TITLE . ' - ' . $this->getIDCCByCode($conf->global->DIGIRISK_COLLECTIVE_AGREEMENT_TITLE)->libelle;
			$json['LegalDisplay']['collective_agreement']['location_and_access_terms_of_the_agreement']   = $conf->global->DIGIRISK_COLLECTIVE_AGREEMENT_LOCATION;
			$json['LegalDisplay']['DUER']['how_access_to_duer']                                           = $conf->global->DIGIRISK_DUER_LOCATION;
			$json['LegalDisplay']['rules']['location']                                                    = $conf->global->DIGIRISK_RULES_LOCATION;
			$json['LegalDisplay']['participation_agreement']['information_procedures']                    = $conf->global->DIGIRISK_PARTICIPATION_AGREEMENT_INFORMATION_PROCEDURE;

			$object->json = json_encode($json, JSON_UNESCAPED_UNICODE);

			return $object->json;
		}
		else
		{
			return -1;
		}
	}

	public function getIDCCByCode($code) {

		$sql = "SELECT rowid, libelle";
		$sql .= " FROM ".MAIN_DB_PREFIX.'c_conventions_collectives';
		$sql .= " WHERE code = " . $code ;

		$result = $this->db->query($sql);

		if ($result)
		{
			$obj = $this->db->fetch_object($result);
		}
		else {
			dol_print_error($this->db);
		}

		return $obj;
	}

	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *  @param  int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *  @param  string  $option                     On what the link point to ('nolink', ...)
	 *  @param  int     $notooltip                  1=Disable tooltip
	 *  @param  string  $morecss                    Add more css on link
	 *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @return	string                              String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

		$result = '';

		$label = '<u>'.$langs->trans("LegalDisplay").'</u>';
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;
		if (isset($this->status)) {
			$label .= '<br><b>'.$langs->trans("Status").":</b> ".$this->getLibStatut(5);
		}

		$url = dol_buildpath('/digiriskdolibarr/legaldisplay_card.php', 1).'?id='.$this->id;

		if ($option != 'nolink')
		{
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
			if ($add_save_lastsearch_values) $url .= '&save_lastsearch_values=1';
		}

		$linkclose = '';
		if (empty($notooltip))
		{
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
			{
				$label = $langs->trans("ShowLegalDisplay");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
		}
		else $linkclose = ($morecss ? ' class="'.$morecss.'"' : '');

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;

		if (empty($this->showphoto_on_popup)) {
			if ($withpicto) $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		} else {
			if ($withpicto) {
				require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

				list($class, $module) = explode('@', $this->picto);
				$upload_dir = $conf->$module->multidir_output[$conf->entity]."/$class/".dol_sanitizeFileName($this->ref);
				$filearray = dol_dir_list($upload_dir, "files");
				$filename = $filearray[0]['name'];
				if (!empty($filename)) {
					$pospoint = strpos($filearray[0]['name'], '.');

					$pathtophoto = $class.'/'.$this->ref.'/thumbs/'.substr($filename, 0, $pospoint).'_mini'.substr($filename, $pospoint);
					if (empty($conf->global->{strtoupper($module.'_'.$class).'_FORMATLISTPHOTOSASUSERS'})) {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo'.$module.'" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div></div>';
					}
					else {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photouserphoto userphoto" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div>';
					}

					$result .= '</div>';
				}
				else {
					$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
				}
			}
		}

		if ($withpicto != 2) $result .= $this->ref;

		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array('legaldisplaydao'));
		$parameters = array('id'=>$this->id, 'getnomurl'=>$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) $result = $hookmanager->resPrint;
		else $result .= $hookmanager->resPrint;

		return $result;
	}

}

/**
 * Class LegalDisplayLine. You can also remove this and generate a CRUD class for lines objects.
 */
class LegalDisplayLine
{
	// To complete with content of an object LegalDisplayLine
	// We should have a field rowid, fk_legaldisplay and position

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}
}
