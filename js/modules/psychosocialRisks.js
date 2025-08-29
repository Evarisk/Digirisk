
/**
 * Initialise l'objet "psychosocialRisks" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * @since   21.1.0
 * @version 21.1.0
 */
window.digiriskdolibarr.psychosocialRisks = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialRisks.init = function() {
  window.digiriskdolibarr.psychosocialRisks.event();

  // Initialiser l'état du bouton et de la case "tout sélectionner" au chargement
  setTimeout(function() {
    window.digiriskdolibarr.psychosocialRisks.toggleAddButton();
  }, 100);
};

/**
 * La méthode contenant tous les événements pour les boutons.
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialRisks.event = function() {
  $( document ).on( 'change', '.select-psychosocial-risk', window.digiriskdolibarr.psychosocialRisks.toggleAddButton );
  $( document ).on( 'click', '#submit_selected_psychosocial_risks', window.digiriskdolibarr.psychosocialRisks.submitSelectedRisks );
  $( document ).on( 'change', '#select_all_psychosocial_risks', window.digiriskdolibarr.psychosocialRisks.toggleSelectAll );
  //on checkbox click

};

/**
 * Active ou désactive le bouton d'ajout de risque psychosocial en fonction de la sélection.
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialRisks.toggleAddButton = function() {
  let selectedRisks = $('.select-psychosocial-risk:checked').length;
  if (selectedRisks > 0) {
    $('#submit_selected_psychosocial_risks').removeAttr('disabled').css('opacity', '1');
    //add class button-disabled
    $('#submit_selected_psychosocial_risks').removeClass('button-grey');
  } else {
    $('#submit_selected_psychosocial_risks').attr('disabled', 'disabled').css('opacity', '0.6');
    //add class button-grey
    $('#submit_selected_psychosocial_risks').addClass('button-grey');
  }

  // Mettre à jour l'état de la case "tout sélectionner"
  window.digiriskdolibarr.psychosocialRisks.updateSelectAllState();
};

/**
 * Gère la fonctionnalité "tout sélectionner / tout désélectionner"
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialRisks.toggleSelectAll = function() {
  let selectAll = $(this);
  let isChecked = selectAll.is(':checked');

  $('.select-psychosocial-risk').prop('checked', isChecked);

  window.digiriskdolibarr.psychosocialRisks.toggleAddButton();
};

/**
 * Met à jour l'état de la case "tout sélectionner" en fonction des sélections individuelles
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialRisks.updateSelectAllState = function() {
  let totalRisks = $('.select-psychosocial-risk').length;
  let selectedRisks = $('.select-psychosocial-risk:checked').length;
  let selectAllCheckbox = $('#select_all_psychosocial_risks');

  if (selectedRisks === totalRisks && totalRisks > 0) {
    selectAllCheckbox.prop('checked', true);
    selectAllCheckbox.prop('', false);
  } else if (selectedRisks === 0) {
    selectAllCheckbox.prop('checked', false);
    selectAllCheckbox.prop('', false);
  } else {
    selectAllCheckbox.prop('checked', false);
    selectAllCheckbox.prop('', true);
  }
};

/**
 * Collecte les données des risques psychosociaux sélectionnés
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {Array}
 */
window.digiriskdolibarr.psychosocialRisks.collectSelectedRisksData = function() {
  let selectedRisks = [];
  let fkElement = $('.psychosocial-risk-add-modal').attr('value');

  $('.select-psychosocial-risk:checked').each(function() {
    let index = $(this).attr('name').match(/\[(\d+)\]/)[1];
    let riskRow = $('#psychosocial_risk_' + index);

    let tasks = [];
    let taskTitle = riskRow.find('.task-name').val();
    if (taskTitle && taskTitle.trim() !== '') {
      tasks.push(taskTitle.trim());
    }

    let cotation = riskRow.find('.risk-evaluation-cotation.selected-cotation').data('evaluation-id');
    let description = riskRow.find('.risk-description').val();
    let subCategory = riskRow.find('.sub-category').val();
    let riskassessmentDate = riskRow.find('.riskassessment-date').val() || '';

    selectedRisks.push({
      description: description,
      cotation: cotation,
      method: 'standard',
      fk_element: fkElement,
      riskassessment_date: riskassessmentDate,
      category: 17,
      sub_category: subCategory,
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
 * Soumet les risques psychosociaux sélectionnés
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @param {Event}
 */
window.digiriskdolibarr.psychosocialRisks.submitSelectedRisks = function(e) {
  e.preventDefault();

  let button = $(this);
  let originalText = button.find('span').html();

  button.attr('disabled', 'disabled').css('opacity', '0.6');
  button.find('span').html('<i class="fas fa-spinner fa-spin"></i> Ajout en cours...');

  let selectedRisks = window.digiriskdolibarr.psychosocialRisks.collectSelectedRisksData();

  if (selectedRisks.length === 0) {
    button.removeAttr('disabled').css('opacity', '1');
    button.find('span').html(originalText);
    return;
  }

  console.log('Données envoyées:', selectedRisks);

  window.digiriskdolibarr.risk_table_common.submitRisks(
    selectedRisks,
    function(response, index) {
    },
    function(xhr, status, error, index) {
    }
  );
};
