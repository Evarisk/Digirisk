/**
 * Action plan — dashboard view: tooltip of the due dates chart
 *
 * @memberof digiriskdolibarr
 */
window.digiriskdolibarr.actionplanDashboard = {};

/**
 * Init — auto-called by saturne.js on document.ready
 */
window.digiriskdolibarr.actionplanDashboard.init = function() {
    if (!$('.actionplan-dashboard').length) {
        return;
    }
    window.digiriskdolibarr.actionplanDashboard.event();
};

/**
 * Register delegated events
 */
window.digiriskdolibarr.actionplanDashboard.event = function() {
    $(document).on('mouseenter', '.apd-chart__hit', window.digiriskdolibarr.actionplanDashboard.showTooltip);
    $(document).on('mouseleave', '.apd-chart__hit', window.digiriskdolibarr.actionplanDashboard.hideTooltip);
};

/**
 * Show the finished, on time and late actions due in the hovered month
 *
 * Each count comes as "label|count" in a data attribute of the month, already translated by the page.
 *
 * @return {void}
 */
window.digiriskdolibarr.actionplanDashboard.showTooltip = function() {
    var $hit     = $(this);
    var $plot    = $hit.closest('.apd-chart__plot');
    var $tooltip = $plot.find('.apd-chart__tooltip');
    var nbMonth  = $plot.find('.apd-chart__hit').length;
    var index    = parseInt($hit.data('index'), 10);

    $plot.find('.apd-chart__hit').removeClass('is-hovered');
    $hit.addClass('is-hovered');

    $tooltip.empty().append($('<strong>').text($hit.data('title')));
    ['done', 'todo', 'late'].forEach(function(state) {
        var parts = String($hit.data(state)).split('|');
        $tooltip.append($('<span class="apd-chart__tooltip-row">')
            .append($('<span class="apd-chart__tooltip-key">').addClass('apd-chart__tooltip-key--' + state).text(parts[0]))
            .append($('<span>').text(parts[1])));
    });

    // The tooltip sits over the middle of its month, kept inside the plot at both ends
    var plotWidth = $plot.width();
    var center    = (index + 0.5) / nbMonth * plotWidth;
    var half      = $tooltip.outerWidth() / 2 || 80;
    $tooltip.css('left', Math.min(Math.max(center, half), plotWidth - half) + 'px');
    $tooltip.prop('hidden', false);
};

/**
 * Hide the tooltip of the due dates chart
 *
 * @return {void}
 */
window.digiriskdolibarr.actionplanDashboard.hideTooltip = function() {
    var $plot = $(this).closest('.apd-chart__plot');

    $(this).removeClass('is-hovered');
    $plot.find('.apd-chart__tooltip').prop('hidden', true);
};
