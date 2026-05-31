const lotteryRoot = document.querySelector("[data-lottery]");

if (lotteryRoot) {
    const lotteryStorageKey = "les-zheros-weekly-lottery";
    const weekSelect = lotteryRoot.querySelector("[data-lottery-week]");
    const rangeLabel = lotteryRoot.querySelector("[data-lottery-range]");
    const eligibleCount = lotteryRoot.querySelector("[data-lottery-eligible]");
    const totalTickets = lotteryRoot.querySelector("[data-lottery-tickets]");
    const lastDrawLabel = lotteryRoot.querySelector("[data-lottery-last]");
    const statusLabel = lotteryRoot.querySelector("[data-lottery-status]");
    const participantsBody = lotteryRoot.querySelector("[data-lottery-participants]");
    const participantsPagination = lotteryRoot.querySelector("[data-lottery-participants-pagination]");
    const participantHistoryBody = lotteryRoot.querySelector("[data-lottery-participant-history]");
    const participantHistoryPagination = lotteryRoot.querySelector("[data-lottery-participant-history-pagination]");
    const historyBody = lotteryRoot.querySelector("[data-lottery-history]");
    const historyPagination = lotteryRoot.querySelector("[data-lottery-history-pagination]");
    const settingsForm = lotteryRoot.querySelector("[data-lottery-settings]");
    const minPointsInput = lotteryRoot.querySelector("[data-lottery-min-points]");
    const testModeInput = lotteryRoot.querySelector("[data-lottery-test]");
    const resultBox = lotteryRoot.querySelector("[data-lottery-result]");
    const drawModal = document.querySelector("[data-lottery-draw-modal]");
    const drawState = document.querySelector("[data-lottery-draw-state]");
    const drawSlots = document.querySelectorAll("[data-lottery-draw-slot]");
    const drawActions = document.querySelector("[data-lottery-draw-actions]");
    const drawCloseButtons = document.querySelectorAll("[data-lottery-draw-close]");
    const downloadButton = document.querySelector("[data-lottery-download]");
    const drawButton = document.querySelector("[data-lottery-draw]");
    const refreshButton = document.querySelector("[data-lottery-refresh]");
    const numberFormatter = new Intl.NumberFormat("fr-FR");
    let latestGeneratedDraw = null;
    let isDrawing = false;
    let participantsPage = 1;
    const participantsPerPage = 12;
    let participantHistoryPage = 1;
    let drawHistoryPage = 1;
    const historyPerPage = 6;
    const participantsByWeek = (() => {
        try {
            return JSON.parse(document.querySelector("#lottery-participants-data")?.textContent || "{}");
        } catch (error) {
            return {};
        }
    })();
    const pendingValidationsByWeek = (() => {
        try {
            return JSON.parse(document.querySelector("#lottery-pending-validations-data")?.textContent || "{}");
        } catch (error) {
            return {};
        }
    })();
    const lotterySettings = (() => {
        try {
            return JSON.parse(document.querySelector("#lottery-settings-data")?.textContent || "{}");
        } catch (error) {
            return {};
        }
    })();

    const getParticipants = () => participantsByWeek[weekSelect?.value] || [];
    const getPendingValidationCount = () => Number(pendingValidationsByWeek[weekSelect?.value] || 0);

    const defaultHistory = [];

    const getPrizeInputs = () => [
        lotteryRoot.querySelector("#lottery-prize-1"),
        lotteryRoot.querySelector("#lottery-prize-2"),
        lotteryRoot.querySelector("#lottery-prize-3")
    ].filter(Boolean);

    const getStoredState = () => {
        try {
            return JSON.parse(localStorage.getItem(lotteryStorageKey)) || {};
        } catch (error) {
            return {};
        }
    };

    const setStoredState = (state) => {
        localStorage.setItem(lotteryStorageKey, JSON.stringify(state));
    };

    const getWeekLabel = () => weekSelect?.selectedOptions[0]?.textContent || "Cycle en cours";
    const getWeekValue = () => weekSelect?.value || "";

    const getSettings = () => ({
        prizes: (lotterySettings.prizes || [250000, 150000, 100000]).map((prize) => Math.max(Number(prize || 0), 0)),
        multiplier: 1,
        minPoints: Math.max(Number(lotterySettings.min_points || minPointsInput?.value || 0), 0),
        isTest: Boolean(testModeInput?.checked)
    });

    const getEligibleParticipants = () => {
        const settings = getSettings();

        return getParticipants()
            .filter((participant) => participant.points >= settings.minPoints)
            .map((participant) => ({
                ...participant,
                tickets: Math.max(Math.round(participant.points * settings.multiplier), 1)
            }));
    };

    const getTotalPrize = (winners) => winners.reduce((total, winner) => total + winner.prize, 0);

    const formatKamas = (value) => `${numberFormatter.format(value)} kamas`;

    const escapeHtml = (value) => String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
    const formatPoints = (value) => String(Math.round(Number(value || 0) * 100) / 100).replace(".", ",");
    const renderParticipantAvatar = (participant) => {
        if (participant.avatar) {
            return `<img src="${escapeHtml(participant.avatar)}" alt="Photo de ${escapeHtml(participant.name)}">`;
        }

        return escapeHtml(participant.initials || participant.name.slice(0, 2).toUpperCase());
    };

    const renderPagination = (container, page, pageCount, pageDatasetName, label) => {
        if (!container) {
            return;
        }

        if (pageCount <= 1) {
            container.innerHTML = "";
            return;
        }

        container.innerHTML = `
            <nav class="admin-pagination admin-pagination--pager" aria-label="${escapeHtml(label)}">
                <button class="admin-pagination__arrow${page === 1 ? " is-disabled" : ""}" type="button" data-${pageDatasetName}="1" ${page === 1 ? "disabled" : ""} aria-label="Premiere page"><i class="fa-solid fa-angles-left"></i></button>
                <button class="admin-pagination__arrow${page === 1 ? " is-disabled" : ""}" type="button" data-${pageDatasetName}="${page - 1}" ${page === 1 ? "disabled" : ""} aria-label="Page precedente"><i class="fa-solid fa-angle-left"></i></button>
                <span class="admin-pagination__current">${page}</span>
                <span class="admin-pagination__meta">sur ${pageCount}</span>
                <button class="admin-pagination__arrow${page === pageCount ? " is-disabled" : ""}" type="button" data-${pageDatasetName}="${page + 1}" ${page === pageCount ? "disabled" : ""} aria-label="Page suivante"><i class="fa-solid fa-angle-right"></i></button>
                <button class="admin-pagination__arrow${page === pageCount ? " is-disabled" : ""}" type="button" data-${pageDatasetName}="${pageCount}" ${page === pageCount ? "disabled" : ""} aria-label="Derniere page"><i class="fa-solid fa-angles-right"></i></button>
            </nav>
        `;
    };

    const drawWeightedWinner = (pool) => {
        const ticketTotal = pool.reduce((total, participant) => total + participant.tickets, 0);
        let cursor = Math.random() * ticketTotal;

        for (const participant of pool) {
            cursor -= participant.tickets;

            if (cursor <= 0) {
                return participant;
            }
        }

        return pool[pool.length - 1] || null;
    };

    const drawWinners = () => {
        const settings = getSettings();
        const pool = getEligibleParticipants();
        const winners = [];

        settings.prizes.forEach((prize) => {
            if (!pool.length || prize <= 0) {
                return;
            }

            const winner = drawWeightedWinner(pool);

            if (!winner) {
                return;
            }

            winners.push({
                name: winner.name,
                prize,
                tickets: winner.tickets,
                points: winner.points,
                missions: winner.missions,
                helps: winner.helps
            });
            pool.splice(pool.indexOf(winner), 1);
        });

        return winners;
    };

    const getHistory = () => getStoredState().history || defaultHistory;

    const setHistory = (history) => {
        const state = getStoredState();
        state.history = history;
        setStoredState(state);
    };

    const getDrawForCurrentWeek = () => getHistory().find((draw) => {
        if (draw.weekValue) {
            return draw.weekValue === getWeekValue();
        }

        return draw.week === getWeekLabel();
    }) || null;

    const updateDrawButtonState = () => {
        if (!(drawButton instanceof HTMLButtonElement)) {
            return;
        }

        const alreadyDrawn = Boolean(getDrawForCurrentWeek());
        const hasPendingValidations = getPendingValidationCount() > 0;
        drawButton.disabled = isDrawing || alreadyDrawn || hasPendingValidations;
        drawButton.dataset.lotteryLocked = alreadyDrawn ? "true" : "false";

        const label = drawButton.querySelector("span");
        if (label) {
            label.textContent = hasPendingValidations
                ? "Validation requise"
                : alreadyDrawn ? "Loterie déjà lancée" : "Lancer la loterie";
        }

        drawButton.title = hasPendingValidations
            ? "Valide les missions en attente avant de lancer ce tirage."
            : alreadyDrawn ? "Un tirage existe déjà pour ce cycle." : "";
    };

    const renderParticipants = () => {
        const eligibleParticipants = getEligibleParticipants();
        const ticketTotal = eligibleParticipants.reduce((total, participant) => total + participant.tickets, 0);
        const pageCount = Math.max(Math.ceil(eligibleParticipants.length / participantsPerPage), 1);
        participantsPage = Math.min(participantsPage, pageCount);
        const pageStart = (participantsPage - 1) * participantsPerPage;
        const pageParticipants = eligibleParticipants.slice(pageStart, pageStart + participantsPerPage);

        eligibleCount.textContent = String(eligibleParticipants.length);
        totalTickets.textContent = String(ticketTotal);

        if (!eligibleParticipants.length) {
            participantsBody.innerHTML = `
                <tr>
                    <td colspan="6">
                        <div class="admin-empty-state admin-lottery-table-empty">
                            <strong>Aucun participant éligible</strong>
                            <span>Les joueurs avec des points validés apparaîtront ici.</span>
                        </div>
                    </td>
                </tr>
            `;
            if (participantsPagination) {
                participantsPagination.innerHTML = "";
            }
            return;
        }

        participantsBody.innerHTML = pageParticipants.map((participant, index) => {
            const rank = pageStart + index + 1;
            const medal = rank === 1
                ? '<i class="fa-solid fa-trophy"></i>'
                : rank === 2
                    ? '<i class="fa-solid fa-medal"></i>'
                    : rank === 3
                        ? '<i class="fa-solid fa-award"></i>'
                        : "";

            return `
                <tr>
                    <td><span class="admin-rank-badge${rank === 1 ? " admin-rank-badge--gold" : rank === 2 ? " admin-rank-badge--silver" : rank === 3 ? " admin-rank-badge--bronze" : ""}">${medal} #${rank}</span></td>
                    <td><div class="admin-user-cell"><span class="admin-user-avatar">${renderParticipantAvatar(participant)}</span><strong>${escapeHtml(participant.name)}</strong></div></td>
                    <td>${participant.points}</td>
                    <td><strong>${participant.tickets}</strong></td>
                    <td>${participant.missions}</td>
                    <td>${participant.helps}</td>
                </tr>
            `;
        }).join("");

        renderPagination(participantsPagination, participantsPage, pageCount, "lottery-participants-page", "Pagination des participants");
    };

    const renderWinnerCell = (winner, index) => {
        if (!winner) {
            return "<span class=\"admin-muted-text\">-</span>";
        }

        return `
            <span class="admin-lottery-winner admin-lottery-winner--${index + 1}">
                <small>#${index + 1}</small>
                <strong>${escapeHtml(winner.name)}</strong>
                <em>${formatKamas(winner.prize)}</em>
            </span>
        `;
    };

    const participantTickets = (participant) => Math.max(Math.round(participant.points * getSettings().multiplier), 1);

    const renderParticipantNames = (participants) => {
        if (!participants.length) {
            return "<span class=\"admin-muted-text\">Aucun participant</span>";
        }

        const visibleLimit = 8;
        const visibleParticipants = participants.slice(0, visibleLimit);
        const hiddenParticipants = participants.slice(visibleLimit);
        const participantTooltip = (participant) => `${participant.name} - ${formatPoints(participant.points)} pts - ${participant.tickets} ticket${participant.tickets > 1 ? "s" : ""}`;

        return `
            <div class="admin-lottery-player-list">
                ${visibleParticipants.map((participant) => `
                    <span class="admin-user-cell admin-lottery-player-chip" data-lottery-tooltip="${escapeHtml(participantTooltip(participant))}">
                        <span class="admin-user-avatar">${renderParticipantAvatar(participant)}</span>
                        <strong>${escapeHtml(participant.name)}</strong>
                    </span>
                `).join("")}
                ${hiddenParticipants.length ? `
                    <span class="admin-lottery-more-chip" data-lottery-tooltip="${escapeHtml(hiddenParticipants.map(participantTooltip).join(" | "))}">
                        + ${hiddenParticipants.length} autres
                    </span>
                ` : ""}
            </div>
        `;
    };

    const renderParticipantHistory = () => {
        if (!participantHistoryBody) {
            return;
        }

        const settings = getSettings();
        const weeks = Object.entries(participantsByWeek)
            .filter(([, participants]) => Array.isArray(participants) && participants.some((participant) => participant.points >= settings.minPoints));
        const pageCount = Math.max(Math.ceil(weeks.length / historyPerPage), 1);
        participantHistoryPage = Math.min(participantHistoryPage, pageCount);
        const pageStart = (participantHistoryPage - 1) * historyPerPage;
        const pageWeeks = weeks.slice(pageStart, pageStart + historyPerPage);

        if (!weeks.length) {
            participantHistoryBody.innerHTML = `
                <tr>
                    <td colspan="6">
                        <div class="admin-empty-state admin-lottery-history-empty">
                            <strong>Aucun historique participant.</strong>
                            <span>Les cycles avec des validations apparaîtront ici.</span>
                        </div>
                    </td>
                </tr>
            `;
            if (participantHistoryPagination) {
                participantHistoryPagination.innerHTML = "";
            }
            return;
        }

        participantHistoryBody.innerHTML = pageWeeks.map(([weekValue, participants]) => {
            const weekOption = [...(weekSelect?.options || [])].find((option) => option.value === weekValue);
            const weekLabel = weekOption?.textContent || weekValue;
            const eligibleParticipants = participants
                .filter((participant) => participant.points >= settings.minPoints)
                .map((participant) => ({
                    ...participant,
                    tickets: participantTickets(participant)
                }));
            const totals = eligibleParticipants.reduce((total, participant) => ({
                points: total.points + Number(participant.points || 0),
                tickets: total.tickets + Number(participant.tickets || 0),
                missions: total.missions + Number(participant.missions || 0),
                helps: total.helps + Number(participant.helps || 0)
            }), { points: 0, tickets: 0, missions: 0, helps: 0 });

            return `
                <tr>
                    <td><strong>${escapeHtml(weekLabel)}</strong></td>
                    <td>${renderParticipantNames(eligibleParticipants)}</td>
                    <td>${Math.round(totals.points * 100) / 100}</td>
                    <td><strong>${totals.tickets}</strong></td>
                    <td>${totals.missions}</td>
                    <td>${totals.helps}</td>
                </tr>
            `;
        }).join("");

        renderPagination(participantHistoryPagination, participantHistoryPage, pageCount, "lottery-participant-history-page", "Pagination de l'historique des participants");
    };

    const renderHistory = () => {
        const history = getHistory();
        const pageCount = Math.max(Math.ceil(history.length / historyPerPage), 1);
        drawHistoryPage = Math.min(drawHistoryPage, pageCount);
        const pageStart = (drawHistoryPage - 1) * historyPerPage;
        const pageHistory = history.slice(pageStart, pageStart + historyPerPage);
        const currentWeekDraw = getDrawForCurrentWeek();
        const latest = currentWeekDraw || history[0];

        lastDrawLabel.textContent = latest ? latest.date : "Aucun tirage";
        statusLabel.textContent = currentWeekDraw ? "Tirage effectué" : "Tirage disponible";
        statusLabel.dataset.lotteryState = currentWeekDraw ? "done" : "available";
        updateDrawButtonState();

        if (!history.length) {
            historyBody.innerHTML = `
                <tr>
                    <td colspan="8">
                        <div class="admin-empty-state admin-lottery-history-empty">
                            <strong>Aucun tirage enregistré.</strong>
                            <span>Le prochain tirage hebdomadaire apparaîtra ici.</span>
                        </div>
                    </td>
                </tr>
            `;
            if (historyPagination) {
                historyPagination.innerHTML = "";
            }
            return;
        }

        historyBody.innerHTML = pageHistory.map((draw) => `
            <tr>
                <td>${escapeHtml(draw.date)}</td>
                <td>${escapeHtml(draw.week)}</td>
                <td>${renderWinnerCell(draw.winners[0], 0)}</td>
                <td>${renderWinnerCell(draw.winners[1], 1)}</td>
                <td>${renderWinnerCell(draw.winners[2], 2)}</td>
                <td><strong>${formatKamas(draw.total)}</strong></td>
                <td>${escapeHtml(draw.author)}</td>
                <td>
                    <button class="admin-danger-button admin-lottery-history-delete" type="button" data-lottery-delete="${escapeHtml(draw.id)}">
                        <i class="fa-regular fa-trash-can"></i>
                        <span>Supprimer</span>
                    </button>
                </td>
            </tr>
        `).join("");

        renderPagination(historyPagination, drawHistoryPage, pageCount, "lottery-history-page", "Pagination de l'historique des tirages");
    };

    const renderResult = (draw) => {
        resultBox.hidden = false;
        resultBox.innerHTML = `
            <div>
                <span>Résultat du tirage</span>
                <strong>${escapeHtml(draw.week)}</strong>
            </div>
            <ol>
                ${draw.winners.map((winner, index) => `
                    <li>
                        <span>#${index + 1}</span>
                        <strong>${escapeHtml(winner.name)}</strong>
                        <em>${formatKamas(winner.prize)}</em>
                    </li>
                `).join("")}
            </ol>
        `;
    };

    const sleep = (duration) => new Promise((resolve) => window.setTimeout(resolve, duration));

    const setSlotContent = (slot, winner, index, isFinal = false) => {
        slot.classList.toggle("is-final", isFinal);
        slot.classList.toggle("is-spinning", !isFinal);
        slot.querySelector("span").textContent = `#${index + 1}`;
        slot.querySelector("strong").textContent = winner?.name || "...";
        slot.querySelector("em").textContent = isFinal && winner ? formatKamas(winner.prize) : "...";
    };

    const openDrawModal = () => {
        if (!drawModal) {
            return;
        }

        drawModal.hidden = false;
        drawActions.hidden = true;
        drawCloseButtons.forEach((button) => {
            if (button instanceof HTMLButtonElement) {
                button.hidden = true;
            }
        });

        if (drawState) {
            drawState.textContent = "Mélange des tickets en cours...";
        }

        drawSlots.forEach((slot, index) => {
            slot.classList.remove("is-final");
            setSlotContent(slot, null, index);
        });
    };

    const closeDrawModal = () => {
        if (isDrawing || !drawModal) {
            return;
        }

        drawModal.hidden = true;
    };

    const animateDrawModal = async (winners) => {
        if (!drawModal) {
            return;
        }

        const pool = getEligibleParticipants();

        for (let tick = 0; tick < 24; tick += 1) {
            drawSlots.forEach((slot, index) => {
                const randomParticipant = pool[Math.floor(Math.random() * pool.length)];
                setSlotContent(slot, randomParticipant, index);
            });
            await sleep(Math.max(55, 130 - tick * 3));
        }

        for (let index = 0; index < drawSlots.length; index += 1) {
            setSlotContent(drawSlots[index], winners[index], index, true);
            await sleep(220);
        }

        if (drawState) {
            drawState.textContent = "Tirage terminé";
        }

        drawActions.hidden = false;
        drawCloseButtons.forEach((button) => {
            if (button instanceof HTMLButtonElement) {
                button.hidden = false;
            }
        });
    };

    const drawRoundRect = (context, x, y, width, height, radius) => {
        context.beginPath();
        context.moveTo(x + radius, y);
        context.lineTo(x + width - radius, y);
        context.quadraticCurveTo(x + width, y, x + width, y + radius);
        context.lineTo(x + width, y + height - radius);
        context.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
        context.lineTo(x + radius, y + height);
        context.quadraticCurveTo(x, y + height, x, y + height - radius);
        context.lineTo(x, y + radius);
        context.quadraticCurveTo(x, y, x + radius, y);
        context.closePath();
    };

    const drawLotteryImage = (draw) => {
        const canvas = document.createElement("canvas");
        canvas.width = 1400;
        canvas.height = 788;
        const context = canvas.getContext("2d");

        context.fillStyle = "#f6f8fc";
        context.fillRect(0, 0, canvas.width, canvas.height);

        context.fillStyle = "#ffffff";
        context.strokeStyle = "#d8e1ee";
        context.lineWidth = 2;
        drawRoundRect(context, 40, 34, 1320, 720, 22);
        context.fill();
        context.stroke();

        context.fillStyle = "#4869ee";
        context.font = "800 22px Inter, Arial";
        context.fillText("LOTERIE HEBDOMADAIRE", 78, 92);
        context.fillStyle = "#111827";
        context.font = "800 52px Inter, Arial";
        context.fillText("Résultats du tirage", 78, 150);
        context.fillStyle = "#4b5563";
        context.font = "700 30px Inter, Arial";
        context.fillText(draw.week, 78, 202);

        const eligibleParticipants = getEligibleParticipants();
        const ticketTotal = eligibleParticipants.reduce((total, participant) => total + participant.tickets, 0);
        const metaItems = [`Tirage: ${draw.date}`, `${eligibleParticipants.length} participants`, `${ticketTotal} tickets`];

        context.font = "800 24px Inter, Arial";
        let metaX = 78;
        metaItems.forEach((text) => {
            const width = context.measureText(text).width + 44;
            context.fillStyle = "#eef3ff";
            context.strokeStyle = "#d5e0ff";
            drawRoundRect(context, metaX, 226, width, 46, 23);
            context.fill();
            context.stroke();
            context.fillStyle = "#203a76";
            context.fillText(text, metaX + 22, 258);
            metaX += width + 18;
        });

        const cardThemes = [
            { bg: "#fff7d1", soft: "#fffcee", text: "#3a2500", muted: "#6d4f04", border: "#f0b90b" },
            { bg: "#eef5ff", soft: "#f8fbff", text: "#203a5f", muted: "#38547c", border: "#a9bedf" },
            { bg: "#fff0e7", soft: "#fff8f4", text: "#5b3020", muted: "#72422d", border: "#dd9c6b" }
        ];

        draw.winners.forEach((winner, index) => {
            const x = 78 + index * 420;
            const y = 316;
            context.fillStyle = cardThemes[index].bg;
            context.strokeStyle = cardThemes[index].border;
            context.lineWidth = 2;
            drawRoundRect(context, x, y, 380, 330, 18);
            context.fill();
            context.stroke();

            context.fillStyle = cardThemes[index].soft;
            drawRoundRect(context, x + 18, y + 18, 344, 66, 12);
            context.fill();

            context.fillStyle = cardThemes[index].text;
            context.font = "800 44px Inter, Arial";
            context.fillText(`#${index + 1}`, x + 36, y + 65);
            context.font = "800 42px Inter, Arial";
            context.fillText(winner.name, x + 36, y + 148);
            context.font = "800 30px Inter, Arial";
            context.fillText(formatKamas(winner.prize), x + 36, y + 202);

            context.fillStyle = cardThemes[index].muted;
            context.font = "800 24px Inter, Arial";
            context.fillText(`${String(winner.points).replace(".", ",")} pts`, x + 36, y + 256);
            context.fillText(`${winner.tickets} tickets`, x + 36, y + 292);
            context.fillText(`${winner.missions} missions - ${winner.helps} aide${winner.helps > 1 ? "s" : ""}`, x + 36, y + 328);
        });

        context.fillStyle = "#6b7280";
        context.font = "800 24px Inter, Arial";
        context.fillText("Les Z-héros - Publication Discord", 78, 710);

        return canvas;
    };

    const downloadLotteryImage = () => {
        if (!latestGeneratedDraw) {
            return;
        }

        const canvas = drawLotteryImage(latestGeneratedDraw);
        const link = document.createElement("a");
        link.download = `resultats-loterie-${Date.now()}.png`;
        link.href = canvas.toDataURL("image/png");
        link.click();
    };

    const saveSettings = () => {
        const state = getStoredState();
        state.settings = getSettings();
        setStoredState(state);
    };

    const restoreSettings = () => {
        const settings = getStoredState().settings;

        if (!settings) {
            return;
        }

        getPrizeInputs().forEach((input, index) => {
            input.value = settings.prizes?.[index] ?? input.value;
        });

        if (minPointsInput) {
            minPointsInput.value = settings.minPoints ?? minPointsInput.value;
        }

        if (testModeInput) {
            testModeInput.checked = Boolean(settings.isTest);
        }
    };

    const refreshLottery = () => {
        if (rangeLabel) {
            rangeLabel.textContent = getWeekLabel();
        }

        participantsPage = 1;
        renderParticipants();
        renderParticipantHistory();
        renderHistory();
    };

    settingsForm?.addEventListener("submit", (event) => {
        event.preventDefault();
        saveSettings();
        refreshLottery();
        window.openAdminAlert?.({
            title: "Barème sauvegardé",
            text: "Les paramètres de la loterie ont bien été enregistrés."
        });
    });

    weekSelect?.addEventListener("change", refreshLottery);
    [minPointsInput, ...getPrizeInputs()].forEach((input) => {
        input?.addEventListener("input", () => {
            participantsPage = 1;
            renderParticipants();
            renderParticipantHistory();
        });
    });

    participantsPagination?.addEventListener("click", (event) => {
        const pageButton = event.target.closest("[data-lottery-participants-page]");

        if (!pageButton || pageButton.disabled) {
            return;
        }

        participantsPage = Number(pageButton.dataset.lotteryParticipantsPage || 1);
        renderParticipants();
    });

    participantHistoryPagination?.addEventListener("click", (event) => {
        const pageButton = event.target.closest("[data-lottery-participant-history-page]");

        if (!pageButton || pageButton.disabled) {
            return;
        }

        participantHistoryPage = Number(pageButton.dataset.lotteryParticipantHistoryPage || 1);
        renderParticipantHistory();
    });

    historyPagination?.addEventListener("click", (event) => {
        const pageButton = event.target.closest("[data-lottery-history-page]");

        if (!pageButton || pageButton.disabled) {
            return;
        }

        drawHistoryPage = Number(pageButton.dataset.lotteryHistoryPage || 1);
        renderHistory();
    });

    refreshButton?.addEventListener("click", () => {
        refreshLottery();
        window.openAdminAlert?.({
            title: "Loterie actualisée",
            text: "Les participants et tickets ont été recalculés."
        });
    });

    drawCloseButtons.forEach((button) => {
        button.addEventListener("click", closeDrawModal);
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            closeDrawModal();
        }
    });

    downloadButton?.addEventListener("click", downloadLotteryImage);

    drawButton?.addEventListener("click", async () => {
        if (isDrawing) {
            return;
        }

        if (getDrawForCurrentWeek()) {
            window.openAdminAlert?.({
                title: "Loterie déjà lancée",
                text: "Un tirage existe déjà pour ce cycle. Supprime-le de l'historique si tu dois vraiment le refaire.",
                type: "warning"
            });
            updateDrawButtonState();
            return;
        }

        const pendingValidationCount = getPendingValidationCount();

        if (pendingValidationCount > 0) {
            window.openAdminAlert?.({
                title: "Validation requise",
                text: `${pendingValidationCount} déclaration(s) de mission attendent encore une validation avant ce tirage.`,
                type: "warning"
            });
            updateDrawButtonState();
            return;
        }

        const winners = drawWinners();

        if (winners.length < 3) {
            window.openAdminAlert?.({
                title: "Tirage impossible",
                text: "Il faut au moins trois gagnants possibles avec le barème actuel.",
                type: "warning"
            });
            return;
        }

        isDrawing = true;
        drawButton.disabled = true;
        openDrawModal();

        const draw = {
            id: `draw-${Date.now()}`,
            date: new Intl.DateTimeFormat("fr-FR", {
                weekday: "long",
                day: "numeric",
                month: "long",
                hour: "2-digit",
                minute: "2-digit"
            }).format(new Date()),
            weekValue: getWeekValue(),
            week: getWeekLabel(),
            winners,
            total: getTotalPrize(winners),
            author: getSettings().isTest ? "Mode test" : (lotteryRoot.dataset.lotteryAuthor || "Admin")
        };

        await animateDrawModal(draw.winners);
        latestGeneratedDraw = draw;

        if (!getSettings().isTest) {
            setHistory([draw, ...getHistory()]);
        }

        renderResult(draw);
        renderHistory();
        window.openAdminAlert?.({
            title: getSettings().isTest ? "Tirage test lancé" : "Loterie lancée",
            text: getSettings().isTest
                ? "Le résultat test est affiché sans toucher à l'historique."
                : "Les gagnants ont été ajoutés à l'historique."
        });

        isDrawing = false;
        updateDrawButtonState();
    });

    historyBody?.addEventListener("click", (event) => {
        const deleteButton = event.target.closest("[data-lottery-delete]");

        if (!deleteButton) {
            return;
        }

        setHistory(getHistory().filter((draw) => draw.id !== deleteButton.dataset.lotteryDelete));
        renderHistory();
        window.openAdminAlert?.({
            title: "Tirage supprimé",
            text: "Le tirage a été retiré de l'historique."
        });
    });

    restoreSettings();
    refreshLottery();
}
