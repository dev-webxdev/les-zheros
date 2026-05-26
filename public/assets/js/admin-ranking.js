const rankingTable = document.querySelector("[data-sortable-ranking]");

if (rankingTable) {
    const rankingBody = rankingTable.querySelector("tbody");
    const sortButtons = rankingTable.querySelectorAll("[data-sort-ranking]");
    let currentSort = {
        key: "week",
        direction: "desc",
    };

    const updateRankingBadges = () => {
        rankingBody.querySelectorAll("tr:not([data-ranking-empty])").forEach((row, index) => {
            const rank = index + 1;
            const badge = row.querySelector(".admin-rank-badge");

            row.classList.toggle("admin-ranking-row--gold", rank === 1);
            row.classList.toggle("admin-ranking-row--silver", rank === 2);
            row.classList.toggle("admin-ranking-row--bronze", rank === 3);

            if (!badge) {
                return;
            }

            badge.classList.toggle("admin-rank-badge--gold", rank === 1);
            badge.classList.toggle("admin-rank-badge--silver", rank === 2);
            badge.classList.toggle("admin-rank-badge--bronze", rank === 3);

            if (rank === 1) {
                badge.innerHTML = '<i class="fa-solid fa-trophy"></i> #1';
            } else if (rank === 2) {
                badge.innerHTML = '<i class="fa-solid fa-medal"></i> #2';
            } else if (rank === 3) {
                badge.innerHTML = '<i class="fa-solid fa-award"></i> #3';
            } else {
                badge.textContent = `#${rank}`;
            }
        });
    };

    const setSortIcons = (key, direction) => {
        sortButtons.forEach((button) => {
            const isActive = button.dataset.sortRanking === key;
            const icon = button.querySelector("span i");

            button.classList.toggle("is-active", isActive);

            if (icon) {
                icon.classList.toggle("fa-arrow-up", isActive && direction === "asc");
                icon.classList.toggle("fa-arrow-down", !isActive || direction === "desc");
            }
        });
    };

    const sortRanking = (key) => {
        const direction = currentSort.key === key && currentSort.direction === "desc" ? "asc" : "desc";
        const rows = Array.from(rankingBody.querySelectorAll("tr:not([data-ranking-empty])"));

        rows.sort((rowA, rowB) => {
            const scoreA = Number(rowA.dataset[key] || 0);
            const scoreB = Number(rowB.dataset[key] || 0);

            return direction === "desc" ? scoreB - scoreA : scoreA - scoreB;
        });

        rows.forEach((row) => rankingBody.appendChild(row));
        currentSort = { key, direction };

        setSortIcons(key, direction);
        updateRankingBadges();
    };

    sortButtons.forEach((button) => {
        button.addEventListener("click", () => {
            sortRanking(button.dataset.sortRanking);
        });
    });

    setSortIcons(currentSort.key, currentSort.direction);
}
