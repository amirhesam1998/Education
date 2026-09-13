/**
 * Jalali date picker bundle — admin panel only.
 *
 * Kept out of app.js on purpose: the public reservation pages and the login
 * screen also load app.js, and none of them use a date picker. Bundling
 * jQuery + persian-date + persian-datepicker there would put ~180 KB of
 * unused JavaScript on a student's phone.
 *
 * persian-datepicker is a jQuery plugin that reads `jQuery` and `persianDate`
 * off `window` when it evaluates. ES imports are hoisted, so those globals are
 * published from a separate module imported ahead of the plugin.
 */
import './jalali-globals';

import 'persian-datepicker/dist/js/persian-datepicker.min.js';
import 'persian-datepicker/dist/css/persian-datepicker.min.css';
