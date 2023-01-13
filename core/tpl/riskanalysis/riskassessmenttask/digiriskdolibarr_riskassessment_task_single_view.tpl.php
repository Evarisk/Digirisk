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
					<span class="riskassessment-task-timespent  riskassessment-total-task-timespent-<?php echo $related_task->id ?>">
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
	</div>
</div>
