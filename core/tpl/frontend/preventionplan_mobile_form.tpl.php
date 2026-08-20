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
 * \file    core/tpl/frontend/preventionplan_mobile_form.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Creation form screen of the mobile prevention plan interface.
 *          Expects: $conf, $form, $langs, $mysoc, $user, $savedSignature, $uploadToken.
 */

global $conf, $langs, $mysoc, $user;

$hasSignature     = digiriskIsValidSignature($savedSignature);
$sirenLookupUrl   = dol_buildpath('/custom/digiriskdolibarr/core/ajax/mobile_siren_lookup.php', 1);
$saveSignatureUrl = dol_buildpath('/custom/digiriskdolibarr/core/ajax/save_user_signature.php', 1);

// Danger categories (risks) and signalisation pictos (protections), indexed by position:
// used both to render the already selected items and to fill the picker modals
$dangerCategories = Risk::getDangerCategories();
$dangerMap        = [];
foreach ($dangerCategories as $dangerCategoryItem) {
    $dangerMap[$dangerCategoryItem['position']] = $dangerCategoryItem;
}

$signalisationFile       = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/signalisationCategories.json';
$signalisationCategories = file_exists($signalisationFile) ? (json_decode(file_get_contents($signalisationFile), true) ?: []) : [];
$protectionMap           = [];
foreach ($signalisationCategories as $signalisationItem) {
    $protectionMap[$signalisationItem['position']] = $signalisationItem;
}
?>
<div class="pwa-container digirisk-mobile">
    <form method="POST" action="<?php print $_SERVER['PHP_SELF']; ?>" class="digirisk-mobile-form digirisk-mobile-form--preventionplan"
          data-has-signature="<?php print ($hasSignature || !empty($isEdit)) ? '1' : '0'; ?>"
          data-siren-lookup-url="<?php print dol_escape_htmltag($sirenLookupUrl); ?>"
          data-save-signature-url="<?php print dol_escape_htmltag($saveSignatureUrl); ?>"
          data-empty-signature-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorEmptySignature')); ?>"
          data-need-signature-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorNoSignature')); ?>"
          data-invalid-siren-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorInvalidSiren')); ?>"
          data-company-found-label="<?php print dol_escape_htmltag($langs->trans('MobilePPCompanyFound')); ?>"
          data-company-not-found-label="<?php print dol_escape_htmltag($langs->trans('MobilePPCompanyNotFound')); ?>"
          data-end-before-start-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorEndBeforeStart')); ?>"
          data-max-span-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorMaxOneYear')); ?>"
          data-max-span-days="365"
          data-dates-required-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorDatesRequired')); ?>"
          data-date-start-required-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorStartRequired')); ?>"
          data-date-end-required-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorEndRequired')); ?>"
          data-risk-comment-label="<?php print dol_escape_htmltag($langs->trans('MobilePPRiskComment')); ?>"
          data-mandatory-label="<?php print dol_escape_htmltag($langs->trans('MobilePPMandatory')); ?>"
          data-risk-start-index="<?php print count($prefill['risks']); ?>"
          data-delete-risk-label="<?php print dol_escape_htmltag($langs->trans('MobilePPDeleteRiskConfirm')); ?>"
          data-cert-start-index="<?php print count($prefill['certifications']); ?>">
        <input type="hidden" name="token" value="<?php print newToken(); ?>">
        <input type="hidden" name="action" value="add_mobile">
        <?php if (!empty($isEdit)) { ?><input type="hidden" name="id" value="<?php print (int) $object->id; ?>"><?php } ?>
        <input type="hidden" name="ext_society_id" class="digirisk-mobile-ext-society-id" value="<?php print (int) $prefill["ext_society_id"]; ?>">
        <input type="hidden" name="resp_contact_id" class="digirisk-mobile-resp-contact-id" value="<?php print (int) $prefill["resp_contact_id"]; ?>">

        <div class="digirisk-mobile-form-errors hidden"></div>

        <!-- Card 3: intervention period, capped to one year -->
        <div class="digirisk-mobile-card">
                        <!-- Workflow injection -->
            <?php
            $svgClipboardInner = '<path d="M30 15 H20 C14.5 15 10 19.5 10 25 V85 C10 90.5 14.5 95 20 95 H80 C85.5 95 90 90.5 90 85 V25 C90 19.5 85.5 15 80 15 H70" fill="none" stroke="currentColor" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/><rect x="35" y="5" width="30" height="15" rx="5" fill="none" stroke="currentColor" stroke-width="8"/><circle cx="50" cy="15" r="3" fill="currentColor"/><line x1="25" y1="40" x2="75" y2="40" stroke="currentColor" stroke-width="6" stroke-linecap="round"/><line x1="25" y1="55" x2="75" y2="55" stroke="currentColor" stroke-width="6" stroke-linecap="round"/><line x1="25" y1="70" x2="50" y2="70" stroke="currentColor" stroke-width="6" stroke-linecap="round"/><circle cx="75" cy="75" r="25" fill="#ffffff"/><circle cx="75" cy="75" r="20" fill="currentColor"/><path d="M65 75 L72 82 L85 65" fill="none" stroke="#ffffff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>';

            $stepsCreation = [
                [
                    'title'   => 'PP créé',
                    'status'  => (!empty($isEdit) ? 'FAIT' : 'EN COURS'),
                    'date'    => dol_print_date(dol_now(), 'day'),
                    'done'    => !empty($isEdit),
                    'current' => empty($isEdit),
                    'viewBox' => '0 0 100 100',
                    'svg'     => $svgClipboardInner
                ],
                [
                    'title'   => 'Resp. EU',
                    'status'  => 'À FAIRE',
                    'date'    => '',
                    'done'    => false,
                    'current' => !empty($isEdit),
                    'viewBox' => '0 0 448 512',
                    'svg'     => '<path d="M224 256c70.7 0 128-57.3 128-128S294.7 0 224 0 96 57.3 96 128s57.3 128 128 128zm95.8 32.6L272 480l-32-136 32 56h-96l32-56-32 136-47.8-191.4C56.9 292 0 350.3 0 422.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-41.6c0-72.1-56.9-130.4-128.2-133.8z"/>'
                ],
                [
                    'title'   => 'Resp. EE',
                    'status'  => 'À FAIRE',
                    'date'    => '',
                    'done'    => false,
                    'current' => false,
                    'viewBox' => '0 0 512 512',
                    'svg'     => '<path d="M480 288c0-80.25-49.28-148.92-119.19-177.62L320 192V80a16 16 0 0 0-16-16h-96a16 16 0 0 0-16 16v112l-40.81-81.62C81.28 139.08 32 207.75 32 288v64h448zm16 96H16a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16h480a16 16 0 0 0 16-16v-32a16 16 0 0 0-16-16z"/>'
                ],
                [
                    'title'   => 'Verrouiller',
                    'status'  => 'À FAIRE',
                    'date'    => '',
                    'done'    => false,
                    'current' => false,
                    'viewBox' => '0 0 24 24',
                    'svg'     => '<path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/>'
                ],
                [
                    'title'   => 'Archiver',
                    'status'  => 'À FAIRE',
                    'date'    => '',
                    'done'    => false,
                    'current' => false,
                    'viewBox' => '0 0 24 24',
                    'svg'     => '<path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>'
                ]
            ];

            $creationWorkflowHtml = '<div style="margin-bottom: 10px; border-bottom: 1px dashed #eaeaea; padding-bottom: 10px;">';
            $creationWorkflowHtml .= '<div class="digirisk-mobile-extsign__title" style="margin-bottom: 10px; padding: 0 5px; display: flex; justify-content: space-between; align-items: center;">';
            $creationWorkflowHtml .= '<div style="color: #4a55d1; font-weight: bold; font-size: 1.1em; text-transform: uppercase;"><i class="fas fa-chart-line" style="margin-right: 5px;"></i> Avancement</div>';
            if (!empty($isEdit) && $object->ref) {
                $creationWorkflowHtml .= '<div style="font-size: 0.9em;">' . $object->getNomUrl(1) . '</div>';
            }
            $creationWorkflowHtml .= '</div>';
            $creationWorkflowHtml .= '<div style="display: flex; justify-content: space-between; overflow-x: auto; padding-bottom: 0px; margin: 0 5px;">';

            foreach ($stepsCreation as $index => $step) {
                if ($step['done']) {
                    $colorCircle = '#347244';
                    $bgColorBadg = '#e6f2e9';
                    $textColor   = '#2d6a3c';
                } elseif ($step['current']) {
                    // Orange for current
                    $colorCircle = '#347244'; 
                    $bgColorBadg = '#e6f2e9'; 
                    $textColor   = '#2d6a3c'; 
                } else {
                    $colorCircle = '#c94236'; 
                    $bgColorBadg = '#fbeae9'; 
                    $textColor   = '#c33a2f'; 
                }
                
                $isLast = ($index === count($stepsCreation) - 1);
                
                $creationWorkflowHtml .= '<div style="display: flex; flex-direction: column; align-items: center; min-width: 90px; text-align: center; position: relative; flex: 1; padding: 0 2px;">';
                
                $creationWorkflowHtml .= '<div style="font-size: 0.7em; font-weight: bold; color: #333; margin-bottom: 10px; height: 28px; line-height: 1.2; display: flex; align-items: flex-end; justify-content: center;">';
                $creationWorkflowHtml .= '<span>' . $step['title'] . '</span>';
                $creationWorkflowHtml .= '</div>';
                
                if (!$isLast) {
                    $creationWorkflowHtml .= '<div style="position: absolute; top: 58px; left: 50%; width: 100%; height: 0px; border-top: 2px dashed #999; z-index: 1;"></div>';
                }

                $creationWorkflowHtml .= '<div style="width: 40px; height: 40px; border-radius: 50%; border: 2px solid ' . $colorCircle . '; display: flex; align-items: center; justify-content: center; background: #fff; z-index: 2; margin-bottom: 10px;">';
                $fillAttr = (strpos($step['svg'], 'stroke=') !== false) ? 'fill="none" style="color: '.$colorCircle.';"' : 'fill="' . $colorCircle . '"';
                $creationWorkflowHtml .= '<svg viewBox="' . $step['viewBox'] . '" ' . $fillAttr . ' width="24px" height="24px">' . $step['svg'] . '</svg>';
                $creationWorkflowHtml .= '</div>';

                $statusText = $step['status'];
                if (!empty($step['date'])) {
                    $statusText .= '<br>' . $step['date'];
                }
                
                $creationWorkflowHtml .= '<div style="background: ' . $bgColorBadg . '; color: ' . $textColor . '; padding: 4px 6px; border-radius: 15px; font-size: 0.65em; font-weight: bold; display: inline-block; line-height: 1.2; text-align: center;">';
                $creationWorkflowHtml .= $statusText;
                $creationWorkflowHtml .= '</div>';

                $creationWorkflowHtml .= '</div>';
            }

            $creationWorkflowHtml .= '</div>';
            $creationWorkflowHtml .= '</div>';
            print $creationWorkflowHtml;
            ?>
            
            <div class="digirisk-mobile-field" style="margin-bottom: 15px;">
                <label><?php print $langs->trans('MobilePPMotif') != 'MobilePPMotif' ? $langs->trans('MobilePPMotif') : 'Motif de l\'intervention'; ?> *</label>
                <input type="text" name="label" class="digirisk-mobile-label" required placeholder="Ex: Maintenance annuelle" value="<?php print dol_escape_htmltag($prefill['label'] ?? ''); ?>">
            </div>

            <div class="digirisk-mobile-row">
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('DateStart'); ?> *</label>
                    <input type="date" name="date_start" class="digirisk-mobile-date-start" value="<?php print dol_escape_htmltag($prefill["date_start"]); ?>">
                </div>
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('DateEnd'); ?> *</label>
                    <input type="date" name="date_end" class="digirisk-mobile-date-end" value="<?php print dol_escape_htmltag($prefill["date_end"]); ?>">
                </div>
            </div>
            <div class="digirisk-mobile-help"><?php print $langs->trans('MobilePPMaxOneYearHint'); ?></div>
            <div class="digirisk-mobile-date-error hidden"></div>

            <div class="digirisk-mobile-field" style="margin-top: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                    <label style="margin: 0;"><?php print $langs->trans('MobilePPSchedules') != 'MobilePPSchedules' ? $langs->trans('MobilePPSchedules') : 'Horaires d\'intervention'; ?></label>
                    <div style="font-size: 0.85em; display: flex; gap: 15px; align-items: center;">
                        <a href="<?php print dol_buildpath('/admin/openinghours.php', 1); ?>" target="_blank" style="color: #4a55d1; text-decoration: none;"><i class="fas fa-cog"></i> <?php print $langs->trans('MobilePPConfigHours') != 'MobilePPConfigHours' ? $langs->trans('MobilePPConfigHours') : 'Configurer vos horaires ici'; ?></a>
                        <button type="button" id="btn-copy-company-hours" title="Copier les horaires de l'entreprise" style="background: none; border: 1px solid #4a55d1; color: #4a55d1; border-radius: 4px; padding: 4px 8px; cursor: pointer;"><i class="fas fa-copy"></i></button>
                    </div>
                </div>
                <div style="overflow-x: auto; margin-top: 10px;">
                    <table class="digirisk-mobile-table" style="width: 100%; border-collapse: collapse; text-align: center; font-size: 0.85em;">
                        <thead>
                            <tr style="background-color: #f4f5f9; border-bottom: 2px solid #ddd;">
                                <th style="padding: 10px; border: 1px solid #ddd;"></th>
                                <th style="padding: 10px; border: 1px solid #ddd;">Lun</th>
                                <th style="padding: 10px; border: 1px solid #ddd;">Mar</th>
                                <th style="padding: 10px; border: 1px solid #ddd;">Mer</th>
                                <th style="padding: 10px; border: 1px solid #ddd;">Jeu</th>
                                <th style="padding: 10px; border: 1px solid #ddd;">Ven</th>
                                <th style="padding: 10px; border: 1px solid #ddd;">Sam</th>
                                <th style="padding: 10px; border: 1px solid #ddd;">Dim</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding: 10px; border: 1px solid #ddd; background-color: #f4f5f9; font-weight: bold;">Matin</td>
                                <?php foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) { ?>
                                    <td style="padding: 4px; border: 1px solid #ddd; background: #fff;">
                                        <input type="text" name="schedule_<?php print $day; ?>_am" value="<?php print dol_escape_htmltag($prefill['schedule_'.$day.'_am']); ?>" style="width: 100%; border: none; text-align: center; background: transparent; padding: 6px;">
                                    </td>
                                <?php } ?>
                            </tr>
                            <tr>
                                <td style="padding: 10px; border: 1px solid #ddd; background-color: #f4f5f9; font-weight: bold;">Après-midi</td>
                                <?php foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) { ?>
                                    <td style="padding: 4px; border: 1px solid #ddd; background: #fff;">
                                        <input type="text" name="schedule_<?php print $day; ?>_pm" value="<?php print dol_escape_htmltag($prefill['schedule_'.$day.'_pm']); ?>" style="width: 100%; border: none; text-align: center; background: transparent; padding: 6px;">
                                    </td>
                                <?php } ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <script>
                document.getElementById('btn-copy-company-hours').addEventListener('click', function() {
                    var hours = {
                        'monday': <?php print json_encode($conf->global->MAIN_INFO_OPENINGHOURS_MONDAY ?? ''); ?>,
                        'tuesday': <?php print json_encode($conf->global->MAIN_INFO_OPENINGHOURS_TUESDAY ?? ''); ?>,
                        'wednesday': <?php print json_encode($conf->global->MAIN_INFO_OPENINGHOURS_WEDNESDAY ?? ''); ?>,
                        'thursday': <?php print json_encode($conf->global->MAIN_INFO_OPENINGHOURS_THURSDAY ?? ''); ?>,
                        'friday': <?php print json_encode($conf->global->MAIN_INFO_OPENINGHOURS_FRIDAY ?? ''); ?>,
                        'saturday': <?php print json_encode($conf->global->MAIN_INFO_OPENINGHOURS_SATURDAY ?? ''); ?>,
                        'sunday': <?php print json_encode($conf->global->MAIN_INFO_OPENINGHOURS_SUNDAY ?? ''); ?>
                    };
                    for (var day in hours) {
                        var str = hours[day].trim();
                        var am = '', pm = '';
                        if (str) {
                            var parts = str.split(' ');
                            if (parts.length > 0) am = parts[0];
                            if (parts.length > 1) pm = parts[1];
                        }
                        var inputAm = document.querySelector('input[name="schedule_' + day + '_am"]');
                        var inputPm = document.querySelector('input[name="schedule_' + day + '_pm"]');
                        if (inputAm) inputAm.value = am;
                        if (inputPm) inputPm.value = pm;
                    }
                });
                </script>
            </div>
        </div>

        
        <!-- Card 1: interior company, auto-signed by the connected responsible -->
        <div class="digirisk-mobile-card digirisk-mobile-extsign" style="margin-bottom: 10px;">
            <div class="digirisk-mobile-extsign__title" style="display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 10px; border-bottom: 1px solid #eaeaea;">
                <div style="text-transform: uppercase; font-weight: bold; font-size: 0.9em; color: #4a55d1; margin-top: 5px;"><i class="fas fa-user-tie"></i> <?php print $langs->trans('PreventionPlanUserCompany'); ?></div>
                
                <div class="digirisk-mobile-signature-saved <?php print $hasSignature ? '' : 'hidden'; ?>" style="text-align: right; margin-left: 10px;">
                    <div style="display: flex; align-items: center; background: #e6f2e9; color: #2d6a3c; padding: 4px 8px; border-radius: 15px; font-weight: bold; line-height: 1.2;">
                        <span style="font-size: 0.8em; text-transform: uppercase;"><i class="fas fa-check-circle" style="margin-right: 5px;"></i> <?php print $langs->trans('MobilePPSignatureSaved') != 'MobilePPSignatureSaved' ? $langs->trans('MobilePPSignatureSaved') : 'Signature enregistrée'; ?></span>
                        <div style="height: 24px; width: 24px; display: flex; align-items: center; justify-content: center; margin-left: 8px; background: #fff; border-radius: 4px; border: 1px solid #c3e6cb; cursor: pointer;" onclick="document.getElementById('modal-signature-preview').classList.add('modal-active');">
                            <i class="fas fa-signature"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="digirisk-mobile-extsign__who" style="display: flex; flex-direction: column; gap: 4px; font-size: 0.9em; margin-bottom: 0px; margin-top: 10px;">
                <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 10px;">
                    <div><span style="color: #666;">Tiers :</span> <span style="color: #000; font-weight: 500;"><?php print dol_escape_htmltag($mysoc->name); ?></span></div>
                    <div><span style="color: #666;">Siren :</span> <span style="color: #000;"><?php print dol_escape_htmltag($mysoc->idprof1); ?></span></div>
                </div>
                <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 10px;">
                    <div><span style="color: #666;">Resp.</span> <span style="color: #000; font-weight: 500;"><?php print dol_escape_htmltag($user->getFullName($langs)); ?></span></div>
                    <div><span style="color: #666;">Tél :</span> <span style="color: #000;"><?php print dol_escape_htmltag($user->office_phone); ?></span></div>
                </div>
                <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 10px;">
                    <div><span style="color: #666;">Mail :</span> <span style="color: #000;"><?php print dol_escape_htmltag($user->email); ?></span></div>
                    <div><span style="color: #666;">Poste :</span> <span style="color: #000;"><?php print dol_escape_htmltag($user->job ?? ''); ?></span></div>
                </div>
            </div>

            <div class="digirisk-mobile-signature">
                <div class="digirisk-mobile-signature-draw <?php print $hasSignature ? 'hidden' : ''; ?>" style="margin-top: 15px; border-top: 1px solid #eaeaea; padding-top: 15px;">
                    <div class="digirisk-mobile-signature-hint" style="font-size: 0.9em; margin-bottom: 10px; color: #666; font-weight: 500;"><i class="fas fa-pen-nib"></i> <?php print $langs->trans('MobilePPDrawSignatureHint'); ?></div>
                    <canvas class="digirisk-mobile-signature-canvas"></canvas>
                    <div class="digirisk-mobile-signature-actions">
                        <button type="button" class="digirisk-mobile-signature-clear wpeo-button button-grey"><?php print $langs->trans('MobilePPClearSignature'); ?></button>
                        <button type="button" class="digirisk-mobile-signature-save wpeo-button button-blue"><?php print $langs->trans('MobilePPSaveSignature'); ?></button>
                    </div>
                </div>
                <div class="digirisk-mobile-signature-status"></div>
            </div>

            <!-- Modal Signature Preview -->
            <div class="wpeo-modal" id="modal-signature-preview">
                <div class="modal-container">
                    <div class="modal-header">
                        <h2 class="modal-title"><?php print $langs->trans('MobilePPSignatureSaved') != 'MobilePPSignatureSaved' ? $langs->trans('MobilePPSignatureSaved') : 'Signature enregistrée'; ?></h2>
                        <div class="modal-close" onclick="document.getElementById('modal-signature-preview').classList.remove('modal-active');"><i class="fas fa-times"></i></div>
                    </div>
                    <div class="modal-content" style="text-align: center;">
                        <img class="digirisk-mobile-signature-preview-img" src="<?php print $hasSignature ? dol_escape_htmltag($savedSignature) : ''; ?>" style="max-width: 100%; border: 1px solid #ddd; border-radius: 8px; padding: 10px; background: #fff;" alt="">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="wpeo-button button-grey" onclick="document.getElementById('modal-signature-preview').classList.remove('modal-active');"><?php print $langs->trans('Close'); ?></button>
                        <button type="button" class="wpeo-button button-blue" onclick="document.getElementById('modal-signature-preview').classList.remove('modal-active'); document.querySelector('.digirisk-mobile-signature-saved').classList.add('hidden'); document.querySelector('.digirisk-mobile-signature-draw').classList.remove('hidden');"><i class="fas fa-edit"></i> <?php print $langs->trans('Modify'); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: exterior company, asked to sign by email. Three ways to fill it in: pick it in
             the third party list, resolve it by SIREN, or type everything for a company to create -->
        <div class="digirisk-mobile-card">
            <div class="digirisk-mobile-card__title"><i class="fas fa-industry"></i> <?php print $langs->trans('MobilePPExteriorCompany'); ?></div>
            
            <div class="digirisk-mobile-field" style="margin-bottom: 15px;">
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <label class="digirisk-mobile-check" style="margin-bottom: 0;">
                        <input type="radio" name="ext_society_mode" id="mode_existing" value="existing" <?php print (!empty($prefill['ext_society_id']) || empty($prefill['ext_society_name'])) ? 'checked' : ''; ?>>
                        <span style="font-weight: 500;"><?php print $langs->trans('MobilePPChooseExistingCompany'); ?></span>
                    </label>
                    <label class="digirisk-mobile-check" style="margin-bottom: 0;">
                        <input type="radio" name="ext_society_mode" id="mode_new" value="new" <?php print (empty($prefill['ext_society_id']) && !empty($prefill['ext_society_name'])) ? 'checked' : ''; ?>>
                        <span style="font-weight: 500;"><?php print $langs->trans('MobilePPOrFillManually') != 'MobilePPOrFillManually' ? $langs->trans('MobilePPOrFillManually') : 'Créer un nouveau tiers'; ?></span>
                    </label>
                </div>
            </div>

            <div id="section_existing_company" class="digirisk-mobile-field <?php print (empty($prefill['ext_society_id']) && !empty($prefill['ext_society_name'])) ? 'hidden' : ''; ?>">
                <label><?php print $langs->trans('MobilePPChooseExistingCompany'); ?></label>
                <!-- La loupe accompagne le choix du tiers : elle resout l'entreprise, que son
                     identifiant vienne de la liste ou du SIREN saisi plus bas -->
                <div class="digirisk-mobile-picker-row">
                    <?php print $form->select_company($prefill['ext_society_id'], 'ext_society_picker', '', '&nbsp;', 0, 0, [], 0, 'digirisk-mobile-society-select maxwidth500'); ?>
                    <button type="button" class="digirisk-mobile-siren-search wpeo-button button-blue" aria-label="<?php print dol_escape_htmltag($langs->trans('Search')); ?>"><i class="fas fa-search"></i></button>
                </div>
            </div>

            <div id="section_new_company" class="<?php print (!empty($prefill['ext_society_id']) || empty($prefill['ext_society_name'])) ? 'hidden' : ''; ?>">
                <div class="digirisk-mobile-row">
                    <div class="digirisk-mobile-field">
                        <label><?php print $langs->trans('CompanyName'); ?> *</label>
                        <input type="text" name="ext_society_name" class="digirisk-mobile-ext-society-name" value="<?php print dol_escape_htmltag($prefill["ext_society_name"]); ?>">
                    </div>
                    <div class="digirisk-mobile-field">
                        <label><?php print $langs->trans('MobileSirenOrSiret'); ?> *</label>
                        <input type="text" name="siren" class="digirisk-mobile-siren-input" inputmode="numeric" autocomplete="off" maxlength="20" placeholder="<?php print dol_escape_htmltag($langs->trans('MobileSirenOrSiretPlaceholder')); ?>" value="<?php print dol_escape_htmltag($prefill["siren"]); ?>" pattern="[\d\s]{9,20}" title="SIREN/SIRET (9 ou 14 chiffres)">
                    </div>
                </div>
                <div class="digirisk-mobile-siren-result"></div>

                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('Email'); ?></label>
                    <input type="email" name="ext_society_email" class="digirisk-mobile-ext-society-email" autocomplete="off" value="<?php print dol_escape_htmltag($prefill["ext_society_email"]); ?>" pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}">
                </div>
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('Address'); ?></label>
                    <textarea name="ext_society_address" class="digirisk-mobile-ext-society-address" rows="2"><?php print dol_escape_htmltag($prefill["ext_society_address"]); ?></textarea>
                </div>
                <div class="digirisk-mobile-row">
                    <div class="digirisk-mobile-field">
                        <label><?php print $langs->trans('Zip'); ?></label>
                        <input type="text" name="ext_society_zip" class="digirisk-mobile-ext-society-zip" inputmode="numeric" autocomplete="off" value="<?php print dol_escape_htmltag($prefill["ext_society_zip"]); ?>">
                    </div>
                    <div class="digirisk-mobile-field">
                        <label><?php print $langs->trans('Town'); ?></label>
                        <input type="text" name="ext_society_town" class="digirisk-mobile-ext-society-town" autocomplete="off" value="<?php print dol_escape_htmltag($prefill["ext_society_town"]); ?>">
                    </div>
                </div>
            </div>

            <div class="digirisk-mobile-subtitle"><?php print $langs->trans('MobilePPResponsibleToSign'); ?></div>

            <div class="digirisk-mobile-field digirisk-mobile-contact-picker hidden">
                <label><?php print $langs->trans('MobilePPChooseExistingContact'); ?></label>
                <select class="digirisk-mobile-contact-select">
                    <option value=""><?php print $langs->trans('MobilePPNewContact'); ?></option>
                </select>
            </div>
            <div class="digirisk-mobile-row">
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('Lastname'); ?> *</label>
                    <input type="text" name="resp_lastname" class="digirisk-mobile-resp-lastname" value="<?php print dol_escape_htmltag($prefill["resp_lastname"]); ?>">
                </div>
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('Firstname'); ?> *</label>
                    <input type="text" name="resp_firstname" class="digirisk-mobile-resp-firstname" value="<?php print dol_escape_htmltag($prefill["resp_firstname"]); ?>">
                </div>
            </div>
            <div class="digirisk-mobile-row">
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('Email'); ?> *</label>
                    <input type="email" name="resp_email" class="digirisk-mobile-resp-email" autocomplete="off" value="<?php print dol_escape_htmltag($prefill["resp_email"]); ?>" pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}" title="<?php print $langs->trans('MobilePPErrorInvalidEmail') ?: 'Veuillez saisir une adresse email valide.'; ?>">
                </div>
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('Phone'); ?></label>
                    <input type="tel" name="resp_phone" class="digirisk-mobile-resp-phone" autocomplete="off" value="<?php print dol_escape_htmltag($prefill["resp_phone"]); ?>" pattern="^(\+?\d{1,3}[\-.\s]?)?(\(?\d{1,4}\)?[\-.\s]?)?[\d\-.\s]{5,15}$" title="<?php print $langs->trans('MobilePPErrorInvalidPhone') ?: 'Veuillez saisir un numéro de téléphone valide.'; ?>">
                </div>
            </div>
            <div class="digirisk-mobile-help"><?php print $langs->trans('MobilePPEmailForSignatureHelp'); ?></div>
        </div>

        <!-- Prior formalities: CSSCT intervention then joint prior inspection, in that order,
             like the classic prevention plan card (native cssct_intervention / prior_visit_* fields) -->
        <div class="digirisk-mobile-card">
            <div class="digirisk-mobile-card__title"><i class="fas fa-clipboard-check"></i> <?php print $langs->trans('MobilePPPriorFormalities'); ?></div>
            <label class="digirisk-mobile-check">
                <input type="checkbox" name="cssct_intervention" value="1"<?php print !empty($prefill['cssct_intervention']) ? ' checked' : ''; ?>>
                <span><?php print $langs->trans('CSSCTIntervention'); ?></span>
            </label>
            <label class="digirisk-mobile-check">
                <input type="checkbox" name="prior_visit_bool" class="digirisk-mobile-prior-visit-toggle" value="1"<?php print !empty($prefill['prior_visit_bool']) ? ' checked' : ''; ?>>
                <span><?php print $langs->trans('MobilePPPriorVisitDone'); ?></span>
            </label>
            <div class="digirisk-mobile-prior-visit-detail <?php print !empty($prefill['prior_visit_bool']) ? '' : 'hidden'; ?>">
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('PriorVisitDate'); ?></label>
                    <input type="date" name="prior_visit_date" value="<?php print dol_escape_htmltag($prefill['prior_visit_date']); ?>">
                </div>
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('PriorVisitText'); ?></label>
                    <textarea name="prior_visit_text" rows="2" placeholder="<?php print dol_escape_htmltag($langs->trans('MobilePPPriorVisitTextPlaceholder')); ?>"><?php print dol_escape_htmltag($prefill['prior_visit_text']); ?></textarea>
                </div>
            </div>
        </div>

        <?php
        // Tags of the plan (native preventionplan category type). Tant qu'aucune categorie n'est
        // declaree, le multiselect s'affichait vide sans rien dire : on annonce l'absence et on
        // donne le lien pour en creer une plutot que de laisser chercher.
        if (isModEnabled('categorie')) {
            $planTagOptions = $form->select_all_categories('digiriskpreventionplan', '', 'parent', 64, 0, 1);
            $planTagOptions = is_array($planTagOptions) ? $planTagOptions : [];
        ?>
        <div class="digirisk-mobile-card">
            <div class="digirisk-mobile-card__title"><i class="fas fa-tags"></i> <?php print $langs->trans('Categories'); ?></div>
            <?php if (!empty($planTagOptions)) {
                print $form->multiselectarray('categories', $planTagOptions, $prefill['categories'], '', 0, 'digirisk-mobile-tags-select minwidth500 width100p');
            } else { ?>
            <div class="digirisk-mobile-empty">
                <i class="fas fa-info-circle"></i>
                <span><?php print $langs->trans('MobilePPNoTagAvailable'); ?></span>
            </div>
            <a class="digirisk-mobile-empty__action" href="<?php print DOL_URL_ROOT . '/categories/card.php?action=create&type=preventionplan'; ?>" target="_blank">
                <i class="fas fa-plus-circle"></i> <?php print $langs->trans('MobilePPCreateTag'); ?>
            </a>
            <?php } ?>
        </div>
        <?php } ?>

        <!-- Card 4: risks — one self-contained block per danger category (description, photos, protections) -->
        <div class="digirisk-mobile-card">
            <div class="digirisk-mobile-card__title"><i class="fas fa-exclamation-triangle"></i> <?php print $langs->trans('MobilePPRisks'); ?></div>
            <div class="digirisk-mobile-risk-list digirisk-mobile-risk-list--blocks">
                <?php
                // Edit mode: render the already selected risks with the very same fragment the JS clones
                foreach ($prefill['risks'] as $prefillRiskIndex => $prefillRisk) {
                    if (!isset($dangerMap[$prefillRisk['category']])) {
                        continue;
                    }
                    $prefillRiskCat = $dangerMap[$prefillRisk['category']];

                    $blockIndex       = (int) $prefillRiskIndex;
                    $blockPosition    = $prefillRisk['category'];
                    $blockName        = $prefillRiskCat['name'];
                    $blockThumbnail   = DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $prefillRiskCat['thumbnail_name'] . '.png';
                    $blockDescription = $prefillRisk['description'];
                    $blockCompanyEu   = $prefillRisk['company_eu'];
                    $blockCompanyEe   = $prefillRisk['company_ee'];

                    // Edit mode: bring the photos already attached to the risk into the temporary
                    // upload directory, so the media block manages them like the new ones
                    digiriskMobileSeedRiskPhotos($object->element, $object->ref, (int) $prefillRisk['category'], $uploadToken, $blockIndex);
                    $blockUploadSubDir = digiriskMobileRiskUploadSubDir($uploadToken, $blockIndex);

                    $blockProtections = [];
                    foreach ($prefillRisk['protections'] as $prefillProtection) {
                        if (!isset($protectionMap[$prefillProtection['position']])) {
                            continue;
                        }
                        $blockProtections[] = [
                            'position'  => $prefillProtection['position'],
                            'thumbnail' => DOL_URL_ROOT . '/custom/digiriskdolibarr/img/' . $protectionMap[$prefillProtection['position']]['name_thumbnail'],
                            'name'      => $protectionMap[$prefillProtection['position']]['name'],
                            'comment'   => $prefillProtection['comment'],
                        ];
                    }

                    include __DIR__ . '/preventionplan_mobile_risk_block.tpl.php';
                }
                ?>
            </div>
            <div class="digirisk-mobile-risk-empty <?php print empty($prefill['risks']) ? '' : 'hidden'; ?>"><?php print $langs->trans('MobilePPNoRiskYet'); ?></div>
            <button type="button" class="digirisk-mobile-risk-add wpeo-button button-blue"><i class="fas fa-plus-circle"></i> <?php print $langs->trans('MobilePPAddRisk'); ?></button>
        </div>

        <!-- Card 6: required certifications (CACES, permits...) -->
        <div class="digirisk-mobile-card">
            <div class="digirisk-mobile-card__title"><i class="fas fa-id-badge"></i> <?php print $langs->trans('MobilePPCertifications'); ?></div>
            <div class="digirisk-mobile-cert-picker-row">
                <select id="digirisk_cert_picker" class="digirisk-mobile-cert-picker">
                    <option value=""><?php print $langs->trans('Select'); ?></option>
                    <?php foreach (digiriskGetCertificationOptions() as $certCode => $certLabel) { ?>
                        <option value="<?php print dol_escape_htmltag($certCode); ?>"><?php print dol_escape_htmltag($certLabel); ?></option>
                    <?php } ?>
                </select>
                <button type="button" class="digirisk-mobile-cert-add wpeo-button button-blue"><i class="fas fa-plus-circle"></i> <?php print $langs->trans('MobilePPAddCertification'); ?></button>
            </div>
            <?php print ajax_combobox('digirisk_cert_picker'); ?>
            <div class="digirisk-mobile-cert-list">
                <?php
                // Edit mode: render the already selected certifications exactly like the JS does
                $prefillCertOptions = digiriskGetCertificationOptions(false);
                foreach ($prefill['certifications'] as $prefillCertIndex => $prefillCert) {
                    $prefillCertLabel = $prefillCertOptions[$prefillCert['code']] ?? $prefillCert['code'];
                ?>
                <div class="digirisk-mobile-cert-item" data-code="<?php print dol_escape_htmltag($prefillCert['code']); ?>">
                    <span class="digirisk-mobile-cert-item-label"><?php print dol_escape_htmltag($prefillCertLabel); ?></span>
                    <input type="hidden" name="cert_code[<?php print (int) $prefillCertIndex; ?>]" value="<?php print dol_escape_htmltag($prefillCert['code']); ?>">
                    <label class="digirisk-mobile-cert-item-mandatory">
                        <input type="checkbox" name="cert_mandatory[<?php print (int) $prefillCertIndex; ?>]" value="1"<?php print !empty($prefillCert['mandatory']) ? ' checked' : ''; ?>>
                        <span><?php print $langs->trans('MobilePPMandatory'); ?></span>
                    </label>
                    <button type="button" class="digirisk-mobile-cert-item-delete"><i class="fas fa-trash"></i></button>
                </div>
                <?php } ?>
            </div>
        </div>

        <button type="submit" class="digirisk-mobile-submit wpeo-button button-blue no-load">
            <i class="fas <?php print !empty($isEdit) ? 'fa-save' : 'fa-plus-circle'; ?>"></i>
            <?php print !empty($isEdit) ? $langs->trans('Save') : $langs->trans('MobilePPSubmit'); ?>
        </button>

        <!-- Risk picker modal -->
        <div class="digirisk-mobile-risk-modal hidden">
            <div class="digirisk-mobile-risk-modal__overlay"></div>
            <div class="digirisk-mobile-risk-modal__dialog">
                <div class="digirisk-mobile-risk-modal__header">
                    <span><?php print $langs->trans('MobilePPRiskModalTitle'); ?></span>
                    <button type="button" class="digirisk-mobile-risk-modal-close"><i class="fas fa-times"></i></button>
                </div>
                <div class="digirisk-mobile-risk-modal__grid">
                    <?php
                    if (!empty($dangerCategories)) {
                        foreach ($dangerCategories as $dangerCategory) {
                            $thumb = DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $dangerCategory['thumbnail_name'] . '.png';
                            ?>
                            <div class="digirisk-mobile-risk-option" data-position="<?php print dol_escape_htmltag($dangerCategory['position']); ?>" data-name="<?php print dol_escape_htmltag($dangerCategory['name']); ?>" data-thumbnail="<?php print dol_escape_htmltag($thumb); ?>">
                                <img src="<?php print $thumb; ?>" alt="">
                                <span><?php print dol_escape_htmltag($dangerCategory['name']); ?></span>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Protection picker modal (signalisation OBLIGATION / EPI pictos) -->
        <div class="digirisk-mobile-protection-modal hidden">
            <div class="digirisk-mobile-protection-modal__overlay"></div>
            <div class="digirisk-mobile-risk-modal__dialog">
                <div class="digirisk-mobile-risk-modal__header">
                    <span><?php print $langs->trans('MobilePPProtectionModalTitle'); ?></span>
                    <button type="button" class="digirisk-mobile-protection-modal-close"><i class="fas fa-times"></i></button>
                </div>
                <div class="digirisk-mobile-risk-modal__grid">
                    <?php
                    foreach ($signalisationCategories as $signalisationCategory) {
                        if (strpos($signalisationCategory['name_thumbnail'], 'OBLIGATION/') !== 0) {
                            continue; // keep only the protection (mandatory EPI) pictos
                        }
                        $thumb = DOL_URL_ROOT . '/custom/digiriskdolibarr/img/' . $signalisationCategory['name_thumbnail'];
                        ?>
                        <div class="digirisk-mobile-protection-option" data-position="<?php print dol_escape_htmltag($signalisationCategory['position']); ?>" data-name="<?php print dol_escape_htmltag($signalisationCategory['name']); ?>" data-thumbnail="<?php print dol_escape_htmltag($thumb); ?>">
                            <img src="<?php print $thumb; ?>" alt="">
                            <span><?php print dol_escape_htmltag($signalisationCategory['name']); ?></span>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Delete confirmation: a risk block carries its photos and its protections, losing it by
             mistake on a phone is easy and there is no undo before the form is submitted -->
        <div class="digirisk-mobile-confirm-modal hidden">
            <div class="digirisk-mobile-confirm-modal__overlay digirisk-mobile-confirm-cancel"></div>
            <div class="digirisk-mobile-risk-modal__dialog">
                <div class="digirisk-mobile-risk-modal__header">
                    <span><?php print $langs->trans('MobilePPDeleteRiskTitle'); ?></span>
                    <button type="button" class="digirisk-mobile-confirm-cancel"><i class="fas fa-times"></i></button>
                </div>
                <div class="digirisk-mobile-confirm-modal__body"></div>
                <div class="digirisk-mobile-confirm-modal__actions">
                    <button type="button" class="digirisk-mobile-confirm-cancel wpeo-button button-grey"><?php print $langs->trans('Cancel'); ?></button>
                    <button type="button" class="digirisk-mobile-confirm-delete wpeo-button button-red"><i class="fas fa-trash"></i> <?php print $langs->trans('Delete'); ?></button>
                </div>
            </div>
        </div>

        <?php
        /*
         * Fragments the JavaScript clones when the user adds a risk or a protection.
         * Rendered with the very same templates as the server-side rows, so both stay in sync;
         * only the row indexes are placeholders the JS replaces.
         */
        ?>
        <template class="digirisk-mobile-risk-block-template"><?php
            $blockIndex        = '__RISKINDEX__';
            $blockPosition     = '';
            $blockName         = '';
            $blockThumbnail    = '';
            $blockDescription  = '';
            // A risk added on site concerns both companies until the user says otherwise
            $blockCompanyEu    = 1;
            $blockCompanyEe    = 1;
            $blockUploadSubDir = digiriskMobileRiskUploadSubDir($uploadToken, $blockIndex);
            $blockProtections  = [];
            include __DIR__ . '/preventionplan_mobile_risk_block.tpl.php';

            // The media block creates its upload directory as it renders: drop the one the
            // placeholder index just created, the real ones are created block by block
            dol_delete_dir_recursive($conf->digiriskdolibarr->dir_output . '/' . $blockUploadSubDir);
        ?></template>
        <template class="digirisk-mobile-protection-row-template"><?php
            $rowRiskIndex = '__RISKINDEX__';
            $rowIndex     = '__INDEX__';
            $rowPosition  = '';
            $rowThumbnail = '';
            $rowName      = '';
            $rowComment   = '';
            include __DIR__ . '/preventionplan_mobile_protection_row.tpl.php';
        ?></template>
    </form>
</div>
<?php
// Photo editor the Saturne media block opens on every shot (crop, rotate, annotate)
$langs->load('medias@saturne');
include dol_buildpath('/saturne/core/tpl/medias/photo_editor_modal.tpl.php');


?>
<script <?php print (function_exists("getNonce") ? "nonce=\"".getNonce()."\"" : ""); ?>>
$(document).ready(function() {
    function toggleSocietyMode() {
        if ($("#mode_new").is(":checked")) {
            $("#section_existing_company").addClass("hidden");
            $("#section_new_company").removeClass("hidden");
            // Clear the select if they switch to new, so we don`t accidentally submit ext_society_id
            $(".digirisk-mobile-ext-society-id").val(0);
        } else {
            $("#section_existing_company").removeClass("hidden");
            $("#section_new_company").addClass("hidden");
        }
    }
    
    $(document).on("change", "input[name=\"ext_society_mode\"]", toggleSocietyMode);
});
</script>
<?php

