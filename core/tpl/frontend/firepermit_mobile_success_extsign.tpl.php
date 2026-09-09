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
 * \file    core/tpl/frontend/firepermit_mobile_success_extsign.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Signature de l'entreprise exterieure sur l'ecran de succes mobile du permis de feu.
 *          Cet ecran dit si le mail est parti et a qui, permet de le renvoyer, et surtout de faire
 *          signer la personne sur le telephone quand elle est la.
 *          Expects: $db, $langs, $object, $thirdparty, $fpExtSignatory, $fpExtSigned,
 *                   $fpExtEmailSent, $fpExtSignatureUrl.
 */

global $db, $langs;

if (empty($fpExtSignatory)) {
    return;
}

require_once __DIR__ . '/../../../lib/digiriskdolibarr_mobile.lib.php';

// Etat des signatures au moment du rendu : le script compare le sien a celui du serveur
$fpSignatureState    = digiriskMobileGetSignatureState($db, $object);
$fpSignatureStateUrl = dol_buildpath('/custom/digiriskdolibarr/core/ajax/mobile_firepermit_signature_state.php', 1);

$fpExtName = trim($fpExtSignatory->firstname . ' ' . $fpExtSignatory->lastname);
?>
<div class="digirisk-mobile-card digirisk-mobile-extsign digirisk-mobile-extsign--firepermit" data-plan-id="<?php print (int) $object->id; ?>"
     data-error-mail="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorMailServerNotConfigured')); ?>"
     data-signature-state-url="<?php print dol_escape_htmltag($fpSignatureStateUrl); ?>"
     data-signature-state="<?php print dol_escape_htmltag($fpSignatureState['state']); ?>">
    <div class="digirisk-mobile-extsign__title digirisk-mobile-extsign__title--split">
        <div><i class="fas fa-hard-hat"></i> <?php print $langs->trans('FirePermitExteriorCompany'); ?></div>
        <?php if ($fpExtSigned) { ?>
        <div class="digirisk-mobile-extsign__signed">
            <i class="fas fa-check"></i> <?php print $langs->trans('MobilePPExtAlreadySigned', dol_print_date($fpExtSignatory->signature_date, 'dayhour')); ?>
        </div>
        <?php } ?>
    </div>

    <div class="digirisk-mobile-extsign__who">
        <div class="digirisk-mobile-extsign__line">
            <div><span><?php print $langs->trans('ThirdParty'); ?> :</span> <strong><?php print !empty($thirdparty) ? dol_escape_htmltag($thirdparty->name) : ''; ?></strong></div>
            <div><span><?php print $langs->transcountry('ProfId1Short', !empty($thirdparty) ? $thirdparty->country_code : ''); ?> :</span> <?php print !empty($thirdparty) ? dol_escape_htmltag($thirdparty->idprof1) : ''; ?></div>
        </div>
        <div class="digirisk-mobile-extsign__line">
            <div><span><?php print $langs->trans('MobileResponsibleShort'); ?></span> <strong><?php print dol_escape_htmltag(dol_strlen($fpExtName) ? $fpExtName : $langs->trans('ExtSocietyResponsible')); ?></strong></div>
            <div><span><?php print $langs->trans('PhoneShort'); ?> :</span> <?php print !empty($fpExtSignatory->phone) ? dol_escape_htmltag($fpExtSignatory->phone) : ''; ?></div>
        </div>
        <?php if (dol_strlen($fpExtSignatory->email) || !empty($fpExtSignatory->job)) { ?>
        <div class="digirisk-mobile-extsign__line">
            <div><span><?php print $langs->trans('Email'); ?> :</span> <?php print dol_escape_htmltag($fpExtSignatory->email); ?></div>
            <div><span><?php print $langs->trans('PostOrFunction'); ?> :</span> <?php print !empty($fpExtSignatory->job) ? dol_escape_htmltag($fpExtSignatory->job) : ''; ?></div>
        </div>
        <?php } ?>
    </div>

    <?php if (!$fpExtSigned) { ?>
    <div class="digirisk-mobile-extsign__status <?php print $fpExtEmailSent ? 'digirisk-mobile-extsign__status--sent' : 'digirisk-mobile-extsign__status--pending'; ?>">
        <i class="fas <?php print $fpExtEmailSent ? 'fa-paper-plane' : 'fa-exclamation-circle'; ?>"></i>
        <span>
            <?php
            print $fpExtEmailSent
                ? $langs->trans('MobilePPExtEmailSentOn', dol_escape_htmltag($fpExtSignatory->email), dol_print_date($fpExtSignatory->last_email_sent_date, 'dayhour'))
                : $langs->trans('MobilePPExtEmailNotSent');
            ?>
        </span>
    </div>
    <?php } ?>

    <?php if (dol_strlen($fpExtSignatureUrl) || $fpExtSigned) { ?>
    <div class="digirisk-mobile-extsign__actions">
        <?php if (!$fpExtSigned) { ?>
        <!-- La personne est sur place : elle signe sur ce telephone, sans passer par sa boite mail -->
        <a class="digirisk-mobile-success__button digirisk-mobile-success__button--primary" href="<?php print $fpExtSignatureUrl; ?>" target="_blank">
            <i class="fas fa-signature"></i> <?php print $langs->trans('MobilePPExtSignNow'); ?>
        </a>
        <button type="button" class="digirisk-mobile-success__button digirisk-mobile-extsign__resend">
            <i class="fas fa-paper-plane"></i> <?php print $fpExtEmailSent ? $langs->trans('MobilePPExtResendEmail') : $langs->trans('MobilePPExtSendEmail'); ?>
        </button>
        <?php } ?>

        <?php if ($object->status < FirePermit::STATUS_LOCKED) { ?>
            <?php if ($fpExtSigned) { ?>
                <a href="<?php print $_SERVER['PHP_SELF']; ?>?action=lock_mobile&plan_id=<?php print $object->id; ?>&token=<?php print newToken(); ?>" class="digirisk-mobile-success__button digirisk-mobile-success__button--primary">
                    <i class="fas fa-lock"></i> <?php print $langs->trans('MobileLock'); ?>
                </a>
            <?php } else { ?>
                <button type="button" class="digirisk-mobile-success__button digirisk-mobile-success__button--disabled" disabled title="<?php print dol_escape_htmltag($langs->trans('MobileFPLockNeedsSignature')); ?>">
                    <i class="fas fa-lock"></i> <?php print $langs->trans('MobileLock'); ?>
                </button>
            <?php } ?>
        <?php } ?>
    </div>

    <?php if (!$fpExtSigned) { ?>
    <div class="digirisk-mobile-extsign__link copy-signatureurl-container">
        <div class="digirisk-mobile-share__link">
            <a href="<?php print $fpExtSignatureUrl; ?>" target="_blank"><?php print dol_escape_htmltag($fpExtSignatureUrl); ?></a>
            <i class="fas fa-clipboard copy-signatureurl" data-signature-url="<?php print dol_escape_htmltag($fpExtSignatureUrl); ?>" title="<?php print dol_escape_htmltag($langs->trans('ClickToCopyToClipboard')); ?>"></i>
        </div>
        <span class="copied-to-clipboard" style="display: none;"><?php print $langs->trans('CopiedToClipboard'); ?></span>
    </div>

    <?php if ($fpExtEmailSent) {
        // Rappel de ce qui est parti, pour repondre sur place a "je n'ai rien recu".
        // Les evenements poses avant que le module ne suffixe le type d'element portent la forme courte.
        $sql   = 'SELECT label, note_private FROM ' . MAIN_DB_PREFIX . 'actioncomm';
        $sql  .= " WHERE elementtype IN ('firepermit', 'firepermit@digiriskdolibarr')";
        $sql  .= ' AND fk_element = ' . ((int) $object->id);
        $sql  .= " AND type_code = 'AC_EMAIL' ORDER BY datep DESC LIMIT 1";
        $resql = $db->query($sql);
        if ($resql && $db->num_rows($resql) > 0) {
            $sentEmail = $db->fetch_object($resql);
            ?>
            <div class="digirisk-mobile-extsign__mail">
                <div><strong><?php print $langs->trans('MobileMailSentTo'); ?> :</strong> <?php print dol_escape_htmltag($fpExtSignatory->email); ?></div>
                <div><strong><?php print $langs->trans('Title'); ?> :</strong> <?php print dol_escape_htmltag($sentEmail->label); ?></div>
                <div><strong><?php print $langs->trans('Message'); ?> :</strong></div>
                <div class="digirisk-mobile-extsign__mail-body"><?php print dol_htmlcleanlastbr($sentEmail->note_private); ?></div>
            </div>
            <?php
        }
    } ?>
    <?php } ?>
    <?php } ?>
</div>
