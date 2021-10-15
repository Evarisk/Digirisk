<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
if (empty($conf) || !is_object($conf))
{
	print "Error, template page digiriskdolibarr_photo_view.tpl.tpl.php can't be called as URL";
	exit;
}

?>

<!-- BEGIN PHP TEMPLATE core/tpl/digiriskdolibarr_photo_view.tpl.php -->

<?php $permtoupload = $user->rights->ecm->upload;?>
<div class="risk-evaluation-photo" value="<?php echo $risk->id ?>">
	<span class="title"><?php echo $langs->trans('Photo'); ?></span>
	<div class="risk-evaluation-photo-container wpeo-modal-event tooltip hover">
		<?php
		$entity = ($conf->entity > 1) ? '/' . $conf->entity  : '';

		$relativepath = 'digiriskdolibarr/medias/thumbs/';
		$modulepart = $entity . 'ecm';
		$path = DOL_URL_ROOT.'/document.php?modulepart=' . $modulepart  . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath) . '/';
		$pathToThumb = DOL_URL_ROOT.'/viewimage.php?modulepart=digiriskdolibarr&entity='.$conf->entity.'&file='.urlencode($cotation->element.'/'.$cotation->ref . '/thumbs/');
		$nophoto = '/public/theme/common/nophoto.png'; ?>
		<!-- BUTTON RISK EVALUATION PHOTO MODAL -->
		<div class="action risk-evaluation-photo default-photo modal-open risk-evaluation-photo-<?php echo $cotation->id > 0 ?  $cotation->id :  0 ; echo $risk->id > 0 ? ' risk-'.$risk->id : ' risk-new' ?>">
			<?php if (isset($cotation->photo)) {
				$filearray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$cotation->element.'/'.$cotation->ref, "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'asc', 1);
				if (count($filearray)) {
					?>
					<span class="floatleft inline-block valignmiddle divphotoref risk-evaluation-photo-single">
						<input class="filepath-to-riskassessment filepath-to-riskassessment-<?php echo $risk->id > 0 ? $risk->id : 'new' ?>" type="hidden" value="<?php echo $pathToThumb ?>">
						<input class="filename" type="hidden" value="">
						 <?php 	print '<img width="40" class="photo clicked-photo-preview" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=digiriskdolibarr&entity='.$conf->entity.'&file='.urlencode($cotation->element.'/'.$cotation->ref . '/thumbs/'. preg_replace('/\./', '_small.', $cotation->photo)).'" >';
						 ?>
					</span>
					<?php
				} else {
					$nophoto = '/public/theme/common/nophoto.png'; ?>
					<span class="floatleft inline-block valignmiddle divphotoref risk-evaluation-photo-single">
						<input class="filepath-to-riskassessment filepath-to-riskassessment-<?php echo $risk->id > 0 ? $risk->id : 'new' ?>" type="hidden" value="<?php echo $pathToThumb ?>">
						<input class="filename" type="hidden" value="">
						<img class="photodigiriskdolibarr clicked-photo-preview maxwidth50" alt="No photo" src="<?php echo $pathToThumb ?>">
					</span>
				<?php }
			} else { ?>
			<span class="floatleft inline-block valignmiddle divphotoref risk-evaluation-photo-single" value="<?php echo $risk->id ?>">
				<?php $pathToThumb = DOL_URL_ROOT.'/viewimage.php?modulepart=digiriskdolibarr&entity='.$conf->entity.'&file='.urlencode('/riskassessment/tmp/'.($risk->id > 0 ? $risk->ref : 'RK0') . '/thumbs/');  ?>
				<input class="filepath-to-riskassessment filepath-to-riskassessment-<?php echo $risk->id > 0 ? $risk->id : 'new' ?>" type="hidden" value="<?php echo $pathToThumb ?>">
				<input class="filename" type="hidden" value="">
				<img class="clicked-photo-preview photo maxwidth50"  src="<?php echo $pathToThumb ?>">
			</span>
			<?php } ?>
		</div>
	</div>
</div>

<!-- END PHP TEMPLATE core/tpl/digiriskdolibarr_photo_view.tpl.php -->
