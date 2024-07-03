let thisGlobal;

export class LocalizeModule {


    constructor() {
        thisGlobal = this;
        this.serieId = 0;
        this.init();
    }

    init() {
        const localizeButton = document.querySelector('#edit-localized-name');
        this.serieId = localizeButton.getAttribute("data-id");
        localizeButton.addEventListener("click", () => {
            this.localize();
        });
        this.initDialog();
    }

    localize() {
        const dialog = document.querySelector("#localize-dialog");
        document.querySelector("body").classList.add("frozen");
        dialog.showModal();
    }

    initDialog() {
        const localize = document.querySelector("#localize-dialog");

        localize.addEventListener("close", () => {
            document.querySelector("body").classList.remove("frozen");
            if (localize.returnValue === "localize") {
                // save localized name
                const name = localize.querySelector("#localized-name").value;
                /** @var {{"name": string, "localized": string, "result": boolean}} r */
                thisGlobal.saveLocalizedName(name).then(r => {
                    if (r.result) {
                        const localizedNameSpan = document.querySelector("#localized-name").querySelector("span");
                        const name = r.name;
                        const localized = r.localized;
                        localizedNameSpan.innerHTML = localized + "(" + name + ")";
                    }
                });
            }
        });
        localize.addEventListener("keydown", (evt) => {
            const action = localize.querySelector("input[name=action]").value;
            if (evt.key === "Escape") {
                evt.preventDefault();
                evt.stopPropagation();
                localize.close("cancel");
            }
            if (action === 'localize' && evt.key === "Enter") {
                evt.preventDefault();
                evt.stopPropagation();
                localize.close(action);
            }
        });
    }


    saveLocalizedName(name) {
        const locale = document.querySelector("html").getAttribute("lang");
        return fetch("/" + locale + "/series/set/localized/name", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({"name": name, "id": this.serieId})
        })
            .then(response => response.json())
            .then(data => {
                return data;
            });
    }
}