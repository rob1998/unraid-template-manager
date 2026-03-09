#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

echo "== Unraid Template Manager Resume Context =="
echo "Timestamp: $(date -u +"%Y-%m-%dT%H:%M:%SZ")"
echo "Root: $ROOT_DIR"
echo

echo "== Git Status =="
git status --short
echo

echo "== Current Milestone State =="
awk '/## Current Milestone State/{flag=1;next}/## Milestone Breakdown/{flag=0}flag' docs/project/MASTER_PLAN.md
echo

echo "== In Progress Tasks =="
rg -n '\| .* \| in_progress \|' docs/project/TASKS.md || true
echo

echo "== Todo Tasks =="
rg -n '\| .* \| todo \|' docs/project/TASKS.md || true
echo

echo "== Last Progress Entries =="
sed -n '1,220p' docs/project/PROGRESS.md
echo

echo "Resume checklist:"
echo "1) Read AGENTS.md, PROJECT_CONTEXT.md, REPO_BOOTSTRAP.md"
echo "2) Read docs/project/*.md"
echo "3) Continue first in_progress task"
