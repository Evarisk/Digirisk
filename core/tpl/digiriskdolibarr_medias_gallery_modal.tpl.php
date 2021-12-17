<!-- START MEDIA GALLERY MODAL -->
<div class="wpeo-modal modal-photo" id="media_gallery" data-id="<?php echo $object->id ?>">
	<div class="modal-container wpeo-modal-event">
		<!-- Modal-Header -->
		<div class="modal-header">
			<h2 class="modal-title"><?php echo $langs->trans('ModalAddPhoto')?></h2>
			<div class="modal-close"><i class="fas fa-2x fa-times"></i></div>
		</div>
		<!-- Modal-Content -->
		<div class="modal-content" id="#modalMediaGalleryContent">
			<div class="messageSuccessSendPhoto notice hidden">
				<div class="wpeo-notice notice-success send-photo-success-notice">
					<div class="notice-content">
						<div class="notice-title"><?php echo $langs->trans('PhotoWellSent') ?></div>
					</div>
					<div class="notice-close"><i class="fas fa-times"></i></div>
				</div>
			</div>
			<div class="messageErrorSendPhoto notice hidden">
				<div class="wpeo-notice notice-warning send-photo-error-notice">
					<div class="notice-content">
						<div class="notice-title"><?php echo $langs->trans('PhotoNotSent') ?></div>
					</div>
					<div class="notice-close"><i class="fas fa-times"></i></div>
				</div>
			</div>
			<?php
			// To attach new file
			if ((!empty($conf->use_javascript_ajax) && empty($conf->global->MAIN_ECM_DISABLE_JS)) || !empty($section))
			{
				$sectiondir = GETPOST('file', 'alpha') ?GETPOST('file', 'alpha') : GETPOST('section_dir', 'alpha');
				print '<!-- Start form to attach new file in digiriskdolibarr_photo_view.tpl.tpl.php sectionid='.$section.' sectiondir='.$sectiondir.' -->'."\n";
				include_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
				$formfile = new FormFile($db);
				$formfile->form_attach_new_file($_SERVER["PHP_SELF"], 'none', 0, 0, 1, 48, null, '', 0, '', 0, $nameforformuserfile, '', $sectiondir, 1);
			} else print '&nbsp;';
			// End "Add new file" area
			?>
			<div class="underbanner clearboth"></div>
			<div class="form-element">
				<div class="form-field-container">
					<div class="wpeo-autocomplete">
						<label class="autocomplete-label" for="media-gallery-search">
							<i class="autocomplete-icon-before far fa-search"></i>
							<input id="search_in_gallery" placeholder="<?php echo $langs->trans('Search') . '...' ?>" class="autocomplete-search-input" type="text" />
							<span class="autocomplete-icon-after"><i class="far fa-times"></i></span>
						</label>
					</div>
				</div>
			</div>
			<div class="ecm-photo-list-content">
				<div class="wpeo-gridlayout grid-4 grid-gap-3 grid-margin-2 ecm-photo-list ecm-photo-list">
					<?php
					$relativepath = 'digiriskdolibarr/medias/thumbs';
					print digirisk_show_medias('ecm', $conf->ecm->multidir_output[$conf->entity] . '/digiriskdolibarr/medias/thumbs', 'small');
					?>
				</div>
			</div>
		</div>
		<!-- Modal-Footer -->
		<div class="modal-footer">
			<div class="save-photo wpeo-button button-blue button-disable" value="">
				<input class="type-from" value="" type="hidden" />
				<span><?php echo $langs->trans('Add'); ?></span>
			</div>
		</div>
	</div>
</div>
<!-- END MEDIA GALLERY MODAL -->
