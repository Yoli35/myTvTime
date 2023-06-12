import {AverageColor} from "/js/AverageColor.js";

let linearGradient = "linear-gradient(90deg, rgb(0,0,0) 0%, rgb(0,0,0) 100%)";
let postersCount = 0;
const averageColor = new AverageColor();

window.addEventListener("DOMContentLoaded", () => {
    const container = document.querySelector(".container");
    if (container.clientWidth < 1200) {
        return;
    }
    const header = document.querySelector(".home-header-bg");
    const div1 = document.createElement("div");

    div1.classList.add("posters");
    header.appendChild(div1);
    postersCount = 1;
    getPosters(div1, 1);

    if (container.clientWidth > 1500) {
        const div2 = document.createElement("div");
        div2.classList.add("posters", "second");
        header.appendChild(div2);
        postersCount = 2;
        getPosters(div2, 2);
    }

    if (container.clientWidth > 1800) {
        const div3 = document.createElement("div");
        div3.classList.add("posters", "third");
        header.appendChild(div3);
        postersCount = 3;
        linearGradient = "linear-gradient(90deg, rgb(0,0,0) 0%, rgb(0,0,0) 50%, rgb(0,0,0) 100%)";
        getPosters(div3, 3);
    }
});

function getPosters(div, n) {
    const xhr = new XMLHttpRequest();
    xhr.onload = function () {
        let data = JSON.parse(this.response);
        // console.log({data});
        cyclePosters(div, data['posters'], n);
    }
    xhr.open("GET", '/get-posters?n=' + n, true);
    xhr.send();
}

function cyclePosters(div, posters, n) {
    let i = 0;
    const img = document.createElement("img");
    img.src = posters[i++];
    div.appendChild(img);
    updateHeaderBackground(img, n);

    setTimeout(() => {
        div.classList.add("fade");
        img.classList.add("end");
        launchCycle(div, posters, i, n);
    }, (n - 1) * 2500);
}

function launchCycle(div, posters, i, n) {
    setInterval(() => {
        const newImg = document.createElement("img");
        const oldImg = div.querySelector("img");

        newImg.src = posters[i++];
        if (oldImg) div.removeChild(oldImg);
        div.appendChild(newImg);
        setTimeout(() => {
            newImg.classList.add("end");
        }, 0);
        if (i >= posters.length) {
            i = 0;
        }
        setTimeout(() => {
            updateHeaderBackground(newImg, n);
        }, 0);
    }, 5000);
}

function updateHeaderBackground(img, n)
{
    const color = averageColor.getColor(img);
    // const color = getAverageColor(img);
    // console.log(color);
    if (color.lightness) {
        const homeHeader = document.querySelector(".home-header");
        const rgb = "rgb(" + color.r + "," + color.g + "," + color.b + ")";
        img.closest(".posters").setAttribute("style", "border-color: " + rgb);
        if (n===1) {
            linearGradient = linearGradient.replace(/rgb\(\d+,\d+,\d+\) 0%/, rgb + " 0%");
            homeHeader.setAttribute("style", "background-image: " + linearGradient);
        }
        if (n===2 && postersCount===3) {
            linearGradient = linearGradient.replace(/rgb\(\d+,\d+,\d+\) 50%/, rgb + " 50%");
            homeHeader.setAttribute("style", "background-image: " + linearGradient);
        }
        if ((n===2 && postersCount===2) || (n===3 && postersCount===3)) {
            linearGradient = linearGradient.replace(/rgb\(\d+,\d+,\d+\) 100%/, rgb + " 100%");
            homeHeader.setAttribute("style", "background-image: " + linearGradient);
        }
    } else {
        img.closest(".posters").setAttribute("style", "border-color: rgb(255,255,255)");
    }
}