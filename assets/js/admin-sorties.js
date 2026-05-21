const outingRoot = document.querySelector("[data-admin-outings]");

if (outingRoot) {
    const form = outingRoot.querySelector("[data-outing-form]");
    const titleInput = outingRoot.querySelector("[data-outing-title]");
    const descriptionInput = outingRoot.querySelector("[data-outing-description]");
    const placesInput = outingRoot.querySelector("[data-outing-places]");
    const closeInput = outingRoot.querySelector("[data-outing-close]");
    const newDateInput = outingRoot.querySelector("[data-outing-new-date]");
    const newTimesInput = outingRoot.querySelector("[data-outing-new-times]");
    const addDayButton = outingRoot.querySelector("[data-outing-add-day]");
    const daysContainer = outingRoot.querySelector("[data-outing-days]");
    const slotCount = outingRoot.querySelector("[data-outing-slot-count]");
    const previewTitle = outingRoot.querySelector("[data-outing-preview-title]");
    const previewDescription = outingRoot.querySelector("[data-outing-preview-description]");
    const previewPlaces = outingRoot.querySelector("[data-outing-preview-places]");
    const previewClose = outingRoot.querySelector("[data-outing-preview-close]");
    const previewDays = outingRoot.querySelector("[data-outing-preview-days]");
    const previewSlots = outingRoot.querySelector("[data-outing-preview-slots]");
    const scheduleInput = outingRoot.querySelector("[data-outing-schedule-input]");
    const dateFormatter = new Intl.DateTimeFormat("fr-FR", { weekday: "short", day: "numeric", month: "short" });
    const longDateFormatter = new Intl.DateTimeFormat("fr-FR", { weekday: "long", day: "numeric", month: "long" });
    const closeFormatter = new Intl.DateTimeFormat("fr-FR", {
        weekday: "short",
        day: "numeric",
        month: "short",
        hour: "2-digit",
        minute: "2-digit"
    });

    let schedule = [];
    try {
        schedule = JSON.parse(outingRoot.dataset.outingInitialSchedule || "[]") || [];
    } catch (error) {
        schedule = [];
    }
    let selectedPreviewDate = schedule[0]?.date || "";

    const showAlert = (title, text, type = "success") => {
        window.openAdminAlert?.({ title, text, type });
    };

    const parseDate = (value) => {
        const date = new Date(`${value}T12:00:00`);
        return Number.isNaN(date.getTime()) ? null : date;
    };

    const formatDate = (value, formatter = dateFormatter) => {
        const date = parseDate(value);
        return date ? formatter.format(date).replace(".", "") : value;
    };

    const normalizeTime = (time) => {
        const match = time.trim().match(/^(\d{1,2})[:hH](\d{2})$/);

        if (!match) {
            return "";
        }

        const hour = Number(match[1]);
        const minute = Number(match[2]);

        if (hour > 23 || minute > 59) {
            return "";
        }

        return `${String(hour).padStart(2, "0")}:${String(minute).padStart(2, "0")}`;
    };

    const parseTimes = (value) => [...new Set(value
        .split(/[;,]/)
        .map(normalizeTime)
        .filter(Boolean))]
        .sort();

    const getTotalSlots = () => schedule.reduce((total, day) => total + day.times.length, 0);

    const getFirstSlotDate = () => {
        const firstSlot = schedule
            .flatMap((day) => day.times.map((time) => `${day.date}T${time}`))
            .sort()[0];

        if (!firstSlot) {
            return null;
        }

        const date = new Date(firstSlot);

        return Number.isNaN(date.getTime()) ? null : date;
    };

    const formatDatetimeLocal = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");
        const hours = String(date.getHours()).padStart(2, "0");
        const minutes = String(date.getMinutes()).padStart(2, "0");

        return `${year}-${month}-${day}T${hours}:${minutes}`;
    };

    const parseCloseDate = (value) => {
        const trimmed = String(value || "").trim();

        if (!trimmed) {
            return null;
        }

        const frenchMatch = trimmed.match(/^(\d{2})\/(\d{2})\/(\d{4})\s+(\d{1,2}):(\d{2})$/);

        if (frenchMatch) {
            const [, day, month, year, hour, minute] = frenchMatch;
            const date = new Date(`${year}-${month}-${day}T${String(hour).padStart(2, "0")}:${minute}`);

            return Number.isNaN(date.getTime()) ? null : date;
        }

        const date = new Date(trimmed);

        return Number.isNaN(date.getTime()) ? null : date;
    };

    const getLatestCloseDate = () => {
        const firstSlotDate = getFirstSlotDate();

        if (!firstSlotDate) {
            return null;
        }

        return new Date(firstSlotDate.getTime() - 2 * 60 * 60 * 1000);
    };

    const cleanSchedule = () => {
        schedule = schedule
            .filter((day) => day.date && day.times.length)
            .map((day) => ({ date: day.date, times: [...new Set(day.times)].sort() }))
            .sort((a, b) => a.date.localeCompare(b.date));
    };

    const renderDayCards = () => {
        if (!daysContainer) {
            return;
        }

        if (!schedule.length) {
            daysContainer.innerHTML = '<p class="admin-outing-empty">Aucun créneau. Ajoute un jour et ses heures.</p>';
            return;
        }

        daysContainer.innerHTML = schedule.map((day) => `
            <article class="admin-outing-day-card">
                <div class="admin-outing-day-card__head">
                    <div>
                        <strong>${formatDate(day.date, longDateFormatter)}</strong>
                        <span>${day.times.length} créneau${day.times.length > 1 ? "x" : ""}</span>
                    </div>
                    <button class="admin-action-button admin-action-button--delete" type="button" data-outing-remove-day="${day.date}" aria-label="Retirer ${formatDate(day.date, longDateFormatter)}" title="Retirer le jour">
                        <i class="fa-regular fa-trash-can"></i>
                    </button>
                </div>
                <div class="admin-outing-slot-list">
                    ${day.times.map((time) => `
                        <span class="admin-outing-slot-chip">
                            ${time}
                            <button type="button" data-outing-remove-slot="${day.date}|${time}" aria-label="Retirer ${time}">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </span>
                    `).join("")}
                </div>
            </article>
        `).join("");
    };

    const renderPreview = () => {
        const title = titleInput?.value.trim() || "Nouvelle sortie";
        const description = descriptionInput?.value.trim() || "Ajoute une description courte pour donner le contexte.";
        const places = Math.max(1, Number(placesInput?.value || 1));
        const closeDate = parseCloseDate(closeInput?.value);

        if (previewTitle) {
            previewTitle.textContent = title;
        }

        if (previewDescription) {
            previewDescription.textContent = description;
        }

        if (previewPlaces) {
            previewPlaces.textContent = `${places} joueur${places > 1 ? "s" : ""}`;
        }

        if (previewClose) {
            previewClose.textContent = closeDate && !Number.isNaN(closeDate.getTime())
                ? closeFormatter.format(closeDate).replace(",", "")
                : "Non définie";
        }

        if (!schedule.some((day) => day.date === selectedPreviewDate)) {
            selectedPreviewDate = schedule[0]?.date || "";
        }

        if (previewDays) {
            previewDays.innerHTML = schedule.map((day) => `
                <button class="admin-outing-preview-day${day.date === selectedPreviewDate ? " is-active" : ""}" type="button" data-outing-preview-date="${day.date}" aria-pressed="${day.date === selectedPreviewDate}">
                    <span>${formatDate(day.date).split(" ")[0]}</span>
                    <strong>${formatDate(day.date).replace(/^\\S+\\s/, "")}</strong>
                </button>
            `).join("");
        }

        if (previewSlots) {
            const selectedDay = schedule.find((day) => day.date === selectedPreviewDate);
            const slots = selectedDay
                ? selectedDay.times.map((time) => ({ date: selectedDay.date, time }))
                : [];

            previewSlots.innerHTML = slots.length
                ? slots.map((slot) => `
                    <article class="admin-outing-preview-slot">
                        <span>${formatDate(slot.date, longDateFormatter)}</span>
                        <strong>${slot.time}</strong>
                        <span>0/${places} inscrits</span>
                    </article>
                `).join("")
                : '<p class="admin-outing-empty">L’aperçu apparaîtra quand un créneau sera ajouté.</p>';
        }

        if (slotCount) {
            const total = getTotalSlots();
            slotCount.textContent = `${total} créneau${total > 1 ? "x" : ""}`;
        }

        if (scheduleInput) {
            scheduleInput.value = JSON.stringify(schedule);
        }
    };

    const render = () => {
        cleanSchedule();
        renderDayCards();
        renderPreview();
    };

    const addSlots = (date, times) => {
        if (!date || !times.length) {
            return false;
        }

        const existingDay = schedule.find((day) => day.date === date);

        if (existingDay) {
            existingDay.times = [...existingDay.times, ...times];
        } else {
            schedule.push({ date, times });
        }

        render();
        return true;
    };

    const addNewDay = () => {
        const date = newDateInput?.value || "";
        const times = parseTimes(newTimesInput?.value || "");

        if (!addSlots(date, times)) {
            showAlert("Créneau incomplet", "Choisis une date et au moins une heure valide.", "warning");
            return;
        }

        showAlert("Créneaux ajoutés", "Le planning de vote a été mis à jour.");
    };

    addDayButton?.addEventListener("click", addNewDay);

    outingRoot.querySelector("[data-outing-clear]")?.addEventListener("click", () => {
        schedule = [];
        selectedPreviewDate = "";
        render();
        showAlert("Créneaux vidés", "Le planning de vote est prêt à être reconstruit.");
    });

    daysContainer?.addEventListener("click", (event) => {
        const removeSlotButton = event.target.closest("[data-outing-remove-slot]");
        const removeDayButton = event.target.closest("[data-outing-remove-day]");

        if (removeSlotButton) {
            const [date, time] = removeSlotButton.dataset.outingRemoveSlot.split("|");
            const day = schedule.find((item) => item.date === date);

            if (day) {
                day.times = day.times.filter((item) => item !== time);
                render();
            }
            return;
        }

        if (removeDayButton) {
            schedule = schedule.filter((day) => day.date !== removeDayButton.dataset.outingRemoveDay);
            render();
        }
    });

    previewDays?.addEventListener("click", (event) => {
        const dayButton = event.target.closest("[data-outing-preview-date]");

        if (!dayButton) {
            return;
        }

        selectedPreviewDate = dayButton.dataset.outingPreviewDate || "";
        renderPreview();
    });

    [titleInput, descriptionInput, placesInput, closeInput].forEach((input) => {
        input?.addEventListener("input", renderPreview);
    });

    form?.addEventListener("submit", (event) => {
        if (!getTotalSlots()) {
            event.preventDefault();
            showAlert("Aucun créneau", "Ajoute au moins un créneau avant de publier.", "warning");
            return;
        }

        const latestCloseDate = getLatestCloseDate();
        const closeDate = parseCloseDate(closeInput?.value);

        if (latestCloseDate && closeDate && closeDate > latestCloseDate) {
            event.preventDefault();
            showAlert("Clôture trop tardive", "La clôture des votes doit être au plus tard 2 heures avant le premier créneau.", "warning");
            closeInput?.focus();
            return;
        }

        if (scheduleInput) {
            scheduleInput.value = JSON.stringify(schedule);
        }
    });

    render();
}
