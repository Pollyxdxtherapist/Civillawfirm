/* ==========================================================================
   Civil Law Firm — stamp duty and registration fee calculator
   Used only on /guides/stamp-duty-and-registration-fees-west-bengal/.

   Progressive enhancement: the rate tables on that page are ordinary HTML
   and are complete on their own with JavaScript switched off. This script
   only reveals and wires up the calculator that sits beneath them; nothing
   on the page depends on it to be readable.

   RATES is the single source of the figures used here. It must always match
   the two rate tables printed in the page's HTML — if one is edited, edit
   the other. Last checked against those tables: 17 August 2026.
   ========================================================================== */
(function () {
  'use strict';

  var RATES = {
    lastChecked: '2026-08-17',
    conveyance: {
      within: [{ upTo: 10000000, rate: 0.06 }, { upTo: Infinity, rate: 0.07 }],
      outside: [{ upTo: 10000000, rate: 0.05 }, { upTo: Infinity, rate: 0.06 }]
    },
    // Gift to a family member specified in the schedule: flat 0.5% regardless of location.
    giftFamily: 0.005,
    // Gift to a non-family member is charged as on a conveyance (location- and value-banded).
    // Partition deed: 0.5% of the market value of the separated share.
    partition: 0.005,
    registrationFee: 0.01
  };

  var calc = document.getElementById('sd-calc');
  if (!calc) { return; }

  function conveyanceDutyRate(value, location) {
    var bands = location === 'outside' ? RATES.conveyance.outside : RATES.conveyance.within;
    for (var i = 0; i < bands.length; i++) {
      if (value <= bands[i].upTo) { return bands[i].rate; }
    }
    return bands[bands.length - 1].rate;
  }

  function formatRupees(n) {
    var rounded = Math.round(n);
    return '₹' + rounded.toLocaleString('en-IN');
  }

  calc.hidden = false;

  var form = document.getElementById('sd-calc-form');
  var result = document.getElementById('sd-calc-result');
  var dutyLabel = document.getElementById('sd-duty-label');
  var dutyValue = document.getElementById('sd-duty-value');
  var feeLabel = document.getElementById('sd-fee-label');
  var feeValue = document.getElementById('sd-fee-value');
  var totalValue = document.getElementById('sd-total-value');

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    var value = parseFloat(document.getElementById('sd-value').value);
    if (!(value > 0)) { return; }
    var location = document.getElementById('sd-location').value;
    var instrument = document.getElementById('sd-instrument').value;

    var dutyRate, dutyLabelText;
    if (instrument === 'conveyance' || instrument === 'gift-other') {
      dutyRate = conveyanceDutyRate(value, location);
      dutyLabelText = 'Stamp duty (' + (dutyRate * 100).toFixed(0) + '% of value, as on a conveyance)';
    } else if (instrument === 'gift-family') {
      dutyRate = RATES.giftFamily;
      dutyLabelText = 'Stamp duty (' + (dutyRate * 100).toFixed(1) + '% of market value)';
    } else {
      dutyRate = RATES.partition;
      dutyLabelText = 'Stamp duty (' + (dutyRate * 100).toFixed(1) + '% of the separated share’s value)';
    }

    var duty = value * dutyRate;
    var fee = value * RATES.registrationFee;
    var total = duty + fee;

    dutyLabel.textContent = dutyLabelText;
    dutyValue.textContent = formatRupees(duty);
    feeLabel.textContent = 'Registration fee (' + (RATES.registrationFee * 100).toFixed(0) + '% of value)';
    feeValue.textContent = formatRupees(fee);
    totalValue.textContent = formatRupees(total);
    result.hidden = false;
  });
})();
