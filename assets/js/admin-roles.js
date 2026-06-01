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
    const categoryLabels = {};
    let draggedPermission = null;

    permissionBoard.querySelectorAll("[data-permission-category-list]").forEach((group) => {
        const label = group.querySelector(".admin-permission-group__head span")?.textContent?.trim();
        if (label) {
            categoryLabels[group.dataset.permissionCategoryList] = label;
        }
    });

    const removeEmptyCategoryGroups = () => {
        permissionBoard.querySelectorAll(".admin-permission-group").forEach((group) => {
            if (!group.querySelector("[data-permission]")) {
                group.remove();
            }
        });
    };

    const ensureCategoryGroup = (list, category) => {
        if (!category) {
            return list;
        }

        let group = list.querySelector(`[data-permission-category-list="${category}"]`);

        if (!group) {
            group = document.createElement("div");
            group.className = "admin-permission-group";
            group.dataset.permissionCategoryList = category;

            const head = document.createElement("div");
            head.className = "admin-permission-group__head";

            const icon = document.createElement("i");
            icon.className = "fa-solid fa-folder";

            const label = document.createElement("span");
            label.textContent = categoryLabels[category] || category;

            const items = document.createElement("div");
            items.className = "admin-permission-group__items";

            head.append(icon, label);
            group.append(head, items);
            list.appendChild(group);
        }

        return group.querySelector(".admin-permission-group__items") || group;
    };

    const movePermissionToList = (permission, list) => {
        ensureCategoryGroup(list, permission.dataset.permissionCategory).appendChild(permission);
        removeEmptyCategoryGroups();
    };

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

            movePermissionToList(permission, targetList);
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

            if (!draggedPermission || draggedPermission.closest("[data-permission-list]") === list) {
                return;
            }

            movePermissionToList(draggedPermission, list);
            syncPermissionInputs();
        });
    });

    syncPermissionInputs();
}
