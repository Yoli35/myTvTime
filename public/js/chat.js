const chatTitle = {'fr': 'utilisateurs', 'en': 'users', 'de': 'Nutzer', 'es': 'usuarios'};
const chatLocale = document.querySelector("html").getAttribute("lang");
let chatIntervalID = 0, username = "", avatar = "";

window.addEventListener("DOMContentLoaded", () => {
    initChatWindow();
    initConversationWindows();

    const chatUsers = document.querySelector(".chat-users");
    const chatUsersList = chatUsers.querySelector(".chat-users-list");

    if (!chatUsersList.classList.contains("minimized")) {
        chatIntervalID = setInterval(updateChat, 30000);
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
            openConversation(id);
        });
    });
}

function initConversationWindows() {
//     const xhr = new XMLHttpRequest();
//     const chatWrapper = document.querySelector(".chat-wrapper");
//
//     xhr.onload = function () {
//         chatWrapper.appendChild(this.response);
//     }
//     xhr.open("GET", '/chat/discussion/' + userId);
//     xhr.send();
    const discussions = document.querySelectorAll(".discussion");

    discussions?.forEach(discussion => {
        const discussionId = discussion.getAttribute("data-id");
        const discussionStatus = localStorage.getItem("mytvtime.discussion." + discussionId);
        const header = discussion.querySelector(".header");
        const minimize = header.querySelector(".minimize");
        const close = header.querySelector(".close");

        if (discussionStatus === "minimized") {
            discussion.classList.add("minimized");
        }
        minimize.addEventListener("click", () => {
            discussion.classList.toggle("minimized");
            if (discussion.classList.contains("minimized")) {
                localStorage.setItem("mytvtime.discussion." + discussion.getAttribute("data-id"), "minimized");
                if (discussion.classList.contains("active")) {
                    const discussions = discussion.closest(".chat-wrapper").querySelectorAll(".discussion:not(.minimized)");
                    if (discussions.length > 0)
                        discussions[0].classList.add("active");
                }
            } else {
                localStorage.setItem("mytvtime.discussion." + discussion.getAttribute("data-id"), "expanded");
            }
        });
        close.addEventListener("click", (e) => {
            closeDiscussion(e);
        });
        discussion.addEventListener("click", () => {
            discussions.forEach(discussion => {
                discussion.classList.remove("active");
            });
            discussion.classList.add("active");
        });
    });
}

function openConversation(id) {
    const discussion = document.querySelector(".discussion[data-user-id='" + id + "']");

    if (discussion) {
        const discussions = document.querySelectorAll(".discussion");
        discussions.forEach(discussion => {
            discussion.classList.remove("active");
        });
        discussion.classList.add("active");
    } else {
        const xhr = new XMLHttpRequest();
        const chatWrapper = document.querySelector(".chat-wrapper");

        xhr.onload = function () {
            chatWrapper.innerHTML = this.response;
            const discussion = document.querySelector(".discussion[data-user-id='" + id + "']");
            discussion.classList.add("active");
            initConversationWindows();
        }
        xhr.open("GET", '/chat/discussion/open/' + id);
        xhr.send();
    }
}

function closeDiscussion(e) {
    const discussion = e.target.closest(".discussion");
    const discussionId = discussion.getAttribute("data-id");
    const chatWrapper = document.querySelector(".chat-wrapper");

    if (discussion.classList.contains("active")) {
        chatWrapper.removeChild(discussion);
        const discussions = chatWrapper.querySelectorAll(".discussion");
        if (discussions.length > 0)
            discussions[0].classList.add("active");
    } else {
        chatWrapper.removeChild(discussion);
    }
    const xhr = new XMLHttpRequest();
    xhr.onload = function () {
        console.log(this.response);
    }
    xhr.open("GET", '/chat/discussion/close/' + discussionId);
    xhr.send();
}

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
        document.querySelector(".chat-wrapper").innerHTML = this.response;
        initChatWindow();
        initConversationWindows();
    }
    xhr.open("GET", "/chat/update");
    xhr.send();
}
