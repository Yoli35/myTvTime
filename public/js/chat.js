import {Discussion} from "./Discussion.js";

"use strict";

const chatTitle = {'fr': 'utilisateurs', 'en': 'users', 'de': 'Nutzer', 'es': 'usuarios'};
const chatLocale = document.querySelector("html").getAttribute("lang");
let chatWrapper;
let chatIntervalID = 0, username = "", avatar = "";
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
            chatHeader.innerHTML = '<i class="fa-solid fa-users"></i>';
            localStorage.setItem("mytvtime.chatWindowStatus", "minimized");
            if (chatIntervalID) clearInterval(chatIntervalID);
        } else {
            chatHeader.innerHTML = chatUsersListItems.length + " " + chatTitle[chatLocale];
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

    if (chatWindowStatus === "minimized") {
        chatWindow.classList.add("minimized");
        chatHeader.innerHTML = '<i class="fa-solid fa-users"></i>';
    } else {
        chatWindow.classList.remove("minimized");
        chatHeader.innerHTML =
            '<div class="my-avatar">'
            + '    <img src="/images/users/avatars/' + avatar + '" alt="' + username + '">'
            + '</div>'
            + '<div class="my-name">' + username + '</div>'
            + '<div class="list-count">' + chatUsersListItems.length + ' ' + chatTitle[chatLocale] + '</div>';
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
