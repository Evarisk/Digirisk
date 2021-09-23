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

<?php $permtoupload = $user->rights->ecm->upload; ?>

<div class="risk-evaluation-photo" value="<?php echo $risk->id ?>">
	<span class="title"><?php echo $langs->trans('Photo'); ?></span>
	<div class="risk-evaluation-photo-container wpeo-modal-event tooltip hover">
		<?php
		$entity = ($conf->entity > 1) ? '/' . $conf->entity  : '';

		$relativepath = 'digiriskdolibarr/medias/thumbs/';
		$modulepart = $entity . 'ecm';
		$path = DOL_URL_ROOT.'/document.php?modulepart=' . $modulepart  . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath) . '/';
		$nophoto = '/public/theme/common/nophoto.png'; ?>
		<!-- BUTTON RISK EVALUATION PHOTO MODAL -->
		<div class="action risk-evaluation-photo default-photo modal-open" value="<?php echo $object->id ?>">
			<?php if (isset($cotation->photo)) {
				$filearray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$cotation->element.'/'.$cotation->ref, "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'asc', 1);
				if (count($filearray)) {
					?>
					<span class="floatleft inline-block valignmiddle divphotoref risk-evaluation-photo-single">
						<input type="hidden" value="<?php echo $path ?>">
						<input class="filename" type="hidden" value="">
						 <?php print digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$cotation->element, 'small', 1, 0, 0, 0, 40, 0, 1, 0, 0, $cotation->element, $cotation); ?>
					</span>
					<?php
				} else {
					$nophoto = '/public/theme/common/nophoto.png'; ?>
					<span class="floatleft inline-block valignmiddle divphotoref risk-evaluation-photo-single">
						<input type="hidden" value="<?php echo $path ?>">
						<input class="filename" type="hidden" value="">
						<img class="photodigiriskdolibarr" alt="No photo" src="<?php echo DOL_URL_ROOT.$nophoto ?>">
					</span>
				<?php }
			} else { ?>
			<span class="floatleft inline-block valignmiddle divphotoref risk-evaluation-photo-single">
				<input type="hidden" value="<?php echo $path ?>">
				<input class="filename" type="hidden" value="">
				<img class="photo maxwidth50"  src="<?php echo DOL_URL_ROOT.$nophoto ?>">
			</span>
			<?php } ?>
		</div>
		<!-- RISK EVALUATION PHOTO MODAL -->
		<div class="wpeo-modal modal-photo" id="risk_evaluation_photo<?php echo $object->id ?>" data-id="<?php echo $object->id ?>">
			<div class="modal-container wpeo-modal-event">
				<!-- Modal-Header -->
				<div class="modal-header">
					<h2 class="modal-title"><?php echo $langs->trans('ModalAddPhoto') ?></h2>
					<div class="modal-close"><i class="fas fa-2x fa-times"></i></div>
				</div>
				<!-- Modal-Content -->
				<div class="modal-content" id="#modalContent<?php echo $object->id ?>">
					<?php
					// To attach new file
					if ((!empty($conf->use_javascript_ajax) && empty($conf->global->MAIN_ECM_DISABLE_JS)) || !empty($section))
					{
						$sectiondir = GETPOST('file', 'alpha') ?GETPOST('file', 'alpha') : GETPOST('section_dir', 'alpha');
						print '<!-- Start form to attach new file in digiriskdolibarr_photo_view.tpl.tpl.php sectionid='.$section.' sectiondir='.$sectiondir.' -->'."\n";
						include_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
						$formfile = new FormFile($db);
						$formfile->form_attach_new_file($_SERVER["PHP_SELF"], 'none', 0, 0, $permtoupload, 48, null, '', 0, '', 0, $nameforformuserfile, '', $sectiondir, 1);
					} else print '&nbsp;';
					// End "Add new file" area
					?>
					<div class="underbanner clearboth"></div>
					<div class="ecm-photo-list-content">
						<div class="wpeo-gridlayout grid-4 grid-gap-3 grid-margin-2 ecm-photo-list ecm-photo-list-<?php echo $risk->id . ($editModal ? '-edit' : '') ?>">
							<?php
							$relativepath = 'digiriskdolibarr/medias/thumbs';

							print digirisk_show_medias('ecm', DOL_DATA_ROOT  . $entity. '/ecm/digiriskdolibarr/medias/thumbs', 'small');
							?>
						</div>
					</div>
				</div>
				<!-- Modal-Footer -->
				<div class="modal-footer">
					<div class="save-photo wpeo-button button-blue button-disable">
						<span><?php echo $langs->trans('Add'); ?></span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- END PHP TEMPLATE core/tpl/digiriskdolibarr_photo_view.tpl.php -->
