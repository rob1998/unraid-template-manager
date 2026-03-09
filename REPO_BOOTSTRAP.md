# REPO_BOOTSTRAP.md

## Purpose

This document defines the **startup procedure for autonomous coding agents** working in this repository.

Agents must follow this sequence **every time they start working on the project**.  
The goal is to ensure agents load the correct context, maintain the project plan, and continue work without losing state.

This prevents agents from:

- restarting the project from scratch
- ignoring the planning system
- diverging from architecture
- missing previous discoveries

---

# Step 1 — Load repository governance

Agents must first read the following files completely:

1. `AGENTS.md`
2. `PROJECT_CONTEXT.md`

These files define:

- architecture constraints
- safety rules
- project purpose
- Unraid platform details
- template lifecycle behavior
- allowed and forbidden technologies

Agents must **not begin implementation before reading these files**.

---

# Step 2 — Load project planning system

Next read all files under:

```
/docs/project/
```

At minimum:

- `MASTER_PLAN.md`
- `PROGRESS.md`
- `DISCOVERIES.md`
- `DECISIONS.md`
- `TASKS.md`

Agents must reconstruct the **current project state** from these documents.

Determine:

- current milestone
- tasks already completed
- tasks currently in progress
- tasks that are blocked
- pending architectural decisions

---

# Step 3 — Repository scan

Agents must inspect the repository structure to understand:

- existing plugin files
- documentation
- implemented features
- missing components
- scaffolding that still needs implementation

The agent should compare repository state with the **task board**.

If tasks are marked `done` but code is missing, record a discrepancy in `DISCOVERIES.md`.

---

# Step 4 — Synchronize planning artifacts

Before starting new work, agents must ensure planning files are consistent.

Update if needed:

### MASTER_PLAN.md
Confirm:

- milestone alignment
- scope accuracy
- architectural notes

### TASKS.md
Ensure:

- each task has a status
- new tasks discovered during repo scan are added

### PROGRESS.md
Add a short entry describing:

- current session start
- what milestone is active
- which tasks are next

---

# Step 5 — Select next task

Select the **highest priority task** that is:

- `todo`
- not blocked
- aligned with the current milestone

Mark the task:

```
status: in_progress
```

Update `TASKS.md` accordingly.

---

# Step 6 — Implement incrementally

When implementing:

1. Work on one task at a time.
2. Update relevant code.
3. Update documentation where appropriate.
4. Record discoveries.

Agents must avoid large uncontrolled rewrites.

---

# Step 7 — Log discoveries

When new information is learned:

Add an entry to:

```
/docs/project/DISCOVERIES.md
```

Examples:

- Unraid API quirks
- dockerMan behaviors
- XML schema edge cases
- filesystem limitations
- UI framework details

If discoveries affect architecture, update `DECISIONS.md`.

---

# Step 8 — Update tasks

When work finishes:

- mark the task `done`
- update `PROGRESS.md`
- add follow-up tasks if new work is discovered

If work cannot proceed:

- mark task `blocked`
- describe blocker
- propose solutions

---

# Step 9 — Milestone progression

When all milestone tasks are complete:

1. Update `MASTER_PLAN.md`
2. Mark milestone complete in `PROGRESS.md`
3. Promote next milestone

Agents must **never silently skip milestones**.

---

# Step 10 — Continuous integrity checks

Agents must continuously ensure:

- planning files match actual code
- architecture rules are respected
- destructive operations remain safe
- documentation reflects implementation

If divergence occurs, correct documentation or code immediately.

---

# When starting with an empty repository

If planning files do not yet exist:

1. Create `/docs/project/`
2. Initialize the required planning files.
3. Begin **Milestone 0: Research and Architecture Confirmation**.

---

# Agent behavior expectations

Agents should behave like responsible engineers:

- do not rush implementation
- maintain the planning system
- document discoveries
- keep tasks updated
- respect architecture rules

The planning system is a **first-class part of the project**.

---

# Final rule

Never begin coding without:

1. Reading governance files
2. Synchronizing the project plan
3. Selecting a tracked task

The plan drives development.
