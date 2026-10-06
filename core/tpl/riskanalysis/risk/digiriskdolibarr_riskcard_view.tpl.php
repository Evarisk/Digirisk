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
 * \file    core/tpl/riskanalysis/risk/digiriskdolibarr_riskcard_view.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Read view of the risk card
 *
 * Expects the risk card controller to have loaded $object, $digiriskElement, $riskAssessment,
 * $riskAssessments, $lastRiskAssessment, $relatedTasks and $usersList.
 */

global $conf, $db, $form, $hookmanager, $langs, $user;

// The standard method stores the lower bound of the range the risk falls in, the advanced one the computed cotation
$defaultCotation = [0 => '0-47', 48 => '48-50', 51 => '51-80', 100 => '81-100'];
$cotations       = $object->getCotations();
$riskType        = $object->type;

// Object card
// ------------------------------------------------------------
saturne_get_fiche_head($object, 'card', $title);

$backToList = '<a href="' . dol_buildpath('/digiriskdolibarr/view/digiriskelement/risk_list.php', 1) . '?restore_lastsearch_values=1&risk_type=' . $riskType . '">' . $langs->trans('BackToList') . '</a>';

// A risk is named by its ref only, the danger category is what tells what it is about
$dangerCategoryThumbnail = $object->getDangerCategory($object, $riskType);

$moreHtmlRef = '<span class="risk-card-danger-category">';
if ($dangerCategoryThumbnail != -1) {
    $moreHtmlRef .= '<img class="risk-card-danger-category-pic" src="' . DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $dangerCategoryThumbnail . '.png" alt="">';
}
$moreHtmlRef .= '<span class="risk-card-danger-category-name">' . $object->getDangerCategoryName($object, $riskType) . '</span>';
$moreHtmlRef .= '</span>';

saturne_banner_tab($object, 'id', $backToList, 1, 'rowid', 'ref', $moreHtmlRef);

print '<div class="fichecenter risk-card">';

// Left column: what the risk is
print '<div class="fichehalfleft">';
print '<table class="border centpercent tableforfield">';

// Sub category, only the danger categories declaring one have it filled
$subCategoryLabel = $object->getDangerSubCategoryName($object->category, $object->sub_category);
if ($subCategoryLabel != -1) {
    print '<tr><td class="titlefield">' . $langs->trans('SubCategory') . '</td><td>' . $langs->trans($subCategoryLabel) . '</td></tr>';
}

// Parent element, read as the whole branch it hangs from so the risk is placed in the organisation
print '<tr><td class="titlefield">' . $langs->trans('ParentElement') . '</td><td>';
if ($digiriskElement->id > 0) {
    $branchIds = array_reverse($digiriskElement->getBranch($digiriskElement->id));
    $parentElement = new DigiriskElement($db);
    foreach ($branchIds as $branchKey => $branchId) {
        if ($parentElement->fetch($branchId) > 0) {
            print str_repeat('&#160;', $branchKey * 2) . ($branchKey > 0 ? '&#x21B3; ' : '') . $parentElement->getNomUrl(1, 'blank', 0, '', -1, 1) . '<br>';
        }
    }
} else {
    print '<span class="opacitymedium">' . $langs->trans('None') . '</span>';
}
print '</td></tr>';

// When the risk entered the document unique, and who put it there
print '<tr><td class="titlefield">' . $langs->trans('DateCreation') . '</td><td>';
print dol_print_date($object->date_creation, 'dayhour');
if (!empty($usersList[$object->fk_user_creat])) {
    $userAuthor = $usersList[$object->fk_user_creat];
    print ' &nbsp; ' . $userAuthor->getNomUrl(-1);
}
print '</td></tr>';

if (!empty($object->tms)) {
    print '<tr><td class="titlefield">' . $langs->trans('DateModification') . '</td><td>';
    print dol_print_date($object->tms, 'dayhour');
    if (!empty($usersList[$object->fk_user_modif])) {
        $userAuthor = $usersList[$object->fk_user_modif];
        print ' &nbsp; ' . $userAuthor->getNomUrl(-1);
    }
    print '</td></tr>';
}

// Tags-Categories
if (isModEnabled('categorie') && getDolGlobalInt('DIGIRISKDOLIBARR_CATEGORY_ON_RISK') > 0 && $user->rights->categorie->lire) {
    print '<tr><td>' . $langs->trans('Categories') . '</td><td>';
    print $form->showCategories($object->id, 'risk', 1);
    print '</td></tr>';
}

print '</table>';
print '</div>';

// Right column: where the risk stands today
print '<div class="fichehalfright">';
print '<div class="risk-card-cotation-panel">';
print '<div class="risk-card-cotation-panel-title">' . $langs->trans('LastRiskAssessment') . '</div>';

if (is_object($lastRiskAssessment)) {
    $cotationLevel = $object->getCotationLevel($lastRiskAssessment->cotation);

    print '<div class="risk-card-cotation-panel-content">';
    print '<div class="risk-evaluation-cotation" data-scale="' . $lastRiskAssessment->getEvaluationScale() . '">' . ($lastRiskAssessment->method == 'standard' ? ($defaultCotation[$lastRiskAssessment->cotation] ?? 0) : $lastRiskAssessment->cotation) . '</div>';
    print '<div class="risk-card-cotation-panel-data">';
    print '<div class="risk-card-cotation-level">' . ($cotations[$cotationLevel]['label'] ?? '') . '</div>';
    print '<div class="risk-card-cotation-meta">';
    print '<i class="fas fa-calendar-alt"></i> ' . dol_print_date((getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE') && !empty($lastRiskAssessment->date_riskassessment)) ? $lastRiskAssessment->date_riskassessment : $lastRiskAssessment->date_creation, 'day');
    if (!empty($usersList[$lastRiskAssessment->fk_user_creat])) {
        $userAuthor = $usersList[$lastRiskAssessment->fk_user_creat];
        print ' &nbsp; ' . $userAuthor->getNomUrl(-1);
    }
    print '</div>';
    print '</div>';
    print '</div>';
} else {
    print '<div class="risk-card-cotation-panel-content opacitymedium">' . $langs->trans('NoRiskAssessmentYet') . '</div>';
}

print '</div>';

// Counters of what hangs from the risk, each one linking to the block that details it
print '<div class="risk-card-counters">';
print '<a class="risk-card-counter" href="#riskCardAssessments"><span class="risk-card-counter-value">' . count($riskAssessments) . '</span><span class="risk-card-counter-label">' . $langs->trans('ListingHeaderEvaluation') . '</span></a>';
if (getDolGlobalInt('DIGIRISKDOLIBARR_TASK_MANAGEMENT')) {
    print '<a class="risk-card-counter" href="#riskCardTasks"><span class="risk-card-counter-value">' . count($relatedTasks) . '</span><span class="risk-card-counter-label">' . $langs->trans('ListingHeaderTask') . '</span></a>';
}
print '</div>';

print '</div>';
print '</div>';
print '<div class="clearboth"></div>';

// Description, the very reason of this card: the list only shows its first 120 characters
print load_fiche_titre($langs->trans('Description'), '', '');
print '<div class="risk-card-description wordbreak">';
if (!getDolGlobalInt('DIGIRISKDOLIBARR_RISK_DESCRIPTION')) {
    print '<span class="opacitymedium">' . $langs->trans('RiskDescriptionNotActivated') . '</span>';
} elseif (dol_strlen($object->description) > 0) {
    print dol_nl2br(dol_escape_htmltag($object->description));
} else {
    print '<span class="opacitymedium">' . $langs->trans('NoDescription') . '</span>';
}
print '</div>';

// Every assessment the risk went through, the list only shows the last one
print '<div id="riskCardAssessments"></div>';
print load_fiche_titre($langs->trans('ListingHeaderEvaluation') . ' (' . count($riskAssessments) . ')', '', '');
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th class="liste_titre">' . $langs->trans('Ref') . '</th>';
print '<th class="liste_titre center">' . $langs->trans('Evaluation') . '</th>';
print '<th class="liste_titre">' . $langs->trans('RiskAssessmentDate') . '</th>';
print '<th class="liste_titre">' . $langs->trans('UserAuthor') . '</th>';
print '<th class="liste_titre">' . $langs->trans('Comment') . '</th>';
print '<th class="liste_titre center">' . $langs->trans('Photo') . '</th>';
print '<th class="liste_titre center">' . $langs->trans('Status') . '</th>';
print '</tr>';

if (!empty($riskAssessments)) {
    foreach ($riskAssessments as $riskAssessmentSingle) {
        print '<tr class="oddeven">';
        print '<td class="nowrap">' . $riskAssessmentSingle->ref . '</td>';

        print '<td class="center">';
        print '<div class="risk-evaluation-cotation" data-scale="' . $riskAssessmentSingle->getEvaluationScale() . '">' . ($riskAssessmentSingle->method == 'standard' ? ($defaultCotation[$riskAssessmentSingle->cotation] ?? 0) : $riskAssessmentSingle->cotation) . '</div>';
        // The advanced method computes the cotation from criteria worth reading next to it
        if ($riskAssessmentSingle->method == 'advanced') {
            print '<div class="risk-card-criteria">';
            foreach ($riskAssessmentSingle->advancedCotation as $criterion) {
                if (dol_strlen($riskAssessmentSingle->$criterion) > 0) {
                    print '<span class="risk-card-criterion">' . $langs->trans($riskAssessment->fields[$criterion]['label']) . ' : ' . $riskAssessmentSingle->$criterion . '</span>';
                }
            }
            print '</div>';
        }
        print '</td>';

        print '<td class="nowrap">' . dol_print_date((getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE') && !empty($riskAssessmentSingle->date_riskassessment)) ? $riskAssessmentSingle->date_riskassessment : $riskAssessmentSingle->date_creation, 'day') . '</td>';

        print '<td class="nowrap">' . (!empty($usersList[$riskAssessmentSingle->fk_user_creat]) ? $usersList[$riskAssessmentSingle->fk_user_creat]->getNomUrl(-1) : '') . '</td>';

        // The comment is read in full here, the list truncates it to 120 characters
        print '<td class="wordbreak">' . (dol_strlen($riskAssessmentSingle->comment) > 0 ? dol_nl2br(dol_escape_htmltag($riskAssessmentSingle->comment)) : '') . '</td>';

        // saturne_show_medias_linked() draws a placeholder when the folder holds nothing, which
        // would fill the whole column with empty frames: only the assessments carrying a photo show one
        print '<td class="center">';
        if (dol_strlen($riskAssessmentSingle->photo) > 0) {
            print saturne_show_medias_linked('digiriskdolibarr', DOL_DATA_ROOT . '/' . ($riskAssessmentSingle->entity > 1 ? $riskAssessmentSingle->entity . '/' : '') . 'digiriskdolibarr/riskassessment/' . $riskAssessmentSingle->ref, 'small', 1, 0, 0, 0, 50, 50, 0, 0, 1, '/riskassessment/' . $riskAssessmentSingle->ref, $riskAssessmentSingle, 'photo', 0, 0, 0, 1);
        }
        print '</td>';

        print '<td class="center">' . $riskAssessmentSingle->getLibStatut(5) . '</td>';
        print '</tr>';
    }
} else {
    print '<tr><td colspan="7" class="opacitymedium">' . $langs->trans('NoRiskAssessmentYet') . '</td></tr>';
}
print '</table>';
print '</div>';

// Action plan opened on the risk, read either as a table or as the Gantt of the module
if (getDolGlobalInt('DIGIRISKDOLIBARR_TASK_MANAGEMENT')) {
    print '<div id="riskCardTasks"></div>';

    $taskViewUrl    = $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&taskview=';
    $taskViewSwitch = '<div class="risk-card-taskview-switch">';
    $taskViewSwitch .= '<a class="risk-card-taskview' . ($taskView == 'list' ? ' active' : '') . '" href="' . $taskViewUrl . 'list#riskCardTasks"><i class="fas fa-list"></i> ' . $langs->trans('List') . '</a>';
    $taskViewSwitch .= '<a class="risk-card-taskview' . ($taskView == 'gantt' ? ' active' : '') . '" href="' . $taskViewUrl . 'gantt#riskCardTasks"><i class="fas fa-stream"></i> ' . $langs->trans('GanttView') . '</a>';
    $taskViewSwitch .= '</div>';

    print load_fiche_titre($langs->trans('ListingHeaderTask') . ' (' . count($relatedTasks) . ')', $taskViewSwitch, '');
}

if (getDolGlobalInt('DIGIRISKDOLIBARR_TASK_MANAGEMENT') && $taskView == 'gantt') {
    // The Gantt of the action plan, fed with the tasks of this risk only
    require __DIR__ . '/../../actionplan/actionplan_list_gantt.tpl.php';
} elseif (getDolGlobalInt('DIGIRISKDOLIBARR_TASK_MANAGEMENT')) {
    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<th class="liste_titre">' . $langs->trans('Ref') . '</th>';
    print '<th class="liste_titre">' . $langs->trans('Label') . '</th>';
    print '<th class="liste_titre">' . $langs->trans('Project') . '</th>';
    print '<th class="liste_titre">' . $langs->trans('DateStart') . '</th>';
    print '<th class="liste_titre">' . $langs->trans('DateEnd') . '</th>';
    print '<th class="liste_titre right">' . $langs->trans('Progress') . '</th>';
    print '</tr>';

    if (!empty($relatedTasks)) {
        foreach ($relatedTasks as $relatedTask) {
            print '<tr class="oddeven">';
            print '<td class="nowrap">' . $relatedTask->getNomUrl(1, 'withproject') . '</td>';
            print '<td>' . dol_escape_htmltag($relatedTask->label) . '</td>';
            print '<td class="nowrap">';
            if ($relatedTask->fk_project > 0 && $project->fetch($relatedTask->fk_project) > 0) {
                print $project->getNomUrl(1);
            }
            print '</td>';
            print '<td class="nowrap">' . dol_print_date($relatedTask->date_start, 'day') . '</td>';
            print '<td class="nowrap">' . dol_print_date($relatedTask->date_end, 'day') . '</td>';
            print '<td class="right"><span class="' . $relatedTask->getTaskProgressColorClass($relatedTask->progress) . '">' . (int) $relatedTask->progress . ' %</span></td>';
            print '</tr>';
        }
    } else {
        print '<tr><td colspan="6" class="opacitymedium">' . $langs->trans('NoTaskYet') . '</td></tr>';
    }
    print '</table>';
    print '</div>';
}

print dol_get_fiche_end();

// Hook to let the other modules add their own block at the bottom of the card
$parameters = [];
$reshook    = $hookmanager->executeHooks('riskCardBottom', $parameters, $object, $action);
print $hookmanager->resPrint;
