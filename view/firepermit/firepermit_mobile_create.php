<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    view/firepermit/firepermit_mobile_create.php
 * \ingroup digiriskdolibarr
 * \brief   Mobile/phone interface to quickly create a fire permit
 *          (interior company auto-signed by the connected responsible with their saved signature,
 *           exterior company resolved by SIREN and asked to sign by email, parent prevention plan,
 *           types of work with the equipment used, protections and required certifications).
 *          The labour inspector is taken from the module configuration instead of being asked for.
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

// Load Saturne libraries
require_once __DIR__ . '/../../../saturne/class/saturnesignature.class.php';

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../class/firepermit.class.php';
require_once __DIR__ . '/../../class/preventionplan.class.php';
require_once __DIR__ . '/../../class/digiriskresources.class.php';
require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_mobile.lib.php';

// Global variables definitions
global $conf, $db, $form, $hookmanager, $langs, $moduleNameLowerCase, $user;

// Both select_preventionplan_list() and selectDigiriskElementList() render through the global $form
$form = new Form($db);

// Load translation files required by the page
saturne_load_langs(['other', 'mails', 'companies', 'projects', 'errors']);

/**
 * A fire permit covers a short, well-delimited job: the period is capped to one month
 * (the prevention plan it depends on is the one capped to one year).
 */
const DIGIRISK_MOBILE_FIREPERMIT_MAX_SPAN_DAYS = 31;

// Get parameters
$action  = GETPOST('action', 'aZ09');
$created = GETPOSTINT('created');
$id      = GETPOSTINT('id'); // > 0 => edit an existing fire permit with the same interface

// Initialize technical objects
$object            = new FirePermit($db);
$firepermitdet     = new FirePermitLine($db);
$preventionplan    = new PreventionPlan($db);
$signatory         = new SaturneSignature($db, $moduleNameLowerCase, $object->element);
$digiriskresources = new DigiriskResources($db);
$digiriskelement   = new DigiriskElement($db);
$thirdparty        = new Societe($db);
$contact           = new Contact($db);

// Load numbering modules for the fire permit ref and its lines
$numberingModules = [
    'digiriskelement/' . $object->element        => getDolGlobalString('DIGIRISKDOLIBARR_FIREPERMIT_ADDON'),
    'digiriskelement/' . $firepermitdet->element => getDolGlobalString('DIGIRISKDOLIBARR_FIREPERMITDET_ADDON'),
];
list($refFirePermitMod, $refFirePermitDetMod) = saturne_require_objects_mod($numberingModules, $moduleNameLowerCase);

// Initialize hooks
$hookmanager->initHooks(['firepermitmobilecreate', 'globalcard']);

// Security check
$permissiontoadd = $user->hasRight('digiriskdolibarr', 'firepermit', 'write');
saturne_check_access($permissiontoadd);

/*
 * Edit mode: load the existing fire permit and pre-fill every field of this same interface
 */

$isEdit  = false;
$prefill = [
    'ext_society_id' => 0, 'ext_society_name' => '', 'ext_society_email' => '', 'siren' => '',
    'resp_contact_id' => 0, 'resp_lastname' => '', 'resp_firstname' => '', 'resp_email' => '', 'resp_phone' => '',
    'fk_preventionplan' => 0, 'fk_element' => 0,
    'date_start' => '', 'date_end' => '',
    'risks' => [], 'protections' => [], 'certifications' => [],
];

if ($id > 0 && $object->fetch($id) > 0) {
    $isEdit = true;
    $object->fetch_optionals();

    $prefill['fk_preventionplan'] = (int) $object->fk_preventionplan;
    $prefill['date_start']        = $object->date_start ? dol_print_date($object->date_start, '%Y-%m-%dT%H:%M') : '';
    $prefill['date_end']          = $object->date_end   ? dol_print_date($object->date_end, '%Y-%m-%dT%H:%M')   : '';

    // Exterior company: fetchResourcesFromObject() returns the resolved object itself (an already
    // fetched Societe) for a single match, and 0 when there is none.
    $extSociety = $digiriskresources->fetchResourcesFromObject('ExtSociety', $object);
    if (is_object($extSociety) && $extSociety->id > 0) {
        $prefill['ext_society_id']    = $extSociety->id;
        $prefill['ext_society_name']  = $extSociety->name;
        $prefill['ext_society_email'] = $extSociety->email;
        // Show back the most precise identifier the company has on file.
        $prefill['siren']             = dol_strlen($extSociety->idprof2) ? $extSociety->idprof2 : $extSociety->idprof1;
    }

    // Exterior responsible (ExtSocietyResponsible signatory)
    $editSignatories = $signatory->fetchSignatory('ExtSocietyResponsible', $object->id, 'firepermit');
    if (is_array($editSignatories) && !empty($editSignatories)) {
        $editSignatory              = array_shift($editSignatories);
        $prefill['resp_contact_id'] = $editSignatory->element_id;
        $prefill['resp_lastname']   = $editSignatory->lastname;
        $prefill['resp_firstname']  = $editSignatory->firstname;
        $prefill['resp_email']      = $editSignatory->email;
        $prefill['resp_phone']      = $editSignatory->phone;
    }

    // Types of work (existing lines). The mobile interface uses a single location for the whole
    // permit, so the location of the first line is the one shown back.
    $existingLines = $firepermitdet->fetchAll('', '', 0, 0, ['fk_firepermit' => $object->id]);
    if (is_array($existingLines)) {
        foreach ($existingLines as $existingLine) {
            if (empty($prefill['fk_element'])) {
                $prefill['fk_element'] = (int) $existingLine->fk_element;
            }
            $prefill['risks'][] = [
                'category'       => $existingLine->category,
                'description'    => $existingLine->description,
                'used_equipment' => $existingLine->used_equipment,
            ];
        }
    }

    $prefill['protections']    = !empty($object->array_options['options_mobile_protections'])    ? json_decode($object->array_options['options_mobile_protections'], true)    : [];
    $prefill['certifications'] = !empty($object->array_options['options_mobile_certifications']) ? json_decode($object->array_options['options_mobile_certifications'], true) : [];
}

/*
 * Actions
 */

if ($action == 'add_mobile' && $permissiontoadd) {
    $error         = 0;
    $isAjax        = GETPOSTINT('ajax');
    $errorMessages = [];

    // In AJAX mode, buffer any stray output (e.g. a PHP mail() warning when no SMTP is configured)
    // so it never corrupts the JSON response.
    if ($isAjax) {
        ob_start();
    }

    // Collect an error: record it for the AJAX JSON response and, in classic mode, show it on the reloaded page.
    $addError = function ($message) use (&$error, &$errorMessages, $isAjax) {
        $errorMessages[] = $message;
        $error++;
        if (!$isAjax) {
            setEventMessages($message, null, 'errors');
        }
    };

    // Read parameters
    $extSocietyId      = GETPOSTINT('ext_society_id');
    $idProfInput       = digiriskMobileCleanIdProf(GETPOST('siren', 'alphanohtml'));
    $societyName       = trim(GETPOST('ext_society_name', 'alphanohtml'));
    $societyEmail      = trim(GETPOST('ext_society_email', 'alphanohtml'));
    $respContactId     = GETPOSTINT('resp_contact_id');
    $respLastname      = trim(GETPOST('resp_lastname', 'alphanohtml'));
    $respFirstname     = trim(GETPOST('resp_firstname', 'alphanohtml'));
    $respEmail         = trim(GETPOST('resp_email', 'alphanohtml'));
    $respPhone         = trim(GETPOST('resp_phone', 'alphanohtml'));
    $fkPreventionPlan  = GETPOSTINT('fk_preventionplan');
    $fkElement         = GETPOSTINT('fk_element');
    $dateStartStr      = GETPOST('date_start', 'alpha');
    $dateEndStr        = GETPOST('date_end', 'alpha');

    // Selected types of work — read here so both the create and the edit paths can use them
    $riskCategories = GETPOST('risk_category', 'array');
    $riskComments   = GETPOST('risk_comment', 'array');
    $riskEquipments = GETPOST('risk_equipment', 'array');

    // Selected protections (signalisation OBLIGATION pictos): position + comment + mandatory checkbox, keyed by row index
    $protectionPositions = GETPOST('protection_position', 'array');
    $protectionComments  = GETPOST('protection_comment', 'array');
    $protectionMandatory = GETPOST('protection_mandatory', 'array');
    $protections         = [];
    if (is_array($protectionPositions)) {
        foreach ($protectionPositions as $protectionKey => $protectionPosition) {
            if ($protectionPosition === '' || !is_numeric($protectionPosition)) {
                continue;
            }
            $protections[] = [
                'position'  => (int) $protectionPosition,
                'comment'   => isset($protectionComments[$protectionKey]) ? $protectionComments[$protectionKey] : '',
                'mandatory' => !empty($protectionMandatory[$protectionKey]) ? 1 : 0,
            ];
        }
    }

    // Selected required certifications (CACES, permits...): code + mandatory checkbox, keyed by row index
    $certCodes      = GETPOST('cert_code', 'array');
    $certMandatory  = GETPOST('cert_mandatory', 'array');
    $certifications = [];
    if (is_array($certCodes)) {
        $certificationOptions = digiriskGetCertificationOptions(false);
        foreach ($certCodes as $certKey => $certCode) {
            if ($certCode === '' || !isset($certificationOptions[$certCode])) {
                continue;
            }
            $certifications[] = [
                'code'      => (string) $certCode,
                'mandatory' => !empty($certMandatory[$certKey]) ? 1 : 0,
            ];
        }
    }

    // Convert "YYYY-MM-DDTHH:MM" HTML datetime-local inputs to timestamps
    $dateStart = digiriskMobileParseDateTime($dateStartStr);
    $dateEnd   = digiriskMobileParseDateTime($dateEndStr);

    // Interior company: a saved electronic signature is only required to create (and auto-sign) a permit
    $savedSignature = digiriskGetUserElectronicSignature($db, $user->id);
    if (!$isEdit && !digiriskIsValidSignature($savedSignature)) {
        $addError($langs->trans('MobilePPErrorNoSignature'));
    }

    // Exterior company: either an existing third party or enough data to create one
    if ($extSocietyId <= 0) {
        if (!dol_strlen($societyName)) {
            $addError($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('ExtSociety')));
        }
        if (!digiriskMobileIsValidIdProf($idProfInput)) {
            $addError($langs->trans('MobilePPErrorInvalidSiren'));
        }
    }

    // Exterior responsible: either an existing contact or a lastname to create one
    if ($respContactId <= 0 && !dol_strlen($respLastname)) {
        $addError($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('ExtSocietyResponsible')));
    }

    // A fire permit always depends on a prevention plan
    if ($fkPreventionPlan <= 0) {
        $addError($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('PreventionPlanLinked')));
    }

    // Dates: both required (say which one), end after start, at most one month apart
    if (empty($dateStart) && empty($dateEnd)) {
        $addError($langs->trans('MobilePPErrorDatesRequired'));
    } elseif (empty($dateStart)) {
        $addError($langs->trans('MobilePPErrorStartRequired'));
    } elseif (empty($dateEnd)) {
        $addError($langs->trans('MobilePPErrorEndRequired'));
    } elseif ($dateEnd < $dateStart) {
        $addError($langs->trans('MobilePPErrorEndBeforeStart'));
    } elseif ($dateEnd > dol_time_plus_duree($dateStart, DIGIRISK_MOBILE_FIREPERMIT_MAX_SPAN_DAYS, 'd')) {
        $addError($langs->trans('MobileFPErrorMaxOneMonth'));
    }

    // The fire permit is attached to the entity default fire permit project (FPR)
    $fkProject = getDolGlobalInt('DIGIRISKDOLIBARR_FIREPERMIT_PROJECT');
    if ($fkProject <= 0) {
        $addError($langs->trans('MobileFPErrorNoProject'));
    }

    if (!$error) {
        $db->begin();
        $subError = 0;

        // 1. Resolve or create the exterior company
        if ($extSocietyId > 0) {
            $thirdparty->fetch($extSocietyId);
        } else {
            // The first 9 digits are always the SIREN; a 14 digit input is a full SIRET.
            $thirdparty->name    = $societyName;
            $thirdparty->idprof1 = substr($idProfInput, 0, 9);
            $thirdparty->idprof2 = (dol_strlen($idProfInput) == 14) ? $idProfInput : '';
            $thirdparty->email   = $societyEmail;
            $thirdparty->client  = 0;
            $thirdparty->status  = 1;
            $resSoc = $thirdparty->create($user);
            if ($resSoc > 0) {
                $extSocietyId = $resSoc;
            } else {
                $errorMessages[] = $thirdparty->error ?: 'KO';
                if (!$isAjax) {
                    setEventMessages($thirdparty->error, $thirdparty->errors, 'errors');
                }
                $subError++;
            }
        }

        // 2. Resolve or create the exterior responsible contact
        if (!$subError) {
            if ($respContactId > 0) {
                $contact->fetch($respContactId);
                if (!dol_strlen($respEmail)) {
                    $respEmail = $contact->email;
                }
            } else {
                $contact->socid     = $extSocietyId;
                $contact->lastname  = $respLastname;
                $contact->firstname = $respFirstname;
                $contact->email     = $respEmail;
                $contact->phone_pro = $respPhone;
                $contact->statut    = 1;
                $resContact = $contact->create($user);
                if ($resContact > 0) {
                    $respContactId = $resContact;
                } else {
                    $errorMessages[] = $contact->error ?: 'KO';
                    if (!$isAjax) {
                        setEventMessages($contact->error, $contact->errors, 'errors');
                    }
                    $subError++;
                }
            }
        }

        // 3a. Edit mode: update the existing permit (no re-validation, no re-signature, no email)
        if (!$subError && $isEdit) {
            $object->label             = $langs->transnoentities('FirePermit') . ' - ' . $thirdparty->name;
            $object->date_start        = $dateStart;
            $object->date_end          = $dateEnd;
            $object->fk_preventionplan = $fkPreventionPlan;
            $object->array_options['options_mobile_protections']    = json_encode($protections);
            $object->array_options['options_mobile_certifications'] = json_encode($certifications);

            if ($object->update($user, true) > 0) {
                // Replace the linked exterior company and its responsible
                $digiriskresources->setDigiriskResources($db, $user->id, 'ExtSociety', 'societe', [$extSocietyId], $conf->entity, 'firepermit', $object->id, 0);
                $signatory->setSignatory($object->id, 'firepermit', 'socpeople', [$respContactId], 'ExtSocietyResponsible');

                // Replace the lines by the ones currently in the form
                $oldLines = $firepermitdet->fetchAll('', '', 0, 0, ['fk_firepermit' => $object->id]);
                if (is_array($oldLines)) {
                    foreach ($oldLines as $oldLine) {
                        // Hard delete: a soft delete would keep the rows and they would come back in the form
                        $oldLine->delete($user, true, false);
                    }
                }
                digiriskMobileCreateFirePermitLines($db, $user, $object->id, $fkElement, $riskCategories, $riskComments, $riskEquipments, $refFirePermitDetMod);

                $db->commit();
                $redirect = $_SERVER['PHP_SELF'] . '?created=' . $object->id;
                setEventMessages($langs->trans('MobileFPUpdated', $object->ref), null, 'mesgs');
                if ($isAjax) {
                    while (ob_get_level()) {
                        ob_end_clean();
                    }
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'redirect' => $redirect]);
                    exit;
                }
                header('Location: ' . $redirect);
                exit;
            }

            $errorMessages[] = $object->error ?: 'KO';
            if (!$isAjax) {
                setEventMessages($object->error, $object->errors, 'errors');
            }
            $subError++;
        }

        // 3b. Create the fire permit and wire everything together
        if (!$subError && !$isEdit) {
            $now                       = dol_now();
            $object->ref               = $refFirePermitMod->getNextValue($object);
            $object->ref_ext           = 'digirisk_' . $object->ref;
            $object->date_creation     = $db->idate($now);
            $object->tms               = $now;
            $object->label             = $langs->transnoentities('FirePermit') . ' - ' . $thirdparty->name;
            $object->status            = FirePermit::STATUS_DRAFT;
            $object->fk_project        = $fkProject;
            $object->fk_preventionplan = $fkPreventionPlan;
            $object->date_start        = $dateStart;
            $object->date_end          = $dateEnd;
            $object->fk_user_creat     = $user->id;

            // Store the selected protections (EPI) and required certifications as JSON in dedicated extrafields
            $object->array_options['options_mobile_protections']    = json_encode($protections);
            $object->array_options['options_mobile_certifications'] = json_encode($certifications);

            $resFP = $object->create($user, true);
            if ($resFP > 0) {
                $object->setInProgress($user, true);
                $digiriskresources->setDigiriskResources($db, $user->id, 'ExtSociety', 'societe', [$extSocietyId], $conf->entity, 'firepermit', $object->id, 1);

                // The labour inspector is not asked for on mobile: reuse the one configured for the entity
                $allLinks = $digiriskresources->fetchDigiriskResources();
                if (!empty($allLinks['LabourInspectorSociety']->id[0])) {
                    $digiriskresources->setDigiriskResources($db, $user->id, 'LabourInspector', 'societe', [$allLinks['LabourInspectorSociety']->id[0]], $conf->entity, 'firepermit', $object->id, 1);
                }
                if (!empty($allLinks['LabourInspectorContact']->id[0])) {
                    $digiriskresources->setDigiriskResources($db, $user->id, 'LabourInspectorAssigned', 'socpeople', [$allLinks['LabourInspectorContact']->id[0]], $conf->entity, 'firepermit', $object->id, 1);
                }

                $signatory->setSignatory($object->id, 'firepermit', 'user', [$user->id], 'MasterWorker');
                $signatory->setSignatory($object->id, 'firepermit', 'socpeople', [$respContactId], 'ExtSocietyResponsible');

                // Selected types of work become fire permit lines
                digiriskMobileCreateFirePermitLines($db, $user, $object->id, $fkElement, $riskCategories, $riskComments, $riskEquipments, $refFirePermitDetMod);

                // Validate so signatures can be collected
                $object->setPendingSignature($user, true);

                // Auto-sign the interior side (MasterWorker) with the responsible saved signature
                $masterWorkers = $signatory->fetchSignatory('MasterWorker', $object->id, 'firepermit');
                if (is_array($masterWorkers) && !empty($masterWorkers)) {
                    $masterWorker                 = array_shift($masterWorkers);
                    $masterWorker->signature      = $savedSignature;
                    $masterWorker->signature_date = dol_now();
                    if ($masterWorker->update($user, true) > 0) {
                        $masterWorker->setSigned($user, true);
                    }
                }

                // Ask the exterior responsible to sign by email (email-only, no SMS configured)
                $extSignatories = $signatory->fetchSignatory('ExtSocietyResponsible', $object->id, 'firepermit');
                if (is_array($extSignatories) && !empty($extSignatories)) {
                    $extSignatory = array_shift($extSignatories);
                    if (isValidEmail($extSignatory->email) && dol_strlen(getDolGlobalString('MAIN_MAIL_EMAIL_FROM'))) {
                        require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';

                        $signatureUrl = dol_buildpath('/custom/saturne/public/signature/add_signature.php?track_id=' . $extSignatory->signature_url . '&entity=' . $conf->entity . '&module_name=' . $moduleNameLowerCase . '&object_type=firepermit&document_type=FirePermitDocument', 3);

                        $from    = getDolGlobalString('MAIN_MAIL_EMAIL_FROM');
                        $subject = $langs->transnoentities('MobileFPSignatureEmailSubject', $object->ref);
                        $message = $langs->transnoentities('MobileFPSignatureEmailContent', $thirdparty->name, $signatureUrl);

                        $mailfile = new CMailFile($subject, $extSignatory->email, $from, $message, [], [], [], '', '', 0, -1, '', '', '', '', 'mail');
                        if (!$mailfile->error && (dol_strlen(getDolGlobalString('MAIN_MAIL_SMTPS_ID')) || getDolGlobalInt('SATURNE_USE_ALL_EMAIL_MODE') > 0)) {
                            if ($mailfile->sendfile()) {
                                $extSignatory->last_email_sent_date = dol_now();
                                $extSignatory->update($user, true);
                                $extSignatory->setPending($user, true);
                            } else {
                                setEventMessages($langs->trans('MobilePPWarningEmailNotSent'), null, 'warnings');
                            }
                        } else {
                            setEventMessages($langs->trans('MobilePPWarningEmailNotConfigured'), null, 'warnings');
                        }
                    } else {
                        setEventMessages($langs->trans('MobilePPWarningEmailNotConfigured'), null, 'warnings');
                    }
                }

                $db->commit();
                $redirect = $_SERVER['PHP_SELF'] . '?created=' . $object->id;
                setEventMessages($langs->trans('MobileFPCreated', $object->ref), null, 'mesgs');
                if ($isAjax) {
                    while (ob_get_level()) {
                        ob_end_clean();
                    }
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'redirect' => $redirect]);
                    exit;
                }
                header('Location: ' . $redirect);
                exit;
            } else {
                $errorMessages[] = $object->error ?: 'KO';
                if (!$isAjax) {
                    setEventMessages($object->error, $object->errors, 'errors');
                }
                $subError++;
            }
        }

        if ($subError) {
            $db->rollback();
            $error += $subError;
        }
    }

    // AJAX request: return the outcome as JSON so the form keeps its values on error
    if ($isAjax) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'errors' => $errorMessages]);
        exit;
    }
}

/*
 * View
 */

$title    = $langs->trans('MobileQuickCreation');
$help_url = 'FR:Module_Digirisk';
$moreJS   = [
    '/custom/saturne/js/saturne.min.js',
    '/custom/digiriskdolibarr/js/signature-pad.min.js',
    '/custom/digiriskdolibarr/js/digiriskdolibarr.min.js',
];
$moreCSS  = [
    '/custom/saturne/css/saturne.min.css',
    '/custom/digiriskdolibarr/css/digiriskdolibarr.min.css',
];

$conf->dol_hide_topmenu         = 1;
$conf->dol_hide_leftmenu        = 1;
$conf->global->MAIN_FAVICON_URL = DOL_URL_ROOT . '/custom/digiriskdolibarr/img/digiriskdolibarr_color.png';

llxHeader('', $title, $help_url, '', 0, 0, $moreJS, $moreCSS, '', 'template-pwa digirisk-mobile-create');

if (!$permissiontoadd) {
    accessforbidden($langs->trans('NotEnoughPermissions'), 0);
    exit;
}

// Fixed top header
$pwaHeaderTitle = $title;
require_once __DIR__ . '/../../core/tpl/frontend/digiriskdolibarr_pwa_header.tpl.php';

if ($created > 0) {
    // Success screen
    $object->fetch($created);
    require_once __DIR__ . '/../../core/tpl/frontend/firepermit_mobile_success.tpl.php';
} else {
    // Creation form screen
    $savedSignature = digiriskGetUserElectronicSignature($db, $user->id);
    require_once __DIR__ . '/../../core/tpl/frontend/firepermit_mobile_form.tpl.php';
}

// Application navigation, so this screen is not a dead end when reached from the PWA
require_once __DIR__ . '/../../core/tpl/frontend/digiriskdolibarr_pwa_bottom_nav.tpl.php';

llxFooter();
$db->close();
