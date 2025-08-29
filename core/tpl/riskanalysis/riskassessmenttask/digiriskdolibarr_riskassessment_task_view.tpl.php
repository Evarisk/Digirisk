<?php
$related_tasks = $riskAssessmentTaskList[$risk->id] ?? [];
if (!empty($related_tasks) && is_array($related_tasks)) {
	foreach ($related_tasks as $related_task_id => $related_task) {
		if (!is_object($related_task)) {
			unset($related_tasks[$related_task_id]);
		}
	}
}
?>
<div class="wpeo-table riskassessment-tasks riskassessment-tasks<?php echo $risk->id ?>" value="<?php echo $risk->id ?>">
	<div class="table-cell riskassessment-task-listing-wrapper riskassessment-task-listing-wrapper-<?php echo $risk->id ?>">
		<?php if (!empty($related_tasks) && $related_tasks > 0) : ?>
			<?php foreach ($related_tasks as $related_task) {
				if (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS) && $contextpage == 'sharedrisk') {
					if ($related_task->fk_projet != $conf->global->DIGIRISKDOLIBARR_DU_PROJECT && $related_task->fk_projet != $conf->global->DIGIRISKDOLIBARR_ENVIRONMENT_PROJECT) {
						$project->fetch($related_task->fk_projet);
					} else {
						$project = $DUProject;
					}
					$result = !empty($mc->sharings['project']) ? in_array($project->entity, $mc->sharings['project']) : 0;
				} else {
					$result = 1;
				}
			} ?>
		<?php endif; ?>
		<div class="table-cell-header">
			<input type="hidden" name="sharedTaskTooltipUrl" value="<?php echo DOL_URL_ROOT . '/custom/multicompany/admin/multicompany.php?action=edit&id=' . $conf->entity;  ?>">
			<div class="table-cell-header-label"><strong><?php echo $form->textwithpicto($langs->trans('ListingHeaderTask') . ' (' . (($related_tasks && $result) ? count($related_tasks) : 0) . ')', $result == 0 ? $langs->trans('ListingHeaderTaskTooltip') : '', 1, 'help', 'listingHeaderTaskTooltip'); ?></strong></div>
			<div class="table-cell-header-actions">
				<?php if ($permissiontoread && $result) : ?>
					<div class="wpeo-button button-square-40 button-grey wpeo-tooltip-event modal-open" aria-label="<?php echo $langs->trans('ListRiskAssessmentTask') ?>" value="<?php echo $risk->id;?>">
						<input type="hidden" class="modal-options" data-modal-to-open="risk_assessment_task_list<?php echo $risk->id; ?>" data-from-id="<?php echo $risk->id;?>" data-from-type="riskassessment" data-from-subtype="photo" data-from-subdir="" data-photo-class="riskassessment-from-riskassessment-create-<?php echo $risk->id; ?>"/>
						<i class="button-icon fas fa-list-ul"></i>
					</div>
				<?php elseif ($result == 0) : ?>
					<div class="wpeo-button button-square-40 button-grey wpeo-tooltip-event" aria-label="<?php echo $langs->trans('NoTaskShared') ?>" value="<?php echo $risk->id;?>">
						<i class="button-icon fas fa-list-ul"></i>
					</div>
				<?php else : ?>
					<div class="wpeo-button button-square-40 button-grey wpeo-tooltip-event" aria-label="<?php echo $langs->trans('PermissionDenied') ?>" value="<?php echo $risk->id;?>">
						<i class="button-icon fas fa-list-ul"></i>
					</div>
				<?php endif; ?>
				<?php if ($contextpage != 'sharedrisk' && $contextpage != 'inheritedrisk') : ?>
					<?php if ($permissiontoadd) : ?>
						<div class="wpeo-button button-square-40 button-primary wpeo-tooltip-event modal-open" aria-label="<?php echo $langs->trans('AddRiskAssessmentTask') ?>" value="<?php echo $risk->id;?>">
							<input type="hidden" class="modal-options" data-modal-to-open="risk_assessment_task_add<?php echo $risk->id; ?>" data-from-id="0" data-from-type="riskassessment" data-from-subtype="photo" data-from-subdir="" data-photo-class="riskassessment-from-riskassessment-create-<?php echo $risk->id; ?>"/>
							<i class="fas fa-plus button-icon"></i>
						</div>
					<?php else : ?>
						<div class="wpeo-button button-square-40 button-grey wpeo-tooltip-event risk-list-button" aria-label="<?php echo $langs->trans('PermissionDenied') ?>" value="<?php echo $risk->id;?>">
							<i class="fas fa-plus button-icon"></i>
						</div>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php if (!empty($related_tasks) && $related_tasks > 0) : ?>
			<?php if ($conf->global->DIGIRISKDOLIBARR_SHOW_ALL_TASKS) : ?>
				<?php $nb_of_tasks_in_progress = 0;
				foreach ($related_tasks as $related_task) {
					if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS) {
						$tmparray = $related_task->getSummaryOfTimeSpent();
						if ($tmparray['total_duration'] > 0 && !empty($related_task->planned_workload)) {
							$task_progress = round($tmparray['total_duration'] / $related_task->planned_workload * 100, 2);
						} else {
							$task_progress = 0;
						}
					} else {
						$task_progress = $related_task->progress;
					}
					if ((($conf->global->DIGIRISKDOLIBARR_SHOW_TASKS_DONE) ? $task_progress <= 100 : $task_progress < 100) && $result) {
						$nb_of_tasks_in_progress++;
						require __DIR__ . '/digiriskdolibarr_riskassessment_task_single_view.tpl.php';
					}
				}
				if ($nb_of_tasks_in_progress == 0) : ?>
					<div class="riskassessment-task-container riskassessment-no-task">
						<div class="riskassessment-task-single-content riskassessment-task-single-content-<?php echo $risk->id ?>" value="<?php echo $related_task->id ?>">
							<div class="riskassessment-task-single riskassessment-task-single-<?php echo $risk->id ?>">
								<div class="riskassessment-task-content">
									<div class="riskassessment-task-data" style="justify-content: center;">
										<span class="name"><?php echo $result > 0 ? $langs->trans('NoTaskLinked') : $langs->trans('NoTaskShared'); ?></span>
									</div>
								</div>
							</div>
						</div>
					</div>
				<?php endif; ?>
			<?php else : ?>
				<?php $related_task = end($related_tasks);
				if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS) {
					$tmparray = $related_task->getSummaryOfTimeSpent();
					if ($tmparray['total_duration'] > 0 && !empty($related_task->planned_workload)) {
						$task_progress = round($tmparray['total_duration'] / $related_task->planned_workload * 100, 2);
					} else {
						$task_progress = 0;
					}
				} else {
					$task_progress = $related_task->progress;
				}
				if ($result > 0) {
					require __DIR__ . '/digiriskdolibarr_riskassessment_task_single_view.tpl.php';
				} else { ?>
					<div class="riskassessment-task-container riskassessment-no-task">
						<div class="riskassessment-task-single-content riskassessment-task-single-content-<?php echo $risk->id ?>" value="<?php echo $related_task->id ?>">
							<div class="riskassessment-task-single riskassessment-task-single-<?php echo $risk->id ?>">
								<div class="riskassessment-task-content">
									<div class="riskassessment-task-data" style="justify-content: center;">
										<span class="name"><?php echo $result > 0 ? $langs->trans('NoTaskLinked') : $langs->trans('NoTaskShared'); ?></span>
									</div>
								</div>
							</div>
						</div>
					</div>
				<?php } ?>
			<?php endif; ?>
		<?php else : ?>
			<div class="riskassessment-task-listing-wrapper riskassessment-task-listing-wrapper-<?php echo $risk->id ?>">
				<div class="riskassessment-task-container riskassessment-no-task">
					<div class="riskassessment-task-single-content riskassessment-task-single-content-<?php echo $risk->id ?>" value="<?php echo $related_task->id ?>">
						<div class="riskassessment-task-single riskassessment-task-single-<?php echo $risk->id ?>">
							<div class="riskassessment-task-content">
								<div class="riskassessment-task-data" style="justify-content: center;">
									<span class="name"><?php echo $langs->trans('NoTaskLinked'); ?></span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<!-- RISK ASSESSMENT TASK ADD MODAL-->
		<div class="riskassessment-task-add-modal">
			<div class="wpeo-modal modal-risk" id="risk_assessment_task_add<?php echo $risk->id?>" value="<?php echo $risk->id ?>">
				<div class="modal-container wpeo-modal-event">
					<!-- Modal-Header -->
					<div class="modal-header">
						<h2 class="modal-title"><?php echo  '<i class="fas fa-tasks"></i>' . ' ' . $taskNextValue . '   ' . '<i class="fas fa-project-diagram em088"></i>' . ' ' . $DUProject->getNomUrl() ?><i class="fas fa-info-circle wpeo-tooltip-event" aria-label="<?php echo $langs->trans('HowToSetDUProject'); ?>"></i></h2>
						<div class="modal-close"><i class="fas fa-times"></i></div>
					</div>
					<!-- MODAL ADD RISK ASSESSMENT TASK CONTENT -->
					<div class="modal-content" id="#modalContent<?php echo $risk->id ?>">
						<div class="messageWarningTaskLabel notice hidden">
							<div class="wpeo-notice notice-warning riskassessment-task-label-warning-notice">
								<div class="notice-content">
									<div class="notice-title"><?php echo $langs->trans('WarningTaskLabel') ?></div>
								</div>
								<div class="notice-close"><i class="fas fa-times"></i></div>
							</div>
						</div>
                        <div class="riskassessment-task-container">
                            <div class="riskassessment-task">
                                <div class="wpeo-gridlayout flex flex-row items-center">
                                    <i class="fas fa-paragraph"></i>
                                    <input type="text" class="riskassessment-task-label" name="label" value="">
                                </div>
                                <div class="riskassessment-task-date wpeo-gridlayout grid-3" style="margin-top: 1em; margin-bottom: 1em;">
                                    <div class="flex flex-row items-center">
                                        <i class="fas fa-calendar-day" style="margin-right: 1em;"></i>
                                        <?php print '<input type="datetime-local" id="RiskassessmentTaskDateStart' . $risk->id . '" name="RiskassessmentTaskDateStart' . $risk->id . '">'; ?>
                                    </div>
                                    <div class="flex flex-row items-center">
                                        <i class="fas fa-calendar-check" style="margin-right: 1em;"></i>
                                        <?php print '<input type="datetime-local" id="RiskassessmentTaskDateEnd' . $risk->id . '" name="RiskassessmentTaskDateEnd' . $risk->id . '">'; ?>
                                    </div>
                                    <div class="flex flex-row items-center paddingright">
                                        <i class="fas fa-euro-sign" style="margin-right: 1em;"></i>
                                        <input type="text" class="riskassessment-task-budget" name="budget" value="">
                                    </div>
                                </div>
                                <div>
                                    <div class="flex flex-row items-center justify-center">
                                        <i class="fas fa-user-tie 100" style="margin-right: 1em;"></i>
                                        <?php print $form->select_dolusers(0, 'executive_id', 1, null, 0, '', 0, '', 0, 'minwidth200', '', 0, '', 'executiveSelect'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
					</div>
					<!-- Modal-Footer -->
					<div class="modal-footer">
						<?php if ($permissiontoadd) : ?>
							<div class="wpeo-button riskassessment-task-create button-blue button-disable modal-close"  style="color: #fff" value="<?php echo $risk->id ?>">
								<i class="fas fa-plus"></i>
							</div>
						<?php else : ?>
							<div class="wpeo-button button-grey wpeo-tooltip-event" aria-label="<?php echo $langs->trans('PermissionDenied') ?>">
								<i class="fas fa-plus"></i>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<!-- RISK ASSESSMENT TASK ADD MODAL END -->
		<!-- RISK ASSESSMENT TASK LIST MODAL -->
		<div class="riskassessment-task-list-modal">
			<div class="wpeo-modal modal-risk" id="risk_assessment_task_list<?php echo $risk->id ?>" value="<?php echo $risk->id ?>">
				<div class="modal-container wpeo-modal-event">
					<!-- Modal-Header -->
					<div class="modal-header">
						<h2 class="modal-title"><?php echo $langs->trans('TaskList') . ' ' . $risk->ref . '  ' . $langs->trans('AT') . '  ' . $langs->trans('Project') . '  ' . $DUProject->getNomUrl()  ?><i class="fas fa-info-circle wpeo-tooltip-event" aria-label="<?php echo $langs->trans('HowToSetDUProject'); ?>"></i></h2>
						<div class="modal-close"><i class="fas fa-times"></i></div>
					</div>
					<!-- MODAL RISK ASSESSMENT TASK LIST CONTENT -->
					<div class="modal-content modal-risk" id="#modalContent" value="<?php echo $risk->id ?>">
						<!-- RISKASSESSMENT TASK -->
						<div class="messageSuccessTaskEdit notice hidden">
							<input type="hidden" class="valueForEditTask1" value="<?php echo $langs->trans('TheTask') . ' ' ?>">
							<input type="hidden" class="valueForEditTask2" value="<?php echo ' ' . $langs->trans('HasBeenEditedF') ?>">
							<div class="wpeo-notice notice-success riskassessment-task-edit-success-notice">
								<div class="notice-content">
									<div class="notice-title"><?php echo $langs->trans('TaskWellEdited') ?></div>
									<div class="notice-subtitle">
										<span class="text"></span>
									</div>
								</div>
								<div class="notice-close"><i class="fas fa-times"></i></div>
							</div>
						</div>
						<div class="messageErrorTaskEdit notice hidden">
							<input type="hidden" class="valueForEditTask1" value="<?php echo $langs->trans('TheTask') . ' ' ?>">
							<input type="hidden" class="valueForEditTask2" value="<?php echo ' ' . $langs->trans('HasNotBeenEditedF') ?>">
							<div class="wpeo-notice notice-warning riskassessment-task-edit-error-notice">
								<div class="notice-content">
									<div class="notice-title"><?php echo $langs->trans('TaskNotEdited') ?></div>
									<div class="notice-subtitle">
										<a href="">
											<span class="text"></span>
										</a>
									</div>
								</div>
								<div class="notice-close"><i class="fas fa-times"></i></div>
							</div>
						</div>
						<div class="messageSuccessTaskDelete notice hidden">
							<input type="hidden" class="valueForDeleteTask1" value="<?php echo $langs->trans('TheTask') . ' ' ?>">
							<input type="hidden" class="valueForDeleteTask2" value="<?php echo ' ' . $langs->trans('HasBeenDeletedF') ?>">
							<div class="wpeo-notice notice-success riskassessment-task-delete-success-notice">
								<div class="notice-content">
									<div class="notice-title"><?php echo $langs->trans('TaskWellDeleted') ?></div>
									<div class="notice-subtitle">
										<span class="text"></span>
									</div>
								</div>
								<div class="notice-close"><i class="fas fa-times"></i></div>
							</div>
						</div>
						<div class="messageErrorTaskDelete notice hidden">
							<input type="hidden" class="valueForDeleteTask1" value="<?php echo $langs->trans('TheTask') . ' ' ?>">
							<input type="hidden" class="valueForDeleteTask2" value="<?php echo ' ' . $langs->trans('HasNotBeenDeletedF') ?>">
							<div class="wpeo-notice notice-warning riskassessment-task-delete-error-notice">
								<div class="notice-content">
									<div class="notice-title"><?php echo $langs->trans('TaskNotDeleted') ?></div>
									<div class="notice-subtitle">
										<a href="">
											<span class="text"></span>
										</a>
									</div>
								</div>
								<div class="notice-close"><i class="fas fa-times"></i></div>
							</div>
						</div>
						<?php if ( ! empty($related_tasks) && $related_tasks > 0) : ?>
							<?php foreach ($related_tasks as $related_task) : ?>
								<?php
								if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS) {
									$tmparray = $related_task->getSummaryOfTimeSpent();
									if ($tmparray['total_duration'] > 0 && !empty($related_task->planned_workload)) {
										$task_progress = round($tmparray['total_duration'] / $related_task->planned_workload * 100, 2);
									} else {
										$task_progress = 0;
									}
								} else {
									$task_progress = $related_task->progress;
								}
								?>
								<?php if ($result > 0) : ?>
									<?php require __DIR__ . '/digiriskdolibarr_riskassessment_task_single_view.tpl.php'; ?>
								<?php endif; ?>

							<?php endforeach; ?>

						<?php endif; ?>
					</div>
					<!-- Modal-Footer -->
					<div class="modal-footer">
						<div class="wpeo-button button-grey modal-close">
							<span><?php echo $langs->trans('CloseModal'); ?></span>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- RISK ASSESSMENT TASK LIST MODAL END -->
		<!-- RISK ASSESSMENT TASK EDIT MODALS -->
		<?php if ( ! empty($related_tasks) && $related_tasks > 0) :
            foreach ($related_tasks as $related_task) :
                if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS) {
                    $tmparray = $related_task->getSummaryOfTimeSpent();
                    if ($tmparray['total_duration'] > 0 && !empty($related_task->planned_workload)) {
                        $task_progress = round($tmparray['total_duration'] / $related_task->planned_workload * 100, 2);
                    } else {
                        $task_progress = 0;
                    }
                } else {
                    $task_progress = $related_task->progress;
                }
                $allTimeSpentArray = $timeSpentSortedByTasks[$related_task->id] ?? []; ?>
				<div class="wpeo-modal riskassessment-task-edit-modal" id="risk_assessment_task_edit<?php echo $related_task->id ?>">
					<div class="modal-container wpeo-modal-event">
						<!-- Modal-Header -->
						<div class="modal-header">
							<h2 class="modal-title"><?php echo $langs->trans('TaskEdit') . ' ' . $related_task->getNomUrl(0) ?></h2>
							<div class="modal-close"><i class="fas fa-times"></i></div>
						</div>
						<!-- Modal-Content -->
						<div class="modal-content riskassessment-task-content">
							<div class="riskassessment-task-data" value="<?php echo $related_task->id ?>">
								<span class="riskassessment-task-reference" value="<?php echo $related_task->ref ?>"><?php echo $related_task->getNomUrl(0, 'withproject'); ?></span>
								<span class="riskassessment-task-author">
									<?php $userAuthor = $usersList[$related_task->fk_user_creat > 0 ? $related_task->fk_user_creat : $user->id];
									echo getNomUrlUser($userAuthor); ?>
								</span>
								<span class="riskassessment-task-date">
									<i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE && ( ! empty($related_task->dateo))) ? $related_task->dateo : $related_task->datec)) . (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && ( ! empty($related_task->datee))) ? ' - ' . date('d/m/Y', $related_task->datee) : ''); ?>
								</span>
								<span class="riskassessment-total-task-timespent riskassessment-total-task-timespent-<?php echo $related_task->id ?>">
									<?php
									$allTimeSpent = 0;
									if (is_array($allTimeSpentArray) && !empty($allTimeSpentArray)) {
										foreach ($allTimeSpentArray as $timespent) {
											$allTimeSpent += $timespent->timespent_duration;
										}
									}
									?>
									<i class="fas fa-clock"></i> <?php echo $allTimeSpent/60 . '/' . $related_task->planned_workload/60 ?>
								</span>
								<span class="riskassessment-task-budget"><i class="fas fa-coins"></i> <?php echo price($related_task->budget_amount, 0, $langs, 1, 0, 0, $conf->currency); ?></span>
								<span class="riskassessment-task-progress <?php echo $related_task->getTaskProgressColorClass($task_progress); ?>"><?php echo $task_progress ? $task_progress . " %" : 0 . " %" ?></span>
							</div>
							<br>
							<div class="riskassessment-task-content">
<!--								<span class="title">--><?php //echo $langs->trans('Label'); ?><!--</span>-->
								<div class="riskassessment-task-title riskassessment-task-label">
									<?php if (!$conf->global->DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS) : ?>
										<span class="riskassessment-task-progress-checkbox">
											<input type="checkbox" id="" class="riskassessment-task-progress-checkbox<?php echo $related_task->id ?>" name="progress-checkbox" value="" <?php echo ($task_progress == 100) ? 'checked' : ''; ?>>
										</span>
									<?php endif; ?>
									<input type="text" class="riskassessment-task-author-label riskassessment-task-label<?php echo $related_task->id ?>" name="label" value="<?php echo $related_task->label; ?>">
								</div>
								<div class="riskassessment-task-date wpeo-gridlayout grid-3">
									<div>
										<span class="title"><?php echo $langs->trans('DateStart'); ?></span>
                                        <?php print '<input type="datetime-local" id="RiskassessmentTaskDateStartEdit' . $related_task->id . '" name="RiskassessmentTaskDateStartEdit' . $related_task->id . '" value="' . ($related_task->dateo ? dol_print_date($related_task->dateo, '%Y-%m-%dT%H:%M:%S') : '') . '">'; ?>
									</div>
									<div>
										<span class="title"><?php echo $langs->trans('Deadline'); ?></span>
                                        <?php print '<input type="datetime-local" id="RiskassessmentTaskDateEndEdit' . $related_task->id . '" name="RiskassessmentTaskDateEndEdit' . $related_task->id . '" value="' . ($related_task->datee ? dol_print_date($related_task->datee, '%Y-%m-%dT%H:%M:%S') : '') . '">'; ?>
									</div>
									<div class="riskassessment-task-budget">
										<span class="title"><?php echo $langs->trans('Budget'); ?></span>
										<input type="text" class="riskassessment-task-budget<?php echo $related_task->id ?>" name="budget" value="<?php echo price2num($related_task->budget_amount); ?>">
									</div>
								</div>
							</div>
							<hr>
							<!-- RISKASSESSMENT TASK TIME SPENT NOTICE -->
							<div class="messageSuccessTaskTimeSpentCreate<?php echo $related_task->id ?> notice hidden">
								<input type="hidden" class="valueForCreateTaskTimeSpent1" value="<?php echo $langs->trans('TheTaskTimeSpent') . ' ' . $langs->trans('OnTheTask') . ' ' ?>">
								<input type="hidden" class="valueForCreateTaskTimeSpent2" value="<?php echo ' ' . $langs->trans('HasBeenCreatedM') ?>">
								<div class="wpeo-notice notice-success riskassessment-task-timespent-create-success-notice">
									<div class="notice-content">
										<div class="notice-title"><?php echo $langs->trans('TaskTimeSpentWellCreated') ?></div>
										<div class="notice-subtitle">
											<span class="text"></span>
										</div>
									</div>
									<div class="notice-close"><i class="fas fa-times"></i></div>
								</div>
							</div>
							<div class="messageErrorTaskTimeSpentCreate<?php echo $related_task->id ?> notice hidden">
								<input type="hidden" class="valueForCreateTaskTimeSpent1" value="<?php echo $langs->trans('TheTaskTimeSpent') . ' ' . $langs->trans('OnTheTask') . ' ' ?>">
								<input type="hidden" class="valueForCreateTaskTimeSpent2" value="<?php echo ' ' . $langs->trans('HasNotBeenCreateM') ?>">
								<div class="wpeo-notice notice-warning riskassessment-task-timespent-create-error-notice">
									<div class="notice-content">
										<div class="notice-title"><?php echo $langs->trans('TaskTimeSpentNotCreated') ?></div>
									</div>
									<div class="notice-close"><i class="fas fa-times"></i></div>
								</div>
							</div>
							<div class="messageSuccessTaskTimeSpentEdit<?php echo $related_task->id ?> notice hidden">
								<input type="hidden" class="valueForEditTaskTimeSpent1" value="<?php echo $langs->trans('TheTaskTimeSpent') . ' ' . $langs->trans('OnTheTask') . ' ' ?>">
								<input type="hidden" class="valueForEditTaskTimeSpent2" value="<?php echo ' ' . $langs->trans('HasBeenEditedM') ?>">
								<div class="wpeo-notice notice-success riskassessment-task-timespent-edit-success-notice">
									<div class="notice-content">
										<div class="notice-title"><?php echo $langs->trans('TaskTimeSpentWellEdited') ?></div>
										<div class="notice-subtitle">
											<span class="text"></span>
										</div>
									</div>
									<div class="notice-close"><i class="fas fa-times"></i></div>
								</div>
							</div>
							<div class="messageErrorTaskTimeSpentEdit<?php echo $related_task->id ?> notice hidden">
								<input type="hidden" class="valueForEditTaskTimeSpent1" value="<?php echo $langs->trans('TheTaskTimeSpent') . ' ' . $langs->trans('OnTheTask') . ' ' ?>">
								<input type="hidden" class="valueForEditTaskTimeSpent2" value="<?php echo ' ' . $langs->trans('HasNotBeenEditedM') ?>">
								<div class="wpeo-notice notice-warning riskassessment-task-timespent-edit-error-notice">
									<div class="notice-content">
										<div class="notice-title"><?php echo $langs->trans('TaskTimeSpentNotEdited') ?></div>
									</div>
									<div class="notice-close"><i class="fas fa-times"></i></div>
								</div>
							</div>
							<div class="messageSuccessTaskTimeSpentDelete<?php echo $related_task->id ?> notice hidden">
								<input type="hidden" class="valueForDeleteTaskTimeSpent1" value="<?php echo $langs->trans('TheTaskTimeSpent') . ' ' . $langs->trans('OnTheTask') . ' ' ?>">
								<input type="hidden" class="valueForDeleteTaskTimeSpent2" value="<?php echo ' ' . $langs->trans('HasBeenDeletedM') ?>">
								<div class="wpeo-notice notice-success riskassessment-task-timespent-delete-success-notice">
									<div class="notice-content">
										<div class="notice-title"><?php echo $langs->trans('TaskTimeSpentWellDeleted') ?></div>
										<div class="notice-subtitle">
											<span class="text"></span>
										</div>
									</div>
									<div class="notice-close"><i class="fas fa-times"></i></div>
								</div>
							</div>
							<div class="messageErrorTaskTimeSpentDelete<?php echo $related_task->id ?> notice hidden">
								<input type="hidden" class="valueForDeleteTaskTimeSpent1" value="<?php echo $langs->trans('TheTaskTimeSpent') . ' ' . $langs->trans('OnTheTask') . ' ' ?>">
								<input type="hidden" class="valueForDeleteTaskTimeSpent2" value="<?php echo ' ' . $langs->trans('HasNotBeenDeletedM') ?>">
								<div class="wpeo-notice notice-warning riskassessment-task-timespent-delete-error-notice">
									<div class="notice-content">
										<div class="notice-title"><?php echo $langs->trans('TaskTimeSpentNotDeleted') ?></div>
									</div>
									<div class="notice-close"><i class="fas fa-times"></i></div>
								</div>
							</div>
							<div class="riskassessment-task-timespent-container">
								<span class="title"><?php echo $langs->trans('TimeSpent'); ?></span>
								<div class="riskassessment-task-timespent-add-container">
									<div class="timespent-date">
										<span class="title"><?php echo $langs->trans('Date'); ?></span>
										<?php print $form->selectDate(dol_now('tzuser'), 'RiskassessmentTaskTimespentDate'.$related_task->id, 1, 1, 0, 'riskassessment_task_timespent_form', 1, 0); ?>
									</div>
									<div class="timespent-comment">
										<span class="title"><?php echo $langs->trans('Comment'); ?></span>
										<input type="text" class="riskassessment-task-timespent-comment" name="comment" value="">
									</div>
									<div class="timespent-duration">
										<span class="title"><?php echo $langs->trans('Duration'); ?></span>
										<span class="time"><?php print '<input type="number" placeholder="minutes" class="riskassessment-task-timespent-duration" name="timespentDuration" value="'.$conf->global->DIGIRISKDOLIBARR_EVALUATOR_DURATION.'">'; ?></span>
									</div>
									<?php if ($permissiontoadd) : ?>
										<div class="timespent-add-button">
											<div class="wpeo-button riskassessment-task-timespent-create button-square-30 button-rounded" value="<?php echo $related_task->id ?>">
												<i class="fas fa-plus button-icon"></i>
											</div>
										</div>
									<?php endif; ?>
								</div>
								<div class="riskassessment-task-timespent-list" value="<?php echo $related_task->id ?>">
									<ul class="wpeo-table table-flex riskassessment-task-timespent-list-<?php echo $related_task->id ?>">
										<?php if (!empty($allTimeSpentArray) && $allTimeSpentArray > 0) : ?>
											<?php foreach ($allTimeSpentArray as $time_spent) :?>
												<li class="riskassessment-task-timespent-<?php echo $time_spent->timespent_id ?>">
													<input type="hidden" class="labelForDelete" value="<?php echo $langs->trans('DeleteTaskTimeSpent', $time_spent->timespent_duration/60) . ' ' . $related_task->ref . ' ?'; ?>">
													<div class="table-row riskassessment-task-timespent-container">
														<div class="table-cell table-padding-0 riskassessment-task-timespent-single">
															<span class="riskassessment-task-timespent-author">
																<?php $userAuthor = $usersList[$time_spent->timespent_fk_user?:$user->id];
																echo getNomUrlUser($userAuthor); ?>
															</span>
															<span class="riskassessment-task-timespent-date">
																<i class="fas fa-calendar-alt"></i> <?php echo dol_print_date($time_spent->timespent_datehour, 'dayhour'); ?>
															</span>
															<span class="riskassessment-task-timespent-time">
																<i class="fas fa-clock"></i> <?php echo $time_spent->timespent_duration/60 . ' mins'; ?>
															</span>
															<span class="riskassessment-task-timespent-comment">
																<?php echo $time_spent->timespent_note; ?>
															</span>
														</div>
														<!-- BUTTON MODAL RISK ASSESSMENT TASK TIMESPENT EDIT  -->
														<div class="table-cell table-end table-125 table-padding-0 riskassessment-task-actions">
															<?php if ($permissiontoadd) : ?>
																<div class="wpeo-button button-square-50 button-transparent modal-open" value="<?php echo $time_spent->timespent_id ?>">
																	<input type="hidden" class="modal-options" data-modal-to-open="risk_assessment_task_timespent_edit<?php echo $time_spent->timespent_id; ?>" data-from-id="<?php echo $time_spent->timespent_id; ?>" data-from-type="riskassessmenttasktimespent" data-from-subtype="photo" data-from-subdir="" data-photo-class="riskassessment-from-riskassessment-create-<?php echo $risk->id; ?>"/>
																	<i class="fas fa-pencil-alt button-icon"></i>
																</div>
															<?php else : ?>
																<div class="wpeo-button button-square-50 button-transparent wpeo-tooltip-event" aria-label="<?php echo $langs->trans('PermissionDenied'); ?>" value="<?php echo $time_spent->timespent_id ?>">
																	<i class="fas fa-pencil-alt button-icon"></i>
																</div>
															<?php endif; ?>
															<?php if ($permissiontodelete) : ?>
																<div class="riskassessment-task-timespent-delete wpeo-button button-square-50 button-transparent" value="<?php echo $time_spent->timespent_id ?>">
																	<i class="fas fa-trash button-icon"></i>
																</div>
															<?php endif; ?>
														</div>
													</div>
												</li>
											<?php endforeach; ?>
										<?php endif; ?>
									</ul>
								</div>
							</div>
						</div>
						<!-- Modal-Footer -->
						<div class="modal-footer">
							<?php if ($permissiontoadd) : ?>
								<div class="wpeo-button riskassessment-task-save button-green" value="<?php echo $related_task->id ?>">
									<i class="fas fa-save"></i> <?php echo $langs->trans('UpdateData'); ?>
								</div>
							<?php else : ?>
								<div class="wpeo-button button-grey wpeo-tooltip-event" aria-label="<?php echo $langs->trans('PermissionDenied') ?>">
									<i class="fas fa-save"></i> <?php echo $langs->trans('UpdateData'); ?>
								</div>
							<?php endif;?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
		<!-- RISK ASSESSMENT TASK EDIT MODALS END -->
		<!-- RISK ASSESSMENT TASK TIMESPENT EDIT MODALS START -->
		<?php if (!empty($allTimeSpentArray) && $allTimeSpentArray > 0) : ?>
			<?php foreach ($allTimeSpentArray as $time_spent) :?>
				<div class="riskassessment-task-timespent-edit-modal">
					<div class="wpeo-modal modal-riskassessment-task-timespent" id="risk_assessment_task_timespent_edit<?php echo $time_spent->timespent_id ?>">
						<div class="modal-container wpeo-modal-event">
							<!-- Modal-Header -->
							<div class="modal-header">
								<h2 class="modal-title"><?php echo $langs->trans('TaskTimeSpentEdit') . ' ' . $related_task->getNomUrl(0, 'withproject') ?></h2>
								<div class="modal-close"><i class="fas fa-times"></i></div>
							</div>
							<!-- Modal EDIT RISK ASSESSMENT TASK Content-->
							<div class="modal-content" id="#modalContent<?php echo $time_spent->timespent_id ?>">
								<div class="riskassessment-task-timespent-container" value="<?php echo $related_task->id; ?>">
									<div class="riskassessment-task-timespent-edit">
										<span class="title"><?php echo $langs->trans('TimeSpent'); ?></span>
										<span class="title"><?php echo $langs->trans('Date'); ?></span>
										<?php print $form->selectDate($time_spent->timespent_datehour, 'RiskassessmentTaskTimespentDateEdit'.$time_spent->timespent_id, 1, 1, 0, 'riskassessment_task_timespent_form', 1, 0); ?>
										<span class="title"><?php echo $langs->trans('Comment'); ?> <input type="text" class="riskassessment-task-timespent-comment" name="comment" value="<?php echo $time_spent->timespent_note; ?>"></span>
										<span class="title"><?php echo $langs->trans('Duration'); ?></span>
										<span class="time"><?php print '<input type="number" placeholder="minutes" class="riskassessment-task-timespent-duration" name="timespentDuration" value="'.($time_spent->timespent_duration/60).'">'; ?></span>
									</div>
								</div>
							</div>
							<!-- Modal-Footer -->
							<div class="modal-footer">
								<?php if ($permissiontoadd) : ?>
									<div class="wpeo-button riskassessment-task-timespent-save button-green" value="<?php echo $time_spent->timespent_id ?>">
										<i class="fas fa-save"></i> <?php echo $langs->trans('UpdateData'); ?>
									</div>
								<?php else : ?>
									<div class="wpeo-button button-grey wpeo-tooltip-event" aria-label="<?php echo $langs->trans('PermissionDenied') ?>">
										<i class="fas fa-save"></i> <?php echo $langs->trans('UpdateData'); ?>
									</div>
								<?php endif;?>
							</div>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
		<!-- RISK ASSESSMENT TASK TIMESPENT EDIT MODALS END -->
	</div>
</div>
