const adminApp = document.querySelector(".admin-app");
const adminSidebar = document.querySelector(".admin-sidebar");
const adminMenuButton = document.querySelector(".admin-menu-button");

if (adminApp && adminSidebar && adminMenuButton) {
    const mobileNavigation = window.matchMedia("(max-width: 640px)");

    if (!adminSidebar.id) {
        adminSidebar.id = "admin-sidebar";
    }

    adminMenuButton.setAttribute("aria-controls", adminSidebar.id);

    const setMenuState = (isOpen) => {
        adminApp.classList.toggle("is-sidebar-open", isOpen);
        adminApp.classList.toggle("is-sidebar-collapsed", !isOpen && !mobileNavigation.matches);
        adminMenuButton.setAttribute("aria-expanded", String(isOpen));
        adminMenuButton.setAttribute("aria-label", isOpen ? "Fermer la navigation" : "Ouvrir la navigation");
    };

    const getInitialMenuState = () => true;

    setMenuState(getInitialMenuState());

    adminMenuButton.addEventListener("click", () => {
        const isOpen = !adminApp.classList.contains("is-sidebar-open");

        setMenuState(isOpen);
    });

    mobileNavigation.addEventListener("change", () => {
        setMenuState(getInitialMenuState());
    });
}

const confirmTrashModal = document.querySelector("[data-confirm-trash-modal]");
const confirmTrashName = document.querySelector("[data-confirm-trash-name]");
const confirmTrashSubmit = document.querySelector("[data-confirm-trash-submit]");
const confirmTrashTitle = document.getElementById("trash-modal-title");
const confirmTrashText = document.getElementById("trash-modal-text");
let confirmTrashUrl = "";
let confirmTrashSuccessMessage = null;
const confirmActionModal = document.querySelector("[data-confirm-action-modal]");
const confirmActionIcon = document.querySelector("[data-confirm-action-icon]");
const confirmActionTitle = document.querySelector("[data-confirm-action-title]");
const confirmActionText = document.querySelector("[data-confirm-action-text]");
const confirmActionName = document.querySelector("[data-confirm-action-name]");
const confirmActionSubmit = document.querySelector("[data-confirm-action-submit]");
let confirmActionUrl = "";
let confirmActionSuccessMessage = null;
const adminPendingAlertKey = "les-zheros-admin-pending-alert";
const adminEntityByFile = {
    "admin-announcements.html": { article: "cette", label: "annonce", plural: "annonces" },
    "admin-announcements-trash.html": { article: "cette", label: "annonce", plural: "annonces" },
    "admin-gallery.html": { article: "cette", label: "image", plural: "images" },
    "admin-gallery-trash.html": { article: "cette", label: "image", plural: "images" },
    "admin-guides.html": { article: "ce", label: "guide", plural: "guides" },
    "admin-guides-trash.html": { article: "ce", label: "guide", plural: "guides" },
    "admin-missions.html": { article: "cette", label: "mission", plural: "missions" },
    "admin-missions-trash.html": { article: "cette", label: "mission", plural: "missions" },
    "admin-roles.html": { article: "ce", label: "rôle", plural: "rôles" },
    "admin-roles-trash.html": { article: "ce", label: "rôle", plural: "rôles" },
    "admin-stuffs.html": { article: "ce", label: "stuff", plural: "stuffs" },
    "admin-stuffs-trash.html": { article: "ce", label: "stuff", plural: "stuffs" },
    "admin-users.html": { article: "cet", label: "utilisateur", plural: "utilisateurs" },
    "admin-users-trash.html": { article: "cet", label: "utilisateur", plural: "utilisateurs" },
    "admin-validations.html": { article: "cette", label: "validation", plural: "validations" },
    "admin-validations-trash.html": { article: "cette", label: "validation", plural: "validations" }
};

const getCurrentAdminFile = () => {
    const path = window.location.pathname.split("/").pop();
    return path || "admin.html";
};

const getCurrentAdminMode = () => {
    const mode = new URLSearchParams(window.location.search).get("mode");

    if (mode === "edit") {
        return "edit";
    }

    return getCurrentAdminFile().includes("-edit.html") ? "edit" : "create";
};

const getCurrentAdminEntity = () => adminEntityByFile[getCurrentAdminFile()] || {
    article: "cet",
    label: "élément",
    plural: "éléments"
};

const createAdminToast = () => {
    const toast = document.createElement("div");
    toast.className = "admin-toast";
    toast.dataset.adminToast = "";
    toast.setAttribute("role", "status");
    toast.setAttribute("aria-live", "polite");
    document.body.appendChild(toast);

    return toast;
};

const adminToast = document.querySelector("[data-admin-toast]") || createAdminToast();
let adminToastTimeout = 0;

const openAdminAlert = ({
    title = "Action enregistrée",
    text = "Les modifications ont bien été prises en compte.",
    type = "success"
} = {}) => {
    const iconByType = {
        success: '<i class="fa-solid fa-circle-check"></i>',
        warning: '<i class="fa-solid fa-circle-exclamation"></i>',
        error: '<i class="fa-solid fa-triangle-exclamation"></i>',
        info: '<i class="fa-solid fa-circle-info"></i>'
    };
    const icon = iconByType[type] || iconByType.success;

    adminToast.dataset.adminToastType = type;
    adminToast.innerHTML = `
        <span class="admin-toast__icon">${icon}</span>
        <span class="admin-toast__content">
            <strong></strong>
            <span></span>
        </span>
    `;
    adminToast.querySelector("strong").textContent = title;
    adminToast.querySelector(".admin-toast__content span").textContent = text;
    adminToast.classList.add("is-visible");

    window.clearTimeout(adminToastTimeout);
    adminToastTimeout = window.setTimeout(() => {
        adminToast.classList.remove("is-visible");
    }, 3400);
};

window.openAdminAlert = openAdminAlert;

const queueAdminAlert = (message) => {
    if (!message) {
        return;
    }

    sessionStorage.setItem(adminPendingAlertKey, JSON.stringify(message));
};

window.queueAdminAlert = queueAdminAlert;

const showQueuedAdminAlert = () => {
    const rawMessage = sessionStorage.getItem(adminPendingAlertKey);

    if (!rawMessage) {
        return;
    }

    sessionStorage.removeItem(adminPendingAlertKey);

    try {
        openAdminAlert(JSON.parse(rawMessage));
    } catch (error) {
        openAdminAlert();
    }
};

showQueuedAdminAlert();

const adminSaveMessages = {
    "admin-announcement-create.html": {
        title: "Annonce créée",
        text: "L'annonce a bien été enregistrée."
    },
    "admin-announcement-edit.html": {
        title: "Annonce modifiée",
        text: "Les modifications de l'annonce ont bien été enregistrées."
    },
    "admin-gallery-create.html": {
        title: "Image ajoutée",
        text: "L'image a bien été ajoutée à la galerie."
    },
    "admin-gallery-edit.html": {
        title: "Image modifiée",
        text: "Les modifications de l'image ont bien été enregistrées."
    },
    "admin-guide-create.html": {
        title: "Guide créé",
        text: "Le guide a bien été enregistré."
    },
    "admin-guide-create.html:edit": {
        title: "Guide modifié",
        text: "Les modifications du guide ont bien été enregistrées."
    },
    "admin-mission-create.html": {
        title: "Mission ajoutée",
        text: "La mission a bien été enregistrée."
    },
    "admin-mission-edit.html": {
        title: "Mission modifiée",
        text: "Les modifications de la mission ont bien été enregistrées."
    },
    "admin-role-create.html": {
        title: "Rôle créé",
        text: "Le rôle et ses permissions ont bien été enregistrés."
    },
    "admin-role-create.html:edit": {
        title: "Rôle modifié",
        text: "Les modifications du rôle ont bien été enregistrées."
    },
    "admin-stuff-create.html": {
        title: "Stuff ajouté",
        text: "Le stuff a bien été ajouté au catalogue."
    },
    "admin-stuff-create.html:edit": {
        title: "Stuff modifié",
        text: "Les modifications du stuff ont bien été enregistrées."
    },
    "admin-user-create.html": {
        title: "Utilisateur créé",
        text: "Le nouvel utilisateur a bien été ajouté."
    },
    "admin-user-edit.html": {
        title: "Utilisateur modifié",
        text: "Les modifications de l'utilisateur ont bien été enregistrées."
    },
    "admin-validation-create.html": {
        title: "Déclaration ajoutée",
        text: "La déclaration joueur a bien été enregistrée."
    },
    "admin-validation-edit.html": {
        title: "Déclaration modifiée",
        text: "Les modifications de la déclaration ont bien été enregistrées."
    }
};

const adminDraftMessages = {
    "admin-announcement-create.html": "L'annonce a bien été enregistrée en brouillon.",
    "admin-announcement-edit.html": "Les modifications de l'annonce ont bien été enregistrées en brouillon.",
    "admin-gallery-create.html": "L'image a bien été enregistrée en brouillon.",
    "admin-gallery-edit.html": "Les modifications de l'image ont bien été enregistrées en brouillon.",
    "admin-guide-create.html": "Le guide a bien été enregistré en brouillon.",
    "admin-guide-create.html:edit": "Les modifications du guide ont bien été enregistrées en brouillon.",
    "admin-mission-create.html": "La mission a bien été enregistrée en brouillon.",
    "admin-mission-edit.html": "Les modifications de la mission ont bien été enregistrées en brouillon."
};

const adminModeLabels = {
    "admin-guide-create.html:edit": {
        title: "Modifier un guide | Les Zheros",
        heading: "Modifier un guide",
        breadcrumb: "Guides / Modifier"
    },
    "admin-role-create.html:edit": {
        title: "Modifier un rôle | Les Zheros",
        heading: "Modifier un rôle",
        breadcrumb: "Rôles / Modifier"
    }
};

const adminSaveRedirects = {
    "admin-announcement-create.html": "admin-announcements.html",
    "admin-announcement-edit.html": "admin-announcements.html",
    "admin-gallery-create.html": "admin-gallery.html",
    "admin-gallery-edit.html": "admin-gallery.html",
    "admin-guide-create.html": "admin-guides.html",
    "admin-mission-create.html": "admin-missions.html",
    "admin-mission-edit.html": "admin-missions.html",
    "admin-role-create.html": "admin-roles.html",
    "admin-stuff-create.html": "admin-stuffs.html",
    "admin-user-create.html": "admin-users.html",
    "admin-user-edit.html": "admin-users.html",
    "admin-validation-create.html": "admin-validations.html",
    "admin-validation-edit.html": "admin-validations.html"
};

const applyAdminModeLabels = () => {
    const labels = adminModeLabels[`${getCurrentAdminFile()}:${getCurrentAdminMode()}`];

    if (!labels) {
        return;
    }

    document.title = labels.title;

    const heading = document.querySelector(".admin-title h1");
    const breadcrumb = document.querySelector(".admin-breadcrumb p");

    if (heading) {
        heading.textContent = labels.heading;
    }

    if (breadcrumb) {
        breadcrumb.textContent = labels.breadcrumb;
    }
};

applyAdminModeLabels();

const getAdminSaveMessage = (submitter) => {
    const messageKey = `${getCurrentAdminFile()}:${getCurrentAdminMode()}`;
    const pageMessage = adminSaveMessages[messageKey] || adminSaveMessages[getCurrentAdminFile()];
    const submitText = submitter?.textContent.trim().toLowerCase() || "";

    if (submitText.includes("brouillon")) {
        return {
            title: "Brouillon enregistré",
            text: adminDraftMessages[messageKey] || adminDraftMessages[getCurrentAdminFile()] || "Le brouillon a bien été enregistré."
        };
    }

    return pageMessage || {
        title: "Enregistrement terminé",
        text: "Les informations ont bien été enregistrées."
    };
};

const redirectAfterAdminSave = (message) => {
    const targetUrl = adminSaveRedirects[getCurrentAdminFile()];

    if (!targetUrl) {
        openAdminAlert(message);
        return;
    }

    queueAdminAlert(message);
    window.location.href = targetUrl;
};

const syncAdminRichEditors = (form) => {
    form.querySelectorAll("[data-rich-editor]").forEach((editor) => {
        const surface = editor.querySelector("[data-editor-surface]");
        const input = editor.querySelector("[data-editor-input]");

        if (surface && input) {
            input.value = surface.innerHTML.trim();
        }
    });
};

document.querySelectorAll('button[type="submit"]').forEach((button) => {
    button.addEventListener("click", () => {
        const form = button.form;

        if (form) {
            syncAdminRichEditors(form);
        }

        if (!form || form.checkValidity()) {
            return;
        }

        window.setTimeout(() => {
            openAdminAlert({
                title: "Formulaire incomplet",
                text: "Remplis les champs obligatoires avant d'enregistrer.",
                type: "warning"
            });
        }, 0);
    });
});

document.addEventListener("invalid", (event) => {
    const form = event.target.closest("form");

    if (!form || form.dataset.adminInvalidAlertShown === "true") {
        return;
    }

    form.dataset.adminInvalidAlertShown = "true";
    window.setTimeout(() => {
        openAdminAlert({
            title: "Formulaire incomplet",
            text: "Remplis les champs obligatoires avant d'enregistrer.",
            type: "warning"
        });
        delete form.dataset.adminInvalidAlertShown;
    }, 0);
}, true);

document.querySelectorAll("form").forEach((form) => {
    if (form.matches("[data-lottery-settings]")) {
        return;
    }

    form.addEventListener("submit", (event) => {
        event.preventDefault();
        syncAdminRichEditors(form);

        redirectAfterAdminSave(getAdminSaveMessage(event.submitter));
    });
});
const closeConfirmTrashModal = () => {
    if (!confirmTrashModal) {
        return;
    }

    confirmTrashModal.hidden = true;
    confirmTrashUrl = "";
    confirmTrashSuccessMessage = null;
};

const openConfirmTrashModal = (name, url) => {
    if (!confirmTrashModal) {
        return;
    }

    const entity = getCurrentAdminEntity();
    confirmTrashUrl = url || "";
    confirmTrashSuccessMessage = {
        title: "Déplacé à la corbeille",
        text: `L'élément « ${name || `${entity.article} ${entity.label}`} » est maintenant dans la corbeille des ${entity.plural}.`
    };

    if (confirmTrashTitle) {
        confirmTrashTitle.textContent = `Mettre ${entity.article} ${entity.label} à la corbeille ?`;
    }

    if (confirmTrashText) {
        confirmTrashText.innerHTML = `Cette action déplacera <strong data-confirm-trash-name></strong> dans la corbeille des ${entity.plural}.`;
    }

    const nameTarget = confirmTrashModal.querySelector("[data-confirm-trash-name]") || confirmTrashName;

    if (nameTarget) {
        nameTarget.textContent = name || `${entity.article} ${entity.label}`;
    }

    confirmTrashModal.hidden = false;
    confirmTrashSubmit?.focus();
};

document.querySelectorAll("[data-confirm-trash]").forEach((button) => {
    button.addEventListener("click", () => {
        openConfirmTrashModal(button.dataset.confirmTrash || "cet élément", button.dataset.confirmTrashUrl || "");
    });
});

document.querySelectorAll("[data-confirm-trash-cancel]").forEach((button) => {
    button.addEventListener("click", closeConfirmTrashModal);
});

confirmTrashSubmit?.addEventListener("click", () => {
    if (confirmTrashUrl) {
        queueAdminAlert(confirmTrashSuccessMessage);
        window.location.href = confirmTrashUrl;
    }
});

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && confirmTrashModal && !confirmTrashModal.hidden) {
        closeConfirmTrashModal();
    }
});

const closeConfirmActionModal = () => {
    if (!confirmActionModal) {
        return;
    }

    confirmActionModal.hidden = true;
    confirmActionUrl = "";
    confirmActionSuccessMessage = null;
};

const addEmptyTrashButton = () => {
    if (!getCurrentAdminFile().endsWith("-trash.html") || !confirmActionModal || document.querySelector('[data-confirm-action="empty"]')) {
        return;
    }

    const actions = document.querySelector(".admin-actions");

    if (!actions) {
        return;
    }

    const entity = getCurrentAdminEntity();
    const button = document.createElement("button");
    button.className = "admin-danger-button";
    button.type = "button";
    button.dataset.confirmAction = "empty";
    button.dataset.confirmUrl = getCurrentAdminFile();
    button.innerHTML = '<i class="fa-regular fa-trash-can"></i><span>Vider la corbeille</span>';
    button.setAttribute("aria-label", `Vider la corbeille des ${entity.plural}`);
    actions.appendChild(button);
};

addEmptyTrashButton();

document.querySelectorAll("[data-confirm-action]").forEach((button) => {
    button.addEventListener("click", () => {
        if (!confirmActionModal) {
            return;
        }

        const mode = button.dataset.confirmAction || "delete";
        const isRestore = mode === "restore";
        const isEmptyTrash = mode === "empty";
        const entity = getCurrentAdminEntity();
        const itemName = button.dataset.confirmName || `${entity.article} ${entity.label}`;
        confirmActionUrl = button.dataset.confirmUrl || "";
        confirmActionSuccessMessage = isEmptyTrash
            ? {
                title: "Corbeille vidée",
                text: `La corbeille des ${entity.plural} a bien été vidée.`
            }
            : isRestore
                ? {
                title: "Remis en ligne",
                    text: `L'élément « ${itemName} » est de retour dans la liste des ${entity.plural}.`
                }
                : {
                    title: "Suppression définitive",
                    text: `L'élément « ${itemName} » a été supprimé définitivement.`
                };

        if (confirmActionIcon) {
            confirmActionIcon.classList.toggle("admin-modal__icon--restore", isRestore);
            confirmActionIcon.innerHTML = isEmptyTrash
                ? '<i class="fa-solid fa-broom"></i>'
                : isRestore
                ? '<i class="fa-solid fa-rotate-left"></i>'
                : '<i class="fa-regular fa-trash-can"></i>';
        }

        if (confirmActionTitle) {
            confirmActionTitle.textContent = isEmptyTrash
                ? `Vider la corbeille des ${entity.plural} ?`
                : isRestore
                ? `Restaurer ${entity.article} ${entity.label} ?`
                : `Supprimer définitivement ${entity.article} ${entity.label} ?`;
        }

        if (confirmActionText) {
            confirmActionText.innerHTML = isEmptyTrash
                ? `Cette action supprimera définitivement tous les éléments de la corbeille des ${entity.plural}.`
                : isRestore
                ? `Cette action remettra <strong data-confirm-action-name></strong> dans la liste des ${entity.plural}.`
                : `Cette action supprimera <strong data-confirm-action-name></strong> de la corbeille des ${entity.plural}.`;
            confirmActionText.querySelector("[data-confirm-action-name]")?.replaceChildren(document.createTextNode(itemName));
        } else if (confirmActionName) {
            confirmActionName.textContent = itemName;
        }

        if (confirmActionSubmit) {
            confirmActionSubmit.textContent = isEmptyTrash
                ? "Vider la corbeille"
                : isRestore ? "Restaurer" : "Supprimer définitivement";
            confirmActionSubmit.classList.toggle("admin-danger-button", !isRestore);
            confirmActionSubmit.classList.toggle("admin-create-button", isRestore);
        }

        confirmActionModal.hidden = false;
        confirmActionSubmit?.focus();
    });
});

document.querySelectorAll("[data-confirm-action-cancel]").forEach((button) => {
    button.addEventListener("click", closeConfirmActionModal);
});

confirmActionSubmit?.addEventListener("click", () => {
    if (confirmActionUrl) {
        queueAdminAlert(confirmActionSuccessMessage);
        window.location.href = confirmActionUrl;
    }
});

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && confirmActionModal && !confirmActionModal.hidden) {
        closeConfirmActionModal();
    }
});

const positionAdminActionMenu = (menu) => {
    const summary = menu.querySelector("summary");
    const panel = menu.querySelector(":scope > div");

    if (!summary || !panel || !menu.open) {
        return;
    }

    const summaryRect = summary.getBoundingClientRect();
    const panelRect = panel.getBoundingClientRect();
    const gap = 6;
    const viewportGap = 10;
    const top = Math.min(summaryRect.bottom + gap, window.innerHeight - panelRect.height - viewportGap);
    const left = Math.min(
        Math.max(viewportGap, summaryRect.right - panelRect.width),
        window.innerWidth - panelRect.width - viewportGap
    );

    menu.style.setProperty("--action-menu-top", `${Math.max(viewportGap, top)}px`);
    menu.style.setProperty("--action-menu-left", `${left}px`);
};

const positionOpenAdminActionMenus = () => {
    document.querySelectorAll(".admin-action-menu[open]").forEach(positionAdminActionMenu);
};

document.querySelectorAll(".admin-action-menu").forEach((menu) => {
    menu.addEventListener("toggle", () => {
        if (menu.open) {
            requestAnimationFrame(() => positionAdminActionMenu(menu));
        }
    });
});

document.addEventListener("click", (event) => {
    document.querySelectorAll(".admin-action-menu[open]").forEach((menu) => {
        if (!menu.contains(event.target)) {
            menu.removeAttribute("open");
        }
    });
});

window.addEventListener("resize", positionOpenAdminActionMenus);
window.addEventListener("scroll", positionOpenAdminActionMenus, true);

const normalizeAdminSearchText = (value) => String(value || "")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase();

document.querySelectorAll(".admin-search input").forEach((searchInput) => {
    if (
        searchInput.matches("[data-gallery-search], [data-stuff-search], [data-validation-filter], [data-monster-search]")
    ) {
        return;
    }

    const root = searchInput.closest(".admin-main") || document;
    const searchableItems = Array.from(root.querySelectorAll(".admin-table tbody > tr, .admin-guide-card"));

    if (!searchableItems.length) {
        return;
    }

    const applySearch = () => {
        const query = normalizeAdminSearchText(searchInput.value.trim());

        searchableItems.forEach((item) => {
            const searchableText = normalizeAdminSearchText(`${item.dataset.search || ""} ${item.textContent}`);
            item.hidden = query.length > 0 && !searchableText.includes(query);
        });
    };

    searchInput.addEventListener("input", applySearch);
    applySearch();
});
