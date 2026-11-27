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
 * \file    core/tpl/riskanalysis/rpsquestionnaire/digiriskdolibarr_rpsquestionnaire_public.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   View of the public questionnaire on the psychosocial risks (#5324): the work unit, the job category
 *          when the session does not fix it, the 26 criteria of the RPS-DU grid grouped by family, a comment.
 *          The form works without JavaScript; js/modules/rpsQuestionnaire.js counts the answers and points to
 *          the first one missing.
 *
 * Variables : $questionnaireState, $questionnaireUnits, $questionnaireGroupFixed, $questionnaireCompany,
 *             $questionnaireFrequencies, $questionnairePicked, $session, $grid, $groups, $key, $postedUnit,
 *             $postedGroup, $postedComment, $answerErrors, $conf, $langs (public/rps/rps_questionnaire.php)
 */

// Message of a page that does not show the questionnaire
$questionnaireMessages = [
    'invalid' => ['icon' => 'fa-lock',         'text' => 'PsychosocialQuestionnaireInvalid'],
    'closed'  => ['icon' => 'fa-lock',         'text' => 'PsychosocialQuestionnaireClosedMessage'],
    'full'    => ['icon' => 'fa-check-circle', 'text' => 'PsychosocialQuestionnaireFullMessage'],
    'done'    => ['icon' => 'fa-check-circle', 'text' => 'PsychosocialQuestionnaireThanks'],
];

$questionnaireFamily = '';

?>
<div class="public-card__container rps-questionnaire<?php echo $questionnaireState == 'form' ? '' : ' rps-questionnaire--message'; ?>" data-public-interface="true">
    <?php if ($questionnaireState != 'invalid') : ?>
        <div class="rps-questionnaire__header">
            <?php if (dol_strlen($questionnaireCompany['logoUrl'])) : ?>
                <img class="rps-questionnaire__logo" src="<?php echo dol_escape_htmltag($questionnaireCompany['logoUrl']); ?>" alt="">
            <?php endif; ?>
            <div>
                <div class="rps-questionnaire__title"><?php echo $langs->trans('PsychosocialQuestionnairePublicTitle'); ?></div>
                <div class="rps-questionnaire__company"><?php echo dol_escape_htmltag($questionnaireCompany['name']); ?></div>
                <div class="rps-questionnaire__session"><?php echo dol_escape_htmltag($session['label']); ?></div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($questionnaireState != 'form') : ?>
        <div class="rps-questionnaire__message rps-questionnaire__message--<?php echo $questionnaireState; ?>">
            <i class="fas <?php echo $questionnaireMessages[$questionnaireState]['icon']; ?>"></i>
            <div><?php echo $langs->trans($questionnaireMessages[$questionnaireState]['text']); ?></div>
        </div>
    <?php else : ?>
        <div class="rps-questionnaire__intro">
            <p><?php echo $langs->trans('PsychosocialQuestionnaireIntro', count($grid)); ?></p>
            <p class="rps-questionnaire__anonymous"><i class="fas fa-user-secret"></i> <?php echo $langs->trans('PsychosocialQuestionnaireAnonymous'); ?></p>
        </div>

        <form class="rps-questionnaire__form" method="POST" action="<?php echo dol_escape_htmltag($_SERVER['PHP_SELF']); ?>"
              data-total="<?php echo count($grid); ?>"
              data-label-missing="<?php echo dol_escape_htmltag($langs->transnoentities('PsychosocialQuestionnaireErrorIncomplete', '%s')); ?>"
              data-label-unit="<?php echo dol_escape_htmltag($langs->transnoentities('PsychosocialQuestionnaireErrorUnit')); ?>">
            <input type="hidden" name="action" value="answer">
            <input type="hidden" name="entity" value="<?php echo (int) $conf->entity; ?>">
            <input type="hidden" name="key" value="<?php echo dol_escape_htmltag($key); ?>">

            <?php if (!empty($answerErrors)) : ?>
                <div class="rps-questionnaire__errors" role="alert">
                    <?php foreach ($answerErrors as $answerError) : ?>
                        <div><i class="fas fa-exclamation-circle"></i> <?php echo dol_escape_htmltag($answerError); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="rps-questionnaire__errors rps-questionnaire__errors--live" role="alert" hidden></div>

            <fieldset class="rps-questionnaire__step rps-questionnaire__step--unit">
                <legend><span class="rps-questionnaire__step-number">1</span> <?php echo $langs->trans('PsychosocialQuestionnaireYourUnit'); ?></legend>
                <?php if (count($questionnaireUnits) == 1) :
                    $questionnaireUnit = reset($questionnaireUnits); ?>
                    <input type="hidden" name="unit" value="<?php echo (int) $postedUnit; ?>">
                    <div class="rps-questionnaire__unit-fixed"><i class="fas fa-map-marker-alt"></i> <?php echo dol_escape_htmltag($questionnaireUnit['ref'] . ' - ' . $questionnaireUnit['label']); ?></div>
                <?php else : ?>
                    <div class="rps-questionnaire__units">
                        <?php foreach ($questionnaireUnits as $unitId => $questionnaireUnit) : ?>
                            <label class="rps-questionnaire__unit<?php echo $questionnaireUnit['open'] ? '' : ' is-full'; ?>">
                                <input type="radio" name="unit" value="<?php echo (int) $unitId; ?>" required
                                    <?php echo $postedUnit == $unitId && $questionnaireUnit['open'] ? 'checked' : ''; ?>
                                    <?php echo $questionnaireUnit['open'] ? '' : 'disabled'; ?>>
                                <span class="rps-questionnaire__unit-card">
                                    <span class="rps-questionnaire__unit-name">
                                        <b><?php echo dol_escape_htmltag($questionnaireUnit['label']); ?></b>
                                        <small><?php echo dol_escape_htmltag($questionnaireUnit['ref']); ?></small>
                                    </span>
                                    <?php if (!$questionnaireUnit['open']) : ?>
                                        <span class="rps-questionnaire__unit-full"><?php echo $langs->trans('PsychosocialQuestionnaireUnitFull'); ?></span>
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </fieldset>

            <fieldset class="rps-questionnaire__step rps-questionnaire__step--group">
                <legend><span class="rps-questionnaire__step-number">2</span> <?php echo $langs->trans('PsychosocialQuestionnaireYourJob'); ?></legend>
                <?php if ($questionnaireGroupFixed) : ?>
                    <div class="rps-questionnaire__unit-fixed"><i class="fas fa-users"></i> <?php echo dol_escape_htmltag($groups[$session['fk_exposuregroup']]->label ?? ''); ?></div>
                <?php else : ?>
                    <select name="group" class="rps-questionnaire__group" aria-label="<?php echo dol_escape_htmltag($langs->trans('PsychosocialQuestionnaireYourJob')); ?>">
                        <option value="0"><?php echo $langs->trans('PsychosocialQuestionnaireJobNotGiven'); ?></option>
                        <?php foreach ($groups as $group) : ?>
                            <option value="<?php echo (int) $group->rowid; ?>"<?php echo $postedGroup == $group->rowid ? ' selected' : ''; ?>><?php echo dol_escape_htmltag($group->label); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </fieldset>

            <fieldset class="rps-questionnaire__step rps-questionnaire__step--questions">
                <legend><span class="rps-questionnaire__step-number">3</span> <?php echo $langs->trans('PsychosocialQuestionnaireYourAnswers'); ?></legend>
                <div class="rps-questionnaire__hint"><?php echo $langs->trans('PsychosocialQuestionnaireAnswersHint'); ?></div>

                <?php foreach ($grid as $position => $criterion) : ?>
                    <?php if ($criterion['family'] != $questionnaireFamily) :
                        $questionnaireFamily = $criterion['family']; ?>
                        <h3 class="rps-questionnaire__family"><?php echo dol_escape_htmltag($questionnaireFamily); ?></h3>
                    <?php endif; ?>
                    <div class="rps-questionnaire__question<?php echo !empty($answerErrors) && empty($questionnairePicked[$position]) ? ' is-missing' : ''; ?>" id="rps-question-<?php echo (int) $position; ?>">
                        <div class="rps-questionnaire__question-text" id="rps-question-text-<?php echo (int) $position; ?>">
                            <span class="rps-questionnaire__question-number"><?php echo (int) $position; ?></span>
                            <span><?php echo dol_escape_htmltag($criterion['questionSelf']); ?></span>
                        </div>
                        <div class="rps-questionnaire__answers" role="radiogroup" aria-labelledby="rps-question-text-<?php echo (int) $position; ?>">
                            <?php foreach ($questionnaireFrequencies as $frequency => $frequencyLabel) : ?>
                                <label class="rps-questionnaire__answer">
                                    <input type="radio" name="q[<?php echo (int) $position; ?>]" value="<?php echo (int) $frequency; ?>"<?php echo $questionnairePicked[$position] == $frequency ? ' checked' : ''; ?>>
                                    <span><?php echo dol_escape_htmltag($frequencyLabel); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </fieldset>

            <fieldset class="rps-questionnaire__step rps-questionnaire__step--comment">
                <legend><span class="rps-questionnaire__step-number">4</span> <?php echo $langs->trans('PsychosocialQuestionnaireComment'); ?></legend>
                <textarea name="comment" rows="3" maxlength="2000" placeholder="<?php echo dol_escape_htmltag($langs->trans('PsychosocialQuestionnaireCommentPlaceholder')); ?>"><?php echo dol_escape_htmltag((string) $postedComment); ?></textarea>
            </fieldset>

            <div class="rps-questionnaire__footer">
                <div class="rps-questionnaire__progress">
                    <span class="rps-questionnaire__progress-text">
                        <b class="rps-questionnaire__answered"><?php echo count(array_filter($questionnairePicked)); ?></b> / <?php echo count($grid); ?> <?php echo $langs->trans('PsychosocialQuestionnaireAnsweredCount'); ?>
                    </span>
                    <span class="rps-questionnaire__progress-bar"><span class="rps-questionnaire__progress-fill"></span></span>
                </div>
                <?php // no-load: the loader of Saturne would stay on a refused sending, js/modules/rpsQuestionnaire.js disables the button itself ?>
                <button type="submit" class="wpeo-button button-blue no-load rps-questionnaire__send"><i class="fas fa-paper-plane"></i> <?php echo $langs->trans('PsychosocialQuestionnaireSend'); ?></button>
            </div>
        </form>
    <?php endif; ?>
</div>
