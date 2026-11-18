<?php
/**
 * Plugin Name: SAMI Prime Landing CTA Repair
 * Description: Reversible front-end fixes for published SAMI PRIME RESEARCH funnel links.
 * Version: 0.1.2
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
  // Isolated repair only; does not edit Membership Funnel HTML, pricing or analytics.
  const destinations = Object.freeze({
    combo: 'https://cosmofeed.com/vig/68d656dd7972d000136fcc0f',
    starter: 'https://cosmofeed.com/vig/68d64aef5dfbeb001365f320',
    telegram: 'https://t.me/SAMI_Prime_Research'
  });
  const buttons = Object.freeze({
    combo: '[data-t="heroCombo"], [data-t="sampleComboCTA"], [data-t="compareCTA"], [data-t="mobileCombo"]',
    starter: '[data-t="heroStarter"], [data-t="sampleStarterCTA"], [data-t="compareStarterCTA"], [data-t="mobileStarter"]',
    telegram: '#freeTelegram'
  });
  const mounted = new WeakSet();
  const watched = new WeakSet();

  function destinationFor(element) {
    if (!element || !element.closest) return null;
    for (const type of Object.keys(buttons)) {
      if (element.closest(buttons[type])) return destinations[type];
    }
    return null;
  }

  function mount(doc) {
    try {
      if (!doc || !doc.querySelector('#sprApp')) return;
      // The exit-intent overlay uses position:fixed inside a page-height iframe;
      // its dialog can be offscreen while a dark backdrop covers all content.
      // Disable that optional popup only. Keep privacy and language choices intact.
      if (!doc.getElementById('spr-repair-no-exit-shade')) {
        const style = doc.createElement('style');
        style.id = 'spr-repair-no-exit-shade';
        style.textContent = '#sprApp #exitDialog{display:none !important}';
        (doc.head || doc.documentElement).appendChild(style);
      }
      if (mounted.has(doc)) return;
      mounted.add(doc);
      doc.addEventListener('click', function (event) {
        const raw = event.target;
        const destination = destinationFor(raw && raw.nodeType === 3 ? raw.parentElement : raw);
        if (!destination) return;
        // Only the explicitly listed CTA buttons and the Telegram link are handled.
        // Working pricing-table checkout buttons retain their existing functionality.
        event.preventDefault();
        event.stopImmediatePropagation();
        event.stopPropagation();
        // Navigate the top-level page to prevent iframe sandbox/popup restrictions.
        window.location.assign(destination);
      }, true);
    } catch (e) {
      // Cross-origin or sandboxed frames are not bypassed.
    }
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
      } catch (e) {}
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scan, {once: true});
  } else {
    scan();
  }
  if (typeof MutationObserver !== 'undefined') {
    const observer = new MutationObserver(scan);
    observer.observe(document.documentElement, {childList: true, subtree: true});
  }
  [350, 1000, 2500, 6000, 12000].forEach(function (ms) { setTimeout(scan, ms); });
})();
</script>
    <?php
}, 100);
