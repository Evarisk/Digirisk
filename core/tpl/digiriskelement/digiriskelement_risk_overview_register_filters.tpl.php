<?php
/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/digiriskelement/digiriskelement_risk_overview_register_filters.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Filters at the top of the register tab of the public overview of the risks: the closed and
 *          cancelled tickets, hidden by default, and the period of their creation. The page reloads on
 *          the register tab with them (the checkbox sends the form at once, js/modules/riskOverview.js).
 *
 * Variables : $registerFilters (digiriskRiskOverviewRegisterFilters()), $key, $tabUrl, $conf, $langs
 *             (core/tpl/digiriskelement/digiriskelement_risk_overview_public.tpl.php)
 */

$registerFiltered = $registerFilters['closed'] || $registerFilters['from'] !== '' || $registerFilters['to'] !== '';

?>
<form class="risk-overview__filters" method="GET" action="<?php echo dol_escape_htmltag($_SERVER['PHP_SELF']); ?>">
    <input type="hidden" name="entity" value="<?php echo (int) $conf->entity; ?>">
    <input type="hidden" name="key" value="<?php echo dol_escape_htmltag($key); ?>">
    <input type="hidden" name="tab" value="register">
    <label class="risk-overview__filter risk-overview__filter--closed">
        <input type="checkbox" name="register_closed" value="1"<?php echo $registerFilters['closed'] ? ' checked' : ''; ?>>
        <?php echo $langs->trans('RiskOverviewRegisterShowClosed'); ?>
    </label>
    <span class="risk-overview__filter-period">
        <label class="risk-overview__filter">
            <?php echo $langs->trans('RiskOverviewRegisterFrom'); ?>
            <input type="date" name="register_from" value="<?php echo dol_escape_htmltag($registerFilters['from']); ?>">
        </label>
        <label class="risk-overview__filter">
            <?php echo $langs->trans('RiskOverviewRegisterTo'); ?>
            <input type="date" name="register_to" value="<?php echo dol_escape_htmltag($registerFilters['to']); ?>">
        </label>
    </span>
    <button type="submit" class="risk-overview__filter-submit"><i class="fas fa-filter"></i> <?php echo $langs->trans('RiskOverviewRegisterFilter'); ?></button>
    <?php if ($registerFiltered) : ?>
        <a class="risk-overview__filter-reset" href="<?php echo dol_escape_htmltag($tabUrl('register')); ?>"><i class="fas fa-times"></i> <?php echo $langs->trans('RiskOverviewRegisterReset'); ?></a>
    <?php endif; ?>
</form>
