
/**
 * Initialise l'objet "form" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * @since   9.8.0
 * @version 9.8.0
 */
window.digiriskdolibarr.form = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   9.8.0
 * @version 9.8.0
 *
 * @return {void}
 */
window.digiriskdolibarr.form.init = function() {
	window.digiriskdolibarr.form.event();
};

/**
 * La méthode contenant tous les événements pour les boutons.
 *
 * @since   9.8.0
 * @version 9.8.0
 *
 * @return {void}
 */
window.digiriskdolibarr.form.event = function() {
	$( document ).on( 'submit', '#searchFormListRisks, #searchFormInheritedListRisks, #searchFormSharedListRisks', window.digiriskdolibarr.form.searchForm );
};

/**
 * Enlève les valeurs d'input vides d'un formulaire.
 *
 * @since   9.8.0
 * @version 9.8.0
 *
 * @return {void}
 */
window.digiriskdolibarr.form.searchForm = function(event) {
	event.preventDefault()

	let formId = $(this).closest('form').attr('id');

	var searchFormListRisks = document.getElementById(formId);
	var formData            = new FormData(searchFormListRisks);
	let newFormData         = new FormData();

	let dataToSend = [
		'token',
		'formfilteraction',
		'action',
		'id',
		'sortfield',
		'sortorder',
		'page',
		'contextpage',
		'limit',
		'toselect[]',
		'massaction',
		'confirm',
		'cancel',
		'pageplusone',
		'backtopage'
	]

	for (const pair of formData.entries()) {
		if (dataToSend.includes(pair[0]) || pair[0].match(/search_/)) {
			newFormData.append(pair[0], pair[1])
		}
	}

	window.saturne.loader.display($('#' + formId));

	$.ajax({
		url: document.URL,
		type: "POST",
		data: newFormData,
		processData: false,
		contentType: false,
		success: function (resp) {
			$('.wpeo-loader').removeClass('wpeo-loader');
			$('#' + formId).replaceWith($(resp).find('#' + formId))
		},
	});
}
