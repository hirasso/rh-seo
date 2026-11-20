# AGENTS.md

Lightweight SEO plugin for WordPress (requires ACF Pro). Setup, commands and releases: `README.md`. This file only adds what agents need beyond it.

## Code style

- derive from the existing code
- prefix getters with `get`; methods that request data from a remote API may use `fetch`
- names must reflect what code does; if a change alters a class/function's behavior, check its name still fits; propose renames that touch call sites
- don't rename untouched code
- keep comments as short as possible; no comments if not really required
- `/** */` block comments (not `//`) for anything that's not an end-of-line comment
- docblocks state _what_ a thing does, never why it was built that way; no `{@see}`; skip if the name is self-explanatory; max 3 lines

## Before making changes

- if the change is big, first propose what you are planning, even if not in plan mode

## After making changes

- Close GitHub issues that are solved by the current edit via `Fixes #<n>` in the commit message (see #issue-tracker)

### PHP

- Must stay compatible with `Requires PHP` in `rh-seo.php`
- Classes in `CamelCase`, methods in `snake_case` (`UpdateChecker` is the camelCase exception; match the class you're in)
- Prefer WordPress-native functionality over reimplementing it (e.g. `list_files()` over `glob()`)
- Keep new code DRY. Propose deduplicating existing code

### Assets

- `assets/` is built from `assets-src/` and committed (Composer installs straight from the repo). Never edit `assets/` by hand; the pre-commit hook rebuilds it

## Do not touch without explicit request

- Release tooling (`release.sh`, `.github/workflows/`)
- `lib/plugin-update-checker/` (third-party, copied as-is)
- do not edit TODO files

## Commits and pull requests

- Keep PR descriptions as brief as possible. Title carries the meaning; empty body or a few bullets
- Never mention the agent that wrote the code. No `Co-Authored-By` trailers

## Project structure

| Path | Purpose |
|------|---------|
| `rh-seo.php` | Plugin bootstrap |
| `lib/rah/` | PHP classes (`RAH\SEO\` namespace) |
| `lib/vendor/` | Composer autoloader (committed) |
| `lib/plugin-update-checker/` | Bundled plugin-update-checker |
| `assets-src/` | JS/SCSS source, built with esbuild (`build.mjs`) |
| `assets/` | Built assets (committed) |
| `lang/` | Translations, `.pot` generator |
| `release.sh` | Release script |

## Agent skills

### Issue tracker

Issues and PRDs live as GitHub issues (`gh issue …`). They close via `Fixes #<n>` in the commit message when pushed to `main`; never `gh issue close`.

### Triage labels

Canonical vocabulary (`needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human`, `wontfix`), as GitHub labels.
