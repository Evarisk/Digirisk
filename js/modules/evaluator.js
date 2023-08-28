
/**
 * Initialise l'objet "évaluateur" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.digiriskdolibarr.evaluator = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.evaluator.init = function() {
	window.digiriskdolibarr.evaluator.event();
};

/**
 * La méthode contenant tous les événements pour l'évaluateur.
 *
 * @since   1.0.0
 * @version 9.6.0
 *
 * @return {void}
 */
window.digiriskdolibarr.evaluator.event = function() {
	$( document ).on( 'click', '.evaluator-create', window.digiriskdolibarr.evaluator.createEvaluator );
	$( document ).on( 'change', '#fk_user_employer', window.digiriskdolibarr.evaluator.selectUser );
};

/**
 * Clique sur une des user de la liste.
 *
 * @since   1.0.0
 * @version 9.6.0
 *
 * @param  {ClickEvent} event L'état du clic.
 * @return {void}
 */
window.digiriskdolibarr.evaluator.selectUser = function( event ) {
	let token = window.saturne.toolbox.getToken()

	var elementParent = $(this).closest('.modal-container')
	let userID = elementParent.find('#fk_user_employer').val()

	window.saturne.loader.display(elementParent.find('input[name="evaluatorJob"]'));

	$.ajax({
		url:  document.URL + '&action=getEvaluatorJob&token='+token,
		type: "POST",
		processData: false,
		data: JSON.stringify({
			userID: userID
		}),
		contentType: false,
		success: function ( resp ) {
			elementParent.find('input[name="evaluatorJob"]').val($(resp).find('input[name="evaluatorJob"]').val())
			elementParent.find('input[name="evaluatorJob"]').removeClass('wpeo-loader')
		},
		error: function ( resp ) {
		}
	});

	// Rend le bouton "active".
	window.digiriskdolibarr.evaluator.haveDataInInput(elementParent);
}

/**
 * Check value on evaluatorUser.
 *
 * @since   9.6.0
 * @version 9.6.0
 *
 * @param  elementParent --- Parent element
 * @return {void}
 */
window.digiriskdolibarr.evaluator.haveDataInInput = function( elementParent ) {
	var element = elementParent.parent().parent();

	if (element.hasClass('evaluator-add-modal')) {
		var evaluator_user = element.find('#fk_user_employer');
		if ( evaluator_user.val() > 0 ) {
			element.find('.button-disable').removeClass('button-disable');
		} else {
			element.find('.evaluator-create').addClass('button-disable');
		}
	}
};

/**
 * Action create evaluator.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.evaluator.createEvaluator = function ( event ) {
	let token = window.saturne.toolbox.getToken()

	let elementEvaluator = $(this).closest('.fichecenter').find('.evaluator-content');

	var userName = $('#select2-fk_user_employer-container').attr('title')
	var userID  = $('#fk_user_employer').find("option:contains('"+userName+"')").attr('value')

	var date     = elementEvaluator.find('#EvaluatorDate').val();
	var duration = elementEvaluator.find('.evaluator-duration .duration').val();
	var job      = elementEvaluator.find('.evaluatorJob').val();

	let elementParent = $(this).closest('.fichecenter').find('.div-table-responsive');

	window.saturne.loader.display(elementParent);

	$.ajax({
		url: document.URL + '&action=add&token='+token,
		type: "POST",
		data: JSON.stringify({
			evaluatorID: userID,
			date: date,
			duration: duration,
			job: job
		}),
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('.fichecenter').html($(resp).find('#searchFormList'))

			let actionContainerSuccess = $('.messageSuccessEvaluatorCreate');

			actionContainerSuccess.find($(resp).find('.evaluator-create-success-notice'))
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( resp ) {
			let actionContainerError = $('.messageErrorEvaluatorCreate');

			actionContainerError.find($(resp).find('.evaluator-create-error-notice'))
			actionContainerError.removeClass('hidden');
		}
	});

};
