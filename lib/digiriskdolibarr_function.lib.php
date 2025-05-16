<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    lib/digiriskdolibarr_function.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Library files with common functions for Digiriskdolibarr
 */

/**
 *	Show HTML header HTML + BODY + Top menu + left menu + DIV
 *
* @param 	string 	$title				HTML title
* @param	string	$help_url			Url links to help page
 * 		                            	Syntax is: For a wiki page: EN:EnglishPage|FR:FrenchPage|ES:SpanishPage
 *                                  	For other external page: http://server/url
* @param array $arrayofjs Array of complementary js files
* @param array $arrayofcss Array of complementary css files
* @param	string	$morequerystring	Query string to add to the link "print" to get same parameters (use only if autodetect fails)
* @param   string  $morecssonbody      More CSS on body tag.
* @param	string	$replacemainareaby	Replace call to main_area() by a print of this string
* @return	void
*@throws Exception
*/
function digirisk_header($title = '', $helpUrl = '', $arrayofjs = [], $arrayofcss =  [], $morequerystring = '', $morecssonbody = '', $replacemainareaby = '')
{
	global $conf, $langs, $db, $user, $moduleNameLowerCase;

	require_once __DIR__ . '/../class/digiriskelement/groupment.class.php';
	require_once __DIR__ . '/../class/digiriskelement/workunit.class.php';

	$numberingModules = [
		'digiriskelement/groupment' => $conf->global->DIGIRISKDOLIBARR_GROUPMENT_ADDON,
		'digiriskelement/workunit' => $conf->global->DIGIRISKDOLIBARR_WORKUNIT_ADDON,
	];

	list($modGroupment, $modWorkUnit) = saturne_require_objects_mod($numberingModules, $moduleNameLowerCase);

	saturne_header(1, '', $title, $helpUrl, '', 0, 0, $arrayofjs, $arrayofcss, $morequerystring, $morecssonbody);

	//Body navigation digirisk
	$object = new DigiriskElement($db);
	if ($conf->global->DIGIRISKDOLIBARR_SHOW_HIDDEN_DIGIRISKELEMENT) {
		$objects = $object->fetchAll('',  'ranks');
	} else {
		$objects = $object->fetchAll('',  'ranks',  0,  0, array('customsql' => 'status > 0 AND entity IN ('. $conf->entity .')'));
	}

	$digiriskElementTree = array();
	if (!is_array($objects) && $objects<0) {
		setEventMessages($object->error, $object->errors, 'errors');
	} elseif (is_array($objects) && count($objects)>0) {
		$digiriskElementTree = recurse_tree(0, 0, $objects);
	}
	?>

	<div id="id-container" class="id-container page-ut-gp-list">
		<input type="hidden" name="token" value="<?php echo newToken(); ?>">
		<div class="side-nav">
			<div class="side-nav-responsive"><i class="fas fa-bars"></i> <?php echo "Navigation UT/GP"; ?></div>
			<div id="id-left">
				<div class="digirisk-wrap wpeo-wrap">
					<div class="navigation-container">
						<div class="society-header">
							<a class="linkElement" href="<?php echo dol_buildpath('/custom/digiriskdolibarr/view/digiriskstandard/digiriskstandard_card.php?id=' . $conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD, 1);?>">
								<span class="icon fas fa-building fa-fw"></span>
								<div class="title"><?php echo $conf->global->MAIN_INFO_SOCIETE_NOM ?></div>
							</a>
                            <?php if ($user->rights->digiriskdolibarr->digiriskelement->write) : ?>
                                <div class="add-container">
                                    <a id="newGroupment" href="<?php echo dol_buildpath('/custom/digiriskdolibarr/view/digiriskelement/digiriskelement_card.php?action=create&element_type=groupment&fk_parent=0', 1);?>">
                                        <div class="wpeo-button button-square-40 button-secondary wpeo-tooltip-event" data-direction="bottom" data-color="light" aria-label="<?php echo $langs->trans('NewGroupment'); ?>"><strong><?php echo $modGroupment->prefix; ?></strong><span class="button-add animated fas fa-plus-circle"></span></div>
                                    </a>
                                    <a id="newWorkunit" href="<?php echo dol_buildpath('/custom/digiriskdolibarr/view/digiriskelement/digiriskelement_card.php?action=create&element_type=workunit&fk_parent=0', 1);?>">
                                        <div class="wpeo-button button-square-40 wpeo-tooltip-event" data-direction="bottom" data-color="light" aria-label="<?php echo $langs->trans('NewWorkUnit'); ?>"><strong><?php echo $modWorkUnit->prefix; ?></strong><span class="button-add animated fas fa-plus-circle"></span></div>
                                    </a>
                                </div>
                            <?php endif; ?>
						</div>
						<?php if ( ! empty($objects) && $objects > 0) : ?>
							<div class="toolbar">
								<div class="toggle-plus tooltip hover" aria-label="<?php echo $langs->trans('UnwrapAll'); ?>"><span class="icon fas fa-plus-square"></span></div>
								<div class="toggle-minus tooltip hover" aria-label="<?php echo $langs->trans('WrapAll'); ?>"><span class="icon fas fa-minus-square"></span></div>
							</div>
						<?php else : ?>
							<div class="society-header">
								<a id="newGroupment" href="<?php echo dol_buildpath('/custom/digiriskdolibarr/view/digiriskelement/digiriskelement_card.php?action=create&element_type=groupment&fk_parent=0', 1);?>">
									<div class="wpeo-button button-square-40 button-secondary wpeo-tooltip-event" data-direction="bottom" data-color="light" aria-label="<?php echo $langs->trans('NewGroupment'); ?>"><strong><?php echo $modGroupment->prefix; ?></strong><span class="button-add animated fas fa-plus-circle"></span></div>
								</a>
								<a id="newWorkunit" href="<?php echo dol_buildpath('/custom/digiriskdolibarr/view/digiriskelement/digiriskelement_card.php?action=create&element_type=workunit&fk_parent=0', 1);?>">
									<div class="wpeo-button button-square-40 wpeo-tooltip-event" data-direction="bottom" data-color="light" aria-label="<?php echo $langs->trans('NewWorkUnit'); ?>"><strong><?php echo $modWorkUnit->prefix; ?></strong><span class="button-add animated fas fa-plus-circle"></span></div>
								</a>
							</div>
						<?php endif; ?>

						<ul class="workunit-list">
							<script>
								if (localStorage.maximized == 'false') {
									$('#id-left').attr('style', 'display:none !important')
								}
							</script>
							<?php display_recurse_tree($digiriskElementTree); ?>
							<script>
								// Get previous menu to display it
								var MENU = localStorage.menu;
								if (MENU == null || MENU == '') {
									MENU = new Set()
								} else {
									MENU = JSON.parse(MENU);
									MENU = new Set(MENU);
								}

								MENU.forEach((id) =>  {
									jQuery( '#menu'+id).removeClass( 'fa-chevron-right').addClass( 'fa-chevron-down' );
									jQuery( '#unit'+id ).addClass( 'toggled' );
								});

								<?php $object->fetch(GETPOST('id') ?: GETPOST('fromid')); ?>
								var idParent = <?php echo json_encode($object->fk_parent);?> ;

								jQuery( '#menu'+idParent).removeClass( 'fa-chevron-right').addClass( 'fa-chevron-down' );
								jQuery( '#unit'+idParent ).addClass( 'toggled' );

								// Set active unit active
								jQuery( '.digirisk-wrap .navigation-container .unit.active' ).removeClass( 'active' );

								var params = new window.URLSearchParams(window.location.search);
								var id = params.get('id');
								id = !id ? params.get('fromid') : id

								if ((document.URL.match(/digiriskelement/) || document.URL.match(/accident/)) && !document.URL.match(/type=standard/)) {
									var elementBranch = <?php echo json_encode($object->getBranch(GETPOST('id'))); ?>;
									elementBranch.forEach((id) =>  {
										jQuery( '#menu'+id).removeClass( 'fa-chevron-right').addClass( 'fa-chevron-down' );
										jQuery( '#unit'+id ).addClass( 'toggled' );
									});

									jQuery( '#unit'  + id ).addClass( 'active' );
									jQuery( '#unit'  + id ).closest( '.unit' ).attr( 'value', id );

									var container = jQuery('.navigation-container');
									$(container).animate({
										scrollTop: $("#unit"  + id).offset().top - 100
									}, 500);
								}
							</script>
						</ul>
					</div>
				</div>
			</div>
		</div>
	<?php
	// main area
	if ($replacemainareaby) {
		print $replacemainareaby;
		return;
	}
	main_area($title);
}

/**
 * Recursive tree process
 *
 * @param  int   $parentID                  Element parent id of Digirisk Element object
 * @param  int   $depth                     Depth of tree
 * @param  array $digiriskElements          Global Digirisk Element list
 * @param  bool  $addCurrentDigiriskElement Add current digirisk element info
 * @return array $tree                      Global Digirisk Element list after recursive process
 */
function recurse_tree(int $parentID, int $depth, array $digiriskElements, bool $addCurrentDigiriskElement = false): array
{
    $tree = [];

    foreach ($digiriskElements as $digiriskElement) {
        if ($digiriskElement->fk_parent == $parentID || ($digiriskElement->id == $parentID && $addCurrentDigiriskElement)) {
            $tree[$digiriskElement->id] = [
                'id'       => $digiriskElement->id,
                'depth'    => $depth,
                'object'   => $digiriskElement,
                'children' => recurse_tree($digiriskElement->id, $depth + 1, $digiriskElements)
            ];
        }
    }

    return $tree;
}

function flatten_tree($tree)
{
    $flat = [];

    foreach ($tree as $node) {
        $flat[$node['id']] = [
            'object' => $node['object'],
            'depth'  => $node['depth']
        ];

        if (!empty($node['children'])) {
            $flat += flatten_tree($node['children']);
        }
    }

    return $flat;
}

/**
 *	Display Recursive tree process
 *
 * @param	array $digiriskElementTree Global Digirisk Element list after recursive process
 * @return	void
 */
function display_recurse_tree($digiriskElementTree)
{
	include_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

	global $conf, $langs, $user, $moduleNameLowerCase;

	$numberingModules = [
		'digiriskelement/groupment' => $conf->global->DIGIRISKDOLIBARR_GROUPMENT_ADDON,
		'digiriskelement/workunit' => $conf->global->DIGIRISKDOLIBARR_WORKUNIT_ADDON,
	];

	list($modGroupment, $modWorkUnit) = saturne_require_objects_mod($numberingModules, $moduleNameLowerCase);

	if ($user->rights->digiriskdolibarr->digiriskelement->read) {
		if ( ! empty($digiriskElementTree)) {
            $riskType = GETPOSTISSET('risk_type') && !empty(GETPOST('risk_type')) ? GETPOST('risk_type') : 'risk';
			foreach ($digiriskElementTree as $element) { ?>
				<?php if ($element['object']->id == $conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH) : ?>
				<hr>
				<?php endif; ?>
			<li class="unit type-<?php echo $element['object']->element_type; ?>" id="unit<?php  echo $element['object']->id; ?>">
				<div class="unit-container">
					<?php if ($element['object']->element_type == 'groupment' && count($element['children'])) { ?>
					<div class="toggle-unit">
						<i class="toggle-icon fas fa-chevron-right" id="menu<?php echo $element['object']->id;?>"></i>
					</div>
					<?php } else { ?>
					<div class="spacer"></div>
					<?php }
					print '<span class="open-media-gallery add-media modal-open photo digirisk-element-photo-'. $element['object']->id .'" value="0">';
					print '<input type="hidden" class="modal-options" data-modal-to-open="media_gallery" data-from-id="'. $element['object']->id .'" data-from-type="'. $element['object']->element_type .'" data-from-subtype="photo" data-from-subdir="" data-photo-class="digirisk-element-photo-'. $element['object']->id .'"/>';
					print saturne_show_medias_linked('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/' . $element['object']->element_type . '/' . $element['object']->ref, 'small', 1, 0, 0, 0, 50, 50, 1, 0, 0, $element['object']->element_type . '/' . $element['object']->ref, $element['object'], 'photo', 0, 0, 0, 1, 'cursorpointer');
					print '</span>';
					?>
					<div class="title" id="scores" value="<?php echo $element['object']->id ?>">
						<?php
						if ($user->rights->digiriskdolibarr->risk->read) : ?>
							<a id="slider" class="linkElement id<?php echo $element['object']->id;?>" href="<?php echo dol_buildpath('/custom/digiriskdolibarr/view/digiriskelement/digiriskelement_risk.php?id=' . $element['object']->id . '&risk_type=' . $riskType, 1);?>">
								<span class="title-container">
									<span class="ref"><?php echo $element['object']->ref; ?></span>
									<span class="name"><?php echo dol_trunc($element['object']->label, 20); ?></span>
								</span>
							</a>
						<?php else : ?>
							<a id="slider" class="linkElement id<?php echo $element['object']->id;?>" href="<?php echo dol_buildpath('/custom/digiriskdolibarr/view/digiriskelement/digiriskelement_card.php?id=' . $element['object']->id, 1);?>">
								<span class="title-container">
									<span class="ref"><?php echo $element['object']->ref; ?></span>
									<span class="name"><?php echo dol_trunc($element['object']->label, 20); ?></span>
								</span>
							</a>
						<?php endif; ?>
					</div>
						<?php if ($user->rights->digiriskdolibarr->digiriskelement->write) : ?>
							<?php if ($element['object']->element_type == 'groupment') : ?>
							<div class="add-container">
								<a id="newGroupment" href="<?php echo dol_buildpath('/custom/digiriskdolibarr/view/digiriskelement/digiriskelement_card.php?action=create&element_type=groupment&fk_parent=' . $element['object']->id, 1);?>">
									<div
										class="wpeo-button button-secondary button-square-40 wpeo-tooltip-event"
										data-direction="bottom" data-color="light"
										aria-label="<?php echo $langs->trans('NewGroupment'); ?>">
										<strong><?php echo $modGroupment->prefix; ?></strong>
										<span class="button-add animated fas fa-plus-circle"></span>
									</div>
								</a>
								<a id="newWorkunit" href="<?php echo dol_buildpath('/custom/digiriskdolibarr/view/digiriskelement/digiriskelement_card.php?action=create&element_type=workunit&fk_parent=' . $element['object']->id, 1);?>">
									<div
										class="wpeo-button button-square-40 wpeo-tooltip-event"
										data-direction="bottom" data-color="light"
										aria-label="<?php echo $langs->trans('NewWorkUnit'); ?>">
										<strong><?php echo $modWorkUnit->prefix; ?></strong>
										<span class="button-add animated fas fa-plus-circle"></span>
									</div>
								</a>
							</div>
							<?php endif; ?>
						<?php endif; ?>
				</div>
				<ul class="sub-list"><?php display_recurse_tree($element['children']) ?></ul>
			</li>
				<?php if ($element['object']->id == $conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH) : ?>
				<hr>
				<?php endif; ?>
			<?php }
		}
	} else {
		print $langs->trans('YouDontHaveTheRightToSeeThis');
	}
}

/**
 *	Display Recursive tree for edit
 *
* @param	array $digiriskElementTree Global Digirisk Element list after recursive process
* @param 	int   $i
* @return	void
*/
function display_recurse_tree_organization($digiriskElementTree, $i = 1)
{
	global $langs, $user;

	if ($user->rights->digiriskdolibarr->digiriskelement->read) {
		if ( ! empty($digiriskElementTree)) {
			foreach ($digiriskElementTree as $element) { ?>
				<li class="route ui-sortable-handle level-<?php echo $i ?>" id="<?php  echo $element['object']->id; ?>" value="<?php echo $i ?>">
					 <h3 class='title <?php echo $element['object']->element_type ?>'>
						<span class="ref"><?php echo  $element['object']->ref; ?></span><?php echo $element['object']->label; ?>
					  </h3>
					 <span class='ui-icon ui-icon-arrow-4-diag'></span>
					<ul class="space space-<?php echo $i; ?> ui-sortable  <?php echo $element['object']->element_type ?>" id="space<?php echo $element['object']->id?>" value="<?php echo $i ?>"><?php display_recurse_tree_organization($element['children'], $i + 1) ?></ul>
				</li>
			<?php }
		}
	} else {
		print $langs->trans('YouDontHaveTheRightToSeeThis');
	}
}

/**
*  Return a link to the user card (with optionaly the picto)
*  Use this->id,this->lastname, this->firstname
*
* @param  User   $object                    User object
* @param  int	 $withpictoimg				Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto, -1=Include photo into link, -2=Only picto photo, -3=Only photo very small)
* @param  string $option					On what the link point to ('leave', 'nolink', )
* @param  int    $infologin      			0=Add default info tooltip, 1=Add complete info tooltip, -1=No info tooltip
* @param  int	 $notooltip					1=Disable tooltip on picto and name
* @param  int	 $maxlen					Max length of visible username
* @param  int	 $hidethirdpartylogo		Hide logo of thirdparty if user is external user
* @param  string $mode               		''=Show firstname and lastname, 'firstname'=Show only firstname, 'firstelselast'=Show firstname or lastname if not defined, 'login'=Show login
* @param  string $morecss            		Add more css on link
* @param  int    $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
* @param  int    $display_initials          Show only initials for firstname/lastname of user
* @return string							String with URL
*/
function getNomUrlUser(User $object, $withpictoimg = 0, $option = '', $infologin = 0, $notooltip = 0, $maxlen = 24, $hidethirdpartylogo = 0, $mode = '', $morecss = '', $save_lastsearch_value = -1, $display_initials = 1)
{
	global $langs, $conf, $db, $hookmanager, $dolibarr_main_demo;
	global $menumanager;

	if ( ! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) && $withpictoimg) $withpictoimg = 0;

	$result = ''; $label = '';

	if ( ! empty($object->photo)) {
		$label .= '<div class="photointooltip">';
		$label .= Form::showphoto('userphoto', $object, 0, 60, 0, 'photowithmargin photologintooltip', 'small', 0, 1); // Force height to 60 so we total height of tooltip can be calculated and collision can be managed
		$label .= '</div><div style="clear: both;"></div>';
	}

	// Info Login
	$company = '';
	$companylink = '';
	$label                               .= '<div class="centpercent">';
	$label                               .= '<u>' . $langs->trans("User") . '</u><br>';
	$label                               .= '<b>' . $langs->trans('Name') . ':</b> ' . $object->getFullName($langs, '');
	if ( ! empty($object->login)) $label .= '<br><b>' . $langs->trans('Login') . ':</b> ' . $object->login;
	if ( ! empty($object->job)) $label   .= '<br><b>' . $langs->trans("Job") . ':</b> ' . $object->job;
	$label                               .= '<br><b>' . $langs->trans("Email") . ':</b> ' . $object->email;
	if ( ! empty($object->phone)) $label .= '<br><b>' . $langs->trans("Phone") . ':</b> ' . $object->phone;
	if ( ! empty($object->admin))
		$label                           .= '<br><b>' . $langs->trans("Administrator") . '</b>: ' . yn($object->admin);
	if ( ! empty($object->socid)) {	// Add thirdparty for external users
		$thirdpartystatic = new Societe($db);
		$thirdpartystatic->fetch($object->socid);
		if (empty($hidethirdpartylogo)) $companylink = ' ' . $thirdpartystatic->getNomUrl(2, (($option == 'nolink') ? 'nolink' : '')); // picto only of company
		$company                                     = ' (' . $langs->trans("Company") . ': ' . $thirdpartystatic->name . ')';
	}
	$type   = ($object->socid ? $langs->trans("External") . $company : $langs->trans("Internal"));
	$label .= '<br><b>' . $langs->trans("Type") . ':</b> ' . $type;
	$label .= '<br><b>' . $langs->trans("Status") . '</b>: ' . $object->getLibStatut(4);
	$label .= '</div>';
	if ($infologin > 0) {
		$label                                                        .= '<br>';
		$label                                                        .= '<br><u>' . $langs->trans("Session") . '</u>';
		$label                                                        .= '<br><b>' . $langs->trans("IPAddress") . '</b>: ' . $_SERVER["REMOTE_ADDR"];
		if ( ! empty($conf->global->MAIN_MODULE_MULTICOMPANY)) $label .= '<br><b>' . $langs->trans("ConnectedOnMultiCompany") . ':</b> ' . $conf->entity . ' (user entity ' . $object->entity . ')';
		$label                                                        .= '<br><b>' . $langs->trans("AuthenticationMode") . ':</b> ' . $_SESSION["dol_authmode"] . (empty($dolibarr_main_demo) ? '' : ' (demo)');
		$label                                                        .= '<br><b>' . $langs->trans("ConnectedSince") . ':</b> ' . dol_print_date($object->datelastlogin, "dayhour", 'tzuser');
		$label                                                        .= '<br><b>' . $langs->trans("PreviousConnexion") . ':</b> ' . dol_print_date($object->datepreviouslogin, "dayhour", 'tzuser');
		$label                                                        .= '<br><b>' . $langs->trans("CurrentTheme") . ':</b> ' . $conf->theme;
		$label                                                        .= '<br><b>' . $langs->trans("CurrentMenuManager") . ':</b> ' . $menumanager->name;
		$s                                                             = picto_from_langcode($langs->getDefaultLang());
		$label                                                        .= '<br><b>' . $langs->trans("CurrentUserLanguage") . ':</b> ' . ($s ? $s . ' ' : '') . $langs->getDefaultLang();
		$label                                                        .= '<br><b>' . $langs->trans("Browser") . ':</b> ' . $conf->browser->name . ($conf->browser->version ? ' ' . $conf->browser->version : '') . ' (' . $_SERVER['HTTP_USER_AGENT'] . ')';
		$label                                                        .= '<br><b>' . $langs->trans("Layout") . ':</b> ' . $conf->browser->layout;
		$label                                                        .= '<br><b>' . $langs->trans("Screen") . ':</b> ' . $_SESSION['dol_screenwidth'] . ' x ' . $_SESSION['dol_screenheight'];
		if ($conf->browser->layout == 'phone') $label                 .= '<br><b>' . $langs->trans("Phone") . ':</b> ' . $langs->trans("Yes");
		if ( ! empty($_SESSION["disablemodules"])) $label             .= '<br><b>' . $langs->trans("DisabledModules") . ':</b> <br>' . join(', ', explode(',', $_SESSION["disablemodules"]));
	}
	if ($infologin < 0) $label = '';

	$url                         = DOL_URL_ROOT . '/user/card.php?id=' . $object->id;
	if ($option == 'leave') $url = DOL_URL_ROOT . '/holiday/list.php?id=' . $object->id;

	if ($option != 'nolink') {
		// Add param to save lastsearch_values or not
		$add_save_lastsearch_values                                                                                      = ($save_lastsearch_value == 1 ? 1 : 0);
		if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
		if ($add_save_lastsearch_values) $url                                                                           .= '&save_lastsearch_values=1';
	}

	$linkclose = "";
	if ($option == 'blank') {
		$linkclose .= ' target=_blank';
	}
	$linkstart = '<a href="' . $url . '"';
	if (empty($notooltip)) {
		if ( ! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
			$langs->load("users");
			$label      = $langs->trans("ShowUser");
			$linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
		}
		$linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
		$linkclose .= ' class="classfortooltip' . ($morecss ? ' ' . $morecss : '') . '"';

		/*
		 $hookmanager->initHooks(array('userdao'));
		 $parameters=array('id'=>$object->id);
		 $reshook=$hookmanager->executeHooks('getnomurltooltip',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
		 if ($reshook > 0) $linkclose = $hookmanager->resPrint;
		 */
	}

	$linkstart .= $linkclose . '>';
	$linkend    = '</a>';

	//if ($withpictoimg == -1) $result.='<div class="nowrap">';
	$result .= (($option == 'nolink') ? '' : $linkstart);
	if ($withpictoimg) {
		$paddafterimage                              = '';
		if (abs($withpictoimg) == 1) $paddafterimage = 'style="margin-' . ($langs->trans("DIRECTION") == 'rtl' ? 'left' : 'right') . ': 3px;"';
		// Only picto
		if ($withpictoimg > 0) $picto = '<!-- picto user --><span class="nopadding userimg' . ($morecss ? ' ' . $morecss : '') . '">' . img_object('', 'user', $paddafterimage . ' ' . ($notooltip ? '' : 'class="paddingright classfortooltip"'), 0, 0, $notooltip ? 0 : 1) . '</span>';
		// Picto must be a photo
		else $picto = '<!-- picto photo user --><span class="nopadding userimg' . ($morecss ? ' ' . $morecss : '') . '"' . ($paddafterimage ? ' ' . $paddafterimage : '') . '>' . Form::showphoto('userphoto', $object, 0, 0, 0, 'userphoto' . ($withpictoimg == -3 ? 'small' : ''), 'mini', 0, 1) . '</span>';
		$result    .= $picto;
	}

	if ($withpictoimg > -2 && $withpictoimg != 2 && $display_initials) {
		$initials = '';
		if (dol_strlen($object->firstname)) {
			$initials .= str_split($object->firstname, 1)[0];
		}
		if (dol_strlen($object->lastname)) {
			$initials .= str_split($object->lastname, 1)[0];
		}
		if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) $result .= '<span class=" nopadding usertext' . (( ! isset($object->statut) || $object->statut) ? '' : ' strikefordisabled') . ($morecss ? ' ' . $morecss : '') . '">';
		if ($mode == 'login') $result                                  .= $initials;
		else $result                                                   .= $initials;
		if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) $result .= '</span>';
	} elseif ($display_initials == 0) {
		if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
			$result .= '<span class="nopadding usertext' . (( ! isset($object->statut) || $object->statut) ? '' : ' strikefordisabled') . ($morecss ? ' ' . $morecss : '') . '">';
		}
		if ($mode == 'login') {
			$result .= dol_string_nohtmltag(dol_trunc($object->login, $maxlen));
		} else {
			$result .= dol_string_nohtmltag($object->getFullName($langs, '', ($mode == 'firstelselast' ? 3 : ($mode == 'firstname' ? 2 : -1)), $maxlen));
		}
		if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
			$result .= '</span>';
		}
	}
	$result .= (($option == 'nolink') ? '' : $linkend);
	//if ($withpictoimg == -1) $result.='</div>';

	$result .= $companylink;

	global $action;
	$hookmanager->initHooks(array('userdao'));
	$parameters               = array('id' => $object->id, 'getnomurluser' => $result);
	$reshook                  = $hookmanager->executeHooks('getNomUrlUser', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
	if ($reshook > 0) $result = $hookmanager->resPrint;
	else $result             .= $hookmanager->resPrint;

	return $result;
}

/**
 *	Show category image
 *
* @param object	$object
* @param string $upload_dir
*/
function show_category_image($object, $upload_dir, $noprint = 0)
{

	global $langs;
	$nbphoto = 0;
	$nbbyrow = 5;

	$maxWidth  = 160;
	$maxHeight = 120;

	$pdir = get_exdir($object->id, 2, 0, 0, $object, 'category') . $object->id . "/photos/";
	$dir  = $upload_dir . '/' . $pdir;

	$listofphoto = $object->liste_photos($dir);
	if (is_array($listofphoto) && count($listofphoto)) {
		//      print '<br>';
		//      print '<table width="100%" valign="top" align="center">';

		foreach ($listofphoto as $key => $obj) {
			$nbphoto++;

			//          if ($nbbyrow && ($nbphoto % $nbbyrow == 1)) print '<tr align=center valign=middle border=1>';
			//          if ($nbbyrow) print '<td width="'.ceil(100 / $nbbyrow).'%" class="">';


			// Si fichier vignette disponible, on l'utilise, sinon on utilise photo origine
			if ($obj['photo_vignette']) {
				$filename = $obj['photo_vignette'];
			} else {
				$filename = $obj['photo'];
			}

			// Nom affiche
			//$viewfilename = $obj['photo'];

			// Taille de l'image
			$object->get_image_size($dir . $filename);
			$imgWidth  = ($object->imgWidth < $maxWidth) ? $object->imgWidth : $maxWidth;
			$imgHeight = ($object->imgHeight < $maxHeight) ? $object->imgHeight : $maxHeight;

			if ($noprint) {
				$out = '<img border="0" width="' . $imgWidth . '" height="' . $imgHeight . '" src="' . DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=category&entity=' . $object->entity . '&file=' . urlencode($pdir . $filename) . '">';
			} else {
				print '<img border="0" width="' . $imgWidth . '" height="' . $imgHeight . '" src="' . DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=category&entity=' . $object->entity . '&file=' . urlencode($pdir . $filename) . '">';
			}

			//          if ($nbbyrow) print '</td>';
			//          if ($nbbyrow && ($nbphoto % $nbbyrow == 0)) print '</tr>';

		}

		// Ferme tableau
		while ($nbphoto % $nbbyrow) {
			$nbphoto++;
		}

		//      print '</table>';
	}

	if ($nbphoto < 1) {
		if (!$noprint) {
			print '<div class="opacitymedium">' . $langs->trans("NoPhotoYet") . "</div>";
		}
	}

	if ($noprint) {
		return $out;
	}
}

/**
* @param $sdir
* @param string $size
* @param int $maxHeight
* @param int $maxWidth
* @return string
*/
function digirisk_show_medias($sdir, $size = '', $maxHeight = 80, $maxWidth = 80)
{
	global $conf, $langs;

	include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
	include_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

	$sortfield = 'date';
	$sortorder = 'desc';
	$dir       = $sdir . '/';

	$return  = '<!-- Photo -->' . "\n";
	$nbphoto = 0;

	$filearray = dol_dir_list($dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, SORT_DESC, 1);
	$j         = 0;

	if (count($filearray)) {
		print '<div class="wpeo-gridlayout grid-4 grid-gap-3 grid-margin-2 ecm-photo-list ecm-photo-list">';
		if ($sortfield && $sortorder) {
			$filearray = dol_sort_array($filearray, $sortfield, $sortorder);
		}
		foreach ($filearray as $key => $val) {
			if (preg_match('/' . $size . '/', $val['name'])) {
				$file = $val['name'];

				if (image_format_supported($file) >= 0) {
					$nbphoto++;

					if ($size == 'small') {   // Format vignette
						$relativepath = 'digiriskdolibarr/medias/thumbs';
						$modulepart   = 'ecm';
						$path         = DOL_URL_ROOT . '/document.php?modulepart=' . $modulepart . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath);
						?>

						<div class="center clickable-photo clickable-photo<?php echo $j; ?>" value="<?php echo $j; ?>" element="risk-evaluation">
							<figure class="photo-image">
								<?php
								$urladvanced = getAdvancedPreviewUrl($modulepart, 'digiriskdolibarr/medias/' . preg_replace('/_' . $size . '/', '', $val['relativename']), 0, 'entity=' . $conf->entity); ?>
								<a class="clicked-photo-preview" href="<?php echo $urladvanced; ?>">
									<div class="wpeo-button button-square-30 button-transparent wpeo-tooltip-event" aria-label="<?php echo $langs->trans('Preview'); ?>">
										<i class="fas fa-search-plus"></i>
									</div>
								</a>
								<?php if (image_format_supported($val['name']) >= 0) : ?>
									<?php $fullpath = $path . '/' . urlencode($val['relativename']) . '&entity=' . $conf->entity; ?>
								<input class="filename" type="hidden" value="<?php echo preg_replace('/_' . $size . '/', '', $val['name']) ?>">
								<img class="photo photo<?php echo $j ?>" height="<?php echo $maxHeight; ?>" width="<?php echo $maxWidth; ?>" src="<?php echo $fullpath; ?>">
								<?php endif; ?>
							</figure>
							<div class="title"><?php echo preg_replace('/_' . $size . '/', '', $val['name']); ?></div>
						</div><?php
						$j++;
					}
				}
			}
		}
		print '</div>';
	} else {
		// Display media library is empty if no media uploaded
		if (!is_array($_FILES['userfile']['tmp_name'])) {
			print($langs->trans("EmptyMediaLibrary"));
		}
	}

	return $return;
}

/**
 *  Show photos of an object (nbmax maximum), into several columns
 *
 *  @param		string	$modulepart		'product', 'ticket', ...
 *  @param      string	$sdir        	Directory to scan (full absolute path)
 *  @param      string	$size        	0=original size, 1='small' use thumbnail if possible
 *  @param      int		$nbmax       	Nombre maximum de photos (0=pas de max)
 *  @param      int		$nbbyrow     	Number of image per line or -1 to use div. Used only if size=1.
 * 	@param		int		$showfilename	1=Show filename
 * 	@param		int		$showaction		1=Show icon with action links (resize, delete)
 * 	@param		int		$maxHeight		Max height of original image when size='small' (so we can use original even if small requested). If 0, always use 'small' thumb image.
 * 	@param		int		$maxWidth		Max width of original image when size='small'
 *  @param      int     $nolink         Do not add a href link to view enlarged imaged into a new tab
 *  @param      int     $notitle        Do not add title tag on image
 *  @param		int		$usesharelink	Use the public shared link of image (if not available, the 'nophoto' image will be shown instead)
 *  @param		string  $subdir			Subdirectory to scan
 *  @param		object	$object			Object element
 *  @return     string					Html code to show photo. Number of photos shown is saved in this->nbphoto
 */
function digirisk_show_medias_linked($modulepart, $sdir, $size = '', $nbmax = 0, $nbbyrow = 5, $showfilename = 0, $showaction = 0, $maxHeight = 120, $maxWidth = 160, $nolink = 0, $notitle = 0, $usesharelink = 0, $subdir = "", $object = null)
{
	global $conf, $langs;

	include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
	include_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

	$sortfield = 'position_name';
	$sortorder = 'desc';

	$dir  = $sdir . '/' . $object->ref . '/';
	$pdir = $subdir . '/' . $object->ref . '/';

	// Defined relative dir to DOL_DATA_ROOT
	if ($dir) {
		$relativedir = preg_replace('/^' . preg_quote(DOL_DATA_ROOT, '/') . '/', '', $dir);
		$relativedir = preg_replace('/^[\\/]/', '', $relativedir);
		preg_replace('/[\\/]$/', '', $relativedir);
	}

	$dirthumb  = $dir . 'thumbs/';
	$pdirthumb = $pdir . 'thumbs/';

	$return  = '<!-- Photo -->' . "\n";
	$nbphoto = 0;

	$filearray = dol_dir_list($dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, SORT_DESC, 1);
	if (count($filearray)) {
		if ($sortfield && $sortorder) {
			$filearray = dol_sort_array($filearray, $sortfield, $sortorder);
		}
		$return .= '<div class=" wpeo-gridlayout grid-4 grid-gap-3 grid-margin-2 valigntop center centpercent" style="height:50%; border: 0; padding: 2px; border-spacing: 2px; border-collapse: separate;">';

		foreach ($filearray as $key => $val) {
			$return .= '<div class="media-container">';
			$photo   = '';
			$file    = $val['name'];

			//if (! utf8_check($file)) $file=utf8_encode($file);	// To be sure file is stored in UTF8 in memory

			//if (dol_is_file($dir.$file) && image_format_supported($file) >= 0)
			if (image_format_supported($file) >= 0) {
				$nbphoto++;
				$photo        = $file;
				$viewfilename = $file;

				if ($size == 1 || $size == 'small') {   // Format vignette
					// Find name of thumb file
					$photo_vignette                                                  = basename(getImageFileNameForSize($dir . $file, '_small'));
					if ( ! dol_is_file($dirthumb . $photo_vignette)) $photo_vignette = '';

					// Get filesize of original file
					$imgarray = dol_getImageSize($dir . $photo);

					if ($nbbyrow > 0) {
						if ($nbphoto == 1) $return .= '<table class="valigntop center centpercent" style="border: 0; padding: 2px; border-spacing: 2px; border-collapse: separate;">';

						if ($nbphoto % $nbbyrow == 1) $return .= '<tr class="center valignmiddle" style="border: 1px">';
						$return                               .= '<td style="width: ' . ceil(100 / $nbbyrow) . '%" class="photo">';
					} elseif ($nbbyrow < 0) $return .= '<div class="inline-block">';

					$return .= "\n";

					$relativefile = preg_replace('/^\//', '', $pdir . $photo);
					if (empty($nolink)) {
						$urladvanced               = getAdvancedPreviewUrl($modulepart, $relativefile, 0, 'entity=' . $conf->entity);
						if ($urladvanced) $return .= '<a href="' . $urladvanced . '">';
						else $return              .= '<a href="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '" class="aphoto" target="_blank">';
					}

					// Show image (width height=$maxHeight)
					// Si fichier vignette disponible et image source trop grande, on utilise la vignette, sinon on utilise photo origine
					$alt               = $langs->transnoentitiesnoconv('File') . ': ' . $relativefile;
					$alt              .= ' - ' . $langs->transnoentitiesnoconv('Size') . ': ' . $imgarray['width'] . 'x' . $imgarray['height'];
					if ($notitle) $alt = '';
					if ($usesharelink) {
						if ($val['share']) {
							if (empty($maxHeight) || $photo_vignette && $imgarray['height'] > $maxHeight) {
								$return .= '<!-- Show original file (thumb not yet available with shared links) -->';
								$return .= '<img width="65" height="65" class="photo photowithmargin clicked-photo-preview" height="' . $maxHeight . '" src="' . DOL_URL_ROOT . '/viewimage.php?hashp=' . urlencode($val['share']) . '" title="' . dol_escape_htmltag($alt) . '">';
							} else {
								$return .= '<!-- Show original file -->';
								$return .= '<img  width="65" height="65" class="photo photowithmargin clicked-photo-preview" height="' . $maxHeight . '" src="' . DOL_URL_ROOT . '/viewimage.php?hashp=' . urlencode($val['share']) . '" title="' . dol_escape_htmltag($alt) . '">';
							}
						} else {
							$return .= '<!-- Show nophoto file (because file is not shared) -->';
							$return .= '<img  width="65" height="65" class="photo photowithmargin" height="' . $maxHeight . '" src="' . DOL_URL_ROOT . '/public/theme/common/nophoto.png" title="' . dol_escape_htmltag($alt) . '">';
						}
					} elseif (empty($maxHeight) || $photo_vignette && $imgarray['height'] > $maxHeight) {
						$return .= '<!-- Show thumb -->';
						$return .= '<img width="' . $maxWidth . '" height="' . $maxHeight . '" class="photo clicked-photo-preview"  src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdirthumb . $photo_vignette) . '" title="' . dol_escape_htmltag($alt) . '">';
					} else {
						$return .= '<!-- Show original file -->';
						$return .= '<img width="' . $maxWidth . '" height="' . $maxHeight . '" class="photo photowithmargin  clicked-photo-preview" height="' . $maxHeight . '" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '" title="' . dol_escape_htmltag($alt) . '">';
					}

					if (empty($nolink)) $return .= '</a>';
					$return                     .= "\n";
					if ($showfilename) $return  .= '<br>' . $viewfilename;
					if ($showaction) {
						$return .= '<br>';
						// On propose la generation de la vignette si elle n'existe pas et si la taille est superieure aux limites
						if ($photo_vignette && (image_format_supported($photo) > 0) && ($object->imgWidth > $maxWidth || $object->imgHeight > $maxHeight)) {
							$return .= '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=addthumb&amp;file=' . urlencode($pdir . $viewfilename) . '">' . img_picto($langs->trans('GenerateThumb'), 'refresh') . '&nbsp;&nbsp;</a>';
						}
					}
					$return .= "\n";

					if ($nbbyrow > 0) {
						$return                                 .= '</td>';
						if (($nbphoto % $nbbyrow) == 0) $return .= '</tr>';
					} elseif ($nbbyrow < 0) $return .= '</div>';
				}

				if (empty($size)) {     // Format origine
					$return .= '<img class="photo photowithmargin" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '">';

					if ($showfilename) $return .= '<br>' . $viewfilename;
				}

				// On continue ou on arrete de boucler ?
				if ($nbmax && $nbphoto >= $nbmax) break;
			}
			$return .= '<div>
				<div class="wpeo-button button-square-50 button-blue media-gallery-favorite" value="' . $object->id . '">
				<input class="element-linked-id" type="hidden" value="' . ($object->id > 0 ? $object->id : 0) . '">
				<input class="filename" type="hidden" value="' . $photo . '">
				<i class="' . (GETPOST('favorite') == $photo ? 'fas' : ($object->photo == $photo ? 'fas' : 'far')) . ' fa-star button-icon"></i>
			</div>
			<div class="wpeo-button button-square-50 button-grey media-gallery-unlink" value="' . $object->id . '">
				<input class="element-linked-id" type="hidden" value="' . ($object->id > 0 ? $object->id : 0) . '">
				<input class="filename" type="hidden" value="' . $photo . '">
				<i class="fas fa-unlink button-icon"></i>
			</div></div></div>';
		}
		$return .= "</div>\n";

		if ($size == 1 || $size == 'small') {
			if ($nbbyrow > 0) {
				// Ferme tableau
				while ($nbphoto % $nbbyrow) {
					$return .= '<td style="width: ' . ceil(100 / $nbbyrow) . '%">&nbsp;</td>';
					$nbphoto++;
				}

				if ($nbphoto) $return .= '</table>';
			}
		}
	} else {
		print $langs->trans('NoMediaLinked');
	}
	if (is_object($object)) {
		$object->nbphoto = $nbphoto;
	}
	return $return;
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
 * @throws Exception
*/
function fetchAllSocPeople($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
{
	global $db;

	dol_syslog(__METHOD__, LOG_DEBUG);

	$records = array();
	$errors  = array();

	$sql  = "SELECT c.rowid, c.entity, c.fk_soc, c.ref_ext, c.civility as civility_code, c.lastname, c.firstname,";
	$sql .= " c.address, c.statut, c.zip, c.town,";
	$sql .= " c.fk_pays as country_id,";
	$sql .= " c.fk_departement as state_id,";
	$sql .= " c.birthday,";
	$sql .= " c.poste, c.phone, c.phone_perso, c.phone_mobile, c.fax, c.email,";
	$sql .= " c.socialnetworks,";
	$sql .= " c.photo,";
	$sql .= " c.priv, c.note_private, c.note_public, c.default_lang, c.canvas,";
	$sql .= " c.fk_prospectcontactlevel, c.fk_stcommcontact, st.libelle as stcomm, st.picto as stcomm_picto,";
	$sql .= " c.import_key,";
	$sql .= " c.datec as date_creation, c.tms as date_modification,";
	$sql .= " co.label as country, co.code as country_code,";
	$sql .= " d.nom as state, d.code_departement as state_code,";
	$sql .= " u.rowid as user_id, u.login as user_login,";
	$sql .= " s.nom as socname, s.address as socaddress, s.zip as soccp, s.town as soccity, s.default_lang as socdefault_lang";
	$sql .= " FROM " . MAIN_DB_PREFIX . "socpeople as c";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_country as co ON c.fk_pays = co.rowid";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_departements as d ON c.fk_departement = d.rowid";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON c.rowid = u.fk_socpeople";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON c.fk_soc = s.rowid";
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_stcommcontact as st ON c.fk_stcommcontact = st.id';
	$sql .= " WHERE c.entity IN (" . getEntity('socpeople') . ")";
	// Manage filter
	$sqlwhere = array();
	if (count($filter) > 0) {
		foreach ($filter as $key => $value) {
			if ($key == 't.rowid') {
				$sqlwhere[] = $key . '=' . $value;
			} elseif (strpos($key, 'date') !== false) {
				$sqlwhere[] = $key . ' = \'' . $db->idate($value) . '\'';
			} elseif ($key == 'customsql') {
				$sqlwhere[] = $value;
			} else {
				$sqlwhere[] = $key . ' LIKE \'%' . $db->escape($value) . '%\'';
			}
		}
	}
	if (count($sqlwhere) > 0) {
		$sql .= ' AND (' . implode(' ' . $filtermode . ' ', $sqlwhere) . ')';
	}

	if ( ! empty($sortfield)) {
		$sql .= $db->order($sortfield, $sortorder);
	}
	if ( ! empty($limit)) {
		$sql .= ' ' . $db->plimit($limit, $offset);
	}
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i   = 0;
		while ($i < ($limit ? min($limit, $num) : $num)) {
			$obj = $db->fetch_object($resql);

			$record = new Contact($db);
			$record->setVarsFromFetchObj($obj);

			$records[$record->id] = $record;

			$i++;
		}
		$db->free($resql);

		return $records;
	} else {
		$errors[] = 'Error ' . $db->lasterror();
		dol_syslog(__METHOD__ . ' ' . join(',', $errors), LOG_ERR);

		return -1;
	}
}

/**
 * Return HTML code of the SELECT of list of all contacts (for a third party or all).
 * This also set the number of contacts found into $this->num
 *
 * @since 9.0 Add afterSelectContactOptions hook
 *
 * @param	int			$socid      	Id ot third party or 0 for all or -1 for empty list
 * @param string $selected Array of ID of pre-selected contact id
 * @param  string		$htmlname  	    Name of HTML field ('none' for a not editable field)
 * @param  int			$showempty     	0=no empty value, 1=add an empty value, 2=add line 'Internal' (used by user edit), 3=add an empty value only if more than one record into list
 * @param  array		$exclude        List of contacts id to exclude
 * @param	string		$limitto		Disable answers that are not id in this array list
 * @param	int		$showfunction   Add function into label
 * @param	string		$moreclass		Add more class to class style
 * @param	bool		$options_only	Return options only (for ajax treatment)
 * @param	int		$showsoc	    Add company into label
 * @param	int			$forcecombo		Force to use combo box (so no ajax beautify effect)
 * @param	array		$events			Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
 * @param	string		$moreparam		Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
 * @param	string		$htmlid			Html id to use instead of htmlname
 * @param	bool		$multiple		add [] in the name of element and add 'multiple' attribut
 * @param	int		$disableifempty Set tag 'disabled' on select if there is no choice
 * @param string $exclude_already_add
 * @return	 string|int						<0 if KO, Nb of contact in list if OK
 *
*/
function digirisk_selectcontacts($socid, $selected = '', $htmlname = 'contactid', $showempty = 0, $exclude = array(), $limitto = '', $showfunction = 0, $moreclass = '', $options_only = false, $showsoc = 0, $forcecombo = 0, $events = array(), $moreparam = '', $htmlid = '', $multiple = false, $disableifempty = 0, $exclude_already_add = '')
{
	global $conf, $langs, $hookmanager, $db;

	$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "companies"));

	if (empty($htmlid)) $htmlid = $htmlname;

//	if ($selected === '') $selected           = array();
//	elseif ( ! is_array($selected)) $selected = array($selected);
	$selected = array($selected);
	$out                                      = '';

	if ( ! is_object($hookmanager)) {
		include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
		$hookmanager = new HookManager($db);
	}

	// We search third parties
	$sql                                                                                        = "SELECT sp.rowid, sp.lastname, sp.statut, sp.firstname, sp.poste, sp.email, sp.phone, sp.phone_perso, sp.phone_mobile, sp.town AS contact_town";
	if ($showsoc > 0 || ! empty($conf->global->CONTACT_SHOW_EMAIL_PHONE_TOWN_SELECTLIST)) $sql .= ", s.nom as company, s.town AS company_town";
	$sql                                                                                       .= " FROM " . MAIN_DB_PREFIX . "socpeople as sp";
	if ($showsoc > 0 || ! empty($conf->global->CONTACT_SHOW_EMAIL_PHONE_TOWN_SELECTLIST)) $sql .= " LEFT OUTER JOIN  " . MAIN_DB_PREFIX . "societe as s ON s.rowid=sp.fk_soc";
	$sql                                                                                       .= " WHERE sp.entity IN (" . getEntity('socpeople') . ")";
	if ($socid > 0 || $socid == -1) $sql                                                       .= " AND sp.fk_soc=" . $socid;
	if ( ! empty($conf->global->CONTACT_HIDE_INACTIVE_IN_COMBOBOX)) $sql                       .= " AND sp.statut <> 0";
	$sql                                                                                       .= " ORDER BY sp.lastname ASC";

	//dol_syslog(get_class($this)."::select_contacts", LOG_DEBUG);
	$resql = $db->query($sql);

	if ($resql) {
		$num = $db->num_rows($resql);

		if ($conf->use_javascript_ajax && ! $forcecombo && ! $options_only) {
			include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
			$out .= ajax_combobox($htmlid, $events, $conf->global->CONTACT_USE_SEARCH_TO_SELECT);
		}

		if ($htmlname != 'none' && ! $options_only) {
			$out .= '<select class="flat' . ($moreclass ? ' ' . $moreclass : '') . '" id="' . $htmlid . '" name="' . $htmlname . (($num || empty($disableifempty)) ? '' : ' disabled') . ($multiple ? '[]' : '') . '" ' . ($multiple ? 'multiple' : '') . ' ' . ( ! empty($moreparam) ? $moreparam : '') . '>';
		}

		if ($showempty == 1 || ($showempty == 3 && $num > 1 && ! $multiple)) $out .= '<option value="0"' . (in_array(0, $selected) ? ' selected' : '') .  '>&nbsp;</option>';
		if ($showempty == 2) $out                                                   .= '<option value="0"' . (in_array(0, $selected) ? ' selected' : '') .  '>-- ' . $langs->trans("Internal") . ' --</option>';

		$i = 0;
		if ($num) {
			include_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
			$contactstatic = new Contact($db);

			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				// Set email (or phones) and town extended infos
				$extendedInfos = '';
				if ( ! empty($conf->global->CONTACT_SHOW_EMAIL_PHONE_TOWN_SELECTLIST)) {
					$extendedInfos                         = array();
					$email                                 = trim($obj->email);
					if ( ! empty($email)) $extendedInfos[] = $email;
					else {
						$phone                                        = trim($obj->phone);
						$phone_perso                                  = trim($obj->phone_perso);
						$phone_mobile                                 = trim($obj->phone_mobile);
						if ( ! empty($phone)) $extendedInfos[]        = $phone;
						if ( ! empty($phone_perso)) $extendedInfos[]  = $phone_perso;
						if ( ! empty($phone_mobile)) $extendedInfos[] = $phone_mobile;
					}
					$contact_town                                     = trim($obj->contact_town);
					$company_town                                     = trim($obj->company_town);
					if ( ! empty($contact_town)) $extendedInfos[]     = $contact_town;
					elseif ( ! empty($company_town)) $extendedInfos[] = $company_town;
					$extendedInfos                                    = implode(' - ', $extendedInfos);
					if ( ! empty($extendedInfos)) $extendedInfos      = ' - ' . $extendedInfos;
				}

				$contactstatic->id        = $obj->rowid;
				$contactstatic->lastname  = $obj->lastname;
				$contactstatic->firstname = $obj->firstname;
				if ($obj->statut == 1) {
					if ($htmlname != 'none') {
						$disabled                                                                               = 0;
						 $noTooltip                                                                             = 0;
						if (is_array($exclude) && count($exclude) && in_array($obj->rowid, $exclude)) $disabled = 1;
						if (is_array($exclude_already_add) && count($exclude_already_add) && in_array($obj->rowid, $exclude_already_add)) {
							$disabled  = 1;
							$noTooltip = 1;
						}
						if (is_array($limitto) && count($limitto) && ! in_array($obj->rowid, $limitto)) $disabled = 1;
						if ( ! empty($selected) && in_array($obj->rowid, $selected)) {
							$out                                      .= '<option value="' . $obj->rowid . '"';
							if ($disabled) $out                       .= ' disabled';
							$out                                      .= ' selected>';
							$out                                      .= $contactstatic->getFullName($langs) . $extendedInfos;
							if ($showfunction && $obj->poste) $out    .= ' (' . $obj->poste . ')';
							if (($showsoc > 0) && $obj->company) $out .= ' - (' . $obj->company . ')';
							if ($noTooltip == 0 && $disabled) $out    .= ' - (' . $langs->trans('NoEmailContact') . ')';
							$out                                      .= '</option>';
						} else {
							$out                                      .= '<option value="' . $obj->rowid . '"';
							if ($disabled) $out                       .= ' disabled';
							$out                                      .= '>';
							$out                                      .= $contactstatic->getFullName($langs) . $extendedInfos;
							if ($showfunction && $obj->poste) $out    .= ' (' . $obj->poste . ')';
							if (($showsoc > 0) && $obj->company) $out .= ' - (' . $obj->company . ')';
							if ($noTooltip == 0 && $disabled) $out    .= ' - (' . $langs->trans('NoEmailContact') . ')';
							$out                                      .= '</option>';
						}
					} elseif (in_array($obj->rowid, $selected)) {
						$out                                      .= $contactstatic->getFullName($langs) . $extendedInfos;
						if ($showfunction && $obj->poste) $out    .= ' (' . $obj->poste . ')';
						if (($showsoc > 0) && $obj->company) $out .= ' - (' . $obj->company . ')';
					}
				}
				$i++;
			}
		} else {
			$labeltoshow = ($socid != -1) ? ($langs->trans($socid ? "NoContactDefinedForThirdParty" : "NoContactDefined")) : $langs->trans('SelectAThirdPartyFirst');
			$out        .= '<option class="disabled" value="-1"' . (($showempty == 2 || $multiple) ? '' : ' selected') . ' disabled="disabled">';
			$out        .= $labeltoshow;
			$out        .= '</option>';
		}

//		$parameters = array(
//			'socid' => $socid,
//			'htmlname' => $htmlname,
//			'resql' => $resql,
//			'out' => &$out,
//			'showfunction' => $showfunction,
//			'showsoc' => $showsoc,
//		);

		//$reshook = $hookmanager->executeHooks('afterSelectContactOptions', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

		if ($htmlname != 'none' && ! $options_only) {
			$out .= '</select>';
		}

		return $out;
	} else {
		dol_print_error($db);
		return -1;
	}
}

/**
 * 	Return clickable name (with picto eventually)
 *
* @param $project
* @param	int		$withpicto		          0=No picto, 1=Include picto into link, 2=Only picto
* @param	string	$option			          Variant where the link point to ('', 'nolink')
* @param	int		$addlabel		          0=Default, 1=Add label into string, >1=Add first chars into string
* @param	string	$moreinpopup	          Text to add into popup
* @param	string	$sep			          Separator between ref and label if option addlabel is set
* @param	int   	$notooltip		          1=Disable tooltip
* @param  int     $save_lastsearch_value    -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
* @param	string	$morecss				  More css on a link
* @return	string					          String with URL
*/
function getNomUrlProject($project, $withpicto = 0, $option = '', $addlabel = 0, $moreinpopup = '', $sep = ' - ', $notooltip = 0, $save_lastsearch_value = -1, $morecss = '')
{
	global $conf, $langs, $user, $hookmanager;

	if ( ! empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

	$result = '';
	if ( ! empty($conf->global->PROJECT_OPEN_ALWAYS_ON_TAB)) {
		$option = $conf->global->PROJECT_OPEN_ALWAYS_ON_TAB;
	}

	$label                          = '';
	if ($option != 'nolink') $label = img_picto('', $project->picto) . ' <u class="paddingrightonly">' . $langs->trans("Project") . '</u>';
	if (isset($project->status)) {
		$label .= ' ' . $project->getLibStatut(5);
	}
	$label .= ($label ? '<br>' : '') . '<b>' . $langs->trans('Ref') . ': </b>' . $project->ref; // The space must be after the : to not being explode when showing the title in img_picto
	$label .= ($label ? '<br>' : '') . '<b>' . $langs->trans('Label') . ': </b>' . $project->title; // The space must be after the : to not being explode when showing the title in img_picto
	if (isset($project->public)) {
		$label .= '<br><b>' . $langs->trans("Visibility") . ":</b> " . ($project->public ? $langs->trans("SharedProject") : $langs->trans("PrivateProject"));
	}
	if ( ! empty($project->thirdparty_name)) {
		$label .= ($label ? '<br>' : '') . '<b>' . $langs->trans('ThirdParty') . ': </b>' . $project->thirdparty_name; // The space must be after the : to not being explode when showing the title in img_picto
	}
	if ( ! empty($project->dateo)) {
		$label .= ($label ? '<br>' : '') . '<b>' . $langs->trans('DateStart') . ': </b>' . dol_print_date($project->dateo, 'day'); // The space must be after the : to not being explode when showing the title in img_picto
	}
	if ( ! empty($project->datee)) {
		$label .= ($label ? '<br>' : '') . '<b>' . $langs->trans('DateEnd') . ': </b>' . dol_print_date($project->datee, 'day'); // The space must be after the : to not being explode when showing the title in img_picto
	}
	if ($moreinpopup) $label .= '<br>' . $moreinpopup;

	$url = '';
	if ($option != 'nolink') {
		if (preg_match('/\.php$/', $option)) {
			$url = dol_buildpath($option, 1) . '?id=' . $project->id;
		} elseif ($option == 'task') {
			$url = DOL_URL_ROOT . '/projet/tasks.php?id=' . $project->id;
		} elseif ($option == 'preview') {
			$url = DOL_URL_ROOT . '/projet/element.php?id=' . $project->id;
		} else {
			$url = DOL_URL_ROOT . '/projet/card.php?id=' . $project->id;
		}
		// Add param to save lastsearch_values or not
		$add_save_lastsearch_values                                                                                      = ($save_lastsearch_value == 1 ? 1 : 0);
		if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
		if ($add_save_lastsearch_values) $url                                                                           .= '&save_lastsearch_values=1';
	}

	$linkclose = '';
	if ($option == 'blank') {
		$linkclose .= ' target=_blank';
	}
	if (empty($notooltip) && $user->rights->projet->lire) {
		if ( ! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
			$label      = $langs->trans("ShowProject");
			$linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
		}
		$linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
		$linkclose .= ' class="classfortooltip' . ($morecss ? ' ' . $morecss : '') . '"';
	} else $linkclose = ($morecss ? ' class="' . $morecss . '"' : '');

	$picto                          = 'projectpub';
	if ( ! $project->public) $picto = 'project';

	$linkstart  = '<a href="' . $url . '"';
	$linkstart .= $linkclose . '>';
	$linkend    = '</a>';

	$result                      .= $linkstart;
	if ($withpicto) $result      .= img_object(($notooltip ? '' : $label), $picto, ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="' . (($withpicto != 2) ? 'paddingright ' : '') . 'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
	if ($withpicto != 2) $result .= $project->ref;
	$result                      .= $linkend;
	if ($withpicto != 2) $result .= (($addlabel && $project->title) ? $sep . dol_trunc($project->title, ($addlabel > 1 ? $addlabel : 0)) : '');

	global $action;
	$hookmanager->initHooks(array('projectdao'));
	$parameters               = array('id' => $project->id, 'getnomurl' => $result);
	$reshook                  = $hookmanager->executeHooks('getNomUrl', $parameters, $project, $action); // Note that $action and $object may have been modified by some hooks
	if ($reshook > 0) $result = $hookmanager->resPrint;
	else $result             .= $hookmanager->resPrint;

	return $result;
}

// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
/**
 *  Return a HTML select list of a dictionary
 *
 *  @param  string	$htmlname          	Name of select zone
 *  @param	string	$dictionarytable	Dictionary table
 *  @param	string	$keyfield			Field for key
 *  @param	string	$labelfield			Label field
 *  @param	string	$selected			Selected value
 *  @param  int		$useempty          	1=Add an empty value in list, 2=Add an empty value in list only if there is more than 2 entries.
 *  @param  string  $moreattrib         More attributes on HTML select tag
 * 	@return	void
 */
function digirisk_select_dictionary($htmlname, $dictionarytable, $keyfield = 'code', $labelfield = 'label', $selected = '', $useempty = 0, $moreattrib = '', $placeholder = '', $morecss = '')
{
	// phpcs:enable
	global $langs, $db;

	$langs->load("admin");

	$out = '';
	$sql  = "SELECT rowid, " . $keyfield . ", " . $labelfield;
	$sql .= " FROM " . MAIN_DB_PREFIX . $dictionarytable;
	$sql .= " ORDER BY " . $labelfield;

	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i   = 0;
		if ($num) {
			$out .= '<select id="select' . $htmlname . '" class="flat selectdictionary' . ($morecss ? ' ' . $morecss : '') . '" name="' . $htmlname . '"' . ($moreattrib ? ' ' . $moreattrib : '') . '>';
			if ($useempty == 1 || ($useempty == 2 && $num > 1)) {
				$out .= '<option value="-1">'. (dol_strlen($placeholder) > 0 ? $langs->transnoentities($placeholder) : '') .'&nbsp;</option>';
			}

			while ($i < $num) {
				$obj = $db->fetch_object($result);
				if ($selected == $obj->rowid || $selected == $langs->transnoentities($obj->$keyfield)) {
					$out .= '<option value="' . $langs->transnoentities($obj->$keyfield) . '" selected>';
				} else {
					$out .= '<option value="' . $langs->transnoentities($obj->$keyfield) . '">';
				}
				$out .= $langs->transnoentities($obj->$keyfield) . ' - ' .  $langs->transnoentities($obj->$labelfield);
				$out .= '</option>';
				$i++;
			}
			$out .= "</select>";
			$out .= ajax_combobox('select'.$htmlname);

		} else {
			$out .= $langs->trans("DictionaryEmpty");
		}
	} else {
		dol_print_error($db);
	}
	return $out;
}

/**
 *    	Return a link on thirdparty (with picto)
 *
* @param $thirdparty
* @param	int		$withpicto		          Add picto into link (0=No picto, 1=Include picto with link, 2=Picto only)
* @param	string	$option			          Target of link ('', 'customer', 'prospect', 'supplier', 'project')
* @param	int		$maxlen			          Max length of name
* @param	int  	$notooltip		          1=Disable tooltip
* @param  int     $save_lastsearch_value    -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
* @return	string					          String with URL
*/
function getNomUrlSociety($thirdparty, $withpicto = 0, $option = '', $maxlen = 0, $notooltip = 0, $save_lastsearch_value = -1)
{
	global $conf, $langs, $hookmanager;

	if ( ! empty($conf->dol_no_mouse_hover)) {
		$notooltip = 1; // Force disable tooltips
	}

	$name = $thirdparty->name ? $thirdparty->name : $thirdparty->nom;

	if ( ! empty($conf->global->SOCIETE_ON_SEARCH_AND_LIST_GO_ON_CUSTOMER_OR_SUPPLIER_CARD)) {
		if (empty($option) && $thirdparty->client > 0) {
			$option = 'customer';
		}
		if (empty($option) && $thirdparty->fournisseur > 0) {
			$option = 'supplier';
		}
	}

	if ( ! empty($conf->global->SOCIETE_ADD_REF_IN_LIST) && ( ! empty($withpicto))) {
		$code = '';
		if (($thirdparty->client) && ( ! empty($thirdparty->code_client)) && ($conf->global->SOCIETE_ADD_REF_IN_LIST == 1 || $conf->global->SOCIETE_ADD_REF_IN_LIST == 2)) {
			$code = $thirdparty->code_client . ' - ';
		}

		if (($thirdparty->fournisseur) && ( ! empty($thirdparty->code_fournisseur)) && ($conf->global->SOCIETE_ADD_REF_IN_LIST == 1 || $conf->global->SOCIETE_ADD_REF_IN_LIST == 3)) {
			$code .= $thirdparty->code_fournisseur . ' - ';
		}

		if ($code) {
			if ($conf->global->SOCIETE_ADD_REF_IN_LIST == 1) {
				$name = $code . ' ' . $name;
			} else {
				$name = $code;
			}
		}
	}

	if ( ! empty($thirdparty->name_alias)) {
		$name .= ' (' . $thirdparty->name_alias . ')';
	}

	$result = ''; $label = '';
	$linkstart = '';

	if ( ! empty($thirdparty->logo) && class_exists('Form')) {
		$label .= '<div class="photointooltip">';
		$label .= Form::showphoto('societe', $thirdparty, 0, 40, 0, '', 'mini', 0); // Important, we must force height so image will have height tags and if image is inside a tooltip, the tooltip manager can calculate height and position correctly the tooltip.
		$label .= '</div><div style="clear: both;"></div>';
	}

	$label .= '<div class="centpercent">';

	if ($option == 'customer' || $option == 'compta' || $option == 'category') {
		$label    .= img_picto('', $thirdparty->picto) . ' <u class="paddingrightonly">' . $langs->trans("Customer") . '</u>';
		$linkstart = '<a href="' . DOL_URL_ROOT . '/comm/card.php?socid=' . $thirdparty->id;
	} elseif ($option == 'prospect' && empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) {
		$label    .= img_picto('', $thirdparty->picto) . ' <u class="paddingrightonly">' . $langs->trans("Prospect") . '</u>';
		$linkstart = '<a href="' . DOL_URL_ROOT . '/comm/card.php?socid=' . $thirdparty->id;
	} elseif ($option == 'supplier' || $option == 'category_supplier') {
		$label    .= img_picto('', $thirdparty->picto) . ' <u class="paddingrightonly">' . $langs->trans("Supplier") . '</u>';
		$linkstart = '<a href="' . DOL_URL_ROOT . '/fourn/card.php?socid=' . $thirdparty->id;
	} elseif ($option == 'agenda') {
		$label    .= img_picto('', $thirdparty->picto) . ' <u class="paddingrightonly">' . $langs->trans("ThirdParty") . '</u>';
		$linkstart = '<a href="' . DOL_URL_ROOT . '/societe/agenda.php?socid=' . $thirdparty->id;
	} elseif ($option == 'project') {
		$label    .= img_picto('', $thirdparty->picto) . ' <u class="paddingrightonly">' . $langs->trans("ThirdParty") . '</u>';
		$linkstart = '<a href="' . DOL_URL_ROOT . '/societe/project.php?socid=' . $thirdparty->id;
	} elseif ($option == 'margin') {
		$label    .= img_picto('', $thirdparty->picto) . ' <u class="paddingrightonly">' . $langs->trans("ThirdParty") . '</u>';
		$linkstart = '<a href="' . DOL_URL_ROOT . '/margin/tabs/thirdpartyMargins.php?socid=' . $thirdparty->id . '&type=1';
	} elseif ($option == 'contact') {
		$label    .= img_picto('', $thirdparty->picto) . ' <u class="paddingrightonly">' . $langs->trans("ThirdParty") . '</u>';
		$linkstart = '<a href="' . DOL_URL_ROOT . '/societe/contact.php?socid=' . $thirdparty->id;
	} elseif ($option == 'ban') {
		$label    .= img_picto('', $thirdparty->picto) . ' <u class="paddingrightonly">' . $langs->trans("ThirdParty") . '</u>';
		$linkstart = '<a href="' . DOL_URL_ROOT . '/societe/paymentmodes.php?socid=' . $thirdparty->id;
	}

	// By default
	if (empty($linkstart)) {
		$label    .= img_picto('', $thirdparty->picto) . ' <u class="paddingrightonly">' . $langs->trans("ThirdParty") . '</u>';
		$linkstart = '<a href="' . DOL_URL_ROOT . '/societe/card.php?socid=' . $thirdparty->id;
	}
	if (isset($thirdparty->status)) {
		$label .= ' ' . $thirdparty->getLibStatut(5);
	}

	if ( ! empty($thirdparty->name)) {
		$label .= '<br><b>' . $langs->trans('Name') . ':</b> ' . dol_escape_htmltag($thirdparty->name);
		if ( ! empty($thirdparty->name_alias)) {
			$label .= ' (' . dol_escape_htmltag($thirdparty->name_alias) . ')';
		}
	}
	$label .= '<br><b>' . $langs->trans('Email') . ':</b> ' . $thirdparty->email;
	if ( ! empty($thirdparty->country_code)) {
		$label .= '<br><b>' . $langs->trans('Country') . ':</b> ' . $thirdparty->country_code;
	}
	if ( ! empty($thirdparty->tva_intra) || ( ! empty($conf->global->SOCIETE_SHOW_FIELD_IN_TOOLTIP) && strpos($conf->global->SOCIETE_SHOW_FIELD_IN_TOOLTIP, 'vatnumber') !== false)) {
		$label .= '<br><b>' . $langs->trans('VATIntra') . ':</b> ' . dol_escape_htmltag($thirdparty->tva_intra);
	}
	if ( ! empty($conf->global->SOCIETE_SHOW_FIELD_IN_TOOLTIP)) {
		if (strpos($conf->global->SOCIETE_SHOW_FIELD_IN_TOOLTIP, 'profid1') !== false) {
			$label .= '<br><b>' . $langs->trans('ProfId1' . $thirdparty->country_code) . ':</b> ' . $thirdparty->idprof1;
		}
		if (strpos($conf->global->SOCIETE_SHOW_FIELD_IN_TOOLTIP, 'profid2') !== false) {
			$label .= '<br><b>' . $langs->trans('ProfId2' . $thirdparty->country_code) . ':</b> ' . $thirdparty->idprof2;
		}
		if (strpos($conf->global->SOCIETE_SHOW_FIELD_IN_TOOLTIP, 'profid3') !== false) {
			$label .= '<br><b>' . $langs->trans('ProfId3' . $thirdparty->country_code) . ':</b> ' . $thirdparty->idprof3;
		}
		if (strpos($conf->global->SOCIETE_SHOW_FIELD_IN_TOOLTIP, 'profid4') !== false) {
			$label .= '<br><b>' . $langs->trans('ProfId4' . $thirdparty->country_code) . ':</b> ' . $thirdparty->idprof4;
		}
		if (strpos($conf->global->SOCIETE_SHOW_FIELD_IN_TOOLTIP, 'profid5') !== false) {
			$label .= '<br><b>' . $langs->trans('ProfId5' . $thirdparty->country_code) . ':</b> ' . $thirdparty->idprof5;
		}
		if (strpos($conf->global->SOCIETE_SHOW_FIELD_IN_TOOLTIP, 'profid6') !== false) {
			$label .= '<br><b>' . $langs->trans('ProfId6' . $thirdparty->country_code) . ':</b> ' . $thirdparty->idprof6;
		}
	}
	if ( ! empty($thirdparty->code_client) && ($thirdparty->client == 1 || $thirdparty->client == 3)) {
		$label .= '<br><b>' . $langs->trans('CustomerCode') . ':</b> ' . $thirdparty->code_client;
	}
	if ( ! empty($thirdparty->code_fournisseur) && $thirdparty->fournisseur) {
		$label .= '<br><b>' . $langs->trans('SupplierCode') . ':</b> ' . $thirdparty->code_fournisseur;
	}
	if ( ! empty($conf->accounting->enabled) && ($thirdparty->client == 1 || $thirdparty->client == 3)) {
		$label .= '<br><b>' . $langs->trans('CustomerAccountancyCode') . ':</b> ' . ($thirdparty->code_compta ? $thirdparty->code_compta : $thirdparty->code_compta_client);
	}
	if ( ! empty($conf->accounting->enabled) && $thirdparty->fournisseur) {
		$label .= '<br><b>' . $langs->trans('SupplierAccountancyCode') . ':</b> ' . $thirdparty->code_compta_fournisseur;
	}
	$label .= '</div>';

	// Add type of canvas
	$linkstart .= ( ! empty($thirdparty->canvas) ? '&canvas=' . $thirdparty->canvas : '');
	// Add param to save lastsearch_values or not
	$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
	if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
		$add_save_lastsearch_values = 1;
	}
	if ($add_save_lastsearch_values) {
		$linkstart .= '&save_lastsearch_values=1';
	}
	$linkstart .= '"';

	$linkclose = '';
	if ($option == 'blank') {
		$linkclose .= ' target=_blank';
	}
	if (empty($notooltip)) {
		if ( ! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
			$label      = $langs->trans("ShowCompany");
			$linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
		}
		$linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
		$linkclose .= ' class="classfortooltip refurl"';

		/*
		$hookmanager->initHooks(array('thirdpartydao'));
		$parameters=array('id'=>$thirdparty->id);
		$reshook=$hookmanager->executeHooks('getnomurltooltip',$parameters,$thirdparty,$action);    // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) $linkclose = $hookmanager->resPrint;
		*/
	}
	$linkstart .= $linkclose . '>';
	$linkend    = '</a>';

	global $user;
	if ( ! $user->rights->societe->client->voir && $user->socid > 0 && $thirdparty->id != $user->socid) {
		$linkstart = '';
		$linkend   = '';
	}

	$result .= $linkstart;
	if ($withpicto) {
		$result .= img_object(($notooltip ? '' : $label), ($thirdparty->picto ? $thirdparty->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="' . (($withpicto != 2) ? 'paddingright ' : '') . 'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
	}
	if ($withpicto != 2) {
		$result .= dol_escape_htmltag($maxlen ? dol_trunc($name, $maxlen) : $name);
	}
	$result .= $linkend;

	global $action;
	$hookmanager->initHooks(array('thirdpartydao'));
	$parameters = array(
		'id' => $thirdparty->id,
		'getnomurl' => $result,
		'withpicto ' => $withpicto,
		'option' => $option,
		'maxlen' => $maxlen,
		'notooltip' => $notooltip,
		'save_lastsearch_value' => $save_lastsearch_value
	);
	$reshook    = $hookmanager->executeHooks('getNomUrl', $parameters, $thirdparty, $action); // Note that $action and $object may have been modified by some hooks
	if ($reshook > 0) {
		$result = $hookmanager->resPrint;
	} else {
		$result .= $hookmanager->resPrint;
	}

	return $result;
}

/**
 * 	Return clickable name (with picto eventually)
 *
 * 	@param	int		$withpicto		          0=No picto, 1=Include picto into link, 2=Only picto
 * 	@param	string	$option			          Variant where the link point to ('', 'nolink')
 * 	@param	int		$addlabel		          0=Default, 1=Add label into string, >1=Add first chars into string
 *  @param	string	$moreinpopup	          Text to add into popup
 *  @param	string	$sep			          Separator between ref and label if option addlabel is set
 *  @param	int   	$notooltip		          1=Disable tooltip
 *  @param  int     $save_lastsearch_value    -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
 *  @param	string	$morecss				  More css on a link
 * 	@return	string					          String with URL
 */
function getNomUrlEntity($object, $withpicto = 0, $option = '', $addlabel = 0, $moreinpopup = '', $sep = ' - ', $notooltip = 0, $save_lastsearch_value = -1, $morecss = '')
{
	global $conf, $langs, $user, $hookmanager, $db;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

	if ( ! empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

	$result = '';

	$label = '';
	$label .= '<i class="fas fa-building"></i> <u class="paddingrightonly">' . $langs->trans('Entity') . '</u>';
	$label .= ($label ? '<br>' : '') . '<b>' . $langs->trans('Ref') . ': </b>' . 'S' . $object->entity; // The space must be after the : to not being explode when showing the title in img_picto
	$label .= ($label ? '<br>' : '') . '<b>' . $langs->trans('Label') . ': </b>' .  dolibarr_get_const($db, 'MAIN_INFO_SOCIETE_NOM', $object->entity); // The space must be after the : to not being explode when showing the title in img_picto
	if ($moreinpopup) $label .= '<br>' . $moreinpopup;

	$url = $_SERVER['REQUEST_URI'];

	if ($option != 'nolink') {
		// Add param to save lastsearch_values or not
		$add_save_lastsearch_values                                                                                      = ($save_lastsearch_value == 1 ? 1 : 0);
		if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
		if ($add_save_lastsearch_values) $url                                                                           .= '&save_lastsearch_values=1';
	}

	$linkclose = '';
	if ($option == 'blank') {
		$linkclose .= ' target=_blank';
	}

	if (empty($notooltip) && $user->rights->digiriskdolibarr->digiriskelement->read) {
		if ( ! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
			$label      = $langs->trans("ShowEntity");
			$linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
		}
		$linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
		$linkclose .= ' class="classfortooltip' . ($morecss ? ' ' . $morecss : '') . '"';
	} else $linkclose = ($morecss ? ' class="' . $morecss . '"' : '');

	if ($option != 'nolink') {
		$linkstart  = '<a href="' . $url . '"';
		$linkstart .= $linkclose . '>';
		$linkend    = '</a>';
	    $result    .= $linkstart;
	}

	if ($withpicto) $result      .= '<i class="fas fa-building"></i>' . ' ';
	if ($withpicto != 2) $result .= 'S' . $object->entity;
	if ($withpicto != 2) $result .= (($addlabel && dolibarr_get_const($db, 'MAIN_INFO_SOCIETE_NOM', $object->entity)) ? $sep . dol_trunc(dolibarr_get_const($db, 'MAIN_INFO_SOCIETE_NOM', $object->entity), ($addlabel > 1 ? $addlabel : 0)) : '');
    if (isset($linked)) {
        $result .= $linkend;
    }

	global $action;
	$hookmanager->initHooks(array('entitydao'));
	$parameters               = array('id' => $object->id, 'getnomurl' => $result);
	$reshook                  = $hookmanager->executeHooks('getNomUrl', $parameters, $object, $action); // Note that $action and $this may have been modified by some hooks
	if ($reshook > 0) $result = $hookmanager->resPrint;
	else $result             .= $hookmanager->resPrint;

	return $result;
}

/**
 *  Output html form to select a third party.
 *  Note, you must use the select_company to get the component to select a third party. This function must only be called by select_company.
 *
 * @param  string 		$selected Preselected type
 * @param  string 		$htmlname Name of field in form
 * @param  string 		$filter Optional filters criteras (example: 's.rowid <> x', 's.client in (1,3)')
 * @param  string 		$showempty Add an empty field (Can be '1' or text to use on empty line like 'SelectThirdParty')
 * @param  int 			$forcecombo Force to use standard HTML select component without beautification
 * @param  array 		$events Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
 * @param  int 			$outputmode 0=HTML select string, 1=Array
 * @param  int 			$limit Limit number of answers
 * @param  string 		$morecss Add more css styles to the SELECT component
 * @param  int	 		$moreparam Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
 * @param  bool 		$multiple add [] in the name of element and add 'multiple' attribut
 * @param  int 			$noroot
 * @return string HTML string with
 * @throws Exception
 */
function select_entity_list($selected = '', $htmlname = 'entity', $filter = '', $showempty = '1', $forcecombo = 0, $events = array(), $outputmode = 0, $limit = 0, $morecss = 'minwidth200', $moreparam = 0, $multiple = false)
{
	global $conf, $langs, $db;

	$out      = '';
	$outarray = array();

	$selected = array($selected);

	$digiriskelement = new DigiriskElement($db);
	// Clean $filter that may contains sql conditions so sql code
	if (function_exists('testSqlAndScriptInject')) {
		if (testSqlAndScriptInject($filter, 3) > 0) {
			$filter = '';
		}
	}
	$sql  = "SELECT *";
	$sql .= " FROM " . MAIN_DB_PREFIX . "entity as e";

	$sql              .= " WHERE e.rowid IN (" . getEntity($digiriskelement->element) . ")";
	//$sql .= ' WHERE 1 = 1';
	if ($filter) $sql .= " AND (" . $filter . ")";
	$sql .= $db->order("rowid", "ASC");
	$sql .= $db->plimit($limit, 0);

	// Build output string
	$resql = $db->query($sql);
	$num = '';
	if ($resql) {
		if ( ! $forcecombo) {
			include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
			$out .= ajax_combobox($htmlname, $events, 0);
		}

		// Construct $out and $outarray
		$out .= '<select id="' . $htmlname . '" class="flat' . ($morecss ? ' ' . $morecss : '') . '"' . ($moreparam ? ' ' . $moreparam : '') . ' name="' . $htmlname . ($multiple ? '[]' : '') . '" ' . ($multiple ? 'multiple' : '') . '>' . "\n";
		$num                  = $db->num_rows($resql);
		$i                    = 0;

		$textifempty          = (($showempty && ! is_numeric($showempty)) ? $langs->trans($showempty) : '');
		if ($showempty) $out .= '<option value="-1">' . $textifempty . '</option>' . "\n";

		if ($num) {
			while ($i < $num) {
				$obj   = $db->fetch_object($resql);
				$label = 'S' . $obj->rowid . ' - ' . $obj->label;


				if (empty($outputmode)) {
					if (in_array($obj->rowid, $selected)) {
						$out .= '<option value="' . $obj->rowid . '" selected>' . $label . '</option>';
					} else {
						$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
					}
				} else {
					array_push($outarray, array('key' => $obj->rowid, 'value' => $label, 'label' => $label));
				}

				$i++;
				if (($i % 10) == 0) $out .= "\n";
			}
		}
		$out .= '</select>' . "\n";
	} else {
		dol_print_error($db);
	}

	$result = array('nbofdigiriskelement' => $num);

	return $out;
}

/**
 *	Delete all links between an object $this
 *
 *	@param	int		$sourceid		Object source id
 *	@param  string	$sourcetype		Object source type
 *	@param  int		$targetid		Object target id
 *	@param  string	$targettype		Object target type
 *  @param	int		$rowid			Row id of line to delete. If defined, other parameters are not used.
 * 	@param	User	$f_user			User that create
 * 	@param	int		$notrigger		1=Does not execute triggers, 0= execute triggers
 *	@return     					int	>0 if OK, <0 if KO
 *	@see	add_object_linked(), updateObjectLinked(), fetchObjectLinked()
 */
function deleteObjectLinkedDigirisk($object, $sourceid = null, $sourcetype = '', $targetid = null, $targettype = '', $rowid = '', $f_user = null, $notrigger = 0)
{
	global $user;
	$deletesource = false;
	$deletetarget = false;
	$f_user = isset($f_user) ? $f_user : $user;

	if (!empty($sourceid) && !empty($sourcetype) && empty($targetid) && empty($targettype)) {
		$deletesource = true;
	} elseif (empty($sourceid) && empty($sourcetype) && !empty($targetid) && !empty($targettype)) {
		$deletetarget = true;
	}

	$sourceid = (!empty($sourceid) ? $sourceid : $object->id);
	$sourcetype = (!empty($sourcetype) ? $sourcetype : $object->element);
	$targetid = (!empty($targetid) ? $targetid : $object->id);
	$targettype = (!empty($targettype) ? $targettype : $object->element);
	$object->db->begin();
	$error = 0;

	if (!$notrigger) {
		// Call trigger
		$object->context['link_id'] = $rowid;
		$object->context['link_source_id'] = $sourceid;
		$object->context['link_source_type'] = $sourcetype;
		$object->context['link_target_id'] = $targetid;
		$object->context['link_target_type'] = $targettype;
		$result = $object->call_trigger('OBJECT_LINK_DELETE', $f_user);
		if ($result < 0) {
			$error++;
		}
		// End call triggers
	}

	if (!$error) {
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "element_element";
		$sql .= " WHERE";
		if ($rowid > 0) {
			$sql .= " rowid = " . ((int) $rowid);
		} else {
			if ($deletesource) {
				$sql .= " fk_source = " . ((int) $sourceid) . " AND sourcetype = '" . $object->db->escape($sourcetype) . "'";
				$sql .= " AND fk_target = " . ((int) $object->id) . " AND targettype = '" . $object->db->escape($object->element) . "'";
			} elseif ($deletetarget) {
				$sql .= " fk_target = " . ((int) $targetid) . " AND targettype = '" . $object->db->escape($targettype) . "'";
				$sql .= " AND fk_source = " . ((int) $object->id) . " AND sourcetype = '" . $object->db->escape($object->element) . "'";
			} else {
				$sql .= " (fk_source = " . ((int) $sourceid) . " AND sourcetype = '" . $object->db->escape($sourcetype) . "')";
				$sql .= " AND";
				$sql .= " (fk_target = " . ((int) $targetid) . " AND targettype = '" . $object->db->escape($targettype) . "')";
			}
		}

		dol_syslog(get_class($object) . "::deleteObjectLinkedDigirisk", LOG_DEBUG);

		if (!$object->db->query($sql)) {
			$object->error = $object->db->lasterror();
			$object->errors[] = $object->error;
			$error++;
		}
	}

	if (!$error) {
		$object->db->commit();
		return 1;
	} else {
		$object->db->rollback();
		return 0;
	}
}

/**
 * Get and save an upload file (for example after submitting a new file a mail form). Database index of file is also updated if donotupdatesession is set.
 * All information used are in db, conf, langs, user and _FILES.
 * Note: This function can be used only into a HTML page context.
 *
 * @param	string	$upload_dir				Directory where to store uploaded file (note: used to forge $destpath = $upload_dir + filename)
 * @param	int		$allowoverwrite			1=Allow overwrite existing file
 * @param	int		$donotupdatesession		1=Do no edit _SESSION variable but update database index. 0=Update _SESSION and not database index. -1=Do not update SESSION neither db.
 * @param	string	$varfiles				_FILES var name
 * @param	string	$savingdocmask			Mask to use to define output filename. For example 'XXXXX-__YYYYMMDD__-__file__'
 * @param	string	$link					Link to add (to add a link instead of a file)
 * @param   string  $trackid                Track id (used to prefix name of session vars to avoid conflict)
 * @param	int		$generatethumbs			1=Generate also thumbs for uploaded image files
 * @param   Object  $object                 Object used to set 'src_object_*' fields
 * @return	int                             <=0 if KO, >0 if OK
 * @see dol_remove_file_process()
 */
function digirisk_dol_add_file_process($upload_dir, $allowoverwrite = 0, $donotupdatesession = 0, $varfiles = 'addedfile', $savingdocmask = '', $link = null, $trackid = '', $generatethumbs = 1, $object = null)
{
	global $db, $user, $conf, $langs;

	$res = 0;

	if (!empty($_FILES[$varfiles])) { // For view $_FILES[$varfiles]['error']
		dol_syslog('digirisk_dol_add_file_process upload_dir='.$upload_dir.' allowoverwrite='.$allowoverwrite.' donotupdatesession='.$donotupdatesession.' savingdocmask='.$savingdocmask, LOG_DEBUG);

		$result = dol_mkdir($upload_dir);
		//      var_dump($result);exit;
		if ($result >= 0) {
			$TFile = $_FILES[$varfiles];
			if (!is_array($TFile['name'])) {
				foreach ($TFile as $key => &$val) {
					$val = array($val);
				}
			}

			$nbfile = count($TFile['name']);
			$nbok = 0;
			for ($i = 0; $i < $nbfile; $i++) {
				if (empty($TFile['name'][$i])) {
					continue; // For example, when submitting a form with no file name
				}

				// Define $destfull (path to file including filename) and $destfile (only filename)
				$destfull = $upload_dir."/".$TFile['name'][$i];
				$destfile = $TFile['name'][$i];
				$destfilewithoutext = preg_replace('/\.[^\.]+$/', '', $destfile);

				if ($savingdocmask && strpos($savingdocmask, $destfilewithoutext) !== 0) {
					$destfull = $upload_dir."/".preg_replace('/__file__/', $TFile['name'][$i], $savingdocmask);
					$destfile = preg_replace('/__file__/', $TFile['name'][$i], $savingdocmask);
				}

				$filenameto = basename($destfile);
				if (preg_match('/^\./', $filenameto)) {
					$langs->load("errors"); // key must be loaded because we can't rely on loading during output, we need var substitution to be done now.
					setEventMessages($langs->trans("ErrorFilenameCantStartWithDot", $filenameto), null, 'errors');
					break;
				}

				// dol_sanitizeFileName the file name and lowercase extension
				$info = pathinfo($destfull);
				$destfull = $info['dirname'].'/'.dol_sanitizeFileName($info['filename'].($info['extension'] != '' ? ('.'.strtolower($info['extension'])) : ''));
				$info = pathinfo($destfile);

				$destfile = dol_sanitizeFileName($info['filename'].($info['extension'] != '' ? ('.'.strtolower($info['extension'])) : ''));

				// We apply dol_string_nohtmltag also to clean file names (this remove duplicate spaces) because
				// this function is also applied when we rename and when we make try to download file (by the GETPOST(filename, 'alphanohtml') call).
				$destfile = dol_string_nohtmltag($destfile);
				$destfull = dol_string_nohtmltag($destfull);

				// Move file from temp directory to final directory. A .noexe may also be appended on file name.
				$resupload = dol_move_uploaded_file($TFile['tmp_name'][$i], $destfull, $allowoverwrite, 0, $TFile['error'][$i], 0, $varfiles, $upload_dir);

				if (is_numeric($resupload) && $resupload > 0) {   // $resupload can be 'ErrorFileAlreadyExists'
					global $maxwidthsmall, $maxheightsmall, $maxwidthmini, $maxheightmini;

					include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

					// Generate thumbs.
					if ($generatethumbs) {
						if (image_format_supported($destfull) == 1) {
							// Create thumbs
							// We can't use $object->addThumbs here because there is no $object known
							vignette($destfull, $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_LARGE, $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_LARGE, '_large', 50, "thumbs");
							vignette($destfull, $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_MEDIUM, $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_MEDIUM, '_medium', 50, "thumbs");
							// Used on logon for example
							$imgThumbSmall = vignette($destfull, $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
							// Create mini thumbs for image (Ratio is near 16/9)
							// Used on menu or for setup page for example
							$imgThumbMini = vignette($destfull, $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");
						}
					}

					// Update session
					if (empty($donotupdatesession)) {
						include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
						$formmail = new FormMail($db);
						$formmail->trackid = $trackid;
						$formmail->add_attached_files($destfull, $destfile, $TFile['type'][$i]);
					}

					// Update index table of files (llx_ecm_files)
					if ($donotupdatesession == 1) {
						$result = addFileIntoDatabaseIndex($upload_dir, basename($destfile).($resupload == 2 ? '.noexe' : ''), $TFile['name'][$i], 'uploaded', 0, $object);
						if ($result < 0) {
							if ($allowoverwrite) {
								// Do not show error message. We can have an error due to DB_ERROR_RECORD_ALREADY_EXISTS
							} else {
								setEventMessages('WarningFailedToAddFileIntoDatabaseIndex', '', 'warnings');
							}
						}
					}

					$nbok++;
				} else {
					$langs->load("errors");
					if ($resupload < 0) {	// Unknown error
						setEventMessages($langs->trans("ErrorFileNotUploaded"), null, 'errors');
					} elseif (preg_match('/ErrorFileIsInfectedWithAVirus/', $resupload)) {	// Files infected by a virus
						setEventMessages($langs->trans("ErrorFileIsInfectedWithAVirus"), null, 'errors');
					} else // Known error
					{
						setEventMessages($langs->trans($resupload), null, 'errors');
					}
				}
			}
			if ($nbok > 0) {
				$res = 1;
				setEventMessages($langs->trans("FileTransferComplete"), null, 'mesgs');
			}
		} else {
			setEventMessages($langs->trans("ErrorFailedToCreateDir", $upload_dir), null, 'errors');
		}
	} elseif ($link) {
		require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
		$linkObject = new Link($db);
		$linkObject->entity = $conf->entity;
		$linkObject->url = $link;
		$linkObject->objecttype = GETPOST('objecttype', 'alpha');
		$linkObject->objectid = GETPOST('objectid', 'int');
		$linkObject->label = GETPOST('label', 'alpha');
		$res = $linkObject->create($user);
		$langs->load('link');
		if ($res > 0) {
			setEventMessages($langs->trans("LinkComplete"), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("ErrorFileNotLinked"), null, 'errors');
		}
	} else {
		$langs->load("errors");
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("File")), null, 'errors');
	}

	return $res;
}


/**
 * Security check when accessing to a document (used by document.php, viewimage.php and webservices to get documents).
 * TODO Replace code that set $accessallowed by a call to restrictedArea()
 *
 * @param	string	$modulepart			Module of document ('module', 'module_user_temp', 'module_user' or 'module_temp'). Exemple: 'medias', 'invoice', 'logs', 'tax-vat', ...
 * @param	string	$original_file		Relative path with filename, relative to modulepart.
 * @param	string	$entity				Restrict onto entity (0=no restriction)
 * @param  	User	$fuser				User object (forced)
 * @param	string	$refname			Ref of object to check permission for external users (autodetect if not provided) or for hierarchy
 * @param   string  $mode               Check permission for 'read' or 'write'
 * @return	mixed						Array with access information : 'accessallowed' & 'sqlprotectagainstexternals' & 'original_file' (as a full path name)
 * @see restrictedArea()
 */
function digirisk_check_secure_access_document($modulepart, $original_file, $entity, $fuser = '', $refname = '', $mode = 'read')
{
	if ( ! defined('NOREQUIREUSER'))  define('NOREQUIREUSER', '1');
	if ( ! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');
	if ( ! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
	if ( ! defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
	if ( ! defined('NOLOGIN'))        define("NOLOGIN", 1); // This means this output page does not require to be logged.
	if ( ! defined('NOCSRFCHECK'))    define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
	if ( ! defined('NOIPCHECK'))      define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
	if ( ! defined('NOBROWSERNOTIF')) define('NOBROWSERNOTIF', '1');

	global $conf, $user;

	if (empty($modulepart)) {
		return 'ErrorBadParameter';
	}
	if (empty($entity)) {
		if (empty($conf->multicompany->enabled)) {
			$entity = 1;
		} else {
			$entity = 0;
		}
	}

	//print 'dol_check_secure_access_document modulepart='.$modulepart.' original_file='.$original_file.' entity='.$entity;
	dol_syslog('digirisk_check_secure_access_document modulepart='.$modulepart.' original_file='.$original_file.' entity='.$entity);

	$accessallowed = 0;
	$sqlprotectagainstexternals = '';

	if (empty($refname)) {
		$refname = basename(dirname($original_file)."/");
		if ($refname == 'thumbs') {
			$refname = basename(dirname(dirname($original_file))."/");
		}
	}

	if ($modulepart == 'mycompany' && !empty($conf->mycompany->dir_output)) {
		// Wrapping for some images
		$accessallowed = 1;
		$original_file = $conf->mycompany->dir_output.'/'.$original_file;
	} elseif ($modulepart == 'category' && !empty($conf->categorie->multidir_output[$entity])) {
		$accessallowed = 1;
		$original_file = $conf->categorie->multidir_output[$entity].'/'.$original_file;
	} elseif ($modulepart == 'digiriskdolibarr' && !empty($conf->digiriskdolibarr->multidir_output[$entity])) {
		$accessallowed = 1;
		$original_file = $conf->digiriskdolibarr->multidir_output[$entity].'/'.$original_file;
	}

	$ret = array(
		'accessallowed' => ($accessallowed ? 1 : 0),
		'sqlprotectagainstexternals' => $sqlprotectagainstexternals,
		'original_file' => $original_file
	);

	return $ret;
}

/**
 * Load indicators for dashboard
 *
 * @param  User	   			$user		 		User object
 * @param  array   			$cat     	 		Category info
 * @param  DigiriskElement  $digiriskelement	DigiriskElement object
 * @return WorkboardResponse|int <0 if KO, WorkboardResponse if OK
 * @throws Exception
 */
function load_board($user, $cat, $digiriskelement)
{
	global $db;

	$categorie = new Categorie($db);

	$categorie->fetch(0, $cat['name']);
	$allObjects = $categorie->getObjectsInCateg(Categorie::TYPE_TICKET);

	if (is_array($allObjects) && !empty($allObjects)) {
		foreach ($allObjects as $object) {
			if (!empty($object->array_options['options_digiriskdolibarr_ticket_service']) && $object->array_options['options_digiriskdolibarr_ticket_service'] == $digiriskelement->id && $object->fk_statut != 9) {
				$arrayCountObject[] = $object;
			}
		}
	}

	if (!empty($arrayCountObject)) {
		$nbobject = count($arrayCountObject);
	}

	if ($allObjects > 0) {
		$response = new WorkboardResponse();
		$response->id = $cat['id'];
		$response->color = $cat['color'];
		$response->img = $cat['photo'];
		$response->label = $cat['name'] . ' : ';
		$response->url = DOL_URL_ROOT . '/ticket/list.php?search_options_digiriskdolibarr_ticket_service='.$digiriskelement->id.'&search_category_ticket_list='.$cat['id'];
		$response->nbtodo = ($nbobject ?: 0);
		$visible = json_decode($user->conf->DIGIRISKDOLIBARR_TICKET_DISABLED_DASHBOARD_INFO);
		$digiriskelementID = $digiriskelement->id;
		$catID = $cat['id'];
		if (isset($visible->$digiriskelementID->$catID) && $visible->$digiriskelementID->$catID == 0){
			$response->visible = 0;
		} else {
			$response->visible = 1;
		}
		return $response;
	} else {
		return -1;
	}
}

/**
 *  Load dictionnary from database
 *
 * 	@param  int       $parent_id
 *	@param  int       $limit
 * 	@return array|int             <0 if KO, >0 if OK
 */
function fetchDictionnary($tablename)
{
	global $db;

	$sql  = 'SELECT t.rowid, t.entity, t.ref, t.label, t.description, t.active';
	$sql .= ' FROM ' . MAIN_DB_PREFIX . $tablename . ' as t';
	$sql .= ' WHERE 1 = 1';
	$sql .= ' AND entity IN (0, ' . getEntity($tablename) . ')';

	$resql = $db->query($sql);

	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		$records = array();
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			$record = new stdClass();

			$record->id          = $obj->rowid;
			$record->entity      = $obj->entity;
			$record->ref         = $obj->ref;
			$record->label       = $obj->label;
			$record->description = $obj->description;
			$record->active      = $obj->active;

			$records[$record->id] = $record;

			$i++;
		}

		$db->free($resql);

		return $records;
	} else {
		return -1;
	}
}

/**
 * Return list of fetched instance of elements having this category
 *
 * @param  object            $object           Category object
 * @param  string     	     $type             Type of category ('customer', 'supplier', 'contact', 'product', 'member', ...)
 * @param  int        	     $onlyids          Return only ids of objects (consume less memory)
 * @param  int			     $limit		       Limit
 * @param  int			     $offset		   Offset
 * @param  string		     $sortfield	       Sort fields
 * @param  string		     $sortorder	       Sort order ('ASC' or 'DESC');
 * @param  string            $morewherefilter  Filter
 * @return array|int                           -1 if KO, array of instance of object if OK
 * @throws Exception
 * @see    containsObject()
*/
function getObjectsInCategDigirisk($object, $type, $onlyids = 0, $limit = 0, $offset = 0, $sortfield = '', $sortorder = 'ASC', $morewherefilter = '')
{
	global $user;

	$objs = array();

	$classnameforobj = 'Ticket';
	$obj = new $classnameforobj($object->db);

	$sql = "SELECT c.fk_".(empty($object->MAP_CAT_FK[$type]) ? $type : $object->MAP_CAT_FK[$type]);
	$sql .= " FROM ".MAIN_DB_PREFIX."categorie_".(empty($object->MAP_CAT_TABLE[$type]) ? $type : $object->MAP_CAT_TABLE[$type])." as c";
	$sql .= ", ".MAIN_DB_PREFIX.(empty($object->MAP_OBJ_TABLE[$type]) ? $type : $object->MAP_OBJ_TABLE[$type])." as o";
	$sql .= " WHERE o.entity IN (".getEntity($obj->element).")";
	$sql .= " AND c.fk_categorie = ".((int) $object->id);
	$sql .= " AND c.fk_".(empty($object->MAP_CAT_FK[$type]) ? $type : $object->MAP_CAT_FK[$type])." = o.rowid";
	// Protection for external users
	if (($type == 'customer' || $type == 'supplier') && $user->socid > 0) {
		$sql .= " AND o.rowid = ".((int) $user->socid);
	}
	if ($morewherefilter) {
		$sql .= $morewherefilter;
	}
	if ($limit > 0 || $offset > 0) {
		$sql .= $object->db->plimit($limit + 1, $offset);
	}
	$sql .= $object->db->order($sortfield, $sortorder);

	dol_syslog(get_class($object)."::getObjectsInCateg", LOG_DEBUG);
	$resql = $object->db->query($sql);
	if ($resql) {
		while ($rec = $object->db->fetch_array($resql)) {
			if ($onlyids) {
				$objs[] = $rec['fk_'.(empty($object->MAP_CAT_FK[$type]) ? $type : $object->MAP_CAT_FK[$type])];
			} else {
				$classnameforobj = 'Ticket';

				$obj = new $classnameforobj($object->db);
				$obj->fetch($rec['fk_'.(empty($object->MAP_CAT_FK[$type]) ? $type : $object->MAP_CAT_FK[$type])]);

				$objs[] = $obj;
			}
		}
		return $objs;
	} else {
		$object->error = $object->db->error().' sql='.$sql;
		return -1;
	}
}

/**
 * Return file specified thumb name
 *
 * @param  object            $object           Category object
 * @param  string     	     $filename         File name
 * @param  string        	 $thumb_type       Thumb type (small, mini, large, medium)
 * @return string
 * @throws Exception
*/
function getThumbName($filename, $thumb_type = 'small')
{
	$img_name = pathinfo($filename, PATHINFO_FILENAME);
	$img_extension = pathinfo($filename, PATHINFO_EXTENSION);
	$thumb_fullname = $img_name . '_'. $thumb_type .'.' . $img_extension;

	return $thumb_fullname;
}

/**
 * Return file thumbs names
 *
 * @param  object            $object           Category object
 * @param  string     	     $filename         File name
 * @param  string        	 $thumb_type       Thumb type (small, mini, large, medium)
 * @return string
 * @throws Exception
*/
function getAllThumbsNames($filename)
{
	$thumbs_fullnames = array();
	$thumb_types = array(
		'large',
		'medium',
		'small',
		'mini'
	);
	$img_name = pathinfo($filename, PATHINFO_FILENAME);
	$img_extension = pathinfo($filename, PATHINFO_EXTENSION);

	foreach ($thumb_types as $thumb_type) {
		$thumbs_fullnames[] = $img_name . '_'. $thumb_type .'.' . $img_extension;
	}

	return $thumbs_fullnames;
}

/**
 * get all working hours
 *
 * @return float
*/
function getWorkedHours()
{
	global $conf, $user;

	if ($conf->global->DIGIRISKDOLIBARR_MANUAL_INPUT_NB_WORKED_HOURS) {
		$total_workhours = $conf->global->DIGIRISKDOLIBARR_NB_WORKED_HOURS;
	} else {
		$userList = $user->get_full_tree();
		$total_workhours = 0;
		if (!empty($userList) && is_array($userList)) {
			foreach ($userList as $sub_user) {
				$user->fetch($sub_user['rowid']);
				if ($user->employee && $user->weeklyhours && $user->dateemployment) {
					$employmentdate = $user->dateemployment;
					$weeklyhours    = $user->weeklyhours;

					$diff = dol_now() - $employmentdate;
					$work_weeks = floor($diff / 604800);
					$total_workhours += $work_weeks * $weeklyhours;
				}
			}
		}
	}

	return $total_workhours;
}

/**
 *     Show a confirmation HTML form or AJAX popup.
 *     Easiest way to use this is with useajax=1.
 *     If you use useajax='xxx', you must also add jquery code to trigger opening of box (with correct parameters)
 *     just after calling this method. For example:
 *       print '<script type="text/javascript">'."\n";
 *       print 'jQuery(document).ready(function() {'."\n";
 *       print 'jQuery(".xxxlink").click(function(e) { jQuery("#aparamid").val(jQuery(this).attr("rel")); jQuery("#dialog-confirm-xxx").dialog("open"); return false; });'."\n";
 *       print '});'."\n";
 *       print '</script>'."\n";
 *
 *     @param  	string			$page        	   	Url of page to call if confirmation is OK. Can contains parameters (param 'action' and 'confirm' will be reformated)
 *     @param	string			$title       	   	Title
 *     @param	string			$question    	   	Question
 *     @param 	string			$action      	   	Action
 *	   @param  	array|string	$formquestion	   	An array with complementary inputs to add into forms: array(array('label'=> ,'type'=> , 'size'=>, 'morecss'=>, 'moreattr'=>))
 *													type can be 'hidden', 'text', 'password', 'checkbox', 'radio', 'date', 'morecss', 'other' or 'onecolumn'...
 * 	   @param  	string			$selectedchoice  	'' or 'no', or 'yes' or '1' or '0'
 * 	   @param  	int|string		$useajax		   	0=No, 1=Yes, 2=Yes but submit page with &confirm=no if choice is No, 'xxx'=Yes and preoutput confirm box with div id=dialog-confirm-xxx
 *     @param  	int|string		$height          	Force height of box (0 = auto)
 *     @param	int				$width				Force width of box ('999' or '90%'). Ignored and forced to 90% on smartphones.
 *     @param	int				$disableformtag		1=Disable form tag. Can be used if we are already inside a <form> section.
 *     @return 	string      		    			HTML ajax code if a confirm ajax popup is required, Pure HTML code if it's an html form
 */
function digiriskformconfirm($page, $title, $question, $action, $formquestion = '', $selectedchoice = '', $useajax = 0, $height = 0, $width = 500, $disableformtag = 0)
{
	global $conf, $form, $langs;

	$more = '<!-- formconfirm before calling page='.dol_escape_htmltag($page).' -->';
	$formconfirm = '';
	$inputok = array();
	$inputko = array();

	// Clean parameters
	$newselectedchoice = empty($selectedchoice) ? "no" : $selectedchoice;
	if ($conf->browser->layout == 'phone') {
		$width = '95%';
	}

	// Set height automatically if not defined
	if (empty($height)) {
		$height = 220;
		if (is_array($formquestion) && count($formquestion) > 2) {
			$height += ((count($formquestion) - 2) * 24);
		}
	}

	if (is_array($formquestion) && !empty($formquestion)) {
		// First add hidden fields and value
		foreach ($formquestion as $key => $input) {
			if (is_array($input) && !empty($input)) {
				if ($input['type'] == 'hidden') {
					$more .= '<input type="hidden" id="'.dol_escape_htmltag($input['name']).'" name="'.dol_escape_htmltag($input['name']).'" value="'.dol_escape_htmltag($input['value']).'">'."\n";
				}
			}
		}

		// Now add questions
		$moreonecolumn = '';
		$more .= '<div class="tagtable paddingtopbottomonly centpercent noborderspacing">'."\n";
		foreach ($formquestion as $key => $input) {
			if (is_array($input) && !empty($input)) {
				$size = (!empty($input['size']) ? ' size="'.$input['size'].'"' : '');	// deprecated. Use morecss instead.
				$moreattr = (!empty($input['moreattr']) ? ' '.$input['moreattr'] : '');
				$morecss = (!empty($input['morecss']) ? ' '.$input['morecss'] : '');

				if ($input['type'] == 'text') {
					$more .= '<div class="tagtr"><div class="tagtd'.(empty($input['tdclass']) ? '' : (' '.$input['tdclass'])).'">'.$input['label'].'</div><div class="tagtd"><input type="text" class="flat'.$morecss.'" id="'.dol_escape_htmltag($input['name']).'" name="'.dol_escape_htmltag($input['name']).'"'.$size.' value="'.$input['value'].'"'.$moreattr.' /></div></div>'."\n";
				} elseif ($input['type'] == 'password')	{
					$more .= '<div class="tagtr"><div class="tagtd'.(empty($input['tdclass']) ? '' : (' '.$input['tdclass'])).'">'.$input['label'].'</div><div class="tagtd"><input type="password" class="flat'.$morecss.'" id="'.dol_escape_htmltag($input['name']).'" name="'.dol_escape_htmltag($input['name']).'"'.$size.' value="'.$input['value'].'"'.$moreattr.' /></div></div>'."\n";
				} elseif ($input['type'] == 'textarea') {
					/*$more .= '<div class="tagtr"><div class="tagtd'.(empty($input['tdclass']) ? '' : (' '.$input['tdclass'])).'">'.$input['label'].'</div><div class="tagtd">';
					$more .= '<textarea name="'.$input['name'].'" class="'.$morecss.'"'.$moreattr.'>';
					$more .= $input['value'];
					$more .= '</textarea>';
					$more .= '</div></div>'."\n";*/
					$moreonecolumn .= '<div class="margintoponly">';
					$moreonecolumn .= $input['label'].'<br>';
					$moreonecolumn .= '<textarea name="'.dol_escape_htmltag($input['name']).'" id="'.dol_escape_htmltag($input['name']).'" class="'.$morecss.'"'.$moreattr.'>';
					$moreonecolumn .= $input['value'];
					$moreonecolumn .= '</textarea>';
					$moreonecolumn .= '</div>';
				} elseif ($input['type'] == 'select') {
					if (empty($morecss)) {
						$morecss = 'minwidth100';
					}

					$show_empty = isset($input['select_show_empty']) ? $input['select_show_empty'] : 1;
					$key_in_label = isset($input['select_key_in_label']) ? $input['select_key_in_label'] : 0;
					$value_as_key = isset($input['select_value_as_key']) ? $input['select_value_as_key'] : 0;
					$translate = isset($input['select_translate']) ? $input['select_translate'] : 0;
					$maxlen = isset($input['select_maxlen']) ? $input['select_maxlen'] : 0;
					$disabled = isset($input['select_disabled']) ? $input['select_disabled'] : 0;
					$sort = isset($input['select_sort']) ? $input['select_sort'] : '';

					$more .= '<div class="tagtr"><div class="tagtd'.(empty($input['tdclass']) ? '' : (' '.$input['tdclass'])).'">';
					if (!empty($input['label'])) {
						$more .= $input['label'].'</div><div class="tagtd left">';
					}
					$more .= $form->selectarray($input['name'], $input['values'], $input['default'], $show_empty, $key_in_label, $value_as_key, $moreattr, $translate, $maxlen, $disabled, $sort, $morecss);
					$more .= '</div></div>'."\n";
				} elseif ($input['type'] == 'checkbox') {
					$more .= '<div class="tagtr">';
					$more .= '<div class="tagtd'.(empty($input['tdclass']) ? '' : (' '.$input['tdclass'])).'">'.$input['label'].' </div><div class="tagtd">';
					$more .= '<input type="checkbox" class="flat'.$morecss.'" id="'.dol_escape_htmltag($input['name']).'" name="'.dol_escape_htmltag($input['name']).'"'.$moreattr;
					if (!is_bool($input['value']) && $input['value'] != 'false' && $input['value'] != '0' && $input['value'] != '') {
						$more .= ' checked';
					}
					if (is_bool($input['value']) && $input['value']) {
						$more .= ' checked';
					}
					if (isset($input['disabled'])) {
						$more .= ' disabled';
					}
					$more .= ' /></div>';
					$more .= '</div>'."\n";
				} elseif ($input['type'] == 'radio') {
					$i = 0;
					foreach ($input['values'] as $selkey => $selval) {
						$more .= '<div class="tagtr">';
						if ($i == 0) {
							$more .= '<div class="tagtd'.(empty($input['tdclass']) ? ' tdtop' : (' tdtop '.$input['tdclass'])).'">'.$input['label'].'</div>';
						} else {
							$more .= '<div clas="tagtd'.(empty($input['tdclass']) ? '' : (' "'.$input['tdclass'])).'">&nbsp;</div>';
						}
						$more .= '<div class="tagtd'.($i == 0 ? ' tdtop' : '').'"><input type="radio" class="flat'.$morecss.'" id="'.dol_escape_htmltag($input['name'].$selkey).'" name="'.dol_escape_htmltag($input['name']).'" value="'.$selkey.'"'.$moreattr;
						if ($input['disabled']) {
							$more .= ' disabled';
						}
						if (isset($input['default']) && $input['default'] === $selkey) {
							$more .= ' checked="checked"';
						}
						$more .= ' /> ';
						$more .= '<label for="'.dol_escape_htmltag($input['name'].$selkey).'">'.$selval.'</label>';
						$more .= '</div></div>'."\n";
						$i++;
					}
				} elseif ($input['type'] == 'date') {
					$more .= '<div class="tagtr"><div class="tagtd'.(empty($input['tdclass']) ? '' : (' '.$input['tdclass'])).'">'.$input['label'].'</div>';
					$more .= '<div class="tagtd">';
					$addnowlink = (empty($input['datenow']) ? 0 : 1);
					$more .= $form->selectDate($input['value'], $input['name'], 0, 0, 0, '', 1, $addnowlink);
					$more .= '</div></div>'."\n";
					$formquestion[] = array('name'=>$input['name'].'day');
					$formquestion[] = array('name'=>$input['name'].'month');
					$formquestion[] = array('name'=>$input['name'].'year');
					$formquestion[] = array('name'=>$input['name'].'hour');
					$formquestion[] = array('name'=>$input['name'].'min');
				} elseif ($input['type'] == 'other') {
					$more .= '<div class="tagtr"><div class="tagtd'.(empty($input['tdclass']) ? '' : (' '.$input['tdclass'])).'">';
					if (!empty($input['label'])) {
						$more .= $input['label'].'</div><div class="tagtd">';
					}
					$more .= $input['value'];
					$more .= '</div></div>'."\n";
				} elseif ($input['type'] == 'onecolumn') {
					$moreonecolumn .= '<div class="margintoponly">';
					$moreonecolumn .= $input['value'];
					$moreonecolumn .= '</div>'."\n";
				} elseif ($input['type'] == 'hidden') {
					// Do nothing more, already added by a previous loop
				} elseif ($input['type'] == 'separator') {
					$more .= '<br>';
				} else {
					$more .= 'Error type '.$input['type'].' for the confirm box is not a supported type';
				}
			}
		}
		$more .= '</div>'."\n";
		$more .= $moreonecolumn;
	}

	// JQUERY method dialog is broken with smartphone, we use standard HTML.
	// Note: When using dol_use_jmobile or no js, you must also check code for button use a GET url with action=xxx and check that you also output the confirm code when action=xxx
	// See page product/card.php for example
	if (!empty($conf->dol_use_jmobile)) {
		$useajax = 0;
	}
	if (empty($conf->use_javascript_ajax)) {
		$useajax = 0;
	}

	if ($useajax) {
		$autoOpen = true;
		$dialogconfirm = 'dialog-confirm';
		$button = '';
		if (!is_numeric($useajax)) {
			$button = $useajax;
			$useajax = 1;
			$autoOpen = false;
			$dialogconfirm .= '-'.$button;
		}
		$pageyes = $page.(preg_match('/\?/', $page) ? '&' : '?').'action='.$action.'&confirm=yes';
		$pageno = ($useajax == 2 ? $page.(preg_match('/\?/', $page) ? '&' : '?').'confirm=no' : '');

		// Add input fields into list of fields to read during submit (inputok and inputko)
		if (is_array($formquestion)) {
			foreach ($formquestion as $key => $input) {
				//print "xx ".$key." rr ".is_array($input)."<br>\n";
				// Add name of fields to propagate with the GET when submitting the form with button OK.
				if (is_array($input) && isset($input['name'])) {
					if (strpos($input['name'], ',') > 0) {
						$inputok = array_merge($inputok, explode(',', $input['name']));
					} else {
						array_push($inputok, $input['name']);
					}
				}
				// Add name of fields to propagate with the GET when submitting the form with button KO.
				if (isset($input['inputko']) && $input['inputko'] == 1) {
					array_push($inputko, $input['name']);
				}
			}
		}

		// Show JQuery confirm box.
		$formconfirm .= '<div id="'.$dialogconfirm.'" title="'.dol_escape_htmltag($title).'" style="display: none;">';
		if (is_array($formquestion) && !empty($formquestion['text'])) {
			$formconfirm .= '<div class="confirmtext">'.$formquestion['text'].'</div>'."\n";
		}
		if (!empty($more)) {
			$formconfirm .= '<div class="confirmquestions">'.$more.'</div>'."\n";
		}
		$formconfirm .= ($question ? '<div class="confirmmessage">'.img_help('', '').' '.$question.'</div>' : '');
		$formconfirm .= '</div>'."\n";

		$formconfirm .= "\n<!-- begin code of popup for formconfirm page=".$page." -->\n";
		$formconfirm .= '<script type="text/javascript">'."\n";
		$formconfirm .= "/* Code for the jQuery('#dialogforpopup').dialog() */\n";
		$formconfirm .= 'jQuery(document).ready(function() {
		$(function() {
			$( "#'.$dialogconfirm.'" ).dialog(
			{
				autoOpen: '.($autoOpen ? "true" : "false").',';
		if ($newselectedchoice == 'no') {
			$formconfirm .= '
					open: function() {
						$(this).parent().find("button.ui-button:eq(2)").focus();
					},';
		}
		$formconfirm .= '
				resizable: false,
				height: "'.$height.'",
				width: "'.$width.'",
				modal: true,
				closeOnEscape: false,
				buttons: {
					"'.dol_escape_js($langs->transnoentities("Yes")).'": function() {
						var options = "&token='.urlencode(newToken()).'";
						var inputok = '.json_encode($inputok).';	/* List of fields into form */
						var pageyes = "'.dol_escape_js(!empty($pageyes) ? $pageyes : '').'";
						if (inputok.length>0) {
							$.each(inputok, function(i, inputname) {
								var more = "";
								var inputvalue;
								if ($("input[name=\'" + inputname + "\']").attr("type") == "radio") {
									inputvalue = $("input[name=\'" + inputname + "\']:checked").val();
								} else {
									if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
									inputvalue = $("#" + inputname + more).val();
								}
								if (typeof inputvalue == "undefined") { inputvalue=""; }
								console.log("formconfirm check inputname="+inputname+" inputvalue="+inputvalue);
								if (inputvalue) {
									options += "&" + inputname + "=" + encodeURIComponent(inputvalue);
								}
							});
						}
						var urljump = pageyes + (pageyes.indexOf("?") < 0 ? "?" : "") + options;
						if (pageyes.length > 0) { location.href = urljump; }
						$(this).dialog("close");
					},
					"'.dol_escape_js($langs->transnoentities("No")).'": function() {
						var options = "&token='.urlencode(newToken()).'";
						var inputko = '.json_encode($inputko).';	/* List of fields into form */
						var pageno="'.dol_escape_js(!empty($pageno) ? $pageno : '').'";
						if (inputko.length>0) {
							$.each(inputko, function(i, inputname) {
								var more = "";
								if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
								var inputvalue = $("#" + inputname + more).val();
								if (typeof inputvalue == "undefined") { inputvalue=""; }
								if (inputvalue) {
									options += "&" + inputname + "=" + encodeURIComponent(inputvalue);
								}
							});
						}
						var urljump=pageno + (pageno.indexOf("?") < 0 ? "?" : "") + options;
						//alert(urljump);
						if (pageno.length > 0) { location.href = urljump; }
						$(this).dialog("close");
					}
				}
			}
			);

			var button = "'.$button.'";
			if (button.length > 0) {
				$( "#" + button ).click(function() {
					$("#'.$dialogconfirm.'").dialog("open");
				});
			}
		});
		});
		</script>';
		$formconfirm .= "<!-- end ajax formconfirm -->\n";
	} else {
		$formconfirm .= "\n<!-- begin formconfirm page=".dol_escape_htmltag($page)." -->\n";

		if (empty($disableformtag)) {
			$formconfirm .= '<form method="POST" action="'.$page.'" class="notoptoleftroright">'."\n";
		}

		$formconfirm .= '<input type="hidden" name="action" value="'.$action.'">'."\n";
		$formconfirm .= '<input type="hidden" name="token" value="'.newToken().'">'."\n";

		$formconfirm .= '<table class="valid centpercent">'."\n";

		// Line title
		$formconfirm .= '<tr class="validtitre"><td class="validtitre" colspan="2">';
		$formconfirm .= img_picto('', 'recent').' '.$title;
		$formconfirm .= '</td></tr>'."\n";

		// Line text
		if (is_array($formquestion) && !empty($formquestion['text'])) {
			$formconfirm .= '<tr class="valid"><td class="valid" colspan="2">'.$formquestion['text'].'</td></tr>'."\n";
		}

		// Line form fields
		if ($more) {
			$formconfirm .= '<tr class="valid"><td class="valid" colspan="2">'."\n";
			$formconfirm .= $more;
			$formconfirm .= '</td></tr>'."\n";
		}

		// Line with question
		$formconfirm .= '<tr class="valid">';
		$formconfirm .= '<td class="valid">'.$question.'</td>';
		$formconfirm .= '<td class="valid center">';
		$formconfirm .= $form->selectyesno("confirm", $newselectedchoice, 0, false, 0, 0, 'marginleftonly marginrightonly');
		$formconfirm .= '<input class="button valignmiddle confirmvalidatebutton small" type="submit" value="'.$langs->trans("Validate").'">';
		$formconfirm .= '</td>';
		$formconfirm .= '</tr>'."\n";

		$formconfirm .= '</table>'."\n";

		if (empty($disableformtag)) {
			$formconfirm .= "</form>\n";
		}
		$formconfirm .= '<br>';

		if (!empty($conf->use_javascript_ajax)) {
			$formconfirm .= '<!-- code to disable button to avoid double clic -->';
			$formconfirm .= '<script type="text/javascript">'."\n";
			$formconfirm .= '
			$(document).ready(function () {
				$(".confirmvalidatebutton").on("click", function() {
					console.log("We click on button");
					$(this).attr("disabled", "disabled");
					setTimeout(\'$(".confirmvalidatebutton").removeAttr("disabled")\', 3000);
					//console.log($(this).closest("form"));
					$(this).closest("form").submit();
				});
			});
			';
			$formconfirm .= '</script>'."\n";
		}

		$formconfirm .= "<!-- end formconfirm -->\n";
	}

	return $formconfirm;
}
