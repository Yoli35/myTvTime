window.addEventListener("DOMContentLoaded", () => {

    if (document.cookie.indexOf("accepted_cookies=") < 0) {

        document.querySelector(".cookie-overlay").classList.remove("d-none");
        document.querySelector(".cookie-overlay").classList.add("d-block");
    }

    document.querySelector(".accept-cookies").addEventListener("click", function() {
        document.cookie = "accepted_cookies=yes;path=/;max-age=172800;SameSite=Lax"
        document.querySelector(".cookie-overlay").classList.add("d-none");
        document.querySelector(".cookie-overlay").classList.remove("d-block");
    })

    // expand depending on your needs
    $('.close-cookies').on('click', function() {
        document.querySelector(".cookie-overlay").classList.add("d-none");
        document.querySelector(".cookie-overlay").classList.remove("d-block");
    })
})