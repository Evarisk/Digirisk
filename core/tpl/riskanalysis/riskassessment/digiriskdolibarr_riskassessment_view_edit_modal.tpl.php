<!-- RISK EVALUATION EDIT MODAL START-->
<div class="risk-evaluation-edit-modal" value="<?php echo $lastEvaluation->id ?>">
	<div class="wpeo-modal modal-risk" id="risk_evaluation_edit<?php echo $lastEvaluation->id ?>" value="<?php echo $risk->id ?>">
		<div class="modal-container wpeo-modal-event">
			<!-- Modal-Header -->
			<div class="modal-header">
				<h2 class="modal-title"><?php echo $langs->trans('EvaluationEdit') . ' ' . $lastEvaluation->ref ?></h2>
				<div class="modal-close"><i class="fas fa-times"></i></div>
			</div>
			<!-- Modal EDIT Evaluation Content-->
			<div class="modal-content" id="#modalContent<?php echo $lastEvaluation->id ?>">
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
				<div class="risk-evaluation-container <?php echo $lastEvaluation->method; ?>">
					<div class="risk-evaluation-header">
						<?php if ($conf->global->DIGIRISKDOLIBARR_ADVANCED_RISKASSESSMENT_METHOD) : ?>
							<?php if ( $conf->global->DIGIRISKDOLIBARR_MULTIPLE_RISKASSESSMENT_METHOD == 1 ) : ?>
								<div class="wpeo-button evaluation-standard select-evaluation-method<?php echo ($lastEvaluation->method == "standard") ? " selected button-blue" : " button-grey" ?> button-radius-2">
									<span><?php echo $langs->trans('SimpleEvaluation') ?></span>
								</div>
								<div class="wpeo-button evaluation-advanced select-evaluation-method<?php echo ($lastEvaluation->method == "advanced") ? " selected button-blue" : " button-grey" ?> button-radius-2">
									<span><?php echo $langs->trans('AdvancedEvaluation') ?></span>
								</div>
							<?php else : ?>
								<div class="wpeo-button evaluation-standard select-evaluation-method<?php echo ($lastEvaluation->method == "standard") ? " selected button-blue" : " button-grey button-disable" ?> button-radius-2">
									<span><?php echo $langs->trans('SimpleEvaluation') ?></span>
								</div>
								<div class="wpeo-button evaluation-advanced select-evaluation-method<?php echo ($lastEvaluation->method == "advanced") ? " selected button-blue" : " button-grey button-disable" ?> button-radius-2">
									<span><?php echo $langs->trans('AdvancedEvaluation') ?></span>
								</div>
							<?php endif; ?>
							<i class="fas fa-info-circle wpeo-tooltip-event" aria-label="<?php echo $langs->trans("HowToSetMultipleRiskAssessmentMethod") ?>"></i>
						<?php endif; ?>
                        <input class="risk-evaluation-method" type="hidden" value="<?php echo ($lastEvaluation->method == "standard") ? "standard" : "advanced" ?>">
                        <input class="risk-evaluation-multiple-method" type="hidden" value="<?php echo $conf->global->DIGIRISKDOLIBARR_MULTIPLE_RISKASSESSMENT_METHOD ?>">
					</div>
					<div class="risk-evaluation-content-wrapper">
						<div class="risk-evaluation-content">
							<div class="cotation-container">
								<?php if ( $lastEvaluation->method == "standard" || $conf->global->DIGIRISKDOLIBARR_MULTIPLE_RISKASSESSMENT_METHOD) : ?>
									<div class="cotation-standard" style="<?php echo ($lastEvaluation->method == "standard") ? " display:block" : " display:none" ?>">
										<span class="title"><i class="fas fa-chart-line"></i><?php echo ' ' . $langs->trans('RiskAssessment'); ?></span>
										<div class="cotation-listing wpeo-gridlayout grid-4 grid-gap-0">
                                            <?php
                                            $defaultCotation = array(0 => '0-47', 48 => '48-50', 51 => '51-80', 100 => '81-100');
                                            if ( ! empty($defaultCotation)) :
                                                foreach ($defaultCotation as $cotation => $shownCotation) :
                                                    $evaluation->cotation = $cotation; ?>
                                                    <div data-id="<?php echo 0; ?>"
                                                         data-evaluation-method="standard"
                                                         data-evaluation-id="<?php echo $cotation; ?>"
                                                         data-variable-id="<?php echo 152 + $cotation; ?>"
                                                         data-seuil="<?php echo  $evaluation->getEvaluationScale(); ?>"
                                                         data-scale="<?php echo  $evaluation->getEvaluationScale(); ?>"
                                                         class="risk-evaluation-cotation cotation<?php echo ($lastEvaluation->cotation == $cotation) ? " selected-cotation" : "" ?>"><?php echo $shownCotation; ?></div>
                                                <?php endforeach;
                                            endif; ?>
										</div>
									</div>
								<?php endif; ?>
								<input class="risk-evaluation-seuil" type="hidden" value="<?php echo $lastEvaluation->cotation ?>">
								<?php if ( $lastEvaluation->method == "advanced" || $conf->global->DIGIRISKDOLIBARR_MULTIPLE_RISKASSESSMENT_METHOD) : ?>
									<?php
									$evaluationMethod       = $advancedCotationMethodArray[0];
									$evaluationMethodSurvey = $evaluationMethod['option'][$risk->type . '_variable'];
									?>
									<div class="wpeo-gridlayout cotation-advanced" style="<?php echo ($lastEvaluation->method == "advanced") ? " display:block" : " display:none" ?>">
										<input type="hidden" class="digi-method-evaluation-id" value="<?php echo $risk->id ; ?>" />
										<textarea style="display: none" name="evaluation_variables" class="tmp_evaluation_variable"><?php echo '{}'; ?></textarea>
										<span class="title"><i class="fas fa-info-circle"></i> <?php echo $langs->trans('SelectEvaluation') ?></span>
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
											<?php $l = 0;
											foreach ($evaluationMethodSurvey as $critere) :
												$name = strtolower($critere['name']);
                                                $key = $lastEvaluation->advancedCotation[$critere['key']] ?>
												<div class="table-row">
													<div class="table-cell"><?php echo $critere['name'] ; ?></div>
													<?php foreach ($critere['option']['survey']['request'] as $request) : ?>
														<div class="table-cell can-select cell-<?php echo $lastEvaluation->id ? $lastEvaluation->id : 0;
														if ( ! empty($request['seuil'])) {
															echo $request['seuil'] == $lastEvaluation->$key ? " active" : "" ;
														} ?>"
															 data-type="<?php echo $name ?>"
															 data-id="<?php echo  $risk->id ? $risk->id : 0 ; ?>"
															 data-evaluation-id="<?php echo $lastEvaluation->id ? $lastEvaluation->id : 0 ; ?>"
															 data-variable-id="<?php echo $l ; ?>"
															 data-seuil="<?php echo  $request['seuil']; ?>">
															<?php echo  $request['question'] ; ?>
														</div>
													<?php endforeach; $l++; ?>
												</div>
											<?php endforeach; ?>
										</div>
									</div>
								<?php endif; ?>
							</div>
						</div>
						<div class="risk-evaluation-calculated-cotation"  style="<?php echo ($lastEvaluation->method == "advanced") ? " display:block" : " display:none" ?>">
							<span class="title"><i class="fas fa-chart-line"></i> <?php echo $langs->trans('CalculatedEvaluation'); ?></span>
							<div data-scale="<?php echo $lastEvaluation->getEvaluationScale() ?>" class="risk-evaluation-cotation cotation">
								<span><?php echo $lastEvaluation->cotation ?: 0 ?></span>
							</div>
						</div>
						<div class="risk-evaluation-comment">
							<span class="title"><i class="fas fa-comment-dots"></i> <?php echo $langs->trans('Comment'); ?> (<span class="char-counter"><?php echo 65535 - strlen($lastEvaluation->comment); ?></span> <?php echo $langs->trans('CharRemaining'); ?>)</span>
							<?php print '<textarea class="evaluation-comment-textarea" data-maxlength="65535" maxlength="65535" name="evaluationComment' . $lastEvaluation->id . '" rows="' . ROWS_2 . '">' . $lastEvaluation->comment . '</textarea>' . "\n"; ?>
						</div>
					</div>
					<?php if ($conf->global->DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE) : ?>
						<div class="risk-evaluation-date">
							<span class="title"><?php echo $langs->trans('Date'); ?></span>
							<?php print $form->selectDate($lastEvaluation->date_riskassessment, 'RiskAssessmentDateEdit' . $lastEvaluation->id, 0, 0, 0, '', 1, 1); ?>
						</div>
					<?php endif; ?>
                    <div class="riskassessment-medias linked-medias riskassessment-photo-<?php echo $lastEvaluation->id; ?>">
                        <div class="element-linked-medias element-linked-medias-<?php echo $lastEvaluation->id; ?> risk-<?php echo $risk->id ?>">
                            <div class="medias section-title"><i class="fas fa-picture-o"></i><?php echo $langs->trans('Medias'); ?></div>
                            <table class="add-medias">
                                <tr>
                                    <td>
                                        <input hidden multiple class="fast-upload" id="fast-upload-photo-riskassessment-photo-<?php echo $lastEvaluation->id; ?>" type="file" name="userfile[]" capture="environment" accept="image/*">
                                        <label for="fast-upload-photo-riskassessment-photo-<?php echo $lastEvaluation->id; ?>">
                                            <div class="wpeo-button <?php echo ($onPhone ? 'button-square-40' : 'button-square-50'); ?>">
                                                <i class="fas fa-camera"></i><i class="fas fa-plus-circle button-add"></i>
                                            </div>
                                        </label>
                                        <input type="hidden" class="favorite-photo" id="photo" name="photo" value="<?php echo $object->photo ?? '' ?>"/>
                                    </td>
                                    <td>
                                        <div class="wpeo-button <?php echo ($onPhone ? 'button-square-40' : 'button-square-50'); ?> 'open-media-gallery add-media modal-open" value="<?php echo $lastEvaluation->id; ?>">
                                            <input type="hidden" class="modal-options" data-modal-to-open="media_gallery" data-from-id="<?php echo $lastEvaluation->id; ?>" data-from-type="riskassessment" data-from-subtype="photo" data-from-subdir="" data-photo-class="riskassessment-photo-<?php echo $lastEvaluation->id; ?>"/>
                                            <i class="fas fa-folder-open"></i><i class="fas fa-plus-circle button-add"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $relativepath = 'digiriskdolibarr/medias/thumbs';
                                        print saturne_show_medias_linked('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/' . $lastEvaluation->ref, 'small', 0, 0, 0, 0, $onPhone ? 40 : 50, $onPhone ? 40 : 50, 1, 0, 0, '/riskassessment/' . $lastEvaluation->ref, $lastEvaluation);
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
				</div>
			</div>
			<!-- Modal-Footer -->
			<div class="modal-footer">
				<?php if ($permissiontoadd) : ?>
					<div class="wpeo-button risk-evaluation-save button-green">
						<i class="fas fa-save"></i> <?php echo $langs->trans('UpdateData'); ?>
					</div>
				<?php else : ?>
					<div class="wpeo-button button-grey wpeo-tooltip-event" aria-label="<?php echo $langs->trans('PermissionDenied') ?>">
						<i class="fas fa-plus"></i> <?php echo $langs->trans('UpdateData'); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
<!-- RISK EVALUATION EDIT MODAL END-->
