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
    category: "Exigences émotionnelles",
    description: "Exposition à des situations émotionnellement difficiles (contact avec la souffrance, agressivité, tensions relationnelles)",
    cotation: 65,
    prevention_actions: [
      "Former le personnel à la gestion des situations difficiles",
      "Mettre en place un soutien psychologique",
      "Organiser des débriefings après incidents",
      "Améliorer les conditions d'accueil et de communication"
    ]
  },
  {
    title: "rps_v2",
    category: "Intensité et temps de travail",
    description: "Charge de travail excessive, rythme imposé, interruptions fréquentes, horaires de travail contraignants",
    cotation: 45,
    prevention_actions: [
      "Analyser et réorganiser la charge de travail",
      "Respecter les temps de pause et de récupération",
      "Améliorer la planification des tâches",
      "Former à la gestion du temps et des priorités"
    ]
  },
  {
    title: "rps_v2",
    category: "Autonomie",
    description: "Manque de marge de manœuvre dans l'exécution du travail, procédures trop rigides, sous-utilisation des compétences",
    cotation: 45,
    prevention_actions: [
      "Développer les marges de manœuvre des salariés",
      "Favoriser la participation aux décisions",
      "Adapter les compétences aux postes",
      "Encourager l'initiative et la créativité"
    ]
  },
  {
    title: "rps_v2",
    category: "Rapports sociaux au travail",
    description: "Relations difficiles avec la hiérarchie, les collègues ou le public, manque de soutien social",
    cotation: 45,
    prevention_actions: [
      "Former les managers au management bienveillant",
      "Améliorer la communication interne",
      "Développer l'esprit d'équipe",
      "Mettre en place des espaces d'échange"
    ]
  },
  {
    title: "rps_v2",
    category: "Sens du travail",
    description: "Perte du sens du travail, conflits de valeurs, qualité empêchée, travail inutile",
    cotation: 45,
    prevention_actions: [
      "Clarifier les objectifs et la finalité du travail",
      "Valoriser les métiers et les compétences",
      "Améliorer la qualité du travail",
      "Favoriser la reconnaissance du travail accompli"
    ]
  },
  {
    title: "rps_v2",
    category: "Insécurité de la situation de travail",
    description: "Précarité de l'emploi, changements organisationnels non maîtrisés, avenir professionnel incertain",
    cotation: 45,
    prevention_actions: [
      "Améliorer la communication sur les évolutions",
      "Accompagner les changements organisationnels",
      "Développer l'employabilité des salariés",
      "Sécuriser les parcours professionnels"
    ]
  },
  {
    title: "rps_v2",
    category: "Contexte de prévention dans l'entreprise",
    description: "Absence de politique de prévention des RPS, manque de formation, défaut de détection des situations à risque",
    cotation: 65,
    prevention_actions: [
      "Élaborer une politique de prévention des RPS",
      "Former les acteurs de la prévention",
      "Mettre en place des indicateurs de suivi",
      "Créer des instances de dialogue social"
    ]
  },
  {
    title: "rps_v2",
    category: "Impact des RPS sur l'entreprise et les salariés",
    description: "Conséquences des RPS : absentéisme, turnover, accidents du travail, maladies professionnelles, perte de performance",
    cotation: 45,
    prevention_actions: [
      "Surveiller les indicateurs d'alerte",
      "Mettre en place un suivi médical renforcé",
      "Analyser les causes d'absentéisme",
      "Améliorer les conditions de travail globales"
    ]
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
  const risks = window.digiriskdolibarr.psychosocial_risk.predefinedRisks;

  risks.forEach((risk, index) => {
    const tr = $('<tr class="oddeven psychosocial-risk-row" id="psychosocial_risk_' + index + '">');
    tr.attr('data-category', risk.title);

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
