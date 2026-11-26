<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    core/ajax/riskassessmentdocument_generation.php
 * \ingroup digiriskdolibarr
 * \brief   Sheets of the groupments and workunits generated one request each, for the generation modal of the risk
 *          assessment document (js/modules/riskAssessmentDocumentGeneration.js) when the ZIP archive option is on.
 *
 *          - action=list: the groupments and workunits the archive holds, in the order of the tree
 *          - action=generate&id=X: generates the sheet of one of them, returns its file
 *
 *          The modal then submits the form of the page, whose archive takes these sheets as they are: the long request
 *          that generated every sheet before the archive becomes one short request per element.
 */

// The modal posts the token of the page at each call, then its form: it must stay the same in between
if (!defined('NOTOKENRENEWAL')) {
    define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
    define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
    define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
    define('NOREQUIREAJAX', '1');
}

if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/digiriskdolibarrdocuments/riskassessmentdocument.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

global $conf, $db, $langs, $user;

// The messages are shown as they are by the modal
saturne_load_langs();

// A notice printed by the generation would break the JSON: the answer drops whatever came before it
ob_start();

/**
 * Send the JSON answer of the call and stop
 *
 * @param  array<string, mixed> $data     Data to send, with an error key when the call failed
 * @param  int                  $httpCode HTTP status code
 * @return void
 */
function digiriskdolibarr_riskassessmentdocument_generation_answer(array $data, int $httpCode = 200): void
{
    ob_end_clean();
    top_httphead('application/json');
    http_response_code($httpCode);
    echo json_encode($data);
    exit;
}

if (!isModEnabled('digiriskdolibarr') || empty($user->id) || !$user->hasRight('digiriskdolibarr', 'riskassessmentdocument', 'write')) {
    digiriskdolibarr_riskassessmentdocument_generation_answer(['error' => $langs->transnoentities('NotEnoughPermissions')], 403);
}

$action = GETPOST('action', 'aZ09');

if ($action == 'list') {
    $elements = [];
    if (getDolGlobalInt('DIGIRISKDOLIBARR_GENERATE_ARCHIVE_WITH_DIGIRISKELEMENT_DOCUMENTS')) {
        // The elements the archive holds, those of the current entity, in the order of the tree
        $digiriskElement = new DigiriskElement($db);
        foreach ($digiriskElement->fetchDigiriskElementFlat(0, [], 'current') as $digiriskElementSingle) {
            $elements[] = [
                'id'    => (int) $digiriskElementSingle['object']->id,
                'ref'   => $digiriskElementSingle['object']->ref,
                'label' => $digiriskElementSingle['object']->label,
                'depth' => (int) $digiriskElementSingle['depth'],
            ];
        }
    }

    digiriskdolibarr_riskassessmentdocument_generation_answer(['elements' => $elements]);
}

if ($action == 'generate') {
    $id = GETPOSTINT('id');

    // Only an element the archive holds: validated, out of the bin, of the current entity. getActiveDigiriskElements()
    // would tell it too, but it loads every element with its extrafields, at each call
    $digiriskElement = new DigiriskElement($db);
    $reachableIds    = $digiriskElement->getReachableDigiriskElementIds();
    $isFound         = $id > 0 && is_array($reachableIds) && isset($reachableIds[$id]) && $digiriskElement->fetch($id) > 0;
    $isOfEntity      = $isFound && ((int) $digiriskElement->entity === (int) $conf->entity || (!isModEnabled('multicompany') && empty($digiriskElement->entity)));
    if (!$isOfEntity) {
        digiriskdolibarr_riskassessmentdocument_generation_answer(['error' => $langs->transnoentities('ErrorRecordNotFound')], 404);
    }

    $outputLangs = $langs;
    $newLang     = GETPOST('lang_id', 'aZ09');
    if (getDolGlobalInt('MAIN_MULTILANGS') && dol_strlen($newLang)) {
        $outputLangs = new Translate('', $conf);
        $outputLangs->setDefaultLang($newLang);
    }

    // The generation writes nothing in the session: releasing it lets the user browse in another tab meanwhile
    session_write_close();

    $document = new RiskAssessmentDocument($db);
    $models   = $document->getDigiriskElementDocumentModels();
    $fileName = empty($models) ? '' : $document->generateDigiriskElementDocument($digiriskElement, $models, $outputLangs);
    if (!dol_strlen($fileName)) {
        // The line of the modal names the element already: the cause is what the user needs
        $messages = array_unique(array_filter((array) $document->errors)) ?: [$document->error];
        digiriskdolibarr_riskassessmentdocument_generation_answer(['error' => dol_string_nohtmltag(implode(' ', $messages))], 422);
    }

    $filePath = $digiriskElement->element_type . 'document/' . $digiriskElement->ref . '/' . $fileName;
    digiriskdolibarr_riskassessmentdocument_generation_answer([
        'file' => $fileName,
        'url'  => DOL_URL_ROOT . '/document.php?modulepart=digiriskdolibarr&file=' . urlencode($filePath) . '&entity=' . ((int) $conf->entity),
    ]);
}

digiriskdolibarr_riskassessmentdocument_generation_answer(['error' => $langs->transnoentities('ErrorBadParameters')], 400);
