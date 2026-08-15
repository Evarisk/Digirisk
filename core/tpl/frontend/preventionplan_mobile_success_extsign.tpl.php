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
 */

/**
 * \file    core/tpl/frontend/preventionplan_mobile_success_extsign.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Signature de l'entreprise exterieure sur l'ecran de succes mobile.
 *          Le mail part tout seul a la creation : cet ecran dit s'il est parti et a qui, permet de
 *          le renvoyer, et surtout de faire signer la personne sur le telephone quand elle est la.
 *          Expects: $langs, $object, $ppExtSignatory, $ppExtSigned, $ppExtEmailSent, $ppExtSignatureUrl.
 */

global $langs;

if (empty($ppExtSignatory)) {
    return;
}

$ppExtName = trim($ppExtSignatory->firstname . ' ' . $ppExtSignatory->lastname);
?>
<div class="digirisk-mobile-card digirisk-mobile-extsign" data-plan-id="<?php print (int) $object->id; ?>" data-error-mail="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorMailServerNotConfigured')); ?>">
    <div class="digirisk-mobile-extsign__title" style="display: flex; justify-content: space-between; align-items: center;">
        <div><i class="fas fa-hard-hat"></i> Entreprise Extérieure ( EE )</div>
        <?php if ($ppExtSigned) { ?>
        <div style="font-size: 0.65em; background: #e6f2e9; color: #2d6a3c; padding: 4px 8px; border-radius: 15px; font-weight: bold; line-height: 1.2;">
            <i class="fas fa-check"></i> Signé le <?php print dol_print_date($ppExtSignatory->signature_date, 'dayhour'); ?>
        </div>
        <?php } ?>
    </div>

    <div class="digirisk-mobile-extsign__who" style="display: flex; flex-direction: column; gap: 4px; font-size: 0.9em; margin-bottom: 12px; margin-top: 10px;">
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 10px;">
            <div><span style="color: #666;">Tiers :</span> <span style="color: #000; font-weight: 500;"><?php print !empty($thirdparty) ? dol_escape_htmltag($thirdparty->name) : ''; ?></span></div>
            <div><span style="color: #666;">Siren :</span> <span style="color: #000;"><?php print !empty($thirdparty) ? dol_escape_htmltag($thirdparty->idprof1) : ''; ?></span></div>
        </div>
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 10px;">
            <div><span style="color: #666;">Resp.</span> <span style="color: #000; font-weight: 500;"><?php print dol_escape_htmltag(dol_strlen($ppExtName) ? $ppExtName : $langs->trans('ExtSocietyResponsible')); ?></span></div>
            <div><span style="color: #666;">Tél :</span> <span style="color: #000;"><?php print !empty($ppExtSignatory->phone) ? dol_escape_htmltag($ppExtSignatory->phone) : ''; ?></span></div>
        </div>
        <?php if (dol_strlen($ppExtSignatory->email) || !empty($ppExtSignatory->job)) { ?>
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 10px;">
            <div><span style="color: #666;">Mail :</span> <span style="color: #000;"><?php print dol_escape_htmltag($ppExtSignatory->email); ?></span></div>
            <div><span style="color: #666;">Poste :</span> <span style="color: #000;"><?php print !empty($ppExtSignatory->job) ? dol_escape_htmltag($ppExtSignatory->job) : ''; ?></span></div>
        </div>
        <?php } ?>
    </div>

    <?php if (!$ppExtSigned) { ?>

    <div class="digirisk-mobile-extsign__status <?php print $ppExtEmailSent ? 'digirisk-mobile-extsign__status--sent' : 'digirisk-mobile-extsign__status--pending'; ?>">
        <i class="fas <?php print $ppExtEmailSent ? 'fa-paper-plane' : 'fa-exclamation-circle'; ?>"></i>
        <span>
            <?php
            print $ppExtEmailSent
                ? $langs->trans('MobilePPExtEmailSentOn', dol_escape_htmltag($ppExtSignatory->email), dol_print_date($ppExtSignatory->last_email_sent_date, 'dayhour'))
                : $langs->trans('MobilePPExtEmailNotSent');
            ?>
        </span>
    </div>

    <?php if (dol_strlen($ppExtSignatureUrl)) { ?>
    <div class="digirisk-mobile-extsign__actions">
        <!-- La personne est sur place : elle signe sur ce telephone, sans passer par sa boite mail -->
        <a class="digirisk-mobile-success__button digirisk-mobile-success__button--primary" href="<?php print $ppExtSignatureUrl; ?>">
            <i class="fas fa-signature"></i> <?php print $langs->trans('MobilePPExtSignNow'); ?>
        </a>
        <button type="button" class="digirisk-mobile-success__button digirisk-mobile-extsign__resend">
            <i class="fas fa-paper-plane"></i> <?php print $ppExtEmailSent ? $langs->trans('MobilePPExtResendEmail') : $langs->trans('MobilePPExtSendEmail'); ?>
        </button>
    </div>

    <div class="digirisk-mobile-extsign__link copy-signatureurl-container">
        <div class="digirisk-mobile-share__link">
            <a href="<?php print $ppExtSignatureUrl; ?>" target="_blank"><?php print dol_escape_htmltag($ppExtSignatureUrl); ?></a>
            <i class="fas fa-clipboard copy-signatureurl" data-signature-url="<?php print dol_escape_htmltag($ppExtSignatureUrl); ?>" title="<?php print dol_escape_htmltag($langs->trans('ClickToCopyToClipboard')); ?>"></i>
        </div>
        <span class="copied-to-clipboard" style="display: none;"><?php print $langs->trans('CopiedToClipboard'); ?></span>
    </div>

    <?php if ($ppExtEmailSent) {
        $sql = "SELECT label, note_private FROM " . MAIN_DB_PREFIX . "actioncomm WHERE elementtype = 'preventionplan' AND fk_element = " . (int)$object->id . " AND type_code = 'AC_EMAIL' ORDER BY datep DESC LIMIT 1";
        $resql = $db->query($sql);
        if ($resql && $db->num_rows($resql) > 0) {
            $actionObj = $db->fetch_object($resql);
            print '<div style="margin-top: 20px; font-size: 0.9em; color: #555;">';
            print '<strong>Mail envoyé à :</strong> ' . dol_escape_htmltag($ppExtSignatory->email) . '<br>';
            print '<strong>Titre :</strong> ' . dol_escape_htmltag($actionObj->label) . '<br>';
            print '<strong>Sujet :</strong><br><div style="border: 1px solid #ccc; padding: 10px; margin-top: 5px; background: #fafafa;">' . dol_htmlcleanlastbr($actionObj->note_private) . '</div>';
            print '</div>';
        }
    } ?>

    <?php } ?>

    <?php } ?>
</div>
