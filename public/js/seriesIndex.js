import {Series} from './Series.js';

window.addEventListener("DOMContentLoaded", () => {
    const globsData = document.querySelector('#globs-data')?.textContent;
    const globs = JSON.parse(globsData);

    new Series(globs);
});

