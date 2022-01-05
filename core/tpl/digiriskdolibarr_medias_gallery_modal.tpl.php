<?php
if ( ! $error && $action == "uploadPhoto" && ! empty($conf->global->MAIN_UPLOAD_DOC)) {
	// Define relativepath and upload_dir
	$relativepath                                             = 'digiriskdolibarr/medias';
	$upload_dir                                               = $conf->ecm->dir_output . '/' . $relativepath;
	if (is_array($_FILES['userfile']['tmp_name'])) $userfiles = $_FILES['userfile']['tmp_name'];
	else $userfiles                                           = array($_FILES['userfile']['tmp_name']);


	foreach ($userfiles as $key => $userfile) {
		if (empty($_FILES['userfile']['tmp_name'][$key])) {
			$error++;
			if ($_FILES['userfile']['error'][$key] == 1 || $_FILES['userfile']['error'][$key] == 2) {
				setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
			} else {
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), null, 'errors');
			}
		}
	}
	if ( ! $error) {
		$generatethumbs = 1;
		$res            = dol_add_file_process($upload_dir, 0, 1, 'userfile', '', null, '', $generatethumbs);
		if ($res > 0) {
			$result = $ecmdir->changeNbOfFiles('+');
		}
	}
}
?>
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
			if (( ! empty($conf->use_javascript_ajax) && empty($conf->global->MAIN_ECM_DISABLE_JS)) || ! empty($section)) {
				$sectiondir = GETPOST('file', 'alpha') ? GETPOST('file', 'alpha') : GETPOST('section_dir', 'alpha');
				print '<!-- Start form to attach new file in digiriskdolibarr_photo_view.tpl.tpl.php sectionid=' . $section . ' sectiondir=' . $sectiondir . ' -->' . "\n";
				include_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
				print '<strong>' . $langs->trans('AddFile') . '</strong>'
				?>

				<input type="file" id="add_media_to_gallery" class="flat minwidth400 maxwidth200onsmartphone" name="userfile[]" multiple accept>
			<?php } else print '&nbsp;';
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
