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
 *       \file       core/ajax/contacts.php
 *       \brief      File to load contacts combobox
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1'); // Disables token renewal
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

// Get parameters
$id        = GETPOST('id', 'int'); // id of thirdparty
$action    = GETPOST('action', 'aZ09');
$htmlname  = GETPOST('htmlname', 'alpha');
$showempty = GETPOST('showempty', 'int');

// Initialize technical objects
$contact = new Contact($db);

/*
 * View
 */

top_httphead();

// Load original field value
if (!empty($id) && !empty($action) && !empty($htmlname)) {
	$form = new Form($db);

	$return = array();
	if (empty($showempty)) $showempty = 0;

	$contacts = fetchAllSocPeople('',  '',  0,  0, array('customsql' => "s.rowid = $id AND c.email IS NULL OR c.email = ''" ));
	$contacts_no_email = array();
	if (is_array($contacts) && !empty ($contacts) && $contacts > 0) {
		foreach ($contacts as $element) {
			$contacts_no_email[$element->id] = $element->id;
		}
	}

	$return['value'] = $form->selectcontacts($id, '', $htmlname, $showempty, $contacts_no_email, '', 0, '', true);
	$return['num']   = $form->num;
	$return['error'] = $form->error;

	echo json_encode($return);
}

