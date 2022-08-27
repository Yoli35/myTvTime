let _app_serie_render_translation_field;
let _app_serie_render_translation_select;
let _app_serie_render_translation_save;

function initSerieStuff(paths) {

    _app_serie_render_translation_field = paths[0];
    _app_serie_render_translation_select = paths[1];
    _app_serie_render_translation_save = paths[2];

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

function translateKeywords() {

    const keywords = document.querySelector(".keywords");
    const newKeywords = keywords.querySelectorAll(".new");

    newKeywords.forEach(newKeyword => {
        newKeyword.addEventListener("click", openModal);
    })
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

    renderTranslationSelect(content);

    newKeywords.forEach((keyword) => {
        let txt = keyword.getAttribute("data-original");
        renderTranslationField(content, index++, txt);
    })
}

function renderTranslationField(content, index, keyword) {

    const xhr = new XMLHttpRequest();
    xhr.onload = function () {
        let div = document.createElement("div");
        div.classList.add("field");
        div.innerHTML = this.response;
        content.appendChild(div);
    }
    xhr.open("GET", _app_serie_render_translation_field + '?i=' + index + '&k=' + keyword);
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
