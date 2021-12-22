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
 *      \file       test/phpunit/DigiriskElementUnitTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf, $user, $langs, $db;

require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../../../../htdocs/master.inc.php';

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
 * @remarks    backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class DigiriskElementUnitTest extends PHPUnit\Framework\TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return DigiriskElementUnitTest
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
	 * testDigiriskElementCreate
	 *
	 * @covers DigiriskElement::create
	 *
	 * @return int
	 */
	public function testDigiriskElementCreate()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new DigiriskElement($this->savdb);

		$now                        = dol_now();
		$localobject->id            = 0;
		//$localobject->ref         = $refPreventionPlanMod->getNextValue($localobject);
		$localobject->ref           = "TestRefPreventionPlan";
		$localobject->ref_ext       = "TestRefExtPreventionPlan";
		$localobject->entity        = 1;
		$localobject->date_creation = $localobject->db->idate($now);
		$localobject->tms           = $now;
		$localobject->import_key    = 1;
		$localobject->status        = 1;
		$localobject->label         = "TestLabelDigiriskElement";
		$localobject->description   = "TestDescriptionDigiriskElement";
		$localobject->element_type  = 'groupment';
		$localobject->photo         = 'test.png';
		$localobject->rank          = 1;
		$localobject->fk_user_creat = $user->id ? $user->id : 1;
		$localobject->fk_user_modif = $user->id ? $user->id : 1;
		$localobject->fk_parent     = 0;
		$localobject->fk_standard   = $conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD;

		$result = $localobject->create($user);

		$this->assertLessThan($result, 0);

		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testDigiriskElementFetch
	 *
	 * @param   int             $id          Id digiriskelement
	 * @return  DigiriskElement $localobject Digiriskelement object
	 *
	 * @covers  DigiriskElement::fetch
	 *
	 * @depends testDigiriskElementCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testDigiriskElementFetch($id)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new DigiriskElement($this->savdb);

		$result = $localobject->fetch($id);

		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testDigiriskElementInfo
	 *
	 * @param   DigiriskElement $localobject Digiriskelement object
	 * @return  void
	 *
	 * @covers  DigiriskElement::info
	 *
	 * @depends testDigiriskElementFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testDigiriskElementInfo($localobject) : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->info($localobject->id);
		$this->assertNull($result);

		print __METHOD__." id=".$localobject->id."\n";
	}

	/**
	 * testDigiriskElementUpdate
	 *
	 * @param   DigiriskElement $localobject Digirisk element object
	 * @return  DigiriskElement $localobject Digirisk element object
	 *
	 * @covers  DigiriskElement::update
	 *
	 * @depends testDigiriskElementFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testDigiriskElementUpdate($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$now                        = dol_now();
		//$localobject->ref         = $refPreventionPlanMod->getNextValue($localobject);
		$localobject->ref           = "UpdatedTestRefPreventionPlan";
		$localobject->ref_ext       = "UpdatedTestRefExtPreventionPlan";
		$localobject->entity        = 1;
		$localobject->date_creation = $now;
		$localobject->tms           = $now;
		$localobject->import_key    = 1;
		$localobject->status        = 1;
		$localobject->label         = "UpdatedTestLabelDigiriskElement";
		$localobject->description   = "UpdatedTestDescriptionDigiriskElement";
		$localobject->element_type  = 'groupment';
		$localobject->photo         = 'newtest.png';
		$localobject->rank          = 1;
		$localobject->fk_user_creat = $user->id ? $user->id : 1;
		$localobject->fk_user_modif = $user->id ? $user->id : 1;
		$localobject->fk_parent     = 0;
		$localobject->fk_standard   = $conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD;

		$result = $localobject->update($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		$newobject = new DigiriskElement($this->savdb);
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
		$this->assertSame($localobject->label, $newobject->label);
		$this->assertSame($localobject->description, $newobject->description);
		$this->assertSame($localobject->element_type, $newobject->element_type);
		$this->assertSame($localobject->photo, $newobject->photo);
		$this->assertSame($localobject->rank, $newobject->rank);
		$this->assertEquals($localobject->fk_user_creat, $newobject->fk_user_creat);
		$this->assertEquals($localobject->fk_user_modif, $newobject->fk_user_modif);
		$this->assertEquals($localobject->fk_parent, $newobject->fk_parent);
		$this->assertEquals($localobject->fk_standard, $newobject->fk_standard);

		return $localobject;
	}

	/**
	 * testDigiriskElementFetchAll
	 *
	 * @return void
	 *
	 * @covers DigiriskElement::fetchAll
	 *
	 * @throws Exception
	 */
	public function testDigiriskElementFetchAll()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new DigiriskElement($this->savdb);
		$localobjectList = $localobject->fetchAll();

		$this->assertSame(true, is_array($localobjectList));
		$this->assertInstanceOf(get_class($localobject), array_shift($localobjectList));
		print __METHOD__ . " ok";
		print "\n";
	}

	/**
	 * testDigiriskElementFetchDigiriskElementFlat
	 *
	 * @return void
	 *
	 * @covers DigiriskElement::fetchDigiriskElementFlat
	 *
	 */
	public function testDigiriskElementFetchDigiriskElementFlat()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new DigiriskElement($this->savdb);
		$localobjectFlatList = $localobject->fetchDigiriskElementFlat(0);

		$this->assertSame(true, is_array($localobjectFlatList));
		$this->assertInstanceOf(get_class($localobject), array_shift($localobjectFlatList)['object']);
		$this->assertIsInt(array_shift($localobjectFlatList)['depth']);
		print __METHOD__ . " ok";
		print "\n";
	}

	/**
	 * testDigiriskElementDelete
	 *
	 * @param   DigiriskElement $localobject Digiriskelement object
	 * @return  int
	 *
	 * @covers  DigiriskElement::delete
	 *
	 * @depends testDigiriskElementUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testDigiriskElementDelete($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$newobject = new DigiriskElement($this->savdb);
		$newobject->fetch($localobject->id);

		$result = $localobject->delete($user);
		print __METHOD__." id=".$newobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $result;
	}
}

