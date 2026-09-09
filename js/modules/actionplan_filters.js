/**
 * Action plan — GP/UT, risk level and tag filters
 *
 * @memberof digiriskdolibarr
 */
window.digiriskdolibarr.actionplanFilters = {};

/**
 * Init — auto-called by saturne.js on document.ready
 */
window.digiriskdolibarr.actionplanFilters.init = function() {
    if (!$('#actionPlanFilterForm').length) {
        return;
    }
    window.digiriskdolibarr.actionplanFilters.event();
};

/**
 * Register delegated events
 */
window.digiriskdolibarr.actionplanFilters.event = function() {
    $(document).on('change', '#actionPlanFilterForm select, #actionPlanFilterForm input[type="checkbox"]', window.digiriskdolibarr.actionplanFilters.apply);
};

/**
 * Apply the criteria — the board is filtered server side, so every change reloads the page
 *
 * @return {void}
 */
window.digiriskdolibarr.actionplanFilters.apply = function() {
    var $filterForm = $('#actionPlanFilterForm');

    // The chip carries the colour of its tag, keep it in sync until the reload paints the new state
    if ($(this).closest('.apf-tag').length) {
        $(this).closest('.apf-tag').toggleClass('selected', $(this).is(':checked'));
    }

    $filterForm.trigger('submit');
};
