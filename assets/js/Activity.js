import {AnimatedHeader} from "AnimatedHeader";
import {ToolTips} from "ToolTips";

let thisGlobal;
let toolTips;

export class Activity {

    constructor() {
        thisGlobal = this;

        const globs = JSON.parse(document.querySelector("#activity-values").innerText);

        this.app_activity_stand_up_toggle = globs.app_activity_stand_up_toggle;
        this.app_activity_save_data = globs.app_activity_save_data;
        this.app_activity_save_day = globs.app_activity_save_day;
        // this.initialDay = new Date().getDate();
        // this.checkCount = 0;
        this.editing = false;
        this.PIx2 = Math.PI * 2;
        this.dayValues = {
            "activityId": 0,
            "dayId": null,
            "moveResult": 0,
            "exerciseResult": 0,
            "standUpResult": 0,
            "steps": 0,
            "distance": 0,
            "standUp": [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
        };

        this.init();
    }

    init() {
        new AnimatedHeader();
        // this.initDateChange();
        this.setProgressAll(["stand-up", "move", "exercise"]);
        this.ringsOfTheDay();
        this.initThreeRings();
        this.initStandUp(document);
        this.initInputs();
        this.initWeeks();

        toolTips = new ToolTips();
        toolTips.init();
    }

    setProgressAll(circles) {
        circles.forEach(circle => {
            const elements = document.querySelectorAll(".progress." + circle);
            elements.forEach(element => {
                thisGlobal.progress(circle, element);
            });
        });
    }

    setProgress(day, circles) {
        circles.forEach(circle => {
            const element = day.querySelector(".progress." + circle);
            thisGlobal.progress(circle, element);
        });
    }

    progress(circle, element) {
        const value = element.getAttribute("data-percent");
        const arc = element.querySelector(".circle");
        let start = element.querySelector(".circle-start");
        let end = element.querySelector(".circle-end");
        let style = "background: conic-gradient(var(--activity-" + circle + ") 0%, var(--activity-" + circle + ") " + value + "%, var(--gradient-grey-80) " + value + "%);";
        arc.setAttribute("style", style);
        start.setAttribute("style", "translate: 0 -27px;");
        end.setAttribute("style", "transform: rotate(" + (value * 3.6) + "deg) translateY(-27px) rotate(45deg)");
    }

    initThreeRings() {
        const days = document.querySelectorAll(".day");
        days.forEach(day => {
            thisGlobal.threeRings(day);
        });
    }

    threeRings(dayBlock) {

        if (dayBlock.querySelectorAll(".completed.visible").length === 3) {
            dayBlock.classList.add("three-rings");
        } else {
            dayBlock.classList.remove("three-rings");
        }
    }

    ringsOfTheDay() {
        const ringsOfTheDays = document.querySelectorAll(".rings-of-the-day");

        ringsOfTheDays.forEach(ringsOfTheDay => {
            const drawnRings = ringsOfTheDay.querySelector(".drawn-rings");
            if (drawnRings) {
                const move = drawnRings.getAttribute("data-move");
                const exercise = drawnRings.getAttribute("data-exercise");
                const standUp = drawnRings.getAttribute("data-stand-up");
                const id = drawnRings.querySelector("canvas").getAttribute("id");
                thisGlobal.drawRings(id, move, exercise, standUp);
            }
        });
    }

    initStandUp(element) {
        const ups = element.querySelectorAll(".stand-up .hour");
        ups.forEach(up => {
            up.addEventListener("click", thisGlobal.toggleUp);
        });
    }

    toggleUp(evt) {
        const up = evt.currentTarget;
        const hours = up.closest(".hours");
        const dayDiv = hours.closest(".day");
        const standUpDiv = up.closest(".stand-up");

        const id = hours.getAttribute("data-id");
        const day = hours.getAttribute("data-day");
        const index = up.getAttribute("data-index");
        const value = parseInt(up.getAttribute("data-up"));
        // console.log({id, day, index, value});

        const xhr = new XMLHttpRequest();
        xhr.onload = function () {
            let {success, html, result, percent, goal} = JSON.parse(this.response);
            if (!success) return;

            const resultDiv = standUpDiv.querySelector(".result");
            if (resultDiv) {
                resultDiv.innerText = result;
            }

            const wrapper = standUpDiv.querySelector(".wrapper");
            const oldHoursDiv = up.parentNode;
            const hoursDiv = document.createElement("div");
            hoursDiv.classList.add("hours");
            hoursDiv.setAttribute("data-id", id);
            hoursDiv.setAttribute("data-day", day);
            hoursDiv.innerHTML = html.content;
            wrapper.insertBefore(hoursDiv, oldHoursDiv);
            wrapper.removeChild(oldHoursDiv);
            thisGlobal.initStandUp(hoursDiv);
            toolTips.init(wrapper);

            const standUpRingCompleted = goal;
            const progress = wrapper.closest(".activity-of-the-day")?.querySelector(".progress.stand-up");
            if (progress) {
                progress.setAttribute("data-percent", percent);
                progress.querySelector(".percentage").innerText = percent + '%';
                thisGlobal.setProgress(dayDiv, ["stand-up"]);

                const canvasId = wrapper.closest(".day").querySelector("canvas").getAttribute("id");
                thisGlobal.updateRings(canvasId, 2, "stand-up", percent);

                const standUpRing = standUpDiv.querySelector(".completed");
                if (standUpRingCompleted) {
                    standUpRing.classList.add("visible");
                } else {
                    standUpRing.classList.remove("visible");
                }
                const doubled = standUpDiv.querySelector(".doubled");
                if (doubled) {
                    if (percent >= 200) {
                        let times = Math.floor(percent / 100);
                        doubled.querySelector("div").innerText = "x" + times;
                        doubled.classList.add("visible");
                    } else {
                        doubled.classList.remove("visible");
                    }
                }
            } else {
                thisGlobal.dayValues.standUpResult = result;
            }
        }
        xhr.open("GET", thisGlobal.app_activity_stand_up_toggle + "?day=" + day + "&up=" + index + "&val=" + (1 - value));
        xhr.send();
    }

    initInputs() {
        const inputContainers = document.querySelectorAll(".activity-of-the-day > .move, .activity-of-the-day > .exercise");

        inputContainers.forEach(container => {
            const inputs = container.querySelectorAll("div > label > input");
            inputs.forEach(input => {
                const card = input.closest(".block-body").parentElement;
                const div = input.closest("div");
                div.addEventListener("click", (evt) => {
                    thisGlobal.startEditing(evt, div, input, card);
                });
                div.addEventListener("focusin", (evt) => {
                    thisGlobal.startEditing(evt, div, input, card);
                });
                input.addEventListener("keydown", (evt) => {
                    if (evt.key === "Enter" || evt.key === "Tab") {
                        thisGlobal.finishEditing(evt, div, input, card, true);
                    }
                    if (evt.key === "Escape") {
                        thisGlobal.finishEditing(evt, div, input, card, false);
                    }
                });
            });
        });
    }

    startEditing(evt, div, input, card) {
        thisGlobal.editing = true;
        evt.preventDefault();
        evt.stopPropagation();
        document.querySelector(".tool-tips").classList.add("masked");
        input.classList.add("visible");
        input.focus();
        card.addEventListener("click", (evt) => {
            thisGlobal.finishEditing(evt, div, input, card, true);
        });
    }

    finishEditing(evt, div, input, card, save) {
        evt.preventDefault();
        evt.stopPropagation();

        card.removeEventListener("click", () => {
            thisGlobal.finishEditing(evt, div, input, card, true);
        });

        if (save && thisGlobal.editing) {
            thisGlobal.editing = false;
            thisGlobal.saveInput(input);
        }

        input.classList.remove("visible");
        document.querySelector(".tool-tips").classList.remove("masked");
        card.focus();
    }

    saveInput(input) {

        const name = input.getAttribute("name");
        const value = input.value;
        const parent = input.closest("div");
        const day = parent.getAttribute("data-day");

        const xhr = new XMLHttpRequest();
        xhr.onload = function () {
            let {success, goal, blockSelector, percent, circleSelector} = JSON.parse(this.response);
            if (!success) return;

            if (circleSelector === 'exercise') {
                parent.querySelector('span.value').innerText = value;
                parent.querySelector('span.unit').innerText = (value > 1 ? 'minutes' : 'minute');
            } else {
                parent.querySelector("span").innerText = value;
            }

            const progress = parent.closest(".activity-of-the-day").querySelector(".progress." + circleSelector);
            if (progress) {
                progress.setAttribute("data-percent", percent);
                progress.querySelector(".percentage").innerText = percent + '%';
                thisGlobal.setProgress(input.closest(".day"), [circleSelector]);
            }

            if (circleSelector === "exercise" || circleSelector === "move") {
                const canvasId = input.closest(".day").querySelector("canvas").getAttribute("id");
                const ringIndex = circleSelector === "exercise" ? 1 : 0
                thisGlobal.updateRings(canvasId, ringIndex, circleSelector, percent);
            }

            const block = input.closest(".activity-of-the-day").querySelector(blockSelector);
            const completed = block.querySelector(".completed"); // null pour steps et distance
            if (completed) {
                if (goal) {
                    completed.classList.add("visible");
                } else {
                    completed.classList.remove("visible");
                }
            }
            const doubled = block.querySelector(".doubled");
            if (doubled) {
                if (percent >= 200) {
                    let times = Math.floor(percent / 100);
                    doubled.querySelector("div").innerText = "x" + times;
                    doubled.classList.add("visible");
                } else {
                    doubled.classList.remove("visible");
                }
            }
            thisGlobal.threeRings(input.closest(".day"));
        }
        xhr.open("GET", thisGlobal.app_activity_save_data + "?day=" + day + "&name=" + name + "&value=" + value);
        xhr.send();
    }

    initWeeks() {
        const weeks = document.querySelectorAll(".week");
        weeks.forEach(week => {
            const days = week.querySelectorAll(".day");
            days.forEach(day => {
                this.initDayOfWeek(day);
            });
        });
        this.initDialog();
    }

    initDayOfWeek(day) {
        const ringsOfTheDay = day.querySelector(".rings-of-the-day");
        if (ringsOfTheDay) {
            ringsOfTheDay.addEventListener("click", (evt) => {
                thisGlobal.editDayValues(evt, day);
            });
        }
    }

    initDialog() {
        const dialog = document.querySelector("#activity-dialog");
        dialog.addEventListener("close", () => {
            document.querySelector("body").classList.remove("frozen");
            if (dialog.returnValue === "update") {
                thisGlobal.dayValues.moveResult = dialog.querySelector("#moveResult").value;
                thisGlobal.dayValues.exerciseResult = dialog.querySelector("#exerciseResult").value;
                thisGlobal.dayValues.steps = dialog.querySelector("#steps").value;
                thisGlobal.dayValues.distance = dialog.querySelector("#distance").value;
                thisGlobal.dayValues.standUpResult = parseInt(dialog.querySelector(".result").innerText);
                for (let i = 0; i < 24; i++) {
                    const selector = '.hour[data-index="' + i + '"]';
                    const standUpDiv = dialog.querySelector(selector);
                    thisGlobal.dayValues.standUp[i] = standUpDiv.classList.contains("up") ? 1 : 0;
                }
                thisGlobal.dayValues.activityId = dialog.querySelector(".hours").getAttribute("data-id");
                thisGlobal.dayValues.dayId = dialog.querySelector(".hours").getAttribute("data-day");
                thisGlobal.updateDayValues();
            }
        });
        dialog.addEventListener("keydown", (evt) => {
            if (evt.key === "Enter") {
                evt.preventDefault();
                evt.stopPropagation();
                dialog.close("update");
            }
            if (evt.key === "Escape") {
                evt.preventDefault();
                evt.stopPropagation();
                dialog.close("cancel");
            }
        });
    }

    editDayValues(evt, day) {
        evt.preventDefault();
        evt.stopPropagation();
        const dayId = day.querySelector(".rings-of-the-day").getAttribute("data-id");
        const selector = "#day-" + dayId + "-values";
        const values = day.querySelector(selector);
        const data = JSON.parse(values.innerText);
        const dialog = document.querySelector("#activity-dialog");
        // console.log({data});
        dialog.querySelector(".activity-dialog-title").innerText = day.querySelector(".date").innerText;
        dialog.querySelector("#moveResult").value = data.moveResult;
        dialog.querySelector("#exerciseResult").value = data.exerciseResult;
        dialog.querySelector("#steps").value = data.steps;
        dialog.querySelector("#distance").value = data.distance;
        dialog.querySelector(".result").innerText = data.standUpResult;
        dialog.querySelector(".hours").setAttribute("data-day", dayId);
        for (let i = 0; i < 24; i++) {
            const standUp = data.standUp[i];
            const selector = '.hour[data-index="' + i + '"]';
            const standUpDiv = dialog.querySelector(selector);
            let count = 0;
            if (standUp) {
                standUpDiv.classList.add("up");
                standUpDiv.classList.remove("down");
                count++;
            } else {
                standUpDiv.classList.add("down");
                standUpDiv.classList.remove("up");
            }
            thisGlobal.dayValues.standUpResult = count;
        }
        document.querySelector("body").classList.add("frozen");
        dialog.showModal()
    }

    updateDayValues() {
        // console.log(thisGlobal.dayValues);
        const dayId = thisGlobal.dayValues.dayId;
        const values = JSON.stringify(thisGlobal.dayValues);

        const xhr = new XMLHttpRequest();
        xhr.onload = function () {
            let response = {
                "success": false,
                "dayBlock": "",
                "moveProgress": 0,
                "exerciseProgress": 0,
                "standUpProgress": 0
            };
            if (this.status === 200) {
                response = JSON.parse(this.response);
                // console.log(response);
                let day = document.querySelector("#day-" + dayId);
                const newDay = document.createElement("div");
                newDay.innerHTML = response.dayBlock;
                day.replaceWith(newDay);
                const canvasId = "rings-" + dayId;
                thisGlobal.drawRings(canvasId, response.moveProgress, response.exerciseProgress, response.standUpProgress);
                day = document.querySelector("#day-" + dayId);
                thisGlobal.initDayOfWeek(day);
            }
        }
        xhr.open("GET", thisGlobal.app_activity_save_day + "?values=" + values);
        xhr.send();
    }

    drawRings(canvasId, progressMove, progressExercise, progressStandUp) {
        const canvas = document.getElementById(canvasId);
        const context = canvas.getContext('2d', {willReadFrequently: true});
        const thickness = 16;
        const scale = canvas.width / 128;
        const x = canvas.width / 2;
        const y = canvas.height / 2;

        thisGlobal.drawBaseRings(canvas, context, x, y, thickness, scale);

        // animeRing(context, x, y, scale * 54, scale * thickness, scale * 2, '#FF0000', progressMove, 0);
        // animeRing(context, x, y, scale * 36, scale * thickness, scale * 2, '#B7FF00', progressExercise, 0);
        // animeRing(context, x, y, scale * 18, scale * thickness, scale * 2, '#00D6BD', progressStandUp, 0);

        thisGlobal.drawRing(context, x, y, scale * 54, scale * thickness, scale * 2, '#FF0000', progressMove);
        thisGlobal.drawRing(context, x, y, scale * 36, scale * thickness, scale * 2, '#B7FF00', progressExercise);
        thisGlobal.drawRing(context, x, y, scale * 18, scale * thickness, scale * 2, '#00D6BD', progressStandUp);
    }

    updateRings(canvasId, ringIndex, ringName, progressTo) {
        const canvas = document.getElementById(canvasId);
        const canvasParent = canvas.parentElement;
        const progressFrom = parseInt(canvasParent.getAttribute("data-" + ringName));
        const context = canvas.getContext('2d', {willReadFrequently: true});
        const thickness = 16;
        const scale = canvas.width / 128;
        const x = canvas.width / 2;
        const y = canvas.height / 2;
        const colors = ['#F00', '#B7FF00', '#00D6BD'];
        const radius = [scale * 54, scale * 36, scale * 18];

        canvasParent.setAttribute("data-" + ringName, progressTo);
        thisGlobal.animeRing(context, x, y, radius[ringIndex], scale * thickness, scale * 2, colors[ringIndex], progressTo, progressFrom);
    }

    drawBaseRings(canvas, context, x, y, thickness, scale) {

        context.clearRect(0, 0, canvas.width, canvas.height);
        context.beginPath();
        context.arc(x, y, scale * 64, 0, thisGlobal.PIx2, false);
        context.fillStyle = '#0F1924';
        context.fill();

        context.beginPath();
        context.arc(x, y, scale * 54, 0, thisGlobal.PIx2, false);
        context.lineWidth = scale * thickness;
        context.strokeStyle = '#2C4A6D';
        context.stroke();

        context.beginPath();
        context.arc(x, y, scale * 36, 0, thisGlobal.PIx2, false);
        context.lineWidth = scale * thickness;
        context.strokeStyle = '#2C4A6D';
        context.stroke();

        context.beginPath();
        context.arc(x, y, scale * 18, 0, thisGlobal.PIx2, false);
        context.lineWidth = scale * thickness;
        context.strokeStyle = '#2C4A6D';
        context.stroke();
    }

    animeRing(context, x, y, radius, thickness, stroke, color, progress, progressStart = 0) {
        let inc = progressStart;
        let way = progressStart < progress ? 1 : -1;
        if (way === 1) {
            const interval = setInterval(() => {
                if (inc <= progress) {
                    thisGlobal.clearRing(context, x, y, radius, thickness);
                    thisGlobal.drawRing(context, x, y, radius, thickness, stroke, color, inc);
                    inc += .5;
                } else {
                    clearInterval(interval);
                }
            }, 5);
        } else {
            const interval = setInterval(() => {
                if (inc >= progress) {
                    thisGlobal.clearRing(context, x, y, radius, thickness);
                    thisGlobal.drawRing(context, x, y, radius, thickness, stroke, color, inc);
                    inc -= .5;
                } else {
                    clearInterval(interval);
                }
            }, 5);
        }
    }

    clearRing(context, x, y, radius, thickness) {
        context.beginPath();
        context.arc(x, y, radius, 0, thisGlobal.PIx2, false);
        context.lineWidth = thickness;
        context.strokeStyle = "#2C4A6D";
        context.stroke();
    }

    drawRing(context, x, y, radius, thickness, stroke, color, progress) {
        const offset = thisGlobal.PIx2 / -4;
        const endAngle = (thisGlobal.PIx2 * progress / 100);
        const endX = x + (radius * Math.sin(endAngle));
        const endY = y - (radius * Math.cos(endAngle));

        context.beginPath();
        context.arc(x, y - radius, thickness / 2, 0, thisGlobal.PIx2, false);
        context.fillStyle = color;
        context.fill();

        context.beginPath();
        context.arc(x, y, radius, offset, offset + (thisGlobal.PIx2 * progress / 100), false);
        context.lineWidth = thickness;
        context.strokeStyle = color;
        context.stroke();

        context.beginPath();
        context.arc(endX, endY, thickness / 2, 0, thisGlobal.PIx2, false);
        context.fillStyle = color;
        context.fill();

        context.beginPath();
        context.arc(endX, endY, (thickness + stroke) / 2, offset + (thisGlobal.PIx2 * progress / 100), -offset + (thisGlobal.PIx2 * progress / 100), false);
        context.lineWidth = stroke;
        context.strokeStyle = '#0F1924';
        context.stroke();
    }
}