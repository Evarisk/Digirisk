<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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

/**
 * \file    lib/digiriskdolibarr_preventionplan.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Library files with common functions for prevention plan
 */

/**
 * Prepare prevention plan pages header
 *
 * @param  PreventionPlan $object Prevention plan
 * @return array          $head   Array of tabs
 * @throws Exception
 */
function preventionplan_prepare_head(PreventionPlan $object): array
{
    // Global variables definitions
    global $conf, $langs;

    // Load translation files required by the page
    saturne_load_langs();

    // Initialize values
    $head = [];

    $head[1][0] = dol_buildpath('/saturne/view/saturne_schedules.php', 1) . '?id=' . $object->id . '&element_type=preventionplan&module_name=DigiriskDolibarr';
    $head[1][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-calendar-times pictofixedwidth"></i>' . $langs->trans('Schedules') : '<i class="fas fa-calendar-times"></i>';
    $head[1][2] = 'schedules';

    $moreParams['documentType'] = 'PreventionPlanDocument';

    return saturne_object_prepare_head($object, $head, $moreParams, true);
}

/**
 * Regenere le document PDF d'un plan de prevention et le remet a disposition de la diffusion.
 *
 * Le PDF n'a pas de cycle de vie propre : il doit exister des la creation du plan et refleter en
 * permanence son contenu, sans quoi la diffusion presente a des gens qui n'ont aucun moyen de s'en
 * apercevoir soit rien du tout, soit une version datee. Toutes les etapes qui changent le plan
 * (creation, modification, validation, signature) passent donc par ici.
 *
 * Remplace la version precedente au lieu de s'empiler avec elle : la page publique affiche tous les
 * fichiers partages, deux PDF y seraient illisibles.
 *
 * @param  DoliDB    $db     Base de donnees
 * @param  int       $planId Identifiant du plan de prevention
 * @param  User      $user   Utilisateur a l'origine de l'action
 * @param  Translate $langs  Objet de traduction
 * @param  bool      $force  Regenerer meme si le plan l'a deja ete dans cette requete
 * @return int               < 0 si KO, 0 si rien a faire, 1 si OK
 */
function digiriskRefreshPreventionPlanDocument(DoliDB $db, int $planId, User $user, Translate $langs, bool $force = false): int
{
    global $conf;

    // Une meme requete enchaine plusieurs etapes qui changent le plan (validation puis signature
    // par exemple) : sans ce garde-fou le PDF serait genere autant de fois, pour un seul resultat
    // utile. Les appels explicites, eux, arrivent en fin de traitement et doivent primer.
    static $alreadyRefreshed = [];
    if (!$force && isset($alreadyRefreshed[$planId])) {
        return 0;
    }

    require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';
    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
    dol_include_once('/digiriskdolibarr/class/preventionplan.class.php');
    dol_include_once('/digiriskdolibarr/class/digiriskdolibarrdocuments/preventionplandocument.class.php');

    $plan = new PreventionPlan($db);
    if ($plan->fetch($planId) <= 0) {
        return -1;
    }

    // Une reference provisoire ne survit pas a la validation : le document genere ici porterait ce
    // nom et resterait dans un repertoire "(PROVxx)" que plus rien ne nettoie, a cote de celui
    // genere avec la reference definitive, et la diffusion presenterait deux apercus. Le plan n'est
    // de toute facon pas diffusable a ce stade : la generation attend la validation.
    // Le marqueur de passage n'est pose qu'ici, pour que la validation qui suit dans la meme
    // requete regenere bien avec la reference definitive.
    if (empty($plan->ref) || preg_match('/^\(?PROV/i', $plan->ref)) {
        return 0;
    }

    $alreadyRefreshed[$planId] = 1;

    // La page publique de signature tourne dans la langue de son visiteur : quand l'entite a une
    // langue fixee, le document la garde, sinon la signature d'un intervenant etranger regenererait
    // le plan dans sa langue a lui, pour tout le monde. Avec MAIN_LANG_DEFAULT a 'auto' il n'y a pas
    // de langue de reference : on ne peut que suivre la requete courante.
    $outputLangs = $langs;
    $defaultLang = getDolGlobalString('MAIN_LANG_DEFAULT');
    if (!empty($defaultLang) && $defaultLang != 'auto' && $defaultLang != $langs->defaultlang) {
        $outputLangs = new Translate('', $conf);
        $outputLangs->setDefaultLang($defaultLang);
    }

    $documentDir = $plan->element . 'document/' . dol_sanitizeFileName($plan->ref);
    // Le chemin indexe dans llx_ecm_files est relatif a DOL_DATA_ROOT, et le multicompany intercale
    // le numero d'entite : le deduire de dir_output plutot que de le coder en dur, sinon on cible le
    // repertoire d'une autre entite
    $relativeDir = trim(str_replace(DOL_DATA_ROOT, '', $conf->digiriskdolibarr->dir_output), '/') . '/' . $documentDir;

    // On genere avant de supprimer : une generation en echec laisserait sinon le plan sans aucun
    // document, alors que la diffusion est deja en ligne.
    // La generation est declenchee depuis des pages qui envoient ensuite une redirection ou du
    // JSON : le moindre avertissement PHP echappe de la chaine ODT/PDF les casserait, il part donc
    // dans le journal plutot que dans la reponse.
    $document   = new PreventionPlanDocument($db);
    $moreParams = ['object' => $plan, 'user' => $user, 'objectType' => $plan->element];

    // PreventionPlanDocumentFillJSON() reads the plan ID via GETPOST('id').
    // When called from an AJAX context (mobile creation), the parameter is absent:
    // inject it so the document generator can resolve the plan.
    $savedGetId = $_GET['id'] ?? null;
    $_GET['id'] = $planId;

    ob_start();
    $generated  = $document->generateDocument('preventionplandocument', $outputLangs, 0, 0, 0, $moreParams);
    $strayOutput = ob_get_clean();

    // Restore the original GET parameter
    if ($savedGetId === null) {
        unset($_GET['id']);
    } else {
        $_GET['id'] = $savedGetId;
    }

    if (dol_strlen($strayOutput)) {
        dol_syslog('digiriskRefreshPreventionPlanDocument : sortie parasite de la generation : ' . dol_trunc($strayOutput, 500), LOG_WARNING);
    }

    if ($generated <= 0) {
        dol_syslog('digiriskRefreshPreventionPlanDocument : ' . $document->error, LOG_WARNING);

        return -1;
    }

    $newFileName = basename($document->last_main_doc);

    // Anciennes generations : on ne touche qu'au repertoire du modele, jamais aux pieces jointes
    // deposees a la main sur le plan.
    // Requete directe plutot que le filtre universel de fetchAll : le chemin porte la reference du
    // plan, dont une parenthese casserait l'analyse du filtre. Le nettoyage echouait alors sans
    // bruit et les generations s'empilaient, la diffusion presentant plusieurs apercus.
    $sql  = 'SELECT rowid, filename FROM ' . MAIN_DB_PREFIX . 'ecm_files';
    $sql .= ' WHERE filepath = \'' . $db->escape($relativeDir) . '\'';
    $sql .= ' AND entity = ' . (int) $conf->entity;

    $resql = $db->query($sql);
    while ($resql && ($previousFile = $db->fetch_object($resql))) {
        if ($previousFile->filename == $newFileName) {
            continue;
        }
        $oldFile = new EcmFiles($db);
        if ($oldFile->fetch($previousFile->rowid) > 0) {
            $oldFile->delete($user);
        }
        // disableglob : le nom porte la raison sociale, dont les crochets seraient pris pour une
        // classe de caracteres et laisseraient le fichier sur le disque
        dol_delete_file($conf->digiriskdolibarr->dir_output . '/' . $documentDir . '/' . $previousFile->filename, 1, 1);
    }

    // Rattachement au plan + cle de partage + favori : les trois conditions pour que la page
    // publique trouve le document et l'affiche en apercu
    if (digiriskShareGeneratedFile($db, $newFileName, $plan->table_element, $plan->id, $user, true) < 0) {
        dol_syslog('digiriskRefreshPreventionPlanDocument : partage impossible pour ' . $newFileName, LOG_WARNING);

        return -1;
    }

    return 1;
}

/**
 * Lien de signature d'un signataire de plan de prevention.
 *
 * Le meme lien sert au mail, au QR code de l'interface mobile et au bouton qui fait signer
 * l'entreprise exterieure sur le telephone : il n'a qu'une definition.
 *
 * @param  SaturneSignature $signatory Signataire du plan
 * @return string                      URL absolue de la page publique de signature
 */
function digiriskGetPreventionPlanSignatureUrl(SaturneSignature $signatory): string
{
    global $conf;

    if (empty($signatory->signature_url)) {
        return '';
    }

    return dol_buildpath('/custom/saturne/public/signature/add_signature.php?track_id=' . $signatory->signature_url . '&entity=' . $conf->entity . '&module_name=digiriskdolibarr&object_type=preventionplan&document_type=PreventionPlanDocument', 3);
}

/**
 * Envoie a un signataire le lien de signature du plan de prevention.
 *
 * Rend compte de ce qui s'est passe au lieu de le passer sous silence : l'interface mobile affiche
 * a qui le mail est parti, ou pourquoi il n'est pas parti, et propose de le renvoyer.
 *
 * @param  DoliDB           $db          Base de donnees
 * @param  PreventionPlan   $plan        Plan de prevention concerne
 * @param  SaturneSignature $signatory   Signataire destinataire
 * @param  string           $societyName Raison sociale affichee dans le message
 * @param  User             $user        Utilisateur a l'origine de l'action
 * @param  Translate        $langs       Objet de traduction
 * @return array                         ['sent' => bool, 'error' => string, 'email' => string]
 */
function digiriskSendPreventionPlanSignatureEmail(DoliDB $db, PreventionPlan $plan, SaturneSignature $signatory, string $societyName, User $user, Translate $langs): array
{
    require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';

    $result = ['sent' => false, 'error' => '', 'email' => (string) $signatory->email];

    if (!isValidEmail($signatory->email)) {
        $result['error'] = $langs->trans('MobilePPWarningNoRecipientEmail');

        return $result;
    }

    $from = getDolGlobalString('MAIN_MAIL_EMAIL_FROM');
    if (!dol_strlen($from)) {
        $result['error'] = $langs->trans('MobilePPWarningEmailNotConfigured');

        return $result;
    }

    if (!dol_strlen(getDolGlobalString('MAIN_MAIL_SMTPS_ID')) && getDolGlobalInt('SATURNE_USE_ALL_EMAIL_MODE') <= 0) {
        $result['error'] = $langs->trans('MobilePPWarningEmailNotConfigured');

        return $result;
    }

    $signatureUrl = digiriskGetPreventionPlanSignatureUrl($signatory);
    $subject      = $langs->transnoentities('MobilePPSignatureEmailSubject', $plan->ref);
    $message      = $langs->transnoentities('MobilePPSignatureEmailContent', $societyName, $signatureUrl);

    $mailfile = new CMailFile($subject, $signatory->email, $from, $message, [], [], [], '', '', 0, -1, '', '', '', '', 'mail');
    if ($mailfile->error) {
        $result['error'] = $mailfile->error;

        return $result;
    }

    if (!$mailfile->sendfile()) {
        $result['error'] = $mailfile->error ?: $langs->trans('MobilePPWarningEmailNotSent');

        return $result;
    }

    $signatory->last_email_sent_date = dol_now();
    $signatory->update($user, true);
    $signatory->setPending($user, true);

    $result['sent'] = true;

    return $result;
}

/**
 * Recale un fichier indexe sur l'objet metier voulu et lui pose une cle de partage.
 *
 * La generation indexe le fichier sur le document Saturne (src_object_type =
 * saturne_object_documents). La page publique de diffusion, elle, cherche les fichiers de l'objet
 * metier et ne sert que ceux qui portent une cle de partage : sans ce recalage le document reste
 * invisible pour les personnes diffusees.
 *
 * @param  DoliDB $db           Base de donnees
 * @param  string $fileName     Nom du fichier indexe
 * @param  string $tableElement Table de l'objet metier a rattacher
 * @param  int    $objectId     Identifiant de l'objet metier
 * @param  User   $user         Utilisateur a l'origine de l'action
 * @param  bool   $favorite     Marquer le fichier comme mis en avant sur la diffusion
 * @return int                  < 0 si KO, 1 si OK
 */
function digiriskShareGeneratedFile(DoliDB $db, string $fileName, string $tableElement, int $objectId, User $user, bool $favorite = false): int
{
    global $conf;

    require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';
    require_once DOL_DOCUMENT_ROOT . '/core/lib/security2.lib.php';

    // Requete directe plutot que le filtre universel de fetchAll : le nom de fichier porte la
    // raison sociale, dont une parenthese ou une apostrophe casserait l'analyse du filtre.
    // L'entite est indispensable, deux entites pouvant heberger un fichier de meme nom.
    $sql  = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . 'ecm_files';
    $sql .= ' WHERE filename = \'' . $db->escape($fileName) . '\'';
    $sql .= ' AND entity = ' . (int) $conf->entity;
    $sql .= ' ORDER BY rowid DESC LIMIT 1';

    $resql = $db->query($sql);
    if (!$resql || $db->num_rows($resql) == 0) {
        return -1;
    }
    $found = $db->fetch_object($resql);

    $ecmFile = new EcmFiles($db);
    if ($ecmFile->fetch($found->rowid) <= 0) {
        return -1;
    }

    $ecmFile->src_object_type = $tableElement;
    $ecmFile->src_object_id   = $objectId;
    if (empty($ecmFile->share)) {
        $ecmFile->share = getRandomPassword(true);
    }
    if ($favorite) {
        // update() enregistre lui-meme les extrafields
        $ecmFile->array_options['options_favorite'] = 1;
    }

    return $ecmFile->update($user) > 0 ? 1 : -1;
}
