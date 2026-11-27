<?php
/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
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
 * \file    view/digiriskstandard/digiriskstandard_risklibrary.php
 * \ingroup digiriskdolibarr
 * \brief   Risk library of the company (#5644): the models of risks, then the grid of the GP/UT and the models where
 *          each GP/UT is qualified (concerned, not concerned, to qualify). A GP/UT concerned gets its own risk, whose
 *          current cotation the grid shows: the grid is the summary of where each risk of the library is assigned.
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
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../class/digiriskstandard.class.php';
require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../class/riskanalysis/riskassessment.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskstandard.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_risklibrary.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $moduleNameLowerCase, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action    = GETPOST('action', 'aZ09');
$confirm   = GETPOST('confirm', 'alpha');
$modelId   = GETPOSTINT('modelid');
$elementId = GETPOSTINT('elementid');

// Initialize technical objects
$object          = new DigiriskStandard($db);
$digiriskElement = new DigiriskElement($db);
$project         = new Project($db);
$risk            = new Risk($db);
$evaluation      = new RiskAssessment($db);

$hookmanager->initHooks(['digiriskstandardrisklibrary', 'digiriskstandardview', 'globalcard']); // Note that conf->hooks_modules contains array

// Load object
$object->fetch(getDolGlobalInt('DIGIRISKDOLIBARR_ACTIVE_STANDARD'));

// Security check - The library makes risks in the GP/UT: the rights of the risks apply
$permissiontoread = $user->hasRight('digiriskdolibarr', 'digiriskstandard', 'read') && $user->hasRight('digiriskdolibarr', 'risk', 'read');
$permissiontoadd  = $user->hasRight('digiriskdolibarr', 'risk', 'write');

saturne_check_access($permissiontoread);

$pageUrl          = $_SERVER['PHP_SELF'];
$models           = digiriskdolibarr_risklibrary_fetch_all($db);
$dangerCategories = [];
foreach (Risk::getDangerCategories() as $dangerCategory) {
    $dangerCategories[(int) $dangerCategory['position']] = $dangerCategory;
}
$cotationLevels = digiriskdolibarr_risklibrary_cotations();

$activeDigiriskElements = $digiriskElement->getActiveDigiriskElements();
$activeDigiriskElements = is_array($activeDigiriskElements) ? $activeDigiriskElements : [];

/*
 * Actions
 */

$parameters = [];
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
    // Excel export of the grid, with the right to read the tab: its cells as on the page, then the models
    if ($action === 'export_xlsx' && !empty($models)) {
        require_once __DIR__ . '/../../class/digiriskrisklibraryexport.class.php';

        $export = new DigiriskRiskLibraryExport($db);
        $export->export($models, digiriskdolibarr_risklibrary_fetch_scopes($db), $activeDigiriskElements, $dangerCategories, $langs->transnoentities('RiskLibrary') . '_' . dol_print_date(dol_now(), 'dayhourlog'));
        exit;
    }

    // Create or update a model: a label and a danger category are required, the cotation is one of the scale
    if (in_array($action, ['addModel', 'updateModel'], true) && $permissiontoadd && !GETPOST('cancel', 'alpha')) {
        $label    = trim(GETPOST('label', 'alphanohtml'));
        $category = GETPOST('category', 'alpha');
        $cotation = GETPOSTINT('cotation');
        if ($label === '') {
            setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Label')), [], 'errors');
            $action = $action === 'addModel' ? 'create' : 'edit';
        } elseif ($category === '' || !isset($dangerCategories[(int) $category])) {
            setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('RiskCategory')), [], 'errors');
            $action = $action === 'addModel' ? 'create' : 'edit';
        } elseif (!isset($cotationLevels[$cotation])) {
            setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('RiskLibraryCotation')), [], 'errors');
            $action = $action === 'addModel' ? 'create' : 'edit';
        } else {
            $savedModelId = $action === 'updateModel' && isset($models[$modelId]) ? $modelId : 0;
            $result       = digiriskdolibarr_risklibrary_save($db, $user, $savedModelId, $label, (int) $category, GETPOST('description', 'alphanohtml'), $cotation);
            if ($result > 0) {
                setEventMessages($langs->trans($savedModelId > 0 ? 'RiskLibraryModelUpdated' : 'RiskLibraryModelCreated', $label), []);
                header('Location: ' . $pageUrl);
                exit;
            }
            setEventMessages($db->lasterror(), [], 'errors');
        }
    }

    if ($action === 'confirm_deleteModel' && $confirm === 'yes' && $permissiontoadd && isset($models[$modelId])) {
        if (digiriskdolibarr_risklibrary_delete($db, $modelId) > 0) {
            setEventMessages($langs->trans('RiskLibraryModelDeleted', $models[$modelId]->label), []);
        } else {
            setEventMessages($db->lasterror(), [], 'errors');
        }
        header('Location: ' . $pageUrl);
        exit;
    }

    // Qualify a GP/UT for a model, from the grid: a GP/UT not concerned says why
    if ($action === 'setScope' && $permissiontoadd && isset($models[$modelId]) && isset($activeDigiriskElements[$elementId])) {
        $concerned = GETPOSTINT('concerned');
        $reason    = trim(GETPOST('reason', 'alphanohtml'));
        if (!in_array($concerned, [-1, 0, 1], true)) {
            $concerned = -1;
        }

        if ($concerned == 0 && $reason === '') {
            setEventMessages($langs->trans('RiskLibraryErrorReason'), [], 'errors');
        } else {
            $numberingModuleName = [
                'riskanalysis/' . $risk->element       => getDolGlobalString('DIGIRISKDOLIBARR_RISK_ADDON'),
                'riskanalysis/' . $evaluation->element => getDolGlobalString('DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON'),
            ];
            list($refRiskMod, $refEvaluationMod) = saturne_require_objects_mod($numberingModuleName, $moduleNameLowerCase);

            if (digiriskdolibarr_risklibrary_set_scope($db, $user, $models[$modelId], $elementId, $concerned, $reason, $refRiskMod, $refEvaluationMod) > 0) {
                setEventMessages($langs->trans('RiskLibraryScopeSaved', $models[$modelId]->label, $activeDigiriskElements[$elementId]->ref), []);
            } elseif ($db->lasterror()) {
                setEventMessages($db->lasterror(), [], 'errors');
            }
        }
        header('Location: ' . $pageUrl . '#risklibrary-grid');
        exit;
    }
}

/*
 * View
 */

$title   = $langs->trans('RiskLibrary');
$helpUrl = 'FR:Module_Digirisk';

digirisk_header($title, $helpUrl);

$form = new Form($db);

// Confirmation to delete a model
if ($action === 'deleteModel' && isset($models[$modelId])) {
    print $form->formconfirm($pageUrl . '?modelid=' . $modelId, $langs->trans('RiskLibraryDeleteModel'), $langs->trans('RiskLibraryConfirmDeleteModel', $models[$modelId]->label), 'confirm_deleteModel', '', 'yes', 1);
}

saturne_get_fiche_head($object, 'standardRiskLibrary', $title);

// Project
$moreHtmlRef = '<div class="refidno">';
$project->fetch(getDolGlobalInt('DIGIRISKDOLIBARR_DU_PROJECT'));
$moreHtmlRef .= $langs->trans('Project') . ' : ' . getNomUrlProject($project, 1, 'blank', 1);
$moreHtmlRef .= '</div>';

$moduleNameLowerCase = 'mycompany';
saturne_banner_tab($object, 'ref', 'none', 0, 'ref', 'ref', $moreHtmlRef, true);
$moduleNameLowerCase = 'digiriskdolibarr';

print '<div class="fichecenter risklibrary">';
print '<div class="opacitymedium risklibrary-intro">' . $langs->trans('RiskLibraryIntro') . '</div>';

$scopes = digiriskdolibarr_risklibrary_fetch_scopes($db);

// Picto of a danger category, with its name as a tooltip
$categoryPicto = static function (int $category) use ($dangerCategories): string {
    if (!isset($dangerCategories[$category])) {
        return '';
    }

    return '<img class="risklibrary-picto" src="' . dol_buildpath('/digiriskdolibarr/img/categorieDangers/' . $dangerCategories[$category]['thumbnail_name'] . '.png', 1) . '" alt="" title="' . dol_escape_htmltag($dangerCategories[$category]['name']) . '">';
};

// Cotation of the scale, as the risk lists show it: a square of the colour of its level
$cotationBadge = static function (int $cotation, string $text, string $title = '') use ($risk): string {
    return '<span class="risk-evaluation-cotation risklibrary-cotation" data-scale="' . $risk->getCotationLevel($cotation) . '"' . ($title !== '' ? ' title="' . dol_escape_htmltag($title) . '"' : '') . '>' . $text . '</span>';
};
$standardCotations = digiriskdolibarr_risklibrary_standard_cotations();

/*
 * The models
 */

$addButton = dolGetButtonTitle($langs->trans('RiskLibraryNewModel'), '', 'fa fa-plus-circle', $pageUrl . '?action=create#risklibrary-form', '', $permissiontoadd ? 1 : 0);
print load_fiche_titre($langs->trans('RiskLibraryModels'), $addButton, 'fontawesome_fa-book_fas');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent risklibrary-list">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('RiskCategory') . '</td>';
print '<td>' . $langs->trans('Label') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $form->textwithpicto($langs->trans('RiskLibraryCotation'), $langs->trans('RiskLibraryCotationHelp')) . '</td>';
print '<td class="center">' . $langs->trans('RiskLibraryAssignment') . '</td>';
print '<td></td>';
print '</tr>';

// Form of a new model, or of the model being edited, in place of its row
$modelForm = static function (?stdClass $model) use ($langs, $pageUrl, $dangerCategories, $cotationLevels, $form): string {
    $categoryOptions = [];
    foreach ($dangerCategories as $position => $dangerCategory) {
        $categoryOptions[$position] = $dangerCategory['name'];
    }
    $cotationOptions = [];
    foreach ($cotationLevels as $cotation => $levelKey) {
        $cotationOptions[$cotation] = $langs->trans($levelKey);
    }

    $out  = '<tr class="oddeven risklibrary-form" id="risklibrary-form">';
    $out .= '<td colspan="6">';
    $out .= '<form method="POST" action="' . $pageUrl . '">';
    $out .= '<input type="hidden" name="token" value="' . newToken() . '">';
    $out .= '<input type="hidden" name="action" value="' . ($model ? 'updateModel' : 'addModel') . '">';
    if ($model) {
        $out .= '<input type="hidden" name="modelid" value="' . (int) $model->rowid . '">';
    }
    $out .= '<div class="risklibrary-form__row">';
    $out .= '<input type="text" name="label" class="minwidth300" maxlength="255" required placeholder="' . dol_escape_htmltag($langs->trans('Label')) . '" value="' . dol_escape_htmltag(GETPOSTISSET('label') ? GETPOST('label', 'alphanohtml') : ($model->label ?? '')) . '">';
    $out .= $form->selectarray('category', $categoryOptions, GETPOSTISSET('category') ? GETPOST('category', 'alpha') : ($model->category ?? ''), $langs->trans('RiskCategory'), 0, 0, '', 0, 0, 0, '', 'minwidth300');
    $out .= $form->selectarray('cotation', $cotationOptions, GETPOSTISSET('cotation') ? GETPOSTINT('cotation') : ($model->cotation ?? 0), 0, 0, 0, '', 0, 0, 0, '', 'minwidth150');
    $out .= '</div>';
    $out .= '<textarea name="description" class="centpercent" rows="2" placeholder="' . dol_escape_htmltag($langs->trans('Description')) . '">' . dol_escape_htmltag(GETPOSTISSET('description') ? GETPOST('description', 'alphanohtml') : ($model->description ?? '')) . '</textarea>';
    $out .= '<div class="risklibrary-form__row">';
    $out .= '<input type="submit" class="button button-save smallpaddingimp" value="' . dol_escape_htmltag($langs->trans('Save')) . '">';
    $out .= '<a class="button button-cancel smallpaddingimp" href="' . $pageUrl . '">' . $langs->trans('Cancel') . '</a>';
    $out .= '</div>';
    $out .= '</form>';
    $out .= '</td>';
    $out .= '</tr>';

    return $out;
};

if (empty($models) && $action !== 'create') {
    print '<tr><td colspan="6"><span class="opacitymedium">' . $langs->trans('RiskLibraryNoModel') . '</span></td></tr>';
}

foreach ($models as $model) {
    if ($action === 'edit' && $modelId === $model->rowid && $permissiontoadd) {
        print $modelForm($model);
        continue;
    }

    // Assignment of the model: the GP/UT concerned, not concerned and still to qualify
    $counts = [1 => 0, 0 => 0, -1 => 0];
    foreach ($activeDigiriskElements as $activeElementId => $activeElement) {
        $counts[isset($scopes[$model->rowid][$activeElementId]) ? $scopes[$model->rowid][$activeElementId]->concerned : -1]++;
    }

    // Category, label and description are cut to keep the actions in the page: the whole text is in the tooltip
    $categoryName = $dangerCategories[$model->category]['name'] ?? '';
    print '<tr class="oddeven">';
    print '<td class="tdoverflowmax200" title="' . dol_escape_htmltag($categoryName) . '">' . $categoryPicto($model->category) . ' <span class="opacitymedium">' . dol_escape_htmltag($categoryName) . '</span></td>';
    print '<td class="tdoverflowmax200" title="' . dol_escape_htmltag($model->label) . '">' . dol_escape_htmltag($model->label) . '</td>';
    print '<td class="tdoverflowmax250" title="' . dol_escape_htmltag((string) $model->description) . '">' . dol_escape_htmltag((string) $model->description) . '</td>';
    print '<td class="center">' . $cotationBadge($model->cotation, $standardCotations[$model->cotation] ?? (string) $model->cotation, $langs->trans($cotationLevels[$model->cotation] ?? '')) . '</td>';
    // Assignment: three counters shaped like the cells of the grid, the sentence in the tooltip
    print '<td class="center nowraponall" title="' . dol_escape_htmltag($langs->transnoentities('RiskLibraryAssignmentCounts', $counts[1], $counts[0], $counts[-1])) . '">';
    print '<span class="risklibrary-state risklibrary-state--yes"><i class="fas fa-check"></i> ' . $counts[1] . '</span> ';
    print '<span class="risklibrary-state risklibrary-state--no">' . $langs->trans('RiskLibraryStateNotConcernedShort') . ' ' . $counts[0] . '</span> ';
    print '<span class="risklibrary-state risklibrary-state--todo">? ' . $counts[-1] . '</span>';
    print '</td>';
    print '<td class="right nowraponall">';
    if ($permissiontoadd) {
        print '<a class="editfielda paddingright" href="' . $pageUrl . '?action=edit&token=' . newToken() . '&modelid=' . (int) $model->rowid . '#risklibrary-form">' . img_edit() . '</a>';
        print '<a class="paddingright" href="' . $pageUrl . '?action=deleteModel&token=' . newToken() . '&modelid=' . (int) $model->rowid . '">' . img_delete() . '</a>';
    }
    print '</td>';
    print '</tr>';
}

if ($action === 'create' && $permissiontoadd) {
    print $modelForm(null);
}

print '</table>';
print '</div>';

/*
 * The grid: one row per GP/UT, in the order of the tree, one column per model
 */

if (!empty($models)) {
    $elementNodes = !empty($activeDigiriskElements) ? flatten_tree(recurse_tree(0, 0, $activeDigiriskElements)) : [];
    $riskCardUrl  = dol_buildpath('/digiriskdolibarr/view/risk/risk_card.php', 1) . '?id=';

    print '<div id="risklibrary-grid"></div>';
    $exportButton = !empty($elementNodes) ? dolGetButtonTitle($langs->trans('XlsxExport'), $langs->trans('RiskLibraryXlsxExportHelp'), 'fa fa-file-excel', $pageUrl . '?action=export_xlsx&token=' . newToken()) : '';
    print load_fiche_titre($langs->trans('RiskLibraryGrid'), $exportButton, 'fontawesome_fa-th_fas');
    print '<div class="opacitymedium risklibrary-intro">' . $langs->trans('RiskLibraryGridHelp') . '</div>';

    if (empty($elementNodes)) {
        print '<div class="opacitymedium">' . $langs->trans('RiskLibraryNoElement') . '</div>';
    } else {
        print '<div class="div-table-responsive">';
        print '<table class="noborder risklibrary-grid">';
        print '<thead><tr class="liste_titre">';
        print '<th class="risklibrary-grid__element">' . $langs->trans('RiskLibraryElement') . '</th>';
        foreach ($models as $model) {
            print '<th class="center risklibrary-grid__model" title="' . dol_escape_htmltag($model->label) . '">' . $categoryPicto($model->category) . '<span class="risklibrary-grid__label">' . dol_escape_htmltag($model->label) . '</span></th>';
        }
        print '</tr></thead>';

        print '<tbody>';
        foreach ($elementNodes as $nodeElementId => $elementNode) {
            $element = $elementNode['object'];

            print '<tr class="oddeven">';
            print '<td class="nowraponall risklibrary-grid__element" style="padding-left: ' . (8 + 18 * (int) $elementNode['depth']) . 'px">';
            print $element->getNomUrl(1, 'blank', 1) . ' <span class="opacitymedium">' . dol_escape_htmltag($element->label) . '</span>';
            print '</td>';
            foreach ($models as $model) {
                $scope = $scopes[$model->rowid][(int) $nodeElementId] ?? null;
                $state = $scope ? $scope->concerned : -1;
                $cell  = digiriskdolibarr_risklibrary_cell($scope);

                $cellTitle = $model->label . ' - ' . $element->ref;
                if ($cell['state'] === 'risk') {
                    // The risk of the GP/UT, at its current cotation
                    $content = '<a href="' . $riskCardUrl . (int) $scope->fk_risk . '" target="_blank">' . $cotationBadge($cell['cotation'], $cell['label'], $scope->risk_ref . ' - ' . $cellTitle) . '</a>';
                } elseif ($cell['state'] === 'missing') {
                    $content = '<span class="risklibrary-state risklibrary-state--missing" title="' . dol_escape_htmltag($langs->trans('RiskLibraryRiskMissing')) . '"><i class="fas fa-exclamation-triangle"></i></span>';
                } elseif ($cell['state'] === 'notconcerned') {
                    $content = '<span class="risklibrary-state risklibrary-state--no" title="' . dol_escape_htmltag($langs->trans('RiskLibraryStateNotConcerned') . ' : ' . $scope->reason) . '">' . $langs->trans('RiskLibraryStateNotConcernedShort') . '</span>';
                } else {
                    $content = '<span class="risklibrary-state risklibrary-state--todo" title="' . dol_escape_htmltag($langs->trans('RiskLibraryStateToQualify')) . '">?</span>';
                }

                print '<td class="center risklibrary-grid__cell">';
                print $content;
                if ($permissiontoadd) {
                    // Opens the qualification of the GP/UT, which js/modules/riskLibrary.js fills from these data
                    print '<span class="risklibrary-edit modal-open" title="' . dol_escape_htmltag($langs->trans('RiskLibraryQualify')) . '"';
                    print ' data-model="' . (int) $model->rowid . '" data-element="' . (int) $nodeElementId . '" data-state="' . $state . '"';
                    print ' data-reason="' . dol_escape_htmltag($state == 0 ? (string) $scope->reason : '') . '" data-title="' . dol_escape_htmltag($cellTitle) . '">';
                    print '<input type="hidden" class="modal-options" data-modal-to-open="risklibrary_scope_modal">';
                    print '<i class="fas fa-pen"></i>';
                    print '</span>';
                }
                print '</td>';
            }
            print '</tr>';
        }
        print '</tbody>';

        // Number of GP/UT concerned by each model
        print '<tfoot><tr class="liste_total">';
        print '<td>' . $langs->trans('RiskLibraryConcernedTotal') . '</td>';
        foreach ($models as $model) {
            $concernedCount = 0;
            foreach ($scopes[$model->rowid] ?? [] as $scopeElementId => $scope) {
                if ($scope->concerned == 1 && isset($elementNodes[$scopeElementId])) {
                    $concernedCount++;
                }
            }
            print '<td class="center">' . $concernedCount . '</td>';
        }
        print '</tr></tfoot>';
        print '</table>';
        print '</div>';
    }
}

print '</div>';

print dol_get_fiche_end();

// --- Modal qualifying a GP/UT for a model, filled by js/modules/riskLibrary.js ---
if ($permissiontoadd && !empty($models)) {
    print '<div class="wpeo-modal modal-flex risklibrary-modal" id="risklibrary_scope_modal">';
    print '<div class="modal-container wpeo-modal-event">';
    print '<form method="POST" action="' . $pageUrl . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="setScope">';
    print '<input type="hidden" name="modelid" value="">';
    print '<input type="hidden" name="elementid" value="">';

    print '<div class="modal-header">';
    print '<h2 class="modal-title">' . $langs->trans('RiskLibraryQualify') . ' <span class="risklibrary-modal__subject opacitymedium"></span></h2>';
    print '<div class="modal-close"><i class="fas fa-times"></i></div>';
    print '</div>';

    print '<div class="modal-content">';
    foreach ([1 => 'RiskLibraryStateConcerned', 0 => 'RiskLibraryStateNotConcerned', -1 => 'RiskLibraryStateToQualify'] as $stateValue => $stateKey) {
        print '<label class="risklibrary-modal__choice"><input type="radio" name="concerned" value="' . $stateValue . '"> ' . $langs->trans($stateKey) . '</label>';
    }
    print '<div class="risklibrary-modal__help opacitymedium">' . $langs->trans('RiskLibraryQualifyHelp') . '</div>';
    print '<textarea name="reason" class="centpercent risklibrary-modal__reason" rows="2" placeholder="' . dol_escape_htmltag($langs->trans('RiskLibraryReason')) . '"></textarea>';
    print '</div>';

    print '<div class="modal-footer">';
    print '<button type="submit" class="wpeo-button button-blue">' . $langs->trans('Save') . '</button>';
    print '</div>';
    print '</form>';
    print '</div>';
    print '</div>';
}

// End of page
llxFooter();
$db->close();
