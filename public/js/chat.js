import Discussion from "./discussion";
"use strict";

const chatTitle = {'fr': 'utilisateurs', 'en': 'users', 'de': 'Nutzer', 'es': 'usuarios'};
const chatLocale = document.querySelector("html").getAttribute("lang");
let chatWrapper;
let chatIntervalID = 0, username = "", avatar = "";
let newMessageLengths = [];
// let discussionIntervals = [];
// let discussionMessageCount = [];

window.addEventListener("DOMContentLoaded", () => {
    initChatWindow();
    initDiscussions();
});

function initChatWindow() {
    username = localStorage.getItem("mytvtime.username");
    avatar = localStorage.getItem("mytvtime.avatar");

    chatWrapper = document.querySelector(".chat-wrapper");

    getChatUsersWindowStatus();

    const chatUsers = chatWrapper.querySelector(".chat-users");
    const chatHeader = chatUsers.querySelector(".chat-users-header");
    const chatUsersList = chatUsers.querySelector(".chat-users-list");
    const chatUsersListItems = chatUsersList.querySelectorAll("li");
    const chatUsersListItemsArray = Array.from(chatUsersListItems);

    chatHeader.addEventListener("click", () => {
        chatUsersList.classList.toggle("minimized");
        if (chatUsersList.classList.contains("minimized")) {
            chatHeader.innerHTML = '<i class="fa-solid fa-users"></i>';
            localStorage.setItem("mytvtime.chatWindowStatus", "minimized");
            clearInterval(chatIntervalID);
        } else {
            chatHeader.innerHTML = chatUsersListItems.length + " " + chatTitle[chatLocale];
            localStorage.setItem("mytvtime.chatWindowStatus", "expanded");
            updateChat();
            chatIntervalID = setInterval(updateChat, 30000);
        }
    });

    chatUsersListItemsArray.forEach(item => {
        item.addEventListener("click", () => {
            const id = item.getAttribute("data-id");
            openDiscussion(id);
        });
    });

    if (!chatUsersList.classList.contains("minimized")) {
        chatIntervalID = setInterval(updateChat, 10000);
    }
}

function initDiscussions() {
    const discussionDivs = chatWrapper.querySelectorAll(".discussion");

    discussionDivs?.forEach(discussionDiv => {
       // initDiscussion(discussion);
        new Discussion(discussionDiv.getAttribute("data-id"));
    });
}

// function initDiscussion(discussion) {
//     const discussionId = discussion.getAttribute("data-id");
//     const discussionStatus = localStorage.getItem("mytvtime.discussion." + discussionId);
//     const header = discussion.querySelector(".header");
//     const minimize = header.querySelector(".minimize");
//     const close = header.querySelector(".close");
//
//     newMessageLengths[discussionId] = 0;
//     discussionMessageCount[discussionId] = discussion.querySelectorAll(".message").length;
//     discussionIntervals[discussionId] = setInterval(updateDiscussion, 5000, discussionId);
//
//     if (discussionStatus === "minimized") {
//         discussion.classList.add("minimized");
//     } else {
//         discussion.querySelector(".message:last-child")?.scrollIntoView();
//         minimize.addEventListener("click", minimizeDiscussion);
//         close.addEventListener("click", closeDiscussion);
//         discussion.addEventListener("click", e => {
//             activateDiscussion(e.currentTarget);
//         });
//     }
// }

function openDiscussion(buddyId) {
    const discussion = document.querySelector(".discussion[data-buddy-id='" + buddyId + "']");

    if (discussion) {
        if (discussion.classList.contains("minimized")) {
            expande(discussion);
        } else {
            activateDiscussion(discussion);
        }
    } else {
        const xhr = new XMLHttpRequest();
        const chatWrapper = document.querySelector(".chat-wrapper");

        xhr.onload = function () {
            const div = document.createElement("div");
            div.innerHTML = this.response;
            const firstDiv = chatWrapper.querySelector("div");
            const discussion = div.querySelector(".discussion");
            discussion.setAttribute('data-update', "0");
            chatWrapper.insertBefore(discussion, firstDiv);
            activateDiscussion(discussion);
            const discussionId = discussion.getAttribute("data-id");
            localStorage.setItem("mytvtime.discussion." + discussionId, "expanded");

            // initDiscussion(discussion);
            new Discussion(discussion.getAttribute("data-id"));
        }
        xhr.open("GET", '/chat/discussion/open/' + buddyId);
        xhr.send();
    }
}

// function updateDiscussion(discussionId) {
//     const discussion = document.querySelector(".discussion[data-id='" + discussionId + "']");
//     const messages = discussion.querySelector(".messages");
//     const update = parseInt(discussion.getAttribute("data-update"));
//     const xhr = new XMLHttpRequest();
//
//     xhr.onload = function () {
//         const div = document.createElement("div");
//         div.innerHTML = this.response;
//         const newMessages = div.querySelector(".messages");
//         const newMessagesLength = newMessages.querySelectorAll(".message").length;
//
//         if (newMessagesLength > discussionMessageCount[discussionId]) {
//             messages.innerHTML = newMessages.innerHTML;
//             discussionMessageCount[discussionId] = newMessagesLength;
//             discussion.querySelector(".message:last-child")?.scrollIntoView();
//         }
//         discussion.setAttribute("data-update", (update + 1));
//     }
//     xhr.open("GET", '/chat/discussion/update/' + discussionId);
//     xhr.send();
// }

// function closeDiscussion(e) {
//     const discussion = e.target.closest(".discussion");
//     const discussionId = discussion.getAttribute("data-id");
//     const chatWrapper = document.querySelector(".chat-wrapper");
//
//     clearInterval(discussionIntervals[discussionId]);
//
//     if (discussion.classList.contains("active")) {
//         chatWrapper.removeChild(discussion);
//         const remainingDiscussion = chatWrapper.querySelector(".discussion");
//         if (remainingDiscussion)
//             remainingDiscussion.classList.add("active");
//     } else {
//         chatWrapper.removeChild(discussion);
//     }
//     localStorage.removeItem("mytvtime.discussion." + discussionId);
//     newMessageLengths[discussionId] = 0;
//     const xhr = new XMLHttpRequest();
//     xhr.onload = function () {
//         console.log(this.response);
//     }
//     xhr.open("GET", '/chat/discussion/close/' + discussionId);
//     xhr.send();
// }

// function minimizeDiscussion(e) {
//     const discussion = e.target.closest(".discussion");
//     const header = discussion.querySelector(".header");
//     const discussionId = discussion.getAttribute("data-id");
//
//     discussion.classList.add("minimized");
//     localStorage.setItem("mytvtime.discussion." + discussionId, "minimized");
//     if (discussion.classList.contains("active")) {
//         discussion.classList.remove("active");
//         const discussions = discussion.closest(".chat-wrapper").querySelectorAll(".discussion:not(.minimized)");
//         if (discussions.length > 0)
//             discussions[0].classList.add("active");
//     }
//     header.addEventListener("click", expandeDiscussion);
// }

function expandeDiscussion(e) {
    const discussion = e.target.closest(".discussion");
    expande(discussion);
}

function expande(discussion) {
    const header = discussion.querySelector(".header");
    const discussionId = discussion.getAttribute("data-id");

    discussion.classList.remove("minimized");
    activateDiscussion(discussion);
    localStorage.setItem("mytvtime.discussion." + discussionId, "expanded");
    header.removeEventListener("click", expandeDiscussion);
}

function activateDiscussion(discussion) {
    const discussions = document.querySelectorAll(".discussion");
    discussions.forEach(discussion => {
        if (discussion.classList.contains("active")) {
            discussion.classList.remove("active");
            const input = discussion.querySelector("input");
            input.removeEventListener("keyup", inputSend);
        }
    });
    discussion.classList.add("active");
    const input = discussion.querySelector("input");
    input.focus();
    input.addEventListener("keyup", inputSend);
}

function inputSend(e) {
    const message = e.target.value;
    const discussion = e.target.closest(".discussion");
    const discussionId = discussion.getAttribute("data-id");

    switch (e.keyCode) {
        case 13:
            e.target.value = "";
            const xhr = new XMLHttpRequest();
            xhr.onload = function () {
                const newDiv = document.createElement("div");
                newDiv.innerHTML = this.response;
                const newMessages = newDiv.querySelector(".messages");
                const messages = discussion.querySelector(".messages");
                messages.innerHTML = newMessages.innerHTML;
                messages.querySelector(".message:last-child").scrollIntoView();
                newMessageLengths[discussionId] = 0;
            }
            xhr.open("POST", '/chat/discussion/message/' + discussionId);
            xhr.setRequestHeader("Content-Type", "application/json");
            xhr.send(JSON.stringify({message}));
            break;
        case 27:
            e.target.value = "";
            break;
        default:
            const userId = discussion.getAttribute("data-user-id");
            if (message.length && newMessageLengths[discussionId] === 0) {
                setTyping(discussionId, userId, true, message.length);
            }
            if (!message.length && newMessageLengths[discussionId] !== 0) {
                setTyping(discussionId, userId, false, 0);
            }
    }
}

function setTyping(discussionId, userId, typing, length) {
    newMessageLengths[discussionId] = length;
    const xhr = new XMLHttpRequest();
    xhr.onload = function () {
        console.log(this.response);
    }
    xhr.open("POST", '/chat/discussion/typing/' + discussionId);
    xhr.setRequestHeader("Content-Type", "application/json");
    xhr.send(JSON.stringify({typing}));
}

// function scrollToBottom(element) {
//     element.scrollTop = element.scrollHeight;
// }

function getChatUsersWindowStatus() {
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
        document.querySelector(".chat-users").innerHTML = container.querySelector(".chat-users").innerHTML;
        initChatWindow();
        initDiscussions();
    }
    xhr.open("GET", "/chat/update");
    xhr.send();
}
