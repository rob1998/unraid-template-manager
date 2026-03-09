# Unraid Template Manager (Native Unraid Plugin)

This directory contains the native plugin files installed to:

`/usr/local/emhttp/plugins/unraid.template.manager/`

Current state:

- Initial page registration via `UnraidTemplateManager.page`
- Read-only template inventory scaffold
- Deterministic template/container mapping baseline with transparent match reasons
- Duplicate and severity diagnostics (`info`/`warning`/`error`)
- Backup service scaffold for safe write workflows
- Backup-before-delete action for template files
- Template clone action with validation and pre-action backup
- Restore preview/apply AJAX endpoints for backup sets
- Basic plugin path/config handling (`/boot/config/plugins/unraid.template.manager`)

Planned next milestones:

- Mapping templates to containers
- Diagnostics and issue classification
- Backup-safe write operations (delete/clone/restore)
