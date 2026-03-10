(function () {
  function initTemplateManager() {
    var root = document.querySelector('.dtm-wrap');
    if (!root || root.getAttribute('data-dtm-init') === '1') {
      return;
    }
    root.setAttribute('data-dtm-init', '1');

    var table = document.getElementById('dtm-table');
    if (!table) {
      return;
    }

    var searchInput = document.getElementById('dtm-search');
    var templateStateFilter = document.getElementById('dtm-filter-template-state');
    var mappingStateFilter = document.getElementById('dtm-filter-mapping-state');
    var severityFilter = document.getElementById('dtm-filter-severity');
    var clearFiltersButton = document.getElementById('dtm-clear-filters');
    var selectAllVisibleCheckbox = document.getElementById('dtm-select-all');
    var selectPageCheckbox = document.getElementById('dtm-select-page');
    var selectedCountEl = document.getElementById('dtm-selected-count');
    var backupAllButton = document.getElementById('dtm-backup-all');
    var backupSelectedButton = document.getElementById('dtm-backup-selected');
    var exportAllButton = document.getElementById('dtm-export-all');
    var exportSelectedButton = document.getElementById('dtm-export-selected');
    var bulkDeleteButton = document.getElementById('dtm-bulk-delete');
    var restoreBackupSelect = document.getElementById('dtm-restore-backup-id');
    var refreshBackupsButton = document.getElementById('dtm-refresh-backups');
    var downloadBackupButton = document.getElementById('dtm-download-backup');
    var previewRestoreButton = document.getElementById('dtm-preview-restore');
    var restoreBackupButton = document.getElementById('dtm-restore-backup');
    var restoreOverwriteCheckbox = document.getElementById('dtm-restore-overwrite');
    var importForm = document.getElementById('dtm-import-form');
    var importFileInput = document.getElementById('dtm-import-file');
    var importOverwriteCheckbox = document.getElementById('dtm-import-overwrite');
    var storageForm = document.getElementById('dtm-storage-form');
    var feedbackEl = document.getElementById('dtm-feedback');

    var modal = document.getElementById('dtm-confirm-modal');
    var modalTitle = document.getElementById('dtm-modal-title');
    var modalMessage = document.getElementById('dtm-modal-message');
    var modalItems = document.getElementById('dtm-modal-items');
    var modalNote = document.getElementById('dtm-modal-note');
    var modalCancel = document.getElementById('dtm-modal-cancel');
    var modalConfirm = document.getElementById('dtm-modal-confirm');

    var tabButtons = Array.prototype.slice.call(root.querySelectorAll('.dtm-tab-button'));
    var tabPanels = Array.prototype.slice.call(root.querySelectorAll('.dtm-tab-panel'));

    function getCsrfToken() {
      if (typeof window.csrf_token === 'string' && window.csrf_token.trim() !== '') {
        return window.csrf_token.trim();
      }
      if (typeof window.csrfToken === 'string' && window.csrfToken.trim() !== '') {
        return window.csrfToken.trim();
      }

      var hidden = document.querySelector('input[name="csrf_token"]');
      if (hidden && typeof hidden.value === 'string' && hidden.value.trim() !== '') {
        return hidden.value.trim();
      }

      var cookieMatch = document.cookie.match(/(?:^|;\\s*)csrf_token=([^;]+)/);
      if (cookieMatch && cookieMatch[1]) {
        return decodeURIComponent(cookieMatch[1]);
      }

      return '';
    }

    function showFeedback(message, type) {
      if (!feedbackEl) {
        if (message) {
          window.alert(message);
        }
        return;
      }

      feedbackEl.className = 'dtm-feedback ' + (type ? 'dtm-feedback-' + type : '');
      feedbackEl.textContent = message || '';
    }

    function activateTab(tabName) {
      tabButtons.forEach(function (button) {
        var active = button.getAttribute('data-tab') === tabName;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-selected', active ? 'true' : 'false');
      });

      tabPanels.forEach(function (panel) {
        var active = panel.getAttribute('data-tab-panel') === tabName;
        panel.classList.toggle('is-active', active);
        panel.hidden = !active;
      });
    }

    tabButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        activateTab(button.getAttribute('data-tab') || 'templates');
      });
    });

    function getTemplateRows() {
      return Array.prototype.slice.call(table.querySelectorAll('tbody tr.dtm-template-row'));
    }

    function getVisibleRows() {
      return getTemplateRows().filter(function (row) {
        return row.style.display !== 'none';
      });
    }

    function getSelectedFilenames() {
      var checked = Array.prototype.slice.call(table.querySelectorAll('tbody tr.dtm-template-row .dtm-row-select:checked'));
      return checked
        .map(function (input) {
          return (input.value || '').trim();
        })
        .filter(function (value) {
          return value !== '';
        });
    }

    function updateSelectionControls() {
      var visibleRows = getVisibleRows();
      var visibleCheckboxes = visibleRows
        .map(function (row) {
          return row.querySelector('.dtm-row-select');
        })
        .filter(Boolean);

      var checkedVisibleCount = visibleCheckboxes.filter(function (cb) {
        return cb.checked;
      }).length;

      var allVisibleSelected = visibleCheckboxes.length > 0 && checkedVisibleCount === visibleCheckboxes.length;
      if (selectAllVisibleCheckbox) {
        selectAllVisibleCheckbox.checked = allVisibleSelected;
      }
      if (selectPageCheckbox) {
        selectPageCheckbox.checked = allVisibleSelected;
      }

      var selectedCount = getSelectedFilenames().length;
      if (selectedCountEl) {
        selectedCountEl.textContent = selectedCount + ' selected';
      }

      var hasSelected = selectedCount > 0;
      if (backupSelectedButton) {
        backupSelectedButton.disabled = !hasSelected;
      }
      if (exportSelectedButton) {
        exportSelectedButton.disabled = !hasSelected;
      }
      if (bulkDeleteButton) {
        bulkDeleteButton.disabled = !hasSelected;
      }
    }

    function applyFilters() {
      var query = (searchInput && searchInput.value ? searchInput.value : '').toLowerCase().trim();
      var templateState = templateStateFilter ? templateStateFilter.value : 'all';
      var mappingState = mappingStateFilter ? mappingStateFilter.value : 'all';
      var severity = severityFilter ? severityFilter.value : 'all';

      getTemplateRows().forEach(function (row) {
        var matchesText = true;
        if (query !== '') {
          var blob = (row.getAttribute('data-filter-text') || '').toLowerCase();
          matchesText = blob.indexOf(query) >= 0;
        }

        var matchesTemplateState = templateState === 'all' || row.getAttribute('data-template-state') === templateState;
        var matchesMappingState = mappingState === 'all' || row.getAttribute('data-mapping-state') === mappingState;
        var matchesSeverity = severity === 'all' || row.getAttribute('data-severity') === severity;

        var visible = matchesText && matchesTemplateState && matchesMappingState && matchesSeverity;
        row.style.display = visible ? '' : 'none';
      });

      updateSelectionControls();
    }

    function setVisibleSelection(checked) {
      getVisibleRows().forEach(function (row) {
        var cb = row.querySelector('.dtm-row-select');
        if (cb) {
          cb.checked = checked;
        }
      });
      updateSelectionControls();
    }

    function formEncode(data) {
      var body = new URLSearchParams();
      Object.keys(data).forEach(function (key) {
        body.set(key, data[key]);
      });
      var csrfToken = getCsrfToken();
      if (csrfToken !== '') {
        body.set('csrf_token', csrfToken);
      }
      return body;
    }

    function postJson(url, data) {
      return fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: formEncode(data).toString(),
        credentials: 'same-origin'
      })
        .then(function (response) {
          return response.text().then(function (text) {
            var payload = null;
            if (text && text.trim() !== '') {
              try {
                payload = JSON.parse(text);
              } catch (error) {
                throw new Error('Invalid JSON response from server.');
              }
            }
            if (!payload || typeof payload !== 'object') {
              throw new Error('Empty response from server.');
            }
            return payload;
          });
        })
        .then(function (payload) {
          if (!payload.success) {
            throw new Error(payload.error || 'Request failed');
          }
          return payload.result || {};
        });
    }

    function withButtonBusy(button, busyText, fn) {
      if (!button) {
        return fn();
      }
      var originalText = button.textContent;
      button.disabled = true;
      button.textContent = busyText;

      return Promise.resolve()
        .then(fn)
        .then(function (result) {
          button.disabled = false;
          button.textContent = originalText;
          return result;
        })
        .catch(function (error) {
          button.disabled = false;
          button.textContent = originalText;
          throw error;
        });
    }

    function openConfirmModal(options) {
      if (!modal || !modalConfirm || !modalCancel || !modalTitle || !modalMessage || !modalItems || !modalNote) {
        return Promise.resolve(window.confirm(options.message || 'Confirm action?'));
      }

      modalTitle.textContent = options.title || 'Confirm Action';
      modalMessage.textContent = options.message || '';
      modalNote.textContent = options.note || '';
      modalConfirm.textContent = options.confirmLabel || 'Confirm';

      modalItems.innerHTML = '';
      (options.items || []).forEach(function (item) {
        var li = document.createElement('li');
        li.textContent = item;
        modalItems.appendChild(li);
      });

      modal.classList.remove('hidden');
      modal.setAttribute('aria-hidden', 'false');

      return new Promise(function (resolve) {
        function close(result) {
          modal.classList.add('hidden');
          modal.setAttribute('aria-hidden', 'true');
          modalConfirm.onclick = null;
          modalCancel.onclick = null;
          modal.onclick = null;
          document.removeEventListener('keydown', onEscape);
          resolve(result);
        }

        function onEscape(event) {
          if (event.key === 'Escape') {
            close(false);
          }
        }

        modalConfirm.onclick = function () {
          close(true);
        };
        modalCancel.onclick = function () {
          close(false);
        };
        modal.onclick = function (event) {
          var target = event.target;
          if (target && target.getAttribute('data-modal-close') === '1') {
            close(false);
          }
        };
        document.addEventListener('keydown', onEscape);
      });
    }

    function backupTemplates(filenames, triggerButton) {
      var fileArg = filenames && filenames.length > 0 ? filenames.join(',') : '';
      return withButtonBusy(triggerButton, 'Backing up...', function () {
        return postJson('/plugins/unraid.template.manager/ajax/backup_templates.php', { files: fileArg });
      })
        .then(function (result) {
          showFeedback('Backup created: ' + (result.id || 'unknown') + ' (' + (result.file_count || 0) + ' files)', 'success');
        })
        .catch(function (error) {
          showFeedback(error.message, 'error');
        });
    }

    function exportTemplates(filenames) {
      var url = '/plugins/unraid.template.manager/ajax/export_templates.php';
      if (filenames && filenames.length > 0) {
        url += '?files=' + encodeURIComponent(filenames.join(','));
      }
      window.open(url, '_blank');
      showFeedback('Export started.', 'success');
    }

    function downloadBackup(backupId) {
      var url = '/plugins/unraid.template.manager/ajax/download_backup.php?backup_id=' + encodeURIComponent(backupId);
      window.open(url, '_blank');
      showFeedback('Backup download started.', 'success');
    }

    function loadBackups(triggerButton) {
      return withButtonBusy(triggerButton, 'Loading...', function () {
        return fetch('/plugins/unraid.template.manager/ajax/list_backups.php', {
          credentials: 'same-origin'
        })
          .then(function (response) {
            return response.json();
          })
          .then(function (payload) {
            if (!payload.success) {
              throw new Error(payload.error || 'Failed to list backups');
            }
            return payload.result || [];
          });
      })
        .then(function (backups) {
          if (!restoreBackupSelect) {
            return backups;
          }

          while (restoreBackupSelect.options.length > 1) {
            restoreBackupSelect.remove(1);
          }

          backups.forEach(function (backup) {
            var option = document.createElement('option');
            option.value = backup.id || '';
            var createdAt = backup.created_at || '';
            var count = backup.file_count || 0;
            option.textContent = (backup.id || 'backup') + ' - ' + createdAt + ' (' + count + ' files)';
            restoreBackupSelect.appendChild(option);
          });

          return backups;
        })
        .catch(function (error) {
          showFeedback(error.message, 'error');
          return [];
        });
    }

    function handleSingleDelete(filename, button) {
      openConfirmModal({
        title: 'Delete Template',
        message: 'This template will be deleted:',
        items: [filename],
        note: 'A backup is always created automatically before removal.',
        confirmLabel: 'Delete Template'
      })
        .then(function (confirmed) {
          if (!confirmed) {
            return;
          }

          return withButtonBusy(button, 'Deleting...', function () {
            return postJson('/plugins/unraid.template.manager/ajax/delete_template.php', { filename: filename });
          })
            .then(function () {
              window.location.reload();
            });
        })
        .catch(function (error) {
          showFeedback(error.message, 'error');
        });
    }

    function handleBulkDelete(filenames) {
      if (!filenames || filenames.length === 0) {
        showFeedback('Select at least one template first.', 'error');
        return;
      }

      openConfirmModal({
        title: 'Delete Selected Templates',
        message: 'The following templates will be deleted:',
        items: filenames,
        note: 'A backup is always created automatically before removal.',
        confirmLabel: 'Delete ' + filenames.length + ' Templates'
      })
        .then(function (confirmed) {
          if (!confirmed) {
            return;
          }

          return withButtonBusy(bulkDeleteButton, 'Deleting...', function () {
            return postJson('/plugins/unraid.template.manager/ajax/bulk_delete_templates.php', {
              files: filenames.join(',')
            });
          })
            .then(function (result) {
              var deletedCount = (result.deleted || []).length;
              var failedCount = (result.failed || []).length;
              showFeedback(
                'Deleted ' + deletedCount + ' template(s). Failed: ' + failedCount + '. Backup: ' + ((result.backup || {}).id || 'created'),
                failedCount > 0 ? 'warning' : 'success'
              );
              window.location.reload();
            });
        })
        .catch(function (error) {
          showFeedback(error.message, 'error');
        });
    }

    if (searchInput) {
      searchInput.addEventListener('input', applyFilters);
    }
    if (templateStateFilter) {
      templateStateFilter.addEventListener('change', applyFilters);
    }
    if (mappingStateFilter) {
      mappingStateFilter.addEventListener('change', applyFilters);
    }
    if (severityFilter) {
      severityFilter.addEventListener('change', applyFilters);
    }

    if (clearFiltersButton) {
      clearFiltersButton.addEventListener('click', function () {
        if (searchInput) searchInput.value = '';
        if (templateStateFilter) templateStateFilter.value = 'all';
        if (mappingStateFilter) mappingStateFilter.value = 'all';
        if (severityFilter) severityFilter.value = 'all';
        applyFilters();
      });
    }

    if (selectAllVisibleCheckbox) {
      selectAllVisibleCheckbox.addEventListener('change', function () {
        setVisibleSelection(selectAllVisibleCheckbox.checked);
      });
    }

    if (selectPageCheckbox) {
      selectPageCheckbox.addEventListener('change', function () {
        setVisibleSelection(selectPageCheckbox.checked);
      });
    }

    table.addEventListener('change', function (event) {
      if (event.target && event.target.classList.contains('dtm-row-select')) {
        updateSelectionControls();
      }
    });

    table.addEventListener('click', function (event) {
      var cloneButton = event.target.closest('.dtm-clone-template');
      if (cloneButton) {
        var sourceFilename = cloneButton.getAttribute('data-filename');
        if (!sourceFilename) {
          return;
        }

        var targetInput = window.prompt('Clone template as (filename):', sourceFilename.replace(/\.xml$/i, '') + '-copy.xml');
        if (!targetInput) {
          return;
        }

        withButtonBusy(cloneButton, 'Cloning...', function () {
          return postJson('/plugins/unraid.template.manager/ajax/clone_template.php', {
            source_filename: sourceFilename,
            target_filename: targetInput
          });
        })
          .then(function () {
            window.location.reload();
          })
          .catch(function (error) {
            showFeedback(error.message, 'error');
          });
        return;
      }

      var exportButton = event.target.closest('.dtm-export-template');
      if (exportButton) {
        var exportFilename = exportButton.getAttribute('data-filename');
        if (!exportFilename) {
          return;
        }
        exportTemplates([exportFilename]);
        return;
      }

      var deleteButton = event.target.closest('.dtm-delete-template');
      if (deleteButton) {
        var filename = deleteButton.getAttribute('data-filename');
        if (!filename) {
          return;
        }
        handleSingleDelete(filename, deleteButton);
      }
    });

    if (backupAllButton) {
      backupAllButton.addEventListener('click', function () {
        backupTemplates([], backupAllButton);
      });
    }

    if (backupSelectedButton) {
      backupSelectedButton.addEventListener('click', function () {
        var selected = getSelectedFilenames();
        if (selected.length === 0) {
          showFeedback('Select at least one template first.', 'error');
          return;
        }
        backupTemplates(selected, backupSelectedButton);
      });
    }

    if (exportAllButton) {
      exportAllButton.addEventListener('click', function () {
        exportTemplates([]);
      });
    }

    if (exportSelectedButton) {
      exportSelectedButton.addEventListener('click', function () {
        var selected = getSelectedFilenames();
        if (selected.length === 0) {
          showFeedback('Select at least one template first.', 'error');
          return;
        }
        exportTemplates(selected);
      });
    }

    if (bulkDeleteButton) {
      bulkDeleteButton.addEventListener('click', function () {
        handleBulkDelete(getSelectedFilenames());
      });
    }

    if (refreshBackupsButton) {
      refreshBackupsButton.addEventListener('click', function () {
        loadBackups(refreshBackupsButton);
      });
    }

    if (downloadBackupButton) {
      downloadBackupButton.addEventListener('click', function () {
        var backupId = restoreBackupSelect ? restoreBackupSelect.value : '';
        if (!backupId) {
          showFeedback('Select a backup first.', 'error');
          return;
        }
        downloadBackup(backupId);
      });
    }

    if (previewRestoreButton) {
      previewRestoreButton.addEventListener('click', function () {
        var backupId = restoreBackupSelect ? restoreBackupSelect.value : '';
        if (!backupId) {
          showFeedback('Select a backup first.', 'error');
          return;
        }

        withButtonBusy(previewRestoreButton, 'Previewing...', function () {
          return fetch('/plugins/unraid.template.manager/ajax/preview_restore.php?backup_id=' + encodeURIComponent(backupId), {
            credentials: 'same-origin'
          })
            .then(function (response) {
              return response.json();
            })
            .then(function (payload) {
              if (!payload.success) {
                throw new Error(payload.error || 'Preview failed');
              }
              return payload.result || {};
            });
        })
          .then(function (result) {
            var fileCount = (result.files || []).length;
            var conflictCount = (result.conflicts || []).length;
            showFeedback('Preview: ' + fileCount + ' file(s), ' + conflictCount + ' conflict(s).', conflictCount > 0 ? 'warning' : 'success');
          })
          .catch(function (error) {
            showFeedback(error.message, 'error');
          });
      });
    }

    if (restoreBackupButton) {
      restoreBackupButton.addEventListener('click', function () {
        var backupId = restoreBackupSelect ? restoreBackupSelect.value : '';
        if (!backupId) {
          showFeedback('Select a backup first.', 'error');
          return;
        }

        var overwrite = restoreOverwriteCheckbox && restoreOverwriteCheckbox.checked ? '1' : '0';
        var confirmed = window.confirm(
          'Restore backup ' + backupId + '?\nThis will copy template files back into templates-user.' +
            (overwrite === '1' ? '\nOverwrite existing files: yes' : '\nOverwrite existing files: no')
        );
        if (!confirmed) {
          return;
        }

        withButtonBusy(restoreBackupButton, 'Restoring...', function () {
          return postJson('/plugins/unraid.template.manager/ajax/restore_backup.php', {
            backup_id: backupId,
            overwrite: overwrite
          });
        })
          .then(function (result) {
            var restoredCount = (result.restored || []).length;
            var skippedCount = (result.skipped || []).length;
            showFeedback('Restore complete. Restored: ' + restoredCount + ', skipped: ' + skippedCount + '.', skippedCount > 0 ? 'warning' : 'success');
            window.location.reload();
          })
          .catch(function (error) {
            showFeedback(error.message, 'error');
          });
      });
    }

    if (importForm) {
      importForm.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!importFileInput || !importFileInput.files || importFileInput.files.length === 0) {
          showFeedback('Select a template XML/tar archive to import.', 'error');
          return;
        }

        var formData = new FormData();
        formData.append('import_file', importFileInput.files[0]);
        formData.append('overwrite', importOverwriteCheckbox && importOverwriteCheckbox.checked ? '1' : '0');
        var csrfToken = getCsrfToken();
        if (csrfToken !== '') {
          formData.append('csrf_token', csrfToken);
        }

        var submitButton = document.getElementById('dtm-import-submit');
        withButtonBusy(submitButton, 'Importing...', function () {
          return fetch('/plugins/unraid.template.manager/ajax/import_templates.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
          })
            .then(function (response) {
              return response.json();
            })
            .then(function (payload) {
              if (!payload.success) {
                throw new Error(payload.error || 'Import failed');
              }
              return payload.result || {};
            });
        })
          .then(function (result) {
            var count = (result.imported || []).length;
            var skipped = (result.skipped_existing || []).length;
            showFeedback('Imported ' + count + ' template(s). Skipped existing: ' + skipped + '.', skipped > 0 ? 'warning' : 'success');
            window.location.reload();
          })
          .catch(function (error) {
            showFeedback(error.message, 'error');
          });
      });
    }

    if (storageForm) {
      storageForm.addEventListener('submit', function (event) {
        event.preventDefault();

        var modeEl = document.getElementById('dtm-storage-target-mode');
        var pathEl = document.getElementById('dtm-storage-target-path');
        var restartEl = document.getElementById('dtm-storage-restart');
        var mode = modeEl ? modeEl.value : '';
        var path = pathEl ? pathEl.value.trim() : '';
        var restart = restartEl && restartEl.checked ? '1' : '0';

        if (!mode || !path) {
          showFeedback('Provide a target mode and path before switching.', 'error');
          return;
        }

        openConfirmModal({
          title: 'Switch Docker Storage Mode',
          message: 'Apply this Docker storage mode change?',
          items: [
            'Target mode: ' + mode,
            'Target path: ' + path,
            'Restart Docker now: ' + (restart === '1' ? 'yes' : 'no')
          ],
          note:
            'This feature has not been fully tested in production scenarios. A backup of /boot/config/docker.cfg is created automatically before applying changes.',
          confirmLabel: 'Switch Mode'
        })
          .then(function (confirmed) {
            if (!confirmed) {
              return;
            }

            var submitButton = document.getElementById('dtm-storage-switch');
            return withButtonBusy(submitButton, 'Switching...', function () {
              return postJson('/plugins/unraid.template.manager/ajax/switch_storage_mode.php', {
                mode: mode,
                path: path,
                restart: restart
              });
            })
              .then(function (result) {
                var backupFile = result.backup_file || 'created';
                showFeedback('Storage mode updated. Config backup: ' + backupFile, 'success');
                window.location.reload();
              });
          })
          .catch(function (error) {
            showFeedback(error.message, 'error');
          });
      });
    }

    activateTab('templates');
    applyFilters();
    loadBackups(null);
    showFeedback('', '');
  }

  window.UnraidTemplateManagerInit = initTemplateManager;
  initTemplateManager();
})();
