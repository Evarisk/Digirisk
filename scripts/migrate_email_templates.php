<?php

define('INC_FROM_CRON_SCRIPT', true);

// Load Dolibarr environment
$res = @include __DIR__ . '/../../../master.inc.php';
if (!$res) {
    $res = @include __DIR__ . '/../../../../master.inc.php';
}
if (!$res) {
    die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT . '/core/class/cemailtemplate.class.php';

global $db, $user;

$templates = [
    [
        'module' => 'digiriskdolibarr',
        'type_template' => 'preventionplan',
        'label' => 'PP - Responsable EE',
        'topic' => 'Signature du plan de prévention - Responsable Entreprise Extérieur - __COMPANY_NAME__',
        'content' => 'Bonjour,<br><br>Vous êtes invité à signer le plan de prévention (réf: __PLAN_REF__) concernant __COMPANY_NAME__.<br><br><a href="__SIGNATURE_URL__">Cliquez ici pour signer</a><br><br>Cordialement.<br>__USER_FULLNAME__<br>__USER_EMAIL__<br>__USER_PHONEPRO__<br>__MYCOMPANY_NAME__<br>__MYCOMPANY_FULLADDRESS__',
        'lang' => 'fr_FR'
    ],
    [
        'module' => 'digiriskdolibarr',
        'type_template' => 'preventionplan',
        'label' => 'PP - Intervenant EE',
        'topic' => 'Signature du plan de prévention - Intervenant de la société __COMPANY_NAME__',
        'content' => 'Bonjour,<br><br>En tant qu\'intervenant pour la société __COMPANY_NAME__, vous êtes invité à signer le plan de prévention (réf: __PLAN_REF__).<br><br><a href="__SIGNATURE_URL__">Cliquez ici pour signer</a><br><br>Cordialement.<br>__USER_FULLNAME__<br>__USER_EMAIL__<br>__USER_PHONEPRO__<br>__MYCOMPANY_NAME__<br>__MYCOMPANY_FULLADDRESS__',
        'lang' => 'fr_FR'
    ],
    [
        'module' => 'digiriskdolibarr',
        'type_template' => 'firepermit',
        'label' => 'PF - Responsable EE',
        'topic' => 'Signature du permis de feu - Responsable Entreprise Extérieur - __COMPANY_NAME__',
        'content' => 'Bonjour,<br><br>Vous êtes invité à signer le permis de feu (réf: __PLAN_REF__) concernant __COMPANY_NAME__.<br><br><a href="__SIGNATURE_URL__">Cliquez ici pour signer</a><br><br>Cordialement.<br>__USER_FULLNAME__<br>__USER_EMAIL__<br>__USER_PHONEPRO__<br>__MYCOMPANY_NAME__<br>__MYCOMPANY_FULLADDRESS__',
        'lang' => 'fr_FR'
    ],
    [
        'module' => 'digiriskdolibarr',
        'type_template' => 'firepermit',
        'label' => 'PF - Intervenant EE',
        'topic' => 'Signature du permis de feu - Intervenant de la société __COMPANY_NAME__',
        'content' => 'Bonjour,<br><br>En tant qu\'intervenant pour la société __COMPANY_NAME__, vous êtes invité à signer le permis de feu (réf: __PLAN_REF__).<br><br><a href="__SIGNATURE_URL__">Cliquez ici pour signer</a><br><br>Cordialement.<br>__USER_FULLNAME__<br>__USER_EMAIL__<br>__USER_PHONEPRO__<br>__MYCOMPANY_NAME__<br>__MYCOMPANY_FULLADDRESS__',
        'lang' => 'fr_FR'
    ]
];

$inserted = 0;
foreach ($templates as $tplData) {
    // Check if it already exists
    $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "c_email_templates WHERE type_template = '" . $db->escape($tplData['type_template']) . "' AND label = '" . $db->escape($tplData['label']) . "'";
    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql) > 0) {
        echo "Template '" . $tplData['label'] . "' already exists.\n";
        continue;
    }

    $template = new CEmailTemplate($db);
    $template->module = $tplData['module'];
    $template->type_template = $tplData['type_template'];
    $template->lang = $tplData['lang'];
    $template->private = 0;
    $template->label = $tplData['label'];
    $template->topic = $tplData['topic'];
    $template->content = $tplData['content'];
    $template->joinfiles = 1;
    $template->active = 1;

    $res = $template->create($user);
    if ($res > 0) {
        echo "Inserted template '" . $tplData['label'] . "' with ID " . $res . "\n";
        $inserted++;
    } else {
        echo "Failed to insert template '" . $tplData['label'] . "': " . $template->error . "\n";
    }
}

echo "\nDone. Inserted $inserted templates.\n";
