import {Youtube} from './youtubeModule.js';

window.addEventListener("DOMContentLoaded", () => {
    const globsData = document.querySelector('#global-data')?.textContent;
    const globs = JSON.parse(globsData);

    new Youtube('search', globs);
});
