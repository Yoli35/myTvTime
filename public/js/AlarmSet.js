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
        const alarmCancel = dialog.querySelector("#alarm-cancel");
        alarmCancel.addEventListener("click", (evt) => {
            evt.preventDefault();
            evt.stopPropagation();
            dialog.close("cancel");
        });
        const alarmActivate = dialog.querySelector("#alarm-activate");
        alarmActivate.addEventListener("click", (evt) => {
            evt.preventDefault();
            evt.stopPropagation();
            dialog.close("activate");
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
        const tabs = dialog.querySelectorAll(".alarm-tab-name");
        tabs.forEach((tab) => {
            tab.addEventListener("click", (evt) => {
                evt.preventDefault();
                evt.stopPropagation();
                const tab = evt.target;
                const tabName = tab.getAttribute("id");
                const tabContents = dialog.querySelectorAll(".alarm-tab-content");
                tabs.forEach((tab) => {
                    tab.classList.remove("active");
                });
                tab.classList.add("active");
                tabContents.forEach((tabContent) => {
                    if (tabContent.getAttribute("data-id") === tabName) {
                        tabContent.classList.add("active");
                    } else {
                        tabContent.classList.remove("active");
                    }
                });
                if (tabName === "once") {
                    const now = new Date();
                    const alarmTime = dialog.querySelector("#alarm-time").value;
                    console.log(alarmTime);
                    const nowTime = now.getHours() + (now.getMinutes()<10?":0":":") + now.getMinutes();
                    console.log(nowTime);
                    const tabContent = dialog.querySelector(".alarm-tab-content[data-id='once']");
                    if (alarmTime < nowTime) {
                        tabContent.innerHTML = "Demain, à " + alarmTime + ".";
                    } else {
                        tabContent.innerHTML = "Aujourd'hui, à " + alarmTime + ".";
                    }
                }
            });
        });
    }
}