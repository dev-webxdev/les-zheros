const initAdminRichEditor = (editor) => {
    if (!editor || editor.dataset.richEditorReady === "true") {
        return;
    }

    editor.dataset.richEditorReady = "true";
    const surface = editor.querySelector("[data-editor-surface]");
    const input = editor.querySelector("[data-editor-input]");
    const form = editor.closest("form");

    if (!surface || !input) {
        return;
    }

    const syncEditorInput = () => {
        input.value = surface.innerHTML.trim();
    };

    const selectionHasFormat = (command) => {
        const selection = window.getSelection();
        const selectors = {
            bold: "b, strong",
            italic: "i, em",
            underline: "u",
            insertUnorderedList: "ul",
        };

        if (!selection || selection.rangeCount === 0 || !selectors[command]) {
            return false;
        }

        let node = selection.getRangeAt(0).startContainer;
        node = node.nodeType === Node.TEXT_NODE ? node.parentElement : node;

        return Boolean(node?.closest(selectors[command]) && surface.contains(node));
    };

    const updateToolbarState = () => {
        editor.querySelectorAll("[data-editor-command]").forEach((button) => {
            const command = button.dataset.editorCommand;

            if (["bold", "italic", "underline", "insertUnorderedList", "insertOrderedList", "justifyLeft", "justifyCenter", "justifyRight"].includes(command)) {
                button.classList.toggle("is-active", document.queryCommandState(command) || selectionHasFormat(command));
            }
        });
    };

    const getImageToAlign = () => {
        const selectedImage = surface.querySelector("img.is-selected");

        if (selectedImage) {
            return selectedImage;
        }

        const selection = window.getSelection();

        if (!selection || selection.rangeCount === 0) {
            return null;
        }

        const range = selection.getRangeAt(0);

        if (!surface.contains(range.commonAncestorContainer)) {
            return null;
        }

        const container = range.startContainer.nodeType === Node.TEXT_NODE
            ? range.startContainer.parentElement
            : range.startContainer;

        if (container instanceof HTMLImageElement) {
            return container;
        }

        if (container instanceof Element) {
            const nearbyImage = container.querySelector("img")
                || container.previousElementSibling
                || container.nextElementSibling;

            return nearbyImage instanceof HTMLImageElement ? nearbyImage : null;
        }

        return null;
    };

    const alignImage = (image, command) => {
        if (!(image instanceof HTMLImageElement) || !["justifyLeft", "justifyCenter", "justifyRight"].includes(command)) {
            return false;
        }

        image.style.display = "block";
        image.style.textAlign = "";

        if (command === "justifyCenter") {
            image.style.margin = "18px auto";
        } else if (command === "justifyRight") {
            image.style.margin = "18px 0 18px auto";
        } else {
            image.style.margin = "18px auto 18px 0";
        }

        syncEditorInput();
        return true;
    };

    editor.querySelectorAll("[data-editor-command]").forEach((button) => {
        button.addEventListener("mousedown", (event) => {
            event.preventDefault();
            surface.focus();

            if (["justifyLeft", "justifyCenter", "justifyRight"].includes(button.dataset.editorCommand)
                && alignImage(getImageToAlign(), button.dataset.editorCommand)) {
                updateToolbarState();
                return;
            }

            if (button.dataset.editorCommand === "insertImage") {
                const imageUrl = window.prompt("URL de l'image");

                if (!imageUrl) {
                    return;
                }

                document.execCommand("insertImage", false, imageUrl);
                syncEditorInput();
                return;
            }

            document.execCommand(button.dataset.editorCommand, false, button.dataset.editorValue || null);
            updateToolbarState();
            syncEditorInput();
        });
    });

    editor.querySelector("[data-editor-link]")?.addEventListener("mousedown", (event) => {
        event.preventDefault();
        surface.focus();
        const url = window.prompt("Lien de destination");

        if (!url) {
            return;
        }

        document.execCommand("createLink", false, url);
        updateToolbarState();
        syncEditorInput();
    });

    surface.addEventListener("click", (event) => {
        surface.querySelectorAll("img.is-selected").forEach((image) => {
            image.classList.remove("is-selected");
        });

        if (event.target instanceof HTMLImageElement) {
            event.target.classList.add("is-selected");
            event.target.style.textAlign = "";
        }
    });

    surface.addEventListener("keyup", () => {
        updateToolbarState();
        syncEditorInput();
    });
    surface.addEventListener("mouseup", updateToolbarState);
    surface.addEventListener("input", () => {
        updateToolbarState();
        syncEditorInput();
    });
    surface.addEventListener("blur", syncEditorInput);
    form?.addEventListener("submit", syncEditorInput);
    syncEditorInput();
};

window.initAdminRichEditors = (root = document) => {
    root.querySelectorAll("[data-rich-editor]").forEach(initAdminRichEditor);
};

window.initAdminRichEditors();
