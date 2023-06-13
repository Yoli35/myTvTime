import {AverageColor} from "./averageColor.js";
import {AnimatedHeader} from "./AnimatedHeader.js";

let thisGlobal;

export class EventModule {

    constructor(globs, locale) {
        this.letterRatios = [];
        this.start = Date.now();
        this.locale = locale;

        thisGlobal = this;

        this.countdownValues = globs.countdownValues;

        if (globs.route === "event_index") {
            this.app_event_new = globs.app_event_new;
            this.app_event_edit = globs.app_event_edit.slice(0, -1);
            this.app_event_delete = globs.app_event_delete.slice(0, -1);
        }
        if (globs.route === "event_edit") {
            this.save_button = globs.save_button;
        }
        this.init(globs.route);
    }

    init(route) {
        new AnimatedHeader();
        if (route === "event_index") {
            this.initTools();
            document.querySelector(".add-event").addEventListener("click", this.addNewEvent);
        }
        if (route === "event_edit") {
            document.querySelector("#event_save").innerHTML = this.save_button;
        } else {
            this.initCountdowns();
            this.setBackgrounds(route);
        }
    }

    initCountdowns() {
        const countdowns = document.querySelectorAll(".countdown");

        countdowns.forEach(countdown => {
            countdown.classList.add("switch");
            setTimeout(this.createCountdown, 2000, countdown);
        });

        return countdowns.length;
    }

    createCountdown(countdown) {
        const id = parseInt(countdown.id);
        let days, hours, minutes, secondes, separator1, separator2, separator3, count, label;
        const countdownDateDiv = document.createElement("div");

        countdownDateDiv.classList.add("date");
        countdownDateDiv.innerHTML = countdown.innerHTML;

        countdown.innerHTML = "";

        days = document.createElement("div");
        hours = document.createElement("div");
        minutes = document.createElement("div");
        secondes = document.createElement("div");
        separator1 = document.createElement("div");
        separator2 = document.createElement("div");
        separator3 = document.createElement("div");
        days.setAttribute("id", "days-" + id);
        hours.setAttribute("id", "hours-" + id);
        minutes.setAttribute("id", "minutes-" + id);
        secondes.setAttribute("id", "secondes-" + id);
        days.classList.add("part");
        hours.classList.add("part");
        minutes.classList.add("part");
        secondes.classList.add("part");
        separator1.classList.add("separator");
        separator2.classList.add("separator");
        separator3.classList.add("separator");

        count = document.createElement("div");
        count.classList.add("count");
        count.innerText = "00";
        label = document.createElement("div");
        label.classList.add("label");
        label.innerText = "jours";
        days.appendChild(count);
        days.appendChild(label);

        count = document.createElement("div");
        count.classList.add("count");
        count.innerText = "00";
        label = document.createElement("div");
        label.classList.add("label");
        label.innerText = "heures";
        hours.appendChild(count);
        hours.appendChild(label);

        count = document.createElement("div");
        count.classList.add("count");
        count.innerText = "00";
        label = document.createElement("div");
        label.classList.add("label");
        label.innerText = "minutes";
        minutes.appendChild(count);
        minutes.appendChild(label);

        count = document.createElement("div");
        count.classList.add("count");
        count.innerText = "00";
        label = document.createElement("div");
        label.classList.add("label");
        label.innerText = "secondes";
        secondes.appendChild(count);
        secondes.appendChild(label);

        separator1.innerText = ":";
        separator2.innerText = ":";
        separator3.innerText = ":";

        countdown.appendChild(days);
        countdown.appendChild(separator1);
        countdown.appendChild(hours);
        countdown.appendChild(separator2);
        countdown.appendChild(minutes);
        countdown.appendChild(separator3);
        countdown.appendChild(secondes);

        countdown.appendChild(countdownDateDiv);

        countdown.classList.remove("switch");
        countdown.addEventListener('mouseenter', thisGlobal.showCountDownDate);
        countdown.addEventListener('mouseleave', thisGlobal.hideCountDownDate);

        const countdownValue = thisGlobal.countdownValues.find(countdownValue => countdownValue.id === id);
        thisGlobal.updateCountdown(countdown);
        countdownValue.interval = setInterval(thisGlobal.updateCountdown, 1000, countdown);
    }

    showCountDownDate(evt) {
        const countdown = evt.target;
        const div = countdown.querySelector(".date");

        countdown.classList.add("fade");
        div.classList.add("show");
    }

    hideCountDownDate(evt) {
        const countdown = evt.target;
        const div = countdown.querySelector(".date");

        countdown.classList.remove("fade");
        div.classList.remove("show");
    }

    updateCountdown(countdown) {
        const id = parseInt(countdown.id);
        const countdownValue = thisGlobal.countdownValues.find(countdownValue => countdownValue.id === id);
        const date = new Date(countdownValue.date);
        let d, h, m, s;
        const now = Date.now();
        const diff = Math.abs(now - date);
        const past = now > date;

        d = Math.floor(diff / 24 / 60 / 60 / 1000);
        h = Math.floor(diff / 60 / 60 / 1000) % 24;
        m = Math.floor(diff / 60 / 1000) % 60;
        s = past ? Math.floor(diff / 1000) % 60 : Math.ceil(diff / 1000) % 60;
        document.querySelector("#days-" + id).querySelector(".count").innerText = (d < 10 ? "0" : "") + d;
        document.querySelector("#hours-" + id).querySelector(".count").innerText = (h < 10 ? "0" : "") + h;
        document.querySelector("#minutes-" + id).querySelector(".count").innerText = (m < 10 ? "0" : "") + m;
        document.querySelector("#secondes-" + id).querySelector(".count").innerText = (s < 10 ? "0" : "") + s;

        if (past) {
            countdown.classList.add("past");
        }
    }

    initTools() {
        const dialog = document.querySelector(".confirm-deletion");
        const tools = document.querySelectorAll(".tools");
        const events = document.querySelectorAll(".event");

        tools.forEach(tool => {
            tool.querySelector(".fa-pen-to-square").addEventListener("click", this.editEvent);
            // tool.querySelector(".fa-eye-slash").addEventListener("click", hideEvent);
            tool.querySelector(".fa-trash-can").addEventListener("click", this.deleteEvent);
        });

        events.forEach(event => {
            event.addEventListener("mouseenter", this.showTools);
            event.addEventListener("mouseleave", this.hideTools);
        });

        dialog.querySelector(".delete-done").addEventListener("click", () => {
            this.closeDialog(dialog, true);
        });
        dialog.querySelector(".delete-cancel").addEventListener("click", () => {
            this.closeDialog(dialog, false);
        });
        dialog.querySelector(".close").addEventListener('click', () => {
            this.closeDialog(dialog, false);
        });
    }

    openDialog(dialog, id, elem) {
        const infos = elem.closest(".infos");
        dialog.querySelector("span").innerHTML = infos.querySelector("h2").innerHTML;
        dialog.setAttribute("data-id", id);

        if (typeof dialog.showModal === "function") {
            dialog.showModal();
            setTimeout(() => {
                dialog.classList.add("show")
            }, 0);
        } else {
            console.error("L'API <dialog> n'est pas prise en charge par ce navigateur.");
        }
    }

    closeDialog(dialog, deleteEvent) {
        let deletedId = parseInt(dialog.getAttribute("data-id"));
        let countdownValue = countdownValues.find(({id}) => id === deletedId);
        dialog.removeAttribute("data-id");
        dialog.classList.remove("show");
        setTimeout(() => {
            dialog.close()
        }, 300);
        if (deleteEvent) {
            const xhr = new XMLHttpRequest();
            xhr.onload = function () {
                const selector = ".event[data-id=\"" + deletedId + "\"]";
                const event = document.querySelector(selector);
                const a = event.closest("a");
                setTimeout(() => {
                    a.classList.add("deleted");
                }, 0);
                setTimeout(() => {
                    const events = a.closest(".events");
                    clearInterval(countdownValue.interval);
                    countdownValue.interval = -1;
                    events.removeChild(a);
                }, 300);
            }
            xhr.open("GET", thisGlobal.app_event_delete + deletedId);
            xhr.send();
        }
    }

    showTools(evt) {
        const tools = evt.currentTarget.querySelector(".tools");
        tools.classList.add("visible");
    }

    hideTools(evt) {
        const tools = evt.currentTarget.querySelector(".tools");
        tools.classList.remove("visible");
    }

    addNewEvent(evt) {
        evt.currentTarget.classList.add("click");
        setTimeout(() => {
            window.location.href = thisGlobal.app_event_new;
        }, 100);
    }

    editEvent(evt) {
        const id = evt.currentTarget.parentElement.getAttribute("id");
        console.log('edit', {id});
        evt.preventDefault();
        window.location.href = thisGlobal.app_event_edit + id;
    }

// function hideEvent(evt) {
//     const id = evt.currentTarget.parentElement.getAttribute("id");
//     console.log('hide', {id});
//     evt.preventDefault();
// }

    deleteEvent(evt) {
        const id = evt.currentTarget.parentElement.getAttribute("id");
        console.log('delete', {id});
        evt.preventDefault();
        openDialog(confirmDialog, id, evt.currentTarget);
    }

    setBackgrounds(route) {
        let events, imageContainerSelector;

        switch (route) {
            case "event_index":
                events = document.querySelectorAll(".event");
                imageContainerSelector = ".poster";
                break;
            case "event_show":
                events = document.querySelectorAll(".my-event");
                imageContainerSelector = ".thumbnail";
                break;
            default:
                return;
        }
        events.forEach(event => {
            const poster = event.querySelector(imageContainerSelector);
            const img = poster.querySelector("img");
            if (img) {
                const averageColor = new AverageColor();
                const color = averageColor.getColor(img);
                let destination = event;
                if (route === "event_show") {
                    destination = event.querySelector("main");
                }
                if (color.lightness > 150) {
                    destination.classList.add("light");
                } else {
                    destination.classList.add("dark");
                }
                destination.setAttribute("style", "background-color: " + "rgb(" + color.r + "," + color.g + "," + color.b + ")" + ";");
            }
        });
    }
}