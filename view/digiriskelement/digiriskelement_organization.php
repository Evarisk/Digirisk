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
 *   	\file       view/digiriskelement/digiriskelement_organization.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to organize arborescence
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
	die('Include of digiriskdolibarr main fails');
}

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['other']);
// Get parameters
$id                  = GETPOST('id', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'digiriskelementcard'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

// Initialize technical objects
$object      = new DigiriskElement($db);
$extrafields = new ExtraFields($db);

$object->fetch($id);

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$hookmanager->initHooks(array('digiriskelementcard', 'globalcard')); // Note that conf->hooks_modules contains array

//Security check
$permissiontoread = $user->rights->digiriskdolibarr->digiriskelement->read;

saturne_check_access($permissiontoread);

/*
 * Actions
 */

$parameters = array();
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/digiriskdolibarr/view/digiriskstandard/digiriskstandard_card.php?id=1', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($object->id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage                                                                              = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_card.php', 1) . '?id=' . ($object->id > 0 ? $object->id : '__ID__');
		}
	}

	if ($action == 'saveOrganization') {
		$ids              = GETPOST('ids');
		$parentIds        = GETPOST('parent_ids');
		$arrayIds         = preg_split('/,/', $ids);
		$arrayParentIds   = preg_split('/,/', $parentIds);
		$i                = 0;

		if ( ! empty($arrayIds) && $arrayIds > 0) {
			foreach ($arrayIds as $id) {
				$digiriskelement = new DigiriskElement($db);
				$digiriskelement->fetch((int) $id);
				$digiriskelement->ranks     = $i + 1;
				$digiriskelement->fk_parent = $arrayParentIds[$i];

				$digiriskelement->update($user);
				$i++;
			}
		}
	}
}

/*
 * View
 */

$form        = new Form($db);
$formconfirm = '';

$parameters                        = array('formConfirm' => $formconfirm, 'object' => $object);
$reshook                           = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

$helpUrl  = 'FR:Module_Digirisk';
$title    = $langs->trans('DigiriskElementOrganization');

saturne_header(0, '', $title, $helpUrl);

?>
<div id="cardContent" value="">
<?php
$objects = $object->fetchAll('',  'ranks',  0,  0, array('customsql' => 'status > 0'));
if (is_array($objects)) {
	$results = recurse_tree(0, 0, $objects);
} else {
	$results = array();
}
?>

<script>
	$(document).ready(function() {
		let organizationEdited = 0

		calcWidth($('#title0'));

		window.onresize = function(event) {
			//method to execute one time after a timer
		};

		//recursively calculate the Width all titles
		function calcWidth(obj){

			var titles =
				$(obj).siblings('.space').children('.route').children('.title');

			$(titles).each(function(index, element){
				var pTitleWidth = parseInt($(obj).css('width'));
				var leftOffset = parseInt($(obj).siblings('.space').css('margin-left'));

				var newWidth = pTitleWidth - leftOffset;

				if ($(obj).attr('id') == 'title0'){
					console.log("called");

					newWidth = newWidth - 10;
				}

				$(element).css({
					'width': newWidth,
				})

				calcWidth(element);
			});

		}

		$('.space').sortable({
			connectWith:'.space:not("'+'.workunit'+'")',
			tolerance:'intersect',
			over:function(event,ui){
				$('.save-organization').removeClass('button-disable')
				$('.save-organization').attr('style','z-index:1050')
				$('.save-organization .fas').attr('style','display:none')
			},
			receive:function(event, ui){
				organizationEdited++
				if (organizationEdited == 1) {
					$('a').click(function(e) {
						if (confirm("Modifications non enregistrées") == false) {
							e.preventDefault();
						}
					})
				}
				calcWidth($(this).siblings('.title'));
			},

		});

		$('.space').disableSelection();

	})
</script>
<div class="messageSuccessOrganizationSaved notice hidden">
	<div class="wpeo-notice notice-success organization-saved-success-notice">
		<div class="notice-content">
			<div class="notice-title"><?php echo $langs->trans('OrganizationSaved') ?></div>
			<div class="notice-subtitle">
				<span class="text"></span>
			</div>
		</div>
		<div class="notice-close"><i class="fas fa-times"></i></div>
	</div>
</div>
<div class="messageErrorOrganizationSaved notice hidden">
	<div class="wpeo-notice notice-error organization-saved-error-notice">
		<div class="notice-content">
			<div class="notice-title"><?php echo $langs->trans('OrganizationNotSaved') ?></div>
			<div class="notice-subtitle">
				<span class="text"></span>
			</div>
		</div>
		<div class="notice-close"><i class="fas fa-times"></i></div>
	</div>
</div>
<div class='container'>
	<input type="hidden" name="token" value="<?php echo newToken() ?>">
	<h3 class='title' id='title0'><?php echo $conf->global->MAIN_INFO_SOCIETE_NOM ?></h3>
	<ul class='space space-0 first-space ui-sortable' id='space0' value="0">
		<?php display_recurse_tree_organization($results) ?>
	</ul>
</div>
<?php
print '<hr>';
print '<button class="save-organization wpeo-button button-disable" style="">' . $langs->trans('Save') . '  <i style="display:none" class="fas fa-times"></i><i style="display:none" class="fas fa-check"></i></button>';

// End of page
llxFooter();
$db->close();
