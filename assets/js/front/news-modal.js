window.frontModules = window.frontModules || {};

window.frontModules.initNewsModal = (root = document) => {
    const newsModal = root.querySelector("#news-modal");

    if (!newsModal || newsModal.dataset.newsModalInitialized === "true") {
        return;
    }

    newsModal.dataset.newsModalInitialized = "true";

    const modalTag = root.querySelector("#news-modal-tag");
    const modalDate = root.querySelector("#news-modal-date");
    const modalTitle = root.querySelector("#news-modal-title");
    const modalContent = root.querySelector("#news-modal-content");
    const openButtons = root.querySelectorAll("[data-news-source]");
    const closeButtons = newsModal.querySelectorAll("[data-news-close]");
    let previousActiveElement = null;

    const openModal = (button) => {
        const sourceId = button.getAttribute("data-news-source");
        const source = document.getElementById(sourceId);
        const card = button.closest(".guild-news-card, .guild-news-featured");
        const tag = card ? card.querySelector(".guild-news-tag") : null;
        const date = card ? card.querySelector("time") : null;
        const title = card ? card.querySelector("h3") : null;

        if (!source || !card || !modalTag || !modalDate || !modalTitle || !modalContent) {
            return;
        }

        previousActiveElement = document.activeElement;
        modalTag.className = tag ? `${tag.className} news-modal__tag` : "guild-news-tag news-modal__tag";
        modalTag.textContent = tag ? tag.textContent : "";
        modalDate.textContent = date ? date.textContent : "";
        modalTitle.textContent = title ? title.textContent : "";
        modalContent.innerHTML = source.innerHTML;

        if (date && date.getAttribute("datetime")) {
            modalDate.setAttribute("datetime", date.getAttribute("datetime"));
        } else {
            modalDate.removeAttribute("datetime");
        }

        newsModal.hidden = false;
        document.body.classList.add("news-modal-open");
        newsModal.querySelector(".news-modal__close")?.focus();
    };

    const closeModal = () => {
        newsModal.hidden = true;

        if (modalContent) {
            modalContent.innerHTML = "";
        }

        document.body.classList.remove("news-modal-open");

        if (previousActiveElement) {
            previousActiveElement.focus();
        }
    };

    openButtons.forEach((button) => {
        button.addEventListener("click", () => {
            openModal(button);
        });
    });

    closeButtons.forEach((button) => {
        button.addEventListener("click", (event) => {
            event.preventDefault();
            closeModal();
        });
    });

    document.addEventListener("keydown", (event) => {
        if (!newsModal.hidden && event.key === "Escape") {
            closeModal();
        }
    });
};

window.frontModules.initNewsModal();
