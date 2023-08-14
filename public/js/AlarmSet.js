let thisGlobal;
export class AlarmSet {

    thisGlobal = this;
    constructor() {
        this.initSearch();
    }

    initSearch() {
        const search = document.querySelector('#alarms');
        search.addEventListener("click", () => {
            this.alarm();
        });
        this.initDialog()
    }

    alarm() {
        const dialog = document.querySelector("#alarm-set");
        document.querySelector("body").classList.add("frozen");
        dialog.showModal();
    }

    initDialog() {
        const dialog = document.querySelector("#alarm-set");
        dialog.addEventListener("close", () => {
            document.querySelector("body").classList.remove("frozen");
            if (dialog.returnValue === "activate") {
                // Save alarm settings
            }
        });
        dialog.addEventListener("keydown", (evt) => {
            if (evt.key === "Escape") {
                evt.preventDefault();
                evt.stopPropagation();
                dialog.close("cancel");
            }
            if (evt.key === "Enter") {
                evt.preventDefault();
                evt.stopPropagation();
                dialog.close("activate");
            }
        });
    }
}