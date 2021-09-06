<div class="wpeo-dropdown">
	<div class="dropdown-toggle wpeo-button button-main"><span><?php echo $langs->trans('ActionsSignature'); ?></span> <i class="fas fa-caret-down"></i></div>
	<ul class="dropdown-content">
		<?php if ($object->status == 2) :
			if ($element->role == 'PP_EXT_SOCIETY_INTERVENANTS') : ?>
				<li class="dropdown-item">
					<div class="signature-absent wpeo-button button-primary" value="<?php echo $element->id ?>">
						<span><?php echo $langs->trans('Absent'); ?></span>
					</div>
				</li>
			<?php endif; ?>
				<li class="dropdown-item">
					<div class="signature-email wpeo-button button-primary" value="<?php echo $element->id ?>">
						<span><i class="fas fa-at"></i> <?php echo $langs->trans('SendEmail'); ?></span>
					</div>
				</li>
		<?php endif; ?>
		<?php
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="deleteAttendant">';
		print '<input type="hidden" name="signatoryToDeleteID" value="'.$element->id.'">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
		?>
		<li class="dropdown-item">
			<button type="submit" name="deleteAttendant" id="deleteAttendant" class="attendant-delete wpeo-button button-primary" value="<?php echo $element->id ?>">
				<span><i class="fas fa-trash"></i> <?php echo $langs->trans('DeleteAttendant'); ?></span>
			</button>
		</li>
		<?php print '</form>'; ?>
	</ul>
</div>





