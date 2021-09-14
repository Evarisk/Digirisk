	<div class="wpeo-dropdown">
		<div class="dropdown-toggle wpeo-button button-main"><span><?php echo $langs->trans('ActionsSignature'); ?></span> <i class="fas fa-caret-down"></i></div>
		<ul class="dropdown-content">
		<?php if ($object->status == 2) : ?>
			<?php if ($element->role == 'PP_EXT_SOCIETY_INTERVENANTS') : ?>
				<li class="dropdown-item">
					<?php print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id='.$id.'">';
					print '<input type="hidden" name="token" value="' . newToken() . '">';
					print '<input type="hidden" name="action" value="setAbsent">';
					print '<input type="hidden" name="signatoryID" value="' . $element->id . '">';
					print '<input type="hidden" name="backtopage" value="' . $backtopage . '">'; ?>
					<button  type="submit" class="signature-absent wpeo-button button-primary" value="<?php echo $element->id ?>">
						<span><?php echo $langs->trans('Absent'); ?></span>
					</button>
					</form>
				</li>
			<?php endif; ?>
			<li class="dropdown-item">
				<?php print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id='.$id.'">';
				print '<input type="hidden" name="token" value="' . newToken() . '">';
				print '<input type="hidden" name="action" value="send">';
				print '<input type="hidden" name="signatoryID" value="' . $element->id . '">';
				print '<input type="hidden" name="backtopage" value="' . $backtopage . '">'; ?>
				<button type="submit" class="signature-email wpeo-button button-primary" value="<?php echo $element->id ?>">
					<span><i class="fas fa-at"></i> <?php echo $langs->trans('SendEmail'); ?></span>
				</button>
				</form>
			</li>

<?php endif; ?>

<?php if ($object->status == 1) : ?>
	<?php if ($element->role == 'PP_EXT_SOCIETY_INTERVENANTS') : ?>
		<li class="dropdown-item">
			<?php print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="deleteAttendant">';
			print '<input type="hidden" name="signatoryID" value="'.$element->id.'">';
			print '<input type="hidden" name="backtopage" value="'.$backtopage.'">'; ?>
			<button type="submit" name="deleteAttendant" id="deleteAttendant" class="attendant-delete wpeo-button button-primary" value="<?php echo $element->id ?>">
				<span><i class="fas fa-trash"></i> <?php echo $langs->trans('DeleteAttendant'); ?></span>
			</button>
			<?php print '</form>'; ?>
		</li>
	<?php endif; ?>
<?php endif; ?>

		</ul>
	</div>




