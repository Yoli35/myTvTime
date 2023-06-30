let thisGlobal;

export class AnimatedHeader {

    /**
     * @param {string} from
     * @param {object} globs
     * @param {string[]} globs.posters
     * @param {string} globs.posterPath
     */
    constructor(from = null, globs = null) {
        this.letterRatios = [];
        this.posters = globs?.posters;
        this.posterPath = globs?.posterPath;
        thisGlobal = this;
        this.initHeader(from);
        if (this.posters) this.initPosters();
    }
    // rowCount = 0;
    // intervalId = null;

    initHeader(from) {
        let ticking = false,
            letters, animatedH1, index = 0;

        letters = document.querySelector("h1").innerText.split('');
        document.querySelector("h1").innerText = "";

        animatedH1 = document.createElement("div");
        animatedH1.classList.add("animated-h1");
        animatedH1 = document.querySelector(".header").insertBefore(animatedH1, document.querySelector(".backdrop"));

        letters.forEach(letter => {
            let part = document.createElement("div");
            part.classList.add("part");
            if (letter === " ") {
                part.innerHTML = "&nbsp;"
                part.classList.add("space");
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

        if (from === 'search') {

        }
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
            part.setAttribute("style", "transform: rotate(" + (720 * (1 - ratio) * thisGlobal.letterRatios[n++]) + "deg);");
        })
        h1.setAttribute("style", "left: " + left.toString() + "px; top: " + top.toString() + "px; opacity: " + ratio + "; transform: scale(" + (1 + (5 * (1 - ratio))) + ")");
    }

    initPosters() {
        const header = document.querySelector(".header");
        setTimeout(() => {
            header.classList.add("fade-bg");
        }, 300);
        const animatedH1 = document.querySelector(".animated-h1");
        const backdrop = document.querySelector(".backdrop");
        animatedH1.classList.add("flat-color");
        backdrop.classList.add("flat-color");

        const h1 = document.querySelector("h1");
        const posters = document.createElement("div");
        posters.classList.add("posters");
        h1.replaceWith(posters);

        this.generatePosters();
        window.addEventListener('resize', this.generatePosters);
        window.addEventListener("resize", this.setOffset);
    }

    generatePosters() {
        const header = document.querySelector(".header");
        const posters = header.querySelector(".posters");
        const headerWidth = header.clientWidth;
        const headerHeight = header.clientHeight;
        const posterHeight = headerHeight / 2;
        const posterWidth = posterHeight * 2 / 3;
        const rowCount = Math.ceil(headerWidth / posterWidth);

        if (rowCount === thisGlobal.rowCount) return;
        thisGlobal.rowCount = rowCount;

        // la première fois, on ne veut pas clear interval
        if (thisGlobal.intervalId) clearInterval(thisGlobal.intervalId);

        const rowWidth = rowCount * posterWidth;
        const posterCount = rowCount * 2;
        const offsetX = (headerWidth - rowWidth) / 2;

        posters.innerHTML = "";

        for (let i = 0; i < posterCount; i++) {
            const poster = document.createElement("div");
            // const counter = document.createElement("div");
            poster.classList.add("changing-poster");
            poster.setAttribute("data-index", i);
            poster.setAttribute("style", "left: " + (offsetX + ((i % rowCount) * posterWidth)) + "px; top: " + ((i < rowCount) ? 0 : posterHeight) + "px;");
            const img = document.createElement("img");
            img.setAttribute("src", thisGlobal.posterPath + thisGlobal.posters[Math.floor(Math.random() * thisGlobal.posters.length)]);
            poster.appendChild(img);
            // counter.classList.add("counter");
            // counter.innerText = "0";
            // poster.appendChild(counter);
            posters.appendChild(poster);
        }

        thisGlobal.intervalId = setInterval(thisGlobal.changePoster, 1000);
    }

    changePoster() {
        const posterCount = thisGlobal.rowCount * 2;
        const poster = document.querySelector(".changing-poster[data-index='" + Math.floor(Math.random() * posterCount) + "']");
        const img = poster.querySelector("img");
        // const counter = poster.querySelector(".counter");
        setTimeout(()=> {
            poster.classList.add("flap");
        }, 0);
        setTimeout(()=> {
            // counter.innerText = parseInt(counter.innerText) + 1;
            img.setAttribute("src", thisGlobal.posterPath + thisGlobal.posters[Math.floor(Math.random() * thisGlobal.posters.length)]);
            setTimeout(()=> {
                poster.classList.remove("flap");
            }, 100);
        }, 450);
    }

    setOffset() {
        const header = document.querySelector(".header");
        const posters = header.querySelectorAll(".changing-poster");
        const headerWidth = header.clientWidth;
        const headerHeight = header.clientHeight;
        const posterHeight = headerHeight / 2;
        const posterWidth = posterHeight * 2 / 3;
        const rowCount = Math.ceil(headerWidth / posterWidth);
        const rowWidth = rowCount * posterWidth;
        const posterCount = rowCount * 2;
        const offsetX = (headerWidth - rowWidth) / 2;

        for (let i = 0; i < posterCount; i++) {
            const poster = posters[i];
            poster.setAttribute("style", "left: " + (offsetX + ((i % rowCount) * posterWidth)) + "px; top: " + ((i < rowCount) ? 0 : posterHeight) + "px;");
        }
    }
}
