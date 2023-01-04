window.addEventListener("DOMContentLoaded", () => {
    const flashes = document.querySelectorAll(".flash-message");

    flashes.forEach(flash => {
        flash.querySelector(".close").addEventListener("click", () => {
            closeFlash(flash);
        })
        flash.querySelector(".flash-accept")?.addEventListener("click", (e) => {
            let id = e.currentTarget.getAttribute("data-id");

            const xhr = new XMLHttpRequest();
            xhr.onload = function () {
                closeFlash(flash);
            }
            xhr.open("GET", "/user/friendship/accept/" + id);
            xhr.send();
        })
        flash.querySelector(".flash-reject")?.addEventListener("click", (e) => {
            let id = e.currentTarget.getAttribute("data-id");

            const xhr = new XMLHttpRequest();
            xhr.onload = function () {
                closeFlash(flash);
            }
            xhr.open("GET", "/user/friendship/reject/" + id);
            xhr.send();
        })
    })

    function closeFlash(flash) {
        setTimeout(()=>{
            flash.classList.add("hide");
        }, 0);
        setTimeout(()=>{
            flash.classList.add("d-none");
        }, 500);

    }
})