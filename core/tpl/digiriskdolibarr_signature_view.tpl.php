<?php if (empty($element->signature) && $object->status == 2 || $element->signature == $langs->trans("FileGenerated")) : ?>
	<div class="wpeo-button button-blue wpeo-modal-event modal-signature-open modal-open" value="<?php echo $element->id ?>">
		<span><i class="fas fa-signature"></i> <?php echo $langs->trans('Sign'); ?></span>
	</div>
<?php elseif (!empty($element->signature)) : ?>
	<img class="wpeo-modal-event modal-signature-open modal-open" value="<?php echo $element->id ?>" src='<?php echo $element->signature ?>' width="100px" height="100px" style="border: #0b419b solid 2px">
<?php endif; ?>

<div class="modal-signature" value="<?php echo $element->id ?>">
	<div class="wpeo-modal modal-signature" id="modal-signature<?php echo $element->id ?>">
		<div class="modal-container wpeo-modal-event">
			<!-- Modal-Header-->
			<div class="modal-header">
				<h2 class="modal-title"><?php echo $langs->trans('Signature'); ?></h2>
				<div class="modal-close"><i class="fas fa-times"></i></div>
			</div>
			<!-- Modal-ADD Signature Content-->
			<div class="modal-content" id="#modalContent">
				<input type="hidden" id="signature_data<?php echo $element->id ?>" value="<?php echo $element->signature ?>">
				<canvas style="height: 95%; width: 95%; border: #0b419b solid 2px"></canvas>
			</div>
			<!-- Modal-Footer-->
			<div class="modal-footer">
				<div class="signature-erase wpeo-button button-grey">
					<span><i class="fas fa-eraser"></i> <?php echo $langs->trans('Erase'); ?></span>
				</div>
				<div class="wpeo-button button-grey modal-close">
					<span><?php echo $langs->trans('Cancel'); ?></span>
				</div>
				<div class="signature-validate wpeo-button button-primary" value="<?php echo $element->id ?>">
					<input type="hidden" id="redirect<?php echo $element->id ?>" value="<?php echo $url ?>">
					<input type="hidden" id="zone<?php echo $element->id ?>" value="<?php echo $zone ?>">
					<span><?php echo $langs->trans('Validate'); ?></span>
				</div>
			</div>
		</div>
	</div>
</div>




