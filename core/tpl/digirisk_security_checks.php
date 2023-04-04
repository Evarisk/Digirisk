<?php
$module = 'digiriskdolibarr';

if ($conf->multicompany->enabled) {
	if ($conf->$module->enabled) {
		if ($object->id > 0) {
			if ($object->entity != $conf->entity) {
				$urltogo = dol_buildpath('/custom/' . $module . '/' . $module . 'index.php?mainmenu=' . $module, 1);
				header("Location: " . $urltogo);
				exit;
			}
		}
	} else {
		setEventMessage($langs->trans('EnableDigirisk'), 'warnings');
		$urltogo = dol_buildpath('/admin/modules.php?search_nature=external_Evarisk', 1);
		header("Location: " . $urltogo);
		exit;
	}
}

