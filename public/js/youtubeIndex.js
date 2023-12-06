import {YoutubeModule} from './YoutubeModule.js';

window.addEventListener("DOMContentLoaded", () => {
    const globsData = document.querySelector('#global-data')?.textContent;
    const globs = JSON.parse(globsData);

    new YoutubeModule(globs);
});
