<?php
/** 		Function called to complete substitution array (before generating on ODT, or a personalized email)
 * 		functions xxx_completesubstitutionarray are called by make_substitutions() if file
 * 		is inside directory htdocs/core/substitutions
 *
 *		@param	array		$substitutionarray	Array with substitution key=>val
 *		@param	Translate	$langs			Output langs
 *		@param	Object		$object			Object to use to get values
 * 		@return	void					The entry parameter $substitutionarray is modified
 */
require_once DOL_DOCUMENT_ROOT.'/custom/digiriskdolibarr/lib/digiriskdolibarr.lib.php';

function digiriskdolibarr_completesubstitutionarray(&$substitutionarray,$langs,$object)
{
    global $conf,$db;

    //Copier la condition ci-dessous pour chaque extrafield à rajouter
	$const = digirisk_dolibarr_fetch_const($db);
	$links = digirisk_dolibarr_fetch_links($db,'all');

	if ($object->fk_socpeople_labour_doctor > 0)
	{
		$labour_doctor = new Contact($db);
		$result = $labour_doctor->fetch($object->fk_socpeople_labour_doctor);
		if ($result < 0) dol_print_error('', $labour_doctor->error);
		elseif ($result > 0) {

			$substitutionarray['service_de_sante_nom']=$labour_doctor->firstname . " " . $labour_doctor->lastname;
			$substitutionarray['service_de_sante_adresse']=$labour_doctor->address;
			$substitutionarray['service_de_sante_code_postal']=$labour_doctor->zip;
			$substitutionarray['service_de_sante_ville']=$labour_doctor->town;
			$substitutionarray['service_de_sante_telephone']=$labour_doctor->phone_pro;
		}
	}

	if ($object->fk_socpeople_labour_inspector > 0)
	{
		$labour_inspector = new Contact($db);
		$result = $labour_inspector->fetch($object->fk_socpeople_labour_inspector);
		if ($result < 0) dol_print_error('', $labour_inspector->error);
		elseif ($result > 0) {
			$substitutionarray['inspection_du_travail_nom']=$labour_inspector->firstname . " " . $labour_inspector->lastname;
			$substitutionarray['inspection_du_travail_adresse']=$labour_inspector->address;
			$substitutionarray['inspection_du_travail_code_postal']=$labour_inspector->zip;
			$substitutionarray['inspection_du_travail_ville']=$labour_inspector->town;
			$substitutionarray['inspection_du_travail_telephone']=$labour_inspector->phone_pro;
		}
	}

    if ($object->fk_soc_samu > 0)
    {
		$samu = new Societe($db);
		$result = $samu->fetch($object->fk_soc_samu);
		if ($result < 0) dol_print_error('', $samu->error);
		elseif ($result > 0) {
            $substitutionarray['samu']=$samu->phone;
        }
    }

	if ($object->fk_soc_pompiers > 0)
	{
		$pompiers = new Societe($db);
		$result = $pompiers->fetch($object->fk_soc_pompiers);
		if ($result < 0) dol_print_error('', $pompiers->error);
		elseif ($result > 0) {
			$substitutionarray['pompier']=$pompiers->phone;
		}
	}

	if ($object->fk_soc_police > 0)
	{
		$police = new Societe($db);
		$result = $police->fetch($object->fk_soc_police);
		if ($result < 0) dol_print_error('', $police->error);
		elseif ($result > 0) {
			$substitutionarray['police']=$police->phone;
		}
	}

	if ($object->fk_soc_urgency > 0)
	{
		$urgency = new Societe($db);
		$result = $urgency->fetch($object->fk_soc_urgency);
		if ($result < 0) dol_print_error('', $urgency->error);
		elseif ($result > 0) {
			$substitutionarray['toute_urgence']=$urgency->phone;
		}
	}

	if ($object->fk_soc_rights_defender > 0)
	{
		$rights_defender = new Societe($db);
		$result = $rights_defender->fetch($object->fk_soc_rights_defender);
		if ($result < 0) dol_print_error('', $rights_defender->error);
		elseif ($result > 0) {
			$substitutionarray['defenseur_des_droits']=$rights_defender->phone;
		}
	}

	if ($object->fk_soc_antipoison > 0)
	{
		$antipoison = new Societe($db);
		$result = $antipoison->fetch($object->fk_soc_antipoison);
		if ($result < 0) dol_print_error('', $antipoison->error);
		elseif ($result > 0) {
			$substitutionarray['anti_poison']=$antipoison->phone;
		}
	}


	if ($object->fk_soc_responsible_prevent > 0)
	{
		$responsible_prevent = new Societe($db);
		$result = $responsible_prevent->fetch($object->fk_soc_responsible_prevent);
		if ($result < 0) dol_print_error('', $responsible_prevent->error);
		elseif ($result > 0) {
			$substitutionarray['responsable_a_prevenir']=$responsible_prevent->name;
			$substitutionarray['telephone']=$responsible_prevent->phone;
		}
	}

	if (!empty($object->note_consigne_detaillee))
	{
			$substitutionarray['emplacement_des_consignes_detaillees']=$object->note_consigne_detaillee;
	}
	if (!empty($object->note_derogation_permanente))
	{
		$substitutionarray['permanente']=$object->note_derogation_permanente;
	}
	if (!empty($object->note_derogation_occas))
	{
		$substitutionarray['occasionnelle']=$object->note_derogation_occas;
	}
	/*
	if (!empty($object->note_convention_collective))
	{
		$substitutionarray['intitule']=$object->note_convention_collective;
	}
	if (!empty($object->note_lieu_cc))
	{
		$substitutionarray['lieu_modalite']=$object->note_lieu_cc;
	}
	*/
	if (!empty($object->note_accord_participation))
	{
		$substitutionarray['modalite_information_ap']=$object->note_accord_participation;
	}

	if (!empty($object->note_lieu_du))
	{
		$substitutionarray['modalite_access']=$object->note_lieu_du;
	}

}
