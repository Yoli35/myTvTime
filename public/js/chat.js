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
        });
    });
}

function initConversationWindows() {
    const conversations = document.querySelectorAll(".conversation");

    conversations?.forEach(conversation => {
        const header = conversation.querySelector(".header");
        const body = conversation.querySelector(".body");
        header.addEventListener("click", () => {
            body.classList.toggle("minimized");
        });
        conversation.addEventListener("click", () => {
            conversations.forEach(conversation => {
                conversation.classList.remove("active");
            });
            conversation.classList.add("active");
        });
    });
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
