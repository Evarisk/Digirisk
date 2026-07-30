/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    js/modules/ticket_dashboard.js
 * \ingroup digiriskdolibarr
 * \brief   JavaScript ticket management dashboard file for module DigiriskDolibarr
 */

'use strict';

/**
 * Init ticket dashboard JS
 *
 * @memberof DigiriskDolibarr_TicketDashboard
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @type {Object}
 */
window.digiriskdolibarr.ticketDashboard = {};

/**
 * Ticket dashboard init
 *
 * @memberof DigiriskDolibarr_TicketDashboard
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketDashboard.init = function() {
  window.digiriskdolibarr.ticketDashboard.event();
};

/**
 * Ticket dashboard event
 *
 * Bound on the document because the graphs are redrawn by AJAX each time a dashboard filter changes.
 *
 * @memberof DigiriskDolibarr_TicketDashboard
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @return {void}
 */
window.digiriskdolibarr.ticketDashboard.event = function() {
  $(document).on('click', '.dolgraph canvas', window.digiriskdolibarr.ticketDashboard.openTicketList);
  $(document).on('mousemove', '.dolgraph canvas', window.digiriskdolibarr.ticketDashboard.markGraphBarAsClickable);
};

/**
 * Get the options DigiriskDolibarr attached to the graph a canvas belongs to
 *
 * Returns null for every graph without those options, so the graphs of the other dashboards keep their
 * default behaviour.
 *
 * @memberof DigiriskDolibarr_TicketDashboard
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @param  {Element}     canvas Canvas the graph is drawn on
 * @return {Object|null}        Graph options, null when the graph carries none
 */
window.digiriskdolibarr.ticketDashboard.getGraphOptions = function(canvas) {
  let options = $(canvas).closest('[id^="graph-"]').find('.ticket-graph-options').val();

  return options ? JSON.parse(options) : null;
};

/**
 * Get the ticket list URL of the graph bar under the pointer
 *
 * @memberof DigiriskDolibarr_TicketDashboard
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @param  {Element} canvas Canvas the graph is drawn on
 * @param  {Event}   event  Mouse event fired on the canvas
 * @return {string}         Ticket list URL, empty when no linked bar is under the pointer
 */
window.digiriskdolibarr.ticketDashboard.getBarTicketListUrl = function(canvas, event) {
  let options = window.digiriskdolibarr.ticketDashboard.getGraphOptions(canvas);
  if (!options || (!options.links && !options.datasetLinks) || typeof Chart === 'undefined' || !Chart.getChart) {
    return '';
  }

  let chart = Chart.getChart(canvas);
  if (!chart) {
    return '';
  }

  // 'nearest' resolves the single bar under the pointer, so a graph whose series each carry their own filter
  // knows which one was clicked
  let bars = chart.getElementsAtEventForMode(event.originalEvent || event, 'nearest', {intersect: true}, true);
  if (!bars.length) {
    return '';
  }

  let links = options.datasetLinks ? (options.datasetLinks[bars[0].datasetIndex] || []) : options.links;

  return links[bars[0].index] || '';
};

/**
 * Open the ticket list filtered on the clicked graph bar
 *
 * @memberof DigiriskDolibarr_TicketDashboard
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @param  {Event} event Click event fired on the canvas
 * @return {void}
 */
window.digiriskdolibarr.ticketDashboard.openTicketList = function(event) {
  let url = window.digiriskdolibarr.ticketDashboard.getBarTicketListUrl(this, event);
  if (url) {
    window.location.href = url;
  }
};

/**
 * Show the pointer cursor while a linked graph bar is hovered
 *
 * @memberof DigiriskDolibarr_TicketDashboard
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @param  {Event} event Mouse move event fired on the canvas
 * @return {void}
 */
window.digiriskdolibarr.ticketDashboard.markGraphBarAsClickable = function(event) {
  let url = window.digiriskdolibarr.ticketDashboard.getBarTicketListUrl(this, event);
  $(this).toggleClass('graph-bar-clickable', url !== '');
};

/**
 * Move the dataset declared by the graph to a Y axis of its own
 *
 * Two series of different magnitudes on a shared axis flatten the smaller one, and DolGraph draws every
 * dataset against the single default axis. The scales are rewritten before the first render, so the graph is
 * drawn once, already readable.
 *
 * @memberof DigiriskDolibarr_TicketDashboard
 *
 * @since   23.0.0
 * @version 23.0.0
 *
 * @param  {Object} chart Chart.js chart being initialized
 * @return {void}
 */
window.digiriskdolibarr.ticketDashboard.setSecondAxis = function(chart) {
  let options = window.digiriskdolibarr.ticketDashboard.getGraphOptions(chart.canvas);
  if (!options || options.secondAxisDataset === undefined) {
    return;
  }

  let dataset = chart.config.data.datasets[options.secondAxisDataset];
  if (!dataset || !chart.config.options) {
    return;
  }

  let secondAxis = {
    type: 'linear',
    position: 'right',
    beginAtZero: true,
    // The right axis has a scale of its own, its grid lines would cross the ones of the left axis
    grid: {drawOnChartArea: false}
  };
  if (typeof dataset.backgroundColor === 'string') {
    secondAxis.ticks = {color: dataset.backgroundColor};
  }

  let scales = chart.config.options.scales || {};
  scales.y = $.extend({type: 'linear', position: 'left', beginAtZero: true}, scales.y);
  scales.digiriskSecondAxis = secondAxis;

  chart.config.options.scales = scales;
  dataset.yAxisID = 'digiriskSecondAxis';
};

/* Registered as soon as the file is evaluated rather than from init(): DolGraph creates its charts from inline
 * scripts running while the page is parsed, long before the document is ready. The plugin is a no-op on every
 * chart whose graph does not declare a second axis. */
if (typeof Chart !== 'undefined' && Chart.register) {
  Chart.register({
    id: 'digiriskdolibarrTicketDashboard',
    beforeInit: window.digiriskdolibarr.ticketDashboard.setSecondAxis
  });
}
