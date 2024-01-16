import {ToolTips} from "./ToolTips.js";

let gThis;

export class Youtube {

    /**
     * @typedef Tag
     * @type {Object}
     * @property {number} id
     * @property {string} label
     * @property {boolean} selected
     */

    /**
     * @typedef SelectedTag
     * @type {Object}
     * @property {number} id
     * @property {string} label
     * @property {number} count
     */

    /**
     * @typedef Translation
     * @type {Object}
     * @property {string} modify
     * @property {string} cancel
     * @property {string} apply
     * @property {string} select_all
     * @property {string} deselect_all
     * @property {string} modify_tag_list
     * @property {string} add_video_to_tag
     * @property {string} add_video_to_tags
     * @property {string} set_visibility
     * @property {string} delete
     * @property {string} video
     * @property {string} videos
     */

    /**
     * @typedef Globs
     * @type {Object}
     * @property {string} app_youtube_video_by_tag
     * @property {string} app_youtube_video_list_delete
     * @property {string} app_youtube_video_set_visibility
     * @property {Array.<Tag>} tags
     * @property {Translation} text
     *
     * @property {string} app_youtube
     * @property {string} yt_video_delete
     * @property {string} yt_video_add_tag
     * @property {string} yt_video_remove_tag
     */

    /**
     * @param {string} page
     * @param {Globs} globs
     */
    constructor(page, globs) {
        gThis = this;
        this.tags = globs.tags;
        this.toolTips = new ToolTips();
        this.xhr = new XMLHttpRequest();
        this.toolTips.init(null, "orange");

        if (page === 'search') {
            this.app_youtube_video_by_tag = globs.app_youtube_video_by_tag;
            this.app_youtube_video_list_delete = globs.app_youtube_video_list_delete;
            this.app_youtube_video_set_visibility = globs.app_youtube_video_set_visibility;
            this.text = globs.text;
            this.letterRatios = [];
            this.inputTagSelector = "#search-tag";
            this.initSearch();
        }
        if (page === 'video') {
            this.app_youtube = globs.app_youtube;
            this.yt_video_delete = globs.yt_video_delete;
            this.yt_video_add_tag = globs.yt_video_add_tag;
            this.yt_video_remove_tag = globs.yt_video_remove_tag;
            this.inputTagSelector = "#new-tag";
            this.initVideo();
        }
    }

    initSearch() {
        this.initHeader();
        this.initDialogs();
        this.autocomplete();

        document.querySelector(".apply").addEventListener("click", this.applyTags);
    }

    initVideo() {
        this.initDeleteVideoDialog(document.querySelector(".delete-video"))
        const trash = document.querySelector(".trash");
        trash.addEventListener("click", this.openDeleteVideoDialog);

        // const addTag = document.querySelector(".add");
        // addTag.addEventListener("click", gThis.videoAddNewTag);
        const input = document.querySelector("#new-tag");
        input.addEventListener("keyup", ({key}) => {
            if (key === "Enter") {
                gThis.addVideoTag(key);
            }
        });
        const delTags = document.querySelectorAll("div[class=close]");
        delTags.forEach(delTag => {
            delTag.addEventListener("click", () => {
                gThis.removeVideoTag(delTag);
            });
        });

        const copy = document.querySelector(".copy");
        /** @param {MouseEvent} evt */
        copy.addEventListener("click", (evt) => {
            const mouseX = evt.pageX, mouseY = evt.pageY;
            const copied = document.querySelector(".copied-text");
            navigator.clipboard.writeText("https://youtu.be/{{ video.link }}").then(r => console.log(r));
            copy.classList.add("copied");
            setTimeout(() => {
                copy.classList.remove("copied")
            }, 500);
            copied.style.top = (mouseY - (copied.clientHeight / 2)) + "px";
            copied.style.left = (mouseX - (copied.clientWidth / 2)) + "px";
            setTimeout(() => {
                copied.classList.add("visible", "move-up");
            }, 0);
            setTimeout(() => {
                copied.classList.remove("visible");
            }, 1500);
            setTimeout(() => {
                copied.classList.remove("move-up");
            }, 2500);
        });

        this.autocomplete();
    }

    autocomplete() {
        /** @type {HTMLInputElement} */
        const inputTag = document.querySelector(this.inputTagSelector);
        const field = inputTag.closest(".tags-field");
        // const selector = "input[id=" + inputTag + "]";
        inputTag.focus();

        this.createTagList(this.inputTagSelector);

        inputTag.addEventListener("input", () => {
            const tagList = field.querySelector(".tag-list");
            let value = gThis.removeAccent(inputTag.value);
            if (!value.length) {
                gThis.hideList(tagList);
                return;
            }
            if (!tagList.classList.contains("visible")) {
                gThis.showList(tagList);
            }
            const tagItems = tagList.querySelectorAll(".tag-item");
            const activeTag = tagList.querySelector(".tag-item.active");
            activeTag?.classList.remove("active");
            tagItems.forEach((tagItem) => {
                const label = gThis.removeAccent(tagItem.innerText);
                if (label.indexOf(value) === -1) {
                    tagItem.classList.add("other");
                } else {
                    tagItem.classList.remove("other");
                }
            });
        });

        inputTag.addEventListener("keydown", function (e) {
            const tagList = field.querySelector(".tag-list");
            if (e.keyCode === 40) { // arrow DOWN key
                e.preventDefault();
                if (!tagList.classList.contains("visible")) {
                    gThis.showList(tagList);
                }
                gThis.setActiveTagItem('next');
            } else if (e.keyCode === 38) { // arrow UP key
                e.preventDefault();
                if (!tagList.classList.contains("visible")) {
                    gThis.showList(tagList);
                }
                gThis.setActiveTagItem('previous');
            } else if (e.keyCode === 13) { // the ENTER key
                e.preventDefault();
                const activeTag = tagList.querySelector(".active");
                if (activeTag) {
                    e.preventDefault()
                    e.stopImmediatePropagation();
                    activeTag.click();
                } else {
                    let tags = field.parentElement.querySelector(".tags").querySelectorAll(".tag");
                    if (tags.length) gThis.applyTags();
                }
            }
        });

        document.addEventListener("click", (e) => {
            const tagList = field.querySelector(".tag-list");
            if (e.target !== tagList) this.hideList(tagList);
        });
    }

    createTagList(inputElement) {
        const searchTag = document.querySelector(inputElement);
        const field = searchTag.closest(".tags-field");
        const tagList = document.createElement("div");
        tagList.classList.add("tag-list");
        field.appendChild(tagList);

        /** var {{'id': number, 'label': string, 'selected': boolean}} tag */
        this.tags.forEach((tag) => {
            const tagItem = document.createElement("div");
            tagItem.classList.add("tag-item");
            tagItem.setAttribute("data-id", tag.id);
            tagItem.innerText = tag.label;
            if (this.inputTagSelector === "#search-tag") {
                tagItem.addEventListener("click", gThis.addSearchTag);
            } else {
                tagItem.addEventListener("click", gThis.addVideoTag);
            }
            tagList.appendChild(tagItem);
        });
    }

    showList(tagList) {
        const tagItems = tagList.querySelectorAll(".tag-item");
        tagItems.forEach((tagItem) => {
            const id = parseInt(tagItem.getAttribute("data-id"));
            const tag = gThis.tagFromList(id);
            if (tag.selected)
                tagItem.classList.add("selected");
            else
                tagItem.classList.remove("selected");
            tagItem.classList.remove("other");
        });
        gThis.currentFocus = -1;
        tagList.classList.add("visible");
        setTimeout(() => {
            tagList.classList.add("show");
        }, 0);
    }

    hideList(tagList) {
        tagList.classList.remove("show");
        setTimeout(() => {
            tagList.classList.remove("visible");
        }, 250);
        const tagItems = tagList.querySelectorAll(".tag-item");
        tagItems.forEach((tagItem) => {
            tagItem.classList.remove("active");
        });
    }

    initDeleteVideoDialog(dialog) {

        dialog.querySelector(".delete-video-done").addEventListener("click", () => {
            gThis.closeDeleteVideoDialog(dialog, true);
        })
        dialog.querySelector(".delete-video-cancel").addEventListener("click", () => {
            gThis.closeDeleteVideoDialog(dialog, false);
        })
        dialog.querySelector(".close").addEventListener('click', function () {
            gThis.closeDeleteVideoDialog(dialog, false);
        });
    }

    openDeleteVideoDialog(evt) {
        const dialog = document.querySelector("." + evt.currentTarget.getAttribute("data-dialog"));
        if (typeof dialog.showModal === "function") {
            dialog.showModal();
            setTimeout(() => {
                dialog.classList.add("show")
            }, 0);
        } else {
            console.error("L'API <dialog> n'est pas prise en charge par ce navigateur.");
            /*dialog.setAttribute("open");
            let offset = document.querySelector("html").scrollTop;
            dialog.setAttribute("style", "translate: 0 " + offset + "px;");
            dialog.classList.remove("d-none");
            dialog.classList.add("d-block");*/
        }
    }

    closeDeleteVideoDialog(dialog, lets_delete) {
        dialog.classList.remove("show");
        setTimeout(() => {
            dialog.close()
        }, 300);
        if (lets_delete) {
            gThis.deleteVideo();
        }
    }

    deleteVideo() {
        const xhr = new XMLHttpRequest();
        xhr.onload = function () {
            const response = JSON.parse(this.response);
            console.log({response});
            // window.location.href = _app_youtube;
            history.back();
        }
        xhr.open("GET", gThis.yt_video_delete);
        xhr.send();
    }

    addVideoTag(key) {
        const tags = document.querySelector(".tags");
        const videoTags = document.querySelectorAll(".tag");
        const input = document.querySelector("#new-tag");
        let newTag, id;
        console.log({key});


        if (key === "Enter") {
            id= 0;
            newTag = input.value;
        }
        else {
            /** @type HTMLElement */
            const tagItemClicked = this;
            tagItemClicked.classList.add("selected");
            id = parseInt(tagItemClicked.getAttribute("data-id"));
            newTag = tagItemClicked.innerText;

            const field = input.closest(".tags-field");
            const tagList = field.querySelector(".tag-list");
            gThis.hideList(tagList);
        }

        if (newTag.length === 0) return;

        const xhr = new XMLHttpRequest();
        xhr.onload = function () {
            const response = JSON.parse(this.response);
            // console.log({response});

            if (response['tag_added']) {
                let newTagButton = document.createElement("div");
                newTagButton.classList.add("tag", "new");
                newTagButton.appendChild(document.createTextNode('#' + response['new_tag']));
                let closeButton = document.createElement("div");
                closeButton.classList.add("close");
                closeButton.setAttribute("data-id", response['new_tag_id']);
                let circleXMark = document.createElement("i");
                circleXMark.classList.add("fa-solid", "fa-circle-xmark");
                closeButton.appendChild(circleXMark);
                newTagButton.appendChild(closeButton);
                if (videoTags.length) {
                    tags.insertBefore(newTagButton, videoTags[0]);
                } else {
                    tags.appendChild(newTagButton);
                }
                closeButton.addEventListener("click", () => {
                    gThis.removeVideoTag(closeButton);
                });
            }
            input.value = "";
            input.focus();
        }

        xhr.open("GET", gThis.yt_video_add_tag + gThis.capitalize(newTag) + '/' + id);
        xhr.send();
    }

    removeVideoTag(tag) {
        const tags = document.querySelector(".tags");
        const tagId = tag.getAttribute("data-id");
        if (tagId === null) return;

        const xhr = new XMLHttpRequest();
        xhr.onload = function () {
            const response = JSON.parse(this.response);
            // console.log({response});
            if (response['result']) {
                tags.removeChild(tag.parentElement);
            }
        }

        xhr.open("GET", gThis.yt_video_remove_tag + tagId);
        xhr.send();
    }

    /** @param {number} id
     * @param {boolean|null} selected
     * @return {Tag|null}
     */
    tagFromList(id, selected = null) {
        for (let i = 0; i < gThis.tags.length; i++) {
            /** @var {Tag} tag */
            let tag = gThis.tags[i];
            if (id === tag.id) {
                if (selected !== null) tag.selected = selected;
                return tag;
            }
        }
        return null;
    }

    selectTag(id) {
        for (let i = 0; i < gThis.tags.length; i++) {
            /** var {{'id': number, 'label': string, 'selected': boolean}} tag */
            let tag = gThis.tags[i];
            if (id === tag.id) {
                tag.selected = true;
                return tag;
            }
        }
    }

    deselectTag(id) {
        for (let i = 0; i < gThis.tags.length; i++) {
            /** var {{'id': number, 'label': string, 'selected': boolean}} tag */
            let tag = gThis.tags[i];
            if (id === tag.id) {
                tag.selected = false;
                return;
            }
        }
    }

    addSearchTag() {
        const searchTag = document.querySelector(gThis.inputTagSelector);
        /** @type HTMLElement */
        const tagItemClicked = this;
        const id = parseInt(tagItemClicked.getAttribute("data-id"));
        tagItemClicked.classList.add("selected");
        const tag = gThis.selectTag(id);
        let newTag = document.createElement("div");
        newTag.classList.add("tag");
        newTag.appendChild(document.createTextNode(tag.label));
        newTag.setAttribute("data-id", id);
        let deleteButton = document.createElement("div");
        deleteButton.classList.add("delete");
        let xmark = document.createElement("i")
        xmark.classList.add("fa-solid", "fa-square-xmark");
        deleteButton.appendChild(xmark);
        deleteButton.addEventListener("click", gThis.removeTag);
        newTag.appendChild(deleteButton);
        document.querySelector(".tags").appendChild(newTag);

        searchTag.value = "";
        searchTag.focus();
    }

    removeTag() {
        /** @type HTMLElement */
        const xmark = this;
        let tag = xmark.parentElement;
        let id = parseInt(tag.getAttribute("data-id"));
        gThis.deselectTag(id);
        document.querySelector(".tags").removeChild(tag);
    }

    applyTags() {
        const tags = document.querySelector(".tags").querySelectorAll(".tag");
        /** @type {HTMLSelectElement} */
        const methodSelect = document.querySelector("#method");
        let method = methodSelect.value;
        let list = "";
        if (tags.length === 0) return;
        tags.forEach(tag => {
            if (list.length) list += ",";
            list += tag.getAttribute("data-id");
        });
        // console.log(list);
        const xhr = gThis.xhr;
        xhr.onload = function () {
            /** @var {{"block": string, "videoCount": number}} data */
            const data = JSON.parse(this.response);
            /** @var {{"content": string}} block */
            const block = data.block;
            document.querySelector(".results").innerHTML = block.content;
            if (data.videoCount) {
                const resultTitle = document.querySelector(".results").querySelector(".result-header");
                const modifyTools = document.createElement("div");
                modifyTools.classList.add("modify-tools");
                const modifyButton = document.createElement("button");
                modifyButton.setAttribute("id", "modify-tool")
                modifyButton.innerText = gThis.text.modify;
                modifyButton.addEventListener("click", gThis.prepareSelection);
                modifyTools.appendChild(modifyButton);
                resultTitle.appendChild(modifyTools);
            }
        }
        xhr.open("GET", gThis.app_youtube_video_by_tag + '?tags=' + list + '&m=' + method);
        xhr.send();

    }

    prepareSelection(e) {
        e.stopImmediatePropagation();
        const resultTitle = document.querySelector(".results").querySelector(".result-header");
        const modifyTools = resultTitle.querySelector(".modify-tools");
        const modifyButton = modifyTools.querySelector("#modify-tool");
        modifyButton.innerText = gThis.text.cancel;
        modifyButton.removeEventListener("click", gThis.prepareSelection)
        modifyButton.addEventListener("click", gThis.cancelSelection);

        const selectAllButton = document.createElement("button");
        selectAllButton.setAttribute("id", "select-all-tool");
        selectAllButton.innerText = gThis.text.select_all;
        selectAllButton.addEventListener("click", gThis.selectAllVideo);
        modifyTools.insertBefore(selectAllButton, modifyButton);

        const deselectAllButton = document.createElement("button");
        deselectAllButton.setAttribute("id", "deselect-all-tool");
        deselectAllButton.innerText = gThis.text.deselect_all;
        deselectAllButton.addEventListener("click", gThis.deselectAllVideo);
        modifyTools.insertBefore(deselectAllButton, selectAllButton);

        const deleteSelection = document.createElement("button");
        deleteSelection.setAttribute("id", "delete-tag-tool");
        deleteSelection.innerHTML = '<i class="fa-solid fa-trash-can"></i>';
        deleteSelection.setAttribute("data-title", gThis.text.delete);
        deleteSelection.addEventListener("click", gThis.openDeleteDialog);
        modifyTools.insertBefore(deleteSelection, deselectAllButton);
        gThis.toolTips.initElement(deleteSelection);

        const addTagSelection = document.createElement("button");
        addTagSelection.setAttribute("id", "add-tag-tool");
        addTagSelection.innerHTML = '<i class="fa-solid fa-plus"></i>';
        addTagSelection.setAttribute("data-title", gThis.text.add_video_to_tags);
        addTagSelection.addEventListener("click", gThis.addVideoSelection);
        modifyTools.insertBefore(addTagSelection, deleteSelection);
        gThis.toolTips.initElement(addTagSelection);

        const modifyTagSelection = document.createElement("button");
        modifyTagSelection.setAttribute("id", "modify-tag-tool");
        modifyTagSelection.innerHTML = '<i class="fa-solid fa-pen"></i>';
        modifyTagSelection.setAttribute("data-title", gThis.text.modify_tag_list);
        modifyTagSelection.addEventListener("click", gThis.openModifyTagsDialog);
        modifyTools.insertBefore(modifyTagSelection, addTagSelection);
        gThis.toolTips.initElement(modifyTagSelection);

        const setVisibility = document.createElement("button");
        setVisibility.setAttribute("id", "set-visibility-tool");
        setVisibility.innerHTML = '<i class="fa-solid fa-eye"></i>';
        setVisibility.setAttribute("data-title", gThis.text.set_visibility);
        setVisibility.addEventListener("click", gThis.openVisibilityDialog);
        modifyTools.insertBefore(setVisibility, modifyTagSelection);
        gThis.toolTips.initElement(setVisibility);

        gThis.hideTagTools();

        const results = document.querySelectorAll(".yt-result");
        results.forEach((result) => {
            const details = result.querySelector(".details");
            const selectVideoDiv = document.createElement("div");
            selectVideoDiv.classList.add("select-video");
            selectVideoDiv.addEventListener("click", gThis.selectVideo);
            details.appendChild(selectVideoDiv);
        });
    }

    selectVideo() {
        /** @type HTMLDivElement */
        const div = this;
        div.classList.toggle("selected");

        if (gThis.isThereASelectedVideo())
            gThis.showTagTools();
        else
            gThis.hideTagTools();
    }

    selectAllVideo() {
        const results = document.querySelectorAll(".yt-result");
        results.forEach((result) => {
            const selectVideoDiv = result.querySelector(".select-video");
            selectVideoDiv.classList.add("selected");
        });
        gThis.showTagTools();
    }

    deselectAllVideo() {
        const results = document.querySelectorAll(".yt-result");
        results.forEach((result) => {
            const selectVideoDiv = result.querySelector(".select-video");
            selectVideoDiv.classList.remove("selected");
        });
        gThis.hideTagTools();
    }

    isThereASelectedVideo() {
        const wrapper = document.querySelector(".wrapper");
        const selectedVideos = wrapper.querySelectorAll(".selected");
        return selectedVideos.length;
    }

    showTagTools() {
        const resultTitle = document.querySelector(".results").querySelector(".result-header");
        const modifyTools = resultTitle.querySelector(".modify-tools");
        const tagTools = modifyTools.querySelectorAll("button:has(i)");
        tagTools.forEach((tool) => {
            tool.classList.remove("d-none");
        });
    }

    hideTagTools() {
        const resultTitle = document.querySelector(".results").querySelector(".result-header");
        const modifyTools = resultTitle.querySelector(".modify-tools");
        const tagTools = modifyTools.querySelectorAll("button:has(i)");
        tagTools.forEach((tool) => {
            tool.classList.add("d-none");
        });
    }

    initDialogs() {
        this.initModifyTagsDialog();
        this.initDeleteDialog();
        this.initVisibilityDialog();
    }

    initDeleteDialog() {
        const dialog = document.querySelector('#delete-video-dialog');

        dialog.addEventListener('close', () => {
            document.querySelector("body").classList.remove("frozen");
            if (dialog.returnValue === "delete") {
                const videoSelectionButton = document.querySelectorAll(".select-video.selected");
                let ids = gThis.selectedVideoIds(videoSelectionButton);

                const xhr = gThis.xhr;
                xhr.onload = function () {
                    const result = JSON.parse(this.response);
                    if (result.success) {
                        const flashes = document.querySelector(".flash-messages");
                        const flashMessage = document.createElement('div');
                        flashMessage.classList.add("flash-message", "success");
                        flashMessage.innerText = result.message;
                        const close = document.createElement("div");
                        close.classList.add("close");
                        const i = document.createElement("i");
                        i.classList.add("fa-solid", "fa-circle-xmark");
                        close.appendChild(i);
                        close.addEventListener("click", () => {
                            flashes.removeChild(flashMessage)
                        });
                        flashMessage.appendChild(close);
                        flashes.appendChild(flashMessage);

                        const wrapper = document.querySelector(".wrapper");
                        videoSelectionButton.forEach((button) => {
                            const ytResult = button.closest(".yt-result");
                            ytResult.classList.add("deleted");
                            setTimeout(() => {
                                wrapper.removeChild(ytResult);
                            }, 500);
                        });
                    }
                }
                xhr.open("GET", gThis.app_youtube_video_list_delete + '?list=' + ids);
                xhr.send();
            }
        });
    }

    openDeleteDialog() {
        const dialog = document.querySelector('#delete-video-dialog');
        const videoList = dialog.querySelector(".video-list");
        videoList.innerHTML = "";
        const videoSelectionButton = document.querySelectorAll(".select-video.selected");

        dialog.querySelector(".dialog-title").innerText = videoSelectionButton.length + " video" + (videoSelectionButton.length > 1 ? "s" : "");
        videoSelectionButton.forEach((button) => {
            const result = button.closest(".yt-result");
            const channel = result.querySelector(".channel").querySelector("img").getAttribute("alt");
            const title = result.querySelector(".infos").querySelector(".info").innerText;
            const item = document.createElement("div");
            item.classList.add("video-item");
            item.appendChild(document.createTextNode(channel + " — " + title));
            videoList.appendChild(item);
        });

        document.querySelector("body").classList.add("frozen");
        dialog.showModal();
    }

    addVideoSelection() {

    }

    initModifyTagsDialog() {
        const dialog = document.querySelector('#modify-tags-dialog');
        const availableTagList = dialog.querySelector(".available-tags");
        const selectedTagList = dialog.querySelector(".selected-tags");
        const input = dialog.querySelector("#search-tag-to-modify-list");

        dialog.addEventListener('close', () => {
            document.querySelector("body").classList.remove("frozen");
            if (dialog.returnValue === "apply-tags") {

            }
        });

        selectedTagList.addEventListener("dragover", gThis.dropoverTagList);
        selectedTagList.addEventListener("dragenter", gThis.dragenterTagList);
        selectedTagList.addEventListener("dragleave", gThis.dragleaveTagList);
        selectedTagList.addEventListener("drop", gThis.dropTag);

        input.addEventListener("input", () => {
            const value = gThis.removeAccent(input.value);
            const tagItems = availableTagList.querySelectorAll(".tag-item");
            tagItems.forEach((tagItem) => {
                const label = gThis.removeAccent(tagItem.innerText);
                if (label.indexOf(value) === -1) {
                    tagItem.classList.add("other");
                } else {
                    tagItem.classList.remove("other");
                }
            });
        });
    }

    openModifyTagsDialog() {
        const dialog = document.querySelector('#modify-tags-dialog');
        const availableTagList = dialog.querySelector(".available-tags");
        const selectedTagList = dialog.querySelector(".selected-tags");
        const wrapper = document.querySelector(".youtube-search").querySelector(".wrapper");
        const videos = wrapper.querySelectorAll(".yt-result:has(.select-video.selected)");
        const videoCount = videos.length;
        /** @var {Array.<SelectedTag>} selectedTags */
        let selectedTags = [];

        availableTagList.innerHTML = "";
        selectedTagList.innerHTML = "";

        videos.forEach((video) => {
            const tags = video.querySelectorAll(".tag");
            tags.forEach((tag) => {
                const id = parseInt(tag.getAttribute("data-id"));
                const label = tag.innerText;
                let found = false;
                for (let i = 0; i < selectedTags.length; i++) {
                    if (selectedTags[i].id === id) {
                        selectedTags[i].count++;
                        found = true;
                        break;
                    }
                }
                if (!found) {
                    selectedTags.push({id: id, label: label, count: 1});
                }
            });
        });
        selectedTags = selectedTags.filter((tag) => {
            return tag.count === videoCount
        });
        selectedTags.forEach((tag) => {
            const tagItem = document.createElement("div");
            tagItem.classList.add("tag-item");
            tagItem.setAttribute("data-id", tag.id);
            tagItem.innerText = tag.label;
            const deleteButton = document.createElement("div");
            deleteButton.classList.add("delete");
            const xmark = document.createElement("i");
            xmark.classList.add("fa-solid", "fa-square-xmark");
            deleteButton.appendChild(xmark);
            deleteButton.addEventListener("click", gThis.removeTagFromSelected);
            tagItem.appendChild(deleteButton);
            selectedTagList.appendChild(tagItem);
        });

        gThis.tags.forEach((tag) => {
            const tagItem = document.createElement("div");
            tagItem.classList.add("tag-item");
            if (tag.selected) tagItem.classList.add("selected");
            tagItem.setAttribute("data-id", tag.id);
            tagItem.setAttribute("draggable", "true");
            tagItem.innerText = tag.label;
            tagItem.addEventListener("dragstart", gThis.dragstartTag);
            tagItem.addEventListener("dragend", gThis.dragendTag);
            const addButton = document.createElement("div");
            addButton.classList.add("add");
            const plus = document.createElement("i");
            plus.classList.add("fa-solid", "fa-square-plus");
            addButton.appendChild(plus);
            // addButton.addEventListener("click", gThis.addTagToSelected);
            tagItem.appendChild(addButton);
            availableTagList.appendChild(tagItem);
        });

        document.querySelector("body").classList.add("frozen");
        dialog.showModal();
    }

    initVisibilityDialog() {
        const dialog = document.querySelector('#set-visibility-dialog');

        dialog.addEventListener('close', () => {
            document.querySelector("body").classList.remove("frozen");
            if (dialog.returnValue === "apply-visibility") {
                const videoSelectionButton = document.querySelectorAll(".select-video.selected");
                const ids = gThis.selectedVideoIds(videoSelectionButton);
                const visibility = dialog.querySelector("#visibility-select").value;

                const xhr = gThis.xhr;
                xhr.onload = function () {
                    const result = JSON.parse(this.response);
                    if (result.success) {
                        console.log({result});
                    }
                }
                xhr.open("GET", gThis.app_youtube_video_set_visibility + '?ids=' + ids + '&visibility=' + visibility);
                xhr.send();
            }
        });
    }

    openVisibilityDialog() {
        const dialog = document.querySelector('#set-visibility-dialog');

        document.querySelector("body").classList.add("frozen");
        dialog.showModal();
    }

    selectedVideoIds(videoSelectionButton) {
        let ids = "";
        videoSelectionButton.forEach((button) => {
            const ytResult = button.closest(".yt-result");
            if (ids.length) ids += ',';
            ids += ytResult.getAttribute("data-id");
        });
        return ids;
    }

    dropoverTagList(e) {
        e.preventDefault();
        console.log("allowDrop");
    }

    dragenterTagList(e) {
        e.preventDefault();
        console.log("dragenter");
        e.target.classList.add("dragover");
    }

    dragleaveTagList(e) {
        e.preventDefault();
        console.log("dragleave");
        e.target.classList.remove("dragover");
    }

    dragstartTag(e) {
        e.currentTarget.classList.add("dragging");
        e.dataTransfer.clearData();
        e.dataTransfer.setData("text/plain", e.target.getAttribute("data-id"));
    }

    dragendTag(e) {
        e.currentTarget.classList.remove("dragging");
    }

    dropTag(e) {
        e.preventDefault();
        const id = parseInt(e.dataTransfer.getData("text/plain"));
        const tag = gThis.tagFromList(id, true);
        const tagItem = document.createElement("div");
        tagItem.classList.add("tag-item");
        tagItem.setAttribute("data-id", tag.id);
        tagItem.innerText = tag.label;
        const removeButton = document.createElement("div");
        removeButton.classList.add("add");
        const xmark = document.createElement("i");
        xmark.classList.add("fa-solid", "fa-square-xmark");
        removeButton.appendChild(xmark);
        removeButton.addEventListener("click", gThis.removeTagFromSelected);
        tagItem.appendChild(removeButton);
        e.target.appendChild(tagItem);

        const availableTag = document.querySelector(".available-tags").querySelector(".tag-item[data-id='" + id + "']");
        availableTag.classList.add("selected");

        const selectedTagList = document.querySelector(".selected-tags");
        selectedTagList.classList.remove("dragover");
    }

    removeTagFromSelected(e) {
        e.stopImmediatePropagation();
        const tagItem = e.currentTarget.closest(".tag-item");
        const id = parseInt(tagItem.getAttribute("data-id"));
        // const tag = gThis.tagFromList(id, false);
        const availableTag = document.querySelector(".available-tags").querySelector(".tag-item[data-id='" + id + "']");
        availableTag.classList.remove("selected");
        tagItem.parentElement.removeChild(tagItem);
    }

    cancelSelection(e) {
        e.stopImmediatePropagation();
        const resultTitle = document.querySelector(".results").querySelector(".result-header");
        const modifyTools = resultTitle.querySelector(".modify-tools");
        const modifyButton = modifyTools.querySelector("#modify-tool");
        const selectAllButton = modifyTools.querySelector("#select-all-tool");
        const deselectAllButton = modifyTools.querySelector("#deselect-all-tool");
        const deleteTagButton = modifyTools.querySelector('#delete-tag-tool');
        const addTagButton = modifyTools.querySelector('#add-tag-tool');
        const modifyTagButton = modifyTools.querySelector('#modify-tag-tool');
        modifyButton.innerText = gThis.text.modify;
        modifyButton.removeEventListener("click", gThis.cancelSelection);
        modifyButton.addEventListener("click", gThis.prepareSelection);
        modifyTools.removeChild(selectAllButton);
        modifyTools.removeChild(deselectAllButton);
        modifyTools.removeChild(deleteTagButton);
        modifyTools.removeChild(addTagButton);
        modifyTools.removeChild(modifyTagButton);

        const results = document.querySelectorAll(".yt-result");
        results.forEach((result) => {
            const details = result.querySelector(".details");
            const selectVideoDiv = result.querySelector(".select-video");
            details.removeChild(selectVideoDiv);
        });
    }

    setActiveTagItem(sibling) {
        const tagList = document.querySelector(".tag-list");
        const visibleList = tagList.querySelectorAll("div.tag-item:not(.other)");
        const tagItem = tagList.querySelector(".tag-item.active");
        let index, maxIndex = visibleList.length - 1;

        if (!tagItem) {
            if (sibling === "next") index = 0;
            if (sibling === "previous") index = maxIndex;
        } else {
            tagItem.classList.remove("active");
            const tagId = tagItem.getAttribute("data-id");
            for (let i = 0; i <= maxIndex; i++) {
                if (tagId === visibleList[i].getAttribute("data-id")) {
                    index = i;
                    break;
                }
            }
            if (sibling === "next") {
                index++;
                if (index > maxIndex) index = 0;
            }
            if (sibling === "previous") {
                index--;
                if (index < 0) index = maxIndex;
            }
        }
        const nextItem = visibleList[index];

        nextItem.classList.add("active");
        nextItem.scrollIntoView(false);
    }

    removeAccent(str) {
        str = str.toLowerCase();

        let from = `àáäâèéëêìíïîòóöôùúüûñç`;
        let to = "aaaaeeeeiiiioooouuuunc";
        for (let i = 0, l = from.length; i < l; i++) {
            str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
        }

        return str;
    }

    initHeader() {
        const header = document.querySelector(".header");
        let ticking = false;
        let letters, animatedH1, index = 0;

        animatedH1 = document.createElement("div");
        animatedH1.classList.add("animated-h1");
        animatedH1 = header.insertBefore(animatedH1, document.querySelector(".backdrop"));
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
            gThis.letterRatios[index] = 2 * (Math.random() - .5);
            index++;
        })
        gThis.setH1();
        window.addEventListener('resize', gThis.setH1);

        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(function () {
                    gThis.setH1();
                    ticking = false;
                });
            }
            ticking = true;
        });
        header.setAttribute("style", "background-color: transparent;");
    }

    setH1() {
        const header = document.querySelector(".header");
        const h1 = document.querySelector(".animated-h1");
        const parts = h1.querySelectorAll(".part");
        let left, ratio, top, n = 0;

        ratio = (header.clientHeight - window.scrollY) / header.clientHeight;
        top = ((header.clientHeight + window.scrollY) - h1.clientHeight) / 2;
        left = (header.clientWidth - h1.clientWidth) / 2;

        if (ratio > 1) ratio = 1;
        if (ratio < 0) ratio = 0;

        parts.forEach(part => {
            part.setAttribute("style", "transform: rotate(" + (720 * (1 - ratio) * gThis.letterRatios[n++]) + "deg);");
        })
        h1.setAttribute("style", "left: " + left.toString() + "px; top: " + top.toString() + "px; opacity: " + ratio + "; transform: scale(" + (1 + (5 * (1 - ratio))) + ")");
    }

    // Object.defineProperty(String.prototype, 'capitalize', {
    // value: function () {
    //     return this.charAt(0).toUpperCase() + this.slice(1);
    // }, enumerable: false});
    capitalize(text) {
        return text.charAt(0).toUpperCase() + text.slice(1);
    }
}
