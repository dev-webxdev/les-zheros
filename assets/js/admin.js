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
const confirmFormModal = document.querySelector("[data-confirm-form-modal]");
const confirmFormIcon = document.querySelector("[data-confirm-form-icon]");
const confirmFormTitle = document.querySelector("[data-confirm-form-title]");
const confirmFormText = document.querySelector("[data-confirm-form-text]");
const confirmFormSubmit = document.querySelector("[data-confirm-form-submit]");
let confirmFormTarget = null;
const adminPendingAlertKey = "les-zheros-admin-pending-alert";
const adminPageKeyByPath = {
    "/admin": "admin.dashboard",
    "/admin/annonces": "admin.annonces.index",
    "/admin/annonces/creer": "admin.annonces.create",
    "/admin/annonces/modifier": "admin.annonces.edit",
    "/admin/annonces/corbeille": "admin.annonces.trash",
    "/admin/commentaires": "admin.commentaires.index",
    "/admin/galerie": "admin.galerie.index",
    "/admin/galerie/creer": "admin.galerie.create",
    "/admin/galerie/modifier": "admin.galerie.edit",
    "/admin/galerie/corbeille": "admin.galerie.trash",
    "/admin/mediatheque": "admin.mediatheque.index",
    "/admin/guides": "admin.guides.index",
    "/admin/guides/creer": "admin.guides.create",
    "/admin/guides/corbeille": "admin.guides.trash",
    "/admin/loterie": "admin.loterie.index",
    "/admin/missions": "admin.missions.index",
    "/admin/missions/creer": "admin.missions.create",
    "/admin/missions/modifier": "admin.missions.edit",
    "/admin/missions/corbeille": "admin.missions.trash",
    "/admin/classement": "admin.classement.index",
    "/admin/roles": "admin.roles.index",
    "/admin/roles/creer": "admin.roles.create",
    "/admin/roles/corbeille": "admin.roles.trash",
    "/admin/parametres": "admin.parametres.index",
    "/admin/sorties": "admin.sorties.index",
    "/admin/sorties/creer": "admin.sorties.create",
    "/admin/sorties/corbeille": "admin.sorties.trash",
    "/admin/stuffs": "admin.stuffs.index",
    "/admin/stuffs/creer": "admin.stuffs.create",
    "/admin/stuffs/corbeille": "admin.stuffs.trash",
    "/admin/utilisateurs": "admin.utilisateurs.index",
    "/admin/utilisateurs/creer": "admin.utilisateurs.create",
    "/admin/utilisateurs/modifier": "admin.utilisateurs.edit",
    "/admin/utilisateurs/corbeille": "admin.utilisateurs.trash",
    "/admin/validations": "admin.validations.index",
    "/admin/validations/creer": "admin.validations.create",
    "/admin/validations/modifier": "admin.validations.edit",
    "/admin/validations/corbeille": "admin.validations.trash"
};
const adminEntityByFile = {
    "admin.annonces.index": { article: "cette", label: "annonce", plural: "annonces" },
    "admin.annonces.trash": { article: "cette", label: "annonce", plural: "annonces" },
    "admin.galerie.index": { article: "cette", label: "image", plural: "images" },
    "admin.galerie.trash": { article: "cette", label: "image", plural: "images" },
    "admin.guides.index": { article: "ce", label: "guide", plural: "guides" },
    "admin.guides.trash": { article: "ce", label: "guide", plural: "guides" },
    "admin.missions.index": { article: "cette", label: "mission", plural: "missions" },
    "admin.missions.trash": { article: "cette", label: "mission", plural: "missions" },
    "admin.roles.index": { article: "ce", label: "rôle", plural: "rôles" },
    "admin.roles.trash": { article: "ce", label: "rôle", plural: "rôles" },
    "admin.sorties.index": { article: "cette", label: "sortie", plural: "sorties" },
    "admin.sorties.trash": { article: "cette", label: "sortie", plural: "sorties" },
    "admin.stuffs.index": { article: "ce", label: "stuff", plural: "stuffs" },
    "admin.stuffs.trash": { article: "ce", label: "stuff", plural: "stuffs" },
    "admin.utilisateurs.index": { article: "cet", label: "utilisateur", plural: "utilisateurs" },
    "admin.utilisateurs.trash": { article: "cet", label: "utilisateur", plural: "utilisateurs" },
    "admin.validations.index": { article: "cette", label: "validation", plural: "validations" },
    "admin.validations.trash": { article: "cette", label: "validation", plural: "validations" }
};

const getCurrentAdminFile = () => {
    const path = window.location.pathname.replace(/\/$/, "") || "/admin";

    if (adminPageKeyByPath[path]) {
        return adminPageKeyByPath[path];
    }

    const file = path.split("/").pop();
    return file || "admin.dashboard";
};

const getCurrentAdminMode = () => {
    const mode = new URLSearchParams(window.location.search).get("mode");

    if (mode === "edit") {
        return "edit";
    }

    return getCurrentAdminFile().endsWith(".edit") ? "edit" : "create";
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

const sessionAdminToastTitle = document.querySelector('meta[name="admin-toast-title"]')?.content;

if (sessionAdminToastTitle) {
    window.setTimeout(() => {
        openAdminAlert({
            title: sessionAdminToastTitle,
            text: document.querySelector('meta[name="admin-toast-text"]')?.content || "",
            type: document.querySelector('meta[name="admin-toast-type"]')?.content || "success"
        });
    }, 150);
}

document.querySelectorAll("[data-copy-media-url]").forEach((button) => {
    button.addEventListener("click", async () => {
        const url = button.dataset.copyMediaUrl || "";

        try {
            await navigator.clipboard.writeText(url);
            openAdminAlert({
                title: "Lien copié",
                text: "L'URL de l'image est prête à être réutilisée.",
                type: "success"
            });
        } catch (error) {
            openAdminAlert({
                title: "Copie impossible",
                text: url,
                type: "warning"
            });
        }
    });
});

const adminSaveMessages = {
    "admin.annonces.create": {
        title: "Annonce créée",
        text: "L'annonce a bien été enregistrée."
    },
    "admin.annonces.edit": {
        title: "Annonce modifiée",
        text: "Les modifications de l'annonce ont bien été enregistrées."
    },
    "admin.galerie.create": {
        title: "Image ajoutée",
        text: "L'image a bien été ajoutée à la galerie."
    },
    "admin.galerie.edit": {
        title: "Image modifiée",
        text: "Les modifications de l'image ont bien été enregistrées."
    },
    "admin.guides.create": {
        title: "Guide créé",
        text: "Le guide a bien été enregistré."
    },
    "admin.guides.create:edit": {
        title: "Guide modifié",
        text: "Les modifications du guide ont bien été enregistrées."
    },
    "admin.missions.create": {
        title: "Mission ajoutée",
        text: "La mission a bien été enregistrée."
    },
    "admin.missions.edit": {
        title: "Mission modifiée",
        text: "Les modifications de la mission ont bien été enregistrées."
    },
    "admin.roles.create": {
        title: "Rôle créé",
        text: "Le rôle et ses permissions ont bien été enregistrés."
    },
    "admin.roles.create:edit": {
        title: "Rôle modifié",
        text: "Les modifications du rôle ont bien été enregistrées."
    },
    "admin.stuffs.create": {
        title: "Stuff ajouté",
        text: "Le stuff a bien été ajouté au catalogue."
    },
    "admin.stuffs.create:edit": {
        title: "Stuff modifié",
        text: "Les modifications du stuff ont bien été enregistrées."
    },
    "admin.utilisateurs.create": {
        title: "Utilisateur créé",
        text: "Le nouvel utilisateur a bien été ajouté."
    },
    "admin.utilisateurs.edit": {
        title: "Utilisateur modifié",
        text: "Les modifications de l'utilisateur ont bien été enregistrées."
    },
    "admin.validations.create": {
        title: "Déclaration ajoutée",
        text: "La déclaration joueur a bien été enregistrée."
    },
    "admin.validations.edit": {
        title: "Déclaration modifiée",
        text: "Les modifications de la déclaration ont bien été enregistrées."
    }
};

const adminDraftMessages = {
    "admin.annonces.create": "L'annonce a bien été enregistrée en brouillon.",
    "admin.annonces.edit": "Les modifications de l'annonce ont bien été enregistrées en brouillon.",
    "admin.galerie.create": "L'image a bien été enregistrée en brouillon.",
    "admin.galerie.edit": "Les modifications de l'image ont bien été enregistrées en brouillon.",
    "admin.guides.create": "Le guide a bien été enregistré en brouillon.",
    "admin.guides.create:edit": "Les modifications du guide ont bien été enregistrées en brouillon.",
    "admin.missions.create": "La mission a bien été enregistrée en brouillon.",
    "admin.missions.edit": "Les modifications de la mission ont bien été enregistrées en brouillon."
};

const adminModeLabels = {
    "admin.guides.create:edit": {
        title: "Modifier un guide | Les Zheros",
        heading: "Modifier un guide",
        breadcrumb: "Guides / Modifier"
    },
    "admin.roles.create:edit": {
        title: "Modifier un rôle | Les Zheros",
        heading: "Modifier un rôle",
        breadcrumb: "Rôles / Modifier"
    }
};

const adminSaveRedirects = {
    "admin.annonces.create": "/admin/annonces",
    "admin.annonces.edit": "/admin/annonces",
    "admin.galerie.create": "/admin/galerie",
    "admin.galerie.edit": "/admin/galerie",
    "admin.guides.create": "/admin/guides",
    "admin.missions.create": "/admin/missions",
    "admin.missions.edit": "/admin/missions",
    "admin.roles.create": "/admin/roles",
    "admin.stuffs.create": "/admin/stuffs",
    "admin.utilisateurs.create": "/admin/utilisateurs",
    "admin.utilisateurs.edit": "/admin/utilisateurs",
    "admin.validations.create": "/admin/validations",
    "admin.validations.edit": "/admin/validations"
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
    if (form.matches("[data-filter-form], [data-lottery-settings], [data-real-form], [data-bulk-form]")) {
        return;
    }

    form.addEventListener("submit", (event) => {
        event.preventDefault();
        syncAdminRichEditors(form);

        redirectAfterAdminSave(getAdminSaveMessage(event.submitter));
    });
});

const getFormSpoofedMethod = (form) => {
    const methodInput = form.querySelector('input[name="_method"]');

    return (methodInput?.value || form.method || "").toLowerCase();
};

const getDeleteConfirmMode = (form) => {
    const action = form.getAttribute("action") || "";

    if (form.dataset.confirmMode) {
        return form.dataset.confirmMode;
    }

    if (/\/corbeille\/?$/.test(action)) {
        return "empty";
    }

    if (action.includes("/corbeille/")) {
        return "force";
    }

    return "trash";
};

const getDeleteConfirmName = (form, submitter) => {
    const buttonText = submitter?.getAttribute("aria-label")
        || submitter?.getAttribute("title")
        || submitter?.textContent
        || "";

    return form.dataset.confirmName || buttonText.trim() || "cet élément";
};

const closeConfirmFormModal = () => {
    if (!confirmFormModal) {
        return;
    }

    confirmFormModal.hidden = true;
    confirmFormTarget = null;
};

const openConfirmFormModal = (form, submitter) => {
    if (!confirmFormModal) {
        return false;
    }

    const mode = getDeleteConfirmMode(form);
    const entity = getCurrentAdminEntity();
    const name = getDeleteConfirmName(form, submitter);
    confirmFormTarget = form;

    const title = form.dataset.confirmTitle || (
        mode === "empty"
            ? `Vider la corbeille des ${entity.plural} ?`
            : mode === "force"
                ? `Supprimer définitivement ${entity.article} ${entity.label} ?`
                : `Mettre ${entity.article} ${entity.label} à la corbeille ?`
    );
    const text = form.dataset.confirmText || (
        mode === "empty"
            ? `Cette action supprimera définitivement tous les éléments de la corbeille des ${entity.plural}.`
            : mode === "force"
                ? `Cette action supprimera définitivement « ${name} ».`
                : `Cette action déplacera « ${name} » dans la corbeille des ${entity.plural}.`
    );
    const submitLabel = form.dataset.confirmSubmit || (
        mode === "empty"
            ? "Vider la corbeille"
            : mode === "force" ? "Supprimer définitivement" : "Déplacer"
    );

    const icon = form.dataset.confirmIcon || "";
    const variant = form.dataset.confirmVariant || "";

    if (confirmFormIcon) {
        confirmFormIcon.classList.toggle("admin-modal__icon--restore", icon === "restore");
        confirmFormIcon.classList.toggle("admin-modal__icon--warning", variant === "warning" && icon !== "restore");
        confirmFormIcon.innerHTML = icon === "restore"
            ? '<i class="fa-solid fa-clock-rotate-left"></i>'
            : mode === "empty"
                ? '<i class="fa-solid fa-broom"></i>'
                : '<i class="fa-regular fa-trash-can"></i>';
    }

    if (confirmFormTitle) {
        confirmFormTitle.textContent = title;
    }

    if (confirmFormText) {
        confirmFormText.textContent = text;
    }

    if (confirmFormSubmit) {
        confirmFormSubmit.classList.toggle("admin-danger-button", variant !== "warning");
        confirmFormSubmit.classList.toggle("admin-warning-button", variant === "warning");
        confirmFormSubmit.textContent = submitLabel;
    }

    confirmFormModal.hidden = false;
    confirmFormSubmit?.focus();

    return true;
};

document.addEventListener("submit", (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    if (form.dataset.confirmedSubmit === "true" || (!form.hasAttribute("data-confirm-form") && getFormSpoofedMethod(form) !== "delete")) {
        return;
    }

    if (openConfirmFormModal(form, event.submitter)) {
        event.preventDefault();
    }
});

document.querySelectorAll("[data-confirm-form-cancel]").forEach((button) => {
    button.addEventListener("click", closeConfirmFormModal);
});

confirmFormSubmit?.addEventListener("click", () => {
    if (!confirmFormTarget) {
        return;
    }

    confirmFormTarget.dataset.confirmedSubmit = "true";

    if (typeof confirmFormTarget.requestSubmit === "function") {
        confirmFormTarget.requestSubmit();
        return;
    }

    confirmFormTarget.submit();
});

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && confirmFormModal && !confirmFormModal.hidden) {
        closeConfirmFormModal();
    }
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
    if (!getCurrentAdminFile().endsWith(".trash") || !confirmActionModal || document.querySelector('[data-confirm-action="empty"]')) {
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
    button.dataset.confirmUrl = window.location.pathname;
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

document.querySelectorAll("[data-server-search]").forEach((searchInput) => {
    let serverSearchTimeout = 0;

    searchInput.addEventListener("input", () => {
        window.clearTimeout(serverSearchTimeout);
        serverSearchTimeout = window.setTimeout(() => {
            searchInput.form?.requestSubmit();
        }, 350);
    });
});

document.querySelectorAll(".admin-search input").forEach((searchInput) => {
    if (
        searchInput.matches("[data-gallery-search], [data-server-search], [data-stuff-search], [data-validation-filter], [data-monster-search]")
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

document.querySelectorAll("[data-bulk-form]").forEach((form) => {
    const checkboxes = Array.from(document.querySelectorAll(`[form="${form.id}"][data-bulk-item]`));
    const masters = Array.from(document.querySelectorAll(`[data-bulk-check-all="${form.id}"]`));
    const count = form.querySelector("[data-bulk-count]");
    const submit = form.querySelector("[data-bulk-submit]");

    if (!checkboxes.length) {
        form.hidden = true;
        return;
    }

    const sync = () => {
        const selected = checkboxes.filter((checkbox) => checkbox.checked);
        form.hidden = selected.length < 2;

        if (count) {
            count.textContent = `${selected.length} sélectionné${selected.length > 1 ? "s" : ""}`;
        }

        if (submit) {
            submit.disabled = selected.length === 0;
        }

        masters.forEach((master) => {
            master.checked = selected.length > 0 && selected.length === checkboxes.length;
            master.indeterminate = selected.length > 0 && selected.length < checkboxes.length;
        });
    };

    masters.forEach((master) => {
        master.addEventListener("change", () => {
            checkboxes.forEach((checkbox) => {
                checkbox.checked = master.checked;
            });
            sync();
        });
    });

    checkboxes.forEach((checkbox) => checkbox.addEventListener("change", sync));

    form.addEventListener("submit", () => {
        form.querySelectorAll("[data-bulk-generated]").forEach((input) => input.remove());

        checkboxes
            .filter((checkbox) => checkbox.checked)
            .forEach((checkbox) => {
                const input = document.createElement("input");
                input.type = "hidden";
                input.name = checkbox.name;
                input.value = checkbox.value;
                input.dataset.bulkGenerated = "true";
                form.appendChild(input);
            });
    });

    sync();
});
