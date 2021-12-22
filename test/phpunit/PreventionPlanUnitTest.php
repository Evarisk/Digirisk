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
 *      \file       test/phpunit/PreventionPlanUnitTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf, $user, $langs, $db;

require_once __DIR__ . '/../../../../../htdocs/master.inc.php';
require_once __DIR__ . '/../../class/preventionplan.class.php';

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
class PreventionPlanUnitTest extends PHPUnit\Framework\TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return PreventionPlanUnitTest
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
	 * testPreventionPlanCreate
	 *
	 * @covers PreventionPlan::create
	 *
	 * @return int
	 */
	public function testPreventionPlanCreate()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new PreventionPlan($this->savdb);

		$now                               = dol_now();
		$localobject->id                   = 0;
		//$localobject->ref                = $refPreventionPlanMod->getNextValue($localobject);
		$localobject->ref                  = "TestRefPreventionPlan";
		$localobject->ref_ext              = "TestRefExtPreventionPlan";
		$localobject->entity               = 1;
		$localobject->date_creation        = $localobject->db->idate($now);
		$localobject->tms                  = $now;
		$localobject->status               = 1;
		$localobject->label                = "TestPreventionPlan";
		$localobject->fk_project           = $conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_PROJECT;
		$localobject->date_start           = $localobject->db->idate($now);
		$localobject->date_end             = $localobject->db->idate($now);
		$localobject->prior_visit_bool     = 1;
		$localobject->prior_visit_text     = "TestPriorVisitTest";
		$localobject->prior_visit_date     = $localobject->db->idate($now);
		$localobject->last_email_sent_date = $localobject->db->idate($now);
		$localobject->cssct_intervention   = 1;
		$localobject->fk_user_creat        = $user->id ? $user->id : 1;
		$localobject->fk_user_modif        = $user->id ? $user->id : 1;

		$result = $localobject->create($user);

		$this->assertLessThan($result, 0);

		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testPreventionPlanFetch
	 *
	 * @param   int            $id          Id prevention plan
	 * @return  PreventionPlan $localobject Prevention plan object
	 *
	 * @covers  PreventionPlan::fetch
	 *
	 * @depends testPreventionPlanCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testPreventionPlanFetch($id)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new PreventionPlan($this->savdb);

		$result = $localobject->fetch($id);

		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testPreventionPlanInfo
	 *
	 * @param   PreventionPlan $localobject Prevention plan object
	 * @return  void
	 *
	 * @covers  PreventionPlan::info
	 *
	 * @depends testPreventionPlanFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testPreventionPlanInfo($localobject) : void
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
	 * testPreventionPlanSetInProgress
	 *
	 * @param   PreventionPlan $localobject Prevention plan object
	 * @return  void
	 *
	 * @covers  PreventionPlan::setInProgress
	 * @covers  PreventionPlan::getLibStatut
	 * @covers  PreventionPlan::LibStatut
	 *
	 * @depends testPreventionPlanFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testPreventionPlanSetInProgress($localobject) : void
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
	 * testPreventionPlanSetPendingSignature
	 *
	 * @param   PreventionPlan $localobject Prevention plan object
	 * @return  void
	 *
	 * @covers  PreventionPlan::setPendingSignature
	 * @covers  PreventionPlan::getLibStatut
	 * @covers  PreventionPlan::LibStatut
	 *
	 * @depends testPreventionPlanFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testPreventionPlanSetPendingSignature($localobject) : void
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
	 * testPreventionPlanSetLocked
	 *
	 * @param   PreventionPlan $localobject Prevention plan object
	 * @return  void
	 *
	 * @covers  PreventionPlan::setLocked
	 * @covers  PreventionPlan::getLibStatut
	 * @covers  PreventionPlan::LibStatut
	 *
	 * @depends testPreventionPlanFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testPreventionPlanSetLocked($localobject) : void
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
	 * testPreventionPlanSetArchived
	 *
	 * @param   PreventionPlan $localobject Prevention plan object
	 * @return  void
	 *
	 * @covers  PreventionPlan::setArchived
	 * @covers  PreventionPlan::getLibStatut
	 * @covers  PreventionPlan::LibStatut
	 *
	 * @depends testPreventionPlanFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testPreventionPlanSetArchived($localobject) : void
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
	 * testPreventionPlanUpdate
	 *
	 * @param   PreventionPlan $localobject Prevention plan object
	 * @return  PreventionPlan $localobject Prevention plan object
	 *
	 * @covers  PreventionPlan::update
	 *
	 * @depends testPreventionPlanFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testPreventionPlanUpdate($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$now                               = dol_now();
		//$localobject->ref                = $refPreventionPlanMod->getNextValue($localobject);
		$localobject->ref                  = "UpdatedTestRefPreventionPlan";
		$localobject->ref_ext              = "UpdatedTestRefExtPreventionPlan";
		$localobject->entity               = 1;
		$localobject->date_creation        = $now;
		$localobject->tms                  = $now;
		$localobject->status               = 1;
		$localobject->label                = "UpdatedTestPreventionPlan";
		$localobject->fk_project           = $conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_PROJECT;
		$localobject->date_start           = $now;
		$localobject->date_end             = $now;
		$localobject->prior_visit_bool     = 1;
		$localobject->prior_visit_text     = "UpdatedTestPriorVisitTest";
		$localobject->prior_visit_date     = $now;
		$localobject->last_email_sent_date = $now;
		$localobject->cssct_intervention   = 1;
		$localobject->fk_user_creat        = $user->id ? $user->id : 1;
		$localobject->fk_user_modif        = $user->id ? $user->id : 1;

		$result = $localobject->update($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		$newobject = new PreventionPlan($this->savdb);
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
		$this->assertEquals($localobject->prior_visit_bool, $newobject->prior_visit_bool);
		$this->assertSame($localobject->prior_visit_text, $newobject->prior_visit_text);
		$this->assertSame($localobject->prior_visit_date, $newobject->prior_visit_date);
		$this->assertSame($localobject->last_email_sent_date, $newobject->last_email_sent_date);
		$this->assertEquals($localobject->cssct_intervention, $newobject->cssct_intervention);
		$this->assertEquals($localobject->fk_user_creat, $newobject->fk_user_creat);
		$this->assertEquals($localobject->fk_user_modif, $newobject->fk_user_modif);

		return $localobject;
	}

//	/**
//	 * testPreventionPlanCreateFromClone
//	 *
//	 * @param  PreventionPlan $localobject Prevention plan object
//	 * @return void
//	 *
//	 * @covers PreventionPlan::createFormClone
//	 *
//	 * @depends testPreventionPlanFetch
//	 * The depends says test is run only if previous is ok
//	 */
//	public function testPreventionPlanCreateFromClone($localobject) : void
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
	 * testPreventionPlanLineInsert
	 *
	 * @param   PreventionPlan $localobject Prevention plan object
	 * @return  int
	 *
	 * @covers  PreventionPlanLine::insert
	 *
	 * @depends testPreventionPlanUpdate
	 * The depends says test is run only if previous is ok
	 *
	 * @throws Exception
	 */
	public function testPreventionPlanLineInsert($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobjectline = new PreventionPlanLine($this->savdb);

		$now                                 = dol_now();
		$localobjectline->id                 = 0;
		//$localobjectline->ref              = $refPreventionPlanMod->getNextValue($localobjectline);
		$localobjectline->ref                = "TestRefPreventionPlanLine";
		$localobjectline->ref_ext            = "TestRefExtPreventionPlanLine";
		$localobjectline->entity             = 1;
		$localobjectline->date_creation      = $localobjectline->db->idate($now);
		$localobjectline->category           = 1;
		$localobjectline->description        = "TestPreventionPlan";
		$localobjectline->prevention_method  = "TestPreventionPlanMethod";
		$localobjectline->fk_element         = 1;
		$localobjectline->fk_preventionplan  = $localobject->id;

		$result = $localobjectline->insert($user);

		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$localobjectline->id." result=".$result."\n";
		return $result;
	}

	/**
	 * testPreventionPlanLineFetch
	 *
	 * @param   int                $id              Id prevention plan line
	 * @return  PreventionPlanLine $localobjectline Prevention plan object
	 *
	 * @covers  PreventionPlanLine::fetch
	 *
	 * @depends testPreventionPlanLineInsert
	 * The depends says test is run only if previous is ok
	 */
	public function testPreventionPlanLineFetch($id)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobjectline = new PreventionPlanLine($this->savdb);

		$result = $localobjectline->fetch($id);

		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$id." result=".$result."\n";
		return $localobjectline;
	}

	/**
	 * testPreventionPlanLineUpdate
	 *
	 * @param   PreventionPlanLine $localobjectline Prevention plan line object
	 * @return  PreventionPlanLine $localobjectline Prevention plan line object
	 *
	 * @covers  PreventionPlanLine::update
	 *
	 * @depends testPreventionPlanLineFetch
	 * The depends says test is run only if previous is ok
	 *
	 * @throws Exception
	 */
	public function testPreventionPlanLineUpdate($localobjectline)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$now                                = dol_now();
		//$localobject->ref                 = $refPreventionPlanMod->getNextValue($localobject);
		$localobjectline->ref               = "UpdatedTestRefPreventionPlanLine";
		$localobjectline->category          = 1;
		$localobjectline->description       = "UpdatedTestPreventionPlanLine";
		$localobjectline->prevention_method = "UpdatedTestPreventionPlanMethodLine";
		$localobjectline->fk_element        = 1;

		$result = $localobjectline->update($user);
		print __METHOD__." id=".$localobjectline->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		$newobjectline = new PreventionPlanLine($this->savdb);
		$result = $newobjectline->fetch($localobjectline->id);
		print __METHOD__." id=".$localobjectline->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		$this->assertEquals($localobjectline->id, $newobjectline->id);
		$this->assertSame($localobjectline->ref, $newobjectline->ref);
		$this->assertEquals($localobjectline->category, $newobjectline->category);
		$this->assertSame($localobjectline->description, $newobjectline->description);
		$this->assertSame($localobjectline->prevention_method, $newobjectline->prevention_method);
		$this->assertEquals($localobjectline->fk_element, $newobjectline->fk_element);

		return $localobjectline;
	}

	/**
	 * testPreventionPlanFetchLines
	 *
	 * @param   PreventionPlan $localobject Prevention plan object
	 * @return  void
	 *
	 * @covers  PreventionPlan::fetchLines
	 *
	 * @depends testPreventionPlanFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testPreventionPlanFetchLines($localobject) : void
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
	 * testPreventionPlanLineFetchAll
	 *
	 * @return void
	 *
	 * @covers PreventionPlanLine::fetchAll
	 *
	 * @throws Exception
	 */
	public function testPreventionPlanLineFetchAll() : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobjectline = new PreventionPlanLine($this->savdb);
		$localobjectlineList = $localobjectline->fetchAll();

		$this->assertSame(true, is_array($localobjectlineList));
		$this->assertInstanceOf(get_class($localobjectline), array_shift($localobjectlineList));
		print __METHOD__ . " ok";
		print "\n";
	}

	/**
	 * testPreventionPlanFetchAll
	 *
	 * @return void
	 *
	 * @covers PreventionPlan::fetchAll
	 *
	 * @throws Exception
	 */
	public function testPreventionPlanFetchAll() : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new PreventionPlan($this->savdb);
		$localobjectList = $localobject->fetchAll();

		$this->assertSame(true, is_array($localobjectList));
		$this->assertInstanceOf(get_class($localobject), array_shift($localobjectList));
		print __METHOD__ . " ok";
		print "\n";
	}

	/**
	 * testPreventionPlanLineDelete
	 *
	 * @param   PreventionPlanLine $localobjectline Prevention plan line object
	 * @return  int
	 *
	 * @covers  PreventionPlanLine::delete
	 *
	 * @depends testPreventionPlanLineUpdate
	 * The depends says test is run only if previous is ok
	 *
	 * @throws Exception
	 */
	public function testPreventionPlanLineDelete($localobjectline)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$newobjectline = new PreventionPlanLine($this->savdb);
		$newobjectline->fetch($localobjectline->id);

		$result = $localobjectline->delete($user);
		print __METHOD__." id=".$newobjectline->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $result;
	}

	/**
	 * testPreventionPlanDelete
	 *
	 * @param   PreventionPlan $localobject Prevention plan object
	 * @return  int
	 *
	 * @covers  PreventionPlan::delete
	 *
	 * @depends testPreventionPlanUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testPreventionPlanDelete($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$newobject = new PreventionPlan($this->savdb);
		$newobject->fetch($localobject->id);

		$result = $localobject->delete($user);
		print __METHOD__." id=".$newobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $result;
	}
}

