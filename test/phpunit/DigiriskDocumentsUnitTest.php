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
 *      \file       test/phpunit/DigiriskDocumentsUnitTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf, $user, $langs, $db;

require_once __DIR__ . '/../../../../../htdocs/master.inc.php';
require_once __DIR__ . '/../../class/digiriskdocuments.class.php';
require_once __DIR__ . '/../../class/digiriskdocuments/legaldisplay.class.php';

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
class DigiriskDocumentsUnitTest extends PHPUnit\Framework\TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return DigiriskDocumentsUnitTest
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
	 * testDigiriskDocumentsCreate
	 *
	 * @covers DigiriskDocuments::create
	 *
	 * @return int
	 */
	public function testDigiriskDocumentsCreate()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new LegalDisplay($this->savdb);

		$now                        = dol_now();
		$localobject->id            = 0;
		//$localobject->ref         = $refDigiriskDocumentsMod->getNextValue($localobject);
		$localobject->ref           = "TestRefDigiriskDocuments";
		$localobject->ref_ext       = "TestRefExtDigiriskDocuments";
		$localobject->entity        = 1;
		$localobject->date_creation = $localobject->db->idate($now);
		$localobject->tms           = $now;
		$localobject->import_key    = 1;
		$localobject->status        = 1;
		$localobject->type          = "legaldisplay";
		$localobject->json          = "TestJSONDigiriskDocuments";
		$localobject->model_pdf     = "TestModelPDFDigiriskDocuments";
		$localobject->model_odt     = "TestModelODTDigiriskDocuments";
		$localobject->last_main_doc = "TestLastMainDocDigiriskDocuments";
		$localobject->parent_type   = "digiriskstandard";
		$localobject->parent_id     = 1;
		$localobject->fk_user_creat = $user->id ?: 1;

		$result = $localobject->create($user);

		$this->assertLessThan($result, 0);

		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testDigiriskDocumentsFetch
	 *
	 * @param   int               $id          Id digirisk documents
	 * @return  DigiriskDocuments $localobject DigiriskDocuments object
	 *
	 * @covers  DigiriskDocuments::fetch
	 *
	 * @depends testDigiriskDocumentsCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testDigiriskDocumentsFetch($id)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new LegalDisplay($this->savdb);

		$result = $localobject->fetch($id);

		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testDigiriskDocumentsInfo
	 *
	 * @param   DigiriskDocuments $localobject DigiriskDocuments object
	 * @return  void
	 *
	 * @covers  DigiriskDocuments::info
	 *
	 * @depends testDigiriskDocumentsFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testDigiriskDocumentsInfo($localobject) : void
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
	 * testDigiriskDocumentsUpdate
	 *
	 * @param   DigiriskDocuments $localobject DigiriskDocuments object
	 * @return  DigiriskDocuments $localobject DigiriskDocuments object
	 *
	 * @covers  DigiriskDocuments::update
	 *
	 * @depends testDigiriskDocumentsFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testDigiriskDocumentsUpdate($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$now                        = dol_now();
		//$localobject->ref         = $refDigiriskDocumentsMod->getNextValue($localobject);
		$localobject->ref           = "UpdatedTestRefDigiriskDocuments";
		$localobject->ref_ext       = "UpdatedTestRefExtDigiriskDocuments";
		$localobject->entity        = 1;
		$localobject->date_creation = $now;
		$localobject->tms           = $now;
		$localobject->import_key    = 1;
		$localobject->status        = 1;
		$localobject->type          = "legaldisplay";
		$localobject->json          = "UpdatedTestJSONDigiriskDocuments";
		$localobject->model_pdf     = "UpdatedTestModelPDFDigiriskDocuments";
		$localobject->model_odt     = "UpdatedTestModelODTDigiriskDocuments";
		$localobject->last_main_doc = "UpdatedTestLastMainDocDigiriskDocuments";
		$localobject->parent_type   = "digiriskstandard";
		$localobject->parent_id     = 1;
		$localobject->fk_user_creat = $user->id ?: 1;

		$result = $localobject->update($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		$newobject = new LegalDisplay($this->savdb);
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
		$this->assertSame($localobject->type, $newobject->type);
		$this->assertSame($localobject->json, $newobject->json);
		$this->assertSame($localobject->model_pdf, $newobject->model_pdf);
		$this->assertSame($localobject->model_odt, $newobject->model_odt);
		$this->assertSame($localobject->last_main_doc, $newobject->last_main_doc);
		$this->assertSame($localobject->parent_type, $newobject->parent_type);
		$this->assertSame($localobject->parent_id, $newobject->parent_id);
		$this->assertEquals($localobject->fk_user_creat, $newobject->fk_user_creat);

		return $localobject;
	}

//	/**
//	 * testDigiriskDocumentsDigiriskFillJSON
//	 *
//	 * @param  DigiriskDocuments $localobject DigiriskDocuments object
//	 * @return void
//	 *
//	 * @covers DigiriskDocuments::DigiriskFillJSON
//	 *
//	 * @depends testDigiriskDocumentsUpdate
//	 * The depends says test is run only if previous is ok
//	 */
//	public function testDigiriskDocumentsDigiriskFillJSON($localobject) : void
//	{
//		global $conf, $user, $langs, $db;
//		$conf = $this->savconf;
//		$user = $this->savuser;
//		$langs = $this->savlangs;
//		$db = $this->savdb;
//
//		$newobject = new DigiriskDocuments($this->savdb);
//		$result = $newobject->DigiriskFillJSON($localobject);
//		echo '<pre>'; print_r( $result ); echo '</pre>'; exit;
//
//
//		$this->assertSame(true, is_string($result));
//		print __METHOD__ . " ok";
//		print "\n";
//	}

	/**
	 * testDigiriskDocumentsFetchAll
	 *
	 * @return void
	 *
	 * @covers DigiriskDocuments::fetchAll
	 *
	 * @throws Exception
	 */
	public function testDigiriskDocumentsFetchAll() : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new LegalDisplay($this->savdb);
		$localobjectList = $localobject->fetchAll();

		$this->assertSame(true, is_array($localobjectList));
		print __METHOD__ . " ok";
		print "\n";
	}

	/**
	 * testDigiriskDocumentsDelete
	 *
	 * @param   DigiriskDocuments $localobject DigiriskDocuments object
	 * @return  int
	 *
	 * @covers  DigiriskDocuments::delete
	 *
	 * @depends testDigiriskDocumentsUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testDigiriskDocumentsDelete($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$newobject = new LegalDisplay($this->savdb);
		$newobject->fetch($localobject->id);

		$result = $localobject->delete($user);
		print __METHOD__." id=".$newobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $result;
	}
}

