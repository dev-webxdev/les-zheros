window.frontModules = window.frontModules || {};

window.frontModules.initRanking = (root = document) => {
    const rankingTable = root.querySelector("[data-ranking-table]");

    if (!rankingTable || rankingTable.dataset.rankingInitialized === "true") {
        return;
    }

    rankingTable.dataset.rankingInitialized = "true";

    const rankingBody = rankingTable.querySelector("tbody");
    const rankingSortButtons = rankingTable.querySelectorAll("[data-ranking-sort]");
    let activeRankingSort = "month";
    let activeRankingDirection = "desc";
    const rankIcons = [
        '<i class="fa-solid fa-trophy"></i>',
        '<i class="fa-solid fa-medal"></i>',
        '<i class="fa-solid fa-award"></i>'
    ];

    const updateRankingRows = () => {
        Array.from(rankingBody?.querySelectorAll("tr:not([data-ranking-empty])") || []).forEach((row, index) => {
            row.classList.toggle("is-gold", index === 0);
            row.classList.toggle("is-silver", index === 1);
            row.classList.toggle("is-bronze", index === 2);

            const rankCell = row.querySelector("td:first-child span");

            if (rankCell) {
                rankCell.innerHTML = `${rankIcons[index] || ""} #${index + 1}`;
            }
        });
    };

    rankingSortButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const sortKey = button.dataset.rankingSort;
            const rows = Array.from(rankingBody?.querySelectorAll("tr:not([data-ranking-empty])") || []);

            if (activeRankingSort === sortKey) {
                activeRankingDirection = activeRankingDirection === "desc" ? "asc" : "desc";
            } else {
                activeRankingSort = sortKey || "month";
                activeRankingDirection = "desc";
            }

            rows.sort((firstRow, secondRow) => {
                const firstValue = Number(firstRow.dataset[sortKey] || 0);
                const secondValue = Number(secondRow.dataset[sortKey] || 0);

                return activeRankingDirection === "desc" ? secondValue - firstValue : firstValue - secondValue;
            });
            rows.forEach((row) => rankingBody?.appendChild(row));

            rankingSortButtons.forEach((item) => {
                const isActive = item === button;
                item.classList.toggle("is-active", isActive);

                const indicator = item.querySelector("span");

                if (indicator && isActive) {
                    indicator.textContent = activeRankingDirection === "desc" ? "\u2193" : "\u2191";
                }
            });
            updateRankingRows();
        });
    });

    updateRankingRows();
};

window.frontModules.initRanking();
