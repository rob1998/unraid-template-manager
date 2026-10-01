# Unraid Template Manager

Find unused, duplicate, and broken Docker templates. Back up, export, import, and restore them from the Unraid web interface.

A native Unraid plugin. Requires **Unraid 6.12 or later**.

## Install

1. Open **Plugins → Install Plugin** in Unraid.
2. Paste this installer URL and click **Install**:

   ```text
   https://raw.githubusercontent.com/rob1998/unraid-template-manager/main/source/unraid.template.manager.plg
   ```

3. Open **Template Manager** in the Docker menu.

The installer downloads the packaged plugin and checks its checksum. No build tools or separate Docker container are needed.

## Manage templates

- **Templates:** search and filter your templates, inspect matching containers and diagnostics, export individual templates, or select several for deletion. Deletes create a backup first.
- **Tools:** back up or export all or selected templates, import XML files or template archives, download backups, and preview or restore a backup.
- **Settings:** inspect Docker storage configuration and access storage mode switching.

Template matching is heuristic. Review the listed templates before deleting or overwriting them.

## Update

Open **Plugins → Check for Updates**, then update **Unraid Template Manager** when a newer version is available. The plugin checks the same installer URL used above.

The current published package is **0.2.0**. Packages are served from this repository; there are currently no separate GitHub release downloads.

## Backups and limitations

Templates live in `/boot/config/plugins/dockerMan/templates-user`. Plugin backups and configuration live in `/boot/config/plugins/unraid.template.manager`.

Download important backups before uninstalling: the current uninstaller removes the plugin configuration directory, including its backups. Template backups do not back up container appdata or Docker image contents.

Docker storage mode switching changes `docker.cfg` and can restart Docker; it does not migrate Docker data. This feature is experimental and has not completed live validation. Keep a separate backup before using it.

## Build from source

Requires Bash, tar, Perl, and either `md5sum` or macOS `md5`.

```sh
git clone https://github.com/rob1998/unraid-template-manager.git
cd unraid-template-manager
bash scripts/build-package.sh
```

The script packages `source/plugin/usr` into `source/packages/unraid.template.manager-<version>.tgz` and updates the checksum in `source/unraid.template.manager.plg`.

For your own distribution, set `gitURL` in the installer to your hosted `source` directory before building. The package URL and update URL derive from it. Publish the matching `.plg` and `.tgz` together. Increment the installer version for each update; do not replace an already published package with different contents under the same version.

## Support

[Report an issue](https://github.com/rob1998/unraid-template-manager/issues) with your Unraid version and the steps to reproduce it. Remove credentials and private configuration from logs or template examples before attaching them.
