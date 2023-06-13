
let thisGlobal;

export class AnimatedHeader {

    constructor() {
        this.letterRatios = [];
        thisGlobal = this;
        this.initHeader();
    }

    initHeader() {
        let ticking = false,
            letters, animatedH1, index = 0;

        animatedH1 = document.createElement("div");
        animatedH1.classList.add("animated-h1");

        animatedH1 = document.querySelector(".header").insertBefore(animatedH1, document.querySelector(".backdrop"));
        letters = document.querySelector("h1").innerText.split('');

        document.querySelector("h1").innerText = "";

        letters.forEach(letter => {
            let part = document.createElement("div");
            part.classList.add("part");
            if (letter === " ") {
                part.innerHTML = "&nbsp;"
            } else {
                part.innerText = letter;
            }
            animatedH1.appendChild(part);
            this.letterRatios[index] = 2 * (Math.random() - .5);
            index++;
        })
        this.setH1();
        window.addEventListener('resize', this.setH1);

        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(function () {
                    thisGlobal.setH1();
                    ticking = false;
                });
            }
            ticking = true;
        });

    }

    setH1() {
        const header = document.querySelector(".header");
        const h1 = document.querySelector(".animated-h1");
        const parts = h1.querySelectorAll(".part");
        let left, ratio, top, n = 0;

        left = (header.clientWidth - h1.clientWidth) / 2;
        top = ((header.clientHeight + window.scrollY) - h1.clientHeight) / 2;
        ratio = (header.clientHeight - window.scrollY) / header.clientHeight;

        if (ratio > 1) ratio = 1;
        if (ratio < 0) ratio = 0;

        parts.forEach(part => {
            part.setAttribute("style", "transform: rotate(" + (720 * (1 - ratio) * this.letterRatios[n++]) + "deg);");
        })
        h1.setAttribute("style", "left: " + left.toString() + "px; top: " + top.toString() + "px; opacity: " + ratio + "; transform: scale(" + (1 + (5 * (1 - ratio))) + ")");
    }
}