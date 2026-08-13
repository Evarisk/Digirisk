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

$successTitle = '';
$nomUrl = $object->getNomUrl(1);
$customLinkText = '<span style="font-size: 0.55em; font-weight: normal; color: #444; vertical-align: middle;">Réf. </span>';
$customLinkText .= '<span style="vertical-align: middle;">' . $object->ref . '-' . dol_escape_htmltag($societyName) . '</span></a>';

$successRefHtml = str_replace('>' . $object->ref . '</a>', '>' . $customLinkText, $nomUrl);
$successLabel = '';

global $mysoc;

$successExtraInfoHtml = '<div style="margin-top: 5px; font-size: 0.9em; line-height: 1.6; color: #333;">';
$successExtraInfoHtml .= '<div style="margin-bottom: 15px;">';
$successExtraInfoHtml .= '<div style="display: flex; gap: 15px; margin-bottom: 8px;">';
$successExtraInfoHtml .= '<div style="color: #666;">Libellé</div>';
$successExtraInfoHtml .= '<div style="color: #000;">' . dol_escape_htmltag($object->label) . '</div>';
$successExtraInfoHtml .= '</div>';
$successExtraInfoHtml .= '<div style="display: flex; gap: 30px;">';
$successExtraInfoHtml .= '<div>Date début : <span style="color: #22427c;">' . dol_print_date($object->date_start, 'day') . '</span></div>';
$successExtraInfoHtml .= '<div>Date de fin : <span style="color: #22427c;">' . dol_print_date($object->date_end, 'day') . '</span></div>';
$successExtraInfoHtml .= '</div>';
$successExtraInfoHtml .= '</div>';
$successExtraInfoHtml .= '<div><span style="color: #22427c;">Entreprise Utilisatrice (EI)</span> : ' . dol_escape_htmltag($mysoc->name) . '</div>';
$successExtraInfoHtml .= '<div><span style="color: #22427c;">Responsable</span> : <span style="color: #22427c;">' . dol_escape_htmltag($user->getFullName($langs)) . '</span></div>';
$successExtraInfoHtml .= '<div style="display: flex; gap: 30px;">';
$successExtraInfoHtml .= '<div>Email : <span style="color: #22427c;">' . dol_escape_htmltag($user->email) . '</span></div>';
$successExtraInfoHtml .= '<div>Tél : ' . dol_escape_htmltag($user->office_phone) . '</div>';
$successExtraInfoHtml .= '</div></div>';

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

$successFacts = [
    $langs->trans('MobilePPSuccessTitle'),
    $langs->trans('MobileSuccessInteriorSignedOn', dol_print_date($object->date_creation, 'day'))
];

if ($ppExtEmailSent) {
    $successFacts[] = $langs->trans('MobileSuccessPlanSentByEmailOn', dol_print_date($ppExtSignatory->last_email_sent_date, 'day'));
}

if ($ppExtSigned) {
    $successFacts[] = $langs->trans('MobileSuccessExteriorSignedOn', dol_print_date($ppExtSignatory->signature_date ?? dol_now(), 'day'));
} else {
    $successFacts[] = [
        'text' => $langs->trans('MobileSuccessExteriorPendingSignature'),
        'status' => 'pending'
    ];
}

// Public spread page, shareable straight away with the people who have to join and sign this plan.
// Only relevant when DoliLetter (which serves the spread public page) is enabled.
$successShareUrl = isModEnabled('doliletter')
    ? dol_buildpath('/custom/doliletter/public/spread/add_spread.php', 3) . '?id=' . $object->id . '&object_type=digiriskdolibarr_preventionplan'
    : '';

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
