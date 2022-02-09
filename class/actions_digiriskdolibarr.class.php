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
 * \file    class/actions_digiriskdolibarr.class.php
 * \ingroup digiriskdolibarr
 * \brief   DigiriskDolibarr hook overload.
 */

/**
 * Class ActionsDigiriskdolibarr
 */
class ActionsDigiriskdolibarr
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error string
	 */
	public $error;

	/**
	 * @var string[] Array of error strings
	 */
	public $errors = array();

	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Overloading the printCommonFooter function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printCommonFooter($parameters)
	{
		global $db, $conf, $langs;

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if ($parameters['currentcontext'] == 'admincompany') {	    // do something only for the context 'somecontext1' or 'somecontext2'
			?>
			<script src="../custom/digiriskdolibarr/js/digiriskdolibarr.js.php"></script>
			<?php
			if ($conf->global->MAIN_INFO_SOCIETE_COUNTRY == '1:FR:France') {
				$formother = new FormOther($db);
				$form      = new Form($db);
				?>
				<script>
					IDCC_form = '<?php $formother->select_dictionary('DIGIRISK_COLLECTIVE_AGREEMENT_TITLE', 'c_conventions_collectives', 'code', 'libelle', $conf->global->DIGIRISK_COLLECTIVE_AGREEMENT_TITLE, 1);?>';

					let $tr = $('<tr class="oddeven"><td><label for="selectidcc_id"><?php print $form->textwithpicto($langs->trans('IDCC'), $langs->trans('IDCCTooltip'));?></label></td>');
					$tr.append('<td>' + IDCC_form + '</td></tr>');

					let currElement = $('table:nth-child(7) .oddeven:last-child');
					currElement.after($tr);
				</script>
				<?php
			}
			print ajax_combobox('selectDIGIRISK_COLLECTIVE_AGREEMENT_TITLE');
		}

		if (true) {
			$this->results   = array('myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 *  Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param Hook $parameters metadatas (context, etc...)
	 * @param $action
	 * @return int              < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, $action)
	{
		global $db, $conf;

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if ($parameters['currentcontext'] == 'admincompany') {	    // do something only for the context 'somecontext1' or 'somecontext2'
			if ($action == 'updateedit' || $action == 'update') {
				dolibarr_set_const($db, "DIGIRISK_COLLECTIVE_AGREEMENT_TITLE", GETPOST("DIGIRISK_COLLECTIVE_AGREEMENT_TITLE", 'nohtml'), 'chaine', 0, '', $conf->entity);
			}
		}

		if (true) {
			$this->results   = array('myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 *  Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param $parameters
	 * @return int
	 */
	public function emailElementlist($parameters)
	{
		global $conf, $user, $langs;

		$value = array();

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if ($parameters['currentcontext'] == 'emailtemplates') {	    // do something only for the context 'somecontext1' or 'somecontext2'
			if ($conf->digiriskdolibarr->enabled && $user->rights->digiriskdolibarr->preventionplan->read) {
				$value['preventionplan'] = '<i class="fas fa-info"></i>  ' . dol_escape_htmltag($langs->trans('PreventionPlan'));
			}
			if ($conf->digiriskdolibarr->enabled && $user->rights->digiriskdolibarr->firepermit->read) {
				$value['firepermit'] = '<i class="fas fa-fire-alt"></i>  ' . dol_escape_htmltag($langs->trans('FirePermit'));
			}
			if ($conf->digiriskdolibarr->enabled && $user->rights->digiriskdolibarr->riskassessmentdocument->read) {
				$value['riskassessmentdocument'] = '<i class="fas fa-exclamation-triangle"></i>  ' . dol_escape_htmltag($langs->trans('RiskAssessmentDocument'));
			}
		}

		if (true) {
			$this->results = $value;
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 *  Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param $parameters
	 * @return int
	 */
	public function redirectAfterConnection($parameters)
	{
		global $conf;

		$value = array();

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if ($parameters['currentcontext'] == 'mainloginpage') {	    // do something only for the context 'somecontext1' or 'somecontext2'
			if ($conf->global->DIGIRISKDOLIBARR_REDIRECT_AFTER_CONNECTION > 0) {
				$value = dol_buildpath('/custom/digiriskdolibarr/digiriskdolibarrindex.php?idmenu=1319&mainmenu=digiriskdolibarr&leftmenu=', 1);
			} else {
				$value = '';
			}
		}

		if (true) {
			$this->resprints = $value;
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}
}
