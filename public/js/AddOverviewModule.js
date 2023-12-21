let thisGlobal;

export class AddOverviewModule {


    constructor() {
        thisGlobal = this;
        this.serieId = 0;
        this.initDialog();
    }

    openDialog() {
        const dialog = document.querySelector("#add-overview-dialog");
        document.querySelector("body").classList.add("frozen");
        dialog.showModal();
    }

    prepareDialog(e){
        const dialog = document.querySelector("#add-overview-dialog");
        const textarea = dialog.querySelector("#new-overview");
        textarea.value = "";
        textarea.focus();
        this.openDialog();
    }

    initDialog() {
        const dialog = document.querySelector("#add-overview-dialog");
        const addButton = document.querySelector(".add-overview");
        const locale = document.querySelector("html").getAttribute("lang");

        this.serieId = dialog.getAttribute("data-id");

        addButton.addEventListener("click", this.prepareDialog.bind(this));

        dialog.addEventListener("close", () => {
            document.querySelector("body").classList.remove("frozen");
            if (dialog.returnValue === "addOverview") {
                const overview = dialog.querySelector("#new-overview").value;
                /** @var {{"result": boolean}} r */
                thisGlobal.saveOverview(overview).then(r => {
                    if (r.result) {
                        // <div class="alternate-overviews">
                        //      <div class="alternate-overview">
                        //           <div class="overview">{{ item.overview }}</div>
                        //      </div>
                        // </div>
                        let infoDiv = document.querySelector(".info.with-overview");
                        let alternateOverviewsDiv = document.querySelector(".alternate-overviews");
                        if (!alternateOverviewsDiv) {
                            alternateOverviewsDiv = document.createElement("div");
                            alternateOverviewsDiv.classList.add("alternate-overviews");
                        }
                        let alternateOverviewDiv = document.createElement("div");
                        alternateOverviewDiv.classList.add("alternate-overview");
                        let overviewDiv = document.createElement("div");
                        overviewDiv.classList.add("overview");
                        overviewDiv.innerHTML = overview;
                        alternateOverviewDiv.appendChild(overviewDiv);
                        alternateOverviewsDiv.appendChild(alternateOverviewDiv);
                        infoDiv.appendChild(alternateOverviewsDiv);
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
            if (action === 'addOverview' && evt.key === "Enter") {
                evt.preventDefault();
                evt.stopPropagation();
                dialog.close(action);
            }
        });
    }


    saveOverview(overview) {
        const locale = document.querySelector("html").getAttribute("lang");
        return fetch("/" + locale + "/series/set/alternate/overview/" + this.serieId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({"overview": overview})
        })
            .then(response => response.json())
            .then(data => {
                return data;
            });
    }
}