
/**
 * Initialise l'objet "accident" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * @since   8.5.0
 * @version 8.5.0
 */
window.digiriskdolibarr.accident = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   8.5.0
 * @version 8.5.0
 *
 * @return {void}
 */
window.digiriskdolibarr.accident.init = function() {
	window.digiriskdolibarr.accident.event();
};

/**
 * La méthode contenant tous les événements pour les accidents.
 *
 * @since   8.5.0
 * @version 8.5.0
 *
 * @return {void}
 */
window.digiriskdolibarr.accident.event = function() {
	$( document ).on( 'submit', '.sendfile', window.digiriskdolibarr.accident.tmpStockFile );
	$( document ).on( 'click', '.linked-file-delete-workstop', window.digiriskdolibarr.accident.removeFile );
	$( document ).on( 'change', '#external_accident', window.digiriskdolibarr.accident.showExternalAccidentLocation );
};

/**
 * Upload automatiquement le(s) fichier(s) sélectionnés dans digiriskdolibarr/accident/accident_ref/workstop/__REF__ (temp ou ref du workstop)
 *
 * @since   8.5.0
 * @version 8.5.0
 *
 * @return {void}
 */
window.digiriskdolibarr.accident.tmpStockFile = function(id, subaction = '') {
	var files = $('#sendfile').prop('files');

	const formData = new FormData();
	for (let i = 0; i < files.length; i++) {
		let file = files[i]
		formData.append('files[]', file)
	}

	let token         = window.saturne.toolbox.getToken();
	let subactionPost = subaction.length > 0 ? '&subaction=' + subaction : ''

	$.ajax({
		url: document.URL + '&action=sendfile&objectlineid=' + id + '&token=' + token + subactionPost,
		type: "POST",
		processData: false,
		contentType: false,
		data: formData,
		success: function ( resp ) {
			$('#sendFileForm' + id).html($(resp).find('#fileLinkedTable' + id))
		},
		error: function ( ) {
		}
	});
};

/**
 * Supprime le fichier sélectionné dans digiriskdolibarr/accident/accident_ref/workstop/__REF__ (temp ou ref du workstop)
 *
 * @since   8.5.0
 * @version 8.5.0
 *
 * @return {void}
 */

window.digiriskdolibarr.accident.removeFile = function( event ) {
	let filetodelete  = $(this).attr('value');
	let subActionPost = $(this).hasClass('edit-line') ? '&subaction=editline' : ''

	filetodelete = filetodelete.replace('_mini', '')

	let objectlineid  = $(this).closest('.objectline').attr('value')
	let token         = window.saturne.toolbox.getToken();

	$.ajax({
		url: document.URL + '&action=removefile&filetodelete=' + filetodelete + '&objectlineid=' + objectlineid + '&token=' + token + subActionPost,
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('#sendFileForm' + objectlineid).html($(resp).find('#fileLinkedTable' + objectlineid))
		},
		error: function ( ) {
		}
	});
};

/**
 * Show external Accident location.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.accident.showExternalAccidentLocation = function() {
	let fkElementField        = $(this).closest('.accident-table').find('.fk_element_field')
	let fkSocField            = $(this).closest('.accident-table').find('.fk_soc_field')
	let accidentLocationField = $(this).closest('.accident-table').find('.accident_location_field')
	let externalAccident      = $(this).closest('.accident-table').find('#select2-external_accident-container').attr('title')

	switch (externalAccident) {
		case 'Non':
			fkElementField.attr('style', '')
			fkSocField.attr('style', 'display:none')
			accidentLocationField.attr('style', 'display:none')
			fkElementField.removeClass('hidden')
			fkSocField.addClass('hidden')
			accidentLocationField.addClass('hidden')
			break;
		case 'Oui':
			fkElementField.attr('style', 'display:none')
			fkSocField.attr('style', '')
			accidentLocationField.attr('style', 'display:none')
			fkElementField.addClass('hidden')
			fkSocField.removeClass('hidden')
			accidentLocationField.addClass('hidden')
			break;
		case 'Autre':
			fkElementField.attr('style', 'display:none')
			fkSocField.attr('style', 'display:none')
			accidentLocationField.attr('style', '')
			fkElementField.addClass('hidden')
			fkSocField.addClass('hidden')
			accidentLocationField.removeClass('hidden')
			break;
	}
};
