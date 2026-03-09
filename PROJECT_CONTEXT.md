# PROJECT_CONTEXT.md

## Purpose

This document provides **core background knowledge and system context** for AI agents working on this repository.

It exists to reduce repeated research and give agents a clear understanding of:

- Unraid architecture
- Docker template behavior
- dockerMan internals (high level)
- where relevant files live
- how templates relate to containers
- known operational pain points

Agents should read this file before starting development tasks.

---

# Unraid Overview

Unraid is a Linux-based NAS operating system focused on:

- storage management
- Docker container hosting
- virtual machines
- home server workloads

The Unraid web interface is implemented using:

- PHP
- JavaScript
- Dynamix framework components

Native plugins extend this UI.

Plugin files typically live in:

```
/usr/local/emhttp/plugins/<plugin-name>/
```

Plugin configuration data is stored on the flash drive under:

```
/boot/config/plugins/<plugin-name>/
```

Plugins are installed via `.plg` installer files.

---

# Unraid Plugin Structure

Typical plugin components:

```
plugin-name.plg
/usr/local/emhttp/plugins/plugin-name/
    plugin-name.page
    plugin-name.php
    javascript/
    css/
    include/
    ajax/
```

`.page` files register UI pages.

PHP handles backend logic and AJAX endpoints.

JavaScript enhances the UI but does not replace the core UI architecture.

---

# Docker on Unraid

Unraid runs Docker containers using a system called **dockerMan**.

dockerMan is responsible for:

- container creation
- container lifecycle
- Docker template handling
- integration with the Unraid UI

dockerMan templates define container configuration.

These templates are stored as XML.

---

# Docker Template Storage

User templates are stored in:

```
/boot/config/plugins/dockerMan/templates-user
```

These XML files represent container configurations used by the Unraid UI.

They include fields like:

- image
- ports
- environment variables
- volume mappings
- network mode
- labels
- container name
- categories
- icon references

Templates allow users to recreate containers easily.

---

# Template Lifecycle

Typical lifecycle:

1. Template is created (manually or via Community Applications).
2. User launches container from template.
3. dockerMan converts template into `docker run` configuration.
4. Container runs independently.
5. Template remains as configuration reference.

Over time:

- containers may be deleted
- templates may remain
- templates may become stale
- duplicate templates may appear
- templates may become invalid

This creates management problems.

---

# Common Template Problems

Users frequently encounter:

### Orphaned templates

Template exists but container is gone.

### Missing templates

Container exists but template was deleted.

### Duplicate templates

Multiple templates reference same container/image.

### Invalid templates

Malformed XML or invalid fields.

### Template drift

Container configuration diverges from template definition.

---

# docker.img vs Folder Mode

Unraid Docker storage historically used a disk image:

```
/mnt/user/system/docker/docker.img
```

Problems with vDisk mode:

- space exhaustion
- corruption
- difficult recovery
- opaque filesystem

Modern Unraid versions support **folder mode**:

```
/mnt/user/system/docker/
```

Folder mode advantages:

- easier recovery
- easier backup
- easier inspection

Agents should be aware of both modes.

---

# Docker Container Data

Running containers can be inspected via Docker.

Relevant information includes:

- container name
- image
- mount paths
- environment variables
- ports
- network configuration

This data can be compared against template XML.

---

# Relationship Between Templates and Containers

Templates are **not authoritative once a container runs**.

The container's configuration may diverge from the template.

Therefore:

Template → container mapping must be heuristic.

Possible matching signals:

- container name
- image name
- mount paths
- environment variables
- labels
- port mappings

Agents should implement matching carefully and transparently.

---

# Safety Concerns

Template files are user data.

Agents must treat them carefully.

Key safety rules:

- never overwrite templates without backup
- validate XML before writing
- sanitize filenames
- prevent path traversal
- log destructive actions

---

# Backup Strategy

Before destructive operations:

Templates should be backed up to:

```
/boot/config/plugins/unraid.template.manager/backups
```

Backup metadata should include:

- timestamp
- template count
- source path

Restore operations must show preview before execution.

---

# UI Integration

The plugin should appear as a native Unraid page.

Typical UI features:

- table views
- filters
- sorting
- detail panels
- confirmation dialogs

Avoid full SPA architectures.

---

# Reference Project

Feature inspiration should be taken from:

https://github.com/Qballjos/docker-template-manager

Important:

That project is a **standalone Docker application**.

It uses:

- Python
- Flask
- React

Its architecture should **not** be copied.

Only its **features and workflows** should inform development.

---

# Target Outcome

The finished plugin should:

- provide a clear overview of Docker templates
- detect problematic templates
- help users clean up template sprawl
- make template recovery easier
- integrate cleanly with Unraid

It should feel like a **native part of the Unraid interface**.

---

# Guidance for AI Agents

Before implementing features:

1. Read this file.
2. Read `/docs/project/MASTER_PLAN.md`.
3. Review `TASKS.md`.
4. Update task status before starting work.

Agents must maintain project tracking files continuously.

---

# Summary

This repository builds a **native Unraid plugin for Docker template lifecycle management**.

Key focus areas:

- template visibility
- template health diagnostics
- template/container mapping
- safe cleanup
- recovery tooling

Agents must prioritize:

- safety
- native integration
- maintainability
- accurate project tracking
