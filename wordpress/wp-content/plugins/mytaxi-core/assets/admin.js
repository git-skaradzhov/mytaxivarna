(function () {
  function rows(box) {
    var list = box.querySelector('[data-rows]');
    return list ? list.querySelectorAll('[data-row]') : [];
  }

  function reindex(box) {
    Array.prototype.forEach.call(rows(box), function (row, index) {
      row.querySelectorAll('[name]').forEach(function (input) {
        input.name = input.name.replace(/\[\d+\]/, '[' + index + ']');
      });
    });
  }

  document.addEventListener('click', function (event) {
    var add = event.target.closest('[data-mytaxi-add]');
    if (add) {
      var box = add.closest('[data-repeater]');
      var list = box.querySelector('[data-rows]');
      var template = box.querySelector('template');
      if (list && template) {
        list.appendChild(template.content.cloneNode(true));
        reindex(box);
      }
      return;
    }

    var remove = event.target.closest('[data-mytaxi-remove]');
    if (remove) {
      var boxRemove = remove.closest('[data-repeater]');
      var row = remove.closest('[data-row]');
      if (rows(boxRemove).length > 1) {
        row.remove();
      } else {
        row.querySelectorAll('input, textarea').forEach(function (field) {
          field.value = '';
        });
      }
      reindex(boxRemove);
      return;
    }

    var up = event.target.closest('[data-mytaxi-up]');
    if (up) {
      var current = up.closest('[data-row]');
      if (current.previousElementSibling) {
        current.parentNode.insertBefore(current, current.previousElementSibling);
        reindex(up.closest('[data-repeater]'));
      }
      return;
    }

    var down = event.target.closest('[data-mytaxi-down]');
    if (down) {
      var currentDown = down.closest('[data-row]');
      if (currentDown.nextElementSibling) {
        currentDown.parentNode.insertBefore(currentDown.nextElementSibling, currentDown);
        reindex(down.closest('[data-repeater]'));
      }
      return;
    }

    var galleryRemove = event.target.closest('[data-gallery-remove]');
    if (galleryRemove) {
      var item = galleryRemove.closest('li');
      var gallery = galleryRemove.closest('[data-gallery]');
      var input = gallery.querySelector('[data-gallery-input]');
      var id = item.getAttribute('data-id');
      input.value = input.value.split(',').filter(function (value) {
        return value && value !== id;
      }).join(',');
      item.remove();
      return;
    }

    var pick = event.target.closest('[data-gallery-pick]');
    if (pick && window.wp && window.wp.media) {
      var frame = window.wp.media({
        title: 'Снимки',
        button: { text: 'Добави' },
        multiple: true,
        library: { type: 'image' }
      });
      frame.on('select', function () {
        var gallery = pick.closest('[data-gallery]');
        var input = gallery.querySelector('[data-gallery-input]');
        var list = gallery.querySelector('[data-gallery-list]');
        var ids = input.value ? input.value.split(',') : [];
        frame.state().get('selection').each(function (attachment) {
          var attachmentId = String(attachment.id);
          if (ids.indexOf(attachmentId) !== -1) {
            return;
          }
          ids.push(attachmentId);
          var li = document.createElement('li');
          li.setAttribute('data-id', attachmentId);
          var button = document.createElement('button');
          button.type = 'button';
          button.setAttribute('data-gallery-remove', '');
          button.textContent = '×';
          var image = document.createElement('img');
          var sizes = attachment.get('sizes') || {};
          image.src = (sizes.thumbnail && sizes.thumbnail.url) || attachment.get('url');
          image.alt = attachment.get('alt') || '';
          li.appendChild(button);
          li.appendChild(image);
          list.appendChild(li);
        });
        input.value = ids.filter(Boolean).join(',');
      });
      frame.open();
    }
  });
})();
