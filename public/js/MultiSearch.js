let thisGlobal;

export class MultiSearch {


    constructor() {
        thisGlobal = this;
        this.timer = 0;
        this.interval = -1;
        this.history = [];
        this.initSearch();
    }

    initSearch() {
        const search = document.querySelector('#multi-search');
        search.addEventListener("click", () => {
            this.search();
        });
        const people = document.querySelector('#multi-people');
        people.addEventListener("click", () => {
            this.multiPeople();
        });
        this.initDialog()
        this.getHistory(20).then(data => {
            // console.log({data});
            if (data.result === 'success') thisGlobal.history = data.history.map(h => {
                /** @var {{"id": number, "text": string,"tmdbId": number}} h */
                return {'name': h.text, 'id': h.tmdbId}
            });
            // console.log(thisGlobal.history);
            if (thisGlobal.history.length) {
                // Ajouter un menu history
                const dialog2 = document.querySelector("#multi-people-dialog");
                const menu = dialog2.querySelector("#multi-people-tools");
                const li = document.createElement("li");
                const label = document.createElement("label");
                label.setAttribute("for", "history");
                const select = document.createElement("select");
                select.setAttribute("name", "history");
                select.setAttribute("id", "history");
                select.innerHTML = "<option value='0'>Historique</option>";
                thisGlobal.history.forEach(h => {
                    const option = document.createElement("option");
                    option.setAttribute("value", h.id);
                    option.innerHTML = h.name;
                    select.appendChild(option);
                });
                select.addEventListener("change", (e) => {
                    if (select.selectedIndex === 0) return;
                    // select the person and hydrate the first empty input or create a new input to hydrate
                    const inputs = dialog2.querySelectorAll("input[id^=people-]");
                    const empty = Array.from(inputs).find(i => i.value === "");
                    if (empty) {
                        empty.value = select.options[select.selectedIndex].innerHTML;
                        empty.setAttribute("data-id", select.value);
                    } else {
                        thisGlobal.addPeople(e, select.options[select.selectedIndex].innerHTML, select.value);
                    }
                    // Disable the option from the select
                    select.options[select.selectedIndex].setAttribute("disabled", "disabled");
                    // Select the first option
                    select.selectedIndex = 0;
                });
                label.appendChild(select);
                li.appendChild(label);
                menu.appendChild(li);
            }
        });
    }

    search() {
        const dialog = document.querySelector("#search-dialog");
        document.querySelector("body").classList.add("frozen");
        dialog.showModal();
    }

    multiPeople() {
        const dialog = document.querySelector("#multi-people-dialog");
        document.querySelector("body").classList.add("frozen");
        dialog.showModal();
    }

    initDialog() {
        const dialog1 = document.querySelector("#search-dialog");
        const dialog2 = document.querySelector("#multi-people-dialog");
        const dialogs = [dialog1, dialog2];

        dialogs.forEach(dialog => {
            dialog.addEventListener("close", () => {
                document.querySelector("body").classList.remove("frozen");
                if (dialog.returnValue === "search") {
                    // do the search and go to the result page
                    const query = document.querySelector("#search-query").value;
                    const db = document.querySelector("#search-db").checked;
                    window.location.href = "/search?query=" + query + (db ? "&db=1" : "");
                }
                if (dialog.returnValue === "multiPeople") {
                    // do the search and go to the result page
                    const people = document.querySelectorAll("input[id^=people-]");
                    const query = Array.from(people).map(p => p.getAttribute("data-id")).join(",");
                    window.location.href = "/search-people?query=" + query;
                }
            });
            dialog.addEventListener("keydown", (evt) => {
                const action = dialog.querySelector("input[name=action]").value;
                if (evt.key === "Escape") {
                    evt.preventDefault();
                    evt.stopPropagation();
                    dialog.close("cancel");
                }
                if (action === 'search' && evt.key === "Enter") {
                    evt.preventDefault();
                    evt.stopPropagation();
                    dialog.close(action);
                }
            });
        });

        const peopleInputs = dialog2.querySelectorAll("input[id^=people-]");
        peopleInputs.forEach(input => {
            input.addEventListener("input", this.initInput);
        });

        const addPeople = dialog2.querySelector("#add-people");
        addPeople.addEventListener("click", this.addPeople);
    }

    addPeople(e, name = "", tmdbId = 0) {
        if (e.type === "click") {
            e.preventDefault();
            e.stopPropagation();
        }
        const dialog2 = document.querySelector("#multi-people-dialog");
        const peopleSearches = dialog2.querySelector(".people-searches");
        const people = dialog2.querySelectorAll(".people-search");
        const last = people[people.length - 1];
        const id = parseInt(last.querySelector("label").getAttribute("for").split("-")[1]) + 1;
        const newPeopleSearch = last.cloneNode(true);
        newPeopleSearch.querySelector("label").setAttribute("for", "people-" + id);
        newPeopleSearch.querySelector("input").setAttribute("id", "people-" + id);
        newPeopleSearch.querySelector("input").setAttribute("name", "people-" + id);
        newPeopleSearch.querySelector("input").setAttribute("data-id", tmdbId);
        newPeopleSearch.querySelector("input").value = name;
        newPeopleSearch.querySelector("input").addEventListener("input", thisGlobal.initInput);
        peopleSearches.appendChild(newPeopleSearch);
    }

    initInput(e) {
        const input = e.target;
        const dialog2 = document.querySelector("#multi-people-dialog");
        const value = input.value;
        if (value.length > 3) {
            thisGlobal.getResults(value).then(data => {
                data = JSON.parse(data);
                const results = data.results;
                const list = input.closest(".people-search").querySelector(".people-list");
                const url = dialog2.querySelector("input[name=imgUrl]").value;
                list.innerHTML = "";
                results.forEach(result => {
                    /** @var {{"adult": bool,"gender": number,"id": number,"known_for_department": string,"name": string,"original_name": string,"popularity": number,"profile_path": string,"known_for": string}} result */
                    /** @var {HTMLElement} list */
                    const item = document.createElement("div");
                    item.classList.add("people-item");
                    item.setAttribute("data-id", result.id);
                    const thumbnail = document.createElement("div");
                    thumbnail.classList.add("thumbnail");
                    if (result.profile_path) {
                        const img = document.createElement("img");
                        img.setAttribute("src", url + result.profile_path);
                        img.setAttribute("alt", result.name);
                        thumbnail.appendChild(img);
                    } else {
                        thumbnail.classList.add("initials");
                        thumbnail.innerHTML = result.name.split(" ").map(n => n[0]).join("");
                    }
                    item.appendChild(thumbnail);
                    const name = document.createElement("div");
                    name.innerHTML = result.name;
                    item.appendChild(name);
                    list.appendChild(item);
                });
                list.classList.add("show");
                list.querySelectorAll(".people-item").forEach(item => {
                    item.addEventListener("click", () => {
                        const id = item.getAttribute("data-id");
                        const input = item.closest(".people-search").querySelector("input");
                        input.value = item.querySelector("div:last-child").innerHTML;
                        input.setAttribute("data-id", id);
                        list.classList.remove("show");
                        thisGlobal.saveHistory(input.value, id).then(data => {
                            console.log({data});
                            // data = JSON.parse(data);
                            // TODO: notify the user according to data.result (success or not)
                        });
                    });
                });
            });
        } else {
            const list = input.closest(".people-search").querySelector(".people-list");
            list.classList.remove("show");
            input.setAttribute("data-id", 0);
        }
    }

    getResults(name) {
        return fetch("/search-person/" + name)
            .then(response => response.json())
            .then(data => {
                return data;
            });
    }

    getHistory(limit) {
        return fetch("/search-people/history/get?limit=" + limit)
            .then(response => response.json())
            .then(data => {
                return data;
            });
    }

    saveHistory(name, id) {
        return fetch("/search-people/history?name=" + name + "&id=" + id)
            .then(response => response.json())
            .then(data => {
                return data;
            });
    }
}