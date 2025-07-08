<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * or see https://www.gnu.org/
 */


/**
 * \file    core/triggers/interface_99_modDigiriskdolibarr_DigiriskdolibarrTriggers.class.php
 * \ingroup digiriskdolibarr
 * \brief   Digirisk Dolibarr trigger.
 */

require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';

/**
 *  Class of triggers for Digiriskdolibarr module
 */
class InterfaceDigiriskdolibarrTriggers extends DolibarrTriggers
{
	/**
	 * @var DoliDB Database handler
	 */
	protected $db;

	/**
	 * @var string Trigger name.
	 */
	public $name;

	/**
	 * @var string Trigger family.
	 */
	public $family;

	/**
	 * @var string Trigger description.
	 */
	public $description;

	/**
	 * @var string Trigger version.
	 */
	public $version;

	/**
	 * @var string String with name of icon for digiriskdolibarr.
	 */
	public $picto;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name        = preg_replace('/^Interface/i', '', get_class($this));
		$this->family      = "demo";
		$this->description = "Digiriskdolibarr triggers.";
		$this->version     = '21.0.1';
		$this->picto       = 'digiriskdolibarr@digiriskdolibarr';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string       $action Event action code
	 * @param CommonObject $object Object
	 * @param User         $user   Object user
	 * @param Translate    $langs  Object langs
	 * @param Conf         $conf   Object conf
	 * @return int                 <0 if KO, 0 if no triggered ran, >0 if OK
	 * @throws Exception
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
        $action = str_replace('@DIGIRISKDOLIBARR', '', $action);
		$active = getDolGlobalInt('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_' . $action);

        // Allowed triggers are a list of trigger from other module that should activate this file
		if (!isModEnabled('digiriskdolibarr') || !$active) {
            $allowedTriggers = ['COMPANY_DELETE', 'CONTACT_DELETE', 'TICKET_CREATE', 'TICKET_PUBLIC_INTERFACE_CREATE'];
            if (!in_array($action, $allowedTriggers)) {
                return 0;  // If module is not enabled or trigger is deactivated, we do nothing
            }
		}

		// Data and type of action are stored into $object and $action
		dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . '. id=' . $object->id);

		require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
        require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
        require_once __DIR__ . '/../../class/digiriskresources.class.php';
		require_once __DIR__ . '/../../class/digiriskelement.class.php';
		require_once __DIR__ . '/../../class/digiriskstandard.class.php';

		$now               = dol_now();
		$actioncomm        = new ActionComm($this->db);
		$digiriskstandard  = new DigiriskStandard($this->db);
		$digiriskresources = new DigiriskResources($this->db);
		$digiriskelement   = new DigiriskElement($this->db);

		$actioncomm->elementtype = $object->element . '@digiriskdolibarr';
		$actioncomm->type_code   = 'AC_OTH_AUTO';
		$actioncomm->code        = 'AC_' . $action;
		$actioncomm->datep       = $now;
		$actioncomm->fk_element  = $object->id;
		$actioncomm->userownerid = $user->id;
		$actioncomm->percentage  = -1;

        // Trigger descriptions are handled by class function getTriggerDescription
        if (method_exists($object, 'getTriggerDescription')) {
            if (strstr($action, '_CREATE')) {
                $object->fetch($object->id);
            }
		    if (getDolGlobalInt('DIGIRISKDOLIBARR_ADVANCED_TRIGGER') && !empty($object->fields)) {
                $actioncomm->note_private = $object->getTriggerDescription($object);
            }
		}

        switch ($action) {
			case 'COMPANY_DELETE' :
				require_once __DIR__ . '/../../class/preventionplan.class.php';
				require_once __DIR__ . '/../../class/firepermit.class.php';

				$preventionplan       = new PreventionPlan($this->db);
				$firepermit           = new FirePermit($this->db);
				$alldigiriskresources = $digiriskresources->fetchAll('', '', 0, 0, array('customsql' => 't.element_id = ' . $object->id . ' AND t.element_type = "societe"'));

				if (is_array($alldigiriskresources) && !empty($alldigiriskresources)) {
					$i = 0;
					foreach ($alldigiriskresources as $digiriskresourcesingle) {
						if ($digiriskresourcesingle->object_type == 'preventionplan') {
							$preventionplan->fetch($digiriskresourcesingle->object_id);
							if ($preventionplan->status > 0) {
								$error[++$i] = $langs->trans('ErrorThirdPartyHasAtLeastOneChildOfTypePreventionPlan') . ' ' . $preventionplan->getNomUrl();
							}
						} else if ($digiriskresourcesingle->object_type == 'firepermit') {
							$firepermit->fetch($digiriskresourcesingle->object_id);
							if ($firepermit->status > 0) {
								$error[++$i] = $langs->trans('ErrorThirdPartyHasAtLeastOneChildOfTypeFirePermit') . ' ' . $firepermit->getNomUrl();
							}
						}
					}
					if (!empty($error)) {
						$error[++$i] = $langs->trans('ErrorRecordHasChildren');
						$object->errors = $error;
						return -1;
					}
				}
				break;

			case 'CONTACT_DELETE' :
				require_once __DIR__ . '/../../class/preventionplan.class.php';
				require_once __DIR__ . '/../../class/firepermit.class.php';
				require_once __DIR__ . '/../../class/digiriskresources.class.php';

				$preventionplan       = new PreventionPlan($this->db);
				$firepermit           = new FirePermit($this->db);
				$alldigiriskresources = $digiriskresources->fetchAll('', '', 0, 0, array('customsql' => 't.element_id = ' . $object->fk_soc . ' AND t.element_type = "societe"'));

				if (is_array($alldigiriskresources) && !empty($alldigiriskresources)) {
					foreach ($alldigiriskresources as $digiriskresourcesingle) {
						$i = 0;
						if ($digiriskresourcesingle->object_type == 'preventionplan') {
							$preventionplan->fetch($digiriskresourcesingle->object_id);
							if ($preventionplan->status > 0) {
								$error[++$i] = $langs->trans('ErrorContactHasAtLeastOneChildOfTypePreventionPlan') . ' ' . $preventionplan->getNomUrl();
							}
						} else if ($digiriskresourcesingle->object_type == 'firepermit') {
							$firepermit->fetch($digiriskresourcesingle->object_id);
							if ($firepermit->status > 0) {
								$error[++$i] = $langs->trans('ErrorContactHasAtLeastOneChildOfTypeFirePermit') . ' ' . $firepermit->getNomUrl();
							}
						}
					}
					if (!empty($error)) {
						$error[++$i] = $langs->trans('ErrorRecordHasChildren');
						$object->errors = $error;
						return -1;
					}
				}
				break;

            case 'AUDITREPORTDOCUMENT_GENERATE' :
            case 'ACCIDENTINVESTIGATIONDOCUMENT_GENERATE' :
            case 'FIREPERMITDOCUMENT_GENERATE' :
            case 'GROUPMENTDOCUMENT_GENERATE' :
            case 'INFORMATIONSSHARING_GENERATE' :
            case 'LEGALDISPLAY_GENERATE' :
            case 'LISTINGRISKSACTION_GENERATE' :
            case 'LISTINGRISKSDOCUMENT_GENERATE' :
            case 'LISTINGRISKSPHOTO_GENERATE' :
            case 'LISTINGRISKSENVIRONMENTALACTION_GENERATE' :
            case 'PREVENTIONPLANDOCUMENT_GENERATE' :
            case 'REGISTERDOCUMENT_GENERATE':
            case 'RISKASSESSMENTDOCUMENT_GENERATE' :
            case 'TICKETDOCUMENT_GENERATE' :
            case 'WORKUNITDOCUMENT_GENERATE' :

                if ($object->parent_type == 'groupment' || $object->parent_type == 'workunit' || preg_match('/listingrisks/', $object->parent_type)) {
                    $object->parent_type = 'digiriskelement';
                }

                $actioncomm->elementtype = $action != 'TICKETDOCUMENT_GENERATE' ? $object->parent_type . '@digiriskdolibarr' : $object->parent_type;
                $actioncomm->label       = $langs->trans('ObjectGenerateTrigger', $langs->transnoentities(ucfirst(get_class($object))), $object->ref);
                $actioncomm->elementid   = $object->parent_id;
                $actioncomm->fk_element  = $object->parent_id;

                $result = $actioncomm->create($user);
				break;

			case 'DIGIRISKELEMENT_CREATE' :
				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
                $actioncomm->elementid   = $object->id;

                $actioncomm->label = $langs->transnoentities('ObjectCreateTrigger', $langs->transnoentities(ucfirst($object->element_type)), $object->ref);

                $result = $actioncomm->create($user);
				break;

            case 'DIGIRISKELEMENT_MODIFY' :
                $actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
                $actioncomm->elementid   = $object->id;

                $actioncomm->label = $langs->transnoentities('ObjectModifyTrigger', $langs->transnoentities(ucfirst($object->element_type)), $object->ref);

                $result = $actioncomm->create($user);
                break;

            case 'DIGIRISKELEMENT_DELETE' :
                $actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
                $actioncomm->elementid   = $object->id;

                $actioncomm->label = $langs->transnoentities('ObjectDeleteTrigger', $langs->transnoentities(ucfirst($object->element_type)), $object->ref);

                $result = $actioncomm->create($user);
                break;

            case 'ACCIDENT_CREATE' :
            case 'ACCIDENTINVESTIGATION_CREATE' :
            case 'FIREPERMIT_CREATE' :
            case 'PREVENTIONPLAN_CREATE' :
                $actioncomm->label = $langs->trans('ObjectCreateTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);

                $result = $actioncomm->create($user);
                break;

            case 'ACCIDENT_MODIFY' :
            case 'ACCIDENTINVESTIGATION_MODIFY' :
            case 'FIREPERMIT_MODIFY' :
			case 'PREVENTIONPLAN_MODIFY' :
				$actioncomm->label = $langs->trans('ObjectModifyTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);

				$result = $actioncomm->create($user);
				break;

            case 'ACCIDENT_DELETE' :
            case 'ACCIDENTINVESTIGATION_DELETE' :
            case 'FIREPERMIT_DELETE' :
			case 'PREVENTIONPLAN_DELETE' :
				$actioncomm->label = $langs->trans('ObjectDeleteTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);

				$result = $actioncomm->create($user);
				break;

            case 'ACCIDENT_VALIDATE':
            case 'ACCIDENTINVESTIGATION_VALIDATE' :
            case 'FIREPERMIT_PENDINGSIGNATURE' :
			case 'PREVENTIONPLAN_PENDINGSIGNATURE' :
				$actioncomm->label = $langs->transnoentities('ObjectValidateTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);

				$result = $actioncomm->create($user);
				break;

            case 'ACCIDENT_LOCK':
            case 'ACCIDENTINVESTIGATION_LOCK' :
            case 'FIREPERMIT_LOCK' :
			case 'PREVENTIONPLAN_LOCK' :
				$actioncomm->label = $langs->trans('ObjectLockedTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);

				$result = $actioncomm->create($user);
				break;

            case 'ACCIDENT_ARCHIVE':
            case 'ACCIDENTINVESTIGATION_ARCHIVE' :
            case 'FIREPERMIT_ARCHIVE' :
			case 'PREVENTIONPLAN_ARCHIVE' :
				$actioncomm->label = $langs->trans('ObjectArchivedTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);

				$result = $actioncomm->create($user);
				break;

            case 'ACCIDENT_UNVALIDATE':
            case 'ACCIDENTINVESTIGATION_UNVALIDATE' :
            case 'FIREPERMIT_UNVALIDATE' :
            case 'PREVENTIONPLAN_UNVALIDATE' :
                $actioncomm->label = $langs->trans('ObjectUnValidateTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);

                $result = $actioncomm->create($user);
                break;

			case 'PREVENTIONPLANLINE_CREATE' :
				$actioncomm->elementtype = 'preventionplan@digiriskdolibarr';

				$actioncomm->label      = $langs->trans('ObjectCreateTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
				$actioncomm->fk_element = $object->fk_preventionplan;

				$result = $actioncomm->create($user);
				break;

			case 'PREVENTIONPLANLINE_MODIFY' :
				$actioncomm->elementtype = 'preventionplan@digiriskdolibarr';

				$actioncomm->label      = $langs->trans('ObjectModifyTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
				$actioncomm->fk_element = $object->fk_preventionplan;

				$result = $actioncomm->create($user);
				break;

			case 'PREVENTIONPLANLINE_DELETE' :
				$actioncomm->elementtype = 'preventionplan@digiriskdolibarr';

				$actioncomm->label      = $langs->trans('ObjectDeleteTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
				$actioncomm->fk_element = $object->fk_preventionplan;

				$result = $actioncomm->create($user);
				break;

            case 'FIREPERMIT_SENTBYMAIL' :
			case 'PREVENTIONPLAN_SENTBYMAIL' :
				$actioncomm->label = $langs->transnoentities('ObjectSentByMailTrigger');

				$result = $actioncomm->create($user);
				$object->last_email_sent_date = $now;
				$object->update($user, true);
				break;

			case 'FIREPERMITLINE_CREATE' :
				$actioncomm->elementtype = 'firepermit@digiriskdolibarr';

				$actioncomm->label      = $langs->trans('ObjectCreateTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
				$actioncomm->fk_element = $object->fk_firepermit;

				$result = $actioncomm->create($user);
				break;

			case 'FIREPERMITLINE_MODIFY' :
				$actioncomm->elementtype = 'firepermit@digiriskdolibarr';

				$actioncomm->label      = $langs->trans('ObjectModifyTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
				$actioncomm->fk_element = $object->fk_firepermit;

				$result = $actioncomm->create($user);
				break;

			case 'FIREPERMITLINE_DELETE' :
				$actioncomm->elementtype = 'firepermit@digiriskdolibarr';

				$actioncomm->label      = $langs->trans('ObjectDeleteTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
				$actioncomm->fk_element = $object->fk_firepermit;

				$result = $actioncomm->create($user);
				break;

			case 'TICKET_CREATE' :
				if (getDolGlobalInt('DIGIRISKDOLIBARR_SEND_EMAIL_ON_TICKET_SUBMIT')) {
					// envoi du mail avec les infos de l'objet aux adresses mail configurées
					// envoi du mail avec une trad puis avec un model

					$error = 0;
					$formmail        = new FormMail($this->db);


					$arraydefaultmessage = $formmail->getEMailTemplate($this->db, 'ticket_send', $user, $langs); // If $model_id is empty, preselect the first one

					$table_element = $object->table_element;
					$object->table_element = '';
					$substitutionarray = getCommonSubstitutionArray($langs, 0, null,$object);
					$object->table_element = $table_element;

					$message = $langs->trans('Hello') . ',' . '<br><br>';
					$message .= '<span style="color:#c55a11">' . $langs->trans('ANewTicketHasBeenSubmitted', $conf->global->MAIN_INFO_SOCIETE_NOM) . '.' . '</span><br><br>';
					$message .= '<strong>' . $langs->trans('Service') . ' : ' . '</strong>';
					$digiriskelement->fetch($object->array_options['options_digiriskdolibarr_ticket_service']);
					$message .= $digiriskelement->ref . ' - ' . $digiriskelement->label . '<br><br>';
					$message .= '<strong>' . $langs->trans('Author') . ' : ' . '</strong>';
					$message .= strtoupper($object->array_options['options_digiriskdolibarr_ticket_lastname']) . ' ' . $object->array_options['options_digiriskdolibarr_ticket_firstname'] . '<br><br>';
					$message .= '<strong>' . $langs->trans('The') . ' : ' . '</strong>';
					$message .= dol_print_date($object->array_options['options_digiriskdolibarr_ticket_date'], 'daytext') . '<br><br>';
					$message .= '<strong>' . $langs->trans('TicketMessage') . ' : ' . '</strong>' . '<br>';
					$message .= $object->message . '<br><br>';
					$message .= $langs->trans('WithKindRegards') . ',' . '<br><br>';
					$message .= '<strong style="color: #c0392b;">' . $langs->trans('SeeTicketUrl') . ' : ' . '</strong><a href="' . DOL_MAIN_URL_ROOT . '/ticket/card.php?id=' . $object->id . '">' . DOL_MAIN_URL_ROOT . '/ticket/card.php?id=' . $object->id . '</a><br><br>';
					$message .= '<span style="color: #afabab; font-size: 12px;">' . $langs->trans('AutoNotificationTicket') . '<br><span style="color: #1f497d;">' . '- - DOLIBARR - -' . '</span><br>' . $langs->trans('TicketPublicInterfaceOtherName') . '</span><br><br>';

					complete_substitutions_array($substitutionarray, $langs, $object);

					$subject = make_substitutions($arraydefaultmessage->topic,$substitutionarray);
					$message .= make_substitutions($arraydefaultmessage->content,$substitutionarray);

					if ( ! $error) {
						$langs->load('mails');

						$listOfMails = $conf->global->DIGIRISKDOLIBARR_TICKET_SUBMITTED_SEND_MAIL_TO;

						if ( ! preg_match('/;/', $listOfMails)) {
							$sendto = $listOfMails;

							if (dol_strlen($sendto) && ( ! empty($conf->global->MAIN_MAIL_EMAIL_FROM))) {
								require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';

								$from = $conf->global->MAIN_MAIL_EMAIL_FROM;
								$trackid = 'tic' . $object->id;

								// Create form object
								// Send mail (substitutionarray must be done just before this)
								$mailfile = new CMailFile($subject, $sendto, $from, $message, array(), array(), array(), "", "", 0, -1, '', '', $trackid, '', 'ticket');

								if ($mailfile->error) {
									setEventMessages($mailfile->error, $mailfile->errors, 'errors');
								} elseif ( ! empty($conf->global->MAIN_MAIL_SMTPS_ID)) {
									$result = $mailfile->sendfile();
									if ( ! $result) {
										$langs->load("other");
										$mesg = '<div class="error">';
										if ($mailfile->error) {
											$mesg .= $langs->transnoentities('ErrorFailedToSendMail', dol_escape_htmltag($from), dol_escape_htmltag($sendto));
											$mesg .= '<br>' . $mailfile->error;
										} else {
											$mesg .= $langs->transnoentities('ErrorFailedToSendMail', dol_escape_htmltag($from), dol_escape_htmltag($sendto));
										}
										$mesg .= '</div>';
										setEventMessages($mesg, null, 'warnings');
									} else {
										$actioncomm->elementtype  = 'ticket';
										$actioncomm->label        = $langs->transnoentities('TicketCreationMailWellSent');
										$actioncomm->note_private = $langs->transnoentities('TicketCreationMailSent', $sendto);

										$result = $actioncomm->create($user);
										break;
									}
								}
							} else {
								$langs->load("errors");
								setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("MailTo")), null, 'warnings');
								dol_syslog('Try to send email with no recipient defined', LOG_WARNING);
							}
						} else {
							$listOfMails = preg_split('/;/', $listOfMails);
							if ( ! empty($listOfMails) && $listOfMails > 0) {
								if (end($listOfMails) == ';') {
									array_pop($listOfMails);
								}
								foreach ($listOfMails as $email) {
									$sendto = $email;

									if (dol_strlen($sendto) && ( ! empty($conf->global->MAIN_MAIL_EMAIL_FROM))) {
										require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';

										$from = $conf->global->MAIN_MAIL_EMAIL_FROM;
										$trackid = 'tic' . $object->id;

										// Create form object
										// Send mail (substitutionarray must be done just before this)
										$mailfile = new CMailFile($subject, $sendto, $from, $message, array(), array(), array(), "", "", 0, -1, '', '', $trackid, '', 'ticket');

										if ($mailfile->error) {
											setEventMessages($mailfile->error, $mailfile->errors, 'errors');
										} else {
											if ( ! empty($conf->global->MAIN_MAIL_SMTPS_ID)) {
												$result = $mailfile->sendfile();
												if ( ! $result) {
													$langs->load("other");
													$mesg = '<div class="error">';
													if ($mailfile->error) {
														$mesg .= $langs->transnoentities('ErrorFailedToSendMail', dol_escape_htmltag($from), dol_escape_htmltag($sendto));
														$mesg .= '<br>' . $mailfile->error;
													} else {
														$mesg .= $langs->transnoentities('ErrorFailedToSendMail', dol_escape_htmltag($from), dol_escape_htmltag($sendto));
													}
													$mesg .= '</div>';
													setEventMessages($mesg, null, 'warnings');
												} else {
													$actioncomm->elementtype  = 'ticket';
													$actioncomm->label        = $langs->transnoentities('TicketCreationMailWellSent');
													$actioncomm->note_private = $langs->transnoentities('TicketCreationMailSent', $sendto);

													$result = $actioncomm->create($user);
													break;
												}
											}
										}
									} else {
										$langs->load("errors");
										setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("MailTo")), null, 'warnings');
										dol_syslog('Try to send email with no recipient defined', LOG_WARNING);
									}
								}
							} else {
								// Mail sent KO
								$error++;
								if ( ! empty($error)) setEventMessages(null, $langs->trans('WrongEmailFormat'), 'errors');
								else setEventMessages($error, null, 'errors');
							}
						}
					}
				}
				break;

            case 'TICKET_PUBLIC_INTERFACE_CREATE' :
                require_once __DIR__ . '/../../../saturne/class/saturnemail.class.php';

                $categories = $object->getCategoriesCommon('ticket');
                if (is_array($categories) && !empty($categories)) {
                    $category = new Categorie($this->db);
                    foreach ($categories as $categoryID) {
                        $category->fetch($categoryID);
                        $categoryConfigs = json_decode($category->array_options['options_ticket_category_config']);
                        if ($categoryConfigs->mail_template && $categoryConfigs->recipients) {
                            $saturneMail = new SaturneMail($this->db);
                            $saturneMail->fetch($categoryConfigs->mail_template);
                            $recipients = explode(',', $categoryConfigs->recipients);
                            foreach ($recipients as $recipientID) {
                                $userTmp = new User($this->db);
                                $userTmp->fetch($recipientID);
                                $sendto = $userTmp->email;
                                if (dol_strlen($sendto) && (!empty($conf->global->MAIN_MAIL_EMAIL_FROM))) {
                                    require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';

                                    $from = $conf->global->MAIN_MAIL_EMAIL_FROM;
                                    $trackid = 'tic' . $object->id;

                                    // Create form object
                                    // Send mail (substitutionarray must be done just before this)
                                    $mailfile = new CMailFile($saturneMail->topic, $sendto, $from, $saturneMail->content, array(), array(), array(), "", "", 0, -1, '', '', $trackid, '', 'ticket');
                                    if ($mailfile->error) {
                                        setEventMessages($mailfile->error, $mailfile->errors, 'errors');
                                    } else {
                                        if (1) {
                                            $result = $mailfile->sendfile();
                                            if ( ! $result) {
                                                $langs->load("other");
                                                $mesg = '<div class="error">';
                                                if ($mailfile->error) {
                                                    $mesg .= $langs->transnoentities('ErrorFailedToSendMail', dol_escape_htmltag($from), dol_escape_htmltag($sendto));
                                                    $mesg .= '<br>' . $mailfile->error;
                                                } else {
                                                    $mesg .= $langs->transnoentities('ErrorFailedToSendMail', dol_escape_htmltag($from), dol_escape_htmltag($sendto));
                                                }
                                                $mesg .= '</div>';
                                                setEventMessages($mesg, null, 'warnings');
                                            } else {
                                                $actioncomm->elementtype   = 'ticket';
                                                $actioncomm->label         = $langs->transnoentities('TicketCreationMailWellSent');
                                                $actioncomm->note_private  = $langs->transnoentities('TicketCreationMailSent', $sendto) . '<br>';
                                                $actioncomm->note_private .= $saturneMail->topic . '<br>';
                                                $actioncomm->note_private .= $saturneMail->content;
                                                $result = $actioncomm->create($user);
                                            }
                                        }
                                    }
                                } else {
                                    $langs->load("errors");
                                    setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("MailTo")), null, 'warnings');
                                    dol_syslog('Try to send email with no recipient defined', LOG_WARNING);
                                }
                            }
                        }
                    }
                }
                break;

            case 'RISKSIGN_CREATE' :
			case 'RISK_CREATE' :
				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';

				$actioncomm->label      = $langs->trans('ObjectCreateTrigger', $langs->transnoentities(get_class($object)), $object->ref);
				$actioncomm->fk_element = $object->fk_element;

				$result = $actioncomm->create($user);
				break;

            case 'RISKSIGN_MODIFY' :
			case 'RISK_MODIFY' :
				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';

				$actioncomm->label      = $langs->trans('ObjectModifyTrigger', $langs->transnoentities(get_class($object)), $object->ref);
				$actioncomm->fk_element = $object->fk_element;

				$result = $actioncomm->create($user);
				break;

            case 'RISKSIGN_DELETE' :
			case 'RISK_DELETE' :
				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';

				$actioncomm->label      = $langs->trans('ObjectDeleteTrigger', $langs->transnoentities(get_class($object)), $object->ref);
				$actioncomm->fk_element = $object->fk_element;

				$result = $actioncomm->create($user);
				break;

            case 'RISKSIGN_IMPORT':
			case 'RISK_IMPORT' :
                $actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';

                $actioncomm->label      = $langs->transnoentities('ObjectImportTrigger', $langs->transnoentities(get_class($object)), $object->ref);
				$actioncomm->fk_element = $object->applied_on;

				$result = $actioncomm->create($user);
				break;

            case 'RISKSIGN_UNLINK':
			case 'RISK_UNLINK' :
                $actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';

                $actioncomm->label      = $langs->transnoentities('ObjectUnlinkTrigger', $langs->transnoentities(get_class($object)), $object->ref);
				$actioncomm->fk_element = $object->applied_on;

				$result = $actioncomm->create($user);
				break;

			case 'TASK_CREATE' :
				if (!empty($object->array_options['options_fk_risk'])) {
                    require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';

					$langs->load("projects");

					$risk = new Risk($this->db);
					$digiriskelement->fetch($object->fk_element);
					$risk->fetch($object->array_options['options_fk_risk']);

					if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS) {
						$timeSpent = $object->getSummaryOfTimeSpent();
						$label_progress = 'ProgressDeclared';
						if ($timeSpent['total_duration'] > 0 && !empty($object->planned_workload)) {
							$task_progress = round($timeSpent['total_duration'] / $object->planned_workload * 100, 2);
						} else {
							$task_progress = 0;
						}
					} else {
						(!empty($object->progress) ? $task_progress = $object->progress : $task_progress = 0);
						$label_progress = 'ProgressCalculated';
					}

					$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';

					$actioncomm->label         = $langs->trans('ObjectCreateTrigger', $langs->transnoentities('Task'), $object->ref);
					$actioncomm->note_private .= $langs->trans($label_progress) . ' : ' . $task_progress . '%' . '<br>';
					$actioncomm->fk_element    = $risk->fk_element;

					$result = $actioncomm->create($user);
				}
				break;

			case 'TASK_MODIFY' :
				if (!empty($object->array_options['options_fk_risk'])) {
					require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
					$langs->load("projects");

					$risk = new Risk($this->db);
					$digiriskelement->fetch($object->fk_element);
					$risk->fetch($object->array_options['options_fk_risk']);

					if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS) {
						$timeSpent = $object->getSummaryOfTimeSpent();
						$label_progress = 'ProgressDeclared';
						if ($timeSpent['total_duration'] > 0 && !empty($object->planned_workload)) {
							$task_progress = round($timeSpent['total_duration'] / $object->planned_workload * 100, 2);
						} else {
							$task_progress = 0;
						}
					} else {
						(!empty($object->progress) ? $task_progress = $object->progress : $task_progress = 0);
						$label_progress = 'ProgressCalculated';
					}

					$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';

					$actioncomm->label         = $langs->trans('ObjectModifyTrigger', $langs->transnoentities('Task'), $object->ref);
					$actioncomm->note_private .= $langs->trans($label_progress) . ' : ' . $task_progress . '%' . '<br>';
					$actioncomm->fk_element    = $risk->fk_element;

					$result = $actioncomm->create($user);
				}
				break;

			case 'TASK_DELETE' :
				if ($object->array_options['options_fk_risk'] != 0) {
					$langs->load("projects");

					$risk = new Risk($this->db);
					$digiriskelement->fetch($object->fk_element);
					$risk->fetch($object->array_options['options_fk_risk']);

					if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS) {
						$timeSpent = $object->getSummaryOfTimeSpent();
						$label_progress = 'ProgressDeclared';
						if ($timeSpent['total_duration'] > 0 && !empty($object->planned_workload)) {
							$task_progress = round($timeSpent['total_duration'] / $object->planned_workload * 100, 2);
						} else {
							$task_progress = 0;
						}
					} else {
						(!empty($object->progress) ? $task_progress = $object->progress : $task_progress = 0);
						$label_progress = 'ProgressCalculated';
					}

					$actioncomm->elementtype   = 'digiriskelement@digiriskdolibarr';
					$actioncomm->label         = $langs->trans('ObjectDeleteTrigger', $langs->transnoentities('Task'), $object->ref);
					$actioncomm->note_private .= $langs->trans($label_progress) . ' : ' . $task_progress . '%' . '<br>';
					$actioncomm->fk_element    = $risk->fk_element;

					$result = $actioncomm->create($user);
				}
				break;

			case 'RISKASSESSMENT_CREATE' :
				require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
				$risk = new Risk($this->db);
				$risk->fetch($object->fk_risk);

				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';

				$actioncomm->label      = $langs->trans('ObjectCreateTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
				$actioncomm->fk_element = $risk->fk_element;

				$result = $actioncomm->create($user);
				break;

			case 'RISKASSESSMENT_MODIFY' :
				require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
				$risk = new Risk($this->db);
				$risk->fetch($object->fk_risk);

				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';

				$actioncomm->label      = $langs->trans('ObjectModifyTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
				$actioncomm->fk_element = $risk->fk_element;

				$result = $actioncomm->create($user);
				break;

			case 'RISKASSESSMENT_DELETE' :
				require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
				$risk = new Risk($this->db);
				$risk->fetch($object->fk_risk);

				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';

				$actioncomm->label      = $langs->trans('ObjectDeleteTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
				$actioncomm->fk_element = $risk->fk_element;

				$result = $actioncomm->create($user);
				break;

			case 'EVALUATOR_CREATE' :
				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';

				$actioncomm->label      = $langs->trans('ObjectCreateTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->fk_element = $object->fk_parent;

				$result = $actioncomm->create($user);
				break;

			case 'EVALUATOR_MODIFY' :
				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';

				$actioncomm->label      = $langs->trans('ObjectModifyTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
                $actioncomm->fk_element = $object->fk_parent;

				$result = $actioncomm->create($user);
				break;

			case 'EVALUATOR_DELETE' :
				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';

				$actioncomm->label      = $langs->trans('ObjectDeleteTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
				$actioncomm->fk_element = $object->fk_parent;

				$result = $actioncomm->create($user);
				break;

            case 'ACCIDENTMETADATA_CREATE' :
            case 'ACCIDENTLESION_CREATE' :
			case 'ACCIDENTWORKSTOP_CREATE' :
				$actioncomm->elementtype = 'accident@digiriskdolibarr';

				$actioncomm->label      = $langs->trans('ObjectCreateTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
				$actioncomm->fk_element = $object->fk_accident;
				$result = $actioncomm->create($user);
				break;

            case 'ACCIDENTLESION_MODIFY' :
			case 'ACCIDENTWORKSTOP_MODIFY' :
				$actioncomm->elementtype = 'accident@digiriskdolibarr';

				$actioncomm->label      = $langs->trans('ObjectModifyTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
				$actioncomm->fk_element = $object->fk_accident;

				$result = $actioncomm->create($user);
				break;

            case 'ACCIDENTLESION_DELETE' :
			case 'ACCIDENTWORKSTOP_DELETE' :
				$actioncomm->elementtype = 'accident@digiriskdolibarr';

				$actioncomm->label      = $langs->trans('ObjectDeleteTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
				$actioncomm->fk_element = $object->fk_accident;

				$result = $actioncomm->create($user);
				break;

			case 'TASK_TIMESPENT_CREATE' :
				$actioncomm->elementtype = 'task';

				$actioncomm->label         = $langs->trans('ObjectCreateTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $object->ref . ' - ' . $object->label . '<br>';
				$actioncomm->note_private .= $langs->trans('TaskTimeSpentDate') . ' : ' . dol_print_date($object->timespent_datehour, 'dayhoursec') . '<br>';
				$actioncomm->note_private .= $langs->trans('TaskTimeSpentDuration') . ' : ' .  convertSecondToTime($object->timespent_duration * 60, 'allhourmin') . '<br>';
				$actioncomm->fk_element    = $object->fk_element;
				$actioncomm->fk_project    = $object->fk_project;

				$result = $actioncomm->create($user);
				break;

			case 'TASK_TIMESPENT_MODIFY' :
				$actioncomm->elementtype = 'task';

				$actioncomm->label         = $langs->trans('ObjectModifyTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $object->ref . ' - ' . $object->label . '<br>';
				$actioncomm->note_private .= $langs->trans('TaskTimeSpentDate') . ' : ' . dol_print_date($object->timespent_datehour, 'dayhoursec') . '<br>';
				$actioncomm->note_private .= $langs->trans('TaskTimeSpentDuration') . ' : ' .  convertSecondToTime($object->timespent_duration * 60, 'allhourmin') . '<br>';
				$actioncomm->fk_element    = $object->fk_element;
				$actioncomm->fk_project    = $object->fk_project;

				$result = $actioncomm->create($user);
				break;

			case 'TASK_TIMESPENT_DELETE' :
				$actioncomm->elementtype = 'task';

				$actioncomm->label         = $langs->trans('ObjectDeleteTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $object->ref . ' - ' . $object->label . '<br>';
				$actioncomm->note_private .= $langs->trans('TaskTimeSpentDate') . ' : ' . dol_print_date($object->timespent_datehour, 'dayhoursec') . '<br>';
				$actioncomm->note_private .= $langs->trans('TaskTimeSpentDuration') . ' : ' .  convertSecondToTime($object->timespent_duration * 60, 'allhourmin') . '<br>';
				$actioncomm->fk_element    = $object->fk_element;
				$actioncomm->fk_project    = $object->fk_project;

				$result = $actioncomm->create($user);
				break;

			case 'ACCIDENTINVESTIGATION_SENTBYMAIL' :
				$actioncomm->label = $langs->trans('ObjectSentByMailTrigger');

				$result = $actioncomm->create($user);
				break;

		}

//		if ($result < 0) {
//			$object->errors = array_merge($object->error, $actioncomm->errors);
//			return $result;
//		}
		return 0;
	}
}
