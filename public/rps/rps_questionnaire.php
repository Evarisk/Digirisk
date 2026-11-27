<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    public/rps/rps_questionnaire.php
 * \ingroup digiriskdolibarr
 * \brief   Public questionnaire on the psychosocial risks (#5324): an employee, without a Dolibarr account, answers
 *          the 26 criteria of the RPS-DU grid of the INRS brochure ED 6403 for his work unit.
 *
 * The page is reached through the link of a session, which carries its secret key
 * (view/digiriskstandard/digiriskstandard_rpsquestionnaire.php). The answer is anonymous: neither name, nor
 * address, nor account is kept, only the work unit, the job category and the levels. A work unit that has got
 * the number of answers the session accepts is no longer offered, a closed session no longer takes any.
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if (!defined('NOLOGIN'))        define('NOLOGIN', '1');
if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', '1');
if (!defined('NOIPCHECK'))      define('NOIPCHECK', '1');
if (!defined('NOBROWSERNOTIF')) define('NOBROWSERNOTIF', '1');

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/digiriskresources.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_riskoverview.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_rpsquestionnaire.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $mysoc;

// Load translation files required by the page: companies for the professional identifier of the header
saturne_load_langs(['companies']);

// Get parameters
$key    = GETPOST('key', 'aZ09');
$action = GETPOST('action', 'aZ09');
$entity = !isModEnabled('multicompany') ? $conf->entity : GETPOSTINT('entity');

if ($entity > 0) {
    $conf->setEntityValues($db, $entity);
    // The company of the header is the one of the entity of the link
    $mysoc->setMysoc($conf);
}

// Nobody is logged in
$user = new User($db);

$hookmanager->initHooks(['rpsquestionnairepublic', 'saturnepublicinterface']);

$session = digiriskRpsSessionFetchByKey($db, $key);
$grid    = digiriskRpsQuestionnaireGrid();
$groups  = digiriskdolibarr_exposuregroup_fetch_all($db);

$pageUrl = $_SERVER['PHP_SELF'] . '?' . http_build_query(['entity' => $conf->entity, 'key' => $key]);

// The work units of the session, in the order of the tree, and whether each one still takes answers
$questionnaireUnits = [];
if ($session !== null) {
    $digiriskElement = new DigiriskElement($db);
    $activeElements  = $digiriskElement->getActiveDigiriskElements();
    $elementNodes    = is_array($activeElements) ? flatten_tree(recurse_tree(0, 0, $activeElements)) : [];
    foreach ($elementNodes as $elementId => $elementNode) {
        if (isset($session['elements'][(int) $elementId])) {
            $questionnaireUnits[(int) $elementId] = [
                'ref'   => $elementNode['object']->ref,
                'label' => $elementNode['object']->label,
                'open'  => digiriskRpsSessionElementOpen($session, (int) $elementId),
            ];
        }
    }
}

// What the page shows: why it is closed, the thanks after an answer, or the questionnaire
$questionnaireState = 'form';
if ($session === null) {
    $questionnaireState = 'invalid';
} elseif ($session['status'] !== DIGIRISK_RPS_SESSION_OPEN) {
    $questionnaireState = 'closed';
} elseif (GETPOSTINT('done')) {
    $questionnaireState = 'done';
} elseif (empty(array_filter(array_column($questionnaireUnits, 'open')))) {
    $questionnaireState = 'full';
}

// Category of the respondent: the one of the session, or the one he picks among the exposure groups
$questionnaireGroupFixed = $session !== null && $session['fk_exposuregroup'] > 0;

// What was typed, shown again when the answer is refused
$postedUnit    = GETPOSTINT('unit');
$postedGroup   = GETPOSTINT('group');
$postedAnswers = GETPOST('q', 'array');
$postedComment = GETPOST('comment', 'nohtml');
$answerErrors  = [];

// The only work unit of a session needs no choice
if (count($questionnaireUnits) == 1) {
    $postedUnit = (int) array_key_first($questionnaireUnits);
}

/*
 * Actions
 */

if ($questionnaireState == 'form' && $action == 'answer') {
    if (!isset($questionnaireUnits[$postedUnit])) {
        $answerErrors[] = $langs->transnoentities('PsychosocialQuestionnaireErrorUnit');
    } elseif (!$questionnaireUnits[$postedUnit]['open']) {
        $answerErrors[] = $langs->transnoentities('PsychosocialQuestionnaireErrorUnitFull', $questionnaireUnits[$postedUnit]['ref'] . ' - ' . $questionnaireUnits[$postedUnit]['label']);
    }

    $groupId = $questionnaireGroupFixed ? $session['fk_exposuregroup'] : (isset($groups[$postedGroup]) ? $postedGroup : 0);

    // Every criterion is answered: the averages of a work unit compare answers on the same questions.
    // The answer is a frequency, from never to always; a criterion that describes a resource reads it backwards
    $answers = [];
    foreach ($grid as $position => $criterion) {
        $frequency = (int) ($postedAnswers[$position] ?? 0);
        if ($frequency < 1 || $frequency > 4) {
            continue;
        }
        $answers[$position] = !empty($criterion['reversed']) ? 5 - $frequency : $frequency;
    }
    if (count($answers) < count($grid)) {
        $answerErrors[] = $langs->transnoentities('PsychosocialQuestionnaireErrorIncomplete', count($grid) - count($answers));
    }

    if (empty($answerErrors)) {
        $result = digiriskRpsResponseAdd($db, $session, $postedUnit, $groupId, $answers, (string) $postedComment);
        if ($result > 0) {
            header('Location: ' . $pageUrl . '&done=1');
            exit;
        }
        if ($result == -2) {
            $answerErrors[] = $langs->transnoentities('PsychosocialQuestionnaireErrorUnitFull', $questionnaireUnits[$postedUnit]['ref'] . ' - ' . $questionnaireUnits[$postedUnit]['label']);
            $questionnaireUnits[$postedUnit]['open'] = false;
        } else {
            $answerErrors[] = $langs->transnoentities('PsychosocialQuestionnaireErrorSave');
        }
    }
}

// Logo and name of the company in the header
$questionnaireCompany = digiriskRiskOverviewCompany($db);

// The four answers of the printed grid, in its order
$questionnaireFrequencies = [
    1 => $langs->trans('PsychosocialGridAnswerNever'),
    2 => $langs->trans('PsychosocialGridAnswerSometimes'),
    3 => $langs->trans('PsychosocialGridAnswerOften'),
    4 => $langs->trans('PsychosocialGridAnswerAlways'),
];

// Frequency picked for each criterion, from the answer refused
$questionnairePicked = [];
foreach ($grid as $position => $criterion) {
    $questionnairePicked[$position] = (int) ($postedAnswers[$position] ?? 0);
}

/*
 * View
 */

$title = $langs->trans('PsychosocialQuestionnairePublicTitle');

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

if ($questionnaireState == 'invalid') {
    http_response_code(403);
}

saturne_header(0, '', $title, '', '', 0, 0, [], [], '', 'page-public-card digirisk-rps-questionnaire-public');

require __DIR__ . '/../../core/tpl/riskanalysis/rpsquestionnaire/digiriskdolibarr_rpsquestionnaire_public.tpl.php';

llxFooter('', 'public');
$db->close();
