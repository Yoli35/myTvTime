import {Event} from "/js/EventModule.js";

let countdownValues, locale;

window.addEventListener("DOMContentLoaded", () => {

    document.querySelector(".header").setAttribute("style", "background: transparent;");

    new Event(countdownValues, locale);
});

function initEvents(values, loc) {
    countdownValues = values;
    locale = loc;
}
