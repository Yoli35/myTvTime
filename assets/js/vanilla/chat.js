import {Discussion} from "Discussion";

"use strict";

const chatTitle = {'fr': 'utilisateurs', 'en': 'users', 'de': 'Nutzer', 'es': 'usuarios'};
const chatLocale = document.querySelector("html").getAttribute("lang");
let chatWrapper;
let chatIntervalID = 0, username = "", avatar = "", userCount;
let discussions = [];

window.addEventListener("DOMContentLoaded", () => {
    initChatWindow();
    initDiscussions();
});

function initChatWindow() {
    username = localStorage.getItem("mytvtime.username");
    avatar = localStorage.getItem("mytvtime.avatar");

    chatWrapper = document.querySelector(".chat-wrapper");

    applyChatWindowStatus();

    const chatUsers = chatWrapper.querySelector(".chat-users");
    const chatHeader = chatUsers.querySelector(".chat-users-header");
    const chatUsersList = chatUsers.querySelector(".chat-users-list");
    const chatUsersListItems = chatUsersList.querySelectorAll("li");

    chatHeader.addEventListener("click", () => {
        chatUsersList.classList.toggle("minimized");
        if (chatUsersList.classList.contains("minimized")) {
            chatHeader.innerHTML = chatHeaderMinimizedContent();
            localStorage.setItem("mytvtime.chatWindowStatus", "minimized");
            if (chatIntervalID) clearInterval(chatIntervalID);
        } else {
            chatHeader.innerHTML = chatHeaderExpandedContent();
            localStorage.setItem("mytvtime.chatWindowStatus", "expanded");
            updateChat();
            chatIntervalID = setInterval(updateChat, 30000);
        }
    });

    initUserList();

    if (!chatUsersList.classList.contains("minimized")) {
        chatIntervalID = setInterval(updateChat, 10000);
    }
}

function initUserList() {
    const chatUsers = chatWrapper.querySelector(".chat-users");
    const chatUsersList = chatUsers.querySelector(".chat-users-list");
    const chatUsersListItems = chatUsersList.querySelectorAll("li");
    const chatUsersListItemsArray = Array.from(chatUsersListItems);

    userCount = chatUsersListItems.length;
    chatUsersListItemsArray.forEach(item => {
        item.addEventListener("click", () => {
            const id = item.getAttribute("data-id");
            openDiscussion(id);
        });
    });
}

function initDiscussions() {
    const discussionDivs = chatWrapper.querySelectorAll(".discussion");

    discussionDivs?.forEach(discussionDiv => {
        discussions.push(new Discussion(discussionDiv));
    });
}

function getDiscussionById(id) {
    let discussion = null;
    discussions.forEach(d => {
        if (d.id === id)
            discussion = d;
    });
    return discussion;
}

function updateDiscussions() {
    const updatedDiscussions = [];
    discussions.forEach(d => {
        const discussionDiv = chatWrapper.querySelector(".discussion[data-buddy-id='" + d.id + "']");
        if (discussionDiv) {
            updatedDiscussions.push(d);
        } else {
            d = null;
        }
    });
    return updatedDiscussions;
}

function openDiscussion(buddyId) {
    const discussionDiv = chatWrapper.querySelector(".discussion[data-buddy-id='" + buddyId + "']");

    discussions = updateDiscussions();

    if (discussionDiv) {
        const discussionObj = getDiscussionById(discussionDiv.getAttribute("data-id"));

        if (discussionObj) {
            discussionObj.activate();
        } else {
            discussions.push(new Discussion(discussionDiv));
        }
    } else {
        const xhr = new XMLHttpRequest();

        xhr.onload = function () {
            const div = document.createElement("div");
            div.innerHTML = this.response;
            const firstDiv = chatWrapper.querySelector("div");
            const discussion = div.querySelector(".discussion");
            discussion.setAttribute('data-update', "0");
            chatWrapper.insertBefore(discussion, firstDiv);

            const discussionId = discussion.getAttribute("data-id");
            localStorage.setItem("mytvtime.discussion." + discussionId, "expanded");

            discussions.push(new Discussion(discussion));
        }
        xhr.open("GET", '/chat/discussion/open/' + buddyId);
        xhr.send();
    }
}

function applyChatWindowStatus() {
    const chatWindowStatus = localStorage.getItem("mytvtime.chatWindowStatus");
    const chatUsers = chatWrapper.querySelector(".chat-users");
    const chatWindow = chatUsers.querySelector(".chat-users-list");
    const chatUsersList = chatUsers.querySelector(".chat-users-list");
    const chatUsersListItems = chatUsersList.querySelectorAll("li");
    const chatHeader = chatUsers.querySelector(".chat-users-header");

    userCount = chatUsersListItems.length;

    if (chatWindowStatus === "minimized") {
        chatWindow.classList.add("minimized");
        chatHeader.innerHTML = chatHeaderMinimizedContent();
    } else {
        chatWindow.classList.remove("minimized");
        chatHeader.innerHTML = chatHeaderExpandedContent();
    }
}

function updateChat() {
    const xhr = new XMLHttpRequest();
    xhr.onload = function () {
        const container = document.createElement("div");
        container.innerHTML = this.response;
        document.querySelector(".chat-users-list").innerHTML = container.querySelector(".chat-users-list").innerHTML;
        initUserList();
    }
    xhr.open("GET", "/chat/update");
    xhr.send();
}

function chatHeaderMinimizedContent() {
    return '<svg viewBox="0 0 640 512" fill="currentColor" height="18px" width="18px" aria-hidden="true"><path fill="currentColor" d="M96 224c35.3 0 64-28.7 64-64s-28.7-64-64-64s-64 28.7-64 64s28.7 64 64 64m448 0c35.3 0 64-28.7 64-64s-28.7-64-64-64s-64 28.7-64 64s28.7 64 64 64m32 32h-64c-17.6 0-33.5 7.1-45.1 18.6c40.3 22.1 68.9 62 75.1 109.4h66c17.7 0 32-14.3 32-32v-32c0-35.3-28.7-64-64-64m-256 0c61.9 0 112-50.1 112-112S381.9 32 320 32S208 82.1 208 144s50.1 112 112 112m76.8 32h-8.3c-20.8 10-43.9 16-68.5 16s-47.6-6-68.5-16h-8.3C179.6 288 128 339.6 128 403.2V432c0 26.5 21.5 48 48 48h288c26.5 0 48-21.5 48-48v-28.8c0-63.6-51.6-115.2-115.2-115.2m-223.7-13.4C161.5 263.1 145.6 256 128 256H64c-35.3 0-64 28.7-64 64v32c0 17.7 14.3 32 32 32h65.9c6.3-47.4 34.9-87.3 75.2-109.4"></path></svg>';
}

function chatHeaderExpandedContent() {
    return '<div class="my-avatar">'
        + '    <img src="/images/users/avatars/' + avatar + '" alt="' + username + '">'
        + '</div>'
        + '<div class="my-name">' + username + '</div>'
        + '<div class="list-count">' + userCount + ' ' + chatTitle[chatLocale] + '</div>';
}
