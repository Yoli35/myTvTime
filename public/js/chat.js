const chatTitle = {'fr': 'utilisateurs', 'en': 'users', 'de': 'Nutzer', 'es': 'usuarios'};
const chatLocale = document.querySelector("html").getAttribute("lang");
let chatIntervalID = 0, username = "", avatar = "";
let newMessagesLengths = [];

window.addEventListener("DOMContentLoaded", () => {
    initChatWindow();
    initDiscussions();

    const chatUsers = document.querySelector(".chat-users");
    const chatUsersList = chatUsers.querySelector(".chat-users-list");

    if (!chatUsersList.classList.contains("minimized")) {
        chatIntervalID = setInterval(updateChat, 10000);
    }
});

function initChatWindow() {
    username = localStorage.getItem("mytvtime.username");
    avatar = localStorage.getItem("mytvtime.avatar");

    getChatUsersWindowStatus();

    const chatUsers = document.querySelector(".chat-users");
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
            console.log({id});
            openDiscussion(id);
        });
    });
}

function initDiscussions() {
    const discussions = document.querySelectorAll(".discussion");

    discussions?.forEach(discussion => {
        initDiscussion(discussion);
    });
}

function initDiscussion(discussion) {
    const discussionId = discussion.getAttribute("data-id");
    const discussionStatus = localStorage.getItem("mytvtime.discussion." + discussionId);
    const header = discussion.querySelector(".header");
    const minimize = header.querySelector(".minimize");
    const close = header.querySelector(".close");

    newMessagesLengths[discussionId] = 0;
    if (discussionStatus === "minimized") {
        discussion.classList.add("minimized");
    } else {
        discussion.querySelector(".message:last-child")?.scrollIntoView();
        minimize.addEventListener("click", minimizeDiscussion);
        close.addEventListener("click", closeDiscussion);
        discussion.addEventListener("click", e => {
            activeDiscussion(e.currentTarget);
        });
    }
}

function openDiscussion(buddyId) {
    const discussion = document.querySelector(".discussion[data-buddy-id='" + buddyId + "']");

    if (discussion) {
        if (discussion.classList.contains("minimized")) {
            expande(discussion);
        } else {
            activeDiscussion(discussion);
        }
    } else {
        const xhr = new XMLHttpRequest();
        const chatWrapper = document.querySelector(".chat-wrapper");

        xhr.onload = function () {
            chatWrapper.innerHTML = this.response;
            const discussion = document.querySelector(".discussion[data-buddy-id='" + buddyId + "']");
            const discussionId = discussion.getAttribute("data-id");
            activeDiscussion(discussion);
            localStorage.setItem("mytvtime.discussion." + discussionId, "expanded");
            newMessagesLengths[discussionId] = 0;
            initChatWindow();
            initDiscussions();
        }
        xhr.open("GET", '/chat/discussion/open/' + buddyId);
        xhr.send();
    }
}

function closeDiscussion(e) {
    const discussion = e.target.closest(".discussion");
    const discussionId = discussion.getAttribute("data-id");
    const chatWrapper = document.querySelector(".chat-wrapper");

    if (discussion.classList.contains("active")) {
        chatWrapper.removeChild(discussion);
        const remainingDiscussion = chatWrapper.querySelector(".discussion");
        if (remainingDiscussion)
            remainingDiscussion.classList.add("active");
    } else {
        chatWrapper.removeChild(discussion);
    }
    localStorage.removeItem("mytvtime.discussion." + discussionId);
    newMessagesLengths[discussionId] = 0;
    const xhr = new XMLHttpRequest();
    xhr.onload = function () {
        console.log(this.response);
    }
    xhr.open("GET", '/chat/discussion/close/' + discussionId);
    xhr.send();
}

function minimizeDiscussion(e) {
    const discussion = e.target.closest(".discussion");
    const header = discussion.querySelector(".header");
    const discussionId = discussion.getAttribute("data-id");

    discussion.classList.add("minimized");
    localStorage.setItem("mytvtime.discussion." + discussionId, "minimized");
    if (discussion.classList.contains("active")) {
        discussion.classList.remove("active");
        const discussions = discussion.closest(".chat-wrapper").querySelectorAll(".discussion:not(.minimized)");
        if (discussions.length > 0)
            discussions[0].classList.add("active");
    }
    header.addEventListener("click", expandeDiscussion);
}

function expandeDiscussion(e) {
    const discussion = e.target.closest(".discussion");
    expande(discussion);
}

function expande(discussion) {
    const header = discussion.querySelector(".header");
    const discussionId = discussion.getAttribute("data-id");

    discussion.classList.remove("minimized");
    activeDiscussion(discussion);
    localStorage.setItem("mytvtime.discussion." + discussionId, "expanded");
    header.removeEventListener("click", expandeDiscussion);
}

function activeDiscussion(discussion) {
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
            const xhr = new XMLHttpRequest();
            xhr.onload = function () {
                const newDiv = document.createElement("div");
                newDiv.innerHTML = this.response;
                const newBody = newDiv.querySelector(".body");
                const body = discussion.querySelector(".body");
                body.innerHTML = newBody.innerHTML;
                body.querySelector(".message:last-child").scrollIntoView();
                newMessagesLengths[discussionId] = 0;
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
            if (message.length && newMessagesLengths[discussionId] === 0) {
                setTyping(discussionId, userId, true, message.length);
            }
            if (!message.length && newMessagesLengths[discussionId] !== 0) {
                setTyping(discussionId, userId, false, 0);
            }
    }
}

function setTyping(discussionId, userId, typing, length) {
    newMessagesLengths[discussionId] = length;
    const xhr = new XMLHttpRequest();
    xhr.onload = function () {
        console.log(this.response);
    }
    xhr.open("POST", '/chat/discussion/typing/' + discussionId);
    xhr.setRequestHeader("Content-Type", "application/json");
    xhr.send(JSON.stringify({userId:userId, typing:typing}));
}

// function scrollToBottom(element) {
//     element.scrollTop = element.scrollHeight;
// }

function getChatUsersWindowStatus() {
    const chatWindowStatus = localStorage.getItem("mytvtime.chatWindowStatus");
    const chatUsers = document.querySelector(".chat-users");
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
