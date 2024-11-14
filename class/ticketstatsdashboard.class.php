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
 * \file    class/ticketdashboard.class.php
 * \ingroup digiriskdolibarr
 * \brief   Class file for manage TicketDashboard
 */

// load Dolibarr librairies
require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';


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
     * @var string SQL FROM
     */
    public string $from = '';

    /**
     * @var string|null SQL JOIN
     */
    public ?string $join = '';

    /**
     * @var string|null SQL WHERE
     */
    public ?string $where = '';

    /**
     * Constructor
     *
     * @param DoliDB $db             Database handler
     * @param string|null $moreJoin  More SQL JOIN
     * @param string|null $moreWhere More SQL filters (' AND ...')
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
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

        $allTickets = $this->getAllTickets();
        $societe = new Societe($this->db);

        $runningTickets = $this->getRunningTickets($allTickets);
        $ticketStats    = $this->getTicketStats($allTickets);

        $ticketRepartitionPerUser = $this->getTicketRepartitionPerUser($allTickets);
        $ticketRepartitionPerSoc = $this->getTicketRepartitionPerSoc($allTickets);

        $array['widgets'] = array_merge($runningTickets, $ticketStats);
        $array['graphs'] = [$ticketRepartitionPerUser, $ticketRepartitionPerSoc];

        return $array;
    }

    /**
     * Get running tickets
     *
     * @param  array $allTickets All tickets
     * @return array             Widget of running tickets
     */
    public function getRunningTickets(array $allTickets): array
    {
        $array = [];
        $ticket = new Ticket($this->db);

        $openTickets = 0;
        $oldestTicket = null;
        $oldestMessageTicket = null;
        foreach ($allTickets as $ticket) {
            if (!in_array($ticket->fk_statut, [Ticket::STATUS_CANCELED, Ticket::STATUS_CLOSED])) {
                $openTickets++;

                if (empty($oldestTicket) || $ticket->datec < $oldestTicket->datec) {
                    $oldestTicket = $ticket;
                }

                $lastComm = !empty($ticket->comms) ? current($ticket->comms) : null;
                if (!empty($lastComm) && (empty($oldestMessageTicket) || $lastComm->datec < $oldestMessageTicket->datec)) {
                    $oldestMessageTicket = $lastComm;
                }
            }
        }

        $array = [
            'runningTickets' => [
                'title'       => 'Tickets en cours',
                'widgetName'  => 'Tickets en cours',
                'picto'       => 'fas fa-ticket-alt',
                'pictoColor'  => '#0D8AFF',
                'label'       => ['Nombre de tickets ouverts', 'Ticket le plus ancien', 'Echange le plus vieux'],
                'content'     => [$openTickets, !empty($oldestTicket) ? dol_print_date($oldestTicket->datec, '%d/%m/%Y') . ' (' . convertSecondToTime(dol_now() - $oldestTicket->datec) . ')' : '', !empty($oldestMessageTicket) ? dol_print_date($oldestMessageTicket->datec, '%d/%m/%Y') . ' (' . convertSecondToTime(dol_now() - $oldestMessageTicket->datec) . ')' : ''],
                'moreContent' => ['', $oldestTicket->getNomUrl(2, 0, 0, 'marginleftonly'), !empty($oldestMessageTicket) ? $allTickets[$oldestMessageTicket->fk_element]->getNomUrl(2, 0, 0, 'marginleftonly') : ''],
            ]
        ];

        return $array;
    }

    /**
     * Get ticket stats
     *
     * @param  array $allTickets All tickets
     * @return array             Widget of ticket stats
     */
    function getTicketStats($allTickets) {
        $timePerTicket = [];

        $users = [];
        $nbTicketAssigned = 0;
        $nbExchanges = 0;
        foreach ($allTickets as $ticket) {
            if (!empty($ticket->date_close)) {
                array_push($timePerTicket, $ticket->date_close - $ticket->datec);
            }
            if (!empty($ticket->fk_user_assign)) {
                $nbTicketAssigned++;
                if (!in_array($ticket->fk_user_assign, $users)) {
                    $users[] = $ticket->fk_user_assign;
                }
            }
            $nbExchanges += count($ticket->comms);
        }

        $array = [
            'ticketStats' => [
                'title'       => 'Statistiques des tickets',
                'picto'       => 'fas fa-chart-pie',
                'pictoColor'  => '#32E592',
                'label'       => ['Nombre de tickets assignés par personne', 'Temps moyen de réponse', 'Nombre d\'échanges par ticket'],
                'content'     => [count($users) ? intdiv($nbTicketAssigned, count($users)) : 0, count($timePerTicket) ? convertSecondToTime(array_sum($timePerTicket) / count($timePerTicket), 'allwithouthour') : 'N/A', $allTickets ? ceil($nbExchanges / count($allTickets)) : 0],
                'moreContent' => [],
                'widgetName'  => 'Statistiques des tickets'
            ]
        ];

        return $array;
    }

    function getTicketRepartitionPerSoc($allTickets)
    {
        $soc = new Societe($this->db);
        $nbTicketBySoc = [];
        foreach ($allTickets as $ticket) {
            if ($ticket->fk_soc == null) {
                continue;
            }
            $soc->fetch($ticket->fk_soc);
            if (!isset($nbTicketBySoc[$soc->name])) {
                $nbTicketBySoc[$soc->name] = 0;
            }
            $nbTicketBySoc[$soc->name]++;
        }
        arsort($nbTicketBySoc);
        $nbTicketBySoc = array_slice($nbTicketBySoc, 0, 10);

        // Graph Title parameters
        $array['title'] = 'Top 10 des sociétés avec le plus de tickets';
        $array['picto'] = 'fas fa-chart-bar';

        // Graph parameters
        $array['width']      = '100%';
        $array['height']     = 300;
        $array['type']       = 'bar';
        $array['showlegend'] = 1;
        $array['dataset']    = 4;
        $array['moreCSS']    = 'grid-2';

        $array['labels'] = [[
            'label' => 'Nombre de tickets',
            'color' => '#A1467E'
        ]];

        foreach ($nbTicketBySoc as $socName => $nbTicket) {
            $dataElem = [
                $socName,
                $nbTicket
            ];
            $array['data'][] = $dataElem;
        }

        return $array;
    }

    function getTicketRepartitionPerUser($allTickets)
    {
        $user = new User($this->db);
        $nbTicketByUser = [];
        foreach ($allTickets as $ticket) {
            if ($ticket->fk_user_assign == null) {
                continue;
            }
            $user->fetch($ticket->fk_user_assign);
            if (!isset($nbTicketByUser[$user->lastname . ' ' . $user->firstname])) {
                $nbTicketByUser[$user->lastname . ' ' . $user->firstname] = 0;
            }
            $nbTicketByUser[$user->lastname . ' ' . $user->firstname]++;
        }
        arsort($nbTicketByUser);

        // Graph Title parameters
        $array['title'] = 'Repartition des tickets par utilisateur';
        $array['picto'] = 'fas fa-chart-bar';

        // Graph parameters
        $array['width']      = '100%';
        $array['height']     = 300;
        $array['type']       = 'bar';
        $array['showlegend'] = 1;
        $array['dataset']    = 4;
        $array['moreCSS']    = 'grid-2';

        $array['labels'] = [[
            'label' => 'Nombre de tickets',
        ]];

        foreach ($nbTicketByUser as $userName => $nbTicket) {
            $dataElem = [
                $userName,
                $nbTicket
            ];
            $array['data'][] = $dataElem;
        }

        return $array;
    }

    /**
     * Get all tickets
     *
     * @return array All tickets
     */
    public function getAllTickets(): array
    {
        global $conf;

        $actionComm = new ActionComm($this->db);
        $tickets = saturne_fetch_all_object_type('Ticket', 'DESC', 't.datec', 0, 0, ['customsql' => 't.entity = ' . $conf->entity], 'AND', true, true);

        if (!is_array($tickets))
            return [];

        foreach ($tickets as $rowid => $ticket) {
            $comms = $actionComm->getActions(0, $rowid, 'ticket', 'AND a.code LIKE "TICKET_MSG%"', 'a.datec', 'DESC');
            $ticket->comms = $comms;
        }

        return $tickets;
    }
}
