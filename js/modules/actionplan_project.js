/**
 * Action plan — displayed project switcher
 *
 * @memberof digiriskdolibarr
 */
window.digiriskdolibarr.actionplanProject = {};

/**
 * Init — auto-called by saturne.js on document.ready
 */
window.digiriskdolibarr.actionplanProject.init = function() {
    if (!$('.actionplan-project-bar').length) {
        return;
    }
    window.digiriskdolibarr.actionplanProject.event();
};

/**
 * Register delegated events
 */
window.digiriskdolibarr.actionplanProject.event = function() {
    $(document).on('change', '.app-project-form select, .app-project-form input[name="projectid"]', window.digiriskdolibarr.actionplanProject.reload);
};

/**
 * Reload the action plan on the newly selected project — the server stores the
 * choice in a cookie, so the next visits reopen the same project
 *
 * @return {void}
 */
window.digiriskdolibarr.actionplanProject.reload = function() {
    var projectId = $(this).val();
    if (!projectId || parseInt(projectId, 10) <= 0) {
        return;
    }
    $(this).closest('form').trigger('submit');
};
