<!-- RISK EVALUATION SINGLE START -->
<div class="risk-evaluation-container risk-evaluation-container-<?php echo $lastEvaluation->id ?>" value="<?php echo $risk->id ?>">
	<div class="risk-evaluation-single-content risk-evaluation-single-content-<?php echo $risk->id ?>">
		<div class="risk-evaluation-single risk-evaluation-single-<?php echo $risk->id ?>">
			<div class="risk-evaluation-cotation risk-evaluation-list modal-open" value="<?php echo $risk->id ?>" data-scale="<?php echo $lastEvaluation->get_evaluation_scale() ?>">
				<span><?php echo $lastEvaluation->cotation ?: 0; ?></span>
			</div>
			<div class="risk-evaluation-photo risk-evaluation-photo-<?php echo $lastEvaluation->id > 0 ? $lastEvaluation->id : 0 ; echo $risk->id > 0 ? ' risk-' . $risk->id : ' risk-new' ?> open-medias-linked" value="<?php echo $lastEvaluation->id ?>">
				<?php
				$riskAssessment = $lastEvaluation;
				$view = 1;
				include DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/core/tpl/medias/digiriskdolibarr_photo_view.tpl.php';
				$view = 0;
				?>
			</div>
			<div class="risk-evaluation-content">
				<div class="risk-evaluation-data">
					<!-- BUTTON MODAL RISK EVALUATION LIST  -->
					<span class="risk-evaluation-reference" value="<?php echo $risk->id ?>"><?php echo $lastEvaluation->ref; ?></span>
					<span class="risk-evaluation-date">
						<i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', (($conf->global->DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE && ( ! empty($lastEvaluation->date_riskassessment))) ? $lastEvaluation->date_riskassessment : $lastEvaluation->date_creation)); ?>
					</span>
					<span class="risk-evaluation-author">
						<?php $userAuthor = $usersList[$lastEvaluation->fk_user_creat?:$user->id];
						echo getNomUrlUser($userAuthor); ?>
					</span>
				</div>
				<div class="risk-evaluation-comment">
					<?php print nl2br(dol_trunc($lastEvaluation->comment, 120)); ?>
				</div>
			</div>
			<!-- BUTTON MODAL RISK EVALUATION ADD  -->
			<?php if ($contextpage != 'sharedrisk' && $contextpage != 'inheritedrisk') : ?>
				<?php if ($permissiontoadd) : ?>
					<div class="risk-evaluation-edit risk-evaluation-button wpeo-button button-square-40 button-transparent wpeo-tooltip-event modal-open" aria-label="<?php echo $langs->trans('EditRiskAssessment') ?>" value="<?php echo $lastEvaluation->id;?>">
						<i class="fas fa-pencil-alt button-icon"></i>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
	<?php require __DIR__ . '/digiriskdolibarr_riskassessment_view_edit_modal.tpl.php'; ?>
</div>
<!-- RISK EVALUATION SINGLE END -->
