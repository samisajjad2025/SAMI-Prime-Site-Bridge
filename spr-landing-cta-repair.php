<?php
/**
 * Plugin Name: SAMI Prime Landing CTA Repair
 * Description: Minimal, reversible checkout-button repair for the SAMI PRIME RESEARCH membership funnel.
 * Version: 0.1.0
 * Author: SAMI PRIME RESEARCH
 */
defined('ABSPATH') || exit;

add_action('wp_footer', static function () {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (rtrim((string) $path, '/') !== '/stock-market-research-education') {
        return;
    }
    ?>
<script id="spr-landing-cta-repair">
(function () {
  'use strict';
  // Exact owner-supplied Cosmofeed plan pages. Nothing here processes payment.
  const destinations = Object.freeze({
    combo: 'https://cosmofeed.com/vig/68d656dd7972d000136fcc0f',
    starter: 'https://cosmofeed.com/vig/68d64aef5dfbeb001365f320',
    swing: 'https://cosmofeed.com/vig/68d647704b66b80013cb6ae2',
    longterm: 'https://cosmofeed.com/vig/68d64cc430a078001395737b'
  });
  const selectors = Object.freeze({
    combo: '#comboBuy, [data-checkout="combo"], [data-t="mobileCombo"]',
    starter: '#starterBuy, [data-checkout="starter"], [data-t="heroStarter"], [data-t="mobileStarter"]',
    swing: '[data-checkout="swing"]',
    longterm: '[data-checkout="longterm"]'
  });
  const installed = new WeakSet();
  const watched = new WeakSet();

  function planOf(element) {
    if (!element || !element.closest) return null;
    for (const plan of Object.keys(selectors)) {
      if (element.closest(selectors[plan])) return plan;
    }
    return null;
  }

  function trackingAllowed(frameWindow) {
    try {
      return frameWindow.localStorage.getItem('spr_marketing_consent') === 'allow';
    } catch (_) {
      return false;
    }
  }

  function trackIntent(frameWindow, plan) {
    // Only explicit consent permits advertising analytics; never send personal data.
    if (!trackingAllowed(frameWindow)) return;
    try {
      const pixel = typeof frameWindow.fbq === 'function'
        ? frameWindow.fbq
        : (typeof window.fbq === 'function' ? window.fbq : null);
      if (pixel) pixel('track', 'InitiateCheckout', {
        content_name: plan,
        content_category: 'research_membership'
      });
    } catch (_) {}
    try {
      // Preserve the existing funnel -> WordPress Site Kit event bridge.
      const message = {
        type: 'spr-funnel-event',
        event: 'checkout_link_click',
        consent: 'allow',
        plan: plan,
        language: 'unknown'
      };
      if (frameWindow !== window) {
        window.postMessage(message, window.location.origin);
      } else if (typeof window.gtag === 'function') {
        window.gtag('event', 'spr_checkout_link_click', {plan: plan});
      }
    } catch (_) {}
  }

  function mount(doc) {
    try {
      if (!doc || installed.has(doc) || !doc.querySelector('#sprApp')) return;
      installed.add(doc);
      doc.addEventListener('click', function (event) {
        const target = event.target;
        const plan = planOf(target && target.nodeType === 3 ? target.parentElement : target);
        if (!plan) return;
        const destination = destinations[plan];
        if (!destination) return;
        // Handle before older button listeners. Parent-window navigation avoids
        // blocked window.top navigation and failed checkout inside an iframe.
        event.preventDefault();
        event.stopImmediatePropagation();
        event.stopPropagation();
        trackIntent(doc.defaultView || window, plan);
        window.location.assign(destination);
      }, true);
    } catch (_) {}
  }

  function scan() {
    mount(document);
    document.querySelectorAll('iframe').forEach(function (frame) {
      try {
        if (!watched.has(frame)) {
          watched.add(frame);
          frame.addEventListener('load', scan);
        }
        mount(frame.contentDocument);
      } catch (_) {
        // Cross-origin frames are intentionally inaccessible; no unsafe bypass.
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scan, {once: true});
  } else {
    scan();
  }
  [400, 1200, 3000, 7000].forEach(function (delay) { setTimeout(scan, delay); });
})();
</script>
    <?php
}, 100);
