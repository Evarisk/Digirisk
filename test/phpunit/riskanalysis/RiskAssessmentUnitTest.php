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
 *      \file       test/phpunit/RiskAssessmentUnitTest.php
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
class RiskAssessmentUnitTest extends PHPUnit\Framework\TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return RiskAssessmentUnitTest
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
	 * testRiskAssessmentCreate
	 *
	 * @covers RiskAssessment::create
	 *
	 * @return int
	 */
	public function testRiskAssessmentCreate()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new RiskAssessment($this->savdb);

		$now                              = dol_now();
		$localobject->id                  = 0;
		//$localobject->ref               = $refRiskAssessmentMod->getNextValue($localobject);
		$localobject->ref                 = "TestRefRiskAssessment";
		$localobject->ref_ext             = "TestRefExtRiskAssessment";
		$localobject->entity              = 1;
		$localobject->date_creation       = $localobject->db->idate($now);
		$localobject->tms                 = $now;
		$localobject->import_key          = 1;
		$localobject->status              = 1;
		$localobject->method              = "advanced";
		$localobject->cotation            = 42;
		$localobject->gravite             = 1;
		$localobject->protection          = 1;
		$localobject->occurrence          = 1;
		$localobject->formation           = 1;
		$localobject->exposition          = 1;
		$localobject->date_riskassessment = $localobject->db->idate($now);
		$localobject->comment             = "TestCommentRiskAssessment";
		$localobject->photo               = "test.png";
		$localobject->has_tasks           = 1;
		$localobject->fk_user_creat       = $user->id ?: 1;
		$localobject->fk_user_modif       = $user->id ?: 1;
		$localobject->fk_risk             = 1;

		$result = $localobject->create($user);

		$this->assertLessThan($result, 0);

		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testRiskAssessmentFetch
	 *
	 * @param   int  $id          Id risk
	 * @return  RiskAssessment $localobject RiskAssessment object
	 *
	 * @covers  RiskAssessment::fetch
	 *
	 * @depends testRiskAssessmentCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testRiskAssessmentFetch($id)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new RiskAssessment($this->savdb);

		$result = $localobject->fetch($id);

		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testRiskAssessmentFetchFormParent
	 *
	 * @param   RiskAssessment $localobject RiskAssessment object
	 * @return  void
	 *
	 * @covers  RiskAssessment::fetchFromParent
	 *
	 * @depends testRiskAssessmentFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testRiskAssessmentFetchFromParent($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->fetchFromParent($localobject->fk_risk);

		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
	}

	/**
	 * testRiskAssessmentUpdate
	 *
	 * @param   RiskAssessment $localobject RiskAssessment object
	 * @return  RiskAssessment $localobject RiskAssessment object
	 *
	 * @covers  RiskAssessment::update
	 *
	 * @depends testRiskAssessmentFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testRiskAssessmentUpdate($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$now                              = dol_now();
		//$localobject->ref               = $refRiskAssessmentMod->getNextValue($localobject);
		$localobject->ref                 = "TestRefRiskAssessment";
		$localobject->ref_ext             = "TestRefExtRiskAssessment";
		$localobject->entity              = 1;
		$localobject->date_creation       = $now;
		$localobject->tms                 = $now;
		$localobject->import_key          = 1;
		$localobject->status              = 1;
		$localobject->method              = "advanced";
		$localobject->cotation            = 42;
		$localobject->gravite             = 1;
		$localobject->protection          = 1;
		$localobject->occurrence          = 1;
		$localobject->formation           = 1;
		$localobject->exposition          = 1;
		$localobject->date_riskassessment = $now;
		$localobject->comment             = "TestCommentRiskAssessment";
		$localobject->photo               = "test.png";
		$localobject->has_tasks           = 1;
		$localobject->fk_user_creat       = $user->id ?: 1;
		$localobject->fk_user_modif       = $user->id ?: 1;
		$localobject->fk_risk             = 1;

		$result = $localobject->update($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		$newobject = new RiskAssessment($this->savdb);
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
		$this->assertSame($localobject->method, $newobject->method);
		$this->assertSame($localobject->cotation, $newobject->cotation);
		$this->assertSame($localobject->gravite, $newobject->gravite);
		$this->assertSame($localobject->protection, $newobject->protection);
		$this->assertSame($localobject->occurrence, $newobject->occurrence);
		$this->assertSame($localobject->formation, $newobject->formation);
		$this->assertSame($localobject->exposition, $newobject->exposition);
		$this->assertSame($localobject->date_riskassessment, $newobject->date_riskassessment);
		$this->assertSame($localobject->comment, $newobject->comment);
		$this->assertSame($localobject->photo, $newobject->photo);
		$this->assertSame($localobject->has_tasks, $newobject->has_tasks);
		$this->assertEquals($localobject->fk_user_creat, $newobject->fk_user_creat);
		$this->assertEquals($localobject->fk_user_modif, $newobject->fk_user_modif);
		$this->assertSame($localobject->fk_risk, $newobject->fk_risk);

		return $localobject;
	}

	/**
	 * testRiskAssessmentUpdateEvaluationStatus
	 *
	 * @return void
	 *
	 * @covers RiskAssessment::updateEvaluationStatus
	 *
	 * @depends testRiskAssessmentUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testRiskAssessmentUpdateEvaluationStatus($localobject) : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->updateEvaluationStatus($user, $localobject->fk_risk);

		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
	}

	/**
	 * testRiskAssessmentGetEvaluationScale
	 *
	 * @param   RiskAssessment $localobject RiskAssessment object
	 * @return void
	 *
	 * @covers RiskAssessment::get_evaluation_scale
	 *
	 * @depends testRiskAssessmentFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testRiskAssessmentGetEvaluationScale($localobject) : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->get_evaluation_scale();

		$this->assertSame(true, is_int($result));

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
	}

	/**
	 * testRiskAssessmentFetchAll
	 *
	 * @return void
	 *
	 * @covers RiskAssessment::fetchAll
	 *
	 * @throws Exception
	 */
	public function testRiskAssessmentFetchAll() : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new RiskAssessment($this->savdb);
		$localobjectList = $localobject->fetchAll();

		$this->assertSame(true, is_array($localobjectList));
		$this->assertInstanceOf(get_class($localobject), array_shift($localobjectList));
		print __METHOD__ . " ok";
		print "\n";
	}

	/**
	 * testRiskAssessmentDelete
	 *
	 * @param   RiskAssessment $localobject RiskAssessment object
	 * @return  int
	 *
	 * @covers  RiskAssessment::delete
	 *
	 * @depends testRiskAssessmentUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testRiskAssessmentDelete($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$newobject = new RiskAssessment($this->savdb);
		$newobject->fetch($localobject->id);

		$result = $localobject->delete($user);
		print __METHOD__." id=".$newobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $result;
	}
}

