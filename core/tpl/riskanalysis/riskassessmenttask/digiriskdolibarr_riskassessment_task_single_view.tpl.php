<div class="table-cell riskassessment-task-container riskassessment-task-container-<?php echo $related_task->id ?>" value="<?php echo $related_task->ref ?>">
	<input type="hidden" class="labelForDelete" value="<?php echo $langs->trans('DeleteTask') . ' ' . $related_task->ref . ' ?'; ?>">
	<div class="riskassessment-task-single-content riskassessment-task-single-content-<?php echo $risk->id ?>" value="<?php echo $related_task->id ?>">
		<div class="riskassessment-task-single riskassessment-task-single-<?php echo $related_task->id ?> wpeo-table table-row">
			<div class="riskassessment-task-content table-cell">
				<div class="riskassessment-task-data">
					<span class="riskassessment-task-reference" value="<?php echo $related_task->ref ?>"><?php echo $related_task->getNomUrl(0, 'withproject'); ?></span>
					<span class="riskassessment-task-date">
						<i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE && ( ! empty($related_task->dateo))) ? $related_task->dateo : $related_task->datec)) . (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && ( ! empty($related_task->datee))) ? ' - ' . date('d/m/Y', $related_task->datee) : ''); ?>
					</span>
					<span class="riskassessment-task-timespent  riskassessment-total-task-timespent-<?php echo $related_task->id ?>">
						<?php $allTimeSpentArray = $timeSpentSortedByTasks[$related_task->id] ?? [];
						$allTimeSpent = 0;
						if (is_array($allTimeSpentArray) && !empty($allTimeSpentArray)) {
							foreach ($allTimeSpentArray as $timespent) {
								$allTimeSpent += $timespent->timespent_duration;
							}
						}
						require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';
						$ecmfiles = new EcmFiles($db);
						$ecmfiles->fetchAll('', '', 0, 0, "(t.src_object_type:=:'projet_task') AND (t.src_object_id:=:{$related_task->id})");
						?>
						<i class="fas fa-clock"></i> <?php echo $allTimeSpent/60 . '/' . $related_task->planned_workload/60 ?>
					</span>
					<span class="riskassessment-task-budget"><i class="fas fa-coins"></i> <?php echo price($related_task->budget_amount, 0, $langs, 1, 0, 0, $conf->currency); ?></span>
					<span class="riskassessment-task-progress <?php echo $related_task->getTaskProgressColorClass($task_progress); ?>"><?php echo $task_progress ? $task_progress . " %" : 0 . " %" ?></span>
					<span class="riskassessment-taks-linked-files badge badge-secondary classfortooltip" title="<?php echo $langs->transnoentities('NumberOfLinkedFiles') ?>"><?php echo count($ecmfiles->lines); ?></span>
                    <?php
                    $contactsIntern  = $related_task->liste_contact(-1, 'internal');
                    $contactsExtern  = $related_task->liste_contact();
                    $taskContacts    = array_merge($contactsIntern, $contactsExtern);
                    $taskContributor = [];
                    $taskExecutive   = [];

                    if (!empty($taskContacts)) {
                        foreach ($taskContacts as $contact) {
                            if ($contact['code'] === 'TASKCONTRIBUTOR') {
                                $taskContributor[] = $contact;
                            } elseif ($contact['code'] === 'TASKEXECUTIVE') {
                                $taskExecutive[] = $contact;
                            }
                        }
                    } else {
                        echo $langs->transnoentities('NoData');
                    }
                    echo '<i class="fas fa-user-tie"></i>';
                    if (!empty($taskExecutive)) {
                        foreach ($taskExecutive as $executive) {
                            $userAuthor = $usersList[$executive['id'] ?: $user->id];
                            echo '<span class="riskassessment-task-author">';
                            echo getNomUrlUser($userAuthor);
                            echo '</span>';
                        }
                    } else {
                        echo $langs->transnoentities('NoData');
                    }
                    echo '<i class="fas fa-users"></i>';
                    if (!empty($taskContributor)) {
                        foreach ($taskContributor as $contributor) {
                            $userAuthor = $usersList[$contributor['id'] ?: $user->id];
                            echo '<span class="riskassessment-task-author">';
                            echo getNomUrlUser($userAuthor);
                            echo '</span>';
                        }
                    } else {
                        echo $langs->transnoentities('NoData');
                    }
                    ?>
				</div>
				<div class="riskassessment-task-title">
					<?php if ($contextpage != 'sharedrisk' && $contextpage != 'inheritedrisk' && !$conf->global->DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS) : ?>
						<span class="riskassessment-task-progress-checkbox <?php echo ($contextpage != 'sharedrisk' && $contextpage != 'inheritedrisk') ? '' : 'riskassessment-task-progress-checkbox-readonly'?>">
							<input type="checkbox" id="" class="riskassessment-task-progress-checkbox<?php echo $related_task->id; echo($task_progress == 100) ? ' progress-checkbox-check' : ' progress-checkbox-uncheck' ?>" name="progress-checkbox" value="" <?php echo ($task_progress == 100) ? 'checked' : ''; ?>>
						</span>
					<?php endif; ?>
					<span class="riskassessment-task-author-label" title="<?php echo $related_task->label; ?>">
						<?php echo dol_trunc($related_task->label, 255); ?>
					</span>
				</div>
			</div>
			<!-- BUTTON MODAL RISK ASSESSMENT TASK EDIT  -->
			<?php if ($contextpage != 'sharedrisk' && $contextpage != 'inheritedrisk') : ?>
				<div class="table-cell riskassessment-task-actions wpeo-gridlayout grid-2 grid-gap-0">
					<?php if ($permissiontoadd) : ?>
						<div class="wpeo-button button-square-40 button-transparent modal-open" value="<?php echo $related_task->id ?>">
							<input type="hidden" class="modal-options" data-modal-to-open="risk_assessment_task_edit<?php echo $related_task->id; ?>" data-from-id="<?php echo $related_task->id; ?>" data-from-type="riskassessmenttask" data-from-subtype="photo" data-from-subdir="" data-photo-class="riskassessment-from-riskassessment-create-<?php echo $risk->id; ?>"/>
							<i class="fas fa-pencil-alt button-icon"></i>
						</div>
					<?php else : ?>
						<div class="wpeo-button button-square-40 button-grey wpeo-tooltip-event" aria-label="<?php echo $langs->trans('PermissionDenied'); ?>" value="<?php echo $related_task->id ?>">
							<i class="fas fa-pencil-alt button-icon"></i>
						</div>
					<?php endif; ?>
					<?php if ($permissiontodelete) : ?>
						<div class="riskassessment-task-delete wpeo-button button-square-40 button-transparent" value="<?php echo $related_task->id ?>">
							<i class="fas fa-trash button-icon"></i>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
