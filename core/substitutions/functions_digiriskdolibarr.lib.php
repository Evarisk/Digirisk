<?php
/* Copyright (C) 2021-2025 EVARISK <technique@evarisk.com>
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
 * or see https://www.gnu.org/
 */

/**
 * \file    core/substitutions/functions_digiriskdolibarr.lib.php
 * \ingroup digiriskdolibarr
 * \brief   File of functions to substitutions array
 */

/** Function called to complete substitution array (before generating on ODT, or a personalized email)
 * functions xxx_completesubstitutionarray are called by make_substitutions() if file
 * is inside directory htdocs/core/substitutions
 *
 * @param  array         $substitutionarray Array with substitution key => val
 * @param  Translate     $outputLangs       Output langs
 * @param  ?CommonObject $object            Object to use to get values
 * @return void                             The entry parameter $substitutionarray is modified
 */
function digiriskdolibarr_completesubstitutionarray(array &$substitutionarray, Translate $outputLangs, ?CommonObject $object): void
{
    if (!is_object($object)) {
        return;
    }

    $type = $object->element;
    $element_type = $object->element_type;

    switch ($type) {
        case 'legaldisplay@digiriskdolibarr':
            $legaldisplay = json_decode($object->json, false, 512, JSON_UNESCAPED_UNICODE)->LegalDisplay;
            $substitutionarray['service_de_sante_nom']         = $legaldisplay->occupational_health_service->name;
            $substitutionarray['service_de_sante_adresse']     = $legaldisplay->occupational_health_service->address;
            $substitutionarray['service_de_sante_code_postal'] = $legaldisplay->occupational_health_service->zip;
            $substitutionarray['service_de_sante_ville']       = $legaldisplay->occupational_health_service->town;
            $substitutionarray['service_de_sante_telephone']   = $legaldisplay->occupational_health_service->phone;

            $substitutionarray['inspection_du_travail_nom']         = $legaldisplay->detective_work->name;
            $substitutionarray['inspection_du_travail_adresse']     = $legaldisplay->detective_work->address;
            $substitutionarray['inspection_du_travail_code_postal'] = $legaldisplay->detective_work->zip;
            $substitutionarray['inspection_du_travail_ville']       = $legaldisplay->detective_work->town;
            $substitutionarray['inspection_du_travail_telephone']   = $legaldisplay->detective_work->phone;

            $substitutionarray['samu']                 = $legaldisplay->emergency_service->samu;
            $substitutionarray['pompier']              = $legaldisplay->emergency_service->pompier;
            $substitutionarray['police']               = $legaldisplay->emergency_service->police;
            $substitutionarray['toute_urgence']        = $legaldisplay->emergency_service->emergency;
            $substitutionarray['defenseur_des_droits'] = $legaldisplay->emergency_service->right_defender;
            $substitutionarray['anti_poison']          = $legaldisplay->emergency_service->poison_control_center;

            $substitutionarray['responsable_a_prevenir']               = $legaldisplay->safety_rule->responsible_for_preventing;
            $substitutionarray['telephone']                            = $legaldisplay->safety_rule->phone;
            $substitutionarray['emplacement_des_consignes_detaillees'] = dol_htmlentitiesbr_decode(strip_tags($legaldisplay->safety_rule->location_of_detailed_instruction, '<br>'));
            $substitutionarray['permanente']                           = dol_htmlentitiesbr_decode(strip_tags($legaldisplay->derogation_schedule->permanent, '<br>'));
            $substitutionarray['occasionnelle']                        = dol_htmlentitiesbr_decode(strip_tags($legaldisplay->derogation_schedule->occasional, '<br>'));
            $substitutionarray['intitule']                             = $legaldisplay->collective_agreement->title_of_the_applicable_collective_agreement;
            $substitutionarray['lieu_modalite']                        = dol_htmlentitiesbr_decode(strip_tags($legaldisplay->collective_agreement->location_and_access_terms_of_the_agreement, '<br>'));
            $substitutionarray['modalite_information_ap']              = dol_htmlentitiesbr_decode(strip_tags($legaldisplay->participation_agreement->information_procedures, '<br>'));
            $substitutionarray['modalite_access']                      = dol_htmlentitiesbr_decode(strip_tags($legaldisplay->DUER->how_access_to_duer, '<br>'));
            $substitutionarray['rules_location']                       = dol_htmlentitiesbr_decode(strip_tags($legaldisplay->rules->location, '<br>'));

            $substitutionarray['lundi_matin']    = $legaldisplay->working_hour->monday_morning;
            $substitutionarray['lundi_aprem']    = $legaldisplay->working_hour->monday_afternoon;
            $substitutionarray['mardi_matin']    = $legaldisplay->working_hour->tuesday_morning;
            $substitutionarray['mardi_aprem']    = $legaldisplay->working_hour->tuesday_afternoon;
            $substitutionarray['mercredi_matin'] = $legaldisplay->working_hour->wednesday_morning;
            $substitutionarray['mercredi_aprem'] = $legaldisplay->working_hour->wednesday_afternoon;
            $substitutionarray['jeudi_matin']    = $legaldisplay->working_hour->thursday_morning;
            $substitutionarray['jeudi_aprem']    = $legaldisplay->working_hour->thursday_afternoon;
            $substitutionarray['vendredi_matin'] = $legaldisplay->working_hour->friday_morning;
            $substitutionarray['vendredi_aprem'] = $legaldisplay->working_hour->friday_afternoon;
            $substitutionarray['samedi_matin']   = $legaldisplay->working_hour->saturday_morning;
            $substitutionarray['samedi_aprem']   = $legaldisplay->working_hour->saturday_afternoon;
            $substitutionarray['dimanche_matin'] = $legaldisplay->working_hour->sunday_morning;
            $substitutionarray['dimanche_aprem'] = $legaldisplay->working_hour->sunday_afternoon;

            $substitutionarray['labour_mon_morning']   = $legaldisplay->occupational_health_service->monday_morning;
            $substitutionarray['labour_mon_afternoon'] = $legaldisplay->occupational_health_service->monday_afternoon;
            $substitutionarray['labour_tue_morning']   = $legaldisplay->occupational_health_service->tuesday_morning;
            $substitutionarray['labour_tue_afternoon'] = $legaldisplay->occupational_health_service->tuesday_afternoon;
            $substitutionarray['labour_wed_morning']   = $legaldisplay->occupational_health_service->wednesday_morning;
            $substitutionarray['labour_wed_afternoon'] = $legaldisplay->occupational_health_service->wednesday_afternoon;
            $substitutionarray['labour_thu_morning']   = $legaldisplay->occupational_health_service->thursday_morning;
            $substitutionarray['labour_thu_afternoon'] = $legaldisplay->occupational_health_service->thursday_afternoon;
            $substitutionarray['labour_fri_morning']   = $legaldisplay->occupational_health_service->friday_morning;
            $substitutionarray['labour_fri_afternoon'] = $legaldisplay->occupational_health_service->friday_afternoon;
            $substitutionarray['labour_sat_morning']   = $legaldisplay->occupational_health_service->saturday_morning;
            $substitutionarray['labour_sat_afternoon'] = $legaldisplay->occupational_health_service->saturday_afternoon;
            $substitutionarray['labour_sun_morning']   = $legaldisplay->occupational_health_service->sunday_morning;
            $substitutionarray['labour_sun_afternoon'] = $legaldisplay->occupational_health_service->sunday_afternoon;

            $substitutionarray['ins_mon_morning']   = $legaldisplay->detective_work->monday_morning;
            $substitutionarray['ins_mon_afternoon'] = $legaldisplay->detective_work->monday_afternoon;
            $substitutionarray['ins_tue_morning']   = $legaldisplay->detective_work->tuesday_morning;
            $substitutionarray['ins_tue_afternoon'] = $legaldisplay->detective_work->tuesday_afternoon;
            $substitutionarray['ins_wed_morning']   = $legaldisplay->detective_work->wednesday_morning;
            $substitutionarray['ins_wed_afternoon'] = $legaldisplay->detective_work->wednesday_afternoon;
            $substitutionarray['ins_thu_morning']   = $legaldisplay->detective_work->thursday_morning;
            $substitutionarray['ins_thu_afternoon'] = $legaldisplay->detective_work->thursday_afternoon;
            $substitutionarray['ins_fri_morning']   = $legaldisplay->detective_work->friday_morning;
            $substitutionarray['ins_fri_afternoon'] = $legaldisplay->detective_work->friday_afternoon;
            $substitutionarray['ins_sat_morning']   = $legaldisplay->detective_work->saturday_morning;
            $substitutionarray['ins_sat_afternoon'] = $legaldisplay->detective_work->saturday_afternoon;
            $substitutionarray['ins_sun_morning']   = $legaldisplay->detective_work->sunday_morning;
            $substitutionarray['ins_sun_afternoon'] = $legaldisplay->detective_work->sunday_afternoon;
            break;

        case 'informationssharing@digiriskdolibarr':
            $informationssharing = json_decode($object->json, false, 512, JSON_UNESCAPED_UNICODE)->InformationsSharing;
            $substitutionarray['service_de_sante_nom']       = $informationssharing->occupational_health_service->name;
            $substitutionarray['service_de_sante_telephone'] = $informationssharing->occupational_health_service->phone;

            $substitutionarray['inspection_du_travail_nom']       = $informationssharing->detective_work->name;
            $substitutionarray['inspection_du_travail_telephone'] = $informationssharing->detective_work->phone;

            $substitutionarray['referant_harcelement_250_nom']       = $informationssharing->harassment_officer->name;
            $substitutionarray['referant_harcelement_250_telephone'] = $informationssharing->harassment_officer->phone;
            $substitutionarray['referant_harcelement_nom']           = $informationssharing->harassment_officer_cse->name;
            $substitutionarray['referant_harcelement_telephone']     = $informationssharing->harassment_officer_cse->phone;

            $substitutionarray['membres_du_comite_entreprise_date']       = dol_print_date($informationssharing->membres_du_comite_entreprise_date, 'day');
            $substitutionarray['membres_du_comite_entreprise_titulaires'] = dol_htmlentitiesbr_decode(strip_tags($informationssharing->membres_du_comite_entreprise_titulaires, '<br>'));
            $substitutionarray['membres_du_comite_entreprise_suppleants'] = dol_htmlentitiesbr_decode(strip_tags($informationssharing->membres_du_comite_entreprise_suppleants, '<br>'));

            $substitutionarray['delegues_du_personnels_date']       = dol_print_date($informationssharing->delegues_du_personnels_date, 'day');
            $substitutionarray['delegues_du_personnels_titulaires'] = dol_htmlentitiesbr_decode(strip_tags($informationssharing->delegues_du_personnels_titulaires, '<br>'));
            $substitutionarray['delegues_du_personnels_suppleants'] = dol_htmlentitiesbr_decode(strip_tags($informationssharing->delegues_du_personnels_suppleants, '<br>'));
            break;

        case 'preventionplandocument@digiriskdolibarr':
            $preventionplan = json_decode($object->json, false, 512, JSON_UNESCAPED_UNICODE)->PreventionPlan;
            $substitutionarray['moyen_generaux_mis_disposition'] = dol_htmlentitiesbr_decode(strip_tags($preventionplan->moyen_generaux_mis_disposition, '<br>'));
            $substitutionarray['consigne_generale']              = dol_htmlentitiesbr_decode(strip_tags($preventionplan->consigne_generale, '<br>'));
            $substitutionarray['premiers_secours']               = dol_htmlentitiesbr_decode(strip_tags($preventionplan->premiers_secours, '<br>'));
            break;

        case 'firepermitdocument@digiriskdolibarr':
            $firepermit = json_decode($object->json, false, 512, JSON_UNESCAPED_UNICODE)->FirePermit;
            $substitutionarray['moyen_generaux_mis_disposition'] = dol_htmlentitiesbr_decode(strip_tags($firepermit->moyen_generaux_mis_disposition, '<br>'));
            $substitutionarray['consigne_generale']              = dol_htmlentitiesbr_decode(strip_tags($firepermit->consigne_generale, '<br>'));
            $substitutionarray['premiers_secours']               = dol_htmlentitiesbr_decode(strip_tags($firepermit->premiers_secours, '<br>'));
            break;

        case 'auditreportdocument@digiriskdolibarr':
        case 'riskassessmentdocument@digiriskdolibarr':
            $riskassessmentdocument = json_decode($object->json, false, 512, JSON_UNESCAPED_UNICODE)->RiskAssessmentDocument;
            $substitutionarray['nomEntreprise']      = $riskassessmentdocument->nomEntreprise;
            $substitutionarray['dateAudit']          = $riskassessmentdocument->dateAudit;
            $substitutionarray['emetteurDUER']       = $riskassessmentdocument->emetteurDUER;
            $substitutionarray['dateGeneration']     = $riskassessmentdocument->dateGeneration;
            $substitutionarray['destinataireDUER']   = $riskassessmentdocument->destinataireDUER;
            $substitutionarray['telephone']          = $riskassessmentdocument->telephone;
            $substitutionarray['portable']           = $riskassessmentdocument->portable;
            $substitutionarray['methodologie']       = dol_htmlentitiesbr_decode(strip_tags($riskassessmentdocument->methodologie, '<br>'));
            $substitutionarray['sources']            = dol_htmlentitiesbr_decode(strip_tags($riskassessmentdocument->sources, '<br>'));
            $substitutionarray['remarqueImportante'] = dol_htmlentitiesbr_decode(strip_tags($riskassessmentdocument->remarqueImportante, '<br>'));
            break;
    }

    switch ($element_type) {
        case 'groupment':
        case 'workunit':
            $substitutionarray['reference']   = $object->ref;
            $substitutionarray['nom']         = $object->label;
            $substitutionarray['description'] = $object->description;
            break;
    }
}
