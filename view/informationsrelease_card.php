<?php

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");

// Change this following line to use the correct relative path from htdocs
dol_include_once('/digiriskdolibarr/class/legaldisplay.class.php');
dol_include_once('../core/class/html.formfile.class.php');

dol_include_once('../core/lib/functions2.lib.php');
dol_include_once('../core/class/html.formorder.class.php');
dol_include_once('../core/class/html.formmargin.class.php');

// Load traductions files requiredby by page
$langs->load("companies");
$langs->load("other");
$object = new Legaldisplay(1);
$formfile = new FormFile($db);

$id			= 'daz';
$model_pdf = 'einstein';


print '<a name="builddoc"></a>'; // ancre
		// Documents
		$objref = dol_sanitizeFileName('daz');
		$relativepath = $objref.'/'.$objref.'.pdf';
		$filedir = DOL_DATA_ROOT . '/digiriskdolibarr/legaldisplay';

		$urlsource = $_SERVER["PHP_SELF"]."?id=".'1';
		$genallowed = 1;
		$delallowed = 1;
	//	echo '<pre>'; var_dump([$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $soc->default_lang, '', $object]); echo '</pre>'; exit;

		print $formfile->showdocuments('digiriskdolibarr:legaldisplay', $objref, $filedir, $urlsource, $genallowed, $delallowed, $model_pdf, 1, 0, 0, 28, 0, '', '', '', '', '', $object);
		$usercancreate = 1;
	