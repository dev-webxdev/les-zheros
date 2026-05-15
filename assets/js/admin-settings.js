const adminSettingsRoot = document.querySelector("[data-admin-settings]");

if (adminSettingsRoot) {
    const settingsStorageKey = "les-zheros-admin-settings";
    const cycleForm = adminSettingsRoot.querySelector('[data-settings-form="missions-cycle"]');
    const pointsForm = adminSettingsRoot.querySelector('[data-settings-form="points"]');
    const cycleEndInput = adminSettingsRoot.querySelector("[data-cycle-end]");
    const cycleSummary = adminSettingsRoot.querySelector("[data-cycle-summary]");
    const pointsBaseInput = adminSettingsRoot.querySelector("[data-points-base]");
    const pointsBonusInput = adminSettingsRoot.querySelector("[data-points-bonus]");
    const helpPointsInput = adminSettingsRoot.querySelector("[data-help-points]");
    const pointsPreview = adminSettingsRoot.querySelector("[data-points-preview]");

    const defaults = {
        missionCycleEnd: "2026-05-19T08:00",
        missionPointsBase: 1,
        missionBonusPerExtraCharacter: 0.25,
        guildHelpPoints: 0.5
    };

    const getStoredSettings = () => {
        try {
            return {
                ...defaults,
                ...JSON.parse(localStorage.getItem(settingsStorageKey))
            };
        } catch (error) {
            return { ...defaults };
        }
    };

    const saveSettings = (nextSettings) => {
        localStorage.setItem(settingsStorageKey, JSON.stringify({
            ...getStoredSettings(),
            ...nextSettings
        }));
    };

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

        const base = normalizeNumber(pointsBaseInput?.value, defaults.missionPointsBase);
        const bonus = normalizeNumber(pointsBonusInput?.value, defaults.missionBonusPerExtraCharacter);
        const help = normalizeNumber(helpPointsInput?.value, defaults.guildHelpPoints);
        const fourCharactersMission = base + (bonus * 3);

        pointsPreview.textContent = `Mission à 4 personnages : ${formatPoints(fourCharactersMission)}. Aide guilde : ${formatPoints(help)}.`;
    };

    const fillFields = () => {
        const settings = getStoredSettings();

        if (cycleEndInput) {
            cycleEndInput.value = settings.missionCycleEnd;
        }

        if (pointsBaseInput) {
            pointsBaseInput.value = Number(settings.missionPointsBase).toFixed(2);
        }

        if (pointsBonusInput) {
            pointsBonusInput.value = Number(settings.missionBonusPerExtraCharacter).toFixed(2);
        }

        if (helpPointsInput) {
            helpPointsInput.value = Number(settings.guildHelpPoints).toFixed(2);
        }

        updateCycleSummary();
        updatePointsPreview();
    };

    cycleEndInput?.addEventListener("input", updateCycleSummary);
    [pointsBaseInput, pointsBonusInput, helpPointsInput].forEach((input) => {
        input?.addEventListener("input", updatePointsPreview);
    });

    cycleForm?.addEventListener("submit", (event) => {
        event.preventDefault();

        saveSettings({
            missionCycleEnd: cycleEndInput?.value || defaults.missionCycleEnd
        });

        window.openAdminAlert?.({
            title: "Cycle enregistré",
            text: "La date de fin des missions a bien été mise à jour."
        });
    });

    pointsForm?.addEventListener("submit", (event) => {
        event.preventDefault();

        saveSettings({
            missionPointsBase: normalizeNumber(pointsBaseInput?.value, defaults.missionPointsBase),
            missionBonusPerExtraCharacter: normalizeNumber(pointsBonusInput?.value, defaults.missionBonusPerExtraCharacter),
            guildHelpPoints: normalizeNumber(helpPointsInput?.value, defaults.guildHelpPoints)
        });

        window.openAdminAlert?.({
            title: "Barème enregistré",
            text: "Le barème de points de la loterie a bien été sauvegardé."
        });
    });

    fillFields();
}
