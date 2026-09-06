/* ==========================================================================
   Civil Law Firm — glossary filter
   Used only on /reference/glossary-of-land-and-revenue-terms/.

   Progressive enhancement: every term is plain HTML in a <dl>, grouped under
   headings, and reads in full with JavaScript switched off. This script only
   hides dt/dd pairs that do not match what the visitor types.
   ========================================================================== */
(function () {
  'use strict';

  var input = document.getElementById('glossary-q');
  if (!input) { return; }

  var groups = document.querySelectorAll('[data-glossary-group]');
  var empty = document.getElementById('glossary-empty');

  function pairs() {
    var out = [];
    groups.forEach(function (dl) {
      var children = dl.children;
      for (var i = 0; i < children.length; i += 2) {
        var dt = children[i];
        var dd = children[i + 1];
        if (dt && dd) { out.push([dt, dd, dl]); }
      }
    });
    return out;
  }

  var all = pairs();

  input.addEventListener('input', function () {
    var q = input.value.trim().toLowerCase();
    var anyVisible = false;
    var groupHasVisible = {};

    all.forEach(function (pair) {
      var dt = pair[0], dd = pair[1];
      var text = (dt.textContent + ' ' + dd.textContent).toLowerCase();
      var match = q === '' || text.indexOf(q) !== -1;
      dt.hidden = !match;
      dd.hidden = !match;
      if (match) { anyVisible = true; }
    });

    // Hide a group heading only if every term beneath it is hidden.
    document.querySelectorAll('.glossary-group-h').forEach(function (h) {
      var dl = h.nextElementSibling;
      if (!dl || !dl.hasAttribute('data-glossary-group')) { return; }
      var visible = Array.prototype.some.call(dl.children, function (el) {
        return el.tagName === 'DT' && !el.hidden;
      });
      h.hidden = !visible;
    });

    if (empty) { empty.hidden = anyVisible || q === ''; }
  });
})();
