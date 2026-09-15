<?php
require_once __DIR__ . '/../../master.inc.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
global $db, $conf;
$login = 'zz_tmp_test_claude';
$pass  = 'Tmp!Test2026#dq';
$u = new User($db);
if ($u->fetch(0, $login) > 0) { echo "existe deja: " . $u->id . "\n"; }
else {
    $u->login = $login; $u->lastname = 'Test'; $u->firstname = 'Temporaire';
    $u->admin = 1; $u->entity = 1; $u->statut = 1;
    $id = $u->create(new User($db), 1);
    if ($id < 0) { echo "ERREUR create: " . $u->error . "\n"; exit(1); }
    $u->fetch($id);
    $r = $u->setPassword(new User($db), $pass, 0, 1, 1);
    echo "cree id=$id setPassword=$r\n";
}
