window.addEventListener("DOMContentLoaded", () => {

    const dialog = document.querySelector("#cookies");
    const actAsADialog = typeof dialog.showModal === 'function';

    if (!actAsADialog) {
        dialog.classList.add("d-none", "classic");
    }

    if (document.cookie.indexOf("accepted_cookies=") < 0) {

        openDialog();

        document.querySelector(".accept-cookies").addEventListener("click", () => {
            document.cookie = "accepted_cookies=yes;path=/;max-age=172800;SameSite=Lax"
            closeDialog();
        })
        document.querySelector(".close").addEventListener('click', function () {
            closeDialog();
        });
    } else {
        dialog.parentNode.removeChild(dialog);
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