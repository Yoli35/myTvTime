import {AnimatedHeader} from "./AnimatedHeader.js";

document.querySelector(".header").setAttribute("style", "background: transparent;");
const inputSearch = document.querySelector("#query");
inputSearch.focus();
inputSearch.select();

new AnimatedHeader();