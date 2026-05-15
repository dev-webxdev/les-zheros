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
