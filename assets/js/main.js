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
