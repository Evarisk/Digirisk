<?php
$related_tasks = $risk->get_related_tasks($risk); ?>
<div class="riskassessment-tasks riskassessment-tasks<?php echo $risk->id ?>" value="<?php echo $risk->id ?>">
	<?php if (!empty($related_tasks) && $related_tasks > 0) : ?>
		<div class="riskassessment-task-listing-wrapper">
			<?php foreach ($related_tasks as $related_task) : ?>
				<div class="table-cell riskassessment-task-container riskassessment-task-container-<?php echo $related_task->id ?>" value="<?php echo $related_task->ref ?>">
					<input type="hidden" class="labelForDelete" value="<?php echo $langs->trans('DeleteTask') . ' ' . $related_task->ref . ' ?'; ?>">
					<div class="riskassessment-task-single-content riskassessment-task-single-content-<?php echo $risk->id ?>">
						<div class="riskassessment-task-single riskassessment-task-single-<?php echo $related_task->id ?>   wpeo-table table-row">
							<div class="riskassessment-task-content table-cell">
								<div class="riskassessment-task-data">
									<span class="riskassessment-task-reference" value="<?php echo $related_task->id ?>"><?php echo $related_task->getNomUrl(); ?></span>
									<span class="riskassessment-task-date">
										<i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', $related_task->date_c); ?>
									</span>
									<span class="riskassessment-task-progress progress-<?php echo $related_task->progress ? $related_task->progress : 0 ?>"><?php echo $related_task->progress ? $related_task->progress . " %" : 0 . " %" ?></span>
								</div>
								<div class="riskassessment-task-title">
									<span class="riskassessment-task-author">
										<?php $user->fetch($related_task->fk_user_creat); ?>
										<?php echo getNomUrl( 0, '', 0, 0, 2 ,0,'','',-1,$user); ?>
									</span>
									<?php echo $related_task->label; ?>
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
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="riskassessment-task-add-wrapper">
			<!-- BUTTON MODAL RISK ASSESSMENT TASK ADD  -->
			<?php if ($permissiontoadd) : ?>
				<div class="table-cell riskassessment-task-add wpeo-button button-square-40 button-primary wpeo-tooltip-event modal-open" aria-label="<?php echo $langs->trans('AddRiskAssessmentTask') ?>" value="<?php echo $risk->id;?>">
					<i class="fas fa-plus button-icon"></i>
				</div>
			<?php else : ?>
				<div class="table-cell wpeo-button button-square-40 button-grey wpeo-tooltip-event" aria-label="<?php echo $langs->trans('PermissionDenied') ?>" value="<?php echo $risk->id;?>">
					<i class="fas fa-plus button-icon"></i>
				</div>
			<?php endif; ?>
		</div>

	<?php else : ?>
		<div class="riskassessment-task-container riskassessment-no-task">
			<div class="riskassessment-task-single-content riskassessment-task-single-content-<?php echo $risk->id ?>">
				<div class="riskassessment-task-single riskassessment-task-single-<?php echo $risk->id ?>">
					<div class="riskassessment-task-content">
						<div class="riskassessment-task-data">
							<span class="name"><?php echo $langs->trans('NoTaskLinked'); ?></span>
						</div>
					</div>
					<!-- BUTTON MODAL RISK ASSESSMENT TASK ADD  -->
					<?php if ($permissiontoadd) : ?>
						<div class="riskassessment-task-add wpeo-button button-square-40 button-primary wpeo-tooltip-event modal-open" aria-label="<?php echo $langs->trans('AddRiskAssessmentTask') ?>" value="<?php echo $risk->id;?>">
							<i class="fas fa-plus button-icon"></i>
						</div>
					<?php else : ?>
						<div class="wpeo-button button-square-40 button-grey wpeo-tooltip-event" aria-label="<?php echo $langs->trans('PermissionDenied') ?>" value="<?php echo $risk->id;?>">
							<i class="fas fa-plus button-icon"></i>
						</div>
					<?php endif; ?>
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
</div>
