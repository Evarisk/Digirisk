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
 *      \file       test/phpunit/DigiriskResourcesUnitTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf, $user, $langs, $db;

require_once __DIR__ . '/../../../../../htdocs/master.inc.php';
require_once __DIR__ . '/../../class/digiriskresources.class.php';

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
class DigiriskResourcesUnitTest extends PHPUnit\Framework\TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return DigiriskResourcesUnitTest
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
	 * testDigiriskResourcesCreate
	 *
	 * @covers DigiriskResources::create
	 *
	 * @return int
	 */
	public function testDigiriskResourcesCreate()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new DigiriskResources($this->savdb);

		$now                        = dol_now();
		$localobject->id            = 0;
		$localobject->ref           = "TestRefDigiriskResources";
		$localobject->ref_ext       = "TestRefExtDigiriskResources";
		$localobject->entity        = 1;
		$localobject->date_creation = $localobject->db->idate($now);
		$localobject->tms           = $now;
		$localobject->status        = 1;
		$localobject->element_type  = "societe";
		$localobject->element_id    = 1;
		$localobject->object_type   = "";
		$localobject->object_id     = 0;
		$localobject->fk_user_creat = $user->id ? $user->id : 1;

		$result = $localobject->create($user);

		$this->assertLessThan($result, 0);

		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testDigiriskResourcesFetch
	 *
	 * @param   int               $id          Id digirisk resource
	 * @return  DigiriskResources $localobject Digirisk resources object
	 *
	 * @covers  DigiriskResources::fetch
	 *
	 * @depends testDigiriskResourcesCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testDigiriskResourcesFetch($id)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new DigiriskResources($this->savdb);

		$result = $localobject->fetch($id);

		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testDigiriskResourcesUpdate
	 *
	 * @param   DigiriskResources $localobject Digirisk resources object
	 * @return  DigiriskResources $localobject Digirisk resources object
	 *
	 * @covers  DigiriskResources::update
	 *
	 * @depends testDigiriskResourcesFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testDigiriskResourcesUpdate($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$now                        = dol_now();
		$localobject->ref           = "UpdatedTestRefDigiriskResources";
		$localobject->ref_ext       = "UpdatedTestRefExtDigiriskResources";
		$localobject->entity        = 1;
		$localobject->date_creation = $now;
		$localobject->tms           = $now;
		$localobject->status        = 1;
		$localobject->element_type  = "societe";
		$localobject->element_id    = 1;
		$localobject->object_type   = "";
		$localobject->object_id     = 0;
		$localobject->fk_user_creat = $user->id ? $user->id : 1;

		$result = $localobject->update($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		$newobject = new DigiriskResources($this->savdb);
		$result = $newobject->fetch($localobject->id);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		$this->assertEquals($localobject->id, $newobject->id);
		$this->assertSame($localobject->ref, $newobject->ref);
		$this->assertSame($localobject->ref_ext, $newobject->ref_ext);
		$this->assertSame($localobject->entity, $newobject->entity);
		$this->assertSame($localobject->date_creation, $newobject->date_creation);
		$this->assertSame($localobject->tms, $newobject->tms);
		$this->assertEquals($localobject->status, $newobject->status);
		$this->assertSame($localobject->element_type, $newobject->element_type);
		$this->assertSame($localobject->element_id, $newobject->element_id);
		$this->assertSame($localobject->object_type, $newobject->object_type);
		$this->assertEquals($localobject->object_id, $newobject->object_id);
		$this->assertEquals($localobject->fk_user_creat, $newobject->fk_user_creat);

		return $localobject;
	}

	/**
	 * testDigiriskResourcesDigiriskDolibarrSetResources
	 *
	 * @covers DigiriskResources::digirisk_dolibarr_set_resources
	 *
	 * @return string $ref ref digirisk resource
	 */
	public function testDigiriskResourcesDigiriskDolibarrSetResources()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new DigiriskResources($this->savdb);

		$localobject->ref          = "TestRef2DigiriskResources";
		$localobject->entity       = 1;
		$localobject->element_type = "societe";
		$localobject->element_id   = 1;
		$localobject->object_type  = "";
		$localobject->object_id    = 0;
		$localobject->fk_user_creat = $user->id ? $user->id : 1;

		$result = $localobject->digirisk_dolibarr_set_resources($db, $localobject->fk_user_creat, $localobject->ref, $localobject->element_type, array($localobject->element_id), $localobject->entity, $localobject->object_type, $localobject->object_id, 0);

		$this->assertLessThan($result, 0);

		print __METHOD__." result=".$result."\n";
		return $localobject->ref;
	}

	/**
	 * testDigiriskResourcesFetch
	 *
	 * @param   string            $ref         ref digirisk resource
	 * @return  DigiriskResources $localobject Digirisk resources object
	 *
	 * @covers  DigiriskResources::digirisk_dolibarr_fetch_resource
	 *
	 * @depends testDigiriskResourcesDigiriskDolibarrSetResources
	 * The depends says test is run only if previous is ok
	 */
	public function testDigiriskResourcesDigiriskDdolibarrFetchResource($ref)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new DigiriskResources($this->savdb);

		$result = $localobject->digirisk_dolibarr_fetch_resource($ref);

		$this->assertLessThan($result, 0);

		print __METHOD__." ref=".$ref." result=".$result."\n";
		return $localobject;
	}

//	/**
//	 * testDigiriskResourcesDigiriskDolibarrFetchResourcesFromObject
//	 *
//	 * @return void
//	 *
//	 * @covers DigiriskResources::fetchResourcesFromObject
//	 *
//	 * @throws Exception
//	 */
//	public function testDigiriskResourcesDigiriskDolibarrFetchResourcesFromObject() : void
//	{
//		global $conf, $user, $langs, $db;
//		$conf = $this->savconf;
//		$user = $this->savuser;
//		$langs = $this->savlangs;
//		$db = $this->savdb;
//
//		$localobject = new DigiriskResources($this->savdb);
//		$localobjectList = $localobject->fetchResourcesFromObject();
//
//		$this->assertSame(true, is_array($localobjectList));
//		$this->assertInstanceOf(get_class($localobject), array_shift($localobjectList));
//		print __METHOD__ . " ok";
//		print "\n";
//	}

	/**
	 * testDigiriskResourcesDigiriskDolibarrFetchResources
	 *
	 * @return void
	 *
	 * @covers DigiriskResources::digirisk_dolibarr_fetch_resources
	 *
	 * @throws Exception
	 */
	public function testDigiriskResourcesDigiriskDolibarrFetchResources() : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new DigiriskResources($this->savdb);
		$localobjectList = $localobject->digirisk_dolibarr_fetch_resources();

		$this->assertSame(true, is_array($localobjectList));
		print __METHOD__ . " ok";
		print "\n";
	}

	/**
	 * testDigiriskResourcesFetchAll
	 *
	 * @return void
	 *
	 * @covers DigiriskResources::fetchAll
	 *
	 * @throws Exception
	 */
	public function testDigiriskResourcesFetchAll() : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new DigiriskResources($this->savdb);
		$localobjectList = $localobject->fetchAll();

		$this->assertSame(true, is_array($localobjectList));
		$this->assertInstanceOf(get_class($localobject), array_shift($localobjectList));
		print __METHOD__ . " ok";
		print "\n";
	}

	/**
	 * testDigiriskResourcesDelete
	 *
	 * @param   DigiriskResources $localobject Digirisk resources object
	 * @return  int
	 *
	 * @covers  DigiriskResources::delete
	 *
	 * @depends testDigiriskResourcesUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testDigiriskResourcesDelete($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$newobject = new DigiriskResources($this->savdb);
		$newobject->fetch($localobject->id);

		$result = $localobject->delete($user);
		print __METHOD__." id=".$newobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $result;
	}
}

