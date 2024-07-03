let thisGlobal;

export class AlarmSet {

    constructor() {
        thisGlobal = this;
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
        const alarms = dialog.querySelectorAll(".alarm");
        alarms.forEach((alarm) => {
            alarm.addEventListener("click", (evt) => {
                evt.preventDefault();
                evt.stopPropagation();
                const alarm = evt.currentTarget;
                thisGlobal.hydrateAlarmContent(alarm);
            });
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
                const tabName = tab.getAttribute("data-id");
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
                    thisGlobal.tabContentOnce(dialog);
                }
            });
        });
    }

    tabContentOnce(dialog) {
        const now = new Date();
        const alarmTime = dialog.querySelector("#alarm-time").value;
        const nowTime = now.getHours() + (now.getMinutes() < 10 ? ":0" : ":") + now.getMinutes();
        const tabContent = dialog.querySelector(".alarm-tab-content[data-id='once']");
        if (alarmTime < nowTime) {
            tabContent.innerHTML = "Demain, à " + alarmTime + ".";
        } else {
            tabContent.innerHTML = "Aujourd'hui, à " + alarmTime + ".";
        }
    }

    hydrateAlarmContent(alarm) {
        const alarms = alarm.closest(".alarms").querySelectorAll(".alarm");
        const dialog = alarm.closest("dialog");
        const content = dialog.querySelector(".content");
        const alarmIdInput = content.querySelector("#alarm-id");
        const alarmNameInput = content.querySelector("#alarm-name");
        const alarmDescriptionInput = content.querySelector("#alarm-description");
        const alarmTimeInput = content.querySelector("#alarm-time");
        alarms.forEach((a) => {
            a.classList.remove("active");
        });
        alarm.classList.add("active");
        const alarmData = alarm.querySelector(".alarm-data");
        alarmIdInput.value = alarmData.getAttribute("data-id");
        alarmNameInput.value = alarm.querySelector(".alarm-name").textContent;
        alarmDescriptionInput.value = alarmData.getAttribute("data-description");
        alarmTimeInput.value = alarmData.getAttribute("data-time");
        const byDays = alarmData.getAttribute("data-by-days");
        const tabs = dialog.querySelectorAll(".alarm-tab-name");
        const tabContents = dialog.querySelectorAll(".alarm-tab-content");
        tabs.forEach((tab) => {
            tab.classList.remove("active");
        });
        tabContents.forEach((tabContent) => {
            tabContent.classList.remove("active");
        });
        let selector = ".alarm-tab-name[data-id='" + byDays + "']";
        const activeTab = dialog.querySelector(selector);
        activeTab.classList.add("active");
        selector = ".alarm-tab-content[data-id='" + byDays + "']";
        const activeTabContent = dialog.querySelector(selector);
        activeTabContent.classList.add("active");
        // Tab once
        if (byDays === "once") {
            thisGlobal.tabContentOnce(dialog);
        }
        // Tab days
        const alarmDaysInput = content.querySelector("#alarm-days");
        alarmDaysInput.value = alarmData.getAttribute("data-days");
        // Tab days of week
        const daysOfWeekTab = dialog.querySelector(".alarm-tab-content[data-id='week']");
        const daysOfWeek = daysOfWeekTab.querySelectorAll("input[type=checkbox]");
        const recurrence = parseInt(alarmData.getAttribute("data-recurrence"));
        daysOfWeek.forEach((day) => {
            if (recurrence & parseInt(day.getAttribute("data-shift"))) {
                day.setAttribute("checked", "checked");
            } else {
                day.removeAttribute("checked");
            }
            day.addEventListener("click", (evt) => {
               const day = evt.currentTarget;
                let recurrence = parseInt(alarmData.getAttribute("data-recurrence"));
                if (day.checked) {
                    recurrence |= parseInt(day.getAttribute("data-shift"));
                } else {
                    recurrence &= ~parseInt(day.getAttribute("data-shift"));
                }
                alarmData.setAttribute("data-recurrence", recurrence);
                console.log(recurrence);
            });
        });
    }
}