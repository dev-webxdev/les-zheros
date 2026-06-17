window.frontModules = window.frontModules || {};

window.frontModules.initWordMystery = (root = document) => {
    const tabGroups = Array.from(root.querySelectorAll("[data-word-mystery-tabs]"));
    let activeShowDifficulty = null;

    if (tabGroups.length === 0) {
        return;
    }

    tabGroups.forEach((tabs) => {
        const gameRoot = tabs.closest(".word-mystery-game");

        if (!gameRoot || gameRoot.dataset.wordMysteryInitialized === "true") {
            return;
        }

        gameRoot.dataset.wordMysteryInitialized = "true";

        const tabLinks = Array.from(tabs.querySelectorAll("[data-word-mystery-tab]"));
        const panels = Array.from(gameRoot.querySelectorAll("[data-word-mystery-panel]"));
        const rewardPanels = Array.from(gameRoot.querySelectorAll("[data-word-mystery-rewards]"));

        if (!tabLinks.length || !panels.length) {
            return;
        }

        const showDifficulty = (difficulty, url = null) => {
            tabLinks.forEach((tab) => {
                const isActive = tab.dataset.wordMysteryTab === difficulty;
                tab.classList.toggle("is-active", isActive);
                tab.setAttribute("aria-selected", isActive ? "true" : "false");
            });

            panels.forEach((panel) => {
                const isActive = panel.dataset.wordMysteryPanel === difficulty;
                panel.hidden = !isActive;

                if (isActive) {
                    const expectedLength = Number(panel.dataset.wordMysteryLength || 0);
                    const input = panel.querySelector("[data-word-mystery-input]");

                    if (input && expectedLength > 0) {
                        input.minLength = expectedLength;
                        input.maxLength = expectedLength;
                    }
                }
            });

            rewardPanels.forEach((panel) => {
                panel.hidden = panel.dataset.wordMysteryRewards !== difficulty;
            });

            if (url && window.history?.pushState) {
                window.history.pushState({ wordMysteryDifficulty: difficulty }, "", url);
            }
        };

        activeShowDifficulty = showDifficulty;

        tabLinks.forEach((tab) => {
            tab.addEventListener("click", (event) => {
                event.preventDefault();
                showDifficulty(tab.dataset.wordMysteryTab, tab.href);
            });
        });

        const updateGrid = (panel, guesses, wordLength) => {
            const rows = Array.from(panel.querySelectorAll(".word-mystery-row"));

            rows.forEach((row, rowIndex) => {
                const guess = guesses[rowIndex] || null;
                const cells = Array.from(row.querySelectorAll("span"));

                cells.forEach((cell, cellIndex) => {
                    cell.classList.remove("is-correct", "is-present", "is-absent");
                    cell.textContent = "";

                    if (!guess) {
                        return;
                    }

                    cell.textContent = (guess.word || "").charAt(cellIndex).toUpperCase();
                    const result = guess.result?.[cellIndex];

                    if (result) {
                        cell.classList.add(`is-${result}`);
                    }
                });

                row.style.gridTemplateColumns = `repeat(${wordLength}, minmax(0, 1fr))`;
            });
        };

        const buildResult = (payload) => {
            const result = document.createElement("div");
            result.className = `word-mystery-result ${payload.has_won ? "is-win" : "is-lost"}`;

            const icon = document.createElement("i");
            icon.className = payload.has_won ? "fa-solid fa-trophy" : "fa-solid fa-hourglass-end";

            const text = document.createElement("span");
            text.textContent = payload.message || "Partie terminee.";

            result.append(icon, text);
            return result;
        };

        const buildDailyCompletedResult = (payload) => {
            const result = document.createElement("div");
            result.className = "word-mystery-result is-lost";

            const icon = document.createElement("i");
            icon.className = "fa-solid fa-circle-info";

            const text = document.createElement("span");
            text.textContent = payload.has_won
                ? "Tu as deja gagne une recompense aujourd'hui. La prochaine sera disponible demain."
                : "Tu as deja termine ta partie du jour. Reviens demain pour retenter ta chance.";

            result.append(icon, text);
            return result;
        };

        gameRoot.querySelectorAll("[data-word-mystery-form]").forEach((form) => {
            form.addEventListener("submit", async (event) => {
                event.preventDefault();

                const panel = form.closest("[data-word-mystery-panel]");
                const submit = form.querySelector('button[type="submit"]');
                const input = form.querySelector('input[name="guess"]');
                const helper = form.querySelector("p");

                submit?.setAttribute("disabled", "disabled");

                try {
                    const response = await fetch(form.action, {
                        method: "POST",
                        body: new FormData(form),
                        headers: {
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest"
                        }
                    });
                    const payload = await response.json();

                    if (!response.ok || !payload.ok) {
                        showSiteToast({
                            title: payload.title || "Action impossible",
                            text: payload.message || "La proposition n'a pas pu etre enregistree.",
                            type: payload.type || "warning"
                        });
                        return;
                    }

                    updateGrid(panel, payload.guesses || [], payload.word_length || Number(input?.maxLength || 5));

                    if (input) {
                        input.value = "";
                    }

                    if (helper && !payload.has_won && !payload.has_lost) {
                        helper.textContent = `${payload.remaining} essai(s) restant(s). Mot de ${payload.word_length} lettres.`;
                    }

                    showSiteToast({
                        title: payload.title || "Essai enregistre",
                        text: payload.message || "La proposition a ete enregistree.",
                        type: payload.type || "success"
                    });

                    if (payload.has_won || payload.has_lost) {
                        form.replaceWith(buildResult(payload));
                    }

                    if (payload.daily_completed) {
                        gameRoot.querySelectorAll("[data-word-mystery-form]").forEach((otherForm) => {
                            otherForm.replaceWith(buildDailyCompletedResult(payload));
                        });
                    }
                } catch (error) {
                    showSiteToast({
                        title: "Action impossible",
                        text: "La proposition n'a pas pu etre envoyee.",
                        type: "danger"
                    });
                } finally {
                    submit?.removeAttribute("disabled");
                }
            });
        });
    });

    if (typeof activeShowDifficulty !== "function") {
        return;
    }

    if (typeof activeShowDifficulty !== "function") {
        return;
    }

    if (window.frontModules.wordMysteryPopstateHandler) {
        window.removeEventListener("popstate", window.frontModules.wordMysteryPopstateHandler);
    }

    window.frontModules.wordMysteryPopstateHandler = () => {
        if (window.location.pathname !== "/mot-mystere" || typeof activeShowDifficulty !== "function") {
            return;
        }

        const params = new URLSearchParams(window.location.search);
        const difficulty = params.get("difficulte") || "normal";
        activeShowDifficulty(difficulty);
    };

    window.addEventListener("popstate", window.frontModules.wordMysteryPopstateHandler);
};

window.frontModules.initWordMystery();
