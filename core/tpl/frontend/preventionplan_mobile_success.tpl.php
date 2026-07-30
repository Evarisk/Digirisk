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

global $db, $langs, $signatory, $user;

$successTitle = $langs->trans('MobilePPSuccessTitle');
$successRef   = $object->ref;
$successLabel = $object->label;

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

$successFacts = [$langs->trans('MobileSuccessInteriorSigned')];
if ($ppHasDocument) {
    $successFacts[] = $langs->trans('MobileSuccessDocumentGenerated');
}
if ($ppExtSigned) {
    $successFacts[] = $langs->trans('MobileSuccessExteriorSigned');
} elseif ($ppExtEmailSent) {
    $successFacts[] = $langs->trans('MobileSuccessExteriorNotified');
} else {
    $successFacts[] = $langs->trans('MobileSuccessExteriorNotNotified');
}

// Public spread page, shareable straight away with the people who have to join and sign this plan.
// Only relevant when DoliLetter (which serves the spread public page) is enabled.
$successShareUrl = isModEnabled('doliletter')
    ? dol_buildpath('/custom/doliletter/public/spread/add_spread.php', 3) . '?id=' . $object->id . '&object_type=digiriskdolibarr_preventionplan'
    : '';

// Bloc propre au plan de prevention, insere par l'ecran de succes commun
$successExtraBlockFile = __DIR__ . '/preventionplan_mobile_success_extsign.tpl.php';

$successViewUrl   = dol_buildpath('/custom/digiriskdolibarr/view/preventionplan/preventionplan_card.php', 1) . '?id=' . $object->id;
$successViewLabel  = $langs->trans('MobilePPViewPlan');
$successAgainUrl   = $_SERVER['PHP_SELF'];
$successAgainLabel = $langs->trans('MobilePPCreateAnother');

require __DIR__ . '/digiriskdolibarr_mobile_success.tpl.php';
