<?php
$related_tasks = $risk->get_related_tasks($risk); ?>
<div class="wpeo-table riskassessment-tasks riskassessment-tasks<?php echo $risk->id ?>" value="<?php echo $risk->id ?>">
	<?php if (!empty($related_tasks) && $related_tasks > 0) : ?>
		<div class="table-cell riskassessment-task-listing-wrapper riskassessment-task-listing-wrapper-<?php echo $risk->id ?>">

			<div class="table-cell-header">
				<div class="table-cell-header-label"><strong><?php echo $langs->trans('ListingHeaderTask'); ?> (<?php echo count( $related_tasks ); ?>)</strong></div>
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

					<?php if ($permissiontoadd) : ?>
						<div class="riskassessment-task-add wpeo-button button-square-40 button-primary wpeo-tooltip-event modal-open risk-list-button" aria-label="<?php echo $langs->trans('AddRiskAssessmentTask') ?>" value="<?php echo $risk->id;?>">
							<i class="fas fa-plus button-icon"></i>
						</div>
					<?php else : ?>
						<div class="wpeo-button button-square-40 button-grey wpeo-tooltip-event risk-list-button" aria-label="<?php echo $langs->trans('PermissionDenied') ?>" value="<?php echo $risk->id;?>">
							<i class="fas fa-plus button-icon"></i>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<?php if ($conf->global->DIGIRISKDOLIBARR_SHOW_ALL_TASKS) : ?>
				<?php $nb_of_tasks_in_progress = 0 ?>
				<?php foreach ($related_tasks as $related_task) : ?>
					<?php if ((($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_PROGRESS) ? $related_task->progress <= 100 : $related_task->progress < 100)) : ?>
						<?php  $nb_of_tasks_in_progress++ ?>
						<div class="table-cell riskassessment-task-container riskassessment-task-container-<?php echo $related_task->id ?>" value="<?php echo $related_task->ref ?>">
						<input type="hidden" class="labelForDelete" value="<?php echo $langs->trans('DeleteTask') . ' ' . $related_task->ref . ' ?'; ?>">
						<div class="riskassessment-task-single-content riskassessment-task-single-content-<?php echo $risk->id ?>">
							<div class="riskassessment-task-single riskassessment-task-single-<?php echo $related_task->id ?>   wpeo-table table-row">
								<div class="riskassessment-task-content table-cell">
									<div class="riskassessment-task-data">
										<span class="riskassessment-task-reference" value="<?php echo $related_task->id ?>"><?php echo getNomUrlTask($related_task, 0, 'withproject'); ?></span>
										<span class="riskassessment-task-date">
											<i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE  && (!empty($related_task->date_start))) ? $related_task->date_start : $related_task->date_c)) . (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && (!empty($related_task->date_end))) ? ' - ' . date('d/m/Y', $related_task->date_end) : ''); ?>
										</span>
										<span class="riskassessment-task-progress progress-<?php echo $related_task->progress ? $related_task->progress : 0 ?>"><?php echo $related_task->progress ? $related_task->progress . " %" : 0 . " %" ?></span>
									</div>
									<div class="riskassessment-task-title">
										<span class="riskassessment-task-author">
											<?php $user->fetch($related_task->fk_user_creat); ?>
											<?php echo getNomUrl( 0, '', 0, 0, 2 ,0,'','',-1,$user); ?>
										</span>
										<span class="riskassessment-task-author-label">
											<?php echo $related_task->label; ?>
										</span>
									</div>
								</div>
								<!-- BUTTON MODAL RISK ASSESSMENT TASK EDIT  -->
								<div class="table-cell riskassessment-task-actions wpeo-gridlayout grid-2 grid-gap-0">
									<?php if ($permissiontoadd) : ?>
										<div class="riskassessment-task-edit wpeo-button button-square-40 button-transparent modal-open" value="<?php echo $related_task->id ?>">
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
							</div>
							<!-- RISK ASSESSMENT TASK EDIT MODAL-->
							<div class="riskassessment-task-edit-modal">
								<div class="wpeo-modal modal-riskassessment-task" id="risk_assessment_task_edit<?php echo $related_task->id ?>">
									<div class="modal-container wpeo-modal-event">
										<!-- Modal-Header -->
										<div class="modal-header">
											<?php $project->fetch($conf->global->DIGIRISKDOLIBARR_DU_PROJECT); ?>
											<h2 class="modal-title"><?php echo $langs->trans('TaskEdit') . ' ' . $risk->ref . '  ' . $langs->trans('AT') . '  ' . $langs->trans('Project') . '  ' . $project->getNomUrl()  ?><i class="fas fa-info-circle wpeo-tooltip-event" aria-label="<?php echo $langs->trans('HowToSetDUProject'); ?>"></i></h2>
											<div class="modal-close"><i class="fas fa-times"></i></div>
										</div>
										<!-- Modal EDIT RISK ASSESSMENT TASK Content-->
										<div class="modal-content" id="#modalContent<?php echo $related_task->id ?>">
											<div class="riskassessment-task-container">
												<div class="riskassessment-task">
													<span class="title"><?php echo $langs->trans('Label'); ?> <input type="text" class="riskassessment-task-label<?php echo $related_task->id ?>" name="label" value="<?php echo $related_task->label ?>"></span>
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
							</div>
							<!-- RISK ASSESSMENT TASK EDIT MODAL END -->
						</div>
					</div>
					<?php endif; ?>
				<?php endforeach; ?>
				<?php if ($nb_of_tasks_in_progress == 0) : ?>
					<div class="riskassessment-task-container riskassessment-no-task">
						<div class="riskassessment-task-single-content riskassessment-task-single-content-<?php echo $risk->id ?>">
							<div class="riskassessment-task-single riskassessment-task-single-<?php echo $risk->id ?>">
								<div class="riskassessment-task-content">
									<div class="riskassessment-task-data" style="justify-content: center;">
										<span class="name"><?php echo $langs->trans('NoTaskLinked'); ?></span>

										<?php if ($permissiontoadd) : ?>
											<div class="riskassessment-task-add wpeo-button button-square-40 button-primary wpeo-tooltip-event modal-open risk-list-button" aria-label="<?php echo $langs->trans('AddRiskAssessmentTask') ?>" value="<?php echo $risk->id;?>">
												<i class="fas fa-plus button-icon"></i>
											</div>
										<?php else : ?>
											<div class="wpeo-button button-square-40 button-grey wpeo-tooltip-event risk-list-button" aria-label="<?php echo $langs->trans('PermissionDenied') ?>" value="<?php echo $risk->id;?>">
												<i class="fas fa-plus button-icon"></i>
											</div>
										<?php endif; ?>
									</div>
								</div>
							</div>
						</div>
					</div>
				<?php endif; ?>
			<?php else : ?>
				<?php $related_task = end($related_tasks); ?>
				<div class="table-cell riskassessment-task-container riskassessment-task-container-<?php echo $related_task->id ?>" value="<?php echo $related_task->ref ?>">
					<input type="hidden" class="labelForDelete" value="<?php echo $langs->trans('DeleteTask') . ' ' . $related_task->ref . ' ?'; ?>">
					<div class="riskassessment-task-single-content riskassessment-task-single-content-<?php echo $risk->id ?>">
						<div class="riskassessment-task-single riskassessment-task-single-<?php echo $related_task->id ?>   wpeo-table table-row">
							<div class="riskassessment-task-content table-cell">
								<div class="riskassessment-task-data">
									<span class="riskassessment-task-reference" value="<?php echo $related_task->id ?>"><?php echo getNomUrlTask($related_task, 0, 'withproject'); ?></span>
									<span class="riskassessment-task-date">
											<i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE  && (!empty($related_task->date_start))) ? $related_task->date_start : $related_task->date_c)) . (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && (!empty($related_task->date_end))) ? ' - ' . date('d/m/Y', $related_task->date_end) : ''); ?>
										</span>
									<span class="riskassessment-task-progress progress-<?php echo $related_task->progress ? $related_task->progress : 0 ?>"><?php echo $related_task->progress ? $related_task->progress . " %" : 0 . " %" ?></span>
								</div>
								<div class="riskassessment-task-title">
										<span class="riskassessment-task-author">
											<?php $user->fetch($related_task->fk_user_creat); ?>
											<?php echo getNomUrl( 0, '', 0, 0, 2 ,0,'','',-1,$user); ?>
										</span>
									<span class="riskassessment-task-author-label">
											<?php echo $related_task->label; ?>
										</span>
								</div>
							</div>
							<!-- BUTTON MODAL RISK ASSESSMENT TASK EDIT  -->
							<div class="table-cell riskassessment-task-actions wpeo-gridlayout grid-2 grid-gap-0">
								<?php if ($permissiontoadd) : ?>
									<div class="riskassessment-task-edit wpeo-button button-square-50 button-transparent modal-open" value="<?php echo $related_task->id ?>">
										<i class="fas fa-pencil-alt button-icon"></i>
									</div>
								<?php else : ?>
									<div class="wpeo-button button-square-50 button-grey wpeo-tooltip-event" aria-label="<?php echo $langs->trans('PermissionDenied'); ?>" value="<?php echo $related_task->id ?>">
										<i class="fas fa-pencil-alt button-icon"></i>
									</div>
								<?php endif; ?>
								<?php if ($permissiontodelete) : ?>
									<div class="riskassessment-task-delete wpeo-button button-square-50 button-transparent" value="<?php echo $related_task->id ?>">
										<i class="fas fa-trash button-icon"></i>
									</div>
								<?php endif; ?>
							</div>
						</div>
						<!-- RISK ASSESSMENT TASK EDIT MODAL-->
						<div class="riskassessment-task-edit-modal">
							<div class="wpeo-modal modal-riskassessment-task" id="risk_assessment_task_edit<?php echo $related_task->id ?>">
								<div class="modal-container wpeo-modal-event">
									<!-- Modal-Header -->
									<div class="modal-header">
										<?php $project->fetch($conf->global->DIGIRISKDOLIBARR_DU_PROJECT); ?>
										<h2 class="modal-title"><?php echo $langs->trans('TaskEdit') . ' ' . $risk->ref . '  ' . $langs->trans('AT') . '  ' . $langs->trans('Project') . '  ' . $project->getNomUrl()  ?><i class="fas fa-info-circle wpeo-tooltip-event" aria-label="<?php echo $langs->trans('HowToSetDUProject'); ?>"></i></h2>
										<div class="modal-close"><i class="fas fa-times"></i></div>
									</div>
									<!-- Modal EDIT RISK ASSESSMENT TASK Content-->
									<div class="modal-content" id="#modalContent<?php echo $related_task->id ?>">
										<div class="riskassessment-task-container">
											<div class="riskassessment-task">
												<span class="title"><?php echo $langs->trans('Label'); ?> <input type="text" class="riskassessment-task-label<?php echo $related_task->id ?>" name="label" value="<?php echo $related_task->label ?>"></span>
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
						</div>
						<!-- RISK ASSESSMENT TASK EDIT MODAL END -->
					</div>
				</div>
			<?php endif; ?>
		</div>
	<?php else : ?>
		<div class="riskassessment-task-listing-wrapper riskassessment-task-listing-wrapper-<?php echo $risk->id ?>">
			<div class="riskassessment-task-container riskassessment-no-task">
				<div class="riskassessment-task-single-content riskassessment-task-single-content-<?php echo $risk->id ?>">
					<div class="riskassessment-task-single riskassessment-task-single-<?php echo $risk->id ?>">
						<div class="riskassessment-task-content">
							<div class="riskassessment-task-data" style="justify-content: center;">
								<span class="name"><?php echo $langs->trans('NoTaskLinked'); ?></span>

								<?php if ($permissiontoadd) : ?>
									<div class="riskassessment-task-add wpeo-button button-square-40 button-primary wpeo-tooltip-event modal-open risk-list-button" aria-label="<?php echo $langs->trans('AddRiskAssessmentTask') ?>" value="<?php echo $risk->id;?>">
										<i class="fas fa-plus button-icon"></i>
									</div>
								<?php else : ?>
									<div class="wpeo-button button-square-40 button-grey wpeo-tooltip-event risk-list-button" aria-label="<?php echo $langs->trans('PermissionDenied') ?>" value="<?php echo $risk->id;?>">
										<i class="fas fa-plus button-icon"></i>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<?php $cotation = new RiskAssessment($db);
	$cotation->method = $lastEvaluation->method ? $lastEvaluation->method : "standard" ; ?>

	<!-- RISK ASSESSMENT TASK ADD MODAL-->
	<div class="riskassessment-task-add-modal">
		<div class="wpeo-modal modal-risk" id="risk_assessment_task_add<?php echo $risk->id?>">
			<div class="modal-container wpeo-modal-event">
				<!-- Modal-Header -->
				<div class="modal-header">
					<?php $project->fetch($conf->global->DIGIRISKDOLIBARR_DU_PROJECT); ?>
					<h2 class="modal-title"><?php echo $langs->trans('TaskCreate') . ' ' .  $refTaskMod->getNextValue('', $task) . '  ' . $langs->trans('AT') . '  ' . $langs->trans('Project') . '  ' . $project->getNomUrl() ?><i class="fas fa-info-circle wpeo-tooltip-event" aria-label="<?php echo $langs->trans('HowToSetDUProject'); ?>"></i></h2>
					<div class="modal-close"><i class="fas fa-times"></i></div>
				</div>
				<!-- Modal ADD RISK ASSESSMENT TASK Content-->
				<div class="modal-content" id="#modalContent<?php echo $risk->id ?>">
					<div class="riskassessment-task-container">
						<div class="riskassessment-task">
							<span class="title"><?php echo $langs->trans('Label'); ?> <input type="text" class="riskassessment-task-label" name="label" value=""></span>
						</div>
					</div>
				</div>
				<!-- Modal-Footer -->
				<div class="modal-footer">
					<?php if ($permissiontoadd) : ?>
						<div class="wpeo-button riskassessment-task-create button-blue button-disable" value="<?php echo $risk->id ?>">
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
							</div>
							<div class="notice-close"><i class="fas fa-times"></i></div>
						</div>
					</div>
					<?php if (!empty($related_tasks) && $related_tasks > 0) : ?>
						<?php foreach ($related_tasks as $related_task) : ?>
							<div class="riskassessment-task-list-content" value="<?php echo $risk->id ?>">
								<ul class="riskassessment-task-list riskassessment-task-list-<?php echo $related_task->id ?>">
									<li class="riskassessment-task riskassessment-task<?php echo $related_task->id ?>" value="<?php echo $related_task->id ?>">
									<input type="hidden" class="labelForDelete" value="<?php echo $langs->trans('DeleteTask') . ' ' . $related_task->ref . ' ?'; ?>">
										<div class="riskassessment-task-container riskassessment-task-ref-<?php echo $related_task->id ?>" value="<?php echo $related_task->ref ?>">
											<div class="riskassessment-task-content">
												<div class="riskassessment-task-single">
													<div class="riskassessment-task-content">
														<div class="riskassessment-task-data">
															<span class="riskassessment-task-reference" value="<?php echo $related_task->id ?>"><?php echo getNomUrlTask($related_task, 0, 'withproject'); ?></span>
															<span class="riskassessment-task-date">
																<i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE && (!empty($related_task->date_start))) ? $related_task->date_start : $related_task->date_c)) . (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && (!empty($related_task->date_end))) ? ' - ' . date('d/m/Y', $related_task->date_end) : ''); ?>
															</span>
															<span class="riskassessment-task-progress progress-<?php echo $related_task->progress ? $related_task->progress : 0 ?>"><?php echo $related_task->progress ? $related_task->progress . " %" : 0 . " %" ?></span>
														</div>
														<div class="riskassessment-task-title">
															<span class="riskassessment-task-author">
																<?php $user->fetch($related_task->fk_user_creat); ?>
																<?php echo getNomUrl( 0, '', 0, 0, 2 ,0,'','',-1,$user); ?>
															</span>
															<span class="riskassessment-task-label">
																<?php echo $related_task->label; ?>
															</span>
														</div>
													</div>
												</div>
											</div>
											<!-- BUTTON MODAL RISK ASSESSMENT TASK EDIT  -->
<!--											<div class="riskassessment-task-actions wpeo-gridlayout grid-2 grid-gap-0">-->
<!--												--><?php //if ($permissiontoadd) : ?>
<!--													<div class="riskassessment-task-edit wpeo-button button-square-50 button-grey modal-open" value="--><?php //echo $related_task->id ?><!--">-->
<!--														<i class="fas fa-pencil-alt button-icon"></i>-->
<!--													</div>-->
<!--												--><?php //else : ?>
<!--													<div class="wpeo-button button-square-50 button-grey wpeo-tooltip-event" aria-label="--><?php //echo $langs->trans('PermissionDenied'); ?><!--" value="--><?php //echo $related_task->id ?><!--">-->
<!--														<i class="fas fa-pencil-alt button-icon"></i>-->
<!--													</div>-->
<!--												--><?php //endif; ?>
<!--												--><?php //if ($permissiontodelete) : ?>
<!--													<div class="riskassessment-task-delete wpeo-button button-square-50 button-transparent">-->
<!--														<i class="fas fa-trash button-icon"></i>-->
<!--													</div>-->
<!--												--><?php //endif; ?>
<!--											</div>-->
										</div>
										<!-- RISK ASSESSMENT TASK EDIT MODAL-->
										<div class="riskassessment-task-edit-modal">
											<div class="wpeo-modal modal-riskassessment-task" id="risk_assessment_task_edit<?php echo $related_task->id ?>">
												<div class="modal-container wpeo-modal-event">
													<!-- Modal-Header -->
													<div class="modal-header">
														<h2 class="modal-title"><?php echo $langs->trans('TaskEdit') . ' ' .  $related_task->ref ?></h2>
														<div class="modal-close"><i class="fas fa-times"></i></div>
													</div>
													<!-- Modal EDIT RISK ASSESSMENT TASK Content-->
													<div class="modal-content" id="#modalContent<?php echo $related_task->id ?>">
														<div class="riskassessment-task-container">
															<div class="riskassessment-task">
																<span class="title"><?php echo $langs->trans('Label'); ?> <input type="text" class="riskassessment-task-label<?php echo $related_task->id ?>" name="label" value="<?php echo $related_task->label ?>"></span>
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
										</div>
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
