(function () {
  var search = document.getElementById('dtm-search');
  var table = document.getElementById('dtm-table');

  if (!search || !table) {
    return;
  }

  search.addEventListener('input', function () {
    var query = search.value.toLowerCase().trim();
    var rows = table.querySelectorAll('tbody tr');

    rows.forEach(function (row) {
      if (row.querySelector('.empty')) {
        return;
      }

      var text = row.textContent.toLowerCase();
      row.style.display = text.indexOf(query) >= 0 ? '' : 'none';
    });
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

      cloneButton.disabled = true;
      cloneButton.textContent = 'Cloning...';

      var cloneBody = new URLSearchParams();
      cloneBody.set('source_filename', sourceFilename);
      cloneBody.set('target_filename', targetInput);

      fetch('/plugins/unraid.template.manager/ajax/clone_template.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: cloneBody.toString()
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (payload) {
          if (!payload.success) {
            throw new Error(payload.error || 'Clone request failed.');
          }
          window.location.reload();
        })
        .catch(function (error) {
          window.alert(error.message);
          cloneButton.disabled = false;
          cloneButton.textContent = 'Clone';
        });
      return;
    }

    var button = event.target.closest('.dtm-delete-template');
    if (!button) {
      return;
    }

    var filename = button.getAttribute('data-filename');
    if (!filename) {
      return;
    }

    var confirmed = window.confirm(
      'Delete "' + filename + '"?\nA backup will be created first in /boot/config/plugins/unraid.template.manager/backups.'
    );
    if (!confirmed) {
      return;
    }

    button.disabled = true;
    button.textContent = 'Deleting...';

    var body = new URLSearchParams();
    body.set('filename', filename);

    fetch('/plugins/unraid.template.manager/ajax/delete_template.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      body: body.toString()
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (payload) {
        if (!payload.success) {
          throw new Error(payload.error || 'Delete request failed.');
        }
        window.location.reload();
      })
      .catch(function (error) {
        window.alert(error.message);
        button.disabled = false;
        button.textContent = 'Backup + Delete';
      });
  });
})();
