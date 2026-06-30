<?php
/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
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
 * \file    class/ticketstatsdashboard.class.php
 * \ingroup digiriskdolibarr
 * \brief   Class file for manage TicketDashboard
 */

// load Dolibarr librairies
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

/**
 * Class to manage stats for tickets
 */
class TicketStatsDashboard extends DigiriskDolibarrDashboard
{
    /**
     * @var DoliDB Database handler
     */
    public DoliDB $db;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db);
    }

    /**
     * Load dashboard info ticket
     *
     * @param  array     $moreParams Parameters for load dashboard info
     * @return array
     * @throws Exception
     */
    public function load_dashboard(array $moreParams = []): array
    {
        global $langs;

        $dashboardConfig = json_decode(getDolUserString('DIGIRISKDOLIBARR_DASHBOARD_CONFIG'));
        $filter          = !empty($dashboardConfig->filters->ticketDate) ? $dashboardConfig->filters->ticketDate : 'datec';

        $tickets = $this->getAllTickets($filter);

        $runningTickets = $this->getRunningTickets($tickets, $filter);
        $ticketStats    = $this->getTicketStats($tickets, $filter);

        $getTicketRepartitionPerUserAndMeanAnswerTime = $this->getTicketRepartitionPerUserAndMeanAnswerTime($tickets, $filter);
        $getTopSocietyWithMostTickets                 = $this->getTopSocietyWithMostTickets($tickets);

        $array['graphsFilters'] = [
            'date' => [
                'title'        => $langs->transnoentities('ShowSelectedDateTypes'),
                'type'         => 'selectarray',
                'filter'       => 'ticketDate',
                'values'       => ['datec' => $langs->transnoentities('DateCreation'), 'declaration_date' => $langs->transnoentities('DeclarationDate')],
                'currentValue' => $filter
            ]
        ];

        $array['widgets'] = array_merge($runningTickets, $ticketStats);
        $array['graphs']  = [$getTicketRepartitionPerUserAndMeanAnswerTime, $getTopSocietyWithMostTickets];

        return $array;
    }

    /**
     * Get all tickets from database for current entity order by datec DESC
     * fetch all comments for each ticket if exists
     * add them to ticket object as comms property (array of comments)
     *
     * @param  string $filter Filter to apply on tickets (datec or declaration_date)
     * @return array          $tickets All tickets with comments if exists or empty array
     * @throws Exception      If an error occurs while fetching tickets
     */
    public function getAllTickets(string $filter = 'datec'): array
    {
        try {
            $actionComm = new ActionComm($this->db);
            if ($filter == 'declaration_date') {
                $filter = 'eft.digiriskdolibarr_ticket_date';
            } else {
                $filter = 't.datec';
            }
            $tickets    = saturne_fetch_all_object_type('Ticket', 'DESC', $filter, 0, 0, ['customsql' => $filter . ' IS NOT NULL'], 'AND', true);
            if (!empty($tickets) && is_array($tickets)) {
                foreach ($tickets as $ticketID => $ticket) {
                    $ticket->comms = [];
                    $actionComms   = $actionComm->getActions(0, $ticketID, 'ticket', 'AND a.code LIKE "TICKET_MSG%"', 'a.datec');
                    if (!empty($actionComms) && is_array($actionComms)) {
                        $ticket->comms = $actionComms;
                    } elseif (is_string($actionComms)) {
                        dol_syslog(__METHOD__ . 'Error while fetching comments for ticket ID {$ticketID}: ' . $actionComms, LOG_ERR);
                    }
                }
            } else {
                $tickets = [];
            }
        } catch (Exception $e) {
            dol_syslog(__METHOD__ . 'Error while fetching tickets: ' . $e->getMessage(), LOG_ERR);
            throw $e;
        }

        return $tickets;
    }

    /**
     * Get running tickets
     *
     * @param  array  $tickets All tickets from database for current entity order by datec DESC with comments if exists or empty array
     * @param  string $filter  Filter to apply on tickets (datec or declaration_date)
     * @return array           Widget of running tickets with the oldest ticket and oldest message ticket
     */
    public function getRunningTickets(array $tickets, string $filter = 'datec'): array
    {
        global $form, $langs;

        // Widget title parameters
        $array['title']      = $langs->transnoentities('RunningTicket');
        $array['widgetName'] = 'RunningTicket';
        $array['picto']      = 'fas fa-ticket-alt';
        $array['pictoColor'] = '#0D8AFF';

        // Widget labels parameters
        $array['label'] = [
            $langs->transnoentities('NbOfOpenedTicket'),
            $form->textwithpicto($langs->transnoentities('OldestTicket'), $langs->transnoentities('OldestTicketDescription')),
            $form->textwithpicto($langs->transnoentities('OldestMessageTicket'), $langs->transnoentities('OldestMessageTicketDescription'))
        ];

        // Initialize variables
        $openTickets         = 0;
        $oldestTicket        = null;
        $oldestMessageTicket = null;
        $now                 = dol_now();
        $oldestTicketDate    = 0;
        if ($filter == 'declaration_date') {
            $filterDate = 'options_digiriskdolibarr_ticket_date';
        } else {
            $filterDate = 'datec';
        }

        // Get number of open tickets, oldest ticket and oldest message ticket
        if (!empty($tickets)) {
            foreach ($tickets as $ticket) {
                if (!in_array($ticket->fk_statut, [Ticket::STATUS_CANCELED, Ticket::STATUS_CLOSED])) {
                    $openTickets++;
                    if ($filter == 'declaration_date') {
                        if (!empty($ticket->array_options[$filterDate])) {
                            $ticketDate = strtotime($ticket->array_options[$filterDate]);
                        }
                        if (!empty($oldestTicket->array_options[$filterDate])) {
                            $oldestTicketDate = strtotime($oldestTicket->array_options[$filterDate]);
                        }
                    } else {
                        $ticketDate       = $ticket->{$filterDate};
                        $oldestTicketDate = $oldestTicket->{$filterDate};
                    }
                    if (empty($oldestTicket) || $ticketDate < $oldestTicketDate) {
                        $oldestTicket = $ticket;
                    }

                    $lastComm = !empty($ticket->comms) ? current($ticket->comms) : null;
                    if (!empty($lastComm) && (empty($oldestMessageTicket) || $lastComm->datec < $oldestMessageTicket->datec)) {
                        $oldestMessageTicket = $lastComm;
                    }
                }
            }
        }

        // Widget content parameters
        $array['content'] = [
            $openTickets,
            !empty($oldestTicket) ? dol_print_date($oldestTicketDate, 'day') : $langs->transnoentities('NoData'),
            !empty($oldestMessageTicket) ? dol_print_date($oldestMessageTicket->datec, 'day') : $langs->transnoentities('NoData')
        ];

        $array['moreContent'] = [
            '',
            !empty($oldestTicket) ? ' (' . convertSecondToTime(roundUpToNextMultiple($now - $oldestTicketDate, 60)) . ')' . $oldestTicket->getNomUrl(2, '', 0, 'paddingleft') : '',
            !empty($oldestMessageTicket) ? ' (' . convertSecondToTime(roundUpToNextMultiple($now - $oldestMessageTicket->datec, 60)) . ')' . $tickets[$oldestMessageTicket->fk_element]->getNomUrl(2, '', 0, 'paddingleft') : ''
        ];

        return ['runningTickets' => $array];
    }

    /**
     * Get ticket stats
     *
     * @param  array  $tickets All tickets from database for current entity order by datec DESC with comments if exists or empty array
     * @param  string $filter  Filter to apply on tickets (datec or declaration_date)
     * @return array           Widget of ticket stats with number of ticket per user, mean answer time and number of exchange per ticket
     */
    function getTicketStats(array $tickets, string $filter = 'datec'): array
    {
        global $langs;

        // Widget title parameters
        $array['title']      = $langs->transnoentities('TicketStatistics');
        $array['widgetName'] = 'TicketStatistics';
        $array['picto']      = 'fas fa-chart-pie';
        $array['pictoColor'] = '#32E592';

        // Widget labels parameters
        $array['label'] = [
            $langs->transnoentities('MeanAnswerTime'),
            $langs->transnoentities('NbTicketPerUser'),
            $langs->transnoentities('NbExchangePerTicket')
        ];

        // Initialize variables
        $timePerTicket    = [];
        $users            = [];
        $nbTicketAssigned = 0;
        $nbExchanges      = 0;
        if ($filter == 'declaration_date') {
            $filterDate = 'options_digiriskdolibarr_ticket_date';
        } else {
            $filterDate = 'datec';
        }

        if (!empty($tickets)) {
            foreach ($tickets as $ticket) {
                if (!empty($ticket->date_close)) {
                    if ($filter == 'declaration_date') {
                        if (!empty($ticket->array_options[$filterDate])) {
                            $ticketDate = strtotime($ticket->array_options[$filterDate]);
                        }
                    } else {
                        $ticketDate = $ticket->{$filterDate};
                    }
                    $timePerTicket[] = $ticket->date_close - $ticketDate;
                }
                if (!empty($ticket->fk_user_assign)) {
                    $nbTicketAssigned++;
                    if (!in_array($ticket->fk_user_assign, $users)) {
                        $users[] = $ticket->fk_user_assign;
                    }
                }
                $nbExchanges += count($ticket->comms);
            }
        }

        // Widget content parameters
        $array['content'] = [
            count($timePerTicket) ? convertSecondToTime(array_sum($timePerTicket) / count($timePerTicket)) : $langs->transnoentities('NoData'),
            count($users) ? intdiv($nbTicketAssigned, count($users)) : 0,
            $tickets ? ceil($nbExchanges / count($tickets)) : 0
        ];

        return ['ticketStats' => $array];
    }

    /**
     * Get ticket repartition per user with number of ticket and mean answer time
     *
     * @param  array  $tickets All tickets from database for current entity order by datec DESC with comments if exists or empty array
     * @param  string $filter  Filter to apply on tickets (datec or declaration_date)
     * @return array           Graph of ticket repartition per user with number of ticket and mean answer time
     */
    function getTicketRepartitionPerUserAndMeanAnswerTime(array $tickets, string $filter = 'datec'): array
    {
        global $langs;

        // Graph title parameters
        $array['title'] = $langs->transnoentities('TicketRepartitionPerUserAndMeanAnswerTime');
        $array['picto'] = 'fontawesome_fa-ticket-alt_fas_#3bbfa8';

        // Graph parameters
        $array['width']      = '100%';
        $array['height']     = 300;
        $array['type']       = 'bar';
        $array['showlegend'] = 1;
        $array['dataset']    = 3;
        $array['moreCSS']    = 'grid-2';

        $array['labels'] = [
            ['label' => $langs->transnoentities('NbOfTickets')],
            ['label' => $langs->transnoentities('MeanAnswerTime')]
        ];

        // Initialize technical objects
        $userTmp = new User($this->db);

        // Initialize variables
        $nbTicketPerUser = [];
        if ($filter == 'declaration_date') {
            $filterDate = 'options_digiriskdolibarr_ticket_date';
        } else {
            $filterDate = 'datec';
        }

        if (!empty($tickets)) {
            foreach ($tickets as $ticket) {
                if ($ticket->fk_user_assign == null) {
                    continue;
                }

                $userTmp->fetch($ticket->fk_user_assign);
                $userFullName = $userTmp->getFullName($langs);
                if (!isset($nbTicketPerUser[$userFullName])) {
                    $nbTicketPerUser[$userFullName]['nbTicket'] = 0;
                }
                $nbTicketPerUser[$userFullName]['nbTicket']++;
                if (!empty($ticket->date_close)) {
                    if ($filter == 'declaration_date') {
                        if (!empty($ticket->array_options[$filterDate])) {
                            $ticketDate = strtotime($ticket->array_options[$filterDate]);
                        }
                    } else {
                        $ticketDate = $ticket->{$filterDate};
                    }
                    $nbTicketPerUser[$userFullName]['meanAnswerTime'][$ticket->id] = $ticket->date_close - $ticketDate;
                }
            }

            if (!empty($nbTicketPerUser)) {
                uasort($nbTicketPerUser, function($a, $b) {
                    return $b['nbTicket'] - $a['nbTicket'];
                });

                foreach ($nbTicketPerUser as $userName => $ticketData) {
                    $meanAnswerTimePerUser = 0;
                    if (isset($ticketData['meanAnswerTime'])) {
                        $meanAnswerTimePerUser = array_sum($ticketData['meanAnswerTime']) / count($ticketData['meanAnswerTime']);
                        $meanAnswerTimePerUser = round($meanAnswerTimePerUser / 86400);
                    }
                    $array['data'][] = [$userName, $ticketData['nbTicket'], $meanAnswerTimePerUser];
                }
            }
        }

        return $array;
    }

    /**
     * Get top society with most tickets
     *
     * @param  array $tickets All tickets from database for current entity order by datec DESC with comments if exists or empty array
     * @return array          Graph of top society with most tickets
     */
    function getTopSocietyWithMostTickets(array $tickets): array
    {
        global $langs;

        // Graph title parameters
        $array['title'] = $langs->transnoentities('TopSocietyWithMostTickets', getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5));
        $array['picto'] = 'fontawesome_fa-ticket-alt_fas_#3bbfa8';

        // Graph parameters
        $array['width']      = '100%';
        $array['height']     = 300;
        $array['type']       = 'bar';
        $array['showlegend'] = 1;
        $array['dataset']    = 2;
        $array['moreCSS']    = 'grid-2';

        $array['labels'] = [
            [
                'label' => $langs->transnoentities('NbOfTickets'),
                'color' => '#A1467E'
            ]
        ];

        // Initialize technical objects
        $society = new Societe($this->db);

        // Initialize variables
        $nbTicketPerSociety = [];

        if (!empty($tickets)) {
            foreach ($tickets as $ticket) {
                if ($ticket->fk_soc == null) {
                    continue;
                }

                $society->fetch($ticket->fk_soc);
                if (!isset($nbTicketPerSociety[$society->name])) {
                    $nbTicketPerSociety[$society->name]['nbTicket'] = 0;
                }
                $nbTicketPerSociety[$society->name]['nbTicket']++;
            }

            if (!empty($nbTicketPerSociety)) {
                uasort($nbTicketPerSociety, function($a, $b) {
                    return $b['nbTicket'] - $a['nbTicket'];
                });

                $nbTicketPerSociety = array_slice($nbTicketPerSociety, 0, getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5), true);
                foreach ($nbTicketPerSociety as $socName => $ticketData) {
                    $array['data'][] = [$socName, $ticketData['nbTicket']];
                }
            }
        }

        return $array;
    }
}
