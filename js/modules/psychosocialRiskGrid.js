/**
 * Initialise l'objet "psychosocialRiskGrid" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * Grille d'évaluation des facteurs de risques psychosociaux de l'INRS (outil RPS-DU, ED 6403).
 *
 * @since   23.3.0
 * @version 23.3.0
 */
window.digiriskdolibarr.psychosocialRiskGrid = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   23.3.0
 * @version 23.3.0
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialRiskGrid.init = function() {
  window.digiriskdolibarr.psychosocialRiskGrid.event();
};

/**
 * La méthode contenant tous les événements de la grille.
 *
 * @since   23.3.0
 * @version 23.3.0
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialRiskGrid.event = function() {
  $( document ).on( 'click', '.psychosocial-grid-answer', window.digiriskdolibarr.psychosocialRiskGrid.selectAnswer );
  $( document ).on( 'click', '.psychosocial-grid-submit', window.digiriskdolibarr.psychosocialRiskGrid.submitAssessedCriteria );
};

/**
 * Retient la réponse cliquée pour un critère, ou la retire si elle était déjà retenue.
 *
 * @since   23.3.0
 * @version 23.3.0
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialRiskGrid.selectAnswer = function() {
  let answer      = $(this);
  let wasSelected = answer.hasClass('psychosocial-grid-answer-selected');

  answer.closest('.psychosocial-grid-answers').find('.psychosocial-grid-answer-selected').removeClass('psychosocial-grid-answer-selected');

  // Un critère est créé parce qu'il porte une réponse : sans ce retour en arrière, une réponse
  // cliquée par erreur ne peut plus être retirée et le risque part quand même.
  if (!wasSelected) {
    answer.addClass('psychosocial-grid-answer-selected');
  }

  window.digiriskdolibarr.psychosocialRiskGrid.refreshSubmitButton();
};

/**
 * Met le compteur et le bouton d'ajout à jour sur le nombre de critères cotés.
 *
 * @since   23.3.0
 * @version 23.3.0
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocialRiskGrid.refreshSubmitButton = function() {
  let submitButton     = $('.psychosocial-grid-submit');
  let assessedCriteria = $('.psychosocial-grid-row').has('.psychosocial-grid-answer-selected').length;

  submitButton.find('.psychosocial-grid-assessed-count').text(assessedCriteria);

  if (assessedCriteria > 0) {
    submitButton.removeAttr('disabled').removeClass('button-grey');
  } else {
    submitButton.attr('disabled', 'disabled').addClass('button-grey');
  }
};

/**
 * Collecte les critères cotés de la grille.
 *
 * @since   23.3.0
 * @version 23.3.0
 *
 * @return {Array}
 */
window.digiriskdolibarr.psychosocialRiskGrid.collectAssessedCriteria = function() {
  let modal              = $('.psychosocial-risk-grid-modal');
  let riskAssessmentDate = modal.find('.psychosocial-grid-date').val() || '';
  let criteria           = [];

  modal.find('.psychosocial-grid-row').each(function() {
    let row    = $(this);
    let answer = row.find('.psychosocial-grid-answer-selected');

    if (answer.length === 0) {
      return;
    }

    let tasks     = [];
    let taskLabel = row.find('.psychosocial-grid-task').val().trim();
    if (taskLabel !== '') {
      tasks.push(taskLabel);
    }

    criteria.push({
      description: row.find('.psychosocial-grid-description').val(),
      cotation: answer.data('cotation'),
      method: 'standard',
      fk_element: modal.attr('value'),
      riskassessment_date: riskAssessmentDate,
      category: modal.data('category'),
      sub_category: row.data('sub-category'),
      tasks: tasks
    });
  });

  return criteria;
};

/**
 * Crée un risque par critère coté, en une seule requête.
 *
 * @since   23.3.0
 * @version 23.3.0
 *
 * @param  {Event} event L'état du clic.
 * @return {void}
 */
window.digiriskdolibarr.psychosocialRiskGrid.submitAssessedCriteria = function(event) {
  event.preventDefault();

  let button = $(this);

  // L'attribut disabled ne bloque pas le clic sur un div : sans cette garde un second clic
  // pendant l'envoi créerait les risques une deuxième fois.
  if (button.attr('disabled')) {
    return;
  }

  let criteria = window.digiriskdolibarr.psychosocialRiskGrid.collectAssessedCriteria();
  if (criteria.length === 0) {
    return;
  }

  let label        = button.find('.psychosocial-grid-submit-label');
  let labelContent = label.html();

  button.attr('disabled', 'disabled').addClass('button-grey');
  label.html('<i class="fas fa-spinner fa-spin"></i> ' + button.data('loading-label'));
  $('.psychosocial-grid-error').addClass('hidden');

  $.ajax({
    url: ($('#dol_url_root').val() || window.location.origin) + '/custom/digiriskdolibarr/core/ajax/create_risk.php?token=' + window.saturne.toolbox.getToken(),
    method: 'POST',
    data: JSON.stringify({risks: criteria}),
    contentType: 'application/json',
    dataType: 'json',
    cache: false,
    headers: {
      'X-Requested-With': 'XMLHttpRequest'
    },
    success: function(response) {
      if (response && response.success > 0) {
        window.location.reload();
        return;
      }

      window.digiriskdolibarr.psychosocialRiskGrid.restoreSubmitButton(button, label, labelContent);
    },
    error: function() {
      window.digiriskdolibarr.psychosocialRiskGrid.restoreSubmitButton(button, label, labelContent);
    }
  });
};

/**
 * Remet le bouton d'ajout dans son état initial et affiche l'erreur : la grille reste saisie,
 * les 26 critères n'ont pas à être recotés pour réessayer.
 *
 * @since   23.3.0
 * @version 23.3.0
 *
 * @param  {jQuery} button       Le bouton d'ajout.
 * @param  {jQuery} label        Le libellé du bouton.
 * @param  {string} labelContent Le contenu du libellé avant l'envoi.
 * @return {void}
 */
window.digiriskdolibarr.psychosocialRiskGrid.restoreSubmitButton = function(button, label, labelContent) {
  button.removeAttr('disabled').removeClass('button-grey');
  label.html(labelContent);
  $('.psychosocial-grid-error').removeClass('hidden');
};
