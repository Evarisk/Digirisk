<div class="wpeo-dropdown">
	<div class="dropdown-toggle wpeo-button button-main"><span><?php echo $langs->trans('ActionsSignature'); ?></span> <i class="fas fa-caret-down"></i></div>
	<ul class="dropdown-content">
		<li class="dropdown-item"></li>
		<?php if ($element->role == 'PP_EXT_SOCIETY_INTERVENANTS') : ?>
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
	</ul>
</div>





