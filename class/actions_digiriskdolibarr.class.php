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
	 * @var string|null String displayed by executeHook() immediately after return
	 */
	public ?string $resprints;

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

        if (strpos($parameters['context'], 'category') !== false) {
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
                'risk' => [
                    'id'        => 436302004,
                    'code'      => 'risk',
                    'obj_class' => 'Risk',
                    'obj_table' => 'digiriskdolibarr_risk',
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
     * Overloading the addHtmlHeader function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function addHtmlHeader(array $parameters): int
    {
        if (strpos($parameters['context'], 'ticketcard') !== false) {
            $resourcesRequired = [
                'css' => '/custom/saturne/css/saturne.min.css',
                'js'  => '/custom/saturne/js/saturne.min.js'
            ];

            $out  = '<!-- Includes CSS added by module saturne -->';
            $out .= '<link rel="stylesheet" type="text/css" href="' . dol_buildpath($resourcesRequired['css'], 1) . '">';
            $out .= '<!-- Includes JS added by module saturne -->';
            $out .= '<script src="' . dol_buildpath($resourcesRequired['js'], 1) . '"></script>';

            $this->resprints = $out;
        }

        if (strpos($_SERVER['PHP_SELF'], 'digiriskdolibarr') !== false) {
            ?>
            <script>
                $('link[rel="manifest"]').remove();
            </script>
            <?php

            $this->resprints = '<link rel="manifest" href="' . DOL_URL_ROOT . '/custom/digiriskdolibarr/manifest.json.php' . '" />';
        }

        return 0; // or return 1 to replace standard code
    }

	/**
	 * Overloading the printCommonFooter function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printCommonFooter($parameters)
	{
		global $conf, $db, $form, $langs, $object, $user;

		require_once __DIR__ . '/../../saturne/lib/saturne_functions.lib.php';

		if (strpos($parameters['context'], 'admincompany') !== false) {	    // do something only for the context 'somecontext1' or 'somecontext2'
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
		} else if (preg_match('/\bticketcard\b/', $parameters['context'])) {
            if (GETPOST('action') != 'create') {
                if (is_numeric($object->array_options['options_digiriskdolibarr_ticket_service']) && $object->array_options['options_digiriskdolibarr_ticket_service'] > 0) {
                    require_once __DIR__ . '/digiriskelement.class.php';

                    $digiriskElement = new DigiriskElement($db);

                    $digiriskElement->fetch($object->array_options['options_digiriskdolibarr_ticket_service']);
                    $selectDictionnary = $digiriskElement->getNomUrl(1, 'blank', 0, '', -1, 1);

                    if (!(GETPOST('action') == 'edit_extras' && GETPOST('attribute') == 'digiriskdolibarr_ticket_service')) {
                        ?>
                        <script>
                            jQuery('.ticket_extras_digiriskdolibarr_ticket_service').html(<?php echo json_encode($selectDictionnary); ?>);
                        </script>
                        <?php
                    }
                }

                $moduleNameLowerCase = 'digiriskdolibarr';

                require_once __DIR__ . '/../../saturne/lib/documents.lib.php';

                $upload_dir = $conf->digiriskdolibarr->multidir_output[$object->entity ?? 1];
                $objRef     = dol_sanitizeFileName($object->ref);
                $dirFiles   = $object->element . 'document/' . $objRef;
                $fileDir    = $upload_dir . '/' . $dirFiles;
                $urlSource  = $_SERVER['PHP_SELF'] . '?id=' . $object->id;

                $out = saturne_show_documents('digiriskdolibarr:TicketDocument', $dirFiles, $fileDir, $urlSource, $user->rights->ticket->write, $user->rights->ticket->delete, getDolGlobalString('DIGIRISKDOLIBARR_TICKETDOCUMENT_DEFAULT_MODEL'), 1, 0, 0, 0, '', '', '', '', '', $object); ?>

                <script>
                    jQuery('.fichehalfleft .div-table-responsive-no-min').first().append(<?php echo json_encode($out); ?>);
                </script>
                <?php

                require_once __DIR__ . '/accident.class.php';
                $accident           = new Accident($db);
                $linkedAccidents    = $accident->fetchAll('', '', 0, 0, ['customsql' => 't.fk_ticket = ' . $object->id]);
                $linkedAccidentList = '';
                if (is_array($linkedAccidents) && !empty($linkedAccidents)) {
                    foreach ($linkedAccidents as $linkedAccident) {
                        $linkedAccidentList .= $linkedAccident->getNomUrl(1) . '<br>';
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
				$selectDigiriskElement = $digiriskelement->selectDigiriskElementList($object->array_options['options_digiriskdolibarr_ticket_service'], 'options_digiriskdolibarr_ticket_service', [], 1, 0, array(), 0, 0, 'minwidth100 maxwidth300', 0, false, 1);
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
        } else if (preg_match('/projectcard|projectcontactcard|projecttaskcard|projecttaskscard|projecttasktime|projectOverview|tasklist|category/', $parameters['context'])) {
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

				if (strpos($parameters['context'], 'projecttaskcard') !== false) {
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

				if ((strpos($parameters['context'], 'projecttaskscard') !== false) || (strpos($parameters['context'], 'tasklist') !== false)) {
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
							if (strpos($parameters['context'], 'projecttaskcard') !== false) {
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
							if (strpos($parameters['context'], 'tasklist') !== false) {
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

				if (preg_match('/projectcard|projectcontactcard|projecttaskcard|projecttaskscard|projecttasktime|projectOverview/', $parameters['context']) || (strpos($parameters['context'], 'category') !== false && preg_match('/contacttpl/', $parameters['context']))) {
                    if (strpos($parameters['context'], 'projecttaskcard') !== false && !GETPOSTISSET('withproject')) {
                        return 0;
                    } else {
                        if (preg_match('/projectcard|projectcontactcard|projecttaskscard/', $parameters['context'])) {
                            $projectId = GETPOST('id');
                        } else if (GETPOSTISSET('projectid') || GETPOSTISSET('ref')) {
                            $project->fetch( GETPOST('projectid'), GETPOST('ref'));
                            $projectId = $project->id;
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
		} elseif (strpos($parameters['context'], 'categoryindex') !== false) {	    // do something only for the context 'somecontext1' or 'somecontext2'
            print '<script src="../custom/digiriskdolibarr/js/digiriskdolibarr.js"></script>';
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
		global $conf, $langs, $db, $user;

        $error = 0;
		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (strpos($parameters['context'], 'admincompany') !== false) {	    // do something only for the context 'somecontext1' or 'somecontext2'
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
		} else if (strpos($parameters['context'], 'ticketcard') !== false) {
            if ($action == 'builddoc' && preg_match('/\/(ticketdocument)\/|\/(digiriskdolibarr)\//', GETPOST('model'))) {
                require_once __DIR__ . '/digiriskdolibarrdocuments/ticketdocument.class.php';

                $document = new TicketDocument($this->db);

                $moduleNameLowerCase = 'digiriskdolibarr';
                $permissiontoadd     = $user->rights->ticket->write;
            }

            if ($action == 'remove_file' && preg_match('/\/(ticketdocument)\/|\/(digiriskdolibarr)\//', GETPOST('file'))) {
                $upload_dir         = $conf->digiriskdolibarr->multidir_output[$conf->entity ?? 1];
                $permissiontodelete = $user->rights->ticket->delete;
            }

            if ($action == 'pdfGeneration') {
                $moduleName          = 'DigiriskDolibarr';
                $moduleNameLowerCase = strtolower($moduleName);
                $upload_dir          = $conf->digiriskdolibarr->multidir_output[$conf->entity ?? 1];

                // Action to generate pdf from odt file
                require __DIR__ . '/../../saturne/core/tpl/documents/saturne_manual_pdf_generation_action.tpl.php';

                $urlToRedirect = $_SERVER['REQUEST_URI'];
                $urlToRedirect = preg_replace('/#pdfGeneration$/', '', $urlToRedirect);
                $urlToRedirect = preg_replace('/action=pdfGeneration&?/', '', $urlToRedirect); // To avoid infinite loop

                header('Location: ' . $urlToRedirect);
                exit;
            }

            require __DIR__ . '/../../saturne/core/tpl/documents/documents_action.tpl.php';
        } else if (strpos($parameters['context'], 'projectcard') !== false) {
            if ($action == 'builddoc' && GETPOST('model') == 'orque_projectdocument') {
                require_once __DIR__ . '/digiriskdolibarrdocuments/projectdocument.class.php';

                $document = new ProjectDocument($this->db);

                $moduleNameLowerCase      = 'digiriskdolibarr';
                $permissiontoadd          = $user->rights->projet->creer;
                $moreParams['modulePart'] = 'project';

                require __DIR__ . '/../../saturne/core/tpl/documents/documents_action.tpl.php';
            }
        } elseif (preg_match('/ticketlist|thirdpartyticket|projectticket/', $parameters['context'])) {
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
		} elseif (strpos($parameters['context'], 'categorycard') !== false) {
            require_once __DIR__ . '/../class/preventionplan.class.php';
            require_once __DIR__ . '/../class/firepermit.class.php';
            require_once __DIR__ . '/../class/accident.class.php';
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
		if (isModEnabled('digiriskdolibarr') && strpos($parameters['context'], 'emailtemplates') !== false) {	    // do something only for the context 'somecontext1' or 'somecontext2'
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
		if (strpos($parameters['context'], 'mainloginpage') !== false) {	    // do something only for the context 'somecontext1' or 'somecontext2'
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

        $sql = '';
		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (preg_match('/ticketlist|thirdpartyticket|projectticket/', $parameters['context'])) {	    // do something only for the context 'somecontext1' or 'somecontext2'
			$searchCategoryTicketList = GETPOST('search_category_ticket_list');
			if (!empty($searchCategoryTicketList)) {
				$sql = ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_ticket as ct ON t.rowid = ct.fk_ticket"; // We'll need this table joined to the select in order to filter by categ
			}
		}

        $this->resprints = $sql;
        return 0; // or return 1 to replace standard code
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
		if (preg_match('/ticketlist|thirdpartyticket|projectticket/', $parameters['context'])) {
			$searchCategoryTicketSqlList = array();
			$searchCategoryTicketList = GETPOST('search_category_ticket_list');
            $sql                      = '';
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

            $this->resprints = $sql;
		}

        return 0; // or return 1 to replace standard code
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
		if (preg_match('/ticketlist|thirdpartyticket|projectticket/', $parameters['context'])) {        // do something only for the context 'somecontext1' or 'somecontext2'
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

        $this->resprints = $moreforfilter ?? '';
        return 0; // or return 1 to replace standard code
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

        $param = '';
		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (preg_match('/ticketlist|thirdpartyticket|projectticket/', $parameters['context'])) {        // do something only for the context 'somecontext1' or 'somecontext2'
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
     * Overloading the saturneBannerTab function : replacing the parent's function with the one below
     *
     * @param  array  $parameters Hook metadatas (context, etc...)
     * @param  object $object     Current object
     * @return int                0 < on error, 0 on success, 1 to replace standard code
     */
    public function saturneBannerTab(array $parameters, object $object): int
    {
        global $conf, $langs;

        if (strpos($parameters['context'], 'firepermitsignature') !== false) {
            require_once __DIR__ . '/../class/digiriskresources.class.php';

            $digiriskResources = new DigiriskResources($this->db);

            $extSociety  = $digiriskResources->fetchResourcesFromObject('ExtSociety', $object);
            $moreHtmlRef = $langs->trans('ExtSociety') . ' : ' . $extSociety->getNomUrl(1);

            $this->resprints = $moreHtmlRef;
        }

        if (preg_match('/digiriskelementdocument|digiriskelementagenda|accidentdocument|accidentagenda|accidentsignature|digiriskstandardagenda/', $parameters['context'])) {
            $contexts = [
                'digiriskelementdocument',
                'digiriskelementagenda',
                'accidentdocument',
                'accidentagenda',
                'accidentsignature',
                'digiriskstandardagenda'
            ];
            $currentContext = '';
            $contextParts   = explode(':', $parameters['context']);
            foreach ($contextParts as $context) {
                if (in_array($context, $contexts)) {
                    $currentContext = $context;
                }
            }
            list($moreHtmlRef, $moreParams) = $object->getBannerTabContent();
            switch ($currentContext) {
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
		if (preg_match('/preventionplanschedules|firepermitschedules/', $parameters['context'])) {
			if ($object->status >= $object::STATUS_LOCKED) {
				return -1;
			}
		}

		return 0; // or return 1 to replace standard code.
	}


	/**
	 *  Overloading the saturneAdminDocumentData function : replacing the parent's function with the one below.
	 *
	 * @param  array        $parameters Hook metadatas (context, etc...).
	 * @param  CommonObject $object     Current object.
	 * @return int                      0 < on error, 0 on success, 1 to replace standard code.
	 */
	public function saturneAdminDocumentData(array $parameters): int
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
            'ListingRisksDocument' => [
                'documentType' => 'listingrisksdocument',
                'picto'        => 'fontawesome_fa-file_fas_#d35968'
            ],
			'ListingRisksAction' => [
				'documentType' => 'listingrisksaction',
				'picto'        => 'fontawesome_fa-exclamation_fas_#d35968'
			],
            'ListingRisksEnvironmentalAction' => [
                'documentType' => 'listingrisksenvironmentalaction',
                'picto'        => 'fontawesome_fa-exclamation_fas_#d35968'
            ],
			'ListingRisksPhoto' => [
				'documentType' => 'listingrisksphoto',
				'picto'        => 'fontawesome_fa-images_fas_#d35968'
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
            'AuditReportDocument' => [
                'documentType' => 'auditreportdocument',
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
		if (strpos($parameters['context'], 'digiriskdolibarradmindocuments') !== false) {
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
            'GenerateZipArchiveWithDigiriskElementDocuments' => 'DIGIRISKDOLIBARR_GENERATE_ARCHIVE_WITH_DIGIRISKELEMENT_DOCUMENTS',
			'RiskAssessmentColor' => 'DIGIRISKDOLIBARR_PROJECTDOCUMENT_DISPLAY_RISKASSESSMENT_COLOR'
		];

		// Do something only for the current context.
        if (strpos($parameters['context'], 'digiriskdolibarradmindocuments') !== false) {
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

		if (preg_match('/digiriskelementdocument|digiriskelementagenda|digiriskstandardagenda/', $parameters['context'])) {
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
		if (preg_match('/digiriskelementview|digiriskstandardview|digiriskstandardagenda|digiriskelementagenda|digiriskelementdocument/', $parameters['context'])) {
			require_once __DIR__ . '/../lib/digiriskdolibarr_function.lib.php';
			if ($object->element == 'digiriskelement') {
				$this->results = ['subdir' => $object->element_type . '/'. $object->ref, 'photoLimit' => 1];
			} elseif ($object->element == 'digiriskstandard') {
				$this->results = ['modulepart' => 'mycompany', 'dir' => $conf->mycompany->dir_output, 'subdir' => 'logos', 'photoLimit' => 1];
			}

			return 1;
		}
		return 0; // or return 1 to replace standard code.
	}

    /**
     * Overloading the extendSheetLinkableObjectsList function : replacing the parent's function with the one below
     *
     * @param  array $linkableObjectTypes  Array of linkable objects
     * @return int                         0 < on error, 0 on success, 1 to replace standard code
     */
    public function extendSheetLinkableObjectsList(array $linkableObjectTypes): int
    {
        require_once __DIR__ . '/firepermit.class.php';
        require_once __DIR__ . '/../lib/digiriskdolibarr_firepermit.lib.php';

        $linkableObjectTypes['digiriskdolibarr_firepermit'] = [
            'langs'          => 'Firepermit',
            'langfile'       => 'digiriskdolibarr@digiriskdolibarr',
            'picto'          => 'fontawesome_fa-fire-alt_fas_#d35968',
            'className'      => 'FirePermit',
            'name_field'     => 'ref',
            'post_name'      => 'fk_firepermit',
            'link_name'      => 'digiriskdolibarr_firepermit',
            'tab_type'       => 'firepermit',
            'hook_name_list' => 'firepermitlist',
            'hook_name_card' => 'firepermitcard',
            'create_url'     => 'custom/digiriskdolibarr/view/firepermit/firepermit_card.php?action=create',
            'class_path'     => 'custom/digiriskdolibarr/class/firepermit.class.php'
        ];
        $this->results = $linkableObjectTypes;

        return 1;
    }

    /**
     * Add new actions buttons on CommonObject
     *
     * @param   CommonObject  $object  The object to process (third party and product object)
     */
    public function addMoreActionsButtons($parameters, &$object, &$action)
    {
        global $langs, $user;

        if (strpos($parameters['context'], 'ticketcard') !== false) {
            print dolGetButtonAction('', img_picto('NewAccident', 'fa-user-injured') . ' ' . $langs->trans('NewAccident'), 'default', dol_buildpath('/digiriskdolibarr/view/accident/accident_card.php?action=create&fk_ticket=' . $object->id, 1), '', $user->rights->digiriskdolibarr->accident->write);
        }

    }
}
