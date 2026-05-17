/**
 * Gantt chart module for action plan tasks
 *
 * @memberof digiriskdolibarr
 */
window.digiriskdolibarr.actionplanGantt = {};

/**
 * Init — auto-called by saturne.js on document.ready
 */
window.digiriskdolibarr.actionplanGantt.init = function() {
    window.digiriskdolibarr.actionplanGantt.event();
    window.digiriskdolibarr.actionplanGantt.render();
};

/**
 * Register delegated events
 */
window.digiriskdolibarr.actionplanGantt.event = function() {
    // Tooltip on bar hover
    $(document).on('mouseenter', '.gantt-bar', function() {
        $(this).find('.gantt-tooltip').addClass('visible');
    });
    $(document).on('mouseleave', '.gantt-bar', function() {
        $(this).find('.gantt-tooltip').removeClass('visible');
    });
};

/**
 * Render the Gantt chart from data attributes
 */
window.digiriskdolibarr.actionplanGantt.render = function() {
    var $dataEl = $('#gantt-data');
    if ($dataEl.length === 0) {
        return;
    }

    var tasksData = [];
    try {
        tasksData = JSON.parse($dataEl.text());
    } catch (e) {
        return;
    }

    if (!tasksData || tasksData.length === 0) {
        return;
    }

    // Calculate date range
    var dates = window.digiriskdolibarr.actionplanGantt.calculateDateRange(tasksData);
    if (!dates) {
        return;
    }

    var startDate = dates.start;
    var endDate   = dates.end;
    var totalDays = dates.totalDays;
    var dayWidth  = 30; // pixels per day
    var totalWidth = totalDays * dayWidth;

    // Render timeline header
    window.digiriskdolibarr.actionplanGantt.renderHeader(startDate, endDate, totalDays, dayWidth);

    // Render bars
    window.digiriskdolibarr.actionplanGantt.renderBars(tasksData, startDate, totalDays, dayWidth, totalWidth);
};

/**
 * Calculate the date range from tasks data
 *
 * @param {Array} tasks Task data array
 * @returns {Object|null} { start, end, totalDays }
 */
window.digiriskdolibarr.actionplanGantt.calculateDateRange = function(tasks) {
    var minDate = null;
    var maxDate = null;

    $.each(tasks, function(i, task) {
        if (task.date_start) {
            var d = new Date(task.date_start);
            if (!minDate || d < minDate) {
                minDate = d;
            }
        }
        if (task.date_end) {
            var d2 = new Date(task.date_end);
            if (!maxDate || d2 > maxDate) {
                maxDate = d2;
            }
        }
    });

    // Fallback if no dates
    if (!minDate) {
        minDate = new Date();
    }
    if (!maxDate) {
        maxDate = new Date(minDate.getTime() + 90 * 24 * 60 * 60 * 1000);
    }

    // Add margin (7 days each side)
    minDate = new Date(minDate.getTime() - 7 * 24 * 60 * 60 * 1000);
    maxDate = new Date(maxDate.getTime() + 7 * 24 * 60 * 60 * 1000);

    var totalDays = Math.ceil((maxDate - minDate) / (24 * 60 * 60 * 1000));
    if (totalDays < 30) {
        totalDays = 30;
    }

    return { start: minDate, end: maxDate, totalDays: totalDays };
};

/**
 * Render the timeline header with months and day markers
 *
 * @param {Date}   startDate Start of timeline
 * @param {Date}   endDate   End of timeline
 * @param {number} totalDays Total days in timeline
 * @param {number} dayWidth  Pixel width per day
 */
window.digiriskdolibarr.actionplanGantt.renderHeader = function(startDate, endDate, totalDays, dayWidth) {
    var $header = $('#gantt-timeline-header');
    var totalWidth = totalDays * dayWidth;
    $header.css('width', totalWidth + 'px');

    var html = '<div class="gantt-months" style="width:' + totalWidth + 'px">';
    var months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];

    var current = new Date(startDate);
    while (current <= endDate) {
        var monthStart = new Date(current.getFullYear(), current.getMonth(), 1);
        var monthEnd   = new Date(current.getFullYear(), current.getMonth() + 1, 0);

        var startOffset = Math.max(0, Math.ceil((monthStart - startDate) / (24 * 60 * 60 * 1000)));
        var endOffset   = Math.min(totalDays, Math.ceil((monthEnd - startDate) / (24 * 60 * 60 * 1000)));
        var monthDays   = endOffset - startOffset;

        if (monthDays > 0) {
            html += '<div class="gantt-month" style="left:' + (startOffset * dayWidth) + 'px;width:' + (monthDays * dayWidth) + 'px">';
            html += months[current.getMonth()] + ' ' + current.getFullYear();
            html += '</div>';
        }

        current = new Date(current.getFullYear(), current.getMonth() + 1, 1);
    }
    html += '</div>';

    // Today marker
    var todayOffset = Math.ceil((new Date() - startDate) / (24 * 60 * 60 * 1000));
    if (todayOffset >= 0 && todayOffset <= totalDays) {
        html += '<div class="gantt-today-marker" style="left:' + (todayOffset * dayWidth) + 'px"></div>';
    }

    $header.html(html);
};

/**
 * Render task bars in the timeline
 *
 * @param {Array}  tasks     Task data array
 * @param {Date}   startDate Start of timeline
 * @param {number} totalDays Total days in timeline
 * @param {number} dayWidth  Pixel width per day
 * @param {number} totalWidth Total width in pixels
 */
window.digiriskdolibarr.actionplanGantt.renderBars = function(tasks, startDate, totalDays, dayWidth, totalWidth) {
    var $body = $('#gantt-timeline-body');
    $body.css('width', totalWidth + 'px');

    // Today marker
    var todayOffset = Math.ceil((new Date() - startDate) / (24 * 60 * 60 * 1000));
    if (todayOffset >= 0 && todayOffset <= totalDays) {
        $body.append('<div class="gantt-today-line" style="left:' + (todayOffset * dayWidth) + 'px"></div>');
    }

    // Grid lines (weekly)
    for (var d = 0; d < totalDays; d += 7) {
        $body.find('.gantt-row').each(function() {
            // Grid rendered via CSS background
        });
    }

    $.each(tasks, function(i, task) {
        var $row = $body.find('.gantt-row[data-task-id="' + task.id + '"]');
        $row.css('width', totalWidth + 'px');

        if (!task.date_start && !task.date_end) {
            // No dates, show as a dot at today
            var pos = todayOffset * dayWidth;
            $row.append('<div class="gantt-bar gantt-no-date" style="left:' + pos + 'px;width:20px">' +
                '<div class="gantt-tooltip"><strong>' + task.ref + '</strong><br>Pas de dates définies</div>' +
                '</div>');
            return;
        }

        var taskStart = task.date_start ? new Date(task.date_start) : new Date();
        var taskEnd   = task.date_end ? new Date(task.date_end) : new Date(taskStart.getTime() + 7 * 24 * 60 * 60 * 1000);

        var left  = Math.max(0, Math.ceil((taskStart - startDate) / (24 * 60 * 60 * 1000))) * dayWidth;
        var right = Math.ceil((taskEnd - startDate) / (24 * 60 * 60 * 1000)) * dayWidth;
        var width = Math.max(right - left, dayWidth); // At least 1 day width

        var colorClass = 'gantt-bar-red';
        if (task.progress >= 100) {
            colorClass = 'gantt-bar-green';
        } else if (task.progress > 0) {
            colorClass = 'gantt-bar-yellow';
        }

        var progressWidth = Math.round(width * task.progress / 100);

        var barHtml = '<div class="gantt-bar ' + colorClass + '" style="left:' + left + 'px;width:' + width + 'px">';
        barHtml += '<div class="gantt-bar-progress" style="width:' + progressWidth + 'px"></div>';
        barHtml += '<span class="gantt-bar-label">' + task.progress + '%</span>';
        barHtml += '<div class="gantt-tooltip">';
        barHtml += '<strong>' + task.ref + '</strong> — ' + task.label + '<br>';
        if (task.date_start) {
            barHtml += 'Début : ' + task.date_start + '<br>';
        }
        if (task.date_end) {
            barHtml += 'Échéance : ' + task.date_end + '<br>';
        }
        barHtml += 'Avancement : ' + task.progress + '%';
        if (task.risk_ref) {
            barHtml += '<br>Risque : ' + task.risk_ref;
        }
        barHtml += '</div>';
        barHtml += '</div>';

        $row.append(barHtml);
    });
};
