(function () {
  document.documentElement.classList.add('mt-js');
  var rules = window.mytaxiBooking || null;
  var JOURNEY = [
    ['origin', 'mytaxi-origin'],
    ['destination', 'mytaxi-destination'],
    ['date', 'mytaxi-date'],
    ['time', 'mytaxi-time'],
    ['passengers', 'mytaxi-passengers'],
    ['return_date', 'mytaxi-return-date'],
    ['return_time', 'mytaxi-return-time']
  ];
  var PICKER_LABELS = {
    'mytaxi-date': 'Arrival date',
    'mytaxi-time': 'Arrival time',
    'mytaxi-return-date': 'Return date',
    'mytaxi-return-time': 'Return pickup time'
  };

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

  // Runs before Contact Form 7 sees the submit: the journey from step 1 is checked first.
  document.addEventListener('submit', function (event) {
    var form = event.target;
    var flow = form.closest ? form.closest('[data-route-flow]') : null;
    if (!flow || !form.querySelector('.mt-continue')) {
      return;
    }
    if (form.classList.contains('submitting')) {
      event.preventDefault();
      event.stopImmediatePropagation();
      return;
    }
    var errors = journeyErrors(routeJourney(form));
    var inRequest = document.documentElement.classList.contains('is-request');
    if (!inRequest || hasErrors(errors)) {
      event.preventDefault();
      event.stopImmediatePropagation();
      continueJourney(flow, form);
    }
  }, true);

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
    var response = event.detail && event.detail.apiResponse ? event.detail.apiResponse : {};
    var invalid = response.invalid_fields || [];
    // Contact Form 7 marks the fields after this event; wait for it.
    window.setTimeout(function () {
      var fields = [];
      invalid.forEach(function (item) {
        var field = form.querySelector('[name="' + item.field + '"], [name="' + item.field + '[]"]');
        if (field) {
          fields.push(field);
        }
      });
      if (!fields.length) {
        return;
      }
      var flow = form.closest('[data-route-flow]');
      var journeyField = null;
      fields.forEach(function (field) {
        if (!journeyField && field.closest('.mt-step-1')) {
          journeyField = field;
        }
      });
      if (flow && journeyField) {
        showStep(flow, form, '1');
      }
      var box = form.querySelector('.wpcf7-response-output');
      if (box) {
        var list = document.createElement('ul');
        fields.forEach(function (field) {
          if (!field.id) {
            field.id = 'mt-field-' + field.name.replace(/[^a-z0-9_-]/gi, '');
          }
          var item = document.createElement('li');
          var link = document.createElement('a');
          link.href = '#' + field.id;
          link.textContent = fieldLabel(field);
          item.appendChild(link);
          list.appendChild(item);
        });
        var note = document.createElement('p');
        note.textContent = 'The request was not sent. Correct the fields below.';
        box.replaceChildren(note, list);
      }
      focusField(journeyField || fields[0]);
    }, 0);
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

  /* Booking rules. The server applies the same rules from MyTaxi\Core\Booking. */

  function sofiaToday() {
    try {
      var parts = {};
      new Intl.DateTimeFormat('en-US', {
        timeZone: rules && rules.timezone ? rules.timezone : 'Europe/Sofia',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
      }).formatToParts(new Date()).forEach(function (part) {
        parts[part.type] = part.value;
      });
      return parts.year + '-' + parts.month + '-' + parts.day;
    } catch (error) {
      return stamp(new Date());
    }
  }

  function shiftDate(ymd, days, months) {
    var p = ymd.split('-');
    var date = new Date(Date.UTC(Number(p[0]), Number(p[1]) - 1 + (months || 0), Number(p[2]) + (days || 0)));
    return date.getUTCFullYear() + '-' + pad(date.getUTCMonth() + 1) + '-' + pad(date.getUTCDate());
  }

  function earliestDate() {
    return shiftDate(sofiaToday(), 1, 0);
  }

  function latestDate() {
    return shiftDate(sofiaToday(), 0, rules && rules.maxMonths ? Number(rules.maxMonths) : 18);
  }

  function dateError(value, required) {
    if (!value) {
      return required;
    }
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value) || shiftDate(value, 0, 0) !== value) {
      return 'date_invalid';
    }
    var today = sofiaToday();
    if (value < today) {
      return 'date_past';
    }
    if (value === today) {
      return 'date_today';
    }
    if (value > latestDate()) {
      return 'date_far';
    }
    return '';
  }

  function timeError(value, required) {
    if (!value) {
      return required;
    }
    return /^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/.test(value) ? '' : 'time_invalid';
  }

  function journeyErrors(v) {
    var errors = {};
    if ('origin' in v && !v.origin) {
      errors.origin = 'origin_required';
    }
    if ('destination' in v && !v.destination) {
      errors.destination = v.destinationCode || 'destination_required';
    }
    var code = dateError(v.date, 'date_required');
    if (code) {
      errors.date = code;
    }
    code = timeError(v.time, 'time_required');
    if (code) {
      errors.time = code;
    }
    var max = rules && rules.maxPassengers ? Number(rules.maxPassengers) : 5;
    if (!v.passengers || !/^\d+$/.test(v.passengers) || Number(v.passengers) < 1) {
      errors.passengers = 'passengers_required';
    } else if (Number(v.passengers) > max) {
      errors.passengers = 'passengers_many';
    }
    if (v.return) {
      code = dateError(v.return_date, 'return_date_required');
      if (code) {
        errors.return_date = code;
      }
      code = timeError(v.return_time, 'return_time_required');
      if (code) {
        errors.return_time = code;
      }
      if (!errors.return_date && !errors.return_time && !errors.date && !errors.time &&
        v.return_date + ' ' + v.return_time.slice(0, 5) <= v.date + ' ' + v.time.slice(0, 5)) {
        errors[v.return_date === v.date ? 'return_time' : 'return_date'] = 'return_order';
      }
    }
    if (v.routeOrigin !== undefined && v.origin !== v.routeOrigin) {
      errors.origin = 'route_mismatch';
    }
    if (v.routeDestination !== undefined && v.destination !== v.routeDestination) {
      errors.destination = 'route_mismatch';
    }
    return errors;
  }

  function hasErrors(errors) {
    return Object.keys(errors).length > 0;
  }

  function message(code) {
    return rules && rules.messages && rules.messages[code] ? rules.messages[code] : code;
  }

  function label(key, fallback) {
    return rules && rules.labels && rules.labels[key] ? rules.labels[key] : fallback;
  }

  /* Field errors: the message sits next to the field and is linked with aria-describedby. */

  function errorHost(field) {
    var pair = field.closest('.mt-search__pair');
    if (pair) {
      return pair.parentNode;
    }
    return field.closest('.wpcf7-form-control-wrap') || field.closest('.mt-search__field') || field.parentNode;
  }

  function errorId(field) {
    return 'mt-error-' + (field.name || field.id).replace(/[^a-z0-9_-]/gi, '');
  }

  function setError(field, text) {
    var host = errorHost(field);
    var id = errorId(field);
    var node = document.getElementById(id);
    if (!node) {
      node = document.createElement('span');
      node.className = 'mt-field-error';
      node.id = id;
      host.appendChild(node);
    }
    node.textContent = text;
    var wrap = field.closest('.wpcf7-form-control-wrap');
    var tip = wrap ? wrap.querySelector('.wpcf7-not-valid-tip') : null;
    if (tip) {
      tip.remove();
    }
    field.classList.add('mt-invalid');
    field.setAttribute('aria-invalid', 'true');
    field.setAttribute('aria-describedby', id);
  }

  function clearError(field) {
    var host = errorHost(field);
    var node = document.getElementById(errorId(field));
    if (node) {
      node.remove();
    }
    var wrap = field.closest('.wpcf7-form-control-wrap');
    var tip = wrap ? wrap.querySelector('.wpcf7-not-valid-tip') : null;
    if (tip) {
      tip.remove();
    }
    field.classList.remove('mt-invalid', 'wpcf7-not-valid');
    field.setAttribute('aria-invalid', 'false');
    field.removeAttribute('aria-describedby');
  }

  function focusField(field) {
    if (!field) {
      return;
    }
    field.scrollIntoView({ block: 'center', behavior: 'auto' });
    field.focus({ preventScroll: true });
  }

  function fieldLabel(field) {
    var own = field.getAttribute('aria-label');
    if (own) {
      return own;
    }
    var holder = field.closest('label, .mt-search__field, .mt-return-when');
    var text = holder ? holder.querySelector('.mt-search__label, .mt-label, .mt-sr, .mt-accept__control + span') : null;
    return (text ? text.textContent : field.name).replace(/\*/g, '').replace(/\s+/g, ' ').trim();
  }

  /* Route page: step 1 (journey) and step 2 (details). */

  function routeJourney(form) {
    var box = form.querySelector('input[name="mytaxi-return[]"]');
    var values = { return: !!(box && box.checked) };
    JOURNEY.forEach(function (pair) {
      if (form.querySelector('[name="' + pair[1] + '"]')) {
        values[pair[0]] = valueOf(form, pair[1]);
      }
    });
    var flow = form.closest('[data-route-flow]');
    if (flow) {
      values.routeOrigin = flow.getAttribute('data-origin');
      values.routeDestination = flow.getAttribute('data-destination');
    }
    return values;
  }

  function showJourney(flow, form, errors, focus) {
    var first = null;
    JOURNEY.forEach(function (pair) {
      var field = form.querySelector('[name="' + pair[1] + '"]');
      if (!field) {
        return;
      }
      if (errors[pair[0]]) {
        setError(field, message(errors[pair[0]]));
        first = first || field;
      } else {
        clearError(field);
      }
    });
    var direct = flow.querySelector('[data-direct]');
    if (direct) {
      direct.classList.toggle('is-highlighted', errors.date === 'date_today' || errors.return_date === 'date_today' || errors.passengers === 'passengers_many');
    }
    if (focus) {
      focusField(first);
    }
    return !first;
  }

  function showStep(flow, form, step) {
    var root = document.documentElement;
    if (step === '2') {
      root.classList.add('is-request');
    } else {
      root.classList.remove('is-request');
    }
    writeDraft(flow, form, step);
  }

  function continueJourney(flow, form) {
    form.dataset.attempted = '1';
    var errors = journeyErrors(routeJourney(form));
    if (hasErrors(errors)) {
      showStep(flow, form, '1');
      showJourney(flow, form, errors, true);
      return;
    }
    fillSummary(flow, form);
    showStep(flow, form, '2');
    flow.scrollIntoView({ block: 'start', behavior: 'auto' });
    var title = flow.querySelector('.mt-request-head__title');
    if (title) {
      title.setAttribute('tabindex', '-1');
      title.focus({ preventScroll: true });
    }
  }

  function prepareRouteFlow(form) {
    var flow = form.closest('[data-route-flow]');
    var button = form.querySelector('.mt-continue');
    if (!flow || !button || button.dataset.ready === '1') {
      return;
    }
    button.dataset.ready = '1';
    ['mytaxi-origin', 'mytaxi-destination', 'mytaxi-date', 'mytaxi-time', 'mytaxi-passengers'].forEach(function (name) {
      var field = form.querySelector('[name="' + name + '"]');
      if (field) {
        field.setAttribute('aria-required', 'true');
      }
    });

    var draft = readDraft(flow);
    applyDraft(flow, form, draft);
    ['origin', 'destination'].forEach(function (key) {
      var field = form.querySelector('[name="mytaxi-' + key + '"]');
      if (field) {
        field.value = flow.getAttribute('data-' + key) || '';
        field.readOnly = true;
      }
    });
    fillSummary(flow, form);
    var errors = journeyErrors(routeJourney(form));
    if (draft.step === '2' && !hasErrors(errors)) {
      document.documentElement.classList.add('is-request');
    } else if (draft.step === '2') {
      form.dataset.attempted = '1';
      showJourney(flow, form, errors, false);
    } else if (draft['mytaxi-date'] || draft['mytaxi-time']) {
      // Values carried over from the homepage: flag only what is wrong, not what is still empty.
      Object.keys(errors).forEach(function (key) {
        if (/_required$/.test(errors[key])) {
          delete errors[key];
        }
      });
      showJourney(flow, form, errors, false);
    }

    var refresh = function () {
      writeDraft(flow, form, document.documentElement.classList.contains('is-request') ? '2' : '1');
      fillSummary(flow, form);
      if (form.dataset.attempted === '1') {
        // Contact Form 7 resets field state on change; apply ours after it.
        window.setTimeout(function () {
          showJourney(flow, form, journeyErrors(routeJourney(form)), false);
        }, 0);
      }
    };
    form.addEventListener('input', refresh);
    form.addEventListener('change', refresh);
    button.addEventListener('click', function () {
      continueJourney(flow, form);
    });
    var edit = flow.querySelector('[data-edit-journey]');
    if (edit) {
      edit.addEventListener('click', function () {
        showStep(flow, form, '1');
        flow.scrollIntoView({ block: 'start', behavior: 'auto' });
        var first = form.querySelector('[name="mytaxi-date"]') || form.querySelector('[name="mytaxi-origin"]');
        if (first) {
          first.focus({ preventScroll: true });
        }
      });
    }
  }

  function draftKey(flow) {
    return 'mytaxi-draft-' + (flow.getAttribute('data-route') || 'route');
  }

  function readDraft(flow) {
    try {
      var data = JSON.parse(window.sessionStorage.getItem(draftKey(flow)) || '{}');
      return data && typeof data === 'object' ? data : {};
    } catch (error) {
      return {};
    }
  }

  function writeDraft(flow, form, step) {
    var data = { step: step || '1' };
    form.querySelectorAll('input, select, textarea').forEach(function (field) {
      if (!field.name || field.type === 'hidden' || field.type === 'submit' || field.name === 'mytaxi-company') {
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

  function applyDraft(flow, form, data) {
    Object.keys(data).forEach(function (name) {
      if (name === 'step' || name === 'mytaxi-origin' || name === 'mytaxi-destination') {
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
      if (field.tagName === 'SELECT' && data[name] && !field.querySelector('option[value="' + String(data[name]).replace(/"/g, '') + '"]')) {
        return;
      }
      if (!field.value && data[name]) {
        field.value = data[name];
        field.dispatchEvent(new Event('change'));
      }
    });
  }

  function readableDate(ymd) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(ymd)) {
      return ymd;
    }
    try {
      var p = ymd.split('-');
      return new Intl.DateTimeFormat('en-GB', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' })
        .format(new Date(Date.UTC(Number(p[0]), Number(p[1]) - 1, Number(p[2]))));
    } catch (error) {
      return ymd;
    }
  }

  function moment(date, time) {
    return [readableDate(date), time ? time.slice(0, 5) : ''].filter(Boolean).join(' · ');
  }

  function fillSummary(flow, form) {
    var origin = flow.getAttribute('data-origin') || '';
    var destination = flow.getAttribute('data-destination') || '';
    var when = moment(valueOf(form, 'mytaxi-date'), valueOf(form, 'mytaxi-time'));
    var passengers = valueOf(form, 'mytaxi-passengers');
    var box = form.querySelector('input[name="mytaxi-return[]"]');
    var returning = !!(box && box.checked);
    var back = moment(valueOf(form, 'mytaxi-return-date'), valueOf(form, 'mytaxi-return-time'));
    var localTime = ' (' + label('localTime', 'local time') + ')';
    setSummary(flow, 'route', origin && destination ? origin + ' → ' + destination : origin || destination);
    setSummary(flow, 'when', when ? when + localTime : '—');
    setSummary(flow, 'passengers', passengers || '—');
    setSummary(flow, 'return', returning ? (back ? back + localTime : label('returnAsked', 'Return requested')) : label('notRequested', 'Not requested'));
    renderQuote(flow, returning);
  }

  function renderQuote(flow, returning) {
    var holder = flow.querySelector('[data-summary-quote]');
    if (!holder || !rules) {
      return;
    }
    var amount = parseFloat(flow.getAttribute('data-price-amount') || '');
    var text = flow.getAttribute('data-price-text') || '';
    var lines = [];
    var note = '';
    if (!isNaN(amount) && amount > 0 && text) {
      if (returning) {
        lines.push([label('outbound'), text], [label('return'), text], [label('total'), euro(amount * 2)]);
      } else {
        lines.push([label('priceForCar'), text]);
      }
    } else {
      lines.push([label('price'), label('priceOnRequest')]);
      if (returning) {
        lines.push([label('returnShort'), label('returnAsked')]);
      }
      note = returning ? label('noteBoth') : label('noteOne');
    }
    var list = document.createElement('dl');
    if (!note) {
      list.setAttribute('data-confirmed', '');
    }
    lines.forEach(function (line) {
      var row = document.createElement('div');
      var dt = document.createElement('dt');
      var dd = document.createElement('dd');
      dt.textContent = line[0];
      dd.textContent = line[1];
      row.append(dt, dd);
      list.appendChild(row);
    });
    var nodes = [list];
    if (note) {
      var p = document.createElement('p');
      p.textContent = note;
      nodes.push(p);
    }
    holder.replaceChildren.apply(holder, nodes);
  }

  function euro(amount) {
    var value = Math.round(amount * 100) / 100;
    return '€' + (value % 1 === 0 ? String(value) : value.toFixed(2));
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

  /* Form controls. */

  function preparePickers(form) {
    var airport = !!form.querySelector('.mt-continue');
    form.querySelectorAll('input.mt-picker').forEach(function (input) {
      if (input.dataset.ready === '1') {
        return;
      }
      input.dataset.ready = '1';
      var kind = input.classList.contains('mt-picker--time') ? 'time' : 'date';
      var placeholder = kind === 'date' ? 'Date' : 'Time';
      if (!input.closest('label') && PICKER_LABELS[input.name] && !input.getAttribute('aria-label')) {
        input.setAttribute('aria-label', PICKER_LABELS[input.name]);
      }
      var switching = false;
      var limit = function () {
        if (kind === 'date') {
          input.min = airport ? earliestDate() : sofiaToday();
          input.max = latestDate();
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
        input.placeholder = placeholder;
      }
      input.addEventListener('change', function () {
        if (input.value && input.type !== kind && !switching) {
          input.type = kind;
          limit();
        }
      });
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
          input.placeholder = placeholder;
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
        input.setAttribute('aria-required', open ? 'true' : 'false');
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
        input.setAttribute('aria-required', box.checked ? 'true' : 'false');
      });
    };
    sync();
    box.addEventListener('change', sync);
  }

  /* Homepage search: same rules as step 1, then the values travel to the route page. */

  function prepareSearch(form) {
    var from = form.querySelector('[data-search-from]');
    var to = form.querySelector('[data-search-to]');
    var date = form.querySelector('[data-search-date]');
    var time = form.querySelector('[data-search-time]');
    var passengers = form.querySelector('[data-search-passengers]');
    var direct = form.parentNode.querySelector('[data-search-direct]');
    if (!from || !to) {
      return;
    }
    if (date) {
      date.min = earliestDate();
      date.max = latestDate();
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
    var chosenRoute = function () {
      var option = to.options[to.selectedIndex];
      if (option && option.value) {
        return option;
      }
      return Array.prototype.filter.call(to.options, function (item) {
        return item.value && !item.disabled;
      })[0] || null;
    };
    var fields = { destination: to, date: date, time: time, passengers: passengers };
    var check = function (focus) {
      var option = to.options[to.selectedIndex];
      var errors = journeyErrors({
        destination: option && option.value ? option.value : '',
        destinationCode: 'destination_choose',
        date: date ? date.value : '',
        time: time ? time.value : '',
        passengers: passengers ? passengers.value : ''
      });
      var first = null;
      Object.keys(fields).forEach(function (key) {
        var field = fields[key];
        if (!field) {
          return;
        }
        if (errors[key]) {
          setError(field, message(errors[key]));
          first = first || field;
        } else {
          clearError(field);
        }
      });
      if (direct) {
        var show = errors.date === 'date_today' || errors.passengers === 'passengers_many';
        var route = chosenRoute();
        direct.hidden = !show || !route;
        if (route) {
          ['whatsapp', 'viber', 'phone'].forEach(function (channel) {
            var link = direct.querySelector('[data-channel="' + channel + '"]');
            var href = route.getAttribute('data-' + channel) || '';
            if (link) {
              link.hidden = !href;
              link.href = href || '#';
            }
          });
        }
      }
      if (focus) {
        focusField(first);
      }
      return !first;
    };
    filter();
    from.addEventListener('change', function () {
      filter();
      if (form.dataset.attempted === '1') {
        check(false);
      }
    });
    form.addEventListener('change', function () {
      if (form.dataset.attempted === '1') {
        check(false);
      }
    });
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      form.dataset.attempted = '1';
      if (!check(true)) {
        return;
      }
      var option = to.options[to.selectedIndex];
      var data = {
        step: '1',
        'mytaxi-date': date ? date.value : '',
        'mytaxi-time': time ? time.value : '',
        'mytaxi-passengers': passengers ? passengers.value : ''
      };
      try {
        window.sessionStorage.setItem('mytaxi-draft-' + option.getAttribute('data-route'), JSON.stringify(data));
      } catch (error) {}
      window.location.href = option.value;
    });
  }

  function pad(value) {
    value = String(value);
    return value.length < 2 ? '0' + value : value;
  }

  function stamp(date) {
    return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
  }
})();
