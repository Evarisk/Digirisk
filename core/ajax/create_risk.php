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

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
    exit;
}
$risk = new Risk($db);
$riskAssessment = new RiskAssessment($db);
$project = new Project($db);

$numberingModuleName = [
    'riskanalysis/' . $risk->element       => $conf->global->DIGIRISKDOLIBARR_RISK_ADDON,
    'riskanalysis/' . $riskAssessment->element => $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON,
    $project->element                      => $conf->global->PROJECT_ADDON,
    'project/task'                         => $conf->global->PROJECT_TASK_ADDON,
];

list($refRiskMod, $refEvaluationMod, $refProjectMod, $refTaskMod) = saturne_require_objects_mod($numberingModuleName, 'digiriskdolibarr');


$risk->fk_element = $data['fk_element'] ?? 0;
$risk->ref        = $refRiskMod->getNextValue($risk);
$risk->category   = $data['category'];
$risk->description = '';
$risk->status     = 1;
$risk->fk_projet = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;

$result = $risk->create($user);

if ($result > 0) {
    $eval = new RiskAssessment($db);
    $eval->fk_risk = $risk->id;
    $eval->ref = $refEvaluationMod->getNextValue($eval);
    $eval->cotation = $data['cotation'];
    $eval->status = 1;
    $eval->method = $data['method'] ?? 'simple';
    $eval->comment = $data['description'] ?? '';
    $eval->date_riskassessment = dol_now();
    $result2 = $eval->create($user);

    if ($result2 > 0) {
        $tasks = $data['tasks'] ?? [];

        foreach ($tasks as $tasktitle) {
            $task = new Task($db);
            $task->label = $tasktitle;
            $task->description = '';
            $task->status = 1;
            $task->fk_project = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;
            $task->array_options['options_fk_risk'] = $risk->id;
            $task->date_start = dol_now();
            $task->date_end = dol_now() + 86400;
            $task->create($user);
        }

    } else {
        echo json_encode(['error' => $eval->db]);
        exit;
    }

    echo json_encode(['success' => $result2]);
} else {
    echo json_encode(['error' => $risk->db]);
}
