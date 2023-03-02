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
 *      \file       test/phpunit/AccidentUnitTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf, $user, $langs, $db;

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

require_once __DIR__ . '/../../class/accident.class.php';

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
class AccidentUnitTest extends PHPUnit\Framework\TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return AccidentUnitTest
	 */
	public function __construct()
	{
		parent::__construct();

		//$this->sharedFixture
		global $conf, $user, $langs, $db;
		$this->savconf  = $conf;
		$this->savuser  = $user;
		$this->savlangs = $langs;
		$this->savdb    = $db;

		print __METHOD__ . " db->type=" . $db->type . " user->id=" . $user->id;
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
			print __METHOD__ . " module digiriskdolibarr must be enabled.\n"; die(1);
		}

		$db->begin(); // This is to have all actions inside a transaction even if test launched without suite.

		print __METHOD__ . "\n";
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

		print __METHOD__ . "\n";
	}

	/**
	 * Init phpunit tests
	 *
	 * @return  void
	 */
	protected function setUp() : void
	{
		global $conf, $user, $langs, $db;
		$conf  = $this->savconf;
		$user  = $this->savuser;
		$langs = $this->savlangs;
		$db    = $this->savdb;

		print __METHOD__ . "\n";
	}

	/**
	 * End phpunit tests
	 *
	 * @return  void
	 */
	protected function tearDown() : void
	{
		print __METHOD__ . "\n";
	}

	/**
	 * testAccidentCreate
	 *
	 * @covers Accident::create
	 *
	 * @return int
	 */
	public function testAccidentCreate()
	{
		global $conf, $user, $langs, $db;
		$conf  = $this->savconf;
		$user  = $this->savuser;
		$langs = $this->savlangs;
		$db    = $this->savdb;

		$localobject = new Accident($this->savdb);

		$now             = dol_now();
		$localobject->id = 0;
		//$localobject->ref             = $refAccidentMod->getNextValue($localobject);
		$localobject->ref               = "TestRefAccident";
		$localobject->ref_ext           = "TestRefExtAccident";
		$localobject->entity            = 1;
		$localobject->date_creation     = $localobject->db->idate($now);
		$localobject->tms               = $now;
		$localobject->status            = 1;
		$localobject->label             = "TestLabelAccident";
		$localobject->fk_user_victim    = 1;
		$localobject->fk_user_employer  = 1;
		$localobject->accident_type     = 1;
		$localobject->fk_element        = 1;
		$localobject->fk_soc            = 1;
		$localobject->accident_date     = $localobject->db->idate($now);
		$localobject->description       = "TestDescriptionAccident";
		$localobject->photo             = "Test.png";
		$localobject->external_accident = 1;
		$localobject->fk_project        = $conf->global->DIGIRISKDOLIBARR_ACCIDENT_PROJECT;
		$localobject->fk_user_creat     = $user->id ? $user->id : 1;
		$localobject->fk_user_modif     = $user->id ? $user->id : 1;

		$result = $localobject->create($user);

		$this->assertLessThan($result, 0);

		print __METHOD__ . " result=" . $result . "\n";
		return $result;
	}

	/**
	 * testAccidentFetch
	 *
	 * @param   int       $id          Id accident
	 * @return  Accident $localobject Accident object
	 *
	 * @covers  Accident::fetch
	 *
	 * @depends testAccidentCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testAccidentFetch($id)
	{
		global $conf, $user, $langs, $db;
		$conf  = $this->savconf;
		$user  = $this->savuser;
		$langs = $this->savlangs;
		$db    = $this->savdb;

		$localobject = new Accident($this->savdb);

		$result = $localobject->fetch($id);

		$this->assertLessThan($result, 0);

		print __METHOD__ . " id=" . $id . " result=" . $result . "\n";
		return $localobject;
	}

//	/**
//	 * testAccidentInfo
//	 *
//	 * @param   Accident $localobject Accident object
//	 * @return  void
//	 *
//	 * @covers  Accident::info
//	 *
//	 * @depends testAccidentFetch
//	 * The depends says test is run only if previous is ok
//	 */
//	public function testAccidentInfo($localobject) : void
//	{
//		global $conf, $user, $langs, $db;
//		$conf  = $this->savconf;
//		$user  = $this->savuser;
//		$langs = $this->savlangs;
//		$db    = $this->savdb;
//
//		$result = $localobject->info($localobject->id);
//		$this->assertNull($result);
//
//		print __METHOD__ . " id=" . $localobject->id . "\n";
//	}

	/**
	 * testAccidentUpdate
	 *
	 * @param   Accident $localobject Accident object
	 * @return  Accident $localobject Accident object
	 *
	 * @covers  Accident::update
	 *
	 * @depends testAccidentFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testAccidentUpdate($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf  = $this->savconf;
		$user  = $this->savuser;
		$langs = $this->savlangs;
		$db    = $this->savdb;

		$now = dol_now();
		//$localobject->ref             = $refAccidentMod->getNextValue($localobject);
		$localobject->ref               = "UpdatedTestRefAccident";
		$localobject->ref_ext           = "UpdatedTestRefExtAccident";
		//$localobject->entity            = 1;
		$localobject->date_creation     = $now;
		$localobject->tms               = $now;
		$localobject->status            = 1;
		$localobject->label             = "UpdatedTestLabelAccident";
		$localobject->fk_user_victim    = 1;
		$localobject->fk_user_employer  = 1;
		$localobject->accident_type     = 1;
		$localobject->fk_element        = 1;
		$localobject->fk_soc            = 1;
		$localobject->accident_date     = $now;
		$localobject->description       = "UpdatedTestDescriptionAccident";
		$localobject->photo             = "Test.png";
		$localobject->external_accident = 1;
		$localobject->fk_project        = $conf->global->DIGIRISKDOLIBARR_ACCIDENT_PROJECT;
		$localobject->fk_user_creat     = $user->id ? $user->id : 1;
		$localobject->fk_user_modif     = $user->id ? $user->id : 1;

		$result = $localobject->update($user);
		print __METHOD__ . " id=" . $localobject->id . " result=" . $result . "\n";
		$this->assertLessThan($result, 0);

		$newobject = new Accident($this->savdb);
		$result    = $newobject->fetch($localobject->id);
		print __METHOD__ . " id=" . $localobject->id . " result=" . $result . "\n";
		$this->assertLessThan($result, 0);

		$this->assertEquals($localobject->id, $newobject->id);
		$this->assertSame($localobject->ref, $newobject->ref);
		$this->assertSame($localobject->ref_ext, $newobject->ref_ext);
		//$this->assertSame($localobject->entity, $newobject->entity);
		$this->assertSame($localobject->date_creation, $newobject->date_creation);
		$this->assertSame($localobject->tms, $newobject->tms);
		$this->assertEquals($localobject->status, $newobject->status);
		$this->assertSame($localobject->label, $newobject->label);
		//$this->assertEquals($localobject->fk_user_victim, $newobject->fk_user_victim);
		//$this->assertSame($localobject->fk_user_employer, $newobject->fk_user_employer);
		$this->assertEquals($localobject->accident_type, $newobject->accident_type);
		$this->assertEquals($localobject->fk_element, $newobject->fk_element);
		$this->assertEquals($localobject->fk_soc, $newobject->fk_soc);
		$this->assertSame($localobject->accident_date, $newobject->accident_date);
		$this->assertSame($localobject->description, $newobject->description);
		$this->assertSame($localobject->photo, $newobject->photo);
		$this->assertEquals($localobject->external_accident, $newobject->external_accident);
		$this->assertEquals($localobject->fk_project, $newobject->fk_project);
		$this->assertEquals($localobject->fk_user_creat, $newobject->fk_user_creat);
		$this->assertEquals($localobject->fk_user_modif, $newobject->fk_user_modif);

		return $localobject;
	}

	/**
	 * testAccidentWorkStopInsert
	 *
	 * @param   Accident $localobject Accident object
	 * @return  int
	 *
	 * @covers  AccidentWorkStop::insert
	 *
	 * @depends testAccidentUpdate
	 * The depends says test is run only if previous is ok
	 *
	 * @throws Exception
	 */
	public function testAccidentWorkStopInsert($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf  = $this->savconf;
		$user  = $this->savuser;
		$langs = $this->savlangs;
		$db    = $this->savdb;

		$localobjectline = new AccidentWorkStop($this->savdb);

		$now                 = dol_now();
		$localobjectline->id = 0;
		//$localobjectline->ref               = $refAccidentPlanMod->getNextValue($localobjectline);
		$localobjectline->ref                 = "TestRefAccidentWorkStop";
		$localobjectline->entity              = 1;
		$localobjectline->date_creation       = $localobjectline->db->idate($now);
		$localobjectline->date_start_workstop = $localobjectline->db->idate($now);
		$localobjectline->date_end_workstop   = $localobjectline->db->idate($now);
		$localobjectline->tms                 = $now;
		$localobjectline->status              = 1;
		$localobjectline->workstop_days       = 10;
		$localobjectline->fk_accident         = $localobject->id;

		$result = $localobjectline->insert($user);

		$this->assertLessThan($result, 0);

		print __METHOD__ . " id=" . $localobjectline->id . " result=" . $result . "\n";
		return $result;
	}

	/**
	 * testAccidentWorkStopFetch
	 *
	 * @param   int              $id              Id acciddent workstop
	 * @return  AccidentWorkStop $localobjectline Accident workstop object
	 *
	 * @covers  AccidentWorkStop::fetch
	 *
	 * @depends testAccidentWorkStopInsert
	 * The depends says test is run only if previous is ok
	 */
	public function testAccidentWorkStopFetch($id)
	{
		global $conf, $user, $langs, $db;
		$conf  = $this->savconf;
		$user  = $this->savuser;
		$langs = $this->savlangs;
		$db    = $this->savdb;

		$localobjectline = new AccidentWorkStop($this->savdb);

		$result = $localobjectline->fetch($id);

		$this->assertLessThan($result, 0);

		print __METHOD__ . " id=" . $id . " result=" . $result . "\n";
		return $localobjectline;
	}

	/**
	 * testAccidentWorkStopUpdate
	 *
	 * @param   AccidentWorkStop $localobjectline Accident workstop object
	 * @return  AccidentWorkStop $localobjectline Accident workstop object
	 *
	 * @covers  AccidentWorkStop::update
	 *
	 * @depends testAccidentWorkStopFetch
	 * The depends says test is run only if previous is ok
	 *
	 * @throws Exception
	 */
	public function testAccidentWorkStopUpdate($localobjectline)
	{
		global $conf, $user, $langs, $db;
		$conf  = $this->savconf;
		$user  = $this->savuser;
		$langs = $this->savlangs;
		$db    = $this->savdb;

		$now = dol_now();
		//$localobjectline->ref         = $refAccidentPlanMod->getNextValue($localobjectline);
		$localobjectline->ref           = "UpdatedTestRefAccidentWorkStop";
		$localobjectline->entity        = 1;
		$localobjectline->date_creation = $now;
		$localobjectline->tms           = $now;
		$localobjectline->status        = 1;
		$localobjectline->workstop_days = 10;

		$result = $localobjectline->update($user);
		print __METHOD__ . " id=" . $localobjectline->id . " result=" . $result . "\n";
		$this->assertLessThan($result, 0);

		$newobjectline = new AccidentWorkStop($this->savdb);
		$result        = $newobjectline->fetch($localobjectline->id);
		print __METHOD__ . " id=" . $localobjectline->id . " result=" . $result . "\n";
		$this->assertLessThan($result, 0);

		$this->assertEquals($localobjectline->id, $newobjectline->id);
		$this->assertSame($localobjectline->ref, $newobjectline->ref);
		//$this->assertEquals($localobjectline->entity, $newobjectline->entity);
		//$this->assertEquals($localobjectline->date_creation, $newobjectline->date_creation);
		//$this->assertEquals($localobjectline->tms, $newobjectline->tms);
		$this->assertEquals($localobjectline->status, $newobjectline->status);
		$this->assertEquals($localobjectline->workstop_days, $newobjectline->workstop_days);

		return $localobjectline;
	}

	/**
	 * testAccidentLesionInsert
	 *
	 * @param   Accident $localobject Accident object
	 * @return  int
	 *
	 * @covers  AccidentLesion::insert
	 *
	 * @depends testAccidentUpdate
	 * The depends says test is run only if previous is ok
	 *
	 * @throws Exception
	 */
	public function testAccidentLesionInsert($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf  = $this->savconf;
		$user  = $this->savuser;
		$langs = $this->savlangs;
		$db    = $this->savdb;

		$localobjectline = new AccidentLesion($this->savdb);

		$now                 = dol_now();
		$localobjectline->id = 0;
		//$localobjectline->ref               = $refAccidentPlanMod->getNextValue($localobjectline);
		$localobjectline->ref                 = "TestRefAccidentLesion";
		$localobjectline->entity              = 1;
		$localobjectline->date_creation       = $localobjectline->db->idate($now);
		$localobjectline->tms                 = $now;
		$localobjectline->lesion_localization = 1;
		$localobjectline->lesion_nature       = 1;
		$localobjectline->fk_accident         = $localobject->id;

		$result = $localobjectline->insert($user);

		$this->assertLessThan($result, 0);

		print __METHOD__ . " id=" . $localobjectline->id . " result=" . $result . "\n";
		return $result;
	}

	/**
	 * testAccidentLesionFetch
	 *
	 * @param   int            $id              Id acciddent lesion
	 * @return  AccidentLesion $localobjectline Accident lesion object
	 *
	 * @covers  AccidentLesion::fetch
	 *
	 * @depends testAccidentLesionInsert
	 * The depends says test is run only if previous is ok
	 */
	public function testAccidentLesionFetch($id)
	{
		global $conf, $user, $langs, $db;
		$conf  = $this->savconf;
		$user  = $this->savuser;
		$langs = $this->savlangs;
		$db    = $this->savdb;

		$localobjectline = new AccidentLesion($this->savdb);

		$result = $localobjectline->fetch($id);

		$this->assertLessThan($result, 0);

		print __METHOD__ . " id=" . $id . " result=" . $result . "\n";
		return $localobjectline;
	}

	/**
	 * testAccidentLesionUpdate
	 *
	 * @param   AccidentLesion $localobjectline Accident lesion object
	 * @return  AccidentLesion $localobjectline Accident lesion object
	 *
	 * @covers  AccidentLesion::update
	 *
	 * @depends testAccidentLesionFetch
	 * The depends says test is run only if previous is ok
	 *
	 * @throws Exception
	 */
	public function testAccidentLesionUpdate($localobjectline)
	{
		global $conf, $user, $langs, $db;
		$conf  = $this->savconf;
		$user  = $this->savuser;
		$langs = $this->savlangs;
		$db    = $this->savdb;

		$now = dol_now();
		//$localobjectline->ref               = $refAccidentPlanMod->getNextValue($localobjectline);
		$localobjectline->ref                 = "UpdatedTestRefAccidentLesion";
		$localobjectline->entity              = 1;
		$localobjectline->date_creation       = $now;
		$localobjectline->tms                 = $now;
		$localobjectline->lesion_localization = 1;
		$localobjectline->lesion_nature       = 1;

		$result = $localobjectline->update($user);
		print __METHOD__ . " id=" . $localobjectline->id . " result=" . $result . "\n";
		$this->assertLessThan($result, 0);

		$newobjectline = new AccidentLesion($this->savdb);
		$result        = $newobjectline->fetch($localobjectline->id);
		print __METHOD__ . " id=" . $localobjectline->id . " result=" . $result . "\n";
		$this->assertLessThan($result, 0);

		$this->assertEquals($localobjectline->id, $newobjectline->id);
		$this->assertSame($localobjectline->ref, $newobjectline->ref);
		//$this->assertEquals($localobjectline->entity, $newobjectline->entity);
		//$this->assertEquals($localobjectline->date_creation, $newobjectline->date_creation);
		//$this->assertEquals($localobjectline->tms, $newobjectline->tms);
		$this->assertEquals($localobjectline->lesion_localization, $newobjectline->lesion_localization);
		$this->assertEquals($localobjectline->lesion_nature, $newobjectline->lesion_nature);

		return $localobjectline;
	}

	//  /**
	//   * testAccidentFetchLines
	//   *
	//   * @param   Accident $localobject Accident object
	//   * @return  void
	//   *
	//   * @covers  Accident::fetchLines
	//   *
	//   * @depends testAccidentFetch
	//   * The depends says test is run only if previous is ok
	//   */
	//  public function testAccidentFetchLines($localobject) : void
	//  {
	//      global $conf, $user, $langs, $db;
	//      $conf  = $this->savconf;
	//      $user  = $this->savuser;
	//      $langs = $this->savlangs;
	//      $db    = $this->savdb;
	//
	//      $result = $localobject->fetchLines();
	//
	//      $this->assertLessThan($result, 0);
	//
	//      print __METHOD__ . " id=" . $localobject->id . " result=" . $result . "\n";
	//  }

	/**
	 * testAccidentMetaDataCreate
	 *
	 * @param   Accident $localobject Accident object
	 * @return  int
	 *
	 * @covers  AccidentMetaData::create
	 *
	 * @depends testAccidentUpdate
	 * The depends says test is run only if previous is ok
	 *
	 * @throws Exception
	 */
	public function testAccidentMetaDataCreate($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf  = $this->savconf;
		$user  = $this->savuser;
		$langs = $this->savlangs;
		$db    = $this->savdb;

		$localobject = new AccidentMetaData($this->savdb);

		$now                                               = dol_now();
		$localobject->id                                   = 0;
		$localobject->entity                               = 1;
		$localobject->date_creation                        = $localobject->db->idate($now);
		$localobject->tms                                  = $now;
		$localobject->status                               = 1;
		$localobject->relative_location                    = "TestRelativeLocationAccidentMetaData";
		$localobject->victim_activity                      = "TestVictimActivityAccidentMetaData";
		$localobject->accident_nature                      = "TestAccidentNatureAccidentMetaData";
		$localobject->accident_object                      = "TestAccidentObjectAccidentMetaData";
		$localobject->accident_nature_doubt                = "TestAccidentNatureDoubtAccidentMetaData";
		$localobject->accident_nature_doubt_link           = "TestAccidentNatureDoubtLinkAccidentMetaData";
		$localobject->victim_transported_to                = "TestVictimTransportedToAccidentMetaData";
		$localobject->collateral_victim                    = 1;
		$localobject->workhours_morning_date_start         = $localobject->db->idate($now);
		$localobject->workhours_morning_date_end           = $localobject->db->idate($now);
		$localobject->workhours_afternoon_date_start       = $localobject->db->idate($now);
		$localobject->workhours_afternoon_date_end         = $localobject->db->idate($now);
		$localobject->accident_noticed                     = 1;
		$localobject->accident_notice_date                 = $localobject->db->idate($now);
		$localobject->accident_notice_by                   = 1;
		$localobject->accident_described_by_victim         = 1;
		$localobject->registered_in_accident_register      = 1;
		$localobject->register_date                        = $localobject->db->idate($now);
		$localobject->register_number                      = 1;
		$localobject->consequence                          = 1;
		$localobject->police_report                        = 1;
		$localobject->police_report_by                     = "TestPoliceReportByAccidentMetaData";
		$localobject->first_person_noticed_is_witness      = 1;
		$localobject->thirdparty_responsibility            = 1;
		$localobject->accident_investigation               = 1;
		$localobject->accident_investigation_link          = "TestAccidentInvestigationLinkAccidentMetaData";
		$localobject->cerfa_link                           = "TestCerfaLinkAccidentMetaData";
		$localobject->json                                 = "TestJsonAccidentMetaData";
		$localobject->fk_user_witness                      = 1;
		$localobject->fk_soc_responsible                   = 1;
		$localobject->fk_soc_responsible_insurance_society = 1;
		$localobject->fk_accident                          = 1;

		$result = $localobject->create($user);

		$this->assertLessThan($result, 0);

		print __METHOD__ . " result=" . $result . "\n";
		return $result;
	}

	/**
	 * testAccidentMetaDataFetch
	 *
	 * @param   int              $id          Id accident
	 * @return  AccidentMetaData $localobject AccidentMetaData object
	 *
	 * @covers  AccidentMetaData::fetch
	 *
	 * @depends testAccidentMetaDataCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testAccidentMetaDataFetch($id)
	{
		global $conf, $user, $langs, $db;
		$conf  = $this->savconf;
		$user  = $this->savuser;
		$langs = $this->savlangs;
		$db    = $this->savdb;

		$localobject = new AccidentMetaData($this->savdb);

		$result = $localobject->fetch($id);

		$this->assertLessThan($result, 0);

		print __METHOD__ . " id=" . $id . " result=" . $result . "\n";
		return $localobject;
	}

	//	/**
	//	 * testAccidentWorkStopFetchAll
	//	 *
	//	 * @return void
	//	 *
	//	 * @covers AccidentWorkStop::fetchAll
	//	 *
	//	 * @throws Exception
	//	 */
	//	public function testAccidentWorkStopFetchAll() : void
	//	{
	//		global $conf, $user, $langs, $db;
	//		$conf = $this->savconf;
	//		$user = $this->savuser;
	//		$langs = $this->savlangs;
	//		$db = $this->savdb;
	//
	//		$localobjectline = new AccidentWorkStop($this->savdb);
	//		$localobjectlineList = $localobjectline->fetchAll();
	//
	//		$this->assertSame(true, is_array($localobjectlineList));
	//		if (is_array($localobjectlineList)) {
	//			$this->assertInstanceOf(get_class($localobjectline), array_shift($localobjectlineList));
	//		}
	//		print __METHOD__ . " ok";
	//		print "\n";
	//	}

	//	/**
	//	 * testAccidentLesionFetchAll
	//	 *
	//	 * @return void
	//	 *
	//	 * @covers AccidentLesion::fetchAll
	//	 *
	//	 * @throws Exception
	//	 */
	//	public function testAccidentLesionFetchAll() : void
	//	{
	//		global $conf, $user, $langs, $db;
	//		$conf  = $this->savconf;
	//		$user  = $this->savuser;
	//		$langs = $this->savlangs;
	//		$db    = $this->savdb;
	//
	//		$localobjectline     = new AccidentLesion($this->savdb);
	//		$localobjectlineList = $localobjectline->fetchAll();
	//
	//		$this->assertSame(true, is_array($localobjectlineList));
	//		if (is_array($localobjectlineList)) {
	//			$this->assertInstanceOf(get_class($localobjectline), array_shift($localobjectlineList));
	//		}
	//		print __METHOD__ . " ok";
	//		print "\n";
	//	}

	/**
	 * testAccidentFetchAll
	 *
	 * @return void
	 *
	 * @covers Accident::fetchAll
	 *
	 * @throws Exception
	 */
	public function testAccidentFetchAll() : void
	{
		global $conf, $user, $langs, $db;
		$conf  = $this->savconf;
		$user  = $this->savuser;
		$langs = $this->savlangs;
		$db    = $this->savdb;

		$localobject     = new Accident($this->savdb);
		$localobjectList = $localobject->fetchAll();

		$this->assertSame(true, is_array($localobjectList));
		if (is_array($localobjectList)) {
			$this->assertInstanceOf(get_class($localobject), array_shift($localobjectList));
		}
		print __METHOD__ . " ok";
		print "\n";
	}

	/**
	 * testAccidentWorkStopDelete
	 *
	 * @param   AccidentWorkStop $localobjectline Accident workstop object
	 * @return  int
	 *
	 * @covers  AccidentWorkStop::delete
	 *
	 * @depends testAccidentWorkStopUpdate
	 * The depends says test is run only if previous is ok
	 *
	 * @throws Exception
	 */
	public function testAccidentWorkStopDelete($localobjectline)
	{
		global $conf, $user, $langs, $db;
		$conf  = $this->savconf;
		$user  = $this->savuser;
		$langs = $this->savlangs;
		$db    = $this->savdb;

		$newobjectline = new AccidentWorkStop($this->savdb);
		$newobjectline->fetch($localobjectline->id);

		$result = $localobjectline->delete($user);
		print __METHOD__ . " id=" . $newobjectline->id . " result=" . $result . "\n";
		$this->assertLessThan($result, 0);

		return $result;
	}

	/**
	 * testAccidentLesionDelete
	 *
	 * @param   AccidentLesion $localobjectline Accident lesion object
	 * @return  int
	 *
	 * @covers  AccidentLesion::delete
	 *
	 * @depends testAccidentLesionUpdate
	 * The depends says test is run only if previous is ok
	 *
	 * @throws Exception
	 */
	public function testAccidentLesionDelete($localobjectline)
	{
		global $conf, $user, $langs, $db;
		$conf  = $this->savconf;
		$user  = $this->savuser;
		$langs = $this->savlangs;
		$db    = $this->savdb;

		$newobjectline = new AccidentLesion($this->savdb);
		$newobjectline->fetch($localobjectline->id);

		$result = $localobjectline->delete($user);
		print __METHOD__ . " id=" . $newobjectline->id . " result=" . $result . "\n";
		$this->assertLessThan($result, 0);

		return $result;
	}

	/**
	 * testAccidentDelete
	 *
	 * @param   Accident $localobject Accident object
	 * @return  int
	 *
	 * @covers  Accident::delete
	 *
	 * @depends testAccidentUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testAccidentDelete($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf  = $this->savconf;
		$user  = $this->savuser;
		$langs = $this->savlangs;
		$db    = $this->savdb;

		$newobject = new Accident($this->savdb);
		$newobject->fetch($localobject->id);

		$result = $localobject->delete($user);
		print __METHOD__ . " id=" . $newobject->id . " result=" . $result . "\n";
		$this->assertLessThan($result, 0);

		return $result;
	}
}
