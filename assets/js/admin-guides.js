const guideCoverInput = document.querySelector("[data-guide-cover-input]");
const guideCoverPreview = document.querySelector("[data-guide-cover-preview]");

guideCoverInput?.addEventListener("change", () => {
    const file = guideCoverInput.files?.[0];

    if (!file || !guideCoverPreview) {
        return;
    }

    guideCoverPreview.src = URL.createObjectURL(file);
});
