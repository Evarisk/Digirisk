/**
 * Initialise l'objet "psychosocialRisksED6403" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 * Gère la modale d'ajout de risques psychosociaux suivant la grille RPS-DU de l'INRS (ED 6403).
 *
 * @since   23.1.0
 * @version 23.1.0
 */
window.digiriskdolibarr.psychosocialRisksED6403 = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   23.1.0
 * @version 23.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialRisksED6403.init = function() {
  window.digiriskdolibarr.psychosocialRisksED6403.event();
  window.digiriskdolibarr.psychosocialRisksED6403.toggleAddButton();
};

/**
 * La méthode contenant tous les événements pour les boutons.
 *
 * @since   23.1.0
 * @version 23.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialRisksED6403.event = function() {
  $( document ).on( 'change', '.select-psychosocial-risk-ed6403', window.digiriskdolibarr.psychosocialRisksED6403.toggleAddButton );
  $( document ).on( 'change', '#select_all_psychosocial_risks_ed6403', window.digiriskdolibarr.psychosocialRisksED6403.toggleSelectAll );
  $( document ).on( 'click', '#submit_selected_psychosocial_risks_ed6403', window.digiriskdolibarr.psychosocialRisksED6403.submitSelectedRisks );
};

/**
 * Active ou désactive le bouton d'ajout en fonction de la sélection.
 *
 * @since   23.1.0
 * @version 23.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialRisksED6403.toggleAddButton = function() {
  let submitButton = $( '#submit_selected_psychosocial_risks_ed6403' );

  if ( ! submitButton.length ) {
    return;
  }

  if ( $( '.select-psychosocial-risk-ed6403:checked' ).length > 0 ) {
    submitButton.removeAttr( 'disabled' ).removeClass( 'button-grey' );
  } else {
    submitButton.attr( 'disabled', 'disabled' ).addClass( 'button-grey' );
  }

  window.digiriskdolibarr.psychosocialRisksED6403.updateSelectAllState();
};

/**
 * Gère la fonctionnalité "tout sélectionner / tout désélectionner".
 *
 * @since   23.1.0
 * @version 23.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialRisksED6403.toggleSelectAll = function() {
  $( '.select-psychosocial-risk-ed6403' ).prop( 'checked', $( this ).is( ':checked' ) );

  window.digiriskdolibarr.psychosocialRisksED6403.toggleAddButton();
};

/**
 * Met à jour l'état de la case "tout sélectionner" en fonction des sélections individuelles.
 *
 * @since   23.1.0
 * @version 23.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialRisksED6403.updateSelectAllState = function() {
  let totalCriteria    = $( '.select-psychosocial-risk-ed6403' ).length;
  let selectedCriteria = $( '.select-psychosocial-risk-ed6403:checked' ).length;

  $( '#select_all_psychosocial_risks_ed6403' ).prop( 'checked', totalCriteria > 0 && selectedCriteria === totalCriteria );
};

/**
 * Collecte les données des critères sélectionnés.
 *
 * @since   23.1.0
 * @version 23.1.0
 *
 * @return {Array}
 */
window.digiriskdolibarr.psychosocialRisksED6403.collectSelectedRisksData = function() {
  let selectedRisks = [];
  let fkElement     = $( '.psychosocial-risk-ed6403-add-modal' ).attr( 'value' );

  $( '.select-psychosocial-risk-ed6403:checked' ).each( function() {
    let criterionRow = $( this ).closest( '.psychosocial-risk-ed6403-row' );

    let tasks     = [];
    let taskTitle = criterionRow.find( '.task-name' ).val();
    if ( taskTitle && taskTitle.trim() !== '' ) {
      tasks.push( taskTitle.trim() );
    }

    selectedRisks.push({
      description: criterionRow.find( '.rps-risk-description' ).val(),
      cotation: criterionRow.find( '.risk-evaluation-cotation.selected-cotation' ).data( 'evaluation-id' ),
      method: 'standard',
      fk_element: fkElement,
      riskassessment_date: criterionRow.find( '.riskassessment-date' ).val() || '',
      category: 17,
      sub_category: criterionRow.find( '.sub-category' ).val(),
      photo: '',
      tasks: tasks,
      dateStart: '',
      hourStart: '',
      minStart: '',
      dateEnd: '',
      hourEnd: '',
      minEnd: '',
      budget: ''
    });
  });

  return selectedRisks;
};

/**
 * Soumet les critères sélectionnés en une seule requête.
 *
 * @since   23.1.0
 * @version 23.1.0
 *
 * @param  {Event} event L'état du clic.
 * @return {void}
 */
window.digiriskdolibarr.psychosocialRisksED6403.submitSelectedRisks = function( event ) {
  event.preventDefault();

  let button = $( this );

  if ( button.attr( 'disabled' ) ) {
    return;
  }

  let selectedRisks = window.digiriskdolibarr.psychosocialRisksED6403.collectSelectedRisksData();
  if ( selectedRisks.length === 0 ) {
    return;
  }

  let originalContent = button.find( 'span' ).html();
  button.attr( 'disabled', 'disabled' ).addClass( 'button-grey' );
  button.find( 'span' ).html( '<i class="fas fa-spinner fa-spin"></i>' );

  window.digiriskdolibarr.risk_table_common.submitRisksBatch( selectedRisks, function() {
    button.removeAttr( 'disabled' ).removeClass( 'button-grey' );
    button.find( 'span' ).html( originalContent );
  });
};
