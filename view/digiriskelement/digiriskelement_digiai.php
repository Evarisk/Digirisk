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
 *   	\file       view/digiriskelement/digiriskelement_digiai.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view digiai
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/digiriskstandard.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskelement.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['other']);

// Get parameters
$id          = GETPOST('id', 'int');
$action      = GETPOST('action', 'aZ09');
$subaction   = GETPOST('subaction', 'aZ09');
$massaction  = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$confirm     = GETPOST('confirm', 'alpha');
$cancel      = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'digiaicard'; // To manage different context of search
$backtopage  = GETPOST('backtopage', 'alpha');
$toselect    = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$limit       = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield   = GETPOST('sortfield', 'alpha');
$sortorder   = GETPOST('sortorder', 'alpha');
$fromid      = GETPOST('fromid', 'int'); //element id
$page        = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
$page        = is_numeric($page) ? $page : 0;
$page        = $page == -1 ? 0 : $page;

// Initialize technical objects
$object           = new DigiriskElement($db);
$digiriskstandard = new DigiriskStandard($db);
$riskAssessment   = new RiskAssessment($db);
$extrafields      = new ExtraFields($db);
$usertmp          = new User($db);
$project          = new Project($db);

$hookmanager->initHooks(array('digiaicard', 'digiriskelementview', 'globalcard')); // Note that conf->hooks_modules contains array

// Load Digirisk_element object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

//Permission for digiriskelement_digiai
$permissiontoread   = $user->rights->digiriskdolibarr->digiai->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->digiai->write;
$permissiontodelete = $user->rights->digiriskdolibarr->digiai->delete;

// Security check
saturne_check_access($permissiontoread, $object);

/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }

$parameters = array();
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $digiai, $action); // Note that $action and $digiai may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
    // Selection of new fields
    include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

    // Purge search criteria
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
        foreach ($digiai->fields as $key => $val) {
            $search[$key] = '';
        }
        $toselect             = '';
        $search_array_options = array();
    }
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
        || GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
        $massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
    }

    $error = 0;

    $backtopage = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_digiai.php', 1) . (empty($fromid) ? '?id=' . ($id > 0 ? $id : '__ID__') : '?fromid=' . ($fromid > 0 ? $fromid : '__ID__'));


}

/*
 * View
 */

$form = new Form($db);

$title    = $langs->trans("DigiAI");
$helpUrl  = 'FR:Module_Digirisk#.C3.89valuateurs';

if ($fromid > 0) {
    saturne_header(0,'', $title, $helpUrl);
} else {
    digirisk_header($title, $helpUrl);
}

print '<div id="cardContent" value="">';

if ($object->id > 0) {
    $res = $object->fetch_optionals();

    saturne_get_fiche_head($object, 'elementDigiAI', $title);

    // Object card
    // ------------------------------------------------------------
    if (empty($fromid)) {
        list($morehtmlref, $moreParams) = $object->getBannerTabContent();

        saturne_banner_tab($object,'ref','none', 0, 'ref', 'ref', $morehtmlref, true, $moreParams);
    } else {
        $linkback = '<a href="' . DOL_URL_ROOT . '/user/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

        dol_banner_tab($userObject, 'fromid', $linkback, $user->rights->user->user->lire || $user->admin);
    }

    if ($fromid) {
        print '<div class="underbanner clearboth"></div>';
    }

    print '<div class="fichecenter digiailist wpeo-wrap">';

    // Formulaire pour upload d'image
    print '<form method="POST" enctype="multipart/form-data" action="' . $_SERVER["PHP_SELF"] . '?id='. $id .'" name="upload_image_form" id="upload_image_form">' . "\n";
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="upload_image">';
    print '<input type="hidden" name="id" value="' . $id . '">';

    print '<h2>';
    print $langs->trans('WelcomeToDigiAI');
    print '</h2>';

    // Ajouter un champ pour l'upload d'image
    print '<div class="digiAI-upload">';
    print '<label for="image_file">' . $langs->trans('UploadAnImage') . ':</label>';
    print '<input type="file" name="image_file" id="image_file" accept="image/*">';
    print '<button type="submit" class="digiAI-upload-button">' . $langs->trans('Upload') . '</button>';
    print '</div>';
    print '</form>';

    print '<div class="wpeo-modal" id="digiai_modal">';
    print '<div class="modal-container">';
    print '<div class="modal-header">';
    print '<h2>Analyse de risques par l\'IA</h2>';
    print '</div>';
    print '<div class="modal-content">';
    print '  <table id="risque_table" style="width: 100%; border-collapse: collapse; border: 1px solid #ccc; display:none">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Cotation</th>
                        <th>Description du Risque</th>
                        <th>Actions de Prévention</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>';
    print '</div>';
    print '</div>';
    print '</div>';


    // Ajout de style pour le formulaire d'upload
    print '<style>';
    print '.digiAI-upload {';
    print 'background-color: #f9f9f9;';
    print 'border: 1px solid #ccc;';
    print 'border-radius: 5px;';
    print 'padding: 10px;';
    print 'margin: 10px;';
    print 'display: flex;';
    print 'align-items: center;';
    print '}';
    print '.digiAI-upload-button {';
    print 'background-color: #4CAF50;';
    print 'border: none;';
    print 'color: white;';
    print 'padding: 10px 20px;';
    print 'cursor: pointer;';
    print 'border-radius: 5px;';
    print 'margin-left: 10px;';
    print '}';
    print '</style>';

    ?>
    <script>
        document.getElementById('upload_image_form').addEventListener('submit', async function(e) {
            e.preventDefault();

            console.log($('#image_file').val());

            // Récupération du fichier
            let imageFile = document.getElementById('image_file').files[0];
            if (!imageFile) {
                alert('Veuillez sélectionner une image.');
                return;
            }

            // Affichage de la modal
            $('#digiai_modal').addClass('modal-active');
            let modalContent = $('#digiai_modal .modal-content');
            modalContent.append('<div class="analysis-in-progress"></div>');

            // Création du FormData pour l'image
            let formData = new FormData();
            formData.append('image_file', imageFile);

            try {
                // Etape 1: Upload de l'image à Google Vision
                modalContent.find('.analysis-in-progress').append(`
                <span>En attente de la réponse de Google Vision...</span>
                <div class="loader" style="display:inline-block; width:16px; height:16px; border:2px solid #ccc; border-top:2px solid #4CAF50; border-radius:50%; animation: spin 1s linear infinite;"></div>
                <br>`);

                let visionResponse = await fetch('backend_endpoint_for_google_vision.php', {
                    method: 'POST',
                    body: formData
                });

                if (!visionResponse.ok) {
                    throw new Error('Failed to fetch Google Vision');
                }

                let visionData = await visionResponse.json();
                let description = generateDescriptionFromGoogleVision(visionData);

                // Etape 2: Envoi des résultats de Google Vision à ChatGPT
                modalContent.find('.analysis-in-progress').append(`
                <span>En attente de la réponse de ChatGPT...</span>
                <div class="loader" style="display:inline-block; width:16px; height:16px; border:2px solid #ccc; border-top:2px solid #4CAF50; border-radius:50%; animation: spin 1s linear infinite;"></div>
                <br>`);

                let chatGptResponse = await fetch('backend_endpoint_for_chatgpt.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ description })
                });

                if (!chatGptResponse.ok) {
                    throw new Error('Failed to fetch ChatGPT');
                }

                let chatGptData = await chatGptResponse.json();

                // Suppression des loaders après les étapes
                modalContent.find('.analysis-in-progress').empty();

                // Turn the response into a table
                let table = $('#risque_table');
                table.attr('style', 'width: 100%; border-collapse: collapse; border: 1px solid #ccc; display: table;')
                let tbody = table.find('tbody');
                tbody.empty();

                let risque = JSON.parse(chatGptData.choices[0].message.content);

                // Boucle pour générer les lignes du tableau
                risque.forEach(risque => {
                    let tr = $('<tr class="oddeven">');
                    let title = risque.title;
                    let cotation = risque.cotation;
                    let description = risque.description;
                    let prevention_actions = risque.prevention_actions;

                    let cotationScale = 0;
                    cotation = parseInt(cotation);
                    if (cotation < 48) {
                        cotationScale = 1;
                    } else if (cotation < 51) {
                        cotationScale = 2;
                    } else if (cotation < 80) {
                        cotationScale = 3;
                    } else {
                        cotationScale = 4;
                    }

                    let cotationContainer = $('<div>').addClass('risk-evaluation-cotation').attr('value', cotationScale).attr('data-scale', cotationScale).text(cotation);

                    // Créer un conteneur d'image
                    let riskImgContainer = $('<img>').addClass('danger-category-pic tooltip wpeo-tooltip-event hover')
                        .attr('src', '<?php echo DOL_URL_ROOT; ?>/custom/digiriskdolibarr/img/categorieDangers/' + title + '.png');

                    tr.append($('<td>').append(riskImgContainer));
                    tr.append($('<td>').append(cotationContainer));
                    tr.append($('<td>').text(description));
                    let actions = $('<ul>');
                    prevention_actions.forEach(action => {
                        actions.append($('<li>').text('-' + action));
                    });
                    tr.append($('<td>').append(actions));
                    tbody.append(tr);
                });
            } catch (error) {
                modalContent.find('.analysis-in-progress').empty().append('An error occurred: ' + error.message);
            }
        });

        // Fonction pour générer la description de Google Vision
        function generateDescriptionFromGoogleVision(visionData) {
            let labels = visionData.responses[0].labelAnnotations;
            let localizedObjects = visionData.responses[0].localizedObjectAnnotations;

            let description = "Voici les éléments détectés dans l'image :\n\n";
            labels.forEach(label => {
                description += `- ${label.description} (Confiance: ${(label.score * 100).toFixed(2)}%)\n`;
            });
            localizedObjects.forEach(object => {
                description += `- ${object.name} (Confiance: ${(object.score * 100).toFixed(2)}%)\n`;
            });
            return description;
        }

        // CSS pour l'animation du loader
        const style = document.createElement('style');
        style.innerHTML = `
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    `;
        document.head.appendChild(style);
    </script>
<?php

    print dol_get_fiche_end();
}

print '</div>' . "\n";
print '<!-- End div class="cardcontent" -->';

// End of page
llxFooter();
$db->close();
