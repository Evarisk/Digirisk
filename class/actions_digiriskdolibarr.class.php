<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
 * \file    class/actions_digiriskdolibarr.class.php
 * \ingroup digiriskdolibarr
 * \brief   DigiriskDolibarr hook overload.
 */

/**
 * Class ActionsDigiriskdolibarr
 */
class ActionsDigiriskdolibarr
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error string
	 */
	public $error;

	/**
	 * @var string[] Array of error strings
	 */
	public $errors = array();

	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Overloading the printCommonFooter function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printCommonFooter($parameters)
	{
		global $db, $conf, $langs;

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if ($parameters['currentcontext'] == 'admincompany') {	    // do something only for the context 'somecontext1' or 'somecontext2'
			?>
			<script src="../custom/digiriskdolibarr/js/digiriskdolibarr.js.php"></script>
			<?php
			if ($conf->global->MAIN_INFO_SOCIETE_COUNTRY == '1:FR:France') {
				require_once __DIR__ . '/../lib/digiriskdolibarr_function.lib.php';
				$form      = new Form($db);
				$pictopath = dol_buildpath('/digiriskdolibarr/img/digiriskdolibarr32px.png', 1);
				$pictoDigirisk = img_picto('', $pictopath, '', 1, 0, 0, '', 'pictoDigirisk');
				$idcc_form = digirisk_select_dictionary('DIGIRISKDOLIBARR_COLLECTIVE_AGREEMENT_TITLE', 'c_conventions_collectives', 'code', 'libelle', $conf->global->DIGIRISKDOLIBARR_COLLECTIVE_AGREEMENT_TITLE, 1);
				?>
				<script>
					let $tr = $('<tr class="oddeven"><td><label for="selectidcc_id"><?php print $pictoDigirisk . $form->textwithpicto($langs->trans('IDCC'), $langs->trans('IDCCTooltip'));?></label></td>');
					$tr.append('<td>' + <?php echo json_encode($idcc_form) ; ?> + '</td></tr>');

					let currElement = $('table:nth-child(7) .oddeven:last-child');
					currElement.after($tr);
				</script>
				<?php
			}
			print ajax_combobox('selectDIGIRISKDOLIBARR_COLLECTIVE_AGREEMENT_TITLE');
		} else if ($parameters['currentcontext'] == 'ticketcard') {
			if (GETPOST('action') == 'view' || empty(GETPOST('action'))) {
				print '<link rel="stylesheet" type="text/css" href="../custom/digiriskdolibarr/css/digiriskdolibarr.css">';

				require_once __DIR__ . '/../lib/digiriskdolibarr_function.lib.php';
				require_once __DIR__ . '/../class/digiriskdocuments/ticketdocument.class.php';
				require_once __DIR__ . '/../core/modules/digiriskdolibarr/digiriskdocuments/ticketdocument/mod_ticketdocument_standard.php';
				require_once __DIR__ . '/../core/modules/digiriskdolibarr/digiriskdocuments/ticketdocument/modules_ticketdocument.php';
				global $langs, $user;

				$object = new Ticket($this->db);
				$result = $object->fetch(GETPOST('id'),GETPOST('ref','alpha'),GETPOST('track_id','alpha'));
				$upload_dir = $conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1];
				$objref    = dol_sanitizeFileName($object->ref);
				$dir_files = $object->element . 'document/' . $objref;

				$filedir   = $upload_dir . '/' . $dir_files;
				$urlsource = $_SERVER["PHP_SELF"] . '?id=' . $object->id;

				$modulepart   = 'digiriskdolibarr:TicketDocument';
				$defaultmodel = $conf->global->DIGIRISKDOLIBARR_TICKET_DEFAULT_MODEL;
				$pictopath = dol_buildpath('/digiriskdolibarr/img/digiriskdolibarr32px.png', 1);
				$pictoDigirisk = img_picto('', $pictopath, '', 1, 0, 0, '', 'pictoDigirisk');
				$title        = $pictoDigirisk . $langs->trans('TicketDocument');

				$html = digiriskshowdocuments($modulepart, $dir_files, $filedir, $urlsource, 1, 0, $defaultmodel, 1, 0, '', $title, '', '', '', 0, 'remove_file');
				?>

				<script>
					jQuery('.fichehalfleft .div-table-responsive-no-min').append(<?php echo json_encode($html) ; ?>)
				</script>
				<?php
			}
		} else if (($parameters['currentcontext'] == 'projecttaskcard') || ($parameters['currentcontext'] == 'projecttaskscard') || ($parameters['currentcontext'] == 'tasklist')) {
			if ((GETPOST('action') == '' || empty(GETPOST('action')) || GETPOST('action') != 'edit')) {
				require_once __DIR__ . '/../../../projet/class/task.class.php';
				require_once __DIR__ . '/../class/riskanalysis/risk.class.php';
				require_once __DIR__ . '/../class/preventionplan.class.php';
				require_once __DIR__ . '/../class/firepermit.class.php';
				require_once __DIR__ . '/../class/accident.class.php';

				$task           = new Task($db);
				$risk           = new Risk($db);
				$preventionplan = new PreventionPlan($db);
				$firepermit     = new FirePermit($db);
				$accident       = new Accident($db);

				if ($parameters['currentcontext'] == 'projecttaskcard') {
					$task->fetch(GETPOST('id'));
					$task->fetch_optionals();

					$risk_id           = $task->array_options['options_fk_risk'];
					$preventionplan_id = $task->array_options['options_fk_preventionplan'];
					$firepermit_id     = $task->array_options['options_fk_firepermit'];
					$accident_id       = $task->array_options['options_fk_accident'];

					$risk->fetch($risk_id);
					$preventionplan->fetch($preventionplan_id);
					$firepermit->fetch($firepermit_id);
					$accident->fetch($accident_id);

					if (!empty($risk_id) && $risk_id > 0) { ?>
						<script>
							jQuery('.project_task_extras_fk_risk').html(<?php echo json_encode($risk->getNomUrl(1, 'blank')) ?>);
						</script>
					<?php }
					if (!empty($preventionplan_id) && $preventionplan_id > 0) { ?>
						<script>
							jQuery('.project_task_extras_fk_preventionplan').html(<?php echo json_encode($preventionplan->getNomUrl(1, 'blank')) ?>);
						</script>
					<?php }
					if (!empty($firepermit_id) && $firepermit_id > 0) { ?>
						<script>
							jQuery('.project_task_extras_fk_firepermit').html(<?php echo json_encode($firepermit->getNomUrl(1)) ?>);
						</script>
					<?php }
					if (!empty($accident_id) && $accident_id > 0) { ?>
						<script>
							jQuery('.project_task_extras_fk_accident').html(<?php echo json_encode($accident->getNomUrl(1)) ?>);
						</script>
					<?php }
				}

				if (($parameters['currentcontext'] == 'projecttaskscard') || ($parameters['currentcontext'] == 'tasklist')) {
					$task        = new Task($db);
					$extrafields = new ExtraFields($db);

					$extrafields->fetch_name_optionals_label($task->table_element);
					$alltasks = $task->getTasksArray(null, null, 0, 0, 0, '', '-1', '', 0, 0, $extrafields);

					if (is_array($alltasks) && !empty($alltasks)) {
						foreach ($alltasks as $tasksingle) {
							$risk_id = $tasksingle->options_fk_risk;
							$preventionplan_id = $tasksingle->options_fk_preventionplan;
							$firepermit_id = $tasksingle->options_fk_firepermit;
							$accident_id = $tasksingle->options_fk_accident;

							$risk->fetch($risk_id);
							$preventionplan->fetch($preventionplan_id);
							$firepermit->fetch($firepermit_id);
							$accident->fetch($accident_id);
							if ($parameters['currentcontext'] == 'projecttaskscard') {
								if (!empty($risk_id) && $risk_id > 0) { ?>
									<script>
										jQuery('.div-table-responsive').find('tr[id="row-' + <?php echo $tasksingle->id; ?> +'"]').find('td[data-key="projet_task.fk_risk"]').html(<?php echo json_encode($risk->getNomUrl(1, 'blank')) ?>);
									</script>
								<?php }
								if (!empty($preventionplan_id) && $preventionplan_id > 0) { ?>
									<script>
										jQuery('.div-table-responsive').find('tr[id="row-' + <?php echo $tasksingle->id; ?> +'"]').find('td[data-key="projet_task.fk_preventionplan"]').html(<?php echo json_encode($preventionplan->getNomUrl(1, 'blank')) ?>);
									</script>
								<?php }
								if (!empty($firepermit_id) && $firepermit_id > 0) { ?>
									<script>
										jQuery('.div-table-responsive').find('tr[id="row-' + <?php echo $tasksingle->id; ?> +'"]').find('td[data-key="projet_task.fk_firepermit"]').html(<?php echo json_encode($firepermit->getNomUrl(1)) ?>);
									</script>
								<?php }
								if (!empty($accident_id) && $accident_id > 0) { ?>
									<script>
										jQuery('.div-table-responsive').find('tr[id="row-' + <?php echo $tasksingle->id; ?> +'"]').find('td[data-key="projet_task.fk_accident"]').html(<?php echo json_encode($accident->getNomUrl(1)) ?>);
									</script>
								<?php }
							}
							if ($parameters['currentcontext'] == 'tasklist') {
								if (!empty($risk_id) && $risk_id > 0) { ?>
									<script>
										jQuery('.div-table-responsive').find('tr[data-rowid="' + <?php echo $tasksingle->id; ?> +'"]').find('td[data-key="projet_task.fk_risk"]').html(<?php echo json_encode($risk->getNomUrl(1, 'blank')) ?>);
									</script>
								<?php }
								if (!empty($preventionplan_id) && $preventionplan_id > 0) { ?>
									<script>
										jQuery('.div-table-responsive').find('tr[data-rowid="' + <?php echo $tasksingle->id; ?> +'"]').find('td[data-key="projet_task.fk_preventionplan"]').html(<?php echo json_encode($preventionplan->getNomUrl(1, 'blank')) ?>);
									</script>
								<?php }
								if (!empty($firepermit_id) && $firepermit_id > 0) { ?>
									<script>
										jQuery('.div-table-responsive').find('tr[data-rowid="' + <?php echo $tasksingle->id; ?> +'"]').find('td[data-key="projet_task.fk_firepermit"]').html(<?php echo json_encode($firepermit->getNomUrl(1)) ?>);
									</script>
								<?php }
								if (!empty($accident_id) && $accident_id > 0) { ?>
									<script>
									jQuery('.div-table-responsive').find('tr[data-rowid="' + <?php echo $tasksingle->id; ?> +'"]').find('td[data-key="projet_task.fk_accident"]').html(<?php echo json_encode($accident->getNomUrl(1)) ?>);
								</script>
								<?php }
							}
						}
					}
				}
			}
		} else if ($parameters['currentcontext'] == 'publicnewticketcard') {
			?>
			<script>
				let date = new Date();

				let month    = date.getMonth() + 1;
				let day      = date.getDate();
				let fulldate = (day < 10 ? '0' : '') + day + '/' + (month < 10 ? '0' : '') + month + '/' + date.getFullYear();
				let hour     = date.getHours();
				let min      = date.getMinutes();

				jQuery('#options_digiriskdolibarr_ticket_date').val(fulldate);
				jQuery('#options_digiriskdolibarr_ticket_dateday').val((day < 10 ? '0' : '') + day);
				jQuery('#options_digiriskdolibarr_ticket_datemonth').val((month < 10 ? '0' : '') + month);
				jQuery('#options_digiriskdolibarr_ticket_dateyear').val(date.getFullYear());
				jQuery('#options_digiriskdolibarr_ticket_datehour').val((hour < 10 ? '0' : '') + hour);
				jQuery('#options_digiriskdolibarr_ticket_datemin').val((min < 10 ? '0' : '') + min);
			</script>
			<?php
		}

		if (true) {
			$this->results   = array('myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 *  Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param Hook $parameters metadatas (context, etc...)
	 * @param $object current object
	 * @param $action
	 * @return int              < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, $object, $action)
	{
		global $db, $conf;

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if ($parameters['currentcontext'] == 'admincompany') {	    // do something only for the context 'somecontext1' or 'somecontext2'
			if ($action == 'update') {
				dolibarr_set_const($db, "DIGIRISKDOLIBARR_COLLECTIVE_AGREEMENT_TITLE", GETPOST("DIGIRISKDOLIBARR_COLLECTIVE_AGREEMENT_TITLE", 'nohtml'), 'chaine', 0, '', $conf->entity);
			}
		} else if ($parameters['currentcontext'] == 'ticketcard') {
			if ($action == 'digiriskbuilddoc') {
				require_once __DIR__ . '/../lib/digiriskdolibarr_function.lib.php';
				require_once __DIR__ . '/../class/digiriskdocuments/ticketdocument.class.php';
				require_once __DIR__ . '/../core/modules/digiriskdolibarr/digiriskdocuments/ticketdocument/mod_ticketdocument_standard.php';
				require_once __DIR__ . '/../core/modules/digiriskdolibarr/digiriskdocuments/ticketdocument/modules_ticketdocument.php';

				global $langs, $user;
				$ticketdocument = new TicketDocument($this->db);
				$outputlangs = $langs;
				$newlang     = '';

				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
				if ( ! empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}

				// To be sure vars is defined
				if (empty($hidedetails)) $hidedetails = 0;
				if (empty($hidedesc)) $hidedesc       = 0;
				if (empty($hideref)) $hideref         = 0;
				if (empty($moreparams)) $moreparams   = null;

				$model = GETPOST('model', 'alpha');

				$moreparams['object'] = $object;
				$moreparams['user']   = $user;

				$result = $ticketdocument->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
				if ($result <= 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					$action = '';
				} else {
					if (empty($donotredirect)) {
						setEventMessages($langs->trans("FileGenerated") . ' - ' . $ticketdocument->last_main_doc, null);

						$urltoredirect = $_SERVER['REQUEST_URI'];
						$urltoredirect = preg_replace('/#digiriskbuilddoc$/', '', $urltoredirect);
						$urltoredirect = preg_replace('/action=digiriskbuilddoc&?/', '', $urltoredirect); // To avoid infinite loop

						header('Location: ' . $urltoredirect );
						exit;
					}
				}
			}


		}

		if (true) {
			$this->results   = array('myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 *  Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param $parameters
	 * @return int
	 */
	public function emailElementlist($parameters)
	{
		global $conf, $user, $langs;

		$value = array();

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if ($parameters['currentcontext'] == 'emailtemplates') {	    // do something only for the context 'somecontext1' or 'somecontext2'
			if ($conf->digiriskdolibarr->enabled && $user->rights->digiriskdolibarr->preventionplan->read) {
				$value['preventionplan'] = '<i class="fas fa-info"></i>  ' . dol_escape_htmltag($langs->trans('PreventionPlan'));
			}
			if ($conf->digiriskdolibarr->enabled && $user->rights->digiriskdolibarr->firepermit->read) {
				$value['firepermit'] = '<i class="fas fa-fire-alt"></i>  ' . dol_escape_htmltag($langs->trans('FirePermit'));
			}
			if ($conf->digiriskdolibarr->enabled && $user->rights->digiriskdolibarr->riskassessmentdocument->read) {
				$value['riskassessmentdocument'] = '<i class="fas fa-exclamation-triangle"></i>  ' . dol_escape_htmltag($langs->trans('RiskAssessmentDocument'));
			}
		}

		if (true) {
			$this->results = $value;
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 *  Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param $parameters
	 * @return int
	 */
	public function redirectAfterConnection($parameters)
	{
		global $conf;

		$value = array();

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if ($parameters['currentcontext'] == 'mainloginpage') {	    // do something only for the context 'somecontext1' or 'somecontext2'
			if ($conf->global->DIGIRISKDOLIBARR_REDIRECT_AFTER_CONNECTION > 0) {
				$value = dol_buildpath('/custom/digiriskdolibarr/digiriskdolibarrindex.php?idmenu=1319&mainmenu=digiriskdolibarr&leftmenu=', 1);
			} else {
				$value = '';
			}
		}

		if (true) {
			$this->resprints = $value;
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}
}
