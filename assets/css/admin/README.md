# Admin CSS

`../admin.css` is the only stylesheet linked by the admin HTML pages. It is an
entrypoint that imports the files in this folder in cascade order.

## Folders

- `core`: global admin variables and base rules.
- `layout`: application shell, content wrappers, and responsive rules.
- `components`: reusable UI pieces shared by several admin pages.
- `pages`: rules tied to one admin screen or feature family.

When adding styles, prefer an existing component file first. Create a page file
only when the class belongs to one specific admin page.
