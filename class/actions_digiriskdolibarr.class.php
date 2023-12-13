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
     * Overloading the constructCategory function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @return  int                             0 < on error, 0 on success, 1 to replace standard code
     */
    public function constructCategory($parameters, &$object)
    {
        $error = 0; // Error counter

        if (($parameters['currentcontext'] == 'category')) {
            $tags = [
                'accident' => [
                    'id'        => 436302001,
                    'code'      => 'accident',
                    'obj_class' => 'Accident',
                    'obj_table' => 'digiriskdolibarr_accident',
                ],
                'preventionplan' => [
                    'id'        => 436302002,
                    'code'      => 'preventionplan',
                    'obj_class' => 'PreventionPlan',
                    'obj_table' => 'digiriskdolibarr_preventionplan',
                ],
                'firepermit' => [
                    'id'        => 436302003,
                    'code'      => 'firepermit',
                    'obj_class' => 'FirePermit',
                    'obj_table' => 'digiriskdolibarr_firepermit',
                ],
            ];
        }

        if (!$error) {
            $this->results = $tags;
            return 0; // or return 1 to replace standard code
        } else {
            $this->errors[] = 'Error message';
            return -1;
        }
    }

	/**
	 * Overloading the printCommonFooter function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printCommonFooter($parameters)
	{
		global $conf, $db, $form, $langs;

		require_once __DIR__ . '/../../saturne/lib/saturne_functions.lib.php';

		if ($parameters['currentcontext'] == 'admincompany') {	    // do something only for the context 'somecontext1' or 'somecontext2'
			?>
			<script src="../custom/digiriskdolibarr/js/digiriskdolibarr.js"></script>
			<?php
			if ($conf->global->MAIN_INFO_SOCIETE_COUNTRY == '1:FR:France') {
				require_once __DIR__ . '/../lib/digiriskdolibarr_function.lib.php';
				$form      = new Form($db);
                $pictopath = dol_buildpath('/custom/digiriskdolibarr/img/digiriskdolibarr_color.png', 1);
                $pictoDigirisk = img_picto('', $pictopath, '', 1, 0, 0, '', 'pictoModule');
				$idcc_form = digirisk_select_dictionary('DIGIRISKDOLIBARR_COLLECTIVE_AGREEMENT_TITLE', 'c_conventions_collectives', 'code', 'libelle', $conf->global->DIGIRISKDOLIBARR_COLLECTIVE_AGREEMENT_TITLE, 1, '', '', 'minwidth100');
				$pee_input = '<input type="checkbox" name="DIGIRISKDOLIBARR_PEE_ENABLED" '. ($conf->global->DIGIRISKDOLIBARR_PEE_ENABLED ? 'checked' : '') .'>';
				$perco_input = '<input type="checkbox" name="DIGIRISKDOLIBARR_PERCO_ENABLED" '. ($conf->global->DIGIRISKDOLIBARR_PERCO_ENABLED ? 'checked' : '') .'>';
				$nbemployees_input = '<input type="number" name="DIGIRISKDOLIBARR_NB_EMPLOYEES" class="minwidth200" value="' . $conf->global->DIGIRISKDOLIBARR_NB_EMPLOYEES . '"' . ($conf->global->DIGIRISKDOLIBARR_MANUAL_INPUT_NB_EMPLOYEES ? '' : 'disabled') . '>';
				$nbworkedhours_input = '<input type="number" name="DIGIRISKDOLIBARR_NB_WORKED_HOURS" class="minwidth200" value="' . $conf->global->DIGIRISKDOLIBARR_NB_WORKED_HOURS . '"' . ($conf->global->DIGIRISKDOLIBARR_MANUAL_INPUT_NB_WORKED_HOURS ? '' : 'disabled') . '>';
				?>
				<script>
					let collectiveAgreementDictionary = $('<tr class="oddeven"><td><label for="selectidcc_id"><?php print $pictoDigirisk . $form->textwithpicto($langs->trans('IDCC'), $langs->trans('IDCCTooltip'));?></label></td>');
					collectiveAgreementDictionary.append('<td>' + <?php echo json_encode($idcc_form) ; ?> + '</td></tr>');

					let peeInput = $('<tr class="oddeven"><td><label for="pee"><?php print $pictoDigirisk . $form->textwithpicto($langs->trans('PEE'), $langs->trans('PEETooltip'));?></label></td>');
					peeInput.append('<td>' + <?php echo json_encode($pee_input) ; ?> + '</td></tr>');

					let percoInput = $('<tr class="oddeven"><td><label for="perco"><?php print $pictoDigirisk . $form->textwithpicto($langs->trans('PERCO'), $langs->trans('PERCOTooltip'));?></label></td>');
					percoInput.append('<td>' + <?php echo json_encode($perco_input) ; ?> + '</td></tr>');

					let nbemployeesInput = $('<tr class="oddeven"><td><label for="nbemployees"><?php print $pictoDigirisk . $form->textwithpicto($langs->transnoentities('NbEmployees'), $langs->transnoentities('HowToConfigureSetupConf')); ?></label></td>');
					nbemployeesInput.append('<td><i class="fas fa-users"></i> ' + <?php echo json_encode($nbemployees_input); ?> + '</td></tr>');

					let nbworkedhoursInput = $('<tr class="oddeven"><td><label for="nbworkedhours"><?php print $pictoDigirisk . $form->textwithpicto($langs->transnoentities('NbWorkedHours'), $langs->transnoentities('HowToConfigureSetupConf')); ?></label></td>');
					nbworkedhoursInput.append('<td><i class="fas fa-clock"></i> ' + <?php echo json_encode($nbworkedhours_input); ?> + '</td></tr>');

					let currentOtherElement = $('table:nth-child(3) .oddeven:last-child');
					currentOtherElement.after(nbworkedhoursInput);
					currentOtherElement.after(nbemployeesInput);

					let currentElement = $('table:nth-child(7) .oddeven:last-child');
					currentElement.after(collectiveAgreementDictionary);
					currentElement.after(peeInput);
					currentElement.after(percoInput);
				</script>
				<?php
				print ajax_combobox('selectDIGIRISKDOLIBARR_COLLECTIVE_AGREEMENT_TITLE');
			}
		} else if ($parameters['currentcontext'] == 'ticketcard') {
			if (GETPOST('action') == 'view' || empty(GETPOST('action')) || GETPOST('action') == 'update_extras') {
				print '<link rel="stylesheet" type="text/css" href="../custom/digiriskdolibarr/css/digiriskdolibarr.css">';
				print '<script src="../custom/digiriskdolibarr/js/digiriskdolibarr.js"></script>';

				require_once __DIR__ . '/../lib/digiriskdolibarr_function.lib.php';
				require_once __DIR__ . '/../class/digiriskdolibarrdocuments/ticketdocument.class.php';
				require_once __DIR__ . '/../../saturne/core/modules/saturne/modules_saturne.php';

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
				$pictopath = dol_buildpath('/digiriskdolibarr/img/digiriskdolibarr_color.png', 1);
				$pictoDigirisk = img_picto('', $pictopath, '', 1, 0, 0, '', 'pictoDigirisk');
				$title        = $pictoDigirisk . $langs->trans('TicketDocument');

				require_once __DIR__ . '/../lib/digiriskdolibarr_function.lib.php';

				if(is_numeric($object->array_options['options_digiriskdolibarr_ticket_service']) && $object->array_options['options_digiriskdolibarr_ticket_service'] > 0) {
					require_once __DIR__ . '/digiriskelement.class.php';
					$digiriskelement = new DigiriskElement($db);
					$digiriskelement->fetch($object->array_options['options_digiriskdolibarr_ticket_service']);
					$selectDictionnary = $digiriskelement->getNomUrl(1, 'blank', 0, '', -1, 1);
					?>
					<script>
					jQuery('.ticket_extras_digiriskdolibarr_ticket_service').html('')
					jQuery('.ticket_extras_digiriskdolibarr_ticket_service').prepend(<?php echo json_encode($selectDictionnary) ; ?>)
					</script>
					<?php
				}
				$html = saturne_show_documents($modulepart, $dir_files, $filedir, $urlsource, 1,1, '', 1, 0, 0, 0, 0, '', 0, '', empty($soc->default_lang) ? '' : $soc->default_lang, $object);

				?>

				<script>
					jQuery('.fichehalfleft .div-table-responsive-no-min').append(<?php echo json_encode($html) ; ?>)
				</script>
				<?php

                require_once __DIR__ . '/accident.class.php';
                $accident           = new Accident($db);
                $linkedAccidents    = $accident->fetchAll('', '', 0, 0, ['customsql' => 't.fk_ticket = ' . $object->id]);
                $linkedAccidentList = '';
                if (is_array($linkedAccidents) && !empty($linkedAccidents)) {
                    foreach ($linkedAccidents as $linkedAccident) {
                        $linkedAccidentList .= $linkedAccident->getNomUrl(1) . '</br>';
                    }
                }

                $fieldLinkedAccidents  = '<tr class="trextrafields_collapse_2"><td class="titlefield">'.$langs->trans('AccidentsLinked').'</td>';
                $fieldLinkedAccidents .= '<td id="ticket_extras_digiriskdolibarr_ticket_accident_'. $object->id .'" class="valuefield ticket_extras_digiriskdolibarr_ticket_accident wordbreak">';
                $fieldLinkedAccidents .= $linkedAccidentList;
                $fieldLinkedAccidents .= '</td>';
                $fieldLinkedAccidents .= '</tr>';
                ?>
                <script>
                    jQuery('table.border.tableforfield.centpercent').first().append(<?php echo json_encode($fieldLinkedAccidents) ; ?>)
                </script>
                <?php
			}
			if (GETPOST('action') == 'edit_extras' && GETPOST('attribute') == 'digiriskdolibarr_ticket_service') {
				require_once __DIR__ . '/../lib/digiriskdolibarr_function.lib.php';

				$object = new Ticket($db);
				$object->fetch(GETPOST('id'),'',GETPOST('track_id'));
				require_once __DIR__ . '/digiriskelement.class.php';
				$digiriskelement = new DigiriskElement($db);
				$selectDigiriskElement = $digiriskelement->selectDigiriskElementList($object->array_options['options_digiriskdolibarr_ticket_service'], 'options_digiriskdolibarr_ticket_service', [], 1, 0, array(), 0, 0, 'minwidth100', 0, false, 1);
				?>
				<script>
					jQuery('#options_digiriskdolibarr_ticket_service').remove()
					jQuery('.ticket_extras_digiriskdolibarr_ticket_service form').prepend(<?php echo json_encode($selectDigiriskElement) ; ?>)
				</script>
				<?php
			}
			if (GETPOST('action') == 'create') {
				require_once __DIR__ . '/../lib/digiriskdolibarr_function.lib.php';
				require_once __DIR__ . '/digiriskelement.class.php';
				$digiriskelement = new DigiriskElement($db);
				$selectDigiriskElement = '<tr class="valuefieldcreate ticket_extras_digiriskdolibarr_ticket_service trextrafields_collapse" data-element="extrafield" data-targetelement="ticket" data-targetid=""><td class="wordbreak">'.$langs->trans('GP/UT').'</td>';
				$selectDigiriskElement .= '<td class="ticket_extras_digiriskdolibarr_ticket_service">';
				$selectDigiriskElement .= $digiriskelement->selectDigiriskElementList(GETPOST('options_digiriskdolibarr_ticket_service'), 'options_digiriskdolibarr_ticket_service', [], 1, 0, array(), 0, 0, 'minwidth500', 0, false, 1);
				$selectDigiriskElement .= '</td>';
				$selectDigiriskElement .= '</tr>';
				?>
				<script>
					jQuery('tr.ticket_extras_digiriskdolibarr_ticket_firstname').after(<?php echo json_encode($selectDigiriskElement) ; ?>)
				</script>
				<?php
			}
			if (GETPOST('action') == 'add_message') {

				$object = new Ticket($this->db);
				$result = $object->fetch(GETPOST('id'),GETPOST('ref','alpha'),GETPOST('track_id','alpha'));

				if ($result > 0) {
					$object->fetch_optionals();

					require_once __DIR__ . '/../class/digiriskelement.class.php';

					$digiriskelement = new DigiriskElement($this->db);
					$digiriskelement->fetch($object->array_options['options_digiriskdolibarr_ticket_service']);

					?>
					<script>
						let mailContent = $('#message').html()
						let digiriskElementRefAndLabel = <?php echo json_encode($digiriskelement->ref . ' - ' . $digiriskelement->label); ?>;
						let digiriskElementId = <?php echo json_encode($digiriskelement->id); ?>;
						let mailContentWithDigiriskElementLabel = mailContent.replace('__EXTRAFIELD_DIGIRISKDOLIBARR_TICKET_SERVICE_NAME__ ', digiriskElementRefAndLabel);
						$('#message').html(mailContentWithDigiriskElementLabel);
					</script>
					<?php
				}
			}
		} else if (in_array($parameters['currentcontext'], array('projectcard', 'projectcontactcard', 'projecttaskscard', 'projecttaskcard', 'projecttasktime', 'projectOverview', 'projecttaskscard', 'tasklist', 'category'))) {
			if ((GETPOST('action') == '' || empty(GETPOST('action')) || GETPOST('action') != 'edit')) {
				require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
				require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';

				require_once __DIR__ . '/../class/riskanalysis/risk.class.php';
				require_once __DIR__ . '/../class/preventionplan.class.php';
				require_once __DIR__ . '/../class/firepermit.class.php';
                require_once __DIR__ . '/../class/accident.class.php';
                require_once __DIR__ . '/../class/accidentinvestigation.class.php';

				global $user;

				$task                  = new SaturneTask($db);
				$risk                  = new Risk($db);
				$preventionplan        = new PreventionPlan($db);
				$firepermit            = new FirePermit($db);
                $accident              = new Accident($db);
                $accidentinvestigation = new AccidentInvestigation($db);
				$project               = new Project($db);
				$extrafields           = new ExtraFields($db);

				if ($parameters['currentcontext'] == 'projecttaskcard') {
					$task->fetch(GETPOST('id'));
					$task->fetch_optionals();

					$risk_id                  = $task->array_options['options_fk_risk'];
					$preventionplan_id        = $task->array_options['options_fk_preventionplan'];
					$firepermit_id            = $task->array_options['options_fk_firepermit'];
                    $accident_id              = $task->array_options['options_fk_accident'];
                    $accidentinvestigation_id = $task->array_options['options_fk_accidentinvestigation'];

					$risk->fetch($risk_id);
					$preventionplan->fetch($preventionplan_id);
					$firepermit->fetch($firepermit_id);
					$accident->fetch($accident_id);
                    $accidentinvestigation->fetch($accidentinvestigation_id);

                    $pictoDigirisk = img_picto('', 'digiriskdolibarr_color@digiriskdolibarr', 'class="pictofixedwidth"');

                    ?>
                    <script>
                        jQuery('.project_task_extras_fk_risk').closest('tr').find('.titlefield td').prepend(<?php echo json_encode($pictoDigirisk); ?>)
                        jQuery('.project_task_extras_fk_preventionplan').closest('tr').find('.titlefield td').prepend(<?php echo json_encode($pictoDigirisk); ?>)
                        jQuery('.project_task_extras_fk_firepermit').closest('tr').find('.titlefield td').prepend(<?php echo json_encode($pictoDigirisk); ?>)
                        jQuery('.project_task_extras_fk_accident').closest('tr').find('.titlefield td').prepend(<?php echo json_encode($pictoDigirisk); ?>)
                        jQuery('.project_task_extras_fk_accidentinvestigation').closest('tr').find('.titlefield td').prepend(<?php echo json_encode($pictoDigirisk); ?>)
                    </script>

                        <?php
					if (!empty($risk_id) && $risk_id > 0) { ?>
						<script>
                            jQuery('.project_task_extras_fk_risk').html(<?php echo json_encode($risk->getNomUrl(1, 'nolink')) ?>);
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
                    if (!empty($accidentinvestigation_id) && $accidentinvestigation_id > 0) { ?>
                        <script>
                            jQuery('.project_task_extras_fk_accidentinvestigation').html(<?php echo json_encode($accidentinvestigation->getNomUrl(1)) ?>);
                        </script>
                    <?php }
				}

				if (($parameters['currentcontext'] == 'projecttaskscard') || ($parameters['currentcontext'] == 'tasklist')) {
					$extrafields->fetch_name_optionals_label($task->table_element);
					$alltasks = $task->getTasksArray(null, null, 0, 0, 0, '', '-1', '', 0, 0, $extrafields);

					if (is_array($alltasks) && !empty($alltasks)) {
						foreach ($alltasks as $tasksingle) {
							$risk_id                  = $tasksingle->options_fk_risk;
							$preventionplan_id        = $tasksingle->options_fk_preventionplan;
							$firepermit_id            = $tasksingle->options_fk_firepermit;
                            $accident_id              = $tasksingle->options_fk_accident;
                            $accidentinvestigation_id = $tasksingle->options_fk_accidentinvestigation;

							$risk->fetch($risk_id);
							$preventionplan->fetch($preventionplan_id);
							$firepermit->fetch($firepermit_id);
                            $accident->fetch($accident_id);
                            $accidentinvestigation->fetch($accidentinvestigation_id);
							if ($parameters['currentcontext'] == 'projecttaskscard') {
								if (!empty($risk_id) && $risk_id > 0) { ?>
									<script>
										jQuery('.div-table-responsive').find('tr[id="row-' + <?php echo $tasksingle->id; ?> +'"]').find('td[data-key="projet_task.fk_risk"]').html(<?php echo json_encode($risk->getNomUrl(1, 'nolink')) ?>);
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
                                if (!empty($accidentinvestigation_id) && $accidentinvestigation_id > 0) { ?>
                                    <script>
                                        jQuery('.div-table-responsive').find('tr[id="row-' + <?php echo $tasksingle->id; ?> +'"]').find('td[data-key="projet_task.fk_accidentinvestigation"]').html(<?php echo json_encode($accidentinvestigation->getNomUrl(1)) ?>);
                                    </script>
                                <?php }
							}
							if ($parameters['currentcontext'] == 'tasklist') {
								if (!empty($risk_id) && $risk_id > 0) { ?>
									<script>
										jQuery('.div-table-responsive').find('tr[data-rowid="' + <?php echo $tasksingle->id; ?> +'"]').find('td[data-key="projet_task.fk_risk"]').html(<?php echo json_encode($risk->getNomUrl(1, 'nolink')) ?>);
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
                                if (!empty($accidentinvestigation_id) && $accidentinvestigation_id > 0) { ?>
                                    <script>
                                        jQuery('.div-table-responsive').find('tr[data-rowid="' + <?php echo $tasksingle->id; ?> +'"]').find('td[data-key="projet_task.fk_accidentinvestigation"]').html(<?php echo json_encode($accidentinvestigation->getNomUrl(1)) ?>);
                                    </script>
                                <?php }
							}
						}
					}
				}

				if (in_array($parameters['currentcontext'], ['projectcard', 'projectcontactcard', 'projecttaskcard', 'projecttaskscard', 'projecttasktime', 'projectOverview']) || ($parameters['currentcontext'] == 'category' && preg_match('/contacttpl/', $parameters['context']))) {
                    if (in_array($parameters['currentcontext'], ['projecttaskcard']) && !GETPOSTISSET('withproject')) {
                        return 0;
                    } else {
                        if (GETPOSTISSET('projectid') || GETPOSTISSET('project_ref')) {
                            $project->fetch( GETPOST('projectid'), GETPOST('project_ref'));
                            $projectId = $project->id;
                        } else if (in_array($parameters['currentcontext'], ['projectcard', 'projectcontactcard', 'projecttaskscard'])) {
                            $projectId = GETPOST('id');
                        } else {
                            $task->fetch(GETPOST('id'));
                            $projectId = $task->fk_project;
                        }
                        $allTasks = $task->getTasksArray(null, null, $projectId, 0, 0, '', '-1', '', 0, 0, $extrafields);
                        if (is_array($allTasks) && !empty($allTasks)) {
                            $nbTasks = count($allTasks);
                            foreach ($allTasks as $taskSingle) {
                                $filter       = ' AND fk_element = ' . $taskSingle->id;
                                $allTimespent = $task->fetchAllTimeSpentAllUsers($filter);
                                foreach ($allTimespent as $timespent) {
                                    $totatConsumedTimeAmount += convertSecondToTime($timespent->timespent_duration, 'allhourmin') * $timespent->timespent_thm;
                                }
                                $totalConsumedTime += $taskSingle->duration;
                                $totalProgress     += $taskSingle->progress;
                                $totalTasksBudget  += $taskSingle->budget_amount;
                            }
                        } else {
                            $totalConsumedTime       = 0;
                            $totatConsumedTimeAmount = 0;
                            $nbTasks                 = 0;
                            $totalProgress           = 0;
                            $totalTasksBudget        = 0;
                        }
                        $outTotatConsumedTime       = '<tr><td>' . $langs->trans('TotalConsumedTime') . '</td><td>' . convertSecondToTime($totalConsumedTime, 'allhourmin') . '</td></tr>';
                        $outTotatConsumedTimeAmount = '<tr><td>' . $langs->trans('TotalConsumedTimeAmount') . '</td><td>' . price($totatConsumedTimeAmount, 0, $langs, 1, -1, 2, $conf->currency) . '</td></tr>';
                        $outNbtasks                 = '<tr><td>' . $langs->trans('NbTasks') . '</td><td>' . $nbTasks . '</td></tr>';
                        $outTotalProgress           = '<tr><td>' . $langs->trans('TotalProgress') . '</td><td>' . (($totalProgress) ? price2num($totalProgress/$nbTasks, 2) . ' %' : '0 %') . '</td></tr>';
                        $outTotalTasksBudget        = '<tr><td>' . $langs->trans('TotalBudget') . '</td><td>' . price($totalTasksBudget, 0, $langs, 1, -1, 2, $conf->currency) . '</td></tr>';?>
                        <script>
                            jQuery('.fichecenter .fichehalfright .tableforfield tbody tr:last-child').first().after(<?php echo json_encode($outTotatConsumedTime) ?>);
                            jQuery('.fichecenter .fichehalfright .tableforfield tbody tr:last-child').first().after(<?php echo json_encode($outTotatConsumedTimeAmount) ?>);
                            jQuery('.fichecenter .fichehalfright .tableforfield tbody tr:last-child').first().after(<?php echo json_encode($outNbtasks) ?>);
                            jQuery('.fichecenter .fichehalfright .tableforfield tbody tr:last-child').first().after(<?php echo json_encode($outTotalProgress) ?>);
                            jQuery('.fichecenter .fichehalfright .tableforfield tbody tr:last-child').first().after(<?php echo json_encode($outTotalTasksBudget) ?>);
                        </script> <?php
                    }
				}
			}
		} else if ($parameters['currentcontext'] == 'publicnewticketcard') {
			if (!$conf->multicompany->enabled) {
				$entity = $conf->entity;
			} else {
				$entity = GETPOST('entity');
			}
			if ($entity > 0) {
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
		} elseif (preg_match('/categoryindex/', $parameters['context'])) {	    // do something only for the context 'somecontext1' or 'somecontext2'
            print '<script src="../custom/digiriskdolibarr/js/digiriskdolibarr.js"></script>';
        } elseif (preg_match('/categorycard/', $parameters['context']) && preg_match('/viewcat.php/', $_SERVER["PHP_SELF"])) {
            global $user;
            $id = GETPOST('id');
            $type = GETPOST('type');

            // Load variable for pagination
            $limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
            $sortfield = GETPOST('sortfield', 'aZ09comma');
            $sortorder = GETPOST('sortorder', 'aZ09comma');
            $page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
            if (empty($page) || $page == -1) {
                $page = 0;
            }     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
            $offset = $limit * $page;

            if ($type == 'accident' || $type == 'preventionplan' || $type == 'firepermit') {
                require_once __DIR__ . '/' . $type . '.class.php';

                $classname = ucfirst($type);
                $object = new $classname($this->db);

                $arrayObjects = $object->fetchAll();
                if (is_array($arrayObjects) && !empty($arrayObjects)) {
                    foreach ($arrayObjects as $objectsingle) {
                        $array[$objectsingle->id] = $objectsingle->ref;
                    }
                }

                $category = new Categorie($this->db);
                $category->fetch($id);
                $objectsInCateg = $category->getObjectsInCateg($type, 0, $limit, $offset);

                $out = '<br>';

                $out .= '<form method="post" action="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&type=' . $type . '">';
                $out .= '<input type="hidden" name="token" value="'.newToken().'">';
                $out .= '<input type="hidden" name="action" value="addintocategory">';

                $out .= '<table class="noborder centpercent">';
                $out .= '<tr class="liste_titre"><td>';
                $out .= $langs->trans("Add". ucfirst($type) . "IntoCategory") . ' ';
                $out .= $form->selectarray('element_id', $array, '', 1);
                $out .= '<input type="submit" class="button buttongen" value="'.$langs->trans("ClassifyInCategory").'"></td>';
                $out .= '</tr>';
                $out .= '</table>';
                $out .= '</form>';

                $out .= '<br>';

                //$param = '&limit=' . $limit . '&id=' . $id . '&type=' . $type;
                //$num = count($objectsInCateg);
                //print_barre_liste($langs->trans(ucfirst($type)), $page, $_SERVER["PHP_SELF"], $param, '', '', '', $num, '', 'object_'.$type.'@digiquali', 0, '', '', $limit);

                $out .= load_fiche_titre($langs->transnoentities($classname), '', 'object_' . $object->picto);
                $out .= '<table class="noborder centpercent">';
                $out .= '<tr class="liste_titre"><td colspan="3">'.$langs->trans("Ref").'</td></tr>';

                if (is_array($objectsInCateg) && !empty($objectsInCateg)) {
                    // Form to add record into a category
                    if (count($objectsInCateg) > 0) {
                        $i = 0;
                        foreach ($objectsInCateg as $element) {
                            $i++;
                            if ($i > $limit) break;

                            $out .= '<tr class="oddeven">';
                            $out .= '<td class="nowrap" valign="top">';
                            $out .= $element->getNomUrl(1);
                            $out .= '</td>';
                            // Link to delete from category
                            $out .= '<td class="right">';
                            if ($user->rights->categorie->creer) {
                                $out .= '<a href="' . $_SERVER["PHP_SELF"] . '?action=delintocategory&id=' . $id . '&type=' . $type . '&element_id=' . $element->id . '&token=' . newToken() . '">';
                                $out .= $langs->trans("DeleteFromCat");
                                $out .= img_picto($langs->trans("DeleteFromCat"), 'unlink', '', false, 0, 0, '', 'paddingleft');
                                $out .= '</a>';
                            }
                            $out .= '</td>';
                            $out .= '</tr>';
                        }
                    } else {
                        $out .= '<tr class="oddeven"><td colspan="2" class="opacitymedium">'.$langs->trans("ThisCategoryHasNoItems").'</td></tr>';
                    }
                }

                $out .= '</table>';
            } ?>

            <script>
                jQuery('.fichecenter').last().after(<?php echo json_encode($out) ; ?>)
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
		global $conf, $db;

        $error = 0;
		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if ($parameters['currentcontext'] == 'admincompany') {	    // do something only for the context 'somecontext1' or 'somecontext2'
			if ($action == 'update') {
				dolibarr_set_const($db, "DIGIRISKDOLIBARR_COLLECTIVE_AGREEMENT_TITLE", GETPOST("DIGIRISKDOLIBARR_COLLECTIVE_AGREEMENT_TITLE", 'nohtml'), 'chaine', 0, '', $conf->entity);
				dolibarr_set_const($db, "DIGIRISKDOLIBARR_PEE_ENABLED", GETPOST("DIGIRISKDOLIBARR_PEE_ENABLED") == 'on' ? 1 : 0, 'integer', 0, '', $conf->entity);
				dolibarr_set_const($db, "DIGIRISKDOLIBARR_PERCO_ENABLED", GETPOST("DIGIRISKDOLIBARR_PERCO_ENABLED") == 'on' ? 1 : 0, 'integer', 0, '', $conf->entity);
				if ($conf->global->DIGIRISKDOLIBARR_MANUAL_INPUT_NB_EMPLOYEES) {
					dolibarr_set_const($db, "DIGIRISKDOLIBARR_NB_EMPLOYEES", GETPOST("DIGIRISKDOLIBARR_NB_EMPLOYEES"), 'integer', 0, '', $conf->entity);
				}
				if ($conf->global->DIGIRISKDOLIBARR_MANUAL_INPUT_NB_WORKED_HOURS) {
					dolibarr_set_const($db, "DIGIRISKDOLIBARR_NB_WORKED_HOURS", GETPOST("DIGIRISKDOLIBARR_NB_WORKED_HOURS"), 'integer', 0, '', $conf->entity);
				}
			}
		} else if ($parameters['currentcontext'] == 'ticketcard') {
			if ($action == 'digiriskbuilddoc') {
				require_once __DIR__ . '/../lib/digiriskdolibarr_function.lib.php';
				require_once __DIR__ . '/../class/digiriskdolibarrdocuments/ticketdocument.class.php';

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

				$moreparams['object']     = $object;
				$moreparams['user']       = $user;
				$moreparams['objectType'] = $object->element;

				$result = $ticketdocument->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);

				if ($result <= 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					$action = '';
				} else {
					if (empty($donotredirect)) {
						setEventMessages($langs->trans('FileGenerated') . ' - ' . '<a href=' . DOL_URL_ROOT . '/document.php?modulepart=digiriskdolibarr&file=ticketdocument/' . (dol_strlen($object->ref) > 0 ? $object->ref . '/' : '') . urlencode($ticketdocument->last_main_doc) . '&entity=' . $conf->entity . '"' . '>' . $ticketdocument->last_main_doc . '</a>', []);

						$urltoredirect = $_SERVER['REQUEST_URI'];
						$urltoredirect = preg_replace('/#digiriskbuilddoc$/', '', $urltoredirect);
						$urltoredirect = preg_replace('/action=digiriskbuilddoc&?/', '', $urltoredirect); // To avoid infinite loop

						header('Location: ' . $urltoredirect );
						exit;
					}
				}
			}

			$upload_dir = $conf->digiriskdolibarr->multidir_output[isset($conf->entity) ? $conf->entity : 1];

			// Action to generate pdf from odt file
            require_once __DIR__ . '/../../saturne/core/tpl/documents/saturne_manual_pdf_generation_action.tpl.php';

			if ($action == 'pdfGeneration') {
				$urltoredirect = $_SERVER['REQUEST_URI'];
				$urltoredirect = preg_replace('/#pdfGeneration$/', '', $urltoredirect);
				$urltoredirect = preg_replace('/action=pdfGeneration&?/', '', $urltoredirect); // To avoid infinite loop

				header('Location: ' . $urltoredirect );
				exit;
			}
		} elseif (in_array($parameters['currentcontext'] , array('ticketlist', 'thirdpartyticket', 'projectticket'))) {
			if ($action == 'list') {
				if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
					$searchCategoryTicketList = GETPOST('search_category_ticket_list', 'array');
					if (is_array($searchCategoryTicketList) && !empty($searchCategoryTicketList)) {
						$_GET['search_category_ticket_list'] = array();
					} else {
						$_GET['search_category_ticket_list'] = '';
					}
				}
			}
		} elseif (preg_match('/categorycard/', $parameters['context'])) {
            global $langs, $user;
            $id = GETPOST('id');
            $elementId = GETPOST('element_id');
            $type = GETPOST('type');
            if ($id > 0 && $elementId > 0 && (($type == 'accident' || $type == 'preventionplan' || $type == 'firepermit') && $user->rights->digiriskdolibarr->$type->write)) {

                require_once __DIR__ . '/' . $type . '.class.php';
                $classname = ucfirst($type);
                $newobject = new $classname($this->db);

                $newobject->fetch($elementId);

                if (GETPOST('action') == 'addintocategory') {
                    $result = $object->add_type($newobject, $type);
                    if ($result >= 0) {
                        setEventMessages($langs->trans("WasAddedSuccessfully", $newobject->ref), array());

                    } else {
                        if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                            setEventMessages($langs->trans("ObjectAlreadyLinkedToCategory"), array(), 'warnings');
                        } else {
                            setEventMessages($object->error, $object->errors, 'errors');
                        }
                    }
                } elseif (GETPOST('action') == 'delintocategory') {
                    $result = $object->del_type($newobject, $type);
                    if ($result < 0) {
                        dol_print_error('', $object->error);
                    }
                    $action = '';
                }
            }
        }
		if (!$error) {
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
		if (isModEnabled('digiriskdolibarr') && $parameters['currentcontext'] == 'emailtemplates') {	    // do something only for the context 'somecontext1' or 'somecontext2'
			if ($user->hasRight('digiriskdolibarr', 'preventionplan', 'read')) {
				$value['preventionplan'] = '<i class="fas fa-info"></i>  ' . dol_escape_htmltag($langs->trans('PreventionPlan'));
			}
			if ($user->hasRight('digiriskdolibarr', 'firepermit', 'read')) {
				$value['firepermit'] = '<i class="fas fa-fire-alt"></i>  ' . dol_escape_htmltag($langs->trans('FirePermit'));
			}
			if ($user->hasRight('digiriskdolibarr', 'riskassessmentdocument', 'read')) {
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

	/**
	 *  Overloading the printFieldListFrom function : replacing the parent's function with the one below
	 *
	 * @param Hook $parameters metadatas (context, etc...)
	 * @param $object current object
	 * @return int
	 */
	public function printFieldListFrom($parameters, $object)
	{
		global $conf, $user, $langs;

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'] , array('ticketlist', 'thirdpartyticket', 'projectticket'))) {	    // do something only for the context 'somecontext1' or 'somecontext2'
			$searchCategoryTicketList = GETPOST('search_category_ticket_list');
			if (!empty($searchCategoryTicketList)) {
				$sql = ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_ticket as ct ON t.rowid = ct.fk_ticket"; // We'll need this table joined to the select in order to filter by categ
			}
		}

		if (true) {
			$this->resprints = $sql;
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}


	/**
	 *  Overloading the printFieldListWhere function : replacing the parent's function with the one below
	 *
	 * @param Hook $parameters metadatas (context, etc...)
	 * @param $object current object
	 * @return int
	 */
	public function printFieldListWhere($parameters, $object)
	{
		global $conf, $user, $langs;

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'] , array('ticketlist', 'thirdpartyticket', 'projectticket'))) {        // do something only for the context 'somecontext1' or 'somecontext2'
			$searchCategoryTicketSqlList = array();
			$searchCategoryTicketList = GETPOST('search_category_ticket_list');
			if (is_array($searchCategoryTicketList) && !empty($searchCategoryTicketList)) {
				foreach ($searchCategoryTicketList as $searchCategoryTicket) {
					if (intval($searchCategoryTicket) == -2) {
						$searchCategoryTicketSqlList[] = "ct.fk_categorie IS NULL";
					} elseif (intval($searchCategoryTicket) > 0) {
						$searchCategoryTicketSqlList[] = "t.rowid IN (SELECT fk_ticket FROM " . MAIN_DB_PREFIX . "categorie_ticket WHERE fk_categorie = " . ((int)$searchCategoryTicket) . ")";
					}
				}
				if (!empty($searchCategoryTicketSqlList)) {
					$sql = " AND (" . implode(' AND ', $searchCategoryTicketSqlList) . ")";
				}
			} else {
				if (!empty($searchCategoryTicketList) && $searchCategoryTicketList > 0) {
					$sql = " AND ct.fk_categorie = ".((int) $searchCategoryTicketList);
				}
				if ($searchCategoryTicketList == -2) {
					$sql = " AND ct.fk_categorie IS NULL";
				}
			}
			if (!empty($searchCategoryTicketList)) {
				$sql .= " GROUP BY t.rowid";
			}
		}

		if ($parameters['currentcontext'] == 'userlist') {
			$user->fetchAll('','','','',['login' => 'USERAPI']);

			if (is_array($user->users) && !empty($user->users)) {
				$userIds = implode(',', array_keys($user->users));
				$sql .= ' AND u.rowid NOT IN (' . $userIds . ')';
			}
		}

		if (true) {
			$this->resprints = $sql;
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}


	/**
	 *  Overloading the printFieldPreListTitle function : replacing the parent's function with the one below
	 *
	 * @param Hook $parameters metadatas (context, etc...)
	 * @param $object current object
	 * @return int
	 */
	public function printFieldPreListTitle($parameters, $object)
	{
		global $conf, $db, $user, $langs;

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'] , array('ticketlist', 'thirdpartyticket', 'projectticket'))) {        // do something only for the context 'somecontext1' or 'somecontext2'
			require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

			$form = new Form($db);

			// Filter on categories
			$moreforfilter = '';
			if (!empty($conf->categorie->enabled) && $user->rights->categorie->lire) {
				$moreforfilter .= '<div class="divsearchfield">';
				$moreforfilter .= img_picto($langs->trans('Categories'), 'category', 'class="pictofixedwidth"');
				$categoriesTicketArr = $form->select_all_categories(Categorie::TYPE_TICKET, '', '', 64, 0, 1);
				$categoriesTicketArr[-2] = '- ' . $langs->trans('NotCategorized') . ' -';
				$searchCategoryTicketList = GETPOST('search_category_ticket_list');
				if (is_array($searchCategoryTicketList) && !empty($searchCategoryTicketList)) {
					$searchCategoryTicket = GETPOST('search_category_ticket_list', 'array');
				} else {
					$searchCategoryTicket = array($searchCategoryTicketList);
				}
				$moreforfilter .= Form::multiselectarray('search_category_ticket_list', $categoriesTicketArr, $searchCategoryTicket, 0, 0, 'minwidth300');
				$moreforfilter .= '</div>';
			}
		}

		if (true) {
			$this->resprints = $moreforfilter;
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 *  Overloading the printFieldListSearchParam function : replacing the parent's function with the one below
	 *
	 * @param Hook $parameters metadatas (context, etc...)
	 * @param $object current object
	 * @return int
	 */
	public function printFieldListSearchParam($parameters, $object)
	{
		global $conf, $db, $user, $langs;

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'] , array('ticketlist', 'thirdpartyticket', 'projectticket'))) {        // do something only for the context 'somecontext1' or 'somecontext2'
			$searchCategoryTicketList = GETPOST('search_category_ticket_list');
			if (is_array($searchCategoryTicketList) && !empty($searchCategoryTicketList)) {
				foreach ($searchCategoryTicketList as $searchCategoryTicket) {
					$param .= "&search_category_ticket_list[]=" . urlencode($searchCategoryTicket);
				}
			} elseif (!empty($searchCategoryTicketList)) {
				$param = "&search_category_ticket_list=" . urlencode($searchCategoryTicketList);
			}
		}

		if (true) {
			$this->resprints = $param;
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 *  Overloading the commonGenerateDocument function : replacing the parent's function with the one below
	 *
	 * @param  Hook   $parameters Metadatas (context, etc...)
	 * @param  object $object     Current object
	 * @param  string $action
	 * @return int               0 < on error, 0 on success, 1 to replace standard code
	 */
	public function commonGenerateDocument($parameters, $object, $action) {
		global $db, $user;

		if ($parameters['currentcontext'] == 'projectcard') {
			if ($parameters['modele'] == 'orque_projectdocument') {
				require_once __DIR__ . '/../class/digiriskdolibarrdocuments/projectdocument.class.php';

				$projectdocument = new ProjectDocument($db);

				$moreparams['object']     = $object;
				$moreparams['user']       = $user;
				$moreparams['objectType'] = 'project';
				if ($object->element == 'project') {
					$projectdocument->generateDocument($parameters['modele'], $parameters['outputlangs'], $parameters['hidedetails'], $parameters['hidedesc'], $parameters['hideref'], $moreparams, true);
				}
			}
		}
		return 0; // return 0 or return 1 to replace standard code
	}

    /**
     * Overloading the saturneBannerTab function : replacing the parent's function with the one below
     *
     * @param  array  $parameters Hook metadatas (context, etc...)
     * @param  object $object     Current object
     * @return int                0 < on error, 0 on success, 1 to replace standard code
     */
    public function saturneBannerTab(array $parameters, object $object): int
    {
        global $conf, $langs;

        if ($parameters['currentcontext'] == 'firepermitsignature') {
            require_once __DIR__ . '/../class/digiriskresources.class.php';

            $digiriskResources = new DigiriskResources($this->db);

            $extSociety  = $digiriskResources->fetchResourcesFromObject('ExtSociety', $object);
            $moreHtmlRef = $langs->trans('ExtSociety') . ' : ' . $extSociety->getNomUrl(1);

            $this->resprints = $moreHtmlRef;
        }
        if (in_array($parameters['currentcontext'], ['digiriskelementdocument', 'digiriskelementagenda', 'accidentdocument', 'accidentagenda', 'accidentsignature', 'digiriskstandardagenda'])) {
            list($moreHtmlRef, $moreParams) = $object->getBannerTabContent();
            switch ($parameters['currentcontext']) {
                case 'digiriskelementdocument' :
                case 'digiriskelementagenda' :
                case 'digiriskstandardagenda' :
                    $moreParams['moreHtml'] = 'none';
                    $object->fk_project     = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;
                    break;
                case 'accidentdocument' :
                case 'accidentagenda' :
                    if ($object->id > 0 && $object->external_accident != 2) {
                        unset($object->fk_soc);
                    }
                    break;
            }
            $this->results = [$moreHtmlRef, $moreParams];
        }

        return 0; // or return 1 to replace standard code
    }

	/**
	 *  Overloading the saturneAttendantsBackToCard function : replacing the parent's function with the one below.
	 *
	 * @param  array        $parameters Hook metadatas (context, etc...).
	 * @param  CommonObject $object     Current object.
	 * @return int                      0 < on error, 0 on success, 1 to replace standard code.
	 */
	public function saturneSchedules(array $parameters, CommonObject $object): int
	{
		global $moduleNameLowerCase;

		// Do something only for the current context.
		if (in_array($parameters['currentcontext'], ['preventionplanschedules', 'firepermitschedules'])) {
			if ($object->status >= $object::STATUS_LOCKED) {
				return -1;
			}
		}

		return 0; // or return 1 to replace standard code.
	}


	/**
	 *  Overloading the SaturneAdminDocumentData function : replacing the parent's function with the one below.
	 *
	 * @param  array        $parameters Hook metadatas (context, etc...).
	 * @param  CommonObject $object     Current object.
	 * @return int                      0 < on error, 0 on success, 1 to replace standard code.
	 */
	public function SaturneAdminDocumentData(array $parameters): int
	{
		global $moduleNameLowerCase;

		$types = [
			'LegalDisplay' => [
				'documentType' => 'legaldisplay',
				'picto'        => 'fontawesome_fa-file_fas_#d35968'
			],
			'InformationsSharing' => [
				'documentType' => 'informationssharing',
				'picto'        => 'fontawesome_fa-comment-dots_fas_#d35968'
			],
            'RegisterDocument' => [
                'documentType' => 'registerdocument',
                'picto'        => 'fontawesome_fa-ticket-alt_fas_#d35968'
            ],
			'ListingRisksAction' => [
				'documentType' => 'listingrisksaction',
				'picto'        => 'fontawesome_fa-images_fas_#d35968'
			],
			'ListingRisksPhoto' => [
				'documentType' => 'listingrisksphoto',
				'picto'        => 'fontawesome_fa-file_fas_#d35968'
			],
			'GroupmentDocument' => [
				'documentType' => 'groupmentdocument',
				'picto'        => 'fontawesome_fa-info-circle_fas_#d35968'
			],
			'WorkUnitDocument' => [
				'documentType' => 'workunitdocument',
				'picto'        => 'fontawesome_fa-info-circle_fas_#d35968'
			],
			'RiskAssessmentDocument' => [
				'documentType' => 'riskassessmentdocument',
				'picto'        => 'fontawesome_fa-file-alt_fas_#d35968'
			],
			'PreventionPlanDocument' => [
				'documentType' => 'preventionplandocument',
				'picto'        => 'fontawesome_fa-info_fas_#d35968'
			],
			'FirePermitDocument' => [
				'documentType' => 'firepermitdocument',
				'picto'        => 'fontawesome_fa-fire-alt_fas_#d35968'
			],
            'AccidentInvestigationDocument' => [
                'documentType' => 'accidentinvestigationdocument',
                'picto'        => 'fontawesome_fa-search_fas_#d35968'
            ],
			'Ticket' => [
				'documentType' => 'ticketdocument',
				'picto'        => 'ticket'
			],
			'Project' => [
				'documentType' => 'project',
                'className'    => 'projectdocument',
				'picto'        => 'project'
			],

		];

		// Do something only for the current context.
		if (in_array($parameters['currentcontext'], ['digiriskdolibarradmindocuments'])) {
			$this->results = $types;
		}

		return 0; // or return 1 to replace standard code.
	}

	/**
	 *  Overloading the saturneAdminAdditionalConfig function : replacing the parent's function with the one below.
	 *
	 * @param  array        $parameters Hook metadatas (context, etc...).
	 * @param  CommonObject $object     Current object.
	 * @return int                      0 < on error, 0 on success, 1 to replace standard code.
	 */
	public function saturneAdminAdditionalConfig(array $parameters): int
	{
		$additionalConfig = [
			'ShowPictoName' => 'DIGIRISKDOLIBARR_DOCUMENT_SHOW_PICTO_NAME',
			'GenerateZipArchiveWithDigiriskElementDocuments' => 'DIGIRISKDOLIBARR_GENERATE_ARCHIVE_WITH_DIGIRISKELEMENT_DOCUMENTS'
		];

		// Do something only for the current context.
		if (in_array($parameters['currentcontext'], ['digiriskdolibarradmindocuments'])) {
			$this->results = $additionalConfig;
		}

		return 0; // or return 1 to replace standard code.
	}

	/**
	 *  Overloading the saturneCustomHeaderFunction function : replacing the parent's function with the one below.
	 *
	 * @param  array        $parameters Hook metadatas (context, etc...).
	 * @param  CommonObject $object     Current object.
	 * @return int                      0 < on error, 0 on success, 1 to replace standard code.
	 */
	public function saturneCustomHeaderFunction(array $parameters, object $object): int
	{
		// Do something only for the current context.

		if (in_array($parameters['currentcontext'], ['digiriskelementdocument', 'digiriskelementagenda', 'digiriskstandardagenda'])) {
			require_once __DIR__ . '/../lib/digiriskdolibarr_function.lib.php';

			$this->resprints = 'digirisk_header';
			return 1;
		}
		return 0; // or return 1 to replace standard code.
	}

	/**
	 *  Overloading the saturneBannerTabCustomSubdir function : replacing the parent's function with the one below.
	 *
	 * @param  array        $parameters Hook metadatas (context, etc...).
	 * @param  CommonObject $object     Current object.
	 * @return int                      0 < on error, 0 on success, 1 to replace standard code.
	 */
	public function saturneBannerTabCustomSubdir(array $parameters, object $object): int
	{
        global $conf;

		// Do something only for the current context.
		if (in_array($parameters['currentcontext'], ['digiriskelementview', 'digiriskstandardview', 'digiriskstandardagenda', 'digiriskelementagenda', 'digiriskelementdocument'])) {
			require_once __DIR__ . '/../lib/digiriskdolibarr_function.lib.php';
			if ($object->element == 'digiriskelement') {
				$this->results = ['subdir' => $object->element_type . '/'. $object->ref];
			} elseif ($object->element == 'digiriskstandard') {
				$this->results = ['modulepart' => 'mycompany', 'dir' => $conf->mycompany->dir_output, 'subdir' => 'logos', 'photoLimit' => 1];
			}

			return 1;
		}
		return 0; // or return 1 to replace standard code.
	}

    /**
     * Add new actions buttons on CommonObject
     *
     * @param   CommonObject  $object  The object to process (third party and product object)
     */
    public function addMoreActionsButtons($parameters, &$object, &$action)
    {
        global $langs, $user;

        if ($parameters['currentcontext'] == 'ticketcard') {
            print dolGetButtonAction('', img_picto('NewAccident', 'fa-user-injured') . ' ' . $langs->trans('NewAccident'), 'default', dol_buildpath('/digiriskdolibarr/view/accident/accident_card.php?action=create&fk_ticket=' . $object->id, 1), '', $user->rights->digiriskdolibarr->accident->write);
        }

    }
}
