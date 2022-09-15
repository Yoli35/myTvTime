window.addEventListener("DOMContentLoaded", () => {

    const dialog = document.querySelector(".cookie-overlay");

    if (document.cookie.indexOf("accepted_cookies=") < 0) {

        dialog.classList.remove("d-none");
        dialog.classList.add("d-block");

        document.querySelector(".accept-cookies").addEventListener("click", function () {
            document.cookie = "accepted_cookies=yes;path=/;max-age=172800;SameSite=Lax"
            dialog.classList.add("d-none");
            dialog.classList.remove("d-block");
        })

        // expand depending on your needs
        document.querySelector(".close").addEventListener('click', function () {
            dialog.classList.add("d-none");
            dialog.classList.remove("d-block");
        })
    }
    else {
        dialog.parentNode.removeChild(dialog);
    }
})