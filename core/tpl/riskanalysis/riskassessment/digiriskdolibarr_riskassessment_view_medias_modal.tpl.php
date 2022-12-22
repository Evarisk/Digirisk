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
			<?php $lastEvaluation = $lastEvaluation;
			include DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/core/tpl/medias/digiriskdolibarr_photo_view.tpl.php'; ?>
			<!-- Modal Content-->
			<div class="modal-content" id="#modalContent<?php echo $lastEvaluation->id ?>">
				<div class="risk-evaluation-container <?php echo $lastEvaluation->method; ?>">
					<div class="risk-evaluation-header">

					</div>
					<div class="element-linked-medias element-linked-medias-<?php echo $lastEvaluation->id ?> modal-media-linked">
						<div class="medias"><i class="fas fa-picture-o"></i><?php echo $langs->trans('Medias'); ?></div>
						<?php
						$relativepath = 'digiriskdolibarr/medias/thumbs';
						print digirisk_show_medias_linked('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessment/', 'small', 0, 0, 0, 0, 150, 150, 1, 0, 0, $lastEvaluation->element, $lastEvaluation);
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
