let _app_serie_render_translation_fields;
let _app_serie_render_translation_select;
let _app_serie_render_translation_save;
let _app_serie_new;
let _app_serie_show;

function initSerieStuff(paths, circles = undefined) {

    _app_serie_render_translation_fields = paths[0];
    _app_serie_render_translation_select = paths[1];
    _app_serie_render_translation_save = paths[2];
    _app_serie_new = paths[3];
    _app_serie_show = paths[4].substring(0, paths[4].length - 1);

    initAddSerie();
    if (circles !== undefined) setVote(circles);
    markMultiWatchProviders();
    translateKeywords();
}

function markMultiWatchProviders() {

    const providers = document.querySelectorAll(".watch-provider");

    if (providers.length > 1) {
        const arrow = document.querySelector(".arrow");
        arrow.classList.add("d-flex");
        arrow.innerHTML = "+" + (providers.length - 1);
    }
}

function initAddSerie() {
    document.querySelectorAll(".add").forEach(add => {
        add.addEventListener("click", addSerie);
    })
}

function setVote(circles) {

    circles.forEach(circle => {
        if (circle !== undefined) {
            const element = document.querySelector(circle[0]);
            const value = circle[1];

            if (element === null) return;

            const arc = element.querySelector(".circle");
            const start = element.querySelector(".circle-start");
            const end = element.querySelector(".circle-end");
            arc.setAttribute("style", "background: conic-gradient(var(--gradient-grey-60) 0%, var(--gradient-grey-60) " + value + "%, var(--gradient-grey-10) " + value + "%);");
            start.setAttribute("style", "translate: 0 -1.5em;");
            end.setAttribute("style", "transform: rotate(" + (value * 3.6) + "deg) translateY(-1.5em)");
        }
    });
}

function translateKeywords() {

    const keywordsBlocks = document.querySelectorAll(".keywords");

    if (keywordsBlocks) {
        keywordsBlocks.forEach(keywords => {
            const newKeywords = keywords.querySelectorAll(".new");
            newKeywords.forEach(newKeyword => {
                newKeyword.addEventListener("click", openModal);
            });
        });
    }
}

function openModal() {

    const back = document.querySelector(".translationModalBack");
    const modal = document.querySelector(".translationModal");
    const cancel = modal.querySelector("[type=button]");
    const translate = modal.querySelector("[type=submit]");

    initFields();
    opening(back, modal);
    cancel.addEventListener("click", () => {
        closing(back, modal);
    })
    translate.addEventListener("click", () => {
        saveTranslations(getTranslations(modal));
        closing(back, modal);
    })
}

function getTranslations(modal) {

    const inputs = modal.querySelectorAll("input");
    const language = modal.querySelector("select").value;
    let translations = [], idx = 0;

    inputs.forEach(input => {
        let key = input.getAttribute("data-original");
        let value = input.value;
        translations[++idx] = [key, value];
    })
    translations[0] = ['locale', language];

    return translations;
}

function saveTranslations(translations) {

    const xhr = new XMLHttpRequest();
    xhr.onload = function () {
        console.log(JSON.parse(this.response));
    }
    xhr.open("GET", _app_serie_render_translation_save + "?t=" + JSON.stringify(translations));
    xhr.send();
}

function opening(back, modal) {

    back.classList.add("open");
    modal.classList.add("open");
    setTimeout(() => {
        back.classList.add("visible");
        modal.classList.add("visible");
    }, 0);
}

function closing(back, modal) {

    back.classList.remove("visible");
    modal.classList.remove("visible");
    setTimeout(() => {
        back.classList.remove("open");
        modal.classList.remove("open");
    }, 500);

    const content = modal.querySelector(".trans-content");
    content.innerHTML = "";
}

function initFields() {

    const keywords = document.querySelector(".keywords");
    const newKeywords = keywords.querySelectorAll(".new");
    const modal = document.querySelector(".translationModal");
    const content = modal.querySelector(".trans-content");
    let index = 0;
    let values = [];

    renderTranslationSelect(content);

    newKeywords.forEach((keyword) => {
        values[index++] = keyword.getAttribute("data-original");
    });
    console.log({values});
    renderTranslationFields(content, values);
}

function renderTranslationFields(content, keywords) {

    const xhr = new XMLHttpRequest();
    xhr.onload = function () {
        let div = document.createElement("div");
        div.classList.add("fields");
        div.innerHTML = this.response;
        content.appendChild(div);
    }
    xhr.open("GET", _app_serie_render_translation_fields + '?k=' + JSON.stringify(keywords));
    xhr.send();
}

function renderTranslationSelect(content) {

    const xhr = new XMLHttpRequest();
    xhr.onload = function () {
        let div = document.createElement("div");
        div.classList.add("language");
        div.innerHTML = this.response;
        content.appendChild(div);
    }
    xhr.open("GET", _app_serie_render_translation_select);
    xhr.send();
}

function addSerie(evt) {
    const addButton = evt.currentTarget;
    const from = addButton.getAttribute("data-from");
    let value = addButton.getAttribute("data-id");

    evt.preventDefault();

    const xhr = new XMLHttpRequest();
    xhr.onload = function () {
        let data = {'serie': "", status: "", response: "", id: 0, card: null, userSerieId: 0, pagination: null};
        if (this.response.slice(0, 1) === '<') {
            data = this.response;
        } else {
            data = JSON.parse(this.response);
            if (data.status === 'Ok') {
                addButton.classList.remove("add");
                addButton.classList.add("seen");
                addButton.innerHTML = "<i class=\"fa-solid fa-eye\"></i>"
            }

            if (data.status === "Ko") {
                alert("{{ 'Serie not found'|trans }} (ID: " + data.id + ")");
            }

            const pathname = evt.view.location.pathname;

            if (pathname.includes("tmdb")) {
                window.location.href = _app_serie_show + data.userSerieId + evt.view.location.search;
            }

            if (data.pagination) {
                document.querySelectorAll(".pages").forEach(page => {
                    page.innerHTML = data.pagination.content;
                })
            }

            // if (from === "my_series") {
            //     if (data.card) {
            //         evt.currentTarget.closest("div[data-id=" + data.userSerieId + "]").innerHTML = data.card;
            //     }
            // }

            if (from === "popular" || from === "top_rated" ||
                from === "airing_today" || from === "on_the_air" ||
                from === "latest" || from === "search") {
                if (data.card) {
                    let card = addButton.closest("div[data-type=\"card\"]");
                    card.innerHTML = data.card.content;
                }
            }

        }
        // console.log({data});
    }
    xhr.open("GET", _app_serie_new + '?value=' + value + "&from=serie" + "&from=" + from);
    xhr.send();
}
