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
 * \file    core/tpl/frontend/firepermit_mobile_success.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Success screen of the mobile fire permit interface.
 *          Expects: $conf, $db, $langs, $mysoc, $signatory, $user, $object (fetched fire permit).
 */

global $conf, $db, $langs, $mysoc, $signatory, $user, $digiriskresources;

require_once __DIR__ . '/../../../lib/digiriskdolibarr_firepermit.lib.php';

if (empty($digiriskresources)) {
    require_once __DIR__ . '/../../../class/digiriskresources.class.php';
    $digiriskresources = new DigiriskResources($db);
}

// fetchResourcesFromObject() returns the resolved Societe for a single match, and 0 when there is none
$extSociety = $digiriskresources->fetchResourcesFromObject('ExtSociety', $object);
if (is_object($extSociety) && $extSociety->id > 0) {
    require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
    $thirdparty = new Societe($db);
    if ($thirdparty->fetch($extSociety->id) <= 0) {
        $thirdparty = null;
    }
} else {
    $thirdparty = null;
}

$svgTarget   = '<svg viewBox="0 0 512 512" fill="#4a55d1" width="20px" height="20px"><path d="M256 512c141.4 0 256-114.6 256-256S397.4 0 256 0 0 114.6 0 256s114.6 256 256 256zm0-448c105.9 0 192 86.1 192 192s-86.1 192-192 192S64 361.9 64 256 150.1 64 256 64zm0 320c70.6 0 128-57.4 128-128s-57.4-128-128-128-128 57.4-128 128 57.4 128 128 128zm0-192c35.3 0 64 28.7 64 64s-28.7 64-64 64-64-28.7-64-64 28.7-64 64-64z"/></svg>';
$svgCalendar = '<svg viewBox="0 0 448 512" fill="#4a55d1" width="20px" height="20px"><path d="M400 64h-48V16c0-8.8-7.2-16-16-16h-32c-8.8 0-16 7.2-16 16v48H160V16c0-8.8-7.2-16-16-16h-32c-8.8 0-16 7.2-16 16v48H48C21.5 64 0 85.5 0 112v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V112c0-26.5-21.5-48-48-48zM48 96h352c8.8 0 16 7.2 16 16v48H32v-48c0-8.8 7.2-16 16-16zm352 384H48c-8.8 0-16-7.2-16-16V192h384v272c0 8.8-7.2 16-16 16z"/></svg>';

$successTitle   = '';
$successRef     = '';
$successRefHtml = '';
$successLabel   = '';

// What the job is and when it takes place: the two facts the permit is read for on site
$successExtraInfoHtml  = '<div style="display: flex; width: 100%; margin-bottom: 15px;">';
$successExtraInfoHtml .= '<div style="display: flex; align-items: center; flex: 3; border-right: 1px solid #eaeaea; padding-right: 15px;">';
$successExtraInfoHtml .= '<div style="width: 36px; height: 36px; border-radius: 50%; background: #eef2ff; display: flex; align-items: center; justify-content: center; margin-right: 10px; flex-shrink: 0;">' . $svgTarget . '</div>';
$successExtraInfoHtml .= '<div><div style="font-size: 0.7em; color: #666; font-weight: bold; text-transform: uppercase; margin-bottom: 3px;">' . $langs->trans('MobileFPMotif') . '</div>';
$successExtraInfoHtml .= '<div style="font-size: 0.9em; color: #000; font-weight: bold; word-break: break-word;">' . (dol_strlen($object->label) ? dol_escape_htmltag($object->label) : $langs->trans('MobileFPNoMotif')) . '</div></div></div>';
$successExtraInfoHtml .= '<div style="display: flex; align-items: center; flex: 1; padding-left: 15px;">';
$successExtraInfoHtml .= '<div style="width: 36px; height: 36px; border-radius: 50%; background: #eef2ff; display: flex; align-items: center; justify-content: center; margin-right: 10px; flex-shrink: 0;">' . $svgCalendar . '</div>';
$successExtraInfoHtml .= '<div style="display: flex; flex-direction: column; gap: 8px;">';
$successExtraInfoHtml .= '<div><div style="font-size: 0.7em; color: #666; font-weight: bold; text-transform: uppercase; margin-bottom: 1px;">' . $langs->trans('DateStart') . '</div>';
$successExtraInfoHtml .= '<div style="font-size: 0.9em; color: #4a55d1; font-weight: bold;">' . (!empty($object->date_start) ? dol_print_date($object->date_start, 'dayhour') : '-') . '</div></div>';
$successExtraInfoHtml .= '<div><div style="font-size: 0.7em; color: #666; font-weight: bold; text-transform: uppercase; margin-bottom: 1px;">' . $langs->trans('DateEnd') . '</div>';
$successExtraInfoHtml .= '<div style="font-size: 0.9em; color: #4a55d1; font-weight: bold;">' . (!empty($object->date_end) ? dol_print_date($object->date_end, 'dayhour') : '-') . '</div></div>';
$successExtraInfoHtml .= '</div></div></div>';

// Interior company: signed the moment the permit was created, with the reusable user signature
$successEuBlockHtml  = '<div class="digirisk-mobile-card digirisk-mobile-extsign" style="margin-top: 15px;">';
$successEuBlockHtml .= '<div class="digirisk-mobile-extsign__title digirisk-mobile-extsign__title--split">';
$successEuBlockHtml .= '<div><i class="fas fa-user-tie"></i> ' . $langs->trans('FirePermitUserCompany') . '</div>';
$successEuBlockHtml .= '<div class="digirisk-mobile-extsign__signed"><i class="fas fa-check"></i> ' . $langs->trans('MobilePPExtAlreadySigned', dol_print_date($object->date_creation, 'dayhour')) . '</div>';
$successEuBlockHtml .= '</div>';
$successEuBlockHtml .= '<div class="digirisk-mobile-extsign__who">';
$successEuBlockHtml .= '<div class="digirisk-mobile-extsign__line">';
$successEuBlockHtml .= '<div><span>' . $langs->trans('ThirdParty') . ' :</span> <strong>' . dol_escape_htmltag($mysoc->name) . '</strong></div>';
$successEuBlockHtml .= '<div><span>' . $langs->transcountry('ProfId1Short', $mysoc->country_code) . ' :</span> ' . dol_escape_htmltag($mysoc->idprof1) . '</div>';
$successEuBlockHtml .= '</div>';
$successEuBlockHtml .= '<div class="digirisk-mobile-extsign__line">';
$successEuBlockHtml .= '<div><span>' . $langs->trans('MobileResponsibleShort') . '</span> <strong>' . dol_escape_htmltag($user->getFullName($langs)) . '</strong></div>';
$successEuBlockHtml .= '<div><span>' . $langs->trans('PhoneShort') . ' :</span> ' . dol_escape_htmltag($user->office_phone) . '</div>';
$successEuBlockHtml .= '</div>';
$successEuBlockHtml .= '<div class="digirisk-mobile-extsign__line">';
$successEuBlockHtml .= '<div><span>' . $langs->trans('Email') . ' :</span> ' . dol_escape_htmltag($user->email) . '</div>';
$successEuBlockHtml .= '<div><span>' . $langs->trans('PostOrFunction') . ' :</span> ' . dol_escape_htmltag($user->job ?? '') . '</div>';
$successEuBlockHtml .= '</div>';
$successEuBlockHtml .= '</div></div>';

// Entreprise exterieure : etat reel de la demande de signature. L'envoi peut avoir echoue et
// personne ne devait s'en apercevoir : l'ecran l'affiche et propose d'y remedier.
$fpExtSignatory   = null;
$fpExtSignatories = $signatory->fetchSignatory('ExtSocietyResponsible', $object->id, 'firepermit');
if (is_array($fpExtSignatories) && !empty($fpExtSignatories)) {
    $fpExtSignatory = array_shift($fpExtSignatories);
}

$fpExtSigned       = !empty($fpExtSignatory) && !empty($fpExtSignatory->signature);
$fpExtEmailSent    = !empty($fpExtSignatory) && !empty($fpExtSignatory->last_email_sent_date);
$fpExtSignatureUrl = !empty($fpExtSignatory) ? digiriskGetFirePermitSignatureUrl($fpExtSignatory) : '';

// Le document est genere a la creation : le dire evite d'aller le verifier dans Dolibarr
$fpDocumentDir = $conf->digiriskdolibarr->dir_output . '/firepermitdocument/' . dol_sanitizeFileName($object->ref);
$fpPdfFiles    = dol_is_dir($fpDocumentDir) ? dol_dir_list($fpDocumentDir, 'files', 0, '\.pdf$') : [];

$workflowIcons = digiriskMobileWorkflowIcons();

$steps = [
    [
        'title'   => $langs->trans('MobileFPStepCreated'),
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
        'status'  => $fpExtSigned ? $langs->transnoentities('MobileStepSignedOn') : $langs->transnoentities('MobileStepTodo'),
        'date'    => $fpExtSigned ? dol_print_date($fpExtSignatory->signature_date ?? dol_now(), 'day') : '',
        'done'    => $fpExtSigned,
        'viewBox' => $workflowIcons['company']['viewBox'],
        'svg'     => $workflowIcons['company']['svg'],
    ],
    [
        'title'   => $langs->trans('MobileStepLock'),
        'status'  => ($object->status >= FirePermit::STATUS_LOCKED) ? $langs->transnoentities('MobileStepDone') : $langs->transnoentities('MobileStepTodo'),
        'date'    => '',
        'done'    => ($object->status >= FirePermit::STATUS_LOCKED),
        'viewBox' => $workflowIcons['lock']['viewBox'],
        'svg'     => $workflowIcons['lock']['svg'],
    ],
    [
        'title'   => $langs->trans('MobileStepArchive'),
        'status'  => ($object->status >= FirePermit::STATUS_ARCHIVED) ? $langs->transnoentities('MobileStepDone') : $langs->transnoentities('MobileStepTodo'),
        'date'    => '',
        'done'    => ($object->status >= FirePermit::STATUS_ARCHIVED),
        'viewBox' => $workflowIcons['archive']['viewBox'],
        'svg'     => $workflowIcons['archive']['svg'],
    ],
];

// Le bandeau d'avancement ouvre l'ecran, avant le motif et les dates
$successExtraInfoHtml = digiriskMobileRenderWorkflow($steps, $object->getNomUrl(1)) . $successExtraInfoHtml;

// Public spread page, shareable straight away with the people who have to join and sign this permit.
// Only relevant when DoliLetter (which serves the spread public page) is enabled.
$successShareUrl = isModEnabled('doliletter')
    ? dol_buildpath('/custom/doliletter/public/spread/add_spread.php', 3) . '?id=' . $object->id . '&object_type=digiriskdolibarr_firepermit'
    : '';

$successShareEnabled      = $fpExtSigned && ($object->status >= FirePermit::STATUS_LOCKED);
$successShareDisabledText = $langs->trans('MobileFPSpreadAvailableOnceSignedAndLocked');

// Bloc propre au permis de feu, insere par l'ecran de succes commun
$successExtraBlockFile = __DIR__ . '/firepermit_mobile_success_extsign.tpl.php';

if (!empty($fpPdfFiles)) {
    $successViewUrl   = DOL_URL_ROOT . '/document.php?modulepart=digiriskdolibarr&entity=' . $conf->entity . '&file=' . urlencode('firepermitdocument/' . dol_sanitizeFileName($object->ref) . '/' . $fpPdfFiles[0]['name']);
    $successViewLabel = $langs->trans('MobileSuccessViewDocument');
} else {
    $successViewUrl   = dol_buildpath('/custom/digiriskdolibarr/view/firepermit/firepermit_card.php', 1) . '?id=' . $object->id;
    $successViewLabel = $langs->trans('MobileFPViewPermit');
}
$successAgainUrl   = $_SERVER['PHP_SELF'];
$successAgainLabel = $langs->trans('MobileFPCreateAnother');

require __DIR__ . '/digiriskdolibarr_mobile_success.tpl.php';
