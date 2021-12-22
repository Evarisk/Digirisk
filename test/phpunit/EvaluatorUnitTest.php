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
 *      \file       test/phpunit/EvaluatorUnitTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf, $user, $langs, $db;

require_once __DIR__ . '/../../../../../htdocs/master.inc.php';
require_once __DIR__ . '/../../class/evaluator.class.php';

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
class EvaluatorUnitTest extends PHPUnit\Framework\TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return EvaluatorUnitTest
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
	 * testEvaluatorCreate
	 *
	 * @covers Evaluator::create
	 *
	 * @return int
	 */
	public function testEvaluatorCreate()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Evaluator($this->savdb);

		$now                          = dol_now();
		$localobject->id              = 0;
		//$localobject->ref           = $refEvaluatorMod->getNextValue($localobject);
		$localobject->ref             = "TestRefEvaluator";
		$localobject->ref_ext         = "TestRefExtEvaluator";
		$localobject->entity          = 1;
		$localobject->date_creation   = $localobject->db->idate($now);
		$localobject->tms             = $now;
		$localobject->import_key      = 1;
		$localobject->status          = 1;
		$localobject->duration        = 60;
		$localobject->assignment_date = $localobject->db->idate($now);
		$localobject->fk_user_creat   = $user->id ? $user->id : 1;
		$localobject->fk_user_modif   = $user->id ? $user->id : 1;
		$localobject->fk_user         = $user->id ? $user->id : 1;
		$localobject->fk_parent       = 1;

		$result = $localobject->create($user);

		$this->assertLessThan($result, 0);

		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testEvaluatorFetch
	 *
	 * @param   int       $id          Id evaluator
	 * @return  Evaluator $localobject Evaluator object
	 *
	 * @covers  Evaluator::fetch
	 *
	 * @depends testEvaluatorCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testEvaluatorFetch($id)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Evaluator($this->savdb);

		$result = $localobject->fetch($id);

		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testEvaluatorFetchFormParent
	 *
	 * @param   Evaluator $localobject Evaluator object
	 * @return  void
	 *
	 * @covers  Evaluator::fetchFromParent
	 *
	 * @depends testEvaluatorFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testEvaluatorFetchFromParent($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->fetchFromParent($localobject->fk_parent);

		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
	}

	/**
	 * testEvaluatorInfo
	 *
	 * @param   Evaluator $localobject Evaluator object
	 * @return  void
	 *
	 * @covers  Evaluator::info
	 *
	 * @depends testEvaluatorFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testEvaluatorInfo($localobject) : void
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
	 * testEvaluatorUpdate
	 *
	 * @param   Evaluator $localobject Evaluator object
	 * @return  Evaluator $localobject Evaluator object
	 *
	 * @covers  Evaluator::update
	 *
	 * @depends testEvaluatorFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testEvaluatorUpdate($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$now                          = dol_now();
		//$localobject->ref           = $refEvaluatorMod->getNextValue($localobject);
		$localobject->ref             = "UpdatedTestRefEvaluator";
		$localobject->ref_ext         = "UpdatedTestRefExtEvaluator";
		$localobject->entity          = 1;
		$localobject->date_creation   = $now;
		$localobject->tms             = $now;
		$localobject->import_key      = 1;
		$localobject->status          = 1;
		$localobject->duration        = 60;
		$localobject->assignment_date = $now;
		$localobject->fk_user_creat   = $user->id ? $user->id : 1;
		$localobject->fk_user_modif   = $user->id ? $user->id : 1;
		$localobject->fk_user         = $user->id ? $user->id : 1;
		$localobject->fk_parent       = 1;

		$result = $localobject->update($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		$newobject = new Evaluator($this->savdb);
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
		$this->assertEquals($localobject->duration, $newobject->duration);
		$this->assertSame($localobject->assignment_date, $newobject->assignment_date);
		$this->assertEquals($localobject->fk_user_creat, $newobject->fk_user_creat);
		$this->assertEquals($localobject->fk_user_modif, $newobject->fk_user_modif);
		$this->assertEquals($localobject->fk_user, $newobject->fk_user);
		$this->assertEquals($localobject->fk_parent, $newobject->fk_parent);

		return $localobject;
	}

	/**
	 * testEvaluatorFetchAll
	 *
	 * @return void
	 *
	 * @covers Evaluator::fetchAll
	 *
	 * @throws Exception
	 */
	public function testEvaluatorFetchAll() : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Evaluator($this->savdb);
		$localobjectList = $localobject->fetchAll();

		$this->assertSame(true, is_array($localobjectList));
		$this->assertInstanceOf(get_class($localobject), array_shift($localobjectList));
		print __METHOD__ . " ok";
		print "\n";
	}

	/**
	 * testEvaluatorDelete
	 *
	 * @param   Evaluator $localobject Evaluator object
	 * @return  int
	 *
	 * @covers  Evaluator::delete
	 *
	 * @depends testEvaluatorUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testEvaluatorDelete($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$newobject = new Evaluator($this->savdb);
		$newobject->fetch($localobject->id);

		$result = $localobject->delete($user);
		print __METHOD__." id=".$newobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $result;
	}
}

