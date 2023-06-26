import {AverageColor} from "/js/AverageColor.js";
import {AnimatedHeader} from "./AnimatedHeader.js";

window.addEventListener("DOMContentLoaded", () => {
    const globsData = document.querySelector('#globs-data')?.textContent;
    const globs = JSON.parse(globsData);
    initHeader('start_or_end', globs);
    setBackgrounds(document.querySelectorAll(".serie"));
})

function initHeader(from, globs) {
    new AnimatedHeader(from, globs);
}

function setBackgrounds(series) {
    const averageColor = new AverageColor();
    series.forEach(serie => {
        const poster = serie.querySelector(".poster");
        const backdrop = serie.querySelector(".backdrop");
        const backdropStyle = backdrop.getAttribute("style");
        const img = poster.querySelector("img");
        const color = averageColor.getColor(img);
        if (color.lightness > 127) {
            serie.classList.add("light");
        } else {
            serie.classList.add("dark");
        }
        serie.setAttribute("style", "background-color: " + "rgb(" + color.r + "," + color.g + "," + color.b + ")" + ";");
        backdrop.setAttribute("style", backdropStyle + "; background-color: " + "rgb(" + color.r + "," + color.g + "," + color.b + ")" + ";");
    });
}