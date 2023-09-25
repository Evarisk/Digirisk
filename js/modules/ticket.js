
/**
 * Initialise l'objet "ticket" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * @since   1.1.0
 * @version 1.1.0
 */
window.digiriskdolibarr.ticket = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticket.init = function() {
	window.digiriskdolibarr.ticket.event();
};

/**
 * La méthode contenant tous les événements pour les tickets.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticket.event = function() {
	$( document ).on( 'click', '.ticket-parentCategory', window.digiriskdolibarr.ticket.selectParentCategory );
	$( document ).on( 'click', '.ticket-subCategory', window.digiriskdolibarr.ticket.selectSubCategory );
	$( document ).on( 'submit', '#sendFile', window.digiriskdolibarr.ticket.tmpStockFile );
	$( document ).on( 'click', '.linked-file-delete', window.digiriskdolibarr.ticket.removeFile );
	$( document ).on( 'change', '.add-dashboard-info', window.digiriskdolibarr.ticket.addDashBoardTicketInfo );
	$( document ).on( 'click', '.close-dashboard-info', window.digiriskdolibarr.ticket.closeDashBoardTicketInfo );
	$( document ).on( 'keyup', '.email', window.digiriskdolibarr.ticket.checkValidEmail );
	$( document ).on( 'keyup', '.options_digiriskdolibarr_ticket_phone', window.digiriskdolibarr.ticket.checkValidPhone );
};

/**
 * Mets à jour les input du formulaire
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticket.updateFormData = function( ) {
	let parentCategoryID = window.digiriskdolibarr.ticket.getParentCategory()
	let subCategoryID    = window.digiriskdolibarr.ticket.getSubCategory()

	$('.ticket-parentCategory.active').removeClass('active')
	$('.ticket-parentCategory'+parentCategoryID).addClass('active')

	$('.subCategories').attr('style','display:none')
	$('.children'+parentCategoryID).attr('style','')

	$('.ticket-subCategory.active').removeClass('active')
	$('.ticket-subCategory'+subCategoryID).addClass('active')
};

/**
 * Clique sur une des catégories parentes de la liste.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticket.selectParentCategory = function( ) {
	let subCategoryInput    = $('.ticketpublicarea').find("#subCategory");
	let parentCategoryInput = $('.ticketpublicarea').find("#parentCategory");

	subCategoryInput.val(0)
	parentCategoryInput.val($(this).attr('id'))

	window.digiriskdolibarr.ticket.updateFormData()
};

/**
 * Récupère la valeur de la catégorie parente sélectionnée
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticket.getParentCategory = function( ) {
	return $('.ticketpublicarea').find("#parentCategory").val()
};

/**
 * Clique sur une des catégories enfants de la liste.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticket.selectSubCategory = function( ) {
	let subCategoryInput = $('.ticketpublicarea').find("#subCategory");
	subCategoryInput.val($(this).attr('id'))

	window.digiriskdolibarr.ticket.updateFormData()
};

/**
 * Récupère la valeur de la catégorie enfant sélectionnée
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticket.getSubCategory = function(  ) {
	return $('.ticketpublicarea').find("#subCategory").val()
};

/**
 * Upload automatiquement le(s) fichier(s) séelectionnés dans ecm/digiriskdolibarr/ticket/tmp/__REF__
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticket.tmpStockFile = function( ) {
	event.preventDefault()

	var files = $('#sendfile').prop('files');

	const formData = new FormData();
	for (let i = 0; i < files.length; i++) {
		let file = files[i]
		formData.append('files[]', file)
	}
	var ticket_id      = $('#ticket_id').val()
	let querySeparator = window.saturne.toolbox.getQuerySeparator()

	window.saturne.loader.display($('.files-uploaded'));

	fetch(document.URL + querySeparator + 'action=sendfile&ticket_id='+ticket_id, {
		method: 'POST',
		body: formData,
	}).then((resp) => {
		$('#sendFileForm').load(document.URL+ querySeparator + 'ticket_id='+ticket_id + ' #fileLinkedTable')
	})
};

/**
 * Upload automatiquement le(s) fichier(s) séelectionnés dans ecm/digiriskdolibarr/ticket/tmp/__REF__
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticket.removeFile = function( event ) {
	let filetodelete   = $(this).attr('value');
	filetodelete       = filetodelete.replace('_mini', '')
	let ticket_id      = $('#ticket_id').val()
	let querySeparator = window.saturne.toolbox.getQuerySeparator()

	fetch(document.URL + querySeparator + 'action=removefile&filetodelete='+filetodelete+'&ticket_id='+ticket_id, {
		method: 'POST',
	}).then((resp) => {
		$(this).parent().parent().hide()
	})
};

/**
 * Add ticket dashboard info for a category by service
 *
 * @since   9.5.0
 * @version 9.5.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticket.addDashBoardTicketInfo = function() {
	let token = window.saturne.toolbox.getToken()

	let selectTitle       = $('#select2-boxcombo-container').attr('title')
	let digiriskelementID = selectTitle.split(' : ')[0];
	let catID             = selectTitle.split(' : ')[2];
	let querySeparator    = window.saturne.toolbox.getQuerySeparator()

	$.ajax({
		url: document.URL + querySeparator + 'action=adddashboardinfo&token='+token,
		type: "POST",
		processData: false,
		data: JSON.stringify({
			digiriskelementID: digiriskelementID,
			catID: catID
		}),
		contentType: false,
		success: function ( resp ) {
			window.location.reload();
		},
		error: function ( ) {
		}
	});
};

/**
 * Close ticket dashboard info for a category by service
 *
 * @since   9.5.0
 * @version 9.5.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticket.closeDashBoardTicketInfo = function() {
	let token = window.saturne.toolbox.getToken()

	let box               = $(this);
	let digiriskelementID = box.attr('data-digiriskelementid');
	let catID             = box.attr('data-catid');
	let querySeparator    = window.saturne.toolbox.getQuerySeparator()

	$.ajax({
		url: document.URL + querySeparator + 'action=closedashboardinfo&token='+token,
		type: "POST",
		processData: false,
		data: JSON.stringify({
			digiriskelementID: digiriskelementID,
			catID: catID
		}),
		contentType: false,
		success: function ( resp ) {
			box.closest('.box-flex-item').fadeOut(400)
			$('.add-widget-box').attr('style', '')
			$('.add-widget-box').html($(resp).find('.add-widget-box').children())
		},
		error: function ( ) {
		}
	});
};

/**
 * Check if email is valid
 *
 * @since   9.6.0
 * @version 9.6.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticket.checkValidEmail = function() {
	var reEmail = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
	if (reEmail.test(this.value) == false) {
		$(this).css("border", "3px solid red");
	} else {
		$(this).css("border", "3px solid green");
	}
};

/**
 * Check if phone is valid
 *
 * @since   9.8.2
 * @version 9.8.2
 *
 * @return {void}
 */
window.digiriskdolibarr.ticket.checkValidPhone = function() {
	var rePhone = /^(?:(?:(?:\+|00)\d{2}[\s]?(?:\(0\)[\s]?)?)|0){1}[1-9]{1}([\s.-]?)(?:\d{2}\1?){3}\d{2}$/;
	if (rePhone.test(this.value) == false) {
		$(this).css("border", "3px solid red");
	} else {
		$(this).css("border", "3px solid green");
	}
};
