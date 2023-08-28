
/**
 * Initialise l'objet "risk" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.digiriskdolibarr.risk = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.risk.init = function() {
	window.digiriskdolibarr.risk.event();
};

/**
 * La méthode contenant tous les événements pour le risk.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.risk.event = function() {
	$( document ).on( 'click', '.category-danger .item, .wpeo-table .category-danger .item', window.digiriskdolibarr.risk.selectDanger );
	$( document ).on( 'click', '.risk-create:not(.button-disable)', window.digiriskdolibarr.risk.createRisk );
	$( document ).on( 'click', '.risk-save', window.digiriskdolibarr.risk.saveRisk );
	$( document ).on( 'click', '.risk-unlink-shared', window.digiriskdolibarr.risk.unlinkSharedRisk );
	$( document ).on( 'click', 'div[aria-describedby="dialog-confirm-actionButtonImportSharedRisks"] .ui-button.ui-corner-all.ui-widget', window.digiriskdolibarr.risk.sharedRiskBoxLoader );
};

/**
 * Lors du clic sur un riskCategory, remplaces le contenu du toggle et met l'image du risque sélectionné.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event [description]
 * @return {void}
 */
window.digiriskdolibarr.risk.selectDanger = function( event ) {
	var element = $(this);

	element.closest('.content').removeClass('active');
	element.closest('.wpeo-dropdown').find('.dropdown-toggle span').hide();
	element.closest('.wpeo-dropdown').find('.dropdown-toggle img').show();
	element.closest('.wpeo-dropdown').find('.dropdown-toggle img').attr('src', element.find('img').attr('src'));
	element.closest('.wpeo-dropdown').find('.dropdown-toggle img').attr('aria-label', element.closest('.wpeo-tooltip-event').attr('aria-label'));
	element.closest('.wpeo-dropdown').find('.input-hidden-danger').val(element.data('id'));

	var riskDescriptionPrefill = element.closest('.wpeo-dropdown').find('.input-risk-description-prefill').val();
	if (riskDescriptionPrefill == 1) {
		element.closest('.risk-content').find('.risk-description textarea').text(element.closest('.wpeo-tooltip-event').attr('aria-label'));
	}
	var elementParent = $(this).closest('.modal-container');

	// Rend le bouton "active".
	window.digiriskdolibarr.risk.haveDataInInput(elementParent);
};

/**
 * Check value on riskCategory and riskCotation.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  elementParent --- Parent element
 * @return {void}
 */
window.digiriskdolibarr.risk.haveDataInInput = function( elementParent ) {
	var element  = elementParent.parent().parent();
	var cotation = element.find('.risk-evaluation-seuil');

	if (element.hasClass('risk-add-modal')) {
		var category = element.find('input[name="risk_category_id"]')
		if ( category.val() >= 0  && cotation.val() >= 0  ) {
			element.find('.risk-create.button-disable').removeClass('button-disable');
		}
	} else if (element.hasClass('risk-evaluation-add-modal')) {
		if ( cotation.val() >= 0 ) {
			element.find('.risk-evaluation-create.button-disable').removeClass('button-disable');
		}
	}
};

/**
 * Sanitize request before send.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.risk.sanitizeBeforeRequest = function ( text ) {
	if (typeof text == 'string') {
		if (text.match(/"/)) {
			return text.split(/"/).join('')
		}
	}
	return text
}

/**
 * Action create risk.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.risk.createRisk = function ( event ) {
	let token = window.saturne.toolbox.getToken()

	let elementRisk       = $(this).closest('.fichecenter').find('.risk-content');
	let elementEvaluation = $(this).closest('.fichecenter').find('.risk-evaluation-container');
	let elementTask       = $(this).closest('.fichecenter').find('.riskassessment-task');

	let riskCommentText = elementRisk.find('.risk-description textarea').val()
	let evaluationText  = elementEvaluation.find('.risk-evaluation-comment textarea').val()

	let taskText = elementTask.find('input').val()

	riskCommentText = window.digiriskdolibarr.risk.sanitizeBeforeRequest(riskCommentText)
	evaluationText  = window.digiriskdolibarr.risk.sanitizeBeforeRequest(evaluationText)
	taskText        = window.digiriskdolibarr.risk.sanitizeBeforeRequest(taskText)

	//Risk
	var category    = elementRisk.find('.risk-category input').val();
	var description = riskCommentText;

	//Risk assessment
	var method   = elementEvaluation.find('.risk-evaluation-header .risk-evaluation-method').val();
	var cotation = elementEvaluation.find('.risk-evaluation-seuil').val();
	var photo    = elementEvaluation.find('.risk-evaluation-photo-single .filename').val();
	var comment  = evaluationText;
	var date     = elementEvaluation.find('#RiskAssessmentDate').val();
	var criteres = []

	Object.values($('.table-cell.active.cell-0')).forEach(function(v) {
		if ($(v).data( 'seuil' ) > -1) {
			criteres[ $(v).data( 'type' )] = $(v).data( 'seuil' )
		}
	})

	//Task
	var task      = taskText;
	let dateStart = elementTask.find('#RiskassessmentTaskDateStartModalRisk').val();
	let hourStart = elementTask.find('#RiskassessmentTaskDateStartModalRiskhour').val();
	let minStart  = elementTask.find('#RiskassessmentTaskDateStartModalRiskmin').val();
	let dateEnd   = elementTask.find('#RiskassessmentTaskDateEndModalRisk').val();
	let hourEnd   = elementTask.find('#RiskassessmentTaskDateEndModalRiskhour').val();
	let minEnd    = elementTask.find('#RiskassessmentTaskDateEndModalRiskmin').val();
	let budget    = elementTask.find('.riskassessment-task-budget').val()

	//Loader
	window.saturne.loader.display($('.fichecenter.risklist'));

	$.ajax({
		url: document.URL + '&action=add&token='+token,
		type: "POST",
		data: JSON.stringify({
			cotation: cotation,
			comment: comment,
			category: category,
			description: description,
			method: method,
			photo: photo,
			date: date,
			task: task,
			dateStart: dateStart,
			hourStart: hourStart,
			minStart: minStart,
			dateEnd: dateEnd,
			hourEnd: hourEnd,
			minEnd: minEnd,
			budget: budget,
			criteres: {
				gravite: criteres['gravite'] ? criteres['gravite'] : 0,
				occurrence: criteres['occurrence'] ? criteres['occurrence'] : 0,
				protection: criteres['protection'] ? criteres['protection'] : 0,
				formation: criteres['formation'] ? criteres['formation'] : 0,
				exposition: criteres['exposition'] ? criteres['exposition'] : 0
			}
		}),
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('.fichecenter.risklist').html($(resp).find('#searchFormListRisks'))

			let actionContainerSuccess = $('.messageSuccessRiskCreate')
			actionContainerSuccess.html($(resp).find('.risk-create-success-notice'))
			actionContainerSuccess.removeClass('hidden')
			$('.fichecenter.risklist').removeClass('wpeo-loader');
		},
		error: function ( resp ) {
			let actionContainerError = $('.messageErrorRiskCreate');

			actionContainerError.html($(resp).find('.risk-create-error-notice'));
			actionContainerError.removeClass('hidden');

			$('.fichecenter.risklist').removeClass('wpeo-loader');
		}
	});

};

/**
 * Action save risk.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.risk.saveRisk = function ( event ) {
	let token = window.saturne.toolbox.getToken()

	let editedRiskId = $(this).attr('value');
	let elementRisk  = $(this).closest('.risk-container').find('.risk-content');
	var params       = new window.URLSearchParams(window.location.search);
	var id           = params.get('id')

	let moveRiskDisabled = $(this).closest('.risk-container').find('.move-risk').hasClass('move-disabled')
	let riskCommentText  = elementRisk.find('.risk-description textarea').val()
	riskCommentText      = window.digiriskdolibarr.risk.sanitizeBeforeRequest(riskCommentText)

	var category = elementRisk.find('.risk-category input').val();

	if (riskCommentText) {
		var description = riskCommentText;
	} else {
		var description = '';
	}

	var newParent = $(this).closest('.risk-container').find('#socid option:selected').val();

	if (newParent == id || moveRiskDisabled) {
		window.saturne.loader.display($(this).closest('.risk-row-content-' + editedRiskId).find('.risk-description-'+editedRiskId));
		window.saturne.loader.display($(this).closest('.risk-row-content-' + editedRiskId).find('.risk-category'));
	} else {
		window.saturne.loader.display($(this).closest('.risk-row-content-' + editedRiskId))
	}
	let riskRef =  $('.risk_row_'+editedRiskId).find('.risk-container > div:nth-child(1)').text();


	$.ajax({
		url:  document.URL + '&action=saveRisk&token='+token,
		type: "POST",
		processData: false,
		data: JSON.stringify({
			riskID: editedRiskId,
			category: category,
			comment: description,
			newParent: newParent
		}),
		contentType: false,
		success: function ( resp ) {
			$('.wpeo-loader').removeClass('wpeo-loader');
			let actionContainerSuccess = $('.messageSuccessRiskEdit');
			if (newParent == id || moveRiskDisabled) {
				$('.modal-active').removeClass('modal-active')
				$('.risk-description-'+editedRiskId).html($(resp).find('.risk-description-'+editedRiskId))
				$('.risk-row-content-' + editedRiskId).find('.risk-category .cell-risk').html($(resp).find('.risk-row-content-' + editedRiskId).find('.risk-category .cell-risk').children())
				$('.risk-row-content-' + editedRiskId).find('.risk-category').fadeOut(800);
				$('.risk-row-content-' + editedRiskId).find('.risk-category').fadeIn(800);
				$('.risk-row-content-' + editedRiskId).find('.risk-description-'+editedRiskId).fadeOut(800);
				$('.risk-row-content-' + editedRiskId).find('.risk-description-'+editedRiskId).fadeIn(800);
			} else {
				$('.risk-row-content-'+editedRiskId).fadeOut(800, function () {
					$('.fichecenter .opacitymedium.colorblack.paddingleft').html($(resp).find('#searchFormListRisks .opacitymedium.colorblack.paddingleft'))
				});
			}

			let textToShow = '';
			textToShow += actionContainerSuccess.find('.valueForEditRisk1').val()
			textToShow += riskRef
			textToShow += actionContainerSuccess.find('.valueForEditRisk2').val()

			actionContainerSuccess.find('a').attr('href', '#risk_row_'+editedRiskId)
			actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( resp ) {
			let actionContainerError = $('.messageErrorRiskEdit');

			let textToShow = '';
			textToShow += actionContainerError.find('.valueForEditRisk1').val()
			textToShow += riskRef
			textToShow += actionContainerError.find('.valueForEditRisk2').val()

			actionContainerError.find('.notice-subtitle .text').text(textToShow)
			actionContainerError.removeClass('hidden');
		}
	});
};

/**
 * Action unlink shared risk.
 *
 * @since   9.2.0
 * @version 9.2.0
 *
 * @return {void}
 */
window.digiriskdolibarr.risk.unlinkSharedRisk = function ( event ) {
	let token = window.saturne.toolbox.getToken()

	let riskId = $(this).attr('value');

	window.saturne.loader.display($(this));

	let riskRef =  $('.risk_row_'+riskId).find('.risk-container > div:nth-child(1)').text();
	let url = document.URL.split(/#/);

	$.ajax({
		url: url[0] + '&action=unlinkSharedRisk&token='+token,
		type: "POST",
		processData: false,
		data: JSON.stringify({
			riskID: riskId,
		}),
		contentType: false,
		success: function ( resp ) {
			//refresh shared risk list form
			$('.confirmquestions').html($(resp).find('.confirmquestions').children())
			//refresh shared risk counter
			$('.fichecenter.sharedrisklist .opacitymedium.colorblack.paddingleft').html($(resp).find('#searchFormSharedListRisks .opacitymedium.colorblack.paddingleft'))
			let actionContainerSuccess = $('.messageSuccessRiskUnlinkShared');

			$('#risk_row_' + riskId).fadeOut(800);

			let textToShow = '';
			textToShow += actionContainerSuccess.find('.valueForUnlinkSharedRisk1').val()
			textToShow += riskRef
			textToShow += actionContainerSuccess.find('.valueForUnlinkSharedRisk2').val()

			actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( resp ) {
			let actionContainerError = $('.messageErrorRiskUnlinkShared');

			let textToShow = '';
			textToShow += actionContainerError.find('.valueForUnlinkSharedRisk1').val()
			textToShow += riskRef
			textToShow += actionContainerError.find('.valueForUnlinkSharedRisk2').val()

			actionContainerError.find('.notice-subtitle .text').text(textToShow)
			actionContainerError.removeClass('hidden');
		}
	});
};

/**
 * Action loader shared risk box.
 *
 * @since   9.2.0
 * @version 9.2.0
 *
 * @return {void}
 */
window.digiriskdolibarr.risk.sharedRiskBoxLoader = function ( event ) {
	if ($(this).text() == 'Oui') {
		window.saturne.loader.display($('#searchFormSharedListRisks'))
	}
};
