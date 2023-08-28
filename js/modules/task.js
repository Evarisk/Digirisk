
/**
 * Initialise l'objet "riskassessmenttask" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.digiriskdolibarr.riskassessmenttask = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.riskassessmenttask.init = function() {
	window.digiriskdolibarr.riskassessmenttask.event();
};

/**
 * La méthode contenant tous les événements pour le riskassessment-task.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.riskassessmenttask.event = function() {
	$( document ).on( 'input', '.riskassessment-task-label', window.digiriskdolibarr.riskassessmenttask.fillRiskAssessmentTaskLabel);
	$( document ).on( 'click', '.riskassessment-task-create', window.digiriskdolibarr.riskassessmenttask.createRiskAssessmentTask);
	$( document ).on( 'click', '.riskassessment-task-save', window.digiriskdolibarr.riskassessmenttask.saveRiskAssessmentTask);
	$( document ).on( 'click', '.riskassessment-task-delete', window.digiriskdolibarr.riskassessmenttask.deleteRiskAssessmentTask );
	$( document ).on( 'click', '.riskassessment-task-timespent-create', window.digiriskdolibarr.riskassessmenttask.createRiskAssessmentTaskTimeSpent);
	$( document ).on( 'click', '.riskassessment-task-timespent-save', window.digiriskdolibarr.riskassessmenttask.saveRiskAssessmentTaskTimeSpent);
	$( document ).on( 'click', '.riskassessment-task-timespent-delete', window.digiriskdolibarr.riskassessmenttask.deleteRiskAssessmentTaskTimeSpent );
	$( document ).on( 'click', '.riskassessment-task-progress-checkbox:not(.riskassessment-task-progress-checkbox-readonly)', window.digiriskdolibarr.riskassessmenttask.checkTaskProgress );
	$( document ).on( 'change', '#RiskassessmentTaskTimespentDatehour', window.digiriskdolibarr.riskassessmenttask.selectRiskassessmentTaskTimespentDateHour );
	$( document ).on( 'change', '#RiskassessmentTaskTimespentDatemin', window.digiriskdolibarr.riskassessmenttask.selectRiskassessmentTaskTimespentDateMin );
	$( document ).on( 'keyup', '.riskassessment-task-label', window.digiriskdolibarr.riskassessmenttask.checkRiskassessmentTaskLabelLength );
	$( document ).on( 'click', '.listingHeaderTaskTooltip', window.digiriskdolibarr.riskassessmenttask.redirectOnSharedTaskConfig );
};

/**
 * Fill riskassessmenttask label
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {MouseEvent} event [description]
 * @return {void}
 */
window.digiriskdolibarr.riskassessmenttask.fillRiskAssessmentTaskLabel = function( event ) {
	var elementParent = $(this).closest('.modal-container');

	window.digiriskdolibarr.riskassessmenttask.haveDataInInput(elementParent);
};

/**
 * Check value on riskAssessmentTask.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  elementParent --- Parent element
 * @return {void}
 */
window.digiriskdolibarr.riskassessmenttask.haveDataInInput = function( elementParent ) {
	var element = elementParent.parent().parent();

	if (element.hasClass('riskassessment-task-add-modal')) {
		var riskassessmenttasklabel = element.find('input[name="label"]').val();
		if ( riskassessmenttasklabel.length ) {
			element.find('.button-disable').removeClass('button-disable');
		}
	}
};

/**
 * Action create task.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.riskassessmenttask.createRiskAssessmentTask = function ( event ) {
	let token = window.saturne.toolbox.getToken()

	var riskToAssign = $(this).attr('value');
	let element      = $(this).closest('.riskassessment-task-add-modal');
	let single       = element.find('.riskassessment-task-container');

	let taskText = single.find('.riskassessment-task-label').val()
	taskText     = window.digiriskdolibarr.risk.sanitizeBeforeRequest(taskText)

	let dateStart = single.find('#RiskassessmentTaskDateStart' + riskToAssign).val();
	let hourStart = single.find('#RiskassessmentTaskDateStart' + riskToAssign + 'hour').val();
	let minStart  = single.find('#RiskassessmentTaskDateStart' + riskToAssign + 'min').val();
	let dateEnd   = single.find('#RiskassessmentTaskDateEnd' + riskToAssign).val();
	let hourEnd   = single.find('#RiskassessmentTaskDateEnd' + riskToAssign + 'hour').val();
	let minEnd    = single.find('#RiskassessmentTaskDateEnd' + riskToAssign + 'min').val();
	let budget    = single.find('.riskassessment-task-budget').val()

	window.saturne.loader.display($(this));
	window.saturne.loader.display($('.riskassessment-tasks' + riskToAssign));

	$.ajax({
		url: document.URL + '&action=addRiskAssessmentTask&token='+token,
		type: "POST",
		data: JSON.stringify({
			tasktitle: taskText,
			dateStart: dateStart,
			hourStart: hourStart,
			minStart: minStart,
			dateEnd: dateEnd,
			hourEnd: hourEnd,
			minEnd: minEnd,
			budget: budget,
			riskToAssign: riskToAssign,
		}),
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('.tasks-list-container-'+riskToAssign).replaceWith($(resp).find('.tasks-list-container-'+riskToAssign))
			let actionContainerSuccess = $('.messageSuccessTaskCreate');

			$('.riskassessment-tasks' + riskToAssign).fadeOut(800);
			$('.riskassessment-tasks' + riskToAssign).fadeIn(800);

			actionContainerSuccess.find('a').attr('href', '#risk_row_'+riskToAssign)

			actionContainerSuccess.html($(resp).find('.task-create-success-notice'))
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( resp ) {
			$('.wpeo-loader').removeClass('wpeo-loader');
			window.scrollTo(0, 0);
			let response = JSON.parse(resp.responseText)

			let actionContainerError = $('.messageErrorTaskCreate');
			$('#risk_assessment_task_add'+riskToAssign).removeClass('modal-active')

			let textToShow = '';

			textToShow += actionContainerError.find('.valueForCreateTask1').val()
			textToShow += actionContainerError.find('.valueForCreateTask2').val()
			textToShow += ' : '
			textToShow += response.message

			actionContainerError.find('.notice-subtitle .text').text(textToShow);
			actionContainerError.removeClass('hidden');
		}
	});
};

/**
 * Action delete riskassessmenttask.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.riskassessmenttask.deleteRiskAssessmentTask = function ( event ) {
	let token = window.saturne.toolbox.getToken()

	let element                     = $(this).closest('.riskassessment-tasks');
	let riskId                      = $(this).closest('.riskassessment-tasks').attr('value');
	let deletedRiskAssessmentTaskId = $(this).attr('value');
	let textToShow                  = element.find('.labelForDelete').val();
	let actionContainerSuccess      = $('.messageSuccessTaskDelete');
	let actionContainerError        = $('.messageErrorTaskDelete');

	var r = confirm(textToShow);
	if (r == true) {

		let riskAssessmentTaskRef =  $('.riskassessment-task-container-'+deletedRiskAssessmentTaskId).attr('value');

		window.saturne.loader.display($('.riskassessment-task-container-'+deletedRiskAssessmentTaskId));

		$.ajax({
			url: document.URL + '&action=deleteRiskAssessmentTask&deletedRiskAssessmentTaskId=' + deletedRiskAssessmentTaskId + '&token=' + token,
			type: "POST",
			processData: false,
			contentType: false,
			success: function ( resp ) {
				$('.riskassessment-task-container-'+deletedRiskAssessmentTaskId).closest('.riskassessment-task-listing-wrapper').html($(resp).find('.tasks-list-container-'+riskId).find('.riskassessment-task-listing-wrapper'))
				$('.riskassessment-tasks' + riskId).fadeOut(800);
				$('.riskassessment-tasks' + riskId).fadeIn(800);
				let textToShow = '';
				textToShow += actionContainerSuccess.find('.valueForDeleteTask1').val()
				textToShow += riskAssessmentTaskRef
				textToShow += actionContainerSuccess.find('.valueForDeleteTask2').val()

				actionContainerSuccess.find('a').attr('href', '#risk_row_'+riskId)

				actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
				actionContainerSuccess.removeClass('hidden');
			},
			error: function ( resp ) {
				$('.wpeo-loader').removeClass('wpeo-loader');
				window.scrollTo(0, 0);
				let response = JSON.parse(resp.responseText)

				let textToShow = '';
				textToShow += actionContainerError.find('.valueForDeleteTask1').val()
				textToShow += riskAssessmentTaskRef
				textToShow += actionContainerError.find('.valueForDeleteTask2').val()
				textToShow += ' : '
				textToShow += response.message

				actionContainerError.find('.notice-subtitle .text').text(textToShow);
				actionContainerError.removeClass('hidden');
			}
		});

	} else {
		return false;
	}
};

/**
 * Action save riskassessmenttask.
 *
 * @since   1.0.0
 * @version 9.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.riskassessmenttask.saveRiskAssessmentTask = function ( event ) {
	let token = window.saturne.toolbox.getToken()

	let editedRiskAssessmentTaskId = $(this).attr('value');
	let elementRiskAssessmentTask  = $(this).closest('.modal-container');
	let riskId                     = $(this).closest('.riskassessment-tasks').attr('value')
	let textToShow                 = '';

	let taskText = elementRiskAssessmentTask.find('.riskassessment-task-label' + editedRiskAssessmentTaskId).val()
	taskText     = window.digiriskdolibarr.risk.sanitizeBeforeRequest(taskText)

	let taskRef =  $('.riskassessment-task-single-'+editedRiskAssessmentTaskId+' .riskassessment-task-reference').attr('value');

	let taskProgress = 0;
	if (elementRiskAssessmentTask.find('.riskassessment-task-progress-checkbox' + editedRiskAssessmentTaskId).is(':checked')) {
		taskProgress = 1;
	}

	let dateStart = elementRiskAssessmentTask.find('#RiskassessmentTaskDateStart' + editedRiskAssessmentTaskId).val();
	let hourStart = elementRiskAssessmentTask.find('#RiskassessmentTaskDateStart' + editedRiskAssessmentTaskId + 'hour').val();
	let minStart  = elementRiskAssessmentTask.find('#RiskassessmentTaskDateStart' + editedRiskAssessmentTaskId + 'min').val();
	let dateEnd   = elementRiskAssessmentTask.find('#RiskassessmentTaskDateEnd' + editedRiskAssessmentTaskId).val();
	let hourEnd   = elementRiskAssessmentTask.find('#RiskassessmentTaskDateEnd' + editedRiskAssessmentTaskId + 'hour').val();
	let minEnd    = elementRiskAssessmentTask.find('#RiskassessmentTaskDateEnd' + editedRiskAssessmentTaskId + 'min').val();
	let budget    = elementRiskAssessmentTask.find('.riskassessment-task-budget'  + editedRiskAssessmentTaskId).val()

	window.saturne.loader.display($(this));
	window.saturne.loader.display($('.riskassessment-task-single-'+ editedRiskAssessmentTaskId));

	$.ajax({
		url: document.URL + '&action=saveRiskAssessmentTask&token='+token,
		data: JSON.stringify({
			riskAssessmentTaskID: editedRiskAssessmentTaskId,
			tasktitle: taskText,
			dateStart: dateStart,
			hourStart: hourStart,
			minStart: minStart,
			dateEnd: dateEnd,
			hourEnd: hourEnd,
			minEnd: minEnd,
			budget: budget,
			taskProgress: taskProgress,
		}),
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('#risk_assessment_task_edit'+editedRiskAssessmentTaskId).removeClass('modal-active')
			$('.riskassessment-task-container-'+editedRiskAssessmentTaskId).replaceWith($(resp).find('.riskassessment-task-container-'+editedRiskAssessmentTaskId).first())
			let actionContainerSuccess = $('.messageSuccessTaskEdit');
			$('.riskassessment-tasks' + riskId).fadeOut(800);
			$('.riskassessment-tasks' + riskId).fadeIn(800);
			textToShow += actionContainerSuccess.find('.valueForEditTask1').val()
			textToShow += taskRef
			textToShow += actionContainerSuccess.find('.valueForEditTask2').val()

			$('.wpeo-loader').removeClass('wpeo-loader')
			$('.loader-spin').remove()
			actionContainerSuccess.find('a').attr('href', '#risk_row_'+riskId)

			actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( resp ) {
			$('.wpeo-loader').removeClass('wpeo-loader');
			window.scrollTo(0, 0);
			let response = JSON.parse(resp.responseText)

			let actionContainerError = $('.messageErrorTaskEdit');
			$('#risk_assessment_task_edit'+editedRiskAssessmentTaskId).removeClass('modal-active')
			$('.wpeo-loader').removeClass('wpeo-loader')

			textToShow += actionContainerError.find('.valueForEditTask1').val()
			textToShow += taskRef
			textToShow += actionContainerError.find('.valueForEditTask2').val()
			textToShow += ' : '
			textToShow += response.message

			actionContainerError.find('.notice-subtitle .text').text(textToShow);
			actionContainerError.removeClass('hidden');
		}
	});
};

/**
 * Action create task timespent.
 *
 * @since   9.1.0
 * @version 9.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.riskassessmenttask.createRiskAssessmentTaskTimeSpent = function ( event ) {
	let token = window.saturne.toolbox.getToken()

	let taskID     = $(this).attr('value');
	let element    = $(this).closest('.riskassessment-task-edit-modal');
	let single     = element.find('.riskassessment-task-timespent-container');
	let riskId     = element.find('riskassessment-task-single').attr('value');
	let textToShow = '';
	let taskRef    = element.find('.riskassessment-task-reference').attr('value');
	let timespent  = $('.id-container').find('.riskassessment-total-task-timespent-'+taskID)

	let date     = single.find('#RiskassessmentTaskTimespentDate' + taskID).val();
	let hour     = single.find('#RiskassessmentTaskTimespentDate' + taskID + 'hour').val();
	let min      = single.find('#RiskassessmentTaskTimespentDate' + taskID + 'min').val();
	let comment  = single.find('.riskassessment-task-timespent-comment').val()
	comment      = window.digiriskdolibarr.risk.sanitizeBeforeRequest(comment)
	let duration = single.find('.riskassessment-task-timespent-duration').val()

	window.saturne.loader.display($(this));
	window.saturne.loader.display($('.riskassessment-task-single-'+ taskID));

	$.ajax({
		url: document.URL + '&action=addRiskAssessmentTaskTimeSpent&token='+token,
		type: "POST",
		data: JSON.stringify({
			taskID: taskID,
			date: date,
			hour: hour,
			min: min,
			comment: comment,
			duration: duration,
		}),
		processData: false,
		contentType: false,
		success: function ( resp ) {
			//element.html($(resp).find(single))
			let actionContainerSuccess = $('.messageSuccessTaskTimeSpentCreate'+ taskID);

			$('.riskassessment-tasks' + riskId).fadeOut(800);
			$('.riskassessment-tasks' + riskId).fadeIn(800);

			textToShow += actionContainerSuccess.find('.valueForCreateTaskTimeSpent1').val()
			textToShow += taskRef
			textToShow += actionContainerSuccess.find('.valueForCreateTaskTimeSpent2').val()

			$('.riskassessment-task-timespent-container').find('.riskassessment-task-timespent-list-'+taskID).html($(resp).find('.riskassessment-task-timespent-container').find('.riskassessment-task-timespent-list-'+taskID))
			$('.riskassessment-task-container-'+taskID).closest('.riskassessment-tasks').html($(resp).find('.riskassessment-task-container-'+taskID).closest('.riskassessment-tasks'))
			$('.loader-spin').remove();
			$('.wpeo-loader').removeClass('wpeo-loader')

			actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
			actionContainerSuccess.removeClass('hidden');
			timespent.html($(resp).find('.modal-content').find('.riskassessment-total-task-timespent-'+taskID).first())
		},
		error: function ( resp ) {
			$(this).closest('.risk-row-content-' + riskId).removeClass('wpeo-loader');
			let actionContainerError = $('.messageErrorTaskTimeSpentCreate'+ taskID);
			actionContainerError.html($(resp).find('.task-timespent-create-error-notice'))
			actionContainerError.removeClass('hidden');
		},
		complete: function () {
			$('#risk_assessment_task_edit'+taskID+'.wpeo-modal').addClass('modal-active')
		}
	});
};

/**
 * Action delete riskassessmenttasktimespent.
 *
 * @since   9.1.0
 * @version 9.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.riskassessmenttask.deleteRiskAssessmentTaskTimeSpent = function ( event ) {
	let token = window.saturne.toolbox.getToken()

	let taskID                               = $(this).closest('.riskassessment-task-timespent-list').attr('value');
	let timespentID                          = $(this).attr('value');
	let deletedRiskAssessmentTaskTimeSpentId = $(this).attr('value');

	let element     = $(this).closest('.riskassessment-task-timespent-'+timespentID);
	let textToShow  = element.find('.labelForDelete').val();
	let timespent   = $('.id-container').first().find('.riskassessment-total-task-timespent-'+taskID)

	var r = confirm(textToShow);
	if (r == true) {

		let riskAssessmentTaskRef =  $('.riskassessment-task-container-'+taskID).attr('value');

		window.saturne.loader.display($(this));

		$.ajax({
			url: document.URL + '&action=deleteRiskAssessmentTaskTimeSpent&deletedRiskAssessmentTaskTimeSpentId=' + deletedRiskAssessmentTaskTimeSpentId + '&token=' + token,
			type: "POST",
			processData: false,
			contentType: false,
			success: function ( resp ) {
				//$('.fichecenter.risklist').html($(resp).find('#searchFormListRisks'))
				let actionContainerSuccess = $('.messageSuccessTaskTimeSpentDelete'+ taskID);
				$('.riskassessment-task-timespent-' + timespentID).fadeOut(800);
				//$('.riskassessment-tasks' + riskId).fadeIn(800);
				let textToShow = '';
				textToShow += actionContainerSuccess.find('.valueForDeleteTaskTimeSpent1').val()
				textToShow += riskAssessmentTaskRef
				textToShow += actionContainerSuccess.find('.valueForDeleteTaskTimeSpent2').val()

				actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
				actionContainerSuccess.removeClass('hidden');
				timespent.html($(resp).find('.modal-content').find('.riskassessment-total-task-timespent-'+taskID).first())
			},
			error: function ( resp ) {
				let actionContainerError = $('.messageErrorTaskDeleteTimeSpent'+ taskID);

				let textToShow = '';
				textToShow += actionContainerError.find('.valueForDeleteTaskTimeSpent1').val()
				textToShow += riskAssessmentTaskRef
				textToShow += actionContainerError.find('.valueForDeleteTaskTimeSpent2').val()

				actionContainerError.find('.notice-subtitle .text').text(textToShow);
				actionContainerError.removeClass('hidden');
			}
		});

	} else {
		return false;
	}
};

/**
 * Action save riskassessmenttasktimespent.
 *
 * @since   9.1.0
 * @version 9.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.riskassessmenttask.saveRiskAssessmentTaskTimeSpent = function ( event ) {
	let token = window.saturne.toolbox.getToken()

	let currentElement                = $(this);
	let riskAssessmentTaskTimeSpentID = $(this).attr('value');

	let element    = $(this).closest('.riskassessment-task-timespent-edit-modal');
	let single     = element.find('.riskassessment-task-timespent-container');
	let taskID     = single.attr('value');
	let textToShow = '';
	let taskRef    = $('.riskassessment-task-container-'+taskID).attr('value');
	let timespent  = $('.id-container').first().find('.riskassessment-total-task-timespent-'+taskID)
	let date       = single.find('#RiskassessmentTaskTimespentDateEdit' + riskAssessmentTaskTimeSpentID).val();
	let hour       = single.find('#RiskassessmentTaskTimespentDateEdit' + riskAssessmentTaskTimeSpentID + 'hour').val();
	let min        = single.find('#RiskassessmentTaskTimespentDateEdit' + riskAssessmentTaskTimeSpentID + 'min').val();
	let comment    = single.find('.riskassessment-task-timespent-comment').val()
	comment        = window.digiriskdolibarr.risk.sanitizeBeforeRequest(comment)
	let duration   = single.find('.riskassessment-task-timespent-duration').val()

	window.saturne.loader.display($(this));
	window.saturne.loader.display($('.riskassessment-task-single-'+ taskID));

	$.ajax({
		url: document.URL + '&action=saveRiskAssessmentTaskTimeSpent&token='+token,
		data: JSON.stringify({
			riskAssessmentTaskTimeSpentID: riskAssessmentTaskTimeSpentID,
			taskID: taskID,
			date: date,
			hour: hour,
			min: min,
			comment: comment,
			duration: duration,
		}),
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( resp ) {
			currentElement.closest('.modal-active').removeClass('modal-active')
			let actionContainerSuccess = $('.messageSuccessTaskTimeSpentEdit'+ taskID);
			$('.wpeo-loader').removeClass('wpeo-loader')

			textToShow += actionContainerSuccess.find('.valueForEditTaskTimeSpent1').val()
			textToShow += taskRef
			textToShow += actionContainerSuccess.find('.valueForEditTaskTimeSpent2').val()

			timespent.html($(resp).find('.modal-content').find('.riskassessment-total-task-timespent-'+taskID).first())
			$('.riskassessment-task-timespent-list-'+taskID).html($(resp).find('.riskassessment-task-timespent-list-'+taskID).children())
			//actionContainerSuccess.find('a').attr('href', '#risk_row_'+riskId)

			actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( resp ) {
			let actionContainerError = $('.messageSuccessTaskTimeSpentEdit'+ taskID);

			textToShow += actionContainerError.find('.valueForEditTaskTimeSpent1').val()
			textToShow += taskRef
			textToShow += actionContainerError.find('.valueForEditTaskTimeSpent2').val()

			actionContainerError.find('.notice-subtitle .text').text(textToShow);
			actionContainerError.removeClass('hidden');
		}
	});
};

/**
 * Action check task progress.
 *
 * @since   9.1.0
 * @version 9.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.riskassessmenttask.checkTaskProgress = function ( event ) {
	let token = window.saturne.toolbox.getToken()

	let elementRiskAssessmentTask = $(this).closest('.riskassessment-task-container');
	let RiskAssessmentTaskId = elementRiskAssessmentTask.find('.riskassessment-task-single-content').attr('value');
	let riskId = $(this).closest('.riskassessment-tasks').attr('value');
	let textToShow = '';

	let taskRef = elementRiskAssessmentTask.attr('value');

	let taskProgress = '';
	if (elementRiskAssessmentTask.find('.riskassessment-task-progress-checkbox'+RiskAssessmentTaskId).hasClass('progress-checkbox-check')) {
		taskProgress = 0;
		elementRiskAssessmentTask.find('.riskassessment-task-progress-checkbox'+RiskAssessmentTaskId).toggleClass('progress-checkbox-check').toggleClass('progress-checkbox-uncheck');
	} else if (elementRiskAssessmentTask.find('.riskassessment-task-progress-checkbox'+RiskAssessmentTaskId).hasClass('progress-checkbox-uncheck')) {
		taskProgress = 1;
		elementRiskAssessmentTask.find('.riskassessment-task-progress-checkbox'+RiskAssessmentTaskId).toggleClass('progress-checkbox-uncheck').toggleClass('progress-checkbox-check');
	}

	window.saturne.loader.display($('.riskassessment-task-single-'+ RiskAssessmentTaskId));

	let url = window.location.href.replace(/#.*/, "");

	$.ajax({
		url: url + '&action=checkTaskProgress&token='+token,
		data: JSON.stringify({
			riskAssessmentTaskID: RiskAssessmentTaskId,
			taskProgress: taskProgress,
		}),
		type: "POST",
		processData: false,
		contentType: false,
		success: function ( resp ) {
			$('.fichecenter.risklist').html($(resp).find('#searchFormListRisks'))
			let actionContainerSuccess = $('.messageSuccessTaskEdit');
			$('.riskassessment-tasks' + riskId).fadeOut(800);
			$('.riskassessment-tasks' + riskId).fadeIn(800);
			textToShow += actionContainerSuccess.find('.valueForEditTask1').val()
			textToShow += taskRef
			textToShow += actionContainerSuccess.find('.valueForEditTask2').val()

			actionContainerSuccess.find('.notice-subtitle .text').text(textToShow)
			actionContainerSuccess.removeClass('hidden');
		},
		error: function ( resp ) {
			let actionContainerError = $('.messageErrorTaskEdit');

			textToShow += actionContainerError.find('.valueForEditTask1').val()
			textToShow += taskRef
			textToShow += actionContainerError.find('.valueForEditTask2').val()

			actionContainerError.find('.notice-subtitle .text').text(textToShow);
			actionContainerError.removeClass('hidden');
		}
	});
};

/**
 * Select riskAssessmentTask TimeSpent date hour.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  elementParent --- Parent element
 * @return {void}
 */
window.digiriskdolibarr.riskassessmenttask.selectRiskassessmentTaskTimespentDateHour = function( event ) {
	$(this).closest('.nowraponall').find('.select-riskassessmenttask-timespent-datehour').remove();
	$(this).before('<input class="select-riskassessmenttask-timespent-datehour" type="hidden" id="RiskassessmentTaskTimespentDatehour" name="RiskassessmentTaskTimespentDatehour" value='+$(this).val()+'>')
};

/**
 * Select riskAssessmentTask TimeSpent date min.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  elementParent --- Parent element
 * @return {void}
 */
window.digiriskdolibarr.riskassessmenttask.selectRiskassessmentTaskTimespentDateMin = function( event ) {
	$(this).closest('.nowraponall').find('.select-riskassessmenttask-timespent-datemin').remove();
	$(this).before('<input class="select-riskassessmenttask-timespent-datemin" type="hidden" id="RiskassessmentTaskTimespentDatemin" name="RiskassessmentTaskTimespentDatemin" value='+$(this).val()+'>')
};

/**
 * Check riskassessmenttask label length
 *
 * @since   9.4.0
 * @version 9.4.0
 *
 * @param  {MouseEvent} event [description]
 * @return {void}
 */
window.digiriskdolibarr.riskassessmenttask.checkRiskassessmentTaskLabelLength = function( event ) {
	var labelLenght = $(this).val().length;
	if (labelLenght > 255) {
		let actionContainerWarning = $('.messageWarningTaskLabel');
		actionContainerWarning.removeClass('hidden');
		$('.riskassessment-task-create').removeClass('button-blue');
		$('.riskassessment-task-create').addClass('button-grey');
		$('.riskassessment-task-create').addClass('button-disable');
	} else {
		let actionContainerWarning = $('.messageWarningTaskLabel');
		actionContainerWarning.addClass('hidden');
		$('.riskassessment-task-create').addClass('button-blue');
		$('.riskassessment-task-create').removeClass('button-grey');
		$('.riskassessment-task-create').removeClass('button-disable');
	}
};

/**
 * Redirect on shared task config
 *
 * @since   9.8.2
 * @version 9.8.2
 *
 * @param  {MouseEvent} event [description]
 * @return {void}
 */
window.digiriskdolibarr.riskassessmenttask.redirectOnSharedTaskConfig = function( event ) {
	let url = $('.riskassessment-tasks').find('input[name="sharedTaskTooltipUrl"]').val();
	window.open(location.origin + url, '_blank');
};
