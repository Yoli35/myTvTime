window.addEventListener("DOMContentLoaded", () => {
    const flashes = document.querySelectorAll(".flash-message");

    flashes.forEach(flash => {

        countdown(flash);
        flash.querySelector(".closure-countdown").addEventListener("click", () => {
            closeFlash(flash);
        });
        flash.querySelector(".flash-accept")?.addEventListener("click", (e) => {
            let id = e.currentTarget.getAttribute("data-id");

            const xhr = new XMLHttpRequest();
            xhr.onload = function () {
                closeFlash(flash);
            }
            xhr.open("GET", "/user/friendship/accept/" + id);
            xhr.send();
        });
        flash.querySelector(".flash-reject")?.addEventListener("click", (e) => {
            let id = e.currentTarget.getAttribute("data-id");

            const xhr = new XMLHttpRequest();
            xhr.onload = function () {
                closeFlash(flash);
            }
            xhr.open("GET", "/user/friendship/reject/" + id);
            xhr.send();
        });
        flash.querySelector("button[id=disable-this-alert]")?.addEventListener("click", (e) => {
            const id = e.currentTarget.getAttribute("data-id");
            const loadingDiv = document.createElement("div");
            loadingDiv.classList.add("loading");
            const innerDiv = document.createElement("div");
            const rotatingDiv = document.createElement("div");
            innerDiv.appendChild(rotatingDiv);
            loadingDiv.appendChild(innerDiv);
            flash.appendChild(loadingDiv);

            const xhr = new XMLHttpRequest();
            xhr.onload = function () {
                const loading = flash.querySelector(".loading");
                loading.parentNode.removeChild(loading);
                closeFlash(flash);
            }
            xhr.open("GET", "/fr/series/alert/disable/" + id);
            xhr.send();
        });
        setTimeout(() => {
            closeFlash(flash);
        }, 30000);
    });

    document.querySelector(".flash-messages").querySelector("#close-all-alerts")?.addEventListener("click", () => {
        flashes.forEach(flash => {
            closeFlash(flash);
        });
        const flashMessagesDiv = document.querySelector(".flash-messages");
        const flashCloseAllDiv = document.querySelector(".flash-close-all");
        flashMessagesDiv.removeChild(flashCloseAllDiv);
    });

    function countdown(flash) {
        /** @type {HTMLElement} */
        const closure = flash.querySelector('.closure-countdown');
        const start = new Date();
        const i = setInterval(() => {
            const now = new Date();
            const progress = 360 * (1 - ((now - start) / 30000) % 1);
            closure.style.backgroundImage = `conic-gradient(var(--clr) 0deg, var(--clr) ${progress}deg, var(--cd) ${progress}deg, var(--cd) 360deg)`;
        }, 100);
        setTimeout(() => {
            clearInterval(i);
            closure.style.backgroundImage = 'none';
        }, 30000);
    }

    function closeFlash(flash) {
        setTimeout(() => {
            flash.classList.add("hide");
        }, 0);
        setTimeout(() => {
            flash.classList.add("d-none");
            flash.parentElement.removeChild(flash);

            const flashMessagesDiv = document.querySelector(".flash-messages");
            const closeAll = flashMessagesDiv.querySelector("#close-all-alerts");
            if (closeAll) {
                const flashes = flashMessagesDiv.querySelectorAll(".flash-message");
                if (flashes.length < 2)
                    flashMessagesDiv.removeChild(closeAll);
            }
        }, 500);
    }
})