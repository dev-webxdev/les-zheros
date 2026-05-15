const dashboardLayout = document.querySelector("[data-dashboard-layout]");
const dashboardEditButton = document.querySelector("[data-dashboard-edit]");
const dashboardStorageKey = "les-zheros-dashboard-layout";
const dashboardNoteKey = "les-zheros-dashboard-note";
let draggedDashboardWidget = null;
let dashboardToastTimeout = 0;

const getDashboardColumns = () => [...document.querySelectorAll("[data-dashboard-column]")];
const getDashboardWidgets = () => [...document.querySelectorAll("[data-dashboard-widget]")];

const showDashboardToast = (message) => {
    let toast = document.querySelector("[data-dashboard-toast]");

    if (!toast) {
        toast = document.createElement("div");
        toast.className = "admin-toast";
        toast.dataset.dashboardToast = "";
        toast.setAttribute("role", "status");
        toast.setAttribute("aria-live", "polite");
        document.body.appendChild(toast);
    }

    toast.innerHTML = `<i class="fa-solid fa-circle-check"></i><span>${message}</span>`;
    toast.classList.add("is-visible");

    window.clearTimeout(dashboardToastTimeout);
    dashboardToastTimeout = window.setTimeout(() => {
        toast.classList.remove("is-visible");
    }, 2200);
};

const saveDashboardLayout = () => {
    if (!dashboardLayout) {
        return;
    }

    const layout = {};

    getDashboardColumns().forEach((column) => {
        layout[column.dataset.dashboardColumn] = [...column.querySelectorAll("[data-dashboard-widget]")]
            .map((widget) => widget.dataset.dashboardWidget);
    });

    localStorage.setItem(dashboardStorageKey, JSON.stringify(layout));
};

const restoreDashboardLayout = () => {
    if (!dashboardLayout) {
        return;
    }

    let savedLayout = null;

    try {
        savedLayout = JSON.parse(localStorage.getItem(dashboardStorageKey));
    } catch (error) {
        savedLayout = null;
    }

    if (!savedLayout) {
        return;
    }

    const widgets = new Map(getDashboardWidgets().map((widget) => [widget.dataset.dashboardWidget, widget]));

    getDashboardColumns().forEach((column) => {
        const columnName = column.dataset.dashboardColumn;
        const widgetNames = savedLayout[columnName] || [];

        widgetNames.forEach((widgetName) => {
            const widget = widgets.get(widgetName);

            if (widget) {
                column.appendChild(widget);
            }
        });
    });
};

const setDashboardEditMode = (isEditing) => {
    if (!dashboardLayout || !dashboardEditButton) {
        return;
    }

    dashboardLayout.classList.toggle("is-editing", isEditing);
    dashboardEditButton.classList.toggle("is-active", isEditing);
    dashboardEditButton.querySelector("span").textContent = isEditing ? "Terminer la disposition" : "Modifier la disposition";

    getDashboardWidgets().forEach((widget) => {
        widget.draggable = isEditing;
    });
};

const moveDashboardWidget = (widget, direction) => {
    const column = widget.closest("[data-dashboard-column]");

    if (!column) {
        return;
    }

    if (direction === "toggle") {
        const nextColumn = getDashboardColumns().find((item) => item !== column);
        nextColumn?.appendChild(widget);
    }

    saveDashboardLayout();
};

restoreDashboardLayout();
setDashboardEditMode(false);

document.querySelectorAll(".admin-dashboard-note").forEach((note) => {
    const panel = note.closest("[data-dashboard-widget]");
    const saveButton = panel?.querySelector("[data-dashboard-note-save]");

    note.value = localStorage.getItem(dashboardNoteKey) || "";

    saveButton?.addEventListener("click", () => {
        localStorage.setItem(dashboardNoteKey, note.value);

        showDashboardToast("Notes enregistrées");
    });
});

dashboardEditButton?.addEventListener("click", () => {
    const isEditing = !dashboardLayout.classList.contains("is-editing");
    setDashboardEditMode(isEditing);
});

dashboardLayout?.addEventListener("click", (event) => {
    const button = event.target.closest("[data-dashboard-move]");

    if (!button || !dashboardLayout.classList.contains("is-editing")) {
        return;
    }

    const widget = button.closest("[data-dashboard-widget]");
    moveDashboardWidget(widget, button.dataset.dashboardMove);
});

getDashboardWidgets().forEach((widget) => {
    widget.addEventListener("dragstart", (event) => {
        if (!dashboardLayout?.classList.contains("is-editing")) {
            event.preventDefault();
            return;
        }

        draggedDashboardWidget = widget;
        widget.classList.add("is-dragging");
    });

    widget.addEventListener("dragend", () => {
        widget.classList.remove("is-dragging");
        draggedDashboardWidget = null;
        saveDashboardLayout();
    });
});

getDashboardColumns().forEach((column) => {
    column.addEventListener("dragover", (event) => {
        if (!draggedDashboardWidget || !dashboardLayout?.classList.contains("is-editing")) {
            return;
        }

        event.preventDefault();

        const afterWidget = [...column.querySelectorAll("[data-dashboard-widget]:not(.is-dragging)")]
            .find((widget) => event.clientY < widget.getBoundingClientRect().top + widget.offsetHeight / 2);

        if (afterWidget) {
            column.insertBefore(draggedDashboardWidget, afterWidget);
        } else {
            column.appendChild(draggedDashboardWidget);
        }
    });
});
