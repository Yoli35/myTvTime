import {Activity} from "./Activity.js";

// document.querySelector(".header").setAttribute("style", "background: transparent;");
const globs = JSON.parse(document.querySelector("#activity-values").innerText);
new Activity(globs);
