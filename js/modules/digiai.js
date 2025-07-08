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
  $(document).on('change', '#image_file', window.digiriskdolibarr.digiai.submitForm);
};

/**
 * Méthode pour gérer le formulaire de soumission du fichier image et l'analyse directe par ChatGPT.
 *
 * @since   21.1.0
 * @version 21.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.digiai.submitForm = async function(e) {
  e.preventDefault();

  window.digiriskdolibarr.digiai.resetModal();

  let token = window.saturne.toolbox.getToken();
  let dolUrlRoot = $('#dol_url_root').val();

  let input = document.getElementById('image_file');
  let imageFile = input.files[0];
  if (!imageFile) {
    alert('Veuillez sélectionner une image.');
    return;
  }

  let digiriskElementId = $('#digiriskElementId').val();
  const categoryMap = await loadCategoryMap();

  input.value = '';

  let imageUrl = URL.createObjectURL(imageFile);
  $('#uploaded-image-preview').attr('src', imageUrl).css('opacity', 1);
  $('.analysis-in-progress').show().css('opacity', 1);
  $('.analysis-result').hide();

  $('#digiai_modal').addClass('modal-active');

  try {
    let formData = new FormData();
    formData.append('image_file', imageFile);

    let chatGptResponse = await fetch('backend_endpoint_for_chatgpt.php?token=' + token, {
      method: 'POST',
      body: formData
    });

    if (!chatGptResponse.ok) {
      throw new Error('Failed to fetch ChatGPT with image');
    }

    let chatGptData = await chatGptResponse.json();

    let rawContent = chatGptData.choices[0].message.content.trim();
    let cleanedContent = rawContent.replace(/^```json\s*|```$/g, '').trim();
    let risque = JSON.parse(cleanedContent);

    $('.modal-analyse-phase').fadeOut(400, function () {
      $('.modal-result-phase').fadeIn(400);
    });

    let table = $('#risque_table');
    table.css('display', 'table');
    let tbody = table.find('tbody');
    tbody.empty();
    risque.forEach((risque, index) => {
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

      tbody.append(tr);
    });

  } catch (error) {
    $('.analysis-in-progress').empty().append('Une erreur est survenue : ' + error.message);
  }
};

/**
 * Réinitialise complètement l'état de la modale DigiAI.
 */
window.digiriskdolibarr.digiai.resetModal = function () {
  $('#digiai_modal').addClass('modal-active');
  $('.modal-analyse-phase').show();
  $('.modal-result-phase').hide();
  $('#uploaded-image-preview').attr('src', '').show();
  $('.analysis-in-progress').show().html(`
    <p style="font-size: 1.1em; font-weight: 500;">Analyse de l'image...</p>
    <div class="loader"></div>
  `);
  $('#risque_table').hide().find('tbody').empty();
};

async function loadCategoryMap() {
  const categoryMap = {};
  window.digiriskdolibarr.categoryMap[0].risk.forEach(item => {
    categoryMap[item.thumbnail_name] = item.name;
  });
  window.digiriskdolibarr.categoryMap[0].riskenvironmental.forEach(item => {
    categoryMap[item.thumbnail_name] = item.name;
  });

  return categoryMap;
}
