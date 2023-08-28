
/**
 * Initialise l'objet "risksign" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.digiriskdolibarr.risksign = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.risksign.init = function() {
	window.digiriskdolibarr.risksign.event();
};

/**
 * La méthode contenant tous les événements pour le risksign.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.risksign.event = function() {
	$( document ).on( 'click', '.risksign-category-danger .item, .wpeo-table .risksign-category-danger .item', window.digiriskdolibarr.risksign.selectRiskSign );
	$( document ).on( 'click', '.risksign-create:not(.button-disable)', window.digiriskdolibarr.risksign.createRiskSign );
	$( document ).on( 'click', '.risksign-save', window.digiriskdolibarr.risksign.saveRiskSign );
	$( document ).on( 'click', '.risksign-unlink-shared', window.digiriskdolibarr.risksign.unlinkSharedRiskSign );
};

/**
 * Lors du clic sur un riskSignCategory, remplaces le contenu du toggle et met l'image du risque sélectionné.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event [description]
 * @return {void}
 */
window.digiriskdolibarr.risksign.selectRiskSign = function( event ) {
	var element = $(this);
	element.closest('.content').removeClass('active');
	element.closest('.wpeo-dropdown').find('.dropdown-toggle span').hide();
	element.closest('.wpeo-dropdown').find('.dropdown-toggle img').show();
	element.closest('.wpeo-dropdown').find('.dropdown-toggle img').attr('src', element.find('img').attr('src'));
	element.closest('.wpeo-dropdown').find('.dropdown-toggle img').attr('aria-label', element.closest('.wpeo-tooltip-event').attr('aria-label'));

	element.closest('.fichecenter').find('.input-hidden-danger').val(element.data('id'));

	var elementParent = $(this).closest('.modal-container');

	// Rend le bouton "active".
	window.digiriskdolibarr.risksign.haveDataInInput(elementParent);
};

/**
 * Check value on riskSignCategory.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  elementParent --- Parent element
 * @return {void}
 */
window.digiriskdolibarr.risksign.haveDataInInput = function( elementParent ) {
	var element = elementParent.parent().parent();

	if (element.hasClass('risksign-add-modal')) {
		var risksign_category = element.find('input[name="risksign_category_id"]')
		if ( risksign_category.val() >= 0 ) {
			element.find('.button-disable').removeClass('button-disable');
		}
	}
};

/**
 * Action create risksign.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.risksign.createRiskSign = function ( event ) {
	let token = window.saturne.toolbox.getToken()

	let elementRiskSign = $(this).closest('.fichecenter').find('.risksign-content');
	var category        = elementRiskSign.find('.risksign-category input').val();
	var description     = elementRiskSign.find('.risksign-description textarea').val();

	window.saturne.loader.display($('.fichecenter.risksignlist'));

	$.ajax({
		url: document.URL + '&action=add&token='+token,
		type: "POST",
		data: JSON.stringify({
			riskSignCategory: category,
			riskSignDescription: description
		}),
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('.fichecenter.risksignlist').html($(resp).find('#searchFormListRiskSigns'))

			let actionContainerSuccess = $('.messageSuccessRiskSignCreate');

			$('.fichecenter.risksignlist').removeClass('wpeo-loader');

			actionContainerSuccess.html($(resp).find('.risksign-create-success-notice'))
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( resp ) {
			let actionContainerError = $('.messageErrorRiskCreate');
			actionContainerError.html($(resp).find('.risksign-create-error-notice'))
			actionContainerError.removeClass('hidden');
		}
	});

};

/**
 * Action save risksign.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.risksign.saveRiskSign = function ( event ) {
	let token = window.saturne.toolbox.getToken()

	let editedRiskSignId = $(this).attr('value');
	let elementRiskSign  = $(this).closest('.risksign-container').find('.risksign-content');
	let textToShow       = ''

	var category    = elementRiskSign.find('.risksign-category input').val();
	var description = elementRiskSign.find('.risksign-description textarea').val();

	let riskSignRef =  $('.risksign_row_'+editedRiskSignId).find('.risksign-container > div:nth-child(1)').text();

	window.saturne.loader.display(elementRiskSign);

	$.ajax({
		url: document.URL + '&action=saveRiskSign&token='+token,
		data: JSON.stringify({
			riskSignID: editedRiskSignId,
			riskSignCategory: category,
			riskSignDescription: description
		}),
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('.fichecenter.risksignlist').html($(resp).find('#searchFormListRiskSigns'))

			let actionContainerSuccess = $('.messageSuccessRiskSignEdit');

			elementRiskSign.removeClass('wpeo-loader');

			textToShow += actionContainerSuccess.find('.valueForEditRiskSign1').val()
			textToShow += riskSignRef
			textToShow += actionContainerSuccess.find('.valueForEditRiskSign2').val()

			actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( ) {
			let actionContainerError = $('.messageErrorRiskSignEdit');
			elementRiskSign.removeClass('wpeo-loader');

			textToShow += actionContainerError.find('.valueForEditRiskSign1').val()
			textToShow += riskSignRef
			textToShow += actionContainerError.find('.valueForEditRiskSign2').val()

			actionContainerError.find('.notice-subtitle .text').text(textToShow)
			actionContainerError.removeClass('hidden');
		}
	});

};

/**
 * Action unlink shared risk sign.
 *
 * @since   9.4.0
 * @version 9.4.0
 *
 * @return {void}
 */
window.digiriskdolibarr.risksign.unlinkSharedRiskSign = function ( event ) {
	let token = window.saturne.toolbox.getToken()

	let risksignId = $(this).attr('value');

	window.saturne.loader.display($(this));

	let risksignRef =  $('.risksign_row_'+risksignId).find('.risksign-container > div:nth-child(1)').text();
	let url = document.URL.split(/#/);

	$.ajax({
		url: url[0] + '&action=unlinkSharedRiskSign&token='+token,
		type: "POST",
		processData: false,
		data: JSON.stringify({
			risksignID: risksignId,
		}),
		contentType: false,
		success: function ( resp ) {
			$('.fichecenter.sharedrisksignlist .opacitymedium.colorblack.paddingleft').html($(resp).find('#searchFormSharedListRiskSigns .opacitymedium.colorblack.paddingleft'))
			let actionContainerSuccess = $('.messageSuccessRiskSignUnlinkShared');

			$('#risksign_row_' + risksignId).fadeOut(800);

			let textToShow = '';
			textToShow += actionContainerSuccess.find('.valueForUnlinkSharedRiskSign1').val()
			textToShow += risksignRef
			textToShow += actionContainerSuccess.find('.valueForUnlinkSharedRiskSign2').val()

			actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( resp ) {
			let actionContainerError = $('.messageErrorRiskSignUnlinkShared');

			let textToShow = '';
			textToShow += actionContainerError.find('.valueForUnlinkSharedRiskSign1').val()
			textToShow += risksignRef
			textToShow += actionContainerError.find('.valueForUnlinkSharedRiskSign2').val()

			actionContainerError.find('.notice-subtitle .text').text(textToShow)
			actionContainerError.removeClass('hidden');
		}
	});
};
