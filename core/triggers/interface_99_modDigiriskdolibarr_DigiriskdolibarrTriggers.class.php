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
		$this->version     = '23.4.0';
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

        // Le document PDF d'un plan de prevention ou d'un permis de feu n'est pas un evenement
        // d'agenda : sa regeneration est traitee avant le filtre ci-dessous, sinon elle dependrait
        // du reglage des actions automatiques et la diffusion presenterait un document absent ou
        // perime selon une option qui n'a rien a voir avec lui.
        if (isModEnabled('digiriskdolibarr')) {
            $this->refreshPreventionPlanDocumentOnTrigger($action, $object, $user, $langs);
        }

        // Allowed triggers are a list of trigger from other module that should activate this file
		if (!isModEnabled('digiriskdolibarr') || !$active) {
			$allowedTriggers = ['COMPANY_DELETE', 'CONTACT_DELETE', 'TICKET_CREATE', 'TICKET_PUBLIC_INTERFACE_CREATE', 'TICKET_SIGN', 'SATURNE_SIGNATURE_SIGN', 'SATURNE_SIGNATURE_SIGN_PUBLIC', 'PRODUCT_CREATE'];
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
		    if (getDolGlobalInt('DIGIRISKDOLIBARR_ADVANCED_TRIGGER') == 1 && !empty($object->fields)) {
                $actioncomm->note_private = $object->getTriggerDescription();
            }
		}

        switch ($action) {
            case 'PRODUCT_CREATE' :
                $fieldsToDefault = [
                    'digirisk_identification' => 'DIGIRISKDOLIBARR_PRODUCT_DEFAULT_IDENTIFICATION',
                    'digirisk_security'       => 'DIGIRISKDOLIBARR_PRODUCT_DEFAULT_SECURITY',
                    'digirisk_usermanual'     => 'DIGIRISKDOLIBARR_PRODUCT_DEFAULT_USERMANUAL',
                    'digirisk_qualification'  => 'DIGIRISKDOLIBARR_PRODUCT_DEFAULT_QUALIFICATION',
                    'digirisk_hygiene'        => 'DIGIRISKDOLIBARR_PRODUCT_DEFAULT_HYGIENE',
                    'digirisk_maintenance'    => 'DIGIRISKDOLIBARR_PRODUCT_DEFAULT_MAINTENANCE'
                ];
                $hasUpdates = false;
                if (!isset($object->array_options)) {
                    $object->array_options = [];
                }
                $updates = [];
                foreach ($fieldsToDefault as $field => $const) {
                    $val = trim($object->array_options['options_' . $field] ?? '');
                    if ($val === '') {
                        $def = trim(getDolGlobalString($const));
                        if ($def !== '') {
                            $object->array_options['options_' . $field] = $def;
                            $updates[] = $field . " = '" . $this->db->escape($def) . "'";
                            $hasUpdates = true;
                        }
                    }
                }
                if ($hasUpdates) {
                    $sql = "UPDATE " . MAIN_DB_PREFIX . "product_extrafields SET ";
                    $sql .= implode(', ', $updates);
                    $sql .= " WHERE fk_object = " . ((int) $object->id);
                    $this->db->query($sql);
                }
                break;

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

                // Enquete accident : le document doit etre lisible par les personnes de la
                // diffusion, qui n'ont pas de compte. On le rattache a l'enquete elle-meme et on
                // lui pose une cle de partage, sinon la page publique ne peut ni le trouver ni le servir.
                if ($action == 'ACCIDENTINVESTIGATIONDOCUMENT_GENERATE') {
                    $this->shareGeneratedDocument($object, 'digiriskdolibarr_accident_investigation', $user);
                }

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
				// Only send this notification for tickets submitted through the DigiRisk public interface.
				// TICKET_CREATE is fired by Dolibarr core on every ticket creation (back-office, API, ...),
				// but the email below is built solely from public-interface extrafields.
				if (getDolGlobalInt('DIGIRISKDOLIBARR_SEND_EMAIL_ON_TICKET_SUBMIT') && !empty($object->context['digiriskdolibarrpublicinterface'])) {
					$this->sendTicketSubmittedMail($object, $user, $langs);
				}
				break;

			case 'TICKET_SIGN':
				$actioncomm->elementtype = 'ticket';
				$actioncomm->label = $langs->transnoentities('ObjectSignedTrigger', $langs->transnoentities(get_class($object)), $object->ref);

				$result = $actioncomm->create($user);
				break;

            case 'TICKET_PUBLIC_INTERFACE_CREATE' :
                require_once __DIR__ . '/../../../saturne/class/saturnemail.class.php';

                $categories = $object->getCategoriesCommon('ticket');
                if (is_array($categories) && !empty($categories)) {
                    $category = new Categorie($this->db);
                    foreach ($categories as $categoryID) {
                        $category->fetch($categoryID);
                        $categoryConfigs = json_decode($category->array_options['options_ticket_category_config'] ?? '');

                        // The empty entry of the mail template selector is stored as -1, a string that PHP
                        // reads as true : a category carrying recipients but no template used to fetch the
                        // template -1, leave SaturneMail::$topic uninitialized, and make the whole public
                        // declaration fatal right after the ticket had been created
                        $mailTemplateID = (int) ($categoryConfigs->mail_template ?? 0);
                        if ($mailTemplateID > 0 && !empty($categoryConfigs->recipients)) {
                            $saturneMail = new SaturneMail($this->db);
                            if ($saturneMail->fetch($mailTemplateID) <= 0) {
                                dol_syslog('TICKET_PUBLIC_INTERFACE_CREATE : mail template ' . $mailTemplateID . ' of category ' . $categoryID . ' not found, no mail sent', LOG_WARNING);
                                continue;
                            }

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
					$digiriskelement->fetch((int)$object->fk_element);
					$risk->fetch((int)$object->array_options['options_fk_risk']);

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
					$digiriskelement->fetch((int)$object->fk_element);
					$risk->fetch((int)$object->array_options['options_fk_risk']);

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
					$digiriskelement->fetch((int)$object->fk_element);
					$risk->fetch((int)$object->array_options['options_fk_risk']);

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
				$risk->fetch((int)$object->fk_risk);

				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';

				$actioncomm->label      = $langs->trans('ObjectCreateTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
				$actioncomm->fk_element = $risk->fk_element;

				$result = $actioncomm->create($user);
				break;

			case 'RISKASSESSMENT_MODIFY' :
				require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
				$risk = new Risk($this->db);
				$risk->fetch((int)$object->fk_risk);

				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';

				$actioncomm->label      = $langs->trans('ObjectModifyTrigger', $langs->transnoentities(ucfirst($object->element)), $object->ref);
				$actioncomm->fk_element = $risk->fk_element;

				$result = $actioncomm->create($user);
				break;

			case 'RISKASSESSMENT_DELETE' :
				require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
				$risk = new Risk($this->db);
				$risk->fetch((int)$object->fk_risk);

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

    /**
     * Regenere le document PDF d'un plan de prevention et le remet a disposition de la diffusion.
     *
     * Remplace la version precedente au lieu de s'empiler avec elle : la page publique affiche
     * tous les fichiers partages, deux PDF y seraient illisibles.
     *
     * @param  int       $planId Identifiant du plan de prevention
     * @param  User      $user   Utilisateur a l'origine de l'action
     * @param  Translate $langs  Objet de traduction
     * @return void
     */
    protected function refreshPreventionPlanDocument(int $planId, User $user, Translate $langs)
    {
        dol_include_once('/digiriskdolibarr/lib/digiriskdolibarr_preventionplan.lib.php');

        digiriskRefreshPreventionPlanDocument($this->db, $planId, $user, $langs);
    }

    /**
     * Regenere le document PDF d'un permis de feu et le remet a disposition de la diffusion.
     *
     * Remplace la version precedente au lieu de s'empiler avec elle : la page publique affiche
     * tous les fichiers partages, deux PDF y seraient illisibles.
     *
     * @param  int       $permitId Identifiant du permis de feu
     * @param  User      $user     Utilisateur a l'origine de l'action
     * @param  Translate $langs    Objet de traduction
     * @return void
     */
    protected function refreshFirePermitDocument(int $permitId, User $user, Translate $langs)
    {
        dol_include_once('/digiriskdolibarr/lib/digiriskdolibarr_firepermit.lib.php');

        digiriskRefreshFirePermitDocument($this->db, $permitId, $user, $langs);
    }

    /**
     * Regenere le document d'un plan de prevention ou d'un permis de feu quand l'evenement recu l'a
     * rendu obsolete.
     *
     * Le PDF suit l'objet et rien d'autre : creation, modification, changement d'etat et signatures.
     * Une signature porte l'objet dans fk_object, les autres evenements sont l'objet lui-meme.
     *
     * @param  string    $action Nom du trigger, prefixe module deja retire
     * @param  object    $object Objet a l'origine du trigger
     * @param  User      $user   Utilisateur a l'origine de l'action
     * @param  Translate $langs  Objet de traduction
     * @return void
     */
    protected function refreshPreventionPlanDocumentOnTrigger(string $action, $object, User $user, Translate $langs)
    {
        $planTriggers = [
            'PREVENTIONPLAN_CREATE', 'PREVENTIONPLAN_MODIFY', 'PREVENTIONPLAN_PENDINGSIGNATURE',
            'PREVENTIONPLAN_VALIDATE', 'PREVENTIONPLAN_UNVALIDATE', 'PREVENTIONPLAN_LOCK',
        ];

        if (in_array($action, $planTriggers) && $object->id > 0) {
            $this->refreshPreventionPlanDocument((int) $object->id, $user, $langs);

            return;
        }

        // Le permis de feu se diffuse comme le plan de prevention : son document suit les memes etapes
        $permitTriggers = [
            'FIREPERMIT_CREATE', 'FIREPERMIT_MODIFY', 'FIREPERMIT_PENDINGSIGNATURE',
            'FIREPERMIT_VALIDATE', 'FIREPERMIT_UNVALIDATE', 'FIREPERMIT_LOCK',
        ];

        if (in_array($action, $permitTriggers) && $object->id > 0) {
            $this->refreshFirePermitDocument((int) $object->id, $user, $langs);

            return;
        }

        // Une signature change le document : sans regeneration, la diffusion continue de presenter
        // une version datee a des gens qui n'ont aucun moyen de s'en apercevoir.
        $signatureTriggers = ['SATURNE_SIGNATURE_SIGN', 'SATURNE_SIGNATURE_SIGN_PUBLIC', 'SATURNE_SIGNATURE_PENDING_SIGNATURE'];
        if (in_array($action, $signatureTriggers) && isset($object->object_type) && in_array($object->object_type, ['preventionplan', 'firepermit']) && $object->fk_object > 0) {
            require_once DOL_DOCUMENT_ROOT . '/custom/saturne/class/saturnesignature.class.php';
            $signatory = new SaturneSignature($this->db);
            
            // Check if all signatures are collected
            if ($signatory->checkSignatoriesSignatures((int) $object->fk_object, $object->object_type) === 1) {
                if ($object->object_type === 'preventionplan') {
                    require_once __DIR__ . '/../../class/preventionplan.class.php';
                    $docToLock = new PreventionPlan($this->db);
                } else {
                    require_once __DIR__ . '/../../class/firepermit.class.php';
                    $docToLock = new FirePermit($this->db);
                }
                
                if ($docToLock->fetch((int) $object->fk_object) > 0 && $docToLock->status == $docToLock::STATUS_VALIDATED) {
                    // Auto-lock the document. This will fire PREVENTIONPLAN_LOCK or FIREPERMIT_LOCK.
                    $docToLock->setLocked($user, false);
                }
            }

            if ($object->object_type === 'preventionplan') {
                $this->refreshPreventionPlanDocument((int) $object->fk_object, $user, $langs);
            } else {
                $this->refreshFirePermitDocument((int) $object->fk_object, $user, $langs);
            }
        }
    }

    /**
     * Rattache le dernier document genere a son objet parent et lui pose une cle de partage.
     *
     * La generation indexe le fichier sur le document Saturne (src_object_type =
     * saturne_object_documents). La page publique de diffusion, elle, cherche les fichiers de
     * l'objet metier et ne sert que ceux qui portent une cle de partage : sans ce recalage le
     * document reste invisible pour les personnes diffusees.
     *
     * @param  SaturneDocuments $document     Document genere
     * @param  string           $tableElement Table de l'objet metier a rattacher
     * @param  User             $user         Utilisateur a l'origine de l'action
     * @return int                            < 0 si KO, 1 si OK
     */
    protected function shareGeneratedDocument($document, string $tableElement, User $user): int
    {
        if (empty($document->last_main_doc) || empty($document->parent_id)) {
            return -1;
        }

        // last_main_doc ne porte que le nom du fichier et le repertoire est celui de l'objet
        // parent, pas du document : on retrouve la ligne indexee par son nom de fichier
        return $this->shareGeneratedFile(basename($document->last_main_doc), $tableElement, (int) $document->parent_id, $user);
    }

    /**
     * Recale un fichier indexe sur l'objet metier voulu et lui pose une cle de partage.
     *
     * @param  string $fileName     Nom du fichier indexe
     * @param  string $tableElement Table de l'objet metier a rattacher
     * @param  int    $objectId     Identifiant de l'objet metier
     * @param  User   $user         Utilisateur a l'origine de l'action
     * @param  bool   $favorite     Marquer le fichier comme mis en avant sur la diffusion
     * @return int                  < 0 si KO, 1 si OK
     */
    protected function shareGeneratedFile(string $fileName, string $tableElement, int $objectId, User $user, bool $favorite = false): int
    {
        dol_include_once('/digiriskdolibarr/lib/digiriskdolibarr_preventionplan.lib.php');

        return digiriskShareGeneratedFile($this->db, $fileName, $tableElement, $objectId, $user, $favorite);
    }

    /**
     * Prevenir les adresses configurees qu'un registre vient d'etre declare - issue #5235
     *
     * Le corps du message etait ecrit en dur ici, et le modele d'email eventuel ne faisait que
     * s'y ajouter. Il pilote desormais le message, comme le fait le socle pour les mails de
     * ticket du coeur : la constante DIGIRISKDOLIBARR_TICKET_SUBMITTED_MAIL_MODEL porte le
     * libelle du modele, et tant qu'elle est vide le contenu d'origine est servi, a la
     * signature « - - DOLIBARR - - » pres, qui est justement ce que l'issue demande de retirer.
     *
     * @param  CommonObject $object Ticket declare
     * @param  User         $user   Utilisateur a l'origine de l'action
     * @param  Translate    $langs  Lang object
     * @return void
     */
    protected function sendTicketSubmittedMail($object, User $user, Translate $langs)
    {
        global $conf;

        require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
        require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

        $langs->load('mails');

        $recipients = $this->ticketSubmittedRecipients();
        if (empty($recipients)) {
            $langs->load('errors');
            setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('MailTo')), null, 'warnings');
            dol_syslog(__METHOD__ . ' : try to send email with no recipient defined', LOG_WARNING);
            return;
        }

        $from = getDolGlobalString('MAIN_MAIL_EMAIL_FROM');
        if (!dol_strlen($from)) {
            $langs->load('errors');
            setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('MailFrom')), null, 'warnings');
            dol_syslog(__METHOD__ . ' : MAIN_MAIL_EMAIL_FROM is empty, no mail sent', LOG_WARNING);
            return;
        }

        // Garde historique : l'envoi n'a lieu que si un identifiant SMTP est configure. Trace,
        // parce que sans cette ligne un envoi manquant ressemble a un bug du declenchement
        if (empty($conf->global->MAIN_MAIL_SMTPS_ID)) {
            dol_syslog(__METHOD__ . ' : MAIN_MAIL_SMTPS_ID is empty, no mail sent', LOG_WARNING);
            return;
        }

        list($subject, $message) = $this->ticketSubmittedMailContent($object, $user, $langs);

        $sent = [];
        foreach ($recipients as $sendto) {
            // Un CMailFile par destinataire, comme le faisait le code d'origine : une adresse
            // invalide n'empeche pas les suivantes d'etre servies
            $mailfile = new CMailFile($subject, $sendto, $from, $message, [], [], [], '', '', 0, -1, '', '', 'tic' . $object->id, '', 'ticket');
            if ($mailfile->error) {
                setEventMessages($mailfile->error, $mailfile->errors, 'errors');
                continue;
            }

            if ($mailfile->sendfile()) {
                $sent[] = $sendto;
                continue;
            }

            $langs->load('other');
            $mesg = '<div class="error">' . $langs->transnoentities('ErrorFailedToSendMail', dol_escape_htmltag($from), dol_escape_htmltag($sendto));
            if ($mailfile->error) {
                $mesg .= '<br>' . $mailfile->error;
            }
            setEventMessages($mesg . '</div>', null, 'warnings');
        }

        if (empty($sent)) {
            return;
        }

        // Un evenement pour l'envoi, et non un par destinataire : c'est le meme message.
        // L'ancien code sortait de sa boucle des le premier succes, donc les adresses
        // suivantes de la liste n'etaient jamais servies
        $actioncomm               = new ActionComm($this->db);
        $actioncomm->type_code    = 'AC_OTH_AUTO';
        $actioncomm->code         = 'AC_TICKET_CREATE';
        $actioncomm->elementtype  = 'ticket';
        $actioncomm->fk_element   = $object->id;
        $actioncomm->datep        = dol_now();
        $actioncomm->userownerid  = $user->id;
        $actioncomm->percentage   = -1;
        $actioncomm->label        = $langs->transnoentities('TicketCreationMailWellSent');
        $actioncomm->note_private = $langs->transnoentities('TicketCreationMailSent', implode(', ', $sent));

        $actioncomm->create($user);
    }

    /**
     * Destinataires du mail de declaration, tels que saisis dans la configuration.
     *
     * @return array Adresses, sans doublon ni entree vide
     */
    protected function ticketSubmittedRecipients(): array
    {
        $recipients = preg_split('/[;,]/', (string) getDolGlobalString('DIGIRISKDOLIBARR_TICKET_SUBMITTED_SEND_MAIL_TO'));
        if (!is_array($recipients)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('trim', $recipients), function ($email) { return dol_strlen($email) > 0; })));
    }

    /**
     * Sujet et corps du mail de declaration.
     *
     * @param  CommonObject $object Ticket declare
     * @param  User      $user   Utilisateur a l'origine de l'action
     * @param  Translate $langs  Lang object
     * @return array             [sujet, corps]
     */
    protected function ticketSubmittedMailContent($object, User $user, Translate $langs): array
    {
        require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';

        // TICKET_CREATE peut partir d'ailleurs que de l'interface publique - API, back-office -
        // et rien ne garantit alors que les domaines de langue du message soient charges. Un
        // domaine absent laisserait la cle brute dans le mail, ce qui arrivait deja a
        // WithKindRegards, porte par le socle.
        // load() et non loadLangs() : en CI, PHPStan resout Translate sur le stub PHPUnit de
        // saturne, qui ne connait ni l'une ni l'autre mais dont seule load() est deja gelee
        // dans la baseline - dont le compteur de ce fichier suit ces deux appels
        $langs->load('saturne@saturne');
        $langs->load('digiriskdolibarr@digiriskdolibarr');

        $substitutions  = $this->ticketSubmittedSubstitutions($object, $langs);
        $defaultSubject = $langs->transnoentities('ANewTicketHasBeenSubmitted', getDolGlobalString('MAIN_INFO_SOCIETE_NOM'));
        $mailModelLabel = getDolGlobalString('DIGIRISKDOLIBARR_TICKET_SUBMITTED_MAIL_MODEL');

        if (dol_strlen($mailModelLabel)) {
            $formmail = new FormMail($this->db);

            // getEMailTemplate() rend l'entier -1 sur erreur SQL et un modele vide quand le
            // libelle ne correspond a rien : dans les deux cas on retombe sur le contenu
            // historique plutot que de partir avec un sujet nul
            $template = $formmail->getEMailTemplate($this->db, 'ticket', $user, $langs, 0, 1, $mailModelLabel);
            if (is_object($template) && $template->id > 0) {
                $subject = make_substitutions($template->topic, $substitutions, $langs);

                return [
                    dol_strlen($subject) ? $subject : $defaultSubject,
                    make_substitutions($template->content, $substitutions, $langs)
                ];
            }

            dol_syslog(__METHOD__ . ' : mail model "' . $mailModelLabel . '" not found, falling back on the built-in content', LOG_WARNING);
        }

        return [$defaultSubject, $this->ticketSubmittedDefaultBody($object, $langs, $substitutions)];
    }

    /**
     * Corps historique du mail de declaration, servi tant qu'aucun modele n'est choisi.
     *
     * @param  CommonObject $object     Ticket declare
     * @param  Translate $langs         Lang object
     * @param  array     $substitutions Substitutions deja resolues
     * @return string                   Corps du message, en HTML
     */
    protected function ticketSubmittedDefaultBody($object, Translate $langs, array $substitutions): string
    {
        $ticketUrl = $substitutions['__TICKET_MANAGEMENT_URL__'];

        $message  = $langs->trans('Hello') . ',<br><br>';
        $message .= '<span style="color:#c55a11">' . $langs->trans('ANewTicketHasBeenSubmitted', getDolGlobalString('MAIN_INFO_SOCIETE_NOM')) . '.</span><br><br>';
        $message .= '<strong>' . $langs->trans('Service') . ' : </strong>' . $substitutions['__TICKET_DIGIRISK_ELEMENT__'] . '<br><br>';
        $message .= '<strong>' . $langs->trans('Author') . ' : </strong>' . $substitutions['__TICKET_DECLARANT__'] . '<br><br>';
        $message .= '<strong>' . $langs->trans('The') . ' : </strong>' . $substitutions['__TICKET_DECLARATION_DATE__'] . '<br><br>';
        $message .= '<strong>' . $langs->trans('TicketMessage') . ' : </strong><br>' . $object->message . '<br><br>';
        $message .= $langs->trans('WithKindRegards') . ',<br><br>';
        $message .= '<strong style="color: #c0392b;">' . $langs->trans('SeeTicketUrl') . ' : </strong><a href="' . $ticketUrl . '">' . $ticketUrl . '</a><br><br>';
        $message .= '<span style="color: #afabab; font-size: 12px;">' . $langs->trans('AutoNotificationTicket') . '<br>' . $langs->trans('TicketPublicInterfaceOtherName') . '</span><br><br>';

        return $message;
    }

    /**
     * Substitutions offertes au modele d'email du mail de declaration.
     *
     * Les cles __TICKET_* reprennent celles du socle pour les mails de ticket, completees des
     * champs propres au formulaire de declaration. Le GP/UT est un chkbxlst : une declaration
     * peut en porter plusieurs, ils sont donc tous repris et non le premier seulement.
     *
     * @param  CommonObject $object Ticket declare
     * @param  Translate $langs  Lang object
     * @return array             Substitutions
     */
    protected function ticketSubmittedSubstitutions($object, Translate $langs): array
    {
        dol_include_once('/digiriskdolibarr/lib/digiriskdolibarr_ticket.lib.php');
        dol_include_once('/digiriskdolibarr/class/digiriskelement.class.php');

        $elementLabels = [];
        foreach (digiriskdolibarr_ticket_service_ids($object) as $elementId) {
            // Un objet neuf par element : un fetch en echec laisse les donnees du precedent
            $digiriskElement = new DigiriskElement($this->db);
            if ($digiriskElement->fetch($elementId) > 0) {
                $elementLabels[] = $digiriskElement->ref . ' - ' . $digiriskElement->label;
            }
        }

        $lastname  = (string) ($object->array_options['options_digiriskdolibarr_ticket_lastname'] ?? '');
        $firstname = (string) ($object->array_options['options_digiriskdolibarr_ticket_firstname'] ?? '');
        $date      = $object->array_options['options_digiriskdolibarr_ticket_date'] ?? 0;

        $substitutions = getCommonSubstitutionArray($langs, 0, null, $object);
        complete_substitutions_array($substitutions, $langs, $object);

        $substitutions['__TICKET_REF__']              = (string) $object->ref;
        $substitutions['__TICKET_TRACK_ID__']         = (string) $object->track_id;
        $substitutions['__TICKET_SUBJECT__']          = (string) $object->subject;
        $substitutions['__TICKET_MESSAGE__']          = (string) $object->message;
        $substitutions['__TICKET_DIGIRISK_ELEMENT__'] = implode(', ', $elementLabels);
        $substitutions['__TICKET_DECLARANT__']        = trim(dol_strtoupper($lastname) . ' ' . $firstname);
        $substitutions['__TICKET_DECLARATION_DATE__'] = !empty($date) ? dol_print_date($date, 'daytext', 'tzuser', $langs) : '';
        $substitutions['__TICKET_LOCATION__']         = (string) ($object->array_options['options_digiriskdolibarr_ticket_location'] ?? '');
        $substitutions['__TICKET_MANAGEMENT_URL__']   = DOL_MAIN_URL_ROOT . '/custom/digiriskdolibarr/view/ticket/ticket_card.php?id=' . $object->id;

        return $substitutions;
    }
}
