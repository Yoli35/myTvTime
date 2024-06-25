let gThis = null;
export class ToolTips {
    constructor() {
        gThis = this;
    }

    init(element = null, className = null) {
        // <div className="tool-tips">
        //     <div className="body"></div>
        //     <div className="tail"></div>
        // </div>
        let divs;
        if (!element) {
            const tooltips = document.createElement("div");
            tooltips.classList.add("tool-tips");
            if (className) {
                tooltips.classList.add(className);
            }
            const body = document.createElement("div");
            body.classList.add("body");
            const tail = document.createElement("div");
            tail.classList.add("tail");
            tooltips.appendChild(body);
            tooltips.appendChild(tail);
            document.body.appendChild(tooltips);

            divs = document.querySelectorAll("*[data-title]");
        } else {
            divs = element.querySelectorAll("*[data-title]");
        }
        divs.forEach(div => {
            div.addEventListener('mousemove', this.move);
            div.addEventListener('mouseover', this.show);
            div.addEventListener('mouseout', this.hide);
        });
    }

    initElement(element) {
        element.addEventListener('mousemove', this.move);
        element.addEventListener('mouseover', this.show);
        element.addEventListener('mouseout', this.hide);
    }

    show(evt) {
        const tooltips = document.querySelector(".tool-tips");
        if (tooltips.classList.contains("show")) {
            return;
        }
        const currentTarget = evt.currentTarget;
        const text = currentTarget.getAttribute("data-title");
        const img = currentTarget.querySelector("img");
        const body = tooltips.querySelector(".body");
        if (!img) {
            body.innerHTML = text;
        } else {
            const imgClone = img.cloneNode(true);
            body.innerHTML = "";
            body.appendChild(imgClone);
            const p = document.createElement("p");
            p.innerHTML = text;
            body.appendChild(p);
        }

        // const width = body.offsetWidth;
        // tooltips.setAttribute("style", "translate: " + (evt.pageX - (width / 2)) + "px " + evt.pageY + "px;");

        gThis.move(evt);
        tooltips.classList.add("show");
    }

    hide() {
        const tooltips = document.querySelector(".tool-tips");
        tooltips.classList.remove("show");
        tooltips.setAttribute("style", "translate: 0px 0px;");
    }

    move(evt) {
        const tooltips = document.querySelector(".tool-tips");
        const tail = tooltips.querySelector(".tail");
        const body = tooltips.querySelector(".body");
        const img = body.querySelector("img");
        const width = body.offsetWidth;
        const height = body.offsetHeight - 48;
        const fromTopViewport = evt.clientY;
        const windowWidth = window.innerWidth;

        if (img) img.style.maxHeight = (fromTopViewport - 64) + "px";

        const left = evt.pageX - (width / 2);
        if (left < 0) {
            let style = "translate: " + (evt.pageX - (width / 2) + (left * -1)) + "px " + (evt.pageY - Math.min(height, fromTopViewport)) + "px;";
            tooltips.setAttribute("style", style);
            tail.setAttribute("style", "translate: " + left + "px -.55em");
            return;
        }

        const right = evt.pageX + (width / 2);
        if (right > windowWidth) {
            let style = "translate: " + (evt.pageX - (width / 2) - (right - windowWidth)) + "px " + (evt.pageY - Math.min(height, fromTopViewport)) + "px;";
            tooltips.setAttribute("style", style);
            tail.setAttribute("style", "translate: " + (right - windowWidth) + "px -.55em;");
            return;
        }

        let style = "translate: " + (evt.pageX - (width / 2)) + "px " + (evt.pageY - Math.min(height, fromTopViewport)) + "px;";
        tooltips.setAttribute("style", style);
        tail.setAttribute("style", "translate: 0 -.55em");
    }
}