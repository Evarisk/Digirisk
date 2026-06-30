/**
 * Initialise l'objet "digiai" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * @since   21.1.0
 * @version 21.1.0
 */
window.digiriskdolibarr.digiai = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.digiai.init = function() {
  window.digiriskdolibarr.digiai.event();
  window.digiriskdolibarr.digiai.initTabs();
  // window.digiriskdolibarr.digiai.bypassSaturneForDigiAI();
};

/**
 * La méthode contenant tous les événements pour digiai.
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.digiai.event = function() {
  $(document).on('submit', '#analyze_text_form', window.digiriskdolibarr.digiai.submitTextForm);
  $(document).on('click', '.digiAI-tab-button', window.digiriskdolibarr.digiai.switchTab);
  $(document).on('click', '.open-analyze-image-modal', window.digiriskdolibarr.digiai.changeModalButton);
  $(document).on('click', '.analyze-image', window.digiriskdolibarr.digiai.submitImageForm);
  $( document ).on( 'click', '.clickable-photo', window.digiriskdolibarr.digiai.selectPhoto );
};

/**
 * Initialise la gestion des onglets
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.digiai.initTabs = function() {
  $('.digiAI-tab-button').first().addClass('active');
  $('.digiAI-tab-content').first().addClass('active');
};

/**
 * Gère le changement d'onglets
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.digiai.switchTab = function(e) {
  e.preventDefault();

  const targetTab = $(this).attr('data-tab');

  $('.digiAI-tab-button').removeClass('active');
  $('.digiAI-tab-content').removeClass('active');

  $(this).addClass('active');
  $('#' + targetTab).addClass('active');
};

/**
 * Select photo
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.digiai.selectPhoto = function( event ) {
  $('.analyze-image').removeClass('button-disable');
};

/**
 * Bypass complètement Saturne pour DigiAI
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.digiai.changeModalButton = function() {
  $('.modal-photo[data-from-type="digiAi"]')
    .find('.save-photo')
    .removeClass('save-photo')
    .addClass('analyze-image');
};

/**
 * Méthode pour gérer le formulaire de soumission du fichier image et l'analyse directe par ChatGPT.
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.digiai.submitImageForm = async function(e) {
  e.preventDefault();

  window.digiriskdolibarr.digiai.resetModal('image');
  let mediaGallery = $('#media_gallery');
  let mediaGalleryModal = $(this).closest('.modal-container');
  mediaGallery.removeClass('modal-active');

  let fileLinked = mediaGalleryModal.find('.clicked-photo').first().find('.filename').val();
  let imgSrc = mediaGalleryModal.find('.clicked-photo').first().find('img').attr('src');

  if (!fileLinked) {
    alert('Veuillez sélectionner une image.');
    return;
  }

  $('#uploaded-image-preview').attr('src', imgSrc).css('opacity', 1).show();
  $('#analyzed-text-preview').hide();
  $('.analysis-in-progress').show().css('opacity', 1);
  $('.analysis-result').hide();

  $('#digiai_modal').addClass('modal-active');

  try {
    const response = await fetch(imgSrc);
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const blob = await response.blob();

    const formData = new FormData();
    formData.append('image_file', blob, fileLinked);
    formData.append('action', 'analyze_image');

    window.digiriskdolibarr.digiai.getChatGptResponse(formData);

  } catch (error) {
    console.error('Erreur lors du chargement de l\'image:', error);
    alert('Erreur lors du chargement de l\'image depuis la bibliothèque de médias');

    $('#digiai_modal').removeClass('modal-active');
  }
};

/**
 * Méthode pour gérer le formulaire d'analyse de texte
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.digiai.submitTextForm = async function(e) {
  e.preventDefault();

  const textArea = $('#analysis_text');
  const text = textArea.val().trim();

  if (!text) {
    alert('Veuillez saisir du texte à analyser');
    return;
  }

  window.digiriskdolibarr.digiai.resetModal('text');

  $('#uploaded-image-preview').hide();
  $('#analyzed-text-preview').show();
  $('.text-preview-content').html(text.replace(/\n/g, '<br>'));

  $('.digiai-loader-text').text('Analyse en cours du texte...');

  $('#digiai_modal').addClass('modal-active');

  let formData = new FormData();
  formData.append('action', 'analyze_text');
  formData.append('analysis_text', text);

  window.digiriskdolibarr.digiai.getChatGptResponse(formData)

};

/**
 * Récupère la réponse de ChatGPT
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 *
 */
window.digiriskdolibarr.digiai.getChatGptResponse = async function(formData) {

  let token = window.saturne.toolbox.getToken();

  try {
    let chatGptResponse = await fetch('backend_endpoint_for_chatgpt.php?token=' + token, {
      method: 'POST',
      body: formData
    });

    if (!chatGptResponse.ok) {
      throw new Error('Pas de clé API ou de jetons suffisants, veuillez configurer votre clé API dans "Configuration => DigiAI"');
    }
    let chatGptData = await chatGptResponse.json();

    if (chatGptData.error) {
      throw new Error('Pas de clé API ou de jetons suffisants, veuillez configurer votre clé API dans "Configuration => DigiAI"');
    }

    let rawContent = chatGptData.choices[0].message.content.trim();
    let cleanedContent = rawContent.replace(/^```json\s*|```$/g, '').trim();

    try {
      JSON.parse(cleanedContent);
    } catch (e) {
      throw new Error('Erreur lors de l\'analyse de l\'image, veuillez réessayer');
    }
    let risque = JSON.parse(cleanedContent);

    window.digiriskdolibarr.digiai.displayResults(risque);

  } catch (error) {
    alert(error.message);
    $('#digiai_modal').removeClass('modal-active');
  }
}

/**
 * Affiche les résultats d'analyse dans le tableau
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @param {Array} risks - Liste des risques détectés
 * @return {void}
 */
window.digiriskdolibarr.digiai.displayResults = function(risks) {
  $('.modal-analyse-phase').fadeOut(400, function () {
    $('.modal-result-phase').fadeIn(400);
  });

  let table = $('#risque_table');
  table.css('display', 'table');
  let tbody = table.find('tbody');
  tbody.empty();

  let dolUrlRoot = $('#dol_url_root').val();
  const categoryMap = window.digiriskdolibarr.categoryMap;

  risks.forEach((risque, index) => {
    let tr = $('<tr class="oddeven" id="new_risk' + index + '">');

    let title = risque.title;
    tr.attr('data-category', title);

    let cotation = parseInt(risque.cotation);
    let description = risque.description;
    let prevention_actions = risque.prevention_actions;

    let descInput = window.digiriskdolibarr.risk_table_common.createDescriptionTextarea(description);
    let cotationInput = window.digiriskdolibarr.risk_table_common.createCotationElement(cotation);
    let actionsContainer = window.digiriskdolibarr.risk_table_common.createActionsContainer(prevention_actions);

    let riskImgContainer = window.digiriskdolibarr.risk_table_common.createCategoryImage(
      dolUrlRoot + '/custom/digiriskdolibarr/img/categorieDangers/' + title + '.png',
      categoryMap[title] || 'Catégorie inconnue'
    );

    let checkbox = window.digiriskdolibarr.risk_table_common.createCheckbox('select-risk', 'submit_selected_risks');

    tr.append($('<td>').append(checkbox));
    tr.append($('<td>').append(riskImgContainer));
    tr.append($('<td>').append(cotationInput));
    tr.append($('<td>').append(descInput));
    tr.append($('<td>').append(actionsContainer));

    tbody.append(tr);
  });

  // Gérer le bouton de soumission
  window.digiriskdolibarr.digiai.handleSubmitButton();
};

/**
 * Gère le bouton de soumission des risques sélectionnés
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.digiai.handleSubmitButton = function() {
  let token = window.saturne.toolbox.getToken();
  let dolUrlRoot = $('#dol_url_root').val();

  $('#submit_selected_risks').off('click').on('click', function () {
    const selectedRows = $('input.select-risk:checked').closest('tr');

    if (selectedRows.length === 0) {
      return;
    }

    selectedRows.each(function () {
      const row = $(this);
      const riskData = window.digiriskdolibarr.risk_table_common.extractRiskDataFromRow(row);

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
        success: function() {
          const clonedRow = row.clone();

          clonedRow.find('.select-risk').remove();
          clonedRow.find('textarea, input[type="text"], input[type="number"]').prop('disabled', true);
          clonedRow.find('.risk-evaluation-cotation').each(function () {
            if (!$(this).hasClass('selected-cotation')) {
              $(this).hide();
            }
          });

          $('.risque-table .previous-risks-list').append(clonedRow);
          row.remove();

          // Vérifier s'il reste des risques à sélectionner
          const remainingRows = $('input.select-risk').length;
          if (remainingRows === 0) {
            $('#submit_selected_risks').prop('disabled', true).css('opacity', 0.6);
          }

          window.saturne.loader.remove($('#submit_selected_risks'))
          $('#submit_selected_risks').removeClass('button-disable');
        },
        error: function(xhr, status, error) {
          alert('Erreur lors de l\'ajout d\'un risque : ' + (xhr.responseText || error));
        }
      });
    });
  });
};

/**
 * Réinitialise complètement l'état de la modale DigiAI.
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @param {string} type - Type d'analyse ('image' ou 'text')
 * @return {void}
 */
window.digiriskdolibarr.digiai.resetModal = function (type) {
  $('#digiai_modal').addClass('modal-active');
  $('.modal-analyse-phase').show();
  $('.modal-result-phase').hide();

  if (type === 'image') {
    $('#uploaded-image-preview').attr('src', '').show();
    $('#analyzed-text-preview').hide();
    $('.digiai-loader-text').text('Analyse en cours de l\'image...');
  } else if (type === 'text') {
    $('#uploaded-image-preview').hide();
    $('#analyzed-text-preview').show();
    $('.digiai-loader-text').text('Analyse en cours du texte...');
  }

  $('.analysis-in-progress').show().html(`
    <p class="digiai-loader-text">Analyse en cours...</p>
    <div class="loader"></div>
  `);
  $('#risque_table').hide().find('tbody').empty();
};

