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
 *      \file       test/phpunit/DigiriskSignatureUnitTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf, $user, $langs, $db;

require_once __DIR__ . '/../../../../../htdocs/master.inc.php';
require_once __DIR__ . '/../../class/digirisksignature.class.php';

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
class DigiriskSignatureUnitTest extends PHPUnit\Framework\TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return DigiriskSignatureUnitTest
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
	 * testDigiriskSignatureCreate
	 *
	 * @covers DigiriskSignature::create
	 *
	 * @return int
	 */
	public function testDigiriskSignatureCreate()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new DigiriskSignature($this->savdb);

		$now                               = dol_now();
		$localobject->id                   = 0;
		$localobject->entity               = 1;
		$localobject->date_creation        = $localobject->db->idate($now);
		$localobject->tms                  = $now;
		$localobject->import_key           = 1;
		$localobject->status               = 1;
		$localobject->role                 = "TestRoleDigiriskSignature";
		$localobject->firstname            = "TestFirstnameDigiriskSignature";
		$localobject->lastname             = "TestLastnameDigiriskSignature";
		$localobject->email                = "TestEmailDigiriskSignature";
		$localobject->phone                = "TestPhoneDigiriskSignature";
		$localobject->society_name         = "TestSocietyNameDigiriskSignature";
		$localobject->signature_date       = $localobject->db->idate($now);
		$localobject->signature_location   = "TestSignatureLocationDigiriskSignature";
		$localobject->signature_comment    = "TestSignatureCommentDigiriskSignature";
		$localobject->element_id           = 1;
		$localobject->element_type         = "user";
		$localobject->signature            = "TestSignatureDigiriskSignature";
		$localobject->stamp                = "TestStampDigiriskSignature";
		$localobject->signature_url        = "TestSignatureUrlDigiriskSignature";
		$localobject->transaction_url      = "TestTransactionUrlDigiriskSignature";
		$localobject->last_email_sent_date = $localobject->db->idate($now);
		$localobject->object_type          = "preventionplan";
		$localobject->fk_object            = 1;

		$result = $localobject->create($user);

		$this->assertLessThan($result, 0);

		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testDigiriskSignatureFetch
	 *
	 * @param   int            $id          Id prevention plan
	 * @return  DigiriskSignature $localobject Digirisk signature object
	 *
	 * @covers  DigiriskSignature::fetch
	 *
	 * @depends testDigiriskSignatureCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testDigiriskSignatureFetch($id)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new DigiriskSignature($this->savdb);

		$result = $localobject->fetch($id);

		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testDigiriskSignatureSetRegistered
	 *
	 * @param   DigiriskSignature $localobject Digirisk signature object
	 * @return  void
	 *
	 * @covers  DigiriskSignature::setRegistered
	 * @covers  DigiriskSignature::getLibStatut
	 * @covers  DigiriskSignature::LibStatut
	 *
	 * @depends testDigiriskSignatureFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testDigiriskSignatureSetRegistered($localobject) : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->setRegistered($user);
		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$result = $localobject->getLibStatut(0);
		$this->assertSame($result, $langs->trans('Registered'));

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
	}

	/**
	 * testDigiriskSignatureSetPending
	 *
	 * @param   DigiriskSignature $localobject Digirisk signature object
	 * @return  void
	 *
	 * @covers  DigiriskSignature::setPending
	 * @covers  DigiriskSignature::getLibStatut
	 * @covers  DigiriskSignature::LibStatut
	 *
	 * @depends testDigiriskSignatureFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testDigiriskSignatureSetPending($localobject) : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->setPending($user);
		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$result = $localobject->getLibStatut(0);
		$this->assertSame($result, $langs->trans('PendingSignature'));

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
	}

	/**
	 * testDigiriskSignatureSetSigned
	 *
	 * @param   DigiriskSignature $localobject Digirisk signature object
	 * @return  void
	 *
	 * @covers  DigiriskSignature::setSigned
	 * @covers  DigiriskSignature::getLibStatut
	 * @covers  DigiriskSignature::LibStatut
	 *
	 * @depends testDigiriskSignatureFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testDigiriskSignatureSetSigned($localobject) : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->setSigned($user);
		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$result = $localobject->getLibStatut(0);
		$this->assertSame($result, $langs->trans('Signed'));

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
	}

	/**
	 * testDigiriskSignatureSetAbsent
	 *
	 * @param   DigiriskSignature $localobject Digirisk signature object
	 * @return  void
	 *
	 * @covers  DigiriskSignature::setAbsent
	 * @covers  DigiriskSignature::getLibStatut
	 * @covers  DigiriskSignature::LibStatut
	 *
	 * @depends testDigiriskSignatureFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testDigiriskSignatureSetAbsent($localobject) : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->setAbsent($user);
		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$result = $localobject->getLibStatut(0);
		$this->assertSame($result, $langs->trans('Absent'));

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
	}

	/**
	 * testDigiriskSignatureSetDeleted
	 *
	 * @param   DigiriskSignature $localobject Digirisk signature object
	 * @return  void
	 *
	 * @covers  DigiriskSignature::setDeleted
	 * @covers  DigiriskSignature::getLibStatut
	 * @covers  DigiriskSignature::LibStatut
	 *
	 * @depends testDigiriskSignatureFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testDigiriskSignatureSetDeleted($localobject) : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->setDeleted($user);
		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$result = $localobject->getLibStatut(0);
		$this->assertSame($result, $langs->trans('Deleted'));

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
	}

	/**
	 * testDigiriskSignatureUpdate
	 *
	 * @param   DigiriskSignature $localobject Digirisk signature object
	 * @return  DigiriskSignature $localobject Digirisk signature object
	 *
	 * @covers  DigiriskSignature::update
	 *
	 * @depends testDigiriskSignatureFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testDigiriskSignatureUpdate($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$now                               = dol_now();
		$localobject->entity               = 1;
		$localobject->date_creation        = $now;
		$localobject->tms                  = $now;
		$localobject->import_key           = 1;
		$localobject->status               = 5;
		$localobject->role                 = "UpdatedTestRoleDigiriskSignature";
		$localobject->firstname            = "UpdatedTestFirstnameDigiriskSignature";
		$localobject->lastname             = "UpdatedTestLastnameDigiriskSignature";
		$localobject->email                = "UpdatedTestEmailDigiriskSignature";
		$localobject->phone                = "UpdatedTestPhoneDigiriskSignature";
		$localobject->society_name         = "UpdatedTestSocietyNameDigiriskSignature";
		$localobject->signature_date       = $now;
		$localobject->signature_location   = "UpdatedTestSignatureLocationDigiriskSignature";
		$localobject->signature_comment    = "UpdatedTestSignatureCommentDigiriskSignature";
		$localobject->element_id           = 1;
		$localobject->element_type         = "user";
		$localobject->signature            = "UpdatedTestSignatureDigiriskSignature";
		$localobject->stamp                = "UpdatedTestStampDigiriskSignature";
		$localobject->signature_url        = "UpdatedTestSignatureUrlDigiriskSignature";
		$localobject->transaction_url      = "UpdatedTestTransactionUrlDigiriskSignature";
		$localobject->last_email_sent_date = $now;
		$localobject->object_type          = "preventionplan";
		$localobject->fk_object            = 1;

		$result = $localobject->update($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		$newobject = new DigiriskSignature($this->savdb);
		$result = $newobject->fetch($localobject->id);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		$this->assertEquals($localobject->id, $newobject->id);
		$this->assertSame($localobject->ref, $newobject->ref);
		$this->assertSame($localobject->ref_ext, $newobject->ref_ext);
		$this->assertSame($localobject->entity, $newobject->entity);
		$this->assertSame($localobject->date_creation, $newobject->date_creation);
		$this->assertSame($localobject->import_key, $newobject->import_key);
		$this->assertSame($localobject->tms, $newobject->tms);
		$this->assertEquals($localobject->status, $newobject->status);
		$this->assertSame($localobject->role, $newobject->role);
		$this->assertSame($localobject->firstname, $newobject->firstname);
		$this->assertSame($localobject->lastname, $newobject->lastname);
		$this->assertSame($localobject->email, $newobject->email);
		$this->assertSame($localobject->phone, $newobject->phone);
		$this->assertSame($localobject->society_name, $newobject->society_name);
		$this->assertSame($localobject->signature_date, $newobject->signature_date);
		$this->assertSame($localobject->signature_location, $newobject->signature_location);
		$this->assertSame($localobject->signature_comment, $newobject->signature_comment);
		$this->assertSame($localobject->element_id, $newobject->element_id);
		$this->assertSame($localobject->element_type, $newobject->element_type);
		$this->assertSame($localobject->signature, $newobject->signature);
		$this->assertSame($localobject->stamp, $newobject->stamp);
		$this->assertSame($localobject->signature_url, $newobject->signature_url);
		$this->assertSame($localobject->transaction_url, $newobject->transaction_url);
		$this->assertSame($localobject->last_email_sent_date, $newobject->last_email_sent_date);
		$this->assertSame($localobject->object_type, $newobject->object_type);
		$this->assertEquals($localobject->fk_object, $newobject->fk_object);

		return $localobject;
	}

	/**
	 * testDigiriskSignatureCheckSignatoriesSignatures
	 *
	 * @return void
	 *
	 * @covers DigiriskSignature::checkSignatoriesSignatures
	 *
	 * @depends testDigiriskSignatureUpdate
	 * The depends says test is run only if previous is ok
	 *
	 * @throws Exception
	 */
	public function testDigiriskSignatureCheckSignatoriesSignatures($localobject) : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$newobject = new DigiriskSignature($this->savdb);
		$newobject->fetch($localobject->id);

		$result = $newobject->checkSignatoriesSignatures($newobject->fk_object, $newobject->object_type);
		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
	}

	/**
	 * testDigiriskSignatureSetSignatory
	 *
	 * @covers DigiriskSignature::setSignatory
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function testDigiriskSignatureSetSignatory() : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new DigiriskSignature($this->savdb);

		$localobject->role         = "UpdatedTestRoleDigiriskSignature";
		$localobject->element_type = "user";
		$localobject->element_id   = 1;
		$localobject->object_type  = "preventionplan";
		$localobject->fk_object    = 1;

		$result = $localobject->setSignatory($localobject->fk_object, $localobject->object_type, $localobject->element_type, array($localobject->element_id), $localobject->role, 0);

		$this->assertLessThan($result, 0);

		print __METHOD__." result=".$result."\n";
	}

	/**
	 * testDigiriskSignatureFetchAll
	 *
	 * @return void
	 *
	 * @covers DigiriskSignature::fetchAll
	 *
	 * @throws Exception
	 */
	public function testDigiriskSignatureFetchAll() : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new DigiriskSignature($this->savdb);
		$localobjectList = $localobject->fetchAll();

		$this->assertSame(true, is_array($localobjectList));
		$this->assertInstanceOf(get_class($localobject), array_shift($localobjectList));
		print __METHOD__ . " ok";
		print "\n";
	}

	/**
	 * testDigiriskSignatureFetchSignatory
	 *
	 * @return void
	 *
	 * @covers DigiriskSignature::fetchSignatory
	 *
	 * @throws Exception
	 */
	public function testDigiriskSignatureFetchSignatory() : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new DigiriskSignature($this->savdb);
		$localobjectList = $localobject->fetchSignatory("UpdatedTestRoleDigiriskSignature", 1, 'preventionplan');

		$this->assertSame(true, is_array($localobjectList));
		$this->assertInstanceOf(get_class($localobject), array_shift($localobjectList));
		print __METHOD__ . " ok";
		print "\n";
	}

	/**
	 * testDigiriskSignatureFetchSignatories
	 *
	 * @return void
	 *
	 * @covers DigiriskSignature::fetchSignatories
	 *
	 * @throws Exception
	 */
	public function testDigiriskSignatureFetchSignatories() : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new DigiriskSignature($this->savdb);
		$localobjectList = $localobject->fetchSignatories(1, 'preventionplan');

		$this->assertSame(true, is_array($localobjectList));
		$this->assertInstanceOf(get_class($localobject), array_shift($localobjectList));
		print __METHOD__ . " ok";
		print "\n";
	}

	/**
	 * testDigiriskSignatureDeletePreviousSignatories
	 *
	 * @return void
	 *
	 * @covers DigiriskSignature::deletePreviousSignatories
	 *
	 * @throws Exception
	 */
	public function testDigiriskSignatureDeletePreviousSignatories() : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new DigiriskSignature($this->savdb);
		$result = $localobject->deletePreviousSignatories("UpdatedTestRoleDigiriskSignature", 1, 'preventionplan');

		$this->assertEquals($result, 0);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
	}


	/**
	 * testDigiriskSignatureDeleteSignatoriesSignatures
	 *
	 * @return void
	 *
	 * @covers DigiriskSignature::deleteSignatoriesSignatures
	 *
	 * @throws Exception
	 */
	public function testDigiriskSignatureDeleteSignatoriesSignatures() : void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new DigiriskSignature($this->savdb);
		$result = $localobject->deleteSignatoriesSignatures(1, 'preventionplan');

		$this->assertLessThan($result, 0);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
	}

	/**
	 * testDigiriskSignatureDelete
	 *
	 * @param   DigiriskSignature $localobject Digirisk signature object
	 * @return  int
	 *
	 * @covers  DigiriskSignature::delete
	 *
	 * @depends testDigiriskSignatureUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testDigiriskSignatureDelete($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$newobject = new DigiriskSignature($this->savdb);
		$newobject->fetch($localobject->id);

		$result = $localobject->delete($user);
		print __METHOD__." id=".$newobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $result;
	}
}

