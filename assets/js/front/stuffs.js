window.frontModules = window.frontModules || {};

window.frontModules.initStuffs = (root = document) => {
    const stuffCatalog = root.querySelector("[data-stuff-catalog]");

    if (!stuffCatalog || stuffCatalog.dataset.stuffsInitialized === "true") {
        return;
    }

    stuffCatalog.dataset.stuffsInitialized = "true";

    const filters = Array.from(stuffCatalog.querySelectorAll("[data-stuff-filter]"))
        .filter((input) => !input.closest("[hidden]"));
    const pickButtons = Array.from(stuffCatalog.querySelectorAll("[data-stuff-pick]"));
    const multiPickFilters = new Set(["element"]);
    const resetButton = stuffCatalog.querySelector("[data-stuff-reset]");
    const cards = Array.from(stuffCatalog.querySelectorAll("[data-stuff-card]"));
    const count = stuffCatalog.querySelector("[data-stuff-count]");
    const empty = stuffCatalog.querySelector("[data-stuff-empty]");

    const normalize = (value) => String(value || "").trim().toLowerCase();
    const normalizeTokens = (value) => normalize(value)
        .split(/[\s,;/|+]+/)
        .filter(Boolean);

    const matchesToken = (value, expected) => {
        if (!expected) {
            return true;
        }

        const valueTokens = normalizeTokens(value);

        return normalizeTokens(expected).every((token) => valueTokens.includes(token));
    };

    const getFilterState = () => filters.reduce((state, input) => {
        state[input.dataset.stuffFilter] = normalize(input.value);
        return state;
    }, {});

    const matchesLevel = (card, level) => {
        if (!level) {
            return true;
        }

        const requestedLevel = Number(level);
        const min = Number(card.dataset.levelMin || 1);
        const max = Number(card.dataset.levelMax || 200);

        return requestedLevel >= min && requestedLevel <= max;
    };

    const updateCatalog = () => {
        const state = getFilterState();
        let visible = 0;

        cards.forEach((card) => {
            const isVisible = (!state.class || card.dataset.class === state.class)
                && matchesToken(card.dataset.element, state.element)
                && matchesToken(card.dataset.mode, state.mode)
                && matchesLevel(card, state.level);

            card.hidden = !isVisible;

            if (isVisible) {
                visible += 1;
            }
        });

        if (count) {
            count.textContent = String(visible);
        }

        if (empty) {
            empty.textContent = cards.length > 0
                ? empty.dataset.emptyFiltered
                : empty.dataset.emptyDefault;
            empty.hidden = visible > 0;
        }
    };

    filters.forEach((input) => {
        input.addEventListener("input", updateCatalog);
        input.addEventListener("change", updateCatalog);
    });

    const syncPickButtons = (filterName, value) => {
        const activeTokens = normalizeTokens(value);

        pickButtons
            .filter((button) => button.dataset.stuffPick === filterName)
            .forEach((button) => {
                const buttonValue = normalize(button.dataset.stuffValue);
                const isActive = multiPickFilters.has(filterName)
                    ? activeTokens.includes(buttonValue)
                    : buttonValue === normalize(value);

                button.classList.toggle("is-active", isActive);
                button.setAttribute("aria-pressed", String(isActive));
            });
    };

    pickButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const filterName = button.dataset.stuffPick;
            const value = button.dataset.stuffValue || "";
            const input = filters.find((item) => item.dataset.stuffFilter === filterName);

            if (!input) {
                return;
            }

            if (multiPickFilters.has(filterName)) {
                const tokens = normalizeTokens(input.value);
                const nextValue = normalize(value);
                input.value = tokens.includes(nextValue)
                    ? tokens.filter((token) => token !== nextValue).join(" ")
                    : [...tokens, nextValue].join(" ");
            } else {
                input.value = value;
            }

            syncPickButtons(filterName, value);
            syncPickButtons(filterName, input.value);
            updateCatalog();
        });
    });

    resetButton?.addEventListener("click", () => {
        window.setTimeout(() => {
            filters.forEach((input) => {
                if (input.dataset.stuffFilter === "level") {
                    input.value = "200";
                } else {
                    input.value = "";
                }

                syncPickButtons(input.dataset.stuffFilter, input.value);
            });

            updateCatalog();
        }, 0);
    });

    filters.forEach((input) => syncPickButtons(input.dataset.stuffFilter, input.value));
    updateCatalog();
};

window.frontModules.initStuffs();
