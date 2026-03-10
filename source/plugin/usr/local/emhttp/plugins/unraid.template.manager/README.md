# Unraid Template Manager (Native Unraid Plugin)

This directory contains the native plugin files installed to:

`/usr/local/emhttp/plugins/unraid.template.manager/`

Current state:

- Native page loader + PHP renderer endpoint (`UnraidTemplateManager.page` + `UnraidTemplateManager.php`)
- Template inventory with deterministic template/container mapping and diagnostics
- Multi-filter toolbar (text + template state + mapping state + severity)
- Single-row and bulk selection workflow with backup-safe bulk delete
- Backup all / selected, export all / selected, and import (`.xml`, `.tgz`, `.tar.gz`, `.tar`)
- Restore workflow UI (list, preview, restore with optional overwrite)
- Docker storage mode detection and config-switch workflow with docker.cfg backup
- Command-timeout + cache fallback for container inventory to avoid long page stalls
- Plugin config paths under `/boot/config/plugins/unraid.template.manager`

Planned next milestones:

- Harden import/export archive compatibility testing on live Unraid
- Add non-destructive diagnostics details and action audit trail UI
- Package polish and release verification on target hosts
