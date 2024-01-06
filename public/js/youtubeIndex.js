import {YoutubeIndexModule} from './youtubeIndexModule.js';

window.addEventListener("DOMContentLoaded", () => {
    const globsData = document.querySelector('#global-data')?.textContent;
    const globs = JSON.parse(globsData);

    new YoutubeIndexModule(globs);
});
