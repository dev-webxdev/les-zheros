window.frontModules = window.frontModules || {};

window.frontModules.initAlmanax = (root = document) => {
    const almanaxSections = root.querySelectorAll("[data-almanax]");

    if (almanaxSections.length === 0) {
        return;
    }

    const reducedMotionMedia = window.matchMedia("(prefers-reduced-motion: reduce)");

    const flashElement = (element, className = "is-flash") => {
        if (!element || reducedMotionMedia.matches) {
            return;
        }

        element.classList.remove(className);
        void element.offsetWidth;
        element.classList.add(className);

        window.setTimeout(() => {
            element.classList.remove(className);
        }, 500);
    };
    const escapeHtml = (value) => String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");

    const numberFormatter = new Intl.NumberFormat("fr-FR");
    const compactNumberFormatter = new Intl.NumberFormat("fr-FR", {
        notation: "compact",
        maximumFractionDigits: 1
    });
    const itemEndpointBySubtype = {
        consumables: "consumables",
        cosmetics: "cosmetics",
        equipment: "equipment",
        quest: "quest",
        resources: "resources"
    };
    const tributeTypeLabels = {
        consumables: "Consommable",
        equipment: "Equipement",
        quest: "Objet de quête",
        resources: "Ressource"
    };
    const bonusPresentationById = {
        loot: { icon: "fa-sack-dollar", variant: "loot" },
        "double-loot": { icon: "fa-gem", variant: "loot" },
        "treasure-chest": { icon: "fa-box-open", variant: "loot" },
        "treasure-chests": { icon: "fa-box-open", variant: "loot" },
        "surprise-gifts": { icon: "fa-gift", variant: "loot" },
        "quests-and-kamas": { icon: "fa-coins", variant: "loot" },
        "experience-points": { icon: "fa-star", variant: "xp" },
        "profession-experience": { icon: "fa-wand-magic-sparkles", variant: "xp" },
        "quest-experience": { icon: "fa-book-open-reader", variant: "xp" },
        "pet-experience": { icon: "fa-paw", variant: "xp" },
        "mount-experience": { icon: "fa-horse", variant: "xp" },
        "kolossium-experience": { icon: "fa-shield-halved", variant: "xp" },
        "metamunch-experience": { icon: "fa-bolt", variant: "xp" },
        "full-of-life": { icon: "fa-heart-pulse", variant: "xp" },
        "plenty-to-harvest": { icon: "fa-wheat-awn", variant: "harvest" },
        "plenty-to-pick": { icon: "fa-leaf", variant: "harvest" },
        "plenty-of-wood": { icon: "fa-tree", variant: "harvest" },
        "plenty-of-ore": { icon: "fa-gem", variant: "harvest" },
        "plenty-of-fish": { icon: "fa-fish", variant: "harvest" },
        "plenty-of-game": { icon: "fa-drumstick-bite", variant: "harvest" },
        "resource-generation": { icon: "fa-seedling", variant: "harvest" },
        "generation-of-resources": { icon: "fa-seedling", variant: "harvest" },
        "generous-nature": { icon: "fa-clover", variant: "harvest" },
        "protectors-of-nature": { icon: "fa-spa", variant: "harvest" },
        "saved-ingredients": { icon: "fa-flask", variant: "craft" },
        "production-line": { icon: "fa-gears", variant: "craft" },
        "item-quality": { icon: "fa-award", variant: "craft" },
        "improved-smithmagic": { icon: "fa-hammer", variant: "craft" },
        "elemental-smithmagic": { icon: "fa-fire-flame-curved", variant: "craft" },
        crushing: { icon: "fa-hammer-crash", variant: "craft" },
        "fairy-factory": { icon: "fa-flask-vial", variant: "craft" },
        "increased-challenges": { icon: "fa-trophy", variant: "challenge" },
        "extra-challenge": { icon: "fa-bullseye", variant: "challenge" },
        "dungeon-challenges": { icon: "fa-trophy", variant: "challenge" },
        "temporal-anomalies": { icon: "fa-hourglass-half", variant: "anomaly" },
        "anomaly-madness": { icon: "fa-clock-rotate-left", variant: "anomaly" },
        "dimensional-voyages": { icon: "fa-compass", variant: "anomaly" },
        "repeatable-quest": { icon: "fa-scroll", variant: "quest" },
        "repeatable-quest-and-chocomania": { icon: "fa-scroll", variant: "quest" },
        "wanted-notices": { icon: "fa-note-sticky", variant: "quest" },
        "bonta-and-brakmar": { icon: "fa-shield-halved", variant: "quest" },
        "mount-breeding": { icon: "fa-horse", variant: "mount" },
        "hardy-mounts": { icon: "fa-horse", variant: "mount" },
        "mounts-in-love": { icon: "fa-heart", variant: "mount" },
        "wise-mounts": { icon: "fa-feather-pointed", variant: "mount" },
        "precocious-mounts": { icon: "fa-horse", variant: "mount" },
        "paddock-efficiency": { icon: "fa-road", variant: "mount" }
    };
    const itemDetailsCache = new Map();

    const capitalizeFirst = (value) => value ? value.charAt(0).toUpperCase() + value.slice(1) : "";

    const getDateFormatter = (timeZone, options) => new Intl.DateTimeFormat("fr-FR", {
        ...options,
        timeZone
    });

    const toIsoDate = (date) => {
        const year = date.getUTCFullYear();
        const month = String(date.getUTCMonth() + 1).padStart(2, "0");
        const day = String(date.getUTCDate()).padStart(2, "0");

        return `${year}-${month}-${day}`;
    };

    const addDaysToIsoDate = (isoDate, daysToAdd) => {
        const nextDate = new Date(`${isoDate}T12:00:00Z`);
        nextDate.setUTCDate(nextDate.getUTCDate() + daysToAdd);

        return toIsoDate(nextDate);
    };

    const getCurrentIsoDateForTimeZone = (timeZone) => {
        const parts = getDateFormatter(timeZone, {
            year: "numeric",
            month: "2-digit",
            day: "2-digit"
        }).formatToParts(new Date());

        const partMap = {};
        parts.forEach((part) => {
            if (part.type !== "literal") {
                partMap[part.type] = part.value;
            }
        });

        return `${partMap.year}-${partMap.month}-${partMap.day}`;
    };

    const formatIsoDate = (isoDate, timeZone, options) => {
        const safeDate = new Date(`${isoDate}T12:00:00Z`);
        return getDateFormatter(timeZone, options).format(safeDate);
    };

    const formatTributeType = (type) => {
        if (!type) {
            return "Objet";
        }

        if (tributeTypeLabels[type]) {
            return tributeTypeLabels[type];
        }

        return capitalizeFirst(type.replace(/-/g, " "));
    };

    const getBonusPresentation = (bonusId) => {
        if (!bonusId || !bonusPresentationById[bonusId]) {
            return {
                icon: "fa-sun",
                variant: "generic"
            };
        }

        return bonusPresentationById[bonusId];
    };

    const buildItemDetailsUrl = (language, subtype, ankamaId) => {
        const endpoint = itemEndpointBySubtype[subtype];

        if (!endpoint || !ankamaId) {
            return null;
        }

        return `https://api.dofusdu.de/dofus3/v1/${language}/items/${endpoint}/${ankamaId}`;
    };

    const loadItemDetails = async (language, item) => {
        const ankamaId = item?.ankama_id;
        const subtype = item?.subtype;
        const itemUrl = buildItemDetailsUrl(language, subtype, ankamaId);
        const cacheKey = `${subtype}:${ankamaId}`;

        if (!itemUrl) {
            return null;
        }

        if (!itemDetailsCache.has(cacheKey)) {
            itemDetailsCache.set(cacheKey, (async () => {
                try {
                    const response = await fetch(itemUrl);

                    if (!response.ok) {
                        return null;
                    }

                    return await response.json();
                } catch (error) {
                    return null;
                }
            })());
        }

        return itemDetailsCache.get(cacheKey);
    };

    const loadRecipeDetails = async (language, recipe) => {
        if (!Array.isArray(recipe) || recipe.length === 0) {
            return [];
        }

        const recipeItems = await Promise.all(recipe.map(async (ingredient) => {
            const ingredientDetails = await loadItemDetails(language, {
                ankama_id: ingredient.item_ankama_id,
                subtype: ingredient.item_subtype
            });

            return {
                quantity: ingredient.quantity,
                name: ingredientDetails?.name || `Objet #${ingredient.item_ankama_id}`
            };
        }));

        return recipeItems.filter((ingredient) => ingredient.quantity && ingredient.name);
    };

    const buildRangeTitle = (startDate, endDate, timeZone) => {
        const startLabel = formatIsoDate(startDate, timeZone, {
            day: "numeric",
            month: "long"
        });
        const endLabel = formatIsoDate(endDate, timeZone, {
            day: "numeric",
            month: "long",
            year: "numeric"
        });

        return `Du ${startLabel} au ${endLabel}`;
    };

    almanaxSections.forEach((almanaxRoot) => {
        if (almanaxRoot.dataset.almanaxInitialized === "true") {
            return;
        }

        almanaxRoot.dataset.almanaxInitialized = "true";

        const tabsContainer = almanaxRoot.querySelector("[data-almanax-tabs]");
        const panel = almanaxRoot.querySelector("[data-almanax-panel]");
        const feedback = almanaxRoot.querySelector("[data-almanax-feedback]");
        const rangeTitle = almanaxRoot.querySelector("[data-almanax-range]");
        const currentIndex = almanaxRoot.querySelector("[data-almanax-current]");
        const totalCount = almanaxRoot.querySelector("[data-almanax-total]");
        const language = almanaxRoot.dataset.almanaxLanguage || "fr";
        const timeZone = almanaxRoot.dataset.almanaxTimezone || "Europe/Paris";
        const requestedDays = Math.max(Number(almanaxRoot.dataset.almanaxDays || "7"), 1);
        const level = Number(almanaxRoot.dataset.almanaxLevel || "");
        const startDate = getCurrentIsoDateForTimeZone(timeZone);
        const endDate = addDaysToIsoDate(startDate, requestedDays - 1);
        const almanaxUrl = new URL(`https://api.dofusdu.de/dofus3/v1/${language}/almanax`);
        let days = [];
        let activeIndex = 0;
        let activeRenderToken = 0;
        let hasRenderedActiveDay = false;

        if (!tabsContainer || !panel || !feedback || !rangeTitle || !currentIndex || !totalCount) {
            return;
        }

        almanaxUrl.searchParams.set("range[from]", startDate);
        almanaxUrl.searchParams.set("range[to]", endDate);
        almanaxUrl.searchParams.set("timezone", timeZone);

        if (Number.isFinite(level) && level > 0) {
            almanaxUrl.searchParams.set("level", String(level));
        }

        const renderDayButtons = () => {
            tabsContainer.innerHTML = days.map((entry, index) => {
                const dayLabel = capitalizeFirst(formatIsoDate(entry.date, timeZone, {
                    weekday: "long"
                }));
                const dateLabel = formatIsoDate(entry.date, timeZone, {
                    day: "numeric",
                    month: "long"
                });
                const isActive = index === activeIndex;

                return `
                    <button type="button" class="almanax-tab${isActive ? " is-active" : ""}" data-almanax-tab-index="${index}" aria-pressed="${isActive}">
                        <span>${escapeHtml(dayLabel)}</span>
                        <strong>${escapeHtml(dateLabel)}</strong>
                    </button>
                `;
            }).join("");

            tabsContainer.querySelectorAll("[data-almanax-tab-index]").forEach((button) => {
                button.addEventListener("click", () => {
                    activeIndex = Number(button.getAttribute("data-almanax-tab-index")) || 0;
                    renderActiveDay();
                });
            });
        };

        const buildRecipeMarkup = (recipeItems) => {
            if (!recipeItems.length) {
                return "";
            }

            const recipeChips = recipeItems.map((ingredient) => `
                <span class="almanax-recipe-chip">${escapeHtml(`${ingredient.quantity} x ${ingredient.name}`)}</span>
            `).join("");

            return `
                <details class="almanax-offering__recipe">
                    <summary class="almanax-offering__recipe-toggle">Voir la recette</summary>
                    <div class="almanax-offering__recipe-list">${recipeChips}</div>
                </details>
            `;
        };

        const buildDayMarkup = (selectedDay, recipeMarkup = "") => {
            if (!selectedDay) {
                return "";
            }

            const bonusPresentation = getBonusPresentation(selectedDay?.bonus?.type?.id);

            const rewardKamas = typeof selectedDay.reward_kamas === "number"
                ? numberFormatter.format(selectedDay.reward_kamas)
                : null;
            const rewardXp = typeof selectedDay.reward_xp === "number"
                ? compactNumberFormatter.format(selectedDay.reward_xp)
                : null;
            const tributeQuantity = Number(selectedDay.tribute?.quantity || 0);
            const tributeItemName = selectedDay.tribute?.item?.name || "Offrande inconnue";
            const tributeType = formatTributeType(selectedDay.tribute?.item?.subtype);
            const tributeImage = selectedDay.tribute?.item?.image_urls?.icon || "";
            const stats = [
                rewardKamas ? `
                    <article class="almanax-stat-card almanax-stat-card--kamas">
                        <span>Kamas</span>
                        <strong>${escapeHtml(rewardKamas)}</strong>
                    </article>
                ` : "",
                rewardXp ? `
                    <article class="almanax-stat-card almanax-stat-card--xp">
                        <span>XP</span>
                        <strong>${escapeHtml(rewardXp)}</strong>
                    </article>
                ` : ""
            ].filter(Boolean).join("");

            return `
                <div class="almanax-panel__layout">
                    <div class="almanax-panel__main">
                        <div class="almanax-panel__title-row">
                            <span class="almanax-bonus-badge almanax-bonus-badge--${escapeHtml(bonusPresentation.variant)}" aria-hidden="true">
                                <i class="fa-solid ${escapeHtml(bonusPresentation.icon)}"></i>
                            </span>
                            <div class="almanax-panel__title-stack">
                                <h3 class="almanax-panel__title">${escapeHtml(selectedDay.bonus?.type?.name || "Bonus du jour")}</h3>
                            </div>
                        </div>
                        <p class="almanax-panel__description">${escapeHtml(selectedDay.bonus?.description || "Aucune description disponible pour cette date.")}</p>
                        <div class="almanax-panel__stats">
                            ${stats}
                        </div>
                    </div>

                    <aside class="almanax-offering">
                        <span class="almanax-data-label">Offrande</span>
                        <div class="almanax-offering__body">
                            <img class="almanax-offering__image" src="${escapeHtml(tributeImage)}" alt="${escapeHtml(tributeItemName)}" loading="lazy">
                            <div class="almanax-offering__content">
                                <strong>${escapeHtml(`${tributeQuantity} x ${tributeItemName}`)}</strong>
                                <p>${escapeHtml(tributeType)}</p>
                            </div>
                        </div>
                        ${recipeMarkup}
                    </aside>
                </div>
            `;
        };

        const renderActiveDay = async () => {
            const selectedDay = days[activeIndex];
            const renderToken = ++activeRenderToken;
            const shouldFlash = hasRenderedActiveDay;

            if (!selectedDay) {
                return;
            }

            currentIndex.textContent = String(activeIndex + 1);
            totalCount.textContent = `/${days.length}`;

            tabsContainer.querySelectorAll("[data-almanax-tab-index]").forEach((button, index) => {
                const isActive = index === activeIndex;
                button.classList.toggle("is-active", isActive);
                button.setAttribute("aria-pressed", String(isActive));
            });

            panel.innerHTML = buildDayMarkup(selectedDay);

            if (shouldFlash) {
                flashElement(panel);
                flashElement(currentIndex, "is-flash-metric");
            }

            const itemDetails = await loadItemDetails(language, selectedDay.tribute?.item);

            if (renderToken !== activeRenderToken) {
                return;
            }

            if (!itemDetails?.recipe?.length) {
                panel.innerHTML = buildDayMarkup(selectedDay);
                hasRenderedActiveDay = true;
                return;
            }

            const recipeItems = await loadRecipeDetails(language, itemDetails.recipe);

            if (renderToken !== activeRenderToken) {
                return;
            }

            panel.innerHTML = buildDayMarkup(selectedDay, buildRecipeMarkup(recipeItems));
            hasRenderedActiveDay = true;
        };

        const loadAlmanax = async () => {
            feedback.hidden = true;
            rangeTitle.textContent = "Semaine en cours";
            panel.innerHTML = "";

            try {
                const response = await fetch(almanaxUrl.toString());
                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload?.error?.text || "Impossible de charger l'Almanax.");
                }

                if (!Array.isArray(payload) || payload.length === 0) {
                    throw new Error("Aucune donnée Almanax n'a été retournée.");
                }

                days = payload.slice(0, requestedDays);
                activeIndex = 0;
                rangeTitle.textContent = buildRangeTitle(days[0].date, days[days.length - 1].date, timeZone);
                renderDayButtons();
                renderActiveDay();
                feedback.hidden = true;
            } catch (error) {
                totalCount.textContent = "/0";
                feedback.hidden = false;
                feedback.textContent = "Impossible de charger l'Almanax pour le moment.";
                panel.innerHTML = "";
            }
        };

        loadAlmanax();
    });
};

window.frontModules.initAlmanax();
