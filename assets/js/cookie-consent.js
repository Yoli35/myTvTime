window.addEventListener("DOMContentLoaded", () => {

    const dialog = document.querySelector("#cookies");
    const actAsADialog = typeof dialog.showModal === 'function';

    if (!actAsADialog) {
        dialog.classList.add("d-none", "classic");
    }

    if (document.cookie.indexOf("accepted_cookies=") < 0) {

        openDialog();

        document.querySelector(".accept-cookies").addEventListener("click", function () {
            document.cookie = "accepted_cookies=yes;path=/;max-age=604800;SameSite=Lax"
            closeDialog();
        })
        document.querySelector(".close").addEventListener('click', function () {
            closeDialog();
        });

        dialog.addEventListener("keyup", function(evt) {
            if (evt.keyCode === 13 || evt.key === 13 || evt.code === 'Enter' || evt.code === 'NumpadEnter') {
                document.querySelector(".accept-cookies").click();
            }
            if (evt.keyCode === 27 || evt.key === 27 || evt.code === 'Escape') {
                document.querySelector(".close").click();
            }
        })
    } else {
        dialog.parentElement.removeChild(dialog);
    }

    function openDialog() {
        if (actAsADialog) {
            dialog.showModal();
        } else {
            dialog.classList.remove("d-none");
            dialog.classList.add("d-block");
        }
    }

    function closeDialog() {
        if (actAsADialog) {
            dialog.close();
        } else {
            dialog.classList.add("d-none");
            dialog.classList.remove("d-block");
        }
    }
})