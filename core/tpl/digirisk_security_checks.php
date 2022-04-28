<?php
if (($object->id > 0) && $conf->multicompany->enabled) {
	if ($object->entity != $conf->entity) {
		$urltogo = dol_buildpath('/custom/digiriskdolibarr/digiriskdolibarrindex.php?idmenu=1319&mainmenu=digiriskdolibarr&leftmenu=', 1);
		header("Location: " . $urltogo);
		exit;
	}
}

