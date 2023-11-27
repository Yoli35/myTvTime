let thisGlobal;

export class DirectLinkModule {


    constructor() {
        thisGlobal = this;
        this.serieId = 0;
        this.initDialog();
    }

    openDialog() {
        const dialog = document.querySelector("#direct-link-dialog");
        document.querySelector("body").classList.add("frozen");
        dialog.showModal();
    }

    initDialog() {
        const dialog = document.querySelector("#direct-link-dialog");
        const editDirectLinkButton = document.querySelector('button[id=edit-direct-link]');
        const newDirectLinkButton = document.querySelector('button[id=new-direct-link]');
        const locale = document.querySelector("html").getAttribute("lang");
        const txt = {
            "en": {
                "Watch now": "Watch now",
            },
            "fr": {
                "Watch now": "Regarder maintenant",
            },
            "de": {
                "Watch now": "Jetzt ansehen",
            },
            "es": {
                "Watch now": "Ver ahora",
            },
        };

        if (editDirectLinkButton) {
            this.serieId = editDirectLinkButton.getAttribute("data-id");
            editDirectLinkButton.addEventListener("click", () => {
                const href = editDirectLinkButton.parentElement.querySelector("a").getAttribute("href");
                const input = dialog.querySelector("#direct-link-url");
                input.value = href;
                this.openDialog();
            });
        }
        else {
            this.serieId = newDirectLinkButton.getAttribute("data-id");
            newDirectLinkButton.addEventListener("click", this.openDialog);
        }

        dialog.addEventListener("close", () => {
            document.querySelector("body").classList.remove("frozen");
            if (dialog.returnValue === "addDirectLink") {
                // save direct link
                const link = dialog.querySelector("#direct-link-url").value;
                /** @var {{"result": boolean}} r */
                thisGlobal.saveDirectLink(link).then(r => {
                    if (r.result) {
                        const directLinkDiv = document.querySelector(".direct-link");
                        const a = directLinkDiv.querySelector("a");
                        if (a) {
                            a.setAttribute("href", link);
                        } else {
                            // <div class="label">{{ 'Watch now'|trans }}</div>
                            const label = directLinkDiv.querySelector(".label");
                            label.innerHTML = txt[locale]["Watch now"];
                            // <div class="link" id="edit-direct-link" data-id="{{ serie.id }}"><i class="fa-solid fa-pen"></i></div>
                            const editLinkDiv = directLinkDiv.querySelector("#new-direct-link");
                            editLinkDiv.setAttribute("id", "edit-direct-link");
                            editLinkDiv.innerHTML = '<i class="fa-solid fa-pen"></i>';
                            editLinkDiv.removeEventListener("click", thisGlobal.openDialog);
                            editLinkDiv.addEventListener("click", () => {
                                const href = editLinkDiv.parentElement.querySelector("a").getAttribute("href");
                                const input = dialog.querySelector("#direct-link-url");
                                input.value = href;
                                this.openDialog();
                            });
                            // <a href="{{ serie.direct_link }}" target="_blank" className="link"><i className="fa-solid fa-circle-arrow-right"></i></a>
                            const link = document.createElement("a");
                            link.setAttribute("href", link);
                            link.setAttribute("target", "_blank");
                            link.innerHTML = '<i class="fa-solid fa-circle-arrow-right"></i>';
                            directLinkDiv.appendChild(link);
                        }
                    }
                });
            }
        });
        dialog.addEventListener("keydown", (evt) => {
            const action = dialog.querySelector("input[name=action]").value;
            if (evt.key === "Escape") {
                evt.preventDefault();
                evt.stopPropagation();
                dialog.close("cancel");
            }
            if (action === 'addDirectLink' && evt.key === "Enter") {
                evt.preventDefault();
                evt.stopPropagation();
                dialog.close(action);
            }
        });
    }


    saveDirectLink(link) {
        const locale = document.querySelector("html").getAttribute("lang");
        return fetch("/" + locale + "/series/set/direct/link/" + this.serieId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({"link": link})
        })
            .then(response => response.json())
            .then(data => {
                return data;
            });
    }
}