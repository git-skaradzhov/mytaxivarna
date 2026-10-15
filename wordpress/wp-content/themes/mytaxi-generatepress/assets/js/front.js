(function () {
  document.documentElement.classList.add('mt-js');
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
    prepareReturn(form);
    prepareRouteFlow(form);
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
    prepareReturn(form);
  });

  document.addEventListener('wpcf7mailsent', function (event) {
    var flow = event.target.closest('[data-route-flow]');
    if (!flow) {
      return;
    }
    try {
      window.sessionStorage.removeItem(draftKey(flow));
    } catch (error) {}
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

  document.querySelectorAll('[data-transfer-search]').forEach(prepareSearch);

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
    var age = form.querySelector('.mt-child-age');
    if (!age || age.dataset.ready === '1') {
      return;
    }
    var box = form.querySelector('input[name="mytaxi-child-seat[]"]');
    var select = form.querySelector('select[name="mytaxi-child-seat"]');
    if (!box && !select) {
      return;
    }
    age.dataset.ready = '1';
    var sync = function () {
      var open = box ? box.checked : select.value === 'Yes';
      age.hidden = !open;
      age.classList.toggle('is-open', open);
      var input = age.querySelector('input, textarea');
      if (input) {
        input.required = open;
      }
    };
    sync();
    if (box) {
      box.addEventListener('change', sync);
    }
    if (select) {
      select.addEventListener('change', sync);
    }
  }

  function prepareReturn(form) {
    var box = form.querySelector('input[name="mytaxi-return[]"]');
    var when = form.querySelector('.mt-return-when');
    if (!box || !when || box.dataset.ready === '1') {
      return;
    }
    box.dataset.ready = '1';
    var sync = function () {
      when.hidden = !box.checked;
      when.querySelectorAll('input').forEach(function (input) {
        input.required = box.checked;
      });
    };
    sync();
    box.addEventListener('change', sync);
  }

  function prepareSearch(form) {
    var from = form.querySelector('[data-search-from]');
    var to = form.querySelector('[data-search-to]');
    var date = form.querySelector('[data-search-date]');
    if (!from || !to) {
      return;
    }
    if (date) {
      var later = new Date();
      later.setMonth(later.getMonth() + 18);
      date.min = stamp(new Date());
      date.max = stamp(later);
    }
    var filter = function () {
      Array.prototype.forEach.call(to.options, function (option) {
        if (!option.value) {
          return;
        }
        var match = option.getAttribute('data-origin') === from.value;
        option.hidden = !match;
        option.disabled = !match;
      });
      var chosen = to.options[to.selectedIndex];
      if (chosen && chosen.disabled) {
        to.value = '';
      }
    };
    filter();
    from.addEventListener('change', filter);
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var option = to.options[to.selectedIndex];
      if (!option || !option.value) {
        to.setCustomValidity('Choose your destination.');
        to.reportValidity();
        to.focus();
        return;
      }
      to.setCustomValidity('');
      var data = { step: '1' };
      var time = form.querySelector('[data-search-time]');
      var passengers = form.querySelector('[data-search-passengers]');
      if (date && date.value) {
        data['mytaxi-date'] = date.value;
      }
      if (time && time.value) {
        data['mytaxi-time'] = time.value;
      }
      if (passengers && passengers.value) {
        data['mytaxi-passengers'] = passengers.value;
      }
      try {
        window.sessionStorage.setItem('mytaxi-draft-' + option.getAttribute('data-route'), JSON.stringify(data));
      } catch (error) {}
      window.location.href = option.value;
    });
    to.addEventListener('change', function () {
      to.setCustomValidity('');
    });
  }

  function draftKey(flow) {
    return 'mytaxi-draft-' + (flow.getAttribute('data-route') || 'route');
  }

  function readDraft(flow) {
    try {
      return JSON.parse(window.sessionStorage.getItem(draftKey(flow)) || '{}');
    } catch (error) {
      return {};
    }
  }

  function writeDraft(flow, form, step) {
    var data = { step: step || '1' };
    form.querySelectorAll('input, select, textarea').forEach(function (field) {
      if (!field.name || field.type === 'hidden' || field.name === 'mytaxi-company') {
        return;
      }
      if (field.type === 'checkbox') {
        data[field.name] = field.checked ? '1' : '';
        return;
      }
      data[field.name] = field.value;
    });
    try {
      window.sessionStorage.setItem(draftKey(flow), JSON.stringify(data));
    } catch (error) {}
  }

  function applyDraft(flow, form) {
    var data = readDraft(flow);
    Object.keys(data).forEach(function (name) {
      if (name === 'step') {
        return;
      }
      var field = form.querySelector('[name="' + name.replace(/"/g, '') + '"]');
      if (!field) {
        return;
      }
      if (field.type === 'checkbox') {
        field.checked = data[name] === '1';
        field.dispatchEvent(new Event('change'));
        return;
      }
      if (!field.value && data[name]) {
        field.value = data[name];
        field.dispatchEvent(new Event('change'));
      }
    });
    return data.step === '2';
  }

  function fillSummary(flow, form) {
    var origin = valueOf(form, 'mytaxi-origin') || flow.getAttribute('data-origin') || '';
    var destination = valueOf(form, 'mytaxi-destination') || flow.getAttribute('data-destination') || '';
    var when = [valueOf(form, 'mytaxi-date'), valueOf(form, 'mytaxi-time')].filter(Boolean).join(' · ');
    var passengers = valueOf(form, 'mytaxi-passengers');
    var returning = form.querySelector('input[name="mytaxi-return[]"]');
    var back = [valueOf(form, 'mytaxi-return-date'), valueOf(form, 'mytaxi-return-time')].filter(Boolean).join(' · ');
    setSummary(flow, 'route', origin && destination ? origin + ' → ' + destination : origin || destination);
    setSummary(flow, 'when', when ? when + ' (local time)' : '—');
    setSummary(flow, 'passengers', passengers || '—');
    setSummary(flow, 'return', returning && returning.checked ? (back || 'Requested') : 'Not requested');
    setSummary(flow, 'price', flow.getAttribute('data-price') || '');
  }

  function valueOf(form, name) {
    var field = form.querySelector('[name="' + name + '"]');
    return field ? String(field.value || '').trim() : '';
  }

  function setSummary(flow, key, text) {
    var node = flow.querySelector('[data-summary="' + key + '"]');
    if (node) {
      node.textContent = text;
    }
  }

  function prepareRouteFlow(form) {
    var flow = form.closest('[data-route-flow]');
    var button = form.querySelector('.mt-continue');
    if (!flow || !button || button.dataset.ready === '1') {
      return;
    }
    button.dataset.ready = '1';
    var reopen = applyDraft(flow, form);
    fillSummary(flow, form);
    if (reopen) {
      document.documentElement.classList.add('is-request');
    }
    form.addEventListener('input', function () {
      writeDraft(flow, form, document.documentElement.classList.contains('is-request') ? '2' : '1');
      fillSummary(flow, form);
    });
    form.addEventListener('change', function () {
      writeDraft(flow, form, document.documentElement.classList.contains('is-request') ? '2' : '1');
      fillSummary(flow, form);
    });
    button.addEventListener('click', function () {
      var step = form.querySelector('.mt-step-1');
      var fields = step ? step.querySelectorAll('input, select, textarea') : [];
      var firstInvalid = null;
      fields.forEach(function (field) {
        if (firstInvalid || field.disabled || field.type === 'hidden' || field.closest('[hidden]')) {
          return;
        }
        if (!field.checkValidity()) {
          firstInvalid = field;
        }
      });
      if (firstInvalid) {
        firstInvalid.reportValidity();
        firstInvalid.focus();
        return;
      }
      fillSummary(flow, form);
      writeDraft(flow, form, '2');
      document.documentElement.classList.add('is-request');
      window.scrollTo(0, 0);
      var name = form.querySelector('[name="mytaxi-name"]');
      if (name) {
        name.focus();
      }
    });
    var edit = flow.querySelector('[data-edit-journey]');
    if (edit) {
      edit.addEventListener('click', function () {
        document.documentElement.classList.remove('is-request');
        writeDraft(flow, form, '1');
        window.scrollTo(0, 0);
        var pickup = form.querySelector('[name="mytaxi-origin"]');
        if (pickup) {
          pickup.focus();
        }
      });
    }
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
