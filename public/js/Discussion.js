
export class Discussion {
    constructor(discussion) {
        this.discussion = discussion;
        this.id = this.discussion.getAttribute("data-id");
        this.chatWrapper = this.discussion.closest('.chat-wrapper');
        this.header = this.discussion.querySelector('.header');
        this.closeButton = this.header.querySelector('.close');
        this.minimizeButton = this.header.querySelector('.minimize');
        this.form = this.discussion.querySelector('form');
        this.input = this.discussion.querySelector('input');
        this.messages = this.discussion.querySelector('.messages');
        this.buffer = document.createElement('div');

        this.xhr = new XMLHttpRequest();
        this.newMessageLength = 0;
        this.messageCount = this.messages.querySelectorAll(".message").length;

        this.intervalID = setInterval(() => this.update(), 5000);

        this.closeButton.addEventListener('click', () => this.close());
        this.minimizeButton.addEventListener('click', () => this.minimize());

        this.input.addEventListener('change', (e) => this.typing(e));
        this.form.addEventListener('submit', (e) => this.submit(e));
        this.discussion.setAttribute('data-update', "0");

        this.discussion.querySelector(".message:last-child")?.scrollIntoView();

        this.update();
        this.activate();
    }

    update() {
        const thisClass = this;
        this.xhr.onload = function () {
            thisClass.buffer.innerHTML = this.response;
            const newMessages = thisClass.buffer.querySelector(".messages");
            const newMessagesLength = newMessages.querySelectorAll(".message").length;
            const update = parseInt(thisClass.discussion.getAttribute("data-update"));

            if (newMessagesLength > thisClass.messageCount) {
                thisClass.messages.innerHTML = newMessages.innerHTML;
                thisClass.messageCount = newMessagesLength;
                thisClass.discussion.querySelector(".message:last-child")?.scrollIntoView();
            }
            thisClass.discussion.setAttribute("data-update", (update + 1));
        }
        this.xhr.open("GET", '/chat/discussion/update/' + this.id);
        this.xhr.send();
    }

    close() {
        clearInterval(this.intervalID);

        this.closeButton.removeEventListener('click', () => this.close());
        this.minimizeButton.removeEventListener('click', () => this.minimize());

        this.input.removeEventListener('change', (e) => this.typing(e));
        this.form.removeEventListener('submit', (e) => this.submit(e));

        if (this.discussion.classList.contains("active")) {
            this.chatWrapper.removeChild(this.discussion);
            const remainingDiscussion = this.chatWrapper.querySelector(".discussion");
            if (remainingDiscussion)
                remainingDiscussion.classList.add("active");
        } else {
            this.chatWrapper.removeChild(this.discussion);
        }
        localStorage.removeItem("mytvtime.discussion." + this.id);
        this.newMessageLength = 0;
        this.xhr.onload = function () {
            console.log(this.response);
        }
        this.xhr.open("GET", '/chat/discussion/close/' + this.id);
        this.xhr.send();
    }

    activate() {
        const discussions = this.chatWrapper.querySelectorAll(".discussion");
        discussions.forEach(discussion => {
            if (discussion.classList.contains("active")) {
                discussion.classList.remove("active");
            }
        });
        this.discussion.classList.add("active");
        this.input.focus();
    }

    minimize() {
        this.discussion.classList.add("minimized");
        localStorage.setItem("mytvtime.discussion." + this.id, "minimized");
        if (this.discussion.classList.contains("active")) {
            this.discussion.classList.remove("active");
            const discussions = this.chatWrapper.querySelectorAll(".discussion:not(.minimized)");
            if (discussions.length > 0)
                discussions[0].classList.add("active");
        }
        this.header.addEventListener("click", this.expande);
    }

    expande() {
        this.discussion.classList.remove("minimized");
        this.activate();
        localStorage.setItem("mytvtime.discussion." + this.id, "expanded");
        this.header.removeEventListener("click", this.expande);
    }

    submit(e) {
        const thisClass = this;
        const message = this.input.value;
        this.input.value = "";
        e.preventDefault();
        this.xhr.onload = function () {
            thisClass.buffer.innerHTML = this.response;
            thisClass.messages.innerHTML = thisClass.buffer.querySelector(".messages").innerHTML;
            thisClass.messages.querySelector(".message:last-child").scrollIntoView();
            thisClass.newMessageLength = 0;
        }
        this.xhr.open("POST", '/chat/discussion/message/' + this.id);
        this.xhr.setRequestHeader("Content-Type", "application/json");
        this.xhr.send(JSON.stringify({message}));
    }

    typing(e) {
        const message = e.target.value;
        let isTyping = false;
        if (message.length && this.newMessageLength === 0) {
            isTyping = true;
        }
        this.newMessageLength = message.length;
        this.xhr.open("POST", '/chat/discussion/typing/' + this.id);
        this.xhr.setRequestHeader("Content-Type", "application/json");
        this.xhr.send(JSON.stringify({typing: isTyping}));
    }
}


