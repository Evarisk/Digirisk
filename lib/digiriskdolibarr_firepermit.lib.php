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
 * \file    lib/digiriskdolibarr_firepermit.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Library files with common functions for fire permit
 */

/**
 * Prepare fire permit pages header
 *
 * @param  FirePermit $object Fire permit
 * @return array      $head   Array of tabs
 * @throws Exception
 */
function firepermit_prepare_head(FirePermit $object): array
{
    // Global variables definitions
    global $conf, $langs;

    // Load translation files required by the page
    saturne_load_langs();

    // Initialize values
    $head = [];

    $head[1][0] = dol_buildpath('/saturne/view/saturne_schedules.php', 1) . '?id=' . $object->id . '&element_type=firepermit&module_name=DigiriskDolibarr';
    $head[1][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-calendar-times pictofixedwidth"></i> ' . $langs->trans('Schedules') : '<i class="fas fa-calendar-times"></i>';
    $head[1][2] = 'schedules';

    $moreParams['documentType'] = 'FirePermitDocument';

    return saturne_object_prepare_head($object, $head, $moreParams, true);
}

/**
 * Regenere le document PDF d'un permis de feu et le remet a disposition de la diffusion.
 *
 * Le PDF n'a pas de cycle de vie propre : il doit exister des la creation du permis et refleter en
 * permanence son contenu, sans quoi la diffusion presente a des gens qui n'ont aucun moyen de s'en
 * apercevoir soit rien du tout, soit une version datee. Toutes les etapes qui changent le permis
 * (creation, modification, validation, signature) passent donc par ici.
 *
 * Remplace la version precedente au lieu de s'empiler avec elle : la page publique affiche tous les
 * fichiers partages, deux PDF y seraient illisibles.
 *
 * @param  DoliDB    $db       Base de donnees
 * @param  int       $permitId Identifiant du permis de feu
 * @param  User      $user     Utilisateur a l'origine de l'action
 * @param  Translate $langs    Objet de traduction
 * @param  bool      $force    Regenerer meme si le permis l'a deja ete dans cette requete
 * @return int                 < 0 si KO, 0 si rien a faire, 1 si OK
 */
function digiriskRefreshFirePermitDocument(DoliDB $db, int $permitId, User $user, Translate $langs, bool $force = false): int
{
    global $conf;

    // Une meme requete enchaine plusieurs etapes qui changent le permis (validation puis signature
    // par exemple) : sans ce garde-fou le PDF serait genere autant de fois, pour un seul resultat
    // utile. Les appels explicites, eux, arrivent en fin de traitement et doivent primer.
    static $alreadyRefreshed = [];
    if (!$force && isset($alreadyRefreshed[$permitId])) {
        return 0;
    }

    require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';
    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
    require_once __DIR__ . '/../../saturne/lib/documents.lib.php';
    dol_include_once('/digiriskdolibarr/class/firepermit.class.php');
    dol_include_once('/digiriskdolibarr/class/digiriskdolibarrdocuments/firepermitdocument.class.php');
    // digiriskShareGeneratedFile() : helper commun aux objets diffuses, defini avec le plan de prevention
    dol_include_once('/digiriskdolibarr/lib/digiriskdolibarr_preventionplan.lib.php');

    $permit = new FirePermit($db);
    if ($permit->fetch($permitId) <= 0) {
        return -1;
    }

    // Une reference provisoire ne survit pas a la validation : le document genere ici porterait ce
    // nom et resterait dans un repertoire "(PROVxx)" que plus rien ne nettoie, a cote de celui
    // genere avec la reference definitive, et la diffusion presenterait deux apercus.
    if (empty($permit->ref) || preg_match('/^\(?PROV/i', $permit->ref)) {
        return 0;
    }

    $alreadyRefreshed[$permitId] = 1;

    // La page publique de signature tourne dans la langue de son visiteur : quand l'entite a une
    // langue fixee, le document la garde, sinon la signature d'un intervenant etranger regenererait
    // le permis dans sa langue a lui, pour tout le monde.
    $outputLangs = $langs;
    $defaultLang = getDolGlobalString('MAIN_LANG_DEFAULT');
    if (!empty($defaultLang) && $defaultLang != 'auto' && $defaultLang != $langs->defaultlang) {
        $outputLangs = new Translate('', $conf);
        $outputLangs->setDefaultLang($defaultLang);
    }

    $documentDir = $permit->element . 'document/' . dol_sanitizeFileName($permit->ref);
    // Le chemin indexe dans llx_ecm_files est relatif a DOL_DATA_ROOT, et le multicompany intercale
    // le numero d'entite : le deduire de dir_output plutot que de le coder en dur, sinon on cible le
    // repertoire d'une autre entite
    $relativeDir = trim(str_replace(DOL_DATA_ROOT, '', $conf->digiriskdolibarr->dir_output), '/') . '/' . $documentDir;

    // Le permis de feu n'a qu'un generateur ODT : la constante de configuration ne porte que le
    // prefixe du modele, la cle attendue par la generation y ajoute le template a utiliser. On la
    // resout comme le fait la page de generation, sinon aucun generateur n'est trouve.
    $model      = '';
    $modelLists = saturne_get_list_of_models($db, $permit->element . 'document');
    if (is_array($modelLists) && !empty($modelLists)) {
        asort($modelLists);
        $modelLists   = array_filter($modelLists, 'saturne_remove_index');
        $defaultModel = getDolGlobalString('DIGIRISKDOLIBARR_FIREPERMITDOCUMENT_DEFAULT_MODEL');
        foreach ($modelLists as $modelKey => $modelLabel) {
            if (dol_strlen($defaultModel) && strpos($modelKey, $defaultModel) !== false) {
                $model = $modelKey;
            }
        }
        if (!dol_strlen($model)) {
            $model = (string) key($modelLists);
        }
    }
    $model = str_replace($permit->element . 'document_custom_odt', $permit->element . 'document_odt', $model);

    if (!dol_strlen($model)) {
        dol_syslog('digiriskRefreshFirePermitDocument : aucun modele de document disponible', LOG_WARNING);

        return -1;
    }

    // On genere avant de supprimer : une generation en echec laisserait sinon le permis sans aucun
    // document, alors que la diffusion est deja en ligne.
    $document   = new FirePermitDocument($db);
    $moreParams = ['object' => $permit, 'user' => $user, 'objectType' => $permit->element, 'zone' => 'private', 'specimen' => 0];

    // FirePermitDocumentFillJSON() lit l'identifiant du permis dans GETPOST('id') : en contexte AJAX
    // (creation mobile) le parametre est absent, on l'injecte pour que la generation resolve bien le
    // permis et ses lignes.
    $savedGetId = $_GET['id'] ?? null;
    $_GET['id'] = $permitId;

    ob_start();
    $generated   = $document->generateDocument($model, $outputLangs, 0, 0, 0, $moreParams);
    $strayOutput = ob_get_clean();

    if ($savedGetId === null) {
        unset($_GET['id']);
    } else {
        $_GET['id'] = $savedGetId;
    }

    if (dol_strlen($strayOutput)) {
        dol_syslog('digiriskRefreshFirePermitDocument : sortie parasite de la generation : ' . dol_trunc($strayOutput, 500), LOG_WARNING);
    }

    if ($generated <= 0) {
        dol_syslog('digiriskRefreshFirePermitDocument : ' . $document->error, LOG_WARNING);

        return -1;
    }

    $newFileName = basename($document->last_main_doc);

    // Anciennes generations : on ne touche qu'au repertoire du modele, jamais aux pieces jointes
    // deposees a la main sur le permis. Requete directe plutot que le filtre universel de fetchAll :
    // le chemin porte la reference du permis, dont une parenthese casserait l'analyse du filtre.
    $sql  = 'SELECT rowid, filename FROM ' . MAIN_DB_PREFIX . 'ecm_files';
    $sql .= " WHERE filepath = '" . $db->escape($relativeDir) . "'";
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

    // Rattachement au permis + cle de partage + favori : les trois conditions pour que la page
    // publique trouve le document et l'affiche en apercu
    if (digiriskShareGeneratedFile($db, $newFileName, $permit->table_element, $permit->id, $user, true) < 0) {
        dol_syslog('digiriskRefreshFirePermitDocument : partage impossible pour ' . $newFileName, LOG_WARNING);

        return -1;
    }

    return 1;
}

/**
 * Lien de signature d'un signataire de permis de feu.
 *
 * Le meme lien sert au mail, au QR code de l'interface mobile et au bouton qui fait signer
 * l'entreprise exterieure sur le telephone : il n'a qu'une definition.
 *
 * @param  SaturneSignature $signatory Signataire du permis
 * @return string                      URL absolue de la page publique de signature
 */
function digiriskGetFirePermitSignatureUrl(SaturneSignature $signatory): string
{
    global $conf;

    if (empty($signatory->signature_url)) {
        return '';
    }

    return dol_buildpath('/custom/saturne/public/signature/add_signature.php?track_id=' . $signatory->signature_url . '&entity=' . $conf->entity . '&module_name=digiriskdolibarr&object_type=firepermit&document_type=FirePermitDocument', 3);
}

/**
 * Envoie a un signataire le lien de signature du permis de feu.
 *
 * Rend compte de ce qui s'est passe au lieu de le passer sous silence : l'interface mobile affiche
 * a qui le mail est parti, ou pourquoi il n'est pas parti, et propose de le renvoyer.
 *
 * @param  DoliDB           $db          Base de donnees
 * @param  FirePermit       $permit      Permis de feu concerne
 * @param  SaturneSignature $signatory   Signataire destinataire
 * @param  string           $societyName Raison sociale affichee dans le message
 * @param  User             $user        Utilisateur a l'origine de l'action
 * @param  Translate        $langs       Objet de traduction
 * @return array                         ['sent' => bool, 'error' => string, 'email' => string]
 */
function digiriskSendFirePermitSignatureEmail(DoliDB $db, FirePermit $permit, SaturneSignature $signatory, string $societyName, User $user, Translate $langs): array
{
    global $conf;

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

    $signatureUrl = digiriskGetFirePermitSignatureUrl($signatory);
    $subject      = $langs->transnoentities('MobileFPSignatureEmailSubject', $permit->ref);
    $message      = $langs->transnoentities('MobileFPSignatureEmailContent', $societyName, $signatureUrl);

    // Modele de mail configure pour le role du destinataire, quand il y en a un
    $templateId = 0;
    if ($signatory->role === 'ExtSocietyResponsible') {
        $templateId = getDolGlobalInt('DIGIRISKDOLIBARR_FIREPERMIT_EMAIL_TEMPLATE_EXT');
    } elseif ($signatory->role === 'ExtSocietyAttendant') {
        $templateId = getDolGlobalInt('DIGIRISKDOLIBARR_FIREPERMIT_EMAIL_TEMPLATE_INT');
    }

    $filepath = [];
    $mimetype = [];
    $filename = [];

    if ($templateId > 0) {
        require_once DOL_DOCUMENT_ROOT . '/core/class/cemailtemplate.class.php';
        $emailTemplate = new CEmailTemplate($db);
        if ($emailTemplate->fetch($templateId) > 0) {
            // Piece jointe demandee : le document est regenere puis le dernier PDF du permis est joint
            if ($emailTemplate->joinfiles == 1) {
                digiriskRefreshFirePermitDocument($db, (int) $permit->id, $user, $langs);

                $dir       = $conf->digiriskdolibarr->dir_output . '/' . $permit->element . 'document/' . dol_sanitizeFileName($permit->ref);
                $fileArray = dol_dir_list($dir, 'files', 0, '\.pdf$', 'date', 'DESC');
                if (!empty($fileArray)) {
                    $filepath[] = $dir . '/' . $fileArray[0]['name'];
                    $mimetype[] = 'application/pdf';
                    $filename[] = $fileArray[0]['name'];
                }
            }

            $myCompanyName        = getDolGlobalString('MAIN_INFO_SOCIETE_NOM');
            $myCompanyFullAddress = trim(getDolGlobalString('MAIN_INFO_SOCIETE_ADDRESS') . ' ' . getDolGlobalString('MAIN_INFO_SOCIETE_ZIP') . ' ' . getDolGlobalString('MAIN_INFO_SOCIETE_TOWN'));

            $subject = str_replace(
                ['__PLAN_REF__', '__COMPANY_NAME__'],
                [$permit->ref, $societyName],
                $emailTemplate->topic
            );
            $message = str_replace(
                [
                    '__PLAN_REF__', '__COMPANY_NAME__', '__SIGNATURE_URL__',
                    '__USER_FULLNAME__', '__USER_EMAIL__', '__USER_PHONEPRO__',
                    '__MYCOMPANY_NAME__', '__MYCOMPANY_FULLADDRESS__'
                ],
                [
                    $permit->ref, $societyName, $signatureUrl,
                    $user->getFullName($langs), $user->email, $user->office_phone,
                    $myCompanyName, $myCompanyFullAddress
                ],
                $emailTemplate->content
            );
        }
    }

    $mailfile = new CMailFile($subject, $signatory->email, $from, $message, $filepath, $mimetype, $filename, '', '', 0, -1, '', '', '', '', 'mail');
    if ($mailfile->error) {
        $result['error'] = $mailfile->error;

        return $result;
    }

    if (!$mailfile->sendfile()) {
        $result['error'] = $mailfile->error ?: $langs->trans('MobilePPWarningEmailNotSent');

        return $result;
    }

    $signatory->last_email_sent_date = dol_now();
    $db->query('UPDATE ' . MAIN_DB_PREFIX . "saturne_object_signature SET last_email_sent_date = '" . $db->idate($signatory->last_email_sent_date) . "' WHERE rowid = " . (int) $signatory->id);
    $signatory->setPending($user, true);

    // Trace du mail dans l'agenda, comme pour un envoi fait depuis la fiche
    require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
    $actioncomm                = new ActionComm($db);
    $actioncomm->type_code     = 'AC_EMAIL';
    $actioncomm->code          = 'AC_EMAIL';
    $actioncomm->label         = $subject;
    $actioncomm->note_private  = $message;
    $actioncomm->fk_project    = $permit->fk_project;
    $actioncomm->datep         = dol_now();
    $actioncomm->datef         = dol_now();
    $actioncomm->percentage    = -1;
    $actioncomm->socid         = (isset($signatory->fk_soc) && $signatory->fk_soc > 0) ? $signatory->fk_soc : 0;
    $actioncomm->contactid     = $signatory->fk_object;
    $actioncomm->authorid      = $user->id;
    $actioncomm->userownerid   = $user->id;
    $actioncomm->email_from    = $from;
    $actioncomm->email_to      = $signatory->email;
    $actioncomm->email_subject = $subject;
    $actioncomm->email_msgid   = '';
    $actioncomm->fk_element    = $permit->id;
    $actioncomm->elementtype   = 'firepermit@digiriskdolibarr';
    $actioncomm->create($user);

    $result['sent'] = true;

    return $result;
}
