# MASTER_PLAN.md

## Project Goal

Build a native Unraid plugin named `unraid.template.manager` that manages the lifecycle of Docker template XML files in `/boot/config/plugins/dockerMan/templates-user` with safety-first diagnostics, backup, restore, and cleanup workflows.

## Architecture Decision

- Platform: native Unraid plugin (`.plg` + plugin files under `/usr/local/emhttp/plugins/unraid.template.manager`)
- Backend: PHP (Unraid-native integration)
- Frontend: native Unraid page with modest JavaScript/CSS
- Shell usage: only for simple, explicit operations when justified
- Config/data path: `/boot/config/plugins/unraid.template.manager`
- Forbidden architectures remain out of scope: Flask/FastAPI/React SPA/Node backend/Python backend/Docker sidecar

## Current Milestone State

- Milestone 0 (Research and Architecture Confirmation): `done`
- Milestone 1 (Native plugin skeleton): `done`
- Milestone 2 (Template inventory): `done`
- Milestone 3 (Mapping and diagnostics): `done`
- Milestone 4 (Safe actions and backup): `done`
- Milestone 5 (Recovery tools): `done`
- Milestone 6 (Polish and packaging): `in_progress`

## Milestone Breakdown

### Milestone 0 - Research and Architecture Confirmation (`done`)

- Confirm native Unraid plugin architecture constraints
- Research dockerMan template storage and behavior
- Analyze reference implementation features and edge cases
- Establish initial plan and task board

### Milestone 1 - Native Plugin Skeleton (`done`)

- Create `source/unraid.template.manager.plg`
- Create plugin directory scaffold under `source/plugin/usr/local/emhttp/plugins/unraid.template.manager/`
- Register initial Unraid page
- Establish basic plugin config path handling (`/boot/config/plugins/unraid.template.manager`)

### Milestone 2 - Template Inventory (`done`)

- Read template XML files from templates-user path
- Parse XML safely with malformed-file isolation
- Render inventory list with key metadata
- Add minimal filtering and status indicators

### Milestone 3 - Mapping and Diagnostics (`done`)

- Add template/container mapping heuristics
- Detect orphaned, duplicate-candidate, invalid templates
- Surface warnings with transparent reasoning

### Milestone 4 - Safe Actions and Backup (`done`)

- Implement backup-first destructive flows
- Add safe delete, clone, and restore-preview operations
- Persist operation audit events

### Milestone 5 - Recovery Tools (`done`)

- Detect docker image mode vs directory mode
- Add recovery guidance and pre-change checks
- Improve mismatch diagnostics (container exists/template missing)

### Milestone 6 - Polish and Packaging (`in_progress`)

- UX polish and error handling hardening
- Package/release workflow and installation validation
- Documentation completion

## High-Level Component Design

- `unraid.template.manager.plg`: installer, pre/post/remove hooks
- `.page` entrypoint: native Unraid page registration + rendering
- `include/Config.php`: canonical paths and configuration helpers
- `include/TemplateInventoryService.php`: file scan + safe XML parse
- `include/TemplateMatcher.php`: deterministic mapping heuristics
- `include/BackupService.php`: backup set creation and restore preview logic
- `include/AuditLog.php`: append-only action trail
- `javascript/app.js`: lightweight client interactions
- `css/app.css`: native-consistent styling

## Risks

- Unraid internals can vary by release; avoid brittle coupling
- Malformed XML may be common; parser must degrade gracefully
- File operations on flash require strict path and filename validation
- Mapping confidence can be misleading if heuristics are opaque

## Open Questions

- Which dockerMan internals are stable enough to depend on directly?
- Should container start/stop controls stay out of scope permanently?
- Which restore conflict strategy is safest by default (skip/overwrite/prompt)?
- How much normalization is needed for duplicate detection without false positives?

## Improvement Cycles (5-Pass Execution Plan)

1. Pass 1: skeleton + read-only inventory
2. Pass 2: mapping + diagnostics baseline
3. Pass 3: backup-first safe actions
4. Pass 4: recovery checks + workflow hardening
5. Pass 5: polish, packaging, and regression verification
