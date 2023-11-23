<?php
// ACCIDENT LESION
print '<div class="div-table-responsive-no-min" style="overflow-x: unset !important">';
print load_fiche_titre($langs->trans("AccidentLesionList"), '', '');
print '<table id="tablelines" class="noborder noshadow" width="100%">';

global $forceall, $forcetoshowtitlelines;

if (empty($forceall)) $forceall = 0;

// Define colspan for the button 'Add'
$colspan = 3; // Columns: total ht + col edit + col delete

// Accident Lines
$accidentlines = $accidentLesion->fetchAll('', '', 0, 0, ['customsql' => 't.fk_accident = ' . $object->id]);

print '<tr class="liste_titre">';
print '<td><span>' . $langs->trans('Ref.') . '</span></td>';
print '<td>' . $langs->trans('LesionLocalization') . '</td>';
print '<td>' . $langs->trans('LesionNature') . '</td>';
print '<td class="center" colspan="' . $colspan . '">' . $langs->trans('ActionsLine') . '</td>';
print '</tr>';

if (!empty($accidentlines) && $accidentlines > 0) {
    foreach ($accidentlines as $key => $item) {
        if ($action == 'editLesion' && $lineid == $key) {
            print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="action" value="updateLesion">';
            print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
            print '<input type="hidden" name="lineid" value="' . $item->id . '">';
            print '<input type="hidden" name="parent_id" value="' . $object->id . '">';

            print '<tr>';
            print '<td>';
            print $item->ref;
            print '</td>';

            $coldisplay++;
            //LesionLocalization -- Siège des lésions
            print '<td>';
            print saturne_select_dictionary('lesion_localization', 'c_lesion_localization', 'label', 'label', $item->lesion_localization);
            print '<a href="' . DOL_URL_ROOT . '/admin/dict.php?mainmenu=home" target="_blank" class="wpeo-tooltip-event" aria-label="' . $langs->trans('ConfigDico') . '">' . ' ' . img_picto('', 'globe') . '</a>';
            print '</td>';

            $coldisplay++;
            //LesionNature -- Nature des lésions
            print '<td>';
            print saturne_select_dictionary('lesion_nature', 'c_lesion_nature', 'label', 'label', $item->lesion_nature);
            print '<a href="' . DOL_URL_ROOT . '/admin/dict.php?mainmenu=home" target="_blank" class="wpeo-tooltip-event" aria-label="' . $langs->trans('ConfigDico') . '">' . ' ' . img_picto('', 'globe') . '</a>';
            print '</td>';

            $coldisplay += $colspan;
            print '<td class="center" colspan="' . $colspan . '">';
            print '<input type="submit" class="button" value="' . $langs->trans('Save') . '" name="updateLesion" id="updateLesion">';
            print ' &nbsp; <input type="submit" id ="cancelLesion" class="button" name="cancelLesion" value="' . $langs->trans("Cancel") . '">';
            print '</td>';
            print '</tr>';

            print '</form>';
        } else {
            print '<td>';
            print $item->ref;
            print '</td>';

            $coldisplay++;
            print '<td>';
            print $langs->transnoentities($item->lesion_localization);
            print '</td>';

            $coldisplay++;
            print '<td>';
            print $langs->transnoentities($item->lesion_nature);
            print '</td>';

            $coldisplay += $colspan;

            //Actions buttons
            if ($object->status == 1) {
                print '<td class="center">';
                $coldisplay++;
                print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&amp;action=editLesion&amp;lineid=' . $item->id . '" style="padding-right: 20px"><i class="fas fa-pencil-alt" style="color: #666"></i></a>';
                print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&amp;action=deleteLesion&amp;lineid=' . $item->id . '&token='. newToken(). '">';
                print img_delete();
                print '</a>';
                print '</td>';
            } else {
                print '<td class="center">';
                print '-';
                print '</td>';
            }

            print '</tr>';
        }
    }
    print '</tr>';
}
if ($object->status == $object::STATUS_DRAFT && $permissiontoadd) {
    print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="addLesion">';
    print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    print '<input type="hidden" name="parent_id" value="' . $object->id . '">';

    print '<tr>';
    print '<td>';
    print $accidentLesion->getNextNumRef();
    print '</td>';

    $coldisplay++;
    //LesionLocalization -- Siège des lésions
    print '<td>';
    print saturne_select_dictionary('lesion_localization', 'c_lesion_localization', 'label');
    print '<a href="' . DOL_URL_ROOT . '/admin/dict.php?mainmenu=home" target="_blank" class="wpeo-tooltip-event" aria-label="' . $langs->trans('ConfigDico') . '">' . ' ' . img_picto('', 'globe') . '</a>';
    print '</td>';

    $coldisplay++;
    //LesionNature -- Nature des lésions
    print '<td>';
    print saturne_select_dictionary('lesion_nature', 'c_lesion_nature', 'label');
    print '<a href="' . DOL_URL_ROOT . '/admin/dict.php?mainmenu=home" target="_blank" class="wpeo-tooltip-event" aria-label="' . $langs->trans('ConfigDico') . '">' . ' ' . img_picto('', 'globe') . '</a>';
    print '</td>';

    $coldisplay += $colspan;
    print '<td class="center" colspan="' . $colspan . '">';
    print '<input type="submit" class="button" value="' . $langs->trans('Add') . '" name="addline" id="addline">';
    print '</td>';
    print '</tr>';

    print '</form>';
}
print '</table>';
print '</div>';
