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
 * \file    view/preventionplan/preventionplan_mobile_create.php
 * \ingroup digiriskdolibarr
 * \brief   Mobile/phone interface to quickly create a prevention plan
 *          (interior company auto-signed by the connected responsible with their saved signature,
 *           exterior company resolved by SIREN and asked to sign by email, start/end dates capped to one year).
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
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

// Load Saturne libraries
require_once __DIR__ . '/../../../saturne/class/saturnesignature.class.php';
require_once __DIR__ . '/../../../saturne/lib/medias.lib.php';

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../class/preventionplan.class.php';
require_once __DIR__ . '/../../class/digiriskresources.class.php';
require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_mobile.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_preventionplan.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $moduleNameLowerCase, $user;

// Load translation files required by the page
saturne_load_langs(['other', 'mails', 'companies', 'projects', 'errors']);

// Get parameters
$action  = GETPOST('action', 'aZ09');
$created = GETPOSTINT('created');
$id      = GETPOSTINT('id'); // > 0 => edit an existing prevention plan with the same interface

// Initialize technical objects
$object            = new PreventionPlan($db);
$preventionplandet = new PreventionPlanLine($db);
$signatory         = new SaturneSignature($db, $moduleNameLowerCase, $object->element);
$digiriskresources = new DigiriskResources($db);
$thirdparty        = new Societe($db);
$contact           = new Contact($db);
$form              = new Form($db);

// Load numbering modules for the prevention plan ref and its lines
$numberingModules = [
    'digiriskelement/' . $object->element            => getDolGlobalString('DIGIRISKDOLIBARR_PREVENTIONPLAN_ADDON'),
    'digiriskelement/' . $preventionplandet->element => getDolGlobalString('DIGIRISKDOLIBARR_PREVENTIONPLANDET_ADDON'),
];
list($refPreventionPlanMod, $refPreventionPlanDetMod) = saturne_require_objects_mod($numberingModules, $moduleNameLowerCase);

// Initialize hooks
$hookmanager->initHooks(['preventionplanmobilecreate', 'globalcard']);

// Security check
$permissiontoadd = $user->hasRight('digiriskdolibarr', 'preventionplan', 'write');
saturne_check_access($permissiontoadd);

// Risk photos are taken before the plan exists: the Saturne media block uploads them right away
// into a per-session directory keyed by this token, and they are moved to the plan on save
$uploadContext = 'digiriskdolibarr_preventionplan_mobile_' . ($id > 0 ? $id : 'new');
$uploadToken   = saturne_get_upload_token($uploadContext);

/*
 * Edit mode: load the existing plan and pre-fill every field of this same interface
 */

$isEdit  = false;

// Default dates from admin config
$defaultStartToday = getDolGlobalInt('DIGIRISKDOLIBARR_PREVENTIONPLAN_DEFAULT_DATE_START_TODAY', 1);
$defaultDuration   = getDolGlobalInt('DIGIRISKDOLIBARR_PREVENTIONPLAN_DEFAULT_DURATION', 30);
$defaultDateStart  = $defaultStartToday ? dol_print_date(dol_now(), '%Y-%m-%d') : '';
$defaultDateEnd    = $defaultStartToday ? dol_print_date(dol_time_plus_duree(dol_now(), $defaultDuration, 'd'), '%Y-%m-%d') : '';

$prefill = [
    'ext_society_id' => 0, 'ext_society_name' => '', 'ext_society_email' => '', 'siren' => '',
    'ext_society_address' => '', 'ext_society_zip' => '', 'ext_society_town' => '',
    'resp_contact_id' => 0, 'resp_lastname' => '', 'resp_firstname' => '', 'resp_email' => '', 'resp_phone' => '',
    'date_start' => $defaultDateStart, 'date_end' => $defaultDateEnd,
    'prior_visit_bool' => 0, 'prior_visit_text' => '', 'prior_visit_date' => '',
    'cssct_intervention' => 0, 'categories' => [],
    'risks' => [], 'certifications' => [],
];

if ($id > 0 && $object->fetch($id) > 0) {
    $isEdit = true;
    $object->fetch_optionals();

    $prefill['date_start'] = $object->date_start ? dol_print_date($object->date_start, '%Y-%m-%d') : '';
    $prefill['date_end']   = $object->date_end   ? dol_print_date($object->date_end, '%Y-%m-%d')   : '';

    $prefill['prior_visit_bool'] = (int) $object->prior_visit_bool;
    $prefill['prior_visit_text'] = (string) $object->prior_visit_text;
    $prefill['prior_visit_date'] = $object->prior_visit_date ? dol_print_date($object->prior_visit_date, '%Y-%m-%d') : '';

    $prefill['cssct_intervention'] = (int) $object->cssct_intervention;

    // Tags already set on the plan
    if (isModEnabled('categorie')) {
        $editCategory   = new Categorie($db);
        $editCategories = $editCategory->containing($object->id, 'preventionplan');
        if (is_array($editCategories)) {
            foreach ($editCategories as $editCategoryItem) {
                $prefill['categories'][] = $editCategoryItem->id;
            }
        }
    }

    // Exterior company: fetchResourcesFromObject() returns the resolved object itself (an already
    // fetched Societe) for a single match, and 0 when there is none.
    $extSociety = $digiriskresources->fetchResourcesFromObject('ExtSociety', $object);
    if (is_object($extSociety) && $extSociety->id > 0) {
        $prefill['ext_society_id']      = $extSociety->id;
        $prefill['ext_society_name']    = $extSociety->name;
        $prefill['ext_society_email']   = $extSociety->email;
        $prefill['ext_society_address'] = $extSociety->address;
        $prefill['ext_society_zip']     = $extSociety->zip;
        $prefill['ext_society_town']    = $extSociety->town;
        // Show back the most precise identifier the company has on file.
        $prefill['siren']             = dol_strlen($extSociety->idprof2) ? $extSociety->idprof2 : $extSociety->idprof1;
    }

    // Exterior responsible (ExtSocietyResponsible signatory)
    $editSignatories = $signatory->fetchSignatory('ExtSocietyResponsible', $object->id, 'preventionplan');
    if (is_array($editSignatories) && !empty($editSignatories)) {
        $editSignatory                = array_shift($editSignatories);
        $prefill['resp_contact_id']   = $editSignatory->element_id;
        $prefill['resp_lastname']     = $editSignatory->lastname;
        $prefill['resp_firstname']    = $editSignatory->firstname;
        $prefill['resp_email']        = $editSignatory->email;
        $prefill['resp_phone']        = $editSignatory->phone;
    }

    // Protections are stored flat in the extrafield, each one carrying the risk it belongs to
    $storedProtections = !empty($object->array_options['options_mobile_protections']) ? json_decode($object->array_options['options_mobile_protections'], true) : [];
    if (!is_array($storedProtections)) {
        $storedProtections = [];
    }

    // Which company each risk concerns, keyed by danger category
    $storedRiskCompanies = !empty($object->array_options['options_mobile_risk_companies']) ? json_decode($object->array_options['options_mobile_risk_companies'], true) : [];
    if (!is_array($storedRiskCompanies)) {
        $storedRiskCompanies = [];
    }

    // Risks (existing lines), with the protections and the photos attached to each of them
    $existingLines = $preventionplandet->fetchAll('', '', 0, 0, ['fk_preventionplan' => $object->id]);
    if (is_array($existingLines)) {
        foreach ($existingLines as $existingLine) {
            $lineProtections = [];
            foreach ($storedProtections as $storedProtection) {
                // Plans created before the protections moved inside the risks have no risk_category:
                // keep them on the first risk rather than dropping them silently
                $storedRiskCategory = isset($storedProtection['risk_category']) ? (int) $storedProtection['risk_category'] : 0;
                if ($storedRiskCategory === (int) $existingLine->category || (empty($storedRiskCategory) && empty($prefill['risks']))) {
                    $lineProtections[] = $storedProtection;
                }
            }
            // Plans created before the concerned companies existed have nothing stored: both are ticked
            $lineCompanies = $storedRiskCompanies[(string) $existingLine->category] ?? ['eu' => 1, 'ee' => 1];

            $prefill['risks'][] = [
                'category'    => $existingLine->category,
                'description' => $existingLine->description,
                'company_eu'  => !empty($lineCompanies['eu']) ? 1 : 0,
                'company_ee'  => !empty($lineCompanies['ee']) ? 1 : 0,
                'protections' => $lineProtections,
            ];
        }
    }

    $prefill['certifications'] = !empty($object->array_options['options_mobile_certifications']) ? json_decode($object->array_options['options_mobile_certifications'], true) : [];
}

/*
 * Actions
 */

// Photo taken or removed on a risk block: the Saturne media block posts here and expects the
// refreshed block as a response. Only the temporary directory of this form session is writable.
if (($action == 'uploadPhoto' || $action == 'deletePhoto') && $permissiontoadd) {
    $uploadSubDir   = GETPOST('sub_dir', 'alpha');
    $uploadBlockDir = DIGIRISK_MOBILE_UPLOAD_DIR . '/' . $uploadToken . '/';

    if (strpos($uploadSubDir, $uploadBlockDir) !== 0 || strpos($uploadSubDir, '..') !== false) {
        accessforbidden($langs->trans('NotEnoughPermissions'), 0, 0, 1);
    }

    $uploadDir = $conf->digiriskdolibarr->dir_output . '/' . $uploadSubDir;
    if (!dol_is_dir($uploadDir)) {
        dol_mkdir($uploadDir);
    }

    if ($action == 'uploadPhoto' && getDolGlobalInt('MAIN_UPLOAD_DOC')) {
        // The media block only sends images, make sure of it before writing anything
        $isImageUpload = true;
        $uploadedFiles = isset($_FILES['userfile']['tmp_name']) ? (array) $_FILES['userfile']['tmp_name'] : [];
        foreach ($uploadedFiles as $uploadedTmpName) {
            if (empty($uploadedTmpName)) {
                continue;
            }
            $fileInfo = new finfo(FILEINFO_MIME_TYPE);
            if (strpos((string) $fileInfo->file($uploadedTmpName), 'image/') !== 0) {
                $isImageUpload = false;
                break;
            }
        }
        if ($isImageUpload) {
            dol_add_file_process($uploadDir, GETPOSTINT('overwrite'), 1, 'userfile', '', null, '', 1);
        }
    } elseif ($action == 'deletePhoto') {
        $photoToDelete = dol_sanitizeFileName(GETPOST('filename', 'alphanohtml'));
        if (dol_strlen($photoToDelete) && dol_is_file($uploadDir . '/' . $photoToDelete)) {
            dol_delete_file($uploadDir . '/' . $photoToDelete, 0, 0, 0, null, 1);
        }
    }

    // The risk blocks added on the fly are not part of any server-rendered page, so answer with
    // the single block that was just updated. Its index is the last segment of the sub directory.
    print saturne_render_media_block('digiriskdolibarr', $uploadSubDir, 'risk-' . basename($uploadSubDir), 'digiriskdolibarr,preventionplan,write', ['show_photo' => true, 'show_audio' => false, 'show_file' => false]);
    exit;
}

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
    $extSocietyId  = GETPOSTINT('ext_society_id');
    $idProfInput   = digiriskMobileCleanIdProf(GETPOST('siren', 'alphanohtml'));
    $societyName   = trim(GETPOST('ext_society_name', 'alphanohtml'));
    $societyEmail  = trim(GETPOST('ext_society_email', 'alphanohtml'));
    $societyAddr   = trim(GETPOST('ext_society_address', 'alphanohtml'));
    $societyZip    = trim(GETPOST('ext_society_zip', 'alphanohtml'));
    $societyTown   = trim(GETPOST('ext_society_town', 'alphanohtml'));
    $respContactId = GETPOSTINT('resp_contact_id');
    $respLastname  = trim(GETPOST('resp_lastname', 'alphanohtml'));
    $respFirstname = trim(GETPOST('resp_firstname', 'alphanohtml'));
    $respEmail     = trim(GETPOST('resp_email', 'alphanohtml'));
    $respPhone     = trim(GETPOST('resp_phone', 'alphanohtml'));
    $dateStartStr  = GETPOST('date_start', 'alpha');
    $dateEndStr    = GETPOST('date_end', 'alpha');

    // Prior visit: its comment and date only make sense once the visit is declared done
    $priorVisitBool = GETPOSTINT('prior_visit_bool');
    $priorVisitText = $priorVisitBool ? GETPOST('prior_visit_text', 'restricthtml') : '';
    $priorVisitDate = $priorVisitBool ? digiriskMobileParseDateTime(GETPOST('prior_visit_date', 'alpha')) : 0;

    $cssctIntervention = GETPOSTINT('cssct_intervention');
    $planCategories    = GETPOST('categories', 'array');

    // Selected risks (danger categories) — read here so both the create and the edit paths can use them.
    // Each risk block carries its own description, photos and protections, all keyed by the block index.
    $riskCategories = GETPOST('risk_category', 'array');
    $riskComments   = GETPOST('risk_comment', 'array');

    // GETPOST('...', 'array') only sanitizes the first level, so the two-level arrays are read and cleaned here
    $riskProtectionPositions = (isset($_POST['risk_protection_position']) && is_array($_POST['risk_protection_position'])) ? $_POST['risk_protection_position'] : [];
    $riskProtectionComments  = (isset($_POST['risk_protection_comment'])  && is_array($_POST['risk_protection_comment']))  ? $_POST['risk_protection_comment']  : [];

    // Concerned companies, one checkbox each, keyed by risk block index
    $riskCompanyEu = GETPOST('risk_company_eu', 'array');
    $riskCompanyEe = GETPOST('risk_company_ee', 'array');

    $risks = [];
    if (is_array($riskCategories)) {
        foreach ($riskCategories as $riskKey => $riskCategory) {
            if ($riskCategory === '' || !is_numeric($riskCategory)) {
                continue;
            }

            // Protections (signalisation OBLIGATION pictos) of this risk: position + comment.
            // A protection attached to a risk is always mandatory, the flag is only kept so the
            // stored shape stays the one the fire permit interface writes.
            $riskProtections = [];
            if (isset($riskProtectionPositions[$riskKey]) && is_array($riskProtectionPositions[$riskKey])) {
                foreach ($riskProtectionPositions[$riskKey] as $protectionKey => $protectionPosition) {
                    if (!is_scalar($protectionPosition) || $protectionPosition === '' || !is_numeric($protectionPosition)) {
                        continue;
                    }
                    $riskProtections[] = [
                        'position'  => (int) $protectionPosition,
                        'comment'   => isset($riskProtectionComments[$riskKey][$protectionKey]) ? dol_string_nohtmltag((string) $riskProtectionComments[$riskKey][$protectionKey]) : '',
                        'mandatory' => 1,
                    ];
                }
            }

            $risks[] = [
                'category'    => (int) $riskCategory,
                'description' => isset($riskComments[$riskKey]) ? $riskComments[$riskKey] : '',
                'company_eu'  => !empty($riskCompanyEu[$riskKey]) ? 1 : 0,
                'company_ee'  => !empty($riskCompanyEe[$riskKey]) ? 1 : 0,
                'protections' => $riskProtections,
                'block_index' => (int) $riskKey,
            ];
        }
    }

    // Which company each risk concerns, keyed by danger category like the form displays it
    $riskCompanies = [];
    foreach ($risks as $riskEntry) {
        $riskCompanies[(string) $riskEntry['category']] = ['eu' => $riskEntry['company_eu'], 'ee' => $riskEntry['company_ee']];
    }

    // The protections extrafield stays a flat list — shared with the fire permit view — but each
    // entry now says which risk it belongs to
    $protections = [];
    foreach ($risks as $riskEntry) {
        foreach ($riskEntry['protections'] as $riskProtection) {
            $riskProtection['risk_category'] = $riskEntry['category'];
            $protections[]                   = $riskProtection;
        }
    }

    // Move the photos of every risk from their temporary directory to the plan once its ref is
    // known, drop the directories of the removed risks, then release the upload token
    $saveRiskPhotos = function () use ($object, $risks, $uploadToken, $uploadContext) {
        $keptCategories = [];
        foreach ($risks as $riskEntry) {
            $keptCategories[] = $riskEntry['category'];
            digiriskMobileMoveRiskPhotos($object->element, $object->ref, $riskEntry['category'], $uploadToken, $riskEntry['block_index']);
        }
        digiriskMobileCleanRiskPhotoDirs($object->element, $object->ref, $keptCategories);
        saturne_invalidate_upload_token($uploadContext, 'digiriskdolibarr', DIGIRISK_MOBILE_UPLOAD_DIR);
    };

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

    // Convert "YYYY-MM-DD" HTML date inputs to timestamps
    $dateStart = digiriskMobileParseDateTime($dateStartStr);
    $dateEnd   = digiriskMobileParseDateTime($dateEndStr);

    // Interior company: a saved electronic signature is only required to create (and auto-sign) a plan
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

    // Exterior responsible: either an existing contact or enough data to create one
    if ($respContactId <= 0) {
        if (!dol_strlen($respLastname)) {
            $addError($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Lastname')));
        }
        if (!dol_strlen($respFirstname)) {
            $addError($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Firstname')));
        }
    }

    // L'email du responsable portait une etoile sans etre verifie : on pouvait enregistrer sans, le
    // contact etait cree sans adresse et la demande de signature ne pouvait plus partir. C'est tout
    // le parcours qui repose dessus, y compris pour un contact deja existant qui n'en aurait pas.
    if (!dol_strlen($respEmail)) {
        $addError($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Email')));
    } elseif (!isValidEmail($respEmail)) {
        $addError($langs->trans('ErrorBadEMail', $respEmail));
    }

    // Dates: both required (say which one), end after start, at most one year apart
    if (empty($dateStart) && empty($dateEnd)) {
        $addError($langs->trans('MobilePPErrorDatesRequired'));
    } elseif (empty($dateStart)) {
        $addError($langs->trans('MobilePPErrorStartRequired'));
    } elseif (empty($dateEnd)) {
        $addError($langs->trans('MobilePPErrorEndRequired'));
    } elseif ($dateEnd < $dateStart) {
        $addError($langs->trans('MobilePPErrorEndBeforeStart'));
    } elseif ($dateEnd > dol_time_plus_duree($dateStart, 1, 'y')) {
        $addError($langs->trans('MobilePPErrorMaxOneYear'));
    }

    // The prevention plan is attached to the entity default prevention plan project (PPR)
    $fkProject = getDolGlobalInt('DIGIRISKDOLIBARR_PREVENTIONPLAN_PROJECT');
    if ($fkProject <= 0) {
        $addError($langs->trans('MobilePPErrorNoProject'));
    }

    if (!$error) {
        $db->begin();
        $subError = 0;

        // 1. Resolve or create the exterior company
        if ($extSocietyId > 0) {
            $thirdparty->fetch($extSocietyId);

            // L'adresse est affichee et modifiable dans le formulaire : la corriger sur place doit
            // avoir un effet, sinon la saisie est silencieusement perdue. On n'ecrit que si elle a
            // change, pour ne pas toucher au tiers a chaque enregistrement du plan.
            if ($thirdparty->address != $societyAddr || $thirdparty->zip != $societyZip || $thirdparty->town != $societyTown) {
                $thirdparty->address = $societyAddr;
                $thirdparty->zip     = $societyZip;
                $thirdparty->town    = $societyTown;
                $thirdparty->update($thirdparty->id, $user);
            }
        } else {
            // The first 9 digits are always the SIREN; a 14 digit input is a full SIRET.
            $thirdparty->name    = $societyName;
            $thirdparty->idprof1 = substr($idProfInput, 0, 9);
            $thirdparty->idprof2 = (dol_strlen($idProfInput) == 14) ? $idProfInput : '';
            $thirdparty->email   = $societyEmail;
            $thirdparty->address = $societyAddr;
            $thirdparty->zip     = $societyZip;
            $thirdparty->town    = $societyTown;
            $thirdparty->client  = 0;
            $thirdparty->status  = 1;
            $resSoc = $thirdparty->create($user);
            if ($resSoc > 0) {
                $extSocietyId = $resSoc;
            } else {
                $msg = (dol_strlen($thirdparty->error) && $thirdparty->error !== 'KO') ? $thirdparty->error : $langs->trans('MobilePPErrorCreatingThirdparty');
                dol_syslog('preventionplan_mobile_create: thirdparty->create failed: ' . $thirdparty->error . ' | ' . implode(', ', (array) $thirdparty->errors), LOG_ERR);
                $errorMessages[] = $msg;
                if (!$isAjax) {
                    setEventMessages($msg, null, 'errors');
                }
                $subError++;
            }
        }

        // 2. Resolve or create the exterior responsible contact
        if (!$subError) {
            if ($respContactId > 0) {
                $contact->fetch($respContactId);

                // Un contact choisi dans la liste peut n'avoir aucune adresse : celle saisie ici
                // est alors reportee sur sa fiche, faute de quoi la demande de signature repartirait
                // dans le vide au prochain plan
                if (dol_strlen($respEmail) && $contact->email != $respEmail) {
                    $contact->email = $respEmail;
                    $contact->update($contact->id, $user);
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
                    $msg = (dol_strlen($contact->error) && $contact->error !== 'KO') ? $contact->error : $langs->trans('MobilePPErrorCreatingContact');
                    dol_syslog('preventionplan_mobile_create: contact->create failed: ' . $contact->error . ' | ' . implode(', ', (array) $contact->errors), LOG_ERR);
                    $errorMessages[] = $msg;
                    if (!$isAjax) {
                        setEventMessages($msg, null, 'errors');
                    }
                    $subError++;
                }
            }
        }

        // 3b. Edit mode: update the existing plan (no re-validation, no re-signature, no email)
        if (!$subError && $isEdit) {
            $object->label              = $langs->transnoentities('PreventionPlan') . ' - ' . $thirdparty->name;
            $object->date_start         = $dateStart;
            $object->date_end           = $dateEnd;
            $object->prior_visit_bool   = $priorVisitBool;
            $object->prior_visit_text   = $priorVisitText;
            $object->prior_visit_date   = $priorVisitDate;
            $object->cssct_intervention = $cssctIntervention;
            $object->array_options['options_mobile_protections']    = json_encode($protections);
            $object->array_options['options_mobile_certifications'] = json_encode($certifications);
            $object->array_options['options_mobile_risk_companies'] = json_encode($riskCompanies);

            if ($object->update($user, true) > 0) {
                // Tags: an empty selection means the user removed them all
                if (isModEnabled('categorie')) {
                    $object->setCategories($planCategories);
                }

                // Replace the linked exterior company and its responsible
                $digiriskresources->setDigiriskResources($db, $user->id, 'ExtSociety', 'societe', [$extSocietyId], $conf->entity, 'preventionplan', $object->id, 0);
                $signatory->setSignatory($object->id, 'preventionplan', 'socpeople', [$respContactId], 'ExtSocietyResponsible');

                // Replace the risk lines by the ones currently in the form
                $oldLines = $preventionplandet->fetchAll('', '', 0, 0, ['fk_preventionplan' => $object->id]);
                if (is_array($oldLines)) {
                    foreach ($oldLines as $oldLine) {
                        // Hard delete: a soft delete would keep the rows and they would come back in the form
                        $oldLine->delete($user, true, false);
                    }
                }
                foreach ($risks as $riskEntry) {
                    $line                    = new PreventionPlanLine($db);
                    $line->ref               = $refPreventionPlanDetMod->getNextValue($line);
                    $line->entity            = $conf->entity;
                    $line->date_creation     = $db->idate(dol_now());
                    $line->status            = PreventionPlanLine::STATUS_VALIDATED;
                    $line->category          = $riskEntry['category'];
                    $line->description       = $riskEntry['description'];
                    $line->prevention_method = '';
                    $line->fk_preventionplan = $object->id;
                    $line->fk_element        = 0;
                    $line->create($user, true);
                }

                $saveRiskPhotos();

                $db->commit();

                // Les etapes ci-dessus sont toutes faites sans trigger : le document est regenere
                // ici, une fois les risques et les photos en base, pour que la diffusion deja en
                // ligne cesse de presenter la version d'avant modification
                digiriskRefreshPreventionPlanDocument($db, (int) $object->id, $user, $langs, true);

                $redirect = $_SERVER['PHP_SELF'] . '?created=' . $object->id;
                setEventMessages($langs->trans('MobilePPUpdated', $object->ref), null, 'mesgs');
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

            $msg = (dol_strlen($object->error) && $object->error !== 'KO') ? $object->error : $langs->trans('MobilePPErrorUpdatingPlan');
            dol_syslog('preventionplan_mobile_create: object->update failed: ' . $object->error . ' | ' . implode(', ', (array) $object->errors), LOG_ERR);
            $errorMessages[] = $msg;
            if (!$isAjax) {
                setEventMessages($msg, null, 'errors');
            }
            $subError++;
        }

        // 3. Create the prevention plan and wire everything together
        if (!$subError && !$isEdit) {
            $now                   = dol_now();
            $object->ref           = $refPreventionPlanMod->getNextValue($object);
            $object->ref_ext       = 'digirisk_' . $object->ref;
            $object->date_creation = $db->idate($now);
            $object->tms           = $now;
            $object->label         = $langs->transnoentities('PreventionPlan') . ' - ' . $thirdparty->name;
            $object->status        = PreventionPlan::STATUS_DRAFT;
            $object->fk_project    = $fkProject;
            $object->date_start    = $dateStart;
            $object->date_end      = $dateEnd;
            $object->fk_user_creat = $user->id;

            $object->prior_visit_bool   = $priorVisitBool;
            $object->prior_visit_text   = $priorVisitText;
            $object->prior_visit_date   = $priorVisitDate;
            $object->cssct_intervention = $cssctIntervention;

            // Store the selected protections (EPI), required certifications and the companies each
            // risk concerns as JSON in dedicated extrafields
            $object->array_options['options_mobile_protections']    = json_encode($protections);
            $object->array_options['options_mobile_certifications'] = json_encode($certifications);
            $object->array_options['options_mobile_risk_companies'] = json_encode($riskCompanies);

            $resPP = $object->create($user, true);
            if ($resPP > 0) {
                if (isModEnabled('categorie') && !empty($planCategories)) {
                    $object->setCategories($planCategories);
                }

                $object->setInProgress($user, true);
                $digiriskresources->setDigiriskResources($db, $user->id, 'ExtSociety', 'societe', [$extSocietyId], $conf->entity, 'preventionplan', $object->id, 1);
                $signatory->setSignatory($object->id, 'preventionplan', 'user', [$user->id], 'MasterWorker');
                $signatory->setSignatory($object->id, 'preventionplan', 'socpeople', [$respContactId], 'ExtSocietyResponsible');

                // Selected risks (danger categories) become prevention plan lines, and their photos
                // land in the object document directory, one folder per danger category
                foreach ($risks as $riskEntry) {
                    $line                    = new PreventionPlanLine($db);
                    $line->ref               = $refPreventionPlanDetMod->getNextValue($line);
                    $line->entity            = $conf->entity;
                    $line->date_creation     = $db->idate(dol_now());
                    $line->status            = PreventionPlanLine::STATUS_VALIDATED;
                    $line->category          = $riskEntry['category'];
                    $line->description       = $riskEntry['description'];
                    $line->prevention_method = '';
                    $line->fk_preventionplan = $object->id;
                    $line->fk_element        = 0; // no GP/UT in the simplified mobile flow
                    $line->create($user, true);
                }

                // Validate so signatures can be collected
                $object->setPendingSignature($user, true);

                // The definitive ref is only assigned during validation: until then the object
                // carries its provisional one, which would send the photos to a directory nothing
                // reads back, and put "(PROV12)" in the signature email
                $object->fetch($object->id);

                $saveRiskPhotos();

                // Auto-sign the interior side (MasterWorker) with the responsible saved signature
                $masterWorkers = $signatory->fetchSignatory('MasterWorker', $object->id, 'preventionplan');
                if (is_array($masterWorkers) && !empty($masterWorkers)) {
                    $masterWorker                 = array_shift($masterWorkers);
                    $masterWorker->signature      = $savedSignature;
                    $masterWorker->signature_date = dol_now();
                    if ($masterWorker->update($user, true) > 0) {
                        $masterWorker->setSigned($user, true);
                    }
                }

                // Le document est genere avant le mail : son destinataire arrive sur une page de
                // signature qui doit deja presenter le plan, et la diffusion peut etre ouverte dans
                // la foulee. Les etapes ci-dessus sont faites sans trigger, l'appel est donc force.
                digiriskRefreshPreventionPlanDocument($db, (int) $object->id, $user, $langs, true);

                // Ask the exterior responsible to sign by email (email-only, no SMS configured).
                // L'echec n'annule pas la creation : l'ecran de succes affiche l'etat de l'envoi et
                // permet de renvoyer le lien ou de faire signer sur le telephone.
                $extSignatories = $signatory->fetchSignatory('ExtSocietyResponsible', $object->id, 'preventionplan');
                if (is_array($extSignatories) && !empty($extSignatories)) {
                    $extSignatory = array_shift($extSignatories);
                    $mailResult   = digiriskSendPreventionPlanSignatureEmail($db, $object, $extSignatory, $thirdparty->name, $user, $langs);
                    if (!$mailResult['sent']) {
                        setEventMessages($langs->trans('MobilePPWarningEmailNotSentDetail', $mailResult['error']), null, 'warnings');
                    }
                }

                $db->commit();
                $redirect = $_SERVER['PHP_SELF'] . '?created=' . $object->id;
                setEventMessages($langs->trans('MobilePPCreated', $object->ref), null, 'mesgs');
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
                $msg = (dol_strlen($object->error) && $object->error !== 'KO') ? $object->error : $langs->trans('MobilePPErrorCreatingPlan');
                dol_syslog('preventionplan_mobile_create: object->create failed: ' . $object->error . ' | ' . implode(', ', (array) $object->errors), LOG_ERR);
                $errorMessages[] = $msg;
                if (!$isAjax) {
                    setEventMessages($msg, null, 'errors');
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
 * Renvoi du lien de signature a l'entreprise exterieure depuis l'ecran de succes.
 *
 * L'envoi automatique de la creation peut avoir echoue, ou le destinataire ne jamais l'avoir recu :
 * sans ce renvoi il faudrait repasser par Dolibarr, alors que la personne est encore sur place.
 */
if ($action == 'resend_ext_signature_email' && $permissiontoadd) {
    ob_start();

    $planId       = GETPOSTINT('plan_id');
    $resendPlan   = new PreventionPlan($db);
    $resendResult = ['sent' => false, 'error' => $langs->trans('ErrorRecordNotFound'), 'email' => ''];

    if ($planId > 0 && $resendPlan->fetch($planId) > 0) {
        $extSignatories = $signatory->fetchSignatory('ExtSocietyResponsible', $resendPlan->id, 'preventionplan');
        if (is_array($extSignatories) && !empty($extSignatories)) {
            $extSignatory = array_shift($extSignatories);
            $extSociety   = $digiriskresources->fetchResourcesFromObject('ExtSociety', $resendPlan);
            $societyName  = '';
            if (!empty($extSociety->id) && $thirdparty->fetch($extSociety->id) > 0) {
                $societyName = $thirdparty->name;
            }

            $resendResult = digiriskSendPreventionPlanSignatureEmail($db, $resendPlan, $extSignatory, $societyName, $user, $langs);
        } else {
            $resendResult['error'] = $langs->trans('MobilePPWarningNoRecipientEmail');
        }
    }

    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $resendResult['sent'],
        'message' => $resendResult['sent'] ? $langs->trans('MobilePPSignatureEmailSentTo', $resendResult['email']) : $langs->trans('MobilePPWarningEmailNotSentDetail', $resendResult['error']),
    ]);
    exit;
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
    require_once __DIR__ . '/../../core/tpl/frontend/preventionplan_mobile_success.tpl.php';
} else {
    // Creation form screen
    $savedSignature = digiriskGetUserElectronicSignature($db, $user->id);
    require_once __DIR__ . '/../../core/tpl/frontend/preventionplan_mobile_form.tpl.php';
}

// Application navigation, so this screen is not a dead end when reached from the PWA
require_once __DIR__ . '/../../core/tpl/frontend/digiriskdolibarr_pwa_bottom_nav.tpl.php';

llxFooter();
$db->close();
