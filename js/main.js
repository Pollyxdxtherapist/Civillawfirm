/* ==========================================================================
   Civil Law Firm — civillawfirm.in
   The only JavaScript on the site. Plain vanilla JS, no libraries, loaded with
   `defer` so it never holds up the page.

   IMPORTANT: this file only ENHANCES pages that are already complete in the
   HTML. It never writes page content. If JavaScript is switched off, or a
   search engine ignores it, every page still reads in full. Please keep it
   that way.

   It does five small jobs:
     1. Opens and closes the menu on small screens
     2. Shows the Bar Council of India acknowledgement on a first visit
     3. Checks the enquiry form before it is sent, and reports the result
     4. Builds a WhatsApp message from whatever was typed into the form
     5. Sends the careers application without leaving the page
   ========================================================================== */
(function () {
  'use strict';

  /* ------------------------------------------------------------------
     1. Mobile menu
     ------------------------------------------------------------------ */
  var toggle = document.querySelector('.nav-toggle');
  var nav = document.getElementById('site-nav');

  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      var label = open ? toggle.getAttribute('data-label-close')
                       : toggle.getAttribute('data-label-open');
      if (label) { toggle.setAttribute('aria-label', label); }
    });

    // Escape closes the menu.
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && nav.classList.contains('is-open')) {
        nav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', toggle.getAttribute('data-label-open') || '');
        toggle.focus();
      }
    });
  }

  /* ------------------------------------------------------------------
     2. Acknowledgement overlay (Bar Council of India)

     The overlay sits on top of the finished page and starts hidden. We show
     it once each time the website is opened. Acceptance is kept in
     sessionStorage, which the browser empties by itself when the visitor
     closes the site -- so moving between pages, or reloading one, does not
     ask again, but coming back later does. Nothing is sent anywhere, and
     nothing is left behind on the device afterwards.
     ------------------------------------------------------------------ */
  var STORE_KEY = 'clf-acknowledgement-session';
  var OLD_KEY = 'clf-acknowledgement-v1';   /* the earlier permanent record */
  var gate = document.getElementById('gate');

  /* The acknowledgement used to be remembered for good. Clear that old entry
     so nothing of ours is left sitting in local storage for ever. */
  try { window.localStorage.removeItem(OLD_KEY); } catch (err) { /* ignore */ }

  function alreadyAccepted() {
    try { return window.sessionStorage.getItem(STORE_KEY) === 'yes'; }
    catch (err) { return false; }   // private browsing, storage disabled, etc.
  }

  function remember() {
    try { window.sessionStorage.setItem(STORE_KEY, 'yes'); } catch (err) { /* ignore */ }
  }

  if (gate) {
    var form = document.getElementById('gate-form');
    var box = document.getElementById('gate-accept');
    var error = document.getElementById('gate-error');

    if (!alreadyAccepted()) {
      gate.hidden = false;
      document.body.classList.add('gate-open');
      if (box) { box.focus(); }
    }

    function closeGate() {
      gate.hidden = true;
      document.body.classList.remove('gate-open');
    }

    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (box && !box.checked) {
          if (error) { error.hidden = false; }
          if (box) { box.focus(); }
          return;
        }
        if (error) { error.hidden = true; }
        remember();
        closeGate();
        var main = document.getElementById('main');
        if (main) { main.setAttribute('tabindex', '-1'); main.focus(); }
      });
    }

    if (box && error) {
      box.addEventListener('change', function () {
        if (box.checked) { error.hidden = true; }
      });
    }
  }

  /* ------------------------------------------------------------------
     5. Careers application form (Careers page only)

     The form posts to /api/apply, which is this site's own code, so the
     applicant never leaves the page. With JavaScript switched off the same
     form still posts normally and the server answers with a plain page --
     nothing here is required for it to work.

     The wording of any message shown belongs to the page, not to this
     script, so the Hindi and Bengali pages stay in their own language.
     ------------------------------------------------------------------ */
  var cf = document.getElementById('careers-form');
  if (cf) {
    var cOk = document.getElementById('careers-ok');
    var cBad = document.getElementById('careers-bad');
    var cBtn = document.getElementById('c-submit');

    cf.addEventListener('submit', function (ev) {
      /* Let the browser show its own messages for empty or malformed fields. */
      if (cf.checkValidity && !cf.checkValidity()) { return; }
      ev.preventDefault();

      if (cOk) { cOk.hidden = true; }
      if (cBad) { cBad.hidden = true; }

      var label = '';
      if (cBtn) {
        label = cBtn.textContent;
        cBtn.disabled = true;
        cBtn.textContent = cf.getAttribute('data-sending') || label;
      }

      fetch(cf.getAttribute('action'), {
        method: 'POST',
        body: new FormData(cf),
        headers: { 'Accept': 'application/json' }
      })
        .then(function (res) { return res.json().catch(function () { return {}; }); })
        .then(function (out) {
          if (out && out.success === true) {
            if (cOk) { cOk.hidden = false; }
            cf.reset();
          } else if (cBad) {
            cBad.hidden = false;
          }
        })
        .catch(function () { if (cBad) { cBad.hidden = false; } })
        .then(function () {
          if (cBtn) { cBtn.disabled = false; cBtn.textContent = label; }
        });
    });
  }

  /* ------------------------------------------------------------------
     3 & 4. Enquiry form (Contact page only)
     ------------------------------------------------------------------ */
  var ef = document.getElementById('enquiry-form');
  if (!ef) { return; }

  var fName = document.getElementById('f-name');
  var fPhone = document.getElementById('f-phone');
  var fMessage = document.getElementById('f-message');
  var eName = document.getElementById('e-name');
  var ePhone = document.getElementById('e-phone');
  var eMessage = document.getElementById('e-message');
  var eHtml = document.getElementById('e-html');
  var okBox = document.getElementById('form-ok');
  var badBox = document.getElementById('form-bad');
  var submitBtn = document.getElementById('f-submit');
  var waBtn = document.getElementById('f-wa');

  function show(el, on) { if (el) { el.hidden = !on; } }

  // Remove anything that looks like a tag, so nothing odd is ever emailed on.
  function clean(value) {
    return String(value == null ? '' : value).replace(/[<>]/g, '').trim();
  }

  function hasTags(value) {
    return /[<>]/.test(String(value == null ? '' : value));
  }

  function digits(value) {
    return String(value == null ? '' : value).replace(/\D/g, '');
  }

  /* Returns true when the three fields are usable. */
  function validate() {
    var ok = true;
    var tagged = false;

    [fName, fPhone, fMessage].forEach(function (el) {
      if (el && hasTags(el.value)) { tagged = true; }
    });
    show(eHtml, tagged);
    if (tagged) { ok = false; }

    var nameOk = fName && clean(fName.value).length >= 2;
    show(eName, !nameOk);
    if (!nameOk) { ok = false; }

    // A plausible phone number: 7 to 15 digits (allows +91, spaces, dashes).
    var d = fPhone ? digits(fPhone.value) : '';
    var phoneOk = d.length >= 7 && d.length <= 15;
    show(ePhone, !phoneOk);
    if (!phoneOk) { ok = false; }

    var msgOk = fMessage && clean(fMessage.value).length >= 5;
    show(eMessage, !msgOk);
    if (!msgOk) { ok = false; }

    return ok;
  }

  /* --- 4. "Send on WhatsApp": no service needed, just a wa.me link --- */
  if (waBtn) {
    waBtn.addEventListener('click', function () {
      if (!validate()) { return; }
      var number = ef.getAttribute('data-wa-number') || '919123305701';
      var lines = [
        (ef.getAttribute('data-l-name') || 'Name') + ': ' + clean(fName.value),
        (ef.getAttribute('data-l-phone') || 'Phone') + ': ' + clean(fPhone.value),
        (ef.getAttribute('data-l-message') || 'Matter') + ': ' + clean(fMessage.value)
      ];
      var url = 'https://wa.me/' + number + '?text=' + encodeURIComponent(lines.join('\n'));
      window.open(url, '_blank', 'noopener');
    });
  }

  /* --- 3. Normal submit: post to the form service in the background --- */
  ef.addEventListener('submit', function (e) {
    show(okBox, false);
    show(badBox, false);

    if (!validate()) {
      e.preventDefault();
      return;
    }

    // If the browser cannot do a background request, let the form submit
    // normally — the form service shows its own thank-you page.
    if (!window.fetch || !window.FormData) { return; }

    e.preventDefault();

    var data = new FormData(ef);
    data.set('name', clean(fName.value));
    data.set('phone', clean(fPhone.value));
    data.set('message', clean(fMessage.value));

    var original = submitBtn ? submitBtn.textContent : '';
    if (submitBtn) {
      submitBtn.disabled = true;
      var sending = ef.getAttribute('data-sending');
      submitBtn.textContent = sending || original;
    }

    fetch(ef.getAttribute('action'), {
      method: 'POST',
      body: data,
      headers: { 'Accept': 'application/json' }
    })
      .then(function (res) { return res.json().catch(function () { return {}; }); })
      .then(function (out) {
        if (out && (out.success === true || out.success === 'true')) {
          show(okBox, true);
          ef.reset();
        } else {
          show(badBox, true);
        }
      })
      .catch(function () { show(badBox, true); })
      .then(function () {
        if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = original; }
      });
  });
})();
