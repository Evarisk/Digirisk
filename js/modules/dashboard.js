/**
 * Initialise l'objet "dashboard" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * @since   1.1.0
 * @version 1.1.0
 */
window.digiriskdolibarr.dashboard = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.dashboard.init = function() {
	window.digiriskdolibarr.dashboard.event();
};

/**
 * La méthode contenant tous les événements pour les dashboards.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.dashboard.event = function() {
	$( document ).on( 'change', '.add-dashboard-widget', window.digiriskdolibarr.dashboard.addDashBoardInfo );
	$( document ).on( 'click', '.close-dashboard-widget', window.digiriskdolibarr.dashboard.closeDashBoardInfo );
};

/**
 * Add widget dashboard info
 *
 * @since   9.5.0
 * @version 9.5.0
 *
 * @return {void}
 */
window.digiriskdolibarr.dashboard.addDashBoardInfo = function() {
	var dashboardWidgetForm = document.getElementById('dashBoardForm')
	var formData            = new FormData(dashboardWidgetForm)
	let dashboardWidgetName = formData.get('boxcombo')
	let querySeparator      = window.saturne.toolbox.getQuerySeparator(document.URL)
	let token               = window.saturne.toolbox.getToken()

	$.ajax({
		url: document.URL + querySeparator + 'action=adddashboardinfo&token=' + token,
		type: "POST",
		processData: false,
		data: JSON.stringify({
			dashboardWidgetName: dashboardWidgetName
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
 * Close widget dashboard info
 *
 * @since   9.5.0
 * @version 9.5.0
 *
 * @return {void}
 */
window.digiriskdolibarr.dashboard.closeDashBoardInfo = function() {
	let box                 = $(this);
	let dashboardWidgetName = box.attr('data-widgetname');
	let querySeparator      = window.saturne.toolbox.getQuerySeparator(document.URL)
	let token               = window.saturne.toolbox.getToken()

	$.ajax({
		url: document.URL + querySeparator + 'action=closedashboardinfo&token='+token,
		type: "POST",
		processData: false,
		data: JSON.stringify({
			dashboardWidgetName: dashboardWidgetName
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
