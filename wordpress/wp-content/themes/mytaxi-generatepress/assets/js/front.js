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

  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form.classList || !form.classList.contains('mt-form')) {
      return;
    }
    var button = form.querySelector('[type="submit"]');
    if (button) {
      button.disabled = true;
    }
  });
})();
