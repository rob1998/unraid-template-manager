# TASKS.md

## Task Board

Status values: `todo`, `in_progress`, `blocked`, `done`, `deferred`, `research`, `needs_decision`

## Milestone 0 - Research and Architecture Confirmation

| ID | Task | Status | Notes |
| --- | --- | --- | --- |
| M0-T01 | Execute bootstrap and initialize planning system | done | `/docs/project` created and synchronized |
| M0-T02 | Research Unraid plugin architecture patterns | done | Based on mature plugin `.plg` and `.page` examples |
| M0-T03 | Research dockerMan template storage and XML behavior | done | Confirmed templates-user path and XML patterns |
| M0-T04 | Analyze Qballjos reference implementation for features/workflows/edge cases | done | Feature inventory and logic notes captured in discoveries |
| M0-T05 | Produce initial architecture + milestones + risks plan | done | `MASTER_PLAN.md` and `DECISIONS.md` aligned |

## Milestone 1 - Native Plugin Skeleton

| ID | Task | Status | Notes |
| --- | --- | --- | --- |
| M1-T01 | Create `.plg` installer skeleton | done | `source/unraid.template.manager.plg` created |
| M1-T02 | Create plugin directory scaffold under `source/plugin/...` | done | Base layout + include/js/css/readme created |
| M1-T03 | Register initial native Unraid page | done | `UnraidTemplateManager.page` implemented |
| M1-T04 | Implement basic plugin config-path handling | done | `PluginPaths::ensureConfigDirectory()` implemented |

## Milestone 2 - Template Inventory

| ID | Task | Status | Notes |
| --- | --- | --- | --- |
| M2-T01 | Implement safe template directory scanning | done | `TemplateInventoryService::listTemplates()` |
| M2-T02 | Implement robust XML parsing with malformed isolation | done | XML errors isolated per-file with error reporting |
| M2-T03 | Render initial inventory table with status badges | done | `PageRenderer` table with `valid/invalid` badges |
| M2-T04 | Add filter/search baseline | done | Client-side row filter in `javascript/app.js` |

## Backlog / Future Milestones

| ID | Task | Status | Notes |
| --- | --- | --- | --- |
| M3-T01 | Implement template/container matching heuristics | done | Added deterministic rules + per-row match reasons |
| M3-T02 | Add duplicate-template diagnostics | done | Duplicate name/image diagnostics added |
| M3-T03 | Add severity model for findings (info/warn/error) | done | `TemplateDiagnosticsService` implemented |
| M4-T00 | Add backup service scaffold | done | `BackupService` with metadata + filename validation |
| M4-T01 | Implement backup-before-delete flow | done | AJAX delete action performs mandatory pre-delete backup |
| M4-T02 | Add clone and restore-preview operations | done | Clone + preview/restore endpoints with overwrite control |
| M5-T01 | Implement docker storage mode detection | done | `StorageModeService` + UI guidance panel |
| M6-T01 | Packaging and release workflow | done | `scripts/build-package.sh` builds artifact + updates MD5 |
| M6-T02 | Finalize release URL metadata in `.plg` | done | Updated to `rob1998/unraid-template-manager` |
| M6-T03 | Perform in-Unraid installation smoke test | done | Install/load verified by user; follow-up performance and UI bugs moved to Milestone 7 |
| M7-T01 | Fix runtime performance bottlenecks and slow page load | done | Added Docker command timeout + cache fallback and loader script execution fix |
| M7-T02 | Replace single search with multi-filter toolbar (text + select filters) | done | Added text search + template/mapping/severity dropdown filters |
| M7-T03 | Add row checkboxes, select-all, and bulk backup+delete action | done | Added row selectors, select-all, and bulk delete endpoint |
| M7-T04 | Restructure table to combine status columns into stacked badges | done | Statuses now stacked in one summary cell |
| M7-T05 | Add backup-all action and UX polish for destructive actions | done | Added backup all/selected; single-row button label changed to Delete |
| M7-T06 | Implement Docker storage mode switch workflow | done | Added switch endpoint and docker.cfg backup-first update flow |
| M7-T07 | Add template export and import workflows | done | Added export (all/selected) and import (.xml/.tgz/.tar.gz/.tar) |
| M7-T08 | Scan and implement additional parity gaps from reference workflows | done | Added restore workflow UI wiring (list, preview, restore apply) |
| M7-T10 | Add explicit delete confirmation modal with itemized list | done | Single and bulk delete now use modal listing templates and backup warning |
| M7-T11 | Reorganize UI into tabbed layout (Templates/Settings/Tools) | done | Status/settings split from tools for cleaner structure |
| M7-T12 | Add per-template export action in templates table | done | Added row-level Export button and frontend wiring |
| M7-T13 | Add download action for existing backup sets | done | Added backup archive download endpoint and Tools-tab button |
| M7-T14 | Add explicit warning + modal confirmation for storage mode switch | done | Settings tab now warns untested status and uses itemized confirmation modal |
| M7-T09 | Execute in-Unraid validation pass for Milestone 7 features | in_progress | Pending user runtime verification on target host |

## Session Follow-Up Automation Safety

| ID | Task | Status | Notes |
| --- | --- | --- | --- |
| OPS-T01 | Add resume script for low-token continuation handoff | done | `scripts/resume-context.sh` |
