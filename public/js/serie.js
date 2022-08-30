let _app_serie_render_translation_fields;
let _app_serie_render_translation_select;
let _app_serie_render_translation_save;
let _app_serie_new;

function initSerieStuff(paths) {

    _app_serie_render_translation_fields = paths[0];
    _app_serie_render_translation_select = paths[1];
    _app_serie_render_translation_save = paths[2];
    _app_serie_new = paths[3]

    initAddSerie();
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

function translateKeywords() {

    const keywords = document.querySelector(".keywords");

    if (keywords) {
        const newKeywords = keywords.querySelectorAll(".new");

        newKeywords.forEach(newKeyword => {
            newKeyword.addEventListener("click", openModal);
        })
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
    let value = addButton.getAttribute("data-id");

    evt.preventDefault();

    const xhr = new XMLHttpRequest();
    xhr.onload = function () {
        let data;
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
        }
        console.log({data});
    }
    xhr.open("GET", _app_serie_new + '?value=' + value + "&from=serie");
    xhr.send();
}
