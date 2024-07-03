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

    prepareDialog(e){
        const dialog = document.querySelector("#direct-link-dialog");
        const href = e.currentTarget.parentElement.querySelector("a").getAttribute("href");
        const input = dialog.querySelector("#direct-link-url");
        input.value = href;
        input.focus();
        this.openDialog();
    }

    initDialog() {
        const dialog = document.querySelector("#direct-link-dialog");
        const editDirectLinkButtons = document.querySelectorAll('div[id^=edit-direct-link]');
        const newDirectLinkButtons = document.querySelectorAll('div[id^=new-direct-link]');
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

        this.serieId = dialog.getAttribute("data-id");

        if (editDirectLinkButtons.length > 0) {
            editDirectLinkButtons.forEach((editDirectLinkButton) => {
                editDirectLinkButton.addEventListener("click", this.prepareDialog.bind(this));
            });
        } else {
            newDirectLinkButtons.forEach((newDirectLinkButton) => {
                newDirectLinkButton.addEventListener("click", this.openDialog);
            });
        }

        dialog.addEventListener("close", () => {
            document.querySelector("body").classList.remove("frozen");
            if (dialog.returnValue === "addDirectLink") {
                // save direct link
                const link = dialog.querySelector("#direct-link-url").value;
                /** @var {{"result": boolean}} r */
                thisGlobal.saveDirectLink(link).then(r => {
                    if (r.result) {
                        const directLinkDivs = document.querySelectorAll(".direct-link");
                        directLinkDivs.forEach((directLinkDiv) => {
                            const a = directLinkDiv.querySelector("a");
                            if (a) {
                                a.setAttribute("href", link);
                            } else {
                                // <div class="label">{{ 'Watch now'|trans }}</div>
                                const label = directLinkDiv.querySelector(".label");
                                label.innerHTML = txt[locale]["Watch now"];
                                // <div class="link" id="edit-direct-link" data-id="{{ serie.id }}"><i class="fa-solid fa-pen"></i></div>
                                const editLinkDiv = directLinkDiv.querySelector("div[id^=new-direct-link]");
                                editLinkDiv.setAttribute("id", "edit-direct-link");
                                editLinkDiv.innerHTML = '<i class="fa-solid fa-pen"></i>';
                                editLinkDiv.removeEventListener("click", thisGlobal.openDialog);
                                editLinkDiv.addEventListener("click", this.prepareDialog.bind(this));
                                // <a href="{{ serie.direct_link }}" target="_blank" className="link"><i class="fa-solid fa-circle-arrow-right"></i></a>
                                const link = document.createElement("a");
                                link.classList.add("link");
                                link.setAttribute("href", link);
                                link.setAttribute("target", "_blank");
                                // link.innerHTML = '<i class="fa-solid fa-circle-arrow-right"></i>';
                                const icon = document.createElement("i");
                                icon.classList.add("fa-solid", "fa-circle-arrow-right");
                                link.appendChild(icon);
                                directLinkDiv.appendChild(link);
                            }
                        });
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