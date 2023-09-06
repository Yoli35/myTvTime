let thisGlobal;

export class MultiSearch {


    constructor() {
        thisGlobal = this;
        this.timer = 0;
        this.interval = -1;
        this.initSearch();
    }

    initSearch() {
        const search = document.querySelector('#multi-search');
        search.addEventListener("click", () => {
            this.search();
        });
        search.addEventListener("mouseenter", () => {
            thisGlobal.timer = 0;
            thisGlobal.interval = setInterval(() => {
                thisGlobal.timer++;
                if (thisGlobal.timer > 20) {
                    clearInterval(thisGlobal.interval);
                    thisGlobal.multiPeople();
                }
            }, 100);
        });
        search.addEventListener("mouseleave", () => {
            clearInterval(thisGlobal.interval);
        });
        this.initDialog()
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
        const peopleSearches = dialog2.querySelector(".people-searches");
        addPeople.addEventListener("click", () => {
            const people = dialog2.querySelectorAll(".people-search");
            const last = people[people.length - 1];
            const id = parseInt(last.querySelector("label").getAttribute("for").split("-")[1]) + 1;
            const newPeopleSearch = last.cloneNode(true);
            newPeopleSearch.querySelector("label").setAttribute("for", "people-" + id);
            newPeopleSearch.querySelector("input").setAttribute("id", "people-" + id);
            newPeopleSearch.querySelector("input").setAttribute("name", "people-" + id);
            newPeopleSearch.querySelector("input").setAttribute("data-id", 0);
            newPeopleSearch.querySelector("input").value = "";
            newPeopleSearch.querySelector("input").addEventListener("input", this.initInput);
            peopleSearches.appendChild(newPeopleSearch);
        });
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
                /** @var {Array<{"adult": bool,"gender": number,"id": number,"known_for_department": string,"name": string,"original_name": string,"popularity": number,"profile_path": string,"known_for": string}>} results */
                results.forEach(result => {
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
}