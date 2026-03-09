#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLG_FILE="$ROOT_DIR/source/unraid.template.manager.plg"
PLUGIN_SOURCE_DIR="$ROOT_DIR/source/plugin"
PACKAGE_DIR="$ROOT_DIR/source/packages"

if [[ ! -f "$PLG_FILE" ]]; then
  echo "Missing .plg file: $PLG_FILE" >&2
  exit 1
fi

NAME="$(sed -n 's/<!ENTITY name[[:space:]]*"\([^"]*\)".*/\1/p' "$PLG_FILE" | head -n1)"
VERSION="$(sed -n 's/<!ENTITY version[[:space:]]*"\([^"]*\)".*/\1/p' "$PLG_FILE" | head -n1)"

if [[ -z "$NAME" || -z "$VERSION" ]]; then
  echo "Unable to parse name/version from $PLG_FILE" >&2
  exit 1
fi

mkdir -p "$PACKAGE_DIR"
PACKAGE_FILE="$PACKAGE_DIR/${NAME}-${VERSION}.tgz"

tar -czf "$PACKAGE_FILE" -C "$PLUGIN_SOURCE_DIR" usr

if command -v md5sum >/dev/null 2>&1; then
  PACKAGE_MD5="$(md5sum "$PACKAGE_FILE" | awk '{print $1}')"
elif command -v md5 >/dev/null 2>&1; then
  PACKAGE_MD5="$(md5 -q "$PACKAGE_FILE")"
else
  echo "Neither md5sum nor md5 is available." >&2
  exit 1
fi

perl -0777 -i -pe "s/(<!ENTITY md5\\s+\")[^\"]*(\">)/\${1}${PACKAGE_MD5}\${2}/" "$PLG_FILE"

echo "Built: $PACKAGE_FILE"
echo "MD5:   $PACKAGE_MD5"
echo "Updated md5 entity in: $PLG_FILE"

