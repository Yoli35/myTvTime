import {TvFilterModule} from "./TvFilterModule.js";

const globs = document.querySelector(".global-data").textContent;
new TvFilterModule(JSON.parse(globs));