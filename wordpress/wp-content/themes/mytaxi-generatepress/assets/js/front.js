(function () {
  var toggle = document.querySelector('[data-nav-toggle]');
  var nav = document.querySelector('[data-nav]');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      document.body.classList.toggle('mt-nav-open', open);
    });
    nav.addEventListener('click', function (event) {
      if (event.target.closest('a')) {
        nav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('mt-nav-open');
      }
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && nav.classList.contains('is-open')) {
        nav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('mt-nav-open');
        toggle.focus();
      }
    });
  }

  var bar = document.querySelector('[data-mobile-bar]');
  if (bar) {
    document.addEventListener('focusin', function (event) {
      if (event.target.closest('input, textarea, select')) {
        document.body.classList.add('mytaxi-input-focus');
      }
    });
    document.addEventListener('focusout', function () {
      window.setTimeout(function () {
        var active = document.activeElement;
        if (!active || !active.closest || !active.closest('input, textarea, select')) {
          document.body.classList.remove('mytaxi-input-focus');
        }
      }, 50);
    });
  }

  var header = document.querySelector('.mt-header');
  if (header) {
    var markHeader = function () {
      header.classList.toggle('is-scrolled', window.scrollY > 8);
    };
    markHeader();
    document.addEventListener('scroll', markHeader, { passive: true });
  }

  document.querySelectorAll('.mt-form').forEach(function (form) {
    preparePickers(form);
    prepareChildSeat(form);
    var tokenUrl = form.getAttribute('data-token-url');
    var tokenInput = form.querySelector('[name="mytaxi_token"]');
    var startedInput = form.querySelector('[name="mytaxi_started"]');
    if (!tokenUrl || !tokenInput || !startedInput) {
      return;
    }
    fetch(tokenUrl, { credentials: 'same-origin', cache: 'no-store' })
      .then(function (response) { return response.json(); })
      .then(function (payload) {
        if (!payload || !payload.success || !payload.data) {
          return;
        }
        tokenInput.value = payload.data.token || '';
        startedInput.value = String(payload.data.started || '');
      })
      .catch(function () {});
  });

  window.addEventListener('pageshow', function () {
    document.querySelectorAll('.mt-form [type="submit"]').forEach(function (button) {
      button.disabled = false;
    });
  });

  document.addEventListener('wpcf7beforesubmit', function (event) {
    var form = event.target;
    var button = form.querySelector('[type="submit"]');
    if (!button) {
      return;
    }
    button.dataset.label = button.value;
    button.value = 'Sending…';
    button.disabled = true;
  });

  document.addEventListener('wpcf7submit', function (event) {
    var form = event.target;
    var button = form.querySelector('[type="submit"]');
    if (button && button.dataset.label) {
      button.value = button.dataset.label;
    }
    if (button) {
      button.disabled = false;
    }
    preparePickers(form);
    prepareChildSeat(form);
  });

  document.addEventListener('wpcf7invalid', function (event) {
    var form = event.target;
    var box = form.querySelector('.wpcf7-response-output');
    var fields = form.querySelectorAll('.wpcf7-not-valid');
    if (!box || !fields.length) {
      return;
    }
    var list = document.createElement('ul');
    fields.forEach(function (field) {
      if (!field.id) {
        field.id = 'mt-field-' + (field.name || list.children.length);
      }
      var label = field.closest('label');
      var text = label ? label.innerText.replace(/\*/g, '').replace(/\s+/g, ' ').trim() : field.name;
      var item = document.createElement('li');
      var link = document.createElement('a');
      link.href = '#' + field.id;
      link.textContent = text;
      item.appendChild(link);
      list.appendChild(item);
    });
    var note = document.createElement('p');
    note.textContent = 'The request was not sent. Correct the fields below.';
    box.replaceChildren(note, list);
  });

  document.querySelectorAll('[data-send-location]').forEach(function (button) {
    button.addEventListener('click', function () {
      var number = button.getAttribute('data-whatsapp');
      if (!number) {
        return;
      }
      var openChat = function (text) {
        window.location.href = 'https://wa.me/' + number + '?text=' + encodeURIComponent(text);
      };
      if (!navigator.geolocation) {
        openChat('I need a taxi in Kranevo. I will share my location in this chat.');
        return;
      }
      button.disabled = true;
      navigator.geolocation.getCurrentPosition(function (position) {
        button.disabled = false;
        var lat = position.coords.latitude.toFixed(6);
        var lng = position.coords.longitude.toFixed(6);
        openChat('I need a taxi in Kranevo. My location: https://maps.google.com/?q=' + lat + ',' + lng);
      }, function () {
        button.disabled = false;
        openChat('I need a taxi in Kranevo. I will share my location in this chat.');
      }, { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 });
    });
  });

  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form.classList || !form.classList.contains('mt-form') || form.classList.contains('wpcf7-form')) {
      return;
    }
    var button = form.querySelector('[type="submit"]');
    var tokenInput = form.querySelector('[name="mytaxi_token"]');
    if (tokenInput && !tokenInput.value) {
      event.preventDefault();
      var note = form.querySelector('.mt-form__note');
      if (note) {
        note.textContent = 'Please wait a moment and submit the form again.';
      }
      if (button) {
        button.disabled = false;
      }
      return;
    }
    if (button) {
      button.disabled = true;
    }
  });

  function preparePickers(form) {
    var yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);
    var later = new Date();
    later.setMonth(later.getMonth() + 18);
    form.querySelectorAll('input.mt-picker').forEach(function (input) {
      if (input.dataset.ready === '1') {
        return;
      }
      input.dataset.ready = '1';
      var kind = input.classList.contains('mt-picker--time') ? 'time' : 'date';
      var label = kind === 'date' ? 'Date' : 'Time';
      var switching = false;
      var limit = function () {
        if (kind === 'date') {
          input.min = stamp(yesterday);
          input.max = stamp(later);
        }
      };
      var openPicker = function () {
        if (typeof input.showPicker !== 'function') {
          return;
        }
        try {
          input.showPicker();
        } catch (error) {}
      };
      var activate = function () {
        if (input.type === kind) {
          limit();
          openPicker();
          return;
        }
        switching = true;
        input.type = kind;
        limit();
        input.focus();
        switching = false;
        openPicker();
      };
      if (!input.value) {
        input.type = 'text';
        input.placeholder = label;
      }
      input.addEventListener('pointerdown', activate);
      input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
          activate();
        }
      });
      input.addEventListener('focus', function () {
        if (switching || input.type === kind) {
          if (input.type === kind) {
            limit();
          }
          return;
        }
        switching = true;
        window.setTimeout(function () {
          input.type = kind;
          limit();
          input.focus();
          switching = false;
          openPicker();
        }, 0);
      });
      input.addEventListener('blur', function () {
        if (switching || input.value) {
          return;
        }
        window.setTimeout(function () {
          if (switching || document.activeElement === input || input.value) {
            return;
          }
          input.type = 'text';
          input.placeholder = label;
        }, 200);
      });
    });
  }

  function prepareChildSeat(form) {
    var box = form.querySelector('input[name="mytaxi-child-seat[]"]');
    var age = form.querySelector('.mt-child-age');
    if (!box || !age || box.dataset.ready === '1') {
      return;
    }
    box.dataset.ready = '1';
    var sync = function () {
      var open = box.checked;
      age.hidden = !open;
      age.classList.toggle('is-open', open);
    };
    sync();
    box.addEventListener('change', sync);
  }

  function stamp(date) {
    var month = String(date.getMonth() + 1);
    var day = String(date.getDate());
    if (month.length < 2) {
      month = '0' + month;
    }
    if (day.length < 2) {
      day = '0' + day;
    }
    return date.getFullYear() + '-' + month + '-' + day;
  }
})();
