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

const sessionToastTitle = document.querySelector('meta[name="site-toast-title"]')?.content;

if (sessionToastTitle) {
    window.setTimeout(() => {
        showSiteToast({
            title: sessionToastTitle,
            text: document.querySelector('meta[name="site-toast-text"]')?.content || "",
            type: document.querySelector('meta[name="site-toast-type"]')?.content || "success"
        });
    }, 150);
}

if (navToggle && nav) {
    const navLinks = nav.querySelectorAll("a");
    const mobileNavMedia = window.matchMedia("(max-width: 1180px)");

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

const stuffCatalog = document.querySelector("[data-stuff-catalog]");

if (stuffCatalog) {
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
}

const guideCatalog = document.querySelector("[data-guide-catalog]");

if (guideCatalog) {
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
}

const guideDetailRoot = document.querySelector("[data-guide-detail]");

if (guideDetailRoot) {
    const databaseGuide = (() => {
        try {
            return JSON.parse(document.querySelector("#guide-detail-data")?.textContent || "null");
        } catch (error) {
            return null;
        }
    })();
    const guide = databaseGuide;

    if (guide) {
    const title = guideDetailRoot.querySelector("[data-guide-detail-title]");
    const type = guideDetailRoot.querySelector("[data-guide-detail-type]");
    const summary = guideDetailRoot.querySelector("[data-guide-detail-summary]");
    const image = guideDetailRoot.querySelector("[data-guide-detail-image]");
    const map = guideDetailRoot.querySelector("[data-guide-detail-map]");
    const mapEmpty = guideDetailRoot.querySelector("[data-guide-detail-map-empty]");
    const chips = guideDetailRoot.querySelector("[data-guide-detail-chips]");
    const checklist = guideDetailRoot.querySelector("[data-guide-detail-checklist]");
    const content = guideDetailRoot.querySelector("[data-guide-detail-content]");
    const placementContent = guideDetailRoot.querySelector("[data-guide-detail-placement-content]");
    const spellsContent = guideDetailRoot.querySelector("[data-guide-detail-spells]");
    const nav = guideDetailRoot.querySelector("[data-guide-detail-nav]");
    const sidebar = guideDetailRoot.querySelector(".guide-detail-sidebar");

    document.title = `${guide.title} | Guide mission`;

    if (title) {
        title.textContent = guide.title;
    }

    if (type) {
        type.textContent = guide.type;
    }

    if (summary) {
        summary.textContent = guide.summary || "Aucun résumé mis pour le moment.";
    }

    if (image) {
        image.src = guide.image;
        image.alt = guide.title;
    }

    if (map && guide.map) {
        map.src = guide.map;
        map.hidden = false;
    } else if (map) {
        map.hidden = true;
    }

    if (chips) {
        chips.innerHTML = guide.chips.map((chip) => `<span>${chip}</span>`).join("");
    }

    if (checklist) {
        const hasChecklist = guide.checklist.length > 0;
        checklist.innerHTML = guide.checklist.length > 0
            ? guide.checklist.map((item) => `<li>${item}</li>`).join("")
            : '<li class="guide-detail-empty">Aucun résumé mis pour le moment.</li>';
        checklist.closest("[data-guide-panel]")?.classList.toggle("is-empty", !hasChecklist);
    }

    const getGuideSectionId = (value) => value.toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-|-$/g, "");

    const escapeGuideText = (value) => String(value || "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");

    const renderGuideFigures = (section) => {
        const images = Array.isArray(section?.images)
            ? section.images
            : (section?.image ? [{ image: section.image, caption: section.caption || "" }] : []);

        return images.map((imageItem) => `
            <figure>
                <img src="${escapeGuideText(imageItem.image)}" alt="">
                ${imageItem.caption ? `<figcaption>${escapeGuideText(imageItem.caption).replace(/\r?\n/g, "<br>")}</figcaption>` : ""}
            </figure>
        `).join("");
    };

    const renderGuideSections = (sections, emptyText) => {
        const hasSections = Array.isArray(sections) && sections.length > 0;

        return hasSections ? sections.map((section) => `
            <section id="${getGuideSectionId(section.title || "section")}">
                ${section.title ? `<h2>${escapeGuideText(section.title)}</h2>` : ""}
                ${section.body || ""}
                ${renderGuideFigures(section)}
            </section>
        `).join("") : `<p class="guide-detail-empty">${emptyText}</p>`;
    };

    if (placementContent) {
        const placement = guide.placement || {};
        const hasPlacement = Boolean((placement.body || "").trim()) || (Array.isArray(placement.images) && placement.images.length > 0);
        placementContent.innerHTML = hasPlacement
            ? `${placement.body || ""}${renderGuideFigures(placement)}`
            : '<p class="guide-detail-empty">Aucun texte de placement mis pour le moment.</p>';

        if (mapEmpty) {
            mapEmpty.hidden = Boolean(guide.map) || hasPlacement;
            mapEmpty.closest(".guide-detail-map")?.classList.toggle("is-empty", !guide.map);
        }
    } else if (mapEmpty) {
        mapEmpty.hidden = Boolean(guide.map);
        mapEmpty.closest(".guide-detail-map")?.classList.toggle("is-empty", !guide.map);
    }

    if (content) {
        const hasSections = guide.sections.length > 0;
        content.innerHTML = renderGuideSections(guide.sections, "Aucune stratégie mise pour le moment.");
        content.closest("[data-guide-panel]")?.classList.toggle("is-empty", !hasSections);
    }

    if (spellsContent) {
        const spells = Array.isArray(guide.spells) ? guide.spells : [];
        spellsContent.innerHTML = renderGuideSections(spells, "Aucun sort de monstre mis pour le moment.");
        spellsContent.closest("[data-guide-panel]")?.classList.toggle("is-empty", spells.length === 0);
    }

    if (nav) {
        const sectionLinks = guide.sections.map((section, index) => {
            const id = getGuideSectionId(section.title);

            return `<a href="#${id}" data-guide-panel-link="strategie" data-guide-scroll-target="${id}">${section.title}<span>${index + 1}</span></a>`;
        }).join("");

        nav.innerHTML = sectionLinks;
    }

    if (sidebar) {
        sidebar.hidden = guide.sections.length === 0;
    }

    const tabs = Array.from(guideDetailRoot.querySelectorAll("[data-guide-tab]"));
    const panels = Array.from(guideDetailRoot.querySelectorAll("[data-guide-panel]"));
    const sidebarLinks = Array.from(guideDetailRoot.querySelectorAll("[data-guide-panel-link]"));

    const setActiveGuidePanel = (panelName, scrollTarget = "") => {
        const firstStrategyLink = sidebarLinks.find((link) => link.dataset.guidePanelLink === "strategie");

        panels.forEach((panel) => {
            const isActive = panel.dataset.guidePanel === panelName;
            panel.classList.toggle("is-active", isActive);
            panel.hidden = !isActive;
        });

        tabs.forEach((tab) => {
            const isActive = tab.dataset.guideTab === panelName;
            tab.classList.toggle("is-active", isActive);
            tab.setAttribute("aria-selected", String(isActive));
        });

        sidebarLinks.forEach((link) => {
            const linkPanel = link.dataset.guidePanelLink;
            const linkTarget = link.dataset.guideScrollTarget || link.getAttribute("href")?.replace("#", "");
            const isExactStrategyLink = panelName === "strategie" && scrollTarget && linkTarget === scrollTarget;
            const isFirstStrategyLink = panelName === "strategie" && !scrollTarget && link === firstStrategyLink;
            const isPanelLink = linkPanel === panelName && !link.dataset.guideScrollTarget && (!scrollTarget || panelName !== "strategie");

            link.classList.toggle("is-active", isExactStrategyLink || isFirstStrategyLink || isPanelLink);
        });

        if (scrollTarget) {
            window.requestAnimationFrame(() => {
                document.getElementById(scrollTarget)?.scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });
            });
        }
    };

    tabs.forEach((tab) => {
        tab.addEventListener("click", (event) => {
            event.preventDefault();
            setActiveGuidePanel(tab.dataset.guideTab || "resume");
        });
    });

    sidebarLinks.forEach((link) => {
        link.addEventListener("click", (event) => {
            event.preventDefault();
            const panelName = link.dataset.guidePanelLink || "resume";
            const scrollTarget = link.dataset.guideScrollTarget || "";
            setActiveGuidePanel(panelName, scrollTarget);
        });
    });

    const initialHash = window.location.hash.replace("#", "");
    const initialLink = initialHash ? sidebarLinks.find((link) => {
        const target = link.dataset.guideScrollTarget || link.getAttribute("href")?.replace("#", "");
        return target === initialHash;
    }) : null;
    const initialPanel = initialHash ? panels.find((panel) => panel.dataset.guidePanel === initialHash) : null;

    if (initialLink) {
        setActiveGuidePanel(initialLink.dataset.guidePanelLink || "resume", initialLink.dataset.guideScrollTarget || "");
    } else if (initialPanel) {
        setActiveGuidePanel(initialPanel.dataset.guidePanel || "resume");
    } else {
        setActiveGuidePanel("resume");
    }
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

    const syncProfileTabUrl = (tab) => {
        const panelName = tab.getAttribute("href")?.replace("#", "").replace("-panel", "");

        if (!panelName || !window.history?.replaceState) {
            return;
        }

        const nextUrl = new URL(window.location.href);

        if (panelName === "profile") {
            nextUrl.searchParams.delete("tab");
        } else {
            nextUrl.searchParams.set("tab", panelName);
        }

        window.history.replaceState(null, "", `${nextUrl.pathname}${nextUrl.search}${nextUrl.hash}`);
    };

    profileTabs.forEach((tab) => {
        tab.addEventListener("click", (event) => {
            event.preventDefault();
            setActiveProfileTab(tab);
            syncProfileTabUrl(tab);
        });
    });

    const requestedProfileTab = new URLSearchParams(window.location.search).get("tab");
    const initialProfileTarget = requestedProfileTab ? `#${requestedProfileTab}-panel` : window.location.hash;
    const initialProfileTab = initialProfileTarget
        ? Array.from(profileTabs).find((tab) => tab.getAttribute("href") === initialProfileTarget)
        : null;

    if (initialProfileTab) {
        setActiveProfileTab(initialProfileTab);
    }
}

const profileAvatarInput = document.querySelector("[data-profile-avatar-input]");
const profileAvatarPreviews = document.querySelectorAll("[data-profile-avatar-preview]");
const profileAvatarName = document.querySelector("[data-profile-avatar-name]");

const updateProfileAvatarPreviews = (imageUrl) => {
    profileAvatarPreviews.forEach((preview) => {
        preview.innerHTML = `<img src="${imageUrl}" alt="Photo de profil">`;
    });
};

profileAvatarInput?.addEventListener("change", async () => {
    const file = profileAvatarInput.files?.[0] || null;

    if (profileAvatarName) {
        profileAvatarName.textContent = file ? "Enregistrement..." : "";
        profileAvatarName.hidden = !file;
    }

    if (!file) {
        return;
    }

    const imageUrl = URL.createObjectURL(file);
    updateProfileAvatarPreviews(imageUrl);

    const formData = new FormData();
    formData.append("avatar", file);

    try {
        const response = await fetch(profileAvatarInput.dataset.profileAvatarUrl || "/profil/avatar", {
            method: "POST",
            body: formData,
            headers: {
                "Accept": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || ""
            }
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(payload.message || "Upload impossible.");
        }

        if (payload.avatar_url) {
            updateProfileAvatarPreviews(payload.avatar_url);
        }

        if (profileAvatarName) {
            profileAvatarName.textContent = "Photo enregistrée";
        }

        showSiteToast({
            title: "Photo de profil",
            text: payload.message || "Ta photo a bien été enregistrée.",
            type: "success"
        });
    } catch (error) {
        if (profileAvatarName) {
            profileAvatarName.textContent = "Upload impossible";
        }

        showSiteToast({
            title: "Action impossible",
            text: error.message || "La photo n'a pas pu être enregistrée.",
            type: "danger"
        });
    }
});

const galleryRoot = document.querySelector("[data-gallery-root]");

if (galleryRoot) {
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
}

const rankingTable = document.querySelector("[data-ranking-table]");

if (rankingTable) {
    const rankingBody = rankingTable.querySelector("tbody");
    const rankingSortButtons = rankingTable.querySelectorAll("[data-ranking-sort]");
    let activeRankingSort = "month";
    let activeRankingDirection = "desc";
    const rankIcons = [
        '<i class="fa-solid fa-trophy"></i>',
        '<i class="fa-solid fa-medal"></i>',
        '<i class="fa-solid fa-award"></i>'
    ];

    const updateRankingRows = () => {
        Array.from(rankingBody?.querySelectorAll("tr:not([data-ranking-empty])") || []).forEach((row, index) => {
            row.classList.toggle("is-gold", index === 0);
            row.classList.toggle("is-silver", index === 1);
            row.classList.toggle("is-bronze", index === 2);

            const rankCell = row.querySelector("td:first-child span");

            if (rankCell) {
                rankCell.innerHTML = `${rankIcons[index] || ""} #${index + 1}`;
            }
        });
    };

    rankingSortButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const sortKey = button.dataset.rankingSort;
            const rows = Array.from(rankingBody?.querySelectorAll("tr:not([data-ranking-empty])") || []);

            if (activeRankingSort === sortKey) {
                activeRankingDirection = activeRankingDirection === "desc" ? "asc" : "desc";
            } else {
                activeRankingSort = sortKey || "month";
                activeRankingDirection = "desc";
            }

            rows.sort((firstRow, secondRow) => {
                const firstValue = Number(firstRow.dataset[sortKey] || 0);
                const secondValue = Number(secondRow.dataset[sortKey] || 0);

                return activeRankingDirection === "desc" ? secondValue - firstValue : firstValue - secondValue;
            });
            rows.forEach((row) => rankingBody?.appendChild(row));

            rankingSortButtons.forEach((item) => {
                const isActive = item === button;
                item.classList.toggle("is-active", isActive);

                const indicator = item.querySelector("span");

                if (indicator && isActive) {
                    indicator.textContent = activeRankingDirection === "desc" ? "↓" : "↑";
                }
            });
            updateRankingRows();
        });
    });

    updateRankingRows();
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
    const slotInput = guildVoteRoot.querySelector("[data-guild-vote-slot-input]");
    const dayButtons = Array.from(guildVoteRoot.querySelectorAll("[data-guild-vote-day]"));
    const dayPanels = Array.from(guildVoteRoot.querySelectorAll("[data-guild-vote-panel]"));
    const slots = Array.from(guildVoteRoot.querySelectorAll("[data-guild-vote-slot]"));
    const memberName = guildVoteRoot.dataset.memberName || "Invité";
    const initialFeedbackText = feedback?.textContent?.trim() || "";
    const defaultSlotLimit = Number(slots[0]?.dataset.slotLimit || "8");
    let activeDay = dayButtons.find((button) => button.classList.contains("is-active"))?.dataset.guildVoteDay || dayButtons[0]?.dataset.guildVoteDay || "";
    let selectedSlotId = slots.find((slot) => slot.dataset.slotConfirmed === "true")?.dataset.slotId || "";
    let confirmedSlotId = selectedSlotId;

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

        if (slotInput) {
            slotInput.value = selectedSlotId;
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
        if (submitButton.type === "submit") {
            return;
        }

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
    const fileInput = missionForm.querySelector("[data-mission-file-input]");
    const fileName = missionForm.querySelector("[data-mission-file-name]");

    const updateTeammateOptions = () => {
        if (!teammatesList) {
            return;
        }

        const selects = Array.from(teammatesList.querySelectorAll('select[name="teammate_name[]"]'));
        const selectedValues = selects
            .map((select) => select.value)
            .filter(Boolean);

        selects.forEach((select) => {
            Array.from(select.options).forEach((option) => {
                option.hidden = option.value !== "" && option.value !== select.value && selectedValues.includes(option.value);
            });
        });
    };

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
                updateTeammateOptions();
                return;
            }

            currentRow.remove();
            updateRemoveButtons();
            updateTeammateOptions();
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
    teammatesList?.querySelectorAll('select[name="teammate_name[]"]').forEach((select) => {
        select.addEventListener("change", updateTeammateOptions);
    });
    updateRemoveButtons();
    updateTeammateOptions();

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

        nextSelect?.addEventListener("change", updateTeammateOptions);
        teammatesList.appendChild(nextRow);
        updateRemoveButtons();
        updateTeammateOptions();
    });

    fileInput?.addEventListener("change", () => {
        if (fileName) {
            fileName.textContent = fileInput.files?.[0]?.name || "Choisir un fichier";
        }
    });
}

document.querySelectorAll(".form-block, .form-stack").forEach((form) => {
    form.addEventListener("submit", (event) => {
        if (form.matches("[data-auth-form], [data-real-form]")) {
            return;
        }

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
