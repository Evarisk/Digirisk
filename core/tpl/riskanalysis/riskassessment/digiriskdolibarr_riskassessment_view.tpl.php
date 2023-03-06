<?php
$allRiskAssessment = $riskAssessmentsOrderedByRisk[$risk->id];
$lastEvaluation    = array_filter($allRiskAssessment, function($lastRiskAssessment) {
	return $lastRiskAssessment->status == 1;
});
if (is_array($allRiskAssessment) && !empty($allRiskAssessment)) :
	usort($allRiskAssessment, function ($riskAssessmentComparer, $riskAssessmentCompared) {
	return $riskAssessmentComparer->date_creation < $riskAssessmentCompared->date_creation;
	}); ?>
	<div class="table-cell-header">
		<div class="table-cell-header-label"><strong><?php echo $langs->trans('ListingHeaderCotation'); ?> (<?php echo count($allRiskAssessment); ?>)</strong></div>
		<div class="table-cell-header-actions">
			<?php if ($permissiontoread) : ?>
				<div class="risk-evaluation-list risk-evaluation-button wpeo-button button-square-40 button-grey wpeo-tooltip-event modal-open" aria-label="<?php echo $langs->trans('RiskAssessmentList') ?>" value="<?php echo $risk->id ?>">
					<i class="fas fa-list button-icon"></i>
				</div>
			<?php else : ?>
				<div class="wpeo-button button-square-40 button-grey wpeo-tooltip-event" aria-label="<?php echo $langs->trans('PermissionDenied') ?>">
					<i class="fas fa-plus"></i> <?php echo $langs->trans('RiskAssessmentList'); ?>
				</div>
			<?php endif; ?>
			<?php if ($contextpage != 'sharedrisk' && $contextpage != 'inheritedrisk') : ?>
				<?php if ($permissiontoadd) : ?>
					<div class="risk-evaluation-add risk-evaluation-button wpeo-button button-square-40 button-primary wpeo-tooltip-event modal-open" aria-label="<?php echo $langs->trans('AddRiskAssessment') ?>" value="<?php echo $risk->id;?>">
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
	<?php
	print '<div class="risk-evaluation-list-content risk-evaluation-list-content-'. $risk->id .'">';
	if ($conf->global->DIGIRISKDOLIBARR_SHOW_ALL_RISKASSESSMENTS) {
		foreach ($allRiskAssessment as $lastEvaluation) {
			require __DIR__ . '/digiriskdolibarr_riskassessment_view_single.tpl.php';
		}
	} else {
		if (is_array($lastEvaluation) && !empty($lastEvaluation)) {
			$lastEvaluation = array_shift($lastEvaluation);
			require __DIR__ . '/digiriskdolibarr_riskassessment_view_single.tpl.php';
		}
	}
	print '</div>'; ?>
	<!-- RISK EVALUATION LIST MODAL -->
	<div class="risk-evaluation-list-modal risk-evaluation-list-modal-<?php echo $risk->id ?>">
		<div class="wpeo-modal" id="risk_evaluation_list<?php echo $risk->id ?>">
			<div class="modal-container wpeo-modal-event">
				<!-- Modal-Header -->
				<div class="modal-header">
					<h2 class="modal-title"><?php echo $langs->trans('EvaluationList') . ' ' . $risk->ref ?></h2>
					<div class="modal-close"><i class="fas fa-times"></i></div>
				</div>
				<!-- MODAL RISK EVALUATION LIST CONTENT -->
				<div class="modal-content" id="#modalContent" value="<?php echo $risk->id ?>">
					<!-- RISK ASSESSMENT -->
					<div class="messageSuccessEvaluationEdit notice hidden">
						<input type="hidden" class="valueForEditEvaluation1" value="<?php echo $langs->trans('TheRiskAssessment') . ' ' ?>">
						<input type="hidden" class="valueForEditEvaluation2" value="<?php echo ' ' . $langs->trans('HasBeenEditedF') ?>">
						<div class="wpeo-notice notice-success riskassessment-edit-success-notice">
							<div class="notice-content">
								<div class="notice-title"><?php echo $langs->trans('RiskAssessmentWellEdited') ?></div>
								<div class="notice-subtitle">
									<span class="text"></span>
								</div>
							</div>
							<div class="notice-close"><i class="fas fa-times"></i></div>
						</div>
					</div>
					<div class="messageErrorEvaluationEdit notice hidden">
						<input type="hidden" class="valueForEditEvaluation1" value="<?php echo $langs->trans('TheRiskAssessment') . ' ' ?>">
						<input type="hidden" class="valueForEditEvaluation2" value="<?php echo ' ' . $langs->trans('HasNotBeenEditedF') ?>">
						<div class="wpeo-notice notice-warning riskassessment-edit-error-notice">
							<div class="notice-content">
								<div class="notice-title"><?php echo $langs->trans('RiskAssessmentNotEdited') ?></div>
							</div>
							<div class="notice-close"><i class="fas fa-times"></i></div>
						</div>
					</div>
					<div class="messageSuccessEvaluationDelete notice hidden">
						<input type="hidden" class="valueForDeleteEvaluation1" value="<?php echo $langs->trans('TheRiskAssessment') . ' ' ?>">
						<input type="hidden" class="valueForDeleteEvaluation2" value="<?php echo ' ' . $langs->trans('HasBeenDeletedF') ?>">
						<div class="wpeo-notice notice-success riskassessment-delete-success-notice">
							<div class="notice-content">
								<div class="notice-title"><?php echo $langs->trans('RiskAssessmentWellDeleted') ?></div>
								<div class="notice-subtitle">
									<span class="text"></span>
								</div>
							</div>
							<div class="notice-close"><i class="fas fa-times"></i></div>
						</div>
					</div>
					<div class="messageErrorEvaluationDelete notice hidden">
						<input type="hidden" class="valueForDeleteEvaluation1" value="<?php echo $langs->trans('TheRiskAssessment') . ' ' ?>">
						<input type="hidden" class="valueForDeleteEvaluation2" value="<?php echo ' ' . $langs->trans('HasNotBeenDeletedF') ?>">
						<div class="wpeo-notice notice-warning riskassessment-delete-error-notice">
							<div class="notice-content">
								<div class="notice-title"><?php echo $langs->trans('RiskAssessmentNotDeleted') ?></div>
							</div>
							<div class="notice-close"><i class="fas fa-times"></i></div>
						</div>
					</div>
					<div class="risk-evaluations-list-content" value="<?php echo $risk->id ?>">
						<ul class="risk-evaluations-list risk-evaluations-list-<?php echo $risk->id ?>">
							<?php if ( ! empty($allRiskAssessment)) :
								foreach ($allRiskAssessment as $lastEvaluation) : ?>
							<div class="risk-evaluation-in-modal risk-evaluation-container-<?php echo $lastEvaluation->id ?>" value="<?php echo $lastEvaluation->id ?>">
								<input type="hidden" class="labelForDelete" value="<?php echo $langs->trans('DeleteEvaluation') . ' ' . $lastEvaluation->ref . ' ?'; ?>">
								<div class="risk-evaluation-container risk-evaluation-ref-<?php echo $lastEvaluation->id ?>" value="<?php echo $lastEvaluation->ref ?>">
									<div class="risk-evaluation-single">
										<div class="risk-evaluation-cotation" data-scale="<?php echo $lastEvaluation->get_evaluation_scale() ?>">
											<span><?php echo $lastEvaluation->cotation ?: 0; ?></span>
										</div>
										<div class="risk-evaluation-photo risk-evaluation-photo-<?php echo $lastEvaluation->id > 0 ? $lastEvaluation->id : 0 ; echo $risk->id > 0 ? ' risk-' . $risk->id : ' risk-new' ?>">
											<?php
											$riskAssessment = $lastEvaluation;
											$view = 1;
											include DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/core/tpl/medias/digiriskdolibarr_photo_view.tpl.php';
											$view = 0;
											?>
										</div>
										<div class="risk-evaluation-content">
											<div class="risk-evaluation-data">
												<span class="risk-evaluation-reference"><?php echo $lastEvaluation->ref; ?></span>
												<span class="risk-evaluation-date">
													<i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', (($conf->global->DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE && ( ! empty($lastEvaluation->date_riskassessment))) ? $lastEvaluation->date_riskassessment : $lastEvaluation->date_creation)); ?>
												</span>
												<span class="risk-evaluation-author">
													<?php $userAuthor = $usersList[$lastEvaluation->fk_user_creat?:$user->id];
													echo getNomUrlUser($userAuthor); ?>
												</span>
											</div>
											<div class="risk-evaluation-comment">
												<?php echo dol_trunc($lastEvaluation->comment, 120); ?>
											</div>
										</div>
										<!-- BUTTON MODAL RISK EVALUATION EDIT  -->
										<?php if ($contextpage != 'sharedrisk' && $contextpage != 'inheritedrisk') : ?>
											<div class="risk-evaluation-actions wpeo-gridlayout grid-2 grid-gap-0">
												<?php if ($permissiontoadd) : ?>
													<div class="risk-evaluation-edit wpeo-button button-square-50 button-grey modal-open" value="<?php echo $lastEvaluation->id ?>">
														<i class="fas fa-pencil-alt button-icon"></i>
													</div>
												<?php else : ?>
													<div class="wpeo-button button-square-50 button-grey wpeo-tooltip-event"  aria-label="<?php echo $langs->trans('PermissionDenied'); ?>" value="<?php echo $lastEvaluation->id ?>">
														<i class="fas fa-pencil-alt button-icon"></i>
													</div>
												<?php endif; ?>
												<?php if ($permissiontodelete) : ?>
													<div class="risk-evaluation-delete wpeo-button button-square-50 button-transparent">
														<i class="fas fa-trash button-icon"></i>
													</div>
												<?php endif; ?>
											</div>
										<?php endif; ?>
									</div>
								</div>
								</li>
								<hr>
							</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</ul>
					</div>
					<!-- RISK EVALUATION LIST MODAL END-->
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
	<?php
else : ?>
<div class="table-cell-header">
	<div class="table-cell-header-label"><strong><?php echo $langs->trans('ListingHeaderCotation'); ?> (<?php echo $allRiskAssessment ? count($allRiskAssessment) : 0; ?>)</strong></div>
	<div class="table-cell-header-actions">
		<?php if ($permissiontoread) : ?>
			<div class="risk-evaluation-list risk-evaluation-button wpeo-button button-square-40 button-grey wpeo-tooltip-event modal-open" aria-label="<?php echo $langs->trans('RiskAssessmentList') ?>" value="<?php echo $risk->id ?>">
				<i class="fas fa-list button-icon"></i>
			</div>
		<?php else : ?>
			<div class="wpeo-button button-square-40 button-grey wpeo-tooltip-event" aria-label="<?php echo $langs->trans('PermissionDenied') ?>">
				<i class="fas fa-plus"></i> <?php echo $langs->trans('RiskAssessmentList'); ?>
			</div>
		<?php endif; ?>

		<?php if ($contextpage != 'sharedrisk' && $contextpage != 'inheritedrisk') : ?>
			<?php if ($permissiontoadd) : ?>
				<div class="risk-evaluation-add risk-evaluation-button wpeo-button button-square-40 button-primary wpeo-tooltip-event modal-open" aria-label="<?php echo $langs->trans('AddRiskAssessment') ?>" value="<?php echo $risk->id;?>">
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
<div class="risk-evaluation-container risk-evaluation-container-<?php echo $lastEvaluation->id ?>">
	<div class="risk-evaluation-single-content risk-evaluation-single-content-<?php echo $risk->id ?>">
		<div class="risk-evaluation-single risk-evaluation-single-<?php echo $risk->id ?>">
			<div class="risk-evaluation-content">
				<div class="risk-evaluation-data">
					<span class="name"><?php echo $langs->trans('NoRiskAssessment'); ?></span>
				</div>
			</div>
			<?php if ($contextpage != 'sharedrisk' && $contextpage != 'inheritedrisk') : ?>
				<?php if ($permissiontoadd) : ?>
					<div class="risk-evaluation-add wpeo-button button-square-40 button-primary wpeo-tooltip-event modal-open" aria-label="<?php echo $langs->trans('AddRiskAssessment') ?>" value="<?php echo $risk->id ?>">
						<i class="fas fa-plus button-icon"></i>
					</div>
				<?php else : ?>
					<div class="wpeo-button button-square-40 button-grey wpeo-tooltip-event" aria-label="<?php echo $langs->trans('PermissionDenied') ?>" value="<?php echo $risk->id;?>">
						<i class="fas fa-plus button-icon"></i>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php endif;
$evaluation->method = $lastEvaluation->method ?: "standard" ;
?>
<!-- RISK EVALUATION ADD MODAL-->
<div class="risk-evaluation-add-modal">
	<div class="wpeo-modal modal-risk" id="risk_evaluation_add<?php echo $risk->id?>" value="<?php echo $risk->id?>">
		<div class="modal-container wpeo-modal-event">
			<!-- Modal-Header -->
			<div class="modal-header">
				<h2 class="modal-title"><?php echo $langs->trans('EvaluationCreate') . ' ' . $riskAssessmentNextValue ?></h2>
				<div class="modal-close"><i class="fas fa-times"></i></div>
			</div>

			<!-- Modal-ADD Evaluation Content-->
			<div class="modal-content" id="#modalContent<?php echo $risk->id?>">
				<!-- PHOTO -->
				<div class="messageSuccessSavePhoto notice hidden">
					<div class="wpeo-notice notice-success save-photo-success-notice">
						<div class="notice-content">
							<div class="notice-title"><?php echo $langs->trans('PhotoWellSaved') ?></div>
						</div>
						<div class="notice-close"><i class="fas fa-times"></i></div>
					</div>
				</div>
				<div class="messageErrorSavePhoto notice hidden">
					<div class="wpeo-notice notice-warning save-photo-error-notice">
						<div class="notice-content">
							<div class="notice-title"><?php echo $langs->trans('PhotoNotSaved') ?></div>
						</div>
						<div class="notice-close"><i class="fas fa-times"></i></div>
					</div>
				</div>
				<div class="risk-evaluation-container <?php echo $evaluation->method; ?>">
					<div class="risk-evaluation-header">
						<?php if ($conf->global->DIGIRISKDOLIBARR_ADVANCED_RISKASSESSMENT_METHOD) : ?>
							<?php if ( $conf->global->DIGIRISKDOLIBARR_MULTIPLE_RISKASSESSMENT_METHOD == 1 ) : ?>
								<div class="wpeo-button evaluation-standard select-evaluation-method<?php echo ($lastEvaluation->method == "standard") ? " selected button-blue" : " button-grey" ?> button-radius-2">
									<span><?php echo $langs->trans('SimpleCotation') ?></span>
								</div>
								<div class="wpeo-button evaluation-advanced select-evaluation-method<?php echo ($lastEvaluation->method == "advanced") ? " selected button-blue" : " button-grey" ?> button-radius-2">
									<span><?php echo $langs->trans('AdvancedCotation') ?></span>
								</div>
							<?php else : ?>
								<div class="wpeo-button evaluation-standard select-evaluation-method<?php echo ($lastEvaluation->method == "standard") ? " selected button-blue" : " button-grey button-disable" ?> button-radius-2">
									<span><?php echo $langs->trans('SimpleCotation') ?></span>
								</div>
								<div class="wpeo-button evaluation-advanced select-evaluation-method<?php echo ($lastEvaluation->method == "advanced") ? " selected button-blue" : " button-grey button-disable" ?> button-radius-2">
									<span><?php echo $langs->trans('AdvancedCotation') ?></span>
								</div>
							<?php endif; ?>
							<i class="fas fa-info-circle wpeo-tooltip-event" aria-label="<?php echo $langs->trans("HowToSetMultipleRiskAssessmentMethod") ?>"></i>
						<?php endif; ?>
						<input class="risk-evaluation-method" type="hidden" value="<?php echo ($lastEvaluation->method == "standard") ? "standard" : "advanced" ?>">
						<input class="risk-evaluation-multiple-method" type="hidden" value="<?php echo $conf->global->DIGIRISKDOLIBARR_MULTIPLE_RISKASSESSMENT_METHOD ?>">
						<div class="wpeo-button open-media-gallery add-media modal-open" value="0">
							<input type="hidden" class="type-from" value="riskassessment"/>
							<span><i class="fas fa-camera"></i>  <?php echo $langs->trans('AddMedia') ?></span>
						</div>
					</div>
					<div class="risk-evaluation-content-wrapper">
						<div class="risk-evaluation-content">
							<div class="cotation-container">
								<div class="cotation-standard" style="<?php echo ($lastEvaluation->method !== "advanced") ? " display:block" : " display:none" ?>">
									<span class="title"><i class="fas fa-chart-line"></i><?php echo ' ' . $langs->trans('Cotation'); ?><required>*</required></span>
									<div class="cotation-listing wpeo-gridlayout grid-4 grid-gap-0">
										<?php
										$defaultCotation = array(0, 48, 51, 100);
										if ( ! empty($defaultCotation)) :
											foreach ($defaultCotation as $request) :
												$evaluation->cotation = $request; ?>
												<div data-id="<?php echo 0; ?>"
													 data-evaluation-method="standard"
													 data-evaluation-id="<?php echo $request; ?>"
													 data-variable-id="<?php echo 152 + $request; ?>"
													 data-seuil="<?php echo  $evaluation->get_evaluation_scale(); ?>"
													 data-scale="<?php echo  $evaluation->get_evaluation_scale(); ?>"
													 class="risk-evaluation-cotation cotation"><?php echo $request; ?></div>
											<?php endforeach;
										endif; ?>
									</div>
								</div>
								<input class="risk-evaluation-seuil" type="hidden">
								<?php $evaluation_method  = $advanced_method_cotation_array[0];
								$evaluation_method_survey = $evaluation_method['option']['variable']; ?>
								<div class="wpeo-gridlayout cotation-advanced" style="<?php echo ($lastEvaluation->method == "advanced") ? " display:block" : " display:none" ?>">
									<input type="hidden" class="digi-method-evaluation-id" value="<?php echo $risk->id ; ?>" />
									<textarea style="display: none" name="evaluation_variables" class="tmp_evaluation_variable"><?php echo '{}'; ?></textarea>
									<p><i class="fas fa-info-circle"></i> <?php echo $langs->trans('SelectCotation') ?></p>
									<div class="wpeo-table evaluation-method table-flex table-<?php echo count($evaluation_method_survey) + 1; ?>">
										<div class="table-row table-header">
											<div class="table-cell">
												<span></span>
											</div>
											<?php for ( $l = 0; $l < count($evaluation_method_survey); $l++ ) : ?>
												<div class="table-cell">
													<span><?php echo $l; ?></span>
												</div>
											<?php endfor; ?>
										</div>
										<?php $l = 0; ?>
										<?php foreach ($evaluation_method_survey as $critere) :
											$name = strtolower($critere['name']); ?>
											<div class="table-row">
												<div class="table-cell"><?php echo $critere['name'] ; ?></div>
												<?php foreach ($critere['option']['survey']['request'] as $request) : ?>
													<div class="table-cell can-select cell-<?php echo  $evaluation_id ? $evaluation_id : 0 ; ?>"
														 data-type="<?php echo $name ?>"
														 data-id="<?php echo  $risk->id ? $risk->id : 0 ; ?>"
														 data-evaluation-id="<?php echo $evaluation_id ? $evaluation_id : 0 ; ?>"
														 data-variable-id="<?php echo $l ; ?>"
														 data-seuil="<?php echo  $request['seuil']; ?>">
														<?php echo  $request['question'] ; ?>
													</div>
												<?php endforeach; $l++; ?>
											</div>
										<?php endforeach; ?>
									</div>
								</div>
							</div>
						</div>
						<?php $riskAssessment = $evaluation; ?>
						<?php include DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/core/tpl/medias/digiriskdolibarr_photo_view.tpl.php'; ?>

						<div class="risk-evaluation-calculated-cotation" style="<?php echo ($lastEvaluation->method == "advanced") ? " display:block" : " display:none" ?>">
							<span class="title"><i class="fas fa-chart-line"></i> <?php echo $langs->trans('CalculatedCotation'); ?><required>*</required></span>
							<div data-scale="1" class="risk-evaluation-cotation cotation">
								<span><?php echo 0 ?></span>
							</div>
						</div>
						<div class="risk-evaluation-comment">
							<span class="title"><i class="fas fa-comment-dots"></i> <?php echo $langs->trans('Comment'); ?></span>
							<?php print '<textarea name="evaluationComment' . $risk->id . '" rows="' . ROWS_2 . '">' . ('') . '</textarea>' . "\n"; ?>
						</div>
					</div>
					<?php if ($conf->global->DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE) : ?>
						<div class="risk-evaluation-date">
							<span class="title"><?php echo $langs->trans('Date'); ?></span>
							<?php print $form->selectDate('', 'RiskAssessmentDateCreate0', 0, 0, 0, '', 1, 1); ?>
						</div>
					<?php endif; ?>
					<div class="element-linked-medias element-linked-medias-0 risk-<?php echo $risk->id ?>">
						<div class="medias"><i class="fas fa-picture-o"></i><?php echo $langs->trans('Medias'); ?></div>
						<?php
						$relativepath = 'digiriskdolibarr/medias/thumbs';
						print digirisk_show_medias_linked('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/tmp/' . $risk->ref . '/', 'small', 0, 0, 0, 0, 150, 150, 1, 0, 0, $lastEvaluation->element . '/tmp/' . $risk->ref);
						?>
					</div>
				</div>
				<!-- RISK EVALUATION SINGLE -->
				<?php if ( ! empty($lastEvaluation) && $lastEvaluation > 0) : ?>
					<div class="risk-evaluation-container last-risk-assessment risk-evaluation-container-<?php echo $lastEvaluation->id ?>">
						<h2><?php echo $langs->trans('LastRiskAssessment') . ' ' . $risk->ref; ?></h2>
						<div class="risk-evaluation-single-content risk-evaluation-single-content-<?php echo $risk->id ?>">
							<div class="risk-evaluation-single">
								<div class="risk-evaluation-cotation risk-evaluation-list" value="<?php echo $risk->id ?>" data-scale="<?php echo $lastEvaluation->get_evaluation_scale() ?>">
									<span><?php echo $lastEvaluation->cotation ?: 0; ?></span>
								</div>
								<div class="risk-evaluation-photo risk-evaluation-photo-<?php echo $lastEvaluation->id > 0 ? $lastEvaluation->id : 0 ; echo $risk->id > 0 ? ' risk-' . $risk->id : ' risk-new' ?>">
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
										<span class="risk-evaluation-reference risk-evaluation-list" value="<?php echo $risk->id ?>"><?php echo $lastEvaluation->ref; ?></span>
										<span class="risk-evaluation-date">
											<i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', (($conf->global->DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE && ( ! empty($lastEvaluation->date_riskassessment))) ? $lastEvaluation->date_riskassessment : $lastEvaluation->date_creation)); ?>
										</span>
										<span class="risk-evaluation-author">
											<?php $userAuthor = $usersList[$lastEvaluation->fk_user_creat?:$user->id];
											echo getNomUrlUser($userAuthor); ?>
										</span>
									</div>
									<div class="risk-evaluation-comment">
										<?php echo dol_trunc($lastEvaluation->comment, 120); ?>
									</div>
								</div>
							</div>
						</div>
					</div>
				<?php endif; ?>
			</div>
			<!-- Modal-Footer -->
			<div class="modal-footer">
				<?php if ($permissiontoadd) : ?>
					<div class="risk-evaluation-create wpeo-button button-blue button-disable modal-close" value="<?php echo $risk->id ?>">
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
