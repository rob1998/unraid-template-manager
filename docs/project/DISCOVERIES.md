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

## Sources Consulted

- https://github.com/Qballjos/docker-template-manager
- Local clone inspection of `app.py`, `readme.md`, and docs in that repository
- https://github.com/Squidly271/community.applications
- https://github.com/dlandon/unassigned.devices
- https://docs.unraid.net/unraid-os/using-unraid-to/troubleshooting/reinstalling-docker-applications/
- https://docs.unraid.net/unraid-os/release-notes/6.12.0/
- https://docs.unraid.net/unraid-os/release-notes/6.9.0/
