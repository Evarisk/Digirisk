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

					let currentOtherElement = $('table:eq(1) .oddeven:last-child');
					currentOtherElement.after(nbworkedhoursInput);
					currentOtherElement.after(nbemployeesInput);

					let currentElement = $('table:eq(2) .oddeven:last-child');
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

                $category = new Categorie($db);
                $categories = $category->containing($object->id, $object->element);

                $childrenMap = [];
                $idMap       = [];
                foreach ($categories as $cat) {
                    $childrenMap[$cat->fk_parent][] = $cat;
                    $idMap[$cat->id]                = $cat;
                }
                $path    = [];
                $current = $idMap[$conf->global->DIGIRISKDOLIBARR_TICKET_MAIN_CATEGORY] ?? null;
                while ($current) {
                    $path[] = $current->id;

                    if (isset($childrenMap[$current->id])) {
                        $current = $childrenMap[$current->id][0];
                    } else {
                        break;
                    }
                }
                $validateText = '';
                foreach ($path as $catId) {
                    $cat    = $idMap[$catId];
                    $config = json_decode($cat->array_options['options_ticket_category_config'], true) ?? [];
                    if (!empty($config['validate_text'])) {
                        $validateText = $config['validate_text'];
                    }
                }
                $substitutionArray = getCommonSubstitutionArray($langs, 0, null, $object);
                complete_substitutions_array($substitutionArray, $langs, $object);
                $validateText = make_substitutions($validateText, $substitutionArray);

                $signatory   = new SaturneSignature($db, 'digiriskdolibarr', $object->element);
                $signatories = $signatory->fetchSignatory('Attendant', $object->id, $object->element);

                $signature = null;
                if (is_array($signatories) && !empty($signatories)) {
                    $signature = current($signatories);
                }

                $signatureTab  = '<tr class="trextrafields_collapse_2"><td class="titlefield"><span class="fas fa-edit paddingrightonly" style=""></span>' . $langs->trans('ValidateText').'</td>';
                $signatureTab .= '<td id="ticket_extras_digiriskdolibarr_ticket_signature_'. $object->id .'" class="valuefield ticket_extras_digiriskdolibarr_ticket_signature wordbreak">';
                $signatureTab .= $validateText;
                if ($signature && !empty($signature->signature)) {
                    $signatureTab .= ' <button type="button" class="wpeo-button button-blue" onclick="window.open(\'' . dol_buildpath('/custom/saturne/public/signature/add_signature.php?track_id=' . $signature->signature_url . '&entity=' . $conf->entity . '&module_name=ticket&object_type=' . $object->element . '&document_type=TicketDocument', 3) . '\', \'_blank\')"><i class="fas fa-eye"></i></button>';
                }
                $signatureTab .= '</td>';
                $signatureTab .= '</tr>';

                $signatureTab .= '<tr class="trextrafields_collapse_2"><td class="titlefield">' . $langs->trans('RegisterSigned') .'</td>';
                $signatureTab .= '<td id="ticket_extras_digiriskdolibarr_ticket_signature_ok_'. $object->id .'" class="valuefield ticket_extras_digiriskdolibarr_ticket_signature_ok wordbreak">';
                $signatureTab .= '<input type="checkbox"' . ($signature && !empty($signature->signature) ? ' checked' : '') . ' disabled>';
                $signatureTab .= '</td>';
                $signatureTab .= '</tr>';
                ?>
                <script>
                    jQuery('table.border.tableforfield.centpercent').first().append(<?php echo json_encode($signatureTab) ; ?>)
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

                require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

                $userGroup  = new UserGroup($this->db);
                $userGroups = saturne_fetch_all_object_type('UserGroup');
                $userGroups = array_column($userGroups, 'nom', 'id');

                $out  = '<tr class="field_user_group"><td class="titlefieldmax45 wordbreak">';
                $out .= $langs->transnoentities('UserGroup');
                $out .= '</td><td class="valuefieldcreate_ticket_user_group">';
                $out .= img_picto('', $userGroup->picto, 'class="pictofixedwidth"') . Form::selectarray('user_group', $userGroups, getDolGlobalInt('DIGIRISKDOLIBARR_TICKET_DEFAULT_USER_GROUP'), -1, 0, 0, 'disabled', '', 0, 0, '', 'minwidth100imp maxwidth500 widthcentpercentminusxx');
                $out .= ' <a href="' . dol_buildpath('/custom/digiriskdolibarr/admin/ticket/ticket.php#userGroup', 1) . '" title="' . $langs->trans('ConfigureDefaultUserGroup') . '" target="_blank">';
                $out .= img_picto($langs->trans('ConfigureDefaultUserGroup'), 'setup', 'class="pictofixedwidth opacitymedium"');
                $out .= '</a>';
                $out .= '</td></tr>';

                $userGroupID = GETPOSTISSET('user_group') ? GETPOST('user_group') : getDolGlobalInt('DIGIRISKDOLIBARR_TICKET_DEFAULT_USER_GROUP');
                $userGroup->fetch($userGroupID);
                $users = $userGroup->listUsersForGroup();
                $users = array_map(fn($userTmp) => $userTmp->getFullName($langs), $users);

                $out .= '<tr class="field_fk_user_assign"><td class="titlefieldmax45 wordbreak">';
                $out .= $langs->transnoentities('AssignedTo');
                $out .= '</td><td class="valuefieldcreate_ticket_fk_user_assign">';
                $out .= img_picto('', $user->picto, 'class="pictofixedwidth"') . Form::selectarray('fk_user_assign', $users, GETPOSTINT('fk_user_assign') ?? $object->fk_user_assign, -1, 0, 0, '', '', 0, 0, '', 'minwidth100imp maxwidth500 widthcentpercentminusxx');
                $out .= '</td></tr>'; ?>

                <?php if (GETPOST('action') == 'create') : ?>
                    <script>
                        $('#fk_user_assign').closest('tr').remove();
                        $('#notify_tiers_at_create').closest('tr').after(<?php echo json_encode($out); ?>);

                    </script>
                <?php endif;
                if (GETPOST('set') == 'assign_ticket') : ?>
                    <script>
                        $('#fk_user_assign').remove();
                        $('input[value="assign_user"]').parent().prepend(<?php echo json_encode($out); ?>);
                        $('input[value="assign_user"]').closest('tr').children('td').first().html('');
                    </script>
                <?php endif;

                if (GETPOST('action') == 'create' || GETPOST('set') == 'assign_ticket') {
                    ?>
                    <script>
                        $('#user_group').on('change', function () {
                            let querySeparator = window.saturne.toolbox.getQuerySeparator(document.URL);

                            $('#fk_user_assign').empty();
                            $.ajax({
                                url: document.URL + querySeparator + 'action=get_user_group&user_group=' + $(this).val(),
                                type: 'POST',
                                success: function (data) {
                                    let usersList = JSON.parse(atob($('<div>').html(data).find('input[name="users_list"]').val()));
                                    $('#fk_user_assign').empty();
                                    $.each(usersList, function (index, userName) {
                                        let option = new Option(userName, index);
                                        $('#fk_user_assign').append(option);
                                    });
                                    $('#fk_user_assign').trigger('change');
                                }
                            });
                        });
                    </script>
                    <?php
                }

                if (GETPOST('action') == 'get_user_group') {
                    $userGroupID = GETPOST('user_group');
                    $userGroup->fetch($userGroupID);
                    $users = $userGroup->listUsersForGroup();
                    $users = array_map(fn($userTmp) => $userTmp->getFullName($langs), $users);

                    echo '<input type="hidden" name="users_list" value="' . base64_encode(json_encode($users)) . '">';
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

            if (is_integer($object->id) && !empty($object->id)) {
                $signatory = new SaturneSignature($db, 'digiriskdolibarr', $object->element);
                $signatories = $signatory->fetchSignatory('Attendant', $object->id, $object->element);
                if (is_array($signatories) && !empty($signatories)) {
                    $signatory = array_shift($signatories);
                    if (dol_strlen( $signatory->signature_url ) > 0) {
                        $picto        = img_picto('', 'digiriskdolibarr_color@digiriskdolibarr', 'class="pictoModule"');
                        $signatureUrl = dol_buildpath('custom/saturne/public/signature/add_signature.php?track_id=' . $signatory->signature_url . '&entity=' . $conf->entity . '&module_name=digiriskdolibarr&object_type=' . $object->element . '&document_type=TicketDocument', 3);

                        $out  = '<tr class="trextrafields_collapse_' . $object->id . '"><td class="titlefield">' . $picto . $langs->trans('Signature') . '</td>';
                        $out .= '<td id="ticket_extras_digiriskdolibarr_ticket_signature_'. $object->id . '" class="valuefield ticket_extras_digiriskdolibarr_ticket_signature wordbreak copy-signatureurl-container">';
                        $out .= '<a href=' . $signatureUrl . ' target="_blank"><div class="wpeo-button button-blue" style="' . ($conf->browser->layout != 'classic' ? 'font-size: 25px;': '') . '"><i class="fas fa-eye"></i></div></a>';
                        $out .= ' <i class="fas fa-clipboard copy-signatureurl" data-signature-url="' . $signatureUrl . '" style="color: #666;' .  ($conf->browser->layout != 'classic' ? 'display: none;': '') . '"></i>';
                        $out .= '<span class="copied-to-clipboard" style="display: none;">' . '  ' . $langs->trans('CopiedToClipboard') . '</span>';
                        $out .= '</td>';
                        $out .= '</tr>';

                        ?>
                        <script>
                            jQuery('.tabBar .fichehalfleft table:first').append(<?php echo json_encode($out); ?>);
                        </script>
                        <?php
                    }
                }
            }
        } else if (preg_match('/projectcard|projectcontactcard|projecttaskcard|projecttaskscard|projecttasktime|projectOverview|tasklist|category/', $parameters['context'])) {
			if ((GETPOST('action') == '' || empty(GETPOST('action')) || GETPOST('action') != 'edit')) {
				require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
                require_once __DIR__ . '/../../saturne/class/task/saturnetask.class.php';

				$task    = new SaturneTask($db);
				$project = new Project($db);

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

        return 0; // or return 1 to replace standard code
	}

    /**
     *  Overloading the addSQLWhereFilterOnSelectUsers function : replacing the parent's function with the one below
     *
     * @param Hook $parameters metadatas (context, etc...)
     * @param $object current object
     * @param $action
     * @return int              < 0 on error, 0 on success, 1 to replace standard code
     */
    public function addSQLWhereFilterOnSelectUsers($parameters, $object, $action) {
        if (strpos($parameters['context'], 'ticketcard') !== false){
            $sql         = '';
            $userGroupID = getDolGlobalInt('DIGIRISKDOLIBARR_TICKET_USER_GROUP_ID_FOR_USER_ASSIGN');
            if ($userGroupID > 0) {
                $sql = ' AND u.rowid IN (SELECT ug.fk_user FROM ' . $this->db->prefix() . 'usergroup_user as ug WHERE ug.entity IN (' . getEntity('usergroup') . ') AND ug.fk_usergroup = ' . $userGroupID . ')';
            }

            $this->resprints = $sql;
            return  1;
        }

        return 0; // or return 1 to replace standard code
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
            if ($action == 'builddoc' && GETPOST('model') == 'papripact_a3_paysage_projectdocument') {
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
     * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
     *
     * @param array       $parameters Hook metadata (context, etc...)
     * @param object|null $object     The object to process
     *
     * @return int                    0 < on error, 0 on success, 1 to replace standard code
     */
    public function addMoreActionsButtons(array $parameters, ?object $object): int
    {
        global $conf, $langs, $user;

        if (strpos($parameters['context'], 'ticketcard') !== false) {
            $urlParameters = '&fk_ticket=' . $object->id . '&label=' . $object->subject . '&description=' . $object->message;
            if (!empty($object->array_options['options_digiriskdolibarr_ticket_service'])) {
                $urlParameters .= '&fromid=' . $object->array_options['options_digiriskdolibarr_ticket_service'];
            }
            if (!empty($object->array_options['options_digiriskdolibarr_ticket_date'])) {
                $declarationDate = dol_getdate($object->array_options['options_digiriskdolibarr_ticket_date'], false, (empty($_SESSION['dol_tz_string']) ? date_default_timezone_get() : $_SESSION['dol_tz_string']));
                $urlParameters  .= '&dateoyear=' . $declarationDate['year'] . '&dateomonth=' . $declarationDate['mon'] . '&dateoday=' . $declarationDate['mday'];
                $urlParameters  .= '&dateohour=' . $declarationDate['hours'] . '&dateomin=' . $declarationDate['minutes'];
            }
            $urlParameters .= '&backtopageforcancel=' . urlencode($_SERVER['PHP_SELF'] . '?id=' . $object->id);

            $url   = dol_buildpath('digiriskdolibarr/view/accident/accident_card.php?action=create' . $urlParameters, 1);
            $label = $conf->browser->layout == 'classic' ? '<i class="fas fa-user-injured"></i>' . ' ' . $langs->trans('NewAccident') : '<i class="fas fa-user-injured fa-2x"></i>';
            print dolGetButtonAction($label, '', 'default', $url, '', $user->hasRight('digiriskdolibarr', 'accident', 'write'));
        }

        return 0; // or return 1 to replace standard code
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
     * Overloading the hookGetEntity function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function hookGetEntity(array $parameters): int
    {
        global $mc;

        if (preg_match('/digiriskstandardriskassessmentdocument|risklist|riskcard/', $parameters['context']) && in_array($parameters['element'], ['digiriskelement', 'riskassessment']) && getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_SHARED_RISKS') && !empty($mc)) {
            $this->resprints = $mc->getEntity('risk', 1);
            return 1;
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the getActionsListWhere function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function getActionsListWhere(array $parameters): int
    {
        if (strpos($parameters['context'], 'digiriskstandardauditreportdocument') !== false) {
            $this->resprints = ' AND a.code = \'AC_AUDITREPORTDOCUMENT_GENERATE\'';
        }
        if (strpos($parameters['context'], 'digiriskstandardriskassessmentdocument') !== false) {
            $this->resprints = ' AND a.code = \'AC_RISKASSESSMENTDOCUMENT_GENERATE\'';
        }

        return 0; // or return 1 to replace standard code
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
     * Overloading the formObjectOptions function : replacing the parent's function with the one below
     *
     * @param  array     $parameters Hook metadata (context, etc...)
     * @return int                   0 < on error, 0 on success, 1 to replace standard code
     * @throws Exception
     */
    public function formObjectOptions(array $parameters, $object, $action): int
    {
        global $extrafields, $langs;

        if (strpos($parameters['context'], 'projecttaskcard') !== false && $object instanceof Task) {
            $picto            = img_picto('', 'digiriskdolibarr_color@digiriskdolibarr', 'class="pictoModule"');
            $extraFieldsNames = ['fk_risk', 'fk_preventionplan', 'fk_firepermit', 'fk_accident', 'fk_accidentinvestigation'];
            foreach ($extraFieldsNames as $extraFieldsName) {
                $extrafields->attributes['projet_task']['label'][$extraFieldsName] = $picto . $langs->transnoentities($extrafields->attributes['projet_task']['label'][$extraFieldsName]);
            }
        }

        if (strpos($parameters['context'], 'ticketcard') !== false) {
            $picto = img_picto('', 'digiriskdolibarr_color@digiriskdolibarr', 'class="pictoModule"');
            foreach ($extrafields->attributes['ticket']['label'] as $key => $value) {
                if (strpos($key, 'digiriskdolibarr_ticket') === false) {
                    continue; // Goes to the next element if ‘digiriskdolibarr_ticket’ is not found
                }
                $extrafields->attributes['ticket']['label'][$key] = $picto . $langs->transnoentities($value);
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the printFieldListOption function : replacing the parent's function with the one below
     *
     * @param  array        $parameters Hook metadata (context, etc...)
     * @param  CommonObject $object     Current object
     * @return int                      0 < on error, 0 on success, 1 to replace standard code
     */
    public function printFieldListOption(array $parameters, $object): int
    {
        global $extrafields, $langs;

        if (preg_match('/tasklist|projecttaskscard/', $parameters['context'])) {
            $picto            = img_picto('', 'digiriskdolibarr_color@digiriskdolibarr', 'class="pictoModule"');
            $extraFieldsNames = ['fk_risk', 'fk_preventionplan', 'fk_firepermit', 'fk_accident', 'fk_accidentinvestigation'];
            foreach ($extraFieldsNames as $extraFieldsName) {
                $extrafields->attributes['projet_task']['label'][$extraFieldsName] = $picto . $langs->transnoentities($extrafields->attributes['projet_task']['label'][$extraFieldsName]);
            }
        }

        if (strpos($parameters['context'], 'ticketlist') !== false) {
            $picto = img_picto('', 'digiriskdolibarr_color@digiriskdolibarr', 'class="pictoModule"');
            foreach ($extrafields->attributes['ticket']['label'] as $key => $value) {
                if (strpos($key, 'digiriskdolibarr_ticket') === false) {
                    continue; // Goes to the next element if ‘digiriskdolibarr_ticket’ is not found
                }
                $extrafields->attributes['ticket']['label'][$key] = $picto . $langs->transnoentities($value);
            }
        }

        return 0; // or return 1 to replace standard code
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
     * Overloading the saturneExtendGetObjectsMetadata function : replacing the parent's function with the one below
     *
     * @param  array $parameters Hook metadata (context, etc...)
     * @return int               0 < on error, 0 on success, 1 to replace standard code
     */
    public function saturneExtendGetObjectsMetadata(array $parameters): int
    {
        $objects = [
            'digiriskstandard'      => 'sitemap',
            'digiriskelement'       => 'network-wired',
            'risk'                  => 'exclamation-triangle',
            'riskassessment'        => 'chart-line',
            'evaluator'             => 'user-check',
            'risksign'              => 'map-signs',
            'preventionplan'        => 'info',
            'firepermit'            => 'fire-alt',
            'accident'              => 'user-injured',
            'accidentinvestigation' => 'search',
        ];
        foreach ($objects as $objectName => $picto) {
            $objectsMetadata['digiriskdolibarr_' . $objectName] = [
                'mainmenu'       => 'digiriskdolibarr',
                'leftmenu'       => '',
                'langs'          => ucfirst($objectName),
                'langfile'       => 'digiriskdolibarr@digiriskdolibarr',
                'picto'          => 'fontawesome_fa-' . $picto . '_fas_#d35968',
                'color'          => '#d35968',
                'class_name'     => ucfirst($objectName),
                'post_name'      => 'fk_' . $objectName,
                'link_name'      => 'digiriskdolibarr_' . $objectName,
                'tab_type'       => $objectName,
                'table_element'  => 'digiriskdolibarr_' . $objectName,
                'name_field'     => 'ref, label',
                'label_field'    => 'label',
                'hook_name_card' => $objectName . 'list',
                'hook_name_list' => $objectName . 'card',
                'create_url'     => 'custom/digiriskdolibarr/view/' . $objectName . '/' . $objectName . '_card.php?action=create',
                'class_path'     => 'custom/digiriskdolibarr/class/' . $objectName . '.class.php',
                'lib_path'       => 'custom/digiriskdolibarr/lib/digiriskdolibarr_' . $objectName . '.lib.php'
            ];
        }

        // objects specificataions
        $objects = ['risk', 'riskassessment', 'risksign'];
        foreach ($objects as $objectName) {
            $objectsMetadata['digiriskdolibarr_' . $objectName]['create_url'] = '';
            $objectsMetadata['digiriskdolibarr_' . $objectName]['class_path'] = 'custom/digiriskdolibarr/class/riskanalysis/' . $objectName . '.class.php';
            $objectsMetadata['digiriskdolibarr_' . $objectName]['lib_path']   = 'custom/digiriskdolibarr/lib/digiriskdolibarr_digiriskelement.lib.php';
        }
        $objectsMetadata['digiriskdolibarr_digiriskelement']['create_url'] = 'custom/digiriskdolibarr/view/digiriskelement/digiriskelement_card.php?action=create&element_type=groupment&fk_parent=0';

        $objectsMetadata['digiriskdolibarr_evaluator']['create_url'] = '';
        $objectsMetadata['digiriskdolibarr_evaluator']['class_path'] = 'custom/digiriskdolibarr/class/evaluator.class.php';
        $objectsMetadata['digiriskdolibarr_evaluator']['lib_path']   = 'custom/digiriskdolibarr/lib/digiriskdolibarr_digiriskelement.lib.php';

        $this->results = $objectsMetadata;

        return 0; // or return 1 to replace standard code
    }

	public function saturneAddAttendantRow($parameters)
	{
		if ($parameters['signatoryRole'] == 'Victim' && !empty($parameters['signatories'])) {
			return 1;
		}

		return 0;
	}
}
