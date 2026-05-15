const gallerySearch = document.querySelector("[data-gallery-search]");
const galleryFilter = document.querySelector("[data-gallery-filter]");
const galleryItems = document.querySelectorAll("[data-gallery-item]");
const galleryModal = document.querySelector("[data-gallery-modal]");
const galleryModalImage = document.querySelector("[data-gallery-modal-image]");

const applyGalleryFilters = () => {
    const query = gallerySearch?.value.trim().toLowerCase() || "";
    const status = galleryFilter?.value || "all";

    galleryItems.forEach((item) => {
        const title = item.dataset.title?.toLowerCase() || "";
        const matchesQuery = !query || title.includes(query);
        const matchesStatus = status === "all" || item.dataset.status === status;

        item.hidden = !(matchesQuery && matchesStatus);
    });
};

gallerySearch?.addEventListener("input", applyGalleryFilters);
galleryFilter?.addEventListener("change", applyGalleryFilters);
applyGalleryFilters();

document.querySelectorAll("[data-gallery-preview]").forEach((button) => {
    button.addEventListener("click", () => {
        if (!galleryModal || !galleryModalImage) {
            return;
        }

        galleryModalImage.src = button.dataset.galleryPreview || "";
        galleryModalImage.alt = button.dataset.galleryTitle || "";
        galleryModal.hidden = false;
    });
});

document.querySelectorAll("[data-gallery-close]").forEach((button) => {
    button.addEventListener("click", () => {
        if (galleryModal) {
            galleryModal.hidden = true;
        }
    });
});

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && galleryModal && !galleryModal.hidden) {
        galleryModal.hidden = true;
    }
});

document.querySelectorAll("[data-gallery-form]").forEach((form) => {
    const preview = form.querySelector("[data-gallery-preview-image]");
    const fileInput = form.querySelector("[data-gallery-file]");
    const urlInput = form.querySelector("[data-gallery-url]");
    const defaultPreview = preview?.getAttribute("src") || "";

    fileInput?.addEventListener("change", () => {
        const file = fileInput.files?.[0];

        if (!preview) {
            return;
        }

        preview.src = file ? URL.createObjectURL(file) : defaultPreview;
    });

    urlInput?.addEventListener("input", () => {
        if (!preview) {
            return;
        }

        preview.src = urlInput.value.trim() || defaultPreview;
    });
});
