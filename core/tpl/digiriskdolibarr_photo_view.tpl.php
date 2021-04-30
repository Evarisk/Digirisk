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

<div class="risk-evaluation-photo">
	<span class="title"><?php echo $langs->trans('Photo'); ?></span>
	<div class="risk-evaluation-photo-container wpeo-modal-event tooltip hover">
		<?php
		$relativepath = 'digiriskdolibarr/medias';
		$modulepart = 'ecm';
		$path = DOL_URL_ROOT.'/document.php?modulepart=' . $modulepart  . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath) . '/';
		$nophoto = '/public/theme/common/nophoto.png'; ?>
		<!-- BUTTON RISK EVALUATION PHOTO MODAL -->
		<div class="action risk-evaluation-photo default-photo modal-open" value="<?php echo $object->id ?>">
			<span class="floatleft inline-block valignmiddle divphotoref risk-evaluation-photo-single">
				<input type="hidden" value="<?php echo $path ?>">
				<input class="filename" type="hidden" value="">
				<img class="photo maxwidth50"  src="<?php echo DOL_URL_ROOT.'/public/theme/common/nophoto.png' ?>">
			</span>
		</div>
		<!-- RISK EVALUATION PHOTO MODAL -->
		<div class="wpeo-modal modal-photo" id="risk_evaluation_photo<?php echo $object->id ?>" data-id="<?php echo $object->id ?>">
			<div class="modal-container wpeo-modal-event">
				<!-- Modal-Header -->
				<div class="modal-header">
					<h2 class="modal-title"><?php echo $langs->trans('ModalAddPhoto') ?></h2>
					<div class="modal-close modal-refresh"><i class="fas fa-2x fa-times"></i></div>
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
						$formfile->form_attach_new_file($_SERVER["PHP_SELF"], 'none', 0, ($section ? $section : -1), $permtoupload, 48, null, '', 0, '', 0, $nameforformuserfile, '', $sectiondir, 1);
					} else print '&nbsp;';
					// End "Add new file" area
					?>
					<div class="underbanner clearboth"></div>
					<div class="wpeo-gridlayout grid-5 grid-gap-3 grid-margin-2 ecm-photo-list ecm-photo-list-<?php echo $risk->id ?>">
						<?php
						$entity =($conf->entity > 1) ? '/' . $conf->entity : '';
						$files =  dol_dir_list(DOL_DATA_ROOT .$entity. '/ecm/digiriskdolibarr/medias');
						$relativepath = 'digiriskdolibarr/medias';
						$modulepart = 'ecm';
						$path = DOL_URL_ROOT.'/document.php?modulepart=' . $modulepart  . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath);
						$j = 0;

						if ( !empty($files) ) :
							foreach ($files as $file) :
								?>
								<div class="center clickable-photo clickable-photo<?php echo $j; ?>" value="<?php echo $j; ?>" element="risk-evaluation">
									<figure class="photo-image">
										<?php $urladvanced = getAdvancedPreviewUrl($modulepart, $relativepath . '/' . $file['relativename'], 0, 'entity='.$object->entity); ?>
										<a class="clicked-photo-preview" href="<?php echo $urladvanced; ?>"><i class="fas fa-2x fa-search-plus"></i></a>
										<?php if (image_format_supported($file['name']) >= 0) : ?>
											<?php $fullpath = $path . '/' . $file['relativename'] . '&entity=' . $conf->entity; ?>
											<input class="filename" type="hidden" value="<?php echo $file['name'] ?>">
											<img class="photo photo<?php echo $j ?> maxwidth200" src="<?php echo $fullpath; ?>">
										<?php endif; ?>
									</figure>
									<div class="title"><?php echo $file['name']; ?></div>
								</div>
								<?php
								$j++;
							endforeach;
						endif; ?>
					</div>
				</div>
				<!-- Modal-Footer -->
				<div class="modal-footer">
					<div class="save-photo wpeo-button button-blue">
						<span><?php echo $langs->trans('Add'); ?></span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- END PHP TEMPLATE core/tpl/digiriskdolibarr_photo_view.tpl.php -->
