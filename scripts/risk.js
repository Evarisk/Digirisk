window.eoxiaJS.saveRisk = function ( event ) {
	//empty and fill object card
	let editedRiskId = $(this).attr('value')
	console.log('te')
	$('#risk_row_'+editedRiskId).empty()
	$('#risk_row_'+editedRiskId).load( document.URL + '&action=editRisk' + editedRiskId + ' #risk_row_'+editedRiskId , id);
}
