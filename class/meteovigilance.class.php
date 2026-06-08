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
 * \file    class/meteovigilance.class.php
 * \ingroup digiriskdolibarr
 * \brief   Class file to fetch and expose Météo-France vigilance data on the dashboard
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/geturl.lib.php';

/**
 * Class for Météo-France vigilance dashboard widget
 */
class MeteoVigilance
{
    /**
     * @var DoliDB Database handler
     */
    public DoliDB $db;

    /**
     * @var string Météo-France DPVigilance "en cours" endpoint
     */
    public const API_URL = 'https://public-api.meteofrance.fr/public/DPVigilance/v1/cartevigilance/encours';

    /**
     * @var int Default cache lifetime in seconds
     */
    public const DEFAULT_CACHE_TTL = 3600;

    /**
     * @var array<string, array|null> Memoized vigilance per department code (one API call per request)
     */
    private array $memo = [];

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    /**
     * Parse a DPVigilance JSON payload for a given department, current day ("J").
     *
     * @param  string      $json           Raw JSON returned by the API
     * @param  string      $departmentCode Department code (e.g. "31", "2A")
     * @return array|null                  ['level' => int, 'phenomena' => [['id'=>string,'level'=>int]], 'update_time' => string] or null
     */
    public static function parseVigilance(string $json, string $departmentCode): ?array
    {
        $data = json_decode($json, true);
        if (!is_array($data) || empty($data['product']['periods'])) {
            return null;
        }

        $departmentCode = strtoupper(trim($departmentCode));

        foreach ($data['product']['periods'] as $period) {
            if (($period['echeance'] ?? '') !== 'J') {
                continue;
            }
            foreach ($period['timelaps']['domain_ids'] ?? [] as $domain) {
                if (strtoupper((string) ($domain['domain_id'] ?? '')) !== $departmentCode) {
                    continue;
                }

                $phenomena = [];
                foreach ($domain['phenomenon_items'] ?? [] as $item) {
                    $phenomenonLevel = (int) ($item['phenomenon_max_color_id'] ?? 1);
                    if ($phenomenonLevel >= 2) {
                        $phenomena[] = [
                            'id'    => (string) ($item['phenomenon_id'] ?? ''),
                            'level' => $phenomenonLevel,
                        ];
                    }
                }

                return [
                    'level'       => (int) ($domain['max_color_id'] ?? 1),
                    'phenomena'   => $phenomena,
                    'update_time' => (string) ($data['product']['update_time'] ?? ''),
                ];
            }
        }

        return null;
    }

    /**
     * Return the hex color associated with a vigilance level.
     *
     * @param  int    $level Vigilance level (1 green .. 4 red)
     * @return string        Hex color
     */
    public static function getLevelColor(int $level): string
    {
        switch ($level) {
            case 4:
                return '#E1031A';
            case 3:
                return '#FF7A00';
            case 2:
                return '#FFD600';
            default:
                return '#2BAD4E';
        }
    }

    /**
     * Return the translated label of a vigilance level.
     *
     * @param  int    $level Vigilance level (1 .. 4)
     * @return string        Translated label
     */
    public static function getLevelLabel(int $level): string
    {
        global $langs;

        $level = ($level >= 1 && $level <= 4) ? $level : 1;
        return $langs->transnoentities('MeteoVigilanceLevel' . $level);
    }

    /**
     * Return the translated label of a phenomenon id.
     *
     * @param  string $id Phenomenon id ("1" .. "9")
     * @return string     Translated label
     */
    public static function getPhenomenonLabel(string $id): string
    {
        global $langs;

        return $langs->transnoentities('MeteoVigilancePhenomenon' . $id);
    }
}
