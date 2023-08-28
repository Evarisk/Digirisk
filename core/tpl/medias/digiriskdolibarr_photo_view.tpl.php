<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */


// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf)) {
	print "Error, template page digiriskdolibarr_photo_view.tpl.php can't be called as URL";
	exit;
}

?>

<!-- BEGIN PHP TEMPLATE core/tpl/digiriskdolibarr_photo_view.tpl.php -->

<?php $permtoupload = $user->rights->ecm->upload;?>
<div class="risk-evaluation-photo" value="<?php echo ($risk->id > 0 && !preg_match('/PROV/',$risk->ref) ? $risk->id : 0) ?>">
	<?php if (!$view) : ?>
		<span class="title"><?php echo $langs->trans('Photo'); ?></span>
	<?php endif; ?>
	<div class="risk-evaluation-photo-container wpeo-modal-event tooltip hover">
		<?php
		$entity = ($riskAssessment->entity > 1) ? '/' . $riskAssessment->entity : '';
		$relativepath = 'digiriskdolibarr/medias/thumbs/';
		$modulepart   = $entity . 'ecm';
		$path         = DOL_URL_ROOT . '/document.php?modulepart=' . $modulepart . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath) . '/';
		$pathToThumb  = DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=digiriskdolibarr&entity=' . $riskAssessment->entity . '&file=' . urlencode($riskAssessment->element . '/' . $riskAssessment->ref . '/thumbs/');
		$nophoto      = DOL_URL_ROOT.'/public/theme/common/nophoto.png';
		?>
		<!-- BUTTON RISK EVALUATION PHOTO MODAL -->
		<div class="action risk-evaluation-photo default-photo modal-open risk-evaluation-photo-<?php echo $riskAssessment->id > 0 ? $riskAssessment->id : 0 ; echo $risk->id > 0  && !preg_match('/PROV/',$risk->ref) ? ' risk-' . $risk->id : ' risk-new' ?>">
			<input hidden class="no-photo-path" value="<?php echo $nophoto ?>">
			<?php if (isset($riskAssessment->photo)) {
				$filearray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$riskAssessment->entity] . '/' . $riskAssessment->element . '/' . $riskAssessment->ref, "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'asc', 1);
				if (($riskAssessment->entity != $conf->entity || count($filearray)) && dol_strlen($riskAssessment->photo) > 0) {
					?>
					<span class="floatleft inline-block valignmiddle divphotoref risk-evaluation-photo-single">
						<input class="filepath-to-riskassessment filepath-to-riskassessment-<?php echo $risk->id > 0 ? $risk->id : 'new' ?>" type="hidden" value="<?php echo $pathToThumb ?>">
						<input class="filename" type="hidden" value="">

						 <?php
							 $urladvanced = getAdvancedPreviewUrl('ecm', 'digiriskdolibarr/medias/' .  $riskAssessment->photo, 0, 'entity=' . $conf->entity);
							 $thumb_name = getThumbName($riskAssessment->photo);
							 print '<a class="clicked-photo-preview" href="'. $urladvanced .'">';
							 print '<img width="50" height="50" class="photo clicked-photo-preview" src="' . DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=digiriskdolibarr&entity=' . $riskAssessment->entity . '&file=' . urlencode($riskAssessment->element . '/' . $riskAssessment->ref . '/thumbs/' . $thumb_name) . '" ></a>';
						 ?>
					</span>

					<?php
				} else {
					?>
					<span class="floatleft inline-block valignmiddle divphotoref risk-evaluation-photo-single">
						<input class="filepath-to-riskassessment filepath-to-riskassessment-<?php echo $risk->id > 0 ? $risk->id : 'new' ?>" type="hidden" value="<?php echo $pathToThumb ?>">
						<input class="filename" type="hidden" value="">
						<img class="photodigiriskdolibarr clicked-photo-preview maxwidth50" width="50" height="50" alt="No photo" src="<?php echo $nophoto ?>">
					</span>
				<?php }
			} else { ?>
				<span class="floatleft inline-block valignmiddle divphotoref risk-evaluation-photo-single" value="<?php echo $risk->id ?>">
					<?php $pathToThumb = DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=digiriskdolibarr&entity=' . $conf->entity . '&file=' . urlencode('/riskassessment/tmp/' . ($risk->id > 0 && !preg_match('/PROV/',$risk->ref) ? $risk->ref : 'RK0') . '/thumbs/');  ?>
					<input class="filepath-to-riskassessment filepath-to-riskassessment-<?php echo $risk->id > 0 ? $risk->id : 'new' ?>" type="hidden" value="<?php echo $pathToThumb ?>">
					<input class="filename" type="hidden" value="">
					<img class="clicked-photo-preview photo maxwidth50" width="50" height="50" src="<?php echo $nophoto ?>">
				</span>
			<?php } ?>
		</div>
	</div>
</div>

<!-- END PHP TEMPLATE core/tpl/digiriskdolibarr_photo_view.tpl.php -->
