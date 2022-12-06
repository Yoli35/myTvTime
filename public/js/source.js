document.addEventListener('DOMContentLoaded', () => {
    const codeDialog = document.querySelector(".show-code");

    document.querySelector(".source-code").addEventListener("click", openDialog);

    codeDialog.querySelector(".close").addEventListener('click', function () {
        closeDialog(codeDialog);
    });

    function openDialog() {
        if (typeof codeDialog.showModal === "function") {
            codeDialog.showModal();
            setTimeout(() => {
                codeDialog.classList.add("show")
            }, 0);
        } else {
            console.error("L'API <dialog> n'est pas prise en charge par ce navigateur.");
        }
    }

    function closeDialog(dialog) {
        dialog.classList.remove("show");
        setTimeout(() => {
            dialog.close()
        }, 300);
    }
});
