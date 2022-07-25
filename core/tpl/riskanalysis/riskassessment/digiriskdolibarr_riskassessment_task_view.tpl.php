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
		<?php if ( ! empty($related_tasks) && $related_tasks > 0) : ?>
			<?php if ($conf->global->DIGIRISKDOLIBARR_SHOW_ALL_TASKS) : ?>
					<?php $nb_of_tasks_in_progress = 0 ?>
					<?php foreach ($related_tasks as $related_task) : ?>
						<?php if (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS) && $contextpage == 'sharedrisk') : ?>
							<?php $project = new Project($db);
							$project->fetch($related_task->fk_project);
							$result = !empty($conf->mc->sharings['project']) ? in_array($project->entity, $conf->mc->sharings['project']) : 0; ?>
						<?php else : ?>
							<?php $result = 1 ?>
						<?php endif; ?>
						<?php if ((($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_PROGRESS) ? $related_task->progress <= 100 : $related_task->progress < 100) && $result) : ?>
							<?php  $nb_of_tasks_in_progress++ ?>
							<div class="table-cell riskassessment-task-container riskassessment-task-container-<?php echo $related_task->id ?>" value="<?php echo $related_task->ref ?>">
							<input type="hidden" class="labelForDelete" value="<?php echo $langs->trans('DeleteTask') . ' ' . $related_task->ref . ' ?'; ?>">
							<div class="riskassessment-task-single-content riskassessment-task-single-content-<?php echo $risk->id ?>">
								<div class="riskassessment-task-single riskassessment-task-single-<?php echo $related_task->id ?>   wpeo-table table-row">
									<div class="riskassessment-task-content table-cell">
										<div class="riskassessment-task-data">
											<span class="riskassessment-task-reference" value="<?php echo $related_task->id ?>"><?php echo getNomUrlTask($related_task, 0, 'withproject'); ?></span>
											<span class="riskassessment-task-author">
												<?php $user->fetch($related_task->fk_user_creat); ?>
												<?php echo getNomUrl(0, '', 0, 0, 2, 0, '', '', -1, $user); ?>
											</span>
											<span class="riskassessment-task-date">
												<i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE && ( ! empty($related_task->date_start))) ? $related_task->date_start : $related_task->date_c)) . (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && ( ! empty($related_task->date_end))) ? ' - ' . date('d/m/Y', $related_task->date_end) : ''); ?>
											</span>
											<span class="riskassessment-task-timespent">
												<?php $allTimeSpentArray = $related_task->fetchAllTimeSpent($user, 'AND ptt.fk_task='.$related_task->id);
													$allTimeSpent = 0;
													foreach ($allTimeSpentArray as $timespent) {
														$allTimeSpent += $timespent->timespent_duration;
													}
												?>
												<i class="fas fa-clock"></i> <?php echo $allTimeSpent/60 . '/' . $related_task->planned_workload/60 ?>
											</span>
											<span class="riskassessment-task-progress progress-<?php echo $related_task->progress ? $related_task->progress : 0 ?>"><?php echo $related_task->progress ? $related_task->progress . " %" : 0 . " %" ?></span>
										</div>
										<div class="riskassessment-task-title">
											<?php if ($contextpage != 'sharedrisk' && $contextpage != 'inheritedrisk') : ?>
												<span class="riskassessment-task-progress-checkbox <?php echo ($contextpage != 'sharedrisk' && $contextpage != 'inheritedrisk') ? '' : 'riskassessment-task-progress-checkbox-readonly'?>">
													<input type="checkbox" id="" class="riskassessment-task-progress-checkbox<?php echo $related_task->id; echo($related_task->progress == 100) ? ' progress-checkbox-check' : ' progress-checkbox-uncheck' ?>" name="progress-checkbox" value="" <?php echo ($related_task->progress == 100) ? 'checked' : ''; ?>>
												</span>
											<?php endif; ?>
											<span class="riskassessment-task-author-label">
												<?php echo dol_trunc($related_task->label, 255, 'right', 'UTF-8', 0, $display = 1); ?>
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
										<div class="modal-container wpeo-modal-event">
											<div class="riskassessment-task-single" value="<?php echo $risk->id ?>">
												<div class="riskassessment-task-content riskassessment-task-ref-<?php echo $related_task->id ?>" value="<?php echo $related_task->ref ?>">
													<!-- Modal-Header -->
													<div class="modal-header">
														<div class="riskassessment-task-data">
															<span class="riskassessment-task-reference" value="<?php echo $related_task->id ?>"><?php echo getNomUrlTask($related_task, 0, 'withproject'); ?></span>
															<span class="riskassessment-task-author">
																		<?php $user->fetch($related_task->fk_user_creat); ?>
																		<?php echo getNomUrl(0, '', 0, 0, 2, 0, '', '', -1, $user); ?>
																	</span>
															<span class="riskassessment-task-date">
																		<i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE && ( ! empty($related_task->date_start))) ? $related_task->date_start : $related_task->date_c)) . (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && ( ! empty($related_task->date_end))) ? ' - ' . date('d/m/Y', $related_task->date_end) : ''); ?>
																	</span>
															<span class="riskassessment-task-timespent">
																		<?php $allTimeSpentArray = $related_task->fetchAllTimeSpent($user, 'AND ptt.fk_task='.$related_task->id);
																		$allTimeSpent = 0;
																		foreach ($allTimeSpentArray as $timespent) {
																			$allTimeSpent += $timespent->timespent_duration;
																		}
																		?>
																		<i class="fas fa-clock"></i> <?php echo $allTimeSpent/60 . '/' . $related_task->planned_workload/60 ?>
																	</span>
															<span class="riskassessment-task-progress progress-<?php echo $related_task->progress ? $related_task->progress : 0 ?>"><?php echo $related_task->progress ? $related_task->progress . " %" : 0 . " %" ?></span>
														</div>

														<div class="modal-close"><i class="fas fa-times"></i></div>
													</div>
													<!-- Modal-Content -->
													<div class="modal-content">
														<?php $allTimeSpentArray = $related_task->fetchAllTimeSpent($user, 'AND ptt.fk_task='.$related_task->id); ?>
														<div class="riskassessment-task-title">
															<span class="riskassessment-task-progress-checkbox">
																<input type="checkbox" id="" class="riskassessment-task-progress-checkbox<?php echo $related_task->id ?>" name="progress-checkbox" value="" <?php echo ($related_task->progress == 100) ? 'checked' : ''; ?>>
															</span>
															<input type="text" class="riskassessment-task-author-label riskassessment-task-label<?php echo $related_task->id ?>" name="label" value="<?php echo $related_task->label ?>">
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
															<div class="riskassessment-task-timespent">
																<div class="riskassessment-task-timespent-add-container">
																	<div class="timespent-date">
																		<span class="title"><?php echo $langs->trans('Date'); ?></span>
																		<?php print $form->selectDate(dol_now('tzuser'), 'RiskassessmentTaskTimespentDate', 1, 1, 0, 'riskassessment_task_timespent_form', 1, 0); ?>
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

																<?php if ( ! empty($allTimeSpentArray) && $allTimeSpentArray > 0) : ?>
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
																									<?php echo getNomUrl(0, '', 0, 0, 2, 0, '', '', -1, $user); ?>
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
																									<h2 class="modal-title"><?php echo $langs->trans('TaskTimeSpentEdit') . ' ' . getNomUrlTask($related_task, 0, 'withproject') ?></h2>
																									<div class="modal-close"><i class="fas fa-times"></i></div>
																								</div>
																								<!-- Modal EDIT RISK ASSESSMENT TASK Content-->
																								<div class="modal-content" id="#modalContent<?php echo $time_spent->timespent_id ?>">
																									<div class="riskassessment-task-timespent-container" value="<?php echo $related_task->id; ?>">
																										<div class="riskassessment-task-timespent">
																											<span class="title"><?php echo $langs->trans('TimeSpent'); ?></span>
																											<span class="title"><?php echo $langs->trans('Date'); ?></span>
																											<?php print $form->selectDate($time_spent->timespent_datehour, 'RiskassessmentTaskTimespentDate', 1, 1, 0, 'riskassessment_task_timespent_form', 1, 0); ?>
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
						<?php endif; ?>
					<?php endforeach; ?>
					<?php if ($nb_of_tasks_in_progress == 0) : ?>
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
					<?php $related_task = end($related_tasks); ?>
					<div class="table-cell riskassessment-task-container riskassessment-task-container-<?php echo $related_task->id ?>" value="<?php echo $related_task->ref ?>">
						<input type="hidden" class="labelForDelete" value="<?php echo $langs->trans('DeleteTask') . ' ' . $related_task->ref . ' ?'; ?>">
						<div class="riskassessment-task-single-content riskassessment-task-single-content-<?php echo $risk->id ?>">
							<div class="riskassessment-task-single riskassessment-task-single-<?php echo $related_task->id ?>   wpeo-table table-row">
								<div class="riskassessment-task-content table-cell">
									<div class="riskassessment-task-data">
										<span class="riskassessment-task-reference" value="<?php echo $related_task->id ?>"><?php echo getNomUrlTask($related_task, 0, 'withproject'); ?></span>
										<span class="riskassessment-task-author">
											<?php $user->fetch($related_task->fk_user_creat); ?>
											<?php echo getNomUrl(0, '', 0, 0, 2, 0, '', '', -1, $user); ?>
										</span>
										<span class="riskassessment-task-date">
											<i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE && ( ! empty($related_task->date_start))) ? $related_task->date_start : $related_task->date_c)) . (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && ( ! empty($related_task->date_end))) ? ' - ' . date('d/m/Y', $related_task->date_end) : ''); ?>
										</span>
										<span class="riskassessment-task-timespent">
											<?php $allTimeSpentArray = $related_task->fetchAllTimeSpent($user, 'AND ptt.fk_task='.$related_task->id);
											$allTimeSpent = 0;
											foreach ($allTimeSpentArray as $timespent) {
												$allTimeSpent += $timespent->timespent_duration;
											}
											?>
											<i class="fas fa-clock"></i> <?php echo $allTimeSpent/60 . '/' . $related_task->planned_workload/60 ?>
										</span>
										<span class="riskassessment-task-progress progress-<?php echo $related_task->progress ? $related_task->progress : 0 ?>"><?php echo $related_task->progress ? $related_task->progress . " %" : 0 . " %" ?></span>
									</div>
									<div class="riskassessment-task-title">
										<?php if ($contextpage != 'sharedrisk' && $contextpage != 'inheritedrisk') : ?>
											<span class="riskassessment-task-progress-checkbox <?php echo ($contextpage != 'sharedrisk' && $contextpage != 'inheritedrisk') ? '' : 'riskassessment-task-progress-checkbox-readonly'?>">
												<input type="checkbox" id="" class="riskassessment-task-progress-checkbox<?php echo $related_task->id; echo($related_task->progress == 100) ? ' progress-checkbox-check' : ' progress-checkbox-uncheck' ?>" name="progress-checkbox" value="" <?php echo ($related_task->progress == 100) ? 'checked' : ''; ?>>
											</span>
										<?php endif; ?>
										<span class="riskassessment-task-author-label">
											<?php echo dol_trunc($related_task->label, 48, 'wrap', 'UTF-8', 0, $display = 1); ?>
										</span>
									</div>
								</div>
								<!-- BUTTON MODAL RISK ASSESSMENT TASK EDIT  -->
								<?php if ($contextpage != 'sharedrisk' && $contextpage != 'inheritedrisk') : ?>
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
								<?php endif; ?>
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
								<span class="title"><?php echo $langs->trans('Label'); ?> <input type="text" class="riskassessment-task-label" name="label" value=""></span>
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
								</div>
								<div class="notice-close"><i class="fas fa-times"></i></div>
							</div>
						</div>
						<?php if ( ! empty($related_tasks) && $related_tasks > 0) : ?>
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
																<span class="riskassessment-task-author">
																	<?php $user->fetch($related_task->fk_user_creat); ?>
																	<?php echo getNomUrl(0, '', 0, 0, 2, 0, '', '', -1, $user); ?>
																</span>
																<span class="riskassessment-task-date">
																	<i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE && ( ! empty($related_task->date_start))) ? $related_task->date_start : $related_task->date_c)) . (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && ( ! empty($related_task->date_end))) ? ' - ' . date('d/m/Y', $related_task->date_end) : ''); ?>
																</span>
																<span class="riskassessment-task-progress progress-<?php echo $related_task->progress ? $related_task->progress : 0 ?>"><?php echo $related_task->progress ? $related_task->progress . " %" : 0 . " %" ?></span>
															</div>
															<div class="riskassessment-task-title">
																<?php if ($contextpage != 'sharedrisk' && $contextpage != 'inheritedrisk') : ?>
																	<span class="riskassessment-task-progress-checkbox <?php echo ($contextpage != 'sharedrisk' && $contextpage != 'inheritedrisk') ? '' : 'riskassessment-task-progress-checkbox-readonly'?>">
																		<input type="checkbox" id="" class="riskassessment-task-progress-checkbox<?php echo $related_task->id; echo($related_task->progress == 100) ? ' progress-checkbox-check' : ' progress-checkbox-uncheck' ?>" name="progress-checkbox" value="" <?php echo ($related_task->progress == 100) ? 'checked' : ''; ?>>
																	</span>
																<?php endif;?>
																<span class="riskassessment-task-label">
																	<?php echo dol_trunc($related_task->label, 48, 'wrap', 'UTF-8', 0, $display = 1); ?>
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
															<h2 class="modal-title"><?php echo $langs->trans('TaskEdit') . ' ' . $related_task->ref ?></h2>
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
</div>
