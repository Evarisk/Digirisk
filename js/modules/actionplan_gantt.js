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
    window.digiriskdolibarr.actionplanGantt.maybeAutoExport();
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
 * Turn the colour of a column into the translucent background of a bar
 *
 * @param  {string} color Hexadecimal colour of the column, empty when the column carries none
 * @return {string}       rgba() colour, empty string when the colour cannot be read
 */
window.digiriskdolibarr.actionplanGantt.softColor = function(color) {
    if (!/^#[0-9a-f]{6}$/i.test(color || '')) {
        return '';
    }

    var red   = parseInt(color.substr(1, 2), 16);
    var green = parseInt(color.substr(3, 2), 16);
    var blue  = parseInt(color.substr(5, 2), 16);

    return 'rgba(' + red + ', ' + green + ', ' + blue + ', 0.25)';
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

        // Colour of the column the progress falls in, so the Gantt follows the configured scale
        var softColor  = window.digiriskdolibarr.actionplanGantt.softColor(task.color);
        var colorClass = '';
        var barStyle   = 'left:' + left + 'px;width:' + width + 'px';

        if (softColor) {
            barStyle += ';background:' + softColor + ';border:1px solid ' + task.color;
        } else {
            // No colour on the column (or an unreadable one): keep the historical three-tone bars
            colorClass = task.progress >= 100 ? 'gantt-bar-green' : (task.progress > 0 ? 'gantt-bar-yellow' : 'gantt-bar-red');
        }

        var progressWidth = Math.round(width * task.progress / 100);
        var progressStyle = 'width:' + progressWidth + 'px' + (softColor ? ';background:' + task.color : '');

        var barHtml = '<div class="gantt-bar ' + colorClass + '" style="' + barStyle + '">';
        barHtml += '<div class="gantt-bar-progress" style="' + progressStyle + '"></div>';
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

/**
 * Trigger a PNG export of the Gantt chart when the page was opened with ?export=png
 * (the toolbar "Gantt" button links to the Gantt view with that flag)
 */
window.digiriskdolibarr.actionplanGantt.maybeAutoExport = function() {
    var $container = $('.gantt-container');
    if ($container.data('autoexport') !== 'png' || $('.gantt-chart').length === 0) {
        return;
    }

    // Drop the export flag from the URL so a manual refresh does not re-export
    if (window.history && window.history.replaceState) {
        var search   = window.location.search.replace(/([&?])export=png(&|$)/, '$1').replace(/[&?]$/, '');
        var cleanUrl = window.location.pathname + search + window.location.hash;
        window.history.replaceState({}, document.title, cleanUrl);
    }

    if (typeof window.html2canvas === 'function') {
        window.digiriskdolibarr.actionplanGantt.exportToPng();
        return;
    }

    var libUrl = $container.data('html2canvas-url');
    if (libUrl) {
        $.getScript(libUrl).done(function() {
            window.digiriskdolibarr.actionplanGantt.exportToPng();
        });
    }
};

/**
 * Capture the full Gantt chart (including the horizontally scrolled timeline) and download it as PNG
 */
window.digiriskdolibarr.actionplanGantt.exportToPng = function() {
    var chart = $('.gantt-chart').get(0);
    if (typeof window.html2canvas !== 'function' || !chart) {
        return;
    }

    var $wrapper         = $('.gantt-timeline-wrapper');
    var body             = $('.gantt-timeline-body').get(0);
    var originalOverflow = $wrapper.css('overflow');
    var originalWidth    = $wrapper.css('width');

    // Expand the scrollable timeline so the whole chart fits in the capture
    $wrapper.css('overflow', 'visible');
    if (body && body.scrollWidth > 0) {
        $wrapper.css('width', body.scrollWidth + 'px');
    }

    var restore = function() {
        $wrapper.css('overflow', originalOverflow);
        $wrapper.css('width', originalWidth);
    };

    window.html2canvas(chart, {
        backgroundColor: '#ffffff',
        scale:           1,
        useCORS:         true,
        windowWidth:     chart.scrollWidth,
        windowHeight:    chart.scrollHeight
    }).then(function(canvas) {
        restore();
        var link      = document.createElement('a');
        link.download = 'papripact_gantt.png';
        link.href     = canvas.toDataURL('image/png');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }).catch(function() {
        restore();
    });
};
