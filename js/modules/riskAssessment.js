
/**
 * Initialise l'objet "evaluation" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.digiriskdolibarr.evaluation = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.evaluation.init = function() {
	window.digiriskdolibarr.evaluation.event();
};

/**
 * La méthode contenant tous les événements pour le evaluation.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.evaluation.event = function() {
	$( document ).on( 'click', '.select-evaluation-method', window.digiriskdolibarr.evaluation.selectEvaluationMethod);
	$( document ).on( 'click', '.cotation-container .risk-evaluation-cotation.cotation', window.digiriskdolibarr.evaluation.selectSeuil );
	$( document ).on( 'click', '.risk-evaluation-create', window.digiriskdolibarr.evaluation.createEvaluation);
	$( document ).on( 'click', '.risk-evaluation-save', window.digiriskdolibarr.evaluation.saveEvaluation);
	$( document ).on( 'click', '.risk-evaluation-delete', window.digiriskdolibarr.evaluation.deleteEvaluation);
}

/**
 * Select Evaluation Method.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.evaluation.selectEvaluationMethod = function ( event ) {
	var elementParent  = $(this).closest('.modal-container');
	var multiple_method = elementParent.find('.risk-evaluation-multiple-method').val();

	if (multiple_method > 0) {
		elementParent.find('.select-evaluation-method.selected').removeClass('selected');

		$(this).addClass('selected');
		$(this).removeClass('button-grey');
		$(this).addClass('button-blue');

		elementParent.find('.select-evaluation-method:not(.selected)').removeClass('button-blue');
		elementParent.find('.select-evaluation-method:not(.selected)').addClass('button-grey');

		if ($(this).hasClass('evaluation-standard')) {
			elementParent.find('.cotation-advanced').attr('style', 'display:none');
			elementParent.find('.cotation-standard').attr('style', 'display:block');
			elementParent.find('.risk-evaluation-calculated-cotation').attr('style', 'display:none')
			elementParent.find('.risk-evaluation-method').val('standard');
			elementParent.find(this).closest('.risk-evaluation-container').removeClass('advanced');
			elementParent.find(this).closest('.risk-evaluation-container').addClass('standard');
		} else {
			elementParent.find('.cotation-standard').attr('style', 'display:none');
			elementParent.find('.cotation-advanced').attr('style', 'display:block');
			elementParent.find('.risk-evaluation-calculated-cotation').attr('style', 'display:block');
			elementParent.find('.risk-evaluation-method').val('advanced');
			elementParent.find(this).closest('.risk-evaluation-container').addClass('advanced');
			elementParent.find(this).closest('.risk-evaluation-container').removeClass('standard');
		}
	}
};

/**
 * Clique sur une des cotations simples.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {ClickEvent} event L'état du clic.
 * @return {void}
 */
window.digiriskdolibarr.evaluation.selectSeuil = function( event ) {
	var element       = $(this);
	var elementParent = $(this).closest('.modal-container')
	var seuil         = element.data( 'seuil' );
	var variableID    = element.data( 'variable-id' );

	element.closest('.cotation-container').find('.risk-evaluation-seuil').val($( this ).text());
	element.closest('.cotation-container').find('.selected-cotation').removeClass('selected-cotation')
	element.addClass('selected-cotation')

	if ( variableID && seuil ) {
		window.digiriskdolibarr.risk.haveDataInInput( elementParent )
	}
};

/**
 * Get default cotation.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {int} risk assessment cotation value.
 * @return {int}
 */
window.digiriskdolibarr.evaluation.getDynamicScale = function (cotation) {
	switch (true) {
		case (cotation === 0):
		case (cotation < 48):
			return 1;
		case (cotation < 51):
			return 2;
		case (cotation < 79):
			return 3;
		case (cotation < 101):
			return 4;
	}
};

/**
 * Action create evaluation.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.evaluation.createEvaluation = function ( event ) {
	let token = window.saturne.toolbox.getToken()

	var riskToAssign = $(this).attr('value');
	let element      = $(this).closest('.risk-evaluation-add-modal');
	let single       = element.find('.risk-evaluation-container');

	let evaluationText = single.find('.risk-evaluation-comment textarea').val()
	evaluationText     = window.digiriskdolibarr.risk.sanitizeBeforeRequest(evaluationText)

	var method   = single.find('.risk-evaluation-method').val();
	var cotation = single.find('.risk-evaluation-seuil').val();
	var comment  = evaluationText
	var date     = single.find('#RiskAssessmentDateCreate0').val();
	var photo    = single.find('.risk-evaluation-photo-single .filename').val();

	let criteres = [];
	Object.values($('.table-cell.active.cell-0')).forEach(function(v) {
		if ($(v).data( 'seuil' ) > -1) {
			criteres[ $(v).data( 'type' )] = $(v).data( 'seuil' )
		}
	})

	window.saturne.loader.display($(this));
	window.saturne.loader.display($('.risk-evaluation-list-container-' + riskToAssign));

	$.ajax({
		url: document.URL + '&action=addEvaluation&token='+token,
		type: "POST",
		data: JSON.stringify({
			cotation: cotation,
			comment: comment,
			method: method,
			photo: photo,
			date: date,
			riskId: riskToAssign,
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
		success: function( resp ) {

			if ($(resp).find('.risk-evaluation-list-container-' + riskToAssign).length > 0) {
				$('.risk-evaluation-list-container-' + riskToAssign).replaceWith($(resp).find('.risk-evaluation-list-container-' + riskToAssign))
			} else {
				$('.div-table-responsive').replaceWith($(resp).find('.div-table-responsive'));
			}

			let actionContainerSuccess = $('.messageSuccessEvaluationCreate');

			$('.risk-evaluation-list-container-' + riskToAssign).fadeOut(800);
			$('.risk-evaluation-list-container-' + riskToAssign).fadeIn(800);
			actionContainerSuccess.empty()
			actionContainerSuccess.html($(resp).find('.riskassessment-create-success-notice'))
			actionContainerSuccess.find('a').attr('href', '#risk_row_'+riskToAssign)

			$('.wpeo-loader').removeClass('wpeo-loader')
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( resp ) {
			let actionContainerError = $('.messageErrorEvaluationCreate');

			$(this).closest('.risk-row-content-' + riskToAssign).removeClass('wpeo-loader');

			actionContainerError.empty()
			actionContainerError.html($(resp).find('.riskassessment-create-error-notice'))

			actionContainerError.removeClass('hidden');
		}
	});
};


/**
 * Action delete evaluation.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {boolean}
 */
window.digiriskdolibarr.evaluation.deleteEvaluation = function ( event ) {
	let token = window.saturne.toolbox.getToken()

	let element                = $(this).closest('.risk-evaluation-in-modal');
	let textToShowBeforeDelete = element.find('.labelForDelete').val();
	let actionContainerSuccess = $('.messageSuccessEvaluationDelete');
	let actionContainerError   = $('.messageErrorEvaluationDelete');
	let evaluationID           = element.attr('value');

	var r = confirm(textToShowBeforeDelete);
	if (r == true) {

		let elementParent = $(this).closest('.risk-evaluations-list-content');
		let riskId        = elementParent.attr('value');
		let evaluationRef =  $('.risk-evaluation-ref-'+evaluationID).attr('value');

		window.saturne.loader.display($('.risk-evaluation-ref-'+evaluationID));

		$.ajax({
			url:document.URL + '&action=deleteEvaluation&deletedEvaluationId=' + evaluationID + '&token=' + token,
			type: "POST",
			processData: false,
			contentType: false,
			success: function ( resp ) {
				//remove risk assessment from list in modal
				$('.risk-evaluation-container-' + evaluationID).fadeOut(400)

				//refresh risk assessment list
				$('.risk-evaluation-list-content-' + riskId).replaceWith($(resp).find('.risk-evaluation-list-content-' + riskId))

				//refresh risk assessment add modal to actualize last risk assessment
				$('#risk_evaluation_add' + riskId).replaceWith($(resp).find('#risk_evaluation_add' + riskId))

				//update risk assessment counter
				let evaluationCounterText = $('#risk_row_'+riskId).find('.table-cell-header-label').text()
				let evaluationCounter     = evaluationCounterText.split(/\(/)[1].split(/\)/)[0]
				$('#risk_row_' + riskId).find('.table-cell-header-label').html('<strong>' + evaluationCounterText.split(/\(/)[0] + '(' + (+evaluationCounter - 1) + ')' + '</strong>')

				if (evaluationCounter - 1 < 1) {
					$('.fichecenter.risklist').html($(resp).find('#searchFormListRisks'))
				}

				elementParent.removeClass('wpeo-loader');

				let textToShow = '';
				textToShow += actionContainerSuccess.find('.valueForDeleteEvaluation1').val()
				textToShow += evaluationRef
				textToShow += actionContainerSuccess.find('.valueForDeleteEvaluation2').val()

				actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
				actionContainerSuccess.removeClass('hidden');
			},
			error: function ( resp ) {

				let textToShow = '';
				textToShow += actionContainerError.find('.valueForDeleteEvaluation1').val()
				textToShow += evaluationRef
				textToShow += actionContainerError.find('.valueForDeleteEvaluation2').val()

				actionContainerError.find('.notice-subtitle .text').text(textToShow);
				actionContainerError.removeClass('hidden');
			}
		});

	} else {
		return false;
	}
}

/**
 * Action save evaluation.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.evaluation.saveEvaluation = function ( event ) {
	let token = window.saturne.toolbox.getToken()

	let element        = $(this).closest('.risk-evaluation-edit-modal');
	let evaluationID   = element.attr('value');
	let evaluationText = element.find('.risk-evaluation-comment textarea').val()

	let elementParent      = $(this).closest('.risk-row').find('.risk-evaluations-list-content');
	let riskId             = elementParent.attr('value');
	let evaluationRef      =  $('.risk-evaluation-ref-'+evaluationID).attr('value');
	let listModalContainer = $('.risk-evaluation-list-modal-'+riskId)
	let listModal          = $('#risk_evaluation_list'+riskId)
	let fromList           = listModal.hasClass('modal-active');

	evaluationText = window.digiriskdolibarr.risk.sanitizeBeforeRequest(evaluationText)

	var method   = element.find('.risk-evaluation-method').val();
	var cotation = element.find('.risk-evaluation-seuil').val();
	var comment  = evaluationText;
	var date     = element.find('#RiskAssessmentDateEdit' + evaluationID).val();
	var photo    = element.find('.risk-evaluation-photo .filename').val();

	let criteres = [];
	Object.values($('.table-cell.active.cell-'+evaluationID)).forEach(function(v) {
		if ($(v).data( 'seuil' ) > -1) {
			criteres[ $(v).data( 'type' )] = $(v).data( 'seuil' )
		}
	})

	window.saturne.loader.display($(this));

	$.ajax({
		url: document.URL + '&action=saveEvaluation&token='+token,
		type: "POST",
		processData: false,
		data: JSON.stringify({
			cotation: cotation,
			comment: comment,
			method: method,
			photo: photo,
			date: date,
			evaluationID: evaluationID,
			criteres: {
				gravite: criteres['gravite'] ? criteres['gravite'] : 0,
				occurrence: criteres['occurrence'] ? criteres['occurrence'] : 0,
				protection: criteres['protection'] ? criteres['protection'] : 0,
				formation: criteres['formation'] ? criteres['formation'] : 0,
				exposition: criteres['exposition'] ? criteres['exposition'] : 0
			}
		}),
		contentType: false,
		success: function ( resp ) {
			$('#risk_evaluation_edit'+evaluationID).removeClass('modal-active')

			if ($(resp).find('.risk-evaluation-container.risk-evaluation-container-'+evaluationID+':not(.last-risk-assessment)').length > 0) {
				if (fromList) {
					$('.risk-evaluation-ref-'+evaluationID+':not(.last-risk-assessment)').fadeOut(800);
					$('.risk-evaluation-ref-'+evaluationID+':not(.last-risk-assessment)').fadeIn(800);
				} else {
					$('.risk-evaluation-container-'+evaluationID+':not(.last-risk-assessment)').fadeOut(800);
					$('.risk-evaluation-container-'+evaluationID+':not(.last-risk-assessment)').fadeIn(800);
				}
				//refresh risk assessment single in modal list
				listModalContainer.find('.risk-evaluation-ref-'+evaluationID).replaceWith($(resp).find('.risk-evaluation-ref-'+evaluationID))

				//refresh risk assessment single in list
				$('.risk-evaluation-container.risk-evaluation-container-'+evaluationID+':not(.last-risk-assessment)').replaceWith($(resp).find('.risk-evaluation-container.risk-evaluation-container-'+evaluationID+':not(.last-risk-assessment)'))

				//refresh risk assessment add modal to actualize last risk assessment
				$('#risk_evaluation_add' + riskId).html($(resp).find('#risk_evaluation_add' + riskId).children())
			} else {
				$('.div-table-responsive').html($(resp).find('.div-table-responsive').children());

				if (fromList) {
					$('.risk-evaluation-ref-'+evaluationID+':not(.last-risk-assessment)').fadeOut(800);
					$('.risk-evaluation-ref-'+evaluationID+':not(.last-risk-assessment)').fadeIn(800);
				} else {
					$('.risk-evaluation-container-'+evaluationID+':not(.last-risk-assessment)').fadeOut(800);
					$('.risk-evaluation-container-'+evaluationID+':not(.last-risk-assessment)').fadeIn(800);
				}
			}

			$('.wpeo-loader').removeClass('wpeo-loader')
			let actionContainerSuccess = $('.messageSuccessEvaluationEdit');

			element.find('#risk_evaluation_edit'+evaluationID).removeClass('modal-active');

			let textToShow = '';
			textToShow += actionContainerSuccess.find('.valueForEditEvaluation1').val()
			textToShow += evaluationRef
			textToShow += actionContainerSuccess.find('.valueForEditEvaluation2').val()
			actionContainerSuccess.find('a').attr('href', '#risk_row_'+riskId)

			actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( ) {
			let actionContainerError = $('.messageErrorEvaluationEdit');

			let textToShow = '';
			textToShow += actionContainerError.find('.valueForEditEvaluation1').val()
			textToShow += evaluationRef
			textToShow += actionContainerError.find('.valueForEditEvaluation2').val()

			actionContainerError.find('.notice-subtitle .text').text(textToShow);
			actionContainerError.removeClass('hidden');
		}
	});

};
