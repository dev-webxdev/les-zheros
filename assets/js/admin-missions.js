const missionForm = document.querySelector("[data-admin-mission-form]");
const deleteModal = document.querySelector("[data-delete-modal]");
const deleteMissionName = document.querySelector("[data-delete-mission-name]");
const deleteConfirm = document.querySelector("[data-delete-confirm]");
const modalIcon = document.querySelector("[data-modal-icon]");
const modalTitle = document.querySelector("[data-modal-title]");
const modalText = document.querySelector("[data-modal-text]");
const trashList = document.querySelector("[data-trash-list]");
const deletedMissionsKey = "les-zheros-deleted-missions";
let rowToDelete = null;
let missionIdToDelete = null;
let missionIdToRestore = null;
let shouldEmptyTrash = false;

const normalizeMissionId = (value) => value
    .toString()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-|-$/g, "");

const getDeletedMissions = () => {
    try {
        return JSON.parse(localStorage.getItem(deletedMissionsKey)) || [];
    } catch (error) {
        return [];
    }
};

const setDeletedMissions = (missions) => {
    localStorage.setItem(deletedMissionsKey, JSON.stringify(missions));
};

const getMissionFromRow = (row) => {
    const image = row?.querySelector(".admin-mission-thumb");
    const title = row?.querySelector("td:nth-child(2)")?.textContent?.trim() || "Mission";
    const type = row?.querySelector("td:nth-child(3)")?.textContent?.trim() || "";
    const rewards = row?.querySelector("td:nth-child(4)")?.innerHTML || "";

    return {
        id: normalizeMissionId(title),
        title,
        type,
        rewards,
        imageSrc: image?.getAttribute("src") || "",
        imageAlt: image?.getAttribute("alt") || title,
    };
};

const hideDeletedMissionRows = () => {
    const deletedIds = new Set(getDeletedMissions().map((mission) => mission.id));

    document.querySelectorAll(".admin-table tbody tr").forEach((row) => {
        const mission = getMissionFromRow(row);

        if (deletedIds.has(mission.id)) {
            row.remove();
        }
    });
};

const moveMissionToTrash = (row) => {
    const mission = getMissionFromRow(row);
    const deletedMissions = getDeletedMissions().filter((item) => item.id !== mission.id);

    deletedMissions.push(mission);
    setDeletedMissions(deletedMissions);
    row?.remove();
};

const closeDeleteModal = () => {
    if (!deleteModal) {
        return;
    }

    deleteModal.hidden = true;
    rowToDelete = null;
    missionIdToDelete = null;
    missionIdToRestore = null;
    shouldEmptyTrash = false;
};

const openAdminModal = ({ mode, missionTitle }) => {
    if (!deleteModal) {
        return;
    }

    if (deleteMissionName) {
        deleteMissionName.textContent = missionTitle || "cette mission";
    }

    if (modalIcon) {
        modalIcon.classList.toggle("admin-modal__icon--restore", mode === "restore");
        modalIcon.innerHTML = mode === "empty"
            ? '<i class="fa-solid fa-broom"></i>'
            : mode === "restore"
            ? '<i class="fa-solid fa-rotate-left"></i>'
            : '<i class="fa-regular fa-trash-can"></i>';
    }

    if (modalTitle) {
        modalTitle.textContent = mode === "empty"
            ? "Vider la corbeille des missions ?"
            : mode === "restore" ? "Restaurer la mission ?" : "Supprimer définitivement ?";
    }

    if (modalText) {
        modalText.innerHTML = mode === "empty"
            ? "Cette action supprimera définitivement toutes les missions de la corbeille."
            : mode === "restore"
            ? 'Cette action remettra <strong data-delete-mission-name></strong> dans la liste des missions.'
            : 'Cette action supprimera <strong data-delete-mission-name></strong> de la corbeille.';
        modalText.querySelector("[data-delete-mission-name]")?.replaceChildren(document.createTextNode(missionTitle || "cette mission"));
    }

    if (deleteConfirm) {
        deleteConfirm.textContent = mode === "empty"
            ? "Vider la corbeille"
            : mode === "restore" ? "Restaurer" : "Supprimer définitivement";
        deleteConfirm.classList.toggle("admin-danger-button", mode !== "restore");
        deleteConfirm.classList.toggle("admin-create-button", mode === "restore");
    }

    deleteModal.hidden = false;
    deleteConfirm?.focus();
};

if (deleteModal) {
    document.querySelectorAll(".admin-action-button--delete").forEach((button) => {
        button.addEventListener("click", () => {
            rowToDelete = button.closest("tr");
            const missionTitle = rowToDelete?.querySelector("td:nth-child(2)")?.textContent?.trim() || "cette mission";

            openAdminModal({ mode: "delete", missionTitle });
        });
    });
}

document.querySelectorAll("[data-delete-cancel]").forEach((button) => {
    button.addEventListener("click", closeDeleteModal);
});

deleteConfirm?.addEventListener("click", () => {
    if (shouldEmptyTrash && trashList) {
        setDeletedMissions([]);
        renderTrashList();
        window.openAdminAlert?.({
            title: "Corbeille vidée",
            text: "La corbeille des missions a bien été vidée."
        });
    } else if (missionIdToRestore && trashList) {
        setDeletedMissions(getDeletedMissions().filter((mission) => mission.id !== missionIdToRestore));
        renderTrashList();
        window.openAdminAlert?.({
            title: "Remis en ligne",
            text: "La mission a bien été restaurée dans la liste des missions."
        });
    } else if (missionIdToDelete && trashList) {
        setDeletedMissions(getDeletedMissions().filter((mission) => mission.id !== missionIdToDelete));
        renderTrashList();
        window.openAdminAlert?.({
            title: "Suppression définitive",
            text: "La mission a bien été supprimée définitivement."
        });
    } else if (rowToDelete) {
        moveMissionToTrash(rowToDelete);
        window.openAdminAlert?.({
            title: "Déplacé à la corbeille",
            text: "La mission a bien été déplacée dans la corbeille des missions."
        });
    }

    closeDeleteModal();
});

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && deleteModal && !deleteModal.hidden) {
        closeDeleteModal();
    }
});

const renderTrashList = () => {
    if (!trashList) {
        return;
    }

        const deletedMissions = getDeletedMissions();

        if (!deletedMissions.length) {
            trashList.innerHTML = `
                <tr>
                    <td class="admin-empty-state" colspan="5">
                        <strong>La corbeille est vide.</strong>
                        <span>Les missions supprimées apparaîtront ici pour pouvoir les restaurer.</span>
                    </td>
                </tr>
            `;
            return;
        }

        trashList.innerHTML = deletedMissions.map((mission) => `
            <tr>
                <td><img class="admin-mission-thumb" src="${mission.imageSrc}" alt="${mission.imageAlt}"></td>
                <td>${mission.title}</td>
                <td><span class="admin-tag">${mission.type}</span></td>
                <td>${mission.rewards}</td>
                <td>
                    <div class="admin-row-actions">
                        <button class="admin-action-button admin-action-button--restore" type="button" data-restore-mission="${mission.id}" data-restore-title="${mission.title}" aria-label="Restaurer ${mission.title}" title="Restaurer">
                            <i class="fa-solid fa-rotate-left"></i>
                        </button>
                        <button class="admin-action-button admin-action-button--delete" type="button" data-delete-forever="${mission.id}" data-delete-title="${mission.title}" aria-label="Supprimer définitivement ${mission.title}" title="Supprimer définitivement">
                            <i class="fa-regular fa-trash-can"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join("");
};

if (trashList) {
    const actions = document.querySelector(".admin-actions");

    if (actions && !document.querySelector("[data-empty-mission-trash]")) {
        const emptyTrashButton = document.createElement("button");
        emptyTrashButton.className = "admin-danger-button";
        emptyTrashButton.type = "button";
        emptyTrashButton.dataset.emptyMissionTrash = "";
        emptyTrashButton.innerHTML = '<i class="fa-regular fa-trash-can"></i><span>Vider la corbeille</span>';
        actions.appendChild(emptyTrashButton);

        emptyTrashButton.addEventListener("click", () => {
            shouldEmptyTrash = true;
            openAdminModal({ mode: "empty" });
        });
    }

    trashList.addEventListener("click", (event) => {
        const restoreButton = event.target.closest("[data-restore-mission]");
        const deleteForeverButton = event.target.closest("[data-delete-forever]");

        if (!restoreButton && !deleteForeverButton) {
            return;
        }

        if (restoreButton) {
            missionIdToRestore = restoreButton.dataset.restoreMission;
            openAdminModal({
                mode: "restore",
                missionTitle: restoreButton.dataset.restoreTitle || "cette mission",
            });
            return;
        }

        missionIdToDelete = deleteForeverButton.dataset.deleteForever;
        openAdminModal({
            mode: "delete",
            missionTitle: deleteForeverButton.dataset.deleteTitle || "cette mission",
        });
    });

    renderTrashList();
} else {
    hideDeletedMissionRows();
}

if (missionForm) {
    const imageModes = missionForm.querySelectorAll("[data-image-mode]");
    const imageSources = missionForm.querySelectorAll("[data-image-source]");
    const missionCategory = missionForm.querySelector("[data-mission-category]");
    const monsterSearch = missionForm.querySelector("[data-monster-search]");
    const monsterResults = missionForm.querySelector("[data-monster-results]");
    const imageFile = missionForm.querySelector("[data-image-file]");
    const imageUrl = missionForm.querySelector("[data-image-url]");
    const imagePreview = missionForm.querySelector("[data-image-preview]");
    const imagePreviewBox = imagePreview?.closest(".admin-image-preview");
    const imagePreviewLabel = missionForm.querySelector("[data-image-preview-label]");
    const uploadPreviewList = missionForm.querySelector("[data-upload-preview-list]");
    const removeMainUpload = missionForm.querySelector("[data-remove-main-upload]");
    const selectedImage = missionForm.querySelector("[data-selected-image]");
    const selectedMonsterId = missionForm.querySelector("[data-selected-monster-id]");
    const defaultPreview = imagePreview?.getAttribute("src") || "";
    let selectedUploadFiles = [];
    let uploadPreviewUrls = [];
    let monsterSearchTimeout = 0;

    const setPreview = (src, label = "Aperçu") => {
        if (imagePreview && src) {
            imagePreview.src = src;
        }

        if (imagePreviewLabel) {
            imagePreviewLabel.textContent = label;
        }

        if (selectedImage) {
            selectedImage.value = src || "";
        }
    };

    const setUploadPreviewState = (isActive) => {
        imagePreviewBox?.classList.toggle("has-upload-file", isActive);

        if (removeMainUpload) {
            removeMainUpload.hidden = !isActive;
        }
    };

    const setResultsMessage = (message) => {
        if (monsterResults) {
            monsterResults.innerHTML = `<p>${message}</p>`;
        }
    };

    const syncImageFileInput = () => {
        if (!imageFile || typeof DataTransfer === "undefined") {
            return;
        }

        const transfer = new DataTransfer();
        selectedUploadFiles.forEach((file) => transfer.items.add(file));
        imageFile.files = transfer.files;
    };

    const clearUploadPreviewUrls = () => {
        uploadPreviewUrls.forEach((url) => URL.revokeObjectURL(url));
        uploadPreviewUrls = [];
    };

    const renderUploadPreviews = () => {
        clearUploadPreviewUrls();

        if (uploadPreviewList) {
            uploadPreviewList.innerHTML = "";
        }

        if (!selectedUploadFiles.length) {
            setUploadPreviewState(false);
            return;
        }

        const firstFile = selectedUploadFiles[0];
        const previewUrl = URL.createObjectURL(firstFile);
        uploadPreviewUrls.push(previewUrl);
        setPreview(previewUrl, selectedUploadFiles.length > 1 ? `${selectedUploadFiles.length} images sélectionnées` : firstFile.name);
        setUploadPreviewState(true);
    };

    const setUploadFiles = (files) => {
        selectedUploadFiles = Array.from(files || []).filter((file) => file.type.startsWith("image/"));
        syncImageFileInput();
        renderUploadPreviews();

        if (!selectedUploadFiles.length) {
            setPreview(defaultPreview, "Image depuis ton PC");
            setUploadPreviewState(false);
        }
    };

    const normalizeSearch = (value) => value
        .toString()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLowerCase()
        .trim();

    const sortMonsterResults = (monsters, query) => {
        const search = normalizeSearch(query);

        return [...monsters].sort((monsterA, monsterB) => {
            const slugA = normalizeSearch(monsterA?.slug?.fr || monsterA?.name?.fr || "");
            const slugB = normalizeSearch(monsterB?.slug?.fr || monsterB?.name?.fr || "");
            const scoreA = slugA === search ? 0 : slugA.startsWith(search) ? 1 : slugA.includes(` ${search}`) ? 2 : 3;
            const scoreB = slugB === search ? 0 : slugB.startsWith(search) ? 1 : slugB.includes(` ${search}`) ? 2 : 3;

            return scoreA - scoreB || slugA.localeCompare(slugB);
        });
    };

    const setImageMode = (mode) => {
        imageSources.forEach((source) => {
            const isActive = source.dataset.imageSource === mode;
            source.hidden = !isActive;
            source.classList.toggle("is-active", isActive);
        });

        imagePreviewBox?.classList.toggle("is-upload-mode", mode === "upload");

        if (mode !== "api" && selectedMonsterId) {
            selectedMonsterId.value = "";
        }

        if (mode === "upload") {
            setPreview(defaultPreview, "Image depuis ton PC");
            setUploadPreviewState(Boolean(selectedUploadFiles.length));
        } else if (mode === "url") {
            setPreview(imageUrl?.value || defaultPreview, "Lien image");
            setUploadPreviewState(false);
        } else {
            setUploadPreviewState(false);
        }
    };

    const updateImageInputsState = () => {
        if (!missionCategory) {
            return;
        }

        const hasCategory = Boolean(missionCategory.value);

        imageModes.forEach((modeInput) => {
            modeInput.disabled = !hasCategory;
        });

        if (monsterSearch) {
            monsterSearch.disabled = !hasCategory;
            monsterSearch.placeholder = hasCategory ? "Tape au moins 2 lettres, puis clique un résultat" : "Choisis d'abord une catégorie";
        }

        if (imageFile) {
            imageFile.disabled = !hasCategory;
        }

        if (imageUrl) {
            imageUrl.disabled = !hasCategory;
        }

        if (!hasCategory) {
            imageModes.forEach((modeInput) => {
                modeInput.checked = modeInput.value === "api";
            });
            setImageMode("api");
            if (monsterSearch) {
                monsterSearch.value = "";
            }
            if (imageFile) {
                imageFile.value = "";
            }
            setUploadFiles([]);
            if (imageUrl) {
                imageUrl.value = "";
            }
            if (selectedMonsterId) {
                selectedMonsterId.value = "";
            }
            setPreview(defaultPreview, "Aperçu");
            setResultsMessage("Choisis une catégorie puis cherche un monstre.");
        } else {
            setResultsMessage("Tape le nom d'un monstre ou choisis une autre source d'image.");
        }
    };

    missionCategory?.addEventListener("change", updateImageInputsState);

    imageModes.forEach((modeInput) => {
        modeInput.addEventListener("change", () => {
            if (modeInput.checked) {
                setImageMode(modeInput.value);
            }
        });
    });

    const renderMonsterResults = (monsters) => {
        if (!monsterResults) {
            return;
        }

        if (!monsters.length) {
            setResultsMessage("Aucun monstre trouvé. Tu peux ajouter une image depuis ton PC ou via un lien.");
            return;
        }

        monsterResults.innerHTML = monsters.map((monster) => {
            const name = monster?.name?.fr || monster?.name?.en || `Monstre #${monster.id}`;
            const image = monster?.img || `https://api.dofusdb.fr/img/monsters/${monster.gfxId}.png`;
            const level = monster?.grades?.[0]?.level ? `Niv. ${monster.grades[0].level}` : "Monstre";

            return `
                <button class="admin-monster-result" type="button" data-monster-id="${monster.id}" data-monster-image="${image}" data-monster-name="${name}">
                    <img src="${image}" alt="">
                    <strong>${name}</strong>
                    <span>${level}</span>
                </button>
            `;
        }).join("");

        monsterResults.querySelectorAll("[data-monster-id]").forEach((button) => {
            button.addEventListener("click", () => {
                monsterResults.querySelectorAll(".is-selected").forEach((item) => item.classList.remove("is-selected"));
                button.classList.add("is-selected");

                if (selectedMonsterId) {
                    selectedMonsterId.value = button.dataset.monsterId || "";
                }

                setPreview(button.dataset.monsterImage || defaultPreview, button.dataset.monsterName || "Monstre sélectionné");
            });
        });
    };

    const searchMonsters = async (query) => {
        if (!query || query.length < 2) {
            setResultsMessage("Tape au moins 2 lettres, puis clique un résultat.");
            return;
        }

        setResultsMessage("Recherche en cours...");

        const url = new URL("https://api.dofusdb.fr/monsters");
        url.searchParams.set("slug.fr[$search]", query);
        url.searchParams.set("$limit", "30");

        try {
            const response = await fetch(url.toString());

            if (!response.ok) {
                throw new Error("Recherche impossible");
            }

            const payload = await response.json();
            const monsters = Array.isArray(payload?.data) ? payload.data : [];
            renderMonsterResults(sortMonsterResults(monsters, query).slice(0, 6));
        } catch (error) {
            setResultsMessage("Impossible de joindre DofusDB. Utilise une image depuis ton PC ou un lien.");
        }
    };

    monsterSearch?.addEventListener("input", () => {
        window.clearTimeout(monsterSearchTimeout);
        monsterSearchTimeout = window.setTimeout(() => {
            searchMonsters(monsterSearch.value.trim());
        }, 280);
    });

    imageFile?.addEventListener("change", () => {
        setUploadFiles(imageFile.files);
    });

    imagePreviewBox?.addEventListener("click", () => {
        const activeMode = missionForm.querySelector("[data-image-mode]:checked")?.value;

        if (activeMode === "upload" && imageFile && !imageFile.disabled) {
            imageFile.click();
        }
    });

    removeMainUpload?.addEventListener("click", (event) => {
        event.stopPropagation();
        selectedUploadFiles = [];
        syncImageFileInput();
        if (imageFile) {
            imageFile.value = "";
        }
        renderUploadPreviews();
        setPreview(defaultPreview, "Image depuis ton PC");
        setUploadPreviewState(false);
    });

    imageUrl?.addEventListener("input", () => {
        const value = imageUrl.value.trim();
        setPreview(value || defaultPreview, value ? "Lien image" : "Lien image");
    });

    missionForm.addEventListener("reset", () => {
        window.setTimeout(() => {
            setImageMode("api");
            setPreview(defaultPreview, "Aperçu");
            setResultsMessage("Choisis une catégorie puis cherche un monstre.");
            updateImageInputsState();
            if (selectedMonsterId) {
                selectedMonsterId.value = "";
            }
            if (imageFile) {
                imageFile.value = "";
            }
            setUploadFiles([]);
        }, 0);
    });

    updateImageInputsState();
}
