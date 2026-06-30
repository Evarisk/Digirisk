<?php
/**
 * \file    core/tpl/actionplan/actionplan_kanban_card.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Single Kanban card fragment (rendered live or deferred for lazy-load)
 *
 * Variables expected from calling TPL:
 * - $t                      array  Enriched task data (one entry of $tasksJson)
 * - $kanbanThresholds       array  Column threshold config
 * - $allUsers               array  Internal users (responsible / contributor selectors)
 * - $allContacts            array  External contacts (contributor selector)
 * - $allAvailableCategories array  Categories (tag selector)
 * - $langs                  Translate
 */
?>
<div class="kanban-card" data-task-id="<?= $t['id'] ?>" data-progress="<?= $t['progress'] ?>">

    <!-- Header: Ref + Date + Workload + Budget + Risk -->
    <div class="kanban-card-header">
        <a href="<?= $t['url'] ?>" class="kanban-card-ref" target="_blank"><?= dol_escape_htmltag($t['ref']) ?></a>

        <span class="kanban-meta-item kanban-editable-meta" data-field="planned_workload" data-task-id="<?= $t['id'] ?>"
              data-raw="<?= $t['planned_workload'] > 0 ? round($t['planned_workload'] / 3600, 2) : 0 ?>"
              title="<?= dol_escape_htmltag($langs->trans('PlannedWorkload')) ?>">
            <i class="fas fa-clock"></i> <span class="kanban-meta-value"><?= !empty($t['planned_workload_fmt']) ? $t['planned_workload_fmt'] : '-' ?></span>
        </span>
        <span class="kanban-meta-item kanban-editable-meta" data-field="budget" data-task-id="<?= $t['id'] ?>"
              data-raw="<?= $t['budget'] ?>"
              title="<?= dol_escape_htmltag($langs->trans('Budget')) ?>">
            <i class="fas fa-coins"></i> <span class="kanban-meta-value"><?= !empty($t['budget_fmt']) ? $t['budget_fmt'] : '-' ?></span>
        </span>
        <?php if (!empty($t['risk_ref'])) :
            $rd = $t['risk_data'];
            $cotColor = !empty($rd['cotation_color']) ? $rd['cotation_color'] : '#ececec';
            $textColor = in_array($cotColor, ['#2b2b2b', '#e05353']) ? '#fff' : '#333';
            // Build risk URL matching getNomUrl: ?id=fk_element
            $riskUrl = !empty($rd['fk_element']) ? DOL_URL_ROOT . '/custom/digiriskdolibarr/view/digiriskelement/digiriskelement_risk.php?id=' . $rd['fk_element'] : '';
        ?>
            <a href="<?= $riskUrl ?>" class="kanban-card-risk kanban-risk-tooltip-trigger" style="background:<?= $cotColor ?>;color:<?= $textColor ?>" target="_blank">
                <i class="fas fa-exclamation-triangle"></i> <?= dol_escape_htmltag($t['risk_ref']) ?>
                <?php if (!empty($rd)) : ?>
                <div class="kanban-risk-tooltip">
                    <div class="krt-header">
                        <i class="fas fa-exclamation-triangle" style="color:<?= $cotColor ?>"></i>
                        <strong><?= dol_escape_htmltag($rd['ref']) ?></strong>
                        <?php if (!empty($rd['category_name'])) : ?>
                            : <?= dol_escape_htmltag($rd['category_name']) ?>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($rd['ra_ref'])) : ?>
                    <div class="krt-eval">
                        <i class="fas fa-chart-line"></i>
                        <span class="krt-cotation" style="background:<?= $cotColor ?>;color:<?= $textColor ?>"><?= $rd['cotation'] ?></span>
                        <?php if (!empty($rd['ra_photo_url'])) : ?>
                            <img src="<?= $rd['ra_photo_url'] ?>" class="krt-photo" alt="">
                        <?php else : ?>
                            <span class="krt-photo-default"><i class="fas fa-image"></i></span>
                        <?php endif; ?>
                        <span><?= dol_escape_htmltag($rd['ra_ref']) ?></span>
                        <?php if (!empty($rd['ra_date'])) : ?>
                            <span><i class="fas fa-calendar-alt"></i> <?= dol_escape_htmltag($rd['ra_date']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($rd['ra_user'])) : ?>
                            <span class="krt-user"><?= dol_escape_htmltag($rd['ra_user']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($rd['ra_comment'])) : ?>
                    <div class="krt-comment">
                        <i class="fas fa-comment"></i> <?= dol_escape_htmltag(dol_trunc($rd['ra_comment'], 120)) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </a>
        <?php endif; ?>
    </div>

    <!-- Label -->
    <div class="kanban-card-label"><?= dol_escape_htmltag($t['label']) ?></div>

    <!-- Contacts row: [Resp initial] | [Contrib initials] [count] [👤+] -->
    <div class="kanban-card-contacts">
        <!-- Responsible: clickable initial circle -->
        <?php
        $respInitial  = '';
        $respFullname = '';
        $respId       = 0;
        if (!empty($t['responsible'])) {
            $respInitial  = !empty($t['responsible'][0]['initials']) ? $t['responsible'][0]['initials'] : strtoupper(mb_substr($t['responsible'][0]['fullname'], 0, 2));
            $respFullname = $t['responsible'][0]['fullname'];
            $respId       = $t['responsible'][0]['id'];
        }
        // Color matching progress bar
        $p = $t['progress'];
        if ($p <= $kanbanThresholds['draft_max']) {
            $respColor = '#999999';
        } elseif ($p <= $kanbanThresholds['progress_max']) {
            $respColor = '#e9ad4f';
        } elseif ($p <= $kanbanThresholds['control_max']) {
            $respColor = '#3085d6';
        } else {
            $respColor = '#47e58e';
        }
        ?>
        <div class="kanban-responsible-wrapper" data-task-id="<?= $t['id'] ?>" data-current-user="<?= $respId ?>">
            <span class="kanban-initial kanban-initial-responsible <?= empty($respInitial) ? 'kanban-initial-empty' : '' ?>"
                  title="<?= dol_escape_htmltag($respFullname ?: $langs->trans('Unassigned')) ?>"
                  style="background: <?= $respColor ?>">
                <?= $respInitial ?: '?' ?>
            </span>
            <!-- Hidden select, shown on click -->
            <select class="kanban-responsible-select" data-task-id="<?= $t['id'] ?>">
                <option value="0"><?= dol_escape_htmltag($langs->trans('Unassigned')) ?></option>
                <?php foreach ($allUsers as $u) : ?>
                    <option value="<?= $u['id'] ?>" data-initial="<?= $u['initials'] ?>"
                        <?= ($respId == $u['id']) ? 'selected' : '' ?>>
                        <?= dol_escape_htmltag($u['fullname']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <span class="kanban-separator">|</span>

        <!-- Contributor initials -->
        <?php if (!empty($t['contributors'])) : ?>
            <?php foreach ($t['contributors'] as $contrib) : ?>
                <span class="kanban-initial-wrapper" data-task-id="<?= $t['id'] ?>" data-user-id="<?= $contrib['id'] ?>">
                    <span class="kanban-initial kanban-initial-contributor"
                          title="<?= dol_escape_htmltag($contrib['fullname']) ?>">
                        <?= !empty($contrib['initials']) ? $contrib['initials'] : strtoupper(mb_substr($contrib['fullname'], 0, 2)) ?>
                    </span>
                    <span class="kanban-remove-contact" title="<?= dol_escape_htmltag($langs->trans('RemoveContact')) ?>">&times;</span>
                </span>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Add contributor -->
        <div class="kanban-add-contributor-wrapper">
            <button class="kanban-add-contributor-btn" title="<?= dol_escape_htmltag($langs->trans('AddContributor')) ?>">
                <i class="fas fa-user-plus"></i>
            </button>
            <div class="kanban-contributor-dropdown" data-task-id="<?= $t['id'] ?>">
                <input type="text" class="kanban-contributor-search" placeholder="<?= dol_escape_htmltag($langs->trans('Search')) ?>..." autocomplete="off">
                <div class="kanban-contributor-options">
                    <div class="kanban-optgroup-label"><?= dol_escape_htmltag($langs->trans('InternalUsers')) ?></div>
                    <?php
                    // Build list of already assigned contributor IDs
                    $assignedIds = [];
                    if (!empty($t['contributors'])) {
                        foreach ($t['contributors'] as $contrib) {
                            $assignedIds[] = 'internal_' . $contrib['id'];
                        }
                    }
                    ?>
                    <?php foreach ($allUsers as $u) :
                        $optVal = 'internal_' . $u['id'];
                        $isAssigned = in_array($optVal, $assignedIds);
                    ?>
                        <div class="kanban-contributor-option<?= $isAssigned ? ' assigned' : '' ?>" data-value="<?= $optVal ?>" data-search="<?= dol_escape_htmltag(strtolower($u['fullname'])) ?>">
                            <?= dol_escape_htmltag($u['fullname']) ?>
                            <?php if ($isAssigned) : ?><i class="fas fa-check" style="margin-left:4px;font-size:9px;color:#28a745"></i><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!empty($allContacts)) : ?>
                        <div class="kanban-optgroup-label"><?= dol_escape_htmltag($langs->trans('ExternalContacts')) ?> (HS)</div>
                        <?php foreach ($allContacts as $c) :
                            $optVal = 'external_' . $c['id'];
                            $isAssigned = in_array($optVal, $assignedIds);
                        ?>
                            <div class="kanban-contributor-option<?= $isAssigned ? ' assigned' : '' ?>" data-value="<?= $optVal ?>" data-search="<?= dol_escape_htmltag(strtolower($c['fullname'])) ?>">
                                <?= dol_escape_htmltag($c['fullname']) ?>
                                <?php if ($isAssigned) : ?><i class="fas fa-check" style="margin-left:4px;font-size:9px;color:#28a745"></i><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Dates row -->
    <div class="kanban-dates-row">
        <span class="kanban-date kanban-date-start kanban-editable-date"
              data-task-id="<?= $t['id'] ?>" data-field="date_start"
              data-raw="<?= $t['date_start'] ?>"
              title="<?= dol_escape_htmltag($langs->trans('DateStart')) ?>">
            <i class="fas fa-calendar-plus"></i> <span class="kanban-date-value"><?= !empty($t['date_start_fmt']) ? $t['date_start_fmt'] : '-' ?></span>
        </span>
        <span class="kanban-date kanban-date-end kanban-editable-date"
              data-task-id="<?= $t['id'] ?>" data-field="date_end"
              data-raw="<?= $t['date_end'] ?>"
              title="<?= dol_escape_htmltag($langs->trans('DateEnd')) ?>">
            <i class="fas fa-calendar-check"></i> <span class="kanban-date-value"><?= !empty($t['date_end_fmt']) ? $t['date_end_fmt'] : '-' ?></span>
        </span>
    </div>

    <!-- Progress bar -->
    <div class="kanban-card-progress">
        <div class="kanban-progress-bar">
            <?php
            if ($t['progress'] <= $kanbanThresholds['draft_max']) {
                $barClass = 'progress-grey';
            } elseif ($t['progress'] <= $kanbanThresholds['progress_max']) {
                $barClass = 'progress-yellow';
            } elseif ($t['progress'] <= $kanbanThresholds['control_max']) {
                $barClass = 'progress-blue';
            } else {
                $barClass = 'progress-green';
            }
            ?>
            <div class="kanban-progress-fill <?= $barClass ?>"
                 style="width: <?= $t['progress'] ?>%"></div>
        </div>
        <span class="kanban-progress-text"><?= $t['progress'] ?>%</span>
    </div>

    <!-- Categories/Tags -->
    <div class="kanban-card-tags" data-task-id="<?= $t['id'] ?>">
        <?php if (!empty($t['categories'])) : ?>
            <?php foreach ($t['categories'] as $cat) : ?>
                <span class="kanban-tag" data-cat-id="<?= $cat['id'] ?>" style="background: <?= !empty($cat['color']) ? '#' . dol_escape_htmltag($cat['color']) : '#8c8c8c' ?>">
                    <?= dol_escape_htmltag($cat['label']) ?>
                    <span class="kanban-tag-remove" title="<?= dol_escape_htmltag($langs->trans('Remove')) ?>">&times;</span>
                </span>
            <?php endforeach; ?>
        <?php endif; ?>
        <div class="kanban-tag-dropdown-wrapper">
            <button class="kanban-add-tag-btn" title="<?= dol_escape_htmltag($langs->trans('AddCategory')) ?>">
                <i class="fas fa-tag"></i><i class="fas fa-plus" style="font-size:7px;margin-left:1px"></i>
            </button>
            <?php
                $assignedCatIds = [];
                if (!empty($t['categories'])) {
                    foreach ($t['categories'] as $cat) {
                        $assignedCatIds[] = $cat['id'];
                    }
                }
            ?>
            <div class="kanban-tag-dropdown" data-task-id="<?= $t['id'] ?>">
                <?php foreach ($allAvailableCategories as $ac) :
                    $isAssigned = in_array($ac['id'], $assignedCatIds);
                    $dotColor = !empty($ac['color']) ? '#' . dol_escape_htmltag($ac['color']) : '#8c8c8c';
                ?>
                    <div class="kanban-tag-option<?= $isAssigned ? ' assigned' : '' ?>" data-value="<?= $ac['id'] ?>" data-color="<?= dol_escape_htmltag($ac['color']) ?>">
                        <span class="kanban-tag-dot" style="background: <?= $dotColor ?>"></span>
                        <?= dol_escape_htmltag($ac['label']) ?>
                        <?php if ($isAssigned) : ?>
                            <i class="fas fa-check" style="margin-left:auto;font-size:9px;color:#28a745"></i>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</div>
