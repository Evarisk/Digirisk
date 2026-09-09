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
 * \file    core/tpl/frontend/digiriskdolibarr_pwa_list.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Search bar, result cards and pagination shared by the PWA list screens.
 *          Expects:
 *            $langs, $listRows (array of ['url','editUrl','ref','title','lines'=>[['icon','text']],'statusHtml'])
 *            $listSearch, $listStatus, $listStatusOptions, $listPage, $listTotalPages, $listTotal
 *            $listCreateUrl (string, empty when the user may not create)
 */

global $langs;

$listBaseUrl = $_SERVER['PHP_SELF'];

/**
 * Build a URL of the current screen keeping the search and status filters.
 *
 * @param  int    $page Target page
 * @return string       URL
 */
$listPageUrl = function (int $page) use ($listBaseUrl, $listSearch, $listStatus) {
    $params = ['page' => $page];
    if ($listSearch !== '') {
        $params['search'] = $listSearch;
    }
    if ($listStatus !== '') {
        $params['status'] = $listStatus;
    }

    return $listBaseUrl . '?' . http_build_query($params);
};
?>
<div class="digirisk-pwa-toolbar">
    <form method="GET" action="<?php print dol_escape_htmltag($listBaseUrl); ?>" class="digirisk-pwa-search">
        <div class="digirisk-pwa-search__field">
            <i class="fas fa-search"></i>
            <input type="search" name="search" value="<?php print dol_escape_htmltag($listSearch); ?>" placeholder="<?php print dol_escape_htmltag($langs->trans('Search')); ?>">
        </div>
        <select name="status" class="digirisk-pwa-search__status">
            <?php foreach ($listStatusOptions as $statusValue => $statusLabel) { ?>
                <option value="<?php print dol_escape_htmltag($statusValue); ?>"<?php print ((string) $statusValue === $listStatus) ? ' selected' : ''; ?>><?php print dol_escape_htmltag($statusLabel); ?></option>
            <?php } ?>
        </select>
        <button type="submit" class="digirisk-pwa-search__submit" aria-label="<?php print dol_escape_htmltag($langs->trans('Search')); ?>"><i class="fas fa-arrow-right"></i></button>
    </form>
    <?php if (!empty($listCreateUrl)) { ?>
        <a href="<?php print dol_escape_htmltag($listCreateUrl); ?>" class="digirisk-pwa-create" aria-label="<?php print dol_escape_htmltag($langs->trans('Add')); ?>"><i class="fas fa-plus"></i></a>
    <?php } ?>
</div>

<div class="digirisk-pwa-count"><?php print $langs->trans('PwaResultCount', $listTotal); ?></div>

<?php if (empty($listRows)) { ?>
    <div class="digirisk-pwa-empty">
        <i class="fas fa-inbox"></i>
        <div><?php print $langs->trans('NoRecordFound'); ?></div>
    </div>
<?php } else { ?>
    <div class="digirisk-pwa-cards">
        <?php foreach ($listRows as $listRow) { ?>
        <div class="digirisk-pwa-card">
            <a class="digirisk-pwa-card__main" href="<?php print dol_escape_htmltag($listRow['url']); ?>">
                <div class="digirisk-pwa-card__head">
                    <span class="digirisk-pwa-card__ref"><?php print dol_escape_htmltag($listRow['ref']); ?></span>
                    <span class="digirisk-pwa-card__status"><?php print $listRow['statusHtml']; ?></span>
                </div>
                <div class="digirisk-pwa-card__title"><?php print dol_escape_htmltag($listRow['title']); ?></div>
                <?php foreach ($listRow['lines'] as $listRowLine) { ?>
                    <div class="digirisk-pwa-card__line"><i class="fas <?php print dol_escape_htmltag($listRowLine['icon']); ?>"></i> <?php print dol_escape_htmltag($listRowLine['text']); ?></div>
                <?php } ?>
            </a>
        </div>
        <?php } ?>
    </div>

    <?php if ($listTotalPages > 1) { ?>
    <div class="digirisk-pwa-pagination">
        <?php if ($listPage > 0) { ?>
            <a href="<?php print dol_escape_htmltag($listPageUrl($listPage - 1)); ?>" class="digirisk-pwa-pagination__btn"><i class="fas fa-chevron-left"></i></a>
        <?php } else { ?>
            <span class="digirisk-pwa-pagination__btn disabled"><i class="fas fa-chevron-left"></i></span>
        <?php } ?>
        <span class="digirisk-pwa-pagination__page"><?php print ($listPage + 1) . ' / ' . $listTotalPages; ?></span>
        <?php if ($listPage < ($listTotalPages - 1)) { ?>
            <a href="<?php print dol_escape_htmltag($listPageUrl($listPage + 1)); ?>" class="digirisk-pwa-pagination__btn"><i class="fas fa-chevron-right"></i></a>
        <?php } else { ?>
            <span class="digirisk-pwa-pagination__btn disabled"><i class="fas fa-chevron-right"></i></span>
        <?php } ?>
    </div>
    <?php } ?>
<?php } ?>
