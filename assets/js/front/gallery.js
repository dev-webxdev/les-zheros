window.frontModules = window.frontModules || {};

window.frontModules.initGallery = (root = document) => {
    const galleryRoot = root.querySelector("[data-gallery-root]");

    if (!galleryRoot || galleryRoot.dataset.galleryInitialized === "true") {
        return;
    }

    galleryRoot.dataset.galleryInitialized = "true";

    const modal = galleryRoot.querySelector("[data-gallery-modal]");
    const modalImage = galleryRoot.querySelector("[data-gallery-modal-image]");
    const modalTitle = galleryRoot.querySelector("[data-gallery-modal-title]");
    const modalDescription = galleryRoot.querySelector("[data-gallery-modal-description]");
    const modalDate = galleryRoot.querySelector("[data-gallery-modal-date]");
    const openButtons = galleryRoot.querySelectorAll("[data-gallery-open]");
    const closeButtons = galleryRoot.querySelectorAll("[data-gallery-close]");

    const closeGalleryModal = () => {
        if (modal) {
            modal.hidden = true;
        }
    };

    openButtons.forEach((button) => {
        button.addEventListener("click", () => {
            if (!modal || !modalImage || !modalTitle || !modalDescription || !modalDate) {
                return;
            }

            modalImage.src = button.dataset.gallerySrc || "";
            modalImage.alt = button.dataset.galleryTitle || "";
            modalTitle.textContent = button.dataset.galleryTitle || "";
            modalDescription.textContent = button.dataset.galleryDescription || "";
            modalDate.textContent = button.dataset.galleryDate || "";
            modal.hidden = false;
        });
    });

    closeButtons.forEach((button) => {
        button.addEventListener("click", closeGalleryModal);
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            closeGalleryModal();
        }
    });
};

window.frontModules.initGallery();
