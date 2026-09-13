/**
 * persian-datepicker is a jQuery plugin that reads `jQuery` and `persianDate`
 * off `window` at evaluation time. ES imports are hoisted, so these globals
 * have to be published from a module that is imported *before* the plugin —
 * assigning them inside app.js would run too late.
 */
import $ from 'jquery';
import persianDate from 'persian-date';

window.$ = window.jQuery = $;
window.persianDate = persianDate;
