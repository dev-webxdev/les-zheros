const adminStuffsRoot = document.querySelector(".admin-stuffs");

if (adminStuffsRoot) {
    const searchInput = document.querySelector("[data-stuff-search]");
    const rows = Array.from(adminStuffsRoot.querySelectorAll("[data-stuff-row]"));

    const applySearch = () => {
        const query = searchInput?.value.trim().toLowerCase() || "";

        rows.forEach((row) => {
            const text = `${row.dataset.search || ""} ${row.textContent}`.toLowerCase();
            row.hidden = query.length > 0 && !text.includes(query);
        });
    };

    searchInput?.addEventListener("input", applySearch);
}

document.querySelectorAll("[data-admin-stuff-elements]").forEach((picker) => {
    const input = picker.querySelector("#stuff-element");
    const buttons = Array.from(picker.querySelectorAll("[data-stuff-admin-element]"));
    const normalize = (value) => String(value || "").trim().toLowerCase();
    const selectedFromInput = () => normalize(input?.value)
        .split(/[\s,;/|+]+/)
        .filter(Boolean);

    const syncValue = () => {
        const selected = buttons
            .filter((button) => button.classList.contains("is-active"))
            .map((button) => button.dataset.stuffAdminElement)
            .filter(Boolean);

        if (input) {
            input.value = selected.join("/");
        }
    };

    buttons.forEach((button) => {
        const isSelected = selectedFromInput().includes(normalize(button.dataset.stuffAdminElement));
        button.classList.toggle("is-active", isSelected);

        button.addEventListener("click", () => {
            button.classList.toggle("is-active");
            button.setAttribute("aria-pressed", String(button.classList.contains("is-active")));
            syncValue();
        });

        button.setAttribute("aria-pressed", String(isSelected));
    });

    syncValue();
});
