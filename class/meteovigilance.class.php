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
                    $phenomena[] = [
                        'id'    => (string) ($item['phenomenon_id'] ?? ''),
                        'level' => (int) ($item['phenomenon_max_color_id'] ?? 1),
                    ];
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

    /**
     * Resolve the department code to monitor: admin override first, else the company department.
     *
     * @return string Department code (e.g. "31", "2A"), or '' if undetermined
     */
    public function getDepartmentCode(): string
    {
        global $mysoc;

        $override = getDolGlobalString('DIGIRISKDOLIBARR_METEOFRANCE_VIGILANCE_DEPARTMENT');
        if (!empty($override)) {
            return strtoupper(trim($override));
        }

        if (empty($mysoc->state_id)) {
            return '';
        }

        $sql   = 'SELECT code_departement FROM ' . MAIN_DB_PREFIX . 'c_departements WHERE rowid = ' . ((int) $mysoc->state_id);
        $resql = $this->db->query($sql);
        if ($resql && $this->db->num_rows($resql) > 0) {
            $obj = $this->db->fetch_object($resql);
            $this->db->free($resql);
            return strtoupper(trim($obj->code_departement));
        }

        return '';
    }

    /**
     * Fetch the current vigilance for the configured department, using a TTL file cache.
     * Returns null when not configured, department undetermined, or data unavailable.
     *
     * @return array|null ['level' => int, 'phenomena' => array, 'update_time' => string] or null
     */
    public function fetchVigilance(): ?array
    {
        global $conf;

        $departmentCode = $this->getDepartmentCode();
        if (empty($departmentCode)) {
            return null;
        }
        if (array_key_exists($departmentCode, $this->memo)) {
            return $this->memo[$departmentCode];
        }

        $apiKey = getDolGlobalString('DIGIRISKDOLIBARR_METEOFRANCE_VIGILANCE_API_KEY');
        if (empty($apiKey)) {
            return $this->memo[$departmentCode] = null;
        }

        $ttl       = getDolGlobalInt('DIGIRISKDOLIBARR_METEOFRANCE_VIGILANCE_CACHE_TTL');
        $ttl       = $ttl > 0 ? $ttl : self::DEFAULT_CACHE_TTL;
        $cacheDir  = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/temp';
        $cacheFile = $cacheDir . '/meteovigilance_' . dol_sanitizeFileName($departmentCode) . '.json';

        // Serve fresh cache when available.
        if (dol_is_file($cacheFile) && (dol_filemtime($cacheFile) > (dol_now() - $ttl))) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if (is_array($cached)) {
                return $this->memo[$departmentCode] = $cached;
            }
        }

        // Call the API with a short timeout (connect 5s, response 10s) so a slow API never blocks the dashboard.
        $response = getURLContent(self::API_URL, 'GET', '', 1, ['apikey: ' . $apiKey, 'Accept: */*'], ['http', 'https'], 0, -1, 5, 10);
        if (($response['http_code'] ?? 0) == 200 && !empty($response['content'])) {
            $parsed = self::parseVigilance($response['content'], $departmentCode);
            if (is_array($parsed)) {
                dol_mkdir($cacheDir);
                file_put_contents($cacheFile, json_encode($parsed));
                return $this->memo[$departmentCode] = $parsed;
            }
        } else {
            dol_syslog('MeteoVigilance::fetchVigilance API error http_code=' . ($response['http_code'] ?? '?') . ' ' . ($response['curl_error_msg'] ?? ''), LOG_WARNING);
        }

        // Fallback to stale cache if the call failed.
        if (dol_is_file($cacheFile)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if (is_array($cached)) {
                return $this->memo[$departmentCode] = $cached;
            }
        }

        return $this->memo[$departmentCode] = null;
    }

    /**
     * Return the highest current vigilance level (for the alert banner). 0 when none/unavailable.
     *
     * @return int
     */
    public function getHighestLevel(): int
    {
        $vigilance = $this->fetchVigilance();
        return is_array($vigilance) ? (int) $vigilance['level'] : 0;
    }

    /**
     * Return a FontAwesome icon class for a phenomenon id.
     *
     * @param  string $id Phenomenon id ("1" .. "9")
     * @return string     FontAwesome class
     */
    public static function getPhenomenonIcon(string $id): string
    {
        $icons = [
            '1' => 'fas fa-wind',
            '2' => 'fas fa-cloud-showers-heavy',
            '3' => 'fas fa-bolt',
            '4' => 'fas fa-water',
            '5' => 'fas fa-snowflake',
            '6' => 'fas fa-thermometer-full',
            '7' => 'fas fa-thermometer-empty',
            '8' => 'fas fa-mountain',
            '9' => 'fas fa-water',
        ];

        return $icons[$id] ?? 'fas fa-exclamation-triangle';
    }

    /**
     * Return the department name for a department code (for display), or '' if unknown.
     *
     * @param  string $code Department code (e.g. "34", "2A")
     * @return string       Department name (e.g. "Hérault")
     */
    public function getDepartmentName(string $code): string
    {
        if (empty($code)) {
            return '';
        }

        $sql   = "SELECT nom FROM " . MAIN_DB_PREFIX . "c_departements WHERE code_departement = '" . $this->db->escape($code) . "'";
        $resql = $this->db->query($sql);
        if ($resql && $this->db->num_rows($resql) > 0) {
            $obj = $this->db->fetch_object($resql);
            $this->db->free($resql);
            return $obj->nom;
        }

        return '';
    }

    /**
     * Render a colored level dot (FontAwesome circle, inline color) for the widget rows.
     *
     * @param  int    $level Vigilance level (1 green .. 4 red)
     * @return string        HTML icon
     */
    private static function renderLevelDot(int $level): string
    {
        return '<i class="fas fa-circle" style="color: ' . self::getLevelColor($level) . ';" title="' . dol_escape_htmltag(self::getLevelLabel($level)) . '"></i>';
    }

    /**
     * Build the dashboard widget consumed by SaturneDashboard::show_dashboard().
     *
     * @return array ['widgets' => ['meteovigilance' => [...]]]
     */
    public function load_dashboard(): array
    {
        global $langs;

        // Feature disabled in the module configuration (off by default): no widget at all.
        if (!getDolGlobalInt('DIGIRISKDOLIBARR_METEOFRANCE_VIGILANCE_ENABLED')) {
            return [];
        }

        $widget = [
            'title'      => $langs->transnoentities('MeteoVigilance'),
            'picto'      => 'fas fa-cloud-sun-rain',
            'pictoColor' => '#9E9E9E',
            'widgetName' => $langs->transnoentities('MeteoVigilance'),
        ];

        // Not configured.
        if (empty(getDolGlobalString('DIGIRISKDOLIBARR_METEOFRANCE_VIGILANCE_API_KEY'))) {
            $configUrl               = dol_buildpath('/digiriskdolibarr/admin/config/meteovigilance.php', 1);
            $widget['label']         = [$langs->transnoentities('MeteoVigilanceLevel')];
            $widget['customContent'] = ['<a href="' . $configUrl . '">' . $langs->transnoentities('MeteoVigilanceNotConfigured') . '</a>'];
            return ['widgets' => ['meteovigilance' => $widget]];
        }

        // Department undetermined.
        $departmentCode = $this->getDepartmentCode();
        if (empty($departmentCode)) {
            $widget['label']         = [$langs->transnoentities('MeteoVigilanceLevel')];
            $widget['customContent'] = [$langs->transnoentities('MeteoVigilanceUnknownDepartment')];
            return ['widgets' => ['meteovigilance' => $widget]];
        }

        // Data unavailable.
        $vigilance = $this->fetchVigilance();
        if (!is_array($vigilance)) {
            $widget['label']         = [$langs->transnoentities('MeteoVigilanceLevel')];
            $widget['customContent'] = [$langs->transnoentities('MeteoVigilanceUnavailable')];
            return ['widgets' => ['meteovigilance' => $widget]];
        }

        // Nominal rendering, using the native dashboard rows (label on the left, colored
        // level dot on the right). Colors are applied inline, like the framework does for
        // its pictos, so the widget needs no extra compiled CSS to look right.
        $level                = (int) $vigilance['level'];
        $widget['pictoColor'] = self::getLevelColor($level);
        $departmentName       = $this->getDepartmentName($departmentCode);
        $location             = trim(($departmentName !== '' ? $departmentName . ' ' : '') . '(' . $departmentCode . ')');

        $widget['label']         = [];
        $widget['customContent'] = [];

        // Overall vigilance line: alert type on the left, colored level dot on the right.
        $summaryLabel = ($level >= 2 ? '<i class="fas fa-exclamation-triangle" style="color: ' . self::getLevelColor($level) . ';"></i> ' : '')
            . '<strong>' . $langs->trans('MeteoVigilancePanelTitle', self::getLevelLabel($level)) . '</strong> · ' . dol_escape_htmltag($location);
        if ($level >= 2) {
            $summaryLabel .= ' — ' . $langs->transnoentities('MeteoVigilanceAdvice' . $level);
        }
        $widget['label'][]         = $summaryLabel;
        $widget['customContent'][] = self::renderLevelDot($level);

        // One row per phenomenon: icon + name on the left, colored level dot on the right.
        if (!empty($vigilance['phenomena'])) {
            foreach ($vigilance['phenomena'] as $phenomenon) {
                $phenomenonId              = (string) $phenomenon['id'];
                $widget['label'][]         = '<i class="' . self::getPhenomenonIcon($phenomenonId) . '"></i> ' . dol_escape_htmltag(self::getPhenomenonLabel($phenomenonId));
                $widget['customContent'][] = self::renderLevelDot((int) $phenomenon['level']);
            }
        } else {
            $widget['label'][]         = $langs->transnoentities('MeteoVigilanceNoAlert');
            $widget['customContent'][] = self::renderLevelDot($level);
        }

        // Footer line: last update time + link to Météo-France.
        if (!empty($vigilance['update_time'])) {
            $widget['label'][]         = $langs->trans('MeteoVigilanceUpdatedAt', dol_print_date(strtotime($vigilance['update_time']), 'dayhour'));
            $widget['customContent'][] = '<a href="https://vigilance.meteofrance.fr/fr" target="_blank" rel="noopener">' . $langs->transnoentities('MeteoVigilanceSeeOnMeteoFrance') . '</a>';
        }

        return ['widgets' => ['meteovigilance' => $widget]];
    }
}
