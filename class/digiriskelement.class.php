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
 * \file        class/digiriskelement.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a CRUD class file for DigiriskElement (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
dol_include_once('/digiriskdolibarr/class/risk.class.php');
dol_include_once('/digiriskdolibarr/class/riskassessment.class.php');

/**
 * Class for DigiriskElement
 */
class DigiriskElement extends CommonObject
{
	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'digiriskelement';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'digiriskdolibarr_digiriskelement';

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
	 * @var string String with name of icon for digiriskelement. Must be the part after the 'object_' into object_digiriskelement.png
	 */
	public $picto = 'digiriskelement@digiriskdolibarr';

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid'         => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'comment'=>"Id"),
		'ref'           => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>1, 'noteditable'=>'1', 'default'=>'(PROV)', 'index'=>1, 'searchall'=>1, 'showoncombobox'=>'1', 'comment'=>"Reference of object"),
		'ref_ext'       => array('type'=>'varchar(128)', 'label'=>'RefExt', 'enabled'=>'1', 'position'=>20, 'notnull'=>0, 'visible'=>-1,),
		'entity'        => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>-1,),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>40, 'notnull'=>1, 'visible'=>-2,),
		'tms'           => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>-2,),
		'import_key'    => array('type'=>'integer', 'label'=>'ImportId', 'enabled'=>'1', 'position'=>60, 'notnull'=>1, 'visible'=>-2,),
		'status'        => array('type'=>'smallint', 'label'=>'Status', 'enabled'=>'1', 'position'=>70, 'notnull'=>0, 'visible'=>1, 'index'=>1,),
		'label'         => array('type'=>'varchar(255)', 'label'=>'Label', 'enabled'=>'1', 'position'=>80, 'notnull'=>1, 'visible'=>1, 'searchall'=>1, 'css'=>'minwidth200', 'help'=>"Help text", 'showoncombobox'=>'1',),
		'description'   => array('type'=>'text', 'label'=>'Description', 'enabled'=>'1', 'position'=>90, 'notnull'=>0, 'visible'=>3,),
		'element_type'  => array('type'=>'varchar(50)', 'label'=>'ElementType', 'enabled'=>'1', 'position'=>100, 'notnull'=>-1, 'visible'=>1,),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>110, 'notnull'=>1, 'visible'=>-2, 'foreignkey'=>'user.rowid',),
		'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>'1', 'position'=>120, 'notnull'=>-1, 'visible'=>-2,),
		'fk_parent'     => array('type'=>'integer', 'label'=>'ParentElement', 'enabled'=>'1', 'position'=>130, 'notnull'=>1, 'visible'=>1, 'default'=>0,),
	);

	public $rowid;
	public $ref;
	public $ref_ext;
	public $entity;
	public $date_creation;
	public $tms;
	public $import_key;
	public $status;
	public $label;
	public $description;
	public $element_type;
	public $fk_user_creat;
	public $fk_user_modif;
	public $fk_parent;

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
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		$this->element = $this->element_type . '@digiriskdolibarr';
		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		return $this->fetchCommon($id, $ref);
	}

	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string      $sortorder    Sort Order
	 * @param  string      $sortfield    Sort field
	 * @param  int         $limit        limit
	 * @param  int         $offset       Offset
	 * @param  array       $filter       Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
	 * @param  string      $filtermode   Filter mode (AND or OR)
	 * @return array|int                 int <0 if KO, array of pages if OK
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
	 * @return int             <0 if KO, >0 if OK
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
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		return $this->deleteCommon($user, $notrigger);
	}

	/**
	 *	Load the info information in the object
	 *
	 *	@param  int		$id       Id of object
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT rowid, date_creation as datec, tms as datem,';
		$sql .= ' fk_user_creat, fk_user_modif';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql .= ' WHERE t.rowid = '.$id;
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation = $cuser;
				}

				if ($obj->fk_user_valid)
				{
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}

				if ($obj->fk_user_cloture)
				{
					$cluser = new User($this->db);
					$cluser->fetch($obj->fk_user_cloture);
					$this->user_cloture = $cluser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				$this->date_validation   = $this->db->jdate($obj->datev);
			}

			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
	}

	/**
	 *	Show HTML header HTML + BODY + Top menu + left menu + DIV
	 *
	 * @param 	string 	$head				Optionnal head lines
	 * @param 	string 	$title				HTML title
	 * @param	string	$help_url			Url links to help page
	 * 		                            	Syntax is: For a wiki page: EN:EnglishPage|FR:FrenchPage|ES:SpanishPage
	 *                                  	For other external page: http://server/url
	 * @param	string	$target				Target to use on links
	 * @param 	int    	$disablejs			More content into html header
	 * @param 	int    	$disablehead		More content into html header
	 * @param 	array  	$arrayofjs			Array of complementary js files
	 * @param 	array  	$arrayofcss			Array of complementary css files
	 * @param	string	$morequerystring	Query string to add to the link "print" to get same parameters (use only if autodetect fails)
	 * @param   string  $morecssonbody      More CSS on body tag.
	 * @param	string	$replacemainareaby	Replace call to main_area() by a print of this string
	 * @return	void
	 */
	public function digiriskHeader($head = '', $title = '', $help_url = '', $target = '', $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '', $morequerystring = '', $morecssonbody = '', $replacemainareaby = '')
	{
		global $conf, $langs;

		// html header

		$tmpcsstouse = 'sidebar-collapse'.($morecssonbody ? ' '.$morecssonbody : '');
		// If theme MD and classic layer, we open the menulayer by default.
		if ($conf->theme == 'md' && !in_array($conf->browser->layout, array('phone', 'tablet')) && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
		{
			global $mainmenu;
			if ($mainmenu != 'website') $tmpcsstouse = $morecssonbody; // We do not use sidebar-collpase by default to have menuhider open by default.
		}

		if (!empty($conf->global->MAIN_OPTIMIZEFORCOLORBLIND)) {
			$tmpcsstouse .= ' colorblind-'.strip_tags($conf->global->MAIN_OPTIMIZEFORCOLORBLIND);
		}

		print '<body id="mainbody" class="'.$tmpcsstouse.'">'."\n";

		llxHeader('', $title, $help_url);

		//Body navigation digirisk
		$object  = new DigiriskElement($this->db);
		$objects = $object->fetchAll('', '', 0,0,array('entity' => $conf->entity));
		$results  = $this->recurse_tree(0,0,$objects); ?>
		<div id="id-container" class="id-container page-ut-gp-list">
			<div class="side-nav">
				<div id="id-left">
					<div class="digirisk-wrap wpeo-wrap">
						<div class="navigation-container">
							<div class="society-header">
								<a class="linkElement" href="../digiriskelement_card.php">
									<span class="icon fas fa-building fa-fw"></span>
										<div class="title">
										<?php echo $conf->global->MAIN_INFO_SOCIETE_NOM ?>
										</div>
									<div class="add-container">
										<a id="newGroupment" href="../digiriskelement_card.php?action=create&element_type=groupment&fk_parent=0">
											<div class="wpeo-button button-square-40 button-secondary wpeo-tooltip-event" data-direction="bottom" data-color="light" aria-label="<?php echo $langs->trans('NewGroupment'); ?>"><strong>GP</strong><span class="button-add animated fas fa-plus-circle"></span></div>
										</a>
										<a id="newWorkunit" href="../digiriskelement_card.php?action=create&element_type=workunit&fk_parent=0">
											<div class="wpeo-button button-square-40 wpeo-tooltip-event" data-direction="bottom" data-color="light" aria-label="<?php echo $langs->trans('NewWorkunit'); ?>"><strong>UT</strong><span class="button-add animated fas fa-plus-circle"></span></div>
										</a>
									</div>
<!--									<div class="mobile-add-container wpeo-dropdown dropdown-right option">-->
<!--										<div class="dropdown-toggle"><i class="action fas fa-ellipsis-v"></i></div>-->
<!--										<ul class="dropdown-content">-->
<!--											<li class="dropdown-item" data-type="Group_Class"><i class="icon dashicons dashicons-admin-multisite"></i>--><?php ////echo //esc_attr( 'Ajouter groupement', 'digirisk' ); ?><!--</li>-->
<!--											<li class="dropdown-item" data-type="Workunit_Class"><i class="icon dashicons dashicons-admin-home"></i>--><?php ////echo //esc_attr( 'Ajouter unité', 'digirisk' ); ?><!--</li>-->
<!--										</ul>-->
<!--									</div>-->
<!--									<div class="close-popup"><i class="icon fas fa-times"></i></div>-->
								</a>
							</div>
							<div class="toolbar">
								<div class="toggle-plus tooltip hover" aria-label="<?php echo $langs->trans('UnwrapAll'); ?>"><span class="icon fas fa-plus-square"></span></div>
								<div class="toggle-minus tooltip hover" aria-label="<?php echo $langs->trans('WrapAll'); ?>"><span class="icon fas fa-minus-square"></span></div>
							</div>

							<ul class="workunit-list">
									<?php $this->display_recurse_tree($results) ?>

									<script>
									// Get previous menu to display it
									var MENU = localStorage.menu
									if (MENU == null || MENU == '') {
										MENU = new Set()
									} else {
										MENU = JSON.parse(MENU)
										MENU = new Set(MENU)
									}

									MENU.forEach((id) =>  {
										jQuery( '#menu'+id).removeClass( 'fa-chevron-right').addClass( 'fa-chevron-down' );
										jQuery( '#unit'+id ).addClass( 'toggled' );
									})

									// Set active unit active
									jQuery( '.digirisk-wrap .navigation-container .unit.active' ).removeClass( 'active' );

									var params = new window.URLSearchParams(window.location.search);
									var id = params.get('id')

									jQuery( '#unit'  + id ).addClass( 'active' );
									jQuery( '#unit'  +id  ).closest( '.unit' ).attr( 'value', id );

									</script>
								</ul>
						</div>
					</div>
				</div>
			</div>
		<?php


		// main area
		if ($replacemainareaby)
		{
			print $replacemainareaby;
			return;
		}
		main_area($title);
	}

	function recurse_tree($parent, $niveau, $array) {
		$result = array();
		foreach ($array as $noeud) {
			if ($parent == $noeud->fk_parent) {
				$result[$noeud->id] = array(
					'id'       => $noeud->id,
					'object'   => $noeud,
					'children' => $this->recurse_tree($noeud->id, ($niveau + 1), $array),
				);
			}
		}
		return $result;
	}

	function display_recurse_tree($results) {
		global $conf, $langs;

		if ( !empty( $results ) ) {
			foreach ($results as $element) { ?>
				<li class="unit type-<?php echo $element['object']->element_type; ?>" id="unit<?php  echo $element['object']->id; ?>">
					<div class="unit-container">
						<?php if ($element['object']->element_type == 'groupment' && count($element['children'])) { ?>
							<div class="toggle-unit">
								<i class="toggle-icon fas fa-chevron-right" id="menu<?php echo $element['object']->id;?>"></i>
							</div>
						<?php } else { ?>
							<div class="spacer"></div>
						<?php } ?>
						<?php $filearray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$element['object']->element_type.'/'.$element['object']->ref.'/', "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'asc', 1);
						if (count($filearray)) {
							print '<span class="floatleft inline-block valignmiddle divphotoref">'.$element['object']->digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$element['object']->element_type, 'small', 1, 0, 0, 0, 50, 0, 0, 0, 0, $element['object']->element_type).'</span>';
						} else {
							$nophoto = '/public/theme/common/nophoto.png'; ?>
							<span class="floatleft inline-block valignmiddle divphotoref"><img class="photodigiriskdolibarr" alt="No photo" src="<?php echo DOL_URL_ROOT.$nophoto ?>"></span>
						<?php } ?>
						<div class="title" id="scores" value="<?php echo $element['object']->id ?>" >
								<a id="slider" class="linkElement id<?php echo $element['object']->id;?>" href="../digiriskelement_risk.php?id=<?php echo $element['object']->id; ?>">
									<span class="title-container">
										<span class="ref"><?php echo $element['object']->ref; ?></span>
										<span class="name"><?php echo $element['object']->label; ?></span>
									</span>
								</a>
						</div>
						<?php if ($element['object']->element_type == 'groupment') { ?>
							<div class="add-container">
								<a id="newGroupment" href="../digiriskelement_card.php?action=create&element_type=groupment&fk_parent=<?php echo $element['object']->id; ?>">
									<div
										class="wpeo-button button-secondary button-square-40 wpeo-tooltip-event"
										data-direction="bottom" data-color="light"
										aria-label="<?php echo $langs->trans('NewGroupment'); ?>">
										<strong>GP</strong>
										<span class="button-add animated fas fa-plus-circle"></span>
									</div>
								</a>
								<a id="newWorkunit" href="../digiriskelement_card.php?action=create&element_type=workunit&fk_parent=<?php echo $element['object']->id; ?>">
									<div
										class="wpeo-button button-square-40 wpeo-tooltip-event"
										data-direction="bottom" data-color="light"
										aria-label="<?php echo $langs->trans('NewWorkunit'); ?>">
										<strong>UT</strong>
										<span class="button-add animated fas fa-plus-circle"></span>
									</div>
								</a>
							</div>
<!--							<div class="mobile-add-container wpeo-dropdown dropdown-right option">-->
<!--								<div class="dropdown-toggle"><i class="action fas fa-ellipsis-v"></i></div>-->
<!--								<ul class="dropdown-content">-->
<!--									<li class="dropdown-item" data-type="Group_Class"><i class="icon dashicons dashicons-admin-multisite"></i>--><?php //echo $langs->trans('NewGroupment'); ?><!--</li>-->
<!--									<li class="dropdown-item" data-type="Workunit_Class"><i class="icon dashicons dashicons-admin-home"></i>--><?php //echo $langs->trans('NewWorkunit'); ?><!--</li>-->
<!--								</ul>-->
<!--							</div>-->
						<?php } ?>
					</div>
					<ul class="sub-list"><?php $this->display_recurse_tree($element['children']) ?></ul>
				</li>
			<?php }
		}
	}

	  // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Show photos of an object (nbmax maximum), into several columns
	 *
	 *  @param		string	$modulepart		'product', 'ticket', ...
	 *  @param      string	$sdir        	Directory to scan (full absolute path)
	 *  @param      int		$size        	0=original size, 1='small' use thumbnail if possible
	 *  @param      int		$nbmax       	Nombre maximum de photos (0=pas de max)
	 *  @param      int		$nbbyrow     	Number of image per line or -1 to use div. Used only if size=1.
	 * 	@param		int		$showfilename	1=Show filename
	 * 	@param		int		$showaction		1=Show icon with action links (resize, delete)
	 * 	@param		int		$maxHeight		Max height of original image when size='small' (so we can use original even if small requested). If 0, always use 'small' thumb image.
	 * 	@param		int		$maxWidth		Max width of original image when size='small'
	 *  @param      int     $nolink         Do not add a href link to view enlarged imaged into a new tab
	 *  @param      int     $notitle        Do not add title tag on image
	 *  @param		int		$usesharelink	Use the public shared link of image (if not available, the 'nophoto' image will be shown instead)
	 *  @return     string					Html code to show photo. Number of photos shown is saved in this->nbphoto
	 */
	public function digirisk_show_photos($modulepart, $sdir, $size = 0, $nbmax = 0, $nbbyrow = 5, $showfilename = 0, $showaction = 0, $maxHeight = 120, $maxWidth = 160, $nolink = 0, $notitle = 0, $usesharelink = 0, $subdir = '')
	{
        // phpcs:enable
		global $conf, $user, $langs;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

		$sortfield = 'position_name';
		$sortorder = 'asc';

		$dir = $sdir.'/';
		$pdir = '/' . $subdir . '/';

		if ($this->tms > 0) {
			$dir .= get_exdir(0, 0, 0, 0, $this, $modulepart).$this->ref.'/';
			$pdir .= get_exdir(0, 0, 0, 0, $this, $modulepart).$this->ref.'/';
		} else {
			$dir .= get_exdir(0, 0, 0, 0, $this, $modulepart);
			$pdir .= get_exdir(0, 0, 0, 0, $this, $modulepart);
		}

		// For backward compatibility
		if ($modulepart == 'product' && !empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO))
		{
			$dir = $sdir.'/'.get_exdir($this->id, 2, 0, 0, $this, $modulepart).$this->id."/photos/";
			$pdir = '/'.get_exdir($this->id, 2, 0, 0, $this, $modulepart).$this->id."/photos/";
		}
		// Defined relative dir to DOL_DATA_ROOT
		$relativedir = '';
		if ($dir)
		{
			$relativedir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $dir);
			$relativedir = preg_replace('/^[\\/]/', '', $relativedir);
			$relativedir = preg_replace('/[\\/]$/', '', $relativedir);
		}

		$dirthumb = $dir.'thumbs/';
		$pdirthumb = $pdir.'thumbs/';

		$return = '<!-- Photo -->'."\n";
		$nbphoto = 0;

		$filearray = dol_dir_list($dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ?SORT_DESC:SORT_ASC), 1);
		/*if (! empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO))    // For backward compatiblity, we scan also old dirs
		 {
		 $filearrayold=dol_dir_list($dirold,"files",0,'','(\.meta|_preview.*\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
		 $filearray=array_merge($filearray, $filearrayold);
		 }*/

		completeFileArrayWithDatabaseInfo($filearray, $relativedir);

		if (count($filearray))
		{
			if ($sortfield && $sortorder)
			{
				$filearray = dol_sort_array($filearray, $sortfield, $sortorder);
			}

			foreach ($filearray as $key => $val)
			{
				$photo = '';
				$file = $val['name'];

				//if (! utf8_check($file)) $file=utf8_encode($file);	// To be sure file is stored in UTF8 in memory

				//if (dol_is_file($dir.$file) && image_format_supported($file) >= 0)
				if (image_format_supported($file) >= 0)
				{
					$nbphoto++;
					$photo = $file;
					$viewfilename = $file;

					if ($size == 1 || $size == 'small') {   // Format vignette
						// Find name of thumb file
						$photo_vignette = basename(getImageFileNameForSize($dir.$file, '_small'));

						if (!dol_is_file($dirthumb.$photo_vignette)) $photo_vignette = '';
						// Get filesize of original file
						$imgarray = dol_getImageSize($dir.$photo);

						if ($nbbyrow > 0)
						{
							if ($nbphoto == 1) $return .= '<table class="valigntop center centpercent" style="border: 0; padding: 2px; border-spacing: 2px; border-collapse: separate;">';

							if ($nbphoto % $nbbyrow == 1) $return .= '<tr class="center valignmiddle" style="border: 1px">';
							$return .= '<td style="width: '.ceil(100 / $nbbyrow).'%" class="photo">';
						}
						elseif ($nbbyrow < 0) $return .= '<div class="inline-block">';

						$return .= "\n";

						$relativefile = preg_replace('/^\//', '', $pdir.$photo);
						if (empty($nolink))
						{
							$urladvanced = getAdvancedPreviewUrl($modulepart, $relativefile, 0, 'entity='.$this->entity);
							if ($urladvanced) $return .= '<a href="'.$urladvanced.'">';
							else $return .= '<a href="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$this->entity.'&file='.urlencode($pdir.$photo).'" class="aphoto" target="_blank">';
						}

						// Show image (width height=$maxHeight)
						// Si fichier vignette disponible et image source trop grande, on utilise la vignette, sinon on utilise photo origine
						$alt = $langs->transnoentitiesnoconv('File').': '.$relativefile;
						$alt .= ' - '.$langs->transnoentitiesnoconv('Size').': '.$imgarray['width'].'x'.$imgarray['height'];
						if ($notitle) $alt = '';

						if ($usesharelink)
						{
							if ($val['share'])
							{
								if (empty($maxHeight) || $photo_vignette && $imgarray['height'] > $maxHeight)
								{
									$return .= '<!-- Show original file (thumb not yet available with shared links) -->';
									$return .= '<img class="photo photowithmargin" height="'.$maxHeight.'" src="'.DOL_URL_ROOT.'/viewimage.php?hashp='.urlencode($val['share']).'" title="'.dol_escape_htmltag($alt).'">';
								}
								else {
									$return .= '<!-- Show original file -->';
									$return .= '<img class="photo photowithmargin" height="'.$maxHeight.'" src="'.DOL_URL_ROOT.'/viewimage.php?hashp='.urlencode($val['share']).'" title="'.dol_escape_htmltag($alt).'">';
								}
							}
							else
							{
								$return .= '<!-- Show nophoto file (because file is not shared) -->';
								$return .= '<img class="photo photowithmargin" height="'.$maxHeight.'" src="'.DOL_URL_ROOT.'/public/theme/common/nophoto.png" title="'.dol_escape_htmltag($alt).'">';
							}
						}
						else
						{
							if (empty($maxHeight) || $photo_vignette && $imgarray['height'] > $maxHeight)
							{
								$return .= '<!-- Show thumb -->';
								$return .= '<img class="photo photowithmargin"  height="'.$maxHeight.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$this->entity.'&file='.urlencode($pdirthumb.$photo_vignette).'" title="'.dol_escape_htmltag($alt).'">';
							}
							else {
								$return .= '<!-- Show original file -->';
								$return .= '<img class="photo photowithmargin" height="'.$maxHeight.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$this->entity.'&file='.urlencode($pdir.$photo).'" title="'.dol_escape_htmltag($alt).'">';
							}
						}

						if (empty($nolink)) $return .= '</a>';
						$return .= "\n";

						if ($showfilename) $return .= '<br>'.$viewfilename;
						if ($showaction)
						{
							$return .= '<br>';
							// On propose la generation de la vignette si elle n'existe pas et si la taille est superieure aux limites
							if ($photo_vignette && (image_format_supported($photo) > 0) && ($this->imgWidth > $maxWidth || $this->imgHeight > $maxHeight))
							{
								$return .= '<a href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=addthumb&amp;file='.urlencode($pdir.$viewfilename).'">'.img_picto($langs->trans('GenerateThumb'), 'refresh').'&nbsp;&nbsp;</a>';
							}
							// Special cas for product
							if ($modulepart == 'product' && ($user->rights->produit->creer || $user->rights->service->creer))
							{
								// Link to resize
								$return .= '<a href="'.DOL_URL_ROOT.'/core/photos_resize.php?modulepart='.urlencode('produit|service').'&id='.$this->id.'&amp;file='.urlencode($pdir.$viewfilename).'" title="'.dol_escape_htmltag($langs->trans("Resize")).'">'.img_picto($langs->trans("Resize"), 'resize', '').'</a> &nbsp; ';

								// Link to delete
								$return .= '<a href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=delete&amp;file='.urlencode($pdir.$viewfilename).'">';
								$return .= img_delete().'</a>';
							}
						}
						$return .= "\n";

						if ($nbbyrow > 0)
						{
							$return .= '</td>';
							if (($nbphoto % $nbbyrow) == 0) $return .= '</tr>';
						}
						elseif ($nbbyrow < 0) $return .= '</div>';
					}

					if (empty($size)) {     // Format origine
						$return .= '<img class="photo photowithmargin" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$this->entity.'&file='.urlencode($pdir.$photo).'">';

						if ($showfilename) $return .= '<br>'.$viewfilename;
						if ($showaction)
						{
							// Special case for product
							if ($modulepart == 'product' && ($user->rights->produit->creer || $user->rights->service->creer))
							{
								// Link to resize
								$return .= '<a href="'.DOL_URL_ROOT.'/core/photos_resize.php?modulepart='.urlencode('produit|service').'&id='.$this->id.'&amp;file='.urlencode($pdir.$viewfilename).'" title="'.dol_escape_htmltag($langs->trans("Resize")).'">'.img_picto($langs->trans("Resize"), 'resize', '').'</a> &nbsp; ';

								// Link to delete
								$return .= '<a href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=delete&amp;file='.urlencode($pdir.$viewfilename).'">';
								$return .= img_delete().'</a>';
							}
						}
					}

					// On continue ou on arrete de boucler ?
					if ($nbmax && $nbphoto >= $nbmax) break;
				}
			}

			if ($size == 1 || $size == 'small')
			{
				if ($nbbyrow > 0)
				{
					// Ferme tableau
					while ($nbphoto % $nbbyrow)
					{
						$return .= '<td style="width: '.ceil(100 / $nbbyrow).'%">&nbsp;</td>';
						$nbphoto++;
					}

					if ($nbphoto) $return .= '</table>';
				}
			}
		}

		$this->nbphoto = $nbphoto;

		return $return;
	}
		/**
	 *  Show tab footer of a card.
	 *  Note: $object->next_prev_filter can be set to restrict select to find next or previous record by $form->showrefnav.
	 *
	 *  @param	Object	$object			Object to show
	 *  @param	string	$paramid   		Name of parameter to use to name the id into the URL next/previous link
	 *  @param	string	$morehtml  		More html content to output just before the nav bar
	 *  @param	int		$shownav	  	Show Condition (navigation is shown if value is 1)
	 *  @param	string	$fieldid   		Nom du champ en base a utiliser pour select next et previous (we make the select max and min on this field). Use 'none' for no prev/next search.
	 *  @param	string	$fieldref   	Nom du champ objet ref (object->ref) a utiliser pour select next et previous
	 *  @param	string	$morehtmlref  	More html to show after ref
	 *  @param	string	$moreparam  	More param to add in nav link url.
	 *	@param	int		$nodbprefix		Do not include DB prefix to forge table name
	 *	@param	string	$morehtmlleft	More html code to show before ref
	 *	@param	string	$morehtmlstatus	More html code to show under navigation arrows
	 *  @param  int     $onlybanner     Put this to 1, if the card will contains only a banner (this add css 'arearefnobottom' on div)
	 *	@param	string	$morehtmlright	More html code to show before navigation arrows
	 *  @return	void
	 */
	function digirisk_banner_tab($object, $paramid, $morehtml = '', $shownav = 1, $fieldid = 'rowid', $fieldref = 'ref', $morehtmlref = '', $moreparam = '', $nodbprefix = 0, $morehtmlleft = '', $morehtmlstatus = '', $onlybanner = 0, $morehtmlright = '')
	{
		global $conf, $form, $user, $langs;


		print '<div class="'.($onlybanner ? 'arearefnobottom ' : 'arearef ').'heightref valignmiddle centpercent">';
		print $form->showrefnav($object, $paramid, $morehtml, $shownav, $fieldid, $fieldref, $morehtmlref, $moreparam, $nodbprefix, $morehtmlleft, $morehtmlstatus, $morehtmlright);
		print '</div>';
		print '<div class="underrefbanner clearboth"></div>';
	}
}
