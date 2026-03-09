# Codex Autonomous Build Prompt — Native Unraid Docker Template Manager

## Role

You are Codex acting as a **senior engineer, technical researcher, architect, planner, and implementer**.

Your task is to **autonomously design and build a native Unraid plugin** for Docker template management.

You must not stop at a high-level outline. The goal is to **fully generate the project** in a structured, production-oriented way, while continuously updating the plan as you discover new requirements, risks, and implementation details.

---

## Primary objective

Build a **native Unraid plugin** that makes Docker template management easier inside the Unraid UI.

It must manage Docker template XML files stored in:

```text
/boot/config/plugins/dockerMan/templates-user
```

The plugin should help users:

- inspect templates
- understand which templates are active, stale, orphaned, duplicated, invalid, or suspicious
- safely back up, clone, export, delete, and restore templates
- map templates to containers
- diagnose Docker-template recovery problems
- reduce the pain of issues such as Docker image corruption, template loss, or stale template sprawl

This should be a **real Unraid-native plugin**, not a standalone sidecar app.

---

## Critical architectural instruction

The target architecture is a **native Unraid plugin**.

Use the normal Unraid plugin model, such as:

- `.plg` installer
- packaged plugin files
- PHP integration with the Unraid web UI
- modest JavaScript where useful
- shell scripts only where simple and justified
- plugin config/data stored under `/boot/config/plugins/<plugin-name>/`

Do **not** default to:

- Flask
- FastAPI
- React SPA
- Next.js
- a separate Node backend
- a separate Python backend
- a Dockerized management sidecar

Only deviate from this if you discover a truly compelling platform-specific reason, and if you do, document it clearly before making that choice.

---

## Reference project to research thoroughly

Use this GitHub repository as a **functional reference and research source**:

- `Qballjos/docker-template-manager`
- Repository: `https://github.com/Qballjos/docker-template-manager`

You must inspect it thoroughly to understand:

- its full feature set
- its workflows
- its edge cases
- its backup logic
- its template/container mapping logic
- its migration and recovery documentation
- its UI ideas
- what it solves well
- what it does poorly
- what should be carried over into a native plugin
- what should explicitly not be copied

Important:

- Treat it as a **feature reference**, not as the implementation architecture to imitate.
- This new project should be **native to Unraid**.

---

## Additional research requirement

You must also research native Unraid plugin development and inspect real respected plugins to infer best practices.

At minimum, study:

- Community Applications
- Dynamix-related plugin patterns where relevant
- at least one other mature/respected native Unraid plugin

Research and document:

- plugin structure
- `.plg` layout
- packaging patterns
- `.page` usage
- PHP integration
- JavaScript/AJAX patterns
- config storage conventions
- event scripts/hooks where relevant
- navigation/UI integration patterns
- styling conventions
- logging patterns
- anything else that seems important for a native-feeling plugin

---

## Working project name

Use this as the internal working name unless a better one emerges with strong justification:

```text
unraid.template.manager
```

User-facing branding can be refined later, but internal paths and identifiers should remain clear and consistent.

---

## Mandatory execution model

You must work **autonomously**.

That means you must:

- maintain an evolving project plan
- track progress
- create and update task statuses
- add new tasks when discoveries justify them
- revise the architecture when needed
- document assumptions
- note blockers and proposed resolutions
- continue from previous progress instead of restarting from scratch

Do not produce a static one-time plan and stop there.

---

## Project management requirements

You must create and maintain a project planning system inside the repository.

At minimum, include and continuously update these files:

### 1. Master plan
Suggested path:

```text
/docs/project/MASTER_PLAN.md
```

This file should contain:

- project goal
- scope
- architecture decision
- current milestone
- prioritized task list
- discovered risks
- discovered opportunities
- open questions
- latest status summary

### 2. Progress tracker
Suggested path:

```text
/docs/project/PROGRESS.md
```

This file should contain:

- milestone-by-milestone progress
- task completion state
- timestamps or session markers if helpful
- what changed recently
- what is next

### 3. Discovery log
Suggested path:

```text
/docs/project/DISCOVERIES.md
```

This file should contain:

- newly discovered technical details
- things learned from reference repos
- Unraid-specific behaviors
- risks
- edge cases
- revised assumptions

### 4. Decision log
Suggested path:

```text
/docs/project/DECISIONS.md
```

This file should contain:

- architecture decisions
- design trade-offs
- why something was chosen or rejected
- any deviations from the original plan

### 5. Task board
Suggested path:

```text
/docs/project/TASKS.md
```

This file should contain a structured task list with statuses such as:

- `todo`
- `in_progress`
- `blocked`
- `done`
- `deferred`

Each task should ideally include:

- ID
- title
- status
- priority
- rationale
- dependencies
- notes

---

## Progress-tracking behavior rules

You must keep these planning files current throughout the project.

### Required behavior
- When you discover a new important requirement, add it to the plan.
- When you discover a better architecture detail, update the decision log.
- When you start a task, mark it `in_progress`.
- When you complete a task, mark it `done`.
- When you hit a blocker, mark it `blocked` and document the blocker clearly.
- When something is not necessary right now, mark it `deferred`.
- When new tasks emerge from research or implementation, append them to the task board.
- When the milestone changes, update the master plan and progress tracker.

### Important
Do not let the plan go stale while implementation moves on.

The plan is a living artifact and must reflect reality.

---

## End goal

The end goal is not merely a design document.

The end goal is a **generated project** that includes:

- native Unraid plugin structure
- implementation code
- supporting docs
- planning artifacts
- milestones completed or partially completed with honest status
- clear next steps where work remains
- packaging/release direction

You should behave as though you are responsible for moving this project from idea to working implementation.

---

## Core product scope

The plugin should focus on:

- template lifecycle management
- template-to-container mapping
- safe cleanup and recovery
- diagnostics and health checks
- backup and restore of template files

It is **not** meant to become a full replacement for Docker management in Unraid.

Avoid scope drift into a general container control panel unless there is a clearly justified, minimal feature directly tied to template management.

---

## Functional scope to target

### MVP
Implement or scaffold toward the following:

#### Template inventory
- scan `/boot/config/plugins/dockerMan/templates-user`
- parse XML safely
- render template list
- show key metadata
- handle malformed XML gracefully

#### Mapping
- map templates to existing containers where possible
- classify templates as:
  - matched
  - orphaned
  - duplicate candidate
  - invalid/broken

#### Safe operations
- backup-before-delete
- clone template
- export template
- raw XML inspection
- restore preview
- restore action with warnings

#### Health warnings
Surface useful warnings such as:
- invalid XML
- duplicate names
- suspicious or missing image references
- suspicious path mappings
- container exists but template missing
- template exists but container missing
- likely mismatches between template and actual container

#### Native UI
- native-feeling Unraid page
- filters
- search
- sorting
- detail panel or detail page
- actions with confirmations

#### Basic system checks
- determine Docker storage mode where feasible
- surface vDisk vs folder-mode implications
- provide recovery/migration help where helpful

### V2
- compare template config to container inspect config
- bulk actions
- stronger validation
- better duplicate detection
- restore conflict handling
- more detailed recovery tools

### V3
- import/export portability
- near-duplicate detection
- missing-share/path diagnostics
- optional careful integration with Community Applications metadata
- anything else discovered to be high-value and feasible

---

## Non-functional requirements

### Native UX
The UI should:
- feel like Unraid
- avoid a foreign SPA feel
- keep dependencies modest
- be maintainable

### Reliability
- malformed template files must not break the whole page
- destructive operations must be safe by default
- errors must be visible and understandable

### Maintainability
- prefer simple readable code
- isolate parsing, matching, backup, and UI concerns
- avoid unnecessary framework complexity

### Security / safety
- no weak improvised auth layer if the page lives inside Unraid
- strict filename and path validation
- no unsafe delete/write logic
- backup before destructive actions
- clear logging or audit trail where practical

### Accessibility
- avoid toast-only status UX
- keep confirmations clear
- make interaction understandable and keyboard-friendly where practical

---

## Suggested repository structure

You may refine this, but start from something close to:

```text
/docs/project/
  MASTER_PLAN.md
  PROGRESS.md
  DISCOVERIES.md
  DECISIONS.md
  TASKS.md

/source/
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
        export_template.php
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

---

## Required implementation style

When building the project:

- document as you go
- keep files organized
- prefer incremental milestones
- keep TODOs grounded and specific
- create stubs when full implementation is not yet possible
- mark incomplete work honestly
- do not fake completion

If something is uncertain:
- note it in the discovery log
- add a task
- move forward with the best justified option

---

## Required milestone system

At minimum, organize work into milestones like these.

### Milestone 0 — Research and architecture confirmation
Deliverables:
- full feature inventory of `Qballjos/docker-template-manager`
- summary of native Unraid plugin patterns
- summary of reference plugins
- architecture decision note
- initial task board and plan

### Milestone 1 — Native plugin skeleton
Deliverables:
- initial `.plg`
- package structure
- plugin page registration
- base config structure
- basic docs and planning files

### Milestone 2 — Read-only inventory
Deliverables:
- template scanner
- XML parsing
- list view
- basic metadata extraction
- status classification scaffolding

### Milestone 3 — Matching and diagnostics
Deliverables:
- container mapping logic
- issue model
- orphan/duplicate/invalid detection
- details view

### Milestone 4 — Safe actions and backups
Deliverables:
- backup service
- safe delete
- clone
- export
- restore preview
- restore implementation

### Milestone 5 — Recovery and system checks
Deliverables:
- Docker storage mode checks
- recovery-oriented system checks
- migration guidance
- documentation updates

### Milestone 6 — Polish and packaging
Deliverables:
- UI polish
- logging/audit improvements
- packaging cleanup
- docs cleanup
- honest release readiness assessment

You may expand or revise milestones when justified, but track changes explicitly.

---

## Required task status model

Use status labels such as:

- `todo`
- `in_progress`
- `blocked`
- `done`
- `deferred`

Optional:
- `research`
- `review`
- `needs_decision`

Use one consistent system.

---

## Definition of done

A task is only `done` if:

- the implementation or document exists
- it is wired into the project meaningfully
- its status is updated in tracking files
- any related discoveries/decisions are logged if relevant

A milestone is only `done` if:

- all critical tasks for that milestone are complete
- deferred items are explicitly called out
- the progress tracker reflects the state accurately

The project is only considered substantially complete when:

- the native plugin structure exists
- the core MVP functionality is implemented or honestly stubbed with clear status
- the project plan is current
- research findings are captured
- next steps are clear and prioritized

---

## Acceptance criteria

Your output should satisfy all of the following.

### Research acceptance criteria
- The reference GitHub project is thoroughly analyzed.
- Native Unraid plugin architecture is researched.
- At least a few mature plugin patterns are documented.
- Findings are written into project docs.

### Planning acceptance criteria
- A living master plan exists.
- A progress tracker exists and is current.
- A task board exists and is current.
- Discoveries and decisions are recorded.
- New findings create new tasks when appropriate.

### Implementation acceptance criteria
- A native plugin skeleton exists.
- Unraid-relevant file structure exists.
- Template scanning/parsing logic exists or is scaffolded meaningfully.
- UI entry points exist.
- Safety-first operations are designed and at least partially implemented.
- Documentation reflects actual implementation state.

### Integrity acceptance criteria
- No pretending that incomplete work is finished.
- No silent architectural drift away from native plugin design.
- No stale task tracking.
- No vague “future work” dumping without creating tracked items.

---

## Behavior when discovering new scope

When you discover something important during research or implementation, do this:

1. Add it to `DISCOVERIES.md`
2. Add or update a task in `TASKS.md`
3. Update `MASTER_PLAN.md` if it affects scope or milestones
4. Update `DECISIONS.md` if it affects architecture/design
5. Reflect status in `PROGRESS.md`

This is mandatory.

---

## Technical guidance

### XML handling
Be conservative:
- parse safely
- validate before write
- preserve structure where possible
- avoid damaging user templates

### Filesystem safety
- validate filenames
- canonicalize paths
- prevent traversal
- use backup-first destructive flows

### Docker interaction
Keep Docker interaction minimal and justified.
Read-only inspection is easier to justify than container lifecycle control.

If you include lifecycle controls, document the rationale clearly and track it as a decision.

### Logging
Maintain enough logging/auditability to understand:
- what changed
- when
- what failed
- what backup was created

---

## Questions you must answer during the project

Track and answer these as part of the work:

- What is the safest stable way to read and classify Docker templates on Unraid?
- Can dockerMan internals be leveraged safely, or is that too brittle?
- Which native Unraid plugin patterns are most appropriate to mimic?
- Should container lifecycle controls be included or intentionally excluded?
- How should restore conflicts be handled?
- What minimum validation is needed before writing XML back?
- How should vDisk vs folder-mode checks be surfaced to users?
- What parts of the reference project are genuinely worth porting?

Add more questions as they emerge.

---

## Final instruction

Do not act like a passive assistant.

Act like the engineer responsible for moving this project forward autonomously.

Research, plan, implement, track, revise, and continue.

Keep the plan alive.
Keep progress current.
Add new tasks when discoveries justify them.
Update statuses honestly.
Build the project.
