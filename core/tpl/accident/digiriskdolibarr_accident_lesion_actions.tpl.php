<?php

// Action to add line
if ($action == 'addLesion' && $permissiontoadd) {
// Get parameters
    $lesion_localization = GETPOST('lesion_localization');
    $lesion_nature       = GETPOST('lesion_nature');
    $parent_id           = GETPOST('parent_id');
    $error               = 0;

// Initialize object accident line
    $now                             = dol_now();
    $accidentLesion->ref                 = $accidentLesion->getNextNumRef();
    $accidentLesion->date_creation       = $object->db->idate($now);
    $accidentLesion->entity              = $conf->entity;
    $accidentLesion->lesion_localization = $lesion_localization;
    $accidentLesion->lesion_nature       = $lesion_nature;
    $accidentLesion->fk_accident         = $parent_id;

// Check parameters
    if ($lesion_localization < 0) {
        setEventMessages($langs->trans('ErrorFieldNotEmpty', $langs->transnoentitiesnoconv('LesionLocalization')), null, 'errors');
        $error++;
    }

    if ($lesion_nature < 0) {
        setEventMessages($langs->trans('ErrorFieldNotEmpty', $langs->transnoentitiesnoconv('LesionNature')), null, 'errors');
        $error++;
    }

    if ( ! $error) {
        $result = $accidentLesion->create($user, false);
        if ($result > 0) {
// Creation accident lesion OK
            setEventMessages($langs->trans('AddAccidentLesion') . ' ' . $object->ref, array());
            $urltogo = str_replace('__ID__', $result, $backtopage);
            $urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
            header("Location: " . $urltogo);
            exit;
        } else {
// Creation accident lesion KO
            if ( ! empty($accidentLesion->errors)) setEventMessages(null, $accidentLesion->errors, 'errors');
            else setEventMessages($accidentLesion->error, null, 'errors');
        }
    }
}

// Action to update line
if ($action == 'updateLesion' && $permissiontoadd) {
// Get parameters
    $lesion_localization = GETPOST('lesion_localization');
    $lesion_nature       = GETPOST('lesion_nature');
    $parent_id           = GETPOST('parent_id');

    $accidentLesion->fetch($lineid);

// Initialize object accident line
    $accidentLesion->lesion_localization = $lesion_localization;
    $accidentLesion->lesion_nature       = $lesion_nature;
    $accidentLesion->fk_accident         = $parent_id;

    if ( ! $error) {
        $result = $accidentLesion->update($user, false);
        if ($result > 0) {
// Update accident lesion OK
            setEventMessages($langs->trans('UpdateAccidentLesion') . ' ' . $object->ref, array());
            $urltogo = str_replace('__ID__', $result, $backtopage);
            $urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $parent_id, $urltogo); // New method to autoselect project after a New on another form object creation
            header("Location: " . $urltogo);
            exit;
        } else {
// Update accident lesion KO
            if ( ! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
            else setEventMessages($object->error, null, 'errors');
        }
    }
}

// Action to delete line
if ($action == 'deleteLesion' && $permissiontodelete) {
    $accidentLesion->fetch($lineid);
    $result = $accidentLesion->delete($user, false, false);
    if ($result > 0) {
// Deletion accident lesion OK
        setEventMessages($langs->trans('DeleteAccidentLesion') . ' ' . $object->ref, array());
        $urltogo = str_replace('__ID__', $result, $backtopage);
        $urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $parent_id, $urltogo); // New method to autoselect project after a New on another form object creation
        header("Location: " . $urltogo);
        exit;
    } else {
// Deletion accident lesion KO
        if (!empty($object->errors)) {
            setEventMessages('', $object->errors, 'errors');
        } else {
            setEventMessages($object->error, [], 'errors');
        }
    }
}
