import {Shows} from "./Shows.js";

let toolTips;
window.addEventListener("DOMContentLoaded", () => {
    const globsData = document.querySelector('#globs-data')?.textContent;
    const globs = JSON.parse(globsData);
    // console.log({globs});
    new Shows(globs);
});
