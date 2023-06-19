import {AnimatedHeader} from "./AnimatedHeader.js";

document.querySelector(".header").setAttribute("style", "background: transparent;");
const inputSearch = document.querySelector("#query");
inputSearch.focus();
inputSearch.select();

const submitSearch = document.querySelector("#submit");
submitSearch.addEventListener("click", submit);
inputSearch.addEventListener("keyup", function(event){
    if(event.keyCode === 13){
        event.preventDefault();
        submit();
    }
});
new AnimatedHeader();

function submit(){
    const dbSearch = document.querySelector("#db").checked ? 1:0;
    const urlSearch = document.querySelector("#url").value;
    const query = inputSearch.value;
    window.location.href = `${urlSearch}?query=${query}&page=1&db=${dbSearch}`;
}