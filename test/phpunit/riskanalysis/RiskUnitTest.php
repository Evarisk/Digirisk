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
 *      \file       test/phpunit/RiskUnitTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf, $user, $langs, $db;

require_once __DIR__ . '/../../../../../../htdocs/master.inc.php';
require_once __DIR__ . '/../../../class/riskanalysis/risk.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->getrights();
}

/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks  backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class RiskUnitTest extends PHPUnit\Framework\TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return RiskUnitTest
	 */
	public function __construct()
	{
		parent::__construct();

		//$this->sharedFixture
		global $conf, $user, $langs, $db;
		$this->savconf = $conf;
		$this->savuser = $user;
		$this->savlangs = $langs;
		$this->savdb = $db;

		print __METHOD__ . " db->type=".$db->type." user->id=".$user->id;
		print "\n";
		print __METHOD__ . " ok";
		print "\n";
	}

	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass() : void
	{
		global $conf, $db;

		if (empty($conf->digiriskdolibarr->enabled)) {
			print __METHOD__." module digiriskdolibarr must be enabled.\n"; die(1);
		}

		$db->begin(); // This is to have all actions inside a transaction even if test launched without suite.

		print __METHOD__."\n";
	}

	/**
	 * tearDownAfterClass
	 *
	 * @return	void
	 */
	public static function tearDownAfterClass() : void
	{
		global $db;
		$db->rollback();

		print __METHOD__."\n";
	}

	/**
	 * Init phpunit tests
	 *
	 * @return  void
	 */
	protected function setUp() : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		print __METHOD__."\n";
	}

	/**
	 * End phpunit tests
	 *
	 * @return  void
	 */
	protected function tearDown() : void
	{
		print __METHOD__."\n";
	}

	/**
	 * testRiskCreate
	 *
	 * @covers Risk::create
	 *
	 * @return int
	 */
	public function testRiskCreate()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Risk($this->savdb);

		$now                         = dol_now();
		$localobject->id             = 0;
		//$localobject->ref          = $refRiskMod->getNextValue($localobject);
		$localobject->ref            = "TestRefRisk";
		$localobject->ref_ext        = "TestRefExtRisk";
		$localobject->entity         = 1;
		$localobject->date_creation  = $localobject->db->idate($now);
		$localobject->tms            = $now;
		$localobject->import_key     = 1;
		$localobject->status         = 1;
		$localobject->category       = 1;
		$localobject->description    = "TestDescriptionRisk";
		$localobject->fk_user_creat  = $user->id ? $user->id : 1;
		$localobject->fk_user_modif  = $user->id ? $user->id : 1;
		$localobject->fk_element     = 1;
		$localobject->fk_projet      = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;

		$result = $localobject->create($user);

		$this->assertLessThan($result, 0);

		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testRiskFetch
	 *
	 * @param   int  $id          Id risk
	 * @return  Risk $localobject Risk object
	 *
	 * @covers  Risk::fetch
	 *
	 * @depends testRiskCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testRiskFetch($id)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Risk($this->savdb);

		$result = $localobject->fetch($id);

		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testRiskFetchFormParent
	 *
	 * @param   Risk $localobject Risk object
	 * @return  void
	 *
	 * @covers  Risk::fetchFromParent
	 *
	 * @depends testRiskFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testRiskFetchFromParent($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->fetchFromParent($localobject->fk_element);

		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
	}

	/**
	 * testRiskUpdate
	 *
	 * @param   Risk $localobject Risk object
	 * @return  Risk $localobject Risk object
	 *
	 * @covers  Risk::update
	 *
	 * @depends testRiskFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testRiskUpdate($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$now                          = dol_now();
		//$localobject->ref           = $refRiskMod->getNextValue($localobject);
		$localobject->ref             = "UpdatedTestRefRisk";
		$localobject->ref_ext         = "UpdatedTestRefExtRisk";
		$localobject->entity          = 1;
		$localobject->date_creation   = $now;
		$localobject->tms             = $now;
		$localobject->import_key      = 1;
		$localobject->status          = 1;
		$localobject->category        = 1;
		$localobject->description     = "UpdatedTestDescriptionRisk";
		$localobject->fk_user_creat   = $user->id ? $user->id : 1;
		$localobject->fk_user_modif   = $user->id ? $user->id : 1;
		$localobject->fk_element      = 1;
		$localobject->fk_projet       = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;

		$result = $localobject->update($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		$newobject = new Risk($this->savdb);
		$result = $newobject->fetch($localobject->id);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		$this->assertEquals($localobject->id, $newobject->id);
		$this->assertSame($localobject->ref, $newobject->ref);
		$this->assertSame($localobject->ref_ext, $newobject->ref_ext);
		$this->assertSame($localobject->entity, $newobject->entity);
		$this->assertSame($localobject->date_creation, $newobject->date_creation);
		$this->assertSame($localobject->tms, $newobject->tms);
		$this->assertEquals($localobject->import_key, $newobject->import_key);
		$this->assertEquals($localobject->status, $newobject->status);
		$this->assertEquals($localobject->category, $newobject->category);
		$this->assertSame($localobject->description, $newobject->description);
		$this->assertEquals($localobject->fk_user_creat, $newobject->fk_user_creat);
		$this->assertEquals($localobject->fk_user_modif, $newobject->fk_user_modif);
		$this->assertEquals($localobject->fk_element, $newobject->fk_element);
		$this->assertEquals($localobject->fk_projet, $newobject->fk_projet);

		return $localobject;
	}

	/**
	 * testRiskFetchAll
	 *
	 * @return void
	 *
	 * @covers Risk::fetchAll
	 *
	 * @throws Exception
	 */
	public function testRiskFetchAll() : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Risk($this->savdb);
		$localobjectList = $localobject->fetchAll();

		$this->assertSame(true, is_array($localobjectList));
		$this->assertInstanceOf(get_class($localobject), array_shift($localobjectList));
		print __METHOD__ . " ok";
		print "\n";
	}

	/**
	 * testRiskGetDangerCategories
	 *
	 * @return void
	 *
	 * @covers Risk::get_danger_categories
	 *
	 */
	public function testRiskGetDangerCategories() : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Risk($this->savdb);
		$localobjectList = $localobject->get_danger_categories();

		$this->assertSame(true, is_array($localobjectList));
		print __METHOD__ . " ok";
		print "\n";
	}

	/**
	 * testRiskGetDangerCategory
	 *
	 * @param   Risk $localobject Risk object
	 * @return  void
	 *
	 * @covers  Risk::get_danger_category
	 *
	 * @depends testRiskFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testRiskGetDangerCategory($localobject) : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->get_danger_category($localobject);

		$this->assertSame(true, is_string($result));

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
	}

	/**
	 * testRiskGetDangerCategoryName
	 *
	 * @param   Risk $localobject Risk object
	 * @return  string
	 *
	 * @covers  Risk::get_danger_category_name
	 *
	 * @depends testRiskFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testRiskGetDangerCategoryName($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->get_danger_category_name($localobject);

		$this->assertSame(true, is_string($result));

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		return $result;
	}

	/**
	 * testRiskGetDangerCategoryPositionByName
	 *
	 * @param   Risk $localobject Risk object
	 * @return  void
	 *
	 * @covers  Risk::get_danger_category_position_by_name
	 *
	 * @depends testRiskFetch
	 * @depends testRiskGetDangerCategoryName
	 * The depends says test is run only if previous is ok
	 */
	public function testRiskGetDangerCategoryPositionByName($localobject, $name) : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->get_danger_category_position_by_name($name);

		$this->assertSame(true, is_int($result));

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
	}

	/**
	 * testRiskGetFirePermitDangerCategories
	 *
	 * @return void
	 *
	 * @covers Risk::get_fire_permit_danger_categories
	 *
	 */
	public function testRiskGetFirePermitDangerCategories() : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Risk($this->savdb);
		$localobjectList = $localobject->get_fire_permit_danger_categories();

		$this->assertSame(true, is_array($localobjectList));
		print __METHOD__ . " ok";
		print "\n";
	}

	/**
	 * testRiskGetFirePermitDangerCategory
	 *
	 * @param   Risk $localobject Risk object
	 * @return  void
	 *
	 * @covers  Risk::get_fire_permit_danger_category
	 *
	 * @depends testRiskFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testRiskGetFirePermitDangerCategory($localobject) : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->get_fire_permit_danger_category($localobject);

		$this->assertSame(true, is_string($result));

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
	}

	/**
	 * testRiskGetFirePermitDangerCategoryName
	 *
	 * @param   Risk $localobject Risk object
	 * @return  string
	 *
	 * @covers  Risk::get_fire_permit_danger_category_name
	 *
	 * @depends testRiskFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testRiskGetFirePermitDangerCategoryName($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->get_fire_permit_danger_category_name($localobject);

		$this->assertSame(true, is_string($result));

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		return $result;
	}

	/**
	 * testRiskGetRelatedTasks
	 *
	 * @return  void
	 *
	 * @covers  Risk::get_related_tasks
	 *
	 * @depends testRiskFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testRiskGetRelatedTasks($localobject) : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobjectList = $localobject->get_related_tasks($localobject);

		if (empty($localobjectList)) {
			$this->assertNull($localobjectList);
		} else {
			$this->assertSame(true, is_array($localobjectList));
			$this->assertInstanceOf(get_class($localobject), array_shift($localobjectList));
		}

		print __METHOD__ . " ok";
		print "\n";
	}

	/**
	 * testRiskFetchRisksOrderedByCotation
	 *
	 * @return void
	 *
	 * @covers Risk::fetchRisksOrderedByCotation
	 *
	 * @depends testRiskFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testRiskFetchRisksOrderedByCotation($localobject) : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobjectList = $localobject->fetchRisksOrderedByCotation($localobject->fk_element);

		$this->assertSame(true, is_array($localobjectList));
		$this->assertInstanceOf(get_class($localobject), array_shift($localobjectList));
		print __METHOD__ . " ok";
		print "\n";
	}

	/**
	 * testRiskDelete
	 *
	 * @param   Risk $localobject Risk object
	 * @return  int
	 *
	 * @covers  Risk::delete
	 *
	 * @depends testRiskUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testRiskDelete($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$newobject = new Risk($this->savdb);
		$newobject->fetch($localobject->id);

		$result = $localobject->delete($user);
		print __METHOD__." id=".$newobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $result;
	}
}

