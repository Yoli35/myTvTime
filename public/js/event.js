let letterRatios = [];
let countdownValues = [];
const start = Date.now();

function initHeader() {
    let ticking = false;
    let letters, animatedH1, index = 0;

    animatedH1 = document.createElement("div");
    animatedH1.classList.add("animated-h1");
    animatedH1 = document.querySelector(".header").insertBefore(animatedH1, document.querySelector(".backdrop"));
    letters = document.querySelector("h1").innerText.split('');

    document.querySelector("h1").innerText = "";

    letters.forEach(letter => {
        let part = document.createElement("div");
        part.classList.add("part");
        if (letter === " ") {
            part.innerHTML = "&nbsp;"
        } else {
            part.innerText = letter;
        }
        animatedH1.appendChild(part);
        letterRatios[index] = 2 * (Math.random() - .5);
        index++;
    })
    setH1();
    window.addEventListener('resize', setH1);

    window.addEventListener('scroll', () => {
        if (!ticking) {
            window.requestAnimationFrame(function () {
                setH1();
                ticking = false;
            });
        }
        ticking = true;
    });

}

function setH1() {
    const header = document.querySelector(".header");
    const h1 = document.querySelector(".animated-h1");
    const parts = h1.querySelectorAll(".part");
    let left, ratio, top, n = 0;
    ratio = (header.clientHeight - window.scrollY) / header.clientHeight;
    left = (header.clientWidth - h1.clientWidth) / 2;
    top = ((header.clientHeight + window.scrollY) - h1.clientHeight) / 2;
    if (ratio < 0) ratio = 0;
    if (ratio > 1) ratio = 1;
    parts.forEach(part => {
        part.setAttribute("style", "transform: rotate(" + (720 * (1 - ratio) * letterRatios[n++]) + "deg);");
    })
    h1.setAttribute("style", "left: " + left.toString() + "px; top: " + top.toString() + "px; opacity: " + ratio + "; transform: scale(" + (1 + (5 * (1 - ratio))) + ")");
}

function initCountdowns(values) {
    const countdowns = document.querySelectorAll(".countdown");

    countdownValues = values;

    setCountdownValues();

    countdowns.forEach(countdown => {
        countdown.classList.add("switch");
        setTimeout(createCountdown, 2000, countdown);
    });
}

function createCountdown(countdown) {
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
    countdown.addEventListener('mouseenter', showCountDownDate);
    countdown.addEventListener('mouseleave', hideCountDownDate);

    updateCountdown(countdown);
    setInterval(updateCountdown, 1000, countdown);
}

function showCountDownDate(evt) {
    const countdown = evt.target;
    const div = countdown.querySelector(".date");

    countdown.classList.add("fade");
    div.classList.add("show");
}

function hideCountDownDate(evt) {
    const countdown = evt.target;
    const div = countdown.querySelector(".date");

    countdown.classList.remove("fade");
    div.classList.remove("show");
}

function updateCountdown(countdown) {
    const id = parseInt(countdown.id);
    const countdownValue = countdownValues.find(item => item.id === id);
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
