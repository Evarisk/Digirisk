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

	$legaldisplay = json_decode($object->json, false, 512, JSON_UNESCAPED_UNICODE)->LegalDisplay;

	$substitutionarray['service_de_sante_nom']                 = $legaldisplay->occupational_health_service->name;
	$substitutionarray['service_de_sante_adresse']             = $legaldisplay->occupational_health_service->address;
	$substitutionarray['service_de_sante_code_postal']         = $legaldisplay->occupational_health_service->zip;
	$substitutionarray['service_de_sante_ville']               = $legaldisplay->occupational_health_service->town;
	$substitutionarray['service_de_sante_telephone']           = $legaldisplay->occupational_health_service->phone;

	$substitutionarray['inspection_du_travail_nom']            = $legaldisplay->detective_work->name;
	$substitutionarray['inspection_du_travail_adresse']        = $legaldisplay->detective_work->address;
	$substitutionarray['inspection_du_travail_code_postal']    = $legaldisplay->detective_work->zip;
	$substitutionarray['inspection_du_travail_ville']          = $legaldisplay->detective_work->town;
	$substitutionarray['inspection_du_travail_telephone']      = $legaldisplay->detective_work->phone;

    $substitutionarray['samu']                                 = $legaldisplay->emergency_service->samu;
	$substitutionarray['pompier']                              = $legaldisplay->emergency_service->pompier;
	$substitutionarray['police']                               = $legaldisplay->emergency_service->police;
	$substitutionarray['toute_urgence']                        = $legaldisplay->emergency_service->emergency;
	$substitutionarray['defenseur_des_droits']                 = $legaldisplay->emergency_service->right_defender;
	$substitutionarray['anti_poison']                          = $legaldisplay->emergency_service->poison_control_center;

	$substitutionarray['responsable_a_prevenir']               = $legaldisplay->safety_rule->responsible_for_preventing;
	$substitutionarray['telephone']                            = $legaldisplay->safety_rule->phone;
	$substitutionarray['emplacement_des_consignes_detaillees'] = $legaldisplay->safety_rule->location_of_detailed_instruction;
	$substitutionarray['permanente']                           = $legaldisplay->derogation_schedule->permanent;
	$substitutionarray['occasionnelle']                        = $legaldisplay->derogation_schedule->occasional;
	$substitutionarray['intitule']                             = $legaldisplay->collective_agreement->title_of_the_applicable_collective_agreement;
	$substitutionarray['lieu_modalite']                        = $legaldisplay->collective_agreement->location_and_access_terms_of_the_agreement;
	$substitutionarray['modalite_information_ap']              = $legaldisplay->participation_agreement->information_procedures;
	$substitutionarray['modalite_access']                      = $legaldisplay->DUER->how_access_to_duer;

	//AJOUTER HORAIRES
}
