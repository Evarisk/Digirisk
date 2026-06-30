
"use strict";
/**
 * Initialise l'objet "tools" ainsi que la méthode "init" obligatoire pour la bibliothèque DigiriskDolibarr.
 *
 * @since   10.3.0
 * @version 10.3.0
 */
window.digiriskdolibarr.tools = {};

/**
 * La méthode appelée automatiquement par la bibliothèque DigiriskDolibarr.
 *
 * @since   10.3.0
 * @version 10.3.0
 *
 * @return {void}
 */
window.digiriskdolibarr.tools.init = function() {
	window.digiriskdolibarr.tools.event();
};

/**
 * La méthode contenant tous les événements pour les tools.
 *
 * @since   10.3.0
 * @version 10.3.0
 *
 * @return {void}
 */
window.digiriskdolibarr.tools.event = function() {
	$(document).on('click', '#data-migration-import-global-dolibarr', window.digiriskdolibarr.tools.submitForm);
};

/**
 * La méthode appelée lors de la soumission du formulaire de migration des données.
 * Elle permet de lancer la migration des données.
 *
 * @since   10.3.0
 * @version 10.3.0
 */
window.digiriskdolibarr.tools.submitForm = function( e ) {
  e.preventDefault();

  let token          = window.saturne.toolbox.getToken();
  let querySeparator = window.saturne.toolbox.getQuerySeparator(window.location.href);

  window.saturne.loader.display($(e.target).parent());

  let fileInput = $(e.target).parent().find('input[type="file"]');
  let formData  = new FormData();

  formData.append('file', fileInput.prop('files')[0]);

  $.ajax({
    url: window.location.href + querySeparator + 'action=import_global_dolibarr' + '&token=' + token,
    type: 'POST',
    data: formData,
    contentType: 'multipart/form-data',
    processData: false,
    success: function(resp) {
      let error = $(resp).find("input[name='error']");
      if (error.length) {
        window.saturne.notice.showNotice('migration-dolibar-notice', error.data('title'), error.val(), 'error');
      } else {
        let success = $(resp).find("input[name='success']");
        window.saturne.notice.showNotice('migration-dolibar-notice', success.data('title'), success.val(), 'success');
      }
      window.saturne.loader.remove($(e.target).parent());
    }
  });
};
