
class Discussion {
    constructor(id) {
        this.id = id;
        this.discussion = document.querySelector('.discussion[data-id=' + id + ']');
        this.chatWrapper = this.discussion.closest('.chat-wrapper');
        this.xhr = new XMLHttpRequest();
        this.newMessageLength = 0;
        this.messageCount = 0;
        this.intervalID = setInterval(() => this.update(), 5000);
        this.form = this.discussion.querySelector('form');
        this.input = this.discussion.querySelector('input');
        this.messages = this.discussion.querySelector('.messages');
        this.buffer = document.createElement('div');

        this.input.addEventListener('change', (e) => this.typing(e));
        this.form.addEventListener('submit', (e) => this.submit(e));
        this.discussion.setAttribute('data-update', "0");
        this.update();
    }

    update() {
        this.xhr.onload = function () {
            this.buffer.innerHTML = this.response;
            const newMessages = this.buffer.querySelector(".messages");
            const newMessagesLength = newMessages.querySelectorAll(".message").length;
            const update = parseInt(this.discussion.getAttribute("data-update"));

            if (newMessagesLength > this.messageCount) {
                this.messages.innerHTML = newMessages.innerHTML;
                this.messageCount = newMessagesLength;
                this.discussion.querySelector(".message:last-child")?.scrollIntoView();
            }
            this.discussion.setAttribute("data-update", Number::toString(update + 1));
        }
        this.xhr.open("GET", '/chat/discussion/update/' + this.id);
        this.xhr.send();
    }

    close() {
        clearInterval(this.intervalID);

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

    submit(e) {
        e.preventDefault();
        this.xhr.onload = function () {
            this.buffer.innerHTML = this.response;
            this.messages.innerHTML = this.buffer.querySelector(".messages").innerHTML;
            this.messages.querySelector(".message:last-child").scrollIntoView();
            this.newMessageLength = 0;
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

export default Discussion;