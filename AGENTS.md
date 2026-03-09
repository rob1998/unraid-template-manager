# AGENTS.md

## Purpose

This file defines the **autonomous agent operating rules** for this repository.

Agents (such as Codex or other AI coding agents) must follow these guidelines while working on the project.

The goal is to allow agents to **safely, autonomously, and continuously develop the project** while maintaining structure, traceability, and architectural integrity.

---

## Agent Startup Procedure

Agents must follow the repository bootstrap process defined in:

REPO_BOOTSTRAP.md

This file defines the mandatory startup sequence for autonomous agents.

Agents must:

1. Read `REPO_BOOTSTRAP.md`
2. Follow its startup procedure
3. Load project context and planning files before implementing anything

---

# Project Overview

This repository contains the development of a **native Unraid plugin** for Docker template management.

Primary functionality:

- Manage Docker template XML files stored in:
  
  /boot/config/plugins/dockerMan/templates-user

- Provide visibility into template usage and health
- Map templates to containers
- Detect stale, orphaned, duplicated, or broken templates
- Provide safe backup, restore, clone, and delete operations
- Provide diagnostics and recovery tooling for template-related failures

This is **not** a standalone Docker web application.

It must remain a **native Unraid plugin**.

---

# Architecture Rules

Agents must preserve the native Unraid plugin architecture.

## Required architecture

Use:

- `.plg` installer
- Unraid plugin packaging structure
- PHP backend integration
- modest JavaScript for UI interaction
- shell scripts only where simple and justified
- plugin configuration stored under:

  /boot/config/plugins/unraid.template.manager

## Explicitly forbidden unless approved by documented decision

Do NOT introduce:

- Flask
- FastAPI
- React SPA
- Next.js
- Node backend
- Python backend
- Dockerized management sidecar

If an agent believes deviation is required, it must:

1. Record the reasoning in `/docs/project/DECISIONS.md`
2. Create a task in `/docs/project/TASKS.md`
3. Mark the task `needs_decision`

---

# Context Files

Agents must read the following context files before performing any development work.

## PROJECT_CONTEXT.md

Provides platform knowledge including:

- Unraid architecture
- plugin development patterns
- dockerMan overview
- Docker template XML lifecycle
- container/template relationships
- known operational pain points

Agents must treat this file as **baseline system knowledge**.

## REPO_BOOTSTRAP.md

Defines the required startup procedure for agents.

Agents must follow this file before:

- selecting tasks
- writing code
- updating plans

---

# Reference Implementation

Agents must research the following repository for feature inspiration:

https://github.com/Qballjos/docker-template-manager

Important:

This repository is a **functional reference**, not an architectural template.

Agents should extract:

- features
- workflows
- edge cases
- recovery mechanisms
- template/container mapping ideas

But should **not copy its architecture**, since it is built as a standalone Docker web application.

---

# Required Project Tracking

Agents must maintain the planning system located in:

/docs/project/

Required planning files:

/docs/project/MASTER_PLAN.md  
/docs/project/PROGRESS.md  
/docs/project/DISCOVERIES.md  
/docs/project/DECISIONS.md  
/docs/project/TASKS.md  

These files must be kept synchronized with the actual state of the repository.

Agents must update them continuously during development.

---

# Planning Integrity Rule

The planning system is a core part of the repository.

Agents must ensure:

- tasks reflect real implementation state
- milestones reflect actual progress
- discoveries are logged
- decisions are documented

Code must never diverge from the documented plan without updating the plan.

If a discrepancy is discovered, the agent must update documentation immediately.

---

# Task Lifecycle

Tasks must use one of the following states:

- todo
- in_progress
- blocked
- done
- deferred
- research
- needs_decision

When working:

1. Set task → `in_progress`
2. Complete work
3. Update task → `done`
4. Log discoveries if relevant

Never leave tasks in ambiguous states.

---

# Discovery Handling

When agents learn something new they must:

1. Write it to `/docs/project/DISCOVERIES.md`
2. Create follow-up tasks if necessary
3. Update `MASTER_PLAN.md` if scope changes

Examples:

- Unraid plugin API behavior
- dockerMan internals
- template XML edge cases
- filesystem behavior
- recovery workflows

---

# Decision Logging

Architectural decisions must be recorded in:

/docs/project/DECISIONS.md

Each decision entry should include:

- context
- decision
- alternatives considered
- consequences

---

# Progress Tracking

Agents must update:

/docs/project/PROGRESS.md

with:

- milestone progress
- recently completed tasks
- upcoming tasks
- blockers

---

# Coding Guidelines

Agents must follow these principles:

## Simplicity

Prefer:

- readable code
- minimal dependencies
- clear logic

Avoid unnecessary frameworks.

## Safety

Never perform destructive filesystem operations without:

- validation
- backup

Always assume template files may be malformed.

## Reliability

Malformed XML must never crash the plugin.

The UI must degrade gracefully.

## Transparency

Errors should be visible to users.

Actions should be logged.

---

# Docker Interaction Rules

Docker interaction should be minimal.

Allowed:

- container listing
- container inspect
- network/mount inspection

Debated features:

- start container
- stop container
- restart container

These must be justified and documented before implementation.

---

# XML Handling Rules

When working with template XML:

- parse safely
- validate before writing
- preserve structure
- avoid destructive rewriting

Never corrupt a user template.

---

# Backup Rules

Before destructive actions:

- create backup
- log backup location
- allow restore

Backup location:

/boot/config/plugins/unraid.template.manager/backups

---

# Scope Discipline

Agents must avoid expanding scope unnecessarily.

This project is about:

- template lifecycle management
- diagnostics
- recovery tooling

It is **not a replacement Docker manager**.

---

# Implementation Milestones

Agents should follow milestone progression:

0. Research and architecture confirmation
1. Native plugin skeleton
2. Template inventory
3. Mapping and diagnostics
4. Safe actions and backup
5. Recovery tools
6. Polish and packaging

Agents may refine milestones but must update documentation accordingly.

---

# When Blocked

If an agent encounters a blocker:

1. Document the blocker in `TASKS.md`
2. Mark task as `blocked`
3. Propose possible solutions
4. Continue with other tasks

Agents should not halt progress unnecessarily.

---

# Definition of Done

A task is considered complete only if:

- implementation exists
- documentation is updated
- task status updated
- discoveries logged if applicable

---

# Agent Behavior Expectations

Agents should behave like responsible engineers:

- keep the plan current
- track progress honestly
- create new tasks when necessary
- log discoveries and decisions
- build incrementally

Agents must never pretend incomplete work is finished.

---

# Bootstrap Fallback Rule

If an agent begins work and the planning system appears incomplete or inconsistent, the agent must:

1. Re-run the bootstrap procedure defined in `REPO_BOOTSTRAP.md`
2. Repair the planning files
3. Resume work from the correct milestone

Agents must never ignore the bootstrap system.

---

# Final Instruction

Maintain:

- architectural integrity
- safety of user data
- clarity of documentation
- incremental progress

The project plan must remain **alive and accurate** as development progresses.
