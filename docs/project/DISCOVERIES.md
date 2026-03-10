# DISCOVERIES.md

## 2026-03-08 - Milestone 0 Research

### D-001 - Native plugin packaging patterns are stable and straightforward

- Mature Unraid plugins (for example `unassigned.devices` and `community.applications`) use:
  - `.plg` metadata + install/update/remove scripts
  - plugin files deployed into `/usr/local/emhttp/plugins/<plugin-name>/`
  - persistent config in `/boot/config/plugins/<plugin-name>/`
- `.page` files register UI entries via header keys such as `Menu`, `Title`, `Tag`, `Type`.
- Practical implication: this project should start with conventional `.plg` + `.page` + PHP include structure, not a separate app runtime.

### D-002 - dockerMan paths relevant to template lifecycle management

- Community Applications path definitions show dockerMan-related paths in active use:
  - `/boot/config/plugins/dockerMan/templates-user`
  - `/boot/config/plugins/dockerMan/templates`
  - `/boot/config/plugins/dockerMan/template-repos`
  - `/boot/config/docker.cfg`
- Practical implication: templates-user remains primary source-of-truth for user templates; plugin should support read-only operation if path is absent.

### D-003 - Template XML shape and parsing concerns

- Unraid Docker templates use `Container` root and multiple `Config` nodes with attributes such as:
  - `Name`, `Target`, `Default`, `Mode`, `Type`, `Display`, `Required`, `Mask`
- Community Applications changelog/history shows repeated issues caused by malformed XML and duplicate tags.
- Practical implication: parser must isolate per-file failures and report parse errors without breaking the whole page.

### D-004 - Container/template mapping is heuristic by nature

- Reference implementation uses multi-step matching:
  - exact name
  - stripped prefix variant
  - case-insensitive fallback
- Community Applications also relies on container info + template associations and compensates for inconsistent naming.
- Practical implication: start with deterministic name matching and expose reasons/confidence rather than hiding ambiguity.

### D-005 - Reference implementation feature inventory (Qballjos/docker-template-manager)

- Implemented feature areas include:
  - dashboard stats
  - template list/edit/rename/clone/delete
  - cleanup dry-run and apply
  - container list/inspect and lifecycle controls
  - backups with metadata and restore
  - container inspect snapshot export inside backup set
  - update check endpoint
- Safety patterns observed:
  - filename and backup-name validation
  - path traversal protections (`safe_join`)
  - backup-before-delete and backup-before-edit
- Recovery docs include migration guidance both directions:
  - vDisk (`docker.img`) -> directory mode
  - directory mode -> vDisk

### D-006 - docker.img vs directory mode is an active operational concern

- Unraid docs/release notes and troubleshooting docs emphasize:
  - default historical `docker.img` usage
  - optional directory mode introduced/expanded for Docker data-root
  - user workflows that rely on templates surviving Docker rebuild/reinstall
- Practical implication: recovery and diagnostics pages should include storage-mode awareness and pre-change backup guidance.

### D-007 - Missing context file issue resolved during bootstrap

- Initial repository state appeared to lack `PROJECT_CONTEXT.md` during first scan; file is now present and loaded.
- Practical implication: bootstrap check should explicitly validate required governance/context files before implementation tasks.

### D-008 - Minimal native inventory can be delivered without coupling to dockerMan internals

- Implemented `TemplateInventoryService` with direct filesystem scan of templates-user directory and `simplexml_load_string` parsing under libxml internal error mode.
- Malformed files are isolated and surfaced as row-level errors rather than hard failures.
- Practical implication: milestone 2 functionality is achievable early with stable primitives and no risky internals.

### D-009 - Packaging is scaffolded but release artifact wiring is pending

- `.plg` now exists and follows native structure, but package URL/MD5 placeholders still require release pipeline integration.
- Practical implication: installation workflow is structurally present but needs packaging task completion in later milestone.

### D-010 - Session continuity script added for long autonomous runs

- Added `scripts/resume-context.sh` to print git state, task state, and milestone summary.
- Practical implication: if token/runtime limits interrupt work, next session can resume quickly and consistently.

### D-011 - Deterministic matching baseline works as a low-risk first pass

- Added `ContainerInventoryService` + `TemplateMatcher` with transparent strategy order:
  - exact template name -> container name
  - name after stripping `my-`
  - filename-derived fallback
  - image-equality heuristic
- Added explicit `match_reason` and `mapping_status` fields for each template row.
- Practical implication: users can see why mapping happened and where uncertainty remains; this supports safer diagnostics before write features.

### D-012 - Duplicate and severity diagnostics are useful before any write actions

- Added duplicate name and shared-image diagnostics plus a severity model (`info`, `warning`, `error`) in `TemplateDiagnosticsService`.
- Practical implication: operators can prioritize cleanup targets before destructive features are enabled.

### D-013 - Backup primitive can be added independently of UI actions

- Added `BackupService` capable of creating timestamped backup sets with metadata and strict filename validation.
- Practical implication: backup safety enforcement can be centralized and reused across delete/clone/restore operations.

### D-014 - Backup-before-delete can be introduced as a narrow, safe first write action

- Added `TemplateActionService::deleteTemplate()` with strict filename validation and guaranteed pre-delete backup.
- Added native AJAX endpoint `ajax/delete_template.php` and UI action button with explicit confirmation.
- Practical implication: destructive operations can be shipped incrementally while keeping mandatory backup guarantees.

### D-015 - Clone + restore-preview can be staged via AJAX endpoints before full UI polish

- Added clone endpoint with validation and pre-action backup.
- Added backup list, restore preview, and restore apply endpoints with overwrite flag support.
- Practical implication: backend safety model can mature before full frontend workflow polish.

### D-016 - Docker storage mode can be detected from `docker.cfg` without direct daemon coupling

- Added `StorageModeService` that reads `/boot/config/docker.cfg` and infers mode from docker image/data-root values.
- Practical implication: recovery guidance can be shown even when Docker CLI is unavailable.

### D-017 - Package build can be scripted from plugin tree directly

- Added `scripts/build-package.sh` to archive `source/plugin/usr` into `source/packages/<name>-<version>.tgz`.
- Script computes MD5 and updates `.plg` md5 entity automatically.
- Practical implication: repeatable package generation without manual checksum edits.

### D-018 - Blank plugin pages need explicit runtime error rendering

- Added `try/catch (\Throwable)` to `UnraidTemplateManager.page` with on-page error details and `error_log` fallback.
- Replaced PHP 8-only `str_contains` / `str_ends_with` usages with compatibility-safe `strpos` / `substr` checks.
- Practical implication: on older PHP runtimes the plugin now fails visibly instead of rendering a blank page.

### D-019 - Package extraction target must match archive root

- Plugin package archive is rooted at `usr/...`, so post-install extraction must target `/`, not `/usr/local/emhttp/plugins`.
- Updated `.plg` post-install script to `tar -xzf ... -C /` and verify `UnraidTemplateManager.page` exists after extraction.
- Practical implication: prevents silent nested-path installs that lead to blank plugin pages.

### D-020 - Dynamic `.page` HTML replacement does not execute injected scripts automatically

- Replacing `.outerHTML` with fetched renderer output can leave `<script src=...>` tags inert in Unraid WebGUI context.
- Added explicit script extraction/reinjection in `UnraidTemplateManager.page`.
- Practical implication: frontend behaviors (filters/actions) now initialize reliably after async page load.

### D-021 - Docker CLI calls need bounded runtime in the render path

- `docker ps -a` in page render flow can block UI load for long periods when Docker is slow/unavailable.
- Added command timeout (2.5s) and cache fallback for container inventory.
- Practical implication: page load latency is capped and stale container data can still be shown safely.

### D-022 - Bulk lifecycle operations require checkbox-driven UX and shared backend primitives

- Added row selection, select-all, and bulk delete endpoint backed by `TemplateActionService::deleteTemplates()`.
- Backup-before-delete remains mandatory for both single and bulk operations.
- Practical implication: large cleanup workflows are now practical without weakening safety guarantees.

### D-023 - Import/export workflow is viable with archive staging and XML validation

- Added template export (all/selected) to `.tgz` and import support for `.xml`, `.tgz`, `.tar.gz`, `.tar`.
- Imports validate XML before writing and backup overwrite targets when overwrite mode is enabled.
- Practical implication: templates can be migrated between hosts with safety-first behavior.

### D-024 - Restore endpoints were present but needed first-class UI wiring

- Existing list/preview/restore endpoints are now exposed via UI controls.
- Practical implication: backup recovery is now accessible directly from the plugin page instead of endpoint-only workflows.

### D-025 - Storage mode switch can be implemented as docker.cfg mutation with config backup

- Added `StorageModeService::switchMode()` to update `docker.cfg`, normalize `DOCKER_OPTS`, and create backup.
- Optional Docker restart support was added to apply changes immediately.
- Practical implication: mode switching is now possible in-plugin, with explicit safety prompts and backup trail.

### D-026 - Some Unraid PHP runtimes cannot use `proc_open` for command execution

- Initial timeout implementation relied on `proc_open`, which can be disabled by runtime policy.
- Added `exec`-based fallback with optional `/usr/bin/timeout` wrapping.
- Practical implication: container inventory mapping works across more Unraid runtime configurations while keeping timeout protection where possible.

### D-027 - Prefer `timeout + exec` over `proc_open` for Docker inventory on Unraid hosts

- Runtime behavior showed `proc_open` path timing out while direct shell `timeout 3 docker ps -a ...` returned quickly.
- Updated command strategy to prefer timeout-binary execution first, then fallback to `proc_open` or plain `exec`.
- Practical implication: inventory collection better matches observed host behavior and restores mapping reliability.

### D-028 - Full Docker row formatting can timeout while names-only still succeeds

- On host validation, full inventory command timed out around 3s in PHP context.
- Added names-only fallback (`docker ps -a --format '{{.Names}}'`) when primary inventory times out.
- Practical implication: mapping can still work even when full container metadata is slow to fetch.

### D-029 - Delete workflows need a visible, itemized confirmation step

- Replaced `window.confirm` delete prompts with a native modal.
- Modal now lists the exact template filename(s) being deleted in a vertical list.
- Practical implication: operators can verify targets before destructive actions, reducing accidental bulk deletions.

### D-030 - Splitting dense controls into tabs improves operational clarity

- Introduced tabbed sections for Templates, Settings, and Tools.
- Moved runtime/storage controls into Settings and backup/export/import/restore into Tools.
- Practical implication: page is less cluttered, and high-risk actions are grouped in clearer contexts.

### D-031 - Empty AJAX responses must be treated as endpoint failures, not silent no-ops

- Host HAR showed delete endpoints returning HTTP 200 with empty body and default `text/html`.
- Hardened delete/bulk-delete endpoints to:
  - load dependencies inside guarded `try` blocks
  - always emit JSON responses
  - log throwable details for server-side diagnosis
- Frontend `postJson` now fails explicitly on empty/invalid JSON responses.
- Practical implication: delete failures are now visible and diagnosable instead of appearing as no-op clicks.

### D-032 - Unraid webGUI requires `csrf_token` on protected POST endpoints

- Syslog confirmed delete and bulk-delete were blocked before endpoint execution due to missing `csrf_token`.
- Added CSRF token propagation in frontend POST requests (including multipart import flow).
- Practical implication: write actions now satisfy Unraid webGUI CSRF enforcement and execute normally.

### D-033 - Operators need export convenience at row level and backup download for recovery workflows

- Added per-template Export action directly in table row actions.
- Added `download_backup.php` endpoint and Tools-tab download button for selected backup set.
- Practical implication: faster single-template portability and direct retrieval of historical backup sets.

### D-034 - High-risk storage mode changes need stronger UX safeguards

- Added an explicit warning in the Settings-tab storage switch form that mode switching is not fully validated in production.
- Replaced browser-native confirm with the plugin confirmation modal for storage switching, with itemized mode/path/restart details and backup note.
- Practical implication: operators get clearer risk context and a more auditable confirmation step before modifying `docker.cfg`.

## Sources Consulted

- https://github.com/Qballjos/docker-template-manager
- Local clone inspection of `app.py`, `readme.md`, and docs in that repository
- https://github.com/Squidly271/community.applications
- https://github.com/dlandon/unassigned.devices
- https://docs.unraid.net/unraid-os/using-unraid-to/troubleshooting/reinstalling-docker-applications/
- https://docs.unraid.net/unraid-os/release-notes/6.12.0/
- https://docs.unraid.net/unraid-os/release-notes/6.9.0/
