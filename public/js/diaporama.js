let diaporamaImages = null, diaporamaIndex, diaporamaCount, diaporamaSrc, _diaporama_locale;

function initDiaporama(images, locale) {
    if (!images) return;
    if (diaporamaImages === null) {
        diaporamaImages = Array.from(images);
        images.forEach(image => {
            image.classList.add("pointer");
            image.addEventListener("click", openDiaporama);
        });
    } else {
        images.forEach(image => {
            image.classList.add("pointer");
            image.addEventListener("click", openDiaporama);
            diaporamaImages.push(image);
        });
    }
    _diaporama_locale = locale;
}

function openDiaporama(e) {
    const images = diaporamaImages;
    const count = images.length;
    let prevDiv, nextDiv;
    const srcArray = Array.from(images, image => {
        return image.getAttribute("src")
    });

    diaporamaIndex = 0;
    diaporamaCount = count;
    diaporamaSrc = srcArray;

    const backDiapo = document.createElement("div");
    backDiapo.classList.add("back-diapo");
    const wrapperDiv = document.createElement("div");
    wrapperDiv.classList.add("wrapper");
    const imageDiv = document.createElement("div");
    imageDiv.classList.add("image");
    backDiapo.appendChild(wrapperDiv);
    wrapperDiv.appendChild(imageDiv);
    let thumbnails
    if (count > 1) {
        thumbnails = document.createElement("div");
        thumbnails.classList.add("thumbnails");
        wrapperDiv.appendChild(thumbnails);
    }
    document.querySelector("body").appendChild(backDiapo);
    document.body.style.overflow = 'hidden';

    const closeDiv = document.createElement("div");
    closeDiv.classList.add("close");
    const crossDiv = document.createElement("div");
    closeDiv.appendChild(crossDiv);
    closeDiv.addEventListener("click", closeDiaporama);
    crossDiv.innerHTML = '<i class="fa-solid fa-xmark"></i>';
    if (count > 1) {
        prevDiv = document.createElement("div");
        prevDiv.classList.add("chevron");
        nextDiv = document.createElement("div");
        nextDiv.classList.add("chevron");
        prevDiv.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
        nextDiv.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
    }

    const imageSrc = e.currentTarget.getAttribute("src");
    const imgDiv = document.createElement("img");
    imgDiv.setAttribute("src", imageSrc);

    if (count > 1) {
        srcArray.forEach((src, index) => {
            const thumbnail = document.createElement("div");
            thumbnail.classList.add("thumbnail");
            thumbnail.setAttribute("data-index", index.toString());
            if (src === imageSrc) {
                thumbnail.classList.add("active");
                diaporamaIndex = index;
            }
            const img = document.createElement("img");
            img.setAttribute("src", src);
            thumbnail.appendChild(img);
            thumbnail.addEventListener("click", gotoImage);
            thumbnails.appendChild(thumbnail);
        });
        prevDiv.addEventListener("click", prevImage);
        nextDiv.addEventListener("click", nextImage);
    }

    document.addEventListener("keydown", getKey);

    backDiapo.appendChild(closeDiv);
    if (count > 1) imageDiv.appendChild(prevDiv);
    imageDiv.appendChild(imgDiv);
    if (count > 1) imageDiv.appendChild(nextDiv);

    setTimeout(() => {
        imgDiv.classList.add("fade");
    }, 0);
    backDiapo.style.bottom = -window.scrollY + "px";
    backDiapo.style.top = window.scrollY + "px";

    initShortcutsHelp(backDiapo, count);
}

function nextImage() {
    thumbnailDeactivate(diaporamaIndex);
    diaporamaIndex = (diaporamaIndex + 1) % diaporamaCount;
    thumbnailActivate(diaporamaIndex);
    const img = document.querySelector(".back-diapo").querySelector(".image").querySelector("img");
    img.setAttribute("src", diaporamaSrc[diaporamaIndex]);
}

function prevImage() {
    thumbnailDeactivate(diaporamaIndex);
    diaporamaIndex = (diaporamaIndex + (diaporamaCount - 1)) % diaporamaCount;
    thumbnailActivate(diaporamaIndex);
    const img = document.querySelector(".back-diapo").querySelector(".image").querySelector("img");
    img.setAttribute("src", diaporamaSrc[diaporamaIndex]);
}

function gotoImage(e) {
    thumbnailDeactivate(diaporamaIndex);
    diaporamaIndex = parseInt(e.currentTarget.getAttribute("data-index"));
    thumbnailActivate(diaporamaIndex);
    const img = document.querySelector(".back-diapo").querySelector(".image").querySelector("img");
    img.setAttribute("src", diaporamaSrc[diaporamaIndex]);
}

function thumbnailDeactivate(index) {
    const selector = '.thumbnail[data-index="' + index + '"]'
    const thumbnail = document.querySelector(selector);
    thumbnail.classList.remove("active");
}

function thumbnailActivate(index) {
    const selector = '.thumbnail[data-index="' + index + '"]'
    const thumbnail = document.querySelector(selector);
    thumbnail.classList.add("active");
}

function closeDiaporama() {
    const backDiapo = document.querySelector(".back-diapo");
    const imgDiv = backDiapo.querySelector("img");

    if (diaporamaCount > 1) {
        for (let i = 0; i < diaporamaCount; i++) {
            const selector = '.thumbnail[data-index="' + i + '"]'
            const thumbnail = document.querySelector(selector);
            thumbnail.removeEventListener("click", gotoImage);
        }
        const prevDiv = document.querySelector(".back-diapo").querySelector(".fa-chevron-left").parentElement;
        const nextDiv = document.querySelector(".back-diapo").querySelector(".fa-chevron-right").parentElement;
        prevDiv.removeEventListener("click", prevImage);
        nextDiv.removeEventListener("click", nextImage);
    }
    document.removeEventListener("keydown", getKey);
    document.body.style.overflow = 'unset';
    setTimeout(() => {
        imgDiv.classList.remove("fade");
    }, 0);
    setTimeout(() => {
        document.querySelector("body").removeChild(backDiapo);
    }, 300);
}

function getKey(e) {
    if (e.key === "ArrowLeft") {
        if (diaporamaCount > 1) prevImage();
    } else if (e.key === "ArrowRight") {
        if (diaporamaCount > 1) nextImage();
    } else if (e.key === "Escape") {
        closeDiaporama();
    }
}

function initShortcutsHelp(back, count) {
    const txt = {
        'title': {
            'fr': 'Raccourcis clavier',
            'en': 'Keyboard shortcuts',
            'de': 'Tastaturkürzel',
            'es': 'Atajos de teclado',
        },
        'left': {
            'fr': 'Image précédente',
            'en': 'Previous image',
            'de': 'Vorheriges Bild',
            'es': 'Imagen anterior',
        }
        ,
        'right': {
            'fr': 'Image suivante',
            'en': 'Next image',
            'de': 'Nächstes Bild',
            'es': 'Imagen siguiente',
        }
        ,
        'escape': {
            'fr': 'Quitter le diaporama',
            'en': 'Quit diaporama',
            'de': 'Diashow beenden',
            'es': 'Salir de la presentación',
        }
    };
    const navigation = document.createElement("div");
    navigation.classList.add("navigation");
    const help = document.createElement("div");
    help.classList.add("help");
    navigation.appendChild(help);
    const mini = document.createElement("div");
    mini.classList.add("mini");
    mini.innerHTML = '<i class="fa-solid fa-circle-question"></i>';
    mini.addEventListener("click", maximiseHelp);
    help.appendChild(mini);
    const maxi = document.createElement("div");
    maxi.classList.add("maxi");
    help.appendChild(maxi);
    const title = document.createElement("div");
    title.classList.add("title");
    title.innerHTML = txt.title[_diaporama_locale];
    maxi.appendChild(title);
    if (count) {
        addKey(maxi, "left", txt.left[_diaporama_locale]);
        addKey(maxi, "right", txt.right[_diaporama_locale]);
    }
    addKey(maxi, "escape", txt.escape[_diaporama_locale]);
    const close = document.createElement("div");
    close.classList.add("close");
    close.innerHTML = '<i class="fa-solid fa-circle-chevron-down"></i>';
    close.addEventListener("click", minimiseHelp);
    maxi.appendChild(close);
    back.appendChild(navigation);
}

function minimiseHelp() {
    const help = document.querySelector(".back-diapo").querySelector(".navigation").querySelector(".help");
    help.classList.add("minimise");
}

function maximiseHelp() {
    const help = document.querySelector(".back-diapo").querySelector(".navigation").querySelector(".help");
    help.classList.remove("minimise");
}

function addKey(parent, name, txt) {
    const key = document.createElement("div");
    key.classList.add("key")
    const img = document.createElement("img");
    img.setAttribute("src", "/images/interface/key-" + name + ".png");
    key.appendChild(img);
    const text = document.createElement("div");
    text.appendChild(document.createTextNode(txt));
    key.appendChild(text);
    parent.appendChild(key);
}
