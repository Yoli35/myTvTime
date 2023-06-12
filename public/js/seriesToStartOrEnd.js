import {AverageColor} from "/js/AverageColor.js";

window.addEventListener("DOMContentLoaded", () => {
    initHeader();
    setBackgrounds(document.querySelectorAll(".serie"));
})

function initHeader() {
    const header = document.querySelector(".header");
    let ticking = false;
    setH1();
    window.addEventListener('resize', setH1);

    window.addEventListener('scroll', () => {
        if (!ticking) {
            window.requestAnimationFrame(function () {
                setH1();
                ticking = false;
            });
        }
        ticking = true;
    });

    setTimeout(() => header.classList.add("fade-bg"), 3000);
}

function setH1() {
    const header = document.querySelector(".header");
    const h1 = document.querySelector("h1");
    let left, ratio, top;
    ratio = (header.clientHeight - window.scrollY) / header.clientHeight;
    left = (header.clientWidth - h1.clientWidth) / 2;
    top = ((header.clientHeight + window.scrollY) - h1.clientHeight) / 2;
    if (ratio < 0) ratio = 0;
    h1.setAttribute("style", "left: " + left.toString() + "px; top: " + top.toString() + "px; opacity: " + ratio + "; transform: scale(" + ratio + ")");
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