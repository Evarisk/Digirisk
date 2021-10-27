<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Luracast\Restler\RestException;

require_once __DIR__ .'/../core/modules/modDigiriskDolibarr.class.php';

/**
 * API class for orders
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class DigiriskDolibarr extends DolibarrApi
{
	/**
	 * @var modDigiriskDolibarr $mod {@type modDigiriskDolibarr}
	 */
	public $mod;
	/**
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
	 */
	static $FIELDS = array(
		'socid',
		'date'
	);
	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db, $conf;
		$this->db = $db;

		$this->mod = new modDigiriskdolibarr($this->db);
	}

	/**
	 * Get properties of an order object by id
	 *
	 * Return an array with order informations
	 *
	 * @return 	array|mixed data without useless information
	 *
	 * @throws 	RestException
	 */
	public function enableModule($entity = 14)
	{
		global $conf;
		dol_include_once('/multicompany/class/dao_multicompany.class.php', 'DaoMulticompany');
		$test = new DaoMulticompany($this->db);
		$test->fetch(14);
		$test->getConstants();
		$test->setEntity($entity, 'active', 1);
		$test->getEntityConfig($entity);
		echo '<pre>'; print_r($test); echo '</pre>'; exit;

		//voir comment multicompany arrive à switch de $conf
//		$conf->entity = 14;

		echo '<pre>'; print_r($conf->entity ); echo '</pre>'; exit;

		return $this->mod->init();
	}

	/**
	 * Get properties of an order object by ref
	 *
	 * Return an array with order informations
	 *
	 * @param       int         $contact_list  0: Returned array of contacts/addresses contains all properties, 1: Return array contains just id
	 * @return 	array|mixed data without useless information
	 *
	 * @url GET    ref/{ref}
	 *
	 * @throws 	RestException
	 */
	public function disableModule()
	{
		return $this->mod->remove();
	}

	/**
	 * Get properties of an order object by ref_ext
	 *
	 * Return an array with order informations
	 *
	 * @return 	string|mixed data without useless information
	 *
	 * @url GET    ref_ext/{ref_ext}
	 *
	 * @throws 	RestException
	 */
	public function getVersion()
	{
		return $this->mod->version;
	}

//	/**
//	 * Get properties of an order object by ref_ext
//	 *
//	 * Return an array with order informations
//	 *
//	 * @param       string		$ref_ext			External reference of object
//	 * @param       int         $contact_list  0: Returned array of contacts/addresses contains all properties, 1: Return array contains just id
//	 * @return 	array|mixed data without useless information
//	 *
//	 * @url GET    ref_ext/{ref_ext}
//	 *
//	 * @throws 	RestException
//	 */
//	public function uploadNewModule($ref_ext, $contact_list = 1)
//	{
//		return $this->_fetch('', '', $ref_ext, $contact_list);
//	}

}
