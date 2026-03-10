# PROGRESS.md

## 2026-03-09

### Session Start

- User-confirmed plugin now loads on Unraid host.
- New issues identified for Milestone 7:
  - page load latency around 21 seconds
  - row filter behavior not working in deployed page
  - missing bulk actions and import/export workflows
  - missing Docker storage mode switch operation workflow

### Milestone Status

- Milestone 0: completed
- Milestone 1: completed
- Milestone 2: completed
- Milestone 3: completed
- Milestone 4: completed
- Milestone 5: completed
- Milestone 6: completed
- Milestone 7: in progress

### In Progress

- M7-T09: execute in-Unraid validation pass for Milestone 7 features

### Next Tasks

- Validate page load latency reduction and filter behavior on target host
- Validate bulk delete + backup flows and export/import operations
- Validate storage-mode switch workflow (config backup and optional restart)

### Recently Completed

- Added Docker inventory timeout + cache fallback to avoid long page stalls.
- Fixed `.page` dynamic loader to execute injected scripts reliably.
- Reworked UI:
  - multi-filter toolbar
  - stacked status badges
  - row select + select-all
  - backup all/selected
  - export all/selected
  - import form with overwrite option
- Added bulk delete endpoint with backup-before-delete safety.
- Added restore workflow UI over existing list/preview/restore endpoints.
- Added Docker storage mode switch endpoint with docker.cfg backup.
- Bumped plugin package to `0.2.0`.
- Ran local smoke checks for:
  - renderer output generation
  - `TemplateTransferService` export/import archive flow
  - `StorageModeService` guarded config switch flow
- Adjusted Docker inventory execution strategy to prefer `timeout + exec` path after host feedback that `proc_open` path timed out.
- Added names-only Docker inventory fallback when full inventory format times out, to keep template mapping available.
- Added delete confirmation modal for single and bulk delete with explicit template list and backup-before-delete notice.
- Reorganized interface into tabs:
  - Templates: filters, selection, table, delete
  - Settings: runtime status and storage mode switch
  - Tools: backup/export/import/restore operations
- Hardened delete and bulk-delete AJAX error handling:
  - endpoint includes moved inside guarded execution path
  - explicit JSON response fallback + server-side error logging
  - frontend now surfaces empty/invalid JSON responses
- Added automatic CSRF token injection for protected POST requests after host syslog showed `missing csrf_token` rejections.
- Added per-row template Export button in Templates tab.
- Added Tools-tab backup download action for selected backup set.
- Added an explicit Settings-tab warning that storage mode switching is not fully validated in production.
- Replaced storage mode switch `window.confirm` with native modal confirmation that lists target mode/path/restart and backup behavior.

## 2026-03-08

### Session Start

- Bootstrap executed per `REPO_BOOTSTRAP.md`
- Governance files loaded: `AGENTS.md`, `PROJECT_CONTEXT.md`, `REPO_BOOTSTRAP.md`
- Planning system initialized under `/docs/project/`

### Milestone Status

- Milestone 0: completed
- Milestone 1: completed
- Milestone 2: completed
- Milestone 3: completed
- Milestone 4: completed
- Milestone 5: completed
- Milestone 6: in progress

### Recently Completed

- Completed research pass covering:
  - Unraid plugin structure patterns (`.plg`, `.page`, plugin file layout)
  - dockerMan template storage and related paths
  - template XML structure examples and safety implications
  - docker storage mode context (`docker.img` vs directory mode)
  - reference implementation feature/workflow/edge-case extraction
- Implemented native plugin skeleton:
  - `source/unraid.template.manager.plg`
  - native page registration (`UnraidTemplateManager.page`)
  - plugin scaffold under `source/plugin/usr/local/emhttp/plugins/unraid.template.manager/`
  - config path initialization helper (`PluginPaths`)
- Implemented first read-only template inventory:
  - safe XML scanning and per-file parse isolation
  - table rendering with valid/invalid status
  - lightweight search/filter behavior
- Implemented Milestone 3 baseline mapping:
  - container inventory via Docker CLI (`docker ps -a --format '{{json .}}'`)
  - deterministic template matching heuristics with explicit reason strings
  - orphaned template detection and unmatched-container panel
- Added diagnostics model:
  - duplicate name/image detection
  - severity model (`info`, `warning`, `error`)
  - per-template issue summaries
- Added backup service scaffold:
  - timestamped backup-set creation
  - filename validation and metadata persistence
- Implemented first safe write action:
  - backup-before-delete template flow
  - native AJAX endpoint (`ajax/delete_template.php`)
  - confirmation + reload UI behavior
- Implemented clone and restore-preview baseline:
  - clone action endpoint with validation and pre-action backup
  - backup listing endpoint
  - restore preview and restore-apply endpoints with overwrite control
- Implemented recovery checks baseline:
  - docker storage mode detection (`vdisk` vs `directory` vs `unknown`)
  - guidance surfaced in page panel from `/boot/config/docker.cfg`
- Implemented packaging workflow baseline:
  - `scripts/build-package.sh` creates `source/packages/unraid.template.manager-0.1.0.tgz`
  - package MD5 auto-synced into `.plg` entity
- Completed internal plugin ID migration:
  - `docker.template.manager` -> `unraid.template.manager`
  - plugin page renamed to `UnraidTemplateManager.page`
  - `.plg` renamed to `source/unraid.template.manager.plg`
  - GitHub raw URL set to `rob1998/unraid-template-manager`
- Added runtime resilience fixes for blank-page debugging:
  - on-page throwable rendering in `UnraidTemplateManager.page`
  - PHP-compatibility replacements for `str_contains`/`str_ends_with`
- Fixed installer extraction path:
  - `.tgz` now extracts to `/` (archive root) instead of `/usr/local/emhttp/plugins`
  - post-install now verifies `UnraidTemplateManager.page` exists
- Added session continuation helper script for low-token handoff (`scripts/resume-context.sh`)

### In Progress

- M6-T03: in-Unraid installation smoke test

### Next Tasks

- Perform in-Unraid installation smoke test
- Capture install verification notes after smoke test

### Blockers

- None currently
