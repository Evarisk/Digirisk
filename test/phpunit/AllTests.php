<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
 *      \file       test/phpunit/AllTests.php
 *      \ingroup    test
 *      \brief      This file is a test suite to run all unit tests
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

print "PHP Version: ".phpversion()."\n";
print "Memory: ". ini_get('memory_limit')."\n";

global $conf,$user,$langs,$db;

//define('TEST_DB_FORCE_TYPE','mysql'); // This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once __DIR__ . '/../../../../../htdocs/master.inc.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->getrights();
}

/**
 * Class for the All test suite
 */
class AllTests
{
	/**
	 * Function suite to make all PHPUnit tests
	 *
	 * @return	void
	 */
	public static function suite()
	{
		$suite = new PHPUnit\Framework\TestSuite('PHPUnit Framework');

		require_once dirname(__FILE__).'/DigiriskStandardUnitTest.php';
		$suite->addTestSuite('DigiriskStandardUnitTest');

		require_once dirname(__FILE__).'/DigiriskElementUnitTest.php';
		$suite->addTestSuite('DigiriskElementUnitTest');

		require_once dirname(__FILE__).'/DigiriskResourcesUnitTest.php';
		$suite->addTestSuite('DigiriskResourcesUnitTest');

		require_once dirname(__FILE__).'/EvaluatorUnitTest.php';
		$suite->addTestSuite('EvaluatorUnitTest');

		require_once dirname(__FILE__).'/PreventionPlanUnitTest.php';
		$suite->addTestSuite('PreventionPlanUnitTest');

		require_once dirname(__FILE__).'/OpeninghoursUnitTest.php';
		$suite->addTestSuite('OpeninghoursUnitTest');

		require_once dirname(__FILE__).'/FirePermitUnitTest.php';
		$suite->addTestSuite('FirePermitUnitTest');

		return $suite;
	}
}
