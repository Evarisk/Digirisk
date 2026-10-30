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
 * \file    core/tpl/digiriskelement/digiriskelement_risk_overview_register_cell.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Cell of the register tab of the public overview of the risks: the number of tickets of a
 *          register, or every ticket of the element on the total column. It opens the ticket list of
 *          the element, filtered on the categories it counts. On hover (js/modules/riskOverview.js, as
 *          the risk tooltip), its tickets by subcategory, or by register for the total, then the latest
 *          ones: ref, date, status, severity when high, subcategory and subject. Neither the message
 *          nor who declared a ticket is shown.
 *
 * Variables : $cell (digiriskRiskOverviewCell()), $position, $columns (digiriskRiskOverviewRegisterColumns()),
 *             $overviewRow, $db, $langs (core/tpl/digiriskelement/digiriskelement_risk_overview_public.tpl.php)
 */

if (!isset($registerTicketStatic)) {
    require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';
    $registerTicketStatic = new Ticket($db);
}

$registerTickets = $overviewRow['tickets'];
// Breakdown of the number, and the categories named on each ticket: the subcategories of a register,
// the registers on the total
$breakdown = [];
if ($position < 0) {
    $registerUrl       = digiriskRiskOverviewRegisterUrl((int) $overviewRow['id']);
    $registerName      = $langs->trans('RiskOverviewTooltipAllRegisters');
    $registerOpenTitle = $langs->trans('RiskOverviewOpenTickets', $overviewRow['ref']);
    $cellTickets       = $registerTickets['list'];
    $namedCategories   = $columns;
    foreach ($columns as $registerId => $registerColumn) {
        $breakdown[] = ['name' => $registerColumn['name'], 'count' => (int) ($registerTickets['registers'][$registerId]['count'] ?? 0)];
    }
    $breakdown[] = ['name' => $langs->trans('RiskOverviewTooltipNoRegister'), 'count' => (int) $registerTickets['unregistered']];
} else {
    $register          = $registerTickets['registers'][$position] ?? ['subs' => [], 'direct' => 0];
    $registerIds       = $columns[$position]['ids'];
    $registerUrl       = digiriskRiskOverviewRegisterUrl((int) $overviewRow['id'], $registerIds);
    $registerName      = $columns[$position]['name'];
    $registerOpenTitle = $langs->trans('RiskOverviewOpenRegisterTickets', $registerName, $overviewRow['ref']);
    $cellTickets       = array_filter($registerTickets['list'], function (array $ticket) use ($registerIds): bool {
        return (bool) array_intersect($ticket['categories'], $registerIds);
    });
    $namedCategories = $columns[$position]['subs'];
    if (!empty($columns[$position]['subs'])) {
        foreach ($columns[$position]['subs'] as $subId => $sub) {
            $breakdown[] = ['name' => $sub['name'], 'count' => (int) ($register['subs'][$subId] ?? 0)];
        }
        $breakdown[] = ['name' => $langs->trans('RiskOverviewTooltipNoSubCategory'), 'count' => (int) $register['direct']];
    }
}
// Only what the number counts; a ticket classified in two subcategories is in both
$breakdown = array_filter($breakdown, function (array $line): bool {
    return $line['count'] > 0;
});

// The latest tickets first
usort($cellTickets, function (array $first, array $second): int {
    return [$second['date'], $second['id']] <=> [$first['date'], $first['id']];
});
$shownTickets = array_slice($cellTickets, 0, DIGIRISK_RISK_OVERVIEW_TOOLTIP_TICKETS);
$moreTickets  = count($cellTickets) - count($shownTickets);

$hasTooltip     = $cell['value'] > 0;
$titleAttribute = $hasTooltip ? 'aria-label' : 'title';
$cellClass      = $position < 0 ? 'risk-overview__total' : 'risk-overview__cell is-register' . ($cell['value'] > 0 ? '' : ' is-zero');

?>
<td class="<?php echo $cellClass . ($hasTooltip ? ' has-risk-tooltip' : ''); ?>"<?php echo $cell['link'] ? '' : ' title="' . dol_escape_htmltag($langs->trans('RiskOverviewNoTicket')) . '"'; ?>>
    <?php if ($cell['link']) : ?>
        <a class="risk-overview__link" href="<?php echo dol_escape_htmltag($registerUrl); ?>" target="_blank" rel="noopener"
           <?php echo $titleAttribute; ?>="<?php echo dol_escape_htmltag($registerOpenTitle); ?>"><?php echo (int) $cell['value']; ?></a>
    <?php else : ?>
        <?php echo (int) $cell['value']; ?>
    <?php endif; ?>
    <?php if ($hasTooltip) : ?>
        <template class="risk-overview__risks">
            <div class="risk-overview__tooltip-title">
                <span class="risk-overview__ref"><?php echo dol_escape_htmltag($overviewRow['ref']); ?></span>
                <span class="risk-overview__tooltip-element"><?php echo dol_escape_htmltag($overviewRow['label']); ?></span>
                <span class="risk-overview__tooltip-category"><?php echo dol_escape_htmltag($registerName); ?></span>
            </div>
            <?php if (count($breakdown) > 1 || ($position < 0 && !empty($breakdown))) : ?>
                <div class="risk-overview__tooltip-breakdown">
                    <?php foreach ($breakdown as $line) : ?>
                        <span class="risk-overview__tooltip-breakdown-item"><span class="risk-overview__tooltip-count"><?php echo (int) $line['count']; ?></span> <?php echo dol_escape_htmltag($line['name']); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php foreach ($shownTickets as $shownTicket) :
                $ticketCategories = [];
                foreach ($namedCategories as $namedCategory) {
                    if (array_intersect($shownTicket['categories'], $namedCategory['ids'])) {
                        $ticketCategories[] = $namedCategory['name'];
                    }
                } ?>
                <div class="risk-overview__tooltip-risk">
                    <div class="krt-header">
                        <i class="fas fa-ticket-alt risk-overview__ticket-icon"></i>
                        <strong><?php echo dol_escape_htmltag($shownTicket['ref']); ?></strong>
                        <?php if (isset($registerTicketStatic->labelStatusShort[$shownTicket['status']])) : ?>
                            <span class="risk-overview__ticket-status" data-status="<?php echo (int) $shownTicket['status']; ?>"><?php echo $langs->trans($registerTicketStatic->labelStatusShort[$shownTicket['status']]); ?></span>
                        <?php endif; ?>
                        <?php if (in_array($shownTicket['severity'], ['HIGH', 'BLOCKING'], true)) : ?>
                            <span class="risk-overview__ticket-severity"><i class="fas fa-exclamation-triangle"></i> <?php echo $langs->trans('TicketSeverityShort' . $shownTicket['severity']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="krt-eval">
                        <span><i class="fas fa-calendar-alt"></i> <?php echo dol_print_date($shownTicket['date'], 'day'); ?></span>
                        <?php if (!empty($ticketCategories)) : ?>
                            <span><i class="fas fa-tag"></i> <?php echo dol_escape_htmltag(implode(', ', $ticketCategories)); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($shownTicket['subject'] !== '') : ?>
                        <div class="krt-comment risk-overview__tooltip-description"><?php echo dol_escape_htmltag(dol_trunc($shownTicket['subject'], 110)); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php if ($moreTickets > 0) : ?>
                <div class="risk-overview__tooltip-more">
                    <?php echo $langs->trans($moreTickets > 1 ? 'RiskOverviewTooltipMoreTickets' : 'RiskOverviewTooltipMoreTicketsOne', $moreTickets); ?>
                </div>
            <?php endif; ?>
            <a class="risk-overview__tooltip-hint" href="<?php echo dol_escape_htmltag($registerUrl); ?>" target="_blank" rel="noopener"><i class="fas fa-external-link-alt"></i> <?php echo dol_escape_htmltag($registerOpenTitle); ?></a>
        </template>
    <?php endif; ?>
</td>
