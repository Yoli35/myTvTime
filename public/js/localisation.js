import {LocalisationModule} from "./localisationModule.js";

const globs = document.querySelector(".global-data").textContent;
new LocalisationModule(JSON.parse(globs));