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

global $langs, $user;

?>
<div class="digirisk-mobile-card digirisk-mobile-extsign">
    <div class="digirisk-mobile-extsign__title">
        <i class="fas fa-file-signature"></i> SIGNATURE DE L'ENTREPRISE UTILISATRICE
    </div>
    <div class="digirisk-mobile-extsign__who">
        <span class="digirisk-mobile-extsign__name"><?php print dol_escape_htmltag($user->getFullName($langs)); ?></span>
        <?php if (dol_strlen($user->email)) { ?>
        <span class="digirisk-mobile-extsign__email"><?php print dol_escape_htmltag($user->email); ?></span>
        <?php } ?>
        <?php if (dol_strlen($user->office_phone)) { ?>
        <span class="digirisk-mobile-extsign__email"><?php print dol_escape_htmltag($user->office_phone); ?></span>
        <?php } ?>
    </div>
    <div class="digirisk-mobile-extsign__status digirisk-mobile-extsign__status--done">
        <i class="fas fa-check-circle"></i>
        <span><?php print $langs->trans('MobileSuccessInteriorSigned'); ?></span>
    </div>
</div>
<?php

if (empty($ppExtSignatory)) {
    return;
}

$ppExtName = trim($ppExtSignatory->firstname . ' ' . $ppExtSignatory->lastname);
?>
<div class="digirisk-mobile-card digirisk-mobile-extsign" data-plan-id="<?php print (int) $object->id; ?>">
    <div class="digirisk-mobile-extsign__title">
        <i class="fas fa-file-signature"></i> <?php print $langs->trans('MobilePPExtSignatureTitle'); ?>
    </div>

    <div class="digirisk-mobile-extsign__who">
        <span class="digirisk-mobile-extsign__name"><?php print dol_escape_htmltag(dol_strlen($ppExtName) ? $ppExtName : $langs->trans('ExtSocietyResponsible')); ?></span>
        <?php if (dol_strlen($ppExtSignatory->email)) { ?>
        <span class="digirisk-mobile-extsign__email"><?php print dol_escape_htmltag($ppExtSignatory->email); ?></span>
        <?php } ?>
    </div>

    <?php if ($ppExtSigned) { ?>
    <div class="digirisk-mobile-extsign__status digirisk-mobile-extsign__status--done">
        <i class="fas fa-check-circle"></i>
        <span><?php print $langs->trans('MobilePPExtAlreadySigned', dol_print_date($ppExtSignatory->signature_date, 'dayhour')); ?></span>
    </div>
    <?php } else { ?>

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
            <i class="fas fa-paper-plane"></i> <?php print $langs->trans('MobilePPExtResendEmail'); ?>
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
