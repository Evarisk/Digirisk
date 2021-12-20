<?php
$lastEvaluation = $evaluation->fetchFromParent($risk->id, 1);
$lastEvaluationCount = count( $evaluation->fetchFromParent($risk->id) );
if (!empty ($lastEvaluation) && $lastEvaluation > 0) {
	$lastEvaluation = array_shift($lastEvaluation);
	$cotationList = $evaluation->fetchFromParent($risk->id, 0, 'DESC'); ?>

	<div class="table-cell-header">
		<div class="table-cell-header-label"><strong><?php echo $langs->trans('ListingHeaderCotation'); ?> (<?php echo count( $cotationList ); ?>)</strong></div>
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

			<?php if ($permissiontoadd) : ?>
				<div class="risk-evaluation-add risk-evaluation-button wpeo-button button-square-40 button-primary wpeo-tooltip-event modal-open" aria-label="<?php echo $langs->trans('AddRiskAssessment') ?>" value="<?php echo $risk->id;?>">
					<i class="fas fa-plus button-icon"></i>
				</div>
			<?php else : ?>
				<div class="wpeo-button button-square-40 button-grey wpeo-tooltip-event risk-list-button" aria-label="<?php echo $langs->trans('PermissionDenied') ?>" value="<?php echo $risk->id;?>">
					<i class="fas fa-plus button-icon"></i>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="risk-evaluation-container risk-evaluation-container-<?php echo $risk->id ?>" value="<?php echo $risk->id ?>">
		<!-- RISK EVALUATION SINGLE -->
		<div class="risk-evaluation-single-content risk-evaluation-single-content-<?php echo $risk->id ?>">
			<div class="risk-evaluation-single risk-evaluation-single-<?php echo $risk->id ?>">
				<div class="risk-evaluation-cotation risk-evaluation-list modal-open" value="<?php echo $risk->id ?>" data-scale="<?php echo $lastEvaluation->get_evaluation_scale() ?>">
					<span><?php echo $lastEvaluation->cotation ?: 0; ?></span>
				</div>
				<div class="risk-evaluation-photo risk-evaluation-photo-<?php echo $lastEvaluation->id > 0 ?  $lastEvaluation->id :  0 ; echo $risk->id > 0 ? ' risk-'.$risk->id : ' risk-new' ?> open-medias-linked" value="<?php echo $lastEvaluation->id ?>">
					<?php $filearray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$lastEvaluation->element.'/'.$lastEvaluation->ref, "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'asc', 1);
					if (count($filearray)) {
						print '<span class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$lastEvaluation->element, 'small', 1, 0, 0, 0, 40, 0, 0, 0, 0, $lastEvaluation->element, $lastEvaluation).'</span>';
					} else {
						$nophoto = '/public/theme/common/nophoto.png'; ?>
						<span class="floatleft inline-block valignmiddle divphotoref"><img class="photodigiriskdolibarr" alt="No photo" src="<?php echo DOL_URL_ROOT.$nophoto ?>"></span>
					<?php } ?>
				</div>
				<div class="risk-evaluation-content">
					<div class="risk-evaluation-data">
						<!-- BUTTON MODAL RISK EVALUATION LIST  -->
						<span class="risk-evaluation-reference" value="<?php echo $risk->id ?>"><?php echo $lastEvaluation->ref; ?></span>
						<span class="risk-evaluation-date">
							<i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', (($conf->global->DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE && (!empty($lastEvaluation->date_riskassessment))) ? $lastEvaluation->date_riskassessment : $lastEvaluation->date_creation)); ?>
						</span>
					</div>
					<div class="risk-evaluation-comment">
						<span class="risk-evaluation-author">
							<?php $user->fetch($lastEvaluation->fk_user_creat); ?>
							<?php echo getNomUrl( 0, '', 0, 0, 2 , 0,'','',-1, $user); ?>
						</span>
						<?php echo dol_trunc($lastEvaluation->comment, 120); ?>
					</div>
				</div>
				<!-- BUTTON MODAL RISK EVALUATION ADD  -->
				<?php if ($permissiontoadd) : ?>
					<div class="risk-evaluation-edit risk-evaluation-button wpeo-button button-square-40 button-transparent wpeo-tooltip-event modal-open" aria-label="<?php echo $langs->trans('EditRiskAssessment') ?>" value="<?php echo $lastEvaluation->id;?>">
						<i class="fas fa-pencil-alt button-icon"></i>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<!-- RISK ASSESSMENT MEDIAS MODAL START-->
		<div class="risk-evaluation-medias-modal" style="z-index:1500" value="<?php echo $lastEvaluation->id ?>">
			<div class="wpeo-modal modal-risk"  id="risk_assessment_medias_modal_<?php echo $lastEvaluation->id ?>" value="<?php echo $risk->id ?>">
				<div class="modal-container wpeo-modal-event">
					<!-- Modal-Header -->
					<div class="modal-header">
						<h2 class="modal-title"><?php echo $langs->trans('RiskAssessmentMedias') . ' ' . $lastEvaluation->ref ?></h2>
						<div class="wpeo-button open-media-gallery add-media modal-open" value="<?php echo $lastEvaluation->id ?>">
							<input type="hidden" class="type-from" value="riskassessment"/>
							<span><i class="fas fa-camera"></i>  <?php echo $langs->trans('AddMedia') ?></span>
						</div>
						<div class="modal-close"><i class="fas fa-times"></i></div>
					</div>
					<?php $cotation = $lastEvaluation;
					include DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/core/tpl/digiriskdolibarr_photo_view.tpl.php'; ?>

					<!-- Modal Content-->
					<div class="modal-content" id="#modalContent<?php echo $lastEvaluation->id ?>">
						<div class="risk-evaluation-container <?php echo $lastEvaluation->method; ?>">
							<div class="risk-evaluation-header">

							</div>
							<div class="element-linked-medias element-linked-medias-<?php echo $lastEvaluation->id ?> modal-media-linked">
								<div class="medias"><i class="fas fa-picture-o"></i><?php echo $langs->trans('Medias'); ?></div>
								<?php
								$relativepath = 'digiriskdolibarr/medias/thumbs';
								print digirisk_show_medias_linked('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/' , 'small', '', 0, 0, 0, 150, 150, 1, 0, 0, $lastEvaluation->element, $lastEvaluation);
								?>
							</div>
						</div>
					</div>
					<!-- Modal-Footer -->
					<div class="modal-footer">
						<div class="wpeo-button modal-close button-blue">
							<i class="fas fa-times"></i> <?php echo $langs->trans('CloseModal'); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- RISK ASSESSMENT MEDIAS MODAL END-->

		<?php if (!empty($cotationList)) :
			foreach ($cotationList as $cotation) : ?>
				<!-- RISK EVALUATION EDIT MODAL START-->
				<div class="risk-evaluation-edit-modal" value="<?php echo $cotation->id ?>">
					<div class="wpeo-modal modal-risk" id="risk_evaluation_edit<?php echo $cotation->id ?>" value="<?php echo $risk->id ?>">
						<div class="modal-container wpeo-modal-event">
							<!-- Modal-Header -->
							<div class="modal-header">
								<h2 class="modal-title"><?php echo $langs->trans('EvaluationEdit') . ' ' . $cotation->ref ?></h2>
								<div class="modal-close"><i class="fas fa-times"></i></div>
							</div>
							<!-- Modal EDIT Evaluation Content-->
							<div class="modal-content" id="#modalContent<?php echo $cotation->id ?>">
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
								<div class="risk-evaluation-container <?php echo $cotation->method; ?>">
									<div class="risk-evaluation-header">
										<?php if ($conf->global->DIGIRISKDOLIBARR_ADVANCED_RISKASSESSMENT_METHOD) : ?>
											<?php if ( $conf->global->DIGIRISKDOLIBARR_MULTIPLE_RISKASSESSMENT_METHOD == 1 ) : ?>
												<div class="wpeo-button evaluation-standard select-evaluation-method<?php echo ($cotation->method == "standard") ? " selected button-blue" : " button-grey" ?> button-radius-2">
													<span><?php echo $langs->trans('SimpleCotation') ?></span>
												</div>
												<div class="wpeo-button evaluation-advanced select-evaluation-method<?php echo ($cotation->method == "advanced") ? " selected button-blue" : " button-grey" ?> button-radius-2">
													<span><?php echo $langs->trans('AdvancedCotation') ?></span>
												</div>
											<?php else : ?>
												<div class="wpeo-button evaluation-standard select-evaluation-method<?php echo ($cotation->method == "standard") ? " selected button-blue" : " button-grey button-disable" ?> button-radius-2">
													<span><?php echo $langs->trans('SimpleCotation') ?></span>
												</div>
												<div class="wpeo-button evaluation-advanced select-evaluation-method<?php echo ($cotation->method == "advanced") ? " selected button-blue" : " button-grey button-disable" ?> button-radius-2">
													<span><?php echo $langs->trans('AdvancedCotation') ?></span>
												</div>
											<?php endif; ?>
											<i class="fas fa-info-circle wpeo-tooltip-event" aria-label="<?php echo $langs->trans("HowToSetMultipleRiskAssessmentMethod") ?>"></i>
										<?php endif; ?>
										<input class="risk-evaluation-method" type="hidden" value="<?php echo $cotation->method ?>" />
										<input class="risk-evaluation-multiple-method" type="hidden" value="<?php echo $conf->global->DIGIRISKDOLIBARR_MULTIPLE_RISKASSESSMENT_METHOD ?>">
										<div class="wpeo-button open-media-gallery add-media modal-open" value="<?php echo $cotation->id ?>">
											<input type="hidden" class="type-from" value="riskassessment"/>
											<span><i class="fas fa-camera"></i>  <?php echo $langs->trans('AddMedia') ?></span>
										</div>
									</div>
									<div class="risk-evaluation-content-wrapper">
										<div class="risk-evaluation-content">
											<div class="cotation-container">
												<?php if ( $cotation->method == "standard" || $conf->global->DIGIRISKDOLIBARR_MULTIPLE_RISKASSESSMENT_METHOD) : ?>
													<div class="cotation-standard" style="<?php echo ($cotation->method == "standard") ? " display:block" : " display:none" ?>">
														<span class="title"><i class="fas fa-chart-line"></i><?php echo ' ' . $langs->trans('Cotation'); ?></span>
														<div class="cotation-listing wpeo-gridlayout grid-4 grid-gap-0">
															<?php
															$defaultCotation = array(0, 48, 51, 100);
															if ( ! empty( $defaultCotation )) :
																foreach ( $defaultCotation as $request ) :
																	$evaluation->cotation = $request; ?>
																	<div data-id="<?php echo 0; ?>"
																		 data-evaluation-method="standard"
																		 data-evaluation-id="<?php echo $request; ?>"
																		 data-variable-id="<?php echo 152+$request; ?>"
																		 data-seuil="<?php echo  $evaluation->get_evaluation_scale(); ?>"
																		 data-scale="<?php echo  $evaluation->get_evaluation_scale(); ?>"
																		 class="risk-evaluation-cotation cotation<?php echo ($cotation->cotation == $request) ? " selected-cotation" : "" ?>"><?php echo $request; ?></div>
																<?php endforeach;
															endif; ?>
														</div>
													</div>
												<?php endif; ?>
												<input class="risk-evaluation-seuil" type="hidden" value="<?php echo $cotation->cotation ?>">
												<?php if ( $cotation->method == "advanced" || $conf->global->DIGIRISKDOLIBARR_MULTIPLE_RISKASSESSMENT_METHOD) : ?>
													<?php
													$evaluation_method = $advanced_method_cotation_array[0];
													$evaluation_method_survey = $evaluation_method['option']['variable'];
													?>
													<div class="wpeo-gridlayout cotation-advanced" style="<?php echo ($cotation->method == "advanced") ? " display:block" : " display:none" ?>">
														<input type="hidden" class="digi-method-evaluation-id" value="<?php echo $risk->id ; ?>" />
														<textarea style="display: none" name="evaluation_variables" class="tmp_evaluation_variable"><?php echo '{}'; ?></textarea>
														<span class="title"><i class="fas fa-info-circle"></i> <?php echo $langs->trans('SelectCotation') ?></span>
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
															<?php $l = 0;
															foreach($evaluation_method_survey as $critere) :
																$name = strtolower($critere['name']); ?>
																<div class="table-row">
																	<div class="table-cell"><?php echo $critere['name'] ; ?></div>
																	<?php foreach($critere['option']['survey']['request'] as $request) : ?>
																		<div class="table-cell can-select cell-<?php echo $cotation->id ? $cotation->id : 0;
																		if (!empty($request['seuil'])) {
																			echo $request['seuil'] == $cotation->$name ? " active" : "" ;
																		} ?>"
																			 data-type="<?php echo $name ?>"
																			 data-id="<?php echo  $risk->id ? $risk->id : 0 ; ?>"
																			 data-evaluation-id="<?php echo $cotation->id ? $cotation->id : 0 ; ?>"
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

										<?php
										$editModal = 1;
										include DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/core/tpl/digiriskdolibarr_photo_view.tpl.php';
										$editModal = 0;
										?>

										<div class="risk-evaluation-calculated-cotation"  style="<?php echo ($cotation->method == "advanced") ? " display:block" : " display:none" ?>">
											<span class="title"><i class="fas fa-chart-line"></i> <?php echo $langs->trans('CalculatedCotation'); ?></span>
											<div data-scale="<?php echo $cotation->get_evaluation_scale() ?>" class="risk-evaluation-cotation cotation">
												<span><?php echo  $cotation->cotation ?></span>
											</div>
										</div>
										<div class="risk-evaluation-comment">
											<span class="title"><i class="fas fa-comment-dots"></i> <?php echo $langs->trans('Comment'); ?></span>
											<?php print '<textarea name="evaluationComment'. $cotation->id .'" rows="'.ROWS_2.'">'.$cotation->comment.'</textarea>'."\n"; ?>
										</div>
									</div>
									<?php if ($conf->global->DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE) : ?>
										<div class="risk-evaluation-date">
											<span class="title"><?php echo $langs->trans('Date'); ?></span>
											<?php print $form->selectDate($lastEvaluation->date_riskassessment, 'RiskAssessmentDateEdit' . $lastEvaluation->id, 0, 0, 0, '', 1, 1); ?>
										</div>
									<?php endif; ?>
									<div class="element-linked-medias element-linked-medias-<?php echo $cotation->id ?> risk-<?php echo $risk->id ?>">
										<div class="medias"><i class="fas fa-picture-o"></i><?php echo $langs->trans('Medias'); ?></div>
										<?php
										$relativepath = 'digiriskdolibarr/medias/thumbs';
										print digirisk_show_medias_linked('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/' , 'small', '', 0, 0, 0, 150, 150, 1, 0, 0, $cotation->element, $cotation);
										?>
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
			<?php endforeach;
		endif; ?>
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
						<!--	RISK ASSESSMENT-->
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
								<?php if (!empty($cotationList)) :
									foreach ($cotationList as $cotation) : ?>
									<div class="risk-evaluation risk-evaluation<?php echo $cotation->id ?>" value="<?php echo $cotation->id ?>">
										<input type="hidden" class="labelForDelete" value="<?php echo $langs->trans('DeleteEvaluation') . ' ' . $cotation->ref . ' ?'; ?>">
										<div class="risk-evaluation-container risk-evaluation-ref-<?php echo $cotation->id ?>" value="<?php echo $cotation->ref ?>">
											<div class="risk-evaluation-single">
												<div class="risk-evaluation-cotation" data-scale="<?php echo $cotation->get_evaluation_scale() ?>">
													<span><?php echo $cotation->cotation; ?></span>
												</div>
												<div class="risk-evaluation-photo risk-evaluation-photo-<?php echo $cotation->id > 0 ?  $cotation->id :  0 ; echo $risk->id > 0 ? ' risk-'.$risk->id : ' risk-new' ?>">
													<?php $filearray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$cotation->element.'/'.$cotation->ref, "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'asc', 1);
													if (count($filearray)) {
														print '<img width="40" class="photo clicked-photo-preview" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=digiriskdolibarr&entity='.$conf->entity.'&file='.urlencode($cotation->element.'/'.$cotation->ref . '/thumbs/'. preg_replace('/\./', '_small.', $cotation->photo)).'" >';
													} else {
														$nophoto = '/public/theme/common/nophoto.png'; ?>
														<span class="floatleft inline-block valignmiddle divphotoref"><img class="photodigiriskdolibarr clicked-photo-preview" alt="No photo" src="<?php echo DOL_URL_ROOT.$nophoto ?>"></span>
													<?php } ?>
												</div>
												<div class="risk-evaluation-content">
													<div class="risk-evaluation-data">
														<span class="risk-evaluation-reference"><?php echo $cotation->ref; ?></span>
														<span class="risk-evaluation-date">
															<i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', (($conf->global->DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE && (!empty($cotation->date_riskassessment))) ? $cotation->date_riskassessment : $cotation->date_creation)); ?>
														</span>
													</div>
													<div class="risk-evaluation-comment">
														<span class="risk-evaluation-author">
															<?php $user->fetch($cotation->fk_user_creat); ?>
															<?php echo getNomUrl( 0, '', 0, 0, 2 ,0,'','',-1,$user); ?>
														</span>
														<?php echo dol_trunc($cotation->comment, 120); ?>
													</div>
												</div>
												<!-- BUTTON MODAL RISK EVALUATION EDIT  -->
												<div class="risk-evaluation-actions wpeo-gridlayout grid-2 grid-gap-0">
													<?php if ($permissiontoadd) : ?>
														<div class="risk-evaluation-edit wpeo-button button-square-50 button-grey modal-open" value="<?php echo $cotation->id ?>">
															<i class="fas fa-pencil-alt button-icon"></i>
														</div>
													<?php else : ?>
														<div class="wpeo-button button-square-50 button-grey wpeo-tooltip-event"  aria-label="<?php echo $langs->trans('PermissionDenied'); ?>" value="<?php echo $cotation->id ?>">
															<i class="fas fa-pencil-alt button-icon"></i>
														</div>
													<?php endif; ?>
													<?php if ($permissiontodelete) : ?>
														<div class="risk-evaluation-delete wpeo-button button-square-50 button-transparent">
															<i class="fas fa-trash button-icon"></i>
														</div>
													<?php endif; ?>
												</div>
											</div>
										</div>
									</li>
									<hr>
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
	</div>
<?php } else { ?>
<div class="risk-evaluation-container risk-evaluation-container-<?php echo $risk->id ?>">
	<div class="risk-evaluation-single-content risk-evaluation-single-content-<?php echo $risk->id ?>">
		<div class="risk-evaluation-single risk-evaluation-single-<?php echo $risk->id ?>">
			<div class="risk-evaluation-content">
				<div class="risk-evaluation-data">
					<span class="name"><?php echo $langs->trans('NoRiskAssessment'); ?></span>
				</div>
			</div>
			<?php if ($permissiontoadd) : ?>
				<div class="risk-evaluation-add wpeo-button button-square-40 button-primary wpeo-tooltip-event modal-open" aria-label="<?php echo $langs->trans('AddRiskAssessment') ?>" value="<?php echo $risk->id ?>">
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
<?php }
$cotation = new RiskAssessment($db);
$cotation->method = $lastEvaluation->method ? $lastEvaluation->method : "standard" ; ?>
<!-- RISK EVALUATION ADD MODAL-->
<div class="risk-evaluation-add-modal">
	<div class="wpeo-modal modal-risk" id="risk_evaluation_add<?php echo $risk->id?>" value="<?php echo $risk->id?>">
		<div class="modal-container wpeo-modal-event">
			<!-- Modal-Header -->
			<div class="modal-header">
				<h2 class="modal-title"><?php echo $langs->trans('EvaluationCreate') . ' ' . $refEvaluationMod->getNextValue($evaluation)?></h2>
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
				<div class="risk-evaluation-container <?php echo $cotation->method; ?>">
					<div class="risk-evaluation-header">
						<?php if ($conf->global->DIGIRISKDOLIBARR_ADVANCED_RISKASSESSMENT_METHOD) : ?>
							<?php if ( $conf->global->DIGIRISKDOLIBARR_MULTIPLE_RISKASSESSMENT_METHOD == 1 ) : ?>
								<div class="wpeo-button evaluation-standard select-evaluation-method<?php echo ($cotation->method == "standard") ? " selected button-blue" : " button-grey" ?> button-radius-2">
									<span><?php echo $langs->trans('SimpleCotation') ?></span>
								</div>
								<div class="wpeo-button evaluation-advanced select-evaluation-method<?php echo ($cotation->method == "advanced") ? " selected button-blue" : " button-grey" ?> button-radius-2">
									<span><?php echo $langs->trans('AdvancedCotation') ?></span>
								</div>
							<?php else : ?>
								<div class="wpeo-button evaluation-standard select-evaluation-method<?php echo ($cotation->method == "standard") ? " selected button-blue" : " button-grey button-disable" ?> button-radius-2">
									<span><?php echo $langs->trans('SimpleCotation') ?></span>
								</div>
								<div class="wpeo-button evaluation-advanced select-evaluation-method<?php echo ($cotation->method == "advanced") ? " selected button-blue" : " button-grey button-disable" ?> button-radius-2">
									<span><?php echo $langs->trans('AdvancedCotation') ?></span>
								</div>
							<?php endif; ?>
							<i class="fas fa-info-circle wpeo-tooltip-event" aria-label="<?php echo $langs->trans("HowToSetMultipleRiskAssessmentMethod") ?>"></i>
						<?php endif; ?>
						<input class="risk-evaluation-method" type="hidden" value="<?php echo ($cotation->method == "standard") ? "standard" : "advanced" ?>">
						<input class="risk-evaluation-multiple-method" type="hidden" value="<?php echo $conf->global->DIGIRISKDOLIBARR_MULTIPLE_RISKASSESSMENT_METHOD ?>">
						<div class="wpeo-button open-media-gallery add-media modal-open" value="0">
							<input type="hidden" class="type-from" value="riskassessment"/>
							<span><i class="fas fa-camera"></i>  <?php echo $langs->trans('AddMedia') ?></span>
						</div>
					</div>
					<div class="risk-evaluation-content-wrapper">
						<div class="risk-evaluation-content">
							<div class="cotation-container">
								<div class="cotation-standard" style="<?php echo ($cotation->method !== "advanced") ? " display:block" : " display:none" ?>">
									<span class="title"><i class="fas fa-chart-line"></i><?php echo ' ' . $langs->trans('Cotation'); ?><required>*</required></span>
									<div class="cotation-listing wpeo-gridlayout grid-4 grid-gap-0">
										<?php
										$defaultCotation = array(0, 48, 51, 100);
										if ( ! empty( $defaultCotation )) :
											foreach ( $defaultCotation as $request ) :
												$evaluation->cotation = $request; ?>
												<div data-id="<?php echo 0; ?>"
													 data-evaluation-method="standard"
													 data-evaluation-id="<?php echo $request; ?>"
													 data-variable-id="<?php echo 152+$request; ?>"
													 data-seuil="<?php echo  $evaluation->get_evaluation_scale(); ?>"
													 data-scale="<?php echo  $evaluation->get_evaluation_scale(); ?>"
													 class="risk-evaluation-cotation cotation"><?php echo $request; ?></div>
											<?php endforeach;
										endif; ?>
									</div>
								</div>
								<input class="risk-evaluation-seuil" type="hidden">
								<?php $evaluation_method = $advanced_method_cotation_array[0];
								$evaluation_method_survey = $evaluation_method['option']['variable']; ?>
								<div class="wpeo-gridlayout cotation-advanced" style="<?php echo ($cotation->method == "advanced") ? " display:block" : " display:none" ?>">
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
										<?php foreach($evaluation_method_survey as $critere) :
											$name = strtolower($critere['name']); ?>
											<div class="table-row">
												<div class="table-cell"><?php echo $critere['name'] ; ?></div>
												<?php foreach($critere['option']['survey']['request'] as $request) : ?>
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

						<?php include DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/core/tpl/digiriskdolibarr_photo_view.tpl.php'; ?>

						<div class="risk-evaluation-calculated-cotation" style="<?php echo ($cotation->method == "advanced") ? " display:block" : " display:none" ?>">
							<span class="title"><i class="fas fa-chart-line"></i> <?php echo $langs->trans('CalculatedCotation'); ?><required>*</required></span>
							<div data-scale="1" class="risk-evaluation-cotation cotation">
								<span><?php echo 0 ?></span>
							</div>
						</div>
						<div class="risk-evaluation-comment">
							<span class="title"><i class="fas fa-comment-dots"></i> <?php echo $langs->trans('Comment'); ?></span>
							<?php print '<textarea name="evaluationComment'. $risk->id .'" rows="'.ROWS_2.'">'.('').'</textarea>'."\n"; ?>
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
						print digirisk_show_medias_linked('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/tmp/' .  $risk->ref . '/' , 'small', '', 0, 0, 0, 150, 150, 1, 0, 0, $cotation->element . '/tmp/' .  $risk->ref);
						?>
					</div>

				</div>
				<!-- RISK EVALUATION SINGLE -->
				<?php if (!empty($lastEvaluation) && $lastEvaluation > 0) : ?>
					<div class="risk-evaluation-container risk-evaluation-container-<?php echo $risk->ref ?>">
						<h2><?php echo $langs->trans('LastRiskAssessment') . ' ' . $risk->ref; ?></h2>
						<div class="risk-evaluation-single-content risk-evaluation-single-content-<?php echo $risk->id ?>">
							<div class="risk-evaluation-single">
								<div class="risk-evaluation-cotation risk-evaluation-list" value="<?php echo $risk->id ?>" data-scale="<?php echo $lastEvaluation->get_evaluation_scale() ?>">
									<span><?php echo $lastEvaluation->cotation; ?></span>
								</div>
								<div class="risk-evaluation-photo risk-evaluation-photo-<?php echo $cotation->id > 0 ?  $cotation->id :  0 ; echo $risk->id > 0 ? ' risk-'.$risk->id : ' risk-new' ?>">
									<?php $filearray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$lastEvaluation->element.'/'.$lastEvaluation->ref, "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'asc', 1);
									if (count($filearray)) {
										print '<img height="40" width="100%" class="photo clicked-photo-preview" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=digiriskdolibarr&entity='.$conf->entity.'&file='.urlencode($lastEvaluation->element.'/'.$lastEvaluation->ref . '/thumbs/'. preg_replace('/\./', '_small.', $lastEvaluation->photo)).'" >';
									} else {
										$nophoto = '/public/theme/common/nophoto.png'; ?>
										<span class="floatleft inline-block valignmiddle divphotoref"><img class="photodigiriskdolibarr clicked-photo-preview" alt="No photo" src="<?php echo DOL_URL_ROOT.$nophoto ?>"></span>
									<?php } ?>
								</div>
								<div class="risk-evaluation-content">
									<div class="risk-evaluation-data">
										<!-- BUTTON MODAL RISK EVALUATION LIST  -->
										<span class="risk-evaluation-reference risk-evaluation-list" value="<?php echo $risk->id ?>"><?php echo $lastEvaluation->ref; ?></span>
										<span class="risk-evaluation-date">
											<i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', (($conf->global->DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE && (!empty($lastEvaluation->date_riskassessment))) ? $lastEvaluation->date_riskassessment : $lastEvaluation->date_creation)); ?>
										</span>
									</div>
									<div class="risk-evaluation-comment">
										<span class="risk-evaluation-author">
											<?php $user->fetch($lastEvaluation->fk_user_creat); ?>
											<?php echo getNomUrl( 0, '', 0, 0, 2 , 0,'','',-1, $user); ?>
										</span>
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
					<div class="risk-evaluation-create wpeo-button button-blue button-disable" value="<?php echo $risk->id ?>">
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
