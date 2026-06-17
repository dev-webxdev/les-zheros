window.frontModules = window.frontModules || {};

window.frontModules.initGuides = (root = document) => {
    const guideCatalog = root.querySelector("[data-guide-catalog]");

    if (!guideCatalog || guideCatalog.dataset.guidesInitialized === "true") {
        return;
    }

    guideCatalog.dataset.guidesInitialized = "true";

    const filters = Array.from(guideCatalog.querySelectorAll("[data-guide-filter]"));
    const resetButton = guideCatalog.querySelector("[data-guide-reset]");
    const cards = Array.from(guideCatalog.querySelectorAll("[data-guide-card]"));
    const count = guideCatalog.querySelector("[data-guide-count]");
    const empty = guideCatalog.querySelector("[data-guide-empty]");

    const normalizeGuideValue = (value) => String(value || "").trim().toLowerCase();

    const getGuideFilterState = () => filters.reduce((state, input) => {
        state[input.dataset.guideFilter] = normalizeGuideValue(input.value);
        return state;
    }, {});

    const updateGuides = () => {
        const state = getGuideFilterState();
        let visible = 0;

        cards.forEach((card) => {
            const category = normalizeGuideValue(card.dataset.category);
            const searchIndex = normalizeGuideValue(card.dataset.search);
            const title = normalizeGuideValue(card.querySelector("h2")?.textContent);
            const isVisible = (!state.category || category === state.category)
                && (!state.search || `${searchIndex} ${title}`.includes(state.search));

            card.hidden = !isVisible;

            if (isVisible) {
                visible += 1;
            }
        });

        if (count) {
            count.textContent = String(visible);
        }

        if (empty) {
            empty.hidden = visible > 0;
        }
    };

    filters.forEach((input) => {
        input.addEventListener("input", updateGuides);
        input.addEventListener("change", updateGuides);
    });

    resetButton?.addEventListener("click", () => {
        window.setTimeout(updateGuides, 0);
    });

    updateGuides();
};

window.frontModules.initGuides();
