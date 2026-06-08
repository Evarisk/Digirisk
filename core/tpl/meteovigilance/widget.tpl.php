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
 * \file    core/tpl/meteovigilance/widget.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Dashboard card body for the Météo-France vigilance widget
 */

/**
 * The following vars must be defined:
 * Global   : $langs
 * Variable : $level (int), $vigilance (array: 'level', 'phenomena', 'update_time'),
 *            $departmentCode (string), $departmentName (string)
 */

$locationLabel = trim(($departmentName !== '' ? $departmentName . ' ' : '') . '(' . $departmentCode . ')');
?>
<div class="meteo-vigilance-card">
    <?php if ($level >= 2) : ?>
        <div class="meteo-vigilance-panel meteo-vigilance-border-<?php echo $level; ?>">
            <span class="meteo-vigilance-panel__icon meteo-vigilance-bg-<?php echo $level; ?>">
                <i class="fas fa-exclamation-triangle"></i>
            </span>
            <span class="meteo-vigilance-panel__text">
                <span class="meteo-vigilance-panel__title"><?php echo $langs->trans('MeteoVigilancePanelTitle', MeteoVigilance::getLevelLabel($level)); ?></span>
                <span class="meteo-vigilance-panel__subtitle"><?php echo dol_escape_htmltag($locationLabel) . ' — ' . $langs->transnoentities('MeteoVigilanceAdvice' . $level); ?></span>
            </span>
        </div>
    <?php endif; ?>

    <?php if (!empty($vigilance['phenomena'])) : ?>
        <ul class="meteo-vigilance-phenomena">
            <?php foreach ($vigilance['phenomena'] as $phenomenon) :
                $phenomenonId    = (string) $phenomenon['id'];
                $phenomenonLevel = (int) $phenomenon['level']; ?>
                <li class="meteo-vigilance-phenomenon">
                    <span class="meteo-vigilance-phenomenon__label">
                        <i class="<?php echo MeteoVigilance::getPhenomenonIcon($phenomenonId); ?> meteo-vigilance-phenomenon__icon"></i>
                        <?php echo dol_escape_htmltag(MeteoVigilance::getPhenomenonLabel($phenomenonId)); ?>
                    </span>
                    <span class="meteo-vigilance-dot meteo-vigilance-bg-<?php echo $phenomenonLevel; ?>" title="<?php echo dol_escape_htmltag(MeteoVigilance::getLevelLabel($phenomenonLevel)); ?>"></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <div class="meteo-vigilance-noalert"><?php echo $langs->transnoentities('MeteoVigilanceNoAlert'); ?></div>
    <?php endif; ?>

    <div class="meteo-vigilance-footer">
        <span class="meteo-vigilance-footer__update">
            <?php if (!empty($vigilance['update_time'])) {
                echo dol_escape_htmltag($langs->trans('MeteoVigilanceUpdatedAt', dol_print_date(strtotime($vigilance['update_time']), 'dayhour')));
            } ?>
        </span>
        <a class="meteo-vigilance-footer__link" href="https://vigilance.meteofrance.fr/fr" target="_blank" rel="noopener">
            <?php echo $langs->transnoentities('MeteoVigilanceSeeOnMeteoFrance'); ?> <i class="fas fa-external-link-alt"></i>
        </a>
    </div>
</div>
