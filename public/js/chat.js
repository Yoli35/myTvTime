const chatTitle = {'fr': 'Utilisateurs', 'en': 'Users', 'de': 'Nutzer', 'es': 'Usuarios'};
const chatLocale = document.querySelector("html").getAttribute("lang");

window.addEventListener("DOMContentLoaded", () => {
    initChatWindow();
    initConversationWindows();

    setInterval(() => {
        updateChat();
    }, 30000);

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
});

function initChatWindow() {

    getChatUsersWindowStatus();

    const chatUsers = document.querySelector(".chat-users");
    const chatHeader = chatUsers.querySelector(".chat-users-header");
    const chatUsersList = chatUsers.querySelector(".chat-users-list");
    const chatUsersListItems = chatUsersList.querySelectorAll("li");
    const chatUsersListItemsArray = Array.from(chatUsersListItems);

    chatHeader.addEventListener("click", () => {
        chatUsersList.classList.toggle("collapsed");
        if (chatUsersList.classList.contains("collapsed")) {
            chatHeader.innerHTML = '<i class="fa-solid fa-users"></i>';
            localStorage.setItem("mytvtime.chatWindowStatus", "collapsed");
        } else {
            chatHeader.innerHTML = chatTitle[chatLocale] + " (" + chatUsersListItems.length + ")";
            localStorage.setItem("mytvtime.chatWindowStatus", "expanded");
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
            body.classList.toggle("collapsed");
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

    if (chatWindowStatus === "collapsed") {
        chatWindow.classList.add("collapsed");
        chatHeader.innerHTML = '<i class="fa-solid fa-users"></i>';
    } else {
        chatWindow.classList.remove("collapsed");
        chatHeader.innerHTML = chatTitle[chatLocale] + " (" + chatUsersListItems.length + ")";
    }
}
