import {YoutubePlaylistModule} from './youtubePlaylistModule.js';

window.addEventListener("DOMContentLoaded", () => {
    const globsData = document.querySelector('#global-data')?.textContent;
    const globs = JSON.parse(globsData);

    new YoutubePlaylistModule(globs);
});
