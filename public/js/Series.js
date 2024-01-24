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
                        // <a href="{{ path('app_series_show', {id: h.id}) }}?p={{ pages.page }}&from=my_series">
                        //     <div class="episode-history">
                        //         <div class="poster"><img src="{{ h.poster_path }}" alt=""></div>
                        //         <div class="offset">{{ h.offset }}</div>
                        //         <div class="name">
                        //             <div>{{ h.name }}</div>
                        //             <div>{% if h.localized_name %}<i>{{ h.localized_name }}</i>{% endif %}</div>
                        //         </div>
                        //         <div class="date">{{ h.viewed_at|format_date('relative_medium')|capitalize }}</div>
                        //         <div class="episode">{{ 'S%02dE%02d'|format(h.season_number, h.episode_number) }}</div>
                        //     </div>
                        // </a>
                        const a = document.createElement("a");
                        a.setAttribute("href", thisGlobal.app_series_show + item.id + "?p=" + thisGlobal.current_page + "&from=my_series");
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
                        if (item.localized_name && item.localized_name.toLowerCase().trim() !== item.name.toLowerCase().trim()) {
                            const i = document.createElement("i");
                            i.appendChild(document.createTextNode(item.localized_name));
                            newName2.appendChild(i);
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
                        a.appendChild(newHistoryItem);

                        historyWrapper.appendChild(a);
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