import {EventModule} from "/js/EventModule.js";

window.addEventListener("DOMContentLoaded", () => {

    document.querySelector(".header").setAttribute("style", "background: transparent;");
    const globs = JSON.parse(document.querySelector("#countdown-values").innerText);
    const locale = document.querySelector("html").getAttribute("lang");

    new EventModule(globs, locale);
});
