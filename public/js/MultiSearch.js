let thisGlobal;
export class MultiSearch {

    thisGlobal = this;
    constructor() {
        this.initSearch();
    }

    initSearch() {
        const search = document.querySelector('#multi-search');
        search.addEventListener("click", () => {
            this.search();
        });
        this.initDialog()
    }

    search() {
        const dialog = document.querySelector("#search-dialog");
        document.querySelector("body").classList.add("frozen");
        dialog.showModal();
    }

    initDialog() {
        const dialog = document.querySelector("#search-dialog");
        dialog.addEventListener("close", () => {
            document.querySelector("body").classList.remove("frozen");
            if (dialog.returnValue === "search") {
                // do the search and go to the result page
                const query = document.querySelector("#search-query").value;
                window.location.href = "/search?query=" + query;
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
                dialog.close("search");
            }
        });
    }
}