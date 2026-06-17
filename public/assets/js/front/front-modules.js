window.frontModules = window.frontModules || {};

window.frontModules.initPage = function initPage(root = document) {
    [
        "initGallery",
        "initRanking",
        "initNewsModal",
        "initAlmanax",
        "initGuides",
        "initStuffs",
        "initSorties",
        "initWordMystery"
    ].forEach((moduleName) => {
        if (typeof window.frontModules[moduleName] === "function") {
            window.frontModules[moduleName](root);
        }
    });
};
