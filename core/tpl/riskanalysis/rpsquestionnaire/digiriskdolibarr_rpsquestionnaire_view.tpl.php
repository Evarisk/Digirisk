<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/riskanalysis/rpsquestionnaire/digiriskdolibarr_rpsquestionnaire_view.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Psychosocial risk questionnaires of the company (#5324): the form of a session, then one card per
 *          session with its public link and QR code, and a row per work unit with its number of answers, the level
 *          of each factor and their average. A row unfolds the average of each criterion and the comments.
 *
 * Variables : $sessions, $sessionResults, $sessionLinks, $sessionForm, $grid, $groups, $groupUnits, $workUnits,
 *             $rpsSubCategories, $rpsScaleLabels, $permissiontoadd, $pageUrl, $form, $langs
 *             (view/digiriskstandard/digiriskstandard_rpsquestionnaire.php)
 */

// The level of a work unit as the other psychosocial screens write it: "2,3 · Moderate"
$rpsQuestionnaireBand = function (?float $average, int $band) use ($rpsScaleLabels, $langs): string {
    if ($average === null) {
        return '<span class="rps-band" data-scale="0">' . $langs->trans('PsychosocialRatingNone') . '</span>';
    }
    return '<span class="rps-band" data-scale="' . $band . '">' . price(round($average, 1), 0, $langs, 0, 1, 1) . ' · ' . $rpsScaleLabels[$band] . '</span>';
};

?>
<div class="fichecenter rps-questionnaires">
    <div class="rps-questionnaires__intro">
        <i class="fas fa-clipboard-check"></i>
        <div>
            <p><?php echo $langs->trans('PsychosocialQuestionnairesIntro', count($grid)); ?></p>
            <p class="opacitymedium"><?php echo $langs->trans('PsychosocialQuestionnairesIntroClosure'); ?></p>
        </div>
    </div>

    <?php
    $addButton = dolGetButtonTitle($langs->trans('PsychosocialQuestionnaireNewSession'), '', 'fa fa-plus-circle', $pageUrl . '?action=create#rps-session-form', '', $permissiontoadd ? 1 : 0);
    print load_fiche_titre($langs->trans('PsychosocialQuestionnaireSessions'), $addButton, 'fontawesome_fa-clipboard-list_fas');
    ?>

    <?php if ($sessionForm !== null) : ?>
        <form class="rps-session-form" id="rps-session-form" method="POST" action="<?php echo $pageUrl; ?>">
            <input type="hidden" name="token" value="<?php echo newToken(); ?>">
            <input type="hidden" name="action" value="<?php echo $sessionForm['action']; ?>">
            <input type="hidden" name="sessionid" value="<?php echo (int) $sessionForm['sessionid']; ?>">

            <div class="rps-session-form__title"><?php echo $langs->trans($sessionForm['sessionid'] > 0 ? 'PsychosocialQuestionnaireEditSession' : 'PsychosocialQuestionnaireNewSession'); ?></div>

            <div class="rps-session-form__grid">
                <label class="rps-session-form__field rps-session-form__field--wide">
                    <span><?php echo $langs->trans('Label'); ?></span>
                    <input type="text" name="label" maxlength="255" required value="<?php echo dol_escape_htmltag($sessionForm['label']); ?>">
                </label>

                <label class="rps-session-form__field">
                    <span><?php echo $langs->trans('PsychosocialQuestionnaireJobCategory'); ?></span>
                    <select name="fk_exposuregroup">
                        <option value="0" data-units="[]" data-people="0"><?php echo $langs->trans('PsychosocialQuestionnaireAllGroupsChoice'); ?></option>
                        <?php foreach ($groups as $group) : ?>
                            <option value="<?php echo (int) $group->rowid; ?>"<?php echo $sessionForm['group'] == $group->rowid ? ' selected' : ''; ?>
                                    data-units="<?php echo dol_escape_htmltag(json_encode($groupUnits[$group->rowid] ?? [])); ?>" data-people="<?php echo (int) $group->nb_people; ?>">
                                <?php echo dol_escape_htmltag($group->label) . ' (' . $langs->trans('ExposureGroupNbPeopleShort', (int) $group->nb_people) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="opacitymedium">
                        <?php echo empty($groups) ? $langs->trans('PsychosocialQuestionnaireNoGroupHint', dol_buildpath('/digiriskdolibarr/view/digiriskstandard/digiriskstandard_exposuregroup.php', 1)) : $langs->trans('PsychosocialQuestionnaireJobCategoryHint'); ?>
                    </small>
                </label>

                <label class="rps-session-form__field">
                    <span><?php echo $langs->trans('PsychosocialQuestionnaireMaxAnswers'); ?></span>
                    <input type="number" name="max_answers" min="0" step="1" class="width100" placeholder="<?php echo dol_escape_htmltag($langs->trans('PsychosocialQuestionnaireNoLimit')); ?>" value="<?php echo dol_escape_htmltag($sessionForm['max_answers']); ?>">
                    <small class="opacitymedium"><?php echo $langs->trans('PsychosocialQuestionnaireMaxAnswersHint'); ?></small>
                </label>

                <div class="rps-session-form__field rps-session-form__field--wide">
                    <span><?php echo $langs->trans('PsychosocialQuestionnaireUnits'); ?></span>
                    <?php
                    $unitOptions = [];
                    foreach ($workUnits as $unitId => $workUnit) {
                        $unitOptions[$unitId] = $workUnit->ref . ' - ' . $workUnit->label;
                    }
                    print $form->multiselectarray('units', $unitOptions, $sessionForm['units'], 0, 0, 'rps-session-units', 0, '100%');
                    ?>
                    <?php if (!empty($sessionForm['locked'])) : ?>
                        <small class="opacitymedium"><?php echo $langs->trans('PsychosocialQuestionnaireUnitsLocked'); ?></small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="rps-session-form__buttons">
                <a class="wpeo-button button-grey" href="<?php echo $pageUrl; ?>"><?php echo $langs->trans('Cancel'); ?></a>
                <button type="submit" class="wpeo-button button-blue"><i class="fas fa-check"></i> <?php echo $langs->trans('Save'); ?></button>
            </div>
        </form>
    <?php endif; ?>

    <?php if (empty($sessions) && $sessionForm === null) : ?>
        <div class="rps-questionnaires__empty">
            <i class="fas fa-clipboard-list"></i>
            <div><?php echo $langs->trans('PsychosocialQuestionnaireNoSession'); ?></div>
        </div>
    <?php endif; ?>

    <?php foreach ($sessions as $session) :
        $sessionOpen    = $session['status'] === DIGIRISK_RPS_SESSION_OPEN;
        $results        = $sessionResults[$session['rowid']];
        $sessionUrl     = $sessionLinks[$session['rowid']];
        $sessionActions = $pageUrl . '?sessionid=' . (int) $session['rowid'] . '&token=' . newToken() . '&action=';
        $groupLabel     = $session['fk_exposuregroup'] > 0 ? ($groups[$session['fk_exposuregroup']]->label ?? '-') : $langs->trans('PsychosocialQuestionnaireAllGroups'); ?>

        <section class="rps-session<?php echo $sessionOpen ? '' : ' is-closed'; ?>" id="rps-session-<?php echo (int) $session['rowid']; ?>">
            <div class="rps-session__head">
                <div class="rps-session__heading">
                    <h3 class="rps-session__title"><?php echo dol_escape_htmltag($session['label']); ?></h3>
                    <span class="rps-session__status">
                        <?php echo $sessionOpen ? $langs->trans('PsychosocialQuestionnaireOpen') : $langs->trans('PsychosocialQuestionnaireClosedOn', dol_print_date($session['date_closure'], 'day')); ?>
                    </span>
                </div>
                <div class="rps-session__meta">
                    <span><i class="fas fa-users"></i> <?php echo dol_escape_htmltag($groupLabel); ?></span>
                    <span><i class="fas fa-map-marker-alt"></i> <?php echo $langs->trans('PsychosocialQuestionnaireUnitsCount', count($session['elements'])); ?></span>
                    <span><i class="fas fa-reply"></i> <?php echo $langs->trans('PsychosocialQuestionnaireAnswersCount', $session['total']); ?></span>
                    <span><i class="fas fa-stop-circle"></i> <?php echo $session['max_answers'] > 0 ? $langs->trans('PsychosocialQuestionnaireMaxPerUnit', $session['max_answers']) : $langs->trans('PsychosocialQuestionnaireNoLimit'); ?></span>
                    <span><i class="fas fa-calendar-alt"></i> <?php echo $langs->trans('PsychosocialQuestionnaireCreatedOn', dol_print_date($session['date_creation'], 'day')); ?></span>
                </div>
                <?php if ($permissiontoadd) : ?>
                    <div class="rps-session__actions">
                        <a class="wpeo-button button-grey button-size-small" href="<?php echo $pageUrl . '?action=edit&sessionid=' . (int) $session['rowid'] . '#rps-session-form'; ?>"><i class="fas fa-pen"></i> <?php echo $langs->trans('Modify'); ?></a>
                        <?php if ($sessionOpen) : ?>
                            <a class="wpeo-button button-blue button-size-small" href="<?php echo $sessionActions; ?>closeSession"><i class="fas fa-flag-checkered"></i> <?php echo $langs->trans('PsychosocialQuestionnaireCloseSession'); ?></a>
                        <?php else : ?>
                            <a class="wpeo-button button-grey button-size-small" href="<?php echo $sessionActions; ?>setOpenSession"><i class="fas fa-redo"></i> <?php echo $langs->trans('PsychosocialQuestionnaireReopenSession'); ?></a>
                        <?php endif; ?>
                        <a class="wpeo-button button-grey button-size-small button-square-30" href="<?php echo $sessionActions; ?>deleteSession" title="<?php echo dol_escape_htmltag($langs->trans('PsychosocialQuestionnaireDeleteSession')); ?>"><i class="fas fa-trash"></i></a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($sessionOpen) : ?>
                <div class="rps-session__share">
                    <div class="rps-session__qr" title="<?php echo dol_escape_htmltag($langs->trans('PsychosocialQuestionnaireQrCode')); ?>"><?php echo digiriskGetQrCodeSvg($sessionUrl); ?></div>
                    <div class="rps-session__share-text">
                        <div class="rps-session__share-label"><?php echo $langs->trans('PsychosocialQuestionnaireLink'); ?></div>
                        <div class="rps-session-link">
                            <input type="text" class="rps-session-url" readonly value="<?php echo dol_escape_htmltag($sessionUrl); ?>" aria-label="<?php echo dol_escape_htmltag($langs->trans('PsychosocialQuestionnaireLink')); ?>">
                            <button type="button" class="wpeo-button button-grey button-size-small rps-session-copy">
                                <i class="fas fa-copy"></i> <span class="rps-session-copy-label"><?php echo $langs->trans('PsychosocialQuestionnaireCopyLink'); ?></span><span class="rps-session-copied-label"><?php echo $langs->trans('PsychosocialQuestionnaireLinkCopied'); ?></span>
                            </button>
                            <a class="wpeo-button button-grey button-size-small" href="<?php echo dol_escape_htmltag($sessionUrl); ?>" target="_blank" rel="noopener"><i class="fas fa-external-link-alt"></i> <?php echo $langs->trans('PsychosocialQuestionnaireOpenLink'); ?></a>
                        </div>
                        <small class="opacitymedium"><?php echo $langs->trans('PsychosocialQuestionnaireLinkHint'); ?></small>
                    </div>
                </div>
            <?php endif; ?>

            <div class="div-table-responsive-no-min rps-session__table">
                <table class="rps-session__units">
                    <thead>
                        <tr>
                            <th><?php echo $langs->trans('PsychosocialQuestionnaireUnit'); ?> · <?php echo $langs->trans('PsychosocialQuestionnaireAnswers'); ?></th>
                            <th><?php echo $langs->trans('PsychosocialFactors'); ?></th>
                            <th><?php echo $langs->trans('PsychosocialQuestionnaireAverage'); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($workUnits as $unitId => $workUnit) :
                            if (!isset($session['elements'][$unitId])) {
                                continue;
                            }
                            $sessionElement = $session['elements'][$unitId];
                            $result         = $results[$unitId] ?? null;
                            $unitFull       = $session['max_answers'] > 0 && $sessionElement['count'] >= $session['max_answers'];
                            $detailId       = 'rps-session-' . (int) $session['rowid'] . '-unit-' . (int) $unitId; ?>
                            <tr class="rps-session__unit">
                                <td class="rps-session__unit-name">
                                    <a href="<?php echo dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_risk.php', 1) . '?id=' . (int) $unitId; ?>">
                                        <b><?php echo dol_escape_htmltag($workUnit->ref); ?></b> <?php echo dol_escape_htmltag($workUnit->label); ?>
                                    </a>
                                    <div class="rps-session__unit-answers">
                                        <span class="rps-num"><?php echo $langs->trans('PsychosocialQuestionnaireAnswersCount', (int) $sessionElement['count'] . ($session['max_answers'] > 0 ? ' / ' . (int) $session['max_answers'] : '')); ?></span>
                                        <?php if ($session['max_answers'] > 0) : ?>
                                            <progress class="rps-session__progress" max="<?php echo (int) $session['max_answers']; ?>" value="<?php echo min((int) $sessionElement['count'], (int) $session['max_answers']); ?>"></progress>
                                        <?php endif; ?>
                                        <?php if ($unitFull) : ?>
                                            <span class="rps-session__full"><?php echo $langs->trans('PsychosocialQuestionnaireUnitFull'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="rps-session__factors">
                                        <?php foreach ($rpsSubCategories as $rpsPosition => $rpsSub) :
                                            $rpsLevel = (int) ($result['factors'][$rpsPosition] ?? 0); ?>
                                            <span class="rps-session__factor" title="<?php echo dol_escape_htmltag($langs->trans($rpsSub['label']) . ' — ' . ($rpsLevel ? $rpsScaleLabels[$rpsLevel] : $langs->trans('PsychosocialFactorNotRated'))); ?>">
                                                <span class="rps-cell" data-scale="<?php echo $rpsLevel; ?>"><?php echo $rpsLevel ?: ''; ?></span>
                                                <span class="rps-session__factor-key">F<?php echo (int) $rpsPosition + 1; ?></span>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td class="rps-session__unit-average">
                                    <?php echo $rpsQuestionnaireBand($result['average'] ?? null, (int) ($result['band'] ?? 0)); ?>
                                    <div class="rps-session__unit-assessment">
                                        <?php if ($sessionElement['fk_riskassessment'] > 0) : ?>
                                            <span class="rps-session__recorded"><i class="fas fa-check"></i> <?php echo $langs->trans('PsychosocialQuestionnaireRecorded'); ?></span>
                                        <?php elseif ($sessionOpen && !empty($result['count'])) : ?>
                                            <span class="opacitymedium"><?php echo $langs->trans('PsychosocialQuestionnaireRecordedAtClosure'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="right">
                                    <?php if (!empty($result['count'])) : ?>
                                        <button type="button" class="wpeo-button button-grey button-square-30 rps-session-unit-toggle" aria-expanded="false" aria-controls="<?php echo $detailId; ?>"
                                                title="<?php echo dol_escape_htmltag($langs->trans('PsychosocialQuestionnaireDetail')); ?>" aria-label="<?php echo dol_escape_htmltag($langs->trans('PsychosocialQuestionnaireDetail')); ?>">
                                            <i class="fas fa-chevron-down"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if (!empty($result['count'])) : ?>
                                <tr class="rps-session__detail" id="<?php echo $detailId; ?>" hidden>
                                    <td colspan="4">
                                        <?php if ($session['fk_exposuregroup'] <= 0) : ?>
                                            <div class="rps-session__groups">
                                                <b><?php echo $langs->trans('PsychosocialQuestionnaireAnswersByGroup'); ?></b>
                                                <?php foreach ($result['groups'] as $answerGroupId => $answerCount) : ?>
                                                    <span class="rps-session__group"><?php echo dol_escape_htmltag($answerGroupId > 0 ? ($groups[$answerGroupId]->label ?? '-') : $langs->trans('PsychosocialQuestionnaireJobNotGiven')); ?> : <b><?php echo (int) $answerCount; ?></b></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        <table class="rps-session__criteria">
                                            <thead>
                                                <tr>
                                                    <th><?php echo $langs->trans('PsychosocialQuestionnaireCriterion'); ?></th>
                                                    <th class="center"><?php echo $langs->trans('PsychosocialQuestionnaireAverage'); ?></th>
                                                    <th><?php echo $langs->trans('PsychosocialQuestionnaireSpread'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($grid as $position => $criterion) :
                                                    $criterionResult = $result['criteria'][$position] ?? null;
                                                    if ($criterionResult === null) {
                                                        continue;
                                                    } ?>
                                                    <tr>
                                                        <td>
                                                            <span class="rps-session__criterion-factor">F<?php echo (int) $criterion['subCategory'] + 1; ?></span>
                                                            <?php echo (int) $position . '. ' . dol_escape_htmltag($criterion['name']); ?>
                                                        </td>
                                                        <td class="center"><span class="rps-band" data-scale="<?php echo (int) $criterionResult['level']; ?>"><?php echo price(round($criterionResult['mean'], 1), 0, $langs, 0, 1, 1); ?></span></td>
                                                        <td class="rps-session__spread">
                                                            <?php foreach ($criterionResult['spread'] as $spreadLevel => $spreadCount) : ?>
                                                                <span class="rps-cell" data-scale="<?php echo $spreadCount > 0 ? (int) $spreadLevel : 0; ?>" title="<?php echo dol_escape_htmltag($rpsScaleLabels[$spreadLevel]); ?>"><?php echo (int) $spreadCount; ?></span>
                                                            <?php endforeach; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <?php if (!empty($result['comments'])) : ?>
                                            <div class="rps-session__comments">
                                                <b><?php echo $langs->trans('PsychosocialQuestionnaireComments'); ?></b>
                                                <?php foreach ($result['comments'] as $answerComment) : ?>
                                                    <blockquote><?php echo dol_nl2br(dol_escape_htmltag($answerComment['text'])); ?><small><?php echo dol_print_date($answerComment['date'], 'day'); ?></small></blockquote>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endforeach; ?>
</div>
