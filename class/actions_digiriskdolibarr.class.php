<?php

class ActionsDigiriskDolibarr
{
/**
* Overloading the doActions function : replacing the parent's function with the one below
*
* @param   array()         $parameters     Hook metadatas (context, etc...)
* @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
* @param   string          &$action        Current action (if set). Generally create or edit or null
* @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
* @return  int                             < 0 on error, 0 on success, 1 to replace standard code
*/
	function completeTabsHead($parameters, &$object, &$action)
	{
		global $hookmanager, $langs, $conf;
		$error = 0;

		$h = 0;
		$head = array();

		$head[$h][0] = DOL_URL_ROOT."/admin/company.php";
		$head[$h][1] = $langs->trans("Company");
		$head[$h][2] = 'company';
		$h++;

		$head[$h][0] = DOL_URL_ROOT."/admin/openinghours.php";
		$head[$h][1] = $langs->trans("OpeningHours");
		$head[$h][2] = 'openinghours';
		$h++;

		$head[$h][0] = DOL_URL_ROOT."/admin/accountant.php";
		$head[$h][1] = $langs->trans("Accountant");
		$head[$h][2] = 'accountant';
		$h++;

		$head[$h][0] = DOL_URL_ROOT."/custom/digiriskdolibarr/admin/securityconf.php";
		$head[$h][1] = $langs->trans("Security");
		$head[$h][2] = 'security';
		$h++;

		$head[$h][0] = DOL_URL_ROOT."/custom/digiriskdolibarr/admin/socialconf.php";
		$head[$h][1] = $langs->trans("Social");
		$head[$h][2] = 'social';
		$h++;
		//if (in_array('admincompany', explode(':', $parameters['context'])))


		if (! $error)
		{
			$this->results = $head;
			$this->resprints = 'A text to show';
			return 1; // or return 1 to replace standard code
		}
		else
		{
			$this->errors[] = 'Error message';
			return -1;
		}
	}
/*
	function doActions($parameters, &$object, &$action)
	{
		global $hookmanager, $langs, $conf;
		$error = 0;

		$h = 0;
		$head = array();

		$head[$h][0] = DOL_URL_ROOT."/admin/company.php";
		$head[$h][1] = $langs->trans("Company");
		$head[$h][2] = 'company';
		$h++;

		$head[$h][0] = DOL_URL_ROOT."/admin/openinghours.php";
		$head[$h][1] = $langs->trans("OpeningHours");
		$head[$h][2] = 'openinghours';
		$h++;

		$head[$h][0] = DOL_URL_ROOT."/admin/accountant.php";
		$head[$h][1] = $langs->trans("Accountant");
		$head[$h][2] = 'accountant';
		$h++;

		$head[$h][0] = DOL_URL_ROOT."/admin/accountant.php";
		$head[$h][1] = $langs->trans("HSE");
		$head[$h][2] = 'HSE';
		$h++;
		//if (in_array('admincompany', explode(':', $parameters['context'])))


		if (! $error)
		{
			$this->results = $head;
			$this->resprints = 'A text to show';
			return 1; // or return 1 to replace standard code
		}
		else
		{
			$this->errors[] = 'Error message';
			return -1;
		}
	} */
}
