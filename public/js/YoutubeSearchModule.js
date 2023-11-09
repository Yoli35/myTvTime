import {ToolTips} from "./ToolTips.js";
let gThis;

export class YoutubeSearch {

    /**
     * @typedef Tag
     * @type {Object}
     * @property {number} id
     * @property {string} label
     * @property {boolean} selected
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
     * @property {string} delete
     * @property {string} video
     * @property {string} videos
     */

    /**
     * @typedef Globs
     * @type {Object}
     * @property {string} app_youtube_video_by_tag
     * @property {string} app_youtube_video_list_delete
     * @property {Array.<Tag>} tags
     * @property {Translation} text
     */

    /**
     * @param {Globs} globs
     */
    constructor(globs) {
        gThis = this;
        this.tags = globs.tags;
        this.app_youtube_video_by_tag = globs.app_youtube_video_by_tag;
        this.app_youtube_video_list_delete = globs.app_youtube_video_list_delete;
        this.text = globs.text;
        this.letterRatios = [];
        this.toolTips = new ToolTips();
        this.xhr = new XMLHttpRequest();

        this.init();
    }

    init() {
        this.initHeader();
        this.initDialogs();
        this.autocomplete("#search-tag");
        this.toolTips.init();

        document.querySelector(".apply").addEventListener("click", this.applyTags);
    }

    autocomplete(inputTag) {
        /** @type {HTMLInputElement} */
        const searchTag = document.querySelector(inputTag);
        const field = searchTag.closest(".field");
        // const selector = "input[id=" + inputTag + "]";
        searchTag.focus();

        this.createTagList(inputTag);

        searchTag.addEventListener("input", () => {
            const tagList = field.querySelector(".tag-list");
            let value = gThis.removeAccent(searchTag.value);
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

        searchTag.addEventListener("keydown", function (e) {
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
        const field = searchTag.closest(".field");
        const tagList = document.createElement("div");
        tagList.classList.add("tag-list");
        field.appendChild(tagList);

        /** var {{'id': number, 'label': string, 'selected': boolean}} tag */
        this.tags.forEach((tag) => {
            const tagItem = document.createElement("div");
            tagItem.classList.add("tag-item");
            tagItem.setAttribute("data-id", tag.id);
            tagItem.innerText = tag.label;
            tagItem.addEventListener("click", gThis.addTag);
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

    tagFromList(id) {
        for (let i = 0; i < gThis.tags.length; i++) {
            /** var {{'id': number, 'label': string, 'selected': boolean}} tag */
            let tag = gThis.tags[i];
            if (id === tag.id) return tag;
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

    addTag() {
        const searchTag = document.querySelector("input[id=search-tag]");
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
        tagTools.forEach((tool)=>{
            tool.classList.remove("d-none");
        });
    }

    hideTagTools() {
        const resultTitle = document.querySelector(".results").querySelector(".result-header");
        const modifyTools = resultTitle.querySelector(".modify-tools");
        const tagTools = modifyTools.querySelectorAll("button:has(i)");
        tagTools.forEach((tool)=>{
            tool.classList.add("d-none");
        });
    }

    initDialogs() {
        this.initModifyTagsDialog();
        this.initDeleteDialog();
    }

    initDeleteDialog() {
        const dialog = document.querySelector('#delete-video-dialog');

        dialog.addEventListener('close', () => {
            document.querySelector("body").classList.remove("frozen");
            if (dialog.returnValue === "delete") {
                const videoSelectionButton = document.querySelectorAll(".select-video.selected");
                let ids = "";

                videoSelectionButton.forEach((button)=>{
                    const ytResult = button.closest(".yt-result");
                    if (ids.length) ids += ',';
                    ids += ytResult.getAttribute("data-id");
                });

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
                        close.addEventListener("click", () => {flashes.removeChild(flashMessage)});
                        flashMessage.appendChild(close);
                        flashes.appendChild(flashMessage);

                        const wrapper = document.querySelector(".wrapper");
                        videoSelectionButton.forEach((button)=>{
                            const ytResult = button.closest(".yt-result");
                            ytResult.classList.add("deleted");
                            setTimeout(()=>{ wrapper.removeChild(ytResult);}, 500);
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

        dialog.querySelector(".dialog-title").innerText = videoSelectionButton.length + " video" + (videoSelectionButton.length>1 ? "s":"");
        videoSelectionButton.forEach((button)=>{
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

        dialog.addEventListener('close', () => {
            document.querySelector("body").classList.remove("frozen");
            if (dialog.returnValue === "deleteVideo") {

            }
        });
    }

    openModifyTagsDialog() {
        const dialog = document.querySelector('#modify-tags-dialog');

        gThis.autocomplete("#search-tag-to-modify-list");
        document.querySelector("body").classList.add("frozen");
        dialog.showModal();
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

        let from = "àáäâèéëêìíïîòóöôùúüûñç";
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
}
