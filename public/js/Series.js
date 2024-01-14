import {AverageColor} from '/js/AverageColor.js';
import {AnimatedHeader} from "./AnimatedHeader.js";

let thisGlobal;

export class Series {
    constructor(globs) {

        thisGlobal = this;

        this.app_series_index = globs.app_series_index;
        this.app_series_new = globs.app_series_new;
        this.app_series_show = globs.app_series_show;
        this.app_series_search = globs.app_series_search;
        this.app_series_history = globs.app_series_history;
        this.app_series_set_settings = globs.app_series_set_settings;
        this.app_series_from_country = globs.app_series_from_country;
        this.locale = globs.locale;
        this.current_page = globs.current_page;
        this.per_page = globs.per_page;
        this.order_by = globs.order_by;
        this.order = globs.order;
        this.from = globs.from;
        this.leaf_settings = globs.leaf_settings;
        this.series_list = globs.series_list;
        this.leaf_particules = [];

        this.slideDuration = 6000;
        this.translateDuration = 3000;
        this.removeDelay = 100;
        this.actionDelay = 10;

        this.trans = {
            "added": {
                "fr": "Nouvelle série ajoutée",
                "en": "New series added",
                "de": "Neue Serie hinzugefügt",
                "es": "Nueva serie agregada"
            },
            "updated": {
                "fr": "Série mise à jour",
                "en": "Serie updated",
                "de": "Serie aktualisiert",
                "es": "Serie actualizada"
            },
            "not found": {
                "fr": "Série introuvable",
                "en": "Serie not found",
                "de": "Serie nicht gefunden",
                "es": "Serie no encontrada"
            },
        }

        this.init();
    }

    init() {
        this.checkHeight();

        const tools = document.querySelector(".series-tools");
        if (tools) {
            this.initHistory();
            this.initSettings();
            this.initPreview();
            this.newSerie();

            this.autocomplete(document.querySelector("#search-name"), this.series_list);

            document.querySelector("#search-tmdb-series").addEventListener("click", () => {
                this.searchSerie();
            });
            document.querySelector("#search-tmdb-name").addEventListener("keyup", (e) => {
                if (e.key === "Enter") {
                    this.searchSerie();
                }
            });
            /** @type HTMLSelectElement chooseOriginCountry */
            const chooseOriginCountry = document.querySelector("#choose-origin-country");
            chooseOriginCountry.addEventListener("change", () => {
                const choice = chooseOriginCountry.value;
                window.location.href = thisGlobal.app_series_from_country + '/' + choice;
            });
        }
        new AnimatedHeader();
        setTimeout(() => document.querySelector('.header').classList.add("fade-bg"), 3000);
        setTimeout(this.clearQuote, 3000);
        setTimeout(this.backdropSlide, 4000);
        this.initAnimation();
        this.setBackgrounds(document.querySelectorAll(".serie"));
    }

    searchSerie() {
        let query = document.querySelector("#search-tmdb-name").value;
        if (query.length > 0) {
            window.location.href = this.app_series_search + "?query=" + query;
        }
    }

    checkHeight() {
        const container = document.querySelector(".my-series");
        if (container.clientHeight < 500) {
            window.location.reload();
        }
    }

    clearQuote() {
        const header = document.querySelector(".header");
        const quotes = header.querySelectorAll(".quote");

        quotes.forEach(quote => setTimeout(() => quote.classList.add("hidden"), 0));
        setTimeout(() => quotes.forEach(quote => header.removeChild(quote)), 500);
    }

    setBackgrounds(series) {
        let averageColor = new AverageColor();
        series.forEach(serie => {
            const poster = serie.querySelector(".poster");
            const backdrop = serie.querySelector(".backdrop");
            if (backdrop) {
                const backdropStyle = backdrop.getAttribute("style");
                const img = poster.querySelector("img");
                const color = averageColor.getColor(img);
                if (color.lightness > 150) {
                    serie.classList.add("light");
                } else {
                    serie.classList.add("dark");
                }
                backdrop.setAttribute("style", backdropStyle + "; background-color: " + "rgb(" + color.r + "," + color.g + "," + color.b + ")" + ";");
                serie.setAttribute("style", "background-color: " + "rgb(" + color.r + "," + color.g + "," + color.b + ")" + ";");
            }
        });
        averageColor = null;
    }

    backdropSlide() {
        const header = document.querySelector(".header");
        let indicatorDiv, indicatorsDiv, left, right, modulo, link;

        thisGlobal.series = thisGlobal.getBackdropsAndNamesAndLinks();

        if (!thisGlobal.series.length) return;

        thisGlobal.slideIndex = 0;
        modulo = thisGlobal.series.length;

        left = document.createElement("div");
        left.classList.add("left-arrow");
        left.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
        left.addEventListener("click", thisGlobal.previousSlide);
        header.appendChild(left);
        right = document.createElement("div");
        right.classList.add("right-arrow");
        right.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
        right.addEventListener("click", thisGlobal.nextSlide);
        header.appendChild(right);

        indicatorsDiv = document.createElement("div");
        indicatorsDiv.classList.add("indicators");
        for (let i = 0; i < modulo; i++) {
            indicatorDiv = document.createElement("div");
            indicatorDiv.classList.add("indicator");
            indicatorDiv.setAttribute("data-index", i.toString());
            indicatorDiv.addEventListener("click", thisGlobal.gotoSlide);
            indicatorsDiv.appendChild(indicatorDiv);
        }
        indicatorsDiv.setAttribute("style", "left: " + ((header.clientWidth - (20 * modulo)) / 2) + "px");
        header.appendChild(indicatorsDiv);

        link = document.createElement("a");
        link.classList.add("link");
        header.insertBefore(link, header.querySelector(".left-arrow"));

        header.addEventListener("mouseenter", thisGlobal.stopSlide);
        header.addEventListener("mouseleave", thisGlobal.startSlide);

        // document.addEventListener("visibilitychange", () => {
        //     if (document.visibilityState === 'visible') {
        //         thisGlobal.startSlide();
        //     } else {
        //         thisGlobal.stopSlide();
        //     }
        // });

        document.addEventListener("visibilitychange", () => (document.visibilityState === 'visible') ? thisGlobal.startSlide() : thisGlobal.stopSlide());

        thisGlobal.slideInterval = setInterval(thisGlobal.slideFunc, thisGlobal.slideDuration);
    }

    slideFunc() {
        const header = document.querySelector(".header");
        const modulo = thisGlobal.series.length;
        const slideIndex = thisGlobal.slideIndex;
        let filename, nameDiv, name, newBackdrop, newLink, href;

        filename = thisGlobal.series[slideIndex].backdrop;
        name = thisGlobal.series[slideIndex].name;
        href = thisGlobal.series[slideIndex].link;

        newBackdrop = document.createElement("div");
        newBackdrop.classList.add("backdrop", "right");
        newBackdrop.setAttribute("style", "background-image: url('" + filename + "')");
        nameDiv = document.createElement("div");
        nameDiv.appendChild(document.createTextNode(name));
        nameDiv.classList.add("name");
        newBackdrop.appendChild(nameDiv);
        header.appendChild(newBackdrop);

        newLink = document.createElement("a");
        newLink.classList.add("link", "right");
        newLink.setAttribute("href", href);
        header.insertBefore(newLink, header.querySelector(".left-arrow"));

        setTimeout(() => {
            const backdrop = header.querySelector(".backdrop");
            const newBackdrop = header.querySelector(".backdrop.right");
            const link = header.querySelector(".link");
            const newLink = header.querySelector(".link.right");

            backdrop.classList.add("left", "to-be-deleted");
            newBackdrop?.classList.remove("right");
            link.classList.add("left", "to-be-deleted");
            newLink.classList.remove("right");
            thisGlobal.indicators(header, slideIndex, modulo);
        }, thisGlobal.actionDelay);

        thisGlobal.slideIndex = (slideIndex + 1) % modulo;

        setTimeout(() => {
            let leftBackdrop = header.querySelector(".backdrop.left");
            let leftLink = header.querySelector(".link.left");
            if (leftBackdrop) {
                header.removeChild(leftBackdrop);
            }
            if (leftLink) {
                header.removeChild(leftLink);
            }
            setTimeout(() => {
                while (header.querySelectorAll(".to-be-deleted").length) {
                    const node = header.querySelector(".to-be-deleted");
                    header.removeChild(node);
                }
            }, thisGlobal.removeDelay);
        }, thisGlobal.translateDuration + thisGlobal.removeDelay);
    }

    gotoSlide(evt) {
        const target = parseInt(evt.target.getAttribute("data-index"));
        const modulo = thisGlobal.series.length;

        if ((target + 1) % modulo === thisGlobal.slideIndex) return;

        thisGlobal.slideIndex = target;
        thisGlobal.setSlide();
        thisGlobal.slideIndex = (thisGlobal.slideIndex + 1) % modulo;
    }

    nextSlide() {
        const modulo = thisGlobal.series.length;

        thisGlobal.setSlide();
        thisGlobal.slideIndex = (thisGlobal.slideIndex + 1) % modulo;
    }

    previousSlide() {
        const modulo = thisGlobal.series.length;

        thisGlobal.slideIndex -= 2;
        if (thisGlobal.slideIndex < 0) thisGlobal.slideIndex = modulo + thisGlobal.slideIndex;
        thisGlobal.setSlide();
        thisGlobal.slideIndex = (thisGlobal.slideIndex + 1) % modulo;
    }

    setSlide() {
        const header = document.querySelector(".header");
        const modulo = thisGlobal.series.length;
        let filename, name, backdrop, nameDiv, link, href;

        let leftBackdrop = document.querySelector(".backdrop.left");
        if (leftBackdrop) {
            header.removeChild(leftBackdrop);
        }
        filename = thisGlobal.series[thisGlobal.slideIndex].backdrop;
        name = thisGlobal.series[thisGlobal.slideIndex].name;
        href = thisGlobal.series[thisGlobal.slideIndex].link;
        backdrop = header.querySelector(".backdrop");
        backdrop.setAttribute("style", "background-image: url('" + filename + "')");
        nameDiv = backdrop.querySelector(".name");
        link = header.querySelector(".link");
        // avant le premier défilement, il n'y a pas de div.name ni de lien
        if (nameDiv) {
            nameDiv.removeChild(nameDiv.firstChild);
            nameDiv.appendChild(document.createTextNode(name));
            link.setAttribute("href", href);
        } else {
            nameDiv = document.createElement("div");
            nameDiv.appendChild(document.createTextNode(name));
            nameDiv.classList.add("name");
            backdrop.appendChild(nameDiv);
            link = document.createElement("a");
            link.classList.add("link");
            link.setAttribute("href", href);
            header.insertBefore(link, header.querySelector(".left-arrow"));
        }
        thisGlobal.indicators(header, thisGlobal.slideIndex + 1, modulo);
    }

    stopSlide() {
        clearInterval(thisGlobal.slideInterval);
    }

    startSlide() {
        thisGlobal.slideInterval = setInterval(thisGlobal.slideFunc, thisGlobal.slideDuration);
    }

    indicators(header, idx, count) {
        const indicators = header.querySelectorAll(".indicator");
        // idx--;
        for (let i = 0; i < count; i++) {
            indicators[i].classList.remove("active");
        }
        if (idx >= 0 && idx < count) {
            indicators[idx].classList.add("active");
        } else {
            indicators[count - 1].classList.add("active");
        }
    }

    getBackdropsAndNamesAndLinks() {
        const wrapper = document.querySelector(".wrapper");
        if (!wrapper) return [];

        const divs = wrapper.querySelectorAll(".serie");
        let tab = [];
        divs.forEach(div => {
            let backdrop = div.querySelector(".backdrop");
            let name = div.querySelector(".infos").firstElementChild.innerHTML;
            let link = div.closest("a").getAttribute("href");
            if (backdrop)
                tab.push({backdrop: backdrop.style.backgroundImage.match(/url\(["']?([^"']*)["']?\)/)[1], name: name, link: link});
        });
        return tab;
    }

    initAnimation() {
        const animations = document.querySelectorAll(".animation");

        if (!animations.length) return;

        animations.forEach(animation => {
            const sliders = animation.querySelectorAll(".slider");

            sliders.forEach(slider => {
                const label = slider.querySelector(".label");
                const control = slider.querySelector(".control");
                const width = control.offsetWidth;
                let values, value;
                switch (control.getAttribute("data-type")) {
                    case 'range':
                        label.appendChild(this.newRangeValue(control));
                        control.appendChild(this.newRange(control));
                        break;
                    case 'interval':
                        let min, max, minV, maxV, offset1, offset2;
                        min = control.getAttribute("data-min");
                        max = control.getAttribute("data-max");
                        minV = control.getAttribute("data-min-value");
                        maxV = control.getAttribute("data-max-value");

                        let data = this.getIntervalValuesFromSettings(control.getAttribute("data-name"));
                        if (data.length) {
                            minV = data[0];
                            maxV = data[1];
                            control.setAttribute("data-min-value", minV);
                            control.setAttribute("data-max-value", maxV);
                        }

                        offset1 = width * ((minV - min) / (max - min));
                        offset2 = width * ((maxV - min) / (max - min));
                        values = document.createElement("div");
                        values.classList.add("values");
                        value = document.createElement("input");
                        value.setAttribute("type", "number");
                        value.classList.add("value", "min");
                        value.value = minV;
                        value.setAttribute("min", min);
                        value.setAttribute("max", max);
                        values.appendChild(value);
                        value = document.createElement("input");
                        value.setAttribute("type", "number");
                        value.classList.add("value", "max");
                        value.value = maxV;
                        value.setAttribute("min", min);
                        value.setAttribute("max", max);
                        values.appendChild(value);
                        label.appendChild(values);
                        const interval = document.createElement("div");
                        interval.classList.add("interval");
                        const bar = document.createElement("div");
                        bar.classList.add("bar");
                        const between = document.createElement("div");
                        between.classList.add("between");
                        between.setAttribute("style", "left: " + (offset1 + 8).toString() + "px; width: " + (offset2 - offset1).toString() + "px;");
                        const handleMin = document.createElement("div");
                        handleMin.classList.add("handle", "min");
                        handleMin.setAttribute("style", "left: " + offset1.toString() + "px");
                        handleMin.setAttribute("draggable", "true");
                        handleMin.addEventListener("dragstart", this.updateIntervalValues);
                        handleMin.ondragstart = () => {
                            return false;
                        }
                        const handleMax = document.createElement("div");
                        handleMax.classList.add("handle", "max");
                        handleMax.setAttribute("style", "left: " + offset2.toString() + "px");
                        handleMax.setAttribute("draggable", "true");
                        handleMax.addEventListener("dragstart", this.updateIntervalValues);
                        handleMax.ondragstart = () => {
                            return false;
                        }
                        bar.appendChild(between);
                        bar.appendChild(handleMin);
                        bar.appendChild(handleMax);
                        interval.appendChild(bar);
                        control.appendChild(interval);
                        break;
                }
            });

            const switcher = animation.querySelector("h4").querySelector("input");
            switcher.addEventListener("change", this.switchLeaves);

            const settingsToggler = animation.querySelector("h4").querySelector(".anim-settings-toggler");
            settingsToggler.addEventListener("click", () => {
                animation.querySelector(".anim-settings").classList.toggle("hide")
            },);

            const save = animation.querySelector(".save").querySelector("button");
            save.addEventListener("click", this.saveLeaves);
        });
    }

    newRangeValue(control) {
        let val = control.getAttribute("data-value"),
            min = control.getAttribute("data-min"),
            max = control.getAttribute("data-max");

        let data = this.getRangeValueFromSettings(control.getAttribute("data-name"));
        if (data) {
            val = data;
            control.setAttribute("data-value", val);
        }

        let values = document.createElement("div");
        values.classList.add("values");
        let value = document.createElement("input");
        value.setAttribute("type", "number");
        value.classList.add("value");
        value.value = val;
        value.setAttribute("min", min);
        value.setAttribute("max", max);
        value.addEventListener("change", this.updateRange);
        values.appendChild(value);

        return values;
    }

    newRange(control) {
        let val = control.getAttribute("data-value"),
            min = control.getAttribute("data-min"),
            max = control.getAttribute("data-max");
        const range = document.createElement("input");

        let data = this.getRangeValueFromSettings(control.getAttribute("data-name"));
        if (data) {
            val = data;
            control.setAttribute("data-value", val);
        }

        range.setAttribute("type", "range");
        range.setAttribute("min", min);
        range.setAttribute("max", max);
        range.setAttribute("value", val);
        range.value = val;
        range.addEventListener("change", this.updateRangeValue)
        return range;
    }

    getRangeValueFromSettings(name) {
        if (this.leaf_settings.length === 0) return false;
        let n = this.leaf_settings.length;
        for (let i = 0; i < n; i++) {
            let slider = this.leaf_settings[i];
            if (slider.type === "range") {
                if (slider.name === name) {
                    return slider.data;
                }
            }
        }
        return false;
    }

    getIntervalValuesFromSettings(name) {
        if (this.leaf_settings.length === 0) return [];
        let n = this.leaf_settings.length;
        for (let i = 0; i < n; i++) {
            let slider = this.leaf_settings[i];
            if (slider.type === "interval") {
                if (slider.name === name) {
                    return slider.data;
                }
            }
        }
        return [];
    }

    updateRange(evt) {
        evt.preventDefault();
        const input = evt.target;
        let value = input.value;
        const min = input.getAttribute("min");
        const max = input.getAttribute("max");
        if (value < min) {
            value = min;
            input.value = min;
        }
        if (value > max) {
            value = max;
            input.value = max;
        }
        const slider = input.closest(".slider");
        const range = slider.querySelector(".control").querySelector("input");
        range.value = value;
        slider.querySelector(".control").setAttribute("data-value", value);
        return false;
    }

    updateRangeValue(evt) {
        console.log(evt.target);
        evt.preventDefault();
        const inputRange = evt.target;
        let value = inputRange.value;
        const slider = inputRange.closest(".slider");
        slider.querySelector(".label").querySelector("input").value = value;
        slider.querySelector(".control").setAttribute("data-value", value);
    }

    updateIntervalValues(evt) {

        evt.preventDefault();
        const handle = evt.target;
        const interval = handle.closest(".interval");
        const control = handle.closest(".control");
        const between = interval.querySelector(".between");
        const handleMin = control.querySelector(".handle.min");
        const handleMax = control.querySelector(".handle.max");
        const min = control.getAttribute("data-min");
        const max = control.getAttribute("data-max");
        const width = interval.offsetWidth;
        const shiftX = evt.clientX - handle.getBoundingClientRect().left;

        handle.classList.add("drag");
        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);

        function onMouseMove(evt) {
            let newLeft = evt.clientX - shiftX - interval.getBoundingClientRect().left;

            if (newLeft < 0) {
                newLeft = 0;
            }
            let rightEdge = interval.offsetWidth - handle.offsetWidth;
            if (newLeft > rightEdge) {
                newLeft = rightEdge;
            }

            handle.style.left = newLeft + 'px';

            const newValue = parseInt(min) + Math.floor((max - min) * (newLeft / (width - 16)));

            if (evt.target.classList.contains("min")) {
                control.setAttribute("data-min-value", newValue);
                const inputMinValue = control.closest(".slider").querySelector(".label").querySelector(".value.min");
                inputMinValue.value = newValue.toString();
                between.style.left = (newLeft + 8) + 'px';
                between.setAttribute("style", "left: " + Math.floor(parseInt(handleMin.style.left) + 8) + "px; width: " + Math.floor(parseInt(handleMax.style.left) - parseInt(handleMin.style.left)) + "px;");
            }
            if (evt.target.classList.contains("max")) {
                control.setAttribute("data-max-value", newValue);
                const inputMaxValue = control.closest(".slider").querySelector(".label").querySelector(".value.max");
                inputMaxValue.value = newValue.toString();
                between.setAttribute("style", "left: " + Math.floor(parseInt(handleMin.style.left) + 8) + "px; width: " + Math.floor(parseInt(handleMax.style.left) - parseInt(handleMin.style.left)) + "px;");
            }
        }

        function onMouseUp() {
            handle.classList.remove("drag");
            document.removeEventListener('mouseup', onMouseUp);
            document.removeEventListener('mousemove', onMouseMove);
        }
    }

    switchLeaves(evt) {
        const check = evt.target;
        if (check.checked) {
            thisGlobal.startLeaves();
        } else {
            thisGlobal.stopLeaves();
        }
    }

    getLeafValues() {
        const leaf = document.querySelector(".leaf");
        const life = leaf.querySelector(".control[data-name='life-length']");
        const lifeLengthMin = parseInt(life.getAttribute("data-min-value"));
        const lifeLengthMax = parseInt(life.getAttribute("data-max-value"));
        const initialAngle = parseInt(leaf.querySelector(".control[data-name='initial-angle']").getAttribute("data-value"));
        const turnPerMinute = parseInt(leaf.querySelector(".control[data-name='turn-per-minute']").getAttribute("data-value"));
        const scaleMin = parseInt(leaf.querySelector(".control[data-name='scale']").getAttribute("data-min-value"));
        const scaleMax = parseInt(leaf.querySelector(".control[data-name='scale']").getAttribute("data-max-value"));
        const pCount = parseInt(leaf.querySelector(".control[data-name='number']").getAttribute("data-value"));

        return {
            count: pCount,
            lifeMin: lifeLengthMin,
            lifeMax: lifeLengthMax,
            initialAngle: initialAngle,
            turnPerMinute: turnPerMinute,
            scaleMin: scaleMin,
            scaleMax: scaleMax
        }
    }

    startLeaves() {
        const leafValues = thisGlobal.getLeafValues();
        // const body = document.body;
        const html = document.documentElement;
        // const height = Math.max(body.getBoundingClientRect().height, html.getBoundingClientRect().height);
        console.log({html})

        for (let i = 0; i < leafValues.count; i++) {
            let particule = {
                interval: 0,
                loop: 1,
                frame: 0,
                maxF: 0,
                maxY: 0,
                div: undefined,
                bg: "b0",
                x: 0,
                dx: 0,
                y: 0,
                dy: 0,
                initialAngle: 0,
                turnPerSeconde: 0,
                scale: 1
            };
            particule.div = document.createElement("div");
            particule.div.classList.add("particule");
            particule.div.classList.add("b0");
            thisGlobal.initParticule(particule, leafValues);
            particule.maxY = html.scrollHeight + 200;

            particule.div.setAttribute("style", "translate: " + particule.x + "px " + particule.y + "px; rotate: " + particule.initialAngle + "deg; scale: " + particule.scale);

            particule.interval = setInterval(thisGlobal.animateParticule, 1000, particule, leafValues);
            thisGlobal.leaf_particules[i] = particule;

            document.querySelector("body").appendChild(particule.div);
        }
    }

    stopLeaves() {
        console.log(thisGlobal.leaf_particules);

        for (let i = 0; i < thisGlobal.leaf_particules.length; i++) {
            if (thisGlobal.leaf_particules[i].interval > -1) {
                clearInterval(thisGlobal.leaf_particules[i].interval);
                thisGlobal.leaf_particules[i].interval = -1;
            }
            thisGlobal.leaf_particules[i].div.remove();
            thisGlobal.leaf_particules[i] = {
                interval: 0,
                loop: 1,
                frame: 0,
                maxF: 0,
                maxY: 0,
                div: undefined,
                bg: "b0",
                x: 0,
                dx: 0,
                y: 0,
                dy: 0,
                initialAngle: 0,
                turnPerSeconde: 0,
                scale: 1
            };
        }
    }

    saveLeaves(evt) {
        const save = evt.target;
        const animation = save.closest(".animation");
        const sliders = animation.querySelectorAll(".slider");
        let data = [], settings = {name: "leaf", data: []};

        sliders.forEach(slider => {
            const control = slider.querySelector(".control");
            let dataSlider = {name: control.getAttribute("data-name"), type: control.getAttribute("data-type")};
            if (dataSlider.type === "range") {
                dataSlider.data = control.getAttribute("data-value");
            }
            if (dataSlider.type === "interval") {
                dataSlider.data = [control.getAttribute("data-min-value"), control.getAttribute("data-max-value")];
            }
            data.push(dataSlider);
        });
        settings.data = data;
        console.log({settings});

        const xhr = new XMLHttpRequest();
        xhr.onload = function () {
            console.log(thisGlobal.response);
        }
        xhr.open("GET", thisGlobal.app_series_set_settings + "?data=" + JSON.stringify(settings));
        xhr.send();
    }

    animateParticule(p, values) {
        p.frame++;
        p.y += p.dy;

        p.div.setAttribute("style", "translate: " + p.x + "px " + p.y + "px; rotate: calc(" + p.initialAngle + "deg + " + (p.frame * p.turnPerSeconde) + "turn); scale: " + p.scale);

        if (p.y > p.maxY + 100) {
            clearInterval(p.interval);
            p.interval = -1
            p.div.classList.add("reboot");
            p.loop++;
            thisGlobal.initParticule(p, values, true);
            setTimeout(() => p.div.classList.remove("reboot"), 50);
        } else {
            if (p.frame > p.maxF) {
                clearInterval(p.interval);
                p.interval = -1;
                p.div.classList.add("re-new");
                setTimeout(() => {
                    p.div.classList.add("reboot");
                    p.div.classList.toggle("new");
                    p.loop = 1;
                    thisGlobal.initParticule(p, values, true);
                    setTimeout(() => {
                        p.div.classList.remove("reboot");
                        p.div.classList.remove("re-new");
                    }, 50);
                }, 800);
            }
        }
    }

    initParticule(p, values, animate = false) {

        p.frame = 0;
        p.maxF = values.lifeMin + (Math.random() * (values.lifeMax - values.lifeMin));
        p.x = Math.random() * (window.scrollX + window.innerWidth);
        p.y = -50 - Math.random() * (300);
        p.dx = Math.random() * 100;
        p.dy = Math.random() * 100;
        p.initialAngle = values.initialAngle + Math.random() * 360;
        p.turnPerSeconde = Math.random() * (values.turnPerMinute / 60);
        p.scale = (values.scaleMin / 100) + (Math.random() * ((values.scaleMax - values.scaleMin) / 100));

        p.div.classList.remove(p.bg);
        p.bg = "b" + (Math.ceil(42 * Math.random())).toString();
        p.div.classList.add(p.bg);

        if (animate) {
            p.div.setAttribute("style", "translate: " + p.x + "px " + p.y + "px; rotate: calc(" + p.initialAngle + "deg + " + (p.frame * p.turnPerSeconde) + "turn); scale: " + p.scale);
            p.interval = setInterval(thisGlobal.animateParticule, 1000, p, values);
        }
    }

    initSettings() {
        //
        // Settings
        //
        const apply = document.querySelector("#apply");
        let per_page = {
            'input': document.querySelector(".per-page").querySelector("input"),
            'value': document.querySelector(".per-page").querySelector(".value"),
            'data': [10, 20, 50, 100]
        }
        let order_by = {
            'input': document.querySelector(".order-by").querySelector("select"),
        }
        let order = {
            'input': document.querySelector(".order").querySelector("select"),
        }
        per_page.input.addEventListener("click", () => {
            per_page.value.innerHTML = per_page.data[per_page.input.value - 1].toString();
        })
        per_page.input.addEventListener("mousemove", () => {
            per_page.value.innerHTML = per_page.data[per_page.input.value - 1].toString();
        })
        order_by.input.addEventListener("change", () => {
            console.log(order_by.input.value)
        })
        order.input.addEventListener("change", () => {
            console.log(order.input.value)
        })
        apply.addEventListener("click", () => {
            window.location.href = this.app_series_index + "?p=1&pp=" + per_page.data[per_page.input.value - 1].toString() + "&ob=" + order_by.input.value + "&o=" + order.input.value + "&s=1";
        })
    }

    initPreview() {

        const preview = document.querySelector(".new-series-preview");
        const close = preview.querySelector(".close");

        preview.addEventListener("click", this.dismissPreview);
        close.addEventListener("click", this.dismissPreview);
    }

    dismissPreview() {
        const preview = document.querySelector(".new-series-preview");
        const message = preview.querySelector(".message").querySelector(".content");
        const wrapper = preview.querySelector(".wrapper");

        setTimeout(() => {
            preview.classList.remove("visible");
        }, 0);
        wrapper.innerHTML = "";
        message.innerHTML = "";
        document.querySelector("#new_serie").focus();
    }

    newSerie() {
        if (document.querySelector("#new-series") == null) {
            return;
        }
        document.querySelector("#new_serie").focus();
        document.querySelector("#new_serie").addEventListener("paste", thisGlobal.addSerie);
        document.querySelector("#add_serie").addEventListener("click", thisGlobal.addSerie);

        document.querySelector("#new_serie").addEventListener("keyup", (event) => {
            if (event.key === "Enter") {
                thisGlobal.addSerie(event);
            }
        })
    }

    addSerie(evt) {
        let value = "";

        if (evt.type === "click" || evt.type === 'keyup') {
            value = document.querySelector("#new_serie").value;
        }
        if (evt.type === "paste") {
            value = evt.clipboardData.getData('text/plain');
        }
        // evt.preventDefault()

        if (value.length) {
            const xhr = new XMLHttpRequest();
            xhr.onload = function () {
                let data = {
                    'serie': '',
                    'status': '',
                    'response': '',
                    'id': '',
                    'card': {},
                    'pagination': {}
                };
                if (this.response.slice(0, 1) === '<') {
                    data = this.response;
                } else {
                    data = JSON.parse(this.response);
                    if (data.status === 'Ok') {
                        const preview = document.querySelector(".new-series-preview");
                        const message = preview.querySelector(".message").querySelector(".content");
                        const wrapper = preview.querySelector(".wrapper");

                        wrapper.innerHTML = data.card.content;

                        if (data.response === "New") {
                            message.innerHTML = this.trans["added"][_locale];
                        }
                        if (data.response === "Update") {
                            message.innerHTML = this.trans["updated"][_locale];
                        }
                        setTimeout(() => {
                            preview.classList.add("visible");
                        }, 0);

                        /*
                         * Si on est sur la première page avec le tri par "ordre d'ajout décroissant",
                         * alors, on insère la nouvelle série au début
                         */
                        if (data.response === "New" && thisGlobal.current_page === 1 && thisGlobal.order_by === 'id' && thisGlobal.order === 'desc') {
                            const wrapper = document.querySelector(".series").querySelector(".wrapper");
                            const first = wrapper.firstElementChild;
                            const last = wrapper.lastElementChild;
                            const new_card = document.createRange().createContextualFragment(data.card.content);

                            first.before(new_card);
                            last.remove();
                        }
                        /*
                         * Mise à jour des blocs Pagination
                         */
                        const tools = document.querySelectorAll(".series-tools");
                        tools.forEach(tool => {
                            let pagination = tool.querySelector(".pages");
                            pagination.innerHTML = data.pagination.content;
                        })
                    }

                    if (data.status === "Ko") {
                        alert(thisGlobal.trans["not found"][_locale] + " (ID: " + data.id + ")");
                    }
                }
                document.querySelector("#new_serie").value = "";
            }
            xhr.open("GET", thisGlobal.app_series_new + '?value=' + value + "&p=" + thisGlobal.current_page + "&pp=" + thisGlobal.per_page + "&ob=" + thisGlobal.order_by + "&o=" + thisGlobal.order);
            xhr.send();
        }
    }

    autocomplete(input, list) {
        let currentFocus = -1;
        const show = document.querySelector("#search_serie");
        const searchId = document.querySelector("#search-id");

        setInterval(function () {
            let id = parseInt(input.value);
            if (id === -1) {
                show.classList.add("disabled");
            } else {
                show.classList.remove("disabled");
            }
        }, 100);

        show.addEventListener("click", () => {
            let id = parseInt(searchId.value);
            if (id === -1) {
                return;
            }
            window.location = thisGlobal.app_series_show + id + "?p=" + thisGlobal.current_page + "&from=" + thisGlobal.from;
        })

        input.addEventListener("input", function (e) {
            let a, b, i, val;
            val = e.target.value;
            closeAllLists();
            searchId.value = -1;
            if (!val) {
                return false;
            }
            currentFocus = -1;
            /*create a DIV element that will contain the items (values):*/
            a = document.createElement("div");
            a.setAttribute("id", e.target.id + "-autocomplete-list");
            a.classList.add("autocomplete-items");
            /*append the DIV element as a child of the autocomplete container:*/
            this.parentNode.appendChild(a);
            /*for each item in the array...*/
            for (i = 0; i < list.length; i++) {
                /*check if the item starts with the same letters as the text field value:*/
                let ok_name = list[i].name.substring(0, val.length).toUpperCase() === val.toUpperCase();
                let ok_original = list[i].original.substring(0, val.length).toUpperCase() === val.toUpperCase();
                let year = ' (' + list[i].date.slice(0, 4) + ')';
                if (ok_name || ok_original) {
                    /*create a DIV element for each matching element:*/
                    b = document.createElement("div");
                    /*make the matching letters bold:*/
                    if (ok_name) {
                        b.innerHTML = "<strong>" + list[i].name.substr(0, val.length) + "</strong>";
                        b.innerHTML += list[i].name.substring(val.length) + year;
                    } else {
                        b.innerHTML = "<strong>" + list[i].original.substring(0, val.length) + "</strong>";
                        b.innerHTML += list[i].original.substring(val.length) + year;
                    }
                    /*insert an input field that will hold the current array item's value:*/
                    b.innerHTML += "<input id='item-" + i + "-id' type='hidden' value='" + list[i].id + "'>";
                    b.innerHTML += "<input id='item-" + i + "-name' type='hidden' value='" + list[i].name + "'>";

                    b.addEventListener("click", function () {
                        input.value = this.getElementsByTagName("input")[1].value;
                        searchId.value = this.getElementsByTagName("input")[0].value;
                        closeAllLists();
                        currentFocus = -1;
                    });
                    a.appendChild(b);
                }
            }
        });

        input.addEventListener("keydown", function (e) {
            let x = document.getElementById(this.id + "-autocomplete-list");
            if (x) x = x.getElementsByTagName("div");
            if (e.keyCode === 40) { /* arrow DOWN key */
                currentFocus++;
                addActive(x);
            } else if (e.keyCode === 38) { /* arrow UP key */
                currentFocus--;
                addActive(x);
            } else if (e.keyCode === 13) { /* the ENTER key */
                e.preventDefault();
                if (currentFocus > -1) {
                    if (x) x[currentFocus].click();
                } else {
                    show.click();
                }
            }
        });

        function addActive(x) {
            if (!x) return false;
            /*start by removing the "active" class on all items:*/
            removeActive(x);
            if (currentFocus >= x.length) currentFocus = 0;
            if (currentFocus < 0) currentFocus = (x.length - 1);
            /*add class "autocomplete-active":*/
            x[currentFocus].classList.add("active");
        }

        function removeActive(x) {
            /*a function to remove the "active" class from all autocomplete items:*/
            for (let i = 0; i < x.length; i++) {
                x[i].classList.remove("active");
            }
        }

        function closeAllLists(element) {
            /*close all autocomplete lists in the document,
            except the one passed as an argument:*/
            let x = document.getElementsByClassName("autocomplete-items");
            for (let i = 0; i < x.length; i++) {
                if (element !== x[i] && element !== input) {
                    x[i].parentNode.removeChild(x[i]);
                }
            }
        }

        /*execute a function when someone clicks in the document:*/
        document.addEventListener("click", (e) => {
            closeAllLists(e.target);
        });
    }

    initHistory() {
        const historyMore = document.querySelector(".history-more");
        if (!historyMore) return;

        historyMore.addEventListener("click", thisGlobal.getMoreHistory);
    }

    getMoreHistory() {
        const historyMore = document.querySelector(".history-more");
        const history = document.querySelector(".history");
        const historyWrapper = history.querySelector(".history-wrapper");
        const historyItems = historyWrapper.querySelectorAll(".episode-history");
        const perPage = historyMore.getAttribute("data-per-page");
        const page = 1 + historyItems.length / perPage;
        const xhr = new XMLHttpRequest();
        xhr.onload = function () {
            let data;
            if (this.response.slice(0, 1) === '<') {
                data = this.response;
                console.log(data);
            } else {
                data = JSON.parse(this.response);
                if (data.status === 'Ok') {
                    /**
                     * @typedef HistoryItem
                     * @type {Object}
                     * @property {number} 'id'
                     * @property {number} 'offset'
                     * @property {string} 'name'
                     * @property {string} 'localized_name'
                     * @property {number} 'season_number'
                     * @property {number} 'episode_number'
                     * @property {string} 'substitute_name'
                     * @property {number} 'vote'
                     * @property {string} 'viewed_at'
                     * @property {string} 'poster_path'
                     */
                    const newItems = data.history;
                    /** @param {HistoryItem} item */
                    newItems.forEach(item => {
                        // <div class="episode-history">
                        //     <div class="poster"><img src="{{ h.poster_path }}" alt=""></div>
                        //     <div class="offset">{{ h.offset }}</div>
                        //     <div class="name">
                        //         <div>{{ h.name }}</div>
                        //         <div>{% if h.localized_name %}{{ h.localized_name }}{% endif %}</div>
                        //     </div>
                        //     <div class="date">{{ h.viewed_at|format_date('relative_medium')|capitalize }}</div>
                        //     <div class="episode">{{ 'S%02dE%02d'|format(h.season_number, h.episode_number) }}</div>
                        // </div>
                        const newHistoryItem = document.createElement("div");
                        newHistoryItem.classList.add("episode-history");
                        const newPoster = document.createElement("div");
                        newPoster.classList.add("poster");
                        const newPosterImg = document.createElement("img");
                        newPosterImg.setAttribute("src", item.poster_path);
                        newPoster.appendChild(newPosterImg);
                        newHistoryItem.appendChild(newPoster);
                        const newOffset = document.createElement("div");
                        newOffset.classList.add("offset");
                        newOffset.appendChild(document.createTextNode(item.offset));
                        newHistoryItem.appendChild(newOffset);
                        const newName = document.createElement("div");
                        newName.classList.add("name");
                        const newName1 = document.createElement("div");
                        newName1.appendChild(document.createTextNode(item.name));
                        newName.appendChild(newName1);
                        const newName2 = document.createElement("div");
                        if (item.localized_name) {
                            newName2.appendChild(document.createTextNode(item.localized_name));
                        }
                        newName.appendChild(newName2);
                        newHistoryItem.appendChild(newName);
                        const newDate = document.createElement("div");
                        newDate.classList.add("date");
                        newDate.innerHTML = item.viewed_at;
                        newHistoryItem.appendChild(newDate);
                        const newEpisode = document.createElement("div");
                        newEpisode.classList.add("episode");
                        newEpisode.appendChild(document.createTextNode("S" + item.season_number.toString().padStart(2, '0') + "E" + item.episode_number.toString().padStart(2, '0')));
                        newHistoryItem.appendChild(newEpisode);

                        historyWrapper.appendChild(newHistoryItem);
                    });
                }
            }
            if (historyItems.length < perPage) {
                historyMore.classList.add("hidden");
            }
        }
        xhr.open("GET", thisGlobal.app_series_history + "?page=" + page);
        xhr.send();
    }
}