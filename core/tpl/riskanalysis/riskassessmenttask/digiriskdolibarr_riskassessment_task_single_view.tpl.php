<div class="table-cell riskassessment-task-container riskassessment-task-container-<?php echo $related_task->id ?>" value="<?php echo $related_task->ref ?>">
	<input type="hidden" class="labelForDelete" value="<?php echo $langs->trans('DeleteTask') . ' ' . $related_task->ref . ' ?'; ?>">
	<div class="riskassessment-task-single-content riskassessment-task-single-content-<?php echo $risk->id ?>" value="<?php echo $related_task->id ?>">
		<div class="riskassessment-task-single riskassessment-task-single-<?php echo $related_task->id ?> wpeo-table table-row">
			<div class="riskassessment-task-content table-cell">
				<div class="riskassessment-task-data">
					<?php // An unfinished task whose shown deadline has passed
					$taskLate = $conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && !empty($related_task->datee) && $related_task->datee < dol_now() && $task_progress < 100; ?>
					<span class="riskassessment-task-date<?php echo $taskLate ? ' riskassessment-task-date-late' : ''; ?>">
						<i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE && ( ! empty($related_task->dateo))) ? $related_task->dateo : $related_task->datec)) . (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && ( ! empty($related_task->datee))) ? ' - ' . date('d/m/Y', $related_task->datee) : '') . ($taskLate ? ' (' . $langs->trans('RiskTaskLate') . ')' : ''); ?>
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
						// This task fragment is rendered twice per task (inline card + list modal);
						// cache the linked-files count per task id to avoid re-querying ecm_files each time.
						if (!isset($taskLinkedFilesCountCache)) {
							$taskLinkedFilesCountCache = [];
						}
						if (!isset($taskLinkedFilesCountCache[$related_task->id])) {
							$ecmfiles = new EcmFiles($db);
							$ecmfiles->fetchAll('', '', 0, 0, "(t.src_object_type:=:'projet_task') AND (t.src_object_id:=:{$related_task->id})");
							$taskLinkedFilesCountCache[$related_task->id] = count($ecmfiles->lines);
						}
						$linkedFilesCount = $taskLinkedFilesCountCache[$related_task->id];
						?>
						<i class="fas fa-clock"></i> <?php echo $allTimeSpent/60 . '/' . $related_task->planned_workload/60 ?>
					</span>
					<span class="riskassessment-task-budget"><i class="fas fa-coins"></i> <?php echo price($related_task->budget_amount, 0, $langs, 1, 0, 0, $conf->currency); ?></span>
					<span class="riskassessment-task-progress <?php echo $related_task->getTaskProgressColorClass($task_progress); ?>"><?php echo $task_progress ? $task_progress . " %" : 0 . " %" ?></span>
					<span class="riskassessment-taks-linked-files badge badge-secondary classfortooltip" title="<?php echo $langs->transnoentities('NumberOfLinkedFiles') ?>"><?php echo $linkedFilesCount; ?></span>
                    <?php
                    // Same double-render as above: cache the task contacts per task id so
                    // liste_contact() is not executed twice per task.
                    if (!isset($taskContactsCache)) {
                        $taskContactsCache = [];
                    }
                    if (!isset($taskContactsCache[$related_task->id])) {
                        $taskContactsCache[$related_task->id] = array_merge(
                            $related_task->liste_contact(-1, 'internal'),
                            $related_task->liste_contact()
                        );
                    }
                    $taskContacts    = $taskContactsCache[$related_task->id];
                    $taskContributor = [];
                    $taskExecutive   = [];

                    foreach ($taskContacts as $contact) {
                        if ($contact['code'] === 'TASKCONTRIBUTOR') {
                            $taskContributor[] = $contact;
                        } elseif ($contact['code'] === 'TASKEXECUTIVE') {
                            $taskExecutive[] = $contact;
                        }
                    }
                    // Initials only: the names and photos took up to three lines per task. getNomUrl() has no such
                    // mode, so its link and tooltip are kept and only its name gives way to the initials. A role
                    // nobody holds is left out rather than marked "no data"
                    foreach (['executive' => [$taskExecutive, 'fa-user-tie'], 'contributors' => [$taskContributor, 'fa-users']] as $taskRole => [$taskRoleContacts, $taskRolePicto]) {
                        if (empty($taskRoleContacts)) {
                            continue;
                        }
                        echo '<span class="riskassessment-task-' . $taskRole . '"><i class="fas ' . $taskRolePicto . '"></i>';
                        foreach ($taskRoleContacts as $contact) {
                            $userAuthor   = $usersList[$contact['id'] ?: $user->id];
                            $userInitials = dol_escape_htmltag(dol_strtoupper(dol_substr(dolGetFirstLetters($userAuthor->getFullName($langs)), 0, 3)));
                            echo '<span class="riskassessment-task-initials">';
                            echo preg_replace_callback('/(<span class="nopadding usertext[^"]*">)[^<]*(<\/span>)/', function ($userText) use ($userInitials) {
                                return $userText[1] . $userInitials . $userText[2];
                            }, $userAuthor->getNomUrl(0), 1);
                            echo '</span>';
                        }
                        echo '</span>';
                    }
                    ?>
				</div>
				<div class="riskassessment-task-title">
					<?php if ($contextpage != 'sharedrisk' && $contextpage != 'inheritedrisk' && !$conf->global->DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS) : ?>
						<span class="riskassessment-task-progress-checkbox <?php echo ($contextpage != 'sharedrisk' && $contextpage != 'inheritedrisk') ? '' : 'riskassessment-task-progress-checkbox-readonly'?>">
							<input type="checkbox" id="" class="riskassessment-task-progress-checkbox<?php echo $related_task->id; echo($task_progress == 100) ? ' progress-checkbox-check' : ' progress-checkbox-uncheck' ?>" name="progress-checkbox" value="" <?php echo ($task_progress == 100) ? 'checked' : ''; ?>>
						</span>
					<?php endif; ?>
					<span class="riskassessment-task-reference" value="<?php echo $related_task->ref ?>"><?php echo $related_task->getNomUrl(0, 'withproject'); ?></span>
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
