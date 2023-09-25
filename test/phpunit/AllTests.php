<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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

use PHPUnit\Framework\TestSuite;

print "PHP Version: " . phpversion() . "\n";
print "Memory: " . ini_get('memory_limit') . "\n";

global $conf,$user,$langs,$db;

//define('TEST_DB_FORCE_TYPE','mysql'); // This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';

// Load Dolibarr environment
$res = 0;
// Try main.inc.php using relative path
if (!$res && file_exists("../../../htdocs/master.inc.php")) {
	$res = require_once dirname(__FILE__).'/../../../htdocs/master.inc.php';
}
if (!$res && file_exists("../../../../htdocs/master.inc.php")) {
	$res = require_once dirname(__FILE__).'/../../../../htdocs/master.inc.php';
}
if (!$res && file_exists("../../../../../htdocs/master.inc.php")) {
	$res = require_once dirname(__FILE__).'/../../../../../htdocs/master.inc.php';;
}
if (!$res) {
	die("Include of main fails");
}

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
	 * @return TestSuite
	 */
	public static function suite()
	{
		$suite = new PHPUnit\Framework\TestSuite('PHPUnit Framework');

		require_once dirname(__FILE__) . '/DigiriskStandardUnitTest.php';
		$suite->addTestSuite('DigiriskStandardUnitTest');

		require_once dirname(__FILE__) . '/DigiriskElementUnitTest.php';
		$suite->addTestSuite('DigiriskElementUnitTest');

		require_once dirname(__FILE__) . '/DigiriskResourcesUnitTest.php';
		$suite->addTestSuite('DigiriskResourcesUnitTest');

		require_once dirname(__FILE__) . '/DigiriskDocumentsUnitTest.php';
		$suite->addTestSuite('DigiriskDocumentsUnitTest');

		require_once dirname(__FILE__) . '/EvaluatorUnitTest.php';
		$suite->addTestSuite('EvaluatorUnitTest');

		require_once dirname(__FILE__) . '/DigiriskSignatureUnitTest.php';
		$suite->addTestSuite('DigiriskSignatureUnitTest');

		require_once dirname(__FILE__) . '/riskanalysis/RiskUnitTest.php';
		$suite->addTestSuite('RiskUnitTest');

		require_once dirname(__FILE__) . '/riskanalysis/RiskAssessmentUnitTest.php';
		$suite->addTestSuite('RiskAssessmentUnitTest');

		require_once dirname(__FILE__) . '/riskanalysis/RiskSignUnitTest.php';
		$suite->addTestSuite('RiskSignUnitTest');

		require_once dirname(__FILE__) . '/PreventionPlanUnitTest.php';
		$suite->addTestSuite('PreventionPlanUnitTest');

		require_once dirname(__FILE__) . '/FirePermitUnitTest.php';
		$suite->addTestSuite('FirePermitUnitTest');

		require_once dirname(__FILE__) . '/AccidentUnitTest.php';
		$suite->addTestSuite('AccidentUnitTest');

		return $suite;
	}
}
