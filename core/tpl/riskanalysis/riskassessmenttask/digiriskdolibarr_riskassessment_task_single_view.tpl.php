<div class="table-cell riskassessment-task-container riskassessment-task-container-<?php echo $related_task->id ?>" value="<?php echo $related_task->ref ?>">
	<input type="hidden" class="labelForDelete" value="<?php echo $langs->trans('DeleteTask') . ' ' . $related_task->ref . ' ?'; ?>">
	<div class="riskassessment-task-single-content riskassessment-task-single-content-<?php echo $risk->id ?>">
		<div class="riskassessment-task-single riskassessment-task-single-<?php echo $related_task->id ?>   wpeo-table table-row">
			<div class="riskassessment-task-content table-cell">
				<div class="riskassessment-task-data">
					<span class="riskassessment-task-reference" value="<?php echo $related_task->id ?>"><?php echo $related_task->getNomUrlTask(0, 'withproject'); ?></span>
					<span class="riskassessment-task-author">
						<?php $user->fetch($related_task->fk_user_creat); ?>
						<?php echo getNomUrlUser($user); ?>
					</span>
					<span class="riskassessment-task-date">
						<i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE && ( ! empty($related_task->date_start))) ? $related_task->date_start : $related_task->date_c)) . (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && ( ! empty($related_task->date_end))) ? ' - ' . date('d/m/Y', $related_task->date_end) : ''); ?>
					</span>
					<span class="riskassessment-task-timespent">
						<?php $allTimeSpentArray = $related_task->fetchAllTimeSpentAllUser('AND ptt.fk_task='.$related_task->id, 'task_datehour', 'DESC');
						$allTimeSpent = 0;
						foreach ($allTimeSpentArray as $timespent) {
							$allTimeSpent += $timespent->timespent_duration;
						}
						?>
						<i class="fas fa-clock"></i> <?php echo $allTimeSpent/60 . '/' . $related_task->planned_workload/60 ?>
					</span>
					<span class="riskassessment-task-budget"><i class="fas fa-coins"></i> <?php echo price($related_task->budget_amount, 0, $langs, 1, 0, 0, $conf->currency); ?></span>
					<span class="riskassessment-task-progress <?php echo $related_task->getTaskProgressColorClass($task_progress); ?>"><?php echo $task_progress ? $task_progress . " %" : 0 . " %" ?></span>
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
			<?php endif; ?>
		</div>
		<!-- RISK ASSESSMENT TASK EDIT MODAL-->
		<div class="riskassessment-task-edit-modal">
			<div class="wpeo-modal modal-riskassessment-task" id="risk_assessment_task_edit<?php echo $related_task->id ?>">
				<div class="modal-container wpeo-modal-event" style="max-width: 900px; max-height: 720px;">
					<div class="riskassessment-task-single" value="<?php echo $risk->id ?>">
						<div class="riskassessment-task-content riskassessment-task-ref-<?php echo $related_task->id ?>" value="<?php echo $related_task->ref ?>">
							<!-- Modal-Header -->
							<div class="modal-header">
								<div class="riskassessment-task-data">
									<span class="riskassessment-task-reference" value="<?php echo $related_task->id ?>"><?php echo $related_task->getNomUrlTask(0, 'withproject'); ?></span>
									<span class="riskassessment-task-author">
										<?php $user->fetch($related_task->fk_user_creat); ?>
										<?php echo getNomUrlUser($user); ?>
									</span>
									<span class="riskassessment-task-date">
										<i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE && ( ! empty($related_task->date_start))) ? $related_task->date_start : $related_task->date_c)) . (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && ( ! empty($related_task->date_end))) ? ' - ' . date('d/m/Y', $related_task->date_end) : ''); ?>
									</span>
									<span class="riskassessment-task-timespent">
										<?php $allTimeSpentArray = $related_task->fetchAllTimeSpentAllUser('AND ptt.fk_task='.$related_task->id, 'task_datehour', 'DESC');
										$allTimeSpent = 0;
										foreach ($allTimeSpentArray as $timespent) {
											$allTimeSpent += $timespent->timespent_duration;
										}
										?>
										<i class="fas fa-clock"></i> <?php echo $allTimeSpent/60 . '/' . $related_task->planned_workload/60 ?>
									</span>
									<span class="riskassessment-task-budget"><i class="fas fa-coins"></i> <?php echo price($related_task->budget_amount, 0, $langs, 1, 0, 0, $conf->currency); ?></span>
									<span class="riskassessment-task-progress <?php echo $related_task->getTaskProgressColorClass($task_progress); ?>"><?php echo $task_progress ? $task_progress . " %" : 0 . " %" ?></span>
								</div>
								<div class="modal-close"><i class="fas fa-times"></i></div>
							</div>
							<!-- Modal-Content -->
							<div class="modal-content" style="height: 600px;">
								<?php $allTimeSpentArray = $related_task->fetchAllTimeSpentAllUser('AND ptt.fk_task='.$related_task->id, 'task_datehour', 'DESC'); ?>
								<div class="riskassessment-task-title">
									<?php if (!$conf->global->DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS) : ?>
										<span class="riskassessment-task-progress-checkbox">
											<input type="checkbox" id="" class="riskassessment-task-progress-checkbox<?php echo $related_task->id ?>" name="progress-checkbox" value="" <?php echo ($task_progress == 100) ? 'checked' : ''; ?>>
										</span>
									<?php endif; ?>
									<span class="title"><?php echo $langs->trans('Label'); ?></span>
									<input type="text" class="riskassessment-task-author-label riskassessment-task-label<?php echo $related_task->id ?>" name="label" value="<?php echo $related_task->label; ?>">
								</div>
								<div class="riskassessment-task-date wpeo-gridlayout grid-2">
									<div>
										<span class="title"><?php echo $langs->trans('DateStart'); ?></span>
										<?php print $form->selectDate($related_task->date_start ?: -1, 'RiskassessmentTaskDateStart'.$related_task->id, 1, 1, 0, '', 1, 1); ?>
									</div>
									<div>
										<span class="title"><?php echo $langs->trans('Deadline'); ?></span>
										<?php print $form->selectDate($related_task->date_end ?: -1,'RiskassessmentTaskDateEnd'.$related_task->id, 1, 1, 0, '', 1, 1); ?>
									</div>
								</div>
								<span class="title"><?php echo $langs->trans('Budget'); ?></span>
								<input type="text" class="riskassessment-task-budget<?php echo $related_task->id ?>" name="budget" value="<?php echo price2num($related_task->budget_amount); ?>">
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
									<div class="riskassessment-task-timespent">
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
										<?php if (!empty($allTimeSpentArray) && $allTimeSpentArray > 0) : ?>
											<?php foreach ($allTimeSpentArray as $time_spent) : ?>
												<div class="riskassessment-task-timespent-list-content" value="<?php echo $risk->id ?>">
													<ul class="riskassessment-task-timespent-list riskassessment-task-timespent-list-<?php echo $related_task->id ?>">
														<li class="riskassessment-task riskassessment-task<?php echo $related_task->id ?>" value="<?php echo $related_task->id ?>">
															<input type="hidden" class="labelForDelete" value="<?php echo $langs->trans('DeleteTaskTimeSpent', $time_spent->timespent_duration/60) . ' ' . $related_task->ref . ' ?'; ?>">
															<div class="riskassessment-task-container riskassessment-task-ref-<?php echo $related_task->id ?>" value="<?php echo $related_task->ref ?>">
																<div class="riskassessment-task-content">
																	<div class="riskassessment-task-single">
																		<div class="riskassessment-task-content">
																			<div class="riskassessment-task-data">
																				<span class="riskassessment-task-author">
																					<?php $user->fetch($related_task->fk_user_creat); ?>
																					<?php echo getNomUrlUser($user); ?>
																				</span>
																				<span class="riskassessment-task-timespent-date">
																					<i class="fas fa-calendar-alt"></i> <?php echo dol_print_date($time_spent->timespent_datehour, 'dayhour'); ?>
																				</span>
																				<span class="riskassessment-task-timespent">
																					<i class="fas fa-clock"></i> <?php echo $time_spent->timespent_duration/60 . ' mins'; ?>
																				</span>
																			</div>
																			<div class="riskassessment-task-timespent-title">
																				<span class="riskassessment-task-timespent-comment">
																					<?php echo $time_spent->timespent_note; ?>
																				</span>
																			</div>
																		</div>
																	</div>
																</div>
																<!-- BUTTON MODAL RISK ASSESSMENT TASK TIMESPENT EDIT  -->
																<div class="riskassessment-task-actions wpeo-gridlayout grid-2 grid-gap-0">
																	<?php if ($permissiontoadd) : ?>
																		<div class="riskassessment-task-timespent-edit wpeo-button button-square-50 button-grey modal-open" value="<?php echo $time_spent->timespent_id ?>">
																			<i class="fas fa-pencil-alt button-icon"></i>
																		</div>
																	<?php else : ?>
																		<div class="wpeo-button button-square-50 button-grey wpeo-tooltip-event" aria-label="<?php echo $langs->trans('PermissionDenied'); ?>" value="<?php echo $time_spent->timespent_id ?>">
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
															<!-- RISK ASSESSMENT TASK TIMESPENT EDIT MODAL-->
															<div class="riskassessment-task-timespent-edit-modal">
																<div class="wpeo-modal modal-riskassessment-task-timespent" id="risk_assessment_task_timespent_edit<?php echo $time_spent->timespent_id ?>">
																	<div class="modal-container wpeo-modal-event">
																		<!-- Modal-Header -->
																		<div class="modal-header">
																			<h2 class="modal-title"><?php echo $langs->trans('TaskTimeSpentEdit') . ' ' . $task->getNomUrlTask(0, 'withproject') ?></h2>
																			<div class="modal-close"><i class="fas fa-times"></i></div>
																		</div>
																		<!-- Modal EDIT RISK ASSESSMENT TASK Content-->
																		<div class="modal-content" id="#modalContent<?php echo $time_spent->timespent_id ?>">
																			<div class="riskassessment-task-timespent-container" value="<?php echo $related_task->id; ?>">
																				<div class="riskassessment-task-timespent">
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
														</li>
													</ul>
												</div>
											<?php endforeach; ?>
										<?php endif; ?>
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
			</div>
		</div>
		<!-- RISK ASSESSMENT TASK EDIT MODAL END -->
	</div>
</div>
