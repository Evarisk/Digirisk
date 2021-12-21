<?php
/**
 *      \file       test/phpunit/DigiriskElementFunctionalTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf, $user, $langs, $db;


define('DOL_DOCUMENT_ROOT', __DIR__ . '/../../../../../htdocs');
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
class DigiriskElementFunctionalTest extends PHPUnit\Framework\TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;
	protected $digiriskelement;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return DigiriskElementFunctionalTest
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
		$this->digiriskelement =  new \DigiriskElement($this->savdb);

		print __METHOD__ . " ok";
		print "\n";

	}

	public function testDigiriskElementCreate()
	{
		$this->assertSame(-1, $this->digiriskelement->create($this->savuser));

		$this->digiriskelement->ref = 'testitesto' ;
		$this->digiriskelement->label = 'le test unitaire';
		$this->digiriskelement->fk_parent = 0;

		$this->assertGreaterThan(0, $this->digiriskelement->create($this->savuser));
		print __METHOD__ . " ok";
		print "\n";
	}

	public function testDigiriskElementFetch()
	{
		$this->assertSame(-1, $this->digiriskelement->fetch(0));
		$digiriskelementtmp = $this->digiriskelement;
		$digiriskelementtmp->fetch(1);
		$this->assertInstanceOf(get_class($this->digiriskelement), $digiriskelementtmp);
		print __METHOD__ . " ok";
		print "\n";
	}

	public function testDigiriskElementFetchAll()
	{
		$digiriskelementList = $this->digiriskelement->fetchAll();
		$this->assertSame(true, is_array($digiriskelementList));
		$this->assertInstanceOf(get_class($this->digiriskelement), array_shift($digiriskelementList));
		print __METHOD__ . " ok";
		print "\n";
	}

	public function testDigiriskElementFetchDigiriskElementFlat()
	{
		$digiriskelementFlatList = $this->digiriskelement->fetchDigiriskElementFlat(0);
		$this->assertSame(true, is_array($digiriskelementFlatList));
		$this->assertInstanceOf(get_class($this->digiriskelement), array_shift($digiriskelementFlatList)['object']);
		$this->assertIsInt(array_shift($digiriskelementFlatList)['depth']);
		print __METHOD__ . " ok";
		print "\n";
	}

	public function testDigiriskElementUpdate()
	{
		$digiriskelementtmp = $this->digiriskelement;
		$digiriskelementtmp->fetch(1);

		$digiriskelementtmp->ref = 'updatedRef';
		$digiriskelementtmp->ref_ext = 'updatedRefExt';
		$digiriskelementtmp->status = 128;
		$digiriskelementtmp->label = 'updatedLabel';
		$digiriskelementtmp->description = 'updatedDescription';
		$digiriskelementtmp->element_type = 'updatedElementType';
		$digiriskelementtmp->photo = 'updatedPhoto';
		$digiriskelementtmp->fk_user_creat = 256;
		$digiriskelementtmp->fk_user_modif = 512;
		$digiriskelementtmp->fk_parent = 1024;
		$digiriskelementtmp->fk_standard = 2048;
		$digiriskelementtmp->rank = 4096;

		$this->assertSame($digiriskelementtmp->id, $digiriskelementtmp->update($this->savuser));
		$this->assertSame('updatedRef', $digiriskelementtmp->ref);
		$this->assertSame('updatedRefExt', $digiriskelementtmp->ref_ext);
		$this->assertSame(128, $digiriskelementtmp->status);
		$this->assertSame('updatedLabel', $digiriskelementtmp->label);
		$this->assertSame('updatedDescription', $digiriskelementtmp->description);
		$this->assertSame('updatedElementType', $digiriskelementtmp->element_type);
		$this->assertSame('updatedPhoto', $digiriskelementtmp->photo);
		$this->assertSame(256, $digiriskelementtmp->fk_user_creat);
		$this->assertSame(512, $digiriskelementtmp->fk_user_modif);
		$this->assertSame(1024, $digiriskelementtmp->fk_parent);
		$this->assertSame(2048, $digiriskelementtmp->fk_standard);
		$this->assertSame(4096, $digiriskelementtmp->rank);

		print __METHOD__ . " ok";
		print "\n";
	}

}

