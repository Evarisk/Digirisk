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

      let descInput = $('<textarea>')
        .addClass('form-control')
        .val(description)
        .css({ width: '100%', height: '60px' });

      // let cotationInput = $('<input type="number" min="0" max="100">')
      //   .addClass('form-control')
      //   .val(cotation)
      //   .css({ width: '80px' });

// Déterminer le scale et label à partir de la cotation IA
      const scale = cotation <= 47 ? 1 : cotation <= 50 ? 2 : cotation <= 80 ? 3 : 4;
      const labelMap = {
        1: { value: 0, label: '0-47' },
        2: { value: 48, label: '48-50' },
        3: { value: 51, label: '51-80' },
        4: { value: 100, label: '81-100' }
      };

// Créer la structure de la cotation
      let cotationInput = $(`
        <div class="cotation-container">
          <div class="cotation-standard" style="display: block">
            <span class="title"><i class="fas fa-chart-line"></i> Cotation <required>*</required></span>
            <div class="cotation-listing wpeo-gridlayout grid-4 grid-gap-0"></div>
          </div>
        </div>
      `);

      let cotationListing = cotationInput.find('.cotation-listing');
      let hiddenInput = $('<input type="hidden" name="cotation[]" value="' + cotation + '">');
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

        // Gérer la sélection dynamique
        div.on('click', function () {
          cotationListing.find('.selected-cotation').removeClass('selected-cotation');
          $(this).addClass('selected-cotation');
          hiddenInput.val($(this).data('evaluation-id'));
        });

        cotationListing.append(div);
      });


      let actionsContainer = $('<div>');
      prevention_actions.forEach(action => {
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
            fontSize: '13px'
          });

        actionsContainer.append(textarea);
      });

      let riskImgContainer = $('<img>')
        .addClass('danger-category-pic tooltip wpeo-tooltip-event hover')
        .attr('src', dolUrlRoot + '/custom/digiriskdolibarr/img/categorieDangers/' + title + '.png')
        .attr('aria-label', categoryMap[title] || 'Catégorie inconnue')

      let checkbox = $('<input type="checkbox" class="select-risk">');

      checkbox.on('change', function () {
        const anyChecked = $('.select-risk:checked').length > 0;
        $('#submit_selected_risks').prop('disabled', !anyChecked);
        $('#submit_selected_risks').css('opacity', anyChecked ? 1 : 0.6);
      });

      tr.append($('<td>').append(checkbox));
      tr.append($('<td>').append(riskImgContainer));
      tr.append($('<td>').append(cotationInput));
      tr.append($('<td>').append(descInput));
      tr.append($('<td>').append(actionsContainer));

      $('#submit_selected_risks').off('click').on('click', function () {
        const selectedRows = $('input.select-risk:checked').closest('tr');

        selectedRows.each(function () {
          const row = $(this);
          const description = row.find('textarea').val();
          const cotation = parseInt(row.find('input[name="cotation[]"]').val());
          const actions = [];
          row.find('textarea.action-textarea').each(function () {
            const action = $(this).val().trim();
            if (action !== '') actions.push(action);
          });
          const category = row.data('category');

          $.ajax({
            url: dolUrlRoot + '/custom/digiriskdolibarr/core/ajax/create_risk.php?token=' + token,
            method: 'POST',
            data: JSON.stringify({
              description: description,
              cotation: cotation,
              method: 'simple',
              fk_element: digiriskElementId,
              category: category,
              photo: '',
              tasks: actions,
              dateStart: '', hourStart: '', minStart: '',
              dateEnd: '', hourEnd: '', minEnd: '',
              budget: ''
            }),
            contentType: 'application/json',
            dataType: 'json',
            cache: false,
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            },
            success: function () {
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
              $('#submit_selected_risks').prop('disabled', true).css('opacity', 0.6);
              window.saturne.loader.remove($('#submit_selected_risks'))
              $('#submit_selected_risks').removeClass('button-disable');

            },
            error: function (xhr, status, error) {
              alert('Erreur lors de l\'ajout d\'un risque : ' + (xhr.responseText || error));
            }
          });
        });
      });

      cotationInput.find('.risk-evaluation-cotation').on('click', function () {
        cotationInput.find('.selected-cotation').removeClass('selected-cotation');
        $(this).addClass('selected-cotation');
        hiddenInput.val($(this).data('evaluation-id'));
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
  console.log(categoryMap)

  return categoryMap;
}
