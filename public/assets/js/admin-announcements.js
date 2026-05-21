const announcementStatus = document.querySelector("[data-announcement-status]");
const scheduledField = document.querySelector("[data-scheduled-field]");

if (announcementStatus && scheduledField) {
    const scheduledInput = scheduledField.querySelector("input");

    const updateScheduledField = () => {
        const isScheduled = announcementStatus.value === "scheduled";

        scheduledField.hidden = !isScheduled;
        scheduledField.style.display = isScheduled ? "" : "none";

        if (scheduledInput) {
            scheduledInput.required = isScheduled;
            if (!isScheduled) {
                scheduledInput.value = "";
            }
        }
    };

    announcementStatus.addEventListener("change", updateScheduledField);
    updateScheduledField();
}
