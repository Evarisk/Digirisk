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
 *      \file       test/phpunit/RiskSignUnitTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf, $user, $langs, $db;

require_once __DIR__ . '/../../../../../../htdocs/master.inc.php';
require_once __DIR__ . '/../../../class/riskanalysis/risksign.class.php';

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
class RiskSignUnitTest extends PHPUnit\Framework\TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return RiskSignUnitTest
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
	 * testRiskSignCreate
	 *
	 * @covers RiskSign::create
	 *
	 * @return int
	 */
	public function testRiskSignCreate()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new RiskSign($this->savdb);

		$now                         = dol_now();
		$localobject->id             = 0;
		//$localobject->ref          = $refRiskSignMod->getNextValue($localobject);
		$localobject->ref            = "TestRefRiskSign";
		$localobject->ref_ext        = "TestRefExtRiskSign";
		$localobject->entity         = 1;
		$localobject->date_creation  = $localobject->db->idate($now);
		$localobject->tms            = $now;
		$localobject->import_key     = 1;
		$localobject->status         = 1;
		$localobject->category       = 1;
		$localobject->description    = "TestDescriptionRiskSign";
		$localobject->fk_user_creat  = $user->id ? $user->id : 1;
		$localobject->fk_user_modif  = $user->id ? $user->id : 1;
		$localobject->fk_element     = 1;

		$result = $localobject->create($user);

		$this->assertLessThan($result, 0);

		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testRiskSignFetch
	 *
	 * @param   int      $id          Id risksign
	 * @return  RiskSign $localobject RiskSign object
	 *
	 * @covers  RiskSign::fetch
	 *
	 * @depends testRiskSignCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testRiskSignFetch($id)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new RiskSign($this->savdb);

		$result = $localobject->fetch($id);

		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testRiskSignFetchFormParent
	 *
	 * @param   RiskSign $localobject RiskSign object
	 * @return  void
	 *
	 * @covers  RiskSign::fetchFromParent
	 *
	 * @depends testRiskSignFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testRiskSignFetchFromParent($localobject)
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
	 * testRiskSignUpdate
	 *
	 * @param   RiskSign $localobject RiskSign object
	 * @return  RiskSign $localobject RiskSign object
	 *
	 * @covers  RiskSign::update
	 *
	 * @depends testRiskSignFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testRiskSignUpdate($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$now                          = dol_now();
		//$localobject->ref           = $refRiskSignMod->getNextValue($localobject);
		$localobject->ref             = "UpdatedTestRefRiskSign";
		$localobject->ref_ext         = "UpdatedTestRefExtRiskSign";
		$localobject->entity          = 1;
		$localobject->date_creation   = $now;
		$localobject->tms             = $now;
		$localobject->import_key      = 1;
		$localobject->status          = 1;
		$localobject->category        = 1;
		$localobject->description     = "UpdatedTestDescriptionRiskSign";
		$localobject->fk_user_creat   = $user->id ? $user->id : 1;
		$localobject->fk_user_modif   = $user->id ? $user->id : 1;
		$localobject->fk_element      = 1;

		$result = $localobject->update($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		$newobject = new RiskSign($this->savdb);
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

		return $localobject;
	}

	/**
	 * testRiskSignFetchAll
	 *
	 * @return void
	 *
	 * @covers RiskSign::fetchAll
	 *
	 * @throws Exception
	 */
	public function testRiskSignFetchAll() : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new RiskSign($this->savdb);
		$localobjectList = $localobject->fetchAll();

		$this->assertSame(true, is_array($localobjectList));
		$this->assertInstanceOf(get_class($localobject), array_shift($localobjectList));
		print __METHOD__ . " ok";
		print "\n";
	}

	/**
	 * testRiskSignGetRiskSignCategories
	 *
	 * @return void
	 *
	 * @covers RiskSign::get_risksign_categories
	 *
	 */
	public function testRiskSignGetRiskSignCategories() : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new RiskSign($this->savdb);
		$localobjectList = $localobject->get_risksign_categories();

		$this->assertSame(true, is_array($localobjectList));
		print __METHOD__ . " ok";
		print "\n";
	}

	/**
	 * testRiskSignGetRiskSignCategory
	 *
	 * @param   RiskSign $localobject RiskSign object
	 * @return  void
	 *
	 * @covers  RiskSign::get_risksign_category
	 *
	 * @depends testRiskSignFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testRiskSignGetRiskSignCategory($localobject) : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->get_risksign_category($localobject);

		$this->assertSame(true, is_string($result));

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
	}

	/**
	 * testRiskSignGetRiskSignCategoryName
	 *
	 * @param   RiskSign $localobject RiskSign object
	 * @return  string
	 *
	 * @covers  RiskSign::get_risksign_category_name
	 *
	 * @depends testRiskSignFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testRiskSignGetRiskSignCategoryName($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->get_risksign_category_name($localobject);

		$this->assertSame(true, is_string($result));

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		return $result;
	}

	/**
	 * testRiskSignDelete
	 *
	 * @param   RiskSign $localobject RiskSign object
	 * @return  int
	 *
	 * @covers  RiskSign::delete
	 *
	 * @depends testRiskSignUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testRiskSignDelete($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$newobject = new RiskSign($this->savdb);
		$newobject->fetch($localobject->id);

		$result = $localobject->delete($user);
		print __METHOD__." id=".$newobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $result;
	}
}

