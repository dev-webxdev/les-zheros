const adminSettingsRoot = document.querySelector("[data-admin-settings]");

if (adminSettingsRoot) {
    const cycleForm = adminSettingsRoot.querySelector('[data-settings-form="missions-cycle"]');
    const pointsForm = adminSettingsRoot.querySelector('[data-settings-form="points"]');
    const cycleEndInput = adminSettingsRoot.querySelector("[data-cycle-end]");
    const cycleSummary = adminSettingsRoot.querySelector("[data-cycle-summary]");
    const pointsBaseInput = adminSettingsRoot.querySelector("[data-points-base]");
    const pointsBonusInput = adminSettingsRoot.querySelector("[data-points-bonus]");
    const helpPointsInput = adminSettingsRoot.querySelector("[data-help-points]");
    const pointsPreview = adminSettingsRoot.querySelector("[data-points-preview]");
    const backupActionMessages = {
        create: {
            title: "Sauvegarde en cours",
            text: "La base et les fichiers du site sont en train d'être archivés."
        },
        restore: {
            title: "Restauration en cours",
            text: "Le site est remis dans l'état de la sauvegarde sélectionnée."
        },
    };
    let backupLoadingProgressTimer = 0;

    const decimalFormatter = new Intl.NumberFormat("fr-FR", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });

    const dateFormatter = new Intl.DateTimeFormat("fr-FR", {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit"
    });

    const normalizeNumber = (value, fallback) => {
        const number = Number(value);
        return Number.isFinite(number) && number >= 0 ? number : fallback;
    };

    const formatPoints = (value) => `${decimalFormatter.format(value)} pt${value > 1 ? "s" : ""}`;

    const updateCycleSummary = () => {
        if (!cycleEndInput || !cycleSummary) {
            return;
        }

        const currentEnd = new Date(cycleEndInput.value);

        if (Number.isNaN(currentEnd.getTime())) {
            cycleSummary.textContent = "Choisis une date de fin valide pour le cycle en cours.";
            return;
        }

        const nextEnd = new Date(currentEnd);
        nextEnd.setDate(nextEnd.getDate() + 7);

        cycleSummary.textContent = `Les missions restent déclarables jusqu'au ${dateFormatter.format(currentEnd)}. Ensuite, la date passera automatiquement au ${dateFormatter.format(nextEnd)}.`;
    };

    const updatePointsPreview = () => {
        if (!pointsPreview) {
            return;
        }

        const base = normalizeNumber(pointsBaseInput?.value, Number(pointsBaseInput?.defaultValue || 0));
        const bonus = normalizeNumber(pointsBonusInput?.value, Number(pointsBonusInput?.defaultValue || 0));
        const help = normalizeNumber(helpPointsInput?.value, Number(helpPointsInput?.defaultValue || 0));
        const fourCharactersMission = base + (bonus * 3);

        pointsPreview.textContent = `Mission à 4 personnages : ${formatPoints(fourCharactersMission)}. Aide guilde : ${formatPoints(help)}.`;
    };

    const showBackupLoading = (action = "create") => {
        const message = backupActionMessages[action] || backupActionMessages.create;
        let overlay = document.querySelector("[data-backup-loading]");

        if (!overlay) {
            overlay = document.createElement("div");
            overlay.className = "admin-backup-loading";
            overlay.dataset.backupLoading = "";
            overlay.setAttribute("role", "status");
            overlay.setAttribute("aria-live", "polite");
            overlay.innerHTML = `
                <div class="admin-backup-loading__dialog">
                    <span class="admin-backup-loading__spinner" aria-hidden="true"></span>
                    <strong data-backup-loading-title></strong>
                    <span data-backup-loading-text></span>
                    <div class="admin-backup-loading__progress" aria-hidden="true">
                        <span data-backup-loading-progress-bar></span>
                    </div>
                    <small data-backup-loading-progress-text>0%</small>
                </div>
            `;
            document.body.appendChild(overlay);
        }

        overlay.querySelector("[data-backup-loading-title]").textContent = message.title;
        overlay.querySelector("[data-backup-loading-text]").textContent = message.text;
        const progressBar = overlay.querySelector("[data-backup-loading-progress-bar]");
        const progressText = overlay.querySelector("[data-backup-loading-progress-text]");
        let progress = 0;

        window.clearInterval(backupLoadingProgressTimer);

        const setProgress = (value) => {
            progress = Math.min(value, 95);

            if (progressBar) {
                progressBar.style.width = `${progress}%`;
            }

            if (progressText) {
                progressText.textContent = `${progress}%`;
            }
        };

        setProgress(8);

        backupLoadingProgressTimer = window.setInterval(() => {
            const step = progress < 45 ? 7 : progress < 75 ? 4 : 2;
            setProgress(progress + step);
        }, 420);

        overlay.hidden = false;
        document.body.classList.add("is-backup-loading");
    };

    document.addEventListener("submit", (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || !form.dataset.backupAction) {
            return;
        }

        if (form.hasAttribute("data-confirm-form") && form.dataset.confirmedSubmit !== "true") {
            return;
        }

        showBackupLoading(form.dataset.backupAction);
    });

    cycleEndInput?.addEventListener("input", updateCycleSummary);
    [pointsBaseInput, pointsBonusInput, helpPointsInput].forEach((input) => {
        input?.addEventListener("input", updatePointsPreview);
    });
    cycleForm?.addEventListener("submit", updateCycleSummary);
    pointsForm?.addEventListener("submit", updatePointsPreview);
    updateCycleSummary();
    updatePointsPreview();
}
