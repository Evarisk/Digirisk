Source: https://raw.githubusercontent.com/Evarisk/Saturne/develop/CLAUDE.md

---

Digirisk est un module Dolibarr ERP/CRM permettant de gérer les risques de votre entreprise et de créer votre Document Unique en toute simplicité. Il est basé sur le framework Saturne.

---

## 1. Architecture Philosophy
Saturne follows a strict separation of concerns:

- PHP controllers prepare data only
- TPL files render HTML only
- Business logic belongs to `class/`
- Reusable UI belongs to `core/tpl/`
- JavaScript is modular and event-driven
- Styling is component-scoped through `.mod-{module}`

The framework prioritizes:
- maintainability over cleverness
- consistency over flexibility
- backward compatibility with Dolibarr
- reusable patterns shared across all modules

---

## 2. AI Assistant Instructions
When generating code:

- Always reuse existing Saturne patterns before creating new ones
- Prefer extending generic Saturne components over module-specific implementations
- Keep generated diffs minimal
- Preserve existing comments and spacing
- Never reformat unrelated code
- Follow the surrounding file style first
- When uncertain, mimic the nearest reference file

---

## 3. Project Architecture
```
htdocs/custom/digiriskdolibarr/
├── admin/          # Admin config pages
├── class/          # PHP CRUD classes (SaturneObject, ActionsSaturne, …)
├── core/
│   ├── ajax/       # AJAX endpoints
│   ├── tpl/        # Reusable TPL fragments (banner_actions, medias, …)
│   └── triggers/   # Dolibarr event triggers
├── css/scss/       # SCSS source → compiled to css/saturne.min.css
├── js/modules/     # JS feature modules → compiled to js/saturne.min.js
├── lib/            # snake_case PHP utility functions
├── view/           # Generic views (saturne_list.php, saturne_document.php, …)
└── gulpfile.js     # Build config (child modules reference gulpfile-shared.js)
```

**Child module entry point** (`digiriskdolibarr.main.inc.php`):
```php
$moduleName = 'Digirisk';
$moduleNameLowerCase = strtolower($moduleName);
require_once __DIR__ . '/../saturne/saturne.main.inc.php';
```

**Class inheritance**:
```php
class DigiriskObject extends SaturneObject {
    public function __construct(DoliDB $db) {
        parent::__construct($db, 'digiriskdolibarr', 'digirisk_object');
    }
}
```

---

## 4. Anti-Patterns
Never:
- Put SQL in views
- Put HTML in classes
- Put business logic in TPL
- Use inline CSS or JS
- Call Dolibarr globals directly inside JS
- Duplicate generic components already existing in `saturne/`
- Create module-specific patterns when a shared Saturne pattern exists

**PHP Rendering**
```php
// BAD
echo '<script>alert()</script>';

// GOOD
saturne_header();
```

**JavaScript Event Binding**
```javascript
// BAD
$('.btn').click(function(){});

// GOOD
$(document).on('click', '.btn', handler);
```

---

## 5. PHP Conventions
**Style** — follow [PSR-12](https://www.php-fig.org/psr/psr-12/) for all PHP code (indentation, spacing, naming, braces, etc.).
PSR-12 is enforced via PHPCS — run `phpcs --standard=PSR12`. Config in `.phpcs.xml` at the module root.

**Comments** — place comments on the line **above** the code they document, never inline after it.
Remove obvious comments that only restate what the code does — a good comment explains **why**, not **what**.

**Blank lines** — keep blank lines between logical code sections; they are intentional and improve readability.

**Asset loading** — never use `<link>` or `<script>` manually:
```php
saturne_header(); // auto-loads saturne.min.css + saturne.min.js
                  // also loads digiriskdolibarr.min.css and digiriskdolibarr.min.js if they exist
```

**Variables before templates** — all logic runs before `require_once` of TPL:
```php
$title  = $langs->trans('MyPage');
$object = new MyObject($db);
$object->fetch($id);
saturne_header(0, '', $title, $help_url);
require_once __DIR__ . '/../../saturne/core/tpl/banner_actions.tpl.php';
```

**Security rules** — always:
```php
$id     = GETPOSTINT('id');           // never $_GET / $_POST
$label  = GETPOST('label', 'alpha');
$name   = dol_sanitize_filename($name);
$html   = dol_escape_htmltag($value);
$user->hasRight('digiriskdolibarr', 'write'); // check before any action
$db->escape($value);                  // escape SQL values
```

**Never**:
- `$langs->load()` manually — saturne loads lang files automatically
- Raw SQL queries — use `fetch()`, `fetchAll()`, Dolibarr ORM methods
- Modify any file outside `htdocs/custom/{module}/`

**Hooks** — action class returns `0` (continue) or `1` (replace):
```php
class ActionsDigirisk {
    public function printMainArea(array $parameters): int {
        if (strpos($parameters['context'], 'digiriskdolibarrcontext') !== false) {
            // custom output
        }
        return 0;
    }
}
```

**Reference files**:
- PHP action: `class/actions_saturne.class.php`
- TPL fragment: `core/tpl/actions/banner_actions.tpl.php`
- Generic view: `view/saturne_list.php`

---

## 6. JavaScript Conventions
**Namespace pattern** — literal object, no IIFE:
```javascript
window.saturne.modal = {};

window.saturne.modal.init = function() {
    window.saturne.modal.event();
};

window.saturne.modal.event = function() {
    $(document).on('click', '.modal-open', window.saturne.modal.openModal);
};

window.saturne.modal.openModal = function(event) { /* … */ };
```

**Rules**:
- Always implement `init()`, `event()`, and handler methods
- `saturne.js` calls `init()` on every `window.saturne.*` automatically via `$(document).ready` — **never call `init()` manually** at end of file
- Use jQuery — no Vanilla JS unless jQuery is unavailable
- **No inline JS in TPL files**

**Linting** — JSHint validates all JS files. Config in `.jshintrc` at the module root.

**Reference files**: `js/modules/modal.js`, `js/modules/button.js`, `js/modules/object.js`

---

## 7. SCSS Conventions
**Structure**:
```
css/scss/style.scss          ← entry point, imports all partials
css/scss/variable/_colors.scss
css/scss/modules/button/_button.scss
css/scss/page/_mypage.scss
```

**Rules**:
- Legacy Saturne partials may still use `@import`.
- New code should use `@use` / `@forward`.
- Do not refactor existing imports unless touching the file.
- Target Dolibarr overrides via the `mod-{element}` class injected by `saturne_header` on `<body>`:
  ```scss
  .mod-digiriskdolibarr .fichecenter { /* override, not a global selector */ }
  ```
- Partials named `_{name}.scss`, aggregated through `_{category}.scss`

**Reference files**: `css/scss/style.scss`, `css/scss/variable/_colors.scss`, `css/scss/modules/modal/_modal.scss`

---

## 8. Performance Rules
- Avoid N+1 fetch loops
- Prefer bulk fetch methods
- Never load unused JS modules
- AJAX endpoints must return minimal payloads
- Large lists must support pagination
- Avoid synchronous AJAX

---

## 9. Compatibility Rules
- Preserve existing hooks and public method signatures
- Avoid breaking database schema changes
- New constants must have migration safety
- UI changes must remain compatible with existing themes

---

### Lancer gulp en local
**Windows CMD :**
```cmd
set MODULE_NAME=saturne && node node_modules/gulp/bin/gulp.js --gulpfile gulpfile-shared.js
```

**Linux / macOS / PowerShell :**
```bash
npm start   # équivalent, défini dans package.json via cross-env
```

**Tâches disponibles :**

| Commande | Description |
|----------|-------------|
| `default` | Compile SCSS + JS puis lance le watch (dev) |
| `build` | Compilation prod one-shot, minifiée, sans sourcemaps |
| `scss_core` | SCSS dev uniquement (sourcemaps + minification) |
| `js_backend` | JS concat + uglify uniquement |

### Ce qui est compilé
| Fichier | Local | CI/prod |
|---------|-------|---------|
| `css/saturne.min.css` | ✅ gulp (avec sourcemaps) | ✅ gulp `build` (sans sourcemaps) |
| `js/saturne.min.js` | ✅ gulp | ✅ gulp `build` |
| `css/saturne.min.css.map` | ✅ gulp (debug local) | ❌ jamais généré ni commité |

### Git et assets compilés
- La CI (`build-assets.yml`) compile et commite `.min.css` / `.min.js` automatiquement sur push vers `main` ou `develop` — **ne jamais les commiter manuellement**
- Le sourcemap `.map` est dans `.gitignore` — uniquement utile en local pour le debug DevTools
- En local, Git ignore les modifications sur les `.min` grâce à `assume-unchanged` :
  ```bash
  git update-index --assume-unchanged css/saturne.min.css
  git update-index --assume-unchanged js/saturne.min.js
  ```
- Use `npm ci` in CI (reproducible installs from lock file), `npm install` locally

---

## 11. Quality Tooling
| Tool | Purpose | Config file | Enforced in CI |
|------|---------|-------------|----------------|
| **PHPCS** | PHP style enforcement (PSR-12) | `.phpcs.xml` | ✓ (blocks build) |
| **phpcbf** | Auto-fix PSR-12 violations | `.phpcs.xml` | — (local only) |
| **JSHint** | JS validation | `.jshintrc` | ✓ (blocks build) |
| **PHPStan** | Static analysis — max level | `phpstan.neon` | ✓ (quality job) |
| **Phan** | Deep static analysis | `.phan/config.php` | ✓ (quality job) |
| **PHPUnit** | Unit tests | `tests/phpunit/phpunittest.xml` | ✓ (quality job) |
| **EditorConfig** | Indentation, charset, line endings consistent across all editors | `.editorconfig` | — (editor-side) |

PHPCS and JSHint run **before** compilation in CI (`build-assets-reusable.yml` — `lint` job must pass before `build` job starts).
PHPStan, Phan, and PHPUnit run in a separate `quality.yml` workflow, triggered on push/PR to `main` and `develop`.

**Indentation** — this project uses **spaces** (PSR-12, 4 spaces), unlike Dolibarr core which uses tabs. Never mix the two.

Run locally:
```bash
# PHPCS — check
~/.composer/vendor/bin/phpcs --standard=.phpcs.xml --extensions=php --ignore=vendor,node_modules,css,js .

# phpcbf — auto-fix (run before committing)
~/.composer/vendor/bin/phpcbf --standard=.phpcs.xml .

# JSHint
jshint js/modules/*.js

# PHPStan (0 errors when baseline is current)
vendor/bin/phpstan analyse --memory-limit=512M

# PHPUnit
vendor/bin/phpunit --configuration tests/phpunit/phpunittest.xml --testdox

# Phan — requires php-ast; runs in CI (PHP 8.1) only
# vendor/bin/phan --config-file=.phan/config.php
```

**PHPStan baseline** — `phpstan.baseline.neon` suppresses pre-existing errors.
When you fix a baselined error, regenerate it:
```bash
vendor/bin/phpstan analyse --memory-limit=512M --generate-baseline phpstan.baseline.neon
```

**PHPUnit bootstrap** — `tests/phpunit/bootstrap.php` is stub-only (no Dolibarr DB).
Tests that load `saturne_functions.lib.php` require `DOL_DOCUMENT_ROOT` to point to a Dolibarr `htdocs/` directory (available locally and in CI via sparse checkout).

EditorConfig is picked up automatically by most editors (VSCode, PhpStorm, etc.) — install the plugin if prompted.

---

## 12. Git Conventions
**Branch**: `{type}/{issue-number}-{short-description}`
→ `fix/503-mail-eventpro`, `feat/478-menu-reorder`

**Never commit directly to `main` or `develop`.** Dev branch: `develop`. PR required with ≥1 reviewer.

**One issue = one branch = one PR.** Never mix multiple issues in a single branch or PR.

**Commit format**: `#{issue} [{Scope}] {type}: {short description}`

| Type | Usage |
|------|-------|
| `feat` / `add` | New feature |
| `fix` | Bug fix |
| `rework` | Refactor/rework |
| `chore` / `ci` | Build, CI, config |
| `docs` / `style` | Docs, formatting |

**Scope**: business element if broad (`Projet`, `EventPro`), technical category if focused (`JS`, `SCSS`, `CI`).

```
#503 [EventPro] fix: returnurl construction before tpl include
#478 [Menu] rework: reorder left menu entries
#1305 [JS] add: counter for all maxlength fields
```

**Issue labels**:
- **Story points** — add a Fibonacci label to every issue: `0`, `1`, `2`, `3`, `5`, `8`, `13`, `21`
- **PWA** — add the `PWA` label to issues related to the Progressive Web App feature

---

## 13. Reference Files
These files are the most complete and representative examples in the codebase. Use them as templates when creating new files of the same type.

---

### PHP — Action / Hook Class
**`class/actions_saturne.class.php`** (513 lines)

The canonical example of a Dolibarr hook class. Study this file to understand:

- How to structure the hook class: `printMainArea`, `addHtmlHeader`, `llxHeader`, `printCommonFooter`, `doActions`, `emailElementlist`, `getElementProperties`
- Return convention: `return 0` to let Dolibarr continue processing, `return 1` to replace it
- How to accumulate HTML output into `$this->resprints` before returning
- How to return structured data via `$this->results` (used for array-type hook responses)
- How to guard each hook method with a context check (`strpos($parameters['context'], '...')`)

```php
// Skeleton pattern from actions_saturne.class.php
public function printMainArea(array $parameters): int {
    if (strpos($parameters['context'], 'digiriskdolibarrcontext') !== false) {
        $this->resprints = '<div>…HTML output…</div>';
        return 1; // replace default rendering
    }
    return 0; // continue
}
```

---

### PHP — View Page
**`view/saturne_list.php`** (247 lines)

The reference implementation of a generic list view. Follow this exact sequence:

1. `saturne_check_access()` — security gate (permissions, module enabled)
2. `saturne_get_objects_metadata()` — loads object definitions
3. `GETPOSTINT()` / `GETPOST()` — read request parameters (never `$_GET`/`$_POST`)
4. `$user->hasRight()` — check write permission before any action
5. `saturne_header(0, '', $title, $help_url)` — renders `<html>` + loads CSS/JS
6. Sequential `require_once` of TPL fragments (list_build, list_header, list_search, list_loop, list_footer)

This file also shows how `$hookmanager->executeHooks()` is called in a view context, and how the body class (`mod-{module}`) is injected by `saturne_header` for SCSS scoping.

---

### TPL — Admin Config Fragment
**`core/tpl/admin/object/object_const_view.tpl.php`**

Best example of an admin-facing TPL. Demonstrates:

- The mandatory comment block at the top listing all expected global variables (`$object`, `$user`, `$langs`, …)
- `$hookmanager->executeHooks('saturneAdminObjectConst', …)` — the standard hook call inside a TPL
- `ajax_constantonoff()` helper for toggle switches on admin config pages
- How to use `$conf->global->MODULE_CONST` to read/write module config values

---

### TPL — Action Fragment
**`core/tpl/actions/banner_actions.tpl.php`**

Minimal but canonical TPL. Use it to understand:

- The expected comment-header format declaring every global variable the TPL relies on
- How variables must be fully prepared in the calling `.php` file before the TPL is included (zero business logic inside a TPL)
- The file naming convention: `{category}/{subcategory}/snake_case_name.tpl.php`

---

### JavaScript — Rich Module (AJAX + DOM)
**`js/modules/object.js`** (155 lines)

The most complete JS module in the codebase. Use it as the gold standard for:

- The three-method skeleton: `init()` → `event()` → named handler functions
- Delegated event binding: `$(document).on('click', '.selector', handler)` (never direct `.click()`)
- `getFields()` — how to collect form field values into a data object
- `$.ajax({url, data, success})` pattern with a named success callback (`reloadListSuccess`)
- `ObjectFromModal()` — how to open a modal and react to its result
- jQuery-only rule: no `document.querySelector`, no `addEventListener`

```javascript
window.saturne.object = {};

window.saturne.object.init = function() {
    window.saturne.object.event();
};

window.saturne.object.event = function() {
    $(document).on('click', '.object-save', window.saturne.object.save);
};

window.saturne.object.save = function(event) {
    var fields = window.saturne.object.getFields();
    // $.ajax(…)
};
```

`saturne.js` auto-calls `window.saturne.object.init()` on `$(document).ready` — **never add an `init()` call at the bottom of the file**.

---

### JavaScript — Event-Driven Module (data-* attributes)
**`js/modules/modal.js`** (168 lines)

Complements `object.js` by showing the event-only pattern (no AJAX). Study it for:

- Reading configuration from `data-*` attributes on a `.modal-options` element: `$('.modal-options').data('url')`, `$('.modal-options').data('type')`
- `openModal` / `closeModal` — how to toggle CSS classes and manage the `modal-active` state
- `refreshModal` — reloading modal content dynamically
- `loadLazyImages()` — deferred image loading triggered on modal open

---

### SCSS — Component Partial (state + responsive)
**`css/scss/modules/modal/_modal.scss`** (146 lines)

Reference for a full component partial:

- BEM-like nesting with `&` for element and modifier: `.wpeo-modal { &.modal-active { … } .modal-container { … } }`
- State modifier class pattern: `.modal-active` added/removed by JS to trigger CSS transitions
- Media query with the `$media__small` variable from `_sizes.scss`
- Color variables from `_colors.scss` (`$color__primary`, `$color__white`, etc.)
- Imports a sub-partial at the bottom: `@import "modal-flex"` — one partial per layout concern

---

### SCSS — Utility Partial (modifier class system)
**`css/scss/modules/button/_button.scss`** (206 lines)

Shows the modifier-class architecture used across all Saturne components:

- Base class `.wpeo-button` with default styles
- Modifier classes: `.button-blue`, `.button-grey`, `.button-red`, `.button-pill`, `.button-square`, etc.
- Color imports via `@import "colors"` at the top
- Sub-partial import at the bottom: `@import "button-add"` for the FAB/add-button variant
- How Dolibarr-specific overrides are scoped inside `.mod-{module}` to avoid polluting global styles

---

## 14. Pitfalls
- **Zero files outside `htdocs/custom/{module}/`** — never touch Dolibarr core
- **Don't copy `gulpfile.js`** into each module — use `gulpfile-shared.js`
- **Test install/uninstall** on a clean Dolibarr instance before opening a PR
- **`.min` files are auto-generated** — conflicts on them = recompile, don't hand-merge
- `$moduleNameLowerCase` must be set before `saturne.main.inc.php` is required

---

## 15. Release Process
See `docs/MEMO_RELEASE.md` for the full release workflow.

Short prompt to generate release notes:
```bash
claude "Generate release notes for version X.X.X based on git log since tag X.X.X. Use RELEASE_NOTES_TEMPLATE.md as format reference. Write in French, group by functional category, add screenshot placeholders for visual features. Save to RELEASE_NOTES.md"
```

