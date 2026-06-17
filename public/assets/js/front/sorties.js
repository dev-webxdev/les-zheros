window.frontModules = window.frontModules || {};

window.frontModules.initSorties = (root = document) => {
    const guildVoteSections = root.querySelectorAll("[data-guild-vote]");

    if (guildVoteSections.length === 0) {
        return;
    }

    const reducedMotionMedia = window.matchMedia("(prefers-reduced-motion: reduce)");

    const flashElement = (element, className = "is-flash") => {
        if (!element || reducedMotionMedia.matches) {
            return;
        }

        element.classList.remove(className);
        void element.offsetWidth;
        element.classList.add(className);

        window.setTimeout(() => {
            element.classList.remove(className);
        }, 500);
    };

    guildVoteSections.forEach((guildVoteRoot) => {
        if (guildVoteRoot.dataset.guildVoteInitialized === "true") {
            return;
        }

        guildVoteRoot.dataset.guildVoteInitialized = "true";

        const feedback = guildVoteRoot.querySelector("[data-guild-vote-feedback]");
        const registeredCount = guildVoteRoot.querySelector("[data-guild-vote-registered]");
        const submitButton = guildVoteRoot.querySelector("[data-guild-vote-submit]");
        const cancelButton = guildVoteRoot.querySelector("[data-guild-vote-cancel]");
        const slotInput = guildVoteRoot.querySelector("[data-guild-vote-slot-input]");
        const dayButtons = Array.from(guildVoteRoot.querySelectorAll("[data-guild-vote-day]"));
        const dayPanels = Array.from(guildVoteRoot.querySelectorAll("[data-guild-vote-panel]"));
        const slots = Array.from(guildVoteRoot.querySelectorAll("[data-guild-vote-slot]"));
        const memberName = guildVoteRoot.dataset.memberName || "Invité";
        const initialFeedbackText = feedback?.textContent?.trim() || "";
        const defaultSlotLimit = Number(slots[0]?.dataset.slotLimit || "8");
        let activeDay = dayButtons.find((button) => button.classList.contains("is-active"))?.dataset.guildVoteDay || dayButtons[0]?.dataset.guildVoteDay || "";
        let selectedSlotId = slots.find((slot) => slot.dataset.slotConfirmed === "true")?.dataset.slotId || "";
        let confirmedSlotId = selectedSlotId;

        const getSlotButton = (slot) => slot.querySelector("[data-guild-vote-select]");
        const getSlotCount = (slot) => slot.querySelector("[data-guild-vote-count]");
        const getSlotMembers = (slot) => slot.querySelector("[data-guild-vote-members]");
        const getSlotCta = (slot) => slot.querySelector(".guild-vote-slot__cta");
        const getSlotPanel = (slot) => slot.closest("[data-guild-vote-panel]");
        const getSlotVotes = (slot) => Number(slot.dataset.slotVotes || "0");
        const getSlotLimit = (slot) => Number(slot.dataset.slotLimit || "8");
        const parseSlotMembers = (slot) => (slot.dataset.slotMembers || "").split("|").filter(Boolean);
        const writeSlotMembers = (slot, members) => {
            slot.dataset.slotMembers = members.join("|");
        };
        const findSlotById = (slotId) => slots.find((slot) => slot.dataset.slotId === slotId) || null;
        const slotBelongsToDay = (slot, day) => getSlotPanel(slot)?.dataset.guildVotePanel === day;

        const updateFeedback = (isSuccess, text) => {
            if (!feedback) {
                return;
            }

            feedback.textContent = text;
            feedback.classList.toggle("is-success", isSuccess);
        };

        const updateRegisteredCount = () => {
            if (!registeredCount) {
                return;
            }

            const confirmedSlot = findSlotById(confirmedSlotId);
            const votes = confirmedSlot ? getSlotVotes(confirmedSlot) : 0;
            const limit = confirmedSlot ? getSlotLimit(confirmedSlot) : defaultSlotLimit;

            registeredCount.textContent = `${votes}/${limit}`;
        };

        const updateSlot = (slot) => {
            const votes = getSlotVotes(slot);
            const limit = getSlotLimit(slot);
            const members = parseSlotMembers(slot);
            const isSelected = slot.dataset.slotId === selectedSlotId;
            const isConfirmed = slot.dataset.slotId === confirmedSlotId;
            const slotButton = getSlotButton(slot);
            const slotCount = getSlotCount(slot);
            const slotMembers = getSlotMembers(slot);
            const slotCta = getSlotCta(slot);

            slot.classList.toggle("is-selected", isSelected);
            slot.classList.toggle("is-confirmed", isConfirmed);

            if (slotButton) {
                slotButton.setAttribute("aria-pressed", String(isSelected || isConfirmed));
            }

            if (slotCount) {
                slotCount.textContent = `${votes}/${limit} places`;
            }

            if (slotCta) {
                if (isConfirmed || isSelected) {
                    slotCta.textContent = "";
                    slotCta.hidden = true;
                } else {
                    slotCta.hidden = false;
                    slotCta.textContent = "Choisir ce créneau";
                }
            }

            if (slotMembers) {
                slotMembers.textContent = members.length > 0 ? members.join(", ") : "Aucun inscrit";
            }
        };

        const updateAllSlots = () => {
            slots.forEach(updateSlot);
        };

        const updateActions = () => {
            if (submitButton) {
                submitButton.disabled = !selectedSlotId || Boolean(confirmedSlotId);
            }

            if (slotInput) {
                slotInput.value = selectedSlotId;
            }

            if (cancelButton) {
                cancelButton.hidden = !confirmedSlotId;
            }
        };

        const setActiveDay = (day, shouldFlash = true) => {
            activeDay = day;

            dayButtons.forEach((button) => {
                const isActive = button.dataset.guildVoteDay === day;
                button.classList.toggle("is-active", isActive);
                button.setAttribute("aria-pressed", String(isActive));
            });

            dayPanels.forEach((panel) => {
                const isActive = panel.dataset.guildVotePanel === day;
                panel.classList.toggle("is-active", isActive);
                panel.hidden = !isActive;
            });

            const selectedSlot = findSlotById(selectedSlotId);

            if (selectedSlot && !slotBelongsToDay(selectedSlot, day) && selectedSlotId !== confirmedSlotId) {
                selectedSlotId = "";
            }

            updateAllSlots();
            updateActions();

            if (shouldFlash) {
                const activePanel = dayPanels.find((panel) => panel.dataset.guildVotePanel === day);
                flashElement(activePanel);
            }
        };

        dayButtons.forEach((button) => {
            button.addEventListener("click", () => {
                if (!button.dataset.guildVoteDay) {
                    return;
                }

                setActiveDay(button.dataset.guildVoteDay);
            });
        });

        slots.forEach((slot) => {
            const slotButton = getSlotButton(slot);

            slotButton?.addEventListener("click", () => {
                if (confirmedSlotId && slot.dataset.slotId !== confirmedSlotId) {
                    return;
                }

                selectedSlotId = slot.dataset.slotId || "";
                updateAllSlots();
                updateActions();
                flashElement(slot);
            });
        });

        submitButton?.addEventListener("click", () => {
            if (submitButton.type === "submit") {
                return;
            }

            const slot = findSlotById(selectedSlotId);

            if (!slot || confirmedSlotId) {
                return;
            }

            const members = parseSlotMembers(slot);

            if (!members.includes(memberName)) {
                members.push(memberName);
                slot.dataset.slotVotes = String(getSlotVotes(slot) + 1);
                writeSlotMembers(slot, members);
            }

            confirmedSlotId = selectedSlotId;
            updateFeedback(true, "Vote enregistré. Inscription prise en compte.");
            showSiteToast({
                title: "Vote enregistré",
                text: "Ton inscription à la sortie est bien prise en compte."
            });
            updateRegisteredCount();
            updateAllSlots();
            updateActions();
            flashElement(slot);
            flashElement(feedback);
            flashElement(registeredCount, "is-flash-metric");
        });

        cancelButton?.addEventListener("click", () => {
            const slot = findSlotById(confirmedSlotId);

            if (!slot) {
                confirmedSlotId = "";
                selectedSlotId = "";
                updateFeedback(false, initialFeedbackText);
                updateRegisteredCount();
                updateAllSlots();
                updateActions();
                return;
            }

            const currentMembers = parseSlotMembers(slot);
            const nextMembers = currentMembers.filter((member) => member !== memberName);
            const hasRemovedMember = nextMembers.length !== currentMembers.length;

            if (hasRemovedMember) {
                slot.dataset.slotVotes = String(Math.max(getSlotVotes(slot) - 1, 0));
                writeSlotMembers(slot, nextMembers);
            }

            confirmedSlotId = "";
            selectedSlotId = "";
            updateFeedback(false, initialFeedbackText);
            showSiteToast({
                title: "Vote annulé",
                text: "Ton créneau a été libéré.",
                type: "warning"
            });
            updateRegisteredCount();
            updateAllSlots();
            updateActions();
            flashElement(feedback);
            flashElement(registeredCount, "is-flash-metric");
        });

        setActiveDay(activeDay, false);
        updateFeedback(false, initialFeedbackText);
        updateRegisteredCount();
        updateAllSlots();
        updateActions();
    });
};

window.frontModules.initSorties();
