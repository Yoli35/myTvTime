window.addEventListener("DOMContentLoaded", () => {
    const flashes = document.querySelectorAll(".flash-message");

    flashes.forEach(flash => {
        flash.querySelector(".close").addEventListener("click", () => {
            setTimeout(()=>{
                flash.classList.add("hide");
            }, 0);
            setTimeout(()=>{
                flash.classList.add("d-none");
            }, 500);
        })
    })
})