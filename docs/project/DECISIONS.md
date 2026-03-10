# DECISIONS.md

## ADR-001 - Use native Unraid plugin architecture

- Date: 2026-03-08
- Context:
  - Project requirements explicitly target native Unraid integration.
  - Reference implementation is feature-rich but architected as Flask + React in a Docker container.
- Decision:
  - Build as native Unraid plugin (`.plg`, `.page`, PHP backend, modest JS).
- Alternatives considered:
  - Reusing Flask/React architecture from reference project.
  - Building an external sidecar service with API integration.
- Consequences:
  - Better UX consistency with Unraid and lower runtime complexity.
  - Need to implement server-side logic directly in PHP and align with Unraid plugin conventions.

## ADR-002 - Safety-first write model for templates

- Date: 2026-03-08
- Context:
  - Template XML files are user-critical recovery artifacts.
  - Corrupted or overwritten files can break recovery and reinstallation workflows.
- Decision:
  - Require backup-before-destructive actions and strict path/filename validation.
  - Prefer read-only inventory and diagnostics before enabling write actions.
- Alternatives considered:
  - Allow direct write/delete operations without mandatory backup.
  - Provide full free-form edit first, then add safeguards later.
- Consequences:
  - Slower rollout of destructive features, but safer behavior by default.
  - Requires backup metadata tracking and clear user-facing audit messages.

## ADR-003 - Keep container lifecycle controls out of initial scope

- Date: 2026-03-08
- Context:
  - Project scope focuses on template lifecycle, diagnostics, and recovery.
  - Container start/stop/restart are explicitly marked as debated.
- Decision:
  - Exclude container lifecycle control from Milestones 1-2 and reassess in later milestones.
- Alternatives considered:
  - Include container controls immediately (as in reference app).
- Consequences:
  - Scope remains aligned and lower-risk during early implementation.
  - If added later, decision will require explicit task and rationale update.

## ADR-004 - Use direct filesystem XML scan for initial inventory

- Date: 2026-03-08
- Context:
  - Early milestones need stable template visibility quickly.
  - dockerMan internal APIs can vary and increase coupling risk.
- Decision:
  - For Milestone 2, read `templates-user` XML files directly and parse safely with libxml error isolation.
- Alternatives considered:
  - Relying on dockerMan internals from day one.
  - Deferring inventory until deeper integration is understood.
- Consequences:
  - Faster delivery of read-only value with lower coupling risk.
  - Future milestones may still add optional dockerMan-assisted enrichment.

## ADR-005 - Centralize backup logic in a dedicated service

- Date: 2026-03-08
- Context:
  - Multiple upcoming actions (delete/clone/restore) need consistent backup behavior.
  - Scattered backup code increases risk of missing pre-action safeguards.
- Decision:
  - Add `BackupService` as a shared primitive for backup set creation, metadata writing, and filename validation.
- Alternatives considered:
  - Implement backup logic independently in each action handler.
- Consequences:
  - Safer and more testable path for destructive operations.
  - Requires action handlers to depend on service contracts before shipping write features.

## ADR-006 - Enforce backup-before-delete for template removal

- Date: 2026-03-08
- Context:
  - Template deletion is irreversible without prior copy.
  - Project safety rules require pre-destructive backup.
- Decision:
  - Template delete action must always call backup creation before unlinking files.
- Alternatives considered:
  - Optional backup toggle before delete.
  - Direct delete with warning only.
- Consequences:
  - Deletion is safer by default and auditable via backup metadata.
  - Slightly slower delete path due to mandatory copy operation.

## ADR-007 - Provide restore preview before applying backup restoration

- Date: 2026-03-08
- Context:
  - Restore operations can overwrite active template files.
  - Users need conflict visibility before data replacement.
- Decision:
  - Add dedicated preview endpoint that enumerates backup files and conflicts before restore.
- Alternatives considered:
  - One-step restore with implicit overwrite behavior.
- Consequences:
  - Safer restore workflow and clearer operator intent.
  - UI must orchestrate preview then restore requests.

## ADR-008 - Detect Docker storage mode from persistent config first

- Date: 2026-03-08
- Context:
  - Recovery guidance is needed even if Docker daemon is down or CLI is unavailable.
- Decision:
  - Use `/boot/config/docker.cfg` as primary source to infer storage mode (`vdisk`/`directory`/`unknown`).
- Alternatives considered:
  - Query daemon state only via Docker CLI.
- Consequences:
  - Recovery panel remains available in degraded states.
  - Detection must handle config-key variations defensively.

## ADR-009 - Automate package checksum sync during build

- Date: 2026-03-08
- Context:
  - `.plg` package MD5 drift causes install/update failures.
- Decision:
  - Use a build script to generate package tarball and patch the `.plg` md5 entity in one step.
- Alternatives considered:
  - Manual package build and hand-edited checksum updates.
- Consequences:
  - Lower release error rate and repeatable packaging.
  - Requires maintainers to use script workflow for packaging.

## ADR-010 - Cap Docker inventory runtime and allow cached fallback

- Date: 2026-03-09
- Context:
  - User-observed page load latency reached ~21 seconds.
  - Render path depended on synchronous `docker ps -a`.
- Decision:
  - Add command timeout for Docker inventory collection and fallback to cached last-known-good container list.
- Alternatives considered:
  - Remove container mapping from initial render.
  - Keep synchronous live query and accept long page stalls.
- Consequences:
  - Page load remains responsive even when Docker is degraded.
  - Mapping data may be stale briefly when cache fallback is used.

## ADR-011 - Implement storage mode switching through guarded docker.cfg updates

- Date: 2026-03-09
- Context:
  - Project requires native storage mode switch capability similar to reference workflows.
  - Direct daemon-level migration orchestration is high risk for initial implementation.
- Decision:
  - Implement storage mode switch by backing up and updating `/boot/config/docker.cfg` with path/mode normalization and optional Docker restart.
- Alternatives considered:
  - Expose guidance only with no switch action.
  - Attempt full migration orchestration of Docker data-root contents in-plugin.
- Consequences:
  - Provides practical in-plugin mode switching while keeping implementation bounded.
  - Operators still need to understand mode implications and validate resulting Docker state.
