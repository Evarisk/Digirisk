
/**
 * Initialise l'objet "evaluationMethodEvarisk" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
window.digiriskdolibarr.evaluationMethodEvarisk = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.evaluationMethodEvarisk.init = function() {
	window.digiriskdolibarr.evaluationMethodEvarisk.event();
};

/**
 * La méthode contenant tous les événements pour le evaluation.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.evaluationMethodEvarisk.event = function() {
	$( document ).on( 'click', '.wpeo-table.evaluation-method .table-cell.can-select', window.digiriskdolibarr.evaluationMethodEvarisk.selectSeuil );
};

/**
 * Select Seuil on advanced cotation.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param  {ClickEvent} event L'état du clic.
 * @return {void}
 */
window.digiriskdolibarr.evaluationMethodEvarisk.selectSeuil = function( event ) {
	$( this ).closest( '.table-row' ).find( '.active' ).removeClass( 'active' );
	$( this ).addClass( 'active' );

	var element       = $( this );
	var elementParent = element.closest('.modal-container');
	var evaluationID  = element.data( 'evaluation-id' );

	let criteres = [];
	Object.values(elementParent.find('.table-cell.active.cell-'+evaluationID)).forEach(function(v) {
		if ($(v).data( 'seuil' ) > -1) {
			criteres.push($(v).data( 'seuil' ));
		}
	});

	// Rend le bouton "active" et met à jour la cotation et la scale
	if (criteres.length === 5) {
		let cotationBeforeAdapt = criteres[0] * criteres[1] * criteres[2] * criteres[3] * criteres[4];

		let root = window.location.pathname.split(/view/)[0]

		fetch(root + '/js/json/default.json').then(response => response.json()).then(data => {
			let cotationAfterAdapt = data[0].option.matrix[cotationBeforeAdapt];
			elementParent.find('.risk-evaluation-calculated-cotation').find('.risk-evaluation-cotation').attr('data-scale', window.digiriskdolibarr.evaluation.getDynamicScale(cotationAfterAdapt));
			elementParent.find('.risk-evaluation-calculated-cotation').find('.risk-evaluation-cotation span').text(cotationAfterAdapt);
			elementParent.find('.risk-evaluation-content').find('.risk-evaluation-seuil').val(cotationAfterAdapt);
			window.digiriskdolibarr.risk.haveDataInInput(elementParent);
		})
	}
};
