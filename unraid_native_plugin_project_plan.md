# Native Unraid Plugin Project Plan

## Docker Template Manager Rebuild for Codex

Version: 0.1 Date: 2026-03-08 Target: Native Unraid plugin (.plg), not a
standalone Docker container

------------------------------------------------------------------------

## Goal

Create a **native Unraid plugin** that improves management of Docker
templates stored in:

/boot/config/plugins/dockerMan/templates-user

The plugin should help users:

-   understand which templates correspond to which containers
-   detect orphaned / duplicate templates
-   safely delete or clone templates
-   back up templates before destructive operations
-   assist with recovery when Docker is rebuilt or corrupted

------------------------------------------------------------------------

## Reference Project

Codex should use the following repository as a **feature reference**,
not as an architectural blueprint:

https://github.com/Qballjos/docker-template-manager

This project provides: - feature ideas - UI concepts -
container/template matching logic - backup workflows - migration notes
(docker.img → folder mode)

However it is implemented as **Flask + React running inside a Docker
container**, while this project should be implemented as a **native
Unraid plugin**.

------------------------------------------------------------------------

## Why Native Plugin

Advantages:

-   integrates with existing Unraid UI
-   avoids running an additional container
-   uses established Unraid plugin patterns
-   simpler authentication model
-   consistent look & feel

Typical Unraid plugins use:

-   .plg installer
-   PHP backend pages
-   JavaScript UI
-   shell scripts for system operations
-   files stored under: /usr/local/emhttp/plugins/`<plugin-name>`{=html}

------------------------------------------------------------------------

## Core Features

### Template Inventory

-   list all template XML files
-   parse XML safely
-   show template name, image, network mode, volumes
-   show last modified time
-   detect malformed XML

### Container Relationship Mapping

-   match templates to containers
-   detect:
    -   orphaned templates
    -   duplicate templates
    -   containers without templates

### Safe Cleanup

-   delete template with automatic backup
-   bulk delete orphaned templates
-   quarantine option instead of hard delete

### Editing / Cloning

-   clone template
-   rename template
-   view raw XML
-   validate XML before saving

### Backup

-   export all templates
-   export selected templates
-   maintain backup metadata
-   restore templates from backup

### Diagnostics

-   malformed XML detection
-   duplicate repository detection
-   network configuration warnings
-   path sanity checks

### Recovery Support

-   detect Docker storage mode (docker.img vs folder)
-   provide recovery guidance
-   export template state before Docker rebuild

------------------------------------------------------------------------

## MVP Scope

Initial release should include:

-   plugin skeleton (.plg)
-   Unraid UI page
-   template listing
-   XML parsing
-   container mapping
-   orphan template detection
-   backup before delete
-   delete / restore workflow
-   template cloning
-   basic search and filtering

------------------------------------------------------------------------

## Plugin Architecture

Suggested layout:

source/ plugin/ unraid.template.manager.plg

package/ usr/local/emhttp/plugins/unraid.template.manager/
UnraidTemplateManager.page UnraidTemplateManager.php javascript/ css/
scripts/ api/ assets/

------------------------------------------------------------------------

## Data Model Concepts

TemplateFile - filename - path - raw_xml - parsed_fields -
validation_state

ContainerRef - id - name - image - state - networks

TemplateMatch - template_file - container_id - match_confidence

BackupSet - id - created_at - templates

Issue - severity - message - remediation_hint

------------------------------------------------------------------------

## Implementation Phases

### Phase 1

Plugin skeleton - .plg installer - menu entry - empty page rendering

### Phase 2

Template read path - read template directory - parse XML - render table

### Phase 3

Container mapping - read Docker containers - implement matching logic

### Phase 4

Safe actions - delete template - backup before delete - clone template

### Phase 5

Diagnostics - malformed XML detection - duplicate detection - Docker
mode detection

------------------------------------------------------------------------

## Success Criteria

The plugin:

-   installs cleanly as a native Unraid plugin
-   integrates with the Unraid UI
-   makes template management understandable
-   safely cleans orphaned templates
-   improves recovery readiness for Docker rebuild scenarios

------------------------------------------------------------------------

## Next Step for Codex

1.  Study the reference repo:
    https://github.com/Qballjos/docker-template-manager

2.  Study Unraid plugin examples:

    -   Community Applications
    -   unraid.patch

3.  Design native plugin structure

4.  Build plugin skeleton

5.  Implement template inventory
