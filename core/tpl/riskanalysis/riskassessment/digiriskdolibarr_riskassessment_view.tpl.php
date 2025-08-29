<?php
$allRiskAssessment = $riskAssessmentsOrderedByRisk[$risk->id];
$lastRiskAssessment    = array_filter($allRiskAssessment, function($lastRiskAssessment) {
	return $lastRiskAssessment->status == 1;
});

if (is_array($lastRiskAssessment) && !empty($lastRiskAssessment)) {
    $lastRiskAssessment = array_shift($lastRiskAssessment);
}

$defaultCotation = [0 => '0-47', 48 => '48-50', 51 => '51-80', 100 => '81-100'];

if (is_array($allRiskAssessment) && !empty($allRiskAssessment)) :
	usort($allRiskAssessment, function ($riskAssessmentComparer, $riskAssessmentCompared) {
	return $riskAssessmentComparer->date_creation < $riskAssessmentCompared->date_creation;
	}); ?>
	<div class="table-cell-header">
		<div class="table-cell-header-label"><strong><?php echo $langs->trans('ListingHeaderEvaluation'); ?> (<?php echo count($allRiskAssessment); ?>)</strong></div>
		<div class="table-cell-header-actions">
			<?php if ($permissiontoread) : ?>
				<div class="wpeo-button button-square-40 button-grey wpeo-tooltip-event modal-open" aria-label="<?php echo $langs->trans('RiskAssessmentList') ?>" value="<?php echo $risk->id ?>">
					<input type="hidden" class="modal-options" data-modal-to-open="risk_evaluation_list<?php echo $risk->id; ?>" data-from-id="0" data-from-type="riskassessment" data-from-subtype="photo" data-from-subdir="" data-photo-class="riskassessment-from-riskassessment-create-<?php echo $risk->id; ?>"/>
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
						<input type="hidden" class="modal-options" data-modal-to-open="risk_evaluation_add<?php echo $risk->id; ?>" data-from-id="0" data-from-type="riskassessment" data-from-subtype="photo" data-from-subdir="" data-photo-class="riskassessment-from-riskassessment-create-<?php echo $risk->id; ?>"/>
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
	<?php
	print '<div class="risk-evaluation-list-content risk-evaluation-list-content-'. $risk->id .'">';
	$riskAssessmentShown = 0;
	foreach ($allRiskAssessment as $lastEvaluation) {
		if ($conf->global->DIGIRISKDOLIBARR_SHOW_ALL_RISKASSESSMENTS || $riskAssessmentShown == 0) {
			$showSingle = 1;
			$riskAssessmentShown = 1;
		} else {
			$showSingle = 0;
		}
		require __DIR__ . '/digiriskdolibarr_riskassessment_view_single.tpl.php';
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
										<div class="risk-evaluation-cotation" data-scale="<?php echo $lastEvaluation->getEvaluationScale() ?>">
											<span><?php echo ($lastEvaluation->method == 'standard' ? $defaultCotation[$lastEvaluation->cotation] ?: 0 : $lastEvaluation->cotation); ?></span>
										</div>
										<div class="photo riskassessment-photo-<?php echo $lastEvaluation->id; ?>" style="margin:auto">
											<?php
											print saturne_show_medias_linked('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/' . $lastEvaluation->ref, 'small', 1, 0, 0, 0, 50, 50, 0, 0, 0, '/riskassessment/' . $lastEvaluation->ref, $lastEvaluation, 'photo', 0, 0, 0, 1);
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
														<input type="hidden" class="modal-options" data-modal-to-open="risk_evaluation_edit<?php echo $lastEvaluation->id; ?>" data-from-id="<?php echo $lastEvaluation->id; ?>" data-from-type="riskassessment" data-from-subtype="photo" data-from-subdir="" data-photo-class="riskassessment-from-riskassessment-edit-<?php echo $lastEvaluation->id; ?>"/>
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
	<div class="table-cell-header-label"><strong><?php echo $langs->trans('ListingHeaderEvaluation'); ?> (<?php echo $allRiskAssessment ? count($allRiskAssessment) : 0; ?>)</strong></div>
	<div class="table-cell-header-actions">
		<?php if ($permissiontoread) : ?>
			<div class="risk-evaluation-list risk-evaluation-button wpeo-button button-square-40 button-grey wpeo-tooltip-event modal-open" aria-label="<?php echo $langs->trans('RiskAssessmentList') ?>" value="<?php echo $risk->id ?>">
				<input type="hidden" class="modal-options" data-modal-to-open="risk_evaluation_list<?php echo $risk->id; ?>" data-from-id="<?php echo $lastEvaluation->id; ?>" data-from-type="riskassessment" data-from-subtype="photo" data-from-subdir="" data-photo-class="riskassessment-from-riskassessment-edit-<?php echo $lastEvaluation->id; ?>"/>
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
<?php endif;
$evaluation->method = $lastRiskAssessment->method ?: "standard" ;
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
								<div class="wpeo-button evaluation-standard select-evaluation-method<?php echo ($lastRiskAssessment->method == "standard") ? " selected button-blue" : " button-grey" ?> button-radius-2">
									<span><?php echo $langs->trans('SimpleEvaluation') ?></span>
								</div>
								<div class="wpeo-button evaluation-advanced select-evaluation-method<?php echo ($lastRiskAssessment->method == "advanced") ? " selected button-blue" : " button-grey" ?> button-radius-2">
									<span><?php echo $langs->trans('AdvancedEvaluation') ?></span>
								</div>
							<?php else : ?>
								<div class="wpeo-button evaluation-standard select-evaluation-method<?php echo ($lastRiskAssessment->method == "standard") ? " selected button-blue" : " button-grey button-disable" ?> button-radius-2">
									<span><?php echo $langs->trans('SimpleEvaluation') ?></span>
								</div>
								<div class="wpeo-button evaluation-advanced select-evaluation-method<?php echo ($lastRiskAssessment->method == "advanced") ? " selected button-blue" : " button-grey button-disable" ?> button-radius-2">
									<span><?php echo $langs->trans('AdvancedEvaluation') ?></span>
								</div>
							<?php endif; ?>
							<i class="fas fa-info-circle wpeo-tooltip-event" aria-label="<?php echo $langs->trans("HowToSetMultipleRiskAssessmentMethod") ?>"></i>
						<?php endif; ?>
						<input class="risk-evaluation-method" type="hidden" value="<?php echo ($lastRiskAssessment->method == "standard") ? "standard" : "advanced" ?>">
						<input class="risk-evaluation-multiple-method" type="hidden" value="<?php echo $conf->global->DIGIRISKDOLIBARR_MULTIPLE_RISKASSESSMENT_METHOD ?>">
					</div>
					<div class="risk-evaluation-content-wrapper">
						<div class="risk-evaluation-content">
							<div class="cotation-container">
								<div class="cotation-standard" style="<?php echo ($lastRiskAssessment->method !== "advanced") ? " display:block" : " display:none" ?>">
									<span class="title"><i class="fas fa-chart-line"></i><?php echo ' ' . $langs->trans('RiskAssessment'); ?><required>*</required></span>
									<div class="cotation-listing wpeo-gridlayout grid-4 grid-gap-0">
                                        <?php
                                        if ( ! empty($defaultCotation)) :
                                            foreach ($defaultCotation as $cotation => $shownCotation) :
                                                $evaluation->cotation = $cotation; ?>
                                                <div data-id="<?php echo 0; ?>"
                                                     data-evaluation-method="standard"
                                                     data-evaluation-id="<?php echo $cotation; ?>"
                                                     data-variable-id="<?php echo 152 + $cotation; ?>"
                                                     data-seuil="<?php echo  $evaluation->getEvaluationScale(); ?>"
                                                     data-scale="<?php echo  $evaluation->getEvaluationScale(); ?>"
                                                     class="risk-evaluation-cotation cotation"><?php echo $shownCotation; ?></div>
                                            <?php endforeach;
                                        endif; ?>
									</div>
								</div>
								<input class="risk-evaluation-seuil" type="hidden">
								<?php $evaluationMethod  = $advancedCotationMethodArray[0];
								$evaluationMethodSurvey  = $evaluationMethod['option'][$risk->type . '_variable']; ?>
								<div class="wpeo-gridlayout cotation-advanced" style="<?php echo ($lastRiskAssessment->method == "advanced") ? " display:block" : " display:none" ?>">
									<input type="hidden" class="digi-method-evaluation-id" value="<?php echo $risk->id ; ?>" />
									<textarea style="display: none" name="evaluation_variables" class="tmp_evaluation_variable"><?php echo '{}'; ?></textarea>
									<p><i class="fas fa-info-circle"></i> <?php echo $langs->trans('SelectEvaluation') ?></p>
									<div class="wpeo-table evaluation-method table-flex table-<?php echo count($evaluationMethodSurvey) + 1; ?>">
										<div class="table-row table-header">
											<div class="table-cell">
												<span></span>
											</div>
											<?php for ( $l = 0; $l < count($evaluationMethodSurvey); $l++ ) : ?>
												<div class="table-cell">
													<span><?php echo $l; ?></span>
												</div>
											<?php endfor; ?>
										</div>
										<?php $l = 0; ?>
										<?php foreach ($evaluationMethodSurvey as $critere) :
											$name = strtolower($critere['name']); ?>
											<div class="table-row">
												<div class="table-cell"><?php echo $critere['name'] ; ?></div>
												<?php foreach ($critere['option']['survey']['request'] as $request) : ?>
													<div class="table-cell can-select cell-<?php echo  !empty($evaluationId) ? $evaluationId : 0 ; ?>"
														 data-type="<?php echo $name ?>"
														 data-id="<?php echo  $risk->id ? $risk->id : 0 ; ?>"
														 data-evaluation-id="<?php echo !empty($evaluationId) ? $evaluationId : 0 ; ?>"
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
						<div class="risk-evaluation-calculated-cotation" style="<?php echo ($lastRiskAssessment->method == "advanced") ? " display:block" : " display:none" ?>">
							<span class="title"><i class="fas fa-chart-line"></i> <?php echo $langs->trans('CalculatedEvaluation'); ?><required>*</required></span>
							<div data-scale="1" class="risk-evaluation-cotation cotation">
								<span><?php echo 0 ?></span>
							</div>
						</div>
						<div class="risk-evaluation-comment">
							<span class="title"><i class="fas fa-comment-dots"></i> <?php echo $langs->trans('Comment'); ?> (<span class="char-counter">65535</span> <?php echo $langs->trans('CharRemaining'); ?>)</span>
							<?php print '<textarea class="evaluation-comment-textarea" data-maxlength="65535" maxlength="65535" name="evaluationComment' . $risk->id . '" rows="' . ROWS_2 . '">' . ('') . '</textarea>' . "\n"; ?>
						</div>
					</div>
					<?php if ($conf->global->DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE) : ?>
						<div class="risk-evaluation-date">
							<span class="title"><?php echo $langs->trans('Date'); ?></span>
							<?php print $form->selectDate('', 'RiskAssessmentDateCreate0', 0, 0, 0, '', 1, 1); ?>
						</div>
					<?php endif; ?>
                    <div class="riskassessment-medias linked-medias riskassessment-from-riskassessment-create-<?php echo $risk->id ?>">
                        <div class="element-linked-medias element-linked-medias-0 risk-<?php echo $risk->id ?>">
                            <div class="medias section-title"><i class="fas fa-picture-o"></i><?php echo $langs->trans('Medias'); ?></div>
                            <table class="add-medias">
                                <tr>
                                    <td>
                                        <input hidden multiple class="fast-upload" id="fast-upload-photo-riskassessment-create-<?php echo $risk->id ?>" type="file" name="userfile[]" capture="environment" accept="image/*">
                                        <label for="fast-upload-photo-riskassessment-create-<?php echo $risk->id ?>">
                                            <div class="wpeo-button <?php echo ($onPhone ? 'button-square-40' : 'button-square-50'); ?>">
                                                <i class="fas fa-camera"></i><i class="fas fa-plus-circle button-add"></i>
                                            </div>
                                        </label>
                                        <input type="hidden" class="favorite-photo" id="photo" name="photo" value="<?php echo $object->photo ?? '' ?>"/>
                                    </td>
                                    <td>
                                        <div class="wpeo-button <?php echo ($onPhone ? 'button-square-40' : 'button-square-50'); ?> 'open-media-gallery add-media modal-open" value="<?php echo $lastRiskAssessment->id; ?>">
                                            <input type="hidden" class="modal-options" data-modal-to-open="media_gallery" data-from-id="0" data-from-type="riskassessment" data-from-subtype="photo" data-from-subdir="<?php echo $risk->ref; ?>" data-photo-class="riskassessment-from-riskassessment-create-<?php echo $risk->id ?>"/>
                                            <i class="fas fa-folder-open"></i><i class="fas fa-plus-circle button-add"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $relativepath = 'digiriskdolibarr/medias/thumbs';
                                        print saturne_show_medias_linked('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/tmp/RA0/' . $risk->ref, 'small', 0, 0, 0, 0, $onPhone ? 40 : 50, $onPhone ? 40 : 50, 1, 0, 0, '/riskassessment/tmp/RA0/' . $risk->ref);
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
				</div>
				<!-- RISK EVALUATION SINGLE -->
				<?php if ( ! empty($lastRiskAssessment) && $lastRiskAssessment > 0) : ?>
					<div class="risk-evaluation-container last-risk-assessment risk-evaluation-container-<?php echo $lastRiskAssessment->id ?>">
						<h2><?php echo $langs->trans('LastRiskAssessment') . ' ' . $risk->ref; ?></h2>
						<div class="risk-evaluation-single-content risk-evaluation-single-content-<?php echo $risk->id ?>">
							<div class="risk-evaluation-single">
								<div class="risk-evaluation-cotation risk-evaluation-list" value="<?php echo $risk->id ?>" data-scale="<?php echo $lastRiskAssessment->getEvaluationScale() ?>">
									<span><?php echo ($lastRiskAssessment->method == 'standard' ? $defaultCotation[$lastRiskAssessment->cotation] ?: 0 : $lastRiskAssessment->cotation); ?></span>
								</div>
								<div class="photo riskassessment-photo-<?php echo $lastRiskAssessment->id > 0 ? $lastRiskAssessment->id : 0 ; echo $risk->id > 0 ? ' risk-' . $risk->id : ' risk-new' ?>">
                                    <?php
                                        print saturne_show_medias_linked('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/' . $lastRiskAssessment->ref, 'small', 1, 0, 0, 0, 50, 50, 0, 0, 0, '/riskassessment/' . $lastRiskAssessment->ref . '/', $lastRiskAssessment, 'photo', 0, 0, 0, 1);
								    ?>
								</div>
								<div class="risk-evaluation-content">
									<div class="risk-evaluation-data">
										<!-- BUTTON MODAL RISK EVALUATION LIST  -->
										<span class="risk-evaluation-reference risk-evaluation-list" value="<?php echo $risk->id ?>"><?php echo $lastRiskAssessment->ref; ?></span>
										<span class="risk-evaluation-date">
											<i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', (($conf->global->DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE && ( ! empty($lastRiskAssessment->date_riskassessment))) ? $lastRiskAssessment->date_riskassessment : $lastRiskAssessment->date_creation)); ?>
										</span>
										<span class="risk-evaluation-author">
											<?php $userAuthor = $usersList[$lastRiskAssessment->fk_user_creat?:$user->id];
											echo getNomUrlUser($userAuthor); ?>
										</span>
									</div>
									<div class="risk-evaluation-comment">
										<?php echo dol_trunc($lastRiskAssessment->comment, 120); ?>
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
					<div class="risk-evaluation-create wpeo-button button-blue button-disable modal-close"value="<?php echo $risk->id ?>">
						<i class="fas fa-plus"></i> <span style="color: #fff"><?php echo $langs->trans('Add'); ?></span>
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
