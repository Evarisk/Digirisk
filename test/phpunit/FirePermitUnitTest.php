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
 *      \file       test/phpunit/FirePermitUnitTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf, $user, $langs, $db;

require_once __DIR__ . '/../../../../../htdocs/master.inc.php';
require_once __DIR__ . '/../../class/firepermit.class.php';

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
class FirePermitUnitTest extends PHPUnit\Framework\TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return FirePermitUnitTest
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
	 * testFirePermitCreate
	 *
	 * @covers FirePermit::create
	 *
	 * @return int
	 */
	public function testFirePermitCreate()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new FirePermit($this->savdb);

		$now                               = dol_now();
		$localobject->id                   = 0;
		//$localobject->ref                = $refFirePermitMod->getNextValue($localobject);
		$localobject->ref                  = "TestRefFirePermit";
		$localobject->ref_ext              = "TestRefExtFirePermit";
		$localobject->entity               = 1;
		$localobject->date_creation        = $localobject->db->idate($now);
		$localobject->tms                  = $now;
		$localobject->status               = 1;
		$localobject->label                = "TestFirePermit";
		$localobject->fk_project           = $conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_PROJECT;
		$localobject->date_start           = $localobject->db->idate($now);
		$localobject->date_end             = $localobject->db->idate($now);
		$localobject->last_email_sent_date = $localobject->db->idate($now);
		$localobject->fk_user_creat        = $user->id ? $user->id : 1;
		$localobject->fk_user_modif        = $user->id ? $user->id : 1;
		$localobject->fk_preventionplan    = 1;

		$result = $localobject->create($user);

		$this->assertLessThan($result, 0);

		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testFirePermitFetch
	 *
	 * @param   int            $id          Id fire permit
	 * @return  FirePermit $localobject Fire permit object
	 *
	 * @covers  FirePermit::fetch
	 *
	 * @depends testFirePermitCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testFirePermitFetch($id)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new FirePermit($this->savdb);

		$result = $localobject->fetch($id);

		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testFirePermitInfo
	 *
	 * @param   FirePermit $localobject Fire permit object
	 * @return  void
	 *
	 * @covers  FirePermit::info
	 *
	 * @depends testFirePermitFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testFirePermitInfo($localobject) : void
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
	 * testFirePermitSetInProgress
	 *
	 * @param   FirePermit $localobject Fire permit object
	 * @return  void
	 *
	 * @covers  FirePermit::setInProgress
	 * @covers  FirePermit::getLibStatut
	 * @covers  FirePermit::LibStatut
	 *
	 * @depends testFirePermitFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testFirePermitSetInProgress($localobject) : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->setInProgress($user);
		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$result = $localobject->getLibStatut(0);
		$this->assertSame($result, $langs->trans('InProgress'));

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
	}

	/**
	 * testFirePermitSetPendingSignature
	 *
	 * @param   FirePermit $localobject Fire permit object
	 * @return  void
	 *
	 * @covers  FirePermit::setPendingSignature
	 * @covers  FirePermit::getLibStatut
	 * @covers  FirePermit::LibStatut
	 *
	 * @depends testFirePermitFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testFirePermitSetPendingSignature($localobject) : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->setPendingSignature($user);
		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$result = $localobject->getLibStatut(0);
		$this->assertSame($result, $langs->trans('ValidatePendingSignature'));

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
	}

	/**
	 * testFirePermitSetLocked
	 *
	 * @param   FirePermit $localobject Fire permit object
	 * @return  void
	 *
	 * @covers  FirePermit::setLocked
	 * @covers  FirePermit::getLibStatut
	 * @covers  FirePermit::LibStatut
	 *
	 * @depends testFirePermitFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testFirePermitSetLocked($localobject) : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->setLocked($user);
		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$result = $localobject->getLibStatut(0);
		$this->assertSame($result, $langs->trans('Locked'));

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
	}

	/**
	 * testFirePermitSetArchived
	 *
	 * @param   FirePermit $localobject Fire permit object
	 * @return  void
	 *
	 * @covers  FirePermit::setArchived
	 * @covers  FirePermit::getLibStatut
	 * @covers  FirePermit::LibStatut
	 *
	 * @depends testFirePermitFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testFirePermitSetArchived($localobject) : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->setArchived($user);
		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$result = $localobject->getLibStatut(0);
		$this->assertSame($result, $langs->trans('Archived'));

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
	}

	/**
	 * testFirePermitUpdate
	 *
	 * @param   FirePermit $localobject Fire permit object
	 * @return  FirePermit $localobject Fire permit object
	 *
	 * @covers  FirePermit::update
	 *
	 * @depends testFirePermitFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testFirePermitUpdate($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$now                               = dol_now();
		//$localobject->ref                = $refFirePermitMod->getNextValue($localobject);
		$localobject->ref                  = "UpdatedTestRefFirePermit";
		$localobject->ref_ext              = "UpdatedTestRefExtFirePermit";
		$localobject->entity               = 1;
		$localobject->date_creation        = $now;
		$localobject->tms                  = $now;
		$localobject->status               = 1;
		$localobject->label                = "UpdatedTestFirePermit";
		$localobject->fk_project           = $conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_PROJECT;
		$localobject->date_start           = $now;
		$localobject->date_end             = $now;
		$localobject->last_email_sent_date = $now;
		$localobject->fk_user_creat        = $user->id ? $user->id : 1;
		$localobject->fk_user_modif        = $user->id ? $user->id : 1;

		$result = $localobject->update($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		$newobject = new FirePermit($this->savdb);
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
		$this->assertSame($localobject->label, $newobject->label);
		$this->assertEquals($localobject->fk_project, $newobject->fk_project);
		$this->assertSame($localobject->date_start, $newobject->date_start);
		$this->assertSame($localobject->date_end, $newobject->date_end);
		$this->assertSame($localobject->last_email_sent_date, $newobject->last_email_sent_date);
		$this->assertEquals($localobject->fk_user_creat, $newobject->fk_user_creat);
		$this->assertEquals($localobject->fk_user_modif, $newobject->fk_user_modif);

		return $localobject;
	}

//	/**
//	 * testFirePermitCreateFromClone
//	 *
//	 * @param  FirePermit $localobject Fire permit object
//	 * @return void
//	 *
//	 * @covers FirePermit::createFormClone
//	 *
//	 * @depends testFirePermitFetch
//	 * The depends says test is run only if previous is ok
//	 */
//	public function testFirePermitCreateFromClone($localobject) : void
//	{
//		global $conf, $user, $langs, $db;
//		$conf = $this->savconf;
//		$user = $this->savuser;
//		$langs = $this->savlangs;
//		$db = $this->savdb;
//
//		$result = $localobject->createFromClone($user, $localobject->id, '');
//		$this->assertLessThan($result, 0);
//
//		print __METHOD__." result=".$result."\n";
//	}

	/**
	 * testFirePermitLineInsert
	 *
	 * @param   FirePermit $localobject Fire permit object
	 * @return  int
	 *
	 * @covers  FirePermitLine::insert
	 *
	 * @depends testFirePermitUpdate
	 * The depends says test is run only if previous is ok
	 *
	 * @throws Exception
	 */
	public function testFirePermitLineInsert($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobjectline = new FirePermitLine($this->savdb);

		$now                                 = dol_now();
		$localobjectline->id                 = 0;
		//$localobjectline->ref              = $refFirePermitMod->getNextValue($localobjectline);
		$localobjectline->ref                = "TestRefFirePermitLine";
		$localobjectline->ref_ext            = "TestRefExtFirePermitLine";
		$localobjectline->entity             = 1;
		$localobjectline->date_creation      = $localobjectline->db->idate($now);
		$localobjectline->category           = 1;
		$localobjectline->description        = "TestFirePermitLine";
		$localobjectline->use_equipment      = "TestFirePermitLineUseEquipment";
		$localobjectline->fk_element         = 1;
		$localobjectline->fk_firepermit      = $localobject->id;

		$result = $localobjectline->insert($user);

		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$localobjectline->id." result=".$result."\n";
		return $result;
	}

	/**
	 * testFirePermitLineFetch
	 *
	 * @param   int                $id              Id fire permit line
	 * @return  FirePermitLine $localobjectline Fire permit object
	 *
	 * @covers  FirePermitLine::fetch
	 *
	 * @depends testFirePermitLineInsert
	 * The depends says test is run only if previous is ok
	 */
	public function testFirePermitLineFetch($id)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobjectline = new FirePermitLine($this->savdb);

		$result = $localobjectline->fetch($id);

		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$id." result=".$result."\n";
		return $localobjectline;
	}

	/**
	 * testFirePermitLineUpdate
	 *
	 * @param   FirePermitLine $localobjectline Fire permit line object
	 * @return  FirePermitLine $localobjectline Fire permit line object
	 *
	 * @covers  FirePermitLine::update
	 *
	 * @depends testFirePermitLineFetch
	 * The depends says test is run only if previous is ok
	 *
	 * @throws Exception
	 */
	public function testFirePermitLineUpdate($localobjectline)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$now                            = dol_now();
		//$localobject->ref             = $refFirePermitMod->getNextValue($localobject);
		$localobjectline->ref           = "UpdatedTestRefFirePermitLine";
		$localobjectline->category      = 1;
		$localobjectline->description   = "UpdatedTestFirePermitLine";
		$localobjectline->use_equipment = "UpdatedTestFirePermitLineUseEquipmentLine";
		$localobjectline->fk_element    = 1;

		$result = $localobjectline->update($user);
		print __METHOD__." id=".$localobjectline->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		$newobjectline = new FirePermitLine($this->savdb);
		$result = $newobjectline->fetch($localobjectline->id);
		print __METHOD__." id=".$localobjectline->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		$this->assertEquals($localobjectline->id, $newobjectline->id);
		$this->assertSame($localobjectline->ref, $newobjectline->ref);
		$this->assertEquals($localobjectline->category, $newobjectline->category);
		$this->assertSame($localobjectline->description, $newobjectline->description);
		$this->assertSame($localobjectline->use_equipment, $newobjectline->use_equipment);
		$this->assertEquals($localobjectline->fk_element, $newobjectline->fk_element);

		return $localobjectline;
	}

	/**
	 * testFirePermitFetchLines
	 *
	 * @param   FirePermit $localobject Fire permit object
	 * @return  void
	 *
	 * @covers  FirePermit::fetchLines
	 *
	 * @depends testFirePermitFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testFirePermitFetchLines($localobject) : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->fetchLines();

		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
	}

	/**
	 * testFirePermitLineFetchAll
	 *
	 * @return void
	 *
	 * @covers FirePermitLine::fetchAll
	 *
	 * @throws Exception
	 */
	public function testFirePermitLineFetchAll() : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobjectline = new FirePermitLine($this->savdb);
		$localobjectlineList = $localobjectline->fetchAll();

		$this->assertSame(true, is_array($localobjectlineList));
		$this->assertInstanceOf(get_class($localobjectline), array_shift($localobjectlineList));
		print __METHOD__ . " ok";
		print "\n";
	}

	/**
	 * testFirePermitFetchAll
	 *
	 * @return void
	 *
	 * @covers FirePermit::fetchAll
	 *
	 * @throws Exception
	 */
	public function testFirePermitFetchAll() : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new FirePermit($this->savdb);
		$localobjectList = $localobject->fetchAll();

		$this->assertSame(true, is_array($localobjectList));
		$this->assertInstanceOf(get_class($localobject), array_shift($localobjectList));
		print __METHOD__ . " ok";
		print "\n";
	}

	/**
	 * testFirePermitLineDelete
	 *
	 * @param   FirePermitLine $localobjectline Fire permit line object
	 * @return  int
	 *
	 * @covers  FirePermitLine::delete
	 *
	 * @depends testFirePermitLineUpdate
	 * The depends says test is run only if previous is ok
	 *
	 * @throws Exception
	 */
	public function testFirePermitLineDelete($localobjectline)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$newobjectline = new FirePermitLine($this->savdb);
		$newobjectline->fetch($localobjectline->id);

		$result = $localobjectline->delete($user);
		print __METHOD__." id=".$newobjectline->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $result;
	}

	/**
	 * testFirePermitDelete
	 *
	 * @param   FirePermit $localobject Fire permit object
	 * @return  int
	 *
	 * @covers  FirePermit::delete
	 *
	 * @depends testFirePermitUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testFirePermitDelete($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$newobject = new FirePermit($this->savdb);
		$newobject->fetch($localobject->id);

		$result = $localobject->delete($user);
		print __METHOD__." id=".$newobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $result;
	}
}


