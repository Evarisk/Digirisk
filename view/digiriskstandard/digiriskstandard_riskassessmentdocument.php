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
 *   	\file       view/digiriskstandard/digiriskstandard_riskassessmentdocument.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to view riskassessmentdocument
 */


// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

require_once __DIR__ . '/../../class/digiriskresources.class.php';
require_once __DIR__ . '/../../class/digiriskstandard.class.php';
require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/digiriskdolibarrdocuments/groupmentdocument.class.php';
require_once __DIR__ . '/../../class/digiriskdolibarrdocuments/workunitdocument.class.php';
require_once __DIR__ . '/../../class/digiriskdolibarrdocuments/riskassessmentdocument.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskstandard.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

require_once __DIR__ . '/../../../saturne/core/modules/saturne/modules_saturne.php';
global $db, $conf, $langs, $hookmanager, $user;

// Load translation files required by the page
saturne_load_langs(['other']);

// Get parameters
$action    = GETPOST('action', 'aZ09');
$subaction = GETPOST('subaction', 'aZ09');
$id        = GETPOST('id', 'integer');

// Initialize technical objects
$object            = new DigiriskStandard($db);
$digiriskelement   = new DigiriskElement($db);
$document          = new RiskAssessmentDocument($db);
$digiriskresources = new DigiriskResources($db);
$thirdparty        = new Societe($db);
$contact           = new Contact($db);
$project           = new Project($db);

$hookmanager->initHooks(array('digiriskelementriskassessmentdocument', 'digiriskstandardview', 'globalcard')); // Note that conf->hooks_modules contains array

$object->fetch($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD);

// Load resources
$allLinks = $digiriskresources->fetchDigiriskResources();

$upload_dir = $conf->digiriskdolibarr->multidir_output[$conf->entity ?? 1];

// Security check - Protection if external user
$permissiontoread   = $user->rights->digiriskdolibarr->digiriskstandard->read && $user->rights->digiriskdolibarr->riskassessmentdocument->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->riskassessmentdocument->write;
$permissiontodelete = $user->rights->digiriskdolibarr->riskassessmentdocument->delete;
$permtoupload       = $user->rights->ecm->upload;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

$parameters = array();
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
    $error = 0;

    if (($action == 'update' && ! GETPOST("cancel", 'alpha')) || ($action == 'updateedit') && $permissiontoadd) {
        $auditStartDate = GETPOST('AuditStartDate', 'none');
        $auditEndDate   = GETPOST('AuditEndDate', 'none');
        $recipient       = GETPOST('Recipient', 'array');
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

        if ($action != 'updateedit' && ! $error) {
            header("Location: " . $_SERVER["PHP_SELF"]);
            exit;
        }
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

        if ($result > 0) {
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

            if ($conf->global->DIGIRISKDOLIBARR_GENERATE_ARCHIVE_WITH_DIGIRISKELEMENT_DOCUMENTS) {
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

                        // Ajout du fichier au dossier à zipper
                        $sourceFilePath = DOL_DATA_ROOT . $entity . '/digiriskdolibarr/' . $subFolder . '/' . $digiriskelementsingle['object']->ref . '/';
                        $nameFile       = $date . '_' . $document->ref . '_' . $digiriskelementsingle['object']->ref . '_' . $digiriskelementdocument->ref . '_' . $digiriskelementsingle['object']->label . '_' . $nameSociety;
                        $nameFile       = str_replace(' ', '_', $nameFile);
                        $nameFile       = dol_sanitizeFileName($nameFile);

                        $digiriskelementdocumentGenerated = $digiriskelementdocument->fetchAll('DESC','date_creation',1,0,['customsql' => 'parent_type="'. $digiriskelementsingle['object']->element_type .'" AND parent_id=' . $digiriskelementsingle['object']->id],'','');
                        $digiriskelementdocument          = array_shift($digiriskelementdocumentGenerated);

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
                        }
                    }

                    // Zip archive will be created only after closing object
                    $zip->close();

                    //move archive to riskassessmentdocument folder
                    rename(DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/view/digiriskstandard/' . $document->ref . '.zip', $pathToZip . '.zip');
                }
            }

        }

        if ($result <= 0) {
            setEventMessages($object->error, $object->errors, 'errors');
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

    // Actions builddoc, forcebuilddoc, remove_file.
    require_once __DIR__ . '/../../../saturne/core/tpl/documents/documents_action.tpl.php';

    // Action to generate pdf from odt file
    require_once __DIR__ . '/../../../saturne/core/tpl/documents/saturne_manual_pdf_generation_action.tpl.php';

    // Actions to send emails
    $triggersendname     = 'RISKASSESSMENTDOCUMENT_SENTBYMAIL';
    $mode                = 'emailfromthirdparty';
    $trackid             = 'riskassessment' . $object->id;
    $labour_inspector    = $allLinks['LabourInspectorSociety'];
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
print '<div id="cardContent">';

$res  = $object->fetch_optionals();

saturne_get_fiche_head($object, 'standardRiskAssessmentDocument', $title);

// Object card
// ------------------------------------------------------------
// Project
$morehtmlref = '<div class="refidno">';
$project->fetch($conf->global->DIGIRISKDOLIBARR_DU_PROJECT);
$morehtmlref .= $langs->trans('Project') . ' : ' . getNomUrlProject($project, 1, 'blank', 1);
$morehtmlref .= '</div>';

$moduleNameLowerCase = 'mycompany';
saturne_banner_tab($object,'ref','none', 0, 'ref', 'ref', $morehtmlref, true);
$moduleNameLowerCase = 'digiriskdolibarr';

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" name="edit" enctype="multipart/form-data">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';

print '<a href="../../admin/socialconf.php" target="_blank">' . $langs->trans('ConfigureSecurityAndSocialData') . ' <i class="fas fa-external-link-alt"></i></a>';
print '<hr>';
print '<div class="fichecenter">';
print '<table class="border centpercent tableforfield">' . "\n";

//JSON Decode and show fields
require_once __DIR__ . '/../../core/tpl/digiriskdocuments/digiriskdolibarr_riskassessmentdocumentfields_view.tpl.php';

print '</table>';
print '</div>';

// Buttons for actions
print '<div class="tabsAction" >' . "\n";
$parameters = array();
$reshook    = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
    // Modify
    if ($permissiontoadd) {
        if ( $action == 'edit' ) {
            print '<input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
        } else {
            print '<a class="butAction" id="actionButtonEdit" href="' . $_SERVER["PHP_SELF"] . '?action=edit">' . $langs->trans("Modify") . '</a>';

            $active = isset($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE) && strlen($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE);
            if ($active) {
                $dir_files = 'riskassessmentdocument';
                $filedir   = $upload_dir . '/' . $dir_files;
                $files     = dol_dir_list($filedir);
                $empty     = 1;

                foreach ($files as $file) {
                    if ($file['type'] != 'dir') {
                        $empty = 0;
                    }
                }

                if ($empty == 0) {
                    print '<a class="butAction" id="actionButtonSendMail" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&mode=init&sendto=' . $allLinks['LabourInspectorSociety']->id[0] . '#sendEmail' . '">' . $langs->trans('SendMail') . '</a>';
                } else {
                    // Model
                    $class     = 'SaturneDocumentModel';
                    $modellist = call_user_func($class . '::liste_modeles', $db, 'riskassessmentdocument');

                    if ( ! empty($modellist)) {
                        asort($modellist);
                        $modellist = array_filter($modellist, 'saturne_remove_index');
                        if (is_array($modellist) && count($modellist) == 1) {    // If there is only one element
                            $arraykeys                = array_keys($modellist);
                            $arrayvalues              = preg_replace('/template_/', '', array_values($modellist)[0]);
                            $modellist[$arraykeys[0]] = $arrayvalues;
                            $modelselected            = $arraykeys[0];
                        }
                    }
                    if (dol_strlen($modelselected) > 0) {
                        print '<a class="butAction send-risk-assessment-document-by-mail" id="actionButtonSendMail"  href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&forcebuilddoc=1&model=' . $modelselected . '&mode=init&sendto=' . $allLinks['LabourInspectorSociety']->id[0] . '#sendEmail' . '">' . $langs->trans('SendMail') . '</a>';
                    } else {
                        print '<a class="butAction send-risk-assessment-document-by-mail" id="actionButtonSendMail"  href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&mode=init&sendto=' . $allLinks['LabourInspectorSociety']->id[0] . '#sendEmail' . '">' . $langs->trans('SendMail') . '</a>';
                    }
                }
            } else {
                print '<span class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("SetStartEndDateBeforeSendEmail")) . '">' . $langs->trans('SendEmail') . '</span>';
            }
        }
    } else {
        if ( $action == 'edit' ) {
            print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('Save') . '</a>';
        } else {
            print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('Modify') . '</a>';
            print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('SendEmail') . '</a>';
        }
    }
}

print '</div>';
print '</form>';

print dol_get_fiche_end();

print '<div class="riskassessmentdocument-generation">';
if ($action != 'presend') {
    $dirFiles  = 'riskassessmentdocument';
    $fileDir   = $upload_dir . '/' . $dirFiles;
    $urlSource = $_SERVER['PHP_SELF'];

    print saturne_show_documents('digiriskdolibarr:RiskAssessmentDocument', $dirFiles, $fileDir, $urlSource, $permissiontoadd, $permissiontodelete, '', 1, 0, 0, 0, 0, '', '', $langs->defaultlang, '', $object);
}
print '</div>';

// Presend form
$labour_inspector    = $allLinks['LabourInspectorSociety'];
$labourInspectorId = $allLinks['LabourInspectorSociety']->id[0];
$thirdparty->fetch($labourInspectorId);
$object->thirdparty = $thirdparty;

$modelmail    = 'riskassessmentdocument';
$defaulttopic = 'Information';
$diroutput    = $upload_dir . '/riskassessmentdocument';
$filter       = array('customsql' => "t.type='riskassessmentdocument'");
$document     = $document->fetchAll('desc', 't.rowid', 1, 0, $filter);

if (!empty($document) && is_array($document)) {
    $document = array_shift($document);
    $ref      = dol_sanitizeFileName($document->ref);
}
$trackid = 'riskassessment' . $object->id;

// Select mail models is same action as presend
if (GETPOST('modelselected', 'alpha')) {
    $action = 'presend';
}
if ($action == 'presend') {
    $langs->load("mails");

    $titreform = 'SendMail';

    $object->fetch_projet();

    if (!in_array($object->element, array('societe', 'user', 'member'))) {
        include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
        $fileparams       = dol_dir_list($diroutput, 'files', 0, '', [], 'date', 'SORT_DESC');
        $lastRef          = pathinfo($fileparams[0]['name']);
        foreach ($fileparams as $fileparam) {
            preg_match('/' . $lastRef['filename'] . '/', $fileparam['name']) ? $filevalue[] = $fileparam['fullname'] : 0;
        }
    }

    // Define output language
    $outputlangs = $langs;
    $newlang     = '';
    if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) {
        $newlang = $_REQUEST['lang_id'];
    }
    if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
        $newlang = $object->thirdparty->default_lang;
    }

    if ( ! empty($newlang)) {
        $outputlangs = new Translate('', $conf);
        $outputlangs->setDefaultLang($newlang);
        // Load traductions files required by page
        $outputlangs->loadLangs(array('digiriskdolibarr'));
    }

    $topicmail = '';
    if (empty($object->ref_client)) {
        $topicmail = $outputlangs->trans($defaulttopic, '__REF__');
    } elseif ( ! empty($object->ref_client)) {
        $topicmail = $outputlangs->trans($defaulttopic, '__REF__ (__REFCLIENT__)');
    }

    print load_fiche_titre($langs->trans($titreform), '', 'digiriskdolibarr_color@digiriskdolibarr', '', 'sendEmail');

    print dol_get_fiche_head('');

    // Create form for email
    include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
    $formmail                       = new FormMail($db);
    $formmail->param['langsmodels'] = (empty($newlang) ? $langs->defaultlang : $newlang);
    $formmail->fromtype             = (GETPOST('fromtype') ? GETPOST('fromtype') : ( ! empty($conf->global->MAIN_MAIL_DEFAULT_FROMTYPE) ? $conf->global->MAIN_MAIL_DEFAULT_FROMTYPE : 'user'));
    $formmail->fromid               = $user->id;
    $formmail->trackid              = $trackid;
    $formmail->fromname             = $user->firstname . ' ' . $user->lastname;
    $formmail->frommail             = $user->email;
    $formmail->fromalsorobot        = 1;
    $formmail->withfrom             = 1;

    // Fill list of recipient with email inside <>.
    $liste = array();

    $labourInspectorContact = $allLinks['LabourInspectorContact'];

    if ( ! empty($object->socid) && $object->socid > 0 && ! is_object($object->thirdparty) && method_exists($object, 'fetch_thirdparty')) {
        $object->fetch_thirdparty();
    }
    if (is_object($object->thirdparty)) {
        foreach ($object->thirdparty->thirdparty_and_contact_email_array(1) as $key => $value) {
            $liste[$key] = $value;
        }
    }

    if ( ! empty($conf->global->MAIN_MAIL_ENABLED_USER_DEST_SELECT)) {
        $listeuser = array();
        $fuserdest = new User($db);

        $result = $fuserdest->fetchAll('ASC', 't.lastname', 0, 0, array('customsql' => 't.statut=1 AND t.employee=1 AND t.email IS NOT NULL AND t.email<>\'\''), 'AND', true);
        if ($result > 0 && is_array($fuserdest->users) && count($fuserdest->users) > 0) {
            foreach ($fuserdest->users as $uuserdest) {
                $listeuser[$uuserdest->id] = $uuserdest->user_get_property($uuserdest->id, 'email');
            }
        } elseif ($result < 0) {
            setEventMessages(null, $fuserdest->errors, 'errors');
        }
        if (count($listeuser) > 0) {
            $formmail->withtouser   = $listeuser;
            $formmail->withtoccuser = $listeuser;
        }
    }


    $Labour_inspector_contact_id = $allLinks['LabourInspectorContact']->id[0];
    $contact->fetch($Labour_inspector_contact_id);
    $withto = array( $allLinks['LabourInspectorContact']->id[0] => $contact->firstname . ' ' . $contact->lastname . " <" . $contact->email . ">");

    $formmail->withto              = $withto;
    $formmail->withtofree          = (GETPOSTISSET('sendto') ? (GETPOST('sendto', 'alphawithlgt') ? GETPOST('sendto', 'alphawithlgt') : '1') : '1');
    $formmail->withtocc            = $liste;
    $formmail->withtoccc           = $conf->global->MAIN_EMAIL_USECCC;
    $formmail->withtopic           = $topicmail;
    $formmail->withfile            = 2;
    $formmail->withbody            = 1;
    $formmail->withdeliveryreceipt = 1;
    $formmail->withcancel          = 1;

    //$arrayoffamiliestoexclude=array('system', 'mycompany', 'object', 'objectamount', 'date', 'user', ...);
    if ( ! isset($arrayoffamiliestoexclude)) $arrayoffamiliestoexclude = null;

    // Make substitution in email content
    $substitutionarray                       = getCommonSubstitutionArray($outputlangs, 0, $arrayoffamiliestoexclude, $object);
    $substitutionarray['__CHECK_READ__']     = (is_object($object) && is_object($object->thirdparty)) ? '<img src="' . DOL_MAIN_URL_ROOT . '/public/emailing/mailing-read.php?tag=' . $object->thirdparty->tag . '&securitykey=' . urlencode($conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY) . '" width="1" height="1" style="width:1px;height:1px" border="0"/>' : '';
    $substitutionarray['__CONTACTCIVNAME__'] = '';
    $substitutionarray['__REF__']            = $ref;
    $parameters                              = array(
        'mode' => 'formemail'
    );
    complete_substitutions_array($substitutionarray, $outputlangs, $object, $parameters);

    // Find the good contact address
    $tmpobject = $object;

    $contactarr = array();
    $contactarr = $tmpobject->liste_contact(-1, 'external');

    if (is_array($contactarr) && count($contactarr) > 0) {
        require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
        $contactstatic = new Contact($db);

        foreach ($contactarr as $contact) {
            $contactstatic->fetch($contact['id']);
            $substitutionarray['__CONTACT_NAME_' . $contact['code'] . '__'] = $contactstatic->getFullName($outputlangs, 1);
        }
    }

    // Array of substitutions
    $formmail->substit = $substitutionarray;

    // Array of other parameters
    $formmail->param['action']    = 'send';
    $formmail->param['models']    = $modelmail;
    $formmail->param['models_id'] = GETPOST('modelmailselected', 'int');
    $formmail->param['id']        = $object->id;
    $formmail->param['returnurl'] = $_SERVER["PHP_SELF"] . '?id=' . $object->id;
    $formmail->param['fileinit']  = $filevalue;

    // Show form
    print $formmail->get_form();

    print dol_get_fiche_end();
}

$saturneDocumentModel        = new SaturneDocumentModel($db, 'digiriskdolibarr', 'riskassessmentdocument');
$documentType                = strtolower('riskassessmentdocument');
$modellist                   = $saturneDocumentModel->liste_modeles($db, $documentType);
$riskassessmentDocumentModel = array_keys($modellist)[1];

$groupmentUrl              = DOL_URL_ROOT . '/custom/digiriskdolibarr/view/digiriskelement/digiriskelement_card.php?forcebuilddoc=1';
$riskAssessmentDocumentUrl = DOL_URL_ROOT . '/custom/digiriskdolibarr/view/digiriskstandard/digiriskstandard_riskassessmentdocument.php?action=builddoc&model='.$riskassessmentDocumentModel;
$documentGeneratedText     = $langs->trans('DocumentGenerated');

$digiriskElementList = $digiriskelement->getActiveDigiriskElements();
$digiriskElementIds  = '';

if (is_array($digiriskElementList) && !empty($digiriskElementList)) {
    foreach($digiriskElementList as $digiriskElementId => $digiriskElementSingle) {
        $digiriskElementIds .= $digiriskElementId . ',';
    }
}
print '<input hidden id="groupmentUrl" value="' . $groupmentUrl . '">';
print '<input hidden id="riskAssessmentDocumentUrl" value="' . $riskAssessmentDocumentUrl . '">';
print '<input hidden id="digiriskElementIds" value="' . $digiriskElementIds . '">';
print '<input hidden id="documentGeneratedText" value="' . $documentGeneratedText . '">';

?>
    <div id="generationModal" class="wpeo-modal">
        <div class="modal-container">
            <div class="modal-header">
                <h2><?php echo $langs->trans('RiskAssessmentDocumentGenerated')?></h2>
            </div>
            <div id="progressbar">
                <div class="ui-progressbar-value"></div>
            </div>
            <div class="modal-content">
                <ul id="generationStatus">
                </ul>
            </div>
        </div>
    </div>
<?php

// End of page
llxFooter();
$db->close();
