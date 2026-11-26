<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/frontend/digiriskdolibarr_mobile_spread_people.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   People of the public spread on the mobile screen of the object: who signed, who is still
 *          pending, and under each one the documents and answers they gave, as at the bottom of the
 *          spread page. The documents go through document.php, which checks the read right again.
 *          Shown from the creation of the object: before anybody joined, it already says which documents
 *          each person will be asked for, instead of leaving nothing where one looks for who signed.
 *          Once somebody signed, the attendance sheet PDF of the spread can be downloaded from here.
 *          Expects: $conf, $langs, $spreadPeople (digiriskMobileGetSpreadPeople()),
 *                   $spreadPeopleShowDetails (the user may read the object), $spreadPeopleRisksLabel,
 *                   $spreadPeopleShowSigninSheet (the user may read DoliLetter documents, optional).
 */

if (empty($spreadPeople['available'])) {
    return;
}

$spreadDocumentBaseUrl = DOL_URL_ROOT . '/document.php?modulepart=digiriskdolibarr&entity=' . ((int) $conf->entity) . '&attachment=0&file=';
?>
<div class="digirisk-mobile-card digirisk-mobile-spread-people">
    <div class="digirisk-mobile-spread-people__title">
        <span><i class="fas fa-users"></i> <?php print $langs->trans('MobileSpreadPeopleTitle'); ?></span>
        <?php if (!empty($spreadPeople['people'])) { ?>
        <span class="digirisk-mobile-spread-people__count"><?php print $langs->trans('MobileSpreadPeopleCount', $spreadPeople['signed_count'], count($spreadPeople['people'])); ?></span>
        <?php } ?>
    </div>

    <?php if (!empty($spreadPeople['certifications'])) { ?>
    <div class="digirisk-mobile-spread-people__requested">
        <span class="digirisk-mobile-spread-people__requested-label"><i class="fas fa-id-badge"></i> <?php print $langs->trans('MobileSpreadPeopleRequested'); ?></span>
        <?php foreach ($spreadPeople['certifications'] as $requestedCertification) { ?>
        <span class="digirisk-mobile-spread-people__requested-item">
            <?php print dol_escape_htmltag($requestedCertification['label']); ?>
            <?php if ($requestedCertification['mandatory']) { ?>
            <span class="digirisk-mobile-spread-people__state digirisk-mobile-spread-people__state--mandatory"><?php print $langs->trans('MobilePPMandatory'); ?></span>
            <?php } ?>
        </span>
        <?php } ?>
    </div>
    <?php } ?>

    <?php if (empty($spreadPeople['people'])) { ?>
    <p class="digirisk-mobile-spread-people__empty"><?php print $langs->trans('MobileSpreadPeopleEmpty'); ?></p>
    <?php } else { ?>
    <ul class="digirisk-mobile-spread-people__list">
        <?php foreach ($spreadPeople['people'] as $spreadPerson) {
            $spreadSignatory  = $spreadPerson['signatory'];
            $spreadName       = trim($spreadSignatory->firstname . ' ' . $spreadSignatory->lastname);
            $spreadFileCount  = array_sum(array_map(function (array $certState) {
                return count($certState['files']);
            }, $spreadPerson['cert_states']));
            $spreadHasDetails = $spreadPeopleShowDetails && (dol_strlen($spreadSignatory->email) || dol_strlen($spreadSignatory->phone) || $spreadPeople['risk_count'] > 0 || !empty($spreadPerson['cert_states'])); ?>
        <li class="digirisk-mobile-spread-people__item">
            <div class="digirisk-mobile-spread-people__head">
                <span class="digirisk-mobile-spread-people__name"><?php print dol_escape_htmltag(dol_strlen($spreadName) ? $spreadName : $spreadSignatory->email); ?></span>
                <?php if ($spreadPerson['signed']) { ?>
                <span class="digirisk-mobile-spread-people__status digirisk-mobile-spread-people__status--signed">
                    <i class="fas fa-check-circle"></i> <?php print $langs->trans('MobilePPExtAlreadySigned', dol_print_date($spreadSignatory->signature_date, 'dayhour', 'tzuser')); ?>
                </span>
                <?php } else { ?>
                <span class="digirisk-mobile-spread-people__status">
                    <i class="fas fa-hourglass-half"></i> <?php print $langs->trans('MobileNotSignedYet'); ?>
                </span>
                <?php } ?>
            </div>

            <?php if ($spreadHasDetails) { ?>
            <details class="digirisk-mobile-spread-people__details">
                <summary>
                    <i class="fas fa-folder-open"></i> <?php print $langs->trans('MobileSpreadPeopleDetails'); ?>
                    <?php if ($spreadFileCount > 0) { ?>
                    <span class="digirisk-mobile-spread-people__badge"><?php print $spreadFileCount; ?></span>
                    <?php } ?>
                </summary>

                <?php if (dol_strlen($spreadSignatory->email) || dol_strlen($spreadSignatory->phone)) { ?>
                <div class="digirisk-mobile-spread-people__line">
                    <?php if (dol_strlen($spreadSignatory->email)) { ?>
                    <span><i class="fas fa-envelope"></i> <?php print dol_escape_htmltag($spreadSignatory->email); ?></span>
                    <?php } ?>
                    <?php if (dol_strlen($spreadSignatory->phone)) { ?>
                    <span><i class="fas fa-phone"></i> <?php print dol_escape_htmltag($spreadSignatory->phone); ?></span>
                    <?php } ?>
                </div>
                <?php } ?>

                <?php if ($spreadPeople['risk_count'] > 0) { ?>
                <div class="digirisk-mobile-spread-people__line">
                    <span><i class="fas fa-check-square"></i> <?php print $langs->trans('MobileSpreadPeopleRisksRead', $langs->transnoentities($spreadPeopleRisksLabel), $spreadPerson['risks_read'], $spreadPeople['risk_count']); ?></span>
                </div>
                <?php } ?>

                <?php foreach ($spreadPerson['cert_states'] as $certState) {
                    if (!empty($certState['not_concerned'])) {
                        $certStateKey   = 'not-concerned';
                        $certStateLabel = $langs->trans('MobileSpreadCertNotConcerned');
                    } elseif (!empty($certState['has_file'])) {
                        $certStateKey   = 'provided';
                        $certStateLabel = $langs->trans('MobileSpreadCertProvided');
                    } else {
                        $certStateKey   = !empty($certState['mandatory']) ? 'missing' : 'none';
                        $certStateLabel = $langs->trans('MobileSpreadCertNotProvided');
                    }
                    $certFileDir = $spreadPeople['cert_sub_dir'] . '/' . ((int) $spreadSignatory->id) . '/' . dol_sanitizeFileName($certState['code']) . '/'; ?>
                <div class="digirisk-mobile-spread-people__cert">
                    <div class="digirisk-mobile-spread-people__cert-label">
                        <i class="fas fa-id-badge"></i>
                        <span><?php print dol_escape_htmltag($certState['label']); ?></span>
                        <span class="digirisk-mobile-spread-people__state digirisk-mobile-spread-people__state--<?php print $certStateKey; ?>"><?php print $certStateLabel; ?></span>
                    </div>
                    <?php if (!empty($certState['files'])) { ?>
                    <div class="digirisk-mobile-spread-people__files">
                        <?php foreach ($certState['files'] as $certFile) {
                            $certFileUrl = $spreadDocumentBaseUrl . urlencode($certFileDir . $certFile['path']);
                            if (!empty($certFile['is_image'])) {
                                $certThumbUrl = $spreadDocumentBaseUrl . urlencode($certFileDir . (!empty($certFile['thumb_path']) ? $certFile['thumb_path'] : $certFile['path'])); ?>
                        <a class="digirisk-mobile-spread-people__photo" href="<?php print dol_escape_htmltag($certFileUrl); ?>" target="_blank" rel="noopener" title="<?php print dol_escape_htmltag($certFile['name']); ?>">
                            <img src="<?php print dol_escape_htmltag($certThumbUrl); ?>" alt="<?php print dol_escape_htmltag($certFile['name']); ?>" loading="lazy">
                        </a>
                            <?php } else { ?>
                        <a class="digirisk-mobile-spread-people__file" href="<?php print dol_escape_htmltag($certFileUrl); ?>" target="_blank" rel="noopener">
                            <i class="fas fa-file-alt"></i> <span><?php print dol_escape_htmltag($certFile['name']); ?></span>
                        </a>
                            <?php }
                        } ?>
                    </div>
                    <?php } ?>
                </div>
                <?php } ?>
            </details>
            <?php } ?>
        </li>
        <?php } ?>
    </ul>
    <?php } ?>

    <?php if (!empty($spreadPeopleShowSigninSheet) && $spreadPeople['signed_count'] > 0 && dol_strlen($spreadPeople['signin_sheet_url'])) { ?>
    <a class="digirisk-mobile-spread-people__download" href="<?php print dol_escape_htmltag($spreadPeople['signin_sheet_url']); ?>" target="_blank" rel="noopener">
        <i class="fas fa-file-pdf"></i> <?php print $langs->trans('MobileSpreadPeopleSigninSheet'); ?>
    </a>
    <?php } ?>
</div>
