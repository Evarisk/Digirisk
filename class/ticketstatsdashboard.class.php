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
     * @var int Number of months covered by the created versus closed graph
     */
    public const NB_MONTHS_OF_FLOW = 12;

    /**
     * @var int[] Age limits, in days, cutting the open ticket backlog into buckets
     */
    public const BACKLOG_AGE_LIMITS = [7, 30, 90];

    /**
     * @var string[] Codes of the ticket messages the requester can see, an internal note is not an answer
     */
    public const PUBLIC_MESSAGE_CODES = ['TICKET_MSG', 'TICKET_MSG_SENTBYMAIL'];

    /**
     * @var string Criteria restricting the native ticket list to the open tickets, as the dashboard counters do
     */
    public const OPEN_TICKETS_FILTER = 'search_fk_statut%5B%5D=openall';

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
        $getTicketsCreatedVersusClosedByMonth         = $this->getTicketsCreatedVersusClosedByMonth($tickets);
        $getOpenTicketsByStatus                       = $this->getOpenTicketsByStatus($tickets);
        $getOpenTicketBacklogAge                      = $this->getOpenTicketBacklogAge($tickets);

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

        // The repartition per user stays first, then the flow, then where the backlog sits
        $array['graphs'] = [
            $getTicketRepartitionPerUserAndMeanAnswerTime,
            $getTicketsCreatedVersusClosedByMonth,
            $getOpenTicketsByStatus,
            $getOpenTicketBacklogAge,
            $getTopSocietyWithMostTickets
        ];

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
                if ($this->isOpenTicket($ticket)) {
                    $openTickets++;
                    if ($filter == 'declaration_date') {
                        $ticketDate = !empty($ticket->array_options[$filterDate]) ? strtotime($ticket->array_options[$filterDate]) : 0;
                    } else {
                        $ticketDate = $ticket->{$filterDate};
                    }

                    // The date is kept alongside the ticket: read back from $oldestTicket on the next iteration
                    // instead, it ended up holding the date of the previously retained ticket
                    if (!empty($ticketDate) && (empty($oldestTicket) || $ticketDate < $oldestTicketDate)) {
                        $oldestTicket     = $ticket;
                        $oldestTicketDate = $ticketDate;
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
     * @return array           Widget of ticket stats with the mean first response time, the mean answer time of
     *                         closed tickets, the number of open ticket per user and the number of exchange per open ticket
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
            $langs->transnoentities('MeanFirstResponseTime'),
            $langs->transnoentities('MeanAnswerTime'),
            $langs->transnoentities('NbTicketPerUser'),
            $langs->transnoentities('NbExchangePerTicket')
        ];

        // Both delays are measured on a subset of the tickets, the tooltip says which one
        $array['tooltip'] = [
            'MeanFirstResponseTimeDescription',
            'MeanAnswerTimeDescription'
        ];

        // Initialize variables
        $timePerTicket       = [];
        $timeToFirstResponse = [];
        $users               = [];
        $nbTicketAssigned    = 0;
        $nbExchanges         = 0;
        $nbOpenTickets       = 0;
        if ($filter == 'declaration_date') {
            $filterDate = 'options_digiriskdolibarr_ticket_date';
        } else {
            $filterDate = 'datec';
        }

        if (!empty($tickets)) {
            foreach ($tickets as $ticket) {
                if ($filter == 'declaration_date') {
                    $ticketDate = !empty($ticket->array_options[$filterDate]) ? strtotime($ticket->array_options[$filterDate]) : 0;
                } else {
                    $ticketDate = $ticket->{$filterDate};
                }

                // The mean answer time is measured on closed tickets only, it needs a closing date
                if (!empty($ticketDate) && !empty($ticket->date_close)) {
                    $timePerTicket[] = $ticket->date_close - $ticketDate;
                }

                $firstResponseDate = $this->getFirstResponseDate($ticket);
                if (!empty($ticketDate) && $firstResponseDate > $ticketDate) {
                    $timeToFirstResponse[] = $firstResponseDate - $ticketDate;
                }

                // Every counter below only weighs open tickets, a closed one is not worked on anymore
                if (!$this->isOpenTicket($ticket)) {
                    continue;
                }
                $nbOpenTickets++;
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
            count($timeToFirstResponse) ? convertSecondToTime((int) round(array_sum($timeToFirstResponse) / count($timeToFirstResponse))) : $langs->transnoentities('NoData'),
            count($timePerTicket) ? convertSecondToTime(array_sum($timePerTicket) / count($timePerTicket)) : $langs->transnoentities('NoData'),
            count($users) ? intdiv($nbTicketAssigned, count($users)) : 0,
            $nbOpenTickets ? ceil($nbExchanges / $nbOpenTickets) : 0
        ];

        return ['ticketStats' => $array];
    }

    /**
     * Get ticket repartition per enabled user with number of open ticket and mean answer time
     *
     * @param  array  $tickets All tickets from database for current entity order by datec DESC with comments if exists or empty array
     * @param  string $filter  Filter to apply on tickets (datec or declaration_date)
     * @return array           Graph of ticket repartition per user with number of open ticket, mean answer time of closed tickets and ticket list link of each bar
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

        // The mean answer time series holds a number of days, the unit has to be carried by the legend as the bars only show raw values
        $array['labels'] = [
            ['label' => $langs->transnoentities('NbOfOpenedTicket')],
            ['label' => $langs->transnoentities('MeanAnswerTime') . ' (' . $langs->transnoentities('DurationDays') . ')']
        ];

        // Initialize variables
        $nbTicketPerUser = [];
        $skippedUsers    = [];
        $links           = [];
        if ($filter == 'declaration_date') {
            $filterDate = 'options_digiriskdolibarr_ticket_date';
        } else {
            $filterDate = 'datec';
        }

        if (!empty($tickets)) {
            foreach ($tickets as $ticket) {
                // An unassigned ticket stores -1 as well as null, and both must stay out of the graph
                if ($ticket->fk_user_assign <= 0) {
                    continue;
                }

                // Keying on the user ID instead of the name fetches each assignee once, and gives the graph link its filter value
                $userID = (int) $ticket->fk_user_assign;
                if (isset($skippedUsers[$userID])) {
                    continue;
                }
                if (!isset($nbTicketPerUser[$userID])) {
                    $userTmp = new User($this->db);
                    // Disabled users are kept out of the repartition, their tickets cannot be worked on anymore
                    if ($userTmp->fetch($userID) <= 0 || $userTmp->statut == User::STATUS_DISABLED) {
                        $skippedUsers[$userID] = 1;
                        continue;
                    }
                    $nbTicketPerUser[$userID] = ['name' => $userTmp->getFullName($langs), 'nbTicket' => 0];
                }

                // Only open tickets weigh in the repartition, while the mean answer time needs closed ones
                if ($this->isOpenTicket($ticket)) {
                    $nbTicketPerUser[$userID]['nbTicket']++;
                }
                if (!empty($ticket->date_close)) {
                    if ($filter == 'declaration_date') {
                        if (!empty($ticket->array_options[$filterDate])) {
                            $ticketDate = strtotime($ticket->array_options[$filterDate]);
                        }
                    } else {
                        $ticketDate = $ticket->{$filterDate};
                    }
                    $nbTicketPerUser[$userID]['meanAnswerTime'][$ticket->id] = $ticket->date_close - $ticketDate;
                }
            }

            if (!empty($nbTicketPerUser)) {
                uasort($nbTicketPerUser, function($a, $b) {
                    return $b['nbTicket'] - $a['nbTicket'];
                });

                foreach ($nbTicketPerUser as $userID => $ticketData) {
                    // A user whose tickets are all closed has nothing left to show in a repartition of open tickets
                    if (empty($ticketData['nbTicket'])) {
                        continue;
                    }

                    $meanAnswerTimePerUser = 0;
                    if (isset($ticketData['meanAnswerTime'])) {
                        $meanAnswerTimePerUser = array_sum($ticketData['meanAnswerTime']) / count($ticketData['meanAnswerTime']);
                        $meanAnswerTimePerUser = round($meanAnswerTimePerUser / 86400);
                    }
                    $array['data'][] = [$ticketData['name'], $ticketData['nbTicket'], $meanAnswerTimePerUser];
                    $links[]         = $this->getTicketListUrl(self::OPEN_TICKETS_FILTER . '&search_fk_user_assign=' . $userID);
                }
            }
        }

        // A mean answer time in days dwarfs a ticket count on a shared scale, so it gets its own Y axis
        $array['morehtmlright'] = $this->getGraphOptionsInput(['links' => $links, 'secondAxisDataset' => 1]);

        return $array;
    }

    /**
     * Get top society with most open tickets
     *
     * @param  array $tickets All tickets from database for current entity order by datec DESC with comments if exists or empty array
     * @return array          Graph of top society with most open tickets and ticket list link of each bar
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
                'label' => $langs->transnoentities('NbOfOpenedTicket'),
                'color' => '#A1467E'
            ]
        ];

        // Initialize variables
        $nbTicketPerSociety = [];
        $links              = [];

        if (!empty($tickets)) {
            foreach ($tickets as $ticket) {
                // Only open tickets weigh in the top, a closed one is not worked on anymore
                if (!$this->isOpenTicket($ticket)) {
                    continue;
                }
                // A ticket with no third party stores -1 as well as null, and both must stay out of the graph
                if ($ticket->fk_soc <= 0) {
                    continue;
                }

                // Keying on the society ID instead of the name fetches each society once, and gives the graph link its filter value
                $societyID = (int) $ticket->fk_soc;
                if (!isset($nbTicketPerSociety[$societyID])) {
                    $society = new Societe($this->db);
                    $society->fetch($societyID);
                    $nbTicketPerSociety[$societyID] = ['name' => $society->name, 'nbTicket' => 0];
                }
                $nbTicketPerSociety[$societyID]['nbTicket']++;
            }

            if (!empty($nbTicketPerSociety)) {
                uasort($nbTicketPerSociety, function($a, $b) {
                    return $b['nbTicket'] - $a['nbTicket'];
                });

                $nbTicketPerSociety = array_slice($nbTicketPerSociety, 0, getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5), true);
                foreach ($nbTicketPerSociety as $societyID => $ticketData) {
                    $array['data'][] = [$ticketData['name'], $ticketData['nbTicket']];
                    $links[]         = $this->getTicketListUrl(self::OPEN_TICKETS_FILTER . '&search_fk_soc=' . $societyID);
                }
            }
        }

        $array['morehtmlright'] = $this->getGraphOptionsInput(['links' => $links]);

        return $array;
    }

    /**
     * Get the number of tickets created and closed for each of the last months
     *
     * Both series are built on the creation and closing dates whatever the dashboard date filter is: they are
     * the two fields the native ticket list can filter on, so a bar and the list it opens hold the same tickets.
     *
     * @param  array $tickets All tickets from database for current entity order by datec DESC with comments if exists or empty array
     * @return array          Graph of created versus closed tickets per month and ticket list link of each bar
     */
    public function getTicketsCreatedVersusClosedByMonth(array $tickets): array
    {
        global $langs;

        // Graph title parameters
        $array['title'] = $langs->transnoentities('TicketsCreatedVersusClosedByMonth', self::NB_MONTHS_OF_FLOW);
        $array['picto'] = 'fontawesome_fa-exchange-alt_fas_#3bbfa8';

        // Graph parameters
        $array['width']      = '100%';
        $array['height']     = 300;
        $array['type']       = 'bar';
        $array['showlegend'] = 1;
        $array['dataset']    = 3;
        $array['moreCSS']    = 'grid-2';

        $array['labels'] = [
            [
                'label' => $langs->transnoentities('TicketsCreated'),
                'color' => '#0D8AFF'
            ],
            [
                'label' => $langs->transnoentities('TicketsClosed'),
                'color' => '#32E592'
            ]
        ];

        // Initialize variables
        $now              = dol_now();
        $currentYear      = (int) dol_print_date($now, '%Y');
        $currentMonth     = (int) dol_print_date($now, '%m');
        $currentMonthStart = dol_get_first_day($currentYear, $currentMonth);

        $months = [];
        for ($i = self::NB_MONTHS_OF_FLOW - 1; $i >= 0; $i--) {
            $monthStart = dol_time_plus_duree($currentMonthStart, -$i, 'm');
            $months[dol_print_date($monthStart, '%Y-%m')] = [
                'label'   => dol_print_date($monthStart, '%m/%Y'),
                'start'   => $monthStart,
                'end'     => dol_get_last_day((int) dol_print_date($monthStart, '%Y'), (int) dol_print_date($monthStart, '%m')),
                'created' => 0,
                'closed'  => 0
            ];
        }

        if (!empty($tickets)) {
            foreach ($tickets as $ticket) {
                $createdMonth = dol_print_date($ticket->datec, '%Y-%m');
                if (isset($months[$createdMonth])) {
                    $months[$createdMonth]['created']++;
                }
                if (!empty($ticket->date_close)) {
                    $closedMonth = dol_print_date($ticket->date_close, '%Y-%m');
                    if (isset($months[$closedMonth])) {
                        $months[$closedMonth]['closed']++;
                    }
                }
            }
        }

        $createdLinks = [];
        $closedLinks  = [];
        foreach ($months as $month) {
            $array['data'][] = [$month['label'], $month['created'], $month['closed']];
            $createdLinks[]  = $this->getTicketListUrl($this->getDateRangeFilter('search_date', $month['start'], $month['end']));
            $closedLinks[]   = $this->getTicketListUrl($this->getDateRangeFilter('search_dateclose', $month['start'], $month['end']));
        }

        // Each series filters on a date of its own, so the links are declared per dataset
        $array['morehtmlright'] = $this->getGraphOptionsInput(['datasetLinks' => [$createdLinks, $closedLinks]]);

        return $array;
    }

    /**
     * Get the repartition of the open tickets over their status
     *
     * @param  array $tickets All tickets from database for current entity order by datec DESC with comments if exists or empty array
     * @return array          Graph of open tickets per status and ticket list link of each slice
     */
    public function getOpenTicketsByStatus(array $tickets): array
    {
        global $conf, $langs;

        // Graph title parameters
        $array['title'] = $langs->transnoentities('OpenTicketsByStatus');
        $array['picto'] = 'fontawesome_fa-chart-pie_fas_#3bbfa8';

        // Graph parameters
        $array['width']      = '100%';
        $array['height']     = 300;
        $array['type']       = 'pie';
        $array['showlegend'] = ($conf->browser->layout ?? '') == 'phone' ? 1 : 2;
        $array['dataset']    = 1;
        $array['moreCSS']    = 'grid-2';

        // labelStatusShort is filled by the constructor, no ticket has to be fetched to read the status labels
        $ticketStatusLabels = (new Ticket($this->db))->labelStatusShort;

        // Initialize variables
        $nbTicketPerStatus = [];
        $links             = [];

        if (!empty($tickets)) {
            foreach ($tickets as $ticket) {
                if (!$this->isOpenTicket($ticket)) {
                    continue;
                }

                $status = (int) $ticket->fk_statut;
                if (!isset($nbTicketPerStatus[$status])) {
                    $nbTicketPerStatus[$status] = 0;
                }
                $nbTicketPerStatus[$status]++;
            }

            // Follow the workflow order of the statuses rather than the order the tickets came in
            ksort($nbTicketPerStatus);

            foreach ($nbTicketPerStatus as $status => $nbTicket) {
                $array['labels'][] = [
                    'label' => $langs->transnoentities($ticketStatusLabels[$status] ?? 'Unknown'),
                    'color' => SaturneDashboard::getColorRange($status)
                ];
                $array['data'][] = $nbTicket;
                $links[]         = $this->getTicketListUrl('search_fk_statut%5B%5D=' . $status);
            }
        }

        $array['morehtmlright'] = $this->getGraphOptionsInput(['links' => $links]);

        return $array;
    }

    /**
     * Get the age of the open ticket backlog, split into buckets
     *
     * The buckets are cut on day boundaries because the native ticket list filters dates by day: a bar and the
     * list it opens then hold the same tickets. The age is counted from the creation date for the same reason.
     *
     * @param  array $tickets All tickets from database for current entity order by datec DESC with comments if exists or empty array
     * @return array          Graph of open tickets per age bucket and ticket list link of each bar
     */
    public function getOpenTicketBacklogAge(array $tickets): array
    {
        global $langs;

        // Graph title parameters
        $array['title'] = $langs->transnoentities('OpenTicketBacklogAge');
        $array['picto'] = 'fontawesome_fa-hourglass-half_fas_#3bbfa8';

        // Graph parameters
        $array['width']      = '100%';
        $array['height']     = 300;
        $array['type']       = 'bar';
        $array['showlegend'] = 1;
        $array['dataset']    = 2;
        $array['moreCSS']    = 'grid-2';

        $array['labels'] = [
            [
                'label' => $langs->transnoentities('NbOfOpenedTicket'),
                'color' => '#E9A00D'
            ]
        ];

        // Initialize variables
        $today   = dol_get_first_hour(dol_now());
        $buckets = [];
        $links   = [];

        // The newest bound is included and the oldest excluded, so a ticket always falls in exactly one bucket
        $previousLimit = 0;
        foreach (self::BACKLOG_AGE_LIMITS as $nbDays) {
            $buckets[] = [
                'label' => empty($previousLimit) ? $langs->transnoentities('BacklogAgeUnderDays', $nbDays) : $langs->transnoentities('BacklogAgeBetweenDays', $previousLimit, $nbDays),
                'from'  => $today - $nbDays * 86400,
                'to'    => empty($previousLimit) ? 0 : $today - $previousLimit * 86400,
                'nb'    => 0
            ];
            $previousLimit = $nbDays;
        }
        $buckets[] = [
            'label' => $langs->transnoentities('BacklogAgeOverDays', $previousLimit),
            'from'  => 0,
            'to'    => $today - $previousLimit * 86400,
            'nb'    => 0
        ];

        if (!empty($tickets)) {
            foreach ($tickets as $ticket) {
                if (!$this->isOpenTicket($ticket)) {
                    continue;
                }

                foreach ($buckets as $key => $bucket) {
                    if ((empty($bucket['from']) || $ticket->datec >= $bucket['from']) && (empty($bucket['to']) || $ticket->datec < $bucket['to'])) {
                        $buckets[$key]['nb']++;
                        break;
                    }
                }
            }
        }

        foreach ($buckets as $bucket) {
            $array['data'][] = [$bucket['label'], $bucket['nb']];
            // The list bound is the whole day, so the exclusive upper bound of the bucket is the day before
            $links[] = $this->getTicketListUrl(self::OPEN_TICKETS_FILTER . '&' . $this->getDateRangeFilter('search_date', $bucket['from'], empty($bucket['to']) ? dol_now() : $bucket['to'] - 86400));
        }

        $array['morehtmlright'] = $this->getGraphOptionsInput(['links' => $links]);

        return $array;
    }

    /**
     * Get the date of the first message of a ticket the requester could see
     *
     * @param  Ticket $ticket Ticket to look into
     * @return int            Timestamp of the first public message, 0 when the ticket has none
     */
    protected function getFirstResponseDate(Ticket $ticket): int
    {
        $firstResponseDate = 0;
        foreach (($ticket->comms ?? []) as $comm) {
            if (!in_array($comm->code, self::PUBLIC_MESSAGE_CODES)) {
                continue;
            }
            if (empty($firstResponseDate) || $comm->datec < $firstResponseDate) {
                $firstResponseDate = $comm->datec;
            }
        }

        return (int) $firstResponseDate;
    }

    /**
     * Get the date range criteria of the native ticket list
     *
     * @param  string $prefix Name of the list date filter, without its bound suffix ('search_date', 'search_dateclose')
     * @param  int    $start  Timestamp of the first day of the range, 0 for no lower bound
     * @param  int    $end    Timestamp of the last day of the range, 0 for no upper bound
     * @return string         Search criteria, empty when the range is unbounded
     */
    protected function getDateRangeFilter(string $prefix, int $start = 0, int $end = 0): string
    {
        $filter = [];
        foreach (['start' => $start, 'end' => $end] as $bound => $timestamp) {
            if (empty($timestamp)) {
                continue;
            }

            $date     = dol_getdate($timestamp);
            $filter[] = $prefix . '_' . $bound . 'day=' . $date['mday'];
            $filter[] = $prefix . '_' . $bound . 'month=' . $date['mon'];
            $filter[] = $prefix . '_' . $bound . 'year=' . $date['year'];
        }

        return implode('&', $filter);
    }

    /**
     * Check if a ticket is still open
     *
     * @param  Ticket $ticket Ticket to check
     * @return bool           True when the ticket is neither closed nor canceled
     */
    protected function isOpenTicket(Ticket $ticket): bool
    {
        return !in_array($ticket->fk_statut, [Ticket::STATUS_CANCELED, Ticket::STATUS_CLOSED]);
    }

    /**
     * Get the ticket list URL a graph bar links to
     *
     * The Digirisk left menu is kept selected so the list opens in the same navigation context as the dashboard.
     *
     * @param  string $searchFilter Search criteria of the native ticket list, already url encoded
     * @return string               Ticket list URL
     */
    protected function getTicketListUrl(string $searchFilter): string
    {
        return DOL_URL_ROOT . '/ticket/list.php?mainmenu=ticket&leftmenu=digiriskticketlist&' . $searchFilter;
    }

    /**
     * Get the hidden input holding the options the ticket dashboard JS needs to enhance a graph
     *
     * Dashboard graphs are drawn on a canvas by DolGraph, which handles neither a link per bar nor a second Y
     * axis, so both are declared here and applied by the ticket dashboard JS.
     *
     * @param  array $options Graph options: 'links' holds the ticket list URL of each bar, in the order of the data
     *                        rows, 'datasetLinks' the same thing per dataset when the series do not share a filter,
     *                        and 'secondAxisDataset' the index of the dataset to move to its own Y axis
     * @return string         Hidden input to append to the graph title, empty when the graph has no data
     */
    protected function getGraphOptionsInput(array $options): string
    {
        if (empty($options['links']) && empty($options['datasetLinks'])) {
            return '';
        }

        return '<input type="hidden" class="ticket-graph-options" value="' . dol_escape_htmltag(json_encode($options, JSON_UNESCAPED_UNICODE)) . '">';
    }
}
