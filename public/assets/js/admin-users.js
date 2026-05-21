const passwordInput = document.querySelector("[data-password-input]");
const generatePasswordButton = document.querySelector("[data-generate-password]");

const generatePassword = () => {
    const groups = [
        "ABCDEFGHJKLMNPQRSTUVWXYZ",
        "abcdefghijkmnopqrstuvwxyz",
        "23456789",
        "!@#$%?",
    ];
    const password = groups.map((group) => group[Math.floor(Math.random() * group.length)]);
    const pool = groups.join("");

    while (password.length < 14) {
        password.push(pool[Math.floor(Math.random() * pool.length)]);
    }

    return password
        .sort(() => Math.random() - 0.5)
        .join("");
};

generatePasswordButton?.addEventListener("click", () => {
    if (!passwordInput) {
        return;
    }

    passwordInput.value = generatePassword();
    passwordInput.focus();
});

const userSearchInput = document.querySelector("[data-user-search]");

userSearchInput?.addEventListener("input", () => {
    const query = userSearchInput.value.trim().toLowerCase();
    const rows = document.querySelectorAll(".admin-table--users tbody tr");

    rows.forEach((row) => {
        row.hidden = query !== "" && !row.textContent.toLowerCase().includes(query);
    });
});

const roleConfig = {
    admin: {
        label: "Administrateur",
        className: "admin-tag admin-tag--danger",
    },
    moderator: {
        label: "Modérateur",
        className: "admin-tag admin-tag--primary",
    },
    recruiter: {
        label: "Recruteur",
        className: "admin-tag admin-tag--success",
    },
    member: {
        label: "Membre",
        className: "admin-tag",
    },
};
const roleButtons = document.querySelectorAll("[data-user-roles]");

if (roleButtons.length) {
    const roleMenu = document.createElement("div");
    let activeRoleButton = null;

    roleMenu.className = "admin-role-menu";
    roleMenu.hidden = true;
    roleMenu.innerHTML = Object.entries(roleConfig).map(([role, config]) => (
        `<button type="button" data-role-option="${role}"><span>${config.label}</span><i class="fa-solid fa-check"></i></button>`
    )).join("");
    document.body.appendChild(roleMenu);

    const getButtonRôles = (button) => button.dataset.userRôles
        .split(",")
        .map((role) => role.trim())
        .filter(Boolean);

    const setButtonRôles = (button, roles) => {
        const uniqueRôles = [...new Set(roles)].filter((role) => roleConfig[role]);
        const finalRôles = uniqueRôles.length ? uniqueRôles : ["member"];
        const tags = button.querySelector(".admin-role-select__tags");

        button.dataset.userRôles = finalRôles.join(",");

        if (tags) {
            tags.innerHTML = finalRôles.map((role) => {
                const config = roleConfig[role];
                return `<span class="${config.className}">${config.label}</span>`;
            }).join("");
        }
    };

    const closeRoleMenu = () => {
        roleMenu.hidden = true;
        activeRoleButton?.classList.remove("is-open");
        activeRoleButton = null;
    };

    const positionRoleMenu = () => {
        if (!activeRoleButton || roleMenu.hidden) {
            return;
        }

        const rect = activeRoleButton.getBoundingClientRect();
        const menuRect = roleMenu.getBoundingClientRect();
        const gap = 6;
        const viewportGap = 10;
        const left = Math.min(Math.max(viewportGap, rect.left), window.innerWidth - menuRect.width - viewportGap);
        const top = Math.min(rect.bottom + gap, window.innerHeight - menuRect.height - viewportGap);

        roleMenu.style.left = `${left}px`;
        roleMenu.style.top = `${Math.max(viewportGap, top)}px`;
    };

    const openRoleMenu = (button) => {
        activeRoleButton?.classList.remove("is-open");
        activeRoleButton = button;
        activeRoleButton.classList.add("is-open");

        const activeRôles = getButtonRôles(button);
        roleMenu.querySelectorAll("[data-role-option]").forEach((option) => {
            option.classList.toggle("is-active", activeRôles.includes(option.dataset.roleOption));
        });

        roleMenu.hidden = false;
        positionRoleMenu();
    };

    roleButtons.forEach((button) => {
        button.addEventListener("click", (event) => {
            event.stopPropagation();

            if (activeRoleButton === button && !roleMenu.hidden) {
                closeRoleMenu();
                return;
            }

            openRoleMenu(button);
        });
    });

    roleMenu.addEventListener("click", (event) => {
        const option = event.target.closest("[data-role-option]");

        if (!option || !activeRoleButton) {
            return;
        }

        const config = roleConfig[option.dataset.roleOption];

        if (!config) {
            return;
        }

        const roles = getButtonRôles(activeRoleButton);
        const hasRole = roles.includes(option.dataset.roleOption);
        const nextRôles = hasRole
            ? roles.filter((role) => role !== option.dataset.roleOption)
            : [...roles, option.dataset.roleOption];

        setButtonRôles(activeRoleButton, nextRôles);
        option.classList.toggle("is-active", !hasRole);
    });

    document.addEventListener("click", (event) => {
        if (!roleMenu.hidden && !roleMenu.contains(event.target)) {
            closeRoleMenu();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && !roleMenu.hidden) {
            closeRoleMenu();
        }
    });

    window.addEventListener("resize", positionRoleMenu);
    window.addEventListener("scroll", positionRoleMenu, true);
}

document.querySelectorAll("[data-user-role-board]").forEach((board) => {
    const lists = board.querySelectorAll("[data-user-role-list]");
    const selectedList = board.querySelector('[data-user-role-list="selected"]');
    const inputs = board.querySelector("[data-user-role-inputs]");
    const emptyState = board.querySelector("[data-user-role-empty]");
    const canTapMoveRôles = window.matchMedia("(pointer: coarse)").matches;
    let draggedRole = null;

    const syncRoleInputs = () => {
        if (!selectedList || !inputs) {
            return;
        }

        const selectedRôles = selectedList.querySelectorAll("[data-user-role-chip]");
        inputs.innerHTML = Array.from(selectedRôles).map((role) => (
            `<input type="hidden" name="roles[]" value="${role.dataset.userRoleChip}">`
        )).join("");

        if (emptyState) {
            emptyState.hidden = selectedRôles.length > 0;
        }
    };

    board.querySelectorAll("[data-user-role-chip]").forEach((role) => {
        role.addEventListener("dragstart", (event) => {
            draggedRole = role;
            role.classList.add("is-dragging");
            event.dataTransfer.effectAllowed = "move";
            event.dataTransfer.setData("text/plain", role.dataset.userRoleChip || "");
        });

        role.addEventListener("dragend", () => {
            role.classList.remove("is-dragging");
            draggedRole = null;
            lists.forEach((list) => list.classList.remove("is-drag-over"));
            syncRoleInputs();
        });

        role.addEventListener("click", () => {
            if (!canTapMoveRôles) {
                return;
            }

            const currentList = role.closest("[data-user-role-list]");
            const targetListName = currentList?.dataset.userRoleList === "selected" ? "available" : "selected";
            const targetList = board.querySelector(`[data-user-role-list="${targetListName}"]`);

            if (!targetList) {
                return;
            }

            targetList.appendChild(role);
            syncRoleInputs();
        });
    });

    lists.forEach((list) => {
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

            if (!draggedRole || draggedRole.parentElement === list) {
                return;
            }

            list.appendChild(draggedRole);
            syncRoleInputs();
        });
    });

    syncRoleInputs();
});
