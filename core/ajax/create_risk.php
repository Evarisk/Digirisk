<?php
// Inclusion de l’environnement Dolibarr
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../class/riskanalysis/riskassessment.class.php';


global $user, $db, $langs, $conf;

header('Content-Type: application/json');

if (!$user->hasRight('digiriskdolibarr', 'risk', 'write')) {
    http_response_code(403);
    echo json_encode(['error' => $langs->trans('PermissionDenied')]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

// Single risk (DigiAI, 'Faire le point' method) or batch of risks (ED 6403 grid)
$risksData = (isset($data['risks']) && is_array($data['risks'])) ? $data['risks'] : [$data];

$risk           = new Risk($db);
$riskAssessment = new RiskAssessment($db);
$project        = new Project($db);

$numberingModuleName = [
    'riskanalysis/' . $risk->element          => $conf->global->DIGIRISKDOLIBARR_RISK_ADDON,
    'riskanalysis/' . $riskAssessment->element => $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON,
    $project->element                         => $conf->global->PROJECT_ADDON,
    'project/task'                            => $conf->global->PROJECT_TASK_ADDON,
];

list($refRiskMod, $refEvaluationMod, $refProjectMod, $refTaskMod) = saturne_require_objects_mod($numberingModuleName, 'digiriskdolibarr');

$error        = 0;
$errorMessage = '';
$nbCreated    = 0;

$db->begin();

foreach ($risksData as $riskData) {
    $risk               = new Risk($db);
    $risk->fk_element   = $riskData['fk_element'] ?? 0;
    $risk->ref          = $refRiskMod->getNextValue($risk);
    $risk->category     = $riskData['category'] ?? 0;
    $risk->sub_category = $riskData['sub_category'] ?? 0;
    $risk->description  = $riskData['description'] ?? '';
    $risk->status       = 1;
    $risk->fk_projet    = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;

    if ($risk->create($user) <= 0) {
        $error++;
        $errorMessage = $risk->errorsToString() ?: $risk->error;
        break;
    }

    $eval                     = new RiskAssessment($db);
    $eval->fk_risk            = $risk->id;
    $eval->ref                = $refEvaluationMod->getNextValue($eval);
    $eval->cotation           = $riskData['cotation'] ?? 0;
    $eval->status             = 1;
    $eval->method             = $riskData['method'] ?? 'simple';
    $eval->comment            = $riskData['description'] ?? '';
    $eval->date_riskassessment = !empty($riskData['riskassessment_date']) ? strtotime($riskData['riskassessment_date']) : dol_now();

    if ($eval->create($user) <= 0) {
        $error++;
        $errorMessage = $eval->errorsToString() ?: $eval->error;
        break;
    }

    foreach (($riskData['tasks'] ?? []) as $taskTitle) {
        $task                                   = new Task($db);
        $task->ref                              = $refTaskMod->getNextValue('', $task);
        $task->label                            = $taskTitle;
        $task->description                      = '';
        $task->status                           = 1;
        $task->fk_project                       = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;
        $task->array_options['options_fk_risk'] = $risk->id;
        $task->date_start                       = dol_now();
        $task->date_end                         = dol_now() + 86400;

        if ($task->create($user) <= 0) {
            $error++;
            $errorMessage = $task->errorsToString() ?: $task->error;
            break 2;
        }
    }

    $nbCreated++;
}

if ($error) {
    $db->rollback();
    echo json_encode(['error' => dol_strlen($errorMessage) > 0 ? $errorMessage : $langs->trans('Error')]);
} else {
    $db->commit();
    echo json_encode(['success' => $nbCreated]);
}
