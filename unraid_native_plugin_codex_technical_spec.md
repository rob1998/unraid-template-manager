# Native Unraid Plugin for Docker Template Management

## Purpose

This document is a Codex-ready technical specification and execution plan for building a **native Unraid plugin** that makes Docker template management easier inside the Unraid UI.

The plugin should manage XML templates stored in:

```text
/boot/config/plugins/dockerMan/templates-user
```

The result should feel like a real Unraid extension, not a standalone web app awkwardly bolted onto Unraid.

---

## Context and reference project

Use the following GitHub project as a **feature and research reference**, not as the architectural model:

- `Qballjos/docker-template-manager`
- Repository: `https://github.com/Qballjos/docker-template-manager`

Codex should inspect that repository to understand:

- which features already exist
- which user problems it tries to solve
- what workflows and UI concepts are useful
- what edge cases around templates, containers, backups, and recovery are already covered
- what migration/recovery guidance exists, such as `docker.img` / vDisk to folder-based Docker storage

Important:

- The GitHub project is a **standalone Docker app** with a Python backend and React frontend.
- This new project must **not** follow that architecture unless there is an extremely strong reason.
- The new project must be designed as a **native Unraid plugin**.

---

## Core product goal

Build a native Unraid plugin that:

- indexes Docker templates
- shows which templates are in use, stale, orphaned, duplicated, broken, or suspicious
- makes cleanup safer
- makes backup/restore of templates easier
- reduces the pain of recovering from Docker-related failures like `docker.img` corruption or template loss
- provides a much better UI for template lifecycle management than Unraid currently offers

This is not a generic Docker management suite.

The primary focus is:

- **template lifecycle management**
- **template to container mapping**
- **recovery, cleanup, and safety tooling**

---

## Architectural direction

### Required architecture

Build this as a **native Unraid plugin**, using normal Unraid plugin patterns.

Expected stack:

- `.plg` installer
- packaged plugin files in the standard Unraid plugin structure
- PHP for backend integration with the Unraid web UI
- JavaScript for richer UI interactions where needed
- shell scripts only where they are the simplest and safest option
- plugin data/config stored under `/boot/config/plugins/<plugin-name>/`

### Explicit non-goals

Do **not** start with:

- Flask
- FastAPI
- React SPA
- Next.js
- separate Node backend
- separate Python backend
- Dockerized sidecar app

A native Unraid plugin is the target.

### Why

Reasons for this architecture choice:

- it should feel native in the Unraid UI
- lower operational overhead
- fewer moving parts
- fewer auth/session layers
- easier adoption by Unraid users
- closer alignment with how mature Unraid plugins are typically structured

---

## Research tasks for Codex

Before implementation, Codex should research and summarize:

### 1. Reference app feature inventory

Inspect `Qballjos/docker-template-manager` and produce a detailed feature index, including:

- dashboard features
- template listing/editing/clone/delete behavior
- container inspection/status behavior
- backup and restore behavior
- mapping logic between templates and containers
- migration docs and system recovery recommendations
- networking / Docker interaction features
- update check behavior
- any quality-of-life features hidden in code that are not obvious from the README

### 2. Unraid plugin development patterns

Research how real Unraid plugins are typically built, including:

- `.plg` file structure
- `.page` files
- PHP integration
- JS/AJAX patterns
- config storage
- event scripts
- page registration and navigation placement
- styling patterns used in mature plugins

### 3. Established plugin examples

Inspect a few mature plugins as references, including at least:

- Community Applications
- Dynamix-related plugins where relevant
- another respected plugin with native UI integration

Codex should document:

- how these plugins are structured
- where plugin files live
- how actions are handled
- how data is stored
- how they integrate into the UI
- which patterns are worth copying

### 4. Docker / Unraid specifics

Research how Unraid stores and uses Docker templates, including:

- template XML structure
- relationship between template XML and actual containers
- whether there are existing helper scripts or internal APIs
- whether dockerMan state can be read or leveraged safely
- how Docker networks, paths, and env vars can be surfaced meaningfully
- how folder mode vs `docker.img` affects recovery workflows

---

## Proposed plugin name

Working name:

- `unraid.template.manager`

Alternative user-facing names can be decided later.

Use a clear internal namespace and avoid ambiguous naming.

---

## MVP scope

The first usable version should include the following.

### Template inventory

- read all XML files from `/boot/config/plugins/dockerMan/templates-user`
- parse them safely
- show a list/table of all templates
- display key metadata per template

### Template/container mapping

- attempt to map each template to a current or historical container
- mark templates as:
  - matched
  - unmatched/orphaned
  - duplicate candidate
  - invalid/broken

### Safety and cleanup

- safe delete with automatic backup before removal
- clone template under a new filename/name
- rename template safely if feasible
- download/export template XML
- raw XML viewer

### Health warnings

Surface useful warnings such as:

- duplicate names
- invalid XML
- missing image reference
- suspicious path mappings
- missing network references where detectable
- template file exists but container no longer exists
- container exists but matching template appears missing

### Backup support

- create a backup set of templates
- list backups
- restore or preview restore of one or more templates
- timestamp and metadata for backups

### UI

- native-looking page in Unraid
- filters for all / matched / orphaned / duplicates / broken
- search and sort
- row actions for inspect / clone / backup / delete / export

---

## V2 scope

### Container comparison

- compare current template config to actual container config
- show differences for:
  - image
  - ports
  - volumes
  - variables
  - network mode
  - labels if relevant

### Regeneration helpers

- draft a template from container inspect data
- merge selected container values back into an existing template
- preview changes before write

### Bulk actions

- bulk backup
- bulk delete stale templates
- bulk export
- bulk validation

### Recovery helpers

- detect whether Docker uses vDisk or folder mode
- explain implications clearly
- provide a migration checklist
- create pre-change backup bundles before Docker reconfiguration

---

## V3 scope

### Advanced analysis

- normalize templates for comparison
- detect near-duplicates
- detect naming mismatches
- detect paths that no longer exist
- detect references to missing shares or devices
- detect containers that may be hard to recreate because the template is incomplete

### Import/export and portability

- export a package suitable for moving templates between Unraid servers
- import with preview and conflict detection

### Optional deeper integration

- investigate whether Community Applications metadata can help enrich template insights
- investigate whether dockerMan internals can be leveraged safely and maintainably

Only do this if it does not create brittle coupling.

---

## Functional requirements

### Read path

Must read from:

```text
/boot/config/plugins/dockerMan/templates-user
```

### Backup path

Default backup location should be something like:

```text
/boot/config/plugins/unraid.template.manager/backups
```

### Config path

Store plugin config under:

```text
/boot/config/plugins/unraid.template.manager
```

### Permissions and safety

- minimize write operations
- validate target paths before any write/delete/copy
- do not allow path traversal
- never assume filenames are safe
- reject invalid operations explicitly

### XML handling

- parse XML safely
- validate before save
- preserve structure where possible
- provide clear errors for malformed XML

### Recovery-first philosophy

Any destructive action should:

- show a warning
- create a backup first by default
- be reversible where possible
- log what happened

---

## Non-functional requirements

### Native UX

- UI should look and feel like Unraid
- avoid an obviously foreign SPA feel
- keep dependencies modest

### Performance

- should remain fast even with many templates
- parsing dozens or a few hundred templates should not feel slow

### Reliability

- malformed XML should not break the whole page
- one broken template should degrade gracefully
- operations should fail safely

### Maintainability

- keep architecture simple
- prefer boring, readable code
- avoid unnecessary framework complexity
- isolate parsing, mapping, and file operations cleanly

### Accessibility

- keyboard-friendly actions where practical
- clear warnings and confirmations
- no reliance on inaccessible toast-only UX

---

## Suggested information architecture

### Main page sections

#### 1. Overview

Show summary cards:

- total templates
- matched templates
- orphaned templates
- duplicates
- invalid templates
- recent backups

#### 2. Template table

Columns could include:

- template name
- filename
- image
- mapped container
- status
- last modified
- actions

#### 3. Detail drawer or detail panel

When opening a template:

- basic metadata
- parsed settings overview
- mapped container details
- warnings/issues
- raw XML tab
- backup history tab

#### 4. Backup and recovery page

- backup list
- restore preview
- restore actions
- export actions

#### 5. System checks page

- Docker storage mode detection
- path validity checks
- missing references
- migration/recovery guidance

---

## Suggested status model

Each template should be classified using a structured model such as:

- `matched`
- `orphaned`
- `duplicate`
- `invalid`
- `warning`
- `missing_template_for_container` (container-centric view)

Also provide severity levels for issues:

- info
- warning
- error

---

## Data model suggestions

### TemplateFile

Fields:

- filename
- absolute_path
- modified_at
- size
- xml_valid
- parse_errors
- template_name
- image
- network_mode
- ports
- volumes
- env_vars
- labels
- category if present

### ContainerRef

Fields:

- container_name
- id
- image
- status
- created_at
- ports
- mounts
- env_vars
- network_mode

### TemplateMatch

Fields:

- template_filename
- container_name
- match_type
- confidence
- reasons

### BackupSet

Fields:

- id
- created_at
- template_count
- path
- metadata
- notes

### Issue

Fields:

- code
- severity
- title
- description
- related_template
- related_container
- suggested_action

---

## Matching logic requirements

Codex should implement mapping logic incrementally and transparently.

Start simple:

- exact template/container name match
- normalized case-insensitive match
- common prefix/suffix stripping where justified

Then improve carefully:

- compare image names
- compare key mount paths
- compare key env vars
- compare network mode

All fuzzy matching should explain why a match was suggested.

Do not hide uncertainty.

---

## Write operations that should exist

### Safe delete

- backup first
- remove selected XML template
- log action

### Clone

- duplicate XML to a new safe filename
- adjust title/name fields where needed
- validate resulting XML

### Rename

Only implement if safe and deterministic.

### Restore

- preview before restore
- allow selective restore
- avoid silent overwrite without warning

### Export

- export raw XML
- export backup bundle
- optional zip export later

---

## Write operations that should not be first priority

Do not rush into:

- full XML editor with auto-save
n- direct free-form editing without strong validation
- container recreation actions
- raw Docker start/stop controls unless there is a very good UX reason

Remember: this project is mainly about template management, not becoming a second Docker manager.

---

## Security and safety requirements

### General

- no weak ad-hoc auth model like URL API keys
- avoid reinventing authentication unnecessarily if the page already lives in Unraid
- do not expose dangerous actions without confirmation

### Filesystem

- sanitize filenames strictly
- canonicalize paths
- block traversal
- backup before destruction

### Docker interaction

Only add Docker-level interaction when clearly justified by the feature.

Examples of justified read access:

- listing containers
- inspecting containers for mapping and diffing
- reading network mode / mounts / image details

Examples that should be debated before inclusion:

- start/stop/restart container actions
- actions that go beyond template lifecycle management

### Logging

Maintain an audit trail for:

- delete
- restore
- clone
- rename
- backup
- failed writes

---

## Plugin structure proposal

```text
source/
  unraid.template.manager.plg
  package/
    unraid.template.manager-0.1.0-x86_64.txz
  plugin/
    usr/local/emhttp/plugins/unraid.template.manager/
      unraid.template.manager.page
      unraid.template.manager.php
      include/
        TemplateRepository.php
        TemplateParser.php
        TemplateMatcher.php
        BackupService.php
        HealthCheckService.php
        DockerInspectService.php
        XmlValidator.php
      ajax/
        list_templates.php
        get_template.php
        delete_template.php
        clone_template.php
        backup_templates.php
        list_backups.php
        restore_backup.php
        run_checks.php
      javascript/
        app.js
      css/
        app.css
      templates/
        overview.php
        details.php
        backups.php
        checks.php
      scripts/
        backup.sh
        restore.sh
      README.md
```

This is only a starting structure and can be refined.

---

## Suggested milestone plan

### Milestone 0 — Research and architecture confirmation

Deliverables:

- summary of `Qballjos/docker-template-manager`
- summary of native Unraid plugin patterns
- recommended architecture decision note

### Milestone 1 — Skeleton native plugin

Deliverables:

- `.plg` file
- package structure
- basic page registered in Unraid UI
- simple plugin settings/config path

### Milestone 2 — Read-only template inventory

Deliverables:

- scan templates-user directory
- parse XML safely
- render template list with status indicators
- filters and search

### Milestone 3 — Matching and issue detection

Deliverables:

- template/container mapping logic
- issue model
- orphan/duplicate/invalid detection
- details view

### Milestone 4 — Backup and safe actions

Deliverables:

- backup service
- safe delete
- clone
- export
- restore preview

### Milestone 5 — Recovery/system checks

Deliverables:

- detect vDisk vs folder mode
- system checks page
- recovery/migration guidance

### Milestone 6 — Polish and packaging

Deliverables:

- improved styling
- better action confirmations
- audit trail
- docs
- release packaging

---

## First implementation tasks for Codex

1. Create the native Unraid plugin skeleton.
2. Research and document established plugin file layout.
3. Implement a directory scanner for `templates-user`.
4. Implement safe XML parsing and error handling.
5. Build a first page that lists templates and basic metadata.
6. Implement container inspection read-only logic for mapping.
7. Add classification states: matched, orphaned, duplicate, invalid.
8. Add backup-before-delete flow.
9. Add clone/export actions.
10. Add recovery checks and Docker storage mode detection.

---

## Questions Codex should answer during implementation

- Is there a stable way to leverage dockerMan internals without fragile coupling?
- Which Unraid-native UI conventions should this plugin mimic most closely?
- Should container start/stop actions be included at all, or kept out of scope?
- What minimum XML validation is needed to avoid corrupting templates?
- How should duplicate/conflict handling work when restoring backups?
- What is the safest and clearest way to surface migration help for `docker.img` versus folder mode?

---

## Final instruction to Codex

Use `Qballjos/docker-template-manager` as a **functional reference** and inspect it thoroughly for features, workflows, and edge cases.

But build this project as a **native Unraid plugin**, using established Unraid plugin conventions and architecture.

Prefer:

- native integration
- simple maintainable code
- safe file operations
- recovery-first UX
- clear template diagnostics

Avoid building a second standalone management appliance unless the project scope explicitly changes later.


