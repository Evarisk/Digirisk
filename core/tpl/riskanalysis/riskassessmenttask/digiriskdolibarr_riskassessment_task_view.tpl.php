<?php
$related_tasks = $risk->get_related_tasks($risk); ?>
<div class="wpeo-table riskassessment-tasks riskassessment-tasks<?php echo $risk->id ?>" value="<?php echo $risk->id ?>">
	<div class="table-cell riskassessment-task-listing-wrapper riskassessment-task-listing-wrapper-<?php echo $risk->id ?>">
		<div class="table-cell-header">
			<div class="table-cell-header-label"><strong><?php echo $langs->trans('ListingHeaderTask'); ?> (<?php echo $related_tasks ? count($related_tasks) : 0; ?>)</strong></div>
			<div class="table-cell-header-actions">
				<?php if ($permissiontoread) : ?>
					<div class="wpeo-button riskassessment-task-list button-square-40 button-grey wpeo-tooltip-event modal-open risk-list-button" aria-label="<?php echo $langs->trans('ListRiskAssessmentTask') ?>" value="<?php echo $risk->id;?>">
						<i class="button-icon fas fa-list-ul"></i>
					</div>
				<?php else : ?>
					<div class="wpeo-button button-square-40 button-grey wpeo-tooltip-event risk-list-button" aria-label="<?php echo $langs->trans('PermissionDenied') ?>" value="<?php echo $risk->id;?>">
						<i class="button-icon fas fa-list-ul"></i>
					</div>
				<?php endif; ?>
				<?php if ($contextpage != 'sharedrisk' && $contextpage != 'inheritedrisk') : ?>
					<?php if ($permissiontoadd) : ?>
						<div class="riskassessment-task-add wpeo-button button-square-40 button-primary wpeo-tooltip-event modal-open risk-list-button" aria-label="<?php echo $langs->trans('AddRiskAssessmentTask') ?>" value="<?php echo $risk->id;?>">
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
					if (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS) && $contextpage == 'sharedrisk') {
						$project = new Project($db);
						$project->fetch($related_task->fk_project);
						$result = !empty($conf->mc->sharings['project']) ? in_array($project->entity, $conf->mc->sharings['project']) : 0;
					} else {
						$result = 1;
					}
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
						<div class="riskassessment-task-single-content riskassessment-task-single-content-<?php echo $risk->id ?>">
							<div class="riskassessment-task-single riskassessment-task-single-<?php echo $risk->id ?>">
								<div class="riskassessment-task-content">
									<div class="riskassessment-task-data" style="justify-content: center;">
										<span class="name"><?php echo $result > 0 ? $langs->trans('NoTaskLinked') : $langs->trans('NoTaskShared'); ?></span>
										<?php if ($contextpage != 'sharedrisk' && $contextpage != 'inheritedrisk') : ?>
											<?php if ($permissiontoadd) : ?>
												<div class="riskassessment-task-add wpeo-button button-square-40 button-primary wpeo-tooltip-event modal-open risk-list-button" aria-label="<?php echo $langs->trans('AddRiskAssessmentTask') ?>" value="<?php echo $risk->id;?>">
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
				require __DIR__ . '/digiriskdolibarr_riskassessment_task_single_view.tpl.php'; ?>
			<?php endif; ?>
		<?php else : ?>
			<div class="riskassessment-task-listing-wrapper riskassessment-task-listing-wrapper-<?php echo $risk->id ?>">
				<div class="riskassessment-task-container riskassessment-no-task">
					<div class="riskassessment-task-single-content riskassessment-task-single-content-<?php echo $risk->id ?>">
						<div class="riskassessment-task-single riskassessment-task-single-<?php echo $risk->id ?>">
							<div class="riskassessment-task-content">
								<div class="riskassessment-task-data" style="justify-content: center;">
									<span class="name"><?php echo $langs->trans('NoTaskLinked'); ?></span>
									<?php if ($contextpage != 'sharedrisk' && $contextpage != 'inheritedrisk') : ?>
										<?php if ($permissiontoadd) : ?>
											<div class="riskassessment-task-add wpeo-button button-square-40 button-primary wpeo-tooltip-event modal-open risk-list-button" aria-label="<?php echo $langs->trans('AddRiskAssessmentTask') ?>" value="<?php echo $risk->id;?>">
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
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<?php $riskAssessment   = new RiskAssessment($db);
		$riskAssessment->method = $lastEvaluation->method ? $lastEvaluation->method : "standard" ; ?>

		<!-- RISK ASSESSMENT TASK ADD MODAL-->
		<div class="riskassessment-task-add-modal">
			<div class="wpeo-modal modal-risk" id="risk_assessment_task_add<?php echo $risk->id?>">
				<div class="modal-container wpeo-modal-event">
					<!-- Modal-Header -->
					<div class="modal-header">
						<?php $project->fetch($conf->global->DIGIRISKDOLIBARR_DU_PROJECT); ?>
						<h2 class="modal-title"><?php echo $langs->trans('TaskCreate') . ' ' . $refTaskMod->getNextValue('', $task) . '  ' . $langs->trans('AT') . '  ' . $langs->trans('Project') . '  ' . $project->getNomUrl() ?><i class="fas fa-info-circle wpeo-tooltip-event" aria-label="<?php echo $langs->trans('HowToSetDUProject'); ?>"></i></h2>
						<div class="modal-close"><i class="fas fa-times"></i></div>
					</div>
					<!-- Modal ADD RISK ASSESSMENT TASK Content-->
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
								<span class="title"><?php echo $langs->trans('Label'); ?></span>
								<input type="text" class="riskassessment-task-label" name="label" value="">
								<div class="riskassessment-task-date wpeo-gridlayout grid-2">
									<div>
										<span class="title"><?php echo $langs->trans('DateStart'); ?></span>
										<?php print $form->selectDate(dol_now('tzuser'), 'RiskassessmentTaskDateStart' . $risk->id, 1, 1, 0, '', 1, 1); ?>
									</div>
									<div>
										<span class="title"><?php echo $langs->trans('Deadline'); ?></span>
										<?php print $form->selectDate(-1,'RiskassessmentTaskDateEnd'. $risk->id, 1, 1, 0, '', 1, 1); ?>
									</div>
								</div>
								<span class="title"><?php echo $langs->trans('Budget'); ?></span>
								<input type="text" class="riskassessment-task-budget" name="budget" value="">
							</div>
						</div>
					</div>
					<!-- Modal-Footer -->
					<div class="modal-footer">
						<?php if ($permissiontoadd) : ?>
							<div class="wpeo-button riskassessment-task-create button-blue button-disable modal-close" value="<?php echo $risk->id ?>">
								<i class="fas fa-plus"></i> <?php echo $langs->trans('Add'); ?>
							</div>
						<?php else : ?>
							<div class="wpeo-button button-grey wpeo-tooltip-event" aria-label="<?php echo $langs->trans('PermissionDenied') ?>">
								<i class="fas fa-plus"></i> <?php echo $langs->trans('Add'); ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<!-- RISK ASSESSMENT TASK ADD MODAL END-->
		<!-- RISK ASSESSMENT TASK LIST MODAL-->
		<div class="riskassessment-task-list-modal">
			<div class="wpeo-modal" id="risk_assessment_task_list<?php echo $risk->id ?>">
				<div class="modal-container wpeo-modal-event">
					<!-- Modal-Header -->
					<div class="modal-header">
						<?php $project->fetch($conf->global->DIGIRISKDOLIBARR_DU_PROJECT); ?>
						<h2 class="modal-title"><?php echo $langs->trans('TaskList') . ' ' . $risk->ref . '  ' . $langs->trans('AT') . '  ' . $langs->trans('Project') . '  ' . $project->getNomUrl()  ?><i class="fas fa-info-circle wpeo-tooltip-event" aria-label="<?php echo $langs->trans('HowToSetDUProject'); ?>"></i></h2>
						<div class="modal-close"><i class="fas fa-times"></i></div>
					</div>
					<!-- MODAL RISK ASSESSMENT TASK LIST CONTENT -->
					<div class="modal-content" id="#modalContent" value="<?php echo $risk->id ?>">
						<!--	RISKASSESSMENT TASK-->
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
								<div class="riskassessment-task-list-content" value="<?php echo $risk->id ?>">
									<ul class="riskassessment-task-list riskassessment-task-list-<?php echo $related_task->id ?>">
										<li>
											<?php require __DIR__ . '/digiriskdolibarr_riskassessment_task_single_view.tpl.php'; ?>
										</li>
										<hr>
									</ul>
								</div>
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
		<!-- RISK ASSESSMENT TASK LIST MODAL END-->
	</div>
</div>
