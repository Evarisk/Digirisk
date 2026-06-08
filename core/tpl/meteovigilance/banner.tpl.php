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
 * \file    core/tpl/meteovigilance/banner.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Template for the Météo-France vigilance alert banner
 */

/**
 * The following vars must be defined:
 * Global   : $langs
 * Variable : $vigilance (array: 'level' int, 'phenomena' array), $departmentCode (string)
 */

$level       = (int) $vigilance['level'];
$noticeClass = $level >= 4 ? 'notice-error' : 'notice-warning';
$levelLabel  = MeteoVigilance::getLevelLabel($level);

$phenomenaLabels = [];
foreach ($vigilance['phenomena'] as $phenomenon) {
    if ((int) $phenomenon['level'] >= 2) {
        $phenomenaLabels[] = MeteoVigilance::getPhenomenonLabel($phenomenon['id']);
    }
}
?>
<div class="wpeo-notice <?php echo $noticeClass; ?> meteo-vigilance-banner meteo-vigilance-banner-level-<?php echo $level; ?>">
    <div class="notice-content">
        <div class="notice-title">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo $langs->trans('MeteoVigilanceBannerTitle', $levelLabel, dol_escape_htmltag($departmentCode)); ?>
        </div>
        <?php if (!empty($phenomenaLabels)) : ?>
            <div class="notice-subtitle"><?php echo dol_escape_htmltag(implode(', ', $phenomenaLabels)); ?></div>
        <?php endif; ?>
    </div>
</div>
