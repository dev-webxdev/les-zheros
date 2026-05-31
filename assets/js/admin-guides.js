const guideCoverInput = document.querySelector("[data-guide-cover-input]");
const guideCoverPreview = document.querySelector("[data-guide-cover-preview]");
const guideMapInput = document.querySelector("[data-guide-map-input]");
const guideMapPreview = document.querySelector("[data-guide-map-preview]");
const guideMapEmpty = document.querySelector("[data-guide-map-empty]");
const checklistList = document.querySelector("[data-guide-checklist-list]");
const emptyChecklist = document.querySelector("[data-guide-empty-checklist]");
const addChecklistButton = document.querySelector("[data-guide-add-check]");
const guideMissionSelect = document.querySelector("#guide-mission");
const guideTitleInput = document.querySelector("[data-guide-title-input]");
const guideCategorySelect = document.querySelector("[data-guide-category-select]");
const guideChipsInput = document.querySelector("[data-guide-chips-input]");
const guideCoverPathInput = document.querySelector("[data-guide-cover-path-input]");
const guideEditorTabs = Array.from(document.querySelectorAll("[data-guide-editor-tab]"));
const guideEditorPanels = Array.from(document.querySelectorAll("[data-guide-editor-panel]"));
const guideForm = document.querySelector("#guide-form");
const guideAutosaveUrl = guideForm?.dataset.guideAutosave || "";
const guideAutoDraftInput = document.querySelector("[data-guide-auto-draft-id]");
const guideAutosaveStatus = document.querySelector("[data-guide-autosave-status]");

let checklistIndex = 0;
let sectionIndex = 0;
let guideAutosaveTimeout = 0;
let guideAutosaveMaxTimeout = 0;
let guideAutosaveInFlight = false;
let guideAutosaveQueued = false;
let lastGuideAutosaveSignature = "";

const setActiveGuideEditorTab = (tabName) => {
    if (!tabName || guideEditorTabs.length === 0 || guideEditorPanels.length === 0) {
        return;
    }

    guideEditorTabs.forEach((tab) => {
        const isActive = tab.dataset.guideEditorTab === tabName;
        tab.classList.toggle("is-active", isActive);
        tab.setAttribute("aria-pressed", isActive ? "true" : "false");
    });

    guideEditorPanels.forEach((panel) => {
        const isActive = panel.dataset.guideEditorPanel === tabName;
        panel.hidden = !isActive;
        panel.classList.toggle("is-active", isActive);
    });
};

guideEditorTabs.forEach((tab) => {
    tab.addEventListener("click", () => setActiveGuideEditorTab(tab.dataset.guideEditorTab));
});

document.addEventListener("invalid", (event) => {
    const panel = event.target.closest?.("[data-guide-editor-panel]");

    if (panel?.dataset.guideEditorPanel) {
        setActiveGuideEditorTab(panel.dataset.guideEditorPanel);
    }
}, true);

const updateGuidePreview = (input, preview, empty = null) => {
    const file = input?.files?.[0];

    if (!file || !preview) {
        return;
    }

    preview.src = URL.createObjectURL(file);
    preview.hidden = false;

    if (empty) {
        empty.hidden = true;
    }
};

const autogrowTextarea = (textarea) => {
    if (!(textarea instanceof HTMLTextAreaElement)) {
        return;
    }

    textarea.style.height = "auto";
    textarea.style.height = `${textarea.scrollHeight}px`;
};

const initAutogrow = (root = document) => {
    root.querySelectorAll("textarea[data-autogrow], .admin-guide-summary textarea").forEach((textarea) => {
        autogrowTextarea(textarea);
        textarea.addEventListener("input", () => autogrowTextarea(textarea));
    });
};

const setGuideAutosaveStatus = (message, state = "") => {
    if (!guideAutosaveStatus) {
        return;
    }

    guideAutosaveStatus.textContent = message;
    guideAutosaveStatus.classList.toggle("is-saved", state === "saved");
    guideAutosaveStatus.classList.toggle("is-error", state === "error");
};

const syncGuideRichEditors = () => {
    guideForm?.querySelectorAll("[data-rich-editor]").forEach((editor) => {
        const surface = editor.querySelector("[data-editor-surface]");
        const input = editor.querySelector("[data-editor-input]");

        if (surface && input) {
            input.value = surface.innerHTML.trim();
        }
    });
};

const guideFormHasDraftContent = () => {
    if (!guideForm) {
        return false;
    }

    syncGuideRichEditors();

    return Array.from(guideForm.elements).some((field) => {
        if (!field.name || field.type === "hidden" || field.type === "file" || field.name === "_token") {
            return false;
        }

        if ((field.type === "checkbox" || field.type === "radio") && !field.checked) {
            return false;
        }

        return String(field.value || "").trim() !== "";
    });
};

const buildGuideAutosavePayload = () => {
    const payload = new FormData();

    if (!guideForm) {
        return payload;
    }

    syncGuideRichEditors();
    updateSectionState();

    Array.from(guideForm.elements).forEach((field) => {
        if (!field.name || field.disabled || field.type === "file" || field.name === "_method" || field.name === "published") {
            return;
        }

        if ((field.type === "checkbox" || field.type === "radio") && !field.checked) {
            return;
        }

        payload.append(field.name, field.value);
    });

    return payload;
};

const saveGuideDraftNow = async () => {
    if (!guideAutosaveUrl || !guideForm || !guideFormHasDraftContent()) {
        return;
    }

    if (guideAutosaveInFlight) {
        guideAutosaveQueued = true;
        return;
    }

    const payload = buildGuideAutosavePayload();
    const signature = JSON.stringify(Array.from(payload.entries()));

    if (signature === lastGuideAutosaveSignature) {
        return;
    }

    guideAutosaveInFlight = true;
    setGuideAutosaveStatus("Sauvegarde du brouillon...");

    try {
        const response = await fetch(guideAutosaveUrl, {
            method: "POST",
            body: payload,
            headers: {
                "Accept": "application/json",
                "X-Requested-With": "XMLHttpRequest"
            }
        });

        if (!response.ok) {
            throw new Error("autosave failed");
        }

        const data = await response.json();

        if (data.id && guideAutoDraftInput) {
            guideAutoDraftInput.value = data.id;
        }

        if (data.edit_url && window.location.pathname.includes("/admin/guides/creer")) {
            window.history.replaceState({}, "", data.edit_url);
        }

        lastGuideAutosaveSignature = signature;
        setGuideAutosaveStatus(`Brouillon sauvegardé à ${data.saved_at || ""}`.trim(), "saved");
    } catch (error) {
        setGuideAutosaveStatus("Sauvegarde auto impossible", "error");
    } finally {
        guideAutosaveInFlight = false;

        if (guideAutosaveQueued) {
            guideAutosaveQueued = false;
            saveGuideDraftNow();
        }
    }
};

const queueGuideDraftSave = (delay = 700) => {
    if (!guideAutosaveUrl) {
        return;
    }

    window.clearTimeout(guideAutosaveTimeout);
    guideAutosaveTimeout = window.setTimeout(() => {
        window.clearTimeout(guideAutosaveMaxTimeout);
        guideAutosaveMaxTimeout = 0;
        saveGuideDraftNow();
    }, delay);
};

const scheduleGuideAutosave = () => {
    if (!guideAutosaveUrl) {
        return;
    }

    queueGuideDraftSave();

    if (!guideAutosaveMaxTimeout) {
        guideAutosaveMaxTimeout = window.setTimeout(() => {
            window.clearTimeout(guideAutosaveTimeout);
            guideAutosaveMaxTimeout = 0;
            saveGuideDraftNow();
        }, 3000);
    }
};

const sendGuideDraftBeforeUnload = () => {
    if (!guideAutosaveUrl || !guideForm || !guideFormHasDraftContent()) {
        return;
    }

    const payload = buildGuideAutosavePayload();
    const signature = JSON.stringify(Array.from(payload.entries()));

    if (signature === lastGuideAutosaveSignature) {
        return;
    }

    if (navigator.sendBeacon) {
        navigator.sendBeacon(guideAutosaveUrl, payload);
        lastGuideAutosaveSignature = signature;
        return;
    }

    fetch(guideAutosaveUrl, {
        method: "POST",
        body: payload,
        keepalive: true,
        headers: {
            "Accept": "application/json",
            "X-Requested-With": "XMLHttpRequest"
        }
    });

    lastGuideAutosaveSignature = signature;
};

guideCoverInput?.addEventListener("change", () => updateGuidePreview(guideCoverInput, guideCoverPreview));
guideMapInput?.addEventListener("change", () => updateGuidePreview(guideMapInput, guideMapPreview, guideMapEmpty));

guideMissionSelect?.addEventListener("change", () => {
    const selectedMission = guideMissionSelect.selectedOptions[0];
    const missionCategory = selectedMission?.dataset.missionCategory;
    const missionTitle = selectedMission?.dataset.missionTitle || selectedMission?.textContent.trim();

    if (!selectedMission || !missionCategory) {
        return;
    }

    if (guideTitleInput && missionTitle) {
        guideTitleInput.value = missionTitle;
    }

    if (guideCategorySelect) {
        guideCategorySelect.value = missionCategory;
    }

    if (guideChipsInput && missionTitle) {
        guideChipsInput.value = missionTitle;
    }

    if (selectedMission.dataset.missionImage) {
        if (guideCoverPreview) {
            guideCoverPreview.src = selectedMission.dataset.missionImage;
            guideCoverPreview.hidden = false;
        }

        if (guideCoverPathInput) {
            guideCoverPathInput.value = selectedMission.dataset.missionImage;
        }
    }

    scheduleGuideAutosave();
});

const updateChecklistState = () => {
    const rows = Array.from(checklistList?.querySelectorAll("[data-guide-check-row]") || []);

    rows.forEach((row, index) => {
        const label = row.querySelector("[data-guide-check-label]");

        if (label) {
            label.textContent = `Point ${index + 1}`;
        }
    });

    if (emptyChecklist) {
        emptyChecklist.hidden = rows.length > 0;
    }
};

const addChecklistPoint = () => {
    if (!checklistList) {
        return;
    }

    checklistIndex += 1;

    checklistList.insertAdjacentHTML("beforeend", `
        <div class="admin-guide-check-row" data-guide-check-row>
            <span class="admin-guide-drag-handle" data-guide-drag-handle aria-label="Déplacer ce point" title="Déplacer">
                <i class="fa-solid fa-grip-vertical"></i>
            </span>
            <label class="admin-field" for="guide-check-${checklistIndex}">
                <span data-guide-check-label>Point</span>
                <input id="guide-check-${checklistIndex}" name="checklist[]" type="text" placeholder="Ex: Garder les personnages fragiles hors des lignes dangereuses.">
            </label>
            <button class="admin-guide-remove-button" type="button" data-guide-remove-check aria-label="Supprimer ce point" title="Supprimer">
                <i class="fa-regular fa-trash-can"></i>
            </button>
        </div>
    `);

    updateChecklistState();
    scheduleGuideAutosave();
};

const sectionCards = () => Array.from(document.querySelectorAll("[data-guide-section-card]"));
const sectionLists = () => Array.from(document.querySelectorAll("[data-guide-section-list]"));

const updateSectionState = () => {
    sectionCards().forEach((card, index) => {
        card.querySelectorAll("[name]").forEach((field) => {
            field.name = field.name.replace(/sections\[\d+\]/, `sections[${index}]`);
        });

        const imageItems = Array.from(card.querySelectorAll("[data-guide-section-media-item]"));
        imageItems.forEach((item, imageIndex) => {
            item.querySelectorAll("[name]").forEach((field) => {
                field.name = field.name.replace(/\[images]\[\d+\]/, `[images][${imageIndex}]`);
            });
        });
    });

    sectionLists().forEach((list) => {
        const cards = Array.from(list.querySelectorAll("[data-guide-section-card]"));
        const empty = list.querySelector("[data-guide-empty-sections]");

        if (empty) {
            empty.hidden = cards.length > 0;
        }
    });
};

const richEditorHtml = (name, id, placeholder) => `
    <div class="admin-rich-editor admin-rich-editor--compact" data-rich-editor>
        <div class="admin-rich-editor__toolbar" aria-label="Outils de mise en forme">
            <button type="button" data-editor-command="bold" title="Gras"><i class="fa-solid fa-bold"></i></button>
            <button type="button" data-editor-command="italic" title="Italique"><i class="fa-solid fa-italic"></i></button>
            <button type="button" data-editor-command="underline" title="Souligné"><i class="fa-solid fa-underline"></i></button>
            <button type="button" data-editor-command="insertUnorderedList" title="Liste à puces"><i class="fa-solid fa-list-ul"></i></button>
            <button type="button" data-editor-command="insertOrderedList" title="Liste numérotée"><i class="fa-solid fa-list-ol"></i></button>
            <button type="button" data-editor-command="formatBlock" data-editor-value="blockquote" title="Citation"><i class="fa-solid fa-quote-right"></i></button>
            <button type="button" data-editor-link title="Lien"><i class="fa-solid fa-link"></i></button>
        </div>
        <div class="admin-rich-editor__surface admin-rich-editor__surface--guide" contenteditable="true" data-editor-surface data-placeholder="${placeholder}"></div>
        <textarea id="${id}" name="${name}" data-editor-input hidden></textarea>
    </div>
`;

const mediaItemHtml = (sectionIndexValue) => {
    const token = `${Date.now()}-${Math.floor(Math.random() * 1000)}`;

    return `
        <div class="admin-guide-section-media-item" data-guide-section-media-item>
            <label class="admin-guide-section-image" for="guide-section-image-${token}">
                <span class="admin-guide-upload-placeholder" data-guide-section-image-empty><i class="fa-regular fa-image"></i> Choisir une image</span>
                <img class="admin-cover-preview admin-cover-preview--strategy" alt="" data-guide-section-image-preview hidden>
                <input id="guide-section-image-${token}" class="admin-cover-input" name="sections[${sectionIndexValue}][images][0][image]" type="file" accept="image/*" data-guide-section-image-input>
            </label>
            <label class="admin-field" for="guide-section-caption-${token}">
                <span>Texte sous l'image</span>
                <textarea id="guide-section-caption-${token}" name="sections[${sectionIndexValue}][images][0][caption]" rows="2" data-autogrow placeholder="Ajoute une explication ou une précision juste sous l'image."></textarea>
            </label>
            <button class="admin-guide-remove-button" type="button" data-guide-remove-section-image aria-label="Supprimer cette image" title="Supprimer">
                <i class="fa-regular fa-trash-can"></i>
            </button>
        </div>
    `;
};

const addGuideSection = (kind = "strategy") => {
    const list = document.querySelector(`[data-guide-section-list="${kind}"]`);

    if (!list) {
        return;
    }

    sectionIndex += 1;
    const index = sectionCards().length;
    const isSpell = kind === "spells";
    const titleLabel = isSpell ? "Nom du sort" : "Titre de section";
    const bodyLabel = isSpell ? "Effet du sort" : "Contenu";
    const titlePlaceholder = isSpell ? "Ex: Attirance explosive" : "Ex: Lecture de la map";
    const bodyPlaceholder = isSpell
        ? "Décris la portée, la zone, le danger et comment l’éviter."
        : "Explique le point important, le placement ou la mécanique.";

    list.insertAdjacentHTML("beforeend", `
        <article class="admin-guide-section-card" data-guide-section-card>
            <input type="hidden" name="sections[${index}][kind]" value="${kind}">
            <div class="admin-guide-section-card__top">
                <span class="admin-guide-drag-handle" data-guide-drag-handle aria-label="Déplacer" title="Déplacer">
                    <i class="fa-solid fa-grip-vertical"></i>
                </span>
                <label class="admin-field" for="guide-section-title-${sectionIndex}">
                    <span>${titleLabel}</span>
                    <input id="guide-section-title-${sectionIndex}" name="sections[${index}][title]" type="text" placeholder="${titlePlaceholder}">
                </label>
                <button class="admin-guide-remove-button" type="button" data-guide-remove-section aria-label="Supprimer" title="Supprimer">
                    <i class="fa-regular fa-trash-can"></i>
                </button>
            </div>
            <label class="admin-field" for="guide-section-body-${sectionIndex}">
                <span>${bodyLabel}</span>
                ${richEditorHtml(`sections[${index}][body]`, `guide-section-body-${sectionIndex}`, bodyPlaceholder)}
            </label>
            <div class="admin-guide-section-media-slot" data-guide-section-media-slot>
                <button class="admin-secondary-button admin-guide-builder-add" type="button" data-guide-add-section-image>
                    <i class="fa-regular fa-image"></i>
                    <span>Ajouter une image</span>
                </button>
            </div>
        </article>
    `);

    const card = list.lastElementChild;
    window.initAdminRichEditors?.(card);
    updateSectionState();
};

addChecklistButton?.addEventListener("click", addChecklistPoint);

document.addEventListener("click", (event) => {
    const addSectionButton = event.target.closest("[data-guide-add-section]");

    if (addSectionButton) {
        addGuideSection(addSectionButton.dataset.guideAddSection || "strategy");
        return;
    }

    const removeCheckButton = event.target.closest("[data-guide-remove-check]");

    if (removeCheckButton) {
        removeCheckButton.closest("[data-guide-check-row]")?.remove();
        updateChecklistState();
        scheduleGuideAutosave();
        return;
    }

    const removeSectionButton = event.target.closest("[data-guide-remove-section]");

    if (removeSectionButton) {
        removeSectionButton.closest("[data-guide-section-card]")?.remove();
        updateSectionState();
        scheduleGuideAutosave();
        return;
    }

    const removeImageButton = event.target.closest("[data-guide-remove-section-image]");

    if (removeImageButton) {
        removeImageButton.closest("[data-guide-section-media-item]")?.remove();
        updateSectionState();
        scheduleGuideAutosave();
        return;
    }

    const addImageButton = event.target.closest("[data-guide-add-section-image]");

    if (!addImageButton) {
        return;
    }

    const card = addImageButton.closest("[data-guide-section-card]");
    const slot = addImageButton.closest("[data-guide-section-media-slot]");
    const cardIndex = sectionCards().indexOf(card);
    addImageButton.insertAdjacentHTML("beforebegin", mediaItemHtml(cardIndex));
    initAutogrow(slot);
    updateSectionState();
    scheduleGuideAutosave();
});

document.addEventListener("change", (event) => {
    const input = event.target.closest("[data-guide-section-image-input]");

    if (!input) {
        return;
    }

    const imageField = input.closest(".admin-guide-section-image");
    const preview = imageField?.querySelector("[data-guide-section-image-preview]");
    const empty = imageField?.querySelector("[data-guide-section-image-empty]");

    updateGuidePreview(input, preview, empty);
});

const getDragAfterElement = (container, y, itemSelector) => {
    const draggableElements = Array.from(container.querySelectorAll(`${itemSelector}:not(.is-dragging)`));

    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;

        if (offset < 0 && offset > closest.offset) {
            return { offset, element: child };
        }

        return closest;
    }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
};

const enableGuideDragSort = (container, itemSelector, afterSort) => {
    if (!container) {
        return;
    }

    container.addEventListener("pointerdown", (event) => {
        const item = event.target.closest(itemSelector);
        const handle = event.target.closest("[data-guide-drag-handle]");

        if (!item || !handle) {
            return;
        }

        event.preventDefault();
        item.classList.add("is-dragging");

        const moveItem = (moveEvent) => {
            moveEvent.preventDefault();
            const afterElement = getDragAfterElement(container, moveEvent.clientY, itemSelector);

            if (afterElement) {
                container.insertBefore(item, afterElement);
            } else {
                container.appendChild(item);
            }
        };

        const stopMove = () => {
            item.classList.remove("is-dragging");
            document.removeEventListener("pointermove", moveItem);
            document.removeEventListener("pointerup", stopMove);
            afterSort();
            scheduleGuideAutosave();
        };

        document.addEventListener("pointermove", moveItem);
        document.addEventListener("pointerup", stopMove, { once: true });
    });
};

enableGuideDragSort(checklistList, "[data-guide-check-row]", updateChecklistState);
sectionLists().forEach((list) => enableGuideDragSort(list, "[data-guide-section-card]", updateSectionState));

initAutogrow();
updateChecklistState();
updateSectionState();

if (guideAutosaveUrl && guideForm) {
    guideForm.addEventListener("input", scheduleGuideAutosave);
    guideForm.addEventListener("change", scheduleGuideAutosave);
    guideForm.addEventListener("submit", () => {
        window.clearTimeout(guideAutosaveTimeout);
        window.clearTimeout(guideAutosaveMaxTimeout);
        syncGuideRichEditors();
    });

    window.addEventListener("pagehide", sendGuideDraftBeforeUnload);
    window.addEventListener("beforeunload", sendGuideDraftBeforeUnload);
}
