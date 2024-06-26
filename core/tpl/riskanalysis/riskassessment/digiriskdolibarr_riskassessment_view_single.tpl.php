<!-- RISK EVALUATION SINGLE START -->
<?php if ($showSingle > 0) : ?>

<div class="risk-evaluation-container risk-evaluation-container-<?php echo $lastEvaluation->id ?>" value="<?php echo $risk->id ?>">
	<div class="risk-evaluation-single-content risk-evaluation-single-content-<?php echo $risk->id ?>">
		<div class="risk-evaluation-single risk-evaluation-single-<?php echo $risk->id ?>">
			<div class="risk-evaluation-cotation risk-evaluation-list modal-open" value="<?php echo $risk->id ?>" data-scale="<?php echo $lastEvaluation->getEvaluationScale() ?>">
				<span><?php echo ($lastEvaluation->method == 'standard' ? $defaultCotation[$lastEvaluation->cotation] ?: 0 : $lastEvaluation->cotation); ?></span>
			</div>
			<div class="photo riskassessment-photo-<?php echo $lastEvaluation->id; ?>" style="margin:auto">
				<?php
				print saturne_show_medias_linked('digiriskdolibarr', DOL_DATA_ROOT . '/' . ($lastEvaluation->entity > 1 ? $lastEvaluation->entity . '/' : '') . 'digiriskdolibarr/riskassessment/' . $lastEvaluation->ref, 'small', 1, 0, 0, 0, 50, 50, 0, 0, 1, '/riskassessment/' . $lastEvaluation->ref, $lastEvaluation, 'photo', 0, 0, 0, 1);
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
				<div class="risk-evaluation-comment wordbreak">
					<?php print nl2br(dol_trunc($lastEvaluation->comment, 120)); ?>
				</div>
			</div>
			<!-- BUTTON MODAL RISK EVALUATION ADD  -->
			<?php if ($contextpage != 'sharedrisk' && $contextpage != 'inheritedrisk') : ?>
				<?php if ($permissiontoadd) : ?>
					<div class="risk-evaluation-edit risk-evaluation-button wpeo-button button-square-40 button-transparent wpeo-tooltip-event modal-open" aria-label="<?php echo $langs->trans('EditRiskAssessment') ?>" value="<?php echo $lastEvaluation->id;?>">
						<input type="hidden" class="modal-options" data-modal-to-open="risk_evaluation_edit<?php echo $lastEvaluation->id ?>" data-from-id="<?php echo $lastEvaluation->id ?>"/>
						<i class="fas fa-pencil-alt button-icon"></i>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/digiriskdolibarr_riskassessment_view_edit_modal.tpl.php'; ?>

<!-- RISK EVALUATION SINGLE END -->
