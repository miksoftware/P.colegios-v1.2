import './bootstrap';
import flatpickr from 'flatpickr';
import { Spanish } from 'flatpickr/dist/l10n/es.js';

flatpickr.localize(Spanish);
window.flatpickr = flatpickr;
