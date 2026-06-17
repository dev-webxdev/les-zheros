(() => {
    const main = document.getElementById("app-main");
    const afterMain = document.getElementById("app-after-main");

    if (!main) {
        return;
    }

    const allowedPaths = new Set([
        "/",
        "/guides",
        "/galerie",
        "/classement",
        "/stuffs",
        "/missions",
        "/sorties",
        "/mot-mystere"
    ]);

    const blockedPrefixes = [
        "/admin",
        "/login",
        "/register",
        "/logout",
        "/connexion",
        "/inscription",
        "/deconnexion",
        "/profil",
        "/profile",
        "/mission",
        "/sortie",
        "/votes",
        "/vote",
        "/uploads",
        "/upload"
    ];

    let activeRequest = null;

    const normalizePath = (pathname) => {
        if (!pathname || pathname === "/") {
            return "/";
        }

        return pathname.replace(/\/+$/, "") || "/";
    };

    const isAllowedPage = (url) => allowedPaths.has(normalizePath(url.pathname));

    const isBlockedPath = (url) => {
        const path = normalizePath(url.pathname);

        return blockedPrefixes.some((prefix) => path === prefix || path.startsWith(`${prefix}/`));
    };

    const isHashOnlyNavigation = (url) => (
        url.hash
        && normalizePath(url.pathname) === normalizePath(window.location.pathname)
        && url.search === window.location.search
    );

    const shouldHandleLink = (link, event) => {
        if (
            event.defaultPrevented
            || event.button !== 0
            || event.metaKey
            || event.ctrlKey
            || event.shiftKey
            || event.altKey
        ) {
            return false;
        }

        if (
            !link
            || link.dataset.noPjax !== undefined
            || link.hasAttribute("download")
            || (link.target && link.target !== "_self")
        ) {
            return false;
        }

        const href = link.getAttribute("href");

        if (!href || href.startsWith("#")) {
            return false;
        }

        const url = new URL(href, window.location.href);

        return url.origin === window.location.origin
            && !isHashOnlyNavigation(url)
            && !isBlockedPath(url)
            && isAllowedPage(url);
    };

    const closeMobileMenu = () => {
        const nav = document.querySelector("[data-nav]");
        const navToggle = document.querySelector("[data-nav-toggle]");

        nav?.classList.remove("is-open");
        navToggle?.setAttribute("aria-expanded", "false");
    };

    const syncActiveNavigation = (nextDocument) => {
        const activeHrefs = new Set(
            Array.from(nextDocument.querySelectorAll("[data-nav] .is-active a[href]"))
                .map((link) => new URL(link.getAttribute("href"), window.location.origin).pathname)
                .map(normalizePath)
        );

        document.querySelectorAll("[data-nav] li").forEach((item) => {
            const link = item.querySelector("a[href]");

            if (!link) {
                return;
            }

            const path = normalizePath(new URL(link.getAttribute("href"), window.location.origin).pathname);
            item.classList.toggle("is-active", activeHrefs.has(path));
        });
    };

    const setLoading = (isLoading) => {
        main.setAttribute("aria-busy", String(isLoading));
        document.documentElement.dataset.pjaxLoading = String(isLoading);
    };

    const replacePage = (nextDocument) => {
        const nextMain = nextDocument.getElementById("app-main");
        const nextAfterMain = nextDocument.getElementById("app-after-main");

        if (!nextMain) {
            throw new Error("Missing PJAX main container.");
        }

        main.innerHTML = nextMain.innerHTML;

        if (afterMain) {
            afterMain.innerHTML = nextAfterMain ? nextAfterMain.innerHTML : "";
        }

        document.title = nextDocument.title;
        document.body.className = nextDocument.body.className;
        syncActiveNavigation(nextDocument);
        closeMobileMenu();
        window.scrollTo({ top: 0, left: 0, behavior: "auto" });
        window.frontModules?.initPage?.(document);
    };

    const navigateTo = async (url, shouldPushState = true) => {
        const targetUrl = new URL(url, window.location.href);

        if (!isAllowedPage(targetUrl) || isBlockedPath(targetUrl)) {
            window.location.href = targetUrl.href;
            return;
        }

        activeRequest?.abort();

        const request = new AbortController();
        activeRequest = request;
        setLoading(true);

        try {
            const response = await fetch(targetUrl.href, {
                signal: request.signal,
                headers: {
                    "X-Requested-With": "fetch",
                    "X-PJAX": "true"
                }
            });

            const responseUrl = new URL(response.url);

            if (!response.ok || responseUrl.origin !== window.location.origin || !isAllowedPage(responseUrl)) {
                throw new Error("PJAX response not eligible.");
            }

            const html = await response.text();
            const nextDocument = new DOMParser().parseFromString(html, "text/html");

            replacePage(nextDocument);

            if (shouldPushState) {
                window.history.pushState({ pjax: true }, nextDocument.title, responseUrl.href);
            }
        } catch (error) {
            if (error.name === "AbortError") {
                return;
            }

            window.location.href = targetUrl.href;
        } finally {
            if (activeRequest === request) {
                setLoading(false);
                activeRequest = null;
            }
        }
    };

    document.addEventListener("click", (event) => {
        const link = event.target.closest("a[href]");

        if (!shouldHandleLink(link, event)) {
            return;
        }

        event.preventDefault();
        navigateTo(link.href);
    });

    window.addEventListener("popstate", () => {
        navigateTo(window.location.href, false);
    });

    window.history.replaceState({ pjax: true }, document.title, window.location.href);
})();
