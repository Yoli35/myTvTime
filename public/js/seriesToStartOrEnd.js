import {AverageColor} from "/js/AverageColor.js";
import {AnimatedHeader} from "./AnimatedHeader.js";

/**
 * @typedef SeriesToEndResponse
 * @type {Object}
 * @property {string} status
 * @property {Array} blocks
 * @property {Array} pages
 */

window.addEventListener("DOMContentLoaded", () => {
    const globsData = document.querySelector('#globs-data')?.textContent;
    const globs = JSON.parse(globsData);
    initHeader('start_or_end', globs);
    setBackgrounds(document.querySelectorAll(".serie"));

    /** @var {HTMLInputElement} */
    const includeUpcomingEpisodesFilter = document.querySelector("#include-upcoming-episodes");
    includeUpcomingEpisodesFilter.addEventListener("change", () => {
        const xhr = new XMLHttpRequest();
        const value = includeUpcomingEpisodesFilter.checked ? 1 : 0;
        const lang = document.querySelector("html").getAttribute("lang");
        xhr.onload = function() {
            /** @var {SeriesToEndResponse} */
            const response = JSON.parse(this.response);
            const wrapper = document.querySelector(".wrapper");
            wrapper.replaceChildren();
            const blocks = response.blocks;
            blocks.forEach(block => {
                const div = document.createElement("div");
                div.innerHTML = block.innerHTML;
                div.setAttribute("data-id", block.id);
                wrapper.appendChild(div);
            });
            setBackgrounds(document.querySelectorAll(".serie"));
            const pages = document.querySelectorAll(".pages");
            pages.forEach(page => {
                page.innerHTML = response.pages;
            });
        };
        xhr.open("GET", "/"+lang+"/series/to-end-settings?iue=" + value);
        xhr.send();
    });
})

function initHeader(from, globs) {
    new AnimatedHeader(from, globs);
}

function setBackgrounds(series) {
    const averageColor = new AverageColor();
    series.forEach(serie => {
        const poster = serie.querySelector(".poster");
        const backdrop = serie.querySelector(".backdrop");
        if (backdrop) {
            const backdropStyle = backdrop?.getAttribute("style");
            const img = poster.querySelector("img");
            const color = averageColor.getColor(img);
            if (color.lightness > 110) {
                serie.classList.add("light");
            } else {
                serie.classList.add("dark");
            }
            serie.setAttribute("style", "background-color: " + "rgb(" + color.r + "," + color.g + "," + color.b + ");");
            backdrop?.setAttribute("style", backdropStyle + "; background-color: " + "rgb(" + color.r + "," + color.g + "," + color.b + ");");
        }
    });
}