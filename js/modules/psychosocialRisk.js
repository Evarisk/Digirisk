/**
 * Initialise l'objet "psychosocial_risk" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * @since   21.1.0
 * @version 21.1.0
 */
window.digiriskdolibarr.psychosocial_risk = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocial_risk.init = function() {
  window.digiriskdolibarr.psychosocial_risk.event();
};

/**
 * La méthode contenant tous les événements pour psychosocial_risk.
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocial_risk.event = function() {
  $(document).on('click', '.psychosocial-risk-add', window.digiriskdolibarr.psychosocial_risk.openModal);
  $(document).on('click', '#submit_selected_psychosocial_risks', window.digiriskdolibarr.psychosocial_risk.addSelectedRisks);
};

/**
 * Risques psychosociaux prédéfinis
 */
window.digiriskdolibarr.psychosocial_risk.predefinedRisks = [
  {
    title: "rps_v2",
    category: "Risques psychosociaux",
    description: "Rapports sociaux au travail",
    cotation: 65,
    prevention_actions: []
  },
  {
    title: "rps_v2",
    category: "Risques psychosociaux",
    description: "Intensité et temps de travail",
    cotation: 48,
    prevention_actions: []
  },
  {
    title: "rps_v2",
    category: "Risques psychosociaux",
    description: "Exigences émotionnelles",
    cotation: 48,
    prevention_actions: []
  },
  {
    title: "rps_v2",
    category: "Risques psychosociaux",
    description: "Autonomie",
    cotation: 48,
    prevention_actions: []
  },
  {
    title: "rps_v2",
    category: "Risques psychosociaux",
    description: "Sens du travail",
    cotation: 48,
    prevention_actions: []
  },
  {
    title: "rps_v2",
    category: "Risques psychosociaux",
    description: "Insécurité de la situation de travail",
    cotation: 48,
    prevention_actions: []
  },
  {
    title: "rps_v2",
    category: "Risques psychosociaux",
    description: "Contexte de prévention dans l'entreprise",
    cotation: 65,
    prevention_actions: []
  },
  {
    title: "rps_v2",
    category: "Risques psychosociaux",
    description: "Impact des RPS sur l'entreprise et les salariés",
    cotation: 25,
    prevention_actions: []
  }
];

/**
 * Ouvre la modal des risques psychosociaux et affiche les risques prédéfinis
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocial_risk.openModal = function() {
  const digiriskElementId = $(this).attr('value');

  // Remplir le tableau avec les risques prédéfinis
  window.digiriskdolibarr.psychosocial_risk.populateRisksTable();

  // Ouvrir la modal
  const modalId = 'psychosocial_risk_add' + digiriskElementId;
  $('#' + modalId).addClass('modal-active');
};

/**
 * Remplit le tableau avec les risques psychosociaux prédéfinis
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocial_risk.populateRisksTable = function() {
  const tbody = $('#psychosocial_risks_list');
  tbody.empty();

  const dolUrlRoot = $('#dol_url_root').val() || window.location.origin;
  const risks            = window.digiriskdolibarr.psychosocial_risk.predefinedRisks;
  const positionCategory = "17";

  risks.forEach((risk, index) => {
    const tr = $('<tr class="oddeven psychosocial-risk-row" id="psychosocial_risk_' + index + '">');
    tr.attr('data-category', positionCategory);

    const checkbox = window.digiriskdolibarr.risk_table_common.createCheckbox('select-psychosocial-risk', 'submit_selected_psychosocial_risks');
    const categoryCell = window.digiriskdolibarr.risk_table_common.createCategoryImage(
      dolUrlRoot + '/custom/digiriskdolibarr/img/categorieDangers/rps_v2.png',
      risk.category
    );
    const cotationInput = window.digiriskdolibarr.risk_table_common.createCotationElement(risk.cotation);
    const descInput = window.digiriskdolibarr.risk_table_common.createDescriptionTextarea(risk.description);
    const actionsContainer = window.digiriskdolibarr.risk_table_common.createActionsContainer(risk.prevention_actions);

    tr.append($('<td>').append(checkbox));
    tr.append($('<td>').append(categoryCell));
    tr.append($('<td>').append(cotationInput));
    tr.append($('<td>').append(descInput));
    tr.append($('<td>').append(actionsContainer));

    tbody.append(tr);
  });
};

/**
 * Ajoute les risques psychosociaux sélectionnés
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.psychosocial_risk.addSelectedRisks = function() {
  const selectedRows = $('input.select-psychosocial-risk:checked').closest('tr');

  if (selectedRows.length === 0) {
    return;
  }

  const risksData = [];
  selectedRows.each(function() {
    const riskData = window.digiriskdolibarr.risk_table_common.extractRiskDataFromRow($(this));
    risksData.push(riskData);
  });

  window.digiriskdolibarr.risk_table_common.submitRisks(
    risksData,
    function(response, index) {
      console.log('Risque psychosocial ajouté avec succès');
    },
    function(xhr, status, error, index) {
      console.error('Erreur lors de l\'ajout du risque psychosocial : ' + (xhr.responseText || error));
    }
  );
};
