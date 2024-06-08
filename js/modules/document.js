
/**
 * Initialise l'objet "document" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * @since   1.1.0
 * @version 1.1.0
 */
window.digiriskdolibarr.document = {};

/**
 * Initialise le canvas document
 *
 * @since   8.1.2
 * @version 8.1.2
 */
window.digiriskdolibarr.document.canvas;

/**
 * Initialise le bouton document
 *
 * @since   8.1.2
 * @version 8.1.2
 */
window.digiriskdolibarr.document.buttonSignature;

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   8.1.2
 * @version 8.1.2
 *
 * @return {void}
 */
window.digiriskdolibarr.document.init = function() {
  window.digiriskdolibarr.document.event();
};

/**
 * La méthode contenant tous les événements pour les documents.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.document.event = function() {
  $( document ).on( 'click', '#builddoc_generatebutton', window.digiriskdolibarr.document.displayLoader );
  $( document ).on( 'click', '.riskassessmentdocument-generation #builddoc_generatebutton', window.digiriskdolibarr.document.showAdvancementModal );
  $( document ).on( 'click', '.pdf-generation', window.digiriskdolibarr.document.displayLoader );
  $( document ).on( 'click', '.send-risk-assessment-document-by-mail', window.digiriskdolibarr.document.displayLoader );
  $("#progressbar").progressbar({
    value: 0
  });
};


/**
 * Display loader on generation document.
 *
 * @since   1.1.0
 * @version 1.1.0
 *
 * @return {void}
 */
window.digiriskdolibarr.document.displayLoader = function(  ) {
  window.saturne.loader.display($(this).closest('.div-table-responsive-no-min'));
};

window.digiriskdolibarr.document.generateDocument = async function (generationUrl, documentGeneratedText) {
  const token = window.saturne.toolbox.getToken()

  return await $.ajax({
    url : generationUrl,
    type: 'POST',
    data: {
      token: token
    }
  }).done(function(data) {
    $('.wpeo-loader').removeClass('wpeo-loader')
    $('.loader').html('<i class="fas fa-check" style="color: green"></i>')

    const digiriskElementText = $(data).find('.refid').text()
    const digiriskElementRef = digiriskElementText.split(/Description|Projet/)[0].trim();
    const documentName        = $(data).find('#builddoc_form').find('.documentdownload').first().text()
    const textToShow   = documentGeneratedText + ' : ' + digiriskElementRef + ' => ' + documentName

    window.digiriskdolibarr.document.updateModal(textToShow)
    return data
  });
}

window.digiriskdolibarr.document.showAdvancementModal = async function () {
  event.preventDefault()

  const modal = $('#generationModal')
  modal.addClass('modal-active')

  $('.new-document').remove()

  const groupmentUrl = $('#groupmentUrl').val()
  const riskAssessmentDocumentUrl = $('#riskAssessmentDocumentUrl').val()
  const documentGeneratedText = $('#documentGeneratedText').val()

  const digiriskElementIds = $('#digiriskElementIds').val()
  const digiriskElementIdsArray = digiriskElementIds.split(',')

  const totalElements = digiriskElementIdsArray.length + 1;
  let completedElements = 0;

  for (let i = 0; i<digiriskElementIdsArray.length; i++) {
    const id = digiriskElementIdsArray[i]
    if (id > 0) {
      await window.digiriskdolibarr.document.generateDocument(groupmentUrl + '&id=' + id, documentGeneratedText)
      completedElements++;
      const progress = Math.floor((completedElements / totalElements) * 100);
      $("#progressbar .ui-progressbar-value").animate({ width: progress + "%" }, 500);

    }
  }

  const riskassessmentdocumentPage = await window.digiriskdolibarr.document.generateDocument(riskAssessmentDocumentUrl, documentGeneratedText)
  completedElements++;
  const progress = Math.floor((completedElements / totalElements) * 100);
  $("#progressbar .ui-progressbar-value").animate({ width: progress + "%" }, 500);

  setTimeout(() =>  {
    $("#progressbar .ui-progressbar-value").animate({ width: "100%" }, 500);
    $('.wpeo-loader').removeClass('wpeo-loader')
    $('.loader').html('<i class="fas fa-check" style="color: green"></i>')
  }, "1000")

  setTimeout(() => {
    modal.removeClass('modal-active');
    modal.find('.modal-container ul').html('');
    $("#progressbar .ui-progressbar-value").width(0);
  }, "2000");

  setTimeout(() => {
    $('#builddoc_form').html($(riskassessmentdocumentPage).find('#builddoc_form'))

    var elements = $('#builddoc_form').find('.oddeven .minwidth200 a');
    const newDocumentDiv = '<span class="new-document">&nbsp;&nbsp;&nbsp;<i class="fas fa-bolt"></i>  Nouveau !</span>'
    elements.eq(0).append(newDocumentDiv);
    elements.eq(1).append(newDocumentDiv);
  }, "2000");
}

window.digiriskdolibarr.document.updateModal = function (text) {
  var statusList = document.getElementById("generationStatus");
  var newStatus = document.createElement("li");

  newStatus.innerHTML = '<div class="loader"></div>' + text;
  statusList.appendChild(newStatus);

  window.saturne.loader.display($(newStatus).find('.loader').last());
}
