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
     * Test parsing an orange department: all phenomena are returned with their level.
     *
     * @return void
     */
    public function testParseReturnsLevelAndAllPhenomena(): void
    {
        $result = MeteoVigilance::parseVigilance($this->sampleJson(), '31');

        $this->assertIsArray($result);
        $this->assertSame(3, $result['level']);
        // All phenomena are kept, including green (level 1) ones, so the widget can show every type.
        $this->assertCount(3, $result['phenomena']);
        $this->assertSame('6', $result['phenomena'][0]['id']);
        $this->assertSame(3, $result['phenomena'][0]['level']);
        $this->assertSame('3', $result['phenomena'][1]['id']);
        $this->assertSame('1', $result['phenomena'][2]['id']);
        $this->assertSame(1, $result['phenomena'][2]['level']);
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
}
