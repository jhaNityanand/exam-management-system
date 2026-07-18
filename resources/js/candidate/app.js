import { initExamRunner } from './exam-runner';
import { initPrepare } from './prepare';

document.addEventListener('DOMContentLoaded', () => {
    initPrepare(document.getElementById('cx-prepare'));
    initExamRunner(document.getElementById('cx-exam'));
});
