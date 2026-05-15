const navToggle = document.querySelector("[data-nav-toggle]");
const nav = document.querySelector("[data-nav]");

const createSiteToast = () => {
    const toast = document.createElement("div");
    toast.className = "site-toast";
    toast.dataset.siteToast = "";
    toast.setAttribute("role", "status");
    toast.setAttribute("aria-live", "polite");
    document.body.appendChild(toast);

    return toast;
};

const siteToast = document.querySelector("[data-site-toast]") || createSiteToast();
let siteToastTimeout = 0;

const showSiteToast = ({
    title = "Action confirmée",
    text = "La demande a bien été prise en compte.",
    type = "success"
} = {}) => {
    const icon = type === "danger"
        ? '<i class="fa-solid fa-triangle-exclamation"></i>'
        : type === "warning"
            ? '<i class="fa-solid fa-circle-exclamation"></i>'
            : '<i class="fa-solid fa-circle-check"></i>';

    siteToast.dataset.siteToastType = type;
    siteToast.innerHTML = `
        <span class="site-toast__icon">${icon}</span>
        <span class="site-toast__content">
            <strong>${title}</strong>
            <span>${text}</span>
        </span>
    `;
    siteToast.classList.add("is-visible");

    window.clearTimeout(siteToastTimeout);
    siteToastTimeout = window.setTimeout(() => {
        siteToast.classList.remove("is-visible");
    }, 3200);
};

if (navToggle && nav) {
    const navLinks = nav.querySelectorAll("a");
    const mobileNavMedia = window.matchMedia("(max-width: 1100px)");

    const setNavState = (isOpen) => {
        nav.classList.toggle("is-open", isOpen);
        navToggle.setAttribute("aria-expanded", String(isOpen));
        navToggle.setAttribute("aria-label", isOpen ? "Fermer le menu" : "Ouvrir le menu");
        document.body.classList.toggle("is-nav-open", isOpen);
    };

    navToggle.addEventListener("click", () => {
        const isOpen = !nav.classList.contains("is-open");
        setNavState(isOpen);
    });

    nav.addEventListener("click", (event) => {
        if (event.target === nav) {
            setNavState(false);
        }
    });

    navLinks.forEach((link) => {
        link.addEventListener("click", () => {
            if (mobileNavMedia.matches) {
                setNavState(false);
            }
        });
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && nav.classList.contains("is-open")) {
            setNavState(false);
        }
    });

    const handleNavBreakpoint = () => {
        if (!mobileNavMedia.matches && nav.classList.contains("is-open")) {
            setNavState(false);
        }
    };

    if (typeof mobileNavMedia.addEventListener === "function") {
        mobileNavMedia.addEventListener("change", handleNavBreakpoint);
    } else if (typeof mobileNavMedia.addListener === "function") {
        mobileNavMedia.addListener(handleNavBreakpoint);
    }

    if (!mobileNavMedia.matches) {
        setNavState(false);
    }
}

const profileTabsRoot = document.querySelector("[data-profile-tabs-root]");
const profilePageTitle = document.querySelector("[data-profile-page-title]");
const profileTabs = document.querySelectorAll("[data-profile-tab]");

if (profileTabsRoot && profilePageTitle && profileTabs.length > 0) {
    const profilePanels = profileTabsRoot.querySelectorAll(".profile-tab-panel");

    const setActiveProfileTab = (tab) => {
        const targetSelector = tab.getAttribute("href");
        const targetPanel = targetSelector ? document.querySelector(targetSelector) : null;

        if (!targetPanel) {
            return;
        }

        profileTabs.forEach((item) => {
            item.setAttribute("aria-selected", String(item === tab));
            item.parentElement?.classList.toggle("is-active", item === tab);
        });

        profilePanels.forEach((panel) => {
            const isActive = panel === targetPanel;
            panel.classList.toggle("is-active", isActive);
            panel.hidden = !isActive;
        });

        const nextTitle = tab.dataset.title;

        if (nextTitle) {
            profilePageTitle.textContent = nextTitle;
        }
    };

    profileTabs.forEach((tab) => {
        tab.addEventListener("click", (event) => {
            event.preventDefault();
            setActiveProfileTab(tab);
        });
    });
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

const almanaxSections = document.querySelectorAll("[data-almanax]");

if (almanaxSections.length > 0) {
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
                                <h4 class="almanax-panel__title">${escapeHtml(selectedDay.bonus?.type?.name || "Bonus du jour")}</h4>
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
}

const guildVoteSections = document.querySelectorAll("[data-guild-vote]");

guildVoteSections.forEach((guildVoteRoot) => {
    const feedback = guildVoteRoot.querySelector("[data-guild-vote-feedback]");
    const registeredCount = guildVoteRoot.querySelector("[data-guild-vote-registered]");
    const submitButton = guildVoteRoot.querySelector("[data-guild-vote-submit]");
    const cancelButton = guildVoteRoot.querySelector("[data-guild-vote-cancel]");
    const dayButtons = Array.from(guildVoteRoot.querySelectorAll("[data-guild-vote-day]"));
    const dayPanels = Array.from(guildVoteRoot.querySelectorAll("[data-guild-vote-panel]"));
    const slots = Array.from(guildVoteRoot.querySelectorAll("[data-guild-vote-slot]"));
    const memberName = guildVoteRoot.dataset.memberName || "Adon";
    const initialFeedbackText = feedback?.textContent?.trim() || "";
    const defaultSlotLimit = Number(slots[0]?.dataset.slotLimit || "8");
    let activeDay = dayButtons.find((button) => button.classList.contains("is-active"))?.dataset.guildVoteDay || dayButtons[0]?.dataset.guildVoteDay || "";
    let selectedSlotId = "";
    let confirmedSlotId = "";

    const getSlotButton = (slot) => slot.querySelector("[data-guild-vote-select]");
    const getSlotCount = (slot) => slot.querySelector("[data-guild-vote-count]");
    const getSlotMembers = (slot) => slot.querySelector("[data-guild-vote-members]");
    const getSlotCta = (slot) => slot.querySelector(".guild-vote-slot__cta");
    const getSlotPanel = (slot) => slot.closest("[data-guild-vote-panel]");
    const getSlotVotes = (slot) => Number(slot.dataset.slotVotes || "0");
    const getSlotLimit = (slot) => Number(slot.dataset.slotLimit || "8");
    const parseSlotMembers = (slot) => (slot.dataset.slotMembers || "").split("|").filter(Boolean);
    const writeSlotMembers = (slot, members) => {
        slot.dataset.slotMembers = members.join("|");
    };
    const findSlotById = (slotId) => slots.find((slot) => slot.dataset.slotId === slotId) || null;
    const slotBelongsToDay = (slot, day) => getSlotPanel(slot)?.dataset.guildVotePanel === day;

    const updateFeedback = (isSuccess, text) => {
        if (!feedback) {
            return;
        }

        feedback.textContent = text;
        feedback.classList.toggle("is-success", isSuccess);
    };

    const updateRegisteredCount = () => {
        if (!registeredCount) {
            return;
        }

        const confirmedSlot = findSlotById(confirmedSlotId);
        const votes = confirmedSlot ? getSlotVotes(confirmedSlot) : 0;
        const limit = confirmedSlot ? getSlotLimit(confirmedSlot) : defaultSlotLimit;

        registeredCount.textContent = `${votes}/${limit}`;
    };

    const updateSlot = (slot) => {
        const votes = getSlotVotes(slot);
        const limit = getSlotLimit(slot);
        const members = parseSlotMembers(slot);
        const isSelected = slot.dataset.slotId === selectedSlotId;
        const isConfirmed = slot.dataset.slotId === confirmedSlotId;
        const slotButton = getSlotButton(slot);
        const slotCount = getSlotCount(slot);
        const slotMembers = getSlotMembers(slot);
        const slotCta = getSlotCta(slot);

        slot.classList.toggle("is-selected", isSelected);
        slot.classList.toggle("is-confirmed", isConfirmed);

        if (slotButton) {
            slotButton.setAttribute("aria-pressed", String(isSelected || isConfirmed));
        }

        if (slotCount) {
            slotCount.textContent = `${votes}/${limit} places`;
        }

        if (slotCta) {
            if (isConfirmed || isSelected) {
                slotCta.textContent = "";
                slotCta.hidden = true;
            } else {
                slotCta.hidden = false;
                slotCta.textContent = "Choisir ce créneau";
            }
        }

        if (slotMembers) {
            slotMembers.textContent = members.length > 0 ? members.join(", ") : "Aucun inscrit";
        }
    };

    const updateAllSlots = () => {
        slots.forEach(updateSlot);
    };

    const updateActions = () => {
        if (submitButton) {
            submitButton.disabled = !selectedSlotId || Boolean(confirmedSlotId);
        }

        if (cancelButton) {
            cancelButton.hidden = !confirmedSlotId;
        }
    };

    const setActiveDay = (day, shouldFlash = true) => {
        activeDay = day;

        dayButtons.forEach((button) => {
            const isActive = button.dataset.guildVoteDay === day;
            button.classList.toggle("is-active", isActive);
            button.setAttribute("aria-pressed", String(isActive));
        });

        dayPanels.forEach((panel) => {
            const isActive = panel.dataset.guildVotePanel === day;
            panel.classList.toggle("is-active", isActive);
            panel.hidden = !isActive;
        });

        const selectedSlot = findSlotById(selectedSlotId);

        if (selectedSlot && !slotBelongsToDay(selectedSlot, day) && selectedSlotId !== confirmedSlotId) {
            selectedSlotId = "";
        }

        updateAllSlots();
        updateActions();

        if (shouldFlash) {
            const activePanel = dayPanels.find((panel) => panel.dataset.guildVotePanel === day);
            flashElement(activePanel);
        }
    };

    dayButtons.forEach((button) => {
        button.addEventListener("click", () => {
            if (!button.dataset.guildVoteDay) {
                return;
            }

            setActiveDay(button.dataset.guildVoteDay);
        });
    });

    slots.forEach((slot) => {
        const slotButton = getSlotButton(slot);

        slotButton?.addEventListener("click", () => {
            if (confirmedSlotId && slot.dataset.slotId !== confirmedSlotId) {
                return;
            }

            selectedSlotId = slot.dataset.slotId || "";
            updateAllSlots();
            updateActions();
            flashElement(slot);
        });
    });

    submitButton?.addEventListener("click", () => {
        const slot = findSlotById(selectedSlotId);

        if (!slot || confirmedSlotId) {
            return;
        }

        const members = parseSlotMembers(slot);

        if (!members.includes(memberName)) {
            members.push(memberName);
            slot.dataset.slotVotes = String(getSlotVotes(slot) + 1);
            writeSlotMembers(slot, members);
        }

        confirmedSlotId = selectedSlotId;
        updateFeedback(true, "Vote enregistré. Inscription prise en compte.");
        showSiteToast({
            title: "Vote enregistré",
            text: "Ton inscription à la sortie est bien prise en compte."
        });
        updateRegisteredCount();
        updateAllSlots();
        updateActions();
        flashElement(slot);
        flashElement(feedback);
        flashElement(registeredCount, "is-flash-metric");
    });

    cancelButton?.addEventListener("click", () => {
        const slot = findSlotById(confirmedSlotId);

        if (!slot) {
            confirmedSlotId = "";
            selectedSlotId = "";
            updateFeedback(false, initialFeedbackText);
            updateRegisteredCount();
            updateAllSlots();
            updateActions();
            return;
        }

        const currentMembers = parseSlotMembers(slot);
        const nextMembers = currentMembers.filter((member) => member !== memberName);
        const hasRemovedMember = nextMembers.length !== currentMembers.length;

        if (hasRemovedMember) {
            slot.dataset.slotVotes = String(Math.max(getSlotVotes(slot) - 1, 0));
            writeSlotMembers(slot, nextMembers);
        }

        confirmedSlotId = "";
        selectedSlotId = "";
        updateFeedback(false, initialFeedbackText);
        showSiteToast({
            title: "Vote annulé",
            text: "Ton créneau a été libéré.",
            type: "warning"
        });
        updateRegisteredCount();
        updateAllSlots();
        updateActions();
        flashElement(feedback);
        flashElement(registeredCount, "is-flash-metric");
    });

    setActiveDay(activeDay, false);
    updateFeedback(false, initialFeedbackText);
    updateRegisteredCount();
    updateAllSlots();
    updateActions();
});

const missionForm = document.querySelector("[data-mission-form]");

if (missionForm) {
    const teammatesToggle = missionForm.querySelector("[data-mission-teammates-toggle]");
    const teammatesSection = missionForm.querySelector("[data-mission-teammates]");
    const teammatesList = missionForm.querySelector("[data-mission-teammates-list]");
    const addTeammateButton = missionForm.querySelector("[data-add-teammate]");
    const pasteProofButton = missionForm.querySelector("[data-paste-proof]");
    const proofField = missionForm.querySelector("#mission-proof");

    const updateTeammatesVisibility = () => {
        if (!teammatesToggle || !teammatesSection) {
            return;
        }

        teammatesSection.hidden = !teammatesToggle.checked;
    };

    const bindRemoveButton = (button) => {
        button.addEventListener("click", () => {
            if (!teammatesList) {
                return;
            }

            const rows = teammatesList.querySelectorAll(".mission-teammate-row");
            const currentRow = button.closest(".mission-teammate-row");

            if (!currentRow) {
                return;
            }

            if (rows.length === 1) {
                const teammateSelect = currentRow.querySelector('select[name="teammate_name[]"]');
                const teammateCount = currentRow.querySelector('input[name="teammate_characters[]"]');

                if (teammateSelect) {
                    teammateSelect.value = "";
                }

                if (teammateCount) {
                    teammateCount.value = "1";
                }

                updateRemoveButtons();
                return;
            }

            currentRow.remove();
            updateRemoveButtons();
        });
    };

    const updateRemoveButtons = () => {
        teammatesList?.querySelectorAll("[data-remove-teammate]").forEach((button, index) => {
            button.hidden = index === 0;
        });
    };

    if (teammatesToggle) {
        teammatesToggle.addEventListener("change", updateTeammatesVisibility);
        updateTeammatesVisibility();
    }

    teammatesList?.querySelectorAll("[data-remove-teammate]").forEach(bindRemoveButton);
    updateRemoveButtons();

    addTeammateButton?.addEventListener("click", () => {
        if (!teammatesList) {
            return;
        }

        const firstRow = teammatesList.querySelector(".mission-teammate-row");

        if (!firstRow) {
            return;
        }

        const nextRow = firstRow.cloneNode(true);
        const nextSelect = nextRow.querySelector('select[name="teammate_name[]"]');
        const nextInput = nextRow.querySelector('input[name="teammate_characters[]"]');
        const nextRemoveButton = nextRow.querySelector("[data-remove-teammate]");

        if (nextSelect) {
            nextSelect.value = "";
        }

        if (nextInput) {
            nextInput.value = "1";
        }

        if (nextRemoveButton) {
            nextRemoveButton.hidden = false;
            bindRemoveButton(nextRemoveButton);
        }

        teammatesList.appendChild(nextRow);
        updateRemoveButtons();
    });

    pasteProofButton?.addEventListener("click", async () => {
        if (!proofField) {
            return;
        }

        proofField.focus();

        if (!navigator.clipboard?.readText) {
            return;
        }

        try {
            const clipboardText = await navigator.clipboard.readText();

            if (clipboardText) {
                proofField.value = clipboardText;
            }
        } catch (error) {
            // Leave the field focused so the user can paste manually.
        }
    });
}

document.querySelectorAll(".form-block, .form-stack").forEach((form) => {
    form.addEventListener("submit", (event) => {
        event.preventDefault();

        if (form.classList.contains("login-card") || form.closest(".login-card")) {
            showSiteToast({
                title: "Connexion réussie",
                text: "Bienvenue, tu peux continuer sur ton espace de guilde."
            });
            return;
        }

        if (form.matches("[data-mission-form]")) {
            showSiteToast({
                title: "Mission envoyée",
                text: "Ta déclaration est partie en validation auprès du meneur."
            });
            return;
        }

        const submitButtonText = form.querySelector('button[type="submit"]')?.textContent.trim().toLowerCase() || "";
        const isPasswordForm = submitButtonText.includes("mot de passe");

        showSiteToast({
            title: isPasswordForm ? "Mot de passe modifié" : "Profil mis à jour",
            text: isPasswordForm
                ? "Ton nouveau mot de passe a été enregistré."
                : "Tes informations de profil ont bien été enregistrées."
        });
    });
});

document.querySelectorAll(".profile-security-block .btn").forEach((button) => {
    button.addEventListener("click", () => {
        const isDanger = button.classList.contains("btn--danger");

        if (isDanger) {
            const isConfirmed = window.confirm("Supprimer le compte ? Cette action supprimera les informations associées à ton compte.");

            if (isConfirmed) {
                showSiteToast({
                    title: "Demande enregistrée",
                    text: "La suppression du compte est prête à être traitée.",
                    type: "warning"
                });
            }
            return;
        }

        showSiteToast({
            title: "Double authentification",
            text: "L'activation de la 2FA est prête. Il restera à scanner le QR code côté backend."
        });
    });
});

document.querySelectorAll(".account-action--logout a").forEach((link) => {
    link.addEventListener("click", (event) => {
        event.preventDefault();
        showSiteToast({
            title: "Déconnexion",
            text: "Ta session serait fermée ici lorsque l'authentification sera branchée.",
            type: "warning"
        });
    });
});

const newsModal = document.getElementById("news-modal");

if (newsModal) {
    const modalTag = document.getElementById("news-modal-tag");
    const modalDate = document.getElementById("news-modal-date");
    const modalTitle = document.getElementById("news-modal-title");
    const modalContent = document.getElementById("news-modal-content");
    const openButtons = document.querySelectorAll("[data-news-source]");
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
}
