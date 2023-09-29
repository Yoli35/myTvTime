let thisGlobal;

export class MovieList {

    /**
     * @type string
     */
    locale;
    /**
     * @type {HTMLDialogElement}
     */
    confirmDialog;

    constructor() {
        thisGlobal = this;
        this.locale = document.querySelector("html").getAttribute("lang");
        this.confirmDialog = document.querySelector("dialog[class=confirm-deletion]");

        this.initTools(this.confirmDialog);
        this.initLayout();
    }

    initTools(dialog) {
        const tools = document.querySelectorAll(".tools");
        const lists = document.querySelectorAll(".movie-list");

        tools.forEach(tool => {
            tool.querySelector(".fa-pen-to-square").addEventListener("click", this.editMovieList);
            // tool.querySelector(".fa-eye-slash").addEventListener("click", hideMovieList
            tool.querySelector(".fa-trash-can").addEventListener("click", this.deleteMovieList);
        });

        lists.forEach(list => {
            list.addEventListener("mouseenter", this.showTools);
            list.addEventListener("mouseleave", this.hideTools);
        });


        dialog.querySelector(".delete-done").addEventListener("click", () => {
            thisGlobal.closeDialog(dialog, true);
        })
        dialog.querySelector(".delete-cancel").addEventListener("click", () => {
            thisGlobal.closeDialog(dialog, false);
        })
        dialog.querySelector(".close").addEventListener('click', () => {
            thisGlobal.closeDialog(dialog, false);
        });
    }

    initLayout() {
        const layouts = document.querySelectorAll('.movie-list-layout-item');
        layouts.forEach((layout) => {
            layout.addEventListener('click', (e) => {
                layouts.forEach((layout) => {
                    layout.classList.remove('active');
                });
                e.currentTarget.classList.add('active');
                const type = e.currentTarget.getAttribute('data-type');
                const wrapper = document.querySelector('.wrapper');
                wrapper.classList.remove('roomy', 'list');
                wrapper.classList.add(type);
                thisGlobal.setLayoutMovieListCookie(type);

                thisGlobal.initDragAndDrop(wrapper);
            });
        });
        this.initDragAndDrop(document.querySelector('.wrapper'), true);
    }

    initDragAndDrop(wrapper, initial = false) {
        if (wrapper.classList.contains('list')) {
            thisGlobal.addDragAndDrop();
        } else {
            if (!initial) {
                thisGlobal.removeDragAndDrop();
            }
        }
    }

    addDragAndDrop() {
        const listItems = document.querySelectorAll('.movie-list');
        const wrapper = document.querySelector('.wrapper');

        listItems.forEach(item => {
            item.setAttribute('draggable', 'true');
            item.addEventListener('dragstart', thisGlobal.dragStart);
            item.addEventListener('dragover', thisGlobal.dragOver);
            item.addEventListener('dragenter', thisGlobal.dragEnter);
            item.addEventListener('dragleave', thisGlobal.dragLeave);
            item.addEventListener('drop', thisGlobal.dragDrop);
            item.addEventListener('dragend', thisGlobal.dragEnd);
        });

        // Ajouter les gestionnaires d'événements pour la div wrapper
        wrapper.addEventListener('dragover', thisGlobal.dragOver);
        wrapper.addEventListener('dragenter', thisGlobal.dragEnter);
        wrapper.addEventListener('dragleave', thisGlobal.dragLeave);
        wrapper.addEventListener('drop', thisGlobal.dragDrop);
    }

    removeDragAndDrop() {
        const listItems = document.querySelectorAll('.movie-list');
        const wrapper = document.querySelector('.wrapper');

        listItems.forEach(item => {
            item.removeAttribute('draggable');
            item.removeEventListener('dragstart', thisGlobal.dragStart);
            item.removeEventListener('dragover', thisGlobal.dragOver);
            item.removeEventListener('dragenter', thisGlobal.dragEnter);
            item.removeEventListener('dragleave', thisGlobal.dragLeave);
            item.removeEventListener('drop', thisGlobal.dragDrop);
            item.removeEventListener('dragend', thisGlobal.dragEnd);
        });

        wrapper.removeEventListener('dragover', thisGlobal.dragOver);
        wrapper.removeEventListener('dragenter', thisGlobal.dragEnter);
        wrapper.removeEventListener('dragleave', thisGlobal.dragLeave);
        wrapper.removeEventListener('drop', thisGlobal.dragDrop);
    }

    dragStart(e) {
        e.dataTransfer.setData('text/plain', e.currentTarget.getAttribute('data-id'));
        e.currentTarget.classList.add('dragging');
    }

    dragOver(e) {
        e.preventDefault();
        e.currentTarget.classList.add('dragover');
    }

    dragEnter(e) {
        e.preventDefault();
        e.currentTarget.classList.add('dragover');
    }

    dragLeave(e) {
        e.currentTarget.classList.remove('dragover');
    }

    dragDrop(e) {
        const wrapper = document.querySelector('.wrapper');
        if (this === wrapper) {
            wrapper.classList.remove('dragover');
            return;
        }
        const draggedItemId = e.dataTransfer.getData('text/plain');
        const selector = ".movie-list[data-id=\"" + draggedItemId + "\"]";
        const draggedItem = wrapper.querySelector(selector);
        const overItem = e.currentTarget;
        if (draggedItem !== wrapper && draggedItem !== overItem) {
            wrapper.insertBefore(draggedItem, overItem);
        }
        overItem.classList.remove('dragover');
    }

    dragEnd(e) {
        e.currentTarget.classList.remove('dragging');
    }

    openDialog(dialog, id, elem) {
        const infos = elem.closest(".infos");
        dialog.querySelector("span").innerHTML = infos.querySelector(".name").innerHTML;
        dialog.setAttribute("data-id", id);

        if (typeof dialog.showModal === "function") {
            dialog.showModal();
            setTimeout(() => {
                dialog.classList.add("show")
            }, 0);
        } else {
            console.error("L'API <dialog> n'est pas prise en charge par ce navigateur.");
        }
    }

    closeDialog(dialog, deleteMovieList) {
        let deletedId = parseInt(dialog.getAttribute("data-id"));
        dialog.removeAttribute("data-id");
        dialog.classList.remove("show");
        setTimeout(() => {
            dialog.close()
        }, 300);
        if (deleteMovieList) {
            const xhr = new XMLHttpRequest();
            xhr.onload = () => {
                const selector = ".movie-list[data-id=\"" + deletedId + "\"]";
                const list = document.querySelector(selector);
                setTimeout(() => {
                    list.classList.add("deleted");
                }, 0);
                setTimeout(() => {
                    const wrapper = list.closest(".wrapper");
                    wrapper.removeChild(list);
                }, 300);
            }
            xhr.open("GET", "/" + locale + "/movie/list/delete/" + deletedId);
            xhr.send();
        }
    }

    showTools(evt) {
        const tools = evt.currentTarget.querySelector(".tools");
        tools.classList.add("visible");
    }

    hideTools(evt) {
        const tools = evt.currentTarget.querySelector(".tools");
        tools.classList.remove("visible");
    }

    editMovieList(evt) {
        const id = evt.currentTarget.parentElement.getAttribute("id");
        console.log('edit', {id});
        evt.preventDefault();
        window.location.href = "/" + locale + "/movie/list/edit/" + id;
    }

    // hideMovieList(evt) {
    //     const id = evt.currentTarget.parentElement.getAttribute("id");
    //     console.log('hide', {id});
    //     evt.preventDefault();
    // }

    deleteMovieList(evt) {
        const id = evt.currentTarget.parentElement.getAttribute("id");
        console.log('delete', {id});
        evt.preventDefault();
        openDialog(confirmDialog, id, evt.currentTarget);
    }

    getMovieListCookie() {
        return JSON.parse(decodeURIComponent(document.cookie.split('; ').find(row => row.startsWith('movie_list=')).split('=')[1]));
    }

    setMovieListCookie(cookie) {
        const time = new Date();
        time.setFullYear(time.getFullYear() + 1);
        document.cookie = "movie_list=" + encodeURIComponent(JSON.stringify(cookie)) + "; expires=" + time.toUTCString() + "; path=/";
        console.log(thisGlobal.getMovieListCookie());
    }

    // getLayoutMovieListCookie() {
    //     return thisGlobal.getMovieListCookie().layout;
    // }

    setLayoutMovieListCookie(layout) {
        const cookie = thisGlobal.getMovieListCookie();
        cookie.layout = layout;
        thisGlobal.setMovieListCookie(cookie);
    }
}