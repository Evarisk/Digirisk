<?php
/* Copyright (C) 2021-2025 EVARISK <technique@evarisk.com>
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
 * \file    view/digiriskstandard/digiriskstandard_riskassessmentdocument.php
 * \ingroup digiriskdolibarr
 * \brief   Page to view riskassessmentdocument
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

// Load Saturne libraries
require_once __DIR__ . '/../../../saturne/class/saturnemail.class.php';

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../class/digiriskstandard.class.php';
require_once __DIR__ . '/../../class/digiriskresources.class.php';
require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/digiriskdolibarrdocuments/groupmentdocument.class.php';
require_once __DIR__ . '/../../class/digiriskdolibarrdocuments/workunitdocument.class.php';
require_once __DIR__ . '/../../class/digiriskdolibarrdocuments/riskassessmentdocument.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskstandard.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id        = GETPOSTINT('id');
$action    = GETPOST('action', 'aZ09');
$subaction = GETPOST('subaction', 'aZ09');

// Initialize technical objects
$object            = new DigiriskStandard($db);
$document          = new RiskAssessmentDocument($db);
$saturneMail       = new SaturneMail($db, $object->module, $object->element);
$digiriskResources = new DigiriskResources($db);
$digiriskelement   = new DigiriskElement($db);
$thirdparty        = new Societe($db);

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks([$object->element . $document->element, $object->element . 'view', 'globalcard']); // Note that conf->hooks_modules contains array

if (empty($action) && empty($id)) {
    $action = 'view';
}

// Load object
require_once DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once
$object->fk_project = getDolGlobalInt('DIGIRISKDOLIBARR_DU_PROJECT');

// Load resources
$allLinks = $digiriskResources->fetchDigiriskResources();

// Load saturne mail models
$saturneMail->fetch(0, '', ' AND t.entity = 0 AND t.type_template = \'' . $document->element . '\' AND t.active = 1');

$upload_dir = $conf->{$object->module}->multidir_output[$object->entity ?? 1];

// Security check - Protection if external user
$permissiontoread   = $user->hasRight($object->module, $object->element, 'read') && $user->hasRight($object->module, $document->element, 'read');
$permissiontoadd    = $user->hasRight($object->module, $document->element, 'write');
$permissiontodelete = $user->hasRight($object->module, $document->element, 'delete');
$permtoupload       = $user->hasRight('ecm', 'upload');
saturne_check_access($permissiontoread);

/*
 * Actions
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    if ($action == 'update' && $permissiontoadd) {
        $auditStartDate = GETPOST('AuditStartDate', 'none');
        $auditEndDate   = GETPOST('AuditEndDate', 'none');
        $recipient      = GETPOST('Recipient', 'array');
        $method         = GETPOST('Method', 'none');
        $sources        = GETPOST('Sources', 'none');
        $importantNote  = GETPOST('ImportantNote', 'none');

        if ( strlen($auditStartDate) ) {
            $auditStartDate = explode('/', $auditStartDate);
            $auditStartDate = $auditStartDate[2] . '-' . $auditStartDate[1] . '-' . $auditStartDate[0];
            dolibarr_set_const($db, "DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE", $auditStartDate, 'date', 0, '', $conf->entity);
        } else {
            if (isset($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE)) {
                dolibarr_del_const($db, "DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE", $conf->entity);
            }
        }

        if ( strlen($auditEndDate) ) {
            $auditEndDate = explode('/', $auditEndDate);
            $auditEndDate = $auditEndDate[2] . '-' . $auditEndDate[1] . '-' . $auditEndDate[0];
            dolibarr_set_const($db, "DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_END_DATE", $auditEndDate, 'date', 0, '', $conf->entity);
        } else {
            if (isset($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_END_DATE)) {
                dolibarr_del_const($db, "DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_END_DATE", $conf->entity);
            }
        }

        dolibarr_set_const($db, "DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_RECIPIENT", json_encode($recipient), 'chaine', 0, '', $conf->entity);

        dolibarr_set_const($db, "DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_METHOD", $method, 'chaine', 0, '', $conf->entity);
        dolibarr_set_const($db, "DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_SOURCES", $sources, 'chaine', 0, '', $conf->entity);
        dolibarr_set_const($db, "DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_IMPORTANT_NOTES", $importantNote, 'chaine', 0, '', $conf->entity);

        // Submit file
        if ( ! empty($conf->global->MAIN_UPLOAD_DOC)) {
            if ( ! empty($_FILES)) {
                if (is_array($_FILES['userfile']['tmp_name'])) $userfiles = $_FILES['userfile']['tmp_name'];
                else $userfiles                                           = array($_FILES['userfile']['tmp_name']);

                foreach ($userfiles as $key => $userfile) {
                    if (empty($_FILES['userfile']['tmp_name'][$key])) {
                        $error++;
                        if ($_FILES['userfile']['error'][$key] == 1 || $_FILES['userfile']['error'][$key] == 2) {
                            setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
                        }
                    }
                }

                if ( ! $error) {
                    $filedir = $upload_dir . '/riskassessmentdocument/siteplans/';
                    array_map('unlink', array_filter((array) glob($filedir . '*')));
                    if ( ! empty($filedir)) {
                        $result = digirisk_dol_add_file_process($filedir, 0, 1, 'userfile', '', null, '', 1, $object);
                    }
                }
            }
        }

        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
        exit;
    }

    // Action to build doc
    if (($action == 'builddoc' || GETPOST('forcebuilddoc')) && $permissiontoadd) {
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

        $previousRef = $object->ref;
        $object->ref = '';
        $moreparams['object'] = $object;
        $moreparams['user']   = $user;
        $moreparams['objectType'] = 'riskassessment';

        $result = $document->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);

        $object->ref = $previousRef;
        if ($result > 0 && getDolGlobalInt('DIGIRISKDOLIBARR_GENERATE_ARCHIVE_WITH_DIGIRISKELEMENT_DOCUMENTS')) {
            //Création du dossier à zipper
            $entity = ($conf->entity > 1) ? '/' . $conf->entity : '';

            $date        = dol_print_date(dol_now(), 'dayxcard');
            $nameSociety = str_replace(' ', '_', $conf->global->MAIN_INFO_SOCIETE_NOM);
            $nameSociety = preg_replace('/\./', '_', $nameSociety);
            $nameSociety = dol_sanitizeFileName($nameSociety);

            $pathToZip = DOL_DATA_ROOT . $entity . '/digiriskdolibarr/riskassessmentdocument/' . $date . '_'. $document->ref . '_' . $nameSociety;
            dol_mkdir($pathToZip);

            // Ajout du fichier au dossier à zipper
            $nameFile = $date . '_' . $document->ref . '_' . $nameSociety;
            $nameFile = str_replace(' ', '_', $nameFile);
            $nameFile = dol_sanitizeFileName($nameFile);


            copy(DOL_DATA_ROOT . $entity . '/digiriskdolibarr/riskassessmentdocument/' . $document->last_main_doc, $pathToZip . '/' . $nameFile . '.odt');
            $pathinfo = pathinfo($document->last_main_doc);
            if (file_exists(DOL_DATA_ROOT . $entity . '/digiriskdolibarr/riskassessmentdocument/' . $pathinfo['filename'] . '.pdf')) {
                copy(DOL_DATA_ROOT . $entity . '/digiriskdolibarr/riskassessmentdocument/' . $pathinfo['filename'] . '.pdf', $pathToZip . '/' . $nameFile . '.pdf');
            }

            $digiriskelementlist = $digiriskelement->fetchDigiriskElementFlat(0);

            if ( ! empty($digiriskelementlist) ) {
                foreach ($digiriskelementlist as $digiriskelementsingle) {
                    if ($digiriskelementsingle['object']->element_type == 'groupment') {
                        $digiriskelementdocument = new GroupmentDocument($db);
                    } elseif ($digiriskelementsingle['object']->element_type == 'workunit') {
                        $digiriskelementdocument = new WorkUnitDocument($db);
                    }
                    $subFolder = $digiriskelementdocument->element;

                    $moreparams['object']     = $digiriskelementsingle['object'];
                    $moreparams['objectType'] = $digiriskelementsingle['object']->element_type;

                    $digiriskelementdocumentmodel = 'DIGIRISKDOLIBARR_' . strtoupper($digiriskelementdocument->element) . '_DEFAULT_MODEL';
                    $digiriskelementdocumentmodelpath = 'DIGIRISKDOLIBARR_' . strtoupper($digiriskelementdocument->element) . '_ADDON_ODT_PATH';
                    $digiriskelementdocumentmodelpath = preg_replace('/DOL_DOCUMENT_ROOT/', DOL_DOCUMENT_ROOT, $conf->global->$digiriskelementdocumentmodelpath);
                    $templateName = preg_replace( '/_/','.' , $conf->global->$digiriskelementdocumentmodel);
                    $digiriskelementdocumentmodelfinal = $conf->global->$digiriskelementdocumentmodel . ':' . $digiriskelementdocumentmodelpath . 'template_' . $templateName;

                    $result = $digiriskelementdocument->generateDocument($digiriskelementdocumentmodelfinal, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);

                    // Ajout du fichier au dossier à zipper
                    $sourceFilePath = DOL_DATA_ROOT . $entity . '/digiriskdolibarr/' . $subFolder . '/' . $digiriskelementsingle['object']->ref . '/';
                    $nameFile       = $date . '_' . $document->ref . '_' . $digiriskelementsingle['object']->ref . '_' . $digiriskelementdocument->ref . '_' . $digiriskelementsingle['object']->label . '_' . $nameSociety;
                    $nameFile       = str_replace(' ', '_', $nameFile);
                    $nameFile       = dol_sanitizeFileName($nameFile);

                    copy($sourceFilePath . $digiriskelementdocument->last_main_doc, $pathToZip . '/' . $nameFile . '.odt');
                    $pathinfo = pathinfo($digiriskelementdocument->last_main_doc);
                    if (file_exists($sourceFilePath . $pathinfo['filename'] . '.pdf')) {
                        copy($sourceFilePath . $pathinfo['filename'] . '.pdf', $pathToZip . '/' . $nameFile . '.pdf');
                    }
                }

                // Get real path for our folder
                $rootPath = realpath($pathToZip);

                // Initialize archive object
                $zip = new ZipArchive();

                $zip->open($document->ref . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);

                // Create recursive directory iterator
                /** @var SplFileInfo[] $files */
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($rootPath),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($files as $name => $file) {
                    // Skip directories (they would be added automatically)
                    if ( ! $file->isDir()) {
                        // Get real and relative path for current file
                        $filePath     = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($rootPath) + 1);

                        // Add current file to archive
                        $zip->addFile($filePath, $relativePath);
                        $zip->setCompressionName($file, ZipArchive::CM_STORE);
                    }
                }

                // Zip archive will be created only after closing object
                $zip->close();

                //move archive to riskassessmentdocument folder
                rename(DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/view/digiriskstandard/' . $document->ref . '.zip', $pathToZip . '.zip');
            }
        }

        if ($result <= 0) {
            setEventMessages($document->error, $document->errors, 'errors');
            $action = '';
        } else {
            if (empty($donotredirect)) {
                setEventMessages($langs->trans("FileGenerated") . ' - ' . $document->last_main_doc, null);

                $urltoredirect = $_SERVER['REQUEST_URI'];
                $urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
                $urltoredirect = preg_replace('/action=builddoc&?/', '', $urltoredirect); // To avoid infinite loop
                if (preg_match('/forcebuilddoc=1/', $urltoredirect)) {
                    $urltoredirect = preg_replace('/forcebuilddoc=1&?/', '', $urltoredirect); // To avoid infinite loop
                    header('Location: ' . $urltoredirect . '#sendEmail');
                } else {
                    header('Location: ' . $urltoredirect . '#builddoc');
                }
                exit;
            }
        }
    }

    // Actions builddoc, forcebuilddoc, remove_file
    require_once __DIR__ . '/../../../saturne/core/tpl/documents/documents_action.tpl.php';

    // Action to generate pdf from odt file
    require_once __DIR__ . '/../../../saturne/core/tpl/documents/saturne_manual_pdf_generation_action.tpl.php';

    // Actions to send emails
    $triggersendname     = 'RISKASSESSMENTDOCUMENT_SENTBYMAIL';
    $mode                = 'emailfromthirdparty';
    $trackid             = 'riskassessment' . $object->id;
    $labourInspectorId = $allLinks['LabourInspectorSociety']->id[0];
    $thirdparty->fetch($labourInspectorId);
    $object->thirdparty = $thirdparty;
    include DOL_DOCUMENT_ROOT . '/core/actions_sendmails.inc.php';
}

/*
 * View
 */

$title    = $langs->trans('RiskAssessmentDocument');
$help_url = 'FR:Module_Digirisk#Impression_du_Document_Unique';

digirisk_header($title, $help_url);

// Part to show record
if ($object->id > 0) {
    saturne_get_fiche_head($object, 'standardRiskAssessmentDocument', $title);
    saturne_banner_tab($object, 'ref', 'none', 0, 'ref', 'ref', '', true);

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '" enctype="multipart/form-data">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="update">';

    print '<div class="fichecenter">';
    print '<table class="border centpercent tableforfield">';

//    $recipients   = [];
//    $recipientIds = json_decode(getDolGlobalString('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_RECIPIENT'));
//    if (is_array($recipientIds) && !empty($recipientIds)) {
//        foreach ($recipientIds as $recipientId) {
//            $userTmp->fetch($recipientId);
//            $recipients[] = $userTmp->getFullName($langs);
//        }
//    } elseif (is_int($recipientIds) && !empty($recipientIds)) {
//        $userTmp->fetch($recipientIds);
//        $recipients[] = $userTmp->getFullName($langs);
//    }
//
//    $typeOfDataForRecipient = 'select;';
//    foreach ($recipients as $key => $recipient) {
//        $typeOfDataForRecipient .= $key . ':' . $recipient . ';';
//    }
//    $typeOfDataForRecipient = rtrim($typeOfDataForRecipient, ';');

//    $fields = [
//        'audit_start_date' => ['type' => 'day',                                          'label' => 'AuditStartDate', 'enabled' => 1, 'position' => 81, 'notnull' => 1, 'visible' => 1, 'alwayseditable' => $permissiontoadd],
//        'audit_end_date'   => ['type' => 'day',                                          'label' => 'AuditEndDate',   'enabled' => 1, 'position' => 82, 'notnull' => 1, 'visible' => 1, 'alwayseditable' => $permissiontoadd],
//        'recipient'        => ['type' => $typeOfDataForRecipient,                        'label' => 'Recipient',      'enabled' => 1, 'position' => 83, 'notnull' => 1, 'visible' => 1, 'alwayseditable' => $permissiontoadd],
//        'method'           => ['type' => 'ckeditor:dolibarr_notes:100%:200::1:12:95%:0', 'label' => 'Method',         'enabled' => 1, 'position' => 84, 'notnull' => 0, 'visible' => 1, 'alwayseditable' => $permissiontoadd],
//        'sources'          => ['type' => 'ckeditor:dolibarr_notes:100%:200::1:12:95%:0', 'label' => 'Sources',        'enabled' => 1, 'position' => 85, 'notnull' => 0, 'visible' => 1, 'alwayseditable' => $permissiontoadd],
//        'important_notes'  => ['type' => 'ckeditor:dolibarr_notes:100%:200::1:12:95%:0', 'label' => 'ImportantNote',  'enabled' => 1, 'position' => 86, 'notnull' => 0, 'visible' => 1, 'alwayseditable' => $permissiontoadd]
//    ];
//
//    $object->fields           = array_merge($object->fields, $fields);
//    $object->audit_start_date = getDolGlobalString('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE');
//    $object->audit_end_date   = getDolGlobalString('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_END_DATE');
//    $object->method           = getDolGlobalString('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_METHOD');
//    $object->sources          = getDolGlobalString('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_SOURCES');
//    $object->important_notes  = getDolGlobalString('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_IMPORTANT_NOTES');
//    require_once DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

    //JSON Decode and show fields
    require_once __DIR__ . '/../../core/tpl/digiriskdocuments/digiriskdolibarr_riskassessmentdocumentfields_view.tpl.php';

    print '</table>';
    print '</div>';

    print dol_get_fiche_end();

    // Buttons for actions
    if ($action != 'presend') {
        print '<div class="tabsAction">';
        $parameters = [];
        $resHook    = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        if ($resHook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($reshook) && $permissiontoadd) {
            if ($action == 'edit') {
                print '<input type="submit" class="butAction" name="save" value="' . $langs->trans('Save') . '">';
            } else {
                print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit');
            }

            $active = isset($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE) && strlen($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE);
            if ($active) {
                print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&token=' .newToken() . '&mode=init&modelmailselected=' . $saturneMail->id . '#formmailbeforetitle');
            } else {
                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('SetStartEndDateBeforeSendEmail')) . '">' . $langs->trans('SendEmail') . '</span>';
            }
        }
        print '</div>';
    }

    print '</form>';

    if ($action != 'presend') {
        print '<div class="fichecenter"><div class="fichehalfleft">';
        $fileDir   = $upload_dir . '/' . $document->element;
        $urlSource =  $_SERVER['PHP_SELF'] .'?id=' . $object->id;

        print saturne_show_documents($object->module . ':RiskAssessmentDocument', $document->element, $fileDir, $urlSource, $permissiontoadd, $permissiontodelete, '', 1, 0, 0, 0, '', '', '', $langs->defaultlang, '', $object);

        print '</div><div class="fichehalfright">';

        $moreHtmlCenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/saturne/view/saturne_agenda.php', 1) . '?id=' . $object->id . '&module_name=DigiriskDolibarr&object_type=' . $object->element . '&show_nav=0&handle_photo=true');

        // List of actions on element
        require_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
        $formActions = new FormActions($db);
        $formActions->showactions($object, $object->element . '@' . $object->module, 0, 1, '', 10, '', $moreHtmlCenter);

        print '</div></div>';
    }

    // Select mail models is same action as presend
    if (GETPOST('modelselected', 'alpha')) {
        $action = 'presend';
    }

    // Presend form
    $modelmail        = $document->element;
    $defaulttopiclang = $object->module;
    $defaulttopic     = 'Information';
    $diroutput        = $upload_dir . '/' . $document->element;
    $trackid          = $object->element . $document->element . $object->id;

    // Need to set object ref to empty string because we don't have directory for this object
    $object->ref = '';

    $thirdparty->fetch($allLinks['LabourInspectorSociety']->id[0]);
    $object->thirdparty = $thirdparty;

    require_once DOL_DOCUMENT_ROOT . '/core/tpl/card_presend.tpl.php';
}

// End of page
llxFooter();
$db->close();
