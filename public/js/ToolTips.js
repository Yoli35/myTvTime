export class ToolTips {
    init(element = null) {
        // <div className="tool-tips">
        //     <div className="body"></div>
        //     <div className="tail"></div>
        // </div>
        let divs;
        if (!element) {
            const tooltips = document.createElement("div");
            tooltips.classList.add("tool-tips");
            const body = document.createElement("div");
            body.classList.add("body");
            const tail = document.createElement("div");
            tail.classList.add("tail");
            tooltips.appendChild(body);
            tooltips.appendChild(tail);
            document.body.appendChild(tooltips);

            divs = document.querySelectorAll("div[data-title]");
        } else {
            divs = element.querySelectorAll("div[data-title]");
        }
        divs.forEach(div => {
            div.addEventListener('mousemove', this.move);
            div.addEventListener('mouseenter', this.show);
            div.addEventListener('mouseleave', this.hide);
        });
    }

    initElement(element) {
        element.addEventListener('mousemove', this.move);
        element.addEventListener('mouseenter', this.show);
        element.addEventListener('mouseleave', this.hide);
    }

    show(evt) {
        const tooltips = document.querySelector(".tool-tips");
        const text = evt.currentTarget.getAttribute("data-title");
        const body = tooltips.querySelector(".body");
        body.innerHTML = text;

        const width = body.offsetWidth;
        tooltips.setAttribute("style", "translate: " + (evt.pageX - (width / 2)) + "px " + evt.pageY + "px;");

        tooltips.classList.add("show");
    }

    hide() {
        const tooltips = document.querySelector(".tool-tips");
        tooltips.classList.remove("show");
        tooltips.setAttribute("style", "translate: 0px 0px;");
    }

    move(evt) {
        const tooltips = document.querySelector(".tool-tips");
        const body = tooltips.querySelector(".body");
        const width = body.offsetWidth;

        tooltips.setAttribute("style", "translate: " + (evt.pageX - (width / 2)) + "px " + evt.pageY + "px;");
    }
}