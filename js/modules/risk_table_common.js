/**
 * Fonctions communes pour les tables de risques (DigiAI et risques psychosociaux)
 *
 * @since   21.1.0
 * @version 21.1.0
 */
window.digiriskdolibarr.risk_table_common = {};

/**
 * Création du système de cotation pour un risque
 *
 * @param {number} cotation - La cotation du risque
 * @returns {jQuery} - L'élément cotation
 */
window.digiriskdolibarr.risk_table_common.createCotationElement = function(cotation) {
  const scale = cotation <= 47 ? 1 : cotation <= 50 ? 2 : cotation <= 80 ? 3 : 4;
  const labelMap = {
    1: { value: 0, label: '0-47' },
    2: { value: 48, label: '48-50' },
    3: { value: 51, label: '51-80' },
    4: { value: 100, label: '81-100' }
  };

  const cotationInput = $(`
    <div class="cotation-container">
      <div class="cotation-standard" style="display: block">
        <span class="title"><i class="fas fa-chart-line"></i> Cotation <required>*</required></span>
        <div class="cotation-listing wpeo-gridlayout grid-4 grid-gap-0"></div>
      </div>
    </div>
  `);

  const cotationListing = cotationInput.find('.cotation-listing');
  const hiddenInput = $('<input type="hidden" name="cotation[]" value="' + cotation + '">');
  cotationInput.append(hiddenInput);

  // Ajouter les 4 blocs de cotation
  Object.entries(labelMap).forEach(([scaleKey, data]) => {
    const div = $(`
      <div class="risk-evaluation-cotation cotation" data-evaluation-method="standard"
           data-evaluation-id="${data.value}" data-scale="${scaleKey}" data-id="0"
           data-variable-id="${152 + data.value}">
        ${data.label}
      </div>
    `);
    div.css('cursor', 'pointer');

    if (parseInt(scaleKey) === scale) {
      div.addClass('selected-cotation');
    }

    div.on('click', function() {
      cotationListing.find('.selected-cotation').removeClass('selected-cotation');
      $(this).addClass('selected-cotation');
      hiddenInput.val($(this).data('evaluation-id'));
    });

    cotationListing.append(div);
  });

  return cotationInput;
};

/**
 * Création d'une checkbox avec gestion de l'état du bouton de soumission
 *
 * @param {string} checkboxClass - La classe CSS de la checkbox
 * @param {string} submitButtonId - L'ID du bouton de soumission
 * @returns {jQuery} - L'élément checkbox
 */
window.digiriskdolibarr.risk_table_common.createCheckbox = function(checkboxClass, submitButtonId) {
  const checkbox = $('<input type="checkbox" class="' + checkboxClass + '">');

  checkbox.on('change', function() {
    const anyChecked = $('.' + checkboxClass + ':checked').length > 0;
    $('#' + submitButtonId).prop('disabled', !anyChecked);
    $('#' + submitButtonId).css('opacity', anyChecked ? 1 : 0.6);
  });

  return checkbox;
};

/**
 * Création du conteneur d'actions de prévention
 *
 * @param {Array} actions - Array des actions de prévention
 * @returns {jQuery} - Le conteneur des actions
 */
window.digiriskdolibarr.risk_table_common.createActionsContainer = function(actions) {
  const actionsContainer = $('<div>');

  actions.forEach(action => {
    const textarea = $('<textarea>')
      .addClass('form-control action-textarea')
      .val(action)
      .css({
        width: '100%',
        height: '32px',
        resize: 'vertical',
        overflow: 'hidden',
        lineHeight: '1.4',
        padding: '6px 8px',
        fontSize: '13px',
        marginBottom: '5px'
      });
    actionsContainer.append(textarea);
  });

  return actionsContainer;
};

/**
 * Création d'une image de catégorie
 *
 * @param {string} imagePath - Le chemin de l'image
 * @param {string} ariaLabel - Le label d'accessibilité
 * @returns {jQuery} - L'élément image dans son conteneur
 */
window.digiriskdolibarr.risk_table_common.createCategoryImage = function(imagePath, ariaLabel) {
  const categoryCell = $('<div class="risk-category-display">');
  const riskImg = $('<img>')
    .addClass('danger-category-pic tooltip wpeo-tooltip-event hover')
    .attr('src', imagePath)
    .attr('aria-label', ariaLabel)
    .css({ width: '40px', height: '40px' });

  categoryCell.append(riskImg);
  return categoryCell;
};

/**
 * Création d'un textarea pour la description
 *
 * @param {string} description - Le texte de description
 * @returns {jQuery} - L'élément textarea
 */
window.digiriskdolibarr.risk_table_common.createDescriptionTextarea = function(description) {
  return $('<textarea>')
    .addClass('form-control')
    .val(description)
    .css({ width: '100%', height: '60px' });
};

/**
 * Soumission AJAX pour créer des risques (utilisé pour les risques psychosociaux avec rechargement de page)
 *
 * @param {Array} risksData - Array des données de risques à créer
 * @param {function} successCallback - Callback en cas de succès (optionnel)
 * @param {function} errorCallback - Callback en cas d'erreur (optionnel)
 */
window.digiriskdolibarr.risk_table_common.submitRisks = function(risksData, successCallback, errorCallback) {
  const token = window.saturne.toolbox.getToken();
  const dolUrlRoot = $('#dol_url_root').val() || window.location.origin;

  let completedRequests = 0;
  let failedRequests = 0;
  const totalRequests = risksData.length;

  risksData.forEach((riskData, index) => {
    $.ajax({
      url: dolUrlRoot + '/custom/digiriskdolibarr/core/ajax/create_risk.php?token=' + token,
      method: 'POST',
      data: JSON.stringify(riskData),
      contentType: 'application/json',
      dataType: 'json',
      cache: false,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      },
      success: function(response) {
        completedRequests++;

        if (typeof successCallback === 'function') {
          successCallback(response, index);
        }

        if (completedRequests + failedRequests === totalRequests) {
          if (failedRequests === 0) {
            window.location.reload();
          } else {
            alert('Certains risques n\'ont pas pu être ajoutés. Veuillez vérifier.');
          }
        }
      },
      error: function(xhr, status, error) {
        failedRequests++;

        if (typeof errorCallback === 'function') {
          errorCallback(xhr, status, error, index);
        }

        if (completedRequests + failedRequests === totalRequests) {
          if (completedRequests > 0) {
            window.location.reload();
          } else {
            alert('Aucun risque n\'a pu être ajouté. Veuillez vérifier.');
          }
        }
      }
    });
  });
};

/**
 * Extraction des données d'une ligne de risque
 *
 * @param {jQuery} row - La ligne du tableau
 * @returns {Object} - Les données du risque
 */
window.digiriskdolibarr.risk_table_common.extractRiskDataFromRow = function(row) {
  const description = row.find('textarea').first().val();
  const cotation = parseInt(row.find('input[name="cotation[]"]').val());
  const actions = [];

  row.find('textarea.action-textarea').each(function() {
    const action = $(this).val().trim();
    if (action !== '') actions.push(action);
  });

  const category = row.data('category');
  const digiriskElementId = $('.digiai-risk-add').attr('value') || $('#digiriskElementId').val();

  return {
    description: description,
    cotation: cotation,
    method: 'simple',
    fk_element: digiriskElementId,
    category: category,
    photo: '',
    tasks: actions,
    dateStart: '',
    hourStart: '',
    minStart: '',
    dateEnd: '',
    hourEnd: '',
    minEnd: '',
    budget: ''
  };
};
