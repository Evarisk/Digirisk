# Widget Vigilance Météo-France — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Afficher sur le tableau de bord Digirisk (`digiriskdolibarrindex.php`) une carte de vigilance météo Météo-France pour le département de la société, plus un bandeau d'alerte si vigilance orange/rouge.

**Architecture:** Une classe autonome `MeteoVigilance` (client API DPVigilance + cache fichier TTL + parsing + construction du widget) branchée sur l'agrégation `DigiriskDolibarrDashboard`. Le bandeau est rendu par un TPL injecté via le hook `saturneIndex` de `ActionsDigiriskdolibarr`. La clé API et le département sont configurés dans une page admin.

**Tech Stack:** PHP 8.1 / Dolibarr 23 / framework Saturne, `getURLContent()` (Dolibarr), PHPUnit (`test/phpunit/`), SCSS (gulp), JSON Météo-France DPVigilance.

**Référence spec :** `docs/superpowers/specs/2026-06-08-meteo-vigilance-widget-design.md`

**Conventions :**
- Nouveaux fichiers PHP : PSR-12, **4 espaces**. Éditions de fichiers existants : **respecter l'indentation déjà présente dans le fichier** (les classes existantes mélangent tabs/espaces).
- Commentaire sur la ligne **au-dessus** du code. Pas de JS/CSS/HTML inline dans les `.php` (sauf `customContent`, champ HTML prévu par `SaturneDashboard`, et les TPL).
- Format de commit : `#4767 [MeteoVigilance] {type}: {description}`.
- Ne **jamais** committer `css/*.min.css` / `js/*.min.js` (la CI les génère). Ne pas pousser sans demande explicite.

---

## File Structure

| Fichier | Création/Modif | Responsabilité |
|---|---|---|
| `class/meteovigilance.class.php` | **Create** | Client API + cache + parsing + `load_dashboard()` widget + helpers couleurs/labels. |
| `test/phpunit/MeteoVigilanceUnitTest.php` | **Create** | Tests du parsing pur et des helpers. |
| `class/digiriskdolibarrdashboard.class.php` | Modify | Enregistrer le type `MeteoVigilance` dans l'agrégation. |
| `digiriskdolibarrindex.php` | Modify | Activer le chargement du widget (`LoadMeteoVigilance`). |
| `langs/fr_FR/digiriskdolibarr.lang` | Modify | Libellés FR. |
| `langs/en_US/digiriskdolibarr.lang` | Modify | Libellés EN. |
| `admin/config/meteovigilance.php` | **Create** | Page de config (clé API, département, TTL). |
| `lib/digiriskdolibarr.lib.php` | Modify | Onglet admin « Vigilance Météo ». |
| `core/tpl/meteovigilance/banner.tpl.php` | **Create** | Rendu HTML du bandeau d'alerte. |
| `class/actions_digiriskdolibarr.class.php` | Modify | Hook `saturneIndex` → bandeau. |
| `core/modules/modDigiriskDolibarr.class.php` | Modify | Enregistrer le contexte de hook `digiriskdolibarrindex`. |
| `css/scss/page/_page-meteovigilance.scss` | **Create** | Styles badges + bandeau. |
| `css/scss/page/_page.scss` | Modify | Importer le partial. |

---

## Task 1: Libellés de traduction (FR + EN)

**Files:**
- Modify: `langs/fr_FR/digiriskdolibarr.lang` (fin de fichier)
- Modify: `langs/en_US/digiriskdolibarr.lang` (fin de fichier)

- [ ] **Step 1: Ajouter les clés FR**

À la fin de `langs/fr_FR/digiriskdolibarr.lang`, ajouter :

```
# MeteoVigilance - Vigilance Météo-France
MeteoVigilance = Vigilance météo
MeteoVigilanceLevel = Niveau de vigilance
MeteoVigilanceActivePhenomena = Phénomènes actifs
MeteoVigilanceNoAlert = Aucune vigilance particulière
MeteoVigilanceSource = Source
MeteoVigilanceSeeOnMeteoFrance = Voir sur vigilance.meteofrance.fr
MeteoVigilanceNotConfigured = Clé API Météo-France non configurée — cliquez pour configurer
MeteoVigilanceUnknownDepartment = Département de la société non déterminé
MeteoVigilanceUnavailable = Données de vigilance momentanément indisponibles
MeteoVigilanceBannerTitle = Vigilance %s en cours sur le département %s
MeteoVigilanceLevel1 = Vert
MeteoVigilanceLevel2 = Jaune
MeteoVigilanceLevel3 = Orange
MeteoVigilanceLevel4 = Rouge
MeteoVigilancePhenomenon1 = Vent violent
MeteoVigilancePhenomenon2 = Pluie-inondation
MeteoVigilancePhenomenon3 = Orages
MeteoVigilancePhenomenon4 = Crues
MeteoVigilancePhenomenon5 = Neige-verglas
MeteoVigilancePhenomenon6 = Canicule
MeteoVigilancePhenomenon7 = Grand-froid
MeteoVigilancePhenomenon8 = Avalanches
MeteoVigilancePhenomenon9 = Vagues-submersion
MeteoVigilanceSetup = Configuration Vigilance Météo
MeteoVigilanceApiKey = Clé API Météo-France
MeteoVigilanceApiKeyDescription = Clé API du portail Météo-France (créez un compte et une application « Bulletin Vigilance » sur <a href="https://portail-api.meteofrance.fr" target="_blank">portail-api.meteofrance.fr</a> pour générer la clé).
MeteoVigilanceDepartment = Code département (override)
MeteoVigilanceDepartmentDescription = Laissez vide pour déduire automatiquement le département de l'adresse de la société. Sinon, saisissez un code (ex. 35, 2A).
MeteoVigilanceCacheTTL = Durée du cache (secondes)
MeteoVigilanceCacheTTLDescription = Durée pendant laquelle les données de l'API sont mises en cache avant un nouvel appel (défaut 3600).
MeteoVigilanceSettings = Configuration Vigilance Météo
```

- [ ] **Step 2: Ajouter les clés EN**

À la fin de `langs/en_US/digiriskdolibarr.lang`, ajouter :

```
# MeteoVigilance - Météo-France weather warnings
MeteoVigilance = Weather warning
MeteoVigilanceLevel = Warning level
MeteoVigilanceActivePhenomena = Active phenomena
MeteoVigilanceNoAlert = No particular warning
MeteoVigilanceSource = Source
MeteoVigilanceSeeOnMeteoFrance = View on vigilance.meteofrance.fr
MeteoVigilanceNotConfigured = Météo-France API key not configured — click to configure
MeteoVigilanceUnknownDepartment = Company department could not be determined
MeteoVigilanceUnavailable = Warning data temporarily unavailable
MeteoVigilanceBannerTitle = %s warning in effect for department %s
MeteoVigilanceLevel1 = Green
MeteoVigilanceLevel2 = Yellow
MeteoVigilanceLevel3 = Orange
MeteoVigilanceLevel4 = Red
MeteoVigilancePhenomenon1 = Strong wind
MeteoVigilancePhenomenon2 = Rain-flood
MeteoVigilancePhenomenon3 = Thunderstorms
MeteoVigilancePhenomenon4 = Floods
MeteoVigilancePhenomenon5 = Snow-ice
MeteoVigilancePhenomenon6 = Heatwave
MeteoVigilancePhenomenon7 = Extreme cold
MeteoVigilancePhenomenon8 = Avalanches
MeteoVigilancePhenomenon9 = Coastal flooding
MeteoVigilanceSetup = Weather warning configuration
MeteoVigilanceApiKey = Météo-France API key
MeteoVigilanceApiKeyDescription = API key from the Météo-France portal (create an account and a "Bulletin Vigilance" application on <a href="https://portail-api.meteofrance.fr" target="_blank">portail-api.meteofrance.fr</a> to generate the key).
MeteoVigilanceDepartment = Department code (override)
MeteoVigilanceDepartmentDescription = Leave empty to auto-detect the department from the company address. Otherwise enter a code (e.g. 35, 2A).
MeteoVigilanceCacheTTL = Cache duration (seconds)
MeteoVigilanceCacheTTLDescription = How long API data is cached before a new call (default 3600).
MeteoVigilanceSettings = Weather warning configuration
```

- [ ] **Step 3: Commit**

```bash
git add langs/fr_FR/digiriskdolibarr.lang langs/en_US/digiriskdolibarr.lang
git commit -m "#4767 [MeteoVigilance] feat: add weather warning translations (FR/EN)"
```

---

## Task 2: Classe `MeteoVigilance` — parsing pur + helpers (TDD)

**Files:**
- Create: `test/phpunit/MeteoVigilanceUnitTest.php`
- Create: `class/meteovigilance.class.php`

- [ ] **Step 1: Écrire le test qui échoue**

Créer `test/phpunit/MeteoVigilanceUnitTest.php` :

```php
<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       test/phpunit/MeteoVigilanceUnitTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for MeteoVigilance parsing
 *      \remarks    To run this script as CLI:  phpunit MeteoVigilanceUnitTest.php
 */

global $conf, $user, $langs, $db;

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../../htdocs/master.inc.php")) {
    $res = require_once dirname(__FILE__) . '/../../../htdocs/master.inc.php';
}
if (!$res && file_exists("../../../../htdocs/master.inc.php")) {
    $res = require_once dirname(__FILE__) . '/../../../../htdocs/master.inc.php';
}
if (!$res && file_exists("../../../../../htdocs/master.inc.php")) {
    $res = require_once dirname(__FILE__) . '/../../../../../htdocs/master.inc.php';
}
if (!$res) {
    die("Include of main fails");
}

require_once __DIR__ . '/../../class/meteovigilance.class.php';

/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks  backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class MeteoVigilanceUnitTest extends PHPUnit\Framework\TestCase
{
    /**
     * Sample DPVigilance JSON (department 31 = orange, with canicule + orages ;
     * department 75 = green ; a J1 period that must be ignored).
     *
     * @return string
     */
    private function sampleJson(): string
    {
        return json_encode([
            'product' => [
                'update_time' => '2026-06-08T06:00:00Z',
                'domain_id'   => 'FRA',
                'periods'     => [
                    [
                        'echeance' => 'J',
                        'timelaps' => [
                            'domain_ids' => [
                                [
                                    'domain_id'        => '31',
                                    'max_color_id'     => 3,
                                    'phenomenon_items' => [
                                        ['phenomenon_id' => '6', 'phenomenon_max_color_id' => 3],
                                        ['phenomenon_id' => '3', 'phenomenon_max_color_id' => 2],
                                        ['phenomenon_id' => '1', 'phenomenon_max_color_id' => 1],
                                    ],
                                ],
                                [
                                    'domain_id'        => '75',
                                    'max_color_id'     => 1,
                                    'phenomenon_items' => [],
                                ],
                            ],
                        ],
                    ],
                    [
                        'echeance' => 'J1',
                        'timelaps' => [
                            'domain_ids' => [
                                ['domain_id' => '31', 'max_color_id' => 4, 'phenomenon_items' => []],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test parsing an orange department with active phenomena.
     *
     * @return void
     */
    public function testParseReturnsLevelAndActivePhenomena(): void
    {
        $result = MeteoVigilance::parseVigilance($this->sampleJson(), '31');

        $this->assertIsArray($result);
        $this->assertSame(3, $result['level']);
        // Only phenomena with level >= 2 are kept (id 1 at level 1 is dropped).
        $this->assertCount(2, $result['phenomena']);
        $this->assertSame('6', $result['phenomena'][0]['id']);
        $this->assertSame(3, $result['phenomena'][0]['level']);
        $this->assertSame('3', $result['phenomena'][1]['id']);
    }

    /**
     * Test parsing a green department (no active phenomenon).
     *
     * @return void
     */
    public function testParseGreenDepartment(): void
    {
        $result = MeteoVigilance::parseVigilance($this->sampleJson(), '75');

        $this->assertIsArray($result);
        $this->assertSame(1, $result['level']);
        $this->assertSame([], $result['phenomena']);
    }

    /**
     * Test that an unknown department returns null.
     *
     * @return void
     */
    public function testParseUnknownDepartmentReturnsNull(): void
    {
        $this->assertNull(MeteoVigilance::parseVigilance($this->sampleJson(), '99'));
    }

    /**
     * Test that invalid JSON returns null.
     *
     * @return void
     */
    public function testParseInvalidJsonReturnsNull(): void
    {
        $this->assertNull(MeteoVigilance::parseVigilance('not json', '31'));
        $this->assertNull(MeteoVigilance::parseVigilance('{}', '31'));
    }

    /**
     * Test the color helper.
     *
     * @return void
     */
    public function testLevelColor(): void
    {
        $this->assertSame('#2BAD4E', MeteoVigilance::getLevelColor(1));
        $this->assertSame('#FFD600', MeteoVigilance::getLevelColor(2));
        $this->assertSame('#FF7A00', MeteoVigilance::getLevelColor(3));
        $this->assertSame('#E1031A', MeteoVigilance::getLevelColor(4));
        $this->assertSame('#2BAD4E', MeteoVigilance::getLevelColor(0));
    }
}
```

- [ ] **Step 2: Lancer le test pour vérifier qu'il échoue**

Run: `cd test/phpunit && phpunit MeteoVigilanceUnitTest.php`
Expected: FAIL — `Class "MeteoVigilance" not found` (le fichier classe n'existe pas encore).

- [ ] **Step 3: Créer la classe avec parsing + helpers**

Créer `class/meteovigilance.class.php` :

```php
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
```

- [ ] **Step 4: Lancer le test pour vérifier qu'il passe**

Run: `cd test/phpunit && phpunit MeteoVigilanceUnitTest.php`
Expected: PASS (5 tests, 0 failures).

- [ ] **Step 5: PHPCS sur le nouveau fichier**

Run: `~/.composer/vendor/bin/phpcs --standard=.phpcs.xml --extensions=php class/meteovigilance.class.php`
Expected: aucune erreur (corriger via `phpcbf` si besoin).

- [ ] **Step 6: Commit**

```bash
git add class/meteovigilance.class.php test/phpunit/MeteoVigilanceUnitTest.php
git commit -m "#4767 [MeteoVigilance] feat: add DPVigilance parsing and color helpers with tests"
```

---

## Task 3: `MeteoVigilance` — département, fetch/cache, widget

**Files:**
- Modify: `class/meteovigilance.class.php`

- [ ] **Step 1: Ajouter la résolution du département**

Dans `class/meteovigilance.class.php`, **après** la méthode `getPhenomenonLabel()`, ajouter :

```php
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
```

- [ ] **Step 2: Ajouter le fetch avec cache TTL**

Toujours dans la classe, après `getDepartmentCode()`, ajouter :

```php
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
```

- [ ] **Step 3: Ajouter le builder de badge et `load_dashboard()`**

Toujours dans la classe, après `getHighestLevel()`, ajouter :

```php
    /**
     * Build a small colored badge (HTML) for the dashboard widget customContent field.
     *
     * @param  string $label Text shown in the badge
     * @param  int    $level Vigilance level driving the color
     * @return string        HTML span
     */
    private static function renderBadge(string $label, int $level): string
    {
        return '<span class="meteo-vigilance-badge meteo-vigilance-level-' . $level . '">' . dol_escape_htmltag($label) . '</span>';
    }

    /**
     * Build the dashboard widget consumed by SaturneDashboard::show_dashboard().
     *
     * @return array ['widgets' => ['meteovigilance' => [...]]]
     */
    public function load_dashboard(): array
    {
        global $langs;

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

        // Nominal rendering.
        $level                   = (int) $vigilance['level'];
        $widget['pictoColor']    = self::getLevelColor($level);
        $widget['label']         = [$langs->transnoentities('MeteoVigilanceLevel') . ' (' . dol_escape_htmltag($departmentCode) . ')'];
        $widget['customContent'] = [self::renderBadge(self::getLevelLabel($level), $level)];

        $widget['label'][] = $langs->transnoentities('MeteoVigilanceActivePhenomena');
        if (!empty($vigilance['phenomena'])) {
            $badges = '';
            foreach ($vigilance['phenomena'] as $phenomenon) {
                $badges .= self::renderBadge(self::getPhenomenonLabel($phenomenon['id']), (int) $phenomenon['level']) . ' ';
            }
            $widget['customContent'][] = $badges;
        } else {
            $widget['customContent'][] = $langs->transnoentities('MeteoVigilanceNoAlert');
        }

        $widget['label'][]         = $langs->transnoentities('MeteoVigilanceSource');
        $widget['customContent'][] = '<a href="https://vigilance.meteofrance.fr/fr" target="_blank" rel="noopener">' . $langs->transnoentities('MeteoVigilanceSeeOnMeteoFrance') . '</a>';

        return ['widgets' => ['meteovigilance' => $widget]];
    }
```

- [ ] **Step 4: PHPCS**

Run: `~/.composer/vendor/bin/phpcs --standard=.phpcs.xml --extensions=php class/meteovigilance.class.php`
Expected: aucune erreur.

- [ ] **Step 5: Ajouter un test pour l'override de département**

Dans `test/phpunit/MeteoVigilanceUnitTest.php`, ajouter cette méthode à la classe `MeteoVigilanceUnitTest` :

```php
    /**
     * Test that the admin override takes precedence over the company department.
     *
     * @return void
     */
    public function testGetDepartmentCodeOverride(): void
    {
        global $conf, $db;

        $saved = getDolGlobalString('DIGIRISKDOLIBARR_METEOFRANCE_VIGILANCE_DEPARTMENT');
        $conf->global->DIGIRISKDOLIBARR_METEOFRANCE_VIGILANCE_DEPARTMENT = '2a';

        $meteoVigilance = new MeteoVigilance($db);
        $this->assertSame('2A', $meteoVigilance->getDepartmentCode());

        $conf->global->DIGIRISKDOLIBARR_METEOFRANCE_VIGILANCE_DEPARTMENT = $saved;
    }
```

- [ ] **Step 6: Lancer les tests (non régression + override)**

Run: `cd test/phpunit && phpunit MeteoVigilanceUnitTest.php`
Expected: PASS (6 tests) — le parsing reste vert et l'override renvoie `2A`.

- [ ] **Step 7: Commit**

```bash
git add class/meteovigilance.class.php test/phpunit/MeteoVigilanceUnitTest.php
git commit -m "#4767 [MeteoVigilance] feat: add department resolution, TTL cache fetch and dashboard widget"
```

---

## Task 4: Brancher le widget sur le tableau de bord

**Files:**
- Modify: `class/digiriskdolibarrdashboard.class.php:54-63`
- Modify: `digiriskdolibarrindex.php:46-56`

- [ ] **Step 1: Enregistrer le type dans l'agrégation**

Dans `class/digiriskdolibarrdashboard.class.php`, dans le tableau `$dashboardDatas` de `load_dashboard()`, ajouter une entrée à la fin de la liste (après la ligne `TicketStatsDashboard`) :

```php
            ['type' => 'TicketStatsDashboard',   'classPath' => '/ticketstatsdashboard.class.php'],
            ['type' => 'MeteoVigilance',         'classPath' => '/meteovigilance.class.php']
```

(Ajouter une virgule à la fin de la ligne `TicketStatsDashboard` existante et la nouvelle ligne ensuite.)

- [ ] **Step 2: Activer le chargement du widget**

Dans `digiriskdolibarrindex.php`, dans le tableau `$moreParams`, ajouter la clé `LoadMeteoVigilance` (après `LoadTicketDashboard`) :

```php
    'LoadTicketDashboard'        => 1,
    'LoadMeteoVigilance'         => 1,
    'specialModuleNameLowerCase' => 'digirisk'
```

- [ ] **Step 3: Vérification manuelle de la carte**

Ouvrir le tableau de bord Digirisk (`digiriskdolibarrindex.php`) dans le navigateur.
Expected (clé API non encore configurée) : une carte « Vigilance météo » apparaît avec le message « Clé API Météo-France non configurée — cliquez pour configurer ». Aucune erreur PHP dans le log.

- [ ] **Step 4: Commit**

```bash
git add class/digiriskdolibarrdashboard.class.php digiriskdolibarrindex.php
git commit -m "#4767 [MeteoVigilance] feat: register vigilance widget on the dashboard"
```

---

## Task 5: Page de configuration admin

**Files:**
- Create: `admin/config/meteovigilance.php`
- Modify: `lib/digiriskdolibarr.lib.php:73-74` (après l'onglet « accident »)

- [ ] **Step 1: Créer la page de config**

Créer `admin/config/meteovigilance.php` :

```php
<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    admin/config/meteovigilance.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr Météo-France vigilance configuration page.
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

global $conf, $db, $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

require_once __DIR__ . '/../../lib/digiriskdolibarr.lib.php';

// Translations
saturne_load_langs(['admin']);

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$apiKey     = GETPOST('MeteoVigilanceApiKey', 'alpha');
$department = GETPOST('MeteoVigilanceDepartment', 'alpha');
$cacheTtl   = GETPOSTINT('MeteoVigilanceCacheTTL');

// Security check - Protection if external user
$permissiontoread = $user->rights->digiriskdolibarr->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

if ($action == 'update') {
    dolibarr_set_const($db, 'DIGIRISKDOLIBARR_METEOFRANCE_VIGILANCE_API_KEY', $apiKey, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'DIGIRISKDOLIBARR_METEOFRANCE_VIGILANCE_DEPARTMENT', strtoupper(trim($department)), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'DIGIRISKDOLIBARR_METEOFRANCE_VIGILANCE_CACHE_TTL', ($cacheTtl > 0 ? $cacheTtl : 3600), 'integer', 0, '', $conf->entity);
    setEventMessages($langs->trans('SetupSaved'), []);
}

/*
 * View
 */

$title   = $langs->trans('ModuleSetup', $moduleName);
$helpUrl = 'FR:Module_Digirisk';

saturne_header(0, '', $title, $helpUrl);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header
$head = digiriskdolibarr_admin_prepare_head();
print dol_get_fiche_head($head, 'meteovigilance', $title, -1, 'digiriskdolibarr_color@digiriskdolibarr');

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td>' . $langs->trans('Value') . '</td>';
print '</tr>';

print '<tr class="oddeven"><td><label for="MeteoVigilanceApiKey">' . $langs->trans('MeteoVigilanceApiKey') . '</label></td>';
print '<td>' . $langs->trans('MeteoVigilanceApiKeyDescription') . '</td>';
print '<td><input type="text" name="MeteoVigilanceApiKey" value="' . dol_escape_htmltag(getDolGlobalString('DIGIRISKDOLIBARR_METEOFRANCE_VIGILANCE_API_KEY')) . '" size="50"></td></tr>';

print '<tr class="oddeven"><td><label for="MeteoVigilanceDepartment">' . $langs->trans('MeteoVigilanceDepartment') . '</label></td>';
print '<td>' . $langs->trans('MeteoVigilanceDepartmentDescription') . '</td>';
print '<td><input type="text" name="MeteoVigilanceDepartment" value="' . dol_escape_htmltag(getDolGlobalString('DIGIRISKDOLIBARR_METEOFRANCE_VIGILANCE_DEPARTMENT')) . '" size="6"></td></tr>';

print '<tr class="oddeven"><td><label for="MeteoVigilanceCacheTTL">' . $langs->trans('MeteoVigilanceCacheTTL') . '</label></td>';
print '<td>' . $langs->trans('MeteoVigilanceCacheTTLDescription') . '</td>';
print '<td><input type="number" name="MeteoVigilanceCacheTTL" value="' . (getDolGlobalInt('DIGIRISKDOLIBARR_METEOFRANCE_VIGILANCE_CACHE_TTL') ?: 3600) . '" min="60"></td></tr>';

print '</table>';
print '<div class="center"><input type="submit" class="button" name="save" value="' . $langs->trans('Save') . '"></div>';
print '</form>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
```

- [ ] **Step 2: Ajouter l'onglet admin**

Dans `lib/digiriskdolibarr.lib.php`, dans `digiriskdolibarr_admin_prepare_head()`, **juste après** le bloc de l'onglet `accident` (qui se termine par `$h++;` après `$head[$h][2] = 'accident';`), insérer :

```php
    $head[$h][0] = dol_buildpath('/digiriskdolibarr/admin/config/meteovigilance.php', 1);
    $head[$h][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-cloud-sun-rain pictofixedwidth"></i>' . $langs->trans('MeteoVigilance') : '<i class="fas fa-cloud-sun-rain"></i>';
    $head[$h][2] = 'meteovigilance';
    $h++;
```

- [ ] **Step 3: Vérification manuelle**

Aller dans Configuration du module Digirisk → onglet « Vigilance météo ».
Expected : le formulaire s'affiche. Saisir une **vraie clé API Météo-France** et enregistrer → message « Setup saved ». Recharger le tableau de bord → la carte affiche le niveau réel du département de la société (couleur + phénomènes), ou « Aucune vigilance particulière » si vert.

- [ ] **Step 4: PHPCS sur la page admin**

Run: `~/.composer/vendor/bin/phpcs --standard=.phpcs.xml --extensions=php admin/config/meteovigilance.php`
Expected : aucune erreur.

- [ ] **Step 5: Commit**

```bash
git add admin/config/meteovigilance.php lib/digiriskdolibarr.lib.php
git commit -m "#4767 [MeteoVigilance] feat: add admin configuration page (API key, department, cache TTL)"
```

---

## Task 6: Bandeau d'alerte (TPL + hook)

**Files:**
- Create: `core/tpl/meteovigilance/banner.tpl.php`
- Modify: `class/actions_digiriskdolibarr.class.php` (ajouter la méthode `saturneIndex`)
- Modify: `core/modules/modDigiriskDolibarr.class.php:444` (ajouter le contexte de hook)

- [ ] **Step 1: Créer le TPL du bandeau**

Créer `core/tpl/meteovigilance/banner.tpl.php` :

```php
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
    $phenomenaLabels[] = MeteoVigilance::getPhenomenonLabel($phenomenon['id']);
}
?>
<div class="wpeo-notice <?php echo $noticeClass; ?> meteo-vigilance-banner">
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
```

- [ ] **Step 2: Ajouter la méthode hook `saturneIndex`**

Dans `class/actions_digiriskdolibarr.class.php`, ajouter la méthode suivante à l'intérieur de la classe `ActionsDigiriskdolibarr` (par exemple juste avant `printCommonFooter`). **Respecter l'indentation du fichier** :

```php
    /**
     * Overloading the saturneIndex hook: print a weather vigilance banner on the Digirisk dashboard.
     *
     * @param  array        $parameters Hook metadata (context, etc...)
     * @param  CommonObject $object     Current object
     * @return int                      0 to let Dolibarr continue
     */
    public function saturneIndex($parameters, &$object)
    {
        if (strpos($parameters['context'], 'digiriskdolibarrindex') === false) {
            return 0;
        }

        require_once __DIR__ . '/meteovigilance.class.php';

        $meteoVigilance = new MeteoVigilance($this->db);
        $vigilance      = $meteoVigilance->fetchVigilance();
        if (is_array($vigilance) && (int) $vigilance['level'] >= 3) {
            global $langs;
            $departmentCode = $meteoVigilance->getDepartmentCode();
            ob_start();
            require __DIR__ . '/../core/tpl/meteovigilance/banner.tpl.php';
            $this->resprints = ob_get_clean();
        }

        return 0;
    }
```

- [ ] **Step 3: Enregistrer le contexte de hook**

Dans `core/modules/modDigiriskDolibarr.class.php`, dans le tableau `'hooks' => [ ... ]` (vers la ligne 444), ajouter le contexte `digiriskdolibarrindex`. Modifier la dernière entrée pour ajouter la virgule et la nouvelle ligne :

```php
				'saturnegetobjectsmetadata',
				'digiriskdolibarrindex'
			],
```

- [ ] **Step 4: Réenregistrer les hooks du module**

Le contexte de hook est persisté en base lors de l'activation du module. Pour le prendre en compte :

Run (UI) : Accueil → Configuration → Modules → désactiver puis réactiver « Digirisk ».
(ou en CLI si disponible : `php htdocs/install/upgrade.php` n'est pas requis — la désactivation/réactivation suffit.)

Expected : aucune erreur ; le module reste actif.

- [ ] **Step 5: Vérification manuelle du bandeau**

Pour tester sans attendre une vraie alerte : en config admin, mettre temporairement dans le champ « Code département (override) » un département actuellement en vigilance orange/rouge (vérifier sur https://vigilance.meteofrance.fr/fr), puis recharger le tableau de bord.
Expected : un bandeau orange (ou rouge) « Vigilance Orange en cours sur le département XX » apparaît **au-dessus** du dashboard, avec la liste des phénomènes. Remettre ensuite l'override à vide.
Si aucun département n'est en alerte au moment du test, vérifier au minimum qu'**aucun** bandeau ne s'affiche en vigilance verte/jaune.

- [ ] **Step 6: PHPCS**

Run: `~/.composer/vendor/bin/phpcs --standard=.phpcs.xml --extensions=php class/actions_digiriskdolibarr.class.php`
Expected : pas de **nouvelle** erreur introduite par la méthode ajoutée (le fichier peut déjà avoir des avertissements préexistants ; ne pas reformater le reste).

- [ ] **Step 7: Commit**

```bash
git add core/tpl/meteovigilance/banner.tpl.php class/actions_digiriskdolibarr.class.php core/modules/modDigiriskDolibarr.class.php
git commit -m "#4767 [MeteoVigilance] feat: add orange/red alert banner via saturneIndex hook"
```

---

## Task 7: Styles SCSS (badges + bandeau)

**Files:**
- Create: `css/scss/page/_page-meteovigilance.scss`
- Modify: `css/scss/page/_page.scss`

- [ ] **Step 1: Créer le partial SCSS**

Créer `css/scss/page/_page-meteovigilance.scss` :

```scss
.mod-digiriskdolibarr {
    .meteo-vigilance-badge {
        display: inline-block;
        margin: 2px 4px 2px 0;
        padding: 2px 10px;
        border-radius: 12px;
        color: #fff;
        font-weight: 600;
        font-size: 12px;
        line-height: 18px;
        white-space: nowrap;

        &.meteo-vigilance-level-1 {
            background: #2BAD4E;
        }

        &.meteo-vigilance-level-2 {
            background: #FFD600;
            color: #333;
        }

        &.meteo-vigilance-level-3 {
            background: #FF7A00;
        }

        &.meteo-vigilance-level-4 {
            background: #E1031A;
        }
    }

    .meteo-vigilance-banner {
        .notice-title i {
            margin-right: 8px;
        }
    }
}
```

- [ ] **Step 2: Importer le partial**

Dans `css/scss/page/_page.scss`, ajouter à la fin :

```scss
@import "page-meteovigilance";
```

- [ ] **Step 3: Vérifier la compilation**

Le watcher gulp de l'utilisateur recompile automatiquement `css/saturne.min.css`. Vérifier qu'aucune erreur SCSS n'apparaît dans le terminal du watcher, et que les badges sont colorés dans le widget (recharger le tableau de bord).
Expected : badges arrondis colorés (vert/jaune/orange/rouge) ; bandeau avec icône d'alerte.

- [ ] **Step 4: Commit (sans les .min)**

```bash
git add css/scss/page/_page-meteovigilance.scss css/scss/page/_page.scss
git commit -m "#4767 [MeteoVigilance] feat: add SCSS for vigilance badges and banner"
```

> Ne pas `git add` les fichiers `css/saturne.min.css*` — ils sont en `assume-unchanged` et générés par la CI.

---

## Task 8: Intégration des tests + vérification finale

**Files:**
- Modify: `test/phpunit/AllTests.php`

- [ ] **Step 1: Lire la suite de tests existante**

Ouvrir `test/phpunit/AllTests.php` et repérer le pattern d'enregistrement des suites (`require_once` du fichier de test + `$suite->addTestSuite('XxxUnitTest')`).

- [ ] **Step 2: Ajouter la suite MeteoVigilance**

En suivant exactement le même pattern que les entrées existantes, ajouter :
- un `require_once dirname(__FILE__).'/MeteoVigilanceUnitTest.php';` à côté des autres `require_once` de tests ;
- un `$suite->addTestSuite('MeteoVigilanceUnitTest');` à côté des autres `addTestSuite`.

- [ ] **Step 3: Lancer la suite complète**

Run: `cd test/phpunit && phpunit MeteoVigilanceUnitTest.php`
Expected : PASS (5 tests). 

(Optionnel, si l'environnement de DB de test est configuré : `phpunit AllTests.php` doit aussi passer la nouvelle suite.)

- [ ] **Step 4: PHPStan (non-régression)**

Run: `vendor/bin/phpstan analyse --memory-limit=512M class/meteovigilance.class.php`
Expected : 0 erreur (sinon corriger les types ; ne pas régénérer le baseline pour ce fichier neuf).

- [ ] **Step 5: Revue manuelle de bout en bout**

Vérifier dans l'ordre :
1. Sans clé API → carte « non configurée » + lien admin, pas de bandeau.
2. Avec clé API valide + département vert → carte « Aucune vigilance particulière », pas de bandeau.
3. Override département en alerte orange/rouge → carte avec badges + **bandeau** en tête.
4. Override département invalide (ex. `99`) ou société sans département → carte « indéterminé » / « indisponible », pas de fatal.
5. Recharger 2× de suite : le 2e chargement lit le cache (`documents/digiriskdolibarr/temp/meteovigilance_<code>.json` présent et récent).

- [ ] **Step 6: Commit**

```bash
git add test/phpunit/AllTests.php
git commit -m "#4767 [MeteoVigilance] test: register MeteoVigilance suite in AllTests"
```

---

## Notes d'implémentation

- **Authentification API** : la méthode principale est l'en-tête `apikey: <clé>` (clé applicative du portail). Si, à l'usage, l'API renvoie 401 avec une clé valide, c'est que l'application est configurée en OAuth2 : ajouter alors une étape d'échange `POST https://portail-api.meteofrance.fr/token` (`grant_type=client_credentials`, en-tête `Authorization: Basic <application id>`) pour obtenir un Bearer token (≈1 h), à mettre en cache séparément. Le reste du flux est inchangé.
- **Périodes J/J1** : on ne lit que `echeance == 'J'` (vigilance en cours). J1 (lendemain) est volontairement ignoré (hors périmètre).
- **Sécurité SSRF** : `getURLContent()` autorise les hôtes publics https comme `public-api.meteofrance.fr` ; aucune action requise.
- **Aucune modification hors `htdocs/custom/digiriskdolibarr/`**.
