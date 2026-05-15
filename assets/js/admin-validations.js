const proofViewer = document.querySelector("[data-proof-viewer]");
const validationTeammateToggle = document.querySelector("[data-validation-teammate-toggle]");
const validationTeammateFields = document.querySelector("[data-validation-teammate-fields]");
const validationRows = document.querySelectorAll("[data-validation-row]");
const validationFilters = document.querySelectorAll("[data-validation-filter]");
const validationSelectAll = document.querySelector("[data-validation-select-all]");
const validationBulk = document.querySelector("[data-validation-bulk]");
const validationSelectedCount = document.querySelector("[data-validation-selected-count]");
const validationBulkAction = document.querySelector("[data-validation-bulk-action]");
const validationBulkApply = document.querySelector("[data-validation-bulk-apply]");
const validationStatusModal = document.querySelector("[data-validation-status-modal]");
const validationStatusTitle = document.querySelector("[data-validation-status-title]");
const validationStatusName = document.querySelector("[data-validation-status-name]");
const validationStatusLabel = document.querySelector("[data-validation-status-label]");
const validationStatusIcon = document.querySelector("[data-validation-status-icon]");
const validationStatusSubmit = document.querySelector("[data-validation-status-submit]");
let pendingValidationStatusRows = [];
let pendingValidationStatus = "";

if (proofViewer) {
    const proofImage = proofViewer.querySelector("[data-proof-image]");
    const proofTitle = proofViewer.querySelector("[data-proof-title]");
    const proofPlayer = proofViewer.querySelector("[data-proof-player]");
    const proofMission = proofViewer.querySelector("[data-proof-mission]");
    const proofModal = document.querySelector("[data-proof-modal]");
    const proofModalImage = document.querySelector("[data-proof-modal-image]");

    document.querySelectorAll("[data-proof-src]").forEach((button) => {
        button.addEventListener("click", () => {
            document.querySelectorAll(".admin-table--validations tr.is-selected").forEach((row) => row.classList.remove("is-selected"));
            button.closest("tr")?.classList.add("is-selected");

            const player = button.dataset.proofPlayer || "Joueur";
            const mission = button.dataset.proofMission || "Mission";

            if (proofImage) {
                proofImage.src = button.dataset.proofSrc || "";
            }

            if (proofModalImage) {
                proofModalImage.src = button.dataset.proofSrc || "";
            }

            if (proofTitle) {
                proofTitle.textContent = `${player} - ${mission}`;
            }

            if (proofPlayer) {
                proofPlayer.textContent = player;
            }

            if (proofMission) {
                proofMission.textContent = mission;
            }
        });
    });

    proofViewer.querySelector("[data-proof-open]")?.addEventListener("click", () => {
        if (!proofModal || !proofImage || !proofModalImage) {
            return;
        }

        proofModalImage.src = proofImage.src;
        proofModal.hidden = false;
    });

    document.querySelectorAll("[data-proof-close]").forEach((button) => {
        button.addEventListener("click", () => {
            if (proofModal) {
                proofModal.hidden = true;
            }
        });
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && proofModal && !proofModal.hidden) {
            proofModal.hidden = true;
        }
    });
}

if (validationTeammateToggle && validationTeammateFields) {
    const teammateInputs = validationTeammateFields.querySelectorAll("select, input");

    const updateValidationTeammateFields = () => {
        const enabled = validationTeammateToggle.checked;

        validationTeammateFields.hidden = !enabled;
        teammateInputs.forEach((input) => {
            input.required = enabled;
            input.disabled = !enabled;
        });
    };

    validationTeammateToggle.addEventListener("change", updateValidationTeammateFields);
    updateValidationTeammateFields();
}

if (validationRows.length) {
    const validationStatusMap = {
        validated: {
            label: "Validé",
            className: "admin-tag admin-tag--success",
            confirmTitle: "Valider cette validation ?",
        },
        pending: {
            label: "En attente",
            className: "admin-tag admin-tag--primary",
            confirmTitle: "Mettre en attente ?",
        },
        refused: {
            label: "Refusé",
            className: "admin-tag admin-tag--danger",
            confirmTitle: "Refuser cette validation ?",
        },
    };
    const getVisibleValidationRows = () => Array.from(validationRows).filter((row) => !row.hidden);
    const getSelectedValidationRows = () => Array.from(validationRows).filter((row) => row.querySelector("[data-validation-select]")?.checked);
    const updateValidationRowStatus = (row, status) => {
        const statusConfig = validationStatusMap[status];
        const statusTag = row.querySelector("td:nth-child(8) .admin-tag");

        if (!statusConfig || !statusTag) {
            return;
        }

        row.dataset.status = status;
        statusTag.textContent = statusConfig.label;
        statusTag.className = statusConfig.className;

        if (row.classList.contains("is-selected")) {
            const proofStatusTag = document.querySelector("[data-proof-viewer] dd .admin-tag");

            if (proofStatusTag) {
                proofStatusTag.textContent = statusConfig.label;
                proofStatusTag.className = statusConfig.className;
            }
        }
    };
    const closeValidationStatusModal = () => {
        if (!validationStatusModal) {
            return;
        }

        validationStatusModal.hidden = true;
        pendingValidationStatusRows = [];
        pendingValidationStatus = "";
    };

    const openValidationStatusModal = (rows, status) => {
        const statusConfig = validationStatusMap[status];
        const targetRows = Array.isArray(rows) ? rows : [rows];

        if (!validationStatusModal || !statusConfig || targetRows.length === 0) {
            return;
        }

        pendingValidationStatusRows = targetRows;
        pendingValidationStatus = status;

        if (validationStatusTitle) {
            validationStatusTitle.textContent = statusConfig.confirmTitle;
        }

        if (validationStatusName) {
            if (targetRows.length > 1) {
                validationStatusName.textContent = `${targetRows.length} validations sélectionnées`;
            } else {
                const row = targetRows[0];
                const player = row.dataset.player || "cette validation";
                const mission = row.querySelector("td:nth-child(3) strong")?.textContent?.trim();
                validationStatusName.textContent = mission ? `${player} - ${mission}` : player;
            }
        }

        if (validationStatusLabel) {
            validationStatusLabel.textContent = statusConfig.label.toLowerCase();
        }

        const isPendingStatus = status === "pending";

        validationStatusIcon?.classList.toggle("admin-modal__icon--warning", isPendingStatus);
        validationStatusSubmit?.classList.toggle("admin-warning-button", isPendingStatus);
        validationStatusSubmit?.classList.toggle("admin-danger-button", !isPendingStatus);

        validationStatusModal.hidden = false;
        validationStatusSubmit?.focus();
    };

    const updateValidationBulkState = () => {
        const selectedRows = getSelectedValidationRows();

        if (validationBulk) {
            validationBulk.hidden = selectedRows.length === 0;
        }

        if (validationSelectedCount) {
            validationSelectedCount.textContent = String(selectedRows.length);
        }

        if (validationSelectAll) {
            const visibleRows = getVisibleValidationRows();
            const selectedVisibleRows = visibleRows.filter((row) => row.querySelector("[data-validation-select]")?.checked);

            validationSelectAll.checked = visibleRows.length > 0 && selectedVisibleRows.length === visibleRows.length;
            validationSelectAll.indeterminate = selectedVisibleRows.length > 0 && selectedVisibleRows.length < visibleRows.length;
        }
    };

    const applyValidationFilters = () => {
        const activeFilters = {};

        validationFilters.forEach((filter) => {
            activeFilters[filter.dataset.validationFilter] = filter.value;
        });

        const searchQuery = String(activeFilters.search || "")
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .toLowerCase()
            .trim();

        validationRows.forEach((row) => {
            const matchesPlayer = activeFilters.player === "all" || row.dataset.player === activeFilters.player;
            const matchesStatus = activeFilters.status === "all" || row.dataset.status === activeFilters.status;
            const matchesWeek = !activeFilters.week || row.dataset.week === activeFilters.week;
            const rowText = `${row.dataset.search || ""} ${row.textContent}`
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .toLowerCase();
            const matchesSearch = !searchQuery || rowText.includes(searchQuery);

            row.hidden = !(matchesPlayer && matchesStatus && matchesWeek && matchesSearch);

            if (row.hidden) {
                row.querySelector("[data-validation-select]").checked = false;
            }
        });

        updateValidationBulkState();
    };

    validationFilters.forEach((filter) => {
        filter.addEventListener(filter.type === "search" ? "input" : "change", applyValidationFilters);
    });

    validationRows.forEach((row) => {
        row.querySelector("[data-validation-select]")?.addEventListener("change", updateValidationBulkState);
        row.querySelectorAll("[data-validation-status-action]").forEach((button) => {
            button.addEventListener("click", () => {
                updateValidationRowStatus(row, button.dataset.validationStatusAction);
                button.closest(".admin-action-menu")?.removeAttribute("open");
                applyValidationFilters();
            });
        });
        row.querySelectorAll("[data-validation-status-confirm]").forEach((button) => {
            button.addEventListener("click", () => {
                openValidationStatusModal(row, button.dataset.validationStatusConfirm);
                button.closest(".admin-action-menu")?.removeAttribute("open");
            });
        });
    });

    document.querySelectorAll("[data-validation-status-cancel]").forEach((button) => {
        button.addEventListener("click", closeValidationStatusModal);
    });

    validationStatusSubmit?.addEventListener("click", () => {
        if (pendingValidationStatusRows.length === 0 || !pendingValidationStatus) {
            return;
        }

        pendingValidationStatusRows.forEach((row) => {
            updateValidationRowStatus(row, pendingValidationStatus);
            row.querySelector("[data-validation-select]").checked = false;
        });

        if (validationBulkAction) {
            validationBulkAction.value = "";
        }

        closeValidationStatusModal();
        applyValidationFilters();
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && validationStatusModal && !validationStatusModal.hidden) {
            closeValidationStatusModal();
        }
    });

    validationSelectAll?.addEventListener("change", () => {
        getVisibleValidationRows().forEach((row) => {
            row.querySelector("[data-validation-select]").checked = validationSelectAll.checked;
        });

        updateValidationBulkState();
    });

    validationBulkApply?.addEventListener("click", () => {
        const selectedRows = getSelectedValidationRows();
        const selectedAction = validationBulkAction?.value || "";

        if (!selectedAction || selectedRows.length === 0) {
            return;
        }

        if (selectedAction === "trash") {
            openConfirmTrashModal(`${selectedRows.length} validations sélectionnées`, "admin-validations-trash.html");
            return;
        }

        if (selectedAction === "pending" || selectedAction === "refused") {
            openValidationStatusModal(selectedRows, selectedAction);
            return;
        }

        selectedRows.forEach((row) => {
            updateValidationRowStatus(row, selectedAction);
            row.querySelector("[data-validation-select]").checked = false;
        });

        validationBulkAction.value = "";
        applyValidationFilters();
    });

    applyValidationFilters();
}
