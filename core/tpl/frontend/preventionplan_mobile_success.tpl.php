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
 * \file    core/tpl/frontend/preventionplan_mobile_success.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Success screen of the mobile prevention plan interface.
 *          Expects: $db, $langs, $signatory, $user, $object (fetched prevention plan).
 */

global $db, $langs, $signatory, $user, $digiriskresources;

if (empty($digiriskresources)) {
    require_once DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/class/digiriskresources.class.php';
    $digiriskresources = new DigiriskResources($db);
}

$extSociety = $digiriskresources->fetchResourcesFromObject('ExtSociety', $object);
$societyName = '';
if (!empty($extSociety->id)) {
    require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
    $thirdparty = new Societe($db);
    if ($thirdparty->fetch($extSociety->id) > 0) {
        $societyName = $thirdparty->name;
    }
}

$svgClipboard = '<svg viewBox="0 0 100 100" width="40px" height="40px">
    <path d="M30 15 H20 C14.5 15 10 19.5 10 25 V85 C10 90.5 14.5 95 20 95 H80 C85.5 95 90 90.5 90 85 V25 C90 19.5 85.5 15 80 15 H70" fill="none" stroke="#25355a" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
    <rect x="35" y="5" width="30" height="15" rx="5" fill="none" stroke="#25355a" stroke-width="8"/>
    <circle cx="50" cy="15" r="3" fill="#25355a"/>
    <line x1="25" y1="40" x2="75" y2="40" stroke="#7e8df6" stroke-width="6" stroke-linecap="round"/>
    <line x1="25" y1="55" x2="75" y2="55" stroke="#7e8df6" stroke-width="6" stroke-linecap="round"/>
    <line x1="25" y1="70" x2="50" y2="70" stroke="#7e8df6" stroke-width="6" stroke-linecap="round"/>
    <circle cx="75" cy="75" r="25" fill="#ffffff"/>
    <circle cx="75" cy="75" r="20" fill="#4a55d1"/>
    <path d="M65 75 L72 82 L85 65" fill="none" stroke="#ffffff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>';
$svgTarget = '<svg viewBox="0 0 512 512" fill="#4a55d1" width="20px" height="20px"><path d="M256 512c141.4 0 256-114.6 256-256S397.4 0 256 0 0 114.6 0 256s114.6 256 256 256zm0-448c105.9 0 192 86.1 192 192s-86.1 192-192 192S64 361.9 64 256 150.1 64 256 64zm0 320c70.6 0 128-57.4 128-128s-57.4-128-128-128-128 57.4-128 128 57.4 128 128 128zm0-192c35.3 0 64 28.7 64 64s-28.7 64-64 64-64-28.7-64-64 28.7-64 64-64z"/></svg>';
$svgCalendar = '<svg viewBox="0 0 448 512" fill="#4a55d1" width="20px" height="20px"><path d="M400 64h-48V16c0-8.8-7.2-16-16-16h-32c-8.8 0-16 7.2-16 16v48H160V16c0-8.8-7.2-16-16-16h-32c-8.8 0-16 7.2-16 16v48H48C21.5 64 0 85.5 0 112v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V112c0-26.5-21.5-48-48-48zM48 96h352c8.8 0 16 7.2 16 16v48H32v-48c0-8.8 7.2-16 16-16zm352 384H48c-8.8 0-16-7.2-16-16V192h384v272c0 8.8-7.2 16-16 16z"/></svg>';

$successTitle = '';



$successRefHtml = '';

$successLabel = ''; 

global $mysoc;

// Motif + Dates replacing the ExtraInfoHtml EU block layout
$successExtraInfoHtml = '<div style="display: flex; width: 100%; margin-bottom: 15px;">';
$successExtraInfoHtml .= '<div style="display: flex; align-items: center; flex: 3; border-right: 1px solid #eaeaea; padding-right: 15px;">';
$successExtraInfoHtml .= '<div style="width: 36px; height: 36px; border-radius: 50%; background: #eef2ff; display: flex; align-items: center; justify-content: center; margin-right: 10px; flex-shrink: 0;">' . $svgTarget . '</div>';
$successExtraInfoHtml .= '<div><div style="font-size: 0.7em; color: #666; font-weight: bold; text-transform: uppercase; margin-bottom: 3px;">Motif de l\'intervention</div>';
$successExtraInfoHtml .= '<div style="font-size: 0.9em; color: #000; font-weight: bold; word-break: break-word;">' . (!empty($object->label) ? dol_escape_htmltag($object->label) : 'Aucun motif renseigné') . '</div></div></div>';
$successExtraInfoHtml .= '<div style="display: flex; align-items: center; flex: 1; padding-left: 15px;">';
$successExtraInfoHtml .= '<div style="width: 36px; height: 36px; border-radius: 50%; background: #eef2ff; display: flex; align-items: center; justify-content: center; margin-right: 10px; flex-shrink: 0;">' . $svgCalendar . '</div>';
$successExtraInfoHtml .= '<div style="display: flex; flex-direction: column; gap: 8px;">';
$successExtraInfoHtml .= '<div><div style="font-size: 0.7em; color: #666; font-weight: bold; text-transform: uppercase; margin-bottom: 1px;">Date début</div>';
$successExtraInfoHtml .= '<div style="font-size: 0.9em; color: #4a55d1; font-weight: bold;">' . (!empty($object->date_start) ? dol_print_date($object->date_start, 'day') : '-') . '</div></div>';
$successExtraInfoHtml .= '<div><div style="font-size: 0.7em; color: #666; font-weight: bold; text-transform: uppercase; margin-bottom: 1px;">Date de fin</div>';
$successExtraInfoHtml .= '<div style="font-size: 0.9em; color: #4a55d1; font-weight: bold;">' . (!empty($object->date_end) ? dol_print_date($object->date_end, 'day') : '-') . '</div></div>';
$successExtraInfoHtml .= '</div></div></div>';

$euBadgeHtml = '<div style="font-size: 0.65em; background: #e6f2e9; color: #2d6a3c; padding: 4px 8px; border-radius: 15px; font-weight: bold; line-height: 1.2;"><i class="fas fa-check" style="margin-right: 5px;"></i> Signé le ' . dol_print_date($object->date_creation, 'dayhour') . '</div>';

$successEuBlockHtml = '<div class="digirisk-mobile-card digirisk-mobile-extsign" style="margin-top: 15px;">';
$successEuBlockHtml .= '<div class="digirisk-mobile-extsign__title" style="display: flex; justify-content: space-between; align-items: center;">';
$successEuBlockHtml .= '<div><i class="fas fa-user-tie"></i> Entreprise Utilisatrice ( EU )</div>';
$successEuBlockHtml .= $euBadgeHtml;
$successEuBlockHtml .= '</div>';
$successEuBlockHtml .= '<div class="digirisk-mobile-extsign__who" style="display: flex; flex-direction: column; gap: 4px; font-size: 0.9em; margin-bottom: 12px; margin-top: 10px;">';
$successEuBlockHtml .= '<div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 10px;">';
$successEuBlockHtml .= '<div><span style="color: #666;">Tiers :</span> <span style="color: #000; font-weight: 500;">' . dol_escape_htmltag($mysoc->name) . '</span></div>';
$successEuBlockHtml .= '<div><span style="color: #666;">Siren :</span> <span style="color: #000;">' . dol_escape_htmltag($mysoc->idprof1) . '</span></div>';
$successEuBlockHtml .= '</div>';
$successEuBlockHtml .= '<div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 10px;">';
$successEuBlockHtml .= '<div><span style="color: #666;">Resp.</span> <span style="color: #000; font-weight: 500;">' . dol_escape_htmltag($user->getFullName($langs)) . '</span></div>';
$successEuBlockHtml .= '<div><span style="color: #666;">Tél :</span> <span style="color: #000;">' . dol_escape_htmltag($user->office_phone) . '</span></div>';
$successEuBlockHtml .= '</div>';
$successEuBlockHtml .= '<div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 10px;">';
$successEuBlockHtml .= '<div><span style="color: #666;">Mail :</span> <span style="color: #000;">' . dol_escape_htmltag($user->email) . '</span></div>';
$successEuBlockHtml .= '<div><span style="color: #666;">Poste :</span> <span style="color: #000;">' . dol_escape_htmltag($user->job ?? '') . '</span></div>';
$successEuBlockHtml .= '</div>';
$successEuBlockHtml .= '</div></div>';

// Entreprise exterieure : etat reel de la demande de signature. L'envoi automatique peut avoir
// echoue et personne ne devait s'en apercevoir : l'ecran l'affiche et propose d'y remedier.
$ppExtSignatory   = null;
$ppExtSignatories = $signatory->fetchSignatory('ExtSocietyResponsible', $object->id, 'preventionplan');
if (is_array($ppExtSignatories) && !empty($ppExtSignatories)) {
    $ppExtSignatory = array_shift($ppExtSignatories);
}

$ppExtSigned       = !empty($ppExtSignatory) && !empty($ppExtSignatory->signature);
$ppExtEmailSent    = !empty($ppExtSignatory) && !empty($ppExtSignatory->last_email_sent_date);
$ppExtSignatureUrl = !empty($ppExtSignatory) ? digiriskGetPreventionPlanSignatureUrl($ppExtSignatory) : '';

// Le document est genere a la creation : le dire evite d'aller le verifier dans Dolibarr
$ppHasDocument = dol_is_dir($conf->digiriskdolibarr->dir_output . '/preventionplandocument/' . dol_sanitizeFileName($object->ref))
    && !empty(dol_dir_list($conf->digiriskdolibarr->dir_output . '/preventionplandocument/' . dol_sanitizeFileName($object->ref), 'files', 0, '\.pdf$'));

$workflowIcons = digiriskMobileWorkflowIcons();

$steps = [
    [
        'title'   => $langs->trans('MobilePPStepCreated'),
        'status'  => $langs->transnoentities('MobileStepDone'),
        'date'    => dol_print_date($object->date_creation, 'day'),
        'done'    => true,
        'viewBox' => $workflowIcons['created']['viewBox'],
        'svg'     => $workflowIcons['created']['svg'],
    ],
    [
        'title'   => $langs->trans('MobileStepUserCompanyResponsible'),
        'status'  => $langs->transnoentities('MobileStepSignedOn'),
        'date'    => dol_print_date($object->date_creation, 'day'),
        'done'    => true,
        'viewBox' => $workflowIcons['user']['viewBox'],
        'svg'     => $workflowIcons['user']['svg'],
    ],
    [
        'title'   => $langs->trans('MobileStepExteriorCompanyResponsible'),
        'status'  => $ppExtSigned ? $langs->transnoentities('MobileStepSignedOn') : $langs->transnoentities('MobileStepTodo'),
        'date'    => $ppExtSigned ? dol_print_date($ppExtSignatory->signature_date ?? dol_now(), 'day') : '',
        'done'    => $ppExtSigned,
        'viewBox' => $workflowIcons['company']['viewBox'],
        'svg'     => $workflowIcons['company']['svg'],
    ],
    [
        'title'   => $langs->trans('MobileStepLock'),
        'status'  => ($object->status >= PreventionPlan::STATUS_LOCKED) ? $langs->transnoentities('MobileStepDone') : $langs->transnoentities('MobileStepTodo'),
        'date'    => '',
        'done'    => ($object->status >= PreventionPlan::STATUS_LOCKED),
        'viewBox' => $workflowIcons['lock']['viewBox'],
        'svg'     => $workflowIcons['lock']['svg'],
    ],
    [
        'title'   => $langs->trans('MobileStepArchive'),
        'status'  => ($object->status >= PreventionPlan::STATUS_ARCHIVED) ? $langs->transnoentities('MobileStepDone') : $langs->transnoentities('MobileStepTodo'),
        'date'    => '',
        'done'    => ($object->status >= PreventionPlan::STATUS_ARCHIVED),
        'viewBox' => $workflowIcons['archive']['viewBox'],
        'svg'     => $workflowIcons['archive']['svg'],
    ],
];

$svgClipboard = '<svg viewBox="0 0 384 512" fill="#4a55d1" width="60px" height="60px"><path d="M336 64h-80c0-35.3-28.7-64-64-64s-64 28.7-64 64H48C21.5 64 0 85.5 0 112v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V112c0-26.5-21.5-48-48-48zM192 40c13.3 0 24 10.7 24 24s-10.7 24-24 24-24-10.7-24-24 10.7-24 24-24zm169.4 195.4l-128 128c-12.5 12.5-32.8 12.5-45.3 0l-64-64c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L216 300.7l105.4-105.4c12.5-12.5 32.8-12.5 45.3 0s12.5 32.8 0 45.3z"/></svg>';
$svgTarget = '<svg viewBox="0 0 512 512" fill="#4a55d1" width="24px" height="24px"><path d="M256 512c141.4 0 256-114.6 256-256S397.4 0 256 0 0 114.6 0 256s114.6 256 256 256zm0-448c105.9 0 192 86.1 192 192s-86.1 192-192 192S64 361.9 64 256 150.1 64 256 64zm0 320c70.6 0 128-57.4 128-128s-57.4-128-128-128-128 57.4-128 128 57.4 128 128 128zm0-192c35.3 0 64 28.7 64 64s-28.7 64-64 64-64-28.7-64-64 28.7-64 64-64z"/></svg>';
$svgCalendar = '<svg viewBox="0 0 448 512" fill="#4a55d1" width="24px" height="24px"><path d="M400 64h-48V16c0-8.8-7.2-16-16-16h-32c-8.8 0-16 7.2-16 16v48H160V16c0-8.8-7.2-16-16-16h-32c-8.8 0-16 7.2-16 16v48H48C21.5 64 0 85.5 0 112v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V112c0-26.5-21.5-48-48-48zM48 96h352c8.8 0 16 7.2 16 16v48H32v-48c0-8.8 7.2-16 16-16zm352 384H48c-8.8 0-16-7.2-16-16V192h384v272c0 8.8-7.2 16-16 16z"/></svg>';

$successCustomFactsHtml = digiriskMobileRenderWorkflow($steps, $object->getNomUrl(1));

$successRef = '';

$successExtraInfoHtml = $successCustomFactsHtml . $successExtraInfoHtml;
$successCustomFactsHtml = '';


// Public spread page, shareable straight away with the people who have to join and sign this plan.
// Only relevant when DoliLetter (which serves the spread public page) is enabled.
$successShareUrl = isModEnabled('doliletter')
    ? dol_buildpath('/custom/doliletter/public/spread/add_spread.php', 3) . '?id=' . $object->id . '&object_type=digiriskdolibarr_preventionplan'
    : '';

$successShareEnabled = $ppExtSigned && ($object->status >= PreventionPlan::STATUS_LOCKED);
$successShareDisabledText = 'La diffusion sera disponible dès que le plan sera signé par les responsables et verrouillé.';

// Bloc propre au plan de prevention, insere par l'ecran de succes commun
$successExtraBlockFile = __DIR__ . '/preventionplan_mobile_success_extsign.tpl.php';

if ($ppHasDocument) {
    // Generate view url for the PDF
    $pdfFiles = dol_dir_list($conf->digiriskdolibarr->dir_output . '/preventionplandocument/' . dol_sanitizeFileName($object->ref), 'files', 0, '\.pdf$');
    if (!empty($pdfFiles)) {
        $pdfFile = $pdfFiles[0];
        $filename = $pdfFile['name'];
        $successViewUrl = DOL_URL_ROOT . '/document.php?modulepart=digiriskdolibarr&entity=' . $conf->entity . '&file=' . urlencode('preventionplandocument/' . dol_sanitizeFileName($object->ref) . '/' . $filename);
        $successViewLabel = $langs->trans('MobileSuccessViewDocument');
    }
} else {
    $successViewUrl = dol_buildpath('/custom/digiriskdolibarr/view/preventionplan/preventionplan_card.php', 1) . '?id=' . $object->id;
    $successViewLabel  = $langs->trans('MobilePPViewPlan');
}
$successAgainUrl   = $_SERVER['PHP_SELF'];
$successAgainLabel = $langs->trans('MobilePPCreateAnother');

require __DIR__ . '/digiriskdolibarr_mobile_success.tpl.php';
