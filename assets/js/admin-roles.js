const permissionBoard = document.querySelector("[data-permission-board]");
const roleColorPicker = document.querySelector("[data-role-color-picker]");

if (roleColorPicker) {
    const roleColorValue = roleColorPicker.querySelector("[data-role-color-value]");
    const roleColorPreview = roleColorPicker.querySelector("[data-role-color-preview]");
    const colorLabels = {
        primary: "Bleu",
        success: "Vert",
        danger: "Rouge",
        warning: "Orange",
        violet: "Violet",
        teal: "Sarcelle",
        pink: "Rose",
        sky: "Ciel",
        neutral: "Neutre"
    };

    const syncRoleColorPreview = () => {
        if (!roleColorValue || !roleColorPreview) {
            return;
        }

        const color = roleColorValue.value || "neutral";
        roleColorPreview.className = `admin-tag admin-tag--${color}`;
        roleColorPreview.textContent = colorLabels[color] || color;
    };

    roleColorValue?.addEventListener("change", syncRoleColorPreview);
    syncRoleColorPreview();
}

document.querySelectorAll("[data-permission-count]").forEach((summary) => {
    const rawCount = summary.dataset.permissionCount;

    if (rawCount === "all") {
        summary.textContent = "Accès total";
        summary.classList.add("admin-tag--danger");
        return;
    }

    const count = Number(rawCount || 0);
    summary.textContent = count === 1 ? "1 permission" : `${count} permissions`;
});

if (permissionBoard) {
    const permissionLists = permissionBoard.querySelectorAll("[data-permission-list]");
    const selectedList = permissionBoard.querySelector('[data-permission-list="selected"]');
    const permissionInputs = permissionBoard.querySelector("[data-permission-inputs]");
    const emptyState = permissionBoard.querySelector("[data-permission-empty]");
    const canTapMovePermissions = window.matchMedia("(pointer: coarse)").matches;
    let draggedPermission = null;

    const syncPermissionInputs = () => {
        if (!selectedList || !permissionInputs) {
            return;
        }

        const selectedPermissions = selectedList.querySelectorAll("[data-permission]");
        permissionInputs.innerHTML = Array.from(selectedPermissions).map((permission) => (
            `<input type="hidden" name="permissions[]" value="${permission.dataset.permission}">`
        )).join("");

        if (emptyState) {
            emptyState.hidden = selectedPermissions.length > 0;
        }
    };

    permissionBoard.querySelectorAll("[data-permission]").forEach((permission) => {
        permission.addEventListener("dragstart", (event) => {
            draggedPermission = permission;
            permission.classList.add("is-dragging");
            event.dataTransfer.effectAllowed = "move";
            event.dataTransfer.setData("text/plain", permission.dataset.permission || "");
        });

        permission.addEventListener("dragend", () => {
            permission.classList.remove("is-dragging");
            draggedPermission = null;
            permissionLists.forEach((list) => list.classList.remove("is-drag-over"));
        });

        permission.addEventListener("click", () => {
            if (!canTapMovePermissions) {
                return;
            }

            const currentList = permission.closest("[data-permission-list]");
            const targetListName = currentList?.dataset.permissionList === "selected" ? "available" : "selected";
            const targetList = permissionBoard.querySelector(`[data-permission-list="${targetListName}"]`);

            if (!targetList) {
                return;
            }

            targetList.appendChild(permission);
            syncPermissionInputs();
        });
    });

    permissionLists.forEach((list) => {
        list.addEventListener("dragover", (event) => {
            event.preventDefault();
            list.classList.add("is-drag-over");
        });

        list.addEventListener("dragleave", () => {
            list.classList.remove("is-drag-over");
        });

        list.addEventListener("drop", (event) => {
            event.preventDefault();
            list.classList.remove("is-drag-over");

            if (!draggedPermission || draggedPermission.parentElement === list) {
                return;
            }

            list.appendChild(draggedPermission);
            syncPermissionInputs();
        });
    });

    syncPermissionInputs();
}
