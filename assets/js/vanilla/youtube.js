import {Youtube} from 'assets/js/YoutubeModule';

window.addEventListener("DOMContentLoaded", () => {
    const globsData = document.querySelector('#global-data')?.textContent;
    const globs = JSON.parse(globsData);

    new Youtube('search', globs);
});
